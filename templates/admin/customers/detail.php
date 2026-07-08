<?php
/**
 * Template: Admin Customer Detail
 *
 * Variables:
 *  $customer       object   Customer row (with WP user data merged)
 *  $subscriptions  array    Subscription rows for this customer
 *  $nonce          string   Security nonce
 *  $back_url       string   URL to customers list
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

$full_name = trim(($customer->first_name ?? '') . ' ' . ($customer->last_name ?? ''));
$is_active = !empty($customer->is_active);
?>

<a href="<?php echo esc_url($back_url); ?>" class="whoiscrm-btn whoiscrm-btn--ghost whoiscrm-btn--sm" style="margin-bottom:var(--space-5); display:inline-block;">
  ← <?php esc_html_e('Back to Customers', 'whois-crm'); ?>
</a>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:var(--space-5); align-items:start;">

  <!-- Profile Card -->
  <div class="whoiscrm-card">
    <div class="whoiscrm-card__header"><?php esc_html_e('Customer Profile', 'whois-crm'); ?></div>
    <div class="whoiscrm-card__body">
      <table class="whoiscrm-detail-table" style="width:100%; border-collapse:collapse;">
        <?php
        $fields = [
            __('ID', 'whois-crm')           => '#' . (int) $customer->id,
            __('Name', 'whois-crm')          => $full_name ?: '—',
            __('Email', 'whois-crm')         => $customer->email ?? '—',
            __('Company', 'whois-crm')       => $customer->company ?? '—',
            __('Phone', 'whois-crm')         => $customer->phone ?? '—',
            __('Country', 'whois-crm')       => $customer->country ?? '—',
            __('VAT / Tax ID', 'whois-crm')  => $customer->tax_id ?? '—',
            __('API Key', 'whois-crm')       => $customer->api_key ? '<code>' . esc_html($customer->api_key) . '</code>' : '—',
            __('Status', 'whois-crm')        => $is_active
                ? '<span class="whoiscrm-badge whoiscrm-badge--success">' . esc_html__('Active', 'whois-crm') . '</span>'
                : '<span class="whoiscrm-badge whoiscrm-badge--danger">' . esc_html__('Blocked', 'whois-crm') . '</span>',
            __('Registered', 'whois-crm')    => $customer->created_at ? esc_html(gmdate('Y-m-d H:i', strtotime($customer->created_at))) : '—',
        ];
        foreach ($fields as $label => $value) : ?>
          <tr style="border-bottom:1px solid var(--color-border);">
            <th style="text-align:left; padding:var(--space-2) var(--space-3) var(--space-2) 0; font-weight:600; font-size:0.8125rem; color:var(--color-text-secondary); white-space:nowrap; width:140px;">
              <?php echo esc_html($label); ?>
            </th>
            <td style="padding:var(--space-2) 0; font-size:0.9375rem; color:var(--color-text-primary);">
              <?php echo $value; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>

      <!-- Block / Unblock -->
      <div style="margin-top:var(--space-5); display:flex; gap:var(--space-3);">
        <form method="post">
          <?php wp_nonce_field('whoiscrm_customer_action'); ?>
          <input type="hidden" name="whoiscrm_action" value="<?php echo $is_active ? 'block' : 'unblock'; ?>">
          <input type="hidden" name="customer_id" value="<?php echo (int) $customer->id; ?>">
          <button
            type="submit"
            class="whoiscrm-btn whoiscrm-btn--md <?php echo $is_active ? 'whoiscrm-btn--ghost' : 'whoiscrm-btn--primary'; ?>"
            style="<?php echo $is_active ? 'color:var(--color-danger);' : ''; ?>"
          >
            <?php echo $is_active ? esc_html__('Block Customer', 'whois-crm') : esc_html__('Unblock Customer', 'whois-crm'); ?>
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Subscriptions Card -->
  <div class="whoiscrm-card">
    <div class="whoiscrm-card__header"><?php esc_html_e('Subscriptions', 'whois-crm'); ?></div>
    <div class="whoiscrm-card__body" style="padding:0;">
      <?php if (empty($subscriptions)) : ?>
        <p style="padding:var(--space-5); color:var(--color-text-muted); text-align:center; margin:0;">
          <?php esc_html_e('No subscriptions found.', 'whois-crm'); ?>
        </p>
      <?php else : ?>
        <table class="whoiscrm-table" style="border-radius:0;">
          <thead>
            <tr>
              <th><?php esc_html_e('Package', 'whois-crm'); ?></th>
              <th><?php esc_html_e('Billing', 'whois-crm'); ?></th>
              <th><?php esc_html_e('Status', 'whois-crm'); ?></th>
              <th><?php esc_html_e('Renews', 'whois-crm'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($subscriptions as $sub) : ?>
              <?php
              $status_classes = [
                  'active'    => 'whoiscrm-badge--success',
                  'cancelled' => 'whoiscrm-badge--danger',
                  'expired'   => 'whoiscrm-badge--warning',
                  'trialing'  => 'whoiscrm-badge--info',
                  'past_due'  => 'whoiscrm-badge--warning',
              ];
              $sc = $status_classes[$sub->status ?? ''] ?? 'whoiscrm-badge--ghost';
              ?>
              <tr>
                <td style="font-size:0.875rem;"><?php echo esc_html($sub->package_name ?? '—'); ?></td>
                <td style="font-size:0.875rem;"><?php echo esc_html(ucfirst($sub->billing_cycle ?? '—')); ?></td>
                <td><span class="whoiscrm-badge <?php echo esc_attr($sc); ?>"><?php echo esc_html(ucfirst(str_replace('_', ' ', $sub->status ?? '—'))); ?></span></td>
                <td style="font-size:0.8125rem; color:var(--color-text-secondary);">
                  <?php echo $sub->current_period_end ? esc_html(gmdate('Y-m-d', strtotime($sub->current_period_end))) : '—'; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>

</div>
