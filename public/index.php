<!doctype html>
<?php require_once __DIR__ . '/../includes/init.php'; ?>
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

$featuredProjects = db_connected() ? db_fetch_all('SELECT name, COALESCE(location, "") AS location FROM projects ORDER BY created_at DESC LIMIT 4') : [];
for ($i = count($featuredProjects); $i < 4; $i++) {
    $featuredProjects[] = ['name' => $ct('fallback_project_name', 'Project'), 'location' => ''];
}
?>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo esc($ct('page_title', 'Home | Ripal Design')); ?></title>
    <link rel="stylesheet" href="./css/index.css">
</head>

<body>
    <div class="grain"></div>

    <?php $HEADER_MODE = 'public';
    require_once __DIR__ . '/../includes/header.php'; ?>

    <main>
        <!-- Hero Section -->
        <section class="hero-section position-relative d-flex align-items-center justify-content-center overflow-hidden">
            <div class="hero-overlay"></div>
            <div class="position-relative z-2 text-center container px-4">
                <span class="tracking-architect text-primary-brand mb-3 d-block" style="font-size: 30px; text-shadow: 2px 2px 5px black;"><?php echo esc($ct('hero_established', 'Est. 2017')); ?></span>
                <h1 class="display-1 mb-4"><?php echo esc($ct('hero_heading', "The Architect's Vision")); ?></h1>
                <p class="lead text-white-50 mx-auto" style="max-width: 650px; letter-spacing: 0.05em;">
                    <?php echo esc($ct('hero_subheading', 'Precision in every measurement. Excellence in every build. Bridging the creative gap between design and reality.')); ?>
                </p>
                <div class="mt-5 pt-4">
                    <div class="vstack gap-2 align-items-center">
                        <span class="tracking-architect opacity-50"><?php echo esc($ct('hero_hint', 'Discovery')); ?></span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Projects carousel below main image -->
        <section id="projectsCarouselSection" class="py-5 bg-black mt-4">
            <div class="container" style="max-width: 100vw;">
                <div class="carousel" id="projectsCarousel">
                    <div class="carousel-track" id="projectsTrack">
                        <div class="carousel-slide"><img src="<?php echo esc_attr($ctImage('carousel_image_1', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg')); ?>" alt="<?php echo esc_attr($ct('carousel_alt_1', 'Project image 1')); ?>"></div>
                        <div class="carousel-slide"><img src="<?php echo esc_attr($ctImage('carousel_image_2', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.51 PM.jpeg')); ?>" alt="<?php echo esc_attr($ct('carousel_alt_2', 'Project image 2')); ?>"></div>
                        <div class="carousel-slide"><img src="<?php echo esc_attr($ctImage('carousel_image_3', '/assets/Content/WhatsApp Image 2026-02-02 at 5.43.21 PM (1).jpeg')); ?>" alt="<?php echo esc_attr($ct('carousel_alt_3', 'Project image 3')); ?>"></div>
                        <div class="carousel-slide"><img src="<?php echo esc_attr($ctImage('carousel_image_4', '/assets/Content/WhatsApp Image 2026-02-02 at 5.51.43 PM.jpeg')); ?>" alt="<?php echo esc_attr($ct('carousel_alt_4', 'Project image 4')); ?>"></div>
                    </div>
                    <button class="carousel-button" id="projectsPrev" style="left:12px"><span class="material-symbols-outlined">&lt; </span></button>
                    <button class="carousel-button" id="projectsNext" style="right:12px"><span class="material-symbols-outlined">&gt; </span></button>
                </div>
            </div>
        </section>

        <!-- Our Story -->
        <section class="py-5 py-lg-9">
            <div class="container py-5">
                <div class="row gx-lg-5 align-items-center">
                    <div class="col-lg-5 mb-5 mb-lg-0">
                        <h2 class="display-3 mb-4"><?php echo esc($ct('story_heading_line', 'Duality in')); ?><br><span class="text-primary-brand"><?php echo esc($ct('story_heading_highlight', 'Execution')); ?></span></h2>
                        <div style="width: 40px; height: 1px; background: var(--primary);" class="mb-3"></div>
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
        <section class="featured-projects-section bg-black">
            <!-- Project 1 - Image Left -->
            <div class="project-showcase">
                <div class="project-showcase-image project-image-left">
                    <img src="<?php echo esc_attr($ctImage('featured_image_1', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg')); ?>" alt="<?php echo esc_attr($ct('featured_image_alt_1', 'Featured project image 1')); ?>" >
                </div>
                <div class="project-showcase-content project-content-right">
                    <div class="project-showcase-inner">
                        <span class="project-number text-primary-brand">01</span>
                        <h2 class="project-title display-4 mb-4"><?php echo htmlspecialchars((string)$featuredProjects[0]['name']); ?></h2>
                        <div style="width: 50px; height: 1px; background: var(--primary);" class="mb-4"></div>
                        <p class="project-description text-white-50 mb-5">
                            <?php echo nl2br(esc($ct('project_1_description', 'A masterpiece of modern residential architecture in the heart of Rajkot, redefining spatial excellence through minimalist precision.'))); ?>
                        </p>
                        <a href="services.php" class="project-link text-white text-decoration-none d-inline-flex align-items-center">
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
                        <div style="width: 50px; height: 1px; background: var(--primary);" class="mb-4"></div>
                        <p class="project-description text-white-50 mb-5">
                            <?php echo nl2br(esc($ct('project_2_description', 'A landmark in Jam Khambhalia, bridging the gap between Tradition and contemporary living with breathable structure.'))); ?>
                        </p>
                        <a href="services.php" class="project-link text-white text-decoration-none d-inline-flex align-items-center">
                            <span class="me-2"><?php echo esc($ct('project_link_label', 'View Project')); ?></span>
                            <span class="project-arrow">→</span>
                        </a>
                    </div>
                </div>
                <div class="project-showcase-image project-image-right">
                    <img src="<?php echo esc_attr($ctImage('featured_image_2', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.51 PM.jpeg')); ?>" alt="<?php echo esc_attr($ct('featured_image_alt_2', 'Featured project image 2')); ?>">
                </div>
            </div>

            <!-- Project 3 - Image Left -->
            <div class="project-showcase">
                <div class="project-showcase-image project-image-left">
                    <img src="<?php echo esc_attr($ctImage('featured_image_3', '/assets/Content/WhatsApp Image 2026-02-02 at 5.43.21 PM (1).jpeg')); ?>" alt="<?php echo esc_attr($ct('featured_image_alt_3', 'Featured project image 3')); ?>">
                </div>
                <div class="project-showcase-content project-content-right">
                    <div class="project-showcase-inner">
                        <span class="project-number text-primary-brand">03</span>
                        <h2 class="project-title display-4 mb-4"><?php echo htmlspecialchars((string)$featuredProjects[2]['name']); ?></h2>
                        <div style="width: 50px; height: 1px; background: var(--primary);" class="mb-4"></div>
                        <p class="project-description text-white-50 mb-5">
                            <?php echo nl2br(esc($ct('project_3_description', "State-of-the-art Multi-Institutional System integrated into Rajkot's burgeoning urban landscape."))); ?>
                        </p>
                        <a href="services.php" class="project-link text-white text-decoration-none d-inline-flex align-items-center">
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
                        <div style="width: 50px; height: 1px; background: var(--primary);" class="mb-4"></div>
                        <p class="project-description text-white-50 mb-5">
                            <?php echo nl2br(esc($ct('project_4_description', "Industrial refinement meeting contemporary aesthetics in the heart of India's ceramic capital."))); ?>
                        </p>
                        <a href="services.php" class="project-link text-white text-decoration-none d-inline-flex align-items-center">
                            <span class="me-2"><?php echo esc($ct('project_link_label', 'View Project')); ?></span>
                            <span class="project-arrow">→</span>
                        </a>
                    </div>
                </div>
                <div class="project-showcase-image project-image-right">
                    <img src="<?php echo esc_attr($ctImage('featured_image_4', '/assets/Content/WhatsApp Image 2026-02-02 at 5.51.43 PM.jpeg')); ?>" alt="<?php echo esc_attr($ct('featured_image_alt_4', 'Featured project image 4')); ?>">
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
                        <div style="width: 60px; height: 1px; background: var(--primary); margin: 0 auto;" class="mb-3"></div>
                        <p class="tracking-architect text-white-50"><?php echo esc($ct('testimonials_subheading', 'Voices from our collaborative journey')); ?></p>
                    </div>
                </div>

                <!-- Testimonials Grid -->
                <div class="row g-4">
                    <!-- Testimonial 1 -->
                    <div class="col-12 col-lg-4">
                        <div class="testimonial-card h-100 bg-dark border-0 p-4 p-lg-5" style="background: #111 !important; transition: all 0.4s ease;">
                            <div class="testimonial-image mb-4 overflow-hidden" style="height: 250px;">
                                <img src="<?php echo esc_attr($ctImage('testimonial_image_1', '/assets/Content/WhatsApp Image 2026-02-02 at 5.51.43 PM (1).jpeg')); ?>"
                                    alt="<?php echo esc_attr($ct('testimonial_image_alt_1', 'Client project 1')); ?>"
                                    class="w-100 h-100 object-fit-cover"
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
                            </div>
                        </div>
                    </div>

                    <!-- Testimonial 2 -->
                    <div class="col-12 col-lg-4">
                        <div class="testimonial-card h-100 bg-dark border-0 p-4 p-lg-5" style="background: #111 !important; transition: all 0.4s ease;">
                            <div class="testimonial-image mb-4 overflow-hidden" style="height: 250px;">
                                <img src="<?php echo esc_attr($ctImage('testimonial_image_2', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg')); ?>"
                                    alt="<?php echo esc_attr($ct('testimonial_image_alt_2', 'Client project 2')); ?>"
                                    class="w-100 h-100 object-fit-cover"
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
                            </div>
                        </div>
                    </div>

                    <!-- Testimonial 3 -->
                    <div class="col-12 col-lg-4">
                        <div class="testimonial-card h-100 bg-dark border-0 p-4 p-lg-5" style="background: #111 !important; transition: all 0.4s ease;">
                            <div class="testimonial-image mb-4 overflow-hidden" style="height: 250px;">
                                <img src="<?php echo esc_attr($ctImage('testimonial_image_3', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.51 PM.jpeg')); ?>"
                                    alt="<?php echo esc_attr($ct('testimonial_image_alt_3', 'Client project 3')); ?>"
                                    class="w-100 h-100 object-fit-cover"
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
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php
    // enqueue page scripts so Common/footer.php can render them in the footer
    asset_enqueue_js('https://code.jquery.com/jquery-3.7.1.min.js');
    asset_enqueue_js('https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js');
    asset_enqueue_js('https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js');
    asset_enqueue_js('https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js');
    asset_enqueue_js('/js/index.js');
    ?>
    <?php require_once __DIR__ . '/../Common/footer.php'; ?>

    <!-- Dependencies -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="./js/index.js"></script>
    <script>

    </script>
</body>

</html>