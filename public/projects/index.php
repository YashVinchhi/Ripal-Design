<?php
require_once __DIR__ . '/../../Common/public_shell.php';

// Projects router: supports listing when no slug provided, and project detail when slug present.
// Deploy with a rewrite rule: /projects/{slug} -> /public/projects/index.php

$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$basePrefix = rtrim(PUBLIC_PATH_PREFIX, '/');
$basePrefix = $basePrefix === '/' ? '' : $basePrefix;

// Attempt to extract slug from PATH_INFO-style URL: /public_prefix/projects/{slug}
$slug = '';
// Remove base path and leading segments
$segments = array_values(array_filter(explode('/', $path)));
// find 'projects' segment and take next as slug
$pos = array_search('projects', $segments);
if ($pos !== false && isset($segments[$pos + 1])) {
    $slug = $segments[$pos + 1];
}

// Fallback to query param
if ($slug === '') {
    $slug = trim((string)($_GET['slug'] ?? ''));
}

if ($slug === '') {
    // No slug — fallback to main projects listing
    header('Location: ' . rd_public_url('project_view.php'));
    exit;
}

$db = get_db();
$stmt = $db->prepare('SELECT * FROM projects WHERE (slug = ? OR id = ?) AND is_published = 1' . projects_soft_delete_sql('projects', ' AND ') . ' LIMIT 1');
$stmt->execute([$slug, (int)$slug]);
$project = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$project) {
    http_response_code(404);
    rd_page_start(['title' => 'Project not found | Ripal Design', 'description' => 'Project not found', 'active' => 'projects']);
    echo '<main id="main"><section class="page-section"><h1>Project not found</h1><p>The requested project could not be found.</p></section></main>';
    rd_page_end();
    exit;
}

$projectId = (int)$project['id'];
$imagesStmt = $db->prepare('SELECT file_path, name FROM project_files WHERE project_id = ? AND is_public = 1 AND (media_type = "IMAGE" OR media_type = "PANORAMA") ORDER BY sort_order ASC, uploaded_at DESC LIMIT 8');
$imagesStmt->execute([$projectId]);
$images = $imagesStmt->fetchAll(PDO::FETCH_ASSOC);

$title = sprintf('%s in %s — %s Project', ($project['project_type'] ?: 'Architecture'), ($project['location'] ?: 'Rajkot'), $project['name']);
$meta = substr(trim((string)($project['address'] ?? $project['location'] ?? 'A Ripal Design project')), 0, 160);

rd_page_start([
    'title' => $title . ' | Ripal Design',
    'description' => $meta,
    'image' => !empty($images[0]['file_path']) ? rd_content_image([], 'unused', $images[0]['file_path']) : rd_asset_url('assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg'),
    'url' => rd_public_url('projects/' . $project['slug']),
    'active' => 'projects',
]);
?>
<main id="main">
    <section class="page-section">
        <div class="section-head">
            <p class="eyebrow">Project</p>
            <h1><?php echo esc($title); ?></h1>
            <p class="project-meta">Location: <?php echo esc($project['location'] ?: 'Rajkot'); ?> &nbsp; • &nbsp; Type: <?php echo esc($project['project_type'] ?: 'Architecture'); ?> &nbsp; • &nbsp; Completed: <?php echo esc($project['due'] ?: ($project['published_at'] ?? '')); ?></p>
        </div>

        <div class="project-content">
            <div class="project-intro">
                <?php
                // Generate a descriptive paragraph (~200+ words) from available fields
                $descParts = [];
                $descParts[] = sprintf('%s is a %s located in %s. Designed and executed by Ripal Design, this project demonstrates our approach to practical and durable architecture that responds to local climate, client needs, and constructability.', $project['name'], ($project['project_type'] ?: 'residential architecture'), ($project['location'] ?: 'Rajkot'));
                $descParts[] = 'The scope included site analysis, architectural planning, detailed drawings, material selection, and on-site coordination during execution. We prioritized clear documentation and regular site reviews so the design intent was maintained through construction.';
                $materials = []; if (!empty($project['material_list'])) { $materials = array_map('trim', explode(',', $project['material_list'])); }
                if ($materials) {
                    $descParts[] = 'Key materials and finishes included ' . implode(', ', $materials) . ', chosen for durability and aesthetic coherence.';
                } else {
                    $descParts[] = 'Materials were selected for durability and local availability, with attention to finishes and maintenance.';
                }
                $descParts[] = 'The project was delivered with a focus on functional layouts, natural ventilation, and integrated lighting design. Our team coordinated with local contractors to maintain timeline and budget discipline.';
                $desc = implode(' ', $descParts);
                // Ensure at least ~200 words: append templated content if short
                if (str_word_count($desc) < 200) {
                    $desc .= ' Ripal Design creates measured, buildable architecture that looks beautiful and performs well. We document decisions clearly for contractors and maintain close communication with clients during execution. The result is a project that balances aesthetics, function, and cost, with a smooth handover at completion.';
                }
                ?>
                <p><?php echo esc($desc); ?></p>
            </div>

            <div class="project-gallery">
                <?php if ($images): foreach ($images as $img):
                    $src = $img['file_path'];
                    $alt = trim((string)($img['name'] ?: $project['name'] . ' photo'));
                    $fullSrc = (strpos($src, '/') === 0) ? rtrim((string)BASE_PATH, '/') . $src : $src;
                ?>
                    <figure>
                        <img src="<?php echo esc_attr($fullSrc); ?>" alt="<?php echo esc_attr($alt); ?>" loading="lazy">
                        <figcaption><?php echo esc($alt); ?></figcaption>
                    </figure>
                <?php endforeach; else: ?>
                    <p>No public images available for this project.</p>
                <?php endif; ?>
            </div>

            <div class="project-cta">
                <p>Interested to learn more about this project or commission similar work? <a href="<?php echo esc_attr(rd_public_url('contact_us.php')); ?>">Contact Ripal Design</a> for a consultation.</p>
            </div>
        </div>
    </section>
</main>

<?php
// Add project-specific JSON-LD
$schema = [
    '@context' => 'https://schema.org',
    '@type' => 'CreativeWork',
    'name' => (string)$project['name'],
    'description' => strip_tags($desc),
    'datePublished' => $project['published_at'] ?? null,
    'dateModified' => $project['updated_at'] ?? null,
    'location' => (string)($project['location'] ?? ''),
    'projectType' => (string)($project['project_type'] ?? ''),
];
if ($images) {
    $schema['image'] = array_map(function($i){ return (strpos($i['file_path'],'/')===0? rtrim((string)BASE_PATH,'/').$i['file_path']:$i['file_path']; }, $images);
}
echo "<script type=\"application/ld+json\">\n" . json_encode($schema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) . "\n</script>\n";

rd_page_end();
