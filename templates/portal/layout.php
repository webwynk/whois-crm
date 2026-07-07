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
?>

<div class="whoiscrm-portal">
  
  <!-- Header Bar -->
  <header class="whoiscrm-portal-header">
    <div>
      <h2 style="margin: 0; font-size: var(--text-h2); font-weight: 700; color: var(--color-primary);">
        <?php echo esc_html(get_bloginfo('name')); ?> <?php esc_html_e('Portal', 'whois-crm'); ?>
      </h2>
    </div>
    
    <div class="whoiscrm-portal-user-meta">
      <div class="whoiscrm-portal-user-avatar">
        <?php echo esc_html($initials); ?>
      </div>
      <div class="whoiscrm-portal-user-info">
        <span class="whoiscrm-portal-user-name">
          <?php echo esc_html($wp_user ? $wp_user->display_name : __('Customer', 'whois-crm')); ?>
        </span>
        <span class="whoiscrm-portal-user-role">
          <?php esc_html_e('Subscribed Member', 'whois-crm'); ?>
        </span>
      </div>
      <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="whoiscrm-btn whoiscrm-btn--ghost whoiscrm-btn--sm" style="margin-left: var(--space-3);">
        <?php esc_html_e('Logout', 'whois-crm'); ?>
      </a>
    </div>
  </header>

  <!-- Navigation Tabs -->
  <nav class="whoiscrm-portal-nav" aria-label="<?php esc_attr_e('Portal navigation', 'whois-crm'); ?>">
    <?php foreach ($tabs as $key => $label) : ?>
      <a
        href="<?php echo esc_url(add_query_arg('tab', $key, get_permalink())); ?>"
        class="whoiscrm-portal-nav-item <?php echo $key === $active_tab ? 'is-active' : ''; ?>"
        aria-current="<?php echo $key === $active_tab ? 'page' : 'false'; ?>"
      >
        <?php echo esc_html($label); ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <!-- Inner Page Content -->
  <main class="whoiscrm-portal-content-box" style="animation: fadeIn var(--duration-normal) var(--ease-out);">
    <?php echo $content; // phpcs:ignore WordPress.Security.OutputNotEscaped ?>
  </main>

</div>
