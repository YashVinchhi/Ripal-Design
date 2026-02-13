<?php
// Centralized header include. Bootstraps common services then forwards
// to the canonical header implementations in Common/.
require_once __DIR__ . '/init.php';

// Forward to the single canonical header in Common/ which adapts to context.
require_once __DIR__ . '/../Common/header.php';
?>