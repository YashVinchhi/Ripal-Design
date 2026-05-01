<?php
if (!defined('PROJECT_ROOT')) {
    require_once dirname(__DIR__, 4) . '/app/Core/Bootstrap/init.php';
}

// Only allow clients
require_role('client');

$user = current_user();
$uid = current_user_id();
$uemail = strtolower(trim((string)($user['email'] ?? '')));
$ucontact = trim((string)($user['phone'] ?? $user['contact'] ?? ''));

$projects = [];
$counts = [
  'total' => 0,
  'planning' => 0,
  'ongoing' => 0,
  'paused' => 0,
  'completed' => 0,
];
$defaultCover = 'https://placehold.co/720x400/94180C/ffffff?text=Project+Cover';

if (function_exists('db_connected') && db_connected()) {
  $hasClientId = function_exists('db_column_exists') && db_column_exists('projects', 'client_id');

  // Build flexible where clauses to match by client_id, created_by, owner_email, owner_name or owner_contact
  $where = [];
  $params = [];

  if ($hasClientId) {
    $where[] = 'p.client_id = ?';
    $params[] = $uid;
  }

  $where[] = 'p.created_by = ?';
  $params[] = $uid;

  if ($uemail !== '') {
    $where[] = "LOWER(COALESCE(p.owner_email,'')) LIKE ?";
    $params[] = '%' . $uemail . '%';
  }

  $uname = strtolower(trim((string)($user['name'] ?? ($user['first_name'] . ' ' . ($user['last_name'] ?? '')))));
  if ($uname !== '') {
    $where[] = "LOWER(COALESCE(p.owner_name,'')) LIKE ?";
    $params[] = '%' . $uname . '%';
  }

  if ($ucontact !== '') {
    $where[] = "COALESCE(p.owner_contact,'') LIKE ?";
    $params[] = '%' . $ucontact . '%';
  }

  // Add extra tolerant matches (username, first/last name) to catch variations in owner fields
  $username = strtolower(trim((string)($user['username'] ?? '')));
  $firstName = strtolower(trim((string)($user['first_name'] ?? '')));
  $lastName = strtolower(trim((string)($user['last_name'] ?? '')));

  if ($username !== '') {
    $where[] = "LOWER(COALESCE(p.owner_email,'')) LIKE ?";
    $params[] = '%' . $username . '%';
    $where[] = "LOWER(COALESCE(p.owner_name,'')) LIKE ?";
    $params[] = '%' . $username . '%';
  }
  if ($firstName !== '') {
    $where[] = "LOWER(COALESCE(p.owner_name,'')) LIKE ?";
    $params[] = '%' . $firstName . '%';
  }
  if ($lastName !== '') {
    $where[] = "LOWER(COALESCE(p.owner_name,'')) LIKE ?";
    $params[] = '%' . $lastName . '%';
  }

  // Add extra tolerant matches (username, first/last name) to catch variations in owner fields
  $username = strtolower(trim((string)($user['username'] ?? '')));
  $firstName = strtolower(trim((string)($user['first_name'] ?? '')));
  $lastName = strtolower(trim((string)($user['last_name'] ?? '')));

  if ($username !== '') {
    $where[] = "LOWER(COALESCE(p.owner_email,'')) LIKE ?";
    $params[] = '%' . $username . '%';
    $where[] = "LOWER(COALESCE(p.owner_name,'')) LIKE ?";
    $params[] = '%' . $username . '%';
  }
  if ($firstName !== '') {
    $where[] = "LOWER(COALESCE(p.owner_name,'')) LIKE ?";
    $params[] = '%' . $firstName . '%';
  }
  if ($lastName !== '') {
    $where[] = "LOWER(COALESCE(p.owner_name,'')) LIKE ?";
    $params[] = '%' . $lastName . '%';
  }

  if (!empty($where)) {
    $select = "SELECT p.id, p.name, COALESCE(p.description,'') AS description, COALESCE(p.status,'') AS status, COALESCE(p.budget,'') AS budget, COALESCE(p.owner_email,'') AS owner_email, COALESCE(p.owner_contact,'') AS owner_contact";
    if (function_exists('db_table_exists') && db_table_exists('project_files')) {
      $select .= ", (SELECT pf.file_path FROM project_files pf WHERE pf.project_id = p.id AND pf.type IN ('JPG','JPEG','PNG','WEBP') ORDER BY pf.uploaded_at DESC LIMIT 1) AS cover_image";
    } else {
      $select .= ", NULL AS cover_image";
    }

    $sql = $select . " FROM projects p WHERE (" . implode(' OR ', $where) . ") ORDER BY p.id DESC";
    $projects = db_fetch_all($sql, $params) ?: [];

    // If no projects found, try exact-equality fallback (safer for mismatched formatting)
    if (empty($projects)) {
      $fallbackWhere = [];
      $fallbackParams = [];
      if ($hasClientId) {
        $fallbackWhere[] = 'client_id = ?';
        $fallbackParams[] = $uid;
      }
      $fallbackWhere[] = 'created_by = ?';
      $fallbackParams[] = $uid;
      if ($uemail !== '') {
        $fallbackWhere[] = 'LOWER(COALESCE(owner_email,\'\')) = ?';
        $fallbackParams[] = $uemail;
      }
      $fullname = strtolower(trim((string)($user['name'] ?? '')));
      if ($fullname !== '') {
        $fallbackWhere[] = 'LOWER(COALESCE(owner_name,\'\')) = ?';
        $fallbackParams[] = $fullname;
      }

      if (!empty($fallbackWhere)) {
        $fsql = "SELECT id, name, COALESCE(description,'') AS description, COALESCE(status,'') AS status, COALESCE(budget,'') AS budget, COALESCE(owner_email,'') AS owner_email, COALESCE(owner_contact,'') AS owner_contact";
        if (function_exists('db_table_exists') && db_table_exists('project_files')) {
          $fsql .= ", (SELECT pf.file_path FROM project_files pf WHERE pf.project_id = projects.id AND pf.type IN ('JPG','JPEG','PNG','WEBP') ORDER BY pf.uploaded_at DESC LIMIT 1) AS cover_image";
        } else {
          $fsql .= ", NULL AS cover_image";
        }
        $fsql .= " FROM projects WHERE (" . implode(' OR ', $fallbackWhere) . ") ORDER BY id DESC";
        $projects = db_fetch_all($fsql, $fallbackParams) ?: [];
        if (!empty($projects) && function_exists('app_log')) {
          app_log('debug', 'Client dashboard fallback match succeeded', ['user_id' => (int)$uid]);
        }
      }
    }

    // If no projects found, try exact-equality fallback (safer for mismatched formatting)
    if (empty($projects)) {
      $fallbackWhere = [];
      $fallbackParams = [];
      if ($hasClientId) {
        $fallbackWhere[] = 'client_id = ?';
        $fallbackParams[] = $uid;
      }
      $fallbackWhere[] = 'created_by = ?';
      $fallbackParams[] = $uid;
      if ($uemail !== '') {
        $fallbackWhere[] = 'LOWER(COALESCE(owner_email,\'\')) = ?';
        $fallbackParams[] = $uemail;
      }
      $fullname = strtolower(trim((string)($user['name'] ?? '')));
      if ($fullname !== '') {
        $fallbackWhere[] = 'LOWER(COALESCE(owner_name,\'\')) = ?';
        $fallbackParams[] = $fullname;
      }

      if (!empty($fallbackWhere)) {
        $fsql = "SELECT id, name, COALESCE(description,'') AS description, COALESCE(status,'') AS status, COALESCE(budget,'') AS budget, COALESCE(owner_email,'') AS owner_email, COALESCE(owner_contact,'') AS owner_contact";
        if (function_exists('db_table_exists') && db_table_exists('project_files')) {
          $fsql .= ", (SELECT pf.file_path FROM project_files pf WHERE pf.project_id = projects.id AND pf.type IN ('JPG','JPEG','PNG','WEBP') ORDER BY pf.uploaded_at DESC LIMIT 1) AS cover_image";
        } else {
          $fsql .= ", NULL AS cover_image";
        }
        $fsql .= " FROM projects WHERE (" . implode(' OR ', $fallbackWhere) . ") ORDER BY id DESC";
        $projects = db_fetch_all($fsql, $fallbackParams) ?: [];
        if (!empty($projects) && function_exists('app_log')) {
          app_log('debug', 'Client dashboard fallback match succeeded', ['user_id' => (int)$uid]);
        }
        // If still empty after fallback, log diagnostics for debugging on localhost
        if (empty($projects)) {
          $dbg = [
            'user_id' => $uid,
            'user_email' => $uemail,
            'user_name' => $fullname,
            'main_sql' => $sql,
            'main_params' => $params,
            'fallback_sql' => $fsql ?? null,
            'fallback_params' => $fallbackParams,
          ];
          if ((in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true) || (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false)) && function_exists('app_log')) {
            app_log('debug', 'Client dashboard diagnostics', $dbg);
          }
          // Show on-page diagnostic when explicitly requested via ?diag=1 from localhost
          if (!empty($_GET['diag']) && (in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true) || (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false))) {
            echo '<div class="max-w-7xl mx-auto px-4 py-6"><pre class="bg-white p-4 rounded shadow">' . htmlspecialchars(print_r($dbg, true), ENT_QUOTES, 'UTF-8') . '</pre></div>';
          }
        }
      }
    }

    // Compute counts and normalize cover images
    foreach ($projects as &$pr) {
      $st = strtolower((string)($pr['status'] ?? ''));
      if (isset($counts[$st])) {
        $counts[$st]++;
      }
      $counts['total']++;

      if (empty($pr['cover_image'])) {
        $pr['cover_image'] = $defaultCover;
      }
    }
    unset($pr);
  }
}
?>
<!DOCTYPE html>
<html lang="en" class="bg-canvas-white">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>My Projects | Client Dashboard</title>
  <?php $HEADER_MODE = 'dashboard'; require_once PROJECT_ROOT . '/Common/header.php'; ?>
