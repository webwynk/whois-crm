<?php
/**
 * Template: Register Form
 *
 * Variables available:
 *   $nonce        — wp nonce value
 *   $login_url    — URL to login page
 *   $ajax_url     — WordPress admin-ajax.php URL
 */
declare(strict_types=1);

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
      <h1><?php esc_html_e('Create your account', 'whois-crm'); ?></h1>
      <p><?php esc_html_e('Get started with WHOIS data access today.', 'whois-crm'); ?></p>
    </div>

    <!-- Message box -->
    <div class="whoiscrm-auth-message" id="whoiscrm-register-message" role="alert" aria-live="polite"></div>

    <!-- Register Form -->
    <form id="whoiscrm-register-form" class="whoiscrm-auth-form" method="post" novalidate>

      <input type="hidden" name="action" value="whoiscrm_register">
      <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">

      <!-- First + Last Name -->
      <div class="form-row-2">
        <div class="whoiscrm-form-group">
          <label for="whoiscrm-reg-first-name">
            <?php esc_html_e('First Name', 'whois-crm'); ?>
            <span class="required" aria-hidden="true">*</span>
          </label>
          <input
            type="text"
            id="whoiscrm-reg-first-name"
            name="first_name"
            autocomplete="given-name"
            required
            placeholder="<?php esc_attr_e('John', 'whois-crm'); ?>"
          >
        </div>
        <div class="whoiscrm-form-group">
          <label for="whoiscrm-reg-last-name">
            <?php esc_html_e('Last Name', 'whois-crm'); ?>
          </label>
          <input
            type="text"
            id="whoiscrm-reg-last-name"
            name="last_name"
            autocomplete="family-name"
            placeholder="<?php esc_attr_e('Doe', 'whois-crm'); ?>"
          >
        </div>
      </div>

      <!-- Company (optional) -->
      <div class="whoiscrm-form-group">
        <label for="whoiscrm-reg-company">
          <?php esc_html_e('Company Name', 'whois-crm'); ?>
          <span style="color: var(--color-text-muted); font-weight: 400;"><?php esc_html_e('(optional)', 'whois-crm'); ?></span>
        </label>
        <input
          type="text"
          id="whoiscrm-reg-company"
          name="company_name"
          autocomplete="organization"
          placeholder="<?php esc_attr_e('Acme Corp', 'whois-crm'); ?>"
        >
      </div>

      <!-- Email -->
      <div class="whoiscrm-form-group">
        <label for="whoiscrm-reg-email">
          <?php esc_html_e('Email Address', 'whois-crm'); ?>
          <span class="required" aria-hidden="true">*</span>
        </label>
        <input
          type="email"
          id="whoiscrm-reg-email"
          name="email"
          autocomplete="email"
          required
          placeholder="<?php esc_attr_e('you@example.com', 'whois-crm'); ?>"
        >
      </div>

      <!-- Password -->
      <div class="whoiscrm-form-group">
        <label for="whoiscrm-reg-password">
          <?php esc_html_e('Password', 'whois-crm'); ?>
          <span class="required" aria-hidden="true">*</span>
        </label>
        <div class="whoiscrm-password-wrapper">
          <input
            type="password"
            id="whoiscrm-reg-password"
            name="password"
            autocomplete="new-password"
            required
            placeholder="<?php esc_attr_e('Min. 8 characters', 'whois-crm'); ?>"
          >
          <button type="button" class="whoiscrm-password-toggle" aria-label="<?php esc_attr_e('Show password', 'whois-crm'); ?>" data-target="whoiscrm-reg-password">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
        <!-- Password strength indicator -->
        <div class="whoiscrm-password-strength" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-label="<?php esc_attr_e('Password strength', 'whois-crm'); ?>">
          <div class="whoiscrm-password-strength__bar" id="whoiscrm-pw-strength-bar"></div>
        </div>
        <div class="whoiscrm-password-strength__label" id="whoiscrm-pw-strength-label"></div>
      </div>

      <!-- Submit -->
      <button type="submit" id="whoiscrm-register-submit" class="whoiscrm-btn-auth">
        <span class="spinner" aria-hidden="true"></span>
        <span class="btn-text"><?php esc_html_e('Create Account', 'whois-crm'); ?></span>
      </button>

    </form>

    <!-- Footer -->
    <div class="whoiscrm-auth-footer">
      <?php esc_html_e('Already have an account?', 'whois-crm'); ?>
      <a href="<?php echo esc_url($login_url); ?>">
        <?php esc_html_e('Sign in', 'whois-crm'); ?>
      </a>
    </div>

  </div><!-- .whoiscrm-auth-card -->
