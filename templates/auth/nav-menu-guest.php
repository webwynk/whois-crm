<?php
/**
 * Template: Navigation Menu — Guest (Logged Out) State (Self-Contained & Responsive)
 *
 * Variables:
 *  $login_url    string
 *  $register_url string
 *  $pricing_url  string
 *  $forgot_url   string
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

$pricing_url = $pricing_url ?? home_url('/pricing-page/');
$forgot_url  = $forgot_url ?? wp_lostpassword_url();
?>

<style>
/* ─── Responsive Guest Nav Scope ─────────────────────────────────────────── */
.whoiscrm-nav-guest-root,
.whoiscrm-nav-guest-root * {
  box-sizing: border-box !important;
  font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
}

.whoiscrm-nav-guest-root {
  display: inline-block !important;
  vertical-align: middle !important;
}

/* Desktop View (>= 768px): Show Login & Sign Up Buttons */
.whoiscrm-guest-desktop-wrap {
  display: inline-flex !important;
  align-items: center !important;
  gap: 12px !important;
}

.whoiscrm-guest-desktop-wrap a.whoiscrm-nav-btn,
.whoiscrm-guest-desktop-wrap a.whoiscrm-nav-btn:link,
.whoiscrm-guest-desktop-wrap a.whoiscrm-nav-btn:visited {
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
}

