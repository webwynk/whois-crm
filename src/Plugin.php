<?php

declare(strict_types=1);

namespace WhoisCRM;

use WhoisCRM\Database\Migrator;
use WhoisCRM\Security\FileProtection;

/**
 * Main plugin bootstrap class.
 *
 * Singleton that initializes all plugin modules.
 * Loaded on the 'plugins_loaded' hook.
 */
class Plugin
{
    private static ?self $instance = null;

    /**
     * Get the singleton instance.
     */
    public static function get_instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor — use get_instance().
     */
    private function __construct()
    {
        $this->check_requirements();
        $this->load_textdomain();
        $this->maybe_migrate();
        $this->init_core();
        $this->init_admin();
        $this->init_frontend();
        $this->init_api();
    }

    /**
     * Verify PHP and WordPress requirements.
     */
    private function check_requirements(): void
    {
        if (version_compare(PHP_VERSION, '8.1', '<')) {
            add_action('admin_notices', function (): void {
                echo '<div class="notice notice-error"><p>';
                printf(
                    /* translators: %s: Required PHP version */
                    esc_html__('WHOIS CRM requires PHP %s or higher. Please upgrade your PHP version.', 'whois-crm'),
                    '8.1'
                );
                echo '</p></div>';
            });
        }
    }

    /**
     * Load plugin text domain for translations.
     */
    private function load_textdomain(): void
    {
        load_plugin_textdomain(
            'whois-crm',
            false,
            dirname(WHOISCRM_PLUGIN_BASENAME) . '/languages'
        );
    }

    /**
     * Run database migrations if needed.
     */
    private function maybe_migrate(): void
    {
        Migrator::maybe_migrate();
    }

    /**
     * Initialize core services that run on every request.
     */
    private function init_core(): void
    {
        // Security (always active).
        new Security\FileProtection();

        // Phase 4+: Subscription cron jobs.
        new Subscription\CronJobs();

        // Phase 11+: Invoice Generator.
        new Invoice\InvoiceGenerator();
    }

    /**
     * Initialize admin-only modules.
     */
    private function init_admin(): void
    {
        if (!is_admin()) {
            return;
        }

        // Phase 4+: Admin dashboard.
        new Admin\AdminMenu();
        new Admin\AdminAssets();

        // Phase 5+: Package Manager CRUD
        new Admin\PackageManager();

        // Phase 6+: File Upload & Management
        new Upload\FileUploader();
        new Upload\FileManager();
    }

    /**
     * Initialize frontend modules (customer portal, auth forms).
     */
    private function init_frontend(): void
    {
        // Phase 3+: Authentication.
        new Auth\AuthController();
        new Auth\AuthShortcodes();
        new Auth\AuthRedirects();

        // Phase 5+: Stripe Checkout handler
        new Stripe\CheckoutHandler();

        // Phase 7+: Customer portal.
        new Portal\PortalShortcodes();
        new Portal\PortalAssets();
        new Portal\PortalController();
        new Portal\DownloadHandler();
    }

    /**
     * Initialize REST API endpoints.
     */
    private function init_api(): void
    {
        // Phase 5+: Stripe Webhook route
        new Api\ApiRouter();
    }

    /**
     * Prevent cloning.
     */
    private function __clone() {}

    /**
     * Prevent unserialization.
     */
    public function __wakeup()
    {
        throw new \Exception('Cannot unserialize singleton.');
    }
}
