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
