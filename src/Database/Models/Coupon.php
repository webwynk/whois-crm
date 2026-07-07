<?php

declare(strict_types=1);

namespace WhoisCRM\Database\Models;

/**
 * Coupon model.
 *
 * Represents a discount coupon (percentage or fixed amount).
 * Validated against usage limits, dates, and minimum purchase.
 */
class Coupon extends BaseModel
{
    protected function table_name(): string
    {
        return 'coupons';
    }

    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED      = 'fixed';

    /**
     * Find an active, valid coupon by code.
     *
     * Returns null if the code doesn't exist or is expired/inactive.
     */
    public function find_valid(string $code): ?object
    {
        global $wpdb;

        $code = strtoupper(trim($code));

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table}
                 WHERE code = %s
                   AND is_active = 1
                   AND (starts_at IS NULL OR starts_at <= NOW())
                   AND (expires_at IS NULL OR expires_at >= NOW())
                   AND (max_uses IS NULL OR used_count < max_uses)
                 LIMIT 1",
                $code
            )
        ) ?: null;
    }

    /**
     * Find a coupon by its Stripe coupon ID.
     */
    public function find_by_stripe_coupon(string $stripe_coupon_id): ?object
    {
        return $this->find_by('stripe_coupon_id', $stripe_coupon_id);
    }

    /**
     * Check if a specific customer has already used this coupon.
     */
    public function customer_has_used(int $coupon_id, int $customer_id): bool
    {
        global $wpdb;

        $payments_table = $wpdb->prefix . 'whoiscrm_payments';

        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$payments_table}
                 WHERE coupon_id = %d AND customer_id = %d AND status = 'succeeded'",
                $coupon_id,
                $customer_id
            )
        );

        return $count > 0;
    }

    /**
     * Find a coupon by its code (case-insensitive).
     */
    public function find_by_code(string $code): ?object
    {
        return $this->find_by('code', strtoupper(trim($code)));
    }

    /**
     * Validate a coupon for use on checkout.
     *
     * Supports both string codes and full coupon objects.
     *
     * @return array{valid: bool, error: string, coupon: object|null}
     */
    public function validate(mixed $coupon_or_code, float $amount, int $package_id = 0, int $customer_id = 0): array
    {
        $coupon = $coupon_or_code;

        if (is_string($coupon)) {
            $coupon = $this->find_by_code($coupon);
        }

        if (!$coupon) {
            return ['valid' => false, 'error' => __('Invalid coupon code.', 'whois-crm'), 'coupon' => null];
        }

        if (!$coupon->is_active) {
            return ['valid' => false, 'error' => __('This coupon is no longer active.', 'whois-crm'), 'coupon' => null];
        }

        if ($coupon->starts_at && strtotime($coupon->starts_at) > time()) {
            return ['valid' => false, 'error' => __('This coupon is not yet active.', 'whois-crm'), 'coupon' => null];
        }

        if ($coupon->expires_at && strtotime($coupon->expires_at) < time()) {
            return ['valid' => false, 'error' => __('This coupon has expired.', 'whois-crm'), 'coupon' => null];
        }

        if ($coupon->max_uses !== null && (int)$coupon->used_count >= (int)$coupon->max_uses) {
            return ['valid' => false, 'error' => __('This coupon has reached its usage limit.', 'whois-crm'), 'coupon' => null];
        }

        // Check per-customer usage limit
        if ($coupon->max_uses_per_customer !== null) {
            if ($customer_id === 0 && is_user_logged_in()) {
                $customer = (new Customer())->find_by_user_id(get_current_user_id());
                if ($customer) {
                    $customer_id = (int) $customer->id;
                }
            }
            if ($customer_id > 0 && $this->customer_has_used((int) $coupon->id, $customer_id)) {
                return ['valid' => false, 'error' => __('You have already used this coupon.', 'whois-crm'), 'coupon' => null];
            }
        }

        // Check minimum purchase amount
        if ($coupon->min_amount !== null && $amount < (float) $coupon->min_amount) {
            return [
                'valid'  => false,
                'error'  => sprintf(
                    /* translators: %s: minimum amount */
                    __('Minimum purchase amount of %s required.', 'whois-crm'),
                    '$' . number_format((float) $coupon->min_amount, 2)
                ),
                'coupon' => null,
            ];
        }

        // Check applicable packages
        if (!empty($coupon->applicable_packages) && $package_id > 0) {
            $applicable = json_decode($coupon->applicable_packages, true) ?: [];
            if (!in_array($package_id, $applicable, true)) {
                return ['valid' => false, 'error' => __('This coupon does not apply to the selected plan.', 'whois-crm'), 'coupon' => null];
            }
        }

        return ['valid' => true, 'error' => '', 'coupon' => $coupon];
    }

    /**
     * Calculate the discount amount for a coupon.
     */
    public function calculate_discount(object $coupon, float $amount): float
    {
        if ($coupon->type === self::TYPE_PERCENTAGE) {
            return round($amount * ((float) $coupon->value / 100), 2);
        }

        // Fixed discount — cannot exceed the amount.
        return min((float) $coupon->value, $amount);
    }

    /**
     * Increment the used_count for a coupon.
     */
    public function increment_usage(int $id): bool
    {
        global $wpdb;

        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->table} SET used_count = used_count + 1, updated_at = %s WHERE id = %d",
                current_time('mysql', true),
                $id
            )
        );

        return $result !== false;
    }

    /**
     * Get all active coupons for the admin list.
     *
     * @return array<object>
     */
    public function get_admin_list(int $page = 1, int $per_page = 20): array
    {
        global $wpdb;

        $offset = ($page - 1) * $per_page;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table} ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            )
        ) ?: [];
    }
}
