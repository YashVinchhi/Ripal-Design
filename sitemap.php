<?php
/**
 * Dynamic sitemap generator
 * - Outputs XML sitemap of static pages + public projects from DB
 * - Content-Type: application/xml
 * - Usage: /sitemap.php
 */
header('Content-Type: application/xml; charset=utf-8');

// Attempt to bootstrap existing app DB connection helpers
$db = null;
try {
    if (file_exists(__DIR__ . '/includes/db.php')) {
        require_once __DIR__ . '/includes/db.php';
        // assumes includes/db.php exposes $db (PDO) or a function to get it
        if (isset($db) && $db instanceof \PDO) {
            // ok
        } elseif (function_exists('db_get_pdo')) {
            $db = db_get_pdo();
        }
    }
} catch (Throwable $e) {
    // ignore and fallback to no-DB sitemap
}

// Helper: base URL
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['REQUEST_SCHEME'] ?? '') === 'https' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'example.com');
$base = rtrim($scheme . '://' . $host, '/');

$items = [];

// Static pages
$now = gmdate('Y-m-d');
$items[] = ['loc' => $base . '/', 'lastmod' => $now, 'changefreq' => 'daily', 'priority' => '1.0'];
$items[] = ['loc' => $base . '/about_us.php', 'lastmod' => $now, 'changefreq' => 'monthly', 'priority' => '0.6'];
$items[] = ['loc' => $base . '/services.php', 'lastmod' => $now, 'changefreq' => 'weekly', 'priority' => '0.8'];
$items[] = ['loc' => $base . '/contact_us.php', 'lastmod' => $now, 'changefreq' => 'monthly', 'priority' => '0.5'];

// Add public projects from DB if possible
if ($db instanceof \PDO) {
    try {
        // Try common column names; adapt if your schema differs
        $sql = "SELECT id, slug, updated_at, name FROM projects WHERE is_public = 1 ORDER BY updated_at DESC LIMIT 1000";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $slug = !empty($row['slug']) ? $row['slug'] : (isset($row['id']) ? 'project-' . $row['id'] : null);
            $loc = $base . '/project_view.php?slug=' . rawurlencode($slug);
            $lastmod = !empty($row['updated_at']) ? date('Y-m-d', strtotime($row['updated_at'])) : $now;
            $items[] = ['loc' => $loc, 'lastmod' => $lastmod, 'changefreq' => 'monthly', 'priority' => '0.6'];
        }
    } catch (Throwable $e) {
        // DB query failed; silently continue with static pages
    }
}

// Output XML
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($items as $it) {
    echo "  <url>\n";
    echo '    <loc>' . htmlspecialchars($it['loc'], ENT_QUOTES | ENT_XML1, 'UTF-8') . '</loc>\n';
    if (!empty($it['lastmod'])) {
        echo '    <lastmod>' . htmlspecialchars($it['lastmod'], ENT_QUOTES | ENT_XML1, 'UTF-8') . '</lastmod>\n';
    }
    if (!empty($it['changefreq'])) {
        echo '    <changefreq>' . htmlspecialchars($it['changefreq'], ENT_QUOTES | ENT_XML1, 'UTF-8') . '</changefreq>\n';
    }
    if (isset($it['priority'])) {
        echo '    <priority>' . htmlspecialchars($it['priority'], ENT_QUOTES | ENT_XML1, 'UTF-8') . '</priority>\n';
    }
    echo "  </url>\n";
}
echo '</urlset>' . "\n";

// Optional: simple ping endpoint when accessed with ?ping=1 (no auth) — triggers Google sitemap ping
if (!empty($_GET['ping'])) {
    $sitemapUrl = rawurlencode($base . '/sitemap.php');
    $googlePing = 'https://www.google.com/ping?sitemap=' . $sitemapUrl;
    // Fire and forget (suppress output)
    @file_get_contents($googlePing);
}

