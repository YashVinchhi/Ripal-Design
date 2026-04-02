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

    $imageUploads = $_FILES['content_image'] ?? [];
    if (!is_array($imageUploads)) {
      $imageUploads = [];
    }

    $removeImages = $_POST['remove_image'][$postedPage] ?? [];
    if (!is_array($removeImages)) {
      $removeImages = [];
    }

    $save = public_content_upsert_page($postedPage, $payload, current_user_id(), $imageUploads, $removeImages);
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
        <p class="text-sm text-gray-500 mt-2">Update text and image values, then save. Text fields show a toolbar on focus. Image fields support replace, direct path edit, and remove.</p>
      </div>

      <form method="post" enctype="multipart/form-data" class="space-y-6">
        <?php echo csrf_token_field(); ?>
        <input type="hidden" name="page_slug" value="<?php echo esc_attr($activePage); ?>">

        <?php foreach ($currentFields as $key => $meta): ?>
          <?php
            $fieldKey = (string)$key;
            $label = (string)($meta['label'] ?? $fieldKey);
            $rawFormat = strtolower(trim((string)($meta['format'] ?? 'plain')));
            $format = $rawFormat === 'html' ? 'html' : ($rawFormat === 'image' ? 'image' : 'plain');
            $default = (string)($meta['default'] ?? '');
            $value = (string)($currentValues[$fieldKey] ?? $default);

            $safeActive = preg_replace('/[^a-z0-9_-]/i', '_', $activePage);
            $safeField = preg_replace('/[^a-z0-9_-]/i', '_', $fieldKey);
            $editorId = 'content_editor_' . $safeActive . '_' . $safeField;
          ?>
          <div class="border border-gray-100 p-4 md:p-5 bg-gray-50">
            <div class="flex items-start justify-between gap-4 mb-3">
              <label class="block text-sm font-bold text-foundation-grey"><?php echo esc($label); ?></label>
              <span class="text-[10px] uppercase tracking-widest font-bold <?php echo $format === 'html' ? 'text-pending-amber' : ($format === 'image' ? 'text-rajkot-rust' : 'text-gray-400'); ?>">
                <?php echo esc($format); ?>
              </span>
            </div>

            <?php if ($format === 'image'): ?>
              <?php
                $imagePreview = function_exists('public_content_image_url')
                  ? (string)public_content_image_url($value, $default)
                  : (string)$value;
              ?>
              <div class="cms-image-shell border border-[#94180c] bg-white p-3">
                <?php if ($imagePreview !== ''): ?>
                  <div class="border border-gray-200 bg-gray-100 p-2 mb-3">
                    <img src="<?php echo esc_attr($imagePreview); ?>" alt="<?php echo esc_attr($label); ?>" class="max-h-48 w-auto object-contain">
                  </div>
                <?php else: ?>
                  <div class="text-xs text-gray-500 mb-3">No image selected.</div>
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                  <div>
                    <label class="block text-[11px] uppercase tracking-widest font-bold text-gray-500 mb-1">Image Path or URL</label>
                    <input
                      type="text"
                      name="content[<?php echo esc_attr($activePage); ?>][<?php echo esc_attr($fieldKey); ?>]"
                      value="<?php echo esc_attr($value); ?>"
                      class="cms-image-input w-full p-2.5 text-sm bg-white"
                      placeholder="/uploads/content/... or https://..."
                    >
                  </div>
                  <div>
                    <label class="block text-[11px] uppercase tracking-widest font-bold text-gray-500 mb-1">Upload New Image</label>
                    <input
                      type="file"
                      name="content_image[<?php echo esc_attr($activePage); ?>][<?php echo esc_attr($fieldKey); ?>]"
                      accept="image/jpeg,image/png,image/webp,image/gif"
                      class="cms-image-file w-full p-2 text-sm bg-white"
                    >
                  </div>
                </div>

                <label class="inline-flex items-center gap-2 mt-3 text-sm text-foundation-grey">
                  <input type="checkbox" name="remove_image[<?php echo esc_attr($activePage); ?>][<?php echo esc_attr($fieldKey); ?>]" value="1" class="cms-image-remove-check">
                  Remove image
                </label>

                <p class="text-[11px] text-gray-500 mt-2">Upload replaces the current image. Supported: JPG, JPEG, PNG, WEBP, GIF (max 10 MB).</p>
              </div>
            <?php else: ?>
              <div class="cms-editor-shell relative">
                <div class="cms-rich-toolbar" data-target="<?php echo esc_attr($editorId); ?>" aria-label="Formatting toolbar">
                  <button type="button" class="cms-rich-btn" data-cmd="bold" title="Bold"><i class="bi bi-type-bold" aria-hidden="true"></i></button>
                  <button type="button" class="cms-rich-btn" data-cmd="italic" title="Italic"><i class="bi bi-type-italic" aria-hidden="true"></i></button>
                  <button type="button" class="cms-rich-btn" data-cmd="underline" title="Underline"><i class="bi bi-type-underline" aria-hidden="true"></i></button>

                  <span class="cms-rich-separator" aria-hidden="true"></span>

                  <button type="button" class="cms-rich-btn" data-cmd="ul" title="Unordered List"><i class="bi bi-list-ul" aria-hidden="true"></i></button>
                  <button type="button" class="cms-rich-btn" data-cmd="ol" title="Ordered List"><i class="bi bi-list-ol" aria-hidden="true"></i></button>
                  <button type="button" class="cms-rich-btn" data-cmd="link" title="Insert Link"><i class="bi bi-link-45deg" aria-hidden="true"></i></button>
                  <button type="button" class="cms-rich-btn" data-cmd="clear" title="Clear Formatting"><i class="bi bi-eraser" aria-hidden="true"></i></button>
                </div>

                <textarea
                  id="<?php echo esc_attr($editorId); ?>"
                  name="content[<?php echo esc_attr($activePage); ?>][<?php echo esc_attr($fieldKey); ?>]"
                  rows="<?php echo $format === 'html' ? '4' : '2'; ?>"
                  class="cms-editor-input w-full p-3 border border-gray-200 bg-white outline-none focus:border-rajkot-rust text-sm font-medium"
                ><?php echo esc($value); ?></textarea>
              </div>
            <?php endif; ?>

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

