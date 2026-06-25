<?php
// DEPRECATED: This folder is kept for redirect compatibility only.
// All logic has moved to /pages/. Do not add new files here.
require_once __DIR__ . '/../app/Core/Bootstrap/init.php';

// Diagnostic mode: visit /admin/dashboard.php?diag=1 to see runtime values
if (isset($_GET['diag']) && $_GET['diag'] === '1') {
	$baseUrl = defined('BASE_URL') ? (string)BASE_URL : '(not-defined)';
	$basePath = defined('BASE_PATH') ? (string)BASE_PATH : '(not-defined)';
	$host = $_SERVER['HTTP_HOST'] ?? '(no-host)';
	$req = $_SERVER['REQUEST_URI'] ?? '(no-uri)';
	$sessionActive = session_status() === PHP_SESSION_ACTIVE ? 'active' : 'inactive';
	$sessionUser = isset($_SESSION['user']) ? json_encode($_SESSION['user']) : '(no user)';
	$out = [];
	$out[] = 'BASE_URL: ' . $baseUrl;
	$out[] = 'BASE_PATH: ' . $basePath;
	$out[] = 'HTTP_HOST: ' . $host;
	$out[] = 'REQUEST_URI: ' . $req;
	$out[] = 'SESSION_STATUS: ' . $sessionActive;
	$out[] = 'SESSION_USER: ' . $sessionUser;
	if (function_exists('app_log')) { app_log('info', 'Admin dashboard diag', ['baseUrl'=>$baseUrl,'basePath'=>$basePath,'host'=>$host,'req'=>$req,'session_status'=>$sessionActive,'session_user'=>$_SESSION['user'] ?? null]); }
	header('Content-Type: text/plain; charset=utf-8');
	echo implode("\n", $out);
	exit;
}

// Redirect to the canonical dashboard location (preserve BASE_URL/subpath)
$target = (defined('BASE_URL') ? rtrim((string)BASE_URL, '/') : '') . '/dashboard/dashboard.php';
header('Location: ' . $target, true, 302);
 exit;
