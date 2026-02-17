<?php
// User Management (Redesigned UI)
session_start();
require_once __DIR__ . '/../includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>User Management | Ripal Design</title>
  <!-- Tailwind CSS CDN -->
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
        <h1 class="text-3xl font-serif font-bold text-rajkot-rust">User Management</h1>
        <p class="text-gray-500 mt-1">Admin interface for managing user access and roles.</p>
      </div>
      <div class="mt-4 md:mt-0">
        <button class="bg-rajkot-rust text-white px-6 py-2 rounded-md hover:bg-opacity-90 transition shadow-sm font-medium flex items-center gap-2">
          <i class="bi bi-person-plus text-lg"></i>
          Add New User
        </button>
      </div>
    </div>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
      <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
        <p class="text-sm text-gray-500 uppercase tracking-wider font-semibold">Total Users</p>
        <p class="text-2xl font-bold text-foundation-grey mt-1">124</p>
      </div>
      <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 border-l-4 border-l-rajkot-rust">
        <p class="text-sm text-gray-500 uppercase tracking-wider font-semibold">Active Clients</p>
        <p class="text-2xl font-bold text-foundation-grey mt-1">42</p>
      </div>
      <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 border-l-4 border-l-green-600">
        <p class="text-sm text-gray-500 uppercase tracking-wider font-semibold">On-Site Workers</p>
        <p class="text-2xl font-bold text-foundation-grey mt-1">68</p>
      </div>
      <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 border-l-4 border-l-amber-500">
        <p class="text-sm text-gray-500 uppercase tracking-wider font-semibold">Employees</p>
        <p class="text-2xl font-bold text-foundation-grey mt-1">14</p>
      </div>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white p-4 rounded-t-lg shadow-sm border border-gray-100 flex flex-col md:flex-row gap-4 items-center justify-between">
      <div class="relative w-full md:w-96">
        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
          <i class="bi bi-search"></i>
        </span>
        <input type="text" placeholder="Search users by name or email..." class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-md focus:outline-none focus:ring-2 focus:ring-rajkot-rust focus:border-transparent text-sm">
      </div>
      <div class="flex items-center gap-3 w-full md:w-auto">
        <select class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-md focus:ring-rajkot-rust focus:border-rajkot-rust block p-2">
          <option selected>All Roles</option>
          <option value="Admin">Admin</option>
          <option value="Employee">Employee</option>
          <option value="Client">Client</option>
          <option value="Worker">Worker</option>
        </select>
        <select class="bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-md focus:ring-rajkot-rust focus:border-rajkot-rust block p-2">
          <option selected>All Status</option>
          <option value="Active">Active</option>
          <option value="Inactive">Inactive</option>
        </select>
      </div>
    </div>

    <!-- Table -->
    <div class="bg-white shadow-sm border-x border-b border-gray-100 rounded-b-lg overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-500">User</th>
              <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-500">Role</th>
              <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
              <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-500">Last Active</th>
              <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-500 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <!-- Sample Rows -->
            <tr class="hover:bg-gray-50 transition">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-full bg-rajkot-rust text-white flex items-center justify-center font-bold">AV</div>
                  <div>
                    <p class="font-medium text-foundation-grey">Ashish Vinchhi</p>
                    <p class="text-xs text-gray-500">ashish@ripaldesign.in</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <span class="px-2 py-1 text-xs font-medium rounded-full bg-slate-100 text-slate-700 border border-slate-200">Admin</span>
              </td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 border border-green-200">
                  <span class="w-1.5 h-1.5 rounded-full bg-green-600"></span> Active
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-500">Just now</td>
              <td class="px-6 py-4 text-right">
                <div class="flex justify-end gap-2">
                  <button class="p-1.5 text-gray-500 hover:text-rajkot-rust transition" title="Edit User">
                    <i class="bi bi-pencil-square text-lg"></i>
                  </button>
                  <button class="p-1.5 text-gray-500 hover:text-red-600 transition" title="Deactivate User">
                    <i class="bi bi-person-x text-lg"></i>
                  </button>
                </div>
              </td>
            </tr>
            <tr class="hover:bg-gray-50 transition">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center font-bold text-sm">JD</div>
                  <div>
                    <p class="font-medium text-foundation-grey">John Doe</p>
                    <p class="text-xs text-gray-500">john.doe@client.com</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700 border border-blue-200">Client</span>
              </td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 border border-green-200">
                  <span class="w-1.5 h-1.5 rounded-full bg-green-600"></span> Active
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-500">2 hours ago</td>
              <td class="px-6 py-4 text-right">
                <div class="flex justify-end gap-2">
                  <button class="p-1.5 text-gray-500 hover:text-rajkot-rust transition" title="Edit User">
                    <i class="bi bi-pencil-square text-lg"></i>
                  </button>
                  <button class="p-1.5 text-gray-500 hover:text-red-600 transition" title="Deactivate User">
                    <i class="bi bi-person-x text-lg"></i>
                  </button>
                </div>
              </td>
            </tr>
            <tr class="hover:bg-gray-50 transition">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center font-bold text-sm">RS</div>
                  <div>
                    <p class="font-medium text-foundation-grey">Rajesh Sharma</p>
                    <p class="text-xs text-gray-500">rajesh.s@site.in</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <span class="px-2 py-1 text-xs font-medium rounded-full bg-emerald-100 text-emerald-700 border border-emerald-200">Worker</span>
              </td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                  <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Inactive
                </span>
              </td>
              <td class="px-6 py-4 text-sm text-gray-500">3 days ago</td>
              <td class="px-6 py-4 text-right">
                <div class="flex justify-end gap-2">
                  <button class="p-1.5 text-gray-500 hover:text-rajkot-rust transition" title="Edit User">
                    <i class="bi bi-pencil-square text-lg"></i>
                  </button>
                  <button class="p-1.5 text-green-600 hover:text-green-700 transition" title="Activate User">
                    <i class="bi bi-person-check text-lg"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <!-- Pagination -->
      <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex items-center justify-between">
        <p class="text-xs text-gray-500">Showing 3 of 124 users</p>
        <div class="flex gap-2">
          <button class="px-3 py-1 border border-gray-200 rounded text-xs font-medium text-gray-600 bg-white hover:bg-gray-50 disabled:opacity-50" disabled>Previous</button>
          <button class="px-3 py-1 border border-gray-200 rounded text-xs font-medium text-gray-600 bg-white hover:bg-gray-50">Next</button>
        </div>
      </div>
    </div>
  </main>

  <?php require_once __DIR__ . '/../common/footer.php'; ?>
</body>
</html>