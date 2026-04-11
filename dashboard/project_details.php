<?php
/**
 * Project Details Page
 * Displays comprehensive project information with tabs for Overview, Team, Files, Activity, and Drawings
 */

require_once __DIR__ . '/../includes/init.php';
require_login();

$sessionUser = $_SESSION['user'] ?? [];
$sessionRole = strtolower(trim((string)($sessionUser['role'] ?? '')));
$isClientReadOnly = ($sessionRole === 'client');

$pdo = get_db();

// Get project ID from URL
$projectId = $_GET['id'] ?? null;
$error = null;
$success = null;

if (isset($_SESSION['project_success'])) {
    $success = (string)$_SESSION['project_success'];
    unset($_SESSION['project_success']);
}
if (isset($_SESSION['project_error'])) {
    $error = (string)$_SESSION['project_error'];
    unset($_SESSION['project_error']);
}

// Helper function for date formatting
function formatDate($dateString) {
    if (empty($dateString)) return 'N/A';
    $date = strtotime($dateString);
    return date('M d, Y', $date);
}

// Normalize stored file paths into browser-openable URLs.
function project_file_url($path) {
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    $normalized = str_replace('\\', '/', $path);
    $basePath = rtrim((string)BASE_PATH, '/');

    // Keep already-correct app-relative paths unchanged.
    if (strpos($normalized, $basePath . '/') === 0) {
        return $normalized;
    }

    // Convert absolute filesystem paths to web paths when possible.
    if (preg_match('#^[A-Za-z]:/#', $normalized) || strpos($normalized, '/uploads/') !== false) {
        $uploadsPos = strpos($normalized, '/uploads/');
        if ($uploadsPos !== false) {
            $normalized = ltrim(substr($normalized, $uploadsPos), '/');
            return $basePath . '/' . $normalized;
        }
    }

    if (strpos($normalized, '/uploads/') === 0) {
        return $basePath . $normalized;
    }

    $normalized = ltrim($normalized, '/');
    return $basePath . '/' . $normalized;
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
                error_log('Project details load error: ' . $e->getMessage());
                $error = 'Unable to load project details right now.';
    }
}
// Create tables if they don't exist
if ($pdo instanceof PDO) {
  try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS projects (
      id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(255) NOT NULL,
      status ENUM('planning', 'ongoing', 'paused', 'completed') DEFAULT 'ongoing',
      budget DECIMAL(15,2),
      progress INT DEFAULT 0,
      due DATE,
      location TEXT,
            map_link TEXT,
      address TEXT,
      owner_name VARCHAR(255),
      owner_contact VARCHAR(50),
      owner_email VARCHAR(255),
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

        if (!db_column_exists('projects', 'map_link')) {
            try {
                $pdo->exec("ALTER TABLE projects ADD COLUMN map_link TEXT");
            } catch (Throwable $e) {
                // Ignore if another request adds it first.
            }
        }

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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo instanceof PDO) {
    if ($isClientReadOnly) {
        $error = 'Client accounts have view-only access for project details.';
    } else {
  $name = $_POST['name'] ?? '';
  $status = $_POST['status'] ?? 'ongoing';
  $budget = $_POST['budget'] ?? 0;
  $progress = $_POST['progress'] ?? 0;
  $due = $_POST['due'] ?? null;
    $currentLocation = trim((string)($project['location'] ?? ''));
    $currentAddress = trim((string)($project['address'] ?? $currentLocation));
    $location = array_key_exists('location', $_POST)
        ? trim((string)$_POST['location'])
        : $currentLocation;
    $address = array_key_exists('address', $_POST)
        ? trim((string)$_POST['address'])
        : ($currentAddress !== '' ? $currentAddress : $location);
    if ($address === '') {
        $address = $location;
    }
  $ownerName = $_POST['owner_name'] ?? '';
  $ownerContact = $_POST['owner_contact'] ?? '';
  $ownerEmail = $_POST['owner_email'] ?? '';
    $mapLink = array_key_exists('map_link', $_POST)
            ? trim((string)$_POST['map_link'])
            : trim((string)($project['map_link'] ?? ''));
    if ($mapLink !== '' && !filter_var($mapLink, FILTER_VALIDATE_URL)) {
        // Unified field accepts plain address/place text as well.
        $mapLink = 'https://www.google.com/maps?q=' . rawurlencode($mapLink);
    }

  if (empty($name)) {
    $error = 'Project name is required';
    } elseif ($mapLink !== '' && !is_valid_google_maps_url($mapLink)) {
        $error = 'Please enter a valid Google Maps link.';
  } else {
        if ($mapLink !== '') {
            $mapLink = canonicalize_google_maps_url($mapLink);
        }

        $derivedMapAddress = $mapLink !== '' ? trim((string)normalize_google_maps_embed_query($mapLink)) : '';
        $derivedMapLooksLikeCoordinates = (bool)preg_match('/^\s*-?\d+(?:\.\d+)?\s*,\s*-?\d+(?:\.\d+)?\s*$/', $derivedMapAddress);
        if ($derivedMapAddress !== '' && !$derivedMapLooksLikeCoordinates) {
            if ($location === '') {
                $location = $derivedMapAddress;
            }
            if ($address === '') {
                $address = $derivedMapAddress;
            }
        }
    try {
      if ($projectId) {
                $previousStatus = strtolower((string)($project['status'] ?? ''));
        // Update existing project
        $stmt = $pdo->prepare('
          UPDATE projects 
          SET name = :name, status = :status, budget = :budget, 
              progress = :progress, due = :due, location = :location, map_link = :map_link, address = :address,
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
          'map_link' => $mapLink,
                    'address' => $address,
          'owner_name' => $ownerName,
          'owner_contact' => $ownerContact,
          'owner_email' => $ownerEmail
        ]);
        $_SESSION['project_success'] = 'Project updated successfully!';
        
        // Log activity
        $activityStmt = $pdo->prepare('
          INSERT INTO project_activity (project_id, user, action, item, created_at)
          VALUES (:project_id, :user, :action, :item, NOW())
        ');
                $activityStmt->execute([
          'project_id' => $projectId,
                    'user' => $_SESSION['user']['name'] ?? $_SESSION['user']['username'] ?? 'Admin',
          'action' => 'updated project',
          'item' => 'Project details'
        ]);

                if ($previousStatus !== 'completed' && strtolower((string)$status) === 'completed') {
                    notifications_notify_admins(
                        'project',
                        'Project Completed',
                        'Project marked as completed: ' . $name . '.',
                        [
                            'actor_user_id' => current_user_id(),
                            'project_id' => (int)$projectId,
                            'action_key' => 'project.completed',
                            'deep_link' => rtrim((string)BASE_PATH, '/') . '/dashboard/project_details.php?id=' . (int)$projectId,
                        ]
                    );
                }

                                header('Location: project_details.php?id=' . (int)$projectId);
                                exit;
      } else {
        // Create new project
        $stmt = $pdo->prepare('
                    INSERT INTO projects (name, status, budget, progress, due, location, map_link, address, owner_name, owner_contact, owner_email)
                                        VALUES (:name, :status, :budget, :progress, :due, :location, :map_link, :address, :owner_name, :owner_contact, :owner_email)
        ');
        $stmt->execute([
          'name' => $name,
          'status' => $status,
          'budget' => $budget,
          'progress' => $progress,
          'due' => $due,
          'location' => $location,
          'map_link' => $mapLink,
                    'address' => $address,
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
                    'user' => $_SESSION['user']['name'] ?? $_SESSION['user']['username'] ?? 'Admin',
          'action' => 'created project',
          'item' => $name
        ]);

                notifications_notify_admins(
                    'project',
                    'New Project Created',
                    'A new project was created: ' . $name . '.',
                    [
                        'actor_user_id' => current_user_id(),
                        'project_id' => (int)$projectId,
                        'action_key' => 'project.created',
                        'deep_link' => rtrim((string)BASE_PATH, '/') . '/dashboard/project_details.php?id=' . (int)$projectId,
                    ]
                );

                $_SESSION['project_success'] = 'Project created successfully!';
                header('Location: dashboard.php');
        exit;
      }
    } catch (PDOException $e) {
      $error = "Database Error: " . $e->getMessage();
    }
  }
    }
}

// Load project data
$project = null;
if ($projectId && $pdo instanceof PDO) {
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
if (!$project && !$projectId) {
    $project = [
        'id' => null,
        'name' => '',
        'status' => 'ongoing',
        'budget' => 0,
        'progress' => 0,
        'due' => date('Y-m-d', strtotime('+30 days')),
        'location' => '',
        'map_link' => '',
        'address' => '',
        'owner' => [
            'name' => '',
            'contact' => '',
            'email' => ''
        ],
        'workers' => [],
        'milestones' => [],
        'files' => [],
        'activities' => [],
        'drawings' => []
    ];
}

if (!$project) {
  $project = [
    'id' => $projectId ?? 1,
    'name' => 'Shanti Sadan',
    'status' => 'ongoing',
    'budget' => 4500000,
    'progress' => 45,
    'due' => date('Y-m-d', strtotime('+30 days')),
    'location' => 'Jasal Complex, Nanavati Chowk, Rajkot',
    'map_link' => '',
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

$projectMapLink = trim((string)($project['map_link'] ?? ''));
$projectAddressForMap = trim((string)($project['address'] ?? $project['location'] ?? ''));
$projectMapSeed = $projectMapLink !== '' ? $projectMapLink : $projectAddressForMap;
$projectMapEmbedSrc = build_google_maps_embed_src($projectMapSeed);
$projectMapInputValue = $projectMapLink !== '' ? trim((string)normalize_google_maps_embed_query($projectMapLink)) : $projectAddressForMap;
if ($projectMapInputValue === '') {
    $projectMapInputValue = $projectMapLink;
}
$projectLocationFromMapLink = '';
if ($projectMapLink !== '') {
    $projectLocationFromMapLink = trim((string)normalize_google_maps_embed_query($projectMapLink));
}
$projectMapLinkLooksLikeCoordinates = (bool)preg_match('/^\s*-?\d+(?:\.\d+)?\s*,\s*-?\d+(?:\.\d+)?\s*$/', $projectLocationFromMapLink);
$projectLocationSentenceValue = $projectAddressForMap;
if ($projectLocationFromMapLink !== '' && !$projectMapLinkLooksLikeCoordinates) {
    $projectLocationSentenceValue = $projectLocationFromMapLink;
}

// Status badge colors
$statusColors = [
  'planning' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300',
  'ongoing' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300',
  'paused' => 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300',
  'completed' => 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300'
];
$statusClass = $statusColors[$project['status']] ?? $statusColors['ongoing'];
// Load registered users with role 'worker' for quick assignment picker
$workerUsers = [];
if ($pdo instanceof PDO) {
    try {
        if (function_exists('db_table_exists') && db_table_exists('users')) {
            // Avoid referencing optional columns (phone/contact) which may not exist in all schemas.
            // Use email/username as a safe contact fallback.
            $stmt = $pdo->prepare("SELECT id, COALESCE(NULLIF(full_name, ''), TRIM(CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')))) AS full_name, role, COALESCE(email, username) AS contact FROM users WHERE role = 'worker' ORDER BY full_name ASC");
            $stmt->execute();
            $workerUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log('Failed to load worker users: ' . $e->getMessage());
        $workerUsers = [];
    }
}
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
            line-height: 1.35;
        }

        .font-serif {
            font-family: 'Playfair Display', serif;
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

        main a {
            color: #731209;
        }

        main a:hover {
            color: #5a0e07;
        }
    </style>
</head>

<body
    class="bg-background-light dark:bg-background-dark text-slate-800 dark:text-slate-200 min-h-screen flex flex-col transition-colors duration-300">
    
    <?php 
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
    <div id="projectSuccessAlertWrap" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 transition-opacity duration-500" role="status" aria-live="polite">
        <div id="projectSuccessAlert" class="bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 px-4 py-3 rounded transition-opacity duration-500">
            <?php echo htmlspecialchars($success); ?>
        </div>
    </div>
    <script>
        (function () {
            const wrap = document.getElementById('projectSuccessAlertWrap');
            if (!wrap) {
                return;
            }
            window.setTimeout(function () {
                wrap.style.opacity = '0';
                window.setTimeout(function () {
                    if (wrap && wrap.parentNode) {
                        wrap.parentNode.removeChild(wrap);
                    }
                }, 500);
            }, 4000);
        })();
    </script>
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
                    <?php
                        $projectDirectionHref = build_google_maps_direction_href((string)($project['map_link'] ?? ''), $projectAddressForMap);
                        $projectLocationText = htmlspecialchars($project['location'] ?? $project['address'] ?? 'Location not set');
                    ?>
                    <p class="text-gray-400 mt-2 flex items-center gap-1">
                        <i data-lucide="map-pin" class="w-4 h-4 text-rajkot-rust"></i>
                        <?php if ($projectDirectionHref !== ''): ?>
                            <a href="<?php echo htmlspecialchars($projectDirectionHref); ?>" target="_blank" rel="noopener noreferrer"><?php echo $projectLocationText; ?></a>
                        <?php else: ?>
                            <?php echo $projectLocationText; ?>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="flex gap-2">
                    <?php if (!$isClientReadOnly): ?>
                    <button
                        id="editProjectBtn"
                        type="button"
                        class="px-6 py-2.5 bg-white/10 border border-white/20 text-white rounded text-sm font-medium hover:bg-white/20 transition-all flex items-center gap-2"
                        >
                        <i data-lucide="edit-3" class="w-4 h-4"></i> Edit Project
                    </button>
                    <?php endif; ?>
                    <button
                        id="shareProjectBtn"
                        type="button"
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
                        <form id="projectDetailsForm" method="post">
                            <?php echo csrf_token_field(); ?>
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
                                <div class="pt-4 border-t border-slate-100 dark:border-slate-800">
                                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Location Mapping
                                    </h3>
                                    <?php $displayAddress = $projectLocationSentenceValue; ?>
                                    <input type="hidden" name="location" id="projectLocationInput" value="<?php echo htmlspecialchars($project['location'] ?? $project['address'] ?? ''); ?>" />
                                    <input type="hidden" name="address" id="projectAddressInput" value="<?php echo htmlspecialchars($project['address'] ?? $project['location'] ?? ''); ?>" />
                                    <div class="mb-4">
                                        <label for="projectMapInput" class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1 block">Location / Google Maps</label>
                                        <input
                                            id="projectMapInput"
                                            name="map_link"
                                            value="<?php echo htmlspecialchars($projectMapInputValue); ?>"
                                            class="w-full bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 rounded text-sm focus:ring-primary focus:border-primary"
                                            placeholder="Paste address, place name, coordinates, or Google Maps link" type="text" />
                                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">Use one field for both: search text and map links.</p>
                                    </div>
                                    <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-4 shadow-sm">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Address</p>
                                                <p id="locationAddressDisplay" class="text-sm text-slate-700 dark:text-slate-200 leading-relaxed break-words cursor-text" title="Double-click to edit address">
                                                    <?php if ($displayAddress !== ''): ?>
                                                        This project is located at <?php echo htmlspecialchars($displayAddress); ?>.
                                                    <?php else: ?>
                                                        Address is not available yet. Add the location above to enable map preview.
                                                    <?php endif; ?>
                                                </p>
                                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-2">Click the info button to reveal map preview.</p>
                                            </div>
                                            <button
                                                type="button"
                                                id="locationInfoToggle"
                                                class="shrink-0 inline-flex items-center justify-center w-9 h-9 rounded border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors"
                                                title="Toggle map preview"
                                                aria-label="Toggle map preview"
                                                aria-expanded="false"
                                                aria-controls="locationMapPreview">
                                                <span class="material-icons text-base">info</span>
                                            </button>
                                        </div>
                                    </div>
                                    <div id="locationMapPreview" class="hidden mt-4">
                                        <div class="bg-slate-100 dark:bg-slate-800 rounded-lg aspect-video relative overflow-hidden border border-slate-200 dark:border-slate-700">
                                            <iframe
                                                id="projectMapIframe"
                                                src="<?php echo htmlspecialchars($projectMapEmbedSrc !== '' ? $projectMapEmbedSrc : 'https://www.google.com/maps?q=Rajkot&output=embed'); ?>"
                                                width="100%"
                                                height="100%"
                                                style="border:0; position:absolute; inset:0;"
                                                allowfullscreen=""
                                                loading="lazy"
                                                referrerpolicy="no-referrer-when-downgrade"
                                                title="Project map preview">
                                            </iframe>
                                        </div>
                                    </div>
                                    <div class="mt-4 text-xs text-slate-500 dark:text-slate-400">Map preview updates when you press Enter or leave the location field.</div>
                                </div>
                            </div>
                            <?php if (!$isClientReadOnly): ?>
                            <div class="p-6 bg-slate-50 dark:bg-slate-800/50 flex justify-end gap-3">
                                <button type="button"
                                    class="px-6 py-2 text-sm font-medium text-slate-600 dark:text-slate-400 hover:text-primary"
                                    onclick="window.location.reload()">Discard Changes</button>
                                <button type="submit"
                                    class="px-8 py-2 bg-primary text-white rounded text-sm font-semibold hover:opacity-95 shadow-md">Save
                                    Project</button>
                            </div>
                            <?php else: ?>
                            <div class="p-6 bg-slate-50 dark:bg-slate-800/50 text-sm text-slate-500">
                                Client mode: view only.
                            </div>
                            <?php endif; ?>
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
                        <button onclick="viewMemberProfile(<?php echo (int)($member['id'] ?? 0); ?>, '<?php echo addslashes($member['worker_name']); ?>', '<?php echo addslashes($member['worker_role']); ?>')"
                            class="flex-1 px-3 py-1.5 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 rounded text-xs font-medium hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">
                            View Profile
                        </button>
                        <button onclick="deleteTeamMember(<?php echo (int)($member['id'] ?? 0); ?>)"
                            class="px-3 py-1.5 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 rounded text-xs hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors">
                            <span class="material-icons text-sm">delete</span>
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
                                    $fileUrl = project_file_url($file['file_path'] ?? '');
                                                                        $fileViewUrl = file_viewer_url([
                                                                            'kind' => 'file',
                                                                            'id' => (int)($file['id'] ?? 0),
                                                                            'project_id' => (int)$projectId,
                                                                            'ext' => strtolower((string)($file['type'] ?? '')),
                                                                        ]);
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
                        <?php if ($fileUrl !== ''): ?>
                        <a href="<?php echo htmlspecialchars($fileViewUrl); ?>" target="_blank" rel="noopener noreferrer"
                            class="px-3 py-1.5 bg-primary text-white rounded text-xs font-medium hover:opacity-90 transition-opacity no-underline">
                            View
                        </a>
                        <a href="<?php echo htmlspecialchars($fileUrl); ?>" download
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
                                    $drawingUrl = project_file_url($drawing['file_path'] ?? '');
                                    $drawingViewUrl = file_viewer_url([
                                        'kind' => 'drawing',
                                        'id' => (int)($drawing['id'] ?? 0),
                                        'project_id' => (int)$projectId,
                                        'ext' => strtolower((string)pathinfo((string)($drawing['name'] ?? ''), PATHINFO_EXTENSION)),
                                    ]);
                ?>
                <div
                    class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg overflow-hidden hover:shadow-md transition-shadow">
                    <div class="aspect-video bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                        <span class="material-icons text-6xl text-slate-300">architecture</span>
                    </div>
                    <div class="p-4">
                        <h3 class="font-semibold text-slate-800 dark:text-slate-100 mb-1"><?php echo htmlspecialchars($drawing['name']); ?></h3>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mb-2"><?php echo htmlspecialchars($drawing['version']); ?> • <?php echo formatDate($drawing['uploaded_at']); ?><?php if (!empty($drawing['uploaded_by'])): ?> • Uploaded by <?php echo htmlspecialchars($drawing['uploaded_by']); ?><?php endif; ?></p>
                        <span class="inline-block px-2 py-0.5 <?php echo $statusClass; ?> rounded text-xs font-medium mb-3">
                            <?php echo htmlspecialchars($drawing['status']); ?>
                        </span>
                        <div class="flex gap-2">
                            <?php if ($drawingUrl !== ''): ?>
                            <a href="<?php echo htmlspecialchars($drawingViewUrl); ?>" target="_blank" rel="noopener noreferrer"
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
            <!-- Worker picker view -->
            <div id="workerListView" class="p-6 space-y-4">
                <?php if (!empty($workerUsers)): ?>
                <div class="space-y-2 max-h-64 overflow-auto">
                    <?php foreach ($workerUsers as $wu): ?>
                    <div class="flex items-center justify-between p-2 rounded border border-slate-100 dark:border-slate-800">
                        <div class="min-w-0 mr-3">
                            <p class="font-semibold text-slate-800 dark:text-slate-100 truncate"><?php echo htmlspecialchars($wu['full_name']); ?></p>
                            <p class="text-sm text-slate-500 truncate"><?php echo htmlspecialchars($wu['role'] ?? 'Worker'); ?><?php if (!empty($wu['contact'])): ?> • <?php echo htmlspecialchars($wu['contact']); ?><?php endif; ?></p>
                        </div>
                        <div class="flex-shrink-0">
                            <button type="button" onclick="assignExistingWorker(<?php echo (int)$wu['id']; ?>)" class="px-3 py-1.5 bg-primary text-white rounded text-xs">Assign</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                    <p class="text-sm text-slate-500">No registered workers found. Use manual add below.</p>
                <?php endif; ?>
                <div class="pt-4">
                    <button type="button" onclick="toggleAddManual(true)" class="px-4 py-2 border border-slate-200 rounded text-sm">Add Manually</button>
                </div>
            </div>

            <!-- Manual add form (kept for backward compatibility) -->
            <form id="addTeamMemberForm" class="p-6 space-y-4 hidden">
                <?php echo csrf_token_field(); ?>
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
                    <button type="button" onclick="toggleAddManual(false)"
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

                <button onclick="openContactModal()" id="contactViaSignalBtn" class="w-full py-3 bg-foundation-grey text-white rounded font-bold uppercase tracking-widest text-xs hover:bg-rajkot-rust transition-all active:scale-[0.98]">
                    Contact via Internal Signal
                </button>
            </div>
        </div>
    </div>

    <!-- Hidden file upload inputs -->
    
    <!-- Contact Modal (Internal Signal) -->
    <div id="contactModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white dark:bg-slate-900 rounded-lg shadow-2xl max-w-lg w-full border border-slate-200 dark:border-slate-800 overflow-hidden">
            <div class="p-6 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <h3 class="text-lg font-serif text-slate-800 dark:text-slate-100">Send Internal Signal</h3>
                <button onclick="closeContactModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"><span class="material-icons">close</span></button>
            </div>
            <form id="contactForm" class="p-6 space-y-4">
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase">To</label>
                    <div id="contactTo" class="mt-2 text-sm text-slate-700 dark:text-slate-300">—</div>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500 uppercase">Message</label>
                    <textarea name="message" required rows="5" class="w-full mt-2 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded text-sm focus:ring-primary focus:border-primary" placeholder="Type your message to the team member..."></textarea>
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="button" onclick="closeContactModal()" class="flex-1 px-4 py-2 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 rounded text-sm font-medium hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors">Cancel</button>
                    <button type="submit" class="flex-1 px-4 py-2 bg-rajkot-rust text-white rounded text-sm font-semibold hover:opacity-95">Send Signal</button>
                </div>
            </form>
        </div>
    </div>
    <input type="file" id="fileUploadInput" style="display: none;" accept="*/*" onchange="uploadFile(this)" />
    <input type="file" id="drawingUploadInput" style="display: none;" accept=".pdf,.dwg,.dxf,image/*" onchange="uploadDrawing(this)" />

    <script>
        const projectId = <?php echo json_encode($projectId); ?>;
        const projectShareUrl = <?php echo json_encode((!empty($projectId) ? rtrim(BASE_URL, '/') . '/dashboard/project_details.php?id=' . (int)$projectId : '')); ?>;
        // Worker users available for quick assignment (loaded server-side)
        const workerUsersData = <?php echo json_encode($workerUsers, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?> || [];
        const csrfToken = <?php echo json_encode(csrf_token()); ?>;

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

        // Persist the active tab in the URL hash (and support ?tab=... on page load)
        // Update the hash without adding a new history entry when user switches tabs.
        document.querySelectorAll('.tab-link').forEach(tab => {
            tab.addEventListener('click', function () {
                const tabName = this.getAttribute('data-tab');
                try {
                    if (history.replaceState) {
                        history.replaceState(null, '', '#' + tabName);
                    } else {
                        location.hash = tabName;
                    }
                } catch (err) {
                    // ignore
                }
            });
        });

        // On load, restore tab from location.hash or ?tab= query param if present
        (function restoreActiveTabFromUrl() {
            try {
                const fromHash = (location.hash || '').replace('#', '').trim();
                const urlParams = new URLSearchParams(window.location.search);
                const fromQuery = (urlParams.get('tab') || '').trim();
                const desired = fromHash || fromQuery || '';
                if (desired) {
                    const desiredLink = document.querySelector('.tab-link[data-tab="' + desired + '"]');
                    if (desiredLink) {
                        // trigger click handler to set classes and content
                        desiredLink.click();
                        return;
                    }
                }
            } catch (err) {
                // ignore and fall back to default markup
            }
        })();

        // Keep native form submit so server redirects correctly after save/create.

        // Edit button should jump user to editable form in Overview tab.
        const editProjectBtn = document.getElementById('editProjectBtn');
        const projectDetailsForm = document.getElementById('projectDetailsForm');
        if (editProjectBtn && projectDetailsForm) {
            editProjectBtn.addEventListener('click', function () {
                document.querySelectorAll('.tab-link').forEach(t => {
                    t.classList.remove('active', 'border-primary', 'text-primary');
                    t.classList.add('border-transparent', 'text-slate-500');
                });
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));

                const overviewTab = document.querySelector('.tab-link[data-tab="overview"]');
                if (overviewTab) {
                    overviewTab.classList.remove('border-transparent', 'text-slate-500');
                    overviewTab.classList.add('active', 'border-primary', 'text-primary');
                }
                const overviewContent = document.getElementById('overview-tab');
                if (overviewContent) {
                    overviewContent.classList.add('active');
                }

                const top = Math.max(0, projectDetailsForm.getBoundingClientRect().top + window.pageYOffset - 100);
                window.scrollTo({ top, behavior: 'smooth' });

                const firstInput = projectDetailsForm.querySelector('input[name="name"]');
                if (firstInput) {
                    setTimeout(() => firstInput.focus(), 350);
                }
            });
        }

        // Share button: use Web Share API where available, otherwise copy link.
        const shareProjectBtn = document.getElementById('shareProjectBtn');
        if (shareProjectBtn) {
            shareProjectBtn.addEventListener('click', async function () {
                if (!projectId || !projectShareUrl) {
                    showNotification('Save the project first, then share it.', 'error');
                    return;
                }

                try {
                    if (navigator.share) {
                        await navigator.share({
                            title: 'Project Details',
                            text: 'Open this project in Ripal Design',
                            url: projectShareUrl
                        });
                        showNotification('Project link shared.', 'success');
                        return;
                    }

                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        await navigator.clipboard.writeText(projectShareUrl);
                        showNotification('Project link copied to clipboard.', 'success');
                        return;
                    }

                    window.prompt('Copy this project link:', projectShareUrl);
                } catch (error) {
                    if (error && error.name === 'AbortError') {
                        return;
                    }
                    showNotification('Unable to share project link.', 'error');
                    console.error('Share error:', error);
                }
            });
        }

        // Location info button toggles the existing map preview.
        const locationInfoToggle = document.getElementById('locationInfoToggle');
        const locationMapPreview = document.getElementById('locationMapPreview');
        if (locationInfoToggle && locationMapPreview) {
            locationInfoToggle.addEventListener('click', function () {
                const willShow = locationMapPreview.classList.contains('hidden');
                locationMapPreview.classList.toggle('hidden');
                locationInfoToggle.setAttribute('aria-expanded', willShow ? 'true' : 'false');
            });
        }

        // Double-click address text to edit location inline.
        const locationAddressDisplay = document.getElementById('locationAddressDisplay');
        const projectLocationInput = document.getElementById('projectLocationInput');
        const projectAddressInput = document.getElementById('projectAddressInput');
        const projectMapInput = document.getElementById('projectMapInput');
        const projectMapIframe = document.getElementById('projectMapIframe');

        function renderAddressSentence(address) {
            const cleanAddress = (address || '').trim();
            if (cleanAddress === '') {
                return 'Address is not available yet. Double-click to add it.';
            }
            return 'This project is located at ' + cleanAddress + '.';
        }

        function extractAddressFromSentence(text) {
            const prefix = 'This project is located at ';
            const trimmed = (text || '').trim();
            if (trimmed.indexOf(prefix) === 0) {
                return trimmed.substring(prefix.length).replace(/\.$/, '').trim();
            }
            if (trimmed.indexOf('Address is not available') === 0) {
                return '';
            }
            return trimmed.replace(/\.$/, '').trim();
        }

        function buildMapEmbedUrl(query) {
            const input = (query || '').trim();
            const coordinateMatch = input.match(/^(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)$/);
            if (coordinateMatch) {
                const lat = coordinateMatch[1];
                const lng = coordinateMatch[2];
                return 'https://www.google.com/maps?q=' + encodeURIComponent(lat + ',' + lng) + '&z=17&output=embed';
            }

            try {
                const parsed = new URL(input);
                const path = decodeURIComponent(parsed.pathname || '');

                const atMatch = path.match(/@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/);
                if (atMatch) {
                    return 'https://www.google.com/maps?q=' + encodeURIComponent(atMatch[1] + ',' + atMatch[2]) + '&z=17&output=embed';
                }

                const placeMatch = path.match(/\/place\/([^/]+)/);
                if (placeMatch && placeMatch[1]) {
                    const placeName = placeMatch[1].replace(/\+/g, ' ').trim();
                    if (placeName) {
                        return 'https://www.google.com/maps?q=' + encodeURIComponent(placeName) + '&output=embed';
                    }
                }

                const searchMatch = path.match(/\/maps\/search\/([^/?]+)/);
                if (searchMatch && searchMatch[1]) {
                    const searchText = searchMatch[1].replace(/\+/g, ' ').trim();
                    if (searchText) {
                        return 'https://www.google.com/maps?q=' + encodeURIComponent(searchText) + '&output=embed';
                    }
                }

                const q = (parsed.searchParams.get('q') || '').trim();
                const queryParam = (parsed.searchParams.get('query') || '').trim();
                const destination = (parsed.searchParams.get('destination') || '').trim();
                const daddr = (parsed.searchParams.get('daddr') || '').trim();
                const candidate = q || queryParam || destination || daddr;
                if (candidate && !/^https?:\/\//i.test(candidate)) {
                    return 'https://www.google.com/maps?q=' + encodeURIComponent(candidate) + '&output=embed';
                }
            } catch (e) {
                // Not a URL; treat as plain address text.
            }

            return 'https://www.google.com/maps?q=' + encodeURIComponent(input) + '&output=embed';
        }

        function deriveAddressFromMapInput(query) {
            const input = (query || '').trim();
            if (input === '') {
                return '';
            }

            const coordinateMatch = input.match(/^(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)$/);
            if (coordinateMatch) {
                return '';
            }

            try {
                const parsed = new URL(input);
                const path = decodeURIComponent(parsed.pathname || '');

                const placeMatch = path.match(/\/place\/([^/]+)/);
                if (placeMatch && placeMatch[1]) {
                    const placeName = placeMatch[1].replace(/\+/g, ' ').trim();
                    if (placeName) {
                        return placeName;
                    }
                }

                const searchMatch = path.match(/\/maps\/search\/([^/?]+)/);
                if (searchMatch && searchMatch[1]) {
                    const searchText = searchMatch[1].replace(/\+/g, ' ').trim();
                    if (searchText) {
                        return searchText;
                    }
                }

                const q = (parsed.searchParams.get('q') || '').trim();
                const queryParam = (parsed.searchParams.get('query') || '').trim();
                const destination = (parsed.searchParams.get('destination') || '').trim();
                const daddr = (parsed.searchParams.get('daddr') || '').trim();
                const candidate = q || queryParam || destination || daddr;
                if (candidate && !/^https?:\/\//i.test(candidate)) {
                    return candidate;
                }

                return '';
            } catch (e) {
                return input;
            }
        }

        function applyMapInputPreview() {
            if (!projectMapInput || !projectMapIframe) {
                return;
            }
            const value = (projectMapInput.value || '').trim();
            if (value === '') {
                return;
            }
            projectMapIframe.src = buildMapEmbedUrl(value);
            if (locationAddressDisplay) {
                const derivedAddress = deriveAddressFromMapInput(value);
                if (derivedAddress !== '') {
                    locationAddressDisplay.textContent = renderAddressSentence(derivedAddress);
                    if (projectLocationInput) {
                        projectLocationInput.value = derivedAddress;
                    }
                    if (projectAddressInput) {
                        projectAddressInput.value = derivedAddress;
                    }
                }
            }
            if (locationMapPreview && locationMapPreview.classList.contains('hidden')) {
                locationMapPreview.classList.remove('hidden');
                if (locationInfoToggle) {
                    locationInfoToggle.setAttribute('aria-expanded', 'true');
                }
            }
        }

        if (projectMapInput) {
            projectMapInput.addEventListener('blur', applyMapInputPreview);
            projectMapInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    applyMapInputPreview();
                }
            });

            if ((projectMapInput.value || '').trim() !== '') {
                applyMapInputPreview();
            }
        }

        if (locationAddressDisplay && projectLocationInput && projectAddressInput) {
            locationAddressDisplay.addEventListener('dblclick', function () {
                if (locationAddressDisplay.dataset.editing === '1') {
                    return;
                }

                const currentValue = extractAddressFromSentence(locationAddressDisplay.textContent || '');
                const editor = document.createElement('input');
                editor.type = 'text';
                editor.value = currentValue;
                editor.className = 'w-full bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 rounded text-sm focus:ring-primary focus:border-primary';
                editor.setAttribute('aria-label', 'Edit project address');

                locationAddressDisplay.dataset.editing = '1';
                locationAddressDisplay.replaceWith(editor);
                editor.focus();
                editor.select();

                const finishEdit = function (save) {
                    const nextValue = save ? editor.value.trim() : currentValue;
                    if (save) {
                        projectLocationInput.value = nextValue;
                        projectAddressInput.value = nextValue;
                    }

                    locationAddressDisplay.textContent = renderAddressSentence(nextValue);
                    locationAddressDisplay.dataset.editing = '0';
                    editor.replaceWith(locationAddressDisplay);
                };

                editor.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        finishEdit(true);
                    } else if (event.key === 'Escape') {
                        event.preventDefault();
                        finishEdit(false);
                    }
                });

                editor.addEventListener('blur', function () {
                    finishEdit(true);
                });
            });
        }

        // File upload function
        async function uploadFile(input) {
            if (!input.files || input.files.length === 0) return;
            if (!projectId || Number(projectId) <= 0) {
                showNotification('Save the project first, then upload files.', 'error');
                input.value = '';
                return;
            }
            
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
                    if (result.view_url) {
                        window.open(result.view_url, '_blank', 'noopener,noreferrer');
                    }
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
            if (!projectId || Number(projectId) <= 0) {
                showNotification('Save the project first, then upload drawings.', 'error');
                input.value = '';
                return;
            }
            
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

        // Delete team member function
        async function deleteTeamMember(workerId) {
            if (!confirm('Are you sure you want to remove this team member?')) return;
            // Ensure URL reflects Team tab so reloads return here
            try {
                if (history.replaceState) history.replaceState(null, '', '#team'); else location.hash = 'team';
            } catch (err) {}

            try {
                const response = await fetch('api/project_files.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'remove_team_member',
                        worker_id: workerId,
                        project_id: projectId
                    })
                });

                const result = await response.json();
                if (result.success) {
                    showNotification('Team member removed.', 'success');
                    logActivity('removed team member', '');
                    setTimeout(() => window.location.reload(), 900);
                } else {
                    showNotification(result.message || 'Failed to remove member', 'error');
                }
            } catch (error) {
                showNotification('Network error occurred', 'error');
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
            const modal = document.getElementById('addTeamMemberModal');
            if (!modal) return;
            // Ensure URL reflects Team tab so reloads return here
            try {
                if (history.replaceState) history.replaceState(null, '', '#team'); else location.hash = 'team';
            } catch (err) {}
            modal.classList.remove('hidden');
            // default to worker list view
            const list = document.getElementById('workerListView');
            const form = document.getElementById('addTeamMemberForm');
            if (list) list.classList.remove('hidden');
            if (form) form.classList.add('hidden');
        }

        function closeAddTeamMemberModal() {
            const modal = document.getElementById('addTeamMemberModal');
            if (!modal) return;
            modal.classList.add('hidden');
            const form = document.getElementById('addTeamMemberForm');
            if (form) {
                form.reset();
                form.classList.add('hidden');
            }
            const list = document.getElementById('workerListView');
            if (list) list.classList.remove('hidden');
        }

        function toggleAddManual(show) {
            const list = document.getElementById('workerListView');
            const form = document.getElementById('addTeamMemberForm');
            if (show) {
                if (list) list.classList.add('hidden');
                if (form) form.classList.remove('hidden');
            } else {
                if (form) form.classList.add('hidden');
                if (list) list.classList.remove('hidden');
            }
        }

        async function assignExistingWorker(workerId) {
            const worker = (workerUsersData || []).find(w => Number(w.id) === Number(workerId));
            if (!worker) {
                showNotification('Worker not found.', 'error');
                return;
            }
            if (!confirm('Assign ' + (worker.full_name || 'this worker') + ' to the project?')) return;

            const params = new URLSearchParams();
            params.append('action', 'add_team_member');
            params.append('project_id', projectId);
            params.append('worker_user_id', String(worker.id || ''));
            params.append('worker_name', worker.full_name || '');
            params.append('worker_role', worker.role || 'Worker');
            params.append('worker_contact', worker.contact || '');
            params.append('csrf_token', csrfToken || '');

            try {
                const resp = await fetch('api/project_files.php', { method: 'POST', body: params });
                const result = await resp.json();
                if (result.success) {
                    showNotification(result.message || 'Worker assigned.', 'success');
                    logActivity('added team member', worker.full_name || '');
                    try { if (history.replaceState) history.replaceState(null, '', '#team'); else location.hash = 'team'; } catch (err) {}
                    setTimeout(() => window.location.reload(), 900);
                } else {
                    showNotification(result.message || 'Failed to assign worker', 'error');
                }
            } catch (err) {
                console.error('Assign worker error:', err);
                showNotification('Network error occurred', 'error');
            }
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
                        try { if (history.replaceState) history.replaceState(null, '', '#team'); else location.hash = 'team'; } catch (err) {}
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
        function viewMemberProfile(id, name, role) {
            document.getElementById('modal-member-name').textContent = name;
            document.getElementById('modal-member-role').textContent = role;

            const initials = name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
            document.getElementById('modal-member-initials').textContent = initials;

            // store selected member id for contact actions
            const modal = document.getElementById('memberProfileModal');
            if (modal) {
                modal.dataset.memberId = id || '';
            }

            document.getElementById('memberProfileModal').classList.remove('hidden');
        }

        function closeMemberProfileModal() {
            document.getElementById('memberProfileModal').classList.add('hidden');
        }

        // Contact modal controls
        function openContactModal() {
            const modal = document.getElementById('memberProfileModal');
            const contactModal = document.getElementById('contactModal');
            const contactTo = document.getElementById('contactTo');
            if (!contactModal) return;

            const memberId = modal?.dataset?.memberId || '';
            const memberName = document.getElementById('modal-member-name').textContent || 'Member';
            contactTo.textContent = memberName;
            contactModal.dataset.targetMemberId = memberId;
            contactModal.classList.remove('hidden');
        }

        function closeContactModal() {
            const contactModal = document.getElementById('contactModal');
            if (!contactModal) return;
            contactModal.classList.add('hidden');
            document.getElementById('contactForm').reset();
        }

        // Handle contact form submission
        const contactForm = document.getElementById('contactForm');
        if (contactForm) {
            contactForm.addEventListener('submit', async function (e) {
                e.preventDefault();
                const contactModal = document.getElementById('contactModal');
                const memberId = contactModal?.dataset?.targetMemberId || 0;
                const formData = new FormData(this);
                const message = formData.get('message') || '';

                if (!memberId || message.trim() === '') {
                    showNotification('Please select a member and write a message.', 'error');
                    return;
                }

                try {
                    const response = await fetch('api/project_files.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'contact_via_signal',
                            project_id: projectId,
                            worker_id: parseInt(memberId, 10),
                            message: String(message)
                        })
                    });

                    const result = await response.json();
                    if (result.success) {
                        showNotification(result.message || 'Message sent.', 'success');
                        logActivity('sent internal signal', '');
                        closeContactModal();
                        setTimeout(() => closeMemberProfileModal(), 800);
                    } else {
                        showNotification(result.message || 'Failed to send message', 'error');
                    }
                } catch (err) {
                    showNotification('Network error occurred', 'error');
                    console.error('Signal send error:', err);
                }
            });
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
