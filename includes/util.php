<?php

require_once dirname(__DIR__) . '/app/Core/Support/util.php';
return;
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

// Backwards-compatible alias used in some templates: `h()` -> `esc()`
if (!function_exists('h')) {
    /**
     * Short HTML escape alias for templates
     *
     * @param mixed $s
     * @return string
     */
    function h($s) {
        return esc($s);
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

if (!function_exists('file_viewer_url')) {
    /**
     * Build canonical URL for the file viewer.
     *
    * Supported params: kind (file|drawing), id, file, project_id, view, ext.
     *
     * @param array $params
     * @return string
     */
    function file_viewer_url($params = []) {
        $base = rtrim((string)base_path('admin/file_viewer.php'), '/');
        $query = [];

        $kind = strtolower(trim((string)($params['kind'] ?? '')));
        $id = (int)($params['id'] ?? 0);
        if (in_array($kind, ['file', 'drawing'], true) && $id > 0) {
            $query['kind'] = $kind;
            $query['id'] = $id;
        }

        $file = trim((string)($params['file'] ?? ''));
        if ($file !== '') {
            $query['file'] = $file;
        }

        $projectId = (int)($params['project_id'] ?? 0);
        if ($projectId > 0) {
            $query['project_id'] = $projectId;
        }

        $view = strtolower(trim((string)($params['view'] ?? '')));
        if ($view !== '') {
            $query['view'] = $view;
        }

        $ext = strtolower(trim((string)($params['ext'] ?? '')));
        if ($ext !== '') {
            $query['ext'] = $ext;
        }

        return empty($query) ? $base : ($base . '?' . http_build_query($query));
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
            if (function_exists('app_log')) {
                app_log('warning', 'Database query error', ['exception' => $e->getMessage()]);
            }
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
            if (strpos($css, 'http') === 0) {
                $href = $css;
            } else {
                $normalized = '/' . ltrim((string)$css, '/');
                $prefix = defined('PUBLIC_PATH_PREFIX') ? (string)PUBLIC_PATH_PREFIX : '';

                if ($prefix === '/public' && stripos($normalized, '/public/') !== 0) {
                    $normalized = '/public' . $normalized;
                } elseif ($prefix === '' && stripos($normalized, '/public/') === 0) {
                    $normalized = substr($normalized, 7);
                }

                $href = base_path($normalized);
            }
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

            if (strpos($src, 'http') === 0) {
                $srcUrl = $src;
            } else {
                $normalized = '/' . ltrim((string)$src, '/');
                $prefix = defined('PUBLIC_PATH_PREFIX') ? (string)PUBLIC_PATH_PREFIX : '';

                if ($prefix === '/public' && stripos($normalized, '/public/') !== 0) {
                    $normalized = '/public' . $normalized;
                } elseif ($prefix === '' && stripos($normalized, '/public/') === 0) {
                    $normalized = substr($normalized, 7);
                }

                $srcUrl = base_path($normalized);
            }
            
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

if (!function_exists('db_table_exists')) {
    /**
     * Check whether a table exists in current database.
     *
     * @param string $table Table name
     * @return bool
     */
    function db_table_exists($table) {
        $row = db_fetch("SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?", [$table]);
        return !empty($row) && (int)($row['c'] ?? 0) > 0;
    }
}

if (!function_exists('db_column_exists')) {
    /**
     * Check whether a column exists on a table in current database.
     *
     * @param string $table Table name
     * @param string $column Column name
     * @return bool
     */
    function db_column_exists($table, $column) {
        $row = db_fetch(
            'SELECT COUNT(*) AS c FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?',
            [$table, $column]
        );
        return !empty($row) && (int)($row['c'] ?? 0) > 0;
    }
}

if (!function_exists('is_valid_google_maps_url')) {
    /**
     * Validate if a URL points to a Google Maps domain.
     *
     * @param string $url
     * @return bool
     */
    function is_valid_google_maps_url($url) {
        $url = trim((string)$url);
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $host = strtolower((string)(parse_url($url, PHP_URL_HOST) ?? ''));
        if ($host === '') {
            return false;
        }

        if (preg_match('/(^|\.)google\.[a-z.]+$/i', $host)) {
            return true;
        }

        return in_array($host, ['maps.app.goo.gl', 'goo.gl'], true);
    }
}

if (!function_exists('build_google_maps_embed_src')) {
    /**
     * Build a Google Maps iframe URL from a map link, coordinates, or plain address.
     *
     * @param string $value
     * @return string
     */
    function build_google_maps_embed_src($value) {
        $input = trim((string)$value);
        if ($input === '') {
            return '';
        }

        if (preg_match('/^(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)$/', $input, $m)) {
            return 'https://www.google.com/maps?q=' . rawurlencode($m[1] . ',' . $m[2]) . '&z=17&output=embed';
        }

        if (filter_var($input, FILTER_VALIDATE_URL)) {
            $normalized = normalize_google_maps_embed_query($input);
            if ($normalized !== '') {
                return 'https://www.google.com/maps?q=' . rawurlencode($normalized) . '&output=embed';
            }
        }

        // Keep this generic so short links and place links both resolve.
        return 'https://www.google.com/maps?q=' . rawurlencode($input) . '&output=embed';
    }
}

if (!function_exists('normalize_google_maps_embed_query')) {
    /**
     * Best-effort normalization of Google Maps URLs into embeddable map queries.
     *
     * @param string $url
     * @return string
     */
    function normalize_google_maps_embed_query($url) {
        $url = trim((string)$url);
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        $url = canonicalize_google_maps_url($url);

        $parts = parse_url($url);
        $path = rawurldecode((string)($parts['path'] ?? ''));
        $queryString = (string)($parts['query'] ?? '');

        if (preg_match('@/maps/search/([^/?]+)@', $path, $m)) {
            $searchTerm = trim(str_replace('+', ' ', (string)$m[1]));
            if ($searchTerm !== '') {
                return $searchTerm;
            }
        }

        if (preg_match('@/place/([^/]+)@', $path, $m)) {
            return trim(str_replace('+', ' ', (string)$m[1]));
        }

        if (preg_match('/@(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)/', $path, $m)) {
            return $m[1] . ',' . $m[2];
        }

        $params = [];
        parse_str($queryString, $params);
        foreach (['q', 'query', 'destination', 'daddr'] as $key) {
            $v = trim((string)($params[$key] ?? ''));
            if ($v !== '') {
                if (filter_var($v, FILTER_VALIDATE_URL)) {
                    // Avoid recursive URL-as-query if nested links are present.
                    continue;
                }
                return $v;
            }
        }

        return '';
    }
}

if (!function_exists('canonicalize_google_maps_url')) {
    /**
     * Expand/normalize Google Maps URLs (including short links) to a final URL.
     *
     * @param string $url
     * @return string
     */
    function canonicalize_google_maps_url($url) {
        $url = trim((string)$url);
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        $host = strtolower((string)(parse_url($url, PHP_URL_HOST) ?? ''));
        if (!in_array($host, ['maps.app.goo.gl', 'goo.gl'], true)) {
            return $url;
        }

        $resolved = resolve_google_maps_short_url($url);
        return $resolved !== '' ? $resolved : $url;
    }
}

if (!function_exists('resolve_google_maps_short_url')) {
    /**
     * Resolve final URL for Google Maps short links using cURL if available.
     *
     * @param string $url
     * @return string
     */
    function resolve_google_maps_short_url($url) {
        $url = trim((string)$url);
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        // Preferred resolver: cURL with redirect-follow.
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                return '';
            }

            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 8,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; RipalMapsResolver/1.0)',
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);

            curl_exec($ch);
            $effective = (string)curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            $ok = curl_errno($ch) === 0 && $effective !== '';
            curl_close($ch);

            if ($ok && filter_var($effective, FILTER_VALIDATE_URL)) {
                $effectiveHost = strtolower((string)(parse_url($effective, PHP_URL_HOST) ?? ''));
                if ($effectiveHost !== '' && preg_match('/(^|\.)google\.[a-z.]+$/i', $effectiveHost)) {
                    return $effective;
                }
            }
        }

        // Fallback resolver when cURL is unavailable or blocked.
        if (!function_exists('get_headers')) {
            return '';
        }

        $current = $url;
        for ($i = 0; $i < 8; $i++) {
            $headers = @get_headers($current, true);
            if (!is_array($headers)) {
                break;
            }

            $location = $headers['Location'] ?? null;
            if ($location === null) {
                break;
            }

            if (is_array($location)) {
                $location = end($location);
            }

            $location = trim((string)$location);
            if ($location === '') {
                break;
            }

            if (!preg_match('#^https?://#i', $location)) {
                $parts = parse_url($current);
                $scheme = (string)($parts['scheme'] ?? 'https');
                $host = (string)($parts['host'] ?? '');
                if ($host === '') {
                    break;
                }
                $location = $scheme . '://' . $host . (strpos($location, '/') === 0 ? '' : '/') . $location;
            }

            $current = $location;
            if (!filter_var($current, FILTER_VALIDATE_URL)) {
                break;
            }
        }

        if (!filter_var($current, FILTER_VALIDATE_URL)) {
            return '';
        }

        $currentHost = strtolower((string)(parse_url($current, PHP_URL_HOST) ?? ''));
        if ($currentHost === '' || !preg_match('/(^|\.)google\.[a-z.]+$/i', $currentHost)) {
            return '';
        }

        return $current;
    }
}

