<?php

declare(strict_types=1);

namespace WhoisCRM\Admin;

use WhoisCRM\Database\Models\Package;
use WhoisCRM\Database\Models\PackagePricing;
use WhoisCRM\Database\Models\ActivityLog;
use WhoisCRM\Stripe\StripeGateway;

/**
 * Package CRUD handler.
 *
 * Manages save / delete of packages and pricings,
 * and the Stripe Product/Price sync triggered from the admin form.
 *
 * Registered as:
 *   POST admin.php  — standard form save (with nonce)
 *   POST admin-ajax — AJAX Stripe sync button
 */
class PackageManager
{
    public function __construct()
    {
        // Standard form save (called directly by PackagesPage form POST)
        add_action('admin_post_whoiscrm_save_package',   [$this, 'handle_save']);
        add_action('admin_post_whoiscrm_delete_package', [$this, 'handle_delete']);

        // AJAX: Sync to Stripe
        add_action('wp_ajax_whoiscrm_sync_package_stripe', [$this, 'handle_stripe_sync']);
    }

    // ─── Save Package ─────────────────────────────────────────────────────

    /**
     * Handle the package create/update form submission.
     *
     * Validates → saves package row → saves pricing rows.
     * Redirects back with success/error notice.
     */
    public function handle_save(): void
    {
        check_admin_referer('whoiscrm_package_save');

        if (!current_user_can('whoiscrm_manage_packages')) {
            wp_die(__('Unauthorized.', 'whois-crm'));
        }

        $package_id = (int) ($_POST['package_id'] ?? 0);

        // ── Collect & sanitize package data ────────────────────────────
        $name        = sanitize_text_field(wp_unslash($_POST['name']        ?? ''));
        $slug        = sanitize_title(wp_unslash($_POST['slug']             ?? ''));
        $description = sanitize_textarea_field(wp_unslash($_POST['description'] ?? ''));
        $type        = sanitize_key($_POST['type']                          ?? 'global_service');
        $service_type = sanitize_key($_POST['service_type']                 ?? '');
        $is_active   = !empty($_POST['is_active']) ? 1 : 0;
        $sort_order  = (int) ($_POST['sort_order'] ?? 0);

        // Countries: array of ISO codes or empty
        $countries = [];
        if (!empty($_POST['countries']) && is_array($_POST['countries'])) {
            $countries = array_map('sanitize_text_field', $_POST['countries']);
        }

        // TLDs: comma-separated string → array
        $tlds_raw = sanitize_text_field(wp_unslash($_POST['tlds'] ?? ''));
        $tlds     = array_filter(array_map('trim', explode(',', $tlds_raw)));

        // Features: repeatable array
        $features = [];
        if (!empty($_POST['features']) && is_array($_POST['features'])) {
            $features = array_filter(array_map('sanitize_text_field', $_POST['features']));
        }

        // ── Validate ──────────────────────────────────────────────────
        if (empty($name)) {
            $this->redirect_with_error($package_id, __('Package name is required.', 'whois-crm'));
        }

        if (empty($slug)) {
            $slug = sanitize_title($name);
        }

        // Validate slug uniqueness
        $model = new Package();
        $existing_slug = $model->find_by_slug($slug);
        if ($existing_slug && (int) $existing_slug->id !== $package_id) {
            $this->redirect_with_error($package_id, __('A package with this slug already exists.', 'whois-crm'));
        }

        // ── Package row ───────────────────────────────────────────────
        $package_data = [
            'name'         => $name,
            'slug'         => $slug,
            'description'  => $description,
            'type'         => $type,
            'service_type' => $service_type,
            'countries'    => wp_json_encode(array_values($countries)),
            'tlds'         => wp_json_encode(array_values($tlds)),
            'features'     => wp_json_encode(array_values(array_values($features))),
            'is_active'    => $is_active,
            'sort_order'   => $sort_order,
        ];

        if ($package_id > 0) {
            $model->update($package_id, $package_data);

            // If Stripe product exists, update name/description
            $pkg = $model->find($package_id);
            if ($pkg && !empty($pkg->stripe_product_id)) {
                (new StripeGateway())->update_product($pkg->stripe_product_id, (object) $package_data);
            }
        } else {
            $package_id = (int) $model->insert($package_data);
        }

        // ── Pricing rows ──────────────────────────────────────────────
        $this->save_pricing($package_id, $type);

        // ── Log ───────────────────────────────────────────────────────
        (new ActivityLog())->log(
            ActivityLog::ACTION_ADMIN_ACTION,
            "Package saved: {$name} (ID #{$package_id})",
            [],
            ActivityLog::SEVERITY_INFO,
            get_current_user_id()
        );

        wp_safe_redirect(add_query_arg(
            ['page' => 'whoiscrm-packages', 'edit' => $package_id, 'saved' => 1],
            admin_url('admin.php')
        ));
        exit;
    }

    /**
     * Save / update pricing rows for a package from POST data.
     */
    private function save_pricing(int $package_id, string $type): void
    {
        $pricing_model = new PackagePricing();

        // Monthly price — always present
        $monthly_price = (float) ($_POST['monthly_price'] ?? 0);
        if ($monthly_price > 0) {
            $this->upsert_pricing($pricing_model, $package_id, 'monthly', $monthly_price);
        }

        // Annual price — only for global service packages
        if ($type === 'global_service') {
            $annual_price = (float) ($_POST['annual_price'] ?? 0);
            if ($annual_price > 0) {
                $this->upsert_pricing($pricing_model, $package_id, 'annually', $annual_price);
            }
        }
    }

