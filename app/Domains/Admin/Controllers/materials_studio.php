<?php

if (!defined('PROJECT_ROOT')) {
    require_once dirname(__DIR__, 4) . '/app/Core/Bootstrap/init.php';
}

require_login();
require_role('admin');

$selfUrl = function_exists('base_path')
    ? base_path('admin/materials_studio.php')
    : rtrim((string)BASE_PATH, '/') . '/admin/materials_studio.php';

if (!function_exists('ms_int')) {
    function ms_int($value, int $default = 0): int
    {
        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return $default;
            }
        }

        return is_numeric($value) ? (int)$value : $default;
    }
}

if (!function_exists('ms_decimal')) {
    function ms_decimal($value, float $default = 0.0): float
    {
        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return $default;
            }
        }

        return is_numeric($value) ? (float)$value : $default;
    }
}

if (!function_exists('ms_list')) {
    function ms_list($value): string
    {
        if (is_array($value)) {
            $value = implode(',', $value);
        }

        $parts = preg_split('/[\r\n,;]+/', trim((string)$value));
        if (!is_array($parts)) {
            return '';
        }

        $clean = [];
        foreach ($parts as $part) {
            $part = trim((string)$part);
            if ($part !== '') {
                $clean[] = $part;
            }
        }

        return implode(', ', array_values(array_unique($clean)));
    }
}

if (!function_exists('ms_code')) {
    function ms_code(string $prefix, string $table, string $column): string
    {
        $prefix = strtoupper(preg_replace('/[^A-Z0-9]+/i', '', $prefix));
        if ($prefix === '') {
            $prefix = 'MS';
        }

        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                $suffix = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            } catch (Throwable $e) {
                $suffix = strtoupper(substr(md5(uniqid('', true)), 0, 6));
            }

            $candidate = $prefix . '-' . date('Ymd') . '-' . $suffix;
            if (!function_exists('db_table_exists') || !db_table_exists($table)) {
                return $candidate;
            }

            $found = db_fetch('SELECT id FROM ' . $table . ' WHERE ' . $column . ' = ? LIMIT 1', [$candidate]);
            if (!$found) {
                return $candidate;
            }
        }

        return $prefix . '-' . date('YmdHis');
    }
}

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
$selectedAssetId = ms_int($_GET['asset_id'] ?? 0);

$vendors = [];
$goods = [];
$goodsById = [];
$priceHistory = [];
$quotes = [];
$assets = [];
$estimateRows = [];
$selectedGoods = null;
$selectedAsset = null;
$overview = [
    'goods' => 0,
    'price_entries' => 0,
    'vendor_quotes' => 0,
    'assets' => 0,
    'estimates' => 0,
];

