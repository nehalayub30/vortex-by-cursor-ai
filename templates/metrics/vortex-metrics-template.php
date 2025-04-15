<?php
/**
 * Template for displaying marketplace metrics
 *
 * This template can be overridden by copying it to yourtheme/vortex/metrics/vortex-metrics-template.php
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Enqueue necessary scripts and styles
wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js', array(), '3.7.1', true);
wp_enqueue_style('vortex-metrics-style', VORTEX_PLUGIN_URL . 'assets/css/vortex-metrics.css', array(), VORTEX_VERSION);
wp_enqueue_script('vortex-metrics-script', VORTEX_PLUGIN_URL . 'assets/js/vortex-metrics.js', array('jquery', 'chart-js'), VORTEX_VERSION, true);

// Prepare data for JavaScript
$js_data = array(
    'labels' => array(),
    'values' => array(),
    'blockchain_data' => isset($data['blockchain']) ? $data['blockchain'] : array()
);

// Pass the metric data to JavaScript
wp_localize_script('vortex-metrics-script', 'vortexMetricsData', $js_data);
?>

<div class="vortex-metrics-container">
    <div class="vortex-metrics-tabs">
        <ul class="vortex-tab-navigation">
            <li class="<?php echo ($atts['view'] === 'summary') ? 'active' : ''; ?>">
                <a href="#summary"><?php _e('Summary', 'vortex-marketplace'); ?></a>
            </li>
            <li class="<?php echo ($atts['view'] === 'artists') ? 'active' : ''; ?>">
                <a href="#artists"><?php _e('Artists', 'vortex-marketplace'); ?></a>
            </li>
            <li class="<?php echo ($atts['view'] === 'categories') ? 'active' : ''; ?>">
                <a href="#categories"><?php _e('Categories', 'vortex-marketplace'); ?></a>
            </li>
            <li class="<?php echo ($atts['view'] === 'transactions') ? 'active' : ''; ?>">
                <a href="#transactions"><?php _e('Transactions', 'vortex-marketplace'); ?></a>
            </li>
            <li class="<?php echo ($atts['view'] === 'blockchain') ? 'active' : ''; ?>">
                <a href="#blockchain"><?php _e('Blockchain', 'vortex-marketplace'); ?></a>
            </li>
        </ul>
        
        <!-- Summary Tab -->
        <div id="summary" class="vortex-tab-content <?php echo ($atts['view'] === 'summary') ? 'active' : ''; ?>">
            <div class="vortex-summary-metrics">
                <div class="vortex-metric-card">
                    <div class="metric-icon">
                        <i class="dashicons dashicons-art"></i>
                    </div>
                    <div class="metric-content">
                        <h3><?php _e('Total Artworks', 'vortex-marketplace'); ?></h3>
                        <div class="metric-value"><?php echo number_format($data['total_artworks']); ?></div>
                        <?php if (isset($data['new_artworks'])): ?>
                            <div class="metric-sub">
                                <?php printf(__('%s new in last %d days', 'vortex-marketplace'), 
                                    number_format($data['new_artworks']),
                                    $data['period_days']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="vortex-metric-card">
                    <div class="metric-icon">
                        <i class="dashicons dashicons-groups"></i>
                    </div>
                    <div class="metric-content">
                        <h3><?php _e('Total Artists', 'vortex-marketplace'); ?></h3>
                        <div class="metric-value"><?php echo number_format($data['total_artists']); ?></div>
                    </div>
                </div>
                
                <div class="vortex-metric-card">
                    <div class="metric-icon">
                        <i class="dashicons dashicons-money-alt"></i>
                    </div>
                    <div class="metric-content">
                        <h3><?php _e('Total Sales', 'vortex-marketplace'); ?></h3>
                        <div class="metric-value"><?php echo number_format($data['total_sales']); ?></div>
                        <?php if (isset($data['period_sales'])): ?>
                            <div class="metric-sub">
                                <?php printf(__('%s in last %d days', 'vortex-marketplace'), 
                                    number_format($data['period_sales']),
                                    $data['period_days']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="vortex-metric-card">
                    <div class="metric-icon">
                        <i class="dashicons dashicons-chart-line"></i>
                    </div>
                    <div class="metric-content">
                        <h3><?php _e('Sales Volume', 'vortex-marketplace'); ?></h3>
                        <div class="metric-value"><?php echo number_format($data['total_value'], 2); ?> ETH</div>
                        <?php if (isset($data['period_value'])): ?>
                            <div class="metric-sub">
                                <?php printf(__('%s ETH in last %d days', 'vortex-marketplace'), 
                                    number_format($data['period_value'], 2),
                                    $data['period_days']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($data['blockchain'])): ?>
            <div class="vortex-blockchain-summary">
                <h3><?php _e('Blockchain Summary', 'vortex-marketplace'); ?></h3>
                <div class="vortex-summary-metrics">
                    <?php if (isset($data['blockchain']['total_nfts'])): ?>
                    <div class="vortex-metric-card">
                        <div class="metric-icon">
                            <i class="dashicons dashicons-admin-network"></i>
                        </div>
                        <div class="metric-content">
                            <h3><?php _e('NFTs on TOLA', 'vortex-marketplace'); ?></h3>
                            <div class="metric-value"><?php echo number_format($data['blockchain']['total_nfts']); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($data['blockchain']['transaction_count'])): ?>
                    <div class="vortex-metric-card">
                        <div class="metric-icon">
                            <i class="dashicons dashicons-randomize"></i>
                        </div>
                        <div class="metric-content">
                            <h3><?php _e('Total Transactions', 'vortex-marketplace'); ?></h3>
                            <div class="metric-value"><?php echo number_format($data['blockchain']['transaction_count']); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($data['blockchain']['unique_owners'])): ?>
                    <div class="vortex-metric-card">
                        <div class="metric-icon">
                            <i class="dashicons dashicons-businessperson"></i>
                        </div>
                        <div class="metric-content">
                            <h3><?php _e('Unique Owners', 'vortex-marketplace'); ?></h3>
                            <div class="metric-value"><?php echo number_format($data['blockchain']['unique_owners']); ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="vortex-chart-container">
                <h3><?php _e('Sales Trend', 'vortex-marketplace'); ?></h3>
                <canvas id="vortex-sales-chart"></canvas>
            </div>
        </div>
        
        <!-- Artists Tab -->
        <div id="artists" class="vortex-tab-content <?php echo ($atts['view'] === 'artists') ? 'active' : ''; ?>">
            <?php if (!empty($data['most_active'])): ?>
            <div class="vortex-section">
                <h3><?php _e('Most Active Artists', 'vortex-marketplace'); ?></h3>
                <div class="vortex-artist-grid">
                    <?php foreach ($data['most_active'] as $artist): ?>
                    <div class="vortex-artist-card">
                        <div class="artist-avatar">
                            <img src="<?php echo esc_url($artist->avatar); ?>" alt="<?php echo esc_attr($artist->display_name); ?>">
                        </div>
                        <div class="artist-details">
                            <h4><?php echo esc_html($artist->display_name); ?></h4>
                            <div class="artist-stats">
                                <?php printf(_n('%s Artwork', '%s Artworks', $artist->artwork_count, 'vortex-marketplace'), 
                                    number_format($artist->artwork_count)); ?>
                            </div>
                            <a href="<?php echo esc_url(get_author_posts_url($artist->user_id)); ?>" class="artist-link">
                                <?php _e('View Profile', 'vortex-marketplace'); ?>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($data['top_selling'])): ?>
            <div class="vortex-section">
                <h3><?php _e('Top Selling Artists', 'vortex-marketplace'); ?></h3>
                <div class="vortex-artist-grid">
                    <?php foreach ($data['top_selling'] as $artist): ?>
                    <div class="vortex-artist-card">
                        <div class="artist-avatar">
                            <img src="<?php echo esc_url($artist->avatar); ?>" alt="<?php echo esc_attr($artist->display_name); ?>">
                        </div>
                        <div class="artist-details">
                            <h4><?php echo esc_html($artist->display_name); ?></h4>
                            <div class="artist-stats">
                                <?php printf(__('Sales: %s ETH', 'vortex-marketplace'), number_format($artist->total_sales, 2)); ?>
                            </div>
                            <a href="<?php echo esc_url(get_author_posts_url($artist->artist_id)); ?>" class="artist-link">
                                <?php _e('View Profile', 'vortex-marketplace'); ?>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($data['blockchain']) && !empty($data['blockchain']['artists'])): ?>
            <div class="vortex-section">
                <h3><?php _e('Top Artists on Blockchain', 'vortex-marketplace'); ?></h3>
                <div class="vortex-blockchain-artists">
                    <table class="vortex-data-table">
                        <thead>
                            <tr>
                                <th><?php _e('Rank', 'vortex-marketplace'); ?></th>
                                <th><?php _e('Artist', 'vortex-marketplace'); ?></th>
                                <th><?php _e('NFTs Created', 'vortex-marketplace'); ?></th>
                                <th><?php _e('Total Volume', 'vortex-marketplace'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['blockchain']['artists'] as $index => $artist): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <?php if (!empty($artist['profile_url'])): ?>
                                    <a href="<?php echo esc_url($artist['profile_url']); ?>">
                                        <?php echo esc_html($artist['name']); ?>
                                    </a>
                                    <?php else: ?>
                                        <?php echo esc_html($artist['name']); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($artist['nft_count']); ?></td>
                                <td><?php echo number_format($artist['volume'], 2); ?> ETH</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Categories Tab -->
        <div id="categories" class="vortex-tab-content <?php echo ($atts['view'] === 'categories') ? 'active' : ''; ?>">
            <?php if (!empty($data['popular_categories'])): ?>
            <div class="vortex-chart-container">
                <h3><?php _e('Popular Categories', 'vortex-marketplace'); ?></h3>
                <canvas id="vortex-categories-chart"></canvas>
            </div>
            
            <div class="vortex-section">
                <table class="vortex-data-table">
                    <thead>
                        <tr>
                            <th><?php _e('Category', 'vortex-marketplace'); ?></th>
                            <th><?php _e('Artwork Count', 'vortex-marketplace'); ?></th>
                            <th><?php _e('% of Total', 'vortex-marketplace'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_count = array_sum(array_column($data['popular_categories'], 'artwork_count'));
                        foreach ($data['popular_categories'] as $category): 
                            $percentage = ($total_count > 0) ? ($category->artwork_count / $total_count) * 100 : 0;
                        ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url(get_term_link($category->term_id)); ?>">
                                    <?php echo esc_html($category->name); ?>
                                </a>
                            </td>
                            <td><?php echo number_format($category->artwork_count); ?></td>
                            <td><?php echo number_format($percentage, 1); ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($data['blockchain']) && !empty($data['blockchain']['categories'])): ?>
            <div class="vortex-section">
                <h3><?php _e('Top Categories on Blockchain', 'vortex-marketplace'); ?></h3>
                <div class="vortex-blockchain-categories">
                    <table class="vortex-data-table">
                        <thead>
                            <tr>
                                <th><?php _e('Rank', 'vortex-marketplace'); ?></th>
                                <th><?php _e('Category', 'vortex-marketplace'); ?></th>
                                <th><?php _e('NFT Count', 'vortex-marketplace'); ?></th>
                                <th><?php _e('Total Volume', 'vortex-marketplace'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['blockchain']['categories'] as $index => $category): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo esc_html($category['name']); ?></td>
                                <td><?php echo number_format($category['nft_count']); ?></td>
                                <td><?php echo number_format($category['volume'], 2); ?> ETH</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Transactions Tab -->
        <div id="transactions" class="vortex-tab-content <?php echo ($atts['view'] === 'transactions') ? 'active' : ''; ?>">
            <?php if (!empty($data['daily_volume'])): ?>
            <div class="vortex-chart-container">
                <h3><?php _e('Transaction Volume', 'vortex-marketplace'); ?></h3>
                <canvas id="vortex-transaction-chart"></canvas>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($data['recent_transactions'])): ?>
            <div class="vortex-section">
                <h3><?php _e('Recent Transactions', 'vortex-marketplace'); ?></h3>
                <table class="vortex-data-table">
                    <thead>
                        <tr>
                            <th><?php _e('Date', 'vortex-marketplace'); ?></th>
                            <th><?php _e('Artwork', 'vortex-marketplace'); ?></th>
                            <th><?php _e('Seller', 'vortex-marketplace'); ?></th>
                            <th><?php _e('Buyer', 'vortex-marketplace'); ?></th>
                            <th><?php _e('Price', 'vortex-marketplace'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['recent_transactions'] as $tx): ?>
                        <tr>
                            <td><?php echo esc_html($tx->formatted_date); ?></td>
                            <td>
                                <a href="<?php echo esc_url(get_permalink($tx->artwork_id)); ?>">
                                    <?php echo esc_html($tx->artwork_title); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html($tx->seller_name); ?></td>
                            <td><?php echo esc_html($tx->buyer_name); ?></td>
                            <td><?php echo number_format($tx->amount, 2); ?> ETH</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($data['highest_value'])): ?>
            <div class="vortex-section">
                <h3><?php _e('Highest Value Transactions', 'vortex-marketplace'); ?></h3>
                <table class="vortex-data-table">
                    <thead>
                        <tr>
                            <th><?php _e('Date', 'vortex-marketplace'); ?></th>
                            <th><?php _e('Artwork', 'vortex-marketplace'); ?></th>
                            <th><?php _e('Seller', 'vortex-marketplace'); ?></th>
                            <th><?php _e('Buyer', 'vortex-marketplace'); ?></th>
                            <th><?php _e('Price', 'vortex-marketplace'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['highest_value'] as $tx): ?>
                        <tr>
                            <td><?php echo esc_html($tx->formatted_date); ?></td>
                            <td>
                                <a href="<?php echo esc_url(get_permalink($tx->artwork_id)); ?>">
                                    <?php echo esc_html($tx->artwork_title); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html($tx->seller_name); ?></td>
                            <td><?php echo esc_html($tx->buyer_name); ?></td>
                            <td><?php echo number_format($tx->amount, 2); ?> ETH</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Blockchain Tab -->
        <div id="blockchain" class="vortex-tab-content <?php echo ($atts['view'] === 'blockchain') ? 'active' : ''; ?>">
            <?php if (!empty($data['blockchain'])): ?>
                <div class="vortex-blockchain-metrics">
                    <div class="vortex-section">
                        <h3><?php _e('TOLA Blockchain Overview', 'vortex-marketplace'); ?></h3>
                        
                        <div class="vortex-summary-metrics">
                            <?php if (isset($data['blockchain']['total_nfts'])): ?>
                            <div class="vortex-metric-card">
                                <div class="metric-icon">
                                    <i class="dashicons dashicons-admin-network"></i>
                                </div>
                                <div class="metric-content">
                                    <h3><?php _e('Total NFTs', 'vortex-marketplace'); ?></h3>
                                    <div class="metric-value"><?php echo number_format($data['blockchain']['total_nfts']); ?></div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($data['blockchain']['transaction_count'])): ?>
                            <div class="vortex-metric-card">
                                <div class="metric-icon">
                                    <i class="dashicons dashicons-randomize"></i>
                                </div>
                                <div class="metric-content">
                                    <h3><?php _e('Transactions', 'vortex-marketplace'); ?></h3>
                                    <div class="metric-value"><?php echo number_format($data['blockchain']['transaction_count']); ?></div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($data['blockchain']['unique_owners'])): ?>
                            <div class="vortex-metric-card">
                                <div class="metric-icon">
                                    <i class="dashicons dashicons-businessperson"></i>
                                </div>
                                <div class="metric-content">
                                    <h3><?php _e('Unique Owners', 'vortex-marketplace'); ?></h3>
                                    <div class="metric-value"><?php echo number_format($data['blockchain']['unique_owners']); ?></div>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($data['blockchain']['total_volume'])): ?>
                            <div class="vortex-metric-card">
                                <div class="metric-icon">
                                    <i class="dashicons dashicons-chart-area"></i>
                                </div>
                                <div class="metric-content">
                                    <h3><?php _e('Total Volume', 'vortex-marketplace'); ?></h3>
                                    <div class="metric-value"><?php echo number_format($data['blockchain']['total_volume'], 2); ?> ETH</div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($data['blockchain']['recent_activity'])): ?>
                    <div class="vortex-section">
                        <h3><?php _e('Recent Blockchain Activity', 'vortex-marketplace'); ?></h3>
                        <div class="vortex-blockchain-activity">
                            <table class="vortex-data-table">
                                <thead>
                                    <tr>
                                        <th><?php _e('Time', 'vortex-marketplace'); ?></th>
                                        <th><?php _e('Type', 'vortex-marketplace'); ?></th>
                                        <th><?php _e('Item', 'vortex-marketplace'); ?></th>
                                        <th><?php _e('From', 'vortex-marketplace'); ?></th>
                                        <th><?php _e('To', 'vortex-marketplace'); ?></th>
                                        <th><?php _e('Value', 'vortex-marketplace'); ?></th>
                                        <th><?php _e('Tx Hash', 'vortex-marketplace'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data['blockchain']['recent_activity'] as $activity): ?>
                                    <tr>
                                        <td><?php echo esc_html($activity['time']); ?></td>
                                        <td><?php echo esc_html($activity['type']); ?></td>
                                        <td>
                                            <?php if (!empty($activity['item_url'])): ?>
                                            <a href="<?php echo esc_url($activity['item_url']); ?>">
                                                <?php echo esc_html($activity['item_name']); ?>
                                            </a>
                                            <?php else: ?>
                                                <?php echo esc_html($activity['item_name']); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo esc_html(substr($activity['from'], 0, 8) . '...'); ?></td>
                                        <td><?php echo esc_html(substr($activity['to'], 0, 8) . '...'); ?></td>
                                        <td><?php echo !empty($activity['value']) ? number_format($activity['value'], 4) . ' ETH' : '-'; ?></td>
                                        <td>
                                            <a href="<?php echo esc_url('https://tola.network/tx/' . $activity['tx_hash']); ?>" target="_blank">
                                                <?php echo esc_html(substr($activity['tx_hash'], 0, 8) . '...'); ?>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="vortex-chart-container">
                        <h3><?php _e('TOLA Blockchain Growth', 'vortex-marketplace'); ?></h3>
                        <canvas id="vortex-blockchain-chart"></canvas>
                    </div>
                </div>
            <?php else: ?>
                <div class="vortex-notice">
                    <p><?php _e('Blockchain data is currently unavailable. Please check your connection to TOLA.', 'vortex-marketplace'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div> 