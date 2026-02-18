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
  <?php $HEADER_MODE = 'dashboard'; require_once __DIR__ . '/../Common/header.php'; ?>
  <!-- jQuery and Validation Plugin -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
  <style>
      .error { color: #94180C; font-size: 10px; font-weight: bold; text-transform: uppercase; margin-top: 4px; display: block; }
      input.error, select.error, textarea.error { border-color: #94180C !important; background-color: #FFF5F5 !important; }
  </style>
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
                <div class="flex gap-2" id="region-filters">
                    <button onclick="filterRegion('Global')" class="px-4 py-1.5 bg-rajkot-rust text-white text-[10px] font-bold uppercase tracking-widest shadow-sm filter-btn active-filter">Global</button>
                    <button onclick="filterRegion('Rajkot')" class="px-4 py-1.5 bg-white border border-gray-100 text-gray-500 text-[10px] font-bold uppercase tracking-widest hover:border-rajkot-rust transition-colors filter-btn">Rajkot</button>
                    <button onclick="filterRegion('Jam Khambhalia')" class="px-4 py-1.5 bg-white border border-gray-100 text-gray-500 text-[10px] font-bold uppercase tracking-widest hover:border-rajkot-rust transition-colors filter-btn">Jam Khambhalia</button>
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
            <!-- Project 1: RMC Smart City Plaza -->
            <div class="project-card group bg-white border border-gray-100 shadow-premium hover:shadow-premium-hover transition-all duration-500 overflow-hidden flex flex-col" data-region="Rajkot" data-status="Construction Ongoing">
                <div class="h-56 bg-foundation-grey relative overflow-hidden">
                    <img src="../assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg" alt="Executive Overview" class="w-full h-full object-cover group-hover:scale-110 group-hover:opacity-40 transition duration-700">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent flex flex-col justify-end p-6">
                       <span class="px-3 py-1 bg-approval-green text-white text-[10px] font-bold uppercase tracking-widest mb-2 w-max shadow-lg">Construction Phase</span>
                       <h3 class="text-xl font-serif font-bold text-white group-hover:text-rajkot-rust transition-colors">Rajkot Smart City Plaza</h3>
                    </div>
                </div>
                <div class="p-6 flex-grow">
                    <div class="flex items-center text-sm text-gray-500 mb-6">
                        <i data-lucide="map-pin" class="w-4 h-4 mr-2 text-rajkot-rust"></i> Rajkot Infrastructure District
                    </div>
                    <div class="mb-8">
                        <div class="flex justify-between items-end mb-2">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Progress</span>
                            <span class="text-sm font-bold text-rajkot-rust font-sans">72%</span>
                        </div>
                        <div class="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden">
                            <div class="bg-rajkot-rust h-full" style="width: 72%"></div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between py-4 border-t border-gray-50">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Active Status</span>
                        <a href="../dashboard/project_details.php?id=1" class="text-[10px] font-bold uppercase tracking-widest text-foundation-grey hover:text-rajkot-rust">Open Record</a>
                    </div>
                </div>
            </div>

            <!-- Project 2: Jam Towers -->
            <div class="project-card group bg-white border border-gray-100 shadow-premium hover:shadow-premium-hover transition-all duration-500 overflow-hidden flex flex-col" data-region="Jam Khambhalia" data-status="Conceptual Design">
                <div class="h-56 bg-foundation-grey relative overflow-hidden">
                    <img src="../assets/Content/WhatsApp Image 2026-02-02 at 5.43.21 PM.jpeg" alt="Executive Overview" class="w-full h-full object-cover group-hover:scale-110 group-hover:opacity-40 transition duration-700">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent flex flex-col justify-end p-6">
                       <span class="px-3 py-1 bg-pending-amber text-white text-[10px] font-bold uppercase tracking-widest mb-2 w-max shadow-lg">Pre-Approval</span>
                       <h3 class="text-xl font-serif font-bold text-white group-hover:text-rajkot-rust transition-colors">Matru Ashish</h3>
                    </div>
                </div>
                <div class="p-6 flex-grow">
                    <div class="flex items-center text-sm text-gray-500 mb-6">
                        <i data-lucide="map-pin" class="w-4 h-4 mr-2 text-rajkot-rust"></i> Khambhalia Heights
                    </div>
                    <div class="mb-8">
                        <div class="flex justify-between items-end mb-2">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Progress</span>
                            <span class="text-sm font-bold text-rajkot-rust font-sans">15%</span>
                        </div>
                        <div class="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden">
                            <div class="bg-rajkot-rust h-full" style="width: 15%"></div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between py-4 border-t border-gray-50">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Design Lock</span>
                        <a href="../dashboard/project_details.php?id=2" class="text-[10px] font-bold uppercase tracking-widest text-foundation-grey hover:text-rajkot-rust">Open Record</a>
                    </div>
                </div>
            </div>

            <!-- Project 3: Morvi Ceramic Hub -->
            <div class="project-card group bg-white border border-gray-100 shadow-premium hover:shadow-premium-hover transition-all duration-500 overflow-hidden flex flex-col" data-region="Global" data-status="Construction Ongoing">
                <div class="h-56 bg-foundation-grey relative overflow-hidden">
                    <img src="../assets/Content/WhatsApp Image 2026-02-02 at 6.55.18 PM.jpeg" alt="Industrial" class="w-full h-full object-cover group-hover:scale-110 group-hover:opacity-40 transition duration-700">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent flex flex-col justify-end p-6">
                       <span class="px-3 py-1 bg-approval-green text-white text-[10px] font-bold uppercase tracking-widest mb-2 w-max shadow-lg">Industrial</span>
                       <h3 class="text-xl font-serif font-bold text-white group-hover:text-rajkot-rust transition-colors">Morbi Ceramic Hub</h3>
                    </div>
                </div>
                <div class="p-6 flex-grow">
                    <div class="flex items-center text-sm text-gray-500 mb-6">
                        <i data-lucide="map-pin" class="w-4 h-4 mr-2 text-rajkot-rust"></i> Morvi District
                    </div>
                    <div class="mb-8">
                        <div class="flex justify-between items-end mb-2">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Progress</span>
                            <span class="text-sm font-bold text-rajkot-rust font-sans">88%</span>
                        </div>
                        <div class="w-full bg-gray-100 h-1.5 rounded-full overflow-hidden">
                            <div class="bg-rajkot-rust h-full" style="width: 88%"></div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between py-4 border-t border-gray-50">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Final Stage</span>
                        <a href="../dashboard/project_details.php?id=3" class="text-[10px] font-bold uppercase tracking-widest text-foundation-grey hover:text-rajkot-rust">Open Record</a>
                    </div>
                </div>
            </div>

            <!-- Add Project Card -->
            <div class="border-2 border-dashed border-gray-200 p-8 flex flex-col items-center justify-center text-center group hover:border-rajkot-rust transition-colors cursor-pointer min-h-[400px]" onclick="openVentureModal()">
                <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mb-6 group-hover:bg-rajkot-rust transition-colors">
                    <i data-lucide="plus" class="w-10 h-10 text-gray-300 group-hover:text-white transition-colors"></i>
                </div>
                <h4 class="font-serif font-bold text-xl text-gray-400 group-hover:text-foundation-grey">Initialize Venture</h4>
                <p class="text-sm text-gray-400 mt-2 max-w-[200px]">Start a new architectural record for standard or government infrastructure.</p>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/../Common/footer.php'; ?>

    <!-- Initialize Venture Modal -->
    <div id="ventureModal" class="fixed inset-0 bg-black/80 backdrop-blur-md z-[100] hidden items-center justify-center p-4">
        <div class="bg-white max-w-4xl w-full max-h-[90vh] overflow-y-auto shadow-2xl border-b-4 border-rajkot-rust">
            <div class="px-10 py-8 border-b border-gray-100 flex justify-between items-center sticky top-0 bg-white z-10">
                <div>
                    <h2 class="text-2xl font-serif font-bold text-foundation-grey">Initialize New Venture</h2>
                    <p class="text-xs text-gray-400 uppercase tracking-widest mt-1">Registry Entry • Part II-B (Standard)</p>
                </div>
                <button onclick="closeVentureModal()" class="text-gray-400 hover:text-rajkot-rust transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
            
            <form class="p-10 space-y-8" id="ventureForm" onsubmit="handleVentureSubmit(event)">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Client Details -->
                    <div class="space-y-4">
                        <h3 class="text-[10px] font-bold uppercase tracking-widest text-rajkot-rust border-b border-rajkot-rust/20 pb-2">I. Client Identity</h3>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 mb-2 uppercase tracking-tighter">Legal Full Name</label>
                            <input type="text" name="client_name" required class="w-full bg-gray-50 border border-gray-200 p-3 outline-none focus:border-rajkot-rust text-sm">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 mb-2 uppercase tracking-tighter">Contact Primary (Mobile / Official)</label>
                            <input type="tel" name="client_contact" required class="w-full bg-gray-50 border border-gray-200 p-3 outline-none focus:border-rajkot-rust text-sm">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 mb-2 uppercase tracking-tighter">Email Address</label>
                            <input type="email" name="client_email" required class="w-full bg-gray-50 border border-gray-200 p-3 outline-none focus:border-rajkot-rust text-sm">
                        </div>
                    </div>

                    <!-- Project Details -->
                    <div class="space-y-4">
                        <h3 class="text-[10px] font-bold uppercase tracking-widest text-rajkot-rust border-b border-rajkot-rust/20 pb-2">II. Project Scope</h3>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 mb-2 uppercase tracking-tighter">Project Designation (Name)</label>
                            <input type="text" name="project_name" required placeholder="e.g. Skyline Apartments — Tower A" class="w-full bg-gray-50 border border-gray-200 p-3 outline-none focus:border-rajkot-rust text-sm">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 mb-2 uppercase tracking-tighter">Type</label>
                                <select name="project_type" class="w-full bg-gray-50 border border-gray-200 p-3 outline-none focus:border-rajkot-rust text-sm">
                                    <option>Residential</option>
                                    <option>Commercial</option>
                                    <option>Industrial</option>
                                    <option>Infrastructure</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-gray-400 mb-2 uppercase tracking-tighter">Estimated Budget</label>
                                <input type="text" name="project_budget" placeholder="₹" class="w-full bg-gray-50 border border-gray-200 p-3 outline-none focus:border-rajkot-rust text-sm">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-gray-400 mb-2 uppercase tracking-tighter">Site Location (Rajkot/Gujarat)</label>
                            <textarea name="project_location" rows="2" class="w-full bg-gray-50 border border-gray-200 p-3 outline-none focus:border-rajkot-rust text-sm"></textarea>
                        </div>
                    </div>
                </div>

                <div class="pt-6 flex justify-end gap-4 border-t border-gray-100">
                    <button type="button" onclick="closeVentureModal()" class="px-8 py-3 text-gray-400 font-bold uppercase tracking-widest text-[10px] hover:text-foundation-grey transition-colors">Abort</button>
                    <button type="submit" class="bg-rajkot-rust text-white px-10 py-3 font-bold uppercase tracking-widest text-[10px] shadow-premium hover:bg-foundation-grey transition-all">Initialize Construction Record</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // jQuery Validation for the Venture Form
            $("#ventureForm").validate({
                rules: {
                    client_name: "required",
                    client_contact: {
                        required: true,
                        minlength: 10
                    },
                    client_email: {
                        required: true,
                        email: true
                    },
                    project_name: "required",
                    project_budget: "required",
                    project_location: "required"
                },
                messages: {
                    client_name: "Please enter the legal name",
                    client_contact: "Valid contact number required",
                    client_email: "Valid registry email required",
                    project_name: "Project designation is mandatory",
                    project_budget: "Estimated budget required",
                    project_location: "Site location must be specified"
                },
                submitHandler: function(form) {
                    alert('Venture initialization sequence complete. Project record created in registry (Demo Mode).');
                    closeVentureModal();
                }
            });
        });

        let dashboardState = {
            region: 'Global',
            status: 'All Statuses'
        };

        function filterRegion(region) {
            dashboardState.region = region;
            
            // UI updates for buttons
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('bg-rajkot-rust', 'text-white', 'active-filter');
                btn.classList.add('bg-white', 'text-gray-500', 'border-gray-100');
            });
            
            const activeBtn = event.currentTarget;
            activeBtn.classList.add('bg-rajkot-rust', 'text-white', 'active-filter');
            activeBtn.classList.remove('bg-white', 'text-gray-500', 'border-gray-100');
            
            applyAllFilters();
        }

        function filterStatus(status) {
            dashboardState.status = status;
            applyAllFilters();
        }

        function applyAllFilters() {
            const cards = document.querySelectorAll('.project-card');
            cards.forEach(card => {
                const cardRegion = card.getAttribute('data-region');
                const cardStatus = card.getAttribute('data-status');
                
                let matchesRegion = (dashboardState.region === 'Global' || cardRegion === dashboardState.region);
                let matchesStatus = (dashboardState.status === 'All Statuses' || cardStatus === dashboardState.status);
                
                if (matchesRegion && matchesStatus) {
                    card.classList.remove('hidden');
                } else {
                    card.classList.add('hidden');
                }
            });
        }

        // Attach event listener to status dropdown
        document.querySelector('select').addEventListener('change', function(e) {
            filterStatus(e.target.value);
        });

        function openVentureModal() {
            const modal = document.getElementById('ventureModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeVentureModal() {
            const modal = document.getElementById('ventureModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = 'auto';
        }

        function handleVentureSubmit(e) {
            // Handled by jQuery Validation's submitHandler
            return false;
        }

        // Close on backdrop click
        document.getElementById('ventureModal').addEventListener('click', function(e) {
            if (e.target === this) closeVentureModal();
        });
    </script>
  </div>

</body>
</html>