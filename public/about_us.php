<!doctype html>
<?php require_once __DIR__ . '/../app/Core/Bootstrap/init.php'; ?>
<?php
$aboutContent = function_exists('public_content_page_values') ? public_content_page_values('about_us') : [];
$ct = static function ($key, $default = '') use ($aboutContent) {
    return (string)($aboutContent[$key] ?? $default);
};
$ctImage = static function ($key, $default = '') use ($aboutContent) {
    $value = (string)($aboutContent[$key] ?? $default);
    if (function_exists('public_content_image_url')) {
        return (string)public_content_image_url($value, $default);
    }
    if (function_exists('base_path')) {
        return (string)base_path(ltrim((string)$value, '/'));
    }
    return (string)$value;
};
?>
<html lang="en">

<head>
    <?php
    require_once __DIR__ . '/../includes/seo.php';
    require_once __DIR__ . '/../includes/schema.php';
    $page_data = [
        'title' => $ct('page_title', 'About Us'),
        'description' => $ct('meta_description', 'About Ripal Design - our story and values.'),
        'image' => $ctImage('timeline_logo_image', '/assets/Content/Logo.png'),
        'url' => rtrim((string)BASE_PATH, '/') . PUBLIC_PATH_PREFIX . '/about_us.php'
    ];
    render_seo_head($page_data);
    render_localbusiness_schema();
    render_breadcrumbs_schema();
    ?>
    <link rel="icon" href="<?php echo esc_attr(BASE_PATH); ?>/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="./css/about_us.css">
</head>

