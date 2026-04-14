<?php

require_once __DIR__ . '/../includes/init.php';

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$ct = static function ($key, $default = '') {
    if (function_exists('public_content_get')) {
        return public_content_get('login_register', $key, $default);
    }
    return (string)$default;
};

$renderTemplate = static function ($template, array $vars = []) {
    return strtr((string)$template, $vars);
};

function post_login_redirect_url(array $user): string
{
    if (!empty($_SESSION['redirect_after_login'])) {
        $url = (string) $_SESSION['redirect_after_login'];
        unset($_SESSION['redirect_after_login']);
        return $url;
    }

    if (function_exists('auth_dashboard_url')) {
        return auth_dashboard_url();
    }

    return rtrim(BASE_PATH, '/') . '/dashboard/dashboard.php';
}

function login_error_and_redirect(string $message): void
{
    $_SESSION['login_error'] = $message;
    $_SESSION['active_form'] = 'login';
    header('Location: login.php');
    exit();
}

function signup_error_and_redirect(string $message): void
{
    $_SESSION['register_error'] = $message;
    $_SESSION['active_form'] = 'signup';
    header('Location: signup.php');
    exit();
}

function generate_unique_username(PDO $db, string $firstName, string $lastName): string
{
    $base = strtolower(trim($firstName . '.' . $lastName));
    $base = preg_replace('/[^a-z0-9.]+/', '', $base ?? '') ?: 'user';
    $candidate = $base;
    $suffix = 1;

    $chk = $db->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
    while (true) {
        $chk->execute([$candidate]);
        if (!$chk->fetch(PDO::FETCH_ASSOC)) {
            return $candidate;
        }
        $candidate = $base . $suffix;
        $suffix++;
    }
}

$db = get_db();
if (!($db instanceof PDO)) {
    if (isset($_POST['signup'])) {
        signup_error_and_redirect($ct('db_unavailable', 'Database connection unavailable. Please try later.'));
    }
    if (isset($_POST['login'])) {
        login_error_and_redirect($ct('db_unavailable', 'Database connection unavailable. Please try later.'));
    }
    header('Location: login.php');
    exit();
}

