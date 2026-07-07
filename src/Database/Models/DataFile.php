<?php

declare(strict_types=1);

namespace WhoisCRM\Database\Models;

/**
 * DataFile model.
 *
 * Represents an uploaded WHOIS data file stored in the protected
 * /wp-content/whois-data/ directory. Each file is linked to a
 * service type and country code; subscriptions grant access to files
 * matching the subscribed service + country combination.
 */
class DataFile extends BaseModel
{
    protected function table_name(): string
    {
        return 'data_files';
    }

    /**
     * Get files a specific customer is allowed to access.
     *
     * Builds a query based on the customer's active subscriptions:
     * - Enterprise → all files
     * - Global service → files matching that service_type (any country)
     * - Country-specific → files matching service_type = 'country_data' AND country_code
     *
     * @param int    $customer_id
     * @param array  $filters     ['country_code', 'tld', 'date_from', 'date_to', 'service_type']
     * @param int    $page
     * @param int    $per_page
     * @return array{rows: array<object>, total: int}
     */
    public function get_accessible_for_customer(
        int $customer_id,
        array $filters = [],
        int $page = 1,
        int $per_page = 30
    ): array {
        global $wpdb;

        $subs_table    = $wpdb->prefix . 'whoiscrm_subscriptions';
        $packages_table = $wpdb->prefix . 'whoiscrm_packages';

        // Build access-control subquery based on subscriptions.
        $access_subquery = "
            EXISTS (
                SELECT 1
                FROM {$subs_table} s
                INNER JOIN {$packages_table} p ON p.id = s.package_id
                WHERE s.customer_id = {$customer_id}
                  AND s.status IN ('active', 'trialing')
                  AND (
                      /* Enterprise: full access */
                      p.service_type = 'enterprise'
                      OR
                      /* Global service: match service_type, all countries */
                      (p.service_type = f.service_type AND p.countries IS NULL)
                      OR
                      /* Country-specific: match service_type AND country */
                      (
                          p.service_type = f.service_type
                          AND JSON_CONTAINS(p.countries, JSON_QUOTE(f.country_code))
                      )
                  )
            )
        ";

        // Build filter conditions.
        $where = ["f.is_active = 1", "({$access_subquery})"];
        $values = [];

        if (!empty($filters['country_code'])) {
            $where[] = 'f.country_code = %s';
            $values[] = strtoupper($filters['country_code']);
        }
        if (!empty($filters['tld'])) {
            $where[] = 'f.tld = %s';
            $values[] = $filters['tld'];
        }
        if (!empty($filters['service_type'])) {
            $where[] = 'f.service_type = %s';
            $values[] = $filters['service_type'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'f.data_date >= %s';
            $values[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'f.data_date <= %s';
            $values[] = $filters['date_to'];
        }

        $where_sql = implode(' AND ', $where);
        $offset = ($page - 1) * $per_page;

        $count_sql = "SELECT COUNT(*) FROM {$this->table} f WHERE {$where_sql}";
        $data_sql  = "SELECT f.* FROM {$this->table} f WHERE {$where_sql}
                      ORDER BY f.data_date DESC, f.created_at DESC
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

    /**
     * Get the absolute server path for a data file record.
     */
    public function get_absolute_path(object $file): string
    {
        return WHOISCRM_DATA_DIR . ltrim($file->file_path, '/');
    }

    /**
     * Check that a data file record's physical file still exists.
     */
    public function file_exists(object $file): bool
    {
        return file_exists($this->get_absolute_path($file));
    }

    /**
     * Paginated admin list of all data files.
     *
     * @return array{rows: array<object>, total: int}
     */
    public function get_admin_list(
        array $filters = [],
        int $page = 1,
        int $per_page = 30
    ): array {
        global $wpdb;

        $where = ['f.is_active = 1'];
        $values = [];

        if (!empty($filters['country_code'])) {
            $where[] = 'f.country_code = %s';
            $values[] = strtoupper($filters['country_code']);
        }
        if (!empty($filters['service_type'])) {
            $where[] = 'f.service_type = %s';
            $values[] = $filters['service_type'];
        }
        if (!empty($filters['date_from'])) {
            $where[] = 'f.data_date >= %s';
            $values[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'f.data_date <= %s';
            $values[] = $filters['date_to'];
        }

        $where_sql = implode(' AND ', $where);
        $offset = ($page - 1) * $per_page;

        $users_table = $wpdb->users;
        $base = "FROM {$this->table} f
                 LEFT JOIN {$users_table} u ON u.ID = f.uploaded_by
                 WHERE {$where_sql}";

        $count_sql = "SELECT COUNT(*) {$base}";
        $data_sql  = "SELECT f.*, u.user_login AS uploaded_by_login {$base}
                      ORDER BY f.data_date DESC, f.created_at DESC
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

    /**
     * Soft-delete a data file (set is_active = 0).
     */
    public function soft_delete(int $id): bool
    {
        return $this->update($id, ['is_active' => 0]);
    }
}
