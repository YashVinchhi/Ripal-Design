<?php
/**
 * Public Content Management Helpers
 *
 * Provides a centralized registry and database-backed read/write helpers
 * for all editable public-page text blocks.
 *
 * @package RipalDesign
 * @subpackage Content
 */

if (!isset($GLOBALS['__public_content_cache'])) {
    $GLOBALS['__public_content_cache'] = [];
}

if (!function_exists('public_content_registry')) {
    /**
     * Return editable content registry.
     *
     * @return array
     */
    function public_content_registry() {
        static $registry = null;
        if (is_array($registry)) {
            return $registry;
        }

        $registry = [
            'common_header' => [
                'title' => 'Shared Header',
                'preview_path' => 'public/index.php',
                'fields' => [
                    'brand_name' => ['label' => 'Brand Name', 'format' => 'plain', 'default' => 'Ripal Design'],
                    'brand_logo_image' => ['label' => 'Brand Logo Image', 'format' => 'image', 'default' => '/assets/Content/Logo.png'],
                    'favicon_image' => ['label' => 'Favicon Image', 'format' => 'image', 'default' => '/favicon.ico'],                    'menu_home' => ['label' => 'Menu: Home', 'format' => 'plain', 'default' => 'Home'],
                    'menu_services' => ['label' => 'Menu: Services', 'format' => 'plain', 'default' => 'Services'],
                    'menu_projects' => ['label' => 'Menu: Projects', 'format' => 'plain', 'default' => 'Projects'],
                    'menu_about' => ['label' => 'Menu: About', 'format' => 'plain', 'default' => 'About'],
                    'menu_contact' => ['label' => 'Menu: Contact', 'format' => 'plain', 'default' => 'Contact'],
                    'btn_login' => ['label' => 'Button: Login', 'format' => 'plain', 'default' => 'Login'],
                    'btn_signup' => ['label' => 'Button: Sign Up', 'format' => 'plain', 'default' => 'Sign Up'],
                    'btn_dashboard' => ['label' => 'Button: Dashboard', 'format' => 'plain', 'default' => 'Dashboard'],
                    'btn_logout' => ['label' => 'Button: Logout', 'format' => 'plain', 'default' => 'Logout'],
                    'dashboard_section_title' => ['label' => 'Dashboard Section Title', 'format' => 'plain', 'default' => 'Dashboard'],
                    'dashboard_link_home' => ['label' => 'Dashboard Link: Home', 'format' => 'plain', 'default' => 'Dashboard Home'],
                    'dashboard_link_project_details' => ['label' => 'Dashboard Link: Project Details', 'format' => 'plain', 'default' => 'Project Details'],
                    'dashboard_link_profile' => ['label' => 'Dashboard Link: Profile', 'format' => 'plain', 'default' => 'Profile Settings'],
                    'dashboard_link_reviews' => ['label' => 'Dashboard Link: Reviews', 'format' => 'plain', 'default' => 'Review Requests'],
                    'worker_section_title' => ['label' => 'Worker Section Title', 'format' => 'plain', 'default' => 'Worker Portal'],
                    'worker_link_dashboard' => ['label' => 'Worker Link: Dashboard', 'format' => 'plain', 'default' => 'Worker Dashboard'],
                    'worker_link_assigned_projects' => ['label' => 'Worker Link: Assigned Projects', 'format' => 'plain', 'default' => 'Assigned Projects'],
                    'worker_link_project_details' => ['label' => 'Worker Link: Project Details', 'format' => 'plain', 'default' => 'Project Details'],
                    'worker_link_ratings' => ['label' => 'Worker Link: Ratings', 'format' => 'plain', 'default' => 'My Ratings'],
                    'admin_section_title' => ['label' => 'Admin Section Title', 'format' => 'plain', 'default' => 'Administration'],
                    'admin_link_dashboard' => ['label' => 'Admin Link: Dashboard', 'format' => 'plain', 'default' => 'Admin Dashboard'],
                    'admin_link_project_portfolio' => ['label' => 'Admin Link: Project Portfolio', 'format' => 'plain', 'default' => 'Project Portfolio'],
                    'admin_link_user_controls' => ['label' => 'Admin Link: User Controls', 'format' => 'plain', 'default' => 'User Controls'],
                    'admin_link_leave_manager' => ['label' => 'Admin Link: Leave Manager', 'format' => 'plain', 'default' => 'Leave Manager'],
                    'admin_link_financial_gateway' => ['label' => 'Admin Link: Financial Gateway', 'format' => 'plain', 'default' => 'Financial Gateway'],
                    'admin_link_content_manager' => ['label' => 'Admin Link: Content Manager', 'format' => 'plain', 'default' => 'Content Manager'],
                ],
            ],
            'common_footer' => [
                'title' => 'Shared Footer',
                'preview_path' => 'public/index.php',
                'fields' => [
                    'cta_heading' => ['label' => 'CTA Heading', 'format' => 'plain', 'default' => 'Ready to build something Iconic?'],
                    'cta_description' => ['label' => 'CTA Description', 'format' => 'plain', 'default' => "Whether it's a private residence or a large-scale government infrastructure project, Ripal Design brings the expertise to make it happen."],
                    'cta_button' => ['label' => 'CTA Button Label', 'format' => 'plain', 'default' => 'Start Your Project'],
                    'contact_heading' => ['label' => 'Footer Contact Heading', 'format' => 'plain', 'default' => 'Contact Us'],
                    'address_html' => ['label' => 'Footer Address (HTML allowed)', 'format' => 'html', 'default' => 'Ripal Design Rajkot<br>538 Jasal Complex, Nanavati Chowk,<br>150ft Ring Road, Rajkot, Gujarat'],
                    'email' => ['label' => 'Footer Contact Email', 'format' => 'plain', 'default' => 'projects@ripaldesign.in'],
                    'copyright_brand' => ['label' => 'Copyright Brand Name', 'format' => 'plain', 'default' => 'Ripal Design'],
                    'copyright_suffix' => ['label' => 'Copyright Suffix', 'format' => 'plain', 'default' => 'All rights reserved.'],
                    'privacy_label' => ['label' => 'Footer Link: Privacy', 'format' => 'plain', 'default' => 'Privacy'],
                    'terms_label' => ['label' => 'Footer Link: Terms', 'format' => 'plain', 'default' => 'Terms'],
                ],
            ],
            'index' => [
                'title' => 'Home Page',
                'preview_path' => 'public/index.php',
                'fields' => [
                    'page_title' => ['label' => 'Browser Title', 'format' => 'plain', 'default' => 'Home | Ripal Design'],
                    'hero_established' => ['label' => 'Hero: Established Label', 'format' => 'plain', 'default' => 'Est. 2017'],
                    'hero_heading' => ['label' => 'Hero: Heading', 'format' => 'plain', 'default' => "The Architect's Vision"],
                    'hero_subheading' => ['label' => 'Hero: Subheading', 'format' => 'plain', 'default' => 'Precision in every measurement. Excellence in every build. Bridging the creative gap between design and reality.'],
                    'hero_hint' => ['label' => 'Hero: Hint Label', 'format' => 'plain', 'default' => 'Discovery'],
                    'story_heading_line' => ['label' => 'Story Heading First Line', 'format' => 'plain', 'default' => 'Duality in'],
                    'story_heading_highlight' => ['label' => 'Story Heading Highlight', 'format' => 'plain', 'default' => 'Execution'],
                    'story_kicker' => ['label' => 'Story Kicker', 'format' => 'plain', 'default' => 'The Ripal Approach'],
                    'story_intro' => ['label' => 'Story Intro', 'format' => 'plain', 'default' => 'Founded by two brothers - A Designer and A Builder, we bridge creative ambition with practical delivery.'],
                    'story_body' => ['label' => 'Story Body', 'format' => 'plain', 'default' => 'Our combined experience across municipal, institutional, and private works ensures designs that stand up to real-world constraints while remaining beautiful and timeless. We eliminate the gap between concept and creation by controlling the measure of every detail.'],
                    'fallback_project_name' => ['label' => 'Fallback Project Name', 'format' => 'plain', 'default' => 'Project'],
                    'carousel_image_1' => ['label' => 'Carousel Image 1', 'format' => 'image', 'default' => '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg'],
                    'carousel_image_2' => ['label' => 'Carousel Image 2', 'format' => 'image', 'default' => '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.51 PM.jpeg'],
                    'carousel_image_3' => ['label' => 'Carousel Image 3', 'format' => 'image', 'default' => '/assets/Content/WhatsApp Image 2026-02-02 at 5.43.21 PM (1).jpeg'],
                    'carousel_image_4' => ['label' => 'Carousel Image 4', 'format' => 'image', 'default' => '/assets/Content/WhatsApp Image 2026-02-02 at 5.51.43 PM.jpeg'],
                    'carousel_alt_1' => ['label' => 'Carousel Image 1 Alt', 'format' => 'plain', 'default' => 'Project image 1'],
                    'carousel_alt_2' => ['label' => 'Carousel Image 2 Alt', 'format' => 'plain', 'default' => 'Project image 2'],
                    'carousel_alt_3' => ['label' => 'Carousel Image 3 Alt', 'format' => 'plain', 'default' => 'Project image 3'],
                    'carousel_alt_4' => ['label' => 'Carousel Image 4 Alt', 'format' => 'plain', 'default' => 'Project image 4'],
                    'project_1_description' => ['label' => 'Project 1 Description', 'format' => 'plain', 'default' => 'A masterpiece of modern residential architecture in the heart of Rajkot, redefining spatial excellence through minimalist precision.'],
                    'project_2_description' => ['label' => 'Project 2 Description', 'format' => 'plain', 'default' => 'A landmark in Jam Khambhalia, bridging the gap between Tradition and contemporary living with breathable structure.'],
                    'project_3_description' => ['label' => 'Project 3 Description', 'format' => 'plain', 'default' => "State-of-the-art Multi-Institutional System integrated into Rajkot's burgeoning urban landscape."],
                    'project_4_description' => ['label' => 'Project 4 Description', 'format' => 'plain', 'default' => "Industrial refinement meeting contemporary aesthetics in the heart of India's ceramic capital."],
                    'featured_image_1' => ['label' => 'Featured Image 1', 'format' => 'image', 'default' => '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg'],
                    'featured_image_2' => ['label' => 'Featured Image 2', 'format' => 'image', 'default' => '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.51 PM.jpeg'],
                    'featured_image_3' => ['label' => 'Featured Image 3', 'format' => 'image', 'default' => '/assets/Content/WhatsApp Image 2026-02-02 at 5.43.21 PM (1).jpeg'],
                    'featured_image_4' => ['label' => 'Featured Image 4', 'format' => 'image', 'default' => '/assets/Content/WhatsApp Image 2026-02-02 at 5.51.43 PM.jpeg'],
                    'featured_image_alt_1' => ['label' => 'Featured Image 1 Alt', 'format' => 'plain', 'default' => 'Featured project image 1'],
                    'featured_image_alt_2' => ['label' => 'Featured Image 2 Alt', 'format' => 'plain', 'default' => 'Featured project image 2'],
                    'featured_image_alt_3' => ['label' => 'Featured Image 3 Alt', 'format' => 'plain', 'default' => 'Featured project image 3'],
                    'featured_image_alt_4' => ['label' => 'Featured Image 4 Alt', 'format' => 'plain', 'default' => 'Featured project image 4'],
                    'project_link_label' => ['label' => 'Project Link Label', 'format' => 'plain', 'default' => 'View Project'],
                    'testimonials_heading' => ['label' => 'Testimonials Heading', 'format' => 'plain', 'default' => 'Client Perspectives'],
                    'testimonials_subheading' => ['label' => 'Testimonials Subheading', 'format' => 'plain', 'default' => 'Voices from our collaborative journey'],
                    'testimonial_1_quote' => ['label' => 'Testimonial 1 Quote', 'format' => 'plain', 'default' => 'The surgical precision of their design language transformed our site into a masterpiece of modern architecture.'],
                    'testimonial_1_name' => ['label' => 'Testimonial 1 Name', 'format' => 'plain', 'default' => 'Amitbhai Patel'],
                    'testimonial_1_role' => ['label' => 'Testimonial 1 Role', 'format' => 'plain', 'default' => 'Chairman, Rajkot Realty Group'],
                    'testimonial_image_1' => ['label' => 'Testimonial Image 1', 'format' => 'image', 'default' => '/assets/Content/WhatsApp Image 2026-02-02 at 5.51.43 PM (1).jpeg'],
                    'testimonial_image_alt_1' => ['label' => 'Testimonial Image 1 Alt', 'format' => 'plain', 'default' => 'Client project 1'],
                    'testimonial_2_quote' => ['label' => 'Testimonial 2 Quote', 'format' => 'plain', 'default' => 'They pushed the boundaries of what we thought was possible, creating a space that feels both Intimate and Grand.'],
                    'testimonial_2_name' => ['label' => 'Testimonial 2 Name', 'format' => 'plain', 'default' => 'Anilbhai Sharma'],
                    'testimonial_2_role' => ['label' => 'Testimonial 2 Role', 'format' => 'plain', 'default' => 'Founder, Khambhalia Arts'],
                    'testimonial_image_2' => ['label' => 'Testimonial Image 2', 'format' => 'image', 'default' => '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg'],
                    'testimonial_image_alt_2' => ['label' => 'Testimonial Image 2 Alt', 'format' => 'plain', 'default' => 'Client project 2'],
                    'testimonial_3_quote' => ['label' => 'Testimonial 3 Quote', 'format' => 'plain', 'default' => 'Deeply committed to sustainability without compromising on aesthetic excellence. Truly leaders in the new era.'],
                    'testimonial_3_name' => ['label' => 'Testimonial 3 Name', 'format' => 'plain', 'default' => 'Sureshbhai'],
                    'testimonial_3_role' => ['label' => 'Testimonial 3 Role', 'format' => 'plain', 'default' => 'Director, Regional Urban Planning'],
                    'testimonial_image_3' => ['label' => 'Testimonial Image 3', 'format' => 'image', 'default' => '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.51 PM.jpeg'],
                    'testimonial_image_alt_3' => ['label' => 'Testimonial Image 3 Alt', 'format' => 'plain', 'default' => 'Client project 3'],
                ],
            ],
            'about_us' => [
                'title' => 'About Us Page',
                'preview_path' => 'public/about_us.php',
                'fields' => [
                    'page_title' => ['label' => 'Browser Title', 'format' => 'plain', 'default' => 'About Us | Ripal Design'],
                    'hero_established' => ['label' => 'Hero: Established Label', 'format' => 'plain', 'default' => 'Est. 2017'],
                    'hero_heading' => ['label' => 'Hero: Heading', 'format' => 'plain', 'default' => "The Architect's Vision"],
                    'hero_subheading' => ['label' => 'Hero: Subheading', 'format' => 'plain', 'default' => 'Precision in every measurement. Excellence in every build. Bridging the creative gap between design and reality.'],
                    'hero_hint' => ['label' => 'Hero: Hint Label', 'format' => 'plain', 'default' => 'Discovery'],
                    'story_heading_line' => ['label' => 'Story Heading First Line', 'format' => 'plain', 'default' => 'Duality in'],
                    'story_heading_highlight' => ['label' => 'Story Heading Highlight', 'format' => 'plain', 'default' => 'Execution'],
                    'story_kicker' => ['label' => 'Story Kicker', 'format' => 'plain', 'default' => 'The Ripal Approach'],
                    'story_intro' => ['label' => 'Story Intro', 'format' => 'plain', 'default' => 'Founded by two brothers - a designer and a builder - we bridge creative ambition with practical delivery.'],
                    'story_body' => ['label' => 'Story Body', 'format' => 'plain', 'default' => 'Our combined experience across municipal, institutional, and private works ensures designs that stand up to real-world constraints while remaining beautiful and timeless. We eliminate the gap between concept and creation by controlling the measure of every detail.'],
                    'timeline_kicker' => ['label' => 'Timeline Kicker', 'format' => 'plain', 'default' => 'Milestones'],
                    'timeline_heading' => ['label' => 'Timeline Heading', 'format' => 'plain', 'default' => 'The Measure of Success'],
                    'timeline_logo_image' => ['label' => 'Timeline Logo Image', 'format' => 'image', 'default' => '/assets/Content/Logo.png'],
                    'timeline_logo_alt' => ['label' => 'Timeline Logo Alt', 'format' => 'plain', 'default' => 'Ripal Design Logo'],
                    'milestone_1_year' => ['label' => 'Milestone 1 Year', 'format' => 'plain', 'default' => '2017'],
                    'milestone_1_label' => ['label' => 'Milestone 1 Label', 'format' => 'plain', 'default' => 'Inception'],
                    'milestone_1_description' => ['label' => 'Milestone 1 Description', 'format' => 'plain', 'default' => 'Firm established with a design-build model, bridging the gap between concept and execution.'],
                    'milestone_2_year' => ['label' => 'Milestone 2 Year', 'format' => 'plain', 'default' => '2021'],
                    'milestone_2_label' => ['label' => 'Milestone 2 Label', 'format' => 'plain', 'default' => 'Scale'],
                    'milestone_2_description' => ['label' => 'Milestone 2 Description', 'format' => 'plain', 'default' => 'Expanded into municipal projects and grew the core team to handle larger scale operations.'],
                    'milestone_3_year' => ['label' => 'Milestone 3 Year', 'format' => 'plain', 'default' => '2026'],
                    'milestone_3_label' => ['label' => 'Milestone 3 Label', 'format' => 'plain', 'default' => 'Future'],
                    'milestone_3_description' => ['label' => 'Milestone 3 Description', 'format' => 'plain', 'default' => 'Aiming for global consultancy status and integrating sustainable tech in every build.'],
                    'stat_1_value' => ['label' => 'Stat 1 Value', 'format' => 'plain', 'default' => '50+'],
                    'stat_1_label' => ['label' => 'Stat 1 Label', 'format' => 'plain', 'default' => 'Projects Completed'],
                    'stat_2_value' => ['label' => 'Stat 2 Value', 'format' => 'plain', 'default' => '09'],
                    'stat_2_label' => ['label' => 'Stat 2 Label', 'format' => 'plain', 'default' => 'Years Experience'],
                    'stat_3_value' => ['label' => 'Stat 3 Value', 'format' => 'plain', 'default' => '100%'],
                    'stat_3_label' => ['label' => 'Stat 3 Label', 'format' => 'plain', 'default' => 'Precision Rate'],
                    'cta_heading' => ['label' => 'Bottom CTA Heading', 'format' => 'plain', 'default' => 'Build the Extraordinary'],
                    'cta_subheading' => ['label' => 'Bottom CTA Subheading', 'format' => 'plain', 'default' => 'Ready to start your next project with Ripal Design?'],
                    'cta_button' => ['label' => 'Bottom CTA Button', 'format' => 'plain', 'default' => 'Contact Our Studio'],
                ],
            ],
            'services' => [
                'title' => 'Services Page',
                'preview_path' => 'public/services.php',
                'fields' => [
                    'page_title' => ['label' => 'Browser Title', 'format' => 'plain', 'default' => 'Services | Ripal Design'],
                    'hero_established' => ['label' => 'Hero: Established Label', 'format' => 'plain', 'default' => 'Est. 2017'],
                    'hero_heading' => ['label' => 'Hero: Heading', 'format' => 'plain', 'default' => "The Architect's Vision"],
                    'hero_subheading' => ['label' => 'Hero: Subheading', 'format' => 'plain', 'default' => 'Precision in every measurement. Excellence in every build. Bridging the creative gap between design and reality.'],
                    'hero_hint' => ['label' => 'Hero: Hint Label', 'format' => 'plain', 'default' => 'Discovery'],
                    'section_kicker' => ['label' => 'Section Kicker', 'format' => 'plain', 'default' => 'Our Expertise'],
                    'section_heading_line_1' => ['label' => 'Section Heading Line 1', 'format' => 'plain', 'default' => 'Crafting Spaces'],
                    'section_heading_line_2' => ['label' => 'Section Heading Line 2', 'format' => 'plain', 'default' => 'With Purpose.'],
                    'service_image_1' => ['label' => 'Service Image 1', 'format' => 'image', 'default' => '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg'],
                    'service_image_2' => ['label' => 'Service Image 2', 'format' => 'image', 'default' => '/assets/Content/WhatsApp Image 2026-02-02 at 5.43.21 PM (1).jpeg'],
                    'service_image_3' => ['label' => 'Service Image 3', 'format' => 'image', 'default' => '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg'],
                    'service_image_4' => ['label' => 'Service Image 4', 'format' => 'image', 'default' => '/assets/Content/WhatsApp Image 2026-02-02 at 5.51.43 PM.jpeg'],
                    'service_1_title' => ['label' => 'Service 1 Title', 'format' => 'plain', 'default' => 'Architectural Planning'],
                    'service_1_description' => ['label' => 'Service 1 Description', 'format' => 'plain', 'default' => 'Comprehensive master planning and structural design that balances aesthetics with functionality.'],
                    'service_2_title' => ['label' => 'Service 2 Title', 'format' => 'plain', 'default' => 'Interior Design'],
                    'service_2_description' => ['label' => 'Service 2 Description', 'format' => 'plain', 'default' => 'Curating internal environments that evoke emotion through texture, light, and material.'],
                    'service_3_title' => ['label' => 'Service 3 Title', 'format' => 'plain', 'default' => 'Landscape Architecture'],
                    'service_3_description' => ['label' => 'Service 3 Description', 'format' => 'plain', 'default' => 'Harmonizing built structures with the natural environment for sustainable outdoor living.'],
                    'service_4_title' => ['label' => 'Service 4 Title', 'format' => 'plain', 'default' => 'Project Management'],
                    'service_4_description' => ['label' => 'Service 4 Description', 'format' => 'plain', 'default' => 'End-to-end oversight ensuring precision in execution and adherence to timelines.'],
                    'badge_brand' => ['label' => 'Badge Brand', 'format' => 'plain', 'default' => 'Ripal Design'],
                    'badge_label' => ['label' => 'Badge Label', 'format' => 'plain', 'default' => '2026 Collection'],
                    'hero_image_src' => ['label' => 'Hero Display Image', 'format' => 'image', 'default' => '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg'],
                    'hero_image_alt' => ['label' => 'Hero Image Alt', 'format' => 'plain', 'default' => 'Architectural service image'],
                    'dynamic_image_alt' => ['label' => 'Dynamic Service Image Alt', 'format' => 'plain', 'default' => 'Service image'],
                ],
            ],
            'project_view' => [
                'title' => 'Project View Page',
                'preview_path' => 'public/project_view.php',
                'fields' => [
                    'page_title' => ['label' => 'Browser Title', 'format' => 'plain', 'default' => 'Products | Ripal Design'],
                    'section_kicker' => ['label' => 'Section Kicker', 'format' => 'plain', 'default' => 'Exquisite Materials'],
                    'section_heading' => ['label' => 'Section Heading', 'format' => 'plain', 'default' => 'Curated Collection'],
                    'card_1_image' => ['label' => 'Card 1 Image', 'format' => 'image', 'default' => '/assets/Content/WhatsApp Image 2026-02-02 at 5.02.50 PM.jpeg'],
                    'card_1_title' => ['label' => 'Card 1 Title', 'format' => 'plain', 'default' => 'Italian Marble Series'],
                    'card_1_subtitle' => ['label' => 'Card 1 Subtitle', 'format' => 'plain', 'default' => 'Flooring & Cladding'],
                    'card_1_image_alt' => ['label' => 'Card 1 Image Alt', 'format' => 'plain', 'default' => 'Product image 1'],
                    'card_2_image' => ['label' => 'Card 2 Image', 'format' => 'image', 'default' => '/assets/Content/WhatsApp Image 2026-02-02 at 5.43.21 PM (1).jpeg'],
                    'card_2_title' => ['label' => 'Card 2 Title', 'format' => 'plain', 'default' => 'Lumina Pendant'],
                    'card_2_subtitle' => ['label' => 'Card 2 Subtitle', 'format' => 'plain', 'default' => 'Lighting'],
                    'card_2_image_alt' => ['label' => 'Card 2 Image Alt', 'format' => 'plain', 'default' => 'Product image 2'],
                    'card_3_image' => ['label' => 'Card 3 Image', 'format' => 'image', 'default' => '/assets/Content/WhatsApp Image 2026-02-02 at 5.51.43 PM.jpeg'],
                    'card_3_title' => ['label' => 'Card 3 Title', 'format' => 'plain', 'default' => 'Oak Wood Panels'],
                    'card_3_subtitle' => ['label' => 'Card 3 Subtitle', 'format' => 'plain', 'default' => 'Interiors'],
                    'card_3_image_alt' => ['label' => 'Card 3 Image Alt', 'format' => 'plain', 'default' => 'Product image 3'],
                    'catalog_button' => ['label' => 'Catalog CTA Label', 'format' => 'plain', 'default' => 'Request Catalog'],
                ],
            ],
            'contact_us' => [
                'title' => 'Contact Us Page',
                'preview_path' => 'public/contact_us.php',
                'fields' => [
                    'page_title' => ['label' => 'Browser Title', 'format' => 'plain', 'default' => 'Contact Us | Ripal Design'],
                    'left_kicker' => ['label' => 'Left Kicker', 'format' => 'plain', 'default' => 'Get in touch'],
                    'left_heading_line_1' => ['label' => 'Left Heading Line 1', 'format' => 'plain', 'default' => "Let's Discuss"],
                    'left_heading_line_2' => ['label' => 'Left Heading Line 2', 'format' => 'plain', 'default' => 'Your Vision.'],
                    'address_heading' => ['label' => 'Address Heading', 'format' => 'plain', 'default' => 'Ripal Design Rajkot'],
                    'address_html' => ['label' => 'Address (HTML allowed)', 'format' => 'html', 'default' => '538 Jasal Complex,<br>Nanavati Chowk,<br>150ft Ring Road,<br>Rajkot, Gujarat, India'],
                    'contact_heading' => ['label' => 'Contact Heading', 'format' => 'plain', 'default' => 'Contact'],
                    'contact_phone' => ['label' => 'Phone Number', 'format' => 'plain', 'default' => '+91 94267 89012'],
                    'contact_email' => ['label' => 'Contact Email', 'format' => 'plain', 'default' => 'projects@ripaldesign.in'],
                    'social_heading' => ['label' => 'Social Heading', 'format' => 'plain', 'default' => 'Social'],
                    'social_instagram_label' => ['label' => 'Social Label: Instagram', 'format' => 'plain', 'default' => 'Instagram'],
                    'social_linkedin_label' => ['label' => 'Social Label: LinkedIn', 'format' => 'plain', 'default' => 'LinkedIn'],
                    'social_behance_label' => ['label' => 'Social Label: Behance', 'format' => 'plain', 'default' => 'Behance'],
                    'form_heading' => ['label' => 'Form Heading', 'format' => 'plain', 'default' => 'Send us a message'],
                    'label_first_name' => ['label' => 'Label: First Name', 'format' => 'plain', 'default' => 'First Name'],
                    'label_last_name' => ['label' => 'Label: Last Name', 'format' => 'plain', 'default' => 'Last Name'],
                    'label_email' => ['label' => 'Label: Email', 'format' => 'plain', 'default' => 'Email Address'],
                    'label_subject' => ['label' => 'Label: Subject', 'format' => 'plain', 'default' => 'Subject'],
                    'label_message' => ['label' => 'Label: Message', 'format' => 'plain', 'default' => 'Message'],
                    'subject_default' => ['label' => 'Subject Option: Default', 'format' => 'plain', 'default' => 'Select Inquiry Type'],
                    'subject_residential' => ['label' => 'Subject Option: Residential', 'format' => 'plain', 'default' => 'Residential Project'],
                    'subject_commercial' => ['label' => 'Subject Option: Commercial', 'format' => 'plain', 'default' => 'Commercial Project'],
                    'subject_consultation' => ['label' => 'Subject Option: Consultation', 'format' => 'plain', 'default' => 'Design Consultation'],
                    'subject_other' => ['label' => 'Subject Option: Other', 'format' => 'plain', 'default' => 'Other'],
                    'submit_button' => ['label' => 'Submit Button', 'format' => 'plain', 'default' => 'Send Message'],
                    'success_title' => ['label' => 'Success Modal Title', 'format' => 'plain', 'default' => 'Message Sent'],
                    'success_message' => ['label' => 'Success Modal Message', 'format' => 'plain', 'default' => 'Thank you for reaching out. Our design team will review your inquiry and contact you shortly.'],
                    'success_button' => ['label' => 'Success Modal Button', 'format' => 'plain', 'default' => 'Return Home'],
                    'error_message' => ['label' => 'Submission Error Message', 'format' => 'plain', 'default' => 'Failed to send message. Please try again.'],
                    'db_unavailable' => ['label' => 'Database Unavailable Message', 'format' => 'plain', 'default' => 'Database connection unavailable.'],
                ],
            ],
            'login' => [
                'title' => 'Login Page',
                'preview_path' => 'public/login.php',
                'fields' => [
                    'page_title' => ['label' => 'Browser Title', 'format' => 'plain', 'default' => 'Login - Ripal Design'],
                    'form_title' => ['label' => 'Form Title', 'format' => 'plain', 'default' => 'Login'],
                    'form_subtitle' => ['label' => 'Form Subtitle', 'format' => 'plain', 'default' => 'Welcome back. Sign in to continue your project journey.'],
                    'label_email' => ['label' => 'Label: Email Address', 'format' => 'plain', 'default' => 'Email Address'],
                    'placeholder_email' => ['label' => 'Placeholder: Email Address', 'format' => 'plain', 'default' => 'youremail@example.com'],
                    'label_password' => ['label' => 'Label: Password', 'format' => 'plain', 'default' => 'Password'],
                    'placeholder_password' => ['label' => 'Placeholder: Password', 'format' => 'plain', 'default' => 'Enter your password'],
                    'label_remember' => ['label' => 'Label: Remember Me', 'format' => 'plain', 'default' => 'Remember me'],
                    'link_forgot_password' => ['label' => 'Link: Forgot Password', 'format' => 'plain', 'default' => 'Forgot password?'],
                    'button_login' => ['label' => 'Button: Login', 'format' => 'plain', 'default' => 'Login'],
                    'switch_prefix' => ['label' => 'Switch Prefix', 'format' => 'plain', 'default' => "Don't have an account?"],
                    'switch_link' => ['label' => 'Switch Link Label', 'format' => 'plain', 'default' => 'Sign up'],
                    'toggle_aria' => ['label' => 'Password Toggle Aria Label', 'format' => 'plain', 'default' => 'Toggle password visibility'],
                    'toggle_show_alt' => ['label' => 'Password Toggle Show Label', 'format' => 'plain', 'default' => 'Show password'],
                    'toggle_hide_alt' => ['label' => 'Password Toggle Hide Label', 'format' => 'plain', 'default' => 'Hide password'],
                ],
            ],
            'signup' => [
                'title' => 'Signup Page',
                'preview_path' => 'public/signup.php',
                'fields' => [
                    'page_title' => ['label' => 'Browser Title', 'format' => 'plain', 'default' => 'Signup - Ripal Design'],
                    'form_title' => ['label' => 'Form Title', 'format' => 'plain', 'default' => 'Create Account'],
                    'form_subtitle' => ['label' => 'Form Subtitle', 'format' => 'plain', 'default' => 'Start your design experience with a curated account setup.'],
                    'label_first_name' => ['label' => 'Label: First Name', 'format' => 'plain', 'default' => 'First Name'],
                    'placeholder_first_name' => ['label' => 'Placeholder: First Name', 'format' => 'plain', 'default' => 'Enter your first name'],
                    'label_last_name' => ['label' => 'Label: Last Name', 'format' => 'plain', 'default' => 'Last Name'],
                    'placeholder_last_name' => ['label' => 'Placeholder: Last Name', 'format' => 'plain', 'default' => 'Enter your last name'],
                    'label_email' => ['label' => 'Label: Email Address', 'format' => 'plain', 'default' => 'Email Address'],
                    'placeholder_email' => ['label' => 'Placeholder: Email Address', 'format' => 'plain', 'default' => 'youremail@example.com'],
                    'label_password' => ['label' => 'Label: Password', 'format' => 'plain', 'default' => 'Password'],
                    'placeholder_password' => ['label' => 'Placeholder: Password', 'format' => 'plain', 'default' => 'Enter your password'],
                    'password_help' => ['label' => 'Password Help Text', 'format' => 'plain', 'default' => 'Use at least 8 characters and 1 number.'],
                    'label_confirm_password' => ['label' => 'Label: Confirm Password', 'format' => 'plain', 'default' => 'Confirm Password'],
                    'placeholder_confirm_password' => ['label' => 'Placeholder: Confirm Password', 'format' => 'plain', 'default' => 'Confirm your password'],
                    'label_phone' => ['label' => 'Label: Phone Number', 'format' => 'plain', 'default' => 'Phone Number'],
                    'placeholder_phone' => ['label' => 'Placeholder: Phone Number', 'format' => 'plain', 'default' => 'Enter your phone number'],
                    'label_terms' => ['label' => 'Label: Terms Checkbox', 'format' => 'plain', 'default' => 'I accept terms and conditions'],
                    'button_signup' => ['label' => 'Button: Create Account', 'format' => 'plain', 'default' => 'Create Account'],
                    'switch_prefix' => ['label' => 'Switch Prefix', 'format' => 'plain', 'default' => 'Already have an account?'],
                    'switch_link' => ['label' => 'Switch Link Label', 'format' => 'plain', 'default' => 'Login'],
                    'toggle_aria' => ['label' => 'Password Toggle Aria Label', 'format' => 'plain', 'default' => 'Toggle password visibility'],
                    'toggle_show_alt' => ['label' => 'Password Toggle Show Label', 'format' => 'plain', 'default' => 'Show password'],
                    'toggle_hide_alt' => ['label' => 'Password Toggle Hide Label', 'format' => 'plain', 'default' => 'Hide password'],
                ],
            ],
            'forgot' => [
                'title' => 'Forgot Password Page',
                'preview_path' => 'public/forgot.php',
                'fields' => [
                    'page_title' => ['label' => 'Browser Title', 'format' => 'plain', 'default' => 'Forgot Password - Ripal Design'],
                    'form_title' => ['label' => 'Form Title', 'format' => 'plain', 'default' => 'Forgot Password'],
                    'form_note' => ['label' => 'Form Note', 'format' => 'plain', 'default' => 'Enter your account email and we will send a secure reset link.'],
                    'label_email' => ['label' => 'Label: Email Address', 'format' => 'plain', 'default' => 'Email Address'],
                    'placeholder_email' => ['label' => 'Placeholder: Email Address', 'format' => 'plain', 'default' => 'youremail@example.com'],
                    'button_send_link' => ['label' => 'Button: Send Reset Link', 'format' => 'plain', 'default' => 'Send Reset Link'],
                    'link_back_to_login' => ['label' => 'Link: Back to Login', 'format' => 'plain', 'default' => 'Back to login'],
                ],
            ],
            'reset_password' => [
                'title' => 'Reset Password Page',
                'preview_path' => 'public/reset_password.php',
                'fields' => [
                    'page_title' => ['label' => 'Browser Title', 'format' => 'plain', 'default' => 'Reset Password | Ripal Design'],
                    'heading' => ['label' => 'Main Heading', 'format' => 'plain', 'default' => 'Reset Password'],
                    'subtitle' => ['label' => 'Subtitle', 'format' => 'plain', 'default' => 'Create a strong new password for your account.'],
                    'status_db_unavailable' => ['label' => 'Status: DB Unavailable', 'format' => 'plain', 'default' => 'Database connection unavailable. Please try later.'],
                    'status_invalid_token' => ['label' => 'Status: Invalid Token', 'format' => 'plain', 'default' => 'Invalid reset token.'],
                    'status_token_not_found' => ['label' => 'Status: Token Not Found', 'format' => 'plain', 'default' => 'Token not found.'],
                    'status_token_expired' => ['label' => 'Status: Token Expired', 'format' => 'plain', 'default' => 'Token has expired.'],
                    'link_after_success' => ['label' => 'Link after Success', 'format' => 'plain', 'default' => 'Go to Login Page'],
                    'label_new_password' => ['label' => 'Label: New Password', 'format' => 'plain', 'default' => 'New Password:'],
                    'placeholder_new_password' => ['label' => 'Placeholder: New Password', 'format' => 'plain', 'default' => 'Enter your new password'],
                    'button_reset' => ['label' => 'Button: Reset Password', 'format' => 'plain', 'default' => 'Reset Password'],
                    'toggle_aria' => ['label' => 'Password Toggle Aria Label', 'format' => 'plain', 'default' => 'Toggle password visibility'],
                    'toggle_show_alt' => ['label' => 'Password Toggle Show Label', 'format' => 'plain', 'default' => 'Show password'],
                    'toggle_hide_alt' => ['label' => 'Password Toggle Hide Label', 'format' => 'plain', 'default' => 'Hide password'],
                ],
            ],
            'login_register' => [
                'title' => 'Auth Processor: Login/Register',
                'preview_path' => '',
                'fields' => [
                    'db_unavailable' => ['label' => 'Database Unavailable Message', 'format' => 'plain', 'default' => 'Database connection unavailable. Please try later.'],
                    'signup_required_fields' => ['label' => 'Signup: Required Fields Message', 'format' => 'plain', 'default' => 'Please fill all required fields.'],
                    'signup_invalid_email' => ['label' => 'Signup: Invalid Email Message', 'format' => 'plain', 'default' => 'Please enter a valid email address.'],
                    'signup_password_mismatch' => ['label' => 'Signup: Password Mismatch Message', 'format' => 'plain', 'default' => 'Password and confirm password do not match.'],
                    'signup_email_exists' => ['label' => 'Signup: Existing Email Message', 'format' => 'plain', 'default' => 'Email already exists. Please use a different email.'],
                    'signup_success' => ['label' => 'Signup: Success Flash Message', 'format' => 'plain', 'default' => 'Account created successfully.'],
                    'signup_failed' => ['label' => 'Signup: Failure Message', 'format' => 'plain', 'default' => 'Failed to create account. Please try again.'],
                    'signup_welcome_from_name' => ['label' => 'Welcome Mail: From Name', 'format' => 'plain', 'default' => 'Ripal Design'],
                    'signup_welcome_subject' => ['label' => 'Welcome Mail: Subject', 'format' => 'plain', 'default' => 'Registration Successful - Ripal Design'],
                    'signup_welcome_html' => ['label' => 'Welcome Mail: HTML Body (use {{first_name}})', 'format' => 'html', 'default' => '<h3>Registration Successful</h3><p>Hi {{first_name}},</p><p>Your account was created successfully. You can now log in and start using Ripal Design.</p>'],
                    'signup_welcome_alt' => ['label' => 'Welcome Mail: Text Body (use {{first_name}} and {{login_url}})', 'format' => 'plain', 'default' => 'Hi {{first_name}}, your account was created successfully. Login at {{login_url}}'],
                    'login_missing_credentials' => ['label' => 'Login: Missing Credentials Message', 'format' => 'plain', 'default' => 'Please enter email and password.'],
                    'login_inactive_account' => ['label' => 'Login: Inactive Account Message', 'format' => 'plain', 'default' => 'Your account is not active. Please contact admin.'],
                    'login_invalid_credentials' => ['label' => 'Login: Invalid Credentials Message', 'format' => 'plain', 'default' => 'Invalid email or password.'],
                ],
            ],
            'send_reset_password' => [
                'title' => 'Auth Processor: Send Reset Link',
                'preview_path' => '',
                'fields' => [
                    'method_not_allowed' => ['label' => 'Method Not Allowed Message', 'format' => 'plain', 'default' => 'Method not allowed.'],
                    'db_unavailable' => ['label' => 'Database Unavailable Message', 'format' => 'plain', 'default' => 'Database connection unavailable. Please try later.'],
                    'email_required' => ['label' => 'Email Required Message', 'format' => 'plain', 'default' => 'Email is required.'],
                    'invalid_email' => ['label' => 'Invalid Email Message', 'format' => 'plain', 'default' => 'Please enter a valid email address.'],
                    'mail_from_name' => ['label' => 'Reset Mail: From Name', 'format' => 'plain', 'default' => 'Ripal Design'],
                    'mail_subject' => ['label' => 'Reset Mail: Subject', 'format' => 'plain', 'default' => 'Password Reset Request'],
                    'mail_body_html' => ['label' => 'Reset Mail: HTML Body (use {{reset_link}})', 'format' => 'html', 'default' => 'Click <a href="{{reset_link}}">here</a> to reset your password. This link will expire in 30 minutes.'],
                    'flash_sent_success' => ['label' => 'Success Flash Message', 'format' => 'plain', 'default' => 'Reset link sent. Please check your email.'],
                    'flash_sent_failed' => ['label' => 'Failure Flash Message', 'format' => 'plain', 'default' => 'Failed to send reset link.'],
                    'email_not_found' => ['label' => 'Email Not Found Message', 'format' => 'plain', 'default' => 'Email not found.'],
                ],
            ],
            'update_password' => [
                'title' => 'Auth Processor: Update Password',
                'preview_path' => '',
                'fields' => [
                    'invalid_token' => ['label' => 'Invalid Token Message', 'format' => 'plain', 'default' => 'Invalid reset token.'],
                    'db_unavailable' => ['label' => 'Database Unavailable Message', 'format' => 'plain', 'default' => 'Database connection unavailable. Please try later.'],
                    'token_not_found' => ['label' => 'Token Not Found Message', 'format' => 'plain', 'default' => 'Token not found.'],
                    'token_expired' => ['label' => 'Token Expired Message', 'format' => 'plain', 'default' => 'Token has expired.'],
                    'password_required' => ['label' => 'Password Required Message', 'format' => 'plain', 'default' => 'Password is required.'],
                    'password_min_length' => ['label' => 'Password Min Length Message', 'format' => 'plain', 'default' => 'Password must be at least 8 characters.'],
                    'update_failed' => ['label' => 'Update Failed Message', 'format' => 'plain', 'default' => 'Unable to update password. Please try again.'],
                    'update_success' => ['label' => 'Update Success Message', 'format' => 'plain', 'default' => 'Password updated successfully. You can now login.'],
                ],
            ],
            'mailer' => [
                'title' => 'Mailer Defaults',
                'preview_path' => '',
                'fields' => [
                    'reset_subject' => ['label' => 'Reset Mail Subject', 'format' => 'plain', 'default' => 'Password Reset Request'],
                    'reset_from_name' => ['label' => 'Reset Mail From Name', 'format' => 'plain', 'default' => 'Reset Password'],
                    'reset_body_html' => ['label' => 'Reset Mail HTML Body (use {{reset_link}})', 'format' => 'html', 'default' => '<h3>Password reset request</h3><p>Click link below to reset your password</p><a href="{{reset_link}}">here</a>'],
                    'reset_body_text' => ['label' => 'Reset Mail Text Body (use {{reset_link}})', 'format' => 'plain', 'default' => 'Reset link: {{reset_link}}'],
                ],
            ],
            'debug_session' => [
                'title' => 'Debug Session Page',
                'preview_path' => 'public/debug_session.php',
                'fields' => [
                    'not_found' => ['label' => 'Not Found Message', 'format' => 'plain', 'default' => 'Not Found'],
                    'page_title' => ['label' => 'Page Title', 'format' => 'plain', 'default' => 'Debug Session'],
                    'heading' => ['label' => 'Main Heading', 'format' => 'plain', 'default' => 'Development Debug Session'],
                    'label_session_user_id' => ['label' => 'Label: Session User ID', 'format' => 'plain', 'default' => 'Session user id:'],
                    'label_session_username' => ['label' => 'Label: Session Username', 'format' => 'plain', 'default' => 'Session username:'],
                    'label_session_role' => ['label' => 'Label: Session Role', 'format' => 'plain', 'default' => 'Session role:'],
                    'db_section_heading' => ['label' => 'DB Section Heading', 'format' => 'plain', 'default' => 'Database user'],
                    'db_label_id' => ['label' => 'DB Label: ID', 'format' => 'plain', 'default' => 'id:'],
                    'db_label_username' => ['label' => 'DB Label: Username', 'format' => 'plain', 'default' => 'username:'],
                    'db_label_role' => ['label' => 'DB Label: Role', 'format' => 'plain', 'default' => 'role:'],
                    'db_unavailable' => ['label' => 'DB Unavailable Message', 'format' => 'plain', 'default' => 'Database user details unavailable.'],
                ],
            ],
            'error_404' => [
                'title' => '404 Page',
                'preview_path' => 'public/404.php',
                'fields' => [
                    'page_title' => ['label' => 'Browser Title', 'format' => 'plain', 'default' => 'Lost in Space | Ripal Design'],
                    'heading' => ['label' => 'Heading', 'format' => 'plain', 'default' => 'Structure Not Found'],
                    'message' => ['label' => 'Message', 'format' => 'plain', 'default' => "The architectural blueprint you're looking for seems to have been misplaced or never existed."],
                    'button_home' => ['label' => 'Button: Back to Home', 'format' => 'plain', 'default' => 'Back to Home'],
                    'button_back' => ['label' => 'Button: Previous Page', 'format' => 'plain', 'default' => 'Previous Page'],
                    'footer_caption' => ['label' => 'Footer Caption', 'format' => 'plain', 'default' => 'Ripal Design & Engineering Studio'],
                ],
            ],
        ];

        return $registry;
    }
}

