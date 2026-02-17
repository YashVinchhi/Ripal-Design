<?php
// Project Management (Redesigned UI)
session_start();
require_once __DIR__ . '/../includes/init.php';
?>
<!DOCTYPE html>
<html lang="en" class="bg-canvas-white">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Project Management | Ripal Design</title>
  <?php require_once __DIR__ . '/../Common/header.php'; ?>
</head>
<body class="bg-canvas-white font-sans text-foundation-grey min-h-screen">
  
  <div class="min-h-screen flex flex-col">
    <!-- Unified Dark Portal Header -->
    <header class="bg-foundation-grey text-white pt-24 pb-12 px-4 sm:px-6 lg:px-8 shadow-lg mb-12">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <h1 class="text-4xl font-serif font-bold">Project Portfolio</h1>
                <p class="text-gray-400 mt-2">Executive oversight for architectural and infrastructure ventures.</p>
            </div>
            <div class="flex gap-3">
                <button class="bg-white/10 hover:bg-white/20 text-white border border-white/20 px-6 py-3 text-sm font-bold uppercase tracking-widest transition-all flex items-center gap-2">
                    <i data-lucide="download" class="w-4 h-4 text-rajkot-rust"></i> Export Report
                </button>
                <button class="bg-rajkot-rust hover:bg-red-700 text-white px-6 py-3 text-sm font-bold uppercase tracking-widest shadow-lg transition-all flex items-center gap-2 active:scale-95">
                    <i data-lucide="plus" class="w-4 h-4"></i> New Project
                </button>
            </div>
        </div>
    </header>

    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Filter Bar -->
        <div class="flex flex-col lg:flex-row items-center justify-between gap-6 mb-10">
            <div class="flex items-center gap-3">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Region:</span>
                <div class="flex gap-2">
                    <button class="px-4 py-1.5 bg-rajkot-rust text-white text-[10px] font-bold uppercase tracking-widest shadow-sm">Global</button>
                    <button class="px-4 py-1.5 bg-white border border-gray-100 text-gray-500 text-[10px] font-bold uppercase tracking-widest hover:border-rajkot-rust transition-colors">Rajkot</button>
                    <button class="px-4 py-1.5 bg-white border border-gray-100 text-gray-500 text-[10px] font-bold uppercase tracking-widest hover:border-rajkot-rust transition-colors">Jam Khambhalia</button>
                </div>
            </div>
            <div class="flex gap-4 w-full lg:w-auto">
                <div class="relative flex-grow lg:w-64">
                    <i data-lucide="filter" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-300 w-4 h-4"></i>
                    <select class="w-full pl-10 pr-4 py-2 border border-gray-100 bg-gray-50 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-[10px] font-bold uppercase tracking-widest appearance-none">
                        <option>All Statuses</option>
                        <option>Conceptual Design</option>
                        <option>Approval Pending</option>
                        <option>Construction Ongoing</option>
                        <option>Project Handover</option>
                    </select>
                </div>
                <div class="relative flex-grow lg:w-80">
                    <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-300 w-4 h-4"></i>
                    <input type="search" placeholder="Search Master Registry..." class="w-full pl-10 pr-4 py-2 border border-gray-100 bg-gray-50 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm">
                </div>
            </div>
        </div>

        <!-- Project Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Project Card Pattern -->
            <div class="group bg-white border border-gray-100 shadow-premium hover:shadow-premium-hover transition-all duration-500 overflow-hidden flex flex-col">
                <div class="h-56 bg-foundation-grey relative overflow-hidden">
                    <img src="https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&q=80&w=800" alt="Executive Overview" class="w-full h-full object-cover group-hover:scale-110 group-hover:opacity-40 transition duration-700">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent flex flex-col justify-end p-6">
                       <span class="px-3 py-1 bg-approval-green text-white text-[10px] font-bold uppercase tracking-widest mb-2 w-max shadow-lg">Construction Phase</span>
                       <h3 class="text-xl font-serif font-bold text-white group-hover:text-rajkot-rust transition-colors">RMC Smart City Plaza</h3>
                    </div>
                    <div class="absolute top-4 right-4 text-white/50 text-xs font-mono">PRJ-2024-001</div>
                </div>
                <div class="p-6 flex-grow">
                    <div class="flex items-center text-sm text-gray-500 mb-6">
                        <i data-lucide="map-pin" class="w-4 h-4 mr-2 text-rajkot-rust"></i> Rajkot Infrastructure District
                    </div>
                    
                    <div class="mb-8">
                        <div class="flex justify-between items-end mb-2">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Global Progress</span>
                            <span class="text-sm font-bold text-rajkot-rust font-sans">72%</span>
                        </div>
                        <div class="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden">
                            <div class="bg-rajkot-rust h-full" style="width: 72%"></div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between py-4 border-t border-gray-50">
                        <div class="flex -space-x-3">
                            <div class="w-10 h-10 rounded-full bg-rajkot-rust border-4 border-white flex items-center justify-center text-[10px] text-white font-bold shadow-sm" title="Lead Architect">AV</div>
                            <div class="w-10 h-10 rounded-full bg-slate-accent border-4 border-white flex items-center justify-center text-[10px] text-white font-bold shadow-sm">JD</div>
                            <div class="w-10 h-10 rounded-full bg-gray-100 border-4 border-white flex items-center justify-center text-[10px] text-gray-400 font-bold shadow-sm">+3</div>
                        </div>
                        <a href="../dashboard/project_details.php?id=1" class="group/btn inline-flex items-center gap-2 text-[10px] font-bold uppercase tracking-widest text-foundation-grey hover:text-rajkot-rust transition-colors">
                            Manage Portfolio <i data-lucide="arrow-right" class="w-4 h-4 transform group-hover/btn:translate-x-1 transition-transform"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Add Project Card -->
            <div class="border-2 border-dashed border-gray-200 p-8 flex flex-col items-center justify-center text-center group hover:border-rajkot-rust transition-colors cursor-pointer min-h-[400px]">
                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-6 group-hover:bg-rajkot-rust transition-colors">
                    <i data-lucide="plus" class="w-10 h-10 text-gray-300 group-hover:text-white transition-colors"></i>
                </div>
                <h4 class="font-serif font-bold text-xl text-gray-400 group-hover:text-foundation-grey">Initialize Venture</h4>
                <p class="text-sm text-gray-400 mt-2 max-w-[200px]">Start a new architectural record for standard or government infrastructure.</p>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/../Common/footer.php'; ?>
  </div>

</body>
</html>