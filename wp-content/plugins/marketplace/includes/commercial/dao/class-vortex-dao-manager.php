<?php
/**
 * VORTEX DAO Manager Class
 *
 * Manages decentralized autonomous organization functionality
 */

class VORTEX_DAO_Manager {
    private $blockchain_manager;
    private $db;
    private $dao_address;
    private $voting_period = 7; // Default voting period in days
    
    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->blockchain_manager = new VORTEX_Blockchain_Manager();
        $this->dao_address = get_option('vortex_dao_address', '');
        $this->voting_period = get_option('vortex_dao_voting_period', 7);
        
        // Initialize hooks
        add_action('init', array($this, 'register_post_types'));
        add_action('vortex_daily_cron', array($this, 'check_proposal_status'));
        
        // AJAX handlers
        add_action('wp_ajax_vortex_create_proposal', array($this, 'ajax_create_proposal'));
        add_action('wp_ajax_vortex_vote_on_proposal', array($this, 'ajax_vote_on_proposal'));
    }
    
    /**
     * Register DAO post types
     */
    public function register_post_types() {
        register_post_type('vortex_proposal', array(
            'labels' => array(
                'name' => __('Proposals', 'vortex-marketplace'),
                'singular_name' => __('Proposal', 'vortex-marketplace'),
            ),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'author', 'comments'),
            'menu_icon' => 'dashicons-clipboard',
            'show_in_rest' => true,
        ));
        
        register_post_status('approved', array(
            'label' => _x('Approved', 'proposal status', 'vortex-marketplace'),
            'public' => true,
            'label_count' => _n_noop('Approved <span class="count">(%s)</span>', 'Approved <span class="count">(%s)</span>'),
        ));
        
        register_post_status('rejected', array(
            'label' => _x('Rejected', 'proposal status', 'vortex-marketplace'),
            'public' => true,
            'label_count' => _n_noop('Rejected <span class="count">(%s)</span>', 'Rejected <span class="count">(%s)</span>'),
        ));
    }
    
    /**
     * Create a new governance proposal
     */
    public function create_proposal($user_id, $proposal_data) {
        // Check user eligibility
        if (!$this->is_user_eligible_to_propose($user_id)) {
            return new WP_Error('ineligible', __('User is not eligible to create proposals', 'vortex-marketplace'));
        }
        
        // Validate proposal data
        if (empty($proposal_data['title']) || empty($proposal_data['description'])) {
            return new WP_Error('invalid_data', __('Proposal must have a title and description', 'vortex-marketplace'));
        }
        
        // Create proposal post
        $proposal_id = wp_insert_post(array(
            'post_title' => sanitize_text_field($proposal_data['title']),
            'post_content' => wp_kses_post($proposal_data['description']),
            'post_author' => $user_id,
            'post_type' => 'vortex_proposal',
            'post_status' => 'publish',
        ));
        
        if (is_wp_error($proposal_id)) {
            return $proposal_id;
        }
        
        // Set proposal metadata
        $end_date = date('Y-m-d H:i:s', strtotime('+' . $this->voting_period . ' days'));
        update_post_meta($proposal_id, 'vortex_proposal_end_date', $end_date);
        update_post_meta($proposal_id, 'vortex_proposal_type', sanitize_text_field($proposal_data['type']));
        update_post_meta($proposal_id, 'vortex_proposal_yes_votes', 0);
        update_post_meta($proposal_id, 'vortex_proposal_no_votes', 0);
        update_post_meta($proposal_id, 'vortex_proposal_abstain_votes', 0);
        update_post_meta($proposal_id, 'vortex_proposal_total_votes', 0);
        
        // Store proposal parameters
        if (!empty($proposal_data['parameters'])) {
            update_post_meta($proposal_id, 'vortex_proposal_parameters', $proposal_data['parameters']);
        }
        
        // Publish to blockchain if enabled
        if (!empty($this->dao_address)) {
            try {
                $blockchain_result = $this->blockchain_manager->create_dao_proposal(
                    $this->dao_address,
                    $user_id,
                    $proposal_id,
                    $proposal_data
                );
                
                if (!is_wp_error($blockchain_result)) {
                    update_post_meta($proposal_id, 'vortex_proposal_tx_hash', $blockchain_result['tx_hash']);
                    update_post_meta($proposal_id, 'vortex_proposal_chain_id', $blockchain_result['proposal_id']);
                }
            } catch (Exception $e) {
                // Log error but continue with local proposal
                error_log('Error publishing proposal to blockchain: ' . $e->getMessage());
            }
        }
        
        // Log the event
        do_action('vortex_proposal_created', $proposal_id, $user_id);
        
        return $proposal_id;
    }
    
    /**
     * Cast a vote on a proposal
     */
    public function cast_vote($user_id, $proposal_id, $vote) {
        // Check if proposal exists and is active
        $proposal = get_post($proposal_id);
        if (!$proposal || $proposal->post_type !== 'vortex_proposal' || $proposal->post_status !== 'publish') {
            return new WP_Error('invalid_proposal', __('Invalid or inactive proposal', 'vortex-marketplace'));
        }
        
        // Check if voting period is still open
        $end_date = get_post_meta($proposal_id, 'vortex_proposal_end_date', true);
        if (strtotime($end_date) < current_time('timestamp')) {
            return new WP_Error('voting_closed', __('Voting period has ended', 'vortex-marketplace'));
        }
        
        // Check if user has already voted
        $existing_vote = $this->get_user_vote($user_id, $proposal_id);
        if ($existing_vote) {
            return new WP_Error('already_voted', __('User has already voted on this proposal', 'vortex-marketplace'));
        }
        
        // Check if user is eligible to vote
        if (!$this->is_user_eligible_to_vote($user_id)) {
            return new WP_Error('ineligible', __('User is not eligible to vote', 'vortex-marketplace'));
        }
        
        // Validate vote
        if (!in_array($vote, array('yes', 'no', 'abstain'))) {
            return new WP_Error('invalid_vote', __('Invalid vote value', 'vortex-marketplace'));
        }
        
        // Calculate voting power
        $voting_power = $this->calculate_voting_power($user_id);
        
        // Record the vote in the database
        $result = $this->db->insert(
            $this->db->prefix . 'vortex_proposal_votes',
            array(
                'proposal_id' => $proposal_id,
                'user_id' => $user_id,
                'vote' => $vote,
                'voting_power' => $voting_power,
                'vote_date' => current_time('mysql')
            ),
            array('%d', '%d', '%s', '%f', '%s')
        );
        
        if (!$result) {
            return new WP_Error('db_error', __('Failed to record vote', 'vortex-marketplace'));
        }
        
        // Update vote counts
        $vote_key = 'vortex_proposal_' . $vote . '_votes';
        $current_votes = get_post_meta($proposal_id, $vote_key, true);
        update_post_meta($proposal_id, $vote_key, $current_votes + $voting_power);
        
        $total_votes = get_post_meta($proposal_id, 'vortex_proposal_total_votes', true);
        update_post_meta($proposal_id, 'vortex_proposal_total_votes', $total_votes + $voting_power);
        
        // Submit vote to blockchain if enabled
        if (!empty($this->dao_address)) {
            try {
                $blockchain_result = $this->blockchain_manager->cast_dao_vote(
                    $this->dao_address,
                    $user_id,
                    get_post_meta($proposal_id, 'vortex_proposal_chain_id', true),
                    $vote,
                    $voting_power
                );
                
                if (!is_wp_error($blockchain_result)) {
                    // Save transaction hash
                    $this->db->update(
                        $this->db->prefix . 'vortex_proposal_votes',
                        array('tx_hash' => $blockchain_result['tx_hash']),
                        array('proposal_id' => $proposal_id, 'user_id' => $user_id),
                        array('%s'),
                        array('%d', '%d')
                    );
                }
            } catch (Exception $e) {
                // Log error but continue with local vote
                error_log('Error submitting vote to blockchain: ' . $e->getMessage());
            }
        }
        
        // Log the event
        do_action('vortex_proposal_vote_cast', $proposal_id, $user_id, $vote, $voting_power);
        
        return true;
    }
    
    /**
     * Check status of proposals and update accordingly
     */
    public function check_proposal_status() {
        $current_time = current_time('mysql');
        
        // Get proposals with ended voting periods
        $args = array(
            'post_type' => 'vortex_proposal',
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => 'vortex_proposal_end_date',
                    'value' => $current_time,
                    'compare' => '<',
                    'type' => 'DATETIME'
                )
            ),
            'posts_per_page' => -1
        );
        
        $proposals = get_posts($args);
        
        foreach ($proposals as $proposal) {
            $this->finalize_proposal($proposal->ID);
        }
    }
    
    /**
     * Finalize a proposal after voting period ends
     */
    public function finalize_proposal($proposal_id) {
        // Get vote counts
        $yes_votes = get_post_meta($proposal_id, 'vortex_proposal_yes_votes', true);
        $no_votes = get_post_meta($proposal_id, 'vortex_proposal_no_votes', true);
        $abstain_votes = get_post_meta($proposal_id, 'vortex_proposal_abstain_votes', true);
        $total_votes = get_post_meta($proposal_id, 'vortex_proposal_total_votes', true);
        
        // Minimum quorum (configurable)
        $min_quorum = get_option('vortex_dao_min_quorum', 100);
        
        // Determine if proposal is approved or rejected
        if ($total_votes < $min_quorum) {
            $result = 'rejected'; // Failed to meet quorum
            $reason = 'quorum_not_met';
        } else if ($yes_votes > $no_votes) {
            $result = 'approved';
            $reason = 'majority_approval';
        } else {
            $result = 'rejected';
            $reason = 'majority_rejection';
        }
        
        // Update proposal status
        wp_update_post(array(
            'ID' => $proposal_id,
            'post_status' => $result
        ));
        
        // Store final results
        update_post_meta($proposal_id, 'vortex_proposal_final_result', $result);
        update_post_meta($proposal_id, 'vortex_proposal_result_reason', $reason);
        update_post_meta($proposal_id, 'vortex_proposal_finalized_date', current_time('mysql'));
        
        // Execute proposal actions if approved
        if ($result === 'approved') {
            $this->execute_proposal($proposal_id);
        }
        
        // Log the event
        do_action('vortex_proposal_finalized', $proposal_id, $result, $reason);
        
        return $result;
    }
    
    /**
     * Execute approved proposal actions
     */
    private function execute_proposal($proposal_id) {
        $proposal_type = get_post_meta($proposal_id, 'vortex_proposal_type', true);
        $parameters = get_post_meta($proposal_id, 'vortex_proposal_parameters', true);
        
        switch ($proposal_type) {
            case 'parameter_change':
                $this->execute_parameter_change($parameters);
                break;
                
            case 'feature_request':
                $this->mark_feature_requested($proposal_id, $parameters);
                break;
                
            case 'fund_allocation':
                $this->execute_fund_allocation($parameters);
                break;
                
            case 'membership':
                $this->execute_membership_change($parameters);
                break;
                
            case 'custom':
                do_action('vortex_execute_custom_proposal', $proposal_id, $parameters);
                break;
        }
        
        // Send to blockchain for execution if connected
        if (!empty($this->dao_address)) {
            try {
                $this->blockchain_manager->execute_dao_proposal(
                    $this->dao_address,
                    get_post_meta($proposal_id, 'vortex_proposal_chain_id', true)
                );
            } catch (Exception $e) {
                error_log('Error executing proposal on blockchain: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Execute parameter change proposal
     */
    private function execute_parameter_change($parameters) {
        if (!is_array($parameters) || empty($parameters['key']) || !isset($parameters['value'])) {
            return false;
        }
        
        $allowed_parameters = array(
            'vortex_dao_voting_period',
            'vortex_dao_min_quorum',
            'vortex_marketplace_fee',
            'vortex_artist_royalty_default',
            'vortex_history_retention_days'
        );
        
        if (in_array($parameters['key'], $allowed_parameters)) {
            update_option($parameters['key'], $parameters['value']);
            return true;
        }
        
        return false;
    }
    
    /**
     * Mark feature as requested
     */
    private function mark_feature_requested($proposal_id, $parameters) {
        if (!is_array($parameters) || empty($parameters['feature_name'])) {
            return false;
        }
        
        // Store in requested features option
        $features = get_option('vortex_requested_features', array());
        $features[] = array(
            'name' => $parameters['feature_name'],
            'description' => $parameters['description'] ?? '',
            'proposal_id' => $proposal_id,
            'date_approved' => current_time('mysql')
        );
        
        update_option('vortex_requested_features', $features);
        return true;
    }
    
    /**
     * Execute fund allocation proposal
     */
    private function execute_fund_allocation($parameters) {
        if (!is_array($parameters) || 
            empty($parameters['recipient']) || 
            empty($parameters['amount']) || 
            empty($parameters['purpose'])) {
            return false;
        }
        
        // Record the allocation
        $allocations = get_option('vortex_fund_allocations', array());
        $allocations[] = array(
            'recipient' => $parameters['recipient'],
            'amount' => $parameters['amount'],
            'purpose' => $parameters['purpose'],
            'date_allocated' => current_time('mysql'),
            'status' => 'pending'
        );
        
        update_option('vortex_fund_allocations', $allocations);
        
        // Notify administrators
        $admin_email = get_option('admin_email');
        wp_mail(
            $admin_email,
            __('DAO Fund Allocation Approved', 'vortex-marketplace'),
            sprintf(
                __('A fund allocation of %s to %s for %s has been approved by the DAO.', 'vortex-marketplace'),
                $parameters['amount'],
                $parameters['recipient'],
                $parameters['purpose']
            )
        );
        
        return true;
    }
    
    /**
     * Execute membership change proposal
     */
    private function execute_membership_change($parameters) {
        if (!is_array($parameters) || 
            empty($parameters['action']) || 
            empty($parameters['user_id'])) {
            return false;
        }
        
        $user_id = intval($parameters['user_id']);
        $user = get_user_by('id', $user_id);
        
        if (!$user) {
            return false;
        }
        
        switch ($parameters['action']) {
            case 'add_role':
                if (!empty($parameters['role']) && in_array($parameters['role'], array('vortex_dao_member', 'vortex_dao_admin'))) {
                    $user->add_role($parameters['role']);
                    return true;
                }
                break;
                
            case 'remove_role':
                if (!empty($parameters['role']) && in_array($parameters['role'], array('vortex_dao_member', 'vortex_dao_admin'))) {
                    $user->remove_role($parameters['role']);
                    return true;
                }
                break;
        }
        
        return false;
    }
    
    /**
     * Get user's vote on a proposal
     */
    public function get_user_vote($user_id, $proposal_id) {
        $vote = $this->db->get_row($this->db->prepare(
            "SELECT * FROM {$this->db->prefix}vortex_proposal_votes 
            WHERE user_id = %d AND proposal_id = %d",
            $user_id,
            $proposal_id
        ));
        
        return $vote;
    }
    
    /**
     * Check if a user is eligible to create proposals
     */
    public function is_user_eligible_to_propose($user_id) {
        $user = get_user_by('id', $user_id);
        
        if (!$user) {
            return false;
        }
        
        // Check if user has required role
        if ($user->has_cap('vortex_create_proposals')) {
            return true;
        }
        
        // Check token holdings if using tokens for governance
        $token_balance = $this->get_user_token_balance($user_id);
        $min_tokens_to_propose = get_option('vortex_min_tokens_to_propose', 100);
        
        return $token_balance >= $min_tokens_to_propose;
    }
    
    /**
     * Check if a user is eligible to vote
     */
    public function is_user_eligible_to_vote($user_id) {
        $user = get_user_by('id', $user_id);
        
        if (!$user) {
            return false;
        }
        
        // Check if user has required role
        if ($user->has_cap('vortex_vote_on_proposals')) {
            return true;
        }
        
        // Check token holdings if using tokens for governance
        $token_balance = $this->get_user_token_balance($user_id);
        $min_tokens_to_vote = get_option('vortex_min_tokens_to_vote', 1);
        
        return $token_balance >= $min_tokens_to_vote;
    }
    
    /**
     * Calculate user's voting power
     */
    private function calculate_voting_power($user_id) {
        $voting_method = get_option('vortex_dao_voting_method', 'token_based');
        
        if ($voting_method === 'equal') {
            // One user, one vote
            return 1;
        } else if ($voting_method === 'token_based') {
            // Voting power based on token holdings
            $token_balance = $this->get_user_token_balance($user_id);
            return $token_balance;
        } else if ($voting_method === 'quadratic') {
            // Quadratic voting (square root of tokens)
            $token_balance = $this->get_user_token_balance($user_id);
            return sqrt($token_balance);
        } else if ($voting_method === 'reputation') {
            // Based on user reputation/contribution
            $reputation = $this->get_user_reputation($user_id);
            return $reputation;
        }
        
        return 1; // Default fallback
    }
    
    /**
     * Get user's token balance
     */
    private function get_user_token_balance($user_id) {
        // Get wallet address
        $wallet_address = get_user_meta($user_id, 'vortex_wallet_address', true);
        
        if (empty($wallet_address)) {
            return 0;
        }
        
        // Check token balance from blockchain
        try {
            $token_address = get_option('vortex_governance_token_address', '');
            
            if (empty($token_address)) {
                return 0;
            }
            
            $balance = $this->blockchain_manager->get_token_balance($token_address, $wallet_address);
            return $balance;
        } catch (Exception $e) {
            error_log('Error checking token balance: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get user's reputation score
     */
    private function get_user_reputation($user_id) {
        // Get stored reputation
        $reputation = get_user_meta($user_id, 'vortex_reputation_score', true);
        
        if (!$reputation) {
            return 1; // Default reputation
        }
        
        return floatval($reputation);
    }
    
    /**
     * AJAX handler for creating a proposal
     */
    public function ajax_create_proposal() {
        check_ajax_referer('vortex_dao_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to create proposals', 'vortex-marketplace')));
            return;
        }
        
        $user_id = get_current_user_id();
        
        $proposal_data = array(
            'title' => sanitize_text_field($_POST['title']),
            'description' => wp_kses_post($_POST['description']),
            'type' => sanitize_text_field($_POST['type']),
            'parameters' => isset($_POST['parameters']) ? $_POST['parameters'] : array()
        );
        
        $result = $this->create_proposal($user_id, $proposal_data);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        } else {
            wp_send_json_success(array(
                'proposal_id' => $result,
                'redirect' => get_permalink($result)
            ));
        }
    }
    
    /**
     * AJAX handler for voting on a proposal
     */
    public function ajax_vote_on_proposal() {
        check_ajax_referer('vortex_dao_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to vote', 'vortex-marketplace')));
            return;
        }
        
        $user_id = get_current_user_id();
        $proposal_id = intval($_POST['proposal_id']);
        $vote = sanitize_text_field($_POST['vote']);
        
        $result = $this->cast_vote($user_id, $proposal_id, $vote);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        } else {
            // Get updated vote counts
            $yes_votes = get_post_meta($proposal_id, 'vortex_proposal_yes_votes', true);
            $no_votes = get_post_meta($proposal_id, 'vortex_proposal_no_votes', true);
            $abstain_votes = get_post_meta($proposal_id, 'vortex_proposal_abstain_votes', true);
            
            wp_send_json_success(array(
                'yes_votes' => $yes_votes,
                'no_votes' => $no_votes,
                'abstain_votes' => $abstain_votes,
                'message' => __('Your vote has been recorded', 'vortex-marketplace')
            ));
        }
    }
    
    /**
     * Install database tables
     */
    public static function install() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$wpdb->prefix}vortex_proposal_votes (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            proposal_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            vote varchar(10) NOT NULL,
            voting_power float NOT NULL,
            vote_date datetime NOT NULL,
            tx_hash varchar(66) DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY proposal_id (proposal_id),
            KEY user_id (user_id),
            UNIQUE KEY proposal_user (proposal_id,user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Integrate AI agents with DAO governance system
     * 
     * Connects the continuous deep learning AI agents with the DAO governance
     * system to provide intelligent proposal analysis and recommendation.
     * 
     * @since 1.0.0
     * @param int $proposal_id The proposal ID to analyze
     * @return array Analysis results
     */
    public function integrate_ai_analysis_with_governance($proposal_id) {
        $proposal = get_post($proposal_id);
        if (!$proposal || $proposal->post_type !== 'vortex_proposal') {
            return new WP_Error('invalid_proposal', __('Invalid proposal', 'vortex-marketplace'));
        }
        
        $analysis_results = array(
            'proposal_id' => $proposal_id,
            'agent_insights' => array(),
            'tola_impact' => null,
} 