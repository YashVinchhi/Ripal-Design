<?php
require_once __DIR__ . '/../app/Core/Bootstrap/init.php';
require_once __DIR__ . '/../Common/public_shell.php';

$tourId = (int)($_GET['tour_id'] ?? 0);
$projectId = (int)($_GET['project_id'] ?? 0);
$apiUrl = base_path('api/public_tours.php');
?>
<!DOCTYPE html>
<html lang="en" class="bg-black">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Virtual Tour | Ripal Design</title>
    <link rel="icon" href="<?php echo esc_attr(BASE_PATH); ?>/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css">
    <style>
        body {
            margin: 0;
            font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            background: #030712;
            color: #e5e7eb;
        }
        .tour-shell {
            max-width: 1200px;
            margin: 0 auto;
            padding: 28px 16px 22px;
        }
        .tour-head {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
        }
        .tour-title {
            font-size: 1.6rem;
            margin: 0;
            color: #f9fafb;
        }
        .tour-subtitle {
            margin: 4px 0 0;
            color: #9ca3af;
            font-size: 0.95rem;
        }
        .tour-controls {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .tour-controls select {
            min-width: 240px;
            background: #111827;
            color: #f9fafb;
            border: 1px solid #374151;
            padding: 8px 10px;
            border-radius: 4px;
        }
        #tourViewer {
            width: 100%;
            height: min(74vh, 700px);
            border: 1px solid #1f2937;
            border-radius: 6px;
            background: #0f172a;
            overflow: hidden;
        }
        #tourStatus {
            margin-top: 10px;
            color: #9ca3af;
            font-size: 0.88rem;
        }
        #tourStatus.error {
            color: #fca5a5;
        }
        .tour-scene-strip {
            margin-top: 12px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .tour-scene-btn {
            border: 1px solid #374151;
            background: #111827;
            color: #e5e7eb;
            padding: 7px 10px;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            cursor: pointer;
            border-radius: 4px;
        }
        .tour-scene-btn.active {
            border-color: #dc2626;
            background: #7f1d1d;
            color: #fff;
        }
        .tour-link-hotspot {
            width: 18px;
            height: 18px;
            background: #dc2626;
            border: 2px solid #fff;
            border-radius: 50%;
            box-shadow: 0 0 0 2px rgba(0, 0, 0, 0.35);
            cursor: pointer;
        }
        .tour-link-hotspot-label {
            background: rgba(2, 6, 23, 0.9);
            color: #fff;
            font-size: 11px;
            padding: 5px 8px;
            border-radius: 4px;
            white-space: nowrap;
            transform: translate(-50%, -130%);
        }
        @media (max-width: 700px) {
            .tour-controls {
                width: 100%;
            }
            .tour-controls select {
                width: 100%;
                min-width: 0;
            }
            #tourViewer {
                height: 62vh;
            }
        }
    </style>
</head>
<body>
<?php $HEADER_MODE = 'public'; require_once __DIR__ . '/../app/Ui/header.php'; ?>
<div class="tour-shell">
    <div class="tour-head">
        <div>
            <h1 id="tourTitle" class="tour-title">Virtual Tour</h1>
            <p id="tourSubtitle" class="tour-subtitle">Loading tour data...</p>
        </div>
        <div class="tour-controls">
            <select id="sceneSelect" aria-label="Select scene"></select>
        </div>
    </div>

    <div id="tourViewer"></div>
    <p id="tourStatus">Loading tour...</p>
    <div id="sceneStrip" class="tour-scene-strip"></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>
