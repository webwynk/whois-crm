<?php
/**
 * Template: Welcome Email Body
 *
 * Variables:
 *  $user       object  WP User object
 *  $login_url  string  URL of custom login page
 *  $site_name  string
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

$display_name = $user->display_name ?: $user->user_login;
?>
<h2 style="margin-top: 0; color: #0A0A0B; font-size: 20px; font-weight: 700;"><?php printf(esc_html__('Hello %s,', 'whois-crm'), esc_html($display_name)); ?></h2>

<p>
  <?php printf(esc_html__('Welcome to %s! Your customer account has been successfully created.', 'whois-crm'), esc_html($site_name)); ?>
</p>

<p>
  <?php esc_html_e('You can now log in to your dashboard to subscribe to database feeds, manage your billing settings, and download your purchased archives.', 'whois-crm'); ?>
</p>

<div style="text-align: center;">
  <a href="<?php echo esc_url($login_url); ?>" class="btn" style="color: #FFFFFF !important;">
    <?php esc_html_e('Go to Dashboard', 'whois-crm'); ?>
  </a>
</div>

<p style="font-size: 13px; color: #9898A8; border-top: 1px solid #E8E8EF; padding-top: var(--space-4); margin-top: 30px;">
  <strong><?php esc_html_e('Account Credentials:', 'whois-crm'); ?></strong><br>
  <?php esc_html_e('Username:', 'whois-crm'); ?> <code><?php echo esc_html($user->user_login); ?></code><br>
  <?php esc_html_e('Email:', 'whois-crm'); ?> <code><?php echo esc_html($user->user_email); ?></code>
</p>

<p>
  <?php esc_html_e('If you have any questions or require assistance setting up your API key, feel free to reply to this email.', 'whois-crm'); ?>
</p>

<p>
  <?php esc_html_e('Regards,', 'whois-crm'); ?><br>
  <strong><?php echo esc_html($site_name); ?> <?php esc_html_e('Team', 'whois-crm'); ?></strong>
</p>
