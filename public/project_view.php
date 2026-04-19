<?php
require_once __DIR__ . '/../app/Core/Bootstrap/init.php';
$projectViewContent = function_exists('public_content_page_values') ? public_content_page_values('project_view') : [];
$ct = static function ($key, $default = '') use ($projectViewContent) {
    return (string)($projectViewContent[$key] ?? $default);
};
$ctImage = static function ($key, $default = '') use ($projectViewContent) {
    $value = (string)($projectViewContent[$key] ?? $default);
    if (function_exists('public_content_image_url')) {
        return (string)public_content_image_url($value, $default);
    }
    if (function_exists('base_path')) {
        return (string)base_path(ltrim((string)$value, '/'));
    }
    return (string)$value;
};
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo esc($ct('page_title', 'Products | Ripal Design')); ?></title>
    <link rel="icon" href="<?php echo esc_attr(BASE_PATH); ?>/favicon.ico" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <style>
        body { background-color: #050505; color: #fff; font-family: 'Inter', sans-serif; }
        .serif { font-family: 'Cormorant Garamond', serif; }
        .product-card:hover img { transform: scale(1.05); }

        /* Masonry-like layout using CSS columns for a Pinterest/Behance feel */
        #projectGrid.masonry-grid {
            column-gap: 1.5rem;
            column-width: 300px;
        }
        .project-item {
            display: inline-block;
            width: 100%;
            margin: 0 0 1.25rem;
            break-inside: avoid;
            -webkit-column-break-inside: avoid;
            -moz-column-break-inside: avoid;
        }
        .project-item img { width: 100%; height: auto; display: block; border-radius: 0.5rem; transition: transform 0.35s ease; }
        .project-item:hover img { transform: scale(1.03); }

        /* Modal content scroll area */
        #projectModal .modal-content { max-height: calc(100vh - 120px); overflow: auto; }
    </style>
