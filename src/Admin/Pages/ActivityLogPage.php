<?php

declare(strict_types=1);

namespace WhoisCRM\Admin\Pages;

use WhoisCRM\Database\Models\ActivityLog;

/**
 * Activity Log admin page.
 *
 * Read-only audit log of all events in the system.
 * Supports filter by user, action type, severity, and date range.
 */
class ActivityLogPage extends BasePage
{
    protected static string $required_cap = 'whoiscrm_view_activity_log';

    protected function display(): void
    {
        $user_id  = (int) ($_GET['user_id'] ?? 0);
        $action   = sanitize_key($_GET['action_filter'] ?? '');
        $severity = sanitize_key($_GET['severity'] ?? '');
        $from     = sanitize_text_field($_GET['from'] ?? '');
        $to       = sanitize_text_field($_GET['to']   ?? '');
        $page     = $this->get_current_page();
        $per      = 50;

        $result = (new ActivityLog())->get_admin_list(
            $user_id,
            $action,
            $severity,
            $from,
            $to,
            $page,
            $per
        );

        $this->page_header(__('Activity Log', 'whois-crm'));
        $this->render_template('activity-log/list', [
            'rows'         => $result['rows'],
            'total'        => $result['total'],
            'per_page'     => $per,
            'current_page' => $page,
            'pagination'   => $this->pagination_html($result['total'], $per, $page),
            'user_id'      => $user_id,
            'action_filter' => $action,
            'severity'     => $severity,
            'from'         => $from,
            'to'           => $to,
            'action_types' => $this->get_action_types(),
        ]);
        $this->page_footer();
    }

    /**
     * Return available action type labels for the filter dropdown.
     *
     * @return array<string, string>
     */
    private function get_action_types(): array
    {
        return [
            ActivityLog::ACTION_LOGIN              => __('Login', 'whois-crm'),
            ActivityLog::ACTION_LOGIN_FAILED       => __('Failed Login', 'whois-crm'),
            ActivityLog::ACTION_LOGOUT             => __('Logout', 'whois-crm'),
            ActivityLog::ACTION_REGISTER           => __('Register', 'whois-crm'),
            ActivityLog::ACTION_PASSWORD_RESET     => __('Password Reset', 'whois-crm'),
            ActivityLog::ACTION_DOWNLOAD           => __('Download', 'whois-crm'),
            ActivityLog::ACTION_PAYMENT            => __('Payment', 'whois-crm'),
            ActivityLog::ACTION_PAYMENT_FAILED     => __('Failed Payment', 'whois-crm'),
            ActivityLog::ACTION_SUBSCRIPTION       => __('Subscription', 'whois-crm'),
            ActivityLog::ACTION_SUBSCRIPTION_CANCEL => __('Subscription Cancel', 'whois-crm'),
            ActivityLog::ACTION_WEBHOOK            => __('Stripe Webhook', 'whois-crm'),
            ActivityLog::ACTION_ADMIN_ACTION       => __('Admin Action', 'whois-crm'),
            ActivityLog::ACTION_API_REQUEST        => __('API Request', 'whois-crm'),
            ActivityLog::ACTION_FILE_UPLOAD        => __('File Upload', 'whois-crm'),
            ActivityLog::ACTION_CUSTOMER_BLOCKED   => __('Customer Blocked', 'whois-crm'),
            ActivityLog::ACTION_CUSTOMER_UNBLOCKED => __('Customer Unblocked', 'whois-crm'),
        ];
    }
}
