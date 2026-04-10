<?php
session_start();
require_once __DIR__ . '/../includes/init.php';

$notice = '';
$noticeType = 'info';

if (!function_exists('file_viewer_test_relative_dir')) {
  function file_viewer_test_relative_dir() {
    return 'uploads/file_viewer_testing';
  }
}

if (!function_exists('file_viewer_test_absolute_dir')) {
  function file_viewer_test_absolute_dir() {
    return rtrim((string)PROJECT_ROOT, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, file_viewer_test_relative_dir());
  }
}

if (!function_exists('file_viewer_history_path')) {
  function file_viewer_history_path() {
    return rtrim((string)PROJECT_ROOT, '/\\') . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'file_viewer_test_history.json';
  }
}

if (!function_exists('file_viewer_load_history')) {
  function file_viewer_load_history() {
    $path = file_viewer_history_path();
    if (!is_file($path)) {
      return [];
    }
    $raw = (string)@file_get_contents($path);
    if ($raw === '') {
      return [];
    }
    $parsed = json_decode($raw, true);
    return is_array($parsed) ? $parsed : [];
  }
}

if (!function_exists('file_viewer_write_history')) {
  function file_viewer_write_history($items) {
    $path = file_viewer_history_path();
    $dir = dirname($path);
    if (!is_dir($dir)) {
      @mkdir($dir, 0775, true);
    }
    @file_put_contents($path, json_encode(array_values($items), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
  }
}

if (!function_exists('file_viewer_append_history')) {
  function file_viewer_append_history($action, $relativePath, $statusText, $meta = []) {
    $items = file_viewer_load_history();
    $items[] = [
      'timestamp' => date('c'),
      'action' => (string)$action,
      'file' => (string)$relativePath,
      'status' => (string)$statusText,
      'meta' => is_array($meta) ? $meta : [],
    ];
    if (count($items) > 200) {
      $items = array_slice($items, -200);
    }
    file_viewer_write_history($items);
  }
}

if (!function_exists('file_viewer_format_bytes')) {
  function file_viewer_format_bytes($bytes) {
    $size = (float)$bytes;
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($size >= 1024 && $i < count($units) - 1) {
      $size /= 1024;
      $i++;
    }
    return number_format($size, $i === 0 ? 0 : 2) . ' ' . $units[$i];
  }
}

$file = trim((string)($_GET['file'] ?? ''));
$projectId = (int)($_GET['project_id'] ?? 0);
$forcedView = strtolower(trim((string)($_GET['view'] ?? '')));
$fileName = $file !== '' ? basename($file) : 'N/A';
$projectName = 'Unknown Project';
$version = 'v1';
$status = 'under_review';
$uploadedAt = '';
$filePath = '';
$storagePath = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $postAction = trim((string)($_POST['test_action'] ?? ''));
  $redirectFile = $file;
  $redirectView = $forcedView;

  if ($postAction === 'upload_test_file' && isset($_FILES['test_file'])) {
    $targetDir = file_viewer_test_absolute_dir();
    if (!is_dir($targetDir) && !@mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
      $notice = 'Could not prepare test upload directory.';
      $noticeType = 'error';
    } else {
      $upload = $_FILES['test_file'];
      $originalName = (string)($upload['name'] ?? '');
      $tmpName = (string)($upload['tmp_name'] ?? '');
      $uploadError = (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE);
      $sizeBytes = (int)($upload['size'] ?? 0);
      $ext = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));
      $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'glb', 'gltf', 'mp4', 'webm', 'ogg', 'obj'];
      $maxBytes = 200 * 1024 * 1024;

      if ($uploadError !== UPLOAD_ERR_OK) {
        $notice = 'Upload failed. Please try again.';
        $noticeType = 'error';
      } elseif (!in_array($ext, $allowed, true)) {
        $notice = 'Unsupported file type for testing upload.';
        $noticeType = 'error';
      } elseif ($sizeBytes <= 0 || $sizeBytes > $maxBytes) {
        $notice = 'File size must be between 1 byte and 200 MB.';
        $noticeType = 'error';
      } else {
        $safeBase = preg_replace('/[^a-zA-Z0-9_\-]/', '_', (string)pathinfo($originalName, PATHINFO_FILENAME));
        $safeBase = trim((string)$safeBase, '_-');
        if ($safeBase === '') {
          $safeBase = 'test_file';
        }
        $finalName = $safeBase . '_' . date('Ymd_His') . '_' . substr(md5((string)microtime(true)), 0, 6) . '.' . $ext;
        $absolutePath = $targetDir . DIRECTORY_SEPARATOR . $finalName;
        $relativePath = file_viewer_test_relative_dir() . '/' . $finalName;

        if (@move_uploaded_file($tmpName, $absolutePath)) {
          $notice = 'Test file uploaded successfully.';
          $noticeType = 'success';
          $redirectFile = $relativePath;
          if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) && preg_match('/(^|[_\-\s])(360|pano|panorama|equirect)/i', $finalName)) {
            $redirectView = '360';
          }
          file_viewer_append_history('upload', $relativePath, 'saved', [
            'size' => $sizeBytes,
            'mime' => (string)($upload['type'] ?? ''),
          ]);
        } else {
          $notice = 'Could not save uploaded test file.';
          $noticeType = 'error';
        }
      }
    }
  } elseif ($postAction === 'delete_test_file') {
    $relativePath = str_replace('\\', '/', trim((string)($_POST['relative_path'] ?? '')));
    $prefix = file_viewer_test_relative_dir() . '/';
    if ($relativePath === '' || strpos($relativePath, $prefix) !== 0 || strpos($relativePath, '..') !== false) {
      $notice = 'Invalid test file path.';
      $noticeType = 'error';
    } else {
      $absolutePath = rtrim((string)PROJECT_ROOT, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
      $deleted = is_file($absolutePath) ? @unlink($absolutePath) : false;
      if ($deleted) {
        $notice = 'Test file deleted.';
        $noticeType = 'success';
        file_viewer_append_history('delete', $relativePath, 'deleted');
        if ($file === $relativePath) {
          $redirectFile = '';
          $redirectView = '';
        }
      } else {
        $notice = 'Could not delete test file.';
        $noticeType = 'error';
      }
    }
  }

  $params = [];
  if ($redirectFile !== '') {
    $params['file'] = $redirectFile;
  }
  if ($projectId > 0) {
    $params['project_id'] = (string)$projectId;
  }
  if ($redirectView !== '') {
    $params['view'] = $redirectView;
  }
  if ($notice !== '') {
    $params['notice'] = $notice;
    $params['notice_type'] = $noticeType;
  }
  header('Location: ' . $_SERVER['PHP_SELF'] . (empty($params) ? '' : ('?' . http_build_query($params))));
  exit;
}

