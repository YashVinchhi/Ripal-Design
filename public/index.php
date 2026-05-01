<?php
require_once __DIR__ . '/../Common/public_shell.php';

if (function_exists('redirect_authenticated_user_to_dashboard')) {
    redirect_authenticated_user_to_dashboard();
}

$content = function_exists('public_content_page_values') ? public_content_page_values('index') : [];
$ct = static fn ($key, $default = '') => (string)($content[$key] ?? $default);
$image = static fn ($key, $default) => rd_content_image($content, $key, $default);

$featuredProjects = db_connected()
    ? db_fetch_all("SELECT id, name, COALESCE(location, '') AS location FROM projects WHERE LOWER(name) NOT LIKE '%test%' ORDER BY created_at DESC LIMIT 6")
    : [];

$fallbackProjects = [
    ['id' => 0, 'name' => $ct('fallback_project_1_name', 'New Palace'), 'location' => $ct('fallback_project_1_location', 'Rajkot')],
    ['id' => 0, 'name' => $ct('fallback_project_2_name', 'Sish Gadh'), 'location' => $ct('fallback_project_2_location', 'Jamkhambhalia')],
    ['id' => 0, 'name' => $ct('fallback_project_3_name', 'Lanka Skyline Towers'), 'location' => $ct('fallback_project_3_location', 'Rajkot')],
    ['id' => 0, 'name' => $ct('fallback_project_4_name', 'Rameshwaram Sea View Retreat'), 'location' => $ct('fallback_project_4_location', 'Coastal Residence')],
];

while (count($featuredProjects) < 4) {
    $featuredProjects[] = $fallbackProjects[count($featuredProjects)] ?? $fallbackProjects[0];
}

