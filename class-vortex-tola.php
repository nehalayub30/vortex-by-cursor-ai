<?php
/**
 * TOLA (Token and Liquidity Asset) Integration
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage Token_Integration
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_TOLA Class
 * 
 * Core class for managing TOLA tokens, liquidity, and integration with
 * marketplace functions. Maintains active AI agent learning throughout
 * token lifecycle and transactions.
 *
 * @since 1.0.0
 */
class VORTEX_TOLA {
    /**
     * Instance of this class.
     *
     * @since 1.0.0
     * @var object
     */
    protected static $instance = null;
    
    /**
     * Active AI agent instances
     *
     * @since 1.0.0
     * @var array
     */
    private $ai_agents = array();
    
    /**
     * Token types and their configurations
     *
     * @since 1.0.0
     * @var array
     */
    private $token_types = array();
    
    /**
     * Constructor
     *
     * @since 1.0.0
     */
    private function __construct() {
        // Initialize AI agents
        $this->initialize_ai_agents();
        
        // Register default token types
        $this->register_default_token_types();
        
        // Set up hooks
        $this->setup_hooks();
    }
    
    /**
     * Get instance of this class.
     *
     * @since 1.0.0
     * @return VORTEX_TOLA
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize AI agents for token learning
     *
     * @since 1.0.0
     * @return void
     */
    private function initialize_ai_agents() {
        // Initialize HURAII for visual token representation
        $this->ai_agents['HURAII'] = array(
            'active' => true,
            'learning_mode' => 'active',
            'context' => 'token_visualization',
            'capabilities' => array(
                'token_design_generation',
                'artwork_valuation',
                'visual_authentication'
            )
        );
        
        // Initialize CLOE for token curation and collections
        $this->ai_agents['CLOE'] = array(
            'active' => true,
            'learning_mode' => 'active',
            'context' => 'token_curation',
            'capabilities' => array(
                'collection_organization',
                'rarity_analysis',
                'market_trend_detection'
            )
        );
        
        // Initialize BusinessStrategist for token economics
        $this->ai_agents['BusinessStrategist'] = array(
            'active' => true,
            'learning_mode' => 'active',
            'context' => 'token_economics',
            'capabilities' => array(
                'pricing_strategy',
                'liquidity_analysis',
                'market_opportunity_detection'
            )
        );
        
        do_action('vortex_ai_agent_init', 'token_integration', array_keys($this->ai_agents), 'active');
    }
    
    /**
     * Register default token types
     *
     * @since 1.0.0
     * @return void
     */
    private function register_default_token_types() {
        $this->token_types = array(
            'artwork_token' => array(
                'name' => __('Artwork Token', 'vortex'),
                'description' => __('Represents ownership of a digital artwork', 'vortex'),
                'divisible' => false,
                'transferable' => true,
                'metadata_schema' => array(
                    'artist_id' => array('type' => 'integer', 'required' => true),
                    'artwork_id' => array('type' => 'integer', 'required' => true),
                    'creation_date' => array('type' => 'datetime', 'required' => true),
                    'edition_number' => array('type' => 'integer', 'required' => false),
                    'total_editions' => array('type' => 'integer', 'required' => false)
                )
            ),
            'collector_token' => array(
                'name' => __('Collector Token', 'vortex'),
                'description' => __('Rewards for collectors and patrons', 'vortex'),
                'divisible' => true,
                'transferable' => true,
                'metadata_schema' => array(
                    'tier' => array('type' => 'string', 'required' => true),
                    'benefits' => array('type' => 'array', 'required' => false),
                    'expiration_date' => array('type' => 'datetime', 'required' => false)
                )
            ),
            'artist_token' => array(
                'name' => __('Artist Token', 'vortex'),
                'description' => __('Represents support for an artist\'s work', 'vortex'),
                'divisible' => true,
                'transferable' => true,
                'metadata_schema' => array(
                    'artist_id' => array('type' => 'integer', 'required' => true),
                    'utility' => array('type' => 'string', 'required' => false),
                    'voting_rights' => array('type' => 'boolean', 'required' => false)
                )
            ),
            'tola_credit' => array(
                'name' => __('TOLA Credit', 'vortex'),
                'description' => __('Platform currency for transactions', 'vortex'),
                'divisible' => true,
                'transferable' => true,
                'metadata_schema' => array()
            )
        );
    }
    
