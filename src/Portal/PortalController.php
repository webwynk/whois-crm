<?php

declare(strict_types=1);

namespace WhoisCRM\Portal;

use WhoisCRM\Database\Models\Customer;
use WhoisCRM\Database\Models\ApiKey;
use WhoisCRM\Database\Models\Subscription;
use WhoisCRM\Database\Models\ActivityLog;

/**
 * Customer Portal AJAX Actions Controller.
 *
 * Handles:
 *  - Profile information saving & password updates
 *  - API key generation
 *  - API key revocation
 */
class PortalController
{
    public function __construct()
    {
        add_action('wp_ajax_whoiscrm_update_profile',     [$this, 'update_profile']);
        add_action('wp_ajax_whoiscrm_generate_api_key',   [$this, 'generate_api_key']);
        add_action('wp_ajax_whoiscrm_revoke_api_key',     [$this, 'revoke_api_key']);
    }

    // ─── Profile & Account Updating ──────────────────────────────────────

    /**
     * AJAX handler to save user profile changes.
     */
    public function update_profile(): void
    {
        check_ajax_referer('whoiscrm_profile_nonce', 'nonce');

        $user_id = get_current_user_id();

        if ($user_id <= 0) {
            wp_send_json_error(['message' => __('Unauthorized session.', 'whois-crm')]);
        }

        $customer_model = new Customer();
        $customer       = $customer_model->find_by_user_id($user_id);

        if (!$customer) {
            wp_send_json_error(['message' => __('Customer profile not found.', 'whois-crm')]);
        }

        // ── Standard WP profile details ──────────────────────────────
        $first_name = sanitize_text_field(wp_unslash($_POST['first_name'] ?? ''));
        $last_name  = sanitize_text_field(wp_unslash($_POST['last_name']  ?? ''));
        $email      = sanitize_email(wp_unslash($_POST['email']           ?? ''));

        if (empty($email)) {
            wp_send_json_error(['message' => __('Email address is required.', 'whois-crm')]);
        }

        // Verify email uniqueness if changed
        $wp_user = get_userdata($user_id);
        if ($wp_user->user_email !== $email) {
            $existing_user_id = email_exists($email);
            if ($existing_user_id && $existing_user_id !== $user_id) {
                wp_send_json_error(['message' => __('This email address is already in use.', 'whois-crm')]);
            }

            // Update user email
            $update_res = wp_update_user([
                'ID'         => $user_id,
                'user_email' => $email,
            ]);

            if (is_wp_error($update_res)) {
                wp_send_json_error(['message' => $update_res->get_error_message()]);
            }
        }

        // Update first name & last name in user meta
        update_user_meta($user_id, 'first_name', $first_name);
        update_user_meta($user_id, 'last_name',  $last_name);

        // ── CRM Company & Billing details ────────────────────────────
        $company_name    = sanitize_text_field(wp_unslash($_POST['company_name']    ?? ''));
        $phone           = sanitize_text_field(wp_unslash($_POST['phone']           ?? ''));
        $country_code    = strtoupper(sanitize_text_field(wp_unslash($_POST['country_code'] ?? '')));
        $billing_address = sanitize_textarea_field(wp_unslash($_POST['billing_address'] ?? ''));
        $tax_id          = sanitize_text_field(wp_unslash($_POST['tax_id']          ?? ''));

        $customer_model->update((int) $customer->id, [
            'company_name'    => $company_name,
            'phone'           => $phone,
            'country_code'    => $country_code,
            'billing_address' => $billing_address,
            'tax_id'          => $tax_id,
        ]);

        // ── Password change check ─────────────────────────────────────
        $current_password = $_POST['current_password'] ?? '';
        $new_password     = $_POST['new_password']     ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                wp_send_json_error(['message' => __('To change your password, please fill in all password fields.', 'whois-crm')]);
            }

            if (!wp_check_password($current_password, $wp_user->user_pass, $user_id)) {
                wp_send_json_error(['message' => __('Current password is incorrect.', 'whois-crm')]);
            }

            if ($new_password !== $confirm_password) {
                wp_send_json_error(['message' => __('Passwords do not match.', 'whois-crm')]);
            }

            if (strlen($new_password) < 8) {
                wp_send_json_error(['message' => __('New password must be at least 8 characters long.', 'whois-crm')]);
            }

            wp_set_password($new_password, $user_id);
            // Re-auth cookie so session stays alive
            wp_set_auth_cookie($user_id);
        }

        // ── Log activity ──────────────────────────────────────────────
        (new ActivityLog())->log(
            ActivityLog::ACTION_PROFILE_UPDATE,
            'Profile settings updated',
            [],
            ActivityLog::SEVERITY_INFO,
            $user_id
        );

        wp_send_json_success(['message' => __('Changes saved successfully.', 'whois-crm')]);
    }

    // ─── API Key Actions ──────────────────────────────────────────────────

    /**
     * AJAX handler to generate a new API credential key.
     */
    public function generate_api_key(): void
    {
        check_ajax_referer('whoiscrm_api_nonce', 'nonce');

        $user_id = get_current_user_id();

        if ($user_id <= 0) {
            wp_send_json_error(['message' => __('Unauthorized session.', 'whois-crm')]);
        }

        $customer = (new Customer())->find_by_user_id($user_id);

        if (!$customer) {
            wp_send_json_error(['message' => __('Customer profile not found.', 'whois-crm')]);
        }

        // Verify that the customer has an active Enterprise subscription
        if (!$this->has_active_enterprise((int) $customer->id)) {
            wp_send_json_error(['message' => __('API Keys are restricted to Enterprise subscribers.', 'whois-crm')]);
        }

        $plain_key = (new ApiKey())->generate_for_customer((int) $customer->id);

        if (empty($plain_key)) {
            wp_send_json_error(['message' => __('Failed to generate API Key.', 'whois-crm')]);
        }

        (new ActivityLog())->log(
            ActivityLog::ACTION_ADMIN_ACTION,
            'Generated new developer REST API key',
            [],
            ActivityLog::SEVERITY_INFO,
            $user_id
        );

        wp_send_json_success([
            'api_key' => $plain_key,
        ]);
    }

    /**
     * AJAX handler to revoke the existing API credential key.
     */
    public function revoke_api_key(): void
    {
        check_ajax_referer('whoiscrm_api_nonce', 'nonce');

        $user_id = get_current_user_id();

        if ($user_id <= 0) {
            wp_send_json_error(['message' => __('Unauthorized session.', 'whois-crm')]);
        }

        $customer = (new Customer())->find_by_user_id($user_id);

        if (!$customer) {
            wp_send_json_error(['message' => __('Customer profile not found.', 'whois-crm')]);
        }

        (new ApiKey())->delete_for_customer((int) $customer->id);

        (new ActivityLog())->log(
            ActivityLog::ACTION_ADMIN_ACTION,
            'Revoked developer REST API key',
            [],
            ActivityLog::SEVERITY_WARNING,
            $user_id
        );

        wp_send_json_success(['message' => __('API key successfully revoked.', 'whois-crm')]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    /**
     * Check if a customer has an active enterprise plan subscription.
     */
    private function has_active_enterprise(int $customer_id): bool
    {
        $subscriptions = (new Subscription())->get_active_for_customer($customer_id);
        foreach ($subscriptions as $sub) {
            if ($sub->service_type === 'enterprise') {
                return true;
            }
        }
        return false;
    }
}
