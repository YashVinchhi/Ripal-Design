<?php
// header.php - include this at the top of pages to get shared nav
// Load common stylesheet fallbacks so header always appears styled during testing.
require_once __DIR__ . '/../includes/config.php';

// Ensure session available so header can show user-specific nav
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$dashboardProfileUrl = function_exists('base_path')
    ? base_path('dashboard/profile.php')
    : rtrim((string)BASE_PATH, '/') . '/dashboard/profile.php';

// Server-side: only emit stylesheet links that exist on disk to avoid client 404s
$candidates = [
    '/assets/css/styles.css',
    '/assets/css/tailwind.css',
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
        $href = rtrim((string)BASE_PATH, '/') . PUBLIC_PATH_PREFIX . $c;
        echo '<link rel="stylesheet" href="' . htmlspecialchars($href, ENT_QUOTES) . '">' . "\n";
        break; // Only include the first match
    }
}
?>
<link rel="icon" href="<?php echo BASE_PATH; ?>/assets/Content/Vector.ico" type="image/x-icon">
<link rel="shortcut icon" href="<?php echo BASE_PATH; ?>/assets/Content/Vector.ico" type="image/x-icon">
<link rel="apple-touch-icon" href="<?php echo BASE_PATH; ?>/assets/Content/Vector.ico">
<!-- Immersive Navigation Header -->
<link rel="stylesheet" href="<?php echo BASE_PATH; ?>/Common/header.css">
<?php if (function_exists('webmcp_render_bootstrap_once')) { webmcp_render_bootstrap_once(); } ?>
<?php if (isset($HEADER_MODE) && $HEADER_MODE === 'dashboard'): ?>
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/Common/admin-responsive.css">
<?php endif; ?>
<nav class="alt-header">
    <div class="alt-logo">
        <?php
            $logoHref = rtrim((string)BASE_PATH, '/') . PUBLIC_PATH_PREFIX . '/index.php';
            if (function_exists('current_user')) {
                $cu = current_user();
                $role = is_array($cu) ? strtolower(trim((string)($cu['role'] ?? ''))) : '';
                if ($role === 'client') {
                    $logoHref = rtrim((string)BASE_PATH, '/') . '/client/dashboard.php';
                } elseif ($role === 'worker') {
                    $logoHref = rtrim((string)BASE_PATH, '/') . '/worker/dashboard.php';
                } elseif ($role === 'admin') {
                    $logoHref = rtrim((string)BASE_PATH, '/') . '/admin/dashboard.php';
                } elseif (function_exists('auth_dashboard_url')) {
                    $logoHref = auth_dashboard_url();
                }
            } elseif (function_exists('is_logged_in') && is_logged_in() && function_exists('auth_dashboard_url')) {
                $logoHref = auth_dashboard_url();
            }
        ?>
        <a href="<?php echo htmlspecialchars($logoHref, ENT_QUOTES, 'UTF-8'); ?>" class="flex items-center gap-3 no-underline">
            <img src="<?php echo BASE_PATH; ?>/assets/Content/Logo.png" alt="Ripal Design Logo" class="h-10" onerror="this.onerror=null;this.src='https://placehold.co/160x60/b91c1c/ffffff?text=RD'">
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
        <?php include __DIR__ . '/notifications.php'; ?>
        <nav>
            <?php
                $navRole = 'dashboard';
                if (function_exists('auth_resolve_navigation_role') && function_exists('current_user')) {
                    $navRole = auth_resolve_navigation_role(current_user());
                } elseif (function_exists('current_user')) {
                    $fallbackUser = current_user();
                    $fallbackRole = is_array($fallbackUser) ? strtolower((string)($fallbackUser['role'] ?? '')) : '';
                    if ($fallbackRole === 'admin' || $fallbackRole === 'worker') {
                        $navRole = $fallbackRole;
                    }
                }

                $sessionRole = '';
                if (function_exists('current_user')) {
                    $sessionUser = current_user();
                    $sessionRole = is_array($sessionUser) ? strtolower((string)($sessionUser['role'] ?? '')) : '';
                }

                $roleDashboardLink = rtrim((string)BASE_PATH, '/') . '/dashboard/dashboard.php';
                if (function_exists('current_user')) {
                    $cu = current_user();
                    $role = is_array($cu) ? strtolower(trim((string)($cu['role'] ?? ''))) : '';
                    if ($role === 'client') {
                        $roleDashboardLink = rtrim((string)BASE_PATH, '/') . '/client/dashboard.php';
                    } elseif ($role === 'worker') {
                        $roleDashboardLink = rtrim((string)BASE_PATH, '/') . '/worker/dashboard.php';
                    } elseif ($role === 'admin') {
                        $roleDashboardLink = rtrim((string)BASE_PATH, '/') . '/admin/dashboard.php';
                    } elseif (function_exists('auth_dashboard_url')) {
                        $roleDashboardLink = auth_dashboard_url();
                    }
                } elseif (function_exists('is_logged_in') && is_logged_in() && function_exists('auth_dashboard_url')) {
                    $roleDashboardLink = auth_dashboard_url();
                }

                $menuSections = [
                    'dashboard' => [
                        'title' => 'Dashboard',
                        'links' => [
                            ['href' => $roleDashboardLink, 'label' => 'Dashboard Home'],
                            ['href' => $dashboardProfileUrl, 'label' => 'Profile Settings'],
                            ['href' => BASE_PATH . '/dashboard/review_requests.php', 'label' => 'Review Requests'],
                        ],
                    ],
                    'worker' => [
                        'title' => 'Worker Portal',
                        'links' => [
                            ['href' => BASE_PATH . '/dashboard/dashboard.php', 'label' => 'Worker Dashboard'],
                            ...($sessionRole === 'client' ? [] : [
                                ['href' => BASE_PATH . '/worker/assigned_projects.php', 'label' => 'Assigned Projects'],
                            ]),
                            ['href' => BASE_PATH . '/worker/worker_rating.php', 'label' => 'My Ratings'],
                        ],
                    ],
                    'admin' => [
                        'title' => 'Administration',
                        'links' => [
                            ['href' => BASE_PATH . '/dashboard/dashboard.php', 'label' => 'Admin Dashboard'],
                            ['href' => BASE_PATH . '/admin/project_management.php', 'label' => 'Project Portfolio'],
                            ['href' => BASE_PATH . '/admin/user_management.php', 'label' => 'User Controls'],
                            ['href' => BASE_PATH . '/admin/leave_management.php', 'label' => 'Leave Manager'],
                            ['href' => BASE_PATH . '/admin/payment_gateway.php', 'label' => 'Financial Gateway'],
                        ],
                    ],
                ];

                $activeSection = $menuSections[$navRole] ?? $menuSections['dashboard'];
            ?>
            <?php if (!empty($activeSection['title'])): ?>
                <strong class="text-white/40 text-[10px] uppercase tracking-[0.2em] mb-2 px-4"><?php echo htmlspecialchars((string)$activeSection['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
            <?php endif; ?>
            <?php foreach (($activeSection['links'] ?? []) as $link): ?>
                <a href="<?php echo htmlspecialchars((string)($link['href'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string)($link['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="panel-footer">
            <a href="<?php echo rtrim((string)BASE_PATH, '/') . PUBLIC_PATH_PREFIX . '/logout.php'; ?>" class="btn-alt btn-login w-full text-center">Logout</a>
        </div>
    </div>
</div>

<script src="<?php echo BASE_PATH; ?>/assets/js/header-nav.js" defer></script>
