<?php

declare(strict_types=1);

namespace WhoisCRM\Admin;

/**
 * Registers the WHOIS CRM top-level admin menu and all submenus.
 *
 * Each submenu maps to a static Page class with a render() method.
 * Capabilities follow the custom caps defined in RoleManager.
 */
class AdminMenu
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'register_menus']);
    }

    public function register_menus(): void
    {
        // ── Top-level menu ────────────────────────────────────────────────
        add_menu_page(
            __('WHOIS CRM', 'whois-crm'),
            __('WHOIS CRM', 'whois-crm'),
            'manage_options',
            'whoiscrm',
            [Pages\DashboardPage::class, 'render'],
            'dashicons-database',
            30
        );

        // ── Submenus ──────────────────────────────────────────────────────
        // Format: [parent_slug, slug, title, capability, page_class]
        $submenus = [
            ['whoiscrm', 'whoiscrm',              __('Dashboard',     'whois-crm'), 'manage_options',               Pages\DashboardPage::class],
            ['whoiscrm', 'whoiscrm-upload',        __('Upload Data',   'whois-crm'), 'whoiscrm_upload_data',         Pages\UploadPage::class],
            ['whoiscrm', 'whoiscrm-data-files',    __('Data Files',    'whois-crm'), 'whoiscrm_upload_data',         Pages\DataFilesPage::class],
            ['whoiscrm', 'whoiscrm-packages',      __('Packages',      'whois-crm'), 'whoiscrm_manage_packages',     Pages\PackagesPage::class],
            ['whoiscrm', 'whoiscrm-customers',     __('Customers',     'whois-crm'), 'whoiscrm_manage_customers',    Pages\CustomersPage::class],
            ['whoiscrm', 'whoiscrm-subscriptions', __('Subscriptions', 'whois-crm'), 'whoiscrm_manage_subscriptions', Pages\SubscriptionsPage::class],
            ['whoiscrm', 'whoiscrm-payments',      __('Payments',      'whois-crm'), 'whoiscrm_view_payments',       Pages\PaymentsPage::class],
            ['whoiscrm', 'whoiscrm-invoices',      __('Invoices',      'whois-crm'), 'whoiscrm_view_payments',       Pages\InvoicesPage::class],
            ['whoiscrm', 'whoiscrm-coupons',       __('Coupons',       'whois-crm'), 'whoiscrm_manage_coupons',      Pages\CouponsPage::class],
            ['whoiscrm', 'whoiscrm-reports',       __('Reports',       'whois-crm'), 'whoiscrm_view_reports',        Pages\ReportsPage::class],
            ['whoiscrm', 'whoiscrm-settings',      __('Settings',      'whois-crm'), 'whoiscrm_manage_settings',     Pages\SettingsPage::class],
            ['whoiscrm', 'whoiscrm-activity-log',  __('Activity Log',  'whois-crm'), 'whoiscrm_view_activity_log',   Pages\ActivityLogPage::class],
        ];

        foreach ($submenus as [$parent, $slug, $title, $cap, $class]) {
            add_submenu_page(
                $parent,
                $title . ' — WHOIS CRM',
                $title,
                $cap,
                $slug,
                [$class, 'render']
            );
        }
    }
}
