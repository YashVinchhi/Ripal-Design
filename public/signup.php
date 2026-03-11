<?php

session_start();

$errors = [
    'login' => $_SESSION['login_error'] ?? '',
    'signup' => $_SESSION['register_error'] ?? ''
];
$active_form = $_SESSION['active_form'] ?? 'login';

session_unset();

function showError($error)
{
    return !empty($error) ? "<p class='alert alert-danger'>$error</p>" : '';
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
    <title>Signup - Ripal Design</title>
    <link rel="stylesheet" href="./css/login.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-4.0.0.js" integrity="sha256-9fsHeVnKBvqh3FB2HYu7g2xseAZ5MlN6Kz/qnkASV8U=" crossorigin="anonymous"></script>
    <script src="./js/validation.js"></script>
</head>

<body>
    <!-- signup form -->

    <main>
        <a href="index.php" class="btn btn-secondary top-back-btn">Back</a>

        <div class="login-card text-center">
            <div class="card-login" style="margin-bottom: 30vh;">
                <h3 class="mb-4">Signup</h3>
                <form id="signupForm" method="post" action="login_register.php" novalidate class="<?= showActive('signup', $active_form) ? 'active' : '' ?>">
                    <?= showError($errors['signup']); ?>
                    <!-- first and last name side-by-side -->
                    <div class="row g-3">
                        <div class="col-md-6 text-start">
                            <label class="firstName">First Name</label>
                            <input type="text" class="form-control" id="firstName" name="firstName" placeholder="Enter your first name" data-validation="required min alphabetic" data-min="2">
                            <span id="firstName_error" class="text-danger"></span>
                        </div>
                        <div class="col-md-6 text-start">
                            <label class="lastName">Last Name</label>
                            <input type="text" class="form-control" id="lastName" name="lastName" placeholder="Enter your last name" data-validation="required min alphabetic" data-min="2">
                            <span id="lastName_error" class="text-danger"></span>
                        </div>
                    </div>
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
                            <input id="confirmPassword_confirm" name="password" type="password" class="form-control" placeholder="Enter your password" data-validation="required strongPassword">
                            <img src="./css/eye/eye_close.svg" alt="Show password" id="eyeicon" class="toggle-password" aria-hidden="false" role="button" tabindex="0" aria-label="Toggle password visibility">
                        </div>
                        <span id="password_error" class="text-danger"></span>
                    </div>
                    <!-- for confirm password  -->
                    <div class="mb-3 text-start">
                        <label class="Password">Confirm Password</label>
                        <div class="input-with-icon">
                            <input id="Password" name="confirmPassword" type="password" class="form-control" placeholder="Confirm your password" data-validation="required confirmPassword">
                            <img src="./css/eye/eye_close.svg" alt="Show password" id="eyeicon1" class="toggle-password" aria-hidden="false" role="button" tabindex="0" aria-label="Toggle password visibility">
                        </div>
                        <span id="confirmPassword_error" class="text-danger"></span>
                    </div>

                    <!-- for phone number  -->

                    <div class="mb-3 text-start">
                        <label class="phone">Phone Number</label>
                        <input type="tel" class="form-control " id="phone" name="phoneNumber" placeholder="Enter your phone number" data-validation="required number min max" data-min="10" data-max="10">
                        <span id="phoneNumber_error" class="text-danger"></span>
                    </div>

                    <!-- for remider  -->
                    <div class="remider">
                        <div class="re">
                            <input type="checkbox" id="remember" name="terms" class="form-check-input" data-validation="required">
                            <label for="terms" class="form-check-label">Remember me</label>
                            <span id="terms_error" class="text-danger d-none"></span>
                        </div>
                    </div>
                    <button type="submit" name="signup" class="btn-1">Create Account</button>
                    <p class="mt-4">Already have an account? <a href="./login.php" class="text-decoration-none text-white">login</a> </p>

                </form>
            </div>
        </div>
    </main>

</body>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggles = document.querySelectorAll('.toggle-password');
        if (!toggles || toggles.length === 0) return;
        const openSrc = './css/eye/eye_open.svg';
        const closeSrc = './css/eye/eye_close.svg';

        toggles.forEach(function(toggle){
            const container = toggle.closest('.input-with-icon');
            const input = container ? container.querySelector('input') : null;
            if (!input) return;
            toggle.style.cursor = 'pointer';

            function doToggle(){
                const showing = input.type === 'text';
                input.type = showing ? 'password' : 'text';
                toggle.src = showing ? closeSrc : openSrc;
                toggle.alt = showing ? 'Show password' : 'Hide password';
            }

            toggle.addEventListener('click', doToggle);
            toggle.addEventListener('keydown', function(e){
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); doToggle(); }
            });
        });
    });
</script>

</html>