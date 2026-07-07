<?php

declare(strict_types=1);

namespace WhoisCRM\Admin\Pages;

use WhoisCRM\Database\Models\Invoice;

/**
 * Invoices list admin page.
 */
class InvoicesPage extends BasePage
{
    protected static string $required_cap = 'whoiscrm_view_payments';

    protected function display(): void
    {
        $customer = (int) ($_GET['customer_id'] ?? 0);
        $from     = sanitize_text_field($_GET['from'] ?? '');
        $to       = sanitize_text_field($_GET['to']   ?? '');
        $page     = $this->get_current_page();
        $per      = 20;

        $result   = (new Invoice())->get_admin_list($customer, $from, $to, $page, $per);

        $this->page_header(__('Invoices', 'whois-crm'));
        $this->render_template('invoices/list', [
            'rows'         => $result['rows'],
            'total'        => $result['total'],
            'per_page'     => $per,
            'current_page' => $page,
            'pagination'   => $this->pagination_html($result['total'], $per, $page),
            'from'         => $from,
            'to'           => $to,
        ]);
        $this->page_footer();
    }
}
