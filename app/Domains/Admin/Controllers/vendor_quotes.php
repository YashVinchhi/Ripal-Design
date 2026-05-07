<?php

if (!defined('PROJECT_ROOT')) {
    require_once dirname(__DIR__, 4) . '/app/Core/Bootstrap/init.php';
}

require_login();
require_role('admin');

require_once PROJECT_ROOT . '/app/Shared/Materials/MaterialsHelpers.php';

$selfUrl = function_exists('base_path')
    ? base_path('admin/vendor_quotes.php')
    : rtrim((string)BASE_PATH, '/') . '/admin/vendor_quotes.php';

$schemaTables = [
    'material_goods',
    'material_price_entries',
    'material_vendor_quotes',
    'material_assets',
];

$schemaReady = true;
foreach ($schemaTables as $schemaTable) {
    if (!db_table_exists($schemaTable)) {
        $schemaReady = false;
        break;
    }
}

$db = get_db();
$selectedGoodsId = ms_int($_GET['goods_id'] ?? 0);

$goods = [];
$vendors = [];
$quotes = [];
$selectedGoods = null;
$overview = [
    'goods' => 0,
    'vendor_quotes' => 0,
];

if (db_table_exists('vendors')) {
    $vendors = db_fetch_all('SELECT id, name, contact_name, phone, email FROM vendors ORDER BY name ASC');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $action = trim((string)($_POST['action'] ?? ''));

    if (!$schemaReady && !in_array($action, ['noop'], true)) {
        set_flash('Run sql/migrations/20260502_materials_platform.sql before using Materials Studio.', 'error');
        header('Location: ' . $selfUrl);
        exit;
    }

    if ($action === 'save_vendor_quote') {
        $goodsId = ms_int($_POST['goods_id'] ?? 0);
        $vendorId = ms_int($_POST['vendor_id'] ?? 0);
        $quotedPrice = ms_decimal($_POST['quoted_price'] ?? 0, 0.0);
        $currency = strtoupper(trim((string)($_POST['quote_currency'] ?? 'INR')));
        $minimumOrderQty = ms_decimal($_POST['minimum_order_qty'] ?? 0, 0.0);
        $leadTimeDays = ms_int($_POST['lead_time_days'] ?? 0);
        $lastQuoteDate = trim((string)($_POST['last_quote_date'] ?? ''));
        $validUntil = trim((string)($_POST['valid_until'] ?? ''));
        $preferredSupplier = !empty($_POST['preferred_supplier']) ? 1 : 0;
        $notes = trim((string)($_POST['quote_notes'] ?? ''));

        if ($goodsId > 0 && $vendorId > 0 && $quotedPrice > 0) {
            try {
                $db->beginTransaction();
                $stmt = $db->prepare('INSERT INTO material_vendor_quotes (goods_id, vendor_id, quoted_price, currency, minimum_order_qty, lead_time_days, last_quote_date, valid_until, preferred_supplier, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$goodsId, $vendorId, $quotedPrice, $currency !== '' ? $currency : 'INR', $minimumOrderQty, $leadTimeDays, $lastQuoteDate !== '' ? $lastQuoteDate : null, $validUntil !== '' ? $validUntil : null, $preferredSupplier, $notes !== '' ? $notes : null]);
                $stmt = $db->prepare('INSERT INTO material_price_entries (goods_id, vendor_id, price, currency, effective_at, quote_type, source_note, expiry_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$goodsId, $vendorId, $quotedPrice, $currency !== '' ? $currency : 'INR', $lastQuoteDate !== '' ? $lastQuoteDate : date('Y-m-d H:i:s'), 'quote', $notes !== '' ? $notes : 'Vendor quote', $validUntil !== '' ? $validUntil : null]);
                $db->commit();
                set_flash('Vendor quote saved.', 'success');
            } catch (Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                set_flash('Could not save quote: ' . $e->getMessage(), 'error');
            }
        } else {
            set_flash('Select a goods item, vendor, and price.', 'error');
        }

        header('Location: ' . $selfUrl . '?goods_id=' . $goodsId);
        exit;
    }

    set_flash('No action was executed.', 'info');
    header('Location: ' . $selfUrl);
    exit;
}

if (db_connected() && $schemaReady) {
    $goods = db_fetch_all("SELECT g.*, (SELECT pe.price FROM material_price_entries pe WHERE pe.goods_id = g.id AND (pe.expiry_date IS NULL OR pe.expiry_date >= CURDATE()) ORDER BY COALESCE(pe.effective_at, pe.created_at) DESC, pe.id DESC LIMIT 1) AS current_price FROM material_goods g ORDER BY g.status = 'active' DESC, g.name ASC");

    $quotes = db_fetch_all('SELECT q.*, g.name AS goods_name, v.name AS vendor_name FROM material_vendor_quotes q INNER JOIN material_goods g ON g.id = q.goods_id LEFT JOIN vendors v ON v.id = q.vendor_id ORDER BY q.preferred_supplier DESC, q.valid_until DESC, q.last_quote_date DESC, q.id DESC');

    $countRow = db_fetch('SELECT COUNT(*) AS total FROM material_goods') ?: [];
    $countQuoteRow = db_fetch('SELECT COUNT(*) AS total FROM material_vendor_quotes') ?: [];

    $overview['goods'] = (int)($countRow['total'] ?? 0);
    $overview['vendor_quotes'] = (int)($countQuoteRow['total'] ?? 0);

    if ($selectedGoodsId > 0) {
        $selectedGoods = db_fetch('SELECT * FROM material_goods WHERE id = ? LIMIT 1', [$selectedGoodsId]);
    }
}

if (!is_array($selectedGoods)) {
    $selectedGoods = null;
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Vendor Quotes | Materials Studio | Ripal Design</title>
  <?php $HEADER_MODE = 'dashboard'; require_once PROJECT_ROOT . '/Common/header.php'; ?>
  <style>
    .material-grid {
      display: grid;
      gap: 1.5rem;
    }
    .material-panel {
      background: #fff;
      border: 1px solid rgba(15, 23, 42, 0.08);
      box-shadow: 0 20px 45px rgba(15, 23, 42, 0.06);
    }
    .section-label {
      font-size: 0.7rem;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: #64748b;
      font-weight: 700;
    }
  </style>
</head>
<body class="bg-canvas-white font-sans text-foundation-grey min-h-screen">
  <div class="min-h-screen flex flex-col">
    <header class="bg-foundation-grey text-white pt-20 pb-10 px-4 sm:px-6 lg:px-8 shadow-lg mb-8 md:mb-12 border-b-2 border-rajkot-rust">
      <div class="max-w-7xl mx-auto">
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
          <div>
            <p class="section-label text-gray-300 mb-2">Materials Studio</p>
            <h1 class="text-3xl md:text-4xl font-serif font-bold">Vendor Quotes</h1>
            <p class="text-gray-300 mt-3 text-sm max-w-3xl">Compare pricing, MOQ, and lead times across suppliers for each goods item.</p>
          </div>
          <div class="text-sm text-gray-300">
            <a href="<?php echo base_path('admin/materials_studio.php'); ?>" class="text-rajkot-rust font-bold hover:underline">← Back to Hub</a>
          </div>
        </div>
      </div>
    </header>

    <main class="flex-grow max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 pb-20">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 md:gap-6 mb-8 md:mb-12">
        <div class="material-panel p-6 md:p-8">
          <div class="flex items-start justify-between gap-4">
            <div>
              <span class="section-label block mb-2">Goods Items</span>
              <span class="text-2xl md:text-3xl font-serif font-black text-foundation-grey"><?php echo (int)$overview['goods']; ?></span>
            </div>
            <i class="fa-solid fa-box-open text-lg text-rajkot-rust"></i>
          </div>
        </div>
        <div class="material-panel p-6 md:p-8">
          <div class="flex items-start justify-between gap-4">
            <div>
              <span class="section-label block mb-2">Vendor Quotes</span>
              <span class="text-2xl md:text-3xl font-serif font-black text-foundation-grey"><?php echo (int)$overview['vendor_quotes']; ?></span>
            </div>
            <i class="fa-solid fa-tags text-lg text-rajkot-rust"></i>
          </div>
        </div>
      </div>

      <?php if (!$schemaReady): ?>
        <div class="mt-8 bg-yellow-50 border border-yellow-200 text-yellow-900 px-4 py-3 rounded-sm text-sm font-bold">
          Run sql/migrations/20260502_materials_platform.sql to initialize the Materials Studio schema.
        </div>
      <?php else: ?>
        <div class="material-grid">
          <section class="material-panel p-6 md:p-8">
            <div class="mb-6 flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
              <div>
                <div class="section-label mb-2">Vendor Quotes</div>
                <h2 class="text-2xl font-serif font-bold mb-1">Compare supplier pricing</h2>
                <p class="text-gray-600 text-sm">Store vendor quotes with MOQ, lead times, and supplier preferences. Separate from purchase history.</p>
              </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
              <div>
                <form method="post" class="space-y-4">
                  <?php echo csrf_token_field(); ?>
                  <input type="hidden" name="action" value="save_vendor_quote">
                  <div>
                    <label class="section-label block mb-2">Select Goods *</label>
                    <select name="goods_id" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust" required onchange="window.location.href='<?php echo $selfUrl; ?>?goods_id=' + this.value">
                      <option value="">-- select --</option>
                      <?php foreach ($goods as $good): ?>
                        <option value="<?php echo (int)$good['id']; ?>" <?php echo $selectedGoodsId === (int)$good['id'] ? 'selected' : ''; ?>><?php echo esc($good['name']); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div>
                    <label class="section-label block mb-2">Vendor *</label>
                    <select name="vendor_id" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust" required>
                      <option value="">-- select --</option>
                      <?php foreach ($vendors as $vendor): ?>
                        <option value="<?php echo (int)$vendor['id']; ?>"><?php echo esc($vendor['name']); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div>
                    <label class="section-label block mb-2">Quoted Price *</label>
                    <input name="quoted_price" type="number" step="0.01" min="0" required class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                  </div>
                  <div>
                    <label class="section-label block mb-2">Currency</label>
                    <input name="quote_currency" value="INR" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                  </div>
                  <div class="grid grid-cols-2 gap-3">
                    <div>
                      <label class="section-label block mb-2">MOQ</label>
                      <input name="minimum_order_qty" type="number" step="0.01" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                    </div>
                    <div>
                      <label class="section-label block mb-2">Lead Time (days)</label>
                      <input name="lead_time_days" type="number" min="0" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                    </div>
                  </div>
                  <div class="grid grid-cols-2 gap-3">
                    <div>
                      <label class="section-label block mb-2">Quote Date</label>
                      <input name="last_quote_date" type="date" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                    </div>
                    <div>
                      <label class="section-label block mb-2">Valid Until</label>
                      <input name="valid_until" type="date" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                    </div>
                  </div>
                  <div>
                    <input type="checkbox" name="preferred_supplier" value="1" id="ms-pref-supplier">
                    <label for="ms-pref-supplier" class="text-sm">Preferred supplier</label>
                  </div>
                  <div>
                    <label class="section-label block mb-2">Notes</label>
                    <textarea name="quote_notes" rows="3" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust"></textarea>
                  </div>
                  <button type="submit" class="w-full bg-foundation-grey text-white px-6 py-3 font-bold uppercase tracking-widest hover:bg-opacity-90 transition-all">Save Quote</button>
                </form>
              </div>

              <div class="lg:col-span-2">
                <div class="overflow-x-auto">
                  <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-widest text-gray-600 border-b">
                      <tr>
                        <th class="px-4 py-3 text-left">Goods</th>
                        <th class="px-4 py-3 text-left">Vendor</th>
                        <th class="px-4 py-3 text-right">Price</th>
                        <th class="px-4 py-3 text-right">MOQ</th>
                        <th class="px-4 py-3 text-right">Lead</th>
                        <th class="px-4 py-3 text-center">Preferred</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (empty($quotes)): ?>
                        <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">No vendor quotes yet.</td></tr>
                      <?php else: ?>
                        <?php foreach ($quotes as $quote): ?>
                          <tr class="border-t">
                            <td class="px-4 py-3"><?php echo esc($quote['goods_name']); ?></td>
                            <td class="px-4 py-3"><?php echo esc($quote['vendor_name']); ?></td>
                            <td class="px-4 py-3 text-right">₹ <?php echo number_format((float)$quote['quoted_price'], 2); ?></td>
                            <td class="px-4 py-3 text-right"><?php echo number_format((float)$quote['minimum_order_qty'], 2); ?></td>
                            <td class="px-4 py-3 text-right"><?php echo (int)$quote['lead_time_days']; ?>d</td>
                            <td class="px-4 py-3 text-center"><?php echo $quote['preferred_supplier'] ? '✓' : ''; ?></td>
                          </tr>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </section>
        </div>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>
