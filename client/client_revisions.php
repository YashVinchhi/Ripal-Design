<?php
// Placeholder: show revision requests/versions
session_start();
$projectId = $_GET['project_id'] ?? null;
?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Client Revisions</title></head>
<body>
  <h1>Client Revisions</h1>
  <?php if ($projectId): ?>
    <p>Revisions for project <?php echo htmlspecialchars($projectId); ?></p>
  <?php else: ?>
    <p>No project specified.</p>
  <?php endif; ?>
</body>
</html>