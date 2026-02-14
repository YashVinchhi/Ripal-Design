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
    <style>
        body { background-color: #050505; color: #fff; font-family: 'Inter', sans-serif; }
        .serif { font-family: 'Cormorant Garamond', serif; }
        input, textarea, select {
            background-color: transparent;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            transition: border-color 0.3s ease;
        }
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #731209;
        }
    </style>
</head>
<body class="bg-[#050505] text-white overflow-x-hidden">
    <?php $HEADER_MODE = 'public'; require_once __DIR__ . '/../includes/header.php'; ?>

    <main class="relative min-h-screen flex flex-col md:flex-row pt-24">
        <!-- Left: Info -->
        <div class="w-full md:w-1/2 p-8 md:p-20 flex flex-col justify-center bg-[#0a0a0a]">
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
                        <p>+91 98765 43210<br>projects@ripaldesign.in</p>
                    </div>
                    <div>
                        <h4 class="text-white text-lg font-medium mb-1">Social</h4>
                        <div class="flex gap-4 mt-2">
                            <a href="#" class="hover:text-[#731209] transition-colors">Instagram</a>
                            <a href="#" class="hover:text-[#731209] transition-colors">LinkedIn</a>
                            <a href="#" class="hover:text-[#731209] transition-colors">Behance</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Form -->
        <div class="w-full md:w-1/2 p-8 md:p-20 bg-[#050505] flex flex-col justify-center">
            <form class="max-w-lg w-full mx-auto md:mx-0 space-y-8" action="#" method="POST">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="group">
                        <label class="block text-xs uppercase tracking-widest text-gray-500 mb-2">First Name</label>
                        <input type="text" name="first_name" class="w-full py-3 text-lg border-b border-white/20 bg-transparent focus:border-[#731209] outline-none transition-colors" required>
                    </div>
                    <div class="group">
                        <label class="block text-xs uppercase tracking-widest text-gray-500 mb-2">Last Name</label>
                        <input type="text" name="last_name" class="w-full py-3 text-lg border-b border-white/20 bg-transparent focus:border-[#731209] outline-none transition-colors" required>
                    </div>
                </div>

                <div class="group">
                    <label class="block text-xs uppercase tracking-widest text-gray-500 mb-2">Email Address</label>
                    <input type="email" name="email" class="w-full py-3 text-lg border-b border-white/20 bg-transparent focus:border-[#731209] outline-none transition-colors" required>
                </div>

                <div class="group">
                    <label class="block text-xs uppercase tracking-widest text-gray-500 mb-2">Subject</label>
                    <select name="subject" class="w-full py-3 text-lg border-b border-white/20 bg-transparent focus:border-[#731209] outline-none transition-colors text-gray-300">
                        <option value="" class="bg-black">Select Inquiry Type</option>
                        <option value="residential" class="bg-black">Residential Project</option>
                        <option value="commercial" class="bg-black">Commercial Project</option>
                        <option value="consultation" class="bg-black">Design Consultation</option>
                        <option value="other" class="bg-black">Other</option>
                    </select>
                </div>

                <div class="group">
                    <label class="block text-xs uppercase tracking-widest text-gray-500 mb-2">Message</label>
                    <textarea name="message" rows="4" class="w-full py-3 text-lg border-b border-white/20 bg-transparent focus:border-[#731209] outline-none transition-colors resize-none"></textarea>
                </div>

                <button type="button" class="mt-8 px-10 py-4 bg-[#731209] hover:bg-[#94180C] text-white uppercase tracking-widest text-sm transition-all duration-300 w-full md:w-auto">
                    Send Message
                </button>
            </form>
        </div>
    </main>

    <?php require_once __DIR__ . '/../Common/footer.php'; ?>
</body>
</html>