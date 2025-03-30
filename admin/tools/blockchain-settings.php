<?php
/**
 * Blockchain Tools
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin/tools
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Check user capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'vortex-ai-marketplace'));
}

// Process blockchain actions
$action_performed = false;
$action_messages = array();
$action_errors = array();

// Get blockchain settings
$blockchain_settings = get_option('vortex_blockchain_settings', array());
$ai_settings = get_option('vortex_ai_settings', array());

// Handle action form submission
if (isset($_POST['vortex_blockchain_action']) && check_admin_referer('vortex_blockchain_tools_nonce')) {
    $action = sanitize_text_field($_POST['vortex_blockchain_action']);
    $action_performed = true;
    
    switch ($action) {
        case 'sync_contracts':
            // Sync smart contracts
            $sync_result = vortex_sync_blockchain_contracts();
            
            if ($sync_result) {
                $action_messages[] = __('Smart contracts synchronized successfully!', 'vortex-ai-marketplace');
            } else {
                $action_errors[] = __('Failed to synchronize smart contracts. Please check your network settings.', 'vortex-ai-marketplace');
            }
            break;
            
        case 'mint_batch_nfts':
            // Validate input
            $artist_id = isset($_POST['artist_id']) ? intval($_POST['artist_id']) : 0;
            $artwork_ids = isset($_POST['artwork_ids']) ? sanitize_text_field($_POST['artwork_ids']) : '';
            
            if (empty($artist_id) || empty($artwork_ids)) {
                $action_errors[] = __('Please provide both artist ID and artwork IDs.', 'vortex-ai-marketplace');
                break;
            }
            
            // Get artwork IDs as array
            $artwork_ids_array = array_map('trim', explode(',', $artwork_ids));
            
            // Mint NFTs for artworks
            $mint_result = vortex_mint_batch_nfts($artist_id, $artwork_ids_array);
            
            if ($mint_result['success']) {
                $action_messages[] = sprintf(
                    __('Successfully minted %d NFTs! Transaction hash: %s', 'vortex-ai-marketplace'),
                    $mint_result['count'],
                    $mint_result['tx_hash']
                );
            } else {
                $action_errors[] = sprintf(
                    __('Failed to mint NFTs: %s', 'vortex-ai-marketplace'),
                    $mint_result['error']
                );
            }
            break;
            
        case 'analyze_blockchain':
            // Ensure CLOE is enabled for analysis
            if (empty($ai_settings['cloe_enabled'])) {
                $action_errors[] = __('CLOE market analysis AI must be enabled to perform blockchain analysis.', 'vortex-ai-marketplace');
                break;
            }
            
            // Run blockchain analysis
            $analysis_result = vortex_cloe_analyze_blockchain();
            
            if ($analysis_result) {
                $action_messages[] = sprintf(
                    __('Blockchain analysis completed successfully! %s', 'vortex-ai-marketplace'),
                    $analysis_result
                );
            } else {
                $action_errors[] = __('Failed to complete blockchain analysis. Please try again later.', 'vortex-ai-marketplace');
            }
            break;
            
        case 'optimize_gas':
            // AI-assisted gas optimization
            $gas_settings = array(
                'strategy' => isset($_POST['gas_strategy']) ? sanitize_text_field($_POST['gas_strategy']) : 'balanced',
                'max_fee' => isset($_POST['max_gas_fee']) ? floatval($_POST['max_gas_fee']) : 0
            );
            
            $optimize_result = vortex_optimize_gas_settings($gas_settings);
            
            if ($optimize_result) {
                $action_messages[] = __('Gas optimization settings updated successfully!', 'vortex-ai-marketplace');
            } else {
                $action_errors[] = __('Failed to update gas optimization settings.', 'vortex-ai-marketplace');
            }
            break;
            
        case 'distribute_tokens':
            // Validate input
            $recipient_address = isset($_POST['recipient_address']) ? sanitize_text_field($_POST['recipient_address']) : '';
            $token_amount = isset($_POST['token_amount']) ? floatval($_POST['token_amount']) : 0;
            
            if (empty($recipient_address) || empty($token_amount)) {
                $action_errors[] = __('Please provide both recipient address and token amount.', 'vortex-ai-marketplace');
                break;
            }
            
            // Transfer TOLA tokens
            $transfer_result = vortex_transfer_tola_tokens($recipient_address, $token_amount);
            
            if ($transfer_result['success']) {
                $action_messages[] = sprintf(
                    __('Successfully transferred %s TOLA tokens to %s! Transaction hash: %s', 'vortex-ai-marketplace'),
                    $token_amount,
                    $recipient_address,
                    $transfer_result['tx_hash']
                );
            } else {
                $action_errors[] = sprintf(
                    __('Failed to transfer tokens: %s', 'vortex-ai-marketplace'),
                    $transfer_result['error']
                );
            }
            break;
    }
}

// Get blockchain stats
$tola_balance = vortex_get_tola_balance();
$nft_count = vortex_get_nft_count();
$artist_count = vortex_get_blockchain_artist_count();
$gas_price = vortex_get_current_gas_price();
$last_sync = get_option('vortex_last_contract_sync', __('Never', 'vortex-ai-marketplace'));
$last_analysis = get_option('vortex_last_blockchain_analysis', __('Never', 'vortex-ai-marketplace'));

// Get blockchain insights if available
$blockchain_insights = get_option('vortex_blockchain_insights', array());

// Get artist list for NFT minting
$artists = vortex_get_artists_list();

/**
 * Mock function for CLOE blockchain analysis 
 * In a real implementation, this would connect to the CLOE API
 */
