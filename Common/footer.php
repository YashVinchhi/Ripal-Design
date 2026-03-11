<!-- Footer / CTA -->
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
    require_once __DIR__ . '/../includes/config.php';
}

$currentYear = date('Y');
?>

<footer class="site-footer bg-foundation-grey text-white pt-16 pb-8 px-4 font-sans" role="contentinfo">
    <div class="max-w-7xl mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
            <div>
                <h2 class="text-3xl font-serif font-bold mb-4">Ready to build something Iconic?</h2>
                <p class="text-gray-400 mb-8 max-w-lg">
                    Whether it's a private residence or a large-scale government infrastructure project, 
                    Ripal Design brings the expertise to make it happen.
                </p>
                <a href="<?php echo esc_attr(BASE_PATH); ?>/public/contact_us.php" 
                   class="inline-flex items-center bg-rajkot-rust hover:bg-red-700 text-white font-serif px-8 py-3 transition-colors duration-300 no-underline" 
                   role="button">
                    Start Your Project <i data-lucide="arrow-right" class="ml-2 w-5 h-5"></i>
                </a>
            </div>

            <div class="bg-black/20 p-8 border border-white/5">
                <h3 class="text-xl font-serif text-gray-400 mb-6">Contact Us</h3>
                <div class="space-y-4">
                    <div class="flex items-start gap-3">
                        <i data-lucide="map-pin" class="w-5 h-5 text-rajkot-rust shrink-0"></i>
                        <address class="not-italic text-gray-400 text-sm">
                            Ripal Design Rajkot<br>
                            538 Jasal Complex, Nanavati Chowk,<br>
                            150ft Ring Road, Rajkot, Gujarat
                        </address>
                    </div>
                    <div class="flex items-center gap-3">
                        <i data-lucide="mail" class="w-5 h-5 text-rajkot-rust"></i>
                        <a href="mailto:projects@ripaldesign.in" class="text-gray-400 hover:text-rajkot-rust transition-colors text-sm">
                            projects@ripaldesign.in
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-16 pt-8 border-t border-white/5 flex flex-col md:flex-row justify-between items-center text-[10px] font-bold uppercase tracking-[0.2em] text-gray-500">
            <div>&copy; <?php echo $currentYear; ?> Ripal Design. All rights reserved.</div>
            <div class="flex gap-6 mt-4 md:mt-0">
                <a href="<?php echo esc_attr(BASE_PATH); ?>/sitemap.php" class="hover:text-white transition-colors no-underline">Privacy</a>
                <a href="<?php echo esc_attr(BASE_PATH); ?>/sitemap.php" class="hover:text-white transition-colors no-underline">Terms</a>
            </div>
        </div>
    </div>
</footer>

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
        '/scripts.js',
        '/public/scripts.js',
        '/assets/js/scripts.js'
    ];

    foreach ($scriptCandidates as $script) {
        $filePath = PROJECT_ROOT . str_replace('/', DIRECTORY_SEPARATOR, $script);
        if (file_exists($filePath)) {
            echo '<script defer src="' . esc_attr(BASE_PATH . $script) . '"></script>' . "\n";
            break;
        }
    }
}

// Render any enqueued scripts from util.php
if (function_exists('render_footer_scripts')) {
    render_footer_scripts();
}
?>
