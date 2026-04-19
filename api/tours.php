<?php
require_once __DIR__ . '/../app/Core/Bootstrap/init.php';

header('Content-Type: application/json; charset=utf-8');

require_login();
require_role('admin');

$db = get_db();
if (!($db instanceof PDO)) {
	http_response_code(500);
	echo json_encode(['error' => 'Database connection unavailable.']);
	exit;
}

if (!function_exists('tours_json')) {
	function tours_json(int $status, array $payload): void
	{
		http_response_code($status);
		echo json_encode($payload);
		exit;
	}
}

if (!function_exists('tours_stream_url')) {
	function tours_stream_url(int $projectFileId): string
	{
		if ($projectFileId <= 0) {
			return '';
		}
		return rtrim((string)base_path('dashboard/file_stream.php'), '/') . '?kind=file&id=' . $projectFileId;
	}
}

if (!function_exists('tours_safe_table_exists')) {
	function tours_safe_table_exists(string $table): bool
	{
		if (!function_exists('db_table_exists')) {
			return false;
		}
		try {
			return (bool)db_table_exists($table);
		} catch (Throwable $e) {
			return false;
		}
	}
}

if (!function_exists('tours_safe_column_exists')) {
	function tours_safe_column_exists(string $table, string $column): bool
	{
		if (!function_exists('db_column_exists')) {
			return false;
		}
		try {
			return (bool)db_column_exists($table, $column);
		} catch (Throwable $e) {
			return false;
		}
	}
}

$projectToursHasStartScene = tours_safe_column_exists('project_tours', 'start_scene_id');
$tourScenesHasInitialYaw = tours_safe_column_exists('tour_scenes', 'initial_yaw');
$tourScenesHasInitialPitch = tours_safe_column_exists('tour_scenes', 'initial_pitch');
$tourScenesHasInitialHfov = tours_safe_column_exists('tour_scenes', 'initial_hfov');
$tourScenesHasInitialFov = tours_safe_column_exists('tour_scenes', 'initial_fov');
$tourScenesHasSortOrder = tours_safe_column_exists('tour_scenes', 'sort_order');

$tourHotspotsHasHotspotType = tours_safe_column_exists('tour_hotspots', 'hotspot_type');
$tourHotspotsHasTitle = tours_safe_column_exists('tour_hotspots', 'title');
$tourHotspotsHasTargetScene = tours_safe_column_exists('tour_hotspots', 'target_scene_id');
$tourHotspotsHasContentHtml = tours_safe_column_exists('tour_hotspots', 'content_html');

$method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if (!tours_safe_table_exists('project_tours')
	|| !tours_safe_table_exists('tour_scenes')
	|| !tours_safe_table_exists('tour_hotspots')) {
	tours_json(503, ['error' => 'Tours schema is unavailable.']);
}

