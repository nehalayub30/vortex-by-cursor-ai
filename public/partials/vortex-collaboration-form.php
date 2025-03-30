<?php
/**
 * Template for the collaboration creation form.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="vortex-collaboration-form">
    <h2><?php esc_html_e('Create New Collaboration', 'vortex-ai-marketplace'); ?></h2>
    
    <form id="vortex-collaboration-form" class="vortex-form">
        <?php wp_nonce_field('vortex_create_collaboration', 'collaboration_nonce'); ?>
        
        <div class="form-group">
            <label for="collaboration_title"><?php esc_html_e('Collaboration Title', 'vortex-ai-marketplace'); ?></label>
            <input type="text" 
                   name="collaboration_title" 
                   id="collaboration_title" 
                   required 
                   class="vortex-input">
            <p class="description">
                <?php esc_html_e('Enter a descriptive title for your collaboration.', 'vortex-ai-marketplace'); ?>
            </p>
        </div>
        
        <div class="form-group">
            <label for="collaboration_description"><?php esc_html_e('Collaboration Description', 'vortex-ai-marketplace'); ?></label>
            <textarea name="collaboration_description" 
                      id="collaboration_description" 
                      required 
                      class="vortex-textarea"
                      rows="5"></textarea>
            <p class="description">
                <?php esc_html_e('Provide detailed information about your collaboration project.', 'vortex-ai-marketplace'); ?>
            </p>
        </div>
        
        <div class="form-group">
            <label for="collaboration_type"><?php esc_html_e('Collaboration Type', 'vortex-ai-marketplace'); ?></label>
            <select name="collaboration_type" 
                    id="collaboration_type" 
                    required 
                    class="vortex-input">
                <option value=""><?php esc_html_e('Select a collaboration type', 'vortex-ai-marketplace'); ?></option>
                <option value="artwork"><?php esc_html_e('Artwork Creation', 'vortex-ai-marketplace'); ?></option>
                <option value="exhibition"><?php esc_html_e('Exhibition', 'vortex-ai-marketplace'); ?></option>
                <option value="project"><?php esc_html_e('Project', 'vortex-ai-marketplace'); ?></option>
            </select>
            <p class="description">
                <?php esc_html_e('Select the type of collaboration you are proposing.', 'vortex-ai-marketplace'); ?>
            </p>
        </div>
        
        <div class="form-group">
            <label for="collaboration_roles"><?php esc_html_e('Required Roles', 'vortex-ai-marketplace'); ?></label>
            <div class="role-checkboxes">
                <label class="checkbox-label">
                    <input type="checkbox" name="collaboration_roles[]" value="artist">
                    <?php esc_html_e('Artist', 'vortex-ai-marketplace'); ?>
                </label>
                <label class="checkbox-label">
                    <input type="checkbox" name="collaboration_roles[]" value="designer">
                    <?php esc_html_e('Designer', 'vortex-ai-marketplace'); ?>
                </label>
                <label class="checkbox-label">
                    <input type="checkbox" name="collaboration_roles[]" value="developer">
                    <?php esc_html_e('Developer', 'vortex-ai-marketplace'); ?>
                </label>
                <label class="checkbox-label">
                    <input type="checkbox" name="collaboration_roles[]" value="curator">
                    <?php esc_html_e('Curator', 'vortex-ai-marketplace'); ?>
                </label>
            </div>
            <p class="description">
                <?php esc_html_e('Select the roles needed for this collaboration.', 'vortex-ai-marketplace'); ?>
            </p>
        </div>
        
        <div class="form-group">
            <label for="collaboration_budget"><?php esc_html_e('Budget (TOLA)', 'vortex-ai-marketplace'); ?></label>
            <input type="number" 
                   name="collaboration_budget" 
                   id="collaboration_budget" 
                   required 
                   min="0"
                   step="0.01"
                   class="vortex-input">
            <p class="description">
                <?php esc_html_e('Enter the total budget for the collaboration in TOLA tokens.', 'vortex-ai-marketplace'); ?>
            </p>
        </div>
        
        <div class="form-group">
            <label for="collaboration_deadline"><?php esc_html_e('Project Deadline', 'vortex-ai-marketplace'); ?></label>
            <input type="datetime-local" 
                   name="collaboration_deadline" 
                   id="collaboration_deadline" 
                   required 
                   min="<?php echo esc_attr(date('Y-m-d\TH:i', strtotime('+1 month'))); ?>"
                   class="vortex-input">
            <p class="description">
                <?php esc_html_e('Select when the collaboration should be completed.', 'vortex-ai-marketplace'); ?>
            </p>
        </div>
        
        <div class="form-group">
            <label for="collaboration_requirements"><?php esc_html_e('Requirements', 'vortex-ai-marketplace'); ?></label>
            <textarea name="collaboration_requirements" 
                      id="collaboration_requirements" 
                      required 
                      class="vortex-textarea"
                      rows="3"></textarea>
            <p class="description">
                <?php esc_html_e('Specify any specific requirements or qualifications needed.', 'vortex-ai-marketplace'); ?>
            </p>
        </div>
        
        <div class="form-group">
            <label for="collaboration_attachments"><?php esc_html_e('Attachments', 'vortex-ai-marketplace'); ?></label>
            <input type="file" 
                   name="collaboration_attachments[]" 
                   id="collaboration_attachments" 
                   multiple
                   class="vortex-input">
            <p class="description">
                <?php esc_html_e('Upload any relevant files (optional).', 'vortex-ai-marketplace'); ?>
            </p>
        </div>
        
        <div class="form-group">
            <button type="submit" class="vortex-button vortex-button-primary">
                <?php esc_html_e('Create Collaboration', 'vortex-ai-marketplace'); ?>
            </button>
        </div>
        
        <div id="collaboration-message" class="vortex-message" style="display: none;"></div>
    </form>
    
    <script>
    jQuery(document).ready(function($) {
        const form = $('#vortex-collaboration-form');
        const message = $('#collaboration-message');
        
        // Handle form submission
        form.on('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'vortex_create_collaboration');
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
                        // Optionally redirect to the collaboration page
                        if (response.data.redirect_url) {
                            window.location.href = response.data.redirect_url;
                        }
                    } else {
                        showMessage('error', response.data.message);
                    }
                },
                error: function() {
                    showMessage('error', '<?php esc_html_e('Failed to create collaboration. Please try again.', 'vortex-ai-marketplace'); ?>');
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
    .vortex-collaboration-form {
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
    
    .role-checkboxes {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 10px;
        margin-top: 5px;
    }
    
    .checkbox-label {
        display: flex;
        align-items: center;
        gap: 5px;
        font-weight: normal;
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