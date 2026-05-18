<?php
// DEPRECATED: This folder is kept for redirect compatibility only.
// All logic has moved to /pages/. Do not add new files here.
require_once __DIR__ . '/../../app/Core/Bootstrap/init.php';
header('Location: /pages/files/index.php', true, 301);
exit;