if (!function_exists('build_google_maps_direction_href')) {
    /**
     * Build direction URL. If a Google Maps link is present, use it as-is.
     *
     * @param string $mapLink
     * @param string $fallbackDestination
     * @return string
     */
    function build_google_maps_direction_href($mapLink, $fallbackDestination = '') {
        $mapLink = trim((string)$mapLink);
        if ($mapLink !== '' && filter_var($mapLink, FILTER_VALIDATE_URL)) {
            return $mapLink;
        }

        $destination = trim((string)$fallbackDestination);
        if ($destination === '') {
            return '';
        }

        return 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode($destination);
    }
}

if (!function_exists('get_user_role_counts')) {
    /**
     * Return user totals and per-role counts.
     *
     * @return array
     */
    function get_user_role_counts() {
        $counts = [
            'total' => 0,
            'client' => 0,
            'worker' => 0,
            'employee' => 0,
            'admin' => 0,
        ];

        if (!db_connected() || !db_table_exists('users')) {
            return $counts;
        }

        $row = db_fetch('SELECT COUNT(*) AS c FROM users');
        $counts['total'] = (int)($row['c'] ?? 0);

        $rows = db_fetch_all('SELECT role, COUNT(*) AS c FROM users GROUP BY role');
        foreach ($rows as $r) {
            $role = (string)($r['role'] ?? '');
            if (array_key_exists($role, $counts)) {
                $counts[$role] = (int)($r['c'] ?? 0);
            }
        }

        return $counts;
    }
}

