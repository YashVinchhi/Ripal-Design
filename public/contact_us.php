<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<html lang="en" class="scroll-smooth">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Contact Us | Ripal Design</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-4.0.0.js" integrity="sha256-9fsHeVnKBvqh3FB2HYu7g2xseAZ5MlN6Kz/qnkASV8U=" crossorigin="anonymous"></script>
    <script src="./js/validation.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/contact_us.css">
</head>

<body class="bg-[#050505] text-white overflow-x-hidden">
    <?php 
    $HEADER_MODE = 'public';
    require_once __DIR__ . '/../includes/header.php'; 
    
    // Simple POST handling for feedback
    $form_success = false;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
        $form_success = true;
    }
    ?>

    <main class="relative min-h-screen flex flex-col md:flex-row pt-24">
        <?php if ($form_success): ?>
            <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 backdrop-blur-sm p-6">
                <div class="bg-white p-12 max-w-lg w-full text-center border-b-4 border-rajkot-rust shadow-premium">
                    <i data-lucide="check-circle" class="w-16 h-16 text-approval-green mx-auto mb-6"></i>
                    <h2 class="text-3xl font-serif font-bold text-foundation-grey mb-4">Message Sent</h2>
                    <p class="text-gray-500 mb-8">Thank you for reaching out. Our design team will review your inquiry and contact you shortly.</p>
                    <button onclick="window.location.href='index.php'" class="bg-foundation-grey hover:bg-rajkot-rust text-white px-8 py-3 text-[10px] font-bold uppercase tracking-widest transition-all">
                        Return Home
                    </button>
                </div>
            </div>
        <?php endif; ?>
        <!-- Left: Info -->
        <div class="w-full md:w-1/2 p-8 md:p-20 flex flex-col justify-content-center text-align-center bg-[#0a0a0a] left-responsive-img" style="background-image: url('../assets/Content/WhatsApp%20Image%202026-02-02%20at%205.02.50%20PM.jpeg');  background-size: cover; background-position: center; background-repeat: no-repeat;">

            <!-- <img class="left-responsive-img" src="../assets/Content/WhatsApp%20Image%202026-02-02%20at%205.02.50%20PM.jpeg" alt="Get in touch" /> -->
            <div class="main">

                <div class="max-w-md mx-auto md:mx-0">
                    <span class="text-[#731209] tracking-[0.2em] text-sm uppercase font-semibold">Get in touch</span>
                    <h1 class="text-5xl md:text-7xl serif mt-6 mb-8 leading-tight">Let's Discuss<br>Your Vision.</h1>

                    <div class="space-y-8 mt-12 text-gray-400 font-light">
                        <div>
                            <h4 class="text-white text-lg font-medium mb-1">Ripal Design Rajkot <br>
                                <p>
                                    538 Jasal Complex,<br>
                                    Nanavati Chowk,<br>
                                    150ft Ring Road,<br>
                                    Rajkot, Gujarat, India <br>
                                </p>
                        </div>
                        <div>
                            <h4 class="text-white text-lg font-medium mb-1">Contact</h4>
                            <p>+91 94267 89012<br>projects@ripaldesign.in</p>
                        </div>
                        <div>
                            <h4 class="text-white text-lg font-medium mb-1">Social</h4>
                            <!-- Old SVG social icons commented out. Neumorphic icon grid below is used instead. -->
                            <!--
                            <div class="social-icons">
                                <a class="icon-button" href="https://www.instagram.com/ripal_design12/" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                                    <span class="sr-only">Instagram</span>
                                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <rect x="3" y="3" width="18" height="18" rx="5" />
                                        <circle cx="12" cy="12" r="3.6" />
                                        <circle cx="17.6" cy="6.4" r="0.9" />
                                    </svg>
                                </a>

                                <a class="icon-button" href="https://www.linkedin.com" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn">
                                    <span class="sr-only">LinkedIn</span>
                                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <rect x="2.5" y="2.5" width="19" height="19" rx="3" />
                                        <circle cx="6" cy="6.2" r="1.2" />
                                        <path d="M5 9.2v7" />
                                        <path d="M10 13.5v3.7" />
                                        <path d="M10 9.2c1.8 0 3 1 3 3.5v4" />
                                    </svg>
                                </a>

                                <a class="icon-button" href="https://www.behance.net/mayankvinchhi" target="_blank" rel="noopener noreferrer" aria-label="Behance">
                                    <span class="sr-only">Behance</span>
                                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <rect x="2.5" y="6.5" width="19" height="11.5" rx="2" />
                                        <path d="M7.2 12.2h3.4c1.2 0 1.8-0.7 1.8-1.6 0-.9-0.7-1.5-1.9-1.5H7.2v3.1z" />
                                        <path d="M7.2 14.6h3.6c1.5 0 2.1 0.8 2.1 1.7 0 .9-0.7 1.8-2.3 1.8H7.2v-3.5z" />
                                    </svg>
                                </a>
                            </div>
                            -->
                            <!-- Neumorphic icon grid -->
                            <div class="icon-container">
                                <a class="icon" href="https://www.instagram.com/ripal_design12/" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                                    <span class="sr-only">Instagram</span>
                                    <i class="fab fa-instagram"></i>
                                </a>

                                <a class="icon" href="https://www.linkedin.com" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn">
                                    <span class="sr-only">LinkedIn</span>
                                    <i class="fab fa-linkedin-in"></i>
                                </a>

                                <a class="icon" href="https://www.behance.net/mayankvinchhi" target="_blank" rel="noopener noreferrer" aria-label="Behance">
                                    <span class="sr-only">Behance</span>
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
            <h1 class="text-3xl md:text-4xl font-bold text-white text-align-start mb-8">Send us a message</h1>
            <form class="max-w-lg w-full mx-0 space-y-8" action="" method="POST" id="signupForm" novalidate>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="group">
                        <label class="block text-xs uppercase tracking-widest text-gray-500 mb-2">First Name</label>
                        <input type="text" name="first_name" class="w-full py-3 text-lg border-b border-white/20 bg-transparent focus:border-[#731209] outline-none transition-colors" data-validation="required min alphabetic" data-min="2">
                        <span id="first_name_error" class="text-danger"></span>
                    </div>
                    <div class="group">
                        <label class="block text-xs uppercase tracking-widest text-gray-500 mb-2">Last Name</label>
                        <input type="text" name="last_name" class="w-full py-3 text-lg border-b border-white/20 bg-transparent focus:border-[#731209] outline-none transition-colors" data-validation="required min alphabetic" data-min="2">
                        <span id="last_name_error" class="text-danger"></span>
                    </div>
                </div>

                <div class="group">
                    <label class="block text-xs uppercase tracking-widest text-gray-500 mb-2">Email Address</label>
                    <input type="email" name="email" class="w-full py-3 text-lg border-b border-white/20 bg-transparent focus:border-[#731209] outline-none transition-colors" data-validation="required email">
                    <span id="email_error" class="text-danger"></span>
                </div>

                <div class="group">
                    <label class="block text-xs uppercase tracking-widest text-gray-500 mb-2">Subject</label>
                    <select name="subject" class="w-full py-3 text-lg border-b border-white/20 bg-transparent focus:border-[#731209] outline-none transition-colors text-gray-300" data-validation="required select">
                        <option value="" class="bg-black">Select Inquiry Type</option>
                        <option value="residential" class="bg-black">Residential Project</option>
                        <option value="commercial" class="bg-black">Commercial Project</option>
                        <option value="consultation" class="bg-black">Design Consultation</option>
                        <option value="other" class="bg-black">Other</option>
                    </select>
                    <span id="subject_error" class="text-danger"></span>
                </div>

                <div class="group">
                    <label class="block text-xs uppercase tracking-widest text-gray-500 mb-2">Message</label>
                    <textarea name="message" rows="4" class="w-full py-3 text-lg border-b border-white/20 bg-transparent focus:border-[#731209] outline-none transition-colors resize-none" data-validation="required min" data-min="10"></textarea>
                    <span id="message_error" class="text-danger"></span>
                </div>

                <div style="text-align:left; margin-top:1.5rem;">
                    <button type="submit" class="px-10 py-4 bg-[#731209] hover:bg-[#94180C] text-white uppercase tracking-widest text-sm transition-all duration-300" style="display:inline-block;">
                        Send Message
                    </button>
                </div>
            </form>
        </div>
    </main>

    <?php require_once __DIR__ . '/../Common/footer.php'; ?>
</body>

</html>