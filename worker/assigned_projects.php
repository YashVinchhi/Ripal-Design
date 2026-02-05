<?php
// Placeholder: list of projects assigned to the logged-in user
session_start();
$user = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Assigned Projects</title></head>
<body>
  <h1>Assigned Projects</h1>
  <?php if ($user): ?>
    <p>Showing projects assigned to <?php echo htmlspecialchars($user); ?></p>
  <?php else: ?>
    <p>Please <a href="../public/login.php">login</a> to view assigned projects.</p>
  <?php endif; ?>
</body>
</html>