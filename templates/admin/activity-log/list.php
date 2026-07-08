<?php
/**
 * Template: Admin Activity Log List
 *
 * Variables:
 *  $rows           array    Log rows
 *  $total          int      Total records
 *  $per_page       int      Rows per page
 *  $current_page   int      Current page
 *  $pagination     string   Pagination HTML
 *  $user_id        int      User filter
 *  $action_filter  string   Action type filter
 *  $severity       string   Severity filter
 *  $from           string   Date from
 *  $to             string   Date to
 *  $action_types   array    [action_key => label] pairs for filter dropdown
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

$severity_badge_map = [
    'info'    => ['class' => 'whoiscrm-badge--info',    'label' => __('Info',    'whois-crm')],
    'warning' => ['class' => 'whoiscrm-badge--warning', 'label' => __('Warning', 'whois-crm')],
    'error'   => ['class' => 'whoiscrm-badge--danger',  'label' => __('Error',   'whois-crm')],
    'debug'   => ['class' => 'whoiscrm-badge--ghost',   'label' => __('Debug',   'whois-crm')],
];
?>

<!-- Filters -->
<div style="display:flex; gap:var(--space-3); margin-bottom:var(--space-5); flex-wrap:wrap; align-items:center;">
  <form method="get" style="display:contents;">
    <input type="hidden" name="page" value="whoiscrm-activity-log">
    <select name="action_filter" class="whoiscrm-select" style="width:200px;">
      <option value=""><?php esc_html_e('All Actions', 'whois-crm'); ?></option>
      <?php foreach ($action_types as $key => $label) : ?>
        <option value="<?php echo esc_attr($key); ?>" <?php selected($action_filter, $key); ?>>
          <?php echo esc_html($label); ?>
        </option>
      <?php endforeach; ?>
    </select>
    <select name="severity" class="whoiscrm-select" style="width:140px;">
      <option value=""><?php esc_html_e('All Severities', 'whois-crm'); ?></option>
      <?php foreach ($severity_badge_map as $sev => $info) : ?>
        <option value="<?php echo esc_attr($sev); ?>" <?php selected($severity, $sev); ?>><?php echo esc_html($info['label']); ?></option>
      <?php endforeach; ?>
    </select>
    <input type="date" name="from" class="whoiscrm-input" style="width:148px;" value="<?php echo esc_attr($from); ?>">
    <input type="date" name="to"   class="whoiscrm-input" style="width:148px;" value="<?php echo esc_attr($to); ?>">
    <button type="submit" class="whoiscrm-btn whoiscrm-btn--secondary whoiscrm-btn--md"><?php esc_html_e('Filter', 'whois-crm'); ?></button>
    <?php if ($action_filter || $severity || $from || $to || $user_id) : ?>
      <a href="<?php echo esc_url(admin_url('admin.php?page=whoiscrm-activity-log')); ?>" class="whoiscrm-btn whoiscrm-btn--ghost whoiscrm-btn--md"><?php esc_html_e('Clear', 'whois-crm'); ?></a>
    <?php endif; ?>
  </form>
  <span style="margin-left:auto; font-size:0.875rem; color:var(--color-text-secondary);">
    <?php printf(esc_html(_n('%d Entry', '%d Entries', $total, 'whois-crm')), $total); ?>
  </span>
</div>

<!-- Table -->
<div class="whoiscrm-table-wrapper">
  <table class="whoiscrm-table">
    <thead>
      <tr>
        <th style="width:160px;"><?php esc_html_e('Date / Time', 'whois-crm'); ?></th>
        <th><?php esc_html_e('User', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Action', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Severity', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Details', 'whois-crm'); ?></th>
        <th style="width:120px;"><?php esc_html_e('IP Address', 'whois-crm'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)) : ?>
        <tr>
          <td colspan="6" style="text-align:center; padding:var(--space-10); color:var(--color-text-muted);">
            <?php esc_html_e('No activity log entries found.', 'whois-crm'); ?>
          </td>
        </tr>
      <?php else : ?>
        <?php foreach ($rows as $row) : ?>
          <?php
          $sev   = $row->severity ?? 'info';
          $badge = $severity_badge_map[$sev] ?? ['class' => 'whoiscrm-badge--ghost', 'label' => ucfirst($sev)];
          $meta  = !empty($row->meta) ? json_decode($row->meta, true) : [];
          ?>
          <tr>
            <td style="font-size:0.8125rem; color:var(--color-text-secondary); white-space:nowrap;">
              <?php echo $row->created_at ? esc_html(gmdate('Y-m-d H:i:s', strtotime($row->created_at))) : '—'; ?>
            </td>
            <td>
              <?php if (!empty($row->user_id)) : ?>
                <a
                  href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . (int) $row->user_id)); ?>"
                  style="font-weight:500; color:var(--color-text-primary); text-decoration:none; font-size:0.875rem;"
                >
                  <?php echo esc_html($row->user_display_name ?? ('User #' . $row->user_id)); ?>
                </a>
              <?php else : ?>
                <span style="color:var(--color-text-muted); font-size:0.875rem;"><?php esc_html_e('Guest', 'whois-crm'); ?></span>
              <?php endif; ?>
            </td>
            <td>
              <span style="font-weight:500; font-size:0.875rem; font-family:monospace; background:var(--color-surface-raised); padding:2px 6px; border-radius:4px;">
                <?php echo esc_html($row->action ?? '—'); ?>
              </span>
            </td>
            <td>
              <span class="whoiscrm-badge <?php echo esc_attr($badge['class']); ?>">
                <?php echo esc_html($badge['label']); ?>
              </span>
            </td>
            <td style="font-size:0.875rem; color:var(--color-text-primary);">
              <?php echo esc_html($row->message ?? ''); ?>
              <?php if (!empty($meta)) : ?>
                <details style="margin-top:var(--space-1);">
                  <summary style="cursor:pointer; font-size:0.75rem; color:var(--color-text-secondary);"><?php esc_html_e('Meta', 'whois-crm'); ?></summary>
                  <pre style="font-size:0.75rem; margin:var(--space-1) 0 0; background:var(--color-surface-raised); padding:var(--space-2); border-radius:4px; overflow:auto;"><?php echo esc_html(wp_json_encode($meta, JSON_PRETTY_PRINT)); ?></pre>
                </details>
              <?php endif; ?>
            </td>
            <td style="font-size:0.8125rem; font-family:monospace; color:var(--color-text-secondary);">
              <?php echo esc_html($row->ip_address ?? '—'); ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php if ($pagination) : ?>
  <?php echo $pagination; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php endif; ?>
