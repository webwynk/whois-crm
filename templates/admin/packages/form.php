<?php
/**
 * Template: Admin Package Add/Edit Form
 *
 * Variables:
 *  $package           object|null  — existing package (null for new)
 *  $package_id        int
 *  $pricing_by_cycle  array        — ['monthly' => obj, 'annually' => obj]
 *  $service_types     array        — [key => label]
 *  $country_options   array        — [code => name]
 *  $nonce             string       — save nonce
 *  $admin_nonce       string       — AJAX nonce
 *  $back_url          string
 *  $stripe_configured bool
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

$is_edit = $package_id > 0;
$monthly = $pricing_by_cycle['monthly'] ?? null;
$annual  = $pricing_by_cycle['annually'] ?? null;
?>

<div style="display:flex; align-items:center; gap:var(--space-3); margin-bottom:var(--space-5);">
  <a href="<?php echo esc_url($back_url); ?>" class="whoiscrm-btn whoiscrm-btn--ghost whoiscrm-btn--sm">← <?php esc_html_e('Back to Packages', 'whois-crm'); ?></a>

  <?php if ($is_edit && $stripe_configured) : ?>
  <!-- Stripe Sync Button -->
  <button type="button" id="whoiscrm-stripe-sync-btn" class="whoiscrm-btn whoiscrm-btn--accent whoiscrm-btn--sm" data-package-id="<?php echo (int) $package_id; ?>" data-nonce="<?php echo esc_attr($admin_nonce); ?>">
    ⚡ <?php esc_html_e('Sync to Stripe', 'whois-crm'); ?>
  </button>
  <span id="whoiscrm-stripe-sync-result" style="font-size:.8125rem;"></span>
  <?php endif; ?>
</div>

<!-- Stripe Sync Status -->
<?php if ($is_edit && !empty($package->stripe_product_id)) : ?>
<div class="whoiscrm-alert whoiscrm-alert--info" style="margin-bottom:var(--space-5);">
  <strong><?php esc_html_e('Stripe Product ID:', 'whois-crm'); ?></strong>
  <code><?php echo esc_html($package->stripe_product_id); ?></code>
  <?php if ($monthly && !empty($monthly->stripe_price_id)) : ?>
    &nbsp;|&nbsp; <strong><?php esc_html_e('Monthly Price ID:', 'whois-crm'); ?></strong>
    <code><?php echo esc_html($monthly->stripe_price_id); ?></code>
  <?php endif; ?>
  <?php if ($annual && !empty($annual->stripe_price_id)) : ?>
    &nbsp;|&nbsp; <strong><?php esc_html_e('Annual Price ID:', 'whois-crm'); ?></strong>
    <code><?php echo esc_html($annual->stripe_price_id); ?></code>
  <?php endif; ?>
</div>
<?php endif; ?>

<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
  <?php wp_nonce_field('whoiscrm_package_save'); ?>
  <input type="hidden" name="action"     value="whoiscrm_save_package">
  <input type="hidden" name="package_id" value="<?php echo (int) $package_id; ?>">

  <div style="display:grid; grid-template-columns:2fr 1fr; gap:var(--space-5); align-items:flex-start;">

    <!-- ── Left column ──────────────────────────────────────────── -->
    <div>

      <!-- Basic Info -->
      <div class="whoiscrm-form-section">
        <div class="whoiscrm-form-section__header"><?php esc_html_e('Basic Information', 'whois-crm'); ?></div>
        <div class="whoiscrm-form-section__body">

          <div class="whoiscrm-form-group">
            <label class="whoiscrm-form-label" for="whoiscrm-package-name">
              <?php esc_html_e('Package Name', 'whois-crm'); ?> <span style="color:var(--color-danger)">*</span>
            </label>
            <input type="text" id="whoiscrm-package-name" name="name" class="whoiscrm-input" required
              value="<?php echo esc_attr($package->name ?? ''); ?>"
              placeholder="<?php esc_attr_e('e.g. WHOIS History Database', 'whois-crm'); ?>">
          </div>

          <div class="whoiscrm-form-group">
            <label class="whoiscrm-form-label" for="whoiscrm-package-slug"><?php esc_html_e('Slug', 'whois-crm'); ?></label>
            <input type="text" id="whoiscrm-package-slug" name="slug" class="whoiscrm-input"
              value="<?php echo esc_attr($package->slug ?? ''); ?>"
              placeholder="<?php esc_attr_e('auto-generated', 'whois-crm'); ?>">
            <p class="whoiscrm-form-hint"><?php esc_html_e('URL-safe identifier. Auto-generated from name.', 'whois-crm'); ?></p>
          </div>

          <div class="whoiscrm-form-group">
            <label class="whoiscrm-form-label" for="whoiscrm-package-description"><?php esc_html_e('Description', 'whois-crm'); ?></label>
            <textarea id="whoiscrm-package-description" name="description" class="whoiscrm-textarea" rows="3"
              placeholder="<?php esc_attr_e('Shown on the pricing page…', 'whois-crm'); ?>"><?php echo esc_textarea($package->description ?? ''); ?></textarea>
          </div>

        </div>
      </div>

      <!-- Type & Countries -->
      <div class="whoiscrm-form-section">
        <div class="whoiscrm-form-section__header"><?php esc_html_e('Plan Type', 'whois-crm'); ?></div>
        <div class="whoiscrm-form-section__body">

          <div class="whoiscrm-form-group">
            <label class="whoiscrm-form-label"><?php esc_html_e('Package Type', 'whois-crm'); ?></label>
            <div style="display:flex; gap:var(--space-6);">
              <label style="display:flex; align-items:center; gap:var(--space-2); cursor:pointer; font-weight:400;">
                <input type="radio" name="type" value="global_service" <?php checked(($package->type ?? 'global_service'), 'global_service'); ?>>
                <?php esc_html_e('Global Service (all countries)', 'whois-crm'); ?>
              </label>
              <label style="display:flex; align-items:center; gap:var(--space-2); cursor:pointer; font-weight:400;">
                <input type="radio" name="type" value="country_specific" <?php checked(($package->type ?? ''), 'country_specific'); ?>>
                <?php esc_html_e('Country Specific', 'whois-crm'); ?>
              </label>
            </div>
          </div>

          <div class="whoiscrm-form-group">
            <label class="whoiscrm-form-label" for="whoiscrm-package-service-type"><?php esc_html_e('Service Type', 'whois-crm'); ?></label>
            <select id="whoiscrm-package-service-type" name="service_type" class="whoiscrm-select">
              <?php foreach ($service_types as $key => $label) : ?>
                <option value="<?php echo esc_attr($key); ?>" <?php selected(($package->service_type ?? ''), $key); ?>>
                  <?php echo esc_html($label); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Countries (country_specific only) -->
          <div class="whoiscrm-country-field" style="display:<?php echo ($package->type ?? '') === 'country_specific' ? 'block' : 'none'; ?>;">
            <label class="whoiscrm-form-label"><?php esc_html_e('Countries', 'whois-crm'); ?></label>
            <select name="countries[]" multiple class="whoiscrm-select" style="height:180px;" id="whoiscrm-package-countries">
              <?php foreach ($country_options as $code => $name) : ?>
                <option value="<?php echo esc_attr($code); ?>" <?php echo in_array($code, $package->countries_arr ?? [], true) ? 'selected' : ''; ?>>
                  <?php echo esc_html("{$code} — {$name}"); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <p class="whoiscrm-form-hint"><?php esc_html_e('Hold Ctrl/Cmd to select multiple.', 'whois-crm'); ?></p>
          </div>

          <div class="whoiscrm-form-group">
            <label class="whoiscrm-form-label"><?php esc_html_e('TLDs (comma-separated)', 'whois-crm'); ?></label>
            <input type="text" name="tlds" class="whoiscrm-input"
              value="<?php echo esc_attr($package->tlds_str ?? ''); ?>"
              placeholder=".com, .in, .co.in">
          </div>

        </div>
      </div>

      <!-- Features -->
      <div class="whoiscrm-form-section">
        <div class="whoiscrm-form-section__header"><?php esc_html_e('Features (shown on pricing page)', 'whois-crm'); ?></div>
        <div class="whoiscrm-form-section__body">
          <div id="whoiscrm-features-list">
            <?php
            $features_arr = $package->features_arr ?? [''];
            foreach ($features_arr as $feat) : ?>
            <div class="whoiscrm-feature-row" style="display:flex; gap:var(--space-2); margin-bottom:var(--space-2);">
              <input type="text" name="features[]" class="whoiscrm-input" value="<?php echo esc_attr($feat); ?>"
                placeholder="<?php esc_attr_e('Feature bullet point…', 'whois-crm'); ?>">
              <button type="button" class="whoiscrm-btn whoiscrm-btn--ghost whoiscrm-btn--sm js-remove-feature" style="flex-shrink:0;">✕</button>
            </div>
            <?php endforeach; ?>
          </div>
          <button type="button" id="js-add-feature" class="whoiscrm-btn whoiscrm-btn--ghost whoiscrm-btn--sm" style="margin-top:var(--space-2);">
            + <?php esc_html_e('Add Feature', 'whois-crm'); ?>
          </button>
        </div>
      </div>

    </div><!-- /left column -->

    <!-- ── Right column ─────────────────────────────────────────── -->
    <div>

      <!-- Pricing -->
      <div class="whoiscrm-form-section">
        <div class="whoiscrm-form-section__header"><?php esc_html_e('Pricing', 'whois-crm'); ?></div>
        <div class="whoiscrm-form-section__body">

          <div class="whoiscrm-form-group">
            <label class="whoiscrm-form-label"><?php esc_html_e('Monthly Price (USD)', 'whois-crm'); ?></label>
            <div style="position:relative;">
              <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--color-text-muted);">$</span>
              <input type="number" name="monthly_price" class="whoiscrm-input" min="0" step="0.01"
                style="padding-left:28px;"
                value="<?php echo isset($monthly) ? esc_attr(number_format((float)$monthly->price, 2, '.', '')) : ''; ?>"
                placeholder="0.00">
            </div>
            <?php if (!empty($monthly->stripe_price_id)) : ?>
              <p class="whoiscrm-form-hint">
                <?php esc_html_e('Stripe:', 'whois-crm'); ?> <code><?php echo esc_html($monthly->stripe_price_id); ?></code>
              </p>
            <?php endif; ?>
          </div>

          <!-- Annual (global only) -->
          <div class="whoiscrm-annual-pricing-field" style="display:<?php echo ($package->type ?? 'global_service') !== 'country_specific' ? 'block' : 'none'; ?>;">
            <div class="whoiscrm-form-group">
              <label class="whoiscrm-form-label"><?php esc_html_e('Annual Price (USD, optional)', 'whois-crm'); ?></label>
              <div style="position:relative;">
                <span style="position:absolute; left:12px; top:50%; transform:translateY(-50%); color:var(--color-text-muted);">$</span>
                <input type="number" name="annual_price" class="whoiscrm-input" min="0" step="0.01"
                  style="padding-left:28px;"
                  value="<?php echo isset($annual) ? esc_attr(number_format((float)$annual->price, 2, '.', '')) : ''; ?>"
                  placeholder="0.00">
              </div>
              <?php if (!empty($annual->stripe_price_id)) : ?>
                <p class="whoiscrm-form-hint">
                  <?php esc_html_e('Stripe:', 'whois-crm'); ?> <code><?php echo esc_html($annual->stripe_price_id); ?></code>
                </p>
              <?php endif; ?>
            </div>
          </div>

        </div>
      </div>

      <!-- Settings -->
      <div class="whoiscrm-form-section">
        <div class="whoiscrm-form-section__header"><?php esc_html_e('Settings', 'whois-crm'); ?></div>
        <div class="whoiscrm-form-section__body">

          <div class="whoiscrm-form-group" style="display:flex; align-items:center; gap:var(--space-3);">
            <label class="whoiscrm-toggle">
              <input type="checkbox" name="is_active" value="1" <?php checked($package->is_active ?? 1, 1); ?>>
              <span class="whoiscrm-toggle__slider"></span>
            </label>
            <span style="font-size:.875rem;"><?php esc_html_e('Active (visible on pricing page)', 'whois-crm'); ?></span>
          </div>

          <div class="whoiscrm-form-group">
            <label class="whoiscrm-form-label"><?php esc_html_e('Sort Order', 'whois-crm'); ?></label>
            <input type="number" name="sort_order" class="whoiscrm-input" min="0"
              value="<?php echo (int) ($package->sort_order ?? 0); ?>"
              style="max-width:120px;">
          </div>

        </div>
      </div>

      <!-- Save -->
      <button type="submit" class="whoiscrm-btn whoiscrm-btn--primary whoiscrm-btn--md" style="width:100%;">
        <?php echo $is_edit ? esc_html__('Update Package', 'whois-crm') : esc_html__('Create Package', 'whois-crm'); ?>
      </button>

      <?php if ($is_edit) : ?>
      <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:var(--space-3);">
        <?php wp_nonce_field('whoiscrm_package_delete'); ?>
        <input type="hidden" name="action" value="whoiscrm_delete_package">
        <input type="hidden" name="package_id" value="<?php echo (int) $package_id; ?>">
        <button type="submit" class="whoiscrm-btn whoiscrm-btn--ghost whoiscrm-btn--sm" style="color:var(--color-danger); width:100%;"
          data-confirm="<?php esc_attr_e('Delete this package? Active subscriptions will be unaffected but new signups will stop.', 'whois-crm'); ?>">
          <?php esc_html_e('Delete Package', 'whois-crm'); ?>
        </button>
      </form>
      <?php endif; ?>

    </div><!-- /right column -->

  </div><!-- /grid -->
</form>

<script>
(function() {
  'use strict';

  // Features repeater
  document.getElementById('js-add-feature')?.addEventListener('click', function() {
    var html = '<div class="whoiscrm-feature-row" style="display:flex; gap:8px; margin-bottom:8px;"><input type="text" name="features[]" class="whoiscrm-input" placeholder="<?php esc_js(esc_html_e('Feature bullet point…', 'whois-crm')); ?>"><button type="button" class="whoiscrm-btn whoiscrm-btn--ghost whoiscrm-btn--sm js-remove-feature" style="flex-shrink:0;">✕</button></div>';
    document.getElementById('whoiscrm-features-list').insertAdjacentHTML('beforeend', html);
  });

  document.addEventListener('click', function(e) {
    if (e.target.classList.contains('js-remove-feature')) {
      e.target.closest('.whoiscrm-feature-row').remove();
    }
  });

  // Stripe sync button
  var syncBtn = document.getElementById('whoiscrm-stripe-sync-btn');
  if (syncBtn) {
    syncBtn.addEventListener('click', function() {
      var btn      = syncBtn;
      var resultEl = document.getElementById('whoiscrm-stripe-sync-result');
      var original = btn.textContent;

      btn.disabled    = true;
      btn.textContent = '<?php esc_js(esc_html_e('Syncing…', 'whois-crm')); ?>';
      resultEl.textContent = '';

      var data = new FormData();
      data.append('action',     'whoiscrm_sync_package_stripe');
      data.append('nonce',      btn.dataset.nonce);
      data.append('package_id', btn.dataset.packageId);

      fetch('<?php echo esc_js(admin_url('admin-ajax.php')); ?>', {
        method: 'POST',
        body: data,
        credentials: 'same-origin',
      })
      .then(function(r) { return r.json(); })
      .then(function(res) {
        btn.disabled    = false;
        btn.textContent = original;

        if (res.success) {
          resultEl.style.color = '#14803C';
          resultEl.textContent = '✓ ' + res.data.message;
        } else {
          resultEl.style.color = '#C42B2B';
          resultEl.textContent = '✗ ' + (res.data.message || '<?php esc_js(esc_html_e('Sync failed.', 'whois-crm')); ?>');
        }
      })
      .catch(function() {
        btn.disabled    = false;
        btn.textContent = original;
        resultEl.style.color = '#C42B2B';
        resultEl.textContent = '<?php esc_js(esc_html_e('Network error.', 'whois-crm')); ?>';
      });
    });
  }
})();
</script>
