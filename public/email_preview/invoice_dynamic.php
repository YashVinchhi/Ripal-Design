<?php
// Simple dynamic preview runner for invoice_email_html()
// Defines minimal constants to avoid bootstrapping the full app.
if (!defined('PROJECT_ROOT')) define('PROJECT_ROOT', true);
if (!defined('BASE_URL')) define('BASE_URL', 'http://localhost');
require_once __DIR__ . '/../../app/Domains/Dashboard/Controllers/invoice_email_template.php';

$project = [
  'id' => 'PRJ-1001',
  'owner_name' => 'Jane Doe',
  'owner_contact' => '+91 98765 43210',
  'location' => "123 Example Road\nAhmedabad, Gujarat",
  'name' => 'Model Renovation',
];

$goods = [
  ['name'=>'Cement - 50kg','sku'=>'CEM-50','description'=>'High-strength cement','quantity'=>10,'unit'=>'BAGS','unit_price'=>550,'total_price'=>5500],
  ['name'=>'Tiles - Porcelain','sku'=>'TIL-POP','description'=>'Floor tiles 2x2 ft','quantity'=>50,'unit'=>'PCS','unit_price'=>1200,'total_price'=>60000],
];

$subtotal = array_sum(array_column($goods, 'total_price'));
$tax = round($subtotal * 0.18, 2);
$total = $subtotal + $tax;
$invoice_id = 'INV-2026-0001';
$share_url = 'http://localhost/invoice/' . $invoice_id;
$payment_link = 'http://localhost/pay/' . $invoice_id;
$qr_data_uri = null; // you may add a base64 data URI for a QR image here

echo invoice_email_html($project, $goods, $subtotal, $tax, $total, $invoice_id, $share_url, $payment_link, $qr_data_uri);
