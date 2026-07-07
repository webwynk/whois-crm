<?php

declare(strict_types=1);

namespace WhoisCRM\Admin\Pages;

use WhoisCRM\Database\Models\Customer;
use WhoisCRM\Database\Models\Subscription;

/**
 * Customers list and detail admin page.
 *
 * Handles:
 *  - Paginated customer list with search/filter
 *  - Block / unblock customer (POST action)
 *  - View individual customer detail
 */
class CustomersPage extends BasePage
{
    protected static string $required_cap = 'whoiscrm_manage_customers';

    protected function display(): void
    {
        // ── POST actions ─────────────────────────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['whoiscrm_action'])) {
            $this->handle_action();
        }

        // ── Single customer detail view ──────────────────────────────────
        $view_id = (int) ($_GET['view'] ?? 0);
        if ($view_id > 0) {
            $this->display_customer_detail($view_id);
            return;
        }

        // ── List view ────────────────────────────────────────────────────
        $search  = $this->get_search();
        $status  = sanitize_key($_GET['status'] ?? '');
        $page    = $this->get_current_page();
        $per     = 20;

        $result  = (new Customer())->get_list($search, $status, $page, $per);

        $this->page_header(
            __('Customers', 'whois-crm'),
            '',
            [['label' => __('+ Add Customer', 'whois-crm'), 'url' => '#', 'class' => 'whoiscrm-btn--primary whoiscrm-btn--md']]
        );

        $this->show_notices('whoiscrm_customers');
        $this->render_template('customers/list', [
            'rows'       => $result['rows'],
            'total'      => $result['total'],
            'per_page'   => $per,
            'current_page' => $page,
            'search'     => $search,
            'status'     => $status,
            'pagination' => $this->pagination_html($result['total'], $per, $page),
            'nonce'      => wp_create_nonce('whoiscrm_customer_action'),
        ]);
        $this->page_footer();
    }

    private function display_customer_detail(int $customer_id): void
    {
        $customer_model = new Customer();
        $customer       = $customer_model->get_with_user_data($customer_id);

        if (!$customer) {
            wp_die(__('Customer not found.', 'whois-crm'));
        }

        $subscriptions = (new Subscription())->get_all_for_customer($customer_id);

        $this->page_header(
            __('Customer Detail', 'whois-crm'),
            __('Customers', 'whois-crm')
        );

        $this->render_template('customers/detail', [
            'customer'      => $customer,
            'subscriptions' => $subscriptions,
            'nonce'         => wp_create_nonce('whoiscrm_customer_action'),
            'back_url'      => admin_url('admin.php?page=whoiscrm-customers'),
        ]);
        $this->page_footer();
    }

    private function handle_action(): void
    {
        check_admin_referer('whoiscrm_customer_action');

        if (!current_user_can(static::$required_cap)) {
            wp_die(__('Unauthorized.', 'whois-crm'));
        }

        $action      = sanitize_key($_POST['whoiscrm_action']);
        $customer_id = (int) ($_POST['customer_id'] ?? 0);

        $model = new Customer();

        switch ($action) {
            case 'block':
                $model->set_active($customer_id, false);
                self::set_notice('whoiscrm_customers', __('Customer blocked.', 'whois-crm'));
                break;

            case 'unblock':
                $model->set_active($customer_id, true);
                self::set_notice('whoiscrm_customers', __('Customer unblocked.', 'whois-crm'));
                break;
        }

        wp_safe_redirect(admin_url('admin.php?page=whoiscrm-customers'));
        exit;
    }
}
