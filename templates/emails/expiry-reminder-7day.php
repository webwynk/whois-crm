<?php
/**
 * Template: 7-Day Expiry Reminder Email Body
 *
 * Variables:
 *  $subscription  object  CRM subscription row merged with customer details
 *  $portal_url    string  URL to customer portal
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

$display_name = $subscription->first_name ?: $subscription->customer_email;
?>
<h2 style="margin-top: 0; color: #0A0A0B; font-size: 20px; font-weight: 700;"><?php printf(esc_html__('Hello %s,', 'whois-crm'), esc_html($display_name)); ?></h2>

<p>
  <?php printf(esc_html__('This is a friendly reminder that your subscription to **%s** is set to renew in 7 days.', 'whois-crm'), esc_html($subscription->package_name)); ?>
</p>

<!-- Sub summary details -->
<div style="background-color: #F8F8FA; border: 1px solid #E8E8EF; border-radius: 8px; padding: 20px; margin: 24px 0;">
  <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
    <tr>
      <td style="padding: 6px 0; color: #5C5C6B;"><?php esc_html_e('Subscription Plan:', 'whois-crm'); ?></td>
      <td style="padding: 6px 0; text-align: right; font-weight: 600; color: #0A0A0B;"><?php echo esc_html($subscription->package_name); ?></td>
    </tr>
    <tr>
      <td style="padding: 6px 0; color: #5C5C6B;"><?php esc_html_e('Expiry / Renewal Date:', 'whois-crm'); ?></td>
      <td style="padding: 6px 0; text-align: right; font-weight: 600; color: #FF6621;"><?php echo esc_html(gmdate('Y-m-d', strtotime($subscription->expires_at))); ?></td>
    </tr>
  </table>
</div>

<p>
  <?php esc_html_e('If you have automatic billing enabled, no action is required — your payment method on file will be charged automatically on the date listed above.', 'whois-crm'); ?>
</p>

<p>
  <?php esc_html_e('If your card details have changed or you wish to manage your renewal preferences, please visit your account dashboard settings.', 'whois-crm'); ?>
</p>

<div style="text-align: center;">
  <a href="<?php echo esc_url($portal_url); ?>" class="btn" style="color: #FFFFFF !important;">
    <?php esc_html_e('Manage Subscription', 'whois-crm'); ?>
  </a>
</div>

<p>
  <?php esc_html_e('Thank you for your continued partnership.', 'whois-crm'); ?>
</p>

<p>
  <?php esc_html_e('Regards,', 'whois-crm'); ?><br>
  <strong><?php echo esc_html(get_bloginfo('name')); ?> <?php esc_html_e('Team', 'whois-crm'); ?></strong>
</p>
