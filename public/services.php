<?php
require_once __DIR__ . '/../Common/public_shell.php';

$content = function_exists('public_content_page_values') ? public_content_page_values('services') : [];
$ct = static fn ($key, $default = '') => (string)($content[$key] ?? $default);
$image = static fn ($key, $default) => rd_content_image($content, $key, $default);
$heroImage = $image('hero_image_src', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg');

$services = [
    [
        'icon' => 'fa-drafting-compass',
        'title' => $ct('service_1_title', 'Architectural Planning'),
        'body' => $ct('service_1_description', 'Site strategy, layouts, elevations, drawings, and approvals guidance for homes and commercial spaces.'),
        'image' => $image('service_image_1', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg'),
        'fit' => $content['service_image_1__fit'] ?? 'auto',
        'position' => $content['service_image_1__position'] ?? 'auto',
    ],
    [
        'icon' => 'fa-couch',
        'title' => $ct('service_2_title', 'Interior Design'),
        'body' => $ct('service_2_description', 'Room-by-room interior direction, furniture planning, lighting, finishes, and material palettes.'),
        'image' => $image('service_image_2', '/assets/Content/WhatsApp Image 2026-02-02 at 5.43.21 PM (1).jpeg'),
        'fit' => $content['service_image_2__fit'] ?? 'auto',
        'position' => $content['service_image_2__position'] ?? 'auto',
    ],
    [
        'icon' => 'fa-seedling',
        'title' => $ct('service_3_title', 'Landscape Architecture'),
        'body' => $ct('service_3_description', 'Outdoor spaces that connect structure, climate, maintenance, and daily use.'),
        'image' => $image('service_image_3', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg'),
        'fit' => $content['service_image_3__fit'] ?? 'auto',
        'position' => $content['service_image_3__position'] ?? 'auto',
    ],
    [
        'icon' => 'fa-list-check',
        'title' => $ct('service_4_title', 'Project Management'),
        'body' => $ct('service_4_description', 'Execution planning, vendor coordination, site checks, and progress communication.'),
        'image' => $image('service_image_4', '/assets/Content/WhatsApp Image 2026-02-02 at 5.51.43 PM.jpeg'),
        'fit' => $content['service_image_4__fit'] ?? 'auto',
        'position' => $content['service_image_4__position'] ?? 'auto',
    ],
];

$initialServiceImage = (string)($services[0]['image'] ?? $heroImage);
$initialServiceTitle = (string)($services[0]['title'] ?? 'Service');

rd_page_start([
    'title' => $ct('page_title', 'Services'),
    'description' => $ct('meta_description', 'Architecture, interiors, landscape, and project execution services by Ripal Design.'),
    'image' => $heroImage,
    'url' => rd_public_url('services.php'),
    'active' => 'services',
]);
?>
<main id="main">
    <section class="hero">
        <div class="hero-copy">
            <p class="eyebrow"><?php echo esc($ct('section_kicker', 'Services')); ?></p>
            <h1><?php echo esc($ct('hero_heading', 'Clear services for decisions that become buildings.')); ?></h1>
            <p><?php echo esc($ct('hero_subheading', 'Choose focused design help, complete interiors, or execution support. Every engagement starts with clarity on scope, timeline, and the decisions required from you.')); ?></p>
            <div class="hero-actions">
                <a class="button button-primary" href="<?php echo esc_attr(rd_public_url('contact_us.php')); ?>">Request a Proposal</a>
                <a class="button button-secondary" href="#packages">Compare Packages</a>
            </div>
        </div>
        <div class="hero-media">
            <figure><img src="<?php echo esc_attr($heroImage); ?>" alt="Architecture service preview"<?php echo rd_content_image_style_attr($content, 'hero_image_src'); ?>></figure>
        </div>
    </section>

    <section class="page-section">
        <div class="service-layout">
            <div>
                <div class="section-head" style="grid-template-columns: 1fr; margin-bottom: 1.5rem;">
                    <div>
                        <p class="eyebrow">What we do</p>
                        <h2>Design work with visible next steps.</h2>
                    </div>
                </div>
                <div class="service-list">
                    <?php foreach ($services as $index => $service): ?>
                        <article class="service-card<?php echo $index === 0 ? ' is-active' : ''; ?>" data-service-image="<?php echo esc_attr($service['image']); ?>" data-service-title="<?php echo esc_attr($service['title']); ?>" data-service-fit="<?php echo esc_attr((string)$service['fit']); ?>" data-service-position="<?php echo esc_attr((string)$service['position']); ?>" tabindex="0">
                            <i class="fa-solid <?php echo esc_attr($service['icon']); ?>" aria-hidden="true"></i>
                            <h3><?php echo esc($service['title']); ?></h3>
                            <p><?php echo esc($service['body']); ?></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="service-visual">
                <img id="serviceVisualImage" src="<?php echo esc_attr($initialServiceImage); ?>" alt="<?php echo esc_attr($initialServiceTitle . ' service preview'); ?>"<?php echo rd_content_image_style_attr($content, 'service_image_1'); ?>>
            </div>
        </div>
    </section>

    <section class="page-section" id="packages">
        <div class="section-head">
            <div>
                <p class="eyebrow">Packages</p>
                <h2>Start with the level of help you need.</h2>
            </div>
            <p>Final pricing depends on site size, location, documentation depth, and site involvement. These are starting points for an easier first conversation.</p>
        </div>
        <div class="card-grid">
            <article class="process-step">
                <strong>01</strong>
                <h3>Concept Planning</h3>
                <p>Feasibility, layout direction, visual mood, and budget guidance.</p>
                <p class="eyebrow">From INR 35k</p>
            </article>
            <article class="process-step">
                <strong>02</strong>
                <h3>Design + Interiors</h3>
                <p>Detailed planning, 3D views, materials, furniture, lighting, and documentation.</p>
                <p class="eyebrow">From INR 1.2L</p>
            </article>
            <article class="process-step">
                <strong>03</strong>
                <h3>Design + Execution</h3>
                <p>Design documentation with vendor coordination, site supervision, and handover support.</p>
                <p class="eyebrow">Custom quote</p>
            </article>
        </div>
    </section>

    <section class="page-section">
        <div class="section-head">
            <div>
                <p class="eyebrow">Process</p>
                <h2>Four stages. No mystery.</h2>
            </div>
        </div>
        <div class="card-grid">
            <?php foreach (['Discovery', 'Concept', 'Detailing', 'Handover'] as $i => $step): ?>
                <article class="process-step">
                    <strong><?php echo str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT); ?></strong>
                    <h3><?php echo esc($step); ?></h3>
                    <p><?php echo esc([
                        'Site visit, needs, budget, and timeline.',
                        'Plans, mood, massing, and material direction.',
                        'Drawings, BOQ, vendor coordination, and execution clarity.',
                        'Final checks, support, and project documentation.',
                    ][$i]); ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>
<script>
    (function () {
        var cards = document.querySelectorAll('.service-card[data-service-image]');
        var visualImage = document.getElementById('serviceVisualImage');
        var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var nextCard = cards[0] || null;

        if (!cards.length || !visualImage) {
            return;
        }

        cards.forEach(function (card) {
            var src = card.getAttribute('data-service-image') || '';
            if (!src) {
                return;
            }
            var preload = new Image();
            preload.src = src;
        });

        function applyCardStyle(card) {
            if (!visualImage || !card) {
                return;
            }

            var fit = card.getAttribute('data-service-fit') || '';
            var position = card.getAttribute('data-service-position') || '';
            visualImage.style.objectFit = fit === 'auto' ? '' : fit;
            visualImage.style.objectPosition = position === 'auto' ? '' : position;
        }

        function animateImageSwap(nextImage, nextTitle) {
            applyCardStyle(nextCard);
            if (!nextImage || visualImage.getAttribute('src') === nextImage) {
                visualImage.setAttribute('alt', nextTitle + ' service preview');
                return;
            }

            if (!window.gsap || reducedMotion) {
                visualImage.setAttribute('src', nextImage);
                visualImage.setAttribute('alt', nextTitle + ' service preview');
                applyCardStyle(nextCard);
                return;
            }

            window.gsap.killTweensOf(visualImage);
            window.gsap.to(visualImage, {
                autoAlpha: 0,
                scale: 1.02,
                duration: 0.18,
                ease: 'power2.out',
                overwrite: 'auto',
                onComplete: function () {
                    visualImage.setAttribute('src', nextImage);
                    visualImage.setAttribute('alt', nextTitle + ' service preview');
                    applyCardStyle(nextCard);
                    window.gsap.fromTo(visualImage, {
                        autoAlpha: 0,
                        scale: 0.985
                    }, {
                        autoAlpha: 1,
                        scale: 1,
                        duration: 0.36,
                        ease: 'power2.out',
                        overwrite: 'auto'
                    });
                }
            });
        }

        function activateCard(card) {
            var nextImage = card.getAttribute('data-service-image') || '';
            var nextTitle = card.getAttribute('data-service-title') || 'Service';
            nextCard = card;
            animateImageSwap(nextImage, nextTitle);

            cards.forEach(function (item) {
                item.classList.toggle('is-active', item === card);
            });
        }

        cards.forEach(function (card) {
            card.addEventListener('mouseenter', function () {
                activateCard(card);
            });
            card.addEventListener('focus', function () {
                activateCard(card);
            });
            card.addEventListener('click', function () {
                activateCard(card);
            });
        });

        applyCardStyle(nextCard);
    })();
</script>
<?php rd_page_end(); ?>
