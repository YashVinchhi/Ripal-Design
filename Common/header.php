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

foreach ($candidates as $c) {
  $filePath = PROJECT_ROOT . str_replace('/', DIRECTORY_SEPARATOR, $c);
  if (file_exists($filePath)) {
    echo '<link rel="stylesheet" href="' . htmlspecialchars(BASE_PATH . $c, ENT_QUOTES) . '">' . "\n";
    break; // Only include the first match
  }
}
?>
<link rel="shortcut icon" href="<?php echo BASE_PATH; ?>/assets/Content/Vector.ico" type="image/x-icon">
<!-- Inline header styles to ensure sufficient contrast across pages -->
<style>
  :root {
    --header-text-color: var(--color-text-main, #1A1A1A);
    --header-bg: rgba(255, 255, 255, 0.90);
    --overlay-bg: rgba(115, 18, 9, 0.95);
  }

  /* Make the fixed header readable on light pages by giving it a subtle background and contrasting text */
  nav.fixed-top {
    background: var(--header-bg);
    color: var(--header-text-color);
    backdrop-filter: blur(4px);
  }

  nav.fixed-top a.text-white,
  nav.fixed-top a.text-decoration-none {
    color: var(--header-text-color) !important;
  }

  /* Menu button lines use the same contrasting color */
  .menu-btn .menu-line {
    background: var(--header-text-color);
  }

  /* Place hamburger neatly next to logo when grouped */
  .menu-btn {
    align-items: center;
    margin-right: 8px;
  }

  /* Overlay nav should remain dark and use white text for legibility */
  #navOverlay {
    background: var(--overlay-bg);
  }

  #navOverlay a {
    color: #fff !important;
  }

  /* Ensure logo image remains visible regardless of header background */
  .mirrored-logo img {
    filter: none;
  }
</style>

<!-- Navigation -->
<nav class="fixed-top p-4 mixed-blend-mode">
  <div class="nav-inner">
    <a class="mirrored-logo text-white" href="<?php echo BASE_PATH; ?>/public/index.php">
      <img src="<?php echo BASE_PATH; ?>/assets/Content/Logo.png" alt="Ripal Design Logo" style="height:2rem; display:inline-block;">
    </a>
    <?php if (!empty($_SESSION['user'])): ?>
      <div class="d-flex align-items-center gap-3">
        <a href="<?php echo BASE_PATH; ?>/dashboard/dashboard.php" class="text-white text-decoration-none">Dashboard</a>
        <a href="<?php echo BASE_PATH; ?>/dashboard/profile.php" class="text-white text-decoration-none">Profile</a>
        <a href="<?php echo BASE_PATH; ?>/public/logout.php" class="text-white text-decoration-none">Logout</a>
      </div>
    <?php endif; ?>
    <div class="menu-btn" id="menuBtn" aria-label="Open navigation">
      <span class="menu-line"></span>
      <span class="menu-line" style="width: 20px;"></span>
    </div>
  </div>
</nav>

<!-- Sidebar Navigation -->
<div id="sidebarBackdrop" style="position:fixed; inset:0; background:rgba(0,0,0,0.4); opacity:0; pointer-events:none; transition:opacity .25s ease; z-index:9998;"></div>
<aside id="siteSidebar" style="position:fixed; top:0; right:-320px; width:300px; height:100%; background:var(--header-bg); color:var(--header-text-color); box-shadow:-2px 0 12px rgba(0,0,0,0.12); transition:right .28s ease; z-index:9999; padding:28px; overflow:auto;">
  <button id="sidebarClose" style="position:absolute; left:12px; top:12px; background:transparent; border:0; font-size:24px; color:var(--header-text-color);">&times;</button>
  <h3 style="margin-top:8px; color:var(--header-text-color);">Quick Navigation</h3>
  <nav style="margin-top:14px; display:flex; flex-direction:column; gap:8px;">
    <strong style="margin-top:6px;">Dashboard</strong>
    <a href="<?php echo BASE_PATH; ?>/dashboard/assign_worker.php" class="text-decoration-none" style="color:var(--header-text-color);">Assign Worker</a>
    <a href="<?php echo BASE_PATH; ?>/dashboard/dashboard.php" class="text-decoration-none" style="color:var(--header-text-color);">Dashboard Home</a>
    <a href="<?php echo BASE_PATH; ?>/dashboard/profile.php" class="text-decoration-none" style="color:var(--header-text-color);">Profile</a>
    <a href="<?php echo BASE_PATH; ?>/dashboard/project_details.php" class="text-decoration-none" style="color:var(--header-text-color);">Project Details</a>
    <a href="<?php echo BASE_PATH; ?>/dashboard/review_requests.php" class="text-decoration-none" style="color:var(--header-text-color);">Review Requests</a>

    <hr style="border-color:rgba(0,0,0,0.06); margin:12px 0;">
    <strong>Admin</strong>
    <a href="<?php echo BASE_PATH; ?>/admin/file_viewer.php" class="text-decoration-none" style="color:var(--header-text-color);">File Viewer</a>
    <a href="<?php echo BASE_PATH; ?>/admin/leave_management.php" class="text-decoration-none" style="color:var(--header-text-color);">Leave Management</a>
    <a href="<?php echo BASE_PATH; ?>/admin/payment_gateway.php" class="text-decoration-none" style="color:var(--header-text-color);">Payment Gateway</a>
    <a href="<?php echo BASE_PATH; ?>/admin/project_management.php" class="text-decoration-none" style="color:var(--header-text-color);">Project Management</a>
    <a href="<?php echo BASE_PATH; ?>/admin/user_management.php" class="text-decoration-none" style="color:var(--header-text-color);">User Management</a>

    <hr style="border-color:rgba(0,0,0,0.06); margin:12px 0;">
    <strong>Worker</strong>
    <a href="<?php echo BASE_PATH; ?>/worker/assigned_projects.php" class="text-decoration-none" style="color:var(--header-text-color);">Assigned Projects</a>
    <a href="<?php echo BASE_PATH; ?>/worker/dashboard.php" class="text-decoration-none" style="color:var(--header-text-color);">Worker Dashboard</a>
    <a href="<?php echo BASE_PATH; ?>/worker/project_details.php" class="text-decoration-none" style="color:var(--header-text-color);">Worker Project Details</a>
    <a href="<?php echo BASE_PATH; ?>/worker/worker_rating.php" class="text-decoration-none" style="color:var(--header-text-color);">Worker Ratings</a>
  </nav>
</aside>

<script>
  (function() {
    var menu = document.getElementById('menuBtn');
    var sidebar = document.getElementById('siteSidebar');
    var backdrop = document.getElementById('sidebarBackdrop');
    var closeBtn = document.getElementById('sidebarClose');

    function open() {
      sidebar.style.right = '0';
      backdrop.style.opacity = '1';
      backdrop.style.pointerEvents = 'auto';
      menu.classList.add('is-active');
    }

    function close() {
      sidebar.style.right = '-320px';
      backdrop.style.opacity = '0';
      backdrop.style.pointerEvents = 'none';
      menu.classList.remove('is-active');
    }
    if (menu) {
      menu.addEventListener('click', function() {
        if (sidebar.style.right === '0px') close();
        else open();
      });
    }
    if (closeBtn) closeBtn.addEventListener('click', close);
    if (backdrop) backdrop.addEventListener('click', close);
  })();
</script>