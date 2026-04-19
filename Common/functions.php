<?php
/**
 * Common template helper aliases.
 *
 * This file is intentionally lightweight and delegates to core helpers
 * loaded from app/Core/Support/util.php through Bootstrap.
 */

if (!function_exists('h')) {
    /**
     * Escape text for safe HTML output.
     *
     * @param mixed $value
     * @return string
     */
    function h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}
