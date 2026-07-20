<?php
/**
 * Template: Customer Portal Downloads
 *
 * Variables:
 *  $files         array   List of accessible data file objects
 *  $total         int     Total available count
 *  $current_page  int     Current page index
 *  $per_page      int     Files per page
 *  $filters       array   Active filter options
 *  $nonce         string  Security nonce
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

$country_options = \WhoisCRM\Helpers\CountryList::all();
$service_types = [
    'whois_history'     => __('WHOIS History', 'whois-crm'),
    'lead_generation'   => __('Lead Generation', 'whois-crm'),
    'expiring_domains'  => __('Expiring Domains', 'whois-crm'),
    'bulk_lookup'       => __('Bulk Lookup', 'whois-crm'),
    'country_data'      => __('Country Data', 'whois-crm'),
    'enterprise'        => __('Enterprise (All Access)', 'whois-crm'),
];

$active_country = $filters['country_code'] ?? '';
$active_service = $filters['service_type'] ?? '';
$active_tld     = $filters['tld'] ?? '';

$total_pages = (int) ceil($total / $per_page);
?>

<div class="whoiscrm-portal-greeting">
  <h3><?php esc_html_e('Data File Downloads', 'whois-crm'); ?></h3>
  <p><?php esc_html_e('Browse and download the data file dumps matching your active subscription plans.', 'whois-crm'); ?></p>
</div>

<!-- Filters Toolbar (Responsive Grid) -->
<div class="whoiscrm-filter-toolbar">
  <form method="get" action="<?php echo esc_url(get_permalink()); ?>" class="whoiscrm-filter-form">
    <input type="hidden" name="tab" value="downloads">

    <div class="whoiscrm-filter-group" style="min-width: 160px;">
      <label class="whoiscrm-filter-label"><?php esc_html_e('Service / Type', 'whois-crm'); ?></label>
      <select name="service_type" class="whoiscrm-filter-select">
        <option value=""><?php esc_html_e('All Services', 'whois-crm'); ?></option>
        <?php foreach ($service_types as $key => $label) { ?>
          <option value="<?php echo esc_attr($key); ?>" <?php selected($active_service, $key); ?>><?php echo esc_html($label); ?></option>
        <?php } ?>
      </select>
    </div>

    <div class="whoiscrm-filter-group" style="min-width: 180px;">
      <label class="whoiscrm-filter-label"><?php esc_html_e('Country', 'whois-crm'); ?></label>
      <select name="country_code" class="whoiscrm-filter-select">
        <option value=""><?php esc_html_e('All Countries', 'whois-crm'); ?></option>
        <?php foreach ($country_options as $code => $name) { ?>
          <option value="<?php echo esc_attr($code); ?>" <?php selected($active_country, $code); ?>><?php echo esc_html("{$code} — {$name}"); ?></option>
        <?php } ?>
      </select>
    </div>

    <div class="whoiscrm-filter-group" style="min-width: 120px;">
      <label class="whoiscrm-filter-label"><?php esc_html_e('TLD', 'whois-crm'); ?></label>
      <input type="text" name="tld" class="whoiscrm-filter-input" placeholder=".com, .in..." value="<?php echo esc_attr($active_tld); ?>">
    </div>

    <div style="display: flex; gap: 8px; align-items: flex-end;">
      <button type="submit" class="whoiscrm-btn whoiscrm-btn--primary whoiscrm-btn--sm" style="height: 38px;">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <span><?php esc_html_e('Filter', 'whois-crm'); ?></span>
      </button>
      <?php if ($active_country || $active_service || $active_tld) { ?>
        <a href="<?php echo esc_url(add_query_arg('tab', 'downloads', get_permalink())); ?>" class="whoiscrm-btn whoiscrm-btn--ghost whoiscrm-btn--sm" style="height: 38px; line-height: 36px;">
          <?php esc_html_e('Clear', 'whois-crm'); ?>
        </a>
      <?php } ?>
    </div>
  </form>
</div>

<!-- Files Table (Touch Scrollable on Mobile) -->
<div class="whoiscrm-table-wrapper">
  <div class="whoiscrm-table-responsive">
    <table class="whoiscrm-table">
      <thead>
        <tr>
          <th><?php esc_html_e('File Name / Info', 'whois-crm'); ?></th>
          <th><?php esc_html_e('Service', 'whois-crm'); ?></th>
          <th><?php esc_html_e('Country / TLD', 'whois-crm'); ?></th>
          <th><?php esc_html_e('Snapshot Date', 'whois-crm'); ?></th>
          <th><?php esc_html_e('Size', 'whois-crm'); ?></th>
          <th style="text-align: right;"><?php esc_html_e('Action', 'whois-crm'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($files)) : ?>
          <tr>
            <td colspan="6" style="text-align: center; padding: 48px 16px; color: var(--color-text-muted);">
              <p style="margin: 0; font-size: 0.9375rem;"><?php esc_html_e('No data files match your current filters or subscription plan.', 'whois-crm'); ?></p>
            </td>
          </tr>
        <?php else : ?>
          <?php foreach ($files as $file) :
            $ext = strtolower(pathinfo($file->original_filename, PATHINFO_EXTENSION));
            $download_url = add_query_arg([
                'action'  => 'whoiscrm_download_file',
                'file_id' => $file->id,
                'nonce'   => wp_create_nonce('whoiscrm_download_' . $file->id),
            ], admin_url('admin-ajax.php'));
          ?>
            <tr>
              <td>
                <div style="display: flex; align-items: center; gap: 10px;">
                  <span style="font-size: 0.75rem; font-weight: 700; padding: 3px 8px; border-radius: 4px; background: rgba(255,102,33,0.1); color: var(--color-primary); text-transform: uppercase;">
                    <?php echo esc_html($ext ?: 'DATA'); ?>
                  </span>
                  <div>
                    <strong style="display: block; color: var(--color-text-primary); font-size: 0.875rem;"><?php echo esc_html($file->original_filename); ?></strong>
                    <?php if (!empty($file->description)) : ?>
                      <span style="font-size: 0.75rem; color: var(--color-text-muted);"><?php echo esc_html(wp_trim_words($file->description, 8)); ?></span>
                    <?php endif; ?>
                  </div>
                </div>
              </td>
              <td>
                <span class="whoiscrm-badge whoiscrm-badge--info">
                  <?php echo esc_html($service_types[$file->service_type] ?? $file->service_type); ?>
                </span>
              </td>
              <td>
                <span style="font-weight: 500; color: var(--color-text-primary);">
                  <?php echo esc_html($file->country_code ? "{$file->country_code}" : __('Global', 'whois-crm')); ?>
                </span>
                <?php if (!empty($file->tld)) : ?>
                  <span style="font-size: 0.75rem; color: var(--color-text-muted);"> (<?php echo esc_html($file->tld); ?>)</span>
                <?php endif; ?>
              </td>
              <td style="white-space: nowrap; font-size: 0.8125rem; color: var(--color-text-muted);">
                <?php echo esc_html($file->snapshot_date); ?>
              </td>
              <td style="white-space: nowrap; font-size: 0.8125rem; color: var(--color-text-muted);">
                <?php echo esc_html(size_format($file->file_size ?: 0)); ?>
              </td>
              <td style="text-align: right; white-space: nowrap;">
                <a href="<?php echo esc_url($download_url); ?>" class="whoiscrm-btn whoiscrm-btn--primary whoiscrm-btn--sm">
                  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                  <span><?php esc_html_e('Download', 'whois-crm'); ?></span>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <?php if ($total_pages > 1) : ?>
    <div style="padding: 16px 20px; border-top: 1px solid var(--color-border); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; background: var(--color-surface);">
      <span style="font-size: 0.8125rem; color: var(--color-text-muted);">
        <?php printf(esc_html__('Page %1$d of %2$d (%3$d total files)', 'whois-crm'), $current_page, $total_pages, $total); ?>
      </span>
      <div style="display: flex; gap: 6px;">
        <?php if ($current_page > 1) : ?>
          <a href="<?php echo esc_url(add_query_arg(array_merge($filters, ['tab' => 'downloads', 'paged' => $current_page - 1]), get_permalink())); ?>" class="whoiscrm-btn whoiscrm-btn--ghost whoiscrm-btn--sm">
            « <?php esc_html_e('Prev', 'whois-crm'); ?>
          </a>
        <?php endif; ?>
        <?php if ($current_page < $total_pages) : ?>
          <a href="<?php echo esc_url(add_query_arg(array_merge($filters, ['tab' => 'downloads', 'paged' => $current_page + 1]), get_permalink())); ?>" class="whoiscrm-btn whoiscrm-btn--ghost whoiscrm-btn--sm">
            <?php esc_html_e('Next', 'whois-crm'); ?> »
          </a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>
