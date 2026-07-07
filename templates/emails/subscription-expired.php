<?php
/**
 * Template: Subscription Expired Email Body
 *
 * Variables:
 *  $wp_user       object  WP User object
 *  $subscription  object  CRM subscription row
 *  $package_name  string  Name of package
 *  $pricing_url   string  URL to public pricing page
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

$display_name = $wp_user->display_name ?: $wp_user->user_login;
?>
<h2 style="margin-top: 0; color: #E53535; font-size: 20px; font-weight: 700;"><?php printf(esc_html__('Hello %s,', 'whois-crm'), esc_html($display_name)); ?></h2>

<p>
  <?php printf(esc_html__('Your subscription to the **%s** plan has expired.', 'whois-crm'), esc_html($package_name)); ?>
</p>

<p>
  <?php esc_html_e('As a result, your access to database feed downloads and API key credentials associated with this plan has been deactivated. Any stored tokens or API keys will return authorization errors starting today.', 'whois-crm'); ?>
</p>

<!-- Expiry detail card -->
<div style="background-color: #F8F8FA; border: 1px solid #E8E8EF; border-radius: 8px; padding: 20px; margin: 24px 0;">
  <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
    <tr>
      <td style="padding: 6px 0; color: #5C5C6B;"><?php esc_html_e('Expired Plan:', 'whois-crm'); ?></td>
      <td style="padding: 6px 0; text-align: right; font-weight: 600; color: #0A0A0B;"><?php echo esc_html($package_name); ?></td>
    </tr>
    <tr>
      <td style="padding: 6px 0; color: #5C5C6B;"><?php esc_html_e('Expiration Date:', 'whois-crm'); ?></td>
      <td style="padding: 6px 0; text-align: right; font-weight: 600; color: #E53535;"><?php echo esc_html(gmdate('Y-m-d', strtotime($subscription->expires_at))); ?></td>
    </tr>
  </table>
</div>

<p>
  <?php esc_html_e('If this expiration was unintentional, you can easily renew your subscription and restore database download permissions by selecting a plan on our pricing page.', 'whois-crm'); ?>
</p>

<div style="text-align: center;">
  <a href="<?php echo esc_url($pricing_url); ?>" class="btn" style="color: #FFFFFF !important;">
    <?php esc_html_e('Renew Subscription', 'whois-crm'); ?>
  </a>
</div>

<p>
  <?php esc_html_e('Thank you for your business. We hope to see you back soon!', 'whois-crm'); ?>
</p>

<p>
  <?php esc_html_e('Regards,', 'whois-crm'); ?><br>
  <strong><?php echo esc_html(get_bloginfo('name')); ?> <?php esc_html_e('Team', 'whois-crm'); ?></strong>
</p>
