<?php
require_once __DIR__ . '/../Common/public_shell.php';

$content = function_exists('public_content_page_values') ? public_content_page_values('contact_us') : [];
$ct = static fn ($key, $default = '') => (string)($content[$key] ?? $default);
$image = static fn ($key, $default) => rd_content_image($content, $key, $default);

$contactPageUrl = rd_public_url('contact_us.php');
$contactAddress = strip_tags((string)public_content_get_html('contact_us', 'address_html', '538 Jasal Complex, Nanavati Chowk, 150ft Ring Road, Rajkot, Gujarat, India'));
$mapQuery = trim($contactAddress !== '' ? $contactAddress : 'Ripal Design Rajkot');
$mapSrc = 'https://www.google.com/maps?q=' . rawurlencode($mapQuery) . '&output=embed';

if (isset($_POST['submit'])) {
    if (!csrf_validate((string)($_POST['csrf_token'] ?? ''))) {
        $_SESSION['contact_form_error'] = $ct('csrf_invalid', 'Invalid request token. Please refresh and try again.');
        header('Location: ' . $contactPageUrl);
        exit;
    }

    $source = trim((string)($_POST['source'] ?? ''));
    $first = trim((string)($_POST['first_name'] ?? ''));
    $last = trim((string)($_POST['last_name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $subject = trim((string)($_POST['subject'] ?? ''));
    $message = trim((string)($_POST['message'] ?? ''));

    if ($source === 'hero_cta') {
        $name = trim((string)($_POST['name'] ?? ''));
        $contact = trim((string)($_POST['contact'] ?? ''));
        $brief = trim((string)($_POST['brief'] ?? ''));
        $parts = preg_split('/\s+/', $name, 2);
        $first = trim((string)($parts[0] ?? ''));
        $last = trim((string)($parts[1] ?? ''));
        $email = filter_var($contact, FILTER_VALIDATE_EMAIL) ? $contact : '';
        $subject = 'Project brief';
        $message = "Contact: {$contact}\n\nBrief: {$brief}";
    }

    $_SESSION['contact_form_old'] = compact('first', 'last', 'email', 'subject', 'message');

    try {
        $db = get_db();
        if (!($db instanceof PDO)) {
            throw new RuntimeException('Database connection unavailable.');
        }
        $stmt = $db->prepare('INSERT INTO contact_messages (first_name, last_name, email, subject, message) VALUES (:first_name, :last_name, :email, :subject, :message)');
        $stmt->execute([
            ':first_name' => $first,
            ':last_name' => $last,
            ':email' => $email,
            ':subject' => $subject,
            ':message' => $message,
        ]);
        $_SESSION['contact_form_success'] = true;
        unset($_SESSION['contact_form_old']);
    } catch (Throwable $e) {
        if (function_exists('app_log')) {
            app_log('warning', 'Contact form submission failed', ['exception' => $e->getMessage(), 'email' => $email]);
        }
        $_SESSION['contact_form_error'] = $ct('error_message', 'Failed to send message. Please try again.');
    }

    header('Location: ' . $contactPageUrl);
    exit;
}

$success = !empty($_SESSION['contact_form_success']);
$error = (string)($_SESSION['contact_form_error'] ?? '');
$old = $_SESSION['contact_form_old'] ?? [];
unset($_SESSION['contact_form_success'], $_SESSION['contact_form_error']);

rd_page_start([
    'title' => $ct('page_title', 'Contact'),
    'description' => $ct('meta_description', 'Contact Ripal Design for architecture, interiors, and execution support.'),
    'image' => $image('left_image', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg'),
    'url' => rd_public_url('contact_us.php'),
    'active' => 'contact',
]);
?>
<main id="main">
    <section class="hero">
        <div class="hero-copy">
            <p class="eyebrow"><?php echo esc($ct('left_kicker', 'Contact')); ?></p>
            <h1><?php echo esc($ct('left_heading_line_1', 'Tell us')); ?> <?php echo esc($ct('left_heading_line_2', 'what you want to build.')); ?></h1>
            <p>Share the site, scope, timeline, and current stage. We will respond with the clearest next step instead of a vague sales call.</p>
        </div>
        <div class="hero-media">
            <figure><img src="<?php echo esc_attr($image('left_image', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg')); ?>" alt="Ripal Design contact"></figure>
        </div>
    </section>

    <section class="page-section">
        <div class="contact-layout">
            <aside class="contact-panel contact-info">
                <p class="eyebrow">Studio details</p>
                <h2>Ripal Design Rajkot</h2>
                <p><?php echo public_content_get_html('contact_us', 'address_html', '538 Jasal Complex,<br>Nanavati Chowk,<br>150ft Ring Road,<br>Rajkot, Gujarat, India'); ?></p>
                <a href="tel:+919426789012"><?php echo esc($ct('contact_phone', '+91 94267 89012')); ?></a>
                <a href="mailto:<?php echo esc_attr($ct('contact_email', 'projects@ripaldesign.studio')); ?>"><?php echo esc($ct('contact_email', 'projects@ripaldesign.studio')); ?></a>
                <div class="map-frame">
                    <iframe title="Ripal Design location map" src="<?php echo esc_attr($mapSrc); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </aside>

            <section class="contact-panel" aria-labelledby="contactFormTitle">
                <p class="eyebrow">Project inquiry</p>
                <h2 id="contactFormTitle"><?php echo esc($ct('form_heading', 'Send a message')); ?></h2>
                <?php if ($success): ?><p class="notice notice-success">Message sent. We will reply shortly.</p><?php endif; ?>
                <?php if ($error !== ''): ?><p class="notice notice-error"><?php echo esc($error); ?></p><?php endif; ?>
                <form class="form-grid" action="<?php echo esc_attr($contactPageUrl); ?>" method="post" id="contactForm">
                    <?php echo csrf_token_field(); ?>
                    <div class="field">
                        <label for="contactFirstName">First name</label>
                        <input id="contactFirstName" name="first_name" value="<?php echo esc_attr((string)($old['first'] ?? '')); ?>" required autocomplete="given-name">
                        <span id="first_name_error" class="text-danger"></span>
                    </div>
                    <div class="field">
                        <label for="contactLastName">Last name</label>
                        <input id="contactLastName" name="last_name" value="<?php echo esc_attr((string)($old['last'] ?? '')); ?>" required autocomplete="family-name">
                        <span id="last_name_error" class="text-danger"></span>
                    </div>
                    <div class="field full">
                        <label for="contactEmail">Email</label>
                        <input id="contactEmail" type="email" name="email" value="<?php echo esc_attr((string)($old['email'] ?? '')); ?>" required autocomplete="email">
                        <span id="email_error" class="text-danger"></span>
                    </div>
                    <div class="field full">
                        <label for="contactSubject">Project type</label>
                        <select id="contactSubject" name="subject" required>
                            <option value="">Select one</option>
                            <?php foreach (['Residential Project', 'Commercial Project', 'Design Consultation', 'Other'] as $option): ?>
                                <option value="<?php echo esc_attr(strtolower(str_replace(' ', '_', $option))); ?>" <?php echo (($old['subject'] ?? '') === strtolower(str_replace(' ', '_', $option))) ? 'selected' : ''; ?>><?php echo esc($option); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field full">
                        <label for="contactMessage">Message</label>
                        <textarea id="contactMessage" name="message" required placeholder="Tell us about the site, scope, timeline, and budget range."><?php echo esc((string)($old['message'] ?? '')); ?></textarea>
                        <span id="message_error" class="text-danger"></span>
                    </div>
                    <div class="field full">
                        <button class="button button-primary" type="submit" name="submit" value="1">Send Message</button>
                    </div>
                </form>
            </section>
        </div>
    </section>
</main>
<script src="<?php echo esc_attr(rd_public_url('js/validation.js')); ?>" defer></script>
<?php rd_page_end(); ?>
