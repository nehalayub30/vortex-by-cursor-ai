<?php
/**
 * Template for business idea input form
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// Exit if accessed directly
if (!defined('WPINC')) {
    die;
}
?>

<div class="vortex-business-intake-container <?php echo esc_attr($atts['class']); ?>">
    <div class="vortex-business-intake-header">
        <h2 class="vortex-form-title"><?php _e('Share Your Creative Vision', 'vortex-ai-marketplace'); ?></h2>
        <p class="vortex-form-subtitle"><?php _e('Our Business Strategist AI will analyze your idea and create a personalized business plan with actionable milestones.', 'vortex-ai-marketplace'); ?></p>
    </div>
    
    <div class="vortex-tabs">
        <div class="vortex-tab-headers">
            <div class="vortex-tab-header active" data-tab="vision"><?php _e('Your Vision', 'vortex-ai-marketplace'); ?></div>
            <div class="vortex-tab-header" data-tab="details"><?php _e('Details', 'vortex-ai-marketplace'); ?></div>
            <div class="vortex-tab-header" data-tab="goals"><?php _e('Goals', 'vortex-ai-marketplace'); ?></div>
        </div>
        
        <form id="vortex-business-idea-form" class="vortex-form">
            <div class="vortex-tab-content active" data-tab="vision">
                <div class="vortex-form-row">
                    <label for="business_idea"><?php _e('Describe your creative business idea in detail', 'vortex-ai-marketplace'); ?></label>
                    <textarea id="business_idea" name="business_idea" rows="8" placeholder="<?php esc_attr_e('What creative business or artistic venture would you like to build? The more details you provide, the better our AI can help you develop a strategic plan.', 'vortex-ai-marketplace'); ?>"></textarea>
                    <div class="vortex-textarea-counter">
                        <span id="character-count">0</span> <?php _e('characters (no limit)', 'vortex-ai-marketplace'); ?>
                    </div>
                </div>
                
                <div class="vortex-form-actions">
                    <button type="button" class="vortex-next-tab" data-next="details"><?php _e('Next: Details', 'vortex-ai-marketplace'); ?></button>
                </div>
            </div>
            
            <div class="vortex-tab-content" data-tab="details">
                <div class="vortex-form-row">
                    <label for="experience_level"><?php _e('What is your experience level in this field?', 'vortex-ai-marketplace'); ?></label>
                    <select id="experience_level" name="experience_level" required>
                        <option value="beginner"><?php _e('Beginner - Just starting out', 'vortex-ai-marketplace'); ?></option>
                        <option value="intermediate"><?php _e('Intermediate - Some experience', 'vortex-ai-marketplace'); ?></option>
                        <option value="advanced"><?php _e('Advanced - Experienced professional', 'vortex-ai-marketplace'); ?></option>
                        <option value="expert"><?php _e('Expert - Industry veteran', 'vortex-ai-marketplace'); ?></option>
                    </select>
                </div>
                
                <div class="vortex-form-row">
                    <label for="time_commitment"><?php _e('How much time can you commit weekly?', 'vortex-ai-marketplace'); ?></label>
                    <select id="time_commitment" name="time_commitment" required>
                        <option value="minimal"><?php _e('Minimal (0-5 hours/week)', 'vortex-ai-marketplace'); ?></option>
                        <option value="part_time"><?php _e('Part-time (5-20 hours/week)', 'vortex-ai-marketplace'); ?></option>
                        <option value="full_time"><?php _e('Full-time (20-40 hours/week)', 'vortex-ai-marketplace'); ?></option>
                        <option value="intensive"><?php _e('Intensive (40+ hours/week)', 'vortex-ai-marketplace'); ?></option>
                    </select>
                </div>
                
                <div class="vortex-form-row">
                    <label for="investment_level"><?php _e('What is your potential investment level?', 'vortex-ai-marketplace'); ?></label>
                    <select id="investment_level" name="investment_level" required>
                        <option value="minimal"><?php _e('Minimal - Bootstrap only', 'vortex-ai-marketplace'); ?></option>
                        <option value="small"><?php _e('Small - Under $5,000', 'vortex-ai-marketplace'); ?></option>
                        <option value="medium"><?php _e('Medium - $5,000 to $25,000', 'vortex-ai-marketplace'); ?></option>
                        <option value="large"><?php _e('Large - Over $25,000', 'vortex-ai-marketplace'); ?></option>
                    </select>
                </div>
                
                <div class="vortex-form-actions">
                    <button type="button" class="vortex-prev-tab" data-prev="vision"><?php _e('Previous', 'vortex-ai-marketplace'); ?></button>
                    <button type="button" class="vortex-next-tab" data-next="goals"><?php _e('Next: Goals', 'vortex-ai-marketplace'); ?></button>
                </div>
            </div>
            
            <div class="vortex-tab-content" data-tab="goals">
                <div class="vortex-form-row">
                    <label for="primary_goal"><?php _e('What is your primary goal?', 'vortex-ai-marketplace'); ?></label>
                    <select id="primary_goal" name="primary_goal" required>
                        <option value="income"><?php _e('Generate income from my art/creativity', 'vortex-ai-marketplace'); ?></option>
                        <option value="recognition"><?php _e('Gain recognition in my field', 'vortex-ai-marketplace'); ?></option>
                        <option value="impact"><?php _e('Make an impact with my creative work', 'vortex-ai-marketplace'); ?></option>
                        <option value="scale"><?php _e('Scale an existing creative business', 'vortex-ai-marketplace'); ?></option>
                        <option value="innovation"><?php _e('Innovate within my creative field', 'vortex-ai-marketplace'); ?></option>
                    </select>
                </div>
                
                <div class="vortex-form-row">
                    <label><?php _e('What challenges concern you the most?', 'vortex-ai-marketplace'); ?></label>
                    <div class="vortex-checkbox-group">
                        <label class="vortex-checkbox-label">
                            <input type="checkbox" name="challenges[]" value="funding">
                            <?php _e('Finding funding or investment', 'vortex-ai-marketplace'); ?>
                        </label>
                        <label class="vortex-checkbox-label">
                            <input type="checkbox" name="challenges[]" value="audience">
                            <?php _e('Building an audience/client base', 'vortex-ai-marketplace'); ?>
                        </label>
                        <label class="vortex-checkbox-label">
                            <input type="checkbox" name="challenges[]" value="time">
                            <?php _e('Time management/productivity', 'vortex-ai-marketplace'); ?>
                        </label>
                        <label class="vortex-checkbox-label">
                            <input type="checkbox" name="challenges[]" value="skills">
                            <?php _e('Developing necessary skills', 'vortex-ai-marketplace'); ?>
                        </label>
                        <label class="vortex-checkbox-label">
                            <input type="checkbox" name="challenges[]" value="competition">
                            <?php _e('Standing out from competition', 'vortex-ai-marketplace'); ?>
                        </label>
                        <label class="vortex-checkbox-label">
                            <input type="checkbox" name="challenges[]" value="pricing">
                            <?php _e('Pricing my work appropriately', 'vortex-ai-marketplace'); ?>
                        </label>
                    </div>
                </div>
                
                <div class="vortex-form-row">
                    <label for="timeline"><?php _e('What is your timeline for achieving initial success?', 'vortex-ai-marketplace'); ?></label>
                    <select id="timeline" name="timeline" required>
                        <option value="short"><?php _e('Short term (1-3 months)', 'vortex-ai-marketplace'); ?></option>
                        <option value="medium"><?php _e('Medium term (3-12 months)', 'vortex-ai-marketplace'); ?></option>
                        <option value="long"><?php _e('Long term (1-3 years)', 'vortex-ai-marketplace'); ?></option>
                        <option value="very_long"><?php _e('Very long term (3+ years)', 'vortex-ai-marketplace'); ?></option>
                    </select>
                </div>
                
                <div class="vortex-form-actions">
                    <button type="button" class="vortex-prev-tab" data-prev="details"><?php _e('Previous', 'vortex-ai-marketplace'); ?></button>
                    <?php wp_nonce_field('vortex_business_idea_nonce', 'business_idea_nonce'); ?>
                    <input type="hidden" name="action" value="vortex_process_business_idea">
                    <button type="submit" class="vortex-submit-button"><?php _e('Generate Business Plan', 'vortex-ai-marketplace'); ?></button>
                </div>
            </div>
        </form>
    </div>
    
    <div id="vortex-business-plan-result" class="vortex-business-plan-result" style="display: none;">
        <div class="vortex-loading-spinner">
            <div class="vortex-spinner"></div>
            <p><?php _e('Our Business Strategist AI is analyzing your idea and creating your personalized plan...', 'vortex-ai-marketplace'); ?></p>
        </div>
        <div class="vortex-business-plan-content"></div>
    </div>
    
    <div id="vortex-notification-prompt" class="vortex-notification-prompt" style="display: none;">
        <div class="vortex-notification-box">
            <h3><?php _e('Stay on Track with Your Business Plan', 'vortex-ai-marketplace'); ?></h3>
            <p><?php _e('Would you like to receive daily motivation and personalized tips to help you achieve your business goals?', 'vortex-ai-marketplace'); ?></p>
            <div class="vortex-notification-actions">
                <button type="button" id="vortex-enable-notifications" class="vortex-button vortex-primary-button"><?php _e('Enable Notifications', 'vortex-ai-marketplace'); ?></button>
                <button type="button" id="vortex-skip-notifications" class="vortex-button vortex-secondary-button"><?php _e('Maybe Later', 'vortex-ai-marketplace'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab navigation
    $('.vortex-tab-header').click(function() {
        const tabId = $(this).data('tab');
        $('.vortex-tab-header').removeClass('active');
        $(this).addClass('active');
        $('.vortex-tab-content').removeClass('active');
        $('.vortex-tab-content[data-tab="' + tabId + '"]').addClass('active');
    });
    
    // Next tab buttons
    $('.vortex-next-tab').click(function() {
        const nextTab = $(this).data('next');
        $('.vortex-tab-header').removeClass('active');
        $('.vortex-tab-header[data-tab="' + nextTab + '"]').addClass('active');
        $('.vortex-tab-content').removeClass('active');
        $('.vortex-tab-content[data-tab="' + nextTab + '"]').addClass('active');
    });
    
    // Previous tab buttons
    $('.vortex-prev-tab').click(function() {
        const prevTab = $(this).data('prev');
        $('.vortex-tab-header').removeClass('active');
        $('.vortex-tab-header[data-tab="' + prevTab + '"]').addClass('active');
        $('.vortex-tab-content').removeClass('active');
        $('.vortex-tab-content[data-tab="' + prevTab + '"]').addClass('active');
    });
    
    // Character counter
    $('#business_idea').on('input', function() {
        const count = $(this).val().length;
        $('#character-count').text(count);
    });
    
    // Form submission
    $('#vortex-business-idea-form').submit(function(e) {
        e.preventDefault();
        
        // Validate business idea not empty
        if ($('#business_idea').val().trim() === '') {
            alert('<?php _e("Please describe your business idea", "vortex-ai-marketplace"); ?>');
            $('.vortex-tab-header[data-tab="vision"]').click();
            return false;
        }
        
        // Show loading state
        $('#vortex-business-idea-form').hide();
        $('#vortex-business-plan-result').show();
        $('.vortex-loading-spinner').show();
        $('.vortex-business-plan-content').hide();
        
        // Submit form data
        $.ajax({
            url: vortex_ajax.ajax_url,
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                $('.vortex-loading-spinner').hide();
                
                if (response.success) {
                    $('.vortex-business-plan-content').html(response.data.html).show();
                    
                    // Show notification prompt after 2 seconds
                    setTimeout(function() {
                        $('#vortex-notification-prompt').fadeIn();
                    }, 2000);
                } else {
                    $('.vortex-business-plan-content').html('<div class="vortex-error">' + response.data.message + '</div>').show();
                }
            },
            error: function() {
                $('.vortex-loading-spinner').hide();
                $('.vortex-business-plan-content').html('<div class="vortex-error"><?php _e("An error occurred. Please try again.", "vortex-ai-marketplace"); ?></div>').show();
            }
        });
    });
    
    // Handle notification buttons
    $('#vortex-enable-notifications').click(function() {
        // Request notification permission
        if ('Notification' in window) {
            Notification.requestPermission().then(function(permission) {
                if (permission === 'granted') {
                    // Save user preference
                    $.ajax({
                        url: vortex_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'vortex_enable_notifications',
                            nonce: vortex_ajax.notifications_nonce
                        }
                    });
                    
                    // Show success message
                    $('#vortex-notification-prompt').html('<div class="vortex-success-message"><p><?php _e("Notifications enabled! We'll send you daily insights and motivation.", "vortex-ai-marketplace"); ?></p></div>');
                    
                    // Hide after 3 seconds
                    setTimeout(function() {
                        $('#vortex-notification-prompt').fadeOut();
                    }, 3000);
                }
            });
        }
    });
    
    $('#vortex-skip-notifications').click(function() {
        $('#vortex-notification-prompt').fadeOut();
    });
});
</script> 