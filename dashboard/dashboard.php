<?php
session_start();
$user = $_SESSION['user'] ?? 'Demo User';

// Try to load projects and workers from database, fall back to static data when DB not available.
require_once __DIR__ . '/../includes/db.php';

$projects = [];
$workers = [];
$assignments = [];

// Load projects from DB when available, otherwise use fallback static data
if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $stmt = $pdo->query("SELECT id, name FROM projects ORDER BY id DESC LIMIT 200");
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Failed loading projects: ' . $e->getMessage());
        $projects = [
          ['id' => 1, 'name' => 'Renovation — Oak Street Residence'],
          ['id' => 2, 'name' => 'Shop Fitout — Market Road'],
          ['id' => 3, 'name' => 'New Build — Riverfront Villa'],
        ];
    }
} else {
    $projects = [
      ['id' => 1, 'name' => 'Renovation — Oak Street Residence'],
      ['id' => 2, 'name' => 'Shop Fitout — Market Road'],
      ['id' => 3, 'name' => 'New Build — Riverfront Villa'],
    ];
}

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE role = :role ORDER BY username ASC");
        $stmt->execute(['role' => 'worker']);
        $workers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Failed loading workers: ' . $e->getMessage());
        $workers = [
          ['id' => 11, 'username' => 'Ramesh Kumar'],
          ['id' => 12, 'username' => 'Suresh Bhai'],
          ['id' => 13, 'username' => 'Mahesh M.'],
        ];
    }
} else {
    $workers = [
      ['id' => 11, 'username' => 'Ramesh Kumar'],
      ['id' => 12, 'username' => 'Suresh Bhai'],
      ['id' => 13, 'username' => 'Mahesh M.'],
    ];
}

