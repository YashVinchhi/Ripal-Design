<?php
session_start();
$user = $_SESSION['user'] ?? 'Demo User';

// Try to load projects and workers from database, fall back to static data when DB not available.
require_once __DIR__ . '/../includes/init.php';

$projects = [];
$workers = [];
$assignments = [];

// Load projects from DB when available, otherwise use fallback static data
if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $stmt = $pdo->query("SELECT id, name FROM projects ORDER BY id DESC LIMIT 200");
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Failed loading projects: ' . $e->getMessage());
        $projects = [
          ['id' => 1, 'name' => 'Renovation — Oak Street Residence'],
          ['id' => 2, 'name' => 'Shop Fitout — Market Road'],
          ['id' => 3, 'name' => 'New Build — Riverfront Villa'],
        ];
    }
} else {
    $projects = [
      ['id' => 1, 'name' => 'Shanti Sadan'],
      ['id' => 2, 'name' => 'Dharmendra Road Shopping Hub'],
      ['id' => 3, 'name' => 'Gokul Nivas (Nyari Dam)'],
    ];
}

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE role = :role ORDER BY username ASC");
        $stmt->execute(['role' => 'worker']);
        $workers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Failed loading workers: ' . $e->getMessage());
        $workers = [
          ['id' => 11, 'username' => 'Ramesh Kumar'],
          ['id' => 12, 'username' => 'Suresh Bhai'],
          ['id' => 13, 'username' => 'Mahesh M.'],
        ];
    }
} else {
    $workers = [
      ['id' => 11, 'username' => 'Rameshbhai Patel'],
      ['id' => 12, 'username' => 'Sureshbhai'],
      ['id' => 13, 'username' => 'Maheshbhai Mehta'],
    ];
}

// Load recent assignments if table exists
if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $stmt = $pdo->query("SELECT a.project_id, p.name AS project_name, u.username AS worker_name, a.assigned_at
                         FROM project_assignments a
                         LEFT JOIN projects p ON p.id = a.project_id
                         LEFT JOIN users u ON u.id = a.worker_id
                         ORDER BY a.assigned_at DESC LIMIT 20");
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Failed loading assignments: ' . $e->getMessage());
        $assignments = [
          ['project_name' => 'Renovation — Oak Street Residence', 'worker_name' => 'Ramesh Kumar', 'assigned_at' => '2026-02-01 10:00'],
          ['project_name' => 'Shop Fitout — Market Road', 'worker_name' => 'Suresh Bhai', 'assigned_at' => '2026-02-05 14:30'],
        ];
    }
} else {
    $assignments = [
      ['project_name' => 'Shanti Sadan', 'worker_name' => 'Rameshbhai Patel', 'assigned_at' => '2026-02-01 10:00'],
      ['project_name' => 'Dharmendra Road Shopping Hub', 'worker_name' => 'Sureshbhai', 'assigned_at' => '2026-02-05 14:30'],
    ];
}
?>
<!DOCTYPE html>
<html lang="en" class="bg-canvas-white">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Dashboard | Ripal Design</title>
  <?php $HEADER_MODE = 'dashboard'; require_once __DIR__ . '/../Common/header.php'; ?>
