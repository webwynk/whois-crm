<?php
/**
 * Template: Email Layout Wrapper (Base Layout)
 *
 * Variables:
 *  $email_body_content  string  The HTML body of the specific email
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

$primary_color = '#FF6621';
$site_name = get_bloginfo('name');
$sender_name = get_option('whoiscrm_email_sender_name') ?: $site_name;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo esc_html($site_name); ?></title>
  <style>
    body {
      background-color: #F8F8FA;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
      margin: 0;
      padding: 0;
      -webkit-font-smoothing: antialiased;
      -moz-osx-font-smoothing: grayscale;
    }
    .wrapper {
      width: 100%;
      background-color: #F8F8FA;
      padding: 40px 0;
    }
    .container {
      max-width: 600px;
      margin: 0 auto;
      background-color: #FFFFFF;
      border: 1px solid #E8E8EF;
      border-radius: 12px;
      box-shadow: 0 4px 6px rgba(10,10,11,0.02);
      overflow: hidden;
    }
    .header {
      background-color: #0A0A0B;
      padding: 24px var(--space-6);
      text-align: center;
      border-bottom: 3px solid <?php echo esc_attr($primary_color); ?>;
    }
    .header h1 {
      margin: 0;
      color: #FFFFFF;
      font-size: 20px;
      font-weight: 700;
      letter-spacing: -0.02em;
    }
    .content {
      padding: 40px 32px;
      color: #0A0A0B;
      font-size: 15px;
      line-height: 1.6;
    }
    .footer {
      background-color: #F8F8FA;
      padding: 24px 32px;
      text-align: center;
      font-size: 12px;
      color: #9898A8;
      border-top: 1px solid #E8E8EF;
    }
    .footer a {
      color: <?php echo esc_attr($primary_color); ?>;
      text-decoration: none;
    }
    .btn {
      display: inline-block;
      background-color: <?php echo esc_attr($primary_color); ?>;
      color: #FFFFFF !important;
      font-weight: 600;
      padding: 12px 24px;
      border-radius: 8px;
      text-decoration: none;
      margin: 20px 0;
    }
    .btn:hover {
      background-color: #E5571A;
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="container">
      
      <!-- Brand Header -->
      <div class="header">
        <h1><?php echo esc_html($site_name); ?></h1>
      </div>

      <!-- Main Body Content -->
      <div class="content">
        <?php echo $email_body_content; // phpcs:ignore WordPress.Security.OutputNotEscaped ?>
      </div>

      <!-- Footer Info -->
      <div class="footer">
        <p style="margin: 0 0 8px 0;">
          <?php printf(esc_html__('Sent by %s. All rights reserved.', 'whois-crm'), esc_html($sender_name)); ?>
        </p>
        <p style="margin: 0;">
          <?php esc_html_e('You are receiving this notification because you hold an account with us.', 'whois-crm'); ?>
          <br>
          <a href="<?php echo esc_url(home_url()); ?>"><?php esc_html_e('Visit Website', 'whois-crm'); ?></a>
        </p>
      </div>

    </div>
  </div>
</body>
</html>