if (!function_exists('public_content_field_meta')) {
    /**
     * Get field metadata map for a page slug.
     *
     * @param string $pageSlug
     * @return array
     */
    function public_content_field_meta($pageSlug) {
        $slug = strtolower(trim((string)$pageSlug));
        $registry = public_content_registry();
        if (!isset($registry[$slug]['fields']) || !is_array($registry[$slug]['fields'])) {
            return [];
        }

        return $registry[$slug]['fields'];
    }
}

if (!function_exists('public_content_defaults_for_page')) {
    /**
     * Get default values for a page slug.
     *
     * @param string $pageSlug
     * @return array
     */
    function public_content_defaults_for_page($pageSlug) {
        $fields = public_content_field_meta($pageSlug);
        $defaults = [];
        foreach ($fields as $key => $meta) {
            $format = (string)($meta['format'] ?? 'plain');
            $defaults[(string)$key] = public_content_resolve_value($format, (string)($meta['default'] ?? ''));
        }

        return $defaults;
    }
}

if (!function_exists('public_content_table_exists')) {
    /**
     * Check if content table exists.
     *
     * @return bool
     */
    function public_content_table_exists() {
        static $exists = null;
        if ($exists !== null) {
            return $exists;
        }

        if (!function_exists('db_connected') || !db_connected()) {
            $exists = false;
            return $exists;
        }

        if (function_exists('db_table_exists')) {
            $exists = db_table_exists('public_page_content');
            return $exists;
        }

        $row = db_fetch('SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?', ['public_page_content']);
        $exists = !empty($row) && (int)($row['c'] ?? 0) > 0;
        return $exists;
    }
}

