<?php
session_start();
$projectId = $_GET['id'] ?? 1;

// Sample static project data for UI prototype
$project = [
  'id' => $projectId,
  'name' => 'Renovation — Oak Street Residence',
  'status' => 'ongoing',
  'address' => '123 Oak St, Rajkot, Gujarat',
  'budget' => '₹ 45,00,000',
  'owner' => ['name' => 'Amitbhai Patel', 'contact' => '+91 98765 43210'],
  'workers' => [
    ['role' => 'Plumber', 'name' => 'Ramesh Kumar'],
    ['role' => 'Electrician', 'name' => 'Suresh Bhai'],
  ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo htmlspecialchars($project['name']); ?> — Project Details</title>
  <link rel="stylesheet" href="../assets/css/styles.css">
  <link rel="stylesheet" href="../worker/worker_dashboard.css">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <style>
    .project-header { margin-bottom: 18px; }
    .tab-pane { padding-top: 12px; }
  </style>
</head>
<body>
  <?php require_once __DIR__ . '/../Common/header.php'; ?>
  <main class="worker-dashboard container">
    <div class="project-header">
      <div class="toolbar justify-content-between">
        <div class="title-wrap">
          <h1><?php echo htmlspecialchars($project['name']); ?></h1>
          <div class="muted"><?php echo htmlspecialchars($project['address']); ?></div>
        </div>
        <div class="avatar" aria-hidden="true"><?php echo strtoupper(substr(($project['name'] ?? 'P'),0,2)); ?></div>
      </div>
    </div>

    <ul class="nav nav-tabs" id="projTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">Overview</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="team-tab" data-bs-toggle="tab" data-bs-target="#team" type="button" role="tab">Team</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="files-tab" data-bs-toggle="tab" data-bs-target="#files" type="button" role="tab">Files</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity" type="button" role="tab">Activity</button>
      </li>
    </ul>

    <div class="tab-content">
      <div class="tab-pane fade show active" id="overview" role="tabpanel">
        <section class="info-card">
          <h3>Overview</h3>
          <div class="row">
            <div class="col-md-6">
              <dl class="data-list">
                <dt>Budget</dt>
                <dd><?php echo $project['budget']; ?></dd>
                <dt>Status</dt>
                <dd><span class="status-badge ongoing"><?php echo ucfirst($project['status']); ?></span></dd>
              </dl>
            </div>
            <div class="col-md-6">
              <h4>Owner</h4>
              <p><?php echo htmlspecialchars($project['owner']['name']); ?><br><a href="tel:<?php echo $project['owner']['contact']; ?>"><?php echo $project['owner']['contact']; ?></a></p>
            </div>
          </div>
        </section>
      </div>

      <div class="tab-pane fade" id="team" role="tabpanel">
        <section class="info-card">
          <h3>Assigned Team</h3>
          <div class="list-group">
            <?php foreach($project['workers'] as $w): ?>
              <div class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                  <div class="fw-bold"><?php echo htmlspecialchars($w['name']); ?></div>
                  <small class="text-muted"><?php echo htmlspecialchars($w['role']); ?></small>
                </div>
                <div>
                  <button class="btn outline">Message</button>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </section>
      </div>

      <div class="tab-pane fade" id="files" role="tabpanel">
        <section class="info-card">
          <h3>Files & Drawings</h3>
          <div class="drawing-grid">
            <div class="drawing-card">
              <i class="bi bi-file-earmark-pdf drawing-icon"></i>
              <div class="fw-bold">Ground Floor Plan.pdf</div>
              <small class="text-muted">2026-01-15</small>
            </div>
            <div class="drawing-card">
              <i class="bi bi-file-earmark-image drawing-icon"></i>
              <div class="fw-bold">Plumbing Diagram.jpg</div>
              <small class="text-muted">2026-01-22</small>
            </div>
          </div>
        </section>
      </div>

      <div class="tab-pane fade" id="activity" role="tabpanel">
        <section class="info-card">
          <h3>Activity Log</h3>
          <ul class="list-group list-group-flush">
            <li class="list-group-item">2026-02-01 — Project created by employee01</li>
            <li class="list-group-item">2026-02-05 — Worker assigned: Suresh Bhai</li>
          </ul>
        </section>
      </div>
    </div>

  </main>
  <?php require_once __DIR__ . '/../Common/footer.php'; ?>
</body>
</html>