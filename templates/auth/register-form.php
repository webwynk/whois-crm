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
}
<!-- DM Sans Font Preload -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&display=swap" rel="stylesheet">

<div class="whoiscrm-auth-page">
  <div class="whoiscrm-auth-split">

    <!-- ── Left: Brand Panel ─────────────────────────────── -->
    <div class="whoiscrm-auth-panel-brand">
      <div class="brand-grid"></div>
      <div class="whoiscrm-auth-brand-inner">

        <div class="whoiscrm-auth-logo-mark" aria-hidden="true">
          <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <circle cx="12" cy="12" r="10" stroke="white" stroke-width="1.5" fill="none"/>
            <ellipse cx="12" cy="12" rx="4" ry="10" stroke="white" stroke-width="1.5" fill="none"/>
            <path d="M2 12h20" stroke="white" stroke-width="1.5"/>
          </svg>
        </div>

        <h2 class="whoiscrm-auth-brand-name"><?php echo esc_html(get_bloginfo('name')); ?></h2>
        <p class="whoiscrm-auth-brand-tagline"><?php esc_html_e('Join thousands of domain professionals who trust us for accurate WHOIS intelligence.', 'whois-crm'); ?></p>

        <ul class="whoiscrm-auth-trust">
          <li><?php esc_html_e('Free account — no credit card needed', 'whois-crm'); ?></li>
          <li><?php esc_html_e('Browse available data packages', 'whois-crm'); ?></li>
          <li><?php esc_html_e('Instant access after signup', 'whois-crm'); ?></li>
        </ul>
      </div>
    </div>

    <!-- ── Right: Form Panel ──────────────────────────────── -->
    <div class="whoiscrm-auth-panel-form">
      <div class="whoiscrm-auth-form-inner">

        <!-- Mobile logo -->
        <div class="whoiscrm-auth-mobile-logo">
          <div class="whoiscrm-auth-mobile-logo-mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <circle cx="12" cy="12" r="10" stroke="white" stroke-width="1.5" fill="none"/>
              <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" stroke="white" stroke-width="1.5" fill="none"/>
              <path d="M2 12h20" stroke="white" stroke-width="1.5"/>
            </svg>
          </div>
          <span class="whoiscrm-auth-mobile-site-name"><?php echo esc_html(get_bloginfo('name')); ?></span>
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
              <div class="whoiscrm-input-icon-wrapper">
                <span class="whoiscrm-input-icon" aria-hidden="true">
                  <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                  </svg>
                </span>
                <input
                  type="text"
                  id="whoiscrm-reg-first-name"
                  name="first_name"
                  autocomplete="given-name"
                  required
                  placeholder="<?php esc_attr_e('John', 'whois-crm'); ?>"
                >
              </div>
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
              <?php esc_html_e('Company', 'whois-crm'); ?>
              <span class="optional"><?php esc_html_e('(optional)', 'whois-crm'); ?></span>
            </label>
            <div class="whoiscrm-input-icon-wrapper">
              <span class="whoiscrm-input-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                  <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
              </span>
              <input
                type="text"
                id="whoiscrm-reg-company"
                name="company_name"
                autocomplete="organization"
                placeholder="<?php esc_attr_e('Acme Corp', 'whois-crm'); ?>"
              >
            </div>
          </div>

          <!-- Email -->
          <div class="whoiscrm-form-group">
            <label for="whoiscrm-reg-email">
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
                id="whoiscrm-reg-email"
                name="email"
                autocomplete="email"
                required
                placeholder="<?php esc_attr_e('you@example.com', 'whois-crm'); ?>"
              >
            </div>
          </div>

          <!-- Password -->
          <div class="whoiscrm-form-group">
            <label for="whoiscrm-reg-password">
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
                  id="whoiscrm-reg-password"
                  name="password"
                  autocomplete="new-password"
                  required
                  placeholder="<?php esc_attr_e('Min. 8 characters', 'whois-crm'); ?>"
                >
                <button type="button" class="whoiscrm-password-toggle" aria-label="<?php esc_attr_e('Show password', 'whois-crm'); ?>" data-target="whoiscrm-reg-password">
                  <svg viewBox="0 0 24 24" class="icon-eye"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  <svg viewBox="0 0 24 24" class="icon-eye-off" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                </button>
              </div>
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

      </div>
    </div><!-- .whoiscrm-auth-panel-form -->

  </div><!-- .whoiscrm-auth-split -->
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
      btn.querySelector('.icon-eye').style.display     = show ? 'none' : '';
      btn.querySelector('.icon-eye-off').style.display = show ? ''     : 'none';
    });
  });

  // ── Password strength ───────────────────────────────────────────
  const strengthLabels = ['', '<?php echo esc_js(__('Weak', 'whois-crm')); ?>', '<?php echo esc_js(__('Fair', 'whois-crm')); ?>', '<?php echo esc_js(__('Good', 'whois-crm')); ?>', '<?php echo esc_js(__('Strong', 'whois-crm')); ?>'];

  pwInput.addEventListener('input', function() {
    const v = pwInput.value;
    let score = 0;
    if (v.length >= 8)           score++;
    if (/[A-Z]/.test(v))         score++;
    if (/[0-9]/.test(v))         score++;
    if (/[^A-Za-z0-9]/.test(v))  score++;
    pwBar.className   = 'whoiscrm-password-strength__bar' + (score ? ' strength-' + score : '');
    pwLabel.textContent = score ? strengthLabels[score] : '';
  });

  // ── Form submission ─────────────────────────────────────────────
  form.addEventListener('submit', function(e) {
    e.preventDefault();
    clearMessage();

    if (pwInput.value.length < 8) {
      showMessage('<?php echo esc_js(__('Password must be at least 8 characters.', 'whois-crm')); ?>', 'error');
      pwInput.focus();
      return;
    }

    setLoading(true);

    fetch(ajaxUrl, { method: 'POST', body: new FormData(form), credentials: 'same-origin' })
      .then(function(r) { return r.json(); })
      .then(function(res) {
        if (res.success) {
          showMessage(res.data.message || '<?php echo esc_js(__('Account created!', 'whois-crm')); ?>', 'success');
          setTimeout(function() { window.location.href = res.data.redirect; }, 800);
        } else {
          setLoading(false);
          showMessage(res.data.message || '<?php echo esc_js(__('Registration failed. Please try again.', 'whois-crm')); ?>', 'error');
        }
      })
      .catch(function() {
        setLoading(false);
        showMessage('<?php echo esc_js(__('A network error occurred.', 'whois-crm')); ?>', 'error');
      });
  });

  function setLoading(on)      { submitBtn.classList.toggle('is-loading', on); submitBtn.disabled = on; }
  function showMessage(t, tp)  { msgBox.className = 'whoiscrm-auth-message is-' + tp; msgBox.textContent = t; }
  function clearMessage()      { msgBox.className = 'whoiscrm-auth-message'; msgBox.textContent = ''; }
})();
</script>