$notice = trim((string)($_GET['notice'] ?? $notice));
$noticeType = trim((string)($_GET['notice_type'] ?? $noticeType));

if (!function_exists('resolve_preview_url')) {
  function resolve_preview_url($rawPath) {
    $value = trim((string)$rawPath);
    if ($value === '') {
      return '';
    }
    if (preg_match('/^https?:\/\//i', $value)) {
      return $value;
    }

    $normalized = str_replace('\\', '/', $value);
    $uploadsPos = strpos($normalized, '/uploads/');
    if ($uploadsPos === false && strpos($normalized, 'uploads/') === 0) {
      $uploadsPos = 0;
    }

    if ($uploadsPos === false) {
      return '';
    }

    $relative = $uploadsPos === 0 ? $normalized : substr($normalized, $uploadsPos + 1);
    $relative = ltrim($relative, '/');
    return function_exists('base_path') ? (string)base_path($relative) : '/' . $relative;
  }
}

if (!function_exists('resolve_preview_absolute_path')) {
  function resolve_preview_absolute_path($rawPath) {
    $value = trim((string)$rawPath);
    if ($value === '' || preg_match('/^https?:\/\//i', $value)) {
      return '';
    }

    $normalized = str_replace('\\', '/', $value);
    $uploadsPos = strpos($normalized, '/uploads/');
    if ($uploadsPos === false && strpos($normalized, 'uploads/') === 0) {
      $uploadsPos = 0;
    }
    if ($uploadsPos === false) {
      return '';
    }

    $relative = $uploadsPos === 0 ? $normalized : substr($normalized, $uploadsPos + 1);
    $relative = ltrim($relative, '/');
    $absolute = rtrim((string)PROJECT_ROOT, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    return is_file($absolute) ? $absolute : '';
  }
}

if (db_connected() && $file !== '') {
  $projectFilterSql = $projectId > 0 ? ' AND pf.project_id = ' . (int)$projectId . ' ' : ' ';
  $row = db_fetch("SELECT pf.filename, pf.name, pf.version, pf.status, pf.uploaded_at, p.name AS project_name, pf.file_path, pf.storage_path
        FROM project_files pf
        LEFT JOIN projects p ON p.id = pf.project_id
        WHERE pf.filename = ? OR pf.file_path = ? OR pf.storage_path = ? OR pf.name = ?
    " . $projectFilterSql . "
        ORDER BY pf.uploaded_at DESC LIMIT 1", [$file, $file, $file, $file]);

    if (!$row) {
    $projectFilterSql = $projectId > 0 ? ' AND pd.project_id = ' . (int)$projectId . ' ' : ' ';
    $row = db_fetch("SELECT pd.name, pd.version, pd.status, pd.uploaded_at, p.name AS project_name, pd.file_path
            FROM project_drawings pd
            LEFT JOIN projects p ON p.id = pd.project_id
            WHERE pd.file_path = ? OR pd.name = ?
      " . $projectFilterSql . "
            ORDER BY pd.uploaded_at DESC LIMIT 1", [$file, $file]);
    }

    if ($row) {
        $fileName = (string)($row['filename'] ?? $row['name'] ?? $fileName);
        $projectName = (string)($row['project_name'] ?? $projectName);
        $version = (string)($row['version'] ?? $version);
        $status = strtolower((string)($row['status'] ?? $status));
        $uploadedAt = (string)($row['uploaded_at'] ?? '');
        $filePath = (string)($row['file_path'] ?? '');
        $storagePath = (string)($row['storage_path'] ?? '');
    }
}

$testHistory = array_reverse(file_viewer_load_history());
$testFiles = [];
foreach ($testHistory as $entry) {
  $candidate = str_replace('\\', '/', trim((string)($entry['file'] ?? '')));
  if ($candidate === '' || isset($testFiles[$candidate])) {
    continue;
  }
  $absoluteCandidate = rtrim((string)PROJECT_ROOT, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $candidate);
  if (!is_file($absoluteCandidate)) {
    continue;
  }
  $testFiles[$candidate] = [
    'relative' => $candidate,
    'name' => basename($candidate),
    'size' => (int)@filesize($absoluteCandidate),
    'mtime' => (int)@filemtime($absoluteCandidate),
  ];
}

$previewUrl = '';
$previewAbsolutePath = '';
foreach ([$storagePath, $filePath, $file] as $candidate) {
  $resolvedUrl = resolve_preview_url($candidate);
  if ($resolvedUrl === '') {
    continue;
  }
  $previewUrl = $resolvedUrl;
  $previewAbsolutePath = resolve_preview_absolute_path($candidate);
  break;
}

$extensionSource = $filePath !== '' ? $filePath : ($storagePath !== '' ? $storagePath : $fileName);
$ext = strtolower((string)pathinfo((string)$extensionSource, PATHINFO_EXTENSION));
$isPanoramaName = (bool)preg_match('/(^|[_\-\s])(360|pano|panorama|equirect)/i', (string)$fileName);
$isPanoramaRatio = false;
if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) && $previewAbsolutePath !== '' && function_exists('getimagesize')) {
  $imgInfo = @getimagesize($previewAbsolutePath);
  if (is_array($imgInfo) && !empty($imgInfo[0]) && !empty($imgInfo[1])) {
    $ratio = (float)$imgInfo[0] / (float)$imgInfo[1];
    $isPanoramaRatio = abs($ratio - 2.0) <= 0.2;
  }
}
$is360Suitable = $isPanoramaName || $isPanoramaRatio;

$viewerMode = 'unsupported';
if (in_array($ext, ['glb', 'gltf'], true)) {
  $viewerMode = '3d';
} elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
  $viewerMode = $is360Suitable ? '360' : 'image';
} elseif ($ext === 'pdf') {
  $viewerMode = 'pdf';
} elseif (in_array($ext, ['mp4', 'webm', 'ogg'], true)) {
  $viewerMode = 'video';
}

