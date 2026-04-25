<?php
if (!defined('PROJECT_ROOT')) { require_once dirname(__DIR__, 4) . '/app/Core/Bootstrap/init.php'; }
// User Management (Redesigned UI)
require_once PROJECT_ROOT . '/app/Core/Bootstrap/init.php';

require_login();
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['action'] ?? '') === 'update_status') {
    require_csrf();

    $userId = (int)($_POST['user_id'] ?? 0);
    $newStatus = strtolower(trim((string)($_POST['new_status'] ?? '')));
    $allowedStatuses = ['active', 'pending', 'suspended'];
    $statusMsg = 'error';

    if ($userId > 0 && in_array($newStatus, $allowedStatuses, true)) {
        $statusDb = get_db();
        if ($statusDb instanceof PDO) {
            $update = $statusDb->prepare('UPDATE users SET status = ?, updated_at = NOW() WHERE id = ? LIMIT 1');
            $update->execute([$newStatus, $userId]);
            $statusMsg = 'updated';
        }
    }

    $redirect = [];
    $qSearch = trim((string)($_GET['search'] ?? ''));
    $qRole = trim((string)($_GET['role'] ?? ''));
    if ($qSearch !== '') {
        $redirect['search'] = $qSearch;
    }
    if ($qRole !== '') {
        $redirect['role'] = $qRole;
    }
    $redirect['status_msg'] = $statusMsg;

    $location = $_SERVER['PHP_SELF'] . (empty($redirect) ? '' : ('?' . http_build_query($redirect)));
    header('Location: ' . $location);
    exit;
}

$roleCounts = get_user_role_counts();
$search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
$role = isset($_GET['role']) ? strtolower(trim((string)$_GET['role'])) : 'all';
$statusMsg = isset($_GET['status_msg']) ? strtolower(trim((string)$_GET['status_msg'])) : '';
$allowedRoles = ['all', 'admin', 'employee', 'worker', 'client'];
if (!in_array($role, $allowedRoles, true)) {
    $role = 'all';
}
$users = [];

