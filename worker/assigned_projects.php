<?php
session_start();
$user = $_SESSION['user'] ?? 'worker01';

// Sample assigned projects (UI only)
$projects = [
  ['id'=>101,'name'=>'Renovation — Oak Street Residence','status'=>'ongoing','progress'=>45,'due'=>'2026-03-15'],
  ['id'=>103,'name'=>'Workshop Materials Procurement','status'=>'on-hold','progress'=>20,'due'=>'2026-04-30'],
];
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Assigned Projects — <?php echo htmlspecialchars($user); ?></title>
  <link rel="stylesheet" href="../worker/worker_dashboard.css">
</head>
<body>
  <?php require_once __DIR__ . '/../Common/header.php'; ?>
  <main class="worker-dashboard container-fluid">
    <div class="page-header">
      <h1>Assigned Projects</h1>
      <p class="muted">Projects currently assigned to you</p>
    </div>

    <div class="dashboard-grid">
      <?php foreach($projects as $p): ?>
        <article class="card project-card">
          <div class="card-header">
            <div>
              <h3 class="project-name"><?php echo htmlspecialchars($p['name']); ?></h3>
              <div class="muted">Due: <?php echo date('M d, Y', strtotime($p['due'])); ?></div>
            </div>
            <div>
              <span class="status-badge <?php echo htmlspecialchars($p['status']); ?>"><?php echo ucfirst($p['status']); ?></span>
            </div>
          </div>
          <div class="card-body">
            <div class="meta-row">
              <div class="meta-item">
                <label>Progress</label>
                <div class="progress"><div class="progress-fill" style="width:<?php echo intval($p['progress']); ?>%"></div></div>
                <small class="muted"><?php echo intval($p['progress']); ?>% complete</small>
              </div>
            </div>
            <div class="card-actions">
              <a class="btn primary" href="../dashboard/project_details.php?id=<?php echo intval($p['id']); ?>">Open</a>
              <a class="btn outline" href="../dashboard/project_details.php?id=<?php echo intval($p['id']); ?>#drawings">Drawings</a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </main>
  <?php require_once __DIR__ . '/../Common/footer.php'; ?>
</body>
</html>
