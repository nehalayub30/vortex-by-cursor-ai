<?php
/**
 * Template for displaying collaboration creation form
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// Exit if accessed directly
if (!defined('WPINC')) {
    die;
}
?>

<div class="vortex-collaboration-form-container">
    <h2 class="vortex-form-title"><?php _e('Create a New Collaboration', 'vortex-ai-marketplace'); ?></h2>
    
    <p class="vortex-form-description">
        <?php _e('Start a collaborative art project with other Vortex creators. Define your vision, set your terms, and find the perfect collaborators.', 'vortex-ai-marketplace'); ?>
    </p>
    
    <form id="vortex-collaboration-form" class="vortex-collaboration-form">
        <div class="vortex-form-row">
            <label for="collab_title"><?php _e('Collaboration Title', 'vortex-ai-marketplace'); ?></label>
            <input type="text" id="collab_title" name="collab_title" required placeholder="<?php esc_attr_e('Give your collaboration a compelling title', 'vortex-ai-marketplace'); ?>">
        </div>
        
        <div class="vortex-form-row">
            <label for="collab_description"><?php _e('Description', 'vortex-ai-marketplace'); ?></label>
            <textarea id="collab_description" name="collab_description" rows="4" required placeholder="<?php esc_attr_e('Describe your collaboration concept, goals, and what you\'re looking for in collaborators', 'vortex-ai-marketplace'); ?>"></textarea>
        </div>
        
        <div class="vortex-form-row">
            <label for="collab_type"><?php _e('Collaboration Type', 'vortex-ai-marketplace'); ?></label>
            <select id="collab_type" name="collab_type" required>
                <option value=""><?php _e('Select collaboration type', 'vortex-ai-marketplace'); ?></option>
                <?php foreach ($types as $type) : ?>
                <option value="<?php echo esc_attr($type->term_id); ?>"><?php echo esc_html($type->name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="vortex-form-row">
            <label for="collab_max_participants"><?php _e('Maximum Participants', 'vortex-ai-marketplace'); ?></label>
            <input type="number" id="collab_max_participants" name="collab_max_participants" min="2" max="10" value="3">
            <p class="vortex-field-hint"><?php _e('Set a limit for the number of collaborators (including you)', 'vortex-ai-marketplace'); ?></p>
        </div>
        
        <div class="vortex-form-row">
            <label for="collab_deadline"><?php _e('Application Deadline', 'vortex-ai-marketplace'); ?></label>
            <input type="date" id="collab_deadline" name="collab_deadline" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
            <p class="vortex-field-hint"><?php _e('When should applications for collaboration close?', 'vortex-ai-marketplace'); ?></p>
        </div>
        
        <div class="vortex-form-row vortex-smart-contract-section">
            <div class="vortex-section-header">
                <h3><?php _e('Smart Contract Terms', 'vortex-ai-marketplace'); ?></h3>
                <p><?php _e('Define the blockchain-verified terms for this collaboration', 'vortex-ai-marketplace'); ?></p>
            </div>
            
            <div class="vortex-smart-contract-options">
                <div class="vortex-checkbox-row">
                    <label class="vortex-checkbox-label">
                        <input type="checkbox" id="enable_smart_contract" name="enable_smart_contract" checked>
                        <?php _e('Enable Smart Contract for this collaboration', 'vortex-ai-marketplace'); ?>
                    </label>
                </div>
                
                <div class="vortex-contract-details" id="smart_contract_details">
                    <div class="vortex-form-row">
                        <label for="revenue_split"><?php _e('Your Revenue Share (%)', 'vortex-ai-marketplace'); ?></label>
                        <input type="range" id="revenue_split" name="revenue_split" min="5" max="80" value="50" step="5">
                        <div class="vortex-range-value-display">
                            <span id="revenue_split_display">50%</span>
                            <span class="vortex-range-hint"><?php _e('Remaining percentage will be split among collaborators', 'vortex-ai-marketplace'); ?></span>
                        </div>
                    </div>
                    
                    <div class="vortex-form-row">
                        <label for="contract_duration"><?php _e('Contract Duration', 'vortex-ai-marketplace'); ?></label>
                        <select id="contract_duration" name="contract_duration">
                            <option value="1"><?php _e('1 year', 'vortex-ai-marketplace'); ?></option>
                            <option value="2"><?php _e('2 years', 'vortex-ai-marketplace'); ?></option>
                            <option value="5" selected><?php _e('5 years', 'vortex-ai-marketplace'); ?></option>
                            <option value="10"><?php _e('10 years', 'vortex-ai-marketplace'); ?></option>
                            <option value="0"><?php _e('Perpetual', 'vortex-ai-marketplace'); ?></option>
                        </select>
                    </div>
                    
                    <div class="vortex-form-row">
                        <label for="license_terms"><?php _e('License Terms', 'vortex-ai-marketplace'); ?></label>
                        <select id="license_terms" name="license_terms">
                            <option value="exclusive"><?php _e('Exclusive (only sold through Vortex)', 'vortex-ai-marketplace'); ?></option>
                            <option value="non-exclusive" selected><?php _e('Non-exclusive (can be sold elsewhere)', 'vortex-ai-marketplace'); ?></option>
                            <option value="time-limited"><?php _e('Time-limited exclusivity (1 year)', 'vortex-ai-marketplace'); ?></option>
                        </select>
                    </div>
                    
                    <div class="vortex-form-row">
                        <label for="min_price"><?php _e('Minimum Sale Price (TOLA)', 'vortex-ai-marketplace'); ?></label>
                        <input type="number" id="min_price" name="min_price" min="5" value="50">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="vortex-form-row vortex-skills-section">
            <div class="vortex-section-header">
                <h3><?php _e('Required Skills', 'vortex-ai-marketplace'); ?></h3>
                <p><?php _e('Select skills you're looking for in potential collaborators', 'vortex-ai-marketplace'); ?></p>
            </div>
            
            <div class="vortex-skills-selection">
                <?php
                $skills = array(
                    'digital_painting' => __('Digital Painting', 'vortex-ai-marketplace'),
                    'character_design' => __('Character Design', 'vortex-ai-marketplace'),
                    'concept_art' => __('Concept Art', 'vortex-ai-marketplace'),
                    'vector_illustration' => __('Vector Illustration', 'vortex-ai-marketplace'),
                    'photo_manipulation' => __('Photo Manipulation', 'vortex-ai-marketplace'),
                    '3d_modeling' => __('3D Modeling', 'vortex-ai-marketplace'),
                    'animation' => __('Animation', 'vortex-ai-marketplace'),
                    'ui_design' => __('UI Design', 'vortex-ai-marketplace'),
                    'texturing' => __('Texturing', 'vortex-ai-marketplace'),
                    'programming' => __('Programming', 'vortex-ai-marketplace'),
                );
                
                foreach ($skills as $skill_id => $skill_name) :
                ?>
                <label class="vortex-checkbox-label vortex-skill-checkbox">
                    <input type="checkbox" name="required_skills[]" value="<?php echo esc_attr($skill_id); ?>">
                    <?php echo esc_html($skill_name); ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="vortex-form-row">
            <label for="collab_image"><?php _e('Cover Image', 'vortex-ai-marketplace'); ?></label>
            <div class="vortex-image-upload-container">
                <div id="collab_image_preview" class="vortex-image-preview"></div>
                <input type="file" id="collab_image" name="collab_image" accept="image/*">
                <button type="button" id="collab_image_button" class="vortex-upload-button">
                    <?php _e('Select Image', 'vortex-ai-marketplace'); ?>
                </button>
            </div>
        </div>
        
        <div class="vortex-form-row vortex-terms-agreement">
            <label class="vortex-checkbox-label">
                <input type="checkbox" id="collab_terms" name="collab_terms" required>
                <?php _e('I agree to the Vortex Collaboration Terms and understand that all agreements will be enforced via smart contracts on the blockchain', 'vortex-ai-marketplace'); ?>
            </label>
        </div>
        
        <div class="vortex-form-row vortex-button-row">
            <input type="hidden" name="action" value="vortex_create_collaboration">
            <?php wp_nonce_field('vortex_collab_nonce', 'collab_nonce'); ?>
            
            <div class="vortex-cost-notice">
                <?php _e('Creating this collaboration will cost', 'vortex-ai-marketplace'); ?>
                <span class="vortex-tola-cost">10 TOLA</span>
            </div>
            
            <button type="submit" id="create_collaboration_button" class="vortex-submit-button vortex-create-button">
                <?php _e('Create Collaboration', 'vortex-ai-marketplace'); ?>
            </button>
        </div>
    </form>
    
    <div id="vortex-collaboration-message" class="vortex-collaboration-message"></div>
</div>

<script>
jQuery(document).ready(function($) {
    // Update revenue split display
    $('#revenue_split').on('input', function() {
        $('#revenue_split_display').text($(this).val() + '%');
    });
    
    // Toggle smart contract details
    $('#enable_smart_contract').change(function() {
        if($(this).is(':checked')) {
            $('#smart_contract_details').slideDown();
        } else {
            $('#smart_contract_details').slideUp();
        }
    });
    
    // Image upload preview
    $('#collab_image').change(function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#collab_image_preview').html('<img src="' + e.target.result + '" alt="Preview">');
            }
            reader.readAsDataURL(file);
        }
    });
    
    $('#collab_image_button').click(function() {
        $('#collab_image').click();
    });
});
</script> 