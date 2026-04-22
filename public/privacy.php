<?php
$require_once_path = __DIR__ . '/../app/Core/Bootstrap/init.php';
require_once $require_once_path;

require_once __DIR__ . '/../includes/seo.php';

if (!function_exists('esc')) {
    function esc($string)
    {
        return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('esc_attr')) {
    function esc_attr($string)
    {
        return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
    }
}

$privacyContent = function_exists('public_content_page_values') ? public_content_page_values('privacy') : [];
$ct = static function ($key, $default = '') use ($privacyContent) {
    return (string)($privacyContent[$key] ?? $default);
};
$ctImage = static function ($key, $default = '') use ($privacyContent) {
    $value = (string)($privacyContent[$key] ?? $default);
    if (function_exists('public_content_image_url')) {
        return (string)public_content_image_url($value, $default);
    }
    if (function_exists('base_path')) {
        return (string)base_path(ltrim($value, '/'));
    }
    return $value;
};

$baseUrl = rtrim((string)(defined('BASE_PATH') ? BASE_PATH : ''), '/');
$publicPrefix = trim((string)(defined('PUBLIC_PATH_PREFIX') ? PUBLIC_PATH_PREFIX : ''), '/');
$prefixPart = $publicPrefix === '' ? '' : '/' . $publicPrefix;

$page_data = [
    'title' => $ct('page_title', 'Privacy Policy'),
    'description' => $ct('meta_description', 'Read how Ripal Design collects, uses, and protects personal information.'),
    'image' => $ctImage('hero_image', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg'),
    'url' => $baseUrl . $prefixPart . '/privacy.php',
];
?><!doctype html>
<html lang="en">
<head>
    <?php require_once __DIR__ . '/../includes/schema.php'; ?>
    <?php if (function_exists('render_seo_head')) { render_seo_head($page_data); } ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="icon" href="<?php echo esc_attr($baseUrl . '/favicon.ico'); ?>" type="image/x-icon">
    <link rel="stylesheet" href="<?php echo esc_attr($baseUrl . $prefixPart . '/css/index.css'); ?>">
    <style>
        .legal-page {
            position: relative;
            z-index: 2;
            padding: 7rem 1.5rem 5rem;
            background:
                radial-gradient(circle at top, rgba(148, 24, 12, 0.16), transparent 34%),
                linear-gradient(180deg, #0d0d0d 0%, #121212 100%);
        }

        .legal-shell {
            width: min(960px, 100%);
            margin: 0 auto;
        }

        .legal-card {
            background: rgba(18, 18, 18, 0.88);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.38);
            padding: 3rem;
            backdrop-filter: blur(8px);
        }

        .legal-eyebrow {
            display: inline-block;
            margin-bottom: 1rem;
            color: rgba(255, 255, 255, 0.62);
            font-size: 0.8rem;
            letter-spacing: 0.28em;
            text-transform: uppercase;
            font-weight: 700;
        }

        .legal-title {
            margin: 0 0 1rem;
            color: #f5efe8;
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(2.6rem, 5vw, 4.8rem);
            font-weight: 600;
            line-height: 0.96;
            letter-spacing: -0.03em;
        }

        .legal-intro {
            max-width: 46rem;
            margin: 0 0 2.2rem;
            color: rgba(255, 255, 255, 0.74);
            font-size: 1.05rem;
            line-height: 1.8;
        }

        .legal-body {
            color: rgba(255, 255, 255, 0.78);
            font-size: 1rem;
            line-height: 1.9;
        }

        .legal-body h2 {
            margin: 2.4rem 0 0.8rem;
            color: #f4ede5;
            font-family: 'Cormorant Garamond', serif;
            font-size: clamp(1.7rem, 2.4vw, 2.2rem);
            font-weight: 600;
            line-height: 1.1;
        }

        .legal-body p,
        .legal-body ul,
        .legal-body ol {
            margin: 0 0 1rem;
        }

        .legal-body ul,
        .legal-body ol {
            padding-left: 1.4rem;
        }

        .legal-body li {
            margin-bottom: 0.6rem;
        }

        .legal-body strong {
            color: #ffffff;
            font-weight: 600;
        }

        .legal-body a {
            color: #d86c5d;
            text-decoration: none;
            border-bottom: 1px solid rgba(216, 108, 93, 0.35);
        }

        .legal-body a:hover {
            color: #ffffff;
            border-bottom-color: rgba(255, 255, 255, 0.65);
        }

        @media (max-width: 768px) {
            .legal-page {
                padding: 5.5rem 1rem 3.5rem;
            }

            .legal-card {
                padding: 1.5rem;
            }

            .legal-intro,
            .legal-body {
                font-size: 0.96rem;
            }
        }
    </style>
</head>
<body>
    <?php
    $HEADER_MODE = 'public';
    require_once __DIR__ . '/../app/Ui/header.php';
    ?>

    <main class="legal-page">
        <div class="legal-shell">
            <section class="legal-card">
                <span class="legal-eyebrow">Legal</span>
                <h1 class="legal-title"><?php echo esc($ct('heading', 'Privacy Policy')); ?></h1>
                <p class="legal-intro">This page explains what information we collect, how we use it, when we may share it, and the choices available to you when interacting with Ripal Design online or through our services.</p>
                <div class="legal-body">
                    <?php
                    $privacyHtml = function_exists('public_content_get_html') ? trim((string)public_content_get_html('privacy', 'body_html', '')) : '';
                    if ($privacyHtml !== '') {
                        echo $privacyHtml;
                    } else {
                        echo <<<'HTML'
<p><strong>Effective Date:</strong> April 22, 2026</p>

<h2>1. Overview</h2>
<p>Ripal Design respects your privacy and is committed to protecting your personal information. This Privacy Policy explains how we collect, use, store, and disclose data when you visit our website or use our services.</p>

<h2>2. Information We Collect</h2>
<p>We may collect the following categories of information:</p>
<ul>
    <li><strong>Information you provide directly:</strong> name, email address, phone number, company details, project requirements, billing details, and any messages or files you share.</li>
    <li><strong>Technical and usage data:</strong> IP address, browser type, operating system, referral source, pages visited, time spent, and interaction data.</li>
    <li><strong>Transaction-related data:</strong> payment status, invoice references, and related records (processed through authorized payment providers).</li>
</ul>

<h2>3. How We Use Information</h2>
<p>We use collected information to:</p>
<ul>
    <li>respond to inquiries and provide requested services;</li>
    <li>prepare proposals, contracts, invoices, and project updates;</li>
    <li>improve website performance, user experience, and service quality;</li>
    <li>send service-related notifications and essential communications;</li>
    <li>comply with legal, tax, and regulatory obligations.</li>
</ul>

<h2>4. Legal Basis and Consent</h2>
<p>Where applicable, we process personal data based on your consent, performance of a contract, legitimate business interests, and compliance with legal obligations. You may withdraw consent for optional communications at any time.</p>

<h2>5. Cookies and Similar Technologies</h2>
<p>We use cookies and similar technologies for basic website functionality, analytics, and performance tracking. You can modify cookie preferences through browser settings; however, some site features may not function correctly if cookies are disabled.</p>

<h2>6. Data Sharing and Disclosure</h2>
<p>We do not sell personal data. We may share data with trusted service providers such as hosting companies, analytics tools, payment processors, CRM/email providers, and legal or accounting professionals when needed to run our services.</p>

<h2>7. Data Security</h2>
<p>We use commercially reasonable administrative, technical, and organizational safeguards to protect personal information. No online system is fully secure, so we cannot guarantee absolute security.</p>

<h2>8. Data Retention</h2>
<p>We retain personal information only for as long as necessary for business operations, contractual obligations, legal compliance, dispute resolution, and recordkeeping requirements.</p>

<h2>9. Your Rights</h2>
<p>Subject to applicable law, you may request access, correction, deletion, restriction, or portability of your personal information, and object to specific processing activities. To make a request, contact us using the details below.</p>

<h2>10. Children's Privacy</h2>
<p>Our website and services are not directed to children under 13 years of age. We do not knowingly collect personal information from children. If such data is identified, we will delete it promptly.</p>

<h2>11. Third-Party Websites</h2>
<p>Our website may include links to third-party websites or tools. We are not responsible for third-party privacy practices, security, or content. Please review their policies before sharing personal data.</p>

<h2>12. International Data Transfers</h2>
<p>Your information may be processed in locations where our service providers operate. By using our services, you acknowledge that data may be transferred and processed outside your local jurisdiction, subject to reasonable safeguards.</p>

<h2>13. Changes to This Policy</h2>
<p>We may update this Privacy Policy from time to time. Updated versions will be posted on this page with a revised effective date. Continued use of our website or services after updates indicates acceptance of the revised policy.</p>

<h2>14. Contact Us</h2>
<p>If you have any questions, requests, or concerns about this Privacy Policy, contact us at <a href="mailto:projects@ripaldesign.studio">projects@ripaldesign.studio</a>.</p>
HTML;
                    }
                    ?>
                </div>
            </section>
        </div>
    </main>

    <?php require_once __DIR__ . '/../Common/footer.php'; ?>
</body>
</html>
