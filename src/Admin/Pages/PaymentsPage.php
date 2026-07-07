<?php

declare(strict_types=1);

namespace WhoisCRM\Admin\Pages;

use WhoisCRM\Database\Models\Payment;

/**
 * Payments list admin page.
 *
 * Read-only view of all payment records with status/date filters.
 */
class PaymentsPage extends BasePage
{
    protected static string $required_cap = 'whoiscrm_view_payments';

    protected function display(): void
    {
        $status   = sanitize_key($_GET['status'] ?? '');
        $customer = (int) ($_GET['customer_id'] ?? 0);
        $from     = sanitize_text_field($_GET['from'] ?? '');
        $to       = sanitize_text_field($_GET['to']   ?? '');
        $page     = $this->get_current_page();
        $per      = 20;

        $result   = (new Payment())->get_admin_list($status, $customer, $from, $to, $page, $per);

        // Revenue summary for the filtered period
        $revenue  = (new Payment())->get_revenue($from, $to);

        $this->page_header(__('Payments', 'whois-crm'));
        $this->render_template('payments/list', [
            'rows'          => $result['rows'],
            'total'         => $result['total'],
            'per_page'      => $per,
            'current_page'  => $page,
            'status_filter' => $status,
            'from'          => $from,
            'to'            => $to,
            'revenue'       => $revenue,
            'pagination'    => $this->pagination_html($result['total'], $per, $page),
        ]);
        $this->page_footer();
    }
}
