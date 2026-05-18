<?php

$sessionUser = current_user();
$sessionRole = is_array($sessionUser) ? strtolower(trim((string)($sessionUser['role'] ?? ''))) : '';
$sessionUserId = is_array($sessionUser) ? (int)($sessionUser['id'] ?? 0) : 0;
$displayName = is_array($sessionUser)
    ? (string)($sessionUser['first_name'] ?? $sessionUser['username'] ?? $sessionUser['email'] ?? 'User')
    : 'User';

$isAdmin = in_array($sessionRole, ['admin', 'employee'], true);
$isWorker = $sessionRole === 'worker';
$isClient = $sessionRole === 'client';
$useWorkerProjectView = $isWorker;
$isReadOnly = $useWorkerProjectView;

$projects = [];
$workers = [];
$pendingApprovals = 0;
$invoicePending = 0.0;
$kpis = [
    'users_total' => 0,
    'projects_total' => 0,
    'leaves_pending' => 0,
    'reviews_pending' => 0,
];

if (db_connected() && db_table_exists('projects')) {
    $softDeleteProjects = function_exists('projects_soft_delete_sql') ? projects_soft_delete_sql('projects', ' AND ') : '';
    $softDeleteP = function_exists('projects_soft_delete_sql') ? projects_soft_delete_sql('p', ' AND ') : '';

    if ($useWorkerProjectView && $sessionUserId > 0 && db_table_exists('project_assignments')) {
        $sql = <<<'SQL'
SELECT p.id, p.name, p.status, COALESCE(p.progress,0) AS progress, COALESCE(p.due,'1970-01-01') AS due, COALESCE(p.location,'') AS location,
       COALESCE(NULLIF(p.address,''), NULLIF(p.location,''), '') AS address, p.latitude, p.longitude
  FROM project_assignments pa
  INNER JOIN projects p ON p.id = pa.project_id
 WHERE pa.worker_id = ?
SQL;
        $sql .= $softDeleteP;
        $sql .= ' ORDER BY pa.assigned_at DESC LIMIT 25';
        $projects = db_fetch_all($sql, [$sessionUserId]);
    } elseif ($isClient && $sessionUserId > 0) {
        $sql = <<<'SQL'
SELECT id, name, status, COALESCE(progress,0) AS progress, COALESCE(due,'1970-01-01') AS due, COALESCE(location,'') AS location,
       COALESCE(NULLIF(address,''), NULLIF(location,''), '') AS address, latitude, longitude
  FROM projects
 WHERE (created_by = ? OR client_id = ?)
SQL;
        $sql .= $softDeleteProjects;
        $sql .= ' ORDER BY id DESC LIMIT 12';
        $projects = db_fetch_all($sql, [$sessionUserId, $sessionUserId]);
    } else {
        $sql = <<<'SQL'
SELECT id, name, status, COALESCE(progress,0) AS progress, COALESCE(due,'1970-01-01') AS due, COALESCE(location,'') AS location,
       COALESCE(NULLIF(address,''), NULLIF(location,''), '') AS address, latitude, longitude, budget
  FROM projects
SQL;
        $sql .= $softDeleteProjects;
        $sql .= ' ORDER BY id DESC LIMIT 12';
        $projects = db_fetch_all($sql);
    }
}

if (db_connected() && db_table_exists('users')) {
    $workers = db_fetch_all("SELECT id, username FROM users WHERE role = 'worker' ORDER BY username ASC");
}

if (db_connected() && db_table_exists('review_requests')) {
    $row = db_fetch("SELECT COUNT(*) AS c FROM review_requests WHERE status = 'pending'");
    $pendingApprovals = (int)($row['c'] ?? 0);
}

if (db_connected() && db_table_exists('project_goods')) {
    $row = db_fetch('SELECT COALESCE(SUM(total_price),0) AS s FROM project_goods');
    $invoicePending = (float)($row['s'] ?? 0);
}

if ($isAdmin && db_connected() && db_table_exists('users')) {
    $row = db_fetch('SELECT COUNT(*) AS c FROM users');
    $kpis['users_total'] = (int)($row['c'] ?? 0);
}
if ($isAdmin && db_connected() && db_table_exists('projects')) {
    $row = db_fetch('SELECT COUNT(*) AS c FROM projects' . (function_exists('projects_soft_delete_sql') ? projects_soft_delete_sql('projects', ' WHERE ') : ''));
    $kpis['projects_total'] = (int)($row['c'] ?? 0);
}
if ($isAdmin && db_connected() && db_table_exists('leave_requests')) {
    $row = db_fetch("SELECT COUNT(*) AS c FROM leave_requests WHERE status = 'pending'");
    $kpis['leaves_pending'] = (int)($row['c'] ?? 0);
}
if ($isAdmin && db_connected() && db_table_exists('review_requests')) {
    $row = db_fetch("SELECT COUNT(*) AS c FROM review_requests WHERE status = 'pending'");
    $kpis['reviews_pending'] = (int)($row['c'] ?? 0);
}

