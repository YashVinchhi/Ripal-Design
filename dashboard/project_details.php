<?php
/**
 * PROJECT DETAILS - Dynamic Database-Driven System
 * 
 * USAGE:
 * ------
 * View Project:    project_details.php?id=1
 * Create Project:  project_details.php (no ID parameter)
 * Edit Project:    project_details.php?id=1 (loads data into form)
 * 
 * FEATURES:
 * ---------
 * - Fully dynamic with database integration
 * - Supports multiple projects with different data
 * - Automatic table creation on first run
 * - Dynamic status badges (ongoing/planning/paused/completed)
 * - Auto-generated owner initials
 * - Milestone tracking with status indicators
 * - Team member management
 * - Location mapping with Google Maps
 * - Responsive Tailwind CSS design
 * 
 * DATABASE TABLES:
 * ----------------
 * 1. projects - Main project data
 * 2. project_workers - Team members assigned to projects
 * 3. project_milestones - Timeline milestones
 * 
 * For detailed documentation, see PROJECT_DETAILS_README.md
 */

require_once __DIR__ . '/../includes/init.php';

// Get project ID from URL
$projectId = $_GET['id'] ?? null;
$error = null;

// Initialize database tables
if (isset($pdo) && $pdo instanceof PDO) {
  try {
    // Projects table
    $pdo->exec("CREATE TABLE IF NOT EXISTS projects (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(255) NOT NULL,
      status VARCHAR(50) DEFAULT 'ongoing',
      progress INT DEFAULT 0,
      budget DECIMAL(15,2) DEFAULT NULL,
      due DATE DEFAULT NULL,
      location VARCHAR(255) DEFAULT NULL,
      latitude DOUBLE DEFAULT NULL,
      longitude DOUBLE DEFAULT NULL,
      owner_name VARCHAR(255) DEFAULT NULL,
      owner_contact VARCHAR(100) DEFAULT NULL,
      owner_email VARCHAR(255) DEFAULT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Project workers/team table
    $pdo->exec("CREATE TABLE IF NOT EXISTS project_workers (
      id INT AUTO_INCREMENT PRIMARY KEY,
      project_id INT NOT NULL,
      worker_name VARCHAR(255) NOT NULL,
      worker_role VARCHAR(100) DEFAULT NULL,
      worker_contact VARCHAR(100) DEFAULT NULL,
      FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Project milestones table
    $pdo->exec("CREATE TABLE IF NOT EXISTS project_milestones (
      id INT AUTO_INCREMENT PRIMARY KEY,
      project_id INT NOT NULL,
      title VARCHAR(255) NOT NULL,
      target_date DATE DEFAULT NULL,
      status VARCHAR(50) DEFAULT 'pending',
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
  } catch (Exception $e) {
    error_log('Database initialization failed: ' . $e->getMessage());
  }
}

// Handle POST to create/update project
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $status = trim($_POST['status'] ?? 'ongoing');
  $progress = (int) ($_POST['progress'] ?? 0);
  $progress = max(0, min(100, $progress)); // Clamp between 0-100
  $budget = !empty($_POST['budget']) ? (float)$_POST['budget'] : null;
  $due = $_POST['due'] ?? null;
  $owner_name = trim($_POST['owner_name'] ?? '');
  $owner_contact = trim($_POST['owner_contact'] ?? '');
  $owner_email = trim($_POST['owner_email'] ?? '');
  $location = trim($_POST['location'] ?? '');
  $latitude = is_numeric($_POST['lat'] ?? null) ? (float)$_POST['lat'] : null;
  $longitude = is_numeric($_POST['lng'] ?? null) ? (float)$_POST['lng'] : null;

  try {
    if (isset($pdo) && $pdo instanceof PDO) {
      if (!empty($projectId) && is_numeric($projectId)) {
        // Update existing project
        $stmt = $pdo->prepare('SELECT id FROM projects WHERE id = :id');
        $stmt->execute(['id' => $projectId]);
        
        if ($stmt->fetch()) {
          $upd = $pdo->prepare('UPDATE projects SET name=:name, status=:status, progress=:progress, budget=:budget, due=:due, location=:location, latitude=:latitude, longitude=:longitude, owner_name=:owner_name, owner_contact=:owner_contact, owner_email=:owner_email WHERE id=:id');
          $upd->execute([
            'name' => $name,
            'status' => $status,
            'progress' => $progress,
            'budget' => $budget,
            'due' => $due,
            'location' => $location,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'owner_name' => $owner_name,
            'owner_contact' => $owner_contact,
            'owner_email' => $owner_email,
            'id' => $projectId
          ]);
        } else {
          // Insert with specific ID
          $ins = $pdo->prepare('INSERT INTO projects (id, name, status, progress, budget, due, location, latitude, longitude, owner_name, owner_contact, owner_email) VALUES (:id, :name, :status, :progress, :budget, :due, :location, :latitude, :longitude, :owner_name, :owner_contact, :owner_email)');
          $ins->execute([
            'id' => $projectId,
            'name' => $name,
            'status' => $status,
            'progress' => $progress,
            'budget' => $budget,
            'due' => $due,
            'location' => $location,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'owner_name' => $owner_name,
            'owner_contact' => $owner_contact,
            'owner_email' => $owner_email
          ]);
        }
      } else {
        // Insert new project
        $ins = $pdo->prepare('INSERT INTO projects (name, status, progress, budget, due, location, latitude, longitude, owner_name, owner_contact, owner_email) VALUES (:name, :status, :progress, :budget, :due, :location, :latitude, :longitude, :owner_name, :owner_contact, :owner_email)');
        $ins->execute([
          'name' => $name,
          'status' => $status,
          'progress' => $progress,
          'budget' => $budget,
          'due' => $due,
          'location' => $location,
          'latitude' => $latitude,
          'longitude' => $longitude,
          'owner_name' => $owner_name,
          'owner_contact' => $owner_contact,
          'owner_email' => $owner_email
        ]);
        $projectId = $pdo->lastInsertId();
      }
      
      header('Location: project_details.php?id=' . urlencode($projectId));
      exit;
    }
  } catch (Exception $e) {
    error_log('Project save failed: ' . $e->getMessage());
    $error = 'Failed to save project. Please try again.';
  }
}

// Initialize default project data
$project = [
  'id' => $projectId,
  'name' => '',
  'status' => 'ongoing',
  'address' => '',
  'budget' => '',
  'owner' => ['name' => '', 'contact' => '', 'email' => ''],
  'progress' => 0,
  'due' => date('Y-m-d'),
  'location' => '',
  'latitude' => null,
  'longitude' => null,
  'workers' => [],
  'milestones' => []
];

// Static sample data for demonstration (fallback when DB is unavailable)
$sampleProjects = [
  1 => [
    'id' => 1,
    'name' => 'Renovation — Oak Street Residence',
    'status' => 'ongoing',
    'progress' => 45,
    'budget' => '₹ 45,00,000',
    'budget_raw' => 4500000,
    'due' => '2026-06-30',
    'address' => '123 Oak Street, Rajkot, Gujarat 360001',
    'location' => '123 Oak Street, Rajkot, Gujarat 360001',
    'latitude' => 22.3039,
    'longitude' => 70.8022,
    'owner' => [
      'name' => 'Amitbhai Patel',
      'contact' => '+91 98765 43210',
      'email' => 'amit.patel@example.com'
    ],
    'workers' => [
      ['worker_name' => 'Ramesh Kumar', 'worker_role' => 'Plumber', 'worker_contact' => '+91 98765 11111'],
      ['worker_name' => 'Suresh Bhai', 'worker_role' => 'Electrician', 'worker_contact' => '+91 98765 22222']
    ],
    'milestones' => [
      ['title' => 'Foundation Completion', 'target_date' => '2026-03-15', 'status' => 'completed'],
      ['title' => 'Material Procurement', 'target_date' => '2026-04-20', 'status' => 'active'],
      ['title' => 'Electrical Rough-in', 'target_date' => '2026-05-10', 'status' => 'pending']
    ]
  ],
  2 => [
    'id' => 2,
    'name' => 'New Construction — Satellite Township',
    'status' => 'planning',
    'progress' => 10,
    'budget' => '₹ 1,25,00,000',
    'budget_raw' => 12500000,
    'due' => '2027-03-31',
    'address' => 'Satellite Road, Ahmedabad, Gujarat 380015',
    'location' => 'Satellite Road, Ahmedabad, Gujarat 380015',
    'latitude' => 23.0225,
    'longitude' => 72.5714,
    'owner' => [
      'name' => 'Rajesh Mehta',
      'contact' => '+91 99999 88888',
      'email' => 'rajesh.mehta@example.com'
    ],
    'workers' => [
      ['worker_name' => 'Vijay Shah', 'worker_role' => 'Site Engineer', 'worker_contact' => '+91 98765 33333']
    ],
    'milestones' => [
      ['title' => 'Site Survey', 'target_date' => '2026-03-01', 'status' => 'active'],
      ['title' => 'Design Approval', 'target_date' => '2026-04-15', 'status' => 'pending'],
      ['title' => 'Foundation Work', 'target_date' => '2026-06-01', 'status' => 'pending']
    ]
  ],
  3 => [
    'id' => 3,
    'name' => 'Commercial Complex — City Center',
    'status' => 'completed',
    'progress' => 100,
    'budget' => '₹ 85,00,000',
    'budget_raw' => 8500000,
    'due' => '2026-01-31',
    'address' => 'City Center, Rajkot, Gujarat 360001',
    'location' => 'City Center, Rajkot, Gujarat 360001',
    'latitude' => 22.2912,
    'longitude' => 70.7954,
    'owner' => [
      'name' => 'Priya Shah',
      'contact' => '+91 97654 32100',
      'email' => 'priya.shah@example.com'
    ],
    'workers' => [
      ['worker_name' => 'Mohan Das', 'worker_role' => 'Mason', 'worker_contact' => '+91 98765 44444'],
      ['worker_name' => 'Kiran Patel', 'worker_role' => 'Carpenter', 'worker_contact' => '+91 98765 55555']
    ],
    'milestones' => [
      ['title' => 'Interior Work', 'target_date' => '2025-12-15', 'status' => 'completed'],
      ['title' => 'Final Inspection', 'target_date' => '2026-01-20', 'status' => 'completed'],
      ['title' => 'Handover', 'target_date' => '2026-01-31', 'status' => 'completed']
    ]
  ]
];

// Load project from database
$projectLoaded = false;
if (isset($pdo) && $pdo instanceof PDO && !empty($projectId) && is_numeric($projectId)) {
  try {
    // Load project details
    $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $projectId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
      $project['id'] = $row['id'];
      $project['name'] = $row['name'];
      $project['status'] = $row['status'] ?? 'ongoing';
      $project['progress'] = isset($row['progress']) ? (int)$row['progress'] : 0;
      $project['budget'] = !empty($row['budget']) ? '₹ ' . number_format($row['budget'], 2) : '';
      $project['budget_raw'] = $row['budget'] ?? null;
      $project['due'] = $row['due'] ?? date('Y-m-d');
      $project['address'] = $row['location'] ?? '';
      $project['location'] = $row['location'] ?? '';
      $project['latitude'] = $row['latitude'];
      $project['longitude'] = $row['longitude'];
      $project['owner']['name'] = $row['owner_name'] ?? '';
      $project['owner']['contact'] = $row['owner_contact'] ?? '';
      $project['owner']['email'] = $row['owner_email'] ?? '';
      
      // Load workers
      $workerStmt = $pdo->prepare('SELECT worker_name, worker_role, worker_contact FROM project_workers WHERE project_id = :id');
      $workerStmt->execute(['id' => $projectId]);
      $project['workers'] = $workerStmt->fetchAll(PDO::FETCH_ASSOC);
      
      // Load milestones
      $milestoneStmt = $pdo->prepare('SELECT title, target_date, status FROM project_milestones WHERE project_id = :id ORDER BY target_date ASC');
      $milestoneStmt->execute(['id' => $projectId]);
      $project['milestones'] = $milestoneStmt->fetchAll(PDO::FETCH_ASSOC);
      
      $projectLoaded = true;
    }
  } catch (Exception $e) {
    error_log('Load project failed: ' . $e->getMessage());
  }
}

// Fallback to static sample data if database load failed
if (!$projectLoaded && !empty($projectId) && is_numeric($projectId)) {
  if (isset($sampleProjects[$projectId])) {
    $project = $sampleProjects[$projectId];
    $projectLoaded = true;
  } else {
    $error = 'Project not found.';
  }
}

// Helper function to format currency
function formatCurrency($amount) {
  if (empty($amount)) return '₹ 0';
  return '₹ ' . number_format($amount, 0);
}

// Helper function to format date
function formatDate($date) {
  if (empty($date)) return '';
  $timestamp = strtotime($date);
  return date('M d, Y', $timestamp);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo htmlspecialchars($project['name']); ?> — Project Details</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
  <script>
    tailwind.config = {
      darkMode: "class",
      theme: {
        extend: {
          colors: {
            primary: "#731209",
            secondary: "#fdfcf8",
            "background-light": "#fdfcf8",
            "background-dark": "#121212",
          },
          fontFamily: {
            display: ["Playfair Display", "serif"],
            sans: ["Inter", "sans-serif"],
          },
          borderRadius: {
            DEFAULT: "4px",
          },
        },
      },
    };
  </script>
  <style>
    body {
      font-family: 'Inter', sans-serif;
    }
    .font-serif {
      font-family: 'Playfair Display', serif;
    }
    
    /* Chrome-style tabs */
    .chrome-tabs {
      display: flex;
      gap: 2px;
      background: linear-gradient(to bottom, #e5e7eb 0%, #d1d5db 100%);
      padding: 8px 8px 0 8px;
      border-radius: 8px 8px 0 0;
      overflow-x: auto;
    }
    
    .dark .chrome-tabs {
      background: linear-gradient(to bottom, #1e293b 0%, #0f172a 100%);
    }
    
    .chrome-tab {
      position: relative;
      padding: 12px 24px;
      min-width: 120px;
      text-align: center;
      background: #f3f4f6;
      border-radius: 8px 8px 0 0;
      cursor: pointer;
      transition: all 0.2s;
      color: #64748b;
      font-weight: 500;
      font-size: 0.875rem;
      white-space: nowrap;
      border: 1px solid #e5e7eb;
      border-bottom: none;
      margin-bottom: -1px;
    }
    
    .dark .chrome-tab {
      background: #334155;
      color: #94a3b8;
      border-color: #475569;
    }
    
    .chrome-tab:hover:not(.active) {
      background: #e5e7eb;
    }
    
    .dark .chrome-tab:hover:not(.active) {
      background: #475569;
    }
    
    .chrome-tab.active {
      background: white;
      color: #731209;
      font-weight: 600;
      box-shadow: 0 -2px 4px rgba(0,0,0,0.05);
      z-index: 10;
    }
    
    .dark .chrome-tab.active {
      background: #0f172a;
      color: #731209;
      box-shadow: 0 -2px 4px rgba(0,0,0,0.2);
    }
    
    .chrome-tab.active::before {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: #731209;
    }
    
    .tab-content {
      display: none;
    }
    
    .tab-content.active {
      display: block;
    }
  </style>
</head>
<body class="bg-background-light dark:bg-background-dark text-slate-800 dark:text-slate-200 min-h-screen flex flex-col transition-colors duration-300">
  <?php $HEADER_MODE = 'dashboard'; require_once __DIR__ . '/../Common/header.php'; ?>
  
  <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
    <?php if (!isset($pdo) || !($pdo instanceof PDO)): ?>
    <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-800 rounded-lg text-blue-800 dark:text-blue-200">
      <div class="flex items-center gap-2">
        <span class="material-icons text-sm">info</span>
        <span><strong>Demo Mode:</strong> Database unavailable. Displaying static sample data for demonstration. Configure database in includes/db.php to enable full functionality.</span>
      </div>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg text-red-800 dark:text-red-200">
      <div class="flex items-center gap-2">
        <span class="material-icons text-sm">error</span>
        <span><?php echo htmlspecialchars($error); ?></span>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($project['name'])): ?>
    <div class="mb-8">
      <div class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 mb-2">
        <a class="hover:text-primary" href="dashboard.php">Dashboard</a>
        <span class="material-icons text-xs">chevron_right</span>
        <span>Projects</span>
      </div>
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
        <div>
          <h1 class="text-3xl md:text-4xl font-serif mb-1" style="color: #731209;"><?php echo htmlspecialchars($project['name'] ?: 'New Project'); ?></h1>
          <?php if (!empty($project['address'])): ?>
          <p class="text-slate-500 dark:text-slate-400 flex items-center gap-1">
            <span class="material-icons text-sm">location_on</span>
            <?php echo htmlspecialchars($project['address']); ?>
          </p>
          <?php endif; ?>
        </div>
        <div class="flex gap-2">
          <button class="px-4 py-2 border border-slate-200 dark:border-slate-700 rounded text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
            Edit Project
          </button>
          <button class="px-4 py-2 text-white rounded text-sm font-medium hover:opacity-90 transition-opacity flex items-center gap-2" style="background-color: #731209;">
            <span class="material-icons text-sm">share</span> Share
          </button>
        </div>
      </div>
    </div>

    <!-- Chrome-style Tabs -->
    <div class="chrome-tabs mb-0">
      <div class="chrome-tab active" data-tab="overview">Overview</div>
      <div class="chrome-tab" data-tab="team">Team</div>
      <div class="chrome-tab" data-tab="files">Files</div>
      <div class="chrome-tab" data-tab="activity">Activity</div>
      <div class="chrome-tab" data-tab="drawings">Drawings</div>
    </div>

    <!-- Tab Content Container -->
    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 border-t-0 rounded-b-lg shadow-sm">
      
      <!-- Overview Tab -->
      <div class="tab-content active" id="overview-tab">
        <div class="p-6">
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <div class="lg:col-span-2 space-y-8">
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div class="bg-white dark:bg-slate-900 p-5 rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Total Budget</p>
            <p class="text-2xl font-serif" style="color: #731209;"><?php echo !empty($project['budget']) ? $project['budget'] : '₹ 0'; ?></p>
          </div>
          <div class="bg-white dark:bg-slate-900 p-5 rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Status</p>
            <div class="flex items-center gap-2">
              <?php
                $statusColors = [
                  'ongoing' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
                  'planning' => 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300',
                  'paused' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300',
                  'completed' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300'
                ];
                $statusClass = $statusColors[$project['status']] ?? $statusColors['ongoing'];
              ?>
              <span class="px-2 py-0.5 <?php echo $statusClass; ?> rounded text-xs font-bold uppercase"><?php echo htmlspecialchars($project['status']); ?></span>
            </div>
          </div>
          <div class="bg-white dark:bg-slate-900 p-5 rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Progress</p>
            <div class="flex items-center gap-3">
              <div class="flex-grow bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                <div class="h-full" style="width: <?php echo intval($project['progress'] ?? 0); ?>%; background-color: #731209;"></div>
              </div>
              <span class="text-sm font-semibold"><?php echo intval($project['progress'] ?? 0); ?>%</span>
            </div>
          </div>
        </div>

        <!-- Project Details Form -->
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm">
          <div class="p-6 border-b border-slate-200 dark:border-slate-800">
            <h2 class="text-xl font-serif text-slate-800 dark:text-slate-100">Project Details</h2>
          </div>
          <form method="post">
            <div class="p-6 space-y-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-1 md:col-span-2">
                  <label class="text-xs font-semibold text-slate-500 uppercase">Project Name</label>
                  <input class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded text-sm focus:ring-primary focus:border-primary" type="text" name="name" value="<?php echo htmlspecialchars($project['name']); ?>" required>
                </div>
                <div class="space-y-1">
                  <label class="text-xs font-semibold text-slate-500 uppercase">Budget (₹)</label>
                  <input class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded text-sm focus:ring-primary focus:border-primary" type="number" name="budget" step="0.01" value="<?php echo htmlspecialchars($project['budget_raw'] ?? ''); ?>" placeholder="45,00,000">
                </div>
                <div class="space-y-1">
                  <label class="text-xs font-semibold text-slate-500 uppercase">Status</label>
                  <select class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded text-sm focus:ring-primary focus:border-primary" name="status">
                    <option value="ongoing" <?php if($project['status']=='ongoing') echo 'selected'; ?>>Ongoing</option>
                    <option value="planning" <?php if($project['status']=='planning') echo 'selected'; ?>>Planning</option>
                    <option value="paused" <?php if($project['status']=='paused') echo 'selected'; ?>>On Hold</option>
                    <option value="completed" <?php if($project['status']=='completed') echo 'selected'; ?>>Completed</option>
                  </select>
                </div>
                <div class="space-y-1">
                  <label class="text-xs font-semibold text-slate-500 uppercase">Due Date</label>
                  <input class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded text-sm focus:ring-primary focus:border-primary" type="date" name="due" value="<?php echo htmlspecialchars($project['due'] ?? date('Y-m-d')); ?>">
                </div>
                <div class="space-y-1">
                  <label class="text-xs font-semibold text-slate-500 uppercase">Progress (%)</label>
                  <input class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded text-sm focus:ring-primary focus:border-primary" type="number" name="progress" min="0" max="100" value="<?php echo intval($project['progress'] ?? 0); ?>">
                </div>
              </div>

              <!-- Owner Details -->
              <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Owner Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-500 uppercase">Owner Name</label>
                    <input class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded text-sm focus:ring-primary focus:border-primary" type="text" name="owner_name" value="<?php echo htmlspecialchars($project['owner']['name']); ?>">
                  </div>
                  <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-500 uppercase">Owner Contact</label>
                    <input class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded text-sm focus:ring-primary focus:border-primary" type="tel" name="owner_contact" value="<?php echo htmlspecialchars($project['owner']['contact']); ?>">
                  </div>
                  <div class="space-y-1 md:col-span-2">
                    <label class="text-xs font-semibold text-slate-500 uppercase">Owner Email</label>
                    <input class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded text-sm focus:ring-primary focus:border-primary" type="email" name="owner_email" value="<?php echo htmlspecialchars($project['owner']['email']); ?>">
                  </div>
                </div>
              </div>

              <div class="space-y-1">
                <label class="text-xs font-semibold text-slate-500 uppercase">Site Location (Address)</label>
                <input class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded text-sm focus:ring-primary focus:border-primary" type="text" name="location" id="location-input" value="<?php echo htmlspecialchars($project['address'] ?? ($project['location'] ?? '')); ?>">
              </div>
              
              <!-- Location Mapping -->
              <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Location Map</h3>
                <div class="bg-slate-100 dark:bg-slate-800 rounded-lg overflow-hidden" style="aspect-ratio: 4/3;">
                  <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d287.47857098438425!2d70.76867685826322!3d22.30597063170977!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3959c983c4b8aeaf%3A0xf7c6e2439ee00a3f!2sNanavati%20Chowk!5e1!3m2!1sen!2sin!4v1771055842937!5m2!1sen!2sin" 
                    width="100%" 
                    height="100%" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade"
                    title="Project Location Map">
                  </iframe>
                </div>
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400 text-center">
                  <span class="material-icons text-xs align-middle">location_on</span>
                  <?php echo htmlspecialchars($project['location'] ?: 'Nanavati Chowk, Rajkot'); ?>
                </p>
              </div>
            </div>
            <div class="p-6 bg-slate-50 dark:bg-slate-800/50 flex justify-end gap-3">
              <button type="button" class="px-6 py-2 border-2 border-slate-300 text-slate-600 dark:text-slate-400 dark:border-slate-600 rounded text-sm font-medium hover:border-slate-400 hover:text-slate-800 transition-colors" onclick="window.location.reload()">Discard Changes</button>
              <button type="submit" class="px-8 py-2 text-white rounded text-sm font-semibold hover:opacity-95 shadow-md" style="background-color: #731209;">Save Project</button>
            </div>
          </form>
        </div>
      </div>

      <!-- Right Column -->
      <div class="space-y-6">
        <!-- Project Owner Card -->
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
          <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Project Owner</h3>
          <?php if (!empty($project['owner']['name'])): ?>
          <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold" style="background-color: #731209;">
              <?php 
                $initials = '';
                $nameParts = explode(' ', $project['owner']['name']);
                if (count($nameParts) >= 2) {
                  $initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
                } else {
                  $initials = strtoupper(substr($project['owner']['name'], 0, 2));
                }
                echo $initials;
              ?>
            </div>
            <div>
              <p class="font-semibold text-slate-800 dark:text-slate-100"><?php echo htmlspecialchars($project['owner']['name']); ?></p>
              <p class="text-sm text-slate-500">Client</p>
            </div>
          </div>
          <div class="space-y-3">
            <?php if (!empty($project['owner']['contact'])): ?>
            <div class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-400">
              <span class="material-icons text-sm">phone</span>
              <span><?php echo htmlspecialchars($project['owner']['contact']); ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($project['owner']['email'])): ?>
            <div class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-400">
              <span class="material-icons text-sm">email</span>
              <span><?php echo htmlspecialchars($project['owner']['email']); ?></span>
            </div>
            <?php endif; ?>
          </div>
          <button class="w-full mt-6 py-2 border border-slate-200 dark:border-slate-700 text-sm font-medium rounded hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
            View Contact Details
          </button>
          <?php else: ?>
          <p class="text-sm text-slate-500 dark:text-slate-400 text-center py-4">No owner information available</p>
          <?php endif; ?>
        </div>

        <!-- Upcoming Milestones -->
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
          <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Upcoming Milestones</h3>
          <?php if (!empty($project['milestones'])): ?>
          <div class="space-y-4">
            <?php foreach($project['milestones'] as $milestone): ?>
            <div class="flex gap-3">
              <div class="mt-1 w-2 h-2 rounded-full shrink-0" style="<?php echo ($milestone['status'] === 'completed') ? 'background-color: #10B981;' : (($milestone['status'] === 'active') ? 'background-color: #731209;' : 'background-color: #cbd5e1;'); ?>"></div>
              <div>
                <p class="text-sm font-medium"><?php echo htmlspecialchars($milestone['title']); ?></p>
                <p class="text-xs text-slate-500"><?php echo formatDate($milestone['target_date']); ?></p>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php else: ?>
          <p class="text-sm text-slate-500 dark:text-slate-400 text-center py-4">No milestones defined yet</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
        </div>
      </div>
      
      <!-- Team Tab -->
      <div class="tab-content" id="team-tab">
        <div class="p-6">
          <div class="mb-6 flex justify-between items-center">
            <h2 class="text-xl font-serif text-slate-800 dark:text-slate-100">Team Members</h2>
            <button class="px-4 py-2 text-white rounded text-sm font-medium hover:opacity-90 transition-opacity flex items-center gap-2" style="background-color: #731209;">
              <span class="material-icons text-sm">add</span> Add Member
            </button>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php 
            $sampleTeam = !empty($project['workers']) ? $project['workers'] : [
              ['worker_name' => 'Ramesh Kumar', 'worker_role' => 'Plumber', 'worker_contact' => '+91 98765 11111'],
              ['worker_name' => 'Suresh Bhai', 'worker_role' => 'Electrician', 'worker_contact' => '+91 98765 22222'],
              ['worker_name' => 'Mohan Das', 'worker_role' => 'Mason', 'worker_contact' => '+91 98765 33333'],
              ['worker_name' => 'Vijay Shah', 'worker_role' => 'Site Engineer', 'worker_contact' => '+91 98765 44444'],
              ['worker_name' => 'Kiran Patel', 'worker_role' => 'Carpenter', 'worker_contact' => '+91 98765 55555'],
              ['worker_name' => 'Anil Sharma', 'worker_role' => 'Painter', 'worker_contact' => '+91 98765 66666']
            ];
            foreach($sampleTeam as $member): 
              $initials = '';
              $nameParts = explode(' ', $member['worker_name']);
              if (count($nameParts) >= 2) {
                $initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
              } else {
                $initials = strtoupper(substr($member['worker_name'], 0, 2));
              }
            ?>
            <div class="bg-white dark:bg-slate-900 p-5 rounded-lg border border-slate-200 dark:border-slate-800 hover:shadow-md transition-shadow">
              <div class="flex items-start gap-3 mb-3">
                <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold shrink-0" style="background-color: #731209;">
                  <?php echo $initials; ?>
                </div>
                <div class="flex-grow min-w-0">
                  <p class="font-semibold text-slate-800 dark:text-slate-100 truncate"><?php echo htmlspecialchars($member['worker_name']); ?></p>
                  <p class="text-sm text-slate-500"><?php echo htmlspecialchars($member['worker_role']); ?></p>
                </div>
              </div>
              <div class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-400 mb-3">
                <span class="material-icons text-sm">phone</span>
                <span><?php echo htmlspecialchars($member['worker_contact']); ?></span>
              </div>
              <div class="flex gap-2">
                <button class="flex-1 px-3 py-1.5 border border-slate-200 dark:border-slate-700 rounded text-xs font-medium hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                  View Profile
                </button>
                <button class="px-3 py-1.5 border border-slate-200 dark:border-slate-700 rounded text-xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                  <span class="material-icons text-sm">more_vert</span>
                </button>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      
      <!-- Files Tab -->
      <div class="tab-content" id="files-tab">
        <div class="p-6">
          <div class="mb-6 flex justify-between items-center">
            <h2 class="text-xl font-serif text-slate-800 dark:text-slate-100">Project Files</h2>
            <button class="px-4 py-2 text-white rounded text-sm font-medium hover:opacity-90 transition-opacity flex items-center gap-2" style="background-color: #731209;">
              <span class="material-icons text-sm">upload</span> Upload File
            </button>
          </div>
          
          <?php 
          $sampleFiles = [
            ['name' => 'Site Plan.pdf', 'type' => 'PDF', 'size' => '2.4 MB', 'date' => '2026-02-10', 'icon' => 'picture_as_pdf', 'color' => 'text-red-500'],
            ['name' => 'Budget Estimate.xlsx', 'type' => 'Excel', 'size' => '856 KB', 'date' => '2026-02-08', 'icon' => 'description', 'color' => 'text-green-500'],
            ['name' => 'Design Mockup.jpg', 'type' => 'Image', 'size' => '4.2 MB', 'date' => '2026-02-05', 'icon' => 'image', 'color' => 'text-blue-500'],
            ['name' => 'Contract Agreement.pdf', 'type' => 'PDF', 'size' => '1.8 MB', 'date' => '2026-01-28', 'icon' => 'picture_as_pdf', 'color' => 'text-red-500'],
            ['name' => 'Material List.docx', 'type' => 'Word', 'size' => '124 KB', 'date' => '2026-01-25', 'icon' => 'description', 'color' => 'text-blue-600'],
            ['name' => 'Progress Photos.zip', 'type' => 'Archive', 'size' => '15.6 MB', 'date' => '2026-02-12', 'icon' => 'folder_zip', 'color' => 'text-yellow-600']
          ];
          ?>
          
          <div class="space-y-2">
            <?php foreach($sampleFiles as $file): ?>
            <div class="flex items-center gap-4 p-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg hover:shadow-md transition-shadow">
              <div class="<?php echo $file['color']; ?>">
                <span class="material-icons text-3xl"><?php echo $file['icon']; ?></span>
              </div>
              <div class="flex-grow min-w-0">
                <p class="font-medium text-slate-800 dark:text-slate-100 truncate"><?php echo htmlspecialchars($file['name']); ?></p>
                <p class="text-sm text-slate-500"><?php echo htmlspecialchars($file['type']); ?> • <?php echo htmlspecialchars($file['size']); ?> • Uploaded <?php echo formatDate($file['date']); ?></p>
              </div>
              <div class="flex gap-2">
                <button class="px-3 py-1.5 border border-slate-200 dark:border-slate-700 rounded text-xs font-medium hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                  Download
                </button>
                <button class="px-3 py-1.5 border border-slate-200 dark:border-slate-700 rounded text-xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                  <span class="material-icons text-sm">more_vert</span>
                </button>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      
      <!-- Activity Tab -->
      <div class="tab-content" id="activity-tab">
        <div class="p-6">
          <h2 class="text-xl font-serif text-slate-800 dark:text-slate-100 mb-6">Recent Activity</h2>
          
          <?php 
          $sampleActivity = [
            ['user' => 'Ramesh Kumar', 'action' => 'completed task', 'item' => 'Plumbing Installation', 'time' => '2 hours ago', 'icon' => 'check_circle', 'color' => 'text-green-500'],
            ['user' => 'Admin', 'action' => 'uploaded file', 'item' => 'Progress Photos.zip', 'time' => '4 hours ago', 'icon' => 'upload_file', 'color' => 'text-blue-500'],
            ['user' => 'Suresh Bhai', 'action' => 'updated status', 'item' => 'Electrical Rough-in', 'time' => '1 day ago', 'icon' => 'update', 'color' => 'text-yellow-600'],
            ['user' => 'Vijay Shah', 'action' => 'added comment', 'item' => 'Foundation inspection passed', 'time' => '2 days ago', 'icon' => 'comment', 'color' => 'text-purple-500'],
            ['user' => 'Admin', 'action' => 'created milestone', 'item' => 'Material Procurement', 'time' => '3 days ago', 'icon' => 'flag', 'color' => 'text-primary'],
            ['user' => 'Mohan Das', 'action' => 'joined team', 'item' => 'as Mason', 'time' => '5 days ago', 'icon' => 'person_add', 'color' => 'text-teal-500']
          ];
          ?>
          
          <div class="space-y-4">
            <?php foreach($sampleActivity as $activity): ?>
            <div class="flex gap-4 p-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg">
              <div class="<?php echo $activity['color']; ?> shrink-0">
                <span class="material-icons"><?php echo $activity['icon']; ?></span>
              </div>
              <div class="flex-grow">
                <p class="text-slate-800 dark:text-slate-100">
                  <span class="font-semibold"><?php echo htmlspecialchars($activity['user']); ?></span>
                  <span class="text-slate-600 dark:text-slate-400"> <?php echo htmlspecialchars($activity['action']); ?> </span>
                  <span class="font-medium"><?php echo htmlspecialchars($activity['item']); ?></span>
                </p>
                <p class="text-sm text-slate-500 mt-1"><?php echo htmlspecialchars($activity['time']); ?></p>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      
      <!-- Drawings Tab -->
      <div class="tab-content" id="drawings-tab">
        <div class="p-6">
          <div class="mb-6 flex justify-between items-center">
            <h2 class="text-xl font-serif text-slate-800 dark:text-slate-100">Technical Drawings</h2>
            <button class="px-4 py-2 text-white rounded text-sm font-medium hover:opacity-90 transition-opacity flex items-center gap-2" style="background-color: #731209;">
              <span class="material-icons text-sm">add</span> Upload Drawing
            </button>
          </div>
          
          <?php 
          $sampleDrawings = [
            ['name' => 'Floor Plan - Ground Floor', 'version' => 'v2.3', 'date' => '2026-02-10', 'status' => 'Approved'],
            ['name' => 'Elevation - Front View', 'version' => 'v1.8', 'date' => '2026-02-08', 'status' => 'Under Review'],
            ['name' => 'Electrical Layout', 'version' => 'v3.1', 'date' => '2026-02-05', 'status' => 'Approved'],
            ['name' => 'Plumbing Schematic', 'version' => 'v2.0', 'date' => '2026-01-30', 'status' => 'Approved'],
            ['name' => 'Structural Details', 'version' => 'v1.5', 'date' => '2026-01-28', 'status' => 'Revision Needed'],
            ['name' => 'Site Layout Plan', 'version' => 'v4.2', 'date' => '2026-01-25', 'status' => 'Approved']
          ];
          ?>
          
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach($sampleDrawings as $drawing): 
              $statusColors = [
                'Approved' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
                'Under Review' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300',
                'Revision Needed' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300'
              ];
              $statusClass = $statusColors[$drawing['status']] ?? '';
            ?>
            <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg overflow-hidden hover:shadow-md transition-shadow">
              <div class="aspect-video bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                <span class="material-icons text-6xl text-slate-300">architecture</span>
              </div>
              <div class="p-4">
                <h3 class="font-semibold text-slate-800 dark:text-slate-100 mb-1"><?php echo htmlspecialchars($drawing['name']); ?></h3>
                <p class="text-sm text-slate-500 mb-2"><?php echo htmlspecialchars($drawing['version']); ?> • <?php echo formatDate($drawing['date']); ?></p>
                <span class="inline-block px-2 py-0.5 <?php echo $statusClass; ?> rounded text-xs font-medium mb-3">
                  <?php echo htmlspecialchars($drawing['status']); ?>
                </span>
                <div class="flex gap-2">
                  <button class="flex-1 px-3 py-1.5 text-white rounded text-xs font-medium hover:opacity-90 transition-opacity" style="background-color: #731209;">
                    View
                  </button>
                  <button class="px-3 py-1.5 border border-slate-200 dark:border-slate-700 rounded text-xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                    <span class="material-icons text-sm">more_vert</span>
                  </button>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      
    </div>
    
    <?php else: ?>
    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-12 text-center">
      <span class="material-icons text-6xl text-slate-300 mb-4">construction</span>
      <h2 class="text-2xl font-serif text-slate-800 dark:text-slate-100 mb-2">Project Not Found</h2>
      <p class="text-slate-500 dark:text-slate-400 mb-6">The requested project could not be found or does not exist.</p>
      <?php if (!isset($pdo) || !($pdo instanceof PDO)): ?>
      <p class="text-sm text-slate-600 dark:text-slate-300 mb-4">Try viewing sample projects: 
        <a href="?id=1" class="text-primary hover:underline">Project 1</a>, 
        <a href="?id=2" class="text-primary hover:underline">Project 2</a>, or 
        <a href="?id=3" class="text-primary hover:underline">Project 3</a>
      </p>
      <?php endif; ?>
      <a href="dashboard.php" class="inline-flex items-center gap-2 px-6 py-3 text-white rounded hover:opacity-90 transition-opacity" style="background-color: #731209;">
        <span class="material-icons text-sm">arrow_back</span>
        Back to Dashboard
      </a>
    </div>
    <?php endif; ?>
  </main>

  <?php require_once __DIR__ . '/../Common/footer.php'; ?>
  
  <script>
    // Tab switching functionality
    document.querySelectorAll('.chrome-tab').forEach(tab => {
      tab.addEventListener('click', function() {
        // Remove active class from all tabs and contents
        document.querySelectorAll('.chrome-tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        
        // Add active class to clicked tab
        this.classList.add('active');
        
        // Show corresponding content
        const tabName = this.getAttribute('data-tab');
        document.getElementById(tabName + '-tab').classList.add('active');
      });
    });
  </script>
</body>
</html>