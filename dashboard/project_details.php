<?php
/**
 * Project Details Page
 * Displays comprehensive project information with tabs for Overview, Team, Files, Activity, and Drawings
 */

require_once __DIR__ . '/../includes/init.php';

// Get project ID from URL
$projectId = $_GET['id'] ?? null;
$error = null;
$success = null;

// Helper function for date formatting
function formatDate($dateString) {
  if (empty($dateString)) return 'N/A';
  $date = strtotime($dateString);
  return date('M d, Y', $date);
}

// Create tables if they don't exist
if (isset($pdo) && $pdo instanceof PDO) {
  try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS projects (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(255) NOT NULL,
      status ENUM('planning', 'ongoing', 'paused', 'completed') DEFAULT 'ongoing',
      budget DECIMAL(15,2),
      progress INT DEFAULT 0,
      due DATE,
      location TEXT,
      address TEXT,
      owner_name VARCHAR(255),
      owner_contact VARCHAR(50),
      owner_email VARCHAR(255),
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS project_workers (
      id INT AUTO_INCREMENT PRIMARY KEY,
      project_id INT NOT NULL,
      worker_name VARCHAR(255),
      worker_role VARCHAR(100),
      worker_contact VARCHAR(50),
      FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS project_milestones (
      id INT AUTO_INCREMENT PRIMARY KEY,
      project_id INT NOT NULL,
      title VARCHAR(255) NOT NULL,
      target_date DATE,
      status ENUM('active', 'completed', 'pending') DEFAULT 'pending',
      FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS project_files (
      id INT AUTO_INCREMENT PRIMARY KEY,
      project_id INT NOT NULL,
      name VARCHAR(255) NOT NULL,
      type VARCHAR(50),
      size VARCHAR(20),
      file_path VARCHAR(500),
      uploaded_by VARCHAR(255),
      uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS project_activity (
      id INT AUTO_INCREMENT PRIMARY KEY,
      project_id INT NOT NULL,
      user VARCHAR(255) NOT NULL,
      action VARCHAR(100) NOT NULL,
      item VARCHAR(255),
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS project_drawings (
      id INT AUTO_INCREMENT PRIMARY KEY,
      project_id INT NOT NULL,
      name VARCHAR(255) NOT NULL,
      version VARCHAR(20),
      status ENUM('Approved', 'Under Review', 'Revision Needed') DEFAULT 'Under Review',
      file_path VARCHAR(500),
      uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
    )");

  } catch (PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
  }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($pdo) && $pdo instanceof PDO) {
  $name = $_POST['name'] ?? '';
  $status = $_POST['status'] ?? 'ongoing';
  $budget = $_POST['budget'] ?? 0;
  $progress = $_POST['progress'] ?? 0;
  $due = $_POST['due'] ?? null;
  $location = $_POST['location'] ?? '';
  $ownerName = $_POST['owner_name'] ?? '';
  $ownerContact = $_POST['owner_contact'] ?? '';
  $ownerEmail = $_POST['owner_email'] ?? '';

  if (empty($name)) {
    $error = 'Project name is required';
  } else {
    try {
      if ($projectId) {
        // Update existing project
        $stmt = $pdo->prepare('
          UPDATE projects 
          SET name = :name, status = :status, budget = :budget, 
              progress = :progress, due = :due, location = :location, address = :location,
              owner_name = :owner_name, owner_contact = :owner_contact, owner_email = :owner_email
          WHERE id = :id
        ');
        $stmt->execute([
          'id' => $projectId,
          'name' => $name,
          'status' => $status,
          'budget' => $budget,
          'progress' => $progress,
          'due' => $due,
          'location' => $location,
          'owner_name' => $ownerName,
          'owner_contact' => $ownerContact,
          'owner_email' => $ownerEmail
        ]);
        $success = "Project updated successfully!";
        
        // Log activity
        $activityStmt = $pdo->prepare('
          INSERT INTO project_activity (project_id, user, action, item, created_at)
          VALUES (:project_id, :user, :action, :item, NOW())
        ');
        $activityStmt->execute([
          'project_id' => $projectId,
          'user' => $_SESSION['user_name'] ?? 'Admin',
          'action' => 'updated project',
          'item' => 'Project details'
        ]);
      } else {
        // Create new project
        $stmt = $pdo->prepare('
          INSERT INTO projects (name, status, budget, progress, due, location, address, owner_name, owner_contact, owner_email)
          VALUES (:name, :status, :budget, :progress, :due, :location, :location, :owner_name, :owner_contact, :owner_email)
        ');
        $stmt->execute([
          'name' => $name,
          'status' => $status,
          'budget' => $budget,
          'progress' => $progress,
          'due' => $due,
          'location' => $location,
          'owner_name' => $ownerName,
          'owner_contact' => $ownerContact,
          'owner_email' => $ownerEmail
        ]);
        $projectId = $pdo->lastInsertId();
        
        // Log activity for new project
        $activityStmt = $pdo->prepare('
          INSERT INTO project_activity (project_id, user, action, item, created_at)
          VALUES (:project_id, :user, :action, :item, NOW())
        ');
        $activityStmt->execute([
          'project_id' => $projectId,
          'user' => $_SESSION['user_name'] ?? 'Admin',
          'action' => 'created project',
          'item' => $name
        ]);
        
        header("Location: project_details.php?id=$projectId");
        exit;
      }
    } catch (PDOException $e) {
      $error = "Database Error: " . $e->getMessage();
    }
  }
}

// Load project data
$project = null;
if ($projectId && isset($pdo) && $pdo instanceof PDO) {
  try {
    $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = :id');
    $stmt->execute(['id' => $projectId]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($project) {
      // Load workers
      $stmt = $pdo->prepare('SELECT * FROM project_workers WHERE project_id = :id');
      $stmt->execute(['id' => $projectId]);
      $project['workers'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
      
      // Load milestones
      $stmt = $pdo->prepare('SELECT * FROM project_milestones WHERE project_id = :id ORDER BY target_date ASC');
      $stmt->execute(['id' => $projectId]);
      $project['milestones'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
      
      // Load project files
      $stmt = $pdo->prepare('SELECT * FROM project_files WHERE project_id = :id ORDER BY uploaded_at DESC');
      $stmt->execute(['id' => $projectId]);
      $project['files'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
      
      // Load activity log
      $stmt = $pdo->prepare('SELECT * FROM project_activity WHERE project_id = :id ORDER BY created_at DESC LIMIT 20');
      $stmt->execute(['id' => $projectId]);
      $project['activities'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
      
      // Load drawings
      $stmt = $pdo->prepare('SELECT * FROM project_drawings WHERE project_id = :id ORDER BY uploaded_at DESC');
      $stmt->execute(['id' => $projectId]);
      $project['drawings'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
      
      // Format owner data
      $project['owner'] = [
        'name' => $project['owner_name'] ?? '',
        'contact' => $project['owner_contact'] ?? '',
        'email' => $project['owner_email'] ?? ''
      ];
    }
  } catch (PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
  }
}

// Sample data fallback
if (!$project) {
  $project = [
    'id' => $projectId ?? 1,
    'name' => 'Shanti Sadan',
    'status' => 'ongoing',
    'budget' => 4500000,
    'progress' => 45,
    'due' => date('Y-m-d', strtotime('+30 days')),
    'location' => 'Jasal Complex, Nanavati Chowk, Rajkot',
    'address' => 'Jasal Complex, Nanavati Chowk, Rajkot',
    'owner' => [
      'name' => 'Amitbhai Patel',
      'contact' => '+91 98765 43210',
      'email' => 'amit.patel@example.com'
    ],
    'workers' => [
      ['worker_name' => 'Rameshbhai Patel', 'worker_role' => 'Plumber', 'worker_contact' => '+91 98765 11111'],
      ['worker_name' => 'Sureshbhai', 'worker_role' => 'Electrician', 'worker_contact' => '+91 98765 22222'],
      ['worker_name' => 'Mohanbhai Ahir', 'worker_role' => 'Mason', 'worker_contact' => '+91 98765 33333'],
      ['worker_name' => 'Vijaybhai Shah', 'worker_role' => 'Site Engineer', 'worker_contact' => '+91 98765 44444'],
      ['worker_name' => 'Kiranbhai Patel', 'worker_role' => 'Carpenter', 'worker_contact' => '+91 98765 55555'],
      ['worker_name' => 'Anilbhai Sharma', 'worker_role' => 'Painter', 'worker_contact' => '+91 98765 66666']
    ],
    'milestones' => [
      ['title' => 'Foundation Completion', 'target_date' => '2026-02-28', 'status' => 'active'],
      ['title' => 'Material Procurement', 'target_date' => '2026-03-15', 'status' => 'pending'],
      ['title' => 'Electrical Rough-in', 'target_date' => '2026-04-05', 'status' => 'pending']
    ],
    'files' => [
      ['id' => 1, 'name' => 'Site Plan.pdf', 'type' => 'PDF', 'size' => '2.4 MB', 'uploaded_at' => '2026-02-10 14:30:00', 'uploaded_by' => 'Admin', 'file_path' => '#'],
      ['id' => 2, 'name' => 'Budget Estimate.xlsx', 'type' => 'Excel', 'size' => '856 KB', 'uploaded_at' => '2026-02-08 10:15:00', 'uploaded_by' => 'Amit Patel', 'file_path' => '#'],
      ['id' => 3, 'name' => 'Design Mockup.jpg', 'type' => 'Image', 'size' => '4.2 MB', 'uploaded_at' => '2026-02-05 16:45:00', 'uploaded_by' => 'Architect', 'file_path' => '#'],
      ['id' => 4, 'name' => 'Contract Agreement.pdf', 'type' => 'PDF', 'size' => '1.8 MB', 'uploaded_at' => '2026-01-28 09:00:00', 'uploaded_by' => 'Legal Team', 'file_path' => '#']
    ],
    'activities' => [
      ['id' => 1, 'user' => 'Rameshbhai Patel', 'action' => 'completed task', 'item' => 'Plumbing Installation', 'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))],
      ['id' => 2, 'user' => 'Admin', 'action' => 'uploaded file', 'item' => 'Progress Photos.zip', 'created_at' => date('Y-m-d H:i:s', strtotime('-4 hours'))],
      ['id' => 3, 'user' => 'Sureshbhai', 'action' => 'updated status', 'item' => 'Electrical Rough-in', 'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))],
      ['id' => 4, 'user' => 'Vijaybhai Shah', 'action' => 'added comment', 'item' => 'Foundation inspection passed', 'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))]
    ],
    'drawings' => [
      ['id' => 1, 'name' => 'Floor Plan - Ground Floor', 'version' => 'v2.3', 'uploaded_at' => '2026-02-10 12:00:00', 'status' => 'Approved', 'file_path' => '#'],
      ['id' => 2, 'name' => 'Elevation - Front View', 'version' => 'v1.8', 'uploaded_at' => '2026-02-08 11:30:00', 'status' => 'Under Review', 'file_path' => '#'],
      ['id' => 3, 'name' => 'Electrical Layout', 'version' => 'v3.1', 'uploaded_at' => '2026-02-05 14:20:00', 'status' => 'Approved', 'file_path' => '#'],
      ['id' => 4, 'name' => 'Plumbing Schematic', 'version' => 'v2.0', 'uploaded_at' => '2026-01-30 09:45:00', 'status' => 'Approved', 'file_path' => '#']
    ]
  ];
}

// Format budget for display
$budgetFormatted = '₹ ' . number_format($project['budget'] ?? 0, 0, '.', ',');

// Status badge colors
$statusColors = [
  'planning' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300',
  'ongoing' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
  'paused' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300',
  'completed' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300'
];
$statusClass = $statusColors[$project['status']] ?? $statusColors['ongoing'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Project Details - <?php echo htmlspecialchars($project['name']); ?> - Ripal Design</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&amp;family=Inter:wght@300;400;500;600&amp;display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "rajkot-rust": "#94180C",
                        "foundation-grey": "#2D2D2D",
                        "canvas-white": "#F9FAFB",
                        primary: "#94180C",
                        secondary: "#F9FAFB",
                        "background-light": "#F9FAFB",
                        "background-dark": "#121212",
                        "slate-accent": "#334155",
                        "approval-green": "#15803D",
                        "pending-amber": "#B45309",
                    },
                    fontFamily: {
                        serif: ["Playfair Display", "serif"],
                        sans: ["Inter", "sans-serif"],
                    },
                    boxShadow: {
                        "premium": "0 10px 30px rgba(0, 0, 0, 0.05)",
                        "premium-hover": "0 20px 40px rgba(0, 0, 0, 0.1)",
                    },
                    borderRadius: {
                        DEFAULT: "4px",
                    },
                },
            },
        };
    </script>
    <style>
        :root {
            --bg-dark: #050505;
            --bg-panel: #111;
            /* Override Bootstrap primary color */
            --bs-primary: #731209;
            --bs-primary-rgb: 115, 18, 9;
            --bs-link-color: #731209;
            --bs-link-hover-color: #5a0e07;
        }

        body {
            font-family: 'Inter', sans-serif;
        }

        .font-serif {
            font-family: 'Playfair Display', serif;
        }

        /* Footer uses Cormorant Garamond to match other pages */
        footer .font-serif,
        footer h2.font-serif,
        footer h3.font-serif {
            font-family: 'Cormorant Garamond', serif;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Override Bootstrap primary color (#0d6efd) with brand color (#731209) */
        .btn-primary,
        .btn-primary:hover,
        .btn-primary:focus,
        .btn-primary:active,
        .btn-outline-primary,
        .btn-outline-primary:hover,
        .bg-primary,
        .text-primary,
        .border-primary {
            --bs-primary: #731209 !important;
            --bs-primary-rgb: 115, 18, 9 !important;
        }

        .btn-primary {
            background-color: #731209 !important;
            border-color: #731209 !important;
        }

        .btn-primary:hover,
        .btn-primary:focus,
        .btn-primary:active {
            background-color: #5a0e07 !important;
            border-color: #5a0e07 !important;
        }

        .btn-outline-primary {
            color: #731209 !important;
            border-color: #731209 !important;
        }

        .btn-outline-primary:hover {
            background-color: #731209 !important;
            border-color: #731209 !important;
            color: #fff !important;
        }

        .text-primary {
            color: #731209 !important;
        }

        .bg-primary {
            background-color: #731209 !important;
        }

        .border-primary {
            border-color: #731209 !important;
        }

        a {
            color: #731209;
        }

        a:hover {
            color: #5a0e07;
        }

        /* Footer-specific styles to match other pages */
        footer.site-footer a,
        footer.site-footer .btn-link,
        footer.site-footer a.text-primary {
            color: #731209 !important;
        }

        footer.site-footer .text-secondary {
            color: rgba(255, 255, 255, 0.62) !important;
        }
    </style>
</head>

<body
    class="bg-background-light dark:bg-background-dark text-slate-800 dark:text-slate-200 min-h-screen flex flex-col transition-colors duration-300">
    
    <?php 
    // Allow only header/footer specific external CSS/JS, block all other external resources
    $DISABLE_EXTERNAL_CSS = true;
    $HEADER_MODE = 'dashboard';
    require_once __DIR__ . '/../Common/header.php'; 
    ?>

    <?php if ($error): ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded">
            <?php echo htmlspecialchars($error); ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 px-4 py-3 rounded">
            <?php echo htmlspecialchars($success); ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Unified Dark Portal Header -->
    <header class="bg-foundation-grey text-white pt-24 pb-12 px-4 shadow-lg">
        <div class="max-w-7xl mx-auto flex flex-col">
            <div class="flex items-center gap-2 text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em] mb-3">
                <a href="dashboard.php" class="hover:text-rajkot-rust transition-colors flex items-center gap-1">
                    <i data-lucide="layout-grid" class="w-3 h-3"></i> Dashboard
                </a>
                <i data-lucide="chevron-right" class="w-3 h-3 text-gray-300"></i>
                <span class="text-rajkot-rust">Project Details</span>
            </div>
            
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <h1 class="text-4xl font-serif font-bold text-white"><?php echo htmlspecialchars($project['name']); ?></h1>
                    <p class="text-gray-400 mt-2 flex items-center gap-1">
                        <i data-lucide="map-pin" class="w-4 h-4 text-rajkot-rust"></i>
                        <?php echo htmlspecialchars($project['location'] ?? $project['address'] ?? 'Location not set'); ?>
                    </p>
                </div>
                <div class="flex gap-2">
                    <button
                        class="px-6 py-2.5 bg-white/10 border border-white/20 text-white rounded text-sm font-medium hover:bg-white/20 transition-all flex items-center gap-2"
                        onclick="window.scrollTo({top: document.querySelector('form').offsetTop - 100, behavior: 'smooth'})">
                        <i data-lucide="edit-3" class="w-4 h-4"></i> Edit Project
                    </button>
                    <button
                        class="px-6 py-2.5 bg-rajkot-rust text-white rounded text-sm font-semibold hover:bg-red-700 transition-all shadow-lg flex items-center gap-2 active:scale-95">
                        <i data-lucide="share-2" class="w-4 h-4"></i> Share
                    </button>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 w-full">

        <!-- Tab Navigation -->
        <div class="flex border-b border-slate-200 dark:border-slate-800 mb-8 overflow-x-auto">
            <a class="tab-link px-6 py-3 border-b-2 border-primary text-primary font-medium text-sm whitespace-nowrap cursor-pointer active"
                data-tab="overview">Overview</a>
            <a class="tab-link px-6 py-3 border-b-2 border-transparent text-slate-500 dark:text-slate-400 hover:text-primary transition-colors font-medium text-sm whitespace-nowrap cursor-pointer"
                data-tab="team">Team</a>
            <a class="tab-link px-6 py-3 border-b-2 border-transparent text-slate-500 dark:text-slate-400 hover:text-primary transition-colors font-medium text-sm whitespace-nowrap cursor-pointer"
                data-tab="files">Files</a>
            <a class="tab-link px-6 py-3 border-b-2 border-transparent text-slate-500 dark:text-slate-400 hover:text-primary transition-colors font-medium text-sm whitespace-nowrap cursor-pointer"
                data-tab="activity">Activity</a>
            <a class="tab-link px-6 py-3 border-b-2 border-transparent text-slate-500 dark:text-slate-400 hover:text-primary transition-colors font-medium text-sm whitespace-nowrap cursor-pointer"
                data-tab="drawings">Drawings</a>
        </div>

        <!-- Overview Tab -->
        <div class="tab-content active" id="overview-tab">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 space-y-8">
                    <!-- Summary Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            class="bg-white dark:bg-slate-900 p-5 rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm">
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Total Budget</p>
                            <p class="text-2xl font-serif text-primary"><?php echo $budgetFormatted; ?></p>
                        </div>
                        <div
                            class="bg-white dark:bg-slate-900 p-5 rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm">
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Status</p>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 <?php echo $statusClass; ?> rounded text-xs font-bold uppercase">
                                    <?php echo htmlspecialchars($project['status']); ?>
                                </span>
                            </div>
                        </div>
                        <div
                            class="bg-white dark:bg-slate-900 p-5 rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm">
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Progress</p>
                            <div class="flex items-center gap-3">
                                <div class="flex-grow bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden">
                                    <div class="bg-primary h-full" style="width: <?php echo intval($project['progress']); ?>%;"></div>
                                </div>
                                <span class="text-sm font-semibold"><?php echo intval($project['progress']); ?>%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Project Details Form -->
                    <div
                        class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 shadow-sm">
                        <div class="p-6 border-b border-slate-200 dark:border-slate-800">
                            <h2 class="text-xl font-serif text-slate-800 dark:text-slate-100">Project Details</h2>
                        </div>
                        <form method="post">
                            <div class="p-6 space-y-6">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div class="space-y-1">
                                        <label class="text-xs font-semibold text-slate-500 uppercase">Project Name</label>
                                        <input
                                            class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded text-sm focus:ring-primary focus:border-primary"
                                            type="text" name="name" value="<?php echo htmlspecialchars($project['name']); ?>" required />
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-xs font-semibold text-slate-500 uppercase">Status</label>
                                        <select
                                            class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded text-sm focus:ring-primary focus:border-primary"
                                            name="status">
                                            <option value="ongoing" <?php echo $project['status'] == 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                                            <option value="planning" <?php echo $project['status'] == 'planning' ? 'selected' : ''; ?>>Planning</option>
                                            <option value="paused" <?php echo $project['status'] == 'paused' ? 'selected' : ''; ?>>On Hold</option>
                                            <option value="completed" <?php echo $project['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        </select>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-xs font-semibold text-slate-500 uppercase">Due Date</label>
                                        <div class="relative">
                                            <input
                                                class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded text-sm focus:ring-primary focus:border-primary"
                                                type="date" name="due" value="<?php echo htmlspecialchars($project['due'] ?? date('Y-m-d')); ?>" />
                                        </div>
                                    </div>
                                    <div class="space-y-1">
                                        <label class="text-xs font-semibold text-slate-500 uppercase">Progress (%)</label>
                                        <input
                                            class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded text-sm focus:ring-primary focus:border-primary"
                                            type="number" name="progress" min="0" max="100" value="<?php echo intval($project['progress'] ?? 0); ?>" />
                                    </div>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-xs font-semibold text-slate-500 uppercase">Site Location
                                        (Address)</label>
                                    <input
                                        class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded text-sm focus:ring-primary focus:border-primary"
                                        type="text" name="location" value="<?php echo htmlspecialchars($project['location'] ?? $project['address'] ?? ''); ?>" />
                                </div>
                                <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
                                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Location Mapping
                                    </h3>
                                    <div
                                        class="bg-slate-100 dark:bg-slate-800 rounded-lg aspect-video flex flex-col items-center justify-center text-slate-400 relative overflow-hidden group">
                                        <div
                                            class="absolute inset-0 bg-[url('../assets/Content/WhatsApp Image 2026-02-02 at 5.51.43 PM (1).jpeg')] bg-cover opacity-20 group-hover:opacity-30 transition-opacity">
                                        </div>
                                        <div class="z-10 text-center px-4">
                                            <span class="material-icons text-4xl mb-2 opacity-50">map</span>
                                            <p class="text-sm font-medium text-slate-600 dark:text-slate-300">Map Preview
                                                Unavailable</p>
                                            <p class="text-xs opacity-60">Click Geocode to refresh coordinates</p>
                                        </div>
                                    </div>
                                    <div class="mt-4 flex flex-col sm:flex-row gap-3">
                                        <input
                                            class="flex-grow bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded text-sm focus:ring-primary focus:border-primary"
                                            placeholder="Search address to geocode..." type="text" />
                                        <button type="button"
                                            class="px-6 py-2 bg-primary text-white rounded text-sm font-medium hover:opacity-90 shadow-md">Geocode</button>
                                    </div>
                                </div>
                            </div>
                            <div class="p-6 bg-slate-50 dark:bg-slate-800/50 flex justify-end gap-3">
                                <button type="button"
                                    class="px-6 py-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-primary"
                                    onclick="window.location.reload()">Discard Changes</button>
                                <button type="submit"
                                    class="px-8 py-2 bg-primary text-white rounded text-sm font-semibold hover:opacity-95 shadow-md">Save
                                    Project</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right Sidebar -->
                <div class="space-y-6">
                    <!-- Project Owner Card -->
                    <div
                        class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Project Owner</h3>
                        <?php if (!empty($project['owner']['name'])): ?>
                        <div class="flex items-center gap-4 mb-4">
                            <div
                                class="w-12 h-12 rounded-full bg-primary flex items-center justify-center text-white font-bold">
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
                        <button
                            class="w-full mt-6 py-2 border border-slate-200 dark:border-slate-700 text-sm font-medium rounded hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                            View Contact Details
                        </button>
                        <?php else: ?>
                        <div
                            class="bg-slate-100 dark:bg-slate-800 rounded-lg flex flex-col items-center justify-center text-slate-400 p-6">
                            <span class="material-icons text-3xl mb-2">person_off</span>
                            <p class="text-sm">No owner assigned</p>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Upcoming Milestones -->
                    <div
                        class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-6 shadow-sm">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Upcoming Milestones</h3>
                        <?php if (!empty($project['milestones'])): ?>
                        <div class="space-y-4">
                            <?php foreach ($project['milestones'] as $milestone): 
                              $dotColor = ($milestone['status'] === 'completed') ? 'bg-green-500' : 
                                         (($milestone['status'] === 'active') ? 'bg-primary' : 'bg-slate-300');
                            ?>
                            <div class="flex gap-3">
                                <div class="mt-1 w-2 h-2 rounded-full <?php echo $dotColor; ?> shrink-0"></div>
                                <div>
                                    <p class="text-sm font-medium"><?php echo htmlspecialchars($milestone['title']); ?></p>
                                    <p class="text-xs text-slate-500"><?php echo formatDate($milestone['target_date']); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div
                            class="bg-slate-100 dark:bg-slate-800 rounded-lg flex flex-col items-center justify-center text-slate-400 p-6">
                            <span class="material-icons text-3xl mb-2">event_busy</span>
                            <p class="text-sm">No milestones yet</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Team Tab -->
        <div class="tab-content" id="team-tab">
            <div class="mb-6 flex justify-between items-center">
                <h2 class="text-xl font-serif text-slate-800 dark:text-slate-100">Team Members</h2>
                <button onclick="showAddTeamMemberModal()"
                    class="px-4 py-2 bg-primary text-white rounded text-sm font-medium hover:opacity-90 transition-opacity flex items-center gap-2">
                    <span class="material-icons text-sm">add</span> Add Member
                </button>
            </div>

            <?php if (!empty($project['workers'])): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($project['workers'] as $member): 
                  $initials = '';
                  $nameParts = explode(' ', $member['worker_name']);
                  if (count($nameParts) >= 2) {
                    $initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
                  } else {
                    $initials = strtoupper(substr($member['worker_name'], 0, 2));
                  }
                ?>
                <div
                    class="bg-white dark:bg-slate-900 p-5 rounded-lg border border-slate-200 dark:border-slate-800 hover:shadow-md transition-shadow">
                    <div class="flex items-start gap-3 mb-3">
                        <div
                            class="w-12 h-12 rounded-full bg-primary flex items-center justify-center text-white font-bold shrink-0">
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
                        <button onclick="viewMemberProfile('<?php echo addslashes($member['worker_name']); ?>', '<?php echo addslashes($member['worker_role']); ?>')"
                            class="flex-1 px-3 py-1.5 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 rounded text-xs font-medium hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                            View Profile
                        </button>
                        <button
                            class="px-3 py-1.5 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 rounded text-xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                            <span class="material-icons text-sm">more_vert</span>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div
                class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-12 text-center">
                <span class="material-icons text-6xl text-slate-300 mb-4">group_off</span>
                <h3 class="text-xl font-serif text-slate-800 dark:text-slate-100 mb-2">No Team Members</h3>
                <p class="text-slate-500 dark:text-slate-400 mb-6">Start building your team by adding members.</p>
                <button onclick="showAddTeamMemberModal()"
                    class="px-6 py-3 bg-primary text-white rounded hover:opacity-90 transition-opacity flex items-center gap-2 mx-auto">
                    <span class="material-icons text-sm">add</span> Add First Member
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Files Tab -->
        <div class="tab-content" id="files-tab">
            <div class="mb-6 flex justify-between items-center">
                <h2 class="text-xl font-serif text-slate-800 dark:text-slate-100">Project Files</h2>
                <button onclick="document.getElementById('fileUploadInput').click()"
                    class="px-4 py-2 bg-primary text-white rounded text-sm font-medium hover:opacity-90 transition-opacity flex items-center gap-2">
                    <span class="material-icons text-sm">upload</span> Upload File
                </button>
            </div>

            <?php 
            // Function to get file icon and color based on type
            function getFileIcon($type) {
              $type = strtolower($type);
              if (in_array($type, ['pdf'])) return ['icon' => 'picture_as_pdf', 'color' => 'text-red-500'];
              if (in_array($type, ['xls', 'xlsx', 'csv', 'excel'])) return ['icon' => 'description', 'color' => 'text-green-500'];
              if (in_array($type, ['jpg', 'jpeg', 'png', 'gif', 'image'])) return ['icon' => 'image', 'color' => 'text-blue-500'];
              if (in_array($type, ['doc', 'docx', 'txt'])) return ['icon' => 'description', 'color' => 'text-blue-600'];
              if (in_array($type, ['zip', 'rar', '7z'])) return ['icon' => 'folder_zip', 'color' => 'text-yellow-600'];
              return ['icon' => 'insert_drive_file', 'color' => 'text-slate-500'];
            }
            ?>

            <?php if (!empty($project['files'])): ?>
            <div class="space-y-2">
                <?php foreach ($project['files'] as $file): 
                  $fileDisplay = getFileIcon($file['type']);
                ?>
                <div
                    class="flex items-center gap-4 p-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg hover:shadow-md transition-shadow">
                    <div class="<?php echo $fileDisplay['color']; ?>">
                        <span class="material-icons text-3xl"><?php echo $fileDisplay['icon']; ?></span>
                    </div>
                    <div class="flex-grow min-w-0">
                        <p class="font-medium text-slate-800 dark:text-slate-100 truncate"><?php echo htmlspecialchars($file['name']); ?></p>
                        <p class="text-sm text-slate-500 dark:text-slate-400"><?php echo htmlspecialchars($file['type']); ?> • <?php echo htmlspecialchars($file['size']); ?> • Uploaded <?php echo formatDate($file['uploaded_at']); ?><?php if (!empty($file['uploaded_by'])): ?> by <?php echo htmlspecialchars($file['uploaded_by']); ?><?php endif; ?></p>
                    </div>
                    <div class="flex gap-2">
                        <?php if (!empty($file['file_path'])): ?>
                        <a href="<?php echo htmlspecialchars($file['file_path']); ?>" download
                            class="px-3 py-1.5 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 rounded text-xs font-medium hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                            Download
                        </a>
                        <?php endif; ?>
                        <button onclick="deleteFile(<?php echo $file['id']; ?>)"
                            class="px-3 py-1.5 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 rounded text-xs hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors">
                            <span class="material-icons text-sm">delete</span>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-12 text-center">
                <span class="material-icons text-6xl text-slate-300 mb-4">folder_open</span>
                <h3 class="text-xl font-serif text-slate-800 dark:text-slate-100 mb-2">No Files Yet</h3>
                <p class="text-slate-500 dark:text-slate-400 mb-6">Upload files related to this project.</p>
                <button onclick="document.getElementById('fileUploadInput').click()"
                    class="px-6 py-3 bg-primary text-white rounded hover:opacity-90 transition-opacity flex items-center gap-2 mx-auto">
                    <span class="material-icons text-sm">upload</span> Upload First File
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Activity Tab -->
        <div class="tab-content" id="activity-tab">
            <h2 class="text-xl font-serif text-slate-800 dark:text-slate-100 mb-6">Recent Activity</h2>

            <?php 
            // Function to get activity icon and color based on action
            function getActivityIcon($action) {
              $action = strtolower($action);
              if (strpos($action, 'complet') !== false) return ['icon' => 'check_circle', 'color' => 'text-green-500'];
              if (strpos($action, 'upload') !== false) return ['icon' => 'upload_file', 'color' => 'text-blue-500'];
              if (strpos($action, 'updat') !== false) return ['icon' => 'update', 'color' => 'text-yellow-600'];
              if (strpos($action, 'comment') !== false || strpos($action, 'add') !== false) return ['icon' => 'comment', 'color' => 'text-purple-500'];
              if (strpos($action, 'delet') !== false) return ['icon' => 'delete', 'color' => 'text-red-500'];
              if (strpos($action, 'creat') !== false) return ['icon' => 'add_circle', 'color' => 'text-green-600'];
              return ['icon' => 'info', 'color' => 'text-slate-500'];
            }

            // Function to format time ago
            function timeAgo($timestamp) {
              $time = strtotime($timestamp);
              $diff = time() - $time;
              if ($diff < 60) return 'Just now';
              if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
              if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
              if ($diff < 604800) return floor($diff / 86400) . ' days ago';
              return formatDate($timestamp);
            }
            ?>

            <?php if (!empty($project['activities'])): ?>
            <div class="space-y-4">
                <?php foreach ($project['activities'] as $activity): 
                  $activityDisplay = getActivityIcon($activity['action']);
                ?>
                <div
                    class="flex gap-4 p-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg">
                    <div class="<?php echo $activityDisplay['color']; ?> shrink-0">
                        <span class="material-icons"><?php echo $activityDisplay['icon']; ?></span>
                    </div>
                    <div class="flex-grow">
                        <p class="text-slate-800 dark:text-slate-100">
                            <span class="font-semibold"><?php echo htmlspecialchars($activity['user']); ?></span>
                            <span class="text-slate-600 dark:text-slate-400"> <?php echo htmlspecialchars($activity['action']); ?> </span>
                            <?php if (!empty($activity['item'])): ?>
                            <span class="font-medium"><?php echo htmlspecialchars($activity['item']); ?></span>
                            <?php endif; ?>
                        </p>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1"><?php echo timeAgo($activity['created_at']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-12 text-center">
                <span class="material-icons text-6xl text-slate-300 mb-4">history</span>
                <h3 class="text-xl font-serif text-slate-800 dark:text-slate-100 mb-2">No Activity Yet</h3>
                <p class="text-slate-500 dark:text-slate-400">Project activity will appear here as changes are made.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Drawings Tab -->
        <div class="tab-content" id="drawings-tab">
            <div class="mb-6 flex justify-between items-center">
                <h2 class="text-xl font-serif text-slate-800 dark:text-slate-100">Technical Drawings</h2>
                <button onclick="document.getElementById('drawingUploadInput').click()"
                    class="px-4 py-2 bg-primary text-white rounded text-sm font-medium hover:opacity-90 transition-opacity flex items-center gap-2">
                    <span class="material-icons text-sm">add</span> Upload Drawing
                </button>
            </div>

            <?php 
            $statusColors = [
              'Approved' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300',
              'Under Review' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300',
              'Revision Needed' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300'
            ];
            ?>

            <?php if (!empty($project['drawings'])): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($project['drawings'] as $drawing): 
                  $statusClass = $statusColors[$drawing['status']] ?? '';
                ?>
                <div
                    class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg overflow-hidden hover:shadow-md transition-shadow">
                    <div class="aspect-video bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                        <span class="material-icons text-6xl text-slate-300">architecture</span>
                    </div>
                    <div class="p-4">
                        <h3 class="font-semibold text-slate-800 dark:text-slate-100 mb-1"><?php echo htmlspecialchars($drawing['name']); ?></h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-2"><?php echo htmlspecialchars($drawing['version']); ?> • <?php echo formatDate($drawing['uploaded_at']); ?></p>
                        <span class="inline-block px-2 py-0.5 <?php echo $statusClass; ?> rounded text-xs font-medium mb-3">
                            <?php echo htmlspecialchars($drawing['status']); ?>
                        </span>
                        <div class="flex gap-2">
                            <?php if (!empty($drawing['file_path'])): ?>
                            <a href="../admin/file_viewer.php?file=<?php echo urlencode($drawing['name']); ?>&project=<?php echo urlencode($project['name']); ?>" target="_blank"
                                class="flex-1 px-3 py-1.5 bg-primary text-white rounded text-xs text-center font-medium hover:opacity-90 transition-opacity">
                                View
                            </a>
                            <?php else: ?>
                            <button disabled
                                class="flex-1 px-3 py-1.5 bg-slate-300 text-slate-500 rounded text-xs font-medium cursor-not-allowed">
                                View
                            </button>
                            <?php endif; ?>
                            <button onclick="deleteDrawing(<?php echo $drawing['id']; ?>)"
                                class="px-3 py-1.5 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 rounded text-xs hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors">
                                <span class="material-icons text-sm">delete</span>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-12 text-center">
                <span class="material-icons text-6xl text-slate-300 mb-4">architecture</span>
                <h3 class="text-xl font-serif text-slate-800 dark:text-slate-100 mb-2">No Drawings Yet</h3>
                <p class="text-slate-500 dark:text-slate-400 mb-6">Upload technical drawings for this project.</p>
                <button onclick="document.getElementById('drawingUploadInput').click()"
                    class="px-6 py-3 bg-primary text-white rounded hover:opacity-90 transition-opacity flex items-center gap-2 mx-auto">
                    <span class="material-icons text-sm">add</span> Upload First Drawing
                </button>
            </div>
            <?php endif; ?>
        </div>

    </main>

    <!-- Modal for Adding Team Member -->
    <div id="addTeamMemberModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 rounded-lg shadow-2xl max-w-md w-full border border-slate-200 dark:border-slate-800">
            <div class="p-6 border-b border-slate-200 dark:border-slate-800">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-serif text-slate-800 dark:text-slate-100">Add Team Member</h3>
                    <button onclick="closeAddTeamMemberModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <span class="material-icons">close</span>
                    </button>
                </div>
            </div>
            <form id="addTeamMemberForm" class="p-6 space-y-4">
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-500 uppercase">Name</label>
                    <input type="text" name="worker_name" required
                        class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded text-sm focus:ring-primary focus:border-primary"
                        placeholder="Enter worker name" />
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-500 uppercase">Role</label>
                    <input type="text" name="worker_role" required
                        class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded text-sm focus:ring-primary focus:border-primary"
                        placeholder="e.g., Plumber, Electrician" />
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-semibold text-slate-500 uppercase">Contact</label>
                    <input type="text" name="worker_contact" required
                        class="w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded text-sm focus:ring-primary focus:border-primary"
                        placeholder="+91 98765 43210" />
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="closeAddTeamMemberModal()"
                        class="flex-1 px-4 py-2 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 rounded text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-1 px-4 py-2 bg-primary text-white rounded text-sm font-semibold hover:opacity-90 transition-opacity">
                        Add Member
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php require_once __DIR__ . '/../Common/footer.php'; ?>

    <!-- Member Profile Modal -->
    <div id="memberProfileModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 rounded-lg shadow-2xl max-w-lg w-full border border-slate-200 dark:border-slate-800 overflow-hidden">
            <div class="h-32 bg-rajkot-rust relative">
                <div class="absolute -bottom-12 left-8 w-24 h-24 rounded-full bg-white border-4 border-white dark:border-slate-900 shadow-lg flex items-center justify-center text-3xl font-bold text-rajkot-rust" id="modal-member-initials">
                    ??
                </div>
            </div>
            <div class="pt-16 p-8">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h3 class="text-2xl font-serif font-bold text-slate-800 dark:text-slate-100" id="modal-member-name">Member Name</h3>
                        <p class="text-rajkot-rust font-medium" id="modal-member-role">Position</p>
                    </div>
                    <button onclick="closeMemberProfileModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <span class="material-icons">close</span>
                    </button>
                </div>
                
                <div class="space-y-4 mb-8">
                    <div class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-400">
                        <span class="material-icons text-lg">verified</span>
                        <span>Verified Field Representative</span>
                    </div>
                    <div class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-400">
                        <span class="material-icons text-lg">history</span>
                        <span>Joined: Jan 2024</span>
                    </div>
                    <div class="flex items-center gap-3 text-sm text-slate-600 dark:text-slate-400">
                        <span class="material-icons text-lg">task_alt</span>
                        <span>12 Projects Completed</span>
                    </div>
                </div>

                <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-lg mb-8">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Registry Bio</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400 leading-relaxed italic">
                        "Committed to architectural excellence and structural integrity. Specializing in high-precision field implementations for Ripal Design's premium ventures."
                    </p>
                </div>

                <button class="w-full py-3 bg-foundation-grey text-white rounded font-bold uppercase tracking-widest text-xs hover:bg-rajkot-rust transition-all active:scale-[0.98]">
                    Contact via Internal Signal
                </button>
            </div>
        </div>
    </div>

    <!-- Hidden file upload inputs -->
    <input type="file" id="fileUploadInput" style="display: none;" accept="*/*" onchange="uploadFile(this)" />
    <input type="file" id="drawingUploadInput" style="display: none;" accept=".pdf,.dwg,.dxf,image/*" onchange="uploadDrawing(this)" />

    <script>
        const projectId = <?php echo json_encode($projectId); ?>;

        // Tab switching functionality
        document.querySelectorAll('.tab-link').forEach(tab => {
            tab.addEventListener('click', function (e) {
                e.preventDefault();

                // Remove active class from all tabs and contents
                document.querySelectorAll('.tab-link').forEach(t => {
                    t.classList.remove('active', 'border-primary', 'text-primary');
                    t.classList.add('border-transparent', 'text-slate-500');
                });
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

                // Add active class to clicked tab
                this.classList.remove('border-transparent', 'text-slate-500');
                this.classList.add('active', 'border-primary', 'text-primary');

                // Show corresponding content
                const tabName = this.getAttribute('data-tab');
                document.getElementById(tabName + '-tab').classList.add('active');
            });
        });

        // AJAX form submission for project updates
        const projectForm = document.querySelector('form');
        if (projectForm) {
            projectForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Saving...';
                
                try {
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });
                    
                    if (response.ok) {
                        showNotification('Project updated successfully!', 'success');
                        // Log activity
                        logActivity('updated project', 'Project details');
                        // Refresh the page to show updated data
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        showNotification('Error updating project', 'error');
                    }
                } catch (error) {
                    showNotification('Network error occurred', 'error');
                    console.error('Error:', error);
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            });
        }

        // File upload function
        async function uploadFile(input) {
            if (!input.files || input.files.length === 0) return;
            
            const file = input.files[0];
            const formData = new FormData();
            formData.append('file', file);
            formData.append('project_id', projectId);
            formData.append('action', 'upload_file');
            
            showNotification(`Uploading ${file.name}...`, 'info');
            
            try {
                const response = await fetch('api/project_files.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                if (result.success) {
                    showNotification('File uploaded successfully!', 'success');
                    logActivity('uploaded file', file.name);
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showNotification(result.message || 'Upload failed', 'error');
                }
            } catch (error) {
                showNotification('Upload error occurred', 'error');
                console.error('Error:', error);
            }
            
            input.value = '';
        }

        // Drawing upload function
        async function uploadDrawing(input) {
            if (!input.files || input.files.length === 0) return;
            
            const file = input.files[0];
            const formData = new FormData();
            formData.append('file', file);
            formData.append('project_id', projectId);
            formData.append('action', 'upload_drawing');
            
            showNotification(`Uploading ${file.name}...`, 'info');
            
            try {
                const response = await fetch('api/project_files.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                if (result.success) {
                    showNotification('Drawing uploaded successfully!', 'success');
                    logActivity('uploaded drawing', file.name);
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showNotification(result.message || 'Upload failed', 'error');
                }
            } catch (error) {
                showNotification('Upload error occurred', 'error');
                console.error('Error:', error);
            }
            
            input.value = '';
        }

        // Delete file function
        async function deleteFile(fileId) {
            if (!confirm('Are you sure you want to delete this file?')) return;
            
            try {
                const response = await fetch('api/project_files.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'delete_file',
                        file_id: fileId,
                        project_id: projectId
                    })
                });
                
                const result = await response.json();
                if (result.success) {
                    showNotification('File deleted successfully!', 'success');
                    logActivity('deleted file', '');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showNotification(result.message || 'Delete failed', 'error');
                }
            } catch (error) {
                showNotification('Delete error occurred', 'error');
                console.error('Error:', error);
            }
        }

        // Delete drawing function
        async function deleteDrawing(drawingId) {
            if (!confirm('Are you sure you want to delete this drawing?')) return;
            
            try {
                const response = await fetch('api/project_files.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'delete_drawing',
                        drawing_id: drawingId,
                        project_id: projectId
                    })
                });
                
                const result = await response.json();
                if (result.success) {
                    showNotification('Drawing deleted successfully!', 'success');
                    logActivity('deleted drawing', '');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showNotification(result.message || 'Delete failed', 'error');
                }
            } catch (error) {
                showNotification('Delete error occurred', 'error');
                console.error('Error:', error);
            }
        }

        // Log activity function
        async function logActivity(action, item) {
            try {
                await fetch('api/project_files.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'log_activity',
                        project_id: projectId,
                        activity_action: action,
                        item: item
                    })
                });
            } catch (error) {
                console.error('Error logging activity:', error);
            }
        }

        // Show notification function
        function showNotification(message, type) {
            const colors = {
                success: 'bg-green-50 dark:bg-green-900/30 border-green-200 dark:border-green-800 text-green-700 dark:text-green-300',
                error: 'bg-red-50 dark:bg-red-900/30 border-red-200 dark:border-red-800 text-red-700 dark:text-red-300',
                info: 'bg-blue-50 dark:bg-blue-900/30 border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300'
            };
            
            const notification = document.createElement('div');
            notification.className = `fixed top-20 right-4 max-w-md px-4 py-3 rounded border ${colors[type] || colors.info} shadow-lg z-50 animate-fade-in`;
            notification.innerHTML = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transition = 'opacity 0.3s';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Real-time progress bar update
        function updateProgress(percentage) {
            const progressBar = document.querySelector('[style*="width:"]');
            const progressText = document.querySelector('.text-sm.font-semibold');
            
            if (progressBar) {
                progressBar.style.width = `${percentage}%`;
            }
            if (progressText) {
                progressText.textContent = `${percentage}%`;
            }
        }

        // Auto-save functionality (optional)
        let autoSaveTimeout;
        const formInputs = document.querySelectorAll('form input, form select');
        formInputs.forEach(input => {
            input.addEventListener('change', function() {
                clearTimeout(autoSaveTimeout);
                autoSaveTimeout = setTimeout(() => {
                    console.log('Auto-saving changes...');
                    // Optionally enable auto-save functionality here
                }, 2000);
            });
        });

        // Team member modal functions
        function showAddTeamMemberModal() {
            document.getElementById('addTeamMemberModal').classList.remove('hidden');
        }

        function closeAddTeamMemberModal() {
            document.getElementById('addTeamMemberModal').classList.add('hidden');
            document.getElementById('addTeamMemberForm').reset();
        }

        // Handle team member form submission
        const teamMemberForm = document.getElementById('addTeamMemberForm');
        if (teamMemberForm) {
            teamMemberForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('project_id', projectId);
                formData.append('action', 'add_team_member');
                
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Adding...';
                
                try {
                    const response = await fetch('api/project_files.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    if (result.success) {
                        showNotification('Team member added successfully!', 'success');
                        logActivity('added team member', formData.get('worker_name'));
                        closeAddTeamMemberModal();
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        showNotification(result.message || 'Failed to add member', 'error');
                    }
                } catch (error) {
                    showNotification('Network error occurred', 'error');
                    console.error('Error:', error);
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            });
        }

        // Close modal on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAddTeamMemberModal();
            }
        });

        // Member profile modal functions
        function viewMemberProfile(name, role) {
            document.getElementById('modal-member-name').textContent = name;
            document.getElementById('modal-member-role').textContent = role;
            
            const initials = name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
            document.getElementById('modal-member-initials').textContent = initials;
            
            document.getElementById('memberProfileModal').classList.remove('hidden');
        }

        function closeMemberProfileModal() {
            document.getElementById('memberProfileModal').classList.add('hidden');
        }

        // Close modal on backdrop click
        document.getElementById('memberProfileModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeMemberProfileModal();
            }
        });
    </script>
</body>

</html>