$heroImage = $image('hero_image_src', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg');
$heroVideo = trim($ct('hero_video_src', ''));

$carouselImages = [
    ['src' => $image('carousel_image_1', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg'), 'alt' => $ct('carousel_alt_1', 'Project image 1')],
    ['src' => $image('carousel_image_2', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.51 PM.jpeg'), 'alt' => $ct('carousel_alt_2', 'Project image 2')],
    ['src' => $image('carousel_image_3', '/assets/Content/WhatsApp Image 2026-02-02 at 5.43.21 PM (1).jpeg'), 'alt' => $ct('carousel_alt_3', 'Project image 3')],
    ['src' => $image('carousel_image_4', '/assets/Content/WhatsApp Image 2026-02-02 at 5.51.43 PM.jpeg'), 'alt' => $ct('carousel_alt_4', 'Project image 4')],
];

$projectImages = [
    ['src' => $image('featured_image_1', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg'), 'alt' => $ct('featured_image_alt_1', 'Featured project image 1')],
    ['src' => $image('featured_image_2', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.51 PM.jpeg'), 'alt' => $ct('featured_image_alt_2', 'Featured project image 2')],
    ['src' => $image('featured_image_3', '/assets/Content/WhatsApp Image 2026-02-02 at 5.43.21 PM (1).jpeg'), 'alt' => $ct('featured_image_alt_3', 'Featured project image 3')],
    ['src' => $image('featured_image_4', '/assets/Content/WhatsApp Image 2026-02-02 at 5.51.43 PM.jpeg'), 'alt' => $ct('featured_image_alt_4', 'Featured project image 4')],
];

$projectDescriptions = [
    $ct('project_1_description', 'Design details aligned with structure, budgets, and on-site delivery for a smooth handover.'),
    $ct('project_2_description', 'Every drawing is checked against site constraints, materials, and vendor timelines.'),
    $ct('project_3_description', 'Design details aligned with structure, budgets, and on-site delivery for a smooth handover.'),
    $ct('project_4_description', 'Every drawing is checked against site constraints, materials, and vendor timelines.'),
];

$companies = [
    $ct('company_1_name', 'Shivam Developers'),
    $ct('company_2_name', 'Rajkot Realty Group'),
    $ct('company_3_name', 'Khambhalia Arts'),
    $ct('company_4_name', 'Lanka Skyline'),
    $ct('company_5_name', 'Rameshwaram Resorts'),
    $ct('company_6_name', 'North Gate Schools'),
];

rd_page_start([
    'title' => $ct('page_title', 'Architecture that reaches the site intact'),
    'description' => $ct('meta_description', 'Ripal Design connects architecture, interiors, and execution for buildable projects in Gujarat.'),
    'image' => $heroImage,
    'url' => rd_public_url('index.php'),
    'active' => 'home',
    'body_class' => 'home-immersive',
]);
?>
<main id="main" class="snap-stack">
    <section class="snap-section hero-full" aria-label="Hero">
        <a class="hero-logo" href="<?php echo esc_attr(rd_public_url('index.php')); ?>" aria-label="Ripal Design home">
            <img src="<?php echo esc_attr(rd_asset_url('assets/Content/Logo.png')); ?>" alt="" width="44" height="44">
        </a>
        <div class="hero-overlay" aria-hidden="true"></div>
        <div class="hero-media">
            <?php if ($heroVideo !== ''): ?>
                <video class="hero-video" autoplay muted loop playsinline poster="<?php echo esc_attr($heroImage); ?>">
                    <source src="<?php echo esc_attr($heroVideo); ?>" type="video/mp4">
                </video>
            <?php else: ?>
                <img src="<?php echo esc_attr($heroImage); ?>" alt="<?php echo esc_attr($ct('hero_image_alt', 'Contemporary residence designed by Ripal Design')); ?>">
            <?php endif; ?>
        </div>
        <div class="hero-content">
            <p class="hero-eyebrow"><?php echo esc($ct('hero_established', 'Est. 2017')); ?> / <?php echo esc($ct('hero_location', 'Rajkot, Gujarat')); ?></p>
            <h1><?php echo esc($ct('hero_heading', 'Design that stays true from drawing to site.')); ?></h1>
            <p class="hero-lede"><?php echo esc($ct('hero_subheading', 'Architecture, interiors, and execution guided by one studio so nothing gets lost in translation.')); ?></p>
            <div class="hero-actions">
                <a class="button button-primary" href="<?php echo esc_attr(rd_public_url('contact_us.php')); ?>"><?php echo esc($ct('hero_primary_cta_label', 'Start Your Project')); ?></a>
                <a class="button button-secondary" href="<?php echo esc_attr(rd_public_url('project_view.php')); ?>"><?php echo esc($ct('hero_secondary_cta_label', 'View Projects')); ?></a>
            </div>
        </div>
        <div class="hero-scroll">
            <span><?php echo esc($ct('hero_scroll_label', 'Scroll')); ?></span>
            <i class="fa-solid fa-arrow-down" aria-hidden="true"></i>
        </div>
    </section>

    <section class="snap-section media-carousel" aria-label="Image carousel">
        <div class="carousel" data-carousel>
            <div class="carousel-track">
                <?php foreach ($carouselImages as $carouselImage): ?>
                    <div class="carousel-slide">
                        <img src="<?php echo esc_attr((string)$carouselImage['src']); ?>" alt="<?php echo esc_attr((string)$carouselImage['alt']); ?>">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="carousel-caption">
            <p><?php echo esc($ct('carousel_caption', 'Another view, another craft decision. Scroll to hold each frame in place.')); ?></p>
        </div>
    </section>

    <section class="projects-section" aria-label="Latest projects">
        <?php
        $projects = array_slice($featuredProjects, 0, 4);
        ?>
        <div class="projects-panel snap-section">
            <?php foreach (array_slice($projects, 0, 2) as $index => $project):
                $href = ((int)($project['id'] ?? 0) > 0) ? rd_public_url('project_view.php?id=' . (int)$project['id']) : rd_public_url('project_view.php');
                $isRight = $index % 2 === 1;
                $imageData = $projectImages[$index] ?? ['src' => $heroImage, 'alt' => 'Featured project image'];
            ?>
                <article class="project-row<?php echo $isRight ? ' is-right' : ''; ?>">
                    <div class="project-media">
                        <img src="<?php echo esc_attr((string)$imageData['src']); ?>" alt="<?php echo esc_attr((string)$imageData['alt']); ?>">
                    </div>
                    <div class="project-spacer" aria-hidden="true"></div>
                    <div class="project-content">
                        <p class="project-eyebrow"><?php echo esc($ct('projects_eyebrow_prefix', 'Latest')); ?> <?php echo str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT); ?> / <?php echo esc((string)($project['location'] ?? 'Gujarat')); ?></p>
                        <h2><?php echo esc((string)$project['name']); ?></h2>
                        <p><?php echo esc($projectDescriptions[$index] ?? ''); ?></p>
                        <a class="project-link" href="<?php echo esc_attr($href); ?>"><?php echo esc($ct('project_link_label', 'View project')); ?> &rarr;</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="projects-panel snap-section">
            <?php foreach (array_slice($projects, 2, 2) as $offset => $project):
                $index = $offset + 2;
                $href = ((int)($project['id'] ?? 0) > 0) ? rd_public_url('project_view.php?id=' . (int)$project['id']) : rd_public_url('project_view.php');
                $isRight = $index % 2 === 1;
                $imageData = $projectImages[$index] ?? ['src' => $heroImage, 'alt' => 'Featured project image'];
            ?>
                <article class="project-row<?php echo $isRight ? ' is-right' : ''; ?>">
                    <div class="project-media">
                        <img src="<?php echo esc_attr((string)$imageData['src']); ?>" alt="<?php echo esc_attr((string)$imageData['alt']); ?>">
                    </div>
                    <div class="project-spacer" aria-hidden="true"></div>
                    <div class="project-content">
                        <p class="project-eyebrow"><?php echo esc($ct('projects_eyebrow_prefix', 'Latest')); ?> <?php echo str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT); ?> / <?php echo esc((string)($project['location'] ?? 'Gujarat')); ?></p>
                        <h2><?php echo esc((string)$project['name']); ?></h2>
                        <p><?php echo esc($projectDescriptions[$index] ?? ''); ?></p>
                        <a class="project-link" href="<?php echo esc_attr($href); ?>"><?php echo esc($ct('project_link_label', 'View project')); ?> &rarr;</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="snap-section companies-section" aria-label="Companies we have worked with">
        <div class="section-intro">
            <h2><?php echo esc($ct('companies_heading', 'Companies we have worked with')); ?></h2>
            <p><?php echo esc($ct('companies_subheading', 'Trusted partnerships across residential, institutional, and commercial work.')); ?></p>
        </div>
        <div class="logo-marquee" aria-hidden="true">
            <div class="logo-track">
                <?php foreach ($companies as $company): ?>
                    <span><?php echo esc($company); ?></span>
                <?php endforeach; ?>
                <?php foreach ($companies as $company): ?>
                    <span><?php echo esc($company); ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="snap-section testimonials-section" aria-label="Client testimonials">
        <div class="section-intro">
            <h2><?php echo esc($ct('testimonials_heading', 'Client testimonials')); ?></h2>
            <p><?php echo esc($ct('testimonials_subheading', 'Short, clear communication that keeps expectations in sync with execution.')); ?></p>
        </div>
        <div class="testimonial-rotator" data-rotator>
            <article class="testimonial-card is-active">
                <blockquote><?php echo esc($ct('testimonial_1_quote', 'They made the design decisions understandable and kept the site team aligned from start to finish.')); ?></blockquote>
                <cite><?php echo esc($ct('testimonial_1_name', 'Amitbhai Patel')); ?>, <?php echo esc($ct('testimonial_1_role', 'Rajkot Realty Group')); ?></cite>
            </article>
            <article class="testimonial-card">
                <blockquote><?php echo esc($ct('testimonial_2_quote', 'The process felt structured, practical, and still creative. We always knew what came next.')); ?></blockquote>
                <cite><?php echo esc($ct('testimonial_2_name', 'Anilbhai Sharma')); ?>, <?php echo esc($ct('testimonial_2_role', 'Khambhalia Arts')); ?></cite>
            </article>
            <article class="testimonial-card">
                <blockquote><?php echo esc($ct('testimonial_3_quote', 'Fast drawings, honest budget calls, and a clean handover at the site.')); ?></blockquote>
                <cite><?php echo esc($ct('testimonial_3_name', 'Meera Joshi')); ?>, <?php echo esc($ct('testimonial_3_role', 'Private Residence')); ?></cite>
            </article>
        </div>
    </section>
</main>

<script>
    (function () {
        var carousel = document.querySelector('[data-carousel] .carousel-track');
        if (carousel) {
            var slides = Array.prototype.slice.call(carousel.children);
            var index = 0;
            setInterval(function () {
                index = (index + 1) % slides.length;
                carousel.style.transform = 'translateX(' + (-index * 100) + '%)';
            }, 4500);
        }

        var rotator = document.querySelector('[data-rotator]');
        if (rotator) {
            var cards = Array.prototype.slice.call(rotator.querySelectorAll('.testimonial-card'));
            var tIndex = 0;
            setInterval(function () {
                cards[tIndex].classList.remove('is-active');
                tIndex = (tIndex + 1) % cards.length;
                cards[tIndex].classList.add('is-active');
            }, 4200);
        }
    })();
</script>

<?php rd_page_end(); ?>
