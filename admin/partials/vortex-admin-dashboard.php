<?php
/**
 * Main dashboard admin page template.
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin/partials
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

// Get marketplace stats
$total_artworks = get_posts( array(
    'post_type' => 'vortex_artwork',
    'post_status' => 'publish',
    'numberposts' => -1,
    'fields' => 'ids',
) );
$total_artwork_count = count( $total_artworks );

$total_artists = get_posts( array(
    'post_type' => 'vortex_artist',
    'post_status' => 'publish',
    'numberposts' => -1,
    'fields' => 'ids',
) );
$total_artist_count = count( $total_artists );

$recent_sales = get_posts( array(
    'post_type' => 'vortex_transaction',
    'post_status' => 'publish',
    'numberposts' => 5,
    'meta_query' => array(
        array(
            'key' => '_vortex_transaction_type',
            'value' => 'artwork_purchase',
        ),
    ),
) );

// Get settings
$marketplace_title = get_option( 'vortex_marketplace_title', 'VORTEX AI Marketplace' );
$currency_symbol = get_option( 'vortex_marketplace_currency_symbol', '$' );

// Calculate total sales and revenue
$total_sales = 0;
$total_revenue = 0;
$transactions = get_posts( array(
    'post_type' => 'vortex_transaction',
    'post_status' => 'publish',
    'numberposts' => -1,
    'meta_query' => array(
        array(
            'key' => '_vortex_transaction_type',
            'value' => 'artwork_purchase',
        ),
    ),
) );

foreach ( $transactions as $transaction ) {
    $total_sales++;
    $amount = get_post_meta( $transaction->ID, '_vortex_transaction_amount', true );
    if ( $amount ) {
        $total_revenue += floatval( $amount );
    }
}

?>
<div class="wrap vortex-admin-wrap">
    <div class="vortex-admin-header">
        <div class="vortex-admin-logo">
            <img src="<?php echo plugin_dir_url( dirname( __FILE__ ) ) . 'images/vortex-logo.png'; ?>" alt="VORTEX AI Marketplace">
        </div>
        <div class="vortex-admin-header-content">
            <h1><?php echo esc_html( $marketplace_title ); ?> Dashboard</h1>
            <p><?php esc_html_e( 'Welcome to your AI-powered digital art marketplace', 'vortex-ai-marketplace' ); ?></p>
        </div>
    </div>

    <div class="vortex-welcome-section">
        <h2 class="vortex-welcome-title"><?php esc_html_e( 'Marketplace Overview', 'vortex-ai-marketplace' ); ?></h2>
        <p class="vortex-welcome-subtitle"><?php esc_html_e( 'Monitor your marketplace performance and manage your digital assets', 'vortex-ai-marketplace' ); ?></p>
        
        <div class="vortex-status-summary">
            <div class="vortex-status-item">
                <span class="vortex-status-label"><?php esc_html_e( 'Total Artworks', 'vortex-ai-marketplace' ); ?></span>
                <span class="vortex-status-value">
                    <i class="dashicons dashicons-format-image"></i>
                    <?php echo esc_html( number_format( $total_artwork_count ) ); ?>
                </span>
            </div>
            
            <div class="vortex-status-item">
                <span class="vortex-status-label"><?php esc_html_e( 'Total Artists', 'vortex-ai-marketplace' ); ?></span>
                <span class="vortex-status-value">
                    <i class="dashicons dashicons-admin-users"></i>
                    <?php echo esc_html( number_format( $total_artist_count ) ); ?>
                </span>
            </div>
            
            <div class="vortex-status-item">
                <span class="vortex-status-label"><?php esc_html_e( 'Total Sales', 'vortex-ai-marketplace' ); ?></span>
                <span class="vortex-status-value">
                    <i class="dashicons dashicons-cart"></i>
                    <?php echo esc_html( number_format( $total_sales ) ); ?>
                </span>
            </div>
            
            <div class="vortex-status-item">
                <span class="vortex-status-label"><?php esc_html_e( 'Total Revenue', 'vortex-ai-marketplace' ); ?></span>
                <span class="vortex-status-value">
                    <i class="dashicons dashicons-money-alt"></i>
                    <?php echo esc_html( $currency_symbol . number_format( $total_revenue, 2 ) ); ?>
                </span>
            </div>
        </div>
        
        <div class="vortex-quick-links">
            <a href="<?php echo admin_url( 'admin.php?page=vortex_marketplace_settings' ); ?>" class="vortex-quick-link">
                <i class="vortex-quick-link-icon dashicons dashicons-admin-settings"></i>
                <span class="vortex-quick-link-label"><?php esc_html_e( 'Settings', 'vortex-ai-marketplace' ); ?></span>
            </a>
            
            <a href="<?php echo admin_url( 'edit.php?post_type=vortex_artwork' ); ?>" class="vortex-quick-link">
                <i class="vortex-quick-link-icon dashicons dashicons-format-image"></i>
                <span class="vortex-quick-link-label"><?php esc_html_e( 'Artworks', 'vortex-ai-marketplace' ); ?></span>
            </a>
            
            <a href="<?php echo admin_url( 'edit.php?post_type=vortex_artist' ); ?>" class="vortex-quick-link">
                <i class="vortex-quick-link-icon dashicons dashicons-admin-users"></i>
                <span class="vortex-quick-link-label"><?php esc_html_e( 'Artists', 'vortex-ai-marketplace' ); ?></span>
            </a>
            
            <a href="<?php echo admin_url( 'admin.php?page=vortex_analytics' ); ?>" class="vortex-quick-link">
                <i class="vortex-quick-link-icon dashicons dashicons-chart-bar"></i>
                <span class="vortex-quick-link-label"><?php esc_html_e( 'Analytics', 'vortex-ai-marketplace' ); ?></span>
            </a>
            
            <a href="<?php echo admin_url( 'admin.php?page=vortex_marketplace_tools' ); ?>" class="vortex-quick-link">
                <i class="vortex-quick-link-icon dashicons dashicons-admin-tools"></i>
                <span class="vortex-quick-link-label"><?php esc_html_e( 'Tools', 'vortex-ai-marketplace' ); ?></span>
            </a>
        </div>
    </div>

    <div class="vortex-dashboard vortex-dashboard-main">
        <!-- Sales Summary Widget -->
        <div class="vortex-dashboard-widget size-2 vortex-load-summary" data-widget-id="sales_summary">
            <div class="vortex-widget-header">
                <h3 class="vortex-widget-title">
                    <i class="vortex-widget-icon dashicons dashicons-chart-line"></i>
                    <?php esc_html_e( 'Sales Summary', 'vortex-ai-marketplace' ); ?>
                </h3>
                <div class="vortex-widget-actions">
                    <select class="vortex-date-range-selector">
                        <option value="7days"><?php esc_html_e( 'Last 7 Days', 'vortex-ai-marketplace' ); ?></option>
                        <option value="30days" selected><?php esc_html_e( 'Last 30 Days', 'vortex-ai-marketplace' ); ?></option>
                        <option value="90days"><?php esc_html_e( 'Last 90 Days', 'vortex-ai-marketplace' ); ?></option>
                        <option value="year"><?php esc_html_e( 'This Year', 'vortex-ai-marketplace' ); ?></option>
                        <option value="custom"><?php esc_html_e( 'Custom Range', 'vortex-ai-marketplace' ); ?></option>
                    </select>
                    <button class="button-link vortex-refresh-widget">
                        <i class="dashicons dashicons-update"></i>
                    </button>
                </div>
            </div>
            <div class="vortex-widget-content">
                <div class="vortex-widget-loading">
                    <div class="vortex-loading-spinner"></div>
                    <p><?php esc_html_e( 'Loading sales data...', 'vortex-ai-marketplace' ); ?></p>
                </div>
            </div>
            <div class="vortex-widget-footer">
                <div class="vortex-widget-footer-actions">
                    <span class="vortex-widget-updated"><?php esc_html_e( 'Last updated:', 'vortex-ai-marketplace' ); ?> <?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ); ?></span>
                    <a href="<?php echo admin_url( 'admin.php?page=vortex_analytics' ); ?>" class="button-link"><?php esc_html_e( 'View Full Analytics', 'vortex-ai-marketplace' ); ?></a>
                </div>
            </div>
        </div>

        <!-- Artist Growth Widget -->
        <div class="vortex-dashboard-widget size-2 vortex-load-summary" data-widget-id="artist_growth">
            <div class="vortex-widget-header">
                <h3 class="vortex-widget-title">
                    <i class="vortex-widget-icon dashicons dashicons-groups"></i>
                    <?php esc_html_e( 'Artist Growth', 'vortex-ai-marketplace' ); ?>
                </h3>
                <div class="vortex-widget-actions">
                    <button class="button-link vortex-refresh-widget">
                        <i class="dashicons dashicons-update"></i>
                    </button>
                </div>
            </div>
            <div class="vortex-widget-content">
                <div class="vortex-widget-loading">
                    <div class="vortex-loading-spinner"></div>
                    <p><?php esc_html_e( 'Loading artist data...', 'vortex-ai-marketplace' ); ?></p>
                </div>
            </div>
        </div>

        <!-- Recent Activity Widget -->
        <div class="vortex-dashboard-widget size-1">
            <div class="vortex-widget-header">
                <h3 class="vortex-widget-title">
                    <i class="vortex-widget-icon dashicons dashicons-clock"></i>
                    <?php esc_html_e( 'Recent Activity', 'vortex-ai-marketplace' ); ?>
                </h3>
            </div>
            <div class="vortex-widget-content">
                <?php if ( ! empty( $recent_sales ) ) : ?>
                    <ul class="vortex-activity-feed">
                        <?php foreach ( $recent_sales as $sale ) : 
                            $buyer_id = get_post_meta( $sale->ID, '_vortex_transaction_buyer_id', true );
                            $artwork_id = get_post_meta( $sale->ID, '_vortex_transaction_artwork_id', true );
                            $amount = get_post_meta( $sale->ID, '_vortex_transaction_amount', true );
                            
                            $buyer = get_user_by( 'id', $buyer_id );
                            $buyer_name = $buyer ? $buyer->display_name : __( 'Unknown User', 'vortex-ai-marketplace' );
                            
                            $artwork_title = get_the_title( $artwork_id );
                        ?>
                            <li class="vortex-activity-item">
                                <div class="vortex-activity-icon">
                                    <i class="dashicons dashicons-cart"></i>
                                </div>
                                <div class="vortex-activity-content">
                                    <p class="vortex-activity-text">
                                        <?php 
                                        printf(
                                            esc_html__( '%1$s purchased %2$s for %3$s', 'vortex-ai-marketplace' ),
                                            '<strong>' . esc_html( $buyer_name ) . '</strong>',
                                            '<a href="' . esc_url( get_edit_post_link( $artwork_id ) ) . '">' . esc_html( $artwork_title ) . '</a>',
                                            '<strong>' . esc_html( $currency_symbol . number_format( floatval( $amount ), 2 ) ) . '</strong>'
                                        );
                                        ?>
                                    </p>
                                    <div class="vortex-activity-meta">
                                        <?php echo esc_html( human_time_diff( get_post_time( 'U', false, $sale ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'vortex-ai-marketplace' ) ); ?>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <div class="vortex-empty-state">
                        <div class="vortex-empty-state-icon dashicons dashicons-cart"></div>
                        <h3 class="vortex-empty-state-title"><?php esc_html_e( 'No Recent Sales', 'vortex-ai-marketplace' ); ?></h3>
                        <p class="vortex-empty-state-description"><?php esc_html_e( 'Sales activity will appear here once your marketplace starts generating transactions.', 'vortex-ai-marketplace' ); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="vortex-widget-footer">
                <div class="vortex-widget-footer-actions">
                    <a href="<?php echo admin_url( 'edit.php?post_type=vortex_transaction' ); ?>" class="button-link"><?php esc_html_e( 'View All Transactions', 'vortex-ai-marketplace' ); ?></a>
                </div>
            </div>
        </div>

        <!-- Top Selling Artworks Widget -->
        <div class="vortex-dashboard-widget size-2 vortex-load-summary" data-widget-id="top_selling_artworks">
            <div class="vortex-widget-header">
                <h3 class="vortex-widget-title">
                    <i class="vortex-widget-icon dashicons dashicons-format-image"></i>
                    <?php esc_html_e( 'Top Selling Artworks', 'vortex-ai-marketplace' ); ?>
                </h3>
                <div class="vortex-widget-actions">
                    <select class="vortex-date-range-selector">
                        <option value="30days" selected><?php esc_html_e( 'Last 30 Days', 'vortex-ai-marketplace' ); ?></option>
                        <option value="90days"><?php esc_html_e( 'Last 90 Days', 'vortex-ai-marketplace' ); ?></option>
                        <option value="year"><?php esc_html_e( 'This Year', 'vortex-ai-marketplace' ); ?></option>
                        <option value="all"><?php esc_html_e( 'All Time', 'vortex-ai-marketplace' ); ?></option>
                    </select>
                    <button class="button-link vortex-refresh-widget">
                        <i class="dashicons dashicons-update"></i>
                    </button>
                </div>
            </div>
            <div class="vortex-widget-content">
                <div class="vortex-widget-loading">
                    <div class="vortex-loading-spinner"></div>
                    <p><?php esc_html_e( 'Loading artwork data...', 'vortex-ai-marketplace' ); ?></p>
                </div>
            </div>
        </div>

        <!-- AI Generation Statistics Widget -->
        <div class="vortex-dashboard-widget size-1 vortex-load-summary" data-widget-id="ai_statistics">
            <div class="vortex-widget-header">
                <h3 class="vortex-widget-title">
                    <i class="vortex-widget-icon dashicons dashicons-welcome-learn-more"></i>
                    <?php esc_html_e( 'AI Generation', 'vortex-ai-marketplace' ); ?>
                </h3>
                <div class="vortex-widget-actions">
                    <button class="button-link vortex-refresh-widget">
                        <i class="dashicons dashicons-update"></i>
                    </button>
                </div>
            </div>
            <div class="vortex-widget-content">
                <div class="vortex-widget-loading">
                    <div class="vortex-loading-spinner"></div>
                    <p><?php esc_html_e( 'Loading AI data...', 'vortex-ai-marketplace' ); ?></p>
                </div>
            </div>
        </div>

        <!-- Marketplace Health Widget -->
        <div class="vortex-dashboard-widget size-2 vortex-load-summary" data-widget-id="marketplace_health">
            <div class="vortex-widget-header">
                <h3 class="vortex-widget-title">
                    <i class="vortex-widget-icon dashicons dashicons-chart-area"></i>
                    <?php esc_html_e( 'Marketplace Health', 'vortex-ai-marketplace' ); ?>
                </h3>
                <div class="vortex-widget-actions">
                    <button class="button-link vortex-refresh-widget">
                        <i class="dashicons dashicons-update"></i>
                    </button>
                </div>
            </div>
            <div class="vortex-widget-content">
                <div class="vortex-widget-loading">
                    <div class="vortex-loading-spinner"></div>
                    <p><?php esc_html_e( 'Loading marketplace metrics...', 'vortex-ai-marketplace' ); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="vortex-marketplace-insights">
        <!-- Loaded via AJAX -->
        <div class="vortex-widget-loading">
            <div class="vortex-loading-spinner"></div>
            <p><?php esc_html_e( 'Loading marketplace insights...', 'vortex-ai-marketplace' ); ?></p>
        </div>
    </div>
</div> 