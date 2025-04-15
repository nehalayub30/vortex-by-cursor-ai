<?php
/**
 * VORTEX DAO Investor Dashboard Template
 *
 * @package VORTEX
 * @var array $investor_data The investor data
 * @var array $this->config The DAO configuration
 */

// Get additional investor data
$token_amount = $investor_data->token_amount ?? 0;
$investment_amount = $investor_data->investment_amount ?? 0;
$token_price = $investor_data->token_price ?? 0;
$vesting_period_days = $investor_data->vesting_period_days ?? 0;
$purchase_date = $investor_data->purchase_date ?? date('Y-m-d H:i:s');
$kyc_status = $investor_data->kyc_status ?? 'pending';

// Calculate vesting information
$purchase_timestamp = strtotime($purchase_date);
$cliff_months = $this->config['investor_cliff_months'];
$vesting_months = $this->config['investor_vesting_months'];
$cliff_timestamp = strtotime("+{$cliff_months} months", $purchase_timestamp);
$vesting_end_timestamp = strtotime("+{$vesting_months} months", $purchase_timestamp);
$current_timestamp = current_time('timestamp');

// Calculate vested percentage
$vested_percentage = 0;
if ($current_timestamp >= $cliff_timestamp) {
    if ($current_timestamp >= $vesting_end_timestamp) {
        $vested_percentage = 100;
    } else {
        $total_vesting_period = $vesting_end_timestamp - $cliff_timestamp;
        $elapsed_vesting_period = $current_timestamp - $cliff_timestamp;
        $vested_percentage = min(100, ($elapsed_vesting_period / $total_vesting_period) * 100);
    }
}

// Calculate vested tokens
$vested_tokens = floor(($token_amount * $vested_percentage) / 100);
$unvested_tokens = $token_amount - $vested_tokens;

// Get current token price from DAO
$current_token_price = 0.15; // Example price - in real-world this would come from an API or database

// Calculate current value
$initial_value = $investment_amount;
$current_value = $token_amount * $current_token_price;
$roi_percentage = $initial_value > 0 ? (($current_value - $initial_value) / $initial_value) * 100 : 0;

// Format dates for display
$formatted_purchase_date = date_i18n(get_option('date_format'), $purchase_timestamp);
$formatted_cliff_date = date_i18n(get_option('date_format'), $cliff_timestamp);
$formatted_vesting_end_date = date_i18n(get_option('date_format'), $vesting_end_timestamp);
?>

