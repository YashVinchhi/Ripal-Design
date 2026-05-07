<?php

if (!defined('PROJECT_ROOT')) {
    require_once dirname(__DIR__, 4) . '/app/Core/Bootstrap/init.php';
}

require_login();
require_role('admin');

$selfUrl = function_exists('base_path')
    ? base_path('admin/materials_studio.php')
    : rtrim((string)BASE_PATH, '/') . '/admin/materials_studio.php';

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

$overview = [
    'goods' => 0,
    'price_entries' => 0,
    'vendor_quotes' => 0,
    'assets' => 0,
    'estimates' => 0,
];

if (db_connected() && $schemaReady) {
    $countRow = db_fetch('SELECT COUNT(*) AS total FROM material_goods') ?: [];
    $countPriceRow = db_fetch('SELECT COUNT(*) AS total FROM material_price_entries') ?: [];
    $countQuoteRow = db_fetch('SELECT COUNT(*) AS total FROM material_vendor_quotes') ?: [];
    $countAssetRow = db_fetch('SELECT COUNT(*) AS total FROM material_assets') ?: [];
    $countEstimateRow = db_table_exists('material_estimates') ? (db_fetch('SELECT COUNT(*) AS total FROM material_estimates') ?: []) : [];

    $overview['goods'] = (int)($countRow['total'] ?? 0);
    $overview['price_entries'] = (int)($countPriceRow['total'] ?? 0);
    $overview['vendor_quotes'] = (int)($countQuoteRow['total'] ?? 0);
    $overview['assets'] = (int)($countAssetRow['total'] ?? 0);
    $overview['estimates'] = (int)($countEstimateRow['total'] ?? 0);
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Materials Studio Hub | Ripal Design</title>
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
      border-radius: 0.375rem;
      transition: all 0.3s ease;
    }
    .material-panel:hover {
      border-color: #c65911;
      box-shadow: 0 25px 50px rgba(198, 89, 17, 0.1);
    }
    .section-label {
      font-size: 0.7rem;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: #64748b;
      font-weight: 700;
    }
    .module-card {
      display: flex;
      flex-direction: column;
      height: 100%;
    }
    .module-card-header {
      padding: 1.5rem;
      border-bottom: 1px solid rgba(15, 23, 42, 0.08);
      flex-grow: 1;
    }
    .module-card-footer {
      padding: 1.5rem;
      background: rgba(15, 23, 42, 0.02);
    }
    .module-stat {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.75rem 0;
      border-bottom: 1px solid rgba(15, 23, 42, 0.05);
    }
    .module-stat:last-child {
      border-bottom: none;
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
            <p class="text-gray-300 mt-3 text-sm max-w-3xl">Manage goods, pricing, vendors, assets, and estimates across five specialized modules.</p>
          </div>
          <div class="text-sm text-gray-300">
            <div>Modular platform</div>
            <div class="mt-1">Five independent workspaces</div>
          </div>
        </div>
      </div>
    </header>

    <main class="flex-grow max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 pb-20">
      <?php if (!$schemaReady): ?>
        <div class="mb-8 bg-yellow-50 border border-yellow-200 text-yellow-900 px-4 py-3 rounded-sm text-sm font-bold">
          Run sql/migrations/20260502_materials_platform.sql to initialize the Materials Studio schema.
        </div>
      <?php endif; ?>

      <div class="material-grid mb-8">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 md:gap-6">
          <!-- Overview Stats -->
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
                <span class="section-label block mb-2">Prices</span>
                <span class="text-2xl md:text-3xl font-serif font-black text-foundation-grey"><?php echo (int)$overview['price_entries']; ?></span>
              </div>
              <i class="fa-solid fa-chart-line text-lg text-rajkot-rust"></i>
            </div>
          </div>
          <div class="material-panel p-6 md:p-8">
            <div class="flex items-start justify-between gap-4">
              <div>
                <span class="section-label block mb-2">Quotes</span>
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
      </div>

      <div class="material-grid">
        <h2 class="text-2xl font-serif font-bold mb-6">Modules</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <!-- Goods Management -->
          <div class="material-panel module-card">
            <div class="module-card-header">
              <div class="flex items-start gap-3 mb-4">
                <i class="fa-solid fa-box text-2xl text-rajkot-rust"></i>
                <div>
                  <div class="section-label mb-1">Module</div>
                  <h3 class="text-lg font-serif font-bold">Goods Management</h3>
                </div>
              </div>
              <p class="text-gray-600 text-sm leading-relaxed">Define reusable material items with unit conversion, GST rates, wastage factors, and labor rules for estimate precision.</p>
            </div>
            <div class="module-card-footer">
              <div class="module-stat">
                <span class="text-gray-600">Items</span>
                <span class="font-bold"><?php echo (int)$overview['goods']; ?></span>
              </div>
              <a href="<?php echo base_path('admin/goods_management.php'); ?>" class="block mt-4 px-4 py-2 bg-foundation-grey text-white text-center font-bold uppercase tracking-widest hover:bg-opacity-90 transition-all text-sm">Open Module</a>
            </div>
          </div>

          <!-- Price History -->
          <div class="material-panel module-card">
            <div class="module-card-header">
              <div class="flex items-start gap-3 mb-4">
                <i class="fa-solid fa-chart-line text-2xl text-rajkot-rust"></i>
                <div>
                  <div class="section-label mb-1">Module</div>
                  <h3 class="text-lg font-serif font-bold">Price History</h3>
                </div>
              </div>
              <p class="text-gray-600 text-sm leading-relaxed">Track pricing evolution for each goods item using append-only history. Keep full traceability of price changes.</p>
            </div>
            <div class="module-card-footer">
              <div class="module-stat">
                <span class="text-gray-600">Entries</span>
                <span class="font-bold"><?php echo (int)$overview['price_entries']; ?></span>
              </div>
              <a href="<?php echo base_path('admin/price_history.php'); ?>" class="block mt-4 px-4 py-2 bg-foundation-grey text-white text-center font-bold uppercase tracking-widest hover:bg-opacity-90 transition-all text-sm">Open Module</a>
            </div>
          </div>

          <!-- Vendor Quotes -->
          <div class="material-panel module-card">
            <div class="module-card-header">
              <div class="flex items-start gap-3 mb-4">
                <i class="fa-solid fa-handshake text-2xl text-rajkot-rust"></i>
                <div>
                  <div class="section-label mb-1">Module</div>
                  <h3 class="text-lg font-serif font-bold">Vendor Quotes</h3>
                </div>
              </div>
              <p class="text-gray-600 text-sm leading-relaxed">Compare pricing, MOQ, and lead times across suppliers. Store vendor preferences separately from purchase history.</p>
            </div>
            <div class="module-card-footer">
              <div class="module-stat">
                <span class="text-gray-600">Quotes</span>
                <span class="font-bold"><?php echo (int)$overview['vendor_quotes']; ?></span>
              </div>
              <a href="<?php echo base_path('admin/vendor_quotes.php'); ?>" class="block mt-4 px-4 py-2 bg-foundation-grey text-white text-center font-bold uppercase tracking-widest hover:bg-opacity-90 transition-all text-sm">Open Module</a>
            </div>
          </div>

          <!-- Asset Catalogue -->
          <div class="material-panel module-card">
            <div class="module-card-header">
              <div class="flex items-start gap-3 mb-4">
                <i class="fa-solid fa-cube text-2xl text-rajkot-rust"></i>
                <div>
                  <div class="section-label mb-1">Module</div>
                  <h3 class="text-lg font-serif font-bold">Asset Catalogue</h3>
                </div>
              </div>
              <p class="text-gray-600 text-sm leading-relaxed">Store 3D models, design assets, and export-ready formats with links to material goods. Support browser-based delivery.</p>
            </div>
            <div class="module-card-footer">
              <div class="module-stat">
                <span class="text-gray-600">Assets</span>
                <span class="font-bold"><?php echo (int)$overview['assets']; ?></span>
              </div>
              <a href="<?php echo base_path('admin/asset_catalogue.php'); ?>" class="block mt-4 px-4 py-2 bg-foundation-grey text-white text-center font-bold uppercase tracking-widest hover:bg-opacity-90 transition-all text-sm">Open Module</a>
            </div>
          </div>

          <!-- Estimates Overview -->
          <div class="material-panel module-card">
            <div class="module-card-header">
              <div class="flex items-start gap-3 mb-4">
                <i class="fa-solid fa-file-invoice-dollar text-2xl text-rajkot-rust"></i>
                <div>
                  <div class="section-label mb-1">Module</div>
                  <h3 class="text-lg font-serif font-bold">Estimates Overview</h3>
                </div>
              </div>
              <p class="text-gray-600 text-sm leading-relaxed">Read-only view of estimate records created using materials data. See snapshots and latest estimate versions.</p>
            </div>
            <div class="module-card-footer">
              <div class="module-stat">
                <span class="text-gray-600">Estimates</span>
                <span class="font-bold"><?php echo (int)$overview['estimates']; ?></span>
              </div>
              <a href="<?php echo base_path('admin/estimates_overview.php'); ?>" class="block mt-4 px-4 py-2 bg-foundation-grey text-white text-center font-bold uppercase tracking-widest hover:bg-opacity-90 transition-all text-sm">Open Module</a>
            </div>
          </div>

          <!-- Documentation -->
          <div class="material-panel module-card" style="background: rgba(198, 89, 17, 0.05);">
            <div class="module-card-header">
              <div class="flex items-start gap-3 mb-4">
                <i class="fa-solid fa-book text-2xl text-rajkot-rust"></i>
                <div>
                  <div class="section-label mb-1">Reference</div>
                  <h3 class="text-lg font-serif font-bold">Platform Guide</h3>
                </div>
              </div>
              <p class="text-gray-600 text-sm leading-relaxed">Read the complete platform architecture, design principles, and feature roadmap for materials and goods management.</p>
            </div>
            <div class="module-card-footer">
              <a href="<?php echo base_path('docs/goods-and-asset-platform-plan.md'); ?>" target="_blank" class="block px-4 py-2 bg-rajkot-rust text-white text-center font-bold uppercase tracking-widest hover:bg-opacity-90 transition-all text-sm">Read Docs</a>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
