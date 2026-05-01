<?php
if (!defined('PROJECT_ROOT')) { require_once dirname(__DIR__, 4) . '/app/Core/Bootstrap/init.php'; }
require_once PROJECT_ROOT . '/app/Core/Bootstrap/init.php';
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
  // Detect likely exceeded post_max_size/upload_max_filesize: when
  // Content-Length is present but $_POST is empty, PHP may have
  // discarded the superglobals. Give a helpful flash instead of
  // failing CSRF validation which confuses users.
  $contentLen = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
  $hasPost = !empty($_POST);
  if ($contentLen > 0 && !$hasPost) {
    set_flash('Upload failed: request body too large. Increase post_max_size or upload_max_filesize in php.ini, or upload a smaller file.', 'danger');
    header('Location: ' . base_path('admin/content_management.php?page=' . rawurlencode($activePage)));
    exit;
  }

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
  <?php $HEADER_MODE = 'dashboard';
  require_once PROJECT_ROOT . '/Common/header.php'; ?>
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
                        placeholder="/uploads/content/... or https://...">
                    </div>
                    <div>
                      <label class="block text-[11px] uppercase tracking-widest font-bold text-gray-500 mb-1">Upload New Image</label>
                      <input
                        type="file"
                        name="content_image[<?php echo esc_attr($activePage); ?>][<?php echo esc_attr($fieldKey); ?>]"
                        accept="image/jpeg,image/png,image/webp,image/gif"
                        class="cms-image-file w-full p-2 text-sm bg-white">
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
                    <div class="cms-rich-group" role="group" aria-label="Text styles">
                      <button type="button" class="cms-rich-btn" data-action="bold" title="Bold (Ctrl+B)" aria-label="Bold"><span class="cms-rich-letter">B</span></button>
                      <button type="button" class="cms-rich-btn" data-action="italic" title="Italic (Ctrl+I)" aria-label="Italic"><span class="cms-rich-letter italic">I</span></button>
                      <button type="button" class="cms-rich-btn" data-action="underline" title="Underline (Ctrl+U)" aria-label="Underline"><span class="cms-rich-letter underline">U</span></button>
                    </div>

                    <div class="cms-rich-group" role="group" aria-label="Lists and links">
                      <button type="button" class="cms-rich-btn" data-action="ul" title="Unordered list" aria-label="Unordered list"><i data-lucide="list" class="w-4 h-4" aria-hidden="true"></i></button>
                      <button type="button" class="cms-rich-btn" data-action="ol" title="Ordered list" aria-label="Ordered list"><i data-lucide="list-ordered" class="w-4 h-4" aria-hidden="true"></i></button>
                      <button type="button" class="cms-rich-btn" data-action="link" title="Insert link" aria-label="Insert link"><i data-lucide="link" class="w-4 h-4" aria-hidden="true"></i></button>
                    </div>

                    <div class="cms-rich-group" role="group" aria-label="Cleanup">
                      <button type="button" class="cms-rich-btn" data-action="clear" title="Clear formatting" aria-label="Clear formatting"><i data-lucide="eraser" class="w-4 h-4" aria-hidden="true"></i></button>
                    </div>
                  </div>

                  <textarea
                    id="<?php echo esc_attr($editorId); ?>"
                    name="content[<?php echo esc_attr($activePage); ?>][<?php echo esc_attr($fieldKey); ?>]"
                    rows="<?php echo $format === 'html' ? '4' : '2'; ?>"
                    class="cms-editor-input w-full p-3 border border-gray-200 bg-white outline-none focus:border-rajkot-rust text-sm font-medium"><?php echo esc($value); ?></textarea>
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
              onclick="return confirm('Seed missing default content keys across all pages? Existing customized values will not be overwritten.');">
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

    <?php if (!defined('HIDE_FOOTER_CTA')) define('HIDE_FOOTER_CTA', true); require_once PROJECT_ROOT . '/Common/footer.php'; ?>
  </div>

  <script>
    if (window.lucide) {
      window.lucide.createIcons();
    }

    (function() {
      function CmsToolbarEditor(toolbar) {
        this.toolbar = toolbar;
        this.targetId = toolbar.getAttribute('data-target');
        this.textarea = document.getElementById(this.targetId);
      }

      CmsToolbarEditor.prototype.getSelection = function () {
        var textarea = this.textarea;
        var start = textarea.selectionStart || 0;
        var end = textarea.selectionEnd || 0;
        return {
          start: start,
          end: end,
          text: textarea.value.substring(start, end)
        };
      };

      CmsToolbarEditor.prototype.replaceSelection = function (replacement, selectInserted) {
        var textarea = this.textarea;
        var sel = this.getSelection();
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
      };

      CmsToolbarEditor.prototype.wrapSelection = function (before, after, placeholder) {
        var sel = this.getSelection();
        var text = sel.text || placeholder || 'text';
        this.replaceSelection(before + text + after, !sel.text);
      };

      CmsToolbarEditor.prototype.buildList = function (selectionText, ordered) {
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
      };

      CmsToolbarEditor.prototype.stripTags = function (text) {
        return text.replace(/<[^>]+>/g, '');
      };

      CmsToolbarEditor.prototype.normalizeUrl = function (url) {
        var trimmed = (url || '').trim();
        if (trimmed === '') {
          return '';
        }
        if (!/^https?:\/\//i.test(trimmed)) {
          return 'https://' + trimmed;
        }
        return trimmed;
      };

      CmsToolbarEditor.prototype.exec = function (action) {
        var sel = this.getSelection();
        if (action === 'bold') {
          this.wrapSelection('<strong>', '</strong>', 'bold text');
          return;
        }
        if (action === 'italic') {
          this.wrapSelection('<em>', '</em>', 'italic text');
          return;
        }
        if (action === 'underline') {
          this.wrapSelection('<u>', '</u>', 'underlined text');
          return;
        }
        if (action === 'ul') {
          this.replaceSelection(this.buildList(sel.text, false), false);
          return;
        }
        if (action === 'ol') {
          this.replaceSelection(this.buildList(sel.text, true), false);
          return;
        }
        if (action === 'link') {
          var linkUrl = this.normalizeUrl(prompt('Enter URL', 'https://'));
          if (!linkUrl) {
            return;
          }
          var linkText = sel.text || 'Link text';
          this.replaceSelection('<a href="' + linkUrl + '">' + linkText + '</a>', !sel.text);
          return;
        }
        if (action === 'clear') {
          if (sel.text) {
            this.replaceSelection(this.stripTags(sel.text), false);
          } else {
            this.textarea.value = this.stripTags(this.textarea.value);
            this.textarea.focus();
          }
        }
      };

      CmsToolbarEditor.prototype.bind = function () {
        var self = this;
        if (!self.textarea) {
          return;
        }

        self.toolbar.querySelectorAll('button[data-action]').forEach(function (button) {
          button.addEventListener('mousedown', function (event) {
            event.preventDefault();
          });

          button.addEventListener('click', function () {
            var action = button.getAttribute('data-action');
            if (action) {
              self.exec(action);
            }
          });
        });

        self.textarea.addEventListener('keydown', function (event) {
          if (!event.ctrlKey && !event.metaKey) {
            return;
          }

          var key = (event.key || '').toLowerCase();
          if (key === 'b') {
            event.preventDefault();
            self.exec('bold');
          } else if (key === 'i') {
            event.preventDefault();
            self.exec('italic');
          } else if (key === 'u') {
            event.preventDefault();
            self.exec('underline');
          }
        });
      };

      document.querySelectorAll('.cms-rich-toolbar').forEach(function (toolbar) {
        var editor = new CmsToolbarEditor(toolbar);
        editor.bind();
      });
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
      position: static;
      display: none;
      align-items: center;
      justify-content: flex-start;
      gap: 0.45rem;
      flex-wrap: wrap;
      padding: 0.45rem;
      margin-bottom: 0.45rem;
      border: 1px solid #e5e7eb;
      border-radius: 0;
      background: #ffffff;
      box-shadow: none;
      z-index: 5;
    }

    .cms-editor-shell:focus-within .cms-rich-toolbar {
      display: flex;
    }

    .cms-rich-group {
      display: inline-flex;
      align-items: center;
      gap: 0.25rem;
      padding: 0.1rem;
      border: 1px solid #e5e7eb;
      border-radius: 0;
      background: #fff;
    }

    .cms-rich-btn {
      border: 1px solid #e5e7eb;
      background: #ffffff;
      color: #2d2d2d;
      border-radius: 0;
      font-size: 0.78rem;
      font-weight: 800;
      line-height: 1;
      min-width: 2rem;
      height: 2rem;
      padding: 0 0.45rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      transition: all 0.18s ease;
    }

    .cms-rich-btn:hover,
    .cms-rich-btn:focus {
      border-color: #94180c;
      background: #fff5f5;
      color: #94180c;
    }

    .cms-rich-letter {
      font-size: 0.82rem;
      font-weight: 900;
      letter-spacing: 0.02em;
    }

    .cms-rich-letter.italic {
      font-style: italic;
    }

    .cms-rich-letter.underline {
      text-decoration: underline;
    }

    .cms-editor-input {
      padding-top: 0.75rem !important;
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
        gap: 0.35rem;
      }

      .cms-editor-input {
        padding-top: 0.75rem !important;
      }
    }
  </style>
</body>

</html>