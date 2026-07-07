<?php

declare(strict_types=1);

namespace WhoisCRM\Database\Models;

/**
 * Subscription model.
 *
 * Tracks customer subscriptions to packages.
 * Status values: active, past_due, cancelled, expired, trialing, paused.
 */
class Subscription extends BaseModel
{
    protected function table_name(): string
    {
        return 'subscriptions';
    }

    // ─── Status Constants ──────────────────────────────────────────────────

    public const STATUS_ACTIVE    = 'active';
    public const STATUS_PAST_DUE  = 'past_due';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED   = 'expired';
    public const STATUS_TRIALING  = 'trialing';
    public const STATUS_PAUSED    = 'paused';

    /**
     * Get all active subscriptions for a customer.
     *
     * @return array<object>
     */
    public function get_active_for_customer(int $customer_id): array
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT s.*,
                        p.name  AS package_name,
                        p.slug  AS package_slug,
                        p.type  AS package_type,
                        p.service_type,
                        p.countries,
                        p.tlds,
                        p.features,
                        pp.billing_cycle,
                        pp.price,
                        pp.currency
                 FROM {$this->table} s
                 INNER JOIN {$wpdb->prefix}whoiscrm_packages p       ON p.id = s.package_id
                 INNER JOIN {$wpdb->prefix}whoiscrm_package_pricing pp ON pp.id = s.package_pricing_id
                 WHERE s.customer_id = %d
                   AND s.status IN ('active', 'trialing', 'past_due')
                 ORDER BY s.created_at DESC",
                $customer_id
            )
        ) ?: [];
    }

    /**
     * Get all subscriptions for a customer (any status).
     *
     * @return array<object>
     */
    public function get_all_for_customer(int $customer_id): array
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT s.*,
                        p.name AS package_name,
                        p.slug AS package_slug,
                        p.type AS package_type,
                        p.service_type,
                        pp.billing_cycle,
                        pp.price,
                        pp.currency
                 FROM {$this->table} s
                 INNER JOIN {$wpdb->prefix}whoiscrm_packages p         ON p.id = s.package_id
                 INNER JOIN {$wpdb->prefix}whoiscrm_package_pricing pp  ON pp.id = s.package_pricing_id
                 WHERE s.customer_id = %d
                 ORDER BY s.created_at DESC",
                $customer_id
            )
        ) ?: [];
    }

    /**
     * Find a subscription by Stripe subscription ID.
     */
    public function find_by_stripe_subscription(string $stripe_sub_id): ?object
    {
        return $this->find_by('stripe_subscription_id', $stripe_sub_id);
    }

    /**
     * Check if a customer has an active subscription to a specific package.
     */
    public function has_active_subscription(int $customer_id, int $package_id): bool
    {
        global $wpdb;

        $result = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$this->table}
                 WHERE customer_id = %d
                   AND package_id = %d
                   AND status IN ('active', 'trialing')
                 LIMIT 1",
                $customer_id,
                $package_id
            )
        );

        return $result !== null;
    }

    /**
     * Check if a customer can access data for a given service_type + country.
     *
     * This is the core access control check called before every download.
     */
    public function can_access_file(int $customer_id, string $service_type, string $country_code): bool
    {
        global $wpdb;

        // Enterprise (null countries) grants access to everything.
        $enterprise_check = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT s.id
                 FROM {$this->table} s
                 INNER JOIN {$wpdb->prefix}whoiscrm_packages p ON p.id = s.package_id
                 WHERE s.customer_id = %d
                   AND s.status IN ('active', 'trialing')
                   AND p.service_type = 'enterprise'
                 LIMIT 1",
                $customer_id
            )
        );

        if ($enterprise_check !== null) {
            return true;
        }

        // Match by service_type AND country (countries JSON contains the code).
        $result = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT s.id
                 FROM {$this->table} s
                 INNER JOIN {$wpdb->prefix}whoiscrm_packages p ON p.id = s.package_id
                 WHERE s.customer_id = %d
                   AND s.status IN ('active', 'trialing')
                   AND (
                       p.service_type = %s
                       AND (
                           p.countries IS NULL
                           OR JSON_CONTAINS(p.countries, JSON_QUOTE(%s))
                       )
                   )
                 LIMIT 1",
                $customer_id,
                $service_type,
                strtoupper($country_code)
            )
        );

        return $result !== null;
    }

    /**
     * Get subscriptions expiring within the next N days.
     *
     * @param int $days Days from now.
     * @return array<object>
     */
    public function get_expiring_soon(int $days = 7): array
    {
        global $wpdb;

        $cutoff = gmdate('Y-m-d H:i:s', strtotime("+{$days} days"));

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT s.*,
                        c.id AS customer_id,
                        u.user_email AS customer_email,
                        COALESCE(fn.meta_value,'') AS first_name,
                        p.name AS package_name
                 FROM {$this->table} s
                 INNER JOIN {$wpdb->prefix}whoiscrm_customers c ON c.id = s.customer_id
                 INNER JOIN {$wpdb->users} u ON u.ID = c.user_id
                 LEFT JOIN  {$wpdb->usermeta} fn ON fn.user_id = u.ID AND fn.meta_key = 'first_name'
                 INNER JOIN {$wpdb->prefix}whoiscrm_packages p ON p.id = s.package_id
                 WHERE s.status = 'active'
                   AND s.expires_at IS NOT NULL
                   AND s.expires_at BETWEEN NOW() AND %s",
                $cutoff
            )
        ) ?: [];
    }

    /**
     * Get subscriptions that have passed their expiry date.
     *
     * @return array<object>
     */
    public function get_expired(): array
    {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT * FROM {$this->table}
             WHERE status = 'active'
               AND expires_at IS NOT NULL
               AND expires_at < NOW()"
        ) ?: [];
    }

    /**
     * Update subscription status.
     */
    public function set_status(int $id, string $status, ?string $cancelled_at = null): bool
    {
        $data = ['status' => $status];
        if ($cancelled_at !== null) {
            $data['cancelled_at'] = $cancelled_at;
        }
        return $this->update($id, $data);
    }

    /**
     * Extend or set the expiry date for a subscription.
     */
    public function set_expiry(int $id, string $expires_at): bool
    {
        return $this->update($id, ['expires_at' => $expires_at]);
    }

    /**
     * Paginated admin list of subscriptions with customer + package details.
     *
     * @return array{rows: array<object>, total: int}
     */
    public function get_admin_list(
        string $status = '',
        int $customer_id = 0,
        int $page = 1,
        int $per_page = 20
    ): array {
        global $wpdb;

        $where = ['1=1'];
        $values = [];

        if ($status !== '') {
            $where[] = 's.status = %s';
            $values[] = $status;
        }
        if ($customer_id > 0) {
            $where[] = 's.customer_id = %d';
            $values[] = $customer_id;
        }

        $where_sql = implode(' AND ', $where);
        $offset = ($page - 1) * $per_page;

        $base = "FROM {$this->table} s
                 INNER JOIN {$wpdb->prefix}whoiscrm_customers c ON c.id = s.customer_id
                 INNER JOIN {$wpdb->users} u ON u.ID = c.user_id
                 INNER JOIN {$wpdb->prefix}whoiscrm_packages p ON p.id = s.package_id
                 INNER JOIN {$wpdb->prefix}whoiscrm_package_pricing pp ON pp.id = s.package_pricing_id
                 WHERE {$where_sql}";

        $count_sql = "SELECT COUNT(*) {$base}";
        $data_sql  = "SELECT s.*,
                          u.user_email AS customer_email,
                          c.company_name,
                          p.name  AS package_name,
                          pp.billing_cycle,
                          pp.price,
                          pp.currency
                      {$base}
                      ORDER BY s.created_at DESC
                      LIMIT %d OFFSET %d";

        $values_with_limit = array_merge($values, [$per_page, $offset]);

        if (!empty($values)) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $total = (int) $wpdb->get_var($wpdb->prepare($count_sql, ...$values));
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $rows  = $wpdb->get_results($wpdb->prepare($data_sql, ...$values_with_limit));
        } else {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $total = (int) $wpdb->get_var($count_sql);
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $rows  = $wpdb->get_results($wpdb->prepare($data_sql, $per_page, $offset));
        }

        return ['rows' => $rows ?: [], 'total' => $total];
    }
}
