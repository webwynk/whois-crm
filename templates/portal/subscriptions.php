<?php
/**
 * Template: Customer Portal Subscriptions Tab
 *
 * Variables:
 *  $subscriptions array Active & past subscription objects
 *  $customer      object Customer object
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
?>

<div class="whoiscrm-portal-greeting">
  <h3><?php esc_html_e('Your Subscriptions', 'whois-crm'); ?></h3>
  <p><?php esc_html_e('Manage your active WHOIS data subscription plans and billing cycles.', 'whois-crm'); ?></p>
</div>

<?php if (empty($subscriptions)) : ?>
  <div class="whoiscrm-table-wrapper" style="padding: 48px 24px; text-align: center;">
    <p style="margin: 0 0 16px 0; color: var(--color-text-muted); font-size: 0.9375rem;">
      <?php esc_html_e('You do not have any active or past subscriptions yet.', 'whois-crm'); ?>
    </p>
    <a href="<?php echo esc_url(get_permalink(get_option('whoiscrm_pricing_page_id'))); ?>" class="whoiscrm-btn whoiscrm-btn--primary whoiscrm-btn--md">
      <?php esc_html_e('Explore Subscription Plans', 'whois-crm'); ?>
    </a>
  </div>
<?php else : ?>
  <div class="whoiscrm-portal-subs-grid">
    <?php foreach ($subscriptions as $sub) :
      $is_active = in_array(strtolower($sub->status), ['active', 'trialing'], true);
      $badge_cls = $is_active ? 'whoiscrm-badge--success' : 'whoiscrm-badge--warning';
    ?>
      <div class="whoiscrm-portal-sub-card">
        <div>
          <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
            <h4 style="margin: 0; font-size: 1.125rem; font-weight: 700; color: var(--color-text-primary);"><?php echo esc_html($sub->package_name); ?></h4>
            <span class="whoiscrm-badge <?php echo esc_attr($badge_cls); ?>"><?php echo esc_html($sub->status); ?></span>
          </div>

          <div style="margin-bottom: 16px; font-size: 0.875rem; color: var(--color-text-secondary); line-height: 1.6;">
            <div><strong><?php esc_html_e('Plan Type:', 'whois-crm'); ?></strong> <?php echo esc_html(ucfirst($sub->billing_cycle ?? 'monthly')); ?></div>
            <div><strong><?php esc_html_e('Expires / Renews:', 'whois-crm'); ?></strong> <?php echo esc_html(gmdate('F j, Y', strtotime($sub->expires_at))); ?></div>
          </div>
        </div>

        <div style="display: flex; gap: 8px; margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--color-border);">
          <a href="<?php echo esc_url(add_query_arg(['tab' => 'downloads'], get_permalink())); ?>" class="whoiscrm-btn whoiscrm-btn--primary whoiscrm-btn--sm" style="flex: 1;">
            <?php esc_html_e('View Files', 'whois-crm'); ?>
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
