<?php
if (!defined('PROJECT_ROOT')) { require_once dirname(__DIR__, 4) . '/app/Core/Bootstrap/init.php'; }
require_once PROJECT_ROOT . '/includes/slug.php';

require_login();
require_role('admin');

$db = get_db();
if (!($db instanceof PDO)) {
    http_response_code(500);
    echo 'Database connection unavailable.';
    exit;
}

function walkthroughs_schema_ready(PDO $db): bool
{
    if (function_exists('db_table_exists') && db_table_exists('project_3d_models')) {
        return true;
    }

    $migration = PROJECT_ROOT . '/sql/migrations/2026_05_20_create_project_3d_walkthroughs.sql';
    if (!is_file($migration)) {
        return false;
    }

    try {
        $sql = (string)file_get_contents($migration);
        $db->exec($sql);
    } catch (Throwable $e) {
        if (function_exists('app_log')) {
            app_log('error', 'Could not create walkthrough schema', ['exception' => $e->getMessage()]);
        }
        return false;
    }

    return function_exists('db_table_exists') ? db_table_exists('project_3d_models') : true;
}

function walkthrough_model_slug(PDO $db, int $projectId, string $title, int $ignoreId = 0): string
{
    $base = generate_slug($title);
    if ($base === '' || $base === 'project') {
        $base = 'walkthrough';
    }

    $slug = $base;
    $i = 2;
    while (true) {
        $sql = 'SELECT COUNT(*) FROM project_3d_models WHERE project_id = ? AND slug = ?';
        $params = [$projectId, $slug];
        if ($ignoreId > 0) {
            $sql .= ' AND id != ?';
            $params[] = $ignoreId;
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        if ((int)$stmt->fetchColumn() === 0) {
            return $slug;
        }
        $slug = $base . '-' . $i;
        $i++;
    }
}

function walkthrough_public_url(array $project, array $model): string
{
    $projectSlug = trim((string)($project['slug'] ?? ''));
    if ($projectSlug === '') {
        $projectSlug = (string)($project['id'] ?? 0);
    }
    return base_path('walkthroughs/' . rawurlencode($projectSlug) . '/' . rawurlencode((string)$model['slug']));
}

$schemaReady = walkthroughs_schema_ready($db);
$projectId = (int)($_GET['project_id'] ?? $_POST['project_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $schemaReady) {
    require_csrf();
    $action = strtolower(trim((string)($_POST['action'] ?? '')));

    try {
        if ($action === 'upload_model') {
            $title = trim((string)($_POST['title'] ?? ''));
            $description = trim((string)($_POST['description'] ?? ''));
            if ($projectId <= 0 || $title === '') {
                set_flash('Choose a project and enter a title.', 'error');
            } elseif (empty($_FILES['model_file']) || !is_array($_FILES['model_file'])) {
                set_flash('Choose a GLB model file.', 'error');
            } else {
                $project = get_project_by_id($projectId, 'id, name, slug');
                if (!$project) {
                    set_flash('Project was not found.', 'error');
                } else {
                    $stored = store_uploaded_file_array($_FILES['model_file'], [
                        'max_size' => 450 * 1024 * 1024,
                        'subdir' => '3d_models/project_' . $projectId,
                        'allowed_extensions' => ['glb'],
                        'allowed_mimes' => ['model/gltf-binary', 'application/octet-stream'],
                    ]);
                    if (empty($stored['ok'])) {
                        set_flash((string)($stored['error'] ?? 'Upload failed.'), 'error');
                    } else {
                        $slug = walkthrough_model_slug($db, $projectId, $title);
                        $userId = (int)(current_user()['id'] ?? 0);
                        $stmt = $db->prepare('INSERT INTO project_3d_models (project_id, uploaded_by, title, slug, description, original_name, stored_name, stored_path, mime, size, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)');
                        $stmt->execute([
                            $projectId,
                            $userId > 0 ? $userId : null,
                            $title,
                            $slug,
                            $description !== '' ? $description : null,
                            (string)($stored['original_name'] ?? ''),
                            (string)($stored['stored_name'] ?? ''),
                            (string)($stored['stored_path'] ?? ''),
                            (string)($stored['mime'] ?? ''),
                            (int)($stored['size'] ?? 0),
                        ]);
                        set_flash('3D walkthrough model uploaded.', 'success');
                    }
                }
            }
        } elseif ($action === 'delete_model') {
            $modelId = (int)($_POST['model_id'] ?? 0);
            if ($modelId > 0) {
                $stmt = $db->prepare('UPDATE project_3d_models SET is_active = 0 WHERE id = ? LIMIT 1');
                $stmt->execute([$modelId]);
                set_flash('Walkthrough model unpublished.', 'success');
            }
        }
    } catch (Throwable $e) {
        if (function_exists('app_log')) {
            app_log('error', 'Walkthrough admin action failed', ['action' => $action, 'exception' => $e->getMessage()]);
        }
        set_flash('Could not process the walkthrough request.', 'error');
    }

    $target = base_path('admin/walkthroughs.php' . ($projectId > 0 ? ('?project_id=' . $projectId) : ''));
    header('Location: ' . $target);
    exit;
}

$projects = db_fetch_all('SELECT id, name, slug FROM projects' . projects_soft_delete_sql('projects', ' WHERE ') . ' ORDER BY id DESC LIMIT 300');
$models = [];
$selectedProject = null;
if ($schemaReady && $projectId > 0) {
    $selectedProject = get_project_by_id($projectId, 'id, name, slug');
    $stmt = $db->prepare('SELECT * FROM project_3d_models WHERE project_id = ? ORDER BY is_active DESC, updated_at DESC, id DESC');
    $stmt->execute([$projectId]);
    $models = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
?>
<!doctype html>
<html lang="en" class="bg-canvas-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>3D Walkthroughs | Ripal Design</title>
    <?php $HEADER_MODE = 'dashboard'; require_once PROJECT_ROOT . '/Common/header.php'; ?>
</head>
<body class="bg-canvas-white font-sans text-foundation-grey min-h-screen">
<div class="min-h-screen flex flex-col">
    <header class="bg-foundation-grey text-white pt-20 md:pt-24 pb-8 md:pb-12 px-4 sm:px-6 lg:px-8 shadow-lg mb-8 md:mb-12 border-b-2 border-rajkot-rust">
        <div class="max-w-7xl mx-auto">
            <p class="text-[10px] uppercase tracking-[0.24em] text-gray-300 font-bold mb-2">GLB model viewer</p>
            <h1 class="text-3xl md:text-4xl font-serif font-bold">3D Walkthroughs</h1>
            <p class="text-gray-400 mt-2 text-sm">Upload Revit/Lumion exports as optimized GLB files and publish SEO-friendly walkthrough pages.</p>
        </div>
    </header>

    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-10 w-full">
        <div class="mb-6"><?php render_flash(); ?></div>

        <?php if (!$schemaReady): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 mb-6">
                Walkthrough tables are missing. Run <strong>sql/migrations/2026_05_20_create_project_3d_walkthroughs.sql</strong>.
            </div>
        <?php endif; ?>

        <section class="bg-white border border-gray-100 shadow-premium p-5 md:p-6 mb-6">
            <form method="get" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div class="md:col-span-3">
                    <label class="block text-[10px] uppercase tracking-widest font-bold text-gray-500 mb-2" for="project_id">Project</label>
                    <select id="project_id" name="project_id" class="w-full px-3 py-3 bg-gray-50 border border-gray-200">
                        <option value="0">Select project</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo (int)$project['id']; ?>" <?php echo (int)$project['id'] === $projectId ? 'selected' : ''; ?>>
                                <?php echo esc($project['name'] ?? ('Project #' . (int)$project['id'])); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="bg-foundation-grey hover:bg-rajkot-rust text-white px-4 py-3 text-[10px] font-bold uppercase tracking-widest">Load</button>
            </form>
        </section>

        <?php if ($schemaReady && $projectId > 0): ?>
            <section class="bg-white border border-gray-100 shadow-premium p-5 md:p-6 mb-6">
                <h2 class="text-xl font-serif font-bold mb-4">Upload GLB Model</h2>
                <form method="post" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                    <?php echo csrf_token_field(); ?>
                    <input type="hidden" name="action" value="upload_model">
                    <input type="hidden" name="project_id" value="<?php echo (int)$projectId; ?>">
                    <div class="md:col-span-2">
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-gray-500 mb-2">Title</label>
                        <input name="title" required class="w-full px-3 py-3 bg-gray-50 border border-gray-200" placeholder="Clubhouse walkthrough">
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-gray-500 mb-2">Description</label>
                        <input name="description" class="w-full px-3 py-3 bg-gray-50 border border-gray-200" placeholder="Optional">
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase tracking-widest font-bold text-gray-500 mb-2">GLB File</label>
                        <input type="file" name="model_file" accept=".glb,model/gltf-binary" required class="w-full px-3 py-2 bg-gray-50 border border-gray-200">
                    </div>
                    <button type="submit" class="bg-rajkot-rust hover:bg-red-700 text-white px-4 py-3 text-[10px] font-bold uppercase tracking-widest">Upload</button>
                </form>
            </section>

            <section class="bg-white border border-gray-100 shadow-premium p-5 md:p-6">
                <h2 class="text-xl font-serif font-bold mb-4">Published Models</h2>
                <?php if (empty($models)): ?>
                    <p class="text-sm text-gray-500">No GLB walkthrough models for this project yet.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($models as $model): ?>
                            <?php $publicUrl = $selectedProject ? walkthrough_public_url($selectedProject, $model) : ''; ?>
                            <div class="border border-gray-200 p-4 flex flex-col md:flex-row md:items-center md:justify-between gap-3 <?php echo (int)$model['is_active'] === 1 ? 'bg-white' : 'bg-gray-50 opacity-70'; ?>">
                                <div>
                                    <p class="font-bold"><?php echo esc($model['title'] ?? 'Untitled model'); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo esc($model['original_name'] ?? ''); ?> | <?php echo number_format(((int)($model['size'] ?? 0)) / 1048576, 2); ?> MB</p>
                                    <?php if ((int)$model['is_active'] === 1): ?>
                                        <p class="text-xs text-rajkot-rust break-all mt-1"><?php echo esc($publicUrl); ?></p>
                                    <?php else: ?>
                                        <p class="text-xs text-gray-500 mt-1">Unpublished</p>
                                    <?php endif; ?>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <?php if ((int)$model['is_active'] === 1): ?>
                                        <a href="<?php echo esc_attr($publicUrl); ?>" target="_blank" rel="noopener" class="bg-foundation-grey text-white px-4 py-2 text-[10px] uppercase tracking-widest no-underline">Open</a>
                                        <form method="post" onsubmit="return confirm('Unpublish this walkthrough model?');">
                                            <?php echo csrf_token_field(); ?>
                                            <input type="hidden" name="action" value="delete_model">
                                            <input type="hidden" name="project_id" value="<?php echo (int)$projectId; ?>">
                                            <input type="hidden" name="model_id" value="<?php echo (int)$model['id']; ?>">
                                            <button class="bg-red-600 text-white px-4 py-2 text-[10px] uppercase tracking-widest" type="submit">Unpublish</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>
    <?php if (!defined('HIDE_FOOTER_CTA')) define('HIDE_FOOTER_CTA', true); require_once PROJECT_ROOT . '/Common/footer.php'; ?>
</div>
</body>
</html>
