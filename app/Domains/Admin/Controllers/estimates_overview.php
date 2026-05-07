<?php

if (!defined('PROJECT_ROOT')) {
    require_once dirname(__DIR__, 4) . '/app/Core/Bootstrap/init.php';
}

require_login();
require_role('admin');

$selfUrl = function_exists('base_path')
    ? base_path('admin/estimates_overview.php')
    : rtrim((string)BASE_PATH, '/') . '/admin/estimates_overview.php';

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

$estimateRows = [];
$overview = [
    'goods' => 0,
    'estimates' => 0,
];

if (db_connected() && $schemaReady) {
    $countRow = db_fetch('SELECT COUNT(*) AS total FROM material_goods') ?: [];
    $overview['goods'] = (int)($countRow['total'] ?? 0);

    if (db_table_exists('material_estimates')) {
        $estimateRows = db_fetch_all('SELECT e.id, e.estimate_code, e.title, e.status, e.currency, e.updated_at, v.grand_total AS latest_total FROM material_estimates e LEFT JOIN material_estimate_versions v ON v.id = e.current_version_id ORDER BY e.updated_at DESC, e.id DESC LIMIT 50');
        $countEstimateRow = db_fetch('SELECT COUNT(*) AS total FROM material_estimates') ?: [];
        $overview['estimates'] = (int)($countEstimateRow['total'] ?? 0);
    }
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Estimates Overview | Materials Studio | Ripal Design</title>
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
            <h1 class="text-3xl md:text-4xl font-serif font-bold">Estimates Overview</h1>
            <p class="text-gray-300 mt-3 text-sm max-w-3xl">Read-only list of estimate records and versions created with materials data.</p>
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
            <div class="mb-6">
              <div class="section-label mb-2">Estimate Snapshots</div>
              <h2 class="text-2xl font-serif font-bold mb-1">Latest estimate records</h2>
              <p class="text-gray-600 text-sm">View all estimates created using the materials and goods platform. This is a read-only reference view.</p>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase tracking-widest text-gray-600 border-b">
                  <tr>
                    <th class="px-4 py-3 text-left">Code</th>
                    <th class="px-4 py-3 text-left">Title</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-right">Total</th>
                    <th class="px-4 py-3 text-left">Currency</th>
                    <th class="px-4 py-3 text-left">Updated</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($estimateRows)): ?>
                    <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">No estimates found.</td></tr>
                  <?php else: ?>
                    <?php foreach ($estimateRows as $estimate): ?>
                      <tr class="border-t hover:bg-gray-50">
                        <td class="px-4 py-3 font-semibold"><?php echo esc((string)$estimate['estimate_code']); ?></td>
                        <td class="px-4 py-3"><?php echo esc((string)$estimate['title']); ?></td>
                        <td class="px-4 py-3"><?php echo esc((string)$estimate['status']); ?></td>
                        <td class="px-4 py-3 text-right font-semibold">₹ <?php echo number_format((float)($estimate['latest_total'] ?? 0), 2); ?></td>
                        <td class="px-4 py-3 text-xs"><?php echo esc((string)$estimate['currency']); ?></td>
                        <td class="px-4 py-3 text-xs text-gray-500"><?php echo esc((string)$estimate['updated_at']); ?></td>
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
