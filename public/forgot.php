
<?php
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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Ripal Design</title>
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
                <h1 class="auth-title" id="forgotTitle">Forgot Password</h1>
                <p class="auth-note">Enter your account email and we will send a secure reset link.</p>

                <form id="forgotPasswordForm" method="post" action="./send_reset_password.php" class="auth-form" novalidate>
                    <?php if ($message !== ''): ?>
                        <div class="auth-status <?php echo ($type === 'success') ? 'auth-status-success' : 'auth-status-error'; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>

                    <div class="field">
                        <label for="email">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="youremail@example.com" data-validation="required email" autocomplete="email">
                        <span id="email_error" class="text-danger"></span>
                    </div>

                    <button type="submit" class="btn-1">Send Reset Link</button>
                    <p class="switch-auth"><a class="auth-inline-link" href="./login.php">Back to login</a></p>
                </form>
            </div>
        </section>
    </main>
    <?php require_once __DIR__ . '/../Common/footer.php'; ?>
</body>

</html>
