<?php
/**
 * VORTEX AI Governance Advisor Module
 *
 * Provides AI-powered governance features for the VORTEX DAO
 *
 * @package    VORTEX_Marketplace
 * @subpackage VORTEX_Marketplace/includes/ai
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * The AI Governance Advisor class.
 *
 * Implements AI-powered governance tools including proposal analysis,
 * voting recommendations, and governance analytics.
 *
 * @since      1.0.0
 * @package    VORTEX_Marketplace
 * @subpackage VORTEX_Marketplace/includes/ai
 */
class VORTEX_AI_Governance_Advisor {

    /**
     * The DAO manager instance.
     *
     * @since  1.0.0
     * @access private
     * @var    VORTEX_DAO_Manager    $dao_manager    The DAO manager instance.
     */
    private $dao_manager;

    /**
     * The token instance.
     *
     * @since  1.0.0
     * @access private
     * @var    VORTEX_DAO_Token    $token    The token instance.
     */
    private $token;

    /**
     * Initialize the class and set its properties.
     *
     * @since 1.0.0
     */
    public function __construct() {
        // Get DAO manager instance
        $this->dao_manager = VORTEX_DAO_Manager::get_instance();
        
        // Get token instance
        $this->token = VORTEX_DAO_Token::get_instance();
        
        // Register AJAX handlers
        add_action('wp_ajax_vortex_ai_analyze_proposal', array($this, 'ajax_analyze_proposal'));
        add_action('wp_ajax_vortex_ai_get_voting_recommendations', array($this, 'ajax_get_voting_recommendations'));
        add_action('wp_ajax_vortex_ai_get_governance_analytics', array($this, 'ajax_get_governance_analytics'));
        
        // Register REST API endpoints
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // Add Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('vortex_dao_after_proposal_content', array($this, 'render_ai_insights_button'), 10, 1);
        add_action('vortex_dao_dashboard_tabs', array($this, 'add_ai_governance_dashboard_tab'));
        add_action('vortex_dao_dashboard_content', array($this, 'render_ai_governance_dashboard'));
    }