function vortex_cloe_analyze_blockchain() {
    // Store last analysis time
    update_option('vortex_last_blockchain_analysis', current_time('mysql'));
    
    // Store mock insights
    $insights = array(
        __('NFT market shows growing interest in AI-generated art, with a 35% increase in sales volume over the last 30 days.', 'vortex-ai-marketplace'),
        __('Gas fees are currently trending 15% lower than monthly average. Consider batching NFT minting operations.', 'vortex-ai-marketplace'),
        __('TOLA token liquidity has increased by 22%, indicating growing marketplace adoption.', 'vortex-ai-marketplace')
    );
    
    update_option('vortex_blockchain_insights', $insights);
    
    return __('Detected 3 actionable market trends to optimize your blockchain operations.', 'vortex-ai-marketplace');
}

// Other necessary functions would be implemented in the blockchain core files
?>

<div class="wrap">
    <h1><?php echo esc_html__('Blockchain Tools', 'vortex-ai-marketplace'); ?></h1>
    
    <?php if (!empty($action_messages)): ?>
        <div class="notice notice-success">
            <?php foreach ($action_messages as $message): ?>
                <p><?php echo wp_kses_post($message); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($action_errors)): ?>
        <div class="notice notice-error">
            <?php foreach ($action_errors as $error): ?>
                <p><?php echo esc_html($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <!-- Blockchain Dashboard -->
    <div class="vortex-blockchain-dashboard">
        <div class="vortex-blockchain-stats">
            <div class="stat-card">
                <span class="stat-title"><?php echo esc_html__('TOLA Balance', 'vortex-ai-marketplace'); ?></span>
                <span class="stat-value"><?php echo esc_html(number_format($tola_balance, 2)); ?></span>
            </div>
            
            <div class="stat-card">
                <span class="stat-title"><?php echo esc_html__('Total NFTs', 'vortex-ai-marketplace'); ?></span>
                <span class="stat-value"><?php echo esc_html(number_format($nft_count)); ?></span>
            </div>
            
            <div class="stat-card">
                <span class="stat-title"><?php echo esc_html__('Artists', 'vortex-ai-marketplace'); ?></span>
                <span class="stat-value"><?php echo esc_html(number_format($artist_count)); ?></span>
            </div>
            
            <div class="stat-card">
                <span class="stat-title"><?php echo esc_html__('Gas Price (Gwei)', 'vortex-ai-marketplace'); ?></span>
                <span class="stat-value"><?php echo esc_html(number_format($gas_price, 1)); ?></span>
            </div>
        </div>
        
        <?php if (!empty($blockchain_insights)): ?>
        <!-- Blockchain Insights -->
        <div class="vortex-blockchain-insights">
            <h2><?php echo esc_html__('Blockchain Insights', 'vortex-ai-marketplace'); ?></h2>
            <p><?php echo esc_html__('CLOE market analysis has detected the following blockchain trends:', 'vortex-ai-marketplace'); ?></p>
            
            <ul class="blockchain-insights-list">
                <?php foreach ($blockchain_insights as $insight): ?>
                    <li class="insight-item">
                        <div class="insight-icon"><span class="dashicons dashicons-chart-bar"></span></div>
                        <div class="insight-content"><?php echo esc_html($insight); ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="vortex-blockchain-actions">
        <div class="action-columns">
            <!-- Smart Contract Management -->
            <div class="action-column">
                <div class="vortex-action-card">
                    <h3><?php echo esc_html__('Smart Contract Management', 'vortex-ai-marketplace'); ?></h3>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('vortex_blockchain_tools_nonce'); ?>
                        <input type="hidden" name="vortex_blockchain_action" value="sync_contracts">
                        
                        <div class="form-field">
                            <p><?php echo esc_html__('Synchronize your smart contracts with the blockchain to ensure you have the latest state.', 'vortex-ai-marketplace'); ?></p>
                            <p class="status-info">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <?php echo esc_html__('Last synchronized:', 'vortex-ai-marketplace'); ?> 
                                <strong><?php echo esc_html($last_sync); ?></strong>
                            </p>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="button button-primary"><?php echo esc_html__('Sync Smart Contracts', 'vortex-ai-marketplace'); ?></button>
                        </div>
                    </form>
                </div>
                
                <!-- Gas Optimization -->
                <div class="vortex-action-card">
                    <h3><?php echo esc_html__('Gas Optimization', 'vortex-ai-marketplace'); ?></h3>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('vortex_blockchain_tools_nonce'); ?>
                        <input type="hidden" name="vortex_blockchain_action" value="optimize_gas">
                        
                        <div class="form-field">
                            <label for="gas_strategy"><?php echo esc_html__('Gas Strategy', 'vortex-ai-marketplace'); ?></label>
                            <select name="gas_strategy" id="gas_strategy">
                                <option value="economic"><?php echo esc_html__('Economic (Slower)', 'vortex-ai-marketplace'); ?></option>
                                <option value="balanced" selected><?php echo esc_html__('Balanced', 'vortex-ai-marketplace'); ?></option>
                                <option value="fast"><?php echo esc_html__('Fast (Expensive)', 'vortex-ai-marketplace'); ?></option>
                                <option value="ai_optimized"><?php echo esc_html__('AI-Optimized', 'vortex-ai-marketplace'); ?></option>
                            </select>
                        </div>
                        
                        <div class="form-field">
                            <label for="max_gas_fee"><?php echo esc_html__('Maximum Gas Fee (Gwei)', 'vortex-ai-marketplace'); ?></label>
                            <input type="number" name="max_gas_fee" id="max_gas_fee" value="50" min="5" step="1">
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="button button-primary"><?php echo esc_html__('Update Gas Settings', 'vortex-ai-marketplace'); ?></button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- NFT Minting -->
            <div class="action-column">
                <div class="vortex-action-card">
                    <h3><?php echo esc_html__('NFT Minting', 'vortex-ai-marketplace'); ?></h3>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('vortex_blockchain_tools_nonce'); ?>
                        <input type="hidden" name="vortex_blockchain_action" value="mint_batch_nfts">
                        
                        <div class="form-field">
                            <label for="artist_id"><?php echo esc_html__('Artist', 'vortex-ai-marketplace'); ?></label>
                            <select name="artist_id" id="artist_id">
                                <option value=""><?php echo esc_html__('-- Select Artist --', 'vortex-ai-marketplace'); ?></option>
                                <?php foreach ($artists as $id => $name): ?>
                                    <option value="<?php echo esc_attr($id); ?>"><?php echo esc_html($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-field">
                            <label for="artwork_ids"><?php echo esc_html__('Artwork IDs (comma-separated)', 'vortex-ai-marketplace'); ?></label>
                            <input type="text" name="artwork_ids" id="artwork_ids" placeholder="e.g. 145, 146, 147">
                            <p class="description"><?php echo esc_html__('Enter the IDs of artworks you want to mint as NFTs.', 'vortex-ai-marketplace'); ?></p>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="button button-primary"><?php echo esc_html__('Mint NFTs', 'vortex-ai-marketplace'); ?></button>
                        </div>
                    </form>
                </div>
                
                <!-- TOLA Token Distribution -->
                <div class="vortex-action-card">
                    <h3><?php echo esc_html__('TOLA Token Distribution', 'vortex-ai-marketplace'); ?></h3>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('vortex_blockchain_tools_nonce'); ?>
                        <input type="hidden" name="vortex_blockchain_action" value="distribute_tokens">
                        
                        <div class="form-field">
                            <label for="recipient_address"><?php echo esc_html__('Recipient Address', 'vortex-ai-marketplace'); ?></label>
                            <input type="text" name="recipient_address" id="recipient_address" placeholder="0x...">
                        </div>
                        
                        <div class="form-field">
                            <label for="token_amount"><?php echo esc_html__('TOLA Amount', 'vortex-ai-marketplace'); ?></label>
                            <input type="number" name="token_amount" id="token_amount" min="0.1" step="0.1">
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="button button-primary"><?php echo esc_html__('Send Tokens', 'vortex-ai-marketplace'); ?></button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Blockchain Analysis -->
            <div class="action-column">
                <div class="vortex-action-card cloe-analysis">
                    <h3><?php echo esc_html__('Blockchain Analysis', 'vortex-ai-marketplace'); ?></h3>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('vortex_blockchain_tools_nonce'); ?>
                        <input type="hidden" name="vortex_blockchain_action" value="analyze_blockchain">
                        
                        <div class="form-field">
                            <p><?php echo esc_html__('Run CLOE market analysis to identify NFT trends, optimal gas prices, and token liquidity insights.', 'vortex-ai-marketplace'); ?></p>
                            <p class="status-info">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <?php echo esc_html__('Last analysis:', 'vortex-ai-marketplace'); ?> 
                                <strong><?php echo esc_html($last_analysis); ?></strong>
                            </p>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="button button-primary"><?php 
                                echo empty($ai_settings['cloe_enabled']) 
                                    ? esc_html__('Enable CLOE in AI Settings', 'vortex-ai-marketplace')
                                    : esc_html__('Analyze Blockchain', 'vortex-ai-marketplace'); 
                            ?></button>
                        </div>
                    </form>
                </div>
                
                <!-- Marketplace Metrics -->
                <div class="vortex-action-card metrics-card">
                    <h3><?php echo esc_html__('Marketplace Metrics', 'vortex-ai-marketplace'); ?></h3>
                    
                    <div class="marketplace-metrics">
                        <p><?php echo esc_html__('These metrics represent the on-chain activity of your marketplace:', 'vortex-ai-marketplace'); ?></p>
                        
                        <ul class="metrics-list">
                            <li>
                                <span class="metric-name"><?php echo esc_html__('Total Transactions:', 'vortex-ai-marketplace'); ?></span>
                                <span class="metric-value"><?php echo esc_html(number_format(vortex_get_transaction_count())); ?></span>
                            </li>
                            <li>
                                <span class="metric-name"><?php echo esc_html__('Unique Buyers:', 'vortex-ai-marketplace'); ?></span>
                                <span class="metric-value"><?php echo esc_html(number_format(vortex_get_unique_buyers_count())); ?></span>
                            </li>
                            <li>
                                <span class="metric-name"><?php echo esc_html__('Marketplace Volume (ETH):', 'vortex-ai-marketplace'); ?></span>
                                <span class="metric-value"><?php echo esc_html(number_format(vortex_get_marketplace_volume(), 4)); ?></span>
                            </li>
                            <li>
                                <span class="metric-name"><?php echo esc_html__('Average NFT Price (TOLA):', 'vortex-ai-marketplace'); ?></span>
                                <span class="metric-value"><?php echo esc_html(number_format(vortex_get_average_nft_price(), 2)); ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="vortex-blockchain-tips">
        <div class="blockchain-tip">
            <h4><span class="dashicons dashicons-lightbulb"></span> <?php echo esc_html__('Gas Saving Tip', 'vortex-ai-marketplace'); ?></h4>
            <p><?php echo esc_html__('Use batch minting to create multiple NFTs in a single transaction and save on gas fees.', 'vortex-ai-marketplace'); ?></p>
        </div>
        
        <div class="blockchain-tip">
            <h4><span class="dashicons dashicons-lightbulb"></span> <?php echo esc_html__('Security Tip', 'vortex-ai-marketplace'); ?></h4>
            <p><?php echo esc_html__('Always verify contract addresses and transaction details before confirming any blockchain operation.', 'vortex-ai-marketplace'); ?></p>
        </div>
        
        <div class="blockchain-tip">
            <h4><span class="dashicons dashicons-lightbulb"></span> <?php echo esc_html__('AI Integration Tip', 'vortex-ai-marketplace'); ?></h4>
            <p><?php echo esc_html__('CLOE analysis can predict optimal times for NFT minting based on gas price trends and market activity.', 'vortex-ai-marketplace'); ?></p>
        </div>
    </div>
</div>

<style>
.vortex-blockchain-dashboard {
    margin-top: 20px;
}

.vortex-blockchain-stats {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.stat-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 15px;
    text-align: center;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
}

.stat-title {
    display: block;
    font-size: 14px;
    color: #50575e;
    margin-bottom: 10px;
}

.stat-value {
    display: block;
    font-size: 24px;
    font-weight: 600;
    color: #1e1e1e;
}

.vortex-blockchain-insights {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
}

.blockchain-insights-list {
    margin: 0;
    padding: 0;
    list-style: none;
}

.insight-item {
    padding: 12px 15px;
    border-left: 4px solid #2271b1;
    background: #f6f7f7;
    margin-bottom: 10px;
    display: grid;
    grid-template-columns: 30px 1fr;
    gap: 10px;
    align-items: center;
}

.insight-icon {
    color: #2271b1;
}

.insight-content {
    font-size: 14px;
}

.vortex-blockchain-actions {
    margin-top: 20px;
    margin-bottom: 20px;
}

.action-columns {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.vortex-action-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
}

.vortex-action-card h3 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.form-field {
    margin-bottom: 15px;
}

.form-field label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.form-field input[type="text"],
.form-field input[type="number"],
.form-field select {
    width: 100%;
    padding: 8px;
    border: 1px solid #8c8f94;
    border-radius: 4px;
}

.form-field .description {
    font-size: 12px;
    color: #646970;
    margin-top: 5px;
}

.form-actions {
    margin-top: 15px;
}

.status-info {
    font-size: 13px;
    color: #646970;
    margin-top: 5px;
}

.vortex-action-card.cloe-analysis {
    background-color: #f0f7fb;
    border-color: #72aee6;
}

.vortex-action-card.metrics-card {
    background-color: #f9f9f9;
}

.marketplace-metrics {
    margin-top: 10px;
}

.metrics-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.metrics-list li {
    padding: 10px 0;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
}

.metrics-list li:last-child {
    border-bottom: none;
}

.metric-name {
    font-weight: 600;
}

.vortex-blockchain-tips {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.blockchain-tip {
    background: #f6f7f7;
    border-left: 4px solid #2271b1;
    padding: 15px;
}

.blockchain-tip h4 {
    margin-top: 0;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
}

.blockchain-tip .dashicons {
    margin-right: 8px;
    color: #2271b1;
}

.blockchain-tip p {
    margin: 0;
    font-size: 13px;
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Gas strategy selection
    $('#gas_strategy').on('change', function() {
        if ($(this).val() === 'ai_optimized') {
            $('#max_gas_fee').prop('disabled', true).val('Auto');
        } else {
            $('#max_gas_fee').prop('disabled', false).val('50');
        }
    });
    
    // Add clipboard copy functionality for transaction hashes
    $('.copy-tx-hash').on('click', function(e) {
        e.preventDefault();
        
        var $this = $(this);
        var $temp = $('<input>');
        $('body').append($temp);
        $temp.val($this.data('hash')).select();
        document.execCommand('copy');
        $temp.remove();
        
        var $icon = $this.find('.dashicons');
        var originalClass = $icon.attr('class');
        
        $icon.attr('class', 'dashicons dashicons-yes-alt');
        
        setTimeout(function() {
            $icon.attr('class', originalClass);
        }, 2000);
    });
});
</script> 