<?php
require_once __DIR__ . '/../Common/public_shell.php';

$content = function_exists('public_content_page_values') ? public_content_page_values('privacy') : [];
$ct = static fn ($key, $default = '') => (string)($content[$key] ?? $default);
$body = function_exists('public_content_get_html') ? trim((string)public_content_get_html('privacy', 'body_html', '')) : '';

if ($body === '') {
    $body = <<<'HTML'
<p><strong>Effective Date:</strong> April 22, 2026</p>
<h2>1. Overview</h2>
<p>Ripal Design respects your privacy and uses personal information only for legitimate website, communication, service, and compliance purposes.</p>
<h2>2. Information We Collect</h2>
<p>We may collect names, email addresses, phone numbers, project details, billing references, technical usage data, and messages or files you share.</p>
<h2>3. How We Use Information</h2>
<p>We use information to respond to inquiries, prepare proposals, manage projects, improve website performance, and meet legal or tax obligations.</p>
<h2>4. Sharing</h2>
<p>We do not sell personal data. We may share limited information with trusted service providers when required to operate our services.</p>
<h2>5. Security and Retention</h2>
<p>We use reasonable safeguards and retain information only as long as needed for business, legal, tax, dispute, and recordkeeping purposes.</p>
<h2>6. Your Choices</h2>
<p>You may request access, correction, or deletion of your personal information where applicable by contacting us.</p>
<h2>7. Contact</h2>
<p>For privacy questions, email <a href="mailto:projects@ripaldesign.studio">projects@ripaldesign.studio</a>.</p>
HTML;
}

rd_page_start([
    'title' => $ct('page_title', 'Privacy Policy'),
    'description' => $ct('meta_description', 'Read how Ripal Design collects, uses, and protects personal information.'),
    'url' => rd_public_url('privacy.php'),
]);
?>
<main id="main" class="legal-wrap">
    <article class="legal-card">
        <p class="eyebrow">Legal</p>
        <h1><?php echo esc($ct('heading', 'Privacy Policy')); ?></h1>
        <p>This policy explains what we collect, why we collect it, and how you can contact us about your information.</p>
        <div class="legal-body"><?php echo $body; ?></div>
    </article>
</main>
<?php rd_page_end(); ?>
