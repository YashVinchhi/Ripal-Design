<?php
if (!defined('PROJECT_ROOT')) { require_once dirname(__DIR__, 4) . '/app/Core/Bootstrap/init.php'; }
/**
 * Unified Billing & Collections Workspace
 */
require_once PROJECT_ROOT . '/app/Core/Bootstrap/init.php';
require_login();
require_role('admin');

$razorpayKeyId = trim((string)(getenv('RAZORPAY_KEY_ID') ?: getenv('RAZORPAY_KEY') ?: ''));
$razorpayKeySecret = trim((string)(getenv('RAZORPAY_KEY_SECRET') ?: ''));
$isRazorpayConfigured = $razorpayKeyId !== '' && $razorpayKeySecret !== '';
$paymentCurrency = strtoupper(trim((string)(getenv('PAYMENT_CURRENCY') ?: 'INR')));

$billingReady = function_exists('billing_ensure_tables') && billing_ensure_tables();
$paymentsReady = function_exists('payments_ensure_table') && payments_ensure_table();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_csrf();
    $action = (string)($_POST['action'] ?? '');

    if (!$billingReady || !$paymentsReady) {
        set_flash('Billing system is not available. Check DB connection and table permissions.', 'error');
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    if ($action === 'create_invoice') {
        $projectId = (int)($_POST['project_id'] ?? 0);
        $taxRate = (float)($_POST['tax_rate'] ?? 18);
        $discount = (float)($_POST['discount_amount'] ?? 0);
        $dueDate = trim((string)($_POST['due_date'] ?? ''));
        $notes = trim((string)($_POST['notes'] ?? ''));

        $taxRate = max(0, min($taxRate, 100));
        $discount = max(0, $discount);

        $financials = function_exists('billing_get_project_financials') ? billing_get_project_financials($projectId) : null;
        if (!$financials) {
            set_flash('Unable to fetch project financials. Select a valid project.', 'error');
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        $baseFee = (float)($financials['base_fee'] ?? 0);
        $goodsTotal = (float)($financials['goods_total'] ?? 0);
        $subTotal = $baseFee + $goodsTotal;
        $taxAmount = round(($subTotal * $taxRate) / 100, 2);
        $invoiceTotal = round(max(0, $subTotal + $taxAmount - $discount), 2);

        if ($invoiceTotal <= 0) {
            set_flash('Invoice total must be greater than zero.', 'error');
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        $invoiceCode = function_exists('billing_generate_invoice_code')
            ? billing_generate_invoice_code($projectId)
            : ('BIL-' . $projectId . '-' . date('YmdHis'));

        $ok = db_query(
            'INSERT INTO billing_invoices (invoice_code, project_id, client_name, client_contact, client_email, base_fee, goods_total, tax_rate, tax_amount, discount_amount, total_amount, amount_paid, due_date, status, notes, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?)',
            [
                $invoiceCode,
                $projectId,
                (string)($financials['client_name'] ?? 'Client'),
                (string)($financials['client_contact'] ?? ''),
                (string)($financials['client_email'] ?? ''),
                $baseFee,
                $goodsTotal,
                $taxRate,
                $taxAmount,
                $discount,
                $invoiceTotal,
                $dueDate !== '' ? $dueDate : null,
                'issued',
                $notes,
                current_user_id(),
            ]
        );

        if ($ok) {
            set_flash('Invoice created successfully: ' . $invoiceCode, 'success');
        } else {
            set_flash('Failed to create invoice.', 'error');
        }

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    if ($action === 'set_invoice_status') {
        $invoiceId = (int)($_POST['invoice_id'] ?? 0);
        $status = strtolower(trim((string)($_POST['status'] ?? 'issued')));
        $allowed = ['issued', 'cancelled'];
        if ($invoiceId > 0 && in_array($status, $allowed, true)) {
            db_query('UPDATE billing_invoices SET status = ? WHERE id = ? LIMIT 1', [$status, $invoiceId]);
            if ($status !== 'cancelled' && function_exists('billing_recalculate_invoice_status')) {
                billing_recalculate_invoice_status($invoiceId);
            }
            set_flash('Invoice status updated.', 'success');
        } else {
            set_flash('Invalid invoice status request.', 'error');
        }
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    if ($action === 'record_manual_payment') {
        $invoiceId = (int)($_POST['invoice_id'] ?? 0);
        $manualAmount = (float)($_POST['manual_amount'] ?? 0);

        $invoice = db_fetch('SELECT * FROM billing_invoices WHERE id = ? LIMIT 1', [$invoiceId]);
        if (!$invoice) {
            set_flash('Invoice not found.', 'error');
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        $outstanding = function_exists('billing_invoice_outstanding') ? billing_invoice_outstanding($invoice) : 0.0;
        if ($outstanding <= 0) {
            set_flash('Invoice is already fully paid.', 'info');
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        if ($manualAmount <= 0 || $manualAmount > $outstanding) {
            $manualAmount = $outstanding;
        }

        $updated = db_query('UPDATE billing_invoices SET amount_paid = amount_paid + ? WHERE id = ? LIMIT 1', [$manualAmount, $invoiceId]);
        if ($updated) {
            if (function_exists('billing_recalculate_invoice_status')) {
                billing_recalculate_invoice_status($invoiceId);
            }

            $orderId = 'manual-' . $invoiceId . '-' . time();
            db_query(
                'INSERT INTO payments (provider, project_id, invoice_id, user_id, amount_paisa, currency, status, provider_order_id, provider_payment_id, metadata_json)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    'mock',
                    (int)($invoice['project_id'] ?? 0),
                    $invoiceId,
                    current_user_id(),
                    (int)round($manualAmount * 100),
                    'INR',
                    'captured',
                    $orderId,
                    $orderId,
                    json_encode(['source' => 'manual-entry', 'note' => 'Recorded from billing workspace']),
                ]
            );

            set_flash('Manual payment recorded.', 'success');
        } else {
            set_flash('Failed to record manual payment.', 'error');
        }

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    if ($action === 'send_invoice_email') {
        $invoiceId = (int)($_POST['invoice_id'] ?? 0);
        if ($invoiceId <= 0) {
            set_flash('Invalid invoice for email dispatch.', 'error');
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        $result = function_exists('billing_send_invoice_email')
            ? billing_send_invoice_email($invoiceId)
            : ['ok' => false, 'error' => 'Email helper unavailable.'];

        if (!empty($result['ok'])) {
            set_flash('Invoice email sent successfully to ' . (string)($result['target'] ?? 'client') . '.', 'success');
        } else {
            set_flash('Failed to send invoice email. ' . (string)($result['error'] ?? ''), 'error');
        }

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

if ($billingReady) {
    db_query("UPDATE billing_invoices
        SET status = 'overdue'
        WHERE status IN ('issued','partially_paid')
          AND due_date IS NOT NULL
          AND due_date < CURDATE()
          AND amount_paid < total_amount");
}

$projectFinancialRows = [];
$invoiceRows = [];
$recentPaymentRows = [];

if ($billingReady) {
    $projectFinancialRows = db_fetch_all("SELECT
        p.id,
        p.name,
        COALESCE(p.budget,0) AS budget,
        COALESCE(p.owner_name,'Client') AS client_name,
        COALESCE(p.owner_email,'') AS client_email,
        COALESCE((SELECT SUM(pg.total_price) FROM project_goods pg WHERE pg.project_id = p.id), 0) AS goods_total,
        COALESCE((SELECT SUM(bi.total_amount) FROM billing_invoices bi WHERE bi.project_id = p.id AND bi.status <> 'cancelled'), 0) AS total_invoiced,
        COALESCE((SELECT SUM(bi.amount_paid) FROM billing_invoices bi WHERE bi.project_id = p.id AND bi.status <> 'cancelled'), 0) AS total_collected,
        COALESCE((SELECT COUNT(*) FROM billing_invoices bi WHERE bi.project_id = p.id), 0) AS invoice_count
        FROM projects p
        ORDER BY p.id DESC
        LIMIT 300");

    $invoiceRows = db_fetch_all("SELECT
        bi.*, p.name AS project_name
        FROM billing_invoices bi
        LEFT JOIN projects p ON p.id = bi.project_id
        ORDER BY bi.id DESC
        LIMIT 120");
}

if ($paymentsReady) {
    $recentPaymentRows = db_fetch_all("SELECT
        pay.id,
        pay.provider,
        pay.project_id,
        pay.invoice_id,
        pay.amount_paisa,
        pay.currency,
        pay.status,
        pay.provider_order_id,
        pay.provider_payment_id,
        pay.created_at,
        bi.invoice_code,
        p.name AS project_name
        FROM payments pay
        LEFT JOIN billing_invoices bi ON bi.id = pay.invoice_id
        LEFT JOIN projects p ON p.id = pay.project_id
        ORDER BY pay.id DESC
        LIMIT 80");
}

$invoiceTotals = [
    'invoiced' => 0.0,
    'collected' => 0.0,
    'outstanding' => 0.0,
    'overdue_count' => 0,
    'open_count' => 0,
    'paid_count' => 0,
];

foreach ($invoiceRows as $row) {
    $total = (float)($row['total_amount'] ?? 0);
    $paid = (float)($row['amount_paid'] ?? 0);
    $status = strtolower((string)($row['status'] ?? 'issued'));
    $out = max(0, round($total - $paid, 2));

    if ($status !== 'cancelled') {
        $invoiceTotals['invoiced'] += $total;
        $invoiceTotals['collected'] += $paid;
        $invoiceTotals['outstanding'] += $out;
    }

    if (in_array($status, ['issued', 'partially_paid', 'overdue'], true)) {
        $invoiceTotals['open_count']++;
    }
    if ($status === 'overdue') {
        $invoiceTotals['overdue_count']++;
    }
    if ($status === 'paid') {
        $invoiceTotals['paid_count']++;
    }
}

$unbilledProjects = 0;
$totalProjectBudget = 0.0;
$totalGoodsCatalog = 0.0;
foreach ($projectFinancialRows as $pRow) {
    $totalProjectBudget += (float)($pRow['budget'] ?? 0);
    $totalGoodsCatalog += (float)($pRow['goods_total'] ?? 0);
    if ((int)($pRow['invoice_count'] ?? 0) === 0) {
        $unbilledProjects++;
    }
}

$statusClass = static function (string $status): string {
    $s = strtolower(trim($status));
    if ($s === 'paid') return 'text-approval-green';
    if ($s === 'overdue') return 'text-red-600';
    if ($s === 'partially_paid') return 'text-slate-accent';
    if ($s === 'issued') return 'text-pending-amber';
    if ($s === 'cancelled') return 'text-gray-500';
    return 'text-foundation-grey';
};
?>
<!DOCTYPE html>
<html lang="en" class="bg-canvas-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Revenue & Billing Workspace | Ripal Design</title>
    <?php $HEADER_MODE = 'dashboard'; require_once PROJECT_ROOT . '/Common/header.php'; ?>
</head>
<body class="bg-canvas-white font-sans text-foundation-grey min-h-screen">
<div class="min-h-screen flex flex-col">
    <header class="bg-foundation-grey text-white pt-20 md:pt-24 pb-8 md:pb-12 px-4 sm:px-6 lg:px-8 shadow-lg mb-8 md:mb-12 border-b-2 border-rajkot-rust">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <h1 class="text-3xl md:text-4xl font-serif font-bold">Revenue & Billing Workspace</h1>
                <p class="text-gray-400 mt-2 text-sm uppercase tracking-widest font-bold opacity-70">Project fees, goods billing, invoice lifecycle, and collections in one command center.</p>
            </div>
            <div class="bg-white/10 border border-white/20 rounded px-5 py-4">
                <p class="text-[10px] uppercase tracking-[0.2em] text-gray-300 font-bold">Payment Provider</p>
                <p class="font-bold text-lg mt-1"><?php echo esc(strtoupper((string)(getenv('PAYMENT_PROVIDER') ?: 'razorpay'))); ?> / LIVE API</p>
                <p class="text-[11px] text-gray-400 mt-1"><?php echo $isRazorpayConfigured ? 'Razorpay API is configured' : 'Razorpay credentials missing'; ?></p>
            </div>
        </div>
    </header>

    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20 space-y-10">
        <?php if (function_exists('render_flash')) { render_flash(); } ?>

        <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-6 gap-4">
            <div class="bg-white p-5 border border-gray-100 shadow-premium">
                <p class="text-[10px] uppercase tracking-[0.2em] text-gray-400 font-bold">Total Invoiced</p>
                <p class="text-xl font-serif font-bold mt-2">₹<?php echo number_format($invoiceTotals['invoiced'], 2); ?></p>
            </div>
            <div class="bg-white p-5 border border-gray-100 shadow-premium">
                <p class="text-[10px] uppercase tracking-[0.2em] text-gray-400 font-bold">Collected</p>
                <p class="text-xl font-serif font-bold mt-2 text-approval-green">₹<?php echo number_format($invoiceTotals['collected'], 2); ?></p>
            </div>
            <div class="bg-white p-5 border border-gray-100 shadow-premium">
                <p class="text-[10px] uppercase tracking-[0.2em] text-gray-400 font-bold">Outstanding</p>
                <p class="text-xl font-serif font-bold mt-2 text-pending-amber">₹<?php echo number_format($invoiceTotals['outstanding'], 2); ?></p>
            </div>
            <div class="bg-white p-5 border border-gray-100 shadow-premium">
                <p class="text-[10px] uppercase tracking-[0.2em] text-gray-400 font-bold">Overdue</p>
                <p class="text-xl font-serif font-bold mt-2 text-red-600"><?php echo (int)$invoiceTotals['overdue_count']; ?></p>
            </div>
            <div class="bg-white p-5 border border-gray-100 shadow-premium">
                <p class="text-[10px] uppercase tracking-[0.2em] text-gray-400 font-bold">Unbilled Projects</p>
                <p class="text-xl font-serif font-bold mt-2"><?php echo (int)$unbilledProjects; ?></p>
            </div>
            <div class="bg-white p-5 border border-gray-100 shadow-premium">
                <p class="text-[10px] uppercase tracking-[0.2em] text-gray-400 font-bold">Goods Catalog Value</p>
                <p class="text-xl font-serif font-bold mt-2">₹<?php echo number_format($totalGoodsCatalog, 2); ?></p>
            </div>
        </section>

        <section class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <div class="xl:col-span-1 bg-white border border-gray-100 shadow-premium p-6">
                <h2 class="text-sm font-bold uppercase tracking-[0.3em] text-foundation-grey mb-4">Create Client Bill</h2>
                <form method="post" class="space-y-4">
                    <?php echo csrf_token_field(); ?>
                    <input type="hidden" name="action" value="create_invoice">

                    <div>
                        <label class="block text-[10px] uppercase tracking-[0.2em] text-gray-500 font-bold mb-2">Project</label>
                        <select name="project_id" required class="w-full border border-gray-200 px-3 py-2 text-sm outline-none focus:border-rajkot-rust bg-white">
                            <option value="">Select project</option>
                            <?php foreach ($projectFinancialRows as $p): ?>
                                <option value="<?php echo (int)$p['id']; ?>"><?php echo esc((string)$p['name']); ?> | Budget $<?php echo number_format((float)$p['budget'], 2); ?> | Goods $<?php echo number_format((float)$p['goods_total'], 2); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] uppercase tracking-[0.2em] text-gray-500 font-bold mb-2">Tax %</label>
                            <input type="number" name="tax_rate" value="18" min="0" max="100" step="0.01" class="w-full border border-gray-200 px-3 py-2 text-sm outline-none focus:border-rajkot-rust">
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase tracking-[0.2em] text-gray-500 font-bold mb-2">Discount</label>
                            <input type="number" name="discount_amount" value="0" min="0" step="0.01" class="w-full border border-gray-200 px-3 py-2 text-sm outline-none focus:border-rajkot-rust">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] uppercase tracking-[0.2em] text-gray-500 font-bold mb-2">Due Date</label>
                        <input type="date" name="due_date" class="w-full border border-gray-200 px-3 py-2 text-sm outline-none focus:border-rajkot-rust">
                    </div>

                    <div>
                        <label class="block text-[10px] uppercase tracking-[0.2em] text-gray-500 font-bold mb-2">Billing Notes</label>
                        <textarea name="notes" rows="3" class="w-full border border-gray-200 px-3 py-2 text-sm outline-none focus:border-rajkot-rust" placeholder="Scope note, milestone note, payment terms..."></textarea>
                    </div>

                    <button type="submit" class="w-full bg-rajkot-rust text-white px-5 py-3 text-[10px] font-bold uppercase tracking-[0.2em] hover:bg-red-700 transition-all">Generate Invoice</button>
                </form>
            </div>

            <div class="xl:col-span-2 bg-white border border-gray-100 shadow-premium overflow-hidden">
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-sm font-bold uppercase tracking-[0.3em] text-foundation-grey">Project Billing Matrix</h2>
                    <span class="text-xs text-gray-500"><?php echo count($projectFinancialRows); ?> projects mapped</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm admin-table">
                        <thead>
                        <tr class="text-[10px] uppercase tracking-[0.2em] text-gray-400 border-b border-gray-100">
                            <th class="px-6 py-4">Project</th>
                            <th class="px-4 py-4">Budget</th>
                            <th class="px-4 py-4">Goods</th>
                            <th class="px-4 py-4">Invoiced</th>
                            <th class="px-4 py-4">Collected</th>
                            <th class="px-4 py-4">Open</th>
                            <th class="px-6 py-4 text-right">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($projectFinancialRows as $p): ?>
                            <?php
                                $open = max(0, round((float)$p['total_invoiced'] - (float)$p['total_collected'], 2));
                                $goodsInvoiceLink = base_path('dashboard/goods_invoice.php?project_id=' . (int)$p['id']);
                            ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50/40">
                                <td class="px-6 py-4" data-label="Project">
                                    <p class="font-semibold text-foundation-grey"><?php echo esc((string)$p['name']); ?></p>
                                    <p class="text-xs text-gray-400"><?php echo esc((string)$p['client_name']); ?></p>
                                </td>
                                <td class="px-4 py-4" data-label="Budget">₹<?php echo number_format((float)$p['budget'], 2); ?></td>
                                <td class="px-4 py-4" data-label="Goods">₹<?php echo number_format((float)$p['goods_total'], 2); ?></td>
                                <td class="px-4 py-4" data-label="Invoiced">₹<?php echo number_format((float)$p['total_invoiced'], 2); ?></td>
                                <td class="px-4 py-4 text-approval-green font-semibold" data-label="Collected">₹<?php echo number_format((float)$p['total_collected'], 2); ?></td>
                                <td class="px-4 py-4 text-pending-amber font-semibold" data-label="Open">₹<?php echo number_format($open, 2); ?></td>
                                <td class="px-6 py-4 text-right" data-label="Action">
                                    <a href="<?php echo esc_attr($goodsInvoiceLink); ?>" class="text-xs uppercase tracking-widest font-bold text-rajkot-rust hover:text-red-700">Goods Invoice</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="bg-white border border-gray-100 shadow-premium overflow-hidden">
            <div class="px-6 md:px-8 py-5 border-b border-gray-100 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <h2 class="text-sm font-bold uppercase tracking-[0.3em] text-foundation-grey">Invoice Lifecycle Registry</h2>
                <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                    <input id="invoiceSearch" type="search" placeholder="Search by invoice, project, client" class="w-full sm:w-72 border border-gray-200 rounded-none px-3 py-2 text-xs outline-none focus:border-rajkot-rust">
                    <select id="invoiceStatusFilter" class="border border-gray-200 px-3 py-2 text-xs outline-none focus:border-rajkot-rust bg-white min-w-[170px]">
                        <option value="all">All Statuses</option>
                        <option value="issued">Issued</option>
                        <option value="partially_paid">Partially Paid</option>
                        <option value="paid">Paid</option>
                        <option value="overdue">Overdue</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>

            <div id="gatewayNotice" class="px-6 md:px-8 py-3 text-xs bg-gray-50 border-b border-gray-100 text-gray-500">Use Razorpay checkout to collect outstanding invoice balances instantly.</div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm admin-table">
                    <thead>
                    <tr class="text-[10px] uppercase tracking-[0.2em] text-gray-400 border-b border-gray-100">
                        <th class="px-6 py-4">Invoice</th>
                        <th class="px-4 py-4">Project</th>
                        <th class="px-4 py-4">Client</th>
                        <th class="px-4 py-4">Total</th>
                        <th class="px-4 py-4">Paid</th>
                        <th class="px-4 py-4">Outstanding</th>
                        <th class="px-4 py-4">Due</th>
                        <th class="px-4 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Operations</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($invoiceRows)): ?>
                        <tr><td colspan="9" class="px-6 py-12 text-gray-400">No invoices yet. Create your first client bill from the form above.</td></tr>
                    <?php else: ?>
                        <?php foreach ($invoiceRows as $inv): ?>
                            <?php
                                $invTotal = (float)($inv['total_amount'] ?? 0);
                                $invPaid = (float)($inv['amount_paid'] ?? 0);
                                $invOut = max(0, round($invTotal - $invPaid, 2));
                                $invStatus = strtolower((string)($inv['status'] ?? 'issued'));
                                $goodsInvoiceLink = base_path('dashboard/goods_invoice.php?project_id=' . (int)$inv['project_id']);
                                $pdfInvoiceLink = base_path('dashboard/api/invoice_pdf.php?invoice_id=' . (int)$inv['id'] . '&download=1');
                            ?>
                            <tr class="invoice-row border-b border-gray-50 hover:bg-gray-50/40"
                                data-invoice="<?php echo esc_attr((string)$inv['invoice_code']); ?>"
                                data-project="<?php echo esc_attr((string)($inv['project_name'] ?? '')); ?>"
                                data-client="<?php echo esc_attr((string)($inv['client_name'] ?? '')); ?>"
                                data-status="<?php echo esc_attr($invStatus); ?>"
                                data-total="<?php echo esc_attr(number_format($invTotal, 2, '.', '')); ?>"
                                data-paid="<?php echo esc_attr(number_format($invPaid, 2, '.', '')); ?>"
                                data-outstanding="<?php echo esc_attr(number_format($invOut, 2, '.', '')); ?>">
                                <td class="px-6 py-4" data-label="Invoice">
                                    <div class="font-mono text-xs font-semibold"><?php echo esc((string)$inv['invoice_code']); ?></div>
                                    <div class="text-xs text-gray-400">ID #<?php echo (int)$inv['id']; ?></div>
                                </td>
                                <td class="px-4 py-4" data-label="Project"><?php echo esc((string)($inv['project_name'] ?? 'Project')); ?></td>
                                <td class="px-4 py-4" data-label="Client">
                                    <div><?php echo esc((string)($inv['client_name'] ?? 'Client')); ?></div>
                                    <div class="text-xs text-gray-400"><?php echo esc((string)($inv['client_email'] ?? '')); ?></div>
                                </td>
                                <td class="px-4 py-4 font-semibold" data-label="Total">₹<?php echo number_format($invTotal, 2); ?></td>
                                <td class="px-4 py-4 text-approval-green font-semibold" data-label="Paid">₹<?php echo number_format($invPaid, 2); ?></td>
                                <td class="px-4 py-4 text-pending-amber font-semibold" data-label="Outstanding">₹<?php echo number_format($invOut, 2); ?></td>
                                <td class="px-4 py-4" data-label="Due"><?php echo !empty($inv['due_date']) ? esc((string)$inv['due_date']) : 'N/A'; ?></td>
                                <td class="px-4 py-4" data-label="Status"><span class="text-[10px] uppercase tracking-widest font-bold <?php echo esc_attr($statusClass($invStatus)); ?>"><?php echo esc(strtoupper($invStatus)); ?></span></td>
                                <td class="px-6 py-4" data-label="Operations">
                                    <div class="flex flex-col gap-2 items-stretch md:items-end">
                                        <div class="flex flex-wrap gap-2 md:justify-end">
                                            <a href="<?php echo esc_attr($goodsInvoiceLink); ?>" class="text-[10px] uppercase tracking-widest font-bold text-foundation-grey hover:text-rajkot-rust">Open Invoice</a>
                                            <a href="<?php echo esc_attr($pdfInvoiceLink); ?>" class="text-[10px] uppercase tracking-widest font-bold text-foundation-grey hover:text-rajkot-rust">PDF</a>
                                            <form method="post" class="inline-block">
                                                <?php echo csrf_token_field(); ?>
                                                <input type="hidden" name="action" value="send_invoice_email">
                                                <input type="hidden" name="invoice_id" value="<?php echo (int)$inv['id']; ?>">
                                                <button type="submit" class="text-[10px] uppercase tracking-widest font-bold text-slate-accent hover:text-foundation-grey">Email</button>
                                            </form>
                                            <?php if ($invStatus !== 'cancelled' && $invStatus !== 'paid'): ?>
                                                <form method="post" class="inline-block">
                                                    <?php echo csrf_token_field(); ?>
                                                    <input type="hidden" name="action" value="set_invoice_status">
                                                    <input type="hidden" name="invoice_id" value="<?php echo (int)$inv['id']; ?>">
                                                    <input type="hidden" name="status" value="cancelled">
                                                    <button type="submit" class="text-[10px] uppercase tracking-widest font-bold text-red-600 hover:text-red-700">Cancel</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>

                                        <?php if ($invOut > 0 && $invStatus !== 'cancelled'): ?>
                                            <form method="post" class="flex gap-2 items-center md:justify-end">
                                                <?php echo csrf_token_field(); ?>
                                                <input type="hidden" name="action" value="record_manual_payment">
                                                <input type="hidden" name="invoice_id" value="<?php echo (int)$inv['id']; ?>">
                                                <input type="number" name="manual_amount" min="0.01" max="<?php echo esc_attr(number_format($invOut, 2, '.', '')); ?>" step="0.01" placeholder="Manual" class="w-24 border border-gray-200 px-2 py-1 text-xs outline-none focus:border-rajkot-rust">
                                                <button type="submit" class="text-[10px] uppercase tracking-widest font-bold text-slate-accent hover:text-foundation-grey">Record</button>
                                            </form>

                                            <div class="razorpay-invoice-btn w-full md:w-[250px]"
                                                 data-invoice-id="<?php echo (int)$inv['id']; ?>"
                                                 data-outstanding="<?php echo esc_attr(number_format($invOut, 2, '.', '')); ?>">
                                            </div>
                                        <?php else: ?>
                                            <div class="text-xs text-gray-400 md:text-right">No collection needed</div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="bg-white border border-gray-100 shadow-premium overflow-hidden">
            <div class="px-6 md:px-8 py-5 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-sm font-bold uppercase tracking-[0.3em] text-foundation-grey">Recent Money Events</h2>
                <span class="text-xs text-gray-500"><?php echo count($recentPaymentRows); ?> entries</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm admin-table">
                    <thead>
                    <tr class="text-[10px] uppercase tracking-[0.2em] text-gray-400 border-b border-gray-100">
                        <th class="px-6 py-4">Event</th>
                        <th class="px-4 py-4">Invoice</th>
                        <th class="px-4 py-4">Project</th>
                        <th class="px-4 py-4">Amount</th>
                        <th class="px-4 py-4">Status</th>
                        <th class="px-6 py-4">Time</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($recentPaymentRows)): ?>
                        <tr><td colspan="6" class="px-6 py-10 text-gray-400">No payment events yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentPaymentRows as $pay): ?>
                            <?php $amount = ((int)($pay['amount_paisa'] ?? 0)) / 100; ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50/40">
                                <td class="px-6 py-4" data-label="Event">
                                    <div class="font-mono text-xs"><?php echo esc((string)$pay['provider_order_id']); ?></div>
                                    <div class="text-xs text-gray-400"><?php echo esc(strtoupper((string)$pay['provider'])); ?></div>
                                </td>
                                <td class="px-4 py-4" data-label="Invoice"><?php echo esc((string)($pay['invoice_code'] ?? '-')); ?></td>
                                <td class="px-4 py-4" data-label="Project"><?php echo esc((string)($pay['project_name'] ?? '-')); ?></td>
                                <td class="px-4 py-4 font-semibold" data-label="Amount">₹<?php echo number_format($amount, 2); ?> <?php echo esc((string)$pay['currency']); ?></td>
                                <td class="px-4 py-4" data-label="Status"><span class="text-[10px] uppercase tracking-widest font-bold <?php echo esc_attr($statusClass((string)($pay['status'] ?? 'created'))); ?>"><?php echo esc(strtoupper((string)($pay['status'] ?? 'created'))); ?></span></td>
                                <td class="px-6 py-4 text-xs text-gray-500" data-label="Time"><?php echo esc(date('M d, Y H:i', strtotime((string)$pay['created_at']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <?php if (!defined('HIDE_FOOTER_CTA')) define('HIDE_FOOTER_CTA', true); require_once PROJECT_ROOT . '/Common/footer.php'; ?>
</div>

<?php if ($isRazorpayConfigured): ?>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<?php endif; ?>

<script>
(function () {
    const invoiceRows = Array.from(document.querySelectorAll('.invoice-row'));
    const invoiceSearch = document.getElementById('invoiceSearch');
    const invoiceStatusFilter = document.getElementById('invoiceStatusFilter');
    const gatewayNotice = document.getElementById('gatewayNotice');

    const config = {
        razorpayEnabled: <?php echo $isRazorpayConfigured ? 'true' : 'false'; ?>,
        csrfToken: <?php echo esc_js(csrf_token()); ?>,
        createOrderUrl: <?php echo esc_js(base_path('api/create-order.php')); ?>,
        verifyPaymentUrl: <?php echo esc_js(base_path('api/verify-payment.php')); ?>,
        currency: <?php echo esc_js($paymentCurrency); ?>
    };

    const setNotice = function (message, isError) {
        gatewayNotice.textContent = message;
        gatewayNotice.classList.toggle('text-red-600', !!isError);
        gatewayNotice.classList.toggle('text-gray-500', !isError);
    };

    const applyInvoiceFilter = function () {
        const q = (invoiceSearch ? invoiceSearch.value : '').trim().toLowerCase();
        const st = (invoiceStatusFilter ? invoiceStatusFilter.value : 'all').trim().toLowerCase();

        invoiceRows.forEach(function (row) {
            const hay = [
                row.dataset.invoice,
                row.dataset.project,
                row.dataset.client,
                row.dataset.status,
                row.dataset.total,
                row.dataset.paid,
                row.dataset.outstanding
            ].join(' ').toLowerCase();

            const matchQ = q === '' || hay.includes(q);
            const matchS = st === 'all' || row.dataset.status === st;
            row.classList.toggle('hidden', !(matchQ && matchS));
        });
    };

    if (invoiceSearch) invoiceSearch.addEventListener('input', applyInvoiceFilter);
    if (invoiceStatusFilter) invoiceStatusFilter.addEventListener('change', applyInvoiceFilter);

    if (!config.razorpayEnabled) {
        setNotice('Razorpay credentials are missing in .env. Manual recording still works.', true);
        return;
    }

    if (!window.Razorpay) {
        setNotice('Razorpay SDK could not be loaded. Check internet or credentials.', true);
        return;
    }

    document.querySelectorAll('.razorpay-invoice-btn').forEach(function (container) {
        const invoiceId = parseInt(container.dataset.invoiceId || '0', 10);
        const outstanding = container.dataset.outstanding || '0.00';

        if (!invoiceId || parseFloat(outstanding) <= 0) {
            return;
        }

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'w-full bg-foundation-grey hover:bg-rajkot-rust text-white px-4 py-2 text-[10px] font-bold uppercase tracking-[0.2em] transition-all';
        trigger.textContent = 'Pay with Razorpay';
        container.appendChild(trigger);

        trigger.addEventListener('click', async function () {
            setNotice('Creating Razorpay order for invoice #' + invoiceId + '...', false);
            const response = await fetch(config.createOrderUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': config.csrfToken
                },
                body: JSON.stringify({
                    invoice_id: invoiceId,
                    amount: outstanding,
                    currency: config.currency
                })
            });

            const data = await response.json();
            if (!response.ok || !data.success || !data.data || !data.data.order_id) {
                const msg = (data && data.message) ? data.message : 'Unable to create payment order.';
                setNotice(msg, true);
                return;
            }

            const options = {
                key: data.data.key_id,
                amount: data.data.amount,
                currency: data.data.currency,
                name: data.data.name,
                description: data.data.description,
                order_id: data.data.order_id,
                prefill: data.data.prefill || {},
                theme: { color: '#94180C' },
                handler: async function (paymentResponse) {
                    setNotice('Verifying Razorpay payment for invoice #' + invoiceId + '...', false);
                    const verifyResponse = await fetch(config.verifyPaymentUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': config.csrfToken
                        },
                        body: JSON.stringify(paymentResponse)
                    });

                    const verifyData = await verifyResponse.json();
                    if (!verifyResponse.ok || !verifyData.success) {
                        const msg = (verifyData && verifyData.message) ? verifyData.message : 'Payment verification failed.';
                        setNotice(msg, true);
                        return;
                    }

                    setNotice('Payment verified successfully. Refreshing dashboard...', false);
                    window.setTimeout(function () { window.location.reload(); }, 900);
                },
                modal: {
                    ondismiss: function () {
                        setNotice('Payment flow cancelled by user.', true);
                    }
                }
            };

            try {
                const checkout = new window.Razorpay(options);
                checkout.on('payment.failed', function () {
                    setNotice('Unexpected Razorpay checkout error.', true);
                });
                checkout.open();
            } catch (err) {
                setNotice(err && err.message ? err.message : 'Unable to open Razorpay checkout.', true);
            }
        });
    });
})();
</script>
</body>
</html>
