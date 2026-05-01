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
    require_once __DIR__ . '/../app/Core/Config/config.php';
}

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

if (function_exists('csrf_token')) {
    csrf_token();
}

// Determine header mode (can be set by including page)
$headerMode = $HEADER_MODE ?? 'public';
$isPublicHeader = ($headerMode === 'public');

$headerContent = function_exists('public_content_page_values') ? public_content_page_values('common_header') : [];
$headerText = static function ($key, $default = '') use ($headerContent) {
    return (string)($headerContent[$key] ?? $default);
};
$headerImage = static function ($key, $default = '') use ($headerContent) {
    $value = (string)($headerContent[$key] ?? $default);
    if (function_exists('public_content_image_url')) {
        return (string)public_content_image_url($value, $default);
    }
    if (function_exists('base_path')) {
        return (string)base_path(ltrim((string)$value, '/'));
    }
    return (string)$value;
};
$brandLogoImage = $headerImage('brand_logo_image', '/assets/Content/Logo.png');
$faviconImage = $headerImage('favicon_image', '/favicon.ico');
$headerPublicUrl = static function ($path) {
    $path = ltrim((string)$path, '/');
    return rtrim((string)BASE_PATH, '/') . PUBLIC_PATH_PREFIX . '/' . $path;
};
$dashboardProfileUrl = function_exists('base_path')
    ? base_path('dashboard/profile.php')
    : rtrim((string)BASE_PATH, '/') . '/dashboard/profile.php';
$whatsAppHref = 'https://wa.me/' . preg_replace('/\D+/', '', (string)WHATSAPP_NUMBER);
$radiusMode = strtolower((string)(getenv('UI_RADIUS') ?: 'sharp'));
$radiusMode = in_array($radiusMode, ['rounded', 'sharp'], true) ? $radiusMode : 'sharp';