if (db_connected() && db_table_exists('vendors')) {
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

    if ($action === 'save_asset') {
        $assetId = ms_int($_POST['asset_id'] ?? 0);
        $assetCode = trim((string)($_POST['asset_code'] ?? ''));
        $name = trim((string)($_POST['name'] ?? ''));
        $assetType = trim((string)($_POST['asset_type'] ?? ''));
        $tags = ms_list($_POST['tags'] ?? '');
        $styleTags = ms_list($_POST['style_tags'] ?? '');
        $softwareTargets = ms_list($_POST['software_targets'] ?? '');
        $sourceFile = trim((string)($_POST['source_file'] ?? ''));
        $versionLabel = trim((string)($_POST['version_label'] ?? ''));
        $previewUrl = trim((string)($_POST['preview_url'] ?? ''));
        $licenseNotes = trim((string)($_POST['license_notes'] ?? ''));
        $goodsId = ms_int($_POST['goods_id'] ?? 0);
        $exportFormats = ms_list($_POST['export_formats'] ?? '');
        $status = strtolower(trim((string)($_POST['status'] ?? 'active')));
        if (!in_array($status, ['draft', 'active', 'archived'], true)) {
            $status = 'active';
        }
        $visibility = strtolower(trim((string)($_POST['visibility'] ?? 'public')));
        if (!in_array($visibility, ['private', 'public'], true)) {
            $visibility = 'public';
        }

        if ($name === '') {
            set_flash('Asset name is required.', 'error');
            header('Location: ' . $selfUrl . '?asset_id=' . $assetId);
            exit;
        }

        if ($assetCode === '') {
            $assetCode = ms_code('AST', 'material_assets', 'asset_code');
        }

        try {
            if ($assetId > 0) {
                $stmt = $db->prepare('UPDATE material_assets SET asset_code = ?, name = ?, asset_type = ?, tags = ?, style_tags = ?, software_targets = ?, source_file = ?, version_label = ?, preview_url = ?, license_notes = ?, goods_id = ?, export_formats = ?, status = ?, visibility = ? WHERE id = ? LIMIT 1');
                $stmt->execute([$assetCode, $name, $assetType !== '' ? $assetType : null, $tags !== '' ? $tags : null, $styleTags !== '' ? $styleTags : null, $softwareTargets !== '' ? $softwareTargets : null, $sourceFile !== '' ? $sourceFile : null, $versionLabel !== '' ? $versionLabel : null, $previewUrl !== '' ? $previewUrl : null, $licenseNotes !== '' ? $licenseNotes : null, $goodsId > 0 ? $goodsId : null, $exportFormats !== '' ? $exportFormats : null, $status, $visibility, $assetId]);
            } else {
                $stmt = $db->prepare('INSERT INTO material_assets (asset_code, name, asset_type, tags, style_tags, software_targets, source_file, version_label, preview_url, license_notes, goods_id, export_formats, status, visibility) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$assetCode, $name, $assetType !== '' ? $assetType : null, $tags !== '' ? $tags : null, $styleTags !== '' ? $styleTags : null, $softwareTargets !== '' ? $softwareTargets : null, $sourceFile !== '' ? $sourceFile : null, $versionLabel !== '' ? $versionLabel : null, $previewUrl !== '' ? $previewUrl : null, $licenseNotes !== '' ? $licenseNotes : null, $goodsId > 0 ? $goodsId : null, $exportFormats !== '' ? $exportFormats : null, $status, $visibility]);
                $assetId = (int)$db->lastInsertId();
            }

            set_flash('Asset saved.', 'success');
            header('Location: ' . $selfUrl . '?asset_id=' . $assetId);
            exit;
        } catch (Throwable $e) {
            set_flash('Could not save asset: ' . $e->getMessage(), 'error');
            header('Location: ' . $selfUrl . '?asset_id=' . $assetId);
            exit;
        }
    }

    if ($action === 'toggle_asset_status') {
        $assetId = ms_int($_POST['asset_id'] ?? 0);
        if ($assetId > 0) {
            $nextStatus = trim((string)($_POST['next_status'] ?? 'active'));
            if (!in_array($nextStatus, ['draft', 'active', 'archived'], true)) {
                $nextStatus = 'active';
            }
            db_query('UPDATE material_assets SET status = ? WHERE id = ? LIMIT 1', [$nextStatus, $assetId]);
            set_flash('Asset status updated.', 'success');
        }

        header('Location: ' . $selfUrl . '?asset_id=' . $assetId);
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

    $quotes = db_fetch_all('SELECT q.*, g.name AS goods_name, v.name AS vendor_name FROM material_vendor_quotes q INNER JOIN material_goods g ON g.id = q.goods_id LEFT JOIN vendors v ON v.id = q.vendor_id ORDER BY q.preferred_supplier DESC, q.valid_until DESC, q.last_quote_date DESC, q.id DESC');
    $assets = db_fetch_all('SELECT a.*, g.name AS goods_name FROM material_assets a LEFT JOIN material_goods g ON g.id = a.goods_id ORDER BY a.created_at DESC, a.id DESC');

    $countRow = db_fetch('SELECT COUNT(*) AS total FROM material_price_entries') ?: [];
    $countQuoteRow = db_fetch('SELECT COUNT(*) AS total FROM material_vendor_quotes') ?: [];
    $countEstimateRow = db_table_exists('material_estimates') ? (db_fetch('SELECT COUNT(*) AS total FROM material_estimates') ?: []) : [];

    $overview['goods'] = count($goods);
    $overview['price_entries'] = (int)($countRow['total'] ?? 0);
    $overview['vendor_quotes'] = (int)($countQuoteRow['total'] ?? 0);
    $overview['assets'] = count($assets);
    $overview['estimates'] = (int)($countEstimateRow['total'] ?? 0);

    if ($selectedGoodsId > 0) {
        $selectedGoods = db_fetch('SELECT * FROM material_goods WHERE id = ? LIMIT 1', [$selectedGoodsId]);
        if ($selectedGoods) {
            $priceHistory = db_fetch_all('SELECT pe.*, v.name AS vendor_name FROM material_price_entries pe LEFT JOIN vendors v ON v.id = pe.vendor_id WHERE pe.goods_id = ? ORDER BY COALESCE(pe.effective_at, pe.created_at) DESC, pe.id DESC', [$selectedGoodsId]);
        }
    }

    if ($selectedAssetId > 0) {
        $selectedAsset = db_fetch('SELECT * FROM material_assets WHERE id = ? LIMIT 1', [$selectedAssetId]);
    }

    if (db_table_exists('material_estimates')) {
        $estimateRows = db_fetch_all('SELECT e.id, e.estimate_code, e.title, e.status, e.currency, e.updated_at, v.grand_total AS latest_total FROM material_estimates e LEFT JOIN material_estimate_versions v ON v.id = e.current_version_id ORDER BY e.updated_at DESC, e.id DESC LIMIT 20');
    }
}

  if (!is_array($selectedGoods)) {
    $selectedGoods = null;
  }

  if (!is_array($selectedAsset)) {
    $selectedAsset = null;
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

$assetForm = [
    'asset_id' => $selectedAsset['id'] ?? 0,
    'asset_code' => $selectedAsset['asset_code'] ?? '',
    'name' => $selectedAsset['name'] ?? '',
    'asset_type' => $selectedAsset['asset_type'] ?? '',
    'tags' => $selectedAsset['tags'] ?? '',
    'style_tags' => $selectedAsset['style_tags'] ?? '',
    'software_targets' => $selectedAsset['software_targets'] ?? '',
    'source_file' => $selectedAsset['source_file'] ?? '',
    'version_label' => $selectedAsset['version_label'] ?? '',
    'preview_url' => $selectedAsset['preview_url'] ?? '',
    'license_notes' => $selectedAsset['license_notes'] ?? '',
    'goods_id' => $selectedAsset['goods_id'] ?? '',
    'export_formats' => $selectedAsset['export_formats'] ?? '',
    'status' => $selectedAsset['status'] ?? 'active',
    'visibility' => $selectedAsset['visibility'] ?? 'public',
];

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Materials Studio | Ripal Design</title>
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
            <p class="section-label text-gray-300 mb-2">Administration</p>
            <h1 class="text-3xl md:text-4xl font-serif font-bold">Materials Studio</h1>
            <p class="text-gray-300 mt-3 text-sm max-w-3xl">Manage goods, price history, vendor quotes, and export-ready assets from one rebuilt workspace.</p>
          </div>
          <div class="text-sm text-gray-300">
            <div>Clean rebuild</div>
            <div class="mt-1">Single-page control surface</div>
          </div>
        </div>
      </div>
    </header>

    <main class="flex-grow max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 pb-20">
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 md:gap-6 mb-8 md:mb-12">
        <div class="material-panel p-6 md:p-8">
          <div class="flex items-start justify-between gap-4">
            <div>
              <span class="section-label block mb-2">Goods</span>
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
              <span class="section-label block mb-2">Assets</span>
              <span class="text-2xl md:text-3xl font-serif font-black text-foundation-grey"><?php echo (int)$overview['assets']; ?></span>
            </div>
            <i class="fa-solid fa-cube text-lg text-rajkot-rust"></i>
          </div>
        </div>
        <div class="material-panel p-6 md:p-8">
          <div class="flex items-start justify-between gap-4">
            <div>
              <span class="section-label block mb-2">Estimates</span>
              <span class="text-2xl md:text-3xl font-serif font-black text-foundation-grey"><?php echo (int)$overview['estimates']; ?></span>
            </div>
            <i class="fa-solid fa-file-invoice-dollar text-lg text-rajkot-rust"></i>
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
                      <label class="section-label block mb-2">GST %</label>
                      <input name="gst_rate" type="number" step="0.01" value="<?php echo esc_attr($goodsForm['gst_rate']); ?>" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                    </div>
                    <div>
                      <label class="section-label block mb-2">Wastage %</label>
                      <input name="default_wastage_pct" type="number" step="0.01" value="<?php echo esc_attr($goodsForm['default_wastage_pct']); ?>" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                    </div>
                  </div>
                  <div class="grid grid-cols-2 gap-3">
                    <div>
                      <label class="section-label block mb-2">Labor Model</label>
                      <select name="labor_model" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                        <option value="none" <?php echo $goodsForm['labor_model'] === 'none' ? 'selected' : ''; ?>>None</option>
                        <option value="flat" <?php echo $goodsForm['labor_model'] === 'flat' ? 'selected' : ''; ?>>Flat</option>
                        <option value="per_unit" <?php echo $goodsForm['labor_model'] === 'per_unit' ? 'selected' : ''; ?>>Per Unit</option>
                      </select>
                    </div>
                    <div>
                      <label class="section-label block mb-2">Labor Rate</label>
                      <input name="labor_rate" type="number" step="0.01" value="<?php echo esc_attr($goodsForm['labor_rate']); ?>" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                    </div>
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
                  <button type="submit" class="w-full bg-rajkot-rust text-white px-6 py-3 font-bold uppercase tracking-widest hover:bg-opacity-90 transition-all shadow-lg">Save Goods</button>
                </form>
              </div>

              <div class="lg:col-span-2">
                <div class="overflow-x-auto">
                  <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-widest text-gray-600 border-b">
                      <tr>
                        <th class="px-4 py-3 text-left font-bold">Name</th>
                        <th class="px-4 py-3 text-left font-bold">SKU</th>
                        <th class="px-4 py-3 text-left font-bold">GST</th>
                        <th class="px-4 py-3 text-left font-bold">Current Price</th>
                        <th class="px-4 py-3 text-left font-bold">Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (empty($goods)): ?>
                        <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">No goods defined yet.</td></tr>
                      <?php else: ?>
                        <?php foreach ($goods as $row): ?>
                          <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-3 font-semibold"><a href="<?php echo esc_attr($selfUrl . '?goods_id=' . (int)$row['id']); ?>" class="text-rajkot-rust hover:underline"><?php echo esc($row['name']); ?></a></td>
                            <td class="px-4 py-3 text-gray-600"><?php echo esc((string)$row['sku']); ?></td>
                            <td class="px-4 py-3"><?php echo number_format((float)$row['gst_rate'], 1); ?>%</td>
                            <td class="px-4 py-3"><?php echo !empty($row['current_price']) ? '₹' . number_format((float)$row['current_price'], 2) : '—'; ?></td>
                            <td class="px-4 py-3 text-gray-600">
                              <div class="flex items-center gap-3">
                                <span><?php echo esc((string)$row['status']); ?></span>
                                <form method="post" class="inline" onsubmit="return confirm('<?php echo (string)$row['status'] === 'archived' ? 'Restore' : 'Archive'; ?> this item?');">
                                  <?php echo csrf_token_field(); ?>
                                  <input type="hidden" name="action" value="toggle_goods_status">
                                  <input type="hidden" name="goods_id" value="<?php echo (int)$row['id']; ?>">
                                  <input type="hidden" name="next_status" value="<?php echo (string)$row['status'] === 'archived' ? 'active' : 'archived'; ?>">
                                  <button type="submit" class="text-xs text-rajkot-rust font-bold uppercase tracking-widest"><?php echo (string)$row['status'] === 'archived' ? 'Restore' : 'Archive'; ?></button>
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

          <section class="material-panel p-6 md:p-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
              <div>
                <div class="mb-6">
                  <div class="section-label mb-2">Price History</div>
                  <h2 class="text-2xl font-serif font-bold mb-1">Track prices by goods item</h2>
                  <p class="text-gray-600 text-sm">Add dated price entries or vendor-specific quotes so the latest valid rate is always visible.</p>
                </div>

                <form method="post" class="space-y-4 bg-gray-50 p-5 border border-gray-100">
                  <?php echo csrf_token_field(); ?>
                  <input type="hidden" name="action" value="save_price_entry">
                  <div>
                    <label class="section-label block mb-2">Goods Item *</label>
                    <select name="goods_id" required class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                      <option value="">-- select goods --</option>
                      <?php foreach ($goods as $good): ?>
                        <option value="<?php echo (int)$good['id']; ?>" <?php echo (int)$good['id'] === $selectedGoodsId ? 'selected' : ''; ?>><?php echo esc($good['name']); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div>
                    <label class="section-label block mb-2">Price *</label>
                    <input name="price" type="number" step="0.01" required class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                  </div>
                  <div class="grid grid-cols-2 gap-3">
                    <div>
                      <label class="section-label block mb-2">Vendor</label>
                      <select name="vendor_id" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                        <option value="">-- direct --</option>
                        <?php foreach ($vendors as $vendor): ?><option value="<?php echo (int)$vendor['id']; ?>"><?php echo esc($vendor['name']); ?></option><?php endforeach; ?>
                      </select>
                    </div>
                    <div>
                      <label class="section-label block mb-2">Type</label>
                      <select name="quote_type" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                        <option value="quote">Quote</option>
                        <option value="purchase">Purchase</option>
                        <option value="actual_closeout">Actual Closeout</option>
                      </select>
                    </div>
                  </div>
                  <div>
                    <label class="section-label block mb-2">Effective Date</label>
                    <input name="effective_at" type="datetime-local" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                  </div>
                  <div>
                    <label class="section-label block mb-2">Source Note</label>
                    <input name="source_note" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                  </div>
                  <button type="submit" class="w-full bg-foundation-grey text-white px-6 py-3 font-bold uppercase tracking-widest hover:bg-opacity-90 transition-all">Add Entry</button>
                </form>
              </div>

              <div>
                <div class="mb-6">
                  <div class="section-label mb-2">Latest History</div>
                  <h3 class="text-2xl font-serif font-bold mb-1"><?php echo $selectedGoods ? esc($selectedGoods['name']) : 'Select a goods item'; ?></h3>
                  <p class="text-gray-600 text-sm">Most recent entries for the selected material.</p>
                </div>

                <?php if ($selectedGoods && !empty($priceHistory)): ?>
                  <div class="space-y-3 max-h-[32rem] overflow-y-auto pr-1">
                    <?php foreach ($priceHistory as $entry): ?>
                      <div class="bg-gray-50 border border-gray-100 p-3 text-sm">
                        <div class="font-semibold text-rajkot-rust">₹ <?php echo number_format((float)$entry['price'], 2); ?></div>
                        <div class="text-xs text-gray-600 mt-1"><?php echo esc((string)($entry['vendor_name'] ?? 'Direct')); ?> · <?php echo esc((string)($entry['quote_type'] ?? '')); ?></div>
                        <div class="text-xs text-gray-500 mt-1"><?php echo esc((string)($entry['effective_at'] ?? 'Today')); ?></div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <p class="text-gray-600 text-sm">Select a goods item to view its price history.</p>
                <?php endif; ?>
              </div>
            </div>
          </section>

          <section class="material-panel p-6 md:p-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
              <div class="lg:col-span-1">
                <div class="mb-6">
                  <div class="section-label mb-2">Vendor Quotes</div>
                  <h2 class="text-2xl font-serif font-bold mb-1">Compare suppliers</h2>
                  <p class="text-gray-600 text-sm">Attach vendor pricing to a goods item and keep a date-stamped history.</p>
                </div>

                <form method="post" class="space-y-4 bg-gray-50 p-5 border border-gray-100">
                  <?php echo csrf_token_field(); ?>
                  <input type="hidden" name="action" value="save_vendor_quote">
                  <div>
                    <label class="section-label block mb-2">Goods Item *</label>
                    <select name="goods_id" required class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                      <option value="">-- select --</option>
                      <?php foreach ($goods as $good): ?>
                        <option value="<?php echo (int)$good['id']; ?>" <?php echo (int)$good['id'] === $selectedGoodsId ? 'selected' : ''; ?>><?php echo esc($good['name']); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div>
                    <label class="section-label block mb-2">Vendor *</label>
                    <select name="vendor_id" required class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                      <option value="">-- select --</option>
                      <?php foreach ($vendors as $vendor): ?><option value="<?php echo (int)$vendor['id']; ?>"><?php echo esc($vendor['name']); ?></option><?php endforeach; ?>
                    </select>
                  </div>
                  <div>
                    <label class="section-label block mb-2">Quoted Price *</label>
                    <input name="quoted_price" type="number" step="0.01" required class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                  </div>
                  <div class="grid grid-cols-2 gap-3">
                    <div>
                      <label class="section-label block mb-2">MOQ</label>
                      <input name="minimum_order_qty" type="number" step="0.001" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                    </div>
                    <div>
                      <label class="section-label block mb-2">Lead Time</label>
                      <input name="lead_time_days" type="number" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                    </div>
                  </div>
                  <div>
                    <label class="section-label block mb-2">Last Quote Date</label>
                    <input name="last_quote_date" type="date" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                  </div>
                  <div>
                    <label class="section-label block mb-2">Valid Until</label>
                    <input name="valid_until" type="date" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                  </div>
                  <div class="flex items-center gap-2">
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
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (empty($quotes)): ?>
                        <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">No vendor quotes yet.</td></tr>
                      <?php else: ?>
                        <?php foreach ($quotes as $quote): ?>
                          <tr class="border-t">
                            <td class="px-4 py-3"><?php echo esc($quote['goods_name']); ?></td>
                            <td class="px-4 py-3"><?php echo esc($quote['vendor_name']); ?></td>
                            <td class="px-4 py-3 text-right">₹ <?php echo number_format((float)$quote['quoted_price'], 2); ?></td>
                            <td class="px-4 py-3 text-right"><?php echo number_format((float)$quote['minimum_order_qty'], 2); ?></td>
                            <td class="px-4 py-3 text-right"><?php echo (int)$quote['lead_time_days']; ?>d</td>
                          </tr>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </section>

          <section class="material-panel p-6 md:p-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
              <div class="lg:col-span-1">
                <div class="mb-6">
                  <div class="section-label mb-2">Asset Catalogue</div>
                  <h2 class="text-2xl font-serif font-bold mb-1">3D and design assets</h2>
                  <p class="text-gray-600 text-sm">Track export formats, source files, and links back to goods items.</p>
                </div>

                <form method="post" class="space-y-4 bg-gray-50 p-5 border border-gray-100">
                  <?php echo csrf_token_field(); ?>
                  <input type="hidden" name="action" value="save_asset">
                  <input type="hidden" name="asset_id" value="<?php echo (int)$assetForm['asset_id']; ?>">
                  <div>
                    <label class="section-label block mb-2">Asset Code</label>
                    <input name="asset_code" value="<?php echo esc_attr($assetForm['asset_code']); ?>" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust" placeholder="Auto-generated if empty">
                  </div>
                  <div>
                    <label class="section-label block mb-2">Name *</label>
                    <input name="name" value="<?php echo esc_attr($assetForm['name']); ?>" required class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                  </div>
                  <div>
                    <label class="section-label block mb-2">Asset Type</label>
                    <input name="asset_type" value="<?php echo esc_attr($assetForm['asset_type']); ?>" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust" placeholder="3D Model, SVG, Texture">
                  </div>
                  <div>
                    <label class="section-label block mb-2">Linked Goods</label>
                    <select name="goods_id" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                      <option value="">-- optional --</option>
                      <?php foreach ($goods as $good): ?>
                        <option value="<?php echo (int)$good['id']; ?>" <?php echo (string)$assetForm['goods_id'] === (string)$good['id'] ? 'selected' : ''; ?>><?php echo esc($good['name']); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div>
                    <label class="section-label block mb-2">Export Formats</label>
                    <input name="export_formats" value="<?php echo esc_attr($assetForm['export_formats']); ?>" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust" placeholder="BLEND, FBX, GLB, SVG">
                  </div>
                  <div>
                    <label class="section-label block mb-2">Tags</label>
                    <input name="tags" value="<?php echo esc_attr($assetForm['tags']); ?>" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust" placeholder="tag, tag">
                  </div>
                  <div>
                    <label class="section-label block mb-2">Style Tags</label>
                    <input name="style_tags" value="<?php echo esc_attr($assetForm['style_tags']); ?>" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                  </div>
                  <div>
                    <label class="section-label block mb-2">Software Targets</label>
                    <input name="software_targets" value="<?php echo esc_attr($assetForm['software_targets']); ?>" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust" placeholder="Revit, Lumion, D5">
                  </div>
                  <div class="grid grid-cols-2 gap-3">
                    <div>
                      <label class="section-label block mb-2">Status</label>
                      <select name="status" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                        <option value="draft" <?php echo $assetForm['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="active" <?php echo $assetForm['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="archived" <?php echo $assetForm['status'] === 'archived' ? 'selected' : ''; ?>>Archived</option>
                      </select>
                    </div>
                    <div>
                      <label class="section-label block mb-2">Visibility</label>
                      <select name="visibility" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                        <option value="public" <?php echo $assetForm['visibility'] === 'public' ? 'selected' : ''; ?>>Public</option>
                        <option value="private" <?php echo $assetForm['visibility'] === 'private' ? 'selected' : ''; ?>>Private</option>
                      </select>
                    </div>
                  </div>
                  <div>
                    <label class="section-label block mb-2">Source File</label>
                    <input name="source_file" value="<?php echo esc_attr($assetForm['source_file']); ?>" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                  </div>
                  <div>
                    <label class="section-label block mb-2">Version Label</label>
                    <input name="version_label" value="<?php echo esc_attr($assetForm['version_label']); ?>" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                  </div>
                  <div>
                    <label class="section-label block mb-2">Preview URL</label>
                    <input name="preview_url" value="<?php echo esc_attr($assetForm['preview_url']); ?>" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust">
                  </div>
                  <div>
                    <label class="section-label block mb-2">License Notes</label>
                    <textarea name="license_notes" rows="3" class="w-full p-3 border border-gray-200 focus:outline-none focus:border-rajkot-rust"><?php echo esc($assetForm['license_notes']); ?></textarea>
                  </div>
                  <button type="submit" class="w-full bg-foundation-grey text-white px-6 py-3 font-bold uppercase tracking-widest hover:bg-opacity-90 transition-all">Save Asset</button>
                </form>
              </div>

              <div class="lg:col-span-2">
                <div class="overflow-x-auto">
                  <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs uppercase tracking-widest text-gray-600 border-b">
                      <tr>
                        <th class="px-4 py-3 text-left">Name</th>
                        <th class="px-4 py-3 text-left">Type</th>
                        <th class="px-4 py-3 text-left">Formats</th>
                        <th class="px-4 py-3 text-left">Linked Goods</th>
                        <th class="px-4 py-3 text-left">Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (empty($assets)): ?>
                        <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">No assets yet.</td></tr>
                      <?php else: ?>
                        <?php foreach ($assets as $asset): ?>
                          <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-3 font-semibold"><a href="<?php echo esc_attr($selfUrl . '?asset_id=' . (int)$asset['id']); ?>" class="text-rajkot-rust"><?php echo esc($asset['name']); ?></a></td>
                            <td class="px-4 py-3 text-gray-600"><?php echo esc((string)($asset['asset_type'] ?? '—')); ?></td>
                            <td class="px-4 py-3 text-gray-600 text-xs"><?php echo esc((string)($asset['export_formats'] ?? '—')); ?></td>
                            <td class="px-4 py-3"><?php echo !empty($asset['goods_name']) ? esc($asset['goods_name']) : '—'; ?></td>
                            <td class="px-4 py-3 text-gray-600">
                              <div class="flex items-center gap-3">
                                <span><?php echo esc((string)$asset['status']); ?></span>
                                <form method="post" class="inline">
                                  <?php echo csrf_token_field(); ?>
                                  <input type="hidden" name="action" value="toggle_asset_status">
                                  <input type="hidden" name="asset_id" value="<?php echo (int)$asset['id']; ?>">
                                  <input type="hidden" name="next_status" value="<?php echo (string)$asset['status'] === 'archived' ? 'active' : 'archived'; ?>">
                                  <button type="submit" class="text-xs text-rajkot-rust font-bold uppercase tracking-widest"><?php echo (string)$asset['status'] === 'archived' ? 'Restore' : 'Archive'; ?></button>
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

          <section class="material-panel p-6 md:p-8">
            <div class="mb-6">
              <div class="section-label mb-2">Estimate Overview</div>
              <h2 class="text-2xl font-serif font-bold mb-1">Latest estimate snapshots</h2>
              <p class="text-gray-600 text-sm">Read-only list of estimate records already stored in the platform.</p>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-widest text-gray-600 border-b">
                  <tr>
                    <th class="px-4 py-3 text-left">Code</th>
                    <th class="px-4 py-3 text-left">Title</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-right">Total</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($estimateRows)): ?>
                    <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">No estimates found.</td></tr>
                  <?php else: ?>
                    <?php foreach ($estimateRows as $estimate): ?>
                      <tr class="border-t">
                        <td class="px-4 py-3 font-semibold"><?php echo esc((string)$estimate['estimate_code']); ?></td>
                        <td class="px-4 py-3"><?php echo esc((string)$estimate['title']); ?></td>
                        <td class="px-4 py-3"><?php echo esc((string)$estimate['status']); ?></td>
                        <td class="px-4 py-3 text-right">₹ <?php echo number_format((float)($estimate['latest_total'] ?? 0), 2); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </section>
        </div>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>
