<?php

require_once __DIR__ . '/../../includes/init.php';
require_login();

$sessionUser = current_user();
$sessionRole = strtolower(trim((string)($sessionUser['role'] ?? '')));
if (!in_array($sessionRole, ['admin', 'client'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

function paypal_create_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    paypal_create_json(['success' => false, 'message' => 'Method not allowed.'], 405);
}

if (!paypal_is_configured()) {
    paypal_create_json(['success' => false, 'message' => 'PayPal is not configured. Set PAYPAL_CLIENT_ID and PAYPAL_CLIENT_SECRET in .env.'], 500);
}

$contentType = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
$isJson = strpos($contentType, 'application/json') !== false;
$body = [];

if ($isJson) {
    $raw = file_get_contents('php://input');
    $decoded = json_decode((string)$raw, true);
    $body = is_array($decoded) ? $decoded : [];
}

$csrfToken = (string)($body['csrf_token'] ?? $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!csrf_validate($csrfToken)) {
    paypal_create_json(['success' => false, 'message' => 'Invalid CSRF token.'], 419);
}

$amountInput = (string)($body['amount'] ?? $_POST['amount'] ?? '10.00');
$amountRaw = (float)$amountInput;
if ($amountRaw <= 0) {
    paypal_create_json(['success' => false, 'message' => 'Amount must be greater than 0.'], 400);
}

$currency = strtoupper(trim((string)($body['currency'] ?? $_POST['currency'] ?? 'USD')));
if (!preg_match('/^[A-Z]{3}$/', $currency)) {
    paypal_create_json(['success' => false, 'message' => 'Invalid currency code.'], 400);
}

$amount = number_format($amountRaw, 2, '.', '');
$projectId = (int)($body['project_id'] ?? $_POST['project_id'] ?? 0);
$invoiceId = (int)($body['invoice_id'] ?? $_POST['invoice_id'] ?? 0);

$invoiceRow = null;
if ($invoiceId > 0) {
    if (!function_exists('billing_ensure_tables') || !billing_ensure_tables()) {
        paypal_create_json(['success' => false, 'message' => 'Unable to prepare billing tables.'], 500);
    }

    $invoiceRow = db_fetch('SELECT * FROM billing_invoices WHERE id = ? LIMIT 1', [$invoiceId]);
    if (!$invoiceRow) {
        paypal_create_json(['success' => false, 'message' => 'Invoice not found.'], 404);
    }

    if (!billing_user_can_access_invoice($invoiceId, current_user_id(), $sessionRole)) {
        paypal_create_json(['success' => false, 'message' => 'You are not authorized to pay this invoice.'], 403);
    }

    $projectId = (int)($invoiceRow['project_id'] ?? 0);
    $outstanding = function_exists('billing_invoice_outstanding') ? billing_invoice_outstanding($invoiceRow) : 0.0;
    if ($outstanding <= 0) {
        paypal_create_json(['success' => false, 'message' => 'Invoice is already fully paid.'], 400);
    }

    // For invoice collections we always collect outstanding amount to keep accounting consistent.
    $amountRaw = $outstanding;
    $amount = number_format($amountRaw, 2, '.', '');
}

$orderRes = paypal_create_order($amount, $currency, []);
if (!$orderRes['ok']) {
    paypal_create_json([
        'success' => false,
        'message' => $orderRes['error'] !== '' ? $orderRes['error'] : 'Unable to create PayPal order.',
        'paypal' => $orderRes['data'],
    ], $orderRes['status'] >= 400 ? $orderRes['status'] : 502);
}

$orderId = (string)($orderRes['data']['id'] ?? '');
if ($orderId === '') {
    paypal_create_json(['success' => false, 'message' => 'PayPal order id missing in response.'], 502);
}

if (!payments_ensure_table()) {
    paypal_create_json(['success' => false, 'message' => 'Unable to prepare payments table.'], 500);
}

$userId = current_user_id();
$amountPaisa = (int)round($amountRaw * 100);
$insertOk = db_query(
    'INSERT INTO payments (provider, project_id, invoice_id, user_id, amount_paisa, currency, status, provider_order_id, metadata_json) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
    [
        'paypal',
        $projectId > 0 ? $projectId : null,
        $invoiceId > 0 ? $invoiceId : null,
        $userId > 0 ? $userId : null,
        $amountPaisa,
        $currency,
        'created',
        $orderId,
        json_encode($orderRes['data']),
    ]
);

if (!$insertOk) {
    paypal_create_json(['success' => false, 'message' => 'Failed to persist payment order.'], 500);
}

paypal_create_json([
    'success' => true,
    'message' => 'PayPal order created.',
    'data' => [
        'order_id' => $orderId,
        'currency' => $currency,
        'amount' => $amount,
        'invoice_id' => $invoiceId > 0 ? $invoiceId : null,
    ],
]);
