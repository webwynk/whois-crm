<?php
/**
 * Plugin Name:       WHOIS CRM
 * Plugin URI:        https://yoursite.com/whois-crm
 * Description:       Subscription-based WHOIS data distribution platform with admin dashboard, customer portal, and Stripe payments.
 * Version:           1.0.6
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            WHOIS CRM
 * Author URI:        https://yoursite.com
 * License:           Proprietary
 * Text Domain:       whois-crm
 * Domain Path:       /languages
 */

declare(strict_types=1);

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}

// ─── Plugin Constants ─────────────────────────────────────────────────────────
define('WHOISCRM_VERSION', '1.0.6');
define('WHOISCRM_DB_VERSION', '1.0.3');
define('WHOISCRM_PLUGIN_FILE', __FILE__);
define('WHOISCRM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WHOISCRM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WHOISCRM_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('WHOISCRM_DATA_DIR', WP_CONTENT_DIR . '/whois-data/');
define('WHOISCRM_DATA_URL', content_url('/whois-data/'));
define('WHOISCRM_INVOICE_DIR', WP_CONTENT_DIR . '/whois-data/invoices/');

// ─── Composer Autoloader ──────────────────────────────────────────────────────
if (file_exists(WHOISCRM_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once WHOISCRM_PLUGIN_DIR . 'vendor/autoload.php';
} else {
    // Autoloader not found — show admin notice and bail.
    add_action('admin_notices', function (): void {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__(
            'WHOIS CRM: Composer dependencies are not installed. Please run "composer install" in the plugin directory.',
            'whois-crm'
        );
        echo '</p></div>';
    });
    return;
}

// ─── Activation & Deactivation ────────────────────────────────────────────────
register_activation_hook(__FILE__, [\WhoisCRM\Activator::class, 'activate']);
register_deactivation_hook(__FILE__, [\WhoisCRM\Deactivator::class, 'deactivate']);

// ─── Bootstrap Plugin ─────────────────────────────────────────────────────────
add_action('plugins_loaded', static function (): void {
    WhoisCRM\Plugin::get_instance();
}, 10);
