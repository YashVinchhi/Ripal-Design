<?php
session_start();
// Simple login placeholder - implement authentication logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // TODO: validate credentials
    $_SESSION['user'] = $_POST['username'] ?? 'user';
    header('Location: ../dashboard/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Login - Ripal Design</title>
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
  <main>
    <h1>Login</h1>
    <form method="post" action="login.php">
      <label>Username<br><input name="username" required></label><br>
      <label>Password<br><input type="password" name="password" required></label><br>
      <button type="submit">Login</button>
    </form>
  </main>
</body>
</html>