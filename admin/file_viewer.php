<?php
session_start();
require_once __DIR__ . '/../app/Core/Bootstrap/init.php';
require_login();

$notice = '';
$noticeType = 'info';

if (!function_exists('file_viewer_test_relative_dir')) {
  function file_viewer_test_relative_dir()
  {
    return 'uploads/file_viewer_testing';
  }
}

if (!function_exists('file_viewer_test_absolute_dir')) {
  function file_viewer_test_absolute_dir()
  {
    return rtrim((string)PROJECT_ROOT, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, file_viewer_test_relative_dir());
  }
}

if (!function_exists('file_viewer_history_path')) {
  function file_viewer_history_path()
  {
    return rtrim((string)PROJECT_ROOT, '/\\') . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'file_viewer_test_history.json';
  }
}

if (!function_exists('file_viewer_stereo_state_path')) {
  function file_viewer_stereo_state_path()
  {
    return rtrim((string)PROJECT_ROOT, '/\\') . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'file_viewer_stereo_state.json';
  }
}

if (!function_exists('file_viewer_load_stereo_state')) {
  function file_viewer_load_stereo_state()
  {
    $path = file_viewer_stereo_state_path();
    if (!is_file($path)) {
      return ['left' => '', 'right' => ''];
    }
    $raw = (string)@file_get_contents($path);
    if ($raw === '') {
      return ['left' => '', 'right' => ''];
    }
    $parsed = json_decode($raw, true);
    if (!is_array($parsed)) {
      return ['left' => '', 'right' => ''];
    }
    $left = str_replace('\\', '/', trim((string)($parsed['left'] ?? '')));
    $right = str_replace('\\', '/', trim((string)($parsed['right'] ?? '')));
    return ['left' => $left, 'right' => $right];
  }
}

if (!function_exists('file_viewer_write_stereo_state')) {
  function file_viewer_write_stereo_state($left, $right)
  {
    $path = file_viewer_stereo_state_path();
    $dir = dirname($path);
    if (!is_dir($dir)) {
      @mkdir($dir, 0775, true);
    }
    $payload = [
      'left' => str_replace('\\', '/', trim((string)$left)),
      'right' => str_replace('\\', '/', trim((string)$right)),
      'updated_at' => date('c'),
    ];
    @file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
  }
}

if (!function_exists('file_viewer_vr_settings_path')) {
  function file_viewer_vr_settings_path()
  {
    return rtrim((string)PROJECT_ROOT, '/\\') . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'file_viewer_vr_settings.json';
  }
}

if (!function_exists('file_viewer_load_vr_settings')) {
  function file_viewer_load_vr_settings()
  {
    $path = file_viewer_vr_settings_path();
    if (!is_file($path)) {
      return [
        'device' => 'Custom',
        'screensize' => '6.1',
        'ipd' => '63.5',
        'headset' => 'No Distortion',
        'custom' => '',
        'updated_at' => '',
      ];
    }
    $raw = @file_get_contents($path);
    if ($raw === '') {
      return [
        'device' => 'Custom',
        'screensize' => '6.1',
        'ipd' => '63.5',
        'headset' => 'No Distortion',
        'custom' => '',
        'updated_at' => '',
      ];
    }
    $parsed = json_decode($raw, true);
    if (!is_array($parsed)) {
      return [
        'device' => 'Custom',
        'screensize' => '6.1',
        'ipd' => '63.5',
        'headset' => 'No Distortion',
        'custom' => '',
        'updated_at' => '',
      ];
    }
    return array_merge([
      'device' => 'Custom',
      'screensize' => '6.1',
      'ipd' => '63.5',
      'headset' => 'No Distortion',
      'custom' => '',
      'updated_at' => '',
    ], $parsed);
  }
}

if (!function_exists('file_viewer_write_vr_settings')) {
  function file_viewer_write_vr_settings($settings)
  {
    $path = file_viewer_vr_settings_path();
    $dir = dirname($path);
    if (!is_dir($dir)) {
      @mkdir($dir, 0775, true);
    }
    $payload = is_array($settings) ? $settings : [];
    $payload['updated_at'] = date('c');
    @file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
  }
}

if (!function_exists('file_viewer_load_history')) {
  function file_viewer_load_history()
  {
    $path = file_viewer_history_path();
    if (!is_file($path)) {
      return [];
    }
    $raw = (string)@file_get_contents($path);
    if ($raw === '') {
      return [];
    }
    $parsed = json_decode($raw, true);
    return is_array($parsed) ? $parsed : [];
  }
}

if (!function_exists('file_viewer_write_history')) {
  function file_viewer_write_history($items)
  {
    $path = file_viewer_history_path();
    $dir = dirname($path);
    if (!is_dir($dir)) {
      @mkdir($dir, 0775, true);
    }
    @file_put_contents($path, json_encode(array_values($items), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
  }
}

if (!function_exists('file_viewer_append_history')) {
  function file_viewer_append_history($action, $relativePath, $statusText, $meta = [])
  {
    $items = file_viewer_load_history();
    $items[] = [
      'timestamp' => date('c'),
      'action' => (string)$action,
      'file' => (string)$relativePath,
      'status' => (string)$statusText,
      'meta' => is_array($meta) ? $meta : [],
    ];
    if (count($items) > 200) {
      $items = array_slice($items, -200);
    }
    file_viewer_write_history($items);
  }
}

if (!function_exists('file_viewer_format_bytes')) {
  function file_viewer_format_bytes($bytes)
  {
    $size = (float)$bytes;
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($size >= 1024 && $i < count($units) - 1) {
      $size /= 1024;
      $i++;
    }
    return number_format($size, $i === 0 ? 0 : 2) . ' ' . $units[$i];
  }
}

if (!function_exists('file_viewer_store_test_upload')) {
  function file_viewer_store_test_upload($upload, $targetDir, $allowed, $maxBytes, &$errorText = '')
  {
    $originalName = (string)($upload['name'] ?? '');
    $tmpName = (string)($upload['tmp_name'] ?? '');
    $uploadError = (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE);
    $sizeBytes = (int)($upload['size'] ?? 0);
    $ext = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));

    if ($uploadError !== UPLOAD_ERR_OK) {
      $errorText = 'Upload failed. Please try again.';
      return null;
    }
    if (!in_array($ext, $allowed, true)) {
      $errorText = 'Unsupported file type for upload.';
      return null;
    }
    if ($sizeBytes <= 0 || $sizeBytes > $maxBytes) {
      $errorText = 'File size must be between 1 byte and 200 MB.';
      return null;
    }

    $safeBase = preg_replace('/[^a-zA-Z0-9_\-]/', '_', (string)pathinfo($originalName, PATHINFO_FILENAME));
    $safeBase = trim((string)$safeBase, '_-');
    if ($safeBase === '') {
      $safeBase = 'test_file';
    }

    $finalName = $safeBase . '_' . date('Ymd_His') . '_' . substr(md5((string)microtime(true) . $originalName), 0, 6) . '.' . $ext;
    $absolutePath = rtrim((string)$targetDir, '/\\') . DIRECTORY_SEPARATOR . $finalName;
    $relativePath = file_viewer_test_relative_dir() . '/' . $finalName;

    if (!@move_uploaded_file($tmpName, $absolutePath)) {
      $errorText = 'Could not save uploaded file.';
      return null;
    }

    return [
      'relative' => $relativePath,
      'absolute' => $absolutePath,
      'size' => $sizeBytes,
      'mime' => (string)($upload['type'] ?? ''),
      'ext' => $ext,
      'name' => $finalName,
    ];
  }
}

if (!function_exists('file_viewer_normalize_uploaded_stem')) {
  function file_viewer_normalize_uploaded_stem($filename)
  {
    $name = basename((string)$filename);
    $stem = strtolower((string)pathinfo($name, PATHINFO_FILENAME));
    // Strip generated suffix: _YYYYMMDD_HHMMSS_xxxxxx
    $stem = preg_replace('/_\d{8}_\d{6}_[a-f0-9]{6}$/i', '', $stem);
    return (string)$stem;
  }
}

if (!function_exists('file_viewer_detect_eye_from_filename')) {
  function file_viewer_detect_eye_from_filename($filename)
  {
    $stem = file_viewer_normalize_uploaded_stem($filename);
    if ((bool)preg_match('/(^|[_\-\s])(left)(?=$|[_\-\s])/i', $stem)) {
      return 'left';
    }
    if ((bool)preg_match('/(^|[_\-\s])(right)(?=$|[_\-\s])/i', $stem)) {
      return 'right';
    }
    return '';
  }
}

if (!function_exists('file_viewer_stereo_pair_key')) {
  function file_viewer_stereo_pair_key($filename)
  {
    $stem = file_viewer_normalize_uploaded_stem($filename);
    $stem = preg_replace('/(^|[_\-\s])(left|right)(?=$|[_\-\s])/i', '$1', $stem);
    $stem = preg_replace('/[_\-\s]+/', '_', (string)$stem);
    return trim((string)$stem, '_');
  }
}

if (!function_exists('file_viewer_find_matching_eye_file')) {
  function file_viewer_find_matching_eye_file($targetDir, $sourceFilename, $sourceEye)
  {
    $sourceEye = strtolower(trim((string)$sourceEye));
    $oppositeEye = $sourceEye === 'left' ? 'right' : ($sourceEye === 'right' ? 'left' : '');
    if ($oppositeEye === '') {
      return '';
    }

    $sourceKey = file_viewer_stereo_pair_key($sourceFilename);
    if ($sourceKey === '') {
      return '';
    }

    $bestFile = '';
    $bestTime = 0;
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $entries = @scandir((string)$targetDir);
    if (!is_array($entries)) {
      return '';
    }

    foreach ($entries as $entry) {
      if ($entry === '.' || $entry === '..') {
        continue;
      }
      $absolutePath = rtrim((string)$targetDir, '/\\') . DIRECTORY_SEPARATOR . $entry;
      if (!is_file($absolutePath)) {
        continue;
      }
      $ext = strtolower((string)pathinfo($entry, PATHINFO_EXTENSION));
      if (!in_array($ext, $allowed, true)) {
        continue;
      }
      if (file_viewer_detect_eye_from_filename($entry) !== $oppositeEye) {
        continue;
      }
      if (file_viewer_stereo_pair_key($entry) !== $sourceKey) {
        continue;
      }

      $mtime = (int)@filemtime($absolutePath);
      if ($mtime >= $bestTime) {
        $bestTime = $mtime;
        $bestFile = $entry;
      }
    }

    if ($bestFile === '') {
      return '';
    }
    return file_viewer_test_relative_dir() . '/' . $bestFile;
  }
}

if (!function_exists('file_viewer_raw_to_upload_relative')) {
  function file_viewer_raw_to_upload_relative($rawPath)
  {
    $value = trim((string)$rawPath);
    if ($value === '' || preg_match('/^https?:\/\//i', $value)) {
      return '';
    }
    $normalized = str_replace('\\', '/', $value);
    $uploadsPos = strpos($normalized, '/uploads/');
    if ($uploadsPos === false && strpos($normalized, 'uploads/') === 0) {
      $uploadsPos = 0;
    }
    if ($uploadsPos === false) {
      return '';
    }
    $relative = $uploadsPos === 0 ? $normalized : substr($normalized, $uploadsPos + 1);
    return ltrim($relative, '/');
  }
}

