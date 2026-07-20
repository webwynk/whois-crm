<?php
/**
 * Template: Navigation Menu — Logged In (Member) State (Self-Contained & Theme Proof)
 *
 * Variables:
 *  $wp_user     \WP_User
 *  $customer    object|null
 *  $portal_url  string
 *  $pricing_url string
 *  $logout_url  string
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

$display_name = $wp_user->display_name ?: $wp_user->user_login;
$user_email   = $wp_user->user_email;
$avatar_url   = get_avatar_url($wp_user->ID, ['size' => 80]);

// Build active tabs links
$profile_url   = add_query_arg('tab', 'profile', $portal_url);
$downloads_url = add_query_arg('tab', 'downloads', $portal_url);
?>

<style>
/* ─── Self-Contained Scope for Navigation Menu Component ─────────────────── */
.whoiscrm-nav-user-wrap,
.whoiscrm-nav-user-wrap * {
  box-sizing: border-box !important;
  font-family: 'DM Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
}

.whoiscrm-nav-user-wrap {
  position: relative !important;
  display: inline-block !important;
  vertical-align: middle !important;
  text-align: left !important;
}

.whoiscrm-user-chip {
  display: inline-flex !important;
  align-items: center !important;
  gap: 10px !important;
  padding: 5px 14px 5px 6px !important;
  background: #FFFFFF !important;
  border: 1.5px solid #E8E8EF !important;
  border-radius: 9999px !important;
  cursor: pointer !important;
  transition: all 200ms ease-out !important;
  box-shadow: 0 2px 6px rgba(10,10,11,0.04) !important;
  color: #0A0A0B !important;
  outline: none !important;
  height: 44px !important;
  min-height: 44px !important;
  max-height: 44px !important;
  line-height: 1 !important;
  text-decoration: none !important;
  margin: 0 !important;
}

.whoiscrm-user-chip:hover,
.whoiscrm-nav-user-wrap.is-open .whoiscrm-user-chip {
  border-color: #FF6621 !important;
  box-shadow: 0 6px 16px rgba(10,10,11,0.08) !important;
  background: #FFFFFF !important;
}

.whoiscrm-user-avatar {
  width: 32px !important;
  height: 32px !important;
  min-width: 32px !important;
  min-height: 32px !important;
  max-width: 32px !important;
  max-height: 32px !important;
  border-radius: 50% !important;
  object-fit: cover !important;
  border: 1.5px solid #FF6621 !important;
  display: block !important;
  padding: 0 !important;
  margin: 0 !important;
  background: none !important;
}

.whoiscrm-user-name {
  font-size: 0.875rem !important;
  font-weight: 700 !important;
  color: #0A0A0B !important;
  white-space: nowrap !important;
  line-height: 1 !important;
}

.whoiscrm-user-chevron {
  width: 14px !important;
  height: 14px !important;
  min-width: 14px !important;
  min-height: 14px !important;
  stroke: #5C5C6B !important;
  fill: none !important;
  stroke-width: 2.5 !important;
  transition: transform 200ms ease-out !important;
  display: inline-block !important;
}
.whoiscrm-nav-user-wrap.is-open .whoiscrm-user-chevron {
  transform: rotate(180deg) !important;
}

