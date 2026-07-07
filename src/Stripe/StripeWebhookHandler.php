<?php

declare(strict_types=1);

namespace WhoisCRM\Stripe;

use WhoisCRM\Database\Models\Subscription;
use WhoisCRM\Database\Models\Payment;
use WhoisCRM\Database\Models\PackagePricing;
use WhoisCRM\Database\Models\Customer;
use WhoisCRM\Database\Models\ActivityLog;

/**
 * Stripe Webhook Handler.
 *
 * Registered as a REST endpoint at:
 *   POST /wp-json/whoiscrm/v1/webhooks/stripe
 *
 * Handles:
 *  - checkout.session.completed       → Create sub + payment + invoice
 *  - invoice.payment_succeeded        → Renew subscription + create payment
 *  - invoice.payment_failed           → Mark past_due + log
 *  - customer.subscription.updated    → Sync status changes
 *  - customer.subscription.deleted    → Mark cancelled
 */
class StripeWebhookHandler
{
    private StripeGateway $stripe;

    public function __construct()
    {
        $this->stripe = new StripeGateway();
    }

    /**
     * REST API callback. Verifies signature, dispatches to correct handler.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function handle(\WP_REST_Request $request): \WP_REST_Response
    {
        $payload    = $request->get_body();
        $sig_header = $request->get_header('stripe-signature');

        if (empty($sig_header)) {
            return new \WP_REST_Response(['error' => 'Missing signature header'], 400);
        }

        $event = $this->stripe->construct_webhook_event($payload, $sig_header);

        if (is_wp_error($event)) {
            error_log('[WHOISCRM Webhook] Signature error: ' . $event->get_error_message());
            return new \WP_REST_Response(['error' => $event->get_error_message()], 400);
        }

        // Log every webhook received
        (new ActivityLog())->log(
            ActivityLog::ACTION_WEBHOOK,
            "Stripe webhook received: {$event->type}",
            ['event_id' => $event->id],
            ActivityLog::SEVERITY_INFO,
            0
        );

        // Dispatch to the correct handler
        switch ($event->type) {
            case 'checkout.session.completed':
                $this->handle_checkout_completed($event->data->object);
                break;

            case 'invoice.payment_succeeded':
                $this->handle_invoice_succeeded($event->data->object);
                break;

            case 'invoice.payment_failed':
                $this->handle_invoice_failed($event->data->object);
                break;

            case 'customer.subscription.updated':
                $this->handle_subscription_updated($event->data->object);
                break;

            case 'customer.subscription.deleted':
                $this->handle_subscription_deleted($event->data->object);
                break;

            default:
                // Event type not handled — silently acknowledge.
                break;
        }

        return new \WP_REST_Response(['received' => true], 200);
    }

    // ─── checkout.session.completed ──────────────────────────────────────

    /**
     * First-time subscription payment succeeds.
     *
     * Creates subscription + payment records in our DB,
     * queues invoice generation, and fires email hooks.
     */
    private function handle_checkout_completed(\Stripe\Checkout\Session $session): void
    {
        $customer_id = (int) ($session->metadata->whoiscrm_customer_id ?? 0);
        $package_id  = (int) ($session->metadata->whoiscrm_package_id  ?? 0);
        $pricing_id  = (int) ($session->metadata->whoiscrm_package_pricing_id ?? 0);

        if (!$customer_id || !$package_id || !$pricing_id) {
            error_log('[WHOISCRM Webhook] checkout.session.completed missing metadata.');
            return;
        }

        // Retrieve session expanded with subscription & payment_intent
        try {
            $full_session = \Stripe\Checkout\Session::retrieve([
                'id'     => $session->id,
                'expand' => ['subscription', 'payment_intent'],
            ]);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log('[WHOISCRM Webhook] Failed to expand session: ' . $e->getMessage());
            return;
        }

        $stripe_sub = $full_session->subscription;

        if (!$stripe_sub) {
            error_log('[WHOISCRM Webhook] No subscription found in session.');
            return;
        }

        // Prevent duplicate processing
        if ((new Subscription())->find_by_stripe_id($stripe_sub->id)) {
            return;
        }

        // 1. Create subscription record
        $sub_id = (new Subscription())->insert([
            'customer_id'            => $customer_id,
            'package_id'             => $package_id,
            'package_pricing_id'     => $pricing_id,
            'stripe_subscription_id' => $stripe_sub->id,
            'status'                 => Subscription::STATUS_ACTIVE,
            'starts_at'              => gmdate('Y-m-d H:i:s', $stripe_sub->current_period_start),
            'expires_at'             => gmdate('Y-m-d H:i:s', $stripe_sub->current_period_end),
        ]);

        // 2. Create payment record
        $payment_id = $this->create_payment_record(
            $full_session,
            $customer_id,
            $package_id,
            $pricing_id,
            (int) $sub_id
        );

        // 3. Fire hooks for invoice generation + emails (Phase 9 + 10)
        do_action('whoiscrm_subscription_activated', $customer_id, (int) $sub_id, $package_id);
        do_action('whoiscrm_payment_succeeded', $customer_id, $payment_id, (int) $sub_id);

        (new ActivityLog())->log(
            ActivityLog::ACTION_SUBSCRIPTION,
            "Subscription activated via Stripe checkout. Package #{$package_id}",
            ['subscription_id' => $sub_id, 'stripe_sub_id' => $stripe_sub->id],
            ActivityLog::SEVERITY_INFO,
            0
        );
    }

