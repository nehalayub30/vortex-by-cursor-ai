<?php
// Determine if this is the advanced analysis view
$is_advanced_analysis = isset($atts['analysis_depth']) && $atts['analysis_depth'] === 'advanced';

// Set form title based on context
$form_title = $is_advanced_analysis ? __('Advanced Market Analysis by CLOE', 'vortex-ai-marketplace') : __('Market Analysis by CLOE', 'vortex-ai-marketplace');
?>

<div id="<?php echo esc_attr($analysis_id); ?>" class="vortex-cloe-container <?php echo esc_attr($atts['class']); ?>">
    <h2 class="vortex-form-title"><?php echo esc_html($form_title); ?></h2>
    
    <?php if ($is_advanced_analysis): ?>
    <p class="vortex-form-description"><?php _e('Get detailed, in-depth analysis of market trends, patterns and projections', 'vortex-ai-marketplace'); ?></p>
    <?php else: ?>
    <p class="vortex-form-description"><?php _e('Discover insights and trends across different markets with AI-powered analysis', 'vortex-ai-marketplace'); ?></p>
    <?php endif; ?>

    <!-- Rest of the form HTML -->
</div> 