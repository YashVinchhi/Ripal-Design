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
        // Store the intended destination for post-login redirect
        if ($redirect_to === null) {
            $redirect_to = $_SERVER['REQUEST_URI'] ?? '';
        }
        if (!empty($redirect_to)) {
            $_SESSION['redirect_after_login'] = $redirect_to;
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
        require_login();
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
        $redirect_to = $redirect_to ?? (defined('BASE_PATH') ? rtrim(BASE_PATH, '/') . '/public/index.php' : '/public/index.php');
        header('Location: ' . $redirect_to);
        exit;
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
    $sessionRole = is_array($user) ? (string)($user['role'] ?? '') : '';
    if ($sessionRole === 'admin') {
        return true;
    }

    $roleCode = (string)($rbacCtx['role_code'] ?? '');
    return $roleCode !== '' && strpos($roleCode, 'admin') !== false;
}

/**
 * Detect if user should be treated as worker/site-ops.
 *
 * @param array|null $user
 * @param array $rbacCtx
 * @return bool
 */
function auth_is_worker_like($user, $rbacCtx = []) {
    $sessionRole = is_array($user) ? (string)($user['role'] ?? '') : '';
    if ($sessionRole === 'worker') {
        return true;
    }

    $roleCode = (string)($rbacCtx['role_code'] ?? '');
    $groupCode = (string)($rbacCtx['group_code'] ?? '');

    if ($groupCode === 'site_ops') {
        return true;
    }

    return $roleCode !== '' && strpos($roleCode, 'site_') === 0;
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
function auth_set_remember_token($userId, $days = 30)
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
        setcookie('remember_me', $token, [
            'expires' => time() + ($days * 24 * 60 * 60),
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        return true;
    } catch (Exception $e) {
        error_log('auth_set_remember_token failed: ' . $e->getMessage());
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

    if (empty($_COOKIE['remember_me'])) {
        return false;
    }

    $token = (string) $_COOKIE['remember_me'];
    if ($token === '') {
        return false;
    }

    $tokenHash = hash('sha256', $token);
    $db = get_db();
    if (!($db instanceof PDO)) {
        return false;
    }

    try {
        $stmt = $db->prepare('SELECT user_id FROM auth_tokens WHERE token = ? AND token_type = ? AND (expires_at IS NULL OR expires_at > NOW()) LIMIT 1');
        $stmt->execute([$tokenHash, 'remember']);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            // invalid token — clear cookie
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
        error_log('auth_try_auto_login failed: ' . $e->getMessage());
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
        error_log('auth_clear_remember_tokens_for_user failed: ' . $e->getMessage());
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
        'samesite' => 'Lax',
    ]);
}

?>