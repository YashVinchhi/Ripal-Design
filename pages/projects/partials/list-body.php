<?php

$user = current_user();
$role = is_array($user) ? strtolower(trim((string)($user['role'] ?? ''))) : '';
$userId = is_array($user) ? (int)($user['id'] ?? 0) : 0;

$projects = [];
if (db_connected()) {
    $projectsSoftDelete = function_exists('projects_soft_delete_sql') ? projects_soft_delete_sql('projects', ' AND ') : '';
    $projectsSoftDeleteP = function_exists('projects_soft_delete_sql') ? projects_soft_delete_sql('p', ' AND ') : '';

    if ($role === 'worker' && $userId > 0 && db_table_exists('project_assignments')) {
        $sql = <<<'SQL'
SELECT p.id, p.name, p.status, COALESCE(p.progress,0) AS progress, COALESCE(p.due,'1970-01-01') AS due, COALESCE(p.location,'') AS location
  FROM project_assignments pa
  INNER JOIN projects p ON p.id = pa.project_id
 WHERE pa.worker_id = ?
SQL;
        $sql .= $projectsSoftDeleteP;
        $sql .= ' ORDER BY pa.assigned_at DESC';
        $projects = db_fetch_all($sql, [$userId]);
    } elseif ($role === 'client' && $userId > 0) {
        $sql = <<<'SQL'
SELECT id, name, status, COALESCE(progress,0) AS progress, COALESCE(due,'1970-01-01') AS due, COALESCE(location,'') AS location
  FROM projects
 WHERE (created_by = ? OR client_id = ?)
SQL;
        $sql .= $projectsSoftDelete;
        $sql .= ' ORDER BY id DESC';
        $projects = db_fetch_all($sql, [$userId, $userId]);
    } else {
        $sql = <<<'SQL'
SELECT id, name, status, COALESCE(progress,0) AS progress, COALESCE(due,'1970-01-01') AS due, COALESCE(location,'') AS location
  FROM projects
SQL;
        $sql .= $projectsSoftDelete;
        $sql .= ' ORDER BY id DESC';
        $projects = db_fetch_all($sql);
    }
}

$rows = [];
foreach ($projects as $project) {
    $rows[] = [
        'id' => (int)($project['id'] ?? 0),
        'name' => (string)($project['name'] ?? 'Untitled'),
        'status' => (string)($project['status'] ?? 'ongoing'),
        'progress' => (int)($project['progress'] ?? 0) . '%',
        'due' => !empty($project['due']) && $project['due'] !== '1970-01-01' ? (string)$project['due'] : 'N/A',
        'location' => (string)($project['location'] ?? 'Location not set'),
    ];
}

$columns = [
    'id' => '#',
    'name' => 'Project',
    'status' => 'Status',
    'progress' => 'Progress',
    'due' => 'Due Date',
    'location' => 'Location',
];
?>
<main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="bg-white shadow-premium border border-gray-100 p-6 md:p-8 mb-8">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.3em] text-gray-400 mb-2">Unified Listing</p>
                <h1 class="text-3xl md:text-4xl font-serif font-bold text-foundation-grey">Projects</h1>
                <p class="text-sm text-gray-500 mt-2">A single list for the current role view.</p>
            </div>
            <?php render_if('projects.list', 'btn.add', static function (): void { ?>
                <a href="<?php echo htmlspecialchars(base_path('admin/new_projects.php'), ENT_QUOTES, 'UTF-8'); ?>" class="bg-foundation-grey hover:bg-rajkot-rust text-white px-4 py-2 text-[10px] font-bold uppercase tracking-[0.2em] no-underline">Add Project</a>
            <?php }); ?>
        </div>
    </div>

    <?php if (empty($rows)): ?>
        <div class="bg-white border border-gray-100 shadow-premium p-10 text-center text-gray-500">No projects found.</div>
    <?php else: ?>
        <?php render_table($columns, $rows, [
            'page' => 'projects.list',
            'caption' => 'Project registry',
            'actions' => [],
        ]); ?>
    <?php endif; ?>
</main>
