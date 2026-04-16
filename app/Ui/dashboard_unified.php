<?php
if (!defined('PROJECT_ROOT')) {
  require_once __DIR__ . '/../Core/Bootstrap/init.php';
}

$variant = isset($DASHBOARD_VARIANT) ? (string)$DASHBOARD_VARIANT : '';
if ($variant === '') {
    $path = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    if (strpos($path, '/admin/') !== false) {
        $variant = 'admin';
    } elseif (strpos($path, '/worker/') !== false) {
        $variant = 'worker';
    } else {
        $variant = 'main';
    }
}

$sessionUser = $_SESSION['user'] ?? null;
if (is_array($sessionUser)) {
    $displayName = (string)($sessionUser['first_name'] ?? $sessionUser['username'] ?? $sessionUser['email'] ?? 'Demo User');
} else {
    $displayName = (string)($sessionUser ?: 'Demo User');
}

$userInitials = strtoupper(substr($displayName, 0, 2));
if ($userInitials === '') {
    $userInitials = 'RD';
}

$projects = [];
$workers = [];
$pendingApprovals = 0;
$invoicePending = 0.0;
$assignments = [];
$kpis = [
    'users_total' => 0,
    'projects_total' => 0,
    'leaves_pending' => 0,
    'reviews_pending' => 0,
];

