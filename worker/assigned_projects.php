<?php
// Assigned Projects (Redesigned UI)
session_start();
require_once __DIR__ . '/../includes/config.php';
$user = $_SESSION['user'] ?? 'Worker';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Assigned Projects | Ripal Design</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            'rajkot-rust': '#94180C',
            'canvas-white': '#F9FAFB',
            'foundation-grey': '#2D2D2D',
          },
          fontFamily: {
            sans: ['Inter', 'sans-serif'],
            serif: ['Playfair Display', 'serif'],
          }
        }
      }
    }
  </script>
</head>
<body class="bg-canvas-white font-sans text-foundation-grey min-h-screen">
  <?php require_once __DIR__ . '/../Common/header_alt.php'; ?>
  <!-- Unified Dark Portal Header -->
  <header class="bg-foundation-grey text-white pt-24 pb-12 px-4 shadow-lg mb-12">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-4xl font-serif font-bold">Active Assignments</h1>
        <p class="text-gray-400 mt-2">Manage your current site projects and structural tasks.</p>
    </div>
  </header>
  
  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    <div class="flex flex-col lg:flex-row gap-8">
      <!-- Project List -->
      <div class="lg:w-2/3 space-y-6">
        <!-- Project 1 -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition">
          <div class="p-6">
            <div class="flex flex-col md:flex-row justify-between items-start mb-6">
              <div>
                <span class="text-[10px] font-bold text-rajkot-rust uppercase tracking-widest bg-red-50 px-2 py-0.5 rounded">Phase: Construction</span>
                <h3 class="text-xl font-bold text-foundation-grey mt-2">Jamnagar Industrial Warehouse</h3>
                <p class="text-xs text-gray-400 mt-1 flex items-center gap-1"><i class="bi bi-geo-alt"></i> Metoda GIDC, Plot 42-B</p>
              </div>
              <div class="mt-4 md:mt-0 text-right">
                <span class="text-xs font-bold text-gray-400 uppercase">Deadline</span>
                <p class="text-sm font-bold text-foundation-grey">March 15, 2024</p>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
              <div class="bg-gray-50 p-3 rounded border border-gray-100">
                <p class="text-[10px] text-gray-400 font-bold uppercase">Pending Tasks</p>
                <p class="text-lg font-bold text-foundation-grey">04</p>
              </div>
              <div class="bg-gray-50 p-3 rounded border border-gray-100">
                <p class="text-[10px] text-gray-400 font-bold uppercase">Blueprints</p>
                <p class="text-lg font-bold text-foundation-grey text-rajkot-rust flex items-center gap-2">
                  02 <i class="bi bi-file-earmark-check text-sm"></i>
                </p>
              </div>
              <div class="bg-gray-50 p-3 rounded border border-gray-100">
                <p class="text-[10px] text-gray-400 font-bold uppercase">Status</p>
                <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-green-100 text-green-700 border border-green-200">On Track</span>
              </div>
            </div>

            <div class="flex items-center justify-between border-t border-gray-50 pt-4">
               <div class="flex -space-x-2">
                  <div class="w-8 h-8 rounded-full bg-rajkot-rust text-white flex items-center justify-center font-bold text-[10px]">AV</div>
                  <div class="w-8 h-8 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center font-bold text-[10px]">JD</div>
               </div>
               <a href="project_details.php?id=101" class="px-5 py-2 bg-foundation-grey text-white text-xs font-bold uppercase tracking-widest rounded hover:bg-rajkot-rust transition">
                 Open Workspace
               </a>
            </div>
          </div>
        </div>

        <!-- Project 2 -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition">
          <div class="p-6">
            <div class="flex flex-col md:flex-row justify-between items-start mb-6">
              <div>
                <span class="text-[10px] font-bold text-amber-600 uppercase tracking-widest bg-amber-50 px-2 py-0.5 rounded">Phase: Foundation</span>
                <h3 class="text-xl font-bold text-foundation-grey mt-2">Saurashtra University Library</h3>
                <p class="text-xs text-gray-400 mt-1 flex items-center gap-1"><i class="bi bi-geo-alt"></i> University Rd, Rajkot</p>
              </div>
              <div class="mt-4 md:mt-0 text-right">
                <span class="text-xs font-bold text-gray-400 uppercase">Deadline</span>
                <p class="text-sm font-bold text-foundation-grey">June 02, 2024</p>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
              <div class="bg-gray-50 p-3 rounded border border-gray-100">
                <p class="text-[10px] text-gray-400 font-bold uppercase">Pending Tasks</p>
                <p class="text-lg font-bold text-foundation-grey">12</p>
              </div>
              <div class="bg-gray-50 p-3 rounded border border-gray-100">
                <p class="text-[10px] text-gray-400 font-bold uppercase">Blueprints</p>
                <p class="text-lg font-bold text-foundation-grey text-rajkot-rust flex items-center gap-2">
                  01 <i class="bi bi-clock-history text-sm"></i>
                </p>
              </div>
              <div class="bg-gray-50 p-3 rounded border border-gray-100">
                <p class="text-[10px] text-gray-400 font-bold uppercase">Status</p>
                <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-50 text-amber-700 border border-amber-100">Needs Attention</span>
              </div>
            </div>

            <div class="flex items-center justify-between border-t border-gray-50 pt-4">
               <div class="flex -space-x-2">
                  <div class="w-8 h-8 rounded-full bg-rajkot-rust text-white flex items-center justify-center font-bold text-[10px]">AV</div>
                  <div class="w-8 h-8 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center font-bold text-[10px]">BK</div>
               </div>
               <a href="project_details.php?id=103" class="px-5 py-2 bg-foundation-grey text-white text-xs font-bold uppercase tracking-widest rounded hover:bg-rajkot-rust transition">
                 Open Workspace
               </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Stats Sidebar -->
      <div class="lg:w-1/3 space-y-6">
        <div class="bg-foundation-grey text-white p-8 rounded-2xl relative overflow-hidden shadow-xl shadow-gray-400/20">
           <div class="absolute -top-12 -right-12 w-48 h-48 bg-rajkot-rust rounded-full blur-[80px] opacity-30"></div>
           <h4 class="text-sm font-bold uppercase tracking-widest text-rajkot-rust mb-6">Workload Summary</h4>
           <div class="space-y-6">
              <div class="flex items-end justify-between">
                 <div>
                    <p class="text-[10px] text-gray-400 font-bold uppercase">Assigned Projects</p>
                    <p class="text-3xl font-black font-serif italic text-rajkot-rust">02</p>
                 </div>
                 <i class="bi bi-briefcase text-4xl text-white/5"></i>
              </div>
              <div class="flex items-end justify-between">
                 <div>
                    <p class="text-[10px] text-gray-400 font-bold uppercase">Hours Logged (Week)</p>
                    <p class="text-3xl font-black font-serif italic text-rajkot-rust">34.5</p>
                 </div>
                 <i class="bi bi-clock text-4xl text-white/5"></i>
              </div>
           </div>
           <button class="w-full mt-8 py-3 bg-white/5 border border-white/10 rounded-lg text-xs font-bold uppercase tracking-widest hover:bg-white/10 transition">
              Log Daily Progress
           </button>
        </div>

        <div class="bg-white p-8 rounded-2xl border border-gray-100 shadow-sm">
           <h4 class="text-sm font-bold uppercase tracking-widest text-foundation-grey mb-6">Recent Reports</h4>
           <div class="space-y-4">
              <div class="flex gap-3">
                 <div class="w-8 h-8 rounded bg-rajkot-rust flex items-center justify-center text-white"><i class="bi bi-file-earmark-text"></i></div>
                 <div>
                    <p class="text-xs font-bold text-foundation-grey leading-none">Weekly Site Update</p>
                    <p class="text-[10px] text-gray-400 mt-1">Feb 12 • Approved</p>
                 </div>
              </div>
              <div class="flex gap-3">
                 <div class="w-8 h-8 rounded bg-gray-100 flex items-center justify-center text-gray-400"><i class="bi bi-file-earmark-text"></i></div>
                 <div>
                    <p class="text-xs font-bold text-foundation-grey leading-none">Material Requisition</p>
                    <p class="text-[10px] text-gray-400 mt-1">Feb 10 • Pending</p>
                 </div>
              </div>
           </div>
        </div>
      </div>
    </div>
  </main>

  <?php require_once __DIR__ . '/../common/footer.php'; ?>
</body>
</html>
