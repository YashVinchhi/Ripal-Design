<?php
// Leave Management (Redesigned UI)
session_start();
require_once __DIR__ . '/../includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Leave Management | Ripal Design</title>
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
      
      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead class="bg-gray-50 border-b border-gray-100">
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
            <tr class="hover:bg-gray-50 transition">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center font-bold text-xs">BK</div>
                  <div>
                    <p class="font-medium text-foundation-grey text-sm">Bhavin Karia</p>
                    <p class="text-[10px] text-gray-400">Structural Engineer</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                 <span class="text-xs font-medium text-gray-600 px-2 py-0.5 bg-gray-100 rounded border border-gray-200">Vacation</span>
              </td>
              <td class="px-6 py-4">
                <p class="text-xs text-foundation-grey font-medium">Feb 20 - Feb 24</p>
                <p class="text-[10px] text-gray-400 italic">5 days</p>
              </td>
              <td class="px-6 py-4">
                <p class="text-xs text-gray-500 max-w-xs truncate">Family wedding in Jamnagar. Already discussed with the site supervisor.</p>
              </td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-50 text-amber-600 border border-amber-100">
                  <i class="bi bi-clock-history"></i> Pending
                </span>
              </td>
              <td class="px-6 py-4 text-right">
                <div class="flex justify-end gap-2">
                  <button onclick="handleLeaveAction('<?php echo addslashes($leave['name'] ?? 'Bhavin Karia'); ?>', 'approved')" class="w-8 h-8 rounded bg-green-600 text-white flex items-center justify-center shadow-sm hover:bg-green-700 transition" title="Approve">
                    <i class="bi bi-check-lg"></i>
                  </button>
                  <button onclick="handleLeaveAction('<?php echo addslashes($leave['name'] ?? 'Bhavin Karia'); ?>', 'rejected')" class="w-8 h-8 rounded bg-rajkot-rust text-white flex items-center justify-center shadow-sm hover:bg-red-800 transition" title="Reject">
                    <i class="bi bi-x-lg"></i>
                  </button>
                </div>
              </td>
            </tr>

            <!-- Row 2: Approved -->
            <tr class="hover:bg-gray-50 transition">
              <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center font-bold text-xs">MV</div>
                  <div>
                    <p class="font-medium text-foundation-grey text-sm">Meera Vora</p>
                    <p class="text-[10px] text-gray-400">Architectural Assistant</p>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                 <span class="text-xs font-medium text-gray-600 px-2 py-0.5 bg-gray-100 rounded border border-gray-200">Casual</span>
              </td>
              <td class="px-6 py-4">
                <p class="text-xs text-foundation-grey font-medium">Feb 18</p>
                <p class="text-[10px] text-gray-400 italic">1 day</p>
              </td>
              <td class="px-6 py-4">
                <p class="text-xs text-gray-500 max-w-xs truncate">Personal work at RMC office.</p>
              </td>
              <td class="px-6 py-4">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-green-50 text-green-700 border border-green-100">
                   <i class="bi bi-check-circle-fill"></i> Approved
                </span>
              </td>
              <td class="px-6 py-4 text-right">
                <button class="text-gray-400 hover:text-gray-600 transition">
                   <i class="bi bi-three-dots-vertical"></i>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </main>

  <?php require_once __DIR__ . '/../common/footer.php'; ?>
    <script>
        function handleLeaveAction(employeeName, action) {
            const verb = action === 'approved' ? 'Authorize' : 'Decline';
            const color = action === 'approved' ? 'bg-green-600' : 'bg-rajkot-rust';
            
            if (confirm(`Are you sure you want to ${verb} the leave request for ${employeeName}?`)) {
                // Simulated status update
                const btn = event.currentTarget;
                const row = btn.closest('tr');
                const statusCell = row.cells[4];
                
                if (statusCell) {
                    statusCell.innerHTML = `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold uppercase ${action === 'approved' ? 'bg-green-50 text-green-700 border border-green-100' : 'bg-red-50 text-red-700 border border-red-100'}">
                        <i class="bi ${action === 'approved' ? 'bi-check-circle-fill' : 'bi-x-circle-fill'}"></i> ${action.toUpperCase()}
                    </span>`;
                }
                
                // Feedback notification
                const notification = document.createElement('div');
                notification.className = `fixed bottom-8 right-8 ${color} text-white px-8 py-4 shadow-2xl z-50 animate-fade-in`;
                notification.innerHTML = `<p class="text-[10px] font-bold uppercase tracking-widest mb-1">Leave Registry Updated</p><p class="text-sm">Request for <b>${employeeName}</b> has been ${action}.</p>`;
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.classList.add('opacity-0', 'transition-opacity', 'duration-500');
                    setTimeout(() => notification.remove(), 500);
                }, 3000);
                
                // Remove action buttons
                const actionContainer = row.querySelector('.flex.justify-end.gap-2');
                if (actionContainer) {
                    actionContainer.innerHTML = `<span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest italic">Processed</span>`;
                }
            }
        }
    </script>
</body>
</html>