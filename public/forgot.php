
<?php
require_once __DIR__ . '/../includes/init.php';

$message = '';
$type = '';

if (isset($_COOKIE['flash_message'])) {
    $message = $_COOKIE['flash_message'];
    $type = $_COOKIE['flash_type'] ?? 'error';

    setcookie('flash_message', '', time() - 3600, '/');
    setcookie('flash_type', '', time() - 3600, '/');
} else {
    $message = $_GET['message'] ?? '';
    $type = $_GET['type'] ?? '';
}

$forgotContent = function_exists('public_content_page_values') ? public_content_page_values('forgot') : [];
$ct = static function ($key, $default = '') use ($forgotContent) {
    return (string)($forgotContent[$key] ?? $default);
};
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc($ct('page_title', 'Forgot Password - Ripal Design')); ?></title>
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
        <section class="auth-card-wrap" aria-labelledby="forgotTitle">
            <div class="auth-card auth-card-compact">
                <h1 class="auth-title" id="forgotTitle"><?php echo esc($ct('form_title', 'Forgot Password')); ?></h1>
                <p class="auth-note"><?php echo esc($ct('form_note', 'Enter your account email and we will send a secure reset link.')); ?></p>

                <form id="forgotPasswordForm" method="post" action="./send_reset_password.php" class="auth-form" novalidate>
                    <?php if ($message !== ''): ?>
                        <div class="auth-status <?php echo ($type === 'success') ? 'auth-status-success' : 'auth-status-error'; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <div class="field">
                        <label for="email"><?php echo esc($ct('label_email', 'Email Address')); ?></label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="<?php echo esc_attr($ct('placeholder_email', 'youremail@example.com')); ?>" data-validation="required email" autocomplete="email">
                        <span id="email_error" class="text-danger"></span>
                    </div>

                    <button type="submit" class="btn-1"><?php echo esc($ct('button_send_link', 'Send Reset Link')); ?></button>
                    <p class="switch-auth"><a class="auth-inline-link" href="./login.php"><?php echo esc($ct('link_back_to_login', 'Back to login')); ?></a></p>
                </form>
            </div>
        </section>
    </main>
    <?php require_once __DIR__ . '/../Common/footer.php'; ?>
</body>

</html>
