<?php
/**
 * Template for displaying the chatbot button
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Determine button classes based on attributes
$button_classes = array('vortex-chatbot-button');
$button_classes[] = 'vortex-chatbot-' . esc_attr($atts['size']);
$button_classes[] = 'vortex-chatbot-' . esc_attr($atts['theme']);
$button_classes[] = 'vortex-chatbot-' . esc_attr($atts['position']);
$button_classes[] = 'vortex-chatbot-' . esc_attr($atts['animation']);
if (!empty($atts['classes'])) {
    $button_classes[] = esc_attr($atts['classes']);
}

// Determine icon class based on attribute
$icon_class = 'dashicons dashicons-';
switch ($atts['icon']) {
    case 'message':
        $icon_class .= 'email';
        break;
    case 'support':
        $icon_class .= 'admin-users';
        break;
    default:
        $icon_class .= 'format-chat';
}
?>

<div class="vortex-chatbot-button-container">
    <button class="<?php echo implode(' ', $button_classes); ?>" id="vortex-chatbot-trigger">
        <span class="vortex-chatbot-icon <?php echo $icon_class; ?>"></span>
        <span class="vortex-chatbot-text"><?php echo esc_html($atts['text']); ?></span>
    </button>
</div>

<style>
.vortex-chatbot-button-container {
    position: fixed;
    z-index: 9999;
}

.vortex-chatbot-button {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    border: none;
    border-radius: 25px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.vortex-chatbot-light {
    background-color: #ffffff;
    color: #333333;
}

.vortex-chatbot-dark {
    background-color: #333333;
    color: #ffffff;
}

.vortex-chatbot-small {
    padding: 8px 16px;
    font-size: 14px;
}

.vortex-chatbot-medium {
    padding: 12px 20px;
    font-size: 16px;
}

.vortex-chatbot-large {
    padding: 16px 24px;
    font-size: 18px;
}

.vortex-chatbot-bottom-right {
    bottom: 20px;
    right: 20px;
}

.vortex-chatbot-bottom-left {
    bottom: 20px;
    left: 20px;
}

.vortex-chatbot-top-right {
    top: 20px;
    right: 20px;
}

.vortex-chatbot-top-left {
    top: 20px;
    left: 20px;
}

.vortex-chatbot-bounce {
    animation: vortex-bounce 2s infinite;
}

.vortex-chatbot-pulse {
    animation: vortex-pulse 2s infinite;
}

.vortex-chatbot-shake {
    animation: vortex-shake 2s infinite;
}

@keyframes vortex-bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

@keyframes vortex-pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

@keyframes vortex-shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

.vortex-chatbot-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.vortex-chatbot-icon {
    font-size: 20px;
}

@media (max-width: 768px) {
    .vortex-chatbot-button {
        padding: 10px 16px;
    }
    
    .vortex-chatbot-text {
        display: none;
    }
    
    .vortex-chatbot-icon {
        font-size: 24px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#vortex-chatbot-trigger').on('click', function(e) {
        e.preventDefault();
        
        // Check if support chat is already initialized
        if (typeof VortexSupportChat !== 'undefined') {
            VortexSupportChat.open();
        } else {
            console.error('Vortex Support Chat not initialized');
        }
    });
});
</script> 