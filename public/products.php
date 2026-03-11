<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Products | Ripal Design</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <style>
        body { background-color: #050505; color: #fff; font-family: 'Inter', sans-serif; }
        .serif { font-family: 'Cormorant Garamond', serif; }
        .product-card:hover img { transform: scale(1.05); }
    </style>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Products | Ripal Design</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <style>
        body { background-color: #050505; color: #fff; font-family: 'Inter', sans-serif; }
        .serif { font-family: 'Cormorant Garamond', serif; }
        .product-card:hover img { transform: scale(1.05); }
    </style>
</head>
<body class="bg-[#050505] text-white overflow-x-hidden">
    <?php $HEADER_MODE = 'public'; require_once __DIR__ . '/../includes/header.php'; ?>

    <main class="min-h-screen pt-32 pb-20">
        <div class="container mx-auto px-6">
            <div class="text-center mb-20">
                <span class="text-[#731209] tracking-[0.2em] text-sm uppercase font-semibold">Exquisite Materials</span>
                <h1 class="text-5xl md:text-6xl serif mt-4">Curated Collection</h1>
            </div>

            <!-- Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <a href="contact_us.php?subject=residential" class="product-card group cursor-pointer no-underline block">
                    <div class="relative overflow-hidden aspect-[4/5] bg-gray-900 mb-6">
                        <img src="../assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg" class="w-full h-full object-cover transition-transform duration-700 ease-out" alt="Product">
                        <div class="absolute inset-0 bg-black/20 group-hover:bg-transparent transition-colors"></div>
                    </div>
                    <div>
                        <h3 class="text-2xl serif mb-1">Italian Marble Series</h3>
                        <p class="text-gray-500 text-sm tracking-wide uppercase">Flooring & Cladding</p>
                    </div>
                </a>
                

                <a href="contact_us.php?subject=commercial" class="product-card group cursor-pointer no-underline block">
                    <div class="relative overflow-hidden aspect-[4/5] bg-gray-900 mb-6">
                        <img src="../assets/Content/WhatsApp Image 2026-02-02 at 5.43.21 PM (1).jpeg" class="w-full h-full object-cover transition-transform duration-700 ease-out" alt="Product">
                         <div class="absolute inset-0 bg-black/20 group-hover:bg-transparent transition-colors"></div>
                    </div>
                    <div>
                        <h3 class="text-2xl serif mb-1">Lumina Pendant</h3>
                        <p class="text-gray-500 text-sm tracking-wide uppercase">Lighting</p>
                    </div>
                </a>
         

                <a href="contact_us.php?subject=consultation" class="product-card group cursor-pointer no-underline block">
                    <div class="relative overflow-hidden aspect-[4/5] bg-gray-900 mb-6">
                        <img src="../assets/Content/WhatsApp Image 2026-02-02 at 5.51.43 PM.jpeg" class="w-full h-full object-cover transition-transform duration-700 ease-out" alt="Product">
                         <div class="absolute inset-0 bg-black/20 group-hover:bg-transparent transition-colors"></div>
                    </div>
                    <div>
                        <h3 class="text-2xl serif mb-1">Oak Wood Panels</h3>
                        <p class="text-gray-500 text-sm tracking-wide uppercase">Interiors</p>
                    </div>
                </a>
               
            </div>
            
            <div class="mt-20 text-center">
                <a href="contact_us.php" class="inline-block border-b border-[#731209] pb-1 text-sm tracking-widest uppercase hover:text-[#731209] transition-colors text-decoration-none">Request Catalog</a>
            </div>
        </div>
    </main>

    <?php require_once __DIR__ . '/../Common/footer.php'; ?>
</body>
</html> 