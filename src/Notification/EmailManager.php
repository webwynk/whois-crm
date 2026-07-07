<?php

declare(strict_types=1);

namespace WhoisCRM\Notification;

use WhoisCRM\Database\Models\Customer;
use WhoisCRM\Database\Models\Package;

/**
 * Central Email Manager.
 *
 * Dispatches all system HTML notifications using wp_mail().
 * Uses settings (sender name and email address) and hooks into wp_mail filters.
 */
class EmailManager
{
    private EmailTemplateRenderer $renderer;

    public function __construct()
    {
        $this->renderer = new EmailTemplateRenderer();
    }

    // ─── Welcome Email ────────────────────────────────────────────────────

    public function send_welcome(object $user): bool
    {
        $subject = sprintf(__('Welcome to %s!', 'whois-crm'), get_bloginfo('name'));
        
        $html = $this->renderer->render('welcome', [
            'user'       => $user,
            'login_url'  => wp_login_url(),
            'site_name'  => get_bloginfo('name'),
        ]);

        return $this->send($user->user_email, $subject, $html);
    }

    // ─── Payment Confirmation Receipts ────────────────────────────────────

    public function send_payment_confirmation(object $payment): bool
    {
        $customer = (new Customer())->find((int) $payment->customer_id);
        if (!$customer) {
            return false;
        }
        $wp_user = get_userdata($customer->user_id);
        if (!$wp_user) {
            return false;
        }

        $package = (new Package())->find((int) $payment->package_id);
        $subject = sprintf(__('Payment Confirmed - Invoice #%s', 'whois-crm'), $payment->invoice_number ?? $payment->id);

        $html = $this->renderer->render('payment-confirmation', [
            'wp_user'      => $wp_user,
            'payment'      => $payment,
            'package_name' => $package ? $package->name : 'Subscription',
        ]);

        return $this->send($wp_user->user_email, $subject, $html);
    }

    // ─── Attached Invoice Notifications ────────────────────────────────────

    public function send_invoice_attached(object $invoice): bool
    {
        $customer = (new Customer())->find((int) $invoice->customer_id);
        if (!$customer) {
            return false;
        }
        $wp_user = get_userdata($customer->user_id);
        if (!$wp_user) {
            return false;
        }

        $subject = sprintf(__('Your Invoice %s is Ready', 'whois-crm'), $invoice->invoice_number);

        $attachments = [];
        if (!empty($invoice->pdf_path) && file_exists(WHOISCRM_DATA_DIR . $invoice->pdf_path)) {
            $attachments[] = WHOISCRM_DATA_DIR . $invoice->pdf_path;
        }

        $html = $this->renderer->render('invoice-attached', [
            'wp_user' => $wp_user,
            'invoice' => $invoice,
        ]);

        return $this->send($wp_user->user_email, $subject, $html, $attachments);
    }

    // ─── Plan Confirmation / Activation ────────────────────────────────────

    public function send_subscription_activated(object $subscription): bool
    {
        $customer = (new Customer())->find((int) $subscription->customer_id);
        if (!$customer) {
            return false;
        }
        $wp_user = get_userdata($customer->user_id);
        if (!$wp_user) {
            return false;
        }

        $package = (new Package())->find((int) $subscription->package_id);
        $subject = sprintf(__('Subscription Activated: %s', 'whois-crm'), $package ? $package->name : 'Feed');

        $html = $this->renderer->render('subscription-activated', [
            'wp_user'      => $wp_user,
            'subscription' => $subscription,
            'package_name' => $package ? $package->name : 'Subscription Feed',
            'portal_url'   => get_permalink(get_option('whoiscrm_portal_page_id')),
        ]);

        return $this->send($wp_user->user_email, $subject, $html);
    }

    // ─── 7-Day Renewal Warnings ───────────────────────────────────────────

    public function send_expiry_reminder_7day(object $subscription): bool
    {
        $email = $subscription->customer_email ?? '';
        if (empty($email)) {
            return false;
        }

        $subject = sprintf(__('Renewal Warning: 7 Days Left for %s', 'whois-crm'), $subscription->package_name);

        $html = $this->renderer->render('expiry-reminder-7day', [
            'subscription' => $subscription,
            'portal_url'   => get_permalink(get_option('whoiscrm_portal_page_id')),
        ]);

        return $this->send($email, $subject, $html);
    }

