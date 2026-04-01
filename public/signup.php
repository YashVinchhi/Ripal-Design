
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

$signupContent = function_exists('public_content_page_values') ? public_content_page_values('signup') : [];
$ct = static function ($key, $default = '') use ($signupContent) {
    return (string)($signupContent[$key] ?? $default);
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
    <title><?php echo esc($ct('page_title', 'Signup - Ripal Design')); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=Inter:wght@400;500;600&family=Manrope:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/login.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="./js/validation.js"></script>
</head>

<body class="auth-page">
    <div class="grain"></div>
    <?php $HEADER_MODE = 'public'; require_once __DIR__ . '/../includes/header.php'; ?>

    <main class="auth-main auth-main-public">
        <section class="auth-card-wrap" aria-labelledby="signupTitle">
            <div class="auth-card auth-card-signup">
                <h1 class="auth-title" id="signupTitle"><?php echo esc($ct('form_title', 'Create Account')); ?></h1>
                <p class="auth-subtitle"><?php echo esc($ct('form_subtitle', 'Start your design experience with a curated account setup.')); ?></p>

                <form id="signupForm" method="post" action="<?= htmlspecialchars(BASE_PATH . PUBLIC_PATH_PREFIX . '/login_register.php', ENT_QUOTES, 'UTF-8'); ?>" novalidate class="auth-form <?= showActive('signup', $active_form) ? 'active' : '' ?>">
                    <?= showError($errors['signup']); ?>

                    <div class="field-grid">
                        <div class="field">
                            <label for="firstName"><?php echo esc($ct('label_first_name', 'First Name')); ?></label>
                            <input type="text" class="form-control" id="firstName" name="firstName" placeholder="<?php echo esc_attr($ct('placeholder_first_name', 'Enter your first name')); ?>" data-validation="required min alphabetic" data-min="2" autocomplete="given-name">
                            <span id="firstName_error" class="text-danger"></span>
                        </div>
                        <div class="field">
                            <label for="lastName"><?php echo esc($ct('label_last_name', 'Last Name')); ?></label>
                            <input type="text" class="form-control" id="lastName" name="lastName" placeholder="<?php echo esc_attr($ct('placeholder_last_name', 'Enter your last name')); ?>" data-validation="required min alphabetic" data-min="2" autocomplete="family-name">
                            <span id="lastName_error" class="text-danger"></span>
                        </div>
                    </div>

                    <div class="field">
                        <label for="username"><?php echo esc($ct('label_username', 'Username')); ?></label>
                        <input type="text" class="form-control" id="username" name="username" placeholder="<?php echo esc_attr($ct('placeholder_username', 'Choose a username')); ?>" data-validation="required min max" data-min="3" data-max="30" autocomplete="username">
                        <span id="username_error" class="text-danger"></span>
                    </div>

                    <div class="field">
                        <label for="email"><?php echo esc($ct('label_email', 'Email Address')); ?></label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="<?php echo esc_attr($ct('placeholder_email', 'youremail@example.com')); ?>" data-validation="required email" autocomplete="email">
                        <span id="email_error" class="text-danger"></span>
                    </div>

                    <div class="field">
                        <label for="confirmPassword_confirm"><?php echo esc($ct('label_password', 'Password')); ?></label>
                        <div class="input-with-icon">
                            <input id="confirmPassword_confirm" name="password" type="password" class="form-control" placeholder="<?php echo esc_attr($ct('placeholder_password', 'Enter your password')); ?>" data-validation="required strongPassword" autocomplete="new-password">
                            <button type="button" class="toggle-password-btn" aria-label="<?php echo esc_attr($ct('toggle_aria', 'Toggle password visibility')); ?>" aria-pressed="false">
                                <img src="./css/eye/eye_close.svg" alt="<?php echo esc_attr($ct('toggle_show_alt', 'Show password')); ?>" id="eyeicon" class="toggle-password" aria-hidden="true">
                            </button>
                        </div>
                        <small class="field-help"><?php echo esc($ct('password_help', 'Use at least 8 characters and 1 number.')); ?></small>
                        <span id="password_error" class="text-danger"></span>
                    </div>

                    <div class="field">
                        <label for="Password"><?php echo esc($ct('label_confirm_password', 'Confirm Password')); ?></label>
                        <div class="input-with-icon">
                            <input id="Password" name="confirmPassword" type="password" class="form-control" placeholder="<?php echo esc_attr($ct('placeholder_confirm_password', 'Confirm your password')); ?>" data-validation="required confirmPassword" autocomplete="new-password">
                            <button type="button" class="toggle-password-btn" aria-label="<?php echo esc_attr($ct('toggle_aria', 'Toggle password visibility')); ?>" aria-pressed="false">
                                <img src="./css/eye/eye_close.svg" alt="<?php echo esc_attr($ct('toggle_show_alt', 'Show password')); ?>" id="eyeicon1" class="toggle-password" aria-hidden="true">
                            </button>
                        </div>
                        <span id="confirmPassword_error" class="text-danger"></span>
                    </div>

                    <div class="field">
                        <label for="phone"><?php echo esc($ct('label_phone', 'Phone Number')); ?></label>
                        <input type="tel" class="form-control" id="phone" name="phoneNumber" placeholder="<?php echo esc_attr($ct('placeholder_phone', 'Enter your phone number')); ?>" data-validation="required number min max" data-min="10" data-max="10" autocomplete="tel">
                        <span id="phoneNumber_error" class="text-danger"></span>
                    </div>

                    <div class="meta-row">
                        <div class="check-wrap">
                            <input type="checkbox" id="remember" name="terms" class="form-check-input" data-validation="required">
                            <label for="remember" class="form-check-label"><?php echo esc($ct('label_terms', 'I accept terms and conditions')); ?></label>
                            <span id="terms_error" class="text-danger d-none"></span>
                        </div>
                    </div>

                    <button type="submit" name="signup" class="btn-1"><?php echo esc($ct('button_signup', 'Create Account')); ?></button>
                    <p class="switch-auth"><?php echo esc($ct('switch_prefix', 'Already have an account?')); ?> <a href="./login.php"><?php echo esc($ct('switch_link', 'Login')); ?></a></p>
                </form>
            </div>
        </section>
    </main>
    <?php require_once __DIR__ . '/../Common/footer.php'; ?>

</body>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleButtons = document.querySelectorAll('.toggle-password-btn');
        if (!toggleButtons || toggleButtons.length === 0) return;
        const openSrc = './css/eye/eye_open.svg';
        const closeSrc = './css/eye/eye_close.svg';
        const showLabel = <?php echo json_encode($ct('toggle_show_alt', 'Show password')); ?>;
        const hideLabel = <?php echo json_encode($ct('toggle_hide_alt', 'Hide password')); ?>;

        toggleButtons.forEach(function(toggleBtn){
            const toggle = toggleBtn.querySelector('.toggle-password');
            const container = toggleBtn.closest('.input-with-icon');
            const input = container ? container.querySelector('input') : null;
            if (!input || !toggle) return;

            function doToggle(){
                const showing = input.type === 'text';
                input.type = showing ? 'password' : 'text';
                toggle.src = showing ? closeSrc : openSrc;
                toggle.alt = showing ? showLabel : hideLabel;
                toggleBtn.setAttribute('aria-pressed', showing ? 'false' : 'true');
            }

            toggleBtn.addEventListener('click', doToggle);
        });
    });
</script>

</html>
