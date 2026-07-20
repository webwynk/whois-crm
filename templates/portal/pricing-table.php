<?php
/**
 * Template: Public Pricing Table & Packages Selector (Modern SaaS Redesign)
 *
 * Variables:
 *  $packages  array   Array of active Package objects (seeded or custom)
 *  $nonce     string  Stripe checkout nonce
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

$pricing_model = new \WhoisCRM\Database\Models\PackagePricing();

// Segregate plans into Global and Country-specific categories
$global_plans = [];
$country_plans = [];

foreach ($packages as $pkg) {
    $pkg_id = isset($pkg->id) ? (int) $pkg->id : 0;
    $pkg_type = $pkg->type ?? 'global_service';

    $pkg->monthly_price = null;
    $pkg->annual_price = null;

    if ($pkg_id > 0) {
        $pricings = $pricing_model->get_for_package($pkg_id);
        foreach ($pricings as $p) {
            if ($p->billing_cycle === 'monthly') {
                $pkg->monthly_price = $p;
            } elseif ($p->billing_cycle === 'annually') {
                $pkg->annual_price = $p;
            }
        }
    } elseif (isset($pkg->pricing)) {
        foreach ($pkg->pricing as $p) {
            if (is_object($p)) {
                if (($p->billing_cycle ?? '') === 'monthly') $pkg->monthly_price = $p;
                if (($p->billing_cycle ?? '') === 'annually') $pkg->annual_price = $p;
            } elseif (is_array($p)) {
                if (($p['billing_cycle'] ?? '') === 'monthly') $pkg->monthly_price = (object)$p;
                if (($p['billing_cycle'] ?? '') === 'annually') $pkg->annual_price = (object)$p;
            }
        }
    }

    if ($pkg_type === 'global_service') {
        $global_plans[] = $pkg;
    } else {
        $country_plans[] = $pkg;
    }
}

// Split global plans into core (top 3) and secondary (lower 2 centered)
$core_plans = array_slice($global_plans, 0, 3);
$secondary_plans = array_slice($global_plans, 3);

// Category Icons mapping
$category_icons = [
    'whois-history'    => '<svg viewBox="0 0 24 24"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>',
    'lead-generation'  => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>',
    'expiring-domains' => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
    'bulk-lookup'      => '<svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>',
    'enterprise'       => '<svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>',
];

// Country Flags mapping
$country_flags = [
    'country-us' => '🇺🇸',
    'country-in' => '🇮🇳',
    'country-uk' => '🇬🇧',
    'country-ca' => '🇨🇦',
    'country-ae' => '🇦🇪',
    'country-au' => '🇦🇺',
    'country-de' => '🇩🇪',
    'country-fr' => '🇫🇷',
    'country-br' => '🇧🇷',
];
?>

<div class="whoiscrm-pricing-wrapper">
  <div class="whoiscrm-portal-container">
    
    <div class="whoiscrm-pricing-header">
      <div class="whoiscrm-pricing-hero-badge">
        <span>⚡ <?php esc_html_e('WHOIS Data Feeds & Programmatic APIs', 'whois-crm'); ?></span>
      </div>
      <h2 class="whoiscrm-pricing-title"><?php esc_html_e('Simple, Transparent Pricing', 'whois-crm'); ?></h2>
      <p class="whoiscrm-pricing-subtitle">
        <?php esc_html_e('Choose the data feed coverage that meets your cybersecurity, sales prospecting, or research needs.', 'whois-crm'); ?>
      </p>
    </div>

    <!-- Billing Cycle Toggle Switch -->
    <div class="whoiscrm-pricing-toggle-wrap">
      <span class="whoiscrm-pricing-toggle-label js-toggle-label is-active" data-cycle="monthly"><?php esc_html_e('Bill Monthly', 'whois-crm'); ?></span>
      <label class="whoiscrm-toggle">
        <input type="checkbox" id="whoiscrm-pricing-cycle-checkbox">
        <span class="whoiscrm-toggle__slider"></span>
      </label>
      <span class="whoiscrm-pricing-toggle-label js-toggle-label" data-cycle="annually">
        <?php esc_html_e('Bill Annually', 'whois-crm'); ?>
        <span style="background: rgba(255,102,33,0.12); color: #FF6621; padding: 3px 8px; font-size: 0.75rem; border-radius: 6px; font-weight: 700; margin-left: 6px;">
          <?php esc_html_e('Save ~15%', 'whois-crm'); ?>
        </span>
      </span>
    </div>

    <!-- Core Global Service Plans Grid (Top 3 Cards) -->
    <div class="whoiscrm-pricing-grid-3">
      <?php foreach ($core_plans as $pkg) :
        $slug = $pkg->slug ?? '';
        $is_featured = ($slug === 'lead-generation');
        $features = !empty($pkg->features) ? (is_array($pkg->features) ? $pkg->features : json_decode((string)$pkg->features, true)) : [];
        $m_price = isset($pkg->monthly_price->price) ? number_format((float)$pkg->monthly_price->price, 0) : '0';
        $a_price = isset($pkg->annual_price->price) ? number_format((float)$pkg->annual_price->price, 0) : '0';
        $pricing_id_m = isset($pkg->monthly_price->id) ? (int)$pkg->monthly_price->id : 0;
        $pricing_id_a = isset($pkg->annual_price->id) ? (int)$pkg->annual_price->id : 0;
        $icon_html = $category_icons[$slug] ?? $category_icons['whois-history'];
        ?>
        <div class="whoiscrm-pricing-card <?php echo $is_featured ? 'whoiscrm-pricing-card--featured' : ''; ?>">
          <div>
            <div class="whoiscrm-pricing-icon-badge" aria-hidden="true">
              <?php echo $icon_html; ?>
            </div>
            <div class="whoiscrm-pricing-card-header">
              <h3 class="whoiscrm-pricing-card-name"><?php echo esc_html($pkg->name ?? ''); ?></h3>
              <p class="whoiscrm-pricing-card-desc"><?php echo esc_html($pkg->description ?? ''); ?></p>
            </div>

            <!-- Price Display -->
            <div class="whoiscrm-pricing-card-price-wrap">
              <span class="whoiscrm-pricing-card-price js-price-amount" 
                    data-monthly="<?php echo esc_attr($m_price); ?>"
                    data-annual="<?php echo esc_attr($a_price); ?>">
                $<?php echo esc_html($m_price); ?>
              </span>
              <span class="whoiscrm-pricing-card-cycle js-price-cycle">/month</span>
            </div>

            <!-- Feature Checklist -->
            <?php if (!empty($features) && is_array($features)) : ?>
              <ul class="whoiscrm-pricing-card-features">
                <?php foreach ($features as $f) : ?>
                  <li>
                    <span class="whoiscrm-pricing-feature-check">
                      <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                    </span>
                    <span><?php echo esc_html($f); ?></span>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>

          <!-- Checkout Buttons -->
          <div style="margin-top: 24px;">
            <button type="button" class="whoiscrm-btn whoiscrm-btn--primary whoiscrm-btn--lg js-subscribe-btn js-btn-monthly" 
                    data-pricing-id="<?php echo $pricing_id_m; ?>" 
                    data-nonce="<?php echo esc_attr($nonce); ?>" style="width: 100%;">
              <?php esc_html_e('Subscribe Now', 'whois-crm'); ?>
            </button>
            <button type="button" class="whoiscrm-btn whoiscrm-btn--primary whoiscrm-btn--lg js-subscribe-btn js-btn-annual" 
                    data-pricing-id="<?php echo $pricing_id_a; ?>" 
                    data-nonce="<?php echo esc_attr($nonce); ?>" style="width: 100%; display: none;">
              <?php esc_html_e('Subscribe Annually', 'whois-crm'); ?>
            </button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Secondary Global Plans Grid (Centered 2 Cards) -->
    <?php if (!empty($secondary_plans)) : ?>
      <div class="whoiscrm-pricing-grid-2-centered">
        <?php foreach ($secondary_plans as $pkg) :
          $slug = $pkg->slug ?? '';
          $features = !empty($pkg->features) ? (is_array($pkg->features) ? $pkg->features : json_decode((string)$pkg->features, true)) : [];
          $m_price = isset($pkg->monthly_price->price) ? number_format((float)$pkg->monthly_price->price, 0) : '0';
          $a_price = isset($pkg->annual_price->price) ? number_format((float)$pkg->annual_price->price, 0) : '0';
          $pricing_id_m = isset($pkg->monthly_price->id) ? (int)$pkg->monthly_price->id : 0;
          $pricing_id_a = isset($pkg->annual_price->id) ? (int)$pkg->annual_price->id : 0;
          $icon_html = $category_icons[$slug] ?? $category_icons['bulk-lookup'];
          ?>
          <div class="whoiscrm-pricing-card">
            <div>
              <div class="whoiscrm-pricing-icon-badge" aria-hidden="true">
                <?php echo $icon_html; ?>
              </div>
              <div class="whoiscrm-pricing-card-header">
                <h3 class="whoiscrm-pricing-card-name"><?php echo esc_html($pkg->name ?? ''); ?></h3>
                <p class="whoiscrm-pricing-card-desc"><?php echo esc_html($pkg->description ?? ''); ?></p>
              </div>

              <!-- Price Display -->
              <div class="whoiscrm-pricing-card-price-wrap">
                <span class="whoiscrm-pricing-card-price js-price-amount" 
                      data-monthly="<?php echo esc_attr($m_price); ?>"
                      data-annual="<?php echo esc_attr($a_price); ?>">
                  $<?php echo esc_html($m_price); ?>
                </span>
                <span class="whoiscrm-pricing-card-cycle js-price-cycle">/month</span>
              </div>

              <!-- Feature Checklist -->
              <?php if (!empty($features) && is_array($features)) : ?>
                <ul class="whoiscrm-pricing-card-features">
                  <?php foreach ($features as $f) : ?>
                    <li>
                      <span class="whoiscrm-pricing-feature-check">
                        <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                      </span>
                      <span><?php echo esc_html($f); ?></span>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>

            <!-- Checkout Buttons -->
            <div style="margin-top: 24px;">
              <button type="button" class="whoiscrm-btn whoiscrm-btn--primary whoiscrm-btn--lg js-subscribe-btn js-btn-monthly" 
                      data-pricing-id="<?php echo $pricing_id_m; ?>" 
                      data-nonce="<?php echo esc_attr($nonce); ?>" style="width: 100%;">
                <?php esc_html_e('Subscribe Now', 'whois-crm'); ?>
              </button>
              <button type="button" class="whoiscrm-btn whoiscrm-btn--primary whoiscrm-btn--lg js-subscribe-btn js-btn-annual" 
                      data-pricing-id="<?php echo $pricing_id_a; ?>" 
                      data-nonce="<?php echo esc_attr($nonce); ?>" style="width: 100%; display: none;">
                <?php esc_html_e('Subscribe Annually', 'whois-crm'); ?>
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- Country Specific Plans Grid -->
    <?php if (!empty($country_plans)) : ?>
      <h3 class="whoiscrm-pricing-countries-title"><?php esc_html_e('Country-Specific WHOIS Feed Plans', 'whois-crm'); ?></h3>
      <p style="text-align: center; color: #5C5C6B; margin: 0 auto 32px auto; max-width: 500px; font-size: 0.9375rem;">
        <?php esc_html_e('Subscribe to regional domain data feeds, updated daily. Country plans are billed monthly.', 'whois-crm'); ?>
      </p>

      <div class="whoiscrm-pricing-countries-grid">
        <?php foreach ($country_plans as $pkg) :
          $slug = $pkg->slug ?? '';
          $flag = $country_flags[$slug] ?? '🌐';
          $price_val = isset($pkg->monthly_price->price) ? '$' . number_format((float)$pkg->monthly_price->price, 0) : '$0';
          $tlds_arr = !empty($pkg->tlds) ? (is_array($pkg->tlds) ? $pkg->tlds : json_decode((string)$pkg->tlds, true)) : [];
          $pricing_id_country = isset($pkg->monthly_price->id) ? (int)$pkg->monthly_price->id : 0;
          ?>
          <div class="whoiscrm-pricing-country-card-modern">
            <div>
              <div class="whoiscrm-country-card-top">
                <div class="whoiscrm-country-flag-badge">
                  <?php echo esc_html($flag); ?>
                </div>
                <span class="whoiscrm-badge whoiscrm-badge--success" style="font-size:0.6875rem;">
                  ⚡ <?php esc_html_e('Daily Feed', 'whois-crm'); ?>
                </span>
              </div>

              <h4 class="whoiscrm-country-card-title">
                <?php echo esc_html($pkg->name ?? ''); ?>
              </h4>
              <p style="font-size: 0.8125rem; color: #5C5C6B; margin: 0 0 12px 0; line-height: 1.4;">
                <?php echo esc_html($pkg->description ?: sprintf(__('Daily WHOIS data feed for %s registry domains.', 'whois-crm'), $pkg->name)); ?>
              </p>

              <?php if (!empty($tlds_arr) && is_array($tlds_arr)) : ?>
                <div class="whoiscrm-tld-pills">
                  <?php foreach ($tlds_arr as $tld) : ?>
                    <span class="whoiscrm-tld-pill"><?php echo esc_html($tld); ?></span>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>

            <div class="whoiscrm-country-card-footer">
              <div class="whoiscrm-country-card-price">
                <span class="price-val"><?php echo esc_html($price_val); ?></span>
                <span class="price-cycle">/month</span>
              </div>
              <button type="button" class="whoiscrm-btn whoiscrm-btn--primary whoiscrm-btn--sm js-subscribe-btn" 
                      data-pricing-id="<?php echo $pricing_id_country; ?>" 
                      data-nonce="<?php echo esc_attr($nonce); ?>">
                <?php esc_html_e('Subscribe →', 'whois-crm'); ?>
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- Trust & Security Banner -->
    <div class="whoiscrm-pricing-trust-banner">
      <div class="whoiscrm-trust-item">
        <svg viewBox="0 0 24 24"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
        <div class="whoiscrm-trust-text">Instant Data Access<br><span style="font-weight:400; color:#5C5C6B;">Immediate download link</span></div>
      </div>
      <div class="whoiscrm-trust-item">
        <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        <div class="whoiscrm-trust-text">256-Bit SSL Checkout<br><span style="font-weight:400; color:#5C5C6B;">Encrypted by Stripe</span></div>
      </div>
      <div class="whoiscrm-trust-item">
        <svg viewBox="0 0 24 24"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
        <div class="whoiscrm-trust-text">Cancel Anytime<br><span style="font-weight:400; color:#5C5C6B;">1-click self serve</span></div>
      </div>
      <div class="whoiscrm-trust-item">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>
        <div class="whoiscrm-trust-text">Daily WHOIS Dumps<br><span style="font-weight:400; color:#5C5C6B;">Updated every 24h</span></div>
      </div>
    </div>

  </div><!-- .whoiscrm-portal-container -->
</div><!-- .whoiscrm-pricing-wrapper -->

<script>
document.addEventListener('DOMContentLoaded', function() {
  const checkbox = document.getElementById('whoiscrm-pricing-cycle-checkbox');
  const labels = document.querySelectorAll('.js-toggle-label');
  const prices = document.querySelectorAll('.js-price-amount');
  const cycles = document.querySelectorAll('.js-price-cycle');
  const monthlyBtns = document.querySelectorAll('.js-btn-monthly');
  const annualBtns = document.querySelectorAll('.js-btn-annual');

  if (!checkbox) return;

  function updatePricingMode(isAnnual) {
    labels.forEach(label => {
      if (label.dataset.cycle === (isAnnual ? 'annually' : 'monthly')) {
        label.classList.add('is-active');
      } else {
        label.classList.remove('is-active');
      }
    });

    prices.forEach(price => {
      price.textContent = '$' + (isAnnual ? price.dataset.annual : price.dataset.monthly);
    });

    cycles.forEach(cycle => {
      cycle.textContent = isAnnual ? '/year' : '/month';
    });

    monthlyBtns.forEach(btn => btn.style.display = isAnnual ? 'none' : 'block');
    annualBtns.forEach(btn => btn.style.display = isAnnual ? 'block' : 'none');
  }

  checkbox.addEventListener('change', function() {
    updatePricingMode(checkbox.checked);
  });

  labels.forEach(label => {
    label.addEventListener('click', function() {
      const targetAnnual = (label.dataset.cycle === 'annually');
      checkbox.checked = targetAnnual;
      updatePricingMode(targetAnnual);
    });
  });
});
</script>
