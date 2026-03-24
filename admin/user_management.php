<?php
// User Management (Redesigned UI)
session_start();
require_once __DIR__ . '/../includes/init.php';

$roleCounts = get_user_role_counts();
$users = db_fetch_all('SELECT id, username, full_name, email, role, status, COALESCE(updated_at, created_at) AS last_sync FROM users ORDER BY id DESC LIMIT 200');
?>
<!DOCTYPE html>
<html lang="en" class="bg-canvas-white">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>User Management | Ripal Design</title>
  <?php $HEADER_MODE = 'dashboard'; require_once __DIR__ . '/../Common/header.php'; ?>
</head>
<body class="bg-canvas-white font-sans text-foundation-grey min-h-screen">
  
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
                <input id="identityFilterInput" type="search" placeholder="Filter identities..." class="w-full pl-12 pr-6 py-3 md:py-4 bg-gray-50 border border-gray-50 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm font-medium">
            </div>
            <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">
                <select id="permissionFilterSelect" class="w-full sm:w-auto py-3 md:py-4 px-6 bg-gray-50 border border-gray-50 text-[10px] font-bold uppercase tracking-widest outline-none focus:bg-white focus:border-rajkot-rust transition-all cursor-pointer">
                    <option value="all">All Permissions</option>
                    <option value="admin">Administrators</option>
                    <option value="employee">Employees</option>
                    <option value="worker">Field Tech</option>
                    <option value="client">Govt Client</option>
                </select>
                <button id="applyIdentityFiltersBtn" type="button" class="bg-foundation-grey hover:bg-rajkot-rust text-white px-6 py-3 md:py-4 text-[10px] font-bold uppercase tracking-[0.2em] transition-all flex items-center justify-center shadow-lg active:scale-95">
                    <i data-lucide="filter" class="w-3.5 h-3.5 mr-2"></i> Apply
                </button>
            </div>
        </div>

        <!-- User Table -->
        <div class="bg-white shadow-premium border border-gray-100 overflow-hidden relative">
            <!-- CAD-style grid line -->
            <div class="absolute top-0 left-0 w-full h-[1px] bg-gradient-to-r from-transparent via-rajkot-rust/20 to-transparent"></div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse admin-table">
                    <thead class="hidden md:table-header-group">
                        <tr class="bg-gray-50/80 text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] border-b border-gray-100">
                            <th class="px-8 py-6 font-bold">Identity Profile</th>
                            <th class="px-8 py-6 font-bold">Authorization Level</th>
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
                            <td class="px-6 md:px-8 py-4 md:py-6 block md:table-cell" data-label="Signal">
                                <span class="flex items-center gap-2 <?php echo $statusClass; ?> text-[9px] font-bold uppercase tracking-widest">
                                    <span class="w-2 h-2 rounded-full bg-current"></span> <?php echo htmlspecialchars($status); ?>
                                </span>
                            </td>
                            <td class="px-6 md:px-8 py-4 md:py-6 text-gray-400 text-[11px] font-medium italic block md:table-cell" data-label="Last Sync"><?php echo htmlspecialchars((string)($u['last_sync'] ?? '')); ?></td>
                            <td class="px-6 md:px-8 py-6 md:py-6 block md:table-cell" data-label="Registry Actions">
                                <div class="flex flex-row md:justify-end gap-3 mt-4 md:mt-0">
                                    <a href="../dashboard/profile.php?user=<?php echo urlencode((string)$u['username']); ?>" class="flex-grow md:flex-grow-0 h-11 w-11 bg-gray-50 md:bg-transparent text-gray-400 hover:text-rajkot-rust transition-colors flex items-center justify-center border border-gray-100 md:border-0 rounded" title="View Profile">
                                        <i data-lucide="eye" class="w-5 h-5 md:w-4 md:h-4"></i>
                                    </a>
                                    <a href="add_user.php?id=<?php echo (int)$u['id']; ?>" class="flex-grow md:flex-grow-0 h-11 w-11 bg-gray-50 md:bg-transparent text-gray-400 hover:text-foundation-grey transition-colors flex items-center justify-center border border-gray-100 md:border-0 rounded" title="Edit Permissions">
                                        <i data-lucide="settings-2" class="w-5 h-5 md:w-4 md:h-4"></i>
                                    </a>
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

    <?php require_once __DIR__ . '/../Common/footer.php'; ?>
  </div>

  <script>
    const applyIdentityFilters = () => {
        const query = (document.getElementById('identityFilterInput').value || '').trim().toLowerCase();
        const role = document.getElementById('permissionFilterSelect').value;
        document.querySelectorAll('.user-row').forEach((row) => {
            const haystack = ((row.dataset.name || '') + ' ' + (row.dataset.email || '')).toLowerCase();
            const rowRole = (row.dataset.role || '').toLowerCase();
            const matchesQuery = query === '' || haystack.includes(query);
            const matchesRole = role === 'all' || rowRole === role;
            row.classList.toggle('hidden', !(matchesQuery && matchesRole));
        });
    };

    document.getElementById('applyIdentityFiltersBtn').addEventListener('click', applyIdentityFilters);
    document.getElementById('identityFilterInput').addEventListener('input', applyIdentityFilters);

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
  </script>

</body>
</html>