if (!function_exists('get_projects_basic')) {
    /**
     * Fetch project cards data.
     *
     * @param int $limit
     * @return array
     */
    function get_projects_basic($limit = 200) {
        if (!db_connected() || !db_table_exists('projects')) {
            return [];
        }

        $limit = max(1, min(500, (int)$limit));
        // Safe interpolation: LIMIT is strictly bounded and cast to integer above.
        // Build SQL conditionally so we don't fail if project_files table is missing.
        $sql = "SELECT p.id, p.name, p.status, COALESCE(p.progress,0) AS progress, p.budget, p.due, COALESCE(p.location,'') AS location, COALESCE(p.address,'') AS address, COALESCE(p.latitude, NULL) AS latitude, COALESCE(p.longitude, NULL) AS longitude, COALESCE(p.owner_name,'') AS owner_name, COALESCE(p.owner_contact,'') AS owner_contact";
        if (db_table_exists('project_files')) {
            $sql .= ", (SELECT pf.file_path FROM project_files pf WHERE pf.project_id = p.id AND pf.type IN ('JPG','JPEG','PNG','WEBP') ORDER BY pf.uploaded_at DESC LIMIT 1) AS cover_image";
        } else {
            $sql .= ", NULL AS cover_image";
        }

        $sql .= " FROM projects p ORDER BY p.id DESC LIMIT {$limit}";
        return db_fetch_all($sql);
    }
}