</head>
<body class="bg-[#050505] text-white overflow-x-hidden">
    <?php $HEADER_MODE = 'public'; require_once __DIR__ . '/../app/Ui/header.php'; ?>

    <main class="min-h-screen pt-32 pb-20">
        <div class="container mx-auto px-6">
            <div class="text-center mb-20">
                <span class="text-[#731209] tracking-[0.2em] text-sm uppercase font-semibold"><?php echo esc($ct('section_kicker', 'Exquisite Materials')); ?></span>
                <h1 class="text-5xl md:text-6xl serif mt-4"><?php echo esc($ct('section_heading', 'Curated Collection')); ?></h1>
            </div>

            <div id="projectGrid" class="masonry-grid">
                <!-- Projects will be loaded dynamically via JS from /api/projects.php -->
            </div>
            <div id="gridEndSentinel" aria-hidden="true"></div>

            <!-- Project modal/catalogue -->
            <div id="projectModal" class="fixed inset-0 z-50 hidden items-center justify-center">
                <div class="absolute inset-0 bg-black/70" id="projectModalOverlay"></div>
                <div class="relative z-60 max-w-6xl w-full mx-4 bg-white text-gray-900 rounded-lg overflow-hidden shadow-2xl">
                    <div class="flex items-center justify-between p-4 border-b">
                        <div>
                            <h2 id="modalProjectTitle" class="text-xl font-serif"></h2>
                            <p id="modalProjectMeta" class="text-sm text-gray-500"></p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button id="modalAppreciateBtn" class="bg-rajkot-rust text-white px-3 py-2 rounded text-sm">Appreciate (<span id="modalLikesCount">0</span>)</button>
                            <button id="modalSaveBtn" class="border border-gray-200 px-3 py-2 rounded text-sm">Save</button>
                            <button id="modalCloseBtn" class="text-gray-600 px-3 py-2">Close</button>
                        </div>
                    </div>
                    <div class="p-4">
                        <div id="modalGallery" class="grid grid-cols-1 md:grid-cols-2 gap-4"></div>
                    </div>
                </div>
            </div>

            <form id="globalCsrfForm" style="display:none;">
                <?php echo csrf_token_field(); ?>
            </form>

            <script>
                const apiBase = <?php echo json_encode(rtrim(BASE_PATH, '/')); ?>;
                const limitPerPage = 18;
                let offset = 0;
                let loading = false;
                let finished = false;
                const grid = document.getElementById('projectGrid');
                let modelViewerLoaded = false;
                let pannellumLoaded = false;

                function loadScript(src, isModule = false) {
                    return new Promise((resolve, reject) => {
                        const script = document.createElement('script');
                        script.src = src;
                        if (isModule) {
                            script.type = 'module';
                        }
                        script.onload = resolve;
                        script.onerror = reject;
                        document.head.appendChild(script);
                    });
                }

                function loadStyle(href) {
                    return new Promise((resolve) => {
                        if (document.querySelector('link[data-runtime-style="' + href + '"]')) {
                            resolve();
                            return;
                        }
                        const link = document.createElement('link');
                        link.rel = 'stylesheet';
                        link.href = href;
                        link.setAttribute('data-runtime-style', href);
                        link.onload = resolve;
                        document.head.appendChild(link);
                    });
                }

                async function ensureRuntimeViewerDeps(mediaTypes) {
                    if (mediaTypes.includes('MODEL') && !modelViewerLoaded) {
                        await loadScript('https://unpkg.com/@google/model-viewer/dist/model-viewer.min.js', true);
                        modelViewerLoaded = true;
                    }

                    if (mediaTypes.includes('PANORAMA') && !pannellumLoaded) {
                        await loadStyle('https://unpkg.com/pannellum/build/pannellum.css');
                        await loadScript('https://unpkg.com/pannellum/build/pannellum.js');
                        pannellumLoaded = true;
                    }
                }

                function escapeHtml(s) {
                    if (!s) return '';
                    return String(s).replace(/[&<>"'`]/g, function (ch) {
                        return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;','`':'&#x60;'})[ch];
                    });
                }

                function createProjectCard(p) {
                    const a = document.createElement('a');
                    a.href = '#';
                    a.className = 'project-item group cursor-pointer no-underline block';
                    a.dataset.projectId = p.id;
                    const cover = p.cover_image || <?php echo json_encode(rtrim(BASE_PATH, '/')); ?> + '/assets/Content/placeholder.jpg';
                    a.innerHTML = `
                        <div class="relative overflow-hidden w-full">
                            <img src="${escapeHtml(cover)}" loading="lazy" decoding="async" class="w-full h-auto object-cover" alt="${escapeHtml(p.name)}">
                        </div>
                        <div class="mt-2">
                            <h3 class="text-lg serif mb-1">${escapeHtml(p.name)}</h3>
                            <p class="text-gray-400 text-sm uppercase tracking-wide">${escapeHtml(p.owner_name || '')}</p>
                        </div>
                    `;
                    a.addEventListener('click', function (e) { e.preventDefault(); openProjectModal(p.id); });
                    return a;
                }

                function renderGridStatus(markup) {
                    if (!grid) return;
                    grid.innerHTML = markup;
                }

                async function loadMore() {
                    if (loading || finished) return;
                    loading = true;
                    try {
                        const resp = await fetch(apiBase + '/api/projects.php?limit=' + limitPerPage + '&offset=' + offset);
                        if (!resp.ok) throw new Error('Network error');
                        const data = await resp.json();
                        const items = data.projects || [];
                        if (offset === 0) {
                            grid.innerHTML = '';
                        }
                        if (!items.length) {
                            if (offset === 0) {
                                renderGridStatus('<div class="text-center py-5"><i class="bi bi-folder2-open fs-1 text-muted"></i><p class="mt-3 text-muted">No projects found.</p></div>');
                            }
                            finished = true;
                            observer.disconnect();
                            sentinel.remove();
                            return;
                        }
                        items.forEach(p => {
                            const el = createProjectCard(p);
                            grid.appendChild(el);
                        });
                        offset += items.length;
                        if (items.length < limitPerPage) {
                            finished = true;
                            observer.disconnect();
                            sentinel.remove();
                        }
                    } catch (e) {
                        console.error('Project load failed:', e);
                        if (offset === 0) {
                            renderGridStatus('<div class="text-center py-5"><i class="bi bi-exclamation-triangle fs-1 text-warning"></i><p class="mt-3 text-muted">Unable to load projects. Please refresh the page.</p><button class="btn btn-outline-secondary mt-2" onclick="location.reload()">Try Again</button></div>');
                        }
                    } finally {
                        loading = false;
                    }
                }

                // sentinel & observer for infinite scroll
                const sentinel = document.getElementById('gridEndSentinel');
                const observer = new IntersectionObserver(entries => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            loadMore();
                        }
                    });
                }, { rootMargin: '300px' });

                observer.observe(sentinel);

                async function openProjectModal(projectId) {
                    try {
                        const resp = await fetch(apiBase + '/api/projects.php?id=' + encodeURIComponent(projectId));
                        const data = await resp.json();
                        if (!data.project) return;
                        const modalMediaTypes = (data.files || []).map(f => String(f.media_type || '').toUpperCase());
                        await ensureRuntimeViewerDeps(modalMediaTypes);
                        document.getElementById('modalProjectTitle').innerText = data.project.name || '';
                        document.getElementById('modalProjectMeta').innerText = (data.project.location || '') + ' • ' + (data.project.owner_name || '');
                        document.getElementById('modalLikesCount').innerText = data.likes || 0;

                        const gallery = document.getElementById('modalGallery');
                        gallery.innerHTML = '';
                        (data.files || []).forEach(f => {
                            const wrap = document.createElement('div');
                            const mediaType = (f.media_type || 'IMAGE').toUpperCase();
                            if (mediaType === 'PANORAMA') {
                                const panoId = 'pano_' + (f.id || Math.random().toString(36).slice(2,9));
                                wrap.innerHTML = `<div id="${panoId}" style="width:100%;height:var(--model-viewer-height,400px);"></div>`;
                                gallery.appendChild(wrap);
                                try {
                                    if (typeof pannellum !== 'undefined' && pannellum.viewer) {
                                        pannellum.viewer(panoId, { type: 'equirectangular', panorama: f.file_path, autoLoad: true, showZoomCtrl: true });
                                    } else {
                                        document.getElementById(panoId).innerHTML = '<img src="' + escapeHtml(f.file_path) + '" class="w-full h-full object-cover" alt="' + escapeHtml(f.name) + '">';
                                    }
                                } catch (e) {
                                    console.error('pannellum init error', e);
                                    document.getElementById(panoId).innerHTML = '<img src="' + escapeHtml(f.file_path) + '" class="w-full h-full object-cover" alt="' + escapeHtml(f.name) + '">';
                                }
                            } else if (mediaType === 'MODEL') {
                                wrap.innerHTML = `<model-viewer src="${escapeHtml(f.file_path)}" alt="${escapeHtml(f.name)}" camera-controls auto-rotate style="width:100%;height:var(--model-viewer-height,400px);background:#f7f7f7;"></model-viewer>`;
                                gallery.appendChild(wrap);
                            } else {
                                wrap.innerHTML = `<img src="${escapeHtml(f.file_path)}" class="w-full h-64 object-cover" alt="${escapeHtml(f.name)}" loading="lazy">`;
                                gallery.appendChild(wrap);
                            }
                        });

                        // wire appreciate button
                        const appreciateBtn = document.getElementById('modalAppreciateBtn');
                        appreciateBtn.onclick = function () { appreciateProject(projectId); };

                        // track current files for Save flow
                        window.currentProjectFiles = data.files || [];
                        const saveBtn = document.getElementById('modalSaveBtn');
                        saveBtn.onclick = async function () {
                            const files = window.currentProjectFiles || [];
                            if (!files.length) { alert('No media to save'); return; }
                            const fileToSave = files[0];
                            const title = window.prompt('Save to collection — enter collection title');
                            if (!title) return;
                            const csrfInput = document.querySelector('#globalCsrfForm input[type=hidden]');
                            const fd = new FormData();
                            if (csrfInput && csrfInput.name) fd.append(csrfInput.name, csrfInput.value);
                            fd.append('action', 'create_and_add');
                            fd.append('title', title);
                            fd.append('project_file_id', fileToSave.id || fileToSave.file_id || 0);
                            try {
                                const resp = await fetch(apiBase + '/api/collections.php', { method: 'POST', body: fd, credentials: 'same-origin' });
                                const json = await resp.json();
                                if (json && json.success) {
                                    alert('Saved to collection');
                                } else {
                                    alert(json.error || 'Failed to save');
                                }
                            } catch (err) {
                                console.error(err);
                                alert('Network error');
                            }
                        };

                        // show modal
                        const modal = document.getElementById('projectModal');
                        modal.classList.remove('hidden');
                        modal.classList.add('flex');
                        // ensure we don't attach duplicate handlers
                        document.getElementById('projectModalOverlay').removeEventListener('click', closeProjectModal);
                        document.getElementById('modalCloseBtn').removeEventListener('click', closeProjectModal);
                        document.getElementById('projectModalOverlay').addEventListener('click', closeProjectModal);
                        document.getElementById('modalCloseBtn').addEventListener('click', closeProjectModal);
                    } catch (err) {
                        console.error(err);
                    }
                }

                function closeProjectModal() {
                    const modal = document.getElementById('projectModal');
                    modal.classList.remove('flex');
                    modal.classList.add('hidden');
                }

                async function appreciateProject(projectId) {
                    const csrfInput = document.querySelector('#globalCsrfForm input[type=hidden]');
                    const fd = new FormData();
                    if (csrfInput && csrfInput.name) fd.append(csrfInput.name, csrfInput.value);
                    fd.append('action', 'appreciate');
                    fd.append('project_id', projectId);

                    try {
                        const resp = await fetch(apiBase + '/api/projects.php', { method: 'POST', body: fd, credentials: 'same-origin' });
                        const json = await resp.json();
                        if (json && json.success) {
                            document.getElementById('modalLikesCount').innerText = json.count || 0;
                        } else if (json && json.error) {
                            if (resp.status === 400) {
                                window.location.href = '/login.php';
                            } else {
                                alert(json.error || 'Could not appreciate project');
                            }
                        }
                    } catch (err) {
                        console.error(err);
                    }
                }

                // Init
                document.addEventListener('DOMContentLoaded', function () {
                    renderGridStatus('<div class="text-center py-5"><div class="spinner-border text-secondary" role="status" aria-label="Loading projects"></div></div>');
                    loadMore();
                });
            </script>

            <div class="mt-20 text-center">
                <a href="contact_us.php" class="inline-block border-b border-[#731209] pb-1 text-sm tracking-widest uppercase hover:text-[#731209] transition-colors text-decoration-none"><?php echo esc($ct('catalog_button', 'Request Catalog')); ?></a>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/../Common/footer.php'; ?>
</body>
</html>