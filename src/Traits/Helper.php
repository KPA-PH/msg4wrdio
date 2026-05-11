<?php

namespace KPAPH\MSG4wrdIO\Traits;

use KPAPH\MSG4wrdIO\Enums\Country;

trait Helper
{
    /**
     * Strip formatting (+, spaces, dashes, dots, parentheses) and return a
     * digits-only string, or null if anything else was in there.
     */
    public static function normalizeNumber(string $number): ?string
    {
        $cleaned = preg_replace('/[\s\-\.\(\)\+]/', '', $number);

        if ($cleaned === '' || !ctype_digit($cleaned)) {
            return null;
        }

        return $cleaned;
    }

    /**
     * Returns the detected Country for a +1 (US/CA) or +63 (PH) number, or
     * null if the number is not a valid mobile number for either region.
     */
    public static function detectCountry(string $number): ?Country
    {
        $normalized = self::normalizeNumber($number);

        if ($normalized === null) {
            return null;
        }

        // Philippines: 63 + 10 digits; PH mobile numbers always begin with 9
        // after the country code (e.g. 639171234567).
        if (strlen($normalized) === 12 && str_starts_with($normalized, '63')) {
            return $normalized[2] === '9' ? Country::PH : null;
        }

        // NANP (US / Canada): 1 + 10 digits, where area code and exchange
        // code must both start with 2-9 per NANP rules.
        if (strlen($normalized) === 11 && str_starts_with($normalized, '1')) {
            $areaCode = $normalized[1];
            $exchange = $normalized[4];

            if ($areaCode >= '2' && $areaCode <= '9' && $exchange >= '2' && $exchange <= '9') {
                return Country::US;
            }
        }

        return null;
    }

    /**
     * True only for valid +63 (PH mobile) or +1 (US/CA) numbers. A leading
     * "+" and common separators (space, dash, dot, parentheses) are accepted.
     */
    public static function checkNumberCountryCode(string $number): bool
    {
        return self::detectCountry($number) !== null;
    }

    /**
     * Resolve the Country enum from the user-supplied $options. Accepts a
     * Country enum, the enum's int value, or a string alias
     * (PH/PHL/PHILIPPINES, US/USA/CA/CAN/CANADA). Falls back to PH.
     */
    public static function checkCountry(array $options): Country
    {
        $country = $options['country'] ?? null;

        if ($country instanceof Country) {
            return $country;
        }

        if (is_int($country)) {
            return Country::tryFrom($country) ?? Country::PH;
        }

        if (is_string($country)) {
            $upper = strtoupper(trim($country));

            if (in_array($upper, ['US', 'USA', 'CA', 'CAN', 'CANADA'], true)) {
                return Country::US;
            }

            if (in_array($upper, ['PH', 'PHL', 'PHILIPPINES'], true)) {
                return Country::PH;
            }
        }

        return Country::PH;
    }
}
