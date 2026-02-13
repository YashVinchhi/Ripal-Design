<?php
// Placeholder: payment gateway integration stub
// NOTE: Do not store sensitive keys in plain files. Use secure env/config.
$HEADER_MODE = 'dashboard'; require_once __DIR__ . '/../includes/header.php';
session_start();
?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Payment Gateway</title></head>
<body>
  <main>
    <h1>Payment Gateway</h1>
    <p>Integrate payment provider here (Stripe, PayPal, etc.).</p>
  </main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>