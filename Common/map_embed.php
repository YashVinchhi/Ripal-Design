<?php
/**
 * Reusable Google Maps Embed Component
 * 
 * USAGE:
 * ------
 * Default map (Nanavati Chowk, Rajkot):
 *   <?php include __DIR__ . '/../Common/map_embed.php'; ?>
 * 
 * With custom height:
 *   <?php $map_height = '400px'; include __DIR__ . '/../Common/map_embed.php'; ?>
 * 
 * With custom aspect ratio:
 *   <?php $map_aspect_ratio = '16/9'; include __DIR__ . '/../Common/map_embed.php'; ?>
 * 
 * With custom classes:
 *   <?php $map_classes = 'shadow-lg rounded-xl'; include __DIR__ . '/../Common/map_embed.php'; ?>
 * 
 * PARAMETERS:
 * -----------
 * $map_height - Fixed height (e.g., '400px'). Overrides aspect ratio.
 * $map_aspect_ratio - Aspect ratio (e.g., '16/9', '4/3'). Default: '4/3'.
 * $map_classes - Additional CSS classes for container.
 * $map_title - Title attribute for accessibility. Default: 'Project Location Map'.
 * 
 * To customize the embedded location, replace the iframe src URL with your own
 * Google Maps embed URL from: https://www.google.com/maps/
 * (Share > Embed a map > Copy HTML)
 */

// Set defaults if not provided
$map_aspect_ratio = $map_aspect_ratio ?? '4/3';
$map_classes = $map_classes ?? '';
$map_title = $map_title ?? 'Project Location Map';

// Build container style
$container_style = isset($map_height) && !empty($map_height) 
    ? "height: {$map_height};" 
    : "aspect-ratio: {$map_aspect_ratio};";
?>
<div class="map-embed-container <?php echo htmlspecialchars($map_classes); ?>" style="<?php echo $container_style; ?> position: relative; overflow: hidden;">
    <iframe 
        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d287.47857098438425!2d70.76867685826322!3d22.30597063170977!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3959c983c4b8aeaf%3A0xf7c6e2439ee00a3f!2sNanavati%20Chowk!5e1!3m2!1sen!2sin!4v1771055842937!5m2!1sen!2sin" 
        width="100%" 
        height="100%" 
        style="border:0; position: absolute; top: 0; left: 0;" 
        allowfullscreen="" 
        loading="lazy" 
        referrerpolicy="no-referrer-when-downgrade"
        title="<?php echo htmlspecialchars($map_title); ?>">
    </iframe>
</div>
<?php
// Reset variables to avoid side effects
unset($map_height, $map_aspect_ratio, $map_classes, $map_title);
?>
