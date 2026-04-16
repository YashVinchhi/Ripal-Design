import re
from pathlib import Path

root = Path(r"d:/WP/laragon/www/Thefinal")
app = root / "app"

# 1) Replace mojibake bullets in app PHP files
mojibake_changed = []
for p in app.rglob("*.php"):
    text = p.read_text(encoding="utf-8", errors="ignore")
    new = text.replace("•", "&bull;")
    if new != text:
        p.write_text(new, encoding="utf-8", newline="")
        mojibake_changed.append(str(p))

# 2) file_viewer.php edits
viewer = root / "app/Domains/Admin/Controllers/file_viewer.php"
text = viewer.read_text(encoding="utf-8", errors="ignore")

# top dedupe bootstrap/session
text = re.sub(
    r"^<\?php\s*if \(!defined\('PROJECT_ROOT'\)\) \{ require_once dirname\(__DIR__, 4\) \. '/app/Core/Bootstrap/init\.php'; \}\s*session_start\(\);\s*require_once PROJECT_ROOT \. '/app/Core/Bootstrap/init\.php';\s*require_login\(\);",
    "<?php\nif (!defined('PROJECT_ROOT')) { require_once dirname(__DIR__, 4) . '/app/Core/Bootstrap/init.php'; }\nrequire_login();",
    text,
    count=1,
    flags=re.S,
)

resolve_new = """if (!function_exists('resolve_preview_absolute_path')) {
  function resolve_preview_absolute_path($rawPath)
  {
    $value = trim((string)$rawPath);
    if ($value === '' || preg_match('/^https?:\\/\\//i', $value)) {
      return '';
    }

    $normalized = str_replace('\\\\', '/', $value);
    $normalized = (string)preg_replace('#/+#', '/', $normalized);
    $pathOnly = (string)parse_url($normalized, PHP_URL_PATH);
    if ($pathOnly !== '') {
      $normalized = $pathOnly;
    }

    $legacyRelative = ltrim($normalized, '/');
    if ($legacyRelative === '') {
      return '';
    }

    $uploadsRelative = $legacyRelative;
    $uploadsPos = stripos($legacyRelative, 'uploads/');
    if ($uploadsPos is not False) {
      $uploadsRelative = substr($legacyRelative, $uploadsPos + strlen('uploads/'));
    }
    $uploadsRelative = ltrim((string)$uploadsRelative, '/');

    if ($uploadsRelative !== '' && defined('UPLOAD_STORAGE_ROOT')) {
      $privateAbsolute = rtrim((string)UPLOAD_STORAGE_ROOT, '/\\\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $uploadsRelative);
      if (is_file($privateAbsolute)) {
        return $privateAbsolute;
      }
    }

    $legacyAbsolute = rtrim((string)PROJECT_ROOT, '/\\\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $legacyRelative);
    return is_file($legacyAbsolute) ? $legacyAbsolute : '';
  }
}
"""
# fix accidental pythonism in inserted php string
resolve_new = resolve_new.replace("if ($uploadsPos is not False)", "if ($uploadsPos !== false)")

text = re.sub(
    r"if \(!function_exists\('resolve_preview_absolute_path'\)\) \{.*?\n\}\s*\n\s*if \(!function_exists\('file_viewer_absolute_url'\)\)",
    resolve_new + "\nif (!function_exists('file_viewer_absolute_url'))",
    text,
    count=1,
    flags=re.S,
)

text = re.sub(
    r"if \(\$previewUrl === '' && \$resourceStreamUrl !== ''\) \{\s*\$previewUrl = \$resourceStreamUrl;\s*\}",
    "if ($resourceStreamUrl !== '' && ($previewUrl === '' || $previewAbsolutePath === '')) {\n  $previewUrl = $resourceStreamUrl;\n  $previewAbsoluteUrl = file_viewer_absolute_url($resourceStreamUrl);\n}",
    text,
    count=1,
    flags=re.S,
)

text = text.replace(
    "if ($viewerMode === '3d' && $previewDirectUrl !== '')",
    "if ($viewerMode === '3d' && $previewDirectUrl !== '' && $previewAbsolutePath !== '')",
    1,
)

viewer.write_text(text, encoding="utf-8", newline="")

# 3) file_stream dedupe
stream = root / "app/Domains/Dashboard/Controllers/file_stream.php"
st = stream.read_text(encoding="utf-8", errors="ignore")
st = re.sub(
    r"if \(!defined\('PROJECT_ROOT'\)\) \{ require_once dirname\(__DIR__, 4\) \. '/app/Core/Bootstrap/init\.php'; \}\s*require_once PROJECT_ROOT \. '/app/Core/Bootstrap/init\.php';",
    "if (!defined('PROJECT_ROOT')) { require_once dirname(__DIR__, 4) . '/app/Core/Bootstrap/init.php'; }",
    st,
    count=1,
    flags=re.S,
)
stream.write_text(st, encoding="utf-8", newline="")

print(f"MOJIBAKE_CHANGED_COUNT={len(mojibake_changed)}")
for p in mojibake_changed:
    print(f"MOJIBAKE_CHANGED_FILE={p}")