<?php
/**
 * Template: Navigation Menu — Guest (Logged Out) State (Self-Contained & Theme Proof)
 *
 * Variables:
 *  $login_url    string
 *  $register_url string
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
?>

<style>
.whoiscrm-nav-guest-wrap,
.whoiscrm-nav-guest-wrap * {
  box-sizing: border-box !important;
  font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
}

.whoiscrm-nav-guest-wrap {
  display: inline-flex !important;
  align-items: center !important;
  gap: 12px !important;
  vertical-align: middle !important;
}

.whoiscrm-nav-btn {
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  padding: 9px 22px !important;
  border-radius: 10px !important;
  font-size: 0.875rem !important;
  font-weight: 700 !important;
  text-decoration: none !important;
  transition: all 200ms cubic-bezier(0.4,0,0.2,1) !important;
  line-height: 1.2 !important;
  cursor: pointer !important;
  height: 40px !important;
  min-height: 40px !important;
  max-height: 40px !important;
}

.whoiscrm-nav-btn--login {
  background: linear-gradient(135deg, #FF6621 0%, #E5571A 100%) !important;
  color: #FFFFFF !important;
  border: none !important;
  box-shadow: 0 4px 14px rgba(255,102,33,0.25) !important;
}
.whoiscrm-nav-btn--login:hover {
  transform: translateY(-2px) !important;
  box-shadow: 0 6px 18px rgba(255,102,33,0.40) !important;
  color: #FFFFFF !important;
}

.whoiscrm-nav-btn--signup {
  background: transparent !important;
  color: #FF6621 !important;
  border: 1.5px solid #FF6621 !important;
}
.whoiscrm-nav-btn--signup:hover {
  background: rgba(255,102,33,0.08) !important;
  transform: translateY(-2px) !important;
  color: #FF6621 !important;
}
</style>

<div class="whoiscrm-nav-guest-wrap">
  <a href="<?php echo esc_url($login_url); ?>" class="whoiscrm-nav-btn whoiscrm-nav-btn--login">
    <?php esc_html_e('Login', 'whois-crm'); ?>
  </a>
  <a href="<?php echo esc_url($register_url); ?>" class="whoiscrm-nav-btn whoiscrm-nav-btn--signup">
    <?php esc_html_e('Sign Up', 'whois-crm'); ?>
  </a>
</div>
