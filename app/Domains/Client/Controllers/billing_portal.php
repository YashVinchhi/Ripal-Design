<?php
if (!defined('PROJECT_ROOT')) { require_once dirname(__DIR__, 4) . '/app/Core/Bootstrap/init.php'; }
require_once PROJECT_ROOT . '/app/Core/Bootstrap/init.php';
require_login();
require_role('client');

$billingReady = function_exists('billing_ensure_tables') && billing_ensure_tables();
$paymentsReady = function_exists('payments_ensure_table') && payments_ensure_table();

$razorpayKeyId = trim((string)(getenv('RAZORPAY_KEY_ID') ?: getenv('RAZORPAY_KEY') ?: ''));
$razorpayKeySecret = trim((string)(getenv('RAZORPAY_KEY_SECRET') ?: ''));
$isRazorpayConfigured = $razorpayKeyId !== '' && $razorpayKeySecret !== '';
$paymentCurrency = strtoupper(trim((string)(getenv('PAYMENT_CURRENCY') ?: 'INR')));

$sessionUser = current_user();
$sessionEmail = strtolower(trim((string)($sessionUser['email'] ?? '')));
$sessionUserId = current_user_id();

$invoiceRows = [];
$paymentRows = [];
$totals = [
    'invoiced' => 0.0,
    'paid' => 0.0,
    'outstanding' => 0.0,
    'overdue' => 0,
];

