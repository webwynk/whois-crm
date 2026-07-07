<?php

declare(strict_types=1);

namespace WhoisCRM\Stripe;

use WhoisCRM\Database\Models\Customer;
use WhoisCRM\Database\Models\Package;
use WhoisCRM\Database\Models\PackagePricing;
use WhoisCRM\Database\Models\Subscription;
use WhoisCRM\Database\Models\Payment;
use WhoisCRM\Database\Models\Coupon;
use WhoisCRM\Database\Models\ActivityLog;

/**
 * Stripe API gateway.
 *
 * Initialises the Stripe PHP SDK and provides higher-level
 * wrappers for all Stripe operations used by this plugin:
 *  - Product / Price sync
 *  - Customer create / retrieve
 *  - Checkout Session creation
 *  - Subscription cancellation
 *  - Coupon sync
 *
 * ALL Stripe API calls are wrapped in try/catch so callers
 * receive either a return value or a WP_Error.
 */
class StripeGateway
{
    /** @var string Stripe API version pinned for stability. */
    private const API_VERSION = '2024-12-18.acacia';

    private string $mode;

    public function __construct()
    {
        $this->mode = get_option('whoiscrm_stripe_mode', 'test');

        $secret_key = get_option("whoiscrm_stripe_{$this->mode}_secret_key", '');

        if (empty($secret_key)) {
            return; // API key not configured yet — skip SDK init.
        }

        \Stripe\Stripe::setApiKey($secret_key);
        \Stripe\Stripe::setApiVersion(self::API_VERSION);
    }

    // ─── Mode helpers ─────────────────────────────────────────────────────

    public function get_mode(): string
    {
        return $this->mode;
    }

    public function get_publishable_key(): string
    {
        return get_option("whoiscrm_stripe_{$this->mode}_publishable_key", '');
    }

    public function is_configured(): bool
    {
        $key = get_option("whoiscrm_stripe_{$this->mode}_secret_key", '');
        return !empty($key);
    }

    // ─── Product Sync ─────────────────────────────────────────────────────

    /**
     * Create a new Stripe Product for a package.
     *
     * @return string|\WP_Error  Stripe Product ID or WP_Error on failure.
     */
    public function create_product(object $package)
    {
        try {
            $product = \Stripe\Product::create([
                'name'        => $package->name,
                'description' => $package->description ?: '',
                'metadata'    => [
                    'whoiscrm_package_id'   => $package->id,
                    'whoiscrm_package_slug' => $package->slug,
                ],
            ]);

            return $product->id;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return new \WP_Error('stripe_error', $e->getMessage());
        }
    }

    /**
     * Update an existing Stripe Product's name/description.
     *
     * @return bool|\WP_Error
     */
    public function update_product(string $stripe_product_id, object $package)
    {
        try {
            \Stripe\Product::update($stripe_product_id, [
                'name'        => $package->name,
                'description' => $package->description ?: '',
            ]);

            return true;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return new \WP_Error('stripe_error', $e->getMessage());
        }
    }

    // ─── Price Sync ───────────────────────────────────────────────────────

    /**
     * Create a Stripe Price for a package pricing row.
     *
     * @param string $stripe_product_id  Stripe Product ID.
     * @param float  $price              Price in USD.
     * @param string $billing_cycle      'monthly' or 'annually'.
     * @param int    $pricing_id         Our DB pricing ID (stored in metadata).
     *
     * @return string|\WP_Error  Stripe Price ID or WP_Error.
     */
    public function create_price(
        string $stripe_product_id,
        float $price,
        string $billing_cycle,
        int $pricing_id
    ) {
        $interval = ($billing_cycle === 'annually') ? 'year' : 'month';

        try {
            $stripe_price = \Stripe\Price::create([
                'product'     => $stripe_product_id,
                'unit_amount' => (int) round($price * 100), // Stripe uses cents
                'currency'    => 'usd',
                'recurring'   => [
                    'interval'       => $interval,
                    'interval_count' => 1,
                ],
                'metadata'    => [
                    'whoiscrm_package_pricing_id' => $pricing_id,
                ],
            ]);

            return $stripe_price->id;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return new \WP_Error('stripe_error', $e->getMessage());
        }
    }

