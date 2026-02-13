<?php
// util.php - small reusable helper functions

if (!function_exists('esc')) {
    function esc($s) {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('base_url')) {
    function base_url($path = '') {
        if (!defined('BASE_URL')) return $path;
        $p = rtrim(BASE_URL, '/');
        if ($path === '') return $p;
        return $p . '/' . ltrim($path, '/');
    }
}

if (!function_exists('base_path')) {
    function base_path($path = '') {
        if (!defined('BASE_PATH')) return $path;
        $p = rtrim(BASE_PATH, '/');
        if ($path === '') return $p;
        return $p . '/' . ltrim($path, '/');
    }
}

if (!function_exists('render_flash')) {
    function render_flash() {
        if (!empty($_SESSION['flash'])) {
            echo '<div class="alert alert-info">' . esc($_SESSION['flash']) . '</div>';
            unset($_SESSION['flash']);
        }
    }
}

// Simple DB helper: prepare & execute, returns PDOStatement or false
if (!function_exists('db_query')) {
    function db_query($sql, $params = []) {
        global $pdo;
        if (empty($pdo)) return false;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}

// --- Asset enqueueing helpers ---
if (!isset($GLOBALS['__asset_queue_css'])) $GLOBALS['__asset_queue_css'] = [];
if (!isset($GLOBALS['__asset_queue_js'])) $GLOBALS['__asset_queue_js'] = [];

if (!function_exists('asset_enqueue_css')) {
    function asset_enqueue_css($path) {
        $k = (string)$path;
        if (!in_array($k, $GLOBALS['__asset_queue_css'], true)) {
            $GLOBALS['__asset_queue_css'][] = $k;
        }
    }
}

if (!function_exists('asset_enqueue_js')) {
    function asset_enqueue_js($path, $opts = []) {
        $k = (string)$path;
        if (!isset($opts['defer'])) $opts['defer'] = false;
        // store as associative to preserve options
        foreach ($GLOBALS['__asset_queue_js'] as $entry) {
            if (!empty($entry['path']) && $entry['path'] === $k) return;
        }
        $GLOBALS['__asset_queue_js'][] = ['path' => $k, 'defer' => (bool)$opts['defer']];
    }
}

if (!function_exists('render_head_assets')) {
    function render_head_assets() {
        if (empty($GLOBALS['__asset_queue_css'])) return;
        foreach ($GLOBALS['__asset_queue_css'] as $css) {
            $href = (strpos($css, 'http') === 0) ? $css : (defined('BASE_PATH') ? rtrim(BASE_PATH, '/') . '/' . ltrim($css, '/') : $css);
            echo '<link rel="stylesheet" href="' . htmlspecialchars($href, ENT_QUOTES) . '">' . "\n";
        }
    }
}

if (!function_exists('render_footer_scripts')) {
    function render_footer_scripts() {
        if (empty($GLOBALS['__asset_queue_js'])) return;
        foreach ($GLOBALS['__asset_queue_js'] as $entry) {
            $src = $entry['path'];
            $defer = !empty($entry['defer']);
            $srcurl = (strpos($src, 'http') === 0) ? $src : (defined('BASE_PATH') ? rtrim(BASE_PATH, '/') . '/' . ltrim($src, '/') : $src);
            echo '<script' . ($defer ? ' defer' : '') . ' src="' . htmlspecialchars($srcurl, ENT_QUOTES) . '"></script>' . "\n";
        }
    }
}

?>
