<?php
session_start();
require_once __DIR__ . '/../includes/init.php';

$file = trim((string)($_GET['file'] ?? ''));
$fileName = $file !== '' ? basename($file) : 'N/A';
$projectName = 'Unknown Project';
$version = 'v1';
$status = 'under_review';
$uploadedAt = '';

if (db_connected() && $file !== '') {
    $row = db_fetch("SELECT pf.filename, pf.name, pf.version, pf.status, pf.uploaded_at, p.name AS project_name
        FROM project_files pf
        LEFT JOIN projects p ON p.id = pf.project_id
        WHERE pf.filename = ? OR pf.file_path = ? OR pf.storage_path = ? OR pf.name = ?
        ORDER BY pf.uploaded_at DESC LIMIT 1", [$file, $file, $file, $file]);

    if (!$row) {
        $row = db_fetch("SELECT pd.name, pd.version, pd.status, pd.uploaded_at, p.name AS project_name
            FROM project_drawings pd
            LEFT JOIN projects p ON p.id = pd.project_id
            WHERE pd.file_path = ? OR pd.name = ?
            ORDER BY pd.uploaded_at DESC LIMIT 1", [$file, $file]);
    }

    if ($row) {
        $fileName = (string)($row['filename'] ?? $row['name'] ?? $fileName);
        $projectName = (string)($row['project_name'] ?? $projectName);
        $version = (string)($row['version'] ?? $version);
        $status = strtolower((string)($row['status'] ?? $status));
        $uploadedAt = (string)($row['uploaded_at'] ?? '');
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="bg-canvas-white">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>File Viewer | Ripal Design</title>
  <?php $HEADER_MODE = 'dashboard'; require_once __DIR__ . '/../Common/header.php'; ?>
</head>
<body class="bg-canvas-white font-sans text-foundation-grey min-h-screen">
  <div class="min-h-screen flex flex-col">
    <header class="bg-foundation-grey text-white pt-24 pb-12 px-4 shadow-lg mb-10 border-b-2 border-rajkot-rust">
      <div class="max-w-7xl mx-auto">
        <h1 class="text-4xl font-serif font-bold">File Viewer</h1>
        <p class="text-gray-400 mt-2">Database-backed file metadata and preview shell.</p>
      </div>
    </header>

    <main class="flex-grow max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <aside class="bg-white border border-gray-100 shadow-premium p-6">
          <h2 class="text-[10px] uppercase tracking-widest text-rajkot-rust font-bold mb-4">File Details</h2>
          <div class="space-y-3 text-sm">
            <p><strong>Project:</strong> <?php echo htmlspecialchars($projectName); ?></p>
            <p><strong>File:</strong> <?php echo htmlspecialchars($fileName); ?></p>
            <p><strong>Version:</strong> <?php echo htmlspecialchars($version); ?></p>
            <p><strong>Status:</strong> <?php echo htmlspecialchars($status); ?></p>
            <p><strong>Uploaded:</strong> <?php echo $uploadedAt ? htmlspecialchars(date('M d, Y H:i', strtotime($uploadedAt))) : 'N/A'; ?></p>
          </div>
        </aside>

        <section class="lg:col-span-2 bg-white border border-gray-100 shadow-premium p-6 min-h-[420px] flex flex-col">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-serif font-bold"><?php echo htmlspecialchars($fileName); ?></h3>
            <span class="text-[10px] uppercase tracking-widest px-2 py-1 bg-gray-50 border border-gray-100"><?php echo htmlspecialchars($status); ?></span>
          </div>
          <div class="flex-grow border-2 border-dashed border-gray-200 flex items-center justify-center text-gray-400">
            Preview surface ready. Integrate actual PDF/image rendering here.
          </div>
        </section>
      </div>
    </main>

    <?php require_once __DIR__ . '/../Common/footer.php'; ?>
  </div>
</body>
</html>
