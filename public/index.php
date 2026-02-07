<!DOCTYPE html>
<html class="scroll-smooth" lang="en">

<head>
    <meta charset="utf-8" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <title>Ripal Design | Architectural Excellence</title>
    <link rel="icon" type="image/png" href="assets/Content/Logo.png">
    <link href="https://fonts.googleapis.com" rel="preconnect" />
    <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect" />
    <link
        href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600&amp;family=Inter:wght@300;400;500;600&amp;display=swap"
        rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap"
        rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>

<body class="antialiased overflow-x-hidden scrollbar:none ms-overflow-style:none">
    <header class="site-header fixed top-0 left-0 w-full z-[100] flex justify-between items-center px-10 py-8 mix-blend-difference">
        <div class="mirrored-logo" aria-hidden="true">
            <span class="mirrored-logo__r text-[var(--primary)]">R</span>
            <span class="text-white">D</span>
        </div>
        <button aria-label="Open menu" class="menu-btn group p-2 focus:outline-none" id="menuBtn">
            <span class="menu-line"></span>
            <span class="menu-line"></span>
        </button>
    </header>
    <div class="fixed top-0 right-0 bottom-0 w-full md:w-1/4 bg-[var(--primary)] z-[110] flex flex-col items-center justify-center translate-x-[100vw] transition-transform duration-700 ease-in-out"
        id="navOverlay" aria-hidden="true">
        <button class="absolute top-10 right-10 text-white hover:rotate-90 transition-transform duration-300"
            id="closeBtn">
            <span class="material-symbols-outlined text-5xl">close</span>
        </button>
        <nav class="flex flex-col items-center md:items-center gap-10 text-5xl md:text-7xl font-['Cormorant_Garamond']">
            <a class="hover:italic hover:tracking-widest transition-all duration-300" href="#">Home</a>
            <a class="hover:italic hover:tracking-widest transition-all duration-300" href="#">Services</a>
            <a class="hover:italic hover:tracking-widest transition-all duration-300" href="#">About Us</a>
            <a class="hover:italic hover:tracking-widest transition-all duration-300" href="#">Pro•ects</a>
        </nav>
    </div>
    <main>
    <section class="hero relative h-screen w-full overflow-hidden">
            <div class="absolute inset-0 z-0">
                <img alt="Hero Architecture" class="w-full h-full object-cover brightness-50"
                    src="assets/Content/WhatsApp Image 2026-02-02 at 6.55.18 PM.jpeg" />
            </div>
            <div class="relative z-10 h-full flex flex-col items-center justify-center text-center px-4">
                <h1 class="text-7xl md:text-9xl font-light mb-4">Ripal Design</h1>
                <p class="uppercase tracking-[0.8em] text-sm opacity-80">Visionary Architecture &amp; Design</p>
                <div class="absolute bottom-20 left-1/2 -translate-x-1/2 flex gap-4">
                    <div class="w-16 h-0.5 bg-[var(--primary)]"></div>
                    <div class="w-16 h-0.5 bg-white/20"></div>
                    <div class="w-16 h-0.5 bg-white/20"></div>
                </div>
            </div>
        </section>
        <!-- First carousel: media (videos + photos) - full screen -->
        <section id="mediaCarousel" class="h-screen w-full relative overflow-hidden">
            <div class="carousel absolute inset-0 z-0">
                <div class="carousel-track" id="mediaTrack">
                    <div class="carousel-slide">
                        <video muted playsinline loop class="w-full h-full object-cover" src="assets/Content/sample-video-1.mp4"></video>
                    </div>
                    <div class="carousel-slide">
                        <img alt="Slide 2" src="assets/Content/WhatsApp%20Image%202026-02-02%20at%205.02.50%20PM.jpeg" />
                    </div>
                    <div class="carousel-slide">
                        <video muted playsinline loop class="w-full h-full object-cover" src="assets/Content/sample-video-2.mp4"></video>
                    </div>
                    <div class="carousel-slide">
                        <img alt="Slide 4" src="assets/Content/WhatsApp%20Image%202026-02-02%20at%205.43.21%20PM%20%281%29.jpeg" />
                    </div>
                </div>
            </div>
            <div class="absolute inset-0 bg-black/20 pointer-events-none"></div>
        </section>

        <!-- Second carousel: photos only - full screen -->
        <section id="photoCarousel" class="h-screen w-full relative overflow-hidden bg-black">
            <div class="carousel absolute inset-0 z-0">
                <div class="carousel-track" id="photoTrack">
                    <div class="carousel-slide"><img alt="P1" src="assets/Content/WhatsApp%20Image%202026-02-02%20at%205.02.50%20PM.jpeg" /></div>
                    <div class="carousel-slide"><img alt="P2" src="assets/Content/WhatsApp%20Image%202026-02-02%20at%205.02.51%20PM.jpeg" /></div>
                    <div class="carousel-slide"><img alt="P3" src="assets/Content/WhatsApp%20Image%202026-02-02%20at%205.43.21%20PM.jpeg" /></div>
                    <div class="carousel-slide"><img alt="P4" src="assets/Content/WhatsApp%20Image%202026-02-02%20at%205.51.43%20PM.jpeg" /></div>
                </div>
            </div>
            <div class="relative z-20 w-full px-10">
                <div class="absolute left-4">
                    <button id="photoPrev" class="carousel-button">
                        <span class="material-symbols-outlined">chevron_left</span>
                    </button>
                </div>
                <div class="absolute right-4">
                    <button id="photoNext" class="carousel-button">
                        <span class="material-symbols-outlined">chevron_right</span>
                    </button>
                </div>
            </div>
        </section>
        <section class="asymmetric-section bg-[#0a0a0a] py-40">
            <div class="projects-container max-w-[1400px] mx-auto px-10 h-full flex flex-col justify-between">
                <div class="project flex items-center">
                    <div class="project__media w-[33%] aspect-[3/4] overflow-hidden group">
                        <img alt="Project 1"
                            class="w-full h-full object-cover transition-transform duration-1000 group-hover:scale-110"
                            src="assets/Content/WhatsApp%20Image%202026-02-02%20at%205.02.50%20PM.jpeg" />
                    </div>
                    <div class="project__content ml-[7%] flex-1">
                        <span class="text-[var(--primary)] font-semibold tracking-tighter text-2xl">01</span>
                        <h3 class="text-6xl my-6">Obsidian Mono</h3>
                        <p class="max-w-md text-gray-400 leading-relaxed text-lg">A monolith of light and shadow,
                            redefining the urban residential experience through carbon-neutral materials and sculptural
                            precision.</p>
                        <a class="mt-8 inline-block text-xs uppercase tracking-[0.3em] border-b border-[var(--primary)] pb-2 hover:text-[var(--primary)] transition-colors"
                            href="#">View Project</a>
                    </div>
                </div>
                <div class="project project--reverse flex flex-row-reverse items-center">
                    <div class="project__media w-[33%] aspect-[3/4] overflow-hidden group">
                        <img alt="Project 2"
                            class="w-full h-full object-cover transition-transform duration-1000 group-hover:scale-110"
                            src="assets/Content/WhatsApp%20Image%202026-02-02%20at%205.02.51%20PM.jpeg" />
                    </div>
                    <div class="project__content mr-[7%] flex-1 text-right flex flex-col items-end">
                        <span class="text-[var(--primary)] font-semibold tracking-tighter text-2xl">02</span>
                        <h3 class="text-6xl my-6">Oasis Pavilion</h3>
                        <p class="max-w-md text-gray-400 leading-relaxed text-lg">Bridging the gap between desert
                            tradition and future technology, this structure breathes with the environment.</p>
                        <a class="mt-8 inline-block text-xs uppercase tracking-[0.3em] border-b border-[var(--primary)] pb-2 hover:text-[var(--primary)] transition-colors"
                            href="#">View Project</a>
                    </div>
                </div>
                <div class="project flex items-center">
                    <div class="project__media w-[33%] aspect-[3/4] overflow-hidden group">
                        <img alt="Project 3"
                            class="w-full h-full object-cover transition-transform duration-1000 group-hover:scale-110"
                            src="assets/Content/WhatsApp%20Image%202026-02-02%20at%205.43.21%20PM%20%281%29.jpeg" />
                    </div>
                    <div class="project__content ml-[7%] flex-1">
                        <span class="text-[var(--primary)] font-semibold tracking-tighter text-2xl">03</span>
                        <h3 class="text-6xl my-6">Vertical Zen</h3>
                        <p class="max-w-md text-gray-400 leading-relaxed text-lg">Urban biophilia integrated into a
                            modular high-rise system. Light as a building material.</p>
                        <a class="mt-8 inline-block text-xs uppercase tracking-[0.3em] border-b border-[var(--primary)] pb-2 hover:text-[var(--primary)] transition-colors"
                            href="#">View Project</a>
                    </div>
                </div>
                <div class="project project--reverse flex flex-row-reverse items-center">
                    <div class="project__media w-[33%] aspect-[3/4] overflow-hidden group">
                        <img alt="Project 4"
                            class="w-full h-full object-cover transition-transform duration-1000 group-hover:scale-110"
                            src="assets/Content/WhatsApp%20Image%202026-02-02%20at%205.51.43%20PM.jpeg" />
                    </div>
                    <div class="project__content mr-[7%] flex-1 text-right flex flex-col items-end">
                        <span class="text-[var(--primary)] font-semibold tracking-tighter text-2xl">04</span>
                        <h3 class="text-6xl my-6">Loft VII</h3>
                        <p class="max-w-md text-gray-400 leading-relaxed text-lg">Raw industrial heritage meeting
                            contemporary refinement. A study in texture and volume preservation.</p>
                        <a class="mt-8 inline-block text-xs uppercase tracking-[0.3em] border-b border-[var(--primary)] pb-2 hover:text-[var(--primary)] transition-colors"
                            href="#">View Project</a>
                    </div>
                </div>
            </div>
        </section>
        <section class="testimonials h-screen bg-black flex flex-col justify-center overflow-hidden border-y border-white/5">
            <div class="px-10 mb-16 text-center">
                <h2 class="text-5xl font-light">Client Perspectives</h2>
            </div>
            <div class="testimonials__list flex gap-8 px-10 overflow-x-auto hide-scrollbar pb-10">
                <div class="testimonial min-w-[calc(33.333%-22px)] bg-[#111] p-12 flex flex-col gap-8 group hover:bg-[#181818] transition-colors duration-500">
                    <div class="h-64 overflow-hidden">
                        <img alt="Estate"
                            class="w-full h-full object-cover opacity-60 group-hover:opacity-100 transition-opacity duration-500"
                            src="assets/Content/WhatsApp%20Image%202026-02-02%20at%205.51.43%20PM%20%281%29.jpeg" />
                    </div>
                    <p class="text-2xl font-['Cormorant_Garamond'] italic leading-relaxed text-gray-300">"The surgical
                        precision of their design language transformed our site into a masterpiece of modern
                        architecture."</p>
                    <div class="pt-6 border-t border-[var(--primary)]">
                        <p class="font-bold text-lg">Jonathan Vance</p>
                        <p class="text-xs text-[var(--primary)] uppercase tracking-widest mt-1">CEO, Skyward Holdings
                        </p>
                    </div>
                </div>
                <div class="testimonial min-w-[calc(33.333%-22px)] bg-[#111] p-12 flex flex-col gap-8 group hover:bg-[#181818] transition-colors duration-500">
                    <div class="h-64 overflow-hidden">
                        <img alt="Estate"
                            class="w-full h-full object-cover opacity-60 group-hover:opacity-100 transition-opacity duration-500"
                            src="https://lh3.googleusercontent.com/aida-public/AB6AXuDwakgzDdh4yYKnaiv01B73866f37Of2Bu5QhltARKUTUO0ivJQODK7L_sw3W67uo5W1adSOFaZePN9-_5aqLfopLjSim7MTdnZhjnK91ThHQJ9UqiwDHrmHJCOQrRvx6ivMymZuXZxa0T018GqAF5jxMGorjCsAQVPdgHTrRw7ZM5B8kbyahTngotQ4jR7Na3RrNaw8WCLsWFJglO9cpNGws9dzgge0kA-bYWuzlTTPXxf00VaVPuQs1mGk_5D4evG8AbKDIAphwY" />
                    </div>
                    <p class="text-2xl font-['Cormorant_Garamond'] italic leading-relaxed text-gray-300">"They pushed
                        the boundaries of what we thought was possible, creating a space that feels both intimate and
                        grand."</p>
                    <div class="pt-6 border-t border-[var(--primary)]">
                        <p class="font-bold text-lg">Elena Rodriguez</p>
                        <p class="text-xs text-[var(--primary)] uppercase tracking-widest mt-1">Private Collector</p>
                    </div>
                </div>
                <div class="testimonial min-w-[calc(33.333%-22px)] bg-[#111] p-12 flex flex-col gap-8 group hover:bg-[#181818] transition-colors duration-500">
                    <div class="h-64 overflow-hidden">
                        <img alt="Estate"
                            class="w-full h-full object-cover opacity-60 group-hover:opacity-100 transition-opacity duration-500"
                            src="https://lh3.googleusercontent.com/aida-public/AB6AXuDNPZ-wTTxSwEFcEa2_a6fHx9KWtPMQZz6cxMHCLzzHQ2KXPhdBpDqqcO-CF4I1Qf_EoBpNCucI38K_njXpEQ1i-EqttlzVfHslel5OMPC7a7GfLPPH-lw6KQduluEQz341L715ELpJP98kIMDOBUMnR657kYRmiD6_Ow8_mihNxgVs0MZU5aJCLKHxsVLeuFk_NCSPwisNYPXSQ8C3GjjQO-twZ8GSEXyzjoJXQh9g3rjTckkqedihGwssxfIB-XIy-j2ftooZskA" />
                    </div>
                    <p class="text-2xl font-['Cormorant_Garamond'] italic leading-relaxed text-gray-300">"Deeply
                        committed to sustainability without compromising on aesthetic excellence. Truly leaders in the
                        new era."</p>
                    <div class="pt-6 border-t border-[var(--primary)]">
                        <p class="font-bold text-lg">Marcus Thorne</p>
                        <p class="text-xs text-[var(--primary)] uppercase tracking-widest mt-1">Director, Urban Planning
                            NYC</p>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <footer class="bg-black py-24 px-10">
        <div class="max-w-[1400px] mx-auto grid grid-cols-1 md:grid-cols-4 gap-16">
            <div class="col-span-1 md:col-span-2">
                <div class="mirrored-logo mb-10 scale-125 origin-left">
                    <span class="mirrored-logo__r text-[var(--primary)]">R</span>
                    <span class="text-white">D</span>
                </div>
                <p class="text-gray-500 max-w-sm leading-relaxed text-lg">
                    Creating landmark structures that define the skyline and enrich the human experience.
                </p>
            </div>
            <div>
                <h4 class="text-[var(--primary)] font-bold uppercase tracking-[0.2em] text-xs mb-8">Contact Us</h4>
                <ul class="text-gray-400 space-y-6">
                    <li class="hover:text-white transition-colors cursor-pointer">45 Madison Ave, New York</li>
                    <li class="hover:text-white transition-colors cursor-pointer">+1 212 555 0198</li>
                    <li class="hover:text-white transition-colors cursor-pointer">studio@rd-arch.com</li>
                </ul>
            </div>
            <div>
                <h4 class="text-[var(--primary)] font-bold uppercase tracking-[0.2em] text-xs mb-8">Navigation</h4>
                <ul class="text-gray-400 space-y-6">
                    <li><a class="hover:text-white transition-colors" href="#">Projects</a></li>
                    <li><a class="hover:text-white transition-colors" href="#">Process</a></li>
                    <li><a class="hover:text-white transition-colors" href="#">Team</a></li>
                    <li><a class="hover:text-white transition-colors" href="#">Careers</a></li>
                </ul>
            </div>
        </div>
        <div
            class="max-w-[1400px] mx-auto mt-24 pt-10 border-t border-white/5 flex flex-col md:flex-row justify-between items-center text-[10px] uppercase tracking-[0.4em] text-gray-600 gap-6">
            <p>© 2024 R/D Studio. Masterpiece in every line.</p>
            <div class="flex gap-10">
                <a class="hover:text-[var(--primary)] transition-colors" href="#">Privacy Policy</a>
                <a class="hover:text-[var(--primary)] transition-colors" href="#">Terms of Service</a>
            </div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="scripts.js"></script>

</body>

</html>