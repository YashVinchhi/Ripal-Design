<?php
/**
 * Review Requests Management (Redesigned)
 * 
 * Displays and manages worker review requests.
 * Fixes header errors and adopts the premium Rajkot Rust design.
 */

require_once __DIR__ . '/../includes/init.php';

$user = $_SESSION['user'] ?? 'employee01';

// Load requests from DB
$requests = [];
if (db_connected()) {
    try {
        $db = get_db();
        $stmt = $db->query("
            SELECT rr.*, p.name as project_name, u.username as submitted_by
            FROM review_requests rr
            LEFT JOIN projects p ON p.id = rr.project_id
            LEFT JOIN users u ON u.id = rr.submitted_by
            ORDER BY FIELD(rr.urgency, 'critical', 'high', 'normal', 'low'), rr.created_at DESC
        ");
        $requests = $stmt->fetchAll();
    } catch (Exception $e) {
        error_log('Review Requests Error: ' . $e->getMessage());
    }
}

// Demo data fallback
if (empty($requests)) {
    $requests = [
        ['id'=>1, 'subject'=>'Foundation Inspection', 'description'=>'Foundation ready for audit.', 'project_name'=>'Oak St Residence', 'urgency'=>'high', 'status'=>'pending', 'submitted_by'=>'Ramesh K.', 'created_at'=>'2026-02-15 10:00:00'],
        ['id'=>2, 'subject'=>'Electrical Layout', 'description'=>'Conduits placed.', 'project_name'=>'Market Rd Shop', 'urgency'=>'normal', 'status'=>'approved', 'submitted_by'=>'Suresh B.', 'created_at'=>'2026-02-14 14:00:00'],
        ['id'=>3, 'subject'=>'Plumbing Test', 'description'=>'Pressure test required.', 'project_name'=>'Riverfront Villa', 'urgency'=>'critical', 'status'=>'changes_requested', 'submitted_by'=>'Dinesh S.', 'created_at'=>'2026-02-16 09:30:00'],
    ];
}

$counts = ['pending'=>0, 'approved'=>0, 'changes_requested'=>0, 'rejected'=>0];
foreach($requests as $r) {
    if(isset($counts[$r['status']])) $counts[$r['status']]++;
}

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id = intval($_POST['request_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if ($id && in_array($status, ['approved', 'rejected', 'changes_requested'])) {
        if (db_connected()) {
            try {
                $db = get_db();
                $stmt = $db->prepare("UPDATE review_requests SET status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$status, $id]);
            } catch (Exception $e) {}
        }
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="bg-canvas-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Review Requests | Ripal Design</title>
    <?php $HEADER_MODE = 'dashboard'; require_once __DIR__ . '/../Common/header.php'; ?>
</head>
<body class="bg-canvas-white font-sans text-foundation-grey min-h-screen">
    
    <div class="min-h-screen flex flex-col">
        <!-- Unified Dark Portal Header -->
        <header class="bg-foundation-grey text-white pt-24 pb-12 px-4 sm:px-6 lg:px-8 shadow-lg mb-12">
            <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <h1 class="text-4xl font-serif font-bold">Review Requests</h1>
                    <p class="text-gray-400 mt-2">Manage and audit work completion requests from the field.</p>
                </div>
                <div class="flex gap-4">
                    <div class="bg-white/5 border border-white/10 px-6 py-4 rounded-sm text-center">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Unresolved</span>
                        <span class="text-2xl font-bold text-rajkot-rust"><?php echo $counts['pending']; ?></span>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
            
            <!-- Quick Metrics Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-12">
                <div class="bg-white p-6 shadow-premium border border-gray-100 border-b-4 border-b-rajkot-rust">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Awaiting Review</span>
                    <span class="text-2xl font-bold"><?php echo $counts['pending']; ?></span>
                </div>
                <div class="bg-white p-6 shadow-premium border border-gray-100 border-b-4 border-b-approval-green">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Approved</span>
                    <span class="text-2xl font-bold"><?php echo $counts['approved']; ?></span>
                </div>
                <div class="bg-white p-6 shadow-premium border border-gray-100 border-b-4 border-b-pending-amber">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Revisions</span>
                    <span class="text-2xl font-bold"><?php echo $counts['changes_requested']; ?></span>
                </div>
                <div class="bg-white p-6 shadow-premium border border-gray-100 border-b-4 border-b-slate-accent">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-1">Closed/Rejected</span>
                    <span class="text-2xl font-bold"><?php echo $counts['rejected']; ?></span>
                </div>
            </div>

            <!-- Requests Table/Cards -->
            <div class="bg-white shadow-premium border border-gray-100">
                <div class="px-8 py-6 border-b border-gray-50 flex justify-between items-center">
                    <h2 class="text-xl font-serif font-bold">Request Registry</h2>
                    <div class="flex gap-2">
                        <button class="p-2 hover:bg-gray-50 text-gray-400 transition-colors" onclick="toggleFilters()" title="Filter Registry"><i data-lucide="filter" class="w-5 h-5"></i></button>
                        <button class="p-2 hover:bg-gray-50 text-gray-400 transition-colors" onclick="window.location.reload()" title="Refresh Registry"><i data-lucide="refresh-cw" class="w-5 h-5"></i></button>
                    </div>
                </div>

                <div id="filter-panel" class="hidden px-8 py-6 bg-gray-50/50 border-b border-gray-50 flex flex-wrap gap-4 items-center animate-fade-in">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Filter by:</span>
                    <select id="status-filter" class="text-xs bg-white border border-gray-200 px-3 py-1 outline-none focus:border-rajkot-rust">
                        <option value="all">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="changes_requested">Revisions</option>
                        <option value="rejected">Rejected</option>
                    </select>
                    <select id="urgency-filter" class="text-xs bg-white border border-gray-200 px-3 py-1 outline-none focus:border-rajkot-rust">
                        <option value="all">All Urgency Levels</option>
                        <option value="critical">Critical</option>
                        <option value="high">High</option>
                        <option value="normal">Normal</option>
                    </select>
                    <button onclick="applyFilters()" class="bg-foundation-grey text-white px-4 py-1 text-[10px] font-bold uppercase tracking-widest hover:bg-rajkot-rust transition-all">Apply Registry Sync</button>
                </div>

                <div class="divide-y divide-gray-50" id="requests-registry">
                    <?php if (empty($requests)): ?>
                        <div class="p-20 text-center">
                            <i data-lucide="clipboard-check" class="w-16 h-16 text-gray-100 mx-auto mb-4"></i>
                            <p class="text-gray-400 font-bold uppercase tracking-widest text-xs">All caught up. No pending reviews.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($requests as $r): ?>
                            <div class="request-row p-8 group hover:bg-gray-50/50 transition-all" 
                                 data-status="<?php echo htmlspecialchars($r['status']); ?>" 
                                 data-urgency="<?php echo htmlspecialchars($r['urgency']); ?>">
                                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                                    <div class="flex-grow space-y-3">
                                        <div class="flex flex-wrap items-center gap-3">
                                            <span class="px-2 py-0.5 text-[8px] font-black uppercase tracking-widest border border-gray-200 <?php echo $r['urgency'] === 'critical' ? 'bg-red-50 text-red-600 border-red-100' : 'text-gray-400'; ?>">
                                                <?php echo strtoupper($r['urgency']); ?>
                                            </span>
                                            <h3 class="text-lg font-serif font-bold group-hover:text-rajkot-rust transition-colors"><?php echo htmlspecialchars($r['subject']); ?></h3>
                                            <span class="text-[10px] font-bold uppercase tracking-widest px-2 py-0.5 rounded-full 
                                                <?php echo $r['status'] === 'pending' ? 'bg-amber-50 text-amber-600' : ($r['status'] === 'approved' ? 'bg-green-50 text-green-600' : 'bg-gray-100 text-gray-500'); ?>">
                                                <?php echo str_replace('_', ' ', strtoupper($r['status'])); ?>
                                            </span>
                                        </div>
                                        <div class="flex flex-wrap gap-4 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                            <span class="flex items-center gap-1.5"><i data-lucide="briefcase" class="w-3.5 h-3.5"></i> <?php echo htmlspecialchars($r['project_name']); ?></span>
                                            <span class="flex items-center gap-1.5"><i data-lucide="user" class="w-3.5 h-3.5"></i> <?php echo htmlspecialchars($r['submitted_by']); ?></span>
                                            <span class="flex items-center gap-1.5"><i data-lucide="clock" class="w-3.5 h-3.5"></i> <?php echo date('M d, H:i', strtotime($r['created_at'])); ?></span>
                                        </div>
                                        <p class="text-sm text-gray-500 max-w-3xl leading-relaxed"><?php echo htmlspecialchars($r['description']); ?></p>
                                    </div>
                                    
                                    <div class="flex items-center gap-3">
                                        <?php if ($r['status'] === 'pending'): ?>
                                            <form method="POST" class="flex gap-2">
                                                <input type="hidden" name="update_status" value="1">
                                                <input type="hidden" name="request_id" value="<?php echo $r['id']; ?>">
                                                <button name="status" value="approved" class="p-3 bg-approval-green/10 hover:bg-approval-green text-approval-green hover:text-white transition-all shadow-sm">
                                                    <i data-lucide="check" class="w-5 h-5"></i>
                                                </button>
                                                <button name="status" value="changes_requested" class="p-3 bg-pending-amber/10 hover:bg-pending-amber text-pending-amber hover:text-white transition-all shadow-sm">
                                                    <i data-lucide="alert-circle" class="w-5 h-5"></i>
                                                </button>
                                                <button name="status" value="rejected" class="p-3 bg-red-50 hover:bg-red-600 text-red-600 hover:text-white transition-all shadow-sm">
                                                    <i data-lucide="x" class="w-5 h-5"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button class="p-3 border border-gray-100 text-gray-300 cursor-not-allowed"><i data-lucide="lock" class="w-5 h-5"></i></button>
                                        <?php endif; ?>
                                        <button class="p-3 bg-foundation-grey hover:bg-rajkot-rust text-white transition-all shadow-md" onclick="alert('Viewing comprehensive audit details for: <?php echo addslashes($r['subject']); ?>')"><i data-lucide="eye" class="w-5 h-5"></i></button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>

        <?php require_once __DIR__ . '/../Common/footer.php'; ?>
    </div>

    <script>
        function toggleFilters() {
            const panel = document.getElementById('filter-panel');
            panel.classList.toggle('hidden');
        }

        function applyFilters() {
            const statusFilter = document.getElementById('status-filter').value;
            const urgencyFilter = document.getElementById('urgency-filter').value;
            
            document.querySelectorAll('.request-row').forEach(row => {
                const rowStatus = row.getAttribute('data-status');
                const rowUrgency = row.getAttribute('data-urgency');
                
                const matchesStatus = (statusFilter === 'all' || rowStatus === statusFilter);
                const matchesUrgency = (urgencyFilter === 'all' || rowUrgency === urgencyFilter);
                
                if (matchesStatus && matchesUrgency) {
                    row.classList.remove('hidden');
                } else {
                    row.classList.add('hidden');
                }
            });
        }
    </script>
</body>
</html>