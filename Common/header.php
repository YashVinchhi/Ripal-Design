<?php
// header.php - include this at the top of pages to get shared nav
// Load common stylesheet fallbacks so header always appears styled during testing.
require_once __DIR__ . '/../includes/config.php';

// Ensure session available so header can show user-specific nav
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
?>
<?php
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

<!-- Navigation -->
<nav class="fixed-top p-4 d-flex justify-content-between align-items-center mixed-blend-mode">
    <a class="mirrored-logo text-white" href="<?php echo BASE_PATH; ?>/public/index.php">
        <img src="<?php echo BASE_PATH; ?>/assets/Content/Logo.png" alt="Ripal Design Logo" style="height:2rem; display:inline-block;">
    </a>
    <div class="menu-btn" id="menuBtn">
        <span class="menu-line"></span>
        <span class="menu-line" style="width: 20px;"></span>
    </div>
    <?php if (!empty($_SESSION['user'])): ?>
        <div class="d-flex align-items-center gap-3">
            <a href="<?php echo BASE_PATH; ?>/dashboard/dashboard.php" class="text-white text-decoration-none">Dashboard</a>
            <a href="<?php echo BASE_PATH; ?>/dashboard/profile.php" class="text-white text-decoration-none">Profile</a>
            <a href="<?php echo BASE_PATH; ?>/public/logout.php" class="text-white text-decoration-none">Logout</a>
        </div>
    <?php endif; ?>
</nav>

<!-- Overlay Navigation -->
<div id="navOverlay" class="d-flex flex-column align-items-center justify-content-center">
    <button class="position-absolute top-0 end-0 m-4 btn btn-link text-white text-decoration-none display-4" id="closeBtn">&times;</button>
    <nav class="d-flex flex-column text-center gap-4">
        <a class="display-4 text-white text-decoration-none font-serif" href="<?php echo BASE_PATH; ?>/public/index.php">Home</a>
        <a class="display-4 text-white text-decoration-none font-serif fst-italic" href="<?php echo BASE_PATH; ?>/public/about_us.php">About</a>
        <a class="display-4 text-white text-decoration-none font-serif" href="<?php echo BASE_PATH; ?>/public/services.php">Services</a>
        <a class="display-4 text-white text-decoration-none font-serif" href="<?php echo BASE_PATH; ?>/public/contact_us.php">Contact</a>
    </nav>
</div>