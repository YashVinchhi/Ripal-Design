<?php
require_once __DIR__ . '/../Common/public_shell.php';

$content = function_exists('public_content_page_values') ? public_content_page_values('about_us') : [];
$ct = static fn ($key, $default = '') => (string)($content[$key] ?? $default);
$image = static fn ($key, $default) => rd_content_image($content, $key, $default);
$heroImage = $image('team_1_image', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg');

rd_page_start([
    'title' => $ct('page_title', 'About the Studio'),
    'description' => $ct('meta_description', 'Meet Ripal Design, a Rajkot design-build studio focused on practical architecture and interiors.'),
    'image' => $heroImage,
    'url' => rd_public_url('about_us.php'),
    'active' => 'about',
]);
?>
<main id="main">
    <section class="hero">
        <div class="hero-copy">
            <p class="eyebrow"><?php echo esc($ct('hero_established', 'Est. 2017')); ?></p>
            <h1><?php echo esc($ct('hero_heading', 'A studio built around design and delivery.')); ?></h1>
            <p><?php echo esc($ct('hero_subheading', 'Ripal Design is led by two brothers with complementary strengths: design direction and site execution. Clients get a calmer, more accountable path from first sketch to handover.')); ?></p>
            <a class="button button-primary" href="<?php echo esc_attr(rd_public_url('contact_us.php')); ?>">Talk to the Studio</a>
        </div>
        <div class="hero-media">
            <figure><img src="<?php echo esc_attr($heroImage); ?>" alt="Ripal Design studio work"></figure>
        </div>
    </section>

    <section class="page-section">
        <div class="feature-panel">
            <img class="feature-image" src="<?php echo esc_attr($image('team_2_image', '/assets/Content/WhatsApp Image 2026-02-02 at 5.43.21 PM (1).jpeg')); ?>" alt="Project execution detail">
            <div class="feature-copy">
                <p class="eyebrow"><?php echo esc($ct('story_kicker', 'The Ripal Approach')); ?></p>
                <h2><?php echo esc($ct('story_heading_line', 'Duality in')); ?> <?php echo esc($ct('story_heading_highlight', 'execution')); ?></h2>
                <p><?php echo esc($ct('story_body', 'We remove friction between concept and construction by keeping drawings, materials, budget, and site decisions connected throughout the project.')); ?></p>
            </div>
        </div>
    </section>

    <section class="page-section">
        <div class="section-head">
            <div>
                <p class="eyebrow">Milestones</p>
                <h2>A practical studio, steadily grown.</h2>
            </div>
        </div>
        <div class="card-grid">
            <article class="process-step"><strong>2017</strong><h3><?php echo esc($ct('milestone_1_label', 'Inception')); ?></h3><p><?php echo esc($ct('milestone_1_description', 'Started with a design-build model focused on closing the gap between concept and execution.')); ?></p></article>
            <article class="process-step"><strong>2021</strong><h3><?php echo esc($ct('milestone_2_label', 'Scale')); ?></h3><p><?php echo esc($ct('milestone_2_description', 'Expanded into larger residential, institutional, and municipal work.')); ?></p></article>
            <article class="process-step"><strong><?php echo date('Y'); ?></strong><h3><?php echo esc($ct('milestone_3_label', 'Now')); ?></h3><p><?php echo esc($ct('milestone_3_description', 'Refining a clear, accountable experience for clients across Gujarat.')); ?></p></article>
        </div>
    </section>

    <section class="page-section">
        <div class="section-head">
            <div>
                <p class="eyebrow"><?php echo esc($ct('team_kicker', 'Leadership')); ?></p>
                <h2><?php echo esc($ct('team_heading', 'The people accountable for the work.')); ?></h2>
            </div>
        </div>
        <div class="split-grid">
            <article class="team-card">
                <img src="<?php echo esc_attr($image('team_1_image', '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg')); ?>" alt="<?php echo esc_attr($ct('team_1_name', 'Ripal Vinchhi')); ?>" style="aspect-ratio: 4 / 3;">
                <div class="feature-copy">
                    <p class="eyebrow"><?php echo esc($ct('team_1_role', 'Design Director')); ?></p>
                    <h3><?php echo esc($ct('team_1_name', 'Ripal Vinchhi')); ?></h3>
                    <p><?php echo esc($ct('team_1_bio', 'Leads concept development, spatial strategy, and material direction.')); ?></p>
                </div>
            </article>
            <article class="team-card">
                <img src="<?php echo esc_attr($image('team_2_image', '/assets/Content/WhatsApp Image 2026-02-02 at 5.43.21 PM (1).jpeg')); ?>" alt="<?php echo esc_attr($ct('team_2_name', 'Mayank Vinchhi')); ?>" style="aspect-ratio: 4 / 3;">
                <div class="feature-copy">
                    <p class="eyebrow"><?php echo esc($ct('team_2_role', 'Execution Lead')); ?></p>
                    <h3><?php echo esc($ct('team_2_name', 'Mayank Vinchhi')); ?></h3>
                    <p><?php echo esc($ct('team_2_bio', 'Oversees site delivery, vendor coordination, and schedule integrity.')); ?></p>
                </div>
            </article>
        </div>
    </section>
</main>
<?php rd_page_end(); ?>
