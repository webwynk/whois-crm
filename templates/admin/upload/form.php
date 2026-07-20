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
              <?php printf(esc_html__('Supports .zip, .csv, .xlsx, .xls, .ods, .txt, .pdf, .json up to %d MB', 'whois-crm'), $max_size_mb); ?>
            </p>
            <input type="file" name="whoiscrm_files[]" id="whoiscrm-file-input" multiple accept=".zip,.csv,.xlsx,.xls,.ods,.txt,.pdf,.json" style="position: absolute; inset: 0; opacity: 0; cursor: pointer;">
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

<!-- ═══════ Upload Progress Overlay (hidden until submit) ═══════ -->
<div id="whoiscrm-upload-overlay" style="display: none;">
  <div class="whoiscrm-upload-progress-card" id="whoiscrm-progress-card">
    <div class="whoiscrm-upload-progress-card__inner">

      <!-- Progress State -->
      <div id="whoiscrm-progress-state">
        <div id="whoiscrm-progress-pct" class="whoiscrm-progress-pct">0%</div>

        <div class="whoiscrm-progress-bar-track">
          <div class="whoiscrm-progress-bar-fill" id="whoiscrm-progress-bar-fill"></div>
        </div>

        <p id="whoiscrm-progress-label" class="whoiscrm-progress-label">
          <?php esc_html_e('Uploading your files…', 'whois-crm'); ?>
        </p>
      </div>

      <!-- Success State (hidden initially) -->
      <div id="whoiscrm-success-state" style="display: none;">
        <div class="whoiscrm-success-icon">✓</div>
        <h3 class="whoiscrm-success-title"><?php esc_html_e('Upload Successful!', 'whois-crm'); ?></h3>
        <p class="whoiscrm-success-subtitle" id="whoiscrm-success-detail"></p>
        <p class="whoiscrm-success-redirect"><?php esc_html_e('Redirecting…', 'whois-crm'); ?></p>
      </div>

      <!-- Error State (hidden initially) -->
      <div id="whoiscrm-error-state" style="display: none;">
        <div class="whoiscrm-error-icon">✕</div>
        <h3 class="whoiscrm-success-title" style="color: var(--color-danger, #dc3545);"><?php esc_html_e('Upload Failed', 'whois-crm'); ?></h3>
        <p id="whoiscrm-error-message" class="whoiscrm-success-subtitle"></p>
        <button type="button" id="whoiscrm-retry-btn" class="whoiscrm-btn whoiscrm-btn--primary whoiscrm-btn--sm" style="margin-top: var(--space-4, 16px);">
          <?php esc_html_e('← Try Again', 'whois-crm'); ?>
        </button>
      </div>

    </div>
  </div>
</div>

