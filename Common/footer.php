<!-- Footer / CTA -->
</phantom-ui>
<?php
/**
 * Common Footer Component
 * 
 * Site-wide footer with contact information, call-to-action, and copyright.
 * Includes necessary JavaScript files.
 * 
 * Usage:
 * <?php require_once __DIR__ . '/../Common/footer.php'; ?>
 * 
 * @package RipalDesign
 * @subpackage Components
 */

// Ensure configuration is loaded
if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/../app/Core/Config/config.php';
}

$footerContent = function_exists('public_content_page_values') ? public_content_page_values('common_footer') : [];
$footerText = static function ($key, $default = '') use ($footerContent) {
    return (string)($footerContent[$key] ?? $default);
};

$currentYear = date('Y');
?>

<style>
    /* Keep footer appearance consistent even when page-level styles override links/fonts */
    .site-footer .font-serif,
    .site-footer h2,
    .site-footer h3 {
        font-family: 'Cormorant Garamond', serif !important;
    }

    .site-footer .footer-cta-btn {
        color: #ffffff !important;
        text-decoration: none !important;
    }

    .site-footer .footer-contact-link {
        color: #9ca3af !important;
        text-decoration: none !important;
    }

    .site-footer .footer-contact-link:hover {
        color: #94180C !important;
    }

    .site-footer .footer-legal-link {
        color: #6b7280 !important;
        text-decoration: none !important;
    }

    .site-footer .footer-legal-link:hover {
        color: #ffffff !important;
    }
</style>

<footer class="site-footer bg-foundation-grey text-white pt-16 pb-8 px-4 font-sans" role="contentinfo">
    <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
            <div>
                <h2 class="text-3xl font-serif font-bold mb-4"><?php echo esc($footerText('cta_heading', 'Ready to build something Iconic?')); ?></h2>
                <p class="text-gray-400 mb-8 max-w-lg">
                    <?php echo esc($footerText('cta_description', "Whether it's a private residence or a large-scale government infrastructure project, Ripal Design brings the expertise to make it happen.")); ?>
                </p>
                     <a href="<?php echo esc_attr(rtrim((string)BASE_PATH, '/') . PUBLIC_PATH_PREFIX . '/contact_us.php'); ?>" 
                         class="footer-cta-btn inline-flex items-center bg-rajkot-rust hover:bg-red-700 text-white font-serif px-8 py-3 transition-colors duration-300 no-underline" 
                   role="button">
                    <?php echo esc($footerText('cta_button', 'Start Your Project')); ?> <i data-lucide="arrow-right" class="ml-2 w-5 h-5"></i>
                </a>
            </div>

            <div class="bg-black/20 p-8 border border-white/5">
                <h3 class="text-xl font-serif text-gray-400 mb-6"><?php echo esc($footerText('contact_heading', 'Contact Us')); ?></h3>
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <i data-lucide="map-pin" class="w-5 h-5 text-rajkot-rust shrink-0"></i>
                        <address class="not-italic text-gray-400 text-sm">
                            <?php
                            if (function_exists('public_content_get_html')) {
                                echo public_content_get_html('common_footer', 'address_html', 'Ripal Design Rajkot<br>538 Jasal Complex, Nanavati Chowk,<br>150ft Ring Road, Rajkot, Gujarat');
                            } else {
                                echo 'Ripal Design Rajkot<br>538 Jasal Complex, Nanavati Chowk,<br>150ft Ring Road, Rajkot, Gujarat';
                            }
                            ?>
                        </address>
                    </div>
                    <div class="flex items-center gap-3">
                        <i data-lucide="mail" class="w-5 h-5 text-rajkot-rust"></i>
                        <a href="mailto:<?php echo esc_attr($footerText('email', 'projects@ripaldesign.in')); ?>" class="footer-contact-link text-gray-400 hover:text-rajkot-rust transition-colors text-sm">
                            <?php echo esc($footerText('email', 'projects@ripaldesign.in')); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-16 pt-8 border-t border-white/5 flex flex-col md:flex-row justify-between items-center text-[10px] font-bold uppercase tracking-[0.2em] text-gray-500">
            <div>&copy; <?php echo $currentYear; ?> <?php echo esc($footerText('copyright_brand', 'Ripal Design')); ?>. <?php echo esc($footerText('copyright_suffix', 'All rights reserved.')); ?></div>
            <div class="flex gap-6 mt-4 md:mt-0">
                <a href="<?php echo esc_attr(BASE_PATH); ?>/sitemap.php" class="footer-legal-link hover:text-white transition-colors no-underline"><?php echo esc($footerText('privacy_label', 'Privacy')); ?></a>
                <a href="<?php echo esc_attr(BASE_PATH); ?>/sitemap.php" class="footer-legal-link hover:text-white transition-colors no-underline"><?php echo esc($footerText('terms_label', 'Terms')); ?></a>
            </div>
        </div>
    </div>
</footer>

<div id="transition-curtain" aria-hidden="true"></div>
<div id="transition-orb" aria-hidden="true"></div>

<script>
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>

<?php
// Include Bootstrap JavaScript (needed for footer and header)
echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>' . "\n";
// echo '<script src="js/bootstrap.bundle.min.js"></script>' . "\n";

// Include main application script if it exists
if (!isset($DISABLE_EXTERNAL_CSS) || !$DISABLE_EXTERNAL_CSS) {
    $scriptCandidates = [
        '/public/scripts.js',
        '/scripts.js',
        '/assets/js/scripts.js'
    ];

    foreach ($scriptCandidates as $script) {
        $filePath = PROJECT_ROOT . str_replace('/', DIRECTORY_SEPARATOR, $script);
        if (file_exists($filePath)) {
            $publicRemoved = preg_replace('~^/public~i', '', $script);
            $href = rtrim((string)BASE_PATH, '/') . PUBLIC_PATH_PREFIX . $publicRemoved;
            echo '<script defer src="' . esc_attr($href) . '"></script>' . "\n";
            break;
        }
    }
}

// Include global tab persistence script if present
$persistPath = PROJECT_ROOT . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'persist-tabs.js';
if (file_exists($persistPath)) {
    echo '<script defer src="' . esc_attr(rtrim(BASE_PATH, '/') . '/assets/js/persist-tabs.js') . '"></script>' . "\n";
}

$pageTransitionPath = PROJECT_ROOT . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'js' . DIRECTORY_SEPARATOR . 'page-transitions.js';
if (file_exists($pageTransitionPath)) {
    echo '<script defer src="' . esc_attr(rtrim(BASE_PATH, '/') . '/assets/js/page-transitions.js') . '"></script>' . "\n";
}

// Render any enqueued scripts from util.php
if (function_exists('render_footer_scripts')) {
    render_footer_scripts();
}
?>
<script>
    (function(){
        function getRoot(){ return document.getElementById('phantom-ui-root') || document.querySelector('phantom-ui[loading]'); }
        function reveal(){
            var el = getRoot();
            if (!el) return;
            try { if (el.hasAttribute && el.hasAttribute('loading')) el.removeAttribute('loading'); } catch(e) {}
        }
        if (document.readyState === 'complete') {
            // page already loaded
            requestAnimationFrame(function(){ setTimeout(reveal, 50); });
        } else {
            window.addEventListener('load', function(){ setTimeout(reveal, 50); });
        }
        // Ensure we always reveal after a short timeout as a fallback
        setTimeout(reveal, 3000);
    })();
</script>
