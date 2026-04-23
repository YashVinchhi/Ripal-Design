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

function razorpay_verify_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    razorpay_verify_json(['success' => false, 'message' => 'Method not allowed.'], 405);
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
    razorpay_verify_json(['success' => false, 'message' => 'Invalid CSRF token.'], 419);
}

$orderId = trim((string)($body['razorpay_order_id'] ?? $_POST['razorpay_order_id'] ?? ''));
$paymentId = trim((string)($body['razorpay_payment_id'] ?? $_POST['razorpay_payment_id'] ?? ''));
$signature = trim((string)($body['razorpay_signature'] ?? $_POST['razorpay_signature'] ?? ''));

if ($orderId === '' || $paymentId === '' || $signature === '') {
    razorpay_verify_json(['success' => false, 'message' => 'Razorpay payment details are required.'], 400);
}

$existingPayment = db_fetch(
    'SELECT id, invoice_id, user_id, status FROM payments WHERE provider = ? AND provider_order_id = ? LIMIT 1',
    ['razorpay', $orderId]
);
if (!$existingPayment) {
    razorpay_verify_json(['success' => false, 'message' => 'Payment order not found.'], 404);
}

$invoiceIdForAccess = (int)($existingPayment['invoice_id'] ?? 0);
if ($sessionRole === 'client') {
    $isOwner = (int)($existingPayment['user_id'] ?? 0) === current_user_id();
    $hasInvoiceAccess = $invoiceIdForAccess > 0 && billing_user_can_access_invoice($invoiceIdForAccess, current_user_id(), $sessionRole);
    if (!$isOwner && !$hasInvoiceAccess) {
        razorpay_verify_json(['success' => false, 'message' => 'You are not authorized to verify this payment.'], 403);
    }
}

if ((string)($existingPayment['status'] ?? '') === 'captured') {
    razorpay_verify_json([
        'success' => true,
        'message' => 'Payment already verified.',
        'data' => [
            'order_id' => $orderId,
            'payment_id' => $paymentId,
            'invoice_id' => $invoiceIdForAccess > 0 ? $invoiceIdForAccess : null,
        ],
    ]);
}

if (!razorpay_verify_signature($orderId, $paymentId, $signature)) {
    db_query(
        'UPDATE payments SET status = ?, metadata_json = ? WHERE provider = ? AND provider_order_id = ? LIMIT 1',
        ['failed', json_encode(['signature_verification' => 'failed', 'payment_id' => $paymentId]), 'razorpay', $orderId]
    );
    razorpay_verify_json(['success' => false, 'message' => 'Razorpay signature verification failed.'], 400);
}

$paymentRes = razorpay_fetch_payment($paymentId);
if (!$paymentRes['ok']) {
    razorpay_verify_json([
        'success' => false,
        'message' => $paymentRes['error'] !== '' ? $paymentRes['error'] : 'Unable to fetch Razorpay payment.',
        'razorpay' => $paymentRes['data'],
    ], $paymentRes['status'] >= 400 ? $paymentRes['status'] : 502);
}

$paymentStatus = strtolower(trim((string)($paymentRes['data']['status'] ?? '')));
if ($paymentStatus !== 'captured' && $paymentStatus !== 'authorized') {
    db_query(
        'UPDATE payments SET status = ?, metadata_json = ? WHERE provider = ? AND provider_order_id = ? LIMIT 1',
        ['failed', json_encode($paymentRes['data']), 'razorpay', $orderId]
    );
    razorpay_verify_json(['success' => false, 'message' => 'Razorpay payment was not captured.'], 400);
}

$updated = db_query(
    'UPDATE payments SET status = ?, provider_payment_id = ?, metadata_json = ? WHERE provider = ? AND provider_order_id = ? LIMIT 1',
    [
        'captured',
        $paymentId,
        json_encode($paymentRes['data']),
        'razorpay',
        $orderId,
    ]
);

if (!$updated) {
    razorpay_verify_json(['success' => false, 'message' => 'Payment verified but failed to persist status.'], 500);
}

$paymentRow = db_fetch('SELECT id, invoice_id, amount_paisa FROM payments WHERE provider = ? AND provider_order_id = ? LIMIT 1', ['razorpay', $orderId]);
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
    'Razorpay Payment Captured',
    'A Razorpay payment was captured from Financial Gateway.',
    [
        'actor_user_id' => current_user_id(),
        'action_key' => 'payment.received',
        'deep_link' => rtrim((string)BASE_PATH, '/') . '/admin/payment_gateway.php',
    ]
);

razorpay_verify_json([
    'success' => true,
    'message' => 'Razorpay payment verified successfully.',
    'data' => [
        'order_id' => $orderId,
        'payment_id' => $paymentId,
        'invoice_id' => $invoiceId > 0 ? $invoiceId : null,
    ],
]);
