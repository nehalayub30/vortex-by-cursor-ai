<?php
/**
 * Template for the career path interface
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    exit;
}

// Get user data
$user_id = get_current_user_id();
$career_stage = get_user_meta($user_id, 'vortex_career_stage', true);
$career_goals = get_user_meta($user_id, 'vortex_career_goals', true);
$interests = get_user_meta($user_id, 'vortex_interests', true);
$skills = get_user_meta($user_id, 'vortex_user_skills', true);
$recommendations = get_user_meta($user_id, 'vortex_career_recommendations', true);

// Define career stages
$career_stages = array(
    'beginner' => __('Beginner Artist', 'vortex-ai-marketplace'),
    'emerging' => __('Emerging Artist', 'vortex-ai-marketplace'),
    'established' => __('Established Artist', 'vortex-ai-marketplace'),
    'professional' => __('Professional Artist', 'vortex-ai-marketplace'),
    'master' => __('Master Artist', 'vortex-ai-marketplace')
);

// Define common interests
$common_interests = array(
    'digital-art' => __('Digital Art', 'vortex-ai-marketplace'),
    'traditional-art' => __('Traditional Art', 'vortex-ai-marketplace'),
    'ai-generated-art' => __('AI Generated Art', 'vortex-ai-marketplace'),
    'photography' => __('Photography', 'vortex-ai-marketplace'),
    'sculpture' => __('Sculpture', 'vortex-ai-marketplace'),
    'mixed-media' => __('Mixed Media', 'vortex-ai-marketplace'),
    'illustration' => __('Illustration', 'vortex-ai-marketplace'),
    'animation' => __('Animation', 'vortex-ai-marketplace'),
    'concept-art' => __('Concept Art', 'vortex-ai-marketplace'),
    'nft-art' => __('NFT Art', 'vortex-ai-marketplace')
);

// Define common skills
$common_skills = array(
    'digital-painting' => __('Digital Painting', 'vortex-ai-marketplace'),
    'color-theory' => __('Color Theory', 'vortex-ai-marketplace'),
    'composition' => __('Composition', 'vortex-ai-marketplace'),
    'perspective' => __('Perspective', 'vortex-ai-marketplace'),
    'character-design' => __('Character Design', 'vortex-ai-marketplace'),
    'landscape' => __('Landscape', 'vortex-ai-marketplace'),
    'portrait' => __('Portrait', 'vortex-ai-marketplace'),
    'photoshop' => __('Photoshop', 'vortex-ai-marketplace'),
    'procreate' => __('Procreate', 'vortex-ai-marketplace'),
    'ai-prompt-engineering' => __('AI Prompt Engineering', 'vortex-ai-marketplace'),
    'midjourney' => __('Midjourney', 'vortex-ai-marketplace'),
    'stable-diffusion' => __('Stable Diffusion', 'vortex-ai-marketplace'),
    'dall-e' => __('DALL-E', 'vortex-ai-marketplace'),
    'blender' => __('Blender', 'vortex-ai-marketplace'),
    '3d-modeling' => __('3D Modeling', 'vortex-ai-marketplace')
);
?>

<div class="vortex-career-path">
    <h2><?php _e('Your Art Career Path', 'vortex-ai-marketplace'); ?></h2>
    
    <div class="vortex-intro">
        <p><?php _e('Define your art career path to receive personalized recommendations from our AI business strategist. We\'ll help you set realistic goals, identify opportunities, and connect with the right resources.', 'vortex-ai-marketplace'); ?></p>
    </div>
    
    <div class="vortex-career-form-container">
        <form id="vortex-career-form" class="vortex-form">
            <?php wp_nonce_field('vortex_career_project_nonce', 'career_nonce'); ?>
            
            <div class="vortex-form-group">
                <label for="career-stage"><?php _e('Current Career Stage', 'vortex-ai-marketplace'); ?></label>
                <select id="career-stage" name="career_stage" required>
                    <option value=""><?php _e('Select your current stage', 'vortex-ai-marketplace'); ?></option>
                    <?php foreach ($career_stages as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($career_stage, $value); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="vortex-form-group">
                <label for="career-goals"><?php _e('Career Goals', 'vortex-ai-marketplace'); ?></label>
                <textarea id="career-goals" name="career_goals" rows="4" placeholder="<?php esc_attr_e('Describe your art career goals and aspirations...', 'vortex-ai-marketplace'); ?>" required><?php echo esc_textarea($career_goals); ?></textarea>
            </div>
            
            <div class="vortex-form-group">
                <label><?php _e('Artistic Interests', 'vortex-ai-marketplace'); ?></label>
                <div class="vortex-checkbox-group">
                    <?php 
                    if (!is_array($interests)) {
                        $interests = array();
                    }
                    
                    foreach ($common_interests as $value => $label) : 
                    ?>
                        <label class="vortex-checkbox">
                            <input type="checkbox" name="interests[]" value="<?php echo esc_attr($value); ?>" <?php checked(in_array($value, $interests), true); ?>>
                            <?php echo esc_html($label); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="vortex-form-group">
                <label><?php _e('Skills & Expertise', 'vortex-ai-marketplace'); ?></label>
                <div class="vortex-checkbox-group">
                    <?php 
                    if (!is_array($skills)) {
                        $skills = array();
                    }
                    
                    foreach ($common_skills as $value => $label) : 
                    ?>
                        <label class="vortex-checkbox">
                            <input type="checkbox" name="skills[]" value="<?php echo esc_attr($value); ?>" <?php checked(in_array($value, $skills), true); ?>>
                            <?php echo esc_html($label); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="vortex-form-actions">
                <button type="submit" class="vortex-button vortex-button-primary">
                    <?php _e('Update Career Path', 'vortex-ai-marketplace'); ?>
                </button>
            </div>
            
            <div id="vortex-career-message" class="vortex-message" style="display: none;"></div>
        </form>
    </div>
    
    <?php if (!empty($recommendations)) : ?>
        <div class="vortex-recommendations">
            <h3><?php _e('AI-Powered Career Recommendations', 'vortex-ai-marketplace'); ?></h3>
            
            <?php if (!empty($recommendations['next_steps'])) : ?>
                <div class="vortex-recommendation-section">
                    <h4><?php _e('Next Steps', 'vortex-ai-marketplace'); ?></h4>
                    <ul>
                        <?php foreach ($recommendations['next_steps'] as $step) : ?>
                            <li><?php echo esc_html($step); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($recommendations['resources'])) : ?>
                <div class="vortex-recommendation-section">
                    <h4><?php _e('Recommended Resources', 'vortex-ai-marketplace'); ?></h4>
                    <ul>
                        <?php foreach ($recommendations['resources'] as $resource) : ?>
                            <li><?php echo esc_html($resource); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($recommendations['milestones'])) : ?>
                <div class="vortex-recommendation-section">
                    <h4><?php _e('Career Milestones', 'vortex-ai-marketplace'); ?></h4>
                    <ul>
                        <?php foreach ($recommendations['milestones'] as $milestone) : ?>
                            <li><?php echo esc_html($milestone); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    $('#vortex-career-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitButton = $form.find('button[type="submit"]');
        var $message = $('#vortex-career-message');
        
        // Disable submit button and show loading state
        $submitButton.prop('disabled', true).text('<?php _e('Processing...', 'vortex-ai-marketplace'); ?>');
        $message.removeClass('vortex-message-error vortex-message-success')
                .addClass('vortex-message-info')
                .text('<?php _e('Generating AI recommendations...', 'vortex-ai-marketplace'); ?>')
                .show();
        
        // Prepare form data
        var formData = new FormData(this);
        formData.append('action', 'vortex_submit_career_path');
        formData.append('nonce', vortex_career.nonce);
        
        // Send AJAX request
        $.ajax({
            url: vortex_career.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $message.removeClass('vortex-message-info vortex-message-error')
                            .addClass('vortex-message-success')
                            .text(response.data.message);
                    
                    // Reload page to show new recommendations
                    setTimeout(function() {
                        window.location.reload();
                    }, 1500);
                } else {
                    $message.removeClass('vortex-message-info vortex-message-success')
                            .addClass('vortex-message-error')
                            .text(response.data.message);
                }
            },
            error: function() {
                $message.removeClass('vortex-message-info vortex-message-success')
                        .addClass('vortex-message-error')
                        .text(vortex_career.i18n.error);
            },
            complete: function() {
                // Re-enable submit button
                $submitButton.prop('disabled', false).text('<?php _e('Update Career Path', 'vortex-ai-marketplace'); ?>');
            }
        });
    });
});
</script>

<style>
.vortex-career-path {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.vortex-intro {
    margin-bottom: 20px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 4px;
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
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.vortex-checkbox-group {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 5px;
}

.vortex-checkbox {
    display: flex;
    align-items: center;
    font-weight: normal;
    padding: 5px 10px;
    background: #f0f0f0;
    border-radius: 4px;
    cursor: pointer;
}

.vortex-checkbox input {
    margin-right: 5px;
}

.vortex-button {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: 600;
}

.vortex-button-primary {
    background: #0073aa;
    color: #fff;
}

.vortex-button-primary:hover {
    background: #005a87;
}

.vortex-message {
    margin-top: 15px;
    padding: 10px;
    border-radius: 4px;
}

.vortex-message-info {
    background-color: #d1ecf1;
    border: 1px solid #bee5eb;
    color: #0c5460;
}

.vortex-message-success {
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.vortex-message-error {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.vortex-recommendations {
    margin-top: 30px;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 4px;
}

.vortex-recommendation-section {
    margin-bottom: 20px;
}

.vortex-recommendation-section h4 {
    margin-bottom: 10px;
    padding-bottom: 5px;
    border-bottom: 1px solid #eee;
}

.vortex-recommendation-section ul {
    margin: 0;
    padding-left: 20px;
}

.vortex-recommendation-section li {
    margin-bottom: 5px;
}
</style> 