$overdueCount = 0;
foreach ($projects as $project) {
    if (!empty($project['due']) && $project['due'] !== '1970-01-01' && strtotime((string)$project['due']) < time() && (string)($project['status'] ?? '') !== 'completed') {
        $overdueCount++;
    }
}

$statCards = [];
if ($isAdmin) {
    $statCards = [
        ['label' => 'Total Users', 'value' => (int)$kpis['users_total'], 'icon' => 'users'],
        ['label' => 'Projects', 'value' => (int)$kpis['projects_total'], 'icon' => 'layout-grid'],
        ['label' => 'Leave Pending', 'value' => (int)$kpis['leaves_pending'], 'icon' => 'calendar-check'],
        ['label' => 'Reviews Pending', 'value' => (int)$kpis['reviews_pending'], 'icon' => 'clipboard-list'],
    ];
} elseif ($useWorkerProjectView) {
    $statCards = [
        ['label' => 'Assigned Projects', 'value' => count($projects), 'icon' => 'briefcase'],
        ['label' => 'Overdue', 'value' => $overdueCount, 'icon' => 'alert-triangle'],
        ['label' => 'Pending Reviews', 'value' => $pendingApprovals, 'icon' => 'check-square'],
    ];
    if (!$isClient) {
        $statCards[] = ['label' => 'Read-Only Mode', 'value' => 'ON', 'icon' => 'shield'];
    }
} else {
    $statCards = [
        ['label' => 'Active Projects', 'value' => count($projects), 'icon' => 'layout-grid'],
        ['label' => 'Assigned Workers', 'value' => count($workers), 'icon' => 'users'],
        ['label' => 'Pending Approvals', 'value' => $pendingApprovals, 'icon' => 'check-square'],
        ['label' => 'Invoices Pending', 'value' => number_format($invoicePending, 0, '.', ','), 'icon' => 'indian-rupee'],
    ];
}

