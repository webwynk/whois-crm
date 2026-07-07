<?php
/**
 * Template: Admin Dashboard
 *
 * Variables:
 *  $total_customers   int
 *  $active_subs       int
 *  $month_revenue     float
 *  $downloads_today   int
 *  $recent_customers  array
 *  $recent_payments   array
 *  $revenue_7d        string  JSON array [{date, amount}]
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

$currency = '$';
?>

<!-- ── Stat Cards ─────────────────────────────────────────────── -->
<div class="whoiscrm-stats-grid">

  <div class="whoiscrm-stat-card">
    <div class="whoiscrm-stat-card__label"><?php esc_html_e('Total Customers', 'whois-crm'); ?></div>
    <div class="whoiscrm-stat-card__value" data-countup="true">
      <?php echo number_format($total_customers); ?>
    </div>
    <div class="whoiscrm-stat-card__sub"><?php esc_html_e('All time registered', 'whois-crm'); ?></div>
  </div>

  <div class="whoiscrm-stat-card">
    <div class="whoiscrm-stat-card__label"><?php esc_html_e('Active Subscriptions', 'whois-crm'); ?></div>
    <div class="whoiscrm-stat-card__value" data-countup="true">
      <?php echo number_format($active_subs); ?>
    </div>
    <div class="whoiscrm-stat-card__sub"><?php esc_html_e('Active + Trialing', 'whois-crm'); ?></div>
  </div>

  <div class="whoiscrm-stat-card">
    <div class="whoiscrm-stat-card__label"><?php esc_html_e('Revenue This Month', 'whois-crm'); ?></div>
    <div class="whoiscrm-stat-card__value" data-countup="true">
      <?php echo $currency . number_format($month_revenue, 2); ?>
    </div>
    <div class="whoiscrm-stat-card__sub">
      <?php echo esc_html(gmdate('F Y')); ?>
    </div>
  </div>

  <div class="whoiscrm-stat-card">
    <div class="whoiscrm-stat-card__label"><?php esc_html_e("Downloads Today", 'whois-crm'); ?></div>
    <div class="whoiscrm-stat-card__value" data-countup="true">
      <?php echo number_format($downloads_today); ?>
    </div>
    <div class="whoiscrm-stat-card__sub"><?php esc_html_e('Files served today', 'whois-crm'); ?></div>
  </div>

</div>

<!-- ── Revenue Sparkline + Recent Tables ─────────────────────── -->
<div style="display:grid; grid-template-columns: 1fr 1fr; gap: var(--space-5); margin-bottom: var(--space-5);">

  <!-- Revenue 7-day chart -->
  <div class="whoiscrm-card">
    <div class="whoiscrm-card__header">
      <h3 class="whoiscrm-card__title"><?php esc_html_e('Revenue — Last 7 Days', 'whois-crm'); ?></h3>
    </div>
    <div class="whoiscrm-card__body" style="padding: var(--space-4);">
      <canvas
        id="whoiscrm-revenue-sparkline"
        width="480"
        height="120"
        data-values="<?php echo esc_attr($revenue_7d); ?>"
        style="width:100%; height:120px;"
      ></canvas>
    </div>
  </div>

  <!-- Quick links -->
  <div class="whoiscrm-card">
    <div class="whoiscrm-card__header">
      <h3 class="whoiscrm-card__title"><?php esc_html_e('Quick Actions', 'whois-crm'); ?></h3>
    </div>
    <div class="whoiscrm-card__body">
      <div style="display:grid; grid-template-columns: 1fr 1fr; gap: var(--space-3);">
        <a href="<?php echo esc_url(admin_url('admin.php?page=whoiscrm-upload')); ?>" class="whoiscrm-btn whoiscrm-btn--secondary whoiscrm-btn--md" style="justify-content:flex-start;">
          📤 <?php esc_html_e('Upload Data', 'whois-crm'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=whoiscrm-customers')); ?>" class="whoiscrm-btn whoiscrm-btn--ghost whoiscrm-btn--md" style="justify-content:flex-start;">
          👥 <?php esc_html_e('Customers', 'whois-crm'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=whoiscrm-packages')); ?>" class="whoiscrm-btn whoiscrm-btn--ghost whoiscrm-btn--md" style="justify-content:flex-start;">
          📦 <?php esc_html_e('Packages', 'whois-crm'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=whoiscrm-reports')); ?>" class="whoiscrm-btn whoiscrm-btn--ghost whoiscrm-btn--md" style="justify-content:flex-start;">
          📊 <?php esc_html_e('Reports', 'whois-crm'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=whoiscrm-coupons')); ?>" class="whoiscrm-btn whoiscrm-btn--ghost whoiscrm-btn--md" style="justify-content:flex-start;">
          🎟 <?php esc_html_e('Coupons', 'whois-crm'); ?>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=whoiscrm-settings')); ?>" class="whoiscrm-btn whoiscrm-btn--ghost whoiscrm-btn--md" style="justify-content:flex-start;">
          ⚙️ <?php esc_html_e('Settings', 'whois-crm'); ?>
        </a>
      </div>
    </div>
  </div>

</div>

<!-- ── Recent Activity Tables ─────────────────────────────────── -->
<div style="display:grid; grid-template-columns: 1fr 1fr; gap: var(--space-5);">

  <!-- Recent customers -->
  <div class="whoiscrm-table-wrapper">
    <div class="whoiscrm-card__header">
      <span class="whoiscrm-card__title"><?php esc_html_e('Recent Customers', 'whois-crm'); ?></span>
      <a href="<?php echo esc_url(admin_url('admin.php?page=whoiscrm-customers')); ?>" style="font-size:.8125rem; color:var(--color-primary);"><?php esc_html_e('View all →', 'whois-crm'); ?></a>
    </div>
    <table class="whoiscrm-table">
      <thead>
        <tr>
          <th><?php esc_html_e('Name', 'whois-crm'); ?></th>
          <th><?php esc_html_e('Email', 'whois-crm'); ?></th>
          <th><?php esc_html_e('Status', 'whois-crm'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($recent_customers)) : ?>
          <tr><td colspan="3" style="text-align:center; color:var(--color-text-muted); padding: var(--space-8);">
            <?php esc_html_e('No customers yet.', 'whois-crm'); ?>
          </td></tr>
        <?php else : ?>
          <?php foreach ($recent_customers as $c) : ?>
          <tr>
            <td>
              <a href="<?php echo esc_url(admin_url('admin.php?page=whoiscrm-customers&view=' . (int) $c->id)); ?>" style="color:var(--color-text-primary); font-weight:500; text-decoration:none;">
                <?php echo esc_html(trim($c->first_name . ' ' . $c->last_name) ?: __('(no name)', 'whois-crm')); ?>
              </a>
            </td>
            <td style="color:var(--color-text-secondary); font-size:.8125rem;"><?php echo esc_html($c->email); ?></td>
            <td>
              <?php if ($c->is_active) : ?>
                <span class="whoiscrm-badge whoiscrm-badge--success"><?php esc_html_e('Active', 'whois-crm'); ?></span>
              <?php else : ?>
                <span class="whoiscrm-badge whoiscrm-badge--danger"><?php esc_html_e('Blocked', 'whois-crm'); ?></span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Recent payments -->
  <div class="whoiscrm-table-wrapper">
    <div class="whoiscrm-card__header">
      <span class="whoiscrm-card__title"><?php esc_html_e('Recent Payments', 'whois-crm'); ?></span>
      <a href="<?php echo esc_url(admin_url('admin.php?page=whoiscrm-payments')); ?>" style="font-size:.8125rem; color:var(--color-primary);"><?php esc_html_e('View all →', 'whois-crm'); ?></a>
    </div>
    <table class="whoiscrm-table">
      <thead>
        <tr>
          <th><?php esc_html_e('Customer', 'whois-crm'); ?></th>
          <th><?php esc_html_e('Plan', 'whois-crm'); ?></th>
          <th><?php esc_html_e('Amount', 'whois-crm'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($recent_payments)) : ?>
          <tr><td colspan="3" style="text-align:center; color:var(--color-text-muted); padding: var(--space-8);">
            <?php esc_html_e('No payments yet.', 'whois-crm'); ?>
          </td></tr>
        <?php else : ?>
          <?php foreach ($recent_payments as $p) : ?>
          <tr>
            <td style="font-size:.8125rem;"><?php echo esc_html($p->customer_email); ?></td>
            <td style="font-size:.8125rem; color:var(--color-text-secondary);"><?php echo esc_html($p->package_name); ?></td>
            <td style="font-weight:600;"><?php echo esc_html($currency . number_format((float) $p->total_amount, 2)); ?></td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>