<div class="vortex-investor-dashboard-container marketplace-frontend-wrapper">
    <h2 class="marketplace-frontend-title"><?php echo esc_html($atts['title']); ?></h2>
    
    <div class="marketplace-frontend-content">
        <!-- Investment Summary Card -->
        <div class="vortex-dashboard-card vortex-investment-summary">
            <h3 class="card-title"><?php _e('Investment Summary', 'vortex'); ?></h3>
            <div class="investment-summary-content">
                <div class="summary-item">
                    <span class="summary-label"><?php _e('Investment Amount', 'vortex'); ?></span>
                    <span class="summary-value">$<?php echo number_format($investment_amount, 2); ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label"><?php _e('Token Amount', 'vortex'); ?></span>
                    <span class="summary-value"><?php echo number_format($token_amount); ?> TOLA-Equity</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label"><?php _e('Purchase Date', 'vortex'); ?></span>
                    <span class="summary-value"><?php echo esc_html($formatted_purchase_date); ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label"><?php _e('Current Value', 'vortex'); ?></span>
                    <span class="summary-value">$<?php echo number_format($current_value, 2); ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label"><?php _e('ROI', 'vortex'); ?></span>
                    <span class="summary-value <?php echo $roi_percentage >= 0 ? 'positive' : 'negative'; ?>">
                        <?php echo ($roi_percentage >= 0 ? '+' : '') . number_format($roi_percentage, 2); ?>%
                    </span>
                </div>
                <div class="summary-item">
                    <span class="summary-label"><?php _e('KYC Status', 'vortex'); ?></span>
                    <span class="summary-value status-badge status-<?php echo esc_attr($kyc_status); ?>">
                        <?php echo ucfirst(esc_html($kyc_status)); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Vesting Information Card -->
        <div class="vortex-dashboard-card vortex-vesting-info">
            <h3 class="card-title"><?php _e('Vesting Schedule', 'vortex'); ?></h3>
            <div class="vesting-info-content">
                <div class="vesting-progress">
                    <div class="vesting-progress-bar">
                        <div class="vesting-progress-fill" style="width: <?php echo esc_attr($vested_percentage); ?>%"></div>
                        <?php if ($current_timestamp < $cliff_timestamp): ?>
                            <div class="vesting-cliff-marker" style="left: <?php echo ($cliff_months / $vesting_months) * 100; ?>%"></div>
                        <?php endif; ?>
                    </div>
                    <div class="vesting-progress-labels">
                        <span class="vesting-start-date"><?php echo esc_html($formatted_purchase_date); ?></span>
                        <span class="vesting-cliff-date"><?php echo esc_html($formatted_cliff_date); ?></span>
                        <span class="vesting-end-date"><?php echo esc_html($formatted_vesting_end_date); ?></span>
                    </div>
                </div>
                
                <div class="vesting-tokens">
                    <div class="vesting-tokens-vested">
                        <span class="tokens-label"><?php _e('Vested Tokens', 'vortex'); ?></span>
                        <span class="tokens-value"><?php echo number_format($vested_tokens); ?> TOLA-Equity</span>
                        <span class="tokens-percentage"><?php echo number_format($vested_percentage, 1); ?>%</span>
                    </div>
                    <div class="vesting-tokens-unvested">
                        <span class="tokens-label"><?php _e('Unvested Tokens', 'vortex'); ?></span>
                        <span class="tokens-value"><?php echo number_format($unvested_tokens); ?> TOLA-Equity</span>
                        <span class="tokens-percentage"><?php echo number_format(100 - $vested_percentage, 1); ?>%</span>
                    </div>
                </div>
                
                <div class="vesting-dates">
                    <div class="vesting-date-item">
                        <span class="date-label"><?php _e('Cliff Period', 'vortex'); ?></span>
                        <span class="date-value"><?php echo esc_html($cliff_months); ?> <?php _e('months', 'vortex'); ?></span>
                    </div>
                    <div class="vesting-date-item">
                        <span class="date-label"><?php _e('Vesting Period', 'vortex'); ?></span>
                        <span class="date-value"><?php echo esc_html($vesting_months); ?> <?php _e('months', 'vortex'); ?></span>
                    </div>
                    <div class="vesting-date-item">
                        <span class="date-label"><?php _e('Cliff Date', 'vortex'); ?></span>
                        <span class="date-value"><?php echo esc_html($formatted_cliff_date); ?></span>
                    </div>
                    <div class="vesting-date-item">
                        <span class="date-label"><?php _e('Vesting End Date', 'vortex'); ?></span>
                        <span class="date-value"><?php echo esc_html($formatted_vesting_end_date); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Token Transactions Card -->
        <div class="vortex-dashboard-card vortex-token-transactions">
            <h3 class="card-title"><?php _e('Token Transactions', 'vortex'); ?></h3>
            <div class="token-transactions-content">
                <?php
                // This would normally come from a database query
                $transactions = array(
                    array(
                        'date' => date_i18n(get_option('date_format'), $purchase_timestamp),
                        'type' => 'purchase',
                        'amount' => $token_amount,
                        'status' => 'completed',
                    ),
                    // Additional placeholder transactions for UI demonstration
                    array(
                        'date' => date_i18n(get_option('date_format'), strtotime('-1 month')),
                        'type' => 'dividend',
                        'amount' => floor($token_amount * 0.01),
                        'status' => 'completed',
                    ),
                );
                
                if (!empty($transactions)): ?>
                    <table class="transactions-table">
                        <thead>
                            <tr>
                                <th><?php _e('Date', 'vortex'); ?></th>
                                <th><?php _e('Type', 'vortex'); ?></th>
                                <th><?php _e('Amount', 'vortex'); ?></th>
                                <th><?php _e('Status', 'vortex'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td><?php echo esc_html($transaction['date']); ?></td>
                                    <td>
                                        <span class="transaction-type transaction-<?php echo esc_attr($transaction['type']); ?>">
                                            <?php echo ucfirst(esc_html($transaction['type'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format($transaction['amount']); ?> TOLA-Equity</td>
                                    <td>
                                        <span class="transaction-status status-<?php echo esc_attr($transaction['status']); ?>">
                                            <?php echo ucfirst(esc_html($transaction['status'])); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-transactions-message">
                        <?php _e('No transactions found.', 'vortex'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Governance Card -->
        <div class="vortex-dashboard-card vortex-governance-info">
            <h3 class="card-title"><?php _e('Governance Power', 'vortex'); ?></h3>
            <div class="governance-info-content">
                <div class="governance-power-info">
                    <div class="governance-power-item">
                        <span class="power-label"><?php _e('Voting Power', 'vortex'); ?></span>
                        <span class="power-value"><?php echo number_format($vested_tokens * $this->config['investor_vote_multiplier']); ?></span>
                        <span class="power-multiplier"><?php echo sprintf(__('(%sx multiplier)', 'vortex'), $this->config['investor_vote_multiplier']); ?></span>
                    </div>
                    <div class="governance-power-item">
                        <span class="power-label"><?php _e('Voting Rights', 'vortex'); ?></span>
                        <span class="power-value">
                            <?php 
                            if ($vested_tokens > 0) {
                                _e('Active', 'vortex');
                            } else {
                                _e('Inactive until cliff period', 'vortex');
                            }
                            ?>
                        </span>
                    </div>
                </div>
                
                <div class="governance-actions">
                    <a href="#" class="vortex-btn"><?php _e('View Active Proposals', 'vortex'); ?></a>
                    <a href="#" class="vortex-btn"><?php _e('Governance Forum', 'vortex'); ?></a>
                </div>
            </div>
        </div>
        
        <!-- Document Links -->
        <div class="vortex-dashboard-card vortex-document-links">
            <h3 class="card-title"><?php _e('Documents', 'vortex'); ?></h3>
            <div class="document-links-content">
                <a href="#" class="document-link">
                    <span class="document-icon pdf-icon"></span>
                    <span class="document-name"><?php _e('Investment Agreement', 'vortex'); ?></span>
                </a>
                <a href="#" class="document-link">
                    <span class="document-icon pdf-icon"></span>
                    <span class="document-name"><?php _e('Tokenomics Whitepaper', 'vortex'); ?></span>
                </a>
                <a href="#" class="document-link">
                    <span class="document-icon pdf-icon"></span>
                    <span class="document-name"><?php _e('Investor Handbook', 'vortex'); ?></span>
                </a>
            </div>
        </div>
    </div>
</div> 