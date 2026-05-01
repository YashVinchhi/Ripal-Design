<?php
require_once __DIR__ . '/../Common/public_shell.php';

if (function_exists('redirect_authenticated_user_to_dashboard')) {
    redirect_authenticated_user_to_dashboard();
}

// Ensure CSRF token is generated
if (function_exists('generate_csrf_token')) {
    generate_csrf_token();
}

$content = function_exists('public_content_page_values') ? public_content_page_values('login') : [];
$ct = static fn ($key, $default = '') => (string)($content[$key] ?? $default);
$error = (string)($_SESSION['login_error'] ?? '');
unset($_SESSION['login_error'], $_SESSION['register_error'], $_SESSION['active_form']);

rd_page_start([
    'title' => $ct('page_title', 'Login'),
    'description' => $ct('meta_description', 'Login to your Ripal Design account.'),
    'url' => rd_public_url('login.php'),
]);
?>
<main id="main" class="auth-wrap">
    <section class="auth-layout">
        <div class="hero-copy">
            <p class="eyebrow">Client portal</p>
            <h1><?php echo esc($ct('form_title', 'Welcome back.')); ?></h1>
            <p><?php echo esc($ct('form_subtitle', 'Sign in to continue project communication, files, billing, and review requests.')); ?></p>
            <a class="button button-secondary" href="<?php echo esc_attr(rd_public_url('contact_us.php')); ?>">Need access?</a>
        </div>
        <article class="auth-card" aria-labelledby="loginTitle">
            <p class="eyebrow">Login</p>
            <h1 id="loginTitle">Sign in</h1>
            <?php if ($error !== ''): ?><p class="notice notice-error"><?php echo esc($error); ?></p><?php endif; ?>
            <form id="loginForm" class="auth-form" method="post" action="<?php echo esc_attr(rd_public_url('login_register.php')); ?>">
                <input type="hidden" name="csrf_token" value="<?php echo h(generate_csrf_token()); ?>">
                <div class="field">
                    <label for="email"><?php echo esc($ct('label_email', 'Email address')); ?></label>
                    <input id="email" type="email" name="email" required autocomplete="email" placeholder="<?php echo esc_attr($ct('placeholder_email', 'you@example.com')); ?>" data-validation="required email">
                    <span id="email_error" class="text-danger" role="alert"></span>
                </div>
                <div class="field">
                    <label for="password"><?php echo esc($ct('label_password', 'Password')); ?></label>
                    <div class="password-wrap">
                        <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="<?php echo esc_attr($ct('placeholder_password', 'Enter your password')); ?>" data-validation="required">
                        <button type="button" class="toggle-password-btn" aria-label="Show password" aria-pressed="false"><i class="fa-solid fa-eye" aria-hidden="true"></i></button>
                    </div>
                    <span id="password_error" class="text-danger" role="alert"></span>
                </div>
                <div class="meta-row">
                    <label class="check-wrap" for="remember"><input id="remember" type="checkbox" name="remember"> Remember me</label>
                    <a class="meta-link" href="<?php echo esc_attr(rd_public_url('forgot.php')); ?>">Forgot password?</a>
                </div>
                <button class="button button-primary" type="submit" name="login">Login</button>
                <p class="switch-auth">New here? <a href="<?php echo esc_attr(rd_public_url('signup.php')); ?>">Create an account</a></p>
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
