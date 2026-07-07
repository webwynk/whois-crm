<?php

declare(strict_types=1);

namespace WhoisCRM\Security;

/**
 * Custom WordPress roles and capabilities for the WHOIS CRM plugin.
 *
 * Creates the `whoiscrm_customer` role and adds admin capabilities
 * to the `administrator` role.
 */
class RoleManager
{
    /**
     * All custom capabilities used by the plugin.
     */
    public const ADMIN_CAPABILITIES = [
        'whoiscrm_manage_packages',
        'whoiscrm_upload_data',
        'whoiscrm_manage_customers',
        'whoiscrm_manage_subscriptions',
        'whoiscrm_view_payments',
        'whoiscrm_manage_coupons',
        'whoiscrm_view_reports',
        'whoiscrm_manage_settings',
        'whoiscrm_view_activity_log',
    ];

    public const CUSTOMER_CAPABILITIES = [
        'read'                  => true,
        'whoiscrm_view_portal'  => true,
        'whoiscrm_download'     => true,
    ];

    /**
     * Register hooks for role management.
     */
    public function __construct()
    {
        // No runtime hooks needed — roles are created during activation.
    }

    /**
     * Create custom roles and capabilities.
     * Called during plugin activation.
     */
    public static function create_roles(): void
    {
        // ── Customer Role ────────────────────────────────────────────────
        // Remove first in case it already exists (to update capabilities).
        remove_role('whoiscrm_customer');

        add_role(
            'whoiscrm_customer',
            __('WHOIS CRM Customer', 'whois-crm'),
            self::CUSTOMER_CAPABILITIES
        );

        // ── Admin Capabilities ───────────────────────────────────────────
        $admin_role = get_role('administrator');
        if ($admin_role) {
            foreach (self::ADMIN_CAPABILITIES as $cap) {
                $admin_role->add_cap($cap);
            }
            // Admins also get customer capabilities.
            foreach (self::CUSTOMER_CAPABILITIES as $cap => $granted) {
                $admin_role->add_cap($cap);
            }
        }
    }

    /**
     * Remove custom roles and capabilities.
     * Called during plugin uninstall.
     */
    public static function remove_roles(): void
    {
        // Remove customer role.
        remove_role('whoiscrm_customer');

        // Remove admin capabilities.
        $admin_role = get_role('administrator');
        if ($admin_role) {
            foreach (self::ADMIN_CAPABILITIES as $cap) {
                $admin_role->remove_cap($cap);
            }
            foreach (array_keys(self::CUSTOMER_CAPABILITIES) as $cap) {
                $admin_role->remove_cap($cap);
            }
        }
    }

    /**
     * Check if the current user is a WHOIS CRM customer.
     */
    public static function is_customer(?int $user_id = null): bool
    {
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }

        if ($user_id === 0) {
            return false;
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        return in_array('whoiscrm_customer', $user->roles, true);
    }

    /**
     * Check if the current user is an administrator with plugin access.
     */
    public static function is_admin(?int $user_id = null): bool
    {
        if ($user_id === null) {
            return current_user_can('manage_options');
        }

        return user_can($user_id, 'manage_options');
    }
}
