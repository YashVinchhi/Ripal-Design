<?php
namespace App\Shared;

class Router
{
    private string $publicDir;

    public function __construct(string $publicDir)
    {
        $this->publicDir = rtrim($publicDir, "\/\\");
    }

    public function dispatch(): void
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $uri = (string) strtok($uri, '?');
        $path = rawurldecode($uri);
        $path = preg_replace('#/+#', '/', $path);

        // Normalize and security checks
        if ($path === '' || $path === '/') {
            $this->includeSafe('views/home.php');
            return;
        }

        if (strpos($path, '..') !== false) {
            $this->log('Rejected traversal: ' . $path);
            $this->send404();
        }

        $rel = ltrim($path, '/');

        // If requests include the public directory segment (e.g. "/public/about_us.php"),
        // strip it so routing matches paths defined in `routes/*.php` and public files.
        $publicBase = basename($this->publicDir);
        if ($publicBase !== '' && stripos($rel, $publicBase . '/') === 0) {
            $rel = substr($rel, strlen($publicBase) + 1);
        }

        // If the requested resource is a real public file, serve it directly
        $candidate = $this->publicDir . DIRECTORY_SEPARATOR . $rel;
        if ($this->isFileUnderPublic($candidate)) {
            $this->includeSafe($rel);
            return;
        }

        // Try with .php appended (common in this project)
        if ($this->isFileUnderPublic($candidate . '.php')) {
            $this->includeSafe($rel . '.php');
            return;
        }

        // Centralized route table (pattern => handler)
        $routes = [
            '#^index\.php$#i' => fn($m) => $this->includeSafe('views/home.php'),
            '#^about(?:_us)?/?$#i' => fn($m) => $this->includeSafe('about_us.php'),
            '#^contact(?:_us)?/?$#i' => fn($m) => $this->includeSafe('contact_us.php'),
            '#^projects/?$#i' => fn($m) => $this->includeSafe('projects/index.php'),
            '#^projects/([0-9]+)$#i' => fn($m) => (function($id){ $_GET['id']=(int)$id; $this->includeSafe('projects/index.php'); })($m[1]),
            '#^projects/([A-Za-z0-9\-]+)/?$#i' => fn($m) => (function($slug){ $_GET['slug']=$slug; $this->includeSafe('project_view.php'); })($m[1]),
            '#^project_view(?:\.php)?$#i' => fn($m) => $this->includeSafe('project_view.php'),
            '#^walkthroughs/([A-Za-z0-9\-]+)/([A-Za-z0-9\-]+)/?$#i' => fn($m) => (function($p,$mod){ $_GET['project_slug']=$p; $_GET['model_slug']=$mod; $this->includeSafe('walkthrough.php'); })($m[1], $m[2]),
            '#^walkthrough-assets/([A-Za-z0-9\-]+)/([A-Za-z0-9\-]+)/model\.glb$#i' => fn($m) => (function($p,$mod){ $_GET['project_slug']=$p; $_GET['model_slug']=$mod; $this->includeSafe('walkthrough_asset.php'); })($m[1], $m[2]),
            '#^login(?:\.php)?$#i' => fn($m) => $this->includeSafe('login.php'),
            '#^logout(?:\.php)?$#i' => fn($m) => $this->includeSafe('logout.php'),
            '#^signup(?:\.php)?$#i' => fn($m) => $this->includeSafe('signup.php'),
            '#^forgot(?:\.php)?$#i' => fn($m) => $this->includeSafe('forgot.php'),
            '#^reset_password(?:\.php)?$#i' => fn($m) => $this->includeSafe('reset_password.php'),
            '#^send_reset_password(?:\.php)?$#i' => fn($m) => $this->includeSafe('send_reset_password.php'),
            '#^services(?:\.php)?$#i' => fn($m) => $this->includeSafe('services.php'),
            '#^about_us(?:\.php)?$#i' => fn($m) => $this->includeSafe('about_us.php'),
            '#^privacy(?:\.php)?$#i' => fn($m) => $this->includeSafe('privacy.php'),
            '#^terms(?:\.php)?$#i' => fn($m) => $this->includeSafe('terms.php'),
            '#^credits(?:\.php)?$#i' => fn($m) => $this->includeSafe('credits.php'),
            '#^projects/index(?:\.php)?$#i' => fn($m) => $this->includeSafe('projects/index.php'),
            '#^content_image(?:\.php)?$#i' => fn($m) => $this->includeSafe('content_image.php'),
            '#^_thumb(?:\.php)?$#i' => fn($m) => $this->includeSafe('_thumb.php'),
            '#^blog/(.*)$#i' => fn($m) => $this->includeSafe('blog/' . $m[1]),
        ];

        foreach ($routes as $pattern => $handler) {
            if (preg_match($pattern, $rel, $m)) {
                try {
                    $handler($m);
                } catch (\Throwable $e) {
                    $this->log('Route handler error for ' . $rel . ': ' . $e->getMessage());
                    $this->send404();
                }
                return;
            }
        }

        // Last-resort fallback: try to include common front files if present
        $fallbackFiles = ['project_view.php', 'project_details.php', 'contact_us.php'];
        foreach ($fallbackFiles as $f) {
            if ($this->isFileUnderPublic($this->publicDir . DIRECTORY_SEPARATOR . $f)) {
                $this->includeSafe($f);
                return;
            }
        }

        $this->log('No route matched: ' . $rel);
        $this->send404();
    }

    private function isFileUnderPublic(string $path): bool
    {
        $real = realpath($path);
        if ($real === false) {
            return false;
        }
        $publicReal = realpath($this->publicDir);
        return $publicReal !== false && strpos($real, $publicReal) === 0 && is_file($real);
    }

    private function includeSafe(string $relativePath): void
    {
        $full = $this->publicDir . DIRECTORY_SEPARATOR . $relativePath;
        $real = realpath($full);
        if ($real === false || strpos($real, realpath($this->publicDir)) !== 0) {
            $this->log('Attempt to include outside public: ' . $full);
            $this->send404();
        }

        // Is it a PHP file? include, otherwise let the webserver serve static files
        if (preg_match('/\.php$/i', $real)) {
            include $real;
            return;
        }

        // For non-PHP files, send a 404 so the webserver can handle static files directly.
        $this->send404();
    }

    private function send404(): void
    {
        http_response_code(404);
        $file = $this->publicDir . DIRECTORY_SEPARATOR . '404.php';
        if (is_file($file)) {
            include $file;
        } else {
            echo '404 Not Found';
        }
        exit;
    }

    private function log(string $message): void
    {
        $logDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $file = $logDir . DIRECTORY_SEPARATOR . 'router.log';
        $ts = date('[Y-m-d H:i:s] ');
        @error_log($ts . $message . "\n", 3, $file);
    }
}
