<?php

declare(strict_types=1);

namespace WhoisCRM\Database\Models;

/**
 * Download model.
 *
 * Records every file download by a customer.
 * Used for rate limiting, activity auditing, and reporting.
 */
class Download extends BaseModel
{
    protected function table_name(): string
    {
        return 'downloads';
    }

    /**
     * Record a download event.
     *
     * @param int    $customer_id
     * @param int    $data_file_id
     * @param int    $file_size     File size in bytes.
     * @param string $source        'portal' or 'api'.
     * @param int    $subscription_id  Optional subscription ID context.
     */
    public function record(
        int $customer_id,
        int $data_file_id,
        int $file_size = 0,
        string $source = 'portal',
        int $subscription_id = 0
    ): int|false {
        $data = [
            'customer_id'     => $customer_id,
            'data_file_id'    => $data_file_id,
            'file_size'       => $file_size,
            'ip_address'      => $this->get_client_ip(),
            'user_agent'      => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            'download_source' => $source,
            'downloaded_at'   => current_time('mysql', true),
        ];

        if ($subscription_id > 0) {
            $data['subscription_id'] = $subscription_id;
        }

        return $this->insert($data);
    }

    /**
     * Count downloads by a customer in the last 24 hours (rate limit check).
     */
    public function count_today(int $customer_id): int
    {
        global $wpdb;

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table}
                 WHERE customer_id = %d
                   AND downloaded_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)",
                $customer_id
            )
        );
    }

    /**
     * Count how many times a specific file has been downloaded.
     */
    public function count_for_file(int $data_file_id): int
    {
        global $wpdb;

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table} WHERE data_file_id = %d",
                $data_file_id
            )
        );
    }

    /**
     * Get recent downloads for a customer (portal history display).
     *
     * @return array<object>
     */
    public function get_for_customer(int $customer_id, int $limit = 20): array
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT d.*, f.original_filename, f.country_name, f.country_code,
                         f.data_date, f.service_type, f.file_type
                 FROM {$this->table} d
                 INNER JOIN {$wpdb->prefix}whoiscrm_data_files f ON f.id = d.data_file_id
                 WHERE d.customer_id = %d
                 ORDER BY d.downloaded_at DESC
                 LIMIT %d",
                $customer_id,
                $limit
            )
        ) ?: [];
    }

    /**
     * Get top downloaded files for analytics.
     *
     * @return array<object>
     */
    public function get_top_files(int $limit = 10, string $from = '', string $to = ''): array
    {
        global $wpdb;

        $where = ['1=1'];
        $values = [];

        if ($from !== '') {
            $where[] = 'd.downloaded_at >= %s';
            $values[] = $from;
        }
        if ($to !== '') {
            $where[] = 'd.downloaded_at <= %s';
            $values[] = $to;
        }

        $where_sql = implode(' AND ', $where);
        $values[] = $limit;

        $sql = "SELECT f.original_filename, f.country_name, f.country_code,
                       f.service_type, COUNT(d.id) AS download_count
                FROM {$this->table} d
                INNER JOIN {$wpdb->prefix}whoiscrm_data_files f ON f.id = d.data_file_id
                WHERE {$where_sql}
                GROUP BY d.data_file_id
                ORDER BY download_count DESC
                LIMIT %d";

        if (!empty($values)) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $sql = $wpdb->prepare($sql, ...$values);
        }

        return $wpdb->get_results($sql) ?: [];
    }

    /**
     * Get top customers by download volume.
     *
     * @return array<object>
     */
    public function get_top_customers(int $limit = 10): array
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT c.id AS customer_id, u.user_email, c.company_name,
                         COUNT(d.id) AS download_count,
                         SUM(d.file_size) AS total_bytes
                 FROM {$this->table} d
                 INNER JOIN {$wpdb->prefix}whoiscrm_customers c ON c.id = d.customer_id
                 INNER JOIN {$wpdb->users} u ON u.ID = c.user_id
                 GROUP BY d.customer_id
                 ORDER BY download_count DESC
                 LIMIT %d",
                $limit
            )
        ) ?: [];
    }

    /**
     * Get the client IP address, handling common proxy headers.
     */
    private function get_client_ip(): string
    {
        $keys = [
            'HTTP_CF_CONNECTING_IP',   // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '';
    }
}
