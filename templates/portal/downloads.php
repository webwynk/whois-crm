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

// Calculate pagination page count
$total_pages = (int) ceil($total / $per_page);
?>

<div style="margin-bottom: var(--space-6);">
  <h3 style="margin: 0 0 var(--space-1) 0; font-size: var(--text-h2); font-weight: 700; color: var(--color-black);">
    <?php esc_html_e('Data File Downloads', 'whois-crm'); ?>
  </h3>
  <p style="margin: 0; color: var(--color-text-secondary); font-size: 0.9375rem;">
    <?php esc_html_e('Browse and download the data file dumps matching your active subscription plans.', 'whois-crm'); ?>
  </p>
</div>

<!-- Filters Toolbar -->
<div class="whoiscrm-card" style="margin-bottom: var(--space-6);">
  <div class="whoiscrm-card__body" style="padding: var(--space-4);">
    <form method="get" action="<?php echo esc_url(get_permalink()); ?>" style="display: flex; flex-wrap: wrap; gap: var(--space-3); align-items: flex-end;">
      <input type="hidden" name="tab" value="downloads">

      <div class="whoiscrm-form-group" style="margin: 0; min-width: 160px;">
        <label class="whoiscrm-form-label" style="font-size: 0.75rem; margin-bottom: 4px;"><?php esc_html_e('Service / Type', 'whois-crm'); ?></label>
        <select name="service_type" class="whoiscrm-select" style="height: 36px;">
          <option value=""><?php esc_html_e('All Services', 'whois-crm'); ?></option>
          <?php foreach ($service_types as $key => $label) { ?>
            <option value="<?php echo esc_attr($key); ?>" <?php selected($active_service, $key); ?>><?php echo esc_html($label); ?></option>
          <?php } ?>
        </select>
      </div>

      <div class="whoiscrm-form-group" style="margin: 0; min-width: 180px;">
        <label class="whoiscrm-form-label" style="font-size: 0.75rem; margin-bottom: 4px;"><?php esc_html_e('Country', 'whois-crm'); ?></label>
        <select name="country_code" class="whoiscrm-select" style="height: 36px;">
          <option value=""><?php esc_html_e('All Countries', 'whois-crm'); ?></option>
          <?php foreach ($country_options as $code => $name) { ?>
            <option value="<?php echo esc_attr($code); ?>" <?php selected($active_country, $code); ?>><?php echo esc_html("{$code} — {$name}"); ?></option>
          <?php } ?>
        </select>
      </div>

      <div class="whoiscrm-form-group" style="margin: 0; width: 140px;">
        <label class="whoiscrm-form-label" style="font-size: 0.75rem; margin-bottom: 4px;"><?php esc_html_e('TLD', 'whois-crm'); ?></label>
        <input type="text" name="tld" class="whoiscrm-input" style="height: 36px;" placeholder=".com, .in..." value="<?php echo esc_attr($active_tld); ?>">
      </div>

      <div style="display: flex; gap: var(--space-2);">
        <button type="submit" class="whoiscrm-btn whoiscrm-btn--primary whoiscrm-btn--sm" style="height: 36px;">
          🔍 <?php esc_html_e('Filter', 'whois-crm'); ?>
        </button>
        <?php if ($active_country || $active_service || $active_tld) { ?>
          <a href="<?php echo esc_url(add_query_arg('tab', 'downloads', get_permalink())); ?>" class="whoiscrm-btn whoiscrm-btn--ghost whoiscrm-btn--sm" style="height: 36px; line-height: 34px;">
            <?php esc_html_e('Clear', 'whois-crm'); ?>
          </a>
        <?php } ?>
      </div>
    </form>
  </div>
</div>

<!-- Files Table -->
<div class="whoiscrm-table-wrapper">
  <table class="whoiscrm-table">
    <thead>
      <tr>
        <th><?php esc_html_e('File Name / Info', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Service / Type', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Country / TLD', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Snapshot Date', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Size', 'whois-crm'); ?></th>
        <th><?php esc_html_e('Action', 'whois-crm'); ?></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($files)) { ?>
        <tr>
          <td colspan="6" style="text-align: center; padding: var(--space-10); color: var(--color-text-muted);">
            <?php esc_html_e('No files are currently available matching your subscriptions and filters.', 'whois-crm'); ?>
          </td>
        </tr>
      <?php } else { ?>
        <?php foreach ($files as $row) { ?>
          <tr>
            <td>
              <div style="font-weight: 600; color: var(--color-text-primary);"><?php echo esc_html($row->original_filename); ?></div>
              <?php if ($row->notes) { ?>
                <div style="font-size: 0.75rem; color: var(--color-text-secondary); margin-top: 2px;">
                  <?php echo esc_html($row->notes); ?>
                </div>
              <?php } ?>
            </td>
            <td>
              <span class="whoiscrm-badge whoiscrm-badge--info">
                <?php echo esc_html($service_types[$row->service_type] ?? $row->service_type); ?>
              </span>
            </td>
            <td>
              <strong><?php echo $row->country_code ? esc_html($row->country_code) : __('Global', 'whois-crm'); ?></strong>
              <?php if ($row->tld) { ?>
                <code style="background: var(--color-surface-overlay); padding: 2px 4px; border-radius: 4px; font-size: 0.8125rem;"><?php echo esc_html($row->tld); ?></code>
              <?php } ?>
            </td>
            <td>
              <span style="font-weight: 500;"><?php echo esc_html($row->data_date); ?></span>
            </td>
            <td>
              <?php echo esc_html(\WhoisCRM\Helpers\Formatter::bytes($row->file_size)); ?>
              <span style="text-transform: uppercase; font-size: 0.75rem; color: var(--color-text-muted);">.<?php echo esc_html($row->file_type); ?></span>
            </td>
            <td>
              <!-- Secure direct file download via WordPress admin-post handler -->
              <a
                href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=whoiscrm_download_data_file&file_id=' . $row->id), 'whoiscrm_download_file_' . $row->id)); ?>"
                class="whoiscrm-btn whoiscrm-btn--primary whoiscrm-btn--sm"
                style="height: 30px; font-size: 0.8125rem;"
              >
                📥 <?php esc_html_e('Download', 'whois-crm'); ?>
              </a>
            </td>
          </tr>
        <?php } ?>
      <?php } ?>
    </tbody>
  </table>

  <!-- Pagination -->
  <?php if ($total_pages > 1) { ?>
    <div class="whoiscrm-table-footer" style="justify-content: flex-end;">
      <nav class="whoiscrm-pagination">
        <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
          <a
            href="<?php echo esc_url(add_query_arg(['tab' => 'downloads', 'paged' => $i], get_permalink())); ?>"
            class="whoiscrm-pagination__num <?php echo $i === $current_page ? 'is-active' : ''; ?>"
          >
            <?php echo $i; ?>
          </a>
        <?php } ?>
      </nav>
    </div>
  <?php } ?>

</div>
