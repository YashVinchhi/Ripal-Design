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
  <header>
    <nav>
      <a href="profile.php">Profile</a> |
      <a href="../public/logout.php">Logout</a>
    </nav>
  </header>
  <main>
    <h1>Dashboard</h1>
    <p>Welcome, <?php echo htmlspecialchars($user); ?></p>
  </main>
</body>
</html>