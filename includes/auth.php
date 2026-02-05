<?php
// auth.php - simple helpers for authentication
function require_login() {
    session_start();
    if (empty($_SESSION['user'])) {
        header('Location: ../public/login.php');
        exit;
    }
}

function current_user() {
    return $_SESSION['user'] ?? null;
}
?>