if (isset($_POST['signup'])) {
    $first_name = trim((string) ($_POST['firstName'] ?? ''));
    $last_name = trim((string) ($_POST['lastName'] ?? ''));
    $username = strtolower(trim((string) ($_POST['username'] ?? '')));
    $email = trim((string) ($_POST['email'] ?? ''));
    $user_password = (string) ($_POST['password'] ?? '');
    $phone_number = trim((string) ($_POST['phoneNumber'] ?? ''));
    $confirm_password = (string) ($_POST['confirmPassword'] ?? '');

    if ($first_name === '' || $last_name === '' || $email === '' || $user_password === '') {
        signup_error_and_redirect($ct('signup_required_fields', 'Please fill all required fields.'));
    }

    // If username is not provided, derive one from email local-part, then ensure uniqueness.
    if ($username === '') {
        $emailLocalPart = strtolower((string) strstr($email, '@', true));
        $derived = preg_replace('/[^a-z0-9._-]+/', '', $emailLocalPart);
        if ($derived === null || $derived === '' || strlen($derived) < 3) {
            $derived = generate_unique_username($db, $first_name, $last_name);
        }
        $username = $derived;
    }

    if (!preg_match('/^[A-Za-z0-9._-]{3,30}$/', $username)) {
        signup_error_and_redirect($ct('signup_invalid_username', 'Username must be 3-30 chars and can contain letters, numbers, dot, underscore or hyphen.'));
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        signup_error_and_redirect($ct('signup_invalid_email', 'Please enter a valid email address.'));
    }

    if ($confirm_password !== '' && $user_password !== $confirm_password) {
        signup_error_and_redirect($ct('signup_password_mismatch', 'Password and confirm password do not match.'));
    }

    try {
        $chkEmail = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $chkEmail->execute([$email]);
        if ($chkEmail->fetch(PDO::FETCH_ASSOC)) {
            signup_error_and_redirect($ct('signup_email_exists', 'Email already exists. Please use a different email.'));
        }

        if (strlen($username) > 30) {
            $username = substr($username, 0, 30);
        }

        $chkUsername = $db->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $baseUsername = $username;
        $suffix = 1;
        while (true) {
            $chkUsername->execute([$username]);
            if (!$chkUsername->fetch(PDO::FETCH_ASSOC)) {
                break;
            }

            $maxBaseLen = max(1, 30 - strlen((string)$suffix));
            $username = substr($baseUsername, 0, $maxBaseLen) . $suffix;
            $suffix++;

            if ($suffix > 9999) {
                signup_error_and_redirect($ct('signup_username_exists', 'Username already exists. Please choose another username.'));
            }
        }

        $passwordHash = password_hash($user_password, PASSWORD_DEFAULT);
        $role = 'client';
        $status = 'active';
        $fullName = trim($first_name . ' ' . $last_name);

        $ins = $db->prepare(
            'INSERT INTO users (username, full_name, first_name, last_name, email, phone, password_hash, role, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $ins->execute([$username, $fullName, $first_name, $last_name, $email, $phone_number, $passwordHash, $role, $status]);
        $user_id = (int) $db->lastInsertId();

        // Auto-login newly created account and route by effective dashboard role.
        $_SESSION['register_success'] = $ct('signup_success', 'Account created successfully.');
        $_SESSION['user'] = [
            'id' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'username' => $username,
            'role' => $role,
            'name' => $fullName !== '' ? $fullName : $username,
        ];
        $_SESSION['user_id'] = $user_id;
        @session_regenerate_id(true);

        // Attempt to send a notification email to the user about pending status
        try {
            $mail = null;
            if (is_readable(__DIR__ . '/mailer.php')) {
                $mail = require __DIR__ . '/mailer.php';
            }
                if ($mail && $mail instanceof \PHPMailer\PHPMailer\PHPMailer) {
                $mail->clearAddresses();
                $from = getenv('MAIL_FROM') ?: 'no-reply@ripaldesign.in';
                $fromName = $ct('signup_welcome_from_name', 'Ripal Design');
                $mail->setFrom($from, $fromName);
                $mail->addAddress($email, $fullName);
                $mail->isHTML(true);
                $mail->Subject = $ct('signup_welcome_subject', 'Welcome to Ripal Design');

                $defaultWelcomeHtml = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Ripal Design</title>
    <style>body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; } img { -ms-interpolation-mode: bicubic; border: 0; height: auto; outline: none; text-decoration: none; }</style>
</head>
<body style="font-family: Inter, Arial, sans-serif; background:#f4f4f5; padding:20px;">
    <div style="max-width:600px;margin:auto;background:#fff;padding:24px;border-radius:8px;">
        <h2 style="color:#731209;">Thanks for registering</h2>
        <p>Hi [User Name],</p>
        <p>Your account has been created successfully and is now active.</p>
        <p>You can <a href="{{login_link}}">log in</a> any time to access your dashboard.</p>
        <p style="margin-top:24px;">— The Ripal Design Team</p>
    </div>
</body>
</html>
HTML;

                $mail->Body = $renderTemplate($defaultWelcomeHtml, [
                    '{{login_link}}' => htmlspecialchars((BASE_URL . PUBLIC_PATH_PREFIX . '/login.php'), ENT_QUOTES, 'UTF-8'),
                    '[User Name]' => htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'),
                ]);

                $mail->AltBody = $renderTemplate($ct('signup_welcome_alt', 'Hi {{first_name}}, your account was created successfully and is now active.'), [
                    '{{first_name}}' => $first_name,
                ]);

                try {
                    $mail->send();
                } catch (Exception $em) {
                    error_log('Welcome email failed: ' . $mail->ErrorInfo . ' / ' . $em->getMessage());
                }
            }
        } catch (\Throwable $e) {
            error_log('Welcome email skipped/failed: ' . $e->getMessage());
        }

        header('Location: ' . post_login_redirect_url($_SESSION['user']));
        exit();
    } catch (Exception $e) {
        error_log('Signup failed: ' . $e->getMessage());
        signup_error_and_redirect($ct('signup_failed', 'Failed to create account. Please try again.'));
    }
}

