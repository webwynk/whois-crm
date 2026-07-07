<?php
/**
 * WHOIS CRM — Uninstall Script
 *
 * Fired when the plugin is DELETED through the WordPress admin.
 * This permanently removes ALL plugin data:
 *   - Custom database tables (11 tables)
 *   - Custom roles and capabilities
 *   - All wp_options entries
 *   - Protected data directory and all uploaded files
 *   - Scheduled cron events
 *
 * WARNING: This action is IRREVERSIBLE. All customer data,
 * subscriptions, payments, and uploaded files will be lost.
 */

// Security: Only run via WordPress uninstall.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Load plugin constants if not already defined.
if (!defined('WHOISCRM_PLUGIN_DIR')) {
    define('WHOISCRM_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('WHOISCRM_DATA_DIR')) {
    define('WHOISCRM_DATA_DIR', WP_CONTENT_DIR . '/whois-data/');
}

// Load Composer autoloader for class access.
if (file_exists(WHOISCRM_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once WHOISCRM_PLUGIN_DIR . 'vendor/autoload.php';
}

// ─── 1. Drop all custom database tables ──────────────────────────────────────
if (class_exists(\WhoisCRM\Database\Schema::class)) {
    \WhoisCRM\Database\Schema::drop_tables();
}

// ─── 2. Remove custom roles and capabilities ────────────────────────────────
if (class_exists(\WhoisCRM\Security\RoleManager::class)) {
    \WhoisCRM\Security\RoleManager::remove_roles();
}

// ─── 3. Delete all plugin options from wp_options ───────────────────────────
global $wpdb;

// Delete all options with our prefix.
$wpdb->query(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'whoiscrm\_%'"
);

// Also delete transients used by the plugin.
$wpdb->query(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_whoiscrm\_%'"
);
$wpdb->query(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_whoiscrm\_%'"
);

// ─── 4. Remove data directory and all files ─────────────────────────────────
if (class_exists(\WhoisCRM\Security\FileProtection::class)) {
    \WhoisCRM\Security\FileProtection::remove_data_directory();
} elseif (file_exists(WHOISCRM_DATA_DIR)) {
    // Fallback: manual recursive delete if class isn't available.
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(WHOISCRM_DATA_DIR, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        if ($item->isDir()) {
            rmdir($item->getPathname());
        } else {
            unlink($item->getPathname());
        }
    }

    rmdir(WHOISCRM_DATA_DIR);
}

// ─── 5. Clear scheduled cron events ─────────────────────────────────────────
$cron_events = [
    'whoiscrm_cron_check_expiry',
    'whoiscrm_cron_send_reminders',
    'whoiscrm_cron_reset_api_limits',
    'whoiscrm_cron_cleanup_logs',
];

foreach ($cron_events as $event) {
    $timestamp = wp_next_scheduled($event);
    if ($timestamp) {
        wp_unschedule_event($timestamp, $event);
    }
}

// ─── 6. Remove user meta for CRM customers ─────────────────────────────────
// Change all whoiscrm_customer users back to 'subscriber' role.
$customer_users = get_users(['role' => 'whoiscrm_customer']);
foreach ($customer_users as $user) {
    $user->set_role('subscriber');
}

// ─── 7. Flush rewrite rules ─────────────────────────────────────────────────
flush_rewrite_rules();
