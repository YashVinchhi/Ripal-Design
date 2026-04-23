<?php
if (!defined('PROJECT_ROOT')) { require_once dirname(__DIR__, 4) . '/app/Core/Bootstrap/init.php'; }
require_once PROJECT_ROOT . '/app/Core/Bootstrap/init.php';

$projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$projectName = 'Project';
$revisions = [];

if (db_connected() && $projectId > 0) {
    $p = db_fetch('SELECT name FROM projects WHERE id = ? LIMIT 1', [$projectId]);
    if ($p) {
        $projectName = (string)$p['name'];
    }

    $revisions = db_fetch_all('SELECT id, name, version, status, file_path, uploaded_at FROM project_drawings WHERE project_id = ? ORDER BY uploaded_at DESC', [$projectId]);
}
?>
<!DOCTYPE html>
<html lang="en" class="bg-canvas-white">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Revision Archive | Ripal Design</title>
  <?php $HEADER_MODE = 'dashboard'; require_once PROJECT_ROOT . '/Common/header.php'; ?>
</head>
<body class="bg-canvas-white font-sans text-foundation-grey min-h-screen">
  <div class="min-h-screen flex flex-col">
    <header class="bg-foundation-grey text-white pt-24 pb-12 px-4 shadow-lg mb-12 border-b-2 border-rajkot-rust">
      <div class="max-w-7xl mx-auto">
        <h1 class="text-4xl font-serif font-bold">Revision Archive</h1>
        <p class="text-gray-400 mt-2">Timeline of drawing revisions for <?php echo htmlspecialchars($projectName); ?>.</p>
      </div>
    </header>

    <main class="flex-grow max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
      <?php if (empty($revisions)): ?>
      <div class="bg-white border border-gray-100 shadow-premium p-10 text-center text-gray-500">No revision records found.</div>
      <?php else: ?>
      <div class="space-y-4">
        <?php foreach ($revisions as $r): ?>
        <?php $status = strtolower((string)($r['status'] ?? 'under review')); ?>
        <div class="bg-white border border-gray-100 shadow-premium p-6">
          <div class="flex items-center justify-between gap-4 flex-wrap">
            <div>
              <h3 class="text-lg font-serif font-bold"><?php echo htmlspecialchars((string)$r['name']); ?></h3>
              <p class="text-xs text-gray-400 mt-1">Version: <?php echo htmlspecialchars((string)($r['version'] ?: 'v1')); ?> ï¿½ <?php echo date('M d, Y H:i', strtotime((string)$r['uploaded_at'])); ?></p>
            </div>
            <span class="text-[10px] uppercase tracking-widest px-2 py-1 border border-gray-100 bg-gray-50"><?php echo htmlspecialchars($status); ?></span>
          </div>
          <?php if (!empty($r['file_path'])): ?>
          <div class="mt-4">
            <a class="text-rajkot-rust text-xs font-bold uppercase tracking-widest" href="<?php echo htmlspecialchars(file_viewer_url(['kind' => 'drawing', 'id' => (int)($r['id'] ?? 0), 'project_id' => (int)$projectId])); ?>" target="_blank">Open File</a>
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </main>

    <?php require_once PROJECT_ROOT . '/Common/footer.php'; ?>
  </div>
</body>
</html>
