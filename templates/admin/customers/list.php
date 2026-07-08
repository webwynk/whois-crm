<?php
/**
 * Template: Admin Customers List
 *
 * Variables:
 *  $rows          array   Customer rows
 *  $total         int     Total records
 *  $per_page      int     Rows per page
 *  $current_page  int     Current page
 *  $search        string  Search query
 *  $status        string  Status filter
 *  $pagination    string  Pagination HTML
 *  $nonce         string  Security nonce
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
?>

<!-- Filters Bar -->
<div class="whoiscrm-filters" style="display:flex; gap:var(--space-3); margin-bottom:var(--space-5); flex-wrap:wrap; align-items:center;">
  <form method="get" style="display:contents;">
    <input type="hidden" name="page" value="whoiscrm-customers">
    <input
      type="search"
      name="s"
      class="whoiscrm-input"
      placeholder="<?php esc_attr_e('Search name or email…', 'whois-crm'); ?>"
      value="<?php echo esc_attr($search); ?>"
      style="width:240px;"
    >
    <select name="status" class="whoiscrm-select" style="width:160px;">
      <option value=""><?php esc_html_e('All Statuses', 'whois-crm'); ?></option>
      <option value="active"   <?php selected($status, 'active'); ?>><?php esc_html_e('Active',   'whois-crm'); ?></option>
      <option value="blocked"  <?php selected($status, 'blocked'); ?>><?php esc_html_e('Blocked',  'whois-crm'); ?></option>
      <option value="inactive" <?php selected($status, 'inactive'); ?>><?php esc_html_e('Inactive', 'whois-crm'); ?></option>
    </select>
    <button type="submit" class="whoiscrm-btn whoiscrm-btn--secondary whoiscrm-btn--md"><?php esc_html_e('Filter', 'whois-crm'); ?></button>
    <?php if ($search || $status) : ?>
      <a href="<?php echo esc_url(admin_url('admin.php?page=whoiscrm-customers')); ?>" class="whoiscrm-btn whoiscrm-btn--ghost whoiscrm-btn--md"><?php esc_html_e('Clear', 'whois-crm'); ?></a>
    <?php endif; ?>
  </form>
  <span style="margin-left:auto; font-size:0.875rem; color:var(--color-text-secondary);">
    <?php printf(esc_html(_n('%d Customer', '%d Customers', $total, 'whois-crm')), $total); ?>
  </span>
</div>

<!-- Table -->
<div class="whoiscrm-table-wrapper">
  <table class="whoiscrm-table">
    <thead>
      <tr>
        <th><?php esc_html_e('Customer', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Email', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Company', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Status', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Registered', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Actions', 'whois-crm'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)) : ?>
        <tr>
          <td colspan="6" style="text-align:center; padding:var(--space-10); color:var(--color-text-muted);">
            <?php esc_html_e('No customers found.', 'whois-crm'); ?>
          </td>
        </tr>
      <?php else : ?>
        <?php foreach ($rows as $row) : ?>
          <tr>
            <td>
              <a
                href="<?php echo esc_url(add_query_arg(['page' => 'whoiscrm-customers', 'view' => $row->id], admin_url('admin.php'))); ?>"
                style="font-weight:600; color:var(--color-text-primary); text-decoration:none;"
              >
                <?php echo esc_html(($row->first_name ?? '') . ' ' . ($row->last_name ?? '')); ?>
              </a>
            </td>
            <td><?php echo esc_html($row->email ?? '—'); ?></td>
            <td><?php echo esc_html($row->company ?? '—'); ?></td>
            <td>
              <?php
              $is_active = !empty($row->is_active);
              $badge_class = $is_active ? 'whoiscrm-badge--success' : 'whoiscrm-badge--danger';
              $badge_label = $is_active ? __('Active', 'whois-crm') : __('Blocked', 'whois-crm');
              ?>
              <span class="whoiscrm-badge <?php echo esc_attr($badge_class); ?>">
                <?php echo esc_html($badge_label); ?>
              </span>
            </td>
            <td style="font-size:0.875rem; color:var(--color-text-secondary);">
              <?php echo $row->created_at ? esc_html(gmdate('Y-m-d', strtotime($row->created_at))) : '—'; ?>
            </td>
            <td>
              <div style="display:flex; gap:var(--space-2);">
                <a
                  href="<?php echo esc_url(add_query_arg(['page' => 'whoiscrm-customers', 'view' => $row->id], admin_url('admin.php'))); ?>"
                  class="whoiscrm-btn whoiscrm-btn--ghost whoiscrm-btn--sm"
                >
                  <?php esc_html_e('View', 'whois-crm'); ?>
                </a>

                <form method="post" style="margin:0;">
                  <?php wp_nonce_field('whoiscrm_customer_action'); ?>
                  <input type="hidden" name="whoiscrm_action" value="<?php echo $is_active ? 'block' : 'unblock'; ?>">
                  <input type="hidden" name="customer_id" value="<?php echo (int) $row->id; ?>">
                  <button
                    type="submit"
                    class="whoiscrm-btn whoiscrm-btn--ghost whoiscrm-btn--sm"
                    style="color:<?php echo $is_active ? 'var(--color-danger)' : 'var(--color-success)'; ?>;"
                  >
                    <?php echo $is_active ? esc_html__('Block', 'whois-crm') : esc_html__('Unblock', 'whois-crm'); ?>
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

<?php if ($pagination) : ?>
  <?php echo $pagination; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php endif; ?>
