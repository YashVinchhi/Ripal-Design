<?php
/**
 * WebMCP firm information endpoint.
 */

require_once __DIR__ . '/_webmcp_common.php';

if (file_exists(__DIR__ . '/../app/Domains/Content/Services/public_content.php')) {
    require_once __DIR__ . '/../app/Domains/Content/Services/public_content.php';
}

wmcp_require_https();
wmcp_handle_options(true, 'GET, OPTIONS');

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'GET') {
    wmcp_error('Method not allowed.', 405, true);
}

$services = [];
if (db_connected() && db_table_exists('services')) {
    $serviceRows = db_fetch_all('SELECT title FROM services WHERE active = 1 ORDER BY id ASC LIMIT 50');
    foreach ($serviceRows as $row) {
        $title = trim((string)($row['title'] ?? ''));
        if ($title !== '') {
            $services[] = $title;
        }
    }
}

if (empty($services)) {
    $services = [
        'Residential Architecture',
        'Commercial Design',
        'Interior Design',
        'Urban Planning',
    ];
}

$team = [];
if (db_connected() && db_table_exists('users')) {
    $teamRows = db_fetch_all(
        "SELECT name, role FROM users WHERE role IN ('admin','employee') ORDER BY id ASC LIMIT 20"
    );

    foreach ($teamRows as $row) {
        $memberName = trim((string)($row['name'] ?? ''));
        $memberRole = trim((string)($row['role'] ?? 'Team Member'));
        if ($memberName !== '') {
            $team[] = [
                'name' => $memberName,
                'role' => $memberRole,
            ];
        }
    }
}

if (empty($team)) {
    $team = [
        ['name' => 'Ripal Design Team', 'role' => 'Architecture & Execution'],
    ];
}

// TODO: replace placeholder awards with DB-managed awards if/when a dedicated table is added.
$awards = [
    'Trusted municipal and private project delivery partner in Rajkot',
    'Recognized for design-to-execution collaboration model',
];

$firmName = 'Ripal Design';
$tagline = 'Where architecture vision meets execution precision';
$foundedYear = '2017';
$address = '538 Jasal Complex, Nanavati Chowk, 150ft Ring Road, Rajkot, Gujarat, India';
$phone = 'Available on request';
$email = 'projects@ripaldesign.studio';

if (function_exists('public_content_page_values')) {
    $aboutValues = public_content_page_values('about_us');
    $contactValues = public_content_page_values('contact_us');

    $firmName = (string)($aboutValues['firm_name'] ?? $firmName);
    $tagline = (string)($aboutValues['hero_subtitle'] ?? $tagline);
    $foundedYear = (string)($aboutValues['founded_year'] ?? $foundedYear);
    $address = (string)($contactValues['address'] ?? $address);
    $phone = 'Available on request';
    $email = (string)($contactValues['contact_email'] ?? $email);
}

$response = [
    'firm_name' => $firmName,
    'tagline' => $tagline,
    'founded_year' => $foundedYear,
    'services' => $services,
    'team' => $team,
    'awards' => $awards,
    'address' => $address,
    'phone' => $phone,
    'email' => $email,
];

wmcp_output($response, 200, true);
