<?php

declare(strict_types=1);

namespace WhoisCRM\Helpers;

/**
 * Country code to name mapping utility.
 *
 * Provides ISO 3166-1 alpha-2 country codes and their display names.
 * Used throughout the plugin for country selection dropdowns and display.
 */
class CountryList
{
    /**
     * Countries actively supported with data packages.
     *
     * @return array<string, string> Country code => Country name
     */
    public static function get_supported_countries(): array
    {
        return [
            'US' => __('United States', 'whois-crm'),
            'IN' => __('India', 'whois-crm'),
            'GB' => __('United Kingdom', 'whois-crm'),
            'CA' => __('Canada', 'whois-crm'),
            'AE' => __('United Arab Emirates', 'whois-crm'),
            'AU' => __('Australia', 'whois-crm'),
            'DE' => __('Germany', 'whois-crm'),
            'FR' => __('France', 'whois-crm'),
            'BR' => __('Brazil', 'whois-crm'),
        ];
    }

    /**
     * Full list of countries for customer profile addresses.
     *
     * @return array<string, string> Country code => Country name
     */
    public static function get_all_countries(): array
    {
        return [
            'AF' => __('Afghanistan', 'whois-crm'),
            'AL' => __('Albania', 'whois-crm'),
            'DZ' => __('Algeria', 'whois-crm'),
            'AR' => __('Argentina', 'whois-crm'),
            'AU' => __('Australia', 'whois-crm'),
            'AT' => __('Austria', 'whois-crm'),
            'BH' => __('Bahrain', 'whois-crm'),
            'BD' => __('Bangladesh', 'whois-crm'),
            'BE' => __('Belgium', 'whois-crm'),
            'BR' => __('Brazil', 'whois-crm'),
            'CA' => __('Canada', 'whois-crm'),
            'CL' => __('Chile', 'whois-crm'),
            'CN' => __('China', 'whois-crm'),
            'CO' => __('Colombia', 'whois-crm'),
            'CZ' => __('Czech Republic', 'whois-crm'),
            'DK' => __('Denmark', 'whois-crm'),
            'EG' => __('Egypt', 'whois-crm'),
            'FI' => __('Finland', 'whois-crm'),
            'FR' => __('France', 'whois-crm'),
            'DE' => __('Germany', 'whois-crm'),
            'GH' => __('Ghana', 'whois-crm'),
            'GR' => __('Greece', 'whois-crm'),
            'HK' => __('Hong Kong', 'whois-crm'),
            'HU' => __('Hungary', 'whois-crm'),
            'IN' => __('India', 'whois-crm'),
            'ID' => __('Indonesia', 'whois-crm'),
            'IE' => __('Ireland', 'whois-crm'),
            'IL' => __('Israel', 'whois-crm'),
            'IT' => __('Italy', 'whois-crm'),
            'JP' => __('Japan', 'whois-crm'),
            'JO' => __('Jordan', 'whois-crm'),
            'KE' => __('Kenya', 'whois-crm'),
            'KR' => __('South Korea', 'whois-crm'),
            'KW' => __('Kuwait', 'whois-crm'),
            'MY' => __('Malaysia', 'whois-crm'),
            'MX' => __('Mexico', 'whois-crm'),
            'NL' => __('Netherlands', 'whois-crm'),
            'NZ' => __('New Zealand', 'whois-crm'),
            'NG' => __('Nigeria', 'whois-crm'),
            'NO' => __('Norway', 'whois-crm'),
            'OM' => __('Oman', 'whois-crm'),
            'PK' => __('Pakistan', 'whois-crm'),
            'PE' => __('Peru', 'whois-crm'),
            'PH' => __('Philippines', 'whois-crm'),
            'PL' => __('Poland', 'whois-crm'),
            'PT' => __('Portugal', 'whois-crm'),
            'QA' => __('Qatar', 'whois-crm'),
            'RO' => __('Romania', 'whois-crm'),
            'RU' => __('Russia', 'whois-crm'),
            'SA' => __('Saudi Arabia', 'whois-crm'),
            'SG' => __('Singapore', 'whois-crm'),
            'ZA' => __('South Africa', 'whois-crm'),
            'ES' => __('Spain', 'whois-crm'),
            'LK' => __('Sri Lanka', 'whois-crm'),
            'SE' => __('Sweden', 'whois-crm'),
            'CH' => __('Switzerland', 'whois-crm'),
            'TW' => __('Taiwan', 'whois-crm'),
            'TH' => __('Thailand', 'whois-crm'),
            'TR' => __('Turkey', 'whois-crm'),
            'AE' => __('United Arab Emirates', 'whois-crm'),
            'GB' => __('United Kingdom', 'whois-crm'),
            'US' => __('United States', 'whois-crm'),
            'VN' => __('Vietnam', 'whois-crm'),
        ];
    }

    /**
     * Get country name by ISO code.
     */
    public static function get_name(string $code): string
    {
        $countries = self::get_all_countries();
        return $countries[strtoupper($code)] ?? $code;
    }

    /**
     * Check if a country code is valid.
     */
    public static function is_valid(string $code): bool
    {
        return array_key_exists(strtoupper($code), self::get_all_countries());
    }

    /**
     * Check if a country has an active data package.
     */
    public static function is_supported(string $code): bool
    {
        return array_key_exists(strtoupper($code), self::get_supported_countries());
    }
}
