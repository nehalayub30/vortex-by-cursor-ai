<?php
/**
 * VORTEX DAO Governance
 *
 * Handles DAO governance, proposals, and voting
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class VORTEX_DAO_Governance {
    
    private static $instance = null;
    
    /**
     * Get class instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Register AJAX handlers
        add_action('wp_ajax_vortex_get_proposals', array($this, 'ajax_get_proposals'));
        add_action('wp_ajax_nopriv_vortex_get_proposals', array($this, 'ajax_get_proposals'));
        
        add_action('wp_ajax_vortex_get_proposal_details', array($this, 'ajax_get_proposal_details'));
        add_action('wp_ajax_nopriv_vortex_get_proposal_details', array($this, 'ajax_get_proposal_details'));
        
        add_action('wp_ajax_vortex_submit_vote', array($this, 'ajax_submit_vote'));
        add_action('wp_ajax_nopriv_vortex_submit_vote', array($this, 'ajax_submit_vote'));
        
        add_action('wp_ajax_vortex_founder_veto', array($this, 'ajax_founder_veto'));
        add_action('wp_ajax_nopriv_vortex_founder_veto', array($this, 'ajax_founder_veto'));
        
        // Register shortcodes
        add_shortcode('vortex_dao_governance', array($this, 'governance_shortcode'));
    }
    
    /**
     * AJAX: Get proposals
     */
    public function ajax_get_proposals() {
        // Verify nonce
        check_ajax_referer('vortex_governance_nonce', 'nonce');
        
        // Get request parameters
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'all';
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        
        // Get proposals
        $proposals = $this->get_proposals($status, $limit, $offset);
        
        // Count total proposals for pagination
        global $wpdb;
        $where = '';
        if ($status !== 'all') {
            $where = $wpdb->prepare("WHERE status = %s", $status);
        }
        
        $total_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vortex_dao_proposals $where");
        
        wp_send_json_success(array(
            'proposals' => $proposals,
            'total' => intval($total_count)
        ));
    }
    
    /**
     * AJAX: Get proposal details
     */
    public function ajax_get_proposal_details() {
        // Verify nonce
        check_ajax_referer('vortex_governance_nonce', 'nonce');
        
        // Get proposal ID
        $proposal_id = isset($_POST['proposal_id']) ? intval($_POST['proposal_id']) : 0;
        
        if (!$proposal_id) {
            wp_send_json_error(array('message' => 'Invalid proposal ID.'));
            return;
        }
        
        // Get proposal details
        $details = $this->get_proposal_details($proposal_id);
        
        if (!$details) {
            wp_send_json_error(array('message' => 'Proposal not found.'));
            return;
        }
        
        wp_send_json_success(array('details' => $details));
    }
    
    /**
     * AJAX: Submit vote
     */
    public function ajax_submit_vote() {
        // Verify nonce
        check_ajax_referer('vortex_governance_nonce', 'nonce');
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'You must be logged in to vote.'));
            return;
        }
        
        // Get form data
        $proposal_id = isset($_POST['proposal_id']) ? intval($_POST['proposal_id']) : 0;
        $vote = isset($_POST['vote']) ? sanitize_text_field($_POST['vote']) : '';
        $wallet_address = isset($_POST['wallet_address']) ? sanitize_text_field($_POST['wallet_address']) : '';
        
        // Validate required fields
        if (!$proposal_id || empty($vote) || empty($wallet_address)) {
            wp_send_json_error(array('message' => 'All required fields must be filled.'));
            return;
        }
        
        // Submit vote
        $result = $this->submit_vote(
            get_current_user_id(),
            $proposal_id,
            $wallet_address,
            $vote
        );
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * AJAX: Founder veto
     */
    public function ajax_founder_veto() {
        // Verify nonce
        check_ajax_referer('vortex_governance_nonce', 'nonce');
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'You must be logged in to veto a proposal.'));
            return;
        }
        
        // Get form data
        $proposal_id = isset($_POST['proposal_id']) ? intval($_POST['proposal_id']) : 0;
        $reason = isset($_POST['reason']) ? sanitize_text_field($_POST['reason']) : '';
        
        // Validate required fields
        if (!$proposal_id || empty($reason)) {
            wp_send_json_error(array('message' => 'All required fields must be filled.'));
            return;
        }
        
        // Apply veto
        $result = $this->founder_veto(
            get_current_user_id(),
            $proposal_id,
            $reason
        );
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Render governance dashboard shortcode
     */
    public function governance_shortcode($atts) {
        $atts = shortcode_atts(array(
            'view' => 'proposals', // proposals, create, details
            'proposal_id' => 0,
            'status' => 'active' // active, approved, rejected, vetoed, executed, all
        ), $atts);
        
        // Enqueue necessary scripts and styles
        wp_enqueue_style('vortex-dao-governance');
        wp_enqueue_script('vortex-dao-governance');
        
        // Get current view
        $view = $atts['view'];
        $proposal_id = intval($atts['proposal_id']);
        $status = $atts['status'];
        
        // Override with query parameters if present
        if (isset($_GET['view'])) {
            $view = sanitize_text_field($_GET['view']);
        }
        
        if (isset($_GET['proposal_id'])) {
            $proposal_id = intval($_GET['proposal_id']);
        }
        
        if (isset($_GET['status'])) {
            $status = sanitize_text_field($_GET['status']);
        }
        
        // Start output buffer
        ob_start();
        
        // Include template based on view
        switch ($view) {
            case 'create':
                $this->render_create_proposal_form();
                break;
                
            case 'details':
                if ($proposal_id) {
                    $this->render_proposal_details($proposal_id);
                } else {
                    echo '<div class="vortex-error-message">Invalid proposal ID.</div>';
                }
                break;
                
            case 'proposals':
            default:
                $this->render_proposals_list($status);
                break;
        }
        
        return ob_get_clean();
    }
    
    /**
     * Render proposals list
     */
    private function render_proposals_list($status = 'active') {
        // Get proposals
        $proposals = $this->get_proposals($status, 10, 0);
        
        // Load template
        include(VORTEX_PLUGIN_DIR . 'public/partials/vortex-dao-proposals.php');
    }
    
    /**
     * Render create proposal form
     */
    private function render_create_proposal_form() {
        // Check if user is logged in
        if (!is_user_logged_in()) {
            echo '<div class="vortex-error-message">You must be logged in to create a proposal.</div>';
            return;
        }
        
        // Get user's wallets
        global $wpdb;
        $wallets = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vortex_wallet_addresses WHERE user_id = %d AND verified = 1",
            get_current_user_id()
        ));
        
        if (empty($wallets)) {
            echo '<div class="vortex-error-message">You need to connect and verify a wallet to create a proposal.</div>';
            return;
        }
        
        // Load template
        include(VORTEX_PLUGIN_DIR . 'public/partials/vortex-dao-create-proposal.php');
    }
    
    /**
     * Render proposal details
     */
    private function render_proposal_details($proposal_id) {
        // Get proposal details
        $details = $this->get_proposal_details($proposal_id);
        
        if (!$details) {
            echo '<div class="vortex-error-message">Proposal not found.</div>';
            return;
        }
        
        // Get user's wallets if logged in
        $wallets = array();
        if (is_user_logged_in()) {
            global $wpdb;
            $wallets = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vortex_wallet_addresses WHERE user_id = %d AND verified = 1",
                get_current_user_id()
            ));
        }
        
        // Load template
        include(VORTEX_PLUGIN_DIR . 'public/partials/vortex-dao-proposal-details.php');
    }
    
    /**
     * Create required database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Proposals table
        $table_proposals = $wpdb->prefix . 'vortex_dao_proposals';
        $sql_proposals = "CREATE TABLE $table_proposals (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            proposer_address varchar(255) NOT NULL,
            title varchar(255) NOT NULL,
            description text NOT NULL,
            proposal_type varchar(50) NOT NULL,
            action_data text DEFAULT NULL,
            voting_end_date datetime NOT NULL,
            execution_time datetime DEFAULT NULL,
            for_votes decimal(18,8) NOT NULL DEFAULT 0,
            against_votes decimal(18,8) NOT NULL DEFAULT 0,
            abstain_votes decimal(18,8) NOT NULL DEFAULT 0,
            total_votes decimal(18,8) NOT NULL DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'active',
            executed tinyint(1) NOT NULL DEFAULT 0,
            execution_result text DEFAULT NULL,
            veto_reason text DEFAULT NULL,
            vetoed_by bigint(20) DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY status (status),
            KEY voting_end_date (voting_end_date)
        ) $charset_collate;";
        
        // Votes table
        $table_votes = $wpdb->prefix . 'vortex_dao_votes';
        $sql_votes = "CREATE TABLE $table_votes (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            proposal_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            wallet_address varchar(255) NOT NULL,
            vote varchar(20) NOT NULL,
            vote_weight decimal(18,8) NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY proposal_wallet (proposal_id, wallet_address),
            KEY proposal_id (proposal_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        // Governance logs table
        $table_logs = $wpdb->prefix . 'vortex_dao_governance_logs';
        $sql_logs = "CREATE TABLE $table_logs (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            proposal_id bigint(20) NOT NULL,
            action_type varchar(50) NOT NULL,
            action_data text DEFAULT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY proposal_id (proposal_id),
            KEY action_type (action_type),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Grants table
        $table_grants = $wpdb->prefix . 'vortex_dao_grants';
        $sql_grants = "CREATE TABLE $table_grants (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            proposal_id bigint(20) NOT NULL,
            recipient varchar(255) NOT NULL,
            amount decimal(18,8) NOT NULL,
            purpose text NOT NULL,
            transaction_signature varchar(255) NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY proposal_id (proposal_id),
            KEY recipient (recipient)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_proposals);
        dbDelta($sql_votes);
        dbDelta($sql_logs);
        dbDelta($sql_grants);
    }
    
    /**
     * Execute treasury proposal
     */
    private function execute_treasury_proposal($proposal_id, $action_data) {
        // Verify action data
        if (!isset($action_data['recipient']) || !isset($action_data['amount'])) {
            return array(
                'success' => false,
                'message' => 'Invalid treasury proposal data.'
            );
        }
        
        $recipient = $action_data['recipient'];
        $amount = floatval($action_data['amount']);
        
        // Execute treasury transfer using Solana API
        $transfer_result = $this->solana_api->transfer_tokens(
            get_option('vortex_dao_treasury_address'),
            $recipient,
            $amount,
            'treasury_proposal_' . $proposal_id
        );
        
        if (!$transfer_result['success']) {
            return array(
                'success' => false,
                'message' => 'Treasury transfer failed: ' . $transfer_result['message']
            );
        }
        
        // Log the transfer
        $this->token->log_token_transfer(
            get_option('vortex_dao_treasury_address'),
            $recipient,
            $amount,
            $transfer_result['transaction_signature'],
            'treasury_transfer',
            array(
                'proposal_id' => $proposal_id,
                'purpose' => isset($action_data['purpose']) ? $action_data['purpose'] : 'Treasury proposal execution'
            )
        );
        
        return array(
            'success' => true,
            'message' => sprintf('Successfully transferred %s TOLA to %s. Transaction: %s', 
                number_format($amount, 2), 
                $recipient,
                $transfer_result['transaction_signature']
            )
        );
    }
    
    /**
     * Execute parameter proposal
     */
    private function execute_parameter_proposal($proposal_id, $action_data) {
        // Verify action data
        if (!isset($action_data['parameter']) || !isset($action_data['value'])) {
            return array(
                'success' => false,
                'message' => 'Invalid parameter proposal data.'
            );
        }
        
        $parameter = $action_data['parameter'];
        $value = $action_data['value'];
        
        // Validate parameter
        $valid_parameters = array(
            'min_proposal_tokens',
            'quorum_percentage',
            'proposal_threshold',
            'voting_period_days',
            'founder_vote_multiplier',
            'founder_veto_enabled',
            'voting_cap_enabled',
            'max_voting_weight'
        );
        
        if (!in_array($parameter, $valid_parameters)) {
            return array(
                'success' => false,
                'message' => 'Invalid parameter: ' . $parameter
            );
        }
        
        // Update the parameter
        update_option('vortex_dao_' . $parameter, $value);
        
        // Update instance property if exists
        if (property_exists($this, $parameter)) {
            $this->{$parameter} = $value;
        }
        
        return array(
            'success' => true,
            'message' => sprintf('Successfully updated %s to %s', $parameter, $value)
        );
    }
    
    /**
     * Execute upgrade proposal
     */
    private function execute_upgrade_proposal($proposal_id, $action_data) {
        // Verify action data
        if (!isset($action_data['upgrade_type']) || !isset($action_data['target_version'])) {
            return array(
                'success' => false,
                'message' => 'Invalid upgrade proposal data.'
            );
        }
        
        $upgrade_type = $action_data['upgrade_type'];
        $target_version = $action_data['target_version'];
        
        // Different upgrade types need different handling
        switch ($upgrade_type) {
            case 'plugin_upgrade':
                // This is potentially dangerous and should require manual intervention
                update_option('vortex_pending_upgrade', array(
                    'proposal_id' => $proposal_id,
                    'type' => 'plugin_upgrade',
                    'target_version' => $target_version,
                    'approved_time' => current_time('mysql')
                ));
                
                return array(
                    'success' => true,
                    'message' => sprintf('Upgrade to version %s approved. Manual upgrade by admin required.', $target_version)
                );
                
            case 'contract_upgrade':
                // This should also require manual intervention for security
                update_option('vortex_pending_contract_upgrade', array(
                    'proposal_id' => $proposal_id,
                    'target_version' => $target_version,
                    'approved_time' => current_time('mysql')
                ));
                
                return array(
                    'success' => true,
                    'message' => sprintf('Contract upgrade to version %s approved. Manual upgrade required.', $target_version)
                );
                
            default:
                return array(
                    'success' => false,
                    'message' => 'Unknown upgrade type: ' . $upgrade_type
                );
        }
    }
    
    /**
     * Execute grant proposal
     */
    private function execute_grant_proposal($proposal_id, $action_data) {
        // Verify action data
        if (!isset($action_data['recipient']) || !isset($action_data['amount']) || !isset($action_data['purpose'])) {
            return array(
                'success' => false,
                'message' => 'Invalid grant proposal data.'
            );
        }
        
        $recipient = $action_data['recipient'];
        $amount = floatval($action_data['amount']);
        $purpose = $action_data['purpose'];
        
        // Execute grant transfer using Solana API
        $transfer_result = $this->solana_api->transfer_tokens(
            get_option('vortex_dao_treasury_address'),
            $recipient,
            $amount,
            'grant_proposal_' . $proposal_id
        );
        
        if (!$transfer_result['success']) {
            return array(
                'success' => false,
                'message' => 'Grant transfer failed: ' . $transfer_result['message']
            );
        }
        
        // Log the grant in grants table
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'vortex_dao_grants',
            array(
                'proposal_id' => $proposal_id,
                'recipient' => $recipient,
                'amount' => $amount,
                'purpose' => $purpose,
                'transaction_signature' => $transfer_result['transaction_signature'],
                'created_at' => current_time('mysql')
            )
        );
        
        // Log the token transfer
        $this->token->log_token_transfer(
            get_option('vortex_dao_treasury_address'),
            $recipient,
            $amount,
            $transfer_result['transaction_signature'],
            'grant_transfer',
            array(
                'proposal_id' => $proposal_id,
                'purpose' => $purpose
            )
        );
        
        return array(
            'success' => true,
            'message' => sprintf('Successfully granted %s TOLA to %s for %s. Transaction: %s', 
                number_format($amount, 2), 
                $recipient,
                $purpose,
                $transfer_result['transaction_signature']
            )
        );
    }
    
    /**
     * Execute marketplace proposal
     */
    private function execute_marketplace_proposal($proposal_id, $action_data) {
        // Verify action data
        if (!isset($action_data['marketplace_action'])) {
            return array(
                'success' => false,
                'message' => 'Invalid marketplace proposal data.'
            );
        }
        
        $marketplace_action = $action_data['marketplace_action'];
        
        // Different marketplace actions need different handling
        switch ($marketplace_action) {
            case 'fee_update':
                if (!isset($action_data['new_fee_percentage'])) {
                    return array(
                        'success' => false,
                        'message' => 'Missing new fee percentage value.'
                    );
                }
                
                $new_fee = floatval($action_data['new_fee_percentage']);
                
                // Validate fee percentage
                if ($new_fee < 0 || $new_fee > 20) {
                    return array(
                        'success' => false,
                        'message' => 'Invalid fee percentage. Must be between 0 and 20.'
                    );
                }
                
                // Update marketplace fee
                update_option('vortex_marketplace_fee_percentage', $new_fee);
                
                return array(
                    'success' => true,
                    'message' => sprintf('Successfully updated marketplace fee to %s%%', $new_fee)
                );
                
            case 'artist_verification_requirement':
                if (!isset($action_data['require_verification'])) {
                    return array(
                        'success' => false,
                        'message' => 'Missing verification requirement value.'
                    );
                }
                
                $require_verification = boolval($action_data['require_verification']);
                
                // Update artist verification requirement
                update_option('vortex_require_artist_verification', $require_verification);
                
                return array(
                    'success' => true,
                    'message' => sprintf('Successfully %s artist verification requirement.', 
                        $require_verification ? 'enabled' : 'disabled'
                    )
                );
                
            case 'featured_artists_update':
                if (!isset($action_data['artist_ids']) || !is_array($action_data['artist_ids'])) {
                    return array(
                        'success' => false,
                        'message' => 'Missing or invalid artist IDs list.'
                    );
                }
                
                $artist_ids = array_map('intval', $action_data['artist_ids']);
                
                // Update featured artists
                update_option('vortex_featured_artists', $artist_ids);
                
                return array(
                    'success' => true,
                    'message' => sprintf('Successfully updated featured artists list with %d artists.', 
                        count($artist_ids)
                    )
                );
                
            default:
                return array(
                    'success' => false,
                    'message' => 'Unknown marketplace action: ' . $marketplace_action
                );
        }
    }
    
    /**
     * Get total token supply
     */
    private function get_total_token_supply() {
        $total_supply = $this->solana_api->get_token_supply($this->token->token_address);
        
        if ($total_supply === false) {
            // Fallback to hardcoded value if API fails
            $total_supply = get_option('vortex_dao_total_supply', 10000000); // Default 10M
        }
        
        return $total_supply;
    }
    
    /**
     * Log a governance action
     *
     * @param string $action_type The type of governance action
     * @param array  $data Additional data about the action
     * @return int|false The ID of the new log entry, or false on failure
     */
    public function log_governance_action($action_type, $data) {
        global $wpdb;
        
        // Ensure user is logged in
        if (!is_user_logged_in()) {
            return false;
        }
        
        $user_id = get_current_user_id();
        $wallet_address = $this->get_user_wallet_address($user_id);
        
        if (!$wallet_address) {
            return false;
        }
        
        // Prepare log data
        $log_data = array(
            'action_type' => sanitize_text_field($action_type),
            'user_id' => $user_id,
            'wallet_address' => $wallet_address,
            'action_data' => wp_json_encode($data),
            'status' => isset($data['status']) ? sanitize_text_field($data['status']) : 'pending',
            'timestamp' => current_time('mysql', true)
        );
        
        // Add optional fields if present
        if (isset($data['proposal_id'])) {
            $log_data['proposal_id'] = intval($data['proposal_id']);
        }
        if (isset($data['token_amount'])) {
            $log_data['token_amount'] = floatval($data['token_amount']);
        }
        if (isset($data['transaction_hash'])) {
            $log_data['transaction_hash'] = sanitize_text_field($data['transaction_hash']);
        }
        if (isset($data['block_number'])) {
            $log_data['block_number'] = intval($data['block_number']);
        }
        
        // Insert log entry
        $result = $wpdb->insert(
            $wpdb->prefix . 'vortex_dao_governance_logs',
            $log_data,
            array(
                '%s', // action_type
                '%d', // user_id
                '%s', // wallet_address
                '%s', // action_data
                '%s', // status
                '%s'  // timestamp
            )
        );
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get governance logs with filtering options
     *
     * @param array $args Query arguments
     * @return array Array of log entries
     */
    public function get_governance_logs($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'action_type' => '',
            'proposal_id' => 0,
            'user_id' => 0,
            'wallet_address' => '',
            'status' => '',
            'from_date' => '',
            'to_date' => '',
            'orderby' => 'timestamp',
            'order' => 'DESC',
            'limit' => 50,
            'offset' => 0
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array('1=1');
        $values = array();
        
        // Build WHERE clause based on arguments
        if (!empty($args['action_type'])) {
            $where[] = 'action_type = %s';
            $values[] = $args['action_type'];
        }
        
        if (!empty($args['proposal_id'])) {
            $where[] = 'proposal_id = %d';
            $values[] = $args['proposal_id'];
        }
        
        if (!empty($args['user_id'])) {
            $where[] = 'user_id = %d';
            $values[] = $args['user_id'];
        }
        
        if (!empty($args['wallet_address'])) {
            $where[] = 'wallet_address = %s';
            $values[] = $args['wallet_address'];
        }
        
        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }
        
        if (!empty($args['from_date'])) {
            $where[] = 'timestamp >= %s';
            $values[] = $args['from_date'];
        }
        
        if (!empty($args['to_date'])) {
            $where[] = 'timestamp <= %s';
            $values[] = $args['to_date'];
        }
        
        // Build query
        $query = "SELECT * FROM {$wpdb->prefix}vortex_dao_governance_logs 
                  WHERE " . implode(' AND ', $where) . "
                  ORDER BY {$args['orderby']} {$args['order']}
                  LIMIT %d OFFSET %d";
        
        $values[] = $args['limit'];
        $values[] = $args['offset'];
        
        // Execute query
        $logs = $wpdb->get_results(
            $wpdb->prepare($query, $values),
            ARRAY_A
        );
        
        // Process logs
        foreach ($logs as &$log) {
            $log['action_data'] = json_decode($log['action_data'], true);
            $log['user_display_name'] = get_user_by('id', $log['user_id'])->display_name;
            $log['time_ago'] = human_time_diff(strtotime($log['timestamp']), current_time('timestamp')) . ' ago';
        }
        
        return $logs;
    }
    
    /**
     * Update governance log status
     *
     * @param int    $log_id The ID of the log entry
     * @param string $status New status
     * @param array  $additional_data Additional data to update
     * @return bool Success or failure
     */
    public function update_governance_log_status($log_id, $status, $additional_data = array()) {
        global $wpdb;
        
        $update_data = array(
            'status' => sanitize_text_field($status)
        );
        
        $update_format = array('%s');
        
        // Add any additional data
        if (!empty($additional_data['transaction_hash'])) {
            $update_data['transaction_hash'] = sanitize_text_field($additional_data['transaction_hash']);
            $update_format[] = '%s';
        }
        
        if (!empty($additional_data['block_number'])) {
            $update_data['block_number'] = intval($additional_data['block_number']);
            $update_format[] = '%d';
        }
        
        // Update the log entry
        $result = $wpdb->update(
            $wpdb->prefix . 'vortex_dao_governance_logs',
            $update_data,
            array('id' => $log_id),
            $update_format,
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Get proposals
     */
    public function get_proposals($status = 'all', $limit = 10, $offset = 0) {
        global $wpdb;
        
        $where = '';
        if ($status !== 'all') {
            $where = $wpdb->prepare("WHERE status = %s", $status);
        }
        
        $proposals = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}vortex_dao_proposals 
            $where 
            ORDER BY created_at DESC 
            LIMIT $limit OFFSET $offset"
        );
        
        // Enhance proposals with additional information
        foreach ($proposals as &$proposal) {
            // Get proposer username
            $user = get_user_by('id', $proposal->user_id);
            $proposal->proposer_name = $user ? $user->display_name : 'Unknown';
            
            // Parse action data
            $proposal->action_data_parsed = json_decode($proposal->action_data, true);
            
            // Calculate voting progress percentage
            $proposal->approval_percentage = 0;
            if ($proposal->total_votes > 0) {
                $proposal->approval_percentage = ($proposal->for_votes / $proposal->total_votes) * 100;
            }
            
            // Calculate time remaining for active proposals
            $proposal->time_remaining = '';
            if ($proposal->status === 'active') {
                $end_time = strtotime($proposal->voting_end_date);
                $now = time();
                $time_diff = $end_time - $now;
                
                if ($time_diff > 0) {
                    $days = floor($time_diff / (60 * 60 * 24));
                    $hours = floor(($time_diff % (60 * 60 * 24)) / (60 * 60));
                    $proposal->time_remaining = $days > 0 ? "$days days, $hours hours" : "$hours hours";
                } else {
                    $proposal->time_remaining = "Ended";
                }
            }
        }
        
        return $proposals;
    }
    
    /**
     * Get proposal details
     */
    public function get_proposal_details($proposal_id) {
        global $wpdb;
        
        // Get proposal data
        $proposal = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vortex_dao_proposals WHERE id = %d",
            $proposal_id
        ));
        
        if (!$proposal) {
            return false;
        }
        
        // Get proposer info
        $proposer = get_user_by('id', $proposal->user_id);
        $proposer_name = $proposer ? $proposer->display_name : 'Unknown';
        $proposer_avatar = $proposer ? get_avatar_url($proposer->ID) : '';
        
        // Get votes
        $votes = $wpdb->get_results($wpdb->prepare(
            "SELECT v.*, u.display_name 
            FROM {$wpdb->prefix}vortex_dao_votes v 
            LEFT JOIN {$wpdb->users} u ON v.user_id = u.ID
            WHERE v.proposal_id = %d 
            ORDER BY v.vote_weight DESC",
            $proposal_id
        ));
        
        // Format votes with wallet info
        foreach ($votes as &$vote) {
            // Shorten wallet address for display
            $vote->wallet_short = substr($vote->wallet_address, 0, 6) . '...' . substr($vote->wallet_address, -4);
            
            // Get user avatar if available
            $vote->avatar = $vote->user_id ? get_avatar_url($vote->user_id, array('size' => 32)) : '';
            
            // Format vote weight
            $vote->vote_weight_formatted = number_format($vote->vote_weight, 2);
            
            // Add vote time in human readable format
            $vote->vote_time = human_time_diff(strtotime($vote->created_at), time()) . ' ago';
        }
        
        // Get total token supply to calculate quorum
        $total_supply = $this->get_total_token_supply();
        
        // Calculate quorum threshold
        $quorum_threshold = $total_supply * ($this->quorum_percentage / 100);
        
        // Calculate approval percentage
        $approval_percentage = 0;
        if ($proposal->total_votes > 0) {
            $approval_percentage = ($proposal->for_votes / $proposal->total_votes) * 100;
        }
        
        // Format action data for display
        $action_data = json_decode($proposal->action_data, true);
        $action_summary = $this->format_action_data_for_display($proposal->proposal_type, $action_data);
        
        // Get vetoer info if applicable
        $vetoer_name = '';
        $vetoer_avatar = '';
        if ($proposal->status === 'vetoed' && $proposal->vetoed_by) {
            $vetoer = get_user_by('id', $proposal->vetoed_by);
            if ($vetoer) {
                $vetoer_name = $vetoer->display_name;
                $vetoer_avatar = get_avatar_url($vetoer->ID);
            }
        }
        
        // Get voting progress info
        $voting_progress = array(
            'for_percentage' => $proposal->total_votes > 0 ? ($proposal->for_votes / $proposal->total_votes) * 100 : 0,
            'against_percentage' => $proposal->total_votes > 0 ? ($proposal->against_votes / $proposal->total_votes) * 100 : 0,
            'abstain_percentage' => $proposal->total_votes > 0 ? ($proposal->abstain_votes / $proposal->total_votes) * 100 : 0,
            'for_votes' => $proposal->for_votes,
            'against_votes' => $proposal->against_votes,
            'abstain_votes' => $proposal->abstain_votes,
            'total_votes' => $proposal->total_votes,
            'quorum_percentage' => $this->quorum_percentage,
            'quorum_progress' => $total_supply > 0 ? ($proposal->total_votes / $total_supply) * 100 : 0,
            'quorum_met' => $proposal->total_votes >= $quorum_threshold,
            'approval_threshold' => $this->proposal_threshold,
            'approval_met' => $approval_percentage >= $this->proposal_threshold
        );
        
        // Get time information
        $time_info = array(
            'created' => $proposal->created_at,
            'created_human' => human_time_diff(strtotime($proposal->created_at), time()) . ' ago',
            'ends' => $proposal->voting_end_date,
            'ended' => strtotime($proposal->voting_end_date) < time(),
            'remaining' => ''
        );
        
        if ($proposal->status === 'active' && strtotime($proposal->voting_end_date) > time()) {
            $time_diff = strtotime($proposal->voting_end_date) - time();
            $days = floor($time_diff / (60 * 60 * 24));
            $hours = floor(($time_diff % (60 * 60 * 24)) / (60 * 60));
            $time_info['remaining'] = $days > 0 ? "$days days, $hours hours remaining" : "$hours hours remaining";
        }
        
        // Combine all data
        $proposal_details = array(
            'proposal' => $proposal,
            'proposer_name' => $proposer_name,
            'proposer_avatar' => $proposer_avatar,
            'votes' => $votes,
            'vote_count' => count($votes),
            'total_supply' => $total_supply,
            'quorum_threshold' => $quorum_threshold,
            'action_data' => $action_data,
            'action_summary' => $action_summary,
            'vetoer_name' => $vetoer_name,
            'vetoer_avatar' => $vetoer_avatar,
            'voting_progress' => $voting_progress,
            'time_info' => $time_info
        );
        
        return $proposal_details;
    }
    
    /**
     * Format action data for user-friendly display
     */
    private function format_action_data_for_display($proposal_type, $action_data) {
        $summary = '';
        
        switch ($proposal_type) {
            case 'treasury':
                $amount = isset($action_data['amount']) ? floatval($action_data['amount']) : 0;
                $recipient = isset($action_data['recipient']) ? $action_data['recipient'] : 'Unknown';
                $recipient_short = substr($recipient, 0, 6) . '...' . substr($recipient, -4);
                $purpose = isset($action_data['purpose']) ? $action_data['purpose'] : '';
                
                $summary = sprintf(
                    'Transfer %s TOLA to %s%s',
                    number_format($amount, 2),
                    $recipient_short,
                    $purpose ? " for: $purpose" : ''
                );
                break;
                
            case 'parameter':
                $parameter = isset($action_data['parameter']) ? $action_data['parameter'] : 'Unknown';
                $value = isset($action_data['value']) ? $action_data['value'] : '';
                
                // Make parameter name more readable
                $parameter_display = str_replace('_', ' ', $parameter);
                $parameter_display = ucwords($parameter_display);
                
                $summary = sprintf('Update %s to %s', $parameter_display, $value);
                break;
                
            case 'upgrade':
                $upgrade_type = isset($action_data['upgrade_type']) ? $action_data['upgrade_type'] : 'Unknown';
                $target_version = isset($action_data['target_version']) ? $action_data['target_version'] : '';
                
                $summary = sprintf('Upgrade %s to version %s', 
                    $upgrade_type === 'plugin_upgrade' ? 'VORTEX plugin' : 'smart contract',
                    $target_version
                );
                break;
                
            case 'grant':
                $amount = isset($action_data['amount']) ? floatval($action_data['amount']) : 0;
                $recipient = isset($action_data['recipient']) ? $action_data['recipient'] : 'Unknown';
                $recipient_short = substr($recipient, 0, 6) . '...' . substr($recipient, -4);
                $purpose = isset($action_data['purpose']) ? $action_data['purpose'] : '';
                
                $summary = sprintf(
                    'Grant %s TOLA to %s%s',
                    number_format($amount, 2),
                    $recipient_short,
                    $purpose ? " for: $purpose" : ''
                );
                break;
                
            case 'marketplace':
                $marketplace_action = isset($action_data['marketplace_action']) ? $action_data['marketplace_action'] : 'Unknown';
                
                switch ($marketplace_action) {
                    case 'fee_update':
                        $new_fee = isset($action_data['new_fee_percentage']) ? floatval($action_data['new_fee_percentage']) : 0;
                        $summary = sprintf('Update marketplace fee to %s%%', $new_fee);
                        break;
                        
                    case 'artist_verification_requirement':
                        $require_verification = isset($action_data['require_verification']) ? (bool)$action_data['require_verification'] : false;
                        $summary = sprintf('%s artist verification requirement', 
                            $require_verification ? 'Enable' : 'Disable'
                        );
                        break;
                        
                    case 'featured_artists_update':
                        $artist_count = isset($action_data['artist_ids']) ? count($action_data['artist_ids']) : 0;
                        $summary = sprintf('Update featured artists list with %d artists', $artist_count);
                        break;
                        
                    default:
                        $summary = 'Unknown marketplace action';
                        break;
                }
                break;
                
            default:
                $summary = 'Generic DAO proposal';
                break;
        }
        
        return $summary;
    }
    
    /**
     * Check if user has voted on a proposal
     */
    public function has_user_voted($proposal_id, $user_id) {
        global $wpdb;
        
        $vote = $wpdb->get_var($wpdb->prepare(
            "SELECT vote FROM {$wpdb->prefix}vortex_dao_votes 
            WHERE proposal_id = %d AND user_id = %d",
            $proposal_id, $user_id
        ));
        
        return $vote ? $vote : false;
    }
    
    /**
     * Get user's wallet for voting
     */
    public function get_user_voting_wallet($user_id) {
        global $wpdb;
        
        $wallet = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vortex_wallet_addresses 
            WHERE user_id = %d AND verified = 1 
            ORDER BY is_primary DESC, token_balance DESC 
            LIMIT 1",
            $user_id
        ));
        
        return $wallet;
    }
    
    /**
     * Add governance data for AI agents
     */
    public function add_governance_data_for_ai($data) {
        // Add recent proposals
        $recent_proposals = $this->get_proposals('all', 5, 0);
        $formatted_proposals = array();
        
        foreach ($recent_proposals as $proposal) {
            $formatted_proposals[] = array(
                'id' => $proposal->id,
                'title' => $proposal->title,
                'type' => $proposal->proposal_type,
                'status' => $proposal->status,
                'approval_percentage' => $proposal->approval_percentage,
                'created_at' => $proposal->created_at
            );
        }
        
        $data['dao_governance'] = array(
            'recent_proposals' => $formatted_proposals,
            'active_proposal_count' => $this->get_proposal_count('active'),
            'completed_proposal_count' => $this->get_proposal_count('all') - $this->get_proposal_count('active'),
            'governance_settings' => array(
                'min_proposal_tokens' => $this->min_proposal_tokens,
                'quorum_percentage' => $this->quorum_percentage,
                'proposal_threshold' => $this->proposal_threshold,
                'voting_period_days' => $this->voting_period_days
            )
        );
        
        return $data;
    }
    
    /**
     * Get proposal count by status
     */
    private function get_proposal_count($status = 'all') {
        global $wpdb;
        
        $where = '';
        if ($status !== 'all') {
            $where = $wpdb->prepare("WHERE status = %s", $status);
        }
        
        return $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vortex_dao_proposals $where");
    }
    
    /**
     * Calculate actual vote weight based on user role and token amount
     *
     * @param int    $user_id The user ID
     * @param float  $token_amount The token amount being voted with
     * @return float The final vote weight
     */
    public function calculate_vote_weight($user_id, $token_amount) {
        // Get the DAO roles instance
        $dao_roles = VORTEX_DAO_Roles::get_instance();
        
        // Get user's vote multiplier based on role
        $multiplier = $dao_roles->get_user_vote_multiplier($user_id);
        
        // Apply the multiplier to the token amount
        $vote_weight = $token_amount * $multiplier;
        
        // Allow other plugins to modify the vote weight
        $vote_weight = apply_filters('vortex_dao_vote_weight', $vote_weight, $user_id, $token_amount);
        
        return $vote_weight;
    }
    
    public function enhance_governance_system() {
        return [
            'voting_mechanisms' => [
                'weighted_voting' => $this->implement_weighted_voting(),
                'delegation_system' => $this->setup_vote_delegation(),
                'proposal_tracking' => $this->enhance_proposal_monitoring()
            ],
            'treasury_management' => [
                'fund_allocation' => $this->optimize_fund_distribution(),
                'investment_strategies' => $this->develop_investment_plans(),
                'reward_distribution' => $this->manage_rewards()
            ],
            'community_engagement' => [
                'participation_metrics' => $this->track_participation(),
                'incentive_systems' => $this->manage_incentives(),
                'feedback_loops' => $this->implement_feedback_system()
            ]
        ];
    }
}

// Initialize Governance class
$vortex_dao_governance = VORTEX_DAO_Governance::get_instance();

// Register activation hook for table creation
register_activation_hook(VORTEX_PLUGIN_FILE, array('VORTEX_DAO_Governance', 'create_tables')); 