    // ─── invoice.payment_succeeded ───────────────────────────────────────

    /**
     * Recurring renewal payment succeeded.
     */
    private function handle_invoice_succeeded(\Stripe\Invoice $invoice): void
    {
        $stripe_sub_id = $invoice->subscription;

        if (!$stripe_sub_id) {
            return;
        }

        $sub = (new Subscription())->find_by_stripe_id($stripe_sub_id);

        if (!$sub) {
            error_log('[WHOISCRM Webhook] invoice.payment_succeeded: subscription not found for ' . $stripe_sub_id);
            return;
        }

        // Retrieve updated subscription period
        try {
            $stripe_sub = \Stripe\Subscription::retrieve($stripe_sub_id);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log('[WHOISCRM Webhook] Failed to retrieve subscription: ' . $e->getMessage());
            return;
        }

        // Update subscription expiry and status
        (new Subscription())->update((int) $sub->id, [
            'status'     => Subscription::STATUS_ACTIVE,
            'expires_at' => gmdate('Y-m-d H:i:s', $stripe_sub->current_period_end),
        ]);

        // Create renewal payment record
        $payment_id = (new Payment())->insert([
            'customer_id'   => $sub->customer_id,
            'package_id'    => $sub->package_id,
            'subscription_id' => $sub->id,
            'stripe_invoice_id' => $invoice->id,
            'amount'        => $invoice->amount_paid / 100,
            'tax_amount'    => $invoice->tax / 100,
            'total_amount'  => $invoice->amount_paid / 100,
            'currency'      => strtoupper($invoice->currency),
            'status'        => Payment::STATUS_SUCCEEDED,
            'billing_cycle' => (new PackagePricing())->find($sub->package_pricing_id)?->billing_cycle ?? 'monthly',
            'paid_at'       => current_time('mysql', true),
        ]);

        do_action('whoiscrm_payment_succeeded', $sub->customer_id, $payment_id, (int) $sub->id);

        (new ActivityLog())->log(
            ActivityLog::ACTION_PAYMENT,
            "Renewal payment succeeded for subscription #{$sub->id}",
            ['invoice_id' => $invoice->id, 'amount' => $invoice->amount_paid / 100],
            ActivityLog::SEVERITY_INFO,
            0
        );
    }

    // ─── invoice.payment_failed ──────────────────────────────────────────

