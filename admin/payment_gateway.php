<?php
/**
 * Payment Gateway / Financial Oversight (Redesigned)
 * 
 * Provides administrators with a high-level view of financial transactions,
 * outstanding collections, and workforce payouts.
 */

require_once __DIR__ . '/../includes/init.php';

// Mock financial data
$stats = [
    'total_revenue' => '₹ 1.24 Cr',
    'pending_collections' => '₹ 18.5 L',
    'workforce_payouts' => '₹ 4.2 L',
    'active_contracts' => '14'
];

$transactions = [
    [
        'id' => 'TXN-9021',
        'project' => 'RMC Smart City Plaza',
        'party' => 'Municipal Corporation',
        'amount' => '₹ 25,00,000',
        'type' => 'Collection',
        'status' => 'synchronized',
        'date' => 'Feb 12, 2026'
    ],
    [
        'id' => 'TXN-8954',
        'project' => 'Oak Street Residence',
        'party' => 'Ramesh Kumar (Foreman)',
        'amount' => '₹ 45,000',
        'type' => 'Payout',
        'status' => 'pending',
        'date' => 'Feb 15, 2026'
    ],
    [
        'id' => 'TXN-8812',
        'project' => 'Market Road Shop',
        'party' => 'Suresh Bhai (Electric)',
        'amount' => '₹ 12,500',
        'type' => 'Payout',
        'status' => 'synchronized',
        'date' => 'Feb 10, 2026'
    ],
    [
        'id' => 'TXN-8701',
        'project' => 'Riverfront Villa',
        'party' => 'Private Client',
        'amount' => '₹ 5,00,000',
        'type' => 'Collection',
        'status' => 'failed',
        'date' => 'Feb 08, 2026'
    ]
];
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
        <header class="bg-foundation-grey text-white pt-24 pb-12 px-4 sm:px-6 lg:px-8 shadow-lg mb-12 border-b-2 border-rajkot-rust">
            <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <h1 class="text-4xl font-serif font-bold">Financial Gateway</h1>
                    <p class="text-gray-400 mt-2 text-sm uppercase tracking-widest font-bold opacity-70">Unified ledger for collections and disbursements.</p>
                </div>
                <div class="flex gap-3">
                    <button class="bg-rajkot-rust hover:bg-red-700 text-white px-8 py-4 text-[10px] font-bold uppercase tracking-[0.2em] shadow-premium transition-all flex items-center gap-3 active:scale-95">
                        <i data-lucide="upload" class="w-4 h-4"></i> Batch Disbursement
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
            
            <!-- Financial Insight Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                <div class="bg-white p-8 shadow-premium border border-gray-100 relative group overflow-hidden">
                    <div class="absolute top-0 right-0 w-16 h-16 bg-gray-50 -mr-8 -mt-8 rotate-45 pointer-events-none"></div>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Total Revenue</span>
                    <span class="text-3xl font-serif font-bold text-foundation-grey"><?php echo $stats['total_revenue']; ?></span>
                    <div class="mt-4 flex items-center gap-1.5 text-approval-green text-[10px] font-bold">
                        <i data-lucide="trending-up" class="w-3 h-3"></i> +12.4% vs Last Qtr
                    </div>
                </div>
                <div class="bg-white p-8 shadow-premium border border-gray-100 border-b-2 border-b-rajkot-rust relative group overflow-hidden">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Pending Collections</span>
                    <span class="text-3xl font-serif font-bold text-rajkot-rust"><?php echo $stats['pending_collections']; ?></span>
                    <div class="mt-4 text-[10px] text-gray-400 font-medium">8 Outstanding Invoices</div>
                </div>
                <div class="bg-white p-8 shadow-premium border border-gray-100 border-b-2 border-b-pending-amber relative group overflow-hidden">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Scheduled Payouts</span>
                    <span class="text-3xl font-serif font-bold text-pending-amber"><?php echo $stats['workforce_payouts']; ?></span>
                    <div class="mt-4 text-[10px] text-gray-400 font-medium">Next run: Friday, 2 PM</div>
                </div>
                <div class="bg-white p-8 shadow-premium border border-gray-100 border-b-2 border-b-slate-accent relative group overflow-hidden">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Active Contracts</span>
                    <span class="text-3xl font-serif font-bold text-slate-accent"><?php echo $stats['active_contracts']; ?></span>
                    <div class="mt-4 text-[10px] text-gray-400 font-medium">Audit complete</div>
                </div>
            </div>

            <!-- Transaction Audit Ledger -->
            <div class="bg-white shadow-premium border border-gray-100 overflow-hidden relative">
                <div class="px-10 py-8 border-b border-gray-50 flex items-center justify-between bg-gray-50/50">
                    <h3 class="text-[10px] font-bold uppercase tracking-[0.4em] text-foundation-grey flex items-center gap-3">
                        <i data-lucide="book-open" class="w-4 h-4 text-rajkot-rust"></i> Transaction Registry
                    </h3>
                    <div class="relative w-72">
                        <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-300 w-4 h-4"></i>
                        <input type="search" placeholder="Filter ledger..." class="w-full pl-10 pr-4 py-2 bg-white border border-gray-100 outline-none focus:border-rajkot-rust text-xs transition-all">
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
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
                            <?php foreach($transactions as $t): ?>
                            <tr class="group hover:bg-gray-50/30 transition-all duration-300">
                                <td class="px-10 py-8">
                                    <span class="text-xs font-mono font-bold text-foundation-grey"><?php echo $t['id']; ?></span>
                                    <div class="text-[9px] text-gray-400 uppercase tracking-tighter mt-1"><?php echo $t['type']; ?> Event</div>
                                </td>
                                <td class="px-8 py-8">
                                    <p class="font-bold text-foundation-grey mb-1"><?php echo $t['project']; ?></p>
                                    <p class="text-[10px] text-gray-400 font-medium italic"><?php echo $t['date']; ?></p>
                                </td>
                                <td class="px-8 py-8">
                                    <p class="text-[11px] font-medium text-gray-600"><?php echo $t['party']; ?></p>
                                </td>
                                <td class="px-8 py-8">
                                    <span class="text-base font-serif font-bold <?php echo $t['type'] === 'Collection' ? 'text-approval-green' : 'text-foundation-grey'; ?>">
                                        <?php echo $t['type'] === 'Payout' ? '-' : '+'; ?><?php echo $t['amount']; ?>
                                    </span>
                                </td>
                                <td class="px-8 py-8">
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
                                <td class="px-10 py-8 text-right">
                                    <button class="text-gray-300 hover:text-rajkot-rust transition-colors p-2" title="View Receipt"><i data-lucide="receipt" class="w-4 h-4"></i></button>
                                    <button class="text-gray-300 hover:text-foundation-grey transition-colors p-2" title="Audit Trail"><i data-lucide="history" class="w-4 h-4"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="p-10 text-center border-t border-gray-50 bg-gray-50/30">
                    <button class="text-[10px] font-bold uppercase tracking-[0.3em] text-gray-300 hover:text-rajkot-rust transition-all border-b border-transparent hover:border-rajkot-rust pb-1 px-4">Initialize Ledger Pagination</button>
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
                        <div class="flex gap-4">
                            <button class="bg-white text-foundation-grey px-6 py-3 text-[10px] font-bold uppercase tracking-widest hover:bg-rajkot-rust hover:text-white transition-all shadow-lg active:scale-95">Configure Provider</button>
                            <button class="border border-white/20 text-white px-6 py-3 text-[10px] font-bold uppercase tracking-widest hover:bg-white/10 transition-all active:scale-95">Security Audit</button>
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

        <?php require_once __DIR__ . '/../Common/footer.php'; ?>
    </div>

</body>
</html>