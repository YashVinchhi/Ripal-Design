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
    return !empty($error) ? "<p class='alert alert-danger'>$error</p>" : '';
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
  <link rel="stylesheet" href="./css/login.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="./js/validation.js"></script>
</head>

<body>
  <a href="index.php" class="btn btn-secondary top-back-btn">Back</a>
  <!-- login form  -->
  <main>
    <div class="login-card text-center">


      <div class="card-login">
        <h3 class="mb-4">Login</h3>
        <form id="loginForm" method="post" novalidate action="<?= htmlspecialchars(BASE_PATH . PUBLIC_PATH_PREFIX . '/login_register.php', ENT_QUOTES, 'UTF-8'); ?>">
        <?=  showError($errors['login']); ?>
          <!-- for email  -->
          <div class="mb-3 text-start">
            <label class="email">Email</label>
            <input type="email" class="form-control " id="email" name="email" placeholder="example@xyz.com" data-validation="required email">
            <span id="email_error" class="text-danger"></span>
          </div>

          <!-- for password  -->
          <div class="mb-3 text-start">
            <label class="Password">Password</label>
            <div class="input-with-icon">
              <input id="password" name="password" type="password" class="form-control" placeholder="Enter your password" data-validation="required strongPassword">
              <img src="./css/eye/eye_close.svg" alt="Show password" class="toggle-password" aria-hidden="false" role="button" tabindex="0" aria-label="Toggle password visibility">
            </div>
            <span id="password_error" class="text-danger"></span>
          </div>

          <!-- for remider  -->
          <div class="remider">
            <div class="re">
              <input type="checkbox" id="remember" name="terms" class="form-check-input" data-validation="required">
              <label for="terms" class="form-check-label">Remember me</label>
              <span id="terms_error" class="text-danger d-none"></span>
            </div>
            <a href="./forgot.php" class="text-decoration-none text-white">Forgot password?</a>
          </div>
          <button type="submit" name="login" class="btn-1">Login</button>
          <p class="mt-4">Don't have an account? <a href="./signup.php" class="text-decoration-none text-white">Sign up</a></p>
        </form>

      </div>
    </div>
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