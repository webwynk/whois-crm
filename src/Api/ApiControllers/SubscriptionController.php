<?php

declare(strict_types=1);

namespace WhoisCRM\Api\ApiControllers;

use WhoisCRM\Database\Models\Subscription;
use WhoisCRM\Database\Models\Package;

/**
 * REST API Subscription Controller.
 *
 * Exposes endpoints for checking active customer subscriptions, starts/expiry details,
 * and package metadata.
 */
class SubscriptionController
{
    /**
     * GET /wp-json/whoiscrm/v1/api/subscription
     *
     * Returns the subscription status and details for the authenticated customer.
     */
    public function status(\WP_REST_Request $request): \WP_REST_Response
    {
        $customer_id = (int) $request->get_param('authenticated_customer_id');

        $sub_model = new Subscription();
        $rows      = $sub_model->get_active_for_customer($customer_id);

        $subscriptions = [];

        foreach ($rows as $sub) {
            $package = (new Package())->find((int) $sub->package_id);
            
            global $wpdb;
            $pricing = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT price, currency, billing_cycle 
                     FROM {$wpdb->prefix}whoiscrm_package_pricing 
                     WHERE id = %d",
                    $sub->package_pricing_id
                )
            );

            $subscriptions[] = [
                'subscription_id'    => (int) $sub->id,
                'package_name'       => $package ? $package->name : 'Subscription',
                'package_slug'       => $package ? $package->slug : '',
                'service_type'       => $package ? $package->service_type : '',
                'billing_cycle'      => $pricing ? $pricing->billing_cycle : 'monthly',
                'price'              => $pricing ? (float)$pricing->price : 0.0,
                'currency'           => $pricing ? $pricing->currency : 'USD',
                'starts_at'          => $sub->starts_at,
                'expires_at'         => $sub->expires_at,
                'status'             => $sub->status,
                'stripe_subscription_id' => $sub->stripe_subscription_id,
            ];
        }

        return new \WP_REST_Response([
            'customer_id'   => $customer_id,
            'status'        => !empty($subscriptions) ? 'active' : 'inactive',
            'subscriptions' => $subscriptions,
        ], 200);
    }
}