if (!function_exists('get_project_full_data')) {
    /**
     * Fetch project details with related rows.
     *
     * @param int $projectId
     * @return array|null
     */
    function get_project_full_data($projectId) {
        $projectId = (int)$projectId;
        if ($projectId <= 0 || !db_connected() || !db_table_exists('projects')) {
            return null;
        }

        $project = db_fetch('SELECT * FROM projects WHERE id = ? LIMIT 1', [$projectId]);
        if (!$project) {
            return null;
        }

        $project['owner'] = [
            'name' => $project['owner_name'] ?? '',
            'contact' => $project['owner_contact'] ?? '',
            'email' => $project['owner_email'] ?? '',
        ];

        $project['workers'] = db_table_exists('project_workers')
            ? db_fetch_all('SELECT worker_name, worker_role, worker_contact FROM project_workers WHERE project_id = ? ORDER BY id DESC', [$projectId])
            : [];
        $project['goods'] = db_table_exists('project_goods')
            ? db_fetch_all('SELECT id, sku, name, description, unit, quantity, unit_price, total_price, created_at FROM project_goods WHERE project_id = ? ORDER BY created_at DESC', [$projectId])
            : [];
        if (db_table_exists('project_drawings')) {
            $sessionUser = function_exists('current_user') ? current_user() : null;
            $sessionRole = is_array($sessionUser) ? strtolower((string)($sessionUser['role'] ?? '')) : '';

            if ($sessionRole === 'worker') {
                $project['drawings'] = db_fetch_all('SELECT * FROM project_drawings WHERE project_id = ? AND LOWER(status) = "approved" ORDER BY uploaded_at DESC', [$projectId]);
            } else {
                $project['drawings'] = db_fetch_all('SELECT * FROM project_drawings WHERE project_id = ? ORDER BY uploaded_at DESC', [$projectId]);
            }
        } else {
            $project['drawings'] = [];
        }
        $project['files'] = db_table_exists('project_files')
            ? db_fetch_all('SELECT * FROM project_files WHERE project_id = ? ORDER BY uploaded_at DESC', [$projectId])
            : [];
        $project['activities'] = db_table_exists('project_activity')
            ? db_fetch_all('SELECT * FROM project_activity WHERE project_id = ? ORDER BY created_at DESC LIMIT 30', [$projectId])
            : [];
        $project['milestones'] = db_table_exists('project_milestones')
            ? db_fetch_all('SELECT * FROM project_milestones WHERE project_id = ? ORDER BY target_date ASC', [$projectId])
            : [];

        return $project;
    }

    if (!function_exists('recalculate_project_progress')) {
        /**
         * Recalculate project progress using business rules and persist it.
         * Rules implemented:
         *  - Basic info (name + location/address/map_link): +2
         *  - Team members: >=5 and <=8 => +15, >8 => +20, partial for 1-4 (proportional)
         *  - Project files present: +13
         *  - Owner assigned: +1
         *  - Activity entry is created when progress changes
         *
         * @param int $projectId
         * @return int New progress value
         */
        function recalculate_project_progress($projectId) {
            $projectId = (int)$projectId;
            if ($projectId <= 0) return 0;

            if (!db_connected() || !db_table_exists('projects')) {
                return 0;
            }

            $proj = db_fetch('SELECT name, location, address, map_link, owner_name FROM projects WHERE id = ? LIMIT 1', [$projectId]);
            if (!$proj) return 0;

            $progress = 0;

            // Basic information
            $name = trim((string)($proj['name'] ?? ''));
            $location = trim((string)($proj['location'] ?? ''));
            $address = trim((string)($proj['address'] ?? ''));
            $mapLink = trim((string)($proj['map_link'] ?? ''));
            if ($name !== '' && ($location !== '' || $address !== '' || $mapLink !== '')) {
                $progress += 2;
            }

            // Team members
            if (db_table_exists('project_workers')) {
                $r = db_fetch('SELECT COUNT(*) AS c FROM project_workers WHERE project_id = ?', [$projectId]);
                $count = (int)($r['c'] ?? 0);
                if ($count >= 5 && $count <= 8) {
                    $progress += 15;
                } elseif ($count > 8) {
                    $progress += 20;
                } elseif ($count > 0) {
                    // small proportional credit for 1-4 members
                    $partial = (int)floor(($count / 5) * 15);
                    $progress += min(15, $partial);
                }
            }

            // Project files
            if (db_table_exists('project_files')) {
                $f = db_fetch('SELECT COUNT(*) AS c FROM project_files WHERE project_id = ?', [$projectId]);
                $filesCount = (int)($f['c'] ?? 0);
                if ($filesCount > 0) {
                    $progress += 13;
                }
            }

            // Owner assignment
            $owner = trim((string)($proj['owner_name'] ?? ''));
            if ($owner !== '') {
                $progress += 1;
            }

            $progress = max(0, min(100, (int)$progress));

            // Persist only if changed
            $cur = db_fetch('SELECT COALESCE(progress,0) AS progress FROM projects WHERE id = ? LIMIT 1', [$projectId]);
            $curVal = (int)($cur['progress'] ?? 0);
            if ($curVal !== $progress) {
                db_query('UPDATE projects SET progress = ? WHERE id = ?', [$progress, $projectId]);

                // Log activity
                if (session_status() === PHP_SESSION_NONE) @session_start();
                $actor = $_SESSION['user']['name'] ?? $_SESSION['user']['username'] ?? 'System';
                db_query('INSERT INTO project_activity (project_id, user, action, item, created_at) VALUES (?, ?, ?, ?, NOW())', [$projectId, $actor, 'progress updated', 'Auto-calculated to ' . $progress . '%']);
            }

            return $progress;
        }
    }
}

if (!function_exists('get_leave_dashboard_data')) {
    /**
     * Return leave stats and rows.
     *
     * @return array
     */
    function get_leave_dashboard_data() {
        $out = [
            'stats' => ['pending' => 0, 'approved' => 0, 'on_leave' => 0],
            'rows' => [],
        ];

        if (!db_connected() || !db_table_exists('leave_requests')) {
            return $out;
        }

        $statRows = db_fetch_all('SELECT status, COUNT(*) AS c FROM leave_requests GROUP BY status');
        foreach ($statRows as $r) {
            $k = (string)($r['status'] ?? '');
            if (isset($out['stats'][$k])) {
                $out['stats'][$k] = (int)($r['c'] ?? 0);
            }
        }

        $out['rows'] = db_fetch_all('SELECT lr.*, u.full_name, u.username, u.role FROM leave_requests lr LEFT JOIN users u ON u.id = lr.user_id ORDER BY lr.requested_at DESC LIMIT 100');
        return $out;
    }
}

?>
