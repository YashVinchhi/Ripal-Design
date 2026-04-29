<!doctype html>
<?php require_once __DIR__ . '/../app/Core/Bootstrap/init.php'; ?>
<?php
if (function_exists('redirect_authenticated_user_to_dashboard')) {
    redirect_authenticated_user_to_dashboard();
}

$indexContent = function_exists('public_content_page_values') ? public_content_page_values('index') : [];
$ct = static function ($key, $default = '') use ($indexContent) {
    return (string)($indexContent[$key] ?? $default);
};
$ctImage = static function ($key, $default = '') use ($indexContent) {
    $value = (string)($indexContent[$key] ?? $default);
    if (function_exists('public_content_image_url')) {
        return (string)public_content_image_url($value, $default);
    }
    if (function_exists('base_path')) {
        return (string)base_path(ltrim((string)$value, '/'));
    }
    return (string)$value;
};

$featuredProjects = db_connected()
    ? db_fetch_all("SELECT id, name, COALESCE(location, '') AS location FROM projects WHERE LOWER(name) NOT LIKE '%test%' ORDER BY created_at DESC LIMIT 4")
    : [];
$fallbackNames = [
    'Skyline Courtyard Residence',
    'Jamkhambhalia Cultural Hall',
    'Rajkot Civic Annex',
    'Morbi Industrial Campus',
];
for ($i = count($featuredProjects); $i < 4; $i++) {
    $featuredProjects[] = [
        'id' => 0,
        'name' => $fallbackNames[$i] ?? $ct('fallback_project_name', 'Project'),
        'location' => '',
    ];
}

$heroProofStats = json_decode((string)HERO_PROOF_STATS, true);
if (!is_array($heroProofStats) || empty($heroProofStats)) {
    $heroProofStats = [
        ['value' => '50+', 'label' => 'Projects Delivered'],
        ['value' => '9+', 'label' => 'Years Experience'],
        ['value' => '100%', 'label' => 'Client Satisfaction'],
    ];
}
$estLabel = (string)$ct('hero_established', 'Est. 2017');
$estYear = 2017;
if (preg_match('/(19|20)\d{2}/', $estLabel, $matches)) {
    $estYear = (int)$matches[0];
}
$yearsExperience = max(1, (int)date('Y') - $estYear);
foreach ($heroProofStats as &$stat) {
    if (isset($stat['label']) && stripos((string)$stat['label'], 'year') !== false) {
        $stat['value'] = $yearsExperience . '+';
    }
}
unset($stat);

$heroCtaSuccess = !empty($_SESSION['hero_cta_success']);
$heroCtaError = (string)($_SESSION['hero_cta_error'] ?? '');
unset($_SESSION['hero_cta_success'], $_SESSION['hero_cta_error']);
?>
<html lang="en">

