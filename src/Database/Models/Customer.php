<?php

declare(strict_types=1);

namespace WhoisCRM\Database\Models;

/**
 * Customer model.
 *
 * Represents the extended profile for a WordPress user who is
 * a WHOIS CRM customer. Linked to wp_users via user_id.
 */
class Customer extends BaseModel
{
    protected function table_name(): string
    {
        return 'customers';
    }

    /**
     * Find a customer record by WordPress user ID.
     */
    public function find_by_user_id(int $user_id): ?object
    {
        return $this->find_by('user_id', $user_id);
    }

    /**
     * Find a customer by Stripe customer ID.
     */
    public function find_by_stripe_customer(string $stripe_customer_id): ?object
    {
        return $this->find_by('stripe_customer_id', $stripe_customer_id);
    }

    /**
     * Get the currently logged-in customer's record.
     */
    public function get_current(): ?object
    {
        $user_id = get_current_user_id();
        if ($user_id === 0) {
            return null;
        }

        return $this->find_by_user_id($user_id);
    }

    /**
     * Get the full customer record merged with WordPress user data.
     *
     * Returns a combined object with: id, user_id, email, first_name,
     * last_name, display_name, company_name, is_active, stripe_customer_id, etc.
     */
    public function get_with_user_data(int $customer_id): ?object
    {
        global $wpdb;

        $users_table = $wpdb->users;
        $usermeta_table = $wpdb->usermeta;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    c.*,
                    u.user_email AS email,
                    u.user_registered,
                    u.user_login,
                    MAX(CASE WHEN um.meta_key = 'first_name' THEN um.meta_value END) AS first_name,
                    MAX(CASE WHEN um.meta_key = 'last_name'  THEN um.meta_value END) AS last_name
                FROM {$this->table} c
                INNER JOIN {$users_table} u ON u.ID = c.user_id
                LEFT JOIN {$usermeta_table} um ON um.user_id = u.ID
                    AND um.meta_key IN ('first_name', 'last_name')
                WHERE c.id = %d
                GROUP BY c.id, u.ID",
                $customer_id
            )
        );

        return $row ?: null;
    }

    /**
     * Get a paginated list of customers with user data, filtered by search/status.
     *
     * @return array{rows: array<object>, total: int}
     */
    public function get_list(
        string $search = '',
        string $status = '',
        int $page = 1,
        int $per_page = 20,
        string $orderby = 'c.created_at',
        string $order = 'DESC'
    ): array {
        global $wpdb;

        $users_table    = $wpdb->users;
        $usermeta_table = $wpdb->usermeta;

        $where_clauses = ['1=1'];
        $values = [];

        if ($search !== '') {
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where_clauses[] = '(u.user_email LIKE %s OR c.company_name LIKE %s OR CONCAT(fn.meta_value, " ", ln.meta_value) LIKE %s)';
            $values[] = $like;
            $values[] = $like;
            $values[] = $like;
        }

        if ($status === 'active') {
            $where_clauses[] = 'c.is_active = 1';
        } elseif ($status === 'blocked') {
            $where_clauses[] = 'c.is_active = 0';
        }

        $where_sql = implode(' AND ', $where_clauses);
        $order_sql = sanitize_key($orderby) . ' ' . ($order === 'ASC' ? 'ASC' : 'DESC');
        $offset = ($page - 1) * $per_page;

        $base_sql = "FROM {$this->table} c
                     INNER JOIN {$users_table} u ON u.ID = c.user_id
                     LEFT JOIN {$usermeta_table} fn ON fn.user_id = u.ID AND fn.meta_key = 'first_name'
                     LEFT JOIN {$usermeta_table} ln ON ln.user_id = u.ID AND ln.meta_key = 'last_name'
                     WHERE {$where_sql}";

        $count_sql = "SELECT COUNT(*) {$base_sql}";
        $data_sql  = "SELECT c.*, u.user_email AS email, u.user_registered,
                          COALESCE(fn.meta_value, '') AS first_name,
                          COALESCE(ln.meta_value, '') AS last_name
                      {$base_sql}
                      ORDER BY {$order_sql}
                      LIMIT %d OFFSET %d";

        $values_with_limit = array_merge($values, [$per_page, $offset]);

        if (!empty($values)) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $total = (int) $wpdb->get_var($wpdb->prepare($count_sql, ...$values));
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $rows  = $wpdb->get_results($wpdb->prepare($data_sql, ...$values_with_limit));
        } else {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $total = (int) $wpdb->get_var($count_sql . ' LIMIT %d OFFSET %d');
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $rows  = $wpdb->get_results($wpdb->prepare($data_sql, $per_page, $offset));
        }

        return ['rows' => $rows ?: [], 'total' => $total];
    }

    /**
     * Toggle a customer's active status.
     */
    public function set_active(int $id, bool $active): bool
    {
        return $this->update($id, ['is_active' => (int) $active]);
    }

    /**
     * Store or update the Stripe customer ID.
     */
    public function set_stripe_customer_id(int $id, string $stripe_customer_id): bool
    {
        return $this->update($id, ['stripe_customer_id' => $stripe_customer_id]);
    }

    /**
     * Check if the given customer ID belongs to the given WP user ID.
     * Used to prevent horizontal privilege escalation.
     */
    public function belongs_to_user(int $customer_id, int $user_id): bool
    {
        global $wpdb;

        $result = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$this->table} WHERE id = %d AND user_id = %d LIMIT 1",
                $customer_id,
                $user_id
            )
        );

        return $result !== null;
    }
}
