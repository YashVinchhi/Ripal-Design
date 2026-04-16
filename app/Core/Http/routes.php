<?php

if (!function_exists('app_routes_bootstrap')) {
    /**
     * Load route definitions from routes/*.php once.
     *
     * @return void
     */
    function app_routes_bootstrap(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;

        $routeFiles = [
            'web' => PROJECT_ROOT . '/routes/web.php',
            'admin' => PROJECT_ROOT . '/routes/admin.php',
            'client' => PROJECT_ROOT . '/routes/client.php',
            'worker' => PROJECT_ROOT . '/routes/worker.php',
            'api' => PROJECT_ROOT . '/routes/api.php',
        ];

        $registry = [];
        foreach ($routeFiles as $group => $filePath) {
            if (!is_file($filePath)) {
                continue;
            }
            $routes = require $filePath;
            if (!is_array($routes)) {
                continue;
            }
            foreach ($routes as $name => $path) {
                $routeName = (string)$name;
                $routePath = (string)$path;
                if ($routeName === '' || $routePath === '') {
                    continue;
                }
                $registry[$routeName] = $routePath;
            }
        }

        $GLOBALS['__app_route_registry'] = $registry;
    }
}

if (!function_exists('app_routes_all')) {
    /**
     * @return array<string,string>
     */
    function app_routes_all(): array
    {
        app_routes_bootstrap();
        $registry = $GLOBALS['__app_route_registry'] ?? [];
        return is_array($registry) ? $registry : [];
    }
}

if (!function_exists('route_path')) {
    /**
     * Resolve a route name to an in-site path.
     *
     * @param string $name
     * @param array<string,string|int> $params
     * @return string
     */
    function route_path(string $name, array $params = []): string
    {
        $routes = app_routes_all();
        $path = (string)($routes[$name] ?? '');
        if ($path === '') {
            return '';
        }

        foreach ($params as $key => $value) {
            $token = '{' . $key . '}';
            $path = str_replace($token, rawurlencode((string)$value), $path);
        }

        $base = rtrim((string)(defined('BASE_PATH') ? BASE_PATH : ''), '/');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('route_url')) {
    /**
     * Resolve a route name to absolute URL.
     *
     * @param string $name
     * @param array<string,string|int> $params
     * @return string
     */
    function route_url(string $name, array $params = []): string
    {
        $path = route_path($name, $params);
        if ($path === '') {
            return '';
        }

        $baseUrl = rtrim((string)(defined('BASE_URL') ? BASE_URL : ''), '/');
        if ($baseUrl === '') {
            return $path;
        }
        return $baseUrl . '/' . ltrim($path, '/');
    }
}
