<?php
// Media Gallery (Redesigned UI)
session_start();
require_once __DIR__ . '/../includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Portfolio | Ripal Design</title>
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
  <?php $HEADER_MODE = 'public'; require_once __DIR__ . '/../common/header_alt.php'; ?>
  
  <header class="pt-32 pb-16 bg-foundation-grey text-white relative overflow-hidden">
    <div class="absolute inset-0 bg-rajkot-rust opacity-5 mix-blend-overlay"></div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
       <span class="text-rajkot-rust text-xs font-black uppercase tracking-[0.4em] mb-4 block">Archive of Excellence</span>
       <h1 class="text-4xl md:text-6xl font-serif font-bold mb-6">Our Portfolio</h1>
       <p class="text-gray-400 max-w-2xl mx-auto text-sm md:text-base leading-relaxed">
         Over 200+ projects crafted across Saurashtra, blending industrial structural integrity with timeless architectural aesthetics.
       </p>
    </div>
  </header>

  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Filter Bar -->
    <div class="flex flex-wrap items-center justify-center gap-4 mb-12">
       <button class="px-6 py-2 bg-rajkot-rust text-white rounded-full text-xs font-bold uppercase tracking-widest transition">All Projects</button>
       <button class="px-6 py-2 bg-white text-gray-500 hover:text-rajkot-rust rounded-full text-xs font-bold uppercase tracking-widest border border-gray-100 hover:border-rajkot-rust transition shadow-sm">Industrial</button>
       <button class="px-6 py-2 bg-white text-gray-500 hover:text-rajkot-rust rounded-full text-xs font-bold uppercase tracking-widest border border-gray-100 hover:border-rajkot-rust transition shadow-sm">Residential</button>
       <button class="px-6 py-2 bg-white text-gray-500 hover:text-rajkot-rust rounded-full text-xs font-bold uppercase tracking-widest border border-gray-100 hover:border-rajkot-rust transition shadow-sm">Commercial</button>
    </div>

    <!-- Gallery Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
       <!-- Project 1 -->
       <div class="group relative bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-2xl transition duration-500">
          <div class="aspect-[4/5] overflow-hidden">
             <img src="https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&q=80&w=800" alt="Crystal Mall Extension" class="w-full h-full object-cover group-hover:scale-110 transition duration-700">
             <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition duration-500 flex flex-col justify-end p-8">
                <span class="text-rajkot-rust text-[10px] font-black uppercase tracking-widest mb-2">Commercial</span>
                <h3 class="text-white text-2xl font-serif font-bold mb-4">Crystal Mall Extension</h3>
                <a href="#" class="text-white text-xs font-bold uppercase tracking-widest flex items-center gap-2 hover:text-rajkot-rust transition">
                   View Project Details <i class="bi bi-arrow-right"></i>
                </a>
             </div>
          </div>
       </div>

       <!-- Project 2 -->
       <div class="group relative bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-2xl transition duration-500">
          <div class="aspect-[4/5] overflow-hidden">
             <img src="https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&q=80&w=800" alt="Heritage Restoration" class="w-full h-full object-cover group-hover:scale-110 transition duration-700">
             <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition duration-500 flex flex-col justify-end p-8">
                <span class="text-rajkot-rust text-[10px] font-black uppercase tracking-widest mb-2">Heritage</span>
                <h3 class="text-white text-2xl font-serif font-bold mb-4">Wankaner Royal Estate</h3>
                <a href="#" class="text-white text-xs font-bold uppercase tracking-widest flex items-center gap-2 hover:text-rajkot-rust transition">
                   View Project Details <i class="bi bi-arrow-right"></i>
                </a>
             </div>
          </div>
       </div>

       <!-- Project 3 -->
       <div class="group relative bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-2xl transition duration-500">
          <div class="aspect-[4/5] overflow-hidden">
             <img src="https://images.unsplash.com/photo-1541888946425-d81bb19480c5?auto=format&fit=crop&q=80&w=800" alt="Industrial Shed Design" class="w-full h-full object-cover group-hover:scale-110 transition duration-700">
             <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-0 group-hover:opacity-100 transition duration-500 flex flex-col justify-end p-8">
                <span class="text-rajkot-rust text-[10px] font-black uppercase tracking-widest mb-2">Industrial</span>
                <h3 class="text-white text-2xl font-serif font-bold mb-4">Metoda GIDC Plant-4</h3>
                <a href="#" class="text-white text-xs font-bold uppercase tracking-widest flex items-center gap-2 hover:text-rajkot-rust transition">
                   View Project Details <i class="bi bi-arrow-right"></i>
                </a>
             </div>
          </div>
       </div>
    </div>

    <!-- CTA Section -->
    <div class="mt-24 bg-foundation-grey p-12 rounded-3xl relative overflow-hidden text-center">
       <div class="absolute -top-24 -right-24 w-64 h-64 bg-rajkot-rust rounded-full blur-[100px] opacity-20"></div>
       <h2 class="text-3xl font-serif font-bold text-white mb-6">Have a vision for your next project?</h2>
       <p class="text-gray-400 mb-8 max-w-lg mx-auto italic">"Architecture should speak of its time and place, but yearn for timelessness."</p>
       <a href="contact.php" class="inline-flex items-center gap-3 px-10 py-4 bg-rajkot-rust text-white font-bold rounded-full hover:bg-red-800 transition shadow-xl shadow-red-900/40 uppercase tracking-widest text-sm">
          Discuss Project <i class="bi bi-chat-dots-fill"></i>
       </a>
    </div>
  </main>

  <?php require_once __DIR__ . '/../common/footer.php'; ?>
</body>
</html>
