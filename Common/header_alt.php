<?php
// header.php - include this at the top of pages to get shared nav
// Load common stylesheet fallbacks so header always appears styled during testing.
require_once __DIR__ . '/../includes/config.php';

// Ensure session available so header can show user-specific nav
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Server-side: only emit stylesheet links that exist on disk to avoid client 404s
$candidates = [
    '/styles.css',
    '/public/styles.css',
    '/assets/css/styles.css',
    '/assets/styles.css'
];

// Typography & Icons (Global)
echo '<link rel="preconnect" href="https://fonts.googleapis.com" />' . "\n";
echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />' . "\n";
echo '<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />' . "\n";
echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">' . "\n";
echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">' . "\n";
// echo '<link rel="stylesheet" href="css/bootstrap.min.css">' . "\n";

foreach ($candidates as $c) {
    $filePath = PROJECT_ROOT . str_replace('/', DIRECTORY_SEPARATOR, $c);
    if (file_exists($filePath)) {
        echo '<link rel="stylesheet" href="' . htmlspecialchars(BASE_PATH . $c, ENT_QUOTES) . '">' . "\n";
        break; // Only include the first match
    }
}
?>
<link rel="shortcut icon" href="<?php echo BASE_PATH; ?>/assets/Content/Vector.ico" type="image/x-icon">
<!-- Immersive Navigation Header -->
<link rel="stylesheet" href="<?php echo BASE_PATH; ?>/Common/header.css">
<nav class="alt-header">
    <div class="alt-logo">
        <a href="<?php echo BASE_PATH; ?>/public/index.php" class="flex items-center gap-3 no-underline">
            <img src="<?php echo BASE_PATH; ?>/assets/Content/Logo.png" alt="Ripal Design Logo" class="h-10">
            <span class="text-white font-serif font-bold text-xl tracking-tight">Ripal Design</span>
        </a>
    </div>
    
    <div class="alt-menu">
        <button id="altMenuBtn" class="alt-btn" aria-label="Open menu" aria-expanded="false" aria-controls="altOverlay">
            <span class="alt-hamburger">
                <span></span>
                <span></span>
                <span></span>
            </span>
        </button>
    </div>
</nav>

<!-- Sidebar Navigation -->
<div id="altOverlay">
    <div class="alt-panel" role="dialog" aria-modal="true" aria-label="Site menu">
        <nav>
            <strong class="text-white/40 text-[10px] uppercase tracking-[0.2em] mb-2 px-4">Dashboard</strong>
            <a href="<?php echo BASE_PATH; ?>/dashboard/dashboard.php">Dashboard Home</a>
            <a href="<?php echo BASE_PATH; ?>/dashboard/profile.php">Profile Settings</a>
            <a href="<?php echo BASE_PATH; ?>/dashboard/review_requests.php">Review Requests</a>

            <hr class="border-white/10 my-4 mx-4">
            <strong class="text-white/40 text-[10px] uppercase tracking-[0.2em] mb-2 px-4">Worker Portal</strong>
            <a href="<?php echo BASE_PATH; ?>/worker/dashboard.php">Worker Dashboard</a>
            <a href="<?php echo BASE_PATH; ?>/worker/assigned_projects.php">Assigned Projects</a>
            <a href="<?php echo BASE_PATH; ?>/worker/worker_rating.php">My Ratings</a>

            <hr class="border-white/10 my-4 mx-4">
            <strong class="text-white/40 text-[10px] uppercase tracking-[0.2em] mb-2 px-4">Administration</strong>
            <a href="<?php echo BASE_PATH; ?>/admin/project_management.php">Project Portfolio</a>
            <a href="<?php echo BASE_PATH; ?>/admin/user_management.php">User Controls</a>
            <a href="<?php echo BASE_PATH; ?>/admin/leave_management.php">Leave Manager</a>
            <a href="<?php echo BASE_PATH; ?>/admin/payment_gateway.php">Financial Gateway</a>
        </nav>

        <div class="panel-footer">
            <a href="<?php echo BASE_PATH; ?>/public/logout.php" class="btn-alt btn-login w-full text-center">Logout</a>
        </div>
    </div>
</div>

<script src="<?php echo BASE_PATH; ?>/assets/js/header-nav.js" defer></script>