    /**
     * Set up hooks
     *
     * @since 1.0.0
     * @return void
     */
    private function setup_hooks() {
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // AJAX handlers
        add_action('wp_ajax_vortex_create_token', array($this, 'ajax_create_token'));
        add_action('wp_ajax_vortex_transfer_token', array($this, 'ajax_transfer_token'));
        add_action('wp_ajax_vortex_get_token_info', array($this, 'ajax_get_token_info'));
        
        // Frontend hooks
        add_shortcode('vortex_wallet', array($this, 'wallet_shortcode'));
        add_shortcode('vortex_token_gallery', array($this, 'token_gallery_shortcode'));
        
        // Integration with artwork
        add_action('vortex_artwork_published', array($this, 'create_artwork_token'), 10, 2);
        add_action('vortex_artwork_purchased', array($this, 'transfer_artwork_token'), 10, 3);
        
        // AI learning hooks
        add_action('vortex_token_created', array($this, 'learn_from_token_creation'), 10, 2);
        add_action('vortex_token_transferred', array($this, 'learn_from_token_transfer'), 10, 3);
        add_action('vortex_token_value_changed', array($this, 'learn_from_value_change'), 10, 2);
    }
    
    /**
     * Add admin menu items
     *
     * @since 1.0.0
     * @return void
     */
    public function add_admin_menu() {
        add_submenu_page(
            'vortex-marketplace',
            __('TOLA Token Management', 'vortex'),
            __('Token Management', 'vortex'),
            'manage_options',
            'vortex-tola',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Register settings
     *
     * @since 1.0.0
     * @return void
     */
    public function register_settings() {
        register_setting('vortex_tola_options', 'vortex_tola_options', array($this, 'validate_options'));
        
        add_settings_section(
            'vortex_tola_general',
            __('General TOLA Settings', 'vortex'),
            array($this, 'render_general_section'),
            'vortex-tola'
        );
        
        add_settings_field(
            'enable_tola',
            __('Enable TOLA System', 'vortex'),
            array($this, 'render_enable_tola_field'),
            'vortex-tola',
            'vortex_tola_general'
        );
        
        add_settings_field(
            'token_contract_address',
            __('Token Contract Address', 'vortex'),
            array($this, 'render_token_contract_field'),
            'vortex-tola',
            'vortex_tola_general'
        );
    }
    
    /**
     * Create a new token
     *
     * @since 1.0.0
     * @param string $token_type Type of token to create
     * @param array $metadata Token metadata
     * @param int $owner_id User ID of the token owner
     * @return int|WP_Error Token ID or error
     */
    public function create_token($token_type, $metadata, $owner_id) {
        // Check if token type is registered
        if (!isset($this->token_types[$token_type])) {
            return new WP_Error('invalid_token_type', __('Invalid token type', 'vortex'));
        }
        
        // Validate metadata against schema
        $validation_result = $this->validate_token_metadata($token_type, $metadata);
        if (is_wp_error($validation_result)) {
            return $validation_result;
        }
        
        // Consult AI agents for token creation insights
        $ai_insights = $this->get_ai_token_insights($token_type, $metadata, $owner_id);
        
        // Enhanced metadata with AI insights
        $enhanced_metadata = array_merge($metadata, array(
            'ai_insights' => $ai_insights,
            'creation_timestamp' => current_time('timestamp')
        ));
        
        // Create token in the database
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_tokens';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'token_type' => $token_type,
                'metadata' => maybe_serialize($enhanced_metadata),
                'owner_id' => $owner_id,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
                'status' => 'active'
            )
        );
        
        if (false === $result) {
            return new WP_Error('token_creation_failed', __('Failed to create token', 'vortex'));
        }
        
        $token_id = $wpdb->insert_id;
        
        // Add token to owner's wallet
        $wallet = new VORTEX_Wallet($owner_id);
        $wallet->add_token($token_id);
        
        // Track token creation for AI learning
        do_action('vortex_token_created', $token_id, $token_type);
        
        return $token_id;
    }
    
    /**
     * Get AI insights for token creation
     *
     * @since 1.0.0
     * @param string $token_type Type of token
     * @param array $metadata Token metadata
     * @param int $owner_id User ID of the token owner
     * @return array AI insights
     */
    private function get_ai_token_insights($token_type, $metadata, $owner_id) {
        $insights = array();
        
        // Get insights from HURAII for visual aspects
        if ($this->ai_agents['HURAII']['active']) {
            $artwork_id = isset($metadata['artwork_id']) ? $metadata['artwork_id'] : 0;
            
            if ($artwork_id > 0) {
                // Get artwork data
                $artwork = get_post($artwork_id);
                $artwork_image_id = get_post_thumbnail_id($artwork_id);
                
                if ($artwork && $artwork_image_id) {
                    // Analyze artwork for visual insights
                    $insights['visual'] = array(
                        'uniqueness_score' => rand(70, 99) / 100, // Simulated score
                        'style_category' => 'contemporary', // Simulated category
                        'visual_complexity' => rand(60, 95) / 100, // Simulated complexity
                        'color_palette' => array('#336699', '#CC3333', '#FFCC00'), // Simulated palette
                        'seed_art_compatibility' => rand(75, 100) / 100 // Simulated Seed Art analysis
                    );
                }
            }
        }
        
        // Get insights from CLOE for curation
        if ($this->ai_agents['CLOE']['active']) {
            $insights['curation'] = array(
                'collection_fit' => array(
                    'recommended_collections' => array('Summer 2023', 'Digital Abstracts'),
                    'thematic_alignment' => rand(60, 95) / 100
                ),
                'rarity_prediction' => array(
                    'score' => rand(70, 99) / 100,
                    'comparable_assets' => array(123, 456, 789) // IDs of comparable tokens
                )
            );
        }
        
        // Get insights from BusinessStrategist for economics
        if ($this->ai_agents['BusinessStrategist']['active']) {
            $insights['economics'] = array(
                'initial_valuation' => array(
                    'suggested_price' => rand(5000, 50000) / 100, // $50-$500
                    'confidence_score' => rand(70, 95) / 100
                ),
                'market_potential' => array(
                    'growth_prediction' => rand(5, 25) / 100, // 5-25% growth
                    'liquidity_score' => rand(60, 90) / 100
                ),
                'investment_category' => array('growth', 'collector', 'speculative')[rand(0, 2)]
            );
        }
        
        return $insights;
    }
    
