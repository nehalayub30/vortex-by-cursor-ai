<?php
/**
 * VORTEX Integration Bridge
 *
 * Serves as a centralized hub for connecting AI agents, blockchain functionality, 
 * DAO governance, and gamification systems.
 *
 * @package VORTEX_Marketplace
 * @subpackage Integration
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class VORTEX_Integration_Bridge {
    private static $instance = null;
    private $ai_agents = array();
    private $blockchain_manager = null;
    private $dao_manager = null;
    private $gamification_manager = null;
    private $event_listeners = array();
    
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
        // Initialize core connections
        add_action('plugins_loaded', array($this, 'initialize_connections'), 15);
        
        // Set up action listeners
        add_action('vortex_artwork_tokenized', array($this, 'handle_artwork_tokenization'), 10, 2);
        add_action('vortex_artwork_sold', array($this, 'handle_artwork_sale'), 10, 3);
        add_action('vortex_token_transferred', array($this, 'handle_token_transfer'), 10, 3);
        add_action('vortex_dao_proposal_created', array($this, 'handle_proposal_creation'), 10, 2);
        add_action('vortex_dao_vote_cast', array($this, 'handle_dao_vote'), 10, 4);
        add_action('vortex_dao_proposal_finalized', array($this, 'handle_proposal_finalization'), 10, 3);
        add_action('vortex_achievement_unlocked', array($this, 'handle_achievement'), 10, 2);
        
        // AJAX handlers
        add_action('wp_ajax_vortex_get_integrated_stats', array($this, 'ajax_get_integrated_stats'));
        add_action('wp_ajax_nopriv_vortex_get_integrated_stats', array($this, 'ajax_get_integrated_stats'));
    }
    
    /**
     * Initialize connections to other components
     */
    public function initialize_connections() {
        // Connect to AI agents
        $this->connect_ai_agents();
        
        // Connect to blockchain
        $this->connect_blockchain();
        
        // Connect to DAO
        $this->connect_dao();
        
        // Connect to gamification
        $this->connect_gamification();
        
        // Run continuous learning integration
        $this->setup_continuous_learning();
        
        do_action('vortex_integration_bridge_initialized', $this);
    }
    
    /**
     * Connect to AI agents
     */
    private function connect_ai_agents() {
        // Connect to HURAII
        if (class_exists('VORTEX_HURAII')) {
            $this->ai_agents['huraii'] = VORTEX_HURAII::get_instance();
        }
        
        // Connect to CLOE
        if (class_exists('VORTEX_CLOE')) {
            $this->ai_agents['cloe'] = VORTEX_CLOE::get_instance();
        }
        
        // Connect to Business Strategist
        if (class_exists('VORTEX_Business_Strategist')) {
            $this->ai_agents['business_strategist'] = VORTEX_Business_Strategist::get_instance();
        }
        
        // Connect to Thorius
        if (class_exists('VORTEX_Thorius')) {
            $this->ai_agents['thorius'] = VORTEX_Thorius::get_instance();
        }
        
        // Connect to AI Orchestrator
        if (class_exists('VORTEX_Orchestrator')) {
            $this->ai_agents['orchestrator'] = VORTEX_Orchestrator::get_instance();
        }
    }
    
    /**
     * Connect to blockchain functionality
     */
    private function connect_blockchain() {
        if (class_exists('VORTEX_Blockchain_Manager')) {
            $this->blockchain_manager = VORTEX_Blockchain_Manager::get_instance();
        }
        
        // Ensure TOLA integration is working
        if (class_exists('VORTEX_TOLA_Integration')) {
            $tola = new VORTEX_TOLA_Integration();
            // Register with blockchain manager if available
            if ($this->blockchain_manager && method_exists($this->blockchain_manager, 'register_blockchain_provider')) {
                $this->blockchain_manager->register_blockchain_provider('tola', $tola);
            }
        }
    }
    
    /**
     * Connect to DAO functionality
     */
    private function connect_dao() {
        if (class_exists('VORTEX_DAO_Manager')) {
            $this->dao_manager = new VORTEX_DAO_Manager();
        }
    }
    
    /**
     * Connect to gamification functionality
     */
    private function connect_gamification() {
        if (class_exists('VORTEX_Gamification')) {
            $this->gamification_manager = VORTEX_Gamification::get_instance();
        }
    }
    
    /**
     * Set up continuous learning loop between AI agents
     */
    private function setup_continuous_learning() {
        if (empty($this->ai_agents['orchestrator'])) {
            return;
        }
        
        // Initialize the continuous learning process
        $this->ai_agents['orchestrator']->initialize_continuous_learning(array(
            'huraii' => isset($this->ai_agents['huraii']) ? $this->ai_agents['huraii'] : null,
            'cloe' => isset($this->ai_agents['cloe']) ? $this->ai_agents['cloe'] : null,
            'business_strategist' => isset($this->ai_agents['business_strategist']) ? $this->ai_agents['business_strategist'] : null,
            'thorius' => isset($this->ai_agents['thorius']) ? $this->ai_agents['thorius'] : null
        ));
        
        // Schedule periodic integration checks
        if (!wp_next_scheduled('vortex_integration_health_check')) {
            wp_schedule_event(time(), 'hourly', 'vortex_integration_health_check');
        }
        add_action('vortex_integration_health_check', array($this, 'perform_integration_health_check'));
    }
    
    /**
     * Register an event listener
     * 
     * @param string $event Event name
     * @param callable $callback Callback function
     */
    public function add_event_listener($event, $callable) {
        if (!isset($this->event_listeners[$event])) {
            $this->event_listeners[$event] = array();
        }
        
        $this->event_listeners[$event][] = $callable;
    }
    
    /**
     * Trigger event listeners
     * 
     * @param string $event Event name
     * @param array $data Event data
     */
    private function trigger_event($event, $data) {
        if (!isset($this->event_listeners[$event])) {
            return;
        }
        
        foreach ($this->event_listeners[$event] as $callable) {
            call_user_func($callable, $data);
        }
    }
    
    /**
     * Handle artwork tokenization event
     * 
     * @param int $artwork_id Artwork ID
     * @param array $token_data Token data
     */
    public function handle_artwork_tokenization($artwork_id, $token_data) {
        // Add to DAO treasury if configured
        if ($this->dao_manager && method_exists($this->dao_manager, 'add_tokenized_artwork_to_treasury')) {
            $this->dao_manager->add_tokenized_artwork_to_treasury($artwork_id, $token_data);
        }
        
        // Award gamification achievements
        if ($this->gamification_manager) {
            $user_id = get_post_field('post_author', $artwork_id);
            $this->gamification_manager->award_points($user_id, 'artwork_tokenized', 50);
            $this->gamification_manager->check_achievement_progress($user_id, 'tokenize_artwork');
        }
        
        // AI agent feedback
        if (isset($this->ai_agents['cloe'])) {
            $this->ai_agents['cloe']->analyze_artwork_tokenization($artwork_id, $token_data);
        }
        
        // Trigger custom event
        $this->trigger_event('artwork_tokenized', array(
            'artwork_id' => $artwork_id,
            'token_data' => $token_data
        ));
    }
    
    /**
     * Handle artwork sale event
     * 
     * @param int $artwork_id Artwork ID
     * @param int $buyer_id Buyer user ID
     * @param float $amount Sale amount
     */
    public function handle_artwork_sale($artwork_id, $buyer_id, $amount) {
        $seller_id = get_post_field('post_author', $artwork_id);
        
        // Register sale on blockchain
        if ($this->blockchain_manager && method_exists($this->blockchain_manager, 'register_artwork_sale')) {
            $this->blockchain_manager->register_artwork_sale($artwork_id, $seller_id, $buyer_id, $amount);
        }
        
        // Add to DAO revenue if configured
        if ($this->dao_manager && method_exists($this->dao_manager, 'record_marketplace_revenue')) {
            $this->dao_manager->record_marketplace_revenue($amount, 'artwork_sale', $artwork_id);
        }
        
        // Award gamification points
        if ($this->gamification_manager) {
            // Points for seller
            $this->gamification_manager->award_points($seller_id, 'artwork_sold', min(100, intval($amount)));
            
            // Points for buyer
            $this->gamification_manager->award_points($buyer_id, 'artwork_purchased', min(50, intval($amount / 2)));
            
            // Check achievements
            $this->gamification_manager->check_achievement_progress($seller_id, 'sell_artwork');
            $this->gamification_manager->check_achievement_progress($buyer_id, 'collect_artwork');
        }
        
        // AI agent insights
        if (isset($this->ai_agents['business_strategist'])) {
            $this->ai_agents['business_strategist']->analyze_market_transaction($artwork_id, $seller_id, $buyer_id, $amount);
        }
        
        // Trigger custom event
        $this->trigger_event('artwork_sold', array(
            'artwork_id' => $artwork_id,
            'seller_id' => $seller_id,
            'buyer_id' => $buyer_id,
            'amount' => $amount
        ));
    }
    
    /**
     * Handle token transfer event
     * 
     * @param int $from_user_id Sender user ID
     * @param int $to_user_id Recipient user ID
     * @param float $amount Transfer amount
     */
    public function handle_token_transfer($from_user_id, $to_user_id, $amount) {
        // Record on blockchain
        if ($this->blockchain_manager && method_exists($this->blockchain_manager, 'record_token_transfer')) {
            $this->blockchain_manager->record_token_transfer($from_user_id, $to_user_id, $amount);
        }
        
        // Update DAO token metrics
        if ($this->dao_manager && method_exists($this->dao_manager, 'update_user_token_balance')) {
            $this->dao_manager->update_user_token_balance($from_user_id);
            $this->dao_manager->update_user_token_balance($to_user_id);
        }
        
        // Gamification for token transactions
        if ($this->gamification_manager) {
            // Points for transfer
            if ($from_user_id > 0) { // Not system transfer
                $this->gamification_manager->award_points($from_user_id, 'token_transfer', min(20, intval($amount / 10)));
            }
            
            // Points for receiving
            $this->gamification_manager->award_points($to_user_id, 'token_received', min(10, intval($amount / 20)));
        }
        
        // Trigger custom event
        $this->trigger_event('token_transferred', array(
            'from_user_id' => $from_user_id,
            'to_user_id' => $to_user_id,
            'amount' => $amount
        ));
    }
    
    /**
     * Handle proposal creation event
     * 
     * @param int $proposal_id Proposal ID
     * @param int $user_id User ID
     */
    public function handle_proposal_creation($proposal_id, $user_id) {
        // Notify AI Governance Advisor
        if (class_exists('VORTEX_AI_Governance_Advisor')) {
            $advisor = new VORTEX_AI_Governance_Advisor();
            $advisor->analyze_new_proposal($proposal_id);
        }
        
        // Award gamification achievements
        if ($this->gamification_manager) {
            $this->gamification_manager->award_points($user_id, 'proposal_created', 75);
            $this->gamification_manager->check_achievement_progress($user_id, 'create_proposal');
        }
        
        // Trigger custom event
        $this->trigger_event('proposal_created', array(
            'proposal_id' => $proposal_id,
            'user_id' => $user_id
        ));
    }
    
    /**
     * Handle DAO vote event
     * 
     * @param int $proposal_id Proposal ID
     * @param int $user_id User ID
     * @param string $vote Vote value
     * @param float $voting_power Voting power
     */
    public function handle_dao_vote($proposal_id, $user_id, $vote, $voting_power) {
        // Record on blockchain if enabled
        if ($this->blockchain_manager && method_exists($this->blockchain_manager, 'record_dao_vote')) {
            $this->blockchain_manager->record_dao_vote($proposal_id, $user_id, $vote, $voting_power);
        }
        
        // Award gamification achievements
        if ($this->gamification_manager) {
            $this->gamification_manager->award_points($user_id, 'dao_vote', 25);
            $this->gamification_manager->check_achievement_progress($user_id, 'participate_governance');
        }
        
        // AI agent feedback
        if (class_exists('VORTEX_AI_Governance_Advisor')) {
            $advisor = new VORTEX_AI_Governance_Advisor();
            $advisor->analyze_vote($proposal_id, $user_id, $vote, $voting_power);
        }
        
        // Trigger custom event
        $this->trigger_event('dao_vote', array(
            'proposal_id' => $proposal_id,
            'user_id' => $user_id,
            'vote' => $vote,
            'voting_power' => $voting_power
        ));
    }
    
    /**
     * Handle proposal finalization event
     * 
     * @param int $proposal_id Proposal ID
     * @param string $result Result (approved/rejected)
     * @param string $reason Reason
     */
    public function handle_proposal_finalization($proposal_id, $result, $reason) {
        // Record on blockchain if enabled
        if ($this->blockchain_manager && method_exists($this->blockchain_manager, 'record_proposal_result')) {
            $this->blockchain_manager->record_proposal_result($proposal_id, $result, $reason);
        }
        
        // AI governance advisor notification
        if (class_exists('VORTEX_AI_Governance_Advisor')) {
            $advisor = new VORTEX_AI_Governance_Advisor();
            $advisor->analyze_proposal_outcome($proposal_id, $result, $reason);
        }
        
        // If approved, process additional execution steps specific to this bridge
        if ($result === 'approved') {
            // Get proposal details
            $proposal_type = get_post_meta($proposal_id, 'vortex_proposal_type', true);
            $parameters = get_post_meta($proposal_id, 'vortex_proposal_parameters', true);
            
            // Special handling for integration-specific proposals
            if ($proposal_type === 'ai_training' && isset($parameters['agent']) && isset($parameters['training_data'])) {
                $this->execute_ai_training_proposal($parameters);
            } elseif ($proposal_type === 'marketplace_parameter' && isset($parameters['parameter']) && isset($parameters['value'])) {
                $this->execute_marketplace_parameter_proposal($parameters);
            }
        }
        
        // Trigger custom event
        $this->trigger_event('proposal_finalized', array(
            'proposal_id' => $proposal_id,
            'result' => $result,
            'reason' => $reason
        ));
    }
    
    /**
     * Handle achievement unlocked event
     * 
     * @param int $user_id User ID
     * @param array $achievement Achievement data
     */
    public function handle_achievement($user_id, $achievement) {
        // Award TOLA tokens if configured
        if ($this->blockchain_manager && 
            isset($achievement['tola_reward']) && 
            $achievement['tola_reward'] > 0) {
            
            $this->blockchain_manager->transfer_tokens(
                0, // System
                $user_id,
                $achievement['tola_reward'],
                'achievement_reward',
                array('achievement_id' => $achievement['id'])
            );
        }
        
        // Notify agents for personalized feedback
        if (isset($this->ai_agents['cloe'])) {
            $this->ai_agents['cloe']->notify_user_achievement($user_id, $achievement);
        }
        
        // Trigger custom event
        $this->trigger_event('achievement_unlocked', array(
            'user_id' => $user_id,
            'achievement' => $achievement
        ));
    }
    
    /**
     * Execute AI training proposal
     * 
     * @param array $parameters Proposal parameters
     */
    private function execute_ai_training_proposal($parameters) {
        $agent = $parameters['agent'];
        $training_data = $parameters['training_data'];
        
        // Check if agent exists
        if (!isset($this->ai_agents[$agent])) {
            error_log("AI agent {$agent} not found for training proposal");
            return;
        }
        
        // Execute training
        if (method_exists($this->ai_agents[$agent], 'train')) {
            $this->ai_agents[$agent]->train($training_data);
            error_log("AI agent {$agent} trained with DAO-approved data");
        }
    }
    
    /**
     * Execute marketplace parameter proposal
     * 
     * @param array $parameters Proposal parameters
     */
    private function execute_marketplace_parameter_proposal($parameters) {
        $parameter = $parameters['parameter'];
        $value = $parameters['value'];
        
        // Whitelist of allowed parameters
        $allowed_parameters = array(
            'vortex_marketplace_fee',
            'vortex_creator_royalty',
            'vortex_dao_treasury_allocation',
            'vortex_token_reward_rate',
            'vortex_marketplace_categories',
            'vortex_featured_artists_count'
        );
        
        if (in_array($parameter, $allowed_parameters)) {
            update_option($parameter, $value);
            error_log("Marketplace parameter {$parameter} updated to {$value} via DAO proposal");
        }
    }
    
    /**
     * Perform integration health check
     */
    public function perform_integration_health_check() {
        $health_status = array(
            'ai_agents' => array(),
            'blockchain' => false,
            'dao' => false,
            'gamification' => false,
            'timestamp' => current_time('mysql')
        );
        
        // Check AI agents
        foreach ($this->ai_agents as $agent_name => $agent) {
            $health_status['ai_agents'][$agent_name] = ($agent !== null);
        }
        
        // Check blockchain
        $health_status['blockchain'] = ($this->blockchain_manager !== null);
        
        // Check DAO
        $health_status['dao'] = ($this->dao_manager !== null);
        
        // Check gamification
        $health_status['gamification'] = ($this->gamification_manager !== null);
        
        // Store health status
        update_option('vortex_integration_health', $health_status);
        
        // Notify admin of issues
        $has_issues = false;
        $issues = array();
        
        if (!$health_status['blockchain']) {
            $has_issues = true;
            $issues[] = 'Blockchain functionality not connected';
        }
        
        if (!$health_status['dao']) {
            $has_issues = true;
            $issues[] = 'DAO functionality not connected';
        }
        
        if (!$health_status['gamification']) {
            $has_issues = true;
            $issues[] = 'Gamification functionality not connected';
        }
        
        foreach ($health_status['ai_agents'] as $agent_name => $status) {
            if (!$status) {
                $has_issues = true;
                $issues[] = "AI agent '{$agent_name}' not connected";
            }
        }
        
        if ($has_issues && count($issues) > 0) {
            $this->notify_admin_of_integration_issues($issues);
        }
    }
    
    /**
     * Notify admin of integration issues
     * 
     * @param array $issues List of issues
     */
    private function notify_admin_of_integration_issues($issues) {
        $admin_email = get_option('admin_email');
        
        $subject = 'VORTEX Marketplace Integration Issues Detected';
        
        $message = '<h1>VORTEX Integration Issues</h1>';
        $message .= '<p>The following integration issues were detected:</p>';
        $message .= '<ul>';
        
        foreach ($issues as $issue) {
            $message .= '<li>' . $issue . '</li>';
        }
        
        $message .= '</ul>';
        $message .= '<p>Please visit the VORTEX admin panel to resolve these issues.</p>';
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        wp_mail($admin_email, $subject, $message, $headers);
    }
    
    /**
     * AJAX handler for getting integrated stats
     */
    public function ajax_get_integrated_stats() {
        check_ajax_referer('vortex_nonce', 'nonce');
        
        $stats = array(
            'ai_generated_artworks' => $this->get_ai_generated_artworks_count(),
            'tokenized_artworks' => $this->get_tokenized_artworks_count(),
            'tola_circulation' => $this->get_tola_circulation(),
            'dao_proposals' => $this->get_proposal_stats(),
            'top_artists' => $this->get_top_artists(),
            'top_categories' => $this->get_top_categories(),
            'timestamp' => current_time('mysql')
        );
        
        wp_send_json_success($stats);
    }
    
    /**
     * Get AI generated artworks count
     * 
     * @return int Count
     */
    private function get_ai_generated_artworks_count() {
        global $wpdb;
        
        return (int)$wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
             JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'vortex_artwork'
             AND pm.meta_key = 'vortex_ai_generated'
             AND pm.meta_value = '1'"
        );
    }
    
    /**
     * Get tokenized artworks count
     * 
     * @return int Count
     */
    private function get_tokenized_artworks_count() {
        global $wpdb;
        
        return (int)$wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
             JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'vortex_artwork'
             AND pm.meta_key = 'vortex_token_id'
             AND pm.meta_value != ''"
        );
    }
    
    /**
     * Get TOLA circulation
     * 
     * @return float Circulation
     */
    private function get_tola_circulation() {
        global $wpdb;
        
        $circulation = (float)$wpdb->get_var(
            "SELECT SUM(token_balance) FROM {$wpdb->prefix}vortex_wallet_addresses"
        );
        
        return $circulation ?: 0;
    }
    
    /**
     * Get proposal stats
     * 
     * @return array Stats
     */
    private function get_proposal_stats() {
        global $wpdb;
        
        $approved = (int)$wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts}
             WHERE post_type = 'vortex_proposal'
             AND post_status = 'approved'"
        );
        
        $rejected = (int)$wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts}
             WHERE post_type = 'vortex_proposal'
             AND post_status = 'rejected'"
        );
        
        $pending = (int)$wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts}
             WHERE post_type = 'vortex_proposal'
             AND post_status = 'publish'"
        );
        
        return array(
            'approved' => $approved,
            'rejected' => $rejected,
            'pending' => $pending,
            'total' => $approved + $rejected + $pending
        );
    }
    
    /**
     * Get top artists
     * 
     * @return array Top artists
     */
    private function get_top_artists() {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT p.post_author as artist_id, u.display_name as artist_name, 
                    COUNT(*) as artwork_count, SUM(pm.meta_value) as token_volume
             FROM {$wpdb->posts} p
             JOIN {$wpdb->users} u ON p.post_author = u.ID
             JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'vortex_token_volume'
             WHERE p.post_type = 'vortex_artwork'
             GROUP BY p.post_author
             ORDER BY token_volume DESC
             LIMIT 5",
            ARRAY_A
        );
    }
    
    /**
     * Get top categories
     * 
     * @return array Top categories
     */
    private function get_top_categories() {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT t.term_id, t.name, COUNT(p.ID) as artwork_count
             FROM {$wpdb->posts} p
             JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
             JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
             JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
             WHERE p.post_type = 'vortex_artwork'
             AND tt.taxonomy = 'artwork_category'
             GROUP BY t.term_id
             ORDER BY artwork_count DESC
             LIMIT 5",
            ARRAY_A
        );
    }
}

// Initialize the integration bridge
VORTEX_Integration_Bridge::get_instance(); 