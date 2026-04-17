<?php
require_once __DIR__ . '/../app/Core/Bootstrap/init.php';
http_response_code(404);

$errorContent = function_exists('public_content_page_values') ? public_content_page_values('error_404') : [];
$ct = static function ($key, $default = '') use ($errorContent) {
    return (string)($errorContent[$key] ?? $default);
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo esc($ct('page_title', 'Lost in Space | Ripal Design')); ?></title>
  <link rel="icon" href="<?php echo esc_attr(BASE_PATH); ?>/favicon.ico" type="image/x-icon">
</head>
<body class="bg-foundation-grey font-sans text-white min-h-screen overflow-hidden relative">
  <?php $HEADER_MODE = 'public'; require_once __DIR__ . '/../app/Ui/header.php'; ?>

  <main class="relative z-10 min-h-[70vh] flex items-center justify-center px-4 py-16">
    <div class="max-w-2xl w-full text-center border border-white/10 bg-black/40 backdrop-blur-sm p-8 md:p-12 shadow-premium">
      <span class="text-[110px] md:text-[160px] font-black leading-none text-white/10 select-none tracking-tighter">404</span>
      <h1 class="text-3xl md:text-5xl font-serif font-bold text-rajkot-rust -mt-6 mb-4"><?php echo esc($ct('heading', 'Structure Not Found')); ?></h1>
      <p class="text-gray-300 text-sm md:text-base max-w-md mx-auto leading-relaxed mb-8"><?php echo esc($ct('message', "The architectural blueprint you're looking for seems to have been misplaced or never existed.")); ?></p>

      <div class="flex flex-col md:flex-row items-center justify-center gap-4 mt-4">
        <a href="<?php echo esc_attr(base_path('public/index.php')); ?>" class="w-full md:w-auto px-8 py-3 bg-rajkot-rust text-white font-bold rounded-full hover:bg-red-800 transition shadow-lg shadow-red-900/40 no-underline">
          <?php echo esc($ct('button_home', 'Back to Home')); ?>
        </a>
        <button onclick="window.history.back()" class="w-full md:w-auto px-8 py-3 bg-white/5 border border-white/10 text-white font-bold rounded-full hover:bg-white/10 transition">
          <?php echo esc($ct('button_back', 'Previous Page')); ?>
        </button>
      </div>

      <div class="mt-10 pt-6 border-t border-white/5">
        <p class="text-[10px] uppercase tracking-[0.2em] text-gray-500 font-bold"><?php echo esc($ct('footer_caption', 'Ripal Design & Engineering Studio')); ?></p>
      </div>
    </div>
  </main>

  <?php require_once __DIR__ . '/../Common/footer.php'; ?>
</body>
</html>