if ($variant === 'main') {
    $projects = get_projects_basic(200);

    if (db_connected() && db_table_exists('users')) {
        $workers = db_fetch_all("SELECT id, username FROM users WHERE role = 'worker' ORDER BY username ASC");
    }

    if (db_connected() && db_table_exists('project_assignments')) {
        $assignments = db_fetch_all("SELECT a.project_id, p.name AS project_name, u.username AS worker_name, a.assigned_at
            FROM project_assignments a
            LEFT JOIN projects p ON p.id = a.project_id
            LEFT JOIN users u ON u.id = a.worker_id
            ORDER BY a.assigned_at DESC LIMIT 20");
    }

    if (db_connected() && db_table_exists('review_requests')) {
        $row = db_fetch("SELECT COUNT(*) AS c FROM review_requests WHERE status = 'pending'");
        if ($row) {
            $pendingApprovals = (int)($row['c'] ?? 0);
        }
    }

    if (db_connected() && db_table_exists('project_goods')) {
        $row = db_fetch('SELECT COALESCE(SUM(total_price),0) AS s FROM project_goods');
        if ($row) {
            $invoicePending = (float)($row['s'] ?? 0);
        }
    }
}

if ($variant === 'worker') {
    if (db_connected() && db_table_exists('projects')) {
    $mapLinkSelect = db_column_exists('projects', 'map_link') ? 'COALESCE(p.map_link,\'\') AS map_link' : "'' AS map_link";
    $projects = db_fetch_all("SELECT DISTINCT p.id, p.name, p.status, COALESCE(p.progress,0) AS progress, COALESCE(p.due,'1970-01-01') AS due, COALESCE(p.location,'') AS location,
      {$mapLinkSelect},
      COALESCE(NULLIF(p.address,''), NULLIF(p.location,''), '') AS address, p.latitude, p.longitude
            FROM projects p
            LEFT JOIN project_assignments pa ON pa.project_id = p.id
            ORDER BY p.id DESC LIMIT 200");
    }
}

if ($variant === 'admin') {
    if (db_connected() && db_table_exists('users')) {
        $row = db_fetch('SELECT COUNT(*) AS c FROM users', []);
        if (!empty($row['c'])) {
            $kpis['users_total'] = (int)$row['c'];
        }
    }

    if (db_connected() && db_table_exists('projects')) {
        $row = db_fetch('SELECT COUNT(*) AS c FROM projects', []);
        if (isset($row['c'])) {
            $kpis['projects_total'] = (int)$row['c'];
        }
    }

    if (db_connected() && db_table_exists('leave_requests')) {
        $row = db_fetch("SELECT COUNT(*) AS c FROM leave_requests WHERE status = 'pending'", []);
        if (isset($row['c'])) {
            $kpis['leaves_pending'] = (int)$row['c'];
        }
    }

    if (db_connected() && db_table_exists('review_requests')) {
        $row = db_fetch("SELECT COUNT(*) AS c FROM review_requests WHERE status = 'pending'", []);
        if (isset($row['c'])) {
            $kpis['reviews_pending'] = (int)$row['c'];
        }
    }
}

$counts = array_count_values(array_map(function($x) {
    return (string)($x['status'] ?? 'unknown');
}, $projects));

$titleMap = [
    'admin' => 'Admin Dashboard | Ripal Design',
    'worker' => 'Worker Dashboard | Ripal Design',
    'main' => 'Dashboard | Ripal Design',
];

$pageTitle = $titleMap[$variant] ?? $titleMap['main'];
?>
<!DOCTYPE html>
<html lang="en" class="bg-canvas-white">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?php echo esc($pageTitle); ?></title>
  <?php $HEADER_MODE = 'dashboard'; require_once __DIR__ . '/../../Common/header.php'; ?>
</head>
<body class="font-sans text-foundation-grey bg-canvas-white">

<div class="min-h-screen flex flex-col">
  <?php if ($variant === 'admin'): ?>
    <header class="bg-foundation-grey text-white pt-20 md:pt-24 pb-8 md:pb-12 px-4 sm:px-6 lg:px-8 shadow-lg mb-8 md:mb-12 border-b-2 border-rajkot-rust">
      <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div>
          <h1 class="text-3xl md:text-4xl font-serif font-bold">Admin Control Centre</h1>
          <p class="text-gray-400 mt-2 text-sm uppercase tracking-widest font-bold opacity-70">Full access &bull; <?php echo esc($displayName); ?></p>
        </div>
        <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
          <a href="<?php echo esc_attr(base_path('admin/project_management.php')); ?>" class="w-full md:w-auto bg-rajkot-rust hover:bg-red-700 text-white px-8 py-4 text-[10px] font-bold uppercase tracking-[0.2em] shadow-premium transition-all flex items-center justify-center gap-3 active:scale-95 no-underline">
            <i data-lucide="layout-grid" class="w-4 h-4"></i> Open Portfolio
          </a>
          <a href="<?php echo esc_attr(base_path('admin/user_management.php')); ?>" class="w-full md:w-auto bg-white/10 hover:bg-white/20 text-white border border-white/20 px-8 py-4 text-[10px] font-bold uppercase tracking-[0.2em] transition-all flex items-center justify-center gap-3 active:scale-95 no-underline">
            <i data-lucide="users" class="w-4 h-4 text-rajkot-rust"></i> User Controls
          </a>
        </div>
      </div>
    </header>

    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-8 md:mb-12">
        <div class="bg-white p-6 md:p-8 shadow-premium border border-gray-100 relative overflow-hidden">
          <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Total Users</span>
          <span class="text-2xl md:text-3xl font-serif font-bold text-foundation-grey"><?php echo (int)$kpis['users_total']; ?></span>
        </div>
        <div class="bg-white p-6 md:p-8 shadow-premium border border-gray-100 border-b-2 border-b-slate-accent relative overflow-hidden">
          <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Projects</span>
          <span class="text-2xl md:text-3xl font-serif font-bold text-slate-accent"><?php echo (int)$kpis['projects_total']; ?></span>
        </div>
        <div class="bg-white p-6 md:p-8 shadow-premium border border-gray-100 border-b-2 border-b-pending-amber relative overflow-hidden">
          <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Leave Pending</span>
          <span class="text-2xl md:text-3xl font-serif font-bold text-pending-amber"><?php echo (int)$kpis['leaves_pending']; ?></span>
        </div>
        <div class="bg-white p-6 md:p-8 shadow-premium border border-gray-100 border-b-2 border-b-rajkot-rust relative overflow-hidden">
          <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Reviews Pending</span>
          <span class="text-2xl md:text-3xl font-serif font-bold text-rajkot-rust"><?php echo (int)$kpis['reviews_pending']; ?></span>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 md:gap-8">
        <section class="bg-white shadow-premium border border-gray-100 p-6 md:p-8 lg:col-span-2">
          <div class="flex items-center justify-between gap-4 mb-6">
            <div>
              <h2 class="text-xl md:text-2xl font-serif font-bold">Quick Actions</h2>
              <p class="text-gray-500 text-sm mt-1">Admin shortcuts + cross-portal access.</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <a href="<?php echo esc_attr(base_path('admin/add_user.php')); ?>" class="group border border-gray-100 hover:border-rajkot-rust p-5 shadow-sm hover:shadow-premium transition-all no-underline bg-gray-50 hover:bg-white">
              <div class="flex items-center justify-between">
                <div>
                  <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Administration</div>
                  <div class="mt-1 font-bold text-foundation-grey">Add User</div>
                </div>
                <i data-lucide="user-plus" class="w-5 h-5 text-rajkot-rust"></i>
              </div>
            </a>
            <a href="<?php echo esc_attr(base_path('admin/leave_management.php')); ?>" class="group border border-gray-100 hover:border-rajkot-rust p-5 shadow-sm hover:shadow-premium transition-all no-underline bg-gray-50 hover:bg-white">
              <div class="flex items-center justify-between">
                <div>
                  <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Administration</div>
                  <div class="mt-1 font-bold text-foundation-grey">Leave Manager</div>
                </div>
                <i data-lucide="calendar-check" class="w-5 h-5 text-rajkot-rust"></i>
              </div>
            </a>
            <a href="<?php echo esc_attr(base_path('admin/payment_gateway.php')); ?>" class="group border border-gray-100 hover:border-rajkot-rust p-5 shadow-sm hover:shadow-premium transition-all no-underline bg-gray-50 hover:bg-white">
              <div class="flex items-center justify-between">
                <div>
                  <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Administration</div>
                  <div class="mt-1 font-bold text-foundation-grey">Financial Gateway</div>
                </div>
                <i data-lucide="wallet" class="w-5 h-5 text-rajkot-rust"></i>
              </div>
            </a>
            <a href="<?php echo esc_attr(base_path('admin/file_viewer.php')); ?>" class="group border border-gray-100 hover:border-rajkot-rust p-5 shadow-sm hover:shadow-premium transition-all no-underline bg-gray-50 hover:bg-white">
              <div class="flex items-center justify-between">
                <div>
                  <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Administration</div>
                  <div class="mt-1 font-bold text-foundation-grey">File Viewer</div>
                </div>
                <i data-lucide="file-search" class="w-5 h-5 text-rajkot-rust"></i>
              </div>
            </a>
            <a href="<?php echo esc_attr(base_path('worker/dashboard.php')); ?>" class="group border border-gray-100 hover:border-approval-green p-5 shadow-sm hover:shadow-premium transition-all no-underline bg-gray-50 hover:bg-white">
              <div class="flex items-center justify-between">
                <div>
                  <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Worker Portal</div>
                  <div class="mt-1 font-bold text-foundation-grey">Worker Dashboard</div>
                </div>
                <i data-lucide="hard-hat" class="w-5 h-5 text-approval-green"></i>
              </div>
            </a>
            <a href="<?php echo esc_attr(base_path('client/client_files.php')); ?>" class="group border border-gray-100 hover:border-slate-accent p-5 shadow-sm hover:shadow-premium transition-all no-underline bg-gray-50 hover:bg-white">
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
            <a href="<?php echo esc_attr(base_path('dashboard/dashboard.php')); ?>" class="inline-flex items-center justify-center w-full bg-foundation-grey hover:bg-rajkot-rust text-white px-6 py-3 text-[10px] font-bold uppercase tracking-[0.2em] transition-all no-underline">
              <i data-lucide="layout-dashboard" class="w-4 h-4 mr-2"></i> Open Main Dashboard
            </a>
          </div>
        </aside>
      </div>
    </main>
  <?php elseif ($variant === 'worker'): ?>
    <header class="bg-foundation-grey text-white pt-24 pb-12 px-4 shadow-lg">
      <div class="max-w-4xl mx-auto flex justify-between items-center">
        <div>
          <h1 class="text-4xl font-serif font-bold">Workforce Portal</h1>
          <p class="text-gray-400 text-sm mt-1 flex items-center gap-1">
            <i data-lucide="shield-check" class="w-4 h-4 text-approval-green"></i>
            On-site Supervisor Mode
          </p>
        </div>
        <div class="w-12 h-12 bg-rajkot-rust rounded-full flex items-center justify-center font-bold text-lg shadow-inner">
          <?php echo esc($userInitials); ?>
        </div>
      </div>
    </header>

    <main class="flex-grow p-4 max-w-4xl mx-auto w-full space-y-6">
      <div class="grid grid-cols-2 gap-3">
        <div class="bg-white p-4 border-l-4 border-rajkot-rust shadow-premium">
          <span class="text-gray-400 text-xs uppercase font-bold">Active Jobs</span>
          <span class="block text-2xl font-bold mt-1"><?php echo (int)($counts['ongoing'] ?? 0); ?></span>
        </div>
        <div class="bg-white p-4 border-l-4 border-pending-amber shadow-premium">
          <span class="text-gray-400 text-xs uppercase font-bold">Overdue</span>
          <span class="block text-2xl font-bold mt-1 text-red-600"><?php echo (int)($counts['overdue'] ?? 0); ?></span>
        </div>
      </div>

      <h2 class="text-lg font-bold text-foundation-grey flex items-center gap-2 px-1">
        <i data-lucide="briefcase" class="w-5 h-5 text-rajkot-rust"></i>
        Assigned Projects
      </h2>

      <div class="space-y-4">
        <?php foreach ($projects as $p): ?>
          <div class="bg-white border border-gray-200 shadow-premium overflow-hidden rounded-sm">
            <div class="p-5">
              <div class="flex justify-between items-start mb-3">
                <?php $statusClass = (($p['status'] ?? '') === 'overdue') ? 'bg-red-100 text-red-700 border-red-200' : 'bg-green-100 text-green-700 border-green-200'; ?>
                <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-widest border <?php echo esc_attr($statusClass); ?>">
                  <?php echo esc(str_replace('-', ' ', (string)($p['status'] ?? 'ongoing'))); ?>
                </span>
                <span class="text-xs text-gray-400 font-mono">#JOB-<?php echo (int)$p['id']; ?></span>
              </div>

              <h3 class="text-lg font-bold text-foundation-grey leading-tight mb-4"><?php echo esc($p['name'] ?? 'Untitled'); ?></h3>

              <div class="space-y-4">
                <div>
                  <div class="flex justify-between items-end mb-1">
                    <span class="text-xs font-bold text-gray-500 uppercase">Completion</span>
                    <span class="text-xs font-bold text-rajkot-rust"><?php echo (int)($p['progress'] ?? 0); ?>%</span>
                  </div>
                  <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                    <div class="bg-rajkot-rust h-full" style="width: <?php echo (int)($p['progress'] ?? 0); ?>%"></div>
                  </div>
                </div>

                <div class="flex items-center justify-between py-3 border-y border-gray-50">
                  <?php
                    $locationText = (($p['address'] ?? '') !== '') ? (string)$p['address'] : ((string)($p['location'] ?? 'Location not set'));
                    if (!empty($p['latitude']) && !empty($p['longitude'])) {
                        $directionDestination = (string)$p['latitude'] . ',' . (string)$p['longitude'];
                    } else {
                        $directionDestination = (string)(($p['address'] ?? '') !== '' ? $p['address'] : ($p['location'] ?? ''));
                    }
                    $mapHref = build_google_maps_direction_href((string)($p['map_link'] ?? ''), $directionDestination);
                  ?>
                  <div class="flex items-center text-sm text-gray-600 truncate mr-4">
                    <i data-lucide="map-pin" class="w-4 h-4 mr-2 shrink-0 text-gray-400"></i>
                    <span class="truncate">
                      <?php if ($mapHref !== ''): ?>
                        <a href="<?php echo esc_attr($mapHref); ?>" target="_blank" rel="noopener noreferrer" title="Open location in Google Maps"><?php echo esc($locationText); ?></a>
                      <?php else: ?>
                        <?php echo esc($locationText); ?>
                      <?php endif; ?>
                    </span>
                  </div>
                  <?php if ($mapHref !== ''): ?>
                    <a href="<?php echo esc_attr($mapHref); ?>" target="_blank" rel="noopener noreferrer" class="shrink-0 text-rajkot-rust hover:underline text-xs font-bold flex items-center gap-1">
                      DIRECTIONS <i data-lucide="external-link" class="w-3 h-3"></i>
                    </a>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-2 bg-gray-50 border-t border-gray-200">
              <a href="<?php echo esc_attr(base_path('worker/project_details.php?id=' . (int)$p['id'] . '#drawings')); ?>" class="flex flex-col items-center justify-center py-4 border-r border-gray-200 hover:bg-white transition-colors active:bg-gray-100 h-20 no-underline">
                <i data-lucide="file-text" class="w-6 h-6 text-slate-accent mb-1"></i>
                <span class="text-[10px] font-bold uppercase tracking-wider text-foundation-grey">Drawings</span>
              </a>
              <a href="<?php echo esc_attr(base_path('worker/project_details.php?id=' . (int)$p['id'])); ?>" class="flex flex-col items-center justify-center py-4 hover:bg-white transition-colors active:bg-gray-100 h-20 no-underline">
                <i data-lucide="layout-grid" class="w-6 h-6 text-rajkot-rust mb-1"></i>
                <span class="text-[10px] font-bold uppercase tracking-wider text-foundation-grey">Open Job</span>
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="pt-6">
        <button class="w-full bg-slate-accent text-white py-5 rounded-lg font-bold flex items-center justify-center gap-3 shadow-lg active:scale-[0.98] transition-all" type="button">
          <i data-lucide="truck" class="w-6 h-6 text-pending-amber"></i>
          MATERIAL REQUEST
        </button>
      </div>
    </main>
  <?php else: ?>
    <header class="bg-foundation-grey text-white pt-24 pb-12 px-4 sm:px-6 lg:px-8 shadow-lg">
      <div class="max-w-7xl mx-auto flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
        <div>
          <h1 class="text-4xl font-serif font-bold">System Dashboard</h1>
          <p class="text-gray-400 mt-2">Welcome back, <span class="text-rajkot-rust font-semibold"><?php echo esc($displayName); ?></span>. Here is your project overview.</p>
        </div>
        <div class="flex gap-3">
          <button class="bg-rajkot-rust hover:bg-red-700 text-white px-6 py-2.5 flex items-center gap-2 transition-all shadow-lg active:scale-95" onclick="location.href='<?php echo esc_attr(base_path('dashboard/project_details.php')); ?>'" type="button">
            <i data-lucide="plus-circle" class="w-5 h-5"></i> Create Project
          </button>
          <a href="<?php echo esc_attr(base_path('dashboard/profile.php')); ?>" class="bg-white/10 border border-white/20 text-white px-6 py-2.5 flex items-center gap-2 hover:bg-white/20 transition-all no-underline">
            <i data-lucide="user" class="w-5 h-5"></i> Profile
          </a>
        </div>
      </div>
    </header>

    <main class="flex-grow py-12 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto w-full">
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
            <span class="text-3xl font-bold"><?php echo (int)$pendingApprovals; ?></span>
            <i data-lucide="check-square" class="text-approval-green/20 w-8 h-8"></i>
           
          </div>
        </div>
        <div class="bg-white p-6 shadow-premium border-l-4 border-pending-amber">
          <p class="text-gray-500 text-sm uppercase tracking-wider font-semibold">Invoices Pending</p>
          <div class="flex items-end justify-between mt-2">
            <span class="text-3xl font-bold">  <?php echo number_format($invoicePending, 0, '.', ','); ?></span>
            <i data-lucide="indian-rupee" class="text-pending-amber/20 w-8 h-8"></i>
          </div>
        </div>
      </div>

      <div class="flex flex-col md:flex-row items-center justify-between gap-4 mb-8">
        <div class="flex gap-2">
          <a href="<?php echo esc_attr(base_path('admin/project_management.php')); ?>" class="px-4 py-2 text-sm font-medium border border-gray-300 hover:bg-gray-50 flex items-center gap-2 no-underline text-foundation-grey">
            <i data-lucide="settings" class="w-4 h-4"></i> Admin Panel
          </a>
          <a href="<?php echo esc_attr(base_path('dashboard/review_requests.php')); ?>" class="px-4 py-2 text-sm font-medium border border-gray-300 hover:bg-gray-50 flex items-center gap-2 no-underline text-foundation-grey">
            <i data-lucide="clipboard-list" class="w-4 h-4"></i> Review Requests
          </a>
        </div>
        <div class="relative w-full md:w-96">
          <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-4 h-4"></i>
          <input type="search" placeholder="Search projects..." class="w-full pl-10 pr-4 py-2.5 border border-gray-300 focus:ring-2 focus:ring-rajkot-rust focus:border-transparent outline-none" />
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php foreach ($projects as $p): ?>
          <div class="group bg-white border border-gray-200 shadow-premium hover:shadow-premium-hover transition-all duration-300 flex flex-col">
            <div class="p-6">
              <div class="flex justify-between items-start mb-4">
                <span class="px-3 py-1 bg-approval-green/10 text-approval-green text-xs font-bold uppercase tracking-widest border border-approval-green/20">Ongoing</span>
                <span class="text-xs text-gray-400 font-mono">#PRJ-0<?php echo (int)$p['id']; ?></span>
              </div>
              <h3 class="text-xl font-serif font-bold group-hover:text-rajkot-rust transition-colors line-clamp-2 min-h-[3.5rem] mb-2"><?php echo esc($p['name'] ?? 'Untitled'); ?></h3>
              <div class="space-y-3 mt-4">
                <?php
                  $locationText = (($p['address'] ?? '') !== '') ? (string)$p['address'] : ((string)($p['location'] ?? 'Location not set'));
                  if (!empty($p['latitude']) && !empty($p['longitude'])) {
                      $directionDestination = (string)$p['latitude'] . ',' . (string)$p['longitude'];
                  } else {
                      $directionDestination = (string)(($p['address'] ?? '') !== '' ? $p['address'] : ($p['location'] ?? ''));
                  }
                  $mapHref = build_google_maps_direction_href((string)($p['map_link'] ?? ''), $directionDestination);
                ?>
                <div class="flex items-center text-sm text-gray-500">
                  <i data-lucide="map-pin" class="w-4 h-4 mr-2"></i>
                  <span>
                    <?php if ($mapHref !== ''): ?>
                      <a href="<?php echo esc_attr($mapHref); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc($locationText); ?></a>
                    <?php else: ?>
                      <?php echo esc($locationText); ?>
                    <?php endif; ?>
                  </span>
                </div>
                <div class="flex items-center text-sm text-gray-500">
                  <i data-lucide="calendar" class="w-4 h-4 mr-2"></i> Due: <?php echo !empty($p['due']) ? esc((string)$p['due']) : 'N/A'; ?>
                </div>
                <div class="flex items-center text-sm text-gray-800 font-semibold">
                  <i data-lucide="indian-rupee" class="w-4 h-4 mr-2 text-rajkot-rust"></i> ₹ <?php echo number_format((float)($p['budget'] ?? 0), 0, '.', ','); ?>
                </div>
              </div>
            </div>
            <div class="mt-auto p-6 pt-0 flex gap-2 border-t border-gray-50 pt-6">
              <a href="<?php echo esc_attr(base_path('dashboard/project_details.php?id=' . (int)$p['id'])); ?>" class="flex-1 bg-foundation-grey hover:bg-black text-white text-center py-2 text-sm font-medium transition-colors flex items-center justify-center gap-2 no-underline">
                <i data-lucide="external-link" class="w-4 h-4"></i> View
              </a>
              <a href="<?php echo esc_attr(base_path('dashboard/goods_list.php?project_id=' . (int)$p['id'])); ?>" class="flex-1 border border-gray-300 hover:bg-gray-50 text-center py-2 text-sm font-medium transition-colors flex items-center justify-center gap-2 no-underline text-foundation-grey">
                <i data-lucide="package" class="w-4 h-4"></i> Goods
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </main>
  <?php endif; ?>

  <?php require_once __DIR__ . '/../../Common/footer.php'; ?>
</div>

<script>
if (window.lucide) {
  window.lucide.createIcons();
}
</script>
</body>
</html>