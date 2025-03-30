<?php
/**
 * Blockchain Settings Template
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin/partials/settings
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Add nonce verification and settings save handler
if (isset($_POST['vortex_blockchain_save_settings']) && check_admin_referer('vortex_blockchain_settings_nonce')) {
    // Sanitize and save settings
    $blockchain_settings = array(
        'network' => sanitize_text_field(isset($_POST['vortex_blockchain_network']) ? $_POST['vortex_blockchain_network'] : 'ethereum'),
        'tola_contract' => sanitize_text_field(isset($_POST['vortex_blockchain_tola_contract']) ? $_POST['vortex_blockchain_tola_contract'] : ''),
        'marketplace_contract' => sanitize_text_field(isset($_POST['vortex_blockchain_marketplace_contract']) ? $_POST['vortex_blockchain_marketplace_contract'] : ''),
        'nft_contract' => sanitize_text_field(isset($_POST['vortex_blockchain_nft_contract']) ? $_POST['vortex_blockchain_nft_contract'] : ''),
        'web3_provider' => sanitize_text_field(isset($_POST['vortex_blockchain_web3_provider']) ? $_POST['vortex_blockchain_web3_provider'] : ''),
        'gas_strategy' => sanitize_text_field(isset($_POST['vortex_blockchain_gas_strategy']) ? $_POST['vortex_blockchain_gas_strategy'] : 'medium'),
        'auto_mint_nft' => isset($_POST['vortex_blockchain_auto_mint']),
        'auto_verify_creator' => isset($_POST['vortex_blockchain_auto_verify']),
        'royalty_percentage' => intval(isset($_POST['vortex_blockchain_royalty']) ? $_POST['vortex_blockchain_royalty'] : 10),
        'huraii_price_analysis' => isset($_POST['vortex_blockchain_huraii_analysis']),
        'cloe_market_prediction' => isset($_POST['vortex_blockchain_cloe_prediction']),
        'ipfs_gateway' => sanitize_text_field(isset($_POST['vortex_blockchain_ipfs_gateway']) ? $_POST['vortex_blockchain_ipfs_gateway'] : 'https://ipfs.io/ipfs/'),
        'ipfs_pinning_service' => sanitize_text_field(isset($_POST['vortex_blockchain_ipfs_pinning']) ? $_POST['vortex_blockchain_ipfs_pinning'] : 'pinata'),
        'ipfs_pinning_key' => sanitize_text_field(isset($_POST['vortex_blockchain_ipfs_key']) ? $_POST['vortex_blockchain_ipfs_key'] : ''),
        'auto_wallet_creation' => isset($_POST['vortex_blockchain_auto_wallet']),
        'require_tola_for_access' => isset($_POST['vortex_blockchain_require_tola']),
        'min_tola_required' => intval(isset($_POST['vortex_blockchain_min_tola']) ? $_POST['vortex_blockchain_min_tola'] : 1),
        'enable_wallet_recovery' => isset($_POST['vortex_blockchain_wallet_recovery']),
        'initial_tola_balance' => intval(isset($_POST['vortex_blockchain_initial_tola']) ? $_POST['vortex_blockchain_initial_tola'] : 0)
    );
    
    // Securely store the settings
    update_option('vortex_blockchain_settings', $blockchain_settings);
    
    // Display success message
    add_settings_error(
        'vortex_messages', 
        'vortex_blockchain_message', 
        esc_html__('Blockchain Settings Saved Successfully', 'vortex-ai-marketplace'), 
        'updated'
    );
}

// Get current settings with default values
$blockchain_settings = get_option('vortex_blockchain_settings', array(
    'network' => 'ethereum',
    'tola_contract' => '',
    'marketplace_contract' => '',
    'nft_contract' => '',
    'web3_provider' => 'https://mainnet.infura.io/v3/',
    'gas_strategy' => 'medium',
    'auto_mint_nft' => true,
    'auto_verify_creator' => true,
    'royalty_percentage' => 10,
    'huraii_price_analysis' => true,
    'cloe_market_prediction' => true,
    'ipfs_gateway' => 'https://ipfs.io/ipfs/',
    'ipfs_pinning_service' => 'pinata',
    'ipfs_pinning_key' => '',
    'auto_wallet_creation' => false,
    'require_tola_for_access' => false,
    'min_tola_required' => 1,
    'enable_wallet_recovery' => true,
    'initial_tola_balance' => 0
));

// Supported blockchain networks
$blockchain_networks = array(
    'ethereum' => __('Ethereum Mainnet', 'vortex-ai-marketplace'),
    'polygon' => __('Polygon (Matic)', 'vortex-ai-marketplace'),
    'bsc' => __('Binance Smart Chain', 'vortex-ai-marketplace'),
    'arbitrum' => __('Arbitrum One', 'vortex-ai-marketplace'),
    'optimism' => __('Optimism', 'vortex-ai-marketplace'),
    'avalanche' => __('Avalanche C-Chain', 'vortex-ai-marketplace'),
    'base' => __('Base', 'vortex-ai-marketplace')
);

// Gas price strategies
$gas_strategies = array(
    'low' => __('Low (Slower, cheaper)', 'vortex-ai-marketplace'),
    'medium' => __('Medium (Balanced)', 'vortex-ai-marketplace'),
    'high' => __('High (Faster, expensive)', 'vortex-ai-marketplace'),
    'auto' => __('Auto (AI-optimized)', 'vortex-ai-marketplace')
);

// IPFS pinning services
$ipfs_services = array(
    'pinata' => __('Pinata', 'vortex-ai-marketplace'),
    'infura' => __('Infura', 'vortex-ai-marketplace'),
    'web3storage' => __('Web3.Storage', 'vortex-ai-marketplace'),
    'nftport' => __('NFTPort', 'vortex-ai-marketplace'),
    'custom' => __('Custom Service', 'vortex-ai-marketplace')
);

?>

<div class="vortex-settings-content">
    <h2><?php echo esc_html__('Blockchain Settings', 'vortex-ai-marketplace'); ?></h2>
    <?php settings_errors('vortex_messages'); ?>
    
    <form method="post" action="">
        <?php wp_nonce_field('vortex_blockchain_settings_nonce'); ?>

        <div class="vortex-section">
            <h3><?php esc_html_e('Blockchain Network', 'vortex-ai-marketplace'); ?></h3>
    
    <table class="form-table">
        <tr>
            <th scope="row">
                        <label for="vortex_blockchain_network">
                            <?php esc_html_e('Network', 'vortex-ai-marketplace'); ?>
                </label>
            </th>
            <td>
                <select id="vortex_blockchain_network" name="vortex_blockchain_network">
                            <?php foreach ($blockchain_networks as $network_id => $network_name) : ?>
                                <option value="<?php echo esc_attr($network_id); ?>" <?php selected($blockchain_settings['network'], $network_id); ?>>
                                    <?php echo esc_html($network_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                        <p class="description">
                            <?php esc_html_e('Select the blockchain network for your marketplace.', 'vortex-ai-marketplace'); ?>
                        </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                        <label for="vortex_blockchain_web3_provider">
                            <?php esc_html_e('Web3 Provider URL', 'vortex-ai-marketplace'); ?>
                        </label>
            </th>
            <td>
                        <input type="text" 
                               id="vortex_blockchain_web3_provider" 
                               name="vortex_blockchain_web3_provider" 
                               value="<?php echo esc_attr($blockchain_settings['web3_provider']); ?>" 
                               class="regular-text"
                               autocomplete="off">
                        <p class="description">
                            <?php esc_html_e('Enter your Web3 provider URL (e.g., Infura endpoint)', 'vortex-ai-marketplace'); ?>
                        </p>
            </td>
        </tr>
    </table>
</div>

        <div class="vortex-section">
            <h3><?php esc_html_e('Smart Contracts', 'vortex-ai-marketplace'); ?></h3>
    
    <table class="form-table">
        <tr>
            <th scope="row">
                        <label for="vortex_blockchain_tola_contract">
                            <?php esc_html_e('TOLA Token Contract', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="text" 
                               id="vortex_blockchain_tola_contract" 
                               name="vortex_blockchain_tola_contract" 
                               value="<?php echo esc_attr($blockchain_settings['tola_contract']); ?>" 
                               class="regular-text code">
                        <p class="description">
                            <?php esc_html_e('Enter the contract address for the TOLA token.', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="vortex_blockchain_marketplace_contract">
                            <?php esc_html_e('Marketplace Contract', 'vortex-ai-marketplace'); ?>
                        </label>
            </th>
            <td>
                        <input type="text" 
                               id="vortex_blockchain_marketplace_contract" 
                               name="vortex_blockchain_marketplace_contract" 
                               value="<?php echo esc_attr($blockchain_settings['marketplace_contract']); ?>" 
                               class="regular-text code">
                        <p class="description">
                            <?php esc_html_e('Enter the contract address for the marketplace.', 'vortex-ai-marketplace'); ?>
                        </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                        <label for="vortex_blockchain_nft_contract">
                            <?php esc_html_e('NFT Contract', 'vortex-ai-marketplace'); ?>
                        </label>
            </th>
            <td>
                        <input type="text" 
                               id="vortex_blockchain_nft_contract" 
                               name="vortex_blockchain_nft_contract" 
                               value="<?php echo esc_attr($blockchain_settings['nft_contract']); ?>" 
                               class="regular-text code">
                        <p class="description">
                            <?php esc_html_e('Enter the contract address for the NFT collection.', 'vortex-ai-marketplace'); ?>
                        </p>
            </td>
        </tr>
    </table>
</div>

        <div class="vortex-section">
            <h3><?php esc_html_e('NFT Settings', 'vortex-ai-marketplace'); ?></h3>
    
    <table class="form-table">
        <tr>
                    <th scope="row"><?php esc_html_e('NFT Creation', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="vortex_blockchain_auto_mint" 
                                   value="1" 
                                   <?php checked($blockchain_settings['auto_mint_nft']); ?>>
                            <?php esc_html_e('Auto-mint NFTs for artwork', 'vortex-ai-marketplace'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Automatically create NFTs when artwork is uploaded.', 'vortex-ai-marketplace'); ?>
                        </p>
                        <br>
                        <label>
                            <input type="checkbox" 
                                   name="vortex_blockchain_auto_verify" 
                                   value="1" 
                                   <?php checked($blockchain_settings['auto_verify_creator']); ?>>
                            <?php esc_html_e('Auto-verify creators', 'vortex-ai-marketplace'); ?>
                </label>
                        <p class="description">
                            <?php esc_html_e('Automatically verify creators using blockchain signatures.', 'vortex-ai-marketplace'); ?>
                        </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                        <label for="vortex_blockchain_royalty">
                            <?php esc_html_e('Default Royalty Percentage', 'vortex-ai-marketplace'); ?>
                        </label>
            </th>
            <td>
                        <input type="number" 
                               id="vortex_blockchain_royalty" 
                               name="vortex_blockchain_royalty" 
                               value="<?php echo esc_attr($blockchain_settings['royalty_percentage']); ?>" 
                               class="small-text"
                               min="0"
                               max="50"
                               step="0.1">%
                        <p class="description">
                            <?php esc_html_e('Default royalty percentage for secondary sales (0-50%).', 'vortex-ai-marketplace'); ?>
                        </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                        <label for="vortex_blockchain_gas_strategy">
                            <?php esc_html_e('Gas Price Strategy', 'vortex-ai-marketplace'); ?>
                        </label>
            </th>
            <td>
                        <select id="vortex_blockchain_gas_strategy" name="vortex_blockchain_gas_strategy">
                            <?php foreach ($gas_strategies as $strategy_id => $strategy_name) : ?>
                                <option value="<?php echo esc_attr($strategy_id); ?>" <?php selected($blockchain_settings['gas_strategy'], $strategy_id); ?>>
                                    <?php echo esc_html($strategy_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Select gas price strategy for transactions.', 'vortex-ai-marketplace'); ?>
                        </p>
            </td>
        </tr>
    </table>
</div>

        <div class="vortex-section">
            <h3><?php esc_html_e('IPFS Storage', 'vortex-ai-marketplace'); ?></h3>
    
    <table class="form-table">
        <tr>
            <th scope="row">
                        <label for="vortex_blockchain_ipfs_gateway">
                            <?php esc_html_e('IPFS Gateway', 'vortex-ai-marketplace'); ?>
                        </label>
            </th>
            <td>
                        <input type="url" 
                               id="vortex_blockchain_ipfs_gateway" 
                               name="vortex_blockchain_ipfs_gateway" 
                               value="<?php echo esc_attr($blockchain_settings['ipfs_gateway']); ?>" 
                               class="regular-text">
                        <p class="description">
                            <?php esc_html_e('Enter your preferred IPFS gateway URL.', 'vortex-ai-marketplace'); ?>
                        </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                        <label for="vortex_blockchain_ipfs_pinning">
                            <?php esc_html_e('IPFS Pinning Service', 'vortex-ai-marketplace'); ?>
                        </label>
            </th>
            <td>
                        <select id="vortex_blockchain_ipfs_pinning" name="vortex_blockchain_ipfs_pinning">
                            <?php foreach ($ipfs_services as $service_id => $service_name) : ?>
                                <option value="<?php echo esc_attr($service_id); ?>" <?php selected($blockchain_settings['ipfs_pinning_service'], $service_id); ?>>
                                    <?php echo esc_html($service_name); ?>
                                </option>
                <?php endforeach; ?>
                        </select>
            </td>
        </tr>
        <tr>
            <th scope="row">
                        <label for="vortex_blockchain_ipfs_key">
                            <?php esc_html_e('IPFS API Key', 'vortex-ai-marketplace'); ?>
                        </label>
            </th>
            <td>
                        <input type="password" 
                               id="vortex_blockchain_ipfs_key" 
                               name="vortex_blockchain_ipfs_key" 
                               value="<?php echo esc_attr($blockchain_settings['ipfs_pinning_key']); ?>" 
                               class="regular-text"
                               autocomplete="off">
                        <button type="button" class="button toggle-password" data-target="vortex_blockchain_ipfs_key">
                            <?php esc_html_e('Show/Hide', 'vortex-ai-marketplace'); ?>
                        </button>
                        <p class="description">
                            <?php esc_html_e('API key for the selected IPFS pinning service.', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="vortex-section">
            <h3><?php esc_html_e('AI Integration', 'vortex-ai-marketplace'); ?></h3>
            
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('AI Blockchain Analysis', 'vortex-ai-marketplace'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="vortex_blockchain_huraii_analysis" 
                                   value="1" 
                                   <?php checked($blockchain_settings['huraii_price_analysis']); ?>>
                            <?php esc_html_e('Enable HURAII price analysis', 'vortex-ai-marketplace'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Use HURAII to analyze blockchain activity and optimize pricing.', 'vortex-ai-marketplace'); ?>
                        </p>
                        <br>
                        <label>
                            <input type="checkbox" 
                                   name="vortex_blockchain_cloe_prediction" 
                                   value="1" 
                                   <?php checked($blockchain_settings['cloe_market_prediction']); ?>>
                            <?php esc_html_e('Enable CLOE market prediction', 'vortex-ai-marketplace'); ?>
                </label>
                        <p class="description">
                            <?php esc_html_e('Use CLOE to predict market trends and performance.', 'vortex-ai-marketplace'); ?>
                        </p>
            </td>
        </tr>
    </table>
</div> 

        <div class="vortex-section">
            <h3><?php _e('Wallet & TOLA Access Settings', 'vortex-ai-marketplace'); ?></h3>
            <p class="description">
                <?php _e('Configure wallet creation and TOLA access requirements for the marketplace.', 'vortex-ai-marketplace'); ?>
            </p>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="vortex_blockchain_auto_wallet">
                            <?php esc_html_e('Auto Create Wallet', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   id="vortex_blockchain_auto_wallet" 
                                   name="vortex_blockchain_auto_wallet" 
                                   value="1" 
                                   <?php checked(isset($blockchain_settings['auto_wallet_creation']) ? $blockchain_settings['auto_wallet_creation'] : false); ?>>
                            <?php esc_html_e('Automatically create wallet for new users on registration', 'vortex-ai-marketplace'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('When enabled, a new TOLA wallet will be created automatically when a user registers.', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="vortex_blockchain_require_tola">
                            <?php esc_html_e('Require TOLA for Access', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   id="vortex_blockchain_require_tola" 
                                   name="vortex_blockchain_require_tola" 
                                   value="1" 
                                   <?php checked(isset($blockchain_settings['require_tola_for_access']) ? $blockchain_settings['require_tola_for_access'] : false); ?>>
                            <?php esc_html_e('Require users to have TOLA tokens to access artwork and marketplace', 'vortex-ai-marketplace'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('When enabled, users must have at least some TOLA tokens in their wallet to view artworks.', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="vortex_blockchain_min_tola">
                            <?php esc_html_e('Minimum TOLA Required', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" 
                               id="vortex_blockchain_min_tola" 
                               name="vortex_blockchain_min_tola" 
                               value="<?php echo esc_attr(isset($blockchain_settings['min_tola_required']) ? $blockchain_settings['min_tola_required'] : 1); ?>"
                               min="0"
                               step="1"
                               class="small-text">
                        <p class="description">
                            <?php esc_html_e('Minimum TOLA tokens required to access restricted content (if TOLA requirement is enabled).', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="vortex_blockchain_wallet_recovery">
                            <?php esc_html_e('Enable Wallet Recovery', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   id="vortex_blockchain_wallet_recovery" 
                                   name="vortex_blockchain_wallet_recovery" 
                                   value="1" 
                                   <?php checked(isset($blockchain_settings['enable_wallet_recovery']) ? $blockchain_settings['enable_wallet_recovery'] : true); ?>>
                            <?php esc_html_e('Allow users to recover wallet access if lost', 'vortex-ai-marketplace'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('When enabled, users can recover wallet access through email verification.', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="vortex_blockchain_initial_tola">
                            <?php esc_html_e('Initial TOLA Balance', 'vortex-ai-marketplace'); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" 
                               id="vortex_blockchain_initial_tola" 
                               name="vortex_blockchain_initial_tola" 
                               value="<?php echo esc_attr(isset($blockchain_settings['initial_tola_balance']) ? $blockchain_settings['initial_tola_balance'] : 0); ?>"
                               min="0"
                               step="1"
                               class="small-text">
                        <p class="description">
                            <?php esc_html_e('Initial TOLA balance for new users (set to 0 to require purchase).', 'vortex-ai-marketplace'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="vortex-shortcode-reference">
            <h3><?php esc_html_e('Blockchain Shortcodes Reference', 'vortex-ai-marketplace'); ?></h3>
            <table class="vortex-shortcode-list">
                <tr>
                    <th><?php esc_html_e('Shortcode', 'vortex-ai-marketplace'); ?></th>
                    <th><?php esc_html_e('Description', 'vortex-ai-marketplace'); ?></th>
                    <th><?php esc_html_e('Parameters', 'vortex-ai-marketplace'); ?></th>
                </tr>
                <tr>
                    <td><code>[vortex_nft_gallery]</code></td>
                    <td><?php esc_html_e('Displays NFT gallery', 'vortex-ai-marketplace'); ?></td>
                    <td><code>limit</code>, <code>artist</code>, <code>sort</code></td>
                </tr>
                <tr>
                    <td><code>[vortex_nft_details]</code></td>
                    <td><?php esc_html_e('Shows details for a specific NFT', 'vortex-ai-marketplace'); ?></td>
                    <td><code>id</code>, <code>contract</code>, <code>token_id</code></td>
                </tr>
                <tr>
                    <td><code>[vortex_blockchain_connect]</code></td>
                    <td><?php esc_html_e('Displays wallet connection button', 'vortex-ai-marketplace'); ?></td>
                    <td><code>text</code>, <code>class</code>, <code>redirect</code></td>
                </tr>
                <tr>
                    <td><code>[vortex_token_balance]</code></td>
                    <td><?php esc_html_e('Shows TOLA token balance', 'vortex-ai-marketplace'); ?></td>
                    <td><code>address</code>, <code>format</code>, <code>show_usd</code></td>
                </tr>
            </table>
        </div>

        <div class="vortex-submit-section">
            <input type="submit" 
                   name="vortex_blockchain_save_settings" 
                   class="button button-primary" 
                   value="<?php esc_attr_e('Save Blockchain Settings', 'vortex-ai-marketplace'); ?>">
        </div>
    </form>
</div>

<style>
.vortex-section {
    margin: 20px 0;
    padding: 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}

.vortex-section h3 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.vortex-shortcode-list {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.vortex-shortcode-list th,
.vortex-shortcode-list td {
    padding: 8px;
    text-align: left;
    border: 1px solid #ddd;
}

.vortex-shortcode-list th {
    background-color: #f8f9fa;
}

.vortex-submit-section {
    margin-top: 20px;
    padding: 20px 0;
    border-top: 1px solid #ddd;
}

input.code {
    font-family: monospace;
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Toggle password visibility
    $('.toggle-password').on('click', function(e) {
        e.preventDefault();
        var target = $(this).data('target');
        var field = $('#' + target);
        var fieldType = field.attr('type');
        field.attr('type', fieldType === 'password' ? 'text' : 'password');
    });
    
    // Dynamic field display based on IPFS service selection
    $('#vortex_blockchain_ipfs_pinning').on('change', function() {
        var service = $(this).val();
        // You could add custom logic here to show/hide fields based on service
    });
    
    // Form change tracking
    var formChanged = false;
    
    $('form input, form select').on('change', function() {
        formChanged = true;
    });
    
    $('form').on('submit', function() {
        window.onbeforeunload = null;
        return true;
    });
    
    window.onbeforeunload = function() {
        if (formChanged) {
            return '<?php echo esc_js(__('You have unsaved changes. Are you sure you want to leave?', 'vortex-ai-marketplace')); ?>';
        }
    };
});
</script> 