<?php

namespace App\Shared {
    use App\Core\Permissions\PermissionService;

    require_once dirname(__DIR__, 2) . '/includes/auth.php';
    require_once dirname(__DIR__, 2) . '/app/Core/Permissions/PermissionService.php';
    require_once dirname(__DIR__, 2) . '/app/Core/Permissions/gate.php';

    /**
     * Resolve a project-relative or absolute path against the application root.
     *
     * @param string $path File path to resolve.
     * @return string
     */
    function rd_resolve_path(string $path): string
    {
        if ($path === '') {
            return $path;
        }

        if (preg_match('~^[A-Za-z]:[\\/]~', $path) || str_starts_with($path, '/') || str_starts_with($path, '\\')) {
            return $path;
        }

        return dirname(__DIR__, 2) . '/' . ltrim($path, '/\\');
    }

    /**
     * Render a full page using the shared layout and view fragment.
     *
     * @param string $title Page title.
     * @param string $view_path Absolute or project-relative view path.
     * @param array<string, mixed> $data Data to extract into the view scope.
     * @return void
     */
    function render_page(string $title, string $view_path, array $data = []): void
    {
        static $permissionsLoaded = false;

        require_login();

        if (!$permissionsLoaded) {
            PermissionService::preload();
            $permissionsLoaded = true;
        }

        $pageTitle = $title;
        extract($data, EXTR_SKIP);

        require dirname(__DIR__, 2) . '/Common/layout/header.php';
        require rd_resolve_path($view_path);
        require dirname(__DIR__, 2) . '/Common/layout/footer.php';
    }

    /**
     * Render a reusable partial from Common/partials.
     *
     * @param string $partial_path Partial file name or relative path.
     * @param array<string, mixed> $data Data to extract into the partial scope.
     * @return void
     */
    function render_component(string $partial_path, array $data = []): void
    {
        $partial = dirname(__DIR__, 2) . '/Common/partials/' . ltrim($partial_path, '/\\');
        extract($data, EXTR_SKIP);

        if (!is_file($partial)) {
            return;
        }

        @include $partial;
    }

    /**
     * Render a semantic table using the shared table partial.
     *
     * @param array<string, string> $columns Column keys mapped to labels.
     * @param array<int, array<string, mixed>> $rows Data rows.
     * @param array<string, mixed> $options Table options.
     * @return void
     */
    function render_table(array $columns, array $rows, array $options = []): void
    {
        render_component('data-table.php', [
            'columns' => $columns,
            'rows' => $rows,
            'options' => $options,
        ]);
    }
}

namespace {
    if (!function_exists('rd_resolve_path')) {
        function rd_resolve_path(string $path): string
        {
            return \App\Shared\rd_resolve_path($path);
        }
    }

    if (!function_exists('render_page')) {
        function render_page(string $title, string $view_path, array $data = []): void
        {
            \App\Shared\render_page($title, $view_path, $data);
        }
    }

    if (!function_exists('render_component')) {
        function render_component(string $partial_path, array $data = []): void
        {
            \App\Shared\render_component($partial_path, $data);
        }
    }

    if (!function_exists('render_table')) {
        function render_table(array $columns, array $rows, array $options = []): void
        {
            \App\Shared\render_table($columns, $rows, $options);
        }
    }
}
