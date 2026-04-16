<?php
if (!defined('PROJECT_ROOT')) {
  require_once __DIR__ . '/../includes/init.php';
  require_once __DIR__ . '/../includes/auth.php';
}
require_login();

$sessionUser = $_SESSION['user'] ?? null;
$sessionRole = is_array($sessionUser) ? strtolower((string)($sessionUser['role'] ?? '')) : '';
$sessionUserId = is_array($sessionUser) ? (int)($sessionUser['id'] ?? 0) : 0;
$displayName = is_array($sessionUser)
  ? (string)($sessionUser['first_name'] ?? $sessionUser['username'] ?? $sessionUser['email'] ?? 'User')
  : (string)($sessionUser ?: 'User');

// If session role is missing or appears generic, attempt to read authoritative role from DB users table
if (($sessionRole === '' || in_array($sessionRole, ['member', 'user', 'client', ''])) && $sessionUserId > 0 && db_connected() && db_table_exists('users')) {
  $u = db_fetch('SELECT id, username, first_name, last_name, role FROM users WHERE id = ? LIMIT 1', [$sessionUserId]);
  if ($u) {
    $dbRole = strtolower((string)($u['role'] ?? ''));
    if ($dbRole !== '') {
      $sessionRole = $dbRole;
      // also keep session in sync if available
      if (is_array($sessionUser)) {
        $_SESSION['user']['role'] = $dbRole;
      }
    }
    // prefer nicer display name when available
    $nameParts = trim((string)($u['first_name'] ?? '') . ' ' . (string)($u['last_name'] ?? ''));
    if ($nameParts !== '') {
      $displayName = $nameParts;
      if (is_array($sessionUser)) {
        $_SESSION['user']['first_name'] = $u['first_name'] ?? '';
        $_SESSION['user']['last_name'] = $u['last_name'] ?? '';
      }
    }
    if (!empty($u['username']) && ($displayName === '' || $displayName === 'User')) {
      $displayName = (string)$u['username'];
    }
  }
}

$roleContext = [
  'role_code' => '',
  'role_name' => '',
  'group_code' => '',
  'group_name' => '',
];

if ($sessionUserId > 0 && db_connected() && db_table_exists('user_roles') && db_table_exists('roles') && db_table_exists('role_groups')) {
  $rbacRole = db_fetch(
    'SELECT r.code AS role_code, r.name AS role_name, rg.code AS group_code, rg.name AS group_name
         FROM user_roles ur
         INNER JOIN roles r ON r.id = ur.role_id
         INNER JOIN role_groups rg ON rg.id = r.role_group_id
         WHERE ur.user_id = ?
         ORDER BY ur.is_primary DESC, ur.assigned_at DESC
         LIMIT 1',
    [$sessionUserId]
  );

  if ($rbacRole) {
    $roleContext['role_code'] = (string)($rbacRole['role_code'] ?? '');
    $roleContext['role_name'] = (string)($rbacRole['role_name'] ?? '');
    $roleContext['group_code'] = (string)($rbacRole['group_code'] ?? '');
    $roleContext['group_name'] = (string)($rbacRole['group_name'] ?? '');
  }
}

$variant = isset($DASHBOARD_VARIANT) ? (string)$DASHBOARD_VARIANT : '';
if ($variant === '') {
  $roleCode = strtolower((string)$roleContext['role_code']);
  if ($sessionRole === 'admin') {
    $variant = 'admin';
  } elseif ($sessionRole === 'worker') {
    $variant = 'worker';
  } elseif (in_array($sessionRole, ['employee', 'client'], true)) {
    $variant = 'main';
  } elseif ($roleCode === 'emp_admin_manager') {
    $variant = 'admin';
  } elseif (strtolower((string)$roleContext['group_code']) === 'worker' || stripos($roleCode, 'wrk_') === 0) {
    $variant = 'worker';
  } else {
    $variant = 'main';
  }
}

$isAdmin = $variant === 'admin';
$isWorker = $variant === 'worker';
$isClient = ($sessionRole === 'client');

// Keep worker-specific project view restricted to worker roles only.
$useWorkerProjectView = ($sessionRole === 'worker');
$isReadOnly = $useWorkerProjectView;

