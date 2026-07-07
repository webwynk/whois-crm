<?php
/**
 * Template: Admin Packages List
 *
 * Variables:
 *  $rows   array  — package rows with pricing info
 *  $nonce  string — admin nonce
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
?>

<div class="whoiscrm-table-wrapper">
  <div class="whoiscrm-table-toolbar">
    <span style="font-size:.875rem; color:var(--color-text-secondary);">
      <?php printf(esc_html(_n('%d Package', '%d Packages', count($rows), 'whois-crm')), count($rows)); ?>
    </span>
  </div>

  <table class="whoiscrm-table">
    <thead>
      <tr>
        <th><?php esc_html_e('Name', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Type', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Monthly', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Annual', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Stripe', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Status', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Actions', 'whois-crm'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)) : ?>
        <tr>
          <td colspan="7" style="text-align:center; padding:var(--space-10); color:var(--color-text-muted);">
            <?php esc_html_e('No packages yet. Click "+ New Package" to create one.', 'whois-crm'); ?>
          </td>
        </tr>
      <?php else : ?>
        <?php foreach ($rows as $row) : ?>
        <tr>
          <td>
            <a href="<?php echo esc_url(add_query_arg(['page'=>'whoiscrm-packages','edit'=>$row->id], admin_url('admin.php'))); ?>" style="font-weight:600; color:var(--color-text-primary); text-decoration:none;">
              <?php echo esc_html($row->name); ?>
            </a>
            <br><small style="color:var(--color-text-muted);"><?php echo esc_html($row->slug); ?></small>
          </td>
          <td>
            <span class="whoiscrm-badge <?php echo $row->type === 'global_service' ? 'whoiscrm-badge--info' : 'whoiscrm-badge--warning'; ?>">
              <?php echo esc_html($row->type === 'global_service' ? 'Global' : 'Country'); ?>
            </span>
          </td>
          <td><?php echo isset($row->monthly_price) ? esc_html('$' . number_format((float)$row->monthly_price, 2)) : '—'; ?></td>
          <td><?php echo isset($row->annual_price)  ? esc_html('$' . number_format((float)$row->annual_price,  2)) : '—'; ?></td>
          <td>
            <?php if (!empty($row->stripe_product_id)) : ?>
              <span class="whoiscrm-badge whoiscrm-badge--success" title="<?php echo esc_attr($row->stripe_product_id); ?>">✓ Synced</span>
            <?php else : ?>
              <span class="whoiscrm-badge whoiscrm-badge--muted">Not synced</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($row->is_active) : ?>
              <span class="whoiscrm-badge whoiscrm-badge--success"><?php esc_html_e('Active', 'whois-crm'); ?></span>
            <?php else : ?>
              <span class="whoiscrm-badge whoiscrm-badge--muted"><?php esc_html_e('Inactive', 'whois-crm'); ?></span>
            <?php endif; ?>
          </td>
          <td>
            <a href="<?php echo esc_url(add_query_arg(['page'=>'whoiscrm-packages','edit'=>$row->id], admin_url('admin.php'))); ?>" class="whoiscrm-btn whoiscrm-btn--ghost whoiscrm-btn--sm">
              <?php esc_html_e('Edit', 'whois-crm'); ?>
            </a>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;">
              <?php wp_nonce_field('whoiscrm_package_delete'); ?>
              <input type="hidden" name="action" value="whoiscrm_delete_package">
              <input type="hidden" name="package_id" value="<?php echo (int) $row->id; ?>">
              <button type="submit" class="whoiscrm-btn whoiscrm-btn--danger whoiscrm-btn--sm" data-confirm="<?php esc_attr_e('Delete this package? This cannot be undone.', 'whois-crm'); ?>">
                <?php esc_html_e('Delete', 'whois-crm'); ?>
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
