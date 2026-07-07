/**
 * WHOIS CRM — Customer Portal JavaScript
 *
 * Handles:
 *  - Tab URL navigation highlights
 *  - Profile update AJAX forms
 *  - Cancel subscription AJAX requests
 *  - API key generation and revocation AJAX requests
 *  - Coupon code validation (pricing / checkout)
 *  - Public pricing checkout redirect triggers
 */

(function () {
  'use strict';

  // Global localized object reference (provided via wp_localize_script)
  const ajaxUrl = typeof whoisCRMPortal !== 'undefined' ? whoisCRMPortal.ajaxUrl : '/wp-admin/admin-ajax.php';

  document.addEventListener('DOMContentLoaded', function () {
    initCheckoutRedirects();
    initCouponValidation();
    initProfileUpdate();
    initSubscriptionCancellation();
    initApiKeyActions();
  });

  // ─── Stripe Checkout Trigger ───────────────────────────────────────────
  function initCheckoutRedirects() {
    document.querySelectorAll('.js-subscribe-btn').forEach(btn => {
      btn.addEventListener('click', function (e) {
        e.preventDefault();

        const pricingId = btn.dataset.pricingId;
        const nonce = btn.dataset.nonce;
        const couponInput = document.getElementById('whoiscrm-coupon-code');
        const couponCode = couponInput ? couponInput.value.trim() : '';

        if (!pricingId) return;

        btn.disabled = true;
        const originalText = btn.textContent;
        btn.textContent = 'Redirecting to Stripe...';

        const data = new FormData();
        data.append('action', 'whoiscrm_create_checkout');
        data.append('nonce', nonce);
        data.append('pricing_id', pricingId);
        if (couponCode) {
          data.append('coupon_code', couponCode);
        }

        fetch(ajaxUrl, {
          method: 'POST',
          body: data,
          credentials: 'same-origin',
        })
        .then(response => response.json())
        .then(res => {
          if (res.success && res.data.checkout_url) {
            window.location.href = res.data.checkout_url;
          } else {
            alert(res.data.message || 'An error occurred during checkout initialization.');
            btn.disabled = false;
            btn.textContent = originalText;
          }
        })
        .catch(() => {
          alert('Network connection error. Please try again.');
          btn.disabled = false;
          btn.textContent = originalText;
        });
      });
    });
  }

  // ─── Coupon Validation ─────────────────────────────────────────────────
  function initCouponValidation() {
    const applyBtn = document.getElementById('whoiscrm-apply-coupon-btn');
    const couponInput = document.getElementById('whoiscrm-coupon-code');
    const couponMessage = document.getElementById('whoiscrm-coupon-status-msg');

    if (!applyBtn || !couponInput) return;

    applyBtn.addEventListener('click', function (e) {
      e.preventDefault();

      const code = couponInput.value.trim();
      const nonce = applyBtn.dataset.nonce;
      const subtotal = applyBtn.dataset.subtotal;
      const packageId = applyBtn.dataset.packageId;

      if (!code) {
        showStatus('Please enter a coupon code.', 'danger');
        return;
      }

      applyBtn.disabled = true;
      applyBtn.textContent = 'Applying...';

      const data = new FormData();
      data.append('action', 'whoiscrm_validate_coupon');
      data.append('nonce', nonce);
      data.append('code', code);
      data.append('subtotal', subtotal);
      data.append('package_id', packageId);

      fetch(ajaxUrl, {
        method: 'POST',
        body: data,
        credentials: 'same-origin',
      })
      .then(response => response.json())
      .then(res => {
        applyBtn.disabled = false;
        applyBtn.textContent = 'Apply';

        if (res.success) {
          showStatus(res.data.message, 'success');
          // Update total price display if present on page
          const totalVal = document.getElementById('whoiscrm-checkout-total-val');
          if (totalVal) {
            totalVal.textContent = '$' + parseFloat(res.data.final_total).toFixed(2);
          }
        } else {
          showStatus(res.data.message || 'Invalid coupon.', 'danger');
        }
      })
      .catch(() => {
        applyBtn.disabled = false;
        applyBtn.textContent = 'Apply';
        showStatus('Failed to validate coupon code due to network issues.', 'danger');
      });
    });

    function showStatus(msg, type) {
      couponMessage.textContent = msg;
      couponMessage.className = 'whoiscrm-form-hint';
      couponMessage.style.color = type === 'success' ? '#14803C' : '#C42B2B';
    }
  }

  // ─── Profile Form Submission ───────────────────────────────────────────
  function initProfileUpdate() {
    const form = document.getElementById('whoiscrm-profile-form');
    if (!form) return;

    form.addEventListener('submit', function (e) {
      e.preventDefault();

      const submitBtn = form.querySelector('button[type="submit"]');
      const statusMsg = document.getElementById('whoiscrm-profile-status-msg');
      const originalText = submitBtn.textContent;

      submitBtn.disabled = true;
      submitBtn.textContent = 'Saving Changes...';
      if (statusMsg) statusMsg.style.display = 'none';

      const data = new FormData(form);

      fetch(ajaxUrl, {
        method: 'POST',
        body: data,
        credentials: 'same-origin',
      })
      .then(response => response.json())
      .then(res => {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;

        if (statusMsg) {
          statusMsg.style.display = 'block';
          statusMsg.textContent = res.data.message;
          statusMsg.className = res.success ? 'whoiscrm-alert whoiscrm-alert--success' : 'whoiscrm-alert whoiscrm-alert--danger';
          
          if (res.success) {
            // Smoothly clear password fields on success
            form.querySelectorAll('input[type="password"]').forEach(input => input.value = '');
          }
        }
      })
      .catch(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
        if (statusMsg) {
          statusMsg.style.display = 'block';
          statusMsg.textContent = 'A connection error occurred. Please try again.';
          statusMsg.className = 'whoiscrm-alert whoiscrm-alert--danger';
        }
      });
    });
  }

  // ─── Cancel Subscription Confirmation ──────────────────────────────────
  function initSubscriptionCancellation() {
    document.querySelectorAll('.js-cancel-sub-btn').forEach(btn => {
      btn.addEventListener('click', function (e) {
        e.preventDefault();

        const message = 'Are you sure you want to cancel your subscription? You will retain access until the end of your current billing cycle, but it will not auto-renew.';
        if (!confirm(message)) return;

        btn.disabled = true;
        const originalText = btn.textContent;
        btn.textContent = 'Cancelling...';

        const data = new FormData();
        data.append('action', 'whoiscrm_cancel_subscription');
        data.append('nonce', btn.dataset.nonce);
        data.append('subscription_id', btn.dataset.subId);

        fetch(ajaxUrl, {
          method: 'POST',
          body: data,
          credentials: 'same-origin',
        })
        .then(response => response.json())
        .then(res => {
          if (res.success) {
            alert(res.data.message);
            location.reload();
          } else {
            alert(res.data.message || 'Could not process cancellation.');
            btn.disabled = false;
            btn.textContent = originalText;
          }
        })
        .catch(() => {
          alert('Network issue. Please try again.');
          btn.disabled = false;
          btn.textContent = originalText;
        });
      });
    });
  }

  // ─── API Key Generator and Revoker ─────────────────────────────────────
  function initApiKeyActions() {
    const generateBtn = document.getElementById('whoiscrm-generate-api-btn');
    const revokeBtn = document.getElementById('whoiscrm-revoke-api-btn');

    if (generateBtn) {
      generateBtn.addEventListener('click', function (e) {
        e.preventDefault();

        generateBtn.disabled = true;
        generateBtn.textContent = 'Generating...';

        const data = new FormData();
        data.append('action', 'whoiscrm_generate_api_key');
        data.append('nonce', generateBtn.dataset.nonce);

        fetch(ajaxUrl, {
          method: 'POST',
          body: data,
          credentials: 'same-origin',
        })
        .then(response => response.json())
        .then(res => {
          if (res.success) {
            alert('Your API Key has been generated. Write it down; it will not be displayed again:\n\nKey: ' + res.data.api_key);
            location.reload();
          } else {
            alert(res.data.message || 'Failed to generate API Key.');
            generateBtn.disabled = false;
            generateBtn.textContent = 'Generate API Key';
          }
        })
        .catch(() => {
          alert('Network error.');
          generateBtn.disabled = false;
          generateBtn.textContent = 'Generate API Key';
        });
      });
    }

    if (revokeBtn) {
      revokeBtn.addEventListener('click', function (e) {
        e.preventDefault();

        if (!confirm('Are you sure you want to revoke your API key? All applications using this key will immediately lose access.')) return;

        revokeBtn.disabled = true;
        revokeBtn.textContent = 'Revoking...';

        const data = new FormData();
        data.append('action', 'whoiscrm_revoke_api_key');
        data.append('nonce', revokeBtn.dataset.nonce);

        fetch(ajaxUrl, {
          method: 'POST',
          body: data,
          credentials: 'same-origin',
        })
        .then(response => response.json())
        .then(res => {
          if (res.success) {
            location.reload();
          } else {
            alert(res.data.message || 'Failed to revoke key.');
            revokeBtn.disabled = false;
            revokeBtn.textContent = 'Revoke API Key';
          }
        })
        .catch(() => {
          alert('Network error.');
          revokeBtn.disabled = false;
          revokeBtn.textContent = 'Revoke API Key';
        });
      });
    }
  }

})();
