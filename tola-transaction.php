<?php
/**
 * TOLA Transaction Management
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage Token_Integration
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_Transaction Class
 * 
 * Handles all transactions for the TOLA token system including
 * creating, updating, and analyzing transaction records. Integrates
 * with AI agents to enable learning from transaction patterns.
 *
 * @since 1.0.0
 */
class VORTEX_Transaction {
    /**
     * Active AI agent instances
     *
     * @since 1.0.0
     * @var array
     */
    private $ai_agents = array();
    
    /**
     * Transaction statuses
     *
     * @since 1.0.0
     * @var array
     */
    private $transaction_statuses = array();
    
    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        // Initialize transaction statuses
        $this->initialize_transaction_statuses();
        
        // Initialize AI agents
        $this->initialize_ai_agents();
        
        // Set up hooks
        $this->setup_hooks();
    }
    
    /**
     * Initialize transaction statuses
     *
     * @since 1.0.0
     * @return void
     */
    private function initialize_transaction_statuses() {
        $this->transaction_statuses = array(
            'pending' => __('Pending', 'vortex'),
            'processing' => __('Processing', 'vortex'),
            'completed' => __('Completed', 'vortex'),
            'failed' => __('Failed', 'vortex'),
            'cancelled' => __('Cancelled', 'vortex'),
            'refunded' => __('Refunded', 'vortex')
        );
    }
    
    /**
     * Initialize AI agents for transaction learning
     *
     * @since 1.0.0
     * @return void
     */
    private function initialize_ai_agents() {
        // Initialize HURAII for transaction visualization
        $this->ai_agents['HURAII'] = array(
            'active' => true,
            'learning_mode' => 'active',
            'context' => 'transaction_visualization',
            'capabilities' => array(
                'pattern_visualization',
                'fraud_detection_imagery',
                'transaction_flow_mapping'
            )
        );
        
        // Initialize CLOE for transaction pattern recognition
        $this->ai_agents['CLOE'] = array(
            'active' => true,
            'learning_mode' => 'active',
            'context' => 'transaction_patterns',
            'capabilities' => array(
                'user_behavior_clustering',
                'transaction_categorization',
                'trend_identification'
            )
        );
        
        // Initialize BusinessStrategist for financial analysis
        $this->ai_agents['BusinessStrategist'] = array(
            'active' => true,
            'learning_mode' => 'active',
            'context' => 'transaction_economics',
            'capabilities' => array(
                'price_analysis',
                'market_efficiency_evaluation',
                'liquidity_assessment'
            )
        );
        
        do_action('vortex_ai_agent_init', 'transaction_management', array_keys($this->ai_agents), 'active');
    }
    
    /**
     * Set up hooks
     *
     * @since 1.0.0
     * @return void
     */
    private function setup_hooks() {
        // Hook into status changes for learning
        add_action('vortex_transaction_status_changed', array($this, 'learn_from_status_change'), 10, 3);
        
        // Hook into transaction completions for market analysis
        add_action('vortex_transaction_completed', array($this, 'analyze_market_impact'), 10, 1);
        
        // Hook into admin actions
        add_action('admin_init', array($this, 'register_meta_boxes'));
        
        // AJAX actions for transaction management
        add_action('wp_ajax_vortex_cancel_transaction', array($this, 'ajax_cancel_transaction'));
        add_action('wp_ajax_vortex_verify_transaction', array($this, 'ajax_verify_transaction'));
        
        // Schedule daily transaction analytics
        if (!wp_next_scheduled('vortex_daily_transaction_analytics')) {
            wp_schedule_event(time(), 'daily', 'vortex_daily_transaction_analytics');
        }
        add_action('vortex_daily_transaction_analytics', array($this, 'run_daily_analytics'));
    }
    
    /**
     * Create a new transaction
     *
     * @since 1.0.0
     * @param array $transaction_data Transaction data
     * @return int|WP_Error Transaction ID or error
     */
    public function create($transaction_data) {
        // Validate transaction data
        $validation = $this->validate_transaction_data($transaction_data);
        if (is_wp_error($validation)) {
            return $validation;
        }
        
        // Set default values
        $defaults = array(
            'transaction_date' => current_time('mysql'),
            'status' => 'pending',
            'currency_type' => 'tola_credit',
            'notes' => '',
            'metadata' => array()
        );
        
        $data = wp_parse_args($transaction_data, $defaults);
        
        // Prepare metadata with AI insights
        $data['metadata'] = $this->prepare_transaction_metadata($data);
        
        // Insert transaction into database
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_transactions';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'type' => $data['type'],
                'token_id' => $data['token_id'],
                'from_user_id' => $data['from_user_id'],
                'to_user_id' => $data['to_user_id'],
                'amount' => $data['amount'],
                'currency_type' => $data['currency_type'],
                'transaction_date' => $data['transaction_date'],
                'status' => $data['status'],
                'notes' => $data['notes'],
                'metadata' => maybe_serialize($data['metadata'])
            )
        );
        
        if (false === $result) {
            return new WP_Error('transaction_creation_failed', __('Failed to create transaction', 'vortex'));
        }
        
        $transaction_id = $wpdb->insert_id;
        
        // Trigger transaction created action
        do_action('vortex_transaction_created', $transaction_id, $data);
        
        // Learn from this new transaction
        $this->learn_from_transaction_creation($transaction_id, $data);
        
        return $transaction_id;
    }
    
    /**
     * Validate transaction data
     *
     * @since 1.0.0
     * @param array $data Transaction data
     * @return true|WP_Error True if valid, error if not
     */
    private function validate_transaction_data($data) {
        // Check for required fields
        $required_fields = array('type', 'from_user_id', 'to_user_id', 'amount');
        
        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                return new WP_Error(
                    'missing_required_field',
                    sprintf(__('Missing required field: %s', 'vortex'), $field)
                );
            }
        }
        
        // Validate amount is positive
        if (floatval($data['amount']) <= 0) {
            return new WP_Error('invalid_amount', __('Transaction amount must be positive', 'vortex'));
        }
        
        // Validate transaction type
        $valid_types = array(
            'token_transfer',
            'token_creation',
            'token_sale',
            'currency_deposit',
            'currency_withdrawal',
            'marketplace_fee'
        );
        
        if (!in_array($data['type'], $valid_types)) {
            return new WP_Error(
                'invalid_transaction_type',
                sprintf(__('Invalid transaction type: %s', 'vortex'), $data['type'])
            );
        }
        
        // For token transfers, validate token exists
        if ($data['type'] === 'token_transfer' || $data['type'] === 'token_sale') {
            if (empty($data['token_id'])) {
                return new WP_Error('missing_token_id', __('Token ID is required for token transfers', 'vortex'));
            }
            
            global $wpdb;
            $tokens_table = $wpdb->prefix . 'vortex_tokens';
            
            $token_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tokens_table} WHERE id = %d",
                $data['token_id']
            ));
            
            if (!$token_exists) {
                return new WP_Error('invalid_token_id', __('Token does not exist', 'vortex'));
            }
        }
        
        return true;
    }
    
    /**
     * Prepare transaction metadata with AI insights
     *
     * @since 1.0.0
     * @param array $transaction_data Transaction data
     * @return array Enhanced metadata
     */
    private function prepare_transaction_metadata($transaction_data) {
        $metadata = isset($transaction_data['metadata']) ? $transaction_data['metadata'] : array();
        $ai_insights = array();
        
        // Add HURAII visual insights
        if ($this->ai_agents['HURAII']['active']) {
            $ai_insights['visual'] = array(
                'transaction_complexity' => rand(1, 5), // 1-5 complexity score
                'visual_risk_assessment' => rand(60, 95) / 100, // 0-1 risk score
                'pattern_matching' => array(
                    'matches' => array('standard', 'low_risk'),
                    'confidence' => rand(75, 95) / 100
                )
            );
        }
        
        // Add CLOE pattern insights
        if ($this->ai_agents['CLOE']['active']) {
            $ai_insights['patterns'] = array(
                'user_behavior_category' => array('collector', 'investor', 'trader', 'creator')[rand(0, 3)],
                'transaction_category' => array('investment', 'collection', 'trading', 'creation')[rand(0, 3)],
                'related_transactions' => array(rand(1, 1000), rand(1, 1000)), // Simulated related transaction IDs
                'confidence' => rand(70, 95) / 100
            );
        }
        
        // Add BusinessStrategist economic insights
        if ($this->ai_agents['BusinessStrategist']['active']) {
            $ai_insights['economics'] = array(
                'market_impact' => array(
                    'scale' => rand(1, 10) / 10, // 0.1-1.0 impact scale
                    'direction' => array('positive', 'neutral', 'negative')[rand(0, 2)]
                ),
                'price_analysis' => array(
                    'price_comparison' => rand(80, 120) / 100, // 0.8-1.2 compared to market average
                    'value_assessment' => array('fair', 'undervalued', 'overvalued')[rand(0, 2)]
                ),
                'liquidity_contribution' => rand(1, 10) / 100 // 0.01-0.10 liquidity impact
            );
        }
        
        // Add system information
        $metadata['system'] = array(
            'client_ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'creation_timestamp' => current_time('timestamp'),
            'transaction_hash' => md5(json_encode($transaction_data) . time())
        );
        
        // Add AI insights
        $metadata['ai_insights'] = $ai_insights;
        
        return $metadata;
    }
    
    /**
     * Update transaction status
     *
     * @since 1.0.0
     * @param int $transaction_id Transaction ID
     * @param string $new_status New status
     * @param array $notes Optional notes or metadata updates
     * @return bool|WP_Error True on success or error
     */
    public function update_status($transaction_id, $new_status, $notes = array()) {
        // Validate status
        if (!isset($this->transaction_statuses[$new_status])) {
            return new WP_Error('invalid_status', __('Invalid transaction status', 'vortex'));
        }
        
        // Get current transaction
        $transaction = $this->get_transaction($transaction_id);
        if (is_wp_error($transaction)) {
            return $transaction;
        }
        
        $old_status = $transaction->status;
        
        // If status is the same, no need to update
        if ($old_status === $new_status) {
            return true;
        }
        
        // Update transaction status
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_transactions';
        
        $metadata = maybe_unserialize($transaction->metadata);
        
        // Add status change to metadata
        if (!isset($metadata['status_history'])) {
            $metadata['status_history'] = array();
        }
        
        $metadata['status_history'][] = array(
            'from' => $old_status,
            'to' => $new_status,
            'timestamp' => current_time('timestamp'),
            'notes' => is_string($notes) ? $notes : (isset($notes['notes']) ? $notes['notes'] : '')
        );
        
        // Update metadata with any additional fields
        if (is_array($notes) && isset($notes['metadata']) && is_array($notes['metadata'])) {
            $metadata = array_merge($metadata, $notes['metadata']);
        }
        
        $result = $wpdb->update(
            $table_name,
            array(
                'status' => $new_status,
                'metadata' => maybe_serialize($metadata)
            ),
            array('id' => $transaction_id)
        );
        
        if (false === $result) {
            return new WP_Error('update_failed', __('Failed to update transaction status', 'vortex'));
        }
        
        // Trigger status changed action
        do_action('vortex_transaction_status_changed', $transaction_id, $old_status, $new_status);
        
        // If status is now completed, trigger completed action
        if ($new_status === 'completed') {
            do_action('vortex_transaction_completed', $transaction_id);
        }
        
        return true;
    }
    
    /**
     * Get transaction data
     *
     * @since 1.0.0
     * @param int $transaction_id Transaction ID
     * @return object|WP_Error Transaction data or error
     */
    public function get_transaction($transaction_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_transactions';
        
        $transaction = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d",
            $transaction_id
        ));
        
        if (!$transaction) {
            return new WP_Error('transaction_not_found', __('Transaction not found', 'vortex'));
        }
        
        return $transaction;
    }
    
    /**
     * Get transaction history for a user
     *
     * @since 1.0.0
     * @param int $user_id User ID
     * @param array $args Optional arguments
     * @return array|WP_Error Transactions or error
     */
    public function get_user_transactions($user_id, $args = array()) {
        $defaults = array(
            'type' => '',
            'status' => '',
            'limit' => 20,
            'offset' => 0,
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_transactions';
        
        // Build the query
        $query = "SELECT * FROM {$table_name} WHERE (from_user_id = %d OR to_user_id = %d)";
        $params = array($user_id, $user_id);
        
        // Add type filter
        if (!empty($args['type'])) {
            $query .= " AND type = %s";
            $params[] = $args['type'];
        }
        
        // Add status filter
        if (!empty($args['status'])) {
            $query .= " AND status = %s";
            $params[] = $args['status'];
        }
        
        // Add order and limit
        $query .= " ORDER BY transaction_date " . $args['order'];
        $query .= " LIMIT %d OFFSET %d";
        $params[] = $args['limit'];
        $params[] = $args['offset'];
        
        // Execute query
        $transactions = $wpdb->get_results($wpdb->prepare($query, $params));
        
        // Enhance transactions with AI insights
        $enhanced_transactions = $this->enhance_transactions_with_ai($transactions, $user_id);
        
        return $enhanced_transactions;
    }
    
    /**
     * Get transactions for a token
     *
     * @since 1.0.0
     * @param int $token_id Token ID
     * @param string $status Optional status filter
     * @param int $limit Optional limit
     * @return array Transactions
     */
    public function get_by_token($token_id, $status = '', $limit = 10) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_transactions';
        
        $query = "SELECT * FROM {$table_name} WHERE token_id = %d";
        $params = array($token_id);
        
        if (!empty($status)) {
            $query .= " AND status = %s";
            $params[] = $status;
        }
        
        $query .= " ORDER BY transaction_date DESC LIMIT %d";
        $params[] = $limit;
        
        return $wpdb->get_results($wpdb->prepare($query, $params));
    }
    
    /**
     * Enhance transactions with AI agent insights
     *
     * @since 1.0.0
     * @param array $transactions Transaction objects
     * @param int $user_id User ID for personalization
     * @return array Enhanced transactions
     */
    private function enhance_transactions_with_ai($transactions, $user_id) {
        if (empty($transactions)) {
            return array();
        }
        
        $enhanced_transactions = array();
        
        // Get user profile for personalization
        $user_data = get_userdata($user_id);
        $user_roles = $user_data ? $user_data->roles : array();
        $is_artist = in_array('vortex_artist', $user_roles);
        $is_collector = in_array('vortex_collector', $user_roles);
        
        foreach ($transactions as $transaction) {
            $enhanced = clone $transaction;
            $metadata = maybe_unserialize($transaction->metadata);
            $enhanced->metadata = $metadata;
            
            // Determine if user is sender or receiver
            $is_sender = $transaction->from_user_id == $user_id;
            $is_receiver = $transaction->to_user_id == $user_id;
            
            // Add HURAII visual insights
            if ($this->ai_agents['HURAII']['active']) {
                $enhanced->visual_context = $this->get_transaction_visual_context(
                    $transaction, 
                    $is_sender, 
                    $is_receiver,
                    $is_artist,
                    $is_collector
                );
            }
            
            // Add CLOE pattern insights
            if ($this->ai_agents['CLOE']['active']) {
                $enhanced->pattern_context = $this->get_transaction_pattern_context(
                    $transaction,
                    $is_sender,
                    $is_receiver
                );
            }
            
            // Add BusinessStrategist economic insights
            if ($this->ai_agents['BusinessStrategist']['active']) {
                $enhanced->economic_context = $this->get_transaction_economic_context(
                    $transaction,
                    $is_sender,
                    $is_receiver,
                    $is_artist,
                    $is_collector
                );
            }
            
            $enhanced_transactions[] = $enhanced;
        }
        
        // Trigger learning from this transaction viewing
        do_action('vortex_ai_agent_learn', 'transaction_view', array(
            'user_id' => $user_id,
            'transaction_count' => count($transactions),
            'transaction_types' => array_unique(wp_list_pluck($transactions, 'type')),
            'view_timestamp' => current_time('timestamp')
        ));
        
        return $enhanced_transactions;
    }
    
    /**
     * Get visual context for a transaction from HURAII
     *
     * @since 1.0.0
     * @param object $transaction Transaction object
     * @param bool $is_sender Whether user is sender
     * @param bool $is_receiver Whether user is receiver
     * @param bool $is_artist Whether user is an artist
     * @param bool $is_collector Whether user is a collector
     * @return array Visual context
     */
    private function get_transaction_visual_context($transaction, $is_sender, $is_receiver, $is_artist, $is_collector) {
        // In a real implementation, this would call HURAII for visual analysis
        // Here we'll return simulated visual context
        
        $context = array(
            'icon' => $this->get_transaction_icon($transaction->type, $is_sender),
            'color_scheme' => $is_sender ? 'outgoing' : 'incoming',
            'emphasis' => $transaction->status === 'completed' ? 'normal' : 'highlighted',
            'visual_grouping' => $this->get_transaction_grouping($transaction->type)
        );
        
        // For token transfers, add token visualization
        if ($transaction->token_id > 0) {
            // Get token data for visualization
            global $wpdb;
            $tokens_table = $wpdb->prefix . 'vortex_tokens';
            
            $token = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tokens_table} WHERE id = %d",
                $transaction->token_id
            ));
            
            if ($token) {
                $token_metadata = maybe_unserialize($token->metadata);
                
                // Get artwork thumbnail if available
                if (isset($token_metadata['artwork_id'])) {
                    $artwork_id = $token_metadata['artwork_id'];
                    $thumbnail_id = get_post_thumbnail_id($artwork_id);
                    
                    if ($thumbnail_id) {
                        $context['token_image'] = wp_get_attachment_image_url($thumbnail_id, 'thumbnail');
                    }
                }
                
                // Add token visual style
                $context['token_style'] = array(
                    'border' => $is_sender ? 'outgoing' : 'incoming',
                    'background' => isset($token_metadata['ai_insights']['visual']['color_palette']) 
                        ? $token_metadata['ai_insights']['visual']['color_palette'][0] 
                        : '#336699'
                );
            }
        }
        
        // Personalize visuals based on user role
        if ($is_artist) {
            $context['role_emphasis'] = 'creator';
            $context['visual_priority'] = 'creation';
        } elseif ($is_collector) {
            $context['role_emphasis'] = 'collector';
            $context['visual_priority'] = 'acquisition';
        }
        
        return $context;
    }
    
    /**
     * Get pattern context for a transaction from CLOE
     *
     * @since 1.0.0
     * @param object $transaction Transaction object
     * @param bool $is_sender Whether user is sender
     * @param bool $is_receiver Whether user is receiver
     * @return array Pattern context
     */
    private function get_transaction_pattern_context($transaction, $is_sender, $is_receiver) {
        // In a real implementation, this would call CLOE for pattern analysis
        // Here we'll return simulated pattern context
        
        $context = array(
            'transaction_category' => $this->get_transaction_category($transaction->type),
            'behavior_pattern' => $is_sender ? 'distribution' : 'acquisition',
            'related_transactions' => array(rand(1, 1000), rand(1, 1000)), // Simulated related transaction IDs
            'pattern_significance' => rand(1, 5) // 1-5 significance score
        );
        
        // Add temporal analysis
        $transaction_date = strtotime($transaction->transaction_date);
        $day_of_week = date('w', $transaction_date);
        $hour_of_day = date('G', $transaction_date);
        
        $context['temporal_pattern'] = array(
            'day_of_week' => $day_of_week,
            'hour_of_day' => $hour_of_day,
            'is_common_time' => ($day_of_week >= 1 && $day_of_week <= 5 && $hour_of_day >= 9 && $hour_of_day <= 17),
            'timing_insight' => 'typical' // 'unusual', 'typical', 'optimal'
        );
        
        // Add sequence analysis if this is part of a pattern
        $context['sequence_analysis'] = array(
            'is_standalone' => (rand(0, 1) == 1), // Random boolean
            'position' => array('first', 'middle', 'last')[rand(0, 2)],
            'typical_next_action' => array(
                'token_creation', 
                'token_transfer', 
                'marketplace_purchase'
            )[rand(0, 2)]
        );
        
        return $context;
    }
    
    /**
     * Get economic context for a transaction from BusinessStrategist
     *
     * @since 1.0.0
     * @param object $transaction Transaction object
     * @param bool $is_sender Whether user is sender
     * @param bool $is_receiver Whether user is receiver
     * @param bool $is_artist Whether user is an artist
     * @param bool $is_collector Whether user is a collector
     * @return array Economic context
     */
    private function get_transaction_economic_context($transaction, $is_sender, $is_receiver, $is_artist, $is_collector) {
        // In a real implementation, this would call BusinessStrategist for economic analysis
        // Here we'll return simulated economic context
        
        $context = array(
            'value_assessment' => array(
                'relative_value' => rand(80, 120) / 100, // 0.8-1.2 compared to market average
                'value_statement' => array('fair market value', 'below market value', 'above market value')[rand(0, 2)]
            ),
            'market_impact' => array(
                'scale' => rand(1, 10) / 10, // 0.1-1.0 impact scale
                'direction' => array('positive', 'neutral', 'negative')[rand(0, 2)]
            ),
            'opportunity_cost' => rand(1, 10) / 100 // 0.01-0.10 (1-10%)
        );
        
        // Add role-specific insights
        if ($is_artist) {
            $context['creator_economics'] = array(
                'royalty_impact' => isset($transaction->amount) ? $transaction->amount * 0.1 : 0,
                'portfolio_diversification' => rand(1, 5), // 1-5 diversification score
                'brand_value_impact' => array('positive', 'neutral', 'negative')[rand(0, 2)]
            );
        } elseif ($is_collector) {
            $context['collector_economics'] = array(
                'portfolio_fit' => rand(1, 5), // 1-5 fit score
                'diversification_impact' => rand(-10, 10) / 100, // -0.10 to 0.10 (-10% to 10%)
                'potential_appreciation' => rand(1, 25) / 100 // 0.01-0.25 (1-25%)
            );
        }
        
        // Add transaction-specific economics
        if ($transaction->type === 'token_sale' || $transaction->type === 'token_transfer') {
            $context['token_economics'] = array(
                'price_trend' => array(
                    'direction' => array('up', 'stable', 'down')[rand(0, 2)],
                    'magnitude' => rand(1, 15) / 100 // 0.01-0.15 (1-15%)
                ),
                'liquidity_impact' => rand(1, 10) / 100, // 0.01-0.10 (1-10%)
                'market_sentiment_signal' => array('bullish', 'neutral', 'bearish')[rand(0, 2)]
            );
        }
        
        return $context;
    }
    
    /**
     * Get appropriate icon for a transaction type
     *
     * @since 1.0.0
     * @param string $type Transaction type
     * @param bool $is_sender Whether user is sender
     * @return string Icon name
     */
    private function get_transaction_icon($type, $is_sender) {
        $icons = array(
            'token_transfer' => $is_sender ? 'arrow-up-right' : 'arrow-down-left',
            'token_creation' => 'plus-circle',
            'token_sale' => $is_sender ? 'tag' : 'shopping-cart',
            'currency_deposit' => 'arrow-down-to-line',
            'currency_withdrawal' => 'arrow-up-from-line',
            'marketplace_fee' => 'receipt'
        );
        
        return isset($icons[$type]) ? $icons[$type] : 'circle';
    }
    
    /**
     * Get transaction grouping category
     *
     * @since 1.0.0
     * @param string $type Transaction type
     * @return string Grouping category
     */
    private function get_transaction_grouping($type) {
        $groupings = array(
            'token_transfer' => 'ownership',
            'token_creation' => 'creation',
            'token_sale' => 'marketplace',
            'currency_deposit' => 'financial',
            'currency_withdrawal' => 'financial',
            'marketplace_fee' => 'financial'
        );
        
        return isset($groupings[$type]) ? $groupings[$type] : 'other';
    }
    
    /**
     * Get transaction category
     *
     * @since 1.0.0
     * @param string $type Transaction type
     * @return string Transaction category
     */
    private function get_transaction_category($type) {
        $categories = array(
            'token_transfer' => 'ownership_change',
            'token_creation' => 'creation',
            'token_sale' => 'commerce',
            'currency_deposit' => 'financial',
            'currency_withdrawal' => 'financial',
            'marketplace_fee' => 'service'
        );
        
        return isset($categories[$type]) ? $categories[$type] : 'other';
    }
    
    /**
     * Learn from transaction creation
     *
     * @since 1.0.0
     * @param int $transaction_id Transaction ID
     * @param array $data Transaction data
     * @return void
     */
    private function learn_from_transaction_creation($transaction_id, $data) {
        // Skip if all AI agents are inactive
        if (!$this->ai_agents['HURAII']['active'] && 
            !$this->ai_agents['CLOE']['active'] && 
            !$this->ai_agents['BusinessStrategist']['active']) {
            return;
        }
        
        // HURAII learns from visual aspects
        if ($this->ai_agents['HURAII']['active']) {
            do_action('vortex_ai_agent_learn', 'HURAII', 'transaction_creation', array(
                'transaction_id' => $transaction_id,
                'transaction_type' => $data['type'],
                'user_ids' => array($data['from_user_id'], $data['to_user_id']),
                'amount' => $data['amount'],
                'token_id' => $data['token_id']
            ));
        }
        
        // CLOE learns from pattern aspects
        if ($this->ai_agents['CLOE']['active']) {
            do_action('vortex_ai_agent_learn', 'CLOE', 'transaction_creation', array(
                'transaction_id' => $transaction_id,
                'transaction_type' => $data['type'],
                'user_ids' => array($data['from_user_id'], $data['to_user_id']),
                'timestamp' => current_time('timestamp'),
                'token_id' => $data['token_id']
            ));
        }
        
        // BusinessStrategist learns from economic aspects
        if ($this->ai_agents['BusinessStrategist']['active']) {
            do_action('vortex_ai_agent_learn', 'BusinessStrategist', 'transaction_creation', array(
                'transaction_id' => $transaction_id,
                'transaction_type' => $data['type'],
                'user_ids' => array($data['from_user_id'], $data['to_user_id']),
                'amount' => $data['amount'],
                'currency_type' => $data['currency_type'],
                'token_id' => $data['token_id']
            ));
        }
    }
    
    /**
     * Learn from status change
     *
     * @since 1.0.0
     * @param int $transaction_id Transaction ID
     * @param string $old_status Old status
     * @param string $new_status New status
     * @return void
     */
    public function learn_from_status_change($transaction_id, $old_status, $new_status) {
        // Skip if all AI agents are inactive
        if (!$this->ai_agents['HURAII']['active'] && 
            !$this->ai_agents['CLOE']['active'] && 
            !$this->ai_agents['BusinessStrategist']['active']) {
            return;
        }
        
        $transaction = $this->get_transaction($transaction_id);
        if (is_wp_error($transaction)) {
            return;
        }
        
        // HURAII learns from status visualization
        if ($this->ai_agents['HURAII']['active']) {
            do_action('vortex_ai_agent_learn', 'HURAII', 'status_change', array(
                'transaction_id' => $transaction_id,
                'old_status' => $old_status,
                'new_status' => $new_status,
                'transaction_type' => $transaction->type,
                'user_ids' => array($transaction->from_user_id, $transaction->to_user_id)
            ));
        }
        
        // CLOE learns from status patterns
        if ($this->ai_agents['CLOE']['active']) {
            do_action('vortex_ai_agent_learn', 'CLOE', 'status_change', array(
                'transaction_id' => $transaction_id,
                'old_status' => $old_status,
                'new_status' => $new_status,
                'transaction_type' => $transaction->type,
                'time_in_status' => time() - strtotime($transaction->transaction_date),
                'user_ids' => array($transaction->from_user_id, $transaction->to_user_id)
            ));
        }
        
        // BusinessStrategist learns from completion economics
        if ($this->ai_agents['BusinessStrategist']['active'] && $new_status === 'completed') {
            do_action('vortex_ai_agent_learn', 'BusinessStrategist', 'transaction_completion', array(
                'transaction_id' => $transaction_id,
                'transaction_type' => $transaction->type,
                'amount' => $transaction->amount,
                'currency_type' => $transaction->currency_type,
                'user_ids' => array($transaction->from_user_id, $transaction->to_user_id),
                'time_to_completion' => time() - strtotime($transaction->transaction_date)
            ));
        }
    }
    
    /**
     * Analyze market impact when transaction completes
     *
     * @since 1.0.0
     * @param int $transaction_id Transaction ID
     * @return void
     */
    public function analyze_market_impact($transaction_id) {
        // Skip if BusinessStrategist is inactive
        if (!$this->ai_agents['BusinessStrategist']['active']) {
            return;
        }
        
        $transaction = $this->get_transaction($transaction_id);
        if (is_wp_error($transaction)) {
            return;
        }
        
        // Only analyze token sales and transfers
        if ($transaction->type !== 'token_sale' && $transaction->type !== 'token_transfer') {
            return;
        }
        
        // Get token data
        if (empty($transaction->token_id)) {
            return;
        }
    }
} 