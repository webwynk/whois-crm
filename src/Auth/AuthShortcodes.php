<?php

declare(strict_types=1);

namespace WhoisCRM\Auth;

/**
 * Authentication shortcodes.
 *
 * Renders custom login/register/forgot-password/reset-password forms.
 * Each shortcode outputs a self-contained HTML form styled with our CSS.
 * Forms submit via AJAX to AuthController handlers.
 *
 * Shortcodes:
 *  [whoiscrm_login]
 *  [whoiscrm_register]
 *  [whoiscrm_forgot_password]
 *  [whoiscrm_reset_password]
 */
class AuthShortcodes
{
    public function __construct()
    {
        add_shortcode('whoiscrm_login',           [$this, 'render_login']);
        add_shortcode('whoiscrm_register',        [$this, 'render_register']);
        add_shortcode('whoiscrm_forgot_password', [$this, 'render_forgot_password']);
        add_shortcode('whoiscrm_reset_password',  [$this, 'render_reset_password']);
    }

    /**
     * Render the login form.
     */
    public function render_login(array $atts = []): string
    {
        if (is_user_logged_in()) {
            return $this->get_already_logged_in_notice();
        }

        $redirect_to = esc_url(sanitize_text_field(wp_unslash($_GET['redirect_to'] ?? '')));

        ob_start();
        $this->render_template('auth/login-form', [
            'nonce'       => wp_create_nonce('whoiscrm_login_nonce'),
            'redirect_to' => $redirect_to,
            'register_url' => get_permalink((int) get_option('whoiscrm_register_page_id')) ?: '#',
            'forgot_url'   => get_permalink((int) get_option('whoiscrm_forgot_password_page_id')) ?: '#',
            'ajax_url'     => admin_url('admin-ajax.php'),
        ]);
        return ob_get_clean() ?: '';
    }

    /**
     * Render the registration form.
     */
    public function render_register(array $atts = []): string
    {
        if (is_user_logged_in()) {
            return $this->get_already_logged_in_notice();
        }

        ob_start();
        $this->render_template('auth/register-form', [
            'nonce'     => wp_create_nonce('whoiscrm_register_nonce'),
            'login_url' => get_permalink((int) get_option('whoiscrm_login_page_id')) ?: wp_login_url(),
            'ajax_url'  => admin_url('admin-ajax.php'),
        ]);
        return ob_get_clean() ?: '';
    }

    /**
     * Render the forgot password form.
     */
    public function render_forgot_password(array $atts = []): string
    {
        if (is_user_logged_in()) {
            return $this->get_already_logged_in_notice();
        }

        ob_start();
        $this->render_template('auth/forgot-password-form', [
            'nonce'     => wp_create_nonce('whoiscrm_forgot_password_nonce'),
            'login_url' => get_permalink((int) get_option('whoiscrm_login_page_id')) ?: wp_login_url(),
            'ajax_url'  => admin_url('admin-ajax.php'),
        ]);
        return ob_get_clean() ?: '';
    }

    /**
     * Render the reset password form (linked from email).
     */
    public function render_reset_password(array $atts = []): string
    {
        $key   = sanitize_text_field(wp_unslash($_GET['key']   ?? ''));
        $login = sanitize_text_field(wp_unslash($_GET['login'] ?? ''));

        if (empty($key) || empty($login)) {
            return '<div class="whoiscrm-alert whoiscrm-alert--danger">' .
                   esc_html__('Invalid reset link. Please request a new password reset.', 'whois-crm') .
                   '</div>';
        }

        // Validate the key before rendering the form.
        $user = check_password_reset_key($key, $login);

        if (is_wp_error($user)) {
            return '<div class="whoiscrm-alert whoiscrm-alert--danger">' .
                   esc_html__('This reset link has expired or is invalid. Please request a new one.', 'whois-crm') .
                   '</div>';
        }

        ob_start();
        $this->render_template('auth/reset-password-form', [
            'nonce'     => wp_create_nonce('whoiscrm_reset_password_nonce'),
            'key'       => $key,
            'login'     => $login,
            'ajax_url'  => admin_url('admin-ajax.php'),
        ]);
        return ob_get_clean() ?: '';
    }

    /**
     * Load and render a PHP template with variables.
     *
     * @param string $template  Template path relative to /templates/ (no .php extension).
     * @param array  $vars      Variables to extract into the template scope.
     */
    private function render_template(string $template, array $vars = []): void
    {
        $path = WHOISCRM_PLUGIN_DIR . 'templates/' . $template . '.php';

        if (!file_exists($path)) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log("WHOIS CRM: Template not found: {$path}");
            return;
        }

        // Extract variables into local scope for the template.
        extract($vars, EXTR_SKIP); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

        require $path;
    }

    /**
     * Get already signed-in fallback notice HTML.
     */
    private function get_already_logged_in_notice(): string
    {
        $portal_page = get_option('whoiscrm_portal_page_id');
        $portal_url  = $portal_page ? get_permalink($portal_page) : home_url('/');

        return sprintf(
            '<div class="whoiscrm-portal-auth-notice" style="text-align: center; max-width: 480px; margin: 40px auto; padding: 30px; border: 1px solid var(--color-border); border-radius: var(--radius-lg); background: var(--color-surface); box-shadow: var(--shadow-sm);">
                <h3 style="margin-top: 0; color: var(--color-text-primary); font-weight: 600;">%1$s</h3>
                <p style="color: var(--color-text-secondary); margin-bottom: 20px;">%2$s</p>
                <a href="%3$s" class="whoiscrm-btn whoiscrm-btn--primary" style="text-decoration: none;">%4$s</a>
            </div>',
            esc_html__('Already Signed In', 'whois-crm'),
            esc_html__('You are already signed in to your account.', 'whois-crm'),
            esc_url($portal_url),
            esc_html__('Go to Customer Portal', 'whois-crm')
        );
    }
}
