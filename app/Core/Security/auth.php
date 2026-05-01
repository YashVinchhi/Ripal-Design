<?php
/**
 * Authentication Helper Functions
 * 
 * Provides secure authentication utilities for the application.
 * Ensures session is properly initialized before checking authentication.
 * 
 * @package RipalDesign
 * @subpackage Auth
 */

if (!function_exists('csrf_token')) {
    /**
     * Get or generate CSRF token for current session.
     *
     * @return string
     */
    function csrf_token() {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        if (empty($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('csrf_token_field')) {
    /**
     * Render hidden CSRF field for HTML forms.
     *
     * @return string
     */
    function csrf_token_field() {
        $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
}

if (!function_exists('csrf_validate')) {
    /**
     * Validate incoming CSRF token.
     *
     * @param string|null $token
     * @return bool
     */
    function csrf_validate($token) {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $stored = $_SESSION['_csrf_token'] ?? '';
        return is_string($token) && $token !== '' && is_string($stored) && $stored !== '' && hash_equals($stored, $token);
    }
}

if (!function_exists('require_csrf')) {
    /**
     * Enforce CSRF validation for mutating form requests.
     *
     * @return void
     */
    function require_csrf() {
        $token = $_POST['csrf_token'] ?? '';
        if (!csrf_validate($token)) {
            http_response_code(419);
            echo 'Invalid CSRF token.';
            exit;
        }
    }
}

if (!function_exists('auth_request_ip')) {
    /**
     * Resolve client IP with basic proxy header awareness.
     *
     * @return string
     */
    function auth_request_ip(): string {
        $forwarded = (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
        if ($forwarded !== '') {
            $parts = explode(',', $forwarded);
            $candidate = trim((string)($parts[0] ?? ''));
            if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_IP)) {
                return $candidate;
            }
        }

        $remote = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
        if ($remote !== '' && filter_var($remote, FILTER_VALIDATE_IP)) {
            return $remote;
        }

        return '0.0.0.0';
    }
}

if (!function_exists('auth_rate_limit_consume')) {
    /**
     * Resolve configured rate-limit backend.
     *
     * Supported values: redis, file
     *
     * @return string
     */
    function auth_rate_limit_backend(): string {
        $backend = strtolower(trim((string)(getenv('RATE_LIMIT_BACKEND') ?: 'file')));
        return in_array($backend, ['redis', 'file'], true) ? $backend : 'file';
    }

    /**
     * Get Redis client for distributed rate limiting.
     *
        * @return object|null
     */
    function auth_rate_limit_redis_client() {
        if (!class_exists('Redis')) {
            return null;
        }

        static $client = null;
        static $attempted = false;
        if ($attempted) {
            return $client;
        }
        $attempted = true;

        $host = (string)(getenv('REDIS_HOST') ?: '127.0.0.1');
        $port = (int)(getenv('REDIS_PORT') ?: 6379);
        $timeout = (float)(getenv('REDIS_TIMEOUT') ?: 1.5);
        $password = (string)(getenv('REDIS_PASSWORD') ?: '');
        $db = (int)(getenv('REDIS_DB') ?: 0);

        try {
            $redisClass = 'Redis';
            $redis = new $redisClass();
            $connected = @$redis->connect($host, $port, $timeout);
            if (!$connected) {
                return null;
            }
            if ($password !== '') {
                @ $redis->auth($password);
            }
            if ($db > 0) {
                @ $redis->select($db);
            }
            $client = $redis;
            return $client;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Consume an attempt using Redis-backed distributed buckets.
     *
     * @param string $bucket
     * @param int $maxAttempts
     * @param int $windowSeconds
     * @param int $blockSeconds
     * @return array{allowed:bool,retry_after:int,remaining:int}
     */
    function auth_rate_limit_consume_redis(string $bucket, int $maxAttempts, int $windowSeconds, int $blockSeconds = 0): array {
        $redis = auth_rate_limit_redis_client();
        if ($redis === null) {
            return ['allowed' => true, 'retry_after' => 0, 'remaining' => $maxAttempts];
        }

        $maxAttempts = max(1, $maxAttempts);
        $windowSeconds = max(1, $windowSeconds);
        $blockSeconds = max(0, $blockSeconds);

        $keyHash = hash('sha256', strtolower(trim($bucket)));
        $countKey = 'rate_limit:count:' . $keyHash;
        $blockKey = 'rate_limit:block:' . $keyHash;

        try {
            $blockedTtl = (int)$redis->ttl($blockKey);
            if ($blockedTtl > 0) {
                return ['allowed' => false, 'retry_after' => $blockedTtl, 'remaining' => 0];
            }

            $count = (int)$redis->incr($countKey);
            if ($count === 1) {
                @ $redis->expire($countKey, $windowSeconds);
            }

            if ($count > $maxAttempts) {
                $retry = $blockSeconds > 0 ? $blockSeconds : $windowSeconds;
                @ $redis->setex($blockKey, $retry, '1');
                return ['allowed' => false, 'retry_after' => $retry, 'remaining' => 0];
            }

            return ['allowed' => true, 'retry_after' => 0, 'remaining' => max(0, $maxAttempts - $count)];
        } catch (Throwable $e) {
            return ['allowed' => true, 'retry_after' => 0, 'remaining' => $maxAttempts];
        }
    }

    /**
     * Consume an attempt using local file-backed buckets.
     *
     * @param string $bucket
     * @param int $maxAttempts
     * @param int $windowSeconds
     * @param int $blockSeconds
     * @return array{allowed:bool,retry_after:int,remaining:int}
     */
    function auth_rate_limit_consume_file(string $bucket, int $maxAttempts, int $windowSeconds, int $blockSeconds = 0): array {
        $maxAttempts = max(1, $maxAttempts);
        $windowSeconds = max(1, $windowSeconds);
        $blockSeconds = max(0, $blockSeconds);
        $now = time();

        $root = defined('PROJECT_ROOT') ? rtrim((string)PROJECT_ROOT, '/\\') : rtrim((string)dirname(__DIR__), '/\\');
        $dir = $root . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'rate_limits';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $key = hash('sha256', strtolower(trim($bucket)));
        $file = $dir . DIRECTORY_SEPARATOR . $key . '.json';

        $handle = @fopen($file, 'c+');
        if ($handle === false) {
            return ['allowed' => true, 'retry_after' => 0, 'remaining' => $maxAttempts];
        }

        $result = ['allowed' => true, 'retry_after' => 0, 'remaining' => $maxAttempts];
        if (@flock($handle, LOCK_EX)) {
            $raw = stream_get_contents($handle);
            $state = is_string($raw) && $raw !== '' ? json_decode($raw, true) : [];
            if (!is_array($state)) {
                $state = [];
            }

            $attempts = [];
            foreach ((array)($state['attempts'] ?? []) as $ts) {
                $t = (int)$ts;
                if ($t > 0 && $t >= ($now - $windowSeconds)) {
                    $attempts[] = $t;
                }
            }

            $blockedUntil = (int)($state['blocked_until'] ?? 0);
            if ($blockedUntil > $now) {
                $result = ['allowed' => false, 'retry_after' => $blockedUntil - $now, 'remaining' => 0];
            } else {
                $blockedUntil = 0;
                if (count($attempts) >= $maxAttempts) {
                    $retry = $blockSeconds > 0 ? $blockSeconds : $windowSeconds;
                    $blockedUntil = $now + $retry;
                    $result = ['allowed' => false, 'retry_after' => $retry, 'remaining' => 0];
                } else {
                    $attempts[] = $now;
                    $result = ['allowed' => true, 'retry_after' => 0, 'remaining' => max(0, $maxAttempts - count($attempts))];
                }
            }

            $newState = ['attempts' => $attempts, 'blocked_until' => $blockedUntil, 'updated_at' => $now];
            @ftruncate($handle, 0);
            @rewind($handle);
            @fwrite($handle, (string)json_encode($newState));
            @fflush($handle);
            @flock($handle, LOCK_UN);
        }

        @fclose($handle);
        return $result;
    }

    /**
     * Consume one rate-limit attempt for a bucket.
     *
     * @param string $bucket
     * @param int $maxAttempts
     * @param int $windowSeconds
     * @param int $blockSeconds
     * @return array{allowed:bool,retry_after:int,remaining:int}
     */
    function auth_rate_limit_consume(string $bucket, int $maxAttempts, int $windowSeconds, int $blockSeconds = 0): array {
        if (auth_rate_limit_backend() === 'redis') {
            return auth_rate_limit_consume_redis($bucket, $maxAttempts, $windowSeconds, $blockSeconds);
        }
        return auth_rate_limit_consume_file($bucket, $maxAttempts, $windowSeconds, $blockSeconds);
    }
}

if (!function_exists('auth_rate_limit_reset')) {
    /**
     * Clear a rate-limit bucket after successful authentication.
     *
     * @param string $bucket
     * @return void
     */
    function auth_rate_limit_reset(string $bucket): void {
        $key = hash('sha256', strtolower(trim($bucket)));
        if (auth_rate_limit_backend() === 'redis') {
            $redis = auth_rate_limit_redis_client();
            if ($redis !== null) {
                try {
                    @ $redis->del('rate_limit:count:' . $key, 'rate_limit:block:' . $key);
                } catch (Throwable $e) {
                }
            }
            return;
        }

        $root = defined('PROJECT_ROOT') ? rtrim((string)PROJECT_ROOT, '/\\') : rtrim((string)dirname(__DIR__), '/\\');
        $dir = $root . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'rate_limits';
        $file = $dir . DIRECTORY_SEPARATOR . $key . '.json';
        if (is_file($file)) {
            @unlink($file);
        }
    }
}

/**
 * Ensure user is logged in, redirect to login page if not
 * 
 * @param string $redirect_to Optional custom redirect location
 * @return void Exits if user not logged in
 */
function require_login($redirect_to = null) {
    // Ensure session is started
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    
    if (empty($_SESSION['user'])) {
        // Only store an intended destination for interactive HTML navigations.
        // Do NOT save AJAX calls or API endpoints as post-login redirect targets
        // because those are typically background requests and not a page the
        // user should be sent to after authenticating.
        $isAjax = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
        $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
        $script = str_replace('\\\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));

        $isApiPath = strpos($script, '/dashboard/api/') !== false || strpos($script, '/api/') !== false;

        if (!$isAjax && strpos($accept, 'application/json') === false && !$isApiPath) {
            if ($redirect_to === null) {
                $redirect_to = $_SERVER['REQUEST_URI'] ?? '';
            }
            if (!empty($redirect_to)) {
                $_SESSION['redirect_after_login'] = $redirect_to;
            }
        }

        // Determine the correct path to login.php for both docroot modes
        $basePath = defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '';
        $publicPrefix = defined('PUBLIC_PATH_PREFIX') ? PUBLIC_PATH_PREFIX : '/public';
        $login_url = $basePath . $publicPrefix . '/login.php';
        header('Location: ' . $login_url);
        exit;
    }
}

/**
 * Get currently logged-in user data
 * 
 * @return array|null User data array or null if not logged in
 */
function current_user() {
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    return $_SESSION['user'] ?? null;
}

/**
 * Resolve current user id from normalized session shape.
 *
 * @return int
 */
function current_user_id() {
    $user = current_user();
    if (is_array($user) && isset($user['id'])) {
        return (int)$user['id'];
    }
    return (int)($_SESSION['user_id'] ?? 0);
}

/**
 * Resolve current username from normalized session shape.
 *
 * @return string
 */
function current_username() {
    $user = current_user();
    if (is_array($user)) {
        $username = (string)($user['username'] ?? $user['email'] ?? '');
        if ($username !== '') {
            return $username;
        }
    }
    return (string)($_SESSION['username'] ?? '');
}

/**
 * Check if user is logged in
 * 
 * @return bool True if user is logged in, false otherwise
 */
function is_logged_in() {
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    return !empty($_SESSION['user']);
}

if (!function_exists('auth_dashboard_url')) {
    /**
     * Resolve canonical dashboard landing URL for authenticated users.
     *
     * @return string
     */
    function auth_dashboard_url() {
        $basePath = defined('BASE_PATH') ? rtrim((string)BASE_PATH, '/') : '';
        $routeByRole = [
            'admin' => '/admin/dashboard.php',
            'worker' => '/worker/dashboard.php',
            'client' => '/client/dashboard.php',
            'dashboard' => '/dashboard/dashboard.php',
        ];

        $navRole = 'dashboard';
        if (function_exists('auth_resolve_navigation_role')) {
            $navRole = auth_resolve_navigation_role(current_user());
        } else {
            $user = current_user();
            $sessionRole = is_array($user) ? (string)($user['role'] ?? '') : '';
            if ($sessionRole === 'admin' || $sessionRole === 'worker') {
                $navRole = $sessionRole;
            }
        }

        return $basePath . ($routeByRole[$navRole] ?? '/dashboard/dashboard.php');
    }
}

if (!function_exists('redirect_authenticated_user_to_dashboard')) {
    /**
     * Redirect authenticated users to dashboard landing page.
     *
     * @return void
     */
    function redirect_authenticated_user_to_dashboard() {
        if (is_logged_in()) {
            header('Location: ' . auth_dashboard_url());
            exit;
        }
    }
}

/**
 * Enforce login for protected application routes.
 *
 * Protected areas: admin, dashboard, client, worker, and unified dashboard page.
 * Public pages under /public remain accessible without login.
 *
 * @return void
 */
function enforce_protected_route_login() {
    if (PHP_SAPI === 'cli') {
        return;
    }

    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($script === '') {
        return;
    }

    $protectedPrefixes = [
        '/admin/',
        '/dashboard/',
        '/client/',
        '/worker/',
    ];

    $isProtected = false;
    foreach ($protectedPrefixes as $prefix) {
        if (strpos($script, $prefix) !== false) {
            $isProtected = true;
            break;
        }
    }

    // Explicit direct-entry page in Common directory.
    if (!$isProtected && strpos($script, '/Common/dashboard_unified.php') !== false) {
        $isProtected = true;
    }

    if ($isProtected) {
        // Also support legacy include-style guard for explicit per-page requires
        $legacyGuard = rtrim((string)(defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__, 3)), '/\\') . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'auth_check.php';
        if (is_file($legacyGuard)) {
            @require_once $legacyGuard;
        }
        require_login();
        enforce_gui_navigation_guard($script);
    }
}

if (!function_exists('auth_strict_gui_navigation_enabled')) {
    /**
     * Determine if strict GUI-only navigation guard is enabled.
     *
     * @return bool
     */
    function auth_strict_gui_navigation_enabled(): bool
    {
        if (function_exists('env_bool')) {
            return env_bool('STRICT_GUI_NAVIGATION', false);
        }

        $raw = strtolower(trim((string)(getenv('STRICT_GUI_NAVIGATION') ?: '')));
        return in_array($raw, ['1', 'true', 'yes', 'on'], true);
    }
}

if (!function_exists('auth_is_interactive_page_get_request')) {
    /**
     * Check whether current request is an interactive HTML page GET request.
     *
     * @param string $script
     * @return bool
     */
    function auth_is_interactive_page_get_request(string $script): bool
    {
        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'GET') {
            return false;
        }

        if (strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest') {
            return false;
        }

        if (strpos($script, '/api/') !== false || strpos($script, '/dashboard/api/') !== false) {
            return false;
        }

        $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
        if ($accept === '') {
            return true;
        }

        return strpos($accept, 'text/html') !== false || strpos($accept, '*/*') !== false;
    }
}

if (!function_exists('auth_has_same_origin_referrer')) {
    /**
     * Validate that request referrer is same-origin and within app base path.
     *
     * @return bool
     */
    function auth_has_same_origin_referrer(): bool
    {
        $referrer = trim((string)($_SERVER['HTTP_REFERER'] ?? ''));
        if ($referrer === '') {
            return false;
        }

        $refHost = strtolower((string)(parse_url($referrer, PHP_URL_HOST) ?: ''));
        $curHost = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));

        if ($refHost === '' || $curHost === '') {
            return false;
        }

        $normalizedCurHost = preg_replace('/:[0-9]+$/', '', $curHost);
        if (!is_string($normalizedCurHost) || $normalizedCurHost === '') {
            $normalizedCurHost = $curHost;
        }

        if ($refHost !== $normalizedCurHost) {
            return false;
        }

        $refPath = (string)(parse_url($referrer, PHP_URL_PATH) ?: '/');
        $basePath = rtrim((string)(defined('BASE_PATH') ? BASE_PATH : ''), '/');
        if ($basePath === '') {
            return true;
        }

        return strpos($refPath, $basePath . '/') === 0 || $refPath === $basePath;
    }
}

if (!function_exists('auth_has_trusted_fetch_metadata_navigation')) {
    /**
     * Validate browser fetch metadata for in-app top-level navigation.
     *
     * @return bool
     */
    function auth_has_trusted_fetch_metadata_navigation(): bool
    {
        $fetchSite = strtolower(trim((string)($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '')));
        $fetchMode = strtolower(trim((string)($_SERVER['HTTP_SEC_FETCH_MODE'] ?? '')));
        $fetchDest = strtolower(trim((string)($_SERVER['HTTP_SEC_FETCH_DEST'] ?? '')));

        if ($fetchSite === '' && $fetchMode === '' && $fetchDest === '') {
            // Header set not present (older clients); caller should use referrer fallback.
            return false;
        }

        // Allow only top-level navigation requests originating from same-origin/site.
        if (!in_array($fetchSite, ['same-origin', 'same-site'], true)) {
            return false;
        }

        if ($fetchMode !== '' && $fetchMode !== 'navigate') {
            return false;
        }

        if ($fetchDest !== '' && $fetchDest !== 'document') {
            return false;
        }

        return true;
    }
}

if (!function_exists('enforce_gui_navigation_guard')) {
    /**
     * Block direct URL access for protected pages when strict mode is enabled.
     *
     * @param string $script
     * @return void
     */
    function enforce_gui_navigation_guard(string $script): void
    {
        if (!auth_strict_gui_navigation_enabled()) {
            return;
        }

        if (!auth_is_interactive_page_get_request($script)) {
            return;
        }

        if (auth_has_same_origin_referrer() || auth_has_trusted_fetch_metadata_navigation()) {
            return;
        }

        if (!headers_sent()) {
            http_response_code(403);
            header('Content-Type: text/html; charset=UTF-8');
        }

        $dashboardUrl = function_exists('auth_dashboard_url') ? auth_dashboard_url() : '/dashboard/dashboard.php';
        $safeDashboardUrl = htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8');

        echo '<!doctype html><html><head><meta charset="utf-8"><title>Direct Access Blocked</title></head><body style="font-family:Arial,sans-serif;padding:24px;line-height:1.5;">';
        echo '<h2>Direct URL access is blocked</h2>';
        echo '<p>Please navigate using the application menu and links.</p>';
        echo '<p><a href="' . $safeDashboardUrl . '">Return to dashboard</a></p>';
        echo '</body></html>';
        exit;
    }
}

/**
 * Check if current user has a specific role
 * 
 * @param string $role Role to check (e.g., 'admin', 'worker', 'client')
 * @return bool True if user has the role, false otherwise
 */
function has_role($role) {
    $user = current_user();
    if (!$user) {
        return false;
    }

    $userRole = $user['role'] ?? null;
    if (!$userRole) {
        return false;
    }

    // Admin is a superuser: allow access to any role-guarded area.
    if ($userRole === 'admin') {
        return true;
    }

    return $userRole === $role;
}

/**
 * Require specific role, redirect if not met
 * 
 * @param string $role Required role
 * @param string $redirect_to Optional redirect location if check fails
 * @return void Exits if role check fails
 */
function require_role($role, $redirect_to = null) {
    require_login();
    
    if (!has_role($role)) {
        if (function_exists('show_404')) {
            show_404();
        }
        
        $redirect_to = $redirect_to ?? (defined('BASE_PATH') ? rtrim(BASE_PATH, '/') . '/public/index.php' : '/public/index.php');
        header('Location: ' . $redirect_to);
        exit;
    }
}

if (!function_exists('auth_user_can_access_project')) {
    /**
     * Verify whether a user can access a project.
     *
     * @param int $projectId
     * @param int $userId
     * @param string|null $userRole
     * @return bool
     */
    function auth_user_can_access_project($projectId, $userId = 0, $userRole = null) {
        $pid = (int)$projectId;
        if ($pid <= 0) {
            return false;
        }

        $uid = (int)$userId;
        if ($uid <= 0) {
            $uid = current_user_id();
        }
        if ($uid <= 0) {
            return false;
        }

        $role = strtolower(trim((string)$userRole));
        if ($role === '') {
            $u = current_user();
            $role = strtolower(trim((string)($u['role'] ?? '')));
        }

        if ($role === 'admin') {
            return true;
        }

        if (!function_exists('db_fetch')) {
            return false;
        }

        $project = db_fetch('SELECT id, client_id, owner_email, owner_name, created_by FROM projects WHERE id = ? LIMIT 1', [$pid]);
        if (!$project) {
            return false;
        }

        if ((int)($project['created_by'] ?? 0) === $uid) {
            return true;
        }

        if ((int)($project['client_id'] ?? 0) === $uid) {
            return true;
        }

        if (db_table_exists('project_assignments')) {
            $assignment = db_fetch('SELECT id FROM project_assignments WHERE project_id = ? AND worker_id = ? LIMIT 1', [$pid, $uid]);
            if ($assignment) {
                return true;
            }
        }

        $user = current_user();
        $email = strtolower(trim((string)($user['email'] ?? '')));
        $username = strtolower(trim((string)($user['username'] ?? '')));
        $fullName = strtolower(trim((string)($user['full_name'] ?? ($user['name'] ?? ''))));

        if ($email !== '' && strtolower(trim((string)($project['owner_email'] ?? ''))) === $email) {
            return true;
        }
        $ownerName = strtolower(trim((string)($project['owner_name'] ?? '')));
        if ($ownerName !== '' && ($ownerName === $username || $ownerName === $fullName)) {
            return true;
        }

        // Keep legacy dashboard roles functional while strict checks roll out.
        return !in_array($role, ['client', 'worker'], true);
    }
}

/**
 * True when current request is a write request.
 *
 * @return bool
 */
function auth_is_write_method() {
    $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    return in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true);
}

/**
 * Check table existence without relying on util.php helpers.
 *
 * @param string $table
 * @return bool
 */
function auth_table_exists($table) {
    $db = get_db();
    if (!($db instanceof PDO)) {
        return false;
    }

    static $cache = [];
    $table = (string)$table;
    if (isset($cache[$table])) {
        return $cache[$table];
    }

    try {
        $stmt = $db->prepare('SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
        $stmt->execute([$table]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $cache[$table] = !empty($row) && (int)($row['c'] ?? 0) > 0;
    } catch (Exception $e) {
        $cache[$table] = false;
    }

    return $cache[$table];
}

/**
 * Map broad user role to preferred RBAC role code.
 *
 * @param string $role
 * @return string
 */
function auth_preferred_rbac_role_code_for_user_role($role) {
    $role = strtolower(trim((string)$role));

    if ($role === 'admin') {
        return 'emp_admin_manager';
    }

    if ($role === 'employee') {
        return 'emp_team_coordinator';
    }

    if ($role === 'worker') {
        return 'wrk_contractor';
    }

    return '';
}

/**
 * Sync broad user role into RBAC user_roles and active session.
 *
 * @param int $userId
 * @param string $role
 * @param int|null $assignedBy
 * @return bool
 */
function auth_sync_user_role_links($userId, $role, $assignedBy = null) {
    $userId = (int)$userId;
    $role = strtolower(trim((string)$role));
    $assignedBy = $assignedBy === null ? null : (int)$assignedBy;

    if ($userId <= 0) {
        return false;
    }

    $allowedRoles = ['admin', 'employee', 'worker', 'client'];
    if (!in_array($role, $allowedRoles, true)) {
        $role = 'client';
    }

    $db = get_db();
    if (!($db instanceof PDO)) {
        return false;
    }

    try {
        // Keep the canonical users table aligned even if caller only updated related tables.
        $u = $db->prepare('UPDATE users SET role = ?, updated_at = NOW() WHERE id = ?');
        $u->execute([$role, $userId]);

        if (auth_table_exists('user_roles')) {
            $del = $db->prepare('DELETE FROM user_roles WHERE user_id = ?');
            $del->execute([$userId]);

            $preferredCode = auth_preferred_rbac_role_code_for_user_role($role);
            if ($preferredCode !== '' && auth_table_exists('roles') && auth_table_exists('role_groups')) {
                $find = $db->prepare('SELECT r.id
                    FROM roles r
                    INNER JOIN role_groups rg ON rg.id = r.role_group_id
                    WHERE r.is_active = 1
                      AND (r.code = ? OR rg.code = ?)
                    ORDER BY CASE WHEN r.code = ? THEN 0 ELSE 1 END, r.id ASC
                    LIMIT 1');

                $groupCode = $role === 'worker' ? 'worker' : 'employee';
                $find->execute([$preferredCode, $groupCode, $preferredCode]);
                $r = $find->fetch(PDO::FETCH_ASSOC);

                if ($r && !empty($r['id'])) {
                    $roleId = (int)$r['id'];
                    $ins = $db->prepare('INSERT INTO user_roles (user_id, role_id, is_primary, assigned_by, assigned_at) VALUES (?, ?, 1, ?, NOW())');
                    $ins->execute([$userId, $roleId, $assignedBy]);
                }
            }
        }

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        if (!empty($_SESSION['user']) && (int)($_SESSION['user']['id'] ?? 0) === $userId) {
            $_SESSION['user']['role'] = $role;
        }

        if ((int)($_SESSION['user_id'] ?? 0) === $userId) {
            $_SESSION['role'] = $role;
        }

        return true;
    } catch (Exception $e) {
        if (function_exists('app_log')) {
            app_log('warning', 'auth_sync_user_role_links failed', ['exception' => $e->getMessage(), 'user_id' => (int)$userId]);
        }
        return false;
    }
}

/**
 * Fetch RBAC role context for logged-in user.
 *
 * @param int $userId
 * @return array
 */
function auth_get_user_rbac_context($userId) {
    $ctx = [
        'role_code' => '',
        'group_code' => '',
    ];

    $userId = (int)$userId;
    if ($userId <= 0 || !auth_table_exists('user_roles') || !auth_table_exists('roles') || !auth_table_exists('role_groups')) {
        return $ctx;
    }

    $db = get_db();
    if (!($db instanceof PDO)) {
        return $ctx;
    }

    try {
        $stmt = $db->prepare('SELECT r.code AS role_code, rg.code AS group_code
            FROM user_roles ur
            INNER JOIN roles r ON r.id = ur.role_id
            INNER JOIN role_groups rg ON rg.id = r.role_group_id
            WHERE ur.user_id = ?
            ORDER BY ur.is_primary DESC, ur.assigned_at DESC
            LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $ctx['role_code'] = (string)($row['role_code'] ?? '');
            $ctx['group_code'] = (string)($row['group_code'] ?? '');
        }
    } catch (Exception $e) {
        return $ctx;
    }

    return $ctx;
}

/**
 * Detect if user should be treated as admin.
 *
 * @param array|null $user
 * @param array $rbacCtx
 * @return bool
 */
function auth_is_admin_like($user, $rbacCtx = []) {
    $sessionRole = strtolower(trim(is_array($user) ? (string)($user['role'] ?? '') : ''));
    if ($sessionRole === 'admin') {
        return true;
    }

    if (in_array($sessionRole, ['employee', 'worker', 'client'], true)) {
        return false;
    }

    $roleCode = (string)($rbacCtx['role_code'] ?? '');
    return $roleCode === 'emp_admin_manager';
}

/**
 * Detect if user should be treated as worker/site-ops.
 *
 * @param array|null $user
 * @param array $rbacCtx
 * @return bool
 */
function auth_is_worker_like($user, $rbacCtx = []) {
    $sessionRole = strtolower(trim(is_array($user) ? (string)($user['role'] ?? '') : ''));
    if ($sessionRole === 'worker') {
        return true;
    }

    if (in_array($sessionRole, ['admin', 'employee', 'client'], true)) {
        return false;
    }

    $roleCode = (string)($rbacCtx['role_code'] ?? '');
    $groupCode = (string)($rbacCtx['group_code'] ?? '');

    if ($groupCode === 'worker') {
        return true;
    }

    return $roleCode !== '' && strpos($roleCode, 'wrk_') === 0;
}

if (!function_exists('auth_resolve_navigation_role')) {
    /**
     * Resolve effective navigation role for role-based sidebar rendering.
     *
     * Returns one of: admin, worker, dashboard.
     *
     * @param array|null $user
     * @return string
     */
    function auth_resolve_navigation_role($user = null) {
        if ($user === null) {
            $user = current_user();
        }

        if (!is_array($user)) {
            return 'dashboard';
        }

        $userId = (int)($user['id'] ?? 0);
        $rbacCtx = $userId > 0 ? auth_get_user_rbac_context($userId) : [];

        if (auth_is_admin_like($user, $rbacCtx)) {
            return 'admin';
        }

        if (auth_is_worker_like($user, $rbacCtx)) {
            return 'worker';
        }

        return 'dashboard';
    }
}

/**
 * Resolve dashboard module code from current script path.
 *
 * @return string
 */
function auth_resolve_module_code_from_request() {
    if (!auth_table_exists('dashboard_modules')) {
        return '';
    }

    $db = get_db();
    if (!($db instanceof PDO)) {
        return '';
    }

    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($script === '') {
        return '';
    }

    try {
        $stmt = $db->query('SELECT code, route FROM dashboard_modules WHERE route IS NOT NULL AND route <> ""');
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        $best = '';
        $bestLen = 0;
        foreach ($rows as $r) {
            $route = str_replace('\\', '/', (string)($r['route'] ?? ''));
            if ($route === '') {
                continue;
            }
            if (strpos($script, $route) !== false && strlen($route) > $bestLen) {
                $best = (string)($r['code'] ?? '');
                $bestLen = strlen($route);
            }
        }
        return $best;
    } catch (Exception $e) {
        return '';
    }
}

/**
 * Check module write access from role_dashboard_access table.
 *
 * @param int $userId
 * @param string $moduleCode
 * @return bool
 */
function auth_user_can_write_module($userId, $moduleCode) {
    $userId = (int)$userId;
    $moduleCode = (string)$moduleCode;
    if ($userId <= 0 || $moduleCode === '') {
        return false;
    }

    if (!auth_table_exists('user_roles') || !auth_table_exists('dashboard_modules') || !auth_table_exists('role_dashboard_access')) {
        return false;
    }

    $db = get_db();
    if (!($db instanceof PDO)) {
        return false;
    }

    try {
        $stmt = $db->prepare('SELECT 1
            FROM user_roles ur
            INNER JOIN role_dashboard_access rda ON rda.role_id = ur.role_id
            INNER JOIN dashboard_modules dm ON dm.id = rda.module_id
            WHERE ur.user_id = ?
              AND dm.code = ?
              AND (rda.can_create = 1 OR rda.can_update = 1 OR rda.can_delete = 1)
            LIMIT 1');
        $stmt->execute([$userId, $moduleCode]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Check if user has any explicit write-like permission in role_permissions.
 *
 * @param int $userId
 * @return bool
 */
function auth_user_has_any_write_permission($userId) {
    $userId = (int)$userId;
    if ($userId <= 0) {
        return false;
    }

    if (!auth_table_exists('user_roles') || !auth_table_exists('role_permissions') || !auth_table_exists('permissions')) {
        return false;
    }

    $db = get_db();
    if (!($db instanceof PDO)) {
        return false;
    }

    try {
        $stmt = $db->prepare('SELECT 1
            FROM user_roles ur
            INNER JOIN role_permissions rp ON rp.role_id = ur.role_id
            INNER JOIN permissions p ON p.id = rp.permission_id
            WHERE ur.user_id = ?
              AND rp.is_allowed = 1
              AND LOWER(p.action) IN ("create", "update", "delete", "write", "manage")
            LIMIT 1');
        $stmt->execute([$userId]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Enforce write access by DB permissions with worker-safe default deny.
 *
 * Policy:
 * - Non-write requests are ignored.
 * - Admin-like users always allowed.
 * - Worker-like users are denied unless module write access or explicit write permission exists.
 *
 * @return void
 */
function enforce_request_write_permission() {
    if (!auth_is_write_method()) {
        return;
    }

    $script = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    // Do not interfere with auth entry point where session may be created.
    if (strpos($script, '/public/login_register.php') !== false) {
        return;
    }

    // Allow project file API upload/log actions to proceed to endpoint-level authorization.
    if (strpos($script, '/dashboard/api/project_files.php') !== false) {
        $apiAction = strtolower(trim((string)($_POST['action'] ?? '')));
        if (in_array($apiAction, ['upload_file', 'upload_drawing', 'log_activity'], true)) {
            return;
        }
    }

    // Allow notification read APIs for authenticated users.
    if (strpos($script, '/dashboard/api/notifications.php') !== false) {
        return;
    }

    $user = current_user();
    if (!is_array($user)) {
        return;
    }

    $userId = (int)($user['id'] ?? 0);
    $rbacCtx = auth_get_user_rbac_context($userId);

    if (auth_is_admin_like($user, $rbacCtx)) {
        return;
    }

    $moduleCode = auth_resolve_module_code_from_request();
    $allowedByModule = auth_user_can_write_module($userId, $moduleCode);
    $allowedByPermission = auth_user_has_any_write_permission($userId);
    if ($allowedByModule || $allowedByPermission) {
        return;
    }

    if (!auth_is_worker_like($user, $rbacCtx)) {
        return;
    }

    http_response_code(403);
    $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
    $isAjax = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    if ($isAjax || strpos($accept, 'application/json') !== false) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => false,
            'error' => 'forbidden',
            'message' => 'Write access denied for worker role.',
        ]);
        exit;
    }

    echo '403 Forbidden: Write access denied for worker role.';
    exit;
}

/**
 * Create and store a persistent "remember me" token for a user and set cookie.
 * Stores a SHA256 hash of the token in the database for lookup.
 *
 * @param int $userId
 * @param int $days
 * @return bool True on success
 */
function auth_set_remember_token($userId, $days = 365)
{
    $db = get_db();
    if (!($db instanceof PDO) || $userId <= 0) {
        return false;
    }

    try {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + ($days * 24 * 60 * 60));

        // Remove old remember tokens for this user to limit active tokens
        $del = $db->prepare('DELETE FROM auth_tokens WHERE user_id = ? AND token_type = ?');
        $del->execute([(int)$userId, 'remember']);

        $ins = $db->prepare('INSERT INTO auth_tokens (user_id, token, token_type, expires_at) VALUES (?, ?, ?, ?)');
        $ins->execute([(int)$userId, $tokenHash, 'remember', $expiresAt]);

        // Set cookie with raw token (hashed copy stored in DB)
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        // Use options array available in PHP 7.3+
        $cookieOptions = [
            'expires' => time() + ($days * 24 * 60 * 60),
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Strict',
        ];
        $setOk = @setcookie('remember_me', $token, $cookieOptions);
        // Log for debugging persistent login issues (do not log token value)
        if (function_exists('app_log')) {
            app_log('debug', 'auth_set_remember_token', [
                'user' => (int)$userId,
                'days' => (int)$days,
                'expires' => $expiresAt,
                'secure' => $secure ? 1 : 0,
                'setcookie_ok' => $setOk ? 1 : 0,
            ]);
        }

        return true;
    } catch (Exception $e) {
        if (function_exists('app_log')) {
            app_log('warning', 'auth_set_remember_token failed', ['exception' => $e->getMessage(), 'user_id' => (int)$userId]);
        }
        return false;
    }
}


/**
 * Attempt to log user in from remember-me cookie when session is empty.
 * If successful, populates $_SESSION['user'] similar to interactive login.
 *
 * @return bool True if auto-login succeeded
 */
function auth_try_auto_login()
{
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }

    if (!empty($_SESSION['user'])) {
        return false;
    }

    // Debug: log whether remember cookie is present
    if (empty($_COOKIE['remember_me'])) {
        if (function_exists('app_log')) {
            app_log('debug', 'auth_try_auto_login: no remember_me cookie present');
        }
        return false;
    }

    $token = (string) $_COOKIE['remember_me'];
    if ($token === '') {
        if (function_exists('app_log')) {
            app_log('debug', 'auth_try_auto_login: remember_me cookie empty string');
        }
        return false;
    }
    // Avoid logging raw token value
    if (function_exists('app_log')) {
        app_log('debug', 'auth_try_auto_login: remember_me cookie length', ['length' => strlen($token)]);
    }
    $tokenHash = hash('sha256', $token);
    $db = get_db();
    if (!($db instanceof PDO)) {
        if (function_exists('app_log')) {
            app_log('debug', 'auth_try_auto_login: DB unavailable');
        }
        return false;
    }

    try {
        $stmt = $db->prepare('SELECT user_id FROM auth_tokens WHERE token = ? AND token_type = ? AND (expires_at IS NULL OR expires_at > NOW()) LIMIT 1');
        $stmt->execute([$tokenHash, 'remember']);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            // invalid token — clear cookie and log
            if (function_exists('app_log')) {
                app_log('debug', 'auth_try_auto_login: token not found or expired in DB');
            }
            auth_clear_remember_cookie();
            return false;
        }

        $userId = (int) ($row['user_id'] ?? 0);
        if ($userId <= 0) {
            auth_clear_remember_cookie();
            return false;
        }

        $u = $db->prepare('SELECT id, username, email, first_name, last_name, full_name, role FROM users WHERE id = ? LIMIT 1');
        $u->execute([$userId]);
        $user = $u->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            auth_clear_remember_cookie();
            return false;
        }

        $first = (string) ($user['first_name'] ?? '');
        $last = (string) ($user['last_name'] ?? '');
        $displayName = trim((string) ($user['full_name'] ?? '')) ?: trim($first . ' ' . $last) ?: (string) ($user['username'] ?? $user['email']);

        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'first_name' => $first,
            'last_name' => $last,
            'email' => (string) ($user['email'] ?? ''),
            'username' => (string) ($user['username'] ?? ''),
            'role' => (string) ($user['role'] ?? 'client'),
            'name' => $displayName,
        ];
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['login_success'] = 'Logged in via remembered session.';

        // Optionally rotate token: create a new one and delete the old
        auth_set_remember_token($userId);

        return true;
    } catch (Exception $e) {
        if (function_exists('app_log')) {
            app_log('warning', 'auth_try_auto_login failed', ['exception' => $e->getMessage()]);
        }
        return false;
    }
}


/**
 * Delete remember tokens for a user from DB.
 *
 * @param int $userId
 * @return void
 */
function auth_clear_remember_tokens_for_user($userId)
{
    $db = get_db();
    if (!($db instanceof PDO) || $userId <= 0) {
        return;
    }

    try {
        $del = $db->prepare('DELETE FROM auth_tokens WHERE user_id = ? AND token_type = ?');
        $del->execute([(int)$userId, 'remember']);
    } catch (Exception $e) {
        if (function_exists('app_log')) {
            app_log('warning', 'auth_clear_remember_tokens_for_user failed', ['exception' => $e->getMessage(), 'user_id' => (int)$userId]);
        }
    }
}


/**
 * Remove remember_me cookie from client.
 *
 * @return void
 */
function auth_clear_remember_cookie()
{
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    setcookie('remember_me', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
}

?>
