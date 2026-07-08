<?php
/**
 * Plugin Name:       WHOIS CRM
 * Plugin URI:        https://yoursite.com/whois-crm
 * Description:       Subscription-based WHOIS data distribution platform with admin dashboard, customer portal, and Stripe payments.
 * Version:           1.0.8
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

// Diagnostics: Shutdown handler to capture and print fatal errors
register_shutdown_function(static function (): void {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        echo '<div style="background:#ffebee;color:#c62828;padding:20px;border:1px solid #ef9a9a;margin:20px;font-family:monospace;z-index:999999;position:relative;display:block;">';
        echo '<h3>PHP Fatal Error caught by Whois CRM Debugger</h3>';
        echo '<strong>Message:</strong> ' . esc_html($error['message']) . '<br>';
        echo '<strong>File:</strong> ' . esc_html($error['file']) . '<br>';
        echo '<strong>Line:</strong> ' . esc_html($error['line']) . '<br>';
        echo '</div>';
    }
});

// ─── Plugin Constants ─────────────────────────────────────────────────────────
define('WHOISCRM_VERSION', '1.0.8');
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
