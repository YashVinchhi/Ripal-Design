<?php

if (!function_exists('asset_manifest_data')) {
    function asset_manifest_data(): array
    {
        static $manifest = null;
        if (is_array($manifest)) {
            return $manifest;
        }

        $manifest = [];
        $projectRoot = defined('PROJECT_ROOT') ? rtrim((string)PROJECT_ROOT, '/\\') : rtrim((string)dirname(__DIR__, 3), '/\\');
        $manifestPath = $projectRoot . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'build-manifest.json';
        if (is_file($manifestPath)) {
            $decoded = json_decode((string)file_get_contents($manifestPath), true);
            if (is_array($decoded)) {
                $manifest = $decoded;
            }
        }

        return $manifest;
    }
}

if (!function_exists('asset_version_suffix_for_file')) {
    function asset_version_suffix_for_file(string $filePath): string
    {
        $path = trim($filePath);
        if ($path === '' || !is_file($path)) {
            return '';
        }

        $basename = basename($path);
        if ($basename === 'styles.css') {
            $manifest = asset_manifest_data();
            if (!empty($manifest['css']) && is_string($manifest['css'])) {
                $css = trim($manifest['css']);
                $query = parse_url($css, PHP_URL_QUERY);
                if (is_string($query) && $query !== '') {
                    return '?' . ltrim($query, '?');
                }
            }
        }

        return '?v=' . filemtime($path);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return $path;
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $relative = ltrim($path, '/\\');
        $projectRoot = defined('PROJECT_ROOT') ? rtrim((string)PROJECT_ROOT, '/\\') : rtrim((string)dirname(__DIR__, 3), '/\\');
        $absolute = $projectRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
        if (!is_file($absolute)) {
            return $path;
        }

        $basePath = defined('BASE_PATH') ? rtrim((string)BASE_PATH, '/') : '';
        $urlRelative = $relative;
        if (defined('PUBLIC_PATH_PREFIX') && PUBLIC_PATH_PREFIX === '' && str_starts_with($relative, 'public/')) {
            $urlRelative = substr($relative, strlen('public/'));
        }

        $url = ($basePath !== '' ? $basePath : '') . '/' . $urlRelative;
        $url = preg_replace('#/+#', '/', $url);
        return $url . asset_version_suffix_for_file($absolute);
    }
}

if (!function_exists('asset_url')) {
    function asset_url(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return $path;
        }

        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        $baseUrl = defined('APP_URL') && APP_URL !== '' ? rtrim((string)APP_URL, '/') : '';
        if ($baseUrl === '') {
            $scheme = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
            $host = (string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost');
            $baseUrl = $scheme . '://' . $host . (defined('BASE_PATH') ? rtrim((string)BASE_PATH, '/') : '');
        }

        $assetPath = asset($path);
        $basePath = defined('BASE_PATH') ? rtrim((string)BASE_PATH, '/') : '';
        if ($basePath !== '' && str_starts_with($assetPath, $basePath)) {
            $assetPath = substr($assetPath, strlen($basePath));
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($assetPath, '/');
    }
}
