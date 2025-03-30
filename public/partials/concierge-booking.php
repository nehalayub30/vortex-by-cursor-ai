<?php
/**
 * Template for displaying concierge booking form
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// Exit if accessed directly
if (!defined('WPINC')) {
    die;
}

// Get experience data
$experience_id = isset($atts['id']) ? intval($atts['id']) : get_the_ID();
$experience_date = get_post_meta($experience_id, 'vortex_experience_date', true);
$formatted_date = date_i18n(get_option('date_format'), strtotime($experience_date));
$time_string = get_post_meta($experience_id, 'vortex_experience_time', true);
$location_terms = get_the_terms($experience_id, 'vortex_location');
$location = $location_terms ? $location_terms[0]->name : '';
$price = get_post_meta($experience_id, 'vortex_experience_price', true);
$tola_price = get_post_meta($experience_id, 'vortex_experience_tola', true);
$capacity = get_post_meta($experience_id, 'vortex_experience_capacity', true);
$booked = get_post_meta($experience_id, 'vortex_experience_booked', true);
$remaining_slots = $capacity - $booked;
$is_nft_gated = get_post_meta($experience_id, 'vortex_experience_nft_gated', true);
$required_nft = get_post_meta($experience_id, 'vortex_required_nft', true);

// Check if user is logged in
$user_can_book = is_user_logged_in();

// If NFT gated, check if user has the required NFT
if ($user_can_book && $is_nft_gated && $required_nft) {
    $user_id = get_current_user_id();
    $blockchain = Vortex_AI_Marketplace::get_instance()->blockchain;
    $user_can_book = $blockchain->user_has_nft($user_id, $required_nft);
}
?>

<div class="vortex-booking-container">
    <h2 class="vortex-booking-title"><?php _e('Book Your Experience', 'vortex-ai-marketplace'); ?></h2>
    
    <div class="vortex-experience-summary">
        <h3><?php echo get_the_title($experience_id); ?></h3>
        
        <div class="vortex-experience-meta">
            <div class="vortex-meta-item">
                <span class="vortex-meta-label"><?php _e('Date:', 'vortex-ai-marketplace'); ?></span>
                <span class="vortex-meta-value"><?php echo esc_html($formatted_date); ?></span>
            </div>
            
            <?php if ($time_string) : ?>
            <div class="vortex-meta-item">
                <span class="vortex-meta-label"><?php _e('Time:', 'vortex-ai-marketplace'); ?></span>
                <span class="vortex-meta-value"><?php echo esc_html($time_string); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($location) : ?>
            <div class="vortex-meta-item">
                <span class="vortex-meta-label"><?php _e('Location:', 'vortex-ai-marketplace'); ?></span>
                <span class="vortex-meta-value"><?php echo esc_html($location); ?></span>
            </div>
            <?php endif; ?>
            
            <div class="vortex-meta-item">
                <span class="vortex-meta-label"><?php _e('Available Spots:', 'vortex-ai-marketplace'); ?></span>
                <span class="vortex-meta-value"><?php echo esc_html($remaining_slots); ?> / <?php echo esc_html($capacity); ?></span>
            </div>
            
            <div class="vortex-meta-item">
                <span class="vortex-meta-label"><?php _e('Price:', 'vortex-ai-marketplace'); ?></span>
                <span class="vortex-meta-value">
                    <?php if ($tola_price) : ?>
                    <span class="vortex-tola-price"><?php echo esc_html($tola_price); ?> TOLA</span>
                    <?php endif; ?>
                    
                    <?php if ($price) : ?>
                    <span class="vortex-fiat-price"><?php echo esc_html($price); ?></span>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </div>
    
    <?php if ($remaining_slots <= 0) : ?>
    
    <div class="vortex-notice vortex-notice-error">
        <?php _e('This experience is sold out.', 'vortex-ai-marketplace'); ?>
    </div>
    
    <?php elseif (!$user_can_book) : ?>
    
    <div class="vortex-notice vortex-notice-warning">
        <?php if (!is_user_logged_in()) : ?>
            <?php _e('Please log in to book this experience.', 'vortex-ai-marketplace'); ?>
            <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="vortex-login-link">
                <?php _e('Log In', 'vortex-ai-marketplace'); ?>
            </a>
        <?php elseif ($is_nft_gated) : ?>
            <?php _e('This experience requires ownership of a specific NFT to book.', 'vortex-ai-marketplace'); ?>
            <a href="<?php echo esc_url(home_url('/marketplace')); ?>" class="vortex-marketplace-link">
                <?php _e('Visit Marketplace', 'vortex-ai-marketplace'); ?>
            </a>
        <?php endif; ?>
    </div>
    
    <?php else : ?>
    
    <form id="vortex-booking-form" class="vortex-booking-form">
        <div class="vortex-form-row">
            <label for="booking_tickets"><?php _e('Number of Tickets', 'vortex-ai-marketplace'); ?></label>
            <select name="booking_tickets" id="booking_tickets" required>
                <?php for ($i = 1; $i <= min(5, $remaining_slots); $i++) : ?>
                <option value="<?php echo esc_attr($i); ?>"><?php echo esc_html($i); ?></option>
                <?php endfor; ?>
            </select>
        </div>
        
        <div class="vortex-form-row">
            <label for="booking_name"><?php _e('Full Name', 'vortex-ai-marketplace'); ?></label>
            <input type="text" name="booking_name" id="booking_name" required>
        </div>
        
        <div class="vortex-form-row">
            <label for="booking_email"><?php _e('Email', 'vortex-ai-marketplace'); ?></label>
            <input type="email" name="booking_email" id="booking_email" required>
        </div>
        
        <div class="vortex-form-row">
            <label for="booking_phone"><?php _e('Phone Number', 'vortex-ai-marketplace'); ?></label>
            <input type="tel" name="booking_phone" id="booking_phone">
        </div>
        
        <div class="vortex-form-row vortex-payment-selection">
            <p class="vortex-payment-label"><?php _e('Payment Method', 'vortex-ai-marketplace'); ?></p>
            
            <div class="vortex-payment-options">
                <?php if ($tola_price) : ?>
                <label class="vortex-payment-option">
                    <input type="radio" name="payment_method" value="tola" checked>
                    <span class="vortex-payment-option-label">
                        <span class="vortex-payment-icon vortex-tola-icon"></span>
                        <?php _e('Pay with TOLA', 'vortex-ai-marketplace'); ?>
                    </span>
                    <span class="vortex-payment-price"><?php echo esc_html($tola_price); ?> TOLA</span>
                </label>
                <?php endif; ?>
                
                <?php if ($price) : ?>
                <label class="vortex-payment-option">
                    <input type="radio" name="payment_method" value="fiat" <?php echo !$tola_price ? 'checked' : ''; ?>>
                    <span class="vortex-payment-option-label">
                        <span class="vortex-payment-icon vortex-card-icon"></span>
                        <?php _e('Pay with Credit Card', 'vortex-ai-marketplace'); ?>
                    </span>
                    <span class="vortex-payment-price"><?php echo esc_html($price); ?></span>
                </label>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="vortex-form-row vortex-terms-agreement">
            <label class="vortex-checkbox-label">
                <input type="checkbox" name="booking_terms" required>
                <?php _e('I agree to the terms and conditions for this experience', 'vortex-ai-marketplace'); ?>
            </label>
        </div>
        
        <div class="vortex-form-row vortex-button-row">
            <input type="hidden" name="experience_id" value="<?php echo esc_attr($experience_id); ?>">
            <input type="hidden" name="action" value="vortex_book_experience">
            <?php wp_nonce_field('vortex_booking_nonce', 'booking_nonce'); ?>
            <button type="submit" class="vortex-submit-button vortex-book-button">
                <?php _e('Complete Booking', 'vortex-ai-marketplace'); ?>
            </button>
        </div>
    </form>
    
    <div id="vortex-booking-message" class="vortex-booking-message"></div>
    
    <?php endif; ?>
</div> 