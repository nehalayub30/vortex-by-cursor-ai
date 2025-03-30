<?php
/**
 * Blockchain Connection
 *
 * Manages connections to the blockchain for NFT and smart contract operations.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class Vortex_Blockchain_Connection {
    
    /**
     * Blockchain provider URL
     *
     * @var string
     */
    private $provider_url;
    
    /**
     * Default blockchain network
     *
     * @var string
     */
    private $default_network;
    
    /**
     * Smart contract ABI
     *
     * @var array
     */
    private $contract_abi;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Get blockchain settings
        $this->provider_url = get_option('vortex_blockchain_provider', 'https://testnet.tola-chain.io');
        $this->default_network = get_option('vortex_blockchain_network', 'tola');
        
        // Load contract ABI
        $this->load_contract_abi();
        
        // Register blockchain settings
        add_action('admin_init', array($this, 'register_blockchain_settings'));
    }
    
    /**
     * Register blockchain settings
     */
    public function register_blockchain_settings() {
        register_setting('vortex_settings', 'vortex_blockchain_provider');
        register_setting('vortex_settings', 'vortex_blockchain_network');
        register_setting('vortex_settings', 'vortex_contract_address');
        register_setting('vortex_settings', 'vortex_marketplace_address');
        register_setting('vortex_settings', 'vortex_admin_wallet');
        
        add_settings_section(
            'vortex_blockchain_settings',
            __('Blockchain Settings', 'vortex-ai-marketplace'),
            array($this, 'blockchain_settings_section_callback'),
            'vortex_settings'
        );
        
        add_settings_field(
            'vortex_blockchain_provider',
            __('Blockchain Provider URL', 'vortex-ai-marketplace'),
            array($this, 'blockchain_provider_callback'),
            'vortex_settings',
            'vortex_blockchain_settings'
        );
        
        add_settings_field(
            'vortex_blockchain_network',
            __('Blockchain Network', 'vortex-ai-marketplace'),
            array($this, 'blockchain_network_callback'),
            'vortex_settings',
            'vortex_blockchain_settings'
        );
        
        add_settings_field(
            'vortex_contract_address',
            __('NFT Contract Address', 'vortex-ai-marketplace'),
            array($this, 'contract_address_callback'),
            'vortex_settings',
            'vortex_blockchain_settings'
        );
        
        add_settings_field(
            'vortex_marketplace_address',
            __('Marketplace Contract Address', 'vortex-ai-marketplace'),
            array($this, 'marketplace_address_callback'),
            'vortex_settings',
            'vortex_blockchain_settings'
        );
        
        add_settings_field(
            'vortex_admin_wallet',
            __('Admin Wallet Address', 'vortex-ai-marketplace'),
            array($this, 'admin_wallet_callback'),
            'vortex_settings',
            'vortex_blockchain_settings'
        );
    }
    
    /**
     * Blockchain settings section callback
     */
    public function blockchain_settings_section_callback() {
        echo '<p>' . __('Configure blockchain settings for artwork verification and swapping.', 'vortex-ai-marketplace') . '</p>';
    }
    
    /**
     * Blockchain provider callback
     */
    public function blockchain_provider_callback() {
        $provider = get_option('vortex_blockchain_provider', 'https://testnet.tola-chain.io');
        echo '<input type="text" id="vortex_blockchain_provider" name="vortex_blockchain_provider" value="' . esc_attr($provider) . '" class="regular-text" />';
        echo '<p class="description">' . __('The blockchain provider URL for API connections.', 'vortex-ai-marketplace') . '</p>';
    }
    
    /**
     * Blockchain network callback
     */
    public function blockchain_network_callback() {
        $network = get_option('vortex_blockchain_network', 'tola');
        ?>
        <select id="vortex_blockchain_network" name="vortex_blockchain_network">
            <option value="tola" <?php selected($network, 'tola'); ?>><?php _e('Tola Network', 'vortex-ai-marketplace'); ?></option>
            <option value="ethereum" <?php selected($network, 'ethereum'); ?>><?php _e('Ethereum', 'vortex-ai-marketplace'); ?></option>
            <option value="polygon" <?php selected($network, 'polygon'); ?>><?php _e('Polygon', 'vortex-ai-marketplace'); ?></option>
        </select>
        <p class="description"><?php _e('The blockchain network to use for artwork verification.', 'vortex-ai-marketplace'); ?></p>
        <?php
    }
    
    /**
     * Contract address callback
     */
    public function contract_address_callback() {
        $address = get_option('vortex_contract_address', '');
        echo '<input type="text" id="vortex_contract_address" name="vortex_contract_address" value="' . esc_attr($address) . '" class="regular-text" />';
        echo '<p class="description">' . __('The NFT contract address for artwork verification.', 'vortex-ai-marketplace') . '</p>';
    }
    
    /**
     * Marketplace address callback
     */
    public function marketplace_address_callback() {
        $address = get_option('vortex_marketplace_address', '');
        echo '<input type="text" id="vortex_marketplace_address" name="vortex_marketplace_address" value="' . esc_attr($address) . '" class="regular-text" />';
        echo '<p class="description">' . __('The marketplace contract address for artwork swapping.', 'vortex-ai-marketplace') . '</p>';
    }
    
    /**
     * Admin wallet callback
     */
    public function admin_wallet_callback() {
        $wallet = get_option('vortex_admin_wallet', '');
        echo '<input type="text" id="vortex_admin_wallet" name="vortex_admin_wallet" value="' . esc_attr($wallet) . '" class="regular-text" />';
        echo '<p class="description">' . __('The admin wallet address for contract deployments.', 'vortex-ai-marketplace') . '</p>';
    }
    
    /**
     * Load contract ABI
     */
    private function load_contract_abi() {
        // Create blockchain directory if it doesn't exist
        $blockchain_dir = plugin_dir_path(__FILE__) . 'blockchain';
        if (!file_exists($blockchain_dir)) {
            wp_mkdir_p($blockchain_dir);
            
            // Create default ABI files if they don't exist
            file_put_contents(
                $blockchain_dir . '/nft-abi.json',
                $this->get_default_nft_abi()
            );
            
            file_put_contents(
                $blockchain_dir . '/marketplace-abi.json',
                $this->get_default_marketplace_abi()
            );
        }
        
        // Load ABI files
        $nft_abi_file = $blockchain_dir . '/nft-abi.json';
        $marketplace_abi_file = $blockchain_dir . '/marketplace-abi.json';
        
        $this->contract_abi = array(
            'nft' => file_exists($nft_abi_file) ? json_decode(file_get_contents($nft_abi_file), true) : array(),
            'marketplace' => file_exists($marketplace_abi_file) ? json_decode(file_get_contents($marketplace_abi_file), true) : array()
        );
    }
    
    /**
     * Get default NFT contract ABI
     *
     * @return string Default NFT ABI JSON
     */
    private function get_default_nft_abi() {
        // This is a simplified ERC-721 ABI
        return json_encode(array(
            array(
                'inputs' => array(
                    array('internalType' => 'string', 'name' => 'name', 'type' => 'string'),
                    array('internalType' => 'string', 'name' => 'symbol', 'type' => 'string')
                ),
                'stateMutability' => 'nonpayable',
                'type' => 'constructor'
            ),
            array(
                'inputs' => array(
                    array('internalType' => 'address', 'name' => 'to', 'type' => 'address'},
                    array('internalType' => 'uint256', 'name' => 'tokenId', 'type' => 'uint256'}
                ),
                'name' => 'approve',
                'outputs' => array(),
                'stateMutability' => 'nonpayable',
                'type' => 'function'
            ),
            array(
                'inputs' => array(
                    array('internalType' => 'address', 'name' => 'owner', 'type' => 'address'}
                ),
                'name' => 'balanceOf',
                'outputs' => array(
                    array('internalType' => 'uint256', 'name' => '', 'type' => 'uint256'}
                ),
                'stateMutability' => 'view',
                'type' => 'function'
            ),
            array(
                'inputs' => array(
                    array('internalType' => 'uint256', 'name' => 'tokenId', 'type' => 'uint256'}
                ),
                'name' => 'tokenURI',
                'outputs' => array(
                    array('internalType' => 'string', 'name' => '', 'type' => 'string'}
                ),
                'stateMutability' => 'view',
                'type' => 'function'
            ),
            array(
                'inputs' => array(
                    array('internalType' => 'address', 'name' => 'from', 'type' => 'address'},
                    array('internalType' => 'address', 'name' => 'to', 'type' => 'address'},
                    array('internalType' => 'uint256', 'name' => 'tokenId', 'type' => 'uint256'}
                ),
                'name' => 'transferFrom',
                'outputs' => array(),
                'stateMutability' => 'nonpayable',
                'type' => 'function'
            ),
            array(
                'inputs' => array(
                    array('internalType' => 'address', 'name' => 'to', 'type' => 'address'},
                    array('internalType' => 'string', 'name' => 'uri', 'type' => 'string'}
                ),
                'name' => 'mint',
                'outputs' => array(
                    array('internalType' => 'uint256', 'name' => '', 'type' => 'uint256'}
                ),
                'stateMutability' => 'nonpayable',
                'type' => 'function'
            )
        ), JSON_PRETTY_PRINT);
    }
    
    /**
     * Get default marketplace contract ABI
     *
     * @return string Default marketplace ABI JSON
     */
    private function get_default_marketplace_abi() {
        // This is a simplified marketplace ABI for swapping NFTs
        return json_encode(array(
            array(
                'inputs' => array(),
                'stateMutability' => 'nonpayable',
                'type' => 'constructor'
            ),
            array(
                'inputs' => array(
                    array('internalType' => 'address', 'name' => 'nftContract1', 'type' => 'address'},
                    array('internalType' => 'uint256', 'name' => 'tokenId1', 'type' => 'uint256'},
                    array('internalType' => 'address', 'name' => 'nftContract2', 'type' => 'address'},
                    array('internalType' => 'uint256', 'name' => 'tokenId2', 'type' => 'uint256'}
                ),
                'name' => 'createSwap',
                'outputs' => array(
                    array('internalType' => 'uint256', 'name' => '', 'type' => 'uint256'}
                ),
                'stateMutability' => 'nonpayable',
                'type' => 'function'
            ),
            array(
                'inputs' => array(
                    array('internalType' => 'uint256', 'name' => 'swapId', 'type' => 'uint256'}
                ),
                'name' => 'acceptSwap',
                'outputs' => array(),
                'stateMutability' => 'nonpayable',
                'type' => 'function'
            ),
            array(
                'inputs' => array(
                    array('internalType' => 'uint256', 'name' => 'swapId', 'type' => 'uint256'}
                ),
                'name' => 'cancelSwap',
                'outputs' => array(),
                'stateMutability' => 'nonpayable',
                'type' => 'function'
            ),
            array(
                'inputs' => array(
                    array('internalType' => 'uint256', 'name' => 'swapId', 'type' => 'uint256'}
                ),
                'name' => 'getSwap',
                'outputs' => array(
                    array('internalType' => 'address', 'name' => 'creator', 'type' => 'address'},
                    array('internalType' => 'address', 'name' => 'nftContract1', 'type' => 'address'},
                    array('internalType' => 'uint256', 'name' => 'tokenId1', 'type' => 'uint256'},
                    array('internalType' => 'address', 'name' => 'nftContract2', 'type' => 'address'},
                    array('internalType' => 'uint256', 'name' => 'tokenId2', 'type' => 'uint256'},
                    array('internalType' => 'enum Marketplace.SwapStatus', 'name' => 'status', 'type' => 'uint8'}
                ),
                'stateMutability' => 'view',
                'type' => 'function'
            ),
            array(
                'inputs' => array(
                    array('internalType' => 'address', 'name' => 'nftContract1', 'type' => 'address'},
                    array('internalType' => 'uint256', 'name' => 'tokenId1', 'type' => 'uint256'},
                    array('internalType' => 'address', 'name' => 'owner1', 'type' => 'address'},
                    array('internalType' => 'address', 'name' => 'nftContract2', 'type' => 'address'},
                    array('internalType' => 'uint256', 'name' => 'tokenId2', 'type' => 'uint256'},
                    array('internalType' => 'address', 'name' => 'owner2', 'type' => 'address'}
                ),
                'name' => 'executeDirectSwap',
                'outputs' => array(
                    array('internalType' => 'uint256', 'name' => '', 'type' => 'uint256'}
                ),
                'stateMutability' => 'nonpayable',
                'type' => 'function'
            )
        ), JSON_PRETTY_PRINT);
    }
    
    /**
     * Verify contract on blockchain
     *
     * @param string $contract_address Contract address
     * @param array $params Additional parameters
     * @return bool Whether contract is valid
     */
    public function verify_contract($contract_address, $params = array()) {
        // In a real implementation, this would connect to the blockchain
        // and verify the contract exists and matches the expected parameters
        
        // For demonstration purposes, we'll simulate a successful verification
        // In production, this would call the blockchain API
        
        // Simulate API call
        $response = $this->simulate_api_call('verify_contract', array(
            'contract_address' => $contract_address,
            'params' => $params
        ));
        
        return isset($response['valid']) && $response['valid'] === true;
    }
    
    /**
     * Create NFT contract for artwork
     *
     * @param array $metadata Token metadata
     * @param string $owner_address Owner wallet address
     * @return array|WP_Error Contract data or error
     */
    public function create_nft_contract($metadata, $owner_address) {
        // In a real implementation, this would connect to the blockchain
        // and create a new NFT contract or mint a new token
        
        // Prepare metadata URI (in production, this would be stored on IPFS)
        $metadata_uri = add_query_arg(array(
            'timestamp' => time(),
            'hash' => md5(json_encode($metadata))
        ), home_url('/api/nft-metadata/'));
        
        // Simulate API call
        $response = $this->simulate_api_call('create_nft', array(
            'metadata' => $metadata,
            'metadata_uri' => $metadata_uri,
            'owner' => $owner_address,
            'contract_address' => get_option('vortex_contract_address', '')
        ));
        
        if (isset($response['error'])) {
            return new WP_Error('blockchain_error', $response['error']);
        }
        
        return array(
            'contract_address' => $response['contract_address'],
            'token_id' => $response['token_id'],
            'transaction_hash' => $response['transaction_hash'],
            'metadata_uri' => $metadata_uri
        );
    }
    
    /**
     * Execute swap transaction
     *
     * @param string $first_contract First contract address
     * @param string $first_token_id First token ID
     * @param string $first_owner First owner address
     * @param string $second_contract Second contract address
     * @param string $second_token_id Second token ID
     * @param string $second_owner Second owner address
     * @return array|WP_Error Transaction data or error
     */
    public function execute_swap_transaction($first_contract, $first_token_id, $first_owner, $second_contract, $second_token_id, $second_owner) {
        // In a real implementation, this would connect to the blockchain
        // and execute a swap transaction between two NFTs
        
        // Get marketplace contract address
        $marketplace_address = get_option('vortex_marketplace_address', '');
        
        if (empty($marketplace_address)) {
            return new WP_Error('marketplace_missing', __('Marketplace contract address is not configured.', 'vortex-ai-marketplace'));
        }
        
        // Simulate API call
        $response = $this->simulate_api_call('execute_swap', array(
            'marketplace_address' => $marketplace_address,
            'first_contract' => $first_contract,
            'first_token_id' => $first_token_id,
            'first_owner' => $first_owner,
            'second_contract' => $second_contract,
            'second_token_id' => $second_token_id,
            'second_owner' => $second_owner
        ));
        
        if (isset($response['error'])) {
            return new WP_Error('swap_error', $response['error']);
        }
        
        return array(
            'transaction_hash' => $response['transaction_hash'],
            'swap_id' => $response['swap_id'],
            'block_number' => $response['block_number'],
            'timestamp' => $response['timestamp']
        );
    }
    
    /**
     * Get token status from blockchain
     *
     * @param string $contract_address Contract address
     * @param string $token_id Token ID
     * @return array|WP_Error Token status or error
     */
    public function get_token_status($contract_address, $token_id) {
        // In a real implementation, this would connect to the blockchain
        // and get the current status of the token
        
        // Simulate API call
        $response = $this->simulate_api_call('get_token_status', array(
            'contract_address' => $contract_address,
            'token_id' => $token_id
        ));
        
        if (isset($response['error'])) {
            return new WP_Error('token_error', $response['error']);
        }
        
        return array(
            'owner' => $response['owner'],
            'uri' => $response['uri'],
            'last_transfer' => $response['last_transfer']
        );
    }
    
    /**
     * Simulate blockchain API call
     *
     * @param string $method API method
     * @param array $params Parameters
     * @return array Response data
     */
    private function simulate_api_call($method, $params) {
        // This is a simulation function for development
        // In production, this would make actual API calls to the blockchain
        
        switch ($method) {
            case 'verify_contract':
                // Simulate contract verification
                return array(
                    'valid' => true,
                    'contract_type' => 'ERC-721',
                    'owner' => isset($params['owner']) ? $params['owner'] : '0x0000000000000000000000000000000000000000'
                );
                
            case 'create_nft':
                // Simulate NFT creation
                return array(
                    'contract_address' => $params['contract_address'] ?: '0x1234567890abcdef1234567890abcdef12345678',
                    'token_id' => (string) mt_rand(1000000, 9999999),
                    'owner' => $params['owner'],
                    'transaction_hash' => '0x' . bin2hex(random_bytes(32)),
                    'block_number' => mt_rand(10000000, 99999999),
                    'timestamp' => time()
                );
                
            case 'execute_swap':
                // Simulate swap execution
                return array(
                    'transaction_hash' => '0x' . bin2hex(random_bytes(32)),
                    'swap_id' => (string) mt_rand(1000, 9999),
                    'block_number' => mt_rand(10000000, 99999999),
                    'timestamp' => time(),
                    'status' => 'completed'
                );
                
            case 'get_token_status':
                // Simulate token status
                return array(
                    'owner' => '0x' . bin2hex(random_bytes(20)),
                    'uri' => home_url('/api/nft-metadata/?token=' . $params['token_id']),
                    'last_transfer' => time() - mt_rand(0, 30 * 24 * 60 * 60) // Random time within last 30 days
                );
                
            default:
                return array(
                    'error' => sprintf(__('Unknown API method: %s', 'vortex-ai-marketplace'), $method)
                );
        }
    }
    
    /**
     * Get available networks
     *
     * @return array Available networks
     */
    public function get_available_networks() {
        return array(
            'tola' => __('Tola Network', 'vortex-ai-marketplace'),
            'ethereum' => __('Ethereum', 'vortex-ai-marketplace'),
            'polygon' => __('Polygon', 'vortex-ai-marketplace')
        );
    }
    
    /**
     * Get explorer URL for a transaction
     *
     * @param string $transaction_hash Transaction hash
     * @param string $network Blockchain network
     * @return string Explorer URL
     */
    public function get_explorer_url($transaction_hash, $network = '') {
        if (empty($network)) {
            $network = $this->default_network;
        }
        
        switch ($network) {
            case 'tola':
                return 'https://explorer.tola-chain.io/tx/' . $transaction_hash;
                
            case 'ethereum':
                return 'https://etherscan.io/tx/' . $transaction_hash;
                
            case 'polygon':
                return 'https://polygonscan.com/tx/' . $transaction_hash;
                
            default:
                return '#';
        }
    }
    
    /**
     * Get blockchain API URL
     *
     * @return string API URL
     */
    public function get_api_url() {
        return trailingslashit($this->provider_url) . 'api/v1';
    }
    
    /**
     * Get contract explorer URL
     *
     * @param string $contract_address Contract address
     * @param string $network Blockchain network
     * @return string Explorer URL
     */
    public function get_contract_explorer_url($contract_address, $network = '') {
        if (empty($network)) {
            $network = $this->default_network;
        }
        
        switch ($network) {
            case 'tola':
                return 'https://explorer.tola-chain.io/address/' . $contract_address;
                
            case 'ethereum':
                return 'https://etherscan.io/address/' . $contract_address;
                
            case 'polygon':
                return 'https://polygonscan.com/address/' . $contract_address;
                
            default:
                return '#';
        }
    }
} 