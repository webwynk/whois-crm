<?php

declare(strict_types=1);

namespace WhoisCRM\Admin;

/**
 * Enqueues admin CSS and JS assets only on WHOIS CRM pages.
 */
class AdminAssets
{
    public function __construct()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
    }

    public function enqueue(string $hook): void
    {
        // Only load on our own admin pages.
        if (!$this->is_whoiscrm_page($hook)) {
            return;
        }

        // Google Fonts — DM Sans
        wp_enqueue_style(
            'whoiscrm-fonts',
            'https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap',
            [],
            null
        );

        // Design system (CSS variables, keyframes)
        wp_enqueue_style(
            'whoiscrm-design-system',
            WHOISCRM_PLUGIN_URL . 'assets/css/design-system.css',
            ['whoiscrm-fonts'],
            WHOISCRM_VERSION
        );

        // Shared components
        wp_enqueue_style(
            'whoiscrm-components',
            WHOISCRM_PLUGIN_URL . 'assets/css/components.css',
            ['whoiscrm-design-system'],
            WHOISCRM_VERSION
        );

        // Admin-specific layout
        wp_enqueue_style(
            'whoiscrm-admin',
            WHOISCRM_PLUGIN_URL . 'assets/css/admin.css',
            ['whoiscrm-components'],
            WHOISCRM_VERSION
        );

        // Admin JS
        wp_enqueue_script(
            'whoiscrm-admin-js',
            WHOISCRM_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            WHOISCRM_VERSION,
            true
        );

        // Pass data to JS
        wp_localize_script('whoiscrm-admin-js', 'whoisCRM', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('whoiscrm_admin_nonce'),
            'i18n'    => [
                'confirm_delete'  => __('Are you sure? This action cannot be undone.', 'whois-crm'),
                'loading'         => __('Loading…', 'whois-crm'),
                'saved'           => __('Saved successfully.', 'whois-crm'),
                'error'           => __('An error occurred. Please try again.', 'whois-crm'),
                'copied'          => __('Copied!', 'whois-crm'),
            ],
        ]);
    }

    /**
     * Check if we are on any WHOIS CRM admin page.
     */
    private function is_whoiscrm_page(string $hook): bool
    {
        return strpos($hook, 'whoiscrm') !== false;
    }
}
