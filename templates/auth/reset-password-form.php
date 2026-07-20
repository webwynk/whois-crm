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
<!-- DM Sans Font Preload -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">

<div class="whoiscrm-auth-page">
  <div class="whoiscrm-auth-card">

    <!-- Brand Header -->
    <div class="whoiscrm-auth-brand-header">
      <div class="whoiscrm-auth-logo-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <circle cx="12" cy="12" r="10" stroke="white" stroke-width="1.5" fill="none"/>
          <ellipse cx="12" cy="12" rx="4" ry="10" stroke="white" stroke-width="1.5" fill="none"/>
          <path d="M2 12h20" stroke="white" stroke-width="1.5"/>
        </svg>
      </div>
      <span class="whoiscrm-auth-brand-title"><?php echo esc_html(get_bloginfo('name')); ?></span>
    </div>

    <!-- Heading -->
    <div class="whoiscrm-auth-heading">
      <h1><?php esc_html_e('Set new password', 'whois-crm'); ?></h1>
      <p><?php esc_html_e('Choose a strong password for your account.', 'whois-crm'); ?></p>
    </div>

    <!-- Message box -->
    <div class="whoiscrm-auth-message" id="whoiscrm-reset-message" role="alert" aria-live="polite"></div>

    <!-- Reset Form -->
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
        <div class="whoiscrm-input-icon-wrapper">
          <span class="whoiscrm-input-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="11" width="18" height="11" rx="2"/>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
          </span>
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
              <svg viewBox="0 0 24 24" class="icon-eye"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg viewBox="0 0 24 24" class="icon-eye-off" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
        </div>
        <div class="whoiscrm-password-strength">
          <div class="whoiscrm-password-strength__bar" id="whoiscrm-reset-strength-bar"></div>
        </div>
        <div class="whoiscrm-password-strength__label" id="whoiscrm-reset-strength-label"></div>
      </div>

      <!-- Confirm password -->
      <div class="whoiscrm-form-group">
        <label for="whoiscrm-reset-confirm">
          <?php esc_html_e('Confirm Password', 'whois-crm'); ?>
          <span class="required" aria-hidden="true">*</span>
        </label>
        <div class="whoiscrm-input-icon-wrapper">
          <span class="whoiscrm-input-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
          </span>
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
              <svg viewBox="0 0 24 24" class="icon-eye"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg viewBox="0 0 24 24" class="icon-eye-off" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
        </div>
      </div>

      <button type="submit" id="whoiscrm-reset-submit" class="whoiscrm-btn-auth">
        <span class="spinner" aria-hidden="true"></span>
        <span class="btn-text"><?php esc_html_e('Reset Password', 'whois-crm'); ?></span>
      </button>

    </form>

  </div><!-- .whoiscrm-auth-card -->
</div><!-- .whoiscrm-auth-page -->

<script>
(function() {
  const form      = document.getElementById('whoiscrm-reset-form');
  const msgBox    = document.getElementById('whoiscrm-reset-message');
  const submitBtn = document.getElementById('whoiscrm-reset-submit');
  const pwInput   = document.getElementById('whoiscrm-reset-password');
  const cfInput   = document.getElementById('whoiscrm-reset-confirm');
  const pwBar     = document.getElementById('whoiscrm-reset-strength-bar');
  const pwLabel   = document.getElementById('whoiscrm-reset-strength-label');
  const ajaxUrl   = <?php echo wp_json_encode($ajax_url); ?>;

  // Password toggles
  document.querySelectorAll('.whoiscrm-password-toggle').forEach(function(btn) {
    btn.addEventListener('click', function() {
      const input = document.getElementById(btn.getAttribute('data-target'));
      if (!input) return;
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.querySelector('.icon-eye').style.display     = show ? 'none' : '';
      btn.querySelector('.icon-eye-off').style.display = show ? ''     : 'none';
    });
  });

  // Strength bar
  const strengthLabels = ['', '<?php echo esc_js(__('Weak', 'whois-crm')); ?>', '<?php echo esc_js(__('Fair', 'whois-crm')); ?>', '<?php echo esc_js(__('Good', 'whois-crm')); ?>', '<?php echo esc_js(__('Strong', 'whois-crm')); ?>'];
  pwInput.addEventListener('input', function() {
    const v = pwInput.value;
    let score = 0;
    if (v.length >= 8)           score++;
    if (/[A-Z]/.test(v))         score++;
    if (/[0-9]/.test(v))         score++;
    if (/[^A-Za-z0-9]/.test(v))  score++;
    pwBar.className     = 'whoiscrm-password-strength__bar' + (score ? ' strength-' + score : '');
    pwLabel.textContent = score ? strengthLabels[score] : '';
  });

  form.addEventListener('submit', function(e) {
    e.preventDefault();
    msgBox.className = 'whoiscrm-auth-message';
    msgBox.textContent = '';

    if (pwInput.value.length < 8) {
      showMessage('<?php echo esc_js(__('Password must be at least 8 characters.', 'whois-crm')); ?>', 'error');
      return;
    }
    if (pwInput.value !== cfInput.value) {
      showMessage('<?php echo esc_js(__('Passwords do not match.', 'whois-crm')); ?>', 'error');
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
          showMessage((res.data && res.data.message) || '<?php echo esc_js(__('Reset failed.', 'whois-crm')); ?>', 'error');
        }
      })
      .catch(function() {
        submitBtn.classList.remove('is-loading');
        submitBtn.disabled = false;
        showMessage('<?php echo esc_js(__('Network error. Please try again.', 'whois-crm')); ?>', 'error');
      });
  });

  function showMessage(t, tp) { msgBox.className = 'whoiscrm-auth-message is-' + tp; msgBox.textContent = t; }
})();
</script>