if (!function_exists('public_content_sanitize_plain')) {
    /**
     * Sanitize plain text content.
     *
     * @param string $value
     * @return string
     */
    function public_content_sanitize_plain($value) {
        $value = strip_tags((string)$value);
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        return trim($value);
    }
}

if (!function_exists('public_content_sanitize_html')) {
    /**
     * Sanitize editable HTML content with a small allowlist.
     *
     * @param string $value
     * @return string
     */
    function public_content_sanitize_html($value) {
        $value = (string)$value;
        $value = preg_replace('#<script[^>]*>.*?</script>#is', '', $value);
        $value = preg_replace('#<style[^>]*>.*?</style>#is', '', $value);

        $allowedTags = '<p><br><strong><em><b><i><u><ul><ol><li><a><span>';
        $value = strip_tags($value, $allowedTags);

        $value = preg_replace('/\s+on[a-z]+\s*=\s*"[^"]*"/i', '', $value);
        $value = preg_replace("/\s+on[a-z]+\s*=\s*'[^']*'/i", '', $value);
        $value = preg_replace('/\s+style\s*=\s*"[^"]*"/i', '', $value);
        $value = preg_replace("/\s+style\s*=\s*'[^']*'/i", '', $value);
        $value = preg_replace('/href\s*=\s*"\s*javascript:[^"]*"/i', 'href="#"', $value);
        $value = preg_replace("/href\s*=\s*'\s*javascript:[^']*'/i", "href='#'", $value);

        return trim($value);
    }
}

