<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Services | Ripal Design</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />
    
    <style>
        body { background-color: #050505; color: #fff; font-family: 'Inter', sans-serif; }
        h1, h2, h3, .serif { font-family: 'Cormorant Garamond', serif; }
        .service-item { transition: all 0.5s ease; opacity: 0.5; border-left: 1px solid rgba(255,255,255,0.1); }
        .service-item.active { opacity: 1; padding-left: 2rem; border-color: #731209; }
        .service-item:hover { opacity: 0.8; cursor: pointer; }
    </style>
</head>
<body class="bg-[#050505] text-white overflow-x-hidden">
    <!-- Header/Nav -->
    <?php $HEADER_MODE = 'public'; require_once __DIR__ . '/../includes/header.php'; ?>

    <main class="relative min-h-screen pt-24 pb-20">
        <div class="container mx-auto px-6 h-full">
            <div class="flex flex-col lg:flex-row h-full min-h-[80vh]">
                
                <!-- Left: Service List -->
                <div class="w-full lg:w-5/12 flex flex-col justify-center space-y-8 z-10 py-10">
                    <div class="mb-12">
                        <span class="text-[#731209] tracking-[0.2em] text-sm uppercase font-semibold">Our Expertise</span>
                        <h1 class="text-5xl md:text-6xl serif mt-4 leading-tight">Crafting Spaces<br>With Purpose.</h1>
                    </div>

                    <div class="space-y-6" id="serviceList">
                        <div class="service-item active group pl-6" data-img="assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg">
                            <h3 class="text-3xl lg:text-4xl serif group-hover:text-[#731209] transition-colors">Architectural Planning</h3>
                            <p class="text-gray-400 mt-2 text-sm max-w-md hidden group-[.active]:block transition-opacity duration-500">Comprehensive master planning and structural design that balances aesthetics with functionality.</p>
                        </div>
                        
                        <div class="service-item pl-6 group" data-img="assets/Content/WhatsApp Image 2026-02-02 at 5.43.21 PM (1).jpeg">
                            <h3 class="text-3xl lg:text-4xl serif group-hover:text-[#731209] transition-colors">Interior Design</h3>
                            <p class="text-gray-400 mt-2 text-sm max-w-md hidden group-[.active]:block transition-opacity duration-500">Curating internal environments that evoke emotion through texture, light, and material.</p>
                        </div>

                        <div class="service-item pl-6 group" data-img="assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg">
                            <h3 class="text-3xl lg:text-4xl serif group-hover:text-[#731209] transition-colors">Landscape Architecture</h3>
                            <p class="text-gray-400 mt-2 text-sm max-w-md hidden group-[.active]:block transition-opacity duration-500">Harmonizing built structures with the natural environment for sustainable outdoor living.</p>
                        </div>

                        <div class="service-item pl-6 group" data-img="assets/Content/WhatsApp Image 2026-02-02 at 5.51.43 PM.jpeg">
                            <h3 class="text-3xl lg:text-4xl serif group-hover:text-[#731209] transition-colors">Project Management</h3>
                            <p class="text-gray-400 mt-2 text-sm max-w-md hidden group-[.active]:block transition-opacity duration-500">End-to-end oversight ensuring precision in execution and adherence to timelines.</p>
                        </div>
                    </div>
                </div>

                <!-- Right: Image Display -->
                <div class="w-full lg:w-7/12 relative h-[50vh] lg:h-auto mt-8 lg:mt-0">
                    <div class="lg:absolute lg:inset-y-0 lg:right-0 w-full lg:w-[90%] h-full overflow-hidden rounded-sm">
                        <!-- Images overlay each other -->
                        <div id="imageDisplay" class="w-full h-full relative">
                            <!-- Default Image -->
                            <img src="assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg" 
                                 class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 ease-in-out scale-105"
                                 alt="Architectural Service">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                        </div>
                        
                        <!-- Decoration -->
                        <div class="absolute bottom-10 right-10 border border-white/20 p-4 backdrop-blur-sm hidden md:block">
                            <span class="block text-xs uppercase tracking-widest mb-1 text-[#731209]">Ripal Design</span>
                            <span class="block text-2xl serif">2026 Collection</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/../Common/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const listItems = document.querySelectorAll('.service-item');
            const displayContainer = document.getElementById('imageDisplay');
            let currentImg = displayContainer.querySelector('img');

            listItems.forEach(item => {
                item.addEventListener('mouseenter', () => {
                    listItems.forEach(i => i.classList.remove('active'));
                    item.classList.add('active');

                    const newSrc = item.getAttribute('data-img');
                    // Simple check to avoid reload if same image (e.g. reused placeholder)
                    // In real scenario images should be unique.
                    
                    const newImg = document.createElement('img');
                    newImg.src = newSrc;
                    newImg.className = "absolute inset-0 w-full h-full object-cover transition-opacity duration-500 opacity-0 scale-105";
                    
                    displayContainer.appendChild(newImg);
                    
                    // Trigger reflow
                    void newImg.offsetWidth;
                    
                    newImg.classList.remove('opacity-0');
                    
                    setTimeout(() => {
                        if(currentImg && currentImg !== newImg) {
                            if(currentImg.parentNode) currentImg.remove();
                        }
                        currentImg = newImg;
                    }, 500);
                });
            });
        });
    </script>
</body>
</html>