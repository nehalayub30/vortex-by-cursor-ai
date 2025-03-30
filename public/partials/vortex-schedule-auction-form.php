<?php
/**
 * Template for the auction scheduling form.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="vortex-schedule-auction-form">
    <h2><?php esc_html_e('Schedule Auction Start', 'vortex-ai-marketplace'); ?></h2>
    
    <?php if (empty($auctions)) : ?>
        <div class="vortex-notice">
            <?php esc_html_e('You do not have any auctions available for scheduling.', 'vortex-ai-marketplace'); ?>
        </div>
    <?php else : ?>
        <form id="vortex-schedule-auction-form" class="vortex-form">
            <?php wp_nonce_field('vortex_schedule_auction', 'schedule_auction_nonce'); ?>
            
            <div class="form-group">
                <label for="auction_id"><?php esc_html_e('Select Auction', 'vortex-ai-marketplace'); ?></label>
                <select name="auction_id" id="auction_id" required>
                    <option value=""><?php esc_html_e('Choose an auction...', 'vortex-ai-marketplace'); ?></option>
                    <?php foreach ($auctions as $auction) : ?>
                        <option value="<?php echo esc_attr($auction->ID); ?>">
                            <?php echo esc_html($auction->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="scheduled_time"><?php esc_html_e('Start Time', 'vortex-ai-marketplace'); ?></label>
                <input type="datetime-local" 
                       name="scheduled_time" 
                       id="scheduled_time" 
                       required 
                       min="<?php echo esc_attr(date('Y-m-d\TH:i', strtotime('+1 hour'))); ?>">
                <p class="description">
                    <?php esc_html_e('Select when you want your auction to start.', 'vortex-ai-marketplace'); ?>
                </p>
            </div>
            
            <div class="form-group">
                <button type="button" id="get-suggestions" class="vortex-button vortex-button-secondary">
                    <?php esc_html_e('Get AI Suggestions', 'vortex-ai-marketplace'); ?>
                </button>
                <div id="suggestions-container" class="vortex-suggestions" style="display: none;">
                    <h3><?php esc_html_e('AI Suggested Times', 'vortex-ai-marketplace'); ?></h3>
                    <ul id="suggestions-list"></ul>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="vortex-button vortex-button-primary">
                    <?php esc_html_e('Schedule Start', 'vortex-ai-marketplace'); ?>
                </button>
            </div>
            
            <div id="schedule-message" class="vortex-message" style="display: none;"></div>
        </form>
        
        <script>
        jQuery(document).ready(function($) {
            const form = $('#vortex-schedule-auction-form');
            const message = $('#schedule-message');
            const suggestionsContainer = $('#suggestions-container');
            const suggestionsList = $('#suggestions-list');
            
            // Handle AI suggestions request
            $('#get-suggestions').on('click', function() {
                const auctionId = $('#auction_id').val();
                if (!auctionId) {
                    showMessage('error', '<?php esc_html_e('Please select an auction first.', 'vortex-ai-marketplace'); ?>');
                    return;
                }
                
                $.ajax({
                    url: vortex_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'vortex_get_schedule_suggestions',
                        nonce: vortex_ajax.nonce,
                        item_id: auctionId,
                        item_type: 'auction'
                    },
                    success: function(response) {
                        if (response.success) {
                            displaySuggestions(response.data.suggestions);
                            suggestionsContainer.show();
                        } else {
                            showMessage('error', response.data.message);
                        }
                    },
                    error: function() {
                        showMessage('error', '<?php esc_html_e('Failed to get suggestions. Please try again.', 'vortex-ai-marketplace'); ?>');
                    }
                });
            });
            
            // Handle form submission
            form.on('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('action', 'vortex_schedule_auction');
                formData.append('nonce', vortex_ajax.nonce);
                
                $.ajax({
                    url: vortex_ajax.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            showMessage('success', response.data.message);
                            form[0].reset();
                            suggestionsContainer.hide();
                        } else {
                            showMessage('error', response.data.message);
                        }
                    },
                    error: function() {
                        showMessage('error', '<?php esc_html_e('Failed to schedule auction. Please try again.', 'vortex-ai-marketplace'); ?>');
                    }
                });
            });
            
            // Display AI suggestions
            function displaySuggestions(suggestions) {
                suggestionsList.empty();
                
                suggestions.forEach(function(suggestion) {
                    const li = $('<li>');
                    const button = $('<button>')
                        .addClass('vortex-button vortex-button-secondary')
                        .text(suggestion.time)
                        .on('click', function() {
                            $('#scheduled_time').val(suggestion.datetime);
                            suggestionsContainer.hide();
                        });
                    
                    li.append(button);
                    suggestionsList.append(li);
                });
            }
            
            // Show message
            function showMessage(type, text) {
                message
                    .removeClass('vortex-success vortex-error')
                    .addClass(type === 'success' ? 'vortex-success' : 'vortex-error')
                    .html(text)
                    .show();
                
                setTimeout(function() {
                    message.fadeOut();
                }, 5000);
            }
        });
        </script>
    <?php endif; ?>
</div> 