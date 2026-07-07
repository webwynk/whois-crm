<?php
/**
 * Template: PDF Invoice layout (Dompdf compatible)
 *
 * Variables:
 *  $invoice           object  Invoice database row
 *  $payment           object  Payment database row
 *  $package           object  Package database row
 *  $customer          object  Customer database row merged with user data
 *  $company_name      string
 *  $company_address   string
 *  $company_tax_id    string
 *  $support_email     string
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

$currency_symbol = '$';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php printf(esc_html__('Invoice %s', 'whois-crm'), esc_html($invoice->invoice_number)); ?></title>
  <style>
    body {
      font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
      font-size: 14px;
      line-height: 1.5;
      color: #0A0A0B;
      margin: 0;
      padding: 0;
    }
    .invoice-box {
      max-width: 800px;
      margin: auto;
      padding: 30px;
    }
    table {
      width: 100%;
      line-height: inherit;
      text-align: left;
      border-collapse: collapse;
    }
    table td {
      padding: 5px;
      vertical-align: top;
    }
    .top-header td {
      padding-bottom: 20px;
    }
    .top-header .title {
      font-size: 28px;
      line-height: 32px;
      font-weight: bold;
      color: #FF6621;
    }
    .information td {
      padding-bottom: 40px;
    }
    .heading th {
      background: #F8F8FA;
      border-bottom: 2px solid #D0D0DD;
      font-weight: bold;
      padding: 10px;
      text-align: left;
      font-size: 12px;
      text-transform: uppercase;
      color: #5C5C6B;
    }
    .item td {
      border-bottom: 1px solid #E8E8EF;
      padding: 12px 10px;
    }
    .total-section td {
      padding: 6px 10px;
    }
    .total-bold {
      font-weight: bold;
      font-size: 16px;
      color: #FF6621;
    }
    .footer {
      margin-top: 60px;
      text-align: center;
      font-size: 12px;
      color: #9898A8;
      border-top: 1px solid #E8E8EF;
      padding-top: 20px;
    }
  </style>
</head>
<body>
  <div class="invoice-box">
    <table>
      
      <!-- Top header layout -->
      <tr class="top-header">
        <td colspan="2">
          <table>
            <tr>
              <td class="title">
                <?php echo esc_html($company_name); ?>
              </td>
              <td style="text-align: right;">
                <span style="font-size: 20px; font-weight: bold; text-transform: uppercase; color: #5C5C6B;"><?php esc_html_e('Tax Invoice', 'whois-crm'); ?></span><br>
                <strong><?php esc_html_e('Invoice #:', 'whois-crm'); ?></strong> <?php echo esc_html($invoice->invoice_number); ?><br>
                <strong><?php esc_html_e('Date:', 'whois-crm'); ?></strong> <?php echo esc_html(gmdate('Y-m-d', strtotime($invoice->invoice_date))); ?><br>
                <strong><?php esc_html_e('Status:', 'whois-crm'); ?></strong> <span style="text-transform: uppercase; font-weight: bold; color: #14803C;"><?php echo esc_html($invoice->status); ?></span>
              </td>
            </tr>
          </table>
        </td>
      </tr>

      <!-- Address information -->
      <tr class="information">
        <td colspan="2">
          <table>
            <tr>
              <!-- Seller details -->
              <td style="width: 50%;">
                <strong><?php esc_html_e('Issuer:', 'whois-crm'); ?></strong><br>
                <?php echo esc_html($company_name); ?><br>
                <?php echo nl2br(esc_html($company_address)); ?><br>
                <?php if ($company_tax_id) : ?>
                  <strong><?php esc_html_e('Tax ID:', 'whois-crm'); ?></strong> <?php echo esc_html($company_tax_id); ?>
                <?php endif; ?>
              </td>
              
              <!-- Buyer details -->
              <td style="text-align: right; width: 50%;">
                <strong><?php esc_html_e('Bill To:', 'whois-crm'); ?></strong><br>
                <?php echo esc_html($invoice->billing_name); ?><br>
                <?php if ($invoice->billing_company) : ?>
                  <?php echo esc_html($invoice->billing_company); ?><br>
                <?php endif; ?>
                <?php echo nl2br(esc_html($invoice->billing_address)); ?><br>
                <?php if ($invoice->billing_phone) : ?>
                  <strong><?php esc_html_e('Phone:', 'whois-crm'); ?></strong> <?php echo esc_html($invoice->billing_phone); ?><br>
                <?php endif; ?>
                <?php if ($invoice->tax_id) : ?>
                  <strong><?php esc_html_e('GSTIN/VAT:', 'whois-crm'); ?></strong> <?php echo esc_html($invoice->tax_id); ?>
                <?php endif; ?>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>

    <!-- Line items -->
    <table style="margin-bottom: 30px;">
      <thead>
        <tr class="heading">
          <th style="width: 60%;"><?php esc_html_e('Description', 'whois-crm'); ?></th>
          <th style="width: 10%; text-align: center;"><?php esc_html_e('Qty', 'whois-crm'); ?></th>
          <th style="width: 15%; text-align: right;"><?php esc_html_e('Unit Price', 'whois-crm'); ?></th>
          <th style="width: 15%; text-align: right;"><?php esc_html_e('Amount', 'whois-crm'); ?></th>
        </tr>
      </thead>
      <tbody>
        <tr class="item">
          <td>
            <strong><?php echo esc_html($package->name); ?></strong><br>
            <span style="font-size: 12px; color: #5C5C6B;">
              <?php printf(esc_html__('Billing Cycle: %s', 'whois-crm'), esc_html($invoice->billing_cycle ?? 'monthly')); ?>
            </span>
          </td>
          <td style="text-align: center;">1</td>
          <td style="text-align: right;"><?php echo esc_html($currency_symbol . number_format((float)$invoice->subtotal, 2)); ?></td>
          <td style="text-align: right;"><?php echo esc_html($currency_symbol . number_format((float)$invoice->subtotal, 2)); ?></td>
        </tr>
      </tbody>
    </table>

    <!-- Totals breakdown -->
    <table style="width: 40%; margin-left: 60%;">
      <tr class="total-section">
        <td style="color: #5C5C6B;"><?php esc_html_e('Subtotal:', 'whois-crm'); ?></td>
        <td style="text-align: right;"><?php echo esc_html($currency_symbol . number_format((float)$invoice->subtotal, 2)); ?></td>
      </tr>
      <?php if ((float)$invoice->discount > 0) : ?>
        <tr class="total-section">
          <td style="color: #5C5C6B;"><?php esc_html_e('Discount:', 'whois-crm'); ?></td>
          <td style="text-align: right; color: #C42B2B;"><?php echo esc_html('-' . $currency_symbol . number_format((float)$invoice->discount, 2)); ?></td>
        </tr>
      <?php endif; ?>
      <?php if ((float)$invoice->tax_amount > 0) : ?>
        <tr class="total-section">
          <td style="color: #5C5C6B;">
            <?php printf(esc_html__('Tax (%s%%):', 'whois-crm'), esc_html(number_format((float)$invoice->tax_rate, 1))); ?>
          </td>
          <td style="text-align: right;"><?php echo esc_html($currency_symbol . number_format((float)$invoice->tax_amount, 2)); ?></td>
        </tr>
      <?php endif; ?>
      <tr style="border-top: 2px solid #E8E8EF;" class="total-section">
        <td class="total-bold"><?php esc_html_e('Total:', 'whois-crm'); ?></td>
        <td style="text-align: right;" class="total-bold">
          <?php echo esc_html($currency_symbol . number_format((float)$invoice->total, 2)); ?>
        </td>
      </tr>
    </table>

    <!-- Payment metadata details -->
    <?php if ($payment) : ?>
      <div style="font-size: 12px; color: #5C5C6B; margin-top: 40px; padding: 15px; background: #F8F8FA; border-radius: 6px; border: 1px solid #E8E8EF;">
        <strong><?php esc_html_e('Payment Information:', 'whois-crm'); ?></strong><br>
        <?php printf(esc_html__('Paid via %1$s on %2$s. Transaction ID: %3$s', 'whois-crm'), 
          esc_html(strtoupper($payment->payment_method ?? 'card')), 
          esc_html(gmdate('Y-m-d H:i:s', strtotime($payment->paid_at))),
          esc_html($payment->stripe_payment_intent_id ?? $payment->id)
        ); ?>
      </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="footer">
      <p style="margin: 0 0 6px 0;"><strong><?php esc_html_e('Thank you for your business!', 'whois-crm'); ?></strong></p>
      <p style="margin: 0;"><?php printf(esc_html__('For support or billing inquiries, please contact %s', 'whois-crm'), esc_html($support_email)); ?></p>
    </div>

  </div>
</body>
</html>
