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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc($ct('page_title', 'Reset Password | Ripal Design')); ?></title>
    <style>
        :root {
            --bg-start: #f5f7ff;
            --bg-end: #eef2ff;
            --card-bg: #ffffff;
            --text-main: #111827;
            --text-muted: #6b7280;
            --accent: #2563eb;
            --accent-hover: #1d4ed8;
            --border: #d1d5db;
            --danger: #dc2626;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(160deg, var(--bg-start), var(--bg-end));
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 24px;
        }

        h1 {
            margin: 0 0 8px;
            color: var(--text-main);
            font-size: 1.8rem;
            letter-spacing: 0.3px;
        }

        .subtitle {
            margin: 0 0 20px;
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.4;
        }

        .reset-shell {
            width: 100%;
            max-width: 460px;
        }

        .reset-form {
            background-color: var(--card-bg);
            width: 100%;
            padding: 28px;
            border-radius: 16px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 16px 40px rgba(17, 24, 39, 0.08);
        }

        .reset-form label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-main);
            font-weight: 600;
        }

        .password-row {
            position: relative;
            margin-bottom: 10px;
        }

        .password-row input {
            width: 100%;
            padding: 12px 44px 12px 14px;
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 0.98rem;
        }

        .password-row input:focus {
            outline: none;
            /* border-color: var(--accent); */
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.16);
        }
        .password-row img {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        

        .reset-form button {
            margin-top: 4px;
            background-color: var(--accent);
            color: white;
            padding: 11px 16px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 600;
            transition: background-color 0.2s;
        }

        .reset-form button:hover {
            background-color: var(--accent-hover);
        }

        .text {
            display: block;
            color: var(--danger);
            font-size: 0.9rem;
            margin-bottom: 10px;
            line-height: 1.4;
            overflow-wrap: break-word;
        }

        .status-message {
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 14px;
            font-size: 0.92rem;
            line-height: 1.4;
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

        .login-link-wrap {
            margin-top: 12px;
        }

        .login-link {
            display: inline-block;
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
        }

        .login-link:hover {
            text-decoration: underline;
        }
    </style>
    <script src="https://code.jquery.com/jquery-4.0.0.js" integrity="sha256-9fsHeVnKBvqh3FB2HYu7g2xseAZ5MlN6Kz/qnkASV8U=" crossorigin="anonymous"></script>

    <script src="./js/validation.js"></script>

</head>

<body>
    <div class="reset-shell">
        <h1><?php echo esc($ct('heading', 'Reset Password')); ?></h1>
        <p class="subtitle"><?php echo esc($ct('subtitle', 'Create a strong new password for your account.')); ?></p>
        <?php if ($message !== ''): ?>
            <div class="status-message <?php echo ($type === 'success') ? 'status-success' : 'status-error'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php if ($type === 'success'): ?>
                <div class="login-link-wrap">
                    <a class="login-link" href="./login.php"><?php echo esc($ct('link_after_success', 'Go to Login Page')); ?></a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($showForm): ?>
        <form method="post" action="./update_password.php" class="reset-form">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <label for="password"><?php echo esc($ct('label_new_password', 'New Password:')); ?></label>
            <div class="password-row">
                <input type="password" id="password" name="password" placeholder="<?php echo esc_attr($ct('placeholder_new_password', 'Enter your new password')); ?>" data-validation="required strongPassword">
                
                  <img src="./css/eye/eye_close.svg" alt="<?php echo esc_attr($ct('toggle_show_alt', 'Show password')); ?>" id="eyeicon1" class="toggle-password" aria-hidden="false" role="button" tabindex="0" aria-label="<?php echo esc_attr($ct('toggle_aria', 'Toggle password visibility')); ?>">
                
            </div>
            <span id="password_error" class="text"></span>
            <button type="submit"><?php echo esc($ct('button_reset', 'Reset Password')); ?></button>
        </form>
        <?php endif; ?>
    </div>
</body>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const input = document.getElementById('password');
        const icon = document.getElementById('eyeicon1');
        if (!input || !icon) return;

        const openSrc = './css/eye/eye_open.svg';
        const closeSrc = './css/eye/eye_close.svg';
        const showLabel = <?php echo json_encode($ct('toggle_show_alt', 'Show password')); ?>;
        const hideLabel = <?php echo json_encode($ct('toggle_hide_alt', 'Hide password')); ?>;

        function updateToggleState() {
            const isVisible = input.type === 'text';
            icon.src = isVisible ? openSrc : closeSrc;
            icon.alt = isVisible ? hideLabel : showLabel;
            icon.setAttribute('aria-label', isVisible ? hideLabel : showLabel);
        }

        function togglePassword() {
            input.type = input.type === 'password' ? 'text' : 'password';
            updateToggleState();
        }

        icon.addEventListener('click', togglePassword);
        icon.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                togglePassword();
            }
        });

        updateToggleState();
    });
</script>

</html>