<?php

declare(strict_types=1);

namespace WhoisCRM\Admin\Pages;

use WhoisCRM\Database\Models\DataFile;

/**
 * Upload Data admin page (stub — full implementation in Phase 6).
 */
class UploadPage extends BasePage
{
    protected static string $required_cap = 'whoiscrm_upload_data';

    protected function display(): void
    {
        $this->page_header(__('Upload Data', 'whois-crm'));
        $this->render_template('upload/form', [
            'nonce'          => wp_create_nonce('whoiscrm_upload_nonce'),
            'max_size_mb'    => (int) get_option('whoiscrm_max_upload_size', 512),
            'allowed_types'  => ['csv', 'xlsx', 'zip', 'json'],
        ]);
        $this->page_footer();
    }
}
