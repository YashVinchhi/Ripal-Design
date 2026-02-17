<?php
// Client Revisions (Redesigned UI)
session_start();
require_once __DIR__ . '/../includes/config.php';
$projectId = $_GET['project_id'] ?? 'PRJ-2024-001';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Revision Archive | Ripal Design</title>
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
    <div class="mb-12">
      <div class="flex items-center gap-2 text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">
         <a href="client_files.php" class="hover:text-rajkot-rust transition">Design Studio</a>
         <i class="bi bi-chevron-right text-[8px]"></i>
         <span>Revision History</span>
      </div>
      <h1 class="text-3xl font-serif font-bold text-rajkot-rust">Revision Archive</h1>
      <p class="text-gray-500 mt-1">Timeline of design evolutions for <strong><?php echo htmlspecialchars($projectId); ?></strong>.</p>
    </div>

    <!-- Timeline UI -->
    <div class="relative">
      <!-- Vertical Line -->
      <div class="absolute left-4 md:left-1/2 top-0 bottom-0 w-px bg-gray-200 -translate-x-1/2"></div>

      <!-- Revision 3 (Latest) -->
      <div class="relative mb-16">
        <div class="flex flex-col md:flex-row items-center">
          <div class="flex-grow md:w-1/2 md:pr-12 mb-4 md:mb-0">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 md:text-right hover:shadow-md transition group">
               <span class="text-[10px] font-black font-mono text-rajkot-rust tracking-tighter uppercase mb-2 block">Feb 15, 2024 • 11:30 AM</span>
               <h3 class="text-lg font-bold text-foundation-grey mb-2 group-hover:text-rajkot-rust transition">Structural Optimization v2.4</h3>
               <p class="text-xs text-gray-500 leading-relaxed italic">
                 "Adjusted steel column placements in Section B-B to improve open-floor visibility as requested."
               </p>
               <div class="mt-4 flex flex-wrap justify-end gap-2">
                 <span class="px-2 py-0.5 bg-green-50 text-green-700 text-[10px] font-bold rounded uppercase border border-green-100">Approved</span>
                 <span class="px-2 py-0.5 bg-gray-50 text-gray-500 text-[10px] font-bold rounded uppercase border border-gray-200">2 Files</span>
               </div>
            </div>
          </div>
          <div class="absolute left-4 md:left-1/2 w-4 h-4 rounded-full bg-rajkot-rust border-4 border-white shadow-sm -translate-x-1/2"></div>
          <div class="flex-grow md:w-1/2 md:pl-12"></div>
        </div>
      </div>

      <!-- Revision 2 -->
      <div class="relative mb-16">
        <div class="flex flex-col md:flex-row items-center">
          <div class="flex-grow md:w-1/2 md:pr-12"></div>
          <div class="absolute left-4 md:left-1/2 w-4 h-4 rounded-full bg-gray-300 border-4 border-white shadow-sm -translate-x-1/2"></div>
          <div class="flex-grow md:w-1/2 md:pl-12">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition group">
               <span class="text-[10px] font-black font-mono text-gray-400 tracking-tighter uppercase mb-2 block">Feb 08, 2024 • 04:15 PM</span>
               <h3 class="text-lg font-bold text-foundation-grey mb-2 group-hover:text-rajkot-rust transition">Façade Material Update v2.1</h3>
               <p class="text-xs text-gray-500 leading-relaxed italic">
                 "Updated the GRC panel textures and added Saurashtra pattern detailing to the north elevation."
               </p>
               <div class="mt-4 flex flex-wrap gap-2">
                 <span class="px-2 py-0.5 bg-amber-50 text-amber-700 text-[10px] font-bold rounded uppercase border border-amber-100">Revision Requested</span>
                 <span class="px-2 py-0.5 bg-gray-50 text-gray-500 text-[10px] font-bold rounded uppercase border border-gray-200">1 File</span>
               </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Start of Project -->
      <div class="relative">
        <div class="flex flex-col md:flex-row items-center">
           <div class="flex-grow md:w-1/2 md:pr-12 mb-4 md:mb-0">
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 md:text-right hover:shadow-md transition group">
               <span class="text-[10px] font-black font-mono text-gray-400 tracking-tighter uppercase mb-2 block">Jan 20, 2024</span>
               <h3 class="text-lg font-bold text-foundation-grey mb-2 group-hover:text-rajkot-rust transition">Project Genesis</h3>
               <p class="text-xs text-gray-500 leading-relaxed italic">
                 Initial schematic drawings and conceptual site plan.
               </p>
            </div>
          </div>
          <div class="absolute left-4 md:left-1/2 w-8 h-8 rounded-full bg-foundation-grey flex items-center justify-center -translate-x-1/2">
             <i class="bi bi-flag-fill text-white text-xs"></i>
          </div>
          <div class="flex-grow md:w-1/2 md:pl-12"></div>
        </div>
      </div>
    </div>
  </main>

  <?php require_once __DIR__ . '/../common/footer.php'; ?>
</body>
</html>