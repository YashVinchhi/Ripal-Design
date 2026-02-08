<?php
session_start();
$user = $_SESSION['user'] ?? 'employee01';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Profile — <?php echo htmlspecialchars($user); ?></title>
  <link rel="stylesheet" href="../worker/worker_dashboard.css">
</head>
<body>
  <?php require_once __DIR__ . '/../Common/header.php'; ?>
  <main class="worker-dashboard">
    <div class="page-header">
      <div class="toolbar justify-content-between">
        <div class="title-wrap">
          <h1>Profile</h1>
          <p class="muted">Manage your profile and account settings</p>
        </div>
        <div class="avatar" aria-hidden="true"><?php echo strtoupper(substr($user,0,2)); ?></div>
      </div>
    </div>

    <section class="info-card" style="max-width:720px;">
      <h3>Account</h3>
      <div style="display:flex; gap:18px; align-items:center;">
        <div class="avatar" style="width:72px; height:72px; font-size:20px;"><?php echo strtoupper(substr($user,0,2)); ?></div>
        <div>
          <div style="font-weight:700; font-size:18px;"><?php echo htmlspecialchars($user); ?></div>
          <div class="muted">Role: Employee</div>
        </div>
      </div>

      <hr style="margin:16px 0; border-color:var(--color-border);">

      <form>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
          <div>
            <label class="form-label">Full name</label>
            <input class="form-control" value="Employee Example">
          </div>
          <div>
            <label class="form-label">Email</label>
            <input class="form-control" value="employee@example.com">
          </div>
        </div>

        <div style="margin-top:12px;">
          <label class="form-label">Contact</label>
          <input class="form-control" value="+91 98765 43210">
        </div>

        <div style="margin-top:16px; display:flex; gap:12px; justify-content:flex-end;">
          <button class="btn outline" type="button">Cancel</button>
          <button class="btn primary" type="button">Save Changes</button>
        </div>
      </form>
    </section>

  </main>
  <?php require_once __DIR__ . '/../Common/footer.php'; ?>
</body>
</html>
<?php
session_start();
// Profile placeholder - require authentication in real app
$user = $_SESSION['user'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Profile - Ripal Design</title>
  <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
  <?php require_once __DIR__ . '/../Common/header.php'; ?>
  <main>
    <?php if ($user): ?>
      <h1>Profile: <?php echo htmlspecialchars($user); ?></h1>
      <p>Profile content goes here.</p>
    <?php else: ?>
      <p>Please <a href="../public/login.php">login</a>.</p>
    <?php endif; ?>
  </main>
  <?php require_once __DIR__ . '/../Common/footer.php'; ?>
</body>
</html>