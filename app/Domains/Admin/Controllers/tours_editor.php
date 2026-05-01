<?php
if (!defined('PROJECT_ROOT')) { require_once dirname(__DIR__, 4) . '/app/Core/Bootstrap/init.php'; }
require_once PROJECT_ROOT . '/app/Core/Bootstrap/init.php';

require_login();
require_role('admin');

$db = get_db();
if (!($db instanceof PDO)) {
	http_response_code(500);
	echo 'Database connection unavailable.';
	exit;
}

$migrationMissing = !function_exists('db_table_exists')
	|| !db_table_exists('project_tours')
	|| !db_table_exists('tour_scenes')
	|| !db_table_exists('tour_hotspots');

if (!function_exists('is_panorama_candidate')) {
	function is_panorama_candidate(array $fileRow, bool $hasMediaType): bool
	{
		$mediaType = strtoupper(trim((string)($fileRow['media_type'] ?? '')));
		if ($hasMediaType && $mediaType === 'PANORAMA') {
			return $mediaType === 'PANORAMA';
		}

		if ($hasMediaType && in_array($mediaType, ['VIDEO', 'MODEL', 'DOCUMENT', 'AUDIO'], true)) {
			return false;
		}

		$name = strtolower((string)($fileRow['name'] ?? ''));
		$type = strtolower((string)($fileRow['type'] ?? ''));
		if (strpos($name, 'pano') !== false || strpos($name, 'panorama') !== false || strpos($name, '360') !== false) {
			return true;
		}

		return in_array($type, ['jpg', 'jpeg', 'png', 'webp'], true);
	}
}

$projectFilesHasMediaType = function_exists('db_column_exists') ? db_column_exists('project_files', 'media_type') : false;
$projectToursHasStartScene = function_exists('db_column_exists') ? db_column_exists('project_tours', 'start_scene_id') : false;

if (!$projectToursHasStartScene && !$migrationMissing) {
	try {
		$db->exec('ALTER TABLE project_tours ADD COLUMN start_scene_id BIGINT UNSIGNED NULL AFTER description');
		$db->exec('ALTER TABLE project_tours ADD KEY idx_project_tours_start_scene (start_scene_id)');
		$projectToursHasStartScene = function_exists('db_column_exists') ? db_column_exists('project_tours', 'start_scene_id') : true;
		if ($projectToursHasStartScene) {
			set_flash('Auto-migration applied: start scene support enabled.', 'success');
		}
	} catch (Throwable $e) {
		if (function_exists('app_log')) {
			app_log('warning', 'Could not auto-add start_scene_id column', ['exception' => $e->getMessage()]);
		}
	}
}

