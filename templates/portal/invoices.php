<?php
/**
 * Template: Customer Portal Invoices Tab
 *
 * Variables:
 *  $invoices  array List of invoice objects
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
?>

<div class="whoiscrm-portal-greeting">
  <h3><?php esc_html_e('Billing & Invoices', 'whois-crm'); ?></h3>
  <p><?php esc_html_e('View your payment receipts and download PDF invoices for tax reporting.', 'whois-crm'); ?></p>
</div>

<div class="whoiscrm-table-wrapper">
  <div class="whoiscrm-table-responsive">
    <table class="whoiscrm-table">
      <thead>
        <tr>
          <th><?php esc_html_e('Invoice #', 'whois-crm'); ?></th>
          <th><?php esc_html_e('Date', 'whois-crm'); ?></th>
          <th><?php esc_html_e('Amount', 'whois-crm'); ?></th>
          <th><?php esc_html_e('Status', 'whois-crm'); ?></th>
          <th style="text-align: right;"><?php esc_html_e('Action', 'whois-crm'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($invoices)) : ?>
          <tr>
            <td colspan="5" style="text-align: center; padding: 48px 16px; color: var(--color-text-muted);">
              <?php esc_html_e('No billing invoices available yet.', 'whois-crm'); ?>
            </td>
          </tr>
        <?php else : ?>
          <?php foreach ($invoices as $inv) :
            $is_paid = strtolower($inv->status ?? '') === 'paid';
            $badge = $is_paid ? 'whoiscrm-badge--success' : 'whoiscrm-badge--warning';
          ?>
            <tr>
              <td>
                <strong style="color: var(--color-text-primary); font-size: 0.875rem;"><?php echo esc_html($inv->invoice_number); ?></strong>
              </td>
              <td style="font-size: 0.8125rem; color: var(--color-text-muted); white-space: nowrap;">
                <?php echo esc_html($inv->created_at); ?>
              </td>
              <td>
                <strong style="color: var(--color-text-primary); font-size: 0.875rem;">$<?php echo esc_html(number_format((float)$inv->amount, 2)); ?></strong>
              </td>
              <td>
                <span class="whoiscrm-badge <?php echo esc_attr($badge); ?>"><?php echo esc_html($inv->status); ?></span>
              </td>
              <td style="text-align: right; white-space: nowrap;">
                <?php if (!empty($inv->pdf_url)) : ?>
                  <a href="<?php echo esc_url($inv->pdf_url); ?>" class="whoiscrm-btn whoiscrm-btn--ghost whoiscrm-btn--sm" target="_blank" rel="noopener">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                    <span><?php esc_html_e('PDF Invoice', 'whois-crm'); ?></span>
                  </a>
                <?php else : ?>
                  <span style="font-size: 0.75rem; color: var(--color-text-muted);"><?php esc_html_e('N/A', 'whois-crm'); ?></span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
