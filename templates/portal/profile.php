<?php
/**
 * Template: Customer Portal Profile Editing Form
 *
 * Variables:
 *  $customer  object  CRM customer row
 *  $wp_user   object  WP User object
 *  $nonce     string  Profile security nonce
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

$country_options = \WhoisCRM\Helpers\CountryList::all();

// Decode billing address fields if stored as JSON
$billing = [
    'company'  => $customer->company_name ?? '',
    'address'  => $customer->billing_address ?? '',
    'phone'    => $customer->phone ?? '',
    'country'  => $customer->country_code ?? '',
    'tax_id'   => $customer->tax_id ?? '',
];
?>

<div style="margin-bottom: var(--space-6);">
  <h3 style="margin: 0 0 var(--space-1) 0; font-size: var(--text-h2); font-weight: 700; color: var(--color-black);">
    <?php esc_html_e('Profile & Account Settings', 'whois-crm'); ?>
  </h3>
  <p style="margin: 0; color: var(--color-text-secondary); font-size: 0.9375rem;">
    <?php esc_html_e('Keep your contact information, company billing details, and account password up to date.', 'whois-crm'); ?>
  </p>
</div>

<!-- AJAX Status Container -->
<div id="whoiscrm-profile-status-msg" style="display: none; margin-bottom: var(--space-5);"></div>

<form id="whoiscrm-profile-form" method="post">
  <input type="hidden" name="action" value="whoiscrm_update_profile">
  <input type="hidden" name="nonce"  value="<?php echo esc_attr($nonce); ?>">

  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-6); align-items: flex-start;">
    
    <!-- Left Column: Personal and Account Info -->
    <div>
      <div class="whoiscrm-form-section">
        <div class="whoiscrm-form-section__header"><?php esc_html_e('Personal Information', 'whois-crm'); ?></div>
        <div class="whoiscrm-form-section__body">
          
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
            <div class="whoiscrm-form-group">
              <label class="whoiscrm-form-label" for="first_name"><?php esc_html_e('First Name', 'whois-crm'); ?></label>
              <input type="text" name="first_name" id="first_name" class="whoiscrm-input" value="<?php echo esc_attr($wp_user->first_name); ?>">
            </div>
            <div class="whoiscrm-form-group">
              <label class="whoiscrm-form-label" for="last_name"><?php esc_html_e('Last Name', 'whois-crm'); ?></label>
              <input type="text" name="last_name" id="last_name" class="whoiscrm-input" value="<?php echo esc_attr($wp_user->last_name); ?>">
            </div>
          </div>

          <div class="whoiscrm-form-group">
            <label class="whoiscrm-form-label" for="email"><?php esc_html_e('Email Address', 'whois-crm'); ?> <span style="color:var(--color-danger)">*</span></label>
            <input type="email" name="email" id="email" class="whoiscrm-input" required value="<?php echo esc_attr($wp_user->user_email); ?>">
            <p class="whoiscrm-form-hint"><?php esc_html_e('Used for dashboard login and billing notifications.', 'whois-crm'); ?></p>
          </div>

        </div>
      </div>

      <!-- Password Update -->
      <div class="whoiscrm-form-section">
        <div class="whoiscrm-form-section__header"><?php esc_html_e('Update Password', 'whois-crm'); ?></div>
        <div class="whoiscrm-form-section__body">
          
          <div class="whoiscrm-form-group">
            <label class="whoiscrm-form-label" for="current_password"><?php esc_html_e('Current Password', 'whois-crm'); ?></label>
            <input type="password" name="current_password" id="current_password" class="whoiscrm-input" autocomplete="current-password">
            <p class="whoiscrm-form-hint"><?php esc_html_e('Required only if you wish to change your password.', 'whois-crm'); ?></p>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
            <div class="whoiscrm-form-group">
              <label class="whoiscrm-form-label" for="new_password"><?php esc_html_e('New Password', 'whois-crm'); ?></label>
              <input type="password" name="new_password" id="new_password" class="whoiscrm-input" autocomplete="new-password">
            </div>
            <div class="whoiscrm-form-group">
              <label class="whoiscrm-form-label" for="confirm_password"><?php esc_html_e('Confirm New Password', 'whois-crm'); ?></label>
              <input type="password" name="confirm_password" id="confirm_password" class="whoiscrm-input" autocomplete="new-password">
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- Right Column: Billing and Company Info -->
    <div>
      <div class="whoiscrm-form-section">
        <div class="whoiscrm-form-section__header"><?php esc_html_e('Company & Billing Details', 'whois-crm'); ?></div>
        <div class="whoiscrm-form-section__body">
          
          <div class="whoiscrm-form-group">
            <label class="whoiscrm-form-label" for="company_name"><?php esc_html_e('Company Name', 'whois-crm'); ?></label>
            <input type="text" name="company_name" id="company_name" class="whoiscrm-input" value="<?php echo esc_attr($billing['company']); ?>">
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
            <div class="whoiscrm-form-group">
              <label class="whoiscrm-form-label" for="phone"><?php esc_html_e('Phone Number', 'whois-crm'); ?></label>
              <input type="tel" name="phone" id="phone" class="whoiscrm-input" value="<?php echo esc_attr($billing['phone']); ?>">
            </div>
            <div class="whoiscrm-form-group">
              <label class="whoiscrm-form-label" for="tax_id"><?php esc_html_e('Company Tax ID / GSTIN / VAT', 'whois-crm'); ?></label>
              <input type="text" name="tax_id" id="tax_id" class="whoiscrm-input" value="<?php echo esc_attr($billing['tax_id']); ?>" placeholder="e.g. VAT / GST...">
            </div>
          </div>

          <div class="whoiscrm-form-group">
            <label class="whoiscrm-form-label" for="country_code"><?php esc_html_e('Country', 'whois-crm'); ?></label>
            <select name="country_code" id="country_code" class="whoiscrm-select">
              <option value=""><?php esc_html_e('— Select Country —', 'whois-crm'); ?></option>
              <?php foreach ($country_options as $code => $name) : ?>
                <option value="<?php echo esc_attr($code); ?>" <?php selected($billing['country'], $code); ?>>
                  <?php echo esc_html($name); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="whoiscrm-form-group">
            <label class="whoiscrm-form-label" for="billing_address"><?php esc_html_e('Full Billing Address', 'whois-crm'); ?></label>
            <textarea name="billing_address" id="billing_address" class="whoiscrm-textarea" rows="4" placeholder="<?php esc_html_e('Street address, suite, city, state, postal code...', 'whois-crm'); ?>"><?php echo esc_textarea($billing['address']); ?></textarea>
          </div>

        </div>
      </div>

      <button type="submit" class="whoiscrm-btn whoiscrm-btn--primary whoiscrm-btn--md" style="width: 100%;">
        💾 <?php esc_html_e('Save Changes', 'whois-crm'); ?>
      </button>
    </div>

  </div>
</form>