    /**
     * Update existing pricing row or insert if it doesn't exist.
     */
    private function upsert_pricing(
        PackagePricing $model,
        int $package_id,
        string $billing_cycle,
        float $price
    ): void {
        $existing = $model->find_by_package_and_cycle($package_id, $billing_cycle);

        if ($existing) {
            // If price changed, deactivate old Stripe price and clear ID
            if ((float) $existing->price !== $price && !empty($existing->stripe_price_id)) {
                (new StripeGateway())->deactivate_price($existing->stripe_price_id);
                $model->update((int) $existing->id, [
                    'price'           => $price,
                    'stripe_price_id' => '', // Will be re-synced
                ]);
            } else {
                $model->update((int) $existing->id, ['price' => $price]);
            }
        } else {
            $model->insert([
                'package_id'    => $package_id,
                'billing_cycle' => $billing_cycle,
                'price'         => $price,
                'currency'      => 'USD',
                'is_active'     => 1,
            ]);
        }
    }

    // ─── Delete Package ───────────────────────────────────────────────────

    /**
     * Handle package deletion.
     */
    public function handle_delete(): void
    {
        check_admin_referer('whoiscrm_package_delete');

        if (!current_user_can('whoiscrm_manage_packages')) {
            wp_die(__('Unauthorized.', 'whois-crm'));
        }

        $package_id = (int) ($_POST['package_id'] ?? 0);

        if ($package_id < 1) {
            wp_safe_redirect(admin_url('admin.php?page=whoiscrm-packages'));
            exit;
        }

        (new Package())->delete_by_id($package_id);

        (new ActivityLog())->log(
            ActivityLog::ACTION_ADMIN_ACTION,
            "Package deleted: ID #{$package_id}",
            [],
            ActivityLog::SEVERITY_WARNING,
            get_current_user_id()
        );

        wp_safe_redirect(add_query_arg(
            ['page' => 'whoiscrm-packages', 'deleted' => 1],
            admin_url('admin.php')
        ));
        exit;
    }

    // ─── Stripe Sync (AJAX) ───────────────────────────────────────────────

    /**
     * AJAX handler for the "Sync to Stripe" button.
     *
     * Creates/updates the Stripe Product and creates missing Prices.
     * Returns JSON with stripe_product_id and price IDs.
     */
    public function handle_stripe_sync(): void
    {
        check_ajax_referer('whoiscrm_admin_nonce', 'nonce');

        if (!current_user_can('whoiscrm_manage_packages')) {
            wp_send_json_error(['message' => __('Unauthorized.', 'whois-crm')]);
        }

        $package_id = (int) ($_POST['package_id'] ?? 0);

        if ($package_id < 1) {
            wp_send_json_error(['message' => __('Invalid package ID.', 'whois-crm')]);
        }

        $package = (new Package())->find($package_id);

        if (!$package) {
            wp_send_json_error(['message' => __('Package not found.', 'whois-crm')]);
        }

        $stripe    = new StripeGateway();
        $pkg_model = new Package();
        $pri_model = new PackagePricing();

        if (!$stripe->is_configured()) {
            wp_send_json_error(['message' => __('Stripe is not configured. Please add your API keys in Settings → Payment.', 'whois-crm')]);
        }

        // ── Product sync ─────────────────────────────────────────────
        if (empty($package->stripe_product_id)) {
            $product_id = $stripe->create_product($package);

            if (is_wp_error($product_id)) {
                wp_send_json_error(['message' => $product_id->get_error_message()]);
            }

            $pkg_model->update($package_id, ['stripe_product_id' => $product_id]);
            $package->stripe_product_id = $product_id;
        } else {
            $stripe->update_product($package->stripe_product_id, $package);
        }

        // ── Price sync ────────────────────────────────────────────────
        $pricings       = $pri_model->get_for_package($package_id);
        $synced_prices  = [];

        foreach ($pricings as $pricing) {
            if (!empty($pricing->stripe_price_id)) {
                $synced_prices[$pricing->billing_cycle] = $pricing->stripe_price_id;
                continue; // Already synced
            }

            $price_id = $stripe->create_price(
                $package->stripe_product_id,
                (float) $pricing->price,
                $pricing->billing_cycle,
                (int) $pricing->id
            );

            if (is_wp_error($price_id)) {
                wp_send_json_error(['message' => $price_id->get_error_message()]);
            }

            $pri_model->update((int) $pricing->id, ['stripe_price_id' => $price_id]);
            $synced_prices[$pricing->billing_cycle] = $price_id;
        }

        (new ActivityLog())->log(
            ActivityLog::ACTION_ADMIN_ACTION,
            "Package #{$package_id} synced to Stripe",
            ['stripe_product_id' => $package->stripe_product_id],
            ActivityLog::SEVERITY_INFO,
            get_current_user_id()
        );

        wp_send_json_success([
            'message'           => __('Package successfully synced to Stripe!', 'whois-crm'),
            'stripe_product_id' => $package->stripe_product_id,
            'prices'            => $synced_prices,
        ]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    private function redirect_with_error(int $package_id, string $message): void
    {
        $args = ['page' => 'whoiscrm-packages', 'error' => urlencode($message)];
        if ($package_id > 0) {
            $args['edit'] = $package_id;
        }

        wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
        exit;
    }
}
