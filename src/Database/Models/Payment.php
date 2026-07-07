<?php

declare(strict_types=1);

namespace WhoisCRM\Database\Models;

/**
 * Payment model.
 *
 * Records every payment attempt (successful or failed).
 * Linked to customer, subscription, and optionally a coupon.
 */
class Payment extends BaseModel
{
    protected function table_name(): string
    {
        return 'payments';
    }

    public const STATUS_PENDING   = 'pending';
    public const STATUS_SUCCEEDED = 'succeeded';
    public const STATUS_FAILED    = 'failed';
    public const STATUS_REFUNDED  = 'refunded';

    /**
     * Get all payments for a customer, newest first.
     *
     * @return array<object>
     */
    public function get_for_customer(int $customer_id, int $limit = 0): array
    {
        return $this->get_where(['customer_id' => $customer_id], 'created_at', 'DESC', $limit);
    }

    /**
     * Get all payments for a subscription.
     *
     * @return array<object>
     */
    public function get_for_subscription(int $subscription_id): array
    {
        return $this->get_where(['subscription_id' => $subscription_id], 'created_at', 'DESC');
    }

    /**
     * Find a payment by Stripe PaymentIntent ID.
     */
    public function find_by_payment_intent(string $payment_intent_id): ?object
    {
        return $this->find_by('stripe_payment_intent_id', $payment_intent_id);
    }

    /**
     * Find a payment by Stripe Invoice ID.
     */
    public function find_by_stripe_invoice(string $stripe_invoice_id): ?object
    {
        return $this->find_by('stripe_invoice_id', $stripe_invoice_id);
    }

    /**
     * Find a payment by Stripe Checkout Session ID.
     */
    public function find_by_checkout_session(string $session_id): ?object
    {
        return $this->find_by('stripe_checkout_session_id', $session_id);
    }

    /**
     * Mark a payment as succeeded.
     */
    public function mark_succeeded(int $id, string $payment_method = ''): bool
    {
        $data = [
            'status'  => self::STATUS_SUCCEEDED,
            'paid_at' => current_time('mysql', true),
        ];

        if ($payment_method !== '') {
            $data['payment_method'] = $payment_method;
        }

        return $this->update($id, $data);
    }

    /**
     * Mark a payment as failed.
     */
    public function mark_failed(int $id): bool
    {
        return $this->update($id, ['status' => self::STATUS_FAILED]);
    }

    /**
     * Mark a payment as refunded.
     */
    public function mark_refunded(int $id, float $refund_amount, string $reason = ''): bool
    {
        return $this->update($id, [
            'status'        => self::STATUS_REFUNDED,
            'refund_amount' => $refund_amount,
            'refund_reason' => $reason,
        ]);
    }

    /**
     * Get total revenue (succeeded payments) between two dates.
     */
    public function get_revenue(string $from = '', string $to = ''): float
    {
        global $wpdb;

        $where = ["status = 'succeeded'"];
        $values = [];

        if ($from !== '') {
            $where[] = 'paid_at >= %s';
            $values[] = $from;
        }
        if ($to !== '') {
            $where[] = 'paid_at <= %s';
            $values[] = $to;
        }

        $where_sql = implode(' AND ', $where);
        $sql = "SELECT COALESCE(SUM(total_amount), 0) FROM {$this->table} WHERE {$where_sql}";

        if (!empty($values)) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $sql = $wpdb->prepare($sql, ...$values);
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        return (float) $wpdb->get_var($sql);
    }

    /**
     * Paginated admin payment list with customer data.
     *
     * @return array{rows: array<object>, total: int}
     */
    public function get_admin_list(
        string $status = '',
        int $customer_id = 0,
        string $from = '',
        string $to = '',
        int $page = 1,
        int $per_page = 20
    ): array {
        global $wpdb;

        $where = ['1=1'];
        $values = [];

        if ($status !== '') {
            $where[] = 'pay.status = %s';
            $values[] = $status;
        }
        if ($customer_id > 0) {
            $where[] = 'pay.customer_id = %d';
            $values[] = $customer_id;
        }
        if ($from !== '') {
            $where[] = 'pay.paid_at >= %s';
            $values[] = $from;
        }
        if ($to !== '') {
            $where[] = 'pay.paid_at <= %s';
            $values[] = $to;
        }

        $where_sql = implode(' AND ', $where);
        $offset = ($page - 1) * $per_page;

        $base = "FROM {$this->table} pay
                 INNER JOIN {$wpdb->prefix}whoiscrm_customers c ON c.id = pay.customer_id
                 INNER JOIN {$wpdb->users} u ON u.ID = c.user_id
                 WHERE {$where_sql}";

        $count_sql = "SELECT COUNT(*) {$base}";
        $data_sql  = "SELECT pay.*, u.user_email AS customer_email, c.company_name
                      {$base}
                      ORDER BY pay.created_at DESC
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