if (!function_exists('file_viewer_relative_to_absolute')) {
  function file_viewer_relative_to_absolute($relativePath)
  {
    $relative = trim((string)$relativePath);
    if ($relative === '') {
      return '';
    }
    return rtrim((string)PROJECT_ROOT, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
  }
}

if (!function_exists('file_viewer_relative_to_url')) {
  function file_viewer_relative_to_url($relativePath)
  {
    $relative = ltrim(trim((string)$relativePath), '/');
    if ($relative === '') {
      return '';
    }
    return function_exists('base_path') ? (string)base_path($relative) : ('/' . $relative);
  }
}

if (!function_exists('file_viewer_cad_preview_paths')) {
  function file_viewer_cad_preview_paths($sourceRawPath)
  {
    $relativeSource = file_viewer_raw_to_upload_relative($sourceRawPath);
    if ($relativeSource === '') {
      return ['relative' => '', 'absolute' => '', 'url' => ''];
    }

    $sourceDir = str_replace('\\', '/', dirname($relativeSource));
    if ($sourceDir === '.' || $sourceDir === '') {
      $sourceDir = 'uploads';
    }

    $sourceName = basename((string)$relativeSource);
    $sourceStem = (string)pathinfo($sourceName, PATHINFO_FILENAME);
    if ($sourceStem === '') {
      $sourceStem = 'cad_file';
    }

    $previewRelative = trim($sourceDir, '/') . '/' . $sourceStem . '_preview.glb';
    return [
      'relative' => $previewRelative,
      'absolute' => file_viewer_relative_to_absolute($previewRelative),
      'url' => file_viewer_relative_to_url($previewRelative),
    ];
  }
}

if (!function_exists('file_viewer_generate_local_cad_preview')) {
  function file_viewer_generate_local_cad_preview($sourceRawPath, $sourceExt, &$message = '')
  {
    $ext = strtolower(trim((string)$sourceExt));
    if (!in_array($ext, ['dwg', 'skp'], true)) {
      $message = 'Unsupported CAD format.';
      return false;
    }

    $sourceRelative = file_viewer_raw_to_upload_relative($sourceRawPath);
    if ($sourceRelative === '') {
      $message = 'Source path is invalid.';
      return false;
    }

    $sourceAbsolute = file_viewer_relative_to_absolute($sourceRelative);
    if ($sourceAbsolute === '' || !is_file($sourceAbsolute)) {
      $message = 'Source CAD file is missing.';
      return false;
    }

    $preview = file_viewer_cad_preview_paths($sourceRawPath);
    $previewAbsolute = (string)($preview['absolute'] ?? '');
    if ($previewAbsolute === '') {
      $message = 'Preview target path could not be prepared.';
      return false;
    }

    $previewDir = dirname($previewAbsolute);
    if (!is_dir($previewDir) && !@mkdir($previewDir, 0775, true) && !is_dir($previewDir)) {
      $message = 'Could not create preview directory.';
      return false;
    }

    $commandTemplate = $ext === 'dwg'
      ? trim((string)getenv('CAD_DWG_CONVERTER_CMD'))
      : trim((string)getenv('CAD_SKP_CONVERTER_CMD'));

    if ($commandTemplate === '') {
      $message = 'Set ' . ($ext === 'dwg' ? 'CAD_DWG_CONVERTER_CMD' : 'CAD_SKP_CONVERTER_CMD') . ' in environment.';
      return false;
    }

    $command = str_replace(
      ['{input}', '{output}'],
      [escapeshellarg($sourceAbsolute), escapeshellarg($previewAbsolute)],
      $commandTemplate
    );

    $output = [];
    $exitCode = 1;
    @exec($command . ' 2>&1', $output, $exitCode);

    if ($exitCode !== 0 || !is_file($previewAbsolute)) {
      $tail = '';
      if (!empty($output)) {
        $tail = ' ' . trim((string)end($output));
      }
      $message = 'CAD conversion failed.' . $tail;
      return false;
    }

    $message = 'Local CAD preview generated.';
    return true;
  }
}

if (!function_exists('file_viewer_is_likely_text')) {
  function file_viewer_is_likely_text($absolutePath)
  {
    if (!is_file($absolutePath) || !is_readable($absolutePath)) {
      return false;
    }
    $fh = @fopen($absolutePath, 'rb');
    if (!$fh) {
      return false;
    }
    $chunk = @fread($fh, 4096);
    @fclose($fh);
    if ($chunk === false) {
      return false;
    }
    // If we find a NUL byte, treat as binary.
    if (strpos($chunk, "\0") !== false) {
      return false;
    }
    return true;
  }
}

if (!function_exists('file_viewer_generate_image_preview')) {
  function file_viewer_generate_image_preview($sourceRawPath, $maxWidth = 1600, &$message = '')
  {
    $relativeSource = file_viewer_raw_to_upload_relative($sourceRawPath);
    if ($relativeSource === '') {
      $message = 'Source path invalid.';
      return ['relative' => '', 'absolute' => '', 'url' => ''];
    }

    $sourceAbsolute = file_viewer_relative_to_absolute($relativeSource);
    if ($sourceAbsolute === '' || !is_file($sourceAbsolute)) {
      $message = 'Source missing.';
      return ['relative' => '', 'absolute' => '', 'url' => ''];
    }

    if (!extension_loaded('imagick')) {
      $message = 'Imagick not available.';
      return ['relative' => '', 'absolute' => '', 'url' => ''];
    }

    try {
      $img = new Imagick($sourceAbsolute);
      // For multi-page TIFFs, keep the first image.
      if ($img->getNumberImages() > 1) {
        $img->setFirstIterator();
      }
      $img->setImageColorspace(Imagick::COLORSPACE_RGB);
      $img->setImageFormat('jpeg');
      $img->stripImage();
      $width = $img->getImageWidth();
      if ($width > $maxWidth) {
        $img->resizeImage($maxWidth, 0, Imagick::FILTER_LANCZOS, 1);
      }
    } catch (Throwable $e) {
      $message = 'Image conversion failed.';
      return ['relative' => '', 'absolute' => '', 'url' => ''];
    }

    $safeBase = preg_replace('/[^a-zA-Z0-9_\-]/', '_', (string)pathinfo($relativeSource, PATHINFO_FILENAME));
    if ($safeBase === '') {
      $safeBase = 'preview';
    }
    $destRel = file_viewer_test_relative_dir() . '/previews/' . $safeBase . '_' . substr(md5($sourceAbsolute), 0, 8) . '.jpg';
    $destAbs = file_viewer_relative_to_absolute($destRel);
    $destDir = dirname($destAbs);
    if (!is_dir($destDir) && !@mkdir($destDir, 0775, true) && !is_dir($destDir)) {
      $message = 'Could not create preview directory.';
      return ['relative' => '', 'absolute' => '', 'url' => ''];
    }

    try {
      $img->writeImage($destAbs);
    } catch (Throwable $e) {
      $message = 'Could not write preview file.';
      return ['relative' => '', 'absolute' => '', 'url' => ''];
    }

    return ['relative' => $destRel, 'absolute' => $destAbs, 'url' => file_viewer_relative_to_url($destRel)];
  }
}

if (!function_exists('file_viewer_generate_image_preview_cli')) {
  function file_viewer_generate_image_preview_cli($sourceRawPath, $maxWidth = 1600, &$message = '')
  {
    $relativeSource = file_viewer_raw_to_upload_relative($sourceRawPath);
    if ($relativeSource === '') {
      $message = 'Source path invalid.';
      return ['relative' => '', 'absolute' => '', 'url' => ''];
    }

    $sourceAbsolute = file_viewer_relative_to_absolute($relativeSource);
    if ($sourceAbsolute === '' || !is_file($sourceAbsolute)) {
      // if file doesn't yet exist at computed absolute path, try building absolute regardless
      $sourceAbsolute = rtrim((string)PROJECT_ROOT, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($relativeSource, '/'));
      if (!is_file($sourceAbsolute)) {
        $message = 'Source missing.';
        return ['relative' => '', 'absolute' => '', 'url' => ''];
      }
    }

    if (!function_exists('exec')) {
      $message = 'exec unavailable.';
      return ['relative' => '', 'absolute' => '', 'url' => ''];
    }

    $convertCmd = null;
    @exec('magick -version 2>&1', $out, $code);
    if (isset($code) && $code === 0) {
      $convertCmd = 'magick';
    } else {
      @exec('convert -version 2>&1', $out2, $code2);
      if (isset($code2) && $code2 === 0) {
        $convertCmd = 'convert';
      }
    }

    if ($convertCmd === null) {
      $message = 'ImageMagick CLI not available.';
      return ['relative' => '', 'absolute' => '', 'url' => ''];
    }

    $safeBase = preg_replace('/[^a-zA-Z0-9_\-]/', '_', (string)pathinfo($relativeSource, PATHINFO_FILENAME));
    if ($safeBase === '') {
      $safeBase = 'preview';
    }
    $hash = substr(md5($sourceAbsolute . '|' . @filemtime($sourceAbsolute)), 0, 10);
    $destRel = file_viewer_test_relative_dir() . '/previews/' . $safeBase . '_' . $hash . '.jpg';
    $destAbs = rtrim((string)PROJECT_ROOT, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($destRel, '/'));
    $destDir = dirname($destAbs);
    if (!is_dir($destDir) && !@mkdir($destDir, 0775, true) && !is_dir($destDir)) {
      $message = 'Could not create preview directory.';
      return ['relative' => '', 'absolute' => '', 'url' => ''];
    }

    // Build resize argument (keep aspect ratio, limit by width)
    $resizeArg = escapeshellarg($maxWidth . 'x');
    if ($convertCmd === 'magick') {
      $cmd = 'magick ' . escapeshellarg($sourceAbsolute) . ' -resize ' . $maxWidth . 'x -strip ' . escapeshellarg($destAbs);
    } else {
      $cmd = 'convert ' . escapeshellarg($sourceAbsolute) . ' -resize ' . $maxWidth . 'x -strip ' . escapeshellarg($destAbs);
    }

    @exec($cmd . ' 2>&1', $convOut, $convCode);
    if (!empty($convCode) && $convCode !== 0) {
      $message = 'CLI conversion failed.' . (is_array($convOut) ? (' ' . implode('\n', $convOut)) : '');
      return ['relative' => '', 'absolute' => '', 'url' => ''];
    }

    if (!is_file($destAbs)) {
      $message = 'Converted preview not found.';
      return ['relative' => '', 'absolute' => '', 'url' => ''];
    }

    return ['relative' => $destRel, 'absolute' => $destAbs, 'url' => file_viewer_relative_to_url($destRel)];
  }
}

if (!function_exists('file_viewer_generate_video_mp4_cli')) {
  function file_viewer_generate_video_mp4_cli($sourceRawPath, &$message = '')
  {
    $relativeSource = file_viewer_raw_to_upload_relative($sourceRawPath);
    if ($relativeSource === '') {
      $message = 'Source path invalid.';
      return ['relative' => '', 'absolute' => '', 'url' => ''];
    }

    $sourceAbsolute = file_viewer_relative_to_absolute($relativeSource);
    if ($sourceAbsolute === '' || !is_file($sourceAbsolute)) {
      $sourceAbsolute = rtrim((string)PROJECT_ROOT, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($relativeSource, '/'));
      if (!is_file($sourceAbsolute)) {
        $message = 'Source missing.';
        return ['relative' => '', 'absolute' => '', 'url' => ''];
      }
    }

    if (!function_exists('exec')) {
      $message = 'exec unavailable.';
      return ['relative' => '', 'absolute' => '', 'url' => ''];
    }

    @exec('ffmpeg -version 2>&1', $ffout, $ffcode);
    if (!isset($ffcode) || $ffcode !== 0) {
      $message = 'ffmpeg not available.';
      return ['relative' => '', 'absolute' => '', 'url' => ''];
    }

    $safeBase = preg_replace('/[^a-zA-Z0-9_\-]/', '_', (string)pathinfo($relativeSource, PATHINFO_FILENAME));
    if ($safeBase === '') {
      $safeBase = 'video';
    }
    $hash = substr(md5($sourceAbsolute . '|' . @filemtime($sourceAbsolute)), 0, 10);
    $destRel = file_viewer_test_relative_dir() . '/previews/' . $safeBase . '_' . $hash . '.mp4';
    $destAbs = rtrim((string)PROJECT_ROOT, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($destRel, '/'));
    $destDir = dirname($destAbs);
    if (!is_dir($destDir) && !@mkdir($destDir, 0775, true) && !is_dir($destDir)) {
      $message = 'Could not create preview directory.';
      return ['relative' => '', 'absolute' => '', 'url' => ''];
    }

    // If already exists and is newer than source, reuse
    if (is_file($destAbs) && @filemtime($destAbs) >= @filemtime($sourceAbsolute)) {
      return ['relative' => $destRel, 'absolute' => $destAbs, 'url' => file_viewer_relative_to_url($destRel)];
    }

    // Transcode to H.264/AAC MP4 for broad browser support
    $cmd = 'ffmpeg -hide_banner -loglevel error -y -i ' . escapeshellarg($sourceAbsolute) . ' -c:v libx264 -preset veryfast -crf 23 -c:a aac -b:a 128k -movflags +faststart ' . escapeshellarg($destAbs);
    @exec($cmd . ' 2>&1', $out, $code);
    if (!isset($code) || $code !== 0) {
      $message = 'ffmpeg failed to transcode.';
      return ['relative' => '', 'absolute' => '', 'url' => ''];
    }

    if (!is_file($destAbs)) {
      $message = 'Transcoded file not found.';
      return ['relative' => '', 'absolute' => '', 'url' => ''];
    }

    return ['relative' => $destRel, 'absolute' => $destAbs, 'url' => file_viewer_relative_to_url($destRel)];
  }
}

$file = trim((string)($_GET['file'] ?? ''));
$resourceKind = strtolower(trim((string)($_GET['kind'] ?? '')));
$resourceId = (int)($_GET['id'] ?? 0);
$versionFileId = (int)($_GET['version_id'] ?? 0);
$projectId = (int)($_GET['project_id'] ?? 0);
$forcedView = strtolower(trim((string)($_GET['view'] ?? '')));
$extHint = strtolower(trim((string)($_GET['ext'] ?? '')));
$stereoLeftFile = '';
$stereoRightFile = '';
$fileName = $file !== '' ? basename($file) : 'N/A';
$projectName = 'Unknown Project';
$version = 'v1';
$status = 'under_review';
$uploadedAt = '';
$filePath = '';
$storagePath = '';
$resourceStreamUrl = '';
$projectLibrary = [];
$projectLibraryVersionMap = [];
$selectedFileGroupKey = '';
$fileVersionButtons = [];
$fileVersionSelectedId = 0;
$hasProjectFilesRevisionGroup = false;
$hasProjectFilesRevisionNo = false;

if (db_connected()) {
  $hasProjectFilesRevisionGroup = function_exists('db_column_exists') ? db_column_exists('project_files', 'revision_group') : false;
  $hasProjectFilesRevisionNo = function_exists('db_column_exists') ? db_column_exists('project_files', 'revision_no') : false;
}

if (db_connected() && $projectId > 0) {
  $revisionGroupSelect = $hasProjectFilesRevisionGroup ? 'COALESCE(revision_group, \'\') AS revision_group' : "'' AS revision_group";
  $revisionNoSelect = $hasProjectFilesRevisionNo ? 'COALESCE(revision_no, 1) AS revision_no' : '1 AS revision_no';
  $projectFilesRows = db_fetch_all('SELECT id, name, type, file_path, storage_path, uploaded_at, ' . $revisionGroupSelect . ', ' . $revisionNoSelect . ' FROM project_files WHERE project_id = ? ORDER BY uploaded_at DESC', [$projectId]);
  foreach ($projectFilesRows as $item) {
    $name = (string)($item['name'] ?? 'File');
    $filePathCandidate = (string)($item['file_path'] ?? '');
    $typeCandidate = strtolower(trim((string)($item['type'] ?? '')));
    $extCandidate = $typeCandidate !== '' ? $typeCandidate : strtolower((string)pathinfo(($filePathCandidate !== '' ? $filePathCandidate : $name), PATHINFO_EXTENSION));
    $projectLibrary[] = [
      'kind' => 'file',
      'id' => (int)($item['id'] ?? 0),
      'name' => $name,
      'uploaded_at' => (string)($item['uploaded_at'] ?? ''),
      'ext' => $extCandidate,
      'status' => '',
      'version' => '',
      'revision_group' => (string)($item['revision_group'] ?? ''),
      'revision_no' => (int)($item['revision_no'] ?? 1),
    ];
  }

  $projectDrawingRows = db_fetch_all('SELECT id, name, version, status, file_path, uploaded_at FROM project_drawings WHERE project_id = ? ORDER BY uploaded_at DESC', [$projectId]);
  foreach ($projectDrawingRows as $item) {
    $name = (string)($item['name'] ?? 'Drawing');
    $filePathCandidate = (string)($item['file_path'] ?? '');
    $extCandidate = strtolower((string)pathinfo(($filePathCandidate !== '' ? $filePathCandidate : $name), PATHINFO_EXTENSION));
    $projectLibrary[] = [
      'kind' => 'drawing',
      'id' => (int)($item['id'] ?? 0),
      'name' => $name,
      'uploaded_at' => (string)($item['uploaded_at'] ?? ''),
      'ext' => $extCandidate,
      'status' => (string)($item['status'] ?? ''),
      'version' => (string)($item['version'] ?? ''),
    ];
  }

  usort($projectLibrary, function ($a, $b) {
    $ta = strtotime((string)($a['uploaded_at'] ?? '')) ?: 0;
    $tb = strtotime((string)($b['uploaded_at'] ?? '')) ?: 0;
    if ($ta === $tb) {
      return 0;
    }
    return $ta > $tb ? -1 : 1;
  });
} elseif (db_connected() && $projectId <= 0) {
  $revisionGroupSelect = $hasProjectFilesRevisionGroup ? 'COALESCE(pf.revision_group, \'\') AS revision_group' : "'' AS revision_group";
  $revisionNoSelect = $hasProjectFilesRevisionNo ? 'COALESCE(pf.revision_no, 1) AS revision_no' : '1 AS revision_no';
  $globalFiles = db_fetch_all('SELECT pf.id, pf.project_id, pf.name, pf.type, pf.uploaded_at, p.name AS project_name, ' . $revisionGroupSelect . ', ' . $revisionNoSelect . ' FROM project_files pf LEFT JOIN projects p ON p.id = pf.project_id ORDER BY pf.uploaded_at DESC LIMIT 200');
  foreach ($globalFiles as $item) {
    $projectLibrary[] = [
      'kind' => 'file',
      'id' => (int)($item['id'] ?? 0),
      'name' => (string)($item['name'] ?? 'File'),
      'uploaded_at' => (string)($item['uploaded_at'] ?? ''),
      'ext' => strtolower(trim((string)($item['type'] ?? ''))),
      'status' => '',
      'version' => '',
      'project_id' => (int)($item['project_id'] ?? 0),
      'project_name' => (string)($item['project_name'] ?? ''),
      'revision_group' => (string)($item['revision_group'] ?? ''),
      'revision_no' => (int)($item['revision_no'] ?? 1),
    ];
  }

  $globalDrawings = db_fetch_all('SELECT pd.id, pd.project_id, pd.name, pd.version, pd.status, pd.file_path, pd.uploaded_at, p.name AS project_name FROM project_drawings pd LEFT JOIN projects p ON p.id = pd.project_id ORDER BY pd.uploaded_at DESC LIMIT 200');
  foreach ($globalDrawings as $item) {
    $projectLibrary[] = [
      'kind' => 'drawing',
      'id' => (int)($item['id'] ?? 0),
      'name' => (string)($item['name'] ?? 'Drawing'),
      'uploaded_at' => (string)($item['uploaded_at'] ?? ''),
      'ext' => strtolower((string)pathinfo((string)($item['file_path'] ?? ''), PATHINFO_EXTENSION)),
      'status' => (string)($item['status'] ?? ''),
      'version' => (string)($item['version'] ?? ''),
      'project_id' => (int)($item['project_id'] ?? 0),
      'project_name' => (string)($item['project_name'] ?? ''),
    ];
  }

  usort($projectLibrary, function ($a, $b) {
    $ta = strtotime((string)($a['uploaded_at'] ?? '')) ?: 0;
    $tb = strtotime((string)($b['uploaded_at'] ?? '')) ?: 0;
    if ($ta === $tb) {
      return 0;
    }
    return $ta > $tb ? -1 : 1;
  });
}

if ($projectId > 0 && $resourceId <= 0 && $file === '' && !empty($projectLibrary)) {
  $firstEntry = $projectLibrary[0];
  $resourceKind = (string)($firstEntry['kind'] ?? 'file');
  $resourceId = (int)($firstEntry['id'] ?? 0);
  if ($extHint === '') {
    $extHint = strtolower((string)($firstEntry['ext'] ?? ''));
  }
}

if (!empty($projectLibrary)) {
  $collapsedLibrary = [];
  $seenFileGroups = [];
  $nameToGroupMap = [];

  foreach ($projectLibrary as $entry) {
    $entryKind = (string)($entry['kind'] ?? 'file');
    if ($entryKind !== 'file') {
      continue;
    }
    $entryProjectId = (int)($entry['project_id'] ?? $projectId);
    $entryNameKey = strtolower(trim((string)($entry['name'] ?? '')));
    $entryNameKey = preg_replace('/\s+/', ' ', $entryNameKey);
    $entryGroup = trim((string)($entry['revision_group'] ?? ''));
    if ($entryGroup !== '' && $entryNameKey !== '') {
      $nameToGroupMap[$entryProjectId . '|' . $entryNameKey] = $entryGroup;
    }
  }

  foreach ($projectLibrary as $entry) {
    $entryKind = (string)($entry['kind'] ?? 'file');
    if ($entryKind !== 'file') {
      $collapsedLibrary[] = $entry;
      continue;
    }

    $entryProjectId = (int)($entry['project_id'] ?? $projectId);
    $entryNameKey = strtolower(trim((string)($entry['name'] ?? '')));
    $entryNameKey = preg_replace('/\s+/', ' ', $entryNameKey);
    $entryGroup = trim((string)($entry['revision_group'] ?? ''));
    if ($entryGroup === '') {
      $entryGroup = (string)($nameToGroupMap[$entryProjectId . '|' . $entryNameKey] ?? $entryNameKey);
    }
    $entryGroup = preg_replace('/\s+/', ' ', (string)$entryGroup);
    $groupKey = $entryProjectId . '|' . $entryGroup;

    if (!isset($projectLibraryVersionMap[$groupKey])) {
      $projectLibraryVersionMap[$groupKey] = [];
    }
    $projectLibraryVersionMap[$groupKey][] = [
      'id' => (int)($entry['id'] ?? 0),
      'revision_no' => (int)($entry['revision_no'] ?? 1),
      'uploaded_at' => (string)($entry['uploaded_at'] ?? ''),
      'ext' => strtolower((string)($entry['ext'] ?? '')),
      'project_id' => $entryProjectId,
    ];

    if (isset($seenFileGroups[$groupKey])) {
      continue;
    }

    $seenFileGroups[$groupKey] = true;
    $entry['group_key'] = $groupKey;
    $collapsedLibrary[] = $entry;
  }

  foreach ($projectLibraryVersionMap as $mapKey => $versions) {
    usort($versions, function ($a, $b) {
      $ra = (int)($a['revision_no'] ?? 1);
      $rb = (int)($b['revision_no'] ?? 1);
      if ($ra !== $rb) {
        return $rb <=> $ra;
      }
      $ta = strtotime((string)($a['uploaded_at'] ?? '')) ?: 0;
      $tb = strtotime((string)($b['uploaded_at'] ?? '')) ?: 0;
      if ($ta !== $tb) {
        return $tb <=> $ta;
      }
      return ((int)($b['id'] ?? 0)) <=> ((int)($a['id'] ?? 0));
    });
    $projectLibraryVersionMap[$mapKey] = $versions;
  }

  $projectLibrary = $collapsedLibrary;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $vrAction = trim((string)($_POST['vr_action'] ?? ''));
  if ($vrAction === 'save_settings') {
    $device = trim((string)($_POST['vr_device'] ?? 'Custom'));
    $screensize = trim((string)($_POST['vr_screensize'] ?? '6.1'));
    $ipd = trim((string)($_POST['vr_ipd'] ?? '63.5'));
    $headset = trim((string)($_POST['vr_headset'] ?? 'No Distortion'));
    $custom = trim((string)($_POST['vr_custom'] ?? ''));
    $settings = [
      'device' => $device,
      'screensize' => $screensize,
      'ipd' => $ipd,
      'headset' => $headset,
      'custom' => $custom,
    ];
    file_viewer_write_vr_settings($settings);
    $notice = 'VR settings saved.';
    $noticeType = 'success';
    $redirectParams = [];
    if ($projectId > 0) {
      $redirectParams['project_id'] = (string)$projectId;
    }
    if ($file !== '') {
      $redirectParams['file'] = (string)$file;
    }
    $redirectParams['notice'] = $notice;
    $redirectParams['notice_type'] = $noticeType;
    header('Location: ' . $_SERVER['PHP_SELF'] . (empty($redirectParams) ? '' : ('?' . http_build_query($redirectParams))));
    exit;
  }
  $postAction = trim((string)($_POST['test_action'] ?? ''));
  $redirectFile = $file;
  $redirectView = $forcedView;
  $redirectKind = $resourceKind;
  $redirectId = $resourceId;
  $redirectExt = $extHint;

  if ($postAction === 'upload_test_file' && isset($_FILES['test_file'])) {
    $targetDir = file_viewer_test_absolute_dir();
    if (!is_dir($targetDir) && !@mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
      $notice = 'Could not prepare upload directory.';
      $noticeType = 'error';
    } else {
      $upload = $_FILES['test_file'];
      $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'glb', 'gltf', 'mp4', 'webm', 'ogg', 'obj'];
      $maxBytes = 200 * 1024 * 1024;

      $uploadErrorText = '';
      $stored = file_viewer_store_test_upload($upload, $targetDir, $allowed, $maxBytes, $uploadErrorText);
      if (!is_array($stored)) {
        $notice = $uploadErrorText !== '' ? $uploadErrorText : 'Could not save uploaded file.';
        $noticeType = 'error';
      } else {
        $notice = 'File uploaded successfully.';
        $noticeType = 'success';
        $redirectFile = (string)$stored['relative'];
        if (in_array((string)$stored['ext'], ['jpg', 'jpeg', 'png', 'webp'], true)) {
          $detectedEye = file_viewer_detect_eye_from_filename((string)$stored['name']);
          if ($detectedEye !== '') {
            $state = file_viewer_load_stereo_state();
            $leftState = str_replace('\\', '/', trim((string)($state['left'] ?? '')));
            $rightState = str_replace('\\', '/', trim((string)($state['right'] ?? '')));

            if ($detectedEye === 'left') {
              $leftState = (string)$stored['relative'];
              $candidate = file_viewer_find_matching_eye_file($targetDir, (string)$stored['name'], 'left');
              if ($candidate !== '') {
                $rightState = $candidate;
              }
            } else {
              $rightState = (string)$stored['relative'];
              $candidate = file_viewer_find_matching_eye_file($targetDir, (string)$stored['name'], 'right');
              if ($candidate !== '') {
                $leftState = $candidate;
                $redirectFile = $leftState;
              }
            }

            file_viewer_write_stereo_state($leftState, $rightState);
            if ($leftState !== '' && $rightState !== '') {
              $notice = 'Uploaded and paired for stereo VR.';
            } else {
              $notice = 'Uploaded ' . $detectedEye . '-eye image. Upload matching ' . ($detectedEye === 'left' ? 'right' : 'left') . '-eye image to complete stereo.';
            }
            $redirectView = '360';
          } elseif (preg_match('/(^|[_\-\s])(360|pano|panorama|equirect)/i', (string)$stored['name'])) {
            $redirectView = '360';
          }
        }
        file_viewer_append_history('upload', (string)$stored['relative'], 'saved', [
          'size' => (int)$stored['size'],
          'mime' => (string)$stored['mime'],
        ]);
      }
    }
  } elseif ($postAction === 'delete_test_file') {
    $relativePath = str_replace('\\', '/', trim((string)($_POST['relative_path'] ?? '')));
    $prefix = file_viewer_test_relative_dir() . '/';
    if ($relativePath === '' || strpos($relativePath, $prefix) !== 0 || strpos($relativePath, '..') !== false) {
      $notice = 'Invalid file path.';
      $noticeType = 'error';
    } else {
      $absolutePath = rtrim((string)PROJECT_ROOT, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
      $deleted = is_file($absolutePath) ? @unlink($absolutePath) : false;
      if ($deleted) {
        $notice = 'File deleted.';
        $noticeType = 'success';
        file_viewer_append_history('delete', $relativePath, 'deleted');
        if ($file === $relativePath) {
          $redirectFile = '';
          $redirectView = '';
        }
        $state = file_viewer_load_stereo_state();
        $stateLeft = str_replace('\\', '/', trim((string)($state['left'] ?? '')));
        $stateRight = str_replace('\\', '/', trim((string)($state['right'] ?? '')));
        if ($stateLeft === $relativePath || $stateRight === $relativePath) {
          if ($stateLeft === $relativePath) {
            $stateLeft = '';
          }
          if ($stateRight === $relativePath) {
            $stateRight = '';
          }
          file_viewer_write_stereo_state($stateLeft, $stateRight);
        }
      } else {
        $notice = 'Could not delete file.';
        $noticeType = 'error';
      }
    }
  } elseif ($postAction === 'generate_cad_preview') {
    $sourceRawPath = trim((string)($_POST['source_raw_path'] ?? ''));
    $sourceExt = trim((string)($_POST['source_ext'] ?? ''));
    $cadMessage = '';
    $generated = file_viewer_generate_local_cad_preview($sourceRawPath, $sourceExt, $cadMessage);
    $notice = $generated ? 'Local CAD preview is ready.' : ('Could not generate local CAD preview. ' . $cadMessage);
    $noticeType = $generated ? 'success' : 'error';
  }

  $params = [];
  if ($redirectFile !== '') {
    $params['file'] = $redirectFile;
  }
  if ($projectId > 0) {
    $params['project_id'] = (string)$projectId;
  }
  if ($redirectView !== '') {
    $params['view'] = $redirectView;
  }
  if (in_array($redirectKind, ['file', 'drawing'], true) && $redirectId > 0) {
    $params['kind'] = $redirectKind;
    $params['id'] = (string)$redirectId;
  }
  if ($redirectExt !== '') {
    $params['ext'] = $redirectExt;
  }
  if ($notice !== '') {
    $params['notice'] = $notice;
    $params['notice_type'] = $noticeType;
  }
  header('Location: ' . $_SERVER['PHP_SELF'] . (empty($params) ? '' : ('?' . http_build_query($params))));
  exit;
}

$notice = trim((string)($_GET['notice'] ?? $notice));
$noticeType = trim((string)($_GET['notice_type'] ?? $noticeType));

if (!function_exists('resolve_preview_url')) {
  function resolve_preview_url($rawPath)
  {
    $value = trim((string)$rawPath);
    if ($value === '') {
      return '';
    }
    if (preg_match('/^https?:\/\//i', $value)) {
      return $value;
    }

    $normalized = str_replace('\\', '/', $value);
    $uploadsPos = strpos($normalized, '/uploads/');
    if ($uploadsPos === false && strpos($normalized, 'uploads/') === 0) {
      $uploadsPos = 0;
    }

    if ($uploadsPos === false) {
      return '';
    }

    $relative = $uploadsPos === 0 ? $normalized : substr($normalized, $uploadsPos + 1);
    $relative = ltrim($relative, '/');
    return function_exists('base_path') ? (string)base_path($relative) : '/' . $relative;
  }
}

if (!function_exists('resolve_preview_absolute_path')) {
  function resolve_preview_absolute_path($rawPath)
  {
    $value = trim((string)$rawPath);
    if ($value === '' || preg_match('/^https?:\/\//i', $value)) {
      return '';
    }

    $normalized = str_replace('\\', '/', $value);
    $uploadsPos = strpos($normalized, '/uploads/');
    if ($uploadsPos === false && strpos($normalized, 'uploads/') === 0) {
      $uploadsPos = 0;
    }
    if ($uploadsPos === false) {
      return '';
    }

    $relative = $uploadsPos === 0 ? $normalized : substr($normalized, $uploadsPos + 1);
    $relative = ltrim($relative, '/');
    $absolute = rtrim((string)PROJECT_ROOT, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    return is_file($absolute) ? $absolute : '';
  }
}

if (!function_exists('file_viewer_absolute_url')) {
  function file_viewer_absolute_url($url)
  {
    $value = trim((string)$url);
    if ($value === '') {
      return '';
    }
    if (preg_match('/^https?:\/\//i', $value)) {
      return $value;
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = trim((string)($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '') {
      return $value;
    }
    return $scheme . '://' . $host . '/' . ltrim($value, '/');
  }
}

if (db_connected()) {
  $hasProjectFilesStoragePath = function_exists('db_column_exists') ? db_column_exists('project_files', 'storage_path') : false;
  $row = null;
  if ($resourceId > 0 && in_array($resourceKind, ['file', 'drawing'], true)) {
    $resourceStreamUrl = rtrim((string)BASE_PATH, '/') . '/dashboard/file_stream.php?kind=' . rawurlencode($resourceKind) . '&id=' . (int)$resourceId;
    if ($resourceKind === 'file') {
      $projectFilterSql = $projectId > 0 ? ' AND pf.project_id = ' . (int)$projectId . ' ' : ' ';
      $storageSelect = $hasProjectFilesStoragePath ? 'pf.storage_path AS storage_path' : "'' AS storage_path";
      $revisionGroupSelect = $hasProjectFilesRevisionGroup ? 'COALESCE(pf.revision_group, \'\') AS revision_group' : "'' AS revision_group";
      $revisionNoSelect = $hasProjectFilesRevisionNo ? 'COALESCE(pf.revision_no, 1) AS revision_no' : '1 AS revision_no';
      $row = db_fetch("SELECT pf.id, pf.project_id, pf.name, pf.uploaded_at, p.name AS project_name, pf.file_path, " . $storageSelect . ", " . $revisionGroupSelect . ", " . $revisionNoSelect . "
            FROM project_files pf
            LEFT JOIN projects p ON p.id = pf.project_id
            WHERE pf.id = ? " . $projectFilterSql . "
            ORDER BY pf.uploaded_at DESC LIMIT 1", [$resourceId]);
    } else {
      $projectFilterSql = $projectId > 0 ? ' AND pd.project_id = ' . (int)$projectId . ' ' : ' ';
      $row = db_fetch("SELECT pd.name, pd.version, pd.status, pd.uploaded_at, p.name AS project_name, pd.file_path
            FROM project_drawings pd
            LEFT JOIN projects p ON p.id = pd.project_id
            WHERE pd.id = ? " . $projectFilterSql . "
            ORDER BY pd.uploaded_at DESC LIMIT 1", [$resourceId]);
    }
  }

  // Fallback lookup via direct PDO in case helper-level query fails unexpectedly.
  if (!$row && $resourceId > 0 && in_array($resourceKind, ['file', 'drawing'], true)) {
    $db = get_db();
    if ($db instanceof PDO) {
      try {
        if ($resourceKind === 'file') {
          if ($projectId > 0) {
            if ($hasProjectFilesStoragePath) {
              $revisionGroupSelect = $hasProjectFilesRevisionGroup ? 'COALESCE(revision_group, \'\') AS revision_group' : "'' AS revision_group";
              $revisionNoSelect = $hasProjectFilesRevisionNo ? 'COALESCE(revision_no, 1) AS revision_no' : '1 AS revision_no';
              $stmt = $db->prepare('SELECT id, project_id, name, uploaded_at, file_path, COALESCE(NULLIF(storage_path,\'\'), file_path) AS storage_path, ' . $revisionGroupSelect . ', ' . $revisionNoSelect . ' FROM project_files WHERE id = ? AND project_id = ? LIMIT 1');
              $stmt->execute([$resourceId, $projectId]);
            } else {
              $revisionGroupSelect = $hasProjectFilesRevisionGroup ? 'COALESCE(revision_group, \'\') AS revision_group' : "'' AS revision_group";
              $revisionNoSelect = $hasProjectFilesRevisionNo ? 'COALESCE(revision_no, 1) AS revision_no' : '1 AS revision_no';
              $stmt = $db->prepare('SELECT id, project_id, name, uploaded_at, file_path, file_path AS storage_path, ' . $revisionGroupSelect . ', ' . $revisionNoSelect . ' FROM project_files WHERE id = ? AND project_id = ? LIMIT 1');
              $stmt->execute([$resourceId, $projectId]);
            }
          } else {
            if ($hasProjectFilesStoragePath) {
              $revisionGroupSelect = $hasProjectFilesRevisionGroup ? 'COALESCE(revision_group, \'\') AS revision_group' : "'' AS revision_group";
              $revisionNoSelect = $hasProjectFilesRevisionNo ? 'COALESCE(revision_no, 1) AS revision_no' : '1 AS revision_no';
              $stmt = $db->prepare('SELECT id, project_id, name, uploaded_at, file_path, COALESCE(NULLIF(storage_path,\'\'), file_path) AS storage_path, ' . $revisionGroupSelect . ', ' . $revisionNoSelect . ' FROM project_files WHERE id = ? LIMIT 1');
              $stmt->execute([$resourceId]);
            } else {
              $revisionGroupSelect = $hasProjectFilesRevisionGroup ? 'COALESCE(revision_group, \'\') AS revision_group' : "'' AS revision_group";
              $revisionNoSelect = $hasProjectFilesRevisionNo ? 'COALESCE(revision_no, 1) AS revision_no' : '1 AS revision_no';
              $stmt = $db->prepare('SELECT id, project_id, name, uploaded_at, file_path, file_path AS storage_path, ' . $revisionGroupSelect . ', ' . $revisionNoSelect . ' FROM project_files WHERE id = ? LIMIT 1');
              $stmt->execute([$resourceId]);
            }
          }
        } else {
          if ($projectId > 0) {
            $stmt = $db->prepare('SELECT name, uploaded_at, file_path, file_path AS storage_path FROM project_drawings WHERE id = ? AND project_id = ? LIMIT 1');
            $stmt->execute([$resourceId, $projectId]);
          } else {
            $stmt = $db->prepare('SELECT name, uploaded_at, file_path, file_path AS storage_path FROM project_drawings WHERE id = ? LIMIT 1');
            $stmt->execute([$resourceId]);
          }
        }
        $fallback = $stmt->fetch(PDO::FETCH_ASSOC);
        if (is_array($fallback)) {
          $fallback['project_name'] = $projectName;
          $row = $fallback;
        }
      } catch (Throwable $e) {
        // Keep existing behavior if fallback lookup also fails.
      }
    }
  }

  if (!$row && $file !== '') {
    $projectFilterSql = $projectId > 0 ? ' AND pf.project_id = ' . (int)$projectId . ' ' : ' ';
    $storageSelect = $hasProjectFilesStoragePath ? 'pf.storage_path AS storage_path' : "'' AS storage_path";
    $storageWhere = $hasProjectFilesStoragePath ? ' OR pf.storage_path = ? ' : ' ';
    $params = [$file, $file];
    if ($hasProjectFilesStoragePath) {
      $params[] = $file;
    }
    $revisionGroupSelect = $hasProjectFilesRevisionGroup ? 'COALESCE(pf.revision_group, \'\') AS revision_group' : "'' AS revision_group";
    $revisionNoSelect = $hasProjectFilesRevisionNo ? 'COALESCE(pf.revision_no, 1) AS revision_no' : '1 AS revision_no';
    $row = db_fetch("SELECT pf.id, pf.project_id, pf.name, pf.uploaded_at, p.name AS project_name, pf.file_path, " . $storageSelect . ", " . $revisionGroupSelect . ", " . $revisionNoSelect . "
          FROM project_files pf
          LEFT JOIN projects p ON p.id = pf.project_id
          WHERE pf.name = ? OR pf.file_path = ? " . $storageWhere . "
      " . $projectFilterSql . "
          ORDER BY pf.uploaded_at DESC LIMIT 1", $params);

    if (!$row) {
      $projectFilterSql = $projectId > 0 ? ' AND pd.project_id = ' . (int)$projectId . ' ' : ' ';
      $row = db_fetch("SELECT pd.name, pd.version, pd.status, pd.uploaded_at, p.name AS project_name, pd.file_path
              FROM project_drawings pd
              LEFT JOIN projects p ON p.id = pd.project_id
              WHERE pd.file_path = ? OR pd.name = ?
        " . $projectFilterSql . "
              ORDER BY pd.uploaded_at DESC LIMIT 1", [$file, $file]);
    }
  }

  if ($row && $resourceKind === 'file') {
    $selectedProjectId = (int)($row['project_id'] ?? $projectId);
    $selectedName = (string)($row['name'] ?? '');
    $selectedGroup = trim((string)($row['revision_group'] ?? ''));
    $selectedFileGroupKey = $selectedProjectId . '|' . ($selectedGroup !== '' ? $selectedGroup : strtolower(trim($selectedName)));
    $storageSelectSql = $hasProjectFilesStoragePath ? 'COALESCE(NULLIF(storage_path,\'\'), file_path) AS storage_path' : 'file_path AS storage_path';
    $revisionNoSelectSql = $hasProjectFilesRevisionNo ? 'COALESCE(revision_no, 1) AS revision_no' : '1 AS revision_no';

    if ($selectedProjectId > 0) {
      if ($hasProjectFilesRevisionGroup && $selectedGroup !== '') {
        $fileVersionButtons = db_fetch_all('SELECT id, name, uploaded_at, file_path, ' . $storageSelectSql . ', ' . $revisionNoSelectSql . ' FROM project_files WHERE project_id = ? AND revision_group = ? ORDER BY revision_no DESC, uploaded_at DESC, id DESC', [$selectedProjectId, $selectedGroup]);
      } elseif ($selectedName !== '') {
        $fileVersionButtons = db_fetch_all('SELECT id, name, uploaded_at, file_path, ' . $storageSelectSql . ', ' . $revisionNoSelectSql . ' FROM project_files WHERE project_id = ? AND name = ? ORDER BY revision_no DESC, uploaded_at DESC, id DESC', [$selectedProjectId, $selectedName]);
      }
    }

    if (!empty($fileVersionButtons)) {
      $selectedVersionRow = $fileVersionButtons[0];
      if ($versionFileId > 0) {
        foreach ($fileVersionButtons as $versionRow) {
          if ((int)($versionRow['id'] ?? 0) === $versionFileId) {
            $selectedVersionRow = $versionRow;
            break;
          }
        }
      }

      $row['id'] = (int)($selectedVersionRow['id'] ?? $row['id'] ?? $resourceId);
      $row['name'] = (string)($selectedVersionRow['name'] ?? $row['name'] ?? '');
      $row['uploaded_at'] = (string)($selectedVersionRow['uploaded_at'] ?? $row['uploaded_at'] ?? '');
      $row['file_path'] = (string)($selectedVersionRow['file_path'] ?? $row['file_path'] ?? '');
      $row['storage_path'] = (string)($selectedVersionRow['storage_path'] ?? $row['storage_path'] ?? '');
      $row['revision_no'] = (int)($selectedVersionRow['revision_no'] ?? 1);

      $resourceId = (int)($row['id'] ?? $resourceId);
      $fileVersionSelectedId = $resourceId;
      $version = 'v' . (int)($row['revision_no'] ?? 1);
    }
  }

  if ($row) {
    $fileName = (string)($row['name'] ?? $fileName);
    $projectName = (string)($row['project_name'] ?? $projectName);
    $uploadedAt = (string)($row['uploaded_at'] ?? '');
    $filePath = (string)($row['file_path'] ?? '');
    $storagePath = (string)($row['storage_path'] ?? '');
  }
}

$testHistory = array_reverse(file_viewer_load_history());
$testFiles = [];
foreach ($testHistory as $entry) {
  $candidate = str_replace('\\', '/', trim((string)($entry['file'] ?? '')));
  if ($candidate === '' || isset($testFiles[$candidate])) {
    continue;
  }
  $absoluteCandidate = rtrim((string)PROJECT_ROOT, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $candidate);
  if (!is_file($absoluteCandidate)) {
    continue;
  }
  $testFiles[$candidate] = [
    'relative' => $candidate,
    'name' => basename($candidate),
    'size' => (int)@filesize($absoluteCandidate),
    'mtime' => (int)@filemtime($absoluteCandidate),
  ];
}

$previewUrl = '';
$previewAbsolutePath = '';
$previewDirectUrl = '';
$previewAbsoluteUrl = '';
foreach ([$storagePath, $filePath, $file] as $candidate) {
  $resolvedUrl = resolve_preview_url($candidate);
  if ($resolvedUrl === '') {
    continue;
  }
  if ($previewDirectUrl === '') {
    $previewDirectUrl = $resolvedUrl;
    $previewAbsoluteUrl = file_viewer_absolute_url($resolvedUrl);
  }
  $previewUrl = $resolvedUrl;
  $previewAbsolutePath = resolve_preview_absolute_path($candidate);
  break;
}

if ($previewUrl === '' && $resourceStreamUrl !== '') {
  $previewUrl = $resourceStreamUrl;
}

$stereoLeftUrl = '';
$stereoRightUrl = '';
$stereoLeftAbsolutePath = '';
$stereoRightAbsolutePath = '';

$state = file_viewer_load_stereo_state();
$stereoLeftFile = str_replace('\\', '/', trim((string)($state['left'] ?? '')));
$stereoRightFile = str_replace('\\', '/', trim((string)($state['right'] ?? '')));

if ($stereoLeftFile !== '' && $stereoRightFile !== '') {
  $candidateLeftUrl = resolve_preview_url($stereoLeftFile);
  $candidateRightUrl = resolve_preview_url($stereoRightFile);
  $candidateLeftAbs = resolve_preview_absolute_path($stereoLeftFile);
  $candidateRightAbs = resolve_preview_absolute_path($stereoRightFile);
  $leftExt = strtolower((string)pathinfo($stereoLeftFile, PATHINFO_EXTENSION));
  $rightExt = strtolower((string)pathinfo($stereoRightFile, PATHINFO_EXTENSION));

  if ($candidateLeftUrl !== '' && $candidateRightUrl !== '' && $candidateLeftAbs !== '' && $candidateRightAbs !== '' && in_array($leftExt, ['jpg', 'jpeg', 'png', 'webp'], true) && in_array($rightExt, ['jpg', 'jpeg', 'png', 'webp'], true)) {
    $stereoLeftUrl = $candidateLeftUrl;
    $stereoRightUrl = $candidateRightUrl;
    $stereoLeftAbsolutePath = $candidateLeftAbs;
    $stereoRightAbsolutePath = $candidateRightAbs;
  }
}

if ($stereoLeftUrl !== '') {
  $previewUrl = $stereoLeftUrl;
  $previewAbsolutePath = $stereoLeftAbsolutePath;
}

$extensionSource = $filePath !== '' ? $filePath : ($storagePath !== '' ? $storagePath : $fileName);
$ext = strtolower((string)pathinfo((string)$extensionSource, PATHINFO_EXTENSION));
if ($extHint !== '' && preg_match('/^[a-z0-9]{2,8}$/', $extHint)) {
  $ext = $extHint;
}
$isPanoramaName = (bool)preg_match('/(^|[_\-\s])(360|pano|panorama|equirect)/i', (string)$fileName);
$isPanoramaRatio = false;
if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) && $previewAbsolutePath !== '' && function_exists('getimagesize')) {
  $imgInfo = @getimagesize($previewAbsolutePath);
  if (is_array($imgInfo) && !empty($imgInfo[0]) && !empty($imgInfo[1])) {
    $ratio = (float)$imgInfo[0] / (float)$imgInfo[1];
    $isPanoramaRatio = abs($ratio - 2.0) <= 0.2;
  }
}
$is360Suitable = $isPanoramaName || $isPanoramaRatio;

$viewerMode = 'unsupported';
if (in_array($ext, ['glb', 'gltf'], true)) {
  $viewerMode = '3d';
} elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'tif', 'tiff'], true)) {
  $viewerMode = $is360Suitable ? '360' : 'image';
} elseif ($ext === 'pdf') {
  $viewerMode = 'pdf';
} elseif ($ext === 'txt') {
  $viewerMode = 'text';
} elseif (in_array($ext, ['mp4', 'webm', 'ogg', 'avi', 'mov', 'mkv'], true)) {
  $viewerMode = 'video';
}

if ($forcedView === '360' && in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'tif', 'tiff'], true)) {
  $viewerMode = '360';
}

if (in_array($ext, ['dwg', 'skp'], true)) {
  $viewerMode = 'cad';
}

// For GLB/GLTF, prefer extension-preserving direct file URL over stream endpoint.
if ($viewerMode === '3d' && $previewDirectUrl !== '') {
  $previewUrl = $previewDirectUrl;
}

// Prefer serving PDFs via the PHP stream endpoint to avoid direct URL 404s
// when uploads live outside the webserver document root. Use stream when
// available and the preview refers to a PDF.
if ($viewerMode === 'pdf' && $resourceStreamUrl !== '') {
  $previewUrl = $resourceStreamUrl;
  $previewAbsoluteUrl = file_viewer_absolute_url($resourceStreamUrl);
  // leave $previewAbsolutePath as-is for server-side checks, but ensure
  // previewDirectUrl isn't used for PDFs.
  $previewDirectUrl = '';
}

// For video files, attempt to produce a web-friendly MP4 using ffmpeg if available.
if ($viewerMode === 'video') {
  $videoSourceRaw = $storagePath !== '' ? $storagePath : ($filePath !== '' ? $filePath : $file);
  if ($videoSourceRaw !== '') {
    $videoMsg = '';
    // Only attempt transcoding for containers / non-mp4 formats to avoid unnecessary work
    if (!in_array($ext, ['mp4'], true)) {
      $videoGenerated = file_viewer_generate_video_mp4_cli($videoSourceRaw, $videoMsg);
      if (!empty($videoGenerated['url'])) {
        $previewUrl = $videoGenerated['url'];
        $previewAbsolutePath = $videoGenerated['absolute'];
        $previewDirectUrl = '';
      }
    }
  }
}

$cadSourceRawPath = $storagePath !== '' ? $storagePath : ($filePath !== '' ? $filePath : $file);
$cadPreviewPath = file_viewer_cad_preview_paths($cadSourceRawPath);
$cadPreviewExists = $viewerMode === 'cad' && $cadPreviewPath['absolute'] !== '' && is_file((string)$cadPreviewPath['absolute']);
$cadPreviewUrl = $cadPreviewExists ? (string)$cadPreviewPath['url'] : '';

$isStereoPanorama = ($stereoLeftUrl !== '' && $stereoRightUrl !== '');
$vrSettings = file_viewer_load_vr_settings();
?>
<!DOCTYPE html>
<html lang="en" class="bg-canvas-white">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
  <title>File Viewer | Ripal Design</title>
  <?php $HEADER_MODE = 'dashboard';
  require_once __DIR__ . '/../Common/header.php'; ?>
  <?php if ($viewerMode === '360' && $previewUrl !== ''): ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css">
  <?php endif; ?>
  <?php if (($viewerMode === '3d' && $previewUrl !== '') || ($viewerMode === 'cad' && $cadPreviewUrl !== '')): ?>
    <script type="module" src="https://unpkg.com/@google/model-viewer/dist/model-viewer.min.js"></script>
  <?php endif; ?>
  <?php if ($viewerMode === '360' && $previewUrl !== ''): ?>
    <script src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>
  <?php endif; ?>
  <style>
    .viewer-3d-modal {
      position: fixed;
      inset: 0;
      z-index: 9999;
      background: rgba(0, 0, 0, 0.78);
      backdrop-filter: blur(4px);
      display: none;
      align-items: center;
      justify-content: center;
      padding: 16px;
      opacity: 0;
      transition: opacity 220ms ease;
    }

    .viewer-3d-modal.is-open {
      display: flex;
      opacity: 1;
    }

    .viewer-3d-dialog {
      width: min(96vw, 1400px);
      height: min(92vh, 900px);
      background: #111827;
      border: 1px solid rgba(255, 255, 255, 0.2);
      box-shadow: 0 24px 80px rgba(0, 0, 0, 0.35);
      display: flex;
      flex-direction: column;
      overflow: hidden;
      transform: translateY(16px) scale(0.97);
      opacity: 0;
      transition: transform 240ms ease, opacity 240ms ease;
    }

    .viewer-3d-modal.is-open .viewer-3d-dialog {
      transform: translateY(0) scale(1);
      opacity: 1;
    }

    .viewer-360-dialog {
      width: min(96vw, 1500px);
      height: min(92vh, 920px);
      background: #0f172a;
      border: 1px solid rgba(255, 255, 255, 0.2);
      box-shadow: 0 24px 80px rgba(0, 0, 0, 0.35);
      display: flex;
      flex-direction: column;
      overflow: hidden;
      transform: translateY(16px) scale(0.97);
      opacity: 0;
      transition: transform 240ms ease, opacity 240ms ease;
    }

    .viewer-3d-modal.is-open .viewer-360-dialog {
      transform: translateY(0) scale(1);
      opacity: 1;
    }

    .viewer-360-canvas {
      flex: 1;
      min-height: 0;
      background: #020617;
    }

    .viewer-3d-canvas {
      width: 100%;
      height: 100%;
      --progress-bar-color: #94180c;
      --poster-color: #0f172a;
    }

    .viewer-3d-viewport {
      position: relative;
      flex: 1;
      min-height: 0;
      overflow: hidden;
      background: #020617;
    }

    .viewer-3d-error {
      position: absolute;
      inset: 0;
      display: none;
      align-items: center;
      justify-content: center;
      text-align: center;
      padding: 24px;
      background: rgba(2, 6, 23, 0.9);
      color: #e2e8f0;
      z-index: 4;
    }

    .viewer-3d-error.is-visible {
      display: flex;
    }

    .viewer-3d-chip {
      animation: chipPulse 2.6s ease-in-out infinite;
    }

    .vr-mode-modal {
      position: fixed;
      inset: 0;
      z-index: 10000;
      background: #000;
      display: none;
      opacity: 0;
      transition: opacity 200ms ease;
    }

    .vr-mode-modal.is-open {
      display: block;
      opacity: 1;
    }

    .vr-mode-header {
      height: 52px;
      padding: 0 10px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      color: #fff;
      background: rgba(0, 0, 0, 0.72);
      border-bottom: 1px solid rgba(255, 255, 255, 0.15);
    }

    .vr-mode-split {
      height: calc(100vh - 52px);
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0;
      background: #000;
    }

    .vr-eye {
      position: relative;
      overflow: hidden;
      background: #020617;
      padding-top: max(env(safe-area-inset-top), 0px);
      padding-bottom: max(env(safe-area-inset-bottom), 0px);
    }

    .vr-eye model-viewer,
    .vr-eye .vr-pano {
      width: 100%;
      height: 100%;
      display: block;
      background: #000;
      transform: translateY(var(--vr-y-shift, 0px)) scale(var(--vr-scale, 1));
      transform-origin: center center;
    }

    .vr-eye::after {
      content: '';
      position: absolute;
      inset: 0;
      pointer-events: none;
      border: 1px solid rgba(255, 255, 255, 0.12);
      box-shadow: inset 0 0 80px rgba(0, 0, 0, 0.25);
    }

    .vr-eye-slave model-viewer,
    .vr-eye-slave .vr-pano {
      pointer-events: none;
      touch-action: none;
    }

    .vr-phone-only {
      display: none;
    }

    .vr-control-chip {
      font-size: 10px;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      padding: 4px 8px;
      border: 1px solid rgba(255, 255, 255, 0.22);
      background: rgba(0, 0, 0, 0.4);
      color: #dbeafe;
    }

    .vr-mode-modal.gyro-active .vr-control-chip {
      color: #86efac;
      border-color: rgba(134, 239, 172, 0.65);
    }

    @media (max-width: 767.98px) {
      .vr-phone-only {
        display: inline-flex;
      }
    }

    @media (hover: none) and (pointer: coarse) and (orientation: landscape) and (max-height: 500px) {
      .vr-phone-only {
        display: inline-flex;
      }
    }

    @keyframes chipPulse {
      0% {
        transform: translateY(0);
      }

      50% {
        transform: translateY(-1px);
      }

      100% {
        transform: translateY(0);
      }
    }
    /* VR Setup Modal */
    .vr-setup-modal {
      position: fixed;
      inset: 0;
      z-index: 10050;
      display: none;
      align-items: center;
      justify-content: center;
      background: rgba(0,0,0,0.6);
      backdrop-filter: blur(4px);
    }
    .vr-setup-modal.is-open { display: flex; }
    .vr-setup-dialog {
      width: min(92vw, 1100px);
      max-height: min(90vh, 820px);
      overflow: auto;
      background: rgba(3,7,18,0.96);
      color: #fff;
      padding: 28px;
      border-radius: 8px;
      text-align: center;
    }
    .vr-setup-dialog h2 { font-size: 28px; margin-bottom: 12px; letter-spacing: 0.06em; }
    .vr-setup-row { display:flex; gap:12px; align-items:center; justify-content:center; margin:10px 0; }
    .vr-setup-row input, .vr-setup-row select, .vr-setup-row textarea { padding:8px 10px; border-radius:6px; border:1px solid rgba(255,255,255,0.08); background: rgba(255,255,255,0.03); color:#fff; }
    .vr-setup-actions { display:flex; gap:18px; justify-content:center; margin-top:18px; }
    .vr-setup-actions button { min-width:80px; padding:10px 14px; border-radius:6px; border:1px solid rgba(255,255,255,0.08); }
  </style>
</head>

<body class="bg-canvas-white font-sans text-foundation-grey min-h-screen">
  <div class="min-h-screen flex flex-col">
    <header class="bg-foundation-grey text-white pt-24 pb-12 px-4 shadow-lg mb-10 border-b-2 border-rajkot-rust">
      <div class="max-w-7xl mx-auto">
        <h1 class="text-4xl font-serif font-bold">File Viewer</h1>
        <p class="text-gray-400 mt-2">Database-backed preview and project file manager.</p>
      </div>
    </header>

    <main class="flex-grow mx-auto px-4 sm:px-6 lg:px-8 pb-20" style="max-width:95vw;">
      <?php if ($notice !== ''): ?>
        <div class="mb-6 px-4 py-3 border <?php echo $noticeType === 'success' ? 'border-approval-green text-approval-green bg-approval-green/5' : 'border-red-300 text-red-700 bg-red-50'; ?> rounded">
          <?php echo htmlspecialchars($notice); ?>
        </div>
      <?php endif; ?>

      <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
        <aside class="bg-white border border-gray-100 shadow-premium p-6 lg:col-span-1" style="height:80vh; overflow:auto;">
          <h2 class="text-[10px] uppercase tracking-widest text-rajkot-rust font-bold mb-4">File Details</h2>
          <div class="space-y-3 text-sm">
            <p><strong>Project:</strong> <?php echo htmlspecialchars($projectName); ?></p>
            <p><strong>File:</strong> <?php echo htmlspecialchars($fileName); ?></p>
            <p><strong>Version:</strong> <?php echo htmlspecialchars($version); ?></p>
            <p><strong>Status:</strong> <?php echo htmlspecialchars($status); ?></p>
            <p><strong>Uploaded:</strong> <?php echo $uploadedAt ? htmlspecialchars(date('M d, Y H:i', strtotime($uploadedAt))) : 'N/A'; ?></p>
          </div>
          <?php if (!empty($projectLibrary)): ?>
            <div class="mt-6 pt-6 border-t border-gray-100">
              <h3 class="text-[10px] uppercase tracking-widest text-rajkot-rust font-bold mb-3"><?php echo $projectId > 0 ? 'Project Files' : 'File Manager'; ?></h3>
              <div class="space-y-2 max-h-72 overflow-auto pr-1">
                <?php foreach (array_slice($projectLibrary, 0, 80) as $entry): ?>
                  <?php
                  $entryKind = (string)($entry['kind'] ?? 'file');
                  $entryId = (int)($entry['id'] ?? 0);
                  $entryProjectId = (int)($entry['project_id'] ?? $projectId);
                  $entryGroup = trim((string)($entry['revision_group'] ?? ''));
                  if ($entryGroup === '') {
                    $entryGroup = strtolower(trim((string)($entry['name'] ?? '')));
                  }
                  $entryGroup = preg_replace('/\s+/', ' ', (string)$entryGroup);
                  $entryGroupKey = $entryProjectId . '|' . $entryGroup;
                  $isActiveEntry = $resourceId > 0 && $resourceKind === $entryKind && $entryId === $resourceId;
                  if ($entryKind === 'file' && $selectedFileGroupKey !== '') {
                    $isActiveEntry = ($selectedFileGroupKey === $entryGroupKey);
                  }
                  $entryExt = strtolower((string)($entry['ext'] ?? ''));
                  $entryUrl = file_viewer_url([
                    'kind' => $entryKind,
                    'id' => $entryId,
                    'project_id' => $entryProjectId,
                    'ext' => $entryExt,
                  ]);
                  ?>
                  <a href="<?php echo htmlspecialchars($entryUrl); ?>" class="block no-underline border rounded p-2 <?php echo $isActiveEntry ? 'border-rajkot-rust bg-red-50' : 'border-gray-100 bg-white hover:border-rajkot-rust'; ?>">
                    <p class="text-xs font-semibold text-foundation-grey break-all"><?php echo htmlspecialchars((string)($entry['name'] ?? 'File')); ?></p>
                    <p class="text-[10px] text-gray-500 mt-1">
                      <?php echo htmlspecialchars(strtoupper($entryKind)); ?>
                      <?php if (!empty($entry['project_name'])): ?> • <?php echo htmlspecialchars((string)$entry['project_name']); ?><?php endif; ?>
                        <?php if (!empty($entry['uploaded_at'])): ?> • <?php echo htmlspecialchars(date('M d, H:i', strtotime((string)$entry['uploaded_at']))); ?><?php endif; ?>
                          <?php if (!empty($entry['status'])): ?> • <?php echo htmlspecialchars((string)$entry['status']); ?><?php endif; ?>
                    </p>
                    <?php // Version chips removed per user request. ?>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

        </aside>

        <section class="lg:col-span-4 bg-white border border-gray-100 shadow-premium p-6 flex flex-col" style="height:80vh;">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-serif font-bold"><?php echo htmlspecialchars($fileName); ?></h3>
            <div class="flex items-center gap-2">
              <span class="text-[10px] uppercase tracking-widest px-2 py-1 bg-gray-50 border border-gray-100"><?php echo htmlspecialchars($status); ?></span>
              <?php if ($isStereoPanorama): ?>
                <span class="text-[10px] uppercase tracking-widest px-2 py-1 bg-slate-50 border border-slate-200 text-slate-700">Stereo VR</span>
              <?php endif; ?>
            </div>
          </div>
          <?php if ($resourceKind === 'file' && count($fileVersionButtons) > 1): ?>
            <div class="mb-4 flex flex-wrap items-center gap-2">
              <span class="text-[10px] uppercase tracking-widest text-slate-500">Versions:</span>
              <?php foreach ($fileVersionButtons as $index => $versionEntry): ?>
                <?php
                  $versionEntryId = (int)($versionEntry['id'] ?? 0);
                  if ($versionEntryId <= 0) {
                    continue;
                  }
                  $versionEntryNo = (int)($versionEntry['revision_no'] ?? ($index + 1));
                  $isActiveVersion = $fileVersionSelectedId > 0 ? ($fileVersionSelectedId === $versionEntryId) : ($index === 0);
                  $versionParams = [
                    'kind' => 'file',
                    'id' => (string)$resourceId,
                    'project_id' => (string)$projectId,
                    'version_id' => (string)$versionEntryId,
                  ];
                  if ($ext !== '') {
                    $versionParams['ext'] = $ext;
                  }
                  $versionUrl = $_SERVER['PHP_SELF'] . '?' . http_build_query($versionParams);
                ?>
                <a href="<?php echo htmlspecialchars($versionUrl); ?>"
                  class="inline-flex items-center px-2 py-1 text-[10px] rounded border no-underline <?php echo $isActiveVersion ? 'bg-rajkot-rust text-white border-rajkot-rust' : 'bg-white text-slate-600 border-slate-300 hover:border-rajkot-rust'; ?>">
                  v<?php echo (int)$versionEntryNo; ?>
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <?php if ($viewerMode === '360' && $previewUrl !== ''): ?>
            <div class="mb-3 flex flex-wrap items-center gap-2">
              <button type="button" id="zoomInBtn" class="text-xs bg-foundation-grey text-white px-2 py-1">Zoom +</button>
              <button type="button" id="zoomOutBtn" class="text-xs bg-foundation-grey text-white px-2 py-1">Zoom -</button>
              <button type="button" id="resetViewBtn" class="text-xs bg-foundation-grey text-white px-2 py-1">Reset</button>
              <button type="button" id="fullscreenBtn" class="text-xs bg-foundation-grey text-white px-2 py-1">Fullscreen</button>
              
              <button type="button" id="openVrModeBtn" class="vr-phone-only shrink-0 text-xs bg-slate-accent text-white px-2 py-1 items-center gap-1" title="Open VR mode">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <path d="M3 7h18a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2h-4.5a2.5 2.5 0 0 1-2.4-1.8l-.2-.7a2 2 0 0 0-1.9-1.5 2 2 0 0 0-1.9 1.5l-.2.7A2.5 2.5 0 0 1 7.5 17H3a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2z" />
                  <circle cx="7.5" cy="12" r="1.2" />
                  <circle cx="16.5" cy="12" r="1.2" />
                </svg>
                VR
              </button>
              <?php if ($isStereoPanorama): ?>
                <span class="text-[10px] uppercase tracking-widest px-2 py-1 bg-slate-100 border border-slate-200 text-slate-700">Left/Right Stereo Loaded</span>
              <?php endif; ?>
            </div>
          <?php endif; ?>
          <div class="flex-grow border-2 border-dashed border-gray-200 rounded-lg bg-gray-50 overflow-hidden">
            <?php if ($previewUrl === ''): ?>
              <div class="h-full min-h-[360px] flex items-center justify-center text-gray-400 px-8 text-center">
                Preview unavailable. File path could not be resolved from saved metadata.
              </div>
            <?php elseif ($viewerMode === '3d'): ?>
              <div class="h-full bg-slate-900 text-white flex flex-col">
                <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 border-b border-white/10 bg-black/20">
                  <div class="flex items-center gap-2">
                    <span class="hidden sm:inline text-[10px] uppercase tracking-widest text-gray-300">Drag to rotate • Scroll to zoom</span>
                  </div>
                  <div class="flex flex-wrap items-center gap-2 justify-end">
                    <button type="button" id="open3DPopup" class="text-xs uppercase tracking-widest bg-rajkot-rust hover:bg-red-700 px-3 py-2 text-white font-bold">Fullscreen 3D</button>
                    
                    <button type="button" id="openVrModeBtn" class="vr-phone-only shrink-0 text-xs uppercase tracking-widest bg-slate-accent hover:bg-foundation-grey px-3 py-2 text-white font-bold items-center gap-1" title="Open VR mode">
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 7h18a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2h-4.5a2.5 2.5 0 0 1-2.4-1.8l-.2-.7a2 2 0 0 0-1.9-1.5 2 2 0 0 0-1.9 1.5l-.2.7A2.5 2.5 0 0 1 7.5 17H3a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2z" />
                        <circle cx="7.5" cy="12" r="1.2" />
                        <circle cx="16.5" cy="12" r="1.2" />
                      </svg>
                      VR
                    </button>
                  </div>
                </div>
                <model-viewer
                  id="inline3DViewer"
                  src="<?php echo htmlspecialchars($previewUrl); ?>"
                  camera-controls
                  auto-rotate
                  auto-rotate-delay="0"
                  rotation-per-second="25deg"
                  autoplay
                  shadow-intensity="1"
                  exposure="1"
                  environment-image="neutral"
                  class="w-full h-full bg-slate-950"></model-viewer>
                <div id="inline3DError" class="viewer-3d-error" role="alert">
                  <div>
                    <p class="text-sm font-semibold mb-2">3D preview failed to load.</p>
                    <a href="<?php echo htmlspecialchars($previewUrl); ?>" target="_blank" rel="noopener" class="inline-block text-xs bg-rajkot-rust hover:bg-red-700 text-white px-3 py-2 no-underline">Open model directly</a>
                  </div>
                </div>
              </div>
            <?php elseif ($viewerMode === '360'): ?>
              <div id="panoViewer" class="w-full h-full"></div>
            <?php elseif ($viewerMode === 'text'): ?>
              <?php if ($previewAbsolutePath !== '' && is_file($previewAbsolutePath) && file_viewer_is_likely_text($previewAbsolutePath)): ?>
                <?php
                  $maxBytes = 512 * 1024; // 512 KB
                  $fileSize = (int)@filesize($previewAbsolutePath);
                  if ($fileSize > $maxBytes) {
                ?>
                  <div class="h-full min-h-[360px] flex flex-col items-center justify-center text-gray-400 px-8 text-center gap-3">
                    <p>Text file too large to preview (<?php echo htmlspecialchars(file_viewer_format_bytes($fileSize)); ?>).</p>
                    <a href="<?php echo htmlspecialchars($previewUrl); ?>" target="_blank" rel="noopener" class="text-xs bg-rajkot-rust hover:bg-red-700 text-white px-3 py-2 no-underline">Open / Download</a>
                  </div>
                <?php
                  } else {
                    $contents = @file_get_contents($previewAbsolutePath);
                    if ($contents === false) {
                ?>
                  <div class="h-full min-h-[360px] flex items-center justify-center text-gray-400 px-8 text-center">
                    Could not read text file for preview.
                  </div>
                <?php
                    } else {
                ?>
                  <div class="h-full overflow-auto bg-white p-4 text-sm text-slate-800">
                    <pre class="whitespace-pre-wrap break-words font-mono text-xs"><?php echo htmlspecialchars($contents); ?></pre>
                  </div>
                <?php
                    }
                  }
                ?>
              <?php else: ?>
                <div class="h-full min-h-[360px] flex flex-col items-center justify-center text-gray-400 px-8 text-center gap-3">
                  <p>Text preview unavailable.</p>
                  <a href="<?php echo htmlspecialchars($previewUrl); ?>" target="_blank" rel="noopener" class="text-xs bg-rajkot-rust hover:bg-red-700 text-white px-3 py-2 no-underline">Open / Download</a>
                </div>
              <?php endif; ?>
            <?php elseif ($viewerMode === 'image'): ?>
              <?php
                $generated = ['url' => ''];
                if (in_array($ext, ['tif', 'tiff'], true)) {
                  $tiffMsg = '';
                  // Prefer PHP Imagick when available, fall back to ImageMagick CLI if needed.
                  $generated = file_viewer_generate_image_preview($storagePath !== '' ? $storagePath : ($filePath !== '' ? $filePath : $file), 1600, $tiffMsg);
                  if (empty($generated['url'])) {
                    $cliMsg = '';
                    $generated = file_viewer_generate_image_preview_cli($storagePath !== '' ? $storagePath : ($filePath !== '' ? $filePath : $file), 1600, $cliMsg);
                    if (!empty($generated['url'])) {
                      $tiffMsg = $cliMsg;
                    }
                  }
                  if (!empty($generated['url'])) {
                    $previewUrl = $generated['url'];
                    $previewAbsolutePath = $generated['absolute'];
                  }
                }
              ?>
              <img src="<?php echo htmlspecialchars($previewUrl); ?>" alt="<?php echo htmlspecialchars($fileName); ?>" class="w-full h-full object-contain bg-white" loading="lazy">
              <?php if (in_array($ext, ['tif', 'tiff'], true) && empty((string)($generated['url'] ?? ''))): ?>
                <div class="h-full min-h-[360px] flex items-center justify-center text-gray-400 px-8 text-center">
                  TIFF preview unavailable. <a href="<?php echo htmlspecialchars($previewUrl); ?>" target="_blank" rel="noopener" class="text-xs bg-rajkot-rust hover:bg-red-700 text-white px-3 py-2 no-underline">Open File</a>
                </div>
              <?php endif; ?>
            <?php elseif ($viewerMode === 'pdf'): ?>
              <iframe src="<?php echo htmlspecialchars($previewUrl); ?>" class="w-full h-full bg-white" title="PDF Preview"></iframe>
            <?php elseif ($viewerMode === 'video'): ?>
              <video controls class="w-full h-full bg-black">
                <source src="<?php echo htmlspecialchars($previewUrl); ?>">
                Your browser does not support video preview.
              </video>
            <?php elseif ($viewerMode === 'cad'): ?>
              <div class="h-full bg-slate-900 text-white flex flex-col">
                <div class="px-4 py-3 border-b border-white/10 bg-black/20 text-[10px] uppercase tracking-widest text-gray-300">
                  CAD Local Preview (<?php echo htmlspecialchars(strtoupper($ext)); ?>)
                </div>
                <div class="flex-1 min-h-0 bg-slate-950">
                  <?php if ($cadPreviewExists): ?>
                    <model-viewer
                      src="<?php echo htmlspecialchars($cadPreviewUrl); ?>"
                      camera-controls
                      auto-rotate
                      auto-rotate-delay="0"
                      rotation-per-second="20deg"
                      exposure="1"
                      environment-image="neutral"
                      class="w-full h-full"></model-viewer>
                  <?php else: ?>
                    <div class="h-full min-h-[360px] flex flex-col items-center justify-center text-gray-400 px-8 text-center gap-4">
                      <p>No local CAD preview generated yet.</p>
                      <p class="text-xs text-gray-500">Configure local converter commands in environment:<br>CAD_DWG_CONVERTER_CMD / CAD_SKP_CONVERTER_CMD</p>
                      <form method="post" class="inline-flex">
                        <input type="hidden" name="test_action" value="generate_cad_preview">
                        <input type="hidden" name="source_raw_path" value="<?php echo htmlspecialchars((string)$cadSourceRawPath); ?>">
                        <input type="hidden" name="source_ext" value="<?php echo htmlspecialchars((string)$ext); ?>">
                        <button type="submit" class="text-xs bg-rajkot-rust hover:bg-red-700 text-white px-3 py-2">Generate Local Preview</button>
                      </form>
                    </div>
                  <?php endif; ?>
                </div>
                <div class="px-4 py-3 border-t border-white/10 bg-black/30 flex gap-2">
                  <a href="<?php echo htmlspecialchars($previewUrl); ?>" target="_blank" rel="noopener" class="text-xs bg-rajkot-rust hover:bg-red-700 text-white px-3 py-2 no-underline">Open File</a>
                </div>
              </div>
            <?php else: ?>
              <div class="h-full min-h-[360px] flex flex-col items-center justify-center text-gray-500 gap-4 px-8 text-center">
                <p>This file type cannot be previewed inline yet.</p>
                <a href="<?php echo htmlspecialchars($previewUrl); ?>" target="_blank" rel="noopener" class="bg-foundation-grey hover:bg-rajkot-rust text-white px-4 py-2 text-xs uppercase tracking-wider font-bold no-underline">Open File</a>
              </div>
            <?php endif; ?>
          </div>
        </section>
      </div>
    </main>

    <?php require_once __DIR__ . '/../Common/footer.php'; ?>
  </div>
  <?php if ($viewerMode === '3d' && $previewUrl !== ''): ?>
    <div id="threeDModal" class="viewer-3d-modal" aria-hidden="true">
      <div class="viewer-3d-dialog" role="dialog" aria-modal="true" aria-label="3D model fullscreen viewer">
        <div class="flex items-center justify-between px-4 py-3 border-b border-white/10 text-white bg-black/30">
          <div class="text-xs uppercase tracking-widest text-gray-200">Fullscreen 3D Viewer</div>
          <div class="flex items-center gap-2">
            <button type="button" id="modalOrbitToggle" class="text-xs bg-foundation-grey hover:bg-rajkot-rust text-white px-3 py-1">Toggle Orbit</button>
            <button type="button" id="modalClose3D" class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-1">Close</button>
          </div>
        </div>
        <div class="viewer-3d-viewport" id="modal3DViewport">
          <model-viewer
            id="modal3DViewer"
            src="<?php echo htmlspecialchars($previewUrl); ?>"
            camera-controls
            auto-rotate
            auto-rotate-delay="0"
            rotation-per-second="30deg"
            autoplay
            shadow-intensity="1"
            exposure="1"
            environment-image="neutral"
            class="viewer-3d-canvas"></model-viewer>
          <div id="modal3DError" class="viewer-3d-error" role="alert">
            <div>
              <p class="text-sm font-semibold mb-2">3D preview failed to load.</p>
              <a href="<?php echo htmlspecialchars($previewUrl); ?>" target="_blank" rel="noopener" class="inline-block text-xs bg-rajkot-rust hover:bg-red-700 text-white px-3 py-2 no-underline">Open model directly</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if (($viewerMode === '3d' || $viewerMode === '360') && $previewUrl !== ''): ?>
    <div id="vrModeModal" class="vr-mode-modal" aria-hidden="true">
      <div class="vr-mode-header">
        <div class="flex items-center gap-2">
          <div class="text-xs uppercase tracking-widest">VR Mode</div>
          <span id="vrGyroStatus" class="vr-control-chip">Gyro Off</span>
        </div>
        <div class="flex items-center gap-2">
          <button type="button" id="enableGyroBtn" class="text-xs bg-slate-accent hover:bg-foundation-grey text-white px-3 py-1">Enable Gyro</button>
          <button type="button" class="openVrSetupBtn text-xs bg-foundation-grey hover:bg-rajkot-rust text-white px-3 py-1">Setup</button>
          <button type="button" id="closeVrModeBtn" class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-1">Close</button>
        </div>
      </div>
      <div class="vr-mode-split">
        <?php if ($viewerMode === '3d'): ?>
          <div class="vr-eye" id="vrEyeLeft">
            <model-viewer
              id="vrModelLeft"
              src="<?php echo htmlspecialchars($previewUrl); ?>"
              camera-controls
              auto-rotate
              auto-rotate-delay="0"
              rotation-per-second="24deg"
              camera-orbit="-3deg 75deg auto"
              exposure="1"
              shadow-intensity="1"
              environment-image="neutral"></model-viewer>
          </div>
          <div class="vr-eye vr-eye-slave" id="vrEyeRight">
            <model-viewer
              id="vrModelRight"
              src="<?php echo htmlspecialchars($previewUrl); ?>"
              camera-controls
              auto-rotate
              auto-rotate-delay="0"
              rotation-per-second="24deg"
              camera-orbit="3deg 75deg auto"
              exposure="1"
              shadow-intensity="1"
              environment-image="neutral"></model-viewer>
          </div>
        <?php else: ?>
          <div class="vr-eye" id="vrEyeLeft">
            <div id="vrPanoLeft" class="vr-pano"></div>
          </div>
          <div class="vr-eye vr-eye-slave" id="vrEyeRight">
            <div id="vrPanoRight" class="vr-pano"></div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($viewerMode === '360' && $previewUrl !== ''): ?>
    <div id="panoFullscreenModal" class="viewer-3d-modal" aria-hidden="true">
      <div class="viewer-360-dialog" role="dialog" aria-modal="true" aria-label="360 panoramic fullscreen viewer">
        <div class="flex items-center justify-between px-4 py-3 border-b border-white/10 text-white bg-black/30">
          <div class="text-xs uppercase tracking-widest text-gray-200">Fullscreen 360 Viewer</div>
          <div class="flex items-center gap-2">
            <button type="button" id="panoFsResetBtn" class="text-xs bg-foundation-grey hover:bg-rajkot-rust text-white px-3 py-1">Reset View</button>
            <button type="button" id="panoFsCloseBtn" class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-1">Close</button>
          </div>
        </div>
        <div id="panoFullscreenViewer" class="viewer-360-canvas"></div>
      </div>
    </div>
  <?php endif; ?>
  <?php if ($viewerMode === '360' && $previewUrl !== ''): ?>
    <script>
      (function() {
        if (!window.pannellum) {
          return;
        }
        const viewer = pannellum.viewer('panoViewer', {
          type: 'equirectangular',
          panorama: <?php echo json_encode($previewUrl); ?>,
          autoLoad: true,
          compass: false,
          showZoomCtrl: true,
          showFullscreenCtrl: true,
          mouseZoom: true
        });

        const zoomInBtn = document.getElementById('zoomInBtn');
        const zoomOutBtn = document.getElementById('zoomOutBtn');
        const resetViewBtn = document.getElementById('resetViewBtn');
        const fullscreenBtn = document.getElementById('fullscreenBtn');
        const panoFsModal = document.getElementById('panoFullscreenModal');
        const panoFsCloseBtn = document.getElementById('panoFsCloseBtn');
        const panoFsResetBtn = document.getElementById('panoFsResetBtn');

        let panoFsViewer = null;

        function openPanoFullscreen() {
          if (!panoFsModal) {
            return;
          }

          panoFsModal.classList.add('is-open');
          panoFsModal.setAttribute('aria-hidden', 'false');
          document.body.style.overflow = 'hidden';

          if (!panoFsViewer) {
            panoFsViewer = pannellum.viewer('panoFullscreenViewer', {
              type: 'equirectangular',
              panorama: <?php echo json_encode($previewUrl); ?>,
              autoLoad: true,
              autoRotate: -2,
              compass: false,
              showZoomCtrl: true,
              showFullscreenCtrl: false,
              mouseZoom: true,
              hfov: viewer.getHfov(),
              pitch: viewer.getPitch(),
              yaw: viewer.getYaw()
            });
          } else {
            panoFsViewer.setPitch(viewer.getPitch());
            panoFsViewer.setYaw(viewer.getYaw());
            panoFsViewer.setHfov(viewer.getHfov());
          }
        }

        function closePanoFullscreen() {
          if (!panoFsModal) {
            return;
          }
          panoFsModal.classList.remove('is-open');
          panoFsModal.setAttribute('aria-hidden', 'true');
          document.body.style.overflow = '';

          if (panoFsViewer) {
            viewer.setPitch(panoFsViewer.getPitch());
            viewer.setYaw(panoFsViewer.getYaw());
            viewer.setHfov(panoFsViewer.getHfov());
          }
        }

        if (zoomInBtn) {
          zoomInBtn.addEventListener('click', function() {
            viewer.setHfov(viewer.getHfov() - 10);
          });
        }
        if (zoomOutBtn) {
          zoomOutBtn.addEventListener('click', function() {
            viewer.setHfov(viewer.getHfov() + 10);
          });
        }
        if (resetViewBtn) {
          resetViewBtn.addEventListener('click', function() {
            viewer.setPitch(0);
            viewer.setYaw(0);
            viewer.setHfov(100);
          });
        }
        if (fullscreenBtn) {
          fullscreenBtn.addEventListener('click', function() {
            openPanoFullscreen();
          });
        }

        if (panoFsCloseBtn) {
          panoFsCloseBtn.addEventListener('click', closePanoFullscreen);
        }

        if (panoFsResetBtn) {
          panoFsResetBtn.addEventListener('click', function() {
            if (!panoFsViewer) {
              return;
            }
            panoFsViewer.setPitch(0);
            panoFsViewer.setYaw(0);
            panoFsViewer.setHfov(100);
          });
        }

        if (panoFsModal) {
          panoFsModal.addEventListener('click', function(event) {
            if (event.target === panoFsModal) {
              closePanoFullscreen();
            }
          });
        }

        document.addEventListener('keydown', function(event) {
          if (event.key === 'Escape' && panoFsModal && panoFsModal.classList.contains('is-open')) {
            closePanoFullscreen();
          }
        });
      })();
    </script>
  <?php endif; ?>
  <!-- VR Setup Modal -->
  <div id="vrSetupModal" class="vr-setup-modal" aria-hidden="true">
    <div class="vr-setup-dialog" role="dialog" aria-modal="true" aria-label="Mobile VR Setup">
      <form method="post" id="vrSetupForm">
        <input type="hidden" name="vr_action" value="save_settings">
        <h2>MOBILE VR SETUP</h2>
        <div class="vr-setup-row">
          <label style="min-width:110px;text-align:right;">Device:</label>
          <input type="text" name="vr_device" id="vr_device" value="<?php echo htmlspecialchars((string)($vrSettings['device'] ?? 'Custom')); ?>">
        </div>
        <div class="vr-setup-row">
          <label style="min-width:110px;text-align:right;">Screensize:</label>
          <input type="text" name="vr_screensize" id="vr_screensize" value="<?php echo htmlspecialchars((string)($vrSettings['screensize'] ?? '6.1')); ?>"> <span style="opacity:0.8;margin-left:6px">inch</span>
        </div>
        <div class="vr-setup-row">
          <label style="min-width:110px;text-align:right;">IPD:</label>
          <input type="text" name="vr_ipd" id="vr_ipd" value="<?php echo htmlspecialchars((string)($vrSettings['ipd'] ?? '63.5')); ?>"> <span style="opacity:0.8;margin-left:6px">mm</span>
        </div>
        <div class="vr-setup-row">
          <label style="min-width:110px;text-align:right;">VR Headset:</label>
          <select name="vr_headset" id="vr_headset">
            <?php $hs = htmlspecialchars((string)($vrSettings['headset'] ?? 'No Distortion')); ?>
            <option value="No Distortion" <?php echo $hs === 'No Distortion' ? 'selected' : ''; ?>>No Distortion</option>
            <option value="Cardboard" <?php echo $hs === 'Cardboard' ? 'selected' : ''; ?>>Cardboard</option>
            <option value="Custom" <?php echo $hs === 'Custom' ? 'selected' : ''; ?>>Custom</option>
          </select>
          <button type="button" id="openVrCustomizeBtn" class="text-xs ml-2">Customize</button>
        </div>
        <div class="vr-setup-row">
          <label style="min-width:110px;text-align:right;">Custom Params:</label>
          <textarea name="vr_custom" id="vr_custom" rows="3" style="width:60%;"><?php echo htmlspecialchars((string)($vrSettings['custom'] ?? '')); ?></textarea>
        </div>
        <div class="vr-setup-row">
          <button type="button" id="calibrateGyroBtn" class="text-sm bg-foundation-grey text-white px-4 py-2">Calibrate Gyroscope</button>
        </div>
        <div class="vr-setup-actions">
          <button type="submit" id="vrSaveBtn" class="bg-rajkot-rust text-white">SAVE</button>
          <button type="button" id="vrResetBtn" class="bg-slate-700 text-white">RESET</button>
          <button type="button" id="vrCloseBtn" class="bg-red-600 text-white">CLOSE</button>
        </div>
      </form>
    </div>
  </div>
  <?php if (($viewerMode === '3d' || $viewerMode === '360') && $previewUrl !== ''): ?>
    <script>
      (function() {
        const openBtn = document.getElementById('openVrModeBtn');
        const modal = document.getElementById('vrModeModal');
        const closeBtn = document.getElementById('closeVrModeBtn');
        const enableGyroBtn = document.getElementById('enableGyroBtn');
        const vrGyroStatus = document.getElementById('vrGyroStatus');
        const vrModelLeft = document.getElementById('vrModelLeft');
        const vrModelRight = document.getElementById('vrModelRight');
        const vrPanoLeftEl = document.getElementById('vrPanoLeft');
        const vrPanoRightEl = document.getElementById('vrPanoRight');
        const vrEyeRight = document.getElementById('vrEyeRight');
        if (!openBtn || !modal || !closeBtn) {
          return;
        }

        let vrPanoInitialized = false;
        let vrPanoLeftViewer = null;
        let vrPanoRightViewer = null;
        let vrSyncFrame = 0;
        let gyroEnabled = false;
        let gyroHandler = null;
        let gyroBaselineAlpha = null;
        let gyroBaselineBeta = null;
        let gyroOriginYaw = 0;
        let gyroOriginPitch = 0;
        let gyroEventCount = 0;
        let gyroNoDataTimer = 0;
        let gyroUsingNativeOrientation = false;

        function hasSecureGyroContext() {
          return window.isSecureContext || window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
        }

        function setGyroStatus(active, text) {
          if (!vrGyroStatus) {
            return;
          }
          vrGyroStatus.textContent = text;
          if (active) {
            modal.classList.add('gyro-active');
          } else {
            modal.classList.remove('gyro-active');
          }
        }

        function clearGyroNoDataTimer() {
          if (gyroNoDataTimer) {
            clearTimeout(gyroNoDataTimer);
            gyroNoDataTimer = 0;
          }
        }

        function normalizeDeg(value) {
          let angle = Number(value) || 0;
          while (angle > 180) {
            angle -= 360;
          }
          while (angle < -180) {
            angle += 360;
          }
          return angle;
        }

        function clamp(value, min, max) {
          return Math.min(max, Math.max(min, value));
        }

        function updateVrCompensation() {
          const vv = window.visualViewport;
          const topInset = vv && Number.isFinite(vv.offsetTop) ? vv.offsetTop : 0;
          const yShift = topInset > 0 ? -Math.min(18, topInset * 0.6) : 0;
          const landscape = window.innerWidth > window.innerHeight;
          const scale = landscape ? 1.01 : 1;
          modal.style.setProperty('--vr-y-shift', yShift + 'px');
          modal.style.setProperty('--vr-scale', String(scale));
        }

        function formatFieldOfView(value) {
          if (typeof value === 'number') {
            return value + 'deg';
          }
          return String(value);
        }

        function sync3DViews() {
          if (!vrModelLeft || !vrModelRight) {
            return;
          }
          const source = vrModelLeft;
          const target = vrModelRight;
          if (!source || !target || typeof source.getCameraOrbit !== 'function' || typeof source.getFieldOfView !== 'function') {
            return;
          }
          const orbit = source.getCameraOrbit();
          const fov = source.getFieldOfView();
          target.setAttribute('camera-orbit', orbit && typeof orbit.toString === 'function' ? orbit.toString() : String(orbit));
          target.setAttribute('field-of-view', formatFieldOfView(fov));
        }

        function sync360Views() {
          if (!vrPanoLeftViewer || !vrPanoRightViewer) {
            return;
          }
          const source = vrPanoLeftViewer;
          const target = vrPanoRightViewer;
          if (!source || !target) {
            return;
          }

          const pitch = source.getPitch();
          const yaw = source.getYaw();
          const hfov = source.getHfov();

          // Pass `false` to prevent transition animation and enforce strict matching.
          target.setPitch(pitch, false);
          target.setYaw(yaw, false);
          target.setHfov(hfov, false);
        }

        function runVrSyncLoop() {
          if (!modal.classList.contains('is-open')) {
            vrSyncFrame = 0;
            return;
          }

          <?php if ($viewerMode === '3d'): ?>
            sync3DViews();
          <?php else: ?>
            sync360Views();
          <?php endif; ?>

          vrSyncFrame = requestAnimationFrame(runVrSyncLoop);
        }

        function startVrSync() {
          if (!vrSyncFrame) {
            vrSyncFrame = requestAnimationFrame(runVrSyncLoop);
          }
        }

        function stopVrSync() {
          if (vrSyncFrame) {
            cancelAnimationFrame(vrSyncFrame);
            vrSyncFrame = 0;
          }
        }

        function applyGyroToPano(event) {
          if (!gyroEnabled || !vrPanoLeftViewer || !vrPanoRightViewer) {
            return;
          }
          gyroEventCount += 1;

          const alpha = typeof event.alpha === 'number' ? event.alpha : null;
          const beta = typeof event.beta === 'number' ? event.beta : null;
          if (alpha === null || beta === null) {
            return;
          }

          if (gyroBaselineAlpha === null || gyroBaselineBeta === null) {
            gyroBaselineAlpha = alpha;
            gyroBaselineBeta = beta;
          }

          const angle = window.screen && window.screen.orientation ? window.screen.orientation.angle : 0;
          const yawFactor = angle === 90 ? -1 : 1;
          const yawDelta = normalizeDeg(alpha - gyroBaselineAlpha) * yawFactor;
          const pitchDelta = clamp((beta - gyroBaselineBeta) * 0.7, -45, 45);

          const nextYaw = normalizeDeg(gyroOriginYaw + yawDelta);
          const nextPitch = clamp(gyroOriginPitch - pitchDelta, -60, 60);

          vrPanoLeftViewer.setYaw(nextYaw, false);
          vrPanoRightViewer.setYaw(nextYaw, false);
          vrPanoLeftViewer.setPitch(nextPitch, false);
          vrPanoRightViewer.setPitch(nextPitch, false);
        }

        function disableGyro() {
          gyroEnabled = false;
          gyroBaselineAlpha = null;
          gyroBaselineBeta = null;
          gyroEventCount = 0;
          clearGyroNoDataTimer();
          if (gyroHandler) {
            window.removeEventListener('deviceorientation', gyroHandler, true);
            gyroHandler = null;
          }
          if (gyroUsingNativeOrientation) {
            if (vrPanoLeftViewer && typeof vrPanoLeftViewer.stopOrientation === 'function') {
              vrPanoLeftViewer.stopOrientation();
            }
            if (vrPanoRightViewer && typeof vrPanoRightViewer.stopOrientation === 'function') {
              vrPanoRightViewer.stopOrientation();
            }
          }
          gyroUsingNativeOrientation = false;
          if (enableGyroBtn) {
            enableGyroBtn.textContent = 'Enable Gyro';
          }
          setGyroStatus(false, 'Gyro Off');
        }

        async function enableGyro() {
          <?php if ($viewerMode !== '360'): ?>
            return;
          <?php endif; ?>
          if (!vrPanoLeftViewer || !vrPanoRightViewer) {
            return;
          }

          if (!hasSecureGyroContext()) {
            setGyroStatus(false, 'HTTPS Required');
            if (enableGyroBtn) {
              enableGyroBtn.textContent = 'HTTPS Required';
            }
            return;
          }

          if (typeof DeviceOrientationEvent === 'undefined') {
            setGyroStatus(false, 'Gyro Unsupported');
            return;
          }

          if (gyroEnabled) {
            disableGyro();
            return;
          }

          // Prefer Pannellum's native orientation handling when available.
          if (typeof vrPanoLeftViewer.startOrientation === 'function' && typeof vrPanoRightViewer.startOrientation === 'function') {
            try {
              vrPanoLeftViewer.startOrientation();
              gyroUsingNativeOrientation = true;
              gyroEnabled = true;
              if (enableGyroBtn) {
                enableGyroBtn.textContent = 'Disable Gyro';
              }
              setGyroStatus(true, 'Gyro On');
              return;
            } catch (err) {
              gyroUsingNativeOrientation = false;
            }
          }

          try {
            if (typeof DeviceOrientationEvent.requestPermission === 'function') {
              const permission = await DeviceOrientationEvent.requestPermission();
              if (permission !== 'granted') {
                setGyroStatus(false, 'Gyro Permission Denied');
                return;
              }
            }
          } catch (err) {
            setGyroStatus(false, 'Gyro Permission Failed');
            return;
          }

          gyroOriginYaw = vrPanoLeftViewer.getYaw();
          gyroOriginPitch = vrPanoLeftViewer.getPitch();
          gyroBaselineAlpha = null;
          gyroBaselineBeta = null;
          gyroEventCount = 0;
          clearGyroNoDataTimer();
          gyroHandler = applyGyroToPano;
          window.addEventListener('deviceorientation', gyroHandler, true);
          gyroEnabled = true;
          if (enableGyroBtn) {
            enableGyroBtn.textContent = 'Disable Gyro';
          }
          setGyroStatus(true, 'Gyro On');

          gyroNoDataTimer = window.setTimeout(function() {
            if (gyroEnabled && !gyroUsingNativeOrientation && gyroEventCount === 0) {
              setGyroStatus(false, 'No Sensor Data');
            }
          }, 2200);
        }

        function openVr() {
          modal.classList.add('is-open');
          modal.setAttribute('aria-hidden', 'false');
          document.body.style.overflow = 'hidden';
          updateVrCompensation();

          if (modal.requestFullscreen) {
            modal.requestFullscreen().catch(function() {});
          }

          <?php if ($viewerMode === '360'): ?>
            if (!vrPanoInitialized && window.pannellum) {
              vrPanoLeftViewer = pannellum.viewer('vrPanoLeft', {
                type: 'equirectangular',
                panorama: <?php echo json_encode($stereoLeftUrl !== '' ? $stereoLeftUrl : $previewUrl); ?>,
                autoLoad: true,
                compass: false,
                showZoomCtrl: false,
                showFullscreenCtrl: false,
                mouseZoom: true,
                draggable: true,
                orientationOnByDefault: false,
                pitch: 0,
                yaw: 0,
                hfov: 100
              });

              vrPanoRightViewer = pannellum.viewer('vrPanoRight', {
                type: 'equirectangular',
                panorama: <?php echo json_encode($stereoRightUrl !== '' ? $stereoRightUrl : $previewUrl); ?>,
                autoLoad: true,
                compass: false,
                showZoomCtrl: false,
                showFullscreenCtrl: false,
                mouseZoom: true,
                draggable: true,
                orientationOnByDefault: false,
                pitch: 0,
                yaw: 0,
                hfov: 100
              });
              vrPanoInitialized = true;
            }

            if (vrPanoLeftViewer && vrPanoRightViewer) {
              const basePitch = vrPanoLeftViewer.getPitch();
              const baseYaw = vrPanoLeftViewer.getYaw();
              const baseHfov = vrPanoLeftViewer.getHfov();
              vrPanoRightViewer.setPitch(basePitch, false);
              vrPanoRightViewer.setYaw(baseYaw, false);
              vrPanoRightViewer.setHfov(baseHfov, false);
            }

            if (vrEyeRight) {
              vrEyeRight.classList.add('vr-eye-slave');
            }

            if (enableGyroBtn) {
              enableGyroBtn.textContent = 'Enable Gyro';
            }
            if (!hasSecureGyroContext()) {
              if (enableGyroBtn) {
                enableGyroBtn.textContent = 'HTTPS Required';
              }
              setGyroStatus(false, 'HTTPS Required');
            } else {
              setGyroStatus(false, 'Tap Enable Gyro');
            }
          <?php endif; ?>

          startVrSync();
        }

        function closeVr() {
          modal.classList.remove('is-open');
          modal.setAttribute('aria-hidden', 'true');
          document.body.style.overflow = '';
          disableGyro();
          stopVrSync();
          if (document.fullscreenElement && document.exitFullscreen) {
            document.exitFullscreen().catch(function() {});
          }
        }

        openBtn.addEventListener('click', openVr);
        closeBtn.addEventListener('click', closeVr);

        if (enableGyroBtn) {
          enableGyroBtn.addEventListener('click', function() {
            enableGyro();
          });
        }

        if (window.visualViewport) {
          window.visualViewport.addEventListener('resize', updateVrCompensation);
          window.visualViewport.addEventListener('scroll', updateVrCompensation);
        }
        window.addEventListener('resize', updateVrCompensation);

        document.addEventListener('keydown', function(event) {
          if (event.key === 'Escape' && modal.classList.contains('is-open')) {
            closeVr();
          }
        });
      })();
    </script>
  <?php endif; ?>
  <?php if ($viewerMode === '3d' && $previewUrl !== ''): ?>
    <script>
      (function() {
        const modelEls = [
          document.getElementById('inline3DViewer'),
          document.getElementById('modal3DViewer'),
          document.getElementById('vrModelLeft'),
          document.getElementById('vrModelRight')
        ].filter(Boolean);
        const statusChip = document.getElementById('gpuRendererStatus');

        if (modelEls.length === 0) {
          return;
        }

        const hasWebGPU = typeof navigator !== 'undefined' && !!navigator.gpu;
        if (hasWebGPU) {
          modelEls.forEach(function(el) {
            // model-viewer supports WebGPU through this renderer hint.
            el.setAttribute('experimental-renderer', 'webgpu');
          });
          if (statusChip) {
            statusChip.textContent = 'Renderer: WebGPU';
          }
        } else if (statusChip) {
          statusChip.textContent = 'Renderer: WebGL';
        }
      })();
    </script>
  <?php endif; ?>
    <script>
      (function() {
        const modal = document.getElementById('vrSetupModal');
        if (!modal) return;
        const setupBtns = Array.from(document.querySelectorAll('.openVrSetupBtn'));
        const closeBtn = document.getElementById('vrCloseBtn');
        const resetBtn = document.getElementById('vrResetBtn');
        const calibrateBtn = document.getElementById('calibrateGyroBtn');
        const enableGyroBtn = document.getElementById('enableGyroBtn');
        const form = document.getElementById('vrSetupForm');

        // Capture initial values for reset
        const initial = {};
        ['vr_device','vr_screensize','vr_ipd','vr_headset','vr_custom'].forEach(function(id) {
          const el = document.getElementById(id);
          initial[id] = el ? el.value : '';
        });

        function openModal() {
          modal.classList.add('is-open');
          modal.setAttribute('aria-hidden', 'false');
          document.body.style.overflow = 'hidden';
        }
        function closeModal() {
          modal.classList.remove('is-open');
          modal.setAttribute('aria-hidden', 'true');
          document.body.style.overflow = '';
        }

        setupBtns.forEach(function(b) {
          b.addEventListener('click', function() { openModal(); });
        });

        if (closeBtn) {
          closeBtn.addEventListener('click', function() { closeModal(); });
        }

        if (resetBtn) {
          resetBtn.addEventListener('click', function() {
            ['vr_device','vr_screensize','vr_ipd','vr_headset','vr_custom'].forEach(function(id) {
              const el = document.getElementById(id);
              if (!el) return;
              el.value = initial[id] || '';
            });
          });
        }

        if (calibrateBtn) {
          calibrateBtn.addEventListener('click', function() {
            // Trigger existing gyro enable button if present
            if (enableGyroBtn) {
              enableGyroBtn.click();
              // Also open VR modal so user can see calibration
              const vrModeModal = document.getElementById('vrModeModal');
              if (vrModeModal) {
                vrModeModal.classList.add('is-open');
                vrModeModal.setAttribute('aria-hidden','false');
              }
            } else {
              alert('Gyroscope control not available in this view.');
            }
          });
        }

        // Close modal when clicking backdrop
        modal.addEventListener('click', function(e) {
          if (e.target === modal) {
            closeModal();
          }
        });

        // Escape closes
        document.addEventListener('keydown', function(e) {
          if (e.key === 'Escape' && modal.classList.contains('is-open')) {
            closeModal();
          }
        });
      })();
    </script>
  <?php if ($viewerMode === '3d' && $previewUrl !== ''): ?>
    <script>
      (function() {
        const inlineViewer = document.getElementById('inline3DViewer');
        const inlineError = document.getElementById('inline3DError');
        const modalViewer = document.getElementById('modal3DViewer');
        const modalError = document.getElementById('modal3DError');

        function wireModelError(viewerEl, errorEl) {
          if (!viewerEl || !errorEl) {
            return;
          }
          viewerEl.addEventListener('error', function() {
            errorEl.classList.add('is-visible');
          });
          viewerEl.addEventListener('load', function() {
            errorEl.classList.remove('is-visible');
          });
        }

        wireModelError(inlineViewer, inlineError);
        wireModelError(modalViewer, modalError);
      })();
    </script>
  <?php endif; ?>
  <?php if ($viewerMode === '3d' && $previewUrl !== ''): ?>
    <script>
      (function() {
        const openBtn = document.getElementById('open3DPopup');
        const modal = document.getElementById('threeDModal');
        const closeBtn = document.getElementById('modalClose3D');
        const orbitToggle = document.getElementById('modalOrbitToggle');
        const modalViewer = document.getElementById('modal3DViewer');

        if (!openBtn || !modal || !closeBtn || !modalViewer) {
          return;
        }

        let orbitEnabled = true;

        function openModal() {
          modal.classList.add('is-open');
          modal.setAttribute('aria-hidden', 'false');
          document.body.style.overflow = 'hidden';
        }

        function closeModal() {
          modal.classList.remove('is-open');
          modal.setAttribute('aria-hidden', 'true');
          document.body.style.overflow = '';
        }

        openBtn.addEventListener('click', openModal);
        closeBtn.addEventListener('click', closeModal);

        modal.addEventListener('click', function(event) {
          if (event.target === modal) {
            closeModal();
          }
        });

        document.addEventListener('keydown', function(event) {
          if (event.key === 'Escape' && modal.classList.contains('is-open')) {
            closeModal();
          }
        });

        if (orbitToggle) {
          orbitToggle.addEventListener('click', function() {
            orbitEnabled = !orbitEnabled;
            if (orbitEnabled) {
              modalViewer.setAttribute('auto-rotate', '');
            } else {
              modalViewer.removeAttribute('auto-rotate');
            }
          });
        }
      })();
    </script>
  <?php endif; ?>
</body>

</html>