<?php
/**
 * Template: Admin Coupon Form (Add / Edit)
 *
 * Variables:
 *  $coupon  object|null  Coupon row if editing
 *  $nonce   string       Security nonce
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

$all_packages = (new \WhoisCRM\Database\Models\Package())->get_all('name', 'ASC');

// Parse applicable packages from JSON
$selected_packages = [];
if ($coupon && !empty($coupon->applicable_packages)) {
    $selected_packages = json_decode($coupon->applicable_packages, true) ?: [];
}

$starts_at_val  = $coupon && $coupon->starts_at ? gmdate('Y-m-d\TH:i', strtotime($coupon->starts_at)) : '';
$expires_at_val = $coupon && $coupon->expires_at ? gmdate('Y-m-d\TH:i', strtotime($coupon->expires_at)) : '';
?>

<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
  <input type="hidden" name="action" value="whoiscrm_coupon_action">
  <input type="hidden" name="whoiscrm_action" value="save">
  <input type="hidden" name="coupon_id" value="<?php echo $coupon ? (int) $coupon->id : 0; ?>">
  <?php wp_nonce_field('whoiscrm_coupon_action'); ?>

  <div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--space-6);">
    
    <!-- Left Column: Details -->
    <div>
      <div class="whoiscrm-card" style="margin-bottom: var(--space-6);">
        <div class="whoiscrm-card__header"><?php esc_html_e('Coupon Information', 'whois-crm'); ?></div>
        <div class="whoiscrm-card__body">
          
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
            <div class="whoiscrm-form-group">
              <label class="whoiscrm-form-label" for="code"><?php esc_html_e('Coupon Code', 'whois-crm'); ?> <span style="color:var(--color-danger)">*</span></label>
              <input
                type="text"
                name="code"
                id="code"
                class="whoiscrm-input"
                required
                style="text-transform: uppercase;"
                placeholder="SAVE30"
                value="<?php echo $coupon ? esc_attr($coupon->code) : ''; ?>"
              >
              <p class="whoiscrm-form-hint"><?php esc_html_e('Alphanumeric code customers use on checkout.', 'whois-crm'); ?></p>
            </div>
            
            <div class="whoiscrm-form-group">
              <label class="whoiscrm-form-label" for="type"><?php esc_html_e('Discount Type', 'whois-crm'); ?></label>
              <select name="type" id="type" class="whoiscrm-select">
                <option value="percentage" <?php selected($coupon ? $coupon->type : '', 'percentage'); ?>><?php esc_html_e('Percentage Discount (%)', 'whois-crm'); ?></option>
                <option value="fixed" <?php selected($coupon ? $coupon->type : '', 'fixed'); ?>><?php esc_html_e('Flat Rate Fixed Discount ($)', 'whois-crm'); ?></option>
              </select>
            </div>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
            <div class="whoiscrm-form-group">
              <label class="whoiscrm-form-label" for="value"><?php esc_html_e('Discount Value', 'whois-crm'); ?> <span style="color:var(--color-danger)">*</span></label>
              <input
                type="number"
                step="0.01"
                min="0.01"
                name="value"
                id="value"
                class="whoiscrm-input"
                required
                placeholder="10.00"
                value="<?php echo $coupon ? esc_attr((string)$coupon->value) : ''; ?>"
              >
            </div>
            
            <div class="whoiscrm-form-group">
              <label class="whoiscrm-form-label" for="min_amount"><?php esc_html_e('Minimum Purchase Subtotal', 'whois-crm'); ?></label>
              <input
                type="number"
                step="0.01"
                min="0"
                name="min_amount"
                id="min_amount"
                class="whoiscrm-input"
                placeholder="0.00"
                value="<?php echo $coupon && $coupon->min_amount !== null ? esc_attr((string)$coupon->min_amount) : ''; ?>"
              >
            </div>
          </div>

          <div class="whoiscrm-form-group">
            <label class="whoiscrm-form-label" for="description"><?php esc_html_e('Description / Internal Notes', 'whois-crm'); ?></label>
            <textarea name="description" id="description" class="whoiscrm-textarea" rows="3" placeholder="<?php esc_attr_e('Internal note on target campaign...', 'whois-crm'); ?>"><?php echo $coupon ? esc_textarea($coupon->description) : ''; ?></textarea>
          </div>

        </div>
      </div>

      <!-- Expirations & Limits Card -->
      <div class="whoiscrm-card" style="margin-bottom: var(--space-6);">
        <div class="whoiscrm-card__header"><?php esc_html_e('Limits & Expiration Settings', 'whois-crm'); ?></div>
        <div class="whoiscrm-card__body">
          
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
            <div class="whoiscrm-form-group">
              <label class="whoiscrm-form-label" for="max_uses"><?php esc_html_e('Total Max Uses (Global Limit)', 'whois-crm'); ?></label>
              <input
                type="number"
                min="1"
                name="max_uses"
                id="max_uses"
                class="whoiscrm-input"
                placeholder="<?php esc_attr_e('Leave blank for unlimited', 'whois-crm'); ?>"
                value="<?php echo $coupon && $coupon->max_uses !== null ? esc_attr((string)$coupon->max_uses) : ''; ?>"
              >
            </div>
            
            <div class="whoiscrm-form-group">
              <label class="whoiscrm-form-label" for="max_uses_per_customer"><?php esc_html_e('Max Uses Per Customer', 'whois-crm'); ?></label>
              <input
                type="number"
                min="1"
                name="max_uses_per_customer"
                id="max_uses_per_customer"
                class="whoiscrm-input"
                placeholder="1"
                value="<?php echo $coupon && $coupon->max_uses_per_customer !== null ? esc_attr((string)$coupon->max_uses_per_customer) : '1'; ?>"
              >
            </div>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
            <div class="whoiscrm-form-group">
              <label class="whoiscrm-form-label" for="starts_at"><?php esc_html_e('Start Date & Time', 'whois-crm'); ?></label>
              <input type="datetime-local" name="starts_at" id="starts_at" class="whoiscrm-input" value="<?php echo esc_attr($starts_at_val); ?>">
            </div>
            
            <div class="whoiscrm-form-group">
              <label class="whoiscrm-form-label" for="expires_at"><?php esc_html_e('Expiry Date & Time', 'whois-crm'); ?></label>
              <input type="datetime-local" name="expires_at" id="expires_at" class="whoiscrm-input" value="<?php echo esc_attr($expires_at_val); ?>">
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- Right Column: Exclusions & Saves -->
    <div>
      <div class="whoiscrm-card" style="margin-bottom: var(--space-6);">
        <div class="whoiscrm-card__header"><?php esc_html_e('Applicable Plans', 'whois-crm'); ?></div>
        <div class="whoiscrm-card__body">
          <p class="whoiscrm-form-hint" style="margin: 0 0 var(--space-3) 0;">
            <?php esc_html_e('Select which packages this coupon code applies to. Leave blank to make it applicable globally to all pricing models.', 'whois-crm'); ?>
          </p>

          <div style="max-height: 250px; overflow-y: auto; border: 1px solid #E8E8EF; padding: 10px; border-radius: 6px;">
            <?php foreach ($all_packages as $package) : ?>
              <div style="margin-bottom: var(--space-2); display: flex; align-items: center; gap: 8px;">
                <input
                  type="checkbox"
                  name="applicable_packages[]"
                  id="pkg_<?php echo (int) $package->id; ?>"
                  value="<?php echo (int) $package->id; ?>"
                  <?php checked(in_array((int) $package->id, $selected_packages, true)); ?>
                >
                <label for="pkg_<?php echo (int) $package->id; ?>" style="font-size: 0.875rem; color: var(--color-text-primary); cursor: pointer;">
                  <?php echo esc_html($package->name); ?>
                </label>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="whoiscrm-card">
        <div class="whoiscrm-card__body" style="padding: var(--space-4);">
          <div class="whoiscrm-form-group" style="margin-bottom: var(--space-4);">
            <div style="display: flex; align-items: center; gap: 8px;">
              <input
                type="checkbox"
                name="is_active"
                id="is_active"
                value="1"
                <?php checked(!$coupon || $coupon->is_active); ?>
              >
              <label for="is_active" style="font-weight: 600; cursor: pointer;"><?php esc_html_e('Active & Enabled', 'whois-crm'); ?></label>
            </div>
          </div>

          <button type="submit" class="whoiscrm-btn whoiscrm-btn--primary whoiscrm-btn--md" style="width: 100%;">
            <?php echo $coupon ? __('Save Changes', 'whois-crm') : __('Create Coupon', 'whois-crm'); ?>
          </button>
          
          <a
            href="<?php echo esc_url(admin_url('admin.php?page=whoiscrm-coupons')); ?>"
            class="whoiscrm-btn whoiscrm-btn--ghost whoiscrm-btn--md"
            style="width: 100%; text-align: center; margin-top: var(--space-2); display: block;"
          >
            <?php esc_html_e('Cancel', 'whois-crm'); ?>
          </a>
        </div>
      </div>
    </div>

  </div>
</form>
