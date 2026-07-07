<?php

declare(strict_types=1);

namespace WhoisCRM\Database\Models;

/**
 * ApiKey model.
 *
 * Manages API keys for Enterprise customers.
 * Each customer can have one active API key.
 * Daily rate limits are tracked and reset by a cron job.
 */
class ApiKey extends BaseModel
{
    protected function table_name(): string
    {
        return 'api_keys';
    }

    /**
     * Find an active API key by its public key string.
     */
    public function find_active(string $api_key): ?object
    {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE api_key = %s AND is_active = 1 LIMIT 1",
                $api_key
            )
        ) ?: null;
    }

    /**
     * Get the API key record for a customer.
     */
    public function get_for_customer(int $customer_id): ?object
    {
        return $this->find_by('customer_id', $customer_id);
    }

    /**
     * Generate and store a new API key for a customer.
     *
     * Returns the plain-text key (only shown once).
     */
    public function generate_for_customer(int $customer_id, string $name = 'Default'): ?string
    {
        // Delete any existing key.
        $this->delete_for_customer($customer_id);

        // Generate secure random key and secret.
        $plain_key    = 'wcrm_' . bin2hex(random_bytes(20));   // 45 chars
        $plain_secret = bin2hex(random_bytes(32));              // 64 chars
        $secret_hash  = wp_hash_password($plain_secret);

        $id = $this->insert([
            'customer_id'       => $customer_id,
            'api_key'           => $plain_key,
            'api_secret_hash'   => $secret_hash,
            'name'              => $name,
            'permissions'       => wp_json_encode(['download']),
            'rate_limit_per_day' => (int) get_option('whoiscrm_api_rate_limit', 1000),
            'requests_today'    => 0,
            'is_active'         => 1,
        ]);

        return $id ? $plain_key : null;
    }

    /**
     * Revoke (hard-delete) all API keys for a customer.
     */
    public function delete_for_customer(int $customer_id): bool
    {
        return $this->delete_where(['customer_id' => $customer_id]);
    }

    /**
     * Verify the secret for an API key.
     *
     * Used for HMAC-style secret verification on API requests.
     */
    public function verify_secret(object $api_key_record, string $plain_secret): bool
    {
        return wp_check_password($plain_secret, $api_key_record->api_secret_hash);
    }

    /**
     * Check if the API key is within its daily rate limit.
     */
    public function is_within_rate_limit(object $api_key_record): bool
    {
        return (int) $api_key_record->requests_today < (int) $api_key_record->rate_limit_per_day;
    }

    /**
     * Increment the request count and update last-used metadata.
     */
    public function record_request(int $id): bool
    {
        global $wpdb;

        $ip = '';
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = sanitize_text_field($_SERVER['REMOTE_ADDR']);
        }

        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$this->table}
                 SET requests_today = requests_today + 1,
                     last_used_at = %s,
                     last_used_ip = %s,
                     updated_at = %s
                 WHERE id = %d",
                current_time('mysql', true),
                $ip,
                current_time('mysql', true),
                $id
            )
        );

        return $result !== false;
    }

    /**
     * Reset daily request counters for all keys.
     * Called by the daily cron job.
     */
    public function reset_daily_counts(): bool
    {
        global $wpdb;

        $result = $wpdb->query(
            "UPDATE {$this->table} SET requests_today = 0"
        );

        return $result !== false;
    }

    /**
     * Deactivate an API key.
     */
    public function deactivate(int $id): bool
    {
        return $this->update($id, ['is_active' => 0]);
    }
}
