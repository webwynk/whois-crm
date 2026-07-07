<?php

declare(strict_types=1);

namespace WhoisCRM\Admin\Pages;

use WhoisCRM\Database\Models\Subscription;

/**
 * Subscriptions list admin page.
 *
 * Shows all subscriptions with status filter,
 * and supports cancel + manual status update actions.
 */
class SubscriptionsPage extends BasePage
{
    protected static string $required_cap = 'whoiscrm_manage_subscriptions';

    protected function display(): void
    {
        // POST action handler
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['whoiscrm_action'])) {
            $this->handle_action();
        }

        $status   = sanitize_key($_GET['status'] ?? '');
        $customer = (int) ($_GET['customer_id'] ?? 0);
        $page     = $this->get_current_page();
        $per      = 20;

        $result = (new Subscription())->get_admin_list($status, $customer, $page, $per);

        $this->page_header(__('Subscriptions', 'whois-crm'));
        $this->show_notices('whoiscrm_subscriptions');
        $this->render_template('subscriptions/list', [
            'rows'         => $result['rows'],
            'total'        => $result['total'],
            'per_page'     => $per,
            'current_page' => $page,
            'status_filter' => $status,
            'pagination'   => $this->pagination_html($result['total'], $per, $page),
            'nonce'        => wp_create_nonce('whoiscrm_subscription_action'),
        ]);
        $this->page_footer();
    }

    private function handle_action(): void
    {
        check_admin_referer('whoiscrm_subscription_action');

        if (!current_user_can(static::$required_cap)) {
            wp_die(__('Unauthorized.', 'whois-crm'));
        }

        $action = sanitize_key($_POST['whoiscrm_action']);
        $sub_id = (int) ($_POST['subscription_id'] ?? 0);
        $model  = new Subscription();

        switch ($action) {
            case 'cancel':
                $model->set_status($sub_id, Subscription::STATUS_CANCELLED, current_time('mysql', true));
                self::set_notice('whoiscrm_subscriptions', __('Subscription cancelled.', 'whois-crm'));
                break;

            case 'activate':
                $model->set_status($sub_id, Subscription::STATUS_ACTIVE);
                self::set_notice('whoiscrm_subscriptions', __('Subscription activated.', 'whois-crm'));
                break;
        }

        wp_safe_redirect(admin_url('admin.php?page=whoiscrm-subscriptions'));
        exit;
    }
}
