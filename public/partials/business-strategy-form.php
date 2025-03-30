<?php
// Determine if this is the detailed recommendation view
$is_detailed = isset($atts['recommendation_type']) && $atts['recommendation_type'] === 'detailed';

// Set form title based on context
$form_title = $is_detailed ? __('Detailed Strategy Recommendations', 'vortex-ai-marketplace') : __('Business Strategy by AI Strategist', 'vortex-ai-marketplace');
?>

<div id="<?php echo esc_attr($strategy_id); ?>" class="vortex-strategist-container <?php echo esc_attr($atts['class']); ?>">
    <h2 class="vortex-form-title"><?php echo esc_html($form_title); ?></h2>
    
    <?php if ($is_detailed): ?>
    <p class="vortex-form-description"><?php _e('Receive comprehensive, actionable strategy recommendations tailored to your business needs', 'vortex-ai-marketplace'); ?></p>
    <?php else: ?>
    <p class="vortex-form-description"><?php _e('Get AI-powered business strategy insights for various industries and focus areas', 'vortex-ai-marketplace'); ?></p>
    <?php endif; ?>

    <!-- Rest of the form HTML -->
</div> 