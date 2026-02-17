<?php
/**
 * Common Header Component
 * 
 * Unified header for all pages (public, dashboard, admin, etc.)
 * Renders HTML head assets, navigation, and logo.
 * 
 * Usage:
 * <?php require_once __DIR__ . '/../Common/header.php'; ?>
 * 
 * @package RipalDesign
 * @subpackage Components
 */

// Ensure configuration is loaded
if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/../includes/config.php';
}

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Determine header mode (can be set by including page)
$headerMode = $HEADER_MODE ?? 'public';

// Find and include the main stylesheet
$stylesheetCandidates = [
    '/styles.css',
    '/public/css/index.css',
    '/assets/css/styles.css',
    '/assets/styles.css'
];

// Render common HTML head assets
?>
<!-- Common Stylesheets and Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

<!-- Bootstrap CSS and Icons (needed for footer and header) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

<?php if (!isset($DISABLE_EXTERNAL_CSS) || !$DISABLE_EXTERNAL_CSS): ?>
<?php
// Include TailwindCSS for pages that need it
if ($headerMode === 'public' || $headerMode === 'dashboard') {
    echo '<script src="https://cdn.tailwindcss.com"></script>' . "\n";
    echo '<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          primary: "#731209",
        }
      }
    }
  }
</script>' . "\n";
}

// Include main stylesheet
foreach ($stylesheetCandidates as $candidate) {
    $filePath = PROJECT_ROOT . str_replace('/', DIRECTORY_SEPARATOR, $candidate);
    if (file_exists($filePath)) {
        echo '<link rel="stylesheet" href="' . esc_attr(BASE_PATH . $candidate) . '">' . "\n";
        break;
    }
}
?>
<?php endif; ?>

<!-- Favicons -->
<link rel="shortcut icon" href="<?php echo esc_attr(BASE_PATH); ?>/assets/Content/Vector.ico" type="image/x-icon">

<!-- Header Navigation (Always loaded) -->
<link rel="stylesheet" href="<?php echo esc_attr(BASE_PATH); ?>/Common/header.css">
<nav class="alt-header">
    <div class="alt-logo">
        <a href="<?php echo esc_attr(BASE_PATH); ?>/public/index.php">
            <img src="<?php echo esc_attr(BASE_PATH); ?>/assets/Content/Logo.png" alt="Ripal Design Logo" style="height:3rem">
        </a>
        <div style="color:var(--alt-text, #fff); font-weight:700; font-size:1.5rem; text-shadow: 5px 5px 5px rgba(0,0,0,0.3);">
            Ripal Design
        </div>
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

<!-- Navigation Overlay -->
<div id="altOverlay">
    <div class="alt-panel" role="dialog" aria-modal="true" aria-label="Site menu">
        <nav>
            <a href="<?php echo esc_attr(BASE_PATH); ?>/public/index.php">Home</a>
            <a href="<?php echo esc_attr(BASE_PATH); ?>/public/services.php">Services</a>
            <a href="<?php echo esc_attr(BASE_PATH); ?>/public/products.php">Products</a>
            <a href="<?php echo esc_attr(BASE_PATH); ?>/public/about_us.php">About</a>
            <a href="<?php echo esc_attr(BASE_PATH); ?>/public/contact_us.php">Contact</a>
        </nav>
  

        
        <div class="panel-footer">
            <?php if (is_logged_in()): ?>
                <a href="<?php echo esc_attr(BASE_PATH); ?>/dashboard/dashboard.php" class="btn-alt btn-login">Dashboard</a>
                <a href="<?php echo esc_attr(BASE_PATH); ?>/public/logout.php" class="btn-alt btn-signup">Logout</a>
            <?php else: ?>
                <a href="<?php echo esc_attr(BASE_PATH); ?>/public/login.php" class="btn-alt btn-login">Login</a>
                <a href="<?php echo esc_attr(BASE_PATH); ?>/public/signup.php" class="btn-alt btn-signup">Sign Up</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Header Navigation Script -->
<script src="<?php echo esc_attr(BASE_PATH); ?>/assets/js/header-nav.js" defer></script>
