<?php
/**
 * Template for the TOLA Statistics Widget
 * Displays TOLA market statistics and user balance
 */

// Get current user data
$user_id = get_current_user_id();
$tola = VORTEX_Tola::get_instance();

// Get TOLA statistics
$stats = array(
    'total_supply' => $tola->get_total_supply(),
    'market_value' => $tola->get_market_value(),
    'marketplace_volume' => $tola->get_marketplace_volume(),
    'user_balance' => $user_id ? $tola->get_user_balance($user_id) : 0
);
?>

<div class="vortex-widget vortex-tola-stats-widget">
    <div class="vortex-widget-header">
        <i class="vortex-widget-icon fas fa-coins"></i>
        <h2 class="vortex-widget-title"><?php esc_html_e('TOLA Statistics', 'vortex'); ?></h2>
    </div>

    <div class="vortex-tola-stats-content">
        <div class="vortex-tola-stat-grid">
            <!-- Total Supply -->
            <div class="vortex-tola-stat-card">
                <div class="stat-header">
                    <i class="fas fa-chart-pie"></i>
                    <h3><?php esc_html_e('Total Supply', 'vortex'); ?></h3>
                </div>
                <div class="stat-value">
                    <?php echo esc_html(number_format($stats['total_supply'])); ?> TOLA
                </div>
            </div>

            <!-- Market Value -->
            <div class="vortex-tola-stat-card">
                <div class="stat-header">
                    <i class="fas fa-dollar-sign"></i>
                    <h3><?php esc_html_e('Market Value', 'vortex'); ?></h3>
                </div>
                <div class="stat-value">
                    $<?php echo esc_html(number_format($stats['market_value'], 2)); ?>
                </div>
            </div>

            <!-- Marketplace Volume -->
            <div class="vortex-tola-stat-card">
                <div class="stat-header">
                    <i class="fas fa-chart-line"></i>
                    <h3><?php esc_html_e('24h Volume', 'vortex'); ?></h3>
                </div>
                <div class="stat-value">
                    <?php echo esc_html(number_format($stats['marketplace_volume'])); ?> TOLA
                </div>
            </div>

            <!-- User Balance -->
            <?php if ($user_id) : ?>
                <div class="vortex-tola-stat-card">
                    <div class="stat-header">
                        <i class="fas fa-wallet"></i>
                        <h3><?php esc_html_e('Your Balance', 'vortex'); ?></h3>
                    </div>
                    <div class="stat-value">
                        <?php echo esc_html(number_format($stats['user_balance'])); ?> TOLA
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Real-time Updates Notice -->
        <div class="vortex-tola-update-notice">
            <i class="fas fa-sync"></i>
            <span><?php esc_html_e('Statistics update in real-time', 'vortex'); ?></span>
        </div>
    </div>
</div> 