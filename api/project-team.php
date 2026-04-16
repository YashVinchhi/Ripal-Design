<?php
/**
 * WebMCP project team endpoint.
 *
 * Supports:
 * - GET /api/project-team.php?id={public_project_id_or_slug}
 */

require_once __DIR__ . '/_webmcp_common.php';

wmcp_require_https();
wmcp_handle_options(true, 'GET, OPTIONS');

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'GET') {
    wmcp_error('Method not allowed.', 405, true);
}

if (!db_connected() || !db_table_exists('projects')) {
    wmcp_error('Projects data source is unavailable.', 503, true);
}

$rawId = $_GET['id'] ?? $_GET['project_id'] ?? '';
if ($rawId === '') {
    wmcp_error('Project id is required.', 422, true);
}

$resolvedId = wmcp_resolve_project_identifier($rawId, get_db());
if ($resolvedId <= 0) {
    wmcp_error('Project not found.', 404, true);
}

$project = db_fetch('SELECT id, name FROM projects WHERE id = ? LIMIT 1', [$resolvedId]);
if (!$project) {
    wmcp_error('Project not found.', 404, true);
}

$members = [];

if (db_table_exists('project_assignments')) {
    if (db_table_exists('users')) {
        $rows = db_fetch_all(
            "SELECT
                pa.worker_id,
                pa.assigned_at,
                u.username,
                u.full_name,
                u.first_name,
                u.last_name,
                u.email,
                u.phone,
                u.role,
                u.status
             FROM project_assignments pa
             LEFT JOIN users u ON u.id = pa.worker_id
             WHERE pa.project_id = ?
             ORDER BY pa.assigned_at DESC, pa.id DESC",
            [$resolvedId]
        );
    } else {
        $rows = db_fetch_all(
            'SELECT worker_id, assigned_at FROM project_assignments WHERE project_id = ? ORDER BY assigned_at DESC, id DESC',
            [$resolvedId]
        );
    }

    foreach ($rows as $row) {
        $workerId = intval($row['worker_id'] ?? 0);
        $fullName = trim((string)($row['full_name'] ?? ''));
        $firstName = trim((string)($row['first_name'] ?? ''));
        $lastName = trim((string)($row['last_name'] ?? ''));
        $username = trim((string)($row['username'] ?? ''));

        $name = $fullName;
        if ($name === '' && ($firstName !== '' || $lastName !== '')) {
            $name = trim($firstName . ' ' . $lastName);
        }
        if ($name === '') {
            $name = $username !== '' ? $username : ('Worker #' . $workerId);
        }

        $members[] = [
            'member_id' => $workerId > 0 ? $workerId : null,
            'name' => $name,
            'role' => trim((string)($row['role'] ?? '')) !== '' ? (string)$row['role'] : 'worker',
            'member_type' => 'user',
            'email' => (string)($row['email'] ?? ''),
            'phone' => (string)($row['phone'] ?? ''),
            'status' => (string)($row['status'] ?? ''),
            'assigned_at' => (string)($row['assigned_at'] ?? ''),
            'source' => 'project_assignments',
        ];
    }
}

if (db_table_exists('project_workers')) {
    $workerRows = db_fetch_all(
        'SELECT id, worker_name, worker_role, worker_contact FROM project_workers WHERE project_id = ? ORDER BY id DESC',
        [$resolvedId]
    );

    foreach ($workerRows as $row) {
        $name = trim((string)($row['worker_name'] ?? ''));
        if ($name === '') {
            $name = 'Team Member';
        }

        $members[] = [
            'member_id' => null,
            'name' => $name,
            'role' => (string)($row['worker_role'] ?? 'worker'),
            'member_type' => 'manual_entry',
            'email' => '',
            'phone' => (string)($row['worker_contact'] ?? ''),
            'status' => '',
            'assigned_at' => '',
            'source' => 'project_workers',
        ];
    }
}

$title = (string)($project['name'] ?? '');

wmcp_output([
    'project_id' => wmcp_project_public_id($resolvedId),
    'slug' => wmcp_project_slug($title, $resolvedId),
    'title' => $title,
    'members' => $members,
    'total_members' => count($members),
], 200, true);

