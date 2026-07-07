<?php

declare(strict_types=1);

namespace WhoisCRM\Admin\Pages;

/**
 * Plugin Settings admin page.
 *
 * Organised into tabs: Payment, Tax, Invoice, Email, Pages, Security, Upload.
 * All values stored in wp_options with the whoiscrm_ prefix.
 */
class SettingsPage extends BasePage
{
    protected static string $required_cap = 'whoiscrm_manage_settings';

    /** Registered tabs. */
    private const TABS = [
        'payment'  => 'Payment',
        'tax'      => 'Tax',
        'invoice'  => 'Invoice',
        'email'    => 'Email',
        'pages'    => 'Pages',
        'security' => 'Security',
        'upload'   => 'Upload',
    ];

    protected function display(): void
    {
        // ── Save handler ─────────────────────────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['whoiscrm_settings_nonce'])) {
            $this->save_settings();
        }

        $active_tab = sanitize_key($_GET['tab'] ?? 'payment');
        if (!array_key_exists($active_tab, self::TABS)) {
            $active_tab = 'payment';
        }

        $this->page_header(__('Settings', 'whois-crm'));
        $this->show_notices('whoiscrm_settings');
        $this->render_template('settings/layout', [
            'tabs'       => self::TABS,
            'active_tab' => $active_tab,
            'options'    => $this->get_all_options(),
            'pages_list' => $this->get_pages_list(),
            'nonce'      => wp_create_nonce('whoiscrm_settings_nonce'),
        ]);
        $this->page_footer();
    }

    /**
     * Save all settings from POST, organised by tab.
     */
    private function save_settings(): void
    {
        if (!check_admin_referer('whoiscrm_settings_nonce', 'whoiscrm_settings_nonce')) {
            wp_die(__('Security check failed.', 'whois-crm'));
        }

        if (!current_user_can(static::$required_cap)) {
            wp_die(__('Unauthorized.', 'whois-crm'));
        }

        // Mapping of option key → sanitizer function.
        $fields = [
            // Payment
            'whoiscrm_stripe_mode'                  => 'sanitize_key',
            'whoiscrm_stripe_test_publishable_key'   => 'sanitize_text_field',
            'whoiscrm_stripe_test_secret_key'        => 'sanitize_text_field',
            'whoiscrm_stripe_live_publishable_key'   => 'sanitize_text_field',
            'whoiscrm_stripe_live_secret_key'        => 'sanitize_text_field',
            'whoiscrm_stripe_webhook_secret'         => 'sanitize_text_field',

            // Tax
            'whoiscrm_tax_enabled'        => null,   // checkbox — handled below
            'whoiscrm_default_tax_rate'   => 'floatval',
            'whoiscrm_tax_label'          => 'sanitize_text_field',
            'whoiscrm_tax_rates'          => 'sanitize_textarea_field',

            // Invoice
            'whoiscrm_seller_name'        => 'sanitize_text_field',
            'whoiscrm_seller_address'     => 'sanitize_textarea_field',
            'whoiscrm_seller_tax_id'      => 'sanitize_text_field',

            // Email
            'whoiscrm_email_from_name'    => 'sanitize_text_field',
            'whoiscrm_email_from_address' => 'sanitize_email',

            // Pages
            'whoiscrm_login_page_id'              => 'absint',
            'whoiscrm_register_page_id'           => 'absint',
            'whoiscrm_forgot_password_page_id'    => 'absint',
            'whoiscrm_reset_password_page_id'     => 'absint',
            'whoiscrm_portal_page_id'             => 'absint',
            'whoiscrm_pricing_page_id'            => 'absint',

            // Security
            'whoiscrm_download_rate_limit' => 'absint',
            'whoiscrm_api_rate_limit'      => 'absint',

            // Upload
            'whoiscrm_max_upload_size'    => 'absint',
        ];

        foreach ($fields as $key => $sanitizer) {
            if ($sanitizer === null) {
                // Checkbox
                update_option($key, !empty($_POST[$key]) ? 1 : 0);
            } elseif (isset($_POST[$key])) {
                $raw   = wp_unslash($_POST[$key]);
                $value = $sanitizer($raw);
                update_option($key, $value);
            }
        }

        self::set_notice('whoiscrm_settings', __('Settings saved.', 'whois-crm'));

        $tab = sanitize_key($_POST['current_tab'] ?? 'payment');
        wp_safe_redirect(admin_url("admin.php?page=whoiscrm-settings&tab={$tab}"));
        exit;
    }

    /**
     * Load all plugin option values in one call.
     *
     * @return array<string, mixed>
     */
    private function get_all_options(): array
    {
        $defaults = [
            'whoiscrm_stripe_mode'                => 'test',
            'whoiscrm_stripe_test_publishable_key' => '',
            'whoiscrm_stripe_test_secret_key'      => '',
            'whoiscrm_stripe_live_publishable_key' => '',
            'whoiscrm_stripe_live_secret_key'      => '',
            'whoiscrm_stripe_webhook_secret'       => '',
            'whoiscrm_tax_enabled'                 => 0,
            'whoiscrm_default_tax_rate'            => 0.0,
            'whoiscrm_tax_label'                   => 'Tax',
            'whoiscrm_tax_rates'                   => '',
            'whoiscrm_seller_name'                 => get_bloginfo('name'),
            'whoiscrm_seller_address'              => '',
            'whoiscrm_seller_tax_id'               => '',
            'whoiscrm_email_from_name'             => get_bloginfo('name'),
            'whoiscrm_email_from_address'          => get_bloginfo('admin_email'),
            'whoiscrm_login_page_id'               => 0,
            'whoiscrm_register_page_id'            => 0,
            'whoiscrm_forgot_password_page_id'     => 0,
            'whoiscrm_reset_password_page_id'      => 0,
            'whoiscrm_portal_page_id'              => 0,
            'whoiscrm_pricing_page_id'             => 0,
            'whoiscrm_download_rate_limit'         => 50,
            'whoiscrm_api_rate_limit'              => 1000,
            'whoiscrm_max_upload_size'             => 512,
        ];

        $options = [];
        foreach ($defaults as $key => $default) {
            $options[$key] = get_option($key, $default);
        }

        return $options;
    }

    /**
     * Get all published pages as [id => title] for the Pages settings tab.
     *
     * @return array<int, string>
     */
    private function get_pages_list(): array
    {
        $pages  = get_pages(['post_status' => 'publish']);
        $result = [0 => '— ' . __('Select a page', 'whois-crm') . ' —'];

        foreach ($pages as $page) {
            $result[$page->ID] = $page->post_title;
        }

        return $result;
    }
}
