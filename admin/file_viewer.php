<?php
// File Viewer (Redesigned UI)
session_start();
require_once __DIR__ . '/../includes/config.php';
$file = $_GET['file'] ?? null;
$fileName = $file ? basename($file) : 'Blueprint_A1_01.pdf';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>File Viewer | Ripal Design</title>
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
<body class="bg-foundation-grey font-sans text-white min-h-screen flex flex-col">
  <?php $HEADER_MODE = 'dashboard'; require_once __DIR__ . '/../common/header_alt.php'; ?>
  
  <main class="flex-grow flex flex-col md:flex-row mt-16 md:mt-20 overflow-hidden h-screen md:h-[calc(100vh-80px)]">
    <!-- Sidebar / File Info -->
    <aside id="fileSidebar" class="w-full md:w-80 bg-white text-foundation-grey border-r border-gray-200 overflow-y-auto hidden md:block transition-all duration-300">
      <div class="p-6 border-b border-gray-100">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-sm font-bold uppercase tracking-widest text-rajkot-rust">File Details</h2>
          <button class="md:hidden text-gray-400" onclick="toggleSidebar()">
            <i class="bi bi-x-lg text-lg"></i>
          </button>
        </div>
        <div class="space-y-4">
          <div>
            <label class="text-[10px] text-gray-400 uppercase font-bold">Project</label>
            <p class="text-sm font-semibold">RMC Smart City Plaza</p>
          </div>
          <div>
            <label class="text-[10px] text-gray-400 uppercase font-bold">File Name</label>
            <p class="text-sm font-semibold break-words"><?php echo htmlspecialchars($fileName); ?></p>
          </div>
          <div>
            <label class="text-[10px] text-gray-400 uppercase font-bold">Version</label>
            <p class="text-sm font-semibold">v2.4 (Current)</p>
          </div>
          <div>
            <label class="text-[10px] text-gray-400 uppercase font-bold">Status</label>
            <span class="inline-flex mt-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-green-100 text-green-700 border border-green-200">Construction Issued</span>
          </div>
        </div>
      </div>

      <div class="p-6 pb-24 md:pb-6">
        <h2 class="text-sm font-bold uppercase tracking-widest text-rajkot-rust mb-4">Revision History</h2>
        <div class="space-y-3">
          <div class="flex items-start gap-3 p-2 bg-gray-50 rounded border-l-2 border-l-rajkot-rust shadow-sm">
             <div class="mt-1"><i class="bi bi-file-earmark-pdf text-rajkot-rust"></i></div>
             <div>
               <p class="text-xs font-bold text-foundation-grey">v2.4 - Feb 15</p>
               <p class="text-[10px] text-gray-500 italic">Minor structural edits</p>
             </div>
          </div>
          <div class="flex items-start gap-3 p-2 hover:bg-gray-50 transition cursor-pointer rounded">
             <div class="mt-1"><i class="bi bi-file-earmark text-gray-400"></i></div>
             <div>
               <p class="text-xs font-medium text-gray-600">v2.3 - Feb 02</p>
               <p class="text-[10px] text-gray-400 italic">Initial submission</p>
             </div>
          </div>
          <div class="flex items-start gap-3 p-2 hover:bg-gray-50 transition cursor-pointer rounded">
             <div class="mt-1"><i class="bi bi-file-earmark text-gray-400"></i></div>
             <div>
               <p class="text-xs font-medium text-gray-600">v2.2 - Jan 20</p>
               <p class="text-[10px] text-gray-400 italic">Schematic approval</p>
             </div>
          </div>
          <div class="flex items-start gap-3 p-2 hover:bg-gray-50 transition cursor-pointer rounded opacity-50">
             <div class="mt-1"><i class="bi bi-file-earmark text-gray-300"></i></div>
             <div>
               <p class="text-xs font-medium text-gray-400">v2.1 - Jan 05</p>
               <p class="text-[10px] text-gray-300 italic">Conceptual draft</p>
             </div>
          </div>
        </div>
      </div>
    </aside>

    <!-- Main Viewer Area -->
    <div class="flex-grow bg-[#E5E7EB] relative flex flex-col group overflow-hidden">
      <!-- Toolbar -->
      <div class="bg-foundation-grey/90 backdrop-blur text-white px-4 py-2 flex items-center justify-between z-10 shrink-0">
        <div class="flex items-center gap-3 md:gap-4 overflow-hidden">
           <button class="md:hidden p-2 hover:bg-white/10 rounded" onclick="toggleSidebar()">
             <i class="bi bi-info-circle"></i>
           </button>
           <span class="text-[10px] md:text-xs font-mono font-bold tracking-tighter bg-rajkot-rust px-1.5 md:py-0.5 rounded shrink-0">PDF</span>
           <span class="text-[10px] md:text-xs text-gray-300 truncate max-w-[120px] md:max-w-xs"><?php echo htmlspecialchars($fileName); ?></span>
        </div>
        <div class="flex items-center gap-1 md:gap-2">
           <button class="h-10 w-10 md:h-8 md:w-8 hover:bg-white/10 rounded transition flex items-center justify-center text-lg" title="Zoom Out" onclick="updateZoom(-10)"><i class="bi bi-dash"></i></button>
           <span class="text-[10px] md:text-xs font-medium px-1 w-10 md:w-12 text-center" id="zoom-level">100%</span>
           <button class="h-10 w-10 md:h-8 md:w-8 hover:bg-white/10 rounded transition flex items-center justify-center text-lg" title="Zoom In" onclick="updateZoom(10)"><i class="bi bi-plus"></i></button>
           <div class="w-px h-6 bg-white/20 mx-1 md:mx-2 hidden sm:block"></div>
           <button class="h-10 w-10 hover:bg-white/10 rounded transition flex items-center justify-center hidden sm:block" title="Print" onclick="window.print()"><i class="bi bi-printer text-lg"></i></button>
           <button class="h-10 w-10 md:h-8 md:w-8 hover:bg-white/10 rounded transition flex items-center justify-center" title="Download" onclick="handleDownload()"><i class="bi bi-download text-lg md:text-base"></i></button>
        </div>
      </div>

      <!-- PDF Canvas Mock -->
      <div class="flex-grow overflow-auto flex items-center justify-center p-4 md:p-8 bg-foundation-grey print:p-0 print:bg-white relative">
        <div id="pdf-container" class="bg-white shadow-2xl w-full max-w-4xl aspect-[1/1.414] relative flex flex-col border border-gray-300 overflow-hidden transition-transform duration-200 origin-center">
           <!-- Placeholder for PDF.js Canvas -->
           <div class="absolute inset-0 bg-striped-dots opacity-5"></div>
           <div class="absolute bottom-4 left-4 flex flex-col items-start gap-1">
              <img src="<?php echo BASE_PATH; ?>/assets/Content/Logo.png" alt="Ripal Design" class="h-6 opacity-30 invert">
              <span class="text-[8px] text-gray-400 font-mono tracking-widest uppercase">Proprietary Construction Drawing</span>
           </div>
           <!-- Blueprint-like Overlay -->
           <div class="flex-grow flex items-center justify-center border-4 border-gray-50 m-4 relative overflow-hidden">
              <div class="absolute inset-0 flex flex-col items-center justify-center text-gray-100 font-serif opacity-50 select-none pointer-events-none">
                 <p class="text-4xl md:text-9xl rotate-[-45deg] border-4 border-gray-100 px-4 md:px-8 py-2 md:py-4 uppercase font-bold">Ripal Design</p>
              </div>
              <!-- Simulated Architectural Content -->
              <div class="absolute inset-0 flex items-center justify-center">
                 <i class="bi bi-building text-[100px] md:text-[200px] text-gray-50 opacity-10"></i>
              </div>
              <div class="absolute inset-x-0 top-0 h-px bg-gray-100"></div>
              <div class="absolute inset-x-0 bottom-0 h-px bg-gray-100"></div>
              <div class="absolute inset-y-0 left-0 w-px bg-gray-100"></div>
              <div class="absolute inset-y-0 right-0 w-px bg-gray-100"></div>
           </div>
        </div>
      </div>

      <!-- Action Footer (Floating on mobile) -->
      <div class="fixed md:absolute bottom-6 md:bottom-8 left-1/2 -translate-x-1/2 flex flex-col sm:flex-row items-center gap-3 md:gap-4 bg-white/10 md:bg-white/10 backdrop-blur-lg md:backdrop-blur-md p-3 md:p-3 rounded-2xl md:rounded-full border border-white/20 shadow-2xl scale-90 md:scale-100 opacity-100 md:opacity-0 md:group-hover:opacity-100 transition duration-300 z-20">
         <button class="w-full sm:w-auto bg-green-600 text-white px-6 py-2.5 rounded-full font-bold text-xs md:text-sm hover:bg-green-700 shadow-xl shadow-green-900/40 transition flex items-center justify-center gap-2">
            <i class="bi bi-check-circle"></i> Approve Drawing
         </button>
         <button class="w-full sm:w-auto bg-rajkot-rust text-white px-6 py-2.5 rounded-full font-bold text-xs md:text-sm hover:bg-red-800 shadow-xl shadow-red-900/40 transition flex items-center justify-center gap-2">
            <i class="bi bi-exclamation-triangle"></i> Request Revision
         </button>
      </div>
    </div>
  <script>
    let currentZoom = 100;
    
    function toggleSidebar() {
        const sidebar = document.getElementById('fileSidebar');
        sidebar.classList.toggle('hidden');
        sidebar.classList.toggle('fixed');
        sidebar.classList.toggle('inset-0');
        sidebar.classList.toggle('z-30');
    }

    function updateZoom(delta) {
        currentZoom = Math.min(Math.max(currentZoom + delta, 50), 200);
        document.getElementById('zoom-level').textContent = currentZoom + '%';
        document.getElementById('pdf-container').style.transform = `scale(${currentZoom / 100})`;
    }

    function handleDownload() {
        const btn = event.currentTarget;
        const originalContent = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i>';
        
        // Simulated download
        setTimeout(() => {
            alert('File: <?php echo addslashes($fileName); ?> is being prepared for secure download. Please check your browser downloads.');
            btn.innerHTML = originalContent;
        }, 1200);
    }
  </script>

  <?php require_once __DIR__ . '/../common/footer.php'; ?>
</body>
</html>