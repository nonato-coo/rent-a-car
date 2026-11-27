<?php
require_once __DIR__ . '/../config.php';

class ApiClient {
    public static function request(string $method, string $endpoint, ?array $data = null, ?string $token = null): array {
        $url = rtrim(API_BASE_URL, '/') . '/' . ltrim($endpoint, '/');

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
        ];

        $authToken = $token ?? ($_SESSION['token'] ?? null);
        if ($authToken) {
            $headers[] = 'Authorization: Bearer ' . $authToken;
        }

        $options = [
            'http' => [
                'method'        => strtoupper($method),
                'header'        => implode("\r\n", $headers) . "\r\n",
                'ignore_errors' => true,
                'timeout'       => 10,
            ]
        ];

        if ($data !== null && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'], true)) {
            $options['http']['content'] = json_encode($data, JSON_UNESCAPED_UNICODE);
        }

        $context = stream_context_create($options);
        $rawBody = @file_get_contents($url, false, $context);

        $responseHeaders = function_exists('http_get_last_response_headers')
            ? (http_get_last_response_headers() ?? [])
            : ($http_response_header ?? []);

        $statusCode = 0;
        if (isset($responseHeaders[0]) && preg_match('/HTTP\/\d\.\d\s+(\d+)/', $responseHeaders[0], $matches)) {
            $statusCode = (int)$matches[1];
        }

        $decoded = $rawBody !== false ? json_decode($rawBody, true) : null;

        return [
            'ok'     => $statusCode >= 200 && $statusCode < 300,
            'status' => $statusCode,
            'data'   => $decoded ?? $rawBody,
        ];
    }

    public static function get(string $endpoint): array {
        return self::request('GET', $endpoint);
    }

    public static function post(string $endpoint, array $data): array {
        return self::request('POST', $endpoint, $data);
    }

    public static function put(string $endpoint, array $data): array {
        return self::request('PUT', $endpoint, $data);
    }

    public static function delete(string $endpoint): array {
        return self::request('DELETE', $endpoint);
    }
}