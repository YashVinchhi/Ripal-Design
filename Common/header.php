<?php
// header.php - include this at the top of pages to get shared nav
// Load common stylesheet fallbacks so header always appears styled during testing.
require_once __DIR__ . '/../includes/config.php';
?>
<?php
// Server-side: only emit stylesheet links that exist on disk to avoid client 404s
$candidates = [
    '/styles.css',
    '/public/styles.css',
    '/assets/css/styles.css',
    '/assets/styles.css'
];

foreach ($candidates as $c) {
    $filePath = PROJECT_ROOT . str_replace('/', DIRECTORY_SEPARATOR, $c);
    if (file_exists($filePath)) {
        echo '<link rel="stylesheet" href="' . htmlspecialchars(BASE_PATH . $c, ENT_QUOTES) . '">' . "\n";
        break; // Only include the first match
    }
}
?>

<header>
  <nav>
    <a href="public/index.php">Home</a> |
    <a href="../public/services.php">Services</a> |
    <a href="../public/about_us.php">About</a> |
    <a href="../public/contact_us.php">Contact</a>
  </nav>
</header>