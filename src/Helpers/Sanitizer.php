<?php

declare(strict_types=1);

namespace WhoisCRM\Helpers;

/**
 * Input sanitization wrappers.
 *
 * Centralizes sanitization logic so every input passes through
 * consistent cleaning before reaching the database.
 */
class Sanitizer
{
    /**
     * Sanitize a standard text field.
     */
    public static function text(mixed $value): string
    {
        return sanitize_text_field(wp_unslash($value ?? ''));
    }

    /**
     * Sanitize an email address.
     */
    public static function email(mixed $value): string
    {
        return sanitize_email(wp_unslash($value ?? ''));
    }

    /**
     * Sanitize a textarea (allows newlines, strips tags).
     */
    public static function textarea(mixed $value): string
    {
        return sanitize_textarea_field(wp_unslash($value ?? ''));
    }

    /**
     * Sanitize to a positive integer.
     */
    public static function absint(mixed $value): int
    {
        return absint($value ?? 0);
    }

    /**
     * Sanitize to a float value.
     */
    public static function float(mixed $value): float
    {
        return (float) filter_var($value ?? 0, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    /**
     * Sanitize a URL.
     */
    public static function url(mixed $value): string
    {
        return esc_url_raw(wp_unslash($value ?? ''));
    }

    /**
     * Sanitize a filename.
     */
    public static function filename(mixed $value): string
    {
        return sanitize_file_name(wp_unslash($value ?? ''));
    }

    /**
     * Sanitize an ISO date string (YYYY-MM-DD).
     *
     * Returns empty string if format is invalid.
     */
    public static function date(mixed $value): string
    {
        $value = self::text($value);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $parsed = \DateTime::createFromFormat('Y-m-d', $value);
            if ($parsed && $parsed->format('Y-m-d') === $value) {
                return $value;
            }
        }

        return '';
    }

    /**
     * Sanitize a country code (ISO 3166-1 alpha-2).
     *
     * Returns empty string if not exactly 2 uppercase letters.
     */
    public static function country_code(mixed $value): string
    {
        $value = strtoupper(self::text($value));

        if (preg_match('/^[A-Z]{2}$/', $value)) {
            return $value;
        }

        return '';
    }

    /**
     * Sanitize a slug (lowercase alphanumeric + hyphens).
     */
    public static function slug(mixed $value): string
    {
        return sanitize_title(wp_unslash($value ?? ''));
    }

    /**
     * Sanitize a coupon code (uppercase, alphanumeric + hyphens/underscores).
     */
    public static function coupon_code(mixed $value): string
    {
        $value = strtoupper(self::text($value));
        return preg_replace('/[^A-Z0-9\-_]/', '', $value);
    }

    /**
     * Sanitize an array of values using a callback.
     *
     * @param array    $values   Values to sanitize.
     * @param callable $callback Sanitization function (e.g., 'sanitize_text_field').
     * @return array Sanitized values.
     */
    public static function array(array $values, callable $callback): array
    {
        return array_map($callback, $values);
    }

    /**
     * Sanitize and validate a value against an allowed list.
     *
     * Returns the value if it's in the allowed list, otherwise returns the default.
     */
    public static function enum(mixed $value, array $allowed, string $default = ''): string
    {
        $value = self::text($value);
        return in_array($value, $allowed, true) ? $value : $default;
    }

    /**
     * Sanitize a JSON string.
     *
     * Decodes, validates, and re-encodes to ensure valid JSON.
     * Returns null if the input is not valid JSON.
     */
    public static function json(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = wp_unslash($value);
        $decoded = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return wp_json_encode($decoded);
    }
}
