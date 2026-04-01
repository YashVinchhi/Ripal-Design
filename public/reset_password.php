<?php

require_once __DIR__ . '/../includes/init.php';
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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title><?php echo esc($ct('page_title', 'Reset Password | Ripal Design')); ?></title>
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
        <section class="auth-card-wrap" aria-labelledby="resetTitle">
            <div class="auth-card auth-card-compact">
                <h1 class="auth-title" id="resetTitle"><?php echo esc($ct('heading', 'Reset Password')); ?></h1>
                <p class="auth-subtitle"><?php echo esc($ct('subtitle', 'Create a strong new password for your account.')); ?></p>

                <?php if ($message !== ''): ?>
                    <p class="alert <?php echo ($type === 'success') ? 'alert-success' : 'alert-danger'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </p>
                    <?php if ($type === 'success'): ?>
                        <p class="switch-auth"><a href="./login.php"><?php echo esc($ct('link_after_success', 'Go to Login Page')); ?></a></p>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($showForm): ?>
                    <form method="post" action="./update_password.php" class="auth-form" novalidate>
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                        <div class="field">
                            <label for="password"><?php echo esc($ct('label_new_password', 'New Password')); ?></label>
                            <div class="input-with-icon max">
                                <input type="password" id="password" name="password" class="form-control" placeholder="<?php echo esc_attr($ct('placeholder_new_password', 'Enter your new password')); ?>" data-validation="required strongPassword" autocomplete="new-password">
                                <button type="button" class="toggle-password-btn" aria-label="<?php echo esc_attr($ct('toggle_aria', 'Toggle password visibility')); ?>" aria-pressed="false">
                                    <img src="./css/eye/eye_close.svg" alt="<?php echo esc_attr($ct('toggle_show_alt', 'Show password')); ?>" class="toggle-password" aria-hidden="true">
                                </button>
                            </div>
                            <span id="password_error" class="text-danger"></span>
                        </div>

                        <button type="submit" class="btn-1"><?php echo esc($ct('button_reset', 'Reset Password')); ?></button>
                        <p class="switch-auth"><a href="./login.php"><?php echo esc($ct('link_back_login', 'Back to Login')); ?></a></p>
                    </form>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <?php require_once __DIR__ . '/../Common/footer.php'; ?>
</body>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const input = document.getElementById('password');
        const toggleBtn = document.querySelector('.toggle-password-btn');
        const icon = document.querySelector('.toggle-password');
        if (!input || !icon || !toggleBtn) return;

        const openSrc = './css/eye/eye_open.svg';
        const closeSrc = './css/eye/eye_close.svg';
        const showLabel = <?php echo json_encode($ct('toggle_show_alt', 'Show password')); ?>;
        const hideLabel = <?php echo json_encode($ct('toggle_hide_alt', 'Hide password')); ?>;

        function updateToggleState() {
            const isVisible = input.type === 'text';
            icon.src = isVisible ? openSrc : closeSrc;
            icon.alt = isVisible ? hideLabel : showLabel;
            toggleBtn.setAttribute('aria-label', isVisible ? hideLabel : showLabel);
            toggleBtn.setAttribute('aria-pressed', isVisible ? 'true' : 'false');
        }

        function togglePassword() {
            input.type = input.type === 'password' ? 'text' : 'password';
            updateToggleState();
        }

        toggleBtn.addEventListener('click', togglePassword);

        updateToggleState();
    });
</script>

</html>