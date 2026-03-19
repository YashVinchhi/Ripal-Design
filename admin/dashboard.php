<?php
// Admin Dashboard (New)
require_once __DIR__ . '/../includes/init.php';

// Try to load some lightweight KPIs; fall back to placeholders.
$kpis = [
    'users_total' => 124,
    'projects_total' => 18,
    'leaves_pending' => 6,
    'reviews_pending' => 3,
];

$db = get_db();
if ($db instanceof PDO) {
    try {
        $row = db_fetch('SELECT COUNT(*) AS c FROM users', []);
        if (!empty($row['c'])) {
            $kpis['users_total'] = (int) $row['c'];
        }
    } catch (Exception $e) {
        // Ignore: demo/offline mode
    }

    // These tables may not exist in every environment, so each is isolated.
    try {
        $row = db_fetch('SELECT COUNT(*) AS c FROM projects', []);
        if (isset($row['c'])) {
            $kpis['projects_total'] = (int) $row['c'];
        }
    } catch (Exception $e) {
    }

    try {
        $row = db_fetch("SELECT COUNT(*) AS c FROM leave_requests WHERE status = 'pending'", []);
        if (isset($row['c'])) {
            $kpis['leaves_pending'] = (int) $row['c'];
        }
    } catch (Exception $e) {
    }

    try {
        $row = db_fetch("SELECT COUNT(*) AS c FROM review_requests WHERE status = 'pending'", []);
        if (isset($row['c'])) {
            $kpis['reviews_pending'] = (int) $row['c'];
        }
    } catch (Exception $e) {
    }
}

$adminName = 'Administrator';
?>
<!DOCTYPE html>
<html lang="en" class="bg-canvas-white">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Dashboard | Ripal Design</title>
  <?php $HEADER_MODE = 'dashboard'; require_once __DIR__ . '/../Common/header.php'; ?>
