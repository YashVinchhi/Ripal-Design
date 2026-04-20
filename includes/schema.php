<?php
/**
 * Schema.org JSON-LD helpers
 * Each function echoes a <script type="application/ld+json"> block.
 */

function render_breadcrumbs_schema() {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'example.com');
    $base = rtrim($scheme . '://' . $host, '/');
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $segments = array_values(array_filter(explode('/', trim($path, '/'))));

    $items = [];
    $acc = $base . '/';
    $pos = 1;
    // Home
    $items[] = [
        '@type' => 'ListItem',
        'position' => 1,
        'name' => 'Home',
        'item' => $base . '/'
    ];
    foreach ($segments as $seg) {
        $acc = rtrim($acc, '/') . '/' . rawurlencode($seg);
        $pos++;
        $items[] = [
            '@type' => 'ListItem',
            'position' => $pos,
            'name' => ucwords(str_replace(['-', '_'], ' ', $seg)),
            'item' => $acc,
        ];
    }

    $graph = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $items,
    ];

    echo '<script type="application/ld+json">' . json_encode($graph, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}

function render_localbusiness_schema(array $overrides = []) {
    $data = array_merge([
        '@context' => 'https://schema.org',
        '@type' => 'ArchitecturalService',
        'name' => 'Ripal Design',
        'url' => 'https://example.com/',
        'logo' => 'https://example.com/assets/Content/Logo.png',
        'telephone' => '+91-0000000000',
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => 'REPLACE_STREET',
            'addressLocality' => 'REPLACE_CITY',
            'addressRegion' => 'REPLACE_STATE',
            'postalCode' => 'REPLACE_PIN',
            'addressCountry' => 'IN'
        ],
    ], $overrides);

    echo '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}

function render_creativework_project_schema(array $project) {
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'CreativeWork',
        'name' => (string)($project['name'] ?? ''),
        'description' => (string)($project['description'] ?? $project['summary'] ?? ''),
    ];
    if (!empty($project['image'])) {
        $schema['image'] = (string)$project['image'];
    } elseif (!empty($project['cover_image'])) {
        $schema['image'] = (string)$project['cover_image'];
    }
    // Creator: firm
    $schema['creator'] = [
        '@type' => 'Organization',
        'name' => 'Ripal Design'
    ];

    echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}

function render_services_schema(array $services) {
    $list = [];
    foreach ($services as $s) {
        $list[] = array_filter([
            '@type' => 'Service',
            'name' => (string)($s['name'] ?? $s),
            'description' => (string)($s['description'] ?? ''),
        ]);
    }
    $graph = [
        '@context' => 'https://schema.org',
        '@graph' => $list,
    ];
    echo '<script type="application/ld+json">' . json_encode($graph, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}

function render_contactpage_schema(array $overrides = []) {
    $data = array_merge([
        '@context' => 'https://schema.org',
        '@type' => 'ContactPage',
        'mainEntity' => [
            '@type' => 'Organization',
            'name' => 'Ripal Design',
            'url' => 'https://example.com/'
        ]
    ], $overrides);
    echo '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
}

// End of includes/schema.php
