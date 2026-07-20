<?php
/**
 * Template: Customer Portal Wrapper Layout
 *
 * Variables:
 *  $customer    object
 *  $tabs        array   ['tab_key' => 'Label']
 *  $active_tab  string
 *  $content     string  Inner HTML content of the active tab
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

$wp_user = get_userdata($customer->user_id);
$initials = '';
if ($wp_user) {
    $first = get_user_meta($customer->user_id, 'first_name', true);
    $last  = get_user_meta($customer->user_id, 'last_name', true);
    if ($first || $last) {
        $initials = strtoupper(substr((string)$first, 0, 1) . substr((string)$last, 0, 1));
    } else {
        $initials = strtoupper(substr($wp_user->display_name, 0, 2));
    }
}

// Icons mapping for nav tabs
$tab_icons = [
    'dashboard'     => '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>',
    'downloads'     => '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>',
    'subscriptions' => '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>',
    'invoices'      => '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>',
    'profile'       => '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
    'api_keys'      => '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>',
];
?>

<div class="whoiscrm-portal">
  <div class="whoiscrm-portal-container">
    
    <!-- Header Bar -->
    <header class="whoiscrm-portal-header">
      <div class="whoiscrm-portal-brand">
        <div class="whoiscrm-portal-brand-logo" aria-hidden="true">
          <svg viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10"/>
            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
            <path d="M2 12h20"/>
          </svg>
        </div>
        <h2 class="whoiscrm-portal-brand-name">
          <?php echo esc_html(get_bloginfo('name')); ?> <?php esc_html_e('Portal', 'whois-crm'); ?>
        </h2>
      </div>
      
      <div class="whoiscrm-portal-user-meta">
        <div class="whoiscrm-portal-user-avatar">
          <?php echo esc_html($initials); ?>
        </div>
        <div class="whoiscrm-portal-user-info">
          <span class="whoiscrm-portal-user-name">
            <?php echo esc_html($wp_user ? ($wp_user->first_name ? $wp_user->first_name . ' ' . $wp_user->last_name : $wp_user->display_name) : __('Customer', 'whois-crm')); ?>
          </span>
          <span class="whoiscrm-portal-user-role">
            <?php esc_html_e('Subscribed Member', 'whois-crm'); ?>
          </span>
        </div>
        <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="whoiscrm-portal-logout-btn">
          <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
          <span><?php esc_html_e('Logout', 'whois-crm'); ?></span>
        </a>
      </div>
    </header>

    <!-- Navigation Tabs (Horizontally Scrollable on Mobile) -->
    <nav class="whoiscrm-portal-nav" aria-label="<?php esc_attr_e('Portal navigation', 'whois-crm'); ?>">
      <?php foreach ($tabs as $key => $label) : ?>
        <a
          href="<?php echo esc_url(add_query_arg('tab', $key, get_permalink())); ?>"
          class="whoiscrm-portal-nav-item <?php echo $key === $active_tab ? 'is-active' : ''; ?>"
          aria-current="<?php echo $key === $active_tab ? 'page' : 'false'; ?>"
        >
          <?php if (isset($tab_icons[$key])) { echo $tab_icons[$key]; } ?>
          <span><?php echo esc_html($label); ?></span>
        </a>
      <?php endforeach; ?>
    </nav>

    <!-- Inner Page Content -->
    <main class="whoiscrm-portal-content-box">
      <?php echo $content; // phpcs:ignore WordPress.Security.OutputNotEscaped ?>
    </main>

  </div><!-- .whoiscrm-portal-container -->
</div><!-- .whoiscrm-portal -->
