<?php
/**
 * VORTEX DAO Gamification Integration
 *
 * @package VORTEX
 */

class VORTEX_DAO_Integration {
    /**
     * Instance of this class.
     *
     * @var object
     */
    protected static $instance = null;

    /**
     * Web3 provider URL.
     *
     * @var string
     */
    private $provider_url;

    /**
     * Contract addresses.
     *
     * @var array
     */
    private $contract_addresses;

    /**
     * Contract ABIs.
     *
     * @var array
     */
    private $contract_abis;
    
    /**
     * Cache duration in seconds.
     *
     * @var int
     */
    private $cache_duration = 300; // 5 minutes

    /**
     * Constructor.
     */
    public function __construct() {
        $this->provider_url = get_option('vortex_web3_provider', 'https://rpc.tola-testnet.io');
        $this->contract_addresses = [
            'token' => get_option('vortex_token_address', ''),
            'achievement' => get_option('vortex_achievement_address', ''),
            'reputation' => get_option('vortex_reputation_address', ''),
            'governance' => get_option('vortex_governance_address', ''),
            'treasury' => get_option('vortex_treasury_address', ''),
            'rewards' => get_option('vortex_rewards_address', ''),
        ];
        
        $this->load_contract_abis();
        
        // Initialize the queue processing system
        $this->init_blockchain_queue();
        
        // Initialize hooks for integration
        $this->init_hooks();
        
        // Add widgets
        add_action('widgets_init', [$this, 'register_widgets']);
        
        // Add dashboard widgets
        add_action('wp_dashboard_setup', [$this, 'add_dashboard_widgets']);
    }

    /**
     * Initialize hooks.
     */
    private function init_hooks() {
        // Register scripts and REST routes
        add_action('init', [$this, 'register_scripts']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        
        // AJAX handlers
        add_action('wp_ajax_vortex_get_user_achievements', [$this, 'ajax_get_user_achievements']);
        add_action('wp_ajax_vortex_get_user_reputation', [$this, 'ajax_get_user_reputation']);
        add_action('wp_ajax_vortex_get_governance_proposals', [$this, 'ajax_get_governance_proposals']);
        add_action('wp_ajax_vortex_get_user_rewards', [$this, 'ajax_get_user_rewards']);
        add_action('wp_ajax_vortex_save_wallet_address', [$this, 'ajax_save_wallet_address']);
        
        // Hooks for gamification events
        add_action('vortex_artwork_created', [$this, 'on_artwork_created'], 10, 2);
        add_action('vortex_artwork_purchased', [$this, 'on_artwork_purchased'], 10, 3);
        add_action('vortex_user_curated_content', [$this, 'on_user_curated_content'], 10, 2);
        add_action('vortex_user_engaged_marketplace', [$this, 'on_marketplace_engagement'], 10, 2);
        add_action('vortex_user_participated_governance', [$this, 'on_governance_participation'], 10, 2);
        add_action('vortex_user_moderated_community', [$this, 'on_community_moderation'], 10, 2);
        add_action('vortex_user_collaborated_ai', [$this, 'on_ai_collaboration'], 10, 2);
        
        // Integration with AI agents
        add_action('vortex_huraii_insight_generated', [$this, 'on_ai_insight_generated'], 10, 3);
        add_action('vortex_cloe_analysis_completed', [$this, 'on_ai_analysis_completed'], 10, 3);
        add_action('vortex_business_strategy_created', [$this, 'on_ai_strategy_created'], 10, 3);
        add_action('vortex_thorius_security_event', [$this, 'on_security_event_detected'], 10, 3);
        
        // Background processing
        add_action('vortex_process_blockchain_queue', [$this, 'process_blockchain_queue']);
        
        // User profile integration
        add_action('show_user_profile', [$this, 'add_wallet_field_to_profile']);
        add_action('edit_user_profile', [$this, 'add_wallet_field_to_profile']);
        add_action('personal_options_update', [$this, 'save_wallet_field_on_profile']);
        add_action('edit_user_profile_update', [$this, 'save_wallet_field_on_profile']);
        
        // Cron jobs for blockchain synchronization
        add_action('vortex_hourly_blockchain_sync', [$this, 'sync_blockchain_data']);
        
        // WooCommerce integration if available
        if (class_exists('WooCommerce')) {
            add_action('woocommerce_order_status_completed', [$this, 'on_woocommerce_purchase']);
        }
    }
    
    /**
     * Initialize the blockchain queue table.
     */
    private function init_blockchain_queue() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_blockchain_queue';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            transaction_type varchar(50) NOT NULL,
            wallet_address varchar(42) NOT NULL,
            data longtext NOT NULL,
            created_at datetime NOT NULL,
            processed_at datetime DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            tx_hash varchar(66) DEFAULT NULL,
            retries int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY wallet_address (wallet_address),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Schedule the cron job if not already scheduled
        if (!wp_next_scheduled('vortex_process_blockchain_queue')) {
            wp_schedule_event(time(), 'five_minutes', 'vortex_process_blockchain_queue');
        }
        
        if (!wp_next_scheduled('vortex_hourly_blockchain_sync')) {
            wp_schedule_event(time(), 'hourly', 'vortex_hourly_blockchain_sync');
        }
    }
    
