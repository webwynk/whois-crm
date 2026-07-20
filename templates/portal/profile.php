<?php
/**
 * Template: Customer Portal Profile Tab
 *
 * Variables:
 *  $customer  object Customer object
 *  $wp_user   WP_User object
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
?>

<div class="whoiscrm-portal-greeting">
  <h3><?php esc_html_e('Account Profile', 'whois-crm'); ?></h3>
  <p><?php esc_html_e('Update your personal details, business info, and password.', 'whois-crm'); ?></p>
</div>

<div class="whoiscrm-table-wrapper" style="padding: 24px;">
  <form id="whoiscrm-profile-form" method="post" action="" style="display: flex; flex-direction: column; gap: 20px;">
    <?php wp_nonce_field('whoiscrm_update_profile', 'whoiscrm_profile_nonce'); ?>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
      <div class="whoiscrm-filter-group">
        <label class="whoiscrm-filter-label"><?php esc_html_e('First Name', 'whois-crm'); ?></label>
        <input type="text" name="first_name" class="whoiscrm-filter-input" value="<?php echo esc_attr(get_user_meta($customer->user_id, 'first_name', true)); ?>">
      </div>

      <div class="whoiscrm-filter-group">
        <label class="whoiscrm-filter-label"><?php esc_html_e('Last Name', 'whois-crm'); ?></label>
        <input type="text" name="last_name" class="whoiscrm-filter-input" value="<?php echo esc_attr(get_user_meta($customer->user_id, 'last_name', true)); ?>">
      </div>
    </div>

    <div class="whoiscrm-filter-group">
      <label class="whoiscrm-filter-label"><?php esc_html_e('Email Address', 'whois-crm'); ?></label>
      <input type="email" class="whoiscrm-filter-input" value="<?php echo esc_attr($wp_user->user_email); ?>" disabled style="opacity: 0.7; cursor: not-allowed;">
    </div>

    <div class="whoiscrm-filter-group">
      <label class="whoiscrm-filter-label"><?php esc_html_e('Company Name (Optional)', 'whois-crm'); ?></label>
      <input type="text" name="company" class="whoiscrm-filter-input" value="<?php echo esc_attr(get_user_meta($customer->user_id, 'company', true)); ?>">
    </div>

    <div style="padding-top: 16px; border-top: 1px solid var(--color-border); display: flex; justify-content: flex-end;">
      <button type="submit" class="whoiscrm-btn whoiscrm-btn--primary whoiscrm-btn--md">
        <?php esc_html_e('Save Changes', 'whois-crm'); ?>
      </button>
    </div>
  </form>
</div>
