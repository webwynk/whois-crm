<?php

declare(strict_types=1);

namespace WhoisCRM\Notification;

/**
 * Email Template Renderer.
 *
 * Compiles specific email templates (e.g., welcome.php)
 * and wraps them inside the master layout (base-layout.php).
 */
class EmailTemplateRenderer
{
    /**
     * Render a complete HTML email.
     *
     * @param string $template_name Relative template file name (without .php).
     * @param array  $args          Data variables to pass to the template.
     * @return string               Rendered HTML.
     */
    public function render(string $template_name, array $args = []): string
    {
        // 1. Render the main body content of the email
        $body_html = $this->render_file($template_name, $args);

        // 2. Wrap the body content inside the base layout
        $layout_args = array_merge($args, [
            'email_body_content' => $body_html,
        ]);

        return $this->render_file('base-layout', $layout_args);
    }

    /**
     * Render a single PHP template file with arguments.
     */
    private function render_file(string $template_name, array $args): string
    {
        $file_path = WHOISCRM_PLUGIN_DIR . 'templates/emails/' . $template_name . '.php';

        if (!file_exists($file_path)) {
            return '';
        }

        // Output buffer template execution
        ob_start();
        extract($args); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
        include $file_path;
        return ob_get_clean() ?: '';
    }
}
