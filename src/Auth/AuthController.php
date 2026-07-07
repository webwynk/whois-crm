<?php

declare(strict_types=1);

namespace WhoisCRM\Auth;

use WhoisCRM\Database\Models\Customer;
use WhoisCRM\Database\Models\ActivityLog;

/**
 * Authentication AJAX handler.
 *
 * Registers nopriv AJAX actions for login, register,
 * forgot password, and reset password.
 * Uses WordPress core functions for all password operations
 * so we never handle raw credentials beyond the POST request.
 */
class AuthController
{
    public function __construct()
    {
        // Login
        add_action('wp_ajax_nopriv_whoiscrm_login',          [$this, 'handle_login']);

        // Register
        add_action('wp_ajax_nopriv_whoiscrm_register',       [$this, 'handle_register']);

        // Forgot password (send reset email)
        add_action('wp_ajax_nopriv_whoiscrm_forgot_password', [$this, 'handle_forgot_password']);

        // Reset password (submit new password)
        add_action('wp_ajax_nopriv_whoiscrm_reset_password',  [$this, 'handle_reset_password']);

        // Logout (priv — logged-in users)
        add_action('wp_ajax_whoiscrm_logout',                 [$this, 'handle_logout']);
    }

    // ─── Login ──────────────────────────────────────────────────────────

