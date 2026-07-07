<?php

declare(strict_types=1);

namespace WhoisCRM\Auth;

/**
 * WordPress URL and redirect filters for custom authentication pages.
 *
 * Redirects customers away from wp-login.php and wp-admin.
 * Overrides WordPress login/register/lostpassword URLs to point
 * to our custom shortcode pages.
 */
class AuthRedirects
{
    public function __construct()
    {
        // Redirect customers away from wp-admin.
        add_action('template_redirect', [$this, 'redirect_customers_from_wp_admin']);

        // Override WordPress built-in auth URLs.
        add_filter('login_url',        [$this, 'custom_login_url'],        10, 3);
        add_filter('register_url',     [$this, 'custom_register_url']);
        add_filter('lostpassword_url', [$this, 'custom_lostpassword_url'], 10, 2);

        // Override password reset URL in WP-generated emails.
        add_filter('retrieve_password_message', [$this, 'custom_reset_email_url'], 10, 4);

        // Redirect customers to portal after WP login (covers any direct wp-login access).
        add_action('wp_login', [$this, 'redirect_after_login'], 10, 2);

        // Redirect logged-in users away from login/register pages.
        add_action('template_redirect', [$this, 'redirect_logged_in_from_auth_pages']);

        // Force HTTPS on auth pages (if SSL is available).
        add_action('template_redirect', [$this, 'force_ssl_on_auth_pages']);
    }

    /**
     * Redirect whoiscrm_customer users away from /wp-admin.
     * Admins are still allowed in.
     */
    public function redirect_customers_from_wp_admin(): void
    {
        if (
            is_admin() &&
            !wp_doing_ajax() &&
            current_user_can('whoiscrm_view_portal') &&
            !current_user_can('manage_options')
        ) {
            $portal_page = get_option('whoiscrm_portal_page_id');
            $redirect    = $portal_page ? get_permalink($portal_page) : home_url('/my-account/');
            wp_safe_redirect($redirect);
            exit;
        }
    }

    /**
     * Replace the default login URL with our custom login page.
     */
    public function custom_login_url(string $login_url, string $redirect, bool $force_reauth): string
    {
        $page_id = (int) get_option('whoiscrm_login_page_id');

        if (!$page_id) {
            return $login_url;
        }

        $url = get_permalink($page_id) ?: $login_url;

        if ($redirect) {
            $url = add_query_arg('redirect_to', rawurlencode($redirect), $url);
        }

        if ($force_reauth) {
            $url = add_query_arg('reauth', '1', $url);
        }

        return $url;
    }

    /**
     * Replace the default register URL with our custom register page.
     */
    public function custom_register_url(): string
    {
        $page_id = (int) get_option('whoiscrm_register_page_id');

        if (!$page_id) {
            return wp_registration_url();
        }

        return get_permalink($page_id) ?: wp_registration_url();
    }

    /**
     * Replace the lost password URL with our custom forgot-password page.
     */
    public function custom_lostpassword_url(string $lostpassword_url, string $redirect): string
    {
        $page_id = (int) get_option('whoiscrm_forgot_password_page_id');

        if (!$page_id) {
            return $lostpassword_url;
        }

        $url = get_permalink($page_id) ?: $lostpassword_url;

        if ($redirect) {
            $url = add_query_arg('redirect_to', rawurlencode($redirect), $url);
        }

        return $url;
    }

    /**
     * Replace the reset URL in WordPress password-reset emails.
     *
     * The default reset link goes to /wp-login.php?action=rp. We replace
     * it with our custom reset page that renders [whoiscrm_reset_password].
     */
    public function custom_reset_email_url(string $message, string $key, string $user_login, \WP_User $user_data): string
    {
        $reset_page_id = (int) get_option('whoiscrm_reset_password_page_id');

        if (!$reset_page_id) {
            return $message;
        }

        $reset_page_url = get_permalink($reset_page_id);

        if (!$reset_page_url) {
            return $message;
        }

        // Build the custom reset URL.
        $custom_reset_url = add_query_arg([
            'action' => 'rp',
            'key'    => $key,
            'login'  => rawurlencode($user_login),
        ], $reset_page_url);

        // Replace the default rp URL in the email body.
        $message = preg_replace(
            '#<' . network_site_url("wp-login.php?action=rp&key={$key}&login=" . rawurlencode($user_login), 'login') . '>#',
            '<' . esc_url($custom_reset_url) . '>',
            $message
        );

        return $message;
    }

    /**
     * After any WP login, redirect customers to the portal.
     *
     * This is a safety net in case someone bypasses our form and
     * uses the WordPress login page directly.
     */
    public function redirect_after_login(string $user_login, \WP_User $user): void
    {
        if (in_array('whoiscrm_customer', $user->roles, true)) {
            $portal_page = get_option('whoiscrm_portal_page_id');
            $redirect    = $portal_page ? get_permalink($portal_page) : home_url('/my-account/');
            wp_safe_redirect($redirect);
            exit;
        }
    }

    /**
     * Redirect logged-in customers away from login/register pages.
     */
    public function redirect_logged_in_from_auth_pages(): void
    {
        if (!is_user_logged_in()) {
            return;
        }

        // Only redirect customers, not admins.
        if (current_user_can('manage_options')) {
            return;
        }

        $auth_pages = [
            (int) get_option('whoiscrm_login_page_id'),
            (int) get_option('whoiscrm_register_page_id'),
        ];

        if (is_page($auth_pages)) {
            $portal_page = get_option('whoiscrm_portal_page_id');
            $redirect    = $portal_page ? get_permalink($portal_page) : home_url('/my-account/');
            wp_safe_redirect($redirect);
            exit;
        }
    }

    /**
     * Force HTTPS on auth pages if the site supports SSL.
     */
    public function force_ssl_on_auth_pages(): void
    {
        if (!is_ssl() && force_ssl_admin()) {
            $auth_pages = [
                (int) get_option('whoiscrm_login_page_id'),
                (int) get_option('whoiscrm_register_page_id'),
                (int) get_option('whoiscrm_forgot_password_page_id'),
                (int) get_option('whoiscrm_reset_password_page_id'),
            ];

            if (is_page($auth_pages)) {
                wp_safe_redirect('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
                exit;
            }
        }
    }
}