if (!function_exists('public_content_normalize_format')) {
    /**
     * Normalize supported content formats.
     *
     * @param string $format
     * @return string
     */
    function public_content_normalize_format($format) {
        $normalized = strtolower(trim((string)$format));
        if ($normalized === 'html') {
            return 'html';
        }
        if ($normalized === 'image') {
            return 'image';
        }

        return 'plain';
    }
}

if (!function_exists('public_content_sanitize_image')) {
    /**
     * Sanitize stored image path/URL values.
     *
     * @param string $value
     * @return string
     */
    function public_content_sanitize_image($value) {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }

        $value = str_replace(["\0", "\r", "\n"], '', $value);

        if (preg_match('#^https?://#i', $value)) {
            return $value;
        }

        $value = str_replace('\\', '/', $value);
        while (strpos($value, '../') === 0) {
            $value = substr($value, 3);
        }
        $value = preg_replace('#^\./#', '', $value);
        $value = str_replace('..', '', $value);
        $value = preg_replace('#/+#', '/', $value);

        if ($value === '') {
            return '';
        }

        return '/' . ltrim($value, '/');
    }
}

if (!function_exists('public_content_image_url')) {
    /**
     * Resolve a stored image value to a browser-ready URL.
     *
     * @param string $value
     * @param string $fallback
     * @return string
     */
    function public_content_image_url($value, $fallback = '') {
        $candidate = trim((string)$value);
        if ($candidate === '') {
            $candidate = trim((string)$fallback);
        }

        $candidate = public_content_sanitize_image($candidate);
        if ($candidate === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $candidate)) {
            return $candidate;
        }

        $relative = ltrim($candidate, '/');
        if ($relative === '') {
            return '';
        }

        // Prepare encoded parts for safe URL generation
        $parts = array_values(array_filter(explode('/', $relative), static function ($part) {
            return $part !== '';
        }));
        $encodedParts = array_map(static function ($part) {
            return rawurlencode(rawurldecode((string)$part));
        }, $parts);
        $encoded = implode('/', $encodedParts);

        // If the file exists on disk at the supplied relative path, return that URL
        $absCandidate = rtrim((string)PROJECT_ROOT, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if (is_file($absCandidate)) {
            if (function_exists('base_path')) {
                $resolved = (string)base_path($encoded);
                return str_replace('/./', '/', $resolved);
            }
            if (defined('BASE_PATH')) {
                $resolved = rtrim((string)BASE_PATH, '/') . '/' . $encoded;
                return str_replace('/./', '/', $resolved);
            }
            return '/' . $encoded;
        }

        // Try a few conventional fallback directories using the same filename
        $filename = rawurldecode((string)end($parts));
        $fallbackDirs = [
            'assets/Content',
            'assets/images',
            'uploads/content',
            'uploads',
            'public/assets/Content',
            'public/images',
            'assets/Content/brand',
        ];

        foreach ($fallbackDirs as $dir) {
            $abs = rtrim((string)PROJECT_ROOT, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $dir) . DIRECTORY_SEPARATOR . $filename;
            if (is_file($abs)) {
                $fallbackParts = array_values(array_filter(explode('/', ltrim($dir . '/' . $filename, '/')), static function ($p) { return $p !== ''; }));
                $encodedFallbackParts = array_map(static function ($part) { return rawurlencode(rawurldecode((string)$part)); }, $fallbackParts);
                $encodedFallback = implode('/', $encodedFallbackParts);

                if (function_exists('base_path')) {
                    $resolved = (string)base_path($encodedFallback);
                    return str_replace('/./', '/', $resolved);
                }
                if (defined('BASE_PATH')) {
                    $resolved = rtrim((string)BASE_PATH, '/') . '/' . $encodedFallback;
                    return str_replace('/./', '/', $resolved);
                }
                return '/' . $encodedFallback;
            }
        }

        // If a fallback value was provided, try to resolve it (allow absolute URLs)
        if ($fallback !== '') {
            $f = public_content_sanitize_image($fallback);
            if (preg_match('#^https?://#i', $f)) {
                return $f;
            }
            if ($f !== $candidate) {
                return public_content_image_url($f, '');
            }
        }

        // Last resort: return an external placeholder so the UI still shows something
        return 'https://placehold.co/240x60/ffffff/000000?text=No+Image';
    }
}

