<?php

declare(strict_types=1);

namespace WhoisCRM\Payment;

/**
 * Tax Calculator.
 *
 * Estimates tax amount and total price based on customer country code
 * and option settings in the plugin.
 */
class TaxCalculator
{
    /**
     * Calculate tax for a given country and subtotal.
     *
     * @param string $country_code ISO-2 country code.
     * @param float  $subtotal     Price before tax.
     * @return array{rate: float, label: string, amount: float, total: float}
     */
    public function calculate(string $country_code, float $subtotal): array
    {
        $rates_json = get_option('whoiscrm_tax_rates', '{}');
        $rates      = json_decode($rates_json, true) ?: [];

        $country_code = strtoupper($country_code);
        
        $rate  = 0.0;
        $label = get_option('whoiscrm_tax_label', 'Tax');

        if (isset($rates[$country_code])) {
            $rate  = (float) ($rates[$country_code]['rate'] ?? $rates[$country_code]);
            $label = $rates[$country_code]['label'] ?? $label;
        } else {
            $rate = (float) get_option('whoiscrm_default_tax_rate', 0.0);
        }

        $tax_amount = round($subtotal * ($rate / 100), 2);

        return [
            'rate'   => $rate,
            'label'  => $label,
            'amount' => $tax_amount,
            'total'  => round($subtotal + $tax_amount, 2),
        ];
    }
}
