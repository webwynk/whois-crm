<?php
/**
 * Template: Admin Payments List
 *
 * Variables:
 *  $rows           array   Payment rows (joined with customer data)
 *  $total          int     Total records
 *  $per_page       int     Rows per page
 *  $current_page   int     Current page number
 *  $status_filter  string  Active status filter
 *  $from           string  Date-from filter
 *  $to             string  Date-to filter
 *  $revenue        object  Revenue summary: total_revenue, transaction_count, tax_collected
 *  $pagination     string  Pagination HTML
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

$status_badge_map = [
    'succeeded'         => 'whoiscrm-badge--success',
    'pending'           => 'whoiscrm-badge--warning',
    'failed'            => 'whoiscrm-badge--danger',
    'refunded'          => 'whoiscrm-badge--ghost',
    'partially_refunded'=> 'whoiscrm-badge--warning',
];
?>

<!-- Revenue Summary Cards -->
<div style="display:grid; grid-template-columns:repeat(3,1fr); gap:var(--space-4); margin-bottom:var(--space-5);">
  <div class="whoiscrm-card">
    <div class="whoiscrm-card__body" style="text-align:center; padding:var(--space-4);">
      <div style="font-size:1.75rem; font-weight:700; color:var(--color-text-primary);">
        $<?php echo esc_html(number_format((float)($revenue->total_revenue ?? 0), 2)); ?>
      </div>
      <div style="font-size:0.8125rem; color:var(--color-text-secondary); margin-top:var(--space-1);">
        <?php esc_html_e('Total Revenue', 'whois-crm'); ?>
      </div>
    </div>
  </div>
  <div class="whoiscrm-card">
    <div class="whoiscrm-card__body" style="text-align:center; padding:var(--space-4);">
      <div style="font-size:1.75rem; font-weight:700; color:var(--color-text-primary);">
        <?php echo (int)($revenue->transaction_count ?? 0); ?>
      </div>
      <div style="font-size:0.8125rem; color:var(--color-text-secondary); margin-top:var(--space-1);">
        <?php esc_html_e('Transactions', 'whois-crm'); ?>
      </div>
    </div>
  </div>
  <div class="whoiscrm-card">
    <div class="whoiscrm-card__body" style="text-align:center; padding:var(--space-4);">
      <div style="font-size:1.75rem; font-weight:700; color:var(--color-text-primary);">
        $<?php echo esc_html(number_format((float)($revenue->tax_collected ?? 0), 2)); ?>
      </div>
      <div style="font-size:0.8125rem; color:var(--color-text-secondary); margin-top:var(--space-1);">
        <?php esc_html_e('Tax Collected', 'whois-crm'); ?>
      </div>
    </div>
  </div>
</div>

<!-- Filters -->
<div style="display:flex; gap:var(--space-3); margin-bottom:var(--space-5); flex-wrap:wrap; align-items:center;">
  <form method="get" style="display:contents;">
    <input type="hidden" name="page" value="whoiscrm-payments">
    <select name="status" class="whoiscrm-select" style="width:180px;">
      <option value=""><?php esc_html_e('All Statuses', 'whois-crm'); ?></option>
      <?php foreach (array_keys($status_badge_map) as $s) : ?>
        <option value="<?php echo esc_attr($s); ?>" <?php selected($status_filter, $s); ?>>
          <?php echo esc_html(ucfirst(str_replace('_', ' ', $s))); ?>
        </option>
      <?php endforeach; ?>
    </select>
    <input type="date" name="from" class="whoiscrm-input" style="width:150px;" value="<?php echo esc_attr($from); ?>" placeholder="<?php esc_attr_e('From', 'whois-crm'); ?>">
    <input type="date" name="to"   class="whoiscrm-input" style="width:150px;" value="<?php echo esc_attr($to); ?>"   placeholder="<?php esc_attr_e('To', 'whois-crm'); ?>">
    <button type="submit" class="whoiscrm-btn whoiscrm-btn--secondary whoiscrm-btn--md"><?php esc_html_e('Filter', 'whois-crm'); ?></button>
    <?php if ($status_filter || $from || $to) : ?>
      <a href="<?php echo esc_url(admin_url('admin.php?page=whoiscrm-payments')); ?>" class="whoiscrm-btn whoiscrm-btn--ghost whoiscrm-btn--md"><?php esc_html_e('Clear', 'whois-crm'); ?></a>
    <?php endif; ?>
  </form>
  <span style="margin-left:auto; font-size:0.875rem; color:var(--color-text-secondary);">
    <?php printf(esc_html(_n('%d Payment', '%d Payments', $total, 'whois-crm')), $total); ?>
  </span>
</div>

<!-- Table -->
<div class="whoiscrm-table-wrapper">
  <table class="whoiscrm-table">
    <thead>
      <tr>
        <th><?php esc_html_e('ID', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Customer', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Package / Period', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Amount', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Tax', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Coupon', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Status', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Date', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Invoice', 'whois-crm'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)) : ?>
        <tr>
          <td colspan="9" style="text-align:center; padding:var(--space-10); color:var(--color-text-muted);">
            <?php esc_html_e('No payments found.', 'whois-crm'); ?>
          </td>
        </tr>
      <?php else : ?>
        <?php foreach ($rows as $row) : ?>
          <?php $badge_class = $status_badge_map[$row->status ?? ''] ?? 'whoiscrm-badge--ghost'; ?>
          <tr>
            <td style="font-size:0.8125rem; color:var(--color-text-muted);">#<?php echo (int) $row->id; ?></td>
            <td>
              <a
                href="<?php echo esc_url(add_query_arg(['page' => 'whoiscrm-customers', 'view' => $row->customer_id], admin_url('admin.php'))); ?>"
                style="font-weight:600; color:var(--color-text-primary); text-decoration:none;"
              >
                <?php echo esc_html(trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? '')) ?: ($row->email ?? '—')); ?>
              </a>
            </td>
            <td>
              <div style="font-weight:500; font-size:0.875rem;"><?php echo esc_html($row->package_name ?? '—'); ?></div>
              <div style="font-size:0.8125rem; color:var(--color-text-secondary); text-transform:capitalize;"><?php echo esc_html($row->billing_cycle ?? ''); ?></div>
            </td>
            <td style="font-weight:700;">$<?php echo esc_html(number_format((float)($row->total_amount ?? 0), 2)); ?></td>
            <td style="font-size:0.875rem;">$<?php echo esc_html(number_format((float)($row->tax_amount ?? 0), 2)); ?></td>
            <td style="font-size:0.8125rem; color:var(--color-text-secondary);">
              <?php if (!empty($row->coupon_code)) : ?>
                <code><?php echo esc_html($row->coupon_code); ?></code>
                <div style="color:var(--color-success);">-$<?php echo esc_html(number_format((float)($row->discount_amount ?? 0), 2)); ?></div>
              <?php else : ?>
                —
              <?php endif; ?>
            </td>
            <td>
              <span class="whoiscrm-badge <?php echo esc_attr($badge_class); ?>">
                <?php echo esc_html(ucfirst(str_replace('_', ' ', $row->status ?? '—'))); ?>
              </span>
            </td>
            <td style="font-size:0.8125rem; color:var(--color-text-secondary);">
              <?php echo $row->paid_at ? esc_html(gmdate('Y-m-d H:i', strtotime($row->paid_at))) : '—'; ?>
            </td>
            <td>
              <?php if (!empty($row->invoice_number)) : ?>
                <a
                  href="<?php echo esc_url(add_query_arg(['page' => 'whoiscrm-invoices', 'view' => $row->id], admin_url('admin.php'))); ?>"
                  class="whoiscrm-btn whoiscrm-btn--ghost whoiscrm-btn--sm"
                >
                  <?php echo esc_html($row->invoice_number); ?>
                </a>
              <?php else : ?>
                <span style="color:var(--color-text-muted);">—</span>
              <?php endif; ?>
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
