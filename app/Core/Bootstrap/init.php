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

// Load .env into environment if present. Prefer vlucas/phpdotenv when available,
// otherwise fall back to a lightweight parser that sets getenv()/$_ENV/$_SERVER.
$autoload = rtrim((string)defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__, 3), '/\\') . '/vendor/autoload.php';
$envPath = rtrim((string)defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__, 3), '/\\') . '/.env';
if (file_exists($autoload)) {
    try {
        require_once $autoload;
        if (class_exists('\\Dotenv\\Dotenv')) {
            try {
                $dot = \Dotenv\Dotenv::createImmutable(rtrim((string)PROJECT_ROOT, '/\\'));
                $dot->safeLoad();
            } catch (Throwable $e) {
                // ignore dotenv failures and fall back to manual loader below
            }
        }
    } catch (Throwable $e) {
        // ignore autoload failures
    }
}

if (file_exists($envPath) && is_readable($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (is_array($lines)) {
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) continue;
            if (strpos($line, '=') === false) continue;
            list($name, $value) = explode('=', $line, 2);
            $name = trim((string)$name);
            $value = trim((string)$value);
            $value = trim($value, "\"'");
            if (getenv($name) === false) {
                putenv($name . '=' . $value);
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

if (file_exists(__DIR__ . '/../Support/logger.php')) {
    require_once __DIR__ . '/../Support/logger.php';
}

// Optionally load CSRF helpers for form protection
$incCsrf = rtrim((string)(defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__, 3)), '/\\') . '/includes/csrf.php';
if (file_exists($incCsrf)) {
    require_once $incCsrf;
}

// Optionally load upload helpers
$incUpload = rtrim((string)(defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__, 3)), '/\\') . '/includes/upload_secure.php';
if (file_exists($incUpload)) {
    require_once $incUpload;
}

if (file_exists(__DIR__ . '/../Http/routes.php')) {
    require_once __DIR__ . '/../Http/routes.php';
    if (function_exists('app_routes_bootstrap')) {
        app_routes_bootstrap();
    }
}

// Load centralized security headers if available, otherwise provide a safe fallback.
$incHeaders = rtrim((string)(defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__, 3)), '/\\') . '/includes/headers.php';
if (file_exists($incHeaders)) {
    require_once $incHeaders;
} else {
    if (!function_exists('apply_security_headers')) {
        function apply_security_headers(): void {
            if (headers_sent()) {
                return;
            }

            header('X-Frame-Options: DENY');
            header('X-Content-Type-Options: nosniff');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

            // Default CSP (allows self resources; permits inline styles for legacy layouts).
            $csp = "default-src 'self' https: data: blob: 'unsafe-inline'; script-src 'self' https: 'unsafe-inline' 'unsafe-eval'; style-src 'self' https: 'unsafe-inline'; img-src 'self' https: data: blob:; font-src 'self' https: data:; connect-src 'self' https: wss:; frame-src 'self' https:; media-src 'self' https: data: blob:; object-src 'none';";
            header('Content-Security-Policy: ' . $csp);

            if (function_exists('app_is_https') && app_is_https() && defined('SECURITY_ENABLE_HSTS') && SECURITY_ENABLE_HSTS) {
                header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
            }
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

$commonFunctionsPath = rtrim((string)PROJECT_ROOT, '/\\') . '/Common/functions.php';
if (file_exists($commonFunctionsPath)) {
    require_once $commonFunctionsPath;
}

$legacyIncludesDir = rtrim((string)(defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__, 3)), '/\\') . DIRECTORY_SEPARATOR . 'includes';

$notificationServicePath = rtrim((string)PROJECT_ROOT, '/\\') . '/app/Domains/Notifications/Services/notification_service.php';
$razorpayServicePath = rtrim((string)PROJECT_ROOT, '/\\') . '/app/Domains/Billing/Services/razorpay_service.php';
$billingServicePath = rtrim((string)PROJECT_ROOT, '/\\') . '/app/Domains/Billing/Services/billing_service.php';
$publicContentPath = rtrim((string)PROJECT_ROOT, '/\\') . '/app/Domains/Content/Services/public_content.php';

// Load notification helpers (depends on db/auth/util)
if (file_exists($notificationServicePath)) {
    require_once $notificationServicePath;
} elseif (file_exists($legacyIncludesDir . '/notification_service.php')) {
    require_once $legacyIncludesDir . '/notification_service.php';
}

// Load Razorpay service helpers (depends on config/util/db)
if (file_exists($razorpayServicePath)) {
    require_once $razorpayServicePath;
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
        'samesite' => 'Strict',
    ]);
    @session_start();
}

apply_security_headers();

// Polyfill for mbstring functions when the extension is not available.
if (!function_exists('mb_substr')) {
    function mb_substr(string $str, int $start, ?int $length = null, string $encoding = 'UTF-8')
    {
        if ($length === null) {
            return substr($str, $start);
        }
        return substr($str, $start, $length);
    }
}
if (!function_exists('mb_strlen')) {
    function mb_strlen(string $str, string $encoding = 'UTF-8')
    {
        return strlen($str);
    }
}

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
