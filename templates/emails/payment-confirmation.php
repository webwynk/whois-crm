<?php
/**
 * Template: Payment Confirmation Email Body
 *
 * Variables:
 *  $wp_user       object  WP User object
 *  $payment       object  CRM payment row
 *  $package_name  string  Name of package purchased
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

$display_name = $wp_user->display_name ?: $wp_user->user_login;
?>
<h2 style="margin-top: 0; color: #0A0A0B; font-size: 20px; font-weight: 700;"><?php printf(esc_html__('Hello %s,', 'whois-crm'), esc_html($display_name)); ?></h2>

<p>
  <?php esc_html_e('Thank you for your purchase! We have successfully processed your subscription payment.', 'whois-crm'); ?>
</p>

<!-- Receipt card details -->
<div style="background-color: #F8F8FA; border: 1px solid #E8E8EF; border-radius: 8px; padding: 20px; margin: 24px 0;">
  <h3 style="margin-top: 0; font-size: 14px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: #9898A8; border-bottom: 1px solid #E8E8EF; padding-bottom: 8px;">
    <?php esc_html_e('Payment Details', 'whois-crm'); ?>
  </h3>
  
  <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
    <tr>
      <td style="padding: 6px 0; font-weight: 600; color: #5C5C6B;"><?php esc_html_e('Subscribed Item:', 'whois-crm'); ?></td>
      <td style="padding: 6px 0; text-align: right; font-weight: 600; color: #0A0A0B;"><?php echo esc_html($package_name); ?></td>
    </tr>
    <tr>
      <td style="padding: 6px 0; color: #5C5C6B;"><?php esc_html_e('Transaction Reference:', 'whois-crm'); ?></td>
      <td style="padding: 6px 0; text-align: right; font-family: monospace; color: #0A0A0B;"><?php echo esc_html($payment->stripe_payment_intent_id ?? $payment->id); ?></td>
    </tr>
    <tr>
      <td style="padding: 6px 0; color: #5C5C6B;"><?php esc_html_e('Payment Method:', 'whois-crm'); ?></td>
      <td style="padding: 6px 0; text-align: right; text-transform: uppercase; color: #0A0A0B;"><?php echo esc_html($payment->payment_method ?? 'card'); ?></td>
    </tr>
    <tr style="border-top: 1px solid #E8E8EF;">
      <td style="padding: 10px 0 0 0; font-size: 16px; font-weight: 700; color: #0A0A0B;"><?php esc_html_e('Amount Charged:', 'whois-crm'); ?></td>
      <td style="padding: 10px 0 0 0; text-align: right; font-size: 18px; font-weight: 700; color: #FF6621;">
        $<?php echo esc_html(number_format((float)$payment->amount, 2)); ?>
      </td>
    </tr>
  </table>
</div>

<p>
  <?php esc_html_e('Your database feed access is now fully active. You can start downloading your target archives directly from the customer portal.', 'whois-crm'); ?>
</p>

<p>
  <?php esc_html_e('If you require a formal tax invoice PDF, it has been generated and is attached to a separate email or accessible inside your profile download tab.', 'whois-crm'); ?>
</p>

<p>
  <?php esc_html_e('Regards,', 'whois-crm'); ?><br>
  <strong><?php echo esc_html(get_bloginfo('name')); ?> <?php esc_html_e('Team', 'whois-crm'); ?></strong>
</p>