<script>
(function () {
    const tourId = <?php echo (int)$tourId; ?>;
    const projectId = <?php echo (int)$projectId; ?>;
    const apiUrl = <?php echo json_encode($apiUrl); ?>;

    const titleEl = document.getElementById('tourTitle');
    const subtitleEl = document.getElementById('tourSubtitle');
<?php rd_page_end(); ?>
    const statusEl = document.getElementById('tourStatus');
    const sceneSelect = document.getElementById('sceneSelect');
    const sceneStrip = document.getElementById('sceneStrip');

    const state = {
        tour: null,
        scenes: [],
        scenesById: new Map(),
        currentSceneId: 0,
        viewer: null,
    };

    function setStatus(message, isError) {
        statusEl.textContent = message;
        statusEl.className = isError ? 'error' : '';
    }

    function createHotspotTooltip(hotSpotDiv, args) {
        hotSpotDiv.classList.add('tour-link-hotspot');
        const label = document.createElement('div');
        label.className = 'tour-link-hotspot-label';
        label.textContent = args && args.label ? args.label : 'Open';
        hotSpotDiv.appendChild(label);
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
                createTooltipArgs: { label: hotspot.title || 'Open' },
                clickHandlerFunc: function () {
                    loadScene(targetId);
                },
            };
        });
    }

    function renderSceneSelectors() {
        sceneSelect.innerHTML = '';
        sceneStrip.innerHTML = '';

        state.scenes.forEach((scene) => {
            const option = document.createElement('option');
            option.value = String(scene.id);
            option.textContent = scene.name;
            sceneSelect.appendChild(option);

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'tour-scene-btn';
            btn.textContent = scene.name;
            btn.setAttribute('data-scene-id', String(scene.id));
            btn.addEventListener('click', function () {
                loadScene(scene.id);
            });
            sceneStrip.appendChild(btn);
        });
    }

    function markActiveScene(sceneId) {
        sceneSelect.value = String(sceneId);
        const buttons = sceneStrip.querySelectorAll('.tour-scene-btn');
        buttons.forEach((btn) => {
            const id = Number(btn.getAttribute('data-scene-id') || 0);
            if (id === Number(sceneId)) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
    }

    function loadScene(sceneId) {
        const scene = state.scenesById.get(Number(sceneId));
        if (!scene) {
            setStatus('Scene not found.', true);
            return;
        }
        if (!scene.image_url) {
            setStatus('Scene image is missing.', true);
            return;
        }

        state.currentSceneId = scene.id;
        markActiveScene(scene.id);

        if (state.viewer && typeof state.viewer.destroy === 'function') {
            state.viewer.destroy();
        }

        state.viewer = pannellum.viewer('tourViewer', {
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

        setStatus('Viewing scene: ' + scene.name, false);
    }

    async function loadTour() {
        const params = new URLSearchParams();
        if (tourId > 0) {
            params.set('tour_id', String(tourId));
        }
        if (projectId > 0) {
            params.set('project_id', String(projectId));
        }

        const url = apiUrl + '?' + params.toString();
        const resp = await fetch(url, { credentials: 'same-origin' });
        const data = await resp.json().catch(() => ({}));

        if (!resp.ok || !data.success || !Array.isArray(data.scenes)) {
            setStatus(data.error || 'Could not load tour.', true);
            subtitleEl.textContent = 'Tour unavailable';
            return;
        }

        state.tour = data.tour || {};
        state.scenes = data.scenes;
        state.scenesById = new Map();
        state.scenes.forEach((scene) => {
            state.scenesById.set(Number(scene.id), scene);
        });

        titleEl.textContent = state.tour.title || 'Virtual Tour';
        subtitleEl.textContent = (state.tour.project_name ? (state.tour.project_name + ' | ') : '') + ((state.tour.description || '').trim() || 'Interactive panorama walkthrough');

        renderSceneSelectors();

        const startSceneId = Number((state.tour && state.tour.start_scene_id) || 0);
        const firstSceneId = Number((state.scenes[0] && state.scenes[0].id) || 0);
        const initialSceneId = startSceneId > 0 && state.scenesById.has(startSceneId) ? startSceneId : firstSceneId;

        if (!initialSceneId) {
            setStatus('No scenes are available in this tour.', true);
            return;
        }

        loadScene(initialSceneId);
    }

    sceneSelect.addEventListener('change', function () {
        loadScene(Number(sceneSelect.value || 0));
    });

    loadTour().catch(function (err) {
        console.error(err);
        setStatus('Could not load tour due to a network or server error.', true);
    });
})();
</script>
</body>
</html>
