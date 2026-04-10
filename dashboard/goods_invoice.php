<?php
// dashboard/goods_invoice.php - redesigned invoice layout (printable + responsive)
require_once __DIR__ . '/../includes/init.php';
require_login();
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
if (!$project_id) { header('Location: dashboard.php'); exit; }

$sessionUser = $_SESSION['user'] ?? [];
$sessionRole = strtolower(trim((string)($sessionUser['role'] ?? '')));
$isClientReadOnly = ($sessionRole === 'client');

// Handle form submissions: add item or save meta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  require_csrf();
  $action = $_POST['action'] ?? '';

    // Client role can only view invoice; no write or request submission actions.
    if ($isClientReadOnly && in_array($action, ['email_invoice', 'add_item', 'save_meta', 'request_invoice_edit'], true)) {
        set_flash('Clients can view invoices only.', 'error');
        header('Location: goods_invoice.php?project_id=' . $project_id);
        exit;
    }

    if ($action === 'request_invoice_edit') {
        $requestDetails = trim((string)($_POST['request_details'] ?? ''));
        if ($requestDetails === '') {
            set_flash('Please describe the invoice changes you want.', 'error');
            header('Location: goods_invoice.php?project_id=' . $project_id);
            exit;
        }

        $submittedBy = (int)($_SESSION['user_id'] ?? ($sessionUser['id'] ?? 0));
        $submitted = false;

        if (db_connected()) {
            try {
                db_query(
                    'INSERT INTO review_requests (project_id, submitted_by, subject, description, urgency, status) VALUES (?, ?, ?, ?, ?, "pending")',
                    [
                        $project_id,
                        $submittedBy > 0 ? $submittedBy : null,
                        'Invoice edit request for project #' . $project_id,
                        $requestDetails,
                        'normal',
                    ]
                );
                $submitted = true;
            } catch (Exception $e) {
                error_log('Invoice edit request failed: ' . $e->getMessage());
            }
        }

        if ($submitted) {
            set_flash('Your invoice edit request has been submitted.', 'success');
        } else {
            set_flash('Could not submit your request right now. Please try again.', 'error');
        }

        header('Location: goods_invoice.php?project_id=' . $project_id);
        exit;
    }

    // Email invoice action
    if ($action === 'email_invoice') {
        $to = trim($_POST['to_email'] ?? '');
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            set_flash('Please provide a valid recipient email address.', 'error');
            header('Location: goods_invoice.php?project_id=' . $project_id);
            exit;
        }

        // Load project and goods to compute invoice totals (needed for PDF/email)
        $project = ['id'=>$project_id,'name'=>'Project '.$project_id,'owner_name'=>'Client','owner_contact'=>''];
        if (isset($pdo) && $pdo instanceof PDO) {
            $stmt = $pdo->prepare("SELECT id,name,owner_name,owner_contact,location,COALESCE(map_link, '') AS map_link, COALESCE(address, '') AS address FROM projects WHERE id = :id LIMIT 1");
            $stmt->execute(['id'=>$project_id]);
            $r = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($r) $project = $r;
        }

        $goods = [];
        $subtotal = 0.0;
        if (isset($pdo) && $pdo instanceof PDO) {
            $stmt = $pdo->prepare('SELECT id,sku,name,description,unit,quantity,unit_price,total_price FROM project_goods WHERE project_id = :pid ORDER BY created_at ASC');
            $stmt->execute(['pid'=>$project_id]);
            $goods = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($goods as $g) { $subtotal += (float)$g['total_price']; }
        }

        $tax_rate = 0.18; // example 18%
        $tax = round($subtotal * $tax_rate, 2);
        $total = round($subtotal + $tax, 2);
        $invoice_id = 'INV-' . str_pad($project_id, 6, '0', STR_PAD_LEFT) . '-' . date('Ymd');
        $share_url = rtrim(BASE_URL, '/') . '/dashboard/goods_invoice.php?project_id=' . $project_id;

        $userMessage = trim($_POST['message'] ?? '');

        // Prepare message
        // Use a simple ASCII subject to avoid character-encoding issues in mail headers
        $projectName = trim((string)($project['name'] ?? ''));
        $ownerName = trim((string)($project['owner_name'] ?? ''));
        $subject = 'Invoice for ' . ($projectName !== '' ? $projectName : 'Property') . ' of ' . ($ownerName !== '' ? $ownerName : 'Owner');
        $body = "Invoice for project: " . ($project['name'] ?? '') . "\n";
        $body .= "Invoice ID: " . $invoice_id . "\n";
        $body .= "Date: " . date('F j, Y') . "\n\n";
        $body .= "Subtotal: ₹ " . number_format($subtotal, 2) . "\n";
        $body .= "Tax (" . ($tax_rate * 100) . "%): ₹ " . number_format($tax, 2) . "\n";
        $body .= "Total: ₹ " . number_format($total, 2) . "\n\n";
        $body .= "View the invoice online: " . $share_url . "\n\n";
        if ($userMessage) $body .= "Message from sender:\n" . $userMessage . "\n\n";
        $body .= "Regards,\nRipal Design";

        // Default from values (can be overridden by env). Fallback to the configured sender.
        $fromEmail = getenv('MAIL_FROM') ?: (getenv('SMTP_FROM') ?: 'yashhvinchhi@gmail.com');
        $fromName = getenv('MAIL_FROM_NAME') ?: 'Ripal Design';

        $sent = false;
        $errorMsg = '';

                // Try PHPMailer if available via Composer
                $composerAutoload = __DIR__ . '/../vendor/autoload.php';
                if (file_exists($composerAutoload)) {
                        require_once $composerAutoload;
                        // If Composer autoload is present but PHPMailer class is still unavailable,
                        // try including the bundled `src/` PHPMailer files as a fallback.
                        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
                            $localSrc = __DIR__ . '/../src';
                            // load in order that satisfies dependencies
                            $parts = [
                                'Exception.php',
                                'OAuthTokenProvider.php',
                                'OAuth.php',
                                'POP3.php',
                                'SMTP.php',
                                'PHPMailer.php',
                                'DSNConfigurator.php'
                            ];
                            foreach ($parts as $p) {
                                $f = $localSrc . '/' . $p;
                                if (file_exists($f)) require_once $f;
                            }
                        }
                        try {
                            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                            // Ensure UTF-8 is used for headers and body
                            $mail->CharSet = 'UTF-8';
                            $mail->Encoding = 'base64';

                                // Prefer environment SMTP config when present; otherwise fallback to known credentials from public/mailer.php
                                $envHost = getenv('MAIL_HOST') ?: getenv('SMTP_HOST');
                                if ($envHost) {
                                        $mail->isSMTP();
                                        $mail->Host = $envHost;
                                        $mail->SMTPAuth = true;
                                        $mail->Username = getenv('MAIL_USERNAME') ?: getenv('SMTP_USER');
                                        $mail->Password = getenv('MAIL_PASSWORD') ?: getenv('SMTP_PASS');
                                        $mail->SMTPSecure = getenv('MAIL_ENCRYPTION') ?: PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                                        $mail->Port = getenv('MAIL_PORT') ?: 587;
                                } else {
                                        // Use credentials present in public/mailer.php as fallback
                                        $mail->isSMTP();
                                        $mail->Host = 'smtp.gmail.com';
                                        $mail->SMTPAuth = true;
                                        $mail->Username = 'yashhvinchhi@gmail.com';
                                        $mail->Password = 'odoc sctf jtuf ejvv';
                                        $mail->SMTPSecure = 'tls';
                                        $mail->Port = 587;
                                }

                                $mail->setFrom($fromEmail, $fromName);
                                $mail->addAddress($to);
                                $mail->Subject = $subject;
                                $mail->Body = $body;
                                $mail->AltBody = $body;

                                // Use an HTML invoice template for the email body (no PDF attachment)
                                $htmlBody = null;
                                $templateFile = __DIR__ . '/invoice_email_template.php';
                                if (file_exists($templateFile)) {
                                    require_once $templateFile;
                                    try {
                                        // Create a UPI deep link prefilled with payee VPA and amount
                                        $upiVpa = 'yashhvinchhi@okaxis';
                                        $upiName = 'Ripal Design';
                                        $upiAmount = number_format((float)$total, 2, '.', '');
                                        $upiNote = 'Invoice ' . $invoice_id;
                                        $upiParams = [
                                            'pa' => $upiVpa,
                                            'pn' => $upiName,
                                            'am' => $upiAmount,
                                            'cu' => 'INR',
                                            'tn' => $upiNote,
                                            'tr' => $invoice_id,
                                        ];
                                        $upiQuery = http_build_query($upiParams, '', '&', PHP_QUERY_RFC3986);
                                        $payment_link = 'upi://pay?' . $upiQuery;

                                        // Try to generate an inline QR code (PNG) for the UPI deep link using Google Chart API
                                        $qr_data_uri = null;
                                        $qrServiceUrl = 'https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl=' . rawurlencode($payment_link) . '&chld=L|1';
                                        $qrBin = false;
                                        if (ini_get('allow_url_fopen')) {
                                            $qrBin = @file_get_contents($qrServiceUrl);
                                        }
                                        if ($qrBin === false && function_exists('curl_init')) {
                                            $ch = curl_init($qrServiceUrl);
                                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                                            curl_setopt($ch, CURLOPT_TIMEOUT, 8);
                                            $qrBin = curl_exec($ch);
                                            curl_close($ch);
                                        }
                                        if (is_string($qrBin) && strlen($qrBin) > 0) {
                                            $qr_data_uri = 'data:image/png;base64,' . base64_encode($qrBin);
                                        }

                                        // Render the HTML email (button uses $payment_link). $share_url remains available as web fallback.
                                        $htmlBody = invoice_email_html($project, $goods, $subtotal, $tax, $total, $invoice_id, $share_url, $payment_link, $qr_data_uri);
                                        $mail->isHTML(true);
                                        $mail->Body = $htmlBody;
                                        $mail->AltBody = $body;
                                    } catch (Exception $e) {
                                        // don't break sending if template rendering fails
                                        $errorMsg = $e->getMessage();
                                    }
                                } else {
                                    // fallback: send basic HTML version of the plaintext body
                                    $mail->isHTML(true);
                                    $mail->Body = nl2br(htmlspecialchars($body));
                                    $mail->AltBody = $body;
                                }

                                $mail->send();
                                $sent = true;
                        } catch (Exception $ex) {
                                $errorMsg = $ex->getMessage();
                                $sent = false;
                        }
                }

        // Fallback to PHP mail() — send HTML when available
        if (!$sent) {
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= 'From: ' . $fromName . ' <' . $fromEmail . "\r\n";
            $headers .= 'Reply-To: ' . $fromEmail . "\r\n";
            $headers .= 'X-Mailer: PHP/' . phpversion();
            $messageForMail = $htmlBody ?? nl2br(htmlspecialchars($body));
            if (mail($to, $subject, $messageForMail, $headers)) {
                $sent = true;
            } else {
                if ($errorMsg === '') $errorMsg = 'Server unable to send mail (mail() returned false).';
            }
        }

        if ($sent) {
            $msg = 'Invoice emailed to ' . htmlspecialchars($to);
            if (empty($htmlBody)) $msg .= ' (sent as plain text)';
            set_flash($msg, 'success');
        } else {
            set_flash('Failed to send invoice: ' . htmlspecialchars($errorMsg), 'error');
        }

        header('Location: goods_invoice.php?project_id=' . $project_id);
        exit;
    }
  if ($action === 'add_item') {
    $sku = trim($_POST['sku'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $unit = trim($_POST['unit'] ?? 'pcs');
    $quantity = max(0, (int)($_POST['quantity'] ?? 0));
    $unit_price = (float)($_POST['unit_price'] ?? 0);
    $total_price = round($quantity * $unit_price, 2);
    if ($name && isset($pdo) && $pdo instanceof PDO) {
      $ins = $pdo->prepare('INSERT INTO project_goods (project_id,sku,name,description,unit,quantity,unit_price,total_price) VALUES (:pid,:sku,:name,:description,:unit,:quantity,:unit_price,:total)');
      $ins->execute(['pid'=>$project_id,'sku'=>$sku,'name'=>$name,'description'=>$description,'unit'=>$unit,'quantity'=>$quantity,'unit_price'=>$unit_price,'total'=>$total_price]);
    }
    header('Location: goods_invoice.php?project_id=' . $project_id);
    exit;
  } elseif ($action === 'save_meta') {
    $client = trim($_POST['client_name'] ?? '');
    $worker = trim($_POST['worker_name'] ?? '');
    if (isset($pdo) && $pdo instanceof PDO) {
      $upd = $pdo->prepare('UPDATE projects SET owner_name = :client, worker_name = :worker WHERE id = :id');
      $upd->execute(['client'=>$client,'worker'=>$worker,'id'=>$project_id]);
    }
    header('Location: goods_invoice.php?project_id=' . $project_id);
    exit;
  }
}

// Load project
$project = ['id'=>$project_id,'name'=>'Project '.$project_id,'owner_name'=>'Client','owner_contact'=>''];
if (isset($pdo) && $pdo instanceof PDO) {
    $stmt = $pdo->prepare("SELECT id,name,owner_name,owner_contact,location,COALESCE(map_link, '') AS map_link, COALESCE(address, '') AS address FROM projects WHERE id = :id LIMIT 1");
    $stmt->execute(['id'=>$project_id]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($r) $project = $r;
}

// Load goods
$goods = [];
$subtotal = 0.0;
if (isset($pdo) && $pdo instanceof PDO) {
    $stmt = $pdo->prepare('SELECT id,sku,name,description,unit,quantity,unit_price,total_price FROM project_goods WHERE project_id = :pid ORDER BY created_at ASC');
    $stmt->execute(['pid'=>$project_id]);
    $goods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($goods as $g) { $subtotal += (float)$g['total_price']; }
}

// Invoice calculations
$tax_rate = 0.18; // example 18%
$tax = round($subtotal * $tax_rate, 2);
$total = round($subtotal + $tax, 2);
$invoice_id = 'INV-' . str_pad($project_id, 6, '0', STR_PAD_LEFT) . '-' . date('Ymd');
$share_url = rtrim(BASE_URL, '/') . '/dashboard/goods_invoice.php?project_id=' . $project_id;
$upi_vpa = 'yashhvinchhi@okaxis';
$upi_name = 'Ripal Design';
$upi_amount = number_format((float)$total, 2, '.', '');
$upi_note = 'Invoice ' . $invoice_id;
$upiParams = [
    'pa' => $upi_vpa,
    'pn' => $upi_name,
    'am' => $upi_amount,
    'cu' => 'INR',
    'tn' => $upi_note,
    'tr' => $invoice_id,
];
$upi_link = 'upi://pay?' . http_build_query($upiParams, '', '&', PHP_QUERY_RFC3986);
$upi_qr_url = 'https://chart.googleapis.com/chart?cht=qr&chs=320x320&chl=' . rawurlencode($upi_link) . '&chld=L|1';

$displayName = $_SESSION['user']['first_name'] ?? 'User';
$title = "Invoice " . $invoice_id . " | Ripal Design";

$HEADER_MODE = 'dashboard';
require_once __DIR__ . '/../Common/header.php';
// Show any flash messages from actions like emailing
if (function_exists('render_flash')) { render_flash(); }
?>
<!-- Premium Invoice Styles -->
<style>
    @media print {
        header, nav, .alt-header, .no-print, footer, .site-footer { display: none !important; }
        body { background: #fff !important; padding: 0 !important; color: #000 !important; }
        .invoice-container { box-shadow: none !important; border: none !important; width: 100% !important; max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
        .print-break-inside-avoid { page-break-inside: avoid; }
        .bg-gray-50\/50 { background-color: #fafafa !important; }
        .bg-rajkot-rust\/5 { background-color: #fef4f4 !important; }
        .text-rajkot-rust { color: #94180C !important; }
    }
</style>

<div class="min-h-screen bg-canvas-white font-sans text-foundation-grey pb-12">
    <!-- Header Hero (no-print) -->
    <header class="bg-foundation-grey text-white pt-24 pb-12 px-4 sm:px-6 lg:px-8 mb-8 border-b-2 border-rajkot-rust no-print">
        <div class="max-w-5xl mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <h1 class="text-3xl md:text-4xl font-serif font-bold">Goods Invoice</h1>
                <p class="text-gray-400 mt-2 text-sm uppercase tracking-widest font-bold opacity-70">
                    Project: <?php echo esc($project['name']); ?> &middot; <?php echo h($invoice_id); ?>
                </p>
            </div>
            <div class="flex gap-3">
                <button onclick="window.print()" class="bg-rajkot-rust hover:bg-[#7f140a] text-white px-6 py-2.5 font-bold transition-all shadow-lg flex items-center gap-2">
                    <i data-lucide="printer" class="w-4 h-4"></i> Print Invoice
                </button>
            </div>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Dashboard Navigation Breadcrumb (no-print) -->
        <div class="flex items-center gap-2 mb-6 text-sm no-print">
            <a href="dashboard.php" class="text-gray-400 hover:text-rajkot-rust no-underline transition-colors uppercase tracking-widest font-bold">Dashboard</a>
            <span class="text-gray-300">/</span>
            <span class="text-rajkot-rust uppercase tracking-widest font-bold">Invoice</span>
        </div>

        <div class="invoice-container bg-white shadow-premium border border-gray-100 p-8 md:p-12 mb-8 relative overflow-hidden">
            <!-- Signature Accent -->
            <div class="absolute top-0 right-0 w-32 h-32 bg-rajkot-rust/5 rounded-bl-full pointer-events-none"></div>
            
            <!-- Invoice Header -->
            <div class="flex flex-col md:flex-row justify-between gap-8 mb-12 border-b border-gray-50 pb-8">
                <div class="flex items-center gap-4">
                    <?php if (file_exists(PROJECT_ROOT . '/assets/Content/Logo.png')): ?>
                        <img src="<?php echo esc_attr(BASE_PATH); ?>/assets/Content/Logo.png" alt="Ripal Design" class="w-16 h-16 object-cover rounded-lg shadow-lg" />
                    <?php else: ?>
                        <div class="w-16 h-16 bg-rajkot-rust flex items-center justify-center text-white font-bold text-2xl shadow-lg">RD</div>
                    <?php endif; ?>
                    <div>
                        <h2 class="text-xl font-serif font-bold text-foundation-grey">Ripal Design</h2>
                        <p class="text-xs uppercase tracking-widest text-gray-400 font-bold">Architects & Interior Design</p>
                        <p class="text-[11px] text-gray-500 mt-1">contact@ripaldesign.example | +91 12345 67890</p>
                    </div>
                </div>
                <div class="text-left">
                    <h3 class="text-gray-300 uppercase tracking-[0.2em] font-bold text-[10px] mb-2 font-sans">Invoice Meta</h3>
                    <div class="text-rajkot-rust font-bold text-lg font-sans"><?php echo h($invoice_id); ?></div>
                    <div class="text-foundation-grey font-medium font-sans"><?php echo date('F j, Y'); ?></div>
                    <div class="text-xs text-gray-400 mt-1 font-sans">Project ID: #<?php echo (int)$project['id']; ?></div>
                </div>
            </div>

            <!-- Billing Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-12 mb-12">
                <div class="p-6 bg-gray-50/50 border border-gray-100 relative group min-h-[160px]">
                    <h4 class="text-[10px] uppercase tracking-widest font-bold text-gray-400 mb-4 flex items-center gap-2">
                        <i data-lucide="user" class="w-3 h-3 text-rajkot-rust"></i> Bill To
                    </h4>
                    <div class="text-lg font-serif font-bold text-foundation-grey mb-1"><?php echo h($project['owner_name'] ?? 'Client'); ?></div>
                    <?php if (!empty($project['owner_contact'])): ?><div class="text-sm text-gray-500 mb-1"><?php echo h($project['owner_contact']); ?></div><?php endif; ?>
                    <?php if (!empty($project['location']) || !empty($project['address']) || !empty($project['map_link'])): ?>
                        <?php
                            $directionDestination = trim((string)($project['address'] ?? $project['location'] ?? ''));
                            $mapHref = build_google_maps_direction_href((string)($project['map_link'] ?? ''), $directionDestination);
                        ?>
                        <div class="text-sm text-gray-500 italic">
                            <i data-lucide="map-pin" class="w-3 h-3 inline mr-1"></i>
                            <?php if ($mapHref !== ''): ?>
                                <a href="<?php echo htmlspecialchars($mapHref); ?>" target="_blank" rel="noopener noreferrer"><?php echo h($project['address'] ?? $project['location']); ?></a>
                            <?php else: ?>
                                <?php echo h($project['address'] ?? $project['location']); ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Meta Edit (no-print) - visible on hover -->
                    <?php if (!$isClientReadOnly): ?>
                        <div class="absolute inset-0 bg-white/95 p-6 opacity-0 group-hover:opacity-100 transition-opacity no-print flex flex-col justify-center border border-rajkot-rust/20 font-sans">
                            <h5 class="text-[10px] uppercase tracking-widest font-bold text-rajkot-rust mb-3">Edit Billing Info</h5>
                            <form method="post">
                                <?php echo csrf_token_field(); ?>
                                <input type="hidden" name="action" value="save_meta">
                                <div class="flex flex-col gap-3">
                                    <input name="client_name" placeholder="Client Name" class="w-full bg-white border border-gray-200 px-3 py-2 text-sm focus:border-rajkot-rust outline-none transition-colors" value="<?php echo h($project['owner_name'] ?? ''); ?>">
                                    <div class="flex gap-2">
                                        <input name="worker_name" placeholder="Worker Name" class="flex-1 bg-white border border-gray-200 px-3 py-2 text-sm focus:border-rajkot-rust outline-none transition-colors" value="<?php echo h($project['worker_name'] ?? ''); ?>">
                                        <button type="submit" class="bg-foundation-grey text-white px-4 py-2 text-xs font-bold uppercase tracking-widest hover:bg-black transition-colors">Save</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="p-6 bg-rajkot-rust/5 border border-rajkot-rust/10 relative flex flex-col min-h-[160px]">
                    <h4 class="text-[10px] uppercase tracking-widest font-bold text-rajkot-rust/60 mb-4 flex items-center gap-2">
                        <i data-lucide="layout" class="w-3 h-3"></i> Project Summary
                    </h4>
                    <div class="text-lg font-serif font-bold text-foundation-grey mb-1"><?php echo h($project['name']); ?></div>
                    <div class="text-sm text-gray-500 mb-6 font-mono">ID: #<?php echo (int)$project['id']; ?></div>
                    
                    <div class="mt-auto border-t border-rajkot-rust/10 pt-4">
                        <div class="text-[10px] uppercase tracking-widest font-bold text-gray-400 mb-1 font-sans">Estimated Invoice Total</div>
                        <div class="text-2xl font-serif font-bold text-rajkot-rust">₹ <?php echo number_format($total, 2); ?></div>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="overflow-x-auto mb-12">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="border-b-2 border-foundation-grey">
                            <th class="px-4 py-4 text-left text-[10px] uppercase tracking-widest font-bold text-gray-400 w-12 font-sans">#</th>
                            <th class="px-4 py-4 text-left text-[10px] uppercase tracking-widest font-bold text-gray-400 font-sans">Item Details</th>
                            <th class="px-4 py-4 text-center text-[10px] uppercase tracking-widest font-bold text-gray-400 w-24 font-sans">Qty</th>
                            <th class="px-4 py-4 text-right text-[10px] uppercase tracking-widest font-bold text-gray-400 w-32 font-sans">Unit Price</th>
                            <th class="px-4 py-4 text-right text-[10px] uppercase tracking-widest font-bold text-gray-400 w-32 font-sans">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 font-sans">
                        <?php if (empty($goods)): ?>
                            <tr><td colspan="5" class="px-4 py-12 text-center text-sm text-gray-400 italic border-b border-gray-100">No procurement items added yet.</td></tr>
                        <?php else: $i=1; foreach($goods as $g): ?>
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-4 py-6 text-sm font-mono text-gray-300"><?php echo str_pad($i++, 2, '0', STR_PAD_LEFT); ?></td>
                                <td class="px-4 py-6">
                                    <div class="font-bold text-foundation-grey"><?php echo h($g['name']); ?></div>
                                    <?php if(!empty($g['sku'])): ?><div class="text-[9px] font-mono text-rajkot-rust bg-rajkot-rust/5 px-1.5 py-0.5 inline-block mt-1 uppercase tracking-wider"><?php echo h($g['sku']); ?></div><?php endif; ?>
                                    <?php if (!empty($g['description'])): ?><div class="text-xs text-gray-400 mt-1.5 leading-relaxed"><?php echo h($g['description']); ?></div><?php endif; ?>
                                </td>
                                <td class="px-4 py-6 text-center text-sm font-medium">
                                    <span class="text-foundation-grey font-bold"><?php echo intval($g['quantity']); ?></span>
                                    <span class="text-[10px] uppercase text-gray-400 ml-1 font-bold tracking-tighter"><?php echo h($g['unit'] ?? 'pcs'); ?></span>
                                </td>
                                <td class="px-4 py-6 text-right text-sm font-medium text-gray-500 font-mono">₹ <?php echo number_format($g['unit_price'], 2); ?></td>
                                <td class="px-4 py-6 text-right text-sm font-bold text-foundation-grey font-mono">₹ <?php echo number_format($g['total_price'], 2); ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Totals Section -->
            <div class="flex flex-col md:flex-row justify-between gap-12 mb-12">
                <div class="max-w-sm">
                    <p class="text-[11px] text-gray-400 leading-relaxed italic font-sans">
                        <strong class="text-foundation-grey not-italic uppercase tracking-widest text-[9px]">Procurement Note:</strong> <br>
                        These goods are listed for procurement by the site supervisor/worker. 
                        Prices indicated are estimates based on latest market rates and are subject to change at the time of final vendor billing.
                    </p>
                </div>
                <div class="md:w-80">
                    <div class="space-y-4 font-sans">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-400 uppercase tracking-widest font-bold text-[10px]">Subtotal</span>
                            <span class="font-bold text-foundation-grey font-mono">₹ <?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-400 uppercase tracking-widest font-bold text-[10px]">Tax (<?php echo ($tax_rate*100); ?>% GST)</span>
                            <span class="font-bold text-foundation-grey font-mono">₹ <?php echo number_format($tax, 2); ?></span>
                        </div>
                        <div class="flex justify-between items-center pt-4 border-t-2 border-rajkot-rust bg-rajkot-rust/5 px-4 py-3 -mx-4">
                            <span class="text-foundation-grey uppercase tracking-widest font-black text-[11px]">Grand Total</span>
                            <span class="text-2xl font-serif font-bold text-rajkot-rust">₹ <?php echo number_format($total, 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($isClientReadOnly): ?>
            <div class="no-print mt-2 mb-10 border border-rajkot-rust/15 bg-rajkot-rust/5 p-6 md:p-8">
                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-6">
                    <div>
                        <h3 class="text-lg font-serif font-bold text-foundation-grey">Client Actions</h3>
                        <p class="text-sm text-gray-500 mt-1">You can request invoice changes and pay this invoice online.</p>
                    </div>
                    <div class="text-sm text-gray-600">
                        <div class="uppercase tracking-widest text-[10px] text-gray-400 font-bold">UPI ID</div>
                        <div class="font-mono font-bold text-foundation-grey"><?php echo h($upi_vpa); ?></div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 mt-6">
                    <button id="requestEditBtn" type="button" class="bg-foundation-grey hover:bg-black text-white px-5 py-2.5 text-[10px] font-bold uppercase tracking-widest transition-all flex items-center gap-2">
                        <i data-lucide="file-pen-line" class="w-4 h-4"></i> Request Edit
                    </button>
                    <button id="payNowBtn" type="button" class="bg-rajkot-rust hover:bg-[#7f140a] text-white px-5 py-2.5 text-[10px] font-bold uppercase tracking-widest transition-all flex items-center gap-2">
                        <i data-lucide="qr-code" class="w-4 h-4"></i> Pay Now
                    </button>
                </div>

                <div id="upiPanel" class="hidden mt-6 p-4 bg-white border border-gray-100">
                    <div class="grid grid-cols-1 md:grid-cols-[320px_1fr] gap-6 items-start">
                        <div>
                            <img src="<?php echo esc_attr($upi_qr_url); ?>" alt="UPI payment QR" class="w-full max-w-[320px] border border-gray-100" />
                            <a href="<?php echo esc_attr($upi_link); ?>" class="mt-3 inline-flex w-full max-w-[320px] justify-center bg-foundation-grey hover:bg-black text-white px-4 py-2.5 text-[10px] font-bold uppercase tracking-widest no-underline transition-all">Open UPI App to Pay</a>
                        </div>
                        <div class="space-y-2 text-sm text-gray-600">
                            <p class="font-bold text-foundation-grey">Scan QR to pay</p>
                            <p>Amount: <span class="font-bold">₹ <?php echo number_format($total, 2); ?></span></p>
                            <p>UPI ID: <span class="font-mono font-bold"><?php echo h($upi_vpa); ?></span></p>
                            <p class="text-xs text-gray-400">If QR scan is not available on your device, use the button to open your installed UPI app with amount pre-filled.</p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Action Area (no-print) -->
            <div class="no-print flex flex-wrap gap-4 pt-12 border-t border-gray-50 font-sans">
                <button id="copyLink" class="group border border-gray-100 hover:border-rajkot-rust hover:bg-white text-foundation-grey px-6 py-3 text-[10px] font-bold uppercase tracking-widest transition-all shadow-sm hover:shadow-premium flex items-center gap-2">
                    <i data-lucide="link-2" class="w-3.5 h-3.5 text-rajkot-rust group-hover:scale-110 transition-transform"></i> Copy Invoice Link
                </button>
                <?php if (!$isClientReadOnly): ?>
                    <button id="emailLink" class="group border border-gray-100 hover:border-rajkot-rust hover:bg-white text-foundation-grey px-6 py-3 text-[10px] font-bold uppercase tracking-widest transition-all shadow-sm hover:shadow-premium flex items-center gap-2">
                        <i data-lucide="mail" class="w-3.5 h-3.5 text-rajkot-rust group-hover:scale-110 transition-transform"></i> Send by Email
                    </button>
                <?php endif; ?>
                <a href="dashboard.php" class="ml-auto text-gray-400 hover:text-rajkot-rust text-[10px] font-bold uppercase tracking-widest no-underline transition-colors flex items-center gap-3 group">
                    Go Back to Dashboard 
                    <div class="w-8 h-8 rounded-full border border-gray-100 flex items-center justify-center group-hover:border-rajkot-rust group-hover:bg-rajkot-rust/5 transition-all">
                        <i data-lucide="chevron-right" class="w-4 h-4 text-rajkot-rust"></i>
                    </div>
                </a>
            </div>
            <!-- Email Modal (no-print) -->
            <?php if (!$isClientReadOnly): ?>
            <div id="emailModal" class="fixed inset-0 z-50 hidden items-center justify-center no-print" aria-hidden="true" role="dialog" aria-modal="true">
                <div class="absolute inset-0 bg-black/50"></div>
                <div class="bg-white rounded-lg shadow-lg z-10 max-w-lg w-full p-6 mx-4">
                    <h3 class="text-lg font-bold mb-2">Send Invoice by Email</h3>
                    <form id="emailModalForm" method="post" class="space-y-4">
                        <?php echo csrf_token_field(); ?>
                        <input type="hidden" name="action" value="email_invoice">
                        <div>
                            <label for="modal_to_email" class="block text-sm font-medium text-gray-700">Recipient Email</label>
                            <input id="modal_to_email" name="to_email" type="email" required class="mt-1 block w-full border border-gray-200 px-3 py-2 rounded" placeholder="recipient@example.com" />
                        </div>
                        <div>
                            <label for="modal_message" class="block text-sm font-medium text-gray-700">Message (optional)</label>
                            <textarea id="modal_message" name="message" rows="3" class="mt-1 block w-full border border-gray-200 px-3 py-2 rounded" placeholder="Optional note to include in the email"></textarea>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" id="emailModalCancel" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
                            <button type="submit" id="emailModalSend" class="px-4 py-2 bg-rajkot-rust text-white rounded">Send</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($isClientReadOnly): ?>
            <div id="requestEditModal" class="fixed inset-0 z-50 hidden items-center justify-center no-print" aria-hidden="true" role="dialog" aria-modal="true">
                <div class="absolute inset-0 bg-black/50"></div>
                <div class="bg-white rounded-lg shadow-lg z-10 max-w-xl w-full p-6 mx-4">
                    <h3 class="text-lg font-bold mb-2">Request Invoice Edit</h3>
                    <p class="text-sm text-gray-500 mb-4">Describe the changes you want. Our team will review your request.</p>
                    <form id="requestEditForm" method="post" class="space-y-4">
                        <?php echo csrf_token_field(); ?>
                        <input type="hidden" name="action" value="request_invoice_edit">
                        <div>
                            <label for="request_details" class="block text-sm font-medium text-gray-700">Requested changes</label>
                            <textarea id="request_details" name="request_details" rows="5" required class="mt-1 block w-full border border-gray-200 px-3 py-2 rounded" placeholder="Example: Please change quantity of item #2 to 3 and update total accordingly."></textarea>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" id="requestEditCancel" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-rajkot-rust text-white rounded">Submit Request</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Add Item Form (no-print) -->
        <?php if (!$isClientReadOnly): ?>
        <section class="no-print bg-white shadow-premium border border-gray-100 p-8 md:p-10 mb-12 relative overflow-hidden group font-sans">
            <div class="absolute top-0 right-0 w-24 h-1 bg-rajkot-rust opacity-20 group-hover:w-full transition-all duration-700"></div>
            <div class="flex items-center gap-4 mb-8">
                <div class="w-10 h-10 bg-rajkot-rust/10 flex items-center justify-center rounded-lg">
                    <i data-lucide="plus-circle" class="w-5 h-5 text-rajkot-rust"></i>
                </div>
                <h3 class="text-2xl font-serif font-bold text-foundation-grey">Add Procurement Item</h3>
            </div>
            
            <form id="addItemForm" method="post" class="grid grid-cols-1 md:grid-cols-12 gap-x-6 gap-y-8 items-end">
                <?php echo csrf_token_field(); ?>
                <input type="hidden" name="action" value="add_item">
                
                <div class="md:col-span-3">
                    <label class="block text-[9px] uppercase tracking-[0.2em] font-black text-gray-400 mb-2">SKU / Code</label>
                    <input id="sku" name="sku" placeholder="PRD-001" class="w-full bg-white border-b-2 border-gray-100 px-0 py-2.5 text-sm focus:border-rajkot-rust outline-none transition-colors font-mono uppercase">
                </div>
                <div class="md:col-span-5">
                    <label class="block text-[9px] uppercase tracking-[0.2em] font-black text-gray-400 mb-2">Item Name / Material</label>
                    <input id="name" name="name" placeholder="Italian Marble (Botticino)" required class="w-full bg-white border-b-2 border-gray-100 px-0 py-2.5 text-sm font-bold focus:border-rajkot-rust outline-none transition-colors">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-[9px] uppercase tracking-[0.2em] font-black text-gray-400 mb-2 font-sans">Unit</label>
                    <select id="unit" name="unit" class="w-full bg-white border-b-2 border-gray-100 px-0 py-2.5 text-sm focus:border-rajkot-rust outline-none transition-colors cursor-pointer">
                        <option value="pcs">Pieces (pcs)</option>
                        <option value="sqft">Sq. Ft.</option>
                        <option value="rm">Running Ft (rm)</option>
                        <option value="kg">Kilograms (kg)</option>
                        <option value="cum">Cu. M.</option>
                        <option value="bags">Bags</option>
                        <option value="set">Set</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-[9px] uppercase tracking-[0.2em] font-black text-gray-400 mb-2 text-right font-sans">Quantity</label>
                    <input id="quantity" name="quantity" type="number" min="1" value="1" class="w-full bg-white border-b-2 border-gray-100 px-0 py-2.5 text-sm focus:border-rajkot-rust outline-none transition-colors text-right font-bold font-mono">
                </div>
                
                <div class="md:col-span-4">
                    <label class="block text-[9px] uppercase tracking-[0.2em] font-black text-gray-400 mb-2 font-sans">Description (Optional)</label>
                    <input name="description" placeholder="Standard size, polished finish" class="w-full bg-white border-b-2 border-gray-100 px-0 py-2.5 text-sm focus:border-rajkot-rust outline-none transition-colors font-sans">
                </div>

                <div class="md:col-span-4">
                    <label class="block text-[9px] uppercase tracking-[0.2em] font-black text-gray-400 mb-2 text-right font-sans">Estimated Unit Price (₹)</label>
                    <div class="relative">
                        <span class="absolute left-0 top-1/2 -translate-y-1/2 text-gray-300 text-xs font-mono">₹</span>
                        <input id="unit_price" name="unit_price" type="number" step="0.01" min="0" value="0" class="w-full bg-white border-b-2 border-gray-100 pl-4 py-2.5 text-sm focus:border-rajkot-rust outline-none transition-colors text-right font-bold font-mono">
                    </div>
                </div>

                <div class="md:col-span-4">
                    <div id="lineTotal" class="text-[10px] text-gray-400 text-right mb-4 font-mono font-bold">Line Total: ₹ 0.00</div>
                    <button type="submit" class="w-full bg-foundation-grey hover:bg-rajkot-rust text-white py-3 shadow-lg font-bold transition-all uppercase tracking-[0.2em] text-[10px] flex items-center justify-center gap-2">
                        <i data-lucide="plus" class="w-3 h-3"></i> Add to Invoice
                    </button>
                </div>
                
                <div id="addError" class="md:col-span-12 text-[10px] font-bold uppercase tracking-widest text-red-600 mt-6 hidden p-4 bg-red-50 border-l-4 border-red-600 font-sans italic"></div>
            </form>
        </section>
        <?php endif; ?>
    </main>
</div>

<script>
(function(){
  // Ensure lucide icons are created
  if (window.lucide) window.lucide.createIcons();

  function showToast(message){
    const toast = document.createElement('div');
    toast.textContent = message;
    toast.style.cssText = 'position:fixed;right:24px;bottom:24px;background:#1a1c1c;color:#fff;padding:14px 20px;z-index:9999;font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:0.2em;box-shadow:0 15px 40px rgba(0,0,0,0.4);border-left:4px solid #94180C';
    document.body.appendChild(toast);
    setTimeout(() => { 
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
        setTimeout(() => toast.remove(), 600);
    }, 2800);
  }

  const shareUrl = '<?php echo addslashes($share_url); ?>';
  document.getElementById('copyLink')?.addEventListener('click', function(){
    if (navigator.clipboard) {
        navigator.clipboard.writeText(shareUrl).then(() => showToast('Link Copied to Clipboard')).catch(() => {
            const el = document.createElement('textarea');
            el.value = shareUrl;
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            document.body.removeChild(el);
            showToast('Link Copied');
        });
    } else {
        const el = document.createElement('textarea');
        el.value = shareUrl;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        showToast('Link Copied');
    }
  });

    document.getElementById('emailLink')?.addEventListener('click', function(){
        // Open modal for recipient email
        const modal = document.getElementById('emailModal');
        const input = document.getElementById('modal_to_email');
        const defaultRecipient = '<?php echo addslashes($project['owner_email'] ?? $project['owner_contact'] ?? ''); ?>';
        if (input) input.value = defaultRecipient || '';
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => { try { input && input.focus(); } catch(e){} }, 50);
        }
    });

    document.getElementById('emailModalCancel')?.addEventListener('click', function(){
        const modal = document.getElementById('emailModal');
        if (modal) { modal.classList.add('hidden'); modal.classList.remove('flex'); }
    });

    document.getElementById('emailModalForm')?.addEventListener('submit', function(ev){
        const emailVal = document.getElementById('modal_to_email')?.value || '';
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(emailVal)) {
            ev.preventDefault();
            showToast('Please enter a valid email address');
            return false;
        }
        showToast('Sending invoice...');
    });

    document.getElementById('requestEditBtn')?.addEventListener('click', function(){
        const modal = document.getElementById('requestEditModal');
        const input = document.getElementById('request_details');
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => { try { input && input.focus(); } catch(e){} }, 50);
        }
    });

    document.getElementById('requestEditCancel')?.addEventListener('click', function(){
        const modal = document.getElementById('requestEditModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    });

    document.getElementById('requestEditForm')?.addEventListener('submit', function(ev){
        const details = (document.getElementById('request_details')?.value || '').trim();
        if (!details) {
            ev.preventDefault();
            showToast('Please add requested changes');
            return false;
        }
        showToast('Submitting edit request...');
    });

    document.getElementById('payNowBtn')?.addEventListener('click', function(){
        const panel = document.getElementById('upiPanel');
        if (!panel) return;
        panel.classList.toggle('hidden');
        if (!panel.classList.contains('hidden')) {
            panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            showToast('UPI QR ready to scan');
        }
    });

  // Dynamic Price Calculation
  const qtyEl = document.getElementById('quantity');
  const priceEl = document.getElementById('unit_price');
  const lineTotalEl = document.getElementById('lineTotal');
  const addForm = document.getElementById('addItemForm');
  const addError = document.getElementById('addError');

  function computeLineTotal(){
    const q = Math.max(0, parseFloat(qtyEl?.value) || 0);
    const p = Math.max(0, parseFloat(priceEl?.value) || 0);
    const t = q * p;
    if (lineTotalEl) lineTotalEl.textContent = 'Line Total: ₹ ' + t.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    return t;
  }

  [qtyEl, priceEl].forEach(el => el?.addEventListener('input', computeLineTotal));
  computeLineTotal();

  // Validation
  addForm?.addEventListener('submit', function(ev){
    const nameEl = document.getElementById('name');
    const errors = [];
    if (!nameEl?.value.trim()) errors.push('Please enter a valid item name');
    if ((parseFloat(qtyEl?.value) || 0) < 1) errors.push('Quantity must be at least 1');
    
    if (errors.length){
      ev.preventDefault();
      if (addError) {
          addError.innerHTML = '<div class="flex items-center gap-2"><i data-lucide="alert-triangle" class="w-4 h-4"></i> ' + errors.join(' | ') + '</div>';
          addError.classList.remove('hidden');
          if (window.lucide) window.lucide.createIcons();
      }
      return false;
    }
  });
})();
</script>

<?php require_once __DIR__ . '/../Common/footer.php'; ?>