    /**
     * Validate token metadata against schema
     *
     * @since 1.0.0
     * @param string $token_type Type of token
     * @param array $metadata Token metadata
     * @return true|WP_Error True if valid, error if not
     */
    private function validate_token_metadata($token_type, $metadata) {
        if (!isset($this->token_types[$token_type])) {
            return new WP_Error('invalid_token_type', __('Invalid token type', 'vortex'));
        }
        
        $schema = $this->token_types[$token_type]['metadata_schema'];
        
        foreach ($schema as $field => $requirements) {
            if (!empty($requirements['required']) && !isset($metadata[$field])) {
                return new WP_Error(
                    'missing_required_field',
                    sprintf(__('Missing required field: %s', 'vortex'), $field)
                );
            }
            
            if (isset($metadata[$field])) {
                $type = $requirements['type'];
                
                // Type validation
                switch ($type) {
                    case 'integer':
                        if (!is_numeric($metadata[$field]) || (int)$metadata[$field] != $metadata[$field]) {
                            return new WP_Error(
                                'invalid_field_type',
                                sprintf(__('Field %s must be an integer', 'vortex'), $field)
                            );
                        }
                        break;
                        
                    case 'string':
                        if (!is_string($metadata[$field])) {
                            return new WP_Error(
                                'invalid_field_type',
                                sprintf(__('Field %s must be a string', 'vortex'), $field)
                            );
                        }
                        break;
                        
                    case 'boolean':
                        if (!is_bool($metadata[$field])) {
                            return new WP_Error(
                                'invalid_field_type',
                                sprintf(__('Field %s must be a boolean', 'vortex'), $field)
                            );
                        }
                        break;
                        
                    case 'datetime':
                        if (!$this->is_valid_datetime($metadata[$field])) {
                            return new WP_Error(
                                'invalid_field_type',
                                sprintf(__('Field %s must be a valid datetime', 'vortex'), $field)
                            );
                        }
                        break;
                        
                    case 'array':
                        if (!is_array($metadata[$field])) {
                            return new WP_Error(
                                'invalid_field_type',
                                sprintf(__('Field %s must be an array', 'vortex'), $field)
                            );
                        }
                        break;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Check if a string is a valid datetime
     *
     * @since 1.0.0
     * @param string $datetime Datetime string to validate
     * @return bool Whether it's a valid datetime
     */
    private function is_valid_datetime($datetime) {
        if (is_numeric($datetime)) {
            // Assume it's a timestamp
            return $datetime > 0;
        }
        
        $d = DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
        return $d && $d->format('Y-m-d H:i:s') === $datetime;
    }
    
    /**
     * Transfer a token to a new owner
     *
     * @since 1.0.0
     * @param int $token_id ID of the token to transfer
     * @param int $from_user_id Current owner's user ID
     * @param int $to_user_id New owner's user ID
     * @return bool|WP_Error True on success, error on failure
     */
    public function transfer_token($token_id, $from_user_id, $to_user_id) {
        // Get token data
        $token = $this->get_token($token_id);
        
        if (is_wp_error($token)) {
            return $token;
        }
        
        // Check ownership
        if ($token->owner_id != $from_user_id) {
            return new WP_Error('not_token_owner', __('The user does not own this token', 'vortex'));
        }
        
        // Check if token is transferable
        $token_type = $token->token_type;
        if (!isset($this->token_types[$token_type]) || !$this->token_types[$token_type]['transferable']) {
            return new WP_Error('token_not_transferable', __('This token is not transferable', 'vortex'));
        }
        
        // Create transaction record
        $transaction = new VORTEX_Transaction();
        $transaction_id = $transaction->create(array(
            'type' => 'token_transfer',
            'token_id' => $token_id,
            'from_user_id' => $from_user_id,
            'to_user_id' => $to_user_id,
            'amount' => 1, // For non-divisible tokens, always 1
            'status' => 'pending'
        ));
        
        if (is_wp_error($transaction_id)) {
            return $transaction_id;
        }
        
        // Process the token transfer
        $from_wallet = new VORTEX_Wallet($from_user_id);
        $to_wallet = new VORTEX_Wallet($to_user_id);
        
        $removed = $from_wallet->remove_token($token_id);
        if (is_wp_error($removed)) {
            $transaction->update_status($transaction_id, 'failed', array(
                'error' => $removed->get_error_message()
            ));
            return $removed;
        }
        
        $added = $to_wallet->add_token($token_id);
        if (is_wp_error($added)) {
            // If adding to new wallet fails, return to original owner
            $from_wallet->add_token($token_id);
            $transaction->update_status($transaction_id, 'failed', array(
                'error' => $added->get_error_message()
            ));
            return $added;
        }
        
        // Update token owner in database
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_tokens';
        
        $updated = $wpdb->update(
            $table_name,
            array(
                'owner_id' => $to_user_id,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $token_id)
        );
        
        if (false === $updated) {
            // Revert the wallet changes
            $to_wallet->remove_token($token_id);
            $from_wallet->add_token($token_id);
            
            $transaction->update_status($transaction_id, 'failed', array(
                'error' => __('Database update failed', 'vortex')
            ));
            
            return new WP_Error('transfer_failed', __('Failed to update token owner', 'vortex'));
        }
        
        // Complete the transaction
        $transaction->update_status($transaction_id, 'completed');
        
        // Trigger learning from this transfer
        do_action('vortex_token_transferred', $token_id, $from_user_id, $to_user_id);
        
        return true;
    }
    
    /**
     * Learn from token creation for AI agents
     *
     * @since 1.0.0
     * @param int $token_id Token that was created
     * @param string $token_type Type of token
     * @return void
     */
    public function learn_from_token_creation($token_id, $token_type) {
        // Skip if any AI agent is inactive
        if (!$this->ai_agents['HURAII']['active'] && 
            !$this->ai_agents['CLOE']['active'] && 
            !$this->ai_agents['BusinessStrategist']['active']) {
            return;
        }
        
        $token = $this->get_token($token_id);
        if (is_wp_error($token)) {
            return;
        }
        
        $metadata = maybe_unserialize($token->metadata);
        
        // HURAII learns from visual aspects
        if ($this->ai_agents['HURAII']['active'] && $token_type === 'artwork_token') {
            // In a real implementation, this would update HURAII's learning state
            // with artwork visual data and token metadata
            $artwork_id = isset($metadata['artwork_id']) ? $metadata['artwork_id'] : 0;
            
            if ($artwork_id > 0) {
                // Simulated learning based on artwork attributes
                // This would be replaced with actual AI model updates
                do_action('vortex_ai_agent_learn', 'HURAII', 'token_creation', array(
                    'token_id' => $token_id,
                    'artwork_id' => $artwork_id,
                    'visual_attributes' => array(
                        'style' => 'digital', // Example attribute
                        'colors' => array('#336699', '#CC3333'), // Example color palette
                        'composition' => 'balanced' // Example composition
                    )
                ));
            }
        }
        
        // CLOE learns from curation and collection aspects
        if ($this->ai_agents['CLOE']['active']) {
            // Simulated learning for CLOE based on token attributes
            do_action('vortex_ai_agent_learn', 'CLOE', 'token_creation', array(
                'token_id' => $token_id,
                'token_type' => $token_type,
                'metadata' => $metadata,
                'curation_attributes' => array(
                    'category' => 'digital art', // Example category
                    'themes' => array('abstract', 'modern'), // Example themes
                    'era' => '2020s' // Example era
                )
            ));
        }
        
        // BusinessStrategist learns from economic aspects
        if ($this->ai_agents['BusinessStrategist']['active']) {
            // Simulated learning for BusinessStrategist based on token economics
            do_action('vortex_ai_agent_learn', 'BusinessStrategist', 'token_creation', array(
                'token_id' => $token_id,
                'token_type' => $token_type,
                'metadata' => $metadata,
                'economic_attributes' => array(
                    'initial_value' => 100, // Example initial value
                    'creator_royalty' => 0.1, // Example royalty rate
                    'scarcity' => 'limited' // Example scarcity level
                )
            ));
        }
    }
    
    /**
     * Learn from token transfers for AI agents
     *
     * @since 1.0.0
     * @param int $token_id Token that was transferred
     * @param int $from_user_id Previous owner
     * @param int $to_user_id New owner
     * @return void
     */
    public function learn_from_token_transfer($token_id, $from_user_id, $to_user_id) {
        // Skip if all AI agents are inactive
        if (!$this->ai_agents['HURAII']['active'] && 
            !$this->ai_agents['CLOE']['active'] && 
            !$this->ai_agents['BusinessStrategist']['active']) {
            return;
        }
        
        $token = $this->get_token($token_id);
        if (is_wp_error($token)) {
            return;
        }
        
        $token_type = $token->token_type;
        $metadata = maybe_unserialize($token->metadata);
        
        // Get transaction details
        $transaction = new VORTEX_Transaction();
        $transactions = $transaction->get_by_token($token_id, 'completed', 1);
        $latest_transaction = !empty($transactions) ? $transactions[0] : null;
        
        $transaction_data = array(
            'token_id' => $token_id,
            'token_type' => $token_type,
            'from_user_id' => $from_user_id,
            'to_user_id' => $to_user_id,
            'timestamp' => current_time('timestamp')
        );
        
        if ($latest_transaction) {
            $transaction_data['price'] = $latest_transaction->amount;
        }
        
        // Each AI agent learns from the transfer
        if ($this->ai_agents['HURAII']['active']) {
            do_action('vortex_ai_agent_learn', 'HURAII', 'token_transfer', $transaction_data);
        }
        
        if ($this->ai_agents['CLOE']['active']) {
            do_action('vortex_ai_agent_learn', 'CLOE', 'token_transfer', $transaction_data);
        }
        
        if ($this->ai_agents['BusinessStrategist']['active']) {
            // BusinessStrategist specifically tracks market value changes
            do_action('vortex_ai_agent_learn', 'BusinessStrategist', 'token_transfer', array_merge(
                $transaction_data,
                array(
                    'market_implications' => array(
                        'value_change' => isset($transaction_data['price']) ? $transaction_data['price'] : 0,
                        'demand_signal' => 'positive', // Example signal
                        'market_activity' => 'increasing' // Example activity trend
                    )
                )
            ));
        }
    }
    
    /**
     * Get token data
     *
     * @since 1.0.0
     * @param int $token_id Token ID
     * @return object|WP_Error Token data or error
     */
    public function get_token($token_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_tokens';
        
        $token = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE id = %d",
            $token_id
        ));
        
        if (!$token) {
            return new WP_Error('token_not_found', __('Token not found', 'vortex'));
        }
        
        return $token;
    }
    
    /**
     * Create an artwork token when artwork is published
     *
     * @since 1.0.0
     * @param int $artwork_id Artwork post ID
     * @param int $artist_id Artist user ID
     * @return int|WP_Error Token ID or error
     */
    public function create_artwork_token($artwork_id, $artist_id) {
        $artwork = get_post($artwork_id);
        
        if (!$artwork || $artwork->post_type !== 'vortex-artwork') {
            return new WP_Error('invalid_artwork', __('Invalid artwork', 'vortex'));
        }
        
        // Get artwork metadata
        $edition_number = get_post_meta($artwork_id, 'vortex_edition_number', true) ?: 1;
        $total_editions = get_post_meta($artwork_id, 'vortex_total_editions', true) ?: 1;
        
        // Create token metadata
        $metadata = array(
            'artist_id' => $artist_id,
            'artwork_id' => $artwork_id,
            'creation_date' => $artwork->post_date,
            'edition_number' => $edition_number,
            'total_editions' => $total_editions,
            'title' => $artwork->post_title,
            'description' => $artwork->post_excerpt,
            'artwork_type' => get_post_meta($artwork_id, 'vortex_artwork_type', true),
        );
        
        // Create the token
        return $this->create_token('artwork_token', $metadata, $artist_id);
    }
    
    /**
     * Transfer artwork token when artwork is purchased
     *
     * @since 1.0.0
     * @param int $artwork_id Artwork post ID
     * @param int $from_user_id Seller user ID
     * @param int $to_user_id Buyer user ID
     * @return bool|WP_Error True on success or error
     */
    public function transfer_artwork_token($artwork_id, $from_user_id, $to_user_id) {
        global $wpdb;
        $tokens_table = $wpdb->prefix . 'vortex_tokens';
        
        // Find token for this artwork owned by seller
        $token = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$tokens_table} 
             WHERE token_type = 'artwork_token' 
             AND owner_id = %d
             AND metadata LIKE %s
             AND status = 'active'
             LIMIT 1",
            $from_user_id,
            '%"artwork_id":' . $artwork_id . '%'
        ));
        
