<?php
/**
 * Template: Subscription Activated Email Body
 *
 * Variables:
 *  $wp_user       object  WP User object
 *  $subscription  object  CRM subscription row
 *  $package_name  string  Name of the package
 *  $portal_url    string  URL to customer portal
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

$display_name = $wp_user->display_name ?: $wp_user->user_login;
?>
<h2 style="margin-top: 0; color: #0A0A0B; font-size: 20px; font-weight: 700;"><?php printf(esc_html__('Hello %s,', 'whois-crm'), esc_html($display_name)); ?></h2>

<p>
  <?php printf(esc_html__('Your subscription to the **%s** plan has been successfully activated!', 'whois-crm'), esc_html($package_name)); ?>
</p>

<p>
  <?php esc_html_e('You can now log in to the secure downloads area to access daily database dumps matching your package settings. For Enterprise subscribers, you are also granted access to configure a developer REST API key.', 'whois-crm'); ?>
</p>

<!-- Sub summary details -->
<div style="background-color: #F8F8FA; border: 1px solid #E8E8EF; border-radius: 8px; padding: 20px; margin: 24px 0;">
  <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
    <tr>
      <td style="padding: 6px 0; color: #5C5C6B;"><?php esc_html_e('Subscription Plan:', 'whois-crm'); ?></td>
      <td style="padding: 6px 0; text-align: right; font-weight: 600; color: #0A0A0B;"><?php echo esc_html($package_name); ?></td>
    </tr>
    <tr>
      <td style="padding: 6px 0; color: #5C5C6B;"><?php esc_html_e('Activation Date:', 'whois-crm'); ?></td>
      <td style="padding: 6px 0; text-align: right; color: #0A0A0B;"><?php echo esc_html(gmdate('Y-m-d', strtotime($subscription->starts_at))); ?></td>
    </tr>
    <tr>
      <td style="padding: 6px 0; color: #5C5C6B;"><?php esc_html_e('Next Renewal Date:', 'whois-crm'); ?></td>
      <td style="padding: 6px 0; text-align: right; font-weight: 600; color: #14803C;"><?php echo esc_html(gmdate('Y-m-d', strtotime($subscription->expires_at))); ?></td>
    </tr>
  </table>
</div>

<div style="text-align: center;">
  <a href="<?php echo esc_url($portal_url); ?>" class="btn" style="color: #FFFFFF !important;">
    <?php esc_html_e('Access Download Feeds', 'whois-crm'); ?>
  </a>
</div>

<p>
  <?php esc_html_e('Thank you for partnering with us to keep your domain data archives updated.', 'whois-crm'); ?>
</p>

<p>
  <?php esc_html_e('Regards,', 'whois-crm'); ?><br>
  <strong><?php echo esc_html(get_bloginfo('name')); ?> <?php esc_html_e('Team', 'whois-crm'); ?></strong>
</p>
