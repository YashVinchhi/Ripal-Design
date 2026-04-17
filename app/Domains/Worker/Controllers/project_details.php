<?php
if (!defined('PROJECT_ROOT')) { require_once dirname(__DIR__, 4) . '/app/Core/Bootstrap/init.php'; }
require_once PROJECT_ROOT . '/app/Core/Bootstrap/init.php';
require_login();
$sessionUser = $_SESSION['user'] ?? null;
$sessionRole = is_array($sessionUser) ? (string)($sessionUser['role'] ?? '') : '';
$isWorkerReadOnly = ($sessionRole === 'worker') || (isset($_GET['readonly']) && $_GET['readonly'] === '1');

$projectId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$project = get_project_full_data($projectId);

if (!$project) {
    if (function_exists('show_404')) {
        show_404();
    }
    http_response_code(404);
    echo '404 Project Not Found';
    exit;
}

// Normalize DB rows to template keys.
$project['address'] = $project['address'] ?? ($project['location'] ?? '');
$project['area'] = $project['area'] ?? 'N/A';
$project['budget'] = isset($project['budget']) ? ('â‚¹ ' . number_format((float)$project['budget'], 0, '.', ',')) : 'â‚¹ 0';
$project['lat'] = $project['latitude'] ?? null;
$project['lng'] = $project['longitude'] ?? null;

$projectAddress = trim((string)($project['address'] ?? ''));
if ($projectAddress === '') {
    $projectAddress = trim((string)($project['location'] ?? ''));
}
if ($projectAddress === '') {
    $projectAddress = 'Address not available';
}

$projectMapQuery = '';
if (!empty($project['lat']) && !empty($project['lng'])) {
    $projectMapQuery = (string)$project['lat'] . ',' . (string)$project['lng'];
} elseif ($projectAddress !== 'Address not available') {
    $projectMapQuery = $projectAddress;
}

$projectMapLink = trim((string)($project['map_link'] ?? ''));
$projectMapEmbedSrc = build_google_maps_embed_src($projectMapLink !== '' ? $projectMapLink : $projectMapQuery);
$projectDirectionHref = build_google_maps_direction_href($projectMapLink, $projectMapQuery);

$projectLocationFromMapLink = '';
if ($projectMapLink !== '') {
    $projectLocationFromMapLink = trim((string)normalize_google_maps_embed_query($projectMapLink));
}
$mapLinkLooksLikeCoordinates = (bool)preg_match('/^\s*-?\d+(?:\.\d+)?\s*,\s*-?\d+(?:\.\d+)?\s*$/', $projectLocationFromMapLink);
$projectLocationSentenceValue = ($projectAddress !== 'Address not available' ? $projectAddress : '');
if ($projectLocationFromMapLink !== '' && !$mapLinkLooksLikeCoordinates) {
    $projectLocationSentenceValue = $projectLocationFromMapLink;
}

$project['workers'] = array_map(function($w) {
    return [
        'role' => $w['worker_role'] ?? 'Worker',
        'name' => $w['worker_name'] ?? '',
        'contact' => $w['worker_contact'] ?? '',
    ];
}, $project['workers'] ?? []);

$project['goods'] = array_map(function($g) {
    $qty = (int)($g['quantity'] ?? 0);
    $unit = (string)($g['unit'] ?? 'pcs');
    return [
        'item' => $g['name'] ?? '',
        'qty' => trim($qty . ' ' . $unit),
        'status' => 'Ordered',
    ];
}, $project['goods'] ?? []);

$project['drawings'] = array_map(function($d) {
    $path = strtolower((string)($d['file_path'] ?? ''));
    $type = (substr($path, -4) === '.pdf') ? 'pdf' : 'img';
    return [
        'id' => (int)($d['id'] ?? 0),
        'title' => $d['name'] ?? '',
        'type' => $type,
        'date' => $d['uploaded_at'] ?? date('Y-m-d H:i:s'),
        'status' => strtolower((string)($d['status'] ?? 'under_review')),
        'file_path' => (string)($d['file_path'] ?? ''),
        'uploaded_by' => (string)($d['uploaded_by'] ?? ''),
    ];
}, $project['drawings'] ?? []);

function worker_file_url($path) {
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    $normalized = str_replace('\\', '/', $path);
    $normalized = ltrim($normalized, '/');
    return rtrim((string)BASE_PATH, '/') . '/' . $normalized;
}

