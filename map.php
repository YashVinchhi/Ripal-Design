<?php
// Dynamic sitemap for testing — scans repo for .php and .html pages
$root = __DIR__;
$files = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
// folders to exclude entirely
$excludeFolders = '(Common|includes|assets|sql|node_modules|vendor|\.git|api|app|tests|scripts|tools|docker)';
foreach ($it as $f) {
  if (!$f->isFile()) continue;
  $ext = strtolower(pathinfo($f->getFilename(), PATHINFO_EXTENSION));
  if (!in_array($ext, ['php', 'html'])) continue;
  $path = str_replace('\\','/',$f->getPathname());
  $rel = ltrim(substr($path, strlen($root)), '/\\');
  // Exclude internal partials, assets and non-page folders
  if (preg_match('#^' . $excludeFolders . '(/|$)#i', $rel)) continue;
  // Skip common header/footer partial filenames
  if (preg_match('#(^|/)(header|footer)\.(php|html)$#i', $rel)) continue;
  // Skip this map file itself
  if (strcasecmp($rel, basename(__FILE__)) === 0) continue;

  // Heuristic: determine whether file is previewable (contains HTML or includes header)
  $isPreviewable = false;
  $full = @file_get_contents($path);
  if ($full !== false) {
    $s = strtolower($full);
    // If file contains HTML structure directly
    if (preg_match('/<!doctype|<html|<head|<body/i', $s)) {
      $isPreviewable = true;
    }
    // If PHP file includes the shared header or renders page head
    if (!$isPreviewable && preg_match('/require_once\s.*header\.php|include_once\s.*header\.php|render_seo_head\(|public_content_page_values\(|app\/Ui\/header\.php|Common\/header\.php/i', $full)) {
      $isPreviewable = true;
    }
    // Exclude API/functional scripts that output JSON or redirect
    if (preg_match('/json_encode\(|application\/(json|xml)|header\s*\(\s*[\"\'].*Location|exit\(|die\(/i', $full)) {
      $isPreviewable = false;
    }
  }

  if ($isPreviewable) {
    $files[] = $rel;
  }
}
sort($files, SORT_NATURAL|SORT_FLAG_CASE);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Test Sitemap — Ripal Design</title>
  <style>
    :root{--bg:#ffffff;--muted:#6b7280;--border:#e5e7eb;--accent:#1e40af}
    body{font-family:Inter,ui-sans-serif,system-ui,Arial,Helvetica,sans-serif;margin:20px;background:var(--bg);color:#111}
    .wrap{display:flex;gap:20px}
    .list{width:420px;max-height:78vh;overflow:auto;border:1px solid var(--border);padding:8px;border-radius:8px;background:#fafafa}
    .preview{flex:1;border:1px solid var(--border);height:78vh;border-radius:8px;overflow:hidden}
    .item{display:flex;align-items:center;justify-content:space-between;padding:8px 10px;border-bottom:1px solid #f3f4f6}
    .item .name{flex:1;color:#0f172a;text-decoration:none;padding:6px;border-radius:6px}
    .item .name:hover{background:#f1f5f9}
    .open-new{margin-left:8px;text-decoration:none;padding:6px 9px;border:1px solid var(--border);border-radius:6px;background:#fff;color:var(--accent);font-weight:600}
    .open-new:hover{background:#eef2ff}
    .controls{margin-bottom:12px;display:flex;gap:8px;align-items:center}
    input[type=search]{flex:1;padding:8px;border-radius:8px;border:1px solid var(--border)}
    button{padding:8px 10px;border-radius:8px;border:1px solid var(--border);background:#fff}
    .small{font-size:13px;color:var(--muted)}
    .meta{font-size:12px;color:var(--muted)}
    .header-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px}
  </style>
</head>
<body>
  <div class="header-row">
    <div>
      <h1 style="margin:0">Previewable Pages</h1>
      <div class="small">Click a page to preview it on the right. Listing only pages that render HTML or include the shared header.</div>
    </div>
    <div class="meta">Found <?php echo count($files); ?> previewable pages</div>
  </div>
  <div class="controls">
    <input id="filter" type="search" placeholder="Filter pages...">
    <button id="openAll">Open all in new tabs</button>
  </div>
  <div class="wrap">
    <div class="list" id="list">
      <?php foreach ($files as $f): ?>
        <div class="item">
          <a class="name" href="#" data-href="<?php echo htmlspecialchars($f, ENT_QUOTES); ?>"><?php echo htmlspecialchars($f); ?></a>
          <div style="display:flex;gap:8px;align-items:center">
            <a class="open-new" href="<?php echo htmlspecialchars($f, ENT_QUOTES); ?>" target="_blank" rel="noopener" title="Open in new tab">Open</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <iframe id="preview" class="preview" name="preview" src="about:blank"></iframe>
  </div>

  <script>
    const list = document.getElementById('list');
    const preview = document.getElementById('preview');
    const filter = document.getElementById('filter');
    list.addEventListener('click', e => {
      const a = e.target.closest('a[data-href]');
      if (!a) return;
      e.preventDefault();
      const href = a.getAttribute('data-href');
      preview.src = href;
      // highlight
        list.querySelectorAll('a.name').forEach(x=>x.style.background='');
        a.style.background='#eef';
    });
    filter.addEventListener('input', ()=>{
      const q = filter.value.toLowerCase();
      list.querySelectorAll('.item').forEach(item=>{
        const txt = item.querySelector('.name').textContent.toLowerCase();
        item.style.display = txt.includes(q) ? 'flex' : 'none';
      });
    });
    document.getElementById('openAll').addEventListener('click', ()=>{
      if (!confirm('Open all listed pages in new tabs? (May open many tabs)')) return;
      list.querySelectorAll('.item').forEach(item=>{
        const a = item.querySelector('a.name');
        const href = a.getAttribute('data-href');
        window.open(href, '_blank');
      });
    });
  </script>
</body>
</html>