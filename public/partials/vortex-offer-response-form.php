<?php
/**
 * Template for the offer response form
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="vortex-offer-response-form-container">
    <h3><?php _e('Respond to Offer', 'vortex-ai-marketplace'); ?></h3>
    
    <form id="vortex-offer-response-form" class="vortex-form">
        <input type="hidden" name="offer_id" value="<?php echo esc_attr($offer_id); ?>">
        
        <div class="vortex-form-group">
            <label for="response_type"><?php _e('Response Type', 'vortex-ai-marketplace'); ?></label>
            <select name="response_type" id="response_type" required>
                <option value=""><?php _e('Select a response type', 'vortex-ai-marketplace'); ?></option>
                <option value="accept"><?php _e('Accept', 'vortex-ai-marketplace'); ?></option>
                <option value="reject"><?php _e('Reject', 'vortex-ai-marketplace'); ?></option>
                <option value="counter"><?php _e('Counter Offer', 'vortex-ai-marketplace'); ?></option>
            </select>
        </div>
        
        <div class="vortex-form-group">
            <label for="response_message"><?php _e('Message', 'vortex-ai-marketplace'); ?></label>
            <textarea name="response_message" id="response_message" rows="4" required></textarea>
        </div>
        
        <div class="vortex-form-group">
            <button type="submit" class="vortex-button">
                <?php _e('Submit Response', 'vortex-ai-marketplace'); ?>
            </button>
        </div>
        
        <div class="vortex-message" style="display: none;"></div>
    </form>
</div>

<style>
.vortex-offer-response-form-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.vortex-form-group {
    margin-bottom: 20px;
}

.vortex-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.vortex-form-group select,
.vortex-form-group textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.vortex-button {
    background: #0073aa;
    color: #fff;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
}

.vortex-button:hover {
    background: #005177;
}

.vortex-message {
    margin-top: 15px;
    padding: 10px;
    border-radius: 4px;
    display: none;
}

.vortex-message-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.vortex-message-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
</style> 