if ($billingReady) {
    $invoiceRows = db_fetch_all(
        'SELECT bi.*, p.name AS project_name
         FROM billing_invoices bi
         LEFT JOIN projects p ON p.id = bi.project_id
         WHERE (p.client_id = ?)
            OR (LOWER(COALESCE(bi.client_email,\'\')) = ?)
            OR (LOWER(COALESCE(p.owner_email,\'\')) = ?)
         ORDER BY bi.id DESC',
        [$sessionUserId, $sessionEmail, $sessionEmail]
    );

    foreach ($invoiceRows as $inv) {
        $total = (float)($inv['total_amount'] ?? 0);
        $paid = (float)($inv['amount_paid'] ?? 0);
        $out = max(0, round($total - $paid, 2));
        $status = strtolower((string)($inv['status'] ?? 'issued'));

        if ($status !== 'cancelled') {
            $totals['invoiced'] += $total;
            $totals['paid'] += $paid;
            $totals['outstanding'] += $out;
        }
        if ($status === 'overdue') {
            $totals['overdue']++;
        }
    }
}

if ($paymentsReady) {
    $paymentRows = db_fetch_all(
        'SELECT pay.*, bi.invoice_code, p.name AS project_name
         FROM payments pay
         LEFT JOIN billing_invoices bi ON bi.id = pay.invoice_id
         LEFT JOIN projects p ON p.id = pay.project_id
         WHERE (pay.user_id = ?)
            OR (bi.id IN (
                SELECT bi2.id
                FROM billing_invoices bi2
                LEFT JOIN projects p2 ON p2.id = bi2.project_id
                WHERE (p2.client_id = ?)
                   OR (LOWER(COALESCE(bi2.client_email,\'\')) = ?)
                   OR (LOWER(COALESCE(p2.owner_email,\'\')) = ?)
            ))
         ORDER BY pay.id DESC
         LIMIT 60',
        [$sessionUserId, $sessionUserId, $sessionEmail, $sessionEmail]
    );
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

$HEADER_MODE = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en" class="bg-canvas-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Client Billing Portal | Ripal Design</title>
    <?php require_once PROJECT_ROOT . '/Common/header.php'; ?>
</head>
<body class="bg-canvas-white font-sans text-foundation-grey min-h-screen">
<div class="min-h-screen flex flex-col">
    <header class="bg-foundation-grey text-white pt-20 md:pt-24 pb-8 md:pb-12 px-4 sm:px-6 lg:px-8 shadow-lg mb-8 md:mb-12 border-b-2 border-rajkot-rust">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <h1 class="text-3xl md:text-4xl font-serif font-bold">Client Billing Portal</h1>
                <p class="text-gray-400 mt-2 text-sm uppercase tracking-widest font-bold opacity-70">View only your invoices, download PDF copies, and pay online securely.</p>
            </div>
            <div class="bg-white/10 border border-white/20 rounded px-5 py-4">
                <p class="text-[10px] uppercase tracking-[0.2em] text-gray-300 font-bold">Gateway</p>
                <p class="font-bold text-lg mt-1"><?php echo esc(strtoupper((string)(getenv('PAYMENT_PROVIDER') ?: 'razorpay'))); ?></p>
            </div>
        </div>
    </header>

    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20 space-y-8">
        <?php if (function_exists('render_flash')) { render_flash(); } ?>

        <section class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white border border-gray-100 shadow-premium p-5">
                <p class="text-[10px] uppercase tracking-[0.2em] text-gray-400 font-bold">Total Invoiced</p>
                <p class="text-xl font-serif font-bold mt-2">₹<?php echo number_format($totals['invoiced'], 2); ?></p>
            </div>
            <div class="bg-white border border-gray-100 shadow-premium p-5">
                <p class="text-[10px] uppercase tracking-[0.2em] text-gray-400 font-bold">Paid</p>
                <p class="text-xl font-serif font-bold mt-2 text-approval-green">₹<?php echo number_format($totals['paid'], 2); ?></p>
            </div>
            <div class="bg-white border border-gray-100 shadow-premium p-5">
                <p class="text-[10px] uppercase tracking-[0.2em] text-gray-400 font-bold">Outstanding</p>
                <p class="text-xl font-serif font-bold mt-2 text-pending-amber">₹<?php echo number_format($totals['outstanding'], 2); ?></p>
            </div>
            <div class="bg-white border border-gray-100 shadow-premium p-5">
                <p class="text-[10px] uppercase tracking-[0.2em] text-gray-400 font-bold">Overdue</p>
                <p class="text-xl font-serif font-bold mt-2 text-red-600"><?php echo (int)$totals['overdue']; ?></p>
            </div>
        </section>

        <section class="bg-white border border-gray-100 shadow-premium overflow-hidden">
            <div class="px-6 md:px-8 py-5 border-b border-gray-100 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                <h2 class="text-sm font-bold uppercase tracking-[0.3em] text-foundation-grey">Your Invoices</h2>
                <input id="clientInvoiceSearch" type="search" placeholder="Search invoice or project" class="w-full md:w-72 border border-gray-200 px-3 py-2 text-xs outline-none focus:border-rajkot-rust">
            </div>
            <div id="clientBillingNotice" class="px-6 md:px-8 py-3 text-xs bg-gray-50 border-b border-gray-100 text-gray-500">Pay online from any invoice row using Razorpay checkout.</div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm admin-table">
                    <thead>
                    <tr class="text-[10px] uppercase tracking-[0.2em] text-gray-400 border-b border-gray-100">
                        <th class="px-6 py-4">Invoice</th>
                        <th class="px-4 py-4">Project</th>
                        <th class="px-4 py-4">Total</th>
                        <th class="px-4 py-4">Paid</th>
                        <th class="px-4 py-4">Outstanding</th>
                        <th class="px-4 py-4">Due</th>
                        <th class="px-4 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($invoiceRows)): ?>
                        <tr><td colspan="8" class="px-6 py-12 text-gray-400">No invoices found for your account.</td></tr>
                    <?php else: ?>
                        <?php foreach ($invoiceRows as $inv): ?>
                            <?php
                                $invTotal = (float)($inv['total_amount'] ?? 0);
                                $invPaid = (float)($inv['amount_paid'] ?? 0);
                                $invOut = max(0, round($invTotal - $invPaid, 2));
                                $invStatus = strtolower((string)($inv['status'] ?? 'issued'));
                                $pdfUrl = base_path('dashboard/api/invoice_pdf.php?invoice_id=' . (int)$inv['id'] . '&download=1');
                            ?>
                            <tr class="client-invoice-row border-b border-gray-50 hover:bg-gray-50/40"
                                data-invoice="<?php echo esc_attr((string)$inv['invoice_code']); ?>"
                                data-project="<?php echo esc_attr((string)($inv['project_name'] ?? '')); ?>"
                                data-status="<?php echo esc_attr($invStatus); ?>">
                                <td class="px-6 py-4" data-label="Invoice">
                                    <div class="font-mono text-xs font-semibold"><?php echo esc((string)$inv['invoice_code']); ?></div>
                                    <div class="text-xs text-gray-400">ID #<?php echo (int)$inv['id']; ?></div>
                                </td>
                                <td class="px-4 py-4" data-label="Project"><?php echo esc((string)($inv['project_name'] ?? 'Project')); ?></td>
                                <td class="px-4 py-4 font-semibold" data-label="Total">₹<?php echo number_format($invTotal, 2); ?></td>
                                <td class="px-4 py-4 text-approval-green font-semibold" data-label="Paid">₹<?php echo number_format($invPaid, 2); ?></td>
                                <td class="px-4 py-4 text-pending-amber font-semibold" data-label="Outstanding">₹<?php echo number_format($invOut, 2); ?></td>
                                <td class="px-4 py-4" data-label="Due"><?php echo !empty($inv['due_date']) ? esc((string)$inv['due_date']) : 'N/A'; ?></td>
                                <td class="px-4 py-4" data-label="Status"><span class="text-[10px] uppercase tracking-widest font-bold <?php echo esc_attr($statusClass($invStatus)); ?>"><?php echo esc(strtoupper($invStatus)); ?></span></td>
                                <td class="px-6 py-4" data-label="Actions">
                                    <div class="flex flex-col items-stretch md:items-end gap-2">
                                        <a href="<?php echo esc_attr($pdfUrl); ?>" class="text-[10px] uppercase tracking-widest font-bold text-foundation-grey hover:text-rajkot-rust">Download PDF</a>
                                        <?php if ($invOut > 0 && $invStatus !== 'cancelled'): ?>
                                            <div class="client-razorpay-btn w-full md:w-[220px]" data-invoice-id="<?php echo (int)$inv['id']; ?>" data-outstanding="<?php echo esc_attr(number_format($invOut, 2, '.', '')); ?>"></div>
                                        <?php else: ?>
                                            <span class="text-xs text-gray-400">No payment due</span>
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
                <h2 class="text-sm font-bold uppercase tracking-[0.3em] text-foundation-grey">Recent Payment Events</h2>
                <span class="text-xs text-gray-500"><?php echo count($paymentRows); ?> entries</span>
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
                    <?php if (empty($paymentRows)): ?>
                        <tr><td colspan="6" class="px-6 py-10 text-gray-400">No payment events yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($paymentRows as $pay): ?>
                            <?php $amount = ((int)($pay['amount_paisa'] ?? 0)) / 100; ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50/40">
                                <td class="px-6 py-4"><div class="font-mono text-xs"><?php echo esc((string)$pay['provider_order_id']); ?></div><div class="text-xs text-gray-400"><?php echo esc(strtoupper((string)$pay['provider'])); ?></div></td>
                                <td class="px-4 py-4"><?php echo esc((string)($pay['invoice_code'] ?? '-')); ?></td>
                                <td class="px-4 py-4"><?php echo esc((string)($pay['project_name'] ?? '-')); ?></td>
                                <td class="px-4 py-4 font-semibold">₹<?php echo number_format($amount, 2); ?> <?php echo esc((string)$pay['currency']); ?></td>
                                <td class="px-4 py-4"><span class="text-[10px] uppercase tracking-widest font-bold <?php echo esc_attr($statusClass((string)($pay['status'] ?? 'created'))); ?>"><?php echo esc(strtoupper((string)($pay['status'] ?? 'created'))); ?></span></td>
                                <td class="px-6 py-4 text-xs text-gray-500"><?php echo esc(date('M d, Y H:i', strtotime((string)$pay['created_at']))); ?></td>
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
    const rows = Array.from(document.querySelectorAll('.client-invoice-row'));
    const search = document.getElementById('clientInvoiceSearch');
    const notice = document.getElementById('clientBillingNotice');

    const cfg = {
        enabled: <?php echo $isRazorpayConfigured ? 'true' : 'false'; ?>,
        csrfToken: <?php echo esc_js(csrf_token()); ?>,
        createOrderUrl: <?php echo esc_js(base_path('api/create-order.php')); ?>,
        verifyPaymentUrl: <?php echo esc_js(base_path('api/verify-payment.php')); ?>,
        currency: <?php echo esc_js($paymentCurrency); ?>
    };

    const setNotice = function (msg, isErr) {
        notice.textContent = msg;
        notice.classList.toggle('text-red-600', !!isErr);
        notice.classList.toggle('text-gray-500', !isErr);
    };

    if (search) {
        search.addEventListener('input', function () {
            const q = search.value.trim().toLowerCase();
            rows.forEach(function (row) {
                const hay = [row.dataset.invoice, row.dataset.project, row.dataset.status].join(' ').toLowerCase();
                row.classList.toggle('hidden', q !== '' && !hay.includes(q));
            });
        });
    }

    if (!cfg.enabled) {
        setNotice('Payment gateway is unavailable right now. Please contact support.', true);
        return;
    }

    if (!window.Razorpay) {
        setNotice('Razorpay SDK load failed. Try reloading the page.', true);
        return;
    }

    document.querySelectorAll('.client-razorpay-btn').forEach(function (el) {
        const invoiceId = parseInt(el.dataset.invoiceId || '0', 10);
        const amount = el.dataset.outstanding || '0.00';
        if (!invoiceId || parseFloat(amount) <= 0) {
            return;
        }

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'w-full bg-foundation-grey hover:bg-rajkot-rust text-white px-4 py-2 text-[10px] font-bold uppercase tracking-[0.2em] transition-all';
        trigger.textContent = 'Pay with Razorpay';
        el.appendChild(trigger);

        trigger.addEventListener('click', async function () {
            setNotice('Creating payment order...', false);
            const r = await fetch(cfg.createOrderUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': cfg.csrfToken },
                body: JSON.stringify({ invoice_id: invoiceId, amount: amount, currency: cfg.currency })
            });
            const d = await r.json();
            if (!r.ok || !d.success || !d.data || !d.data.order_id) {
                const msg = (d && d.message) ? d.message : 'Unable to create order.';
                setNotice(msg, true);
                return;
            }

            const options = {
                key: d.data.key_id,
                amount: d.data.amount,
                currency: d.data.currency,
                name: d.data.name,
                description: d.data.description,
                order_id: d.data.order_id,
                prefill: d.data.prefill || {},
                theme: { color: '#94180C' },
                handler: async function (paymentResponse) {
                    setNotice('Verifying payment...', false);
                    const verifyResponse = await fetch(cfg.verifyPaymentUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': cfg.csrfToken },
                        body: JSON.stringify(paymentResponse)
                    });
                    const verifyData = await verifyResponse.json();
                    if (!verifyResponse.ok || !verifyData.success) {
                        const msg = (verifyData && verifyData.message) ? verifyData.message : 'Payment verification failed.';
                        setNotice(msg, true);
                        return;
                    }
                    setNotice('Payment successful. Refreshing...', false);
                    window.setTimeout(function () { window.location.reload(); }, 800);
                },
                modal: {
                    ondismiss: function () {
                        setNotice('Payment cancelled.', true);
                    }
                }
            };

            try {
                const checkout = new window.Razorpay(options);
                checkout.on('payment.failed', function () {
                    setNotice('Payment failed unexpectedly.', true);
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
