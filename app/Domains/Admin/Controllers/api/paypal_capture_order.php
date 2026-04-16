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

function paypal_capture_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    paypal_capture_json(['success' => false, 'message' => 'Method not allowed.'], 405);
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
    paypal_capture_json(['success' => false, 'message' => 'Invalid CSRF token.'], 419);
}

$orderId = trim((string)($body['order_id'] ?? $_POST['order_id'] ?? ''));
if ($orderId === '') {
    paypal_capture_json(['success' => false, 'message' => 'Order id is required.'], 400);
}

$existingPayment = db_fetch('SELECT id, invoice_id, user_id FROM payments WHERE provider = ? AND provider_order_id = ? LIMIT 1', ['paypal', $orderId]);
if (!$existingPayment) {
    paypal_capture_json(['success' => false, 'message' => 'Payment order not found.'], 404);
}

$invoiceIdForAccess = (int)($existingPayment['invoice_id'] ?? 0);
if ($sessionRole === 'client') {
    $isOwner = (int)($existingPayment['user_id'] ?? 0) === current_user_id();
    $hasInvoiceAccess = $invoiceIdForAccess > 0 && billing_user_can_access_invoice($invoiceIdForAccess, current_user_id(), $sessionRole);
    if (!$isOwner && !$hasInvoiceAccess) {
        paypal_capture_json(['success' => false, 'message' => 'You are not authorized to capture this payment.'], 403);
    }
}

$captureRes = paypal_capture_order($orderId);
if (!$captureRes['ok']) {
    $details = $captureRes['data'];
    $isDuplicate = (string)($details['name'] ?? '') === 'UNPROCESSABLE_ENTITY';

    if ($isDuplicate && payments_ensure_table()) {
        db_query(
            'UPDATE payments SET status = ?, metadata_json = ? WHERE provider = ? AND provider_order_id = ? LIMIT 1',
            ['captured', json_encode($details), 'paypal', $orderId]
        );
        paypal_capture_json([
            'success' => true,
            'message' => 'Order already captured.',
            'data' => ['order_id' => $orderId],
        ]);
    }

    paypal_capture_json([
        'success' => false,
        'message' => $captureRes['error'] !== '' ? $captureRes['error'] : 'Unable to capture PayPal order.',
        'paypal' => $details,
    ], $captureRes['status'] >= 400 ? $captureRes['status'] : 502);
}

$captureId = '';
if (isset($captureRes['data']['purchase_units'][0]['payments']['captures'][0]['id'])) {
    $captureId = (string)$captureRes['data']['purchase_units'][0]['payments']['captures'][0]['id'];
}

if (!payments_ensure_table()) {
    paypal_capture_json(['success' => false, 'message' => 'Unable to prepare payments table.'], 500);
}

$updated = db_query(
    'UPDATE payments SET status = ?, provider_payment_id = ?, metadata_json = ? WHERE provider = ? AND provider_order_id = ? LIMIT 1',
    [
        'captured',
        $captureId !== '' ? $captureId : null,
        json_encode($captureRes['data']),
        'paypal',
        $orderId,
    ]
);

if (!$updated) {
    paypal_capture_json(['success' => false, 'message' => 'Payment captured but failed to persist status.'], 500);
}

$paymentRow = db_fetch('SELECT id, invoice_id, amount_paisa FROM payments WHERE provider = ? AND provider_order_id = ? LIMIT 1', ['paypal', $orderId]);
$invoiceId = (int)($paymentRow['invoice_id'] ?? 0);
if ($invoiceId > 0 && function_exists('billing_ensure_tables') && billing_ensure_tables()) {
    $capturedAmount = ((int)($paymentRow['amount_paisa'] ?? 0)) / 100;
    db_query('UPDATE billing_invoices SET amount_paid = amount_paid + ? WHERE id = ? LIMIT 1', [$capturedAmount, $invoiceId]);
    if (function_exists('billing_recalculate_invoice_status')) {
        billing_recalculate_invoice_status($invoiceId);
    }
}

notifications_notify_admins(
    'payment',
    'PayPal Test Payment Captured',
    'A PayPal sandbox payment was captured from Financial Gateway.',
    [
        'actor_user_id' => current_user_id(),
        'action_key' => 'payment.received',
        'deep_link' => rtrim((string)BASE_PATH, '/') . '/admin/payment_gateway.php',
    ]
);

paypal_capture_json([
    'success' => true,
    'message' => 'PayPal payment captured successfully.',
    'data' => [
        'order_id' => $orderId,
        'capture_id' => $captureId,
        'invoice_id' => $invoiceId > 0 ? $invoiceId : null,
    ],
]);
