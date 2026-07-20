<?php
/**
 * Template: Customer Portal API Keys Tab
 *
 * Variables:
 *  $customer  object Customer object
 *  $api_key   string Active API key
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
?>

<div class="whoiscrm-portal-greeting">
  <h3><?php esc_html_e('Developer API Keys', 'whois-crm'); ?></h3>
  <p><?php esc_html_e('Use your secure API key to query WHOIS data programmatic REST endpoints directly.', 'whois-crm'); ?></p>
</div>

<div class="whoiscrm-table-wrapper" style="padding: 24px;">
  <div style="margin-bottom: 20px;">
    <label class="whoiscrm-filter-label" style="margin-bottom: 8px; display: block;"><?php esc_html_e('Your Secret API Key', 'whois-crm'); ?></label>
    
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
      <input type="text" id="whoiscrm-api-key-input" class="whoiscrm-filter-input" value="<?php echo esc_attr($customer->api_key ?? ''); ?>" readonly style="font-family: monospace !important; flex: 1; min-width: 240px; background: #fff;">
      <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('whoiscrm-api-key-input').value); alert('API Key copied to clipboard!');" class="whoiscrm-btn whoiscrm-btn--primary whoiscrm-btn--sm" style="height: 38px;">
        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
        <span><?php esc_html_e('Copy Key', 'whois-crm'); ?></span>
      </button>
    </div>
  </div>

  <div style="padding: 16px; background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-lg);">
    <h4 style="margin: 0 0 8px 0; font-size: 0.875rem; font-weight: 700; color: var(--color-text-primary);"><?php esc_html_e('API Usage Header Example:', 'whois-crm'); ?></h4>
    <code style="font-size: 0.8125rem; color: var(--color-primary); word-break: break-all; font-family: monospace;">
      curl -H "X-WHOIS-API-KEY: <?php echo esc_html($customer->api_key ?? 'YOUR_API_KEY'); ?>" <?php echo esc_url(rest_url('whoiscrm/v1/download')); ?>
    </code>
  </div>
</div>
