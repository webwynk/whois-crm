<?php

declare(strict_types=1);

namespace WhoisCRM\Admin;

use WhoisCRM\Database\Models\Coupon;

/**
 * Handles coupon CRUD form submissions posted to admin-post.php.
 *
 * The coupon templates post to admin-post.php with action=whoiscrm_coupon_action.
 * WordPress requires an explicit add_action('admin_post_{action}', ...) handler.
 */
class CouponManager
{
    public function __construct()
    {
        add_action('admin_post_whoiscrm_coupon_action', [$this, 'handle']);
    }

    /**
     * Process save / delete / toggle actions for coupons.
     */
    public function handle(): void
    {
        check_admin_referer('whoiscrm_coupon_action');

        if (!current_user_can('whoiscrm_manage_coupons')) {
            wp_die(__('Unauthorized.', 'whois-crm'));
        }

        $action    = sanitize_key($_POST['whoiscrm_action'] ?? '');
        $coupon_id = (int) ($_POST['coupon_id'] ?? 0);
        $model     = new Coupon();

        switch ($action) {
            case 'save':
                $packages = isset($_POST['applicable_packages'])
                    ? array_map('intval', (array) $_POST['applicable_packages'])
                    : [];

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
                    Pages\BasePage::set_notice('whoiscrm_coupons', __('Coupon updated.', 'whois-crm'));
                } else {
                    $model->insert($data);
                    Pages\BasePage::set_notice('whoiscrm_coupons', __('Coupon created.', 'whois-crm'));
                }
                break;

            case 'delete':
                $model->delete_by_id($coupon_id);
                Pages\BasePage::set_notice('whoiscrm_coupons', __('Coupon deleted.', 'whois-crm'));
                break;

            case 'toggle':
                $coupon = $model->find($coupon_id);
                if ($coupon) {
                    $model->update($coupon_id, ['is_active' => $coupon->is_active ? 0 : 1]);
                    Pages\BasePage::set_notice('whoiscrm_coupons', __('Coupon status updated.', 'whois-crm'));
                }
                break;
        }

        wp_safe_redirect(admin_url('admin.php?page=whoiscrm-coupons'));
        exit;
    }
}
