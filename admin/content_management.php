<?php
require_once __DIR__ . '/../includes/init.php';
require_login();
require_role('admin');

$registry = public_content_registry();
$pages = public_content_admin_pages();
$defaultPage = $pages[0]['slug'] ?? '';
$activePage = strtolower(trim((string)($_GET['page'] ?? $defaultPage)));
if ($activePage === '' || !isset($registry[$activePage])) {
    $activePage = $defaultPage;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $action = strtolower(trim((string)($_POST['action'] ?? 'save')));
    $postedPage = strtolower(trim((string)($_POST['page_slug'] ?? $activePage)));
    if ($postedPage === '' || !isset($registry[$postedPage])) {
        $postedPage = $defaultPage;
    }

    if ($action === 'seed_defaults') {
        $seed = public_content_seed_defaults(current_user_id());
        if (!empty($seed['ok'])) {
            set_flash(
                'Default seed completed. Inserted missing fields: ' . (int)($seed['seeded'] ?? 0) . '. Already present: ' . (int)($seed['skipped'] ?? 0),
                'success'
            );
        } else {
            $errors = implode(' ', array_map('strval', $seed['errors'] ?? []));
            set_flash(
                'Default seed completed with issues. Inserted missing fields: ' . (int)($seed['seeded'] ?? 0) . '. ' . $errors,
                'warning'
            );
        }

        header('Location: ' . base_path('admin/content_management.php?page=' . rawurlencode($postedPage)));
        exit;
    }

    if ($postedPage === '' || !isset($registry[$postedPage])) {
        set_flash('Invalid content page selected.', 'danger');
        header('Location: ' . base_path('admin/content_management.php'));
        exit;
    }

    $payload = $_POST['content'][$postedPage] ?? [];
    if (!is_array($payload)) {
        $payload = [];
    }

    $save = public_content_upsert_page($postedPage, $payload, current_user_id());
    if (!empty($save['ok'])) {
        set_flash('Content updated successfully. Saved fields: ' . (int)($save['saved'] ?? 0), 'success');
    } else {
        $errors = implode(' ', array_map('strval', $save['errors'] ?? []));
        set_flash('Update completed with issues. ' . $errors, 'warning');
    }

    header('Location: ' . base_path('admin/content_management.php?page=' . rawurlencode($postedPage)));
    exit;
}

$currentMeta = $registry[$activePage] ?? ['fields' => [], 'title' => 'Content'];
$currentFields = $currentMeta['fields'] ?? [];
$currentValues = public_content_page_values($activePage);
$previewPath = (string)($currentMeta['preview_path'] ?? '');
?>
<!DOCTYPE html>
<html lang="en" class="bg-canvas-white">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Content Manager | Ripal Design</title>
  <?php $HEADER_MODE = 'dashboard'; require_once __DIR__ . '/../Common/header.php'; ?>
</head>
<body class="bg-canvas-white font-sans text-foundation-grey min-h-screen">