exit;
// <?php
// // Dynamic sitemap for testing — scans repo for .php and .html pages
// $root = __DIR__;
// $files = [];
// $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
// foreach ($it as $f) {
//     if (!$f->isFile()) continue;
//     $ext = strtolower(pathinfo($f->getFilename(), PATHINFO_EXTENSION));
//     if (!in_array($ext, ['php', 'html'])) continue;
//     $path = str_replace('\\','/',$f->getPathname());
//     $rel = ltrim(substr($path, strlen($root)), '/\\');
//     // Exclude internal partials, assets and non-page folders
//     if (preg_match('#^(Common|includes|assets|sql|node_modules|vendor|\.git)(/|$)#i', $rel)) continue;
//     // Skip common header/footer partial filenames
//     if (preg_match('#(^|/)(header|footer)\.(php|html)$#i', $rel)) continue;
//     // Optionally skip this sitemap file
//     if (strcasecmp($rel, basename(__FILE__)) === 0) continue;
//     $files[] = $rel;
// }
// sort($files, SORT_NATURAL|SORT_FLAG_CASE);
// ?>
// <!doctype html>
// <html lang="en">
// <head>
//   <meta charset="utf-8">
//   <meta name="viewport" content="width=device-width,initial-scale=1">
//   <title>Test Sitemap — Ripal Design</title>
//   <link rel="icon" href="favicon.ico" type="image/x-icon">
//   <style>
//     body{font-family:Arial,Helvetica,sans-serif;margin:16px}
//     .wrap{display:flex;gap:16px}
//     .list{width:420px;max-height:80vh;overflow:auto;border:1px solid #ddd;padding:8px}
//     .preview{flex:1;border:1px solid #ddd;height:80vh}
//     .item{display:flex;align-items:center;justify-content:space-between;padding:6px 8px;border-bottom:1px solid #f2f2f2}
//     .item .name{flex:1;color:#111;text-decoration:none}
//     .item .name:hover{background:#f7f7f7}
//     .open-new{margin-left:8px;text-decoration:none;padding:4px 8px;border:1px solid #ddd;border-radius:4px;background:#fff;color:#111}
//     .open-new:hover{background:#eef}
//     .controls{margin-bottom:8px;display:flex;gap:8px}
//     input[type=search]{flex:1;padding:6px}
//     button{padding:6px 8px}
//     .small{font-size:12px;color:#666}
//   </style>
// </head>
// <body>
//   <h1>Testing Sitemap</h1>
//   <p class="small">Click any page to preview it in the right pane. This is for testing only.</p>
//   <div class="controls">
//     <input id="filter" type="search" placeholder="Filter pages...">
//     <button id="openAll">Open all in new tabs</button>
//   </div>
//   <div class="wrap">
//     <div class="list" id="list">
//       <?php foreach ($files as $f): ?>
//         <div class="item">
//           <a class="name" href="#" data-href="<?php echo htmlspecialchars($f, ENT_QUOTES); ?>"><?php echo htmlspecialchars($f); ?></a>
//           <a class="open-new" href="<?php echo htmlspecialchars($f, ENT_QUOTES); ?>" target="_blank" rel="noopener" title="Open in new tab">↗</a>
//         </div>
//       <?php endforeach; ?>
//     </div>
//     <iframe id="preview" class="preview" name="preview" src="about:blank"></iframe>
//   </div>

//   <script>
//     const list = document.getElementById('list');
//     const preview = document.getElementById('preview');
//     const filter = document.getElementById('filter');
//     list.addEventListener('click', e => {
//       const a = e.target.closest('a[data-href]');
//       if (!a) return;
//       e.preventDefault();
//       const href = a.getAttribute('data-href');
//       preview.src = href;
//       // highlight
//       list.querySelectorAll('a').forEach(x=>x.style.background='');
//       a.style.background='#eef';
//     });
//     filter.addEventListener('input', ()=>{
//       const q = filter.value.toLowerCase();
//       list.querySelectorAll('a').forEach(a=>{
//         a.style.display = a.textContent.toLowerCase().includes(q) ? 'block' : 'none';
//       });
//     });
//     document.getElementById('openAll').addEventListener('click', ()=>{
//       if (!confirm('Open all listed pages in new tabs? (May open many tabs)')) return;
//       list.querySelectorAll('a').forEach(a=>{
//         const href = a.getAttribute('data-href');
//         window.open(href, '_blank');
//       });
//     });
//   </script>
// </body>
// </html>
