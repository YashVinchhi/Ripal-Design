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

// Temporarily hide displayed PHP warnings/notices during header render
// (we still let them log; this prevents raw warnings breaking the UI)
@ini_set('display_errors', '0');

// Ensure configuration is loaded
if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/../app/Core/Config/config.php';
}
if (file_exists(__DIR__ . '/../app/Core/Support/assets.php')) {
    require_once __DIR__ . '/../app/Core/Support/assets.php';
}

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

if (function_exists('csrf_token')) {
    csrf_token();
}
?>
<?php
// Safe defaults to prevent runtime warnings when header is included standalone
if (!isset($isPublicHeader)) { $isPublicHeader = false; }
if (!function_exists('esc_attr')) {
    function esc_attr($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
if (!function_exists('headerText')) {
    function headerText($k, $d = '') { return $d; }
}
if (!isset($headerText) || !is_callable($headerText)) { $headerText = 'headerText'; }
if (!isset($headerPublicUrl) || !is_callable($headerPublicUrl)) {
    $headerPublicUrl = function($p){ return rtrim((string)(defined('BASE_PATH') ? BASE_PATH : ''), '/') . '/' . ltrim((string)$p, '/'); };
}
if (!isset($logoHref)) { $logoHref = (defined('BASE_PATH') ? rtrim((string)BASE_PATH, '/') . '/' : '/'); }
if (!isset($roleDashboardLink)) { $roleDashboardLink = (defined('BASE_PATH') ? rtrim((string)BASE_PATH, '/') . '/dashboard.php' : '/dashboard.php'); }
if (!isset($dashboardProfileUrl)) { $dashboardProfileUrl = (defined('BASE_PATH') ? rtrim((string)BASE_PATH, '/') . '/dashboard/profile.php' : '/dashboard/profile.php'); }
if (!isset($headerContent) || !is_array($headerContent)) { $headerContent = []; }
if (!isset($radiusMode)) {
    $radiusMode = strtolower((string)(getenv('UI_RADIUS') ?: 'sharp'));
    $radiusMode = in_array($radiusMode, ['rounded', 'sharp'], true) ? $radiusMode : 'sharp';
}
if (!isset($brandLogoImage) || (string)$brandLogoImage === '') {
    $brandLogoImage = (defined('BASE_PATH') ? rtrim((string)BASE_PATH, '/') : '') . '/assets/images/rd-placeholder.svg';
}

// Normalize header mode variables: some pages set $HEADER_MODE while others set $headerMode
if (!isset($headerMode) && isset($HEADER_MODE)) { $headerMode = $HEADER_MODE; }

// Ensure $role is available early for header rendering when possible
if (!isset($role) && function_exists('current_user') && function_exists('is_logged_in') && is_logged_in()) {
    $cu = current_user();
    $role = is_array($cu) ? strtolower(trim((string)($cu['role'] ?? ''))) : '';
}
?>
    <?php /* toolbar moved below the header nav so it renders in the BODY */ ?>
<?php
if (!$isPublicHeader && function_exists('current_user')) {
    $cu = current_user();
    $role = is_array($cu) ? strtolower(trim((string)($cu['role'] ?? ''))) : '';
    render_if('dashboard', 'nav.logo_href', static function () use (&$logoHref, $role): void {
        if ($role === 'client') {
            $logoHref = rtrim((string)BASE_PATH, '/') . '/client/dashboard.php';
        } elseif ($role === 'worker') {
            $logoHref = rtrim((string)BASE_PATH, '/') . '/worker/dashboard.php';
        } elseif ($role === 'admin') {
            $logoHref = rtrim((string)BASE_PATH, '/') . '/admin/dashboard.php';
        } elseif (function_exists('auth_dashboard_url')) {
            $logoHref = auth_dashboard_url();
        }
    });
} elseif (!$isPublicHeader && function_exists('is_logged_in') && is_logged_in() && function_exists('auth_dashboard_url')) {
    $logoHref = auth_dashboard_url();
}

// Active nav helper for public menu links.
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
$isActiveNav = static function ($path) use ($currentPath) {
    $target = basename((string)$path);
    if ($target === '') {
        return false;
    }
    $current = basename((string)$currentPath);
    return $current === $target;
};

// Render common HTML head assets
?>
    <!-- Dynamic page title -->
    <title><?php echo htmlspecialchars($pageTitle ?? 'Ripal Design - Architecture & Project Management'); ?></title>

    <!-- Dynamic meta description -->
    <meta name="description" content="<?php echo htmlspecialchars($metaDesc ?? 'Ripal Design — architecture and project management for design firms.'); ?>">

    <!-- Canonical URL (strip query string to avoid duplicate content) -->
    <?php
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $canonical = $proto . '://' . $host . $path;
    ?>
    <link rel="canonical" href="<?php echo esc_attr($canonical); ?>">

    <?php if (function_exists('csrf_meta_tag')) { echo csrf_meta_tag(); } ?>

    <!-- Analytics include (GA4 + Clarity placeholders) - disabled in development -->
    <?php if (!defined('APP_ENV') || APP_ENV !== 'development') {
        if (file_exists(__DIR__ . '/analytics.php')) { include __DIR__ . '/analytics.php'; }
    } ?>

<!-- Common Stylesheets and Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<!-- TODO: Self-host Google Fonts to enable Subresource Integrity (SRI) checks. -->
<link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:opsz,wght@6..96,400;6..96,500;6..96,600&family=Space+Grotesk:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<!-- Phantom-UI: SSR pre-hydration CSS + CDN bundle -->
<style>
    /* Prevent content flash before Phantom-UI hydrates */
    phantom-ui[loading] * {
        -webkit-text-fill-color: transparent !important;
        pointer-events: none;
        user-select: none;
    }
    phantom-ui[loading] img, phantom-ui[loading] svg,
    phantom-ui[loading] video, phantom-ui[loading] canvas,
    phantom-ui[loading] button, phantom-ui[loading] [role="button"] {
        opacity: 0 !important;
    }
</style>
<!-- TODO: Replace placeholder SRI hash with real one from srihash.org -->
<script defer src="https://cdn.jsdelivr.net/npm/@aejkatappaja/phantom-ui/dist/phantom-ui.cdn.js" crossorigin="anonymous" nonce="<?php echo htmlspecialchars($_REQUEST['csp_nonce']); ?>"></script>

<!-- Icons -->
<!-- TODO: Replace placeholder SRI hash with real one from srihash.org -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous">

<?php if ((empty($HEADER_MODE) || $HEADER_MODE !== 'public') && (!isset($DISABLE_EXTERNAL_CSS) || !$DISABLE_EXTERNAL_CSS)): ?>
<?php
$tailwindBuiltPath = PROJECT_ROOT . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'tailwind.css';
$stylesBuiltPath = PROJECT_ROOT . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'styles.css';
if (file_exists($tailwindBuiltPath)) {
    echo '<link rel="stylesheet" href="' . esc_attr(asset('assets/css/tailwind.css')) . '">' . "\n";
}
if (file_exists($stylesBuiltPath)) {
    echo '<link rel="stylesheet" href="' . esc_attr(asset('assets/css/styles.css')) . '">' . "\n";
}

$variablesCssPath = PROJECT_ROOT . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'variables.css';
$mainCssPath = PROJECT_ROOT . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'main.css';
$variablesCss = rtrim((string)BASE_PATH, '/') . PUBLIC_PATH_PREFIX . '/css/variables.css';
$mainCss = rtrim((string)BASE_PATH, '/') . PUBLIC_PATH_PREFIX . '/css/main.css';
echo '<link rel="stylesheet" href="' . esc_attr(asset('assets/css/ui-radius.css')) . '">' . "\n";
echo '<link rel="stylesheet" href="' . esc_attr($variablesCss . asset_version_suffix_for_file($variablesCssPath)) . '">' . "\n";
echo '<link rel="stylesheet" href="' . esc_attr($mainCss . asset_version_suffix_for_file($mainCssPath)) . '">' . "\n";
?>
<?php endif; ?>

<script nonce="<?php echo htmlspecialchars($_REQUEST['csp_nonce']); ?>">
    document.documentElement.setAttribute('data-ui-radius', <?php echo json_encode($radiusMode); ?>);
</script>

<!-- Favicons -->
<?php
// Prefer existing configured favicon; fall back to local SVG to avoid 404/CSP issues
$localFavSvg = rtrim((string)BASE_PATH, '/') . '/assets/images/favicon.svg';
if (empty($faviconImage)) {
    $faviconImage = $localFavSvg;
}
// If configured favicon is an .ico and file missing, prefer local svg
$tryIcoPath = PROJECT_ROOT . '/assets/images/favicon.ico';
if (isset($faviconImage) && strpos((string)$faviconImage, '.ico') !== false && !file_exists($tryIcoPath)) {
    $faviconImage = $localFavSvg;
}
?>
<link rel="icon" href="<?php echo esc_attr($faviconImage); ?>" type="image/x-icon">
<link rel="shortcut icon" href="<?php echo esc_attr($faviconImage); ?>" type="image/x-icon">
<link rel="apple-touch-icon" href="<?php echo esc_attr($faviconImage); ?>">

<!-- Header Navigation (Always loaded) -->
<!-- Layout tokens for legacy CSS (variables: spacing, header height, container) -->
<?php if (empty($HEADER_MODE) || $HEADER_MODE !== 'public'): ?>
    <link rel="stylesheet" href="<?php echo esc_attr(asset('assets/css/_layout.css')); ?>">
    <link rel="stylesheet" href="<?php echo esc_attr(asset('public/css/header.css')); ?>">
<?php endif; ?>
<?php if (function_exists('is_logged_in') && is_logged_in()): ?>
    <style>
        /* Compact, app-like header for logged-in users: menu bar height set to 6% of viewport height */
        :root { --rd-logged-header-max: 6vh; }
        nav.alt-header {
            height: var(--rd-logged-header-max);
            max-height: var(--rd-logged-header-max);
            align-items: center;
            padding-top: 3vh;
            padding-bottom: 3vh;
            gap: 0.75rem;
        }
        nav.alt-header .alt-logo img {
            height: calc(var(--rd-logged-header-max) - 0.6rem) !important;
            max-height: calc(var(--rd-logged-header-max) - 0.6rem) !important;
            width: auto !important;
        }
        nav.alt-header .alt-logo span {
            font-size: 1rem;
            line-height: 1;
            display: inline-block;
            vertical-align: middle;
        }
        nav.alt-header .alt-menu { margin-left: auto; }
        /* Admin horizontal menu in header */
        .alt-main-menu {
            display: flex;
            gap: 0.75rem;
            align-items: center;
            margin-left: 1rem;
            white-space: nowrap;
            overflow: auto;
        }
        .alt-main-menu a {
            color: #2d2d2d; /* muted gray to distinguish from main UI */
            text-decoration: none;
            text-transform: uppercase;
            font-weight: 600;
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            opacity: 0.95;
        }
        .alt-main-menu a:hover { background: rgba(255,255,255,0.04); color: #94180c; }
        @media (max-width: 768px) {
            .alt-main-menu { display: none; }
        }
        /* Ensure overlay/panel positions respect compact header */
        #altOverlay .alt-panel { top: var(--rd-logged-header-max); }
        @media (max-width: 640px) {
            nav.alt-header { padding-left: 0.5rem; padding-right: 0.5rem; }
            nav.alt-header .alt-logo span { display: none; }
        }
        /* Toolbar CSS moved to public/css/header.css for caching and maintainability */
        /* See: /public/css/header.css */
    </style>
<?php endif; ?>
<?php if (empty($HEADER_MODE) || $HEADER_MODE !== 'public') { if (function_exists('webmcp_render_bootstrap_once')) { webmcp_render_bootstrap_once(); } } ?>
<?php if ($headerMode === 'dashboard'): ?>
    <link rel="stylesheet" href="<?php echo esc_attr(rtrim((string) BASE_PATH, '/') . PUBLIC_PATH_PREFIX . '/css/admin-responsive.css'); ?>">
<?php endif; ?>
<nav class="alt-header">
    <div class="alt-logo">
        <a href="<?php echo esc_attr($logoHref); ?>" class="flex items-center gap-3 no-underline">
            <?php $localLogoFallback = rtrim((string)BASE_PATH, '/') . '/assets/images/rd-placeholder.svg'; ?>
            <img src="<?php echo esc_attr($brandLogoImage); ?>" alt="Ripal Design Logo" class="h-10"<?php echo function_exists('rd_content_image_style_attr') ? rd_content_image_style_attr($headerContent, 'brand_logo_image') : ''; ?> onerror="this.onerror=null;this.src='<?php echo esc_attr($localLogoFallback); ?>'">
            <?php if (!(function_exists('is_logged_in') && is_logged_in())): ?>
                <span class="text-white font-serif font-bold text-xl tracking-tight"><?php echo htmlspecialchars($headerText('brand_name', 'Ripal Design')); ?></span>
            <?php endif; ?>
        </a>
    </div>

    <?php render_if('dashboard', 'nav.admin.topmenu', static function (): void { ?>
        <div class="alt-main-menu" role="navigation" aria-label="Admin menu">
            <a href="<?php echo esc_attr(rtrim((string)BASE_PATH, '/') . '/dashboard/dashboard.php'); ?>">DASHBOARD</a>
            <a href="<?php echo esc_attr(rtrim((string)BASE_PATH, '/') . '/admin/project_management.php'); ?>">PORTFOLIO</a>
            <a href="<?php echo esc_attr(rtrim((string)BASE_PATH, '/') . '/admin/user_management.php'); ?>">USER CONTROLS</a>
            <a href="<?php echo esc_attr(rtrim((string)BASE_PATH, '/') . '/admin/leave_management.php'); ?>">LEAVE MANAGER</a>
            <a href="<?php echo esc_attr(rtrim((string)BASE_PATH, '/') . '/admin/payment_gateway.php'); ?>">FINANCIAL GATEWAY</a>
            <a href="<?php echo esc_attr(rtrim((string)BASE_PATH, '/') . '/admin/content_management.php'); ?>">CONTENT</a>
            <a href="<?php echo esc_attr(rtrim((string)BASE_PATH, '/') . '/admin/contact_messages.php'); ?>">MESSAGES</a>
        </div>
    <?php }); ?>

    <?php if ($headerMode === 'public'): ?>
    <!-- Contact (small-screen friendly) -->
    <div class="hidden" aria-hidden="true">
        <?php $telHref = 'tel:' . preg_replace('/\s+/', '', (string)PHONE_NUMBER); ?>
        <a href="<?php echo esc_attr($telHref); ?>" class="inline-flex items-center px-2 py-1 border border-white/30 text-white rounded ml-2 text-sm no-underline">
            <i class="fa-solid fa-phone" aria-hidden="true"></i>&nbsp;Call
        </a>
        <a href="<?php echo esc_attr($whatsAppHref); ?>" class="inline-flex items-center px-2 py-1 bg-approval-green text-white rounded ml-1 no-underline" target="_blank" rel="noopener noreferrer">
            <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>&nbsp;WhatsApp
        </a>
    </div>
    <?php endif; ?>
    
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
        <?php if (function_exists('is_logged_in') && is_logged_in()): ?>
            <?php include __DIR__ . '/notifications.php'; ?>
        <?php endif; ?>

        <nav>
            <?php if ($headerMode === 'dashboard'): ?>
                <?php
                    $navRole = 'dashboard';
                    if (function_exists('auth_resolve_navigation_role') && function_exists('current_user')) {
                        $navRole = auth_resolve_navigation_role(current_user());
                    } elseif (function_exists('current_user')) {
                        $fallbackUser = current_user();
                        $fallbackRole = is_array($fallbackUser) ? strtolower((string)($fallbackUser['role'] ?? '')) : '';
                        render_if('dashboard', 'nav.resolve_role', static function () use (&$navRole, $fallbackRole): void {
                            if ($fallbackRole === 'admin' || $fallbackRole === 'worker') {
                                $navRole = $fallbackRole;
                            }
                        });
                    }


                    $sessionRole = '';
                    if (function_exists('current_user')) {
                        $sessionUser = current_user();
                        $sessionRole = is_array($sessionUser) ? strtolower((string)($sessionUser['role'] ?? '')) : '';
                    }

                    // Role-aware dashboard link: clients should land on client dashboard
                    render_if('dashboard', 'nav.client.dashboard_link', static function () use (&$roleDashboardLink, $sessionRole): void {
                        if ($sessionRole === 'client') {
                            $roleDashboardLink = rtrim((string)BASE_PATH, '/') . '/client/dashboard.php';
                        }
                    });

                    $activeSection = $navRole;
                ?>
                <?php if ($activeSection === 'dashboard'): ?>
                    <strong class="text-white/40 text-[10px] uppercase tracking-[0.2em] mb-2 px-4"><?php echo htmlspecialchars((string)$headerText('dashboard_section_title', 'Dashboard')); ?></strong>
                    <?php render_if('dashboard', 'nav.dashboard.home', static function () use ($roleDashboardLink, $headerText): void { ?>
                        <a href="<?php echo htmlspecialchars((string)$roleDashboardLink, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string)$headerText('dashboard_link_home', 'Dashboard Home')); ?></a>
                    <?php }); ?>
                    <?php render_if('dashboard', 'nav.dashboard.project_details', static function () use ($headerText): void { ?>
                        <a href="<?php echo htmlspecialchars(rtrim((string)BASE_PATH, '/') . '/worker/project_details.php'); ?>"><?php echo htmlspecialchars((string)$headerText('dashboard_link_project_details', 'Project Details')); ?></a>
                    <?php }); ?>
                    <?php render_if('dashboard', 'nav.dashboard.profile', static function () use ($dashboardProfileUrl, $headerText): void { ?>
                        <a href="<?php echo htmlspecialchars($dashboardProfileUrl); ?>"><?php echo htmlspecialchars((string)$headerText('dashboard_link_profile', 'Profile Settings')); ?></a>
                    <?php }); ?>
                    <?php render_if('dashboard', 'nav.dashboard.reviews', static function () use ($headerText): void { ?>
                        <a href="<?php echo htmlspecialchars(rtrim((string)BASE_PATH, '/') . '/dashboard/review_requests.php'); ?>"><?php echo htmlspecialchars((string)$headerText('dashboard_link_reviews', 'Review Requests')); ?></a>
                    <?php }); ?>
                <?php elseif ($activeSection === 'worker'): ?>
                    <strong class="text-white/40 text-[10px] uppercase tracking-[0.2em] mb-2 px-4"><?php echo htmlspecialchars((string)$headerText('worker_section_title', 'Worker Portal')); ?></strong>
                    <?php render_if('dashboard', 'nav.worker.dashboard', static function () use ($headerText): void { ?>
                        <a href="<?php echo htmlspecialchars(rtrim((string)BASE_PATH, '/') . '/dashboard/dashboard.php'); ?>"><?php echo htmlspecialchars((string)$headerText('worker_link_dashboard', 'Worker Dashboard')); ?></a>
                    <?php }); ?>
                    <?php render_if('dashboard', 'nav.worker.assigned_projects', static function () use ($headerText): void { ?>
                        <a href="<?php echo htmlspecialchars(rtrim((string)BASE_PATH, '/') . '/worker/assigned_projects.php'); ?>"><?php echo htmlspecialchars((string)$headerText('worker_link_assigned_projects', 'Assigned Projects')); ?></a>
                    <?php }); ?>
                    <?php render_if('dashboard', 'nav.worker.project_details', static function () use ($headerText): void { ?>
                        <a href="<?php echo htmlspecialchars(rtrim((string)BASE_PATH, '/') . '/dashboard/project_details.php'); ?>"><?php echo htmlspecialchars((string)$headerText('worker_link_project_details', 'Project Details')); ?></a>
                    <?php }); ?>
                    <?php render_if('dashboard', 'nav.worker.ratings', static function () use ($headerText): void { ?>
                        <a href="<?php echo htmlspecialchars(rtrim((string)BASE_PATH, '/') . '/worker/worker_rating.php'); ?>"><?php echo htmlspecialchars((string)$headerText('worker_link_ratings', 'My Ratings')); ?></a>
                    <?php }); ?>
                <?php elseif ($activeSection === 'admin'): ?>
                    <strong class="text-white/40 text-[10px] uppercase tracking-[0.2em] mb-2 px-4"><?php echo htmlspecialchars((string)$headerText('admin_section_title', 'Administration')); ?></strong>
                    <?php render_if('dashboard', 'nav.admin.dashboard', static function () use ($headerText): void { ?>
                        <a href="<?php echo htmlspecialchars(rtrim((string)BASE_PATH, '/') . '/dashboard/dashboard.php'); ?>"><?php echo htmlspecialchars((string)$headerText('admin_link_dashboard', 'Admin Dashboard')); ?></a>
                    <?php }); ?>
                    <?php render_if('dashboard', 'nav.admin.projects', static function () use ($headerText): void { ?>
                        <a href="<?php echo htmlspecialchars(rtrim((string)BASE_PATH, '/') . '/admin/project_management.php'); ?>"><?php echo htmlspecialchars((string)$headerText('admin_link_project_portfolio', 'Project Portfolio')); ?></a>
                    <?php }); ?>
                    <?php render_if('dashboard', 'nav.users.list', static function () use ($headerText): void { ?>
                        <a href="<?php echo htmlspecialchars(rtrim((string)BASE_PATH, '/') . '/admin/user_management.php'); ?>"><?php echo htmlspecialchars((string)$headerText('admin_link_user_controls', 'User Controls')); ?></a>
                    <?php }); ?>
                    <?php render_if('dashboard', 'nav.settings', static function () use ($headerText): void { ?>
                        <a href="<?php echo htmlspecialchars(rtrim((string)BASE_PATH, '/') . '/pages/settings/permissions.php'); ?>"><?php echo htmlspecialchars((string)$headerText('admin_link_settings', 'Settings')); ?></a>
                    <?php }); ?>
                    <?php render_if('dashboard', 'nav.admin.leave', static function () use ($headerText): void { ?>
                        <a href="<?php echo htmlspecialchars(rtrim((string)BASE_PATH, '/') . '/admin/leave_management.php'); ?>"><?php echo htmlspecialchars((string)$headerText('admin_link_leave_manager', 'Leave Manager')); ?></a>
                    <?php }); ?>
                    <?php render_if('dashboard', 'nav.admin.billing', static function () use ($headerText): void { ?>
                        <a href="<?php echo htmlspecialchars(rtrim((string)BASE_PATH, '/') . '/admin/payment_gateway.php'); ?>"><?php echo htmlspecialchars((string)$headerText('admin_link_financial_gateway', 'Financial Gateway')); ?></a>
                    <?php }); ?>
                    <?php render_if('dashboard', 'nav.admin.content', static function () use ($headerText): void { ?>
                        <a href="<?php echo htmlspecialchars(rtrim((string)BASE_PATH, '/') . '/admin/content_management.php'); ?>"><?php echo htmlspecialchars((string)$headerText('admin_link_content_manager', 'Content Manager')); ?></a>
                    <?php }); ?>
                    <?php render_if('dashboard', 'nav.vendors', static function (): void { ?>
                        <a href="<?php echo htmlspecialchars(rtrim((string)BASE_PATH, '/') . '/admin/entities.php?tab=vendors'); ?>">Vendors</a>
                    <?php }); ?>
                    <?php render_if('dashboard', 'nav.admin.workers', static function () use ($headerText): void { ?>
                        <a href="<?php echo htmlspecialchars(rtrim((string)BASE_PATH, '/') . '/admin/entities.php?tab=workers'); ?>"><?php echo htmlspecialchars((string)$headerText('admin_link_workers', 'Workers')); ?></a>
                    <?php }); ?>
                    <?php render_if('dashboard', 'nav.admin.contacts', static function () use ($headerText): void { ?>
                        <a href="<?php echo htmlspecialchars(rtrim((string)BASE_PATH, '/') . '/admin/contact_messages.php'); ?>"><?php echo htmlspecialchars((string)$headerText('admin_link_contact_messages', 'Contact Messages')); ?></a>
                    <?php }); ?>
                <?php endif; ?>
            <?php else: ?>
                <a href="<?php echo htmlspecialchars($headerPublicUrl('index.php')); ?>" class="nav-link<?php echo $isActiveNav('index.php') ? ' nav-link-active' : ''; ?>"><?php echo htmlspecialchars($headerText('menu_home', 'Home')); ?></a>
                <a href="<?php echo htmlspecialchars($headerPublicUrl('services.php')); ?>" class="nav-link<?php echo $isActiveNav('services.php') ? ' nav-link-active' : ''; ?>"><?php echo htmlspecialchars($headerText('menu_services', 'Services')); ?></a>
                <a href="<?php echo htmlspecialchars($headerPublicUrl('project_view.php')); ?>" class="nav-link<?php echo $isActiveNav('project_view.php') ? ' nav-link-active' : ''; ?>"><?php echo htmlspecialchars($headerText('menu_projects', 'Projects')); ?></a>
                <a href="<?php echo htmlspecialchars($headerPublicUrl('about_us.php')); ?>" class="nav-link<?php echo $isActiveNav('about_us.php') ? ' nav-link-active' : ''; ?>"><?php echo htmlspecialchars($headerText('menu_about', 'About')); ?></a>
                <a href="<?php echo htmlspecialchars($headerPublicUrl('credits.php')); ?>" class="nav-link<?php echo $isActiveNav('credits.php') ? ' nav-link-active' : ''; ?>"><?php echo htmlspecialchars($headerText('menu_credits', 'Credits')); ?></a>
                <a href="<?php echo htmlspecialchars($headerPublicUrl('contact_us.php')); ?>" class="nav-link<?php echo $isActiveNav('contact_us.php') ? ' nav-link-active' : ''; ?>"><?php echo htmlspecialchars($headerText('menu_contact', 'Contact')); ?></a>
            <?php endif; ?>
        </nav>
  

        
        <div class="panel-footer">
            <?php if (function_exists('is_logged_in') && is_logged_in()): ?>
                <?php if ($headerMode !== 'dashboard'): ?>
                    <a href="<?php echo htmlspecialchars($roleDashboardLink); ?>" class="btn-alt btn-login"><?php echo htmlspecialchars($headerText('btn_dashboard', 'Dashboard')); ?></a>
                <?php endif; ?>
                <style>
                    /* Dashboard-specific: use Cormorant Garamond (bold) for project title/location and stat numbers */
                    [data-stats-group] [data-countup], .stat-number {
                        font-family: 'Cormorant Garamond', 'Playfair Display', serif !important;
                        font-weight: 700 !important;
                        letter-spacing: -0.01em;
                    }

                    .project-title {
                        font-family: 'Cormorant Garamond', 'Playfair Display', serif !important;
                        font-weight: 700 !important;
                        line-height: 1.05 !important;
                    }

                    .project-location {
                        font-family: 'Cormorant Garamond', 'Playfair Display', serif !important;
                        font-weight: 600 !important;
                        color: #374151 !important;
                    }
                </style>
                    <a href="<?php echo htmlspecialchars($headerPublicUrl('logout.php')); ?>" class="btn-alt <?php echo $headerMode === 'dashboard' ? 'btn-login w-full text-center' : 'btn-signup'; ?>"><?php echo htmlspecialchars($headerText('btn_logout', 'Logout')); ?></a>
            <?php else: ?>
                <?php if ($isPublicHeader): ?>
                    <a href="<?php echo htmlspecialchars($headerPublicUrl('contact_us.php')); ?>" class="btn-alt btn-signup"><?php echo htmlspecialchars($headerText('btn_contact', 'Start Your Project')); ?></a>
                <?php else: ?>
                    <a href="<?php echo htmlspecialchars($headerPublicUrl('login.php')); ?>" class="btn-alt btn-login"><?php echo htmlspecialchars($headerText('btn_login', 'Login')); ?></a>
                    <a href="<?php echo htmlspecialchars($headerPublicUrl('signup.php')); ?>" class="btn-alt btn-signup"><?php echo htmlspecialchars($headerText('btn_signup', 'Sign Up')); ?></a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
    // Include the centralized page-aware toolbar here so it renders inside the BODY
    // and is visible to users (moved from earlier in the head section).
    require_once __DIR__ . '/toolbar.php';
?>
<script nonce="<?php echo htmlspecialchars($_REQUEST['csp_nonce']); ?>">
    // Global image error handler: replace broken images with local placeholder (same-origin)
    (function(){
        var fallback = <?php echo esc_js(rtrim((string)BASE_PATH, '/') . "/assets/images/rd-placeholder.svg"); ?>;
        window.addEventListener('error', function(e){
            var t = e.target || e.srcElement;
            if (!t) return;
            if (t.tagName && t.tagName.toLowerCase() === 'img') {
                // Avoid infinite loop
                if (t.dataset.rdFallbackApplied) return;
                t.dataset.rdFallbackApplied = '1';
                try { t.src = fallback; } catch(_){}
            }
        }, true);
    })();
</script>

<!-- Header Navigation Script -->
    <!-- Phantom root: wraps main page content. Closed in Common/footer.php -->
    <?php if (!empty($isPublicHeader) || (empty($HEADER_MODE) || $HEADER_MODE === 'public')): ?>
        <phantom-ui loading id="phantom-ui-root">
    <?php else: ?>
        <!-- For admin/dashboard pages we don't use phantom-ui pre-hydration wrapper -->
        <div id="phantom-ui-root">
    <?php endif; ?>
        <!-- Lucide icons (used via data-lucide="icon-name"). Initialize after the library loads. -->
        <script nonce="<?php echo htmlspecialchars($_REQUEST['csp_nonce']); ?>">
            (function(){
                var lucideScript = document.createElement('script');
                // Prefer local copy to avoid CORB/CSP/CDN issues. Fallback to CDN if not present.
                var localPath = '<?php echo esc_attr(asset('assets/js/lucide.min.js')); ?>';
                // server-side: if local file exists, use it. Otherwise use CDN.
                var useLocal = false;
                try {
                    useLocal = <?php echo (file_exists(PROJECT_ROOT . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'lucide.min.js') ? 'true' : 'false'); ?>;
                } catch(e){ useLocal = false; }
                lucideScript.src = useLocal ? localPath : 'https://cdn.jsdelivr.net/npm/lucide@0.259.0/dist/lucide.min.js';
                lucideScript.defer = false;
                lucideScript.async = true;
                lucideScript.onload = function () {
                    try { if (window.lucide && typeof window.lucide.createIcons === 'function') window.lucide.createIcons(); } catch(e){}

                    // Mutation observer to initialize icons for dynamically added nodes
                    try {
                        function initLucide(root) {
                            try { if (window.lucide && typeof window.lucide.createIcons === 'function') window.lucide.createIcons({ parent: root || document }); } catch(e){}
                        }
                        var observer = new MutationObserver(function (mutations) {
                            for (var i = 0; i < mutations.length; i++) {
                                var m = mutations[i];
                                if (m.addedNodes && m.addedNodes.length) {
                                    for (var j = 0; j < m.addedNodes.length; j++) {
                                        var n = m.addedNodes[j];
                                        if (n.nodeType === 1 && (n.matches && n.matches('[data-lucide]') || (n.querySelector && n.querySelector('[data-lucide]')))) {
                                            initLucide(n);
                                            return;
                                        }
                                    }
                                }
                            }
                        });
                        observer.observe(document.documentElement || document.body, { childList: true, subtree: true });
                    } catch (e) {}
                };
                lucideScript.onerror = function(){ /* silent fallback */ };
                document.head.appendChild(lucideScript);
            })();
        </script>
        <script nonce="<?php echo htmlspecialchars($_REQUEST['csp_nonce']); ?>">
            // Replace <i data-lucide="name"> with Font Awesome equivalent when lucide isn't available
            (function(){
                var map = {
                    'eye':'fa-eye',
                    'user-plus':'fa-user-plus',
                    'settings-2':'fa-gear',
                    'search':'fa-search',
                    'filter':'fa-filter',
                    'loader-2':'fa-spinner',
                    'download':'fa-download',
                    'edit-3':'fa-pen-to-square',
                    'plus':'fa-plus',
                    'chevron-right':'fa-chevron-right',
                    'mail':'fa-envelope',
                    'lock':'fa-lock',
                    'shield-check':'fa-shield-halved',
                    'user':'fa-user',
                    'file-text':'fa-file-lines',
                    'download-cloud':'fa-cloud-arrow-down',
                    'image-plus':'fa-image',
                    'trash-2':'fa-trash',
                    'eye-off':'fa-eye-slash',
                    'x':'fa-xmark',
                    'check':'fa-check',
                    'alert-circle':'fa-circle-exclamation',
                    'info':'fa-circle-info',
                    'external-link':'fa-arrow-up-right-from-square',
                    'calendar':'fa-calendar',
                    'map-pin':'fa-map-pin',
                    'phone':'fa-phone',
                    'download-cloud':'fa-cloud-arrow-down'
                };

                function replaceIcons(root){
                    root = root || document;
                    var nodes = root.querySelectorAll('i[data-lucide]');
                    nodes.forEach(function(n){
                        try {
                            var name = (n.getAttribute('data-lucide')||'').trim();
                            if(!name) return;
                            var fa = map[name] || ('fa-' + name.replace(/_/g,'-'));
                            // keep size classes from original element
                            var classes = Array.from(n.classList).filter(Boolean).join(' ');
                            var span = document.createElement('i');
                            span.className = 'fa-solid ' + fa + (classes ? ' ' + classes : '');
                            // transfer title/aria-hidden
                            if(n.getAttribute('title')) span.setAttribute('title', n.getAttribute('title'));
                            if(n.getAttribute('aria-hidden')) span.setAttribute('aria-hidden', n.getAttribute('aria-hidden'));
                            n.parentNode.replaceChild(span, n);
                        } catch(e){}
                    });
                }

                document.addEventListener('DOMContentLoaded', function(){
                    // If lucide loaded successfully, prefer it; otherwise replace with FA
                    setTimeout(function(){
                        if(!(window.lucide && typeof window.lucide.createIcons==='function')){
                            replaceIcons(document);
                        }
                    }, 50);
                });

                // Watch for dynamic content
                try{
                    var obs = new MutationObserver(function(mutations){
                        mutations.forEach(function(m){
                            if(m.addedNodes && m.addedNodes.length){
                                for(var i=0;i<m.addedNodes.length;i++){
                                    var n = m.addedNodes[i];
                                    if(n.nodeType===1){
                                        if(n.matches && n.matches('i[data-lucide]') || (n.querySelector && n.querySelector('i[data-lucide]'))){
                                            if(!(window.lucide && typeof window.lucide.createIcons==='function')){
                                                replaceIcons(n);
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    });
                    obs.observe(document.documentElement || document.body, { childList:true, subtree:true });
                }catch(e){}
            })();
        </script>
    <!-- TODO: Replace placeholder SRI hashes with real ones from srihash.org -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js" crossorigin="anonymous" defer nonce="<?php echo htmlspecialchars($_REQUEST['csp_nonce']); ?>"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js" crossorigin="anonymous" defer nonce="<?php echo htmlspecialchars($_REQUEST['csp_nonce']); ?>"></script>
    <script src="<?php echo esc_attr(asset('assets/js/gsap-core-init.js')); ?>" defer nonce="<?php echo htmlspecialchars($_REQUEST['csp_nonce']); ?>"></script>
    <script src="<?php echo esc_attr(asset('assets/js/gsap-motion-presets.js')); ?>" defer nonce="<?php echo htmlspecialchars($_REQUEST['csp_nonce']); ?>"></script>
    <script src="<?php echo esc_attr(asset('assets/js/header-nav.js')); ?>" defer nonce="<?php echo htmlspecialchars($_REQUEST['csp_nonce']); ?>"></script>
    <script src="<?php echo esc_attr(asset('assets/js/auto-hide-alerts.js')); ?>" defer nonce="<?php echo htmlspecialchars($_REQUEST['csp_nonce']); ?>"></script>
    <script src="<?php echo esc_attr(asset('assets/js/ajax-forms.js')); ?>" defer nonce="<?php echo htmlspecialchars($_REQUEST['csp_nonce']); ?>"></script>
<script nonce="<?php echo htmlspecialchars($_REQUEST['csp_nonce']); ?>">
document.addEventListener('DOMContentLoaded', function(){
  // Fallback: if Phantom-UI doesn't initialize (CDN blocked), ensure content is visible
  setTimeout(function(){
    try {
      var root = document.getElementById('phantom-ui-root') || document.querySelector('phantom-ui[loading]');
      if (root && root.hasAttribute && root.hasAttribute('loading')) {
        root.removeAttribute('loading');
      }
    } catch(e){}
  }, 600);
});
// Filter panel toggle wiring
document.addEventListener('DOMContentLoaded', function(){
        try {
                var filterBtn = document.getElementById('filterSortBtn');
                var panel = document.getElementById('filterSortPanel');
                if (filterBtn && panel) {
                        filterBtn.addEventListener('click', function(){
                                var open = panel.style.display === 'block';
                                panel.style.display = open ? 'none' : 'block';
                                filterBtn.setAttribute('aria-expanded', (!open).toString());
                                panel.setAttribute('aria-hidden', open ? 'true' : 'false');
                        });
                        document.addEventListener('click', function(e){
                                if (!panel.contains(e.target) && e.target !== filterBtn) {
                                        panel.style.display = 'none';
                                        filterBtn.setAttribute('aria-expanded', 'false');
                                        panel.setAttribute('aria-hidden', 'true');
                                }
                        });
                }
        } catch(e){}
});
// Proxy header toolbar buttons to existing project page handlers if present
// toolbar uses canonical IDs now; no proxy required
// Restore display_errors setting to previous environment if possible
try { if (typeof window === 'undefined') {} } catch(e) {}
</script>