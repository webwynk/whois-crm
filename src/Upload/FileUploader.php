<?php

declare(strict_types=1);

namespace WhoisCRM\Upload;

use WhoisCRM\Database\Models\DataFile;
use WhoisCRM\Database\Models\ActivityLog;

/**
 * Handles admin data file uploads.
 *
 * Registered as admin_post action (standard form POST with nonce).
 * Accepts multiple files per upload, validates extension + MIME + size,
 * places them in the structured data directory, and records each file
 * in the whoiscrm_data_files table with a SHA-256 checksum.
 *
 * Storage path layout:
 *   {WHOISCRM_DATA_DIR}/{service_type}/{COUNTRY_CODE}/{YYYY-MM-DD}/{filename}
 */
class FileUploader
{
    /** Allowed file extensions. */
    private const ALLOWED_EXTENSIONS = ['csv', 'xlsx', 'xls', 'zip', 'json', 'txt', 'pdf', 'ods'];

    /** Allowed MIME types mapped to extensions. */
    private const ALLOWED_MIMES = [
        // CSV
        'text/csv'                                                          => 'csv',
        'text/x-csv'                                                        => 'csv',
        'application/csv'                                                   => 'csv',
        'application/x-csv'                                                 => 'csv',
        'text/comma-separated-values'                                       => 'csv',
        // Excel (XLSX)
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        // Excel (XLS) / generic MS Office
        'application/vnd.ms-excel'                                          => 'xls',
        'application/vnd.ms-office'                                         => 'xls',
        'application/xls'                                                   => 'xls',
        'application/x-xls'                                                 => 'xls',
        // ZIP
        'application/zip'                                                   => 'zip',
        'application/x-zip-compressed'                                      => 'zip',
        'application/x-zip'                                                 => 'zip',
        'application/x-compress'                                            => 'zip',
        'application/x-compressed'                                          => 'zip',
        'application/zip-compressed'                                        => 'zip',
        'application/x-zip-archive'                                         => 'zip',
        'multipart/x-zip'                                                   => 'zip',
        // JSON
        'application/json'                                                  => 'json',
        'text/json'                                                         => 'json',
        // Plain text (TXT and CSV both report text/plain)
        'text/plain'                                                        => 'txt',
        // PDF
        'application/pdf'                                                   => 'pdf',
        // OpenDocument Spreadsheet (ODS — Google Sheets export)
        'application/vnd.oasis.opendocument.spreadsheet'                    => 'ods',
        // Generic binary (ZIP, XLS, ODS may report this)
        'application/octet-stream'                                          => 'zip',
    ];

    public function __construct()
    {
        add_action('admin_post_whoiscrm_upload_files', [$this, 'handle_upload']);
    }

    /**
     * Process the file upload form submission.
     *
     * Validates nonce, capability, fields, and each file.
     * Moves valid files to structured data directory and records them in DB.
     */
    public function handle_upload(): void
    {
        check_admin_referer('whoiscrm_upload_nonce', 'whoiscrm_upload_nonce');

        if (!current_user_can('whoiscrm_upload_data')) {
            wp_die(__('Unauthorized. You do not have permission to upload data files.', 'whois-crm'));
        }

        // ── Collect & sanitize POST fields ───────────────────────────
        $service_type = sanitize_key(wp_unslash($_POST['service_type'] ?? ''));
        $country_code = strtoupper(sanitize_text_field(wp_unslash($_POST['country_code'] ?? '')));
        $country_name = sanitize_text_field(wp_unslash($_POST['country_name'] ?? ''));
        $tld          = sanitize_text_field(wp_unslash($_POST['tld'] ?? ''));
        $data_date    = sanitize_text_field(wp_unslash($_POST['data_date'] ?? ''));
        $notes        = sanitize_textarea_field(wp_unslash($_POST['notes'] ?? ''));

        // ── Field validation ─────────────────────────────────────────
        if (empty($service_type)) {
            $this->redirect_error('Service type is required.');
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_date)) {
            $this->redirect_error('Invalid date format. Use YYYY-MM-DD.');
        }

        // ── Prepare storage directory ────────────────────────────────
        $rel_dir = $this->build_rel_path($service_type, $country_code, $data_date);
        $abs_dir = WHOISCRM_DATA_DIR . $rel_dir;

        if (!wp_mkdir_p($abs_dir)) {
            $this->redirect_error('Failed to create storage directory. Check server permissions.');
        }

        // Ensure directory is protected from direct web access
        $this->ensure_htaccess($abs_dir);

        // ── Process files ────────────────────────────────────────────
        $files = $_FILES['whoiscrm_files'] ?? null;

        if (empty($files) || empty($files['name'])) {
            $this->redirect_error('No files were selected for upload.');
        }

        $max_size = ((int) get_option('whoiscrm_max_upload_size', 512)) * 1024 * 1024;

        // Normalise single vs. multiple files into arrays
        $file_names   = (array) $files['name'];
        $file_tmps    = (array) $files['tmp_name'];
        $file_sizes   = (array) $files['size'];
        $file_errors  = (array) $files['error'];
        $file_types   = (array) $files['type'];

        $uploaded     = 0;
        $skipped      = [];
        $model        = new DataFile();

        foreach ($file_names as $i => $original_name) {
            if ($file_errors[$i] !== UPLOAD_ERR_OK) {
                $skipped[] = $original_name . ' (' . $this->upload_error_message($file_errors[$i]) . ')';
                continue;
            }

            $size = (int) $file_sizes[$i];
            $tmp  = $file_tmps[$i];
            $ext  = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

            // Extension check
            if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
                $skipped[] = $original_name . ' (unsupported type)';
                continue;
            }

