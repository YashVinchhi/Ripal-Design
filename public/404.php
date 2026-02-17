<?php
$HEADER_MODE = 'public';
require_once __DIR__ . '/../includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" /><?php
// 404 Error Page (Redesigned UI)
header("HTTP/1.0 404 Not Found");
require_once __DIR__ . '/../includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Lost in Space | Ripal Design</title>
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
<body class="bg-foundation-grey font-sans text-white min-h-screen flex items-center justify-center overflow-hidden relative">
  <!-- Decorative Elements -->
  <div class="absolute top-0 left-0 w-full h-full bg-striped-dark opacity-10 pointer-events-none"></div>
  <div class="absolute -top-24 -left-24 w-96 h-96 bg-rajkot-rust rounded-full blur-[120px] opacity-20 animate-pulse"></div>
  <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-rajkot-rust rounded-full blur-[120px] opacity-20 animate-pulse"></div>

  <main class="relative z-10 max-w-2xl px-6 text-center">
    <div class="mb-8 relative">
       <span class="text-[180px] md:text-[240px] font-black leading-none text-white/5 select-none tracking-tighter">404</span>
       <div class="absolute inset-0 flex flex-col items-center justify-center pt-8">
          <h1 class="text-4xl md:text-5xl font-serif font-bold text-rajkot-rust mb-2">Structure Not Found</h1>
          <p class="text-gray-400 text-sm md:text-base max-w-md mx-auto leading-relaxed">
            The architectural blueprint you're looking for seems to have been misplaced or never existed.
          </p>
       </div>
    </div>

    <div class="flex flex-col md:flex-row items-center justify-center gap-4 mt-8">
      <a href="<?php echo BASE_URL; ?>" class="w-full md:w-auto px-8 py-3 bg-rajkot-rust text-white font-bold rounded-full hover:bg-red-800 transition shadow-lg shadow-red-900/40 flex items-center justify-center gap-2">
        <i class="bi bi-house"></i> Back to Home
      </a>
      <button onclick="window.history.back()" class="w-full md:w-auto px-8 py-3 bg-white/5 border border-white/10 text-white font-bold rounded-full hover:bg-white/10 transition flex items-center justify-center gap-2">
        <i class="bi bi-arrow-left"></i> Previous Page
      </button>
    </div>

    <div class="mt-16 pt-8 border-t border-white/5">
       <p class="text-[10px] uppercase tracking-[0.2em] text-gray-500 font-bold">Ripal Design & Engineering Studio</p>
    </div>
  </main>

  <style>
    .bg-striped-dark {
      background-image: repeating-linear-gradient(45deg, #000, #000 1px, transparent 1px, transparent 10px);
    }
  </style>
</body>
</html>