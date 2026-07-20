<?php
/**
 * Template: Navigation Menu — Logged In (Member) State
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
      <div style="overflow: hidden;">
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
  document.addEventListener('DOMContentLoaded', function() {
    const triggers = document.querySelectorAll('.js-whoiscrm-user-trigger');
    
    triggers.forEach(trigger => {
      const wrap = trigger.closest('.whoiscrm-nav-user-wrap');
      if (!wrap) return;
      const dropdown = wrap.querySelector('.js-whoiscrm-user-dropdown');

      function openMenu() {
        wrap.classList.add('is-open');
        trigger.setAttribute('aria-expanded', 'true');
      }

      function closeMenu() {
        wrap.classList.remove('is-open');
        trigger.setAttribute('aria-expanded', 'false');
      }

      function toggleMenu(e) {
        e.stopPropagation();
        if (wrap.classList.contains('is-open')) {
          closeMenu();
        } else {
          openMenu();
        }
      }

      // Hover on desktop
      wrap.addEventListener('mouseenter', function() {
        if (window.innerWidth >= 768) openMenu();
      });
      wrap.addEventListener('mouseleave', function() {
        if (window.innerWidth >= 768) closeMenu();
      });

      // Click for mobile / touch
      trigger.addEventListener('click', toggleMenu);

      // Close on outside click
      document.addEventListener('click', function(e) {
        if (!wrap.contains(e.target)) closeMenu();
      });
    });
  });
})();
</script>
