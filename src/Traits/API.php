<?php

namespace KPAPH\MSG4wrdIO\Traits;

use Exception;
use GuzzleHttp\Client;

trait API
{
    public static function PostAPI(array $data): array
    {
        $url = rtrim((string) self::resolveBaseUrl(), '/');
        $token = (string) config('msg4wrdio.token');

        try {
            $client = new Client(['timeout' => 30]);

            $response = $client->post($url . '/api/v5/send', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ],
                'json' => $data,
                'http_errors' => false,
            ]);

            $status = $response->getStatusCode();
            $body = json_decode((string) $response->getBody(), true);

            if ($status >= 200 && $status < 300) {
                return is_array($body) ? $body : ['status' => $status, 'message' => 'OK'];
            }

            return [
                'status' => $status,
                'message' => is_array($body) && isset($body['message']) ? $body['message'] : 'Request failed',
                'body' => $body,
            ];
        } catch (Exception $e) {
            return [
                'status' => 500,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Pick the base URL based on dev_mode: true → sandbox, false → live.
     * Falls back to the live URL if the resolved entry is empty.
     */
    protected static function resolveBaseUrl(): string
    {
        $devMode = filter_var(config('msg4wrdio.dev_mode', false), FILTER_VALIDATE_BOOLEAN);
        $key = $devMode ? 'sandbox' : 'live';

        $url = (string) config('msg4wrdio.' . $key);

        if ($url === '') {
            $url = (string) config('msg4wrdio.live', 'https://api.msg4wrd.io');
        }

        return $url;
    }
}