</div><!-- .whoiscrm-auth-page -->

<script>
(function() {
  'use strict';

  const form      = document.getElementById('whoiscrm-register-form');
  const msgBox    = document.getElementById('whoiscrm-register-message');
  const submitBtn = document.getElementById('whoiscrm-register-submit');
  const pwInput   = document.getElementById('whoiscrm-reg-password');
  const pwBar     = document.getElementById('whoiscrm-pw-strength-bar');
  const pwLabel   = document.getElementById('whoiscrm-pw-strength-label');
  const ajaxUrl   = <?php echo wp_json_encode($ajax_url); ?>;

  // ── Password visibility toggles ─────────────────────────────────
  document.querySelectorAll('.whoiscrm-password-toggle').forEach(function(btn) {
    btn.addEventListener('click', function() {
      const input = document.getElementById(btn.getAttribute('data-target'));
      if (!input) return;
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
    });
  });

  // ── Password strength ───────────────────────────────────────────
  const strengthLabels = ['', '<?php esc_js(_e('Weak', 'whois-crm')); ?>', '<?php esc_js(_e('Fair', 'whois-crm')); ?>', '<?php esc_js(_e('Good', 'whois-crm')); ?>', '<?php esc_js(_e('Strong', 'whois-crm')); ?>'];

  pwInput.addEventListener('input', function() {
    const v   = pwInput.value;
    let score = 0;
    if (v.length >= 8)  score++;
    if (/[A-Z]/.test(v)) score++;
    if (/[0-9]/.test(v)) score++;
    if (/[^A-Za-z0-9]/.test(v)) score++;

    pwBar.className = 'whoiscrm-password-strength__bar' + (score ? ' strength-' + score : '');
    pwLabel.textContent = score ? strengthLabels[score] : '';
  });

  // ── Form submission ─────────────────────────────────────────────
  form.addEventListener('submit', function(e) {
    e.preventDefault();
    clearMessage();

    if (pwInput.value.length < 8) {
      showMessage('<?php esc_js(_e('Password must be at least 8 characters.', 'whois-crm')); ?>', 'error');
      pwInput.focus();
      return;
    }

    setLoading(true);

    fetch(ajaxUrl, { method: 'POST', body: new FormData(form), credentials: 'same-origin' })
      .then(function(r) { return r.json(); })
      .then(function(res) {
        if (res.success) {
          showMessage(res.data.message || '<?php esc_js(_e('Account created!', 'whois-crm')); ?>', 'success');
          setTimeout(function() { window.location.href = res.data.redirect; }, 800);
        } else {
          setLoading(false);
          showMessage(res.data.message || '<?php esc_js(_e('Registration failed. Please try again.', 'whois-crm')); ?>', 'error');
        }
      })
      .catch(function() {
        setLoading(false);
        showMessage('<?php esc_js(_e('A network error occurred.', 'whois-crm')); ?>', 'error');
      });
  });

  function setLoading(on) { submitBtn.classList.toggle('is-loading', on); submitBtn.disabled = on; }
  function showMessage(text, type) { msgBox.className = 'whoiscrm-auth-message is-' + type; msgBox.textContent = text; }
  function clearMessage() { msgBox.className = 'whoiscrm-auth-message'; msgBox.textContent = ''; }
})();
</script>
