<?php

declare(strict_types=1);

namespace WhoisCRM\Upload;

use WhoisCRM\Database\Models\DataFile;
use WhoisCRM\Database\Models\ActivityLog;

/**
 * Manages data file admin operations (delete, toggle active, bulk delete).
 *
 * Registered as:
 *  admin_post_whoiscrm_delete_data_file — single file delete
 *  admin_post_whoiscrm_bulk_data_files  — bulk actions
 *  wp_ajax_whoiscrm_toggle_data_file    — AJAX active toggle
 */
class FileManager
{
    public function __construct()
    {
        add_action('admin_post_whoiscrm_delete_data_file', [$this, 'handle_delete']);
        add_action('admin_post_whoiscrm_bulk_data_files',  [$this, 'handle_bulk']);
        add_action('wp_ajax_whoiscrm_toggle_data_file',    [$this, 'handle_toggle']);
    }

    // ─── Delete single file ───────────────────────────────────────────────

    public function handle_delete(): void
    {
        $file_id = (int) ($_GET['file_id'] ?? 0);

        if ($file_id < 1) {
            wp_safe_redirect(admin_url('admin.php?page=whoiscrm-data-files'));
            exit;
        }

        // Verify per-row nonce (generated with wp_nonce_url in the template)
        check_admin_referer('whoiscrm_delete_file_' . $file_id);

        if (!current_user_can('whoiscrm_upload_data')) {
            wp_die(__('Unauthorized.', 'whois-crm'));
        }

        $this->delete_file($file_id);

        wp_safe_redirect(add_query_arg(
            ['page' => 'whoiscrm-data-files', 'deleted' => 1],
            admin_url('admin.php')
        ));
        exit;
    }

    // ─── Bulk actions ─────────────────────────────────────────────────────

    public function handle_bulk(): void
    {
        check_admin_referer('whoiscrm_data_file_action');

        if (!current_user_can('whoiscrm_upload_data')) {
            wp_die(__('Unauthorized.', 'whois-crm'));
        }

        $bulk_action = sanitize_key($_POST['bulk_action'] ?? '');
        $file_ids    = array_map('absint', (array) ($_POST['file_ids'] ?? []));

        if (empty($file_ids) || empty($bulk_action)) {
            wp_safe_redirect(admin_url('admin.php?page=whoiscrm-data-files'));
            exit;
        }

        $count = 0;

        foreach ($file_ids as $file_id) {
            switch ($bulk_action) {
                case 'delete':
                    $this->delete_file($file_id);
                    $count++;
                    break;

                case 'deactivate':
                    (new DataFile())->update($file_id, ['is_active' => 0]);
                    $count++;
                    break;

                case 'activate':
                    (new DataFile())->update($file_id, ['is_active' => 1]);
                    $count++;
                    break;
            }
        }

        wp_safe_redirect(add_query_arg(
            ['page' => 'whoiscrm-data-files', 'bulk_done' => $count, 'bulk_action' => $bulk_action],
            admin_url('admin.php')
        ));
        exit;
    }

    // ─── AJAX toggle active ───────────────────────────────────────────────

    public function handle_toggle(): void
    {
        check_ajax_referer('whoiscrm_admin_nonce', 'nonce');

        if (!current_user_can('whoiscrm_upload_data')) {
            wp_send_json_error(['message' => __('Unauthorized.', 'whois-crm')]);
        }

        $file_id = (int) ($_POST['file_id'] ?? 0);
        $model   = new DataFile();
        $file    = $model->find($file_id);

        if (!$file) {
            wp_send_json_error(['message' => __('File not found.', 'whois-crm')]);
        }

        $new_state = $file->is_active ? 0 : 1;
        $model->update($file_id, ['is_active' => $new_state]);

        wp_send_json_success([
            'is_active' => $new_state,
            'label'     => $new_state
                ? __('Active', 'whois-crm')
                : __('Inactive', 'whois-crm'),
        ]);
    }

    // ─── Private helpers ──────────────────────────────────────────────────

    /**
     * Delete a data file from disk and remove its DB record.
     */
    private function delete_file(int $file_id): void
    {
        $model = new DataFile();
        $file  = $model->find($file_id);

        if (!$file) {
            return;
        }

        // Remove physical file
        $abs_path = WHOISCRM_DATA_DIR . ltrim($file->file_path, '/');

        if (file_exists($abs_path) && is_file($abs_path)) {
            @unlink($abs_path); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
        }

        // Soft-delete the DB record (sets is_active = 0 and deleted_at)
        $model->soft_delete($file_id);

        (new ActivityLog())->log(
            ActivityLog::ACTION_ADMIN_ACTION,
            "Data file deleted: {$file->filename} (ID #{$file_id})",
            ['file_path' => $file->file_path],
            ActivityLog::SEVERITY_WARNING,
            get_current_user_id()
        );
    }
}
