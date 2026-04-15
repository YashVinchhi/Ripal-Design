<?php
// Test file to verify path configuration for InfinityFree hosting
require_once __DIR__ . '/includes/config.php';

echo "<h2>Path Configuration Test</h2>";
echo "<p><strong>BASE_URL:</strong> " . BASE_URL . "</p>";
echo "<p><strong>BASE_PATH:</strong> " . BASE_PATH . "</p>";
echo "<p><strong>PUBLIC_PATH_PREFIX:</strong> " . PUBLIC_PATH_PREFIX . "</p>";
echo "<p><strong>PROJECT_ROOT:</strong> " . PROJECT_ROOT . "</p>";

echo "<h3>Generated Paths:</h3>";
echo "<p><strong>Home:</strong> " . base_path('public/index.php') . "</p>";
echo "<p><strong>CSS:</strong> " . base_path('public/css/index.css') . "</p>";
echo "<p><strong>Assets:</strong> " . base_path('assets/Content/Logo.png') . "</p>";

echo "<h3>Server Info:</h3>";
echo "<p><strong>SCRIPT_NAME:</strong> " . ($_SERVER['SCRIPT_NAME'] ?? 'Not set') . "</p>";
echo "<p><strong>DOCUMENT_ROOT:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Not set') . "</p>";
echo "<p><strong>REQUEST_URI:</strong> " . ($_SERVER['REQUEST_URI'] ?? 'Not set') . "</p>";
?>