    /**
     * Handle login form AJAX submission.
     */
    public function handle_login(): void
    {
        check_ajax_referer('whoiscrm_login_nonce', 'nonce');

        $email    = sanitize_email(wp_unslash($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';           // raw — passed directly to wp_authenticate
        $remember = !empty($_POST['remember']);

        if (!is_email($email)) {
            wp_send_json_error(['message' => __('Please enter a valid email address.', 'whois-crm')]);
        }

        if (empty($password)) {
            wp_send_json_error(['message' => __('Please enter your password.', 'whois-crm')]);
        }

        // Brute-force: check recent failed attempts.
        if ($this->is_rate_limited_login($email)) {
            wp_send_json_error(['message' => __('Too many login attempts. Please wait 15 minutes and try again.', 'whois-crm')]);
        }

        // WordPress core authentication.
        $user = wp_authenticate($email, $password);

        if (is_wp_error($user)) {
            // Log failed attempt.
            (new ActivityLog())->log_login_failed($email);
            $this->record_failed_login($email);

            // Return generic message (don't reveal whether email exists).
            wp_send_json_error(['message' => __('Invalid email address or password.', 'whois-crm')]);
        }

        // Verify the user has the customer role (not just any WP user).
        if (!in_array('whoiscrm_customer', $user->roles, true) && !user_can($user, 'manage_options')) {
            wp_send_json_error(['message' => __('This account does not have portal access.', 'whois-crm')]);
        }

        // Check if the customer account is active.
        $customer = (new Customer())->find_by_user_id($user->ID);
        if ($customer && !(bool) $customer->is_active) {
            wp_send_json_error(['message' => __('Your account has been suspended. Please contact support.', 'whois-crm')]);
        }

        // Set auth cookie and log.
        wp_set_auth_cookie($user->ID, $remember, is_ssl());
        wp_set_current_user($user->ID);

        (new ActivityLog())->log_login($user->ID);
        $this->clear_failed_logins($email);

        // Determine redirect target.
        $redirect = '';
        if (!empty($_POST['redirect_to'])) {
            $redirect = esc_url_raw(wp_unslash($_POST['redirect_to']));
        }

        if (empty($redirect) || !$this->is_safe_redirect($redirect)) {
            $portal_page = get_option('whoiscrm_portal_page_id');
            $redirect    = $portal_page ? get_permalink($portal_page) : home_url('/my-account/');
        }

        wp_send_json_success([
            'message'  => __('Login successful! Redirecting…', 'whois-crm'),
            'redirect' => $redirect,
        ]);
    }

    // ─── Register ────────────────────────────────────────────────────────

    /**
     * Handle registration form AJAX submission.
     */
    public function handle_register(): void
    {
        check_ajax_referer('whoiscrm_register_nonce', 'nonce');

        $first_name = sanitize_text_field(wp_unslash($_POST['first_name'] ?? ''));
        $last_name  = sanitize_text_field(wp_unslash($_POST['last_name']  ?? ''));
        $email      = sanitize_email(wp_unslash($_POST['email']           ?? ''));
        $password   = $_POST['password'] ?? '';
        $company    = sanitize_text_field(wp_unslash($_POST['company_name'] ?? ''));

        // ── Validation ──────────────────────────────────────────────────
        $errors = [];

        if (empty($first_name)) {
            $errors[] = __('First name is required.', 'whois-crm');
        }
        if (!is_email($email)) {
            $errors[] = __('Please enter a valid email address.', 'whois-crm');
        }
        if (email_exists($email)) {
            $errors[] = __('An account with this email address already exists.', 'whois-crm');
        }
        if (strlen($password) < 8) {
            $errors[] = __('Password must be at least 8 characters long.', 'whois-crm');
        }

        if (!empty($errors)) {
            wp_send_json_error(['message' => implode(' ', $errors)]);
        }

        // ── Create WordPress user ────────────────────────────────────────
        $user_id = wp_create_user($email, $password, $email);

        if (is_wp_error($user_id)) {
            wp_send_json_error(['message' => $user_id->get_error_message()]);
        }

        // Update display name and meta.
        wp_update_user([
            'ID'           => $user_id,
            'first_name'   => $first_name,
            'last_name'    => $last_name,
            'display_name' => trim("{$first_name} {$last_name}"),
        ]);

        // Assign customer role (remove default subscriber role).
        $user = new \WP_User($user_id);
        $user->set_role('whoiscrm_customer');

        // ── Create CRM customer profile ──────────────────────────────────
        (new Customer())->insert([
            'user_id'      => $user_id,
            'company_name' => $company,
            'is_active'    => 1,
        ]);

        // ── Log activity ─────────────────────────────────────────────────
        (new ActivityLog())->log(
            ActivityLog::ACTION_REGISTER,
            "New customer registered: {$email}",
            ['email' => $email, 'company' => $company],
            ActivityLog::SEVERITY_INFO,
            $user_id
        );

        // ── Auto-login ───────────────────────────────────────────────────
        wp_set_auth_cookie($user_id, false, is_ssl());
        wp_set_current_user($user_id);

        // ── Send welcome email (Phase 10) ────────────────────────────────
        // EmailManager::send_welcome() will be called here once Phase 10 is built.
        do_action('whoiscrm_customer_registered', $user_id);

        $portal_page = get_option('whoiscrm_portal_page_id');
        $redirect    = $portal_page ? get_permalink($portal_page) : home_url('/my-account/');

        wp_send_json_success([
            'message'  => __('Account created! Redirecting to your dashboard…', 'whois-crm'),
            'redirect' => $redirect,
        ]);
    }

    // ─── Forgot Password ─────────────────────────────────────────────────

    /**
     * Send a password reset email.
     */
    public function handle_forgot_password(): void
    {
        check_ajax_referer('whoiscrm_forgot_password_nonce', 'nonce');

        $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));

        if (!is_email($email)) {
            wp_send_json_error(['message' => __('Please enter a valid email address.', 'whois-crm')]);
        }

        // Always return success even if email doesn't exist (prevent user enumeration).
        $user = get_user_by('email', $email);

        if ($user) {
            // Use WordPress core to generate and send the reset email.
            // We override the reset URL to point to our custom page via a filter.
            add_filter('lostpassword_redirect', '__return_empty_string');

            $result = retrieve_password($email);

            if (is_wp_error($result)) {
                // Log quietly but don't expose to user.
                error_log('WHOIS CRM forgot password error: ' . $result->get_error_message());
            }

            (new ActivityLog())->log(
                ActivityLog::ACTION_PASSWORD_RESET,
                "Password reset requested for: {$email}",
                [],
                ActivityLog::SEVERITY_INFO,
                $user->ID
            );
        }

        wp_send_json_success([
            'message' => __('If an account exists with that email, a reset link has been sent. Please check your inbox.', 'whois-crm'),
        ]);
    }