(function () {
  function getSelection(textarea) {
    var start = textarea.selectionStart || 0;
    var end = textarea.selectionEnd || 0;
    return {
      start: start,
      end: end,
      text: textarea.value.substring(start, end)
    };
  }

  function replaceSelection(textarea, replacement, selectInserted) {
    var sel = getSelection(textarea);
    var prefix = textarea.value.substring(0, sel.start);
    var suffix = textarea.value.substring(sel.end);
    textarea.value = prefix + replacement + suffix;

    var caretStart = sel.start;
    var caretEnd = sel.start + replacement.length;

    if (selectInserted) {
      textarea.setSelectionRange(caretStart, caretEnd);
    } else {
      textarea.setSelectionRange(caretEnd, caretEnd);
    }
    textarea.focus();
  }

  function wrapSelection(textarea, before, after, placeholder) {
    var sel = getSelection(textarea);
    var text = sel.text;
    if (!text) {
      text = placeholder || 'text';
    }
    replaceSelection(textarea, before + text + after, !sel.text);
  }

  function buildList(selectionText, ordered) {
    var lines = selectionText.split(/\r?\n/).map(function (line) {
      return line.trim();
    }).filter(function (line) {
      return line.length > 0;
    });

    if (!lines.length) {
      lines = ['List item'];
    }

    var tag = ordered ? 'ol' : 'ul';
    var html = '<' + tag + '>';
    lines.forEach(function (line) {
      html += '<li>' + line + '</li>';
    });
    html += '</' + tag + '>';
    return html;
  }

  function stripTags(text) {
    return text.replace(/<[^>]+>/g, '');
  }

  function attachToolbar(toolbar) {
    var targetId = toolbar.getAttribute('data-target');
    var textarea = document.getElementById(targetId);
    if (!textarea) {
      return;
    }

    toolbar.querySelectorAll('button[data-cmd]').forEach(function (control) {
      control.addEventListener('mousedown', function (event) {
        event.preventDefault();
      });
    });

    toolbar.addEventListener('click', function (event) {
      var btn = event.target.closest('button[data-cmd]');
      if (!btn) {
        return;
      }

      var cmd = btn.getAttribute('data-cmd');
      var sel = getSelection(textarea);

      if (cmd === 'bold') {
        wrapSelection(textarea, '<strong>', '</strong>', 'bold text');
      } else if (cmd === 'italic') {
        wrapSelection(textarea, '<em>', '</em>', 'italic text');
      } else if (cmd === 'underline') {
        wrapSelection(textarea, '<u>', '</u>', 'underlined text');
      } else if (cmd === 'ul') {
        replaceSelection(textarea, buildList(sel.text, false));
      } else if (cmd === 'ol') {
        replaceSelection(textarea, buildList(sel.text, true));
      } else if (cmd === 'link') {
        var linkUrl = prompt('Enter URL (include https://)', 'https://');
        if (linkUrl) {
          var linkText = sel.text || 'Link text';
          replaceSelection(textarea, '<a href="' + linkUrl + '">' + linkText + '</a>');
        }
      } else if (cmd === 'clear') {
        if (sel.text) {
          replaceSelection(textarea, stripTags(sel.text));
        } else {
          textarea.value = stripTags(textarea.value);
          textarea.focus();
        }
      }
    });
  }

  document.querySelectorAll('.cms-rich-toolbar').forEach(attachToolbar);
})();
</script>

