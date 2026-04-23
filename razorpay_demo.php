<?php
require_once __DIR__ . '/app/Core/Bootstrap/init.php';
require_login();

$HEADER_MODE = 'public';
require_once PROJECT_ROOT . '/Common/header.php';

$isRazorpayConfigured = function_exists('razorpay_is_configured') ? razorpay_is_configured() : false;
$currency = strtoupper(trim((string)(getenv('PAYMENT_CURRENCY') ?: 'INR')));
$csrf = csrf_token();
?>
<main class="max-w-4xl mx-auto p-8">
    <h1 class="text-2xl font-bold mb-4">Razorpay Checkout Demo</h1>
    <p class="text-sm text-gray-600 mb-4">Use this page to create a test Razorpay order and open the checkout modal. You must be logged in as a `client` or `admin` for the demo to work.</p>

    <div class="bg-white border p-6 mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-2">Amount (INR)</label>
        <input id="demoAmount" type="number" step="0.01" min="1" value="100.00" class="border px-3 py-2 w-40" />
        <button id="demoPayBtn" class="ml-4 bg-rajkot-rust text-white px-4 py-2">Pay Now</button>
        <div id="demoMessage" class="mt-4 text-sm text-gray-700"></div>
    </div>

    <div class="bg-white border p-6">
        <h3 class="font-semibold mb-2">Instructions</h3>
        <ol class="text-sm list-decimal list-inside text-gray-600">
            <li>Enter an amount (minimum ₹1.00).</li>
            <li>Click <strong>Pay Now</strong>. The page will call <code>/api/create-order.php</code>, open Razorpay modal, then POST the response to <code>/api/verify-payment.php</code>.</li>
            <li>On success the page will show a confirmation.</li>
        </ol>
    </div>
</main>

<?php if ($isRazorpayConfigured): ?>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<?php endif; ?>

<script>
(function(){
    const msg = (t, err) => {
        const el = document.getElementById('demoMessage');
        if (!el) return; el.textContent = t; el.style.color = err ? '#b91c1c' : '#0b6623';
    };

    const cfg = {
        enabled: <?php echo $isRazorpayConfigured ? 'true' : 'false'; ?>,
        csrfToken: '<?php echo addslashes($csrf); ?>',
        createOrderUrl: '<?php echo addslashes(base_path('api/create-order.php')); ?>',
        verifyPaymentUrl: '<?php echo addslashes(base_path('api/verify-payment.php')); ?>',
        currency: '<?php echo addslashes($currency); ?>'
    };

    document.getElementById('demoPayBtn').addEventListener('click', async function () {
        if (!cfg.enabled) { msg('Razorpay not configured on server.', true); return; }
        const raw = document.getElementById('demoAmount').value;
        const amount = Math.max(0, parseFloat(raw) || 0);
        if (amount < 1) { msg('Amount must be at least ₹1.00', true); return; }

        msg('Creating order...');
        try {
            const res = await fetch(cfg.createOrderUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': cfg.csrfToken },
                body: JSON.stringify({ amount: amount, currency: cfg.currency })
            });
            const data = await res.json();
            if (!res.ok || !data.success || !data.data || !data.data.order_id) {
                msg((data && data.message) ? data.message : 'Unable to create order', true);
                return;
            }

            const options = {
                key: data.data.key_id,
                amount: data.data.amount,
                currency: data.data.currency,
                name: data.data.name || 'Ripal Design',
                description: data.data.description || 'Demo payment',
                order_id: data.data.order_id,
                theme: { color: '#94180C' },
                handler: async function (paymentResponse) {
                    msg('Verifying payment...');
                    const vr = await fetch(cfg.verifyPaymentUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': cfg.csrfToken },
                        body: JSON.stringify(paymentResponse)
                    });
                    const vd = await vr.json();
                    if (!vr.ok || !vd.success) {
                        msg((vd && vd.message) ? vd.message : 'Verification failed', true);
                        return;
                    }
                    msg('Payment verified successfully.');
                    setTimeout(() => location.reload(), 900);
                },
                modal: { ondismiss: function () { msg('Payment cancelled', true); } }
            };

            const checkout = new window.Razorpay(options);
            checkout.on('payment.failed', function () { msg('Payment failed', true); });
            checkout.open();
        } catch (e) {
            msg(e && e.message ? e.message : 'Unexpected error', true);
        }
    });
})();
</script>

<?php require_once PROJECT_ROOT . '/Common/footer.php'; ?>
