<?php

declare(strict_types=1);

namespace WhoisCRM\Helpers;

/**
 * Formatting utilities for currency, dates, file sizes, and display values.
 */
class Formatter
{
    /**
     * Format a monetary amount with currency symbol.
     */
    public static function currency(float $amount, string $currency = 'USD'): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'INR' => '₹',
            'AUD' => 'A$',
            'CAD' => 'C$',
            'AED' => 'AED ',
            'BRL' => 'R$',
        ];

        $symbol = $symbols[strtoupper($currency)] ?? strtoupper($currency) . ' ';

        return $symbol . number_format($amount, 2);
    }

    /**
     * Format file size in human-readable units.
     */
    public static function file_size(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = (int) floor(log($bytes, 1024));
        $power = min($power, count($units) - 1);

        return round($bytes / pow(1024, $power), 2) . ' ' . $units[$power];
    }

    /**
     * Format file size in human-readable units (alias for file_size).
     */
    public static function bytes(int $bytes): string
    {
        return self::file_size($bytes);
    }

    /**
     * Format a date for display.
     */
    public static function date(string $datetime, string $format = ''): string
    {
        if (empty($format)) {
            $format = get_option('date_format', 'Y-m-d');
        }

        $timestamp = strtotime($datetime);
        if ($timestamp === false) {
            return $datetime;
        }

        return wp_date($format, $timestamp) ?: $datetime;
    }

    /**
     * Format a datetime for display.
     */
    public static function datetime(string $datetime, string $format = ''): string
    {
        if (empty($format)) {
            $format = get_option('date_format', 'Y-m-d') . ' ' . get_option('time_format', 'H:i');
        }

        $timestamp = strtotime($datetime);
        if ($timestamp === false) {
            return $datetime;
        }

        return wp_date($format, $timestamp) ?: $datetime;
    }

    /**
     * Format relative time (e.g., "2 hours ago").
     */
    public static function time_ago(string $datetime): string
    {
        $timestamp = strtotime($datetime);
        if ($timestamp === false) {
            return $datetime;
        }

        return human_time_diff($timestamp, current_time('timestamp', true)) . ' ' . __('ago', 'whois-crm');
    }

    /**
     * Format billing cycle for display.
     */
    public static function billing_cycle(string $cycle): string
    {
        return match ($cycle) {
            'monthly'  => __('Monthly', 'whois-crm'),
            'annually' => __('Annual', 'whois-crm'),
            default    => ucfirst($cycle),
        };
    }

    /**
     * Format subscription status with readable label.
     */
    public static function subscription_status(string $status): string
    {
        return match ($status) {
            'active'    => __('Active', 'whois-crm'),
            'past_due'  => __('Past Due', 'whois-crm'),
            'cancelled' => __('Cancelled', 'whois-crm'),
            'expired'   => __('Expired', 'whois-crm'),
            'trialing'  => __('Trial', 'whois-crm'),
            'paused'    => __('Paused', 'whois-crm'),
            default     => ucfirst($status),
        };
    }

    /**
     * Format subscription status as an HTML badge.
     */
    public static function status_badge(string $status): string
    {
        $label = self::subscription_status($status);

        $class = match ($status) {
            'active'    => 'whoiscrm-badge--success',
            'past_due'  => 'whoiscrm-badge--warning',
            'cancelled' => 'whoiscrm-badge--danger',
            'expired'   => 'whoiscrm-badge--muted',
            'trialing'  => 'whoiscrm-badge--info',
            'paused'    => 'whoiscrm-badge--warning',
            'succeeded' => 'whoiscrm-badge--success',
            'failed'    => 'whoiscrm-badge--danger',
            'pending'   => 'whoiscrm-badge--warning',
            'refunded'  => 'whoiscrm-badge--muted',
            default     => 'whoiscrm-badge--muted',
        };

        return sprintf(
            '<span class="whoiscrm-badge %s">%s</span>',
            esc_attr($class),
            esc_html($label)
        );
    }

    /**
     * Generate an invoice number.
     *
     * Format: WHOIS-{YEAR}-{PADDED_NUMBER}
     */
    public static function invoice_number(int $sequence): string
    {
        $year = gmdate('Y');
        return sprintf('WHOIS-%s-%05d', $year, $sequence);
    }

    /**
     * Truncate a string with ellipsis.
     */
    public static function truncate(string $text, int $length = 50): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        return mb_substr($text, 0, $length) . '…';
    }
}