$selectedResourceKind = strtolower(trim((string)($_GET['resource_kind'] ?? ($_GET['kind'] ?? ''))));
$selectedResourceId = (int)($_GET['resource_id'] ?? ($_GET['id'] ?? 0));
if ($selectedResourceId <= 0 && isset($_GET['drawing_id'])) {
    $selectedResourceKind = 'drawing';
    $selectedResourceId = (int)$_GET['drawing_id'];
}
if ($selectedResourceId <= 0 && isset($_GET['file_id'])) {
    $selectedResourceKind = 'file';
    $selectedResourceId = (int)$_GET['file_id'];
}

$projectResources = [];

foreach ($project['files'] ?? [] as $f) {
    $typeHint = strtolower(trim((string)($f['type'] ?? '')));
    $path = (string)($f['file_path'] ?? ($f['storage_path'] ?? ''));
    $ext = strtolower((string)pathinfo((string)$path, PATHINFO_EXTENSION));
    if ($ext === '' && preg_match('/^[a-z0-9]{2,8}$/', $typeHint)) {
        $ext = $typeHint;
    }
    $projectResources[] = [
        'kind' => 'file',
        'id' => (int)($f['id'] ?? 0),
        'title' => (string)($f['name'] ?? 'File'),
        'date' => (string)($f['uploaded_at'] ?? date('Y-m-d H:i:s')),
        'status' => 'uploaded',
        'version' => '',
        'file_path' => $path,
        'file_url' => worker_file_url($path),
        'ext' => $ext,
        'viewer_url' => rtrim((string)BASE_PATH, '/') . '/worker/project_details.php?id=' . (int)$projectId . '&resource_kind=file&resource_id=' . (int)($f['id'] ?? 0) . '#drawings',
    ];
}

foreach ($project['drawings'] ?? [] as $d) {
    $path = (string)($d['file_path'] ?? '');
    $projectResources[] = [
        'kind' => 'drawing',
        'id' => (int)($d['id'] ?? 0),
        'title' => (string)($d['title'] ?? 'Drawing'),
        'date' => (string)($d['date'] ?? date('Y-m-d H:i:s')),
        'status' => (string)($d['status'] ?? 'under_review'),
        'version' => (string)($d['version'] ?? 'v1'),
        'file_path' => $path,
        'file_url' => worker_file_url($path),
        'ext' => strtolower((string)pathinfo((string)(($path !== '') ? $path : ($d['title'] ?? '')), PATHINFO_EXTENSION)),
        'viewer_url' => rtrim((string)BASE_PATH, '/') . '/worker/project_details.php?id=' . (int)$projectId . '&resource_kind=drawing&resource_id=' . (int)($d['id'] ?? 0) . '#drawings',
    ];
}

usort($projectResources, function ($a, $b) {
    $ta = strtotime((string)($a['date'] ?? '')) ?: 0;
    $tb = strtotime((string)($b['date'] ?? '')) ?: 0;
    return $tb <=> $ta;
});

$activeResource = !empty($projectResources) ? $projectResources[0] : null;
foreach ($projectResources as $resource) {
    if ($selectedResourceId > 0 && (int)$resource['id'] === $selectedResourceId && ($selectedResourceKind === '' || $selectedResourceKind === (string)$resource['kind'])) {
        $activeResource = $resource;
        break;
    }
}

