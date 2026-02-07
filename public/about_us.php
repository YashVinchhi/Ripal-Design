<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>About Us - Ripal Design</title>
    
    <!-- Typography & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --primary: #731209;
            --primary-light: #94180C;
            --bg-dark: #0a0a0a;
            --bg-panel: #121212;
            --text-muted: rgba(255, 255, 255, 0.6);
            --blueprint-grid: rgba(255, 255, 255, 0.03);
        }

        /* Base Aesthetics */
        body {
            background-color: var(--bg-dark);
            color: #fff;
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
            margin: 0;
        }

        /* Film Grain & Grid Overlay */
        body::before {
            content: "";
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background-image: 
                linear-gradient(var(--blueprint-grid) 1px, transparent 1px),
                linear-gradient(90deg, var(--blueprint-grid) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none;
            z-index: 1;
        }

        .grain {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: url('https://grainy-gradients.vercel.app/noise.svg');
            opacity: 0.05;
            pointer-events: none;
            z-index: 9999;
        }

        /* Typography */
        h1, h2, h3, .font-serif {
            font-family: 'Cormorant Garamond', serif;
            font-weight: 300;
            letter-spacing: -0.02em;
        }

        .tracking-architect {
            letter-spacing: 0.3em;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        /* Navigation */
        nav.mixed-blend-mode {
            mix-blend-mode: difference;
            z-index: 1000;
        }

        .mirrored-logo {
            display: inline-flex;
            align-items: center;
            font-size: 2rem;
            font-weight: 600;
            letter-spacing: -0.05em;
            text-decoration: none;
        }

        .mirrored-logo__r {
            transform: scaleX(-1);
            display: inline-block;
        }

        .menu-btn {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: flex-end;
            cursor: pointer;
        }

        .menu-line {
            width: 32px;
            height: 1px;
            background: #fff;
            transition: all 0.3s ease;
        }

        /* Nav Overlay */
        #navOverlay {
            position: fixed;
            top: 0; right: 0; bottom: 0;
            width: 100%;
            background-color: var(--primary);
            transform: translateX(100%);
            transition: transform .6s cubic-bezier(0.77, 0, 0.175, 1);
            z-index: 2000;
        }

        @media (min-width: 768px) {
            #navOverlay { width: 25vw; min-width: 350px; }
        }

        #navOverlay.open { transform: translateX(0); }

        /* Hero */
        .hero-section {
            height: 100vh;
            background: url('https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?q=80&w=2070&auto=format&fit=crop') center/cover no-repeat;
        }

        .hero-overlay {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(to bottom, rgba(10,10,10,0.4), rgba(10,10,10,1));
        }

        /* Timeline (Measuring Tape) */
        .timeline-section {
            padding: 150px 0;
            position: relative;
            z-index: 2;
        }

        .tape-case {
            width: 140px; height: 140px;
            border-radius: 12px 2px 40px 2px;
            background: linear-gradient(145deg, #1a1a1a, #000);
            border: 1px solid #333;
            box-shadow: 20px 20px 60px #050505;
            position: relative;
            overflow: hidden;
        }

        .tape-strip {
            background: #f0f0f0;
            height: 48px;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.2);
            position: relative;
        }

        .tape-ticks {
            width: 100%; height: 100%;
            background-image: repeating-linear-gradient(90deg, #000 0, #000 1px, transparent 1px, transparent 8px);
            opacity: 0.2;
        }

        .tape-hook {
            width: 14px; height: 60px;
            background: linear-gradient(to bottom, #999, #444);
            border-radius: 0 4px 4px 0;
            z-index: 5;
        }

        /* Milestone Markers */
        .year-box {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 20px;
            text-align: center;
            transition: all 0.5s cubic-bezier(0.19, 1, 0.22, 1);
            min-width: 180px;
            position: relative;
            z-index: 10;
        }

        .milestone-marker.active .year-box {
            border-color: var(--primary);
            background: rgba(115, 18, 9, 0.15);
        }

        .milestone-details {
            position: absolute;
            top: 100%; left: 50%;
            transform: translateX(-50%) translateY(20px);
            width: 280px;
            background: rgba(10, 10, 10, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid var(--primary);
            padding: 20px;
            opacity: 0;
            pointer-events: none;
            transition: all 0.4s ease;
            z-index: 20;
        }

        .milestone-marker:hover .milestone-details {
            opacity: 1;
            transform: translateX(-50%) translateY(10px);
            pointer-events: auto;
        }

        /* Stats */
        .stat-item {
            position: relative;
            padding: 60px 20px;
            overflow: hidden;
        }

        .stat-number-bg {
            font-size: 8rem;
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            -webkit-text-stroke: 1px rgba(255,255,255,0.05);
            color: transparent;
            font-family: 'Cormorant Garamond', serif;
            z-index: 0;
        }

        .stat-content {
            position: relative;
            z-index: 1;
        }

        /* Utilities */
        .py-lg-9 { padding-top: 8rem; padding-bottom: 8rem; }
        .text-primary-brand { color: var(--primary); }

        @media (max-width: 991px) {
            .timeline-section { padding: 80px 0; }
            .milestones-wrapper { position: relative !important; width: 100% !important; right: 0 !important; top: 0 !important; display: flex; flex-direction: column; gap: 40px; margin-top: 60px; }
            .milestone-marker { position: relative !important; left: 0 !important; transform: none !important; }
            .milestone-details { opacity: 1; position: relative; transform: none; width: 100%; top: 0; margin-top: 10px; }
            .tape-wrapper { display: none; } /* Hide technical tape on small mobile to favor readability */
        }
    </style>
</head>
<body>
    <div class="grain"></div>

    <!-- Navigation -->
    <nav class="fixed-top p-4 d-flex justify-content-between align-items-center mixed-blend-mode">
        <a class="mirrored-logo text-white" href="#">
            <span class="mirrored-logo__r" style="color: var(--primary);">R</span>
            <span>D</span>
        </a>
        <div class="menu-btn" id="menuBtn">
            <span class="menu-line"></span>
            <span class="menu-line" style="width: 20px;"></span>
        </div>
    </nav>

    <!-- Overlay Navigation -->
    <div id="navOverlay" class="d-flex flex-column align-items-center justify-content-center">
        <button class="position-absolute top-0 end-0 m-4 btn btn-link text-white text-decoration-none display-4" id="closeBtn">&times;</button>
        <nav class="d-flex flex-column text-center gap-4">
            <a class="display-4 text-white text-decoration-none font-serif" href="#">Home</a>
            <a class="display-4 text-white text-decoration-none font-serif fst-italic" href="#">About</a>
            <a class="display-4 text-white text-decoration-none font-serif" href="#">Services</a>
            <a class="display-4 text-white text-decoration-none font-serif" href="#">Contact</a>
        </nav>
    </div>

    <main>
        <!-- Hero Section -->
        <section class="hero-section position-relative d-flex align-items-center justify-content-center overflow-hidden">
            <div class="hero-overlay"></div>
            <div class="position-relative z-2 text-center container px-4">
                <span class="tracking-architect text-primary-brand mb-3 d-block">Est. 2017</span>
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
                            <span class="font-serif fs-3" style="color: var(--primary);">RD</span>
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
                <a href="#" class="btn btn-primary rounded-0 px-5 py-3 tracking-architect" style="background:#731209;border-color:#731209;color:#fff;">Contact Our Studio</a>
            </div>
        </section>
    </main>

    <?php include __DIR__ . '/footer.php'; ?>

    <!-- Dependencies -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function () {
            gsap.registerPlugin(ScrollTrigger);

            // 1. Navigation Logic
            const menuBtn = $('#menuBtn');
            const closeBtn = $('#closeBtn');
            const navOverlay = $('#navOverlay');

            menuBtn.on('click', function() {
                navOverlay.addClass('open');
                gsap.from('#navOverlay nav a', {
                    y: 30,
                    opacity: 0,
                    stagger: 0.1,
                    duration: 0.6,
                    ease: "power2.out"
                });
            });

            closeBtn.on('click', () => navOverlay.removeClass('open'));

            // 2. Timeline Animation Logic
            // Reset states
            gsap.set('#tapeStrip', { width: '0%' });
            gsap.set('.milestone-marker', { opacity: 0, scale: 0.8, y: 30 });

            const timelineMaster = gsap.timeline({
                scrollTrigger: {
                    trigger: ".timeline-section",
                    start: "top 45%",
                    end: "bottom 20%",
                    toggleActions: "play none none reverse"
                }
            });

            // Tape extend
            timelineMaster.to('#tapeStrip', {
                width: '100%',
                duration: 2.5,
                ease: "expo.inOut"
            });

            // Markers pop in sequence
            timelineMaster.to('.milestone-marker', {
                opacity: 1,
                scale: 1,
                y: 0,
                duration: 0.8,
                stagger: 0.4,
                ease: "back.out(1.7)"
            }, "-=1.8");

            // Individual highlight logic based on progress
            const markers = document.querySelectorAll('.milestone-marker');
            markers.forEach((marker, index) => {
                timelineMaster.to(marker, {
                    onStart: () => $(marker).addClass('active'),
                    duration: 0.1
                }, `-=${2 - (index * 0.7)}`);
            });

            // 3. Stats Section Parallax/Reveal
            gsap.from('.stat-number-bg', {
                scrollTrigger: {
                    trigger: '.stat-item',
                    start: "top 90%",
                    scrub: 1
                },
                y: 100,
                opacity: 0
            });
        });
    </script>
</body>
</html>