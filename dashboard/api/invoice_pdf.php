<?php

require_once __DIR__ . '/../../app/Core/Bootstrap/init.php';
require_login();

$invoiceId = isset($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : 0;
if ($invoiceId <= 0) {
    http_response_code(400);
    echo 'Invalid invoice id.';
    exit;
}

$sessionUser = current_user();
$sessionRole = strtolower(trim((string)($sessionUser['role'] ?? '')));
if (!in_array($sessionRole, ['admin', 'client'], true)) {
    http_response_code(403);
    echo 'Access denied.';
    exit;
}

if (!function_exists('billing_ensure_tables') || !billing_ensure_tables()) {
    http_response_code(500);
    echo 'Billing system unavailable.';
    exit;
}

if (!billing_user_can_access_invoice($invoiceId, current_user_id(), $sessionRole)) {
    http_response_code(403);
    echo 'You are not authorized to view this invoice.';
    exit;
}

$pdf = billing_generate_invoice_pdf_binary($invoiceId);
if (!$pdf['ok']) {
    http_response_code(500);
    echo 'PDF generation failed: ' . htmlspecialchars((string)$pdf['error']);
    exit;
}

$download = isset($_GET['download']) && (string)$_GET['download'] === '1';
$disposition = $download ? 'attachment' : 'inline';

header('Content-Type: application/pdf');
header('Content-Disposition: ' . $disposition . '; filename="' . str_replace('"', '', (string)$pdf['filename']) . '"');
header('Content-Length: ' . strlen((string)$pdf['binary']));
header('Cache-Control: private, max-age=0, must-revalidate');

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'HEAD') {
    echo (string)$pdf['binary'];
}
exit;