$db = get_db();
if ($db instanceof PDO) {
    // Prefer worker_scores/worker_metric_events if available (new scoring system). Fall back to worker_ratings if present.
    $hasNewScoring = false;
    $hasOldRatings = false;
    try {
        $tableCheck = $db->query("SHOW TABLES LIKE 'worker_scores'");
        $hasNewScoring = (bool)$tableCheck->fetch(PDO::FETCH_NUM);
        $tableCheck2 = $db->query("SHOW TABLES LIKE 'worker_ratings'");
        $hasOldRatings = (bool)$tableCheck2->fetch(PDO::FETCH_NUM);
    } catch (Exception $e) {
        $hasNewScoring = false; $hasOldRatings = false;
    }

    if ($hasNewScoring) {
        $ratingSelect = ', COALESCE(ws.final_score,0) AS final_score, COALESCE(ev.total_events,0) AS total_ratings, COALESCE(ws.decision_score,0) AS decision_score';
        $ratingJoin = ' LEFT JOIN worker_scores ws ON ws.worker_id = users.id LEFT JOIN (SELECT worker_id, COUNT(*) AS total_events FROM worker_metric_events GROUP BY worker_id) ev ON ev.worker_id = users.id';
    } elseif ($hasOldRatings) {
        $ratingSelect = ', COALESCE(r.avg_rating, 0) AS avg_rating, COALESCE(r.total_ratings, 0) AS total_ratings';
        $ratingJoin = ' LEFT JOIN (SELECT worker_id, AVG(rating) AS avg_rating, COUNT(*) AS total_ratings FROM worker_ratings GROUP BY worker_id) r ON r.worker_id = users.id';
    } else {
        $ratingSelect = ', 0 AS avg_rating, 0 AS total_ratings';
        $ratingJoin = '';
    }

    $sql = 'SELECT users.id AS id, users.username, users.first_name, users.last_name, users.full_name, users.email, users.role, users.status, COALESCE(users.updated_at, users.created_at) AS last_sync' . $ratingSelect . ' FROM users' . $ratingJoin;
    $where = [];
    $params = [];

    if ($search !== '') {
        $searchLike = '%' . $search . '%';
        $where[] = '(full_name LIKE :search OR first_name LIKE :search OR last_name LIKE :search OR username LIKE :search OR email LIKE :search)';
        $params[':search'] = $searchLike;
    }

    if ($role !== 'all') {
        $where[] = 'LOWER(role) = :role';
        $params[':role'] = $role;
    }

    if (!empty($where)) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $sql .= ' ORDER BY users.id DESC LIMIT 200';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} else {
    // Fallback to existing helper if PDO is unavailable.
    $users = db_fetch_all('SELECT id, username, first_name, last_name, full_name, email, role, status, COALESCE(updated_at, created_at) AS last_sync, 0 AS avg_rating, 0 AS total_ratings FROM users ORDER BY id DESC LIMIT 200');
}
?>
<!DOCTYPE html>
<html lang="en" class="bg-canvas-white">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>User Management | Ripal Design</title>
    <style>
        /* Keep table area and column widths stable even when search returns few rows. */
        .registry-table-shell {
            min-height: 520px;
        }

        .registry-table-scroll {
            min-height: 420px;
        }

        .registry-table-fixed {
            table-layout: fixed;
            width: 100%;
        }

        @media (max-width: 767px) {
            .registry-table-shell,
            .registry-table-scroll {
                min-height: auto;
            }

            .registry-table-fixed {
                table-layout: auto;
            }

            .registry-table-fixed colgroup {
                display: none;
            }

            .registry-table-scroll {
                overflow-x: visible;
            }

            .admin-table,
            .admin-table tbody,
            .admin-table tr,
            .admin-table td {
                display: block;
                width: 100%;
            }

            .admin-table tr.user-row {
                margin-bottom: 1rem;
                border-radius: 0.75rem;
                border: 1px solid #e5e7eb;
                background: #fff;
            }

            .admin-table td[data-label] {
                position: relative;
                padding-top: 1.6rem;
            }

            .admin-table td[data-label]::before {
                content: attr(data-label);
                display: block;
                margin-bottom: 0.4rem;
                font-size: 10px;
                font-weight: 700;
                letter-spacing: 0.12em;
                text-transform: uppercase;
                color: #9ca3af;
            }

            .admin-table td[data-label="Registry Actions"] {
                padding-top: 1rem;
            }

            .admin-table td[data-label="Registry Actions"]::before {
                margin-bottom: 0.75rem;
            }
        }
        /* Force sharp corners on this admin page */
        .user-management-sharp *,
        .user-management-sharp *::before,
        .user-management-sharp *::after {
            border-radius: 0 !important;
        }
        .user-management-sharp .rounded,
        .user-management-sharp .rounded-lg,
        .user-management-sharp .rounded-full {
            border-radius: 0 !important;
        }
    </style>
  <?php $HEADER_MODE = 'dashboard'; require_once PROJECT_ROOT . '/Common/header.php'; ?>
