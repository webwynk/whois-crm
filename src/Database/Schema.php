<?php

declare(strict_types=1);

namespace WhoisCRM\Database;

/**
 * Database schema creator.
 *
 * Creates all 11 custom tables using WordPress dbDelta().
 * Called during plugin activation and schema migrations.
 *
 * IMPORTANT: dbDelta formatting rules:
 *  - Each field on its own line
 *  - Two spaces between PRIMARY KEY and the definition
 *  - Use KEY keyword (not INDEX)
 *  - No backticks around identifiers
 *  - No FOREIGN KEY constraints (added separately)
 */
class Schema
{
    /**
     * Create all plugin database tables.
     */
    public static function create_tables(): void
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // ── 1. Packages ──────────────────────────────────────────────────
        $sql = "CREATE TABLE {$prefix}whoiscrm_packages (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description text NULL,
            type varchar(30) NOT NULL DEFAULT 'global_service',
            service_type varchar(30) NOT NULL,
            countries longtext NULL,
            tlds longtext NULL,
            features longtext NULL,
            stripe_product_id varchar(255) NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            sort_order int NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY  (id),
            UNIQUE KEY idx_slug (slug),
            KEY idx_type (type),
            KEY idx_service_type (service_type),
            KEY idx_is_active (is_active)
        ) $charset_collate;";
        dbDelta($sql);

        // ── 2. Package Pricing ───────────────────────────────────────────
        $sql = "CREATE TABLE {$prefix}whoiscrm_package_pricing (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            package_id bigint(20) unsigned NOT NULL,
            billing_cycle varchar(20) NOT NULL,
            price decimal(10,2) NOT NULL,
            currency varchar(3) NOT NULL DEFAULT 'USD',
            stripe_price_id varchar(255) NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY  (id),
            KEY idx_package_id (package_id),
            KEY idx_billing_cycle (billing_cycle),
            UNIQUE KEY idx_package_cycle (package_id, billing_cycle, currency)
        ) $charset_collate;";
        dbDelta($sql);

        // ── 3. Customers ─────────────────────────────────────────────────
        $sql = "CREATE TABLE {$prefix}whoiscrm_customers (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            company_name varchar(255) NULL,
            phone varchar(50) NULL,
            billing_address text NULL,
            country_code varchar(2) NULL,
            tax_id varchar(100) NULL,
            stripe_customer_id varchar(255) NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            notes text NULL,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY  (id),
            UNIQUE KEY idx_user_id (user_id),
            KEY idx_stripe_customer_id (stripe_customer_id),
            KEY idx_country_code (country_code),
            KEY idx_is_active (is_active)
        ) $charset_collate;";
        dbDelta($sql);

        // ── 4. Subscriptions ─────────────────────────────────────────────
        $sql = "CREATE TABLE {$prefix}whoiscrm_subscriptions (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            customer_id bigint(20) unsigned NOT NULL,
            package_id bigint(20) unsigned NOT NULL,
            package_pricing_id bigint(20) unsigned NOT NULL,
            stripe_subscription_id varchar(255) NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            starts_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            expires_at datetime NULL,
            cancelled_at datetime NULL,
            cancel_reason varchar(500) NULL,
            trial_ends_at datetime NULL,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY  (id),
            KEY idx_customer_id (customer_id),
            KEY idx_package_id (package_id),
            KEY idx_status (status),
            KEY idx_expires_at (expires_at),
            KEY idx_stripe_subscription_id (stripe_subscription_id)
        ) $charset_collate;";
        dbDelta($sql);

        // ── 5. Payments ──────────────────────────────────────────────────
        $sql = "CREATE TABLE {$prefix}whoiscrm_payments (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            customer_id bigint(20) unsigned NOT NULL,
            subscription_id bigint(20) unsigned NULL,
            coupon_id bigint(20) unsigned NULL,
            stripe_payment_intent_id varchar(255) NULL,
            stripe_invoice_id varchar(255) NULL,
            stripe_checkout_session_id varchar(255) NULL,
            amount decimal(10,2) NOT NULL,
            discount_amount decimal(10,2) NOT NULL DEFAULT 0.00,
            subtotal decimal(10,2) NOT NULL,
            tax_rate decimal(5,2) NOT NULL DEFAULT 0.00,
            tax_amount decimal(10,2) NOT NULL DEFAULT 0.00,
            total_amount decimal(10,2) NOT NULL,
            currency varchar(3) NOT NULL DEFAULT 'USD',
            status varchar(30) NOT NULL DEFAULT 'pending',
            payment_method varchar(50) NULL,
            refund_amount decimal(10,2) NOT NULL DEFAULT 0.00,
            refund_reason varchar(500) NULL,
            paid_at datetime NULL,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY  (id),
            KEY idx_customer_id (customer_id),
            KEY idx_subscription_id (subscription_id),
            KEY idx_status (status),
            KEY idx_paid_at (paid_at),
            KEY idx_stripe_pi (stripe_payment_intent_id)
        ) $charset_collate;";
        dbDelta($sql);

        // ── 6. Invoices ──────────────────────────────────────────────────
        $sql = "CREATE TABLE {$prefix}whoiscrm_invoices (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            payment_id bigint(20) unsigned NOT NULL,
            customer_id bigint(20) unsigned NOT NULL,
            invoice_number varchar(50) NOT NULL,
            invoice_date date NOT NULL,
            due_date date NULL,
            billing_name varchar(255) NOT NULL,
            billing_email varchar(255) NOT NULL,
            billing_company varchar(255) NULL,
            billing_address text NULL,
            billing_country varchar(2) NULL,
            billing_tax_id varchar(100) NULL,
            seller_name varchar(255) NOT NULL,
            seller_address text NULL,
            seller_tax_id varchar(100) NULL,
            line_item_description varchar(500) NOT NULL,
            quantity int NOT NULL DEFAULT 1,
            unit_price decimal(10,2) NOT NULL,
            subtotal decimal(10,2) NOT NULL,
            discount_description varchar(255) NULL,
            discount_amount decimal(10,2) NOT NULL DEFAULT 0.00,
            taxable_amount decimal(10,2) NOT NULL,
            tax_label varchar(50) NOT NULL DEFAULT 'Tax',
            tax_rate decimal(5,2) NOT NULL DEFAULT 0.00,
            tax_amount decimal(10,2) NOT NULL DEFAULT 0.00,
            total decimal(10,2) NOT NULL,
            currency varchar(3) NOT NULL DEFAULT 'USD',
            payment_status varchar(20) NOT NULL DEFAULT 'paid',
            pdf_path varchar(500) NULL,
            notes text NULL,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY  (id),
            UNIQUE KEY idx_invoice_number (invoice_number),
            KEY idx_payment_id (payment_id),
            KEY idx_customer_id (customer_id),
            KEY idx_invoice_date (invoice_date)
        ) $charset_collate;";
        dbDelta($sql);

        // ── 7. Data Files ────────────────────────────────────────────────
        $sql = "CREATE TABLE {$prefix}whoiscrm_data_files (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            filename varchar(255) NOT NULL,
            original_filename varchar(255) NOT NULL,
            file_path varchar(500) NOT NULL,
            file_size bigint(20) unsigned NOT NULL DEFAULT 0,
            file_type varchar(10) NOT NULL,
            mime_type varchar(100) NULL,
            country_code varchar(10) NOT NULL DEFAULT '',
            country_name varchar(100) NOT NULL DEFAULT '',
            tld varchar(20) NULL,
            data_date date NOT NULL,
            service_type varchar(30) NOT NULL,
            notes text NULL,
            record_count int unsigned NULL,
            checksum varchar(64) NULL,
            uploaded_by bigint(20) unsigned NOT NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY  (id),
            KEY idx_country_code (country_code),
            KEY idx_data_date (data_date),
            KEY idx_service_type (service_type),
            KEY idx_tld (tld),
            KEY idx_is_active (is_active),
            KEY idx_country_date (country_code, data_date),
            KEY idx_svc_country_date (service_type, country_code, data_date)
        ) $charset_collate;";
        dbDelta($sql);

        // ── 8. Downloads ─────────────────────────────────────────────────
        $sql = "CREATE TABLE {$prefix}whoiscrm_downloads (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            customer_id bigint(20) unsigned NOT NULL,
            data_file_id bigint(20) unsigned NOT NULL,
            subscription_id bigint(20) unsigned NULL,
            file_size bigint(20) unsigned NULL,
            ip_address varchar(45) NOT NULL,
            user_agent varchar(500) NULL,
            download_source varchar(10) NOT NULL DEFAULT 'portal',
            downloaded_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY  (id),
            KEY idx_customer_id (customer_id),
            KEY idx_data_file_id (data_file_id),
            KEY idx_downloaded_at (downloaded_at),
            KEY idx_customer_date (customer_id, downloaded_at)
        ) $charset_collate;";
        dbDelta($sql);

        // ── 9. Coupons ───────────────────────────────────────────────────
        $sql = "CREATE TABLE {$prefix}whoiscrm_coupons (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            code varchar(50) NOT NULL,
            description varchar(255) NULL,
            type varchar(20) NOT NULL,
            value decimal(10,2) NOT NULL,
            currency varchar(3) NOT NULL DEFAULT 'USD',
            max_uses int unsigned NULL,
            used_count int unsigned NOT NULL DEFAULT 0,
            max_uses_per_customer int unsigned NULL DEFAULT 1,
            min_amount decimal(10,2) NULL,
            applicable_packages longtext NULL,
            starts_at datetime NULL,
            expires_at datetime NULL,
            stripe_coupon_id varchar(255) NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY  (id),
            UNIQUE KEY idx_code (code),
            KEY idx_is_active (is_active),
            KEY idx_expires_at (expires_at)
        ) $charset_collate;";
        dbDelta($sql);

        // ── 10. API Keys ─────────────────────────────────────────────────
        $sql = "CREATE TABLE {$prefix}whoiscrm_api_keys (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            customer_id bigint(20) unsigned NOT NULL,
            api_key varchar(64) NOT NULL,
            api_secret_hash varchar(255) NOT NULL,
            name varchar(100) NOT NULL DEFAULT 'Default',
            permissions longtext NULL,
            rate_limit_per_day int unsigned NOT NULL DEFAULT 1000,
            requests_today int unsigned NOT NULL DEFAULT 0,
            last_used_at datetime NULL,
            last_used_ip varchar(45) NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY  (id),
            UNIQUE KEY idx_api_key (api_key),
            KEY idx_customer_id (customer_id),
            KEY idx_is_active (is_active)
        ) $charset_collate;";
        dbDelta($sql);

        // ── 11. Activity Log ─────────────────────────────────────────────
        $sql = "CREATE TABLE {$prefix}whoiscrm_activity_log (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NULL,
            action varchar(100) NOT NULL,
            description text NULL,
            object_type varchar(50) NULL,
            object_id bigint(20) unsigned NULL,
            ip_address varchar(45) NOT NULL DEFAULT '',
            user_agent varchar(500) NULL,
            metadata longtext NULL,
            severity varchar(10) NOT NULL DEFAULT 'info',
            created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
            PRIMARY KEY  (id),
            KEY idx_user_id (user_id),
            KEY idx_action (action),
            KEY idx_created_at (created_at),
            KEY idx_severity (severity),
            KEY idx_ip_address (ip_address),
            KEY idx_object (object_type, object_id)
        ) $charset_collate;";
        dbDelta($sql);

        // ── Add Foreign Key Constraints ──────────────────────────────────
        // dbDelta doesn't support FOREIGN KEY, so we add them separately.
        // Wrapped in try/catch — constraints are nice-to-have, not critical.
        self::add_foreign_keys($prefix);

        // ── Set Timestamps Default ───────────────────────────────────────
        // Update default timestamps since dbDelta needs '0000-00-00 00:00:00'
        // but we want CURRENT_TIMESTAMP behavior in the application layer.
    }

    /**
     * Add foreign key constraints to tables.
     *
     * These are added separately because dbDelta() does not support them.
     * Failures are silently ignored — the app works without them.
     */
    private static function add_foreign_keys(string $prefix): void
    {
        global $wpdb;

        $constraints = [
            "ALTER TABLE {$prefix}whoiscrm_package_pricing
                ADD CONSTRAINT fk_pricing_package
                FOREIGN KEY (package_id) REFERENCES {$prefix}whoiscrm_packages(id)
                ON DELETE CASCADE",

            "ALTER TABLE {$prefix}whoiscrm_subscriptions
                ADD CONSTRAINT fk_sub_customer
                FOREIGN KEY (customer_id) REFERENCES {$prefix}whoiscrm_customers(id)
                ON DELETE CASCADE",

            "ALTER TABLE {$prefix}whoiscrm_subscriptions
                ADD CONSTRAINT fk_sub_package
                FOREIGN KEY (package_id) REFERENCES {$prefix}whoiscrm_packages(id)
                ON DELETE RESTRICT",

            "ALTER TABLE {$prefix}whoiscrm_subscriptions
                ADD CONSTRAINT fk_sub_pricing
                FOREIGN KEY (package_pricing_id) REFERENCES {$prefix}whoiscrm_package_pricing(id)
                ON DELETE RESTRICT",

            "ALTER TABLE {$prefix}whoiscrm_payments
                ADD CONSTRAINT fk_payment_customer
                FOREIGN KEY (customer_id) REFERENCES {$prefix}whoiscrm_customers(id)
                ON DELETE CASCADE",

            "ALTER TABLE {$prefix}whoiscrm_invoices
                ADD CONSTRAINT fk_invoice_payment
                FOREIGN KEY (payment_id) REFERENCES {$prefix}whoiscrm_payments(id)
                ON DELETE CASCADE",

            "ALTER TABLE {$prefix}whoiscrm_invoices
                ADD CONSTRAINT fk_invoice_customer
                FOREIGN KEY (customer_id) REFERENCES {$prefix}whoiscrm_customers(id)
                ON DELETE CASCADE",

            "ALTER TABLE {$prefix}whoiscrm_downloads
                ADD CONSTRAINT fk_download_customer
                FOREIGN KEY (customer_id) REFERENCES {$prefix}whoiscrm_customers(id)
                ON DELETE CASCADE",

            "ALTER TABLE {$prefix}whoiscrm_downloads
                ADD CONSTRAINT fk_download_file
                FOREIGN KEY (data_file_id) REFERENCES {$prefix}whoiscrm_data_files(id)
                ON DELETE CASCADE",

            "ALTER TABLE {$prefix}whoiscrm_api_keys
                ADD CONSTRAINT fk_apikey_customer
                FOREIGN KEY (customer_id) REFERENCES {$prefix}whoiscrm_customers(id)
                ON DELETE CASCADE",
        ];

        foreach ($constraints as $sql) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Static schema SQL, no user input.
            $wpdb->query($sql);
        }
    }

    /**
     * Drop all plugin tables (used during uninstall).
     */
    public static function drop_tables(): void
    {
        global $wpdb;

        $prefix = $wpdb->prefix;

        // Drop in reverse dependency order (children first).
        $tables = [
            "{$prefix}whoiscrm_activity_log",
            "{$prefix}whoiscrm_api_keys",
            "{$prefix}whoiscrm_downloads",
            "{$prefix}whoiscrm_invoices",
            "{$prefix}whoiscrm_payments",
            "{$prefix}whoiscrm_subscriptions",
            "{$prefix}whoiscrm_coupons",
            "{$prefix}whoiscrm_data_files",
            "{$prefix}whoiscrm_package_pricing",
            "{$prefix}whoiscrm_packages",
            "{$prefix}whoiscrm_customers",
        ];

        foreach ($tables as $table) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name from static list.
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
    }

    /**
     * Check if all plugin tables exist.
     */
    public static function tables_exist(): bool
    {
        global $wpdb;

        $prefix = $wpdb->prefix;
        $required = [
            'whoiscrm_packages',
            'whoiscrm_package_pricing',
            'whoiscrm_customers',
            'whoiscrm_subscriptions',
            'whoiscrm_payments',
            'whoiscrm_invoices',
            'whoiscrm_data_files',
            'whoiscrm_downloads',
            'whoiscrm_coupons',
            'whoiscrm_api_keys',
            'whoiscrm_activity_log',
        ];

        foreach ($required as $table) {
            $full_name = $prefix . $table;
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $result = $wpdb->get_var("SHOW TABLES LIKE '{$full_name}'");
            if ($result !== $full_name) {
                return false;
            }
        }

        return true;
    }
}
