<?php

declare(strict_types=1);

namespace WhoisCRM;

/**
 * Plugin deactivation handler.
 *
 * Cleans up scheduled events. Does NOT delete data or tables.
 * Data removal only happens on uninstall (see uninstall.php).
 */
class Deactivator
{
    /**
     * Execute deactivation routines.
     */
    public static function deactivate(): void
    {
        // Clear all scheduled cron events.
        self::clear_cron_events();
    }

    /**
     * Remove all plugin cron schedules.
     */
    private static function clear_cron_events(): void
    {
        $events = [
            'whoiscrm_cron_check_expiry',
            'whoiscrm_cron_send_reminders',
            'whoiscrm_cron_reset_api_limits',
            'whoiscrm_cron_cleanup_logs',
        ];

        foreach ($events as $event) {
            $timestamp = wp_next_scheduled($event);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $event);
            }
        }
    }
}
