<?php
session_start();
// Profile placeholder - require authentication in real app
$user = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Profile - Ripal Design</title>
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
  <?php require_once __DIR__ . '/../Common/header.php'; ?>
  <main>
    <?php if ($user): ?>
      <h1>Profile: <?php echo htmlspecialchars($user); ?></h1>
      <p>Profile content goes here.</p>
    <?php else: ?>
      <p>Please <a href="../public/login.php">login</a>.</p>
    <?php endif; ?>
  </main>
  <?php require_once __DIR__ . '/../Common/footer.php'; ?>
</body>
</html>