    /**
     * Recurring payment attempt failed.
     */
    private function handle_invoice_failed(\Stripe\Invoice $invoice): void
    {
        $stripe_sub_id = $invoice->subscription;

        if (!$stripe_sub_id) {
            return;
        }

        $sub = (new Subscription())->find_by_stripe_id($stripe_sub_id);

        if (!$sub) {
            return;
        }

        // Set subscription to past_due
        (new Subscription())->update((int) $sub->id, [
            'status' => Subscription::STATUS_PAST_DUE,
        ]);

        // Record failed payment
        $payment_id = (new Payment())->insert([
            'customer_id'         => $sub->customer_id,
            'package_id'          => $sub->package_id,
            'subscription_id'     => $sub->id,
            'stripe_invoice_id'   => $invoice->id,
            'amount'              => $invoice->amount_due / 100,
            'tax_amount'          => 0.0,
            'total_amount'        => $invoice->amount_due / 100,
            'currency'            => strtoupper($invoice->currency),
            'status'              => Payment::STATUS_FAILED,
            'billing_cycle'       => 'monthly',
            'paid_at'             => null,
        ]);

        do_action('whoiscrm_payment_failed', $sub->customer_id, $payment_id, (int) $sub->id);

        (new ActivityLog())->log(
            ActivityLog::ACTION_PAYMENT_FAILED,
            "Renewal payment failed for subscription #{$sub->id}",
            ['invoice_id' => $invoice->id],
            ActivityLog::SEVERITY_WARNING,
            0
        );
    }

    // ─── customer.subscription.updated ──────────────────────────────────

    /**
     * Stripe subscription status changed (e.g., trial → active, active → past_due).
     */
    private function handle_subscription_updated(\Stripe\Subscription $stripe_sub): void
    {
        $sub = (new Subscription())->find_by_stripe_id($stripe_sub->id);

        if (!$sub) {
            return;
        }

        $status_map = [
            'active'           => Subscription::STATUS_ACTIVE,
            'trialing'         => Subscription::STATUS_TRIALING,
            'past_due'         => Subscription::STATUS_PAST_DUE,
            'canceled'         => Subscription::STATUS_CANCELLED,
            'incomplete'       => Subscription::STATUS_INCOMPLETE,
            'incomplete_expired' => Subscription::STATUS_CANCELLED,
        ];

        $new_status = $status_map[$stripe_sub->status] ?? Subscription::STATUS_CANCELLED;

        (new Subscription())->update((int) $sub->id, [
            'status'     => $new_status,
            'expires_at' => gmdate('Y-m-d H:i:s', $stripe_sub->current_period_end),
        ]);
    }

    // ─── customer.subscription.deleted ──────────────────────────────────

    /**
     * Subscription fully cancelled (either by customer or after failed retries).
     */
    private function handle_subscription_deleted(\Stripe\Subscription $stripe_sub): void
    {
        $sub = (new Subscription())->find_by_stripe_id($stripe_sub->id);

        if (!$sub) {
            return;
        }

        (new Subscription())->update((int) $sub->id, [
            'status'        => Subscription::STATUS_CANCELLED,
            'cancelled_at'  => current_time('mysql', true),
            'cancel_reason' => 'Cancelled via Stripe',
        ]);

        do_action('whoiscrm_subscription_cancelled', $sub->customer_id, (int) $sub->id);

        (new ActivityLog())->log(
            ActivityLog::ACTION_SUBSCRIPTION_CANCEL,
            "Subscription #{$sub->id} cancelled via Stripe webhook.",
            ['stripe_sub_id' => $stripe_sub->id],
            ActivityLog::SEVERITY_INFO,
            0
        );
    }

    // ─── Private helpers ──────────────────────────────────────────────────

    /**
     * Create a Payment record from a completed Checkout Session.
     */
    private function create_payment_record(
        \Stripe\Checkout\Session $session,
        int $customer_id,
        int $package_id,
        int $pricing_id,
        int $subscription_id
    ): int {
        $pricing = (new PackagePricing())->find($pricing_id);

        $amount       = $session->amount_total / 100;
        $tax_amount   = ($session->total_details->amount_tax ?? 0) / 100;
        $subtotal     = $amount - $tax_amount;
        $billing_cycle = $pricing ? $pricing->billing_cycle : 'monthly';

        return (int) (new Payment())->insert([
            'customer_id'          => $customer_id,
            'package_id'           => $package_id,
            'subscription_id'      => $subscription_id,
            'stripe_payment_intent_id' => $session->payment_intent ?? '',
            'stripe_invoice_id'    => '',
            'amount'               => $subtotal,
            'tax_amount'           => $tax_amount,
            'total_amount'         => $amount,
            'currency'             => strtoupper($session->currency),
            'status'               => Payment::STATUS_SUCCEEDED,
            'billing_cycle'        => $billing_cycle,
            'paid_at'              => current_time('mysql', true),
        ]);
    }
}
