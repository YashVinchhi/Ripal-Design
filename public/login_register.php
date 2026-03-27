<?php

require_once __DIR__ . '/../includes/init.php';

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

function post_login_redirect_url(array $user): string
{
    if (!empty($_SESSION['redirect_after_login'])) {
        $url = (string) $_SESSION['redirect_after_login'];
        unset($_SESSION['redirect_after_login']);
        return $url;
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
        signup_error_and_redirect('Database connection unavailable. Please try later.');
    }
    if (isset($_POST['login'])) {
        login_error_and_redirect('Database connection unavailable. Please try later.');
    }
    header('Location: login.php');
    exit();
}

if (isset($_POST['signup'])) {
    $first_name = trim((string) ($_POST['firstName'] ?? ''));
    $last_name = trim((string) ($_POST['lastName'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $user_password = (string) ($_POST['password'] ?? '');
    $phone_number = trim((string) ($_POST['phoneNumber'] ?? ''));
    $confirm_password = (string) ($_POST['confirmPassword'] ?? '');

    if ($first_name === '' || $last_name === '' || $email === '' || $user_password === '') {
        signup_error_and_redirect('Please fill all required fields.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        signup_error_and_redirect('Please enter a valid email address.');
    }

    if ($confirm_password !== '' && $user_password !== $confirm_password) {
        signup_error_and_redirect('Password and confirm password do not match.');
    }

    try {
        $chk = $db->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $chk->execute([$email]);
        if ($chk->fetch(PDO::FETCH_ASSOC)) {
            signup_error_and_redirect('Email already exists. Please use a different email.');
        }

        $passwordHash = password_hash($user_password, PASSWORD_DEFAULT);
        $role = 'client';
        $status = 'active';
        $fullName = trim($first_name . ' ' . $last_name);
        $username = generate_unique_username($db, $first_name, $last_name);

        $ins = $db->prepare(
            'INSERT INTO users (username, full_name, first_name, last_name, email, phone, password_hash, role, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $ins->execute([$username, $fullName, $first_name, $last_name, $email, $phone_number, $passwordHash, $role, $status]);
        $user_id = (int) $db->lastInsertId();

        $_SESSION['user'] = [
            'id' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'username' => $username,
            'role' => $role,
            'name' => $fullName,
        ];
        $_SESSION['user_id'] = $user_id;
        $_SESSION['login_success'] = 'Account created successfully.';

        // If user asked to be remembered, create persistent token
        if (!empty($_POST['remember']) && function_exists('auth_set_remember_token')) {
            auth_set_remember_token($user_id);
        }

        header('Location: ' . post_login_redirect_url($_SESSION['user']));
        exit();
    } catch (Exception $e) {
        error_log('Signup failed: ' . $e->getMessage());
        signup_error_and_redirect('Failed to create account. Please try again.');
    }
}

if (isset($_POST['login'])) {
    $email = trim((string) ($_POST['email'] ?? ''));
    $user_password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $user_password === '') {
        login_error_and_redirect('Please enter email and password.');
    }

    try {
        // Login only if an account exists in database.
        $stmt = $db->prepare(
            'SELECT id, username, email, first_name, last_name, full_name, password_hash, role, status FROM users WHERE username = ? OR email = ? LIMIT 1'
        );
        $stmt->execute([$email, $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            login_error_and_redirect('Account not found. Please sign up first.');
        }

        if (($user['status'] ?? 'active') !== 'active') {
            login_error_and_redirect('Your account is not active. Please contact admin.');
        }

        $hash = (string) ($user['password_hash'] ?? '');
        if ($hash === '' || !password_verify($user_password, $hash)) {
            login_error_and_redirect('Invalid email or password.');
        }

        $first = (string) ($user['first_name'] ?? '');
        $last = (string) ($user['last_name'] ?? '');
        $displayName = trim((string) ($user['full_name'] ?? ''));
        if ($displayName === '') {
            $displayName = trim($first . ' ' . $last);
        }
        if ($displayName === '') {
            $displayName = (string) ($user['username'] ?? $email);
        }

        $_SESSION['user'] = [
            'id' => (int) ($user['id'] ?? 0),
            'first_name' => $first,
            'last_name' => $last,
            'email' => (string) ($user['email'] ?? $email),
            'username' => (string) ($user['username'] ?? $email),
            'role' => (string) ($user['role'] ?? 'client'),
            'name' => $displayName,
        ];
        $_SESSION['user_id'] = (int) ($user['id'] ?? 0);
        $_SESSION['login_success'] = 'Logged in successfully.';

        // Remember-me support: create persistent token if requested
        if (!empty($_POST['remember']) && function_exists('auth_set_remember_token')) {
            auth_set_remember_token((int)($_SESSION['user_id'] ?? 0));
        }

        header('Location: ' . post_login_redirect_url($_SESSION['user']));
        exit();
    } catch (Exception $e) {
        error_log('Login failed: ' . $e->getMessage());
        login_error_and_redirect('Login failed. Please try again.');
    }
}

header('Location: login.php');
exit();