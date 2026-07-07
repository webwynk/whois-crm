<?php

declare(strict_types=1);

namespace WhoisCRM\Stripe;

use WhoisCRM\Database\Models\Customer;
use WhoisCRM\Database\Models\PackagePricing;
use WhoisCRM\Database\Models\Coupon;
use WhoisCRM\Database\Models\ActivityLog;

/**
 * Handles checkout AJAX actions from the pricing/portal pages.
 *
 * Registered as:
 *  wp_ajax_whoiscrm_create_checkout        — logged-in customers
 *  wp_ajax_nopriv_whoiscrm_create_checkout — redirect non-logged-in to login
 *  wp_ajax_whoiscrm_cancel_subscription    — logged-in customers
 *  wp_ajax_whoiscrm_validate_coupon        — logged-in and anonymous
 */
class CheckoutHandler
{
    private StripeGateway $stripe;

    public function __construct()
    {
        $this->stripe = new StripeGateway();

        add_action('wp_ajax_whoiscrm_create_checkout',        [$this, 'create_checkout_session']);
        add_action('wp_ajax_nopriv_whoiscrm_create_checkout', [$this, 'require_login']);

        add_action('wp_ajax_whoiscrm_cancel_subscription',    [$this, 'cancel_subscription']);

        add_action('wp_ajax_whoiscrm_validate_coupon',        [$this, 'validate_coupon']);
        add_action('wp_ajax_nopriv_whoiscrm_validate_coupon', [$this, 'validate_coupon']);
    }

    // ─── Create Checkout Session ─────────────────────────────────────────

    /**
     * Validate coupon + create Stripe Checkout Session.
     *
     * Returns {checkout_url} to redirect the customer to Stripe hosted page.
     */
    public function create_checkout_session(): void
    {
        check_ajax_referer('whoiscrm_checkout_nonce', 'nonce');

        $pricing_id  = (int) ($_POST['pricing_id']  ?? 0);
        $coupon_code = sanitize_text_field(wp_unslash($_POST['coupon_code'] ?? ''));

        if ($pricing_id < 1) {
            wp_send_json_error(['message' => __('Invalid plan selected.', 'whois-crm')]);
        }

        $customer_id = $this->get_current_customer_id();

        if (!$customer_id) {
            wp_send_json_error(['message' => __('Customer profile not found.', 'whois-crm')]);
        }

        // ── Check for existing active subscription to this package ────
        $pricing  = (new PackagePricing())->find($pricing_id);
        if (!$pricing) {
            wp_send_json_error(['message' => __('Plan not found.', 'whois-crm')]);
        }

        // ── Resolve coupon to Stripe coupon ID ────────────────────────
        $stripe_coupon_id = '';

        if (!empty($coupon_code)) {
            $coupon_result = $this->resolve_stripe_coupon($coupon_code, (int) $pricing->package_id, 0);

            if (is_wp_error($coupon_result)) {
                wp_send_json_error(['message' => $coupon_result->get_error_message()]);
            }

            $stripe_coupon_id = $coupon_result;
        }

        // ── Create Stripe Checkout Session ────────────────────────────
        $session = $this->stripe->create_checkout_session(
            $customer_id,
            $pricing_id,
            $stripe_coupon_id
        );

        if (is_wp_error($session)) {
            wp_send_json_error(['message' => $session->get_error_message()]);
        }

        wp_send_json_success([
            'checkout_url' => $session->url,
        ]);
    }

    // ─── Cancel Subscription ─────────────────────────────────────────────

    /**
     * Customer cancels their own subscription.
     *
     * Cancels at period end in Stripe (access continues until expires_at).
     */
    public function cancel_subscription(): void
    {
        check_ajax_referer('whoiscrm_portal_nonce', 'nonce');

        $subscription_id = (int) ($_POST['subscription_id'] ?? 0);

        if ($subscription_id < 1) {
            wp_send_json_error(['message' => __('Invalid subscription.', 'whois-crm')]);
        }

        $customer_id = $this->get_current_customer_id();

        if (!$customer_id) {
            wp_send_json_error(['message' => __('Unauthorized.', 'whois-crm')]);
        }

        // Verify subscription belongs to this customer
        $sub = (new \WhoisCRM\Database\Models\Subscription())->find($subscription_id);

        if (!$sub || (int) $sub->customer_id !== $customer_id) {
            wp_send_json_error(['message' => __('Subscription not found.', 'whois-crm')]);
        }

        if (empty($sub->stripe_subscription_id)) {
            // Manual subscription — cancel immediately in our DB
            (new \WhoisCRM\Database\Models\Subscription())->update($subscription_id, [
                'status'        => \WhoisCRM\Database\Models\Subscription::STATUS_CANCELLED,
                'cancelled_at'  => current_time('mysql', true),
                'cancel_reason' => 'Customer requested cancellation',
            ]);
        } else {
            // Stripe subscription — cancel at period end
            $success = $this->stripe->cancel_at_period_end($sub->stripe_subscription_id);

            if (!$success) {
                wp_send_json_error(['message' => __('Failed to cancel subscription. Please contact support.', 'whois-crm')]);
            }

            (new \WhoisCRM\Database\Models\Subscription())->update($subscription_id, [
                'status'        => \WhoisCRM\Database\Models\Subscription::STATUS_CANCELLED,
                'cancelled_at'  => current_time('mysql', true),
                'cancel_reason' => 'Customer requested cancellation',
            ]);
        }

        (new ActivityLog())->log(
            ActivityLog::ACTION_SUBSCRIPTION_CANCEL,
            "Customer cancelled subscription #{$subscription_id}",
            [],
            ActivityLog::SEVERITY_INFO,
            get_current_user_id()
        );

        do_action('whoiscrm_subscription_cancelled', $customer_id, $subscription_id);

        wp_send_json_success([
            'message' => __('Your subscription has been cancelled. You will retain access until the end of your current billing period.', 'whois-crm'),
        ]);
    }

