<?php

declare(strict_types=1);

namespace WhoisCRM\Invoice;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * PDF Renderer using Dompdf.
 *
 * Renders HTML templates into secure PDF documents and saves them to server storage.
 */
class PdfRenderer
{
    /**
     * Render HTML to a PDF file on disk.
     *
     * @param string $html     HTML content of the invoice.
     * @param string $abs_path Absolute path to save the PDF.
     * @return bool            True on success, false on failure.
     */
    public function render_to_file(string $html, string $abs_path): bool
    {
        try {
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true); // Enable downloading external assets like logos
            $options->set('defaultFont', 'Helvetica');

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            
            // Set A4 paper format in portrait orientation
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $output = $dompdf->output();

            if (empty($output)) {
                return false;
            }

            // Ensure destination folder exists
            $dir = dirname($abs_path);
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
            }

            return file_put_contents($abs_path, $output) !== false;
        } catch (\Throwable $e) {
            error_log('[WHOISCRM PDF Renderer] Error: ' . $e->getMessage());
            return false;
        }
    }
}
