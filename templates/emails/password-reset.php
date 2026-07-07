<?php
/**
 * Template: Password Reset Email Body
 *
 * Variables:
 *  $user        object  WP User object
 *  $reset_link  string  Password reset URL
 *  $site_name   string
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

$display_name = $user->display_name ?: $user->user_login;
?>
<h2 style="margin-top: 0; color: #0A0A0B; font-size: 20px; font-weight: 700;"><?php printf(esc_html__('Hello %s,', 'whois-crm'), esc_html($display_name)); ?></h2>

<p>
  <?php printf(esc_html__('We received a request to reset the password for your customer account on %s.', 'whois-crm'), esc_html($site_name)); ?>
</p>

<p>
  <?php esc_html_e('To choose a new password, click the recovery link below. This password recovery link is only valid for 24 hours.', 'whois-crm'); ?>
</p>

<div style="text-align: center;">
  <a href="<?php echo esc_url($reset_link); ?>" class="btn" style="color: #FFFFFF !important;">
    <?php esc_html_e('Reset Password', 'whois-crm'); ?>
  </a>
</div>

<p style="font-size: 13px; color: #9898A8; border-top: 1px solid #E8E8EF; padding-top: var(--space-4); margin-top: 30px;">
  <?php esc_html_e('If the button above does not work, copy and paste this URL directly into your web browser address bar:', 'whois-crm'); ?><br>
  <code style="word-break: break-all; font-size: 11px;"><?php echo esc_html($reset_link); ?></code>
</p>

<p>
  <?php esc_html_e('If you did not request this change, you can safely ignore this email. Your password will remain unchanged.', 'whois-crm'); ?>
</p>

<p>
  <?php esc_html_e('Regards,', 'whois-crm'); ?><br>
  <strong><?php echo esc_html($site_name); ?> <?php esc_html_e('Team', 'whois-crm'); ?></strong>
</p>