    /**
     * Enqueue scripts and styles.
     *
     * @since 1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_style('vortex-ai-governance', plugin_dir_url(__FILE__) . '../../assets/css/vortex-ai-governance.css', array(), VORTEX_MARKETPLACE_VERSION);
        wp_enqueue_script('vortex-ai-governance', plugin_dir_url(__FILE__) . '../../assets/js/vortex-ai-governance.js', array('jquery'), VORTEX_MARKETPLACE_VERSION, true);
        
        // Localize script with data and translations
        wp_localize_script('vortex-ai-governance', 'vortexAiGov', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'rest_url' => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('vortex_ai_governance_nonce'),
            'wp_rest_nonce' => wp_create_nonce('wp_rest'),
            'proposal_url' => home_url('/proposal/'),
            'i18n' => array(
                'analyzing' => __('Analyzing proposal...', 'vortex-marketplace'),
                'loading' => __('Loading recommendations...', 'vortex-marketplace'),
                'error' => __('An error occurred. Please try again.', 'vortex-marketplace'),
                'no_recommendations' => __('No active proposals found for recommendations.', 'vortex-marketplace'),
                'alignment_score' => __('Alignment Score', 'vortex-marketplace'),
                'key_considerations' => __('Key Considerations', 'vortex-marketplace'),
                'recommend_approve' => __('Recommended: Approve', 'vortex-marketplace'),
                'recommend_reject' => __('Recommended: Reject', 'vortex-marketplace'),
                'recommend_review' => __('Recommended: Review Carefully', 'vortex-marketplace'),
                'view_proposal' => __('View Proposal', 'vortex-marketplace'),
                'health_score' => __('Health Score', 'vortex-marketplace'),
                'participation_rate' => __('Participation Rate', 'vortex-marketplace'),
                'success_rate' => __('Success Rate', 'vortex-marketplace'),
                'active_participants' => __('Active Participants', 'vortex-marketplace'),
                'active_proposers' => __('Active Proposers', 'vortex-marketplace'),
                'participation_forecast' => __('Participation Forecast', 'vortex-marketplace'),
                'proposal_volume' => __('Proposal Volume', 'vortex-marketplace'),
                'token_distribution' => __('Token Distribution', 'vortex-marketplace'),
                'governance_health' => __('Governance Health', 'vortex-marketplace'),
                'probability' => __('Probability', 'vortex-marketplace'),
                'impact' => __('Impact', 'vortex-marketplace'),
                'complexity' => __('Complexity', 'vortex-marketplace'),
                'select_proposal' => __('Please select a proposal to analyze.', 'vortex-marketplace'),
                'prediction' => __('Prediction', 'vortex-marketplace'),
                'confidence' => __('Confidence', 'vortex-marketplace'),
                'current_status' => __('Current Status', 'vortex-marketplace'),
                'projected_outcome' => __('Projected Outcome', 'vortex-marketplace'),
                'yes' => __('Yes', 'vortex-marketplace'),
                'no' => __('No', 'vortex-marketplace'),
                'abstain' => __('Abstain', 'vortex-marketplace'),
                'quorum' => __('Quorum', 'vortex-marketplace'),
                'days_remaining' => __('Days Remaining', 'vortex-marketplace'),
                'projected_participation' => __('Projected Participation', 'vortex-marketplace'),
                'quorum_status' => __('Quorum Status', 'vortex-marketplace'),
                'will_reach_quorum' => __('Will reach quorum', 'vortex-marketplace'),
                'wont_reach_quorum' => __('Will not reach quorum', 'vortex-marketplace'),
                'key_factors' => __('Key Factors', 'vortex-marketplace'),
                'pass' => __('PASS', 'vortex-marketplace'),
                'fail' => __('FAIL', 'vortex-marketplace'),
                'no_quorum' => __('NO QUORUM', 'vortex-marketplace')
            )
        ));
    }

    /**
     * AJAX handler for analyzing a proposal
     *
     * @since 1.0.0
     */
    public function ajax_analyze_proposal() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_ai_governance_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'vortex-marketplace')));
        }

        // Check if user can vote
        if (!$this->token->user_can_vote()) {
            wp_send_json_error(array('message' => __('You do not have sufficient tokens to access AI insights.', 'vortex-marketplace')));
        }

        // Get proposal ID
        $proposal_id = isset($_POST['proposal_id']) ? intval($_POST['proposal_id']) : 0;
        if (!$proposal_id) {
            wp_send_json_error(array('message' => __('Invalid proposal.', 'vortex-marketplace')));
        }

        // Get proposal data
        $proposal = $this->dao_manager->get_proposal($proposal_id);
        if (!$proposal) {
            wp_send_json_error(array('message' => __('Proposal not found.', 'vortex-marketplace')));
        }

        // Generate AI analysis
        $analysis = $this->generate_proposal_analysis($proposal);

        wp_send_json_success(array(
            'analysis' => $analysis,
        ));
    }

    /**
     * AJAX handler for getting voting recommendations
     *
     * @since 1.0.0
     */
    public function ajax_get_voting_recommendations() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_ai_governance_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'vortex-marketplace')));
        }

        // Check if user can vote
        if (!$this->token->user_can_vote()) {
            wp_send_json_error(array('message' => __('You do not have sufficient tokens to access voting recommendations.', 'vortex-marketplace')));
        }

        // Get user ID
        $user_id = get_current_user_id();
        
        // Get open proposals
        $proposals = $this->dao_manager->get_proposals(array(
            'status' => 'open',
        ));

        // Generate recommendations
        $recommendations = $this->generate_voting_recommendations($user_id, $proposals);

        wp_send_json_success(array(
            'recommendations' => $recommendations,
        ));
    }

    /**
     * AJAX handler for getting governance analytics
     *
     * @since 1.0.0
     */
    public function ajax_get_governance_analytics() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_ai_governance_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'vortex-marketplace')));
        }

        // Check if user has admin or moderator role
        if (!current_user_can('administrator') && !$this->dao_manager->is_dao_moderator()) {
            wp_send_json_error(array('message' => __('You do not have permission to access governance analytics.', 'vortex-marketplace')));
        }

        // Generate analytics data
        $analytics = $this->generate_governance_analytics();

        wp_send_json_success(array(
            'analytics' => $analytics,
        ));
    }

    /**
     * Generate AI-powered proposal analysis
     *
     * @param array $proposal The proposal data
     * @return array The analysis data
     */
    private function generate_proposal_analysis($proposal) {
        // In a production environment, this would call an AI service
        // For demo purposes, we'll generate a structured analysis
        
        $key_points = array(
            __('The proposal aims to allocate resources for platform development.', 'vortex-marketplace'),
            __('Implementation timeline is within reasonable parameters.', 'vortex-marketplace'),
            __('Budget allocation follows historical precedents for similar initiatives.', 'vortex-marketplace'),
        );
        
        $potential_impact = array(
            'community' => array(
                'score' => rand(65, 95),
                'description' => __('Moderate to high positive impact on community engagement.', 'vortex-marketplace'),
            ),
            'technical' => array(
                'score' => rand(70, 90),
                'description' => __('Significant technical improvements expected.', 'vortex-marketplace'),
            ),
            'financial' => array(
                'score' => rand(60, 85),
                'description' => __('Potential ROI within 6-8 months after implementation.', 'vortex-marketplace'),
            ),
        );
        
        $historical_context = array(
            __('Similar proposals have been implemented successfully in the past.', 'vortex-marketplace'),
            __('Previous iterations had an average approval rate of 78%.', 'vortex-marketplace'),
        );
        
        return array(
            'key_points' => $key_points,
            'potential_impact' => $potential_impact,
            'historical_context' => $historical_context,
            'overall_assessment' => __('This proposal aligns with the DAO\'s strategic objectives and shows promise for improving platform functionality.', 'vortex-marketplace'),
            'confidence_score' => rand(75, 90),
            'generated_at' => current_time('mysql'),
        );
    }

    /**
     * Generate personalized voting recommendations
     *
     * @param int $user_id The user ID
     * @param array $proposals The open proposals
     * @return array The recommendations data
     */
    private function generate_voting_recommendations($user_id, $proposals) {
        // In a production environment, this would use AI to analyze user voting history
        // and generate personalized recommendations based on user preferences
        $recommendations = array();
        
        foreach ($proposals as $proposal) {
            // Get user's past voting history
            $user_voting_history = $this->dao_manager->get_user_voting_history($user_id);
            
            // Calculate alignment score based on user's past votes and proposal content
            $alignment_score = $this->calculate_proposal_alignment($proposal, $user_voting_history);
            
            $recommendations[] = array(
                'proposal_id' => $proposal->ID,
                'title' => $proposal->post_title,
                'alignment_score' => $alignment_score,
                'recommendation' => $alignment_score > 70 ? 'approve' : ($alignment_score < 40 ? 'reject' : 'review'),
                'reasoning' => $this->generate_recommendation_reasoning($proposal, $alignment_score),
                'key_considerations' => $this->generate_key_considerations($proposal),
            );
        }
        
        return $recommendations;
    }

    /**
     * Calculate proposal alignment score
     *
     * @param object $proposal The proposal object
     * @param array $user_voting_history The user's voting history
     * @return int The alignment score (0-100)
     */
    private function calculate_proposal_alignment($proposal, $user_voting_history) {
        // In a production environment, this would use more sophisticated algorithms
        // For demo purposes, we'll return a random score between 30 and 95
        return rand(30, 95);
    }

    /**
     * Generate recommendation reasoning
     *
     * @param object $proposal The proposal object
     * @param int $alignment_score The alignment score
     * @return string The recommendation reasoning
     */
    private function generate_recommendation_reasoning($proposal, $alignment_score) {
        if ($alignment_score > 70) {
            return __('This proposal aligns well with your past voting patterns and interests. It addresses areas you have historically supported.', 'vortex-marketplace');
        } elseif ($alignment_score < 40) {
            return __('This proposal contains elements that conflict with your previous voting preferences. Consider reviewing carefully before voting.', 'vortex-marketplace');
        } else {
            return __('This proposal has mixed alignment with your voting history. We recommend reviewing details carefully before deciding.', 'vortex-marketplace');
        }
    }

    /**
     * Generate key considerations for a proposal
     *
     * @param object $proposal The proposal object
     * @return array The key considerations
     */
    private function generate_key_considerations($proposal) {
        // Sample considerations - would be AI-generated in production
        return array(
            __('Budget impact on treasury reserves', 'vortex-marketplace'),
            __('Technical feasibility within proposed timeline', 'vortex-marketplace'),
            __('Community consensus on prioritization', 'vortex-marketplace'),
        );
    }

    /**
     * Generate governance analytics data
     *
     * @return array The analytics data
     */
    private function generate_governance_analytics() {
        // Get historical proposals data
        $proposals = $this->dao_manager->get_proposals(array(
            'posts_per_page' => -1,
        ));
        
        // Calculate participation trends
        $participation_trends = $this->calculate_participation_trends($proposals);
        
        // Calculate voting distribution
        $voting_distribution = $this->calculate_voting_distribution($proposals);
        
        // Calculate proposal success rates
        $proposal_success_rates = $this->calculate_proposal_success_rates($proposals);
        
        // Identify active governance participants
        $active_participants = $this->identify_active_participants($proposals);
        
        return array(
            'participation_trends' => $participation_trends,
            'voting_distribution' => $voting_distribution,
            'proposal_success_rates' => $proposal_success_rates,
            'active_participants' => $active_participants,
            'governance_health_score' => $this->calculate_governance_health_score(
                $participation_trends,
                $voting_distribution,
                $proposal_success_rates
            ),
            'generated_at' => current_time('mysql'),
        );
    }

    /**
     * Calculate participation trends
     *
     * @param array $proposals The proposals data
     * @return array The participation trends data
     */
    private function calculate_participation_trends($proposals) {
        // In production, this would analyze real voting data
        // For demo purposes, we'll return sample data
        
        return array(
            'overall_participation_rate' => rand(25, 65),
            'trend' => rand(-10, 10),
            'monthly_data' => array(
                array('month' => date('M Y', strtotime('-5 months')), 'participation' => rand(20, 60)),
                array('month' => date('M Y', strtotime('-4 months')), 'participation' => rand(20, 60)),
                array('month' => date('M Y', strtotime('-3 months')), 'participation' => rand(20, 60)),
                array('month' => date('M Y', strtotime('-2 months')), 'participation' => rand(20, 60)),
                array('month' => date('M Y', strtotime('-1 month')), 'participation' => rand(20, 60)),
                array('month' => date('M Y'), 'participation' => rand(20, 60)),
            ),
        );
    }

    /**
     * Calculate voting distribution
     *
     * @param array $proposals The proposals data
     * @return array The voting distribution data
     */
    private function calculate_voting_distribution($proposals) {
        // Distribution of voting by token holding size
        return array(
            'by_token_holdings' => array(
                'whale' => rand(20, 40), // >100k tokens
                'large' => rand(15, 30), // 10k-100k tokens
                'medium' => rand(20, 35), // 1k-10k tokens
                'small' => rand(10, 25), // <1k tokens
            ),
            'by_proposal_type' => array(
                'technical' => rand(40, 70),
                'financial' => rand(50, 80),
                'governance' => rand(30, 60),
                'community' => rand(20, 50),
            ),
        );
    }

    /**
     * Calculate proposal success rates
     *
     * @param array $proposals The proposals data
     * @return array The proposal success rates data
     */
    private function calculate_proposal_success_rates($proposals) {
        return array(
            'overall_success_rate' => rand(60, 85),
            'by_category' => array(
                'technical' => rand(60, 90),
                'financial' => rand(50, 80),
                'governance' => rand(65, 85),
                'community' => rand(70, 95),
            ),
            'by_proposer_type' => array(
                'core_team' => rand(75, 95),
                'community_moderator' => rand(65, 85),
                'regular_member' => rand(50, 75),
                'new_member' => rand(30, 60),
            ),
        );
    }

    /**
     * Identify active governance participants
     *
     * @param array $proposals The proposals data
     * @return array The active participants data
     */
    private function identify_active_participants($proposals) {
        // In production, this would analyze real participation data
        return array(
            'total_participants' => rand(50, 200),
            'repeat_voters' => rand(30, 150),
            'one_time_voters' => rand(20, 50),
            'proposers' => rand(10, 30),
            'commenters' => rand(30, 100),
        );
    }

    /**
     * Calculate governance health score
     *
     * @param array $participation_trends The participation trends data
     * @param array $voting_distribution The voting distribution data
     * @param array $proposal_success_rates The proposal success rates data
     * @return array The governance health score data
     */
    private function calculate_governance_health_score($participation_trends, $voting_distribution, $proposal_success_rates) {
        $participation_score = $participation_trends['overall_participation_rate'];
        $distribution_score = 100 - abs($voting_distribution['by_token_holdings']['whale'] - 25);
        $success_score = $proposal_success_rates['overall_success_rate'];
        
        $overall_score = ($participation_score + $distribution_score + $success_score) / 3;
        
        return array(
            'score' => round($overall_score),
            'assessment' => $this->get_health_assessment($overall_score),
            'recommendations' => $this->get_health_recommendations($participation_score, $distribution_score, $success_score),
        );
    }

    /**
     * Get health assessment based on score
     *
     * @param int $score The health score
     * @return string The assessment
     */
    private function get_health_assessment($score) {
        if ($score >= 80) {
            return __('Excellent. The DAO shows strong governance participation and balanced decision-making.', 'vortex-marketplace');
        } elseif ($score >= 60) {
            return __('Good. The DAO has healthy governance processes with some areas for improvement.', 'vortex-marketplace');
        } elseif ($score >= 40) {
            return __('Fair. Several governance metrics indicate potential concerns that should be addressed.', 'vortex-marketplace');
        } else {
            return __('Needs attention. Governance processes show significant imbalances or low participation.', 'vortex-marketplace');
        }
    }

    /**
     * Get health recommendations based on component scores
     *
     * @param int $participation_score The participation score
     * @param int $distribution_score The distribution score
     * @param int $success_score The success score
     * @return array The recommendations
     */
    private function get_health_recommendations($participation_score, $distribution_score, $success_score) {
        $recommendations = array();
        
        if ($participation_score < 50) {
            $recommendations[] = __('Consider incentivizing participation in governance voting.', 'vortex-marketplace');
        }
        
        if ($distribution_score < 60) {
            $recommendations[] = __('Review voting power distribution to ensure balanced decision-making.', 'vortex-marketplace');
        }
        
        if ($success_score < 60) {
            $recommendations[] = __('Analyze rejected proposals to identify common issues and improve proposal quality.', 'vortex-marketplace');
        }
        
        if (empty($recommendations)) {
            $recommendations[] = __('Maintain current governance practices while monitoring for changes.', 'vortex-marketplace');
        }
        
        return $recommendations;
    }

    /**
     * Render AI insights button for proposals
     *
     * @param int $proposal_id The proposal ID
     */
    public function render_ai_insights_button($proposal_id) {
        if (!$this->token->user_can_vote()) {
            return;
        }
        
        ?>
        <div class="vortex-ai-insights-container">
            <button class="vortex-ai-analyze-btn" data-proposal-id="<?php echo esc_attr($proposal_id); ?>">
                <span class="dashicons dashicons-lightbulb"></span>
                <?php _e('AI Insights', 'vortex-marketplace'); ?>
            </button>
            <div class="vortex-ai-analysis-results" id="vortex-ai-analysis-<?php echo esc_attr($proposal_id); ?>" style="display: none;">
                <div class="vortex-ai-analysis-loading">
                    <?php _e('Analyzing proposal...', 'vortex-marketplace'); ?>
                </div>
                <div class="vortex-ai-analysis-content"></div>
            </div>
        </div>
        <?php
    }

    /**
     * Add AI governance dashboard tab
     *
     * @param array $tabs The current tabs
     * @return array The updated tabs
     */
    public function add_ai_governance_dashboard_tab($tabs) {
        if ($this->token->user_can_vote()) {
            $tabs['ai_governance'] = __('AI Governance', 'vortex-marketplace');
        }
        return $tabs;
    }

    /**
     * Render AI governance dashboard
     *
     * @param string $current_tab The current tab
     */
    public function render_ai_governance_dashboard($current_tab) {
        if ($current_tab !== 'ai_governance' || !$this->token->user_can_vote()) {
            return;
        }
        
        ?>
        <div class="vortex-ai-governance-dashboard">
            <div class="vortex-ai-governance-header">
                <h2><?php _e('AI Governance Dashboard', 'vortex-marketplace'); ?></h2>
                <p><?php _e('Get AI-powered insights and recommendations for DAO governance.', 'vortex-marketplace'); ?></p>
            </div>
            
            <!-- Tab Navigation -->
            <div class="vortex-ai-tabs">
                <div class="vortex-ai-tab-nav">
                    <a href="#" class="vortex-ai-tab-link" data-tab="vortex-ai-recommendations-tab">
                        <span class="dashicons dashicons-thumbs-up"></span>
                        <?php _e('Voting Recommendations', 'vortex-marketplace'); ?>
                    </a>
                    <a href="#" class="vortex-ai-tab-link" data-tab="vortex-ai-predictions-tab">
                        <span class="dashicons dashicons-chart-area"></span>
                        <?php _e('Proposal Predictions', 'vortex-marketplace'); ?>
                    </a>
                    <?php if (current_user_can('administrator') || $this->dao_manager->is_dao_moderator()): ?>
                    <a href="#" class="vortex-ai-tab-link" data-tab="vortex-ai-analytics-tab">
                        <span class="dashicons dashicons-chart-bar"></span>
                        <?php _e('Governance Analytics', 'vortex-marketplace'); ?>
                    </a>
                    <a href="#" class="vortex-ai-tab-link" data-tab="vortex-ai-predictive-tab">
                        <span class="dashicons dashicons-visibility"></span>
                        <?php _e('Predictive Analytics', 'vortex-marketplace'); ?>
                    </a>
                    <?php endif; ?>
                </div>
                
                <!-- Recommendations Tab -->
                <div id="vortex-ai-recommendations-tab" class="vortex-ai-tab-content">
                    <div class="vortex-ai-section vortex-ai-recommendations">
                        <h3><?php _e('Personalized Voting Recommendations', 'vortex-marketplace'); ?></h3>
                        <div class="vortex-ai-section-content" id="vortex-ai-recommendations-content">
                            <div class="vortex-ai-loading">
                                <?php _e('Loading recommendations...', 'vortex-marketplace'); ?>
                            </div>
                        </div>
                        <button class="vortex-ai-refresh-btn" data-target="recommendations">
                            <span class="dashicons dashicons-update"></span>
                            <?php _e('Refresh', 'vortex-marketplace'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Proposal Predictions Tab -->
                <div id="vortex-ai-predictions-tab" class="vortex-ai-tab-content">
                    <div class="vortex-ai-section">
                        <h3><?php _e('Proposal Outcome Predictions', 'vortex-marketplace'); ?></h3>
                        
                        <form id="vortex-proposal-prediction-form" class="vortex-prediction-form">
                            <div class="vortex-form-row">
                                <div class="vortex-form-col">
                                    <label for="proposal_id" class="vortex-form-label">
                                        <?php _e('Select Proposal', 'vortex-marketplace'); ?>
                                    </label>
                                    <select id="proposal_id" name="proposal_id" class="vortex-form-select" required>
                                        <option value=""><?php _e('-- Select a proposal --', 'vortex-marketplace'); ?></option>
                                        <?php
                                        $proposals = $this->dao_manager->get_proposals(array(
                                            'status' => 'publish',
                                            'posts_per_page' => -1
                                        ));
                                        
                                        if ($proposals) {
                                            foreach ($proposals as $proposal) {
                                                echo '<option value="' . esc_attr($proposal->ID) . '">' . esc_html($proposal->post_title) . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="vortex-form-row">
                                <div class="vortex-form-col">
                                    <label for="participation_modifier" class="vortex-form-label">
                                        <?php _e('Participation Modifier', 'vortex-marketplace'); ?>
                                    </label>
                                    <input type="number" id="participation_modifier" name="participation_modifier" 
                                           class="vortex-form-input" min="0.5" max="2" step="0.1" placeholder="1.0">
                                    <small><?php _e('Optional: Adjust expected participation (0.5-2.0)', 'vortex-marketplace'); ?></small>
                                </div>
                                
                                <div class="vortex-form-col">
                                    <label for="sentiment_modifier" class="vortex-form-label">
                                        <?php _e('Sentiment Modifier', 'vortex-marketplace'); ?>
                                    </label>
                                    <input type="number" id="sentiment_modifier" name="sentiment_modifier" 
                                           class="vortex-form-input" min="-0.3" max="0.3" step="0.05" placeholder="0.0">
                                    <small><?php _e('Optional: Adjust sentiment (-0.3 to 0.3)', 'vortex-marketplace'); ?></small>
                                </div>
                            </div>
                            
                            <div class="vortex-form-row">
                                <div class="vortex-form-col">
                                    <button type="submit" class="vortex-form-submit">
                                        <?php _e('Predict Outcome', 'vortex-marketplace'); ?>
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <div id="prediction-result">
                            <div class="vortex-ai-no-data">
                                <?php _e('Select a proposal above to see outcome prediction', 'vortex-marketplace'); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (current_user_can('administrator') || $this->dao_manager->is_dao_moderator()): ?>
                <!-- Governance Analytics Tab -->
                <div id="vortex-ai-analytics-tab" class="vortex-ai-tab-content">
                    <div class="vortex-ai-section vortex-ai-analytics">
                        <h3><?php _e('Governance Analytics', 'vortex-marketplace'); ?></h3>
                        <div class="vortex-ai-section-content" id="vortex-ai-analytics-content">
                            <div class="vortex-ai-loading">
                                <?php _e('Loading analytics...', 'vortex-marketplace'); ?>
                            </div>
                        </div>
                        <button class="vortex-ai-refresh-btn" data-target="analytics">
                            <span class="dashicons dashicons-update"></span>
                            <?php _e('Refresh', 'vortex-marketplace'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Predictive Analytics Tab -->
                <div id="vortex-ai-predictive-tab" class="vortex-ai-tab-content">
                    <div class="vortex-ai-section">
                        <h3><?php _e('Governance Predictive Analytics', 'vortex-marketplace'); ?></h3>
                        
                        <div id="vortex-ai-predictive-analytics">
                            <div class="vortex-ai-loading">
                                <?php _e('Loading predictive analytics...', 'vortex-marketplace'); ?>
                            </div>
                        </div>
                        
                        <div class="vortex-predictive-sections">
                            <div class="vortex-predictive-section">
                                <h4><?php _e('Predicted Governance Challenges', 'vortex-marketplace'); ?></h4>
                                <div id="governance-challenges">
                                    <div class="vortex-ai-loading">
                                        <?php _e('Loading challenges...', 'vortex-marketplace'); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="vortex-predictive-section">
                                <h4><?php _e('Governance Improvement Opportunities', 'vortex-marketplace'); ?></h4>
                                <div id="governance-opportunities">
                                    <div class="vortex-ai-loading">
                                        <?php _e('Loading opportunities...', 'vortex-marketplace'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Register REST API routes for AI Governance features
     *
     * @since 1.0.0
     */
    public function register_rest_routes() {
        // Register governance predictive analytics endpoint
        register_rest_route('vortex-marketplace/v1', '/ai/governance/predictive-analytics', array(
            'methods'  => 'GET',
            'callback' => array($this, 'get_predictive_analytics'),
            'permission_callback' => array($this, 'check_api_permissions')
        ));
        
        // Register proposal outcome prediction endpoint
        register_rest_route('vortex-marketplace/v1', '/ai/governance/predict-outcome', array(
            'methods'  => 'POST',
            'callback' => array($this, 'predict_proposal_outcome'),
            'permission_callback' => array($this, 'check_api_permissions'),
            'args' => array(
                'proposal_id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
                'simulation_factors' => array(
                    'type' => 'array',
                    'default' => array()
                )
            )
        ));
        
        // Register voter behavior analysis endpoint
        register_rest_route('vortex-marketplace/v1', '/ai/governance/voter-behavior', array(
            'methods'  => 'GET',
            'callback' => array($this, 'get_voter_behavior_analysis'),
            'permission_callback' => array($this, 'check_api_permissions')
        ));
        
        // Register governance health forecast endpoint
        register_rest_route('vortex-marketplace/v1', '/ai/governance/health-forecast', array(
            'methods'  => 'GET',
            'callback' => array($this, 'get_governance_health_forecast'),
            'permission_callback' => array($this, 'check_api_permissions')
        ));
    }
    
    /**
     * Check API permissions
     *
     * @param WP_REST_Request $request The request object
     * @return bool Whether the user has permission
     */
    public function check_api_permissions($request) {
        // Allow access for administrators and DAO moderators
        if (current_user_can('administrator') || $this->dao_manager->is_dao_moderator()) {
            return true;
        }
        
        // For regular users, check if they have sufficient tokens to access AI insights
        return $this->token->user_can_vote();
    }
    
    /**
     * REST API endpoint for predictive analytics
     *
     * @param WP_REST_Request $request The request object
     * @return WP_REST_Response The response
     */
    public function get_predictive_analytics($request) {
        // Generate predictive analytics data
        $analytics = $this->generate_predictive_analytics();
        
        return rest_ensure_response(array(
            'success' => true,
            'data' => $analytics
        ));
    }
    
    /**
     * REST API endpoint for predicting proposal outcome
     *
     * @param WP_REST_Request $request The request object
     * @return WP_REST_Response The response
     */
    public function predict_proposal_outcome($request) {
        $proposal_id = $request->get_param('proposal_id');
        $simulation_factors = $request->get_param('simulation_factors');
        
        // Get proposal data
        $proposal = $this->dao_manager->get_proposal($proposal_id);
        if (!$proposal) {
            return new WP_Error(
                'invalid_proposal',
                __('Proposal not found', 'vortex-marketplace'),
                array('status' => 404)
            );
        }
        
        // Generate prediction
        $prediction = $this->predict_outcome($proposal, $simulation_factors);
        
        return rest_ensure_response(array(
            'success' => true,
            'data' => $prediction
        ));
    }
    
    /**
     * REST API endpoint for voter behavior analysis
     *
     * @param WP_REST_Request $request The request object
     * @return WP_REST_Response The response
     */
    public function get_voter_behavior_analysis($request) {
        // Generate voter behavior analysis
        $analysis = $this->analyze_voter_behavior();
        
        return rest_ensure_response(array(
            'success' => true,
            'data' => $analysis
        ));
    }
    
    /**
     * REST API endpoint for governance health forecast
     *
     * @param WP_REST_Request $request The request object
     * @return WP_REST_Response The response
     */
    public function get_governance_health_forecast($request) {
        // Generate governance health forecast
        $forecast = $this->forecast_governance_health();
        
        return rest_ensure_response(array(
            'success' => true,
            'data' => $forecast
        ));
    }

    /**
     * Generate predictive analytics for governance
     *
     * @return array Predictive analytics data
     */
    private function generate_predictive_analytics() {
        // In a production environment, this would use ML/AI to analyze historical data
        // and make predictions based on patterns and trends
        
        // Get historical proposals data
        $proposals = $this->dao_manager->get_proposals(array(
            'posts_per_page' => -1,
        ));
        
        // Calculate trend prediction data
        $participation_forecast = $this->predict_participation_trend($proposals);
        $proposal_volume_forecast = $this->predict_proposal_volume($proposals);
        $token_distribution_forecast = $this->predict_token_distribution();
        
        return array(
            'participation_forecast' => $participation_forecast,
            'proposal_volume_forecast' => $proposal_volume_forecast,
            'token_distribution_forecast' => $token_distribution_forecast,
            'predicted_governance_challenges' => $this->identify_future_challenges(),
            'opportunity_areas' => $this->identify_opportunity_areas(),
            'confidence_metrics' => array(
                'participation_confidence' => rand(70, 90),
                'proposal_volume_confidence' => rand(65, 85),
                'token_distribution_confidence' => rand(60, 80)
            ),
            'next_actions' => $this->generate_next_actions(),
            'generated_at' => current_time('mysql')
        );
    }
    
    /**
     * Predict participation trend
     *
     * @param array $proposals Historical proposals
     * @return array Participation forecast data
     */
    private function predict_participation_trend($proposals) {
        // In production, this would use time series forecasting
        // Based on historical participation patterns
        
        // For demo, we'll generate some plausible projections
        $current_participation = rand(25, 65);
        
        // Generate a trend line with some variability but overall direction
        $trend_direction = (rand(0, 100) > 70) ? 'increasing' : 'decreasing';
        $trend_strength = rand(5, 15) / 10; // 0.5 to 1.5
        
        $monthly_forecast = array();
        $current_value = $current_participation;
        
        // Generate 6 months of forecast data
        for ($i = 1; $i <= 6; $i++) {
            // Add some randomness to the trend
            $random_factor = (rand(-50, 50) / 100); // -0.5 to 0.5
            
            if ($trend_direction === 'increasing') {
                $current_value = min(95, $current_value + ($trend_strength + $random_factor));
            } else {
                $current_value = max(15, $current_value - ($trend_strength + $random_factor));
            }
            
            $monthly_forecast[] = array(
                'month' => date('M Y', strtotime("+$i month")),
                'projected_participation' => round($current_value, 1),
                'confidence_interval' => array(
                    'lower' => max(0, round($current_value - rand(5, 15), 1)),
                    'upper' => min(100, round($current_value + rand(5, 15), 1))
                )
            );
        }
        
        return array(
            'current_participation' => $current_participation,
            'trend_direction' => $trend_direction,
            'monthly_forecast' => $monthly_forecast,
            'factors_influencing_trend' => array(
                array('factor' => __('Token holder engagement initiatives', 'vortex-marketplace'), 'impact' => 'high'),
                array('factor' => __('Proposal quality and relevance', 'vortex-marketplace'), 'impact' => 'medium'),
                array('factor' => __('Governance UI/UX improvements', 'vortex-marketplace'), 'impact' => 'medium'),
                array('factor' => __('Token price fluctuations', 'vortex-marketplace'), 'impact' => 'low')
            )
        );
    }
    
    /**
     * Predict proposal volume
     *
     * @param array $proposals Historical proposals
     * @return array Proposal volume forecast
     */
    private function predict_proposal_volume($proposals) {
        // For demo purposes, we'll generate plausible projections
        
        // Generate category-based forecast with some randomness
        $categories = array('technical', 'financial', 'governance', 'community');
        $forecast_by_category = array();
        
        foreach ($categories as $category) {
            $forecast_by_category[$category] = rand(3, 12);
        }
        
        // Generate monthly forecast with some trend
        $monthly_forecast = array();
        $current_monthly_volume = array_sum($forecast_by_category);
        
        for ($i = 1; $i <= 6; $i++) {
            // Add some randomness, leaning slightly upward
            $adjustment = (rand(-20, 30) / 100); // -0.2 to 0.3
            $forecast_volume = max(1, round($current_monthly_volume * (1 + $adjustment)));
            
            $monthly_forecast[] = array(
                'month' => date('M Y', strtotime("+$i month")),
                'projected_volume' => $forecast_volume
            );
            
            // Update for next month
            $current_monthly_volume = $forecast_volume;
        }
        
        return array(
            'current_monthly_volume' => array_sum($forecast_by_category) / 4, // Average per month
            'forecast_by_category' => $forecast_by_category,
            'monthly_forecast' => $monthly_forecast,
            'seasonality_pattern' => __('Higher proposal volumes typically follow major platform updates and token value increases', 'vortex-marketplace')
        );
    }
    
    /**
     * Predict token distribution changes
     *
     * @return array Token distribution forecast
     */
    private function predict_token_distribution() {
        // Generate current distribution data
        $current_distribution = array(
            'whale' => rand(20, 40), // >100k tokens
            'large' => rand(15, 30), // 10k-100k tokens
            'medium' => rand(20, 35), // 1k-10k tokens
            'small' => rand(10, 25), // <1k tokens
        );
        
        // Generate projected distribution - subtle shifts based on vesting and market activity
        $projected_distribution = array(
            'whale' => max(15, $current_distribution['whale'] + rand(-5, 3)),
            'large' => max(10, $current_distribution['large'] + rand(-3, 5)),
            'medium' => max(15, $current_distribution['medium'] + rand(-2, 7)),
            'small' => max(8, $current_distribution['small'] + rand(-2, 8)),
        );
        
        // Normalize to 100%
        $total = array_sum($projected_distribution);
        foreach ($projected_distribution as &$value) {
            $value = round(($value / $total) * 100);
        }
        
        return array(
            'current_distribution' => $current_distribution,
            'projected_distribution' => $projected_distribution,
            'timeframe' => '6 months',
            'shift_factors' => array(
                array('factor' => __('Vesting schedule releases', 'vortex-marketplace'), 'impact' => 'high'),
                array('factor' => __('New investor onboarding', 'vortex-marketplace'), 'impact' => 'medium'),
                array('factor' => __('Secondary market trading', 'vortex-marketplace'), 'impact' => 'medium'),
                array('factor' => __('Governance incentive programs', 'vortex-marketplace'), 'impact' => 'low')
            ),
            'centralization_risk' => $this->calculate_centralization_risk($projected_distribution)
        );
    }
    
    /**
     * Calculate centralization risk based on token distribution
     *
     * @param array $distribution Token distribution data
     * @return array Centralization risk assessment
     */
    private function calculate_centralization_risk($distribution) {
        // Higher whale percentage = higher centralization risk
        $risk_score = min(100, $distribution['whale'] * 2.5);
        
        // Determine risk level
        $risk_level = 'low';
        if ($risk_score > 80) {
            $risk_level = 'high';
        } elseif ($risk_score > 50) {
            $risk_level = 'medium';
        }
        
        return array(
            'score' => $risk_score,
            'level' => $risk_level,
            'assessment' => $this->get_centralization_assessment($risk_level)
        );
    }
    
    /**
     * Get assessment text based on centralization risk level
     *
     * @param string $risk_level Risk level (low, medium, high)
     * @return string Assessment text
     */
    private function get_centralization_assessment($risk_level) {
        switch ($risk_level) {
            case 'high':
                return __('High concentration of voting power may lead to centralized decision-making.', 'vortex-marketplace');
            case 'medium':
                return __('Moderate concentration of tokens presents some centralization concerns.', 'vortex-marketplace');
            default:
                return __('Well-distributed voting power supports decentralized governance.', 'vortex-marketplace');
        }
    }
    
    /**
     * Identify potential future governance challenges
     *
     * @return array Future challenges
     */
    private function identify_future_challenges() {
        // In production this would be based on pattern recognition and sentiment analysis
        return array(
            array(
                'challenge' => __('Voter participation fatigue', 'vortex-marketplace'),
                'probability' => rand(50, 80),
                'impact' => 'high',
                'mitigation_strategy' => __('Implement delegation options and voting incentives.', 'vortex-marketplace')
            ),
            array(
                'challenge' => __('Governance parameter optimization', 'vortex-marketplace'),
                'probability' => rand(60, 90),
                'impact' => 'medium',
                'mitigation_strategy' => __('Regular review and iterative tuning of voting thresholds and periods.', 'vortex-marketplace')
            ),
            array(
                'challenge' => __('Cross-chain governance coordination', 'vortex-marketplace'),
                'probability' => rand(40, 70),
                'impact' => 'high',
                'mitigation_strategy' => __('Develop multi-chain governance views and synchronization mechanisms.', 'vortex-marketplace')
            ),
            array(
                'challenge' => __('Proposal quality assurance', 'vortex-marketplace'),
                'probability' => rand(50, 75),
                'impact' => 'medium',
                'mitigation_strategy' => __('Create proposal templates and pre-submission review processes.', 'vortex-marketplace')
            )
        );
    }
    
    /**
     * Identify opportunity areas for governance improvement
     *
     * @return array Opportunity areas
     */
    private function identify_opportunity_areas() {
        return array(
            array(
                'opportunity' => __('Delegated voting system', 'vortex-marketplace'),
                'potential_impact' => 'high',
                'implementation_complexity' => 'medium',
                'expected_outcome' => __('Increased participation while maintaining quality decisions', 'vortex-marketplace')
            ),
            array(
                'opportunity' => __('Governance education program', 'vortex-marketplace'),
                'potential_impact' => 'medium',
                'implementation_complexity' => 'low',
                'expected_outcome' => __('More informed voting and higher-quality proposals', 'vortex-marketplace')
            ),
            array(
                'opportunity' => __('Proposal categorization system', 'vortex-marketplace'),
                'potential_impact' => 'medium',
                'implementation_complexity' => 'low',
                'expected_outcome' => __('Better organization and discoverability of proposals', 'vortex-marketplace')
            ),
            array(
                'opportunity' => __('Token-weighted quadratic voting', 'vortex-marketplace'),
                'potential_impact' => 'high',
                'implementation_complexity' => 'high',
                'expected_outcome' => __('More balanced influence distribution while respecting token holdings', 'vortex-marketplace')
            )
        );
    }
    
    /**
     * Generate next actions based on predictive analytics
     *
     * @return array Recommended next actions
     */
    private function generate_next_actions() {
        return array(
            array(
                'action' => __('Review voting threshold parameters', 'vortex-marketplace'),
                'priority' => 'high',
                'timeframe' => __('Next 30 days', 'vortex-marketplace'),
                'expected_benefit' => __('Optimize for current participation levels', 'vortex-marketplace')
            ),
            array(
                'action' => __('Implement additional voter education resources', 'vortex-marketplace'),
                'priority' => 'medium',
                'timeframe' => __('Next 60 days', 'vortex-marketplace'),
                'expected_benefit' => __('Increase informed participation', 'vortex-marketplace')
            ),
            array(
                'action' => __('Develop proposal templates for common request types', 'vortex-marketplace'),
                'priority' => 'medium',
                'timeframe' => __('Next 45 days', 'vortex-marketplace'),
                'expected_benefit' => __('Improve proposal quality and standardization', 'vortex-marketplace')
            ),
            array(
                'action' => __('Launch governance participation incentive program', 'vortex-marketplace'),
                'priority' => 'high',
                'timeframe' => __('Next 90 days', 'vortex-marketplace'),
                'expected_benefit' => __('Increase voter participation rates', 'vortex-marketplace')
            )
        );
    }
    
    /**
     * Predict proposal outcome
     *
     * @param object $proposal The proposal to analyze
     * @param array $simulation_factors Optional factors to adjust the simulation
     * @return array Prediction results
     */
    public function predict_outcome($proposal, $simulation_factors = array()) {
        // In production, this would use ML model trained on historical proposals
        // For demo purposes, we'll create a plausible prediction
        
        // Get proposal data from proposal object
        $proposal_id = $proposal->ID;
        $proposal_title = $proposal->post_title;
        $proposal_content = $proposal->post_content;
        $proposal_type = get_post_meta($proposal_id, 'vortex_proposal_type', true);
        
        // Get current voting stats
        $yes_votes = get_post_meta($proposal_id, 'vortex_proposal_yes_votes', true) ?: 0;
        $no_votes = get_post_meta($proposal_id, 'vortex_proposal_no_votes', true) ?: 0;
        $abstain_votes = get_post_meta($proposal_id, 'vortex_proposal_abstain_votes', true) ?: 0;
        $total_votes = get_post_meta($proposal_id, 'vortex_proposal_total_votes', true) ?: 0;
        
        // Get voting end date
        $end_date = get_post_meta($proposal_id, 'vortex_proposal_end_date', true);
        $time_remaining = strtotime($end_date) - current_time('timestamp');
        $days_remaining = max(0, ceil($time_remaining / (60 * 60 * 24)));
        
        // Minimum quorum
        $min_quorum = get_option('vortex_dao_min_quorum', 100);
        
        // Calculate projected final votes based on historical patterns
        // In production this would use regression analysis on past voting patterns
        $total_eligible_votes = $this->estimate_total_eligible_votes();
        $projected_participation = $this->estimate_projected_participation($proposal_type, $days_remaining);
        $projected_total_votes = $total_eligible_votes * ($projected_participation / 100);
        
        // Apply any simulation factors
        if (!empty($simulation_factors['participation_modifier'])) {
            $projected_participation *= $simulation_factors['participation_modifier'];
            $projected_total_votes = $total_eligible_votes * ($projected_participation / 100);
        }
        
        // Predict final vote distribution
        // Start with current votes as the base
        $projected_yes = $yes_votes;
        $projected_no = $no_votes;
        $projected_abstain = $abstain_votes;
        
        // Add projected additional votes based on content analysis and voter history
        // In production, this would use NLP and historical voting patterns
        if ($projected_total_votes > $total_votes) {
            $remaining_votes = $projected_total_votes - $total_votes;
            
            // Distribution factors would be determined by ML in production
            // For demo, we'll use some base distributions with randomness
            $yes_factor = $this->get_proposal_sentiment_factor($proposal_content);
            
            // Apply any simulation factors
            if (!empty($simulation_factors['sentiment_modifier'])) {
                $yes_factor = min(1, max(0, $yes_factor + $simulation_factors['sentiment_modifier']));
            }
            
            $projected_yes += $remaining_votes * $yes_factor;
            $projected_no += $remaining_votes * (1 - $yes_factor - 0.1); // 10% abstain
            $projected_abstain += $remaining_votes * 0.1;
        }
        
        // Determine predicted outcome
        $will_reach_quorum = $projected_total_votes >= $min_quorum;
        $will_pass = $will_reach_quorum && $projected_yes > $projected_no;
        
        // Calculate confidence level
        // In production, this would be based on model confidence scores
        $confidence = $this->calculate_prediction_confidence(
            $days_remaining,
            $total_votes,
            $projected_total_votes,
            abs($projected_yes - $projected_no)
        );
        
        return array(
            'proposal_id' => $proposal_id,
            'current_status' => array(
                'yes_votes' => $yes_votes,
                'no_votes' => $no_votes,
                'abstain_votes' => $abstain_votes,
                'total_votes' => $total_votes,
                'days_remaining' => $days_remaining,
                'quorum_requirement' => $min_quorum,
                'quorum_progress' => ($total_votes / $min_quorum) * 100
            ),
            'prediction' => array(
                'projected_yes' => round($projected_yes),
                'projected_no' => round($projected_no),
                'projected_abstain' => round($projected_abstain),
                'projected_total' => round($projected_total_votes),
                'projected_participation' => round($projected_participation, 1) . '%',
                'will_reach_quorum' => $will_reach_quorum,
                'will_pass' => $will_pass,
                'projected_outcome' => $will_pass ? 'pass' : ($will_reach_quorum ? 'fail' : 'no_quorum'),
                'confidence' => $confidence . '%',
                'key_factors' => $this->identify_key_outcome_factors($proposal_type, $yes_factor, $will_reach_quorum)
            ),
            'generated_at' => current_time('mysql')
        );
    }
    
    /**
     * Estimate total eligible votes
     *
     * @return int Estimated total eligible votes
     */
    private function estimate_total_eligible_votes() {
        // In production, this would query actual eligible voters based on token holdings
        // For demo purposes, we'll return a plausible number
        return rand(500, 2000);
    }
    
    /**
     * Estimate projected participation for a proposal
     *
     * @param string $proposal_type The type of proposal
     * @param int $days_remaining Days remaining in voting period
     * @return float Projected participation percentage
     */
    private function estimate_projected_participation($proposal_type, $days_remaining) {
        // Base participation rate varies by proposal type
        $base_rates = array(
            'parameter_change' => rand(20, 35),
            'feature_request' => rand(25, 40),
            'fund_allocation' => rand(30, 50),
            'membership' => rand(25, 45),
            'custom' => rand(20, 40)
        );
        
        // Default if type not found
        $base_rate = isset($base_rates[$proposal_type]) ? $base_rates[$proposal_type] : 30;
        
        // Adjust for days remaining - participation tends to increase near the end
        $time_factor = 1 + (max(0, 7 - $days_remaining) / 10);
        
        return $base_rate * $time_factor;
    }
    
    /**
     * Get sentiment factor for a proposal based on content
     *
     * @param string $content Proposal content
     * @return float Sentiment factor (0-1 representing probability of "yes" vote)
     */
    private function get_proposal_sentiment_factor($content) {
        // In production, this would use NLP sentiment analysis
        // For demo, we'll return a plausible factor with some randomness
        
        // Words that might indicate positive or negative sentiment
        $positive_words = array('improve', 'enhance', 'benefit', 'increase', 'opportunity', 'advantage');
        $negative_words = array('risk', 'concern', 'problem', 'decrease', 'difficult', 'complex');
        
        $positive_count = 0;
        $negative_count = 0;
        
        // Simple word counting - would be much more sophisticated in production
        foreach ($positive_words as $word) {
            $positive_count += substr_count(strtolower($content), $word);
        }
        
        foreach ($negative_words as $word) {
            $negative_count += substr_count(strtolower($content), $word);
        }
        
        // Calculate a base sentiment factor
        $total_words = $positive_count + $negative_count;
        $base_factor = ($total_words > 0) ? ($positive_count / $total_words) : 0.5;
        
        // Add some randomness to simulate more complex analysis
        $random_factor = (rand(-15, 15) / 100); // -0.15 to 0.15
        
        // Ensure result is between 0.3 and 0.8 (proposals rarely get unanimous votes)
        return min(0.8, max(0.3, $base_factor + $random_factor));
    }
    
    /**
     * Calculate prediction confidence
     *
     * @param int $days_remaining Days remaining in voting period
     * @param int $current_votes Current vote count
     * @param float $projected_votes Projected final vote count
     * @param float $vote_margin Margin between yes and no votes
     * @return int Confidence percentage
     */
    private function calculate_prediction_confidence($days_remaining, $current_votes, $projected_votes, $vote_margin) {
        // Confidence decreases with more days remaining
        $time_factor = max(0.5, min(1, (7 - $days_remaining) / 7));
        
        // Confidence increases with more votes already cast
        $vote_factor = max(0.3, min(0.9, $current_votes / $projected_votes));
        
        // Confidence increases with larger margins
        $margin_factor = max(0.4, min(0.95, $vote_margin / $projected_votes * 5));
        
        // Calculate overall confidence
        $confidence = ($time_factor * 0.3) + ($vote_factor * 0.4) + ($margin_factor * 0.3);
        
        // Convert to percentage
        return round($confidence * 100);
    }
    
    /**
     * Identify key factors affecting the proposal outcome
     *
     * @param string $proposal_type Proposal type
     * @param float $sentiment_factor Sentiment factor
     * @param bool $will_reach_quorum Whether the proposal will reach quorum
     * @return array Key factors
     */
    private function identify_key_outcome_factors($proposal_type, $sentiment_factor, $will_reach_quorum) {
        $factors = array();
        
        // Quorum factor
        if (!$will_reach_quorum) {
            $factors[] = __('Low projected participation may prevent reaching quorum requirements.', 'vortex-marketplace');
        }
        
        // Sentiment factors
        if ($sentiment_factor > 0.6) {
            $factors[] = __('Proposal language is generally viewed positively by the community.', 'vortex-marketplace');
        } elseif ($sentiment_factor < 0.4) {
            $factors[] = __('Proposal contains terminology that may be perceived negatively.', 'vortex-marketplace');
        }
        
        // Type-specific factors
        switch ($proposal_type) {
            case 'parameter_change':
                $factors[] = __('Parameter change proposals typically receive more technical scrutiny.', 'vortex-marketplace');
                break;
            case 'fund_allocation':
                $factors[] = __('Fund allocation proposals often attract higher voter participation.', 'vortex-marketplace');
                break;
            case 'membership':
                $factors[] = __('Membership proposals may be subject to existing relationship dynamics.', 'vortex-marketplace');
                break;
        }
        
        // Add timing factor
        $factors[] = __('Voting patterns typically accelerate in the final days of the voting period.', 'vortex-marketplace');
        
        return $factors;
    }
    
    /**
     * Analyze voter behavior across proposals
     *
     * @return array Voter behavior analysis data
     */
    private function analyze_voter_behavior() {
        // In production, this would analyze actual voter data
        // For demonstration, we'll create plausible behavior patterns
        
        // Participation frequency segments
        $participation_segments = array(
            'super_voters' => array(
                'percentage' => rand(5, 15),
                'description' => __('Users who vote on 75% or more of all proposals', 'vortex-marketplace'),
                'avg_token_holdings' => rand(5000, 50000)
            ),
            'regular_voters' => array(
                'percentage' => rand(15, 30),
                'description' => __('Users who vote on 25-75% of proposals', 'vortex-marketplace'),
                'avg_token_holdings' => rand(1000, 10000)
            ),
            'occasional_voters' => array(
                'percentage' => rand(20, 40),
                'description' => __('Users who vote on 5-25% of proposals', 'vortex-marketplace'),
                'avg_token_holdings' => rand(100, 2000)
            ),
            'rare_voters' => array(
                'percentage' => rand(30, 50),
                'description' => __('Users who vote on fewer than 5% of proposals', 'vortex-marketplace'),
                'avg_token_holdings' => rand(10, 500)
            )
        );
        
        // Voting time patterns
        $voting_time_patterns = array(
            'early_voters' => rand(10, 25),
            'mid_period_voters' => rand(25, 40),
            'last_minute_voters' => rand(40, 60)
        );
        
        // Proposal type preferences
        $type_preferences = array(
            'technical_focused' => rand(15, 30),
            'financial_focused' => rand(20, 35),
            'governance_focused' => rand(15, 25),
            'community_focused' => rand(20, 40),
            'balanced_participation' => rand(10, 25)
        );
        
        // Vote consistency
        $vote_consistency = array(
            'highly_consistent' => rand(30, 50), // Users who tend to always vote the same way
            'context_dependent' => rand(40, 60), // Users whose votes vary by proposal
            'contrarian' => rand(5, 15)         // Users who often vote against the majority
        );
        
        return array(
            'participation_segments' => $participation_segments,
            'voting_time_patterns' => $voting_time_patterns,
            'type_preferences' => $type_preferences,
            'vote_consistency' => $vote_consistency,
            'key_insights' => $this->generate_voter_behavior_insights(),
            'generated_at' => current_time('mysql')
        );
    }
    
    /**
     * Generate insights on voter behavior
     *
     * @return array Voter behavior insights
     */
    private function generate_voter_behavior_insights() {
        return array(
            __('Whale token holders tend to vote earlier in the voting period.', 'vortex-marketplace'),
            __('Financial proposals show the highest participation rate across all user segments.', 'vortex-marketplace'),
            __('Users who participate in forum discussions are 3x more likely to vote on related proposals.', 'vortex-marketplace'),
            __('First-time voters typically follow the voting pattern of whale token holders.', 'vortex-marketplace'),
            __('Users who stake their tokens are 2.5x more likely to participate in governance.', 'vortex-marketplace')
        );
    }
    
    /**
     * Generate governance health forecast
     *
     * @return array Governance health forecast data
     */
    private function forecast_governance_health() {
        // Get current health score
        $current_health = $this->calculate_current_governance_health();
        
        // Predicted health trajectory
        $trajectory = array(
            '1_month' => min(100, max(0, $current_health + rand(-8, 12))),
            '3_months' => min(100, max(0, $current_health + rand(-15, 20))),
            '6_months' => min(100, max(0, $current_health + rand(-20, 25)))
        );
        
        // Set trend direction based on 6-month projection
        $trend_direction = ($trajectory['6_months'] > $current_health) ? 'improving' : 
                          (($trajectory['6_months'] < $current_health) ? 'declining' : 'stable');
        
        return array(
            'current_health' => $current_health,
            'trend' => $trend_direction,
            'trajectory' => $trajectory,
            'risk_factors' => $this->identify_governance_risk_factors(),
            'improvement_opportunities' => $this->identify_governance_improvements(),
            'generated_at' => current_time('mysql')
        );
    }
    
    /**
     * Calculate current governance health
     *
     * @return int Current governance health score (0-100)
     */
    private function calculate_current_governance_health() {
        // In production, this would be based on actual metrics
        // For demonstration, we'll generate a plausible score
        
        // Component scores
        $participation_score = rand(40, 80);
        $decentralization_score = rand(50, 85);
        $proposal_quality_score = rand(60, 90);
        $execution_score = rand(55, 85);
        
        // Weighted average
        return round(
            ($participation_score * 0.3) + 
            ($decentralization_score * 0.3) + 
            ($proposal_quality_score * 0.2) + 
            ($execution_score * 0.2)
        );
    }
    
    /**
     * Identify governance risk factors
     *
     * @return array Governance risk factors
     */
    private function identify_governance_risk_factors() {
        return array(
            array(
                'factor' => __('Voter participation decline', 'vortex-marketplace'),
                'risk_level' => rand(1, 5),
                'mitigation' => __('Implement voting rewards and simplified voting interface', 'vortex-marketplace')
            ),
            array(
                'factor' => __('Token holder concentration', 'vortex-marketplace'),
                'risk_level' => rand(1, 5),
                'mitigation' => __('Quadratic voting implementation and delegation options', 'vortex-marketplace')
            ),
            array(
                'factor' => __('Proposal complexity barriers', 'vortex-marketplace'),
                'risk_level' => rand(1, 5),
                'mitigation' => __('Improved proposal templates and AI-assisted summaries', 'vortex-marketplace')
            ),
            array(
                'factor' => __('Governance fatigue', 'vortex-marketplace'),
                'risk_level' => rand(1, 5),
                'mitigation' => __('More focused proposal batching and prioritization', 'vortex-marketplace')
            )
        );
    }
    
    /**
     * Identify governance improvement opportunities
     *
     * @return array Governance improvement opportunities
     */
    private function identify_governance_improvements() {
        return array(
            array(
                'opportunity' => __('Tiered voting system', 'vortex-marketplace'),
                'impact' => 'high',
                'effort' => 'medium',
                'description' => __('Implement different voting weight calculations based on proposal category', 'vortex-marketplace')
            ),
            array(
                'opportunity' => __('Governance analytics dashboard', 'vortex-marketplace'),
                'impact' => 'medium',
                'effort' => 'low',
                'description' => __('Provide transparent metrics on governance health and participation', 'vortex-marketplace')
            ),
            array(
                'opportunity' => __('Community discussion integration', 'vortex-marketplace'),
                'impact' => 'high',
                'effort' => 'medium',
                'description' => __('Connect forum discussions directly to related governance proposals', 'vortex-marketplace')
            ),
            array(
                'opportunity' => __('Delegation marketplace', 'vortex-marketplace'),
                'impact' => 'high',
                'effort' => 'high',
                'description' => __('Allow users to delegate voting power to specialized representatives', 'vortex-marketplace')
            )
        );
    }
}

// Initialize the class
new VORTEX_AI_Governance_Advisor(); 