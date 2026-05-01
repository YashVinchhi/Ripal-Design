
<?php
require_once __DIR__ . '/../Common/public_shell.php';

$message = '';
$type = '';

if (isset($_COOKIE['flash_message'])) {
    $message = $_COOKIE['flash_message'];
    $type = $_COOKIE['flash_type'] ?? 'error';

    $secure = function_exists('app_is_https') ? app_is_https() : (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $clearCookie = ['expires' => time() - 3600, 'path' => '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Strict'];
    setcookie('flash_message', '', $clearCookie);
    setcookie('flash_type', '', $clearCookie);
} else {
    $message = $_GET['message'] ?? '';
    $type = $_GET['type'] ?? '';
}

$forgotContent = function_exists('public_content_page_values') ? public_content_page_values('forgot') : [];
$ct = static function ($key, $default = '') use ($forgotContent) {
    return (string)($forgotContent[$key] ?? $default);
};

rd_page_start([
    'title' => $ct('page_title', 'Forgot Password'),
    'description' => $ct('meta_description', 'Reset your Ripal Design account password.'),
    'url' => rd_public_url('forgot.php'),
]);
?>
<main id="main" class="auth-wrap">
    <section class="auth-layout">
        <div class="hero-copy">
            <p class="eyebrow">Password Recovery</p>
            <h1><?php echo esc($ct('hero_title', 'Reset your password.')); ?></h1>
            <p><?php echo esc($ct('hero_subtitle', 'Enter your email address and we\'ll send you a link to reset your password.')); ?></p>
        </div>
        <article class="auth-card" aria-labelledby="forgotTitle">
            <p class="eyebrow"><?php echo esc($ct('form_label', 'Forgot Password')); ?></p>
            <h2 id="forgotTitle"><?php echo esc($ct('form_title', 'Forgot Password')); ?></h2>
            
            <?php if ($message !== ''): ?>
                <p class="notice <?php echo ($type === 'success') ? 'notice-success' : 'notice-error'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </p>
            <?php endif; ?>

            <form id="forgotPasswordForm" method="post" action="<?php echo esc_attr(rd_public_url('send_reset_password.php')); ?>" class="auth-form" novalidate>
                <?php echo csrf_token_field(); ?>
                
                <div class="field">
                    <label for="email"><?php echo esc($ct('label_email', 'Email address')); ?></label>
                    <input id="email" type="email" name="email" required autocomplete="email" placeholder="<?php echo esc_attr($ct('placeholder_email', 'you@example.com')); ?>" data-validation="required email">
                    <span id="email_error" class="text-danger" role="alert"></span>
                </div>

                <button class="button button-primary" type="submit"><?php echo esc($ct('button_send_link', 'Send Reset Link')); ?></button>
                <p class="switch-auth"><a href="<?php echo esc_attr(rd_public_url('login.php')); ?>"><?php echo esc($ct('link_back_to_login', 'Back to login')); ?></a></p>
            </form>
        </article>
    </section>
</main>
<script src="<?php echo esc_attr(rd_public_url('js/validation.js')); ?>" defer></script>
<?php rd_page_end(); ?>