</head>
<body class="font-sans text-foundation-grey">
    
    <div class="min-h-screen flex flex-col">
        <!-- Dashboard Header -->
        <header class="bg-foundation-grey text-white pt-24 pb-12 px-4 sm:px-6 lg:px-8 shadow-lg">
            <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div>
                    <h1 class="text-4xl font-serif font-bold">System Dashboard</h1>
                    <p class="text-gray-400 mt-2">Welcome back, <span class="text-rajkot-rust font-semibold"><?php echo htmlspecialchars($user); ?></span>. Here's your project overview.</p>
                </div>
                <div class="flex gap-3">
                    <button class="bg-rajkot-rust hover:bg-red-700 text-white px-6 py-2.5 flex items-center gap-2 transition-all shadow-lg active:scale-95" onclick="location.href='project_details.php'">
                        <i data-lucide="plus-circle" class="w-5 h-5"></i> Create Project
                    </button>
                    <a href="profile.php" class="bg-white/10 border border-white/20 text-white px-6 py-2.5 flex items-center gap-2 hover:bg-white/20 transition-all">
                        <i data-lucide="user" class="w-5 h-5"></i> Profile
                    </a>
                </div>
            </div>
        </header>

        <main class="flex-grow py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto w-full">
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
                <div class="bg-white p-6 shadow-premium border-l-4 border-rajkot-rust">
                    <p class="text-gray-500 text-sm uppercase tracking-wider font-semibold">Active Projects</p>
                    <div class="flex items-end justify-between mt-2">
                        <span class="text-3xl font-bold"><?php echo count($projects); ?></span>
                        <i data-lucide="layout" class="text-rajkot-rust/20 w-8 h-8"></i>
                    </div>
                </div>
                <div class="bg-white p-6 shadow-premium border-l-4 border-slate-accent">
                    <p class="text-gray-500 text-sm uppercase tracking-wider font-semibold">Assigned Workers</p>
                    <div class="flex items-end justify-between mt-2">
                        <span class="text-3xl font-bold"><?php echo count($workers); ?></span>
                        <i data-lucide="users" class="text-slate-accent/20 w-8 h-8"></i>
                    </div>
                </div>
                <div class="bg-white p-6 shadow-premium border-l-4 border-approval-green">
                    <p class="text-gray-500 text-sm uppercase tracking-wider font-semibold">Pending Approvals</p>
                    <div class="flex items-end justify-between mt-2">
                        <span class="text-3xl font-bold">12</span>
                        <i data-lucide="check-square" class="text-approval-green/20 w-8 h-8"></i>
                    </div>
                </div>
                <div class="bg-white p-6 shadow-premium border-l-4 border-pending-amber">
                    <p class="text-gray-500 text-sm uppercase tracking-wider font-semibold">Invoices Pending</p>
                    <div class="flex items-end justify-between mt-2">
                        <span class="text-3xl font-bold">₹ 4.2L</span>
                        <i data-lucide="indian-rupee" class="text-pending-amber/20 w-8 h-8"></i>
                    </div>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="flex flex-col md:flex-row items-center justify-between gap-4 mb-8">
                <div class="flex gap-2">
                    <a href="../admin/project_management.php" class="px-4 py-2 text-sm font-medium border border-gray-300 hover:bg-gray-50 flex items-center gap-2">
                        <i data-lucide="settings" class="w-4 h-4"></i> Admin Panel
                    </a>
                    <a href="review_requests.php" class="px-4 py-2 text-sm font-medium border border-gray-300 hover:bg-gray-50 flex items-center gap-2">
                        <i data-lucide="clipboard-list" class="w-4 h-4"></i> Review Requests
                    </a>
                </div>
                <div class="relative w-full md:w-96">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-4 h-4"></i>
                    <input type="search" placeholder="Search projects..." class="w-full pl-10 pr-4 py-2.5 border border-gray-300 focus:ring-2 focus:ring-rajkot-rust focus:border-transparent outline-none">
                </div>
            </div>

            <!-- Projects Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach($projects as $p): ?>
                <div class="group bg-white border border-gray-200 shadow-premium hover:shadow-premium-hover transition-all duration-300 flex flex-col">
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-4">
                            <span class="px-3 py-1 bg-approval-green/10 text-approval-green text-xs font-bold uppercase tracking-widest border border-approval-green/20">Ongoing</span>
                            <span class="text-xs text-gray-400 font-mono">#PRJ-0<?php echo $p['id']; ?></span>
                        </div>
                        <h3 class="text-xl font-serif font-bold group-hover:text-rajkot-rust transition-colors line-clamp-2 min-h-[3.5rem] mb-2">
                            <?php echo htmlspecialchars($p['name']); ?>
                        </h3>
                        <div class="space-y-3 mt-4">
                            <div class="flex items-center text-sm text-gray-500">
                                <i data-lucide="map-pin" class="w-4 h-4 mr-2"></i> Rajkot, Gujarat
                            </div>
                            <div class="flex items-center text-sm text-gray-500">
                                <i data-lucide="maximize" class="w-4 h-4 mr-2"></i> 2,400 sq.ft.
                            </div>
                            <div class="flex items-center text-sm text-gray-800 font-semibold">
                                <i data-lucide="indian-rupee" class="w-4 h-4 mr-2 text-rajkot-rust"></i> ₹ 45,00,000
                            </div>
                        </div>
                    </div>
                    <div class="mt-auto p-6 pt-0 flex gap-2 border-t border-gray-50 pt-6">
                        <a href="project_details.php?id=<?php echo $p['id']; ?>" class="flex-1 bg-foundation-grey hover:bg-black text-white text-center py-2 text-sm font-medium transition-colors flex items-center justify-center gap-2">
                            <i data-lucide="external-link" class="w-4 h-4"></i> View
                        </a>
                        <a href="goods_list.php?project_id=<?php echo $p['id']; ?>" class="flex-1 border border-gray-300 hover:bg-gray-50 text-center py-2 text-sm font-medium transition-colors flex items-center justify-center gap-2">
                            <i data-lucide="package" class="w-4 h-4"></i> Goods
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </main>

        <?php require_once __DIR__ . '/../Common/footer.php'; ?>
    </div>

</body>
</html>