            // Size check
            if ($size > $max_size) {
                $skipped[] = $original_name . ' (exceeds ' . ($max_size / 1024 / 1024) . ' MB limit)';
                continue;
            }

            // MIME check using finfo for accuracy
            $real_mime = $this->get_real_mime($tmp);
            if ($real_mime && !array_key_exists($real_mime, self::ALLOWED_MIMES)) {
                $skipped[] = $original_name . ' (invalid MIME type)';
                continue;
            }

            // Sanitize filename
            $safe_name = sanitize_file_name($original_name);
            $dest      = $abs_dir . '/' . $safe_name;

            // Avoid overwriting — prefix with microtime
            if (file_exists($dest)) {
                $safe_name = substr(str_replace('.', '', (string) microtime(true)), -8) . '-' . $safe_name;
                $dest      = $abs_dir . '/' . $safe_name;
            }

            if (!move_uploaded_file($tmp, $dest)) {
                $skipped[] = $original_name . ' (move failed)';
                continue;
            }

            // ── Record in database ────────────────────────────────────
            $inserted_id = $model->insert([
                'filename'          => $safe_name,
                'original_filename' => $original_name,
                'file_path'         => $rel_dir . '/' . $safe_name,
                'file_size'         => $size,
                'file_type'         => $ext,
                'mime_type'         => $real_mime ?: ($file_types[$i] ?? ''),
                'country_code'      => $country_code,
                'country_name'      => $country_name,
                'tld'               => $tld,
                'data_date'         => $data_date,
                'service_type'      => $service_type,
                'notes'             => $notes,
                'checksum'          => hash_file('sha256', $dest) ?: null,
                'uploaded_by'       => get_current_user_id(),
                'is_active'         => 1,
            ]);

            if ($inserted_id === false) {
                @unlink($dest);
                $db_error = $model->last_error();
                $skipped[] = $original_name . ' (database error: ' . ($db_error ?: 'unknown database error') . ')';
                error_log("WHOIS CRM Upload DB Error: " . $db_error);
                continue;
            }

            $uploaded++;
        }

        // ── Activity log ─────────────────────────────────────────────
        (new ActivityLog())->log(
            ActivityLog::ACTION_FILE_UPLOAD,
            "Uploaded {$uploaded} file(s) for {$country_code} / {$data_date} [{$service_type}]",
            [
                'uploaded' => $uploaded,
                'skipped'  => count($skipped),
                'skipped_names' => $skipped,
            ],
            ActivityLog::SEVERITY_INFO,
            get_current_user_id()
        );

        // ── Redirect with result ──────────────────────────────────────
        $query = [
            'page'     => 'whoiscrm-data-files',
            'uploaded' => $uploaded,
        ];

        if (!empty($skipped)) {
            $query['skipped'] = count($skipped);
            set_transient('whoiscrm_upload_skipped_' . get_current_user_id(), $skipped, 60);
        }

        $redirect_url = add_query_arg($query, admin_url('admin.php'));

        // ── AJAX: return JSON instead of redirecting ─────────────────
        if ($this->is_ajax_request()) {
            wp_send_json_success([
                'uploaded'        => $uploaded,
                'skipped'         => count($skipped),
                'skipped_details' => $skipped,
                'redirect'        => $redirect_url,
            ]);
        }

        wp_safe_redirect($redirect_url);
        exit;
    }

    /**
     * Build relative storage path.
     * Example: whois_history/US/2026-07-07
     */
    private function build_rel_path(string $service_type, string $country_code, string $data_date): string
    {
        $parts = [$service_type];

        if (!empty($country_code)) {
            $parts[] = $country_code;
        }

        $parts[] = $data_date;

        return implode('/', $parts);
    }

    /**
     * Write an .htaccess to the data directory (belt + suspenders — the
     * main protection comes from the root whois-data .htaccess).
     */
    private function ensure_htaccess(string $dir): void
    {
        $htaccess = $dir . '/.htaccess';

        if (!file_exists($htaccess)) {
            file_put_contents(
                $htaccess,
                "Options -Indexes\nDeny from all\n"
            );
        }
    }

    /**
     * Get the real MIME type of a file using finfo.
     */
    private function get_real_mime(string $path): string
    {
        if (!file_exists($path)) {
            return '';
        }

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $path);
            finfo_close($finfo);

            return (string) $mime;
        }

        // Fallback: mime_content_type
        if (function_exists('mime_content_type')) {
            return (string) mime_content_type($path);
        }

        return '';
    }

    /**
     * Human-readable PHP upload error message.
     */
    private function upload_error_message(int $code): string
    {
        $messages = [
            UPLOAD_ERR_INI_SIZE   => 'file exceeds server upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE  => 'file exceeds form MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL    => 'file was only partially uploaded',
            UPLOAD_ERR_NO_FILE    => 'no file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'temporary folder missing',
            UPLOAD_ERR_CANT_WRITE => 'failed to write to disk',
            UPLOAD_ERR_EXTENSION  => 'upload blocked by PHP extension',
        ];

        return $messages[$code] ?? "upload error code {$code}";
    }

    /**
     * Redirect back to the upload page with an error message.
     * Returns JSON for AJAX requests instead of redirecting.
     */
    private function redirect_error(string $message): void
    {
        if ($this->is_ajax_request()) {
            wp_send_json_error(['message' => $message]);
        }

        wp_safe_redirect(add_query_arg(
            ['page' => 'whoiscrm-upload', 'upload_error' => urlencode($message)],
            admin_url('admin.php')
        ));
        exit;
    }

    /**
     * Check if the current request is an XMLHttpRequest (AJAX).
     */
    private function is_ajax_request(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}