</head>
<body class="bg-canvas-white font-sans text-foundation-grey min-h-screen">
  <div class="min-h-screen flex flex-col">
    <header class="bg-foundation-grey text-white pt-24 pb-12 px-4 shadow-lg border-b-2 border-rajkot-rust">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-serif font-bold">My Projects</h1>
            <p class="text-gray-300 mt-1 text-sm">Projects assigned to your account</p>
            <div class="mt-4 flex items-center gap-4 text-sm">
              <div class="px-3 py-2 bg-white text-rajkot-rust font-bold rounded shadow-sm">Total: <?php echo (int)$counts['total']; ?></div>
              <div class="px-3 py-2 bg-white text-gray-700 rounded shadow-sm">Ongoing: <?php echo (int)$counts['ongoing']; ?></div>
              <div class="px-3 py-2 bg-white text-gray-700 rounded shadow-sm">Planning: <?php echo (int)$counts['planning']; ?></div>
              <div class="px-3 py-2 bg-white text-gray-700 rounded shadow-sm">Completed: <?php echo (int)$counts['completed']; ?></div>
              <div class="px-3 py-2 bg-white text-gray-700 rounded shadow-sm">Paused: <?php echo (int)$counts['paused']; ?></div>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <a href="<?php echo esc_attr(BASE_PATH . '/client/client_files.php'); ?>" class="bg-white text-rajkot-rust px-4 py-2 rounded shadow-sm text-sm font-bold">Files</a>
          </div>
        </div>
    </header>

    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <?php if (empty($projects)): ?>
          <div class="md:col-span-3 bg-white p-8 border border-gray-100 shadow-premium text-center">
            <p class="text-lg font-semibold text-foundation-grey">No projects found</p>
            <p class="text-sm text-gray-500 mt-2">If you believe this is an error, contact support or your project manager.</p>
          </div>
        <?php else: ?>
          <?php foreach ($projects as $p): ?>
            <div class="bg-white border border-gray-100 p-6 shadow-premium hover:shadow-premium-hover transition-shadow">
              <div class="flex items-start justify-between gap-4">
                <div>
                  <h3 class="text-xl font-serif font-bold text-foundation-grey mb-1"><?php echo esc($p['name']); ?></h3>
                  <p class="text-sm text-gray-500 mb-3"><?php echo esc(substr((string)$p['description'], 0, 160)); ?></p>
                  <div class="flex items-center gap-3 text-xs">
                    <span class="px-2 py-1 bg-gray-50 border border-gray-100 text-gray-600 font-bold uppercase tracking-widest"><?php echo esc(strtoupper((string)$p['status'])); ?></span>
                    <span class="text-sm text-rajkot-rust font-bold">Budget: <?php echo esc((string)$p['budget']); ?></span>
                  </div>
                </div>
                <div class="text-right">
                  <a href="<?php echo esc_attr(BASE_PATH . '/dashboard/project_details.php?id=' . (int)$p['id']); ?>" class="inline-block bg-rajkot-rust text-white px-4 py-2 rounded font-bold">Open</a>
                  <div class="text-xs text-gray-400 mt-2">Owner: <?php echo esc((string)$p['owner_email']); ?></div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </main>

    <?php if (!defined('HIDE_FOOTER_CTA')) define('HIDE_FOOTER_CTA', true); require_once PROJECT_ROOT . '/Common/footer.php'; ?>
  </div>
</body>
</html>
