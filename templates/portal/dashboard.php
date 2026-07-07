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

<div style="margin-bottom: var(--space-6);">
  <h3 style="margin: 0 0 var(--space-1) 0; font-size: var(--text-h2); font-weight: 700; color: var(--color-black);">
    <?php printf(esc_html__('Welcome back, %s!', 'whois-crm'), esc_html($wp_user ? ($wp_user->first_name ?: $wp_user->display_name) : 'User')); ?>
  </h3>
  <p style="margin: 0; color: var(--color-text-secondary); font-size: 0.9375rem;">
    <?php esc_html_e('Manage your subscriptions and download your customized WHOIS dataset dumps.', 'whois-crm'); ?>
  </p>
</div>

<!-- Stats Grid -->
<div class="whoiscrm-portal-stats">
  
  <div class="whoiscrm-portal-stat-card">
    <span class="whoiscrm-portal-stat-label"><?php esc_html_e('Active Subscriptions', 'whois-crm'); ?></span>
    <span class="whoiscrm-portal-stat-value">
      <?php echo count($subscriptions); ?>
    </span>
  </div>

  <div class="whoiscrm-portal-stat-card">
    <span class="whoiscrm-portal-stat-label"><?php esc_html_e('Daily Download Limit', 'whois-crm'); ?></span>
    <span class="whoiscrm-portal-stat-value">
      <?php echo (int) get_option('whoiscrm_download_rate_limit', 50); ?>
    </span>
  </div>

  <div class="whoiscrm-portal-stat-card">
    <span class="whoiscrm-portal-stat-label"><?php esc_html_e('Total Downloads Today', 'whois-crm'); ?></span>
    <span class="whoiscrm-portal-stat-value">
      <?php echo $total_downloads; ?>
    </span>
  </div>

</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); align-items: flex-start;">

  <!-- Active Subscriptions -->
  <div class="whoiscrm-table-wrapper">
    <div class="whoiscrm-card__header" style="background: var(--color-surface); padding: var(--space-4) var(--space-5); border-bottom: 1px solid var(--color-border);">
      <h4 style="margin: 0; font-size: 0.9375rem; font-weight: 600; color: var(--color-text-primary);"><?php esc_html_e('Your Active Services', 'whois-crm'); ?></h4>
    </div>
    
    <div style="padding: var(--space-4);">
      <?php if (empty($subscriptions)) : ?>
        <p style="text-align: center; padding: var(--space-6); color: var(--color-text-muted); margin: 0;">
          <?php esc_html_e('You do not have any active subscriptions.', 'whois-crm'); ?>
          <br><br>
          <a href="<?php echo esc_url(get_permalink(get_option('whoiscrm_pricing_page_id'))); ?>" class="whoiscrm-btn whoiscrm-btn--primary whoiscrm-btn--sm">
            <?php esc_html_e('View Pricing & Plans', 'whois-crm'); ?>
          </a>
        </p>
      <?php else : ?>
        <div style="display: flex; flex-direction: column; gap: var(--space-3);">
          <?php foreach ($subscriptions as $sub) : ?>
            <div style="border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: var(--space-4); display: flex; justify-content: space-between; align-items: center;">
              <div>
                <strong style="display: block; color: var(--color-text-primary);"><?php echo esc_html($sub->package_name); ?></strong>
                <span style="font-size: 0.8125rem; color: var(--color-text-muted);">
                  <?php printf(esc_html__('Renews on %s', 'whois-crm'), esc_html(gmdate('Y-m-d', strtotime($sub->expires_at)))); ?>
                </span>
              </div>
              <span class="whoiscrm-badge whoiscrm-badge--success" style="font-size: 0.6875rem;">
                <?php echo esc_html($sub->status); ?>
              </span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Recent Downloads -->
  <div class="whoiscrm-table-wrapper">
    <div class="whoiscrm-card__header" style="background: var(--color-surface); padding: var(--space-4) var(--space-5); border-bottom: 1px solid var(--color-border);">
      <h4 style="margin: 0; font-size: 0.9375rem; font-weight: 600; color: var(--color-text-primary);"><?php esc_html_e('Recent Downloads', 'whois-crm'); ?></h4>
    </div>
    
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
            <td colspan="2" style="text-align: center; padding: var(--space-8); color: var(--color-text-muted);">
              <?php esc_html_e('No download history recorded yet.', 'whois-crm'); ?>
            </td>
          </tr>
        <?php else : ?>
          <?php foreach ($downloads as $d) : ?>
            <tr>
              <td>
                <span style="font-weight: 500;"><?php echo esc_html($d->original_filename); ?></span>
              </td>
              <td style="font-size: 0.8125rem; color: var(--color-text-muted);">
                <?php echo esc_html($d->downloaded_at); ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>
