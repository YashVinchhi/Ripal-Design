<?php
require_once __DIR__ . '/../includes/init.php';

$db = function_exists('get_db') ? get_db() : null;
if (!($db instanceof PDO)) {
    fwrite(STDERR, "db=unavailable\n");
    exit(1);
}

$updates = [
    [
        'label' => 'worker_project_details',
        'sql' => "UPDATE notifications
                  SET deep_link = REPLACE(deep_link, '/dashboard/api/worker/project_details.php', '/worker/project_details.php')
                  WHERE deep_link LIKE '%/dashboard/api/worker/project_details.php%'"
    ],
    [
        'label' => 'client_files',
        'sql' => "UPDATE notifications
                  SET deep_link = REPLACE(deep_link, '/dashboard/api/client/client_files.php', '/client/client_files.php')
                  WHERE deep_link LIKE '%/dashboard/api/client/client_files.php%'"
    ],
    [
        'label' => 'dashboard_project_details',
        'sql' => "UPDATE notifications
                  SET deep_link = REPLACE(deep_link, '/dashboard/api/dashboard/project_details.php', '/dashboard/project_details.php')
                  WHERE deep_link LIKE '%/dashboard/api/dashboard/project_details.php%'"
    ],
    [
        'label' => 'dashboard_review_requests',
        'sql' => "UPDATE notifications
                  SET deep_link = REPLACE(deep_link, '/dashboard/api/dashboard/review_requests.php', '/dashboard/review_requests.php')
                  WHERE deep_link LIKE '%/dashboard/api/dashboard/review_requests.php%'"
    ],
];

foreach ($updates as $item) {
    $count = $db->exec($item['sql']);
    if ($count === false) {
        $err = $db->errorInfo();
        fwrite(STDERR, "update_{$item['label']}=failed " . ($err[2] ?? 'unknown') . "\n");
        exit(1);
    }
    echo 'update_' . $item['label'] . '=' . (int)$count . "\n";
}

echo "status=ok\n";
