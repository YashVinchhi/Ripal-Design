<?php
// CSRF helpers
// Place this in /includes and ensure bootstrap loads it so functions are available.

if (session_status() === PHP_SESSION_NONE) {
    @ini_set('session.use_strict_mode', '1');
    @session_start();
}

if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token(): string
    {
        if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token']) || $_SESSION['csrf_token'] === '') {
            try {
                if (function_exists('random_bytes')) {
                    $bytes = random_bytes(32);
                } elseif (function_exists('openssl_random_pseudo_bytes')) {
                    $bytes = openssl_random_pseudo_bytes(32);
                } else {
                    $bytes = uniqid('', true);
                }
                $token = is_string($bytes) ? bin2hex($bytes) : bin2hex((string)$bytes);
            } catch (Throwable $e) {
                $token = bin2hex(uniqid('', true));
            }
            $_SESSION['csrf_token'] = $token;
            // Backwards compatibility with older code using _csrf_token
            if (!isset($_SESSION['_csrf_token'])) {
                $_SESSION['_csrf_token'] = $_SESSION['csrf_token'];
            }
        }
        return (string)$_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_input')) {
    function csrf_input(): string
    {
        $t = generate_csrf_token();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($t, ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('validate_csrf_token')) {
    function validate_csrf_token($token = null): bool
    {
        if ($token === null) {
            if (!empty($_POST['csrf_token'])) {
                $token = (string)$_POST['csrf_token'];
            } elseif (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
                $token = (string)$_SERVER['HTTP_X_CSRF_TOKEN'];
            } else {
                $token = '';
            }
        }

        if (!is_string($token) || $token === '') {
            return false;
        }

        $sessionTokens = [];
        if (!empty($_SESSION['csrf_token'])) $sessionTokens[] = (string)$_SESSION['csrf_token'];
        if (!empty($_SESSION['_csrf_token'])) $sessionTokens[] = (string)$_SESSION['_csrf_token'];

        foreach ($sessionTokens as $st) {
            if (is_string($st) && hash_equals($st, $token)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('enforce_csrf')) {
    function enforce_csrf(): void
    {
        if (!validate_csrf_token()) {
            if (function_exists('app_log')) {
                $ip = function_exists('auth_request_ip') ? auth_request_ip() : ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
                app_log('warning', 'CSRF token validation failed', ['script' => $_SERVER['SCRIPT_NAME'] ?? '', 'ip' => $ip, 'post_keys' => array_keys($_POST ?? [])]);
            }
            if (!headers_sent()) {
                header('Content-Type: text/plain; charset=utf-8', true, 403);
            }
            echo 'CSRF validation failed.';
            exit;
        }
    }
}

if (!function_exists('csrf_meta_tag')) {
    function csrf_meta_tag(): string
    {
        return '<meta name="csrf-token" content="' . htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}

// Backwards-compatible wrappers (legacy code may call these names)
if (!function_exists('require_csrf')) {
    function require_csrf(): void
    {
        enforce_csrf();
    }
}

if (!function_exists('csrf_token_field')) {
    function csrf_token_field(): string
    {
        return csrf_input();
    }
}
