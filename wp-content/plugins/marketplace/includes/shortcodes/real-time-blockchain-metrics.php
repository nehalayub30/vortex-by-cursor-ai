<?php
/**
 * VORTEX Marketplace Real-Time Blockchain Metrics Shortcode
 *
 * Provides real-time metrics from the TOLA blockchain with advanced data visualization
 *
 * @package VORTEX_Marketplace
 * @subpackage Shortcodes
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Render real-time blockchain metrics
 */
function vortex_real_time_blockchain_metrics_shortcode($atts) {
    $defaults = array(
        'days' => 7,
        'type' => 'all',
        'include_artists' => 'true',
        'include_categories' => 'true',
        'include_artworks' => 'true',
        'refresh_rate' => 30,  // seconds between auto-refresh
        'layout' => 'dashboard'
    );

    $atts = shortcode_atts($defaults, $atts, 'vortex_real_time_metrics');
    
    // Convert string booleans to actual booleans
    $include_artists = filter_var($atts['include_artists'], FILTER_VALIDATE_BOOLEAN);
    $include_categories = filter_var($atts['include_categories'], FILTER_VALIDATE_BOOLEAN);
    $include_artworks = filter_var($atts['include_artworks'], FILTER_VALIDATE_BOOLEAN);
    
    // Sanitize other inputs
    $days = intval($atts['days']);
    $days = min(max($days, 1), 365); // Between 1 and 365
    
    $type = in_array($atts['type'], array('artwork', 'token', 'all')) ? $atts['type'] : 'all';
    $refresh_rate = max(intval($atts['refresh_rate']), 5); // Minimum 5 seconds
    $layout = in_array($atts['layout'], array('dashboard', 'minimal', 'detailed')) ? $atts['layout'] : 'dashboard';
    
    // Get blockchain metrics
    if (class_exists('VORTEX_Blockchain_Metrics')) {
        $blockchain_metrics = VORTEX_Blockchain_Metrics::get_instance();
        $metrics = $blockchain_metrics->get_metrics($type, $days);
    } else {
        return '<div class="vortex-error-message">' . 
               __('Blockchain metrics component is not available.', 'vortex-marketplace') . 
               '</div>';
    }
    
    // Prepare unique identifier for this instance
    $dashboard_id = 'vortex-blockchain-metrics-' . uniqid();
    
    // Start output buffer
    ob_start();
    
    // Include CSS
    wp_enqueue_style('vortex-blockchain-metrics-css');
    
    // Include JS
    wp_enqueue_script('chart-js');
    wp_enqueue_script('vortex-blockchain-metrics-js');
    
    // Add inline script for this specific instance
    $script = "
    jQuery(document).ready(function($) {
        var {$dashboard_id}_refreshTimer;
        
        function initializeMetrics() {
            vortexBlockchainMetrics.initialize('{$dashboard_id}', {
                days: {$days},
                type: '{$type}',
                refreshRate: {$refresh_rate},
                layout: '{$layout}'
            });
        }
        
        initializeMetrics();
        
        // Setup auto refresh
        {$dashboard_id}_refreshTimer = setInterval(function() {
            vortexBlockchainMetrics.refreshData('{$dashboard_id}');
        }, {$refresh_rate} * 1000);
        
        // Clean up when navigating away
        $(window).on('beforeunload', function() {
            clearInterval({$dashboard_id}_refreshTimer);
        });
    });
    ";
    
    wp_add_inline_script('vortex-blockchain-metrics-js', $script);
    
    // Main dashboard container
    echo '<div id="' . esc_attr($dashboard_id) . '" class="vortex-blockchain-dashboard vortex-layout-' . esc_attr($layout) . '">';
    
    // Dashboard header
    echo '<div class="vortex-dashboard-header">';
    echo '<h2>' . esc_html__('Real-Time TOLA Blockchain Metrics', 'vortex-marketplace') . '</h2>';
    echo '<div class="vortex-last-updated">' . 
         sprintf(esc_html__('Last updated: %s', 'vortex-marketplace'), 
         '<span class="update-timestamp">' . current_time('M j, Y H:i:s') . '</span>') . 
         ' <button class="vortex-refresh-btn"><span class="dashicons dashicons-update"></span></button></div>';
    echo '</div>';
    
    // Overview cards
    echo '<div class="vortex-metrics-overview">';
    
    // Artwork card
    echo '<div class="vortex-metric-card vortex-artwork-card">';
    echo '<h3>' . esc_html__('Tokenized Artworks', 'vortex-marketplace') . '</h3>';
    echo '<div class="vortex-metric-value artwork-count">' . 
         (isset($metrics['artwork']['total_artworks']) ? number_format($metrics['artwork']['total_artworks']) : '0') . 
         '</div>';
    echo '</div>';
    
    // Volume card
    echo '<div class="vortex-metric-card vortex-volume-card">';
    echo '<h3>' . esc_html__('Trading Volume (TOLA)', 'vortex-marketplace') . '</h3>';
    echo '<div class="vortex-metric-value volume-amount">' . 
         (isset($metrics['artwork']['total_volume']) ? number_format($metrics['artwork']['total_volume'], 2) : '0.00') . 
         '</div>';
    echo '</div>';
    
    // Token Price card
    echo '<div class="vortex-metric-card vortex-token-card">';
    echo '<h3>' . esc_html__('TOLA Token Price (USD)', 'vortex-marketplace') . '</h3>';
    echo '<div class="vortex-metric-value token-price">' . 
         (isset($metrics['token']['current_price']) ? '$' . number_format($metrics['token']['current_price'], 2) : '$0.00') . 
         '</div>';
    echo '</div>';
    
    // Holders card
    echo '<div class="vortex-metric-card vortex-holders-card">';
    echo '<h3>' . esc_html__('Token Holders', 'vortex-marketplace') . '</h3>';
    echo '<div class="vortex-metric-value holders-count">' . 
         (isset($metrics['token']['total_holders']) ? number_format($metrics['token']['total_holders']) : '0') . 
         '</div>';
    echo '</div>';
    
    echo '</div>'; // End overview cards
    
    // Charts section
    echo '<div class="vortex-charts-container">';
    
    // Artwork Creation chart
    echo '<div class="vortex-chart-wrapper">';
    echo '<h3>' . esc_html__('Artwork Creation & Sales', 'vortex-marketplace') . '</h3>';
    echo '<canvas id="' . esc_attr($dashboard_id) . '-creation-chart" height="250"></canvas>';
    echo '</div>';
    
    // Trading Volume chart
    echo '<div class="vortex-chart-wrapper">';
    echo '<h3>' . esc_html__('Trading Volume', 'vortex-marketplace') . '</h3>';
    echo '<canvas id="' . esc_attr($dashboard_id) . '-volume-chart" height="250"></canvas>';
    echo '</div>';
    
    echo '</div>'; // End charts section
    
    // Artists and Categories section
    if ($include_artists || $include_categories) {
        echo '<div class="vortex-metrics-details">';
        
        // Top Artists
        if ($include_artists && isset($metrics['artwork']['top_artists'])) {
            echo '<div class="vortex-top-artists">';
            echo '<h3>' . esc_html__('Top Artists by Trading Volume', 'vortex-marketplace') . '</h3>';
            echo '<div class="vortex-table-container">';
            echo '<table class="vortex-data-table">';
            echo '<thead><tr><th>' . esc_html__('Artist', 'vortex-marketplace') . '</th>';
            echo '<th>' . esc_html__('Swaps', 'vortex-marketplace') . '</th>';
            echo '<th>' . esc_html__('Volume (TOLA)', 'vortex-marketplace') . '</th></tr></thead>';
            echo '<tbody>';
            
            foreach ($metrics['artwork']['top_artists'] as $artist) {
                echo '<tr>';
                echo '<td>' . esc_html($artist['artist_name']) . '</td>';
                echo '<td>' . number_format($artist['swap_count']) . '</td>';
                echo '<td>' . number_format($artist['volume'], 2) . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
            echo '</div>'; // End table container
            echo '</div>'; // End top artists
        }
        
        // Top Categories
        if ($include_categories && isset($metrics['artwork']['top_categories'])) {
            echo '<div class="vortex-top-categories">';
            echo '<h3>' . esc_html__('Top Categories by Trading Volume', 'vortex-marketplace') . '</h3>';
            echo '<div class="vortex-table-container">';
            echo '<table class="vortex-data-table">';
            echo '<thead><tr><th>' . esc_html__('Category', 'vortex-marketplace') . '</th>';
            echo '<th>' . esc_html__('Count', 'vortex-marketplace') . '</th>';
            echo '<th>' . esc_html__('Volume (TOLA)', 'vortex-marketplace') . '</th></tr></thead>';
            echo '<tbody>';
            
            foreach ($metrics['artwork']['top_categories'] as $category) {
                echo '<tr>';
                echo '<td>' . esc_html($category['category']) . '</td>';
                echo '<td>' . number_format($category['count']) . '</td>';
                echo '<td>' . number_format($category['volume'], 2) . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
            echo '</div>'; // End table container
            echo '</div>'; // End top categories
        }
        
        echo '</div>'; // End metrics details
    }
    
    // Trending Artworks section
    if ($include_artworks && isset($metrics['artwork']['trending_artworks'])) {
        echo '<div class="vortex-trending-artworks">';
        echo '<h3>' . esc_html__('Trending Artworks', 'vortex-marketplace') . '</h3>';
        echo '<div class="vortex-artwork-grid">';
        
        foreach ($metrics['artwork']['trending_artworks'] as $artwork) {
            echo '<div class="vortex-artwork-card">';
            
            if (!empty($artwork['thumbnail_url'])) {
                echo '<div class="vortex-artwork-image">';
                echo '<img src="' . esc_url($artwork['thumbnail_url']) . '" alt="' . esc_attr($artwork['title']) . '">';
                echo '</div>';
            }
            
            echo '<div class="vortex-artwork-info">';
            echo '<h4>' . esc_html($artwork['title']) . '</h4>';
            echo '<p class="vortex-artwork-artist">' . esc_html__('By', 'vortex-marketplace') . ' ' . esc_html($artwork['artist_name']) . '</p>';
            echo '<p class="vortex-artwork-stats">';
            echo '<span class="vortex-transfer-count">' . sprintf(esc_html__('%s Transfers', 'vortex-marketplace'), number_format($artwork['transfer_count'])) . '</span>';
            echo '<span class="vortex-highest-amount">' . sprintf(esc_html__('Highest: %s TOLA', 'vortex-marketplace'), number_format($artwork['highest_amount'], 2)) . '</span>';
            echo '</p>';
            
            if (!empty($artwork['permalink'])) {
                echo '<a href="' . esc_url($artwork['permalink']) . '" class="vortex-view-artwork">' . esc_html__('View Artwork', 'vortex-marketplace') . '</a>';
            }
            
            echo '</div>'; // End artwork info
            echo '</div>'; // End artwork card
        }
        
        echo '</div>'; // End artwork grid
        echo '</div>'; // End trending artworks
    }
    
    // Add export and view more links
    echo '<div class="vortex-dashboard-footer">';
    echo '<a href="#" class="vortex-export-data" data-id="' . esc_attr($dashboard_id) . '">' . esc_html__('Export Data', 'vortex-marketplace') . '</a>';
    echo '<a href="' . esc_url(site_url('blockchain-explorer')) . '" class="vortex-view-explorer">' . esc_html__('View TOLA Explorer', 'vortex-marketplace') . '</a>';
    echo '</div>';
    
    // Add data attribution
    echo '<div class="vortex-data-attribution">';
    echo esc_html__('Powered by TOLA Blockchain', 'vortex-marketplace');
    echo '</div>';
    
    echo '</div>'; // End dashboard container
    
    return ob_get_clean();
}
add_shortcode('vortex_real_time_blockchain_metrics', 'vortex_real_time_blockchain_metrics_shortcode'); 