<?php
/**
 * Template: Navigation Menu — Guest (Logged Out) State
 *
 * Variables:
 *  $login_url    string
 *  $register_url string
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
?>

<div class="whoiscrm-nav-guest-wrap">
  <a href="<?php echo esc_url($login_url); ?>" class="whoiscrm-nav-btn whoiscrm-nav-btn--login">
    <?php esc_html_e('Login', 'whois-crm'); ?>
  </a>
  <a href="<?php echo esc_url($register_url); ?>" class="whoiscrm-nav-btn whoiscrm-nav-btn--signup">
    <?php esc_html_e('Sign Up', 'whois-crm'); ?>
  </a>
</div>
