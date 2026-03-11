<?php
// Client Files (Redesigned UI)
session_start();
require_once __DIR__ . '/../includes/init.php';
$projectId = $_GET['project_id'] ?? 'PRJ-2024-001';

// Sample drawing data
$drawings = [
    [
        'id' => 1,
        'title' => 'Site Layout Plan - GF',
        'file' => 'Blueprint_SLP_01.pdf',
        'size' => '4.2 MB',
        'version' => 'v1.8',
        'date' => 'Feb 14, 2026',
        'status' => 'approved'
    ],
    [
        'id' => 2,
        'title' => 'Electrical Conduit Plan',
        'file' => 'Blueprint_ELE_04.pdf',
        'size' => '2.8 MB',
        'version' => 'v2.1',
        'date' => 'Just Now',
        'status' => 'pending'
    ],
    [
        'id' => 3,
        'title' => 'Plumbing & Drainage Layout',
        'file' => 'Blueprint_PLM_02.pdf',
        'size' => '3.5 MB',
        'version' => 'v1.4',
        'date' => 'Feb 10, 2026',
        'status' => 'approved'
    ]
];
?>
<!DOCTYPE html>
<html lang="en" class="bg-canvas-white">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Design Studio | Ripal Design</title>
  <?php $HEADER_MODE = 'dashboard'; require_once __DIR__ . '/../Common/header.php'; ?>
