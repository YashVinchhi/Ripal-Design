<?php

/**
 * Materials Studio Shared Helpers
 *
 * Utility functions for the materials, goods, and asset platform.
 * These functions are used across multiple materials studio modules.
 */

if (!function_exists('ms_int')) {
    /**
     * Type cast to integer with safe default fallback.
     *
     * @param mixed $value
     * @param int $default
     * @return int
     */
    function ms_int($value, int $default = 0): int
    {
        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return $default;
            }
        }

        return is_numeric($value) ? (int)$value : $default;
    }
}

if (!function_exists('ms_decimal')) {
    /**
     * Type cast to float with safe default fallback.
     *
     * @param mixed $value
     * @param float $default
     * @return float
     */
    function ms_decimal($value, float $default = 0.0): float
    {
        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return $default;
            }
        }

        return is_numeric($value) ? (float)$value : $default;
    }
}

if (!function_exists('ms_list')) {
    /**
     * Parse comma/semicolon-separated string into normalized list.
     *
     * @param mixed $value
     * @return string
     */
    function ms_list($value): string
    {
        if (is_array($value)) {
            $value = implode(',', $value);
        }

        $parts = preg_split('/[\r\n,;]+/', trim((string)$value));
        if (!is_array($parts)) {
            return '';
        }

        $clean = [];
        foreach ($parts as $part) {
            $part = trim((string)$part);
            if ($part !== '') {
                $clean[] = $part;
            }
        }

        return implode(', ', array_values(array_unique($clean)));
    }
}

if (!function_exists('ms_code')) {
    /**
     * Generate a unique code for goods or assets.
     *
     * @param string $prefix
     * @param string $table
     * @param string $column
     * @return string
     */
    function ms_code(string $prefix, string $table, string $column): string
    {
        $prefix = strtoupper(preg_replace('/[^A-Z0-9]+/i', '', $prefix));
        if ($prefix === '') {
            $prefix = 'MS';
        }

        for ($attempt = 0; $attempt < 5; $attempt++) {
            try {
                $suffix = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            } catch (Throwable $e) {
                $suffix = strtoupper(substr(md5(uniqid('', true)), 0, 6));
            }

            $candidate = $prefix . '-' . date('Ymd') . '-' . $suffix;
            if (!function_exists('db_table_exists') || !db_table_exists($table)) {
                return $candidate;
            }

            $found = db_fetch('SELECT id FROM ' . $table . ' WHERE ' . $column . ' = ? LIMIT 1', [$candidate]);
            if (!$found) {
                return $candidate;
            }
        }

        return $prefix . '-' . date('YmdHis');
    }
}