        if (!$token) {
            return new WP_Error('token_not_found', __('Artwork token not found', 'vortex'));
        }
        
        // Transfer the token
        return $this->transfer_token($token->id, $from_user_id, $to_user_id);
    }
    
    /**
     * Wallet shortcode
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function wallet_shortcode($atts) {
        $atts = shortcode_atts(array(
            'user_id' => 0,
        ), $atts);
        
        $user_id = intval($atts['user_id']);
        if ($user_id <= 0) {
            $user_id = get_current_user_id();
        }
        
        if ($user_id <= 0) {
            return '<p>' . __('Please log in to view your wallet.', 'vortex') . '</p>';
        }
        
        $wallet = new VORTEX_Wallet($user_id);
        $tokens = $wallet->get_tokens();
        
        ob_start();
        include_once VORTEX_PLUGIN_PATH . 'templates/wallet/wallet-display.php';
        return ob_get_clean();
    }
    
    /**
     * Token gallery shortcode
     *
     * @since 1.0.0
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function token_gallery_shortcode($atts) {
        $atts = shortcode_atts(array(
            'type' => 'artwork_token',
            'count' => 10,
            'columns' => 3,
            'orderby' => 'created_at',
            'order' => 'DESC'
        ), $atts);
        
        global $wpdb;
        $tokens_table = $wpdb->prefix . 'vortex_tokens';
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$tokens_table} 
             WHERE token_type = %s 
             AND status = 'active'
             ORDER BY {$atts['orderby']} {$atts['order']}
             LIMIT %d",
            $atts['type'],
            intval($atts['count'])
        );
        
        $tokens = $wpdb->get_results($query);
        
        ob_start();
        include_once VORTEX_PLUGIN_PATH . 'templates/token/token-gallery.php';
        return ob_get_clean();
    }
    
    /**
     * Render admin page
     *
     * @since 1.0.0
     * @return void
     */
    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Save settings if submitted
        if (isset($_POST['vortex_tola_options'])) {
            check_admin_referer('vortex_tola_options_nonce');
            $options = $this->validate_options($_POST['vortex_tola_options']);
            update_option('vortex_tola_options', $options);
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved.', 'vortex') . '</p></div>';
        }
        
        // Get current options
        $options = get_option('vortex_tola_options', array());
        
        // Get token statistics
        global $wpdb;
        $tokens_table = $wpdb->prefix . 'vortex_tokens';
        $total_tokens = $wpdb->get_var("SELECT COUNT(*) FROM {$tokens_table}");
        
        // Get token type distribution
        $token_types = $wpdb->get_results("
            SELECT token_type, COUNT(*) as count 
            FROM {$tokens_table} 
            GROUP BY token_type
        ");
        
        // Include admin template
        include_once VORTEX_PLUGIN_PATH . 'templates/admin/tola-settings.php';
    }
    
    /**
     * Validate options
     *
     * @since 1.0.0
     * @param array $input Input options
     * @return array Validated options
     */
    public function validate_options($input) {
        $validated = array();
        
        $validated['enable_tola'] = isset($input['enable_tola']) && $input['enable_tola'] ? 1 : 0;
        
        if (isset($input['token_contract_address'])) {
            $validated['token_contract_address'] = sanitize_text_field($input['token_contract_address']);
        }
        
        return $validated;
    }
    
    /**
     * Render general settings section
     *
     * @since 1.0.0
     * @return void
     */
    public function render_general_section() {
        echo '<p>' . __('Configure general settings for the TOLA token system.', 'vortex') . '</p>';
    }
    
    /**
     * Render enable TOLA field
     *
     * @since 1.0.0
     * @return void
     */
    public function render_enable_tola_field() {
        $options = get_option('vortex_tola_options', array());
        $enabled = isset($options['enable_tola']) ? $options['enable_tola'] : 0;
        
        echo '<input type="checkbox" id="enable_tola" name="vortex_tola_options[enable_tola]" value="1" ' . checked(1, $enabled, false) . '>';
        echo '<label for="enable_tola">' . __('Enable TOLA token system', 'vortex') . '</label>';
    }
    
    /**
     * Get liquidity pool data from Solana blockchain
     *
     * @since 1.0.0
     * @param string $pool_address The liquidity pool address
     * @return array|WP_Error Pool data or error
     */
    private function get_pool_data($pool_address) {
        try {
            // Validate pool address
            if (empty($pool_address)) {
                return new WP_Error('invalid_pool', __('Invalid pool address', 'vortex'));
            }

            // Get Solana connection
            $connection = $this->get_solana_connection();
            if (is_wp_error($connection)) {
                return $connection;
            }

            // Get pool account data
            $pool_account = $connection->getAccountInfo($pool_address);
            if (!$pool_account) {
                return new WP_Error('pool_not_found', __('Pool not found', 'vortex'));
            }

            // Parse pool data
            $pool_data = array(
                'address' => $pool_address,
                'tola_balance' => $this->get_token_balance($pool_address, 'TOLA'),
                'sol_balance' => $this->get_token_balance($pool_address, 'SOL'),
                'total_liquidity' => $this->calculate_total_liquidity($pool_account),
                'last_update' => current_time('timestamp'),
                'health_metrics' => array(
                    'liquidity_ratio' => $this->calculate_liquidity_ratio($pool_account),
                    'price_impact' => $this->calculate_price_impact($pool_account),
                    'volume_24h' => $this->get_24h_volume($pool_address)
                )
            );

            // AI agent analysis
            if ($this->ai_agents['business_strategist']['active']) {
                $pool_data['ai_insights'] = $this->get_ai_pool_insights($pool_data);
            }

            return $pool_data;

        } catch (Exception $e) {
            error_log(sprintf(
                'TOLA Pool Data Error: %s',
                $e->getMessage()
            ));
            return new WP_Error('pool_data_error', $e->getMessage());
        }
    }

    /**
     * Get Solana connection
     *
     * @since 1.0.0
     * @return object|WP_Error Solana connection or error
     */
    private function get_solana_connection() {
        try {
            // Initialize Solana connection
            $connection = new SolanaConnection(
                get_option('vortex_solana_rpc_url', 'https://api.mainnet-beta.solana.com')
            );
            return $connection;
        } catch (Exception $e) {
            return new WP_Error('connection_error', $e->getMessage());
        }
    }

    /**
     * Get token balance for a specific address
     *
     * @since 1.0.0
     * @param string $address The address to check
     * @param string $token The token symbol
     * @return float|WP_Error Balance or error
     */
    private function get_token_balance($address, $token) {
        try {
            $connection = $this->get_solana_connection();
            if (is_wp_error($connection)) {
                return $connection;
            }

            $token_account = $this->get_token_account($address, $token);
            if (is_wp_error($token_account)) {
                return $token_account;
            }

            return $connection->getTokenAccountBalance($token_account);
        } catch (Exception $e) {
            return new WP_Error('balance_error', $e->getMessage());
        }
    }

    /**
     * Calculate total liquidity in USD
     *
     * @since 1.0.0
     * @param object $pool_account The pool account data
     * @return float Total liquidity in USD
     */
    private function calculate_total_liquidity($pool_account) {
        try {
            $tola_balance = $this->get_token_balance($pool_account->owner, 'TOLA');
            $sol_balance = $this->get_token_balance($pool_account->owner, 'SOL');
            
            if (is_wp_error($tola_balance) || is_wp_error($sol_balance)) {
                return 0;
            }

            $sol_price = $this->get_sol_price();
            return ($tola_balance * $this->get_tola_price()) + ($sol_balance * $sol_price);
        } catch (Exception $e) {
            error_log(sprintf(
                'TOLA Liquidity Calculation Error: %s',
                $e->getMessage()
            ));
            return 0;
        }
    }

    /**
     * Calculate liquidity ratio
     *
     * @since 1.0.0
     * @param object $pool_account The pool account data
     * @return float Liquidity ratio
     */
    private function calculate_liquidity_ratio($pool_account) {
        try {
            $tola_balance = $this->get_token_balance($pool_account->owner, 'TOLA');
            $sol_balance = $this->get_token_balance($pool_account->owner, 'SOL');
            
            if (is_wp_error($tola_balance) || is_wp_error($sol_balance)) {
                return 0;
            }

            return $sol_balance > 0 ? $tola_balance / $sol_balance : 0;
        } catch (Exception $e) {
            error_log(sprintf(
                'TOLA Liquidity Ratio Error: %s',
                $e->getMessage()
            ));
            return 0;
        }
    }

    /**
     * Calculate price impact
     *
     * @since 1.0.0
     * @param object $pool_account The pool account data
     * @return float Price impact percentage
     */
    private function calculate_price_impact($pool_account) {
        try {
            $liquidity = $this->calculate_total_liquidity($pool_account);
            $volume_24h = $this->get_24h_volume($pool_account->owner);
            
            if ($liquidity <= 0) {
                return 100;
            }

            return ($volume_24h / $liquidity) * 100;
        } catch (Exception $e) {
            error_log(sprintf(
                'TOLA Price Impact Error: %s',
                $e->getMessage()
            ));
            return 100;
        }
    }

    /**
     * Get 24-hour volume
     *
     * @since 1.0.0
     * @param string $pool_address The pool address
     * @return float 24-hour volume in USD
     */
    private function get_24h_volume($pool_address) {
        try {
            global $wpdb;
            $transactions_table = $wpdb->prefix . 'vortex_transactions';
            
            $volume = $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(amount) FROM {$transactions_table} 
                 WHERE pool_address = %s 
                 AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                 AND status = 'completed'",
                $pool_address
            ));

            return floatval($volume) ?: 0;
        } catch (Exception $e) {
            error_log(sprintf(
                'TOLA Volume Error: %s',
                $e->getMessage()
            ));
            return 0;
        }
    }

    /**
     * Get AI insights for pool data
     *
     * @since 1.0.0
     * @param array $pool_data The pool data
     * @return array AI insights
     */
    private function get_ai_pool_insights($pool_data) {
        if (!$this->ai_agents['business_strategist']['active']) {
            return array();
        }

        return array(
            'health_score' => $this->calculate_health_score($pool_data),
            'risk_assessment' => $this->assess_pool_risk($pool_data),
            'recommendations' => $this->generate_pool_recommendations($pool_data)
        );
    }

    /**
     * Calculate pool health score
     *
     * @since 1.0.0
     * @param array $pool_data The pool data
     * @return float Health score (0-100)
     */
    private function calculate_health_score($pool_data) {
        try {
            $metrics = $pool_data['health_metrics'];
            $liquidity_score = min(100, ($metrics['liquidity_ratio'] / 2) * 100);
            $impact_score = max(0, 100 - $metrics['price_impact']);
            $volume_score = min(100, ($metrics['volume_24h'] / 1000000) * 100);

            return ($liquidity_score * 0.4) + ($impact_score * 0.3) + ($volume_score * 0.3);
        } catch (Exception $e) {
            error_log(sprintf(
                'TOLA Health Score Error: %s',
                $e->getMessage()
            ));
            return 0;
        }
    }

    /**
     * Assess pool risk
     *
     * @since 1.0.0
     * @param array $pool_data The pool data
     * @return array Risk assessment
     */
    private function assess_pool_risk($pool_data) {
        try {
            $health_score = $this->calculate_health_score($pool_data);
            $metrics = $pool_data['health_metrics'];

            return array(
                'risk_level' => $this->get_risk_level($health_score),
                'liquidity_risk' => $this->get_liquidity_risk($metrics['liquidity_ratio']),
                'price_risk' => $this->get_price_risk($metrics['price_impact']),
                'volume_risk' => $this->get_volume_risk($metrics['volume_24h'])
            );
        } catch (Exception $e) {
            error_log(sprintf(
                'TOLA Risk Assessment Error: %s',
                $e->getMessage()
            ));
            return array('risk_level' => 'high');
        }
    }

    /**
     * Generate pool recommendations
     *
     * @since 1.0.0
     * @param array $pool_data The pool data
     * @return array Recommendations
     */
    private function generate_pool_recommendations($pool_data) {
        try {
            $risk_assessment = $this->assess_pool_risk($pool_data);
            $recommendations = array();

            if ($risk_assessment['risk_level'] === 'high') {
                $recommendations[] = 'Consider adding more liquidity to reduce risk';
            }

            if ($risk_assessment['liquidity_risk'] === 'high') {
                $recommendations[] = 'Increase TOLA/SOL ratio to improve liquidity';
            }

            if ($risk_assessment['price_risk'] === 'high') {
                $recommendations[] = 'Monitor price impact and consider adjusting pool parameters';
            }

            return $recommendations;
        } catch (Exception $e) {
            error_log(sprintf(
                'TOLA Recommendations Error: %s',
                $e->getMessage()
            ));
            return array();
        }
    }

    /**
     * Get risk level based on health score
     *
     * @since 1.0.0
     * @param float $health_score The health score
     * @return string Risk level
     */
    private function get_risk_level($health_score) {
        if ($health_score >= 80) return 'low';
        if ($health_score >= 60) return 'medium';
        return 'high';
    }

    /**
     * Get liquidity risk level
     *
     * @since 1.0.0
     * @param float $liquidity_ratio The liquidity ratio
     * @return string Risk level
     */
    private function get_liquidity_risk($liquidity_ratio) {
        if ($liquidity_ratio >= 2) return 'low';
        if ($liquidity_ratio >= 1) return 'medium';
        return 'high';
    }

    /**
     * Get price risk level
     *
     * @since 1.0.0
     * @param float $price_impact The price impact
     * @return string Risk level
     */
    private function get_price_risk($price_impact) {
        if ($price_impact <= 1) return 'low';
        if ($price_impact <= 5) return 'medium';
        return 'high';
    }

    /**
     * Get volume risk level
     *
     * @since 1.0.0
     * @param float $volume_24h The 24-hour volume
     * @return string Risk level
     */
    private function get_volume_risk($volume_24h) {
        if ($volume_24h >= 1000000) return 'low';
        if ($volume_24h >= 100000) return 'medium';
        return 'high';
    }

    /**
     * Monitor liquidity pool status and health
     *
     * @since 1.0.0
     * @return void
     */
    public function monitor_liquidity_pool() {
        $pool_address = get_option('vortex_tola_liquidity_pool');
        $pool_data = $this->get_pool_data($pool_address);
        
        // AI agent monitoring
        $this->ai_agents['business_strategist']->analyze_pool_health($pool_data);
        
        // Log pool status
        error_log(sprintf(
            'TOLA Liquidity Pool Status: %s',
            json_encode($pool_data)
        ));
    }
} 