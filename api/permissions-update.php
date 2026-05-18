<?php

require_once dirname(__DIR__) . '/app/Core/Bootstrap/init.php';
require_once dirname(__DIR__) . '/app/Core/Http/ApiValidator.php';

if (!function_exists('permissions_update_json')) {
    /**
     * Emit a JSON response and terminate.
     *
     * @param array<string, mixed> $payload Response body.
     * @param int $status HTTP status code.
     * @return void
     */
    function permissions_update_json(array $payload, int $status): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (!function_exists('permissions_update_cache_content')) {
    /**
     * Build the cached permissions PHP file content.
     *
     * @param array<int, array<string, mixed>> $rows Rows from ui_permissions.
     * @return string
     */
    function permissions_update_cache_content(array $rows): string
    {
        $cache = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $pageKey = (string)($row['page_key'] ?? '');
            $componentKey = (string)($row['component_key'] ?? '');
            $role = (string)($row['role'] ?? '');
            if ($pageKey === '' || $componentKey === '' || $role === '') {
                continue;
            }

            $cache[$role . '.' . $pageKey . '.' . $componentKey] = [
                'view' => (int)($row['can_view'] ?? 0),
                'edit' => (int)($row['can_edit'] ?? 0),
                'delete' => (int)($row['can_delete'] ?? 0),
            ];
        }

        ksort($cache);

        $content = "<?php\n\nreturn [\n";
        foreach ($cache as $key => $flags) {
            $content .= '    ' . var_export($key, true) . ' => ['
                . "'view' => " . (int)($flags['view'] ?? 0) . ', '
                . "'edit' => " . (int)($flags['edit'] ?? 0) . ', '
                . "'delete' => " . (int)($flags['delete'] ?? 0) . "],\n";
        }
        $content .= "];\n";

        return $content;
    }
}

require_login();
gate('settings', 'panel.permissions', 'edit');

$requestMethod = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
if ($requestMethod !== 'POST') {
    permissions_update_json(['error' => 'Method not allowed'], 405);
}

$requestValidator = \App\Core\Http\ApiValidator::fromJson()->required('permissions');
if (!$requestValidator->passes()) {
    permissions_update_json(['error' => 'Invalid JSON payload', 'details' => $requestValidator->errors()], 400);
}

$payload = $requestValidator->all();

$csrfToken = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if ($csrfToken === '' && isset($payload['csrf_token'])) {
    $csrfToken = (string)$payload['csrf_token'];
}
if (!csrf_validate($csrfToken)) {
    permissions_update_json(['error' => 'Invalid CSRF token'], 419);
}

if ($csrfToken !== '') {
    $_POST['csrf_token'] = $csrfToken;
}

require_csrf();

$entries = $payload['permissions'] ?? null;
if (!is_array($entries) || empty($entries)) {
    permissions_update_json(['error' => 'No permissions provided'], 400);
}

$validatedEntries = [];
foreach ($entries as $index => $entry) {
    $entryValidator = new \App\Core\Http\ApiValidator(is_array($entry) ? $entry : []);
    $entryValidator
        ->required('page_key')->string('page_key', 255)
        ->required('component_key')->string('component_key', 255)
        ->required('role')->inList('role', ['admin', 'employee', 'worker', 'client'])
        ->required('can_view')->integer('can_view', 0, 1)
        ->required('can_edit')->integer('can_edit', 0, 1)
        ->required('can_delete')->integer('can_delete', 0, 1);

    if (!$entryValidator->passes()) {
        permissions_update_json([
            'error' => 'Invalid permission entry',
            'index' => $index,
            'details' => $entryValidator->errors(),
        ], 422);
    }

    $validatedEntries[] = $entryValidator->validated();
}

$db = get_db();
if (!($db instanceof PDO)) {
    permissions_update_json(['error' => 'Database unavailable'], 503);
}

try {
    $db->beginTransaction();

    $statement = $db->prepare(
        'INSERT INTO ui_permissions (page_key, component_key, role, can_view, can_edit, can_delete)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            can_view = VALUES(can_view),
            can_edit = VALUES(can_edit),
            can_delete = VALUES(can_delete)'
    );

    $updated = 0;
    foreach ($validatedEntries as $entry) {
        $statement->execute([
            (string)$entry['page_key'],
            (string)$entry['component_key'],
            (string)$entry['role'],
            (int)$entry['can_view'],
            (int)$entry['can_edit'],
            (int)$entry['can_delete'],
        ]);
        $updated++;
    }

    if ($updated <= 0) {
        throw new InvalidArgumentException('No valid permissions provided');
    }

    $query = $db->query(
        'SELECT page_key, component_key, role, can_view, can_edit, can_delete
           FROM ui_permissions
       ORDER BY role ASC, page_key ASC, component_key ASC'
    );
    if (!$query instanceof PDOStatement) {
        throw new RuntimeException('Unable to refresh permissions cache');
    }
    $rows = $query->fetchAll(PDO::FETCH_ASSOC);

    $cacheFile = rtrim((string)APP_ROOT, '/\\') . '/app/Config/permissions_cache.php';
    $cacheDir = dirname($cacheFile);
    if (!is_dir($cacheDir) && !mkdir($cacheDir, 0775, true) && !is_dir($cacheDir)) {
        throw new RuntimeException('Unable to create cache directory');
    }

    $cacheContent = permissions_update_cache_content($rows);
    $tempFile = $cacheFile . '.tmp';
    if (file_put_contents($tempFile, $cacheContent, LOCK_EX) === false) {
        throw new RuntimeException('Unable to write permissions cache');
    }
    if (!@rename($tempFile, $cacheFile)) {
        @unlink($cacheFile);
        if (!@rename($tempFile, $cacheFile)) {
            @unlink($tempFile);
            throw new RuntimeException('Unable to publish permissions cache');
        }
    }

    $db->commit();

    if (function_exists('app_log')) {
        app_log('info', 'Permissions updated', [
            'updated' => $updated,
            'by_user' => function_exists('current_user') && is_array(current_user()) ? (int)(current_user()['id'] ?? 0) : 0,
            'request_id' => function_exists('request_id') ? request_id() : 'no-id',
        ]);
    }

    permissions_update_json(['success' => true, 'updated' => $updated], 200);
} catch (InvalidArgumentException $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    permissions_update_json(['error' => $exception->getMessage()], 400);
} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    permissions_update_json(['error' => 'Unable to update permissions'], 500);
}