$projectId = (int)($_GET['project_id'] ?? $_POST['project_id'] ?? 0);
$tourId = (int)($_GET['tour_id'] ?? $_POST['tour_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$migrationMissing) {
	require_csrf();

	$action = strtolower(trim((string)($_POST['action'] ?? '')));
	try {
		if ($action === 'create_tour') {
			$title = trim((string)($_POST['title'] ?? ''));
			$description = trim((string)($_POST['description'] ?? ''));
			if ($projectId > 0 && $title !== '') {
				$userId = (int)(current_user()['id'] ?? 0);
				$stmt = $db->prepare('INSERT INTO project_tours (project_id, title, description, is_active, created_by, updated_by) VALUES (?, ?, ?, 1, ?, ?)');
				$stmt->execute([$projectId, $title, $description !== '' ? $description : null, $userId > 0 ? $userId : null, $userId > 0 ? $userId : null]);
				$tourId = (int)$db->lastInsertId();
				set_flash('Tour created successfully.', 'success');
			} else {
				set_flash('Project and title are required to create a tour.', 'error');
			}
		} elseif ($action === 'delete_tour') {
			if ($tourId > 0) {
				$stmt = $db->prepare('DELETE FROM project_tours WHERE id = ? LIMIT 1');
				$stmt->execute([$tourId]);
				$tourId = 0;
				set_flash('Tour deleted.', 'success');
			}
		} elseif ($action === 'create_scene') {
			$sceneName = trim((string)($_POST['scene_name'] ?? ''));
			$projectFileId = (int)($_POST['project_file_id'] ?? 0);
			if ($tourId > 0 && $sceneName !== '' && $projectFileId > 0) {
				$fileSql = $projectFilesHasMediaType
					? 'SELECT id, project_id, name, type, media_type FROM project_files WHERE id = ? AND project_id = ? LIMIT 1'
					: 'SELECT id, project_id, name, type FROM project_files WHERE id = ? AND project_id = ? LIMIT 1';
				$fileStmt = $db->prepare($fileSql);
				$fileStmt->execute([$projectFileId, $projectId]);
				$fileRow = $fileStmt->fetch(PDO::FETCH_ASSOC) ?: null;

				if (!$fileRow) {
					set_flash('Selected file was not found for this project.', 'error');
				} elseif (!is_panorama_candidate($fileRow, $projectFilesHasMediaType)) {
					set_flash('Please select a panoramic image file.', 'error');
				} else {
					$nextSort = 0;
					$sortStmt = $db->prepare('SELECT COALESCE(MAX(sort_order), -1) + 1 AS next_sort FROM tour_scenes WHERE tour_id = ?');
					$sortStmt->execute([$tourId]);
					$nextSortRow = $sortStmt->fetch(PDO::FETCH_ASSOC) ?: [];
					$nextSort = (int)($nextSortRow['next_sort'] ?? 0);

					$insertScene = $db->prepare('INSERT INTO tour_scenes (tour_id, project_file_id, name, initial_yaw, initial_pitch, initial_hfov, sort_order, is_active) VALUES (?, ?, ?, 0, 0, 100, ?, 1)');
					$insertScene->execute([$tourId, $projectFileId > 0 ? $projectFileId : null, $sceneName, $nextSort]);
					$newSceneId = (int)$db->lastInsertId();
					if ($projectToursHasStartScene && $newSceneId > 0) {
						$startCheckStmt = $db->prepare('SELECT start_scene_id FROM project_tours WHERE id = ? LIMIT 1');
						$startCheckStmt->execute([$tourId]);
						$currentStart = (int)(($startCheckStmt->fetch(PDO::FETCH_ASSOC) ?: [])['start_scene_id'] ?? 0);
						if ($currentStart <= 0) {
							$startSetStmt = $db->prepare('UPDATE project_tours SET start_scene_id = ? WHERE id = ? LIMIT 1');
							$startSetStmt->execute([$newSceneId, $tourId]);
						}
					}
					set_flash('Scene added to tour.', 'success');
				}
			} else {
				set_flash('Tour, scene name and panoramic file are required.', 'error');
			}
		} elseif ($action === 'delete_scene') {
			$sceneId = (int)($_POST['scene_id'] ?? 0);
			if ($sceneId > 0 && $tourId > 0) {
				$stmt = $db->prepare('DELETE FROM tour_scenes WHERE id = ? AND tour_id = ? LIMIT 1');
				$stmt->execute([$sceneId, $tourId]);
				if ($projectToursHasStartScene) {
					$clearStartStmt = $db->prepare('UPDATE project_tours SET start_scene_id = NULL WHERE id = ? AND start_scene_id = ? LIMIT 1');
					$clearStartStmt->execute([$tourId, $sceneId]);
				}
				set_flash('Scene deleted.', 'success');
			}
		} elseif ($action === 'set_start_scene') {
			$sceneId = (int)($_POST['scene_id'] ?? 0);
			if (!$projectToursHasStartScene) {
				set_flash('Start scene requires migration sql/migrations/2026_04_18_add_tour_start_scene.sql.', 'error');
			} elseif ($sceneId <= 0 || $tourId <= 0) {
				set_flash('Tour and scene are required.', 'error');
			} else {
				$sceneCheckStmt = $db->prepare('SELECT id FROM tour_scenes WHERE id = ? AND tour_id = ? LIMIT 1');
				$sceneCheckStmt->execute([$sceneId, $tourId]);
				$sceneExists = $sceneCheckStmt->fetch(PDO::FETCH_ASSOC) ?: null;
				if (!$sceneExists) {
					set_flash('Scene was not found in this tour.', 'error');
				} else {
					$startStmt = $db->prepare('UPDATE project_tours SET start_scene_id = ? WHERE id = ? LIMIT 1');
					$startStmt->execute([$sceneId, $tourId]);
					set_flash('Start scene updated.', 'success');
				}
			}
		}
	} catch (Throwable $e) {
		if (function_exists('app_log')) {
			app_log('error', 'Tours editor action failed', ['action' => $action, 'exception' => $e->getMessage()]);
		}
		set_flash('Could not process the requested action.', 'error');
	}

	$qs = [];
	if ($projectId > 0) {
		$qs['project_id'] = (string)$projectId;
	}
	if ($tourId > 0) {
		$qs['tour_id'] = (string)$tourId;
	}
	header('Location: ' . $_SERVER['PHP_SELF'] . (empty($qs) ? '' : ('?' . http_build_query($qs))));
	exit;
}

$projects = [];
$tours = [];
$scenes = [];
$projectFiles = [];
$selectedTour = null;

try {
	$projects = $db->query('SELECT id, name FROM projects ORDER BY id DESC LIMIT 250')->fetchAll(PDO::FETCH_ASSOC) ?: [];

	if ($projectId > 0) {
		$startSceneSelect = $projectToursHasStartScene ? 't.start_scene_id AS start_scene_id' : 'NULL AS start_scene_id';
		$tourStmt = $db->prepare('SELECT t.id, t.project_id, t.title, t.description, t.is_active, t.updated_at, ' . $startSceneSelect . ', COUNT(s.id) AS scene_count
			FROM project_tours t
			LEFT JOIN tour_scenes s ON s.tour_id = t.id AND s.is_active = 1
			WHERE t.project_id = ?
			GROUP BY t.id
			ORDER BY t.updated_at DESC, t.id DESC');
		$tourStmt->execute([$projectId]);
		$tours = $tourStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

		$fileSql = $projectFilesHasMediaType
			? 'SELECT id, name, type, media_type, uploaded_at FROM project_files WHERE project_id = ? ORDER BY uploaded_at DESC, id DESC LIMIT 400'
			: 'SELECT id, name, type, uploaded_at FROM project_files WHERE project_id = ? ORDER BY uploaded_at DESC, id DESC LIMIT 400';
		$fileStmt = $db->prepare($fileSql);
		$fileStmt->execute([$projectId]);
		$allProjectFiles = $fileStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
		$projectFiles = [];
		foreach ($allProjectFiles as $fileRow) {
			if (is_panorama_candidate($fileRow, $projectFilesHasMediaType)) {
				$projectFiles[] = $fileRow;
			}
		}

		if ($tourId <= 0 && !empty($tours)) {
			$tourId = (int)($tours[0]['id'] ?? 0);
		}
	}

	if ($tourId > 0) {
		$selectedTourStmt = $db->prepare('SELECT * FROM project_tours WHERE id = ? LIMIT 1');
		$selectedTourStmt->execute([$tourId]);
		$selectedTour = $selectedTourStmt->fetch(PDO::FETCH_ASSOC) ?: null;

		if ($selectedTour) {
			$sceneStmt = $db->prepare('SELECT s.*, pf.name AS file_name
				FROM tour_scenes s
				LEFT JOIN project_files pf ON pf.id = s.project_file_id
				WHERE s.tour_id = ?
				ORDER BY s.sort_order ASC, s.id ASC');
			$sceneStmt->execute([$tourId]);
			$scenes = $sceneStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
		}
	}
} catch (Throwable $e) {
	if (function_exists('app_log')) {
		app_log('error', 'Tours editor load failed', ['exception' => $e->getMessage()]);
	}
	set_flash('Could not load tours editor data.', 'error');
}
?>
<!DOCTYPE html>
<html lang="en" class="bg-canvas-white">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Tours Editor | Ripal Design</title>
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css">
	<style>
		#tourPanoramaCanvas {
			width: 100%;
			height: 420px;
			background: #f3f4f6;
			border: 1px solid #e5e7eb;
		}
		.tour-link-hotspot {
			width: 18px;
			height: 18px;
			background: #ef4444;
			border: 2px solid #fff;
			border-radius: 50%;
			box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.2);
			cursor: pointer;
		}
		.tour-link-hotspot-label {
			background: rgba(15, 23, 42, 0.9);
			color: #fff;
			font-size: 11px;
			padding: 5px 8px;
			border-radius: 4px;
			white-space: nowrap;
			transform: translate(-50%, -130%);
		}
	</style>
	<?php $HEADER_MODE = 'dashboard'; require_once PROJECT_ROOT . '/Common/header.php'; ?>
</head>
<body class="bg-canvas-white font-sans text-foundation-grey min-h-screen">
	<div class="min-h-screen flex flex-col">
		<header class="bg-foundation-grey text-white pt-20 md:pt-24 pb-8 md:pb-12 px-4 sm:px-6 lg:px-8 shadow-lg mb-8 md:mb-12 border-b-2 border-rajkot-rust">
			<div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-4">
				<div>
					<h1 class="text-3xl md:text-4xl font-serif font-bold">Tours Editor</h1>
					<p class="text-gray-400 mt-2 text-xs uppercase tracking-widest font-bold opacity-70">Panorama scenes and hotspot authoring</p>
				</div>
				<div class="flex flex-wrap gap-2">
					<a href="<?php echo esc_attr(base_path('admin/file_viewer.php' . ($projectId > 0 ? ('?project_id=' . $projectId) : ''))); ?>" class="bg-rajkot-rust hover:bg-red-700 text-white px-6 py-3 text-[10px] font-bold uppercase tracking-widest no-underline">
						Open File Viewer
					</a>
					<?php if ($tourId > 0): ?>
						<a href="<?php echo esc_attr(base_path('public/tour.php?tour_id=' . (int)$tourId)); ?>" target="_blank" rel="noopener" class="bg-foundation-grey border border-white/20 hover:bg-white/10 text-white px-6 py-3 text-[10px] font-bold uppercase tracking-widest no-underline">
							Open Public Tour
						</a>
					<?php endif; ?>
				</div>
			</div>
		</header>

		<main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-10">
			<div class="mb-6"><?php render_flash(); ?></div>

			<?php if ($migrationMissing): ?>
				<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
					Tours tables are missing. Run <strong>sql/migrations/2026_04_18_add_tours_hotspots.sql</strong> and reload this page.
				</div>
			<?php endif; ?>

			<?php if (!$projectToursHasStartScene): ?>
				<div class="bg-amber-50 border border-amber-200 text-amber-800 px-4 py-3 rounded mb-6">
					Start scene persistence is disabled until you run <strong>sql/migrations/2026_04_18_add_tour_start_scene.sql</strong>.
				</div>
			<?php endif; ?>

			<section class="bg-white border border-gray-100 shadow-premium p-5 md:p-6 mb-6">
				<form method="get" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
					<div class="md:col-span-2">
						<label for="project_id" class="block text-[10px] uppercase tracking-widest font-bold text-gray-500 mb-2">Project</label>
						<select id="project_id" name="project_id" class="w-full px-3 py-3 bg-gray-50 border border-gray-200">
							<option value="0">Select a project</option>
							<?php foreach ($projects as $project): ?>
								<option value="<?php echo (int)($project['id'] ?? 0); ?>" <?php echo (int)($project['id'] ?? 0) === $projectId ? 'selected' : ''; ?>>
									<?php echo htmlspecialchars((string)($project['name'] ?? ('Project #' . (int)($project['id'] ?? 0)))); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div>
						<label for="tour_id" class="block text-[10px] uppercase tracking-widest font-bold text-gray-500 mb-2">Tour</label>
						<select id="tour_id" name="tour_id" class="w-full px-3 py-3 bg-gray-50 border border-gray-200">
							<option value="0">Select tour</option>
							<?php foreach ($tours as $tour): ?>
								<option value="<?php echo (int)($tour['id'] ?? 0); ?>" <?php echo (int)($tour['id'] ?? 0) === $tourId ? 'selected' : ''; ?>>
									<?php echo htmlspecialchars((string)($tour['title'] ?? ('Tour #' . (int)($tour['id'] ?? 0)))); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div>
						<button type="submit" class="w-full bg-foundation-grey hover:bg-rajkot-rust text-white px-4 py-3 text-[10px] font-bold uppercase tracking-widest">Load</button>
					</div>
				</form>
			</section>

			<?php if ($projectId > 0 && !$migrationMissing): ?>
				<section class="bg-white border border-gray-100 shadow-premium p-5 md:p-6 mb-6">
					<h2 class="text-lg font-serif font-bold mb-4">Create Tour</h2>
					<form method="post" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
						<?php echo csrf_token_field(); ?>
						<input type="hidden" name="action" value="create_tour">
						<input type="hidden" name="project_id" value="<?php echo (int)$projectId; ?>">
						<input type="hidden" name="tour_id" value="<?php echo (int)$tourId; ?>">
						<div class="md:col-span-2">
							<label class="block text-[10px] uppercase tracking-widest font-bold text-gray-500 mb-2">Title</label>
							<input type="text" name="title" required class="w-full px-3 py-3 bg-gray-50 border border-gray-200" placeholder="Main Walkthrough">
						</div>
						<div>
							<label class="block text-[10px] uppercase tracking-widest font-bold text-gray-500 mb-2">Description</label>
							<input type="text" name="description" class="w-full px-3 py-3 bg-gray-50 border border-gray-200" placeholder="Optional">
						</div>
						<div>
							<button type="submit" class="w-full bg-rajkot-rust hover:bg-red-700 text-white px-4 py-3 text-[10px] font-bold uppercase tracking-widest">Create</button>
						</div>
					</form>
				</section>
			<?php endif; ?>

			<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
				<section class="bg-white border border-gray-100 shadow-premium p-5 md:p-6">
					<h2 class="text-lg font-serif font-bold mb-4">Tours</h2>
					<?php if (empty($tours)): ?>
						<p class="text-sm text-gray-500">No tours found for this project.</p>
					<?php else: ?>
						<div class="space-y-3">
							<?php foreach ($tours as $tour): ?>
								<div class="border <?php echo (int)($tour['id'] ?? 0) === $tourId ? 'border-rajkot-rust bg-red-50' : 'border-gray-200 bg-white'; ?> p-3 rounded">
									<div class="flex items-center justify-between gap-3">
										<div>
											<p class="text-sm font-bold text-foundation-grey"><?php echo htmlspecialchars((string)($tour['title'] ?? 'Untitled Tour')); ?></p>
											<p class="text-[11px] text-gray-500">Scenes: <?php echo (int)($tour['scene_count'] ?? 0); ?><?php if ((int)($tour['start_scene_id'] ?? 0) > 0): ?> | Start scene ID: <?php echo (int)($tour['start_scene_id'] ?? 0); ?><?php endif; ?></p>
										</div>
										<div class="flex gap-2">
											<a class="text-[10px] uppercase tracking-widest px-3 py-2 bg-foundation-grey text-white no-underline" href="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?' . http_build_query(['project_id' => $projectId, 'tour_id' => (int)($tour['id'] ?? 0)])); ?>">Select</a>
											<form method="post" onsubmit="return confirm('Delete this tour and all scenes/hotspots?');">
												<?php echo csrf_token_field(); ?>
												<input type="hidden" name="action" value="delete_tour">
												<input type="hidden" name="project_id" value="<?php echo (int)$projectId; ?>">
												<input type="hidden" name="tour_id" value="<?php echo (int)($tour['id'] ?? 0); ?>">
												<button type="submit" class="text-[10px] uppercase tracking-widest px-3 py-2 bg-red-600 text-white">Delete</button>
											</form>
										</div>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</section>

				<section class="bg-white border border-gray-100 shadow-premium p-5 md:p-6">
					<h2 class="text-lg font-serif font-bold mb-4">Scenes<?php echo $selectedTour ? ' for ' . htmlspecialchars((string)($selectedTour['title'] ?? 'Tour')) : ''; ?></h2>

					<?php if ($selectedTour): ?>
						<form method="post" class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-5">
							<?php echo csrf_token_field(); ?>
							<input type="hidden" name="action" value="create_scene">
							<input type="hidden" name="project_id" value="<?php echo (int)$projectId; ?>">
							<input type="hidden" name="tour_id" value="<?php echo (int)$tourId; ?>">
							<div>
								<label class="block text-[10px] uppercase tracking-widest font-bold text-gray-500 mb-2">Scene Name</label>
								<input type="text" name="scene_name" required class="w-full px-3 py-3 bg-gray-50 border border-gray-200" placeholder="Entrance View">
							</div>
							<div>
								<label class="block text-[10px] uppercase tracking-widest font-bold text-gray-500 mb-2">Panorama File</label>
								<select name="project_file_id" class="w-full px-3 py-3 bg-gray-50 border border-gray-200">
									<option value="0">Select panoramic image</option>
									<?php foreach ($projectFiles as $file): ?>
										<option value="<?php echo (int)($file['id'] ?? 0); ?>"><?php echo htmlspecialchars((string)($file['name'] ?? ('File #' . (int)($file['id'] ?? 0)))); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
							<div class="flex items-end">
								<button type="submit" class="w-full bg-approval-green hover:bg-green-700 text-white px-4 py-3 text-[10px] font-bold uppercase tracking-widest">Add Scene</button>
							</div>
						</form>

						<?php if (empty($scenes)): ?>
							<p class="text-sm text-gray-500">No scenes yet. Add the first scene to start hotspot authoring.</p>
						<?php else: ?>
							<div class="space-y-3">
								<?php foreach ($scenes as $scene): ?>
									<div class="border border-gray-200 p-3 rounded bg-white">
										<div class="flex items-center justify-between gap-3">
											<div>
												<p class="text-sm font-bold text-foundation-grey"><?php echo htmlspecialchars((string)($scene['name'] ?? 'Untitled Scene')); ?></p>
												<p class="text-[11px] text-gray-500">
													Sort: <?php echo (int)($scene['sort_order'] ?? 0); ?>
													<?php if (!empty($scene['file_name'])): ?>
														| File: <?php echo htmlspecialchars((string)$scene['file_name']); ?>
													<?php endif; ?>
													<?php if ((int)($selectedTour['start_scene_id'] ?? 0) === (int)($scene['id'] ?? 0)): ?>
														| <span class="text-approval-green font-bold">Start Scene</span>
													<?php endif; ?>
												</p>
											</div>
											<div class="flex items-center gap-2">
												<?php if ($projectToursHasStartScene): ?>
													<form method="post">
														<?php echo csrf_token_field(); ?>
														<input type="hidden" name="action" value="set_start_scene">
														<input type="hidden" name="project_id" value="<?php echo (int)$projectId; ?>">
														<input type="hidden" name="tour_id" value="<?php echo (int)$tourId; ?>">
														<input type="hidden" name="scene_id" value="<?php echo (int)($scene['id'] ?? 0); ?>">
														<button type="submit" class="text-[10px] uppercase tracking-widest px-3 py-2 <?php echo (int)($selectedTour['start_scene_id'] ?? 0) === (int)($scene['id'] ?? 0) ? 'bg-approval-green text-white' : 'bg-foundation-grey text-white'; ?>"><?php echo (int)($selectedTour['start_scene_id'] ?? 0) === (int)($scene['id'] ?? 0) ? 'Start' : 'Set Start'; ?></button>
													</form>
												<?php endif; ?>
												<form method="post" onsubmit="return confirm('Delete this scene and its hotspots?');">
													<?php echo csrf_token_field(); ?>
													<input type="hidden" name="action" value="delete_scene">
													<input type="hidden" name="project_id" value="<?php echo (int)$projectId; ?>">
													<input type="hidden" name="tour_id" value="<?php echo (int)$tourId; ?>">
													<input type="hidden" name="scene_id" value="<?php echo (int)($scene['id'] ?? 0); ?>">
													<button type="submit" class="text-[10px] uppercase tracking-widest px-3 py-2 bg-red-600 text-white">Delete</button>
												</form>
											</div>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
						<?php if (empty($projectFiles)): ?>
							<p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 px-3 py-2 mt-4">No panorama files were detected. Upload a panoramic image in File Viewer first.</p>
						<?php endif; ?>
					<?php else: ?>
						<p class="text-sm text-gray-500">Select a tour to manage scenes.</p>
					<?php endif; ?>
				</section>
			</div>

			<?php if ($selectedTour && !empty($scenes)): ?>
				<section class="bg-white border border-gray-100 shadow-premium p-5 md:p-6 mt-6">
					<div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
						<div>
							<h2 class="text-lg font-serif font-bold">Hotspot Linker</h2>
							<p class="text-sm text-gray-600">Select main scene, right-click on panorama to place a hotspot, then choose target scene. Click hotspots to traverse linked images.</p>
						</div>
						<div class="w-full md:w-80">
							<label for="mainSceneSelect" class="block text-[10px] uppercase tracking-widest font-bold text-gray-500 mb-2">Main Scene</label>
							<select id="mainSceneSelect" class="w-full px-3 py-3 bg-gray-50 border border-gray-200"></select>
						</div>
					</div>

					<div id="tourPanoramaCanvas"></div>
					<p id="tourEditorStatus" class="text-xs text-gray-500 mt-2">Loading tour scenes...</p>

					<div class="grid grid-cols-1 md:grid-cols-3 gap-3 mt-4">
						<div class="md:col-span-2">
							<p class="text-[10px] uppercase tracking-widest font-bold text-gray-500 mb-2">Pending hotspot point</p>
							<div id="pendingHotspotInfo" class="text-sm bg-gray-50 border border-gray-200 px-3 py-3">Right-click a point in panorama to capture yaw/pitch.</div>
						</div>
						<div>
							<label for="targetSceneSelect" class="block text-[10px] uppercase tracking-widest font-bold text-gray-500 mb-2">Target Scene</label>
							<select id="targetSceneSelect" class="w-full px-3 py-3 bg-gray-50 border border-gray-200 mb-2"></select>
							<input id="hotspotTitleInput" type="text" class="w-full px-3 py-3 bg-gray-50 border border-gray-200 mb-2" placeholder="Optional hotspot label">
							<button id="createHotspotBtn" type="button" class="w-full bg-rajkot-rust hover:bg-red-700 text-white px-4 py-3 text-[10px] font-bold uppercase tracking-widest">Create Link</button>
						</div>
					</div>

					<div class="mt-5">
						<h3 class="text-sm font-bold text-foundation-grey mb-2">Hotspots in current scene</h3>
						<div id="currentHotspotsList" class="space-y-2 text-sm text-gray-600">No hotspots yet.</div>
					</div>
				</section>
			<?php endif; ?>
		</main>

		<?php if (!defined('HIDE_FOOTER_CTA')) define('HIDE_FOOTER_CTA', true); require_once PROJECT_ROOT . '/Common/footer.php'; ?>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>
	<?php if ($selectedTour && !empty($scenes)): ?>
	<script>
	(function () {
		const projectId = <?php echo (int)$projectId; ?>;
		const tourId = <?php echo (int)$tourId; ?>;
		const csrfToken = <?php echo json_encode(csrf_token()); ?>;
		const apiUrl = <?php echo json_encode(base_path('api/tours.php')); ?>;

		const statusEl = document.getElementById('tourEditorStatus');
		const mainSceneSelect = document.getElementById('mainSceneSelect');
		const targetSceneSelect = document.getElementById('targetSceneSelect');
		const createHotspotBtn = document.getElementById('createHotspotBtn');
		const pendingInfo = document.getElementById('pendingHotspotInfo');
		const hotspotTitleInput = document.getElementById('hotspotTitleInput');
		const hotspotsList = document.getElementById('currentHotspotsList');

		const state = {
			scenes: [],
			scenesById: new Map(),
			tour: null,
			currentSceneId: 0,
			pendingPoint: null,
			dragging: null,
			dragBlockClickUntil: 0,
			viewer: null,
		};

		function setStatus(message, isError) {
			statusEl.textContent = message;
			statusEl.className = isError ? 'text-xs text-red-600 mt-2' : 'text-xs text-gray-500 mt-2';
		}

		function toBody(action, fields) {
			const body = new URLSearchParams();
			body.set('csrf_token', csrfToken);
			body.set('action', action);
			body.set('project_id', String(projectId));
			body.set('tour_id', String(tourId));
			Object.keys(fields || {}).forEach((key) => {
				body.set(key, String(fields[key]));
			});
			return body.toString();
		}

		function createHotspotTooltip(hotSpotDiv, args) {
			hotSpotDiv.classList.add('tour-link-hotspot');
			hotSpotDiv.setAttribute('data-hotspot-id', String(args && args.hotspotId ? args.hotspotId : '0'));
			const label = document.createElement('div');
			label.className = 'tour-link-hotspot-label';
			label.textContent = args && args.label ? args.label : 'Open';
			hotSpotDiv.appendChild(label);
		}

		function findCurrentHotspotById(hotspotId) {
			const scene = state.scenesById.get(Number(state.currentSceneId || 0));
			if (!scene || !Array.isArray(scene.hotspots)) {
				return null;
			}
			for (let i = 0; i < scene.hotspots.length; i += 1) {
				if (Number(scene.hotspots[i].id || 0) === Number(hotspotId || 0)) {
					return scene.hotspots[i];
				}
			}
			return null;
		}

		function renderSceneDropdowns() {
			mainSceneSelect.innerHTML = '';
			targetSceneSelect.innerHTML = '';

			state.scenes.forEach((scene) => {
				const optionMain = document.createElement('option');
				optionMain.value = String(scene.id);
				optionMain.textContent = scene.name;
				mainSceneSelect.appendChild(optionMain);

				const optionTarget = document.createElement('option');
				optionTarget.value = String(scene.id);
				optionTarget.textContent = scene.name;
				targetSceneSelect.appendChild(optionTarget);
			});
		}

		function renderHotspotList(sceneId) {
			const scene = state.scenesById.get(sceneId);
			if (!scene || !Array.isArray(scene.hotspots) || scene.hotspots.length === 0) {
				hotspotsList.innerHTML = 'No hotspots yet.';
				return;
			}

			hotspotsList.innerHTML = '';
			scene.hotspots.forEach((hotspot) => {
				const row = document.createElement('div');
				row.className = 'border border-gray-200 rounded px-3 py-2 flex items-center justify-between gap-3';

				const left = document.createElement('div');
				left.className = 'text-xs text-gray-600';
				left.textContent = (hotspot.title || 'Go to linked scene') + ' | yaw: ' + Number(hotspot.yaw).toFixed(2) + ' pitch: ' + Number(hotspot.pitch).toFixed(2);

				const controls = document.createElement('div');
				controls.className = 'flex items-center gap-2';

				const goBtn = document.createElement('button');
				goBtn.type = 'button';
				goBtn.className = 'text-[10px] uppercase tracking-widest px-2 py-1 bg-foundation-grey text-white';
				goBtn.textContent = 'Go';
				goBtn.addEventListener('click', () => loadScene(Number(hotspot.target_scene_id || 0)));

				const delBtn = document.createElement('button');
				delBtn.type = 'button';
				delBtn.className = 'text-[10px] uppercase tracking-widest px-2 py-1 bg-red-600 text-white';
				delBtn.textContent = 'Delete';
				delBtn.addEventListener('click', async () => {
					if (!confirm('Delete this hotspot link?')) {
						return;
					}
					setStatus('Deleting hotspot...', false);
					const resp = await fetch(apiUrl, {
						method: 'POST',
						headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
						body: toBody('delete_hotspot', { hotspot_id: hotspot.id }),
					});
					const data = await resp.json().catch(() => ({}));
					if (!resp.ok || !data.success) {
						setStatus(data.error || 'Failed to delete hotspot.', true);
						return;
					}
					await loadTourData(state.currentSceneId);
					setStatus('Hotspot deleted.', false);
				});

				controls.appendChild(goBtn);
				controls.appendChild(delBtn);

				row.appendChild(left);
				row.appendChild(controls);
				hotspotsList.appendChild(row);
			});
		}

		function buildViewerHotspots(scene) {
			const source = Array.isArray(scene.hotspots) ? scene.hotspots : [];
			return source.map((hotspot) => {
				const targetId = Number(hotspot.target_scene_id || 0);
				return {
					id: 'tour_hotspot_' + String(hotspot.id),
					pitch: Number(hotspot.pitch || 0),
					yaw: Number(hotspot.yaw || 0),
					type: 'info',
					text: hotspot.title || 'Open',
					createTooltipFunc: createHotspotTooltip,
					createTooltipArgs: { label: hotspot.title || 'Open', hotspotId: hotspot.id },
					clickHandlerFunc: function () {
						if (Date.now() < Number(state.dragBlockClickUntil || 0)) {
							return;
						}
						loadScene(targetId);
					},
				};
			});
		}

		function bindHotspotDragging() {
			const canvas = document.getElementById('tourPanoramaCanvas');
			if (!canvas) {
				return;
			}

			const hotspots = canvas.querySelectorAll('.tour-link-hotspot');
			hotspots.forEach((hotspotEl) => {
				hotspotEl.onpointerdown = function (event) {
					if (!state.viewer || !state.currentSceneId) {
						return;
					}
					event.preventDefault();
					event.stopPropagation();

					const hotspotId = Number(hotspotEl.getAttribute('data-hotspot-id') || 0);
					if (!hotspotId) {
						return;
					}

					state.dragging = {
						hotspotId,
						coords: null,
					};
					setStatus('Dragging hotspot... release mouse to save new position.', false);
				};
			});

			window.onpointermove = function (event) {
				if (!state.dragging || !state.viewer) {
					return;
				}
				let coords = null;
				try {
					coords = state.viewer.mouseEventToCoords(event);
				} catch (e) {
					coords = null;
				}
				if (!coords || coords.length < 2) {
					return;
				}
				state.dragging.coords = {
					pitch: Number(coords[0]),
					yaw: Number(coords[1]),
				};
				setStatus('Dragging hotspot... yaw: ' + state.dragging.coords.yaw.toFixed(4) + ', pitch: ' + state.dragging.coords.pitch.toFixed(4), false);
			};

			window.onpointerup = async function () {
				if (!state.dragging) {
					return;
				}
				const drag = state.dragging;
				state.dragging = null;

				if (!drag.coords) {
					setStatus('Hotspot drag cancelled.', false);
					return;
				}

				setStatus('Saving hotspot position...', false);
				const resp = await fetch(apiUrl, {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
					body: toBody('update_hotspot_position', {
						hotspot_id: drag.hotspotId,
						yaw: drag.coords.yaw,
						pitch: drag.coords.pitch,
					}),
				});

				const data = await resp.json().catch(() => ({}));
				if (!resp.ok || !data.success) {
					setStatus(data.error || 'Failed to move hotspot.', true);
					return;
				}

				const movedHotspot = findCurrentHotspotById(drag.hotspotId);
				if (movedHotspot) {
					movedHotspot.yaw = drag.coords.yaw;
					movedHotspot.pitch = drag.coords.pitch;
				}

				state.dragBlockClickUntil = Date.now() + 300;
				loadScene(state.currentSceneId);
				setStatus('Hotspot moved.', false);
			};
		}

		function bindContextMenuCapture() {
			const canvas = document.getElementById('tourPanoramaCanvas');
			canvas.oncontextmenu = function (event) {
				event.preventDefault();
				if (!state.viewer || !state.currentSceneId) {
					return false;
				}

				let coords = null;
				try {
					coords = state.viewer.mouseEventToCoords(event);
				} catch (e) {
					coords = null;
				}

				if (!coords || coords.length < 2) {
					setStatus('Could not read hotspot coordinates from this click.', true);
					return false;
				}

				state.pendingPoint = {
					pitch: Number(coords[0]),
					yaw: Number(coords[1]),
				};
				pendingInfo.textContent = 'Captured point -> yaw: ' + state.pendingPoint.yaw.toFixed(4) + ', pitch: ' + state.pendingPoint.pitch.toFixed(4);
				setStatus('Point captured. Select target scene and click Create Link.', false);
				return false;
			};
		}

		function loadScene(sceneId) {
			const scene = state.scenesById.get(Number(sceneId));
			if (!scene) {
				setStatus('Scene not found.', true);
				return;
			}
			if (!scene.image_url) {
				setStatus('Selected scene has no panorama file.', true);
				return;
			}

			state.currentSceneId = scene.id;
			mainSceneSelect.value = String(scene.id);

			if (state.viewer && typeof state.viewer.destroy === 'function') {
				state.viewer.destroy();
			}

			state.viewer = pannellum.viewer('tourPanoramaCanvas', {
				type: 'equirectangular',
				panorama: scene.image_url,
				autoLoad: true,
				showZoomCtrl: true,
				showFullscreenCtrl: true,
				hfov: Number(scene.initial_hfov || 100),
				pitch: Number(scene.initial_pitch || 0),
				yaw: Number(scene.initial_yaw || 0),
				hotSpots: buildViewerHotspots(scene),
			});

			bindContextMenuCapture();
			bindHotspotDragging();
			renderHotspotList(scene.id);
			setStatus('Loaded scene: ' + scene.name + '. Right-click to add a hotspot. Drag existing hotspots to move.', false);
		}

		async function loadTourData(preferredSceneId) {
			setStatus('Loading scenes and links...', false);
			const url = apiUrl + '?project_id=' + encodeURIComponent(String(projectId)) + '&tour_id=' + encodeURIComponent(String(tourId));
			const resp = await fetch(url, { credentials: 'same-origin' });
			const data = await resp.json().catch(() => ({}));
			if (!resp.ok || !data.success || !Array.isArray(data.scenes)) {
				setStatus(data.error || 'Could not load tour data.', true);
				return;
			}

			state.scenes = data.scenes;
			state.tour = data.tour || null;
			state.scenesById = new Map();
			state.scenes.forEach((scene) => {
				state.scenesById.set(Number(scene.id), scene);
			});

			renderSceneDropdowns();

			const tourStartSceneId = Number((state.tour && state.tour.start_scene_id) || 0);
			const initialSceneId = Number(preferredSceneId || 0) > 0 && state.scenesById.has(Number(preferredSceneId))
				? Number(preferredSceneId)
				: (tourStartSceneId > 0 && state.scenesById.has(tourStartSceneId)
					? tourStartSceneId
					: Number((state.scenes[0] && state.scenes[0].id) || 0));

			if (!initialSceneId) {
				setStatus('This tour has no scenes.', true);
				return;
			}

			if (Number(targetSceneSelect.value || 0) === 0) {
				targetSceneSelect.value = String(initialSceneId);
			}

			loadScene(initialSceneId);
		}

		mainSceneSelect.addEventListener('change', function () {
			const nextSceneId = Number(mainSceneSelect.value || 0);
			loadScene(nextSceneId);
		});

		createHotspotBtn.addEventListener('click', async function () {
			if (!state.currentSceneId) {
				setStatus('Load a scene first.', true);
				return;
			}
			if (!state.pendingPoint) {
				setStatus('Right-click on panorama first to capture hotspot location.', true);
				return;
			}

			const targetSceneId = Number(targetSceneSelect.value || 0);
			if (!targetSceneId || !state.scenesById.has(targetSceneId)) {
				setStatus('Choose a valid target scene.', true);
				return;
			}

			setStatus('Saving hotspot link...', false);
			const resp = await fetch(apiUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: toBody('create_hotspot', {
					scene_id: state.currentSceneId,
					target_scene_id: targetSceneId,
					yaw: state.pendingPoint.yaw,
					pitch: state.pendingPoint.pitch,
					title: hotspotTitleInput.value.trim(),
				}),
			});

			const data = await resp.json().catch(() => ({}));
			if (!resp.ok || !data.success) {
				setStatus(data.error || 'Failed to create hotspot.', true);
				return;
			}

			state.pendingPoint = null;
			hotspotTitleInput.value = '';
			pendingInfo.textContent = 'Right-click a point in panorama to capture yaw/pitch.';
			await loadTourData(state.currentSceneId);
			setStatus('Hotspot link created.', false);
		});

		loadTourData(0);
	})();
	</script>
	<?php endif; ?>
</body>
</html>
