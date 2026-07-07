<?php

declare(strict_types=1);

namespace WhoisCRM\Api;

use WhoisCRM\Stripe\StripeWebhookHandler;

/**
 * REST API Router.
 *
 * Registers all REST routes under the `whoiscrm/v1` namespace.
 *
 * Routes:
 *  POST /wp-json/whoiscrm/v1/webhooks/stripe  — Stripe webhook receiver
 *  (Premium API routes added in Phase 11)
 */
class ApiRouter
{
    public function __construct()
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void
    {
        $namespace = 'whoiscrm/v1';

        // ── Stripe Webhook ────────────────────────────────────────────────
        register_rest_route($namespace, '/webhooks/stripe', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [(new StripeWebhookHandler()), 'handle'],
            'permission_callback' => '__return_true', // Stripe signs requests — we verify inside handler
        ]);

        // ── Secure Tokenized Downloads ────────────────────────────────────
        register_rest_route($namespace, '/download/(?P<id>\d+)', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [new \WhoisCRM\Portal\DownloadHandler(), 'handle_rest_download'],
            'permission_callback' => '__return_true', // Authenticated inside handler
        ]);

        // ── API Key Authenticated Routes ──────────────────────────────────
        $auth = new ApiAuthMiddleware();

        register_rest_route($namespace, '/api/countries', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [new ApiControllers\DataController(), 'list_countries'],
            'permission_callback' => [$auth, 'authenticate'],
        ]);

        register_rest_route($namespace, '/api/files', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [new ApiControllers\DataController(), 'list_files'],
            'permission_callback' => [$auth, 'authenticate'],
            'args'                => [
                'country'      => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'date_from'    => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'date_to'      => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'service_type' => ['type' => 'string', 'sanitize_callback' => 'sanitize_text_field'],
                'page'         => ['type' => 'integer', 'default' => 1],
                'per_page'     => ['type' => 'integer', 'default' => 50],
            ],
        ]);

        register_rest_route($namespace, '/api/files/(?P<file_id>\d+)/download', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [new ApiControllers\DataController(), 'download_file'],
            'permission_callback' => [$auth, 'authenticate'],
        ]);

        register_rest_route($namespace, '/api/subscription', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [new ApiControllers\SubscriptionController(), 'status'],
            'permission_callback' => [$auth, 'authenticate'],
        ]);
    }
}
