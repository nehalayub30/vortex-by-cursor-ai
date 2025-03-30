<?php
/**
 * Template for user registration form
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// Exit if accessed directly
if (!defined('WPINC')) {
    die;
}
?>

<div class="vortex-registration-container <?php echo esc_attr($atts['class']); ?>">
    <h2 class="vortex-form-title"><?php _e('Create Your Vortex Account', 'vortex-ai-marketplace'); ?></h2>
    
    <form id="vortex-registration-form" class="vortex-form" method="post">
        <div class="vortex-form-row">
            <label for="vortex_username"><?php _e('Username', 'vortex-ai-marketplace'); ?></label>
            <input type="text" name="vortex_username" id="vortex_username" required>
        </div>
        
        <div class="vortex-form-row">
            <label for="vortex_email"><?php _e('Email', 'vortex-ai-marketplace'); ?></label>
            <input type="email" name="vortex_email" id="vortex_email" required>
        </div>
        
        <div class="vortex-form-row">
            <label for="vortex_password"><?php _e('Password', 'vortex-ai-marketplace'); ?></label>
            <input type="password" name="vortex_password" id="vortex_password" required>
        </div>
        
        <div class="vortex-form-row">
            <label for="vortex_password_confirm"><?php _e('Confirm Password', 'vortex-ai-marketplace'); ?></label>
            <input type="password" name="vortex_password_confirm" id="vortex_password_confirm" required>
        </div>
        
        <!-- User Role Selection -->
        <div class="vortex-form-row vortex-user-role">
            <label><?php _e('I am joining Vortex as a:', 'vortex-ai-marketplace'); ?></label>
            <div class="vortex-role-options">
                <label class="vortex-role-option">
                    <input type="radio" name="vortex_user_role" value="artist" checked>
                    <span class="vortex-role-icon artist-icon"></span>
                    <span class="vortex-role-label"><?php _e('Artist', 'vortex-ai-marketplace'); ?></span>
                    <span class="vortex-role-description"><?php _e('I create art and want to showcase my work', 'vortex-ai-marketplace'); ?></span>
                </label>
                
                <label class="vortex-role-option">
                    <input type="radio" name="vortex_user_role" value="collector">
                    <span class="vortex-role-icon collector-icon"></span>
                    <span class="vortex-role-label"><?php _e('Collector', 'vortex-ai-marketplace'); ?></span>
                    <span class="vortex-role-description"><?php _e('I collect and purchase art', 'vortex-ai-marketplace'); ?></span>
                </label>
            </div>
        </div>
        
        <!-- Artist Categories (shown/hidden with JS) -->
        <div class="vortex-form-row vortex-categories-section" id="vortex-artist-categories">
            <label><?php _e('Select up to 3 categories that best describe your art:', 'vortex-ai-marketplace'); ?></label>
            <div class="vortex-categories-grid">
                <?php
                $artist_categories = array(
                    'musician' => __('Musician', 'vortex-ai-marketplace'),
                    'choreographer' => __('Choreographer', 'vortex-ai-marketplace'),
                    'sculptor' => __('Sculptor', 'vortex-ai-marketplace'),
                    'fine_artist' => __('Fine Artist', 'vortex-ai-marketplace'),
                    'digital_artist' => __('Digital Artist', 'vortex-ai-marketplace'),
                    'film' => __('Film', 'vortex-ai-marketplace'),
                    'graphic_designer' => __('Graphic Designer', 'vortex-ai-marketplace'),
                    'fashion_designer' => __('Fashion Designer', 'vortex-ai-marketplace'),
                    'architect' => __('Architect', 'vortex-ai-marketplace'),
                    'interior_designer' => __('Interior Designer', 'vortex-ai-marketplace'),
                    'dancer' => __('Dancer', 'vortex-ai-marketplace'),
                    'other' => __('Other', 'vortex-ai-marketplace')
                );
                
                foreach ($artist_categories as $value => $label) {
                    echo '<label class="vortex-category-checkbox">';
                    echo '<input type="checkbox" name="vortex_artist_categories[]" value="' . esc_attr($value) . '" class="vortex-category-input" data-category-group="artist">';
                    echo '<span class="vortex-category-label">' . esc_html($label) . '</span>';
                    echo '</label>';
                }
                ?>
            </div>
            <div class="vortex-categories-hint"><?php _e('Select up to 3 categories', 'vortex-ai-marketplace'); ?></div>
        </div>
        
        <!-- Collector Categories (shown/hidden with JS) -->
        <div class="vortex-form-row vortex-categories-section" id="vortex-collector-categories" style="display: none;">
            <label><?php _e('Select up to 3 categories that best describe you as a collector:', 'vortex-ai-marketplace'); ?></label>
            <div class="vortex-categories-grid">
                <?php
                $collector_categories = array(
                    'art_gallery' => __('Art Gallery', 'vortex-ai-marketplace'),
                    'museum' => __('Museum', 'vortex-ai-marketplace'),
                    'private_collector' => __('Private Collector', 'vortex-ai-marketplace'),
                    'art_dealer' => __('Art Dealer', 'vortex-ai-marketplace'),
                    'art_enthusiast' => __('Art Enthusiast', 'vortex-ai-marketplace'),
                    'online_art_website' => __('Online Art Website', 'vortex-ai-marketplace'),
                    'online_art_gallery' => __('Online Art Gallery', 'vortex-ai-marketplace'),
                    'art_fair' => __('Art Fair', 'vortex-ai-marketplace'),
                    'art_festival' => __('Art Festival', 'vortex-ai-marketplace'),
                    'art_school' => __('Art School', 'vortex-ai-marketplace'),
                    'corporate_collector' => __('Corporate Collector', 'vortex-ai-marketplace'),
                    'other' => __('Other', 'vortex-ai-marketplace')
                );
                
                foreach ($collector_categories as $value => $label) {
                    echo '<label class="vortex-category-checkbox">';
                    echo '<input type="checkbox" name="vortex_collector_categories[]" value="' . esc_attr($value) . '" class="vortex-category-input" data-category-group="collector">';
                    echo '<span class="vortex-category-label">' . esc_html($label) . '</span>';
                    echo '</label>';
                }
                ?>
            </div>
            <div class="vortex-categories-hint"><?php _e('Select up to 3 categories', 'vortex-ai-marketplace'); ?></div>
        </div>
        
        <div class="vortex-form-row vortex-terms-agreement">
            <label class="vortex-checkbox-label">
                <input type="checkbox" name="vortex_terms" required>
                <?php _e('I agree to the Terms of Service and Privacy Policy', 'vortex-ai-marketplace'); ?>
            </label>
        </div>
        
        <div class="vortex-form-row">
            <?php wp_nonce_field('vortex_register_nonce', 'vortex_register_nonce'); ?>
            <input type="hidden" name="action" value="vortex_register_user">
            <button type="submit" class="vortex-submit-button"><?php _e('Create Account', 'vortex-ai-marketplace'); ?></button>
        </div>
    </form>
    
    <div class="vortex-login-link">
        <?php _e('Already have an account?', 'vortex-ai-marketplace'); ?> 
        <a href="<?php echo esc_url(wp_login_url()); ?>"><?php _e('Log In', 'vortex-ai-marketplace'); ?></a>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Toggle between artist and collector categories
    $('input[name="vortex_user_role"]').change(function() {
        if ($(this).val() === 'artist') {
            $('#vortex-artist-categories').show();
            $('#vortex-collector-categories').hide();
            $('input[data-category-group="collector"]').prop('checked', false);
        } else {
            $('#vortex-artist-categories').hide();
            $('#vortex-collector-categories').show();
            $('input[data-category-group="artist"]').prop('checked', false);
        }
    });
    
    // Limit selection to 3 categories per group
    $('.vortex-category-input').change(function() {
        const group = $(this).data('category-group');
        const checked = $('input[data-category-group="' + group + '"]:checked').length;
        
        if (checked > 3) {
            $(this).prop('checked', false);
            alert('<?php _e('You can select up to 3 categories', 'vortex-ai-marketplace'); ?>');
        }
    });
});
</script> 