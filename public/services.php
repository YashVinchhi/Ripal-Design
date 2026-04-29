<?php
require_once __DIR__ . '/../app/Core/Bootstrap/init.php';
$servicesContent = function_exists('public_content_page_values') ? public_content_page_values('services') : [];
$ct = static function ($key, $default = '') use ($servicesContent) {
  return (string)($servicesContent[$key] ?? $default);
};
$ctImage = static function ($key, $default = '') use ($servicesContent) {
  $value = (string)($servicesContent[$key] ?? $default);
  if (function_exists('public_content_image_url')) {
    return (string)public_content_image_url($value, $default);
  }
  if (function_exists('base_path')) {
    return (string)base_path(ltrim((string)$value, '/'));
  }
  return (string)$value;
};
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">

<head>
  <?php
  require_once __DIR__ . '/../includes/seo.php';
  require_once __DIR__ . '/../includes/schema.php';
  $page_data = [
    'title' => $ct('page_title', 'Services'),
    'description' => $ct('meta_description', 'Our architectural and design services.'),
    'image' => $ctImage('hero_image_src', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg'),
    'url' => rtrim((string)BASE_PATH, '/') . PUBLIC_PATH_PREFIX . '/services.php'
  ];
  render_seo_head($page_data);
  // Build a simple services list for schema
  $servicesList = [
    ['name' => $ct('service_1_title', 'Architectural Planning'), 'description' => $ct('service_1_description', '')],
    ['name' => $ct('service_2_title', 'Interior Design'), 'description' => $ct('service_2_description', '')],
    ['name' => $ct('service_3_title', 'Landscape Architecture'), 'description' => $ct('service_3_description', '')],
    ['name' => $ct('service_4_title', 'Project Management'), 'description' => $ct('service_4_description', '')],
  ];
  render_services_schema($servicesList);
  render_breadcrumbs_schema();
  ?>
  <link rel="icon" href="<?php echo esc_attr(BASE_PATH); ?>/favicon.ico" type="image/x-icon">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,400&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="./css/service.css">

</head>

<body class="bg-[#050505] text-white overflow-x-hidden">
  <div class="grain"></div>

  <?php $HEADER_MODE = 'public'; require_once __DIR__ . '/../app/Ui/header.php'; ?>

  <main class="relative min-h-screen pb-20">
    <!-- Hero Section -->
    <section class="hero-section relative flex items-center justify-center overflow-hidden min-h-[44vh]">
      <div class="hero-overlay"></div>
      <div class="relative z-20 text-center max-w-7xl mx-auto px-4">
        <span class="tracking-architect text-primary-brand mb-3 block" style="font-size: 30px; text-shadow: 2px 2px 5px black;"><?php echo esc($ct('hero_established', 'Est. 2017')); ?></span>
        <h1 class="text-5xl md:text-6xl mb-4 font-serif"><?php echo esc($ct('hero_heading', 'Services & Process')); ?></h1>
        <p class="text-white/70 mx-auto text-base md:text-lg max-w-[650px]" style="letter-spacing: 0.05em;">
          <?php echo esc($ct('hero_subheading', 'From site strategy to material detailing, we design spaces that are buildable, measurable, and on-budget.')); ?>
        </p>
        <div class="mt-5 pt-4">
          <div class="flex flex-col gap-2 items-center">
            <span class="hero-scroll-cue"><span><?php echo esc($ct('hero_hint', 'Discovery')); ?></span><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></span>
          </div>
        </div>
      </div>
    </section>

    <!-- Services Section -->
    <div class="container mx-auto px-6 h-full">
      <div class="flex flex-col lg:flex-row h-full min-h-[80vh]">
        <!-- Left: Service List -->
        <div class="w-full lg:w-5/12 flex flex-col justify-center space-y-8 z-10 py-10">
          <div class="mb-12">
            <span class="text-[#731209] tracking-[0.2em] text-sm uppercase font-semibold"><?php echo esc($ct('section_kicker', 'Our Expertise')); ?></span>
            <h1 class="text-5xl md:text-6xl serif mt-4 leading-tight"><?php echo esc($ct('section_heading_line_1', 'Crafting Spaces')); ?><br><?php echo esc($ct('section_heading_line_2', 'With Purpose.')); ?></h1>
          </div>

          <div class="space-y-6" id="serviceList">
            <div class="service-item active pl-6" data-img="<?php echo esc_attr($ctImage('service_image_1', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg')); ?>">
              <h3 class="text-3xl lg:text-4xl serif hover:text-[#731209] transition-colors"><?php echo esc($ct('service_1_title', 'Architectural Planning')); ?></h3>
              <p class="text-gray-400 mt-2 text-sm max-w-md hidden transition-opacity duration-500">
                <?php echo esc($ct('service_1_description', 'Comprehensive master planning and structural design that balances aesthetics with functionality.')); ?>
              </p>
            </div>

            <div class="service-item pl-6" data-img="<?php echo esc_attr($ctImage('service_image_2', '/assets/Content/WhatsApp Image 2026-02-02 at 5.43.21 PM (1).jpeg')); ?>">
              <h3 class="text-3xl lg:text-4xl serif hover:text-[#731209] transition-colors"><?php echo esc($ct('service_2_title', 'Interior Design')); ?></h3>
              <p class="text-gray-400 mt-2 text-sm max-w-md hidden transition-opacity duration-500">
                <?php echo esc($ct('service_2_description', 'Curating internal environments that evoke emotion through texture, light, and material.')); ?>
              </p>
            </div>

            <div class="service-item pl-6" data-img="<?php echo esc_attr($ctImage('service_image_3', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg')); ?>">
              <h3 class="text-3xl lg:text-4xl serif hover:text-[#731209] transition-colors"><?php echo esc($ct('service_3_title', 'Landscape Architecture')); ?></h3>
              <p class="text-gray-400 mt-2 text-sm max-w-md hidden transition-opacity duration-500">
                <?php echo esc($ct('service_3_description', 'Harmonizing built structures with the natural environment for sustainable outdoor living.')); ?>
              </p>
            </div>

            <div class="service-item pl-6" data-img="<?php echo esc_attr($ctImage('service_image_4', '/assets/Content/WhatsApp Image 2026-02-02 at 5.51.43 PM.jpeg')); ?>">
              <h3 class="text-3xl lg:text-4xl serif hover:text-[#731209] transition-colors"><?php echo esc($ct('service_4_title', 'Project Management')); ?></h3>
              <p class="text-gray-400 mt-2 text-sm max-w-md hidden transition-opacity duration-500">
                <?php echo esc($ct('service_4_description', 'End-to-end oversight ensuring precision in execution and adherence to timelines.')); ?>
              </p>
            </div>
          </div>
        </div>

        <!-- Right: Image Display -->
        <div class="w-full lg:w-7/12 relative h-[50vh] lg:h-auto mt-8 lg:mt-0">
          <div class="lg:absolute lg:inset-y-0 lg:right-0 w-full lg:w-[90%] h-full overflow-hidden rounded-sm">
            <div id="imageDisplay" class="w-full h-full relative">
              <img src="<?php echo esc_attr($ctImage('hero_image_src', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg')); ?>"
                class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 ease-in-out scale-105"
                alt="<?php echo esc_attr($ct('hero_image_alt', 'Architectural service image')); ?>">
              <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
            </div>

            <!-- Decoration Badge -->
            <div class="service-highlight-badge absolute bottom-10 right-10 hidden md:block">
              <span class="service-highlight-brand"><?php echo esc($ct('badge_brand', 'Ripal Design')); ?></span>
              <span class="service-highlight-title"><?php echo esc($ct('badge_label', '2026 Collection')); ?></span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <section class="bg-[#0b0b0b] py-16 border-t border-white/10">
    <div class="max-w-7xl mx-auto px-6">
      <div class="text-center mb-12">
        <span class="text-[#731209] tracking-[0.2em] text-sm uppercase font-semibold">Process</span>
        <h2 class="text-4xl md:text-5xl serif mt-4">How We Deliver</h2>
        <p class="text-white/70 max-w-2xl mx-auto mt-4">Clear stages, measurable milestones, and the same team from first sketch to handover.</p>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="border border-white/10 p-6 bg-[#0f0f0f]">
          <div class="text-[#731209] tracking-[0.2em] text-xs uppercase font-semibold">01</div>
          <h3 class="text-2xl serif mt-3">Discovery</h3>
          <p class="text-white/60 mt-3 text-sm">Site visit, brief alignment, and budget reality check.</p>
        </div>
        <div class="border border-white/10 p-6 bg-[#0f0f0f]">
          <div class="text-[#731209] tracking-[0.2em] text-xs uppercase font-semibold">02</div>
          <h3 class="text-2xl serif mt-3">Concept & Planning</h3>
          <p class="text-white/60 mt-3 text-sm">Layouts, massing, materials, and approvals roadmap.</p>
        </div>
        <div class="border border-white/10 p-6 bg-[#0f0f0f]">
          <div class="text-[#731209] tracking-[0.2em] text-xs uppercase font-semibold">03</div>
          <h3 class="text-2xl serif mt-3">Detail & Execution</h3>
          <p class="text-white/60 mt-3 text-sm">Technical drawings, BOQ, vendor coordination, and site supervision.</p>
        </div>
        <div class="border border-white/10 p-6 bg-[#0f0f0f]">
          <div class="text-[#731209] tracking-[0.2em] text-xs uppercase font-semibold">04</div>
          <h3 class="text-2xl serif mt-3">Handover</h3>
          <p class="text-white/60 mt-3 text-sm">Final walkthrough, material warranties, and post-handover support.</p>
        </div>
      </div>
    </div>
  </section>

  <section class="bg-black py-16 border-t border-white/10">
    <div class="max-w-7xl mx-auto px-6">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-start">
        <div>
          <span class="text-[#731209] tracking-[0.2em] text-sm uppercase font-semibold">Engagement</span>
          <h2 class="text-4xl md:text-5xl serif mt-4">What You Receive</h2>
          <ul class="mt-6 space-y-3 text-white/70">
            <li>Concept plan + mood direction</li>
            <li>3D views and material palette</li>
            <li>Detailed drawings and documentation</li>
            <li>Site coordination and weekly progress updates</li>
          </ul>
        </div>
        <div class="bg-[#0f0f0f] border border-white/10 p-8">
          <h3 class="text-2xl serif">Starting Packages</h3>
          <p class="text-white/60 mt-3 text-sm">Final pricing depends on scope, site conditions, and timeline.</p>
          <div class="mt-6 space-y-4">
            <div class="flex items-start justify-between gap-4">
              <div>
                <h4 class="text-lg serif">Concept Planning</h4>
                <p class="text-white/60 text-sm">Layout + feasibility + budget guidance</p>
              </div>
              <span class="text-[#c6a26a] text-sm">From ₹ 35k</span>
            </div>
            <div class="flex items-start justify-between gap-4">
              <div>
                <h4 class="text-lg serif">Design + Interiors</h4>
                <p class="text-white/60 text-sm">3D visuals + material specification</p>
              </div>
              <span class="text-[#c6a26a] text-sm">From ₹ 1.2L</span>
            </div>
            <div class="flex items-start justify-between gap-4">
              <div>
                <h4 class="text-lg serif">Design + Execution</h4>
                <p class="text-white/60 text-sm">Turnkey delivery with supervision</p>
              </div>
              <span class="text-[#c6a26a] text-sm">Custom Quote</span>
            </div>
          </div>
          <a href="contact_us.php" class="inline-flex mt-8 bg-rajkot-rust hover:bg-red-700 text-white font-serif px-6 py-3 no-underline">Request a Proposal</a>
        </div>
      </div>
    </div>
  </section>

  <?php require_once __DIR__ . '/../Common/footer.php'; ?>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const dynamicAlt = <?php echo json_encode($ct('dynamic_image_alt', 'Service image')); ?>;
      const listItems = Array.from(document.querySelectorAll('.service-item'));
      const displayContainer = document.getElementById('imageDisplay');
      let currentImg = displayContainer ? displayContainer.querySelector('img') : null;

      if (!displayContainer || !listItems.length) return;

      listItems.forEach(item => {
        item.addEventListener('mouseenter', () => {
          // Update active state and hide descriptions on others
          listItems.forEach(i => {
            i.classList.remove('active');
            const p = i.querySelector('p');
            if (p) {
              p.classList.add('hidden');
              p.classList.remove('block');
            }
          });

          // Activate current item and show its description
          item.classList.add('active');
          const desc = item.querySelector('p');
          if (desc) {
            desc.classList.remove('hidden');
            desc.classList.add('block');
          }

          // Create and transition to new image
          const newSrc = item.getAttribute('data-img');
          if (!newSrc) return;
          const newImg = document.createElement('img');
          newImg.src = newSrc;
          newImg.className = "absolute inset-0 w-full h-full object-cover transition-opacity duration-500 opacity-0 scale-105";
          newImg.alt = dynamicAlt;

          displayContainer.appendChild(newImg);

          // Trigger reflow for animation
          void newImg.offsetWidth;
          newImg.classList.remove('opacity-0');

          // Remove old image after transition
          setTimeout(() => {
            if (currentImg && currentImg !== newImg && currentImg.parentNode) {
              currentImg.remove();
            }
            currentImg = newImg;
          }, 500);
        });
      });
    });
  </script>
</body>

</html>