</head>
<body class="user-management-sharp bg-canvas-white font-sans text-foundation-grey min-h-screen">
  
  <div class="min-h-screen flex flex-col">
    <!-- Unified Dark Portal Header -->
    <header class="bg-foundation-grey text-white pt-20 md:pt-24 pb-8 md:pb-12 px-4 sm:px-6 lg:px-8 shadow-lg mb-8 md:mb-12 border-b-2 border-rajkot-rust">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <h1 class="text-3xl md:text-4xl font-serif font-bold">User Management</h1>
                <p class="text-gray-400 mt-2 text-sm uppercase tracking-widest font-bold opacity-70">Identity & Authorization Registry</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
                <a href="provision_temp_user.php?demo=1" class="w-full md:w-auto bg-rajkot-rust hover:bg-red-700 text-white px-8 py-4 text-[10px] font-bold uppercase tracking-[0.2em] shadow-premium transition-all flex items-center justify-center gap-3 active:scale-95 no-underline">
                    <i data-lucide="user-plus" class="w-4 h-4"></i> Provision Identity
                </a>
            </div>
        </div>
    </header>

    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-8 md:mb-12">
            <div class="bg-white p-6 md:p-8 shadow-premium border border-gray-100 relative group overflow-hidden">
                <div class="absolute top-0 right-0 w-16 h-16 bg-gray-50 -mr-8 -mt-8 rotate-45 pointer-events-none"></div>
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Total Registry</span>
                <span class="text-2xl md:text-3xl font-serif font-bold text-foundation-grey"><?php echo (int)$roleCounts['total']; ?></span>
            </div>
            <div class="bg-white p-6 md:p-8 shadow-premium border border-gray-100 border-b-2 border-b-rajkot-rust relative group overflow-hidden">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Active Clients</span>
                <span class="text-2xl md:text-3xl font-serif font-bold text-rajkot-rust"><?php echo (int)$roleCounts['client']; ?></span>
            </div>
            <div class="bg-white p-6 md:p-8 shadow-premium border border-gray-100 border-b-2 border-b-approval-green relative group overflow-hidden">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Field Workers</span>
                <span class="text-2xl md:text-3xl font-serif font-bold text-approval-green"><?php echo (int)$roleCounts['worker']; ?></span>
            </div>
            <div class="bg-white p-6 md:p-8 shadow-premium border border-gray-100 border-b-2 border-b-slate-accent relative group overflow-hidden">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">System Staff</span>
                <span class="text-2xl md:text-3xl font-serif font-bold text-slate-accent"><?php echo (int)$roleCounts['employee'] + (int)$roleCounts['admin']; ?></span>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="bg-white shadow-premium border border-gray-100 p-4 md:p-6 mb-8 flex flex-col lg:flex-row justify-between items-center gap-4 md:gap-6">
            <div class="relative w-full lg:w-96">
                <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 w-4 h-4"></i>
                <form action="" method="get" onsubmit="return false;">
                    <input id="identityFilterInput" type="search" name="search" value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Filter identities..." class="w-full pl-12 pr-6 py-3 md:py-4 bg-gray-50 border border-gray-50 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm font-medium">
                </form>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
                <select id="permissionFilterSelect" class="w-full sm:w-auto py-3 md:py-4 px-6 bg-gray-50 border border-gray-50 text-[10px] font-bold uppercase tracking-widest outline-none focus:bg-white focus:border-rajkot-rust transition-all cursor-pointer">
                    <option value="all" <?php echo $role === 'all' ? 'selected' : ''; ?>>All Permissions</option>
                    <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>Administrators</option>
                    <option value="employee" <?php echo $role === 'employee' ? 'selected' : ''; ?>>Employees</option>
                    <option value="worker" <?php echo $role === 'worker' ? 'selected' : ''; ?>>Field Tech</option>
                    <option value="client" <?php echo $role === 'client' ? 'selected' : ''; ?>>Govt Client</option>
                </select>
                <button id="applyIdentityFiltersBtn" type="button" class="bg-foundation-grey hover:bg-rajkot-rust text-white px-6 py-3 md:py-4 text-[10px] font-bold uppercase tracking-[0.2em] transition-all flex items-center justify-center shadow-lg active:scale-95">
                    <i data-lucide="filter" class="w-3.5 h-3.5 mr-2"></i> Apply
                </button>
            </div>
        </div>

        <?php if ($statusMsg === 'updated'): ?>
            <div class="mb-6 bg-approval-green/10 border border-approval-green/30 text-approval-green px-4 py-3 text-xs font-bold uppercase tracking-wider">
                User status updated successfully.
            </div>
        <?php elseif ($statusMsg === 'error'): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-xs font-bold uppercase tracking-wider">
                Unable to update user status.
            </div>
        <?php endif; ?>

        <!-- User Table -->
        <div class="bg-white shadow-premium border border-gray-100 overflow-hidden relative registry-table-shell">
            <!-- CAD-style grid line -->
            <div class="absolute top-0 left-0 w-full h-[1px] bg-gradient-to-r from-transparent via-rajkot-rust/20 to-transparent"></div>
            
            <div class="overflow-x-auto registry-table-scroll">
                <table class="w-full text-left text-sm border-collapse admin-table registry-table-fixed">
                    <colgroup>
                        <col style="width: 30%;">
                        <col style="width: 14%;">
                        <col style="width: 14%;">
                        <col style="width: 14%;">
                        <col style="width: 14%;">
                        <col style="width: 14%;">
                    </colgroup>
                    <thead class="hidden md:table-header-group">
                        <tr class="bg-gray-50/80 text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] border-b border-gray-100">
                            <th class="px-8 py-6 font-bold">Identity Profile</th>
                            <th class="px-8 py-6 font-bold">Authorization Level</th>
                            <th class="px-8 py-6 font-bold">Rating</th>
                            <th class="px-8 py-6 font-bold">Signal</th>
                            <th class="px-8 py-6 font-bold">Last Sync</th>
                            <th class="px-8 py-6 font-bold text-right">Registry Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($users as $i => $u): ?>
                        <?php
                          $displayName = $u['full_name'] ?: $u['username'];
                          $status = strtolower((string)($u['status'] ?? 'active'));
                          $statusClass = $status === 'active' ? 'text-approval-green' : ($status === 'pending' ? 'text-pending-amber' : 'text-red-600');
                          $initials = strtoupper(substr((string)$displayName, 0, 1));
                        ?>
                        <tr class="group hover:bg-gray-50/50 transition-all duration-300 block md:table-row mb-4 md:mb-0 border md:border-0 rounded-lg md:rounded-none bg-white md:bg-transparent user-row<?php echo $i >= 10 ? ' hidden extra-user-row' : ''; ?>"
                            data-name="<?php echo htmlspecialchars((string)$displayName); ?>"
                            data-email="<?php echo htmlspecialchars((string)($u['email'] ?? '')); ?>"
                            data-role="<?php echo htmlspecialchars(strtolower((string)$u['role'])); ?>">
                            <td class="px-6 md:px-8 py-4 md:py-6 block md:table-cell" data-label="Identity Profile">
                                <div class="flex items-center gap-4">
                                    <div class="w-11 h-11 bg-foundation-grey text-white font-serif font-bold flex items-center justify-center border-b-2 border-rajkot-rust shadow-sm"><?php echo htmlspecialchars($initials); ?></div>
                                    <div>
                                        <p class="font-bold text-foundation-grey group-hover:text-rajkot-rust transition-colors mb-0.5"><?php echo htmlspecialchars($displayName); ?></p>
                                        <p class="text-[10px] text-gray-400 font-mono tracking-tighter uppercase opacity-70 text-wrap break-all"><?php echo htmlspecialchars((string)($u['email'] ?? '')); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 md:px-8 py-4 md:py-6 block md:table-cell" data-label="Authorization Level">
                                <span class="px-3 py-1 bg-foundation-grey/5 text-foundation-grey text-[9px] font-bold uppercase tracking-[0.15em] border border-foundation-grey/10"><?php echo htmlspecialchars(strtoupper((string)$u['role'])); ?></span>
                            </td>
                            <td class="px-6 md:px-8 py-4 md:py-6 block md:table-cell" data-label="Rating">
                                <?php $finalScore = floatval($u['final_score'] ?? 0.0); ?>
                                <?php $decisionScore = floatval($u['decision_score'] ?? 0.0); ?>
                                <?php $totalEvents = (int)($u['total_ratings'] ?? 0); ?>
                                <?php if ($totalEvents > 0): ?>
                                    <span class="text-[11px] font-bold text-approval-green"><?php echo htmlspecialchars(round($decisionScore * 100, 1)); ?>%</span>
                                    <p class="text-[10px] text-gray-400 mt-1"><?php echo $totalEvents; ?> entries • Final <?php echo round($finalScore * 100,1); ?>%</p>
                                <?php else: ?>
                                    <span class="text-[11px] font-bold text-gray-400">No ratings</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 md:px-8 py-4 md:py-6 block md:table-cell" data-label="Signal">
                                <span class="flex items-center gap-2 <?php echo htmlspecialchars($statusClass); ?> text-[9px] font-bold uppercase tracking-widest">
                                    <span class="w-2 h-2 rounded-full bg-current"></span> <?php echo htmlspecialchars($status); ?>
                                </span>
                            </td>
                            <td class="px-6 md:px-8 py-4 md:py-6 text-gray-400 text-[11px] font-medium italic block md:table-cell" data-label="Last Sync"><?php echo htmlspecialchars((string)($u['last_sync'] ?? '')); ?></td>
                            <td class="px-6 md:px-8 py-6 md:py-6 block md:table-cell" data-label="Registry Actions">
                                <div class="flex flex-row md:justify-end items-center gap-3 mt-4 md:mt-0 flex-wrap">
                                    <a href="../dashboard/profile.php?user=<?php echo urlencode((string)$u['username']); ?>" class="h-11 w-11 shrink-0 bg-gray-50 md:bg-transparent text-gray-400 hover:text-rajkot-rust transition-colors flex items-center justify-center border border-gray-100 md:border-0 rounded" title="View Profile">
                                        <i data-lucide="eye" class="w-5 h-5 md:w-4 md:h-4"></i>
                                    </a>
                                    <a href="add_user.php?id=<?php echo (int)$u['id']; ?>" class="h-11 w-11 shrink-0 bg-gray-50 md:bg-transparent text-gray-400 hover:text-foundation-grey transition-colors flex items-center justify-center border border-gray-100 md:border-0 rounded" title="Edit Permissions">
                                        <i data-lucide="settings-2" class="w-5 h-5 md:w-4 md:h-4"></i>
                                    </a>
                                    <form method="post" class="shrink-0">
                                        <?php echo csrf_token_field(); ?>
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="user_id" value="<?php echo (int)$u['id']; ?>">
                                        <input type="hidden" name="new_status" value="<?php echo $status === 'active' ? 'suspended' : 'active'; ?>">
                                        <button type="submit" class="h-11 px-3 bg-gray-50 md:bg-transparent text-[9px] font-bold uppercase tracking-widest <?php echo htmlspecialchars($status === 'active' ? 'text-red-600 hover:text-red-700' : 'text-approval-green hover:text-approval-green/80'); ?> transition-colors flex items-center justify-center border border-gray-100 md:border-0 rounded" title="<?php echo $status === 'active' ? 'Deactivate User' : 'Activate User'; ?>">
                                            <?php echo $status === 'active' ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination / Load More -->
            <div class="p-6 md:p-10 text-center border-t border-gray-50 bg-gray-50/30">
                <button id="loadMoreUsersBtn" type="button" class="text-[10px] font-bold uppercase tracking-[0.3em] text-gray-300 hover:text-rajkot-rust transition-all border-b border-transparent hover:border-rajkot-rust pb-1 px-4">Load More Users</button>
            </div>
        </div>
    </main>

    <?php require_once PROJECT_ROOT . '/Common/footer.php'; ?>
  </div>

  <script>
    document.getElementById('applyIdentityFiltersBtn').addEventListener('click', function () {
        const roleValue = (document.getElementById('permissionFilterSelect').value || 'all').trim().toLowerCase();
        const url = new URL(window.location.href);
        if (roleValue && roleValue !== 'all') {
            url.searchParams.set('role', roleValue);
        } else {
            url.searchParams.delete('role');
        }
        window.location.href = url.toString();
    });

    // Reference-style search: debounce and redirect with query params.
    (function initSearchRedirect() {
        const searchInput = document.getElementById('identityFilterInput');
        if (!searchInput) return;

        let refreshTimer;
        function focusAtEnd() {
            searchInput.focus();
            const val = searchInput.value || '';
            if (searchInput.setSelectionRange) {
                searchInput.setSelectionRange(val.length, val.length);
            }
        }

        focusAtEnd();
        // Retry once for browsers that delay paint/focus when the page just reloaded.
        setTimeout(focusAtEnd, 60);

        searchInput.addEventListener('input', function () {
            clearTimeout(refreshTimer);
            const searchValue = (this.value || '').trim();
            refreshTimer = setTimeout(function () {
                const url = new URL(window.location.href);
                if (searchValue) {
                    url.searchParams.set('search', searchValue);
                } else {
                    url.searchParams.delete('search');
                }
                window.location.href = url.toString();
            }, 500);
        });
    })();

    document.getElementById('loadMoreUsersBtn').addEventListener('click', function () {
        document.querySelectorAll('.extra-user-row.hidden').forEach((row) => row.classList.remove('hidden'));
        this.classList.remove('text-gray-300');
        this.classList.add('text-approval-green');
        this.textContent = 'Full Registry Loaded';
    });

    function confirmDelete(userName) {
        if (confirm('Are you sure you want to remove ' + userName + ' from the registry? This action cannot be revoked.')) {
            // Simulated delete action
            const notification = document.createElement('div');
            notification.className = 'fixed top-24 right-8 bg-foundation-grey text-white px-8 py-4 shadow-2xl border-l-4 border-rajkot-rust z-50 animate-bounce';
            notification.innerHTML = '<p class="text-[10px] font-bold uppercase tracking-widest mb-1">Registry Synchronization</p><p class="text-sm">Identity for <b>' + userName + '</b> has been decommissioned.</p>';
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.remove('animate-bounce');
                notification.classList.add('opacity-0', 'transition-opacity', 'duration-1000');
                setTimeout(() => notification.remove(), 1000);
            }, 3000);
        }
    }

        // Server-side role filtering is applied on page load via URL params.
  </script>

</body>
</html>