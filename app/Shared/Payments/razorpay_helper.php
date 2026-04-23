<?php
/**
 * Minimal Razorpay Payment Link helper.
 * Expects `RAZORPAY_KEY` and `RAZORPAY_SECRET` to be provided in environment (.env).
 */
if (!function_exists('razorpay_create_payment_link')) {
    /**
     * Create a Razorpay payment link.
     * @param array $opts keys: amount (float, rupees), currency (string), description, reference_id, customer(array), notify(array), reminder_enable(bool)
     * @return array{ok:bool,url:string,id:string,error:string,response:array|null}
     */
    function razorpay_create_payment_link(array $opts): array
    {
        $apiKey = getenv('RAZORPAY_KEY') ?: getenv('RAZORPAY_API_KEY') ?: '';
        $apiSecret = getenv('RAZORPAY_SECRET') ?: getenv('RAZORPAY_API_SECRET') ?: '';
        if ($apiKey === '' || $apiSecret === '') {
            return ['ok' => false, 'url' => '', 'id' => '', 'error' => 'Razorpay credentials not configured', 'response' => null];
        }

        $amount = isset($opts['amount']) ? (float)$opts['amount'] : 0.0;
        if ($amount <= 0) {
            return ['ok' => false, 'url' => '', 'id' => '', 'error' => 'Invalid amount', 'response' => null];
        }

        $currency = strtoupper(trim((string)($opts['currency'] ?? 'INR')));
        $amountPaise = (int)round($amount * 100);

        $payload = [
            'amount' => $amountPaise,
            'currency' => $currency,
            'accept_partial' => false,
            'description' => (string)($opts['description'] ?? ''),
            'reference_id' => (string)($opts['reference_id'] ?? ''),
            'customer' => [
                'name' => (string)($opts['customer']['name'] ?? ''),
                'contact' => (string)($opts['customer']['contact'] ?? ''),
                'email' => (string)($opts['customer']['email'] ?? ''),
            ],
            'notify' => [
                'sms' => (bool)($opts['notify']['sms'] ?? false),
                'email' => (bool)($opts['notify']['email'] ?? true),
            ],
            'reminder_enable' => (bool)($opts['reminder_enable'] ?? true),
        ];

        $url = 'https://api.razorpay.com/v1/payment_links';
        if (!function_exists('curl_version')) {
            return ['ok' => false, 'url' => '', 'id' => '', 'error' => 'cURL extension not available', 'response' => null];
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $apiKey . ':' . $apiSecret);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $resp = curl_exec($ch);
        if ($resp === false) {
            $err = curl_error($ch);
            curl_close($ch);
            return ['ok' => false, 'url' => '', 'id' => '', 'error' => 'cURL error: ' . $err, 'response' => null];
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $dec = json_decode($resp, true);
        if (!is_array($dec)) {
            return ['ok' => false, 'url' => '', 'id' => '', 'error' => 'Invalid JSON response from Razorpay: ' . $resp, 'response' => null];
        }

        if ($httpCode >= 400) {
            $errMsg = '';
            if (!empty($dec['error']['description'])) {
                $errMsg = $dec['error']['description'];
            } elseif (!empty($dec['error'])) {
                $errMsg = is_string($dec['error']) ? $dec['error'] : json_encode($dec['error']);
            } else {
                $errMsg = json_encode($dec);
            }
            return ['ok' => false, 'url' => '', 'id' => $dec['id'] ?? '', 'error' => 'Razorpay API error: ' . $errMsg, 'response' => $dec];
        }

        $link = $dec['short_url'] ?? $dec['short_link'] ?? $dec['long_url'] ?? $dec['short_url'] ?? '';
        if (empty($link) && !empty($dec['id'])) {
            $link = 'https://rzp.io/i/' . $dec['id'];
        }

        return ['ok' => true, 'url' => $link, 'id' => $dec['id'] ?? '', 'error' => '', 'response' => $dec];
    }
}