$roleDisplayName = $roleContext['role_name'] !== ''
  ? $roleContext['role_name']
  : ($sessionRole !== '' ? ucfirst(str_replace('_', ' ', $sessionRole)) : 'Member');
$groupDisplayName = $roleContext['group_name'] !== '' ? $roleContext['group_name'] : 'General';

$userInitials = strtoupper(substr($displayName, 0, 2));
if ($userInitials === '') {
  $userInitials = 'RD';
}

// Resolve avatar URL: prefer session value, fall back to DB and persist to session
$sessionAvatar = '';
if (is_array($sessionUser) && !empty($sessionUser['avatar'])) {
  $sessionAvatar = (string)$sessionUser['avatar'];
} elseif ($sessionUserId > 0 && db_connected() && db_table_exists('users')) {
  try {
    $row = db_fetch('SELECT avatar FROM users WHERE id = ? LIMIT 1', [$sessionUserId]);
    if ($row && !empty($row['avatar'])) {
      $sessionAvatar = (string)$row['avatar'];
      if (!empty($_SESSION['user']) && is_array($_SESSION['user'])) {
        $_SESSION['user']['avatar'] = $sessionAvatar;
      }
    }
  } catch (Exception $e) {
    // ignore DB read errors for avatar fallback
  }
}

$profileUrl = function_exists('base_path')
  ? base_path('dashboard/profile.php')
  : rtrim((string)BASE_PATH, '/') . '/dashboard/profile.php';

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
  if ($useWorkerProjectView && $sessionUserId > 0 && db_table_exists('project_assignments')) {
    $projects = db_fetch_all(
      'SELECT p.id, p.name, p.status, COALESCE(p.progress,0) AS progress, COALESCE(p.due,\'1970-01-01\') AS due, COALESCE(p.location,\'\') AS location,
              COALESCE(NULLIF(p.address,\'\'), NULLIF(p.location,\'\'), \'\') AS address, p.latitude, p.longitude
             FROM project_assignments pa
             INNER JOIN projects p ON p.id = pa.project_id
             WHERE pa.worker_id = ?
             ORDER BY pa.assigned_at DESC
             LIMIT 25',
      [$sessionUserId]
    );
  }

  if (empty($projects)) {
    $limit = $useWorkerProjectView ? 12 : 200;
    $projects = db_fetch_all("SELECT id, name, status, COALESCE(progress,0) AS progress, COALESCE(due,'1970-01-01') AS due, COALESCE(location,'') AS location,
      COALESCE(NULLIF(address,''), NULLIF(location,''), '') AS address, latitude, longitude, budget FROM projects ORDER BY id DESC LIMIT {$limit}");
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
  $row = db_fetch('SELECT COUNT(*) AS c FROM projects');
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

$counts = array_count_values(array_map(function ($p) {
  return (string)($p['status'] ?? 'unknown');
}, $projects));

$overdueCount = 0;
foreach ($projects as $p) {
  if (!empty($p['due']) && $p['due'] !== '1970-01-01' && strtotime((string)$p['due']) < time() && (string)($p['status'] ?? '') !== 'completed') {
    $overdueCount++;
  }
}

$title = 'Dashboard | Ripal Design';
$subtitle = $isAdmin ? 'Administrative View' : ($isWorker ? 'Operational View' : 'Role-Based View');
$badge = $isReadOnly ? 'Read Only' : 'Standard Access';

// Page heading can be overridden by setting $DASHBOARD_HEADING before including this file
$pageHeading = isset($DASHBOARD_HEADING) && $DASHBOARD_HEADING !== '' ? (string)$DASHBOARD_HEADING : 'Unified Dashboard';

$statCards = [];
if ($isAdmin) {
  $statCards = [
    ['label' => 'Total Users', 'value' => (int)$kpis['users_total'], 'icon' => 'users'],
    ['label' => 'Projects', 'value' => (int)$kpis['projects_total'], 'icon' => 'layout-grid'],
    ['label' => 'Leave Pending', 'value' => (int)$kpis['leaves_pending'], 'icon' => 'calendar-check'],
    ['label' => 'Reviews Pending', 'value' => (int)$kpis['reviews_pending'], 'icon' => 'clipboard-list'],
  ];
} elseif ($useWorkerProjectView) {
  // Base stat cards for worker-style view
  $statCards = [
    ['label' => 'Assigned Projects', 'value' => count($projects), 'icon' => 'briefcase'],
    ['label' => 'Overdue', 'value' => $overdueCount, 'icon' => 'alert-triangle'],
    ['label' => 'Pending Reviews', 'value' => $pendingApprovals, 'icon' => 'check-square'],
  ];

  // Only show the explicit "Read-Only Mode" indicator for actual worker sessions,
  // not for clients who are temporarily viewing the worker-style project list.
  if (!$isClient) {
    $statCards[] = ['label' => 'Read-Only Mode', 'value' => 'ON', 'icon' => 'shield'];
  }
} else {
  $statCards = [
    ['label' => 'Active Projects', 'value' => count($projects), 'icon' => 'layout'],
    ['label' => 'Assigned Workers', 'value' => count($workers), 'icon' => 'users'],
    ['label' => 'Pending Approvals', 'value' => $pendingApprovals, 'icon' => 'check-square'],
    ['label' => 'Invoices Pending', 'value' => number_format($invoicePending, 0, '.', ','), 'icon' => 'indian-rupee'],
  ];
}

// Compute responsive grid columns so when a card is removed (e.g. Read-Only for clients)
// the remaining cards expand to use the available space evenly.
$statCount = max(0, count($statCards));
$mdCols = ($statCount >= 3) ? 3 : max(1, $statCount);
$lgCols = ($statCount >= 4) ? 4 : max(1, $statCount);
$statGridClasses = "grid grid-cols-1 sm:grid-cols-2 md:grid-cols-{$mdCols} lg:grid-cols-{$lgCols} gap-4 md:gap-6 mb-8 md:mb-12";

$actionCards = [
  ['label' => 'Profile', 'href' => base_path('dashboard/profile.php'), 'icon' => 'user'],
  ['label' => ($sessionRole === 'client' ? 'Project Details (View)' : 'Create Project'), 'href' => ($sessionRole === 'client' ? base_path('dashboard/dashboard.php') : base_path('dashboard/project_details.php')), 'icon' => 'layout-grid'],
  ['label' => ($sessionRole === 'client' ? 'Review Requests (View)' : 'Review Requests'), 'href' => base_path('dashboard/review_requests.php'), 'icon' => 'clipboard-list'],
];

if ($isAdmin) {
  $actionCards[] = ['label' => 'User Controls', 'href' => base_path('admin/user_management.php'), 'icon' => 'user-cog'];
  $actionCards[] = ['label' => 'Portfolio', 'href' => base_path('admin/project_management.php'), 'icon' => 'folder-kanban'];
  $actionCards[] = ['label' => 'Content Manager', 'href' => base_path('admin/content_management.php'), 'icon' => 'file-pen-line'];
  $actionCards[] = ['label' => 'Contact Manager', 'href' => base_path('admin/contact_messages.php'), 'icon' => 'mail'];
}

if ($useWorkerProjectView) {
  $actionCards = [
    ['label' => 'Assigned Projects', 'href' => base_path('worker/assigned_projects.php'), 'icon' => 'briefcase'],
    ['label' => 'My Ratings', 'href' => base_path('worker/worker_rating.php'), 'icon' => 'star'],
    ['label' => 'Project Details (Read-Only)', 'href' => base_path('worker/project_details.php?readonly=1'), 'icon' => 'eye'],
  ];
}
?>

<!DOCTYPE html>
<html lang="en" class="bg-canvas-white">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?php echo esc($title); ?></title>
  <?php $HEADER_MODE = 'dashboard';
  require_once __DIR__ . '/header.php'; ?>
</head>

<body class="font-sans text-foundation-grey bg-canvas-white">

  <div class="min-h-screen flex flex-col">
    <header class="bg-foundation-grey text-white pt-20 md:pt-24 pb-8 md:pb-12 px-4 sm:px-6 lg:px-8 shadow-lg mb-8 md:mb-12 border-b-2 border-rajkot-rust">
      <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div>
          <h1 class="text-2xl md:text-3xl font-serif font-bold">Project Command Center</h1>
          <p class="text-gray-300 mt-2 text-sm">Manage projects, teams, and progress.</p>
        </div>
        <a href="<?php echo esc_attr($profileUrl); ?>" aria-label="Open profile settings" title="Open Profile" class="no-underline">
          <?php if ($sessionAvatar !== ''): ?>
            <div class="w-12 h-12 rounded-full overflow-hidden shadow-inner" style="display:inline-block;">
              <img src="<?php echo esc_attr($sessionAvatar); ?>" alt="Avatar" class="w-12 h-12 object-cover block" onerror="this.style.display='none'; document.getElementById('dashProfileInitials').style.display='flex';">
              <div id="dashProfileInitials" class="w-12 h-12 bg-rajkot-rust rounded-full flex items-center justify-center font-bold text-lg text-white" style="display:none;"><?php echo esc($userInitials); ?></div>
            </div>
          <?php else: ?>
            <div id="dashProfileInitials" class="w-12 h-12 bg-rajkot-rust rounded-full flex items-center justify-center font-bold text-lg shadow-inner no-underline text-white hover:bg-[#7f140a] transition-colors">
              <?php echo esc($userInitials); ?>
            </div>
          <?php endif; ?>
        </a>
      </div>
    </header>

    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-10">
      <div class="<?php echo $statGridClasses; ?>" data-stats-group>
        <?php foreach ($statCards as $card): ?>
          <div class="bg-white p-6 md:p-8 shadow-premium border border-gray-100 relative overflow-hidden" data-stat-card>
            <div class="flex items-start justify-between gap-4">
              <div>
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2"><?php echo esc($card['label']); ?></span>
                <span class="text-2xl md:text-3xl font-serif font-bold text-foundation-grey" data-countup data-countup-target="<?php echo esc_attr((string)$card['value']); ?>"><?php echo esc((string)$card['value']); ?></span>
              </div>
              <i data-lucide="<?php echo esc_attr($card['icon']); ?>" class="w-5 h-5 text-rajkot-rust"></i>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <section class="bg-white shadow-premium border border-gray-100 p-6 md:p-8 mb-8" data-quick-actions>
        <h2 class="text-xl md:text-2xl font-serif font-bold mb-5">Quick Actions</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <?php foreach ($actionCards as $card): ?>
            <a href="<?php echo esc_attr($card['href']); ?>" class="group border border-gray-100 hover:border-rajkot-rust p-5 shadow-sm hover:shadow-premium transition-all no-underline bg-gray-50 hover:bg-white" data-magnetic data-quick-action>
              <div class="flex items-center justify-between">
                <div class="font-bold text-foundation-grey"><?php echo esc($card['label']); ?></div>
                <i data-lucide="<?php echo esc_attr($card['icon']); ?>" class="w-5 h-5 text-rajkot-rust"></i>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="bg-white shadow-premium border border-gray-100 p-6 md:p-8">
        <div class="flex items-center justify-between mb-5">
          <h2 class="text-xl md:text-2xl font-serif font-bold">Projects</h2>
          <?php if ($isReadOnly): ?>
            <span class="text-[10px] uppercase tracking-widest font-bold text-gray-400">Read-only</span>
          <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" data-project-grid>
          <?php foreach ($projects as $p): ?>
            <div class="group bg-white border border-gray-200 shadow-premium hover:shadow-premium-hover transition-all duration-300 flex flex-col" data-project-card>
              <div class="p-6">
                <div class="flex justify-between items-start mb-3">
                  <span class="px-3 py-1 bg-approval-green/10 text-approval-green text-xs font-bold uppercase tracking-widest border border-approval-green/20">
                    <?php echo esc(strtoupper((string)($p['status'] ?? 'ongoing'))); ?>
                  </span>
                  <span class="text-xs text-gray-400 font-mono">#PRJ-<?php echo (int)($p['id'] ?? 0); ?></span>
                </div>
                <h3 class="text-lg font-serif font-bold group-hover:text-rajkot-rust transition-colors mb-2"><?php echo esc($p['name'] ?? 'Untitled'); ?></h3>
                <?php
                  $projectAddress = (string)(($p['address'] ?? '') !== '' ? $p['address'] : (($p['location'] ?? '') ?: 'Location not set'));
                  $mapQuery = '';
                  if (!empty($p['latitude']) && !empty($p['longitude'])) {
                    $mapQuery = (string)$p['latitude'] . ',' . (string)$p['longitude'];
                  } elseif ($projectAddress !== 'Location not set') {
                    $mapQuery = $projectAddress;
                  }
                ?>
                <div class="space-y-2 text-sm text-gray-500">
                  <div class="flex items-center"><i data-lucide="map-pin" class="w-4 h-4 mr-2"></i><?php echo esc($projectAddress); ?></div>
                  <div class="flex items-center"><i data-lucide="calendar" class="w-4 h-4 mr-2"></i>Due: <?php echo !empty($p['due']) && $p['due'] !== '1970-01-01' ? esc((string)$p['due']) : 'N/A'; ?></div>
                </div>
                <?php if ($useWorkerProjectView && $mapQuery !== ''): ?>
                  <div class="mt-4 border border-gray-200 overflow-hidden rounded-sm">
                    <iframe
                      title="Project location map"
                      class="w-full"
                      style="aspect-ratio: 1 / 1;"
                      loading="lazy"
                      referrerpolicy="no-referrer-when-downgrade"
                      src="https://maps.google.com/maps?q=<?php echo urlencode($mapQuery); ?>&amp;z=15&amp;output=embed"></iframe>
                  </div>
                <?php endif; ?>
              </div>
              <div class="mt-auto p-6 pt-0 flex gap-2 border-t border-gray-50 pt-6">
                <?php if ($isClient): ?>
                  <a href="<?php echo esc_attr(base_path('worker/project_details.php?id=' . (int)$p['id'] . '&readonly=1')); ?>" class="flex-1 bg-foundation-grey hover:bg-black text-white text-center py-2 text-sm font-medium transition-colors no-underline">View</a>
                <?php elseif ($useWorkerProjectView): ?>
                  <a href="<?php echo esc_attr(base_path('worker/project_details.php?id=' . (int)$p['id'])); ?>" class="flex-1 bg-foundation-grey hover:bg-black text-white text-center py-2 text-sm font-medium transition-colors no-underline">View</a>
                <?php else: ?>
                  <a href="<?php echo esc_attr(base_path('dashboard/project_details.php?id=' . (int)$p['id'])); ?>" class="flex-1 bg-foundation-grey hover:bg-black text-white text-center py-2 text-sm font-medium transition-colors no-underline">View</a>
                  <?php if (!$isReadOnly): ?>
                    <a href="<?php echo esc_attr(base_path('dashboard/goods_list.php?project_id=' . (int)$p['id'])); ?>" class="flex-1 border border-gray-300 hover:bg-gray-50 text-center py-2 text-sm font-medium transition-colors no-underline text-foundation-grey">Goods</a>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    </main>

    <?php require_once __DIR__ . '/footer.php'; ?>
  </div>

  <script>
    if (window.lucide) {
      window.lucide.createIcons();
    }

    (function () {
      if (!window.RDAnimations || !window.RDMotionPresets || typeof window.RDAnimations.initPageAnimations !== 'function') {
        return;
      }

      window.RDAnimations.initPageAnimations('dashboard-unified', document, function () {
        var presets = window.RDMotionPresets;
        var stats = document.querySelectorAll('[data-stat-card]');
        var actions = document.querySelectorAll('[data-quick-action]');
        var projects = document.querySelectorAll('[data-project-card]');
        var counters = document.querySelectorAll('[data-countup]');

        if (stats.length && typeof presets.staggeredEntry === 'function') {
          presets.staggeredEntry(stats, { distance: 18, duration: 0.42, stagger: 0.08 });
        }

        if (counters.length && typeof presets.countUp === 'function') {
          presets.countUp(counters, { duration: 0.72 });
        }

        if (actions.length && typeof presets.staggeredEntry === 'function') {
          presets.staggeredEntry(actions, { distance: 14, duration: 0.34, stagger: 0.06 });
        }

        if (actions.length && typeof presets.magneticButtons === 'function') {
          presets.magneticButtons('[data-quick-action]', { strength: 0.2, maxShift: 10 });
        }

        if (projects.length && typeof presets.staggeredEntry === 'function') {
          presets.staggeredEntry(projects, { distance: 16, duration: 0.36, stagger: 0.05 });
        }
      });
    })();
  </script>
</body>

</html>