    // ─── Reset Password ───────────────────────────────────────────────────

    /**
     * Handle the password reset form (after clicking the email link).
     */
    public function handle_reset_password(): void
    {
        check_ajax_referer('whoiscrm_reset_password_nonce', 'nonce');

        $key      = sanitize_text_field(wp_unslash($_POST['key']      ?? ''));
        $login    = sanitize_text_field(wp_unslash($_POST['login']    ?? ''));
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm']  ?? '';

        if (strlen($password) < 8) {
            wp_send_json_error(['message' => __('Password must be at least 8 characters.', 'whois-crm')]);
        }

        if ($password !== $confirm) {
            wp_send_json_error(['message' => __('Passwords do not match.', 'whois-crm')]);
        }

        // Verify the reset key using WordPress core.
        $user = check_password_reset_key($key, $login);

        if (is_wp_error($user)) {
            wp_send_json_error(['message' => __('Invalid or expired reset link. Please request a new one.', 'whois-crm')]);
        }

        // Reset the password.
        reset_password($user, $password);

        (new ActivityLog())->log(
            ActivityLog::ACTION_PASSWORD_RESET,
            'Password successfully reset.',
            [],
            ActivityLog::SEVERITY_INFO,
            $user->ID
        );

        $login_page = get_option('whoiscrm_login_page_id');
        $redirect   = $login_page ? get_permalink($login_page) : home_url('/login/');

        wp_send_json_success([
            'message'  => __('Password reset successful! You can now log in with your new password.', 'whois-crm'),
            'redirect' => $redirect,
        ]);
    }

    // ─── Logout ───────────────────────────────────────────────────────────

    /**
     * Log out the current user.
     */
    public function handle_logout(): void
    {
        check_ajax_referer('whoiscrm_logout_nonce', 'nonce');

        $user_id = get_current_user_id();

        if ($user_id) {
            (new ActivityLog())->log(
                ActivityLog::ACTION_LOGOUT,
                'Customer logged out.',
                [],
                ActivityLog::SEVERITY_INFO,
                $user_id
            );
        }

        wp_logout();

        $login_page = get_option('whoiscrm_login_page_id');
        $redirect   = $login_page ? get_permalink($login_page) : home_url('/login/');

        wp_send_json_success(['redirect' => $redirect]);
    }

    // ─── Rate Limiting ────────────────────────────────────────────────────

    /**
     * Check if this email has too many recent failed logins.
     * Stores attempts in transients.
     */
    private function is_rate_limited_login(string $email): bool
    {
        $key      = 'whoiscrm_failed_logins_' . md5($email);
        $attempts = (int) get_transient($key);

        return $attempts >= 5; // Max 5 attempts before 15-min lockout.
    }

    /**
     * Record a failed login attempt.
     */
    private function record_failed_login(string $email): void
    {
        $key      = 'whoiscrm_failed_logins_' . md5($email);
        $attempts = (int) get_transient($key);
        set_transient($key, $attempts + 1, 15 * MINUTE_IN_SECONDS);
    }

    /**
     * Clear failed login records on successful login.
     */
    private function clear_failed_logins(string $email): void
    {
        delete_transient('whoiscrm_failed_logins_' . md5($email));
    }

    /**
     * Check that a redirect URL is on the same domain (prevents open redirect).
     */
    private function is_safe_redirect(string $url): bool
    {
        $site_host     = wp_parse_url(home_url(), PHP_URL_HOST);
        $redirect_host = wp_parse_url($url, PHP_URL_HOST);

        return $redirect_host === $site_host || empty($redirect_host);
    }
}
