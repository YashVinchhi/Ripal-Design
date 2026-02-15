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

<footer class="site-footer text-white pt-5 pb-4" role="contentinfo" style="background-color: rgba(0, 0, 0, 0.15); position: relative; z-index: 100;">
    <div class="container">
        <div class="row align-items-center gy-4">
            <div class="col-md-6">
                <h2 class="h4 fw-bold mb-2 font-serif">Ready to build something iconic?</h2>
                <p class="text-secondary mb-3">
                    Whether it's a private residence or a large-scale government infrastructure project, 
                    Ripal Design brings the expertise to make it happen.
                </p>
                <a href="<?php echo esc_attr(BASE_PATH); ?>/public/contact_us.php" 
                   class="btn btn-primary btn-lg rounded-0 px-4" 
                   role="button" 
                   aria-label="Start your project" 
                   style="background:#731209; border-color:#731209; color:#fff;">
                    Start Your Project <i class="bi bi-arrow-right ms-2" aria-hidden="true"></i>
                </a>
            </div>

            <div class="col-md-6">
                <div class="p-4" style="background: var(--bg-panel, #111); border:1px solid rgba(51,51,51,0.6); border-radius:0;">
                    <h3 class="h5 text-secondary mb-3 font-serif">Contact Us</h3>
                    <ul class="list-unstyled mb-0 text-secondary">
                        <li class="d-flex align-items-start mb-2">
                            <i class="bi bi-geo-alt-fill me-2 text-secondary" aria-hidden="true"></i>
                            <div class="text-secondary">
                                Ripal Design Rajkot<br>
                                538 Jasal Complex,<br>
                                Nanavati Chowk,<br>
                                150ft Ring Road,<br>
                                Rajkot, Gujarat, India
                            </div>
                        </li>
                        <li class="d-flex align-items-center">
                            <i class="bi bi-envelope-fill me-2 text-secondary" aria-hidden="true"></i>
                            <a href="mailto:projects@ripaldesign.in" class="text-secondary text-decoration-none">
                                projects@ripaldesign.in
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <hr class="border-secondary my-4 opacity-25">

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center small text-secondary">
            <div>&copy; <?php echo esc($currentYear); ?> Ripal Design. All rights reserved.</div>
            <div class="d-flex gap-3 mt-3 mt-md-0">
                <a href="#" class="text-secondary text-decoration-none">Privacy</a>
                <a href="#" class="text-secondary text-decoration-none">Terms</a>
            </div>
        </div>
    </div>
</footer>

<?php
// Include Bootstrap JavaScript (needed for footer and header)
echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>' . "\n";

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