<head>
    <?php
    require_once __DIR__ . '/../includes/seo.php';
    require_once __DIR__ . '/../includes/schema.php';

    $page_data = [
        'title' => $ct('page_title', 'Home'),
        'description' => $ct('meta_description', 'Precision in every measurement. Excellence in every build.'),
        'image' => $ctImage('hero_image_src', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg'),
        'url' => rtrim((string)BASE_PATH, '/') . PUBLIC_PATH_PREFIX . '/index.php'
    ];
    render_seo_head($page_data);
    // Render site-level schema
    render_localbusiness_schema();
    render_breadcrumbs_schema();
    ?>
    <link rel="stylesheet" href="<?php echo esc_attr(rtrim((string)BASE_PATH, '/') . PUBLIC_PATH_PREFIX . '/css/index.css'); ?>">
</head>

<body>
    <div class="grain"></div>

    <?php
    // Dynamic title/meta for header include
    $pageTitle = $ct('page_title', 'Home') . ' | Ripal Design';
    $metaDesc = $ct('meta_description', 'Precision in every measurement. Excellence in every build.');
    $HEADER_MODE = 'public';
    require_once __DIR__ . '/../app/Ui/header.php'; ?>

    <main>
        <!-- Hero Section -->
        <section class="hero-section position-relative d-flex align-items-center justify-content-center overflow-hidden" style="--hero-image: url('<?php echo esc_attr($ctImage('hero_image_src', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg')); ?>');">
            <div class="hero-overlay"></div>
            <div class="position-relative z-2 text-center container px-4">
                <span data-stagger-entry class="tracking-architect text-primary-brand mb-3 d-block" style="font-size: var(--hero-est-font-size, 30px); text-shadow: 2px 2px 5px black;"><?php echo esc($ct('hero_established', 'Est. 2017')); ?></span>
                <h1 data-stagger-entry class="display-1 mb-4"><?php echo esc($ct('hero_heading', "The Architect's Vision")); ?></h1>
                <p data-stagger-entry class="lead text-white-50 mx-auto" style="max-width: var(--content-max-width, 650px); letter-spacing: 0.05em;">
                    <?php echo esc($ct('hero_subheading', 'Precision in every measurement. Excellence in every build. Bridging the creative gap between design and reality.')); ?>
                </p>
                <div data-stagger-entry class="mt-4" id="start-project">
                    <button type="button" id="openHeroQuickBrief" class="btn-hero">
                        Start Your Project <span class="ml-2" aria-hidden="true">&rarr;</span>
                    </button>
                </div>
                <div class="hero-proof d-flex gap-4 mt-3">
                    <?php foreach ($heroProofStats as $stat): ?>
                        <span><strong><?php echo esc($stat['value'] ?? ''); ?></strong> <?php echo esc($stat['label'] ?? ''); ?></span>
                    <?php endforeach; ?>
                </div>
                <div class="mt-5 pt-4">
                    <div class="vstack gap-2 align-items-center">
                        <span class="hero-scroll-cue"><span><?php echo esc($ct('hero_hint', 'Discovery')); ?></span><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Projects carousel below main image -->
        <section id="projectsCarouselSection" class="py-5 bg-black">
            <div class="container" style="max-width: 100vw;">
                <div class="carousel" id="projectsCarousel">
                    <div class="carousel-track" id="projectsTrack">
                        <div class="carousel-slide"><img src="<?php echo esc_attr($ctImage('carousel_image_1', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg')); ?>" alt="<?php echo esc_attr($ct('carousel_alt_1', 'Project image 1')); ?>" loading="lazy"></div>
                        <div class="carousel-slide"><img src="<?php echo esc_attr($ctImage('carousel_image_2', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.51 PM.jpeg')); ?>" alt="<?php echo esc_attr($ct('carousel_alt_2', 'Project image 2')); ?>" loading="lazy"></div>
                        <div class="carousel-slide"><img src="<?php echo esc_attr($ctImage('carousel_image_3', '/assets/Content/WhatsApp Image 2026-02-02 at 5.43.21 PM (1).jpeg')); ?>" alt="<?php echo esc_attr($ct('carousel_alt_3', 'Project image 3')); ?>" loading="lazy"></div>
                        <div class="carousel-slide"><img src="<?php echo esc_attr($ctImage('carousel_image_4', '/assets/Content/WhatsApp Image 2026-02-02 at 5.51.43 PM.jpeg')); ?>" alt="<?php echo esc_attr($ct('carousel_alt_4', 'Project image 4')); ?>" loading="lazy"></div>
                    </div>
                    <button class="carousel-button" id="projectsPrev" style="left:12px" aria-label="Previous slide"><i class="fa-solid fa-chevron-left"></i></button>
                    <button class="carousel-button" id="projectsNext" style="right:12px" aria-label="Next slide"><i class="fa-solid fa-chevron-right"></i></button>
                </div>
            </div>
        </section>

        <!-- Our Story -->
        <section class="py-5 py-lg-9 story-section" data-anim="reveal">
            <div class="container py-5">
                <div class="row gx-lg-5 align-items-center">
                    <div class="col-lg-5 mb-5 mb-lg-0">
                        <h2 class="display-3 mb-4"><?php echo esc($ct('story_heading_line', 'Duality in')); ?><br><span class="text-primary-brand"><?php echo esc($ct('story_heading_highlight', 'Execution')); ?></span></h2>
                        <div style="width: var(--section-divider-width, 40px); height: var(--section-divider-height, 1px); background: var(--primary);" class="mb-3"></div>
                        <p class="tracking-architect opacity-75"><?php echo esc($ct('story_kicker', 'The Ripal Approach')); ?></p>
                    </div>
                    <div class="col-lg-7">
                        <p class="lead text-white-50 mb-4" style="font-size: 1.4rem; font-weight: 300;">
                            <?php echo esc($ct('story_intro', 'Founded by two brothers - A Designer and A Builder, we bridge creative ambition with practical delivery.')); ?>
                        </p>
                        <p class="text-white-50">
                            <?php echo esc($ct('story_body', 'Our combined experience across municipal, institutional, and private works ensures designs that stand up to real-world constraints while remaining beautiful and timeless. We eliminate the gap between concept and creation by controlling the measure of every detail.')); ?>
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Featured Projects Section -->
        <section class="featured-projects-section bg-black" data-anim="reveal">
            <!-- Project 1 - Image Left -->
            <div class="project-showcase">
                <div class="project-showcase-image project-image-left">
                    <img src="<?php echo esc_attr($ctImage('featured_image_1', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg')); ?>" alt="<?php echo esc_attr($ct('featured_image_alt_1', 'Featured project image 1')); ?>" loading="lazy" >
                </div>
                <div class="project-showcase-content project-content-right">
                    <div class="project-showcase-inner">
                        <span class="project-number text-primary-brand">01</span>
                        <h2 class="project-title display-4 mb-4"><?php echo htmlspecialchars((string)$featuredProjects[0]['name']); ?></h2>
                        <div style="width: var(--section-divider-width, 40px); height: var(--section-divider-height, 1px); background: var(--primary);" class="mb-4"></div>
                        <p class="project-description text-white-50 mb-5">
                            <?php echo nl2br(esc($ct('project_1_description', 'A masterpiece of modern residential architecture in the heart of Rajkot, redefining spatial excellence through minimalist precision.'))); ?>
                        </p>
                        <a href="<?php echo esc_attr(((int)($featuredProjects[0]['id'] ?? 0) > 0) ? ('project_view.php?id=' . (int)$featuredProjects[0]['id']) : 'project_view.php'); ?>" class="project-link text-white text-decoration-none d-inline-flex align-items-center">
                            <span class="me-2"><?php echo esc($ct('project_link_label', 'View Project')); ?></span>
                            <span class="project-arrow">→</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Project 2 - Image Right -->
            <div class="project-showcase">
                <div class="project-showcase-content project-content-left">
                    <div class="project-showcase-inner">
                        <span class="project-number text-primary-brand">02</span>
                        <h2 class="project-title display-4 mb-4"><?php echo htmlspecialchars((string)$featuredProjects[1]['name']); ?></h2>
                        <div style="width: var(--section-divider-width, 40px); height: var(--section-divider-height, 1px); background: var(--primary);" class="mb-4"></div>
                        <p class="project-description text-white-50 mb-5">
                            <?php echo nl2br(esc($ct('project_2_description', 'A landmark in Jam Khambhalia, bridging the gap between Tradition and contemporary living with breathable structure.'))); ?>
                        </p>
                        <a href="<?php echo esc_attr(((int)($featuredProjects[1]['id'] ?? 0) > 0) ? ('project_view.php?id=' . (int)$featuredProjects[1]['id']) : 'project_view.php'); ?>" class="project-link text-white text-decoration-none d-inline-flex align-items-center">
                            <span class="me-2"><?php echo esc($ct('project_link_label', 'View Project')); ?></span>
                            <span class="project-arrow">→</span>
                        </a>
                    </div>
                </div>
                <div class="project-showcase-image project-image-right">
                    <img src="<?php echo esc_attr($ctImage('featured_image_2', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.51 PM.jpeg')); ?>" alt="<?php echo esc_attr($ct('featured_image_alt_2', 'Featured project image 2')); ?>" loading="lazy">
                </div>
            </div>

            <!-- Project 3 - Image Left -->
            <div class="project-showcase">
                <div class="project-showcase-image project-image-left">
                    <img src="<?php echo esc_attr($ctImage('featured_image_3', '/assets/Content/WhatsApp Image 2026-02-02 at 5.43.21 PM (1).jpeg')); ?>" alt="<?php echo esc_attr($ct('featured_image_alt_3', 'Featured project image 3')); ?>" loading="lazy">
                </div>
                <div class="project-showcase-content project-content-right">
                    <div class="project-showcase-inner">
                        <span class="project-number text-primary-brand">03</span>
                        <h2 class="project-title display-4 mb-4"><?php echo htmlspecialchars((string)$featuredProjects[2]['name']); ?></h2>
                        <div style="width: var(--section-divider-width, 40px); height: var(--section-divider-height, 1px); background: var(--primary);" class="mb-4"></div>
                        <p class="project-description text-white-50 mb-5">
                            <?php echo nl2br(esc($ct('project_3_description', "State-of-the-art Multi-Institutional System integrated into Rajkot's burgeoning urban landscape."))); ?>
                        </p>
                        <a href="<?php echo esc_attr(((int)($featuredProjects[2]['id'] ?? 0) > 0) ? ('project_view.php?id=' . (int)$featuredProjects[2]['id']) : 'project_view.php'); ?>" class="project-link text-white text-decoration-none d-inline-flex align-items-center">
                            <span class="me-2"><?php echo esc($ct('project_link_label', 'View Project')); ?></span>
                            <span class="project-arrow">→</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Project 4 - Image Right -->
            <div class="project-showcase">
                <div class="project-showcase-content project-content-left">
                    <div class="project-showcase-inner">
                        <span class="project-number text-primary-brand">04</span>
                        <h2 class="project-title display-4 mb-4"><?php echo htmlspecialchars((string)$featuredProjects[3]['name']); ?></h2>
                        <div style="width: var(--section-divider-width, 40px); height: var(--section-divider-height, 1px); background: var(--primary);" class="mb-4"></div>
                        <p class="project-description text-white-50 mb-5">
                            <?php echo nl2br(esc($ct('project_4_description', "Industrial refinement meeting contemporary aesthetics in the heart of India's ceramic capital."))); ?>
                        </p>
                        <a href="<?php echo esc_attr(((int)($featuredProjects[3]['id'] ?? 0) > 0) ? ('project_view.php?id=' . (int)$featuredProjects[3]['id']) : 'project_view.php'); ?>" class="project-link text-white text-decoration-none d-inline-flex align-items-center">
                            <span class="me-2"><?php echo esc($ct('project_link_label', 'View Project')); ?></span>
                            <span class="project-arrow">→</span>
                        </a>
                    </div>
                </div>
                <div class="project-showcase-image project-image-right">
                    <img src="<?php echo esc_attr($ctImage('featured_image_4', '/assets/Content/WhatsApp Image 2026-02-02 at 5.51.43 PM.jpeg')); ?>" alt="<?php echo esc_attr($ct('featured_image_alt_4', 'Featured project image 4')); ?>" loading="lazy">
                </div>
            </div>
        </section>

        <!-- Client Perspectives Section -->
        <section class="testimonials-section py-5 py-lg-9 bg-black border-top border-bottom" style="border-color: rgba(255,255,255,0.05) !important;">
            <div class="container py-5">
                <!-- Section Header -->
                <div class="row mb-5">
                    <div class="col-lg-8 mx-auto text-center">
                        <h2 class="display-3 mb-4"><?php echo esc($ct('testimonials_heading', 'Client Perspectives')); ?></h2>
                        <div style="width: var(--section-divider-width, 40px); height: var(--section-divider-height, 1px); background: var(--primary); margin: 0 auto;" class="mb-3"></div>
                        <p class="tracking-architect text-white-50"><?php echo esc($ct('testimonials_subheading', 'Voices from our collaborative journey')); ?></p>
                    </div>
                </div>

                <!-- Testimonials Grid -->
                <div class="row g-4">
                    <!-- Testimonial 1 -->
                    <div class="col-12 col-lg-4">
                        <div class="testimonial-card h-100 bg-dark border-0 p-4 p-lg-5" style="background: #111 !important; transition: all 0.4s ease;">
                            <div class="testimonial-image mb-4 overflow-hidden" style="height: var(--testimonial-image-height, 250px);">
                                <img src="<?php echo esc_attr($ctImage('testimonial_image_1', '/assets/Content/WhatsApp Image 2026-02-02 at 5.51.43 PM (1).jpeg')); ?>"
                                    alt="<?php echo esc_attr($ct('testimonial_image_alt_1', 'Client project 1')); ?>"
                                    class="w-100 h-100 object-fit-cover"
                                    loading="lazy"
                                    style="opacity: 0.6; transition: opacity 0.4s ease;" />
                            </div>
                            <blockquote class="mb-4">
                                <p class="fst-italic text-white-50 fs-5 lh-base" style="font-family: 'Cormorant Garamond', serif;">
                                    "<?php echo esc($ct('testimonial_1_quote', 'The surgical precision of their design language transformed our site into a masterpiece of modern architecture.')); ?>"
                                </p>
                            </blockquote>
                            <div class="pt-4 border-top" style="border-color: var(--primary) !important;">
                                <h6 class="text-white fw-bold mb-1"><?php echo esc($ct('testimonial_1_name', 'Amitbhai Patel')); ?></h6>
                                <p class="text-uppercase tracking-architect mb-0" style="font-size: 0.7rem; color: var(--primary); letter-spacing: 0.15em;">
                                    <?php echo esc($ct('testimonial_1_role', 'Chairman, Rajkot Realty Group')); ?>
                                </p>
                                <p class="text-white-50 mt-2 mb-0" style="font-size: 0.75rem; letter-spacing: 0.12em; text-transform: uppercase;">
                                    <?php echo esc($ct('testimonial_1_project', 'Project: Skyline Courtyard Residence')); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Testimonial 2 -->
                    <div class="col-12 col-lg-4">
                        <div class="testimonial-card h-100 bg-dark border-0 p-4 p-lg-5" style="background: #111 !important; transition: all 0.4s ease;">
                            <div class="testimonial-image mb-4 overflow-hidden" style="height: var(--testimonial-image-height, 250px);">
                                <img src="<?php echo esc_attr($ctImage('testimonial_image_2', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg')); ?>"
                                    alt="<?php echo esc_attr($ct('testimonial_image_alt_2', 'Client project 2')); ?>"
                                    class="w-100 h-100 object-fit-cover"
                                    loading="lazy"
                                    style="opacity: 0.6; transition: opacity 0.4s ease;" />
                            </div>
                            <blockquote class="mb-4">
                                <p class="fst-italic text-white-50 fs-5 lh-base" style="font-family: 'Cormorant Garamond', serif;">
                                    "<?php echo esc($ct('testimonial_2_quote', 'They pushed the boundaries of what we thought was possible, creating a space that feels both Intimate and Grand.')); ?>"
                                </p>
                            </blockquote>
                            <div class="pt-4 border-top" style="border-color: var(--primary) !important;">
                                <h6 class="text-white fw-bold mb-1"><?php echo esc($ct('testimonial_2_name', 'Anilbhai Sharma')); ?></h6>
                                <p class="text-uppercase tracking-architect mb-0" style="font-size: 0.7rem; color: var(--primary); letter-spacing: 0.15em;">
                                    <?php echo esc($ct('testimonial_2_role', 'Founder, Khambhalia Arts')); ?>
                                </p>
                                <p class="text-white-50 mt-2 mb-0" style="font-size: 0.75rem; letter-spacing: 0.12em; text-transform: uppercase;">
                                    <?php echo esc($ct('testimonial_2_project', 'Project: Jamkhambhalia Cultural Hall')); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Testimonial 3 -->
                    <div class="col-12 col-lg-4">
                        <div class="testimonial-card h-100 bg-dark border-0 p-4 p-lg-5" style="background: #111 !important; transition: all 0.4s ease;">
                            <div class="testimonial-image mb-4 overflow-hidden" style="height: var(--testimonial-image-height, 250px);">
                                <img src="<?php echo esc_attr($ctImage('testimonial_image_3', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.51 PM.jpeg')); ?>"
                                    alt="<?php echo esc_attr($ct('testimonial_image_alt_3', 'Client project 3')); ?>"
                                    class="w-100 h-100 object-fit-cover"
                                    loading="lazy"
                                    style="opacity: 0.6; transition: opacity 0.4s ease;" />
                            </div>
                            <blockquote class="mb-4">
                                <p class="fst-italic text-white-50 fs-5 lh-base" style="font-family: 'Cormorant Garamond', serif;">
                                    "<?php echo esc($ct('testimonial_3_quote', 'Deeply committed to sustainability without compromising on aesthetic excellence. Truly leaders in the new era.')); ?>"
                                </p>
                            </blockquote>
                            <div class="pt-4 border-top" style="border-color: var(--primary) !important;">
                                <h6 class="text-white fw-bold mb-1"><?php echo esc($ct('testimonial_3_name', 'Sureshbhai')); ?></h6>
                                <p class="text-uppercase tracking-architect mb-0" style="font-size: 0.7rem; color: var(--primary); letter-spacing: 0.15em;">
                                    <?php echo esc($ct('testimonial_3_role', 'Director, Regional Urban Planning')); ?>
                                </p>
                                <p class="text-white-50 mt-2 mb-0" style="font-size: 0.75rem; letter-spacing: 0.12em; text-transform: uppercase;">
                                    <?php echo esc($ct('testimonial_3_project', 'Project: Rajkot Civic Annex')); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <div id="heroQuickBriefModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/60 p-4" role="dialog" aria-modal="true" aria-labelledby="heroQuickBriefLabel">
        <div class="max-w-2xl w-full bg-[var(--color-brand-surface)] text-[var(--color-brand-light)] rounded-lg overflow-hidden shadow-lg">
            <div class="flex items-center justify-between p-4 border-b border-white/5">
                <h2 id="heroQuickBriefLabel" class="text-lg font-semibold">Start Your Project</h2>
                <button type="button" id="heroModalClose" class="text-white text-xl leading-none" aria-label="Close">&times;</button>
            </div>
            <div class="p-4">
                <?php if ($heroCtaSuccess): ?>
                    <div class="bg-green-600 text-white p-3 rounded mb-3">We'll be in touch within 24 hours.</div>
                <?php endif; ?>
                <?php if ($heroCtaError !== ''): ?>
                    <div class="bg-red-600 text-white p-3 rounded mb-3"><?php echo h($heroCtaError); ?></div>
                <?php endif; ?>
                <form method="post" action="<?php echo esc_attr(rtrim((string)BASE_PATH, '/') . PUBLIC_PATH_PREFIX . '/contact_us.php'); ?>" class="space-y-3">
                    <?php echo csrf_token_field(); ?>
                    <input type="hidden" name="submit" value="1">
                    <input type="hidden" name="source" value="hero_cta">
                    <div>
                        <label for="heroBriefName" class="block mb-1">Name</label>
                        <input id="heroBriefName" name="name" class="w-full border border-gray-300 rounded px-3 py-2" required>
                    </div>
                    <div>
                        <label for="heroBriefContact" class="block mb-1">Phone or Email</label>
                        <input id="heroBriefContact" name="contact" class="w-full border border-gray-300 rounded px-3 py-2" required>
                    </div>
                    <div>
                        <label for="heroBriefMessage" class="block mb-1">Brief Message</label>
                        <textarea id="heroBriefMessage" name="brief" class="w-full border border-gray-300 rounded px-3 py-2" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn-primary">Send Brief</button>
                </form>
            </div>
        </div>
    </div>

    <?php
    // enqueue page scripts so Common/footer.php can render them in the footer
    // index.js uses ES module imports; load as a module
    asset_enqueue_js('/js/index.js', ['type' => 'module']);
    asset_enqueue_js('/assets/js/modal.js', ['defer' => true]);
    ?>
    <?php require_once __DIR__ . '/../Common/footer.php'; ?>
        <script>
            (function () {
                if (!window.RDAnimations || !window.RDMotionPresets || typeof window.RDAnimations.initPageAnimations !== 'function') {
                    return;
                }

                window.RDAnimations.initPageAnimations('public-index-hero', document, function () {
                    var presets = window.RDMotionPresets;
                    var heroNodes = document.querySelectorAll('.hero-section [data-stagger-entry]');
                    if (heroNodes.length && typeof presets.staggeredEntry === 'function') {
                        presets.staggeredEntry(heroNodes, { distance: 22, duration: 0.48, stagger: 0.1 });
                    }
                });
            })();
        </script>
</body>

</html>