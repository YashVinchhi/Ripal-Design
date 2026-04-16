<?php

if (!function_exists('paypal_mode')) {
    function paypal_mode(): string
    {
        $mode = strtolower(trim((string)(getenv('PAYPAL_MODE') ?: 'sandbox')));
        return $mode === 'live' ? 'live' : 'sandbox';
    }
}

if (!function_exists('paypal_base_url')) {
    function paypal_base_url(): string
    {
        return paypal_mode() === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }
}

if (!function_exists('paypal_client_id')) {
    function paypal_client_id(): string
    {
        return trim((string)(getenv('PAYPAL_CLIENT_ID') ?: ''));
    }
}

if (!function_exists('paypal_client_secret')) {
    function paypal_client_secret(): string
    {
        return trim((string)(getenv('PAYPAL_CLIENT_SECRET') ?: ''));
    }
}

if (!function_exists('paypal_is_configured')) {
    function paypal_is_configured(): bool
    {
        return paypal_client_id() !== '' && paypal_client_secret() !== '';
    }
}

if (!function_exists('paypal_http_request')) {
    /**
     * @return array{ok:bool,status:int,data:array<string,mixed>,raw:string,error:string}
     */
    function paypal_http_request(string $method, string $url, array $headers = [], ?string $body = null): array
    {
        if (!function_exists('curl_init')) {
            return [
                'ok' => false,
                'status' => 500,
                'data' => [],
                'raw' => '',
                'error' => 'cURL extension is required for PayPal integration.',
            ];
        }

        $ch = curl_init($url);
        if ($ch === false) {
            return [
                'ok' => false,
                'status' => 500,
                'data' => [],
                'raw' => '',
                'error' => 'Unable to initialize HTTP client.',
            ];
        }

        $normalizedHeaders = [];
        foreach ($headers as $k => $v) {
            if (is_int($k)) {
                $normalizedHeaders[] = (string)$v;
            } else {
                $normalizedHeaders[] = $k . ': ' . $v;
            }
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $normalizedHeaders,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $raw = (string)curl_exec($ch);
        $curlErrNo = curl_errno($ch);
        $curlErr = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlErrNo !== 0) {
            return [
                'ok' => false,
                'status' => 502,
                'data' => [],
                'raw' => $raw,
                'error' => 'PayPal API call failed: ' . $curlErr,
            ];
        }

        $decoded = json_decode($raw, true);
        $data = is_array($decoded) ? $decoded : [];

        return [
            'ok' => $status >= 200 && $status < 300,
            'status' => $status,
            'data' => $data,
            'raw' => $raw,
            'error' => '',
        ];
    }
}

if (!function_exists('paypal_get_access_token')) {
    /**
     * @return array{ok:bool,status:int,token:string,error:string,data:array<string,mixed>}
     */
    function paypal_get_access_token(): array
    {
        if (!paypal_is_configured()) {
            return [
                'ok' => false,
                'status' => 500,
                'token' => '',
                'error' => 'PayPal credentials are not configured in environment variables.',
                'data' => [],
            ];
        }

        $auth = base64_encode(paypal_client_id() . ':' . paypal_client_secret());
        $res = paypal_http_request(
            'POST',
            paypal_base_url() . '/v1/oauth2/token',
            [
                'Authorization' => 'Basic ' . $auth,
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
            ],
            'grant_type=client_credentials'
        );

        if (!$res['ok']) {
            $err = (string)($res['data']['error_description'] ?? $res['data']['error'] ?? $res['error'] ?? 'Unable to fetch PayPal access token.');
            return [
                'ok' => false,
                'status' => (int)$res['status'],
                'token' => '',
                'error' => $err,
                'data' => $res['data'],
            ];
        }

        $token = (string)($res['data']['access_token'] ?? '');
        if ($token === '') {
            return [
                'ok' => false,
                'status' => 502,
                'token' => '',
                'error' => 'PayPal access token was empty.',
                'data' => $res['data'],
            ];
        }

        return [
            'ok' => true,
            'status' => 200,
            'token' => $token,
            'error' => '',
            'data' => $res['data'],
        ];
    }
}