    // ─── 1-Day Renewal Warnings ───────────────────────────────────────────

    public function send_expiry_reminder_1day(object $subscription): bool
    {
        $email = $subscription->customer_email ?? '';
        if (empty($email)) {
            return false;
        }

        $subject = sprintf(__('Action Required: 24 Hours Left for %s', 'whois-crm'), $subscription->package_name);

        $html = $this->renderer->render('expiry-reminder-1day', [
            'subscription' => $subscription,
            'portal_url'   => get_permalink(get_option('whoiscrm_portal_page_id')),
        ]);

        return $this->send($email, $subject, $html);
    }

    // ─── Subscription Deactivation / Expiration ───────────────────────────

    public function send_subscription_expired(object $subscription): bool
    {
        $customer = (new Customer())->find((int) $subscription->customer_id);
        if (!$customer) {
            return false;
        }
        $wp_user = get_userdata($customer->user_id);
        if (!$wp_user) {
            return false;
        }

        $package = (new Package())->find((int) $subscription->package_id);
        $subject = sprintf(__('Subscription Expired: %s', 'whois-crm'), $package ? $package->name : 'Feed');

        $html = $this->renderer->render('subscription-expired', [
            'wp_user'      => $wp_user,
            'subscription' => $subscription,
            'package_name' => $package ? $package->name : 'Feed',
            'pricing_url'  => get_permalink(get_option('whoiscrm_pricing_page_id')),
        ]);

        return $this->send($wp_user->user_email, $subject, $html);
    }

    // ─── Bounces / Failed Payments ────────────────────────────────────────

    public function send_payment_failed(object $payment): bool
    {
        $customer = (new Customer())->find((int) $payment->customer_id);
        if (!$customer) {
            return false;
        }
        $wp_user = get_userdata($customer->user_id);
        if (!$wp_user) {
            return false;
        }

        $package = (new Package())->find((int) $payment->package_id);
        $subject = __('Billing Alert: Subscription Renewal Payment Failed', 'whois-crm');

        $html = $this->renderer->render('payment-failed', [
            'wp_user'      => $wp_user,
            'payment'      => $payment,
            'package_name' => $package ? $package->name : 'Subscription',
            'portal_url'   => get_permalink(get_option('whoiscrm_portal_page_id')),
        ]);

        return $this->send($wp_user->user_email, $subject, $html);
    }

    // ─── Password Recovery Updates ────────────────────────────────────────

    public function send_password_reset(object $user, string $key): bool
    {
        $subject = sprintf(__('[%s] Password Reset Link', 'whois-crm'), get_bloginfo('name'));

        // Build reset link URL
        $reset_page = get_option('whoiscrm_reset_password_page_id');
        $reset_url  = $reset_page ? get_permalink($reset_page) : wp_login_url();
        $reset_link = add_query_arg([
            'action' => 'rp',
            'key'    => $key,
            'login'  => rawurlencode($user->user_login),
        ], $reset_url);

        $html = $this->renderer->render('password-reset', [
            'user'       => $user,
            'reset_link' => $reset_link,
            'site_name'  => get_bloginfo('name'),
        ]);

        return $this->send($user->user_email, $subject, $html);
    }

    // ─── Core Sender Logic ────────────────────────────────────────────────

    /**
     * Send email with HTML wrapping and attachments.
     */
    private function send(string $to, string $subject, string $message, array $attachments = []): bool
    {
        // 1. Hook custom sender filters
        add_filter('wp_mail_from',      [$this, 'filter_sender_email']);
        add_filter('wp_mail_from_name', [$this, 'filter_sender_name']);

        $headers = ['Content-Type: text/html; charset=UTF-8'];

        $result = wp_mail($to, $subject, $message, $headers, $attachments);

        // 2. Unhook filters to prevent side effects on other plugins
        remove_filter('wp_mail_from',      [$this, 'filter_sender_email']);
        remove_filter('wp_mail_from_name', [$this, 'filter_sender_name']);

        return $result;
    }

    public function filter_sender_email(string $default): string
    {
        $sender = get_option('whoiscrm_email_sender_address');
        return !empty($sender) && is_email($sender) ? $sender : $default;
    }

    public function filter_sender_name(string $default): string
    {
        $name = get_option('whoiscrm_email_sender_name');
        return !empty($name) ? $name : $default;
    }
}
