<?php
/**
 * Template: Customer Portal REST API Keys Manager
 *
 * Variables:
 *  $keys   object|null  ApiKey row for this customer (one active key allowed)
 *  $nonce  string       API Action nonce
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
?>

<div style="margin-bottom: var(--space-6);">
  <h3 style="margin: 0 0 var(--space-1) 0; font-size: var(--text-h2); font-weight: 700; color: var(--color-black);">
    <?php esc_html_e('Developer API Settings', 'whois-crm'); ?>
  </h3>
  <p style="margin: 0; color: var(--color-text-secondary); font-size: 0.9375rem;">
    <?php esc_html_e('Generate and manage credentials to access the WHOIS CRM REST API directly from your programs.', 'whois-crm'); ?>
  </p>
</div>

<div class="whoiscrm-form-section" style="max-width: 640px;">
  <div class="whoiscrm-form-section__header"><?php esc_html_e('Your API Key', 'whois-crm'); ?></div>
  <div class="whoiscrm-form-section__body" style="padding: var(--space-6);">
    
    <?php if (empty($keys)) : ?>
      
      <!-- No API key exists -->
      <div style="text-align: center; padding: var(--space-6) 0;">
        <div style="font-size: 2.5rem; margin-bottom: var(--space-3);">🔑</div>
        <h4 style="margin: 0 0 8px 0; color: var(--color-text-primary);"><?php esc_html_e('No Active API Key Found', 'whois-crm'); ?></h4>
        <p style="color: var(--color-text-secondary); max-width: 440px; margin: 0 auto var(--space-6) auto; font-size: 0.875rem; line-height: 1.5;">
          <?php esc_html_e('Generate a secure API Key credential to start querying domain feeds programmatically.', 'whois-crm'); ?>
        </p>
        <button
          type="button"
          id="whoiscrm-generate-api-btn"
          class="whoiscrm-btn whoiscrm-btn--primary whoiscrm-btn--md"
          data-nonce="<?php echo esc_attr($nonce); ?>"
        >
          🔑 <?php esc_html_e('Generate API Key', 'whois-crm'); ?>
        </button>
      </div>

    <?php else : ?>

      <!-- API key exists -->
      <div style="display: flex; flex-direction: column; gap: var(--space-4);">
        
        <div style="background: var(--color-surface); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: var(--space-4); display: flex; align-items: center; justify-content: space-between;">
          <div>
            <span style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: var(--color-text-muted); display: block; margin-bottom: 2px;">
              <?php esc_html_e('API Credential Key', 'whois-crm'); ?>
            </span>
            <code style="font-size: 1.05rem; font-weight: 600; font-family: monospace; letter-spacing: 0.05em; background: #fff; padding: 4px 8px; border-radius: 4px; border: 1px solid var(--color-border);">
              <?php echo esc_html(substr($keys->api_key, 0, 10)); ?>••••••••••••••••••••••••••••
            </code>
          </div>
          <span class="whoiscrm-badge whoiscrm-badge--success">
            <?php esc_html_e('Active', 'whois-crm'); ?>
          </span>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4);">
          <div style="border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: var(--space-4);">
            <strong style="font-size: 0.75rem; color: var(--color-text-muted); text-transform: uppercase; display: block; margin-bottom: 2px;"><?php esc_html_e('Daily Quota Limit', 'whois-crm'); ?></strong>
            <span style="font-size: 1.1rem; font-weight: 600; color: var(--color-text-primary);">
              <?php echo number_format((int) $keys->rate_limit_per_day); ?> <?php esc_html_e('Reqs/day', 'whois-crm'); ?>
            </span>
          </div>

          <div style="border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: var(--space-4);">
            <strong style="font-size: 0.75rem; color: var(--color-text-muted); text-transform: uppercase; display: block; margin-bottom: 2px;"><?php esc_html_e('Used Today', 'whois-crm'); ?></strong>
            <span style="font-size: 1.1rem; font-weight: 600; color: var(--color-primary);">
              <?php echo number_format((int) $keys->requests_today); ?>
            </span>
          </div>
        </div>

        <div style="font-size: 0.8125rem; color: var(--color-text-muted); border-top: 1px solid var(--color-border); padding-top: var(--space-4); display: flex; justify-content: space-between; align-items: center;">
          <span>
            <?php printf(esc_html__('Generated on %s', 'whois-crm'), esc_html(gmdate('Y-m-d H:i:s', strtotime($keys->created_at)))); ?>
          </span>
          <button
            type="button"
            id="whoiscrm-revoke-api-btn"
            class="whoiscrm-btn whoiscrm-btn--danger whoiscrm-btn--sm"
            data-nonce="<?php echo esc_attr($nonce); ?>"
          >
            🛑 <?php esc_html_e('Revoke API Key', 'whois-crm'); ?>
          </button>
        </div>

      </div>

    <?php endif; ?>

  </div>
</div>

<!-- API Reference Notes -->
<div class="whoiscrm-card" style="max-width: 640px; margin-top: var(--space-6);">
  <div class="whoiscrm-card__header">
    <h4 class="whoiscrm-card__title"><?php esc_html_e('REST API Reference Notes', 'whois-crm'); ?></h4>
  </div>
  <div class="whoiscrm-card__body" style="font-size: 0.875rem; line-height: 1.6; color: var(--color-text-secondary);">
    <p style="margin: 0 0 10px 0;">
      <?php esc_html_e('Query endpoints programmatically. Authenticate requests by adding the header:', 'whois-crm'); ?>
    </p>
    <pre style="background: var(--color-surface); padding: 12px; border-radius: 6px; font-family: monospace; font-size: 0.8125rem; border: 1px solid var(--color-border); margin: 0 0 12px 0;">
X-API-Key: YOUR_API_KEY_HERE</pre>
    <p style="margin: 0;">
      <?php esc_html_e('Endpoint base URL:', 'whois-crm'); ?> <code><?php echo esc_url(home_url('/wp-json/whoiscrm/v1/')); ?></code>
    </p>
  </div>
</div>