try {
	if ($method === 'GET') {
		$projectId = (int)($_GET['project_id'] ?? 0);
		$tourId = (int)($_GET['tour_id'] ?? 0);

		if ($projectId <= 0 || $tourId <= 0) {
			tours_json(422, ['error' => 'project_id and tour_id are required.']);
		}

		$tourStmt = $db->prepare('SELECT id, project_id, title FROM project_tours WHERE id = ? AND project_id = ? LIMIT 1');
		$tourStmt->execute([$tourId, $projectId]);
		$tour = $tourStmt->fetch(PDO::FETCH_ASSOC) ?: null;
		if (!$tour) {
			tours_json(404, ['error' => 'Tour not found.']);
		}

		$startSceneId = 0;
		if ($projectToursHasStartScene) {
			try {
				$startStmt = $db->prepare('SELECT start_scene_id FROM project_tours WHERE id = ? LIMIT 1');
				$startStmt->execute([$tourId]);
				$startSceneId = (int)(($startStmt->fetch(PDO::FETCH_ASSOC) ?: [])['start_scene_id'] ?? 0);
			} catch (Throwable $e) {
				$startSceneId = 0;
			}
		}

		$hasProjectFilesMediaType = tours_safe_column_exists('project_files', 'media_type');
		$mediaTypeSelect = $hasProjectFilesMediaType ? 'pf.media_type AS media_type' : 'NULL AS media_type';

		$sceneYawSelect = $tourScenesHasInitialYaw ? 's.initial_yaw AS initial_yaw' : '0 AS initial_yaw';
		$scenePitchSelect = $tourScenesHasInitialPitch ? 's.initial_pitch AS initial_pitch' : '0 AS initial_pitch';
		$sceneHfovSelect = $tourScenesHasInitialHfov
			? 's.initial_hfov AS initial_hfov'
			: ($tourScenesHasInitialFov ? 's.initial_fov AS initial_hfov' : '100 AS initial_hfov');
		$sceneSortSelect = $tourScenesHasSortOrder ? 's.sort_order AS sort_order' : '0 AS sort_order';

		$sceneSql = 'SELECT s.id, s.tour_id, s.project_file_id, s.name, ' . $sceneYawSelect . ', ' . $scenePitchSelect . ', ' . $sceneHfovSelect . ', ' . $sceneSortSelect . ', '
			. 'pf.name AS file_name, pf.type AS file_type, ' . $mediaTypeSelect . ' '
			. 'FROM tour_scenes s '
			. 'LEFT JOIN project_files pf ON pf.id = s.project_file_id '
			. 'WHERE s.tour_id = ? '
			. 'ORDER BY ' . ($tourScenesHasSortOrder ? 's.sort_order ASC, ' : '') . 's.id ASC';

		$sceneStmt = $db->prepare($sceneSql);
		$sceneStmt->execute([$tourId]);
		$sceneRows = $sceneStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

		$sceneIds = [];
		$scenes = [];
		foreach ($sceneRows as $sceneRow) {
			$sceneId = (int)($sceneRow['id'] ?? 0);
			if ($sceneId <= 0) {
				continue;
			}

			$sceneIds[] = $sceneId;
			$scenes[$sceneId] = [
				'id' => $sceneId,
				'name' => (string)($sceneRow['name'] ?? ('Scene #' . $sceneId)),
				'project_file_id' => (int)($sceneRow['project_file_id'] ?? 0),
				'image_url' => tours_stream_url((int)($sceneRow['project_file_id'] ?? 0)),
				'initial_yaw' => (float)($sceneRow['initial_yaw'] ?? 0),
				'initial_pitch' => (float)($sceneRow['initial_pitch'] ?? 0),
				'initial_hfov' => (float)($sceneRow['initial_hfov'] ?? 100),
				'sort_order' => (int)($sceneRow['sort_order'] ?? 0),
				'file_name' => (string)($sceneRow['file_name'] ?? ''),
				'media_type' => (string)($sceneRow['media_type'] ?? ''),
				'hotspots' => [],
			];
		}

		if (!empty($sceneIds)) {
			$inClause = implode(',', array_fill(0, count($sceneIds), '?'));
			$hotspotTargetSelect = $tourHotspotsHasTargetScene ? 'h.target_scene_id AS target_scene_id' : '0 AS target_scene_id';
			$hotspotTitleSelect = $tourHotspotsHasTitle ? 'h.title AS title' : 'NULL AS title';
			$hotspotTypeSelect = $tourHotspotsHasHotspotType ? 'h.hotspot_type AS hotspot_type' : "'navigation' AS hotspot_type";

			$hotspotSql = 'SELECT h.id, h.scene_id, h.yaw, h.pitch, ' . $hotspotTargetSelect . ', ' . $hotspotTitleSelect . ', ' . $hotspotTypeSelect . ', ts.name AS target_scene_name '
				. 'FROM tour_hotspots h '
				. 'LEFT JOIN tour_scenes ts ON ts.id = ' . ($tourHotspotsHasTargetScene ? 'h.target_scene_id' : '0') . ' '
				. 'WHERE h.scene_id IN (' . $inClause . ') '
				. 'ORDER BY h.id ASC';

			$hotspotStmt = $db->prepare($hotspotSql);
			$hotspotStmt->execute($sceneIds);
			$hotspotRows = $hotspotStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

			foreach ($hotspotRows as $row) {
				$sceneId = (int)($row['scene_id'] ?? 0);
				if (!isset($scenes[$sceneId])) {
					continue;
				}
				$scenes[$sceneId]['hotspots'][] = [
					'id' => (int)($row['id'] ?? 0),
					'scene_id' => $sceneId,
					'yaw' => (float)($row['yaw'] ?? 0),
					'pitch' => (float)($row['pitch'] ?? 0),
					'target_scene_id' => (int)($row['target_scene_id'] ?? 0),
					'target_scene_name' => (string)($row['target_scene_name'] ?? ''),
					'title' => (string)($row['title'] ?? ''),
					'hotspot_type' => (string)($row['hotspot_type'] ?? 'link'),
				];
			}
		}

		tours_json(200, [
			'success' => true,
			'tour' => [
				'id' => (int)$tour['id'],
				'project_id' => (int)$tour['project_id'],
				'title' => (string)($tour['title'] ?? ''),
				'start_scene_id' => $startSceneId,
			],
			'scenes' => array_values($scenes),
		]);
	}

	if ($method === 'POST') {
		require_csrf();

		$action = strtolower(trim((string)($_POST['action'] ?? '')));
		$projectId = (int)($_POST['project_id'] ?? 0);
		$tourId = (int)($_POST['tour_id'] ?? 0);

		if ($projectId <= 0 || $tourId <= 0) {
			tours_json(422, ['error' => 'project_id and tour_id are required.']);
		}

		$tourStmt = $db->prepare('SELECT id FROM project_tours WHERE id = ? AND project_id = ? LIMIT 1');
		$tourStmt->execute([$tourId, $projectId]);
		if (!$tourStmt->fetch(PDO::FETCH_ASSOC)) {
			tours_json(404, ['error' => 'Tour not found.']);
		}

		if ($action === 'create_hotspot') {
			$sceneId = (int)($_POST['scene_id'] ?? 0);
			$targetSceneId = (int)($_POST['target_scene_id'] ?? 0);
			$yaw = (float)($_POST['yaw'] ?? 0);
			$pitch = (float)($_POST['pitch'] ?? 0);
			$title = trim((string)($_POST['title'] ?? ''));

			if ($sceneId <= 0 || $targetSceneId <= 0) {
				tours_json(422, ['error' => 'scene_id and target_scene_id are required.']);
			}

			$sceneCheck = $db->prepare('SELECT id, name FROM tour_scenes WHERE id = ? AND tour_id = ? LIMIT 1');
			$sceneCheck->execute([$sceneId, $tourId]);
			$sourceScene = $sceneCheck->fetch(PDO::FETCH_ASSOC) ?: null;
			if (!$sourceScene) {
				tours_json(404, ['error' => 'Source scene not found in this tour.']);
			}

			$targetCheck = $db->prepare('SELECT id, name FROM tour_scenes WHERE id = ? AND tour_id = ? LIMIT 1');
			$targetCheck->execute([$targetSceneId, $tourId]);
			$targetScene = $targetCheck->fetch(PDO::FETCH_ASSOC) ?: null;
			if (!$targetScene) {
				tours_json(404, ['error' => 'Target scene not found in this tour.']);
			}

			if ($title === '') {
				$title = 'Go to ' . (string)($targetScene['name'] ?? ('Scene #' . $targetSceneId));
			}

			$insertColumns = ['scene_id', 'yaw', 'pitch'];
			$insertValues = [$sceneId, $yaw, $pitch];
			if ($tourHotspotsHasHotspotType) {
				$insertColumns[] = 'hotspot_type';
				$insertValues[] = 'navigation';
			}
			if ($tourHotspotsHasTargetScene) {
				$insertColumns[] = 'target_scene_id';
				$insertValues[] = $targetSceneId;
			}
			if ($tourHotspotsHasTitle) {
				$insertColumns[] = 'title';
				$insertValues[] = $title;
			}
			if ($tourHotspotsHasContentHtml) {
				$insertColumns[] = 'content_html';
				$insertValues[] = null;
			}

			$placeholders = implode(',', array_fill(0, count($insertColumns), '?'));
			$insertHotspot = $db->prepare('INSERT INTO tour_hotspots (' . implode(',', $insertColumns) . ') VALUES (' . $placeholders . ')');
			$insertHotspot->execute($insertValues);

			tours_json(200, [
				'success' => true,
				'hotspot' => [
					'id' => (int)$db->lastInsertId(),
					'scene_id' => $sceneId,
					'target_scene_id' => $targetSceneId,
					'target_scene_name' => (string)($targetScene['name'] ?? ''),
					'yaw' => $yaw,
					'pitch' => $pitch,
					'title' => $title,
					'hotspot_type' => 'navigation',
				],
			]);
		}

		if ($action === 'delete_hotspot') {
			$hotspotId = (int)($_POST['hotspot_id'] ?? 0);
			if ($hotspotId <= 0) {
				tours_json(422, ['error' => 'hotspot_id is required.']);
			}

			$findStmt = $db->prepare('SELECT h.id FROM tour_hotspots h INNER JOIN tour_scenes s ON s.id = h.scene_id WHERE h.id = ? AND s.tour_id = ? LIMIT 1');
			$findStmt->execute([$hotspotId, $tourId]);
			if (!$findStmt->fetch(PDO::FETCH_ASSOC)) {
				tours_json(404, ['error' => 'Hotspot not found.']);
			}

			$deleteStmt = $db->prepare('DELETE FROM tour_hotspots WHERE id = ? LIMIT 1');
			$deleteStmt->execute([$hotspotId]);

			tours_json(200, ['success' => true]);
		}

		if ($action === 'update_hotspot_position') {
			$hotspotId = (int)($_POST['hotspot_id'] ?? 0);
			$yaw = (float)($_POST['yaw'] ?? 0);
			$pitch = (float)($_POST['pitch'] ?? 0);

			if ($hotspotId <= 0) {
				tours_json(422, ['error' => 'hotspot_id is required.']);
			}

			$findStmt = $db->prepare('SELECT h.id FROM tour_hotspots h INNER JOIN tour_scenes s ON s.id = h.scene_id WHERE h.id = ? AND s.tour_id = ? LIMIT 1');
			$findStmt->execute([$hotspotId, $tourId]);
			if (!$findStmt->fetch(PDO::FETCH_ASSOC)) {
				tours_json(404, ['error' => 'Hotspot not found.']);
			}

			$updateStmt = $db->prepare('UPDATE tour_hotspots SET yaw = ?, pitch = ? WHERE id = ? LIMIT 1');
			$updateStmt->execute([$yaw, $pitch, $hotspotId]);

			tours_json(200, ['success' => true, 'hotspot_id' => $hotspotId, 'yaw' => $yaw, 'pitch' => $pitch]);
		}

		tours_json(400, ['error' => 'Unsupported action.']);
	}

	tours_json(405, ['error' => 'Unsupported method.']);
} catch (Throwable $e) {
	if (function_exists('app_log')) {
		app_log('error', 'Tours API failed', [
			'method' => $method,
			'exception' => $e->getMessage(),
		]);
	}
	tours_json(500, [
		'error' => 'Unexpected server error.',
		'details' => $e->getMessage(),
	]);
}
