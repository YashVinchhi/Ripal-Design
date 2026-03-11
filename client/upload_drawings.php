<?php
// Upload Drawings (Redesigned UI)
session_start();
require_once __DIR__ . '/../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg = 'Blueprint received and queued for architectural review.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Upload Drawings | Ripal Design</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://code.jquery.com/jquery-4.0.0.js" integrity="sha256-9fsHeVnKBvqh3FB2HYu7g2xseAZ5MlN6Kz/qnkASV8U=" crossorigin="anonymous"></script>
  <script src="../public/js/validation.js"></script>

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
         <span>Upload Drawings</span>
      </div>
      <h1 class="text-3xl font-serif font-bold text-rajkot-rust">Submit Blueprints</h1>
      <p class="text-gray-500 mt-1">Submit your revised drawings or site photos for project review.</p>
    </div>

    <?php if (!empty($msg)): ?>
    <div class="mb-8 p-4 bg-green-50 border border-green-100 rounded-xl flex items-center gap-3 text-green-700 shadow-sm animate-bounce-slow">
       <i class="bi bi-check-circle-fill text-xl"></i>
       <p class="text-sm font-bold"><?php echo htmlspecialchars($msg); ?></p>
    </div>
    <?php endif; ?>

    <div class="max-w-3xl mx-auto">
      <form method="post" enctype="multipart/form-data" class="bg-white p-8 md:p-12 rounded-3xl shadow-sm border border-gray-100 relative overflow-hidden" novalidate>
        <div class="absolute top-0 right-0 w-32 h-32 bg-rajkot-rust opacity-[0.03] -mr-16 -mt-16 rounded-full pointer-events-none"></div>
        
        <div class="mb-8">
          <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Project Selection</label>
          <select class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-rajkot-rust focus:border-transparent outline-none transition">
             <option>PRJ-2024-001: RMC Smart City Plaza</option>
             <option>PRJ-2024-008: Saurashtra Heritage Villa</option>
          </select>
        </div>

        <div class="mb-8">
          <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Upload Drawing (PDF/DWG)</label>
          <div class="border-2 border-dashed border-gray-200 rounded-3xl p-12 text-center group hover:border-rajkot-rust transition cursor-pointer relative">
             <input type="file" name="drawing" class="absolute inset-0 opacity-0 cursor-pointer" id="fileInput" data-validation="required fileType:pdf,dwg fileSize:51200">
             <span id="name_error" class="text-danger"></span>
             <div class="space-y-4">
                <div class="w-16 h-16 bg-gray-50 text-gray-400 rounded-full flex items-center justify-center mx-auto group-hover:bg-red-50 group-hover:text-rajkot-rust transition">
                   <i class="bi bi-cloud-arrow-up text-3xl"></i>
                </div>
                <div>
                   <p class="text-sm font-bold text-foundation-grey">Click to upload or drag and drop</p>
                   <p class="text-[10px] text-gray-400 uppercase tracking-widest mt-1">Maximum file size: 50MB</p>
                </div>
             </div>
          </div>
        </div>

        <div class="mb-10">
          <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Submission Notes</label>
          <textarea rows="4" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-rajkot-rust focus:border-transparent outline-none transition" placeholder="Briefly describe the changes or purpose of this upload..."></textarea>
        </div>

        <button type="submit" class="w-full py-4 bg-rajkot-rust text-white font-bold rounded-xl hover:bg-red-800 transition shadow-xl shadow-red-900/20 uppercase tracking-[0.2em] text-sm flex items-center justify-center gap-3">
          Submit for Review <i class="bi bi-send-fill"></i>
        </button>
      </form>

      <div class="mt-8 text-center">
         <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold">Secure Architectural Transmission • ISO 9001:2015 Compliant</p>
      </div>
    </div>
  </main>

  <style>
    @keyframes bounce-slow {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-5px); }
    }
    .animate-bounce-slow {
      animation: bounce-slow 3s infinite ease-in-out;
    }
  </style>

  <?php require_once __DIR__ . '/../common/footer.php'; ?>
</body>
</html>