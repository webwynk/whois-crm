<?php
/**
 * Template: Customer Portal Invoices List
 *
 * Variables:
 *  $invoices  array  List of Invoice objects for this customer
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

$currency_symbol = '$';
?>

<div style="margin-bottom: var(--space-6);">
  <h3 style="margin: 0 0 var(--space-1) 0; font-size: var(--text-h2); font-weight: 700; color: var(--color-black);">
    <?php esc_html_e('Billing Invoices', 'whois-crm'); ?>
  </h3>
  <p style="margin: 0; color: var(--color-text-secondary); font-size: 0.9375rem;">
    <?php esc_html_e('Access and download PDF copies of all billing invoices generated for your account.', 'whois-crm'); ?>
  </p>
</div>

<div class="whoiscrm-table-wrapper">
  <table class="whoiscrm-table">
    <thead>
      <tr>
        <th><?php esc_html_e('Invoice #', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Date', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Subtotal', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Discount', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Tax', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Total Charged', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Status', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Action', 'whois-crm'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($invoices)) : ?>
        <tr>
          <td colspan="8" style="text-align: center; padding: var(--space-10); color: var(--color-text-muted);">
            <?php esc_html_e('No invoices recorded for your account yet.', 'whois-crm'); ?>
          </td>
        </tr>
      <?php else : ?>
        <?php foreach ($invoices as $invoice) :
          $status_class = 'whoiscrm-badge--muted';
          if ($invoice->status === 'paid') {
              $status_class = 'whoiscrm-badge--success';
          } elseif ($invoice->status === 'unpaid') {
              $status_class = 'whoiscrm-badge--warning';
          } elseif ($invoice->status === 'void') {
              $status_class = 'whoiscrm-badge--danger';
          }
          ?>
          <tr>
            <td>
              <strong><?php echo esc_html($invoice->invoice_number); ?></strong>
            </td>
            <td>
              <span><?php echo esc_html(gmdate('Y-m-d', strtotime($invoice->invoice_date))); ?></span>
            </td>
            <td>
              <span><?php echo esc_html($currency_symbol . number_format((float)$invoice->subtotal, 2)); ?></span>
            </td>
            <td>
              <span><?php echo (float)$invoice->discount > 0 ? esc_html('-' . $currency_symbol . number_format((float)$invoice->discount, 2)) : '—'; ?></span>
            </td>
            <td>
              <span><?php echo (float)$invoice->tax_amount > 0 ? esc_html($currency_symbol . number_format((float)$invoice->tax_amount, 2)) : '—'; ?></span>
            </td>
            <td style="font-weight: 600;">
              <span><?php echo esc_html($currency_symbol . number_format((float)$invoice->total, 2)); ?></span>
            </td>
            <td>
              <span class="whoiscrm-badge <?php echo esc_attr($status_class); ?>">
                <?php echo esc_html($invoice->status); ?>
              </span>
            </td>
            <td>
              <?php
              $download_url = (new \WhoisCRM\Database\Models\Invoice())->get_download_url($invoice);
              if ($download_url) : ?>
                <a href="<?php echo esc_url($download_url); ?>" class="whoiscrm-btn whoiscrm-btn--ghost whoiscrm-btn--sm" style="font-size: 0.8125rem; height: 28px;">
                  📄 <?php esc_html_e('Download PDF', 'whois-crm'); ?>
                </a>
              <?php else : ?>
                <span style="font-size: 0.8125rem; color: var(--color-text-muted); font-style: italic;">
                  <?php esc_html_e('Generating...', 'whois-crm'); ?>
                </span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>
