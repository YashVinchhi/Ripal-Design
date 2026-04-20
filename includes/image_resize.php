<?php
/**
 * Simple image resize helper using GD (fallback if Imagick not available)
 * Usage: includes/image_resize.php provides resize_image($sourcePath,$destPath,$maxWidth)
 */
function resize_image_gd($src, $dst, $maxWidth) {
    if (!extension_loaded('gd')) return false;
    if (!file_exists($src)) return false;
    $info = getimagesize($src);
    if (!$info) return false;
    list($w, $h, $type) = $info;
    if ($w <= $maxWidth) {
        // copy original if smaller
        return copy($src, $dst);
    }
    $ratio = $h / $w;
    $newW = (int)$maxWidth;
    $newH = (int)round($newW * $ratio);

    switch ($type) {
        case IMAGETYPE_JPEG:
            $srcImg = imagecreatefromjpeg($src);
            break;
        case IMAGETYPE_PNG:
            $srcImg = imagecreatefrompng($src);
            break;
        case IMAGETYPE_WEBP:
            if (function_exists('imagecreatefromwebp')) {
                $srcImg = imagecreatefromwebp($src);
                break;
            }
            return false;
        default:
            return false;
    }

    $dstImg = imagecreatetruecolor($newW, $newH);
    // preserve PNG transparency
    if ($type === IMAGETYPE_PNG) {
        imagealphablending($dstImg, false);
        imagesavealpha($dstImg, true);
        $transparent = imagecolorallocatealpha($dstImg, 255, 255, 255, 127);
        imagefilledrectangle($dstImg, 0, 0, $newW, $newH, $transparent);
    }

    imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newW, $newH, $w, $h);

    $ok = false;
    if ($type === IMAGETYPE_JPEG) {
        $ok = imagejpeg($dstImg, $dst, 85);
    } elseif ($type === IMAGETYPE_PNG) {
        $ok = imagepng($dstImg, $dst, 6);
    } elseif ($type === IMAGETYPE_WEBP && function_exists('imagewebp')) {
        $ok = imagewebp($dstImg, $dst, 80);
    }

    imagedestroy($srcImg);
    imagedestroy($dstImg);
    return $ok;
}

function ensure_thumb_cached($srcPath, $w) {
    $srcPath = ltrim($srcPath, '/');
    $absSrc = __DIR__ . '/../' . $srcPath;
    if (!file_exists($absSrc)) return false;

    $cacheDir = __DIR__ . '/../uploads/.cache';
    if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);
    $hash = sha1($srcPath);
    $ext = strtolower(pathinfo($absSrc, PATHINFO_EXTENSION));
    $thumbName = $hash . '-' . intval($w) . '.' . $ext;
    $thumbPath = $cacheDir . '/' . $thumbName;
    if (file_exists($thumbPath)) return $thumbPath;

    if (resize_image_gd($absSrc, $thumbPath, (int)$w)) {
        return $thumbPath;
    }
    return false;
}
