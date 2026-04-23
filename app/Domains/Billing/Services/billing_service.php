<?php

if (!function_exists('billing_ensure_tables')) {
    function billing_ensure_tables(): bool
    {
        if (!function_exists('db_connected') || !db_connected()) {
            return false;
        }

        $invoiceSql = "CREATE TABLE IF NOT EXISTS billing_invoices (
            id INT AUTO_INCREMENT PRIMARY KEY,
            invoice_code VARCHAR(64) NOT NULL,
            project_id INT NOT NULL,
            client_name VARCHAR(255) DEFAULT NULL,
            client_contact VARCHAR(64) DEFAULT NULL,
            client_email VARCHAR(255) DEFAULT NULL,
            base_fee DECIMAL(12,2) NOT NULL DEFAULT 0,
            goods_total DECIMAL(12,2) NOT NULL DEFAULT 0,
            tax_rate DECIMAL(6,2) NOT NULL DEFAULT 18.00,
            tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0,
            due_date DATE DEFAULT NULL,
            status ENUM('draft','issued','partially_paid','paid','overdue','cancelled') NOT NULL DEFAULT 'issued',
            notes TEXT DEFAULT NULL,
            created_by INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_billing_invoice_code (invoice_code),
            KEY idx_billing_project (project_id),
            KEY idx_billing_status (status),
            KEY idx_billing_due_date (due_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $ok = (bool)db_query($invoiceSql);
        if (!$ok) {
            return false;
        }

        return true;
    }
}

if (!function_exists('billing_generate_invoice_code')) {
    function billing_generate_invoice_code(int $projectId): string
    {
        $prefix = 'BIL-' . str_pad((string)max(1, $projectId), 5, '0', STR_PAD_LEFT);
        $seq = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        return $prefix . '-' . date('Ymd') . '-' . $seq;
    }
}

if (!function_exists('billing_get_project_financials')) {
    /**
     * @return array<string,mixed>|null
     */
    function billing_get_project_financials(int $projectId): ?array
    {
        if ($projectId <= 0 || !db_connected()) {
            return null;
        }

        $project = db_fetch(
            'SELECT id, name, COALESCE(budget,0) AS budget, COALESCE(owner_name,\'Client\') AS owner_name, COALESCE(owner_contact,\'\') AS owner_contact, COALESCE(owner_email,\'\') AS owner_email
             FROM projects WHERE id = ? LIMIT 1',
            [$projectId]
        );
        if (!$project) {
            return null;
        }

        $goodsRow = db_fetch('SELECT COALESCE(SUM(total_price),0) AS s FROM project_goods WHERE project_id = ?', [$projectId]);
        $goodsTotal = (float)($goodsRow['s'] ?? 0);

        return [
            'project_id' => (int)$project['id'],
            'project_name' => (string)$project['name'],
            'base_fee' => (float)$project['budget'],
            'goods_total' => $goodsTotal,
            'client_name' => (string)$project['owner_name'],
            'client_contact' => (string)$project['owner_contact'],
            'client_email' => (string)$project['owner_email'],
        ];
    }
}

if (!function_exists('billing_invoice_outstanding')) {
    function billing_invoice_outstanding(array $invoice): float
    {
        $total = (float)($invoice['total_amount'] ?? 0);
        $paid = (float)($invoice['amount_paid'] ?? 0);
        $outstanding = $total - $paid;
        return $outstanding > 0 ? round($outstanding, 2) : 0.0;
    }
}

if (!function_exists('billing_recalculate_invoice_status')) {
    function billing_recalculate_invoice_status(int $invoiceId): bool
    {
        if ($invoiceId <= 0 || !db_connected()) {
            return false;
        }

        $row = db_fetch('SELECT id, total_amount, amount_paid, due_date, status FROM billing_invoices WHERE id = ? LIMIT 1', [$invoiceId]);
        if (!$row) {
            return false;
        }

        $total = (float)($row['total_amount'] ?? 0);
        $paid = (float)($row['amount_paid'] ?? 0);
        $dueDate = (string)($row['due_date'] ?? '');
        $status = (string)($row['status'] ?? 'issued');

        if ($status === 'cancelled') {
            return true;
        }

        if ($paid >= $total && $total > 0) {
            $status = 'paid';
        } elseif ($paid > 0) {
            $status = 'partially_paid';
        } else {
            $status = 'issued';
        }

        if (($status === 'issued' || $status === 'partially_paid') && $dueDate !== '' && strtotime($dueDate) < strtotime(date('Y-m-d'))) {
            $status = 'overdue';
        }

        return (bool)db_query('UPDATE billing_invoices SET status = ? WHERE id = ? LIMIT 1', [$status, $invoiceId]);
    }
}

if (!function_exists('billing_user_can_access_invoice')) {
    function billing_user_can_access_invoice(int $invoiceId, int $userId = 0, ?string $userRole = null): bool
    {
        if ($invoiceId <= 0 || !db_connected()) {
            return false;
        }

        $role = strtolower(trim((string)($userRole ?? '')));
        if ($role === '') {
            $sessionUser = function_exists('current_user') ? current_user() : null;
            $role = is_array($sessionUser) ? strtolower(trim((string)($sessionUser['role'] ?? ''))) : '';
        }

        if ($role === 'admin') {
            return true;
        }

        $sessionUser = function_exists('current_user') ? current_user() : null;
        $sessionEmail = is_array($sessionUser) ? strtolower(trim((string)($sessionUser['email'] ?? ''))) : '';
        $uid = $userId > 0 ? $userId : (function_exists('current_user_id') ? current_user_id() : 0);

        $row = db_fetch(
            'SELECT bi.id, LOWER(COALESCE(bi.client_email,\'\')) AS invoice_email, p.client_id, LOWER(COALESCE(p.owner_email,\'\')) AS owner_email
             FROM billing_invoices bi
             LEFT JOIN projects p ON p.id = bi.project_id
             WHERE bi.id = ? LIMIT 1',
            [$invoiceId]
        );

        if (!$row) {
            return false;
        }

        if ($uid > 0 && (int)($row['client_id'] ?? 0) === $uid) {
            return true;
        }

        if ($sessionEmail !== '') {
            if ($sessionEmail === (string)($row['invoice_email'] ?? '')) {
                return true;
            }
            if ($sessionEmail === (string)($row['owner_email'] ?? '')) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('billing_get_invoice_context')) {
    /**
     * @return array<string,mixed>|null
     */
    function billing_get_invoice_context(int $invoiceId): ?array
    {
        if ($invoiceId <= 0 || !db_connected()) {
            return null;
        }

        $invoice = db_fetch('SELECT * FROM billing_invoices WHERE id = ? LIMIT 1', [$invoiceId]);
        if (!$invoice) {
            return null;
        }

        $projectId = (int)($invoice['project_id'] ?? 0);
        $project = db_fetch('SELECT id, name, owner_name, owner_contact, owner_email, location, map_link, address FROM projects WHERE id = ? LIMIT 1', [$projectId]);
        if (!$project) {
            $project = [
                'id' => $projectId,
                'name' => 'Project #' . $projectId,
                'owner_name' => (string)($invoice['client_name'] ?? 'Client'),
                'owner_contact' => (string)($invoice['client_contact'] ?? ''),
                'owner_email' => (string)($invoice['client_email'] ?? ''),
                'location' => '',
                'map_link' => '',
                'address' => '',
            ];
        }

        $goods = db_fetch_all('SELECT id, sku, name, description, unit, quantity, unit_price, total_price FROM project_goods WHERE project_id = ? ORDER BY created_at ASC', [$projectId]);

        $baseFee = (float)($invoice['base_fee'] ?? 0);
        $goodsTotal = (float)($invoice['goods_total'] ?? 0);
        $discount = (float)($invoice['discount_amount'] ?? 0);
        $tax = (float)($invoice['tax_amount'] ?? 0);
        $total = (float)($invoice['total_amount'] ?? 0);
        $subtotal = max(0, $baseFee + $goodsTotal - $discount);

        $invoiceCode = (string)($invoice['invoice_code'] ?? ('BIL-' . $invoiceId));
        $shareUrl = rtrim((string)BASE_URL, '/') . '/client/billing_portal.php?invoice_id=' . $invoiceId;

        return [
            'invoice' => $invoice,
            'project' => $project,
            'goods' => $goods,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
            'invoice_code' => $invoiceCode,
            'share_url' => $shareUrl,
        ];
    }
}

if (!function_exists('billing_generate_invoice_pdf_binary')) {
    /**
     * @return array{ok:bool,binary:string,filename:string,error:string}
     */
    function billing_generate_invoice_pdf_binary(int $invoiceId): array
    {
        $ctx = billing_get_invoice_context($invoiceId);
        if (!$ctx) {
            return ['ok' => false, 'binary' => '', 'filename' => '', 'error' => 'Invoice context unavailable.'];
        }

        $templatePath = rtrim((string)PROJECT_ROOT, '/\\') . '/dashboard/invoice_pdf_template.php';
        if (!file_exists($templatePath)) {
            return ['ok' => false, 'binary' => '', 'filename' => '', 'error' => 'PDF template file is missing.'];
        }
        require_once $templatePath;

        if (!function_exists('invoice_pdf_html')) {
            return ['ok' => false, 'binary' => '', 'filename' => '', 'error' => 'PDF template renderer is unavailable.'];
        }

        $html = invoice_pdf_html(
            $ctx['project'],
            $ctx['goods'],
            (float)$ctx['subtotal'],
            (float)$ctx['tax'],
            (float)$ctx['total'],
            (string)$ctx['invoice_code'],
            (string)$ctx['share_url']
        );

        $autoload = rtrim((string)PROJECT_ROOT, '/\\') . '/vendor/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        }

        if (!class_exists('Dompdf\\Dompdf')) {
            return ['ok' => false, 'binary' => '', 'filename' => '', 'error' => 'Dompdf is not available. Install dependencies via Composer.'];
        }

        try {
            $options = new Dompdf\Options();
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf\Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $pdfBinary = (string)$dompdf->output();

            return [
                'ok' => true,
                'binary' => $pdfBinary,
                'filename' => (string)$ctx['invoice_code'] . '.pdf',
                'error' => '',
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'binary' => '', 'filename' => '', 'error' => 'PDF generation failed: ' . $e->getMessage()];
        }
    }
}

if (!function_exists('billing_send_invoice_email')) {
    /**
     * @return array{ok:bool,error:string,target:string}
     */
    function billing_send_invoice_email(int $invoiceId, ?string $toEmail = null): array
    {
        $ctx = billing_get_invoice_context($invoiceId);
        if (!$ctx) {
            return ['ok' => false, 'error' => 'Invoice context unavailable.', 'target' => ''];
        }

        $invoice = (array)$ctx['invoice'];
        $project = (array)$ctx['project'];
        $goods = (array)$ctx['goods'];

        $target = strtolower(trim((string)($toEmail ?? ($invoice['client_email'] ?? ''))));
        if (!filter_var($target, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Client email is missing or invalid.', 'target' => $target];
        }

        $templatePath = rtrim((string)PROJECT_ROOT, '/\\') . '/dashboard/invoice_email_template.php';
        if (!file_exists($templatePath)) {
            return ['ok' => false, 'error' => 'Email template file is missing.', 'target' => $target];
        }
        require_once $templatePath;

        if (!function_exists('invoice_email_html')) {
            return ['ok' => false, 'error' => 'Email template renderer is unavailable.', 'target' => $target];
        }

        $projectName = trim((string)($project['name'] ?? 'Project'));
        $invoiceCode = (string)($ctx['invoice_code'] ?? ('BIL-' . $invoiceId));
        $subject = 'Invoice ' . $invoiceCode . ' - ' . $projectName;

        // Attempt to create a Razorpay payment link (falls back to the invoice page URL)
        $paymentLink = (string)$ctx['share_url'];
        $razHelper = rtrim((string)PROJECT_ROOT, '/\\') . '/app/Shared/Payments/razorpay_helper.php';
        if (file_exists($razHelper)) {
            try {
                require_once $razHelper;
                if (function_exists('razorpay_create_payment_link')) {
                    $custName = trim((string)($project['owner_name'] ?? $invoice['client_name'] ?? ''));
                    $custEmail = trim((string)($invoice['client_email'] ?? $project['owner_email'] ?? ''));
                    $custContact = trim((string)($invoice['client_contact'] ?? $project['owner_contact'] ?? ''));
                    $rz = razorpay_create_payment_link([
                        'amount' => (float)$ctx['total'],
                        'currency' => 'INR',
                        'reference_id' => $invoiceCode,
                        'description' => 'Payment for invoice ' . $invoiceCode,
                        'customer' => ['name' => $custName, 'email' => $custEmail, 'contact' => $custContact],
                        'notify' => ['email' => true, 'sms' => false],
                        'reminder_enable' => true,
                    ]);
                    if (!empty($rz['ok']) && !empty($rz['url'])) {
                        $paymentLink = $rz['url'];
                    }
                }
            } catch (Throwable $e) {
                // silently fall back to share URL
            }
        }

        $html = invoice_email_html(
            $project,
            $goods,
            (float)$ctx['subtotal'],
            (float)$ctx['tax'],
            (float)$ctx['total'],
            $invoiceCode,
            (string)$ctx['share_url'],
            $paymentLink
        );

        $textBody = 'Invoice ' . $invoiceCode . ' for project ' . $projectName . PHP_EOL .
            'Total: ' . number_format((float)$ctx['total'], 2) . PHP_EOL .
            'View and pay online: ' . $paymentLink;

        $fromEmail = getenv('MAIL_FROM') ?: (getenv('SMTP_FROM') ?: 'no-reply@ripaldesign.studio');
        $fromName = getenv('MAIL_FROM_NAME') ?: 'Ripal Design';

        $sent = false;
        $error = '';

        $autoload = rtrim((string)PROJECT_ROOT, '/\\') . '/vendor/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        }

        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail->CharSet = 'UTF-8';
                $mail->isHTML(true);

                require_once rtrim((string)PROJECT_ROOT, '/\\') . '/app/Shared/Mail/mail_helper.php';
                // Centralized SMTP configuration (reads from env). Returns false when no SMTP host is configured.
                @configure_mailer($mail);

                $mail->setFrom($fromEmail, $fromName);
                $mail->addAddress($target);
                $mail->Subject = $subject;
                $mail->Body = $html;
                $mail->AltBody = $textBody;
                $mail->send();
                $sent = true;
            } catch (Throwable $e) {
                $error = $e->getMessage();
                $sent = false;
            }
        }

        if (!$sent) {
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= 'From: ' . $fromName . ' <' . $fromEmail . "\r\n";
            $headers .= 'Reply-To: ' . $fromEmail . "\r\n";

            if (!mail($target, $subject, $html, $headers)) {
                if ($error === '') {
                    $error = 'Unable to send invoice email using configured mail transports.';
                }
            } else {
                $sent = true;
            }
        }

        return ['ok' => $sent, 'error' => $error, 'target' => $target];
    }
}

if (!function_exists('billing_user_can_access_invoice')) {
    function billing_user_can_access_invoice(int $invoiceId, int $userId = 0, ?string $userRole = null): bool
    {
        if ($invoiceId <= 0 || !db_connected()) {
            return false;
        }

        $role = strtolower(trim((string)($userRole ?? '')));
        if ($role === '') {
            $sessionUser = function_exists('current_user') ? current_user() : null;
            $role = is_array($sessionUser) ? strtolower(trim((string)($sessionUser['role'] ?? ''))) : '';
        }

        if ($role === 'admin') {
            return true;
        }

        $sessionUser = function_exists('current_user') ? current_user() : null;
        $sessionEmail = is_array($sessionUser) ? strtolower(trim((string)($sessionUser['email'] ?? ''))) : '';
        $uid = $userId > 0 ? $userId : (function_exists('current_user_id') ? current_user_id() : 0);

        $row = db_fetch(
            'SELECT bi.id, LOWER(COALESCE(bi.client_email,\'\')) AS invoice_email, p.client_id, LOWER(COALESCE(p.owner_email,\'\')) AS owner_email
             FROM billing_invoices bi
             LEFT JOIN projects p ON p.id = bi.project_id
             WHERE bi.id = ? LIMIT 1',
            [$invoiceId]
        );

        if (!$row) {
            return false;
        }

        if ($uid > 0 && (int)($row['client_id'] ?? 0) === $uid) {
            return true;
        }

        if ($sessionEmail !== '') {
            if ($sessionEmail === (string)($row['invoice_email'] ?? '')) {
                return true;
            }
            if ($sessionEmail === (string)($row['owner_email'] ?? '')) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('billing_get_invoice_context')) {
    /**
     * @return array<string,mixed>|null
     */
    function billing_get_invoice_context(int $invoiceId): ?array
    {
        if ($invoiceId <= 0 || !db_connected()) {
            return null;
        }

        $invoice = db_fetch('SELECT * FROM billing_invoices WHERE id = ? LIMIT 1', [$invoiceId]);
        if (!$invoice) {
            return null;
        }

        $projectId = (int)($invoice['project_id'] ?? 0);
        $project = db_fetch('SELECT id, name, owner_name, owner_contact, owner_email, location, map_link, address FROM projects WHERE id = ? LIMIT 1', [$projectId]);
        if (!$project) {
            $project = [
                'id' => $projectId,
                'name' => 'Project #' . $projectId,
                'owner_name' => (string)($invoice['client_name'] ?? 'Client'),
                'owner_contact' => (string)($invoice['client_contact'] ?? ''),
                'owner_email' => (string)($invoice['client_email'] ?? ''),
                'location' => '',
                'map_link' => '',
                'address' => '',
            ];
        }

        $goods = db_fetch_all('SELECT id, sku, name, description, unit, quantity, unit_price, total_price FROM project_goods WHERE project_id = ? ORDER BY created_at ASC', [$projectId]);

        $baseFee = (float)($invoice['base_fee'] ?? 0);
        $goodsTotal = (float)($invoice['goods_total'] ?? 0);
        $discount = (float)($invoice['discount_amount'] ?? 0);
        $tax = (float)($invoice['tax_amount'] ?? 0);
        $total = (float)($invoice['total_amount'] ?? 0);
        $subtotal = max(0, $baseFee + $goodsTotal - $discount);

        $invoiceCode = (string)($invoice['invoice_code'] ?? ('BIL-' . $invoiceId));
        $shareUrl = rtrim((string)BASE_URL, '/') . '/client/billing_portal.php?invoice_id=' . $invoiceId;

        return [
            'invoice' => $invoice,
            'project' => $project,
            'goods' => $goods,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
            'invoice_code' => $invoiceCode,
            'share_url' => $shareUrl,
        ];
    }
}

if (!function_exists('billing_generate_invoice_pdf_binary')) {
    /**
     * @return array{ok:bool,binary:string,filename:string,error:string}
     */
    function billing_generate_invoice_pdf_binary(int $invoiceId): array
    {
        $ctx = billing_get_invoice_context($invoiceId);
        if (!$ctx) {
            return ['ok' => false, 'binary' => '', 'filename' => '', 'error' => 'Invoice context unavailable.'];
        }

        $templatePath = rtrim((string)PROJECT_ROOT, '/\\') . '/dashboard/invoice_pdf_template.php';
        if (!file_exists($templatePath)) {
            return ['ok' => false, 'binary' => '', 'filename' => '', 'error' => 'PDF template file is missing.'];
        }
        require_once $templatePath;

        if (!function_exists('invoice_pdf_html')) {
            return ['ok' => false, 'binary' => '', 'filename' => '', 'error' => 'PDF template renderer is unavailable.'];
        }

        $html = invoice_pdf_html(
            $ctx['project'],
            $ctx['goods'],
            (float)$ctx['subtotal'],
            (float)$ctx['tax'],
            (float)$ctx['total'],
            (string)$ctx['invoice_code'],
            (string)$ctx['share_url']
        );

        $autoload = rtrim((string)PROJECT_ROOT, '/\\') . '/vendor/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        }

        if (!class_exists('Dompdf\\Dompdf')) {
            return ['ok' => false, 'binary' => '', 'filename' => '', 'error' => 'Dompdf is not available. Install dependencies via Composer.'];
        }

        try {
            $options = new Dompdf\Options();
            $options->set('isRemoteEnabled', true);
            $dompdf = new Dompdf\Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $pdfBinary = (string)$dompdf->output();

            return [
                'ok' => true,
                'binary' => $pdfBinary,
                'filename' => (string)$ctx['invoice_code'] . '.pdf',
                'error' => '',
            ];
        } catch (Throwable $e) {
            return ['ok' => false, 'binary' => '', 'filename' => '', 'error' => 'PDF generation failed: ' . $e->getMessage()];
        }
    }
}

if (!function_exists('billing_send_invoice_email')) {
    /**
     * @return array{ok:bool,error:string,target:string}
     */
    function billing_send_invoice_email(int $invoiceId, ?string $toEmail = null): array
    {
        $ctx = billing_get_invoice_context($invoiceId);
        if (!$ctx) {
            return ['ok' => false, 'error' => 'Invoice context unavailable.', 'target' => ''];
        }

        $invoice = (array)$ctx['invoice'];
        $project = (array)$ctx['project'];
        $goods = (array)$ctx['goods'];

        $target = strtolower(trim((string)($toEmail ?? ($invoice['client_email'] ?? ''))));
        if (!filter_var($target, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Client email is missing or invalid.', 'target' => $target];
        }

        $templatePath = rtrim((string)PROJECT_ROOT, '/\\') . '/dashboard/invoice_email_template.php';
        if (!file_exists($templatePath)) {
            return ['ok' => false, 'error' => 'Email template file is missing.', 'target' => $target];
        }
        require_once $templatePath;

        if (!function_exists('invoice_email_html')) {
            return ['ok' => false, 'error' => 'Email template renderer is unavailable.', 'target' => $target];
        }

        $projectName = trim((string)($project['name'] ?? 'Project'));
        $invoiceCode = (string)($ctx['invoice_code'] ?? ('BIL-' . $invoiceId));
        $subject = 'Invoice ' . $invoiceCode . ' - ' . $projectName;
        $html = invoice_email_html(
            $project,
            $goods,
            (float)$ctx['subtotal'],
            (float)$ctx['tax'],
            (float)$ctx['total'],
            $invoiceCode,
            (string)$ctx['share_url']
        );

        $textBody = 'Invoice ' . $invoiceCode . ' for project ' . $projectName . PHP_EOL .
            'Total: ' . number_format((float)$ctx['total'], 2) . PHP_EOL .
            'View and pay online: ' . (string)$ctx['share_url'];

        $fromEmail = getenv('MAIL_FROM') ?: (getenv('SMTP_FROM') ?: 'no-reply@ripaldesign.studio');
        $fromName = getenv('MAIL_FROM_NAME') ?: 'Ripal Design';

        $sent = false;
        $error = '';

        $autoload = rtrim((string)PROJECT_ROOT, '/\\') . '/vendor/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        }

        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            try {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                $mail->CharSet = 'UTF-8';
                $mail->isHTML(true);

                    require_once rtrim((string)PROJECT_ROOT, '/\\') . '/app/Shared/Mail/mail_helper.php';
                    @configure_mailer($mail);

                $mail->setFrom($fromEmail, $fromName);
                $mail->addAddress($target);
                $mail->Subject = $subject;
                $mail->Body = $html;
                $mail->AltBody = $textBody;
                $mail->send();
                $sent = true;
            } catch (Throwable $e) {
                $error = $e->getMessage();
                $sent = false;
            }
        }

        if (!$sent) {
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= 'From: ' . $fromName . ' <' . $fromEmail . "\r\n";
            $headers .= 'Reply-To: ' . $fromEmail . "\r\n";

            if (!mail($target, $subject, $html, $headers)) {
                if ($error === '') {
                    $error = 'Unable to send invoice email using configured mail transports.';
                }
            } else {
                $sent = true;
            }
        }

        return ['ok' => $sent, 'error' => $error, 'target' => $target];
    }
}