    /**
     * Deactivate a Stripe Price (Stripe doesn't allow deletion or price edits).
     * Call this before creating a replacement price.
     */
    public function deactivate_price(string $stripe_price_id): void
    {
        try {
            \Stripe\Price::update($stripe_price_id, ['active' => false]);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            // Log and continue — deactivation failure is non-fatal.
            error_log('[WHOISCRM Stripe] Failed to deactivate price ' . $stripe_price_id . ': ' . $e->getMessage());
        }
    }

    // ─── Customer ─────────────────────────────────────────────────────────

    /**
     * Return the Stripe Customer ID for a CRM customer, creating one if needed.
     *
     * @return string|\WP_Error
     */
    public function get_or_create_customer(int $customer_id)
    {
        $customer_model = new Customer();
        $customer       = $customer_model->find($customer_id);

        if (!$customer) {
            return new \WP_Error('not_found', 'Customer not found.');
        }

        if (!empty($customer->stripe_customer_id)) {
            return $customer->stripe_customer_id;
        }

        $wp_user = get_userdata($customer->user_id);
        if (!$wp_user) {
            return new \WP_Error('not_found', 'WordPress user not found.');
        }

        try {
            $stripe_customer = \Stripe\Customer::create([
                'email'    => $wp_user->user_email,
                'name'     => $wp_user->display_name,
                'metadata' => [
                    'whoiscrm_customer_id' => $customer_id,
                    'whoiscrm_user_id'     => $customer->user_id,
                ],
            ]);

            $customer_model->update($customer_id, ['stripe_customer_id' => $stripe_customer->id]);

            return $stripe_customer->id;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return new \WP_Error('stripe_error', $e->getMessage());
        }
    }

    // ─── Checkout Session ─────────────────────────────────────────────────

    /**
     * Create a Stripe Checkout Session for a subscription.
     *
     * @return \Stripe\Checkout\Session|\WP_Error
     */
    public function create_checkout_session(
        int $customer_id,
        int $pricing_id,
        string $coupon_stripe_id = ''
    ) {
        $pricing  = (new PackagePricing())->find($pricing_id);
        $package  = (new Package())->find($pricing->package_id);

        if (!$pricing || !$package) {
            return new \WP_Error('not_found', 'Package or pricing not found.');
        }

        if (empty($pricing->stripe_price_id)) {
            return new \WP_Error('not_synced', 'This package has not been synced to Stripe yet. Please sync it first.');
        }

        $stripe_customer_id = $this->get_or_create_customer($customer_id);

        if (is_wp_error($stripe_customer_id)) {
            return $stripe_customer_id;
        }

        $portal_page = get_option('whoiscrm_portal_page_id');
        $portal_url  = $portal_page ? get_permalink($portal_page) : home_url('/my-account/');
        $pricing_page = get_option('whoiscrm_pricing_page_id');
        $pricing_url  = $pricing_page ? get_permalink($pricing_page) : home_url('/pricing/');

        $params = [
            'mode'      => 'subscription',
            'customer'  => $stripe_customer_id,
            'line_items' => [[
                'price'    => $pricing->stripe_price_id,
                'quantity' => 1,
            ]],
            'success_url'         => add_query_arg(
                ['checkout' => 'success', 'session_id' => '{CHECKOUT_SESSION_ID}'],
                trailingslashit($portal_url) . 'subscriptions/'
            ),
            'cancel_url'          => add_query_arg('checkout', 'cancelled', $pricing_url),
            'metadata'            => [
                'whoiscrm_customer_id'         => $customer_id,
                'whoiscrm_package_id'          => $package->id,
                'whoiscrm_package_pricing_id'  => $pricing_id,
            ],
            'subscription_data'   => [
                'metadata' => [
                    'whoiscrm_customer_id'        => $customer_id,
                    'whoiscrm_package_id'         => $package->id,
                    'whoiscrm_package_pricing_id' => $pricing_id,
                ],
            ],
            'tax_id_collection'   => ['enabled' => true],
            'allow_promotion_codes' => false,
        ];

        // Apply coupon discount
        if (!empty($coupon_stripe_id)) {
            $params['discounts'] = [['coupon' => $coupon_stripe_id]];
        }

        try {
            return \Stripe\Checkout\Session::create($params);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            error_log('[WHOISCRM Stripe] Invalid checkout request: ' . $e->getMessage());
            return new \WP_Error('stripe_invalid_request', 'Payment configuration error. Please contact support.');
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log('[WHOISCRM Stripe] Checkout session error: ' . $e->getMessage());
            return new \WP_Error('stripe_error', 'Payment service temporarily unavailable. Please try again.');
        }
    }

