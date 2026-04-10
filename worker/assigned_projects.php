<?php
session_start();
require_once __DIR__ . '/../includes/init.php';

$userId = (int)($_SESSION['user_id'] ?? 0);

$projects = [];
if (db_connected()) {
    if ($userId > 0) {
    $projects = db_fetch_all("SELECT p.id, p.name, p.status, COALESCE(p.progress,0) AS progress, p.due, COALESCE(p.location,'') AS location,
      COALESCE(NULLIF(p.address,''), NULLIF(p.location,''), '') AS address
            FROM project_assignments pa
            JOIN projects p ON p.id = pa.project_id
            WHERE pa.worker_id = ?
            ORDER BY pa.assigned_at DESC", [$userId]);
    }

    if (empty($projects)) {
    $projects = db_fetch_all("SELECT p.id, p.name, p.status, COALESCE(p.progress,0) AS progress, p.due, COALESCE(p.location,'') AS location,
      COALESCE(NULLIF(p.address,''), NULLIF(p.location,''), '') AS address
            FROM project_assignments pa
            JOIN projects p ON p.id = pa.project_id
            ORDER BY pa.assigned_at DESC LIMIT 50");
    }
}

$assignedCount = count($projects);
$overdueCount = 0;
foreach ($projects as $p) {
    if (!empty($p['due']) && strtotime((string)$p['due']) < time() && ($p['status'] ?? '') !== 'completed') {
        $overdueCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="bg-canvas-white">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Assigned Projects | Ripal Design</title>
  <?php $HEADER_MODE = 'dashboard'; require_once __DIR__ . '/../Common/header.php'; ?>
</head>
<body class="bg-canvas-white font-sans text-foundation-grey min-h-screen">
  <div class="min-h-screen flex flex-col">
    <header class="bg-foundation-grey text-white pt-24 pb-12 px-4 shadow-lg mb-12 border-b-2 border-rajkot-rust">
      <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-6">
        <div>
          <h1 class="text-4xl font-serif font-bold">Assigned Projects</h1>
          <p class="text-gray-400 mt-2">Projects allocated to your workforce profile.</p>
        </div>
        <div class="flex gap-6">
          <div class="text-center">
            <p class="text-[10px] uppercase tracking-widest text-gray-400">Assigned</p>
            <p class="text-2xl font-bold text-approval-green"><?php echo $assignedCount; ?></p>
          </div>
          <div class="text-center">
            <p class="text-[10px] uppercase tracking-widest text-gray-400">Overdue</p>
            <p class="text-2xl font-bold text-rajkot-rust"><?php echo $overdueCount; ?></p>
          </div>
        </div>
      </div>
    </header>

    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
      <?php if (empty($projects)): ?>
      <div class="bg-white border border-gray-100 shadow-premium p-10 text-center text-gray-500">
        No assigned projects found in database.
      </div>
      <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php foreach ($projects as $p): ?>
        <div class="bg-white border border-gray-100 shadow-premium p-6">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-xl font-serif font-bold"><?php echo htmlspecialchars((string)$p['name']); ?></h3>
            <span class="text-[10px] uppercase tracking-widest px-2 py-1 bg-gray-50 border border-gray-100"><?php echo htmlspecialchars((string)$p['status']); ?></span>
          </div>
          <p class="text-sm text-gray-500 mb-2"><?php echo htmlspecialchars((string)(($p['address'] ?? '') !== '' ? $p['address'] : ($p['location'] ?? 'Location not set'))); ?></p>
          <p class="text-xs text-gray-400 mb-4">Due: <?php echo !empty($p['due']) ? htmlspecialchars((string)$p['due']) : 'N/A'; ?></p>
          <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden mb-4">
            <div class="bg-rajkot-rust h-full" style="width: <?php echo (int)$p['progress']; ?>%"></div>
          </div>
          <?php $directionTarget = (string)(($p['address'] ?? '') !== '' ? $p['address'] : ($p['location'] ?? '')); ?>
          <div class="flex items-center gap-2">
            <a href="project_details.php?id=<?php echo (int)$p['id']; ?>" class="inline-flex items-center justify-center bg-foundation-grey hover:bg-rajkot-rust text-white px-4 py-2 text-[10px] font-bold uppercase tracking-widest transition-all no-underline">Open Workspace</a>
            <?php if ($directionTarget !== ''): ?>
              <a href="https://www.google.com/maps/dir/?api=1&amp;destination=<?php echo urlencode($directionTarget); ?>" target="_blank" class="inline-flex items-center justify-center border border-rajkot-rust text-rajkot-rust hover:bg-rajkot-rust hover:text-white px-4 py-2 text-[10px] font-bold uppercase tracking-widest transition-all no-underline">Get Direction</a>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </main>

    <?php require_once __DIR__ . '/../Common/footer.php'; ?>
  </div>
</body>
</html>

