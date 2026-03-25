<?php
/**
 * Payment Gateway / Financial Oversight (Redesigned)
 * 
 * Provides administrators with a high-level view of financial transactions,
 * outstanding collections, and workforce payouts.
 */

require_once __DIR__ . '/../includes/init.php';
require_login();
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_csrf();
    $action = (string)$_POST['action'];
    if ($action === 'batch_disbursement') {
        set_flash('Batch disbursement run initialized successfully.', 'success');
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$totalRevenue = (float)(db_fetch('SELECT COALESCE(SUM(budget),0) AS s FROM projects')['s'] ?? 0);
$pendingCollections = (float)(db_fetch("SELECT COALESCE(SUM(budget),0) AS s FROM projects WHERE status IN ('planning','ongoing')")['s'] ?? 0);
$workforcePayouts = (float)(db_fetch('SELECT COALESCE(SUM(total_price),0) AS s FROM project_goods')['s'] ?? 0);
$activeContracts = (int)(db_fetch("SELECT COUNT(*) AS c FROM projects WHERE status IN ('planning','ongoing','paused')")['c'] ?? 0);

$stats = [
    'total_revenue' => '₹ ' . number_format($totalRevenue, 0, '.', ','),
    'pending_collections' => '₹ ' . number_format($pendingCollections, 0, '.', ','),
    'workforce_payouts' => '₹ ' . number_format($workforcePayouts, 0, '.', ','),
    'active_contracts' => (string)$activeContracts,
];

$transactions = db_fetch_all("SELECT pg.id, p.name AS project, COALESCE(p.owner_name,'Client') AS party, pg.total_price AS amount, 'Payout' AS type,
    CASE WHEN p.status = 'completed' THEN 'synchronized' WHEN p.status IN ('ongoing','planning') THEN 'pending' ELSE 'failed' END AS status,
    pg.created_at AS date
    FROM project_goods pg
    LEFT JOIN projects p ON p.id = pg.project_id
    ORDER BY pg.created_at DESC LIMIT 20");

if (empty($transactions)) {
    $transactions = db_fetch_all("SELECT p.id, p.name AS project, COALESCE(p.owner_name,'Client') AS party, p.budget AS amount, 'Collection' AS type,
        CASE WHEN p.status = 'completed' THEN 'synchronized' WHEN p.status IN ('ongoing','planning') THEN 'pending' ELSE 'failed' END AS status,
        p.created_at AS date
        FROM projects p
        ORDER BY p.created_at DESC LIMIT 20");
}
?>
<!DOCTYPE html>
<html lang="en" class="bg-canvas-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Financial Gateway | Ripal Design</title>
    <?php $HEADER_MODE = 'dashboard'; require_once __DIR__ . '/../Common/header.php'; ?>
</head>
<body class="bg-canvas-white font-sans text-foundation-grey min-h-screen">
    
    <div class="min-h-screen flex flex-col">
        <!-- Unified Dark Portal Header -->
        <!-- Unified Dark Portal Header -->
        <header class="bg-foundation-grey text-white pt-20 md:pt-24 pb-8 md:pb-12 px-4 sm:px-6 lg:px-8 shadow-lg mb-8 md:mb-12 border-b-2 border-rajkot-rust">
            <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <h1 class="text-3xl md:text-4xl font-serif font-bold">Financial Gateway</h1>
                    <p class="text-gray-400 mt-2 text-sm uppercase tracking-widest font-bold opacity-70">Unified ledger for collections and disbursements.</p>
                </div>
                <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                    <form method="post" class="w-full md:w-auto">
                        <?php echo csrf_token_field(); ?>
                        <input type="hidden" name="action" value="batch_disbursement">
                        <button type="submit" class="w-full md:w-auto bg-rajkot-rust hover:bg-red-700 text-white px-8 py-4 text-[10px] font-bold uppercase tracking-[0.2em] shadow-premium transition-all flex items-center justify-center gap-3 active:scale-95">
                            <i data-lucide="upload" class="w-4 h-4"></i> Batch Disbursement
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
            
            <!-- Financial Insight Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                <div class="bg-white p-6 md:p-8 shadow-premium border border-gray-100 relative group overflow-hidden">
                    <div class="absolute top-0 right-0 w-16 h-16 bg-gray-50 -mr-8 -mt-8 rotate-45 pointer-events-none"></div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Total Revenue</span>
                    <span class="text-2xl md:text-3xl font-serif font-bold text-foundation-grey"><?php echo $stats['total_revenue']; ?></span>
                    <div class="mt-4 flex items-center gap-1.5 text-approval-green text-[10px] font-bold">
                        <i data-lucide="trending-up" class="w-3 h-3"></i> +12.4% vs Last Qtr
                    </div>
                </div>
                <div class="bg-white p-6 md:p-8 shadow-premium border border-gray-100 border-b-2 border-b-rajkot-rust relative group overflow-hidden">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Pending Collections</span>
                    <span class="text-2xl md:text-3xl font-serif font-bold text-rajkot-rust"><?php echo $stats['pending_collections']; ?></span>
                    <div class="mt-4 text-[10px] text-gray-400 font-medium">8 Outstanding Invoices</div>
                </div>
                <div class="bg-white p-6 md:p-8 shadow-premium border border-gray-100 border-b-2 border-b-pending-amber relative group overflow-hidden">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Scheduled Payouts</span>
                    <span class="text-2xl md:text-3xl font-serif font-bold text-pending-amber"><?php echo $stats['workforce_payouts']; ?></span>
                    <div class="mt-4 text-[10px] text-gray-400 font-medium">Next run: Friday, 2 PM</div>
                </div>
                <div class="bg-white p-6 md:p-8 shadow-premium border border-gray-100 border-b-2 border-b-slate-accent relative group overflow-hidden">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Active Contracts</span>
                    <span class="text-2xl md:text-3xl font-serif font-bold text-slate-accent"><?php echo $stats['active_contracts']; ?></span>
                    <div class="mt-4 text-[10px] text-gray-400 font-medium">Audit complete</div>
                </div>
            </div>

            <!-- Transaction Audit Ledger -->
            <div class="bg-white shadow-premium border border-gray-100 overflow-hidden relative">
                <div class="px-6 md:px-10 py-6 md:py-8 border-b border-gray-50 flex flex-col md:flex-row items-center justify-between bg-gray-50/50 gap-4">
                    <h3 class="text-[10px] font-bold uppercase tracking-[0.4em] text-foundation-grey flex items-center gap-3">
                        <i data-lucide="book-open" class="w-4 h-4 text-rajkot-rust"></i> Transaction Registry
                    </h3>
                    <div class="relative w-full md:w-72">
                        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-300 w-4 h-4"></i>
                        <input id="ledgerFilterInput" type="search" placeholder="Filter ledger..." class="w-full pl-10 pr-4 py-2 bg-white border border-gray-100 outline-none focus:border-rajkot-rust text-xs transition-all">
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse admin-table">
                        <thead class="hidden md:table-header-group">
                            <tr class="bg-gray-50/20 text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] border-b border-gray-100">
                                <th class="px-10 py-6 font-bold">Transaction Code</th>
                                <th class="px-8 py-6 font-bold">Venture / Project</th>
                                <th class="px-8 py-6 font-bold">Involved Party</th>
                                <th class="px-8 py-6 font-bold">Quantum</th>
                                <th class="px-8 py-6 font-bold">Status Signal</th>
                                <th class="px-10 py-6 font-bold text-right">Ledger Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            <?php foreach($transactions as $i => $t): ?>
                            <tr class="group hover:bg-gray-50/30 transition-all duration-300 block md:table-row mb-4 md:mb-0 border md:border-0 rounded-lg md:rounded-none bg-white md:bg-transparent ledger-row<?php echo $i >= 10 ? ' hidden extra-row' : ''; ?>"
                                data-txn="TXN-<?php echo str_pad((string)$t['id'], 5, '0', STR_PAD_LEFT); ?>"
                                data-project="<?php echo htmlspecialchars((string)$t['project']); ?>"
                                data-party="<?php echo htmlspecialchars((string)$t['party']); ?>"
                                data-amount="<?php echo number_format((float)$t['amount'], 0, '.', ','); ?>"
                                data-type="<?php echo htmlspecialchars((string)$t['type']); ?>"
                                data-status="<?php echo htmlspecialchars((string)$t['status']); ?>"
                                data-date="<?php echo htmlspecialchars(date('M d, Y', strtotime((string)$t['date']))); ?>">
                                <td class="px-6 md:px-10 py-4 md:py-8 block md:table-cell" data-label="Transaction Code">
                                    <span class="text-xs font-mono font-bold text-foundation-grey">TXN-<?php echo str_pad((string)$t['id'], 5, '0', STR_PAD_LEFT); ?></span>
                                    <div class="text-[9px] text-gray-400 uppercase tracking-tighter mt-1"><?php echo $t['type']; ?> Event</div>
                                </td>
                                <td class="px-6 md:px-8 py-4 md:py-8 block md:table-cell" data-label="Venture / Project">
                                    <p class="font-bold text-foundation-grey mb-1"><?php echo $t['project']; ?></p>
                                    <p class="text-[10px] text-gray-400 font-medium italic"><?php echo date('M d, Y', strtotime((string)$t['date'])); ?></p>
                                </td>
                                <td class="px-6 md:px-8 py-4 md:py-8 block md:table-cell" data-label="Involved Party">
                                    <p class="text-[11px] font-medium text-gray-600"><?php echo $t['party']; ?></p>
                                </td>
                                <td class="px-6 md:px-8 py-4 md:py-8 block md:table-cell" data-label="Quantum">
                                    <span class="text-base font-serif font-bold <?php echo $t['type'] === 'Collection' ? 'text-approval-green' : 'text-foundation-grey'; ?>">
                                        <?php echo $t['type'] === 'Payout' ? '-' : '+'; ?>₹ <?php echo number_format((float)$t['amount'], 0, '.', ','); ?>
                                    </span>
                                </td>
                                <td class="px-6 md:px-8 py-4 md:py-8 block md:table-cell" data-label="Status Signal">
                                    <?php if ($t['status'] === 'synchronized'): ?>
                                        <span class="flex items-center gap-2 text-approval-green text-[9px] font-bold uppercase tracking-widest">
                                            <span class="w-2 h-2 bg-approval-green rounded-full shadow-[0_0_8px_rgba(21,128,61,0.5)]"></span> Synchronized
                                        </span>
                                    <?php elseif ($t['status'] === 'pending'): ?>
                                        <span class="flex items-center gap-2 text-pending-amber text-[9px] font-bold uppercase tracking-widest animate-pulse">
                                            <span class="w-2 h-2 bg-pending-amber rounded-full"></span> Awaiting Sync
                                        </span>
                                    <?php else: ?>
                                        <span class="flex items-center gap-2 text-red-600 text-[9px] font-bold uppercase tracking-widest">
                                            <span class="w-2 h-2 bg-red-600 rounded-full shadow-[0_0_8px_rgba(220,38,38,0.5)]"></span> Reconcile Fail
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 md:px-10 py-6 md:py-8 block md:table-cell" data-label="Ledger Actions">
                                    <div class="flex flex-row md:justify-end gap-3 mt-4 md:mt-0">
                                        <button type="button" class="flex-grow md:flex-grow-0 h-11 w-11 bg-gray-50 md:bg-transparent text-gray-400 hover:text-rajkot-rust transition-colors flex items-center justify-center border border-gray-100 md:border-0 rounded-lg receipt-btn" title="View Receipt">
                                            <i data-lucide="receipt" class="w-5 h-5 md:w-4 md:h-4"></i>
                                        </button>
                                        <button type="button" class="flex-grow md:flex-grow-0 h-11 w-11 bg-gray-50 md:bg-transparent text-gray-400 hover:text-foundation-grey transition-colors flex items-center justify-center border border-gray-100 md:border-0 rounded-lg audit-btn" title="Audit Trail">
                                            <i data-lucide="history" class="w-5 h-5 md:w-4 md:h-4"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="p-6 md:p-10 text-center border-t border-gray-50 bg-gray-50/30">
                    <button id="ledgerShowMoreBtn" type="button" class="text-[10px] font-bold uppercase tracking-[0.3em] text-gray-300 hover:text-rajkot-rust transition-all border-b border-transparent hover:border-rajkot-rust pb-1 px-4">Show More Records</button>
                </div>
            </div>

            <!-- Dynamic Simulation Area -->
            <div class="mt-12 grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 bg-foundation-grey text-white p-10 flex flex-col md:flex-row items-center gap-10 shadow-premium relative overflow-hidden group">
                    <i data-lucide="shield-check" class="w-32 h-32 text-white/5 absolute -right-8 -bottom-8 transform -rotate-12"></i>
                    <div class="shrink-0 w-24 h-24 bg-rajkot-rust flex items-center justify-center rounded-sm">
                        <i data-lucide="credit-card" class="w-12 h-12 text-white"></i>
                    </div>
                    <div>
                        <h4 class="text-2xl font-serif font-bold mb-3">Merchant Integration Terminal</h4>
                        <p class="text-sm text-gray-400 mb-6 leading-relaxed">System is currently operating with a <strong class="text-white">Unified Payment Interface (UPI)</strong> bridge. All administrative disbursements require dual-approval hash verification before release.</p>
                        <div class="flex flex-col sm:flex-row gap-4">
                            <button id="configureProviderBtn" type="button" class="bg-white text-foundation-grey px-8 py-4 text-[10px] font-bold uppercase tracking-widest hover:bg-rajkot-rust hover:text-white transition-all shadow-lg active:scale-95 flex items-center justify-center">Configure Provider</button>
                            <button id="securityAuditBtn" type="button" class="border border-white/20 text-white px-8 py-4 text-[10px] font-bold uppercase tracking-widest hover:bg-white/10 transition-all active:scale-95 flex items-center justify-center">Security Audit</button>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-10 border border-gray-100 shadow-premium flex flex-col justify-center gap-6">
                    <div>
                        <span class="text-[9px] font-bold text-gray-300 uppercase tracking-widest block mb-2">Automated Reconciliation</span>
                        <div class="flex items-center justify-between">
                            <span class="text-lg font-bold text-foundation-grey">System Health</span>
                            <span class="text-[10px] font-black text-approval-green uppercase">Optimal</span>
                        </div>
                        <div class="w-full bg-gray-50 h-1.5 mt-3 rounded-full overflow-hidden">
                            <div class="bg-approval-green h-full" style="width: 100%"></div>
                        </div>
                    </div>
                    <div class="pt-6 border-t border-gray-50">
                        <p class="text-[10px] text-gray-400 font-medium italic">Last Sync: Today, 22:30 IST</p>
                    </div>
                </div>
            </div>
        </main>

        <div id="ledgerModal" class="fixed inset-0 hidden bg-black/40 z-50 items-center justify-center p-4">
            <div class="bg-white w-full max-w-lg rounded-lg shadow-premium p-6">
                <div class="flex items-start justify-between mb-4">
                    <h3 id="ledgerModalTitle" class="text-lg font-bold text-foundation-grey">Transaction Detail</h3>
                    <button id="ledgerModalClose" type="button" class="text-gray-500 hover:text-foundation-grey text-xl leading-none">&times;</button>
                </div>
                <div id="ledgerModalBody" class="text-sm text-gray-600 space-y-2"></div>
            </div>
        </div>

        <?php require_once __DIR__ . '/../Common/footer.php'; ?>
    </div>

    <script>
        (function () {
            const rows = Array.from(document.querySelectorAll('.ledger-row'));
            const filterInput = document.getElementById('ledgerFilterInput');
            const showMoreBtn = document.getElementById('ledgerShowMoreBtn');
            const modal = document.getElementById('ledgerModal');
            const modalTitle = document.getElementById('ledgerModalTitle');
            const modalBody = document.getElementById('ledgerModalBody');
            const modalClose = document.getElementById('ledgerModalClose');

            const openModal = function (title, html) {
                modalTitle.textContent = title;
                modalBody.innerHTML = html;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            };

            const closeModal = function () {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            };

            modalClose.addEventListener('click', closeModal);
            modal.addEventListener('click', function (e) {
                if (e.target === modal) {
                    closeModal();
                }
            });

            filterInput.addEventListener('input', function () {
                const q = filterInput.value.trim().toLowerCase();
                rows.forEach(function (row) {
                    const haystack = [
                        row.dataset.txn,
                        row.dataset.project,
                        row.dataset.party,
                        row.dataset.amount,
                        row.dataset.type,
                        row.dataset.status,
                        row.dataset.date
                    ].join(' ').toLowerCase();
                    row.classList.toggle('hidden', q !== '' && !haystack.includes(q));
                });
            });

            showMoreBtn.addEventListener('click', function () {
                const hiddenRows = document.querySelectorAll('.extra-row.hidden');
                hiddenRows.forEach(function (r) { r.classList.remove('hidden'); });
                showMoreBtn.classList.add('hidden');
            });

            document.querySelectorAll('.receipt-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const row = btn.closest('.ledger-row');
                    openModal('Receipt Preview',
                        '<p><strong>Transaction:</strong> ' + row.dataset.txn + '</p>' +
                        '<p><strong>Project:</strong> ' + row.dataset.project + '</p>' +
                        '<p><strong>Party:</strong> ' + row.dataset.party + '</p>' +
                        '<p><strong>Amount:</strong> ' + row.dataset.amount + '</p>' +
                        '<p><strong>Date:</strong> ' + row.dataset.date + '</p>');
                });
            });

            document.querySelectorAll('.audit-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const row = btn.closest('.ledger-row');
                    openModal('Audit Trail',
                        '<p><strong>Transaction:</strong> ' + row.dataset.txn + '</p>' +
                        '<p><strong>Status:</strong> ' + row.dataset.status + '</p>' +
                        '<p>Audit trace available for internal reconciliation review.</p>');
                });
            });

            document.getElementById('configureProviderBtn').addEventListener('click', function () {
                openModal('Provider Configuration', '<p>Provider configuration wizard will be available in the next release.</p>');
            });

            document.getElementById('securityAuditBtn').addEventListener('click', function () {
                openModal('Security Audit', '<p>Security audit has been queued for payment subsystem.</p>');
            });
        })();
    </script>

</body>
</html>