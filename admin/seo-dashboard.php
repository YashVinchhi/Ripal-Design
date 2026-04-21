<?php
if (!defined('PROJECT_ROOT')) { require_once dirname(__DIR__, 1) . '/app/Core/Bootstrap/init.php'; }
require_login();
require_role('admin');

$pdo = get_db();
$projects = [];
if ($pdo instanceof PDO && function_exists('db_table_exists') && db_table_exists('projects')) {
  // Build select dynamically to avoid errors on missing columns
  $select = ['id', 'name'];
  $select[] = (function() {
    try { return db_column_exists('projects', 'seo_title') ? 'COALESCE(seo_title, "") AS seo_title' : '"" AS seo_title'; } catch (Throwable $e) { return '"" AS seo_title'; }
  })();
  $select[] = (function() {
    try { return db_column_exists('projects', 'meta_description') ? 'COALESCE(meta_description, "") AS meta_description' : '"" AS meta_description'; } catch (Throwable $e) { return '"" AS meta_description'; }
  })();
  $select[] = (function() {
    try { return db_column_exists('projects', 'slug') ? 'COALESCE(slug, "") AS slug' : '"" AS slug'; } catch (Throwable $e) { return '"" AS slug'; }
  })();

  $sql = 'SELECT ' . implode(', ', $select) . ' FROM projects ORDER BY id DESC';
  try {
    $stmt = $pdo->query($sql);
    $projects = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
  } catch (PDOException $e) {
    // If query fails, keep projects empty and log if available
    if (function_exists('app_log')) app_log('warning', 'SEO dashboard query failed', ['exception' => $e->getMessage()]);
    $projects = [];
  }
}

function flag_missing($p) {
    $flags = [];
    $title = trim((string)($p['seo_title'] ?: $p['name']));
    $desc = trim((string)($p['meta_description'] ?? ''));
    $slug = trim((string)($p['slug'] ?? ''));
    if ($title === '') $flags[] = 'missing_title';
    if ($desc === '') $flags[] = 'missing_description';
    if (mb_strlen($desc) > 160) $flags[] = 'description_too_long';
    if ($slug === '') $flags[] = 'missing_slug';
    return $flags;
}

?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>SEO Dashboard | Admin</title>
  <?php $HEADER_MODE = 'dashboard'; require_once PROJECT_ROOT . '/Common/header.php'; ?>
</head>
<body>
  <main class="max-w-6xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4">SEO Dashboard</h1>
    <p class="mb-4">Quick access to sitemap submission and per-project SEO audit.</p>
    <div class="mb-6">
      <a href="https://search.google.com/search-console" target="_blank" rel="noopener noreferrer" class="px-4 py-2 bg-rajkot-rust text-white rounded">Open Google Search Console</a>
      <a href="<?php echo rtrim((string)BASE_PATH, '/'); ?>/sitemap.php" target="_blank" rel="noopener noreferrer" class="ml-4 px-4 py-2 border rounded">View sitemap</a>
    </div>

    <table class="w-full border-collapse">
      <thead>
        <tr class="text-left text-sm text-gray-600 uppercase"><th class="p-2">ID</th><th>Title</th><th>Meta Description</th><th>Slug</th><th>Flags</th></tr>
      </thead>
      <tbody>
      <?php foreach ($projects as $p): $flags = flag_missing($p); ?>
        <tr class="border-t">
          <td class="p-2 align-top"><?php echo (int)$p['id']; ?></td>
          <td class="p-2 align-top"><?php echo htmlspecialchars((string)($p['seo_title'] ?: $p['name'])); ?></td>
          <td class="p-2 align-top"><div class="text-sm text-gray-700 max-w-xl break-words"><?php echo htmlspecialchars((string)($p['meta_description'])); ?></div></td>
          <td class="p-2 align-top"><?php echo htmlspecialchars((string)($p['slug'])); ?></td>
          <td class="p-2 align-top">
            <?php if (empty($flags)): ?>
              <span class="px-2 py-1 rounded bg-green-100 text-green-800 text-xs">OK</span>
            <?php else: ?>
              <?php foreach ($flags as $f): ?>
                <?php if ($f === 'missing_title'): ?><span class="px-2 py-1 rounded bg-yellow-100 text-yellow-800 text-xs">Missing Title</span><?php endif; ?>
                <?php if ($f === 'missing_description'): ?><span class="px-2 py-1 rounded bg-red-100 text-red-800 text-xs">Missing Description</span><?php endif; ?>
                <?php if ($f === 'description_too_long'): ?><span class="px-2 py-1 rounded bg-orange-100 text-orange-800 text-xs">Desc &gt;160</span><?php endif; ?>
                <?php if ($f === 'missing_slug'): ?><span class="px-2 py-1 rounded bg-yellow-100 text-yellow-800 text-xs">Missing Slug</span><?php endif; ?>
              <?php endforeach; ?>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </main>

  </phantom-ui>
  <script>
    (function () {
      function reveal() {
        var root = document.getElementById('phantom-ui-root') || document.querySelector('phantom-ui[loading]');
        if (!root) return;
        try {
          if (root.hasAttribute('loading')) {
            root.removeAttribute('loading');
          }
        } catch (e) {}
      }

      if (document.readyState === 'complete') {
        setTimeout(reveal, 50);
      } else {
        window.addEventListener('load', function () { setTimeout(reveal, 50); });
      }

      // Fallback: always reveal even if load handler is delayed.
      setTimeout(reveal, 1500);
    })();
  </script>
</body>
</html>
