<?php

if (!defined('PROJECT_ROOT')) {
    require_once dirname(__DIR__, 4) . '/app/Core/Bootstrap/init.php';
}

require_login();
require_role('admin');

require_once PROJECT_ROOT . '/app/Shared/Materials/MaterialsHelpers.php';

$selfUrl = function_exists('base_path')
    ? base_path('admin/goods_management.php')
    : rtrim((string)BASE_PATH, '/') . '/admin/goods_management.php';

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
$goodsById = [];
$selectedGoods = null;
$overview = [
    'goods' => 0,
    'price_entries' => 0,
    'vendor_quotes' => 0,
    'assets' => 0,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $action = trim((string)($_POST['action'] ?? ''));

    if (!$schemaReady && !in_array($action, ['noop'], true)) {
        set_flash('Run sql/migrations/20260502_materials_platform.sql before using Materials Studio.', 'error');
        header('Location: ' . $selfUrl);
        exit;
    }

    if ($action === 'save_goods') {
        $goodsId = ms_int($_POST['goods_id'] ?? 0);
        $sku = trim((string)($_POST['sku'] ?? ''));
        $name = trim((string)($_POST['name'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $category = trim((string)($_POST['category'] ?? ''));
        $subcategory = trim((string)($_POST['subcategory'] ?? ''));
        $baseUnit = trim((string)($_POST['base_unit'] ?? 'pcs')) ?: 'pcs';
        $estimateUnit = trim((string)($_POST['estimate_unit'] ?? $baseUnit)) ?: $baseUnit;
        $conversionFactor = max(0.0001, ms_decimal($_POST['unit_conversion_factor'] ?? 1, 1.0));
        $gstRate = max(0, ms_decimal($_POST['gst_rate'] ?? 18, 18.0));
        $wastagePct = max(0, ms_decimal($_POST['default_wastage_pct'] ?? 0, 0.0));
        $laborModel = strtolower(trim((string)($_POST['labor_model'] ?? 'none')));
        if (!in_array($laborModel, ['none', 'flat', 'per_unit'], true)) {
            $laborModel = 'none';
        }
        $laborRate = max(0, ms_decimal($_POST['labor_rate'] ?? 0, 0.0));
        $status = strtolower(trim((string)($_POST['status'] ?? 'active')));
        if (!in_array($status, ['active', 'archived'], true)) {
            $status = 'active';
        }
        $visibility = strtolower(trim((string)($_POST['visibility'] ?? 'public')));
        if (!in_array($visibility, ['private', 'public'], true)) {
            $visibility = 'public';
        }
        $specificationUrl = trim((string)($_POST['specification_url'] ?? ''));
        $notes = trim((string)($_POST['notes'] ?? ''));

        if ($name === '') {
            set_flash('Goods name is required.', 'error');
            header('Location: ' . $selfUrl . '?goods_id=' . $goodsId);
            exit;
        }

        if ($sku === '') {
            $sku = ms_code('GDS', 'material_goods', 'sku');
        }

        try {
            if ($goodsId > 0) {
                $stmt = $db->prepare('UPDATE material_goods SET sku = ?, name = ?, description = ?, category = ?, subcategory = ?, base_unit = ?, estimate_unit = ?, unit_conversion_factor = ?, gst_rate = ?, default_wastage_pct = ?, labor_model = ?, labor_rate = ?, status = ?, visibility = ?, specification_url = ?, notes = ? WHERE id = ? LIMIT 1');
                $stmt->execute([$sku, $name, $description !== '' ? $description : null, $category !== '' ? $category : null, $subcategory !== '' ? $subcategory : null, $baseUnit, $estimateUnit, $conversionFactor, $gstRate, $wastagePct, $laborModel, $laborRate, $status, $visibility, $specificationUrl !== '' ? $specificationUrl : null, $notes !== '' ? $notes : null, $goodsId]);
            } else {
                $stmt = $db->prepare('INSERT INTO material_goods (sku, name, description, category, subcategory, base_unit, estimate_unit, unit_conversion_factor, gst_rate, default_wastage_pct, labor_model, labor_rate, status, visibility, specification_url, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$sku, $name, $description !== '' ? $description : null, $category !== '' ? $category : null, $subcategory !== '' ? $subcategory : null, $baseUnit, $estimateUnit, $conversionFactor, $gstRate, $wastagePct, $laborModel, $laborRate, $status, $visibility, $specificationUrl !== '' ? $specificationUrl : null, $notes !== '' ? $notes : null]);
                $goodsId = (int)$db->lastInsertId();
            }

            set_flash('Goods saved.', 'success');
            header('Location: ' . $selfUrl . '?goods_id=' . $goodsId);
            exit;
        } catch (Throwable $e) {
            set_flash('Could not save goods: ' . $e->getMessage(), 'error');
            header('Location: ' . $selfUrl . '?goods_id=' . $goodsId);
            exit;
        }
    }

    if ($action === 'toggle_goods_status') {
        $goodsId = ms_int($_POST['goods_id'] ?? 0);
        if ($goodsId > 0) {
            $nextStatus = trim((string)($_POST['next_status'] ?? 'active'));
            if (!in_array($nextStatus, ['active', 'archived'], true)) {
                $nextStatus = 'active';
            }
            db_query('UPDATE material_goods SET status = ? WHERE id = ? LIMIT 1', [$nextStatus, $goodsId]);
            set_flash('Goods status updated.', 'success');
        }

        header('Location: ' . $selfUrl . '?goods_id=' . $goodsId);
        exit;
    }

    set_flash('No action was executed.', 'info');
    header('Location: ' . $selfUrl);
    exit;
}

if (db_connected() && $schemaReady) {
    $goods = db_fetch_all("SELECT g.*, (SELECT pe.price FROM material_price_entries pe WHERE pe.goods_id = g.id AND (pe.expiry_date IS NULL OR pe.expiry_date >= CURDATE()) ORDER BY COALESCE(pe.effective_at, pe.created_at) DESC, pe.id DESC LIMIT 1) AS current_price, (SELECT v.name FROM material_price_entries pe LEFT JOIN vendors v ON v.id = pe.vendor_id WHERE pe.goods_id = g.id ORDER BY COALESCE(pe.effective_at, pe.created_at) DESC, pe.id DESC LIMIT 1) AS current_vendor_name, (SELECT COUNT(*) FROM material_price_entries pe WHERE pe.goods_id = g.id) AS price_entries_count, (SELECT COUNT(*) FROM material_vendor_quotes q WHERE q.goods_id = g.id) AS quote_count, (SELECT COUNT(*) FROM material_assets a WHERE a.goods_id = g.id) AS asset_count FROM material_goods g ORDER BY g.status = 'active' DESC, g.name ASC");
    $goodsById = [];
    foreach ($goods as $row) {
        $goodsById[(int)$row['id']] = $row;
    }

    $countRow = db_fetch('SELECT COUNT(*) AS total FROM material_goods') ?: [];
    $countPriceRow = db_fetch('SELECT COUNT(*) AS total FROM material_price_entries') ?: [];
    $countQuoteRow = db_fetch('SELECT COUNT(*) AS total FROM material_vendor_quotes') ?: [];
    $countAssetRow = db_fetch('SELECT COUNT(*) AS total FROM material_assets') ?: [];

    $overview['goods'] = (int)($countRow['total'] ?? 0);
    $overview['price_entries'] = (int)($countPriceRow['total'] ?? 0);
    $overview['vendor_quotes'] = (int)($countQuoteRow['total'] ?? 0);
    $overview['assets'] = (int)($countAssetRow['total'] ?? 0);

    if ($selectedGoodsId > 0) {
        $selectedGoods = db_fetch('SELECT * FROM material_goods WHERE id = ? LIMIT 1', [$selectedGoodsId]);
    }
}

if (!is_array($selectedGoods)) {
    $selectedGoods = null;
}

$goodsForm = [
    'goods_id' => $selectedGoods['id'] ?? 0,
    'sku' => $selectedGoods['sku'] ?? '',
    'name' => $selectedGoods['name'] ?? '',
    'description' => $selectedGoods['description'] ?? '',
    'category' => $selectedGoods['category'] ?? '',
    'subcategory' => $selectedGoods['subcategory'] ?? '',
    'base_unit' => $selectedGoods['base_unit'] ?? 'pcs',
    'estimate_unit' => $selectedGoods['estimate_unit'] ?? 'pcs',
    'unit_conversion_factor' => $selectedGoods['unit_conversion_factor'] ?? '1.0000',
    'gst_rate' => $selectedGoods['gst_rate'] ?? '18.00',
    'default_wastage_pct' => $selectedGoods['default_wastage_pct'] ?? '0.00',
    'labor_model' => $selectedGoods['labor_model'] ?? 'none',
    'labor_rate' => $selectedGoods['labor_rate'] ?? '0.00',
    'status' => $selectedGoods['status'] ?? 'active',
    'visibility' => $selectedGoods['visibility'] ?? 'public',
    'specification_url' => $selectedGoods['specification_url'] ?? '',
    'notes' => $selectedGoods['notes'] ?? '',
];

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Goods Management | Materials Studio | Ripal Design</title>
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
            <h1 class="text-3xl md:text-4xl font-serif font-bold">Goods Master</h1>
            <p class="text-gray-300 mt-3 text-sm max-w-3xl">Define reusable material items with unit conversion, GST rates, wastage factors, and labor rules.</p>
          </div>
          <div class="text-sm text-gray-300">
            <a href="<?php echo base_path('admin/materials_studio.php'); ?>" class="text-rajkot-rust font-bold hover:underline">← Back to Hub</a>
          </div>
        </div>
      </div>
    </header>

    <main class="flex-grow max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 pb-20">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-8 md:mb-12">
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
        <div class="material-panel p-6 md:p-8">
          <div class="flex items-start justify-between gap-4">
            <div>
              <span class="section-label block mb-2">Vendor Quotes</span>
              <span class="text-2xl md:text-3xl font-serif font-black text-foundation-grey"><?php echo (int)$overview['vendor_quotes']; ?></span>
            </div>
            <i class="fa-solid fa-tags text-lg text-rajkot-rust"></i>
          </div>
        </div>
        <div class="material-panel p-6 md:p-8">
          <div class="flex items-start justify-between gap-4">
            <div>
              <span class="section-label block mb-2">Linked Assets</span>
              <span class="text-2xl md:text-3xl font-serif font-black text-foundation-grey"><?php echo (int)$overview['assets']; ?></span>
            </div>
            <i class="fa-solid fa-cube text-lg text-rajkot-rust"></i>
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
                <div class="section-label mb-2">Goods Master</div>
                <h2 class="text-2xl font-serif font-bold mb-1">Goods and unit rules</h2>
                <p class="text-gray-600 text-sm">Store reusable materials with GST, wastage, labor, and unit conversion settings.</p>
              </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
              <div>
                <form method="post" class="space-y-4">
                  <?php echo csrf_token_field(); ?>
                  <input type="hidden" name="action" value="save_goods">
                  <input type="hidden" name="goods_id" value="<?php echo (int)$goodsForm['goods_id']; ?>">
                  <div>
                    <label class="section-label block mb-2">Name *</label>
                    <input name="name" value="<?php echo esc_attr($goodsForm['name']); ?>" required class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                  </div>
                  <div>
                    <label class="section-label block mb-2">SKU</label>
                    <input name="sku" value="<?php echo esc_attr($goodsForm['sku']); ?>" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust" placeholder="Auto-generated if empty">
                  </div>
                  <div>
                    <label class="section-label block mb-2">Category</label>
                    <input name="category" value="<?php echo esc_attr($goodsForm['category']); ?>" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                  </div>
                  <div>
                    <label class="section-label block mb-2">Description</label>
                    <textarea name="description" rows="3" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust"><?php echo esc($goodsForm['description']); ?></textarea>
                  </div>
                  <div class="grid grid-cols-2 gap-3">
                    <div>
                      <label class="section-label block mb-2">Base Unit</label>
                      <input name="base_unit" value="<?php echo esc_attr($goodsForm['base_unit']); ?>" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust" placeholder="pcs, kg">
                    </div>
                    <div>
                      <label class="section-label block mb-2">Estimate Unit</label>
                      <input name="estimate_unit" value="<?php echo esc_attr($goodsForm['estimate_unit']); ?>" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust" placeholder="sqft, pcs">
                    </div>
                  </div>
                  <div class="grid grid-cols-2 gap-3">
                    <div>
                      <label class="section-label block mb-2">Unit Conversion</label>
                      <input name="unit_conversion_factor" type="number" step="0.0001" value="<?php echo esc_attr($goodsForm['unit_conversion_factor']); ?>" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                    </div>
                    <div>
                      <label class="section-label block mb-2">GST %</label>
                      <input name="gst_rate" type="number" step="0.01" value="<?php echo esc_attr($goodsForm['gst_rate']); ?>" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                    </div>
                  </div>
                  <div class="grid grid-cols-2 gap-3">
                    <div>
                      <label class="section-label block mb-2">Wastage %</label>
                      <input name="default_wastage_pct" type="number" step="0.01" value="<?php echo esc_attr($goodsForm['default_wastage_pct']); ?>" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                    </div>
                    <div>
                      <label class="section-label block mb-2">Labor Model</label>
                      <select name="labor_model" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                        <option value="none" <?php echo $goodsForm['labor_model'] === 'none' ? 'selected' : ''; ?>>None</option>
                        <option value="flat" <?php echo $goodsForm['labor_model'] === 'flat' ? 'selected' : ''; ?>>Flat</option>
                        <option value="per_unit" <?php echo $goodsForm['labor_model'] === 'per_unit' ? 'selected' : ''; ?>>Per Unit</option>
                      </select>
                    </div>
                  </div>
                  <div>
                    <label class="section-label block mb-2">Labor Rate</label>
                    <input name="labor_rate" type="number" step="0.01" value="<?php echo esc_attr($goodsForm['labor_rate']); ?>" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                  </div>
                  <div class="grid grid-cols-2 gap-3">
                    <div>
                      <label class="section-label block mb-2">Status</label>
                      <select name="status" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                        <option value="active" <?php echo $goodsForm['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="archived" <?php echo $goodsForm['status'] === 'archived' ? 'selected' : ''; ?>>Archived</option>
                      </select>
                    </div>
                    <div>
                      <label class="section-label block mb-2">Visibility</label>
                      <select name="visibility" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                        <option value="public" <?php echo $goodsForm['visibility'] === 'public' ? 'selected' : ''; ?>>Public</option>
                        <option value="private" <?php echo $goodsForm['visibility'] === 'private' ? 'selected' : ''; ?>>Private</option>
                      </select>
                    </div>
                  </div>
                  <div>
                    <label class="section-label block mb-2">Specification URL</label>
                    <input name="specification_url" value="<?php echo esc_attr($goodsForm['specification_url']); ?>" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                  </div>
                  <div>
                    <label class="section-label block mb-2">Notes</label>
                    <textarea name="notes" rows="3" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust"><?php echo esc($goodsForm['notes']); ?></textarea>
                  </div>
                  <button type="submit" class="w-full bg-foundation-grey text-white px-6 py-3 font-bold uppercase tracking-widest hover:bg-opacity-90 transition-all">Save Goods</button>
                </form>
              </div>

              <div class="lg:col-span-2">
                <div class="overflow-x-auto">
                  <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-widest text-gray-600 border-b">
                      <tr>
                        <th class="px-4 py-3 text-left">Name</th>
                        <th class="px-4 py-3 text-left">SKU</th>
                        <th class="px-4 py-3 text-left">Category</th>
                        <th class="px-4 py-3 text-left">Current Price</th>
                        <th class="px-4 py-3 text-left">Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (empty($goods)): ?>
                        <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">No goods yet.</td></tr>
                      <?php else: ?>
                        <?php foreach ($goods as $item): ?>
                          <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-3 font-semibold"><a href="<?php echo esc_attr($selfUrl . '?goods_id=' . (int)$item['id']); ?>" class="text-rajkot-rust"><?php echo esc($item['name']); ?></a></td>
                            <td class="px-4 py-3 text-gray-600 text-xs"><?php echo esc($item['sku']); ?></td>
                            <td class="px-4 py-3 text-gray-600"><?php echo esc((string)($item['category'] ?? '—')); ?></td>
                            <td class="px-4 py-3 text-right">₹ <?php echo number_format((float)($item['current_price'] ?? 0), 2); ?></td>
                            <td class="px-4 py-3 text-gray-600">
                              <div class="flex items-center gap-3">
                                <span><?php echo esc((string)$item['status']); ?></span>
                                <form method="post" class="inline">
                                  <?php echo csrf_token_field(); ?>
                                  <input type="hidden" name="action" value="toggle_goods_status">
                                  <input type="hidden" name="goods_id" value="<?php echo (int)$item['id']; ?>">
                                  <input type="hidden" name="next_status" value="<?php echo (string)$item['status'] === 'archived' ? 'active' : 'archived'; ?>">
                                  <button type="submit" class="text-xs text-rajkot-rust font-bold uppercase tracking-widest"><?php echo (string)$item['status'] === 'archived' ? 'Restore' : 'Archive'; ?></button>
                                </form>
                              </div>
                            </td>
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
