<?php
/**
 * Utility Helper Functions
 * 
 * Collection of reusable helper functions for the application.
 * Includes output escaping, URL generation, asset management, and database helpers.
 * 
 * @package RipalDesign
 * @subpackage Utilities
 */

// ============================================================================
// OUTPUT ESCAPING AND SANITIZATION
// ============================================================================

if (!function_exists('esc')) {
    /**
     * Escape HTML output to prevent XSS attacks
     * 
     * @param mixed $s String to escape
     * @return string Escaped HTML string
     */
    function esc($s) {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_attr')) {
    /**
     * Escape HTML attribute value
     * 
     * @param mixed $s String to escape
     * @return string Escaped attribute string
     */
    function esc_attr($s) {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_js')) {
    /**
     * Escape JavaScript string
     * 
     * @param mixed $s String to escape
     * @return string Escaped JavaScript string
     */
    function esc_js($s) {
        return json_encode((string)$s, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }
}

// ============================================================================
// URL AND PATH HELPERS
// ============================================================================

if (!function_exists('base_url')) {
    /**
     * Generate absolute URL from path
     * 
     * @param string $path Path to append to base URL
     * @return string Complete URL
     */
    function base_url($path = '') {
        if (!defined('BASE_URL')) return $path;
        $baseUrl = rtrim(BASE_URL, '/');
        if ($path === '') return $baseUrl;
        return $baseUrl . '/' . ltrim($path, '/');
    }
}

if (!function_exists('base_path')) {
    /**
     * Generate path relative to base
     * 
     * @param string $path Path to append
     * @return string Complete path
     */
    function base_path($path = '') {
        if (!defined('BASE_PATH')) return $path;
        $basePath = rtrim(BASE_PATH, '/');
        if ($path === '') return $basePath;
        return $basePath . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset_url')) {
    /**
     * Generate URL for static asset
     * 
     * @param string $path Path to asset
     * @return string Complete asset URL
     */
    function asset_url($path) {
        return base_url('assets/' . ltrim($path, '/'));
    }
}

// ============================================================================
// FLASH MESSAGES
// ============================================================================

if (!function_exists('set_flash')) {
    /**
     * Set a flash message for next page load
     * 
     * @param string $message Message to display
     * @param string $type Message type (success, error, warning, info)
     * @return void
     */
    function set_flash($message, $type = 'info') {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['flash'] = [
            'message' => $message,
            'type' => $type
        ];
    }
}

if (!function_exists('render_flash')) {
    /**
     * Render and clear flash message
     * 
     * @return void Outputs HTML
     */
    function render_flash() {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        
        if (!empty($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            $message = esc($flash['message'] ?? '');
            $type = esc($flash['type'] ?? 'info');
            
            // Map types to Bootstrap alert classes
            $alertClass = 'alert-' . $type;
            
            echo '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">';
            echo $message;
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
            
            unset($_SESSION['flash']);
        }
    }
}

// ============================================================================
// DATABASE HELPERS
// ============================================================================

if (!function_exists('db_query')) {
    /**
     * Execute prepared SQL query
     * 
     * @param string $sql SQL query with placeholders
     * @param array $params Parameters for prepared statement
     * @return PDOStatement|false Result statement or false on failure
     */
    function db_query($sql, $params = []) {
        global $pdo;
        if (empty($pdo)) return false;
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log('Database query error: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('db_fetch')) {
    /**
     * Fetch single row from database
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return array|false Row data or false
     */
    function db_fetch($sql, $params = []) {
        $stmt = db_query($sql, $params);
        return $stmt ? $stmt->fetch() : false;
    }
}

if (!function_exists('db_fetch_all')) {
    /**
     * Fetch all rows from database
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return array Array of rows
     */
    function db_fetch_all($sql, $params = []) {
        $stmt = db_query($sql, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }
}

// ============================================================================
// ASSET QUEUE MANAGEMENT
// ============================================================================

// Initialize asset queues
if (!isset($GLOBALS['__asset_queue_css'])) $GLOBALS['__asset_queue_css'] = [];
if (!isset($GLOBALS['__asset_queue_js'])) $GLOBALS['__asset_queue_js'] = [];

if (!function_exists('asset_enqueue_css')) {
    /**
     * Add CSS file to render queue
     * 
     * @param string $path Path to CSS file
     * @param int $priority Optional priority (lower numbers load first)
     * @return void
     */
    function asset_enqueue_css($path, $priority = 10) {
        $key = (string)$path;
        
        // Check if already enqueued
        foreach ($GLOBALS['__asset_queue_css'] as $item) {
            if ($item['path'] === $key) return;
        }
        
        $GLOBALS['__asset_queue_css'][] = [
            'path' => $key,
            'priority' => $priority
        ];
    }
}

if (!function_exists('asset_enqueue_js')) {
    /**
     * Add JavaScript file to render queue
     * 
     * @param string $path Path to JS file
     * @param array $opts Options: defer, async, priority
     * @return void
     */
    function asset_enqueue_js($path, $opts = []) {
        $key = (string)$path;
        
        // Set defaults
        if (!isset($opts['defer'])) $opts['defer'] = false;
        if (!isset($opts['async'])) $opts['async'] = false;
        if (!isset($opts['priority'])) $opts['priority'] = 10;
        
        // Check if already enqueued
        foreach ($GLOBALS['__asset_queue_js'] as $item) {
            if ($item['path'] === $key) return;
        }
        
        $GLOBALS['__asset_queue_js'][] = [
            'path' => $key,
            'defer' => (bool)$opts['defer'],
            'async' => (bool)$opts['async'],
            'priority' => $opts['priority']
        ];
    }
}

if (!function_exists('render_head_assets')) {
    /**
     * Render all enqueued CSS assets
     * 
     * @return void Outputs HTML
     */
    function render_head_assets() {
        if (empty($GLOBALS['__asset_queue_css'])) return;
        
        // Sort by priority
        usort($GLOBALS['__asset_queue_css'], function($a, $b) {
            return ($a['priority'] ?? 10) - ($b['priority'] ?? 10);
        });
        
        foreach ($GLOBALS['__asset_queue_css'] as $item) {
            $css = $item['path'];
            $href = (strpos($css, 'http') === 0) ? $css : base_path($css);
            echo '<link rel="stylesheet" href="' . esc_attr($href) . '">' . "\n";
        }
    }
}

if (!function_exists('render_footer_scripts')) {
    /**
     * Render all enqueued JavaScript assets
     * 
     * @return void Outputs HTML
     */
    function render_footer_scripts() {
        if (empty($GLOBALS['__asset_queue_js'])) return;
        
        // Sort by priority
        usort($GLOBALS['__asset_queue_js'], function($a, $b) {
            return ($a['priority'] ?? 10) - ($b['priority'] ?? 10);
        });
        
        foreach ($GLOBALS['__asset_queue_js'] as $entry) {
            $src = $entry['path'];
            $defer = !empty($entry['defer']);
            $async = !empty($entry['async']);
            
            $srcUrl = (strpos($src, 'http') === 0) ? $src : base_path($src);
            
            $attrs = [];
            if ($defer) $attrs[] = 'defer';
            if ($async) $attrs[] = 'async';
            
            $attrStr = !empty($attrs) ? ' ' . implode(' ', $attrs) : '';
            echo '<script' . $attrStr . ' src="' . esc_attr($srcUrl) . '"></script>' . "\n";
        }
    }
}

// ============================================================================
// MISCELLANEOUS HELPERS
// ============================================================================

if (!function_exists('redirect')) {
    /**
     * Redirect to another page
     * 
     * @param string $url URL to redirect to
     * @param int $statusCode HTTP status code
     * @return void Exits after redirect
     */
    function redirect($url, $statusCode = 302) {
        header('Location: ' . $url, true, $statusCode);
        exit;
    }
}

if (!function_exists('format_date')) {
    /**
     * Format date for display
     * 
     * @param string $date Date string
     * @param string $format PHP date format
     * @return string Formatted date
     */
    function format_date($date, $format = 'Y-m-d') {
        if (empty($date)) return '';
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        return date($format, $timestamp);
    }
}

?>
