<?php
/**
 * Template: Invoice Attached Email Body
 *
 * Variables:
 *  $wp_user  object  WP User object
 *  $invoice  object  CRM invoice row
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

$display_name = $wp_user->display_name ?: $wp_user->user_login;
?>
<h2 style="margin-top: 0; color: #0A0A0B; font-size: 20px; font-weight: 700;"><?php printf(esc_html__('Hello %s,', 'whois-crm'), esc_html($display_name)); ?></h2>

<p>
  <?php printf(esc_html__('Your invoice %s has been successfully generated for your subscription billing cycle.', 'whois-crm'), esc_html($invoice->invoice_number)); ?>
</p>

<p>
  <?php esc_html_e('We have attached a secure PDF copy of this invoice to this email for your accounting records. It lists the subtotal, applicable taxes (like GST/VAT), and coupon discounts applied to your payment.', 'whois-crm'); ?>
</p>

<!-- Invoice summary details -->
<div style="background-color: #F8F8FA; border: 1px solid #E8E8EF; border-radius: 8px; padding: 20px; margin: 24px 0;">
  <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
    <tr>
      <td style="padding: 6px 0; color: #5C5C6B;"><?php esc_html_e('Invoice Number:', 'whois-crm'); ?></td>
      <td style="padding: 6px 0; text-align: right; font-weight: 600; color: #0A0A0B;"><?php echo esc_html($invoice->invoice_number); ?></td>
    </tr>
    <tr>
      <td style="padding: 6px 0; color: #5C5C6B;"><?php esc_html_e('Billing Date:', 'whois-crm'); ?></td>
      <td style="padding: 6px 0; text-align: right; color: #0A0A0B;"><?php echo esc_html(gmdate('Y-m-d', strtotime($invoice->invoice_date))); ?></td>
    </tr>
    <tr>
      <td style="padding: 6px 0; color: #5C5C6B;"><?php esc_html_e('Status:', 'whois-crm'); ?></td>
      <td style="padding: 6px 0; text-align: right; color: #0A0A0B; text-transform: uppercase; font-weight: 600;">
        <?php echo esc_html($invoice->status); ?>
      </td>
    </tr>
    <tr style="border-top: 1px solid #E8E8EF;">
      <td style="padding: 10px 0 0 0; font-size: 16px; font-weight: 700; color: #0A0A0B;"><?php esc_html_e('Total Amount:', 'whois-crm'); ?></td>
      <td style="padding: 10px 0 0 0; text-align: right; font-size: 18px; font-weight: 700; color: #FF6621;">
        $<?php echo esc_html(number_format((float)$invoice->total, 2)); ?>
      </td>
    </tr>
  </table>
</div>

<p>
  <?php esc_html_e('If you cannot find the attachment, you can also view or download all historical billing invoices at any time by visiting the Invoices tab in your customer dashboard.', 'whois-crm'); ?>
</p>

<p>
  <?php esc_html_e('Thank you for choosing our service!', 'whois-crm'); ?>
</p>

<p>
  <?php esc_html_e('Regards,', 'whois-crm'); ?><br>
  <strong><?php echo esc_html(get_bloginfo('name')); ?> <?php esc_html_e('Team', 'whois-crm'); ?></strong>
</p>
