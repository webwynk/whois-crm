<?php

declare(strict_types=1);

namespace WhoisCRM\Admin\Pages;

/**
 * Abstract base for all admin page renderers.
 *
 * Provides:
 *   - Access control check via required_cap()
 *   - Shared page wrapper (header + content area)
 *   - Notice display helper
 *   - Template loading from templates/admin/
 */
abstract class BasePage
{
    /**
     * The WordPress capability required to view this page.
     * Override in each page class.
     */
    protected static string $required_cap = 'manage_options';

    /**
     * Entry point called by WordPress via add_menu_page / add_submenu_page.
     */
    public static function render(): void
    {
        if (!current_user_can(static::$required_cap)) {
            wp_die(__('You do not have permission to view this page.', 'whois-crm'));
        }

        $instance = new static();
        $instance->display();
    }

    /**
     * Implemented by each page class to output its content.
     */
    abstract protected function display(): void;

    // ─── Helpers ──────────────────────────────────────────────────────────

    /**
     * Output the standard page wrapper open tag with page title.
     *
     * @param string $title        Page heading (translated).
     * @param string $breadcrumb   Optional secondary breadcrumb label.
     * @param array  $actions      Optional header action buttons: [['label', 'url', 'class'], …]
     */
    protected function page_header(string $title, string $breadcrumb = '', array $actions = []): void
    {
        ?>
        <div class="whoiscrm-admin-page">
          <div class="whoiscrm-page-header">
            <div class="whoiscrm-page-header__left">
              <h1 class="whoiscrm-page-title"><?php echo esc_html($title); ?></h1>
              <?php if ($breadcrumb) : ?>
              <nav class="whoiscrm-breadcrumb" aria-label="breadcrumb">
                <span class="whoiscrm-breadcrumb__item">
                  <a href="<?php echo esc_url(admin_url('admin.php?page=whoiscrm')); ?>">
                    <?php esc_html_e('WHOIS CRM', 'whois-crm'); ?>
                  </a>
                </span>
                <span class="whoiscrm-breadcrumb__sep" aria-hidden="true">/</span>
                <span class="whoiscrm-breadcrumb__item whoiscrm-breadcrumb__item--active">
                  <?php echo esc_html($breadcrumb); ?>
                </span>
              </nav>
              <?php endif; ?>
            </div>
            <?php if (!empty($actions)) : ?>
            <div class="whoiscrm-page-header__actions">
              <?php foreach ($actions as $action) : ?>
                <a
                  href="<?php echo esc_url($action['url'] ?? '#'); ?>"
                  class="whoiscrm-btn <?php echo esc_attr($action['class'] ?? 'whoiscrm-btn--primary whoiscrm-btn--md'); ?>"
                >
                  <?php echo esc_html($action['label']); ?>
                </a>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div><!-- .whoiscrm-page-header -->
          <div class="whoiscrm-page-content">
        <?php
    }

    /**
     * Close the page wrapper.
     */
    protected function page_footer(): void
    {
        echo '</div><!-- .whoiscrm-page-content --></div><!-- .whoiscrm-admin-page -->';
    }

    /**
     * Display admin notices stored in a transient (for post-redirect-get pattern).
     *
     * @param string $transient_key  Unique key per page action.
     */
    protected function show_notices(string $transient_key): void
    {
        $notice = get_transient($transient_key . '_' . get_current_user_id());
        if (!$notice) {
            return;
        }

        delete_transient($transient_key . '_' . get_current_user_id());

        $type    = $notice['type'] ?? 'info';
        $message = $notice['message'] ?? '';

        printf(
            '<div class="whoiscrm-alert whoiscrm-alert--%s" role="alert">%s</div>',
            esc_attr($type),
            esc_html($message)
        );
    }

    /**
     * Store an admin notice to display after a redirect.
     */
    protected static function set_notice(string $transient_key, string $message, string $type = 'success'): void
    {
        set_transient(
            $transient_key . '_' . get_current_user_id(),
            ['type' => $type, 'message' => $message],
            60
        );
    }

    /**
     * Render a PHP template from templates/admin/.
     *
     * @param string $template  Template name relative to templates/admin/ (no .php).
     * @param array  $vars      Variables to extract into template scope.
     */
    protected function render_template(string $template, array $vars = []): void
    {
        $path = WHOISCRM_PLUGIN_DIR . 'templates/admin/' . $template . '.php';

        if (!file_exists($path)) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log("WHOIS CRM: Admin template not found: {$path}");
            return;
        }

        extract($vars, EXTR_SKIP); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
        require $path;
    }

    /**
     * Helper: paginate query results and return pagination HTML.
     *
     * @param int $total       Total rows.
     * @param int $per_page    Rows per page.
     * @param int $current     Current page number.
     * @return string          Pagination HTML.
     */
    protected function pagination_html(int $total, int $per_page, int $current): string
    {
        if ($total <= $per_page) {
            return '';
        }

        $total_pages = (int) ceil($total / $per_page);
        $base_url    = remove_query_arg('paged');

        $html = '<nav class="whoiscrm-pagination" aria-label="' . esc_attr__('Pagination', 'whois-crm') . '">';

        // Previous
        if ($current > 1) {
            $html .= sprintf(
                '<a class="whoiscrm-pagination__btn" href="%s">← %s</a>',
                esc_url(add_query_arg('paged', $current - 1, $base_url)),
                esc_html__('Previous', 'whois-crm')
            );
        }

        // Page numbers
        for ($p = max(1, $current - 2); $p <= min($total_pages, $current + 2); $p++) {
            $active = ($p === $current) ? ' is-active' : '';
            $html .= sprintf(
                '<a class="whoiscrm-pagination__num%s" href="%s" aria-current="%s">%d</a>',
                esc_attr($active),
                esc_url(add_query_arg('paged', $p, $base_url)),
                $p === $current ? 'page' : 'false',
                $p
            );
        }

        // Next
        if ($current < $total_pages) {
            $html .= sprintf(
                '<a class="whoiscrm-pagination__btn" href="%s">%s →</a>',
                esc_url(add_query_arg('paged', $current + 1, $base_url)),
                esc_html__('Next', 'whois-crm')
            );
        }

        $html .= '</nav>';

        return $html;
    }

    /**
     * Get the current page number from the request.
     */
    protected function get_current_page(int $min = 1): int
    {
        return max($min, (int) ($_GET['paged'] ?? 1));
    }

    /**
     * Sanitize and retrieve a search query string from the request.
     */
    protected function get_search(): string
    {
        return sanitize_text_field(wp_unslash($_GET['s'] ?? ''));
    }
}