    // ─── Validate Coupon (live preview) ──────────────────────────────────

    /**
     * AJAX: Validate a coupon code and return the discount amount.
     *
     * Used on the pricing/checkout page for live discount preview.
     */
    public function validate_coupon(): void
    {
        check_ajax_referer('whoiscrm_checkout_nonce', 'nonce');

        $code       = strtoupper(sanitize_text_field(wp_unslash($_POST['code']       ?? '')));
        $package_id = (int) ($_POST['package_id'] ?? 0);
        $subtotal   = (float) ($_POST['subtotal']  ?? 0);

        if (empty($code)) {
            wp_send_json_error(['message' => __('Please enter a coupon code.', 'whois-crm')]);
        }

        $coupon = (new Coupon())->find_by_code($code);

        if (!$coupon) {
            wp_send_json_error(['message' => __('Invalid coupon code.', 'whois-crm')]);
        }

        $validation = (new Coupon())->validate($coupon, $subtotal, $package_id);

        if (!$validation['valid']) {
            wp_send_json_error(['message' => $validation['error']]);
        }

        $discount = (new Coupon())->calculate_discount($coupon, $subtotal);

        wp_send_json_success([
            'code'             => $coupon->code,
            'type'             => $coupon->type,
            'value'            => $coupon->value,
            'discount_amount'  => $discount,
            'final_total'      => max(0, $subtotal - $discount),
            'message'          => sprintf(
                /* translators: %s = discount amount */
                __('Coupon applied! You save $%s.', 'whois-crm'),
                number_format($discount, 2)
            ),
        ]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    /**
     * Redirect non-logged-in users trying to checkout.
     */
    public function require_login(): void
    {
        $login_page = get_option('whoiscrm_login_page_id');
        $login_url  = $login_page ? get_permalink($login_page) : wp_login_url();

        wp_send_json_error([
            'message'       => __('Please sign in to subscribe.', 'whois-crm'),
            'redirect'      => add_query_arg('redirect_to', rawurlencode(home_url('/pricing/')), $login_url),
            'require_login' => true,
        ]);
    }

    /**
     * Get the CRM customer_id for the currently logged-in user.
     */
    private function get_current_customer_id(): int
    {
        $user_id = get_current_user_id();

        if (!$user_id) {
            return 0;
        }

        $customer = (new Customer())->find_by_user_id($user_id);

        return $customer ? (int) $customer->id : 0;
    }

    /**
     * Validate a coupon code and return its Stripe coupon ID.
     *
     * Syncs to Stripe if the coupon doesn't have a Stripe ID yet.
     *
     * @return string|\WP_Error  Stripe coupon ID.
     */
    private function resolve_stripe_coupon(string $code, int $package_id, float $subtotal)
    {
        $coupon = (new Coupon())->find_by_code(strtoupper($code));

        if (!$coupon) {
            return new \WP_Error('invalid', __('Invalid coupon code.', 'whois-crm'));
        }

        $validation = (new Coupon())->validate($coupon, $subtotal, $package_id);

        if (!$validation['valid']) {
            return new \WP_Error('invalid', $validation['error']);
        }

        // Sync to Stripe if needed
        if (empty($coupon->stripe_coupon_id)) {
            $stripe_id = (new StripeGateway())->sync_coupon($coupon);

            if (is_wp_error($stripe_id)) {
                // Non-fatal — coupon exists in our DB, Stripe sync failed
                error_log('[WHOISCRM CheckoutHandler] Coupon Stripe sync failed: ' . $stripe_id->get_error_message());
                return '';
            }

            (new Coupon())->update((int) $coupon->id, ['stripe_coupon_id' => $stripe_id]);
            return $stripe_id;
        }

        return $coupon->stripe_coupon_id;
    }
}
