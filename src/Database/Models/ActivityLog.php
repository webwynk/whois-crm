<?php

declare(strict_types=1);

namespace WhoisCRM\Database\Models;

/**
 * ActivityLog model.
 *
 * Records all significant events: logins, downloads, payments,
 * admin actions, failed attempts, and API calls.
 *
 * Severity levels: debug, info, warning, error, critical
 */
class ActivityLog extends BaseModel
{
    protected function table_name(): string
    {
        return 'activity_log';
    }

    // ─── Action Constants ──────────────────────────────────────────────────

    public const ACTION_LOGIN              = 'login';
    public const ACTION_LOGIN_FAILED       = 'login_failed';
    public const ACTION_LOGOUT             = 'logout';
    public const ACTION_REGISTER           = 'register';
    public const ACTION_PASSWORD_RESET     = 'password_reset';
    public const ACTION_DOWNLOAD           = 'download';
    public const ACTION_PAYMENT            = 'payment';
    public const ACTION_PAYMENT_FAILED     = 'payment_failed';
    public const ACTION_SUBSCRIPTION       = 'subscription';
    public const ACTION_SUBSCRIPTION_CANCEL = 'subscription_cancel';
    public const ACTION_WEBHOOK            = 'webhook';
    public const ACTION_ADMIN_ACTION       = 'admin_action';
    public const ACTION_API_REQUEST        = 'api_request';
    public const ACTION_FILE_UPLOAD        = 'file_upload';
    public const ACTION_CUSTOMER_BLOCKED   = 'customer_blocked';
    public const ACTION_CUSTOMER_UNBLOCKED = 'customer_unblocked';

    // ─── Severity Constants ────────────────────────────────────────────────

    public const SEVERITY_DEBUG    = 'debug';
    public const SEVERITY_INFO     = 'info';
    public const SEVERITY_WARNING  = 'warning';
    public const SEVERITY_ERROR    = 'error';
    public const SEVERITY_CRITICAL = 'critical';

    /**
     * Log an event.
     *
     * @param string   $action      One of the ACTION_* constants.
     * @param string   $description Human-readable description.
     * @param array    $meta        Optional key-value metadata.
     * @param string   $severity    One of the SEVERITY_* constants.
     * @param int|null $user_id     WP user ID (null = system).
     * @param string   $object_type Entity type ('subscription', 'payment', etc.)
     * @param int      $object_id   Entity ID.
     */
    public function log(
        string $action,
        string $description,
        array $meta = [],
        string $severity = self::SEVERITY_INFO,
        ?int $user_id = null,
        string $object_type = '',
        int $object_id = 0
    ): int|false {
        if ($user_id === null) {
            $user_id = get_current_user_id() ?: null;
        }

        return $this->insert([
            'user_id'     => $user_id,
            'action'      => $action,
            'description' => $description,
            'object_type' => $object_type ?: null,
            'object_id'   => $object_id > 0 ? $object_id : null,
            'ip_address'  => $this->get_client_ip(),
            'user_agent'  => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
            'metadata'    => !empty($meta) ? wp_json_encode($meta) : null,
            'severity'    => $severity,
            'created_at'  => current_time('mysql', true),
        ]);
    }

    // ─── Convenience Shortcuts ─────────────────────────────────────────────

    public function log_login(int $user_id, string $ip = ''): void
    {
        $this->log(self::ACTION_LOGIN, 'Customer logged in.', [], self::SEVERITY_INFO, $user_id);
    }

    public function log_login_failed(string $email): void
    {
        $this->log(self::ACTION_LOGIN_FAILED, "Failed login attempt for: {$email}", ['email' => $email], self::SEVERITY_WARNING, null);
    }

    public function log_download(int $user_id, int $file_id, string $filename): void
    {
        $this->log(self::ACTION_DOWNLOAD, "Downloaded file: {$filename}", ['file_id' => $file_id], self::SEVERITY_INFO, $user_id, 'data_file', $file_id);
    }

    public function log_payment(int $user_id, int $payment_id, float $amount, string $currency): void
    {
        $this->log(self::ACTION_PAYMENT, "Payment of {$currency} {$amount} recorded.", ['payment_id' => $payment_id, 'amount' => $amount], self::SEVERITY_INFO, $user_id, 'payment', $payment_id);
    }

    public function log_webhook(string $event_type, array $meta = []): void
    {
        $this->log(self::ACTION_WEBHOOK, "Stripe webhook received: {$event_type}", $meta, self::SEVERITY_INFO, null);
    }

    /**
     * Paginated admin activity log query.
     *
     * @return array{rows: array<object>, total: int}
     */
    public function get_admin_list(
        int $user_id = 0,
        string $action = '',
        string $severity = '',
        string $from = '',
        string $to = '',
        int $page = 1,
        int $per_page = 50
    ): array {
        global $wpdb;

        $where = ['1=1'];
        $values = [];

        if ($user_id > 0) {
            $where[] = 'l.user_id = %d';
            $values[] = $user_id;
        }
        if ($action !== '') {
            $where[] = 'l.action = %s';
            $values[] = $action;
        }
        if ($severity !== '') {
            $where[] = 'l.severity = %s';
            $values[] = $severity;
        }
        if ($from !== '') {
            $where[] = 'l.created_at >= %s';
            $values[] = $from;
        }
        if ($to !== '') {
            $where[] = 'l.created_at <= %s';
            $values[] = $to;
        }

        $where_sql = implode(' AND ', $where);
        $offset = ($page - 1) * $per_page;

        $base = "FROM {$this->table} l
                 LEFT JOIN {$wpdb->users} u ON u.ID = l.user_id
                 WHERE {$where_sql}";

        $count_sql = "SELECT COUNT(*) {$base}";
        $data_sql  = "SELECT l.*, u.user_email AS user_email {$base}
                      ORDER BY l.created_at DESC
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
     * Delete log entries older than N days (cleanup cron).
     */
    public function cleanup(int $days = 90): int
    {
        global $wpdb;

        $result = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table}
                 WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)
                   AND severity IN ('debug', 'info')",
                $days
            )
        );

        return $result !== false ? (int) $result : 0;
    }

    /**
     * Get the client IP address.
     */
    private function get_client_ip(): string
    {
        foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'] as $key) {
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
