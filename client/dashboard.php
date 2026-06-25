<?php
// DEPRECATED: This folder is kept for redirect compatibility only.
// All logic has moved to /pages/. Do not add new files here.
require_once __DIR__ . '/../app/Core/Bootstrap/init.php';
// Redirect to the canonical dashboard location (preserve BASE_URL/subpath)
$target = (defined('BASE_URL') ? rtrim((string)BASE_URL, '/') : '') . '/dashboard/dashboard.php';
header('Location: ' . $target, true, 302);
exit;
