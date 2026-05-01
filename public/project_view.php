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
    'title' => $ct('page_title', 'Projects'),
    'description' => $ct('meta_description', 'Explore architecture and interior projects by Ripal Design.'),
    'image' => $fallbackCards[0]['image'],
    'url' => rd_public_url('project_view.php'),
    'active' => 'projects',
]);
?>
<main id="main">
    <section class="hero">
        <div class="hero-copy">
            <p class="eyebrow"><?php echo esc($ct('section_kicker', 'Projects')); ?></p>
            <h1><?php echo esc($ct('section_heading', 'Built work, documented clearly.')); ?></h1>
            <p>Browse selected residences, interiors, and institutional work. Each project is shown as a practical reference for scale, material direction, and site thinking.</p>
            <a class="button button-primary" href="<?php echo esc_attr(rd_public_url('contact_us.php')); ?>">Discuss a Similar Project</a>
        </div>
        <div class="hero-media">
            <figure><img src="<?php echo esc_attr($fallbackCards[0]['image']); ?>" alt="Featured Ripal Design project"<?php echo rd_content_image_style_attr($content, 'card_1_image'); ?>></figure>
        </div>
    </section>

    <section class="page-section">
        <div class="section-head">
            <div>
                <p class="eyebrow">Portfolio</p>
                <h2>Projects that show the decision quality.</h2>
            </div>
            <p id="projectStatus">Loading projects...</p>
        </div>
        <div class="project-grid" id="projectGrid" aria-live="polite"></div>
    </section>
</main>

<script>
    const apiBase = <?php echo json_encode(rtrim((string)BASE_PATH, '/')); ?>;
    const fallbackCards = <?php echo json_encode($fallbackCards); ?>;
    const grid = document.getElementById('projectGrid');
    const status = document.getElementById('projectStatus');

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"'`]/g, function (char) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','`':'&#x60;'})[char];
        });
    }

    function cardMarkup(project, index) {
        const cover = project.cover_image || project.image || fallbackCards[index % fallbackCards.length].image;
        const title = project.name || project.title || 'Project';
        const subtitle = project.location || project.owner_name || project.subtitle || 'Ripal Design';
        const href = project.id ? '#project-' + encodeURIComponent(project.id) : <?php echo json_encode(rd_public_url('contact_us.php')); ?>;
        return `
            <a class="project-card" href="${href}">
                <figure><img src="${escapeHtml(cover)}" alt="${escapeHtml(title)}" loading="lazy"></figure>
                <div class="project-card-body">
                    <p class="eyebrow">${String(index + 1).padStart(2, '0')} / ${escapeHtml(subtitle)}</p>
                    <h3>${escapeHtml(title)}</h3>
                    <p>View the project direction, scale, and architectural language.</p>
                </div>
            </a>
        `;
    }

    function render(items) {
        const data = items && items.length ? items : fallbackCards;
        grid.innerHTML = data.map(cardMarkup).join('');
        status.textContent = data.length + ' projects available';
    }

    fetch(apiBase + '/api/projects.php?limit=12&offset=0')
        .then(response => response.ok ? response.json() : Promise.reject())
        .then(data => render(data.projects || []))
        .catch(() => {
            render(fallbackCards);
            status.textContent = 'Showing sample projects. Refresh if the live portfolio is unavailable.';
        });
</script>
<?php rd_page_end(); ?>
