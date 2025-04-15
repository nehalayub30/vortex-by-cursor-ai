<?php
/**
 * Template for Thorius Chat interface
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Extract shortcode attributes
$theme = isset($atts['theme']) ? sanitize_text_field($atts['theme']) : 'light';
$height = isset($atts['height']) ? sanitize_text_field($atts['height']) : '400px';
$welcome_message = isset($atts['welcome_message']) ? sanitize_text_field($atts['welcome_message']) : __('Hello! How can I assist you today?', 'vortex-ai-marketplace');
$placeholder = isset($atts['placeholder']) ? sanitize_text_field($atts['placeholder']) : __('Type your message...', 'vortex-ai-marketplace');
$voice_enabled = isset($atts['enable_voice']) && filter_var($atts['enable_voice'], FILTER_VALIDATE_BOOLEAN);

// Generate unique ID for this chat instance
$chat_id = 'thorius-chat-' . uniqid();

// Enqueue required assets
wp_enqueue_style('thorius-chat-style');
wp_enqueue_script('thorius-chat-script');

if ($voice_enabled) {
    wp_enqueue_script('thorius-voice-script');
}
?>

<div id="<?php echo esc_attr($chat_id); ?>" class="thorius-chat-container thorius-theme-<?php echo esc_attr($theme); ?>" 
     style="height: <?php echo esc_attr($height); ?>;" 
     data-voice="<?php echo $voice_enabled ? 'true' : 'false'; ?>">
    
    <div class="thorius-chat-header">
        <div class="thorius-chat-logo"></div>
        <div class="thorius-chat-title"><?php esc_html_e('Thorius AI', 'vortex-ai-marketplace'); ?></div>
    </div>
    
    <div class="thorius-chat-messages">
        <div class="thorius-message thorius-message-assistant">
            <div class="thorius-avatar"></div>
            <div class="thorius-message-bubble"><?php echo esc_html($welcome_message); ?></div>
        </div>
    </div>
    
    <div class="thorius-chat-input">
        <textarea placeholder="<?php echo esc_attr($placeholder); ?>" rows="1"></textarea>
        <?php if ($voice_enabled) : ?>
        <button class="thorius-voice-button" aria-label="<?php esc_attr_e('Voice input', 'vortex-ai-marketplace'); ?>">
            <span class="dashicons dashicons-microphone"></span>
        </button>
        <?php endif; ?>
        <button class="thorius-send-button" aria-label="<?php esc_attr_e('Send message', 'vortex-ai-marketplace'); ?>">
            <span class="dashicons dashicons-arrow-right-alt2"></span>
        </button>
    </div>
    
    <div class="thorius-chat-footer">
        <div class="thorius-powered-by">
            <?php esc_html_e('Powered by Vortex AI', 'vortex-ai-marketplace'); ?>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize the chat
    if (typeof ThoriusChat !== 'undefined') {
        new ThoriusChat('<?php echo esc_js($chat_id); ?>');
    }
});
</script> 