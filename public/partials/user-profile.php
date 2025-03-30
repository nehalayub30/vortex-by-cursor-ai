<?php
/**
 * Template for user profile page
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// Exit if accessed directly
if (!defined('WPINC')) {
    die;
}

// Get user data
$user_id = get_current_user_id();
$user_data = get_userdata($user_id);
$user_role = get_user_meta($user_id, 'vortex_user_role', true);
$user_categories = get_user_meta($user_id, 'vortex_user_categories', true);

// Define category labels
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

// Get the appropriate category labels based on user role
$category_labels = ($user_role === 'artist') ? $artist_categories : $collector_categories;
?>

<div class="vortex-profile-container">
    <div class="vortex-profile-header">
        <div class="vortex-profile-avatar">
            <?php echo get_avatar($user_id, 120); ?>
        </div>
        
        <div class="vortex-profile-info">
            <h1 class="vortex-profile-name"><?php echo esc_html($user_data->display_name); ?></h1>
            
            <div class="vortex-profile-meta">
                <div class="vortex-profile-role">
                    <?php if ($user_role === 'artist'): ?>
                        <span class="vortex-role-badge vortex-role-artist">
                            <?php _e('Artist', 'vortex-ai-marketplace'); ?>
                        </span>
                    <?php elseif ($user_role === 'collector'): ?>
                        <span class="vortex-role-badge vortex-role-collector">
                            <?php _e('Collector', 'vortex-ai-marketplace'); ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($user_categories) && is_array($user_categories)): ?>
                <div class="vortex-profile-categories">
                    <?php foreach ($user_categories as $category): ?>
                        <?php if (isset($category_labels[$category])): ?>
                            <span class="vortex-category-badge">
                                <?php echo esc_html($category_labels[$category]); ?>
                            </span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Rest of profile content -->
</div> 