if ($forcedView === '360' && in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
  $viewerMode = '360';
}
?>
<!DOCTYPE html>
<html lang="en" class="bg-canvas-white">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <title>File Viewer | Ripal Design</title>
  <?php $HEADER_MODE = 'dashboard'; require_once __DIR__ . '/../Common/header.php'; ?>
  <?php if ($viewerMode === '360' && $previewUrl !== ''): ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css">
  <?php endif; ?>
  <?php if ($viewerMode === '3d' && $previewUrl !== ''): ?>
    <script type="module" src="https://unpkg.com/@google/model-viewer/dist/model-viewer.min.js"></script>
  <?php endif; ?>
  <?php if ($viewerMode === '360' && $previewUrl !== ''): ?>
    <script src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>
  <?php endif; ?>
  <style>
    .viewer-3d-modal {
      position: fixed;
      inset: 0;
      z-index: 9999;
      background: rgba(0, 0, 0, 0.78);
      backdrop-filter: blur(4px);
      display: none;
      align-items: center;
      justify-content: center;
      padding: 16px;
      opacity: 0;
      transition: opacity 220ms ease;
    }

    .viewer-3d-modal.is-open {
      display: flex;
      opacity: 1;
    }

    .viewer-3d-dialog {
      width: min(96vw, 1400px);
      height: min(92vh, 900px);
      background: #111827;
      border: 1px solid rgba(255, 255, 255, 0.2);
      box-shadow: 0 24px 80px rgba(0, 0, 0, 0.35);
      display: flex;
      flex-direction: column;
      overflow: hidden;
      transform: translateY(16px) scale(0.97);
      opacity: 0;
      transition: transform 240ms ease, opacity 240ms ease;
    }

    .viewer-3d-modal.is-open .viewer-3d-dialog {
      transform: translateY(0) scale(1);
      opacity: 1;
    }

    .viewer-360-dialog {
      width: min(96vw, 1500px);
      height: min(92vh, 920px);
      background: #0f172a;
      border: 1px solid rgba(255, 255, 255, 0.2);
      box-shadow: 0 24px 80px rgba(0, 0, 0, 0.35);
      display: flex;
      flex-direction: column;
      overflow: hidden;
      transform: translateY(16px) scale(0.97);
      opacity: 0;
      transition: transform 240ms ease, opacity 240ms ease;
    }

    .viewer-3d-modal.is-open .viewer-360-dialog {
      transform: translateY(0) scale(1);
      opacity: 1;
    }

    .viewer-360-canvas {
      flex: 1;
      min-height: 0;
      background: #020617;
    }

    .viewer-3d-canvas {
      width: 100%;
      height: 100%;
      --progress-bar-color: #94180c;
      --poster-color: #0f172a;
    }

    .viewer-3d-viewport {
      position: relative;
      flex: 1;
      min-height: 0;
      overflow: hidden;
      background: #020617;
    }

    .viewer-3d-chip {
      animation: chipPulse 2.6s ease-in-out infinite;
    }

    .vr-mode-modal {
      position: fixed;
      inset: 0;
      z-index: 10000;
      background: #000;
      display: none;
      opacity: 0;
      transition: opacity 200ms ease;
    }

    .vr-mode-modal.is-open {
      display: block;
      opacity: 1;
    }

    .vr-mode-header {
      height: 52px;
      padding: 0 10px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      color: #fff;
      background: rgba(0, 0, 0, 0.72);
      border-bottom: 1px solid rgba(255, 255, 255, 0.15);
    }

    .vr-mode-split {
      height: calc(100vh - 52px);
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0;
      background: #000;
    }

    .vr-eye {
      position: relative;
      overflow: hidden;
      background: #020617;
      padding-top: max(env(safe-area-inset-top), 0px);
      padding-bottom: max(env(safe-area-inset-bottom), 0px);
    }

    .vr-eye model-viewer,
    .vr-eye .vr-pano {
      width: 100%;
      height: 100%;
      display: block;
      background: #000;
      transform: translateY(var(--vr-y-shift, 0px)) scale(var(--vr-scale, 1));
      transform-origin: center center;
    }

    .vr-eye::after {
      content: '';
      position: absolute;
      inset: 0;
      pointer-events: none;
      border: 1px solid rgba(255, 255, 255, 0.12);
      box-shadow: inset 0 0 80px rgba(0, 0, 0, 0.25);
    }

    .vr-eye-slave model-viewer,
    .vr-eye-slave .vr-pano {
      pointer-events: none;
      touch-action: none;
    }

    .vr-phone-only {
      display: none;
    }

    .vr-control-chip {
      font-size: 10px;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      padding: 4px 8px;
      border: 1px solid rgba(255, 255, 255, 0.22);
      background: rgba(0, 0, 0, 0.4);
      color: #dbeafe;
    }

    .vr-mode-modal.gyro-active .vr-control-chip {
      color: #86efac;
      border-color: rgba(134, 239, 172, 0.65);
    }

    @media (max-width: 767.98px) {
      .vr-phone-only {
        display: inline-flex;
      }
    }

    @media (hover: none) and (pointer: coarse) and (orientation: landscape) and (max-height: 500px) {
      .vr-phone-only {
        display: inline-flex;
      }
    }

    @keyframes chipPulse {
      0% { transform: translateY(0); }
      50% { transform: translateY(-1px); }
      100% { transform: translateY(0); }
    }
  </style>
