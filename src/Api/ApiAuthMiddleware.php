<?php

declare(strict_types=1);

namespace WhoisCRM\Api;

use WhoisCRM\Database\Models\ApiKey;
use WhoisCRM\Database\Models\Subscription;
use WhoisCRM\Database\Models\Customer;

/**
 * REST API Authentication Middleware.
 *
 * Verifies developer API Keys passed via X-API-Key or Authorization headers,
 * validates active Enterprise subscriptions, and enforces daily rate limit quotas.
 */
class ApiAuthMiddleware
{
    /**
     * Authenticate the REST request.
     *
     * @param \WP_REST_Request $request
     * @return bool|\WP_Error
     */
    public function authenticate(\WP_REST_Request $request)
    {
        // 0. Enforce HTTPS in production environments (allow localhost for dev/testing)
        if (!is_ssl() && !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
            return new \WP_Error(
                'rest_forbidden',
                __('SSL/HTTPS is required to access the developer REST API.', 'whois-crm'),
                ['status' => 403]
            );
        }

        // 1. Extract API Key from headers (X-API-Key or Bearer Authorization)
        $key = $request->get_header('X-API-Key');

        if (empty($key)) {
            $auth_header = $request->get_header('Authorization');
            if (!empty($auth_header) && preg_match('/Bearer\s+(wcrm_[a-f0-9]+)/i', $auth_header, $matches)) {
                $key = $matches[1];
            }
        }

        if (empty($key)) {
            return new \WP_Error(
                'rest_unauthorized',
                __('Missing API Key. Please provide it via X-API-Key or Authorization: Bearer header.', 'whois-crm'),
                ['status' => 401]
            );
        }

        // 2. Lookup active API Key record
        $api_key_model = new ApiKey();
        $key_record    = $api_key_model->find_active($key);

        if (!$key_record) {
            return new \WP_Error(
                'rest_unauthorized',
                __('Invalid or inactive API Key.', 'whois-crm'),
                ['status' => 401]
            );
        }

        $customer_id = (int) $key_record->customer_id;

        // 3. Verify Customer exists and is active
        $customer = (new Customer())->find($customer_id);
        if (!$customer || !$customer->is_active) {
            return new \WP_Error(
                'rest_forbidden',
                __('Customer account is suspended or blocked.', 'whois-crm'),
                ['status' => 403]
            );
        }

        // 4. Verify Active Enterprise Subscription
        if (!$this->has_active_enterprise($customer_id)) {
            return new \WP_Error(
                'rest_forbidden',
                __('API access requires an active Enterprise subscription plan.', 'whois-crm'),
                ['status' => 403]
            );
        }

        // 5. Enforce Daily API Request Limits
        if (!$api_key_model->is_within_rate_limit($key_record)) {
            return new \WP_Error(
                'rest_too_many_requests',
                __('Daily API request limit quota exceeded.', 'whois-crm'),
                ['status' => 429]
            );
        }

        // 6. Record request (increment requests_today count)
        $api_key_model->record_request((int) $key_record->id);

        // Bind customer and user context for controllers
        $request->set_param('authenticated_customer_id', $customer_id);
        $request->set_param('authenticated_user_id', (int) $customer->user_id);

        return true;
    }

    /**
     * Check if a customer has an active enterprise subscription.
     */
    private function has_active_enterprise(int $customer_id): bool
    {
        $subscriptions = (new Subscription())->get_active_for_customer($customer_id);
        foreach ($subscriptions as $sub) {
            if ($sub->service_type === 'enterprise') {
                return true;
            }
        }
        return false;
    }
}
