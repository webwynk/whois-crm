<?php

declare(strict_types=1);

namespace WhoisCRM;

use WhoisCRM\Database\Schema;
use WhoisCRM\Database\Migrator;
use WhoisCRM\Security\RoleManager;
use WhoisCRM\Security\FileProtection;

/**
 * Plugin activation handler.
 *
 * Runs once when the plugin is activated. Creates database tables,
 * roles, directories, and seeds default data.
 */
class Activator
{
    /**
     * Execute activation routines.
     */
    public static function activate(): void
    {
        // Verify minimum requirements before proceeding.
        if (version_compare(PHP_VERSION, '8.1', '<')) {
            deactivate_plugins(WHOISCRM_PLUGIN_BASENAME);
            wp_die(
                esc_html__('WHOIS CRM requires PHP 8.1 or higher.', 'whois-crm'),
                esc_html__('Plugin Activation Error', 'whois-crm'),
                ['back_link' => true]
            );
        }

        global $wpdb;

        // 1. Create database tables.
        Schema::create_tables();

        // 2. Set database version.
        Migrator::set_version(WHOISCRM_DB_VERSION);

        // 3. Create custom roles and capabilities.
        RoleManager::create_roles();

        // 4. Create protected data directory with .htaccess.
        FileProtection::create_data_directory();

        // 5. Seed default packages if the packages table is empty.
        self::seed_default_packages();

        // 6. Set default plugin options.
        self::set_default_options();

        // 7. Schedule cron events.
        self::schedule_cron_events();

        // 8. Store plugin version.
        update_option('whoiscrm_version', WHOISCRM_VERSION);

        // 9. Flush rewrite rules for REST API.
        flush_rewrite_rules();
    }

    /**
     * Seed default packages from the JSON file.
     */
    public static function seed_default_packages(): void
    {
        global $wpdb;

        $packages_table = $wpdb->prefix . 'whoiscrm_packages';
        $pricing_table = $wpdb->prefix . 'whoiscrm_package_pricing';

        // Only seed if the packages table is empty.
        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$packages_table}");
        if ($count > 0) {
            return;
        }

        $json_path = WHOISCRM_PLUGIN_DIR . 'data/default-packages.json';
        if (!file_exists($json_path)) {
            return;
        }

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $json = file_get_contents($json_path);
        $data = json_decode($json, true);

        if (!$data || empty($data['packages'])) {
            return;
        }

        $now = current_time('mysql', true);

        foreach ($data['packages'] as $pkg) {
            $wpdb->insert($packages_table, [
                'name'         => $pkg['name'],
                'slug'         => $pkg['slug'],
                'description'  => $pkg['description'],
                'type'         => $pkg['type'],
                'service_type' => $pkg['service_type'],
                'countries'    => $pkg['countries'] !== null ? wp_json_encode($pkg['countries']) : null,
                'tlds'         => $pkg['tlds'] !== null ? wp_json_encode($pkg['tlds']) : null,
                'features'     => wp_json_encode($pkg['features']),
                'is_active'    => 1,
                'sort_order'   => $pkg['sort_order'] ?? 0,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);

            $package_id = (int) $wpdb->insert_id;

            if ($package_id > 0 && !empty($pkg['pricing'])) {
                foreach ($pkg['pricing'] as $price) {
                    $wpdb->insert($pricing_table, [
                        'package_id'    => $package_id,
                        'billing_cycle' => $price['billing_cycle'],
                        'price'         => $price['price'],
                        'currency'      => $price['currency'],
                        'is_active'     => 1,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ]);
                }
            }
        }
    }

    /**
     * Set default plugin options.
     */
    private static function set_default_options(): void
    {
        $defaults = [
            'whoiscrm_stripe_mode'            => 'test',
            'whoiscrm_tax_enabled'            => '0',
            'whoiscrm_default_tax_rate'       => '0.00',
            'whoiscrm_tax_label'              => 'Tax',
            'whoiscrm_tax_rates'              => wp_json_encode([
                'IN' => ['rate' => 18.00, 'label' => 'GST'],
                'GB' => ['rate' => 20.00, 'label' => 'VAT'],
                'DE' => ['rate' => 19.00, 'label' => 'MwSt'],
                'FR' => ['rate' => 20.00, 'label' => 'TVA'],
                'AU' => ['rate' => 10.00, 'label' => 'GST'],
                'CA' => ['rate' => 13.00, 'label' => 'HST'],
                'AE' => ['rate' => 5.00,  'label' => 'VAT'],
                'BR' => ['rate' => 0.00,  'label' => 'Tax'],
                'US' => ['rate' => 0.00,  'label' => 'Sales Tax'],
            ]),
            'whoiscrm_seller_name'            => get_bloginfo('name'),
            'whoiscrm_seller_address'         => '',
            'whoiscrm_seller_tax_id'          => '',
            'whoiscrm_email_from_name'        => get_bloginfo('name'),
            'whoiscrm_email_from_address'     => get_bloginfo('admin_email'),
            'whoiscrm_download_rate_limit'    => '50',
            'whoiscrm_api_rate_limit'         => '1000',
            'whoiscrm_max_upload_size'        => '512',
        ];

        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }

    /**
     * Schedule WordPress cron events.
     */
    private static function schedule_cron_events(): void
    {
        if (!wp_next_scheduled('whoiscrm_cron_check_expiry')) {
            wp_schedule_event(time(), 'twicedaily', 'whoiscrm_cron_check_expiry');
        }

        if (!wp_next_scheduled('whoiscrm_cron_reset_api_limits')) {
            wp_schedule_event(time(), 'daily', 'whoiscrm_cron_reset_api_limits');
        }
    }
}
