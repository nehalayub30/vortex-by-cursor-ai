/**
 * Render expanded blockchain metrics shortcode
 * 
 * Provides a comprehensive real-time dashboard of all blockchain metrics
 * including trending artists, popular categories, and market trends.
 *
 * @param array $atts Shortcode attributes
 * @return string Rendered shortcode HTML
 */
public function render_expanded_blockchain_metrics($atts = []) {
    // Parse attributes
    $atts = shortcode_atts([
        'refresh' => 60, // Refresh interval in seconds
        'show_artists' => 'yes',
        'show_categories' => 'yes',
        'show_trends' => 'yes',
        'show_tokens' => 'yes',
        'limit_artists' => 5,
        'limit_categories' => 5,
        'theme' => 'light'
    ], $atts);
    
    // Convert string attributes to boolean or integer
    $show_artists = ($atts['show_artists'] === 'yes');
    $show_categories = ($atts['show_categories'] === 'yes');
    $show_trends = ($atts['show_trends'] === 'yes');
    $show_tokens = ($atts['show_tokens'] === 'yes');
    $limit_artists = intval($atts['limit_artists']);
    $limit_categories = intval($atts['limit_categories']);
    $refresh_interval = intval($atts['refresh']);
    
    // Enqueue necessary scripts and styles
    wp_enqueue_style('vortex-blockchain-metrics-css', VORTEX_PLUGIN_URL . 'assets/css/blockchain-metrics.css', [], VORTEX_VERSION);
    wp_enqueue_script('vortex-blockchain-metrics-js', VORTEX_PLUGIN_URL . 'assets/js/blockchain-metrics.js', ['jquery', 'chart-js'], VORTEX_VERSION, true);
    
    // Check if we have the blockchain metrics class
    if (!class_exists('Vortex_Blockchain_Metrics')) {
        return '<div class="vortex-error">Blockchain metrics module not available</div>';
    }
    
    // Get metrics
    $metrics_instance = new Vortex_Blockchain_Metrics();
    
    // Check for expanded metrics method
    if (method_exists($metrics_instance, 'get_expanded_blockchain_metrics')) {
        $metrics = $metrics_instance->get_expanded_blockchain_metrics();
    } else {
        // Fallback to regular metrics
        $metrics = $metrics_instance->get_blockchain_metrics();
    }
    
    // Prepare data for JavaScript
    wp_localize_script('vortex-blockchain-metrics-js', 'vortexBlockchainData', [
        'metrics' => $metrics,
        'settings' => [
            'refreshInterval' => $refresh_interval * 1000,
            'theme' => $atts['theme'],
            'showArtists' => $show_artists,
            'showCategories' => $show_categories,
            'showTrends' => $show_trends,
            'showTokens' => $show_tokens
        ],
        'i18n' => [
            'trending_artists' => __('Trending Artists', 'vortex-marketplace'),
            'popular_categories' => __('Popular Categories', 'vortex-marketplace'),
            'market_trends' => __('Market Trends', 'vortex-marketplace'),
            'tokenized_artworks' => __('Recent Tokenized Artworks', 'vortex-marketplace'),
            'last_updated' => __('Last Updated:', 'vortex-marketplace'),
            'total_volume' => __('Total Volume:', 'vortex-marketplace'),
            'refresh' => __('Refresh', 'vortex-marketplace')
        ]
    ]);
    
    // Start output buffer
    ob_start();
    
    // Output HTML structure
    ?>
    <div class="vortex-blockchain-metrics-dashboard theme-<?php echo esc_attr($atts['theme']); ?>" 
         data-refresh="<?php echo esc_attr($refresh_interval); ?>">
        
        <div class="vortex-metrics-header">
            <h2><?php _e('TOLA Blockchain Metrics', 'vortex-marketplace'); ?></h2>
            <div class="vortex-metrics-controls">
                <span class="vortex-last-updated">
                    <?php _e('Last Updated:', 'vortex-marketplace'); ?> 
                    <span class="update-time"><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format')); ?></span>
                </span>
                <button class="vortex-refresh-button">
                    <span class="dashicons dashicons-update"></span> 
                    <?php _e('Refresh', 'vortex-marketplace'); ?>
                </button>
            </div>
        </div>
        
        <div class="vortex-metrics-summary">
            <div class="vortex-metric-card">
                <div class="metric-title"><?php _e('Tokenized Artworks', 'vortex-marketplace'); ?></div>
                <div class="metric-value"><?php echo number_format($metrics['total_artworks']); ?></div>
            </div>
            
            <div class="vortex-metric-card">
                <div class="metric-title"><?php _e('Total Volume', 'vortex-marketplace'); ?></div>
                <div class="metric-value"><?php echo number_format($metrics['total_volume'], 2); ?> TOLA</div>
            </div>
            
            <div class="vortex-metric-card">
                <div class="metric-title"><?php _e('Active Artists', 'vortex-marketplace'); ?></div>
                <div class="metric-value"><?php echo number_format(count($metrics['top_artists'])); ?></div>
            </div>
            
            <div class="vortex-metric-card">
                <div class="metric-title"><?php _e('Daily Volume', 'vortex-marketplace'); ?></div>
                <div class="metric-value">
                    <?php 
                    if (isset($metrics['market_trends']['daily_volume'])) {
                        echo number_format($metrics['market_trends']['daily_volume'], 2);
                    } else {
                        echo number_format(0, 2);
                    }
                    ?> TOLA
                </div>
            </div>
        </div>
        
        <?php if ($show_artists): ?>
        <div class="vortex-metrics-section">
            <h3><?php _e('Trending Artists', 'vortex-marketplace'); ?></h3>
            <div class="vortex-trending-artists">
                <?php if (!empty($metrics['top_artists'])): ?>
                <table class="vortex-data-table">
                    <thead>
                        <tr>
                            <th><?php _e('Artist', 'vortex-marketplace'); ?></th>
                            <th><?php _e('Swap Count', 'vortex-marketplace'); ?></th>
                            <th><?php _e('Volume', 'vortex-marketplace'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($metrics['top_artists'], 0, $limit_artists) as $artist): ?>
                        <tr>
                            <td><?php echo esc_html($artist['artist_name']); ?></td>
                            <td><?php echo number_format($artist['swap_count']); ?></td>
                            <td><?php echo number_format($artist['volume'], 2); ?> TOLA</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="no-data"><?php _e('No trending artists data available.', 'vortex-marketplace'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($show_categories): ?>
        <div class="vortex-metrics-section">
            <h3><?php _e('Popular Categories', 'vortex-marketplace'); ?></h3>
            <div class="vortex-popular-categories">
                <?php if (!empty($metrics['top_categories'])): ?>
                <table class="vortex-data-table">
                    <thead>
                        <tr>
                            <th><?php _e('Category', 'vortex-marketplace'); ?></th>
                            <th><?php _e('Artworks', 'vortex-marketplace'); ?></th>
                            <th><?php _e('Volume', 'vortex-marketplace'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($metrics['top_categories'], 0, $limit_categories) as $category): ?>
                        <tr>
                            <td><?php echo esc_html($category['category']); ?></td>
                            <td><?php echo number_format($category['count']); ?></td>
                            <td><?php echo number_format($category['volume'], 2); ?> TOLA</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="no-data"><?php _e('No category data available.', 'vortex-marketplace'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($show_tokens && isset($metrics['recent_tokens']) && !empty($metrics['recent_tokens'])): ?>
        <div class="vortex-metrics-section">
            <h3><?php _e('Recent Tokenized Artworks', 'vortex-marketplace'); ?></h3>
            <div class="vortex-recent-tokens">
                <?php foreach ($metrics['recent_tokens'] as $token): ?>
                <div class="token-item">
                    <div class="token-title">
                        <a href="<?php echo esc_url(get_permalink($token['id'])); ?>">
                            <?php echo esc_html($token['title']); ?>
                        </a>
                    </div>
                    <div class="token-artist">
                        <?php _e('By', 'vortex-marketplace'); ?> 
                        <?php echo esc_html($token['artist_name']); ?>
                    </div>
                    <div class="token-date">
                        <?php echo date_i18n(get_option('date_format'), strtotime($token['created_at'])); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($show_trends && isset($metrics['market_trends']['price_movements']) && !empty($metrics['market_trends']['price_movements'])): ?>
        <div class="vortex-metrics-section">
            <h3><?php _e('Price Movements', 'vortex-marketplace'); ?></h3>
            <div class="vortex-price-movements">
                <table class="vortex-data-table">
                    <thead>
                        <tr>
                            <th><?php _e('Artwork', 'vortex-marketplace'); ?></th>
                            <th><?php _e('Initial Price', 'vortex-marketplace'); ?></th>
                            <th><?php _e('Current Price', 'vortex-marketplace'); ?></th>
                            <th><?php _e('Change', 'vortex-marketplace'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($metrics['market_trends']['price_movements'] as $movement): ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url(get_permalink($movement['id'])); ?>">
                                    <?php echo esc_html($movement['title']); ?>
                                </a>
                            </td>
                            <td><?php echo number_format($movement['initial_price'], 2); ?> TOLA</td>
                            <td><?php echo number_format($movement['current_price'], 2); ?> TOLA</td>
                            <td class="<?php echo $movement['price_change'] >= 0 ? 'positive' : 'negative'; ?>">
                                <?php echo ($movement['price_change'] >= 0 ? '+' : ''); ?><?php echo number_format($movement['price_change'], 2); ?>%
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="vortex-metrics-footer">
            <div class="vortex-metrics-attribution">
                <?php _e('Powered by TOLA Blockchain', 'vortex-marketplace'); ?>
            </div>
        </div>
    </div>
    <?php
    
    // Return output buffer
    return ob_get_clean();
} 