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
        <p class="whoiscrm-auth-brand-tagline"><?php esc_html_e("No worries — it happens to the best of us. We'll get you back in quickly.", 'whois-crm'); ?></p>

        <ul class="whoiscrm-auth-trust">
          <li><?php esc_html_e('Reset link sent within seconds', 'whois-crm'); ?></li>
          <li><?php esc_html_e('Secure one-time link', 'whois-crm'); ?></li>
          <li><?php esc_html_e('Check your spam folder if needed', 'whois-crm'); ?></li>
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

      </div>
    </div><!-- .whoiscrm-auth-panel-form -->

  </div><!-- .whoiscrm-auth-split -->
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