/* Floating Dropdown Card */
.whoiscrm-user-dropdown {
  position: absolute !important;
  top: calc(100% + 10px) !important;
  right: 0 !important;
  width: 260px !important;
  min-width: 260px !important;
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

.whoiscrm-nav-user-wrap.is-open .whoiscrm-user-dropdown {
  opacity: 1 !important;
  visibility: visible !important;
  transform: translateY(0) scale(1) !important;
  pointer-events: auto !important;
}

.whoiscrm-user-dropdown-header {
  display: flex !important;
  align-items: center !important;
  gap: 12px !important;
  padding: 4px 4px 12px 4px !important;
}

.whoiscrm-user-dropdown-avatar {
  width: 44px !important;
  height: 44px !important;
  min-width: 44px !important;
  min-height: 44px !important;
  max-width: 44px !important;
  max-height: 44px !important;
  border-radius: 50% !important;
  object-fit: cover !important;
  border: 2px solid #FF6621 !important;
  flex-shrink: 0 !important;
  display: block !important;
  margin: 0 !important;
  padding: 0 !important;
}

.whoiscrm-user-dropdown-name {
  font-size: 0.9375rem !important;
  font-weight: 800 !important;
  color: #0A0A0B !important;
  line-height: 1.2 !important;
  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
  margin: 0 !important;
}

.whoiscrm-user-dropdown-email {
  font-size: 0.75rem !important;
  color: #9898A8 !important;
  white-space: nowrap !important;
  overflow: hidden !important;
  text-overflow: ellipsis !important;
  margin: 2px 0 0 0 !important;
  line-height: 1.2 !important;
}

.whoiscrm-user-dropdown-divider {
  height: 1px !important;
  background: #E8E8EF !important;
  margin: 8px -16px !important;
  border: none !important;
}

.whoiscrm-user-dropdown-menu {
  display: flex !important;
  flex-direction: column !important;
  gap: 4px !important;
  padding-top: 4px !important;
  margin: 0 !important;
  list-style: none !important;
}

.whoiscrm-user-dropdown-item {
  display: flex !important;
  align-items: center !important;
  gap: 12px !important;
  padding: 10px 12px !important;
  border-radius: 10px !important;
  font-size: 0.875rem !important;
  font-weight: 600 !important;
  color: #5C5C6B !important;
  text-decoration: none !important;
  transition: all 150ms ease-out !important;
  background: transparent !important;
  border: none !important;
  line-height: 1.2 !important;
}

.whoiscrm-user-dropdown-item svg {
  width: 18px !important;
  height: 18px !important;
  min-width: 18px !important;
  min-height: 18px !important;
  max-width: 18px !important;
  max-height: 18px !important;
  stroke: #9898A8 !important;
  fill: none !important;
  stroke-width: 2 !important;
  transition: stroke 150ms ease-out !important;
  flex-shrink: 0 !important;
  display: inline-block !important;
}

.whoiscrm-user-dropdown-item:hover {
  background: rgba(255,102,33,0.08) !important;
  color: #FF6621 !important;
}
.whoiscrm-user-dropdown-item:hover svg {
  stroke: #FF6621 !important;
}

.whoiscrm-user-dropdown-item--logout:hover {
  background: rgba(229,53,53,0.08) !important;
  color: #E53535 !important;
}
.whoiscrm-user-dropdown-item--logout:hover svg {
  stroke: #E53535 !important;
}

@media (max-width: 480px) {
  .whoiscrm-user-name {
    display: none !important;
  }
  .whoiscrm-user-chip {
    padding: 4px 8px !important;
  }
}
</style>

<div class="whoiscrm-nav-user-wrap">
  <!-- Trigger Chip -->
  <button type="button" class="whoiscrm-user-chip js-whoiscrm-user-trigger" aria-haspopup="true" aria-expanded="false">
    <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($display_name); ?>" class="whoiscrm-user-avatar">
    <span class="whoiscrm-user-name"><?php echo esc_html($display_name); ?></span>
    <svg class="whoiscrm-user-chevron" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
  </button>

  <!-- Animated Floating Dropdown Card -->
  <div class="whoiscrm-user-dropdown js-whoiscrm-user-dropdown">
    <!-- User Profile Header -->
    <div class="whoiscrm-user-dropdown-header">
      <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($display_name); ?>" class="whoiscrm-user-dropdown-avatar">
      <div style="overflow: hidden; text-align: left;">
        <div class="whoiscrm-user-dropdown-name"><?php echo esc_html($display_name); ?></div>
        <div class="whoiscrm-user-dropdown-email"><?php echo esc_html($user_email); ?></div>
      </div>
    </div>

    <div class="whoiscrm-user-dropdown-divider"></div>

    <!-- Navigation Links -->
    <div class="whoiscrm-user-dropdown-menu">
      <a href="<?php echo esc_url($portal_url); ?>" class="whoiscrm-user-dropdown-item">
        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        <span><?php esc_html_e('Customer Portal', 'whois-crm'); ?></span>
      </a>
      <a href="<?php echo esc_url($downloads_url); ?>" class="whoiscrm-user-dropdown-item">
        <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        <span><?php esc_html_e('My Downloads', 'whois-crm'); ?></span>
      </a>
      <a href="<?php echo esc_url($pricing_url); ?>" class="whoiscrm-user-dropdown-item">
        <svg viewBox="0 0 24 24"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
        <span><?php esc_html_e('Pricing & Feeds', 'whois-crm'); ?></span>
      </a>
      <a href="<?php echo esc_url($profile_url); ?>" class="whoiscrm-user-dropdown-item">
        <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        <span><?php esc_html_e('My Account', 'whois-crm'); ?></span>
      </a>

      <div class="whoiscrm-user-dropdown-divider"></div>

      <a href="<?php echo esc_url($logout_url); ?>" class="whoiscrm-user-dropdown-item whoiscrm-user-dropdown-item--logout">
        <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        <span><?php esc_html_e('Sign Out', 'whois-crm'); ?></span>
      </a>
    </div>
  </div>
</div>

<script>
(function() {
  'use strict';
  function initWhoisCRMNav() {
    const triggers = document.querySelectorAll('.js-whoiscrm-user-trigger');
    triggers.forEach(trigger => {
      if (trigger.dataset.initialized) return;
      trigger.dataset.initialized = 'true';

      const wrap = trigger.closest('.whoiscrm-nav-user-wrap');
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

      wrap.addEventListener('mouseenter', function() {
        if (window.innerWidth >= 768) openMenu();
      });
      wrap.addEventListener('mouseleave', function() {
        if (window.innerWidth >= 768) closeMenu();
      });

      trigger.addEventListener('click', toggleMenu);

      document.addEventListener('click', function(e) {
        if (!wrap.contains(e.target)) closeMenu();
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initWhoisCRMNav);
  } else {
    initWhoisCRMNav();
  }
})();
</script>
