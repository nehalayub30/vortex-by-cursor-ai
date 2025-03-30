<?php
/**
 * Collector Subscription Plans Template
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage Subscriptions
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$subscriptions = VORTEX_Subscriptions::get_instance();
$plans = $subscriptions->collector_plans;
?>

<div class="vortex-subscription-plans vortex-collector-plans">
    <h2><?php esc_html_e('Collector Subscription Plans', 'vortex'); ?></h2>
    
    <div class="vortex-plans-grid">
        <?php foreach ($plans as $tier => $plan) : ?>
            <div class="vortex-plan-card">
                <div class="vortex-plan-header">
                    <h3><?php echo esc_html($plan['name']); ?></h3>
                    <div class="vortex-plan-price">
                        <span class="vortex-currency">TOLA</span>
                        <span class="vortex-amount"><?php echo esc_html($plan['price']); ?></span>
                        <span class="vortex-period">/<?php esc_html_e('month', 'vortex'); ?></span>
                    </div>
                </div>
                
                <div class="vortex-plan-features">
                    <ul>
                        <?php foreach ($plan['features'] as $feature) : ?>
                            <li>
                                <span class="dashicons dashicons-yes"></span>
                                <?php echo esc_html(ucwords(str_replace('_', ' ', $feature))); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="vortex-plan-footer">
                    <button class="vortex-subscribe-button" 
                            data-plan-type="collector"
                            data-plan-tier="<?php echo esc_attr($tier); ?>">
                        <?php esc_html_e('Subscribe Now', 'vortex'); ?>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="vortex-subscription-notice">
        <p><?php esc_html_e('All payments are processed using TOLA tokens. Please ensure you have sufficient TOLA balance in your wallet.', 'vortex'); ?></p>
    </div>
</div> 