// Load recent assignments if table exists
if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $stmt = $pdo->query("SELECT a.project_id, p.name AS project_name, u.username AS worker_name, a.assigned_at
                         FROM project_assignments a
                         LEFT JOIN projects p ON p.id = a.project_id
                         LEFT JOIN users u ON u.id = a.worker_id
                         ORDER BY a.assigned_at DESC LIMIT 20");
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Failed loading assignments: ' . $e->getMessage());
        $assignments = [
          ['project_name' => 'Renovation — Oak Street Residence', 'worker_name' => 'Ramesh Kumar', 'assigned_at' => '2026-02-01 10:00'],
          ['project_name' => 'Shop Fitout — Market Road', 'worker_name' => 'Suresh Bhai', 'assigned_at' => '2026-02-05 14:30'],
        ];
    }
} else {
    $assignments = [
      ['project_name' => 'Renovation — Oak Street Residence', 'worker_name' => 'Ramesh Kumar', 'assigned_at' => '2026-02-01 10:00'],
      ['project_name' => 'Shop Fitout — Market Road', 'worker_name' => 'Suresh Bhai', 'assigned_at' => '2026-02-05 14:30'],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Dashboard - Ripal Design</title>
  <link rel="stylesheet" href="../worker/worker_dashboard.css">
</head>
<body>
  <?php require_once __DIR__ . '/../Common/header.php'; ?>
  <main class="worker-dashboard">
    <div class="container">
      <div class="page-header">
        <div class="toolbar justify-content-between">
          <div class="title-wrap">
            <h1>Dashboard</h1>
            <p class="muted">Overview of projects and quick actions</p>
          </div>
          <div class="avatar" aria-hidden="true"><?php echo strtoupper(substr($user,0,2)); ?></div>
        </div>
      </div>

      <!-- Summary KPIs -->
      <div class="dashboard-summary">
        <div class="summary-card">
          <div class="summary-title">Active Projects</div>
          <div class="summary-value"><?php echo count($projects); ?></div>
        </div>
        <div class="summary-card">
          <div class="summary-title">Assigned Workers</div>
          <div class="summary-value"><?php echo count($workers); ?></div>
        </div>
        <div class="summary-card">
          <div class="summary-title">Open Assignments</div>
          <div class="summary-value"><?php echo count($assignments); ?></div>
        </div>
        <div class="summary-card">
          <div class="summary-title">Pending Reviews</div>
          <div class="summary-value">2</div>
        </div>
      </div>

      <!-- Actions -->
      <div class="toolbar" style="margin-bottom:24px;">
        <button class="btn primary" onclick="location.href='project_details.php'">Create Project</button>
        <a class="btn outline" href="../admin/project_management.php">Manage Projects</a>
        <a class="btn outline" href="profile.php">Profile</a>
        <a class="btn outline" href="review_requests.php">Review Requests</a>
        <div style="flex:1"></div>
        <input type="search" placeholder="Search projects..." style="padding:10px 12px; border:1px solid var(--color-border); border-radius:8px; width:260px;">
      </div>

      <!-- Projects Grid -->
      <div class="dashboard-grid">
        <?php foreach($projects as $p): ?>
          <article class="card project-card">
            <div class="card-header">
              <div>
                <h3 class="project-name"><?php echo htmlspecialchars($p['name']); ?></h3>
                <div class="muted">Project ID: <?php echo $p['id']; ?> &middot; Area: 2,400 sq.ft.</div>
              </div>
              <div>
                <span class="status-badge ongoing">Ongoing</span>
              </div>
            </div>
            <div class="card-body">
              <div class="meta-row">
                <div class="meta-item">
                  <label>Budget</label>
                  <div class="due-date">₹ 45,00,000</div>
                </div>
                <div class="meta-item">
                  <label>Owner</label>
                  <div class="muted">Amitbhai Patel</div>
                </div>
              </div>

              <div class="card-actions">
                <a class="btn outline" href="project_details.php?id=<?php echo $p['id']; ?>">Open</a>
                <a class="btn" href="project_details.php?id=<?php echo $p['id']; ?>">View Details</a>
                <a class="btn outline" href="goods_list.php?project_id=<?php echo $p['id']; ?>">Goods</a>
                <a class="btn outline" href="../worker/assigned_projects.php?project_id=<?php echo $p['id']; ?>">Assigned</a>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>

    </div>
  </main>

  <!-- Assign Worker Modal -->
  <div id="assignModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.4); align-items:center; justify-content:center; z-index:9999;">
    <div style="width:420px; background:#fff; border-radius:10px; padding:18px; box-shadow:0 10px 30px rgba(0,0,0,0.2);">
      <h3 style="margin-top:0; color:var(--color-primary, #731209);">Assign Worker</h3>
      <p id="assignProjectName" style="margin:6px 0 12px; color:#333;">Select a worker to assign to the project.</p>
      <input type="hidden" id="assign_project_id" value="">
      <div style="margin-bottom:12px;">
        <label style="display:block; font-size:14px; margin-bottom:6px; color:#666;">Worker</label>
        <select id="assign_worker_select" style="width:100%; padding:8px 10px; border:1px solid var(--color-border,#E0E0E0); border-radius:8px;">
          <?php foreach($workers as $w): ?>
            <option value="<?php echo $w['id']; ?>"><?php echo htmlspecialchars($w['username']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="display:flex; gap:8px; justify-content:flex-end;">
        <button class="btn outline" onclick="hideAssignModal();">Cancel</button>
        <button class="btn primary" id="assignSubmitBtn" onclick="submitAssign();">Assign</button>
      </div>
    </div>
  </div>

  <script>
    function openAssignModal(projectId, projectName){
      document.getElementById('assign_project_id').value = projectId;
      document.getElementById('assignProjectName').textContent = projectName || 'Select a worker to assign to the project.';
      document.getElementById('assignModal').style.display = 'flex';
    }
    function hideAssignModal(){
      document.getElementById('assignModal').style.display = 'none';
    }
    async function submitAssign(){
      var projectId = document.getElementById('assign_project_id').value;
      var workerId = document.getElementById('assign_worker_select').value;
      if(!projectId || !workerId){ alert('Please select a worker.'); return; }
        var btn = document.getElementById('assignSubmitBtn');
      btn.disabled = true; btn.textContent = 'Assigning...';
      console.log('submitAssign called', { projectId, workerId });
      try{
        var fd = new FormData();
        fd.append('project_id', projectId);
        fd.append('worker_id', workerId);
        // use explicit relative path and include X-Requested-With header
        var res = await fetch('./assign_worker.php', { method:'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        var json = await res.json();
        if(json && json.success){
          alert(json.message || 'Worker assigned.');
          hideAssignModal();
          // simple UI update: reload to reflect new assignment counts
          location.reload();
        } else {
          alert(json.message || 'Assignment failed.');
        }
      }catch(e){
        alert('Network error: ' + e.message);
      }finally{
        btn.disabled = false; btn.textContent = 'Assign';
      }
    }
    // Attach assign buttons to project cards
    document.addEventListener('DOMContentLoaded', function(){
      var cards = document.querySelectorAll('.project-card');
      cards.forEach(function(card){
        var idText = card.querySelector('.muted');
        // attempt to read project id from the muted line
        var projectId = null;
        if(idText){
          var m = idText.textContent.match(/Project ID:\s*(\d+)/);
          if(m) projectId = m[1];
        }
        if(projectId){
          var assignBtn = document.createElement('button');
          assignBtn.className = 'btn outline';
          assignBtn.style.marginLeft = '8px';
          assignBtn.textContent = 'Assign Worker';
          assignBtn.onclick = function(){
            var nameEl = card.querySelector('.project-name');
            openAssignModal(projectId, nameEl ? nameEl.textContent.trim() : 'Project ' + projectId);
          };
          var actions = card.querySelector('.card-actions');
          if(actions) actions.appendChild(assignBtn);
        }
      });
    });
  </script>

  <?php require_once __DIR__ . '/../Common/footer.php'; ?>
</body>
</html>