    /**
     * Register custom widgets.
     */
    public function register_widgets() {
        register_widget('VORTEX_Achievement_Widget');
        register_widget('VORTEX_Reputation_Widget');
        register_widget('VORTEX_Governance_Widget');
        register_widget('VORTEX_Rewards_Widget');
    }
    
    /**
     * Add dashboard widgets for admins.
     */
    public function add_dashboard_widgets() {
        // Only add for users who can manage options
        if (current_user_can('manage_options')) {
            wp_add_dashboard_widget(
                'vortex_dao_stats',
                'VORTEX DAO Statistics',
                [$this, 'render_dao_stats_widget']
            );
            
            wp_add_dashboard_widget(
                'vortex_blockchain_queue',
                'Blockchain Transaction Queue',
                [$this, 'render_blockchain_queue_widget']
            );
        }
    }
    
    /**
     * Render DAO stats widget for the admin dashboard.
     */
    public function render_dao_stats_widget() {
        global $wpdb;
        
        // Get queue stats
        $table_name = $wpdb->prefix . 'vortex_blockchain_queue';
        $pending = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'pending'");
        $completed = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'completed'");
        $failed = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'failed'");
        
        // Get user stats
        $users_with_wallet = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->usermeta WHERE meta_key = 'vortex_wallet_address' AND meta_value != ''");
        
        echo '<div class="vortex-dao-stats">';
        echo '<h3>Transaction Queue</h3>';
        echo '<ul>';
        echo '<li><strong>Pending:</strong> ' . esc_html($pending) . '</li>';
        echo '<li><strong>Completed:</strong> ' . esc_html($completed) . '</li>';
        echo '<li><strong>Failed:</strong> ' . esc_html($failed) . '</li>';
        echo '</ul>';
        
        echo '<h3>User Statistics</h3>';
        echo '<ul>';
        echo '<li><strong>Users with wallet:</strong> ' . esc_html($users_with_wallet) . '</li>';
        echo '</ul>';
        
        // Add quick actions
        echo '<div class="vortex-dao-actions">';
        echo '<a href="' . esc_url(admin_url('admin.php?page=vortex-dao-settings')) . '" class="button">DAO Settings</a> ';
        echo '<a href="#" class="button process-queue">Process Queue Now</a>';
        echo '</div>';
        
        // Add JavaScript to handle the process queue button
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('.vortex-dao-actions .process-queue').on('click', function(e) {
                    e.preventDefault();
                    
                    $(this).prop('disabled', true).text('Processing...');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'vortex_process_blockchain_queue_manually',
                            nonce: '<?php echo wp_create_nonce('vortex_process_queue'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('Queue processing initiated successfully!');
                            } else {
                                alert('Error: ' + response.data.message);
                            }
                        },
                        error: function() {
                            alert('Server error while processing queue.');
                        },
                        complete: function() {
                            $('.vortex-dao-actions .process-queue').prop('disabled', false).text('Process Queue Now');
                        }
                    });
                });
            });
        </script>
        <?php
        echo '</div>';
    }
    
    /**
     * Render blockchain queue widget for the admin dashboard.
     */
    public function render_blockchain_queue_widget() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_blockchain_queue';
        $recent_transactions = $wpdb->get_results(
            "SELECT id, transaction_type, wallet_address, status, created_at, processed_at 
            FROM $table_name 
            ORDER BY created_at DESC 
            LIMIT 10"
        );
        
        if (empty($recent_transactions)) {
            echo '<p>No blockchain transactions in the queue.</p>';
            return;
        }
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>ID</th>';
        echo '<th>Type</th>';
        echo '<th>Wallet</th>';
        echo '<th>Status</th>';
        echo '<th>Created</th>';
        echo '<th>Processed</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($recent_transactions as $tx) {
            $wallet = substr($tx->wallet_address, 0, 6) . '...' . substr($tx->wallet_address, -4);
            
            echo '<tr>';
            echo '<td>' . esc_html($tx->id) . '</td>';
            echo '<td>' . esc_html($tx->transaction_type) . '</td>';
            echo '<td>' . esc_html($wallet) . '</td>';
            echo '<td>' . esc_html($tx->status) . '</td>';
            echo '<td>' . esc_html(human_time_diff(strtotime($tx->created_at), current_time('timestamp')) . ' ago') . '</td>';
            echo '<td>' . (empty($tx->processed_at) ? '-' : esc_html(human_time_diff(strtotime($tx->processed_at), current_time('timestamp')) . ' ago')) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        
        echo '<p><a href="' . esc_url(admin_url('admin.php?page=vortex-blockchain-queue')) . '">View all transactions</a></p>';
    }

    /**
     * Add custom wallet field to user profile.
     */
    public function add_wallet_field_to_profile($user) {
        ?>
        <h3>VORTEX DAO Integration</h3>
        <table class="form-table">
            <tr>
                <th><label for="vortex_wallet_address">Wallet Address</label></th>
                <td>
                    <input type="text" name="vortex_wallet_address" id="vortex_wallet_address" 
                           value="<?php echo esc_attr(get_user_meta($user->ID, 'vortex_wallet_address', true)); ?>" 
                           class="regular-text" pattern="^0x[a-fA-F0-9]{40}$" />
                    <p class="description">Enter your Ethereum-compatible wallet address for DAO participation.</p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save wallet address in user profile.
     */
    public function save_wallet_field_on_profile($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }
        
        if (isset($_POST['vortex_wallet_address'])) {
            $wallet_address = sanitize_text_field($_POST['vortex_wallet_address']);
            
            // Basic validation for Ethereum addresses
            if (empty($wallet_address) || preg_match('/^0x[a-fA-F0-9]{40}$/', $wallet_address)) {
                update_user_meta($user_id, 'vortex_wallet_address', $wallet_address);
            }
        }
    }
    
    /**
     * Process blockchain transaction queue.
     */
    public function process_blockchain_queue() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_blockchain_queue';
        
        // Get pending transactions, oldest first, limit 10
        $transactions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE status = %s AND retries < 3 ORDER BY created_at ASC LIMIT 10",
                'pending'
            )
        );
        
        if (empty($transactions)) {
            return;
        }
        
        foreach ($transactions as $tx) {
            $this->process_transaction($tx);
        }
    }
    
    /**
     * Process an individual blockchain transaction.
     */
    private function process_transaction($tx) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_blockchain_queue';
        
        try {
            $data = json_decode($tx->data, true);
            $result = false;
            
            // Process different transaction types
            switch ($tx->transaction_type) {
                case 'contribution':
                    $result = $this->process_contribution_transaction($tx->wallet_address, $data);
                    break;
                    
                case 'achievement':
                    $result = $this->process_achievement_transaction($tx->wallet_address, $data);
                    break;
                    
                case 'reward':
                    $result = $this->process_reward_transaction($tx->wallet_address, $data);
                    break;
                    
                // Add more transaction types as needed
            }
            
            if ($result) {
                // Update transaction status to completed
                $wpdb->update(
                    $table_name,
                    [
                        'status' => 'completed',
                        'processed_at' => current_time('mysql'),
                        'tx_hash' => $result
                    ],
                    ['id' => $tx->id],
                    ['%s', '%s', '%s'],
                    ['%d']
                );
            } else {
                // Increment retry counter
                $wpdb->update(
                    $table_name,
                    [
                        'retries' => $tx->retries + 1
                    ],
                    ['id' => $tx->id],
                    ['%d'],
                    ['%d']
                );
                
                // If max retries reached, mark as failed
                if ($tx->retries + 1 >= 3) {
                    $wpdb->update(
                        $table_name,
                        [
                            'status' => 'failed',
                            'processed_at' => current_time('mysql')
                        ],
                        ['id' => $tx->id],
                        ['%s', '%s'],
                        ['%d']
                    );
                }
            }
        } catch (Exception $e) {
            // Log error and update retry counter
            error_log('VORTEX DAO Transaction Error: ' . $e->getMessage());
            
            $wpdb->update(
                $table_name,
                [
                    'retries' => $tx->retries + 1,
                    'status' => ($tx->retries + 1 >= 3) ? 'failed' : 'pending'
                ],
                ['id' => $tx->id],
                ['%d', '%s'],
                ['%d']
            );
        }
    }
    
    /**
     * Sync blockchain data for local caching.
     */
    public function sync_blockchain_data() {
        // Get all users with wallet addresses
        $users = get_users([
            'meta_key' => 'vortex_wallet_address',
            'meta_value' => ['', '0x0'],
            'meta_compare' => 'NOT IN'
        ]);
        
        foreach ($users as $user) {
            $wallet_address = get_user_meta($user->ID, 'vortex_wallet_address', true);
            
            // Cache user blockchain data
            $this->cache_user_achievements($wallet_address);
            $this->cache_user_reputation($wallet_address);
            $this->cache_user_rewards($wallet_address);
        }
        
        // Cache governance data
        $this->cache_governance_proposals();
    }
    
    /**
     * Cache user achievements data.
     */
    private function cache_user_achievements($wallet_address) {
        try {
            $achievements = $this->get_user_achievements($wallet_address);
            set_transient('vortex_achievements_' . md5($wallet_address), $achievements, $this->cache_duration);
        } catch (Exception $e) {
            error_log('Error caching achievements: ' . $e->getMessage());
        }
    }
    
    /**
     * Cache user reputation data.
     */
    private function cache_user_reputation($wallet_address) {
        try {
            $reputation = $this->get_user_reputation($wallet_address);
            set_transient('vortex_reputation_' . md5($wallet_address), $reputation, $this->cache_duration);
        } catch (Exception $e) {
            error_log('Error caching reputation: ' . $e->getMessage());
        }
    }
    
    /**
     * Cache user rewards data.
     */
    private function cache_user_rewards($wallet_address) {
        try {
            $rewards = $this->get_user_rewards($wallet_address);
            set_transient('vortex_rewards_' . md5($wallet_address), $rewards, $this->cache_duration);
        } catch (Exception $e) {
            error_log('Error caching rewards: ' . $e->getMessage());
        }
    }
    
    /**
     * Cache governance proposals data.
     */
    private function cache_governance_proposals() {
        try {
            $proposals = $this->get_governance_proposals();
            set_transient('vortex_governance_proposals', $proposals, $this->cache_duration);
        } catch (Exception $e) {
            error_log('Error caching governance proposals: ' . $e->getMessage());
        }
    }

    /**
     * Register scripts for the frontend.
     */
    public function register_scripts() {
        wp_register_script(
            'web3',
            'https://cdn.jsdelivr.net/npm/web3@1.8.0/dist/web3.min.js',
            [],
            '1.8.0',
            true
        );
        
        wp_register_script(
            'vortex-dao',
            plugin_dir_url(__FILE__) . '../assets/js/vortex-dao.js',
            ['jquery', 'web3'],
            VORTEX_VERSION,
            true
        );
        
        $dao_data = [
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => rest_url('vortex/v1/'),
            'contracts' => $this->contract_addresses,
            'provider_url' => $this->provider_url,
            'nonce' => wp_create_nonce('wp_rest'),
        ];
        
        wp_localize_script('vortex-dao', 'vortexDAO', $dao_data);
    }
    
    /**
     * Register REST API routes.
     */
    public function register_rest_routes() {
        register_rest_route('vortex/v1', '/achievements/(?P<wallet_address>[a-zA-Z0-9]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_user_achievements'],
            'permission_callback' => function() {
                return current_user_can('read');
            }
        ]);
        
        register_rest_route('vortex/v1', '/reputation/(?P<wallet_address>[a-zA-Z0-9]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_user_reputation'],
            'permission_callback' => function() {
                return current_user_can('read');
            }
        ]);
        
        register_rest_route('vortex/v1', '/proposals', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_governance_proposals'],
            'permission_callback' => function() {
                return current_user_can('read');
            }
        ]);
        
        register_rest_route('vortex/v1', '/rewards/(?P<wallet_address>[a-zA-Z0-9]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_user_rewards'],
            'permission_callback' => function() {
                return current_user_can('read');
            }
        ]);
    }
    
    /**
     * Load contract ABIs from JSON files.
     */
    private function load_contract_abis() {
        $this->contract_abis = [
            'token' => json_decode(file_get_contents(plugin_dir_path(__FILE__) . '../contracts/abi/TOLAToken.json'), true),
            'achievement' => json_decode(file_get_contents(plugin_dir_path(__FILE__) . '../contracts/abi/VortexAchievement.json'), true),
            'reputation' => json_decode(file_get_contents(plugin_dir_path(__FILE__) . '../contracts/abi/VortexReputation.json'), true),
            'governance' => json_decode(file_get_contents(plugin_dir_path(__FILE__) . '../contracts/abi/VortexGovernance.json'), true),
            'treasury' => json_decode(file_get_contents(plugin_dir_path(__FILE__) . '../contracts/abi/VortexTreasury.json'), true),
            'rewards' => json_decode(file_get_contents(plugin_dir_path(__FILE__) . '../contracts/abi/VortexRewards.json'), true),
        ];
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
    
    /**
     * Get user achievements from blockchain.
     */
    public function rest_get_user_achievements($request) {
        $wallet_address = $request['wallet_address'];
        
        try {
            // This would typically use a Web3 library to call the blockchain
            // For now, we'll implement a simplified version
            $achievements = $this->get_user_achievements($wallet_address);
            return new WP_REST_Response($achievements, 200);
        } catch (Exception $e) {
            return new WP_Error('blockchain_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    /**
     * Get user reputation from blockchain.
     */
    public function rest_get_user_reputation($request) {
        $wallet_address = $request['wallet_address'];
        
        try {
            $reputation_data = $this->get_user_reputation($wallet_address);
            return new WP_REST_Response($reputation_data, 200);
        } catch (Exception $e) {
            return new WP_Error('blockchain_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    /**
     * Get governance proposals from blockchain.
     */
    public function rest_get_governance_proposals($request) {
        try {
            $proposals = $this->get_governance_proposals();
            return new WP_REST_Response($proposals, 200);
        } catch (Exception $e) {
            return new WP_Error('blockchain_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    /**
     * Get user rewards from blockchain.
     */
    public function rest_get_user_rewards($request) {
        $wallet_address = $request['wallet_address'];
        
        try {
            $rewards = $this->get_user_rewards($wallet_address);
            return new WP_REST_Response($rewards, 200);
        } catch (Exception $e) {
            return new WP_Error('blockchain_error', $e->getMessage(), ['status' => 500]);
        }
    }
    
    /**
     * AJAX handler for getting user achievements.
     */
    public function ajax_get_user_achievements() {
        check_ajax_referer('wp_rest', 'nonce');
        
        $wallet_address = sanitize_text_field($_POST['wallet_address']);
        
        try {
            $achievements = $this->get_user_achievements($wallet_address);
            wp_send_json_success($achievements);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
        
        wp_die();
    }
    
    /**
     * AJAX handler for getting user reputation.
     */
    public function ajax_get_user_reputation() {
        check_ajax_referer('wp_rest', 'nonce');
        
        $wallet_address = sanitize_text_field($_POST['wallet_address']);
        
        try {
            $reputation_data = $this->get_user_reputation($wallet_address);
            wp_send_json_success($reputation_data);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
        
        wp_die();
    }
    
    /**
     * AJAX handler for getting governance proposals.
     */
    public function ajax_get_governance_proposals() {
        check_ajax_referer('wp_rest', 'nonce');
        
        try {
            $proposals = $this->get_governance_proposals();
            wp_send_json_success($proposals);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
        
        wp_die();
    }
    
    /**
     * AJAX handler for getting user rewards.
     */
    public function ajax_get_user_rewards() {
        check_ajax_referer('wp_rest', 'nonce');
        
        $wallet_address = sanitize_text_field($_POST['wallet_address']);
        
        try {
            $rewards = $this->get_user_rewards($wallet_address);
            wp_send_json_success($rewards);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
        
        wp_die();
    }
    
    /**
     * Get user achievements.
     * 
     * This would typically use a Web3 PHP library to interact with the blockchain.
     * For this implementation, we'll use a simplified approach with REST API calls.
     */
    private function get_user_achievements($wallet_address) {
        // In a real implementation, this would call the smart contract
        // using Web3.php or similar library
        $achievement_data = $this->make_blockchain_request('achievement', 'getAchievementsByOwner', [$wallet_address]);
        
        $achievements = [];
        
        if (!empty($achievement_data) && isset($achievement_data['result'])) {
            foreach ($achievement_data['result'] as $token_id) {
                // Get achievement metadata
                $token_uri = $this->make_blockchain_request('achievement', 'tokenURI', [$token_id]);
                $achievement_type = $this->make_blockchain_request('achievement', 'tokenAchievementType', [$token_id]);
                
                // Fetch metadata from IPFS or other storage
                $metadata = $this->fetch_token_metadata($token_uri['result']);
                
                $achievements[] = [
                    'id' => $token_id,
                    'name' => $metadata['name'] ?? 'Unknown Achievement',
                    'description' => $metadata['description'] ?? '',
                    'image' => $metadata['image'] ?? '',
                    'type_id' => $achievement_type['result'] ?? 0,
                    'earned_at' => $metadata['attributes']['earned_at'] ?? '',
                ];
            }
        }
        
        return $achievements;
    }
    
    /**
     * Get user reputation data.
     */
    private function get_user_reputation($wallet_address) {
        $reputation_data = $this->make_blockchain_request('reputation', 'getUserReputation', [$wallet_address]);
        
        $level_progress = $this->make_blockchain_request('reputation', 'getLevelProgress', [$wallet_address]);
        
        // Get contribution type points
        $contribution_types = [
            'artwork_creation' => 0,
            'artwork_purchase' => 1,
            'artwork_curation' => 2,
            'marketplace_engagement' => 3,
            'governance_participation' => 4,
            'community_moderation' => 5,
            'ai_collaboration' => 6,
            'blockchain_validation' => 7
        ];
        
        $contribution_details = [];
        
        foreach ($contribution_types as $type_name => $type_id) {
            $type_points = $this->make_blockchain_request(
                'reputation',
                'getUserContributionTypePoints',
                [$wallet_address, $type_id]
            );
            
            $contribution_details[$type_name] = $type_points['result'] ?? 0;
        }
        
        return [
            'total_points' => $reputation_data['result'][0] ?? 0,
            'contribution_points' => $reputation_data['result'][1] ?? 0,
            'achievement_points' => $reputation_data['result'][2] ?? 0,
            'level' => $reputation_data['result'][3] ?? 1,
            'level_progress' => $level_progress['result'] ?? 0,
            'contribution_details' => $contribution_details
        ];
    }
    
    /**
     * Get active governance proposals.
     */
    private function get_governance_proposals() {
        // In a real implementation, this would query past events and active proposals
        // from the governance contract
        
        // Placeholder implementation
        return [
            'active' => [],
            'pending' => [],
            'executed' => [],
            'defeated' => []
        ];
    }
    
    /**
     * Get user rewards.
     */
    private function get_user_rewards($wallet_address) {
        $total_rewards = $this->make_blockchain_request('rewards', 'getUserTotalRewards', [$wallet_address]);
        
        $reward_types = [
            'contribution_based' => 0,
            'achievement_based' => 1,
            'daily_activity' => 2,
            'content_creation' => 3,
            'marketplace_activity' => 4,
            'governance_activity' => 5,
            'ai_collaboration' => 6,
            'custom_challenge' => 7
        ];
        
        $rewards_by_type = [];
        
        foreach ($reward_types as $type_name => $type_id) {
            $type_rewards = $this->make_blockchain_request(
                'rewards',
                'getUserRewardsByType',
                [$wallet_address, $type_id]
            );
            
            $rewards_by_type[$type_name] = $type_rewards['result'] ?? 0;
        }
        
        return [
            'total_rewards' => $total_rewards['result'] ?? 0,
            'rewards_by_type' => $rewards_by_type
        ];
    }
    
    /**
     * Make a blockchain request.
     * 
     * In a real implementation, this would use Web3.php or similar library to call the contract.
     * For this example, we're mocking the behavior.
     */
    private function make_blockchain_request($contract_type, $method, $params = []) {
        // In a real implementation, this would use a Web3 library
        // to call the blockchain contract methods
        
        // For this example, we'll return mock data
        $mock_data = $this->get_mock_blockchain_data($contract_type, $method, $params);
        
        // Simulate blockchain delay
        usleep(200000); // 200ms delay
        
        return $mock_data;
    }
    
    /**
     * Get mock blockchain data for testing.
     */
    private function get_mock_blockchain_data($contract_type, $method, $params) {
        // This is just for demonstration purposes
        // In a real implementation, this wouldn't exist
        
        $wallet_address = $params[0] ?? '';
        
        switch ($contract_type) {
            case 'achievement':
                if ($method === 'getAchievementsByOwner') {
                    return [
                        'result' => [1, 2, 3]
                    ];
                } elseif ($method === 'tokenURI') {
                    return [
                        'result' => 'ipfs://QmXyZ123456789'
                    ];
                } elseif ($method === 'tokenAchievementType') {
                    return [
                        'result' => 1 // Achievement type ID
                    ];
                }
                break;
                
            case 'reputation':
                if ($method === 'getUserReputation') {
                    return [
                        'result' => [1000, 750, 250, 3] // total, contribution, achievement, level
                    ];
                } elseif ($method === 'getLevelProgress') {
                    return [
                        'result' => 65 // 65% to next level
                    ];
                } elseif ($method === 'getUserContributionTypePoints') {
                    $type_id = $params[1] ?? 0;
                    $points = [150, 200, 100, 75, 50, 25, 150, 0];
                    return [
                        'result' => $points[$type_id] ?? 0
                    ];
                }
                break;
                
            case 'rewards':
                if ($method === 'getUserTotalRewards') {
                    return [
                        'result' => 500 // 500 TOLA tokens
                    ];
                } elseif ($method === 'getUserRewardsByType') {
                    $type_id = $params[1] ?? 0;
                    $rewards = [100, 150, 50, 75, 25, 50, 50, 0];
                    return [
                        'result' => $rewards[$type_id] ?? 0
                    ];
                }
                break;
        }
        
        return ['result' => null];
    }
    
    /**
     * Fetch token metadata from IPFS or other storage.
     */
    private function fetch_token_metadata($uri) {
        // In a real implementation, this would fetch data from IPFS or other storage
        // For this example, we'll return mock data
        
        return [
            'name' => 'Community Contributor',
            'description' => 'Awarded for making valuable contributions to the VORTEX community.',
            'image' => 'https://example.com/images/community-contributor.png',
            'attributes' => [
                'earned_at' => date('Y-m-d H:i:s', time() - 86400), // 1 day ago
                'rarity' => 'Common',
                'points' => 100
            ]
        ];
    }
    
    /**
     * Handler for artwork creation action.
     */
    public function on_artwork_created($artwork_id, $user_id) {
        $user_wallet = $this->get_user_wallet($user_id);
        if (!$user_wallet) {
            return;
        }
        
        // Record contribution
        $this->queue_contribution_transaction(
            $user_wallet,
            0, // ContributionType.ArtworkCreation
            50, // points
            sprintf('Created artwork #%d', $artwork_id)
        );
        
        // Check for achievements
        $artwork_count = $this->get_user_artwork_count($user_id);
        
        if ($artwork_count === 1) {
            $this->queue_achievement_transaction(
                $user_wallet,
                1, // First Creation achievement type
                $this->generate_achievement_metadata('First Creation', $artwork_id)
            );
        } elseif ($artwork_count === 10) {
            $this->queue_achievement_transaction(
                $user_wallet,
                2, // Prolific Creator achievement type
                $this->generate_achievement_metadata('Prolific Creator', $artwork_id)
            );
        } elseif ($artwork_count === 50) {
            $this->queue_achievement_transaction(
                $user_wallet,
                3, // Master Creator achievement type
                $this->generate_achievement_metadata('Master Creator', $artwork_id)
            );
        }
        
        // Queue reward if eligible
        $this->queue_reward_transaction(
            $user_wallet,
            3, // RewardType.ContentCreation
            10, // 10 TOLA tokens
            sprintf('Artwork creation reward for #%d', $artwork_id)
        );
    }
    
    /**
     * Handler for artwork purchase action.
     */
    public function on_artwork_purchased($artwork_id, $buyer_id, $seller_id) {
        $buyer_wallet = $this->get_user_wallet($buyer_id);
        $seller_wallet = $this->get_user_wallet($seller_id);
        
        if ($buyer_wallet) {
            // Record buyer contribution
            $this->queue_contribution_transaction(
                $buyer_wallet,
                1, // ContributionType.ArtworkPurchase
                25, // points
                sprintf('Purchased artwork #%d', $artwork_id)
            );
            
            // Check for buyer achievements
            $purchase_count = $this->get_user_purchase_count($buyer_id);
            
            if ($purchase_count === 1) {
                $this->queue_achievement_transaction(
                    $buyer_wallet,
                    4, // First Purchase achievement type
                    $this->generate_achievement_metadata('First Purchase', $artwork_id)
                );
            } elseif ($purchase_count === 10) {
                $this->queue_achievement_transaction(
                    $buyer_wallet,
                    5, // Art Collector achievement type
                    $this->generate_achievement_metadata('Art Collector', $artwork_id)
                );
            }
            
            // Queue reward for buyer
            $this->queue_reward_transaction(
                $buyer_wallet,
                4, // RewardType.MarketplaceActivity
                5, // 5 TOLA tokens
                sprintf('Artwork purchase reward for #%d', $artwork_id)
            );
        }
        
        if ($seller_wallet) {
            // Record seller contribution
            $this->queue_contribution_transaction(
                $seller_wallet,
                1, // ContributionType.ArtworkPurchase (selling is also marketplace activity)
                15, // points
                sprintf('Sold artwork #%d', $artwork_id)
            );
            
            // Check for seller achievements
            $sale_count = $this->get_user_sale_count($seller_id);
            
            if ($sale_count === 1) {
                $this->queue_achievement_transaction(
                    $seller_wallet,
                    6, // First Sale achievement type
                    $this->generate_achievement_metadata('First Sale', $artwork_id)
                );
            } elseif ($sale_count === 10) {
                $this->queue_achievement_transaction(
                    $seller_wallet,
                    7, // Professional Artist achievement type
                    $this->generate_achievement_metadata('Professional Artist', $artwork_id)
                );
            }
            
            // Queue reward for seller
            $this->queue_reward_transaction(
                $seller_wallet,
                4, // RewardType.MarketplaceActivity
                3, // 3 TOLA tokens
                sprintf('Artwork sale reward for #%d', $artwork_id)
            );
        }
    }
    
    /**
     * Handler for content curation action.
     */
    public function on_user_curated_content($user_id, $content_id) {
        $user_wallet = $this->get_user_wallet($user_id);
        if (!$user_wallet) {
            return;
        }
        
        // Record contribution
        $this->queue_contribution_transaction(
            $user_wallet,
            2, // ContributionType.ArtworkCuration
            10, // points
            sprintf('Curated content #%d', $content_id)
        );
        
        // Check for achievements
        $curation_count = $this->get_user_curation_count($user_id);
        
        if ($curation_count === 10) {
            $this->queue_achievement_transaction(
                $user_wallet,
                8, // Curator achievement type
                $this->generate_achievement_metadata('Curator', $content_id)
            );
        }
        
        // Queue reward if eligible
        $this->queue_reward_transaction(
            $user_wallet,
            3, // RewardType.ContentCreation (curation is a form of content creation)
            2, // 2 TOLA tokens
            sprintf('Content curation reward for #%d', $content_id)
        );
    }
    
    /**
     * Get user wallet address.
     */
    private function get_user_wallet($user_id) {
        return get_user_meta($user_id, 'vortex_wallet_address', true);
    }
    
    /**
     * Get user artwork count.
     */
    private function get_user_artwork_count($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_artworks';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE creator_id = %d",
            $user_id
        ));
    }
    
    /**
     * Get user purchase count.
     */
    private function get_user_purchase_count($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_transactions';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE buyer_id = %d AND status = 'completed'",
            $user_id
        ));
    }
    
    /**
     * Get user sale count.
     */
    private function get_user_sale_count($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_transactions';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE seller_id = %d AND status = 'completed'",
            $user_id
        ));
    }
    
    /**
     * Get user curation count.
     */
    private function get_user_curation_count($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_curations';
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d",
            $user_id
        ));
    }
    
    /**
     * Generate achievement metadata.
     */
    private function generate_achievement_metadata($achievement_name, $content_id) {
        $metadata = [
            'name' => $achievement_name,
            'description' => sprintf('Earned for %s achievement in the VORTEX ecosystem.', strtolower($achievement_name)),
            'image' => sprintf('ipfs://QmAchievement%s', md5($achievement_name . $content_id)),
            'attributes' => [
                'earned_at' => date('Y-m-d H:i:s'),
                'content_id' => $content_id
            ]
        ];
        
        // In a real implementation, this would upload to IPFS
        // For now, we'll just return the metadata
        return json_encode($metadata);
    }
    
    /**
     * Queue a contribution transaction for blockchain processing.
     */
    private function queue_contribution_transaction($user_wallet, $contribution_type, $points, $details) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_blockchain_queue';
        
        $wpdb->insert(
            $table_name,
            [
                'transaction_type' => 'contribution',
                'wallet_address' => $user_wallet,
                'data' => json_encode([
                    'contribution_type' => $contribution_type,
                    'points' => $points,
                    'details' => $details
                ]),
                'created_at' => current_time('mysql'),
                'status' => 'pending'
            ]
        );
        
        // Trigger the background processor to handle the queue
        do_action('vortex_process_blockchain_queue');
    }
    
    /**
     * Queue an achievement transaction for blockchain processing.
     */
    private function queue_achievement_transaction($user_wallet, $achievement_type, $metadata) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_blockchain_queue';
        
        $wpdb->insert(
            $table_name,
            [
                'transaction_type' => 'achievement',
                'wallet_address' => $user_wallet,
                'data' => json_encode([
                    'achievement_type' => $achievement_type,
                    'metadata' => $metadata
                ]),
                'created_at' => current_time('mysql'),
                'status' => 'pending'
            ]
        );
        
        // Trigger the background processor to handle the queue
        do_action('vortex_process_blockchain_queue');
    }
    
    /**
     * Queue a reward transaction for blockchain processing.
     */
    private function queue_reward_transaction($user_wallet, $reward_type, $amount, $metadata) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_blockchain_queue';
        
        $wpdb->insert(
            $table_name,
            [
                'transaction_type' => 'reward',
                'wallet_address' => $user_wallet,
                'data' => json_encode([
                    'reward_type' => $reward_type,
                    'amount' => $amount,
                    'metadata' => $metadata
                ]),
                'created_at' => current_time('mysql'),
                'status' => 'pending'
            ]
        );
        
        // Trigger the background processor to handle the queue
        do_action('vortex_process_blockchain_queue');
    }
}

// Include widget classes
require_once plugin_dir_path(__FILE__) . 'widgets/class-vortex-achievement-widget.php';
require_once plugin_dir_path(__FILE__) . 'widgets/class-vortex-reputation-widget.php';
require_once plugin_dir_path(__FILE__) . 'widgets/class-vortex-governance-widget.php';
require_once plugin_dir_path(__FILE__) . 'widgets/class-vortex-rewards-widget.php';

// Initialize the DAO integration
function vortex_dao_integration() {
    return VORTEX_DAO_Integration::get_instance();
}
add_action('plugins_loaded', 'vortex_dao_integration'); 