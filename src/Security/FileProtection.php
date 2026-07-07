<?php

declare(strict_types=1);

namespace WhoisCRM\Security;

/**
 * Protects the data storage directory from direct HTTP access.
 *
 * Creates the /wp-content/whois-data/ directory structure and
 * writes .htaccess / index.php files to block direct access.
 * All file downloads must go through the PHP download handler.
 */
class FileProtection
{
    /**
     * Register hooks (no runtime hooks needed — protection is set during activation).
     */
    public function __construct()
    {
        // Verify protection on admin_init (in case files were deleted).
        add_action('admin_init', [self::class, 'verify_protection']);
    }

    /**
     * Create the data directory with protection files.
     * Called during plugin activation.
     */
    public static function create_data_directory(): void
    {
        $directories = [
            WHOISCRM_DATA_DIR,
            WHOISCRM_DATA_DIR . 'whois_history/',
            WHOISCRM_DATA_DIR . 'lead_generation/',
            WHOISCRM_DATA_DIR . 'expiring_domains/',
            WHOISCRM_DATA_DIR . 'bulk_lookup/',
            WHOISCRM_DATA_DIR . 'country_data/',
            WHOISCRM_INVOICE_DIR,
        ];

        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
            }
            self::write_index_file($dir);
        }

        // Write .htaccess to the root data directory.
        self::write_htaccess(WHOISCRM_DATA_DIR);
    }

    /**
     * Write .htaccess to deny all direct HTTP access.
     */
    private static function write_htaccess(string $directory): void
    {
        $htaccess_path = rtrim($directory, '/') . '/.htaccess';

        $htaccess_content = <<<HTACCESS
# WHOIS CRM — Block all direct file access
# Files are served through the authenticated download handler.

# Apache 2.4+
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>

# Apache 2.2 (fallback)
<IfModule !mod_authz_core.c>
    Order deny,allow
    Deny from all
</IfModule>

# Deny all file types explicitly
<FilesMatch ".*">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order deny,allow
        Deny from all
    </IfModule>
</FilesMatch>
HTACCESS;

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
        file_put_contents($htaccess_path, $htaccess_content);
    }

    /**
     * Write an index.php file to prevent directory listing.
     */
    private static function write_index_file(string $directory): void
    {
        $index_path = rtrim($directory, '/') . '/index.php';

        if (!file_exists($index_path)) {
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
            file_put_contents($index_path, "<?php\n// Silence is golden.\n");
        }
    }

    /**
     * Verify that protection files are in place.
     * Runs on admin_init to catch accidental deletions.
     */
    public static function verify_protection(): void
    {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        $htaccess = WHOISCRM_DATA_DIR . '.htaccess';

        if (file_exists(WHOISCRM_DATA_DIR) && !file_exists($htaccess)) {
            self::write_htaccess(WHOISCRM_DATA_DIR);

            // Show admin notice.
            add_action('admin_notices', function (): void {
                echo '<div class="notice notice-warning is-dismissible"><p>';
                echo esc_html__(
                    'WHOIS CRM: The .htaccess protection file was missing and has been regenerated.',
                    'whois-crm'
                );
                echo '</p></div>';
            });
        }
    }

    /**
     * Get the Nginx equivalent configuration for display in settings.
     * (For users on Nginx who need to manually configure server blocks.)
     */
    public static function get_nginx_rules(): string
    {
        $data_path = str_replace(ABSPATH, '/', WHOISCRM_DATA_DIR);

        return <<<NGINX
# WHOIS CRM — Nginx configuration
# Add this to your server block to protect the data directory.

location ~* ^{$data_path} {
    deny all;
    return 403;
}
NGINX;
    }

    /**
     * Remove all protection files and data directory.
     * Called during plugin uninstall.
     */
    public static function remove_data_directory(): void
    {
        if (file_exists(WHOISCRM_DATA_DIR)) {
            self::recursive_delete(WHOISCRM_DATA_DIR);
        }
    }

    /**
     * Recursively delete a directory and its contents.
     */
    private static function recursive_delete(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
                unlink($item->getPathname());
            }
        }

        rmdir($directory);
    }
}
