<?php
$requireInit = true;
require_once __DIR__ . '/../includes/init.php';
$user = $_SESSION['user'] ?? 'worker01';

// Sample assigned projects (UI only)
$projects = [
  ['id'=>101,'name'=>'Renovation — Oak Street Residence','status'=>'ongoing','progress'=>45,'due'=>'2026-03-15'],
  ['id'=>103,'name'=>'Workshop Materials Procurement','status'=>'on-hold','progress'=>20,'due'=>'2026-04-30'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Assigned Projects — <?php echo htmlspecialchars($user); ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
  <style type="text/tailwindcss">
    :root {
      --maroon: #731209;
      --bone: #F5F2ED;
      --charcoal: #1A1A1A;
      --muted-gold: #A68966;
    }
    @theme {
      --font-display: "Playfair Display", serif;
      --font-sans: "Inter", sans-serif;
    }
    body {
      font-family: 'Inter', sans-serif;
      background-color: var(--bone);
      color: var(--charcoal);
    }
    .serif-font {
      font-family: 'Playfair Display', serif;
    }
    .custom-scrollbar::-webkit-scrollbar {
      width: 4px;
    }
    .custom-scrollbar::-webkit-scrollbar-track {
      background: #e5e7eb;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
      background: var(--muted-gold);
      border-radius: 10px;
    }
  </style>
  <?php if (function_exists('render_head_assets')) { render_head_assets(); } ?>
</head>
<body class="min-h-screen flex flex-col">
  <?php $HEADER_MODE = 'dashboard'; require_once __DIR__ . '/../Common/header.php'; ?>
  
  <main class="flex-grow max-w-[1440px] mx-auto w-full px-6 py-8">
    <!-- Page Header with Tabs -->
    <div class="mb-8 border-b border-gray-200">
      <div class="flex justify-between items-end">
        <div>
          <h1 class="serif-font text-4xl text-[var(--charcoal)] mb-2">Assigned Projects</h1>
          <p class="text-sm text-gray-500 mb-6 uppercase tracking-widest">Active Studio Assignments</p>
        </div>
        <div class="flex items-center gap-2 mb-[-1px]">
          <button class="px-6 py-3 border-b-2 border-[var(--maroon)] text-[var(--maroon)] font-semibold text-sm tracking-wide">Overview</button>
          <button class="px-6 py-3 border-b-2 border-transparent text-gray-400 hover:text-[var(--charcoal)] font-medium text-sm transition-all tracking-wide">Tasks</button>
          <button class="px-6 py-3 border-b-2 border-transparent text-gray-400 hover:text-[var(--charcoal)] font-medium text-sm transition-all tracking-wide">Documents</button>
          <button class="px-6 py-3 border-b-2 border-transparent text-gray-400 hover:text-[var(--charcoal)] font-medium text-sm transition-all tracking-wide">Timeline</button>
        </div>
      </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-8">
      <!-- Main Content Area -->
      <div class="lg:w-2/3 space-y-6">
        <?php foreach($projects as $p): 
          $statusClass = $p['status'] === 'ongoing' ? 'bg-green-50 text-green-700 border-green-100' : 'bg-amber-50 text-amber-700 border-amber-100';
          $statusText = ucfirst(str_replace('-', ' ', $p['status']));
          $projectImages = [
            101 => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDqgKavuMw8O9YooDp4LHkMAnMmFEUJaKB6UaYkyJSOhsptzHMGm1ALfK-ZF6YUiEUKb9-Tzr6GPqH5ZBAVKJDd-NduQiwTNc4B1f2yaZ_C6r8aYgPjANW1kNi3HXdDYwBYkxxu_FBgxMbvOlRJMt2NEf4yQYPuqG7IAc_VgnKExtxIoqWu5g7owVDwPTW3Pa7PDWouMF-li6NhT0m1BoLcPfWek1tb76b4aJO0qpLUsUamdIhOT309iulbyCn-X-u6zn9LvVEqfMs',
            103 => 'https://lh3.googleusercontent.com/aida-public/AB6AXuA4NIODPjRM-BEEWxROQ8AS4nEM53PV894L9r2WA5mgGKjMCRmRKz-9KUZAxpIQ7d_u2eOfy5k5X87y6IsVHBuHM-2Zdb6RNlUCkXXb1fTFp0-UBIvsMnnBzUb1viEyyfqQdcCLkZYPxtE9qnmi2A4I9ZzQEh3wxeDyjWTwegFw7hnICb39JDBiyvaIlkXWM6YKbYLL8jfAySdsveS2sNe216LREr4gDZ0AGmHfzJxqY9MRiaFjeRjCet3nwhq-QXmT5Gtrq1T3p5M'
          ];
        ?>
        <div class="bg-white border border-gray-100 shadow-sm flex flex-col md:flex-row overflow-hidden group">
          <div class="md:w-48 bg-gray-50 flex-shrink-0 relative overflow-hidden">
            <img alt="Project Site Map" class="w-full h-full object-cover opacity-80 group-hover:scale-105 transition-transform duration-700" src="<?php echo $projectImages[$p['id']] ?? 'https://via.placeholder.com/300x400'; ?>">
            <div class="absolute inset-0 bg-[var(--maroon)]/10 mix-blend-multiply"></div>
            <div class="absolute top-2 left-2 bg-white/90 px-2 py-1 text-[9px] font-bold uppercase tracking-tighter border border-gray-200">Site Plan v2.1</div>
          </div>
          <div class="flex-grow p-6 flex flex-col">
            <div class="flex justify-between items-start mb-2">
              <div>
                <h3 class="serif-font text-2xl text-[var(--charcoal)] group-hover:text-[var(--maroon)] transition-colors"><?php echo htmlspecialchars($p['name']); ?></h3>
                <p class="text-xs text-[var(--muted-gold)] font-semibold uppercase tracking-widest mt-1">
                  <?php echo $p['status'] === 'ongoing' ? 'Interior Detailing Phase' : 'Structural Review'; ?>
                </p>
              </div>
              <span class="px-3 py-1 <?php echo $statusClass; ?> text-[10px] font-bold uppercase tracking-widest border"><?php echo $statusText; ?></span>
            </div>
            <div class="grid grid-cols-2 gap-4 mt-4 mb-6">
              <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-[var(--muted-gold)] text-lg">event</span>
                <div>
                  <p class="text-[10px] text-gray-400 uppercase font-bold tracking-tighter">Deadline</p>
                  <p class="text-xs font-medium"><?php echo date('M d, Y', strtotime($p['due'])); ?></p>
                </div>
              </div>
              <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-[var(--muted-gold)] text-lg">pending_actions</span>
                <div>
                  <p class="text-[10px] text-gray-400 uppercase font-bold tracking-tighter">Deliverables</p>
                  <p class="text-xs font-medium"><?php echo $p['status'] === 'ongoing' ? '4 Drawings Pending' : '1 Approval Required'; ?></p>
                </div>
              </div>
            </div>
            <div class="mt-auto space-y-3">
              <div class="flex justify-between text-[11px] font-bold uppercase tracking-tighter">
                <span class="text-gray-400">Project Completion</span>
                <span class="text-[var(--maroon)]"><?php echo intval($p['progress']); ?>%</span>
              </div>
              <div class="w-full h-1 bg-gray-100">
                <div class="bg-[var(--maroon)] h-full" style="width: <?php echo intval($p['progress']); ?>%;"></div>
              </div>
              <div class="flex justify-end gap-3 pt-2">
                <a href="../dashboard/project_details.php?id=<?php echo intval($p['id']); ?>#drawings" class="text-xs font-bold text-[var(--muted-gold)] hover:text-[var(--maroon)] uppercase tracking-widest flex items-center gap-1 transition-colors">
                  <span class="material-symbols-outlined text-sm">map</span>
                  Drawings
                </a>
                <a href="../dashboard/project_details.php?id=<?php echo intval($p['id']); ?>" class="px-4 py-2 bg-[var(--maroon)] text-white text-xs font-bold uppercase tracking-[0.2em] hover:bg-[var(--charcoal)] transition-colors">
                  Open Detail View
                </a>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>

        <!-- Request New Assignment Box -->
        <div class="border border-dashed border-gray-300 p-8 flex flex-col items-center justify-center text-center group cursor-pointer hover:border-[var(--muted-gold)] transition-colors">
          <span class="material-symbols-outlined text-4xl text-gray-300 group-hover:text-[var(--muted-gold)] mb-2">add_task</span>
          <p class="text-xs font-bold uppercase tracking-[0.2em] text-gray-400 group-hover:text-[var(--charcoal)] transition-colors">Request New Assignment</p>
        </div>
      </div>

      <!-- Sidebar -->
      <div class="lg:w-1/3 space-y-6">
        <!-- Workload Summary -->
        <div class="bg-white border border-gray-100 p-6 shadow-sm">
          <h4 class="serif-font text-xl mb-4 text-[var(--charcoal)]">Workload Summary</h4>
          <div class="space-y-4">
            <div class="flex justify-between items-center py-2 border-b border-gray-50">
              <span class="text-sm text-gray-500">Active Projects</span>
              <span class="font-bold text-[var(--maroon)]"><?php echo str_pad(count($projects), 2, '0', STR_PAD_LEFT); ?></span>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-gray-50">
              <span class="text-sm text-gray-500">Pending Tasks</span>
              <span class="font-bold text-[var(--maroon)]">12</span>
            </div>
            <div class="flex justify-between items-center py-2 border-b border-gray-50">
              <span class="text-sm text-gray-500">Weekly Hours Logged</span>
              <span class="font-bold text-[var(--maroon)]">32.5</span>
            </div>
          </div>
          <button class="w-full mt-6 py-3 border border-[var(--muted-gold)] text-[var(--muted-gold)] text-xs font-bold uppercase tracking-widest hover:bg-[var(--muted-gold)] hover:text-white transition-all">
            View Detailed Stats
          </button>
        </div>

        <!-- Recent Activity -->
        <div class="bg-[#1A1A1A] text-[var(--bone)] p-6 shadow-xl">
          <div class="flex justify-between items-center mb-6">
            <h4 class="serif-font text-xl">Recent Activity</h4>
            <span class="material-symbols-outlined text-[var(--muted-gold)]">history</span>
          </div>
          <div class="space-y-6 custom-scrollbar max-h-[400px] overflow-y-auto pr-2">
            <div class="flex gap-4">
              <div class="flex-shrink-0 mt-1">
                <div class="w-2 h-2 rounded-full bg-[var(--muted-gold)]"></div>
                <div class="w-[1px] h-full bg-white/10 mx-auto mt-2"></div>
              </div>
              <div class="pb-4">
                <p class="text-xs leading-relaxed">
                  <span class="font-bold text-white">Drawing Approved:</span>
                  Ground Floor Electrical Layout for Renovation Project.
                </p>
                <p class="text-[10px] text-white/40 uppercase tracking-tighter mt-1 italic">2 hours ago</p>
              </div>
            </div>
            <div class="flex gap-4">
              <div class="flex-shrink-0 mt-1">
                <div class="w-2 h-2 rounded-full bg-[var(--maroon)]"></div>
                <div class="w-[1px] h-full bg-white/10 mx-auto mt-2"></div>
              </div>
              <div class="pb-4">
                <p class="text-xs leading-relaxed">
                  <span class="font-bold text-white">Feedback Received:</span>
                  Client requested changes on materials procurement list.
                </p>
                <p class="text-[10px] text-white/40 uppercase tracking-tighter mt-1 italic">Yesterday, 4:15 PM</p>
              </div>
            </div>
            <div class="flex gap-4">
              <div class="flex-shrink-0 mt-1">
                <div class="w-2 h-2 rounded-full border border-white/20"></div>
                <div class="w-[1px] h-full bg-white/10 mx-auto mt-2"></div>
              </div>
              <div class="pb-4">
                <p class="text-xs leading-relaxed">
                  <span class="font-bold text-white">Project Re-assigned:</span>
                  Workshop materials moved to Phase 2 review.
                </p>
                <p class="text-[10px] text-white/40 uppercase tracking-tighter mt-1 italic">Feb 12, 2026</p>
              </div>
            </div>
            <div class="flex gap-4">
              <div class="flex-shrink-0 mt-1">
                <div class="w-2 h-2 rounded-full bg-[var(--muted-gold)]"></div>
              </div>
              <div>
                <p class="text-xs leading-relaxed">
                  <span class="font-bold text-white">Milestone Reached:</span>
                  Site measurements completed for Oak Street Residence.
                </p>
                <p class="text-[10px] text-white/40 uppercase tracking-tighter mt-1 italic">Feb 10, 2026</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
  
  <?php require_once __DIR__ . '/../Common/footer.php'; ?>
</body>
</html>
