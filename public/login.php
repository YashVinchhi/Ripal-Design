<?php
session_start();
// Simple login placeholder - implement authentication logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // TODO: validate credentials
  $_SESSION['user'] = $_POST['username'] ?? 'user';
  header('Location: ../dashboard/dashboard.php');
  exit;
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
  <script src="https://code.jquery.com/jquery-4.0.0.js" integrity="sha256-9fsHeVnKBvqh3FB2HYu7g2xseAZ5MlN6Kz/qnkASV8U=" crossorigin="anonymous"></script>
  <script src="./js/validation.js"></script>
</head>

<body>
  <a href="index.php" class="btn btn-secondary top-back-btn">Back</a>
  <!-- login form  -->
  <main>
    <div class="login-card text-center">


      <div class="card-login">
        <h3 class="mb-4">Login</h3>
        <form id="loginForm" method="post" novalidate>

          <!-- for email  -->
          <div class="mb-3 text-start">
            <label class="email">Email</label>
            <input type="email" class="form-control " id="email" name="email" placeholder="example@xyz.com" data-validation="required email">
            <span id="email_error" class="text-danger"></span>
          </div>

          <!-- for password  -->
          <div class="mb-3 text-start">
            <label class="Password">Password</label>
            <input id="password" name="password" type="password" class="form-control" placeholder="Enter your password" data-validation="required strongPassword">
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
          <button type="submit" class="btn-1">Login</button>
          <p class="mt-4">Don't have an account? <a href="./signup.php" class="text-decoration-none text-white">Sign up</a></p>
        </form>

      </div>
    </div>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>


</body>

</html>