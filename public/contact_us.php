<?php
require_once __DIR__ . '/../app/Core/Bootstrap/init.php';

$contactContent = function_exists('public_content_page_values') ? public_content_page_values('contact_us') : [];
$ct = static function ($key, $default = '') use ($contactContent) {
    return (string)($contactContent[$key] ?? $default);
};

$contactPageUrl = BASE_PATH . PUBLIC_PATH_PREFIX . '/contact_us.php';

if (isset($_POST['submit'])) {
    if (!csrf_validate((string)($_POST['csrf_token'] ?? ''))) {
        $_SESSION['contact_form_error'] = $ct('csrf_invalid', 'Invalid request token. Please refresh and try again.');
        header('Location: ' . $contactPageUrl);
        exit();
    }
    

    $first_name = trim((string)($_POST['first_name'] ?? ''));
    $last_name = trim((string)($_POST['last_name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $subject = trim((string)($_POST['subject'] ?? ''));
    $message = trim((string)($_POST['message'] ?? ''));

    $_SESSION['contact_form_old'] = [
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
        'subject' => $subject,
        'message' => $message,
    ];

    try {
        $db = get_db();
        if (!($db instanceof PDO)) {
            throw new RuntimeException($ct('db_unavailable', 'Database connection unavailable.'));
        }

        $stmt = $db->prepare(
            'INSERT INTO contact_messages (first_name, last_name, email, subject, message) VALUES (:first_name, :last_name, :email, :subject, :message)'
        );

        $stmt->execute([
            ':first_name' => $first_name,
            ':last_name' => $last_name,
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
    exit();
}

$form_success = !empty($_SESSION['contact_form_success']);
$form_error = (string)($_SESSION['contact_form_error'] ?? '');
$old_input = $_SESSION['contact_form_old'] ?? [];

unset($_SESSION['contact_form_success'], $_SESSION['contact_form_error']);
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo esc($ct('page_title', 'Contact Us | Ripal Design')); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="./js/validation.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/contact_us.css">
</head>

<body class="bg-[#050505] text-white overflow-x-hidden">
    <?php 
    $HEADER_MODE = 'public';
    require_once __DIR__ . '/../app/Ui/header.php'; 
    ?>
        <?php if ($form_error): ?>
            <div class="fixed top-24 right-6 z-[100] bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
                <?php echo htmlspecialchars($form_error); ?>
            </div>
        <?php endif; ?>


    <main class="relative min-h-screen flex flex-col md:flex-row pt-24">
        <?php if ($form_success): ?>
            <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm p-6">
                <div class="bg-white p-12 max-w-lg w-full text-center border-b-4 border-rajkot-rust shadow-premium">
                    <i data-lucide="check-circle" class="w-16 h-16 text-approval-green mx-auto mb-6"></i>
                    <h2 class="text-3xl font-serif font-bold text-foundation-grey mb-4"><?php echo esc($ct('success_title', 'Message Sent')); ?></h2>
                    <p class="text-gray-500 mb-8"><?php echo esc($ct('success_message', 'Thank you for reaching out. Our design team will review your inquiry and contact you shortly.')); ?></p>
                    <button onclick="window.location.href='index.php'" class="bg-foundation-grey hover:bg-rajkot-rust text-white px-8 py-3 text-[10px] font-bold uppercase tracking-widest transition-all">
                        <?php echo esc($ct('success_button', 'Return Home')); ?>
                    </button>
                </div>
            </div>
        <?php endif; ?>
        <!-- Left: Info -->
        <div class="w-full md:w-1/2 p-8 md:p-20 flex flex-col justify-content-center text-align-center bg-[#0a0a0a] left-responsive-img" style="background-image: url('../assets/Content/WhatsApp%20Image%202026-02-02%20at%205.02.50%20PM.jpeg');  background-size: cover; background-position: center; background-repeat: no-repeat;">

            <!-- <img class="left-responsive-img" src="../assets/Content/WhatsApp%20Image%202026-02-02%20at%205.02.50%20PM.jpeg" alt="Get in touch" /> -->
            <div class="main">

                <div class="max-w-md mx-auto md:mx-0">
                    <span class="text-[#731209] tracking-[0.2em] text-sm uppercase font-semibold"><?php echo esc($ct('left_kicker', 'Get in touch')); ?></span>
                    <h1 class="text-5xl md:text-7xl serif mt-6 mb-8 leading-tight"><?php echo esc($ct('left_heading_line_1', "Let's Discuss")); ?><br><?php echo esc($ct('left_heading_line_2', 'Your Vision.')); ?></h1>

                    <div class="space-y-8 mt-12 text-gray-400 font-light">
                        <div>
                            <h4 class="text-white text-lg font-medium mb-1"><?php echo esc($ct('address_heading', 'Ripal Design Rajkot')); ?> <br>
                                <p>
                                    <?php echo public_content_get_html('contact_us', 'address_html', '538 Jasal Complex,<br>Nanavati Chowk,<br>150ft Ring Road,<br>Rajkot, Gujarat, India'); ?> <br>
                                </p>
                        </div>
                        <div>
                            <h4 class="text-white text-lg font-medium mb-1"><?php echo esc($ct('contact_heading', 'Contact')); ?></h4>
                            <p><?php echo esc($ct('contact_phone', '+91 94267 89012')); ?><br><?php echo esc($ct('contact_email', 'projects@ripaldesign.in')); ?></p>
                        </div>
                        <div>
                            <h4 class="text-white text-lg font-medium mb-1"><?php echo esc($ct('social_heading', 'Social')); ?></h4>
                      
                            <!-- Neumorphic icon grid -->
                            <div class="icon-container">
                                <a class="icon" href="https://www.instagram.com/ripal_design12/" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr($ct('social_instagram_label', 'Instagram')); ?>">
                                    <span class="sr-only"><?php echo esc($ct('social_instagram_label', 'Instagram')); ?></span>
                                    <i class="fab fa-instagram"></i>
                                </a>

                                <a class="icon" href="https://www.linkedin.com" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr($ct('social_linkedin_label', 'LinkedIn')); ?>">
                                    <span class="sr-only"><?php echo esc($ct('social_linkedin_label', 'LinkedIn')); ?></span>
                                    <i class="fab fa-linkedin-in"></i>
                                </a>

                                <a class="icon" href="https://www.behance.net/mayankvinchhi" target="_blank" rel="noopener noreferrer" aria-label="<?php echo esc_attr($ct('social_behance_label', 'Behance')); ?>">
                                    <span class="sr-only"><?php echo esc($ct('social_behance_label', 'Behance')); ?></span>
                                    <i class="fab fa-behance"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Form -->
        <div class="w-full md:w-1/2 p-8 md:p-20 bg-[#050505] flex flex-col justify-center">
            <h1 class="text-3xl md:text-4xl font-bold text-white text-align-start mb-8"><?php echo esc($ct('form_heading', 'Send us a message')); ?></h1>
            <form class="max-w-lg w-full mx-0 space-y-8" action="<?php echo htmlspecialchars($contactPageUrl, ENT_QUOTES, 'UTF-8'); ?>" method="POST" id="contactForm" >
               <?php echo csrf_token_field(); ?>
               
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="group">
                        <label class="block text-xs uppercase tracking-widest text-gray-500 mb-2"><?php echo esc($ct('label_first_name', 'First Name')); ?></label>
                        <input type="text" name="first_name" value="<?php echo htmlspecialchars((string)($old_input['first_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="w-full py-3 text-lg border-b border-white/20 bg-transparent focus:border-[#731209] outline-none transition-colors" data-validation="required min alphabetic" data-min="2">
                        <span id="first_name_error" class="text-danger"></span>
                    </div>
                    <div class="group">
                        <label class="block text-xs uppercase tracking-widest text-gray-500 mb-2"><?php echo esc($ct('label_last_name', 'Last Name')); ?></label>
                        <input type="text" name="last_name" value="<?php echo htmlspecialchars((string)($old_input['last_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="w-full py-3 text-lg border-b border-white/20 bg-transparent focus:border-[#731209] outline-none transition-colors" data-validation="required min alphabetic" data-min="2">
                        <span id="last_name_error" class="text-danger"></span>
                    </div>
                </div>

                <div class="group">
                    <label class="block text-xs uppercase tracking-widest text-gray-500 mb-2"><?php echo esc($ct('label_email', 'Email Address')); ?></label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars((string)($old_input['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" class="w-full py-3 text-lg border-b border-white/20 bg-transparent focus:border-[#731209] outline-none transition-colors" data-validation="required email">
                    <span id="email_error" class="text-danger"></span>
                </div>

                <div class="group">
                    <label class="block text-xs uppercase tracking-widest text-gray-500 mb-2"><?php echo esc($ct('label_subject', 'Subject')); ?></label>
                    <select name="subject" class="w-full py-3 text-lg border-b border-white/20 bg-transparent focus:border-[#731209] outline-none transition-colors text-gray-300" data-validation="required select">
                        <option value="" class="bg-black" <?php echo (($old_input['subject'] ?? '') === '') ? 'selected' : ''; ?>><?php echo esc($ct('subject_default', 'Select Inquiry Type')); ?></option>
                        <option value="residential" class="bg-black" <?php echo (($old_input['subject'] ?? '') === 'residential') ? 'selected' : ''; ?>><?php echo esc($ct('subject_residential', 'Residential Project')); ?></option>
                        <option value="commercial" class="bg-black" <?php echo (($old_input['subject'] ?? '') === 'commercial') ? 'selected' : ''; ?>><?php echo esc($ct('subject_commercial', 'Commercial Project')); ?></option>
                        <option value="consultation" class="bg-black" <?php echo (($old_input['subject'] ?? '') === 'consultation') ? 'selected' : ''; ?>><?php echo esc($ct('subject_consultation', 'Design Consultation')); ?></option>
                        <option value="other" class="bg-black" <?php echo (($old_input['subject'] ?? '') === 'other') ? 'selected' : ''; ?>><?php echo esc($ct('subject_other', 'Other')); ?></option>
                    </select>
                    <span id="subject_error" class="text-danger"></span>
                </div>

                <div class="group">
                    <label class="block text-xs uppercase tracking-widest text-gray-500 mb-2"><?php echo esc($ct('label_message', 'Message')); ?></label>
                    <textarea name="message" rows="4" class="w-full py-3 text-lg border-b border-white/20 bg-transparent focus:border-[#731209] outline-none transition-colors resize-none" data-validation="required min" data-min="10"><?php echo htmlspecialchars((string)($old_input['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                    <span id="message_error" class="text-danger"></span>
                </div>

                <div style="text-align:left; margin-top:1.5rem;">
                    <button type="submit" name="submit" class="px-10 py-4 bg-[#731209] hover:bg-[#94180C] text-white uppercase tracking-widest text-sm transition-all duration-300" style="display:inline-block;">
                        <?php echo esc($ct('submit_button', 'Send Message')); ?>
                    </button>
                </div>
            </form>
        </div>
    </main>

    <?php require_once __DIR__ . '/../Common/footer.php'; ?>
</body>

</html>