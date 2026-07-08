<?php
/**
 * Template: Admin Subscriptions List
 *
 * Variables:
 *  $rows           array   Subscription rows (joined with customer & package data)
 *  $total          int     Total records
 *  $per_page       int     Rows per page
 *  $current_page   int     Current page number
 *  $status_filter  string  Active status filter value
 *  $pagination     string  Pagination HTML
 *  $nonce          string  Security nonce
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

$statuses = [
    ''          => __('All Statuses',  'whois-crm'),
    'active'    => __('Active',        'whois-crm'),
    'trialing'  => __('Trialing',      'whois-crm'),
    'past_due'  => __('Past Due',      'whois-crm'),
    'cancelled' => __('Cancelled',     'whois-crm'),
    'expired'   => __('Expired',       'whois-crm'),
];

$status_badge_map = [
    'active'    => 'whoiscrm-badge--success',
    'trialing'  => 'whoiscrm-badge--info',
    'past_due'  => 'whoiscrm-badge--warning',
    'cancelled' => 'whoiscrm-badge--danger',
    'expired'   => 'whoiscrm-badge--ghost',
];
?>

<!-- Filters -->
<div style="display:flex; gap:var(--space-3); margin-bottom:var(--space-5); flex-wrap:wrap; align-items:center;">
  <form method="get" style="display:contents;">
    <input type="hidden" name="page" value="whoiscrm-subscriptions">
    <select name="status" class="whoiscrm-select" style="width:180px;">
      <?php foreach ($statuses as $val => $label) : ?>
        <option value="<?php echo esc_attr($val); ?>" <?php selected($status_filter, $val); ?>>
          <?php echo esc_html($label); ?>
        </option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="whoiscrm-btn whoiscrm-btn--secondary whoiscrm-btn--md">
      <?php esc_html_e('Filter', 'whois-crm'); ?>
    </button>
  </form>
  <span style="margin-left:auto; font-size:0.875rem; color:var(--color-text-secondary);">
    <?php printf(esc_html(_n('%d Subscription', '%d Subscriptions', $total, 'whois-crm')), $total); ?>
  </span>
</div>

<!-- Table -->
<div class="whoiscrm-table-wrapper">
  <table class="whoiscrm-table">
    <thead>
      <tr>
        <th><?php esc_html_e('ID', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Customer', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Package', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Billing', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Status', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Renews / Ends', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Actions', 'whois-crm'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)) : ?>
        <tr>
          <td colspan="7" style="text-align:center; padding:var(--space-10); color:var(--color-text-muted);">
            <?php esc_html_e('No subscriptions found.', 'whois-crm'); ?>
          </td>
        </tr>
      <?php else : ?>
        <?php foreach ($rows as $row) : ?>
          <?php
          $badge_class = $status_badge_map[$row->status ?? ''] ?? 'whoiscrm-badge--ghost';
          $is_active   = $row->status === 'active';
          ?>
          <tr>
            <td style="font-size:0.8125rem; color:var(--color-text-muted);">#<?php echo (int) $row->id; ?></td>
            <td>
              <a
                href="<?php echo esc_url(add_query_arg(['page' => 'whoiscrm-customers', 'view' => $row->customer_id], admin_url('admin.php'))); ?>"
                style="font-weight:600; color:var(--color-text-primary); text-decoration:none;"
              >
                <?php echo esc_html(trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? '')) ?: ($row->email ?? '—')); ?>
              </a>
              <div style="font-size:0.8125rem; color:var(--color-text-secondary);"><?php echo esc_html($row->email ?? ''); ?></div>
            </td>
            <td style="font-weight:500;"><?php echo esc_html($row->package_name ?? '—'); ?></td>
            <td style="text-transform:capitalize;"><?php echo esc_html($row->billing_cycle ?? '—'); ?></td>
            <td>
              <span class="whoiscrm-badge <?php echo esc_attr($badge_class); ?>">
                <?php echo esc_html(ucfirst(str_replace('_', ' ', $row->status ?? '—'))); ?>
              </span>
            </td>
            <td style="font-size:0.8125rem; color:var(--color-text-secondary);">
              <?php echo $row->current_period_end ? esc_html(gmdate('Y-m-d', strtotime($row->current_period_end))) : '—'; ?>
            </td>
            <td>
              <form method="post" style="margin:0;">
                <?php wp_nonce_field('whoiscrm_subscription_action'); ?>
                <input type="hidden" name="subscription_id" value="<?php echo (int) $row->id; ?>">
                <?php if ($is_active) : ?>
                  <input type="hidden" name="whoiscrm_action" value="cancel">
                  <button
                    type="submit"
                    class="whoiscrm-btn whoiscrm-btn--ghost whoiscrm-btn--sm"
                    style="color:var(--color-danger);"
                    onclick="return confirm('<?php esc_attr_e('Cancel this subscription?', 'whois-crm'); ?>');"
                  >
                    <?php esc_html_e('Cancel', 'whois-crm'); ?>
                  </button>
                <?php elseif (in_array($row->status, ['cancelled', 'expired'], true)) : ?>
                  <input type="hidden" name="whoiscrm_action" value="activate">
                  <button type="submit" class="whoiscrm-btn whoiscrm-btn--ghost whoiscrm-btn--sm" style="color:var(--color-success);">
                    <?php esc_html_e('Re-activate', 'whois-crm'); ?>
                  </button>
                <?php else : ?>
                  <span style="color:var(--color-text-muted); font-size:0.8125rem;">—</span>
                <?php endif; ?>
              </form>
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
