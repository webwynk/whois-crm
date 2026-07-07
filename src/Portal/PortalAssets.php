<?php

declare(strict_types=1);

namespace WhoisCRM\Portal;

/**
 * Enqueues customer portal CSS and JS assets on demand.
 *
 * Checks if the active post/page contains any WHOIS CRM portal shortcodes
 * to prevent enqueuing assets on unrelated WordPress pages.
 */
class PortalAssets
{
    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue']);
    }

    /**
     * Enqueue portal stylesheets and scripts.
     */
    public function enqueue(): void
    {
        // Prevent loading assets on pages that do not use our shortcodes
        if (!is_singular() || !$this->has_whoiscrm_shortcode()) {
            return;
        }

        // Google Fonts — DM Sans
        wp_enqueue_style(
            'whoiscrm-fonts',
            'https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap',
            [],
            null
        );

        // Design System
        wp_enqueue_style(
            'whoiscrm-design-system',
            WHOISCRM_PLUGIN_URL . 'assets/css/design-system.css',
            ['whoiscrm-fonts'],
            WHOISCRM_VERSION
        );

        // Shared Components
        wp_enqueue_style(
            'whoiscrm-components',
            WHOISCRM_PLUGIN_URL . 'assets/css/components.css',
            ['whoiscrm-design-system'],
            WHOISCRM_VERSION
        );

        // Portal layout & templates styling
        wp_enqueue_style(
            'whoiscrm-portal',
            WHOISCRM_PLUGIN_URL . 'assets/css/portal.css',
            ['whoiscrm-components'],
            WHOISCRM_VERSION
        );

        // Portal client-side script
        wp_enqueue_script(
            'whoiscrm-portal-js',
            WHOISCRM_PLUGIN_URL . 'assets/js/portal.js',
            [],
            WHOISCRM_VERSION,
            true
        );

        // Pass global URLs and nonces
        wp_localize_script('whoiscrm-portal-js', 'whoisCRMPortal', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
        ]);
    }

    /**
     * Determine if the current page contains our portal shortcodes.
     */
    private function has_whoiscrm_shortcode(): bool
    {
        global $post;

        if (!$post || empty($post->post_content)) {
            return false;
        }

        return has_shortcode($post->post_content, 'whoiscrm_portal') ||
               has_shortcode($post->post_content, 'whoiscrm_pricing') ||
               has_shortcode($post->post_content, 'whoiscrm_login') ||
               has_shortcode($post->post_content, 'whoiscrm_register') ||
               has_shortcode($post->post_content, 'whoiscrm_forgot_password') ||
               has_shortcode($post->post_content, 'whoiscrm_reset_password');
    }
}
