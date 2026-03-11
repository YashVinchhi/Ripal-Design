<!doctype html>
<?php require_once __DIR__ . '/../includes/init.php'; ?>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Home | Ripal Design</title>
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
                <span class="tracking-architect text-primary-brand mb-3 d-block" style="font-size: 30px; text-shadow: 2px 2px 5px black;">Est. 2017</span>
                <h1 class="display-1 mb-4">The Architect's Vision</h1>
                <p class="lead text-white-50 mx-auto" style="max-width: 650px; letter-spacing: 0.05em;">
                    Precision in every measurement. Excellence in every build. Bridging the creative gap between design and reality.
                </p>
                <div class="mt-5 pt-4">
                    <div class="vstack gap-2 align-items-center">
                        <span class="tracking-architect opacity-50">Discovery</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Projects carousel below main image -->
        <section id="projectsCarouselSection" class="py-5 bg-black mt-4">
            <div class="container" style="max-width: 100vw;">
                <div class="carousel" id="projectsCarousel">
                    <div class="carousel-track" id="projectsTrack">
                        <div class="carousel-slide"><img src="../assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg" alt="P1"></div>
                        <div class="carousel-slide"><img src="../assets/Content/WhatsApp Image 2026-02-02 at 5.02.51 PM.jpeg" alt="P2"></div>
                        <div class="carousel-slide"><img src="../assets/Content/WhatsApp Image 2026-02-02 at 5.43.21 PM (1).jpeg" alt="P3"></div>
                        <div class="carousel-slide"><img src="../assets/Content/WhatsApp Image 2026-02-02 at 5.51.43 PM.jpeg" alt="P4"></div>
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
                        <h2 class="display-3 mb-4">Duality in<br><span class="text-primary-brand">Execution</span></h2>
                        <div style="width: 40px; height: 1px; background: var(--primary);" class="mb-3"></div>
                        <p class="tracking-architect opacity-75">The Ripal Approach</p>
                    </div>
                    <div class="col-lg-7">
                        <p class="lead text-white-50 mb-4" style="font-size: 1.4rem; font-weight: 300;">
                            Founded by two brothers - A Designer and A Builder, we bridge creative ambition with practical delivery.
                        </p>
                        <p class="text-white-50">
                            Our combined experience across municipal, institutional, and private works ensures designs that stand up to real-world constraints while remaining beautiful and timeless. We eliminate the gap between concept and creation by controlling the measure of every detail.
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
                    <img src="../assets/Content/WhatsApp%20Image%202026-02-02%20at%205.02.50%20PM.jpeg" alt="Obsidian Mono" >
                </div>
                <div class="project-showcase-content project-content-right">
                    <div class="project-showcase-inner">
                        <span class="project-number text-primary-brand">01</span>
                        <h2 class="project-title display-4 mb-4">Shanti Sadan</h2>
                        <div style="width: 50px; height: 1px; background: var(--primary);" class="mb-4"></div>
                        <p class="project-description text-white-50 mb-5">
                            A masterpiece of modern residential architecture in the heart of Rajkot, <br>redefining spatial excellence through minimalist precision.
                        </p>
                        <a href="services.php" class="project-link text-white text-decoration-none d-inline-flex align-items-center">
                            <span class="me-2">View Project</span>
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
                        <h2 class="project-title display-4 mb-4">Matru Ashish</h2>
                        <div style="width: 50px; height: 1px; background: var(--primary);" class="mb-4"></div>
                        <p class="project-description text-white-50 mb-5">
                            A landmark in Jam Khambhalia, bridging the gap between <br> Tradition and contemporary living with breathable structure.
                        </p>
                        <a href="services.php" class="project-link text-white text-decoration-none d-inline-flex align-items-center">
                            <span class="me-2">View Project</span>
                            <span class="project-arrow">→</span>
                        </a>
                    </div>
                </div>
                <div class="project-showcase-image project-image-right">
                    <img src="../assets/Content/WhatsApp%20Image%202026-02-02%20at%205.02.51%20PM.jpeg" alt="Oasis Pavilion">
                </div>
            </div>

            <!-- Project 3 - Image Left -->
            <div class="project-showcase">
                <div class="project-showcase-image project-image-left">
                    <img src="../assets/Content/WhatsApp%20Image%202026-02-02%20at%205.43.21%20PM%20%281%29.jpeg" alt="Vertical Zen">
                </div>
                <div class="project-showcase-content project-content-right">
                    <div class="project-showcase-inner">
                        <span class="project-number text-primary-brand">03</span>
                        <h2 class="project-title display-4 mb-4">Rajkot Smart City Plaza</h2>
                        <div style="width: 50px; height: 1px; background: var(--primary);" class="mb-4"></div>
                        <p class="project-description text-white-50 mb-5">
                            State-of-the-art Multi-Institutional System integrated <br>into Rajkot's burgeoning urban landscape.
                        </p>
                        <a href="services.php" class="project-link text-white text-decoration-none d-inline-flex align-items-center">
                            <span class="me-2">View Project</span>
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
                        <h2 class="project-title display-4 mb-4">Morbi Ceramic Hub</h2>
                        <div style="width: 50px; height: 1px; background: var(--primary);" class="mb-4"></div>
                        <p class="project-description text-white-50 mb-5">
                            Industrial refinement meeting contemporary <br> aesthetics in the heart of India's ceramic capital.
                        </p>
                        <a href="services.php" class="project-link text-white text-decoration-none d-inline-flex align-items-center">
                            <span class="me-2">View Project</span>
                            <span class="project-arrow">→</span>
                        </a>
                    </div>
                </div>
                <div class="project-showcase-image project-image-right">
                    <img src="../assets/Content/WhatsApp%20Image%202026-02-02%20at%205.51.43%20PM.jpeg" alt="Loft VII">
                </div>
            </div>
        </section>

        <!-- Client Perspectives Section -->
        <section class="testimonials-section py-5 py-lg-9 bg-black border-top border-bottom" style="border-color: rgba(255,255,255,0.05) !important;">
            <div class="container py-5">
                <!-- Section Header -->
                <div class="row mb-5">
                    <div class="col-lg-8 mx-auto text-center">
                        <h2 class="display-3 mb-4">Client Perspectives</h2>
                        <div style="width: 60px; height: 1px; background: var(--primary); margin: 0 auto;" class="mb-3"></div>
                        <p class="tracking-architect text-white-50">Voices from our collaborative journey</p>
                    </div>
                </div>

                <!-- Testimonials Grid -->
                <div class="row g-4">
                    <!-- Testimonial 1 -->
                    <div class="col-12 col-lg-4">
                        <div class="testimonial-card h-100 bg-dark border-0 p-4 p-lg-5" style="background: #111 !important; transition: all 0.4s ease;">
                            <div class="testimonial-image mb-4 overflow-hidden" style="height: 250px;">
                                <img src="../assets/Content/WhatsApp%20Image%202026-02-02%20at%205.51.43%20PM%20%281%29.jpeg"
                                    alt="Project"
                                    class="w-100 h-100 object-fit-cover"
                                    style="opacity: 0.6; transition: opacity 0.4s ease;" />
                            </div>
                            <blockquote class="mb-4">
                                <p class="fst-italic text-white-50 fs-5 lh-base" style="font-family: 'Cormorant Garamond', serif;">
                                    "The surgical precision of their design language transformed our site into a masterpiece of modern architecture."
                                </p>
                            </blockquote>
                            <div class="pt-4 border-top" style="border-color: var(--primary) !important;">
                                <h6 class="text-white fw-bold mb-1">Amitbhai Patel</h6>
                                <p class="text-uppercase tracking-architect mb-0" style="font-size: 0.7rem; color: var(--primary); letter-spacing: 0.15em;">
                                    Chairman, Rajkot Realty Group
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Testimonial 2 -->
                    <div class="col-12 col-lg-4">
                        <div class="testimonial-card h-100 bg-dark border-0 p-4 p-lg-5" style="background: #111 !important; transition: all 0.4s ease;">
                            <div class="testimonial-image mb-4 overflow-hidden" style="height: 250px;">
                                <img src="../assets/Content/WhatsApp%20Image%202026-02-02%20at%205.02.50%20PM.jpeg"
                                    alt="Project"
                                    class="w-100 h-100 object-fit-cover"
                                    style="opacity: 0.6; transition: opacity 0.4s ease;" />
                            </div>
                            <blockquote class="mb-4">
                                <p class="fst-italic text-white-50 fs-5 lh-base" style="font-family: 'Cormorant Garamond', serif;">
                                    "They pushed the boundaries of what we thought was possible, creating a space that feels both Intimate and Grand."
                                </p>
                            </blockquote>
                            <div class="pt-4 border-top" style="border-color: var(--primary) !important;">
                                <h6 class="text-white fw-bold mb-1">Anilbhai Sharma</h6>
                                <p class="text-uppercase tracking-architect mb-0" style="font-size: 0.7rem; color: var(--primary); letter-spacing: 0.15em;">
                                    Founder, Khambhalia Arts
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Testimonial 3 -->
                    <div class="col-12 col-lg-4">
                        <div class="testimonial-card h-100 bg-dark border-0 p-4 p-lg-5" style="background: #111 !important; transition: all 0.4s ease;">
                            <div class="testimonial-image mb-4 overflow-hidden" style="height: 250px;">
                                <img src="../assets/Content/WhatsApp%20Image%202026-02-02%20at%205.02.51%20PM.jpeg"
                                    alt="Project"
                                    class="w-100 h-100 object-fit-cover"
                                    style="opacity: 0.6; transition: opacity 0.4s ease;" />
                            </div>
                            <blockquote class="mb-4">
                                <p class="fst-italic text-white-50 fs-5 lh-base" style="font-family: 'Cormorant Garamond', serif;">
                                    "Deeply committed to sustainability without compromising on aesthetic excellence. Truly leaders in the new era."
                                </p>
                            </blockquote>
                            <div class="pt-4 border-top" style="border-color: var(--primary) !important;">
                                <h6 class="text-white fw-bold mb-1">Sureshbhai</h6>
                                <p class="text-uppercase tracking-architect mb-0" style="font-size: 0.7rem; color: var(--primary); letter-spacing: 0.15em;">
                                    Director, Regional Urban Planning
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
    asset_enqueue_js('/public/index.js');
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