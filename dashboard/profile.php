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
  <header>
    <nav>
      <a href="dashboard.php">Dashboard</a> |
      <a href="../public/logout.php">Logout</a>
    </nav>
  </header>
  <main>
    <?php if ($user): ?>
      <h1>Profile: <?php echo htmlspecialchars($user); ?></h1>
      <p>Profile content goes here.</p>
    <?php else: ?>
      <p>Please <a href="../public/login.php">login</a>.</p>
    <?php endif; ?>
  </main>
</body>
</html>