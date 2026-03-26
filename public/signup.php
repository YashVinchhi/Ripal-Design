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
    <title>Signup - Ripal Design</title>
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
        <section class="auth-card-wrap" aria-labelledby="signupTitle">
            <div class="auth-card auth-card-signup">
                <h1 class="auth-title" id="signupTitle">Create Account</h1>
                <p class="auth-subtitle">Start your design experience with a curated account setup.</p>

                <form id="signupForm" method="post" action="<?= htmlspecialchars(BASE_PATH . PUBLIC_PATH_PREFIX . '/login_register.php', ENT_QUOTES, 'UTF-8'); ?>" novalidate class="auth-form <?= showActive('signup', $active_form) ? 'active' : '' ?>">
                    <?= showError($errors['signup']); ?>

                    <div class="field-grid">
                        <div class="field">
                            <label for="firstName">First Name</label>
                            <input type="text" class="form-control" id="firstName" name="firstName" placeholder="Enter your first name" data-validation="required min alphabetic" data-min="2" autocomplete="given-name">
                            <span id="firstName_error" class="text-danger"></span>
                        </div>
                        <div class="field">
                            <label for="lastName">Last Name</label>
                            <input type="text" class="form-control" id="lastName" name="lastName" placeholder="Enter your last name" data-validation="required min alphabetic" data-min="2" autocomplete="family-name">
                            <span id="lastName_error" class="text-danger"></span>
                        </div>
                    </div>

                    <div class="field">
                        <label for="email">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="curator@studio.com" data-validation="required email" autocomplete="email">
                        <span id="email_error" class="text-danger"></span>
                    </div>

                    <div class="field">
                        <label for="confirmPassword_confirm">Password</label>
                        <div class="input-with-icon">
                            <input id="confirmPassword_confirm" name="password" type="password" class="form-control" placeholder="Enter your password" data-validation="required strongPassword" autocomplete="new-password">
                            <img src="./css/eye/eye_close.svg" alt="Show password" id="eyeicon" class="toggle-password" aria-hidden="false" role="button" tabindex="0" aria-label="Toggle password visibility">
                        </div>
                        <small class="field-help">Use at least 8 characters and 1 number.</small>
                        <span id="password_error" class="text-danger"></span>
                    </div>

                    <div class="field">
                        <label for="Password">Confirm Password</label>
                        <div class="input-with-icon">
                            <input id="Password" name="confirmPassword" type="password" class="form-control" placeholder="Confirm your password" data-validation="required confirmPassword" autocomplete="new-password">
                            <img src="./css/eye/eye_close.svg" alt="Show password" id="eyeicon1" class="toggle-password" aria-hidden="false" role="button" tabindex="0" aria-label="Toggle password visibility">
                        </div>
                        <span id="confirmPassword_error" class="text-danger"></span>
                    </div>

                    <div class="field">
                        <label for="phone">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phoneNumber" placeholder="Enter your phone number" data-validation="required number min max" data-min="10" data-max="10" autocomplete="tel">
                        <span id="phoneNumber_error" class="text-danger"></span>
                    </div>

                    <div class="meta-row">
                        <div class="check-wrap">
                            <input type="checkbox" id="remember" name="terms" class="form-check-input" data-validation="required">
                            <label for="remember" class="form-check-label">I accept terms and conditions</label>
                            <span id="terms_error" class="text-danger d-none"></span>
                        </div>
                    </div>

                    <button type="submit" name="signup" class="btn-1">Create Account</button>
                    <p class="switch-auth">Already have an account? <a href="./login.php">Login</a></p>
                </form>
            </div>
        </section>
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