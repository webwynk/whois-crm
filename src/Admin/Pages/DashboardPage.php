<?php

declare(strict_types=1);

namespace WhoisCRM\Admin\Pages;

use WhoisCRM\Database\Models\Customer;
use WhoisCRM\Database\Models\Subscription;
use WhoisCRM\Database\Models\Payment;
use WhoisCRM\Database\Models\Download;
use WhoisCRM\Database\Models\DataFile;

/**
 * Dashboard overview page.
 *
 * Shows KPI stat cards and quick-access charts/lists.
 */
class DashboardPage extends BasePage
{
    protected static string $required_cap = 'manage_options';

    protected function display(): void
    {
        // ── Collect stats ─────────────────────────────────────────────────
        global $wpdb;
        $prefix = $wpdb->prefix . 'whoiscrm_';

        // Total customers
        $total_customers = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$prefix}customers");

        // Active subscriptions
        $active_subs = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$prefix}subscriptions WHERE status IN ('active','trialing')"
        );

        // Revenue this month
        $month_start   = gmdate('Y-m-01 00:00:00');
        $month_revenue = (new Payment())->get_revenue($month_start);

        // Total downloads today
        $today_start     = gmdate('Y-m-d 00:00:00');
        $downloads_today = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$prefix}downloads WHERE downloaded_at >= %s",
                $today_start
            )
        );

        // Recent customers (last 5)
        $recent_customers = $wpdb->get_results(
            "SELECT c.*, u.user_email AS email,
                    COALESCE(fn.meta_value,'') AS first_name,
                    COALESCE(ln.meta_value,'') AS last_name
             FROM {$prefix}customers c
             INNER JOIN {$wpdb->users} u ON u.ID = c.user_id
             LEFT JOIN {$wpdb->usermeta} fn ON fn.user_id=u.ID AND fn.meta_key='first_name'
             LEFT JOIN {$wpdb->usermeta} ln ON ln.user_id=u.ID AND ln.meta_key='last_name'
             ORDER BY c.created_at DESC LIMIT 5"
        ) ?: [];

        // Recent payments (last 5)
        $recent_payments = $wpdb->get_results(
            "SELECT pay.*, u.user_email AS customer_email, p.name AS package_name
             FROM {$prefix}payments pay
             INNER JOIN {$prefix}customers c ON c.id = pay.customer_id
             INNER JOIN {$wpdb->users} u ON u.ID = c.user_id
             INNER JOIN {$prefix}packages p ON p.id = pay.package_id
             WHERE pay.status = 'succeeded'
             ORDER BY pay.paid_at DESC LIMIT 5"
        ) ?: [];

        // Revenue last 7 days (for sparkline)
        $revenue_7d = [];
        for ($i = 6; $i >= 0; $i--) {
            $day        = gmdate('Y-m-d', strtotime("-{$i} days"));
            $day_start  = $day . ' 00:00:00';
            $day_end    = $day . ' 23:59:59';
            $revenue_7d[] = [
                'date'   => $day,
                'amount' => (float) $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COALESCE(SUM(total_amount),0) FROM {$prefix}payments
                         WHERE status='succeeded' AND paid_at BETWEEN %s AND %s",
                        $day_start,
                        $day_end
                    )
                ),
            ];
        }

        $this->page_header(__('Dashboard', 'whois-crm'));
        $this->render_template('dashboard', [
            'total_customers'  => $total_customers,
            'active_subs'      => $active_subs,
            'month_revenue'    => $month_revenue,
            'downloads_today'  => $downloads_today,
            'recent_customers' => $recent_customers,
            'recent_payments'  => $recent_payments,
            'revenue_7d'       => wp_json_encode($revenue_7d),
        ]);
        $this->page_footer();
    }
}
