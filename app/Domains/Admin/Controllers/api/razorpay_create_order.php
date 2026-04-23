<?php
if (!defined('PROJECT_ROOT')) { require_once dirname(__DIR__, 5) . '/app/Core/Bootstrap/init.php'; }
require_once PROJECT_ROOT . '/app/Core/Bootstrap/init.php';
require_login();

$sessionUser = current_user();
$sessionRole = strtolower(trim((string)($sessionUser['role'] ?? '')));
if (!in_array($sessionRole, ['admin', 'client'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

function razorpay_create_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    razorpay_create_json(['success' => false, 'message' => 'Method not allowed.'], 405);
}

if (!razorpay_is_configured()) {
    razorpay_create_json(['success' => false, 'message' => 'Razorpay is not configured. Set RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET in .env.'], 500);
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
    razorpay_create_json(['success' => false, 'message' => 'Invalid CSRF token.'], 419);
}

$currency = strtoupper(trim((string)($body['currency'] ?? $_POST['currency'] ?? 'INR')));
if (!preg_match('/^[A-Z]{3}$/', $currency)) {
    razorpay_create_json(['success' => false, 'message' => 'Invalid currency code.'], 400);
}

$projectId = (int)($body['project_id'] ?? $_POST['project_id'] ?? 0);
$invoiceId = (int)($body['invoice_id'] ?? $_POST['invoice_id'] ?? 0);
$invoiceRow = null;
$amountRaw = (float)((string)($body['amount'] ?? $_POST['amount'] ?? '0'));

if ($invoiceId > 0) {
    if (!function_exists('billing_ensure_tables') || !billing_ensure_tables()) {
        razorpay_create_json(['success' => false, 'message' => 'Unable to prepare billing tables.'], 500);
    }

    $invoiceRow = db_fetch('SELECT * FROM billing_invoices WHERE id = ? LIMIT 1', [$invoiceId]);
    if (!$invoiceRow) {
        razorpay_create_json(['success' => false, 'message' => 'Invoice not found.'], 404);
    }

    if (!billing_user_can_access_invoice($invoiceId, current_user_id(), $sessionRole)) {
        razorpay_create_json(['success' => false, 'message' => 'You are not authorized to pay this invoice.'], 403);
    }

    $projectId = (int)($invoiceRow['project_id'] ?? 0);
    $outstanding = function_exists('billing_invoice_outstanding') ? billing_invoice_outstanding($invoiceRow) : 0.0;
    if ($outstanding <= 0) {
        razorpay_create_json(['success' => false, 'message' => 'Invoice is already fully paid.'], 400);
    }

    $amountRaw = $outstanding;
}

if ($amountRaw <= 0) {
    razorpay_create_json(['success' => false, 'message' => 'Amount must be greater than 0.'], 400);
}

if (!payments_ensure_table()) {
    razorpay_create_json(['success' => false, 'message' => 'Unable to prepare payments table.'], 500);
}

$amountPaisa = (int)round($amountRaw * 100);
$orderRes = razorpay_create_order($amountPaisa, $currency, [
    'invoice_id' => $invoiceId > 0 ? (string)$invoiceId : '',
    'project_id' => $projectId > 0 ? (string)$projectId : '',
    'user_id' => (string)current_user_id(),
]);

if (!$orderRes['ok']) {
    razorpay_create_json([
        'success' => false,
        'message' => $orderRes['error'] !== '' ? $orderRes['error'] : 'Unable to create Razorpay order.',
        'razorpay' => $orderRes['data'],
    ], $orderRes['status'] >= 400 ? $orderRes['status'] : 502);
}

$orderId = (string)($orderRes['data']['id'] ?? '');
if ($orderId === '') {
    razorpay_create_json(['success' => false, 'message' => 'Razorpay order id missing in response.'], 502);
}

$insertOk = db_query(
    'INSERT INTO payments (provider, project_id, invoice_id, user_id, amount_paisa, currency, status, provider_order_id, metadata_json) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
    [
        'razorpay',
        $projectId > 0 ? $projectId : null,
        $invoiceId > 0 ? $invoiceId : null,
        current_user_id() > 0 ? current_user_id() : null,
        $amountPaisa,
        $currency,
        'created',
        $orderId,
        json_encode($orderRes['data']),
    ]
);

if (!$insertOk) {
    razorpay_create_json(['success' => false, 'message' => 'Failed to persist payment order.'], 500);
}

razorpay_create_json([
    'success' => true,
    'message' => 'Razorpay order created.',
    'data' => [
        'order_id' => $orderId,
        'amount' => $amountPaisa,
        'currency' => $currency,
        'invoice_id' => $invoiceId > 0 ? $invoiceId : null,
        'key_id' => razorpay_key_id(),
        'name' => 'Ripal Design',
        'description' => $invoiceId > 0 ? ('Invoice #' . $invoiceId) : 'Project payment',
        'prefill' => [
            'name' => trim((string)($sessionUser['first_name'] ?? '')) . ' ' . trim((string)($sessionUser['last_name'] ?? '')),
            'email' => (string)($sessionUser['email'] ?? ''),
            'contact' => (string)($sessionUser['phone'] ?? ''),
        ],
    ],
]);