    // ─── Subscription Management ──────────────────────────────────────────

    /**
     * Cancel a Stripe subscription at period end.
     *
     * The customer keeps access until `expires_at` — they just won't renew.
     */
    public function cancel_at_period_end(string $stripe_subscription_id): bool
    {
        try {
            \Stripe\Subscription::update($stripe_subscription_id, [
                'cancel_at_period_end' => true,
            ]);

            return true;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log('[WHOISCRM Stripe] Cancel subscription error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cancel a Stripe subscription immediately.
     */
    public function cancel_immediately(string $stripe_subscription_id): bool
    {
        try {
            $sub = \Stripe\Subscription::retrieve($stripe_subscription_id);
            $sub->cancel();

            return true;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            error_log('[WHOISCRM Stripe] Immediate cancel error: ' . $e->getMessage());
            return false;
        }
    }

    // ─── Coupon Sync ──────────────────────────────────────────────────────

    /**
     * Sync a plugin coupon to Stripe and return the Stripe coupon ID.
     *
     * @return string|\WP_Error  Stripe coupon ID.
     */
    public function sync_coupon(object $coupon)
    {
        $stripe_id = 'whoiscrm_' . strtolower($coupon->code);

        // Check if it already exists in Stripe
        try {
            $existing = \Stripe\Coupon::retrieve($stripe_id);
            if ($existing && !$existing->deleted) {
                return $existing->id;
            }
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            // Coupon doesn't exist — create it below.
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return new \WP_Error('stripe_error', $e->getMessage());
        }

        $params = [
            'id'       => $stripe_id,
            'name'     => $coupon->code,
            'metadata' => ['whoiscrm_coupon_id' => $coupon->id],
        ];

        if ($coupon->type === 'percentage') {
            $params['percent_off'] = (float) $coupon->value;
        } else {
            $params['amount_off'] = (int) round($coupon->value * 100);
            $params['currency']   = 'usd';
        }

        if (!empty($coupon->expires_at)) {
            $params['redeem_by'] = strtotime($coupon->expires_at);
        }

        if (!empty($coupon->max_uses)) {
            $params['max_redemptions'] = (int) $coupon->max_uses;
        }

        try {
            $stripe_coupon = \Stripe\Coupon::create($params);
            return $stripe_coupon->id;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return new \WP_Error('stripe_error', $e->getMessage());
        }
    }

    // ─── Webhook Signature Verification ───────────────────────────────────

    /**
     * Construct and verify a Stripe webhook event from the raw request.
     *
     * @return \Stripe\Event|\WP_Error
     */
    public function construct_webhook_event(string $payload, string $sig_header)
    {
        $secret = get_option('whoiscrm_stripe_webhook_secret', '');

        if (empty($secret)) {
            return new \WP_Error('no_secret', 'Webhook secret not configured.');
        }

        try {
            return \Stripe\Webhook::constructEvent($payload, $sig_header, $secret);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return new \WP_Error('invalid_signature', 'Invalid webhook signature.');
        } catch (\UnexpectedValueException $e) {
            return new \WP_Error('invalid_payload', 'Invalid webhook payload.');
        }
    }
}
