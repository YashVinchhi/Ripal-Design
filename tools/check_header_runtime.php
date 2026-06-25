<?php
// Minimal environment to include Common/header.php for runtime warnings
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Stubs and minimal constants/vars used by header.php
if (!defined('BASE_PATH')) define('BASE_PATH', '/base');
if (!defined('PROJECT_ROOT')) define('PROJECT_ROOT', __DIR__ . '/../');
if (!defined('PUBLIC_PATH_PREFIX')) define('PUBLIC_PATH_PREFIX', '/public');
if (!defined('PHONE_NUMBER')) define('PHONE_NUMBER', '0000000000');
if (!defined('APP_ENV')) define('APP_ENV', 'development');

$HEADER_MODE = 'dashboard';
$headerMode = 'dashboard';
$DISABLE_EXTERNAL_CSS = false;
$dashboardProfileUrl = '/dashboard/profile.php';
$brandLogoImage = '/assets/images/logo.png';
$headerContent = [];
$faviconImage = '/assets/images/favicon.ico';
$radiusMode = 'normal';
$whatsAppHref = 'https://wa.me/0000000000';
$pageTitle = 'Test';
$metaDesc = 'desc';
// Flags used in header
$isPublicHeader = false;

// Minimal helper functions used in templates
if (!function_exists('esc_attr')) {
	function esc_attr($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}

function csrf_token() { return true; }
function csrf_meta_tag() { return "<meta name=\"csrf\">"; }
function is_logged_in(){ return true; }
function current_user(){ return ['role'=>'admin']; }
function auth_dashboard_url(){ return '/admin/dashboard.php'; }
function rd_content_image_style_attr($a,$b){ return ''; }
// Provide both function and callable-variable for headerText (templates use both forms)
function headerText($k,$d=''){ return $d; }
$headerText = function($k,$d=''){ return $d; };
function headerPublicUrl($p){ return '/public/'.$p; }
function webmcp_render_bootstrap_once(){ return true; }
function auth_resolve_navigation_role($user){ return 'admin'; }

// include
include __DIR__ . '/../Common/header.php';

echo "\n--- done include ---\n";
