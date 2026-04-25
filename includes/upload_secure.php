<?php
// Secure upload helper: validate MIME, limit size, rename to random name,
// store outside webroot and return metadata for DB.

if (!defined('PROJECT_ROOT')) {
    $PROJECT_ROOT = rtrim((string)dirname(__DIR__, 1), '/\\');
} else {
    $PROJECT_ROOT = rtrim((string)PROJECT_ROOT, '/\\');
}

if (!function_exists('upload_allowed_mime_map')) {
    function upload_allowed_mime_map(): array
    {
        return [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'doc' => 'application/msword',
        ];
    }
}

if (!function_exists('detect_file_mime')) {
    function detect_file_mime(string $tmpPath): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $tmpPath) : mime_content_type($tmpPath);
        if ($finfo) finfo_close($finfo);
        return (string)$mime;
    }
}

if (!function_exists('mime_to_extension')) {
    function mime_to_extension(string $mime): ?string
    {
        $map = upload_allowed_mime_map();
        foreach ($map as $ext => $m) {
            if ($m === $mime) return $ext;
        }
        return null;
    }
}

if (!function_exists('sanitize_filename_for_db')) {
    function sanitize_filename_for_db(string $name): string
    {
        $base = basename($name);
        $base = preg_replace('/[^A-Za-z0-9._ \-]/', '_', $base);
        return mb_substr($base, 0, 200);
    }
}

if (!function_exists('ensure_upload_storage')) {
    function ensure_upload_storage(string $dir): bool
    {
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0775, true)) return false;
        }
        // Create an .htaccess to block direct execution
        $ht = $dir . DIRECTORY_SEPARATOR . '.htaccess';
        if (!file_exists($ht)) {
            @file_put_contents($ht, "Options -Indexes\n<FilesMatch \"\\.(php|php5|phtml)$\">\n  Deny from all\n</FilesMatch>\n");
        }
        $idx = $dir . DIRECTORY_SEPARATOR . 'index.html';
        if (!file_exists($idx)) {
            @file_put_contents($idx, '<!doctype html><title>Forbidden</title>');
        }
        return true;
    }
}

if (!function_exists('store_uploaded_file_array')) {
    /**
     * Store uploaded file given a $_FILES-like array.
     * Returns array with keys: ok (bool), error (string|null), stored_name, stored_path,
     * original_name, mime, size
     */
    function store_uploaded_file_array(array $file, array $opts = []): array
    {
        $maxSize = isset($opts['max_size']) ? (int)$opts['max_size'] : (int)((getenv('UPLOAD_MAX_SIZE') ?: 10 * 1024 * 1024));
        // Respect an existing UPLOAD_STORAGE_ROOT constant if defined, otherwise env, otherwise storage/private_uploads
        if (defined('UPLOAD_STORAGE_ROOT') && UPLOAD_STORAGE_ROOT !== '') {
            $storageRoot = rtrim((string)UPLOAD_STORAGE_ROOT, '/\\');
        } else {
            $storageRoot = rtrim((string)(getenv('UPLOAD_STORAGE_ROOT') ?: ($PROJECT_ROOT . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'private_uploads')), '/\\');
        }

        if (empty($file) || empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return ['ok' => false, 'error' => 'No uploaded file', 'stored_name' => null];
        }

        if (!empty($file['error']) && $file['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => 'Upload error code: ' . (int)$file['error'], 'stored_name' => null];
        }

        $size = isset($file['size']) ? (int)$file['size'] : filesize($file['tmp_name']);
        if ($size <= 0 || $size > $maxSize) {
            return ['ok' => false, 'error' => 'File size exceeds limit', 'stored_name' => null, 'size' => $size];
        }

        $mime = detect_file_mime($file['tmp_name']);
        $ext = mime_to_extension($mime);
        if ($ext === null) {
            return ['ok' => false, 'error' => 'Disallowed file type: ' . $mime, 'detected_mime' => $mime];
        }

        if (!ensure_upload_storage($storageRoot)) {
            return ['ok' => false, 'error' => 'Unable to create storage directory'];
        }

        try {
            $name = bin2hex(random_bytes(16)) . '.' . $ext;
        } catch (Throwable $e) {
            $name = bin2hex(uniqid('', true)) . '.' . $ext;
        }

        $dest = $storageRoot . DIRECTORY_SEPARATOR . $name;
        if (!@move_uploaded_file($file['tmp_name'], $dest)) {
            return ['ok' => false, 'error' => 'Failed to move uploaded file'];
        }

        @chmod($dest, 0640);

        return [
            'ok' => true,
            'error' => null,
            'stored_name' => $name,
            'stored_path' => $dest,
            'original_name' => sanitize_filename_for_db($file['name'] ?? ''),
            'mime' => $mime,
            'size' => $size,
        ];
    }
}
