<?php
// Minimal Imagick stub for IDEs and static analyzers.
// Declares class and commonly used methods/constants only when the real
// PECL imagick extension is not available. This file is safe to include in
// autoload files so editors can resolve the type.
if (!class_exists('Imagick')) {
    class Imagick
    {
        public const COLORSPACE_RGB = 1;
        public const FILTER_LANCZOS = 22;

        public function __construct($input = null) {}
        public function getNumberImages(): int { return 1; }
        public function setFirstIterator(): bool { return true; }
        public function setImageColorspace($colorspace): bool { return true; }
        public function setImageFormat(string $format): bool { return true; }
        public function stripImage(): bool { return true; }
        public function getImageWidth(): int { return 0; }
        public function resizeImage(int $columns, int $rows, $filter, float $blur): bool { return true; }
        public function writeImage(string $filename): bool { return true; }
    }

    class ImagickException extends \Exception {}
}
