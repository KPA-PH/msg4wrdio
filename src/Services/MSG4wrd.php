<?php

namespace KPAPH\MSG4wrdIO\Services;

use KPAPH\MSG4wrdIO\Traits\API;
use KPAPH\MSG4wrdIO\Enums\Country;
use KPAPH\MSG4wrdIO\Traits\Helper;
use KPAPH\MSG4wrdIO\Enums\SenderName;

class MSG4wrd
{
    use API, Helper;

    public static function Send(string $number, string $message, array $options = []): array
    {
        $options = array_merge([
            'sendername' => SenderName::Default,
            'priority' => 0,
            'country' => Country::PH,
        ], $options);

        if (!self::checkNumberCountryCode($number)) {
            return [
                'status' => 400,
                'message' => 'Invalid number. Only +63 (PH mobile) and +1 (US/CA) numbers are supported.',
            ];
        }

        $normalized = self::normalizeNumber($number);

        $sendername = $options['sendername'];
        if ($sendername instanceof SenderName) {
            $sendername = $sendername->value;
        }

        $data = [
            'mobile' => $normalized,
            'message' => $message,
            'local' => self::checkCountry($options)->value,
            'sendername' => $sendername,
            'priority' => (int) $options['priority'],
        ];

        return self::PostAPI($data);
    }
}
