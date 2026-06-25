<?php
/**
 * Centralized SEO helper
 * Function: render_seo_head(array $page_data)
 * Expected keys: title, description, image, url
 */
function render_seo_head(array $page_data = []) {
    $siteName = 'Ripal Design';
    $title = trim((string)($page_data['title'] ?? $siteName));
    $description = trim((string)($page_data['description'] ?? ''));
    $image = trim((string)($page_data['image'] ?? ''));
    $url = trim((string)($page_data['url'] ?? ''));
    $googleVerification = trim((string)($page_data['google_verification'] ?? ''));

    // Ensure reasonable lengths
    if (mb_strlen($title) > 60) {
        $title = mb_substr($title, 0, 57) . '...';
    }
    if (mb_strlen($description) > 160) {
        $description = mb_substr($description, 0, 157) . '...';
    }

    $fullTitle = ($title !== '' && stripos($title, $siteName) === false) ? $title . ' | ' . $siteName : $title;

    // Output minimal head SEO tags
    echo "<meta charset=\"UTF-8\">\n";
    echo "<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n";
    echo '<title>' . htmlspecialchars($fullTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8') . "</title>\n";
    if ($description !== '') {
        echo '<meta name="description" content="' . htmlspecialchars($description, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '">' . "\n";
    }
    echo '<meta name="robots" content="index, follow">' . "\n";

    // Canonical
    if ($url !== '') {
        echo '<link rel="canonical" href="' . htmlspecialchars($url, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '">' . "\n";
    } else {
        // attempt to build canonical from current host/request
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '');
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        if ($host !== '') {
            $canon = rtrim($scheme . '://' . $host, '/') . $requestUri;
            echo '<link rel="canonical" href="' . htmlspecialchars($canon, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '">' . "\n";
        }
    }

    // Optional Google Search Console verification meta tag
    if ($googleVerification !== '') {
        echo '<meta name="google-site-verification" content="' . htmlspecialchars($googleVerification, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '">' . "\n";
    }

    // Open Graph
    echo '<meta property="og:site_name" content="' . htmlspecialchars($siteName, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '">' . "\n";
    echo '<meta property="og:type" content="website">' . "\n";
    if ($fullTitle !== '') {
        echo '<meta property="og:title" content="' . htmlspecialchars($fullTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '">' . "\n";
    }
    if ($description !== '') {
        echo '<meta property="og:description" content="' . htmlspecialchars($description, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '">' . "\n";
    }
    if ($image !== '') {
        echo '<meta property="og:image" content="' . htmlspecialchars($image, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '">' . "\n";
    }
    if ($url !== '') {
        echo '<meta property="og:url" content="' . htmlspecialchars($url, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '">' . "\n";
    }

    // Twitter Card
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    if ($fullTitle !== '') {
        echo '<meta name="twitter:title" content="' . htmlspecialchars($fullTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '">' . "\n";
    }
    if ($description !== '') {
        echo '<meta name="twitter:description" content="' . htmlspecialchars($description, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '">' . "\n";
    }
    if ($image !== '') {
        echo '<meta name="twitter:image" content="' . htmlspecialchars($image, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '">' . "\n";
    }

    // Structured data (JSON-LD) for LocalBusiness / Architect
    $schemaTelephone = defined('PHONE_NUMBER') ? (string)PHONE_NUMBER : '+918264271111';
    // Normalize telephone to E.164-like (remove spaces) but keep readable in JSON-LD
    $schemaTelephone = preg_replace('/\s+/', ' ', trim($schemaTelephone));
    $schemaEmail = 'projects@ripaldesign.studio';
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => ['Architect', 'LocalBusiness'],
        'name' => $siteName,
        'description' => $description !== '' ? $description : 'Architectural firm in Rajkot offering architecture, interior design, and project management.',
        'url' => $url !== '' ? $url : (isset($_SERVER['HTTP_HOST']) ? (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? '') . '/' : ''),
        'telephone' => $schemaTelephone,
        'email' => $schemaEmail,
        'foundingDate' => '2017',
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => '538 Jasal Complex, Nanavati Chowk, 150ft Ring Road',
            'addressLocality' => 'Rajkot',
            'addressRegion' => 'Gujarat',
            'postalCode' => '360005',
            'addressCountry' => 'IN',
        ],
        'geo' => [
            '@type' => 'GeoCoordinates',
            'latitude' => '22.3039',
            'longitude' => '70.8022',
        ],
        'areaServed' => ['Rajkot', 'Jamnagar', 'Morbi', 'Junagadh', 'Gujarat'],
        'serviceType' => ['Architectural Planning', 'Interior Design', 'Landscape Architecture', 'Project Management'],
        'priceRange' => '₹₹',
        'openingHours' => 'Mo-Sa 09:00-18:00',
        'sameAs' => ['https://www.instagram.com/ripal_design12/', 'hhttps://www.facebook.com/profile.php?id=100063867671131'],
    ];

    echo "<script type=\"application/ld+json\">\n";
    echo json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
    echo "</script>\n";

}

// End of seo.php
