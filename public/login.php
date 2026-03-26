<?php 

require_once __DIR__ . '/../includes/config.php';

session_start();

$errors = [
    'login' => $_SESSION['login_error'] ?? '',
    'signup' => $_SESSION['register_error'] ?? ''
];
$active_form = $_SESSION['active_form'] ?? 'login';

session_unset();

function showError($error){
  if (empty($error)) {
    return '';
  }
  return "<p class='alert alert-danger'>" . htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') . "</p>";
}

function showActive($form, $active_form){
    return $active_form === $form ? 'active' : '';
}
?>




<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Login - Ripal Design</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./css/login.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="./js/validation.js"></script>
</head>

<body class="auth-page">
  <header class="auth-topbar">
    <a href="index.php" class="back-link" aria-label="Go back to home">Back</a>
    <div class="brand">Ripal Design</div>
  </header>

  <main class="auth-main">
    <section class="auth-card-wrap" aria-labelledby="loginTitle">
      <div class="auth-card">
        <h1 class="auth-title" id="loginTitle">Login</h1>
        <p class="auth-subtitle">Welcome back. Sign in to continue your project journey.</p>

        <form id="loginForm" method="post" novalidate action="<?= htmlspecialchars(BASE_PATH . PUBLIC_PATH_PREFIX . '/login_register.php', ENT_QUOTES, 'UTF-8'); ?>" class="auth-form">
          <?= showError($errors['login']); ?>

          <div class="field">
            <label for="email">Email Address</label>
            <input type="email" class="form-control" id="email" name="email" placeholder="curator@studio.com" data-validation="required email" autocomplete="email">
            <span id="email_error" class="text-danger"></span>
          </div>

          <div class="field">
            <label for="password">Password</label>
            <div class="input-with-icon">
              <input id="password" name="password" type="password" class="form-control" placeholder="Enter your password" data-validation="required strongPassword" autocomplete="current-password">
              <img src="./css/eye/eye_close.svg" alt="Show password" class="toggle-password" aria-hidden="false" role="button" tabindex="0" aria-label="Toggle password visibility">
            </div>
            <span id="password_error" class="text-danger"></span>
          </div>

          <div class="meta-row">
            <div class="check-wrap">
              <input type="checkbox" id="remember" name="terms" class="form-check-input" data-validation="required">
              <label for="remember" class="form-check-label">Remember me</label>
              <span id="terms_error" class="text-danger d-none"></span>
            </div>
            <a href="./forgot.php" class="meta-link">Forgot password?</a>
          </div>

          <button type="submit" name="login" class="btn-1">Login</button>

          <p class="switch-auth">Don't have an account? <a href="./signup.php">Sign up</a></p>
        </form>
      </div>
    </section>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function(){
      const toggle = document.querySelector('.toggle-password');
      const pwd = document.getElementById('password');
      if (!toggle || !pwd) return;
      toggle.style.cursor = 'pointer';
      function doToggle(){
        const showing = pwd.type === 'text';
        pwd.type = showing ? 'password' : 'text';
        // swap icon
        const openSrc = './css/eye/eye_open.svg';
        const closeSrc = './css/eye/eye_close.svg';
        toggle.src = showing ? closeSrc : openSrc;
        toggle.alt = showing ? 'Show password' : 'Hide password';
      }
      toggle.addEventListener('click', doToggle);
      // keyboard accessibility
      toggle.addEventListener('keydown', function(e){
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); doToggle(); }
      });
    });
  </script>

</body>

</html>