<?php
/**
 * Template: Customer Portal Subscriptions List
 *
 * Variables:
 *  $subscriptions  array   List of all customer subscriptions (active + inactive)
 *  $nonce          string  Cancel AJAX action nonce
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

$currency_symbol = '$';
?>

<div style="margin-bottom: var(--space-6); display: flex; justify-content: space-between; align-items: flex-end;">
  <div>
    <h3 style="margin: 0 0 var(--space-1) 0; font-size: var(--text-h2); font-weight: 700; color: var(--color-black);">
      <?php esc_html_e('Your Subscriptions', 'whois-crm'); ?>
    </h3>
    <p style="margin: 0; color: var(--color-text-secondary); font-size: 0.9375rem;">
      <?php esc_html_e('Review your package subscription histories, renewal cycles, and cancellation preferences.', 'whois-crm'); ?>
    </p>
  </div>
  <a href="<?php echo esc_url(get_permalink(get_option('whoiscrm_pricing_page_id'))); ?>" class="whoiscrm-btn whoiscrm-btn--primary whoiscrm-btn--sm">
    <?php esc_html_e('+ Subscribe to New Plan', 'whois-crm'); ?>
  </a>
</div>

<div class="whoiscrm-portal-subs-grid">
  <?php if (empty($subscriptions)) : ?>
    <div class="whoiscrm-card" style="grid-column: span 2; text-align: center; padding: var(--space-10); color: var(--color-text-muted);">
      <?php esc_html_e('You do not have any subscription history.', 'whois-crm'); ?>
    </div>
  <?php else : ?>
    <?php foreach ($subscriptions as $sub) :
      $is_active = in_array($sub->status, ['active', 'trialing'], true);
      $badge_class = 'whoiscrm-badge--muted';
      if ($sub->status === 'active') {
          $badge_class = 'whoiscrm-badge--success';
      } elseif ($sub->status === 'trialing') {
          $badge_class = 'whoiscrm-badge--info';
      } elseif ($sub->status === 'past_due') {
          $badge_class = 'whoiscrm-badge--warning';
      } elseif ($sub->status === 'cancelled' || $sub->status === 'expired') {
          $badge_class = 'whoiscrm-badge--danger';
      }
      ?>
      
      <div class="whoiscrm-portal-sub-card">
        <div>
          <div class="whoiscrm-portal-sub-header">
            <h4 class="whoiscrm-portal-sub-title"><?php echo esc_html($sub->package_name); ?></h4>
            <span class="whoiscrm-badge <?php echo esc_attr($badge_class); ?>">
              <?php echo esc_html($sub->status); ?>
            </span>
          </div>

          <div class="whoiscrm-portal-sub-meta">
            <div>
              <strong><?php esc_html_e('Billing Cycle:', 'whois-crm'); ?></strong>
              <span style="text-transform: capitalize;"><?php echo esc_html($sub->billing_cycle); ?></span>
            </div>
            <div>
              <strong><?php esc_html_e('Price:', 'whois-crm'); ?></strong>
              <span><?php echo esc_html($currency_symbol . number_format((float)$sub->price, 2)); ?></span>
            </div>
            <div style="margin-top: var(--space-2); padding-top: var(--space-2); border-top: 1px solid var(--color-border); font-size: 0.8125rem; color: var(--color-text-secondary);">
              <div>
                <strong><?php esc_html_e('Subscribed At:', 'whois-crm'); ?></strong>
                <span><?php echo esc_html(gmdate('Y-m-d', strtotime($sub->starts_at))); ?></span>
              </div>
              <div>
                <strong><?php echo $is_active ? esc_html__('Next Renewal:', 'whois-crm') : esc_html__('Expired At:', 'whois-crm'); ?></strong>
                <span><?php echo esc_html(gmdate('Y-m-d', strtotime($sub->expires_at))); ?></span>
              </div>
              <?php if ($sub->cancelled_at) : ?>
                <div style="color: var(--color-danger); margin-top: 4px;">
                  <strong><?php esc_html_e('Cancelled At:', 'whois-crm'); ?></strong>
                  <span><?php echo esc_html(gmdate('Y-m-d', strtotime($sub->cancelled_at))); ?></span>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <?php if ($is_active) : ?>
          <div style="margin-top: var(--space-4); border-top: 1px solid var(--color-border); padding-top: var(--space-4); text-align: right;">
            <button
              type="button"
              class="whoiscrm-btn whoiscrm-btn--ghost whoiscrm-btn--sm js-cancel-sub-btn"
              data-sub-id="<?php echo (int) $sub->id; ?>"
              data-nonce="<?php echo esc_attr($nonce); ?>"
              style="color: var(--color-danger); border-color: rgba(229,53,53,0.3);"
            >
              🛑 <?php esc_html_e('Cancel Subscription', 'whois-crm'); ?>
            </button>
          </div>
        <?php endif; ?>
      </div>

    <?php endforeach; ?>
  <?php endif; ?>
</div>