</head>
<body class="bg-canvas-white font-sans text-foundation-grey min-h-screen">
  
  <div class="min-h-screen flex flex-col">
    <!-- Unified Dark Portal Header -->
    <header class="bg-foundation-grey text-white pt-24 pb-12 px-4 shadow-lg border-b-2 border-rajkot-rust">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-end md:justify-between gap-6">
            <div>
                <div class="flex items-center gap-2 text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] mb-4">
                    <a href="../dashboard/dashboard.php" class="hover:text-rajkot-rust transition-colors flex items-center gap-2 no-underline">
                        <i data-lucide="layout-grid" class="w-3.5 h-3.5"></i> Repository
                    </a>
                    <i data-lucide="slash" class="w-3 h-3 text-gray-600 rotate-[15deg]"></i>
                    <span class="text-rajkot-rust">Design Studio</span>
                </div>
                <h1 class="text-4xl font-serif font-bold text-white leading-tight">Design Studio</h1>
                <p class="text-gray-400 mt-2 text-sm uppercase tracking-widest font-bold opacity-70">Transparency Engine • <strong class="text-rajkot-rust font-mono"><?php echo htmlspecialchars($projectId); ?></strong></p>
            </div>
            <div class="flex gap-3">
                <button class="bg-rajkot-rust hover:bg-red-700 text-white px-8 py-4 text-[10px] font-bold uppercase tracking-[0.2em] shadow-premium transition-all flex items-center gap-3 active:scale-95">
                    <i data-lucide="download-cloud" class="w-4 h-4"></i> Export manifest
                </button>
            </div>
        </div>
    </header>

    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">

        <!-- Transparency Engine: Progress Bar -->
        <div class="bg-white p-10 mb-12 shadow-premium border border-gray-100 relative group overflow-hidden">
            <!-- Background CAD line -->
            <div class="absolute bottom-0 right-0 w-1/2 h-[1px] bg-gradient-to-l from-rajkot-rust/20 to-transparent"></div>
            
            <div class="flex justify-between items-end mb-8 relative">
                <div>
                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.3em] mb-2 font-mono">Architecture Lifecycle Phase</p>
                    <h2 class="text-2xl font-serif font-bold text-foundation-grey flex items-center gap-4">
                        Detailed Engineering & IFC 
                        <span class="text-[9px] bg-approval-green/5 text-approval-green px-3 py-1 border border-approval-green/10 uppercase tracking-[0.2em] font-sans font-bold">In Progress</span>
                    </h2>
                </div>
                <div class="text-right">
                    <span class="text-5xl font-black text-gray-50 absolute -mt-10 right-0 select-none font-serif opacity-50">03</span>
                    <span class="text-3xl font-black text-rajkot-rust font-sans relative flex items-baseline gap-1">75<span class="text-xs font-bold uppercase tracking-widest text-gray-300">%</span></span>
                </div>
            </div>
            <div class="relative pt-2">
                <div class="overflow-hidden h-2 mb-6 text-xs flex rounded-full bg-gray-50 border border-gray-100">
                    <div style="width:75%" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-rajkot-rust transition-all duration-1000 relative">
                        <div class="absolute top-0 right-0 w-2 h-2 bg-white rounded-full border-2 border-rajkot-rust -mr-1"></div>
                    </div>
                </div>
                <div class="flex justify-between text-[9px] font-bold text-gray-300 uppercase tracking-[0.2em] font-mono">
                    <span class="flex items-center gap-2">01 <span class="hidden sm:inline">Concept</span></span>
                    <span class="flex items-center gap-2">02 <span class="hidden sm:inline">Schematic</span></span>
                    <span class="flex items-center gap-2 text-rajkot-rust bg-rajkot-rust/5 px-3 py-1 -mt-1 border-b border-rajkot-rust">03 <span class="hidden sm:inline">Detailed Design</span></span>
                    <span class="flex items-center gap-2">04 <span class="hidden sm:inline">Construction</span></span>
                    <span class="flex items-center gap-2">05 <span class="hidden sm:inline">Delivery</span></span>
                </div>
            </div>
        </div>

        <!-- Drawings Table -->
        <div class="bg-white shadow-premium border border-gray-100 overflow-hidden">
            <div class="px-10 py-8 border-b border-gray-50 flex items-center justify-between bg-gray-50/50">
                <h3 class="text-[10px] font-bold uppercase tracking-[0.4em] text-foundation-grey flex items-center gap-3">
                    <i data-lucide="layers" class="w-4 h-4 text-rajkot-rust"></i> Revision Registry
                </h3>
                <div class="flex bg-white shadow-sm ring-1 ring-gray-100 p-1">
                    <button class="p-2 text-rajkot-rust bg-gray-50"><i data-lucide="rows" class="w-4 h-4"></i></button>
                    <button class="p-2 text-gray-300 hover:text-gray-500 transition-colors"><i data-lucide="layout-grid" class="w-4 h-4"></i></button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead>
                        <tr class="bg-gray-50/20 text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] border-b border-gray-100">
                            <th class="px-10 py-6 font-bold">Document Metadata</th>
                            <th class="px-8 py-6 font-bold">Iteration</th>
                            <th class="px-8 py-6 font-bold">Timestamp</th>
                            <th class="px-8 py-6 font-bold">Registry Status</th>
                            <th class="px-10 py-6 font-bold text-right">Commit Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php foreach($drawings as $d): ?>
                        <tr class="group hover:bg-gray-50/30 transition-all duration-300">
                            <td class="px-10 py-8">
                                <div class="flex items-center gap-5">
                                    <div class="w-12 h-16 bg-foundation-grey flex flex-col items-center justify-center text-white shrink-0 relative overflow-hidden group-hover:bg-rajkot-rust transition-colors shadow-premium border-b-2 border-rajkot-rust/20 group-hover:border-white/20">
                                        <i data-lucide="file-text" class="w-6 h-6 mb-1 opacity-80"></i>
                                        <span class="text-[7px] font-bold uppercase tracking-tighter">CAD DATA</span>
                                        <div class="absolute bottom-0 left-0 w-full h-1 bg-white/10"></div>
                                    </div>
                                    <div>
                                        <p class="font-serif font-bold text-lg text-foundation-grey group-hover:text-rajkot-rust transition-colors mb-1"><?php echo htmlspecialchars($d['title']); ?></p>
                                        <div class="flex items-center gap-3">
                                            <p class="text-[10px] text-gray-400 font-mono tracking-tighter uppercase opacity-70"><?php echo $d['file']; ?></p>
                                            <span class="text-[8px] bg-gray-50 text-gray-400 px-2 py-0.5 font-bold border border-gray-100"><?php echo $d['size']; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-8">
                                <span class="text-xs font-mono font-bold text-foundation-grey bg-gray-50 px-2 py-1"><?php echo $d['version']; ?></span>
                            </td>
                            <td class="px-8 py-8">
                                <span class="text-[11px] font-medium text-gray-400 italic"><?php echo $d['date']; ?></span>
                            </td>
                            <td class="px-8 py-8">
                                <?php if($d['status'] === 'approved'): ?>
                                    <span class="inline-flex items-center gap-2 text-approval-green text-[9px] font-bold uppercase tracking-[0.2em]">
                                        <i data-lucide="check" class="w-3.5 h-3.5"></i> Synchronized
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-2 text-pending-amber text-[9px] font-bold uppercase tracking-[0.2em] animate-pulse">
                                        <i data-lucide="eye" class="w-3.5 h-3.5"></i> Under Review
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-10 py-8 text-right">
                                <?php if($d['status'] === 'pending'): ?>
                                    <div class="flex justify-end gap-3">
                                        <button onclick="handleClientAction('authorize', '<?php echo addslashes($d['title']); ?>')" class="bg-approval-green hover:bg-green-700 text-white px-6 py-3 text-[10px] font-bold uppercase tracking-[0.2em] shadow-premium transition-all active:scale-[0.98]">
                                            Authorize
                                        </button>
                                        <button onclick="handleClientAction('redline', '<?php echo addslashes($d['title']); ?>')" class="bg-foundation-grey hover:bg-rajkot-rust text-white px-6 py-3 text-[10px] font-bold uppercase tracking-[0.2em] shadow-premium transition-all active:scale-[0.98]">
                                            Request Redline
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="flex justify-end gap-3">
                                        <button onclick="window.open('../admin/file_viewer.php?file=<?php echo urlencode($d['file']); ?>&project=Client Registry', '_blank')" class="text-gray-300 hover:text-rajkot-rust transition-colors p-2" title="View Document"><i data-lucide="eye" class="w-5 h-5"></i></button>
                                        <!-- <button class="text-gray-300 hover:text-foundation-grey transition-colors p-2"><i data-lucide="history" class="w-5 h-5"></i></button> -->
                                        <button onclick="handleDownload('<?php echo addslashes($d['file']); ?>')" class="text-gray-300 hover:text-blue-600 transition-colors p-2" title="Registry Download"><i data-lucide="download" class="w-5 h-5"></i></button>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Change Order / Advisory Box -->
        <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="md:col-span-2 bg-slate-accent text-white p-8 flex items-center gap-6 shadow-premium relative overflow-hidden group">
                <i data-lucide="info" class="w-16 h-16 text-white/10 absolute -right-4 -top-4 transform rotate-12 group-hover:scale-110 transition-transform"></i>
                <div class="shrink-0 w-16 h-16 bg-white/10 flex items-center justify-center">
                    <i data-lucide="alert-triangle" class="w-8 h-8 text-pending-amber"></i>
                </div>
                <div>
                    <h4 class="text-lg font-serif font-bold mb-1">Standard Transparency Protocol</h4>
                    <p class="text-sm text-gray-300">Approving a drawing digitally locks the record. All subsequent changes will be tracked as separate Change Orders to ensure billing transparency.</p>
                </div>
            </div>
            <div class="bg-white p-8 border border-gray-100 shadow-premium flex flex-col justify-center text-center">
                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] mb-4">Pending Impacts</p>
                <span class="text-4xl font-serif font-bold text-rajkot-rust mb-2">02</span>
                <p class="text-xs text-gray-500">Unresolved Change Orders</p>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/../Common/footer.php'; ?>
  </div>

</body>
</html>