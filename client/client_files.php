<?php
// Placeholder: lists client-uploaded files for a project
session_start();
$projectId = $_GET['project_id'] ?? null;
?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Client Files</title></head>
<body>
  <h1>Client Files</h1>
  <?php if ($projectId): ?>
    <p>Files for project <?php echo htmlspecialchars($projectId); ?></p>
  <?php else: ?>
    <p>No project specified.</p>
  <?php endif; ?>
</body>
</html>