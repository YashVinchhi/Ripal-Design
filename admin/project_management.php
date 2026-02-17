<?php
// Project Management (static UI prototype)
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Project Management</title>
  <link rel="stylesheet" href="../worker/worker_dashboard.css">
</head>
<body>
  <?php $HEADER_MODE = 'dashboard'; require_once __DIR__ . '/../common/header_alt.php'; ?>
  <main class="worker-dashboard container">
    <div class="page-header">
      <div class="toolbar justify-content-between">
        <div class="title-wrap">
          <h1>Project Management</h1>
          <p class="muted">Create and manage projects (UI prototype)</p>
        </div>
        <div class="avatar">PM</div>
      </div>
    </div>

    <section class="info-card">
      <h3>Projects</h3>
      <p>Tools to manage projects will go here.</p>
      <div style="margin-top:12px;"><a class="btn primary" href="project_management.php">Open Project Admin</a></div>
    </section>
  </main>
  <?php require_once __DIR__ . '/../common/footer.php'; ?>
</body>
</html>