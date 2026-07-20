<?php
/**
 * Template: Public Pricing Table & Packages Selector
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
    // If $pkg is an object, normalize
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
        // Raw JSON fallback format
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
?>

<div class="whoiscrm-pricing-wrapper">
  <div class="whoiscrm-portal-container">
    
    <div class="whoiscrm-pricing-header">
      <h2 class="whoiscrm-pricing-title"><?php esc_html_e('Simple, Transparent Pricing', 'whois-crm'); ?></h2>
      <p class="whoiscrm-pricing-subtitle">
        <?php esc_html_e('Choose the data feed coverage that meets your cybersecurity or prospecting needs.', 'whois-crm'); ?>
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
        <span style="background: rgba(255,102,33,0.12); color: #FF6621; padding: 2px 8px; font-size: 0.75rem; border-radius: 4px; font-weight: 700; margin-left: 6px;">
          <?php esc_html_e('Save ~15%', 'whois-crm'); ?>
        </span>
      </span>
    </div>

    <!-- Global Service Plans Grid -->
    <div class="whoiscrm-pricing-grid">
      <?php foreach ($global_plans as $index => $pkg) :
        $slug = $pkg->slug ?? '';
        $is_featured = ($slug === 'lead-generation');
        $features = [];
        if (!empty($pkg->features)) {
            $features = is_array($pkg->features) ? $pkg->features : json_decode((string)$pkg->features, true);
        }
        $m_price = isset($pkg->monthly_price->price) ? number_format((float)$pkg->monthly_price->price, 0) : '0';
        $a_price = isset($pkg->annual_price->price) ? number_format((float)$pkg->annual_price->price, 0) : '0';
        $pricing_id_m = isset($pkg->monthly_price->id) ? (int)$pkg->monthly_price->id : 0;
        $pricing_id_a = isset($pkg->annual_price->id) ? (int)$pkg->annual_price->id : 0;
        ?>
        <div class="whoiscrm-pricing-card <?php echo $is_featured ? 'whoiscrm-pricing-card--featured' : ''; ?>">
          <div>
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
                  <li><?php echo esc_html($f); ?></li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>

          <!-- Checkout Buttons -->
          <div style="margin-top: 24px;">
            <!-- Monthly Subscribe Button -->
            <button type="button" class="whoiscrm-btn whoiscrm-btn--primary whoiscrm-btn--lg js-subscribe-btn js-btn-monthly" 
                    data-pricing-id="<?php echo $pricing_id_m; ?>" 
                    data-nonce="<?php echo esc_attr($nonce); ?>" style="width: 100%;">
              <?php esc_html_e('Subscribe Now', 'whois-crm'); ?>
            </button>

            <!-- Annual Subscribe Button (Initially hidden) -->
            <button type="button" class="whoiscrm-btn whoiscrm-btn--primary whoiscrm-btn--lg js-subscribe-btn js-btn-annual" 
                    data-pricing-id="<?php echo $pricing_id_a; ?>" 
                    data-nonce="<?php echo esc_attr($nonce); ?>" style="width: 100%; display: none;">
              <?php esc_html_e('Subscribe Annually', 'whois-crm'); ?>
            </button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Country Specific Plans Grid -->
    <?php if (!empty($country_plans)) : ?>
      <h3 class="whoiscrm-pricing-countries-title"><?php esc_html_e('Country-Specific WHOIS Feed Plans', 'whois-crm'); ?></h3>
      <p style="text-align: center; color: #5C5C6B; margin: -12px auto 32px auto; max-width: 500px; font-size: 0.9375rem;">
        <?php esc_html_e('Subscribe to regional domain data feeds, updated daily. Country plans are billed monthly.', 'whois-crm'); ?>
      </p>

      <div class="whoiscrm-pricing-countries-grid">
        <?php foreach ($country_plans as $pkg) :
          $price_label = isset($pkg->monthly_price->price) ? '$' . number_format((float)$pkg->monthly_price->price, 0) . '/mo' : '—';
          $tlds_arr = [];
          if (!empty($pkg->tlds)) {
              $tlds_arr = is_array($pkg->tlds) ? $pkg->tlds : json_decode((string)$pkg->tlds, true);
          }
          $pricing_id_country = isset($pkg->monthly_price->id) ? (int)$pkg->monthly_price->id : 0;
          ?>
          <div class="whoiscrm-pricing-country-card">
            <div>
              <h4 style="margin: 0 0 4px 0; font-size: 1.125rem; font-weight: 700; color: #0A0A0B;">
                <?php echo esc_html($pkg->name ?? ''); ?>
              </h4>
              <?php if (!empty($tlds_arr) && is_array($tlds_arr)) : ?>
                <span style="font-size: 0.75rem; color: #9898A8;">
                  <?php echo esc_html(implode(', ', $tlds_arr)); ?>
                </span>
              <?php endif; ?>
            </div>
            <div style="text-align: right;">
              <div style="font-weight: 700; color: #FF6621; font-size: 1.125rem; margin-bottom: 6px;">
                <?php echo esc_html($price_label); ?>
              </div>
              <button type="button" class="whoiscrm-btn whoiscrm-btn--secondary whoiscrm-btn--sm js-subscribe-btn" 
                      data-pricing-id="<?php echo $pricing_id_country; ?>" 
                      data-nonce="<?php echo esc_attr($nonce); ?>">
                <?php esc_html_e('Subscribe', 'whois-crm'); ?>
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

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