<body>
    <div class="grain"></div>

    <?php $HEADER_MODE = 'public'; require_once __DIR__ . '/../app/Ui/header.php'; ?>

    <main>
        <!-- Hero Section -->
        <section class="hero-section relative flex items-center justify-center overflow-hidden min-h-[44vh]">
            <div class="hero-overlay"></div>
            <div class="relative z-20 text-center max-w-7xl mx-auto px-4">
                <span class="tracking-architect text-primary-brand mb-3 block" style="font-size: var(--hero-est-font-size, 30px); text-shadow: 2px 2px 5px black;">
                    <?php echo esc($ct('hero_established', 'Est. 2017')); ?>
                </span>
                <h1 class="text-5xl md:text-6xl mb-4"><?php echo esc($ct('hero_heading', 'About the Studio')); ?></h1>
                <p class="text-white/70 mx-auto text-base md:text-lg max-w-[650px]" style="letter-spacing: 0.05em;">
                    <?php echo esc($ct('hero_subheading', 'A design-build partnership shaped by two brothers, rooted in Rajkot, trusted across Gujarat.')); ?>
                </p>
                <div class="mt-5 pt-4">
                    <div class="flex flex-col gap-2 items-center">
                        <span class="hero-scroll-cue"><span><?php echo esc($ct('hero_hint', 'Discovery')); ?></span><i class="fa-solid fa-arrow-down" aria-hidden="true"></i></span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Our Story -->
        <section class="py-5 story-section">
            <div class="max-w-7xl mx-auto px-6 py-5">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                    <div class="lg:col-span-5 mb-5 lg:mb-0">
                        <h2 class="text-4xl md:text-5xl font-serif mb-4"><?php echo esc($ct('story_heading_line', 'Duality in')); ?><br><span class="text-primary-brand"><?php echo esc($ct('story_heading_highlight', 'Execution')); ?></span></h2>
                        <div style="width: var(--section-divider-width, 40px); height: var(--section-divider-height, 1px); background: var(--primary);" class="mb-3"></div>
                        <p class="tracking-architect opacity-75"><?php echo esc($ct('story_kicker', 'The Ripal Approach')); ?></p>
                    </div>
                    <div class="lg:col-span-7">
                        <p class="text-white/70 mb-4 text-lg font-light">
                            <?php echo esc($ct('story_intro', 'Founded by two brothers - a designer and a builder - we bridge creative ambition with practical delivery.')); ?>
                        </p>
                        <p class="text-white/70">
                            <?php echo esc($ct('story_body', 'Our combined experience across municipal, institutional, and private works ensures designs that stand up to real-world constraints while remaining beautiful and timeless. We eliminate the gap between concept and creation by controlling the measure of every detail.')); ?>
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Timeline Section -->
        <section class="timeline-section">
            <div class="max-w-7xl mx-auto px-6 mb-5 pb-5 text-center">
                <span class="tracking-architect text-primary-brand"><?php echo esc($ct('timeline_kicker', 'Milestones')); ?></span>
                <h2 class="text-3xl md:text-4xl font-serif mt-2"><?php echo esc($ct('timeline_heading', 'The Measure of Success')); ?></h2>
            </div>

            <div class="w-full px-6 lg:px-20">
                <div class="tape-wrapper relative mx-auto" style="width: 90%; height: var(--tape-wrapper-height, 250px);">
                    <!-- Tape Case -->
                    <div class="tape-body absolute left-0 top-1/2 -translate-y-1/2 z-30">
                        <div class="tape-case flex items-center justify-center">
                            <img src="<?php echo esc_attr($ctImage('timeline_logo_image', '/assets/Content/Logo.png')); ?>" alt="<?php echo esc_attr($ct('timeline_logo_alt', 'Ripal Design Logo')); ?>" style="height:2.5rem;">
                        </div>
                    </div>

                    <!-- Tape Strip Container -->
                    <div class="tape-strip-container absolute top-1/2 -translate-y-1/2 z-20 w-full" style="padding-left: var(--tape-strip-padding-left, 120px);">
                        <div class="tape-strip flex items-center" id="tapeStrip" style="width: 0%;">
                            <div class="tape-ticks"></div>
                            <div class="tape-hook absolute right-0"></div>
                        </div>
                    </div>

                    <!-- Milestones -->
                    <div class="milestones-wrapper absolute h-full w-3/4" style="top: 0; right: 5%;">
                        <!-- 2017 -->
                        <div class="milestone-marker absolute -translate-x-1/2" style="left: 0%; top: 50%;" data-pos="0">
                            <div class="year-box">
                                <h3 class="mb-0"><?php echo esc($ct('milestone_1_year', '2017')); ?></h3>
                                <span class="tracking-architect" style="font-size: 0.65rem; color: var(--primary);"><?php echo esc($ct('milestone_1_label', 'Inception')); ?></span>
                            </div>
                            <div class="milestone-details shadow-lg">
                                <p class="small text-white mb-0"><?php echo esc($ct('milestone_1_description', 'Firm established with a design-build model, bridging the gap between concept and execution.')); ?></p>
                            </div>
                        </div>

                        <!-- 2021 -->
                        <div class="milestone-marker absolute -translate-x-1/2" style="left: 50%; top: 50%;" data-pos="50">
                            <div class="year-box">
                                <h3 class="mb-0"><?php echo esc($ct('milestone_2_year', '2021')); ?></h3>
                                <span class="tracking-architect" style="font-size: 0.65rem; color: var(--primary);"><?php echo esc($ct('milestone_2_label', 'Scale')); ?></span>
                            </div>
                            <div class="milestone-details shadow-lg">
                                <p class="small text-white mb-0"><?php echo esc($ct('milestone_2_description', 'Expanded into municipal projects and grew the core team to handle larger scale operations.')); ?></p>
                            </div>
                        </div>

                        <!-- 2026 -->
                        <div class="milestone-marker absolute -translate-x-1/2" style="left: 100%; top: 50%;" data-pos="100">
                            <div class="year-box">
                                <h3 class="mb-0"><?php echo esc($ct('milestone_3_year', '2026')); ?></h3>
                                <span class="tracking-architect" style="font-size: 0.65rem; color: var(--primary);"><?php echo esc($ct('milestone_3_label', 'Future')); ?></span>
                            </div>
                            <div class="milestone-details shadow-lg">
                                <p class="small text-white mb-0"><?php echo esc($ct('milestone_3_description', 'Aiming for global consultancy status and integrating sustainable tech in every build.')); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Team Section -->
        <section class="py-5 border-t border-white/10">
            <div class="max-w-7xl mx-auto px-6">
                <div class="text-center mb-12">
                    <span class="tracking-architect text-primary-brand"><?php echo esc($ct('team_kicker', 'Leadership')); ?></span>
                    <h2 class="text-3xl md:text-4xl font-serif mt-2"><?php echo esc($ct('team_heading', 'The Brothers Behind Ripal')); ?></h2>
                    <p class="text-white/70 max-w-2xl mx-auto mt-3"><?php echo esc($ct('team_subheading', 'Design vision and on-site execution, aligned end-to-end.')); ?></p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="border border-white/10 bg-[#0f0f0f] p-6">
                        <img src="<?php echo esc_attr($ctImage('team_1_image', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg')); ?>" alt="<?php echo esc_attr($ct('team_1_name', 'Ripal Vinchhi')); ?>" class="w-full h-72 object-cover mb-5" loading="lazy">
                        <h3 class="text-2xl font-serif"><?php echo esc($ct('team_1_name', 'Ripal Vinchhi')); ?></h3>
                        <p class="text-[#c6a26a] tracking-architect text-xs mt-1"><?php echo esc($ct('team_1_role', 'Design Director')); ?></p>
                        <p class="text-white/70 mt-4"><?php echo esc($ct('team_1_bio', 'Leads concept development, spatial strategy, and material direction across residential and public works.')); ?></p>
                    </div>
                    <div class="border border-white/10 bg-[#0f0f0f] p-6">
                        <img src="<?php echo esc_attr($ctImage('team_2_image', '/assets/Content/WhatsApp Image 2026-02-02 at 5.43.21 PM (1).jpeg')); ?>" alt="<?php echo esc_attr($ct('team_2_name', 'Mayank Vinchhi')); ?>" class="w-full h-72 object-cover mb-5" loading="lazy">
                        <h3 class="text-2xl font-serif"><?php echo esc($ct('team_2_name', 'Mayank Vinchhi')); ?></h3>
                        <p class="text-[#c6a26a] tracking-architect text-xs mt-1"><?php echo esc($ct('team_2_role', 'Execution Lead')); ?></p>
                        <p class="text-white/70 mt-4"><?php echo esc($ct('team_2_bio', 'Oversees on-site delivery, vendor coordination, and schedule integrity.')); ?></p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="py-5 about-stats-section border-t border-white/10">
            <div class="max-w-7xl mx-auto px-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="border-r border-white/10 pr-6">
                        <div class="stat-item text-center">
                            <div class="stat-number-bg">50</div>
                            <div class="stat-content">
                                <div class="text-3xl md:text-4xl font-serif mb-0"><?php echo esc($ct('stat_1_value', '50+')); ?></div>
                                <div class="tracking-architect opacity-50"><?php echo esc($ct('stat_1_label', 'Projects Completed')); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="border-r border-white/10 pr-6">
                        <div class="stat-item text-center">
                            <div class="stat-number-bg">09</div>
                            <div class="stat-content">
                                <div class="text-3xl md:text-4xl font-serif mb-0"><?php echo esc($ct('stat_2_value', '09+')); ?></div>
                                <div class="tracking-architect opacity-50"><?php echo esc($ct('stat_2_label', 'Years Experience')); ?></div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="stat-item text-center">
                            <div class="stat-number-bg">100</div>
                            <div class="stat-content">
                                <div class="text-3xl md:text-4xl font-serif mb-0"><?php echo esc($ct('stat_3_value', '100%')); ?></div>
                                <div class="tracking-architect opacity-50"><?php echo esc($ct('stat_3_label', 'Precision Rate')); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-5 about-cta text-center">
            <div class="container py-5">
                <h2 class="display-3 font-serif mb-4"><?php echo esc($ct('cta_heading', 'Build the Extraordinary')); ?></h2>
                <p class="lead mb-5 opacity-75"><?php echo esc($ct('cta_subheading', 'Ready to start your next project with Ripal Design?')); ?></p>
                <a href="contact_us.php" class="inline-block bg-rajkot-rust text-white px-5 py-3 tracking-architect rounded about-cta-btn"><?php echo esc($ct('cta_button', 'Contact Our Studio')); ?></a>
            </div>
        </section>
    </main>

    <?php asset_enqueue_js('/js/about_us.js'); ?>
    <?php require_once __DIR__ . '/../Common/footer.php'; ?>
</body>

</html>