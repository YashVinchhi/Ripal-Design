<!doctype html>
<?php require_once __DIR__ . '/../includes/init.php'; ?>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>About Us | Ripal Design</title>

    <link rel="stylesheet" href="./css/about_us.css">
</head>

<body>
    <div class="grain"></div>

    <?php $HEADER_MODE = 'public'; require_once __DIR__ . '/../includes/header.php'; ?>

    <main>
        <!-- Hero Section -->
        <section class="hero-section position-relative d-flex align-items-center justify-content-center overflow-hidden">
            <div class="hero-overlay"></div>
            <div class="position-relative z-2 text-center container px-4">
                <span class="tracking-architect text-primary-brand mb-3 d-block" style="    font-size: 30px; text-shadow: 2px 2px 5px black;">Est. 2017</span>
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

        <!-- Timeline Section -->
        <section class="timeline-section">
            <div class="container mb-5 pb-5 text-center">
                <span class="tracking-architect text-primary-brand">Milestones</span>
                <h2 class="display-4 font-serif mt-2">The Measure of Success</h2>
            </div>

            <div class="container-fluid px-lg-5">
                <div class="tape-wrapper position-relative mx-auto" style="width: 90%; height: 250px;">
                    <!-- Tape Case -->
                    <div class="tape-body position-absolute start-0 top-50 translate-middle-y z-3">
                        <div class="tape-case d-flex align-items-center justify-content-center">
                            <img src="../assets/Content/Logo.png" alt="Ripal Design Logo" style="height:2.5rem;">
                        </div>
                    </div>

                    <!-- Tape Strip Container -->
                    <div class="tape-strip-container position-absolute top-50 translate-middle-y z-2 w-100" style="padding-left: 120px;">
                        <div class="tape-strip d-flex align-items-center" id="tapeStrip" style="width: 0%;">
                            <div class="tape-ticks"></div>
                            <div class="tape-hook position-absolute end-0"></div>
                        </div>
                    </div>

                    <!-- Milestones -->
                    <div class="milestones-wrapper position-absolute h-100 w-75" style="top: 0; right: 5%;">
                        <!-- 2017 -->
                        <div class="milestone-marker position-absolute translate-middle-x" style="left: 0%; top: 50%;" data-pos="0">
                            <div class="year-box">
                                <h3 class="mb-0">2017</h3>
                                <span class="tracking-architect" style="font-size: 0.65rem; color: var(--primary);">Inception</span>
                            </div>
                            <div class="milestone-details shadow-lg">
                                <p class="small text-white mb-0">Firm established with a design-build model, bridging the gap between concept and execution.</p>
                            </div>
                        </div>

                        <!-- 2021 -->
                        <div class="milestone-marker position-absolute translate-middle-x" style="left: 50%; top: 50%;" data-pos="50">
                            <div class="year-box">
                                <h3 class="mb-0">2021</h3>
                                <span class="tracking-architect" style="font-size: 0.65rem; color: var(--primary);">Scale</span>
                            </div>
                            <div class="milestone-details shadow-lg">
                                <p class="small text-white mb-0">Expanded into municipal projects and grew the core team to handle larger scale operations.</p>
                            </div>
                        </div>

                        <!-- 2026 -->
                        <div class="milestone-marker position-absolute translate-middle-x" style="left: 100%; top: 50%;" data-pos="100">
                            <div class="year-box">
                                <h3 class="mb-0">2026</h3>
                                <span class="tracking-architect" style="font-size: 0.65rem; color: var(--primary);">Future</span>
                            </div>
                            <div class="milestone-details shadow-lg">
                                <p class="small text-white mb-0">Aiming for global consultancy status and integrating sustainable tech in every build.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="py-5 border-top border-secondary border-opacity-10">
            <div class="container">
                <div class="row g-0">
                    <div class="col-md-4 border-end border-secondary border-opacity-10">
                        <div class="stat-item text-center">
                            <div class="stat-number-bg">50</div>
                            <div class="stat-content">
                                <div class="display-3 font-serif mb-0">50+</div>
                                <div class="tracking-architect opacity-50">Projects Completed</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 border-end border-secondary border-opacity-10">
                        <div class="stat-item text-center">
                            <div class="stat-number-bg">09</div>
                            <div class="stat-content">
                                <div class="display-3 font-serif mb-0">09</div>
                                <div class="tracking-architect opacity-50">Years Experience</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-item text-center">
                            <div class="stat-number-bg">100</div>
                            <div class="stat-content">
                                <div class="display-3 font-serif mb-0">100%</div>
                                <div class="tracking-architect opacity-50">Precision Rate</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-5 bg-white text-black text-center">
            <div class="container py-5">
                <h2 class="display-3 font-serif mb-4">Build the Extraordinary</h2>
                <p class="lead mb-5 opacity-75">Ready to start your next project with Ripal Design?</p>
                <a href="contact_us.php" class="btn btn-primary rounded-0 px-5 py-3 tracking-architect" style="background:#731209;border-color:#731209;color:#fff;">Contact Our Studio</a>
            </div>
        </section>
    </main>

    <?php
        asset_enqueue_js('https://code.jquery.com/jquery-3.7.1.min.js');
        asset_enqueue_js('https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js');
        asset_enqueue_js('https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js');
        asset_enqueue_js('https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js');
        asset_enqueue_js('/public/about_us.js');
    ?>
    <?php require_once __DIR__ . '/../Common/footer.php'; ?>

    <!-- Dependencies -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="./js/about_us.js"></script>
</body>

</html>