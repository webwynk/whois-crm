<?php

declare(strict_types=1);

namespace WhoisCRM\Database\Models;

/**
 * Package model.
 *
 * Represents a subscription plan (global service or country-specific).
 * Handles JSON field encoding/decoding transparently.
 */
class Package extends BaseModel
{
    protected function table_name(): string
    {
        return 'packages';
    }

    // ─── JSON Field Helpers ───────────────────────────────────────────────

    /**
     * Get all active packages, ordered by sort_order.
     *
     * @return array<object>
     */
    public function get_active(string $type = ''): array
    {
        global $wpdb;

        $where = 'is_active = 1';
        $values = [];

        if ($type !== '') {
            $where .= ' AND type = %s';
            $values[] = $type;
        }

        $sql = "SELECT * FROM {$this->table} WHERE {$where} ORDER BY sort_order ASC";

        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, ...$values);
        }

        $rows = $wpdb->get_results($sql);

        return array_map([$this, 'decode_json_fields'], $rows ?: []);
    }

    /**
     * Find a package by slug.
     */
    public function find_by_slug(string $slug): ?object
    {
        $row = $this->find_by('slug', $slug);

        return $row ? $this->decode_json_fields($row) : null;
    }

    /**
     * Find a package by Stripe product ID.
     */
    public function find_by_stripe_product(string $product_id): ?object
    {
        $row = $this->find_by('stripe_product_id', $product_id);

        return $row ? $this->decode_json_fields($row) : null;
    }

    /**
     * {@inheritdoc} — Overridden to decode JSON on find.
     */
    public function find(int $id): ?object
    {
        $row = parent::find($id);

        return $row ? $this->decode_json_fields($row) : null;
    }

    /**
     * Get all packages with their pricing options.
     *
     * @return array<object>
     */
    public function get_all_with_pricing(): array
    {
        global $wpdb;

        $pricing_table = $wpdb->prefix . 'whoiscrm_package_pricing';

        $sql = "SELECT p.*,
                    pp.id AS pricing_id,
                    pp.billing_cycle,
                    pp.price,
                    pp.currency,
                    pp.stripe_price_id
                FROM {$this->table} p
                LEFT JOIN {$pricing_table} pp ON pp.package_id = p.id AND pp.is_active = 1
                WHERE p.is_active = 1
                ORDER BY p.sort_order ASC, pp.billing_cycle ASC";

        $rows = $wpdb->get_results($sql);

        // Group pricing rows into their parent package.
        $packages = [];
        foreach ($rows ?: [] as $row) {
            $pkg_id = (int) $row->id;

            if (!isset($packages[$pkg_id])) {
                $pkg = clone $row;
                unset($pkg->pricing_id, $pkg->billing_cycle, $pkg->price, $pkg->currency, $pkg->stripe_price_id);
                $pkg = $this->decode_json_fields($pkg);
                $pkg->pricing = [];
                $packages[$pkg_id] = $pkg;
            }

            if ($row->pricing_id !== null) {
                $packages[$pkg_id]->pricing[] = (object) [
                    'id'              => (int) $row->pricing_id,
                    'billing_cycle'   => $row->billing_cycle,
                    'price'           => (float) $row->price,
                    'currency'        => $row->currency,
                    'stripe_price_id' => $row->stripe_price_id,
                ];
            }
        }

        return array_values($packages);
    }

    /**
     * Decode JSON fields (countries, tlds, features) from JSON strings to arrays.
     */
    public function decode_json_fields(object $row): object
    {
        foreach (['countries', 'tlds', 'features'] as $field) {
            if (isset($row->$field) && is_string($row->$field)) {
                $decoded = json_decode($row->$field, true);
                $row->$field = (json_last_error() === JSON_ERROR_NONE) ? $decoded : null;
            }
        }

        return $row;
    }

    /**
     * Check if a package grants access to a specific country code.
     *
     * Enterprise (null countries) = all countries.
     */
    public function grants_country_access(object $package, string $country_code): bool
    {
        if ($package->countries === null) {
            return true; // Global plan
        }

        $countries = is_array($package->countries) ? $package->countries : [];

        return in_array(strtoupper($country_code), array_map('strtoupper', $countries), true);
    }
}