if (!function_exists('public_content_uploaded_image_for_field')) {
    /**
     * Extract a single uploaded file entry from nested content_image payload.
     *
     * @param array $files
     * @param string $pageSlug
     * @param string $fieldKey
     * @return array|null
     */
    function public_content_uploaded_image_for_field($files, $pageSlug, $fieldKey) {
        if (!is_array($files) || !isset($files['error']) || !is_array($files['error'])) {
            return null;
        }

        $error = $files['error'][$pageSlug][$fieldKey] ?? ($files['error'][$fieldKey] ?? UPLOAD_ERR_NO_FILE);
        if ((int)$error === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return [
            'name' => (string)($files['name'][$pageSlug][$fieldKey] ?? ($files['name'][$fieldKey] ?? '')),
            'type' => (string)($files['type'][$pageSlug][$fieldKey] ?? ($files['type'][$fieldKey] ?? '')),
            'tmp_name' => (string)($files['tmp_name'][$pageSlug][$fieldKey] ?? ($files['tmp_name'][$fieldKey] ?? '')),
            'error' => (int)$error,
            'size' => (int)($files['size'][$pageSlug][$fieldKey] ?? ($files['size'][$fieldKey] ?? 0)),
        ];
    }
}

if (!function_exists('public_content_store_uploaded_image')) {
    /**
     * Validate and store uploaded image in uploads/content/{pageSlug}.
     *
     * @param string $pageSlug
     * @param string $fieldKey
     * @param array $uploaded
     * @return array
     */
    function public_content_store_uploaded_image($pageSlug, $fieldKey, $uploaded) {
        $result = ['ok' => false, 'path' => '', 'error' => ''];

        if (!is_array($uploaded)) {
            $result['error'] = 'Invalid upload payload.';
            return $result;
        }

        $uploadError = (int)($uploaded['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($uploadError !== UPLOAD_ERR_OK) {
            $messages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds server upload size limit.',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds form upload size limit.',
                UPLOAD_ERR_PARTIAL => 'File upload was incomplete.',
                UPLOAD_ERR_NO_FILE => 'No file selected.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary upload directory.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write uploaded file.',
                UPLOAD_ERR_EXTENSION => 'Upload stopped by server extension.',
            ];
            $result['error'] = (string)($messages[$uploadError] ?? 'Image upload failed.');
            return $result;
        }

        $tmpPath = (string)($uploaded['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            $result['error'] = 'Invalid uploaded file.';
            return $result;
        }

        $originalName = (string)($uploaded['name'] ?? 'image');
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedMimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
        ];
        if (!isset($allowedMimes[$ext])) {
            $result['error'] = 'Only JPG, JPEG, PNG, WEBP, and GIF files are allowed.';
            return $result;
        }

        $size = (int)($uploaded['size'] ?? 0);
        if ($size <= 0 || $size > 10 * 1024 * 1024) {
            $result['error'] = 'Image must be less than or equal to 10 MB.';
            return $result;
        }

        $detectedMime = '';
        if (function_exists('finfo_open') && function_exists('finfo_file')) {
            $finfo = @finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $detectedMime = (string)@finfo_file($finfo, $tmpPath);
                @finfo_close($finfo);
            }
        }
        if ($detectedMime === '' && function_exists('mime_content_type')) {
            $detectedMime = (string)@mime_content_type($tmpPath);
        }
        if ($detectedMime !== '' && !in_array($detectedMime, array_values($allowedMimes), true)) {
            $result['error'] = 'Uploaded file is not a valid image type.';
            return $result;
        }

        $imgInfo = @getimagesize($tmpPath);
        if ($imgInfo === false) {
            $result['error'] = 'Uploaded file is not a valid image.';
            return $result;
        }

        $safeSlug = preg_replace('/[^A-Za-z0-9_-]+/', '_', strtolower((string)$pageSlug));
        $safeSlug = $safeSlug !== '' ? $safeSlug : 'page';
        $safeField = preg_replace('/[^A-Za-z0-9_-]+/', '_', strtolower((string)$fieldKey));
        $safeField = $safeField !== '' ? $safeField : 'image';

        $relativeDir = 'uploads/content/' . $safeSlug;
        $absoluteDir = rtrim((string)PROJECT_ROOT, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);
        if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
            $result['error'] = 'Unable to create content image directory.';
            return $result;
        }

        try {
            $random = bin2hex(random_bytes(4));
        } catch (Throwable $e) {
            $random = (string)mt_rand(100000, 999999);
        }

        $storedName = $safeField . '_' . time() . '_' . $random . '.' . $ext;
        $absolutePath = $absoluteDir . DIRECTORY_SEPARATOR . $storedName;

        if (!move_uploaded_file($tmpPath, $absolutePath)) {
            $result['error'] = 'Failed to store uploaded image.';
            return $result;
        }

        $result['ok'] = true;
        $result['path'] = '/' . $relativeDir . '/' . $storedName;
        return $result;
    }
}

