<?php
session_start();
$user = $_SESSION['user'] ?? 'employee01';

// Sample static review requests for UI prototype
$requests = [
  ['id'=>1,'subject'=>'Foundation Layer Inspection','project'=>'Renovation — Oak Street Residence','urgency'=>'High','date'=>'2026-02-02'],
  ['id'=>2,'subject'=>'Electrical Layout Approval','project'=>'Shop Fitout — Market Road','urgency'=>'Normal','date'=>'2026-02-05'],
];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Review Requests</title>
  <link rel="stylesheet" href="../worker/worker_dashboard.css">
</head>
<body>
  <?php require_once __DIR__ . '/../Common/header.php'; ?>
  <main class="worker-dashboard">
    <div class="page-header">
      <div class="toolbar justify-content-between">
        <div class="title-wrap">
          <h1>Review Requests</h1>
          <p class="muted">Requests submitted for review and approval</p>
        </div>
        <div class="avatar" aria-hidden="true"><?php echo strtoupper(substr($user,0,2)); ?></div>
      </div>
    </div>

    <section class="info-card" style="max-width:960px;">
      <h3>Pending Requests</h3>
      <div class="list-group">
        <?php foreach($requests as $r): ?>
          <div class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-bold"><?php echo htmlspecialchars($r['subject']); ?></div>
              <small class="text-muted"><?php echo htmlspecialchars($r['project']); ?> — <?php echo $r['date']; ?></small>
            </div>
            <div style="display:flex; gap:8px;">
              <span class="badge bg-custom text-dark border" style="background:#F2E6E6; color:var(--color-primary); padding:6px 10px; border-radius:8px;"><?php echo htmlspecialchars($r['urgency']); ?></span>
              <button class="btn outline">View</button>
              <button class="btn primary">Approve</button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

  </main>
  <?php require_once __DIR__ . '/../Common/footer.php'; ?>
</body>
</html>
