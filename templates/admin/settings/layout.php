<?php
/**
 * Template: Settings — Layout wrapper with tabs
 *
 * Variables:
 *  $tabs        array  ['tab_key' => 'Label']
 *  $active_tab  string
 *  $options     array  all wp_options values
 *  $pages_list  array  [page_id => 'Page Title']
 *  $nonce       string
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
?>

<!-- Tab navigation -->
<nav class="whoiscrm-tabs" aria-label="<?php esc_attr_e('Settings tabs', 'whois-crm'); ?>">
  <?php foreach ($tabs as $key => $label) : ?>
    <a
      href="<?php echo esc_url(admin_url("admin.php?page=whoiscrm-settings&tab={$key}")); ?>"
      class="whoiscrm-tab <?php echo $key === $active_tab ? 'is-active' : ''; ?>"
      aria-current="<?php echo $key === $active_tab ? 'page' : 'false'; ?>"
    >
      <?php echo esc_html(__($label, 'whois-crm')); ?>
    </a>
  <?php endforeach; ?>
</nav>

<form method="post" action="<?php echo esc_url(admin_url("admin.php?page=whoiscrm-settings&tab={$active_tab}")); ?>">
  <?php wp_nonce_field('whoiscrm_settings_nonce', 'whoiscrm_settings_nonce'); ?>
  <input type="hidden" name="current_tab" value="<?php echo esc_attr($active_tab); ?>">

  <?php

  // ── Render the active tab's content ──
  switch ($active_tab) :

    // ── Payment ─────────────────────────────────────────────────────────
    case 'payment': ?>
      <div class="whoiscrm-form-section">
        <div class="whoiscrm-form-section__header"><?php esc_html_e('Stripe Configuration', 'whois-crm'); ?></div>
        <div class="whoiscrm-form-section__body">

          <div class="whoiscrm-form-group">
            <label class="whoiscrm-form-label" for="whoiscrm-stripe-mode"><?php esc_html_e('Mode', 'whois-crm'); ?></label>
            <select id="whoiscrm-stripe-mode" name="whoiscrm_stripe_mode" class="whoiscrm-select" style="max-width:240px;">
              <option value="test" <?php selected($options['whoiscrm_stripe_mode'], 'test'); ?>><?php esc_html_e('Test (Sandbox)', 'whois-crm'); ?></option>
              <option value="live" <?php selected($options['whoiscrm_stripe_mode'], 'live'); ?>><?php esc_html_e('Live (Production)', 'whois-crm'); ?></option>
            </select>
          </div>

          <div class="whoiscrm-stripe-test-fields">
            <div class="whoiscrm-form-group">
              <label class="whoiscrm-form-label"><?php esc_html_e('Test Publishable Key', 'whois-crm'); ?></label>
              <input type="text" name="whoiscrm_stripe_test_publishable_key" class="whoiscrm-input" value="<?php echo esc_attr($options['whoiscrm_stripe_test_publishable_key']); ?>" placeholder="pk_test_…" autocomplete="off">
            </div>
            <div class="whoiscrm-form-group">
              <label class="whoiscrm-form-label"><?php esc_html_e('Test Secret Key', 'whois-crm'); ?></label>
              <input type="password" name="whoiscrm_stripe_test_secret_key" class="whoiscrm-input" value="<?php echo esc_attr($options['whoiscrm_stripe_test_secret_key']); ?>" placeholder="sk_test_…" autocomplete="new-password">
            </div>
          </div>

          <div class="whoiscrm-stripe-live-fields">
            <div class="whoiscrm-form-group">
              <label class="whoiscrm-form-label"><?php esc_html_e('Live Publishable Key', 'whois-crm'); ?></label>
              <input type="text" name="whoiscrm_stripe_live_publishable_key" class="whoiscrm-input" value="<?php echo esc_attr($options['whoiscrm_stripe_live_publishable_key']); ?>" placeholder="pk_live_…" autocomplete="off">
            </div>
            <div class="whoiscrm-form-group">
              <label class="whoiscrm-form-label"><?php esc_html_e('Live Secret Key', 'whois-crm'); ?></label>
              <input type="password" name="whoiscrm_stripe_live_secret_key" class="whoiscrm-input" value="<?php echo esc_attr($options['whoiscrm_stripe_live_secret_key']); ?>" placeholder="sk_live_…" autocomplete="new-password">
            </div>
          </div>

          <div class="whoiscrm-form-group">
            <label class="whoiscrm-form-label"><?php esc_html_e('Webhook Signing Secret', 'whois-crm'); ?></label>
            <input type="password" name="whoiscrm_stripe_webhook_secret" class="whoiscrm-input" value="<?php echo esc_attr($options['whoiscrm_stripe_webhook_secret']); ?>" placeholder="whsec_…" autocomplete="new-password">
            <p class="whoiscrm-form-hint"><?php esc_html_e('Found in the Stripe Dashboard → Developers → Webhooks.', 'whois-crm'); ?></p>
          </div>

        </div>
      </div>
      <?php break;

    // ── Tax ─────────────────────────────────────────────────────────────
    case 'tax': ?>
      <div class="whoiscrm-form-section">
        <div class="whoiscrm-form-section__header"><?php esc_html_e('Tax Settings', 'whois-crm'); ?></div>
        <div class="whoiscrm-form-section__body">

          <div class="whoiscrm-form-group" style="display:flex; align-items:center; gap:var(--space-3);">
            <label class="whoiscrm-toggle">
              <input type="checkbox" name="whoiscrm_tax_enabled" value="1" <?php checked($options['whoiscrm_tax_enabled'], 1); ?>>
              <span class="whoiscrm-toggle__slider"></span>
            </label>
            <span><?php esc_html_e('Enable tax calculation on invoices', 'whois-crm'); ?></span>
          </div>

          <div class="whoiscrm-form-group">
            <label class="whoiscrm-form-label"><?php esc_html_e('Default Tax Rate (%)', 'whois-crm'); ?></label>
            <input type="number" step="0.01" min="0" max="100" name="whoiscrm_default_tax_rate" class="whoiscrm-input" style="max-width:160px;" value="<?php echo esc_attr($options['whoiscrm_default_tax_rate']); ?>">
          </div>

          <div class="whoiscrm-form-group">
            <label class="whoiscrm-form-label"><?php esc_html_e('Tax Label', 'whois-crm'); ?></label>
            <input type="text" name="whoiscrm_tax_label" class="whoiscrm-input" style="max-width:200px;" value="<?php echo esc_attr($options['whoiscrm_tax_label']); ?>" placeholder="Tax, GST, VAT…">
          </div>

          <div class="whoiscrm-form-group">
            <label class="whoiscrm-form-label"><?php esc_html_e('Country-Specific Tax Rates (JSON)', 'whois-crm'); ?></label>
            <textarea name="whoiscrm_tax_rates" class="whoiscrm-textarea" rows="5" placeholder='{"IN": 18, "AU": 10}'><?php echo esc_textarea($options['whoiscrm_tax_rates']); ?></textarea>
            <p class="whoiscrm-form-hint"><?php esc_html_e('JSON object mapping ISO country codes to tax percentages. Overrides the default rate for matched countries.', 'whois-crm'); ?></p>
          </div>

        </div>
      </div>
      <?php break;

    // ── Invoice ──────────────────────────────────────────────────────────
    case 'invoice': ?>
      <div class="whoiscrm-form-section">
        <div class="whoiscrm-form-section__header"><?php esc_html_e('Seller / Company Details', 'whois-crm'); ?></div>
        <div class="whoiscrm-form-section__body">

          <div class="whoiscrm-form-group">
            <label class="whoiscrm-form-label"><?php esc_html_e('Company Name', 'whois-crm'); ?></label>
            <input type="text" name="whoiscrm_seller_name" class="whoiscrm-input" value="<?php echo esc_attr($options['whoiscrm_seller_name']); ?>">
          </div>

          <div class="whoiscrm-form-group">
            <label class="whoiscrm-form-label"><?php esc_html_e('Company Address', 'whois-crm'); ?></label>
            <textarea name="whoiscrm_seller_address" class="whoiscrm-textarea" rows="3"><?php echo esc_textarea($options['whoiscrm_seller_address']); ?></textarea>
          </div>

          <div class="whoiscrm-form-group">
            <label class="whoiscrm-form-label"><?php esc_html_e('Tax ID / GST / VAT Number', 'whois-crm'); ?></label>
            <input type="text" name="whoiscrm_seller_tax_id" class="whoiscrm-input" value="<?php echo esc_attr($options['whoiscrm_seller_tax_id']); ?>" placeholder="GST: 07AAKCK0000L1ZA">
          </div>

        </div>
      </div>
      <?php break;

    // ── Email ────────────────────────────────────────────────────────────
    case 'email': ?>
      <div class="whoiscrm-form-section">
        <div class="whoiscrm-form-section__header"><?php esc_html_e('Email Sender Settings', 'whois-crm'); ?></div>
        <div class="whoiscrm-form-section__body">

          <div class="whoiscrm-form-group">
            <label class="whoiscrm-form-label" for="whoiscrm-email-from-name"><?php esc_html_e('From Name', 'whois-crm'); ?></label>
            <input type="text" id="whoiscrm-email-from-name" name="whoiscrm_email_from_name" class="whoiscrm-input" value="<?php echo esc_attr($options['whoiscrm_email_from_name']); ?>">
          </div>

          <div class="whoiscrm-form-group">
            <label class="whoiscrm-form-label" for="whoiscrm-email-from-address"><?php esc_html_e('From Email Address', 'whois-crm'); ?></label>
            <input type="email" id="whoiscrm-email-from-address" name="whoiscrm_email_from_address" class="whoiscrm-input" value="<?php echo esc_attr($options['whoiscrm_email_from_address']); ?>">
          </div>

          <p class="whoiscrm-form-hint">
            <?php esc_html_e('Preview:', 'whois-crm'); ?>
            <code id="whoiscrm-from-preview" style="background:var(--color-surface-overlay); padding:2px 8px; border-radius:4px;">
              "<?php echo esc_html($options['whoiscrm_email_from_name']); ?>" &lt;<?php echo esc_html($options['whoiscrm_email_from_address']); ?>&gt;
            </code>
          </p>

        </div>
      </div>
      <?php break;

    // ── Pages ────────────────────────────────────────────────────────────
    case 'pages': ?>
      <div class="whoiscrm-form-section">
        <div class="whoiscrm-form-section__header"><?php esc_html_e('WordPress Page Assignments', 'whois-crm'); ?></div>
        <div class="whoiscrm-form-section__body">
          <p class="whoiscrm-form-hint" style="margin-bottom:var(--space-5);">
            <?php esc_html_e('Assign existing WordPress pages to each CRM function. The page must have the corresponding shortcode.', 'whois-crm'); ?>
          </p>

          <?php
          $page_fields = [
            'whoiscrm_login_page_id'           => [__('Login Page', 'whois-crm'),           '[whoiscrm_login]'],
            'whoiscrm_register_page_id'        => [__('Register Page', 'whois-crm'),         '[whoiscrm_register]'],
            'whoiscrm_forgot_password_page_id' => [__('Forgot Password Page', 'whois-crm'),  '[whoiscrm_forgot_password]'],
            'whoiscrm_reset_password_page_id'  => [__('Reset Password Page', 'whois-crm'),   '[whoiscrm_reset_password]'],
            'whoiscrm_portal_page_id'          => [__('Customer Portal Page', 'whois-crm'),  '[whoiscrm_portal]'],
            'whoiscrm_pricing_page_id'         => [__('Pricing Page', 'whois-crm'),          '[whoiscrm_pricing]'],
          ];

          foreach ($page_fields as $option_key => [$label, $shortcode]) : ?>
          <div class="whoiscrm-form-group" style="display:grid; grid-template-columns:220px 1fr; align-items:center; gap:var(--space-4);">
            <label class="whoiscrm-form-label" style="margin:0;">
              <?php echo esc_html($label); ?>
              <code style="font-size:.75rem; color:var(--color-text-muted); display:block; font-weight:400;"><?php echo esc_html($shortcode); ?></code>
            </label>
            <select name="<?php echo esc_attr($option_key); ?>" class="whoiscrm-select">
              <?php foreach ($pages_list as $page_id => $page_title) : ?>
                <option value="<?php echo (int) $page_id; ?>" <?php selected($options[$option_key], $page_id); ?>>
                  <?php echo esc_html($page_title); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endforeach; ?>

        </div>
      </div>
      <?php break;

    // ── Security ─────────────────────────────────────────────────────────
    case 'security': ?>
      <div class="whoiscrm-form-section">
        <div class="whoiscrm-form-section__header"><?php esc_html_e('Rate Limits', 'whois-crm'); ?></div>
        <div class="whoiscrm-form-section__body">

          <div class="whoiscrm-form-group">
            <label class="whoiscrm-form-label"><?php esc_html_e('Max Downloads Per Day (per customer)', 'whois-crm'); ?></label>
            <input type="number" min="1" name="whoiscrm_download_rate_limit" class="whoiscrm-input" style="max-width:160px;" value="<?php echo (int) $options['whoiscrm_download_rate_limit']; ?>">
          </div>

          <div class="whoiscrm-form-group">
            <label class="whoiscrm-form-label"><?php esc_html_e('Default API Requests Per Day (per key)', 'whois-crm'); ?></label>
            <input type="number" min="1" name="whoiscrm_api_rate_limit" class="whoiscrm-input" style="max-width:160px;" value="<?php echo (int) $options['whoiscrm_api_rate_limit']; ?>">
          </div>

        </div>
      </div>
      <?php break;

    // ── Upload ───────────────────────────────────────────────────────────
    case 'upload': ?>
      <div class="whoiscrm-form-section">
        <div class="whoiscrm-form-section__header"><?php esc_html_e('Upload Settings', 'whois-crm'); ?></div>
        <div class="whoiscrm-form-section__body">

          <div class="whoiscrm-form-group">
            <label class="whoiscrm-form-label"><?php esc_html_e('Max Upload Size (MB)', 'whois-crm'); ?></label>
            <input type="number" min="1" max="2048" name="whoiscrm_max_upload_size" class="whoiscrm-input" style="max-width:160px;" value="<?php echo (int) $options['whoiscrm_max_upload_size']; ?>">
            <p class="whoiscrm-form-hint"><?php esc_html_e('PHP memory and upload_max_filesize must also support this value.', 'whois-crm'); ?></p>
          </div>

        </div>
      </div>
      <?php break;

  endswitch; ?>

  <!-- Save button -->
  <div style="margin-top: var(--space-5);">
    <button type="submit" class="whoiscrm-btn whoiscrm-btn--primary whoiscrm-btn--md">
      <?php esc_html_e('Save Settings', 'whois-crm'); ?>
    </button>
  </div>

</form>