</head>
<body class="bg-canvas-white font-sans text-foundation-grey min-h-screen">

  <div class="min-h-screen flex flex-col">

    <header class="bg-foundation-grey text-white pt-20 md:pt-24 pb-8 md:pb-12 px-4 sm:px-6 lg:px-8 shadow-lg mb-8 md:mb-12 border-b-2 border-rajkot-rust">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <h1 class="text-3xl md:text-4xl font-serif font-bold">Admin Control Centre</h1>
                <p class="text-gray-400 mt-2 text-sm uppercase tracking-widest font-bold opacity-70">Full access • <?php echo esc($adminName); ?></p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                <a href="project_management.php" class="w-full md:w-auto bg-rajkot-rust hover:bg-red-700 text-white px-8 py-4 text-[10px] font-bold uppercase tracking-[0.2em] shadow-premium transition-all flex items-center justify-center gap-3 active:scale-95 no-underline">
                    <i data-lucide="layout-grid" class="w-4 h-4"></i> Open Portfolio
                </a>
                <a href="user_management.php" class="w-full md:w-auto bg-white/10 hover:bg-white/20 text-white border border-white/20 px-8 py-4 text-[10px] font-bold uppercase tracking-[0.2em] transition-all flex items-center justify-center gap-3 active:scale-95 no-underline">
                    <i data-lucide="users" class="w-4 h-4 text-rajkot-rust"></i> User Controls
                </a>
            </div>
        </div>
    </header>

    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-8 md:mb-12">
            <div class="bg-white p-6 md:p-8 shadow-premium border border-gray-100 relative overflow-hidden">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Total Users</span>
                <span class="text-2xl md:text-3xl font-serif font-bold text-foundation-grey"><?php echo (int) $kpis['users_total']; ?></span>
            </div>
            <div class="bg-white p-6 md:p-8 shadow-premium border border-gray-100 border-b-2 border-b-slate-accent relative overflow-hidden">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Projects</span>
                <span class="text-2xl md:text-3xl font-serif font-bold text-slate-accent"><?php echo (int) $kpis['projects_total']; ?></span>
            </div>
            <div class="bg-white p-6 md:p-8 shadow-premium border border-gray-100 border-b-2 border-b-pending-amber relative overflow-hidden">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Leave Pending</span>
                <span class="text-2xl md:text-3xl font-serif font-bold text-pending-amber"><?php echo (int) $kpis['leaves_pending']; ?></span>
            </div>
            <div class="bg-white p-6 md:p-8 shadow-premium border border-gray-100 border-b-2 border-b-rajkot-rust relative overflow-hidden">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Reviews Pending</span>
                <span class="text-2xl md:text-3xl font-serif font-bold text-rajkot-rust"><?php echo (int) $kpis['reviews_pending']; ?></span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 md:gap-8">
            <section class="bg-white shadow-premium border border-gray-100 p-6 md:p-8 lg:col-span-2">
                <div class="flex items-center justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-xl md:text-2xl font-serif font-bold">Quick Actions</h2>
                        <p class="text-gray-500 text-sm mt-1">Admin shortcuts + the same portals other roles use.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <a href="add_user.php" class="group border border-gray-100 hover:border-rajkot-rust p-5 shadow-sm hover:shadow-premium transition-all no-underline bg-gray-50 hover:bg-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Administration</div>
                                <div class="mt-1 font-bold text-foundation-grey">Add User</div>
                            </div>
                            <i data-lucide="user-plus" class="w-5 h-5 text-rajkot-rust"></i>
                        </div>
                    </a>
                    <a href="leave_management.php" class="group border border-gray-100 hover:border-rajkot-rust p-5 shadow-sm hover:shadow-premium transition-all no-underline bg-gray-50 hover:bg-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Administration</div>
                                <div class="mt-1 font-bold text-foundation-grey">Leave Manager</div>
                            </div>
                            <i data-lucide="calendar-check" class="w-5 h-5 text-rajkot-rust"></i>
                        </div>
                    </a>
                    <a href="payment_gateway.php" class="group border border-gray-100 hover:border-rajkot-rust p-5 shadow-sm hover:shadow-premium transition-all no-underline bg-gray-50 hover:bg-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Administration</div>
                                <div class="mt-1 font-bold text-foundation-grey">Financial Gateway</div>
                            </div>
                            <i data-lucide="wallet" class="w-5 h-5 text-rajkot-rust"></i>
                        </div>
                    </a>
                    <a href="file_viewer.php" class="group border border-gray-100 hover:border-rajkot-rust p-5 shadow-sm hover:shadow-premium transition-all no-underline bg-gray-50 hover:bg-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Administration</div>
                                <div class="mt-1 font-bold text-foundation-grey">File Viewer</div>
                            </div>
                            <i data-lucide="file-search" class="w-5 h-5 text-rajkot-rust"></i>
                        </div>
                    </a>

                    <a href="<?php echo esc_attr(BASE_PATH); ?>/worker/dashboard.php" class="group border border-gray-100 hover:border-approval-green p-5 shadow-sm hover:shadow-premium transition-all no-underline bg-gray-50 hover:bg-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Worker Portal</div>
                                <div class="mt-1 font-bold text-foundation-grey">Worker Dashboard</div>
                            </div>
                            <i data-lucide="hard-hat" class="w-5 h-5 text-approval-green"></i>
                        </div>
                    </a>
                    <a href="<?php echo esc_attr(BASE_PATH); ?>/client/client_files.php" class="group border border-gray-100 hover:border-slate-accent p-5 shadow-sm hover:shadow-premium transition-all no-underline bg-gray-50 hover:bg-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Client Portal</div>
                                <div class="mt-1 font-bold text-foundation-grey">Design Studio</div>
                            </div>
                            <i data-lucide="folder-open" class="w-5 h-5 text-slate-accent"></i>
                        </div>
                    </a>
                </div>
            </section>

            <aside class="bg-white shadow-premium border border-gray-100 p-6 md:p-8">
                <h2 class="text-xl md:text-2xl font-serif font-bold">Access Scope</h2>
                <p class="text-gray-500 text-sm mt-2">As an Admin, you can use:</p>
                <ul class="mt-4 space-y-3 text-sm">
                    <li class="flex items-center gap-2"><span class="w-1.5 h-[1px] bg-rajkot-rust"></span> Admin pages (users, projects, leave, payments)</li>
                    <li class="flex items-center gap-2"><span class="w-1.5 h-[1px] bg-rajkot-rust"></span> Dashboard tools (profile, reviews, project tools)</li>
                    <li class="flex items-center gap-2"><span class="w-1.5 h-[1px] bg-rajkot-rust"></span> Worker portal pages</li>
                    <li class="flex items-center gap-2"><span class="w-1.5 h-[1px] bg-rajkot-rust"></span> Client portal pages</li>
                </ul>

                <div class="mt-8 border-t border-gray-100 pt-6">
                    <a href="<?php echo esc_attr(BASE_PATH); ?>/dashboard/dashboard.php" class="inline-flex items-center justify-center w-full bg-foundation-grey hover:bg-rajkot-rust text-white px-6 py-3 text-[10px] font-bold uppercase tracking-[0.2em] transition-all no-underline">
                        <i data-lucide="layout-dashboard" class="w-4 h-4 mr-2"></i> Open Main Dashboard
                    </a>
                </div>
            </aside>
        </div>

    </main>

    <?php require_once __DIR__ . '/../Common/footer.php'; ?>
  </div>

  <script>
    if (window.lucide) {
      window.lucide.createIcons();
    }
  </script>
</body>
</html>
