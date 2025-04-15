<?php
/**
 * VORTEX DAO AI Integration
 *
 * Handles integration between DAO governance and AI Orchestrator
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class VORTEX_DAO_AI_Integration {
    
    private static $instance = null;
    private $orchestrator;
    
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
        // Initialize AI Orchestrator reference
        $this->orchestrator = VORTEX_Orchestrator::get_instance();
        
        // Hook into proposal creation
        add_action('vortex_dao_proposal_created', array($this, 'analyze_new_proposal'), 10, 2);
        
        // Hook into proposal voting
        add_action('vortex_dao_vote_cast', array($this, 'analyze_voting_pattern'), 10, 3);
        
        // Hook into proposal execution
        add_action('vortex_dao_proposal_executed', array($this, 'analyze_execution_impact'), 10, 2);
        
        // Hook into daily learning cycle
        add_action('vortex_daily_learning_complete', array($this, 'integrate_governance_insights'), 10, 1);
        
        // Add AJAX endpoint for governance recommendations
        add_action('wp_ajax_vortex_get_governance_insights', array($this, 'ajax_get_governance_insights'));
        add_action('wp_ajax_nopriv_vortex_get_governance_insights', array($this, 'ajax_get_governance_insights'));
    }
    
    /**
     * Analyze new proposal with AI
     *
     * @param int   $proposal_id The new proposal ID
     * @param array $proposal_data The proposal data
     */
    public function analyze_new_proposal($proposal_id, $proposal_data) {
        global $wpdb;
        
        // Prepare proposal data for AI analysis
        $analysis_data = array(
            'proposal_id' => $proposal_id,
            'title' => $proposal_data['title'],
            'description' => $proposal_data['description'],
            'proposal_type' => $proposal_data['proposal_type'],
            'proposer' => $proposal_data['proposer'],
            'created_at' => $proposal_data['created_at'],
            'action_data' => $proposal_data['action_data']
        );
        
        // Get CLOE (Community & Logic Optimization Engine) to analyze the proposal
        $cloe_analysis = $this->orchestrator->get_cloe_analysis('proposal', $analysis_data);
        
        // Get Business Strategist to evaluate financial impact
        $business_analysis = $this->orchestrator->get_business_analysis('proposal', $analysis_data);
        
        // Get Thorius to check for security implications
        $security_analysis = $this->orchestrator->get_thorius_analysis('proposal', $analysis_data);
        
        // Combine analyses
        $combined_analysis = array(
            'proposal_id' => $proposal_id,
            'cloe_analysis' => $cloe_analysis,
            'business_analysis' => $business_analysis,
            'security_analysis' => $security_analysis,
            'timestamp' => current_time('mysql')
        );
        
        // Store analysis in proposal metadata
        $wpdb->insert(
            $wpdb->prefix . 'vortex_dao_proposal_meta',
            array(
                'proposal_id' => $proposal_id,
                'meta_key' => 'ai_analysis',
                'meta_value' => wp_json_encode($combined_analysis)
            ),
            array('%d', '%s', '%s')
        );
        
        // If the analysis identifies significant issues, create an alert
        if ($security_analysis['risk_level'] === 'high' || $business_analysis['risk_level'] === 'high') {
            $this->create_governance_alert($proposal_id, $combined_analysis);
        }
    }
    
    /**
     * Analyze voting patterns
     *
     * @param int    $vote_id The vote ID
     * @param int    $proposal_id The proposal ID
     * @param array  $vote_data The vote data
     */
    public function analyze_voting_pattern($vote_id, $proposal_id, $vote_data) {
        // Collect voting pattern data for AI learning
        $voting_data = array(
            'proposal_id' => $proposal_id,
            'vote_id' => $vote_id,
            'voter' => $vote_data['voter'],
            'vote_type' => $vote_data['vote_type'],
            'vote_weight' => $vote_data['vote_weight'],
            'timestamp' => $vote_data['created_at']
        );
        
        // Submit to AI Orchestrator for learning
        $this->orchestrator->submit_data_for_learning('voting_pattern', $voting_data);
        
        // Check for suspicious voting patterns (potential collusion)
        $this->detect_suspicious_voting_patterns($proposal_id);
    }
    
    /**
     * Analyze execution impact
     *
     * @param int   $proposal_id The proposal ID
     * @param array $execution_data The execution data
     */
    public function analyze_execution_impact($proposal_id, $execution_data) {
        // Submit execution data for AI analysis
        $impact_data = array(
            'proposal_id' => $proposal_id,
            'execution_data' => $execution_data,
            'executed_at' => current_time('mysql')
        );
        
        // Get analysis from the orchestrator
        $impact_analysis = $this->orchestrator->analyze_proposal_impact($impact_data);
        
        // Store analysis in proposal metadata
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'vortex_dao_proposal_meta',
            array(
                'proposal_id' => $proposal_id,
                'meta_key' => 'execution_impact',
                'meta_value' => wp_json_encode($impact_analysis)
            ),
            array('%d', '%s', '%s')
        );
        
        // Update metrics to reflect execution impact
        $this->update_dao_metrics_after_execution($proposal_id, $impact_analysis);
    }
    
    /**
     * Integrate governance insights into AI learning cycle
     *
     * @param array $learning_data The learning cycle data
     */
    public function integrate_governance_insights($learning_data) {
        global $wpdb;
        
        // Collect governance data from recent period
        $from_date = date('Y-m-d H:i:s', strtotime('-1 day'));
        
        // Get recent proposals
        $proposals = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vortex_dao_proposals 
                WHERE created_at > %s",
                $from_date
            ),
            ARRAY_A
        );
        
        // Get recent votes
        $votes = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT v.*, p.proposal_type 
                FROM {$wpdb->prefix}vortex_dao_votes v
                JOIN {$wpdb->prefix}vortex_dao_proposals p ON v.proposal_id = p.id
                WHERE v.created_at > %s",
                $from_date
            ),
            ARRAY_A
        );
        
        // Get metrics history
        $metrics = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vortex_dao_metrics_history
                WHERE recorded_at > %s",
                $from_date
            ),
            ARRAY_A
        );
        
        // Prepare governance insights data
        $governance_data = array(
            'proposals' => $proposals,
            'votes' => $votes,
            'metrics' => $metrics,
            'treasury_activity' => $this->get_treasury_activity($from_date)
        );
        
        // Submit data for AI learning
        $this->orchestrator->submit_data_for_learning('governance', $governance_data);
        
        // Generate cross-agent insights specifically for governance
        $governance_insights = $this->orchestrator->generate_governance_insights($governance_data);
        
        // Store insights in database
        $this->store_governance_insights($governance_insights);
    }
    
    /**
     * Get treasury activity
     *
     * @param string $from_date Start date
     * @return array Treasury activity data
     */
    private function get_treasury_activity($from_date) {
        global $wpdb;
        
        // Get transfers from the treasury
        $outgoing = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vortex_token_transfers
                WHERE from_address = %s AND created_at > %s",
                get_option('vortex_dao_treasury_address'),
                $from_date
            ),
            ARRAY_A
        );
        
        // Get transfers to the treasury
        $incoming = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vortex_token_transfers
                WHERE to_address = %s AND created_at > %s",
                get_option('vortex_dao_treasury_address'),
                $from_date
            ),
            ARRAY_A
        );
        
        return array(
            'outgoing' => $outgoing,
            'incoming' => $incoming
        );
    }
    
    /**
     * Store governance insights
     *
     * @param array $insights The insights data
     */
    private function store_governance_insights($insights) {
        global $wpdb;
        
        foreach ($insights as $insight) {
            $wpdb->insert(
                $wpdb->prefix . 'vortex_agent_insights',
                array(
                    'agent_name' => $insight['agent'],
                    'insight_type' => 'governance',
                    'insight_category' => $insight['category'],
                    'title' => $insight['title'],
                    'content' => $insight['content'],
                    'confidence' => $insight['confidence'],
                    'created_at' => current_time('mysql')
                ),
                array('%s', '%s', '%s', '%s', '%s', '%f', '%s')
            );
        }
    }
    
    /**
     * Detect suspicious voting patterns
     *
     * @param int $proposal_id The proposal ID
     */
    private function detect_suspicious_voting_patterns($proposal_id) {
        global $wpdb;
        
        // Get all votes for this proposal
        $votes = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vortex_dao_votes
                WHERE proposal_id = %d
                ORDER BY created_at ASC",
                $proposal_id
            ),
            ARRAY_A
        );
        
        if (count($votes) < 5) {
            return; // Not enough votes to analyze
        }
        
        // Submit to HURAII for pattern analysis
        $analysis = $this->orchestrator->analyze_voting_patterns($votes);
        
        // If suspicious patterns are detected, create an alert
        if ($analysis['suspicious_pattern_detected']) {
            $this->create_voting_pattern_alert($proposal_id, $analysis);
        }
    }
    
    /**
     * Create governance alert
     *
     * @param int   $proposal_id The proposal ID
     * @param array $analysis The analysis data
     */
    private function create_governance_alert($proposal_id, $analysis) {
        global $wpdb;
        
        // Create alert in the database
        $wpdb->insert(
            $wpdb->prefix . 'vortex_agent_insights',
            array(
                'agent_name' => 'Thorius',
                'insight_type' => 'alert',
                'insight_category' => 'governance',
                'title' => 'High Risk Proposal Detected',
                'content' => wp_json_encode(array(
                    'proposal_id' => $proposal_id,
                    'security_risk' => $analysis['security_analysis']['risk_level'],
                    'financial_risk' => $analysis['business_analysis']['risk_level'],
                    'recommendation' => $analysis['security_analysis']['recommendation'],
                    'details' => $analysis['security_analysis']['details']
                )),
                'confidence' => 0.9,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s', '%f', '%s')
        );
        
        // Notify admin users about the alert
        $this->notify_admins_of_alert('High Risk Proposal', $proposal_id);
    }
    
    /**
     * Create voting pattern alert
     *
     * @param int   $proposal_id The proposal ID
     * @param array $analysis The analysis data
     */
    private function create_voting_pattern_alert($proposal_id, $analysis) {
        global $wpdb;
        
        // Create alert in the database
        $wpdb->insert(
            $wpdb->prefix . 'vortex_agent_insights',
            array(
                'agent_name' => 'HURAII',
                'insight_type' => 'alert',
                'insight_category' => 'governance',
                'title' => 'Suspicious Voting Pattern Detected',
                'content' => wp_json_encode(array(
                    'proposal_id' => $proposal_id,
                    'pattern_type' => $analysis['pattern_type'],
                    'confidence' => $analysis['confidence'],
                    'affected_addresses' => $analysis['affected_addresses'],
                    'recommendation' => $analysis['recommendation']
                )),
                'confidence' => $analysis['confidence'],
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s', '%f', '%s')
        );
        
        // Notify admin users about the alert
        $this->notify_admins_of_alert('Suspicious Voting Pattern', $proposal_id);
    }
    
    /**
     * Notify admins of an alert
     *
     * @param string $alert_type Type of alert
     * @param int    $proposal_id The proposal ID
     */
    private function notify_admins_of_alert($alert_type, $proposal_id) {
        // Get admin emails
        $admin_emails = array();
        $admins = get_users(array('role' => 'administrator'));
        
        foreach ($admins as $admin) {
            $admin_emails[] = $admin->user_email;
        }
        
        // Also notify founders
        $founders = get_users(array('role' => 'vortex_founder'));
        foreach ($founders as $founder) {
            $admin_emails[] = $founder->user_email;
        }
        
        // Prepare email content
        $subject = "[VORTEX DAO] $alert_type Alert";
        $message = "An AI alert has been generated for proposal #$proposal_id.\n\n";
        $message .= "Alert Type: $alert_type\n";
        $message .= "Please review this proposal immediately at: " . home_url("/dao/proposal/$proposal_id") . "\n\n";
        $message .= "This alert was automatically generated by the VORTEX AI system.";
        
        // Send email
        wp_mail($admin_emails, $subject, $message);
    }
    
    /**
     * Update DAO metrics after proposal execution
     *
     * @param int   $proposal_id The proposal ID
     * @param array $impact_analysis The impact analysis
     */
    private function update_dao_metrics_after_execution($proposal_id, $impact_analysis) {
        // Record a metrics snapshot with updated values
        $dao_metrics = VORTEX_DAO_Metrics::get_instance();
        $dao_metrics->record_metrics('proposal');
    }
    
    /**
     * AJAX handler to get governance insights
     */
    public function ajax_get_governance_insights() {
        check_ajax_referer('vortex_dao_nonce', 'nonce');
        
        $category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
        
        global $wpdb;
        
        $where = "insight_type = 'governance'";
        if (!empty($category)) {
            $where .= $wpdb->prepare(" AND insight_category = %s", $category);
        }
        
        $insights = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}vortex_agent_insights
            WHERE $where
            ORDER BY created_at DESC
            LIMIT $limit",
            ARRAY_A
        );
        
        foreach ($insights as &$insight) {
            $insight['time_ago'] = human_time_diff(strtotime($insight['created_at']), current_time('timestamp')) . ' ago';
            
            if ($insight['content']) {
                $insight['content'] = wp_kses_post($insight['content']);
            }
        }
        
        wp_send_json_success(array(
            'insights' => $insights,
            'count' => count($insights)
        ));
    }
}

// Initialize the integration
$vortex_dao_ai_integration = VORTEX_DAO_AI_Integration::get_instance(); 