<!doctype html>
<?php require_once __DIR__ . '/../includes/init.php'; ?>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Home - Ripal Design</title>
    <link rel="stylesheet" href="./css/index.css">
</head>

<body>
    <div class="grain"></div>

    <?php $HEADER_MODE = 'public'; require_once __DIR__ . '/../includes/header.php'; ?>

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
                        <div style="width: 1px; height: 80px; background: linear-gradient(to bottom, var(--primary), transparent);"></div>
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
                            Founded by two brothers — a designer and a builder — we bridge creative ambition with practical delivery.
                        </p>
                        <p class="text-white-50">
                            Our combined experience across municipal, institutional, and private works ensures designs that stand up to real-world constraints while remaining beautiful and timeless. We eliminate the gap between concept and creation by controlling the measure of every detail.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Projects cards — simplified row of cards for consistent layout -->
        <section class="projects-cards-section py-5 bg-black">
            <div class="container py-5">
                <div class="row g-4">
                    <!-- card 1 -->
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="card bg-dark text-white h-100 border-0">
                            <div class="overflow-hidden">
                                <img src="../assets/Content/WhatsApp%20Image%202026-02-02%20at%205.02.50%20PM.jpeg" width="100%" height="20%" alt="Obsidian Mono">
                            </div>
                            <div class="card-body">
                                <span class="text-primary-brand fw-semibold">01</span>
                                <h5 class="card-title mt-2">Obsidian Mono</h5>
                                <p class="card-text text-muted small">A monolith of light and shadow, redefining the urban residential experience.</p>
                                <a href="#" class="text-primary text-decoration-none">View Project</a>
                            </div>
                        </div>
                    </div>
                    <!-- card 2 -->
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="card bg-dark text-white h-100 border-0">
                            <div class="overflow-hidden">
                                <img src="../assets/Content/WhatsApp%20Image%202026-02-02%20at%205.02.51%20PM.jpeg" width="20%" height="20%" alt="Oasis Pavilion">
                            </div>
                            <div class="card-body">
                                <span class="text-primary-brand fw-semibold">02</span>
                                <h5 class="card-title mt-2">Oasis Pavilion</h5>
                                <p class="card-text text-muted small">Bridging the gap between tradition and future technology with breathable structure.</p>
                                <a href="#" class="text-primary text-decoration-none">View Project</a>
                            </div>
                        </div>
                    </div>
                    <!-- crad 3 -->
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="card bg-dark text-white h-100 border-0">
                            <div class="overflow-hidden">
                                <img src="../assets/Content/WhatsApp%20Image%202026-02-02%20at%205.43.21%20PM%20%281%29.jpeg" width="20%" height="20%" alt="Vertical Zen">
                            </div>
                            <div class="card-body">
                                <span class="text-primary-brand fw-semibold">03</span>
                                <h5 class="card-title mt-2">Vertical Zen</h5>
                                <p class="card-text text-muted small mt-4">Urban biophilia integrated into a modular high-rise system.</p>
                                <a href="#" class="text-primary text-decoration-none">View Project</a>
                            </div>
                        </div>
                    </div>
                    <!-- card 4 -->
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="card bg-dark text-white h-100 border-0">
                            <div class="overflow-hidden">
                                <img src="../assets/Content/WhatsApp%20Image%202026-02-02%20at%205.51.43%20PM.jpeg" width="20%" height="20%" alt="Loft VII">
                            </div>
                            <div class="card-body">
                                <span class="text-primary-brand fw-semibold">04</span>
                                <h5 class="card-title mt-2">Loft VII</h5>
                                <p class="card-text text-muted small">Raw industrial heritage meeting contemporary refinement.</p>
                                <a href="#" class="text-primary text-decoration-none">View Project</a>
                            </div>
                        </div>
                    </div>
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
                                <h6 class="text-white fw-bold mb-1">Jonathan Vance</h6>
                                <p class="text-uppercase tracking-architect mb-0" style="font-size: 0.7rem; color: var(--primary); letter-spacing: 0.15em;">
                                    CEO, Skyward Holdings
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
                                    "They pushed the boundaries of what we thought was possible, creating a space that feels both intimate and grand."
                                </p>
                            </blockquote>
                            <div class="pt-4 border-top" style="border-color: var(--primary) !important;">
                                <h6 class="text-white fw-bold mb-1">Elena Rodriguez</h6>
                                <p class="text-uppercase tracking-architect mb-0" style="font-size: 0.7rem; color: var(--primary); letter-spacing: 0.15em;">
                                    Private Collector
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
                                <h6 class="text-white fw-bold mb-1">Marcus Thorne</h6>
                                <p class="text-uppercase tracking-architect mb-0" style="font-size: 0.7rem; color: var(--primary); letter-spacing: 0.15em;">
                                    Director, Urban Planning NYC
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