<?php

declare(strict_types=1);

namespace WhoisCRM\Api\ApiControllers;

use WhoisCRM\Database\Models\DataFile;
use WhoisCRM\Database\Models\Download;
use WhoisCRM\Database\Models\ActivityLog;
use WhoisCRM\Subscription\AccessControl;
use WhoisCRM\Portal\DownloadHandler;

/**
 * REST API Data Controller.
 *
 * Exposes endpoints for listing countries, listing files,
 * and downloading database dumps for premium/Enterprise API keys.
 */
class DataController
{
    /**
     * GET /wp-json/whoiscrm/v1/api/countries
     *
     * List all country codes that have active data feeds.
     */
    public function list_countries(\WP_REST_Request $request): \WP_REST_Response
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'whoiscrm_data_files';
        
        // Find distinct active country codes
        $codes = $wpdb->get_col(
            "SELECT DISTINCT country_code 
             FROM {$table_name} 
             WHERE is_active = 1 
               AND country_code IS NOT NULL 
               AND country_code != ''"
        );

        $country_names = \WhoisCRM\Helpers\CountryList::all();
        $countries     = [];

        foreach ($codes as $code) {
            $code = strtoupper($code);
            $countries[] = [
                'code' => $code,
                'name' => $country_names[$code] ?? $code,
            ];
        }

        return new \WP_REST_Response($countries, 200);
    }

    /**
     * GET /wp-json/whoiscrm/v1/api/files
     *
     * List paginated data files that the authenticated customer has permission to access.
     */
    public function list_files(\WP_REST_Request $request): \WP_REST_Response
    {
        $customer_id = (int) $request->get_param('authenticated_customer_id');

        $filters = [
            'country_code' => $request->get_param('country'),
            'service_type' => $request->get_param('service_type'),
            'date_from'    => $request->get_param('date_from'),
            'date_to'      => $request->get_param('date_to'),
        ];

        $page     = (int) $request->get_param('page');
        $per_page = (int) $request->get_param('per_page');

        if ($page < 1) {
            $page = 1;
        }
        if ($per_page < 1 || $per_page > 100) {
            $per_page = 50; // Bound maximum limits
        }

        $file_model = new DataFile();
        $result     = $file_model->get_accessible_for_customer($customer_id, $filters, $page, $per_page);

        // Sanitize output rows: only return safe metadata fields (hide full server file path)
        $files = array_map(function ($row) {
            return [
                'id'                => (int) $row->id,
                'original_filename' => $row->original_filename,
                'service_type'      => $row->service_type,
                'country_code'      => $row->country_code,
                'tld'               => $row->tld,
                'file_size'         => (int) $row->file_size,
                'file_type'         => $row->file_type,
                'data_date'         => $row->data_date,
                'download_url'      => rest_url('whoiscrm/v1/api/files/' . $row->id . '/download'),
            ];
        }, $result['rows']);

        return new \WP_REST_Response([
            'files'       => $files,
            'total'       => $result['total'],
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => (int) ceil($result['total'] / $per_page),
        ], 200);
    }

    /**
     * GET /wp-json/whoiscrm/v1/api/files/{file_id}/download
     *
     * Directly stream the requested file archives to the API client.
     */
    public function download_file(\WP_REST_Request $request)
    {
        $file_id     = (int) $request->get_param('file_id');
        $user_id     = (int) $request->get_param('authenticated_user_id');
        $customer_id = (int) $request->get_param('authenticated_customer_id');

        if ($file_id < 1) {
            return new \WP_Error('rest_bad_request', __('Invalid file ID.', 'whois-crm'), ['status' => 400]);
        }

        // 1. Verify subscription privileges
        $access = new AccessControl();
        if (!$access->can_download($user_id, $file_id)) {
            return new \WP_Error('rest_forbidden', __('Your active subscriptions do not cover this data feed.', 'whois-crm'), ['status' => 403]);
        }

        $file_model = new DataFile();
        $file       = $file_model->find($file_id);

        if (!$file || !$file->is_active) {
            return new \WP_Error('rest_not_found', __('Requested feed file is inactive or missing.', 'whois-crm'), ['status' => 404]);
        }

        $abs_path = $file_model->get_absolute_path($file);

        if (!file_exists($abs_path) || !is_file($abs_path)) {
            return new \WP_Error('rest_not_found', __('Physical database archive is missing on server storage.', 'whois-crm'), ['status' => 404]);
        }

        // 2. Log download action
        (new Download())->insert([
            'customer_id'     => $customer_id,
            'data_file_id'    => $file_id,
            'download_source' => 'api',
            'downloaded_at'   => current_time('mysql', true),
            'ip_address'      => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent'      => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ]);

        (new ActivityLog())->log_download($user_id, $file_id, $file->original_filename);

        // 3. Stream file
        $handler = new DownloadHandler();
        $handler->stream_file($abs_path, $file->original_filename, $file->mime_type);
        exit;
    }
}
