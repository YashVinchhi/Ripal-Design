<?php
// Leave Management (Redesigned UI)
session_start();
require_once __DIR__ . '/../includes/init.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Leave Management | Ripal Design</title>
  <link rel="icon" href="data:,">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    function handleLeaveAction(employeeName, action, btn) {
        const verb = action === 'approved' ? 'Authorize' : 'Decline';
        const color = action === 'approved' ? 'bg-green-600' : 'bg-rajkot-rust';
        
        if (confirm(`Are you sure you want to ${verb} the leave request for ${employeeName}?`)) {
            const row = btn.closest('tr');
            const statusCell = row.querySelector('[data-label="Status"]');
            
            if (statusCell) {
                statusCell.innerHTML = `<span class="inline-flex items-center gap-2 px-3 py-1 rounded text-[10px] font-bold uppercase ${action === 'approved' ? 'bg-green-50 text-green-700 border border-green-100' : 'bg-red-50 text-red-700 border border-red-100'} shadow-sm">
                    <span class="w-1.5 h-1.5 rounded-full ${action === 'approved' ? 'bg-green-500' : 'bg-red-500'}"></span> ${action.toUpperCase()}
                </span>`;
            }
            
            const notification = document.createElement('div');
            notification.className = `fixed bottom-8 right-8 ${color} text-white px-8 py-4 shadow-2xl z-50 rounded-lg border-b-4 border-black/20 animate-bounce-in`;
            notification.innerHTML = `<p class="text-[10px] font-bold uppercase tracking-widest mb-1 opacity-70">Registry Updated</p><p class="text-sm"><b>${employeeName}</b> is ${action}.</p>`;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('opacity-0', 'translate-y-4', 'transition-all', 'duration-500');
                setTimeout(() => notification.remove(), 500);
            }, 3000);
            
            const wrapper = row.querySelector('.actions-wrapper');
            if (wrapper) wrapper.innerHTML = `<span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest italic">Processed</span>`;
        }
    }
  </script>
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
  
  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 mt-16 md:mt-20 admin-main">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
      <div>
        <h1 class="text-3xl font-serif font-bold text-rajkot-rust">Leave Management</h1>
        <p class="text-gray-500 mt-1">Review and manage time-off requests from the team.</p>
      </div>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 border-t-4 border-t-amber-500">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-sm text-gray-400 uppercase tracking-wider font-semibold">Pending</p>
            <p class="text-2xl font-bold text-foundation-grey mt-1">12</p>
          </div>
          <div class="p-2 bg-amber-50 rounded-lg text-amber-600">
            <i class="bi bi-hourglass-split text-xl"></i>
          </div>
        </div>
      </div>
      <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 border-t-4 border-t-green-600">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-sm text-gray-400 uppercase tracking-wider font-semibold">Approved</p>
            <p class="text-2xl font-bold text-foundation-grey mt-1">24</p>
          </div>
          <div class="p-2 bg-green-50 rounded-lg text-green-600">
            <i class="bi bi-check-circle text-xl"></i>
          </div>
        </div>
        <p class="text-[10px] text-green-600 font-medium mt-2">This month</p>
      </div>
      <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100 border-t-4 border-t-rajkot-rust">
        <div class="flex justify-between items-start">
          <div>
            <p class="text-sm text-gray-400 uppercase tracking-wider font-semibold">On Leave Today</p>
            <p class="text-2xl font-bold text-foundation-grey mt-1">5</p>
          </div>
          <div class="p-2 bg-red-50 rounded-lg text-rajkot-rust">
            <i class="bi bi-calendar-event text-xl"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Table Container -->
    <div class="bg-white shadow-sm border border-gray-100 rounded-lg overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
        <h2 class="font-bold text-foundation-grey">Recent Requests</h2>
        <div class="flex gap-2">
           <button class="text-xs font-semibold text-rajkot-rust hover:underline underline-offset-4">View All Archive</button>
        </div>
      </div>
      
      <div class="overflow-x-auto pb-4">
        <table class="w-full text-left admin-table">
          <thead class="bg-gray-50 border-b border-gray-100 hidden md:table-header-group">
            <tr>
              <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-500">Employee</th>
              <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-500">Type</th>
              <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-500">Dates</th>
              <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-500">Reason</th>
              <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
              <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-500 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <!-- Row 1: Pending -->
            <tr class="hover:bg-gray-50 transition block md:table-row mb-4 md:mb-0 border md:border-0 rounded-lg md:rounded-none bg-white md:bg-transparent">
              <td class="px-6 py-4 block md:table-cell" data-label="Employee">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center font-bold text-xs">BK</div>
                  <div>
                    <p class="font-medium text-foundation-grey text-sm">Bhavin Karia</p>
                    <p class="text-[10px] text-gray-400">Structural Engineer</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 block md:table-cell" data-label="Type">
                 <span class="text-xs font-medium text-gray-600 px-2 py-0.5 bg-gray-100 rounded border border-gray-200">Vacation</span>
              </td>
              <td class="px-6 py-4 block md:table-cell" data-label="Dates">
                <p class="text-xs text-foundation-grey font-medium">Feb 20 - Feb 24</p>
                <p class="text-[10px] text-gray-400 italic">5 days</p>
              </td>
              <td class="px-6 py-4 block md:table-cell" data-label="Reason">
                <p class="text-xs text-gray-500 max-w-xs truncate md:max-w-none md:whitespace-normal">Family wedding in Jamnagar. Already discussed with the site supervisor.</p>
              </td>
              <td class="px-6 py-4 block md:table-cell" data-label="Status">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-50 text-amber-600 border border-amber-100">
                  <i class="bi bi-clock-history"></i> Pending
                </span>
              </td>
              <td class="px-6 py-4 text-right block md:table-cell" data-label="Actions">
                <div class="actions-wrapper flex flex-row md:justify-end gap-3 mt-4 md:mt-0">
                  <button onclick="handleLeaveAction('Bhavin Karia', 'approved', this)" class="flex-grow md:flex-grow-0 h-12 md:h-9 px-6 md:px-4 rounded bg-green-600 text-white flex items-center justify-center gap-2 shadow-lg shadow-green-900/20 hover:bg-green-700 transition active:scale-95" title="Approve">
                    <i class="bi bi-check-lg text-lg md:text-base"></i> <span class="md:hidden font-bold uppercase tracking-widest text-[10px]">Approve</span>
                  </button>
                  <button onclick="handleLeaveAction('Bhavin Karia', 'rejected', this)" class="flex-grow md:flex-grow-0 h-12 md:h-9 px-6 md:px-4 rounded bg-rajkot-rust text-white flex items-center justify-center gap-2 shadow-lg shadow-red-900/20 hover:bg-red-800 transition active:scale-95" title="Reject">
                    <i class="bi bi-x-lg text-lg md:text-base"></i> <span class="md:hidden font-bold uppercase tracking-widest text-[10px]">Reject</span>
                  </button>
                </div>
              </td>
            </tr>

            <!-- Row 2: Approved -->
            <tr class="hover:bg-gray-50 transition block md:table-row mb-4 md:mb-0 border md:border-0 rounded-lg md:rounded-none bg-white md:bg-transparent">
              <td class="px-6 py-4 block md:table-cell" data-label="Employee">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center font-bold text-xs">MV</div>
                  <div>
                    <p class="font-medium text-foundation-grey text-sm">Meera Vora</p>
                    <p class="text-[10px] text-gray-400">Architectural Assistant</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 block md:table-cell" data-label="Type">
                 <span class="text-xs font-medium text-gray-600 px-2 py-0.5 bg-gray-100 rounded border border-gray-200">Casual</span>
              </td>
              <td class="px-6 py-4 block md:table-cell" data-label="Dates">
                <p class="text-xs text-foundation-grey font-medium">Feb 18</p>
                <p class="text-[10px] text-gray-400 italic">1 day</p>
              </td>
              <td class="px-6 py-4 block md:table-cell" data-label="Reason">
                <p class="text-xs text-gray-500 max-w-xs truncate md:max-w-none md:whitespace-normal">Personal work at RMC office.</p>
              </td>
              <td class="px-6 py-4 block md:table-cell" data-label="Status">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-green-50 text-green-700 border border-green-100">
                   <i class="bi bi-check-circle-fill"></i> Approved
                </span>
              </td>
              <td class="px-6 py-4 text-right block md:table-cell" data-label="Actions">
                <div class="flex justify-start md:justify-end">
                  <button class="text-gray-400 hover:text-gray-600 transition">
                     <i class="bi bi-three-dots-vertical"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <?php require_once __DIR__ . '/../common/footer.php'; ?>
</body>
</html>