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
/* ─── Ultra-Resilient Theme Overrides ────────────────────────────────────── */
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
  margin: 0 !important;
  padding: 0 !important;
}

.whoiscrm-nav-guest-wrap a.whoiscrm-nav-btn,
.whoiscrm-nav-guest-wrap a.whoiscrm-nav-btn:link,
.whoiscrm-nav-guest-wrap a.whoiscrm-nav-btn:visited {
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
  outline: none !important;
  box-shadow: none !important;
}

/* Primary Solid Login Button */
.whoiscrm-nav-guest-wrap a.whoiscrm-nav-btn--login,
.whoiscrm-nav-guest-wrap a.whoiscrm-nav-btn--login:link,
.whoiscrm-nav-guest-wrap a.whoiscrm-nav-btn--login:visited {
  background: linear-gradient(135deg, #FF6621 0%, #E5571A 100%) !important;
  color: #FFFFFF !important;
  border: none !important;
  box-shadow: 0 4px 14px rgba(255,102,33,0.30) !important;
}
.whoiscrm-nav-guest-wrap a.whoiscrm-nav-btn--login:hover,
.whoiscrm-nav-guest-wrap a.whoiscrm-nav-btn--login:focus {
  transform: translateY(-2px) !important;
  box-shadow: 0 6px 18px rgba(255,102,33,0.45) !important;
  color: #FFFFFF !important;
  background: linear-gradient(135deg, #FF6621 0%, #E5571A 100%) !important;
}

/* Secondary Outline Sign Up Button */
.whoiscrm-nav-guest-wrap a.whoiscrm-nav-btn--signup,
.whoiscrm-nav-guest-wrap a.whoiscrm-nav-btn--signup:link,
.whoiscrm-nav-guest-wrap a.whoiscrm-nav-btn--signup:visited {
  background: #FFFFFF !important;
  color: #FF6621 !important;
  border: 1.5px solid #FF6621 !important;
}
.whoiscrm-nav-guest-wrap a.whoiscrm-nav-btn--signup:hover,
.whoiscrm-nav-guest-wrap a.whoiscrm-nav-btn--signup:focus {
  background: rgba(255,102,33,0.08) !important;
  transform: translateY(-2px) !important;
  color: #FF6621 !important;
  border-color: #FF6621 !important;
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
