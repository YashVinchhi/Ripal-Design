<?php
// header_alt.php - alternate header for non-dashboard pages
require_once __DIR__ . '/../includes/config.php';
if (session_status() === PHP_SESSION_NONE) {
  @session_start();
}

$candidates = [
  '/styles.css',
  '/public/styles.css',
  '/assets/css/styles.css',
  '/assets/styles.css'
];

echo '<link rel="preconnect" href="https://fonts.googleapis.com" />' . "\n";
echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />' . "\n";
echo '<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />' . "\n";
echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">' . "\n";
echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">' . "\n";
echo '<script src="https://cdn.tailwindcss.com"></script>' . "\n";
echo '<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          primary: "#731209",
        }
      }
    }
  }
</script>' . "\n";

foreach ($candidates as $c) {
  $filePath = PROJECT_ROOT . str_replace('/', DIRECTORY_SEPARATOR, $c);
  if (file_exists($filePath)) {
    echo '<link rel="stylesheet" href="' . htmlspecialchars(BASE_PATH . $c, ENT_QUOTES) . '">' . "\n";
    break;
  }
}
?>
<link rel="shortcut icon" href="<?php echo BASE_PATH; ?>/assets/Content/Vector.ico" type="image/x-icon">
<link rel="shortcut icon" href="../assets/Content/rd.ico" type="image/x-icon">

<!-- Alternate header: left hamburger, full-screen overlay, brand-colored -->
<link rel="stylesheet" href="<?php echo BASE_PATH; ?> /Common/header_alt.css">
<nav class="alt-header">
  <div class="alt-logo">
    <a href="<?php echo BASE_PATH; ?>/public/index.php"><img src="<?php echo BASE_PATH; ?>/assets/Content/Logo.png" alt="logo" style="height:3rem"></a>
    <div style="color:var(--alt-text); font-weight:700; font-size:1.5rem; text-shadow: 5px 5px 5px rgba(0,0,0);">Ripal Design</div>
  </div>
  <div class="alt-menu">
    <button id="altMenuBtn" class="alt-btn" aria-label="Open menu" aria-expanded="false" aria-controls="altOverlay">
      <span class="alt-hamburger">
        <span></span>
        <span></span>
        <span></span>
      </span>
    </button>
  </div>

</nav>

<div id="altOverlay">
  <div class="alt-panel" role="dialog" aria-modal="true" aria-label="Site menu">
    <nav>
      <a href="<?php echo BASE_PATH; ?>/public/index.php">Home</a>
      <a href="<?php echo BASE_PATH; ?>/public/services.php">Services</a>
      <a href="<?php echo BASE_PATH; ?>/public/products.php">Products</a>
      <a href="<?php echo BASE_PATH; ?>/public/about_us.php">About</a>
      <a href="<?php echo BASE_PATH; ?>/public/contact_us.php">Contact</a>
    </nav>
    <div class="panel-footer">
      <a href="<?php echo BASE_PATH; ?>/public/login.php" class="btn-alt btn-login">Login</a>
      <a href="<?php echo BASE_PATH; ?>/public/signup.php?action=signup" class="btn-alt btn-signup">Sign Up</a>
    </div>
  </div>
</div>

<script>
  (function() {
    var btn = document.getElementById('altMenuBtn');
    var overlay = document.getElementById('altOverlay');
    var panel = overlay && overlay.querySelector('.alt-panel');

    function open() {
      overlay.classList.add('open');
      if (btn) {
        btn.setAttribute('aria-expanded', 'true');
        var ham = btn.querySelector('.alt-hamburger');
        if (ham) ham.classList.add('active');
      }
    }

    function close() {
      overlay.classList.remove('open');
      if (btn) {
        btn.setAttribute('aria-expanded', 'false');
        var ham = btn.querySelector('.alt-hamburger');
        if (ham) ham.classList.remove('active');
      }
    }

    function toggle() {
      if (overlay.classList.contains('open')) close();
      else open();
    }
    if (btn) {
      // ensure initial aria state
      btn.setAttribute('aria-expanded', 'false');
      btn.addEventListener('click', toggle);
    }
    if (overlay) overlay.addEventListener('click', function(e) {
      // close when clicking outside the panel
      if (e.target === overlay) close();
    });
    // close on Escape
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') close();
    });

    // spotlight pointer interaction (throttled via RAF)
    if (panel) {
      var rect = null;
      var mx = 0,
        my = 0,
        rafId = null;

      function update() {
        if (!rect) rect = panel.getBoundingClientRect();
        panel.style.setProperty('--spot-x', (mx - rect.left) + 'px');
        panel.style.setProperty('--spot-y', (my - rect.top) + 'px');
        rafId = null;
      }
      panel.addEventListener('mousemove', function(e) {
        mx = e.clientX;
        my = e.clientY;
        if (!rafId) rafId = requestAnimationFrame(update);
      });
      panel.addEventListener('touchmove', function(e) {
        if (e.touches && e.touches[0]) {
          mx = e.touches[0].clientX;
          my = e.touches[0].clientY;
          if (!rafId) rafId = requestAnimationFrame(update);
        }
      }, {
        passive: true
      });
      panel.addEventListener('mouseleave', function() {
        rect = null;
        panel.style.setProperty('--spot-x', '50%');
        panel.style.setProperty('--spot-y', '50%');
      });
    }
  })();
</script>