$statCount = max(0, count($statCards));
$mdCols = ($statCount >= 3) ? 3 : max(1, $statCount);
$lgCols = ($statCount >= 4) ? 4 : max(1, $statCount);
$statGridClasses = 'grid grid-cols-1 sm:grid-cols-2 md:grid-cols-' . $mdCols . ' lg:grid-cols-' . $lgCols . ' gap-4 md:gap-6 mb-8 md:mb-12';
?>
<main class="dashboard-main flex-grow px-4 sm:px-6 lg:px-8 pb-10">
    <?php render_if('dashboard', 'widget.stats', static function () use ($statCards, $statGridClasses): void { ?>
        <div class="<?php echo htmlspecialchars($statGridClasses, ENT_QUOTES, 'UTF-8'); ?>" data-stats-group style="margin-top:10vh;">
            <?php foreach ($statCards as $card): ?>
                <div class="bg-white p-6 md:p-8 shadow-premium border border-gray-100 relative overflow-hidden" data-stat-card>
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2"><?php echo htmlspecialchars((string)$card['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="text-2xl md:text-3xl font-serif font-black stat-number text-foundation-grey" data-countup data-countup-target="<?php echo htmlspecialchars((string)$card['value'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string)$card['value'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <i data-lucide="<?php echo htmlspecialchars((string)$card['icon'], ENT_QUOTES, 'UTF-8'); ?>" class="w-5 h-5 text-rajkot-rust"></i>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php }); ?>

    <?php render_if('dashboard', 'widget.revenue', static function () use ($invoicePending): void { ?>
        <section class="bg-white shadow-premium border border-gray-100 p-6 md:p-8 mb-8">
            <div class="flex items-center justify-between gap-4 mb-4">
                <h2 class="text-xl md:text-2xl font-serif font-bold">Revenue Snapshot</h2>
                <span class="text-[10px] uppercase tracking-widest font-bold text-gray-400">Pending billing</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-stretch">
                <div class="md:col-span-2 bg-gray-50 border border-gray-100 p-5">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2">Outstanding Invoices</p>
                    <p class="text-3xl font-serif font-black text-foundation-grey"><?php echo htmlspecialchars(number_format((float)$invoicePending, 0, '.', ','), ENT_QUOTES, 'UTF-8'); ?></p>
                    <p class="text-sm text-gray-500 mt-2">Aggregated from active billing records in the current workspace.</p>
                </div>
                <div class="bg-foundation-grey text-white p-5">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-300 mb-2">Billing health</p>
                    <p class="text-2xl font-serif font-bold text-rajkot-rust">Active</p>
                    <p class="text-sm text-gray-300 mt-2">Revenue visibility is controlled by dashboard permissions.</p>
                </div>
            </div>
        </section>
    <?php }); ?>

    <?php render_if('dashboard', 'widget.worker_tasks', static function () use ($projects, $useWorkerProjectView, $isClient): void { ?>
        <section class="bg-white shadow-premium border border-gray-100 p-6 md:p-8 mb-8" data-quick-actions>
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-xl md:text-2xl font-serif font-bold">Projects</h2>
                <?php if ($useWorkerProjectView): ?>
                    <span class="text-[10px] uppercase tracking-widest font-bold text-gray-400">Read-only</span>
                <?php endif; ?>
            </div>

            <?php if (empty($projects)): ?>
                <div class="border border-gray-100 bg-gray-50 p-6 text-sm text-gray-500">No projects found for this dashboard view.</div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" data-project-grid>
                    <?php foreach ($projects as $project): ?>
                        <div class="group bg-white border border-gray-200 shadow-premium hover:shadow-premium-hover transition-all duration-300 flex flex-col" data-project-card>
                            <div class="p-6">
                                <div class="flex justify-between items-start mb-3">
                                    <span class="px-3 py-1 bg-approval-green/10 text-approval-green text-xs font-bold uppercase tracking-widest border border-approval-green/20"><?php echo htmlspecialchars(strtoupper((string)($project['status'] ?? 'ongoing')), ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="text-xs text-gray-400 font-mono">#PRJ-<?php echo (int)($project['id'] ?? 0); ?></span>
                                </div>
                                <h3 class="text-lg project-title font-serif font-black group-hover:text-rajkot-rust transition-colors mb-2"><?php echo htmlspecialchars((string)($project['name'] ?? 'Untitled'), ENT_QUOTES, 'UTF-8'); ?></h3>
                                <?php $projectAddress = (string)(($project['address'] ?? '') !== '' ? $project['address'] : (($project['location'] ?? '') ?: 'Location not set')); ?>
                                <div class="space-y-2 text-sm text-gray-600 font-semibold">
                                    <div class="flex items-center project-location"><i data-lucide="map-pin" class="w-4 h-4 mr-2"></i><?php echo htmlspecialchars($projectAddress, ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div class="flex items-center"><i data-lucide="calendar" class="w-4 h-4 mr-2"></i>Due: <?php echo !empty($project['due']) && $project['due'] !== '1970-01-01' ? htmlspecialchars((string)$project['due'], ENT_QUOTES, 'UTF-8') : 'N/A'; ?></div>
                                </div>
                                <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden mt-4">
                                    <div class="bg-rajkot-rust h-full" style="width: <?php echo (int)($project['progress'] ?? 0); ?>%"></div>
                                </div>
                            </div>
                            <div class="mt-auto p-6 pt-0 flex gap-2 border-t border-gray-50 pt-6">
                                <?php if ($isClient): ?>
                                    <a href="<?php echo htmlspecialchars(base_path('worker/project_details.php?id=' . (int)($project['id'] ?? 0) . '&readonly=1'), ENT_QUOTES, 'UTF-8'); ?>" class="flex-1 bg-foundation-grey hover:bg-black text-white text-center py-2 text-sm font-medium transition-colors no-underline">View</a>
                                <?php elseif ($useWorkerProjectView): ?>
                                    <a href="<?php echo htmlspecialchars(base_path('worker/project_details.php?id=' . (int)($project['id'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?>" class="flex-1 bg-foundation-grey hover:bg-black text-white text-center py-2 text-sm font-medium transition-colors no-underline">View</a>
                                <?php else: ?>
                                    <a href="<?php echo htmlspecialchars(base_path('dashboard/project_details.php?id=' . (int)($project['id'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?>" class="flex-1 bg-foundation-grey hover:bg-black text-white text-center py-2 text-sm font-medium transition-colors no-underline">View</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php }); ?>

    <?php render_if('dashboard', 'panel.approval', static function () use ($pendingApprovals): void { ?>
        <section class="bg-slate-accent text-white p-8 flex items-center gap-6 shadow-premium relative overflow-hidden group">
            <i data-lucide="info" class="w-16 h-16 text-white/10 absolute -right-4 -top-4 transform rotate-12 group-hover:scale-110 transition-transform"></i>
            <div class="shrink-0 w-16 h-16 bg-white/10 flex items-center justify-center">
                <i data-lucide="alert-triangle" class="w-8 h-8 text-pending-amber"></i>
            </div>
            <div class="flex-1">
                <h4 class="text-lg font-serif font-bold mb-1">Pending Approvals</h4>
                <p class="text-sm text-gray-300">There are <?php echo (int)$pendingApprovals; ?> items waiting for review.</p>
            </div>
            <a href="<?php echo htmlspecialchars(base_path('dashboard/review_requests.php'), ENT_QUOTES, 'UTF-8'); ?>" class="bg-white text-foundation-grey px-4 py-2 text-[10px] font-bold uppercase tracking-[0.2em] no-underline">Open</a>
        </section>
    <?php }); ?>
</main>
