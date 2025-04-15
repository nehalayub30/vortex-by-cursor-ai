            <p><?php echo esc_html($atts['description']); ?></p>
            <div class="vortex-rewards-tab">
                <div class="vortex-connect-first">Connect your wallet to view rewards</div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Dashboard shortcode.
     */
    public function dashboard_shortcode($atts) {
        // Enqueue scripts and styles
        $this->enqueue_dao_assets();
        
        // Shortcode attributes
        $atts = shortcode_atts(
            [
                'title' => 'DAO Dashboard',
                'description' => 'Manage your participation in the VORTEX ecosystem',
                'class' => '',
            ],
            $atts,
            'vortex_dashboard'
        );
        
        ob_start();
        ?>
        <div class="vortex-dao-container <?php echo esc_attr($atts['class']); ?>">
            <h2><?php echo esc_html($atts['title']); ?></h2>
            <p><?php echo esc_html($atts['description']); ?></p>
            
            <div class="vortex-connect-wallet-container">
                <button class="vortex-connect-wallet">Connect Wallet</button>
                <div class="vortex-wallet-status">Not Connected</div>
            </div>
            
            <div class="vortex-dashboard">
                <div class="vortex-dashboard-tabs">
                    <div class="tab active" data-tab="vortex-achievements-tab">Achievements</div>
                    <div class="tab" data-tab="vortex-reputation-tab">Reputation</div>
                    <div class="tab" data-tab="vortex-governance-tab">Governance</div>
                    <div class="tab" data-tab="vortex-rewards-tab">Rewards</div>
                </div>
                
                <div id="vortex-achievements-tab" class="vortex-dashboard-tab-content">
                    <div class="vortex-achievement-gallery">
                        <div class="vortex-loading">Loading achievements...</div>
                    </div>
                </div>
                
                <div id="vortex-reputation-tab" class="vortex-dashboard-tab-content">
                    <div class="vortex-reputation-dashboard">
                        <div class="vortex-loading">Loading reputation data...</div>
                    </div>
                </div>
                
                <div id="vortex-governance-tab" class="vortex-dashboard-tab-content">
                    <div class="vortex-governance-tab">
                        <div class="vortex-loading">Loading governance data...</div>
                    </div>
                </div>
                
                <div id="vortex-rewards-tab" class="vortex-dashboard-tab-content">
                    <div class="vortex-rewards-tab">
                        <div class="vortex-loading">Loading rewards data...</div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Enqueue DAO assets.
     */
    private function enqueue_dao_assets() {
        // Use the assets manager to enqueue needed assets
        $assets_manager = vortex_assets_manager();
        $assets_manager->enqueue_dao_assets();
        
        // Register Web3 JavaScript library
        wp_enqueue_script('web3', 'https://cdn.jsdelivr.net/npm/web3@1.8.0/dist/web3.min.js', [], '1.8.0', true);
        
        // Localize script with contract addresses and provider URL
        $dao_data = [
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => rest_url('vortex/v1/'),
            'contracts' => [
                'token' => get_option('vortex_token_address', ''),
                'achievement' => get_option('vortex_achievement_address', ''),
                'reputation' => get_option('vortex_reputation_address', ''),
                'governance' => get_option('vortex_governance_address', ''),
                'treasury' => get_option('vortex_treasury_address', ''),
                'rewards' => get_option('vortex_rewards_address', ''),
            ],
            'provider_url' => get_option('vortex_web3_provider', 'https://rpc.tola-testnet.io'),
            'nonce' => wp_create_nonce('wp_rest'),
        ];
        
        wp_localize_script('vortex-dao', 'vortexDAO', $dao_data);
    }

    /**
     * Get an instance of this class.
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

// Initialize shortcodes
function vortex_dao_shortcodes() {
    return VORTEX_DAO_Shortcodes::get_instance();
}
vortex_dao_shortcodes(); 