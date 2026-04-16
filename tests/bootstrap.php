<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SERVER_PORT'] = $_SERVER['SERVER_PORT'] ?? '80';

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/util.php';
require_once dirname(__DIR__) . '/includes/auth.php';
