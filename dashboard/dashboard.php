<?php
session_start();
$user = $_SESSION['user'] ?? null;
if (!$user) {
    header('Location: ../public/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Dashboard - Ripal Design</title>
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
  <?php require_once __DIR__ . '/../Common/header.php'; ?>
  <main>
    <h1>Dashboard</h1>
    <p>Welcome, <?php echo htmlspecialchars($user); ?></p>
  </main>
  <?php require_once __DIR__ . '/../Common/footer.php'; ?>
</body>
</html>