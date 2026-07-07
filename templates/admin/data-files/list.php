<?php
/**
 * Template: Admin Data Files List
 *
 * Variables:
 *  $rows          array   — array of database rows
 *  $total         int     — total number of files
 *  $current_page  int     — current page
 *  $per_page      int     — items per page
 *  $filters       array   — active filters
 *  $pagination    string  — pagination HTML
 *  $nonce         string  — form verification nonce
 *  $admin_nonce   string  — AJAX toggle nonce
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
$active_from    = $filters['date_from'] ?? '';
$active_to      = $filters['date_to'] ?? '';
?>

<!-- Alerts & Notices -->
<?php if (isset($_GET['uploaded'])) : ?>
  <div class="whoiscrm-alert whoiscrm-alert--success">
    <?php printf(esc_html__('Successfully uploaded %d file(s).', 'whois-crm'), (int) $_GET['uploaded']); ?>
    <?php if (isset($_GET['skipped'])) : ?>
      <span style="display:block; margin-top:4px; font-size:0.8125rem;">
        ⚠️ <?php printf(esc_html__('%d file(s) were skipped due to size or validation failures.', 'whois-crm'), (int) $_GET['skipped']); ?>
      </span>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php if (isset($_GET['deleted'])) : ?>
  <div class="whoiscrm-alert whoiscrm-alert--success">
    <?php esc_html_e('File deleted successfully.', 'whois-crm'); ?>
  </div>
<?php endif; ?>

<?php if (isset($_GET['bulk_done'])) : ?>
  <div class="whoiscrm-alert whoiscrm-alert--success">
    <?php
    $action_label = $_GET['bulk_action'] === 'delete' ? __('deleted', 'whois-crm') : ($_GET['bulk_action'] === 'deactivate' ? __('deactivated', 'whois-crm') : __('activated', 'whois-crm'));
    printf(esc_html__('Successfully %s %d file(s).', 'whois-crm'), $action_label, (int) $_GET['bulk_done']);
    ?>
  </div>
<?php endif; ?>

<!-- Filter Toolbar -->
<div class="whoiscrm-card" style="margin-bottom: var(--space-6);">
  <div class="whoiscrm-card__body" style="padding: var(--space-4);">
    <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>" style="display: flex; flex-wrap: wrap; gap: var(--space-3); align-items: flex-end;">
      <input type="hidden" name="page" value="whoiscrm-data-files">

      <div class="whoiscrm-form-group" style="margin: 0; min-width: 160px;">
        <label class="whoiscrm-form-label" style="font-size: 0.75rem; margin-bottom: 4px;"><?php esc_html_e('Service / Type', 'whois-crm'); ?></label>
        <select name="service_type" class="whoiscrm-select" style="height: 36px;">
          <option value=""><?php esc_html_e('All Services', 'whois-crm'); ?></option>
          <?php foreach ($service_types as $key => $label) : ?>
            <option value="<?php echo esc_attr($key); ?>" <?php selected($active_service, $key); ?>><?php echo esc_html($label); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="whoiscrm-form-group" style="margin: 0; min-width: 180px;">
        <label class="whoiscrm-form-label" style="font-size: 0.75rem; margin-bottom: 4px;"><?php esc_html_e('Country', 'whois-crm'); ?></label>
        <select name="country_code" class="whoiscrm-select" style="height: 36px;">
          <option value=""><?php esc_html_e('All Countries', 'whois-crm'); ?></option>
          <?php foreach ($country_options as $code => $name) : ?>
            <option value="<?php echo esc_attr($code); ?>" <?php selected($active_country, $code); ?>><?php echo esc_html("{$code} — {$name}"); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="whoiscrm-form-group" style="margin: 0; width: 140px;">
        <label class="whoiscrm-form-label" style="font-size: 0.75rem; margin-bottom: 4px;"><?php esc_html_e('From Date', 'whois-crm'); ?></label>
        <input type="date" name="date_from" class="whoiscrm-input" style="height: 36px; padding: 0 8px;" value="<?php echo esc_attr($active_from); ?>">
      </div>

      <div class="whoiscrm-form-group" style="margin: 0; width: 140px;">
        <label class="whoiscrm-form-label" style="font-size: 0.75rem; margin-bottom: 4px;"><?php esc_html_e('To Date', 'whois-crm'); ?></label>
        <input type="date" name="date_to" class="whoiscrm-input" style="height: 36px; padding: 0 8px;" value="<?php echo esc_attr($active_to); ?>">
      </div>

      <div style="display: flex; gap: var(--space-2);">
        <button type="submit" class="whoiscrm-btn whoiscrm-btn--primary whoiscrm-btn--sm" style="height: 36px;">
          🔍 <?php esc_html_e('Filter', 'whois-crm'); ?>
        </button>
        <?php if ($active_country || $active_service || $active_from || $active_to) : ?>
          <a href="<?php echo esc_url(admin_url('admin.php?page=whoiscrm-data-files')); ?>" class="whoiscrm-btn whoiscrm-btn--ghost whoiscrm-btn--sm" style="height: 36px; line-height: 34px;">
            <?php esc_html_e('Clear', 'whois-crm'); ?>
          </a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<!-- Bulk Action Form Wrapper -->
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="whoiscrm-bulk-form">
  <?php wp_nonce_field('whoiscrm_data_file_action', 'whoiscrm_data_file_action'); ?>
  <input type="hidden" name="action" value="whoiscrm_bulk_data_files">

  <div class="whoiscrm-table-wrapper">
    <div class="whoiscrm-table-toolbar">
      <div style="display: flex; align-items: center; gap: var(--space-3);">
        <select name="bulk_action" class="whoiscrm-select" style="height: 34px; padding: 0 8px; width: 160px; font-size: 0.8125rem;">
          <option value=""><?php esc_html_e('Bulk Actions', 'whois-crm'); ?></option>
          <option value="activate"><?php esc_html_e('Set Active', 'whois-crm'); ?></option>
          <option value="deactivate"><?php esc_html_e('Set Inactive', 'whois-crm'); ?></option>
          <option value="delete"><?php esc_html_e('Delete Permanently', 'whois-crm'); ?></option>
        </select>
        <button type="submit" class="whoiscrm-btn whoiscrm-btn--secondary whoiscrm-btn--sm" style="height: 34px;" data-confirm="<?php esc_attr_e('Are you sure you want to perform this action on the selected files?', 'whois-crm'); ?>">
          <?php esc_html_e('Apply', 'whois-crm'); ?>
        </button>
      </div>

      <span style="font-size: 0.8125rem; color: var(--color-text-muted);">
        <?php printf(esc_html__('Showing %d of %d data files', 'whois-crm'), count($rows), $total); ?>
      </span>
    </div>

    <table class="whoiscrm-table">
      <thead>
        <tr>
          <th style="width: 30px; text-align: center; padding: 11px var(--space-3);">
            <input type="checkbox" id="whoiscrm-select-all-checkbox">
          </th>
          <th><?php esc_html_e('File Name / Info', 'whois-crm'); ?></th>
          <th><?php esc_html_e('Service / Type', 'whois-crm'); ?></th>
          <th><?php esc_html_e('Target Country / TLD', 'whois-crm'); ?></th>
          <th><?php esc_html_e('Data Date', 'whois-crm'); ?></th>
          <th><?php esc_html_e('Size', 'whois-crm'); ?></th>
          <th><?php esc_html_e('Status', 'whois-crm'); ?></th>
          <th><?php esc_html_e('Actions', 'whois-crm'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)) : ?>
          <tr>
            <td colspan="8" style="text-align: center; padding: var(--space-10); color: var(--color-text-muted);">
              <?php esc_html_e('No files matched the search criteria or none uploaded yet.', 'whois-crm'); ?>
            </td>
          </tr>
        <?php else : ?>
          <?php foreach ($rows as $row) : ?>
            <tr>
              <td style="text-align: center; padding: 14px var(--space-3);">
                <input type="checkbox" name="file_ids[]" value="<?php echo (int) $row->id; ?>" class="whoiscrm-file-checkbox">
              </td>
              <td>
                <div style="font-weight: 600; color: var(--color-text-primary);"><?php echo esc_html($row->original_filename); ?></div>
                <small style="color: var(--color-text-muted); display: block; font-family: monospace; font-size: 0.75rem;">
                  SHA256: <?php echo esc_html(substr($row->checksum, 0, 12)); ?>...
                  <button type="button" class="whoiscrm-btn whoiscrm-btn--ghost whoiscrm-btn--sm" style="height: 18px; padding: 0 4px; font-size: 0.6875rem; border: none; margin-left: var(--space-1);" data-copy="<?php echo esc_attr($row->checksum); ?>">
                    <?php esc_html_e('Copy', 'whois-crm'); ?>
                  </button>
                </small>
                <?php if ($row->notes) : ?>
                  <div style="font-size: 0.75rem; color: var(--color-primary); margin-top: 4px; font-style: italic;">
                    ℹ️ <?php echo esc_html($row->notes); ?>
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <span class="whoiscrm-badge whoiscrm-badge--info">
                  <?php echo esc_html($service_types[$row->service_type] ?? $row->service_type); ?>
                </span>
              </td>
              <td>
                <strong><?php echo $row->country_code ? esc_html($row->country_code) : __('Global', 'whois-crm'); ?></strong>
                <?php if ($row->tld) : ?>
                  <code style="background: var(--color-surface-overlay); padding: 2px 4px; border-radius: 4px; font-size: 0.8125rem;"><?php echo esc_html($row->tld); ?></code>
                <?php endif; ?>
              </td>
              <td>
                <span style="font-weight: 500;"><?php echo esc_html($row->data_date); ?></span>
              </td>
              <td>
                <?php echo esc_html(\WhoisCRM\Helpers\Formatter::bytes($row->file_size)); ?>
                <span style="text-transform: uppercase; font-size: 0.75rem; color: var(--color-text-muted);">.<?php echo esc_html($row->file_type); ?></span>
              </td>
              <td>
                <button type="button" class="whoiscrm-btn whoiscrm-btn--ghost whoiscrm-btn--sm js-toggle-file-status" data-file-id="<?php echo (int) $row->id; ?>" data-nonce="<?php echo esc_attr($admin_nonce); ?>" style="height: 26px; font-size: 0.75rem; padding: 0 var(--space-2);">
                  <span class="whoiscrm-badge <?php echo $row->is_active ? 'whoiscrm-badge--success' : 'whoiscrm-badge--muted'; ?>">
                    <?php echo $row->is_active ? esc_html__('Active', 'whois-crm') : esc_html__('Inactive', 'whois-crm'); ?>
                  </span>
                </button>
              </td>
              <td>
                <div style="display: flex; gap: var(--space-1);">
                  <!-- Secure Direct Download Link -->
                  <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=whoiscrm_download_data_file&file_id=' . $row->id), 'whoiscrm_download_file_' . $row->id)); ?>" class="whoiscrm-btn whoiscrm-btn--secondary whoiscrm-btn--sm" style="padding: 0 8px; height: 28px;">
                    📥
                  </a>
                  
                  <button type="submit" class="whoiscrm-btn whoiscrm-btn--danger whoiscrm-btn--sm js-delete-single-file-btn" data-file-id="<?php echo (int) $row->id; ?>" style="padding: 0 8px; height: 28px;">
                    ✕
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>

    <div class="whoiscrm-table-footer">
      <div>
        <?php printf(esc_html__('Total: %d record(s)', 'whois-crm'), $total); ?>
      </div>
      <div>
        <?php echo $pagination; // phpcs:ignore WordPress.Security.OutputNotEscaped ?>
      </div>
    </div>
  </div>
</form>

<!-- Single File Delete Form (Submits to admin-post) -->
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="whoiscrm-single-delete-form" style="display: none;">
  <?php wp_nonce_field('whoiscrm_data_file_action', 'whoiscrm_data_file_action'); ?>
  <input type="hidden" name="action" value="whoiscrm_delete_data_file">
  <input type="hidden" name="file_id" id="whoiscrm-delete-file-id" value="">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const selectAllCheckbox = document.getElementById('whoiscrm-select-all-checkbox');
  const fileCheckboxes = document.querySelectorAll('.whoiscrm-file-checkbox');
  const bulkForm = document.getElementById('whoiscrm-bulk-form');
  const singleDeleteForm = document.getElementById('whoiscrm-single-delete-form');
  const deleteFileIdInput = document.getElementById('whoiscrm-delete-file-id');

  // Toggle selection for all checkboxes
  if (selectAllCheckbox) {
    selectAllCheckbox.addEventListener('change', function() {
      fileCheckboxes.forEach(cb => {
        cb.checked = selectAllCheckbox.checked;
      });
    });
  }

  // Handle single deletion trigger
  document.querySelectorAll('.js-delete-single-file-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      if (confirm('<?php esc_js(esc_html_e('Are you sure you want to permanently delete this data file from disk?', 'whois-crm')); ?>')) {
        deleteFileIdInput.value = btn.dataset.fileId;
        singleDeleteForm.submit();
      }
    });
  });

  // Handle AJAX file status toggling
  document.querySelectorAll('.js-toggle-file-status').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      
      const fileId = btn.dataset.fileId;
      const nonce = btn.dataset.nonce;
      const badge = btn.querySelector('.whoiscrm-badge');
      
      btn.disabled = true;
      badge.style.opacity = '0.5';

      const data = new FormData();
      data.append('action', 'whoiscrm_toggle_data_file');
      data.append('nonce', nonce);
      data.append('file_id', fileId);

      fetch('<?php echo esc_js(admin_url('admin-ajax.php')); ?>', {
        method: 'POST',
        body: data,
        credentials: 'same-origin',
      })
      .then(r => r.json())
      .then(res => {
        btn.disabled = false;
        badge.style.opacity = '1';
        
        if (res.success) {
          badge.textContent = res.data.label;
          if (res.data.is_active) {
            badge.className = 'whoiscrm-badge whoiscrm-badge--success';
          } else {
            badge.className = 'whoiscrm-badge whoiscrm-badge--muted';
          }
        } else {
          alert(res.data.message || 'Error occurred');
        }
      })
      .catch(() => {
        btn.disabled = false;
        badge.style.opacity = '1';
        alert('Network error');
      });
    });
  });
});
</script>
