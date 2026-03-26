<?php

// Unified auth handler (login + signup)
// Uses the modern `users` table when available (PDO via includes/init.php)
// Falls back to legacy `signup` table (mysqli via sql/config.php) if needed.

require_once __DIR__ . '/../includes/init.php';

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

/**
 * Compute the default landing page after login.
 * Prefers `redirect_after_login` when set by `require_login()`.
 */
function post_login_redirect_url(array $user): string
{
    if (!empty($_SESSION['redirect_after_login'])) {
        $url = (string) $_SESSION['redirect_after_login'];
        unset($_SESSION['redirect_after_login']);
        return $url;
    }

    $role = $user['role'] ?? '';
    // Single dashboard route for all roles; UI is role-based inside dashboard renderer.
    return rtrim(BASE_PATH, '/') . '/dashboard/dashboard.php';
}

if (isset($_POST['signup'])) {
    $first_name = trim((string) ($_POST['firstName'] ?? ''));
    $last_name = trim((string) ($_POST['lastName'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $user_password = (string) ($_POST['password'] ?? '');
    $phone_number = trim((string) ($_POST['phoneNumber'] ?? ''));

    if (empty($first_name) || empty($last_name) || empty($email) || empty($user_password)) {
        $_SESSION['register_error'] = 'Please fill all required fields.';
        $_SESSION['active_form'] = 'signup';
        header('Location: signup.php');
        exit();
    }

    $db = get_db();
    if ($db instanceof PDO) {
        try {
            // Create a client user in the unified `users` table
            $stmt = $db->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $_SESSION['register_error'] = 'Email already exists. Please use a different email.';
                $_SESSION['active_form'] = 'signup';
                header('Location: signup.php');
                exit();
            }

            $passwordHash = password_hash($user_password, PASSWORD_DEFAULT);
            $role = 'client';
            $ins = $db->prepare('INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)');
            $ins->execute([$email, $passwordHash, $role]);
            $user_id = (int) $db->lastInsertId();

            $_SESSION['user'] = [
                'id' => $user_id,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'username' => $email,
                'role' => $role,
                'name' => trim($first_name . ' ' . $last_name),
            ];
            $_SESSION['user_id'] = $user_id;

            header('Location: ' . post_login_redirect_url($_SESSION['user']));
            exit();
        } catch (Exception $e) {
            error_log('Signup failed (users table): ' . $e->getMessage());
            // Fall through to legacy signup
        }
    }

    // Legacy fallback (old schema)
    require_once __DIR__ . '/../sql/config.php';
    if (!$conn || $conn->connect_error) {
        $_SESSION['register_error'] = 'Database connection unavailable. Please try later.';
        $_SESSION['active_form'] = 'signup';
        header('Location: signup.php');
        exit();
    }

    try {
        $passwordHash = password_hash($user_password, PASSWORD_DEFAULT);
        $chk = $conn->prepare('SELECT 1 FROM signup WHERE email = ? LIMIT 1');
        $chk->bind_param('s', $email);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            $chk->close();
            $_SESSION['register_error'] = 'Email already exists. Please use a different email.';
            $_SESSION['active_form'] = 'signup';
            header('Location: signup.php');
            exit();
        }
        $chk->close();

        $ins = $conn->prepare('INSERT INTO signup (first_name, lats_name, email, password, phone_number, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $role = 'user';
        $status = 'active';
        $ins->bind_param('sssssss', $first_name, $last_name, $email, $passwordHash, $phone_number, $role, $status);
        if ($ins->execute()) {
            $user_id = $conn->insert_id;
            $ins->close();

            $_SESSION['user'] = [
                'id' => $user_id,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'username' => $email,
                'role' => 'client',
                'name' => trim($first_name . ' ' . $last_name),
            ];
            $_SESSION['user_id'] = $user_id;
            header('Location: ' . post_login_redirect_url($_SESSION['user']));
            exit();
        }

        $ins->close();
        $_SESSION['register_error'] = 'Failed to create account. Please try again.';
        $_SESSION['active_form'] = 'signup';
        header('Location: signup.php');
        exit();
    } catch (Exception $e) {
        error_log('Legacy signup failed: ' . $e->getMessage());
        $_SESSION['register_error'] = 'Failed to create account. Please try again.';
        $_SESSION['active_form'] = 'signup';
        header('Location: signup.php');
        exit();
    }
}


if (isset($_POST['login'])) {
    $email = trim((string)($_POST['email'] ?? ''));
    $user_password = (string)($_POST['password'] ?? '');

    if ($email === '' || $user_password === '') {
        $_SESSION['login_error'] = 'Please enter email and password.';
        $_SESSION['active_form'] = 'login';
        header('Location: login.php');
        exit();
    }

    // Prefer unified users table
    $db = get_db();
    if ($db instanceof PDO) {
        try {
            $stmt = $db->prepare('SELECT id, username, password_hash, role FROM users WHERE username = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user && !empty($user['password_hash']) && password_verify($user_password, $user['password_hash'])) {
                $_SESSION['user'] = [
                    'id' => (int)($user['id'] ?? 0),
                    'first_name' => '',
                    'last_name' => '',
                    'email' => $email,
                    'username' => (string)($user['username'] ?? $email),
                    'role' => (string)($user['role'] ?? 'client'),
                    'name' => (string)($user['username'] ?? $email),
                ];
                $_SESSION['user_id'] = (int)($user['id'] ?? 0);
                header('Location: ' . post_login_redirect_url($_SESSION['user']));
                exit();
            }
        } catch (Exception $e) {
            error_log('Login failed (users table): ' . $e->getMessage());
            // fall through to legacy login
        }
    }

    // Legacy fallback: signup table
    require_once __DIR__ . '/../sql/config.php';
    if ($conn && !$conn->connect_error) {
        try {
            $stmt = $conn->prepare('SELECT * FROM signup WHERE email = ? LIMIT 1');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result ? $result->fetch_assoc() : null;
            $stmt->close();

            if ($user && !empty($user['password']) && password_verify($user_password, $user['password'])) {
                $_SESSION['user'] = [
                    'id' => (int)($user['s_id'] ?? $user['id'] ?? 0),
                    'first_name' => $user['first_name'] ?? '',
                    'last_name' => $user['lats_name'] ?? $user['last_name'] ?? '',
                    'email' => $user['email'] ?? $email,
                    'username' => $user['email'] ?? $email,
                    'role' => $user['role'] ?? 'client',
                    'name' => trim(($user['first_name'] ?? '') . ' ' . ($user['lats_name'] ?? $user['last_name'] ?? '')) ?: $email,
                ];
                $_SESSION['user_id'] = (int)($user['s_id'] ?? $user['id'] ?? 0);
                header('Location: ' . post_login_redirect_url($_SESSION['user']));
                exit();
            }
        } catch (Exception $e) {
            error_log('Legacy login failed: ' . $e->getMessage());
        }
    }

    $_SESSION['login_error'] = 'Invalid email or password.';
    $_SESSION['active_form'] = 'login';
    header('Location: login.php');
    exit();
}
}
