<?php
// Ensure session and constants are loaded first
require_once __DIR__ . '/../includes/init.php';
?>
<!doctype html>
<html lang="en" class="bg-canvas-white">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Worker Dashboard | Ripal Design</title>
    <?php $HEADER_MODE = 'dashboard'; require_once __DIR__ . '/../Common/header.php'; ?>
</head>
<body class="font-sans text-foundation-grey bg-canvas-white">

<?php
// Sample placeholder data — replace with real queries later
$projects = [];
if (isset($pdo) && $pdo instanceof PDO) {
    try {
      $stmt = $pdo->query('SELECT id, name, status, COALESCE(progress,0) AS progress, COALESCE(due,\'1970-01-01\') AS due, COALESCE(location,\'\') AS location, latitude, longitude FROM projects ORDER BY id DESC LIMIT 200');
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Worker dashboard projects load failed: '.$e->getMessage());
        $projects = [];
    }
}
if (empty($projects)) {
  $projects = [
    [
      'id' => 101,
      'name' => 'Shanti Sadan',
      'status' => 'ongoing',
      'progress' => 45,
      'due' => '2026-03-15',
      'location' => 'Jasal Complex, Nanavati Chowk, Rajkot',
      'latitude' => '22.3039',
      'longitude' => '70.8022'
    ],
    [
      'id' => 102,
      'name' => 'Sukh Sagar (Nyari Dam)',
      'status' => 'overdue',
      'progress' => 70,
      'due' => '2026-01-20',
      'location' => 'Nyari Dam Road, Rajkot'
    ]
  ];
}

$counts = array_count_values(array_map(function($x){return $x['status'];}, $projects));
?>

<div class="min-h-screen flex flex-col">
    <!-- Unified Dark Portal Header -->
    <header class="bg-foundation-grey text-white pt-24 pb-12 px-4 shadow-lg">
        <div class="max-w-4xl mx-auto flex justify-between items-center">
            <div>
                <h1 class="text-4xl font-serif font-bold">Workforce Portal</h1>
                <p class="text-gray-400 text-sm mt-1 flex items-center gap-1">
                    <i data-lucide="shield-check" class="w-4 h-4 text-approval-green"></i> 
                    On-site Supervisor Mode
                </p>
            </div>
            <div class="w-12 h-12 bg-rajkot-rust rounded-full flex items-center justify-center font-bold text-lg shadow-inner">
                <?php echo strtoupper(substr($_SESSION['user'] ?? 'WD', 0, 2)); ?>
            </div>
        </div>
    </header>

    <main class="flex-grow p-4 max-w-4xl mx-auto w-full space-y-6">
        <!-- Quick Glance Stats -->
        <div class="grid grid-cols-2 gap-3">
            <div class="bg-white p-4 border-l-4 border-rajkot-rust shadow-premium">
                <span class="text-gray-400 text-xs uppercase font-bold">Active Jobs</span>
                <span class="block text-2xl font-bold mt-1"><?php echo intval($counts['ongoing'] ?? 0); ?></span>
            </div>
            <div class="bg-white p-4 border-l-4 border-pending-amber shadow-premium">
                <span class="text-gray-400 text-xs uppercase font-bold">Overdue</span>
                <span class="block text-2xl font-bold mt-1 text-red-600"><?php echo intval($counts['overdue'] ?? 0); ?></span>
            </div>
        </div>

        <!-- Project List -->
        <h2 class="text-lg font-bold text-foundation-grey flex items-center gap-2 px-1">
            <i data-lucide="briefcase" class="w-5 h-5 text-rajkot-rust"></i>
            Assigned Projects
        </h2>

        <div class="space-y-4">
            <?php foreach ($projects as $p): ?>
            <div class="bg-white border border-gray-200 shadow-premium overflow-hidden rounded-sm">
                <div class="p-5">
                    <div class="flex justify-between items-start mb-3">
                        <?php 
                        $statusClass = $p['status'] === 'overdue' ? 'bg-red-100 text-red-700 border-red-200' : 'bg-green-100 text-green-700 border-green-200';
                        ?>
                        <span class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-widest border <?php echo $statusClass; ?>">
                            <?php echo str_replace('-', ' ', $p['status']); ?>
                        </span>
                        <span class="text-xs text-gray-400 font-mono">#JOB-<?php echo $p['id']; ?></span>
                    </div>

                    <h3 class="text-lg font-bold text-foundation-grey leading-tight mb-4">
                        <?php echo htmlspecialchars($p['name']); ?>
                    </h3>

                    <div class="space-y-4">
                        <!-- Progress -->
                        <div>
                            <div class="flex justify-between items-end mb-1">
                                <span class="text-xs font-bold text-gray-500 uppercase">Completion</span>
                                <span class="text-xs font-bold text-rajkot-rust"><?php echo intval($p['progress']); ?>%</span>
                            </div>
                            <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                                <div class="bg-rajkot-rust h-full" style="width: <?php echo intval($p['progress']); ?>%"></div>
                            </div>
                        </div>

                        <!-- Location & Directions -->
                        <div class="flex items-center justify-between py-3 border-y border-gray-50">
                            <div class="flex items-center text-sm text-gray-600 truncate mr-4">
                                <i data-lucide="map-pin" class="w-4 h-4 mr-2 shrink-0 text-gray-400"></i>
                                <span class="truncate"><?php echo htmlspecialchars($p['location']); ?></span>
                            </div>
                            <?php if (!empty($p['latitude'])): ?>
                                <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo urlencode($p['latitude'] . ',' . $p['longitude']); ?>" target="_blank" class="shrink-0 text-rajkot-rust hover:underline text-xs font-bold flex items-center gap-1">
                                    DIRECTIONS <i data-lucide="external-link" class="w-3 h-3"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Large Mobile Action Buttons -->
                <div class="grid grid-cols-2 bg-gray-50 border-t border-gray-200">
                    <a href="project_details.php?id=<?php echo $p['id']; ?>#drawings" class="flex flex-col items-center justify-center py-4 border-r border-gray-200 hover:bg-white transition-colors active:bg-gray-100 h-20">
                        <i data-lucide="file-text" class="w-6 h-6 text-slate-accent mb-1"></i>
                        <span class="text-[10px] font-bold uppercase tracking-wider">Drawings</span>
                    </a>
                    <a href="project_details.php?id=<?php echo $p['id']; ?>" class="flex flex-col items-center justify-center py-4 hover:bg-white transition-colors active:bg-gray-100 h-20">
                        <i data-lucide="layout-grid" class="w-6 h-6 text-rajkot-rust mb-1"></i>
                        <span class="text-[10px] font-bold uppercase tracking-wider">Open Job</span>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Material Request Quick Action -->
        <div class="pt-6">
            <button class="w-full bg-slate-accent text-white py-5 rounded-lg font-bold flex items-center justify-center gap-3 shadow-lg active:scale-[0.98] transition-all">
                <i data-lucide="truck" class="w-6 h-6 text-pending-amber"></i>
                MATERIAL REQUEST
            </button>
        </div>
    </main>

    <?php require_once __DIR__ . '/../Common/footer.php'; ?>
</div>

</body>
</html>