.whoiscrm-guest-desktop-wrap a.whoiscrm-nav-btn--login,
.whoiscrm-guest-desktop-wrap a.whoiscrm-nav-btn--login:link,
.whoiscrm-guest-desktop-wrap a.whoiscrm-nav-btn--login:visited {
  background: linear-gradient(135deg, #FF6621 0%, #E5571A 100%) !important;
  color: #FFFFFF !important;
  border: none !important;
  box-shadow: 0 4px 14px rgba(255,102,33,0.30) !important;
}
.whoiscrm-guest-desktop-wrap a.whoiscrm-nav-btn--login:hover {
  transform: translateY(-2px) !important;
  box-shadow: 0 6px 18px rgba(255,102,33,0.45) !important;
  color: #FFFFFF !important;
}

.whoiscrm-guest-desktop-wrap a.whoiscrm-nav-btn--signup,
.whoiscrm-guest-desktop-wrap a.whoiscrm-nav-btn--signup:link,
.whoiscrm-guest-desktop-wrap a.whoiscrm-nav-btn--signup:visited {
  background: #FFFFFF !important;
  color: #FF6621 !important;
  border: 1.5px solid #FF6621 !important;
}
.whoiscrm-guest-desktop-wrap a.whoiscrm-nav-btn--signup:hover {
  background: rgba(255,102,33,0.08) !important;
  transform: translateY(-2px) !important;
  color: #FF6621 !important;
}

/* Mobile & Tablet View (< 768px): Hide Desktop Buttons, Show Avatar Chip */
.whoiscrm-guest-mobile-wrap {
  display: none !important;
  position: relative !important;
}

.whoiscrm-guest-chip {
  display: inline-flex !important;
  align-items: center !important;
  gap: 8px !important;
  padding: 4px 12px 4px 5px !important;
  background: #FFFFFF !important;
  border: 1.5px solid #E8E8EF !important;
  border-radius: 9999px !important;
  cursor: pointer !important;
  transition: all 200ms ease-out !important;
  box-shadow: 0 2px 6px rgba(10,10,11,0.04) !important;
  color: #0A0A0B !important;
  outline: none !important;
  height: 40px !important;
}

.whoiscrm-guest-chip:hover,
.whoiscrm-guest-mobile-wrap.is-open .whoiscrm-guest-chip {
  border-color: #FF6621 !important;
  box-shadow: 0 6px 16px rgba(10,10,11,0.08) !important;
}

.whoiscrm-guest-avatar-icon {
  width: 30px !important;
  height: 30px !important;
  border-radius: 50% !important;
  background: rgba(255,102,33,0.12) !important;
  color: #FF6621 !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  flex-shrink: 0 !important;
}
.whoiscrm-guest-avatar-icon svg {
  width: 16px !important;
  height: 16px !important;
  stroke: #FF6621 !important;
  fill: none !important;
  stroke-width: 2 !important;
}

.whoiscrm-guest-chip-text {
  font-size: 0.8125rem !important;
  font-weight: 700 !important;
  color: #0A0A0B !important;
}

.whoiscrm-guest-chevron {
  width: 12px !important;
  height: 12px !important;
  stroke: #5C5C6B !important;
  fill: none !important;
  stroke-width: 2.5 !important;
  transition: transform 200ms ease-out !important;
}
.whoiscrm-guest-mobile-wrap.is-open .whoiscrm-guest-chevron {
  transform: rotate(180deg) !important;
}

/* Mobile Guest Dropdown Card */
.whoiscrm-guest-dropdown {
  position: absolute !important;
  top: calc(100% + 10px) !important;
  right: 0 !important;
  width: 250px !important;
  background: #FFFFFF !important;
  border: 1.5px solid #E8E8EF !important;
  border-radius: 20px !important;
  padding: 16px !important;
  box-shadow: 0 16px 40px rgba(10,10,11,0.12) !important;
  z-index: 999999 !important;
  opacity: 0 !important;
  visibility: hidden !important;
  transform: translateY(12px) scale(0.96) !important;
  transition: opacity 220ms ease-out, transform 220ms cubic-bezier(0.34, 1.56, 0.64, 1), visibility 220ms !important;
  pointer-events: none !important;
  text-align: left !important;
}

.whoiscrm-guest-mobile-wrap.is-open .whoiscrm-guest-dropdown {
  opacity: 1 !important;
  visibility: visible !important;
  transform: translateY(0) scale(1) !important;
  pointer-events: auto !important;
}

.whoiscrm-guest-dropdown-header {
  display: flex !important;
  align-items: center !important;
  gap: 10px !important;
  padding: 4px 4px 10px 4px !important;
}

.whoiscrm-guest-dropdown-title {
  font-size: 0.9375rem !important;
  font-weight: 800 !important;
  color: #0A0A0B !important;
  line-height: 1.2 !important;
}
.whoiscrm-guest-dropdown-subtitle {
  font-size: 0.75rem !important;
  color: #9898A8 !important;
  margin-top: 2px !important;
  line-height: 1.2 !important;
}

.whoiscrm-guest-dropdown-divider {
  height: 1px !important;
  background: #E8E8EF !important;
  margin: 8px -16px !important;
  border: none !important;
}

.whoiscrm-guest-dropdown-menu {
  display: flex !important;
  flex-direction: column !important;
  gap: 4px !important;
  padding-top: 4px !important;
}

.whoiscrm-guest-dropdown-item {
  display: flex !important;
  align-items: center !important;
  gap: 10px !important;
  padding: 10px 12px !important;
  border-radius: 10px !important;
  font-size: 0.875rem !important;
  font-weight: 600 !important;
  color: #5C5C6B !important;
  text-decoration: none !important;
  transition: all 150ms ease-out !important;
  line-height: 1.2 !important;
}

.whoiscrm-guest-dropdown-item svg {
  width: 18px !important;
  height: 18px !important;
  stroke: #9898A8 !important;
  fill: none !important;
  stroke-width: 2 !important;
  flex-shrink: 0 !important;
}

.whoiscrm-guest-dropdown-item:hover {
  background: rgba(255,102,33,0.08) !important;
  color: #FF6621 !important;
}
.whoiscrm-guest-dropdown-item:hover svg {
  stroke: #FF6621 !important;
}

.whoiscrm-guest-dropdown-btn-primary {
  background: linear-gradient(135deg, #FF6621 0%, #E5571A 100%) !important;
  color: #FFFFFF !important;
  font-weight: 700 !important;
  box-shadow: 0 4px 12px rgba(255,102,33,0.25) !important;
}
.whoiscrm-guest-dropdown-btn-primary svg {
  stroke: #FFFFFF !important;
}
.whoiscrm-guest-dropdown-btn-primary:hover {
  color: #FFFFFF !important;
  background: linear-gradient(135deg, #FF6621 0%, #E5571A 100%) !important;
}
.whoiscrm-guest-dropdown-btn-primary:hover svg {
  stroke: #FFFFFF !important;
}

/* Media Query Breakpoint (768px) */
@media (max-width: 767px) {
  .whoiscrm-guest-desktop-wrap {
    display: none !important;
  }
  .whoiscrm-guest-mobile-wrap {
    display: inline-block !important;
  }
}
</style>

<div class="whoiscrm-nav-guest-root">
  <!-- Desktop Buttons (screens >= 768px) -->
  <div class="whoiscrm-guest-desktop-wrap">
    <a href="<?php echo esc_url($login_url); ?>" class="whoiscrm-nav-btn whoiscrm-nav-btn--login">
      <?php esc_html_e('Login', 'whois-crm'); ?>
    </a>
    <a href="<?php echo esc_url($register_url); ?>" class="whoiscrm-nav-btn whoiscrm-nav-btn--signup">
      <?php esc_html_e('Sign Up', 'whois-crm'); ?>
    </a>
  </div>

  <!-- Mobile Avatar Chip & Dropdown (screens < 768px) -->
  <div class="whoiscrm-guest-mobile-wrap js-whoiscrm-guest-mobile-wrap">
    <button type="button" class="whoiscrm-guest-chip js-whoiscrm-guest-trigger" aria-haspopup="true" aria-expanded="false">
      <span class="whoiscrm-guest-avatar-icon">
        <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      </span>
      <span class="whoiscrm-guest-chip-text"><?php esc_html_e('Account', 'whois-crm'); ?></span>
      <svg class="whoiscrm-guest-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
    </button>

    <div class="whoiscrm-guest-dropdown js-whoiscrm-guest-dropdown">
      <div class="whoiscrm-guest-dropdown-header">
        <div class="whoiscrm-guest-avatar-icon" style="width:36px; height:36px;">
          <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </div>
        <div>
          <div class="whoiscrm-guest-dropdown-title"><?php esc_html_e('Welcome Guest', 'whois-crm'); ?></div>
          <div class="whoiscrm-guest-dropdown-subtitle"><?php esc_html_e('Sign in to access data feeds', 'whois-crm'); ?></div>
        </div>
      </div>

      <div class="whoiscrm-guest-dropdown-divider"></div>

      <div class="whoiscrm-guest-dropdown-menu">
        <a href="<?php echo esc_url($login_url); ?>" class="whoiscrm-guest-dropdown-item whoiscrm-guest-dropdown-btn-primary">
          <svg viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
          <span><?php esc_html_e('Sign In / Login', 'whois-crm'); ?></span>
        </a>
        <a href="<?php echo esc_url($register_url); ?>" class="whoiscrm-guest-dropdown-item">
          <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="17" y1="11" x2="23" y2="11"/></svg>
          <span><?php esc_html_e('Create Account', 'whois-crm'); ?></span>
        </a>
        <a href="<?php echo esc_url($pricing_url); ?>" class="whoiscrm-guest-dropdown-item">
          <svg viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
          <span><?php esc_html_e('Pricing & Feeds', 'whois-crm'); ?></span>
        </a>
        <a href="<?php echo esc_url($forgot_url); ?>" class="whoiscrm-guest-dropdown-item">
          <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          <span><?php esc_html_e('Forgot Password?', 'whois-crm'); ?></span>
        </a>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  'use strict';
  function initWhoisCRMGuestMobile() {
    const triggers = document.querySelectorAll('.js-whoiscrm-guest-trigger');
    triggers.forEach(trigger => {
      if (trigger.dataset.initialized) return;
      trigger.dataset.initialized = 'true';

      const wrap = trigger.closest('.js-whoiscrm-guest-mobile-wrap');
      if (!wrap) return;

      function openMenu() {
        wrap.classList.add('is-open');
        trigger.setAttribute('aria-expanded', 'true');
      }

      function closeMenu() {
        wrap.classList.remove('is-open');
        trigger.setAttribute('aria-expanded', 'false');
      }

      function toggleMenu(e) {
        e.preventDefault();
        e.stopPropagation();
        if (wrap.classList.contains('is-open')) {
          closeMenu();
        } else {
          openMenu();
        }
      }

      trigger.addEventListener('click', toggleMenu);

      document.addEventListener('click', function(e) {
        if (!wrap.contains(e.target)) closeMenu();
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initWhoisCRMGuestMobile);
  } else {
    initWhoisCRMGuestMobile();
  }
})();
</script>
