<?php

require_once __DIR__ . '/../includes/init.php';

if (session_status() === PHP_SESSION_NONE) {
  @session_start();
}

if (function_exists('redirect_authenticated_user_to_dashboard')) {
  redirect_authenticated_user_to_dashboard();
}

$errors = [
  'login' => $_SESSION['login_error'] ?? '',
  'signup' => $_SESSION['register_error'] ?? ''
];
$active_form = $_SESSION['active_form'] ?? 'login';

unset($_SESSION['login_error'], $_SESSION['register_error'], $_SESSION['active_form']);

$loginContent = function_exists('public_content_page_values') ? public_content_page_values('login') : [];
$ct = static function ($key, $default = '') use ($loginContent) {
  return (string)($loginContent[$key] ?? $default);
};

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
  <title><?php echo esc($ct('page_title', 'Login - Ripal Design')); ?></title>
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
        <h1 class="auth-title" id="loginTitle"><?php echo esc($ct('form_title', 'Login')); ?></h1>
        <p class="auth-subtitle"><?php echo esc($ct('form_subtitle', 'Welcome back. Sign in to continue your project journey.')); ?></p>

        <form id="loginForm" method="post" novalidate action="<?= htmlspecialchars(BASE_PATH . PUBLIC_PATH_PREFIX . '/login_register.php', ENT_QUOTES, 'UTF-8'); ?>" class="auth-form">
          <?= showError($errors['login']); ?>

          <div class="field">
            <label for="email"><?php echo esc($ct('label_email', 'Email Address')); ?></label>
            <input type="email" class="form-control" id="email" name="email" placeholder="<?php echo esc_attr($ct('placeholder_email', 'youremail@example.com')); ?>" data-validation="required email" autocomplete="email">
            <span id="email_error" class="text-danger"></span>
          </div>

          <div class="field">
            <label for="password"><?php echo esc($ct('label_password', 'Password')); ?></label>
            <div class="input-with-icon max">
              <input id="password" name="password" type="password" class="form-control" placeholder="<?php echo esc_attr($ct('placeholder_password', 'Enter your password')); ?>" data-validation="required strongPassword" autocomplete="current-password">
              <button type="button" class="toggle-password-btn" aria-label="<?php echo esc_attr($ct('toggle_aria', 'Toggle password visibility')); ?>" aria-pressed="false">
                <img src="./css/eye/eye_close.svg" alt="<?php echo esc_attr($ct('toggle_show_alt', 'Show password')); ?>" class="toggle-password" aria-hidden="true">
              </button>

            </div>
            <span id="password_error" class="text-danger"></span>
          </div>

          <div class="meta-row">
            <div class="check-wrap">
              <input type="checkbox" id="remember" name="remember" class="form-check-input">
              <label for="remember" class="form-check-label"><?php echo esc($ct('label_remember', 'Remember me')); ?></label>
              <span id="terms_error" class="text-danger d-none"></span>
            </div>
            <a href="./forgot.php" class="meta-link"><?php echo esc($ct('link_forgot_password', 'Forgot password?')); ?></a>
          </div>

          <button type="submit" name="login" class="btn-1"><?php echo esc($ct('button_login', 'Login')); ?></button>

          <p class="switch-auth"><?php echo esc($ct('switch_prefix', "Don't have an account?")); ?> <a href="./signup.php"><?php echo esc($ct('switch_link', 'Sign up')); ?></a></p>
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
      const showLabel = <?php echo json_encode($ct('toggle_show_alt', 'Show password')); ?>;
      const hideLabel = <?php echo json_encode($ct('toggle_hide_alt', 'Hide password')); ?>;

      function doToggle() {
        const showing = pwd.type === 'text';
        pwd.type = showing ? 'password' : 'text';
        const openSrc = './css/eye/eye_open.svg';
        const closeSrc = './css/eye/eye_close.svg';
        toggle.src = showing ? closeSrc : openSrc;
        toggle.alt = showing ? showLabel : hideLabel;
        toggleBtn.setAttribute('aria-pressed', showing ? 'false' : 'true');
      }

      toggleBtn.addEventListener('click', doToggle);
    });
  </script>

</body>

</html>