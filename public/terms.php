<?php
require_once __DIR__ . '/../Common/public_shell.php';

$content = function_exists('public_content_page_values') ? public_content_page_values('terms') : [];
$ct = static fn ($key, $default = '') => (string)($content[$key] ?? $default);
$body = function_exists('public_content_get_html') ? trim((string)public_content_get_html('terms', 'body_html', '')) : '';

if ($body === '') {
    $body = <<<'HTML'
<p><strong>Effective Date:</strong> April 22, 2026</p>
<h2>1. Acceptance of Terms</h2>
<p>By accessing this website or engaging Ripal Design for services, you agree to these Terms and Conditions.</p>
<h2>2. Scope of Services</h2>
<p>Service details, fees, timelines, and deliverables are governed by the final proposal, quotation, or written agreement shared with you.</p>
<h2>3. Client Responsibilities</h2>
<p>You agree to provide accurate information, timely feedback, required approvals, and lawful materials needed for the project.</p>
<h2>4. Payments</h2>
<p>Payments must be made according to the approved quotation or invoice. Taxes, duties, and transaction charges may apply unless explicitly included.</p>
<h2>5. Intellectual Property</h2>
<p>Unless agreed in writing, Ripal Design retains ownership of preliminary concepts, unused options, source files, and proprietary methods.</p>
<h2>6. Revisions and Timelines</h2>
<p>Revision cycles and timelines depend on the approved scope and timely client feedback. Scope changes may affect fees and schedules.</p>
<h2>7. Limitation of Liability</h2>
<p>To the fullest extent permitted by law, Ripal Design is not liable for indirect, incidental, or consequential damages arising from website or service use.</p>
<h2>8. Governing Law</h2>
<p>These terms are governed by the laws of India and subject to competent courts in Gujarat, India.</p>
<h2>9. Contact</h2>
<p>For questions, email <a href="mailto:projects@ripaldesign.studio">projects@ripaldesign.studio</a>.</p>
HTML;
}

rd_page_start([
    'title' => $ct('page_title', 'Terms and Conditions'),
    'description' => $ct('meta_description', 'Read the terms and conditions for using Ripal Design services and website.'),
    'url' => rd_public_url('terms.php'),
]);
?>
<main id="main" class="legal-wrap">
    <article class="legal-card">
        <p class="eyebrow">Legal</p>
        <h1><?php echo esc($ct('heading', 'Terms and Conditions')); ?></h1>
        <p>Clear terms help keep expectations, ownership, timelines, and responsibilities visible before work begins.</p>
        <div class="legal-body"><?php echo $body; ?></div>
    </article>
</main>
<?php rd_page_end(); ?>
