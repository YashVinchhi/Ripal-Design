<?php
// Lightweight front-controller: delegate to Router
require_once __DIR__ . '/../Common/public_shell.php';
require_once __DIR__ . '/../app/Shared/Router.php';

$router = new \App\Shared\Router(__DIR__);
$router->dispatch();
