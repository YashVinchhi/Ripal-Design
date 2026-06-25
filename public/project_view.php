<?php
require_once __DIR__ . '/../Common/public_shell.php';

$content = function_exists('public_content_page_values') ? public_content_page_values('project_view') : [];
$ct = static fn ($key, $default = '') => (string)($content[$key] ?? $default);
$image = static fn ($key, $default) => rd_content_image($content, $key, $default);

$fallbackCards = [
    ['image' => $image('card_1_image', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg'), 'title' => $ct('card_1_title', 'New Palace'), 'subtitle' => $ct('card_1_subtitle', 'Residential Architecture')],
    ['image' => $image('card_2_image', '/assets/Content/WhatsApp Image 2026-02-02 at 5.43.21 PM (1).jpeg'), 'title' => $ct('card_2_title', 'Lanka Skyline Towers'), 'subtitle' => $ct('card_2_subtitle', 'Urban Housing')],
    ['image' => $image('card_3_image', '/assets/Content/WhatsApp Image 2026-02-02 at 5.51.43 PM.jpeg'), 'title' => $ct('card_3_title', 'Rameshwaram Retreat'), 'subtitle' => $ct('card_3_subtitle', 'Coastal Residence')],
];

rd_page_start([
    'title' => $ct('page_title', 'Architecture Projects in Rajkot & Gujarat | Ripal Design'),
    'description' => $ct('meta_description', 'Explore architecture and interior projects by Ripal Design in Rajkot and across Gujarat.'),
    'image' => $fallbackCards[0]['image'],
    'url' => rd_public_url('project_view.php'),
    'active' => 'projects',
]);
?>
<main id="main">
    

    <section class="page-section">
        <div class="section-head">
            <div>
                <p class="eyebrow">Portfolio</p>
                <h2>Projects that show the decision quality.</h2>
            </div>
            <p id="projectStatus">Loading projects...</p>
        </div>
        <div class="filter-bar" id="filterBar">
            <div class="filter-chips" id="filterChips" role="list"></div>
        </div>
        <div class="project-grid" id="projectGrid" aria-live="polite"></div>
        <div id="gridEnd" aria-hidden="true" style="height:1px"></div>
    </section>
</main>

<style>
    /* Dense, edge-to-edge masonry */
    .page-section { padding: 12px 8px 48px; }
    .filter-bar { position: sticky; top: 0; z-index: 50; display:flex; align-items:center; gap:12px; padding:8px 6px; margin-bottom:8px; }
    .filter-bar { background: rgba(255,255,255,0.55); backdrop-filter: blur(6px); border-radius:8px; }
    .filter-chips { display:flex; gap:8px; flex-wrap:wrap; }
    .filter-chip { background: rgba(0,0,0,0.06); border-radius:4px; padding:6px 10px; font-size:13px; cursor:pointer; color:#111; transition:all .18s ease; border:1px solid rgba(0,0,0,0.06); }
    .filter-chip.active { background: linear-gradient(180deg, rgba(255,255,255,0.08), rgba(0,0,0,0.06)); box-shadow: 0 4px 14px rgba(0,0,0,0.08); }

    .project-grid {
        column-width: 260px;
        column-gap: 12px;
        width: 100%;
    }
    .project-card {
        display: inline-block;
        width: 100%;
        margin: 0 0 12px;
        break-inside: avoid;
        background: transparent;
        color: inherit;
        text-decoration: none;
        border-radius: 6px;
        overflow: visible;
        position: relative;
    }
    .project-card figure { margin:0; display:block; position:relative; overflow:hidden; border-radius:8px; }
    .project-card img, .project-card video { display:block; width:100%; height:auto; vertical-align:middle; }

    /* Hover overlay (glassmorphism) */
    .overlay { position:absolute; left:0; right:0; bottom:0; padding:12px; transform:translateY(100%); transition:all .22s ease; background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(0,0,0,0.45)); color:#fff; backdrop-filter: blur(6px); }
    .overlay h3 { margin:0; font-size:16px; }
    .overlay p { margin:6px 0 0; font-size:13px; opacity:0.9 }
    .project-card:hover .overlay, .project-card:focus .overlay { transform:translateY(0%); }

    /* Minimal caption beneath each card when focused on small screens */
    .project-card-body { display:none; }

    /* Lightbox */
    .lightbox { position:fixed; inset:0; background:rgba(0,0,0,0.85); display:flex; align-items:center; justify-content:center; z-index:1100; }
    .lightbox.hidden { display:none; }
    .lightbox-content { max-width:1100px; width:95%; max-height:90vh; overflow:hidden; position:relative; }
    .lightbox-media { width:100%; height:100%; display:flex; align-items:center; justify-content:center; }
    .lightbox-media img, .lightbox-media video { max-width:100%; max-height:90vh; }
    .lightbox-close, .lightbox-prev, .lightbox-next { position:absolute; top:12px; background:rgba(255,255,255,0.08); color:#fff; border:0; padding:8px 10px; border-radius:4px; cursor:pointer }
    .lightbox-close { right:12px }
    .lightbox-prev { left:12px; top:50%; transform:translateY(-50%); }
    .lightbox-next { right:12px; top:50%; transform:translateY(-50%); }

    @media (max-width:700px) {
        .project-grid { column-width: 180px; }
        .filter-bar { position:relative; }
    }
</style>

<!-- Lightbox markup -->
<div id="lightbox" class="lightbox hidden" aria-hidden="true">
    <div class="lightbox-content" role="dialog" aria-modal="true">
        <button class="lightbox-close" id="lightboxClose" aria-label="Close">✕</button>
        <button class="lightbox-prev" id="lightboxPrev" aria-label="Previous">◀</button>
        <button class="lightbox-next" id="lightboxNext" aria-label="Next">▶</button>
        <div class="lightbox-media" id="lightboxMedia"></div>
        <div id="lightboxCaption" style="color:#fff;padding:10px 12px;background:rgba(0,0,0,0.25);font-size:14px"></div>
    </div>
</div>

<script nonce="<?php echo htmlspecialchars($_REQUEST['csp_nonce'] ?? ''); ?>">
    const apiBase = <?php echo json_encode(rtrim((string)BASE_PATH, '/')); ?>;
    const fallbackCards = <?php echo json_encode($fallbackCards); ?>;
    const grid = document.getElementById('projectGrid');
    const status = document.getElementById('projectStatus');
    const filterChips = document.getElementById('filterChips');
    const gridEnd = document.getElementById('gridEnd');

    let projectsCache = [];
    let previewMap = {};
    let offset = 0;
    const limit = 24;
    let loading = false;
    let activeTag = 'all';

    const suggestedTags = ['all','residential','commercial','interior','urban','renovation','sustainable','luxury'];

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>\"'`]/g, function (char) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;',"'":'&#39;','`':'&#x60;'})[char];
        });
    }

    const allowedImageExt = /\.(jpe?g|png|gif|webp|svg)$/i;
    const allowedVideoExt = /\.(mp4|webm|ogg)$/i;
    const allowed3DExt = /\.(glb|gltf|usdz|ply|splat|obj|fbx)$/i;

    function mediaTag(item) {
        if (!item) return '';
        const src = escapeHtml(item.src || item.file_path || '');
        const type = (item.type || item.media_type || item.mediaType || '').toLowerCase();
        // prefer explicit video/3D detection, then fallback to extension
        if (type.includes('video') || allowedVideoExt.test(src)) {
            const poster = escapeHtml(item.poster || '');
            return `<video controls preload="metadata" ${poster?`poster="${poster}"`:''}><source src="${src}"></video>`;
        }
        if (type.includes('image') || allowedImageExt.test(src)) {
            return `<img src="${src}" alt="${escapeHtml(item.title || '')}" loading="lazy">`;
        }
        // 3D files: show a poster if available, otherwise a generic 3D thumbnail
        if (type.includes('model') || allowed3DExt.test(src) || type.includes('3d')) {
            const poster = escapeHtml(item.poster || item.thumbnail || '');
            if (poster) return `<img src="${poster}" alt="3D preview" loading="lazy">`;
            return `<div style="display:flex;align-items:center;justify-content:center;height:200px;background:#111;color:#fff;font-weight:600">3D</div>`;
        }
        return '';
    }

    function cardMarkup(project, index, preview) {
        const title = project.name || project.title || 'Project';
        const subtitle = project.location || project.owner_name || project.subtitle || project.category || 'Ripal Design';
        const id = project.id || project.ID || project.project_id || '';
        const previewHtml = mediaTag(preview);
        return `
            <a class="project-card" href="#" data-project-id="${escapeHtml(id)}">
                <figure>
                    ${previewHtml}
                    <div class="overlay" aria-hidden="true">
                        <h3>${escapeHtml(title)}</h3>
                        <p>${escapeHtml(subtitle)}</p>
                    </div>
                </figure>
                <div class="project-card-body">
                    <p class="eyebrow">${String(index + 1).padStart(2, '0')} / ${escapeHtml(subtitle)}</p>
                    <h3 style="display:none">${escapeHtml(title)}</h3>
                </div>
            </a>
        `;
    }

    function openLightbox(files, startIndex, project) {
        const lb = document.getElementById('lightbox');
        const media = document.getElementById('lightboxMedia');
        const caption = document.getElementById('lightboxCaption');
        let index = startIndex || 0;

        function renderCurrent() {
            const item = files[index];
            media.innerHTML = mediaTag(item);
            caption.textContent = `${project.name || project.title || ''} — ${index+1} / ${files.length}`;
        }

        document.getElementById('lightboxClose').onclick = () => { lb.classList.add('hidden'); lb.setAttribute('aria-hidden','true'); };
        document.getElementById('lightboxPrev').onclick = () => { index = (index-1+files.length)%files.length; renderCurrent(); };
        document.getElementById('lightboxNext').onclick = () => { index = (index+1)%files.length; renderCurrent(); };

        lb.classList.remove('hidden'); lb.setAttribute('aria-hidden','false'); renderCurrent();
    }

    async function fetchProjectFiles(projectId) {
        try {
            const resp = await fetch(apiBase + '/api/projects.php?id=' + encodeURIComponent(projectId));
            if (!resp.ok) return null;
            const data = await resp.json();
            const raw = (data.files || []);
            const files = raw.map(f => ({
                src: (f.file_path || '') && String(f.file_path).startsWith('/') ? (apiBase + f.file_path) : (f.file_path || ''),
                media_type: (f.media_type || f.type || '').toLowerCase(),
                title: f.name || '',
                poster: f.poster || f.thumbnail || ''
            })).filter(item => {
                const s = (item.src || '').toString();
                // allow image, video, or 3D
                if (!s) return false;
                if (allowedImageExt.test(s)) return true;
                if (allowedVideoExt.test(s)) return true;
                if (allowed3DExt.test(s)) return true;
                const mt = (item.media_type || '').toLowerCase();
                if (mt.includes('image')||mt.includes('panorama')||mt.includes('video')||mt.includes('model')||mt.includes('3d')) return true;
                return false;
            });
            return files.length ? files : null;
        } catch (e) {
            return null;
        }
    }

    function renderFilters() {
        filterChips.innerHTML = suggestedTags.map(t => `<button role="listitem" class="filter-chip ${t===activeTag? 'active':''}" data-tag="${t}">${t[0].toUpperCase()+t.slice(1)}</button>`).join('');
        filterChips.querySelectorAll('.filter-chip').forEach(btn => btn.addEventListener('click', () => {
            activeTag = btn.getAttribute('data-tag');
            filterChips.querySelectorAll('.filter-chip').forEach(b=>b.classList.toggle('active', b===btn));
            renderGrid();
        }));
    }

    function getPreviewForProject(id) { return previewMap[id] || null; }

    function renderGrid() {
        const filtered = projectsCache.filter(p => {
            if (!activeTag || activeTag === 'all') return true;
            const cat = (p.category || p.project_type || '').toString().toLowerCase();
            if (cat && cat.includes(activeTag)) return true;
            // fallback: check title/description
            const txt = (p.name || p.title || p.location || '').toString().toLowerCase();
            return txt.includes(activeTag);
        });

        const html = filtered.map((p, i) => cardMarkup(p, i, getPreviewForProject(p.id))).join('');
        grid.innerHTML = html;
        status.textContent = filtered.length + ' projects';

        grid.querySelectorAll('.project-card').forEach((el, idx) => {
            el.addEventListener('click', async (ev) => {
                ev.preventDefault();
                const pid = el.getAttribute('data-project-id');
                const files = await fetchProjectFiles(pid) || (getPreviewForProject(pid) ? [getPreviewForProject(pid)] : []);
                const project = projectsCache.find(x=>String(x.id)===String(pid)) || {};
                if (files && files.length) openLightbox(files, 0, project);
                else window.location.href = <?php echo json_encode(rd_public_url('contact_us.php')); ?>;
            });
        });
    }

    async function loadPage() {
        if (loading) return; loading = true; status.textContent = 'Loading...';
        try {
            const resp = await fetch(apiBase + '/api/projects.php?limit=' + limit + '&offset=' + offset);
            if (!resp.ok) throw new Error('no list');
            const json = await resp.json();
            const projects = json.projects || [];
            if (!projects.length) { observer.disconnect(); status.textContent = 'All projects loaded'; loading = false; return; }

            // append
            projects.forEach(p => projectsCache.push(p));

            // fetch previews for new projects in parallel but don't block render entirely
            await Promise.all(projects.map(async (p) => {
                const files = await fetchProjectFiles(p.id || p.ID || p.project_id);
                if (files && files.length) {
                    const vid = files.find(f => (f.media_type||'').includes('video') || (f.src||'').match(/\.(mp4|webm|ogg)(\?|$)/i));
                    const img = files.find(f => (f.media_type||'').includes('image') || (f.src||'').match(/\.(jpe?g|png|gif|webp)(\?|$)/i));
                    previewMap[p.id] = vid ? vid : (img ? img : files[0]);
                } else if (p.cover_image) {
                    previewMap[p.id] = { src: p.cover_image, media_type: 'image' };
                } else {
                    previewMap[p.id] = null;
                }
            }));

            offset += projects.length;
            renderGrid();
        } catch (e) {
            // fallback to static cards
            projectsCache = fallbackCards.map((c,i)=>({ id: 'f'+i, name: c.title, cover_image: c.image, location: c.subtitle }));
            fallbackCards.forEach((c,i)=> previewMap['f'+i] = { src: c.image, media_type: 'image' });
            renderGrid();
            status.textContent = 'Showing sample projects. Refresh if the live portfolio is unavailable.';
            observer.disconnect();
        } finally {
            loading = false;
        }
    }

    // infinite-scroll observer
    const observer = new IntersectionObserver((entries)=>{
        entries.forEach(en=>{ if (en.isIntersecting && !loading) loadPage(); });
    }, { rootMargin: '600px' });

    // init
    renderFilters();
    loadPage().then(()=> observer.observe(gridEnd));
</script>
<?php rd_page_end(); ?>