<?php
// Ensure session and constants are loaded first
require_once __DIR__ . '/../includes/config.php';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Worker Dashboard - Ripal Design</title>
    
    <!-- Custom Styles -->
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/worker/worker_dashboard.css">
</head>
<body>
<?php
// Include navigation header (UI only)
require_once __DIR__ . '/../includes/header.php';

// Sample placeholder data — replace with real queries later
$projects = [
  [
    'id' => 101,
    'name' => 'Renovation — Oak Street Residence',
    'status' => 'ongoing',
    'progress' => 45,
    'due' => '2026-03-15'
  ],
  [
    'id' => 102,
    'name' => 'Payment Gateway Integration',
    'status' => 'completed',
    'progress' => 100,
    'due' => '2025-12-01'
  ],
  [
    'id' => 103,
    'name' => 'Workshop Materials Procurement',
    'status' => 'on-hold',
    'progress' => 20,
    'due' => '2026-04-30'
  ],
  [
    'id' => 104,
    'name' => 'Client Revisions — Lakeside Villa',
    'status' => 'overdue',
    'progress' => 70,
    'due' => '2026-01-20'
  ],
  [
    'id' => 105,
    'name' => 'Site Survey — Elm Park',
    'status' => 'info',
    'progress' => 10,
    'due' => '2026-05-10'
  ],
];

// small summary counts for quick glance
$counts = array_count_values(array_map(function($x){return $x['status'];}, $projects));
?>
<main class="worker-dashboard">
  <div class="page-header">
    <div class="toolbar justify-content-between">
      <div class="title-wrap">
        <h1>Worker Dashboard</h1>
        <p class="muted">Overview of your assigned projects and current workload</p>
      </div>
      <div class="avatar" aria-hidden="true">WD</div>
    </div>
  </div>

  <section class="dashboard-grid" aria-label="Assigned projects">
    <section class="dashboard-summary" aria-hidden="false">
      <article class="summary-card">
        <div class="summary-title">Active</div>
        <div class="summary-value"><?php echo intval($counts['ongoing'] ?? 0); ?></div>
      </article>
      <article class="summary-card">
        <div class="summary-title">Completed</div>
        <div class="summary-value"><?php echo intval($counts['completed'] ?? 0); ?></div>
      </article>
      <article class="summary-card">
        <div class="summary-title">On Hold</div>
        <div class="summary-value"><?php echo intval($counts['on-hold'] ?? 0); ?></div>
      </article>
      <article class="summary-card">
        <div class="summary-title">Overdue</div>
        <div class="summary-value"><?php echo intval($counts['overdue'] ?? 0); ?></div>
      </article>
    </section>
    <?php
    foreach ($projects as $p):
    ?>
    
    <article class="card project-card">
      <header class="card-header">
        <h3 class="project-name"><?php echo htmlspecialchars($p['name']); ?></h3>
        <span class="status-badge <?php echo htmlspecialchars($p['status']); ?>">
          <?php
            $label = ucfirst(str_replace('-', ' ', $p['status']));
            echo $label;
          ?>
        </span>
      </header>

      <div class="card-body">
        <div class="meta-row">
          <div class="meta-item">
            <label>Progress</label>
            <div class="progress">
              <div class="progress-fill" style="width: <?php echo intval($p['progress']); ?>%"></div>
            </div>
            <small class="muted"><?php echo intval($p['progress']); ?>% complete</small>
          </div>

          <div class="meta-item">
            <label>Due</label>
            <div class="due-date"><?php echo date('M d, Y', strtotime($p['due'])); ?></div>
          </div>
        </div>

        <div class="card-actions">
          <a class="btn primary" href="project_details.php?id=<?php echo intval($p['id']); ?>#details">View Details</a>
          <a class="btn outline" href="project_details.php?id=<?php echo intval($p['id']); ?>#drawings">Drawings</a>
          <a class="btn outline" href="project_details.php?id=<?php echo intval($p['id']); ?>#request">Request Review</a>
        </div>
      </div>
    </article>
    <?php endforeach; ?>
  </section>
</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
</body>
</html>
