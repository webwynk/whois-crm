<?php

declare(strict_types=1);

namespace WhoisCRM\Portal;

use WhoisCRM\Database\Models\Customer;
use WhoisCRM\Database\Models\DataFile;
use WhoisCRM\Database\Models\Download;
use WhoisCRM\Database\Models\ActivityLog;
use WhoisCRM\Subscription\AccessControl;

/**
 * Secure Download serving handler.
 *
 * Intercepts download requests, performs security checks,
 * enforces daily download rate limits, logs download statistics,
 * and streams the file from the protected /whois-data/ directory.
 *
 * Hooks:
 *  admin_post_whoiscrm_download_data_file - Direct portal download links
 */
class DownloadHandler
{
    private AccessControl $access;

    public function __construct()
    {
        $this->access = new AccessControl();

        // Register standard WordPress post callback for direct downloads
        add_action('admin_post_whoiscrm_download_data_file', [$this, 'handle_portal_download']);

        // Intercept front-end invoice PDF downloads
        add_action('init', [$this, 'intercept_invoice_download']);
    }

    // ─── Portal Download Handler ──────────────────────────────────────────

    /**
     * Handle download clicks from the customer portal.
     *
     * Validates session, nonces, daily rate limit constraints,
     * and subscription access permissions before streaming the file.
     */
    public function handle_portal_download(): void
    {
        if (!is_user_logged_in()) {
            wp_die(__('You must be signed in to download files.', 'whois-crm'), __('Access Denied', 'whois-crm'), 401);
        }

        $file_id = (int) ($_GET['file_id'] ?? 0);

        if ($file_id < 1) {
            wp_die(__('Invalid file identifier.', 'whois-crm'), __('Bad Request', 'whois-crm'), 400);
        }

        // Verify the secure URL action nonce
        if (!check_admin_referer('whoiscrm_download_file_' . $file_id)) {
            wp_die(__('Secure validation check failed.', 'whois-crm'), __('Access Denied', 'whois-crm'), 403);
        }

        $user_id = get_current_user_id();
        $customer = (new Customer())->find_by_user_id($user_id);

        if (!$customer || !$customer->is_active) {
            wp_die(__('Your customer profile is inactive or blocked.', 'whois-crm'), __('Access Denied', 'whois-crm'), 403);
        }

        $customer_id = (int) $customer->id;

        // ── Check Daily Download Rate Limits ─────────────────────────
        $limit      = (int) get_option('whoiscrm_download_rate_limit', 50);
        $used_today = (new Download())->get_count_24h($customer_id);

        if ($used_today >= $limit) {
            wp_die(
                sprintf(
                    /* translators: %d: download limit count */
                    esc_html__('Daily download quota exceeded. Your limit is %d downloads per 24 hours.', 'whois-crm'),
                    $limit
                ),
                __('Rate Limit Exceeded', 'whois-crm'),
                429
            );
        }

        // ── Check Subscription Permissions ───────────────────────────
        if (!$this->access->can_download($user_id, $file_id)) {
            wp_die(__('Your active subscription plans do not cover this data file.', 'whois-crm'), __('Access Denied', 'whois-crm'), 403);
        }

        $file_model = new DataFile();
        $file       = $file_model->find($file_id);

        if (!$file || !$file->is_active) {
            wp_die(__('Requested file is missing or has been deactivated.', 'whois-crm'), __('Not Found', 'whois-crm'), 404);
        }

        $abs_path = $file_model->get_absolute_path($file);

        // ── Record download in database ──────────────────────────────
        (new Download())->insert([
            'customer_id'     => $customer_id,
            'data_file_id'    => $file_id,
            'download_source' => 'portal',
            'downloaded_at'   => current_time('mysql', true),
            'ip_address'      => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent'      => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ]);

        (new ActivityLog())->log_download($user_id, $file_id, $file->original_filename);

        // ── Stream the file ──────────────────────────────────────────
        $this->stream_file($abs_path, $file->original_filename, $file->mime_type);
    }

    // ─── REST Download Handler ────────────────────────────────────────────

    /**
     * REST endpoint callback: GET /wp-json/whoiscrm/v1/download/{id}
     *
     * Validates tokenized queries (expires + token signature) or REST API Key authentication.
     */
    public function handle_rest_download(\WP_REST_Request $request): void
    {
        $file_id = (int) $request->get_param('id');
        $token   = sanitize_text_field($request->get_param('token') ?? '');
        $expires = (int) $request->get_param('expires');
        $user_id = (int) $request->get_param('user_id');

        // Verify token authenticity and expiry (browser redirect downloads)
        if (!$this->verify_download_token($token, $user_id, $file_id, $expires)) {
            wp_send_json_error(['message' => __('Invalid or expired download token.', 'whois-crm')], 403);
            return;
        }

        $customer = (new Customer())->find_by_user_id($user_id);
        if (!$customer || !$customer->is_active) {
            wp_send_json_error(['message' => __('Customer account suspended.', 'whois-crm')], 403);
            return;
        }

        $customer_id = (int) $customer->id;

        // Daily rate limit validation
        $limit      = (int) get_option('whoiscrm_download_rate_limit', 50);
        $used_today = (new Download())->get_count_24h($customer_id);

        if ($used_today >= $limit) {
            wp_send_json_error(['message' => __('Daily download quota exceeded.', 'whois-crm')], 429);
            return;
        }

        $file_model = new DataFile();
        $file       = $file_model->find($file_id);

        if (!$file || !$file->is_active) {
            wp_send_json_error(['message' => __('Data file not found.', 'whois-crm')], 404);
            return;
        }

        $abs_path = $file_model->get_absolute_path($file);

        // Record the download event
        (new Download())->insert([
            'customer_id'     => $customer_id,
            'data_file_id'    => $file_id,
            'download_source' => 'api',
            'downloaded_at'   => current_time('mysql', true),
            'ip_address'      => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent'      => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ]);

        (new ActivityLog())->log_download($user_id, $file_id, $file->original_filename);

        // Stream the file
        $this->stream_file($abs_path, $file->original_filename, $file->mime_type);
    }

    // ─── Token Signatures ──────────────────────────────────────────────────

    /**
     * Generate a secure download token link.
     */
    public function generate_download_token(int $user_id, int $file_id): array
    {
        $expires = time() + 300; // 5 minutes
        $secret  = wp_salt('auth');
        $data    = "{$user_id}:{$file_id}:{$expires}";
        $token   = hash_hmac('sha256', $data, $secret);

        return [
            'token'   => $token,
            'expires' => $expires,
        ];
    }

    /**
     * Verify a secure signed token.
     */
    public function verify_download_token(string $token, int $user_id, int $file_id, int $expires): bool
    {
        if (time() > $expires) {
            return false; // Expired
        }

        $secret   = wp_salt('auth');
        $data     = "{$user_id}:{$file_id}:{$expires}";
        $expected = hash_hmac('sha256', $data, $secret);

        return hash_equals($expected, $token);
    }

    // ─── Direct File Streaming ─────────────────────────────────────────────

    /**
     * Stream a physical file chunk by chunk to prevent PHP memory exhaustion.
     */
    /**
     * Stream a physical file chunk by chunk to prevent PHP memory exhaustion.
     */
    public function stream_file(string $abs_path, string $original_filename, string $mime_type): void
    {
        if (!file_exists($abs_path) || !is_file($abs_path)) {
            wp_die(__('Physical data archive is missing on server storage.', 'whois-crm'), __('Not Found', 'whois-crm'), 404);
        }

        $file_size = (int) filesize($abs_path);

        // Reset output buffer to prevent output leakage
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Set attachment download headers
        header('Content-Description: File Transfer');
        header('Content-Type: ' . ($mime_type ?: 'application/octet-stream'));
        header('Content-Disposition: attachment; filename="' . $original_filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . $file_size);

        // Serve using X-Sendfile if enabled in settings
        if (get_option('whoiscrm_enable_xsendfile', 0)) {
            header('X-Sendfile: ' . $abs_path);
            exit;
        }

        // Standard chunked file reads
        $handle = fopen($abs_path, 'rb');
        if ($handle === false) {
            wp_die(__('Could not read physical file.', 'whois-crm'), __('Server Error', 'whois-crm'), 500);
        }

        while (!feof($handle)) {
            $buffer = fread($handle, 8192); // 8KB chunks
            echo $buffer; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            flush();
        }

        fclose($handle);
        exit;
    }

    /**
     * Intercept invoice PDF download query params.
     */
    public function intercept_invoice_download(): void
    {
        if (empty($_GET['whoiscrm_action']) || $_GET['whoiscrm_action'] !== 'download_invoice') {
            return;
        }

        if (!is_user_logged_in()) {
            wp_die(__('You must be logged in to access invoices.', 'whois-crm'), __('Access Denied', 'whois-crm'), 401);
        }

        $invoice_id = (int) ($_GET['invoice_id'] ?? 0);
        if ($invoice_id < 1) {
            wp_die(__('Invalid invoice identifier.', 'whois-crm'), __('Bad Request', 'whois-crm'), 400);
        }

        // Verify the secure download nonce
        if (!check_admin_referer('whoiscrm_invoice_' . $invoice_id)) {
            wp_die(__('Security validation check failed.', 'whois-crm'), __('Access Denied', 'whois-crm'), 403);
        }

        $invoice_model = new \WhoisCRM\Database\Models\Invoice();
        $invoice       = $invoice_model->find($invoice_id);

        if (!$invoice) {
            wp_die(__('Invoice record not found.', 'whois-crm'), __('Not Found', 'whois-crm'), 404);
        }

        // Enforce horizontal privilege security:
        // Admin (whoiscrm_view_payments) can view all invoices, customer only their own.
        if (!current_user_can('whoiscrm_view_payments')) {
            $customer = (new Customer())->find_by_user_id(get_current_user_id());
            if (!$customer || (int) $customer->id !== (int) $invoice->customer_id) {
                wp_die(__('You do not have permission to access this invoice.', 'whois-crm'), __('Access Denied', 'whois-crm'), 403);
            }
        }

        $abs_path = WHOISCRM_DATA_DIR . $invoice->pdf_path;

        if (empty($invoice->pdf_path) || !file_exists($abs_path) || !is_file($abs_path)) {
            wp_die(__('Invoice PDF document is missing on server storage.', 'whois-crm'), __('Not Found', 'whois-crm'), 404);
        }

        // Stream the PDF
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . esc_attr($invoice->invoice_number) . '.pdf"');
        header('Content-Length: ' . filesize($abs_path));
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');

        readfile($abs_path);
        exit;
    }
}
