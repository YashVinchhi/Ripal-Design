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

<!-- Bootstrap CSS and Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet"> 
<!-- <link rel="stylesheet" href="css/bootstrap.min.css"> -->

<!-- Lucide Icons (CAD style) -->
<script src="https://unpkg.com/lucide@latest"></script>

<?php if (!isset($DISABLE_EXTERNAL_CSS) || !$DISABLE_EXTERNAL_CSS): ?>
<?php
// Include TailwindCSS globally for consistent design system
echo '<script src="https://cdn.tailwindcss.com"></script>' . "\n";
echo '<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          "rajkot-rust": "#94180C",
          "canvas-white": "#F9FAFB",
          "foundation-grey": "#2D2D2D",
          "slate-accent": "#334155",
          "approval-green": "#15803D",
          "pending-amber": "#B45309",
          primary: "#94180C",
          background: "#F9FAFB",
        },
        fontFamily: {
          sans: ["Inter", "sans-serif"],
          serif: ["Playfair Display", "serif"],
        },
        boxShadow: {
            "premium": "0 10px 30px rgba(0, 0, 0, 0.05)",
            "premium-hover": "0 20px 40px rgba(0, 0, 0, 0.1)",
        }
      }
    }
  }
</script>' . "\n";

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
<?php if ($headerMode === 'dashboard'): ?>
    <link rel="stylesheet" href="<?php echo esc_attr(BASE_PATH); ?>/Common/admin-responsive.css">
<?php endif; ?>
<nav class="alt-header">
    <div class="alt-logo">
        <a href="<?php echo esc_attr(BASE_PATH); ?>/public/index.php" class="flex items-center gap-3 no-underline">
            <img src="<?php echo esc_attr(BASE_PATH); ?>/assets/Content/Logo.png" alt="Ripal Design Logo" class="h-10">
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

<!-- Navigation Overlay -->
<div id="altOverlay">
    <div class="alt-panel" role="dialog" aria-modal="true" aria-label="Site menu">
        <?php include __DIR__ . '/notifications.php'; ?>

        <nav>
            <?php if ($headerMode === 'dashboard'): ?>
                <strong class="text-white/40 text-[10px] uppercase tracking-[0.2em] mb-2 px-4">Dashboard</strong>
                <a href="<?php echo esc_attr(BASE_PATH); ?>/dashboard/dashboard.php">Dashboard Home</a>
                <a href="<?php echo esc_attr(BASE_PATH); ?>/dashboard/project_details.php">Project Details</a>
                <a href="<?php echo esc_attr(BASE_PATH); ?>/dashboard/profile.php">Profile Settings</a>
                <a href="<?php echo esc_attr(BASE_PATH); ?>/dashboard/review_requests.php">Review Requests</a>

                <hr class="border-white/10 my-4 mx-4">
                <strong class="text-white/40 text-[10px] uppercase tracking-[0.2em] mb-2 px-4">Worker Portal</strong>
                <a href="<?php echo esc_attr(BASE_PATH); ?>/worker/dashboard.php">Worker Dashboard</a>
                <a href="<?php echo esc_attr(BASE_PATH); ?>/worker/assigned_projects.php">Assigned Projects</a>
                <a href="<?php echo esc_attr(BASE_PATH); ?>/worker/project_details.php">Project Details</a>
                <a href="<?php echo esc_attr(BASE_PATH); ?>/worker/worker_rating.php">My Ratings</a>

                <hr class="border-white/10 my-4 mx-4">
                <strong class="text-white/40 text-[10px] uppercase tracking-[0.2em] mb-2 px-4">Administration</strong>
                <a href="<?php echo esc_attr(BASE_PATH); ?>/admin/dashboard.php">Admin Dashboard</a>
                <a href="<?php echo esc_attr(BASE_PATH); ?>/admin/project_management.php">Project Portfolio</a>
                <a href="<?php echo esc_attr(BASE_PATH); ?>/admin/user_management.php">User Controls</a>
                <a href="<?php echo esc_attr(BASE_PATH); ?>/admin/leave_management.php">Leave Manager</a>
                <a href="<?php echo esc_attr(BASE_PATH); ?>/admin/payment_gateway.php">Financial Gateway</a>
            <?php else: ?>
                <a href="<?php echo esc_attr(BASE_PATH); ?>/public/index.php">Home</a>
                <a href="<?php echo esc_attr(BASE_PATH); ?>/public/services.php">Services</a>
                <a href="<?php echo esc_attr(BASE_PATH); ?>/public/products.php">Products</a>
                <a href="<?php echo esc_attr(BASE_PATH); ?>/public/about_us.php">About</a>
                <a href="<?php echo esc_attr(BASE_PATH); ?>/public/contact_us.php">Contact</a>
            <?php endif; ?>
        </nav>
  

        
        <div class="panel-footer">
            <?php if (is_logged_in()): ?>
                <?php if ($headerMode !== 'dashboard'): ?>
                    <a href="<?php echo esc_attr(BASE_PATH); ?>/dashboard/dashboard.php" class="btn-alt btn-login">Dashboard</a>
                <?php endif; ?>
                <a href="<?php echo esc_attr(BASE_PATH); ?>/public/logout.php" class="btn-alt <?php echo $headerMode === 'dashboard' ? 'btn-login w-full text-center' : 'btn-signup'; ?>">Logout</a>
            <?php else: ?>
                <a href="<?php echo esc_attr(BASE_PATH); ?>/public/login.php" class="btn-alt btn-login">Login</a>
                <a href="<?php echo esc_attr(BASE_PATH); ?>/public/signup.php" class="btn-alt btn-signup">Sign Up</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Header Navigation Script -->
<script src="<?php echo esc_attr(BASE_PATH); ?>/assets/js/header-nav.js" defer></script>
