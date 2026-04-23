<?php

if (!function_exists('payment_provider')) {
    function payment_provider(): string
    {
        $provider = strtolower(trim((string)(getenv('PAYMENT_PROVIDER') ?: 'razorpay')));
        return in_array($provider, ['paypal', 'razorpay', 'mock'], true) ? $provider : 'razorpay';
    }
}

if (!function_exists('razorpay_key_id')) {
    function razorpay_key_id(): string
    {
        return trim((string)(getenv('RAZORPAY_KEY_ID') ?: getenv('RAZORPAY_KEY') ?: ''));
    }
}

if (!function_exists('razorpay_key_secret')) {
    function razorpay_key_secret(): string
    {
        return trim((string)(getenv('RAZORPAY_KEY_SECRET') ?: ''));
    }
}

if (!function_exists('razorpay_is_configured')) {
    function razorpay_is_configured(): bool
    {
        return razorpay_key_id() !== '' && razorpay_key_secret() !== '';
    }
}

if (!function_exists('razorpay_base_url')) {
    function razorpay_base_url(): string
    {
        return 'https://api.razorpay.com';
    }
}

if (!function_exists('razorpay_http_request')) {
    /**
     * @return array{ok:bool,status:int,data:array<string,mixed>,raw:string,error:string}
     */
    function razorpay_http_request(string $method, string $url, array $headers = [], ?string $body = null): array
    {
        if (!function_exists('curl_init')) {
            return [
                'ok' => false,
                'status' => 500,
                'data' => [],
                'raw' => '',
                'error' => 'cURL extension is required for Razorpay integration.',
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
            $normalizedHeaders[] = is_int($k) ? (string)$v : ($k . ': ' . $v);
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $normalizedHeaders,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERPWD => razorpay_key_id() . ':' . razorpay_key_secret(),
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
                'error' => 'Razorpay API call failed: ' . $curlErr,
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

if (!function_exists('razorpay_create_order')) {
    /**
     * @param array<string,mixed> $notes
     * @return array{ok:bool,status:int,data:array<string,mixed>,error:string}
     */
    function razorpay_create_order(int $amountPaisa, string $currency = 'INR', array $notes = []): array
    {
        if (!razorpay_is_configured()) {
            return [
                'ok' => false,
                'status' => 500,
                'data' => [],
                'error' => 'Razorpay is not configured. Set RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET in .env.',
            ];
        }

        if ($amountPaisa <= 0) {
            return [
                'ok' => false,
                'status' => 400,
                'data' => [],
                'error' => 'Order amount must be greater than 0.',
            ];
        }

        $payload = [
            'amount' => $amountPaisa,
            'currency' => strtoupper(trim($currency)) ?: 'INR',
            'receipt' => 'rcpt_' . strtoupper(substr(bin2hex(random_bytes(6)), 0, 12)),
            'payment_capture' => 1,
        ];

        if (!empty($notes)) {
            $payload['notes'] = $notes;
        }

        $res = razorpay_http_request(
            'POST',
            razorpay_base_url() . '/v1/orders',
            [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            json_encode($payload)
        );

        return [
            'ok' => $res['ok'],
            'status' => (int)$res['status'],
            'data' => $res['data'],
            'error' => $res['ok'] ? '' : (string)($res['data']['error']['description'] ?? $res['error'] ?? 'Razorpay order creation failed.'),
        ];
    }
}

if (!function_exists('razorpay_fetch_payment')) {
    /**
     * @return array{ok:bool,status:int,data:array<string,mixed>,error:string}
     */
    function razorpay_fetch_payment(string $paymentId): array
    {
        $paymentId = trim($paymentId);
        if ($paymentId === '') {
            return [
                'ok' => false,
                'status' => 400,
                'data' => [],
                'error' => 'Payment ID is required.',
            ];
        }

        $res = razorpay_http_request(
            'GET',
            razorpay_base_url() . '/v1/payments/' . rawurlencode($paymentId),
            [
                'Accept' => 'application/json',
            ]
        );

        return [
            'ok' => $res['ok'],
            'status' => (int)$res['status'],
            'data' => $res['data'],
            'error' => $res['ok'] ? '' : (string)($res['data']['error']['description'] ?? $res['error'] ?? 'Unable to fetch Razorpay payment.'),
        ];
    }
}

if (!function_exists('razorpay_verify_signature')) {
    function razorpay_verify_signature(string $orderId, string $paymentId, string $signature): bool
    {
        $orderId = trim($orderId);
        $paymentId = trim($paymentId);
        $signature = trim($signature);

        if ($orderId === '' || $paymentId === '' || $signature === '' || !razorpay_is_configured()) {
            return false;
        }

        $expected = hash_hmac('sha256', $orderId . '|' . $paymentId, razorpay_key_secret());
        return hash_equals($expected, $signature);
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
            currency VARCHAR(10) NOT NULL DEFAULT 'INR',
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
