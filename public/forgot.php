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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/forgot.css">
<<<<<<< HEAD
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
=======
    <style>
        .status-message {
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 14px;
            font-size: 0.92rem;
            line-height: 1.4;
            text-align: left;
        }

        .status-success {
            background: #ecfdf3;
            border: 1px solid #86efac;
            color: #166534;
        }

        .status-error {
            background: #fef2f2;
            border: 1px solid #fca5a5;
            color: #991b1b;
        }
    </style>
    <script src="https://code.jquery.com/jquery-4.0.0.js" integrity="sha256-9fsHeVnKBvqh3FB2HYu7g2xseAZ5MlN6Kz/qnkASV8U=" crossorigin="anonymous"></script>
>>>>>>> e40d25d4e6575badb418f0adf2a0f75f0f0a2982

    <script src="./js/validation.js"></script>

</head>

<body>
    <main>

        <a href="index.php" class="btn btn-secondary top-back-btn">Back</a>
        <div class="login-card text-center">
            <div class="card-login">
                <h3 class="mb-5">Forgot Password</h3>
                <p class="infem">Please enter your email address you'd like your password reset information sent to.</p>
                <?php if ($message !== ''): ?>
                    <div class="status-message <?php echo ($type === 'success') ? 'status-success' : 'status-error'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                <!-- for forgot password -->
                <form id="forgotPasswordForm" method="post" action="./send_reset_password.php">
                    <!-- for email  -->
                    <div class="mb-3 text-start">
                        <label class="email">Email</label>
                        <input type="email" class="form-control " id="email" name="email" placeholder="example@xyz.com" data-validation="required email">
                        <span id="email_error" class="text-danger"></span>
                    </div>
                    <button type="submit" class="btn-1">Send Reset Link</button>
                    <a href="./login.php" class="text-decoration-none text-white p-3 d-block">Back to login</a>
                </form>
            </div>
        </div>
    </main>
</body>

</html>