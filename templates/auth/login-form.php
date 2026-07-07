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

// Prevent direct access.
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="whoiscrm-auth-page">
  <div class="whoiscrm-auth-card">

    <!-- Brand -->
    <div class="whoiscrm-auth-brand">
      <div class="whoiscrm-auth-logo" aria-hidden="true">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"/>
        </svg>
      </div>
      <span class="whoiscrm-auth-site-name"><?php echo esc_html(get_bloginfo('name')); ?></span>
    </div>

    <!-- Heading -->
    <div class="whoiscrm-auth-heading">
      <h1><?php esc_html_e('Welcome back', 'whois-crm'); ?></h1>
      <p><?php esc_html_e('Sign in to access your data portal.', 'whois-crm'); ?></p>
    </div>

    <!-- Message box (hidden, shown by JS) -->
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

      <!-- Password -->
      <div class="whoiscrm-form-group">
        <label for="whoiscrm-login-password">
          <?php esc_html_e('Password', 'whois-crm'); ?>
          <span class="required" aria-hidden="true">*</span>
        </label>
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
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
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

  const form       = document.getElementById('whoiscrm-login-form');
  const msgBox     = document.getElementById('whoiscrm-login-message');
  const submitBtn  = document.getElementById('whoiscrm-login-submit');
  const ajaxUrl    = <?php echo wp_json_encode($ajax_url); ?>;

  // ── Password visibility toggle ──────────────────────────────────
  document.querySelectorAll('.whoiscrm-password-toggle').forEach(function(btn) {
    btn.addEventListener('click', function() {
      const targetId = btn.getAttribute('data-target');
      const input    = document.getElementById(targetId);
      if (!input) return;
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.setAttribute('aria-label', show ? '<?php esc_js(_e('Hide password', 'whois-crm')); ?>' : '<?php esc_js(_e('Show password', 'whois-crm')); ?>');
    });
  });

  // ── Form submission ─────────────────────────────────────────────
  form.addEventListener('submit', function(e) {
    e.preventDefault();
    clearMessage();
    setLoading(true);

    const data = new FormData(form);

    fetch(ajaxUrl, { method: 'POST', body: data, credentials: 'same-origin' })
      .then(function(r) { return r.json(); })
      .then(function(res) {
        if (res.success) {
          showMessage(res.data.message || '<?php esc_js(_e('Redirecting…', 'whois-crm')); ?>', 'success');
          setTimeout(function() { window.location.href = res.data.redirect; }, 600);
        } else {
          setLoading(false);
          showMessage(res.data.message || '<?php esc_js(_e('Login failed. Please try again.', 'whois-crm')); ?>', 'error');
        }
      })
      .catch(function() {
        setLoading(false);
        showMessage('<?php esc_js(_e('A network error occurred. Please try again.', 'whois-crm')); ?>', 'error');
      });
  });

  function setLoading(loading) {
    submitBtn.classList.toggle('is-loading', loading);
    submitBtn.disabled = loading;
  }

  function showMessage(text, type) {
    msgBox.className = 'whoiscrm-auth-message is-' + type;
    msgBox.textContent = text;
  }

  function clearMessage() {
    msgBox.className = 'whoiscrm-auth-message';
    msgBox.textContent = '';
  }
})();
</script>
