<?php

declare(strict_types=1);

namespace WhoisCRM\Subscription;

use WhoisCRM\Database\Models\Subscription;
use WhoisCRM\Database\Models\Customer;
use WhoisCRM\Database\Models\ActivityLog;
use WhoisCRM\Notification\EmailManager;

/**
 * Scheduled Cron Jobs Manager.
 *
 * Configures WP Cron tasks to:
 *  1. Reset daily developer REST API key quotas.
 *  2. Check for subscriptions nearing expiration and dispatch email alerts.
 *  3. Set past-due/expired subscription statuses automatically.
 */
class CronJobs
{
    public function __construct()
    {
        add_action('whoiscrm_cron_check_expiry',    [$this, 'check_expiry']);
        add_action('whoiscrm_cron_reset_api_limits', [$this, 'reset_api_limits']);
        add_action('whoiscrm_cron_cleanup_logs',    [$this, 'cleanup_old_logs']);

        // Schedule events on instantiation
        $this->schedule();
    }

    /**
     * Schedule periodic WP Cron events if they are not already registered.
     */
    public function schedule(): void
    {
        if (!wp_next_scheduled('whoiscrm_cron_check_expiry')) {
            wp_schedule_event(time(), 'twicedaily', 'whoiscrm_cron_check_expiry');
        }

        if (!wp_next_scheduled('whoiscrm_cron_reset_api_limits')) {
            wp_schedule_event(time(), 'daily', 'whoiscrm_cron_reset_api_limits');
        }

        if (!wp_next_scheduled('whoiscrm_cron_cleanup_logs')) {
            wp_schedule_event(time(), 'daily', 'whoiscrm_cron_cleanup_logs');
        }
    }

    /**
     * Check for expired subscriptions or those expiring within 7-day or 1-day intervals.
     *
     * Sends email alerts and transitions status to 'expired' where applicable.
     */
    public function check_expiry(): void
    {
        $sub_model     = new Subscription();
        $email_manager = new EmailManager();

        // ── 1. 7-Day Expiration Reminders ──────────────────────────────
        $expiring_7d = $sub_model->get_expiring_soon(7);
        foreach ($expiring_7d as $sub) {
            $transient_key = "whoiscrm_reminder_7d_{$sub->id}";
            if (!get_transient($transient_key)) {
                $email_manager->send_expiry_reminder_7day($sub);
                // Cache reminder state for 8 days to prevent redundant sends
                set_transient($transient_key, '1', 8 * DAY_IN_SECONDS);
            }
        }

        // ── 2. 1-Day Expiration Reminders ──────────────────────────────
        $expiring_1d = $sub_model->get_expiring_soon(1);
        foreach ($expiring_1d as $sub) {
            $transient_key = "whoiscrm_reminder_1d_{$sub->id}";
            if (!get_transient($transient_key)) {
                $email_manager->send_expiry_reminder_1day($sub);
                // Cache reminder state for 2 days
                set_transient($transient_key, '1', 2 * DAY_IN_SECONDS);
            }
        }

        // ── 3. Handle Expired Subscriptions ────────────────────────────
        $expired = $sub_model->get_expired();
        foreach ($expired as $sub) {
            $sub_model->set_status((int) $sub->id, 'expired');
            $email_manager->send_subscription_expired($sub);

            // Fetch WP user ID for activity logging
            $customer = (new Customer())->find((int) $sub->customer_id);
            $wp_user_id = $customer ? (int) $customer->user_id : null;

            // Log activity
            (new ActivityLog())->log(
                ActivityLog::ACTION_SUBSCRIPTION,
                sprintf(__('Subscription #%d has expired.', 'whois-crm'), (int) $sub->id),
                [],
                ActivityLog::SEVERITY_WARNING,
                $wp_user_id,
                'subscription',
                (int) $sub->id
            );
        }
    }

    /**
     * Reset the daily request quota tracker for all active REST API keys.
     */
    public function reset_api_limits(): void
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'whoiscrm_api_keys';
        
        // Reset requests_today back to zero
        $wpdb->query("UPDATE {$table_name} SET requests_today = 0");
    }

    /**
     * Delete activity logs older than 90 days.
     */
    public function cleanup_old_logs(): void
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'whoiscrm_activity_log';
        $ninety_days_ago = gmdate('Y-m-d H:i:s', strtotime('-90 days'));

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table_name} WHERE created_at < %s",
                $ninety_days_ago
            )
        );
    }

    /**
     * Clear scheduled cron events on plugin deactivation.
     */
    public static function unschedule(): void
    {
        $timestamp_expiry = wp_next_scheduled('whoiscrm_cron_check_expiry');
        if ($timestamp_expiry) {
            wp_unschedule_event($timestamp_expiry, 'whoiscrm_cron_check_expiry');
        }

        $timestamp_api = wp_next_scheduled('whoiscrm_cron_reset_api_limits');
        if ($timestamp_api) {
            wp_unschedule_event($timestamp_api, 'whoiscrm_cron_reset_api_limits');
        }

        $timestamp_logs = wp_next_scheduled('whoiscrm_cron_cleanup_logs');
        if ($timestamp_logs) {
            wp_unschedule_event($timestamp_logs, 'whoiscrm_cron_cleanup_logs');
        }
    }
}
