<?php
/**
 * Template: Admin Data File Upload Form
 *
 * Variables:
 *  $nonce          string
 *  $max_size_mb    int
 *  $allowed_types  array
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

$country_options = \WhoisCRM\Helpers\CountryList::all();
$service_types = [
    'whois_history'     => __('WHOIS History Database', 'whois-crm'),
    'lead_generation'   => __('Domain Lead Generation', 'whois-crm'),
    'expiring_domains'  => __('Expiring Domains', 'whois-crm'),
    'bulk_lookup'       => __('Bulk Domain Lookup', 'whois-crm'),
    'country_data'      => __('Country Data', 'whois-crm'),
    'enterprise'        => __('Enterprise (All Access)', 'whois-crm'),
];
?>

<div style="margin-bottom: var(--space-5);">
  <a href="<?php echo esc_url(admin_url('admin.php?page=whoiscrm-data-files')); ?>" class="whoiscrm-btn whoiscrm-btn--ghost whoiscrm-btn--sm">
    ← <?php esc_html_e('Back to Data Files', 'whois-crm'); ?>
  </a>
</div>

<?php if (!empty($_GET['upload_error'])) : ?>
  <div class="whoiscrm-alert whoiscrm-alert--danger">
    <?php echo esc_html(urldecode($_GET['upload_error'])); ?>
  </div>
<?php endif; ?>

<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
  <?php wp_nonce_field('whoiscrm_upload_nonce', 'whoiscrm_upload_nonce'); ?>
  <input type="hidden" name="action" value="whoiscrm_upload_files">

  <div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--space-6); align-items: flex-start;">
    
    <!-- Left Column (Files & Options) -->
    <div>
      <div class="whoiscrm-form-section">
        <div class="whoiscrm-form-section__header"><?php esc_html_e('Select Files', 'whois-crm'); ?></div>
        <div class="whoiscrm-form-section__body">
          
          <!-- Drag & Drop Zone -->
          <div id="whoiscrm-drop-zone" style="border: 2px dashed var(--color-border-strong); border-radius: var(--radius-lg); padding: var(--space-8); text-align: center; background: var(--color-surface); transition: background-color var(--duration-fast), border-color var(--duration-fast); cursor: pointer; position: relative;">
            <div style="font-size: 2.5rem; margin-bottom: var(--space-3);">📤</div>
            <h4 style="margin: 0 0 var(--space-2) 0; font-size: 1.1rem; color: var(--color-text-primary);"><?php esc_html_e('Drag and drop your data files here', 'whois-crm'); ?></h4>
            <p style="margin: 0; font-size: 0.875rem; color: var(--color-text-muted);">
              <?php printf(esc_html__('Supports .zip, .csv, .xlsx, .json up to %d MB', 'whois-crm'), $max_size_mb); ?>
            </p>
            <input type="file" name="whoiscrm_files[]" id="whoiscrm-file-input" multiple accept=".zip,.csv,.xlsx,.json" style="position: absolute; inset: 0; opacity: 0; cursor: pointer;">
          </div>

          <!-- Selected Files List -->
          <div id="whoiscrm-selected-files" style="margin-top: var(--space-4); display: none;">
            <h5 style="margin: 0 0 var(--space-2) 0; font-size: 0.875rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--color-text-muted);"><?php esc_html_e('Selected Files:', 'whois-crm'); ?></h5>
            <ul id="whoiscrm-files-list-ul" style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: var(--space-2);">
            </ul>
          </div>

        </div>
      </div>

      <div class="whoiscrm-form-section">
        <div class="whoiscrm-form-section__header"><?php esc_html_e('Upload Details', 'whois-crm'); ?></div>
        <div class="whoiscrm-form-section__body">
          
          <div class="whoiscrm-form-group">
            <label class="whoiscrm-form-label" for="service_type"><?php esc_html_e('Service / Data Type', 'whois-crm'); ?> <span style="color:var(--color-danger)">*</span></label>
            <select name="service_type" id="service_type" class="whoiscrm-select" required>
              <option value=""><?php esc_html_e('— Select Service Type —', 'whois-crm'); ?></option>
              <?php foreach ($service_types as $key => $label) : ?>
                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="whoiscrm-form-group">
            <label class="whoiscrm-form-label" for="notes"><?php esc_html_e('Upload Notes (internal)', 'whois-crm'); ?></label>
            <textarea name="notes" id="notes" class="whoiscrm-textarea" rows="3" placeholder="<?php esc_html_e('Any notes about this data dump…', 'whois-crm'); ?>"></textarea>
          </div>

        </div>
      </div>
    </div>

    <!-- Right Column (Meta Settings) -->
    <div>
      <div class="whoiscrm-form-section">
        <div class="whoiscrm-form-section__header"><?php esc_html_e('Data Attributes', 'whois-crm'); ?></div>
        <div class="whoiscrm-form-section__body">
          
          <div class="whoiscrm-form-group">
            <label class="whoiscrm-form-label" for="country_code"><?php esc_html_e('Target Country', 'whois-crm'); ?></label>
            <select name="country_code" id="country_code" class="whoiscrm-select">
              <option value=""><?php esc_html_e('— Global / All Countries —', 'whois-crm'); ?></option>
              <?php foreach ($country_options as $code => $name) : ?>
                <option value="<?php echo esc_attr($code); ?>" data-name="<?php echo esc_attr($name); ?>"><?php echo esc_html("{$code} — {$name}"); ?></option>
              <?php endforeach; ?>
            </select>
            <!-- Hidden field to hold country name so we can store it in DB -->
            <input type="hidden" name="country_name" id="country_name" value="">
          </div>

          <div class="whoiscrm-form-group">
            <label class="whoiscrm-form-label" for="tld"><?php esc_html_e('Target TLD', 'whois-crm'); ?></label>
            <input type="text" name="tld" id="tld" class="whoiscrm-input" placeholder="e.g. .com or .in">
            <p class="whoiscrm-form-hint"><?php esc_html_e('Leave blank for all TLDs in the archive.', 'whois-crm'); ?></p>
          </div>

          <div class="whoiscrm-form-group">
            <label class="whoiscrm-form-label" for="data_date"><?php esc_html_e('Data Snapshot Date', 'whois-crm'); ?> <span style="color:var(--color-danger)">*</span></label>
            <input type="date" name="data_date" id="data_date" class="whoiscrm-input" required value="<?php echo esc_attr(gmdate('Y-m-d')); ?>">
          </div>

        </div>
      </div>

      <button type="submit" class="whoiscrm-btn whoiscrm-btn--primary whoiscrm-btn--md" style="width: 100%;">
        📤 <?php esc_html_e('Upload Data Files', 'whois-crm'); ?>
      </button>
    </div>

  </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const dropZone = document.getElementById('whoiscrm-drop-zone');
  const fileInput = document.getElementById('whoiscrm-file-input');
  const selectedFilesContainer = document.getElementById('whoiscrm-selected-files');
  const filesListUl = document.getElementById('whoiscrm-files-list-ul');
  const countrySelect = document.getElementById('country_code');
  const countryNameInput = document.getElementById('country_name');

  // Handle Country Name synchronization
  if (countrySelect && countryNameInput) {
    countrySelect.addEventListener('change', function() {
      const selectedOption = countrySelect.options[countrySelect.selectedIndex];
      countryNameInput.value = selectedOption.dataset.name || '';
    });
  }

  // Visual effects for Drag & Drop
  ['dragenter', 'dragover'].forEach(eventName => {
    dropZone.addEventListener(eventName, function(e) {
      e.preventDefault();
      dropZone.style.backgroundColor = 'rgba(255, 102, 33, 0.05)';
      dropZone.style.borderColor = 'var(--color-primary)';
    }, false);
  });

  ['dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, function(e) {
      e.preventDefault();
      dropZone.style.backgroundColor = 'var(--color-surface)';
      dropZone.style.borderColor = 'var(--color-border-strong)';
    }, false);
  });

  // Handle File selection
  fileInput.addEventListener('change', function() {
    filesListUl.innerHTML = '';
    
    if (fileInput.files.length > 0) {
      selectedFilesContainer.style.display = 'block';
      
      Array.from(fileInput.files).forEach(file => {
        const li = document.createElement('li');
        li.style.display = 'flex';
        li.style.justifyContent = 'between';
        li.style.padding = '8px 12px';
        li.style.background = '#fff';
        li.style.border = '1px solid var(--color-border)';
        li.style.borderRadius = 'var(--radius-md)';
        li.style.fontSize = '0.875rem';
        
        const sizeMb = (file.size / 1024 / 1024).toFixed(2);
        li.innerHTML = `
          <span style="font-weight: 500; color: var(--color-text-primary); flex-grow: 1;">${escapeHtml(file.name)}</span>
          <span style="color: var(--color-text-muted); font-size: 0.8125rem;">${sizeMb} MB</span>
        `;
        filesListUl.appendChild(li);
      });
    } else {
      selectedFilesContainer.style.display = 'none';
    }
  });

  function escapeHtml(str) {
    return str.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
  }
});
</script>