if (!function_exists('paypal_create_order')) {
    /**
     * @return array{ok:bool,status:int,data:array<string,mixed>,error:string}
     */
    function paypal_create_order(string $amount, string $currency = 'USD', array $custom = []): array
    {
        $tokenRes = paypal_get_access_token();
        if (!$tokenRes['ok']) {
            return [
                'ok' => false,
                'status' => (int)$tokenRes['status'],
                'data' => $tokenRes['data'],
                'error' => $tokenRes['error'],
            ];
        }

        $payload = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'amount' => [
                        'currency_code' => strtoupper($currency),
                        'value' => $amount,
                    ],
                ],
            ],
            'application_context' => [
                'brand_name' => 'Ripal Design',
                'user_action' => 'PAY_NOW',
            ],
        ];

        if (!empty($custom)) {
            $payload['purchase_units'][0] = array_replace_recursive($payload['purchase_units'][0], $custom);
        }

        $res = paypal_http_request(
            'POST',
            paypal_base_url() . '/v2/checkout/orders',
            [
                'Authorization' => 'Bearer ' . $tokenRes['token'],
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Prefer' => 'return=representation',
            ],
            json_encode($payload)
        );

        return [
            'ok' => $res['ok'],
            'status' => (int)$res['status'],
            'data' => $res['data'],
            'error' => $res['ok'] ? '' : (string)($res['data']['message'] ?? $res['error'] ?? 'PayPal order creation failed.'),
        ];
    }
}

if (!function_exists('paypal_capture_order')) {
    /**
     * @return array{ok:bool,status:int,data:array<string,mixed>,error:string}
     */
    function paypal_capture_order(string $orderId): array
    {
        $orderId = trim($orderId);
        if ($orderId === '') {
            return [
                'ok' => false,
                'status' => 400,
                'data' => [],
                'error' => 'Order ID is required.',
            ];
        }

        $tokenRes = paypal_get_access_token();
        if (!$tokenRes['ok']) {
            return [
                'ok' => false,
                'status' => (int)$tokenRes['status'],
                'data' => $tokenRes['data'],
                'error' => $tokenRes['error'],
            ];
        }

        $res = paypal_http_request(
            'POST',
            paypal_base_url() . '/v2/checkout/orders/' . rawurlencode($orderId) . '/capture',
            [
                'Authorization' => 'Bearer ' . $tokenRes['token'],
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Prefer' => 'return=representation',
            ],
            '{}'
        );

        return [
            'ok' => $res['ok'],
            'status' => (int)$res['status'],
            'data' => $res['data'],
            'error' => $res['ok'] ? '' : (string)($res['data']['message'] ?? $res['error'] ?? 'PayPal capture failed.'),
        ];
    }
}

if (!function_exists('payments_ensure_table')) {
    function payments_ensure_table(): bool
    {
        if (!function_exists('db_connected') || !db_connected()) {
            return false;
        }

        $sql = "CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            provider ENUM('paypal','razorpay','mock') NOT NULL DEFAULT 'paypal',
            project_id INT NULL,
            invoice_id INT NULL,
            user_id INT NULL,
            amount_paisa BIGINT NOT NULL,
            currency VARCHAR(10) NOT NULL DEFAULT 'USD',
            status ENUM('created','approved','captured','failed','cancelled','refunded') NOT NULL DEFAULT 'created',
            provider_order_id VARCHAR(128) NOT NULL,
            provider_payment_id VARCHAR(128) NULL,
            metadata_json JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_provider_order (provider, provider_order_id),
            KEY idx_payments_project (project_id),
            KEY idx_payments_invoice (invoice_id),
            KEY idx_payments_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $ok = (bool)db_query($sql);
        if (!$ok) {
            return false;
        }

        if (function_exists('db_column_exists') && !db_column_exists('payments', 'invoice_id')) {
            db_query('ALTER TABLE payments ADD COLUMN invoice_id INT NULL AFTER project_id');
            db_query('ALTER TABLE payments ADD KEY idx_payments_invoice (invoice_id)');
        }

        return true;
    }
}
