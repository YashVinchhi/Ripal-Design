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
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo esc($ct('page_title', 'About Us | Ripal Design')); ?></title>
    <link rel="icon" href="<?php echo esc_attr(BASE_PATH); ?>/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="./css/about_us.css">
</head>

<body>
    <div class="grain"></div>

    <?php $HEADER_MODE = 'public'; require_once __DIR__ . '/../app/Ui/header.php'; ?>

    <main>
        <!-- Hero Section -->
        <section class="hero-section position-relative d-flex align-items-center justify-content-center overflow-hidden">
            <div class="hero-overlay"></div>
            <div class="position-relative z-2 text-center container px-4">
                <span class="tracking-architect text-primary-brand mb-3 d-block" style="font-size: var(--hero-est-font-size, 30px); text-shadow: 2px 2px 5px black;"><?php echo esc($ct('hero_established', 'Est. 2017')); ?></span>
                <h1 class="display-1 mb-4"><?php echo esc($ct('hero_heading', "The Architect's Vision")); ?></h1>
                <p class="lead text-white-50 mx-auto" style="max-width: var(--content-max-width, 650px); letter-spacing: 0.05em;">
                    <?php echo esc($ct('hero_subheading', 'Precision in every measurement. Excellence in every build. Bridging the creative gap between design and reality.')); ?>
                </p>
                <div class="mt-5 pt-4">
                    <div class="vstack gap-2 align-items-center">
                        <span class="tracking-architect opacity-50"><?php echo esc($ct('hero_hint', 'Discovery')); ?></span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Our Story -->
        <section class="py-5 py-lg-9">
            <div class="container py-5">
                <div class="row gx-lg-5 align-items-center">
                    <div class="col-lg-5 mb-5 mb-lg-0">
                        <h2 class="display-3 mb-4"><?php echo esc($ct('story_heading_line', 'Duality in')); ?><br><span class="text-primary-brand"><?php echo esc($ct('story_heading_highlight', 'Execution')); ?></span></h2>
                        <div style="width: var(--section-divider-width, 40px); height: var(--section-divider-height, 1px); background: var(--primary);" class="mb-3"></div>
                        <p class="tracking-architect opacity-75"><?php echo esc($ct('story_kicker', 'The Ripal Approach')); ?></p>
                    </div>
                    <div class="col-lg-7">
                        <p class="lead text-white-50 mb-4" style="font-size: 1.4rem; font-weight: 300;">
                            <?php echo esc($ct('story_intro', 'Founded by two brothers - a designer and a builder - we bridge creative ambition with practical delivery.')); ?>
                        </p>
                        <p class="text-white-50">
                            <?php echo esc($ct('story_body', 'Our combined experience across municipal, institutional, and private works ensures designs that stand up to real-world constraints while remaining beautiful and timeless. We eliminate the gap between concept and creation by controlling the measure of every detail.')); ?>
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Timeline Section -->
        <section class="timeline-section">
            <div class="container mb-5 pb-5 text-center">
                <span class="tracking-architect text-primary-brand"><?php echo esc($ct('timeline_kicker', 'Milestones')); ?></span>
                <h2 class="display-4 font-serif mt-2"><?php echo esc($ct('timeline_heading', 'The Measure of Success')); ?></h2>
            </div>

            <div class="container-fluid px-lg-5">
                <div class="tape-wrapper position-relative mx-auto" style="width: 90%; height: var(--tape-wrapper-height, 250px);">
                    <!-- Tape Case -->
                    <div class="tape-body position-absolute start-0 top-50 translate-middle-y z-3">
                        <div class="tape-case d-flex align-items-center justify-content-center">
                            <img src="<?php echo esc_attr($ctImage('timeline_logo_image', '/assets/Content/Logo.png')); ?>" alt="<?php echo esc_attr($ct('timeline_logo_alt', 'Ripal Design Logo')); ?>" style="height:2.5rem;">
                        </div>
                    </div>

                    <!-- Tape Strip Container -->
                    <div class="tape-strip-container position-absolute top-50 translate-middle-y z-2 w-100" style="padding-left: var(--tape-strip-padding-left, 120px);">
                        <div class="tape-strip d-flex align-items-center" id="tapeStrip" style="width: 0%;">
                            <div class="tape-ticks"></div>
                            <div class="tape-hook position-absolute end-0"></div>
                        </div>
                    </div>

                    <!-- Milestones -->
                    <div class="milestones-wrapper position-absolute h-100 w-75" style="top: 0; right: 5%;">
                        <!-- 2017 -->
                        <div class="milestone-marker position-absolute translate-middle-x" style="left: 0%; top: 50%;" data-pos="0">
                            <div class="year-box">
                                <h3 class="mb-0"><?php echo esc($ct('milestone_1_year', '2017')); ?></h3>
                                <span class="tracking-architect" style="font-size: 0.65rem; color: var(--primary);"><?php echo esc($ct('milestone_1_label', 'Inception')); ?></span>
                            </div>
                            <div class="milestone-details shadow-lg">
                                <p class="small text-white mb-0"><?php echo esc($ct('milestone_1_description', 'Firm established with a design-build model, bridging the gap between concept and execution.')); ?></p>
                            </div>
                        </div>

                        <!-- 2021 -->
                        <div class="milestone-marker position-absolute translate-middle-x" style="left: 50%; top: 50%;" data-pos="50">
                            <div class="year-box">
                                <h3 class="mb-0"><?php echo esc($ct('milestone_2_year', '2021')); ?></h3>
                                <span class="tracking-architect" style="font-size: 0.65rem; color: var(--primary);"><?php echo esc($ct('milestone_2_label', 'Scale')); ?></span>
                            </div>
                            <div class="milestone-details shadow-lg">
                                <p class="small text-white mb-0"><?php echo esc($ct('milestone_2_description', 'Expanded into municipal projects and grew the core team to handle larger scale operations.')); ?></p>
                            </div>
                        </div>

                        <!-- 2026 -->
                        <div class="milestone-marker position-absolute translate-middle-x" style="left: 100%; top: 50%;" data-pos="100">
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

        <!-- Stats Section -->
        <section class="py-5 border-top border-secondary border-opacity-10">
            <div class="container">
                <div class="row g-0">
                    <div class="col-md-4 border-end border-secondary border-opacity-10">
                        <div class="stat-item text-center">
                            <div class="stat-number-bg">50</div>
                            <div class="stat-content">
                                <div class="display-3 font-serif mb-0"><?php echo esc($ct('stat_1_value', '50+')); ?></div>
                                <div class="tracking-architect opacity-50"><?php echo esc($ct('stat_1_label', 'Projects Completed')); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 border-end border-secondary border-opacity-10">
                        <div class="stat-item text-center">
                            <div class="stat-number-bg">09</div>
                            <div class="stat-content">
                                <div class="display-3 font-serif mb-0"><?php echo esc($ct('stat_2_value', '09')); ?></div>
                                <div class="tracking-architect opacity-50"><?php echo esc($ct('stat_2_label', 'Years Experience')); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-item text-center">
                            <div class="stat-number-bg">100</div>
                            <div class="stat-content">
                                <div class="display-3 font-serif mb-0"><?php echo esc($ct('stat_3_value', '100%')); ?></div>
                                <div class="tracking-architect opacity-50"><?php echo esc($ct('stat_3_label', 'Precision Rate')); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-5 bg-white text-black text-center">
            <div class="container py-5">
                <h2 class="display-3 font-serif mb-4"><?php echo esc($ct('cta_heading', 'Build the Extraordinary')); ?></h2>
                <p class="lead mb-5 opacity-75"><?php echo esc($ct('cta_subheading', 'Ready to start your next project with Ripal Design?')); ?></p>
                <a href="contact_us.php" class="btn btn-primary rounded-0 px-5 py-3 tracking-architect" style="background:#731209;border-color:#731209;color:#fff;"><?php echo esc($ct('cta_button', 'Contact Our Studio')); ?></a>
            </div>
        </section>
    </main>

    <?php asset_enqueue_js('/js/about_us.js'); ?>
    <?php require_once __DIR__ . '/../Common/footer.php'; ?>
</body>

</html>