<?php
// Placeholder: upload drawings endpoint (form + handling)
$HEADER_MODE = 'dashboard';
require_once __DIR__ . '/../includes/header.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // TODO: handle uploaded files securely
    $msg = 'Upload received (not implemented).';
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Upload Drawings</title></head>
<body>
  <main>
    <h1>Upload Drawings</h1>
    <?php if (!empty($msg)) echo '<p>'.htmlspecialchars($msg).'</p>'; ?>
    <form method="post" enctype="multipart/form-data">
      <label>Select file: <input type="file" name="drawing"></label><br>
      <button type="submit">Upload</button>
    </form>
  </main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>