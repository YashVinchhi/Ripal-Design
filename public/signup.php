<?php
require_once __DIR__ . '/../Common/public_shell.php';

if (function_exists('redirect_authenticated_user_to_dashboard')) {
    redirect_authenticated_user_to_dashboard();
}

// Ensure CSRF token is generated
if (function_exists('generate_csrf_token')) {
    generate_csrf_token();
}

$content = function_exists('public_content_page_values') ? public_content_page_values('signup') : [];
$ct = static fn ($key, $default = '') => (string)($content[$key] ?? $default);
$error = (string)($_SESSION['register_error'] ?? '');
unset($_SESSION['login_error'], $_SESSION['register_error'], $_SESSION['active_form']);

rd_page_start([
    'title' => $ct('page_title', 'Create Account'),
    'description' => $ct('meta_description', 'Create a Ripal Design client account.'),
    'url' => rd_public_url('signup.php'),
]);
?>
<main id="main" class="auth-wrap">
    <section class="auth-layout">
        <div class="hero-copy">
            <p class="eyebrow">Create account</p>
            <h1><?php echo esc($ct('form_title', 'Start with a clean account setup.')); ?></h1>
            <p><?php echo esc($ct('form_subtitle', 'Use one account for project communication, approvals, files, and billing updates.')); ?></p>
            <a class="button button-secondary" href="<?php echo esc_attr(rd_public_url('login.php')); ?>">Already registered?</a>
        </div>
        <article class="auth-card" aria-labelledby="signupTitle">
            <p class="eyebrow">Signup</p>
            <h1 id="signupTitle">Create account</h1>
            <?php if ($error !== ''): ?><p class="notice notice-error"><?php echo esc($error); ?></p><?php endif; ?>
            <form id="signupForm" class="auth-form" method="post" action="<?php echo esc_attr(rd_public_url('login_register.php')); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo h(generate_csrf_token()); ?>">
                <div class="form-grid">
                    <div class="field">
                        <label for="firstName">First name</label>
                        <input id="firstName" name="firstName" required autocomplete="given-name" data-validation="required min alphabetic" data-min="2">
                        <span id="firstName_error" class="text-danger" role="alert"></span>
                    </div>
                    <div class="field">
                        <label for="lastName">Last name</label>
                        <input id="lastName" name="lastName" required autocomplete="family-name" data-validation="required min alphabetic" data-min="2">
                        <span id="lastName_error" class="text-danger" role="alert"></span>
                    </div>
                </div>
                <div class="field">
                    <label for="email">Email address</label>
                    <input id="email" type="email" name="email" required autocomplete="email" data-validation="required email">
                    <span id="email_error" class="text-danger" role="alert"></span>
                </div>
                <div class="field">
                    <label for="phone">Phone number</label>
                    <input id="phone" type="tel" name="phoneNumber" required autocomplete="tel" data-validation="required number min max" data-min="10" data-max="10">
                    <span id="phoneNumber_error" class="text-danger" role="alert"></span>
                </div>
                <div class="field">
                    <label for="signupPassword">Password</label>
                    <div class="password-wrap">
                        <input id="signupPassword" type="password" name="password" required autocomplete="new-password" data-validation="required strongPassword">
                        <button type="button" class="toggle-password-btn" aria-label="Show password" aria-pressed="false"><i class="fa-solid fa-eye" aria-hidden="true"></i></button>
                    </div>
                    <p class="field-help">Use at least 8 characters with a number.</p>
                    <span id="password_error" class="text-danger" role="alert"></span>
                </div>
                <div class="field">
                    <label for="confirmPassword">Confirm password</label>
                    <div class="password-wrap">
                        <input id="confirmPassword" type="password" name="confirmPassword" required autocomplete="new-password" data-validation="required confirmPassword">
                        <button type="button" class="toggle-password-btn" aria-label="Show password" aria-pressed="false"><i class="fa-solid fa-eye" aria-hidden="true"></i></button>
                    </div>
                    <span id="confirmPassword_error" class="text-danger" role="alert"></span>
                </div>
                <label class="check-wrap" for="terms">
                    <input id="terms" type="checkbox" name="terms" required data-validation="required">
                    I accept the <a href="<?php echo esc_attr(rd_public_url('terms.php')); ?>">terms</a> and <a href="<?php echo esc_attr(rd_public_url('privacy.php')); ?>">privacy policy</a>
                </label>
                <span id="terms_error" class="text-danger" role="alert"></span>
                <button class="button button-primary" type="submit" name="signup">Create Account</button>
                <p class="switch-auth">Already have an account? <a href="<?php echo esc_attr(rd_public_url('login.php')); ?>">Login</a></p>
            </form>
        </article>
    </section>
</main>
<script src="<?php echo esc_attr(rd_public_url('js/validation.js')); ?>" defer></script>
<script>
    document.querySelectorAll('.toggle-password-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            var input = button.closest('.password-wrap').querySelector('input');
            var showing = input.type === 'text';
            input.type = showing ? 'password' : 'text';
            button.setAttribute('aria-pressed', showing ? 'false' : 'true');
            button.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
            button.innerHTML = '<i class="fa-solid ' + (showing ? 'fa-eye' : 'fa-eye-slash') + '" aria-hidden="true"></i>';
        });
    });
</script>
<?php rd_page_end(); ?>