if (!function_exists('public_content_delete_managed_image')) {
    /**
     * Delete an old managed image file (uploads/content/*) if it exists.
     *
     * @param string $value
     * @return bool
     */
    function public_content_delete_managed_image($value) {
        $path = public_content_sanitize_image($value);
        if ($path === '' || preg_match('#^https?://#i', $path)) {
            return false;
        }

        $relative = ltrim($path, '/');
        if (strpos($relative, 'uploads/content/') !== 0) {
            return false;
        }

        $absolute = rtrim((string)PROJECT_ROOT, '/\\') . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if (!is_file($absolute)) {
            return false;
        }

        $realFile = realpath($absolute);
        $managedRoot = realpath(rtrim((string)PROJECT_ROOT, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'content');
        if ($realFile === false || $managedRoot === false) {
            return false;
        }

        if (strpos($realFile, $managedRoot . DIRECTORY_SEPARATOR) !== 0) {
            return false;
        }

        return @unlink($realFile);
    }
}

if (!function_exists('public_content_resolve_value')) {
    /**
     * Sanitize value by format.
     *
     * @param string $format
     * @param string $value
     * @return string
     */
    function public_content_resolve_value($format, $value) {
        $normalized = public_content_normalize_format($format);
        if ($normalized === 'html') {
            return public_content_sanitize_html($value);
        }
        if ($normalized === 'image') {
            return public_content_sanitize_image($value);
        }

        return public_content_sanitize_plain($value);
    }
}

if (!function_exists('public_content_clear_cache')) {
    /**
     * Invalidate cached content values.
     *
     * @param string|null $pageSlug
     * @return void
     */
    function public_content_clear_cache($pageSlug = null) {
        if ($pageSlug === null) {
            $GLOBALS['__public_content_cache'] = [];
            return;
        }

        $slug = strtolower(trim((string)$pageSlug));
        unset($GLOBALS['__public_content_cache'][$slug]);
    }
}

if (!function_exists('public_content_page_values')) {
    /**
     * Fetch merged values (defaults + DB values) for page slug.
     *
     * @param string $pageSlug
     * @return array
     */
    function public_content_page_values($pageSlug) {
        $slug = strtolower(trim((string)$pageSlug));
        if ($slug === '') {
            return [];
        }

        if (isset($GLOBALS['__public_content_cache'][$slug])) {
            return $GLOBALS['__public_content_cache'][$slug];
        }

        $fields = public_content_field_meta($slug);
        if (empty($fields)) {
            $GLOBALS['__public_content_cache'][$slug] = [];
            return [];
        }

        $values = public_content_defaults_for_page($slug);

        if (!public_content_table_exists()) {
            $GLOBALS['__public_content_cache'][$slug] = $values;
            return $values;
        }

        $rows = db_fetch_all('SELECT section_key, content_value, content_format FROM public_page_content WHERE page_slug = ?', [$slug]);
        foreach ($rows as $row) {
            $key = (string)($row['section_key'] ?? '');
            if ($key === '' || !isset($fields[$key])) {
                continue;
            }

            $fieldFormat = public_content_normalize_format((string)($fields[$key]['format'] ?? 'plain'));
            $format = $fieldFormat;
            if ($fieldFormat !== 'image' && !empty($row['content_format'])) {
                $format = (string)$row['content_format'];
            }

            $values[$key] = public_content_resolve_value($format, (string)($row['content_value'] ?? ''));
        }

        $GLOBALS['__public_content_cache'][$slug] = $values;
        return $values;
    }
}

if (!function_exists('public_content_get')) {
    /**
     * Read one content value from page.
     *
     * @param string $pageSlug
     * @param string $sectionKey
     * @param string $fallback
     * @return string
     */
    function public_content_get($pageSlug, $sectionKey, $fallback = '') {
        $values = public_content_page_values($pageSlug);
        $key = (string)$sectionKey;
        if (array_key_exists($key, $values)) {
            return (string)$values[$key];
        }

        return (string)$fallback;
    }
}

if (!function_exists('public_content_get_html')) {
    /**
     * Read one HTML value from page.
     *
     * @param string $pageSlug
     * @param string $sectionKey
     * @param string $fallback
     * @return string
     */
    function public_content_get_html($pageSlug, $sectionKey, $fallback = '') {
        $value = public_content_get($pageSlug, $sectionKey, $fallback);
        return public_content_sanitize_html($value);
    }
}

if (!function_exists('public_content_upsert_page')) {
    /**
     * Save page fields to public_page_content table.
     *
     * @param string $pageSlug
     * @param array $incoming
     * @param int $updatedBy
     * @param array $uploadedImages
     * @param array $removeImages
     * @return array
     */
    function public_content_upsert_page($pageSlug, $incoming, $updatedBy = 0, $uploadedImages = [], $removeImages = []) {
        $slug = strtolower(trim((string)$pageSlug));
        $result = [
            'ok' => false,
            'saved' => 0,
            'errors' => [],
        ];

        if ($slug === '') {
            $result['errors'][] = 'Missing page slug.';
            return $result;
        }

        if (!is_array($incoming)) {
            $result['errors'][] = 'Invalid submitted content payload.';
            return $result;
        }
        if (!is_array($uploadedImages)) {
            $uploadedImages = [];
        }
        if (!is_array($removeImages)) {
            $removeImages = [];
        }

        $fields = public_content_field_meta($slug);
        if (empty($fields)) {
            $result['errors'][] = 'Unknown content page.';
            return $result;
        }

        if (!public_content_table_exists()) {
            $result['errors'][] = 'Content table is not available. Run the SQL migration first.';
            return $result;
        }

        $authorId = (int)$updatedBy;
        if ($authorId <= 0 && function_exists('current_user_id')) {
            $authorId = (int)current_user_id();
        }
        if ($authorId <= 0) {
            $authorId = null;
        }

        $existingRows = db_fetch_all('SELECT section_key, content_value FROM public_page_content WHERE page_slug = ?', [$slug]);
        $existingValues = [];
        foreach ($existingRows as $existingRow) {
            $existingKey = (string)($existingRow['section_key'] ?? '');
            if ($existingKey === '') {
                continue;
            }
            $existingValues[$existingKey] = (string)($existingRow['content_value'] ?? '');
        }

        foreach ($fields as $key => $meta) {
            $fieldKey = (string)$key;
            $fieldFormat = public_content_normalize_format((string)($meta['format'] ?? 'plain'));
            $uploaded = public_content_uploaded_image_for_field($uploadedImages, $slug, $fieldKey);
            $hasUpload = $fieldFormat === 'image' && is_array($uploaded);

            $removeRequested = !empty($removeImages[$fieldKey]);
            if (!$removeRequested && isset($removeImages[$slug]) && is_array($removeImages[$slug])) {
                $removeRequested = !empty($removeImages[$slug][$fieldKey]);
            }

            $hasIncoming = array_key_exists($fieldKey, $incoming);
            if (!$hasIncoming && !$hasUpload && !$removeRequested) {
                continue;
            }

            $raw = $hasIncoming ? (string)$incoming[$fieldKey] : '';
            $cleanValue = public_content_resolve_value($fieldFormat, $raw);
            $dbFormat = $fieldFormat === 'html' ? 'html' : 'plain';

            if ($fieldFormat === 'image') {
                $existingValue = public_content_sanitize_image((string)($existingValues[$fieldKey] ?? ''));

                if ($removeRequested) {
                    $cleanValue = '';
                }

                if ($hasUpload) {
                    $stored = public_content_store_uploaded_image($slug, $fieldKey, $uploaded);
                    if (empty($stored['ok'])) {
                        $result['errors'][] = 'Image upload failed for field: ' . $fieldKey . '. ' . (string)($stored['error'] ?? 'Unknown error.');
                        continue;
                    }
                    $cleanValue = public_content_sanitize_image((string)($stored['path'] ?? ''));
                }

                $ok = db_query(
                    'INSERT INTO public_page_content (page_slug, section_key, content_value, content_format, updated_by) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE content_value = VALUES(content_value), content_format = VALUES(content_format), updated_by = VALUES(updated_by), updated_at = CURRENT_TIMESTAMP',
                    [$slug, $fieldKey, $cleanValue, $dbFormat, $authorId]
                );

                if ($ok === false) {
                    if ($hasUpload && $cleanValue !== '') {
                        public_content_delete_managed_image($cleanValue);
                    }
                    $result['errors'][] = 'Failed to save field: ' . $fieldKey;
                    continue;
                }

                if ($existingValue !== '' && $existingValue !== $cleanValue) {
                    public_content_delete_managed_image($existingValue);
                }

                $existingValues[$fieldKey] = $cleanValue;
                $result['saved']++;
                continue;
            }

            $ok = db_query(
                'INSERT INTO public_page_content (page_slug, section_key, content_value, content_format, updated_by) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE content_value = VALUES(content_value), content_format = VALUES(content_format), updated_by = VALUES(updated_by), updated_at = CURRENT_TIMESTAMP',
                [$slug, $fieldKey, $cleanValue, $dbFormat, $authorId]
            );

            if ($ok === false) {
                $result['errors'][] = 'Failed to save field: ' . $fieldKey;
                continue;
            }

            $result['saved']++;
        }

        public_content_clear_cache($slug);
        $result['ok'] = empty($result['errors']);

        return $result;
    }
}

if (!function_exists('public_content_seed_defaults')) {
    /**
     * Seed missing registry defaults into public_page_content.
     *
     * @param int $updatedBy
     * @return array
     */
    function public_content_seed_defaults($updatedBy = 0) {
        $result = [
            'ok' => false,
            'seeded' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        if (!public_content_table_exists()) {
            $result['errors'][] = 'Content table is not available. Run the SQL migration first.';
            return $result;
        }

        $authorId = (int)$updatedBy;
        if ($authorId <= 0 && function_exists('current_user_id')) {
            $authorId = (int)current_user_id();
        }
        if ($authorId <= 0) {
            $authorId = null;
        }

        $registry = public_content_registry();
        foreach ($registry as $pageSlug => $meta) {
            $fields = $meta['fields'] ?? [];
            if (!is_array($fields) || empty($fields)) {
                continue;
            }

            foreach ($fields as $sectionKey => $fieldMeta) {
                $fieldFormat = public_content_normalize_format((string)($fieldMeta['format'] ?? 'plain'));
                $dbFormat = $fieldFormat === 'html' ? 'html' : 'plain';
                $defaultValue = public_content_resolve_value($fieldFormat, (string)($fieldMeta['default'] ?? ''));

                $stmt = db_query(
                    'INSERT IGNORE INTO public_page_content (page_slug, section_key, content_value, content_format, updated_by) VALUES (?, ?, ?, ?, ?)',
                    [(string)$pageSlug, (string)$sectionKey, $defaultValue, $dbFormat, $authorId]
                );

                if ($stmt === false) {
                    $result['errors'][] = 'Failed to seed key: ' . (string)$pageSlug . '.' . (string)$sectionKey;
                    continue;
                }

                if ((int)$stmt->rowCount() > 0) {
                    $result['seeded']++;
                } else {
                    $result['skipped']++;
                }
            }
        }

        public_content_clear_cache();
        $result['ok'] = empty($result['errors']);

        return $result;
    }
}

if (!function_exists('public_content_admin_pages')) {
    /**
     * Return lightweight page metadata for editor navigation.
     *
     * @return array
     */
    function public_content_admin_pages() {
        $registry = public_content_registry();
        $out = [];
        foreach ($registry as $slug => $meta) {
            $out[] = [
                'slug' => (string)$slug,
                'title' => (string)($meta['title'] ?? $slug),
                'preview_path' => (string)($meta['preview_path'] ?? ''),
            ];
        }

        return $out;
    }
}
