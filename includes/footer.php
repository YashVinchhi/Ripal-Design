<?php
// Centralized footer include. Ensure app is bootstrapped first then
// forward to canonical footer in Common/ which emits closing scripts.
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/../Common/footer.php';
?>