<div class="min-h-screen flex flex-col">
  <header class="bg-foundation-grey text-white pt-20 md:pt-24 pb-8 md:pb-12 px-4 sm:px-6 lg:px-8 shadow-lg mb-8 md:mb-12 border-b-2 border-rajkot-rust">
    <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-6">
      <div>
        <h1 class="text-3xl md:text-4xl font-serif font-bold">Content Manager</h1>
        <p class="text-gray-400 mt-2 text-sm uppercase tracking-widest font-bold opacity-70">Admin-only Public Text Control Center</p>
      </div>
      <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
        <?php if ($previewPath !== ''): ?>
          <a href="<?php echo esc_attr(base_path($previewPath)); ?>" target="_blank" rel="noopener noreferrer" class="w-full md:w-auto bg-white/10 hover:bg-white/20 text-white border border-white/20 px-6 py-3 text-[10px] font-bold uppercase tracking-widest transition-all text-center no-underline">
            Preview Page
          </a>
        <?php endif; ?>
        <a href="<?php echo esc_attr(base_path('admin/dashboard.php')); ?>" class="w-full md:w-auto bg-rajkot-rust hover:bg-red-700 text-white px-6 py-3 text-[10px] font-bold uppercase tracking-widest transition-all text-center no-underline">
          Back to Admin
        </a>
      </div>
    </div>
  </header>

  <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-10">
    <div class="mb-6">
      <?php render_flash(); ?>
    </div>

    <section class="bg-white shadow-premium border border-gray-100 p-6 md:p-8 mb-8">
      <h2 class="text-xl md:text-2xl font-serif font-bold mb-5">Page Groups</h2>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <?php foreach ($pages as $page): ?>
          <?php
            $slug = (string)($page['slug'] ?? '');
            $title = (string)($page['title'] ?? $slug);
            $isActive = $slug === $activePage;
          ?>
          <a href="<?php echo esc_attr(base_path('admin/content_management.php?page=' . rawurlencode($slug))); ?>" class="no-underline px-4 py-3 border text-sm font-bold transition-all <?php echo $isActive ? 'bg-foundation-grey text-white border-foundation-grey' : 'bg-gray-50 text-foundation-grey border-gray-100 hover:border-rajkot-rust'; ?>">
            <?php echo esc($title); ?>
          </a>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="bg-white shadow-premium border border-gray-100 p-6 md:p-8">
      <div class="mb-6">
        <h2 class="text-xl md:text-2xl font-serif font-bold"><?php echo esc((string)($currentMeta['title'] ?? 'Content')); ?></h2>
        <p class="text-sm text-gray-500 mt-2">Update text values and save. Plain fields render as escaped text. HTML fields allow a small safe subset of tags.</p>
      </div>

      <form method="post" class="space-y-6">
        <?php echo csrf_token_field(); ?>
        <input type="hidden" name="page_slug" value="<?php echo esc_attr($activePage); ?>">

        <?php foreach ($currentFields as $key => $meta): ?>
          <?php
            $fieldKey = (string)$key;
            $label = (string)($meta['label'] ?? $fieldKey);
            $format = strtolower((string)($meta['format'] ?? 'plain')) === 'html' ? 'html' : 'plain';
            $default = (string)($meta['default'] ?? '');
            $value = (string)($currentValues[$fieldKey] ?? $default);
          ?>
          <div class="border border-gray-100 p-4 md:p-5 bg-gray-50">
            <div class="flex items-start justify-between gap-4 mb-3">
              <label class="block text-sm font-bold text-foundation-grey"><?php echo esc($label); ?></label>
              <span class="text-[10px] uppercase tracking-widest font-bold <?php echo $format === 'html' ? 'text-pending-amber' : 'text-gray-400'; ?>">
                <?php echo $format === 'html' ? 'html' : 'plain'; ?>
              </span>
            </div>
            <textarea
              name="content[<?php echo esc_attr($activePage); ?>][<?php echo esc_attr($fieldKey); ?>]"
              rows="<?php echo $format === 'html' ? '4' : '2'; ?>"
              class="w-full p-3 border border-gray-200 bg-white outline-none focus:border-rajkot-rust text-sm font-medium"
            ><?php echo esc($value); ?></textarea>
            <p class="text-[11px] text-gray-400 mt-2">Key: <?php echo esc($fieldKey); ?></p>
          </div>
        <?php endforeach; ?>

        <div class="pt-4 border-t border-gray-100 flex flex-col sm:flex-row gap-3">
          <button type="submit" name="action" value="save" class="bg-rajkot-rust hover:bg-red-700 text-white px-6 py-3 text-[10px] font-bold uppercase tracking-[0.2em] transition-all">
            Save Content
          </button>
          <button
            type="submit"
            name="action"
            value="seed_defaults"
            class="bg-foundation-grey hover:bg-black text-white px-6 py-3 text-[10px] font-bold uppercase tracking-[0.2em] transition-all"
            onclick="return confirm('Seed missing default content keys across all pages? Existing customized values will not be overwritten.');"
          >
            Seed Missing Defaults
          </button>
          <?php if ($previewPath !== ''): ?>
            <a href="<?php echo esc_attr(base_path($previewPath)); ?>" target="_blank" rel="noopener noreferrer" class="bg-foundation-grey hover:bg-black text-white px-6 py-3 text-[10px] font-bold uppercase tracking-[0.2em] transition-all text-center no-underline">
              Open Preview
            </a>
          <?php endif; ?>
        </div>
      </form>
    </section>
  </main>

  <?php require_once __DIR__ . '/../Common/footer.php'; ?>
</div>

<script>
if (window.lucide) {
  window.lucide.createIcons();
}
</script>
</body>
</html>
