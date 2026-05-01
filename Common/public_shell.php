<?php
require_once __DIR__ . '/../app/Core/Bootstrap/init.php';

if (!function_exists('rd_public_url')) {
    function rd_public_url(string $path = ''): string
    {
        $base = rtrim((string)BASE_PATH, '/');
        $prefix = rtrim((string)PUBLIC_PATH_PREFIX, '/');
        $path = ltrim($path, '/');
        return $base . $prefix . ($path === '' ? '' : '/' . $path);
    }
}

if (!function_exists('rd_asset_url')) {
    function rd_asset_url(string $path): string
    {
        return rtrim((string)BASE_PATH, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('rd_content_image')) {
    function rd_content_image(array $content, string $key, string $fallback): string
    {
        $value = (string)($content[$key] ?? $fallback);
        if (function_exists('public_content_image_url')) {
            return (string)public_content_image_url($value, $fallback);
        }
        return rd_asset_url(ltrim($value, '/'));
    }
}

if (!function_exists('rd_page_start')) {
    function rd_page_start(array $options): void
    {
        $title = (string)($options['title'] ?? 'Ripal Design');
        $description = (string)($options['description'] ?? 'Architecture, interiors, and execution by Ripal Design.');
        $bodyClass = (string)($options['body_class'] ?? '');
        $bodyClass = trim($bodyClass);
        $bodyTokens = $bodyClass === '' ? [] : preg_split('/\s+/', $bodyClass);
        $bodyTokens = array_filter($bodyTokens ?: [], 'strlen');
        $isImmersive = !empty($options['immersive']);
        if ($isImmersive && !in_array('public-immersive', $bodyTokens, true)) {
            $bodyTokens[] = 'public-immersive';
        }
        $bodyClass = implode(' ', $bodyTokens);
        $radiusMode = strtolower((string)(getenv('UI_RADIUS') ?: 'sharp'));
        $radiusMode = in_array($radiusMode, ['rounded', 'sharp'], true) ? $radiusMode : 'sharp';
        $image = (string)($options['image'] ?? rd_asset_url('assets/Content/Logo.png'));
        $url = (string)($options['url'] ?? rd_public_url('index.php'));
        $active = (string)($options['active'] ?? '');
        ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc($title); ?> | Ripal Design</title>
    <meta name="description" content="<?php echo esc_attr($description); ?>">
    <link rel="canonical" href="<?php echo esc_attr($url); ?>">
    <meta property="og:title" content="<?php echo esc_attr($title); ?> | Ripal Design">
    <meta property="og:description" content="<?php echo esc_attr($description); ?>">
    <meta property="og:image" content="<?php echo esc_attr($image); ?>">
    <meta property="og:url" content="<?php echo esc_attr($url); ?>">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="icon" href="<?php echo esc_attr(rd_asset_url('favicon.ico')); ?>" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700;800&family=Newsreader:opsz,wght@6..72,500;6..72,600;6..72,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo esc_attr(rd_asset_url('assets/css/ui-radius.css')); ?>">
    <link rel="stylesheet" href="<?php echo esc_attr(rd_public_url('css/public-redesign.css')); ?>">
    <script>
        document.documentElement.setAttribute('data-ui-radius', <?php echo json_encode($radiusMode); ?>);
    </script>
</head>
<body class="<?php echo esc_attr($bodyClass); ?>" data-ui-radius="<?php echo esc_attr($radiusMode); ?>">
    <a class="skip-link" href="#main">Skip to content</a>
    <header class="site-header" data-active="<?php echo esc_attr($active); ?>">
        <a class="brand" href="<?php echo esc_attr(rd_public_url('index.php')); ?>" aria-label="Ripal Design home">
            <img src="<?php echo esc_attr(rd_asset_url('assets/Content/Logo.png')); ?>" alt="" width="36" height="36">
            <span>Ripal Design</span>
        </a>
        <button class="nav-toggle" type="button" aria-label="Open navigation" aria-expanded="false" aria-controls="siteNav">
            <span class="hamburger-lines" aria-hidden="true">
                <span></span>
                <span></span>
                <span></span>
            </span>
        </button>
        <nav class="site-nav" id="siteNav" aria-label="Primary navigation">
            <?php
            $links = [
                'home' => ['href' => 'index.php', 'label' => 'Home'],
                'services' => ['href' => 'services.php', 'label' => 'Services'],
                'projects' => ['href' => 'project_view.php', 'label' => 'Projects'],
                'about' => ['href' => 'about_us.php', 'label' => 'About'],
                'contact' => ['href' => 'contact_us.php', 'label' => 'Contact'],
            ];
            foreach ($links as $key => $link):
                $isActive = $active === $key ? ' aria-current="page"' : '';
            ?>
                <a href="<?php echo esc_attr(rd_public_url($link['href'])); ?>"<?php echo $isActive; ?>><?php echo esc($link['label']); ?></a>
            <?php endforeach; ?>
            <div class="menu-actions" aria-label="Account actions">
                <a class="button button-secondary" href="<?php echo esc_attr(rd_public_url('login.php')); ?>">Login</a>
                <a class="button button-primary" href="<?php echo esc_attr(rd_public_url('signup.php')); ?>">Sign Up</a>
            </div>
            <a class="menu-cta" href="<?php echo esc_attr(rd_public_url('contact_us.php')); ?>">Start a Project</a>
        </nav>
    </header>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js" defer></script>
    <script src="<?php echo esc_attr(rd_asset_url('assets/js/gsap-core-init.js')); ?>" defer></script>
    <script src="<?php echo esc_attr(rd_asset_url('assets/js/gsap-motion-presets.js')); ?>" defer></script>
    <script src="<?php echo esc_attr(rd_public_url('js/public-immersive.js')); ?>" defer></script>
    <script src="<?php echo esc_attr(rd_asset_url('assets/js/home-immersive.js')); ?>" defer></script>
        <?php
    }
}

if (!function_exists('rd_page_end')) {
    function rd_page_end(bool $showCta = true): void
    {
        $phoneHref = 'tel:' . preg_replace('/\s+/', '', (string)PHONE_NUMBER);
        $whatsAppHref = 'https://wa.me/' . preg_replace('/\D+/', '', (string)WHATSAPP_NUMBER);
        ?>
    <footer class="site-footer">
        <?php if ($showCta): ?>
            <section class="footer-cta" aria-labelledby="footerCtaTitle">
                <div>
                    <p class="eyebrow">Next step</p>
                    <h2 id="footerCtaTitle">Ready to make the site, budget, and design speak the same language?</h2>
                </div>
                <a class="button button-primary" href="<?php echo esc_attr(rd_public_url('contact_us.php')); ?>">Request a Consultation</a>
            </section>
        <?php endif; ?>
        <div class="footer-grid">
            <div>
                <a class="brand footer-brand" href="<?php echo esc_attr(rd_public_url('index.php')); ?>">
                    <img src="<?php echo esc_attr(rd_asset_url('assets/Content/Logo.png')); ?>" alt="" width="36" height="36">
                    <span>Ripal Design</span>
                </a>
                <p>Architecture, interiors, and execution support from Rajkot for homes, institutions, and commercial spaces.</p>
            </div>
            <address>
                <strong>Studio</strong>
                <span>538 Jasal Complex, Nanavati Chowk, 150ft Ring Road, Rajkot, Gujarat</span>
                <a href="<?php echo esc_attr($phoneHref); ?>"><?php echo esc(PHONE_NUMBER); ?></a>
                <a href="mailto:projects@ripaldesign.studio">projects@ripaldesign.studio</a>
            </address>
            <nav aria-label="Footer navigation">
                <strong>Pages</strong>
                <a href="<?php echo esc_attr(rd_public_url('services.php')); ?>">Services</a>
                <a href="<?php echo esc_attr(rd_public_url('project_view.php')); ?>">Projects</a>
                <a href="<?php echo esc_attr(rd_public_url('about_us.php')); ?>">About</a>
                <a href="<?php echo esc_attr(rd_public_url('privacy.php')); ?>">Privacy</a>
                <a href="<?php echo esc_attr(rd_public_url('terms.php')); ?>">Terms</a>
            </nav>
        </div>
        <div class="footer-bottom">
            <span>&copy; <?php echo date('Y'); ?> Ripal Design. All rights reserved.</span>
            <a href="<?php echo esc_attr($whatsAppHref); ?>" target="_blank" rel="noopener noreferrer">WhatsApp</a>
        </div>
    </footer>
    <script>
        document.querySelectorAll('.nav-toggle').forEach(function (button) {
            button.addEventListener('click', function () {
                var nav = document.getElementById(button.getAttribute('aria-controls'));
                var open = button.getAttribute('aria-expanded') === 'true';
                button.setAttribute('aria-expanded', open ? 'false' : 'true');
                button.setAttribute('aria-label', open ? 'Open navigation' : 'Close navigation');
                if (nav) nav.classList.toggle('is-open', !open);
            });
        });

        (function () {
            if (!document.body || !document.body.classList.contains('home-immersive')) {
                return;
            }

            var lastY = window.scrollY || 0;
            var threshold = 40;
            var ticking = false;

            function update() {
                var currentY = window.scrollY || 0;
                var scrollingDown = currentY > lastY;
                var menuOpen = false;
                document.querySelectorAll('.nav-toggle').forEach(function (button) {
                    if (button.getAttribute('aria-expanded') === 'true') {
                        menuOpen = true;
                    }
                });

                if (!menuOpen && scrollingDown && currentY > threshold) {
                    document.body.classList.add('nav-hidden');
                } else {
                    document.body.classList.remove('nav-hidden');
                }

                lastY = currentY;
                ticking = false;
            }

            window.addEventListener('scroll', function () {
                if (!ticking) {
                    window.requestAnimationFrame(update);
                    ticking = true;
                }
            }, { passive: true });
        })();
    </script>
</body>
</html>
        <?php
    }
}
