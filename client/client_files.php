<?php
// Client Files (Redesigned UI)
session_start();
require_once __DIR__ . '/../includes/config.php';
$projectId = $_GET['project_id'] ?? 'PRJ-2024-001';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Design Studio | Ripal Design</title>
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
  <?php $HEADER_MODE = 'dashboard'; require_once __DIR__ . '/../common/header_alt.php'; ?>
  
  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 mt-20">
    <div class="mb-8">
      <div class="flex items-center gap-2 text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">
         <a href="dashboard.php" class="hover:text-rajkot-rust transition">Dashboard</a>
         <i class="bi bi-chevron-right text-[8px]"></i>
         <span>Design Studio</span>
      </div>
      <h1 class="text-3xl font-serif font-bold text-rajkot-rust">Design Studio</h1>
      <p class="text-gray-500 mt-1">Review and approve architectural drawings for <strong><?php echo htmlspecialchars($projectId); ?></strong>.</p>
    </div>

    <!-- Project Status Progress Bar -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 mb-8">
      <div class="flex justify-between items-end mb-4">
        <div>
          <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Current Phase</p>
          <h2 class="text-lg font-bold text-foundation-grey">Detailed Design & Engineering</h2>
        </div>
        <div class="text-right">
          <span class="text-2xl font-black text-rajkot-rust">65%</span>
        </div>
      </div>
      <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden">
        <div class="bg-rajkot-rust h-full rounded-full transition-all duration-1000" style="width: 65%"></div>
      </div>
      <div class="flex justify-between mt-3 text-[10px] font-bold text-gray-400 uppercase tracking-tighter">
        <span>Concept</span>
        <span>Schematic</span>
        <span class="text-rajkot-rust">Detailed Design</span>
        <span>Construction</span>
        <span>Handover</span>
      </div>
    </div>

    <!-- Files Table -->
    <div class="bg-white shadow-sm border border-gray-100 rounded-xl overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-bold text-foundation-grey">Latest Drawings</h3>
        <div class="flex gap-2">
           <button class="p-2 hover:bg-gray-50 rounded text-gray-400" title="Grid View"><i class="bi bi-grid"></i></button>
           <button class="p-2 bg-gray-50 rounded text-rajkot-rust" title="List View"><i class="bi bi-list-ul"></i></button>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead class="bg-gray-50/50 border-b border-gray-100 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
            <tr>
              <th class="px-6 py-4">Document</th>
              <th class="px-6 py-4">Version</th>
              <th class="px-6 py-4">Date</th>
              <th class="px-6 py-4">Preview</th>
              <th class="px-6 py-4">Status</th>
              <th class="px-6 py-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <!-- Approved Item -->
            <tr class="group hover:bg-gray-50/50 transition">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded bg-blue-50 text-blue-600 flex items-center justify-center text-xl border border-blue-100">
                    <i class="bi bi-file-earmark-pdf"></i>
                  </div>
                  <div>
                    <p class="text-sm font-bold text-foundation-grey">Site Layout Plan - GF</p>
                    <p class="text-[10px] text-gray-400">Blueprint_SLP_01.pdf (4.2 MB)</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 text-xs font-mono text-gray-500">v1.8</td>
              <td class="px-6 py-4 text-xs text-gray-500">Feb 14, 2024</td>
              <td class="px-6 py-4">
                <div class="w-16 h-10 bg-gray-100 rounded border border-gray-200 overflow-hidden relative group-hover:border-rajkot-rust transition">
                  <div class="absolute inset-0 bg-black/5 flex items-center justify-center opacity-0 group-hover:opacity-100 transition cursor-zoom-in">
                    <i class="bi bi-search text-xs text-rajkot-rust"></i>
                  </div>
                  <div class="h-full w-full bg-striped opacity-20"></div>
                </div>
              </td>
              <td class="px-6 py-4">
                 <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-green-50 text-green-700 border border-green-100">
                   <i class="bi bi-lock-fill"></i> Approved
                 </span>
              </td>
              <td class="px-6 py-4 text-right">
                 <button class="bg-white border border-gray-200 text-gray-600 px-3 py-1 rounded text-xs font-bold hover:border-rajkot-rust hover:text-rajkot-rust transition shadow-sm">
                   View
                 </button>
              </td>
            </tr>

            <!-- Pending Review -->
            <tr class="group hover:bg-gray-50/50 transition">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded bg-amber-50 text-amber-600 flex items-center justify-center text-xl border border-amber-100">
                    <i class="bi bi-file-earmark-pdf"></i>
                  </div>
                  <div>
                    <p class="text-sm font-bold text-foundation-grey">Electrical Conduit Plan</p>
                    <p class="text-[10px] text-gray-400">Blueprint_ELE_04.pdf (2.8 MB)</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 text-xs font-mono text-gray-500">v2.1</td>
              <td class="px-6 py-4 text-xs text-gray-500">Just Now</td>
              <td class="px-6 py-4 text-xs text-gray-500 italic">Processing...</td>
              <td class="px-6 py-4">
                 <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-50 text-amber-700 border border-amber-100 animate-pulse">
                   Pending Review
                 </span>
              </td>
              <td class="px-6 py-4 text-right">
                <div class="flex justify-end gap-2">
                   <button class="bg-green-600 text-white px-3 py-1 rounded text-xs font-bold hover:bg-green-700 transition shadow-sm">
                     Approve
                   </button>
                   <button class="bg-white border border-rajkot-rust text-rajkot-rust px-3 py-1 rounded text-xs font-bold hover:bg-red-50 transition shadow-sm">
                     Revision
                   </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <style>
    .bg-striped {
      background-image: repeating-linear-gradient(45deg, #000, #000 1px, transparent 1px, transparent 5px);
    }
  </style>

  <?php require_once __DIR__ . '/../common/footer.php'; ?>
</body>
</html>