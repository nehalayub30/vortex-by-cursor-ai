<?php
/**
 * Thorius Chat Shortcode
 * 
 * Renders a standalone chat interface with CLOE
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/shortcodes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Sanitize attributes
$agent = sanitize_text_field($atts['agent']);
$theme = sanitize_text_field($atts['theme']);

// Allowed agents
$allowed_agents = array('cloe', 'strategist');
if (!in_array($agent, $allowed_agents)) {
    $agent = 'cloe';
}

// Allowed themes
$allowed_themes = array('light', 'dark');
if (!in_array($theme, $allowed_themes)) {
    $theme = 'light';
}

// Get agent display name
$agent_name = ($agent === 'cloe') ? 'CLOE' : 'Business Strategist';

// Generate unique ID for this instance
$chat_id = 'vortex-thorius-chat-' . uniqid();
?>

<div id="<?php echo esc_attr($chat_id); ?>" class="vortex-thorius-standalone-chat vortex-thorius-<?php echo esc_attr($theme); ?>">
    <div class="vortex-thorius-standalone-header">
        <div class="vortex-thorius-standalone-title">
            <svg width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                <path d="M8 15c4.418 0 8-3.134 8-7s-3.582-7-8-7-8 3.134-8 7c0 1.76.743 3.37 1.97 4.6-.097 1.016-.417 2.13-.771 2.966-.079.186.074.394.273.362 2.256-.37 3.597-.938 4.18-1.234A9.06 9.06 0 0 0 8 15z"/>
            </svg>
            <?php echo esc_html($agent_name); ?>
        </div>
        <button type="button" class="vortex-thorius-theme-toggle-btn" aria-label="<?php esc_attr_e('Toggle theme', 'vortex-ai-marketplace'); ?>">
            <svg width="16" height="16" fill="currentColor" class="vortex-thorius-light-icon" viewBox="0 0 16 16">
                <path d="M8 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM8 0a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 0zm0 13a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 13zm8-5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5zM3 8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2A.5.5 0 0 1 3 8zm10.657-5.657a.5.5 0 0 1 0 .707l-1.414 1.415a.5.5 0 1 1-.707-.708l1.414-1.414a.5.5 0 0 1 .707 0zm-9.193 9.193a.5.5 0 0 1 0 .707L3.05 13.657a.5.5 0 0 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0zm9.193 2.121a.5.5 0 0 1-.707 0l-1.414-1.414a.5.5 0 0 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .707zM4.464 4.465a.5.5 0 0 1-.707 0L2.343 3.05a.5.5 0 1 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .708z"/>
            </svg>
            <svg width="16" height="16" fill="currentColor" class="vortex-thorius-dark-icon" viewBox="0 0 16 16">
                <path d="M6 .278a.768.768 0 0 1 .08.858 7.208 7.208 0 0 0-.878 3.46c0 4.021 3.278 7.277 7.318 7.277.527 0 1.04-.055 1.533-.16a.787.787 0 0 1 .81.316.733.733 0 0 1-.031.893A8.349 8.349 0 0 1 8.344 16C3.734 16 0 12.286 0 7.71 0 4.266 2.114 1.312 5.124.06A.752.752 0 0 1 6 .278z"/>
            </svg>
        </button>
    </div>
    
    <div class="vortex-thorius-standalone-body">
        <div class="vortex-thorius-messages"></div>
        
        <form class="vortex-thorius-message-form">
            <textarea class="vortex-thorius-message-input" 
                     placeholder="<?php esc_attr_e('Ask anything...', 'vortex-ai-marketplace'); ?>"
                     rows="1"></textarea>
            <button type="submit" class="vortex-thorius-send-btn" aria-label="<?php esc_attr_e('Send message', 'vortex-ai-marketplace'); ?>">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M15.964.686a.5.5 0 0 0-.65-.65L.767 5.855H.766l-.452.18a.5.5 0 0 0-.082.887l.41.26.001.002 4.995 3.178 3.178 4.995.002.002.26.41a.5.5 0 0 0 .886-.083l6-15Zm-1.833 1.89L6.637 10.07l-.215-.338a.5.5 0 0 0-.154-.154l-.338-.215 7.494-7.494 1.178-.471-.47 1.178Z"/>
                </svg>
            </button>
        </form>
    </div>
    
    <input type="hidden" class="vortex-thorius-agent-type" value="<?php echo esc_attr($agent); ?>">
    
    <div class="vortex-thorius-standalone-footer">
        <?php esc_html_e('Powered by Thorius AI', 'vortex-ai-marketplace'); ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    const $chat = $('#<?php echo esc_js($chat_id); ?>');
    
    // Initialize chat
    initChat($chat);
    
    function initChat($chatElement) {
        const $form = $chatElement.find('.vortex-thorius-message-form');
        const $input = $chatElement.find('.vortex-thorius-message-input');
        const $messages = $chatElement.find('.vortex-thorius-messages');
        const agent = $chatElement.find('.vortex-thorius-agent-type').val();
        
        // Add greeting message
        const greeting = agent === 'cloe' 
            ? '<?php echo esc_js(__('Hello! I\'m CLOE, your AI assistant. How can I help you today?', 'vortex-ai-marketplace')); ?>'
            : '<?php echo esc_js(__('Hello! I\'m your Business Strategy Assistant. I can help with market analysis, pricing, and trend predictions.', 'vortex-ai-marketplace')); ?>';
            
        addMessage('ai', greeting);
        
        // Handle form submission
        $form.on('submit', function(e) {
            e.preventDefault();
            
            const message = $input.val().trim();
            
            if (message) {
                // Add user message to chat
                addMessage('user', message);
                
                // Clear input
                $input.val('');
                
                // Add typing indicator
                const $typing = $('<div class="vortex-thorius-typing"><span></span><span></span><span></span></div>');
                $messages.append($typing);
                $messages.scrollTop($messages[0].scrollHeight);
                
                // Process with appropriate agent
                processAgentRequest(agent, message, $typing);
            }
        });
        
        // Add message to chat
        function addMessage(sender, content) {
            const $message = $('<div>', {
                class: `vortex-thorius-message vortex-thorius-${sender}-message`
            });
            
            const $content = $('<div>', {
                class: 'vortex-thorius-message-content',
                html: content
            });
            
            $message.append($content);
            $messages.append($message);
            $messages.scrollTop($messages[0].scrollHeight);
        }
        
        // Process agent request
        function processAgentRequest(agentType, message, $typingIndicator) {
            if (typeof vortex_thorius_params === 'undefined') {
                console.error('Thorius parameters not found');
                addMessage('ai', '<?php echo esc_js(__('Sorry, there was an error processing your request.', 'vortex-ai-marketplace')); ?>');
                $typingIndicator.remove();
                return;
            }
            
            $.ajax({
                url: vortex_thorius_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_thorius_agent_request',
                    nonce: vortex_thorius_params.nonce,
                    agent: agentType,
                    action_type: 'chat',
                    prompt: message
                },
                success: function(response) {
                    $typingIndicator.remove();
                    
                    if (response.success && response.data) {
                        addMessage('ai', response.data.response);
                    } else {
                        addMessage('ai', '<?php echo esc_js(__('Sorry, I couldn\'t process your request.', 'vortex-ai-marketplace')); ?>');
                    }
                },
                error: function() {
                    $typingIndicator.remove();
                    addMessage('ai', '<?php echo esc_js(__('Sorry, there was an error communicating with the server.', 'vortex-ai-marketplace')); ?>');
                }
            });
        }
        
        // Theme toggle
        $chatElement.find('.vortex-thorius-theme-toggle-btn').on('click', function() {
            $chatElement.toggleClass('vortex-thorius-light vortex-thorius-dark');
        });
    }
});
</script> 