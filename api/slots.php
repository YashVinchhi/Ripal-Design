<?php
/**
 * WebMCP consultation slots endpoint.
 */

require_once __DIR__ . '/_webmcp_common.php';

wmcp_require_https();
wmcp_handle_options(true, 'GET, OPTIONS');

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'GET') {
    wmcp_error('Method not allowed.', 405, true);
}

$month = wmcp_clean_text($_GET['month'] ?? date('Y-m'));
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    wmcp_error('month must be in YYYY-MM format.', 422, true);
}

$slots = [];

if (db_connected() && db_table_exists('consultation_slots')) {
    $startDate = $month . '-01';
    $endDate = date('Y-m-t', strtotime($startDate));

    $rows = db_fetch_all(
        'SELECT slot_date, time_slot FROM consultation_slots WHERE slot_date BETWEEN ? AND ? AND is_available = 1 ORDER BY slot_date ASC, time_slot ASC',
        [$startDate, $endDate]
    );

    $grouped = [];
    foreach ($rows as $row) {
        $date = (string)($row['slot_date'] ?? '');
        $timeSlot = (string)($row['time_slot'] ?? '');
        if ($date === '' || $timeSlot === '') {
            continue;
        }
        if (!isset($grouped[$date])) {
            $grouped[$date] = [];
        }
        $grouped[$date][] = $timeSlot;
    }

    foreach ($grouped as $date => $times) {
        $slots[] = [
            'date' => $date,
            'time_slots' => array_values(array_unique($times)),
        ];
    }
} else {
    // TODO: replace with your DB-driven consultation calendar if needed.
    $start = strtotime($month . '-01');
    $end = strtotime(date('Y-m-t', $start));

    for ($ts = $start; $ts <= $end; $ts = strtotime('+1 day', $ts)) {
        $weekday = date('N', $ts);

        // Weekdays only in default fallback schedule.
        if ($weekday >= 6) {
            continue;
        }

        $slots[] = [
            'date' => date('Y-m-d', $ts),
            'time_slots' => ['10:00', '11:30', '14:00', '16:30'],
        ];
    }
}

wmcp_output($slots, 200, true);
