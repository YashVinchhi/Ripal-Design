<?php
require_once __DIR__ . '/../Common/public_shell.php';

$resetContent = function_exists('public_content_page_values') ? public_content_page_values('reset_password') : [];
$ct = static function ($key, $default = '') use ($resetContent) {
    return (string)($resetContent[$key] ?? $default);
};

$message = $_GET['message'] ?? '';
$type = $_GET['type'] ?? '';
$token = $_GET['token'] ?? '';
$token_hash = $token !== '' ? hash("sha256", $token) : '';

$showForm = true;
$db = get_db();

if (!($db instanceof PDO)) {
    $message = $ct('status_db_unavailable', 'Database connection unavailable. Please try later.');
    $type = 'error';
    $showForm = false;
} elseif ($type === 'success' && $message !== '' && $token === '') {
    $showForm = false;
} else {
    if ($token === '') {
        $message = $ct('status_invalid_token', 'Invalid reset token.');
        $type = 'error';
        $showForm = false;
    } else {
        $stmt = $db->prepare('SELECT id, reset_token_expires FROM users WHERE token_reset = ? LIMIT 1');
        $stmt->execute([$token_hash]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if ($showForm && $user === false) {
        $message = $ct('status_token_not_found', 'Token not found.');
        $type = 'error';
        $showForm = false;
    } elseif ($showForm && strtotime((string) ($user['reset_token_expires'] ?? '')) <= time()) {
        $message = $ct('status_token_expired', 'Token has expired.');
        $type = 'error';
        $showForm = false;
    }
}

rd_page_start([
    'title' => $ct('page_title', 'Reset Password'),
    'description' => $ct('meta_description', 'Create a new password for your Ripal Design account.'),
    'url' => rd_public_url('reset_password.php'),
]);
?>
<main id="main" class="auth-wrap">
    <section class="auth-layout">
        <div class="hero-copy">
            <p class="eyebrow">Password Reset</p>
            <h1><?php echo esc($ct('hero_title', 'Create a new password.')); ?></h1>
            <p><?php echo esc($ct('hero_subtitle', 'Enter a strong password to regain access to your account.')); ?></p>
        </div>
        <article class="auth-card" aria-labelledby="resetTitle">
            <p class="eyebrow"><?php echo esc($ct('form_label', 'Reset Password')); ?></p>
            <h2 id="resetTitle"><?php echo esc($ct('form_title', 'Reset Password')); ?></h2>

            <?php if ($message !== ''): ?>
                <p class="notice <?php echo ($type === 'success') ? 'notice-success' : 'notice-error'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </p>
                <?php if ($type === 'success'): ?>
                    <p class="switch-auth"><a href="<?php echo esc_attr(rd_public_url('login.php')); ?>"><?php echo esc($ct('link_after_success', 'Go to Login Page')); ?></a></p>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($showForm): ?>
                <form method="post" action="<?php echo esc_attr(rd_public_url('update_password.php')); ?>" class="auth-form" novalidate>
                    <?php echo csrf_token_field(); ?>
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                    <div class="field">
                        <label for="password"><?php echo esc($ct('label_new_password', 'New Password')); ?></label>
                        <div class="password-wrap">
                            <input id="password" type="password" name="password" required autocomplete="new-password" placeholder="<?php echo esc_attr($ct('placeholder_new_password', 'Enter your new password')); ?>" data-validation="required strongPassword">
                            <button type="button" class="toggle-password-btn" aria-label="<?php echo esc_attr($ct('toggle_aria', 'Show password')); ?>" aria-pressed="false"><i class="fa-solid fa-eye" aria-hidden="true"></i></button>
                        </div>
                        <small class="text-muted"><?php echo esc($ct('password_help', 'Use at least 8 characters with 1 uppercase, 1 lowercase, and 1 number.')); ?></small>
                        <span id="password_error" class="text-danger" role="alert"></span>
                    </div>

                    <div class="field">
                        <label for="confirmPassword"><?php echo esc($ct('label_confirm_password', 'Confirm Password')); ?></label>
                        <div class="password-wrap">
                            <input id="confirmPassword" type="password" name="confirmPassword" required autocomplete="new-password" placeholder="<?php echo esc_attr($ct('placeholder_confirm_password', 'Confirm your password')); ?>" data-validation="required confirmPassword">
                            <button type="button" class="toggle-password-btn" aria-label="<?php echo esc_attr($ct('toggle_aria', 'Show password')); ?>" aria-pressed="false"><i class="fa-solid fa-eye" aria-hidden="true"></i></button>
                        </div>
                        <span id="confirmPassword_error" class="text-danger" role="alert"></span>
                    </div>

                    <button class="button button-primary" type="submit"><?php echo esc($ct('button_reset', 'Reset Password')); ?></button>
                    <p class="switch-auth"><a href="<?php echo esc_attr(rd_public_url('login.php')); ?>"><?php echo esc($ct('link_back_login', 'Back to login')); ?></a></p>
                </form>
            <?php endif; ?>
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
            button.setAttribute('aria-label', showing ? '<?php echo esc_attr($ct('toggle_aria_show', 'Show password')); ?>' : '<?php echo esc_attr($ct('toggle_aria_hide', 'Hide password')); ?>');
            button.innerHTML = '<i class="fa-solid ' + (showing ? 'fa-eye' : 'fa-eye-slash') + '" aria-hidden="true"></i>';
        });
    });
</script>
<?php rd_page_end(); ?>