<?php

if (!defined('PROJECT_ROOT')) {
    require_once dirname(__DIR__, 4) . '/app/Core/Bootstrap/init.php';
}

require_login();
require_role('admin');

require_once PROJECT_ROOT . '/app/Shared/Materials/MaterialsHelpers.php';

$selfUrl = function_exists('base_path')
    ? base_path('admin/asset_catalogue.php')
    : rtrim((string)BASE_PATH, '/') . '/admin/asset_catalogue.php';

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
$selectedAssetId = ms_int($_GET['asset_id'] ?? 0);

$goods = [];
$assets = [];
$selectedAsset = null;
$overview = [
    'goods' => 0,
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
    $goods = db_fetch_all("SELECT g.* FROM material_goods g ORDER BY g.status = 'active' DESC, g.name ASC");
    $assets = db_fetch_all('SELECT a.*, g.name AS goods_name FROM material_assets a LEFT JOIN material_goods g ON g.id = a.goods_id ORDER BY a.created_at DESC, a.id DESC');

    $countRow = db_fetch('SELECT COUNT(*) AS total FROM material_goods') ?: [];
    $countAssetRow = db_fetch('SELECT COUNT(*) AS total FROM material_assets') ?: [];

    $overview['goods'] = (int)($countRow['total'] ?? 0);
    $overview['assets'] = (int)($countAssetRow['total'] ?? 0);

    if ($selectedAssetId > 0) {
        $selectedAsset = db_fetch('SELECT * FROM material_assets WHERE id = ? LIMIT 1', [$selectedAssetId]);
    }
}

if (!is_array($selectedAsset)) {
    $selectedAsset = null;
}

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
  <title>Asset Catalogue | Materials Studio | Ripal Design</title>
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
            <h1 class="text-3xl md:text-4xl font-serif font-bold">Asset Catalogue</h1>
            <p class="text-gray-300 mt-3 text-sm max-w-3xl">Store 3D models, design assets, and export-ready formats with links to material goods.</p>
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
              <span class="section-label block mb-2">Assets</span>
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
                <div class="section-label mb-2">3D and Design Assets</div>
                <h2 class="text-2xl font-serif font-bold mb-1">Digital asset management</h2>
                <p class="text-gray-600 text-sm">Track export formats, source files, and links back to goods items. Support browser-based exports.</p>
              </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
              <div>
                <form method="post" class="space-y-4">
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
        </div>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>
