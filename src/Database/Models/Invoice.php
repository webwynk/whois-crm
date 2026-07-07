<?php

declare(strict_types=1);

namespace WhoisCRM\Database\Models;

/**
 * Invoice model.
 *
 * Represents a generated PDF invoice linked to a payment.
 * Stores complete billing snapshot (buyer + seller details)
 * so invoices remain accurate even if settings later change.
 */
class Invoice extends BaseModel
{
    protected function table_name(): string
    {
        return 'invoices';
    }

    /**
     * Get the next invoice sequence number for the current year.
     * Used to generate a unique invoice number.
     */
    public function get_next_sequence(): int
    {
        global $wpdb;

        $year = gmdate('Y');

        $max = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table}
                 WHERE invoice_date >= %s AND invoice_date < %s",
                "{$year}-01-01",
                ($year + 1) . '-01-01'
            )
        );

        return $max + 1;
    }

    /**
     * Find an invoice by invoice number.
     */
    public function find_by_number(string $invoice_number): ?object
    {
        return $this->find_by('invoice_number', $invoice_number);
    }

    /**
     * Find an invoice by payment ID.
     */
    public function find_by_payment(int $payment_id): ?object
    {
        return $this->find_by('payment_id', $payment_id);
    }

    /**
     * Get all invoices for a customer.
     *
     * @return array<object>
     */
    public function get_for_customer(int $customer_id, int $limit = 0): array
    {
        return $this->get_where(['customer_id' => $customer_id], 'invoice_date', 'DESC', $limit);
    }

    /**
     * Set the PDF file path once the PDF has been generated.
     */
    public function set_pdf_path(int $id, string $pdf_path): bool
    {
        return $this->update($id, ['pdf_path' => $pdf_path]);
    }

    /**
     * Get the public download URL for an invoice PDF.
     *
     * Returns null if the PDF hasn't been generated yet.
     * The actual file is served via a secure download handler.
     */
    public function get_download_url(object $invoice): ?string
    {
        if (empty($invoice->pdf_path)) {
            return null;
        }

        return add_query_arg([
            'whoiscrm_action'  => 'download_invoice',
            'invoice_id'       => $invoice->id,
            '_wpnonce'         => wp_create_nonce('whoiscrm_invoice_' . $invoice->id),
        ], home_url('/'));
    }

    /**
     * Paginated admin invoice list.
     *
     * @return array{rows: array<object>, total: int}
     */
    public function get_admin_list(
        int $customer_id = 0,
        string $from = '',
        string $to = '',
        int $page = 1,
        int $per_page = 20
    ): array {
        global $wpdb;

        $where = ['1=1'];
        $values = [];

        if ($customer_id > 0) {
            $where[] = 'i.customer_id = %d';
            $values[] = $customer_id;
        }
        if ($from !== '') {
            $where[] = 'i.invoice_date >= %s';
            $values[] = $from;
        }
        if ($to !== '') {
            $where[] = 'i.invoice_date <= %s';
            $values[] = $to;
        }

        $where_sql = implode(' AND ', $where);
        $offset = ($page - 1) * $per_page;

        $base = "FROM {$this->table} i WHERE {$where_sql}";

        $count_sql = "SELECT COUNT(*) {$base}";
        $data_sql  = "SELECT i.* {$base} ORDER BY i.invoice_date DESC LIMIT %d OFFSET %d";

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
