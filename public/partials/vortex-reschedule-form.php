<?php
/**
 * Template for the event rescheduling form.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="vortex-reschedule-form">
    <h3><?php esc_html_e('Reschedule Event', 'vortex-ai-marketplace'); ?></h3>
    
    <form id="vortex-reschedule-form" class="vortex-form">
        <?php wp_nonce_field('vortex_reschedule_event', 'reschedule_event_nonce'); ?>
        <input type="hidden" name="event_id" value="<?php echo esc_attr($event->id); ?>">
        <input type="hidden" name="event_type" value="<?php echo esc_attr($event->type); ?>">
        
        <div class="form-group">
            <label for="scheduled_time"><?php esc_html_e('New Time', 'vortex-ai-marketplace'); ?></label>
            <input type="datetime-local" 
                   name="scheduled_time" 
                   id="scheduled_time" 
                   required 
                   min="<?php echo esc_attr(date('Y-m-d\TH:i', strtotime('+1 hour'))); ?>"
                   value="<?php echo esc_attr(date('Y-m-d\TH:i', strtotime($event->scheduled_time))); ?>">
            <p class="description">
                <?php esc_html_e('Select the new time for this event.', 'vortex-ai-marketplace'); ?>
            </p>
        </div>
        
        <div class="form-group">
            <button type="button" id="get-suggestions" class="vortex-button vortex-button-secondary">
                <?php esc_html_e('Get AI Suggestions', 'vortex-ai-marketplace'); ?>
            </button>
            <div id="suggestions-container" class="vortex-suggestions" style="display: none;">
                <h4><?php esc_html_e('AI Suggested Times', 'vortex-ai-marketplace'); ?></h4>
                <ul id="suggestions-list"></ul>
            </div>
        </div>
        
        <div class="form-group">
            <button type="submit" class="vortex-button vortex-button-primary">
                <?php esc_html_e('Update Schedule', 'vortex-ai-marketplace'); ?>
            </button>
            <button type="button" class="vortex-button vortex-button-secondary cancel-reschedule">
                <?php esc_html_e('Cancel', 'vortex-ai-marketplace'); ?>
            </button>
        </div>
        
        <div id="reschedule-message" class="vortex-message" style="display: none;"></div>
    </form>
    
    <script>
    jQuery(document).ready(function($) {
        const form = $('#vortex-reschedule-form');
        const message = $('#reschedule-message');
        const suggestionsContainer = $('#suggestions-container');
        const suggestionsList = $('#suggestions-list');
        
        // Handle AI suggestions request
        $('#get-suggestions').on('click', function() {
            const eventId = $('input[name="event_id"]').val();
            const eventType = $('input[name="event_type"]').val();
            
            $.ajax({
                url: vortex_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_get_schedule_suggestions',
                    nonce: vortex_ajax.nonce,
                    item_id: eventId,
                    item_type: eventType
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
            formData.append('action', 'vortex_reschedule_event');
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
                        // Close the modal or form container
                        $('.vortex-modal').fadeOut();
                        // Refresh the events list
                        location.reload();
                    } else {
                        showMessage('error', response.data.message);
                    }
                },
                error: function() {
                    showMessage('error', '<?php esc_html_e('Failed to reschedule event. Please try again.', 'vortex-ai-marketplace'); ?>');
                }
            });
        });
        
        // Handle cancel button
        $('.cancel-reschedule').on('click', function() {
            $('.vortex-modal').fadeOut();
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
    
    <style>
    .vortex-reschedule-form {
        padding: 20px;
        background: #fff;
        border-radius: 4px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .vortex-suggestions {
        margin-top: 15px;
        padding: 10px;
        background: #f5f5f5;
        border-radius: 4px;
    }
    
    .vortex-suggestions ul {
        list-style: none;
        padding: 0;
        margin: 10px 0 0;
    }
    
    .vortex-suggestions li {
        margin-bottom: 5px;
    }
    
    .vortex-suggestions button {
        width: 100%;
        text-align: left;
        padding: 8px 12px;
    }
    
    .vortex-suggestions button:hover {
        background: #e0e0e0;
    }
    </style>
</div> 