<?php

declare(strict_types=1);

namespace WhoisCRM\Admin\Pages;

use WhoisCRM\Database\Models\DataFile;

/**
 * Data Files list admin page (stub — full implementation in Phase 6).
 */
class DataFilesPage extends BasePage
{
    protected static string $required_cap = 'whoiscrm_upload_data';

    protected function display(): void
    {
        $page = $this->get_current_page();

        $filters = [];
        if (!empty($_GET['country_code'])) {
            $filters['country_code'] = sanitize_text_field(wp_unslash($_GET['country_code']));
        }
        if (!empty($_GET['service_type'])) {
            $filters['service_type'] = sanitize_key($_GET['service_type']);
        }
        if (!empty($_GET['date_from'])) {
            $filters['date_from'] = sanitize_text_field(wp_unslash($_GET['date_from']));
        }
        if (!empty($_GET['date_to'])) {
            $filters['date_to'] = sanitize_text_field(wp_unslash($_GET['date_to']));
        }

        $result = (new DataFile())->get_admin_list($filters, $page, 30);

        $this->page_header(
            __('Data Files', 'whois-crm'),
            '',
            [['label' => __('📤 Upload Data', 'whois-crm'), 'url' => admin_url('admin.php?page=whoiscrm-upload')]]
        );

        $this->render_template('data-files/list', [
            'rows'         => $result['rows'],
            'total'        => $result['total'],
            'current_page' => $page,
            'per_page'     => 30,
            'filters'      => $filters,
            'pagination'   => $this->pagination_html($result['total'], 30, $page),
            'nonce'        => wp_create_nonce('whoiscrm_data_file_action'),
            'admin_nonce'  => wp_create_nonce('whoiscrm_admin_nonce'),
        ]);
        $this->page_footer();
    }
}
