<?php
/**
 * Template: Payment Failed Email Body
 *
 * Variables:
 *  $wp_user       object  WP User object
 *  $payment       object  CRM payment row
 *  $package_name  string  Name of package
 *  $portal_url    string  URL to customer portal
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

$display_name = $wp_user->display_name ?: $wp_user->user_login;
?>
<h2 style="margin-top: 0; color: #E53535; font-size: 20px; font-weight: 700;"><?php printf(esc_html__('Hello %s,', 'whois-crm'), esc_html($display_name)); ?></h2>

<p>
  <strong><?php esc_html_e('Important Billing Alert:', 'whois-crm'); ?></strong> <?php printf(esc_html__('We were unable to process your renewal payment of **$%s** for the **%s** subscription plan.', 'whois-crm'), esc_html(number_format((float)$payment->amount, 2)), esc_html($package_name)); ?>
</p>

<p>
  <?php esc_html_e('This failure might be due to an expired card, insufficient funds, or a temporary block by your bank. Stripe will retry processing this invoice automatically in a few days.', 'whois-crm'); ?>
</p>

<p>
  <?php esc_html_e('To avoid any interruption to your daily WHOIS data feeds or REST API access, please log in and update your credit card details immediately.', 'whois-crm'); ?>
</p>

<!-- Billing status details -->
<div style="background-color: #F8F8FA; border: 1px solid #E8E8EF; border-radius: 8px; padding: 20px; margin: 24px 0;">
  <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
    <tr>
      <td style="padding: 6px 0; color: #5C5C6B;"><?php esc_html_e('Subscribed Item:', 'whois-crm'); ?></td>
      <td style="padding: 6px 0; text-align: right; font-weight: 600; color: #0A0A0B;"><?php echo esc_html($package_name); ?></td>
    </tr>
    <tr>
      <td style="padding: 6px 0; color: #5C5C6B;"><?php esc_html_e('Renewal Price:', 'whois-crm'); ?></td>
      <td style="padding: 6px 0; text-align: right; font-weight: 600; color: #E53535;">
        $<?php echo esc_html(number_format((float)$payment->amount, 2)); ?>
      </td>
    </tr>
    <tr>
      <td style="padding: 6px 0; color: #5C5C6B;"><?php esc_html_e('Stripe Invoice ID:', 'whois-crm'); ?></td>
      <td style="padding: 6px 0; text-align: right; font-family: monospace; color: #0A0A0B;"><?php echo esc_html($payment->stripe_payment_intent_id ?? '—'); ?></td>
    </tr>
  </table>
</div>

<div style="text-align: center;">
  <a href="<?php echo esc_url($portal_url); ?>" class="btn" style="background-color: #E53535; color: #FFFFFF !important;">
    <?php esc_html_e('Update Payment Method', 'whois-crm'); ?>
  </a>
</div>

<p>
  <?php esc_html_e('If you have already resolved this issue with your card issuer, you can ignore this alert.', 'whois-crm'); ?>
</p>

<p style="font-size: 13px; color: #9898A8; border-top: 1px solid #E8E8EF; padding-top: var(--space-4); margin-top: 30px;">
  <?php esc_html_e('If you have any questions, reply to this message to reach our billing support team.', 'whois-crm'); ?>
</p>

<p>
  <?php esc_html_e('Regards,', 'whois-crm'); ?><br>
  <strong><?php echo esc_html(get_bloginfo('name')); ?> <?php esc_html_e('Team', 'whois-crm'); ?></strong>
</p>
