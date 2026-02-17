<?php
$HEADER_MODE = 'dashboard';
// Placeholder: safe file viewer (implement security checks before use)
require_once __DIR__ . '/../common/header_alt.php';
$file = $_GET['file'] ?? null;
?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>File Viewer</title></head>
<body>
<?php // header included above ?>
  <main>
    <h1>File Viewer</h1>
    <?php if ($file): ?>
      <p>Requested file: <?php echo htmlspecialchars($file); ?></p>
      <p>Implement secure file-serving here.</p>
    <?php else: ?>
      <p>No file specified.</p>
    <?php endif; ?>
  </main>
<?php require_once __DIR__ . '/../common/footer.php'; ?>
</body>
</html>