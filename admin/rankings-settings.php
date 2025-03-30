<?php
/**
 * Rankings Settings Page
 * 
 * Handles configuration settings for the marketplace rankings system,
 * ensuring AI agent deep learning capabilities remain active during all operations.
 *
 * @package VORTEX_AI_Marketplace
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render the Rankings settings page
 */
function vortex_rankings_settings_page() {
    // Security check
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'vortex-ai-marketplace'));
    }
    
    // Initialize AI agents to ensure deep learning during settings interactions
    do_action('vortex_ai_agent_init', 'CLOE', 'rankings_settings', array(
        'context' => 'admin_rankings_configuration',
        'user_id' => get_current_user_id(),
        'session_id' => wp_get_session_token(),
        'learning_enabled' => true
    ));
    
    do_action('vortex_ai_agent_init', 'BusinessStrategist', 'rankings_settings', array(
        'context' => 'business_metrics_configuration',
        'user_id' => get_current_user_id(),
        'session_id' => wp_get_session_token(),
        'learning_enabled' => true
    ));
    
    do_action('vortex_ai_agent_init', 'HURAII', 'rankings_settings', array(
        'context' => 'artwork_quality_metrics',
        'user_id' => get_current_user_id(),
        'session_id' => wp_get_session_token(),
        'learning_enabled' => true
    ));
    
    // Get saved settings with defaults
    $settings = get_option('vortex_rankings_settings', array(
        'artist_ranking_factors' => array(
            'sales_volume' => 35,
            'artwork_quality' => 25,
            'user_engagement' => 20,
            'upload_frequency' => 10,
            'marketplace_activity' => 10
        ),
        'artwork_ranking_factors' => array(
            'sales_count' => 30,
            'view_count' => 15,
            'like_count' => 15,
            'quality_score' => 25,
            'recency' => 15
        ),
        'collector_ranking_factors' => array(
            'purchase_volume' => 40,
            'collection_diversity' => 20,
            'marketplace_activity' => 25,
            'community_engagement' => 15
        ),
        'enable_ai_boosting' => 'yes',
        'trending_calculation_period' => '7days',
        'rankings_refresh_interval' => 'daily',
        'minimum_data_points' => 5,
        'expose_ranking_factors' => 'no',
        'featured_artist_count' => 10,
        'featured_artwork_count' => 12,
        'enable_categorical_rankings' => 'yes',
        'popular_categories_count' => 8
    ));
    
    // Process form submission
    if (isset($_POST['vortex_rankings_settings_submit']) && check_admin_referer('vortex_rankings_settings_nonce')) {
        try {
            // Validate and sanitize artist ranking factors
            $artist_factors = array();
            if (isset($_POST['artist_factors']) && is_array($_POST['artist_factors'])) {
                foreach ($_POST['artist_factors'] as $factor => $value) {
                    $artist_factors[sanitize_key($factor)] = min(100, max(0, absint($value)));
                }
                
                // Ensure factors sum to 100%
                $total = array_sum($artist_factors);
                if ($total > 0) {
                    foreach ($artist_factors as $factor => $value) {
                        $artist_factors[$factor] = round(($value / $total) * 100);
                    }
                }
            } else {
                throw new Exception(__('Invalid artist ranking factors provided.', 'vortex-ai-marketplace'));
            }
            
            // Validate and sanitize artwork ranking factors
            $artwork_factors = array();
            if (isset($_POST['artwork_factors']) && is_array($_POST['artwork_factors'])) {
                foreach ($_POST['artwork_factors'] as $factor => $value) {
                    $artwork_factors[sanitize_key($factor)] = min(100, max(0, absint($value)));
                }
                
                // Ensure factors sum to 100%
                $total = array_sum($artwork_factors);
                if ($total > 0) {
                    foreach ($artwork_factors as $factor => $value) {
                        $artwork_factors[$factor] = round(($value / $total) * 100);
                    }
                }
            } else {
                throw new Exception(__('Invalid artwork ranking factors provided.', 'vortex-ai-marketplace'));
            }
            
            // Validate and sanitize collector ranking factors
            $collector_factors = array();
            if (isset($_POST['collector_factors']) && is_array($_POST['collector_factors'])) {
                foreach ($_POST['collector_factors'] as $factor => $value) {
                    $collector_factors[sanitize_key($factor)] = min(100, max(0, absint($value)));
                }
                
                // Ensure factors sum to 100%
                $total = array_sum($collector_factors);
                if ($total > 0) {
                    foreach ($collector_factors as $factor => $value) {
                        $collector_factors[$factor] = round(($value / $total) * 100);
                    }
                }
            } else {
                throw new Exception(__('Invalid collector ranking factors provided.', 'vortex-ai-marketplace'));
            }
            
            // Validate and sanitize other settings
            $new_settings = array(
                'artist_ranking_factors' => $artist_factors,
                'artwork_ranking_factors' => $artwork_factors,
                'collector_ranking_factors' => $collector_factors,
                'enable_ai_boosting' => sanitize_text_field($_POST['enable_ai_boosting'] ?? 'no'),
                'trending_calculation_period' => sanitize_text_field($_POST['trending_calculation_period'] ?? '7days'),
                'rankings_refresh_interval' => sanitize_text_field($_POST['rankings_refresh_interval'] ?? 'daily'),
                'minimum_data_points' => absint($_POST['minimum_data_points'] ?? 5),
                'expose_ranking_factors' => sanitize_text_field($_POST['expose_ranking_factors'] ?? 'no'),
                'featured_artist_count' => absint($_POST['featured_artist_count'] ?? 10),
                'featured_artwork_count' => absint($_POST['featured_artwork_count'] ?? 12),
                'enable_categorical_rankings' => sanitize_text_field($_POST['enable_categorical_rankings'] ?? 'no'),
                'popular_categories_count' => absint($_POST['popular_categories_count'] ?? 8)
            );
            
            // Track settings changes for AI learning
            do_action('vortex_ai_agent_learn', 'CLOE', 'rankings_settings_updated', array(
                'previous_settings' => $settings,
                'new_settings' => $new_settings,
                'user_id' => get_current_user_id(),
                'timestamp' => current_time('mysql'),
            ));
            
            // Also let BusinessStrategist learn from these settings
            do_action('vortex_ai_agent_learn', 'BusinessStrategist', 'ranking_metrics_updated', array(
                'previous_settings' => $settings,
                'new_settings' => $new_settings,
                'user_id' => get_current_user_id(),
                'timestamp' => current_time('mysql'),
            ));
            
            // Also let HURAII learn about quality metrics settings
            do_action('vortex_ai_agent_learn', 'HURAII', 'quality_metrics_updated', array(
                'previous_settings' => $settings,
                'new_settings' => $new_settings,
                'user_id' => get_current_user_id(),
                'timestamp' => current_time('mysql'),
            ));
            
            // Update settings
            update_option('vortex_rankings_settings', $new_settings);
            $settings = $new_settings;
            
            // Schedule a rankings recalculation if enabled
            if (!wp_next_scheduled('vortex_recalculate_rankings')) {
                wp_schedule_event(time(), $settings['rankings_refresh_interval'], 'vortex_recalculate_rankings');
            }
            
            // Display success message
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Rankings settings saved successfully.', 'vortex-ai-marketplace') . '</p></div>';
        } catch (Exception $e) {
            // Display error message
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($e->getMessage()) . '</p></div>';
            
            // Log error for AI learning
            do_action('vortex_ai_agent_learn', 'CLOE', 'rankings_settings_error', array(
                'error_message' => $e->getMessage(),
                'form_data' => wp_kses_post_deep($_POST),
                'user_id' => get_current_user_id(),
                'timestamp' => current_time('mysql'),
            ));
        }
    }
    
    // Get trending calculation periods
    $trending_periods = array(
        '1day' => __('Last 24 Hours', 'vortex-ai-marketplace'),
        '3days' => __('Last 3 Days', 'vortex-ai-marketplace'),
        '7days' => __('Last 7 Days', 'vortex-ai-marketplace'),
        '14days' => __('Last 14 Days', 'vortex-ai-marketplace'),
        '30days' => __('Last 30 Days', 'vortex-ai-marketplace')
    );
    
    // Get refresh intervals
    $refresh_intervals = array(
        'hourly' => __('Hourly', 'vortex-ai-marketplace'),
        'twicedaily' => __('Twice Daily', 'vortex-ai-marketplace'),
        'daily' => __('Daily', 'vortex-ai-marketplace'),
        'weekly' => __('Weekly', 'vortex-ai-marketplace')
    );
    
    // Render settings form
    ?>
    <div class="wrap vortex-rankings-settings">
        <h1><?php esc_html_e('Marketplace Rankings Settings', 'vortex-ai-marketplace'); ?></h1>
        
        <?php if (isset($_GET['recalculate']) && $_GET['recalculate'] === 'success') : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e('Rankings have been recalculated successfully.', 'vortex-ai-marketplace'); ?></p>
            </div>
        <?php endif; ?>
        
        <form method="post" action="" id="rankings-settings-form">
            <?php wp_nonce_field('vortex_rankings_settings_nonce'); ?>
            
            <div class="vortex-settings-section">
                <h2><?php esc_html_e('Artist Ranking Factors', 'vortex-ai-marketplace'); ?></h2>
                <p class="description"><?php esc_html_e('Set the weight of each factor in determining artist rankings. Total must equal 100%.', 'vortex-ai-marketplace'); ?></p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Sales Volume', 'vortex-ai-marketplace'); ?></th>
                        <td>
                            <input type="range" name="artist_factors[sales_volume]" min="0" max="100" value="<?php echo esc_attr($settings['artist_ranking_factors']['sales_volume']); ?>" class="factor-slider" data-group="artist">
                            <span class="factor-value"><?php echo esc_html($settings['artist_ranking_factors']['sales_volume']); ?>%</span>
                            <p class="description"><?php esc_html_e('Weight given to total sales and revenue.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Artwork Quality', 'vortex-ai-marketplace'); ?></th>
                        <td>
                            <input type="range" name="artist_factors[artwork_quality]" min="0" max="100" value="<?php echo esc_attr($settings['artist_ranking_factors']['artwork_quality']); ?>" class="factor-slider" data-group="artist">
                            <span class="factor-value"><?php echo esc_html($settings['artist_ranking_factors']['artwork_quality']); ?>%</span>
                            <p class="description"><?php esc_html_e('Weight given to HURAII-evaluated artwork quality scores.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('User Engagement', 'vortex-ai-marketplace'); ?></th>
                        <td>
                            <input type="range" name="artist_factors[user_engagement]" min="0" max="100" value="<?php echo esc_attr($settings['artist_ranking_factors']['user_engagement']); ?>" class="factor-slider" data-group="artist">
                            <span class="factor-value"><?php echo esc_html($settings['artist_ranking_factors']['user_engagement']); ?>%</span>
                            <p class="description"><?php esc_html_e('Weight given to profile views, followers, and comments.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Upload Frequency', 'vortex-ai-marketplace'); ?></th>
                        <td>
                            <input type="range" name="artist_factors[upload_frequency]" min="0" max="100" value="<?php echo esc_attr($settings['artist_ranking_factors']['upload_frequency']); ?>" class="factor-slider" data-group="artist">
                            <span class="factor-value"><?php echo esc_html($settings['artist_ranking_factors']['upload_frequency']); ?>%</span>
                            <p class="description"><?php esc_html_e('Weight given to consistency in uploading new artwork.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Marketplace Activity', 'vortex-ai-marketplace'); ?></th>
                        <td>
                            <input type="range" name="artist_factors[marketplace_activity]" min="0" max="100" value="<?php echo esc_attr($settings['artist_ranking_factors']['marketplace_activity']); ?>" class="factor-slider" data-group="artist">
                            <span class="factor-value"><?php echo esc_html($settings['artist_ranking_factors']['marketplace_activity']); ?>%</span>
                            <p class="description"><?php esc_html_e('Weight given to general activity in the marketplace.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Total', 'vortex-ai-marketplace'); ?></th>
                        <td>
                            <div class="total-container">
                                <span class="total-label"><?php esc_html_e('Total Weight:', 'vortex-ai-marketplace'); ?></span>
                                <span id="artist-total-value" class="total-value">100%</span>
                                <div class="total-warning" id="artist-total-warning" style="display: none;">
                                    <?php esc_html_e('Total must equal 100%', 'vortex-ai-marketplace'); ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="vortex-settings-section">
                <h2><?php esc_html_e('Artwork Ranking Factors', 'vortex-ai-marketplace'); ?></h2>
                <p class="description"><?php esc_html_e('Set the weight of each factor in determining artwork rankings. Total must equal 100%.', 'vortex-ai-marketplace'); ?></p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Sales Count', 'vortex-ai-marketplace'); ?></th>
                        <td>
                            <input type="range" name="artwork_factors[sales_count]" min="0" max="100" value="<?php echo esc_attr($settings['artwork_ranking_factors']['sales_count']); ?>" class="factor-slider" data-group="artwork">
                            <span class="factor-value"><?php echo esc_html($settings['artwork_ranking_factors']['sales_count']); ?>%</span>
                            <p class="description"><?php esc_html_e('Weight given to number of sales.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('View Count', 'vortex-ai-marketplace'); ?></th>
                        <td>
                            <input type="range" name="artwork_factors[view_count]" min="0" max="100" value="<?php echo esc_attr($settings['artwork_ranking_factors']['view_count']); ?>" class="factor-slider" data-group="artwork">
                            <span class="factor-value"><?php echo esc_html($settings['artwork_ranking_factors']['view_count']); ?>%</span>
                            <p class="description"><?php esc_html_e('Weight given to number of views.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Like Count', 'vortex-ai-marketplace'); ?></th>
                        <td>
                            <input type="range" name="artwork_factors[like_count]" min="0" max="100" value="<?php echo esc_attr($settings['artwork_ranking_factors']['like_count']); ?>" class="factor-slider" data-group="artwork">
                            <span class="factor-value"><?php echo esc_html($settings['artwork_ranking_factors']['like_count']); ?>%</span>
                            <p class="description"><?php esc_html_e('Weight given to number of likes and favorites.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Quality Score', 'vortex-ai-marketplace'); ?></th>
                        <td>
                            <input type="range" name="artwork_factors[quality_score]" min="0" max="100" value="<?php echo esc_attr($settings['artwork_ranking_factors']['quality_score']); ?>" class="factor-slider" data-group="artwork">
                            <span class="factor-value"><?php echo esc_html($settings['artwork_ranking_factors']['quality_score']); ?>%</span>
                            <p class="description"><?php esc_html_e('Weight given to HURAII quality assessment.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Recency', 'vortex-ai-marketplace'); ?></th>
                        <td>
                            <input type="range" name="artwork_factors[recency]" min="0" max="100" value="<?php echo esc_attr($settings['artwork_ranking_factors']['recency']); ?>" class="factor-slider" data-group="artwork">
                            <span class="factor-value"><?php echo esc_html($settings['artwork_ranking_factors']['recency']); ?>%</span>
                            <p class="description"><?php esc_html_e('Weight given to how recently the artwork was created.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Total', 'vortex-ai-marketplace'); ?></th>
                        <td>
                            <div class="total-container">
                                <span class="total-label"><?php esc_html_e('Total Weight:', 'vortex-ai-marketplace'); ?></span>
                                <span id="artwork-total-value" class="total-value">100%</span>
                                <div class="total-warning" id="artwork-total-warning" style="display: none;">
                                    <?php esc_html_e('Total must equal 100%', 'vortex-ai-marketplace'); ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="vortex-settings-section">
                <h2><?php esc_html_e('Collector Ranking Factors', 'vortex-ai-marketplace'); ?></h2>
                <p class="description"><?php esc_html_e('Set the weight of each factor in determining collector rankings. Total must equal 100%.', 'vortex-ai-marketplace'); ?></p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Purchase Volume', 'vortex-ai-marketplace'); ?></th>
                        <td>
                            <input type="range" name="collector_factors[purchase_volume]" min="0" max="100" value="<?php echo esc_attr($settings['collector_ranking_factors']['purchase_volume']); ?>" class="factor-slider" data-group="collector">
                            <span class="factor-value"><?php echo esc_html($settings['collector_ranking_factors']['purchase_volume']); ?>%</span>
                            <p class="description"><?php esc_html_e('Weight given to total purchase volume.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Collection Diversity', 'vortex-ai-marketplace'); ?></th>
                        <td>
                            <input type="range" name="collector_factors[collection_diversity]" min="0" max="100" value="<?php echo esc_attr($settings['collector_ranking_factors']['collection_diversity']); ?>" class="factor-slider" data-group="collector">
                            <span class="factor-value"><?php echo esc_html($settings['collector_ranking_factors']['collection_diversity']); ?>%</span>
                            <p class="description"><?php esc_html_e('Weight given to diversity of collected artwork styles and artists.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Marketplace Activity', 'vortex-ai-marketplace'); ?></th>
                        <td>
                            <input type="range" name="collector_factors[marketplace_activity]" min="0" max="100" value="<?php echo esc_attr($settings['collector_ranking_factors']['marketplace_activity']); ?>" class="factor-slider" data-group="collector">
                            <span class="factor-value"><?php echo esc_html($settings['collector_ranking_factors']['marketplace_activity']); ?>%</span>
                            <p class="description"><?php esc_html_e('Weight given to browse, like, and view activity.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Community Engagement', 'vortex-ai-marketplace'); ?></th>
                        <td>
                            <input type="range" name="collector_factors[community_engagement]" min="0" max="100" value="<?php echo esc_attr($settings['collector_ranking_factors']['community_engagement']); ?>" class="factor-slider" data-group="collector">
                            <span class="factor-value"><?php echo esc_html($settings['collector_ranking_factors']['community_engagement']); ?>%</span>
                            <p class="description"><?php esc_html_e('Weight given to comments, sharing, and artist follows.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Total', 'vortex-ai-marketplace'); ?></th>
                        <td>
                            <div class="total-container">
                                <span class="total-label"><?php esc_html_e('Total Weight:', 'vortex-ai-marketplace'); ?></span>
                                <span id="collector-total-value" class="total-value">100%</span>
                                <div class="total-warning" id="collector-total-warning" style="display: none;">
                                    <?php esc_html_e('Total must equal 100%', 'vortex-ai-marketplace'); ?>
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="vortex-settings-section">
                <h2><?php esc_html_e('General Ranking Settings', 'vortex-ai-marketplace'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="enable_ai_boosting"><?php esc_html_e('Enable AI Boosting', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <select name="enable_ai_boosting" id="enable_ai_boosting">
                                <option value="yes" <?php selected($settings['enable_ai_boosting'], 'yes'); ?>><?php esc_html_e('Yes', 'vortex-ai-marketplace'); ?></option>
                                <option value="no" <?php selected($settings['enable_ai_boosting'], 'no'); ?>><?php esc_html_e('No', 'vortex-ai-marketplace'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Allow CLOE to dynamically adjust rankings based on user behavior patterns.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="trending_calculation_period"><?php esc_html_e('Trending Calculation Period', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <select name="trending_calculation_period" id="trending_calculation_period">
                                <?php foreach ($trending_periods as $value => $label) : ?>
                                    <option value="<?php echo esc_attr($value); ?>" <?php selected($settings['trending_calculation_period'], $value); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php esc_html_e('Time period used to calculate trending items.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="rankings_refresh_interval"><?php esc_html_e('Rankings Refresh Interval', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <select name="rankings_refresh_interval" id="rankings_refresh_interval">
                                <?php foreach ($refresh_intervals as $value => $label) : ?>
                                    <option value="<?php echo esc_attr($value); ?>" <?php selected($settings['rankings_refresh_interval'], $value); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php esc_html_e('How often to automatically recalculate all rankings.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="minimum_data_points"><?php esc_html_e('Minimum Data Points', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="minimum_data_points" id="minimum_data_points" value="<?php echo esc_attr($settings['minimum_data_points']); ?>" min="1" max="100">
                            <p class="description"><?php esc_html_e('Minimum amount of data points required before an item appears in rankings.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="expose_ranking_factors"><?php esc_html_e('Expose Ranking Factors', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <select name="expose_ranking_factors" id="expose_ranking_factors">
                                <option value="yes" <?php selected($settings['expose_ranking_factors'], 'yes'); ?>><?php esc_html_e('Yes', 'vortex-ai-marketplace'); ?></option>
                                <option value="no" <?php selected($settings['expose_ranking_factors'], 'no'); ?>><?php esc_html_e('No', 'vortex-ai-marketplace'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Show users what factors impact their rankings.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="vortex-settings-section">
                <h2><?php esc_html_e('Display Settings', 'vortex-ai-marketplace'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="featured_artist_count"><?php esc_html_e('Featured Artists Count', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="featured_artist_count" id="featured_artist_count" value="<?php echo esc_attr($settings['featured_artist_count']); ?>" min="1" max="50">
                            <p class="description"><?php esc_html_e('Number of featured artists to display in rankings.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="featured_artwork_count"><?php esc_html_e('Featured Artworks Count', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="featured_artwork_count" id="featured_artwork_count" value="<?php echo esc_attr($settings['featured_artwork_count']); ?>" min="1" max="50">
                            <p class="description"><?php esc_html_e('Number of featured artworks to display in rankings.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="enable_categorical_rankings"><?php esc_html_e('Enable Categorical Rankings', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <select name="enable_categorical_rankings" id="enable_categorical_rankings">
                                <option value="yes" <?php selected($settings['enable_categorical_rankings'], 'yes'); ?>><?php esc_html_e('Yes', 'vortex-ai-marketplace'); ?></option>
                                <option value="no" <?php selected($settings['enable_categorical_rankings'], 'no'); ?>><?php esc_html_e('No', 'vortex-ai-marketplace'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Generate rankings by artwork category (e.g., top abstract art).', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="popular_categories_count"><?php esc_html_e('Popular Categories Count', 'vortex-ai-marketplace'); ?></label>
                        </th>
                        <td>
                            <input type="number" name="popular_categories_count" id="popular_categories_count" value="<?php echo esc_attr($settings['popular_categories_count']); ?>" min="1" max="20">
                            <p class="description"><?php esc_html_e('Number of categories to highlight in popular categories list.', 'vortex-ai-marketplace'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <p class="submit">
                <input type="submit" name="vortex_rankings_settings_submit" class="button button-primary" value="<?php esc_attr_e('Save Rankings Settings', 'vortex-ai-marketplace'); ?>">
                <a href="<?php echo esc_url(add_query_arg(array('page' => 'vortex-rankings-settings', 'action' => 'recalculate', '_wpnonce' => wp_create_nonce('vortex_recalculate_rankings_nonce')))); ?>" class="button button-secondary" id="recalculate-rankings-btn">
                    <?php esc_html_e('Recalculate All Rankings Now', 'vortex-ai-marketplace'); ?>
                </a>
            </p>
        </form>
        
        <div class="vortex-ai-insights-container">
            <h2><?php esc_html_e('AI Insights', 'vortex-ai-marketplace'); ?></h2>
            
            <div class="vortex-ai-insights">
                <?php
                // Get insights from CLOE
                $cloe_insights = apply_filters('vortex_cloe_ranking_insights', array());
                
                if (!empty($cloe_insights)) {
                    echo '<div class="ai-insight cloe-insight">';
                    echo '<h3>' . esc_html__('CLOE Ranking Insights', 'vortex-ai-marketplace') . '</h3>';
                    echo '<p>' . esc_html($cloe_insights['message'] ?? '') . '</p>';
                    
                    if (!empty($cloe_insights['recommendations'])) {
                        echo '<ul class="insight-recommendations">';
                        foreach ($cloe_insights['recommendations'] as $recommendation) {
                            echo '<li>' . esc_html($recommendation) . '</li>';
                        }
                        echo '</ul>';
                    }
                    
                    echo '</div>';
                }
                
                // Get insights from BusinessStrategist
                $business_insights = apply_filters('vortex_business_strategist_ranking_insights', array());
                
                if (!empty($business_insights)) {
                    echo '<div class="ai-insight business-insight">';
                    echo '<h3>' . esc_html__('BusinessStrategist Ranking Insights', 'vortex-ai-marketplace') . '</h3>';
                    echo '<p>' . esc_html($business_insights['message'] ?? '') . '</p>';
                    
                    if (!empty($business_insights['market_impact'])) {
                        echo '<div class="market-impact">';
                        echo '<h4>' . esc_html__('Market Impact', 'vortex-ai-marketplace') . '</h4>';
                        echo '<p>' . esc_html($business_insights['market_impact']) . '</p>';
                        echo '</div>';
                    }
                    
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Track AI interactions for learning
        function trackAIInteraction(agent, action, data) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_track_ai_interaction',
                    agent: agent,
                    interaction_type: action,
                    interaction_data: data,
                    nonce: '<?php echo wp_create_nonce('vortex_ai_interaction_nonce'); ?>'
                },
                success: function(response) {
                    console.log(response);
                },
                error: function(xhr, status, error) {
                    console.error('Error tracking AI interaction:', status, error);
                }
            });
        }
        
        // Calculate totals for each factor group
        function calculateTotals() {
            const groups = ['artist', 'artwork', 'collector'];
            
            groups.forEach(function(group) {
                let total = 0;
                $(`.factor-slider[data-group="${group}"]`).each(function() {
                    total += parseInt($(this).val());
                });
                
                $(`#${group}-total-value`).text(total + '%');
                
                if (total !== 100) {
                    $(`#${group}-total-warning`).show();
                } else {
                    $(`#${group}-total-warning`).hide();
                }
            });
        }
        
        // Update factor value display when slider changes
        $('.factor-slider').on('input', function() {
            const value = $(this).val();
            $(this).next('.factor-value').text(value + '%');
            calculateTotals();
            
            // Track for AI learning
            const factor = $(this).attr('name');
            const group = $(this).data('group');
            
            trackAIInteraction('CLOE', 'ranking_factor_adjusted', {
                factor: factor,
                value: value,
                group: group,
                user_id: <?php echo get_current_user_id(); ?>,
                timestamp: new Date().toISOString()
            });
        });
        
        // Calculate totals on page load
        calculateTotals();
        
        // Handle form submission with validation
        $('#rankings-settings-form').on('submit', function(e) {
            const groups = ['artist', 'artwork', 'collector'];
            let hasErrors = false;
            
            groups.forEach(function(group) {
                let total = 0;
                $(`.factor-slider[data-group="${group}"]`).each(function() {
                    total += parseInt($(this).val());
                });
                
                if (total !== 100) {
                    $(`#${group}-total-warning`).show();
                    hasErrors = true;
                }
            });
            
            if (hasErrors) {
                e.preventDefault();
                alert('<?php echo esc_js(__('Please ensure that factor weights in each group sum to exactly 100%.', 'vortex-ai-marketplace')); ?>');
                
                // Log this validation error for AI learning
                trackAIInteraction('CLOE', 'settings_validation_error', {
                    error_type: 'factor_sum_mismatch',
                    user_id: <?php echo get_current_user_id(); ?>,
                    timestamp: new Date().toISOString()
                });
                
                return false;
            }
            
            // Track form submission for AI learning
            trackAIInteraction('CLOE', 'rankings_settings_saved', {
                user_id: <?php echo get_current_user_id(); ?>,
                timestamp: new Date().toISOString()
            });
        });
        
        // Handle recalculate rankings button
        $('#recalculate-rankings-btn').on('click', function(e) {
            if (!confirm('<?php echo esc_js(__('Are you sure you want to recalculate all rankings now? This may take a few minutes.', 'vortex-ai-marketplace')); ?>')) {
                e.preventDefault();
                return false;
            }
            
            // Track recalculation request for AI learning
            trackAIInteraction('CLOE', 'rankings_recalculation_requested', {
                user_id: <?php echo get_current_user_id(); ?>,
                timestamp: new Date().toISOString()
            });
        });
        
        // Track settings page view for AI learning
        trackAIInteraction('CLOE', 'rankings_settings_page_view', {
            user_id: <?php echo get_current_user_id(); ?>,
            timestamp: new Date().toISOString()
        });
        
        // Also track for BusinessStrategist
        trackAIInteraction('BusinessStrategist', 'rankings_settings_page_view', {
            user_id: <?php echo get_current_user_id(); ?>,
            timestamp: new Date().toISOString()
        });
        
        // Additional input validation
        $('#minimum_data_points, #featured_artist_count, #featured_artwork_count, #popular_categories_count').on('change', function() {
            const minVal = parseInt($(this).attr('min'));
            const maxVal = parseInt($(this).attr('max'));
            let currentVal = parseInt($(this).val());
            
            if (isNaN(currentVal) || currentVal < minVal) {
                $(this).val(minVal);
                currentVal = minVal;
            } else if (currentVal > maxVal) {
                $(this).val(maxVal);
                currentVal = maxVal;
            }
            
            // Track this adjustment for AI learning
            trackAIInteraction('CLOE', 'numeric_setting_adjusted', {
                setting: $(this).attr('id'),
                value: currentVal,
                user_id: <?php echo get_current_user_id(); ?>,
                timestamp: new Date().toISOString()
            });
        });
    });
    </script>
    <?php
}

/**
 * Handle manual rankings recalculation
 */
function vortex_handle_rankings_recalculation() {
    if (!isset($_GET['action']) || $_GET['action'] !== 'recalculate') {
        return;
    }
    
    // Verify nonce
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'vortex_recalculate_rankings_nonce')) {
        wp_die(esc_html__('Security check failed.', 'vortex-ai-marketplace'));
    }
    
    // Verify user capability
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to perform this action.', 'vortex-ai-marketplace'));
    }
    
    // Initialize AI agents for learning during recalculation
    do_action('vortex_ai_agent_init', 'CLOE', 'rankings_recalculation', array(
        'context' => 'manual_recalculation',
        'user_id' => get_current_user_id(),
        'session_id' => wp_get_session_token(),
        'learning_enabled' => true
    ));
    
    try {
        // Trigger recalculation action
        do_action('vortex_recalculate_rankings');
        
        // Record successful recalculation for AI learning
        do_action('vortex_ai_agent_learn', 'CLOE', 'rankings_recalculation_completed', array(
            'triggered_by' => get_current_user_id(),
            'timestamp' => current_time('mysql'),
            'success' => true
        ));
        
        // Redirect back with success message
        wp_redirect(add_query_arg(array('page' => 'vortex-rankings-settings', 'recalculate' => 'success'), admin_url('admin.php')));
        exit;
    } catch (Exception $e) {
        // Log error for AI learning
        do_action('vortex_ai_agent_learn', 'CLOE', 'rankings_recalculation_error', array(
            'error_message' => $e->getMessage(),
            'triggered_by' => get_current_user_id(),
            'timestamp' => current_time('mysql')
        ));
        
        // Display error message
        wp_die(
            esc_html__('Error recalculating rankings: ', 'vortex-ai-marketplace') . esc_html($e->getMessage()),
            esc_html__('Recalculation Error', 'vortex-ai-marketplace'),
            array('back_link' => true)
        );
    }
}
add_action('admin_init', 'vortex_handle_rankings_recalculation');

/**
 * Register the Rankings settings page in the admin menu
 */
function vortex_register_rankings_settings_page() {
    add_submenu_page(
        'vortex-dashboard', 
        esc_html__('Rankings Settings', 'vortex-ai-marketplace'),
        esc_html__('Rankings Settings', 'vortex-ai-marketplace'),
        'manage_options',
        'vortex-rankings-settings',
        'vortex_rankings_settings_page'
    );
}
add_action('admin_menu', 'vortex_register_rankings_settings_page', 30);

/**
 * Enqueue admin styles and scripts for Rankings settings
 */
function vortex_rankings_settings_enqueue_scripts($hook) {
    if ('vortex-dashboard_page_vortex-rankings-settings' !== $hook) {
        return;
    }
    
    wp_enqueue_style(
        'vortex-rankings-settings-css',
        plugin_dir_url(__FILE__) . '../assets/css/vortex-rankings-settings.css',
        array(),
        VORTEX_VERSION
    );
    
    wp_enqueue_script(
        'vortex-rankings-settings-js',
        plugin_dir_url(__FILE__) . '../assets/js/vortex-rankings-settings.js',
        array('jquery'),
        VORTEX_VERSION,
        true
    );
    
    wp_localize_script('vortex-rankings-settings-js', 'vortexRankings', array(
        'nonce' => wp_create_nonce('vortex_rankings_nonce'),
        'ajaxurl' => admin_url('admin-ajax.php'),
        'is_admin' => current_user_can('manage_options') ? 'yes' : 'no',
        'i18n' => array(
            'save_success' => esc_html__('Rankings settings saved successfully.', 'vortex-ai-marketplace'),
            'save_error' => esc_html__('Error saving settings.', 'vortex-ai-marketplace'),
            'confirm_recalculate' => esc_html__('Are you sure you want to recalculate all rankings now? This may take a few minutes.', 'vortex-ai-marketplace'),
            'factor_sum_error' => esc_html__('Please ensure that factor weights in each group sum to exactly 100%.', 'vortex-ai-marketplace')
        )
    ));
}
add_action('admin_enqueue_scripts', 'vortex_rankings_settings_enqueue_scripts');

/**
 * Register AJAX handler for getting AI insights about current rankings
 */
function vortex_register_rankings_insights_ajax() {
    add_action('wp_ajax_vortex_get_rankings_insights', 'vortex_ajax_get_rankings_insights');
}
add_action('init', 'vortex_register_rankings_insights_ajax');

/**
 * AJAX handler for getting AI insights about current rankings
 */
function vortex_ajax_get_rankings_insights() {
    // Security checks
    if (!check_ajax_referer('vortex_rankings_nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => esc_html__('Security check failed.', 'vortex-ai-marketplace')));
    }
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => esc_html__('You do not have permission to perform this action.', 'vortex-ai-marketplace')));
    }
    
    try {
        // Initialize CLOE for learning from this request
        do_action('vortex_ai_agent_init', 'CLOE', 'rankings_insights_request', array(
            'context' => 'admin_insights_request',
            'user_id' => get_current_user_id(),
            'session_id' => wp_get_session_token(),
            'learning_enabled' => true
        ));
        
        // Get insights from CLOE
        $cloe_insights = apply_filters('vortex_cloe_ranking_insights', array());
        
        // Get insights from BusinessStrategist
        $business_insights = apply_filters('vortex_business_strategist_ranking_insights', array());
        
        // Return insights data
        wp_send_json_success(array(
            'cloe_insights' => $cloe_insights,
            'business_insights' => $business_insights,
            'timestamp' => current_time('mysql')
        ));
    } catch (Exception $e) {
        // Log error for AI learning
        do_action('vortex_ai_agent_learn', 'CLOE', 'rankings_insights_error', array(
            'error_message' => $e->getMessage(),
            'user_id' => get_current_user_id(),
            'timestamp' => current_time('mysql')
        ));
        
        wp_send_json_error(array('message' => $e->getMessage()));
    }
}

/**
 * Add scheduled event for rankings recalculation on plugin activation
 */
function vortex_schedule_rankings_recalculation() {
    if (!wp_next_scheduled('vortex_recalculate_rankings')) {
        $settings = get_option('vortex_rankings_settings', array());
        $interval = isset($settings['rankings_refresh_interval']) ? $settings['rankings_refresh_interval'] : 'daily';
        wp_schedule_event(time(), $interval, 'vortex_recalculate_rankings');
    }
}
add_action('vortex_plugin_activated', 'vortex_schedule_rankings_recalculation');

/**
 * Clear scheduled event on plugin deactivation
 */
function vortex_clear_rankings_schedule() {
    wp_clear_scheduled_hook('vortex_recalculate_rankings');
}
add_action('vortex_plugin_deactivated', 'vortex_clear_rankings_schedule');

load_plugin_textdomain('vortex-ai-marketplace', false, dirname(plugin_basename(__FILE__)) . '/languages/'); 