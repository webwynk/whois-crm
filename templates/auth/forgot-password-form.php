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
      <h1><?php esc_html_e('Forgot your password?', 'whois-crm'); ?></h1>
      <p><?php esc_html_e("Enter your email and we'll send you a reset link.", 'whois-crm'); ?></p>
    </div>

    <div class="whoiscrm-auth-message" id="whoiscrm-forgot-message" role="alert" aria-live="polite"></div>

    <form id="whoiscrm-forgot-form" class="whoiscrm-auth-form" method="post" novalidate>

      <input type="hidden" name="action" value="whoiscrm_forgot_password">
      <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">

      <div class="whoiscrm-form-group">
        <label for="whoiscrm-forgot-email">
          <?php esc_html_e('Email Address', 'whois-crm'); ?>
          <span class="required" aria-hidden="true">*</span>
        </label>
        <input
          type="email"
          id="whoiscrm-forgot-email"
          name="email"
          autocomplete="email"
          required
          placeholder="<?php esc_attr_e('you@example.com', 'whois-crm'); ?>"
        >
      </div>

      <button type="submit" id="whoiscrm-forgot-submit" class="whoiscrm-btn-auth">
        <span class="spinner" aria-hidden="true"></span>
        <span class="btn-text"><?php esc_html_e('Send Reset Link', 'whois-crm'); ?></span>
      </button>

    </form>

    <div class="whoiscrm-auth-footer">
      <a href="<?php echo esc_url($login_url); ?>">
        ← <?php esc_html_e('Back to sign in', 'whois-crm'); ?>
      </a>
    </div>

  </div>
</div>

<script>
(function() {
  const form      = document.getElementById('whoiscrm-forgot-form');
  const msgBox    = document.getElementById('whoiscrm-forgot-message');
  const submitBtn = document.getElementById('whoiscrm-forgot-submit');
  const ajaxUrl   = <?php echo wp_json_encode($ajax_url); ?>;

  form.addEventListener('submit', function(e) {
    e.preventDefault();
    msgBox.className = 'whoiscrm-auth-message';
    submitBtn.classList.add('is-loading');
    submitBtn.disabled = true;

    fetch(ajaxUrl, { method: 'POST', body: new FormData(form), credentials: 'same-origin' })
      .then(function(r) { return r.json(); })
      .then(function(res) {
        submitBtn.classList.remove('is-loading');
        submitBtn.disabled = false;
        const type = res.success ? 'success' : 'error';
        const msg  = (res.data && res.data.message) || '<?php esc_js(_e('An error occurred.', 'whois-crm')); ?>';
        msgBox.className = 'whoiscrm-auth-message is-' + type;
        msgBox.textContent = msg;
        if (res.success) { form.reset(); }
      })
      .catch(function() {
        submitBtn.classList.remove('is-loading');
        submitBtn.disabled = false;
        msgBox.className = 'whoiscrm-auth-message is-error';
        msgBox.textContent = '<?php esc_js(_e('Network error. Please try again.', 'whois-crm')); ?>';
      });
  });
})();
</script>
