<?php
$projectRoot = defined('PROJECT_ROOT') ? PROJECT_ROOT : dirname(__DIR__, 1);
require_once $projectRoot . '/app/Core/Bootstrap/init.php';

require_login();
require_role('admin');

$db = get_db();
$message = '';

// Handle category creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_category') {
    require_csrf();
    $code = trim((string)($_POST['code'] ?? ''));
    $name = trim((string)($_POST['name'] ?? ''));
    $desc = trim((string)($_POST['description'] ?? ''));
    if ($code !== '' && $name !== '' && $db instanceof PDO) {
        $stmt = $db->prepare('INSERT IGNORE INTO vendor_categories (code, name, description) VALUES (?, ?, ?)');
        $stmt->execute([$code, $name, $desc]);
        $message = 'Category added';
    }
}

// Handle vendor creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_vendor') {
    require_csrf();
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
}

$categories = [];
$vendors = [];
if ($db instanceof PDO) {
    try {
      $categories = $db->query('SELECT id, code, name FROM vendor_categories ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);
      $vendors = $db->query('SELECT v.id, v.name, v.contact_name, v.phone, v.email, vc.name AS category, COALESCE(vs.decision_score,0) AS decision_score, COALESCE(vs.final_score,0) AS final_score, COALESCE(vs.confidence,0) AS confidence, COALESCE(vs.risk,0) AS risk, (SELECT COUNT(*) FROM vendor_metric_events vme WHERE vme.vendor_id = v.id) AS events_count FROM vendors v LEFT JOIN vendor_categories vc ON vc.id = v.category_id LEFT JOIN vendor_scores vs ON vs.vendor_id = v.id ORDER BY v.name ASC')->fetchAll(PDO::FETCH_ASSOC);
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
  <title>Vendors | Ripal Design</title>
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
        <h1 class="text-3xl font-serif font-bold">Vendor Management</h1>
        <p class="text-gray-300 mt-2 text-sm">Manage supplier partners and categories.</p>
      </div>
    </header>

    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-20 page-80">
      <?php if ($message !== ''): ?>
        <div class="mb-6 bg-approval-green/10 border border-approval-green/30 text-approval-green px-4 py-3 text-xs font-bold uppercase tracking-wider"><?php echo esc($message); ?></div>
      <?php endif; ?>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white p-6 shadow-premium border border-gray-100">
          <h2 class="text-xl font-bold mb-4">Add Category</h2>
          <form method="post">
            <?php echo csrf_token_field(); ?>
            <input type="hidden" name="action" value="add_category">
            <div class="mb-4"><label class="block text-sm font-bold mb-1">Code</label><input name="code" required class="w-full p-3 border"></div>
            <div class="mb-4"><label class="block text-sm font-bold mb-1">Name</label><input name="name" required class="w-full p-3 border"></div>
            <div class="mb-4"><label class="block text-sm font-bold mb-1">Description</label><textarea name="description" class="w-full p-3 border"></textarea></div>
            <button class="bg-foundation-grey text-white px-6 py-3 font-bold">Create</button>
          </form>
        </div>

        <div class="bg-white p-6 shadow-premium border border-gray-100">
          <h2 class="text-xl font-bold mb-4">Add Vendor</h2>
          <form method="post">
            <?php echo csrf_token_field(); ?>
            <input type="hidden" name="action" value="add_vendor">
            <div class="mb-3"><label class="block text-sm font-bold mb-1">Vendor Name</label><input name="name" required class="w-full p-3 border"></div>
            <div class="mb-3"><label class="block text-sm font-bold mb-1">Contact</label><input name="contact_name" class="w-full p-3 border"></div>
            <div class="mb-3"><label class="block text-sm font-bold mb-1">Phone</label><input name="phone" class="w-full p-3 border"></div>
            <div class="mb-3"><label class="block text-sm font-bold mb-1">Email</label><input name="email" class="w-full p-3 border"></div>
            <div class="mb-3"><label class="block text-sm font-bold mb-1">Category</label>
              <select name="category_id" class="w-full p-3 border">
                <option value="">-- none --</option>
                <?php foreach ($categories as $c): ?>
                  <option value="<?php echo (int)$c['id']; ?>"><?php echo esc($c['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-4"><label class="block text-sm font-bold mb-1">Notes</label><textarea name="notes" class="w-full p-3 border"></textarea></div>
            <button class="bg-foundation-grey text-white px-6 py-3 font-bold">Add Vendor</button>
          </form>
        </div>
      </div>

      <section class="mt-10 bg-white p-6 shadow-premium border border-gray-100">
        <h3 class="text-lg font-bold mb-4">Existing Vendors</h3>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase tracking-widest">
              <tr><th class="px-4 py-2">Vendor</th><th class="px-4 py-2">Contact</th><th class="px-4 py-2">Phone</th><th class="px-4 py-2">Category</th><th class="px-4 py-2">Score</th></tr>
            </thead>
            <tbody>
              <?php if (empty($vendors)): ?>
                <tr><td colspan="4" class="px-4 py-6 text-gray-500">No vendors defined.</td></tr>
              <?php else: ?>
                <?php foreach ($vendors as $v): ?>
                  <tr class="border-t"><td class="px-4 py-3 font-semibold"><?php echo esc($v['name']); ?></td><td class="px-4 py-3"><?php echo esc($v['contact_name']); ?></td><td class="px-4 py-3"><?php echo esc($v['phone']); ?></td><td class="px-4 py-3"><?php echo esc($v['category']); ?></td><td class="px-4 py-3"><?php echo isset($v['decision_score']) ? round(floatval($v['decision_score'])*100,1) . '%' : 'N/A'; ?> (<?php echo (int)$v['events_count']; ?>)</td></tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>
    </main>

    <?php if (!defined('HIDE_FOOTER_CTA')) define('HIDE_FOOTER_CTA', true); require_once PROJECT_ROOT . '/Common/footer.php'; ?>
  </div>
</body>
</html>
