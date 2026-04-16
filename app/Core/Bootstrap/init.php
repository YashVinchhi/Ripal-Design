<?php
/**
 * Application Bootstrap/Initialization
 * 
 * Central bootstrapping file that loads all core dependencies.
 * Include this file at the top of every page to ensure the application
 * is properly initialized.
 * 
 * Loads in order:
 * 1. Configuration (paths, URLs, environment)
 * 2. Database connection
 * 3. Authentication helpers
 * 4. Utility functions
 * 
 * Also initializes session management.
 * 
 * Usage:
 * require_once __DIR__ . '/../app/Core/Bootstrap/init.php';
 * 
 * @package RipalDesign
 * @subpackage Core
 */

// Prevent direct access
if (!defined('PROJECT_ROOT')) {
    // Load configuration first (defines PROJECT_ROOT)
    require_once __DIR__ . '/../Config/config.php';
}

if (file_exists(__DIR__ . '/../Support/logger.php')) {
    require_once __DIR__ . '/../Support/logger.php';
}

if (file_exists(__DIR__ . '/../Http/routes.php')) {
    require_once __DIR__ . '/../Http/routes.php';
    if (function_exists('app_routes_bootstrap')) {
        app_routes_bootstrap();
    }
}

if (!function_exists('apply_security_headers')) {
    /**
     * Apply low-risk HTTP security headers globally.
     */
    function apply_security_headers(): void {
        if (headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

        if (defined('SECURITY_ENABLE_CSP') && SECURITY_ENABLE_CSP && defined('SECURITY_CSP_POLICY') && SECURITY_CSP_POLICY !== '') {
            header('Content-Security-Policy: ' . SECURITY_CSP_POLICY);
        }

        if (function_exists('app_is_https') && app_is_https() && defined('SECURITY_ENABLE_HSTS') && SECURITY_ENABLE_HSTS) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}

// Load database connection
require_once __DIR__ . '/../Database/db.php';

// Load authentication helpers
require_once __DIR__ . '/../Security/auth.php';

// Load utility functions (depends on config and db)
if (file_exists(__DIR__ . '/../Support/util.php')) {
    require_once __DIR__ . '/../Support/util.php';
}

$legacyIncludesDir = rtrim((string)(defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__, 3)), '/\\') . DIRECTORY_SEPARATOR . 'includes';

$notificationServicePath = rtrim((string)PROJECT_ROOT, '/\\') . '/app/Domains/Notifications/Services/notification_service.php';
$paypalServicePath = rtrim((string)PROJECT_ROOT, '/\\') . '/app/Domains/Billing/Services/paypal_service.php';
$billingServicePath = rtrim((string)PROJECT_ROOT, '/\\') . '/app/Domains/Billing/Services/billing_service.php';
$publicContentPath = rtrim((string)PROJECT_ROOT, '/\\') . '/app/Domains/Content/Services/public_content.php';

// Load notification helpers (depends on db/auth/util)
if (file_exists($notificationServicePath)) {
    require_once $notificationServicePath;
} elseif (file_exists($legacyIncludesDir . '/notification_service.php')) {
    require_once $legacyIncludesDir . '/notification_service.php';
}

// Load PayPal service helpers (depends on config/util/db)
if (file_exists($paypalServicePath)) {
    require_once $paypalServicePath;
} elseif (file_exists($legacyIncludesDir . '/paypal_service.php')) {
    require_once $legacyIncludesDir . '/paypal_service.php';
}

// Load billing service helpers (depends on db/util/auth)
if (file_exists($billingServicePath)) {
    require_once $billingServicePath;
} elseif (file_exists($legacyIncludesDir . '/billing_service.php')) {
    require_once $legacyIncludesDir . '/billing_service.php';
}

// Load public content helpers (depends on util/db/auth)
if (file_exists($publicContentPath)) {
    require_once $publicContentPath;
} elseif (file_exists($legacyIncludesDir . '/public_content.php')) {
    require_once $legacyIncludesDir . '/public_content.php';
}

// Start session if not already started
// Use @ to suppress warnings if session already started
if (session_status() === PHP_SESSION_NONE) {
    @ini_set('session.use_strict_mode', '1');
    @ini_set('session.cookie_httponly', '1');

    $secureCookie = function_exists('app_is_https') ? app_is_https() : (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    // Ensure cookie is available across the site root so pages in different
    // folders share the same session (helps when app is served from a subpath).
    // Keep this minimal to avoid interfering with user's environment.
    @session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secureCookie,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    @session_start();
}

apply_security_headers();

// Attempt auto-login from remember-me cookie if session empty
if (function_exists('auth_try_auto_login')) {
    auth_try_auto_login();
}

// Global login guard for protected routes.
if (function_exists('enforce_protected_route_login')) {
    enforce_protected_route_login();
}

// Global write guard: enforces DB-backed role permissions for mutating requests.
if (function_exists('enforce_request_write_permission')) {
    enforce_request_write_permission();
}

// Make $pdo available as a global for legacy code that expects it
global $pdo;

// Set timezone (adjust as needed for your location)
if (!ini_get('date.timezone')) {
    date_default_timezone_set('UTC');
}
?>
