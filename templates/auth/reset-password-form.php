<?php
/**
 * Template: Reset Password Form
 *
 * Variables:
 *   $nonce    — wp nonce value
 *   $key      — WordPress reset key (from email link)
 *   $login    — WordPress user login
 *   $ajax_url — admin-ajax.php URL
 */
declare(strict_types=1);

if (!defined('ABSPATH')) { exit; }
?>
<div class="whoiscrm-auth-page">
  <div class="whoiscrm-auth-card">

    <div class="whoiscrm-auth-brand">
      <div class="whoiscrm-auth-logo" aria-hidden="true">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"/>
        </svg>
      </div>
      <span class="whoiscrm-auth-site-name"><?php echo esc_html(get_bloginfo('name')); ?></span>
    </div>

    <div class="whoiscrm-auth-heading">
      <h1><?php esc_html_e('Set new password', 'whois-crm'); ?></h1>
      <p><?php esc_html_e('Choose a strong password for your account.', 'whois-crm'); ?></p>
    </div>

    <div class="whoiscrm-auth-message" id="whoiscrm-reset-message" role="alert" aria-live="polite"></div>

    <form id="whoiscrm-reset-form" class="whoiscrm-auth-form" method="post" novalidate>

      <input type="hidden" name="action" value="whoiscrm_reset_password">
      <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">
      <input type="hidden" name="key"   value="<?php echo esc_attr($key); ?>">
      <input type="hidden" name="login" value="<?php echo esc_attr($login); ?>">

      <!-- New password -->
      <div class="whoiscrm-form-group">
        <label for="whoiscrm-reset-password">
          <?php esc_html_e('New Password', 'whois-crm'); ?>
          <span class="required" aria-hidden="true">*</span>
        </label>
        <div class="whoiscrm-password-wrapper">
          <input
            type="password"
            id="whoiscrm-reset-password"
            name="password"
            autocomplete="new-password"
            required
            placeholder="<?php esc_attr_e('Min. 8 characters', 'whois-crm'); ?>"
          >
          <button type="button" class="whoiscrm-password-toggle" data-target="whoiscrm-reset-password" aria-label="<?php esc_attr_e('Show password', 'whois-crm'); ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
        <div class="whoiscrm-password-strength">
          <div class="whoiscrm-password-strength__bar" id="whoiscrm-reset-strength-bar"></div>
        </div>
      </div>

      <!-- Confirm password -->
      <div class="whoiscrm-form-group">
        <label for="whoiscrm-reset-confirm">
          <?php esc_html_e('Confirm Password', 'whois-crm'); ?>
          <span class="required" aria-hidden="true">*</span>
        </label>
        <div class="whoiscrm-password-wrapper">
          <input
            type="password"
            id="whoiscrm-reset-confirm"
            name="confirm"
            autocomplete="new-password"
            required
            placeholder="<?php esc_attr_e('Re-enter password', 'whois-crm'); ?>"
          >
          <button type="button" class="whoiscrm-password-toggle" data-target="whoiscrm-reset-confirm" aria-label="<?php esc_attr_e('Show password', 'whois-crm'); ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>

      <button type="submit" id="whoiscrm-reset-submit" class="whoiscrm-btn-auth">
        <span class="spinner" aria-hidden="true"></span>
        <span class="btn-text"><?php esc_html_e('Reset Password', 'whois-crm'); ?></span>
      </button>

    </form>

  </div>
</div>

<script>
(function() {
  const form      = document.getElementById('whoiscrm-reset-form');
  const msgBox    = document.getElementById('whoiscrm-reset-message');
  const submitBtn = document.getElementById('whoiscrm-reset-submit');
  const pwInput   = document.getElementById('whoiscrm-reset-password');
  const cfInput   = document.getElementById('whoiscrm-reset-confirm');
  const pwBar     = document.getElementById('whoiscrm-reset-strength-bar');
  const ajaxUrl   = <?php echo wp_json_encode($ajax_url); ?>;

  // Password toggles
  document.querySelectorAll('.whoiscrm-password-toggle').forEach(function(btn) {
    btn.addEventListener('click', function() {
      const input = document.getElementById(btn.getAttribute('data-target'));
      if (input) input.type = input.type === 'password' ? 'text' : 'password';
    });
  });

  // Strength bar
  pwInput.addEventListener('input', function() {
    const v = pwInput.value;
    let score = 0;
    if (v.length >= 8) score++;
    if (/[A-Z]/.test(v)) score++;
    if (/[0-9]/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;
    pwBar.className = 'whoiscrm-password-strength__bar' + (score ? ' strength-' + score : '');
  });

  form.addEventListener('submit', function(e) {
    e.preventDefault();
    msgBox.className = 'whoiscrm-auth-message';

    if (pwInput.value.length < 8) {
      showMessage('<?php esc_js(_e('Password must be at least 8 characters.', 'whois-crm')); ?>', 'error');
      return;
    }
    if (pwInput.value !== cfInput.value) {
      showMessage('<?php esc_js(_e('Passwords do not match.', 'whois-crm')); ?>', 'error');
      cfInput.focus();
      return;
    }

    submitBtn.classList.add('is-loading');
    submitBtn.disabled = true;

    fetch(ajaxUrl, { method: 'POST', body: new FormData(form), credentials: 'same-origin' })
      .then(function(r) { return r.json(); })
      .then(function(res) {
        submitBtn.classList.remove('is-loading');
        submitBtn.disabled = false;
        if (res.success) {
          showMessage(res.data.message, 'success');
          setTimeout(function() { window.location.href = res.data.redirect; }, 1200);
        } else {
          showMessage((res.data && res.data.message) || '<?php esc_js(_e('Reset failed.', 'whois-crm')); ?>', 'error');
        }
      })
      .catch(function() {
        submitBtn.classList.remove('is-loading');
        submitBtn.disabled = false;
        showMessage('<?php esc_js(_e('Network error. Please try again.', 'whois-crm')); ?>', 'error');
      });
  });

  function showMessage(text, type) { msgBox.className = 'whoiscrm-auth-message is-' + type; msgBox.textContent = text; }
})();
</script>
