<?php
/**
 * Template for the upgrade button display
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage VORTEX_AI_Marketplace/public/partials
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get button classes based on style and size
$button_classes = array('vortex-upgrade-button');
$button_classes[] = 'vortex-button-' . esc_attr($atts['button_style']);
$button_classes[] = 'vortex-button-' . esc_attr($atts['size']);
if (!empty($atts['classes'])) {
    $button_classes[] = esc_attr($atts['classes']);
}
?>

<div class="vortex-upgrade-container">
    <?php if ($show_features) : ?>
    <div class="vortex-upgrade-features">
        <?php foreach ($atts['features'] as $feature) : ?>
            <div class="vortex-upgrade-feature">
                <span class="vortex-upgrade-feature-icon">✓</span>
                <span class="vortex-upgrade-feature-text"><?php echo esc_html($feature); ?></span>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <div class="vortex-upgrade-button-wrapper">
        <?php if ($show_price) : ?>
            <div class="vortex-upgrade-price"><?php echo esc_html($atts['price_text']); ?></div>
        <?php endif; ?>
        
        <a href="<?php echo esc_url(add_query_arg(array(
            'action' => 'upgrade',
            'nonce' => wp_create_nonce('vortex_upgrade_subscription')
        ), admin_url('admin-ajax.php'))); ?>" 
           class="<?php echo esc_attr(implode(' ', $button_classes)); ?>"
           data-vortex-upgrade="true">
            <?php echo esc_html($atts['text']); ?>
        </a>
    </div>
</div>

<style>
.vortex-upgrade-container {
    background: #ffffff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin: 20px 0;
}

.vortex-upgrade-features {
    margin-bottom: 20px;
}

.vortex-upgrade-feature {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
    color: #333;
}

.vortex-upgrade-feature-icon {
    color: #28a745;
    margin-right: 10px;
    font-weight: bold;
}

.vortex-upgrade-button-wrapper {
    text-align: center;
}

.vortex-upgrade-price {
    font-size: 1.2em;
    font-weight: bold;
    color: #333;
    margin-bottom: 10px;
}

.vortex-upgrade-button {
    display: inline-block;
    padding: 12px 24px;
    border-radius: 4px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
}

.vortex-button-primary {
    background: #007bff;
    color: #ffffff;
}

.vortex-button-primary:hover {
    background: #0056b3;
}

.vortex-button-secondary {
    background: #6c757d;
    color: #ffffff;
}

.vortex-button-secondary:hover {
    background: #545b62;
}

.vortex-button-outline {
    border: 2px solid #007bff;
    color: #007bff;
}

.vortex-button-outline:hover {
    background: #007bff;
    color: #ffffff;
}

.vortex-button-small {
    padding: 8px 16px;
    font-size: 0.9em;
}

.vortex-button-large {
    padding: 16px 32px;
    font-size: 1.1em;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('.vortex-upgrade-button').on('click', function(e) {
        e.preventDefault();
        var $button = $(this);
        var upgradeUrl = $button.attr('href');
        
        // Show loading state
        $button.prop('disabled', true).text('<?php echo esc_js(__('Processing...', 'vortex-ai-marketplace')); ?>');
        
        // Send upgrade request
        $.ajax({
            url: upgradeUrl,
            type: 'POST',
            success: function(response) {
                if (response.success) {
                    // Redirect to payment page
                    window.location.href = response.data.redirect_url;
                } else {
                    // Show error message
                    alert(response.data.message || '<?php echo esc_js(__('An error occurred. Please try again.', 'vortex-ai-marketplace')); ?>');
                    $button.prop('disabled', false).text('<?php echo esc_js($atts['text']); ?>');
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('An error occurred. Please try again.', 'vortex-ai-marketplace')); ?>');
                $button.prop('disabled', false).text('<?php echo esc_js($atts['text']); ?>');
            }
        });
    });
});
</script> 