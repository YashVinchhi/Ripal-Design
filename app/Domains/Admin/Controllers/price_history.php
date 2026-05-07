<?php

if (!defined('PROJECT_ROOT')) {
    require_once dirname(__DIR__, 4) . '/app/Core/Bootstrap/init.php';
}

require_login();
require_role('admin');

require_once PROJECT_ROOT . '/app/Shared/Materials/MaterialsHelpers.php';

$selfUrl = function_exists('base_path')
    ? base_path('admin/price_history.php')
    : rtrim((string)BASE_PATH, '/') . '/admin/price_history.php';

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
$priceHistory = [];
$selectedGoods = null;
$overview = [
    'goods' => 0,
    'price_entries' => 0,
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

    if ($action === 'save_price_entry') {
        $goodsId = ms_int($_POST['goods_id'] ?? 0);
        $vendorId = ms_int($_POST['vendor_id'] ?? 0);
        $price = ms_decimal($_POST['price'] ?? 0, 0.0);
        $currency = strtoupper(trim((string)($_POST['currency'] ?? 'INR')));
        $effectiveAt = trim((string)($_POST['effective_at'] ?? ''));
        $quoteType = strtolower(trim((string)($_POST['quote_type'] ?? 'quote')));
        $sourceNote = trim((string)($_POST['source_note'] ?? ''));
        $expiryDate = trim((string)($_POST['expiry_date'] ?? ''));

        if ($goodsId > 0 && $price > 0) {
            db_query(
                'INSERT INTO material_price_entries (goods_id, vendor_id, price, currency, effective_at, quote_type, source_note, expiry_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [$goodsId, $vendorId > 0 ? $vendorId : null, $price, $currency !== '' ? $currency : 'INR', $effectiveAt !== '' ? $effectiveAt : null, in_array($quoteType, ['quote', 'purchase', 'actual_closeout'], true) ? $quoteType : 'quote', $sourceNote !== '' ? $sourceNote : null, $expiryDate !== '' ? $expiryDate : null]
            );
            set_flash('Price entry added.', 'success');
        } else {
            set_flash('Select a goods item and enter a valid price.', 'error');
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

    $countRow = db_fetch('SELECT COUNT(*) AS total FROM material_goods') ?: [];
    $countPriceRow = db_fetch('SELECT COUNT(*) AS total FROM material_price_entries') ?: [];

    $overview['goods'] = (int)($countRow['total'] ?? 0);
    $overview['price_entries'] = (int)($countPriceRow['total'] ?? 0);

    if ($selectedGoodsId > 0) {
        $selectedGoods = db_fetch('SELECT * FROM material_goods WHERE id = ? LIMIT 1', [$selectedGoodsId]);
        if ($selectedGoods) {
            $priceHistory = db_fetch_all('SELECT pe.*, v.name AS vendor_name FROM material_price_entries pe LEFT JOIN vendors v ON v.id = pe.vendor_id WHERE pe.goods_id = ? ORDER BY COALESCE(pe.effective_at, pe.created_at) DESC, pe.id DESC', [$selectedGoodsId]);
        }
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
  <title>Price History | Materials Studio | Ripal Design</title>
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
            <h1 class="text-3xl md:text-4xl font-serif font-bold">Price History</h1>
            <p class="text-gray-300 mt-3 text-sm max-w-3xl">Track pricing evolution for each goods item from vendors and actual purchases.</p>
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
              <span class="section-label block mb-2">Price Entries</span>
              <span class="text-2xl md:text-3xl font-serif font-black text-foundation-grey"><?php echo (int)$overview['price_entries']; ?></span>
            </div>
            <i class="fa-solid fa-chart-line text-lg text-rajkot-rust"></i>
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
                <div class="section-label mb-2">Price Entries</div>
                <h2 class="text-2xl font-serif font-bold mb-1">Track pricing history</h2>
                <p class="text-gray-600 text-sm">Append-only price records for goods by vendor and date. Keep full pricing history.</p>
              </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
              <div>
                <form method="post" class="space-y-4">
                  <?php echo csrf_token_field(); ?>
                  <input type="hidden" name="action" value="save_price_entry">
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
                    <label class="section-label block mb-2">Price *</label>
                    <input name="price" type="number" step="0.01" min="0" required class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                  </div>
                  <div class="grid grid-cols-2 gap-3">
                    <div>
                      <label class="section-label block mb-2">Currency</label>
                      <input name="currency" value="INR" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                    </div>
                    <div>
                      <label class="section-label block mb-2">Quote Type</label>
                      <select name="quote_type" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                        <option value="quote">Quote</option>
                        <option value="purchase">Purchase</option>
                        <option value="actual_closeout">Actual Closeout</option>
                      </select>
                    </div>
                  </div>
                  <div>
                    <label class="section-label block mb-2">Vendor</label>
                    <select name="vendor_id" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                      <option value="">-- optional --</option>
                      <?php foreach ($vendors as $vendor): ?>
                        <option value="<?php echo (int)$vendor['id']; ?>"><?php echo esc($vendor['name']); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div>
                    <label class="section-label block mb-2">Effective At</label>
                    <input name="effective_at" type="date" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                  </div>
                  <div>
                    <label class="section-label block mb-2">Expiry Date</label>
                    <input name="expiry_date" type="date" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                  </div>
                  <div>
                    <label class="section-label block mb-2">Source Note</label>
                    <input name="source_note" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust" placeholder="e.g., Email quote, Purchase order">
                  </div>
                  <button type="submit" class="w-full bg-foundation-grey text-white px-6 py-3 font-bold uppercase tracking-widest hover:bg-opacity-90 transition-all">Add Price Entry</button>
                </form>
              </div>

              <div class="lg:col-span-2">
                <div class="overflow-x-auto">
                  <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-widest text-gray-600 border-b">
                      <tr>
                        <th class="px-4 py-3 text-right">Price</th>
                        <th class="px-4 py-3 text-left">Vendor</th>
                        <th class="px-4 py-3 text-left">Type</th>
                        <th class="px-4 py-3 text-left">Effective</th>
                        <th class="px-4 py-3 text-left">Expiry</th>
                        <th class="px-4 py-3 text-left">Source</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (empty($priceHistory)): ?>
                        <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">Select a goods item to view price history.</td></tr>
                      <?php else: ?>
                        <?php foreach ($priceHistory as $entry): ?>
                          <tr class="border-t">
                            <td class="px-4 py-3 text-right font-semibold">₹ <?php echo number_format((float)$entry['price'], 2); ?></td>
                            <td class="px-4 py-3"><?php echo esc((string)($entry['vendor_name'] ?? '—')); ?></td>
                            <td class="px-4 py-3 text-xs"><?php echo esc((string)$entry['quote_type']); ?></td>
                            <td class="px-4 py-3 text-xs"><?php echo esc((string)($entry['effective_at'] ?? '—')); ?></td>
                            <td class="px-4 py-3 text-xs"><?php echo esc((string)($entry['expiry_date'] ?? '—')); ?></td>
                            <td class="px-4 py-3 text-xs text-gray-500"><?php echo esc((string)($entry['source_note'] ?? '—')); ?></td>
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
