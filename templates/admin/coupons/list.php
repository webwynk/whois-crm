<?php
/**
 * Template: Admin Coupons List
 *
 * Variables:
 *  $rows   array   List of coupon rows
 *  $nonce  string  Security nonce
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
?>

<div class="whoiscrm-table-wrapper">
  <div class="whoiscrm-table-toolbar">
    <span style="font-size: 0.875rem; color: var(--color-text-secondary);">
      <?php printf(esc_html(_n('%d Coupon', '%d Coupons', count($rows), 'whois-crm')), count($rows)); ?>
    </span>
  </div>

  <table class="whoiscrm-table">
    <thead>
      <tr>
        <th><?php esc_html_e('Code', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Description', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Discount Value', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Usage (Total / Per Cust)', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Min Purchase', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Validity', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Status', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Actions', 'whois-crm'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)) : ?>
        <tr>
          <td colspan="8" style="text-align: center; padding: var(--space-10); color: var(--color-text-muted);">
            <?php esc_html_e('No coupons created yet. Click "+ New Coupon" to get started.', 'whois-crm'); ?>
          </td>
        </tr>
      <?php else : ?>
        <?php foreach ($rows as $row) : ?>
          <tr>
            <td>
              <a
                href="<?php echo esc_url(add_query_arg(['page' => 'whoiscrm-coupons', 'edit' => $row->id], admin_url('admin.php'))); ?>"
                style="font-weight: 700; color: var(--color-text-primary); text-decoration: none;"
              >
                <code><?php echo esc_html($row->code); ?></code>
              </a>
            </td>
            <td>
              <?php echo esc_html($row->description ?: '—'); ?>
            </td>
            <td>
              <strong>
                <?php if ($row->type === 'percentage') : ?>
                  <?php echo esc_html($row->value); ?>%
                <?php else : ?>
                  $<?php echo esc_html(number_format((float)$row->value, 2)); ?>
                <?php endif; ?>
              </strong>
            </td>
            <td>
              <span style="font-weight: 500; color: var(--color-text-primary);">
                <?php echo (int) $row->used_count; ?>
              </span>
              <span style="color: var(--color-text-muted);">
                / <?php echo $row->max_uses !== null ? (int) $row->max_uses : '∞'; ?>
              </span>
              <div style="font-size: 0.75rem; color: var(--color-text-secondary); margin-top: 2px;">
                Limit: <?php echo $row->max_uses_per_customer !== null ? (int) $row->max_uses_per_customer : '∞'; ?> per user
              </div>
            </td>
            <td>
              <?php if ($row->min_amount !== null && (float)$row->min_amount > 0) : ?>
                $<?php echo esc_html(number_format((float)$row->min_amount, 2)); ?>
              <?php else : ?>
                <span style="color: var(--color-text-muted);">—</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if (empty($row->starts_at) && empty($row->expires_at)) : ?>
                <span class="whoiscrm-badge whoiscrm-badge--ghost"><?php esc_html_e('Always Valid', 'whois-crm'); ?></span>
              <?php else : ?>
                <div style="font-size: 0.8125rem; color: var(--color-text-primary);">
                  <?php echo $row->starts_at ? esc_html(gmdate('Y-m-d', strtotime($row->starts_at))) : '—'; ?>
                  to
                  <?php echo $row->expires_at ? esc_html(gmdate('Y-m-d', strtotime($row->expires_at))) : '—'; ?>
                </div>
              <?php endif; ?>
            </td>
            <td>
              <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin:0;">
                <input type="hidden" name="action" value="whoiscrm_coupon_action">
                <input type="hidden" name="whoiscrm_action" value="toggle">
                <input type="hidden" name="coupon_id" value="<?php echo (int) $row->id; ?>">
                <?php wp_nonce_field('whoiscrm_coupon_action'); ?>
                
                <button
                  type="submit"
                  class="whoiscrm-btn whoiscrm-btn--sm <?php echo $row->is_active ? 'whoiscrm-btn--ghost' : 'whoiscrm-btn--primary'; ?>"
                  style="padding: 2px 8px; font-size: 0.75rem; height: 26px;"
                >
                  <?php echo $row->is_active ? __('Deactivate', 'whois-crm') : __('Activate', 'whois-crm'); ?>
                </button>
              </form>
            </td>
            <td>
              <div style="display: flex; gap: var(--space-2);">
                <a
                  href="<?php echo esc_url(add_query_arg(['page' => 'whoiscrm-coupons', 'edit' => $row->id], admin_url('admin.php'))); ?>"
                  class="whoiscrm-btn whoiscrm-btn--ghost whoiscrm-btn--sm"
                  style="height: 30px; font-size: 0.8125rem; line-height: 28px;"
                >
                  <?php esc_html_e('Edit', 'whois-crm'); ?>
                </a>
                
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin: 0;" onsubmit="return confirm('<?php esc_attr_e('Are you sure you want to delete this coupon code?', 'whois-crm'); ?>');">
                  <input type="hidden" name="action" value="whoiscrm_coupon_action">
                  <input type="hidden" name="whoiscrm_action" value="delete">
                  <input type="hidden" name="coupon_id" value="<?php echo (int) $row->id; ?>">
                  <?php wp_nonce_field('whoiscrm_coupon_action'); ?>
                  <button type="submit" class="whoiscrm-btn whoiscrm-btn--ghost whoiscrm-btn--sm" style="height: 30px; font-size: 0.8125rem; color: var(--color-danger); line-height: 28px;">
                    <?php esc_html_e('Delete', 'whois-crm'); ?>
                  </button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
