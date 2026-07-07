<?php

declare(strict_types=1);

namespace WhoisCRM\Admin\Pages;

use WhoisCRM\Database\Models\Package;
use WhoisCRM\Database\Models\PackagePricing;

/**
 * Full Packages admin page.
 *
 * List + Add/Edit form with Stripe sync button.
 * Save/Delete are handled by PackageManager via admin_post actions.
 */
class PackagesPage extends BasePage
{
    protected static string $required_cap = 'whoiscrm_manage_packages';

    protected function display(): void
    {
        // ── Notices from redirect params ─────────────────────────────
        $this->show_notices('whoiscrm_packages');

        if (!empty($_GET['saved'])) {
            echo '<div class="whoiscrm-alert whoiscrm-alert--success">' . esc_html__('Package saved successfully.', 'whois-crm') . '</div>';
        }
        if (!empty($_GET['deleted'])) {
            echo '<div class="whoiscrm-alert whoiscrm-alert--success">' . esc_html__('Package deleted.', 'whois-crm') . '</div>';
        }
        if (!empty($_GET['error'])) {
            echo '<div class="whoiscrm-alert whoiscrm-alert--danger">' . esc_html(urldecode($_GET['error'])) . '</div>';
        }

        // ── Route to form or list ──────────────────────────────────
        $edit_id  = (int) ($_GET['edit']  ?? 0);
        $new_view = isset($_GET['view']) && $_GET['view'] === 'add';

        if ($edit_id > 0 || $new_view) {
            $this->display_form($edit_id);
        } else {
            $this->display_list();
        }
    }

    // ─── List View ────────────────────────────────────────────────────────

    private function display_list(): void
    {
        $rows = (new Package())->get_all_with_pricing();

        $this->page_header(
            __('Packages', 'whois-crm'),
            '',
            [['label' => __('+ New Package', 'whois-crm'), 'url' => add_query_arg(['page' => 'whoiscrm-packages', 'view' => 'add'], admin_url('admin.php'))]]
        );

        $this->render_template('packages/list', [
            'rows'  => $rows,
            'nonce' => wp_create_nonce('whoiscrm_admin_nonce'),
        ]);

        $this->page_footer();
    }

    // ─── Edit / New Form ─────────────────────────────────────────────────

    private function display_form(int $package_id = 0): void
    {
        $package  = null;
        $pricings = [];

        if ($package_id > 0) {
            $package  = (new Package())->find($package_id);
            $pricings = (new PackagePricing())->get_for_package($package_id);

            if (!$package) {
                wp_die(__('Package not found.', 'whois-crm'));
            }

            // Decode JSON fields for the form
            $package->countries_arr = json_decode($package->countries ?? '[]', true) ?: [];
            $package->tlds_str      = implode(', ', json_decode($package->tlds ?? '[]', true) ?: []);
            $package->features_arr  = json_decode($package->features ?? '[]', true) ?: [''];
        }

        // Index pricings by billing cycle for easy template access
        $pricing_by_cycle = [];
        foreach ($pricings as $p) {
            $pricing_by_cycle[$p->billing_cycle] = $p;
        }

        $this->page_header(
            $package_id > 0 ? __('Edit Package', 'whois-crm') : __('New Package', 'whois-crm'),
            __('Packages', 'whois-crm')
        );

        $this->render_template('packages/form', [
            'package'          => $package,
            'package_id'       => $package_id,
            'pricing_by_cycle' => $pricing_by_cycle,
            'service_types'    => $this->get_service_types(),
            'country_options'  => $this->get_country_options(),
            'nonce'            => wp_create_nonce('whoiscrm_package_save'),
            'admin_nonce'      => wp_create_nonce('whoiscrm_admin_nonce'),
            'back_url'         => admin_url('admin.php?page=whoiscrm-packages'),
            'stripe_configured' => (new \WhoisCRM\Stripe\StripeGateway())->is_configured(),
        ]);

        $this->page_footer();
    }

    // ─── Helpers ─────────────────────────────────────────────────────────

    private function get_service_types(): array
    {
        return [
            'whois_history'     => __('WHOIS History Database', 'whois-crm'),
            'lead_generation'   => __('Domain Lead Generation', 'whois-crm'),
            'expiring_domains'  => __('Expiring Domains', 'whois-crm'),
            'bulk_lookup'       => __('Bulk Domain Lookup', 'whois-crm'),
            'country_data'      => __('Country Data', 'whois-crm'),
            'enterprise'        => __('Enterprise (All Access)', 'whois-crm'),
        ];
    }

    private function get_country_options(): array
    {
        return \WhoisCRM\Helpers\CountryList::all();
    }
}
