<?php

require_once __DIR__ . '/../includes/config.php';

session_start();

$errors = [
  'login' => $_SESSION['login_error'] ?? '',
  'signup' => $_SESSION['register_error'] ?? ''
];
$active_form = $_SESSION['active_form'] ?? 'login';

session_unset();

function showError($error)
{
  if (empty($error)) {
    return '';
  }
  return "<p class='alert alert-danger'>" . htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') . "</p>";
}

function showActive($form, $active_form)
{
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
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=Inter:wght@400;500;600&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./css/login.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="./js/validation.js"></script>
</head>

<body class="auth-page">
  <div class="grain"></div>
  <?php $HEADER_MODE = 'public';
  require_once __DIR__ . '/../includes/header.php'; ?>

  <main class="auth-main auth-main-public">
    <section class="auth-card-wrap" aria-labelledby="loginTitle">
      <div class="auth-card auth-card-compact">
        <h1 class="auth-title" id="loginTitle">Login</h1>
        <p class="auth-subtitle">Welcome back. Sign in to continue your project journey.</p>

        <form id="loginForm" method="post" novalidate action="<?= htmlspecialchars(BASE_PATH . PUBLIC_PATH_PREFIX . '/login_register.php', ENT_QUOTES, 'UTF-8'); ?>" class="auth-form">
          <?= showError($errors['login']); ?>

          <div class="field">
            <label for="email">Email Address</label>
            <input type="email" class="form-control" id="email" name="email" placeholder="youremail@example.com" data-validation="required email" autocomplete="email">
            <span id="email_error" class="text-danger"></span>
          </div>

          <div class="field">
            <label for="password">Password</label>
            <div class="input-with-icon max">
              <input id="password" name="password" type="password" class="form-control" placeholder="Enter your password" data-validation="required strongPassword" autocomplete="current-password">
              <button type="button" class="toggle-password-btn" aria-label="Toggle password visibility" aria-pressed="false">
                <img src="./css/eye/eye_close.svg" alt="Show password" class="toggle-password" aria-hidden="true">
              </button>

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
  <?php require_once __DIR__ . '/../Common/footer.php'; ?>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const toggleBtn = document.querySelector('.toggle-password-btn');
      const toggle = document.querySelector('.toggle-password');
      const pwd = document.getElementById('password');
      if (!toggle || !toggleBtn || !pwd) return;

      function doToggle() {
        const showing = pwd.type === 'text';
        pwd.type = showing ? 'password' : 'text';
        const openSrc = './css/eye/eye_open.svg';
        const closeSrc = './css/eye/eye_close.svg';
        toggle.src = showing ? closeSrc : openSrc;
        toggle.alt = showing ? 'Show password' : 'Hide password';
        toggleBtn.setAttribute('aria-pressed', showing ? 'false' : 'true');
      }

      toggleBtn.addEventListener('click', doToggle);
    });
  </script>

</body>

</html>