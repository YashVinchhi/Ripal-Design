<?php
/**
 * WebMCP consultation request endpoint.
 *
 * This is a write endpoint and therefore does not expose wildcard CORS.
 */

require_once __DIR__ . '/_webmcp_common.php';

if (file_exists(__DIR__ . '/../app/Shared/Mail/mail_helper.php')) {
    require_once __DIR__ . '/../app/Shared/Mail/mail_helper.php';
}

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

wmcp_require_https();
wmcp_handle_options(false, 'POST, OPTIONS');

if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    wmcp_error('Method not allowed.', 405, false);
}

if (!db_connected() || !db_table_exists('contact_messages')) {
    wmcp_error('Consultation intake is unavailable.', 503, false);
}

$rawBody = file_get_contents('php://input');
$jsonInput = json_decode((string)$rawBody, true);
if (!is_array($jsonInput)) {
    $jsonInput = [];
}

$payload = !empty($jsonInput) ? $jsonInput : $_POST;

$name = wmcp_clean_text($payload['name'] ?? '');
$email = wmcp_clean_text($payload['email'] ?? '');
$projectType = wmcp_clean_text($payload['project_type'] ?? '');
$message = wmcp_clean_text($payload['message'] ?? '');
$preferredDate = wmcp_clean_text($payload['preferred_date'] ?? '');

if ($name === '' || $email === '' || $projectType === '' || $message === '') {
    wmcp_error('Missing required fields: name, email, project_type, message.', 422, false);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    wmcp_error('Invalid email address.', 422, false);
}

if ($preferredDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $preferredDate)) {
    wmcp_error('preferred_date must be in YYYY-MM-DD format.', 422, false);
}

$referenceId = 'CONS-' . strtoupper(substr(sha1(uniqid((string)mt_rand(), true)), 0, 10));
$subject = 'Consultation Request: ' . $projectType;

$fullMessage = "Reference ID: {$referenceId}\n"
    . "Name: {$name}\n"
    . "Email: {$email}\n"
    . "Project Type: {$projectType}\n"
    . ($preferredDate !== '' ? "Preferred Date: {$preferredDate}\n" : '')
    . "Message:\n{$message}";

try {
    $nameParts = preg_split('/\s+/', trim($name));
    $firstName = wmcp_clean_text($nameParts[0] ?? $name);
    $lastName = wmcp_clean_text(count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '');

    $stmt = get_db()->prepare(
        'INSERT INTO contact_messages (first_name, last_name, email, subject, message) VALUES (:first_name, :last_name, :email, :subject, :message)'
    );

    $stmt->execute([
        ':first_name' => $firstName,
        ':last_name' => $lastName,
        ':email' => $email,
        ':subject' => $subject,
        ':message' => $fullMessage,
    ]);
} catch (Throwable $e) {
    wmcp_error('Failed to save consultation request.', 500, false);
}

$confirmationSent = false;

try {
    $mailSubject = 'We received your consultation request [' . $referenceId . ']';
    $mailBodyHtml = '<p>Hello ' . esc($name) . ',</p>'
        . '<p>Thank you for contacting Ripal Design. We have received your consultation request.</p>'
        . '<p><strong>Reference ID:</strong> ' . esc($referenceId) . '</p>'
        . '<p>Our team will get in touch soon.</p>'
        . '<p>Regards,<br>Ripal Design</p>';

    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        $mailer = new PHPMailer\PHPMailer\PHPMailer(true);
        $configured = function_exists('configure_mailer') ? configure_mailer($mailer) : false;

        $mailer->setFrom(getenv('MAIL_FROM_ADDRESS') ?: 'no-reply@ripaldesign.studio', getenv('MAIL_FROM_NAME') ?: 'Ripal Design');
        $mailer->addAddress($email, $name);
        $mailer->isHTML(true);
        $mailer->Subject = $mailSubject;
        $mailer->Body = $mailBodyHtml;

        if (!$configured) {
            // TODO: replace with your SMTP transport env config in production.
            $mailer->isMail();
        }

        $confirmationSent = $mailer->send();
    } else {
        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-type: text/html; charset=UTF-8';
        $headers[] = 'From: Ripal Design <no-reply@ripaldesign.studio>';

        $confirmationSent = mail($email, $mailSubject, $mailBodyHtml, implode("\r\n", $headers));
    }
} catch (Throwable $e) {
    $confirmationSent = false;
}

wmcp_output([
    'success' => true,
    'reference_id' => $referenceId,
    'message' => $confirmationSent
        ? 'Consultation request submitted successfully. Confirmation email sent.'
        : 'Consultation request submitted successfully. Confirmation email is pending.',
], 200, false);
