<?php
/**
 * Template: Customer Portal Dashboard (Home)
 *
 * Variables:
 *  $customer         object
 *  $subscriptions    array   Active subscriptions list
 *  $downloads        array   Recent download history list
 *  $total_downloads  int     Downloads count
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

$wp_user = get_userdata($customer->user_id);
?>

<div class="whoiscrm-portal-greeting">
  <h3>
    <?php printf(esc_html__('Welcome back, %s!', 'whois-crm'), esc_html($wp_user ? ($wp_user->first_name ?: $wp_user->display_name) : 'User')); ?>
  </h3>
  <p>
    <?php esc_html_e('Manage your subscriptions and download your customized WHOIS dataset dumps.', 'whois-crm'); ?>
  </p>
</div>

<!-- Stats Grid (Responsive Auto-Fit) -->
<div class="whoiscrm-portal-stats">
  
  <div class="whoiscrm-portal-stat-card">
    <div class="whoiscrm-portal-stat-info">
      <span class="whoiscrm-portal-stat-label"><?php esc_html_e('Active Subscriptions', 'whois-crm'); ?></span>
      <span class="whoiscrm-portal-stat-value"><?php echo count($subscriptions); ?></span>
    </div>
    <div class="whoiscrm-portal-stat-icon" aria-hidden="true">
      <svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
    </div>
  </div>

  <div class="whoiscrm-portal-stat-card">
    <div class="whoiscrm-portal-stat-info">
      <span class="whoiscrm-portal-stat-label"><?php esc_html_e('Daily Download Limit', 'whois-crm'); ?></span>
      <span class="whoiscrm-portal-stat-value"><?php echo (int) get_option('whoiscrm_download_rate_limit', 50); ?></span>
    </div>
    <div class="whoiscrm-portal-stat-icon" aria-hidden="true">
      <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
    </div>
  </div>

  <div class="whoiscrm-portal-stat-card">
    <div class="whoiscrm-portal-stat-info">
      <span class="whoiscrm-portal-stat-label"><?php esc_html_e('Downloads Today', 'whois-crm'); ?></span>
      <span class="whoiscrm-portal-stat-value"><?php echo $total_downloads; ?></span>
    </div>
    <div class="whoiscrm-portal-stat-icon" aria-hidden="true">
      <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
    </div>
  </div>

</div>

<!-- Responsive Grid Split Row (2-Col Desktop → 1-Col Mobile) -->
<div class="whoiscrm-portal-grid-2">

  <!-- Active Subscriptions -->
  <div class="whoiscrm-table-wrapper">
    <div class="whoiscrm-card__header">
      <h4><?php esc_html_e('Your Active Services', 'whois-crm'); ?></h4>
    </div>
    
    <div style="padding: 20px;">
      <?php if (empty($subscriptions)) : ?>
        <div style="text-align: center; padding: 24px 16px; color: var(--color-text-muted);">
          <p style="margin: 0 0 16px 0; font-size: 0.9375rem;"><?php esc_html_e('You do not have any active subscriptions.', 'whois-crm'); ?></p>
          <a href="<?php echo esc_url(get_permalink(get_option('whoiscrm_pricing_page_id'))); ?>" class="whoiscrm-btn whoiscrm-btn--primary whoiscrm-btn--sm">
            <?php esc_html_e('View Pricing & Plans', 'whois-crm'); ?>
          </a>
        </div>
      <?php else : ?>
        <div style="display: flex; flex-direction: column; gap: 12px;">
          <?php foreach ($subscriptions as $sub) : ?>
            <div style="border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: 16px; display: flex; justify-content: space-between; align-items: center; background: #fff;">
              <div>
                <strong style="display: block; color: var(--color-text-primary); font-size: 0.9375rem; margin-bottom: 2px;"><?php echo esc_html($sub->package_name); ?></strong>
                <span style="font-size: 0.8125rem; color: var(--color-text-muted);">
                  <?php printf(esc_html__('Renews on %s', 'whois-crm'), esc_html(gmdate('Y-m-d', strtotime($sub->expires_at)))); ?>
                </span>
              </div>
              <span class="whoiscrm-badge whoiscrm-badge--success">
                <?php echo esc_html($sub->status); ?>
              </span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Recent Downloads (Responsive Touch-Scroll Table) -->
  <div class="whoiscrm-table-wrapper">
    <div class="whoiscrm-card__header">
      <h4><?php esc_html_e('Recent Downloads', 'whois-crm'); ?></h4>
    </div>
    
    <div class="whoiscrm-table-responsive">
      <table class="whoiscrm-table">
        <thead>
          <tr>
            <th><?php esc_html_e('File Name', 'whois-crm'); ?></th>
            <th><?php esc_html_e('Downloaded At', 'whois-crm'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($downloads)) : ?>
            <tr>
              <td colspan="2" style="text-align: center; padding: 32px 16px; color: var(--color-text-muted);">
                <?php esc_html_e('No download history recorded yet.', 'whois-crm'); ?>
              </td>
            </tr>
          <?php else : ?>
            <?php foreach ($downloads as $d) : ?>
              <tr>
                <td>
                  <span style="font-weight: 600; color: var(--color-text-primary);"><?php echo esc_html($d->original_filename); ?></span>
                </td>
                <td style="font-size: 0.8125rem; color: var(--color-text-muted); white-space: nowrap;">
                  <?php echo esc_html($d->downloaded_at); ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>
