<?php

declare(strict_types=1);

namespace WhoisCRM\Admin\Pages;

use WhoisCRM\Database\Models\Download;
use WhoisCRM\Database\Models\Payment;

/**
 * Reports & Analytics admin page.
 */
class ReportsPage extends BasePage
{
    protected static string $required_cap = 'whoiscrm_view_reports';

    protected function display(): void
    {
        global $wpdb;
        $prefix = $wpdb->prefix . 'whoiscrm_';

        $from = sanitize_text_field($_GET['from'] ?? gmdate('Y-m-01'));
        $to   = sanitize_text_field($_GET['to']   ?? gmdate('Y-m-d'));

        // Revenue for period
        $revenue = (new Payment())->get_revenue($from . ' 00:00:00', $to . ' 23:59:59');

        // Subscription growth (new subscriptions per day in period)
        $sub_growth = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DATE(created_at) AS day, COUNT(*) AS count
                 FROM {$prefix}subscriptions
                 WHERE created_at BETWEEN %s AND %s
                 GROUP BY DATE(created_at)
                 ORDER BY day ASC",
                $from . ' 00:00:00',
                $to . ' 23:59:59'
            )
        ) ?: [];

        // Top plans by revenue
        $top_plans = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT p.name AS package_name, COUNT(pay.id) AS payment_count,
                        SUM(pay.total_amount) AS revenue
                 FROM {$prefix}payments pay
                 INNER JOIN {$prefix}packages p ON p.id = pay.package_id
                 WHERE pay.status = 'succeeded'
                   AND pay.paid_at BETWEEN %s AND %s
                 GROUP BY pay.package_id
                 ORDER BY revenue DESC
                 LIMIT 10",
                $from . ' 00:00:00',
                $to . ' 23:59:59'
            )
        ) ?: [];

        // Top downloaded files
        $top_files = (new Download())->get_top_files(10, $from . ' 00:00:00', $to . ' 23:59:59');

        // Top customers by downloads
        $top_customers = (new Download())->get_top_customers(10);

        // New customers this period
        $new_customers = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$prefix}customers
                 WHERE created_at BETWEEN %s AND %s",
                $from . ' 00:00:00',
                $to . ' 23:59:59'
            )
        );

        $this->page_header(__('Reports & Analytics', 'whois-crm'));
        $this->render_template('reports/overview', [
            'from'          => $from,
            'to'            => $to,
            'revenue'       => $revenue,
            'new_customers' => $new_customers,
            'sub_growth'    => wp_json_encode($sub_growth),
            'top_plans'     => $top_plans,
            'top_files'     => $top_files,
            'top_customers' => $top_customers,
        ]);
        $this->page_footer();
    }
}
