<?php
if (!defined('PROJECT_ROOT')) { require_once dirname(__DIR__, 4) . '/app/Core/Bootstrap/init.php'; }
/**
 * Provision Temporary User Credentials
 *
 * Static/approval page to generate temporary credentials for review.
 * - Assign role (worker/employee/admin/etc)
 * - If role is worker, choose specific trade (carpenter, electrician...)
 * - If role is employee, choose specific position (manager, supervisor...)
 * This page currently simulates creation and generates a temporary password.
 */

require_once PROJECT_ROOT . '/app/Core/Bootstrap/init.php';
require_login();
require_role('admin');

$error = '';
$success = '';
$mailStatus = '';
$generated = [];

if (!function_exists('generate_policy_compliant_temp_password')) {
    function generate_policy_compliant_temp_password(int $length = 12): string
    {
        $minLength = 8;
        $length = max($minLength, $length);

        $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower = 'abcdefghijkmnopqrstuvwxyz';
        $numbers = '23456789';
        $special = '!@#$%^&*()-_=+[]{}?';
        $all = $upper . $lower . $numbers . $special;

        $pick = static function (string $chars): string {
            $idx = random_int(0, strlen($chars) - 1);
            return $chars[$idx];
        };

        // Guarantee policy coverage: 1 uppercase, 1 lowercase, 1 number, 1 special.
        $chars = [
            $pick($upper),
            $pick($lower),
            $pick($numbers),
            $pick($special),
        ];

        while (count($chars) < $length) {
            $chars[] = $pick($all);
        }

        // Fisher-Yates shuffle with cryptographic randomness.
        for ($i = count($chars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            $tmp = $chars[$i];
            $chars[$i] = $chars[$j];
            $chars[$j] = $tmp;
        }

        return implode('', $chars);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!function_exists('require_csrf')) {
        $error = 'Security validation unavailable.';
    } else {
        require_csrf();
    }

    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? '';
    $subrole = $_POST['subrole'] ?? '';
    // If user selected 'other' (case-insensitive), prefer the free-text value when provided
    if (strtolower($subrole) === 'other' && !empty($_POST['subrole_other'])) {
        $subrole = trim($_POST['subrole_other']);
    }

    if (empty($firstName) || empty($lastName) || empty($email) || empty($role)) {
        $error = 'All required fields must be filled.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Generate policy-compliant temporary credential password.
        try {
            $tempPassword = generate_policy_compliant_temp_password(12);
        } catch (Exception $e) {
            // Fallback that still complies with policy requirements.
            $tempPassword = 'Temp@' . rand(1000, 9999) . 'aA';
        }

        $generated = [
            'username' => $email,
            'password' => $tempPassword,
            'role' => $role,
            'subrole' => $subrole,
            'name' => $firstName . ' ' . $lastName,
        ];

        $success = 'Temporary credentials generated for approval.';

        // Send temporary credentials email using shared mail setup.
        $renderTemplate = static function ($template, array $vars = []) {
            return strtr((string)$template, $vars);
        };
        $templatePath = PROJECT_ROOT . '/public/email_preview/temp_credentials_preview.html';
        $defaultTemplate = '<h3>Your Temporary Login Credentials</h3><p>Hello {{full_name}},</p><p>Username: {{username}}</p><p>Temporary Password: {{temp_password}}</p><p>Role: {{role}}</p><p>Designation: {{subrole}}</p><p><a href="{{login_url}}">Open Login Page</a></p><p>Please change your password after first login.</p>';
        $htmlTemplate = is_readable($templatePath) ? (string)file_get_contents($templatePath) : $defaultTemplate;
        $loginUrl = rtrim(BASE_URL, '/') . PUBLIC_PATH_PREFIX . '/login.php';
        $mailHtml = $renderTemplate($htmlTemplate, [
            '{{full_name}}' => htmlspecialchars($generated['name'], ENT_QUOTES, 'UTF-8'),
            '{{username}}' => htmlspecialchars($generated['username'], ENT_QUOTES, 'UTF-8'),
            '{{temp_password}}' => htmlspecialchars($generated['password'], ENT_QUOTES, 'UTF-8'),
            '{{role}}' => htmlspecialchars((string)$generated['role'], ENT_QUOTES, 'UTF-8'),
            '{{subrole}}' => htmlspecialchars((string)($generated['subrole'] !== '' ? $generated['subrole'] : '-'), ENT_QUOTES, 'UTF-8'),
            '{{login_url}}' => htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8'),
        ]);
        $mailText = $renderTemplate(
            "Hello {{full_name}},\n\nYour temporary credentials are below:\nUsername: {{username}}\nPassword: {{temp_password}}\nRole: {{role}}\nDesignation: {{subrole}}\n\nLogin: {{login_url}}\n\nPlease change your password after first login.",
            [
                '{{full_name}}' => (string)$generated['name'],
                '{{username}}' => (string)$generated['username'],
                '{{temp_password}}' => (string)$generated['password'],
                '{{role}}' => (string)$generated['role'],
                '{{subrole}}' => (string)($generated['subrole'] !== '' ? $generated['subrole'] : '-'),
                '{{login_url}}' => $loginUrl,
            ]
        );

        try {
            $mailBootstrap = PROJECT_ROOT . '/public/mailer.php';
            if (!is_readable($mailBootstrap)) {
                throw new RuntimeException('Shared mail bootstrap is unavailable.');
            }
            $mail = require $mailBootstrap;
            if (!($mail instanceof \PHPMailer\PHPMailer\PHPMailer)) {
                throw new RuntimeException('Mailer instance could not be initialized.');
            }

            $from = getenv('MAIL_FROM') ?: 'no-reply@ripaldesign.in';
            $fromName = getenv('MAIL_FROM_NAME') ?: 'Ripal Design';
            $mail->clearAddresses();
            $mail->setFrom($from, $fromName);
            $mail->addAddress($email, $generated['name']);
            $mail->isHTML(true);
            $mail->Subject = 'Temporary Login Credentials - Ripal Design';
            $mail->Body = $mailHtml;
            $mail->AltBody = $mailText;
            $mail->send();
            $mailStatus = 'Temporary credentials email sent to ' . $email . '.';
        } catch (Throwable $e) {
            $mailStatus = 'Temporary credentials generated, but email could not be sent.';
            if (function_exists('app_log')) {
                app_log('warning', 'Temp credentials email failed', [
                    'email' => $email,
                    'exception' => $e->getMessage(),
                ]);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="bg-canvas-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Provision Temporary Identity | Ripal Design</title>
    <?php require_once PROJECT_ROOT . '/Common/header.php'; ?>
    <!-- jQuery and Validation Plugin -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
    <style> 
        .hidden { display:none; } 
        .error { color: #94180C; font-size: 10px; font-weight: bold; text-transform: uppercase; margin-top: 4px; display: block; }
        input.error, select.error, textarea.error { border-color: #94180C !important; background-color: #FFF5F5 !important; }
    </style>
</head>
<body class="bg-canvas-white font-sans text-foundation-grey min-h-screen">
    <div class="min-h-screen flex flex-col">
        <header class="bg-foundation-grey text-white pt-20 md:pt-24 pb-8 md:pb-12 px-4 sm:px-6 lg:px-8 shadow-lg mb-8 md:mb-12 border-b-2 border-rajkot-rust">
            <div class="max-w-3xl mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-3xl md:text-4xl font-serif font-bold">Provision Identity</h1>
                    <p class="text-gray-400 mt-1 text-xs md:text-sm uppercase tracking-widest font-bold opacity-70">Temporary Credentials Portal</p>
                </div>
                <div>
                    <a href="user_management.php" class="text-gray-400 hover:text-rajkot-rust transition-colors flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.2em] no-underline">
                        <i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Registry
                    </a>
                </div>
            </div>
        </header>

        <main class="flex-grow max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 pb-12 admin-main w-full">
            <div class="bg-white shadow-premium border border-gray-100 p-6 md:p-12 relative overflow-hidden">
                <!-- CAD-style background accent -->
                <div class="absolute top-0 right-0 w-32 h-32 bg-rajkot-rust/5 -mr-16 -mt-16 rotate-45 pointer-events-none"></div>

                <?php if ($error): ?>
                    <div class="bg-red-50 border-l-4 border-rajkot-rust text-foundation-grey p-4 md:p-5 mb-6 md:mb-8 text-[12px] font-bold flex items-center gap-4" role="alert">
                        <i data-lucide="alert-circle" class="w-5 h-5 text-rajkot-rust shrink-0"></i>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="bg-approval-green/10 border-l-4 border-approval-green text-foundation-grey p-4 md:p-5 mb-6 md:mb-8 text-[12px] font-bold flex items-center gap-4" role="alert">
                        <i data-lucide="check-circle" class="w-5 h-5 text-approval-green shrink-0"></i>
                        <span><?php echo htmlspecialchars($success); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($mailStatus): ?>
                    <div class="bg-blue-50 border-l-4 border-blue-500 text-foundation-grey p-4 md:p-5 mb-6 md:mb-8 text-[12px] font-bold flex items-center gap-4" role="status">
                        <i data-lucide="mail" class="w-5 h-5 text-blue-500 shrink-0"></i>
                        <span><?php echo htmlspecialchars($mailStatus); ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" id="tempProvisionForm" class="space-y-6 md:space-y-8">
                    <?php echo csrf_token_field(); ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8">
                        <div class="space-y-2">
                            <label for="firstName" class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">First Name</label>
                            <input id="firstName" name="firstName" type="text" required
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-100 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm font-medium" 
                                placeholder="e.g. Ramesh">
                        </div>
                        <div class="space-y-2">
                            <label for="lastName" class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Last Name</label>
                            <input id="lastName" name="lastName" type="text" required
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-100 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm font-medium" 
                                placeholder="e.g. Kumar">
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label for="email" class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Email Address</label>
                        <input id="email" name="email" type="email" required
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-100 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm font-medium" 
                            placeholder="user@ripaldesign.in">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8">
                        <div class="space-y-2">
                            <label for="roleSelect" class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">System Role</label>
                            <select id="roleSelect" name="role" required
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-100 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm font-bold uppercase tracking-widest cursor-pointer">
                                <option value="">-- Select role --</option>
                                <option value="client">Client</option>
                                <option value="worker">Worker</option>
                                <option value="employee">Employee</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>
                        <div id="subroleContainer" class="hidden space-y-2">
                            <label for="subroleSelect" class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Specific Designation</label>
                            <select id="subroleSelect" name="subrole" 
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-100 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm font-bold uppercase tracking-widest cursor-pointer">
                                <option value="">-- Select specific --</option>
                            </select>
                            <div id="subroleOtherContainer" class="hidden mt-4 pt-2 border-t border-gray-50">
                                <label for="subroleOther" class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2 block">If Other, specify</label>
                                <input type="text" name="subrole_other" id="subroleOther"
                                    class="w-full px-4 py-3 bg-gray-50 border border-gray-100 outline-none focus:bg-white focus:border-rajkot-rust transition-all text-sm font-medium" 
                                    placeholder="Please specify">
                            </div>
                        </div>
                    </div>

                    <div class="pt-6 flex flex-col sm:flex-row gap-4">
                        <button type="submit" 
                            class="flex-grow bg-foundation-grey hover:bg-rajkot-rust text-white py-5 md:py-4 px-8 text-[10px] font-bold uppercase tracking-[0.3em] shadow-premium transition-all">
                            Generate Temporary Credentials
                        </button>
                        <a href="user_management.php" class="flex-grow sm:flex-grow-0 text-center bg-gray-50 hover:bg-gray-100 text-foundation-grey font-bold py-5 md:py-4 px-8 rounded transition-all text-[10px] uppercase tracking-widest no-underline border border-gray-100 sm:border-0 flex items-center justify-center">
                            Cancel
                        </a>
                    </div>
                </form>

                <?php if ($generated): ?>
                    <div class="mt-8 md:mt-12 bg-gray-50 p-6 md:p-8 border border-gray-100 rounded shadow-inner relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-4 opacity-10">
                            <i data-lucide="key" class="w-12 h-12"></i>
                        </div>
                        <h3 class="text-xs font-bold uppercase tracking-widest text-rajkot-rust mb-6 border-b border-gray-200 pb-2">Generation Result</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-tight">Full Name</label>
                                <p class="text-sm font-bold mt-1"><?php echo htmlspecialchars($generated['name']); ?></p>
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-tight">System Role</label>
                                <p class="text-sm font-bold mt-1"><?php echo htmlspecialchars($generated['role']); ?> <?php if (!empty($generated['subrole'])): ?><span class="text-gray-400 font-medium">(<?php echo htmlspecialchars($generated['subrole']); ?>)</span><?php endif; ?></p>
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-tight">Username (Email)</label>
                                <p class="text-sm font-bold mt-1 break-all uppercase tracking-tight"><?php echo htmlspecialchars($generated['username']); ?></p>
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-tight">Temporary Password</label>
                                <div class="flex items-center gap-3 mt-1">
                                    <span id="tempPass" class="font-mono bg-white px-3 py-2 border border-gray-200 rounded text-sm font-bold text-rajkot-rust shadow-sm inline-block min-w-[120px] text-center"><?php echo htmlspecialchars($generated['password']); ?></span>
                                    <button onclick="copyPassword()" class="h-10 w-10 md:h-8 md:w-8 bg-white md:bg-transparent border border-gray-100 md:border-0 hover:bg-gray-200 rounded transition flex items-center justify-center shadow-sm md:shadow-none" title="Copy Password">
                                        <i data-lucide="copy" class="w-5 h-5 md:w-4 md:h-4 text-gray-500"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <p class="text-[10px] text-gray-400 mt-8 italic border-t border-gray-200 pt-4 flex items-center gap-2">
                            <i data-lucide="info" class="w-3 h-3"></i> Note: These are temporary credentials for approval only. 
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </main>

        <?php require_once PROJECT_ROOT . '/Common/footer.php'; ?>
    </div>

    <script>
        $(document).ready(function() {
            // jQuery Validation
            $("#tempProvisionForm").validate({
                rules: {
                    firstName: "required",
                    lastName: "required",
                    email: {
                        required: true,
                        email: true
                    },
                    role: "required",
                    subrole_other: {
                        required: function() {
                            return $("#subroleSelect").val() === "Other";
                        }
                    }
                },
                messages: {
                    firstName: "First name is mandatory",
                    lastName: "Last name is mandatory",
                    email: "Valid email address required",
                    role: "Please select a system role",
                    subrole_other: "Please specify the designation"
                }
            });

            const roleSelect = $('#roleSelect');
            const subroleContainer = $('#subroleContainer');
            const subroleSelect = $('#subroleSelect');

            const options = {
                worker: ['Master Carpenter','Field Site Engineer','Electrical Specialist','Plumbing Specialist','Masonry Craftsman','Other'],
                employee: ['Principal Structural Engineer','Design Principal','Valuation Specialist','Strategic Planner','Other']
            };

            roleSelect.on('change', function() {
                const v = $(this).val();
                if (v === 'worker' || v === 'employee') {
                    subroleSelect.html('<option value="">-- Select specific --</option>');
                    options[v].forEach(function(o){
                        subroleSelect.append($('<option>').val(o).text(o));
                    });
                    subroleContainer.removeClass('hidden');
                } else {
                    subroleSelect.html('');
                    subroleContainer.addClass('hidden');
                }
                // Trigger validation if already shown
                if($("#tempProvisionForm").validate().element("#roleSelect")) {}
            });

            subroleSelect.on('change', function() {
                const v = $(this).val();
                if (v === 'Other') {
                    $('#subroleOtherContainer').removeClass('hidden');
                } else {
                    $('#subroleOtherContainer').addClass('hidden');
                }
            });
        });

        function copyPassword(){
            const el = document.getElementById('tempPass');
            if(!el) return;
            const text = el.innerText;
            navigator.clipboard.writeText(text).then(() => {
                const notification = document.createElement('div');
                notification.className = 'fixed bottom-8 right-8 bg-foundation-grey text-white px-6 py-3 shadow-2xl z-50 rounded-lg border-l-4 border-approval-green';
                notification.innerHTML = '<p class="text-[10px] font-bold uppercase tracking-widest opacity-70 mb-1">Credential Copied</p><p class="text-sm">Temporary password copied to clipboard.</p>';
                document.body.appendChild(notification);
                setTimeout(() => {
                    notification.classList.add('opacity-0', 'transition-opacity', 'duration-500');
                    setTimeout(() => notification.remove(), 500);
                }, 2200);
            });
        }
    </script>
</body>
</html>
