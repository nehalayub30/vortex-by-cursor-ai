<?php
/**
 * Thorius Chat Tab
 * 
 * Template for the chat tab in Thorius Concierge
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="vortex-thorius-chat">
    <div id="vortex-thorius-messages" class="vortex-thorius-messages"></div>
    
    <form id="vortex-thorius-message-form" class="vortex-thorius-message-form">
        <textarea id="vortex-thorius-message-input" class="vortex-thorius-message-input" 
                 placeholder="<?php esc_attr_e('Ask Thorius anything...', 'vortex-ai-marketplace'); ?>"
                 rows="1"></textarea>
        <button type="submit" class="vortex-thorius-send-btn" aria-label="<?php esc_attr_e('Send message', 'vortex-ai-marketplace'); ?>">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                <path d="M15.964.686a.5.5 0 0 0-.65-.65L.767 5.855H.766l-.452.18a.5.5 0 0 0-.082.887l.41.26.001.002 4.995 3.178 3.178 4.995.002.002.26.41a.5.5 0 0 0 .886-.083l6-15Zm-1.833 1.89L6.637 10.07l-.215-.338a.5.5 0 0 0-.154-.154l-.338-.215 7.494-7.494 1.178-.471-.47 1.178Z"/>
            </svg>
        </button>
    </form>
    
    <div class="vortex-thorius-chat-suggestions">
        <h5><?php esc_html_e('Suggested Prompts', 'vortex-ai-marketplace'); ?></h5>
        <div class="vortex-thorius-chat-suggestion-list">
            <button type="button" class="vortex-thorius-chat-suggestion"><?php esc_html_e('How can I create an NFT?', 'vortex-ai-marketplace'); ?></button>
            <button type="button" class="vortex-thorius-chat-suggestion"><?php esc_html_e('What art styles are trending?', 'vortex-ai-marketplace'); ?></button>
            <button type="button" class="vortex-thorius-chat-suggestion"><?php esc_html_e('Help me price my digital artwork', 'vortex-ai-marketplace'); ?></button>
            <button type="button" class="vortex-thorius-chat-suggestion"><?php esc_html_e('Generate a cyberpunk cityscape', 'vortex-ai-marketplace'); ?></button>
        </div>
    </div>
</div> 