$workerPreviewUrl = (string)($activeResource['file_url'] ?? '');
$workerFileName = (string)($activeResource['title'] ?? 'N/A');
$workerStatus = (string)($activeResource['status'] ?? 'under_review');
$workerVersion = (string)($activeResource['version'] ?? 'v1');
$workerUploadedAt = (string)($activeResource['date'] ?? '');
$workerExt = strtolower((string)($activeResource['ext'] ?? ''));
$workerViewerMode = 'unsupported';
if (in_array($workerExt, ['glb', 'gltf'], true)) {
    $workerViewerMode = '3d';
} elseif (in_array($workerExt, ['jpg', 'jpeg', 'png', 'webp'], true)) {
    $isPanoName = (bool)preg_match('/(^|[_\-\s])(360|pano|panorama|equirect)/i', $workerFileName);
    $workerViewerMode = $isPanoName ? '360' : 'image';
} elseif ($workerExt === 'pdf') {
    $workerViewerMode = 'pdf';
} elseif (in_array($workerExt, ['mp4', 'webm', 'ogg'], true)) {
    $workerViewerMode = 'video';
}
?>
<!doctype html>
<html lang="en" class="bg-canvas-white">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo htmlspecialchars($project['name']); ?> | Ripal Design</title>
    <?php $HEADER_MODE = 'dashboard'; require_once PROJECT_ROOT . '/Common/header.php'; 
    
    // Handle review request submission (blocked in read-only worker mode)
    $request_sent = false;
    if (!$isWorkerReadOnly && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_details'])) {
        require_csrf();
        if (db_connected() && !empty($project['id'])) {
            $subject = trim((string)($_POST['request_subject'] ?? 'Site Review'));
            $details = trim((string)($_POST['request_details'] ?? ''));
            $urgency = strtolower(trim((string)($_POST['request_urgency'] ?? 'normal')));
            if (!in_array($urgency, ['critical', 'high', 'normal', 'low'], true)) {
                $urgency = 'normal';
            }
            $submittedBy = (int)($_SESSION['user_id'] ?? ($sessionUser['id'] ?? 0));
            db_query('INSERT INTO review_requests (project_id, submitted_by, subject, description, urgency, status) VALUES (?, ?, ?, ?, ?, "pending")', [
                (int)$project['id'], $submittedBy, $subject, $details, $urgency,
            ]);

            $participants = notifications_get_project_participants((int)$project['id']);
            $recipientIds = [];
            if (!empty($participants['created_by'])) {
                $recipientIds[] = (int)$participants['created_by'];
            }
            $recipientIds = array_merge($recipientIds, notifications_get_user_ids_by_roles(['admin', 'employee']));
            notifications_insert_bulk(
                $recipientIds,
                'review',
                'New Review Request Submitted',
                'A review request was submitted for ' . (string)($project['name'] ?? ('Project #' . (int)$project['id'])) . '.',
                [
                    'actor_user_id' => current_user_id(),
                    'project_id' => (int)$project['id'],
                    'action_key' => 'review.submitted',
                    'deep_link' => rtrim((string)BASE_PATH, '/') . '/dashboard/review_requests.php',
                ]
            );
        }
        $request_sent = true;
    }
    ?>
    <?php if ($workerViewerMode === '360' && $workerPreviewUrl !== ''): ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css">
    <?php endif; ?>
    <?php if ($workerViewerMode === '3d' && $workerPreviewUrl !== ''): ?>
        <script type="module" src="https://unpkg.com/@google/model-viewer/dist/model-viewer.min.js"></script>
    <?php endif; ?>
    <?php if ($workerViewerMode === '360' && $workerPreviewUrl !== ''): ?>
        <script src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>
    <?php endif; ?>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>
    <style>
        .error { color: #94180C; font-size: 10px; font-weight: bold; text-transform: uppercase; margin-top: 4px; display: block; }
        .viewer-3d-modal {
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(0, 0, 0, 0.78);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 16px;
            opacity: 0;
            transition: opacity 220ms ease;
        }
        .viewer-3d-modal.is-open { display: flex; opacity: 1; }
        .viewer-3d-dialog {
            width: min(96vw, 1400px);
            height: min(92vh, 900px);
            background: #111827;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.35);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .viewer-3d-canvas {
            width: 100%;
            height: 100%;
            --progress-bar-color: #94180c;
            --poster-color: #0f172a;
        }
        .viewer-3d-viewport {
            position: relative;
            flex: 1;
            min-height: 0;
            overflow: hidden;
            background: #020617;
        }
        .viewer-3d-error {
            position: absolute;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 24px;
            background: rgba(2, 6, 23, 0.9);
            color: #e2e8f0;
            z-index: 4;
        }
        .viewer-3d-error.is-visible { display: flex; }
    </style>
</head>
<body class="font-sans text-foundation-grey bg-canvas-white">

<?php if ($request_sent): ?>
    <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm p-6">
        <div class="bg-white p-12 max-w-lg w-full text-center border-b-4 border-rajkot-rust shadow-premium">
            <i data-lucide="check-circle" class="w-16 h-16 text-approval-green mx-auto mb-6"></i>
            <h2 class="text-3xl font-serif font-bold text-foundation-grey mb-4">Verification Submitted</h2>
            <p class="text-gray-500 mb-8">Your review request has been logged. An architect will inspect the site shortly.</p>
            <button onclick="window.location.href='dashboard.php'" class="bg-foundation-grey hover:bg-rajkot-rust text-white px-8 py-3 text-[10px] font-bold uppercase tracking-widest transition-all" type="button">
                Continue
            </button>
        </div>
    </div>
<?php endif; ?>

<div class="min-h-screen flex flex-col">
    <!-- Unified Dark Portal Header -->
    <header class="bg-foundation-grey text-white pt-24 pb-12 px-4 shadow-lg">
        <div class="max-w-4xl mx-auto flex flex-col">
            <div class="flex items-center gap-2 mb-3">
                <a href="dashboard.php" class="text-gray-400 hover:text-white transition-colors">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </a>
                <span class="px-2 py-0.5 bg-rajkot-rust text-[10px] font-bold uppercase tracking-widest">
                    Job Detail
                </span>
            </div>
            <h1 class="text-2xl font-serif font-bold"><?php echo htmlspecialchars($project['name']); ?></h1>
            <p class="text-gray-400 text-sm mt-1 flex items-center gap-1">
                <i data-lucide="map-pin" class="w-4 h-4 text-rajkot-rust"></i> 
                <?php echo htmlspecialchars($projectAddress); ?>
            </p>
        </div>
    </header>

    <!-- Navigation Tabs -->
    <nav class="bg-white border-b border-gray-200 sticky top-20 z-30 overflow-x-auto">
        <div class="max-w-4xl mx-auto flex text-sm font-bold">
            <button onclick="switchTab('overview')" id="tab-overview" class="flex-1 py-4 px-2 border-b-2 border-rajkot-rust text-rajkot-rust tab-btn transition-colors uppercase tracking-wider">
                Overview
            </button>
            <button onclick="switchTab('drawings')" id="tab-drawings" class="flex-1 py-4 px-2 border-b-2 border-transparent text-gray-400 tab-btn transition-colors uppercase tracking-wider">
                Drawings
            </button>
            <?php if (!$isWorkerReadOnly): ?>
                <button onclick="switchTab('request')" id="tab-request" class="flex-1 py-4 px-2 border-b-2 border-transparent text-gray-400 tab-btn transition-colors uppercase tracking-wider">
                    Requests
                </button>
            <?php endif; ?>
        </div>
    </nav>

    <main class="flex-grow p-4 max-w-4xl mx-auto w-full">
        
        <!-- 1. OVERVIEW TAB -->
        <div id="content-overview" class="tab-content space-y-6">
            <!-- Project Stats -->
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-white p-4 shadow-premium border border-gray-100">
                    <span class="text-gray-400 text-[10px] uppercase font-bold tracking-widest">Area</span>
                    <span class="block text-lg font-bold mt-1"><?php echo htmlspecialchars((string)$project['area']); ?></span>
                </div>
                <div class="bg-white p-4 shadow-premium border border-gray-100">
                    <span class="text-gray-400 text-[10px] uppercase font-bold tracking-widest">Budget</span>
                    <span class="block text-lg font-bold mt-1 text-rajkot-rust"><?php echo htmlspecialchars((string)$project['budget']); ?></span>
                </div>
            </div>

            <!-- Contacts Section -->
            <div class="space-y-4">
                <h2 class="text-sm font-bold uppercase tracking-widest text-gray-400 flex items-center gap-2">
                    <i data-lucide="users" class="w-4 h-4 text-rajkot-rust text-opacity-50"></i>
                    Site Contacts
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Owner -->
                    <div class="bg-white p-4 shadow-premium border border-gray-100 flex justify-between items-center">
                        <div>
                            <span class="text-xs text-gray-400 font-bold uppercase tracking-widest block mb-1">Owner</span>
                            <span class="font-bold"><?php echo htmlspecialchars($project['owner']['name']); ?></span>
                        </div>
                        <a href="tel:<?php echo htmlspecialchars((string)$project['owner']['contact']); ?>" class="w-10 h-10 bg-green-50 text-green-700 rounded-full flex items-center justify-center">
                            <i data-lucide="phone" class="w-5 h-5"></i>
                        </a>
                    </div>
                    <!-- Team -->
                    <?php foreach($project['workers'] as $w): ?>
                    <div class="bg-white p-4 shadow-premium border border-gray-100 flex justify-between items-center">
                        <div>
                            <span class="text-xs text-gray-400 font-bold uppercase tracking-widest block mb-1"><?php echo htmlspecialchars($w['role']); ?></span>
                            <span class="font-bold"><?php echo htmlspecialchars($w['name']); ?></span>
                        </div>
                        <a href="tel:<?php echo htmlspecialchars((string)$w['contact']); ?>" class="w-10 h-10 bg-slate-accent/10 text-slate-accent rounded-full flex items-center justify-center">
                            <i data-lucide="phone-call" class="w-5 h-5"></i>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="space-y-4">
                <h2 class="text-sm font-bold uppercase tracking-widest text-gray-400 flex items-center gap-2">
                    <i data-lucide="map-pin" class="w-4 h-4 text-rajkot-rust text-opacity-50"></i>
                    Location Mapping
                </h2>
                <div class="bg-white p-4 shadow-premium border border-gray-100">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Address</p>
                    <p class="text-sm text-slate-700 leading-relaxed break-words mb-3">
                        <?php if ($projectLocationSentenceValue !== ''): ?>
                            This project is located at <?php echo htmlspecialchars($projectLocationSentenceValue); ?>.
                        <?php else: ?>
                            Address is not available yet.
                        <?php endif; ?>
                    </p>
                    <?php if ($projectMapEmbedSrc !== ''): ?>
                        <div class="mb-3">
                            <?php if ($projectDirectionHref !== ''): ?>
                                <a
                                    href="<?php echo htmlspecialchars($projectDirectionHref); ?>"
                                    target="_blank"
                                    class="inline-flex items-center justify-center bg-foundation-grey hover:bg-rajkot-rust text-white px-4 py-2 text-[10px] font-bold uppercase tracking-widest transition-all no-underline">
                                    Get Direction
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="border border-gray-200 overflow-hidden rounded-sm">
                            <iframe
                                title="Project location map"
                                class="w-full"
                                style="aspect-ratio: 1 / 1;"
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"
                                src="<?php echo htmlspecialchars($projectMapEmbedSrc); ?>"></iframe>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Materials Summary -->
            <div class="space-y-4">
                <h2 class="text-sm font-bold uppercase tracking-widest text-gray-400 flex items-center gap-2">
                    <i data-lucide="package" class="w-4 h-4 text-rajkot-rust text-opacity-50"></i>
                    Material Status
                </h2>
                <div class="bg-white shadow-premium border border-gray-100 overflow-hidden">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-[10px] uppercase font-bold text-gray-400 tracking-widest">
                            <tr>
                                <th class="p-4">Item</th>
                                <th class="p-4 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach($project['goods'] as $g): ?>
                            <tr>
                                <td class="p-4 font-medium"><?php echo htmlspecialchars($g['item']); ?></td>
                                <td class="p-4 text-right">
                                    <span class="text-[10px] font-bold px-2 py-0.5 border <?php echo $g['status'] == 'Delivered' ? 'text-approval-green border-approval-green/20 bg-approval-green/10' : 'text-pending-amber border-pending-amber/20 bg-pending-amber/10'; ?>">
                                        <?php echo strtoupper($g['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 2. DRAWINGS TAB -->
        <div id="content-drawings" class="tab-content hidden space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-widest text-gray-400 flex items-center gap-2 px-1">
                <i data-lucide="file-text" class="w-4 h-4 text-rajkot-rust text-opacity-50"></i>
                Technical Drawings
            </h2>
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
                <aside class="bg-white border border-gray-100 shadow-premium p-6 lg:col-span-1" style="height:80vh; overflow:auto;">
                    <h2 class="text-[10px] uppercase tracking-widest text-rajkot-rust font-bold mb-4">File Details</h2>
                    <div class="space-y-3 text-sm">
                        <p><strong>Project:</strong> <?php echo htmlspecialchars((string)$project['name']); ?></p>
                        <p><strong>File:</strong> <?php echo htmlspecialchars($workerFileName); ?></p>
                        <p><strong>Version:</strong> <?php echo htmlspecialchars($workerVersion !== '' ? $workerVersion : 'v1'); ?></p>
                        <p><strong>Status:</strong> <?php echo htmlspecialchars($workerStatus); ?></p>
                        <p><strong>Uploaded:</strong> <?php echo $workerUploadedAt ? htmlspecialchars(date('M d, Y H:i', strtotime($workerUploadedAt))) : 'N/A'; ?></p>
                    </div>

                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <h3 class="text-[10px] uppercase tracking-widest text-rajkot-rust font-bold mb-3">Project Files</h3>
                        <div class="space-y-2 max-h-72 overflow-auto pr-1">
                            <?php foreach ($projectResources as $entry): ?>
                                <?php $isActiveEntry = !empty($activeResource['id']) && (int)$entry['id'] === (int)$activeResource['id'] && (string)$entry['kind'] === (string)($activeResource['kind'] ?? ''); ?>
                                <a href="<?php echo htmlspecialchars((string)$entry['viewer_url']); ?>" class="block no-underline border rounded p-2 <?php echo $isActiveEntry ? 'border-rajkot-rust bg-red-50' : 'border-gray-100 bg-white hover:border-rajkot-rust'; ?>">
                                    <p class="text-xs font-semibold text-foundation-grey break-all"><?php echo htmlspecialchars((string)$entry['title']); ?></p>
                                    <p class="text-[10px] text-gray-500 mt-1">
                                        <?php echo htmlspecialchars(strtoupper((string)$entry['kind'])); ?>
                                        <?php if (!empty($entry['date'])): ?> &bull; <?php echo htmlspecialchars(date('M d, H:i', strtotime((string)$entry['date']))); ?><?php endif; ?>
                                    </p>
                                </a>
                            <?php endforeach; ?>
                            <?php if (empty($projectResources)): ?>
                                <p class="text-xs text-gray-400">No files uploaded for this project yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </aside>

                <section class="lg:col-span-4 bg-white border border-gray-100 shadow-premium p-6 flex flex-col" style="height:80vh;">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-serif font-bold"><?php echo htmlspecialchars($workerFileName); ?></h3>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] uppercase tracking-widest px-2 py-1 bg-gray-50 border border-gray-100"><?php echo htmlspecialchars($workerStatus); ?></span>
                        </div>
                    </div>
                    <div class="flex-grow border-2 border-dashed border-gray-200 rounded-lg bg-gray-50 overflow-hidden">
                        <?php if ($workerPreviewUrl === ''): ?>
                            <div class="h-full flex items-center justify-center text-gray-400 px-8 text-center">
                                Preview unavailable. File path could not be resolved from saved metadata.
                            </div>
                        <?php elseif ($workerViewerMode === '3d'): ?>
                            <div class="h-full bg-slate-900 text-white flex flex-col">
                                <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 border-b border-white/10 bg-black/20">
                                    <div class="flex items-center gap-2">
                                        <span class="hidden sm:inline text-[10px] uppercase tracking-widest text-gray-300">Drag to rotate &bull; Scroll to zoom</span>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2 justify-end">
                                        <button type="button" id="open3DPopup" class="text-xs uppercase tracking-widest bg-rajkot-rust hover:bg-red-700 px-3 py-2 text-white font-bold">Fullscreen 3D</button>
                                    </div>
                                </div>
                                <model-viewer
                                    id="inline3DViewer"
                                    src="<?php echo htmlspecialchars($workerPreviewUrl); ?>"
                                    camera-controls
                                    auto-rotate
                                    auto-rotate-delay="0"
                                    rotation-per-second="25deg"
                                    autoplay
                                    shadow-intensity="1"
                                    exposure="1"
                                    environment-image="neutral"
                                    class="w-full h-full bg-slate-950"
                                ></model-viewer>
                                <div id="inline3DError" class="viewer-3d-error" role="alert">
                                    <div>
                                        <p class="text-sm font-semibold mb-2">3D preview failed to load.</p>
                                        <a href="<?php echo htmlspecialchars($workerPreviewUrl); ?>" target="_blank" rel="noopener" class="inline-block text-xs bg-rajkot-rust hover:bg-red-700 text-white px-3 py-2 no-underline">Open model directly</a>
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($workerViewerMode === '360'): ?>
                            <div class="mb-3 flex flex-wrap items-center gap-2 p-2">
                                <button type="button" id="zoomInBtn" class="text-xs bg-foundation-grey text-white px-2 py-1">Zoom +</button>
                                <button type="button" id="zoomOutBtn" class="text-xs bg-foundation-grey text-white px-2 py-1">Zoom -</button>
                                <button type="button" id="resetViewBtn" class="text-xs bg-foundation-grey text-white px-2 py-1">Reset</button>
                            </div>
                            <div id="panoViewer" class="w-full h-full"></div>
                        <?php elseif ($workerViewerMode === 'image'): ?>
                            <img src="<?php echo htmlspecialchars($workerPreviewUrl); ?>" alt="<?php echo htmlspecialchars($workerFileName); ?>" class="w-full h-full object-contain bg-white" loading="lazy">
                        <?php elseif ($workerViewerMode === 'pdf'): ?>
                            <iframe src="<?php echo htmlspecialchars($workerPreviewUrl); ?>" class="w-full h-full bg-white" title="PDF Preview"></iframe>
                        <?php elseif ($workerViewerMode === 'video'): ?>
                            <video controls class="w-full h-full bg-black">
                                <source src="<?php echo htmlspecialchars($workerPreviewUrl); ?>">
                                Your browser does not support video preview.
                            </video>
                        <?php else: ?>
                            <div class="h-full flex flex-col items-center justify-center text-gray-500 gap-4 px-8 text-center">
                                <p>This file type cannot be previewed inline yet.</p>
                                <a href="<?php echo htmlspecialchars($workerPreviewUrl); ?>" target="_blank" rel="noopener" class="bg-foundation-grey hover:bg-rajkot-rust text-white px-4 py-2 text-xs uppercase tracking-wider font-bold no-underline">Open File</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>
        </div>

        <?php if (!$isWorkerReadOnly): ?>
            <!-- 3. REQUEST TAB -->
            <div id="content-request" class="tab-content hidden space-y-6">
                <div class="bg-white p-6 shadow-premium border border-gray-100">
                    <h3 class="text-lg font-bold font-serif mb-4">New Review Request</h3>
                    <form class="space-y-4" method="POST" action="" id="requestForm">
                        <?php echo csrf_token_field(); ?>
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-2">Subject</label>
                            <input type="text" name="request_subject" class="w-full bg-gray-50 border border-gray-200 p-3 outline-none focus:border-rajkot-rust transition-colors" placeholder="e.g. Beam Reinforcement Ready">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-2">Urgency</label>
                                <select name="request_urgency" class="w-full bg-gray-50 border border-gray-200 p-3 outline-none focus:border-rajkot-rust transition-colors appearance-none">
                                    <option>Normal</option>
                                    <option>High</option>
                                    <option>Critical</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-2">Trade</label>
                                <select name="request_trade" class="w-full bg-gray-50 border border-gray-200 p-3 outline-none focus:border-rajkot-rust transition-colors appearance-none">
                                    <option>Structural</option>
                                    <option>Plumbing</option>
                                    <option>Electrical</option>
                                    <option>Finishing</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-gray-400 tracking-widest mb-2">Details</label>
                            <textarea name="request_details" rows="4" class="w-full bg-gray-50 border border-gray-200 p-3 outline-none focus:border-rajkot-rust transition-colors" placeholder="Explain what requires immediate inspection..."></textarea>
                        </div>
                        <button type="submit" class="w-full bg-rajkot-rust text-white py-4 font-bold uppercase tracking-widest shadow-lg active:scale-[0.98] transition-all">
                            Submit for Verification
                        </button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="mt-6 bg-white border border-gray-100 shadow-premium p-5 text-xs uppercase tracking-widest text-gray-400 text-center">
                Worker access is read-only. Editing and new request submissions are disabled.
            </div>
        <?php endif; ?>

    </main>

    <?php require_once PROJECT_ROOT . '/Common/footer.php'; ?>
</div>

<?php if ($workerViewerMode === '3d' && $workerPreviewUrl !== ''): ?>
    <div id="threeDModal" class="viewer-3d-modal" aria-hidden="true">
        <div class="viewer-3d-dialog" role="dialog" aria-modal="true" aria-label="3D model fullscreen viewer">
            <div class="flex items-center justify-between px-4 py-3 border-b border-white/10 text-white bg-black/30">
                <div class="text-xs uppercase tracking-widest text-gray-200">Fullscreen 3D Viewer</div>
                <div class="flex items-center gap-2">
                    <button type="button" id="modalClose3D" class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-1">Close</button>
                </div>
            </div>
            <div class="viewer-3d-viewport" id="modal3DViewport">
                <model-viewer
                    id="modal3DViewer"
                    src="<?php echo htmlspecialchars($workerPreviewUrl); ?>"
                    camera-controls
                    auto-rotate
                    auto-rotate-delay="0"
                    rotation-per-second="30deg"
                    autoplay
                    shadow-intensity="1"
                    exposure="1"
                    environment-image="neutral"
                    class="viewer-3d-canvas"
                ></model-viewer>
                <div id="modal3DError" class="viewer-3d-error" role="alert">
                    <div>
                        <p class="text-sm font-semibold mb-2">3D preview failed to load.</p>
                        <a href="<?php echo htmlspecialchars($workerPreviewUrl); ?>" target="_blank" rel="noopener" class="inline-block text-xs bg-rajkot-rust hover:bg-red-700 text-white px-3 py-2 no-underline">Open model directly</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    function switchTab(tab) {
        // Update Buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('border-rajkot-rust', 'text-rajkot-rust');
            btn.classList.add('border-transparent', 'text-gray-400');
        });
        const activeBtn = document.getElementById('tab-' + tab);
        activeBtn.classList.add('border-rajkot-rust', 'text-rajkot-rust');
        activeBtn.classList.remove('border-transparent', 'text-gray-400');

        // Update Content
        document.querySelectorAll('.tab-content').forEach(content => content.classList.add('hidden'));
        document.getElementById('content-' + tab).classList.remove('hidden');

        // Update Hash
        window.location.hash = tab;
    }

    // Initialize from Hash
    window.addEventListener('load', () => {
        const hash = window.location.hash.replace('#', '') || 'overview';
        if (hash === 'request' && !document.getElementById('tab-request')) {
            switchTab('overview');
            return;
        }
        switchTab(hash);
    });

    $(document).ready(function() {
        if (!document.getElementById('requestForm')) {
            // Viewer logic should still run without request form.
        } else {
            $("#requestForm").validate({
                rules: {
                    request_subject: {
                        required: true,
                        minlength: 5
                    },
                    request_details: {
                        required: true,
                        minlength: 10
                    }
                },
                messages: {
                    request_subject: {
                        required: "Subject is required for registry",
                        minlength: "Subject must be at least 5 characters"
                    },
                    request_details: {
                        required: "Detailed context is mandatory",
                        minlength: "Please provide more detail for the architect"
                    }
                },
                errorPlacement: function(error, element) {
                    error.insertAfter(element);
                }
            });
        }

        <?php if ($workerViewerMode === '3d' && $workerPreviewUrl !== ''): ?>
        const open3DPopup = document.getElementById('open3DPopup');
        const threeDModal = document.getElementById('threeDModal');
        const modalClose3D = document.getElementById('modalClose3D');
        const inline3DViewer = document.getElementById('inline3DViewer');
        const modal3DViewer = document.getElementById('modal3DViewer');
        const inline3DError = document.getElementById('inline3DError');
        const modal3DError = document.getElementById('modal3DError');

        function open3DModal() {
            if (!threeDModal) {
                return;
            }
            threeDModal.classList.add('is-open');
            threeDModal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function close3DModal() {
            if (!threeDModal) {
                return;
            }
            threeDModal.classList.remove('is-open');
            threeDModal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        if (open3DPopup) {
            open3DPopup.addEventListener('click', open3DModal);
        }
        if (modalClose3D) {
            modalClose3D.addEventListener('click', close3DModal);
        }
        if (threeDModal) {
            threeDModal.addEventListener('click', function(event) {
                if (event.target === threeDModal) {
                    close3DModal();
                }
            });
        }
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && threeDModal && threeDModal.classList.contains('is-open')) {
                close3DModal();
            }
        });

        if (inline3DViewer && inline3DError) {
            inline3DViewer.addEventListener('error', function() {
                inline3DError.classList.add('is-visible');
            });
        }
        if (modal3DViewer && modal3DError) {
            modal3DViewer.addEventListener('error', function() {
                modal3DError.classList.add('is-visible');
            });
        }
        <?php endif; ?>

        <?php if ($workerViewerMode === '360' && $workerPreviewUrl !== ''): ?>
        if (window.pannellum) {
            const panoViewer = pannellum.viewer('panoViewer', {
                type: 'equirectangular',
                panorama: <?php echo json_encode($workerPreviewUrl); ?>,
                autoLoad: true,
                compass: false,
                showZoomCtrl: true,
                showFullscreenCtrl: true,
                mouseZoom: true
            });

            const zoomInBtn = document.getElementById('zoomInBtn');
            const zoomOutBtn = document.getElementById('zoomOutBtn');
            const resetViewBtn = document.getElementById('resetViewBtn');

            if (zoomInBtn) {
                zoomInBtn.addEventListener('click', function() {
                    panoViewer.setHfov(panoViewer.getHfov() - 10);
                });
            }
            if (zoomOutBtn) {
                zoomOutBtn.addEventListener('click', function() {
                    panoViewer.setHfov(panoViewer.getHfov() + 10);
                });
            }
            if (resetViewBtn) {
                resetViewBtn.addEventListener('click', function() {
                    panoViewer.setPitch(0);
                    panoViewer.setYaw(0);
                    panoViewer.setHfov(100);
                });
            }
        }
        <?php endif; ?>
    });
</script>

</body>
</html>
