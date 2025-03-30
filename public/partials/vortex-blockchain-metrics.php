<?php
/**
 * Blockchain Metrics Dashboard Template
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="vortex-blockchain-metrics-dashboard">
    <div class="dashboard-header">
        <h2><?php _e('TOLA Blockchain Metrics', 'vortex-ai-marketplace'); ?></h2>
        <p class="dashboard-description"><?php _e('Real-time analytics of artwork on the TOLA blockchain network', 'vortex-ai-marketplace'); ?></p>
        
        <div class="refresh-controls">
            <span class="last-updated"><?php _e('Last updated:', 'vortex-ai-marketplace'); ?> <span id="last-updated-time"><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format')); ?></span></span>
            <button id="refresh-metrics" class="refresh-button"><span class="dashicons dashicons-update"></span> <?php _e('Refresh Data', 'vortex-ai-marketplace'); ?></button>
        </div>
    </div>
    
    <div class="metrics-loading">
        <div class="spinner"></div>
        <p><?php _e('Loading blockchain data...', 'vortex-ai-marketplace'); ?></p>
    </div>
    
    <div class="metrics-container" style="display: none;">
        <!-- Summary Cards -->
        <div class="metrics-summary">
            <div class="metric-card">
                <div class="metric-icon artwork-icon"></div>
                <div class="metric-data">
                    <h3><?php _e('Verified Artworks', 'vortex-ai-marketplace'); ?></h3>
                    <div class="metric-value" id="total-artworks">0</div>
                </div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon artist-icon"></div>
                <div class="metric-data">
                    <h3><?php _e('Verified Artists', 'vortex-ai-marketplace'); ?></h3>
                    <div class="metric-value" id="total-artists">0</div>
                </div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon swap-icon"></div>
                <div class="metric-data">
                    <h3><?php _e('Completed Swaps', 'vortex-ai-marketplace'); ?></h3>
                    <div class="metric-value" id="total-swaps">0</div>
                </div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon tola-icon"></div>
                <div class="metric-data">
                    <h3><?php _e('TOLA Tokens', 'vortex-ai-marketplace'); ?></h3>
                    <div class="metric-value" id="total-tola">0</div>
                </div>
            </div>
        </div>
        
        <!-- Charts Section -->
        <div class="metrics-charts">
            <div class="chart-container">
                <h3><?php _e('Monthly Activity', 'vortex-ai-marketplace'); ?></h3>
                <canvas id="monthly-activity-chart"></canvas>
            </div>
            
            <div class="chart-container">
                <h3><?php _e('Top Artwork Categories', 'vortex-ai-marketplace'); ?></h3>
                <canvas id="categories-chart"></canvas>
            </div>
        </div>
        
        <!-- Top Artists Section -->
        <div class="top-artists-section">
            <h3><?php _e('Most Swapped Artists', 'vortex-ai-marketplace'); ?></h3>
            <div class="top-artists-container" id="top-artists-list">
                <!-- Will be populated dynamically -->
                <div class="empty-state"><?php _e('Loading top artists data...', 'vortex-ai-marketplace'); ?></div>
            </div>
        </div>
        
        <!-- Recent Transactions -->
        <div class="recent-transactions-section">
            <h3><?php _e('Recent Blockchain Transactions', 'vortex-ai-marketplace'); ?></h3>
            <div class="transactions-container">
                <table class="transactions-table">
                    <thead>
                        <tr>
                            <th><?php _e('Transaction Hash', 'vortex-ai-marketplace'); ?></th>
                            <th><?php _e('Date', 'vortex-ai-marketplace'); ?></th>
                            <th><?php _e('Type', 'vortex-ai-marketplace'); ?></th>
                            <th><?php _e('Action', 'vortex-ai-marketplace'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="recent-transactions">
                        <!-- Will be populated dynamically -->
                        <tr>
                            <td colspan="4" class="empty-state"><?php _e('Loading transaction data...', 'vortex-ai-marketplace'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div> 