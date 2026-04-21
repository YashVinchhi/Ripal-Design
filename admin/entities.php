<?php
$projectRoot = defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__, 1);
require_once $projectRoot . '/app/Core/Bootstrap/init.php';

require_login();
require_role('admin');
require_once PROJECT_ROOT . '/app/Core/Services/scoring.php';

$db = get_db();
$message = '';
$error = '';

// POST handlers: add_category, add_entity (vendor|worker)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'add_category') {
            $code = trim((string)($_POST['code'] ?? ''));
            $name = trim((string)($_POST['name'] ?? ''));
            $desc = trim((string)($_POST['description'] ?? ''));
            if ($code !== '' && $name !== '' && $db instanceof PDO) {
                $stmt = $db->prepare('INSERT IGNORE INTO vendor_categories (code, name, description) VALUES (?, ?, ?)');
                $stmt->execute([$code, $name, $desc]);
                $message = 'Category added';
            }
        }

        if ($action === 'add_entity') {
            $type = strtolower(trim((string)($_POST['entity_type'] ?? 'vendor')));
            if ($type === 'vendor') {
                $name = trim((string)($_POST['name'] ?? ''));
                $contact = trim((string)($_POST['contact_name'] ?? ''));
                $phone = trim((string)($_POST['phone'] ?? ''));
                $email = trim((string)($_POST['email'] ?? ''));
                $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : null;
                $notes = trim((string)($_POST['notes'] ?? ''));
                if ($name !== '' && $db instanceof PDO) {
                    $stmt = $db->prepare('INSERT INTO vendors (name, contact_name, phone, email, category_id, notes) VALUES (?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$name, $contact, $phone, $email, $category_id ?: null, $notes]);
                    $message = 'Vendor added';
                }
            } elseif ($type === 'worker') {
                $first = trim((string)($_POST['first_name'] ?? ''));
                $last = trim((string)($_POST['last_name'] ?? ''));
                $email = trim((string)($_POST['email'] ?? ''));
                $phone = trim((string)($_POST['phone'] ?? ''));
                if ($first === '' && $last === '' && !empty($_POST['name'])) {
                    $parts = preg_split('/\s+/', trim((string)$_POST['name']));
                    $first = $parts[0] ?? '';
                    $last = isset($parts[1]) ? implode(' ', array_slice($parts,1)) : '';
                }

                if ($db instanceof PDO && ($first !== '' || $last !== '')) {
                    // generate username base
                    $base = preg_replace('/[^a-z0-9._-]+/', '', strtolower(substr(trim($first . '.' . $last),0,40)));
                    if ($base === '') $base = 'worker';
                    $candidate = substr($base,0,20);
                    $suf = 1;
                    while (true) {
                        $chk = $db->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
                        $chk->execute([$candidate]);
                        if (!$chk->fetch(PDO::FETCH_ASSOC)) break;
                        $candidate = substr($base,0, max(1,20 - strlen((string)$suf))) . $suf;
                        $suf++;
                    }
                    $username = $candidate;

                    // temp password (admin must share it securely)
                    try { $tempPass = bin2hex(random_bytes(3)); } catch (Throwable $t) { $tempPass = substr(md5(uniqid('',true)),0,8); }
                    $passHash = password_hash($tempPass, PASSWORD_DEFAULT);

                    $full = trim(($first ?: '') . ' ' . ($last ?: ''));
                    $ins = $db->prepare('INSERT INTO users (username, full_name, first_name, last_name, email, password_hash, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, "active", NOW())');
                    $ins->execute([$username, $full, $first, $last, $email, $passHash, 'worker']);
                    $newId = (int)$db->lastInsertId();
                    $message = 'Worker created: ' . $username . ' (ID ' . $newId . ')';
                    if ($email !== '') $message .= ' • notification not sent';
                    // show temp password for admin to share
                    $message .= ' • temp password: ' . $tempPass;
                }
            }
        }
    } catch (Exception $e) {
        $error = 'Save failed: ' . $e->getMessage();
    }
    if ($message !== '') {
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

$categories = [];
$vendors = [];
$workers = [];
if ($db instanceof PDO) {
    try {
        $categories = $db->query('SELECT id, code, name FROM vendor_categories ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);
        $vendors = $db->query('SELECT v.id, v.name, v.contact_name, v.phone, v.email, vc.name AS category, COALESCE(vs.decision_score,0) AS decision_score, (SELECT COUNT(*) FROM vendor_metric_events vme WHERE vme.vendor_id = v.id) AS events_count FROM vendors v LEFT JOIN vendor_categories vc ON vc.id = v.category_id LEFT JOIN vendor_scores vs ON vs.vendor_id = v.id ORDER BY v.name ASC')->fetchAll(PDO::FETCH_ASSOC);
        $workers = $db->query("SELECT u.id, COALESCE(u.first_name,'') AS first_name, COALESCE(u.last_name,'') AS last_name, u.username, u.email, u.phone, COALESCE(ws.decision_score,0) AS decision_score, (SELECT COUNT(*) FROM worker_metric_events wme WHERE wme.worker_id = u.id) AS events_count FROM users u LEFT JOIN worker_scores ws ON ws.worker_id = u.id WHERE u.role = 'worker' ORDER BY u.username ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // ignore
    }
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Entities | Ripal Design</title>
  <?php $HEADER_MODE = 'dashboard'; require_once PROJECT_ROOT . '/Common/header.php'; ?>
  <style>
    /* Make main content use 80% width on desktop with 10% margins */
    .page-80 { width: 100%; max-width: none !important; }
    @media (min-width: 768px) {
      .page-80 { width: 80% !important; margin-left: 10% !important; margin-right: 10% !important; }
    }
  </style>
</head>
<body class="bg-canvas-white font-sans text-foundation-grey min-h-screen">
  <div class="min-h-screen flex flex-col">
    <header class="bg-foundation-grey text-white pt-20 pb-8 px-4 sm:px-6 lg:px-8 shadow-lg mb-8 md:mb-12 border-b-2 border-rajkot-rust">
      <div class="max-w-7xl mx-auto">
        <h1 class="text-3xl font-serif font-bold">Entities</h1>
        <p class="text-gray-300 mt-2 text-sm">Unified management for Vendors and Field Workers.</p>
      </div>
    </header>

    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20 page-80">
      <?php if ($message): ?><div class="mb-6 bg-approval-green/10 border border-approval-green/30 text-approval-green px-4 py-3 text-xs font-bold uppercase tracking-wider"><?php echo esc($message); ?></div><?php endif; ?>
      <?php if ($error): ?><div class="mb-6 bg-red-50 border border-rajkot-rust text-rajkot-rust px-4 py-3 text-xs font-bold uppercase tracking-wider"><?php echo esc($error); ?></div><?php endif; ?>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="bg-white p-6 shadow-premium border border-gray-100 lg:col-span-1">
          <h2 class="text-xl font-bold mb-4">Add Entity</h2>
          <form method="post" id="addEntityForm">
            <?php echo csrf_token_field(); ?>
            <input type="hidden" name="action" value="add_entity">
            <div class="mb-3"><label class="block text-sm font-bold mb-1">Type</label>
              <select name="entity_type" id="entity_type" class="w-full p-3 border">
                <option value="vendor">Vendor</option>
                <option value="worker">Worker</option>
              </select>
            </div>

            <div id="vendor_fields">
              <div class="mb-3 two-col"><label class="block text-sm font-bold mb-1">Vendor Name</label><input name="name" class="w-full p-3 border"></div>
              <div class="mb-3 two-col"><label class="block text-sm font-bold mb-1">Contact Name</label><input name="contact_name" class="w-full p-3 border"></div>
              <div class="mb-3 two-col"><label class="block text-sm font-bold mb-1">Phone</label><input name="phone" class="w-full p-3 border"></div>
              <div class="mb-3 two-col"><label class="block text-sm font-bold mb-1">Email</label><input name="email" class="w-full p-3 border" type="email"></div>
              <div class="mb-3 two-col"><label class="block text-sm font-bold mb-1">Category</label>
                <select name="category_id" class="w-full p-3 border">
                  <option value="">-- none --</option>
                  <?php foreach ($categories as $c): ?><option value="<?php echo (int)$c['id']; ?>"><?php echo esc($c['name']); ?></option><?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3 full"><label class="block text-sm font-bold mb-1">Notes</label><textarea name="notes" class="w-full p-3 border"></textarea></div>
            </div>

            <div id="worker_fields" style="display:none;">
              <div class="mb-3 two-col"><label class="block text-sm font-bold mb-1">First Name</label><input name="first_name" class="w-full p-3 border"></div>
              <div class="mb-3 two-col"><label class="block text-sm font-bold mb-1">Last Name</label><input name="last_name" class="w-full p-3 border"></div>
              <div class="mb-3 two-col"><label class="block text-sm font-bold mb-1">Email</label><input name="email" class="w-full p-3 border" type="email"></div>
              <div class="mb-3 two-col"><label class="block text-sm font-bold mb-1">Phone</label><input name="phone" class="w-full p-3 border"></div>
            </div>

            <div class="mt-4"><button type="submit" class="bg-foundation-grey text-white px-6 py-3 font-bold">Add</button></div>
          </form>

          <hr class="my-6">

          <h3 class="text-lg font-bold mb-3">Add Category</h3>
          <form method="post">
            <?php echo csrf_token_field(); ?>
            <input type="hidden" name="action" value="add_category">
            <div class="mb-3"><label class="block text-sm font-bold mb-1">Code</label><input name="code" class="w-full p-3 border"></div>
            <div class="mb-3"><label class="block text-sm font-bold mb-1">Name</label><input name="name" class="w-full p-3 border"></div>
            <div class="mb-3"><label class="block text-sm font-bold mb-1">Description</label><textarea name="description" class="w-full p-3 border"></textarea></div>
            <div><button class="bg-foundation-grey text-white px-6 py-3 font-bold">Create Category</button></div>
          </form>
        </div>

        <div class="lg:col-span-2">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <section class="bg-white p-6 shadow-premium border border-gray-100">
              <h3 class="text-lg font-bold mb-4">Vendors</h3>
              <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                  <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-widest"><tr><th class="px-4 py-2">Vendor</th><th class="px-4 py-2">Contact</th><th class="px-4 py-2">Phone</th><th class="px-4 py-2">Score</th></tr></thead>
                  <tbody>
                    <?php if (empty($vendors)): ?><tr><td colspan="4" class="px-4 py-6 text-gray-500">No vendors defined.</td></tr><?php else: foreach ($vendors as $v): ?>
                      <tr class="border-t"><td class="px-4 py-3 font-semibold"><?php echo esc($v['name']); ?></td><td class="px-4 py-3"><?php echo esc($v['contact_name']); ?></td><td class="px-4 py-3"><?php echo esc($v['phone']); ?></td><td class="px-4 py-3"><?php echo isset($v['decision_score']) ? round(floatval($v['decision_score'])*100,1) . '%' : 'N/A'; ?> (<?php echo (int)$v['events_count']; ?>)</td></tr>
                    <?php endforeach; endif; ?>
                  </tbody>
                </table>
              </div>
            </section>

            <section class="bg-white p-6 shadow-premium border border-gray-100">
              <h3 class="text-lg font-bold mb-4">Workers</h3>
              <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                  <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-widest"><tr><th class="px-4 py-2">Worker</th><th class="px-4 py-2">Contact</th><th class="px-4 py-2">Phone</th><th class="px-4 py-2">Score</th></tr></thead>
                  <tbody>
                    <?php if (empty($workers)): ?><tr><td colspan="4" class="px-4 py-6 text-gray-500">No workers found.</td></tr><?php else: foreach ($workers as $w): $full = trim((string)$w['first_name'] . ' ' . (string)$w['last_name']); ?>
                      <tr class="border-t"><td class="px-4 py-3 font-semibold"><?php echo esc($full !== '' ? $full : $w['username']); ?></td><td class="px-4 py-3"><?php echo esc($w['email']); ?></td><td class="px-4 py-3"><?php echo esc($w['phone']); ?></td><td class="px-4 py-3"><?php echo isset($w['decision_score']) ? round(floatval($w['decision_score'])*100,1) . '%' : 'N/A'; ?> (<?php echo (int)$w['events_count']; ?>)</td></tr>
                    <?php endforeach; endif; ?>
                  </tbody>
                </table>
              </div>
            </section>
          </div>
        </div>
      </div>
    </main>

    <?php require_once PROJECT_ROOT . '/Common/footer.php'; ?>
  </div>

  <script>
    document.getElementById('entity_type').addEventListener('change', function(e){
      var t = e.target.value;
      document.getElementById('vendor_fields').style.display = t === 'vendor' ? 'block' : 'none';
      document.getElementById('worker_fields').style.display = t === 'worker' ? 'block' : 'none';
    });
  </script>
</body>
</html>
