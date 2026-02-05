<?php
session_start();
// Placeholder for project details view
$projectId = $_GET['id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Project Details</title>
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
  <main>
    <h1>Project Details</h1>
    <?php if ($projectId): ?>
      <p>Details for project #<?php echo htmlspecialchars($projectId); ?></p>
    <?php else: ?>
      <p>No project selected.</p>
    <?php endif; ?>
  </main>
</body>
</html>