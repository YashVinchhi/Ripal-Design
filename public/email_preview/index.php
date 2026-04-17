<?php
$dir = __DIR__;
$files = array_filter(scandir($dir), function($f){ return !in_array($f, ['.', '..', 'index.php']); });
$previews = array_values($files);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Email Previews</title>
  <style>body{font-family:Arial,Helvetica,sans-serif;padding:24px;background:#f7f7f8}a{color:#b91c1c;text-decoration:none}</style>
</head>
<body>
  <h1>Email Previews</h1>
  <p>Click a preview to open it in your browser.</p>
  <ul>
<?php foreach ($previews as $p):
    $ext = strtolower(pathinfo($p, PATHINFO_EXTENSION));
    if (!in_array($ext, ['html','php'])) continue;
    echo '    <li><a href="' . htmlspecialchars($p) . '">' . htmlspecialchars($p) . '</a></li>\n';
  endforeach; ?>
  </ul>
  <h2>Dynamic previews</h2>
  <ul>
    <li><a href="invoice_dynamic.php">invoice_dynamic.php (renders invoice_email_html())</a></li>
  </ul>
  <hr>
  <p>To update a template, edit its source file in the repository and refresh this page.</p>
</body>
</html>
