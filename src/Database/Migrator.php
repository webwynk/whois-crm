<?php

declare(strict_types=1);

namespace WhoisCRM\Database;

/**
 * Schema version tracker and migrator.
 *
 * Compares the stored DB version against the expected version
 * and runs incremental migration routines when needed.
 */
class Migrator
{
    private const OPTION_KEY = 'whoiscrm_db_version';

    /**
     * Check if migration is needed and run it.
     */
    public static function maybe_migrate(): void
    {
        $current_version = get_option(self::OPTION_KEY, '0.0.0');

        if (version_compare($current_version, WHOISCRM_DB_VERSION, '>=')) {
            return; // Already up to date.
        }

        self::run_migrations($current_version);

        update_option(self::OPTION_KEY, WHOISCRM_DB_VERSION);
    }

    /**
     * Run migrations from the current version to the target version.
     */
    private static function run_migrations(string $from_version): void
    {
        // Migration registry: version => callback.
        // Add new migrations here as the plugin evolves.
        $migrations = [
            '1.0.0' => [self::class, 'migrate_to_1_0_0'],
            '1.0.3' => [self::class, 'migrate_to_1_0_3'],
            '1.1.0' => [self::class, 'migrate_to_1_1_0'],
            '1.1.1' => [self::class, 'migrate_to_1_1_1'],
        ];

        foreach ($migrations as $version => $callback) {
            if (version_compare($from_version, $version, '<')) {
                if (is_callable($callback)) {
                    call_user_func($callback);
                }
            }
        }
    }

    /**
     * Initial schema creation (1.0.0).
     */
    private static function migrate_to_1_0_0(): void
    {
        Schema::create_tables();
    }

    /**
     * Re-run schema creation to ensure all tables exist (1.0.3).
     */
    private static function migrate_to_1_0_3(): void
    {
        Schema::create_tables();
    }

    /**
     * Add notes column to data_files table and synchronize schema (1.1.0).
     */
    private static function migrate_to_1_1_0(): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'whoiscrm_data_files';

        // Check if notes column exists. If not, add it.
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- DESCRIBE statement is static.
        $columns = $wpdb->get_col("DESCRIBE {$table}", 0);
        if ($columns && !in_array('notes', $columns, true)) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is built from $wpdb->prefix, ALTER TABLE is static.
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN notes text NULL AFTER service_type");
        }

        Schema::create_tables();
    }

    /**
     * Widen country_code to varchar(10) and add DEFAULT '' to country fields (1.1.1).
     *
     * Fixes upload failure when 'Global / All Countries' is selected (empty string
     * violated the original NOT NULL varchar(2) constraint).
     */
    private static function migrate_to_1_1_1(): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'whoiscrm_data_files';

        // Widen country_code and add default
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $wpdb->query("ALTER TABLE {$table} MODIFY COLUMN country_code varchar(10) NOT NULL DEFAULT ''");

        // Add default to country_name
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $wpdb->query("ALTER TABLE {$table} MODIFY COLUMN country_name varchar(100) NOT NULL DEFAULT ''");

        Schema::create_tables();
    }

    /**
     * Get the current stored DB version.
     */
    public static function get_current_version(): string
    {
        return get_option(self::OPTION_KEY, '0.0.0');
    }

    /**
     * Set the DB version (used during activation).
     */
    public static function set_version(string $version): void
    {
        update_option(self::OPTION_KEY, $version);
    }
}
