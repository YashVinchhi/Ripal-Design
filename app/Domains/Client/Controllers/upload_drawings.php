<?php
if (!defined('PROJECT_ROOT')) { require_once dirname(__DIR__, 4) . '/app/Core/Bootstrap/init.php'; }
// Upload Drawings (Redesigned UI)
require_once PROJECT_ROOT . '/app/Core/Bootstrap/init.php';
require_login();

$projectOptions = db_connected() ? db_fetch_all('SELECT id, name FROM projects ORDER BY id DESC LIMIT 200') : [];

if (!function_exists('client_uuid_v4')) {
  /**
   * Generate a UUID v4 for unguessable upload filenames.
   */
  function client_uuid_v4(): string
  {
      $data = random_bytes(16);
      $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
      $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
      return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  require_csrf();

    $maxBytes = 5 * 1024 * 1024;

    $uploaded = $_FILES['drawing'] ?? null;
    if (!is_array($uploaded) || (int)($uploaded['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
      $msg = 'Please select an image file before submitting.';
    } else {
      // Use centralized secure upload helper
      if (!function_exists('store_uploaded_file_array')) {
        $msg = 'Server upload handler not available.';
      } else {
        $res = store_uploaded_file_array($uploaded, ['max_size' => $maxBytes]);
        if (empty($res['ok'])) {
          $msg = 'Upload failed: ' . ($res['error'] ?? 'unknown');
          if (function_exists('app_log')) {
            app_log('warning', 'Upload failed', ['error' => $res['error'] ?? '', 'tmp' => $uploaded['tmp_name'] ?? '', 'uploader_id' => function_exists('current_user_id') ? current_user_id() : 0]);
          }
        } else {
          $projectId = (int)($_POST['project_id'] ?? 0);
          $notes = trim((string)($_POST['submission_notes'] ?? ''));

          $storedName = $res['stored_name'];
          $storagePath = 'private_uploads/' . $storedName;

          if (function_exists('app_log')) {
            app_log('info', 'File uploaded', ['stored_name' => $storedName, 'storage_path' => $storagePath, 'uploader_id' => function_exists('current_user_id') ? current_user_id() : 0, 'size' => (int)($res['size'] ?? 0)]);
          }

          if ($projectId > 0 && db_connected()) {
            try {
              $db = get_db();
              if ($db instanceof PDO) {
                $stmt = $db->prepare('INSERT INTO project_drawings (project_id, name, version, status, file_path, uploaded_at) VALUES (?, ?, ?, ?, ?, NOW())');
                $displayName = 'Client drawing' . ($notes !== '' ? ': ' . mb_substr($notes, 0, 120) : '');
                $stmt->execute([$projectId, $displayName, 'v1.0', 'Under Review', $storagePath]);
              }
            } catch (Throwable $e) {
              if (function_exists('app_log')) {
                app_log('warning', 'Client upload metadata save failed', ['exception' => $e->getMessage(), 'project_id' => $projectId]);
              }
            }
          }
          $msg = 'Blueprint received and queued for architectural review.';
        }
      }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Upload Drawings | Ripal Design</title>
  <link rel="stylesheet" href="<?php echo esc_attr(rtrim((string)BASE_PATH, '/') . PUBLIC_PATH_PREFIX . '/css/main.css'); ?>">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body class="bg-canvas-white font-sans text-foundation-grey min-h-screen">
  <?php $HEADER_MODE = 'dashboard'; require_once PROJECT_ROOT . '/Common/header_alt.php'; ?>
  
  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 mt-20">
    <div class="mb-12">
      <div class="flex items-center gap-2 text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">
         <a href="client_files.php" class="hover:text-rajkot-rust transition">Design Studio</a>
         <i class="fa-solid fa-chevron-right text-[8px]"></i>
         <span>Upload Drawings</span>
      </div>
      <h1 class="text-3xl font-serif font-bold text-rajkot-rust">Submit Blueprints</h1>
      <p class="text-gray-500 mt-1">Submit your revised drawings or site photos for project review.</p>
    </div>

    <?php if (!empty($msg)): ?>
    <div class="mb-8 p-4 bg-green-50 border border-green-100 rounded-xl flex items-center gap-3 text-green-700 shadow-sm animate-bounce-slow">
      <i class="fa-solid fa-circle-check text-xl"></i>
       <p class="text-sm font-bold"><?php echo htmlspecialchars($msg); ?></p>
    </div>
    <?php endif; ?>

    <div class="max-w-3xl mx-auto">
      <form method="post" enctype="multipart/form-data" class="bg-white p-8 md:p-12 rounded-3xl shadow-sm border border-gray-100 relative overflow-hidden" novalidate>
        <?php echo csrf_token_field(); ?>
        <div class="absolute top-0 right-0 w-32 h-32 bg-rajkot-rust opacity-[0.03] -mr-16 -mt-16 rounded-full pointer-events-none"></div>
        
        <div class="mb-8">
          <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Project Selection</label>
           <select class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-rajkot-rust focus:border-transparent outline-none transition" name="project_id">
             <?php foreach ($projectOptions as $p): ?>
             <option value="<?php echo (int)$p['id']; ?>">PRJ-<?php echo str_pad((string)$p['id'], 6, '0', STR_PAD_LEFT); ?>: <?php echo htmlspecialchars((string)$p['name']); ?></option>
             <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-8">
           <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Upload Drawing (JPEG/PNG/WEBP)</label>
          <div class="border-2 border-dashed border-gray-200 rounded-3xl p-12 text-center group hover:border-rajkot-rust transition cursor-pointer relative">
             <input type="file" name="drawing" class="absolute inset-0 opacity-0 cursor-pointer" id="fileInput" accept="image/jpeg,image/png,image/webp" data-validation="required fileType:jpg,jpeg,png,webp fileSize:5120">
             <span id="name_error" class="text-danger"></span>
             <div class="space-y-4">
                <div class="w-16 h-16 bg-gray-50 text-gray-400 rounded-full flex items-center justify-center mx-auto group-hover:bg-red-50 group-hover:text-rajkot-rust transition">
                   <i class="fa-solid fa-cloud-arrow-up text-3xl"></i>
                </div>
                <div>
                   <p class="text-sm font-bold text-foundation-grey">Click to upload or drag and drop</p>
                   <p class="text-[10px] text-gray-400 uppercase tracking-widest mt-1">Maximum file size: 5MB</p>
                </div>
             </div>
          </div>
        </div>

        <div class="mb-10">
          <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Submission Notes</label>
          <textarea name="submission_notes" rows="4" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-rajkot-rust focus:border-transparent outline-none transition" placeholder="Briefly describe the changes or purpose of this upload..."></textarea>
        </div>

        <button type="submit" class="w-full py-4 bg-rajkot-rust text-white font-bold rounded-xl hover:bg-red-800 transition shadow-xl shadow-red-900/20 uppercase tracking-[0.2em] text-sm flex items-center justify-center gap-3">
          Submit for Review <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
        </button>
      </form>

      <div class="mt-8 text-center">
         <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold">Secure Architectural Transmission &bull; ISO 9001:2015 Compliant</p>
      </div>
    </div>
  </main>

  <style>
    @keyframes bounce-slow {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-5px); }
    }
    .animate-bounce-slow {
      animation: bounce-slow 3s infinite ease-in-out;
    }
  </style>

  <?php if (!defined('HIDE_FOOTER_CTA')) define('HIDE_FOOTER_CTA', true); require_once PROJECT_ROOT . '/Common/footer.php'; ?>
</body>
</html>