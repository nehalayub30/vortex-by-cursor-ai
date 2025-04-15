<?php
/**
 * Template for Thorius Concierge interface
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
$position = isset($atts['position']) ? sanitize_text_field($atts['position']) : 'right';
$welcome_message = isset($atts['welcome_message']) ? sanitize_text_field($atts['welcome_message']) : __('Hello! I\'m Thorius, your AI concierge. How can I help you today?', 'vortex-ai-marketplace');
$placeholder = isset($atts['placeholder']) ? sanitize_text_field($atts['placeholder']) : __('Ask me anything...', 'vortex-ai-marketplace');
$voice_enabled = isset($atts['enable_voice']) && filter_var($atts['enable_voice'], FILTER_VALIDATE_BOOLEAN);
$show_tabs = isset($atts['show_tabs']) && filter_var($atts['show_tabs'], FILTER_VALIDATE_BOOLEAN);

// Generate unique ID for this concierge instance
$concierge_id = 'thorius-concierge-' . uniqid();

// Enqueue required assets
wp_enqueue_style('thorius-concierge-style');
wp_enqueue_script('thorius-concierge-script');

if ($voice_enabled) {
    wp_enqueue_script('thorius-voice-script');
}
?>

<div id="<?php echo esc_attr($concierge_id); ?>" 
     class="thorius-concierge-container thorius-theme-<?php echo esc_attr($theme); ?> thorius-position-<?php echo esc_attr($position); ?>"
     data-voice="<?php echo $voice_enabled ? 'true' : 'false'; ?>"
     data-tabs="<?php echo $show_tabs ? 'true' : 'false'; ?>">
    
    <div class="thorius-concierge-button">
        <div class="thorius-concierge-icon"></div>
    </div>
    
    <div class="thorius-concierge-panel">
        <div class="thorius-concierge-header">
            <div class="thorius-concierge-logo"></div>
            <div class="thorius-concierge-title"><?php esc_html_e('Thorius AI', 'vortex-ai-marketplace'); ?></div>
            <div class="thorius-concierge-close">×</div>
        </div>
        
        <?php if ($show_tabs): ?>
        <div class="thorius-concierge-tabs">
            <div class="thorius-tab thorius-tab-active" data-tab="chat"><?php esc_html_e('Chat', 'vortex-ai-marketplace'); ?></div>
            <div class="thorius-tab" data-tab="marketplace"><?php esc_html_e('Marketplace', 'vortex-ai-marketplace'); ?></div>
            <div class="thorius-tab" data-tab="insights"><?php esc_html_e('Insights', 'vortex-ai-marketplace'); ?></div>
        </div>
        <?php endif; ?>
        
        <div class="thorius-concierge-content">
            <div class="thorius-tab-content thorius-tab-content-active" data-tab-content="chat">
                <div class="thorius-chat-messages">
                    <div class="thorius-message thorius-message-assistant">
                        <div class="thorius-avatar"></div>
                        <div class="thorius-message-bubble"><?php echo esc_html($welcome_message); ?></div>
                    </div>
                </div>
                
                <div class="thorius-suggestions">
                    <div class="thorius-suggestion"><?php esc_html_e('Show me new artwork', 'vortex-ai-marketplace'); ?></div>
                    <div class="thorius-suggestion"><?php esc_html_e('What is TOLA?', 'vortex-ai-marketplace'); ?></div>
                    <div class="thorius-suggestion"><?php esc_html_e('Find artists near me', 'vortex-ai-marketplace'); ?></div>
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
            </div>
            
            <?php if ($show_tabs): ?>
            <div class="thorius-tab-content" data-tab-content="marketplace">
                <div class="thorius-marketplace-content">
                    <h3><?php esc_html_e('Marketplace Highlights', 'vortex-ai-marketplace'); ?></h3>
                    <p><?php esc_html_e('Loading marketplace data...', 'vortex-ai-marketplace'); ?></p>
                </div>
            </div>
            
            <div class="thorius-tab-content" data-tab-content="insights">
                <div class="thorius-insights-content">
                    <h3><?php esc_html_e('AI Insights', 'vortex-ai-marketplace'); ?></h3>
                    <p><?php esc_html_e('Loading insights...', 'vortex-ai-marketplace'); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="thorius-concierge-footer">
            <div class="thorius-powered-by">
                <?php esc_html_e('Powered by Vortex AI', 'vortex-ai-marketplace'); ?>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Initialize the concierge
    if (typeof ThoriusConcierge !== 'undefined') {
        new ThoriusConcierge('<?php echo esc_js($concierge_id); ?>');
    }
});
</script> 