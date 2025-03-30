<?php
/**
 * Template for the exhibition scheduling form.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="vortex-schedule-exhibition-form">
    <h2><?php esc_html_e('Schedule Exhibition Opening', 'vortex-ai-marketplace'); ?></h2>
    
    <?php if (empty($exhibitions)) : ?>
        <div class="vortex-notice">
            <?php esc_html_e('You do not have any exhibitions available for scheduling.', 'vortex-ai-marketplace'); ?>
        </div>
    <?php else : ?>
        <form id="vortex-schedule-exhibition-form" class="vortex-form">
            <?php wp_nonce_field('vortex_schedule_exhibition', 'schedule_exhibition_nonce'); ?>
            
            <div class="form-group">
                <label for="exhibition_id"><?php esc_html_e('Select Exhibition', 'vortex-ai-marketplace'); ?></label>
                <select name="exhibition_id" id="exhibition_id" required>
                    <option value=""><?php esc_html_e('Choose an exhibition...', 'vortex-ai-marketplace'); ?></option>
                    <?php foreach ($exhibitions as $exhibition) : ?>
                        <option value="<?php echo esc_attr($exhibition->ID); ?>">
                            <?php echo esc_html($exhibition->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="scheduled_time"><?php esc_html_e('Opening Time', 'vortex-ai-marketplace'); ?></label>
                <input type="datetime-local" 
                       name="scheduled_time" 
                       id="scheduled_time" 
                       required 
                       min="<?php echo esc_attr(date('Y-m-d\TH:i', strtotime('+1 hour'))); ?>">
                <p class="description">
                    <?php esc_html_e('Select when you want your exhibition to open.', 'vortex-ai-marketplace'); ?>
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
                    <?php esc_html_e('Schedule Opening', 'vortex-ai-marketplace'); ?>
                </button>
            </div>
            
            <div id="schedule-message" class="vortex-message" style="display: none;"></div>
        </form>
        
        <script>
        jQuery(document).ready(function($) {
            const form = $('#vortex-schedule-exhibition-form');
            const message = $('#schedule-message');
            const suggestionsContainer = $('#suggestions-container');
            const suggestionsList = $('#suggestions-list');
            
            // Handle AI suggestions request
            $('#get-suggestions').on('click', function() {
                const exhibitionId = $('#exhibition_id').val();
                if (!exhibitionId) {
                    showMessage('error', '<?php esc_html_e('Please select an exhibition first.', 'vortex-ai-marketplace'); ?>');
                    return;
                }
                
                $.ajax({
                    url: vortex_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'vortex_get_schedule_suggestions',
                        nonce: vortex_ajax.nonce,
                        item_id: exhibitionId,
                        item_type: 'exhibition'
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
                formData.append('action', 'vortex_schedule_exhibition');
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
                        showMessage('error', '<?php esc_html_e('Failed to schedule exhibition. Please try again.', 'vortex-ai-marketplace'); ?>');
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