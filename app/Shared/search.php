<?php
/**
 * Reusable search helper for building SQL conditions and params.
 * Supports modes: 'like' (default) and 'regex'.
 *
 * Usage:
 *  $res = rd_build_search_conditions(['full_name','username'], $search, 'like');
 *  if ($res['sql'] !== '') { $where[] = $res['sql']; $params = array_merge($params, $res['params']); }
 */

if (!defined('PROJECT_ROOT')) {
    // best-effort bootstrap if included directly
    if (file_exists(__DIR__ . '/../Core/Bootstrap/init.php')) {
        require_once __DIR__ . '/../Core/Bootstrap/init.php';
    }
}

function rd_build_search_conditions(array $fields, string $search, string $mode = 'like') : array {
    $search = trim($search);
    if ($search === '') {
        return ['sql' => '', 'params' => []];
    }

    $conds = [];
    $params = [];

    if ($mode === 'regex' || $mode === 'rlike' || stripos($mode, 'regex') === 0) {
        // Use MySQL REGEXP/RLIKE operator. Bind the raw pattern as provided by caller.
        foreach ($fields as $i => $field) {
            $ph = 'search_regex_' . $i;
            $conds[] = "$field RLIKE :$ph";
            $params[$ph] = $search;
        }
    } else {
        // Default to LIKE search; create unique param names for each field.
        foreach ($fields as $i => $field) {
            $ph = 'search_like_' . $i;
            $conds[] = "$field LIKE :$ph";
            $params[$ph] = '%' . $search . '%';
        }
    }

    return ['sql' => '(' . implode(' OR ', $conds) . ')', 'params' => $params];
}
