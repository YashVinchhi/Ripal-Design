<?php
// User Management (static UI prototype)
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>User Management</title>
  <link rel="stylesheet" href="../worker/worker_dashboard.css">
</head>
<body>
  <?php require_once __DIR__ . '/../includes/header.php'; ?>
  <main class="worker-dashboard container">
    <div class="page-header">
      <div class="toolbar justify-content-between">
        <div class="title-wrap">
          <h1>User Management</h1>
          <p class="muted">Admin interface for users (create, edit, delete)</p>
        </div>
        <div class="avatar">UM</div>
      </div>
    </div>

    <section class="info-card">
      <h3>Users</h3>
      <p>Admin interface for users (create, edit, delete).</p>
    </section>
  </main>
  <?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>