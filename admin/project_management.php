<?php
// Project Management (Redesigned UI)
session_start();
require_once __DIR__ . '/../includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Project Management | Ripal Design</title>
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
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
      <div>
        <h1 class="text-3xl font-serif font-bold text-rajkot-rust">Project Management</h1>
        <p class="text-gray-500 mt-1">Oversee and manage architectural and construction projects.</p>
      </div>
      <div class="mt-4 md:mt-0 flex gap-3">
        <button class="bg-white text-foundation-grey border border-gray-200 px-4 py-2 rounded-md hover:bg-gray-50 transition shadow-sm font-medium flex items-center gap-2 text-sm">
          <i class="bi bi-download"></i> Export Report
        </button>
        <button class="bg-rajkot-rust text-white px-6 py-2 rounded-md hover:bg-opacity-90 transition shadow-sm font-medium flex items-center gap-2">
          <i class="bi bi-plus-circle"></i> Create New Project
        </button>
      </div>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap items-center gap-4 mb-8">
      <div class="flex items-center gap-2">
        <span class="text-sm font-medium text-gray-500">Filter by City:</span>
        <div class="flex gap-2">
          <button class="px-3 py-1 bg-rajkot-rust text-white rounded-full text-xs font-medium">All</button>
          <button class="px-3 py-1 bg-white text-gray-600 border border-gray-200 rounded-full text-xs font-medium hover:border-rajkot-rust transition">Rajkot</button>
          <button class="px-3 py-1 bg-white text-gray-600 border border-gray-200 rounded-full text-xs font-medium hover:border-rajkot-rust transition">Jam Khambhalia</button>
        </div>
      </div>
      <div class="flex items-center gap-2 ml-auto">
        <div class="relative">
          <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
            <i class="bi bi-filter"></i>
          </span>
          <select class="pl-8 pr-4 py-1.5 border border-gray-200 rounded-md focus:outline-none focus:ring-1 focus:ring-rajkot-rust text-sm bg-white">
            <option>All Statuses</option>
            <option>Design</option>
            <option>Approval</option>
            <option>Construction</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Project Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
      <!-- Project Card 1 -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition group">
        <div class="h-48 bg-gray-200 relative overflow-hidden">
          <img src="https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&q=80&w=800" alt="RMC Smart City Project" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
          <div class="absolute top-4 left-4">
            <span class="px-3 py-1 bg-green-600 text-white text-xs font-bold rounded-full shadow-sm uppercase tracking-wider">Construction</span>
          </div>
        </div>
        <div class="p-6">
          <div class="flex justify-between items-start mb-2">
            <h3 class="text-xl font-bold text-foundation-grey">RMC Smart City Plaza</h3>
            <span class="text-xs font-medium text-gray-400">ID: PRJ-2024-001</span>
          </div>
          <p class="text-sm text-gray-500 mb-6 flex items-center gap-1">
            <i class="bi bi-geo-alt"></i> Rajkot, Gujarat
          </p>
          
          <div class="mb-6">
            <div class="flex justify-between items-center mb-1 text-xs">
              <span class="font-medium text-gray-700">Project Progress</span>
              <span class="font-bold text-rajkot-rust">72%</span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-2">
              <div class="bg-rajkot-rust h-2 rounded-full" style="width: 72%"></div>
            </div>
          </div>

          <div class="flex items-center justify-between border-t border-gray-50 pt-4">
            <div class="flex -space-x-2">
              <div class="w-8 h-8 rounded-full bg-rajkot-rust border-2 border-white flex items-center justify-center text-[10px] text-white font-bold" title="Lead Architect">AV</div>
              <div class="w-8 h-8 rounded-full bg-gray-300 border-2 border-white flex items-center justify-center text-[10px] text-gray-600 font-bold">JD</div>
              <div class="w-8 h-8 rounded-full bg-gray-200 border-2 border-white flex items-center justify-center text-[10px] text-gray-500 font-bold">+3</div>
            </div>
            <a href="project_details.php?id=1" class="text-rajkot-rust text-sm font-bold flex items-center gap-1 hover:gap-2 transition-all">
              Manage Project <i class="bi bi-arrow-right"></i>
            </a>
          </div>
        </div>
      </div>

      <!-- Project Card 2 -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition group">
        <div class="h-48 bg-gray-200 relative overflow-hidden">
          <img src="https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&q=80&w=800" alt="Saurashtra Heritage Villa" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
          <div class="absolute top-4 left-4">
             <span class="px-3 py-1 bg-amber-500 text-white text-xs font-bold rounded-full shadow-sm uppercase tracking-wider">Design</span>
          </div>
        </div>
        <div class="p-6">
          <div class="flex justify-between items-start mb-2">
            <h3 class="text-xl font-bold text-foundation-grey">Saurashtra Heritage Villa</h3>
            <span class="text-xs font-medium text-gray-400">ID: PRJ-2024-008</span>
          </div>
          <p class="text-sm text-gray-500 mb-6 flex items-center gap-1">
            <i class="bi bi-geo-alt"></i> Jam Khambhalia
          </p>
          
          <div class="mb-6">
            <div class="flex justify-between items-center mb-1 text-xs">
              <span class="font-medium text-gray-700">Project Progress</span>
              <span class="font-bold text-rajkot-rust">15%</span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-2">
              <div class="bg-rajkot-rust h-2 rounded-full" style="width: 15%"></div>
            </div>
          </div>

          <div class="flex items-center justify-between border-t border-gray-50 pt-4">
            <div class="flex -space-x-2">
              <div class="w-8 h-8 rounded-full bg-rajkot-rust border-2 border-white flex items-center justify-center text-[10px] text-white font-bold">AV</div>
              <div class="w-8 h-8 rounded-full bg-gray-200 border-2 border-white flex items-center justify-center text-[10px] text-gray-500 font-bold">+1</div>
            </div>
            <a href="project_details.php?id=8" class="text-rajkot-rust text-sm font-bold flex items-center gap-1 hover:gap-2 transition-all">
              Manage Project <i class="bi bi-arrow-right"></i>
            </a>
          </div>
        </div>
      </div>

      <!-- Project Card 3 -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition group">
        <div class="h-48 bg-gray-200 relative overflow-hidden flex items-center justify-center border-b border-gray-100 bg-striped">
          <div class="text-center p-8">
            <i class="bi bi-plus-circle text-4xl text-gray-300"></i>
            <p class="text-gray-400 mt-2 font-medium">Add New Project</p>
          </div>
          <button class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" aria-label="Add new project"></button>
        </div>
        <div class="p-6 h-full flex flex-col justify-center">
            <p class="text-sm text-center text-gray-400 italic">Start a new venture for Ripal Design</p>
        </div>
      </div>
    </div>
  </main>

  <style>
    .bg-striped {
      background-image: repeating-linear-gradient(45deg, #f9fafb, #f9fafb 10px, #f3f4f6 10px, #f3f4f6 20px);
    }
  </style>

  <?php require_once __DIR__ . '/../common/footer.php'; ?>
</body>
</html>