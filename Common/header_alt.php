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

foreach ($candidates as $c) {
  $filePath = PROJECT_ROOT . str_replace('/', DIRECTORY_SEPARATOR, $c);
  if (file_exists($filePath)) {
    echo '<link rel="stylesheet" href="' . htmlspecialchars(BASE_PATH . $c, ENT_QUOTES) . '">' . "\n";
    break;
  }
}
?>

<!-- Alternate header: left hamburger, full-screen overlay, brand-colored -->
<style>
  :root {
    --alt-overlay-bg: rgba(115, 18, 9, 0.96);
    --alt-text: #fff;
  }

  nav.alt-header {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 64px;
    display: flex;
    align-items: center;
    padding: 0 20px;
    z-index: 9999;
    background: transparent;
  }

  .alt-logo {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .alt-menu {
    margin-left: auto;
  }

  .alt-menu .alt-btn {
    background: transparent;
    border: 0;
    color: var(--alt-text);
    font-size: 18px;
    padding: 8px;
  }

  /* Overlay becomes a right-side sliding panel that occupies max 25% of screen */
  #altOverlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.25);
    opacity: 0;
    pointer-events: none;
    transition: opacity .22s ease;
    z-index: 10000;
  }

  #altOverlay.open {
    opacity: 1;
    pointer-events: auto;
  }

  .alt-panel {
    position: absolute;
    right: 0;
    top: 0;
    height: 100%;
    width: min(25%, 360px);
    max-width: 360px;
    background: var(--alt-overlay-bg);
    box-shadow: -8px 0 24px rgba(0, 0, 0, 0.2);
    transform: translateX(100%);
    transition: transform .28s ease;
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }

  #altOverlay.open .alt-panel {
    transform: translateX(0);
  }

  .alt-panel {
    --spot-x: 50%;
    --spot-y: 50%;
  }

  .alt-panel::before {
    /* subtle grid pattern */
    content: '';
    position: absolute;
    inset: 0;
    background-image:
      linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px),
      linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
    background-size: 24px 24px, 24px 24px;
    mix-blend-mode: overlay;
    pointer-events: none;
    opacity: 0.9;
  }

  .alt-panel::after {
    /* spotlight that follows pointer */
    content: '';
    position: absolute;
    width: 700px;
    height: 700px;
    left: calc(var(--spot-x) - 350px);
    top: calc(var(--spot-y) - 350px);
    pointer-events: none;
    background: radial-gradient(circle at center, rgba(255, 255, 255, 0.08) 0%, rgba(255, 255, 255, 0.02) 25%, transparent 50%);
    transition: left 0.05s linear, top 0.05s linear;
  }

  .alt-panel nav {
    position: relative;
    z-index: 2;
    display: flex;
    flex-direction: column;
    gap: 14px;
    padding: 32px;
    padding-top: 48px;
    overflow: auto;
  }

  .alt-panel a {
    color: var(--alt-text);
    font-size: 18px;
    text-decoration: none;
  }

  .alt-panel .panel-footer {
    margin-top: auto;
    padding: 20px;
    display: flex;
    gap: 10px;
    justify-content: center;
    z-index: 2;
  }

  .btn-alt {
    padding: 10px 14px;
    border-radius: 8px;
    font-weight: 600;
    border: 2px solid rgba(255, 255, 255, 0.12);
  }

  .btn-login {
    background: transparent;
    color: var(--alt-text);
  }

  .btn-signup {
    background: #fff;
    color: var(--alt-overlay-bg);
  }

  /* Ensure sign-up button text overrides the general anchor color inside panel */
  .alt-panel .panel-footer .btn-signup {
    color: var(--alt-overlay-bg);
  }

  .alt-hamburger {
    width: 36px;
    height: 24px;
    display: inline-flex;
    flex-direction: column;
    justify-content: space-between;
  }

  .alt-hamburger span {
    display: block;
    height: 3px;
    background: var(--alt-text);
    border-radius: 2px;
  }
</style>

<nav class="alt-header">
  <div class="alt-logo">
    <a href="<?php echo BASE_PATH; ?>/public/index.php"><img src="<?php echo BASE_PATH; ?>/assets/Content/Logo.png" alt="logo" style="height:2rem"></a>
    <div style="color:var(--alt-text); font-weight:700;">Ripal Design</div>
  </div>
  <div class="alt-menu">
    <button id="altMenuBtn" class="alt-btn" aria-label="Open menu">
      <span class="alt-hamburger"><span></span><span></span><span></span></span>
    </button>
  </div>
</nav>

<div id="altOverlay">
  <div class="alt-panel" role="dialog" aria-modal="true" aria-label="Site menu">
    <nav>
      <a href="<?php echo BASE_PATH; ?>/public/index.php">Home</a>
      <a href="<?php echo BASE_PATH; ?>/public/services.php">Services</a>
      <a href="<?php echo BASE_PATH; ?>/public/about_us.php">About</a>
      <a href="<?php echo BASE_PATH; ?>/public/contact_us.php">Contact</a>
    </nav>
    <div class="panel-footer">
      <a href="<?php echo BASE_PATH; ?>/public/login.php" class="btn-alt btn-login">Login</a>
      <a href="<?php echo BASE_PATH; ?>/public/login.php?action=signup" class="btn-alt btn-signup">Sign Up</a>
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
    }

    function close() {
      overlay.classList.remove('open');
    }

    function toggle() {
      if (overlay.classList.contains('open')) close();
      else open();
    }
    if (btn) btn.addEventListener('click', toggle);
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