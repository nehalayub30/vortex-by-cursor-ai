<?php
/**
 * Template for the offer creation form.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="vortex-offer-form">
    <h2><?php esc_html_e('Create New Offer', 'vortex-ai-marketplace'); ?></h2>
    
    <form id="vortex-offer-form" class="vortex-form">
        <?php wp_nonce_field('vortex_create_offer', 'offer_nonce'); ?>
        
        <div class="form-group">
            <label for="offer_title"><?php esc_html_e('Offer Title', 'vortex-ai-marketplace'); ?></label>
            <input type="text" 
                   name="offer_title" 
                   id="offer_title" 
                   required 
                   class="vortex-input">
            <p class="description">
                <?php esc_html_e('Enter a descriptive title for your offer.', 'vortex-ai-marketplace'); ?>
            </p>
        </div>
        
        <div class="form-group">
            <label for="offer_description"><?php esc_html_e('Offer Description', 'vortex-ai-marketplace'); ?></label>
            <textarea name="offer_description" 
                      id="offer_description" 
                      required 
                      class="vortex-textarea"
                      rows="5"></textarea>
            <p class="description">
                <?php esc_html_e('Provide detailed information about your offer.', 'vortex-ai-marketplace'); ?>
            </p>
        </div>
        
        <div class="form-group">
            <label for="offer_type"><?php esc_html_e('Offer Type', 'vortex-ai-marketplace'); ?></label>
            <select name="offer_type" 
                    id="offer_type" 
                    required 
                    class="vortex-input">
                <option value=""><?php esc_html_e('Select an offer type', 'vortex-ai-marketplace'); ?></option>
                <option value="artwork"><?php esc_html_e('Artwork', 'vortex-ai-marketplace'); ?></option>
                <option value="service"><?php esc_html_e('Service', 'vortex-ai-marketplace'); ?></option>
                <option value="collaboration"><?php esc_html_e('Collaboration', 'vortex-ai-marketplace'); ?></option>
            </select>
            <p class="description">
                <?php esc_html_e('Select the type of offer you are making.', 'vortex-ai-marketplace'); ?>
            </p>
        </div>
        
        <div class="form-group">
            <label for="offer_amount"><?php esc_html_e('Offer Amount (TOLA)', 'vortex-ai-marketplace'); ?></label>
            <input type="number" 
                   name="offer_amount" 
                   id="offer_amount" 
                   required 
                   min="0"
                   step="0.01"
                   class="vortex-input">
            <p class="description">
                <?php esc_html_e('Enter the amount in TOLA tokens.', 'vortex-ai-marketplace'); ?>
            </p>
        </div>
        
        <div class="form-group">
            <label for="offer_deadline"><?php esc_html_e('Offer Deadline', 'vortex-ai-marketplace'); ?></label>
            <input type="datetime-local" 
                   name="offer_deadline" 
                   id="offer_deadline" 
                   required 
                   min="<?php echo esc_attr(date('Y-m-d\TH:i', strtotime('+1 day'))); ?>"
                   class="vortex-input">
            <p class="description">
                <?php esc_html_e('Select when this offer expires.', 'vortex-ai-marketplace'); ?>
            </p>
        </div>
        
        <div class="form-group">
            <label for="offer_terms"><?php esc_html_e('Terms and Conditions', 'vortex-ai-marketplace'); ?></label>
            <textarea name="offer_terms" 
                      id="offer_terms" 
                      required 
                      class="vortex-textarea"
                      rows="3"></textarea>
            <p class="description">
                <?php esc_html_e('Specify the terms and conditions of your offer.', 'vortex-ai-marketplace'); ?>
            </p>
        </div>
        
        <div class="form-group">
            <label for="offer_attachments"><?php esc_html_e('Attachments', 'vortex-ai-marketplace'); ?></label>
            <input type="file" 
                   name="offer_attachments[]" 
                   id="offer_attachments" 
                   multiple
                   class="vortex-input">
            <p class="description">
                <?php esc_html_e('Upload any relevant files (optional).', 'vortex-ai-marketplace'); ?>
            </p>
        </div>
        
        <div class="form-group">
            <button type="submit" class="vortex-button vortex-button-primary">
                <?php esc_html_e('Create Offer', 'vortex-ai-marketplace'); ?>
            </button>
        </div>
        
        <div id="offer-message" class="vortex-message" style="display: none;"></div>
    </form>
    
    <script>
    jQuery(document).ready(function($) {
        const form = $('#vortex-offer-form');
        const message = $('#offer-message');
        
        // Handle form submission
        form.on('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'vortex_create_offer');
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
                        // Optionally redirect to the offer page
                        if (response.data.redirect_url) {
                            window.location.href = response.data.redirect_url;
                        }
                    } else {
                        showMessage('error', response.data.message);
                    }
                },
                error: function() {
                    showMessage('error', '<?php esc_html_e('Failed to create offer. Please try again.', 'vortex-ai-marketplace'); ?>');
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
    .vortex-offer-form {
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