<!-- ═══════ Progress Styles ═══════ -->
<style>
/* ── Animated border card ────────────────────────────────────── */
.whoiscrm-upload-progress-card {
  --progress-deg: 0deg;
  position: relative;
  padding: 3px;
  border-radius: var(--radius-lg, 12px);
  background: conic-gradient(
    var(--color-primary, #ff6621) var(--progress-deg),
    rgba(0, 0, 0, 0.08) var(--progress-deg)
  );
  max-width: 560px;
  margin: var(--space-8, 32px) auto 0;
  animation: whoiscrm-border-pulse 2s ease-in-out infinite;
}
.whoiscrm-upload-progress-card--success {
  background: conic-gradient(#22c55e 360deg, #22c55e 360deg) !important;
  animation: none;
  box-shadow: 0 0 24px rgba(34, 197, 94, 0.25);
}
.whoiscrm-upload-progress-card--error {
  background: conic-gradient(var(--color-danger, #dc3545) 360deg, var(--color-danger, #dc3545) 360deg) !important;
  animation: none;
}
.whoiscrm-upload-progress-card__inner {
  background: var(--color-surface, #fff);
  border-radius: calc(var(--radius-lg, 12px) - 2px);
  padding: 56px 48px;
  text-align: center;
}

/* ── Percentage counter ──────────────────────────────────────── */
.whoiscrm-progress-pct {
  font-size: 3rem;
  font-weight: 800;
  font-variant-numeric: tabular-nums;
  color: var(--color-primary, #ff6621);
  line-height: 1;
  margin-bottom: var(--space-5, 20px);
  letter-spacing: -0.02em;
}

/* ── Horizontal progress bar ─────────────────────────────────── */
.whoiscrm-progress-bar-track {
  width: 100%;
  height: 10px;
  background: rgba(0, 0, 0, 0.06);
  border-radius: 999px;
  overflow: hidden;
  margin-bottom: var(--space-4, 16px);
}
.whoiscrm-progress-bar-fill {
  width: 0%;
  height: 100%;
  border-radius: 999px;
  background: linear-gradient(90deg, var(--color-primary, #ff6621), #ff8f5e);
  background-size: 200% 100%;
  transition: width 0.25s ease-out;
  animation: whoiscrm-shimmer 1.8s ease-in-out infinite;
}

/* ── Status label ────────────────────────────────────────────── */
.whoiscrm-progress-label {
  margin: 0;
  font-size: 0.9375rem;
  font-weight: 500;
  color: var(--color-text-muted, #71717a);
}

/* ── Success state ───────────────────────────────────────────── */
.whoiscrm-success-icon {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: #22c55e;
  color: #fff;
  font-size: 2rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto var(--space-4, 16px);
  animation: whoiscrm-bounce-in 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.whoiscrm-error-icon {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: var(--color-danger, #dc3545);
  color: #fff;
  font-size: 2rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto var(--space-4, 16px);
  animation: whoiscrm-bounce-in 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.whoiscrm-success-title {
  margin: 0 0 var(--space-2, 8px);
  font-size: 1.375rem;
  font-weight: 700;
  color: var(--color-text-primary, #18181b);
}
.whoiscrm-success-subtitle {
  margin: 0 0 var(--space-2, 8px);
  font-size: 0.9375rem;
  color: var(--color-text-muted, #71717a);
}
.whoiscrm-success-redirect {
  margin: 0;
  font-size: 0.8125rem;
  color: var(--color-text-muted, #71717a);
  opacity: 0.7;
}

/* ── Keyframe animations ─────────────────────────────────────── */
@keyframes whoiscrm-shimmer {
  0%   { background-position: 200% 0; }
  100% { background-position: -200% 0; }
}
@keyframes whoiscrm-border-pulse {
  0%, 100% { box-shadow: 0 0 8px rgba(255, 102, 33, 0.15); }
  50%      { box-shadow: 0 0 20px rgba(255, 102, 33, 0.35); }
}
@keyframes whoiscrm-bounce-in {
  0%   { transform: scale(0); opacity: 0; }
  60%  { transform: scale(1.15); opacity: 1; }
  100% { transform: scale(1); }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  /* ── Existing: Element references ────────────────────────────── */
  const dropZone = document.getElementById('whoiscrm-drop-zone');
  const fileInput = document.getElementById('whoiscrm-file-input');
  const selectedFilesContainer = document.getElementById('whoiscrm-selected-files');
  const filesListUl = document.getElementById('whoiscrm-files-list-ul');
  const countrySelect = document.getElementById('country_code');
  const countryNameInput = document.getElementById('country_name');

  /* ── Existing: Country name synchronisation ──────────────────── */
  if (countrySelect && countryNameInput) {
    countrySelect.addEventListener('change', function() {
      const selectedOption = countrySelect.options[countrySelect.selectedIndex];
      countryNameInput.value = selectedOption.dataset.name || '';
    });
  }

  /* ── Existing: Drag-and-drop visual effects ──────────────────── */
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

  /* ── Existing: Selected file list display ────────────────────── */
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

  /* ════════════════════════════════════════════════════════════════
   *  NEW: AJAX Upload with Real-Time Progress
   * ════════════════════════════════════════════════════════════════ */
  const uploadForm       = document.querySelector('form[enctype="multipart/form-data"]');
  const progressOverlay  = document.getElementById('whoiscrm-upload-overlay');
  const progressCard     = document.getElementById('whoiscrm-progress-card');
  const progressPct      = document.getElementById('whoiscrm-progress-pct');
  const progressBarFill  = document.getElementById('whoiscrm-progress-bar-fill');
  const progressLabel    = document.getElementById('whoiscrm-progress-label');
  const progressState    = document.getElementById('whoiscrm-progress-state');
  const successState     = document.getElementById('whoiscrm-success-state');
  const successDetail    = document.getElementById('whoiscrm-success-detail');
  const errorState       = document.getElementById('whoiscrm-error-state');
  const errorMessage     = document.getElementById('whoiscrm-error-message');
  const retryBtn         = document.getElementById('whoiscrm-retry-btn');

  if (uploadForm && progressOverlay) {
    uploadForm.addEventListener('submit', function(e) {
      e.preventDefault();

      /* ── Client-side required-field validation ──────────────── */
      const serviceType = document.getElementById('service_type');
      const dataDate    = document.getElementById('data_date');

      if (!serviceType.value) {
        serviceType.focus();
        return;
      }
      if (!dataDate.value) {
        dataDate.focus();
        return;
      }
      if (!fileInput.files || fileInput.files.length === 0) {
        dropZone.style.borderColor = 'var(--color-danger, #dc3545)';
        dropZone.style.backgroundColor = 'rgba(220, 53, 69, 0.04)';
        setTimeout(() => {
          dropZone.style.borderColor = 'var(--color-border-strong)';
          dropZone.style.backgroundColor = 'var(--color-surface)';
        }, 1500);
        return;
      }

      /* ── Build FormData BEFORE hiding the form ─────────────── */
      const formData = new FormData(uploadForm);

      /* ── Switch UI to progress view ────────────────────────── */
      uploadForm.style.display = 'none';
      progressOverlay.style.display = 'block';
      resetProgressUI();

      /* ── XMLHttpRequest (supports upload.onprogress) ───────── */
      const xhr = new XMLHttpRequest();

      xhr.upload.addEventListener('progress', function(ev) {
        if (!ev.lengthComputable) return;

        const pct = Math.round((ev.loaded / ev.total) * 100);
        updateProgress(pct);

        if (pct >= 100) {
          progressLabel.textContent = '<?php echo esc_js(__('Processing on server…', 'whois-crm')); ?>';
        }
      });

      xhr.addEventListener('load', function() {
        updateProgress(100);

        let redirectUrl = '<?php echo esc_js(admin_url('admin.php?page=whoiscrm-data-files')); ?>';
        let uploadedCount = 0;
        let skippedCount  = 0;

        try {
          const resp = JSON.parse(xhr.responseText);
          if (resp.success) {
            uploadedCount = resp.data.uploaded || 0;
            skippedCount  = resp.data.skipped  || 0;
            redirectUrl   = resp.data.redirect || redirectUrl;
          } else {
            /* Server returned a validation error */
            showError(resp.data && resp.data.message
              ? resp.data.message
              : '<?php echo esc_js(__('An unexpected error occurred.', 'whois-crm')); ?>');
            return;
          }
        } catch (parseErr) {
          /* Non-JSON response — server returned HTML error page */
          showError('<?php echo esc_js(__('Server returned an unexpected response. Please try again.', 'whois-crm')); ?>');
          return;
        }

        showSuccess(uploadedCount, skippedCount, redirectUrl);
      });

      xhr.addEventListener('error', function() {
        showError('<?php echo esc_js(__('Network error — check your connection and try again.', 'whois-crm')); ?>');
      });

      xhr.addEventListener('abort', function() {
        showError('<?php echo esc_js(__('Upload was cancelled.', 'whois-crm')); ?>');
      });

      xhr.open('POST', uploadForm.action, true);
      xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
      xhr.send(formData);
    });

    /* ── Retry button ─────────────────────────────────────────── */
    if (retryBtn) {
      retryBtn.addEventListener('click', function() {
        progressOverlay.style.display = 'none';
        uploadForm.style.display = 'block';
      });
    }
  }

  /* ── Helper: update progress UI ──────────────────────────────── */
  function updateProgress(pct) {
    const clamped = Math.min(100, Math.max(0, pct));
    const deg = (clamped / 100) * 360;

    progressCard.style.setProperty('--progress-deg', deg + 'deg');
    progressPct.textContent  = clamped + '%';
    progressBarFill.style.width = clamped + '%';
  }

  /* ── Helper: show success state ──────────────────────────────── */
  function showSuccess(uploadedCount, skippedCount, redirectUrl) {
    progressState.style.display = 'none';

    if (uploadedCount < 1) {
      /* All files were rejected — show as error, not success */
      let msg = '<?php echo esc_js(__('No files were saved.', 'whois-crm')); ?>';
      if (skippedCount > 0) {
        msg += ' ' + skippedCount + ' <?php echo esc_js(__('file(s) skipped due to validation errors.', 'whois-crm')); ?>';
      }
      showError(msg);
      return;
    }

    successState.style.display = 'block';
    progressCard.classList.add('whoiscrm-upload-progress-card--success');

    let detail = uploadedCount + ' <?php echo esc_js(__('file(s) uploaded successfully', 'whois-crm')); ?>';
    if (skippedCount > 0) {
      detail += ', ' + skippedCount + ' <?php echo esc_js(__('skipped', 'whois-crm')); ?>';
    }
    successDetail.textContent = detail;

    setTimeout(function() {
      window.location.href = redirectUrl;
    }, 2000);
  }

  /* ── Helper: show error state ────────────────────────────────── */
  function showError(msg) {
    progressState.style.display = 'none';
    errorState.style.display    = 'block';
    errorMessage.textContent    = msg;
    progressCard.classList.add('whoiscrm-upload-progress-card--error');
  }

  /* ── Helper: reset progress UI to initial state ──────────────── */
  function resetProgressUI() {
    progressState.style.display = 'block';
    successState.style.display  = 'none';
    errorState.style.display    = 'none';
    progressCard.classList.remove('whoiscrm-upload-progress-card--success', 'whoiscrm-upload-progress-card--error');
    updateProgress(0);
    progressLabel.textContent = '<?php echo esc_js(__('Uploading your files…', 'whois-crm')); ?>';
  }
});
</script>