<style>
.cms-editor-shell {
  position: relative;
}

.cms-image-shell,
.cms-image-input,
.cms-image-file,
.cms-image-remove-check {
  border-radius: 0 !important;
}

.cms-image-input,
.cms-image-file {
  border: 1px solid #94180c;
  color: #2d2d2d;
}

.cms-image-input:focus,
.cms-image-file:focus {
  border-color: #94180c;
  outline: none;
  box-shadow: none;
}

.cms-image-remove-check {
  width: 1rem;
  height: 1rem;
  accent-color: #94180c;
}

.cms-rich-toolbar {
  position: absolute;
  top: 0.5rem;
  left: 0.5rem;
  right: 0.5rem;
  display: none;
  align-items: center;
  gap: 0.2rem;
  flex-wrap: nowrap;
  padding: 0.25rem;
  border: 1px solid #94180c;
  border-radius: 0;
  background: #ffffff;
  box-shadow: 0 2px 8px rgba(148, 24, 12, 0.12);
  z-index: 5;
}

.cms-editor-shell:focus-within .cms-rich-toolbar {
  display: flex;
}

.cms-rich-btn {
  border: 1px solid #94180c;
  background: #ffffff;
  color: #94180c;
  border-radius: 0;
  font-size: 0.8rem;
  line-height: 1;
  width: 1.85rem;
  height: 1.85rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.cms-rich-btn:hover,
.cms-rich-btn:focus {
  border-color: #94180c;
  background: #94180c;
  color: #ffffff;
}

.cms-rich-separator {
  width: 1px;
  height: 1rem;
  background: #94180c;
  margin: 0 0.15rem;
}

.cms-editor-input {
  padding-top: 3rem !important;
  border-color: #94180c !important;
  border-radius: 0 !important;
}

.cms-editor-input:focus {
  border-color: #94180c !important;
  box-shadow: none;
}

.cms-editor-shell .cms-rich-toolbar,
.cms-editor-shell .cms-rich-btn,
.cms-editor-shell .cms-editor-input {
  border-radius: 0 !important;
}

@media (max-width: 640px) {
  .cms-rich-toolbar {
    overflow-x: auto;
    overflow-y: hidden;
  }

  .cms-editor-input {
    padding-top: 3.25rem !important;
  }
}
</style>
</body>
</html>
