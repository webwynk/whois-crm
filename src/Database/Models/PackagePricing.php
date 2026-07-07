<?php

declare(strict_types=1);

namespace WhoisCRM\Database\Models;

/**
 * PackagePricing model.
 *
 * Represents a billing cycle + price row for a package.
 * Each package can have multiple pricing rows (monthly, annually).
 */
class PackagePricing extends BaseModel
{
    protected function table_name(): string
    {
        return 'package_pricing';
    }

    /**
     * Get all active pricing options for a specific package.
     *
     * @param int $package_id
     * @return array<object>
     */
    public function get_for_package(int $package_id): array
    {
        return $this->get_where(
            ['package_id' => $package_id, 'is_active' => 1],
            'billing_cycle',
            'ASC'
        );
    }

    /**
     * Get a specific pricing record by package + billing cycle.
     */
    public function get_by_package_and_cycle(int $package_id, string $billing_cycle): ?object
    {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table}
                 WHERE package_id = %d AND billing_cycle = %s AND is_active = 1
                 LIMIT 1",
                $package_id,
                $billing_cycle
            )
        ) ?: null;
    }

    /**
     * Find a pricing record by Stripe price ID.
     */
    public function find_by_stripe_price(string $stripe_price_id): ?object
    {
        return $this->find_by('stripe_price_id', $stripe_price_id);
    }

    /**
     * Update the Stripe price ID for a pricing record.
     */
    public function set_stripe_price_id(int $id, string $stripe_price_id): bool
    {
        return $this->update($id, ['stripe_price_id' => $stripe_price_id]);
    }

    /**
     * Deactivate all pricing rows for a package (before delete or re-sync).
     */
    public function deactivate_for_package(int $package_id): bool
    {
        global $wpdb;

        $result = $wpdb->update(
            $this->table,
            ['is_active' => 0, 'updated_at' => current_time('mysql', true)],
            ['package_id' => $package_id],
            ['%d', '%s'],
            ['%d']
        );

        return $result !== false;
    }
}
