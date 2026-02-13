<?php
$HEADER_MODE = 'public';
require_once __DIR__ . '/../includes/header.php';
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>404 - Not Found</title>
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
  <main style="text-align:center;padding:4rem;">
    <h1>404</h1>
    <p>Sorry — the page you were looking for was not found.</p>
    <p><a href="index.php">Go back home</a></p>
  </main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>