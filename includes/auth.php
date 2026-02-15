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
        
        // Determine the correct path to login.php
        $login_url = defined('BASE_PATH') ? rtrim(BASE_PATH, '/') . '/public/login.php' : '/public/login.php';
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
    return isset($user['role']) && $user['role'] === $role;
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
?>