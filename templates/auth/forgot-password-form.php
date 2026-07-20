<?php
/**
 * Template: Forgot Password Form
 *
 * Variables:
 *   $nonce     — wp nonce value
 *   $login_url — URL to login page
 *   $ajax_url  — admin-ajax.php URL
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

    <!-- Back link -->
    <a href="<?php echo esc_url($login_url); ?>" class="whoiscrm-auth-back">
      <svg viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="15 18 9 12 15 6"/>
      </svg>
      <?php esc_html_e('Back to sign in', 'whois-crm'); ?>
    </a>

    <!-- Heading -->
    <div class="whoiscrm-auth-heading">
      <h1><?php esc_html_e('Forgot password?', 'whois-crm'); ?></h1>
      <p><?php esc_html_e("Enter your email and we'll send you a reset link.", 'whois-crm'); ?></p>
    </div>

    <!-- Message box -->
    <div class="whoiscrm-auth-message" id="whoiscrm-forgot-message" role="alert" aria-live="polite"></div>

    <!-- Forgot Form -->
    <form id="whoiscrm-forgot-form" class="whoiscrm-auth-form" method="post" novalidate>

      <input type="hidden" name="action" value="whoiscrm_forgot_password">
      <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">

      <div class="whoiscrm-form-group">
        <label for="whoiscrm-forgot-email">
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
            id="whoiscrm-forgot-email"
            name="email"
            autocomplete="email"
            required
            placeholder="<?php esc_attr_e('you@example.com', 'whois-crm'); ?>"
          >
        </div>
      </div>

      <button type="submit" id="whoiscrm-forgot-submit" class="whoiscrm-btn-auth">
        <span class="spinner" aria-hidden="true"></span>
        <span class="btn-text"><?php esc_html_e('Send Reset Link', 'whois-crm'); ?></span>
      </button>

    </form>

    <!-- Footer -->
    <div class="whoiscrm-auth-footer">
      <?php esc_html_e("Remember your password?", 'whois-crm'); ?>
      <a href="<?php echo esc_url($login_url); ?>">
        <?php esc_html_e('Sign in', 'whois-crm'); ?>
      </a>
    </div>

  </div><!-- .whoiscrm-auth-card -->
</div><!-- .whoiscrm-auth-page -->

<script>
(function() {
  const form      = document.getElementById('whoiscrm-forgot-form');
  const msgBox    = document.getElementById('whoiscrm-forgot-message');
  const submitBtn = document.getElementById('whoiscrm-forgot-submit');
  const ajaxUrl   = <?php echo wp_json_encode($ajax_url); ?>;

  form.addEventListener('submit', function(e) {
    e.preventDefault();
    msgBox.className = 'whoiscrm-auth-message';
    msgBox.textContent = '';
    submitBtn.classList.add('is-loading');
    submitBtn.disabled = true;

    fetch(ajaxUrl, { method: 'POST', body: new FormData(form), credentials: 'same-origin' })
      .then(function(r) { return r.json(); })
      .then(function(res) {
        submitBtn.classList.remove('is-loading');
        submitBtn.disabled = false;
        const type = res.success ? 'success' : 'error';
        const msg  = (res.data && res.data.message) || '<?php echo esc_js(__('An error occurred.', 'whois-crm')); ?>';
        msgBox.className   = 'whoiscrm-auth-message is-' + type;
        msgBox.textContent = msg;
        if (res.success) { form.reset(); }
      })
      .catch(function() {
        submitBtn.classList.remove('is-loading');
        submitBtn.disabled = false;
        msgBox.className   = 'whoiscrm-auth-message is-error';
        msgBox.textContent = '<?php echo esc_js(__('Network error. Please try again.', 'whois-crm')); ?>';
      });
  });
})();
</script>
