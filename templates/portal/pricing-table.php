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
    // Fetch pricing variants
    $pricings = $pricing_model->get_for_package((int) $pkg->id);
    $pkg->monthly_price = null;
    $pkg->annual_price = null;
    
    foreach ($pricings as $p) {
        if ($p->billing_cycle === 'monthly') {
            $pkg->monthly_price = $p;
        } elseif ($p->billing_cycle === 'annually') {
            $pkg->annual_price = $p;
        }
    }

    if ($pkg->type === 'global_service') {
        $global_plans[] = $pkg;
    } else {
        $country_plans[] = $pkg;
    }
}
?>

<div class="whoiscrm-pricing-wrapper">
  
  <div class="whoiscrm-pricing-header">
    <h2 class="whoiscrm-pricing-title"><?php esc_html_e('Simple, Transparent Pricing', 'whois-crm'); ?></h2>
    <p class="whoiscrm-pricing-subtitle">
      <?php esc_html_e('Choose the data feed coverage that meets your cybersecurity or prospecting needs.', 'whois-crm'); ?>
    </p>
  </div>

  <!-- Billing Cycle Toggle Switch (Only applies to Global Plans) -->
  <div class="whoiscrm-pricing-toggle-wrap">
    <span class="whoiscrm-pricing-toggle-label js-toggle-label is-active" data-cycle="monthly"><?php esc_html_e('Bill Monthly', 'whois-crm'); ?></span>
    <label class="whoiscrm-toggle">
      <input type="checkbox" id="whoiscrm-pricing-cycle-checkbox">
      <span class="whoiscrm-toggle__slider"></span>
    </label>
    <span class="whoiscrm-pricing-toggle-label js-toggle-label" data-cycle="annually">
      <?php esc_html_e('Bill Annually', 'whois-crm'); ?>
      <span style="background: rgba(255,102,33,0.12); color: var(--color-primary); padding: 2px 6px; font-size: 0.6875rem; border-radius: 4px; font-weight: 700; margin-left: 4px;">
        <?php esc_html_e('Save ~15%', 'whois-crm'); ?>
      </span>
    </span>
  </div>

  <!-- Global Service Plans Grid -->
  <div class="whoiscrm-pricing-grid">
    <?php foreach ($global_plans as $index => $pkg) :
      $is_featured = ($pkg->slug === 'lead-generation'); // Feature lead gen by default
      $features = $pkg->features ? json_decode($pkg->features, true) : [];
      ?>
      <div class="whoiscrm-pricing-card <?php echo $is_featured ? 'whoiscrm-pricing-card--featured' : ''; ?>">
        <div>
          <div class="whoiscrm-pricing-card-header">
            <h3 class="whoiscrm-pricing-card-name"><?php echo esc_html($pkg->name); ?></h3>
            <p class="whoiscrm-pricing-card-desc"><?php echo esc_html($pkg->description); ?></p>
          </div>

          <!-- Price Display -->
          <div class="whoiscrm-pricing-card-price-wrap">
            <span class="whoiscrm-pricing-card-price js-price-amount" 
                  data-monthly="<?php echo isset($pkg->monthly_price) ? esc_attr(number_format((float)$pkg->monthly_price->price, 0)) : '0'; ?>"
                  data-annual="<?php echo isset($pkg->annual_price) ? esc_attr(number_format((float)$pkg->annual_price->price, 0)) : '0'; ?>">
              $<?php echo isset($pkg->monthly_price) ? esc_html(number_format((float)$pkg->monthly_price->price, 0)) : '0'; ?>
            </span>
            <span class="whoiscrm-pricing-card-cycle js-price-cycle">/month</span>
          </div>

          <!-- Feature Checklist -->
          <ul class="whoiscrm-pricing-card-features">
            <?php foreach ($features as $f) : ?>
              <li><?php echo esc_html($f); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>

        <!-- Checkout Buttons -->
        <div style="margin-top: var(--space-4);">
          <!-- Monthly Subscribe Button -->
          <?php if (isset($pkg->monthly_price)) : ?>
            <button type="button" class="whoiscrm-btn whoiscrm-btn--primary whoiscrm-btn--lg js-subscribe-btn js-btn-monthly" 
                    data-pricing-id="<?php echo (int) $pkg->monthly_price->id; ?>" 
                    data-nonce="<?php echo esc_attr($nonce); ?>" style="width: 100%;">
              <?php esc_html_e('Subscribe Now', 'whois-crm'); ?>
            </button>
          <?php endif; ?>

          <!-- Annual Subscribe Button (Initially hidden) -->
          <?php if (isset($pkg->annual_price)) : ?>
            <button type="button" class="whoiscrm-btn whoiscrm-btn--primary whoiscrm-btn--lg js-subscribe-btn js-btn-annual" 
                    data-pricing-id="<?php echo (int) $pkg->annual_price->id; ?>" 
                    data-nonce="<?php echo esc_attr($nonce); ?>" style="width: 100%; display: none;">
              <?php esc_html_e('Subscribe Annually', 'whois-crm'); ?>
            </button>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Country Specific Plans Grid -->
  <?php if (!empty($country_plans)) : ?>
    <h3 class="whoiscrm-pricing-countries-title"><?php esc_html_e('Country-Specific WHOIS Feed Plans', 'whois-crm'); ?></h3>
    <p style="text-align: center; color: var(--color-text-secondary); margin: -20px auto var(--space-8) auto; max-width: 500px; font-size: 0.9375rem;">
      <?php esc_html_e('Subscribe to regional domain data feeds, updated daily. Country plans are billed monthly.', 'whois-crm'); ?>
    </p>

    <div class="whoiscrm-pricing-countries-grid">
      <?php foreach ($country_plans as $pkg) :
        $price_label = isset($pkg->monthly_price) ? '$' . number_format((float)$pkg->monthly_price->price, 0) . '/mo' : '—';
        $tlds_arr = $pkg->tlds ? json_decode($pkg->tlds, true) : [];
        ?>
        <div class="whoiscrm-pricing-country-card">
          <div>
            <h4 style="margin: 0 0 4px 0; font-size: var(--text-h4); font-weight: 600; color: var(--color-black);">
              <?php echo esc_html($pkg->name); ?>
            </h4>
            <span style="font-size: 0.75rem; color: var(--color-text-muted);">
              <?php echo esc_html(implode(', ', $tlds_arr)); ?>
            </span>
          </div>
          <div style="text-align: right;">
            <div style="font-weight: 700; color: var(--color-primary); font-size: 1.1rem; margin-bottom: 6px;">
              <?php echo esc_html($price_label); ?>
            </div>
            <?php if (isset($pkg->monthly_price)) : ?>
              <button type="button" class="whoiscrm-btn whoiscrm-btn--secondary whoiscrm-btn--sm js-subscribe-btn" 
                      data-pricing-id="<?php echo (int) $pkg->monthly_price->id; ?>" 
                      data-nonce="<?php echo esc_attr($nonce); ?>">
                <?php esc_html_e('Subscribe', 'whois-crm'); ?>
              </button>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

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
    // Update labels highlights
    labels.forEach(label => {
      if (label.dataset.cycle === (isAnnual ? 'annually' : 'monthly')) {
        label.classList.add('is-active');
      } else {
        label.classList.remove('is-active');
      }
    });

    // Toggle price text
    prices.forEach(price => {
      price.textContent = '$' + (isAnnual ? price.dataset.annual : price.dataset.monthly);
    });

    // Toggle cycles text
    cycles.forEach(cycle => {
      cycle.textContent = isAnnual ? '/year' : '/month';
    });

    // Toggle button visibilities
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
