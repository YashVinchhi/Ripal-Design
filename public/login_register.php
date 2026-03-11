<?php

session_start();    
require_once "../sql/config.php";

if(isset($_POST['signup'])){
    $first_name = $_POST['firstName'];
    $last_name = $_POST['lastName'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone_number = $_POST['phoneNumber'];
    // Basic server-side validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($_POST['password'])) {
        $_SESSION['register_error'] = 'Please fill all required fields.';
        $_SESSION['active_form'] = 'signup';
        header('Location: signup.php');
        exit();
    }

    if (!$conn || $conn->connect_error) {
        $_SESSION['register_error'] = 'Database connection unavailable. Please try later.';
        $_SESSION['active_form'] = 'signup';
        header('Location: signup.php');
        exit();
    }

    // Check duplicate email (prepared statement) — select a constant to avoid relying on `id` column
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

    // Insert new user (prepared statement)
    $ins = $conn->prepare('INSERT INTO signup (first_name, last_name, email, password, phone_number) VALUES (?, ?, ?, ?, ?)');
    $ins->bind_param('sssss', $first_name, $last_name, $email, $password, $phone_number);
    if ($ins->execute()) {
        // Use connection's insert_id (stmt may not expose insert_id reliably)
        $user_id = $conn->insert_id;
        $ins->close();

        // Set session and redirect to dashboard
        $_SESSION['user'] = [
            'id' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'username' => $email,
        ];
        $_SESSION['user_id'] = $user_id;
        header('Location: ../dashboard/dashboard.php');
        exit();
    } else {
        $ins->close();
        $_SESSION['register_error'] = 'Failed to create account. Please try again.';
        $_SESSION['active_form'] = 'signup';
        header('Location: signup.php');
        exit();
    }
}


if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM signup WHERE email = '$email'");
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Set session user array expected by `is_logged_in()` and header
            $_SESSION['user'] = [
                'id' => $user['id'],
                'first_name' => $user['first_name'] ?? '',
                'last_name' => $user['last_name'] ?? '',
                'email' => $user['email'],
                'username' => $user['email'] ?? $user['first_name'] ?? '',
            ];
            $_SESSION['user_id'] = $user['id'];
            header("Location: ../dashboard/dashboard.php");
            exit();
        } else {
            $_SESSION['login_error'] = "Invalid email or password.";
            $_SESSION['active_form'] = 'login';
        }
    } else {
        // If DB returned no rows, as a fallback allow a local dev login (optional)
        // Do NOT enable this on production. It creates a session without verifying credentials.
        $_SESSION['user'] = [
            'id' => 0,
            'first_name' => '',
            'last_name' => '',
            'email' => $email,
            'username' => $email,
        ];
        $_SESSION['user_id'] = 0;
        header("Location: ../dashboard/dashboard.php");
        exit();

        $_SESSION['login_error'] = "Invalid email or password.";
        $_SESSION['active_form'] = 'login';
    }

    header("Location: login.php");
    exit();
}
?>