// Compute logo target: public pages always link to homepage.
$logoHref = $headerPublicUrl('index.php');
if (!$isPublicHeader && function_exists('current_user')) {
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

    <!-- Analytics include (GA4 + Clarity placeholders) - disabled in development -->
    <?php if (!defined('APP_ENV') || APP_ENV !== 'development') {
        if (file_exists(__DIR__ . '/analytics.php')) { include __DIR__ . '/analytics.php'; }
    } ?>

<!-- Common Stylesheets and Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
<script defer src="https://cdn.jsdelivr.net/npm/@aejkatappaja/phantom-ui/dist/phantom-ui.cdn.js"></script>

<!-- Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<?php if ((empty($HEADER_MODE) || $HEADER_MODE !== 'public') && (!isset($DISABLE_EXTERNAL_CSS) || !$DISABLE_EXTERNAL_CSS)): ?>
<?php
$tailwindBuiltPath = PROJECT_ROOT . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . 'tailwind.css';
if (file_exists($tailwindBuiltPath)) {
    $tailwindHref = rtrim((string)BASE_PATH, '/') . '/assets/css/tailwind.css';
    echo '<link rel="stylesheet" href="' . esc_attr($tailwindHref) . '">' . "\n";
}

$variablesCss = rtrim((string)BASE_PATH, '/') . PUBLIC_PATH_PREFIX . '/css/variables.css';
$mainCss = rtrim((string)BASE_PATH, '/') . PUBLIC_PATH_PREFIX . '/css/main.css';
echo '<link rel="stylesheet" href="' . esc_attr($variablesCss) . '">' . "\n";
echo '<link rel="stylesheet" href="' . esc_attr(rtrim((string)BASE_PATH, '/') . '/assets/css/ui-radius.css') . '">' . "\n";
echo '<link rel="stylesheet" href="' . esc_attr($mainCss) . '">' . "\n";
?>
<?php endif; ?>

<script>
    document.documentElement.setAttribute('data-ui-radius', <?php echo json_encode($radiusMode); ?>);
</script>

<!-- Favicons -->
<link rel="icon" href="<?php echo esc_attr($faviconImage); ?>" type="image/x-icon">
<link rel="shortcut icon" href="<?php echo esc_attr($faviconImage); ?>" type="image/x-icon">
<link rel="apple-touch-icon" href="<?php echo esc_attr($faviconImage); ?>">

<!-- Header Navigation (Always loaded) -->
<!-- Layout tokens for legacy CSS (variables: spacing, header height, container) -->
<?php if (empty($HEADER_MODE) || $HEADER_MODE !== 'public'): ?>
    <link rel="stylesheet" href="<?php echo esc_attr(rtrim((string) BASE_PATH, '/') . '/assets/css/_layout.css'); ?>">
    <link rel="stylesheet" href="<?php echo esc_attr(rtrim((string) BASE_PATH, '/') . PUBLIC_PATH_PREFIX . '/css/header.css'); ?>">
<?php endif; ?>
<?php if (empty($HEADER_MODE) || $HEADER_MODE !== 'public') { if (function_exists('webmcp_render_bootstrap_once')) { webmcp_render_bootstrap_once(); } } ?>
<?php if ($headerMode === 'dashboard'): ?>
    <link rel="stylesheet" href="<?php echo esc_attr(rtrim((string) BASE_PATH, '/') . PUBLIC_PATH_PREFIX . '/css/admin-responsive.css'); ?>">
<?php endif; ?>
<nav class="alt-header">
    <div class="alt-logo">
        <a href="<?php echo esc_attr($logoHref); ?>" class="flex items-center gap-3 no-underline">
            <img src="<?php echo esc_attr($brandLogoImage); ?>" alt="Ripal Design Logo" class="h-10" onerror="this.onerror=null;this.src='https://placehold.co/160x60/b91c1c/ffffff?text=RD'">
            <span class="text-white font-serif font-bold text-xl tracking-tight"><?php echo htmlspecialchars($headerText('brand_name', 'Ripal Design')); ?></span>
        </a>
    </div>

    <?php if ($headerMode === 'public'): ?>
    <!-- Contact (small-screen friendly) -->
    <div class="hidden" aria-hidden="true">
        <a href="#" data-rd-phone="<?php echo esc_attr(base64_encode('tel:' . preg_replace('/\s+/', '', (string)PHONE_NUMBER))); ?>" data-rd-phone-label="<?php echo esc_attr(base64_encode('Call')); ?>" class="inline-flex items-center px-2 py-1 border border-white/30 text-white rounded ml-2 text-sm no-underline">
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
                        if ($fallbackRole === 'admin' || $fallbackRole === 'worker') {
                            $navRole = $fallbackRole;
                        }
                    }


                    $sessionRole = '';
                    if (function_exists('current_user')) {
                        $sessionUser = current_user();
                        $sessionRole = is_array($sessionUser) ? strtolower((string)($sessionUser['role'] ?? '')) : '';
                    }

                    // Role-aware dashboard link: clients should land on client dashboard
                    $roleDashboardLink = rtrim((string)BASE_PATH, '/') . '/dashboard/dashboard.php';
                    if ($sessionRole === 'client') {
                        $roleDashboardLink = rtrim((string)BASE_PATH, '/') . '/client/dashboard.php';
                    }

                    $menuSections = [
                        'dashboard' => [
                            'title' => $headerText('dashboard_section_title', 'Dashboard'),
                            'links' => [
                                ['href' => $roleDashboardLink, 'label' => $headerText('dashboard_link_home', 'Dashboard Home')],
                                ['href' => BASE_PATH . '/worker/project_details.php', 'label' => $headerText('dashboard_link_project_details', 'Project Details')],
                                ['href' => $dashboardProfileUrl, 'label' => $headerText('dashboard_link_profile', 'Profile Settings')],
                                ['href' => BASE_PATH . '/dashboard/review_requests.php', 'label' => $headerText('dashboard_link_reviews', 'Review Requests')],
                            ],
                        ],
                        'worker' => [
                            'title' => $headerText('worker_section_title', 'Worker Portal'),
                            'links' => [
                                ['href' => BASE_PATH . '/dashboard/dashboard.php', 'label' => $headerText('worker_link_dashboard', 'Worker Dashboard')],
                                ...($sessionRole === 'client' ? [] : [
                                    ['href' => BASE_PATH . '/worker/assigned_projects.php', 'label' => $headerText('worker_link_assigned_projects', 'Assigned Projects')],
                                ]),
                                ['href' => BASE_PATH . '/dashboard/project_details.php', 'label' => $headerText('worker_link_project_details', 'Project Details')],
                                ['href' => BASE_PATH . '/worker/worker_rating.php', 'label' => $headerText('worker_link_ratings', 'My Ratings')],
                            ],
                        ],
                        'admin' => [
                            'title' => $headerText('admin_section_title', 'Administration'),
                            'links' => [
                                ['href' => BASE_PATH . '/dashboard/dashboard.php', 'label' => $headerText('admin_link_dashboard', 'Admin Dashboard')],
                                ['href' => BASE_PATH . '/admin/project_management.php', 'label' => $headerText('admin_link_project_portfolio', 'Project Portfolio')],
                                ['href' => BASE_PATH . '/admin/user_management.php', 'label' => $headerText('admin_link_user_controls', 'User Controls')],
                                ['href' => BASE_PATH . '/admin/leave_management.php', 'label' => $headerText('admin_link_leave_manager', 'Leave Manager')],
                                ['href' => BASE_PATH . '/admin/payment_gateway.php', 'label' => $headerText('admin_link_financial_gateway', 'Financial Gateway')],
                                ['href' => BASE_PATH . '/admin/content_management.php', 'label' => $headerText('admin_link_content_manager', 'Content Manager')],
                                ['href' => BASE_PATH . '/admin/entities.php?tab=vendors', 'label' => 'Vendors'],
                                ['href' => BASE_PATH . '/admin/entities.php?tab=workers', 'label' => 'Workers'],
                                ['href' => BASE_PATH . '/admin/contact_messages.php', 'label' => $headerText('admin_link_contact_messages', 'Contact Messages')],
                            ],
                        ],
                    ];

                    $activeSection = $menuSections[$navRole] ?? $menuSections['dashboard'];
                ?>
                <?php if (!empty($activeSection['title'])): ?>
                    <strong class="text-white/40 text-[10px] uppercase tracking-[0.2em] mb-2 px-4"><?php echo htmlspecialchars((string)$activeSection['title']); ?></strong>
                <?php endif; ?>
                <?php foreach (($activeSection['links'] ?? []) as $link): ?>
                    <a href="<?php echo htmlspecialchars((string)($link['href'] ?? '')); ?>"><?php echo htmlspecialchars((string)($link['label'] ?? '')); ?></a>
                <?php endforeach; ?>
            <?php else: ?>
                <a href="<?php echo htmlspecialchars($headerPublicUrl('index.php')); ?>" class="nav-link<?php echo $isActiveNav('index.php') ? ' nav-link-active' : ''; ?>"><?php echo htmlspecialchars($headerText('menu_home', 'Home')); ?></a>
                <a href="<?php echo htmlspecialchars($headerPublicUrl('services.php')); ?>" class="nav-link<?php echo $isActiveNav('services.php') ? ' nav-link-active' : ''; ?>"><?php echo htmlspecialchars($headerText('menu_services', 'Services')); ?></a>
                <a href="<?php echo htmlspecialchars($headerPublicUrl('project_view.php')); ?>" class="nav-link<?php echo $isActiveNav('project_view.php') ? ' nav-link-active' : ''; ?>"><?php echo htmlspecialchars($headerText('menu_projects', 'Projects')); ?></a>
                <a href="<?php echo htmlspecialchars($headerPublicUrl('about_us.php')); ?>" class="nav-link<?php echo $isActiveNav('about_us.php') ? ' nav-link-active' : ''; ?>"><?php echo htmlspecialchars($headerText('menu_about', 'About')); ?></a>
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

<!-- Header Navigation Script -->
    <!-- Phantom root: wraps main page content. Closed in Common/footer.php -->
    <phantom-ui loading id="phantom-ui-root">
        <!-- Lucide icons (used via data-lucide="icon-name"). Initialize after the library loads. -->
        <script>
            (function(){
                var lucideScript = document.createElement('script');
                // Prefer local copy to avoid CORB/CSP/CDN issues. Fallback to CDN if not present.
                var localPath = '<?php echo esc_attr(rtrim((string)BASE_PATH, "/") . "/assets/js/lucide.min.js"); ?>';
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
        <script>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js" defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js" defer></script>
<script src="<?php echo htmlspecialchars(BASE_PATH); ?>/assets/js/gsap-core-init.js" defer></script>
<script src="<?php echo htmlspecialchars(BASE_PATH); ?>/assets/js/gsap-motion-presets.js" defer></script>
<script src="<?php echo htmlspecialchars(BASE_PATH); ?>/assets/js/header-nav.js" defer></script>
<script src="<?php echo htmlspecialchars(BASE_PATH); ?>/assets/js/auto-hide-alerts.js" defer></script>
<script src="<?php echo htmlspecialchars(BASE_PATH); ?>/assets/js/ajax-forms.js" defer></script>
