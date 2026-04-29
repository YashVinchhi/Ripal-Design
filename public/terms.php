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

$termsContent = function_exists('public_content_page_values') ? public_content_page_values('terms') : [];
$ct = static function ($key, $default = '') use ($termsContent) {
    return (string)($termsContent[$key] ?? $default);
};
$ctImage = static function ($key, $default = '') use ($termsContent) {
    $value = (string)($termsContent[$key] ?? $default);
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
    'title' => $ct('page_title', 'Terms and Conditions'),
    'description' => $ct('meta_description', 'Read the terms and conditions for using Ripal Design services and website.'),
    'image' => $ctImage('hero_image', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg'),
    'url' => $baseUrl . $prefixPart . '/terms.php',
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
                radial-gradient(circle at top, rgba(198, 162, 106, 0.14), transparent 34%),
                linear-gradient(180deg, #0b0b0b 0%, #141414 100%);
        }

        .legal-shell {
            width: min(960px, 100%);
            margin: 0 auto;
        }

        .legal-card {
            background: rgba(18, 18, 18, 0.86);
            border: 1px solid rgba(246, 242, 236, 0.1);
            box-shadow: 0 24px 80px rgba(0, 0, 0, 0.45);
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
            color: #f6f2ec;
            font-family: "Bodoni Moda", "Playfair Display", serif;
            font-size: clamp(2.6rem, 5vw, 4.8rem);
            font-weight: 600;
            line-height: 0.96;
            letter-spacing: -0.03em;
        }

        .legal-intro {
            max-width: 46rem;
            margin: 0 0 2.2rem;
            color: rgba(245, 241, 235, 0.74);
            font-size: 1.05rem;
            line-height: 1.8;
        }

        .legal-body {
            color: rgba(245, 241, 235, 0.78);
            font-size: 1rem;
            line-height: 1.9;
        }

        .legal-body h2 {
            margin: 2.4rem 0 0.8rem;
            color: #f6f2ec;
            font-family: "Bodoni Moda", "Playfair Display", serif;
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
            color: #c6a26a;
            text-decoration: none;
            border-bottom: 1px solid rgba(198, 162, 106, 0.35);
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
                <h1 class="legal-title"><?php echo esc($ct('heading', 'Terms and Conditions')); ?></h1>
                <p class="legal-intro">These terms explain the rules, responsibilities, and limits that apply when you use the Ripal Design website or engage us for design, branding, web, and related creative services.</p>
                <div class="legal-body">
                    <?php
                    $termsHtml = function_exists('public_content_get_html') ? trim((string)public_content_get_html('terms', 'body_html', '')) : '';
                    if ($termsHtml !== '') {
                        echo $termsHtml;
                    } else {
                        echo <<<'HTML'
<p><strong>Effective Date:</strong> April 22, 2026</p>

<h2>1. Acceptance of Terms</h2>
<p>By accessing or using this website and any related services offered by Ripal Design, you agree to be bound by these Terms and Conditions. If you do not agree, please do not use this website or our services.</p>

<h2>2. Scope of Services</h2>
<p>Ripal Design provides design, branding, web, and related creative services. All service details, timelines, fees, and deliverables are governed by the final proposal, quotation, or agreement shared with you.</p>

<h2>3. Eligibility and User Responsibilities</h2>
<p>You agree to provide accurate information, keep your account or communication details updated, and use our website lawfully. You must not attempt to disrupt the website, upload malicious code, or misuse any feature.</p>

<h2>4. Quotations, Payments, and Taxes</h2>
<p>All prices are provided as per the approved quotation or invoice unless stated otherwise. Payments must be made on or before due dates. Applicable taxes, duties, transaction charges, and bank fees are your responsibility unless explicitly included.</p>

<h2>5. Intellectual Property</h2>
<p>Unless otherwise agreed in writing, Ripal Design retains ownership of all preliminary concepts, drafts, unused creative options, source files, and proprietary methods. Final approved deliverables are licensed or assigned according to your signed agreement.</p>

<h2>6. Client Materials and Rights</h2>
<p>You confirm that content, logos, images, and materials you provide are legally owned by you or licensed for use. You are responsible for any third-party claims arising from materials supplied by you.</p>

<h2>7. Revisions and Approvals</h2>
<p>Revision cycles, response times, and approval windows are as defined in the project scope. Additional revisions or scope changes may require timeline extensions and extra charges.</p>

<h2>8. Delivery Timelines</h2>
<p>Delivery schedules depend on timely client feedback and approvals. Delays in communication, content submission, or payments may shift final timelines.</p>

<h2>9. Third-Party Tools and Links</h2>
<p>Our website or services may include third-party platforms, APIs, plugins, payment gateways, hosting tools, and external links. We are not responsible for third-party terms, uptime, or privacy practices.</p>

<h2>10. Disclaimer of Warranties</h2>
<p>Website and services are provided on an "as is" and "as available" basis. To the extent permitted by law, we disclaim all warranties, express or implied, including merchantability, fitness for a particular purpose, and non-infringement.</p>

<h2>11. Limitation of Liability</h2>
<p>To the fullest extent allowed by law, Ripal Design is not liable for indirect, incidental, special, or consequential damages, including loss of profits, business interruption, data loss, or reputational harm arising from website or service use.</p>

<h2>12. Indemnity</h2>
<p>You agree to indemnify and hold harmless Ripal Design, its team, and affiliates from claims, damages, liabilities, and expenses resulting from your misuse of services, breach of these terms, or violation of any rights of a third party.</p>

<h2>13. Suspension or Termination</h2>
<p>We may suspend or terminate access to our website or services if you breach these terms, fail to make required payments, or use services in a harmful or unlawful manner.</p>

<h2>14. Governing Law and Jurisdiction</h2>
<p>These terms are governed by the laws of India. Any dispute shall be subject to the exclusive jurisdiction of competent courts in Gujarat, India, unless otherwise agreed in writing.</p>

<h2>15. Updates to Terms</h2>
<p>We may revise these Terms and Conditions at any time. Updated versions will be posted on this page with a revised effective date. Continued use of the website after updates means you accept the revised terms.</p>

<h2>16. Contact Us</h2>
<p>For legal or service-related questions about these terms, email us at <a href="mailto:projects@ripaldesign.studio">projects@ripaldesign.studio</a>.</p>
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
