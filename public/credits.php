<?php
require_once __DIR__ . '/../Common/public_shell.php';

$content = function_exists('public_content_page_values') ? public_content_page_values('credits') : [];
$ct = static fn ($key, $default = '') => (string)($content[$key] ?? $default);
$imageValue = static fn ($key, $default = '') => (string)($content[$key] ?? $default);
$image = static fn ($key, $default = '') => rd_content_image($content, $key, $default);
$splitList = static function (string $value): array {
    $items = preg_split('/\r\n|\r|\n/', $value) ?: [];
    $items = array_map(static fn ($item) => trim((string)$item), $items);
    return array_values(array_filter($items, static fn ($item) => $item !== ''));
};

$contributors = [
    [
        'name' => $ct('contributor_1_name', 'Your Name'),
        'role' => $ct('contributor_1_role', 'Co-Creator & Developer'),
        'photo_raw' => $imageValue('contributor_1_photo', ''),
        'photo' => $image('contributor_1_photo', ''),
        'index' => 1,
        'summary' => $ct('contributor_1_summary', 'Focused on building reliable project features, clean user flows, and practical frontend experiences for the platform.'),
        'skills' => $splitList($ct('contributor_1_skills', "Frontend page development\nBackend feature implementation\nDatabase planning\nAuthentication and user workflows\nTesting and debugging")),
        'technologies' => $splitList($ct('contributor_1_technologies', "HTML\nCSS\nJavaScript\nPHP\nMySQL\nGit")),
    ],
    [
        'name' => $ct('contributor_2_name', "Friend's Name"),
        'role' => $ct('contributor_2_role', 'Co-Creator & Developer'),
        'photo_raw' => $imageValue('contributor_2_photo', ''),
        'photo' => $image('contributor_2_photo', ''),
        'index' => 2,
        'summary' => $ct('contributor_2_summary', 'Focused on design details, feature refinement, content organization, and making the platform easier to use.'),
        'skills' => $splitList($ct('contributor_2_skills', "UI planning\nResponsive layout work\nProject feature testing\nContent management\nProblem solving and optimization")),
        'technologies' => $splitList($ct('contributor_2_technologies', "HTML\nCSS\nBootstrap\nJavaScript\nPHP\nMySQL")),
    ],
];

$photoExists = static function (string $path): bool {
    if ($path === '') {
        return false;
    }
    if (preg_match('#^https?://#i', $path)) {
        return true;
    }
    $relativePath = ltrim($path, '/');
    return is_file(PROJECT_ROOT . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
};

$initials = static function (string $name): string {
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';
    foreach ($parts as $part) {
        if ($part !== '') {
            $letters .= strtoupper(substr($part, 0, 1));
        }
        if (strlen($letters) >= 2) {
            break;
        }
    }
    return $letters !== '' ? $letters : 'RD';
};

rd_page_start([
    'title' => $ct('page_title', 'Credits'),
    'description' => $ct('meta_description', 'Meet the creators of this project and the skills and technologies behind the build.'),
    'image' => rd_asset_url('assets/Content/Logo.png'),
    'url' => rd_public_url('credits.php'),
    'active' => 'credits',
]);
?>
<main id="main" class="credits-page">
    <section class="hero credits-hero">
        <div class="hero-copy">
            <p class="eyebrow"><?php echo esc($ct('hero_kicker', 'Project Credits')); ?></p>
            <h1><?php echo esc($ct('hero_heading', 'Built with care by our team.')); ?></h1>
            <p><?php echo esc($ct('hero_subheading', 'This project was created through shared effort, practical learning, and hands-on development across design, frontend, backend, and database work.')); ?></p>
            <div class="hero-actions">
                <a class="button button-primary" href="#contributors"><?php echo esc($ct('hero_primary_cta', 'Meet the Creators')); ?></a>
                <a class="button button-secondary" href="<?php echo esc_attr(rd_public_url('project_view.php')); ?>"><?php echo esc($ct('hero_secondary_cta', 'View Projects')); ?></a>
            </div>
        </div>
        <div class="credits-hero-panel" aria-label="Project creators">
            <?php foreach ($contributors as $contributor): ?>
                <div class="credits-avatar-stack">
                    <?php if ($photoExists((string)$contributor['photo_raw'])): ?>
                        <img src="<?php echo esc_attr((string)$contributor['photo']); ?>" alt="<?php echo esc_attr((string)$contributor['name']); ?>"<?php echo rd_content_image_style_attr($content, 'contributor_' . (string)$contributor['index'] . '_photo'); ?>>
                    <?php else: ?>
                        <span aria-hidden="true"><?php echo esc($initials((string)$contributor['name'])); ?></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="page-section" id="contributors">
        <div class="section-head">
            <div>
                <p class="eyebrow"><?php echo esc($ct('section_kicker', 'Creators')); ?></p>
                <h2><?php echo esc($ct('section_heading', 'The people behind the build.')); ?></h2>
            </div>
            <p><?php echo esc($ct('section_description', 'Each contributor brought their own strengths to the project, from interface design and usability to backend logic and database structure.')); ?></p>
        </div>

        <div class="credits-grid">
            <?php foreach ($contributors as $contributor): ?>
                <article class="credit-card">
                    <div class="credit-photo">
                        <?php if ($photoExists((string)$contributor['photo_raw'])): ?>
                            <img src="<?php echo esc_attr((string)$contributor['photo']); ?>" alt="<?php echo esc_attr((string)$contributor['name']); ?>"<?php echo rd_content_image_style_attr($content, 'contributor_' . (string)$contributor['index'] . '_photo'); ?>>
                        <?php else: ?>
                            <span aria-hidden="true"><?php echo esc($initials((string)$contributor['name'])); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="credit-card-body">
                        <p class="eyebrow"><?php echo esc((string)$contributor['role']); ?></p>
                        <h3><?php echo esc((string)$contributor['name']); ?></h3>
                        <p><?php echo esc((string)$contributor['summary']); ?></p>

                        <div class="credit-list-block">
                            <h4><?php echo esc($ct('skills_heading', 'Skills')); ?></h4>
                            <ul class="credit-list">
                                <?php foreach ($contributor['skills'] as $skill): ?>
                                    <li><?php echo esc((string)$skill); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <div class="credit-list-block">
                            <h4><?php echo esc($ct('technologies_heading', 'Technologies')); ?></h4>
                            <ul class="tech-pill-list">
                                <?php foreach ($contributor['technologies'] as $technology): ?>
                                    <li><?php echo esc((string)$technology); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>
<?php rd_page_end(); ?>
