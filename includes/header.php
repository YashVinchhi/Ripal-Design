<?php
/**
 * Header Include Wrapper
 * 
 * Ensures the application is bootstrapped, then includes the canonical header.
 * This provides a consistent include path for all pages.
 * 
 * Usage:
 * <?php require_once __DIR__ . '/../includes/header.php'; ?>
 * 
 * @package RipalDesign
 * @subpackage Core
 */

// Ensure application is bootstrapped
require_once __DIR__ . '/init.php';

// Include the canonical header component
require_once __DIR__ . '/../Common/header.php';
?>
