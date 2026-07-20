<?php
/**
 * Template: Login Form
 *
 * Variables available:
 *   $nonce        — wp nonce value
 *   $redirect_to  — optional redirect URL after login
 *   $register_url — URL to register page
 *   $forgot_url   — URL to forgot password page
 *   $ajax_url     — WordPress admin-ajax.php URL
 */
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}
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
      <h1><?php esc_html_e('Welcome back', 'whois-crm'); ?></h1>
      <p><?php esc_html_e('Sign in to access your data portal.', 'whois-crm'); ?></p>
    </div>

    <!-- Message box -->
    <div class="whoiscrm-auth-message" id="whoiscrm-login-message" role="alert" aria-live="polite"></div>

    <!-- Login Form -->
    <form id="whoiscrm-login-form" class="whoiscrm-auth-form" method="post" novalidate>

      <input type="hidden" name="action" value="whoiscrm_login">
      <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">
      <?php if (!empty($redirect_to)) : ?>
      <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_to); ?>">
      <?php endif; ?>

      <!-- Email -->
      <div class="whoiscrm-form-group">
        <label for="whoiscrm-login-email">
          <?php esc_html_e('Email Address', 'whois-crm'); ?>
          <span class="required" aria-hidden="true">*</span>
        </label>
        <div class="whoiscrm-input-icon-wrapper">
          <span class="whoiscrm-input-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="2" y="4" width="20" height="16" rx="2"/>
              <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
            </svg>
          </span>
          <input
            type="email"
            id="whoiscrm-login-email"
            name="email"
            autocomplete="email"
            required
            placeholder="<?php esc_attr_e('you@example.com', 'whois-crm'); ?>"
            aria-required="true"
          >
        </div>
      </div>

      <!-- Password -->
      <div class="whoiscrm-form-group">
        <label for="whoiscrm-login-password">
          <?php esc_html_e('Password', 'whois-crm'); ?>
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
              id="whoiscrm-login-password"
              name="password"
              autocomplete="current-password"
              required
              placeholder="<?php esc_attr_e('Your password', 'whois-crm'); ?>"
              aria-required="true"
            >
            <button type="button" class="whoiscrm-password-toggle" aria-label="<?php esc_attr_e('Show password', 'whois-crm'); ?>" data-target="whoiscrm-login-password">
              <svg viewBox="0 0 24 24" class="icon-eye"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg viewBox="0 0 24 24" class="icon-eye-off" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Remember + Forgot -->
      <div class="whoiscrm-form-row-meta">
        <label class="whoiscrm-checkbox-label">
          <input type="checkbox" name="remember" value="1" id="whoiscrm-login-remember">
          <?php esc_html_e('Remember me', 'whois-crm'); ?>
        </label>
        <a href="<?php echo esc_url($forgot_url); ?>" class="whoiscrm-auth-link">
          <?php esc_html_e('Forgot password?', 'whois-crm'); ?>
        </a>
      </div>

      <!-- Submit -->
      <button type="submit" id="whoiscrm-login-submit" class="whoiscrm-btn-auth">
        <span class="spinner" aria-hidden="true"></span>
        <span class="btn-text"><?php esc_html_e('Sign In', 'whois-crm'); ?></span>
      </button>

    </form>

    <!-- Footer -->
    <div class="whoiscrm-auth-footer">
      <?php esc_html_e("Don't have an account?", 'whois-crm'); ?>
      <a href="<?php echo esc_url($register_url); ?>">
        <?php esc_html_e('Create one free', 'whois-crm'); ?>
      </a>
    </div>

  </div><!-- .whoiscrm-auth-card -->
</div><!-- .whoiscrm-auth-page -->

<script>
(function() {
  'use strict';

  const form      = document.getElementById('whoiscrm-login-form');
  const msgBox    = document.getElementById('whoiscrm-login-message');
  const submitBtn = document.getElementById('whoiscrm-login-submit');
  const ajaxUrl   = <?php echo wp_json_encode($ajax_url); ?>;

  // Password visibility toggle
  document.querySelectorAll('.whoiscrm-password-toggle').forEach(function(btn) {
    btn.addEventListener('click', function() {
      const targetId = btn.getAttribute('data-target');
      const input    = document.getElementById(targetId);
      if (!input) return;
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.querySelector('.icon-eye').style.display     = show ? 'none' : '';
      btn.querySelector('.icon-eye-off').style.display = show ? ''     : 'none';
      btn.setAttribute('aria-label', show ? '<?php echo esc_js(__('Hide password', 'whois-crm')); ?>' : '<?php echo esc_js(__('Show password', 'whois-crm')); ?>');
    });
  });

  // Form submission
  form.addEventListener('submit', function(e) {
    e.preventDefault();
    clearMessage();
    setLoading(true);

    fetch(ajaxUrl, { method: 'POST', body: new FormData(form), credentials: 'same-origin' })
      .then(function(r) { return r.json(); })
      .then(function(res) {
        if (res.success) {
          showMessage(res.data.message || '<?php echo esc_js(__('Redirecting…', 'whois-crm')); ?>', 'success');
          setTimeout(function() { window.location.href = res.data.redirect; }, 600);
        } else {
          setLoading(false);
          showMessage(res.data.message || '<?php echo esc_js(__('Login failed. Please try again.', 'whois-crm')); ?>', 'error');
        }
      })
      .catch(function() {
        setLoading(false);
        showMessage('<?php echo esc_js(__('A network error occurred. Please try again.', 'whois-crm')); ?>', 'error');
      });
  });

  function setLoading(on) { submitBtn.classList.toggle('is-loading', on); submitBtn.disabled = on; }
  function showMessage(t, tp) { msgBox.className = 'whoiscrm-auth-message is-' + tp; msgBox.textContent = t; }
  function clearMessage() { msgBox.className = 'whoiscrm-auth-message'; msgBox.textContent = ''; }
})();
</script>
