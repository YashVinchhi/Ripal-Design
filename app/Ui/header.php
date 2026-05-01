<?php
/**
 * Header Include Wrapper
 * 
 * Ensures the application is bootstrapped, then includes the canonical header.
 * This provides a consistent include path for all pages.
 * 
 * Usage:
 * <?php require_once __DIR__ . '/../app/Ui/header.php'; ?>
 * 
 * @package RipalDesign
 * @subpackage Core
 */

// Ensure application is bootstrapped
require_once __DIR__ . '/../Core/Bootstrap/init.php';

// Only load bootstrap for non-public header modes to keep public pages Tailwind-only
if ((empty($HEADER_MODE) || $HEADER_MODE !== 'public') && function_exists('webmcp_render_bootstrap_once')) {
	webmcp_render_bootstrap_once();
}

// Include the canonical header component
require_once __DIR__ . '/../../Common/header.php';
?>