</head>
<body class="bg-canvas-white font-sans text-foundation-grey min-h-screen">
  <div class="min-h-screen flex flex-col">
    <header class="bg-foundation-grey text-white pt-24 pb-12 px-4 shadow-lg mb-10 border-b-2 border-rajkot-rust">
      <div class="max-w-7xl mx-auto">
        <h1 class="text-4xl font-serif font-bold">File Viewer</h1>
        <p class="text-gray-400 mt-2">Database-backed preview with temporary testing uploads and file history.</p>
      </div>
    </header>

    <main class="flex-grow max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
      <?php if ($notice !== ''): ?>
        <div class="mb-6 px-4 py-3 border <?php echo $noticeType === 'success' ? 'border-approval-green text-approval-green bg-approval-green/5' : 'border-red-300 text-red-700 bg-red-50'; ?> rounded">
          <?php echo htmlspecialchars($notice); ?>
        </div>
      <?php endif; ?>

      <div class="bg-white border border-gray-100 shadow-premium p-6 mb-6">
        <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
          <div>
            <h2 class="text-[10px] uppercase tracking-widest text-rajkot-rust font-bold mb-2">Temporary Testing Upload</h2>
            <p class="text-sm text-gray-500">Upload a file to <strong><?php echo htmlspecialchars(file_viewer_test_relative_dir()); ?></strong> for local preview testing only.</p>
          </div>
          <form method="post" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3">
            <input type="hidden" name="test_action" value="upload_test_file">
            <input type="file" name="test_file" required class="text-sm">
            <button type="submit" class="bg-rajkot-rust hover:bg-red-700 text-white px-4 py-2 text-xs uppercase tracking-widest font-bold">Upload Test File</button>
          </form>
        </div>
      </div>

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

          <div class="mt-6 pt-6 border-t border-gray-100">
            <h3 class="text-[10px] uppercase tracking-widest text-rajkot-rust font-bold mb-3">Testing Files</h3>
            <div class="space-y-3 max-h-64 overflow-auto pr-1">
              <?php if (empty($testFiles)): ?>
                <p class="text-xs text-gray-400">No uploaded testing files yet.</p>
              <?php else: ?>
                <?php foreach ($testFiles as $tf): ?>
                  <div class="border border-gray-100 rounded p-3 bg-gray-50">
                    <p class="text-xs font-bold text-foundation-grey break-all mb-1"><?php echo htmlspecialchars($tf['name']); ?></p>
                    <p class="text-[11px] text-gray-500 mb-2"><?php echo htmlspecialchars(file_viewer_format_bytes($tf['size'])); ?> • <?php echo htmlspecialchars(date('M d, H:i', (int)$tf['mtime'])); ?></p>
                    <div class="flex items-center gap-2">
                      <a href="?file=<?php echo urlencode($tf['relative']); ?><?php echo $projectId > 0 ? '&project_id=' . (int)$projectId : ''; ?>" class="text-xs bg-foundation-grey hover:bg-rajkot-rust text-white px-2 py-1 no-underline">Open</a>
                      <form method="post" onsubmit="return confirm('Delete this testing file?');" class="inline">
                        <input type="hidden" name="test_action" value="delete_test_file">
                        <input type="hidden" name="relative_path" value="<?php echo htmlspecialchars($tf['relative']); ?>">
                        <button type="submit" class="text-xs bg-red-600 hover:bg-red-700 text-white px-2 py-1">Delete</button>
                      </form>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>

          <div class="mt-6 pt-6 border-t border-gray-100">
            <h3 class="text-[10px] uppercase tracking-widest text-rajkot-rust font-bold mb-3">Testing History</h3>
            <div class="space-y-2 max-h-56 overflow-auto pr-1">
              <?php if (empty($testHistory)): ?>
                <p class="text-xs text-gray-400">No history records yet.</p>
              <?php else: ?>
                <?php foreach (array_slice($testHistory, 0, 30) as $event): ?>
                  <div class="text-xs border border-gray-100 bg-white p-2 rounded">
                    <p class="font-semibold text-foundation-grey"><?php echo htmlspecialchars(strtoupper((string)($event['action'] ?? 'event'))); ?> • <?php echo htmlspecialchars((string)($event['status'] ?? '')); ?></p>
                    <p class="text-gray-500 break-all"><?php echo htmlspecialchars((string)($event['file'] ?? '')); ?></p>
                    <p class="text-gray-400"><?php echo htmlspecialchars(date('M d, Y H:i:s', strtotime((string)($event['timestamp'] ?? 'now')))); ?></p>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </aside>

        <section class="lg:col-span-2 bg-white border border-gray-100 shadow-premium p-6 min-h-[420px] flex flex-col">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-serif font-bold"><?php echo htmlspecialchars($fileName); ?></h3>
            <div class="flex items-center gap-2">
              <span class="text-[10px] uppercase tracking-widest px-2 py-1 bg-gray-50 border border-gray-100"><?php echo htmlspecialchars($status); ?></span>
            </div>
          </div>
          <?php if ($viewerMode === '360' && $previewUrl !== ''): ?>
            <div class="mb-3 flex flex-wrap items-center gap-2">
              <button type="button" id="zoomInBtn" class="text-xs bg-foundation-grey text-white px-2 py-1">Zoom +</button>
              <button type="button" id="zoomOutBtn" class="text-xs bg-foundation-grey text-white px-2 py-1">Zoom -</button>
              <button type="button" id="resetViewBtn" class="text-xs bg-foundation-grey text-white px-2 py-1">Reset</button>
              <button type="button" id="fullscreenBtn" class="text-xs bg-foundation-grey text-white px-2 py-1">Fullscreen</button>
              <button type="button" id="openVrModeBtn" class="vr-phone-only shrink-0 text-xs bg-slate-accent text-white px-2 py-1 items-center gap-1" title="Open VR mode">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M3 7h18a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2h-4.5a2.5 2.5 0 0 1-2.4-1.8l-.2-.7a2 2 0 0 0-1.9-1.5 2 2 0 0 0-1.9 1.5l-.2.7A2.5 2.5 0 0 1 7.5 17H3a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2z"/>
                  <circle cx="7.5" cy="12" r="1.2"/>
                  <circle cx="16.5" cy="12" r="1.2"/>
                </svg>
                VR
              </button>
            </div>
          <?php endif; ?>
          <div class="flex-grow border-2 border-dashed border-gray-200 rounded-lg bg-gray-50 overflow-hidden">
            <?php if ($previewUrl === ''): ?>
              <div class="h-full min-h-[360px] flex items-center justify-center text-gray-400 px-8 text-center">
                Preview unavailable. File path could not be resolved from saved metadata.
              </div>
            <?php elseif ($viewerMode === '3d'): ?>
              <div class="h-full min-h-[520px] bg-slate-900 text-white flex flex-col">
                <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 border-b border-white/10 bg-black/20">
                  <div class="flex items-center gap-2">
                    <span class="text-[10px] uppercase tracking-widest bg-white/10 px-2 py-1 viewer-3d-chip">3D Interactive</span>
                    <span class="hidden sm:inline text-[10px] uppercase tracking-widest text-gray-300">Drag to rotate • Scroll to zoom</span>
                  </div>
                  <div class="flex flex-wrap items-center gap-2 justify-end">
                    <button type="button" id="open3DPopup" class="text-xs uppercase tracking-widest bg-rajkot-rust hover:bg-red-700 px-3 py-2 text-white font-bold">Fullscreen 3D</button>
                    <button type="button" id="openVrModeBtn" class="vr-phone-only shrink-0 text-xs uppercase tracking-widest bg-slate-accent hover:bg-foundation-grey px-3 py-2 text-white font-bold items-center gap-1" title="Open VR mode">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 7h18a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2h-4.5a2.5 2.5 0 0 1-2.4-1.8l-.2-.7a2 2 0 0 0-1.9-1.5 2 2 0 0 0-1.9 1.5l-.2.7A2.5 2.5 0 0 1 7.5 17H3a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2z"/>
                        <circle cx="7.5" cy="12" r="1.2"/>
                        <circle cx="16.5" cy="12" r="1.2"/>
                      </svg>
                      VR
                    </button>
                  </div>
                </div>
                <model-viewer
                  id="inline3DViewer"
                  src="<?php echo htmlspecialchars($previewUrl); ?>"
                  camera-controls
                  auto-rotate
                  auto-rotate-delay="0"
                  rotation-per-second="25deg"
                  autoplay
                  shadow-intensity="1"
                  exposure="1"
                  environment-image="neutral"
                  class="w-full h-[520px] bg-slate-950"
                ></model-viewer>
              </div>
            <?php elseif ($viewerMode === '360'): ?>
              <div id="panoViewer" class="w-full h-[520px]"></div>
            <?php elseif ($viewerMode === 'image'): ?>
              <img src="<?php echo htmlspecialchars($previewUrl); ?>" alt="<?php echo htmlspecialchars($fileName); ?>" class="w-full h-[520px] object-contain bg-white" loading="lazy">
            <?php elseif ($viewerMode === 'pdf'): ?>
              <iframe src="<?php echo htmlspecialchars($previewUrl); ?>" class="w-full h-[520px] bg-white" title="PDF Preview"></iframe>
            <?php elseif ($viewerMode === 'video'): ?>
              <video controls class="w-full h-[520px] bg-black">
                <source src="<?php echo htmlspecialchars($previewUrl); ?>">
                Your browser does not support video preview.
              </video>
            <?php else: ?>
              <div class="h-full min-h-[360px] flex flex-col items-center justify-center text-gray-500 gap-4 px-8 text-center">
                <p>This file type cannot be previewed inline yet.</p>
                <a href="<?php echo htmlspecialchars($previewUrl); ?>" target="_blank" rel="noopener" class="bg-foundation-grey hover:bg-rajkot-rust text-white px-4 py-2 text-xs uppercase tracking-wider font-bold no-underline">Open File</a>
              </div>
            <?php endif; ?>
          </div>
        </section>
      </div>
    </main>

    <?php require_once __DIR__ . '/../Common/footer.php'; ?>
  </div>
  <?php if ($viewerMode === '3d' && $previewUrl !== ''): ?>
    <div id="threeDModal" class="viewer-3d-modal" aria-hidden="true">
      <div class="viewer-3d-dialog" role="dialog" aria-modal="true" aria-label="3D model fullscreen viewer">
        <div class="flex items-center justify-between px-4 py-3 border-b border-white/10 text-white bg-black/30">
          <div class="text-xs uppercase tracking-widest text-gray-200">Fullscreen 3D Viewer</div>
          <div class="flex items-center gap-2">
            <button type="button" id="modalOrbitToggle" class="text-xs bg-foundation-grey hover:bg-rajkot-rust text-white px-3 py-1">Toggle Orbit</button>
            <button type="button" id="modalClose3D" class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-1">Close</button>
          </div>
        </div>
        <div class="viewer-3d-viewport" id="modal3DViewport">
          <model-viewer
            id="modal3DViewer"
            src="<?php echo htmlspecialchars($previewUrl); ?>"
            camera-controls
            auto-rotate
            auto-rotate-delay="0"
            rotation-per-second="30deg"
            autoplay
            shadow-intensity="1"
            exposure="1"
            environment-image="neutral"
            class="viewer-3d-canvas"
          ></model-viewer>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if (($viewerMode === '3d' || $viewerMode === '360') && $previewUrl !== ''): ?>
    <div id="vrModeModal" class="vr-mode-modal" aria-hidden="true">
      <div class="vr-mode-header">
        <div class="flex items-center gap-2">
          <div class="text-xs uppercase tracking-widest">VR Mode</div>
          <span id="vrGyroStatus" class="vr-control-chip">Gyro Off</span>
        </div>
        <div class="flex items-center gap-2">
          <button type="button" id="enableGyroBtn" class="text-xs bg-slate-accent hover:bg-foundation-grey text-white px-3 py-1">Enable Gyro</button>
          <button type="button" id="closeVrModeBtn" class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-1">Close</button>
        </div>
      </div>
      <div class="vr-mode-split">
        <?php if ($viewerMode === '3d'): ?>
          <div class="vr-eye" id="vrEyeLeft">
            <model-viewer
              id="vrModelLeft"
              src="<?php echo htmlspecialchars($previewUrl); ?>"
              camera-controls
              auto-rotate
              auto-rotate-delay="0"
              rotation-per-second="24deg"
              camera-orbit="-3deg 75deg auto"
              exposure="1"
              shadow-intensity="1"
              environment-image="neutral"
            ></model-viewer>
          </div>
          <div class="vr-eye vr-eye-slave" id="vrEyeRight">
            <model-viewer
              id="vrModelRight"
              src="<?php echo htmlspecialchars($previewUrl); ?>"
              camera-controls
              auto-rotate
              auto-rotate-delay="0"
              rotation-per-second="24deg"
              camera-orbit="3deg 75deg auto"
              exposure="1"
              shadow-intensity="1"
              environment-image="neutral"
            ></model-viewer>
          </div>
        <?php else: ?>
          <div class="vr-eye" id="vrEyeLeft"><div id="vrPanoLeft" class="vr-pano"></div></div>
          <div class="vr-eye vr-eye-slave" id="vrEyeRight"><div id="vrPanoRight" class="vr-pano"></div></div>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($viewerMode === '360' && $previewUrl !== ''): ?>
    <div id="panoFullscreenModal" class="viewer-3d-modal" aria-hidden="true">
      <div class="viewer-360-dialog" role="dialog" aria-modal="true" aria-label="360 panoramic fullscreen viewer">
        <div class="flex items-center justify-between px-4 py-3 border-b border-white/10 text-white bg-black/30">
          <div class="text-xs uppercase tracking-widest text-gray-200">Fullscreen 360 Viewer</div>
          <div class="flex items-center gap-2">
            <button type="button" id="panoFsResetBtn" class="text-xs bg-foundation-grey hover:bg-rajkot-rust text-white px-3 py-1">Reset View</button>
            <button type="button" id="panoFsCloseBtn" class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-1">Close</button>
          </div>
        </div>
        <div id="panoFullscreenViewer" class="viewer-360-canvas"></div>
      </div>
    </div>
  <?php endif; ?>
  <?php if ($viewerMode === '360' && $previewUrl !== ''): ?>
    <script>
      (function () {
        if (!window.pannellum) {
          return;
        }
        const viewer = pannellum.viewer('panoViewer', {
          type: 'equirectangular',
          panorama: <?php echo json_encode($previewUrl); ?>,
          autoLoad: true,
          compass: false,
          showZoomCtrl: true,
          showFullscreenCtrl: true,
          mouseZoom: true
        });

        const zoomInBtn = document.getElementById('zoomInBtn');
        const zoomOutBtn = document.getElementById('zoomOutBtn');
        const resetViewBtn = document.getElementById('resetViewBtn');
        const fullscreenBtn = document.getElementById('fullscreenBtn');
        const panoFsModal = document.getElementById('panoFullscreenModal');
        const panoFsCloseBtn = document.getElementById('panoFsCloseBtn');
        const panoFsResetBtn = document.getElementById('panoFsResetBtn');

        let panoFsViewer = null;

        function openPanoFullscreen() {
          if (!panoFsModal) {
            return;
          }

          panoFsModal.classList.add('is-open');
          panoFsModal.setAttribute('aria-hidden', 'false');
          document.body.style.overflow = 'hidden';

          if (!panoFsViewer) {
            panoFsViewer = pannellum.viewer('panoFullscreenViewer', {
              type: 'equirectangular',
              panorama: <?php echo json_encode($previewUrl); ?>,
              autoLoad: true,
              autoRotate: -2,
              compass: false,
              showZoomCtrl: true,
              showFullscreenCtrl: false,
              mouseZoom: true,
              hfov: viewer.getHfov(),
              pitch: viewer.getPitch(),
              yaw: viewer.getYaw()
            });
          } else {
            panoFsViewer.setPitch(viewer.getPitch());
            panoFsViewer.setYaw(viewer.getYaw());
            panoFsViewer.setHfov(viewer.getHfov());
          }
        }

        function closePanoFullscreen() {
          if (!panoFsModal) {
            return;
          }
          panoFsModal.classList.remove('is-open');
          panoFsModal.setAttribute('aria-hidden', 'true');
          document.body.style.overflow = '';

          if (panoFsViewer) {
            viewer.setPitch(panoFsViewer.getPitch());
            viewer.setYaw(panoFsViewer.getYaw());
            viewer.setHfov(panoFsViewer.getHfov());
          }
        }

        if (zoomInBtn) {
          zoomInBtn.addEventListener('click', function () {
            viewer.setHfov(viewer.getHfov() - 10);
          });
        }
        if (zoomOutBtn) {
          zoomOutBtn.addEventListener('click', function () {
            viewer.setHfov(viewer.getHfov() + 10);
          });
        }
        if (resetViewBtn) {
          resetViewBtn.addEventListener('click', function () {
            viewer.setPitch(0);
            viewer.setYaw(0);
            viewer.setHfov(100);
          });
        }
        if (fullscreenBtn) {
          fullscreenBtn.addEventListener('click', function () {
            openPanoFullscreen();
          });
        }

        if (panoFsCloseBtn) {
          panoFsCloseBtn.addEventListener('click', closePanoFullscreen);
        }

        if (panoFsResetBtn) {
          panoFsResetBtn.addEventListener('click', function () {
            if (!panoFsViewer) {
              return;
            }
            panoFsViewer.setPitch(0);
            panoFsViewer.setYaw(0);
            panoFsViewer.setHfov(100);
          });
        }

        if (panoFsModal) {
          panoFsModal.addEventListener('click', function (event) {
            if (event.target === panoFsModal) {
              closePanoFullscreen();
            }
          });
        }

        document.addEventListener('keydown', function (event) {
          if (event.key === 'Escape' && panoFsModal && panoFsModal.classList.contains('is-open')) {
            closePanoFullscreen();
          }
        });
      })();
    </script>
  <?php endif; ?>
  <?php if (($viewerMode === '3d' || $viewerMode === '360') && $previewUrl !== ''): ?>
    <script>
      (function () {
        const openBtn = document.getElementById('openVrModeBtn');
        const modal = document.getElementById('vrModeModal');
        const closeBtn = document.getElementById('closeVrModeBtn');
        const enableGyroBtn = document.getElementById('enableGyroBtn');
        const vrGyroStatus = document.getElementById('vrGyroStatus');
        const vrModelLeft = document.getElementById('vrModelLeft');
        const vrModelRight = document.getElementById('vrModelRight');
        const vrPanoLeftEl = document.getElementById('vrPanoLeft');
        const vrPanoRightEl = document.getElementById('vrPanoRight');
        const vrEyeRight = document.getElementById('vrEyeRight');
        if (!openBtn || !modal || !closeBtn) {
          return;
        }

        let vrPanoInitialized = false;
        let vrPanoLeftViewer = null;
        let vrPanoRightViewer = null;
        let vrSyncFrame = 0;
        let gyroEnabled = false;
        let gyroHandler = null;
        let gyroBaselineAlpha = null;
        let gyroBaselineBeta = null;
        let gyroOriginYaw = 0;
        let gyroOriginPitch = 0;
        let gyroEventCount = 0;
        let gyroNoDataTimer = 0;
        let gyroUsingNativeOrientation = false;

        function hasSecureGyroContext() {
          return window.isSecureContext || window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
        }

        function setGyroStatus(active, text) {
          if (!vrGyroStatus) {
            return;
          }
          vrGyroStatus.textContent = text;
          if (active) {
            modal.classList.add('gyro-active');
          } else {
            modal.classList.remove('gyro-active');
          }
        }

        function clearGyroNoDataTimer() {
          if (gyroNoDataTimer) {
            clearTimeout(gyroNoDataTimer);
            gyroNoDataTimer = 0;
          }
        }

        function normalizeDeg(value) {
          let angle = Number(value) || 0;
          while (angle > 180) {
            angle -= 360;
          }
          while (angle < -180) {
            angle += 360;
          }
          return angle;
        }

        function clamp(value, min, max) {
          return Math.min(max, Math.max(min, value));
        }

        function updateVrCompensation() {
          const vv = window.visualViewport;
          const topInset = vv && Number.isFinite(vv.offsetTop) ? vv.offsetTop : 0;
          const yShift = topInset > 0 ? -Math.min(18, topInset * 0.6) : 0;
          const landscape = window.innerWidth > window.innerHeight;
          const scale = landscape ? 1.01 : 1;
          modal.style.setProperty('--vr-y-shift', yShift + 'px');
          modal.style.setProperty('--vr-scale', String(scale));
        }

        function formatFieldOfView(value) {
          if (typeof value === 'number') {
            return value + 'deg';
          }
          return String(value);
        }

        function sync3DViews() {
          if (!vrModelLeft || !vrModelRight) {
            return;
          }
          const source = vrModelLeft;
          const target = vrModelRight;
          if (!source || !target || typeof source.getCameraOrbit !== 'function' || typeof source.getFieldOfView !== 'function') {
            return;
          }
          const orbit = source.getCameraOrbit();
          const fov = source.getFieldOfView();
          target.setAttribute('camera-orbit', orbit && typeof orbit.toString === 'function' ? orbit.toString() : String(orbit));
          target.setAttribute('field-of-view', formatFieldOfView(fov));
        }

        function sync360Views() {
          if (!vrPanoLeftViewer || !vrPanoRightViewer) {
            return;
          }
          const source = vrPanoLeftViewer;
          const target = vrPanoRightViewer;
          if (!source || !target) {
            return;
          }

          const pitch = source.getPitch();
          const yaw = source.getYaw();
          const hfov = source.getHfov();

          // Pass `false` to prevent transition animation and enforce strict matching.
          target.setPitch(pitch, false);
          target.setYaw(yaw, false);
          target.setHfov(hfov, false);
        }

        function runVrSyncLoop() {
          if (!modal.classList.contains('is-open')) {
            vrSyncFrame = 0;
            return;
          }

          <?php if ($viewerMode === '3d'): ?>
          sync3DViews();
          <?php else: ?>
          sync360Views();
          <?php endif; ?>

          vrSyncFrame = requestAnimationFrame(runVrSyncLoop);
        }

        function startVrSync() {
          if (!vrSyncFrame) {
            vrSyncFrame = requestAnimationFrame(runVrSyncLoop);
          }
        }

        function stopVrSync() {
          if (vrSyncFrame) {
            cancelAnimationFrame(vrSyncFrame);
            vrSyncFrame = 0;
          }
        }

        function applyGyroToPano(event) {
          if (!gyroEnabled || !vrPanoLeftViewer || !vrPanoRightViewer) {
            return;
          }
          gyroEventCount += 1;

          const alpha = typeof event.alpha === 'number' ? event.alpha : null;
          const beta = typeof event.beta === 'number' ? event.beta : null;
          if (alpha === null || beta === null) {
            return;
          }

          if (gyroBaselineAlpha === null || gyroBaselineBeta === null) {
            gyroBaselineAlpha = alpha;
            gyroBaselineBeta = beta;
          }

          const angle = window.screen && window.screen.orientation ? window.screen.orientation.angle : 0;
          const yawFactor = angle === 90 ? -1 : 1;
          const yawDelta = normalizeDeg(alpha - gyroBaselineAlpha) * yawFactor;
          const pitchDelta = clamp((beta - gyroBaselineBeta) * 0.7, -45, 45);

          const nextYaw = normalizeDeg(gyroOriginYaw + yawDelta);
          const nextPitch = clamp(gyroOriginPitch - pitchDelta, -60, 60);

          vrPanoLeftViewer.setYaw(nextYaw, false);
          vrPanoRightViewer.setYaw(nextYaw, false);
          vrPanoLeftViewer.setPitch(nextPitch, false);
          vrPanoRightViewer.setPitch(nextPitch, false);
        }

        function disableGyro() {
          gyroEnabled = false;
          gyroBaselineAlpha = null;
          gyroBaselineBeta = null;
          gyroEventCount = 0;
          clearGyroNoDataTimer();
          if (gyroHandler) {
            window.removeEventListener('deviceorientation', gyroHandler, true);
            gyroHandler = null;
          }
          if (gyroUsingNativeOrientation) {
            if (vrPanoLeftViewer && typeof vrPanoLeftViewer.stopOrientation === 'function') {
              vrPanoLeftViewer.stopOrientation();
            }
            if (vrPanoRightViewer && typeof vrPanoRightViewer.stopOrientation === 'function') {
              vrPanoRightViewer.stopOrientation();
            }
          }
          gyroUsingNativeOrientation = false;
          if (enableGyroBtn) {
            enableGyroBtn.textContent = 'Enable Gyro';
          }
          setGyroStatus(false, 'Gyro Off');
        }

        async function enableGyro() {
          <?php if ($viewerMode !== '360'): ?>
          return;
          <?php endif; ?>
          if (!vrPanoLeftViewer || !vrPanoRightViewer) {
            return;
          }

          if (!hasSecureGyroContext()) {
            setGyroStatus(false, 'HTTPS Required');
            if (enableGyroBtn) {
              enableGyroBtn.textContent = 'HTTPS Required';
            }
            return;
          }

          if (typeof DeviceOrientationEvent === 'undefined') {
            setGyroStatus(false, 'Gyro Unsupported');
            return;
          }

          if (gyroEnabled) {
            disableGyro();
            return;
          }

          // Prefer Pannellum's native orientation handling when available.
          if (typeof vrPanoLeftViewer.startOrientation === 'function' && typeof vrPanoRightViewer.startOrientation === 'function') {
            try {
              vrPanoLeftViewer.startOrientation();
              gyroUsingNativeOrientation = true;
              gyroEnabled = true;
              if (enableGyroBtn) {
                enableGyroBtn.textContent = 'Disable Gyro';
              }
              setGyroStatus(true, 'Gyro On');
              return;
            } catch (err) {
              gyroUsingNativeOrientation = false;
            }
          }

          try {
            if (typeof DeviceOrientationEvent.requestPermission === 'function') {
              const permission = await DeviceOrientationEvent.requestPermission();
              if (permission !== 'granted') {
                setGyroStatus(false, 'Gyro Permission Denied');
                return;
              }
            }
          } catch (err) {
            setGyroStatus(false, 'Gyro Permission Failed');
            return;
          }

          gyroOriginYaw = vrPanoLeftViewer.getYaw();
          gyroOriginPitch = vrPanoLeftViewer.getPitch();
          gyroBaselineAlpha = null;
          gyroBaselineBeta = null;
          gyroEventCount = 0;
          clearGyroNoDataTimer();
          gyroHandler = applyGyroToPano;
          window.addEventListener('deviceorientation', gyroHandler, true);
          gyroEnabled = true;
          if (enableGyroBtn) {
            enableGyroBtn.textContent = 'Disable Gyro';
          }
          setGyroStatus(true, 'Gyro On');

          gyroNoDataTimer = window.setTimeout(function () {
            if (gyroEnabled && !gyroUsingNativeOrientation && gyroEventCount === 0) {
              setGyroStatus(false, 'No Sensor Data');
            }
          }, 2200);
        }

        function openVr() {
          modal.classList.add('is-open');
          modal.setAttribute('aria-hidden', 'false');
          document.body.style.overflow = 'hidden';
          updateVrCompensation();

          if (modal.requestFullscreen) {
            modal.requestFullscreen().catch(function () {});
          }

          <?php if ($viewerMode === '360'): ?>
          if (!vrPanoInitialized && window.pannellum) {
            vrPanoLeftViewer = pannellum.viewer('vrPanoLeft', {
              type: 'equirectangular',
              panorama: <?php echo json_encode($previewUrl); ?>,
              autoLoad: true,
              compass: false,
              showZoomCtrl: false,
              showFullscreenCtrl: false,
              mouseZoom: true,
              draggable: true,
              orientationOnByDefault: false,
              pitch: 0,
              yaw: 0,
              hfov: 100
            });

            vrPanoRightViewer = pannellum.viewer('vrPanoRight', {
              type: 'equirectangular',
              panorama: <?php echo json_encode($previewUrl); ?>,
              autoLoad: true,
              compass: false,
              showZoomCtrl: false,
              showFullscreenCtrl: false,
              mouseZoom: true,
              draggable: true,
              orientationOnByDefault: false,
              pitch: 0,
              yaw: 0,
              hfov: 100
            });
            vrPanoInitialized = true;
          }

          if (vrPanoLeftViewer && vrPanoRightViewer) {
            const basePitch = vrPanoLeftViewer.getPitch();
            const baseYaw = vrPanoLeftViewer.getYaw();
            const baseHfov = vrPanoLeftViewer.getHfov();
            vrPanoRightViewer.setPitch(basePitch, false);
            vrPanoRightViewer.setYaw(baseYaw, false);
            vrPanoRightViewer.setHfov(baseHfov, false);
          }

          if (vrEyeRight) {
            vrEyeRight.classList.add('vr-eye-slave');
          }

          if (enableGyroBtn) {
            enableGyroBtn.textContent = 'Enable Gyro';
          }
          if (!hasSecureGyroContext()) {
            if (enableGyroBtn) {
              enableGyroBtn.textContent = 'HTTPS Required';
            }
            setGyroStatus(false, 'HTTPS Required');
          } else {
            setGyroStatus(false, 'Tap Enable Gyro');
          }
          <?php endif; ?>

          startVrSync();
        }

        function closeVr() {
          modal.classList.remove('is-open');
          modal.setAttribute('aria-hidden', 'true');
          document.body.style.overflow = '';
          disableGyro();
          stopVrSync();
          if (document.fullscreenElement && document.exitFullscreen) {
            document.exitFullscreen().catch(function () {});
          }
        }

        openBtn.addEventListener('click', openVr);
        closeBtn.addEventListener('click', closeVr);

        if (enableGyroBtn) {
          enableGyroBtn.addEventListener('click', function () {
            enableGyro();
          });
        }

        if (window.visualViewport) {
          window.visualViewport.addEventListener('resize', updateVrCompensation);
          window.visualViewport.addEventListener('scroll', updateVrCompensation);
        }
        window.addEventListener('resize', updateVrCompensation);

        document.addEventListener('keydown', function (event) {
          if (event.key === 'Escape' && modal.classList.contains('is-open')) {
            closeVr();
          }
        });
      })();
    </script>
  <?php endif; ?>
  <?php if ($viewerMode === '3d' && $previewUrl !== ''): ?>
    <script>
      (function () {
        const openBtn = document.getElementById('open3DPopup');
        const modal = document.getElementById('threeDModal');
        const closeBtn = document.getElementById('modalClose3D');
        const orbitToggle = document.getElementById('modalOrbitToggle');
        const modalViewer = document.getElementById('modal3DViewer');

        if (!openBtn || !modal || !closeBtn || !modalViewer) {
          return;
        }

        let orbitEnabled = true;

        function openModal() {
          modal.classList.add('is-open');
          modal.setAttribute('aria-hidden', 'false');
          document.body.style.overflow = 'hidden';
        }

        function closeModal() {
          modal.classList.remove('is-open');
          modal.setAttribute('aria-hidden', 'true');
          document.body.style.overflow = '';
        }

        openBtn.addEventListener('click', openModal);
        closeBtn.addEventListener('click', closeModal);

        modal.addEventListener('click', function (event) {
          if (event.target === modal) {
            closeModal();
          }
        });

        document.addEventListener('keydown', function (event) {
          if (event.key === 'Escape' && modal.classList.contains('is-open')) {
            closeModal();
          }
        });

        if (orbitToggle) {
          orbitToggle.addEventListener('click', function () {
            orbitEnabled = !orbitEnabled;
            if (orbitEnabled) {
              modalViewer.setAttribute('auto-rotate', '');
            } else {
              modalViewer.removeAttribute('auto-rotate');
            }
          });
        }
      })();
    </script>
  <?php endif; ?>
</body>
</html>
