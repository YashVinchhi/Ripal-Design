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
    ['id' => 0, 'name' => 'New Palace', 'location' => 'Rajkot'],
    ['id' => 0, 'name' => 'Sish Gadh', 'location' => 'Jamkhambhalia'],
    ['id' => 0, 'name' => 'Lanka Skyline Towers', 'location' => 'Rajkot'],
    ['id' => 0, 'name' => 'Rameshwaram Sea View Retreat', 'location' => 'Coastal Residence'],
];

while (count($featuredProjects) < 4) {
    $featuredProjects[] = $fallbackProjects[count($featuredProjects)] ?? $fallbackProjects[0];
}

$heroImage = $image('hero_image_src', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg');
$heroVideo = trim($ct('hero_video_src', ''));
$stats = [
    ['value' => '50+', 'label' => 'completed projects'],
    ['value' => max(1, (int)date('Y') - 2017) . '+', 'label' => 'years in practice'],
    ['value' => '1', 'label' => 'team from design to site'],
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
            <p class="hero-eyebrow"><?php echo esc($ct('hero_established', 'Est. 2017')); ?> / Rajkot, Gujarat</p>
            <h1><?php echo esc($ct('hero_heading', 'Design that stays true from drawing to site.')); ?></h1>
            <p class="hero-lede"><?php echo esc($ct('hero_subheading', 'Architecture, interiors, and execution guided by one studio so nothing gets lost in translation.')); ?></p>
            <div class="hero-actions">
                <a class="button button-primary" href="<?php echo esc_attr(rd_public_url('contact_us.php')); ?>">Start Your Project</a>
                <a class="button button-secondary" href="<?php echo esc_attr(rd_public_url('project_view.php')); ?>">View Projects</a>
            </div>
        </div>
        <div class="hero-scroll">
            <span>Scroll</span>
            <i class="fa-solid fa-arrow-down" aria-hidden="true"></i>
        </div>
    </section>

    <section class="snap-section media-carousel" aria-label="Image carousel">
        <div class="carousel" data-carousel>
            <div class="carousel-track">
                <?php
                $carouselImages = [
                    $image('carousel_image_1', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg'),
                    $image('carousel_image_2', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.51 PM.jpeg'),
                    $image('carousel_image_3', '/assets/Content/WhatsApp Image 2026-02-02 at 5.43.21 PM (1).jpeg'),
                    $image('carousel_image_4', '/assets/Content/WhatsApp Image 2026-02-02 at 5.51.43 PM.jpeg'),
                ];
                foreach ($carouselImages as $carouselImage):
                ?>
                    <div class="carousel-slide">
                        <img src="<?php echo esc_attr($carouselImage); ?>" alt="Ripal Design project view">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="carousel-caption">
            <p>Another view, another craft decision. Scroll to hold each frame in place.</p>
        </div>
    </section>

    <section class="projects-section" aria-label="Latest projects">
        <?php
        $projectImages = [
            $image('featured_image_1', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg'),
            $image('featured_image_2', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.51 PM.jpeg'),
            $image('featured_image_3', '/assets/Content/WhatsApp Image 2026-02-02 at 5.43.21 PM (1).jpeg'),
            $image('featured_image_4', '/assets/Content/WhatsApp Image 2026-02-02 at 5.51.43 PM.jpeg'),
        ];
        $projects = array_slice($featuredProjects, 0, 4);
        ?>
        <div class="projects-panel snap-section">
            <?php foreach (array_slice($projects, 0, 2) as $index => $project):
                $href = ((int)($project['id'] ?? 0) > 0) ? rd_public_url('project_view.php?id=' . (int)$project['id']) : rd_public_url('project_view.php');
                $isRight = $index % 2 === 1;
                $imageSrc = $projectImages[$index] ?? $heroImage;
            ?>
                <article class="project-row<?php echo $isRight ? ' is-right' : ''; ?>">
                    <div class="project-media">
                        <img src="<?php echo esc_attr($imageSrc); ?>" alt="<?php echo esc_attr((string)$project['name']); ?>">
                    </div>
                    <div class="project-spacer" aria-hidden="true"></div>
                    <div class="project-content">
                        <p class="project-eyebrow">Latest <?php echo str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT); ?> / <?php echo esc((string)($project['location'] ?? 'Gujarat')); ?></p>
                        <h2><?php echo esc((string)$project['name']); ?></h2>
                        <p>Design details aligned with structure, budgets, and on-site delivery for a smooth handover.</p>
                        <a class="project-link" href="<?php echo esc_attr($href); ?>">View project &rarr;</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="projects-panel snap-section">
            <?php foreach (array_slice($projects, 2, 2) as $offset => $project):
                $index = $offset + 2;
                $href = ((int)($project['id'] ?? 0) > 0) ? rd_public_url('project_view.php?id=' . (int)$project['id']) : rd_public_url('project_view.php');
                $isRight = $index % 2 === 1;
                $imageSrc = $projectImages[$index] ?? $heroImage;
            ?>
                <article class="project-row<?php echo $isRight ? ' is-right' : ''; ?>">
                    <div class="project-media">
                        <img src="<?php echo esc_attr($imageSrc); ?>" alt="<?php echo esc_attr((string)$project['name']); ?>">
                    </div>
                    <div class="project-spacer" aria-hidden="true"></div>
                    <div class="project-content">
                        <p class="project-eyebrow">Latest <?php echo str_pad((string)($index + 1), 2, '0', STR_PAD_LEFT); ?> / <?php echo esc((string)($project['location'] ?? 'Gujarat')); ?></p>
                        <h2><?php echo esc((string)$project['name']); ?></h2>
                        <p>Every drawing is checked against site constraints, materials, and vendor timelines.</p>
                        <a class="project-link" href="<?php echo esc_attr($href); ?>">View project &rarr;</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="snap-section companies-section" aria-label="Companies we have worked with">
        <div class="section-intro">
            <h2>Companies we have worked with</h2>
            <p>Trusted partnerships across residential, institutional, and commercial work.</p>
        </div>
        <div class="logo-marquee" aria-hidden="true">
            <div class="logo-track">
                <span>Shivam Developers</span>
                <span>Rajkot Realty Group</span>
                <span>Khambhalia Arts</span>
                <span>Lanka Skyline</span>
                <span>Rameshwaram Resorts</span>
                <span>North Gate Schools</span>
                <span>Shivam Developers</span>
                <span>Rajkot Realty Group</span>
                <span>Khambhalia Arts</span>
                <span>Lanka Skyline</span>
                <span>Rameshwaram Resorts</span>
                <span>North Gate Schools</span>
            </div>
        </div>
    </section>

    <section class="snap-section testimonials-section" aria-label="Client testimonials">
        <div class="section-intro">
            <h2>Client testimonials</h2>
            <p>Short, clear communication that keeps expectations in sync with execution.</p>
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
