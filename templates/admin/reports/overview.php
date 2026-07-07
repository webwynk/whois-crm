<?php
/**
 * Template: Admin Reports & Analytics Overview
 *
 * Variables:
 *  $from           string  Start date filter (Y-m-d)
 *  $to             string  End date filter (Y-m-d)
 *  $revenue        float   Total revenue in period
 *  $new_customers  int     Total new customers registered in period
 *  $sub_growth     string  JSON data of subscription growth [[day => count]]
 *  $top_plans      array   Top packages by revenue
 *  $top_files      array   Top files downloaded
 *  $top_customers  array   Top active downloaders
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

$currency = '$';
?>

<!-- Date Filter Toolbar -->
<div class="whoiscrm-card" style="margin-bottom: var(--space-6);">
  <div class="whoiscrm-card__body" style="padding: var(--space-4);">
    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" style="display: flex; flex-wrap: wrap; gap: var(--space-4); align-items: flex-end; margin: 0;">
      <input type="hidden" name="page" value="whoiscrm-reports">
      
      <div class="whoiscrm-form-group" style="margin: 0; min-width: 180px;">
        <label class="whoiscrm-form-label" for="from" style="margin-bottom: 4px; font-weight: 600; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;"><?php esc_html_e('Date From', 'whois-crm'); ?></label>
        <input type="date" name="from" id="from" class="whoiscrm-input" value="<?php echo esc_attr($from); ?>" style="height: 36px;">
      </div>

      <div class="whoiscrm-form-group" style="margin: 0; min-width: 180px;">
        <label class="whoiscrm-form-label" for="to" style="margin-bottom: 4px; font-weight: 600; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;"><?php esc_html_e('Date To', 'whois-crm'); ?></label>
        <input type="date" name="to" id="to" class="whoiscrm-input" value="<?php echo esc_attr($to); ?>" style="height: 36px;">
      </div>

      <button type="submit" class="whoiscrm-btn whoiscrm-btn--primary" style="height: 36px; padding: 0 var(--space-4);">
        🔍 <?php esc_html_e('Apply Filters', 'whois-crm'); ?>
      </button>

      <a href="<?php echo esc_url(admin_url('admin.php?page=whoiscrm-reports')); ?>" class="whoiscrm-btn whoiscrm-btn--ghost" style="height: 36px; line-height: 34px; padding: 0 var(--space-4);">
        <?php esc_html_e('Reset', 'whois-crm'); ?>
      </a>
    </form>
  </div>
</div>

<!-- KPI Cards -->
<div class="whoiscrm-stats-grid" style="margin-bottom: var(--space-6);">
  <div class="whoiscrm-stat-card">
    <div class="whoiscrm-stat-card__label"><?php esc_html_e('Period Revenue', 'whois-crm'); ?></div>
    <div class="whoiscrm-stat-card__value"><?php echo $currency . number_format($revenue, 2); ?></div>
    <div class="whoiscrm-stat-card__sub"><?php printf(esc_html__('From %s to %s', 'whois-crm'), esc_html($from), esc_html($to)); ?></div>
  </div>

  <div class="whoiscrm-stat-card">
    <div class="whoiscrm-stat-card__label"><?php esc_html_e('New Customers', 'whois-crm'); ?></div>
    <div class="whoiscrm-stat-card__value"><?php echo number_format($new_customers); ?></div>
    <div class="whoiscrm-stat-card__sub"><?php esc_html_e('Registered in range', 'whois-crm'); ?></div>
  </div>

  <div class="whoiscrm-stat-card">
    <div class="whoiscrm-stat-card__label"><?php esc_html_e('Active Subscriptions', 'whois-crm'); ?></div>
    <div class="whoiscrm-stat-card__value">
      <?php
      global $wpdb;
      echo (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}whoiscrm_subscriptions WHERE status IN ('active','trialing')");
      ?>
    </div>
    <div class="whoiscrm-stat-card__sub"><?php esc_html_e('Current active accounts', 'whois-crm'); ?></div>
  </div>

  <div class="whoiscrm-stat-card">
    <div class="whoiscrm-stat-card__label"><?php esc_html_e('Top Plan Revenue', 'whois-crm'); ?></div>
    <div class="whoiscrm-stat-card__value">
      <?php echo !empty($top_plans) ? $currency . number_format((float)$top_plans[0]->revenue, 2) : '$0.00'; ?>
    </div>
    <div class="whoiscrm-stat-card__sub">
      <?php echo !empty($top_plans) ? esc_html($top_plans[0]->package_name) : __('No sales yet', 'whois-crm'); ?>
    </div>
  </div>
</div>

<!-- Chart JS Library CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- Graphical Analytics Sections -->
<div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: var(--space-6); margin-bottom: var(--space-6);">
  <!-- Chart 1: Subscription Growth -->
  <div class="whoiscrm-card">
    <div class="whoiscrm-card__header"><?php esc_html_e('New Subscription Signups (Daily)', 'whois-crm'); ?></div>
    <div class="whoiscrm-card__body" style="padding: var(--space-4);">
      <div style="position: relative; height: 300px; width: 100%;">
        <canvas id="chart-sub-growth"></canvas>
      </div>
    </div>
  </div>

  <!-- Chart 2: Plan Breakdown -->
  <div class="whoiscrm-card">
    <div class="whoiscrm-card__header"><?php esc_html_e('Plan Breakdown (Revenue)', 'whois-crm'); ?></div>
    <div class="whoiscrm-card__body" style="padding: var(--space-4);">
      <div style="position: relative; height: 300px; width: 100%;">
        <canvas id="chart-plans-breakdown"></canvas>
      </div>
    </div>
  </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6);">
  <!-- Top Downloaded Files -->
  <div class="whoiscrm-card">
    <div class="whoiscrm-card__header"><?php esc_html_e('Top Downloaded Files', 'whois-crm'); ?></div>
    <div class="whoiscrm-card__body" style="padding: 0;">
      <table class="whoiscrm-table" style="box-shadow: none; border-radius: 0;">
        <thead>
          <tr>
            <th><?php esc_html_e('Filename', 'whois-crm'); ?></th>
            <th><?php esc_html_e('Feed Type', 'whois-crm'); ?></th>
            <th style="text-align: right;"><?php esc_html_e('Downloads', 'whois-crm'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($top_files)) : ?>
            <tr>
              <td colspan="3" style="text-align: center; color: var(--color-text-muted); padding: var(--space-6);">
                <?php esc_html_e('No downloads in this period.', 'whois-crm'); ?>
              </td>
            </tr>
          <?php else : ?>
            <?php foreach ($top_files as $file) : ?>
              <tr>
                <td><code style="word-break: break-all;"><?php echo esc_html($file->original_filename); ?></code></td>
                <td><span class="whoiscrm-badge whoiscrm-badge--ghost"><?php echo esc_html(strtoupper(str_replace('_', ' ', $file->service_type))); ?></span></td>
                <td style="text-align: right; font-weight: 600;"><?php echo number_format((int)$file->download_count); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Top Active Downloaders -->
  <div class="whoiscrm-card">
    <div class="whoiscrm-card__header"><?php esc_html_e('Top Customers by Downloads', 'whois-crm'); ?></div>
    <div class="whoiscrm-card__body" style="padding: 0;">
      <table class="whoiscrm-table" style="box-shadow: none; border-radius: 0;">
        <thead>
          <tr>
            <th><?php esc_html_e('Customer / Company', 'whois-crm'); ?></th>
            <th style="text-align: right;"><?php esc_html_e('Downloads', 'whois-crm'); ?></th>
            <th style="text-align: right;"><?php esc_html_e('Volume (MB)', 'whois-crm'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($top_customers)) : ?>
            <tr>
              <td colspan="3" style="text-align: center; color: var(--color-text-muted); padding: var(--space-6);">
                <?php esc_html_e('No customer download activity recorded.', 'whois-crm'); ?>
              </td>
            </tr>
          <?php else : ?>
            <?php foreach ($top_customers as $cust) : ?>
              <tr>
                <td>
                  <strong style="color: var(--color-text-primary);"><?php echo esc_html($cust->company_name ?: __('Individual', 'whois-crm')); ?></strong>
                  <div style="font-size: 0.75rem; color: var(--color-text-secondary);"><?php echo esc_html($cust->user_email); ?></div>
                </td>
                <td style="text-align: right; font-weight: 600;"><?php echo number_format((int)$cust->download_count); ?></td>
                <td style="text-align: right; color: var(--color-text-secondary);">
                  <?php echo number_format((float)$cust->total_bytes / (1024 * 1024), 1); ?> MB
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Chart Configuration Scripts -->
<script>
document.addEventListener("DOMContentLoaded", function() {
  // 1. Subscription growth line chart
  const growthData = <?php echo $sub_growth; ?>;
  const growthLabels = growthData.map(item => item.day);
  const growthCounts = growthData.map(item => parseInt(item.count));

  const ctxGrowth = document.getElementById('chart-sub-growth').getContext('2d');
  new Chart(ctxGrowth, {
    type: 'line',
    data: {
      labels: growthLabels.length ? growthLabels : ['No Data'],
      datasets: [{
        label: '<?php esc_attr_e('New Subscriptions', 'whois-crm'); ?>',
        data: growthCounts.length ? growthCounts : [0],
        borderColor: '#FF6621',
        backgroundColor: 'rgba(255, 102, 33, 0.08)',
        fill: true,
        tension: 0.35,
        borderWidth: 2,
        pointBackgroundColor: '#FF6621',
        pointRadius: 4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { stepSize: 1, color: '#6A6A75' },
          grid: { color: '#E8E8EF' }
        },
        x: {
          ticks: { color: '#6A6A75' },
          grid: { display: false }
        }
      }
    }
  });

  // 2. Plan breakdown bar/doughnut chart
  const planNames = [<?php echo implode(',', array_map(fn($p) => '"' . esc_js($p->package_name) . '"', $top_plans)); ?>];
  const planRevenues = [<?php echo implode(',', array_map(fn($p) => (float)$p->revenue, $top_plans)); ?>];

  const ctxPlans = document.getElementById('chart-plans-breakdown').getContext('2d');
  new Chart(ctxPlans, {
    type: 'doughnut',
    data: {
      labels: planNames.length ? planNames : ['None'],
      datasets: [{
        data: planRevenues.length ? planRevenues : [1],
        backgroundColor: [
          '#FF6621',
          '#FFB020',
          '#0A0A0B',
          '#6A6A75',
          '#A4A4AF',
          '#D0D0DB',
          '#E8E8EF'
        ],
        borderWidth: 2,
        borderColor: '#ffffff'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'right',
          labels: {
            boxWidth: 12,
            font: { family: 'DM Sans', size: 11 },
            color: '#3A3A3F'
          }
        }
      }
    }
  });
});
</script>
