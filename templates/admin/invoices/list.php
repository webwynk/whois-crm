<?php
/**
 * Template: Admin Invoices List
 *
 * Variables:
 *  $rows          array   Invoice rows
 *  $total         int     Total count
 *  $per_page      int     Rows per page
 *  $current_page  int     Current page
 *  $from          string  From date filter
 *  $to            string  To date filter
 *  $pagination    string  HTML pagination
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
?>

<!-- Filter form -->
<div class="whoiscrm-card" style="margin-bottom: var(--space-6);">
  <div class="whoiscrm-card__body" style="padding: var(--space-4);">
    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" style="display: flex; flex-wrap: wrap; gap: var(--space-3); align-items: flex-end;">
      <input type="hidden" name="page" value="whoiscrm-invoices">

      <div class="whoiscrm-form-group" style="margin: 0; min-width: 150px;">
        <label class="whoiscrm-form-label" style="font-size: 0.75rem; margin-bottom: 4px;"><?php esc_html_e('From Date', 'whois-crm'); ?></label>
        <input type="date" name="from" class="whoiscrm-input" style="height: 36px;" value="<?php echo esc_attr($from); ?>">
      </div>

      <div class="whoiscrm-form-group" style="margin: 0; min-width: 150px;">
        <label class="whoiscrm-form-label" style="font-size: 0.75rem; margin-bottom: 4px;"><?php esc_html_e('To Date', 'whois-crm'); ?></label>
        <input type="date" name="to" class="whoiscrm-input" style="height: 36px;" value="<?php echo esc_attr($to); ?>">
      </div>

      <div style="display: flex; gap: var(--space-2);">
        <button type="submit" class="whoiscrm-btn whoiscrm-btn--primary whoiscrm-btn--sm" style="height: 36px;">
          🔍 <?php esc_html_e('Filter', 'whois-crm'); ?>
        </button>
        <?php if ($from || $to) : ?>
          <a href="<?php echo esc_url(admin_url('admin.php?page=whoiscrm-invoices')); ?>" class="whoiscrm-btn whoiscrm-btn--ghost whoiscrm-btn--sm" style="height: 36px; line-height: 34px;">
            <?php esc_html_e('Clear', 'whois-crm'); ?>
          </a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<div class="whoiscrm-table-wrapper">
  <div class="whoiscrm-table-toolbar">
    <span style="font-size: 0.875rem; color: var(--color-text-secondary);">
      <?php printf(esc_html(_n('%d Invoice found', '%d Invoices found', $total, 'whois-crm')), $total); ?>
    </span>
  </div>

  <table class="whoiscrm-table">
    <thead>
      <tr>
        <th><?php esc_html_e('Invoice #', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Customer / Billing info', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Item Description', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Billing Cycle', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Subtotal', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Tax / Discount', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Total Amount', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Actions', 'whois-crm'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)) : ?>
        <tr>
          <td colspan="8" style="text-align: center; padding: var(--space-10); color: var(--color-text-muted);">
            <?php esc_html_e('No billing invoices found matching your filters.', 'whois-crm'); ?>
          </td>
        </tr>
      <?php else : ?>
        <?php foreach ($rows as $row) : ?>
          <tr>
            <td>
              <strong style="color: var(--color-text-primary);"><?php echo esc_html($row->invoice_number); ?></strong>
              <div style="font-size: 0.75rem; color: var(--color-text-muted); margin-top: 2px;">
                <?php echo esc_html(gmdate('Y-m-d', strtotime($row->invoice_date))); ?>
              </div>
            </td>
            <td>
              <div style="font-weight: 600; color: var(--color-text-primary);"><?php echo esc_html($row->billing_name); ?></div>
              <small style="color: var(--color-text-secondary);"><?php echo esc_html($row->billing_email); ?></small>
              <?php if ($row->billing_company) : ?>
                <div style="font-size: 0.75rem; color: var(--color-text-muted);"><?php echo esc_html($row->billing_company); ?></div>
              <?php endif; ?>
            </td>
            <td>
              <strong><?php echo esc_html($row->line_item_description); ?></strong>
            </td>
            <td>
              <span class="whoiscrm-badge whoiscrm-badge--ghost" style="text-transform: uppercase;">
                <?php echo esc_html($row->billing_cycle ?? 'monthly'); ?>
              </span>
            </td>
            <td>
              <?php echo esc_html($row->currency); ?> $<?php echo esc_html(number_format((float)$row->subtotal, 2)); ?>
            </td>
            <td>
              <?php if ((float)$row->discount_amount > 0) : ?>
                <div style="font-size: 0.8125rem; color: var(--color-danger);">
                  Discount: -$<?php echo esc_html(number_format((float)$row->discount_amount, 2)); ?>
                </div>
              <?php endif; ?>
              <?php if ((float)$row->tax_amount > 0) : ?>
                <div style="font-size: 0.8125rem; color: var(--color-text-secondary);">
                  Tax (<?php echo esc_html(number_format((float)$row->tax_rate, 1)); ?>%): +$<?php echo esc_html(number_format((float)$row->tax_amount, 2)); ?>
                </div>
              <?php endif; ?>
              <?php if ((float)$row->discount_amount == 0 && (float)$row->tax_amount == 0) : ?>
                <span style="color: var(--color-text-muted);">—</span>
              <?php endif; ?>
            </td>
            <td>
              <strong style="color: var(--color-primary); font-size: 0.9375rem;">
                <?php echo esc_html($row->currency); ?> $<?php echo esc_html(number_format((float)$row->total, 2)); ?>
              </strong>
            </td>
            <td>
              <?php if (!empty($row->pdf_path)) : ?>
                <a
                  href="<?php echo esc_url(wp_nonce_url(home_url('/?whoiscrm_action=download_invoice&invoice_id=' . $row->id), 'whoiscrm_invoice_' . $row->id)); ?>"
                  class="whoiscrm-btn whoiscrm-btn--primary whoiscrm-btn--sm"
                  style="height: 30px; font-size: 0.8125rem; line-height: 28px;"
                  target="_blank"
                >
                  📥 <?php esc_html_e('PDF', 'whois-crm'); ?>
                </a>
              <?php else : ?>
                <span style="font-size: 0.75rem; color: var(--color-text-muted);"><?php esc_html_e('No File', 'whois-crm'); ?></span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- Pagination footer -->
  <?php if ($pagination) : ?>
    <?php echo $pagination; // phpcs:ignore WordPress.Security.OutputNotEscaped ?>
  <?php endif; ?>
</div>
