/**
 * WHOIS CRM — Admin JavaScript
 *
 * Handles:
 *  - Table search / filter interactions
 *  - Confirm dialogs for destructive actions
 *  - AJAX form submissions
 *  - Stat card count-up animation
 *  - Copy-to-clipboard
 *  - Toggle switches
 */
/* global whoisCRM, jQuery */

(function ($) {
  'use strict';

  const AJAX_URL = whoisCRM.ajaxUrl;
  const NONCE    = whoisCRM.nonce;
  const i18n     = whoisCRM.i18n;

  // ──────────────────────────────────────────────────────────────────────────
  // Confirm dangerous actions
  // ──────────────────────────────────────────────────────────────────────────
  $(document).on('click', '[data-confirm]', function (e) {
    const msg = $(this).data('confirm') || i18n.confirm_delete;
    if (!window.confirm(msg)) {
      e.preventDefault();
      return false;
    }
  });

  // ──────────────────────────────────────────────────────────────────────────
  // Copy to clipboard
  // ──────────────────────────────────────────────────────────────────────────
  $(document).on('click', '[data-copy]', function () {
    const text = $(this).data('copy') || $(this).closest('[data-copy-target]').find('[data-copy-value]').text();
    if (!text) return;

    navigator.clipboard.writeText(text).then(() => {
      const btn     = $(this);
      const original = btn.text();
      btn.text(i18n.copied);
      setTimeout(() => btn.text(original), 1500);
    });
  });

  // ──────────────────────────────────────────────────────────────────────────
  // Count-up animation for stat cards
  // ──────────────────────────────────────────────────────────────────────────
  function animateCountUp($el) {
    const raw      = $el.text().trim();
    const prefix   = raw.match(/^[^0-9]*/)[0] || '';
    const suffix   = raw.match(/[^0-9.]*$/)[0] || '';
    const numStr   = raw.replace(prefix, '').replace(suffix, '').replace(/,/g, '');
    const target   = parseFloat(numStr);

    if (isNaN(target) || target === 0) return;

    const isFloat  = numStr.includes('.');
    const duration = 800;
    const start    = performance.now();

    function step(now) {
      const elapsed  = now - start;
      const progress = Math.min(elapsed / duration, 1);
      // Ease-out cubic
      const eased    = 1 - Math.pow(1 - progress, 3);
      const current  = target * eased;
      const formatted = isFloat
        ? current.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
        : Math.floor(current).toLocaleString('en-US');

      $el.text(prefix + formatted + suffix);

      if (progress < 1) {
        requestAnimationFrame(step);
      } else {
        $el.text(raw); // Restore original exact text
      }
    }

    requestAnimationFrame(step);
  }

  // Trigger count-up when stat cards become visible.
  function initCountUp() {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          animateCountUp($(entry.target));
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.1 });

    document.querySelectorAll('.whoiscrm-stat-card__value[data-countup]').forEach((el) => {
      observer.observe(el);
    });
  }

  // ──────────────────────────────────────────────────────────────────────────
  // Table filter — debounced search
  // ──────────────────────────────────────────────────────────────────────────
  let searchTimer = null;

  $(document).on('input', '.whoiscrm-table-search[data-live-search]', function () {
    clearTimeout(searchTimer);
    const $input = $(this);
    const $form  = $input.closest('form');

    searchTimer = setTimeout(() => {
      if ($form.length) {
        $form.submit();
      }
    }, 500);
  });

  // Status filter dropdowns — auto-submit on change
  $(document).on('change', 'select[data-auto-submit]', function () {
    $(this).closest('form').submit();
  });

  // ──────────────────────────────────────────────────────────────────────────
  // AJAX toggle for customer block/unblock (quick action)
  // ──────────────────────────────────────────────────────────────────────────
  $(document).on('click', '.js-toggle-customer-status', function (e) {
    e.preventDefault();

    const $btn       = $(this);
    const customerId = $btn.data('customer-id');
    const action     = $btn.data('action');   // 'block' or 'unblock'

    $btn.prop('disabled', true).text(i18n.loading);

    $.post(AJAX_URL, {
      action: 'whoiscrm_customer_status',
      nonce: NONCE,
      customer_id: customerId,
      customer_action: action,
    }).done((res) => {
      if (res.success) {
        location.reload();
      } else {
        alert(res.data.message || i18n.error);
        $btn.prop('disabled', false);
      }
    }).fail(() => {
      alert(i18n.error);
      $btn.prop('disabled', false);
    });
  });

  // ──────────────────────────────────────────────────────────────────────────
  // Settings page — Stripe mode switcher (show live/test fields)
  // ──────────────────────────────────────────────────────────────────────────
  function initStripeMode() {
    const $select = $('#whoiscrm-stripe-mode');
    if (!$select.length) return;

    function updateVisibility() {
      const mode = $select.val();
      $('.whoiscrm-stripe-test-fields').toggle(mode === 'test');
      $('.whoiscrm-stripe-live-fields').toggle(mode === 'live');
    }

    $select.on('change', updateVisibility);
    updateVisibility();
  }

  // ──────────────────────────────────────────────────────────────────────────
  // Settings — preview email from address
  // ──────────────────────────────────────────────────────────────────────────
  $(document).on('input', '#whoiscrm-email-from-name, #whoiscrm-email-from-address', function () {
    const name  = $('#whoiscrm-email-from-name').val();
    const email = $('#whoiscrm-email-from-address').val();
    const $prev = $('#whoiscrm-from-preview');
    if ($prev.length) {
      $prev.text(name && email ? `"${name}" <${email}>` : (email || name));
    }
  });

  // ──────────────────────────────────────────────────────────────────────────
  // AJAX coupon status toggle
  // ──────────────────────────────────────────────────────────────────────────
  $(document).on('click', '.js-toggle-coupon', function (e) {
    e.preventDefault();
    const $btn     = $(this);
    const couponId = $btn.data('coupon-id');

    $.post(AJAX_URL, {
      action:        'whoiscrm_admin_ajax',
      sub_action:    'toggle_coupon',
      nonce:         NONCE,
      coupon_id:     couponId,
    }).done((res) => {
      if (res.success) {
        location.reload();
      }
    });
  });

  // ──────────────────────────────────────────────────────────────────────────
  // Package form — auto-generate slug from name
  // ──────────────────────────────────────────────────────────────────────────
  $(document).on('input', '#whoiscrm-package-name', function () {
    const $slug = $('#whoiscrm-package-slug');
    if ($slug.data('user-edited')) return;
    $slug.val(
      $(this).val().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')
    );
  });

  $('#whoiscrm-package-slug').on('input', function () {
    $(this).data('user-edited', true);
  });

  // ──────────────────────────────────────────────────────────────────────────
  // Package form — show/hide country multi-select based on type
  // ──────────────────────────────────────────────────────────────────────────
  function initPackageTypeToggle() {
    const $type = $('#whoiscrm-package-type');
    if (!$type.length) return;

    function update() {
      const isCountry = $type.val() === 'country_specific';
      $('.whoiscrm-country-field').toggle(isCountry);
      $('.whoiscrm-annual-pricing-field').toggle(!isCountry);
    }

    $type.on('change', update);
    update();
  }

  // ──────────────────────────────────────────────────────────────────────────
  // Dismiss inline notices
  // ──────────────────────────────────────────────────────────────────────────
  $(document).on('click', '.whoiscrm-alert__dismiss', function () {
    $(this).closest('.whoiscrm-alert').fadeOut(250);
  });

  // ──────────────────────────────────────────────────────────────────────────
  // Simple revenue sparkline (if canvas present)
  // ──────────────────────────────────────────────────────────────────────────
  function initSparkline() {
    const canvas = document.getElementById('whoiscrm-revenue-sparkline');
    if (!canvas || !canvas.getContext) return;

    const rawData = canvas.dataset.values;
    if (!rawData) return;

    let data;
    try { data = JSON.parse(rawData); } catch (e) { return; }

    const amounts = data.map((d) => d.amount);
    const max     = Math.max(...amounts, 1);
    const ctx     = canvas.getContext('2d');
    const w       = canvas.width;
    const h       = canvas.height;
    const pad     = 4;

    ctx.clearRect(0, 0, w, h);

    // Fill area
    ctx.beginPath();
    amounts.forEach((v, i) => {
      const x = pad + (i / (amounts.length - 1)) * (w - pad * 2);
      const y = h - pad - (v / max) * (h - pad * 2);
      i === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
    });
    ctx.lineTo(w - pad, h - pad);
    ctx.lineTo(pad, h - pad);
    ctx.closePath();
    ctx.fillStyle = 'rgba(255, 102, 33, 0.12)';
    ctx.fill();

    // Line
    ctx.beginPath();
    amounts.forEach((v, i) => {
      const x = pad + (i / (amounts.length - 1)) * (w - pad * 2);
      const y = h - pad - (v / max) * (h - pad * 2);
      i === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
    });
    ctx.strokeStyle = '#FF6621';
    ctx.lineWidth   = 2;
    ctx.lineJoin    = 'round';
    ctx.stroke();
  }

  // ──────────────────────────────────────────────────────────────────────────
  // Bootstrap
  // ──────────────────────────────────────────────────────────────────────────
  $(document).ready(function () {
    initCountUp();
    initStripeMode();
    initPackageTypeToggle();
    initSparkline();
  });

})(jQuery);
