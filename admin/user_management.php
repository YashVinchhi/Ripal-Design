<?php
// User Management (Redesigned UI)
session_start();
require_once __DIR__ . '/../includes/init.php';
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
    <header class="bg-foundation-grey text-white pt-24 pb-12 px-4 sm:px-6 lg:px-8 shadow-lg mb-12 border-b-2 border-rajkot-rust">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <h1 class="text-4xl font-serif font-bold">User Management</h1>
                <p class="text-gray-400 mt-2 text-sm uppercase tracking-widest font-bold opacity-70">Identity & Authorization Registry</p>
            </div>
            <div class="flex gap-3">
                <a href="add_user.php" class="bg-rajkot-rust hover:bg-red-700 text-white px-8 py-4 text-[10px] font-bold uppercase tracking-[0.2em] shadow-premium transition-all flex items-center gap-3 active:scale-95 no-underline">
                    <i data-lucide="user-plus" class="w-4 h-4"></i> Provision Identity
                </a>
            </div>
        </div>
    </header>

    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">
            <div class="bg-white p-8 shadow-premium border border-gray-100 relative group overflow-hidden">
                <div class="absolute top-0 right-0 w-16 h-16 bg-gray-50 -mr-8 -mt-8 rotate-45 pointer-events-none"></div>
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Total Registry</span>
                <span class="text-3xl font-serif font-bold text-foundation-grey">124</span>
            </div>
            <div class="bg-white p-8 shadow-premium border border-gray-100 border-b-2 border-b-rajkot-rust relative group overflow-hidden">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Active Clients</span>
                <span class="text-3xl font-serif font-bold text-rajkot-rust">42</span>
            </div>
            <div class="bg-white p-8 shadow-premium border border-gray-100 border-b-2 border-b-approval-green relative group overflow-hidden">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Field Workers</span>
                <span class="text-3xl font-serif font-bold text-approval-green">68</span>
            </div>
            <div class="bg-white p-8 shadow-premium border border-gray-100 border-b-2 border-b-slate-accent relative group overflow-hidden">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">System Staff</span>
                <span class="text-3xl font-serif font-bold text-slate-accent">14</span>
            </div>
        </div>

        <!-- Toolbar -->
        <div class="bg-white shadow-premium border border-gray-100 p-6 mb-8 flex flex-col lg:flex-row justify-between items-center gap-6">
            <div class="relative w-full lg:w-96">
                <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-300 w-4 h-4"></i>
                <input type="search" placeholder="Filter identities..." class="w-full pl-12 pr-6 py-4 bg-gray-50 border border-gray-50 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm font-medium">
            </div>
            <div class="flex gap-3 w-full lg:w-auto">
                <select class="flex-1 lg:flex-none py-4 px-6 bg-gray-50 border border-gray-50 text-[10px] font-bold uppercase tracking-widest outline-none focus:bg-white focus:border-rajkot-rust transition-all cursor-pointer">
                    <option>All Permissions</option>
                    <option>Administrators</option>
                    <option>Design Lead</option>
                    <option>Field Tech</option>
                    <option>Govt Client</option>
                </select>
                <button class="bg-foundation-grey hover:bg-rajkot-rust text-white px-6 py-4 text-[10px] font-bold uppercase tracking-[0.2em] transition-all flex items-center shadow-lg active:scale-95">
                    <i data-lucide="filter" class="w-3.5 h-3.5 mr-2"></i> Apply
                </button>
            </div>
        </div>

        <!-- User Table -->
        <div class="bg-white shadow-premium border border-gray-100 overflow-hidden relative">
            <!-- CAD-style grid line -->
            <div class="absolute top-0 left-0 w-full h-[1px] bg-gradient-to-r from-transparent via-rajkot-rust/20 to-transparent"></div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead>
                        <tr class="bg-gray-50/80 text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] border-b border-gray-100">
                            <th class="px-8 py-6 font-bold">Identity Profile</th>
                            <th class="px-8 py-6 font-bold">Authorization Level</th>
                            <th class="px-8 py-6 font-bold">Signal</th>
                            <th class="px-8 py-6 font-bold">Last Sync</th>
                            <th class="px-8 py-6 font-bold text-right">Registry Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <!-- Row 1 -->
                        <tr class="group hover:bg-gray-50/50 transition-all duration-300">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-11 h-11 bg-foundation-grey text-white font-serif font-bold flex items-center justify-center border-b-2 border-rajkot-rust shadow-sm">AV</div>
                                    <div>
                                        <p class="font-bold text-foundation-grey group-hover:text-rajkot-rust transition-colors mb-0.5">Ashish Vinchhi</p>
                                        <p class="text-[10px] text-gray-400 font-mono tracking-tighter uppercase opacity-70">admin@ripaldesign.in</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <span class="px-3 py-1 bg-foundation-grey/5 text-foundation-grey text-[9px] font-bold uppercase tracking-[0.15em] border border-foundation-grey/10">Principal Owner</span>
                            </td>
                            <td class="px-8 py-6">
                                <span class="flex items-center gap-2 text-approval-green text-[9px] font-bold uppercase tracking-widest">
                                    <span class="w-2 h-2 bg-approval-green rounded-full shadow-[0_0_8px_rgba(21,128,61,0.5)] animate-pulse"></span> Synchronized
                                </span>
                            </td>
                            <td class="px-8 py-6 text-gray-400 text-[11px] font-medium italic">Active Now</td>
                            <td class="px-8 py-6">
                                <div class="flex justify-end gap-4">
                                    <a href="../dashboard/profile.php?user=ashish" class="text-gray-300 hover:text-rajkot-rust transition-colors" title="View Profile"><i data-lucide="eye" class="w-4 h-4"></i></a>
                                    <a href="add_user.php?id=1" class="text-gray-300 hover:text-foundation-grey transition-colors" title="Edit Permissions"><i data-lucide="settings-2" class="w-4 h-4"></i></a>
                                    <button class="text-gray-300 hover:text-red-600 transition-colors" onclick="confirmDelete('Ashish Vinchhi')"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                </div>
                            </td>
                        </tr>
                        <!-- Row 2 -->
                        <tr class="group hover:bg-gray-50/50 transition-all duration-300">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-11 h-11 bg-rajkot-rust/10 text-rajkot-rust font-serif font-bold flex items-center justify-center border-b-2 border-rajkot-rust shadow-sm uppercase">RP</div>
                                    <div>
                                        <p class="font-bold text-foundation-grey group-hover:text-rajkot-rust transition-colors mb-0.5">Ripal Patel</p>
                                        <p class="text-[10px] text-gray-400 font-mono tracking-tighter uppercase opacity-70">ripal@ripaldesign.in</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <span class="px-3 py-1 bg-foundation-grey/5 text-foundation-grey text-[9px] font-bold uppercase tracking-[0.15em] border border-foundation-grey/10">Chief Architect</span>
                            </td>
                            <td class="px-8 py-6">
                                <span class="flex items-center gap-2 text-approval-green text-[9px] font-bold uppercase tracking-widest opacity-60">
                                    <span class="w-2 h-2 bg-approval-green rounded-full"></span> Online
                                </span>
                            </td>
                            <td class="px-8 py-6 text-gray-400 text-[11px] font-medium italic">12 min ago</td>
                            <td class="px-8 py-6">
                                <div class="flex justify-end gap-4">
                                    <a href="../dashboard/profile.php?user=ripal" class="text-gray-300 hover:text-rajkot-rust transition-colors" title="View Profile"><i data-lucide="eye" class="w-4 h-4"></i></a>
                                    <a href="add_user.php?id=2" class="text-gray-300 hover:text-foundation-grey transition-colors" title="Edit Permissions"><i data-lucide="settings-2" class="w-4 h-4"></i></a>
                                    <button class="text-gray-300 hover:text-red-600 transition-colors" onclick="confirmDelete('Ripal Patel')"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                </div>
                            </td>
                        </tr>
                        <!-- Row 3 -->
                        <tr class="group hover:bg-gray-50/50 transition-all duration-300">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-11 h-11 bg-slate-accent/10 text-slate-accent font-serif font-bold flex items-center justify-center border-b-2 border-slate-accent shadow-sm uppercase">SA</div>
                                    <div>
                                        <p class="font-bold text-foundation-grey group-hover:text-rajkot-rust transition-colors mb-0.5">Sanjaybhai Ahir</p>
                                        <p class="text-[10px] text-gray-400 font-mono tracking-tighter uppercase opacity-70">sanjay.ahir@field.ripal.in</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <span class="px-3 py-1 bg-foundation-grey/5 text-foundation-grey text-[9px] font-bold uppercase tracking-[0.15em] border border-foundation-grey/10">Field Engineer</span>
                            </td>
                            <td class="px-8 py-6">
                                <span class="flex items-center gap-2 text-pending-amber text-[9px] font-bold uppercase tracking-widest">
                                    <span class="w-2 h-2 bg-pending-amber rounded-full"></span> Away
                                </span>
                            </td>
                            <td class="px-8 py-6 text-gray-400 text-[11px] font-medium italic">4 hours ago</td>
                            <td class="px-8 py-6">
                                <div class="flex justify-end gap-4">
                                    <a href="../dashboard/profile.php?user=sanjay" class="text-gray-300 hover:text-rajkot-rust transition-colors" title="View Profile"><i data-lucide="eye" class="w-4 h-4"></i></a>
                                    <a href="add_user.php?id=3" class="text-gray-300 hover:text-foundation-grey transition-colors" title="Edit Permissions"><i data-lucide="settings-2" class="w-4 h-4"></i></a>
                                    <button class="text-gray-300 hover:text-red-600 transition-colors" onclick="confirmDelete('Sanjaybhai Ahir')"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination / Load More -->
            <div class="p-10 text-center border-t border-gray-50 bg-gray-50/30">
                <button onclick="this.innerText='Full Registry Loaded'; this.classList.remove('text-gray-300'); this.classList.add('text-approval-green');" class="text-[10px] font-bold uppercase tracking-[0.3em] text-gray-300 hover:text-rajkot-rust transition-all border-b border-transparent hover:border-rajkot-rust pb-1 px-4">Initialize Full Registry Scroll</button>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/../Common/footer.php'; ?>
  </div>

  <script>
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