<?php
/**
 * Template: 1-Day Expiry Reminder Email Body
 *
 * Variables:
 *  $subscription  object  CRM subscription row merged with customer details
 *  $portal_url    string  URL to customer portal
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

$display_name = $subscription->first_name ?: $subscription->customer_email;
?>
<h2 style="margin-top: 0; color: #E53535; font-size: 20px; font-weight: 700;"><?php printf(esc_html__('Hello %s,', 'whois-crm'), esc_html($display_name)); ?></h2>

<p>
  <strong><?php esc_html_e('Urgent Billing Alert:', 'whois-crm'); ?></strong> <?php printf(esc_html__('Your subscription to **%s** is expiring in less than 24 hours.', 'whois-crm'), esc_html($subscription->package_name)); ?>
</p>

<!-- Sub summary details -->
<div style="background-color: #F8F8FA; border: 1px solid #E8E8EF; border-radius: 8px; padding: 20px; margin: 24px 0;">
  <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
    <tr>
      <td style="padding: 6px 0; color: #5C5C6B;"><?php esc_html_e('Subscription Plan:', 'whois-crm'); ?></td>
      <td style="padding: 6px 0; text-align: right; font-weight: 600; color: #0A0A0B;"><?php echo esc_html($subscription->package_name); ?></td>
    </tr>
    <tr>
      <td style="padding: 6px 0; color: #5C5C6B;"><?php esc_html_e('Expiry Date:', 'whois-crm'); ?></td>
      <td style="padding: 6px 0; text-align: right; font-weight: 600; color: #E53535;"><?php echo esc_html(gmdate('Y-m-d', strtotime($subscription->expires_at))); ?></td>
    </tr>
  </table>
</div>

<p>
  <?php esc_html_e('Please make sure that you have a valid card linked to your account. If the payment fails or auto-renewal is disabled, your daily database feed downloads and REST API access keys will be disabled tomorrow.', 'whois-crm'); ?>
</p>

<div style="text-align: center;">
  <a href="<?php echo esc_url($portal_url); ?>" class="btn" style="background-color: #E53535; color: #FFFFFF !important;">
    <?php esc_html_e('Update Billing & Renew', 'whois-crm'); ?>
  </a>
</div>

<p>
  <?php esc_html_e('Regards,', 'whois-crm'); ?><br>
  <strong><?php echo esc_html(get_bloginfo('name')); ?> <?php esc_html_e('Team', 'whois-crm'); ?></strong>
</p>
