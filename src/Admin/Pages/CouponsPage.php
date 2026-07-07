<?php

declare(strict_types=1);

namespace WhoisCRM\Admin\Pages;

use WhoisCRM\Database\Models\Coupon;

/**
 * Coupons admin page.
 *
 * CRUD for discount coupons (percentage + fixed).
 */
class CouponsPage extends BasePage
{
    protected static string $required_cap = 'whoiscrm_manage_coupons';

    protected function display(): void
    {
        // POST actions
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['whoiscrm_action'])) {
            $this->handle_action();
        }

        $sub_view = sanitize_key($_GET['view'] ?? 'list');

        if ($sub_view === 'add' || isset($_GET['edit'])) {
            $this->display_form((int) ($_GET['edit'] ?? 0));
            return;
        }

        $page   = $this->get_current_page();
        $per    = 20;
        $rows   = (new Coupon())->get_admin_list($page, $per);

        $this->page_header(
            __('Coupons', 'whois-crm'),
            '',
            [['label' => __('+ New Coupon', 'whois-crm'), 'url' => add_query_arg('view', 'add', admin_url('admin.php?page=whoiscrm-coupons'))]]
        );

        $this->show_notices('whoiscrm_coupons');
        $this->render_template('coupons/list', [
            'rows'  => $rows,
            'nonce' => wp_create_nonce('whoiscrm_coupon_action'),
        ]);
        $this->page_footer();
    }

    private function display_form(int $coupon_id = 0): void
    {
        $coupon = $coupon_id ? (new Coupon())->find($coupon_id) : null;

        $this->page_header(
            $coupon_id ? __('Edit Coupon', 'whois-crm') : __('New Coupon', 'whois-crm'),
            __('Coupons', 'whois-crm')
        );

        $this->render_template('coupons/form', [
            'coupon' => $coupon,
            'nonce'  => wp_create_nonce('whoiscrm_coupon_action'),
        ]);
        $this->page_footer();
    }

    private function handle_action(): void
    {
        check_admin_referer('whoiscrm_coupon_action');

        if (!current_user_can(static::$required_cap)) {
            wp_die(__('Unauthorized.', 'whois-crm'));
        }

        $action    = sanitize_key($_POST['whoiscrm_action']);
        $coupon_id = (int) ($_POST['coupon_id'] ?? 0);
        $model     = new Coupon();

        switch ($action) {
            case 'save':
                $packages = isset($_POST['applicable_packages']) ? array_map('intval', (array) $_POST['applicable_packages']) : [];

                $data = [
                    'code'                  => strtoupper(sanitize_text_field($_POST['code'] ?? '')),
                    'type'                  => sanitize_key($_POST['type'] ?? 'percentage'),
                    'value'                 => (float) ($_POST['value'] ?? 0),
                    'max_uses'              => !empty($_POST['max_uses']) ? (int) $_POST['max_uses'] : null,
                    'max_uses_per_customer' => !empty($_POST['max_uses_per_customer']) ? (int) $_POST['max_uses_per_customer'] : null,
                    'min_amount'            => !empty($_POST['min_amount']) ? (float) $_POST['min_amount'] : null,
                    'applicable_packages'   => !empty($packages) ? wp_json_encode($packages) : null,
                    'starts_at'             => sanitize_text_field($_POST['starts_at'] ?? '') ?: null,
                    'expires_at'            => sanitize_text_field($_POST['expires_at'] ?? '') ?: null,
                    'is_active'             => !empty($_POST['is_active']) ? 1 : 0,
                    'description'           => sanitize_textarea_field($_POST['description'] ?? ''),
                ];

                if ($coupon_id > 0) {
                    $model->update($coupon_id, $data);
                    self::set_notice('whoiscrm_coupons', __('Coupon updated.', 'whois-crm'));
                } else {
                    $model->insert($data);
                    self::set_notice('whoiscrm_coupons', __('Coupon created.', 'whois-crm'));
                }
                break;

            case 'delete':
                $model->delete_by_id($coupon_id);
                self::set_notice('whoiscrm_coupons', __('Coupon deleted.', 'whois-crm'));
                break;

            case 'toggle':
                $coupon = $model->find($coupon_id);
                if ($coupon) {
                    $model->update($coupon_id, ['is_active' => $coupon->is_active ? 0 : 1]);
                    self::set_notice('whoiscrm_coupons', __('Coupon status updated.', 'whois-crm'));
                }
                break;
        }

        wp_safe_redirect(admin_url('admin.php?page=whoiscrm-coupons'));
        exit;
    }
}
