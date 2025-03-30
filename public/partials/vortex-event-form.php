<?php
/**
 * Template for the event creation form.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="vortex-event-form">
    <h2><?php esc_html_e('Create New Event', 'vortex-ai-marketplace'); ?></h2>
    
    <form id="vortex-event-form" class="vortex-form">
        <?php wp_nonce_field('vortex_create_event', 'event_nonce'); ?>
        
        <div class="form-group">
            <label for="event_title"><?php esc_html_e('Event Title', 'vortex-ai-marketplace'); ?></label>
            <input type="text" 
                   name="event_title" 
                   id="event_title" 
                   required 
                   class="vortex-input">
            <p class="description">
                <?php esc_html_e('Enter a descriptive title for your event.', 'vortex-ai-marketplace'); ?>
            </p>
        </div>
        
        <div class="form-group">
            <label for="event_description"><?php esc_html_e('Event Description', 'vortex-ai-marketplace'); ?></label>
            <textarea name="event_description" 
                      id="event_description" 
                      required 
                      class="vortex-textarea"
                      rows="5"></textarea>
            <p class="description">
                <?php esc_html_e('Provide detailed information about your event.', 'vortex-ai-marketplace'); ?>
            </p>
        </div>
        
        <div class="form-group">
            <label for="event_date"><?php esc_html_e('Event Date & Time', 'vortex-ai-marketplace'); ?></label>
            <input type="datetime-local" 
                   name="event_date" 
                   id="event_date" 
                   required 
                   min="<?php echo esc_attr(date('Y-m-d\TH:i', strtotime('+1 day'))); ?>"
                   class="vortex-input">
            <p class="description">
                <?php esc_html_e('Select when your event will take place.', 'vortex-ai-marketplace'); ?>
            </p>
        </div>
        
        <div class="form-group">
            <label for="event_location"><?php esc_html_e('Event Location', 'vortex-ai-marketplace'); ?></label>
            <input type="text" 
                   name="event_location" 
                   id="event_location" 
                   required 
                   class="vortex-input">
            <p class="description">
                <?php esc_html_e('Enter the location or platform for your event.', 'vortex-ai-marketplace'); ?>
            </p>
        </div>
        
        <div class="form-group">
            <label for="event_capacity"><?php esc_html_e('Event Capacity', 'vortex-ai-marketplace'); ?></label>
            <input type="number" 
                   name="event_capacity" 
                   id="event_capacity" 
                   required 
                   min="1"
                   class="vortex-input">
            <p class="description">
                <?php esc_html_e('Maximum number of participants allowed.', 'vortex-ai-marketplace'); ?>
            </p>
        </div>
        
        <div class="form-group">
            <label for="event_price"><?php esc_html_e('Event Price (TOLA)', 'vortex-ai-marketplace'); ?></label>
            <input type="number" 
                   name="event_price" 
                   id="event_price" 
                   required 
                   min="0"
                   step="0.01"
                   class="vortex-input">
            <p class="description">
                <?php esc_html_e('Price per participant in TOLA tokens.', 'vortex-ai-marketplace'); ?>
            </p>
        </div>
        
        <div class="form-group">
            <label for="event_image"><?php esc_html_e('Event Image', 'vortex-ai-marketplace'); ?></label>
            <input type="file" 
                   name="event_image" 
                   id="event_image" 
                   accept="image/*"
                   class="vortex-input">
            <p class="description">
                <?php esc_html_e('Upload an image for your event (optional).', 'vortex-ai-marketplace'); ?>
            </p>
        </div>
        
        <div class="form-group">
            <button type="submit" class="vortex-button vortex-button-primary">
                <?php esc_html_e('Create Event', 'vortex-ai-marketplace'); ?>
            </button>
        </div>
        
        <div id="event-message" class="vortex-message" style="display: none;"></div>
    </form>
    
    <script>
    jQuery(document).ready(function($) {
        const form = $('#vortex-event-form');
        const message = $('#event-message');
        
        // Handle form submission
        form.on('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'vortex_create_event');
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
                        // Optionally redirect to the event page
                        if (response.data.redirect_url) {
                            window.location.href = response.data.redirect_url;
                        }
                    } else {
                        showMessage('error', response.data.message);
                    }
                },
                error: function() {
                    showMessage('error', '<?php esc_html_e('Failed to create event. Please try again.', 'vortex-ai-marketplace'); ?>');
                }
            });
        });
        
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
    .vortex-event-form {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
    }
    
    .vortex-input,
    .vortex-textarea {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }
    
    .vortex-textarea {
        resize: vertical;
    }
    
    .description {
        margin-top: 5px;
        color: #666;
        font-size: 12px;
    }
    
    .vortex-button {
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        transition: background-color 0.3s;
    }
    
    .vortex-button-primary {
        background: #007bff;
        color: #fff;
    }
    
    .vortex-button-primary:hover {
        background: #0056b3;
    }
    
    .vortex-message {
        margin-top: 20px;
        padding: 10px;
        border-radius: 4px;
    }
    
    .vortex-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .vortex-error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    </style>
</div> 