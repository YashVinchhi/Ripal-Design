<?php
// Ensure session and constants are loaded first
require_once __DIR__ . '/../includes/init.php';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Worker Dashboard - Ripal Design</title>
    
    <!-- Custom Styles -->
    <?php asset_enqueue_css('/worker/worker_dashboard.css'); ?>
</head>
<body>
<?php
// Include navigation header (UI only)
$HEADER_MODE = 'dashboard';
require_once __DIR__ . '/../common/header_alt.php';

// Sample placeholder data — replace with real queries later
// Try to load real projects from DB if available, otherwise fallback to sample data
// DB and config are loaded via init
require_once __DIR__ . '/../includes/init.php';
$projects = [];
if (isset($pdo) && $pdo instanceof PDO) {
    try {
      $stmt = $pdo->query('SELECT id, name, status, COALESCE(progress,0) AS progress, COALESCE(due,\'1970-01-01\') AS due, COALESCE(location,\'\') AS location, latitude, longitude FROM projects ORDER BY id DESC LIMIT 200');
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Worker dashboard projects load failed: '.$e->getMessage());
        $projects = [];
    }
}
if (empty($projects)) {
  $projects = [
    [
      'id' => 101,
      'name' => 'Renovation — Oak Street Residence',
      'status' => 'ongoing',
      'progress' => 45,
      'due' => '2026-03-15',
      'location' => '123 Oak St, Rajkot, Gujarat'
    ],
    [
      'id' => 102,
      'name' => 'Payment Gateway Integration',
      'status' => 'completed',
      'progress' => 100,
      'due' => '2025-12-01',
      'location' => 'Office HQ, Ripal Design'
    ],
    [
      'id' => 103,
      'name' => 'Workshop Materials Procurement',
      'status' => 'on-hold',
      'progress' => 20,
      'due' => '2026-04-30',
      'location' => 'Warehouse, Industrial Park'
    ],
    [
      'id' => 104,
      'name' => 'Client Revisions — Lakeside Villa',
      'status' => 'overdue',
      'progress' => 70,
      'due' => '2026-01-20',
      'location' => 'Lakeside Villa, Plot 9'
    ],
    [
      'id' => 105,
      'name' => 'Site Survey — Elm Park',
      'status' => 'info',
      'progress' => 10,
      'due' => '2026-05-10',
      'location' => 'Elm Park, Sector 3'
    ],
  ];
}

// small summary counts for quick glance
$counts = array_count_values(array_map(function($x){return $x['status'];}, $projects));

// Load goods for visible projects (if DB available)
$goodsMap = [];
if (isset($pdo) && $pdo instanceof PDO) {
  $ids = array_map(function($p){return (int)$p['id'];}, $projects);
  if (!empty($ids)) {
    $in = implode(',', $ids);
    try {
      $stmt = $pdo->query("SELECT project_id, id, name, quantity, unit_price, total_price FROM project_goods WHERE project_id IN ($in) ORDER BY created_at DESC");
      $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
      foreach ($rows as $r) {
        $goodsMap[$r['project_id']][] = $r;
      }
    } catch (Exception $e) {
      error_log('Failed loading goods: '.$e->getMessage());
    }
  }
}
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

            <div class="meta-item">
              <label>Location</label>
              <div class="location-line">
                <?php echo htmlspecialchars($p['location'] ?? ''); ?>
                <?php if (!empty($p['latitude']) && !empty($p['longitude'])): ?>
                  <a class="btn outline btn-sm" target="_blank" href="https://www.google.com/maps/dir/?api=1&destination=<?php echo urlencode($p['latitude'] . ',' . $p['longitude']); ?>">Get Directions</a>
                <?php elseif (!empty($p['location'])): ?>
                  <a class="btn outline btn-sm" target="_blank" href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($p['location']); ?>">Get Directions</a>
                <?php endif; ?>
                <?php if (!empty($goodsMap[$p['id']])): ?>
                <div style="margin-top:8px;">
                  <strong>Goods:</strong>
                  <ul class="muted" style="margin:6px 0 0; padding-left:18px;">
                    <?php foreach($goodsMap[$p['id']] as $item): ?>
                      <li><?php echo htmlspecialchars($item['name']); ?><?php if(!empty($item['sku'])): ?> <small class="muted">(<?php echo htmlspecialchars($item['sku']); ?>)</small><?php endif; ?> — <?php echo intval($item['quantity']); ?> <?php echo htmlspecialchars($item['unit'] ?? 'pcs'); ?> × ₹ <?php echo number_format($item['unit_price'],2); ?> = ₹ <?php echo number_format($item['total_price'],2); ?></li>
                    <?php endforeach; ?>
                  </ul>
                </div>
                <div style="margin-top:8px;">
                  <a class="btn outline btn-sm" href="../dashboard/goods_invoice.php?project_id=<?php echo $p['id']; ?>" target="_blank">Invoice</a>
                </div>
              <?php endif; ?>
              </div>
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
require_once __DIR__ . '/../common/footer.php';
?>
</body>
</html>