if (isset($_POST['login'])) {
    $email = trim((string)($_POST['email'] ?? ''));
    $user_password = (string)($_POST['password'] ?? '');

    if ($email === '' || $user_password === '') {
        login_error_and_redirect($ct('login_missing_credentials', 'Please enter email and password.'));
    }

    // Try primary users table via PDO
    try {
        if ($db instanceof PDO) {
            $stmt = $db->prepare('SELECT id, username, email, first_name, last_name, full_name, password_hash, role, status FROM users WHERE username = ? OR email = ? LIMIT 1');
            $stmt->execute([$email, $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user && !empty($user['password_hash']) && password_verify($user_password, $user['password_hash'])) {
                if (($user['status'] ?? 'active') !== 'active') {
                    login_error_and_redirect($ct('login_inactive_account', 'Your account is not active. Please contact admin.'));
                }

                $first = (string)($user['first_name'] ?? '');
                $last = (string)($user['last_name'] ?? '');
                $displayName = trim((string)($user['full_name'] ?? '')) ?: trim($first . ' ' . $last) ?: (string)($user['username'] ?? $email);

                $_SESSION['user'] = [
                    'id' => (int)($user['id'] ?? 0),
                    'first_name' => $first,
                    'last_name' => $last,
                    'email' => (string)($user['email'] ?? $email),
                    'username' => (string)($user['username'] ?? ''),
                    'role' => (string)($user['role'] ?? 'client'),
                    'name' => $displayName,
                ];
                $_SESSION['user_id'] = (int)($user['id'] ?? 0);
                @session_regenerate_id(true);
                // If user asked to be remembered, create persistent remember token/cookie
                if (!empty($_POST['remember']) && function_exists('auth_set_remember_token')) {
                    auth_set_remember_token((int)$_SESSION['user_id']);
                }
                header('Location: ' . post_login_redirect_url($_SESSION['user']));
                exit();
            }
        }
    } catch (Exception $e) {
        error_log('Login failed (PDO): ' . $e->getMessage());
    }

    // Legacy fallback: check signup table via mysqli
    try {
        require_once __DIR__ . '/../sql/config.php';
        if (isset($conn) && !$conn->connect_error) {
            $stmt = $conn->prepare('SELECT * FROM signup WHERE email = ? LIMIT 1');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $legacyUser = $result ? $result->fetch_assoc() : null;
            $stmt->close();

            if ($legacyUser && !empty($legacyUser['password']) && password_verify($user_password, $legacyUser['password'])) {
                $legacyUsername = trim((string)($legacyUser['username'] ?? ''));
                if ($legacyUsername === '') {
                    $legacyUsername = preg_replace('/[^a-z0-9._-]+/', '', strtolower(trim((string)($legacyUser['first_name'] ?? '') . '.' . (string)($legacyUser['last_name'] ?? '')))) ?: 'user';
                }

                $_SESSION['user'] = [
                    'id' => (int)($legacyUser['s_id'] ?? $legacyUser['id'] ?? 0),
                    'first_name' => $legacyUser['first_name'] ?? '',
                    'last_name' => $legacyUser['last_name'] ?? '',
                    'email' => $legacyUser['email'] ?? $email,
                    'username' => $legacyUsername,
                    'role' => $legacyUser['role'] ?? 'client',
                    'name' => trim(($legacyUser['first_name'] ?? '') . ' ' . ($legacyUser['last_name'] ?? '')) ?: $email,
                ];
                $_SESSION['user_id'] = (int)($legacyUser['s_id'] ?? $legacyUser['id'] ?? 0);
                @session_regenerate_id(true);
                // If user asked to be remembered, create persistent remember token/cookie
                if (!empty($_POST['remember']) && function_exists('auth_set_remember_token')) {
                    auth_set_remember_token((int)$_SESSION['user_id']);
                }
                header('Location: ' . post_login_redirect_url($_SESSION['user']));
                exit();
            }
        }
    } catch (Exception $e) {
        error_log('Legacy login failed: ' . $e->getMessage());
    }

    login_error_and_redirect($ct('login_invalid_credentials', 'Invalid email or password.'));
}// End of login/register processor