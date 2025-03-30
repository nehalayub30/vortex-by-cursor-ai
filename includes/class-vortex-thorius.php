<?php
/**
 * Thorius AI Concierge - Advanced Implementation
 * 
 * Full-featured AI concierge that can process voice and text commands,
 * supports multiple languages, understands geographic context, and
 * recommends local collaborations.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * The class that handles Thorius AI Concierge functionality
 */
class Vortex_Thorius {
    
    /**
     * Context tracking for conversations
     */
    private $conversation_context = array();
    
    /**
     * Action handlers registry
     */
    private $action_handlers = array();

    /**
     * Supported languages
     */
    private $supported_languages = array(
        'en' => 'English',
        'es' => 'Español',
        'fr' => 'Français',
        'de' => 'Deutsch',
        'it' => 'Italiano',
        'ja' => '日本語',
        'zh' => '中文',
        'ru' => 'Русский',
        'ar' => 'العربية',
        'pt' => 'Português'
    );
    
    /**
     * Get instance of Thorius
     * 
     * @return Vortex_Thorius
     */
    public static function get_instance() {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
            $instance->register_action_handlers();
        }
        return $instance;
    }
    
    /**
     * Register available action handlers
     */
    private function register_action_handlers() {
        // Marketplace actions
        $this->action_handlers['search_artwork'] = array($this, 'handle_artwork_search');
        $this->action_handlers['show_artwork'] = array($this, 'handle_artwork_display');
        $this->action_handlers['price_estimate'] = array($this, 'handle_price_estimation');
        $this->action_handlers['artist_info'] = array($this, 'handle_artist_info');
        
        // TOLA token actions
        $this->action_handlers['show_tola_balance'] = array($this, 'handle_tola_balance');
        $this->action_handlers['explain_tola'] = array($this, 'handle_tola_explanation');
        
        // Web3 actions
        $this->action_handlers['verify_artwork'] = array($this, 'handle_artwork_verification');
        $this->action_handlers['show_smart_contract'] = array($this, 'handle_contract_display');
        $this->action_handlers['show_royalties'] = array($this, 'handle_royalties_display');
        
        // AI assistant actions
        $this->action_handlers['ask_huraii'] = array($this, 'handle_huraii_query');
        $this->action_handlers['ask_cloe'] = array($this, 'handle_cloe_query');
        $this->action_handlers['ask_architect'] = array($this, 'handle_architect_query');
        
        // User profile actions
        $this->action_handlers['update_preferences'] = array($this, 'handle_preference_update');
        $this->action_handlers['show_profile'] = array($this, 'handle_profile_display');
        
        // Location-based actions
        $this->action_handlers['find_local_events'] = array($this, 'handle_local_events');
        $this->action_handlers['find_local_artists'] = array($this, 'handle_local_artists');
        $this->action_handlers['find_local_collaborations'] = array($this, 'handle_local_collaborations');
        $this->action_handlers['find_local_galleries'] = array($this, 'handle_local_galleries');
        
        // Language actions
        $this->action_handlers['change_language'] = array($this, 'handle_language_change');
        
        // Allow plugins to register custom handlers
        $this->action_handlers = apply_filters('vortex_thorius_action_handlers', $this->action_handlers);
    }
    
    /**
     * Process a user query to Thorius
     *
     * @param string $query User's question or request
     * @param string $context Context of the conversation
     * @param int $user_id User ID for personalization
     * @param bool $is_voice Whether this query came from voice input
     * @param string $language Language code
     * @param array $location User location data
     * @return array Response data
     */
    public function process_query($query, $context = '', $user_id = 0, $is_voice = false, $language = 'en', $location = array()) {
        try {
            // Sanitize inputs
            $query = sanitize_text_field($query);
            $context = sanitize_text_field($context);
            $language = sanitize_text_field($language);
            
            // Validate language
            if (!array_key_exists($language, $this->supported_languages)) {
                $language = 'en';
            }
            
            // Get user info if logged in
            $user_info = '';
            if (!$user_id && is_user_logged_in()) {
                $user_id = get_current_user_id();
            }
            
            if ($user_id) {
                $user = get_user_by('id', $user_id);
                $user_role = get_user_meta($user_id, 'vortex_user_role', true);
                $user_info = "User is a " . ($user_role ?: 'visitor');
                
                // Get user preferences including preferred language
                $user_preferences = $this->get_user_preferences($user_id);
                if (isset($user_preferences['language']) && array_key_exists($user_preferences['language'], $this->supported_languages)) {
                    $language = $user_preferences['language'];
                }
                
                // Update conversation context
                $this->update_conversation_context($user_id, $query);
            }
            
            // Get location information if not provided
            if (empty($location) && $user_id) {
                $location = $this->get_user_location($user_id);
            }
            
            // Process the query with NLP
            $nlp_result = $this->process_with_nlp($query, $context, $user_id, $language);
            
            // Check if this is an actionable command
            $action_result = $this->check_for_action_command($query, $nlp_result, $user_id);
            
            if ($action_result['is_action']) {
                // Add location data to parameters
                $action_result['parameters']['location'] = $location;
                
                // Execute the action
                $action_response = $this->execute_action(
                    $action_result['action'], 
                    $action_result['parameters'], 
                    $user_id,
                    $language
                );
                
                // Track conversation for context
                $this->track_conversation($query, $action_response, $user_id);
                
                return array(
                    'success' => true,
                    'response' => $action_response,
                    'followup_suggestions' => $this->generate_followup_suggestions($query, $action_response, $action_result['action'], $user_id, $language, $location),
                    'context' => $this->get_conversation_summary($user_id),
                    'executed_action' => $action_result['action'],
                    'is_voice' => $is_voice,
                    'language' => $language
                );
            }
            
            // Handle as regular query if not an action
            // Determine intent and entities
            $intent = $nlp_result['intent'] ?? '';
            $entities = $nlp_result['entities'] ?? array();
            
            // Get response based on intent
            if (!empty($intent)) {
                $response = $this->get_intent_based_response($intent, $entities, $user_id, $language, $location);
            } else {
                // Fallback to keyword matching if intent detection fails
                $response = $this->get_predefined_response($query, $context, $user_info, $language, $location);
            }
            
            // Check if this is a Web3 query
            if ($this->is_web3_query($query)) {
                $web3_data = $this->get_web3_information($query, $user_id);
                $response = $this->enhance_response_with_web3($response, $web3_data, $language);
            }
            
            // Check for art-specific query
            if ($this->is_art_knowledge_query($query)) {
                $art_knowledge = $this->get_art_knowledge($query, $language);
                $response = $this->enhance_response_with_art_knowledge($response, $art_knowledge, $language);
            }
            
            // Check for location-specific query
            if ($this->is_location_query($query) && !empty($location)) {
                $location_info = $this->get_location_information($query, $location, $language);
                $response = $this->enhance_response_with_location($response, $location_info, $language);
            }
            
            // Personalize the response if user is logged in
            if ($user_id) {
                $response = $this->personalize_response($response, $user_id, $language);
            }
            
            // Translate response if necessary
            if ($language != 'en') {
                $response = $this->translate_response($response, $language);
            }
            
            // Track conversation for context
            $this->track_conversation($query, $response, $user_id);
            
            // Track user interests for preference learning
            if ($user_id) {
                $this->update_user_preferences($user_id, $query);
            }
            
            // Process query with emotion awareness
            $emotion_aware_response = $this->process_with_emotion_awareness($query, $user_id);
            
            if ($emotion_aware_response) {
                $response = $emotion_aware_response;
            }
            
            return array(
                'success' => true,
                'response' => $response,
                'followup_suggestions' => $this->generate_followup_suggestions($query, $response, $intent, $user_id, $language, $location),
                'context' => $this->get_conversation_summary($user_id),
                'is_voice' => $is_voice,
                'language' => $language
            );
            
        } catch (Exception $e) {
            error_log('Thorius Error: ' . $e->getMessage());
            
            // Localize error message
            $error_message = $this->get_localized_message('error_processing', $language);
            
            return array(
                'success' => false,
                'message' => $error_message,
                'language' => $language
            );
        }
    }

    /**
     * Initialize plugin components
     */
    public function initialize() {
        // Register shortcodes
        $this->register_shortcodes();
        
        // Initialize admin components if in admin
        if (is_admin()) {
            $this->init_admin();
        }
        
        // Initialize core components
        $this->init_analytics();
        $this->init_synthesis_reports();
        $this->init_admin_intelligence();
        $this->init_security();
        $this->init_consent_manager();
        $this->init_data_cleanup();
        
        // Initialize AI components
        $this->init_agent_orchestrator();
        $this->init_learning_system();
        $this->init_multimodal_system();
        $this->init_cache_system();
        $this->init_recovery_system();
        $this->init_user_library();
        
        // Initialize TOLA incentive system
        $this->init_tola_incentives();
        $this->register_tola_shortcodes();
        
        // Initialize artwork swap system
        $this->init_artwork_swap_system();
        
        // Register assets and AJAX handlers
        add_action('wp_enqueue_scripts', array($this, 'register_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'register_admin_assets'));
        $this->register_ajax_handlers();
    }

    /**
     * Initialize admin components
     */
    private function init_admin() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/admin/class-vortex-thorius-admin.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/admin/class-vortex-thorius-admin-tabs.php';
        
        $this->admin = new Vortex_Thorius_Admin();
        $this->admin_tabs = new Vortex_Thorius_Admin_Tabs();
    }

    /**
     * Initialize widget
     */
    private function init_widget() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/widgets/class-vortex-thorius-widget.php';
        
        // Register widget
        add_action('widgets_init', function() {
            register_widget('Vortex_Thorius_Widget');
        });
    }

    /**
     * Initialize analytics
     */
    private function init_analytics() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-thorius-analytics.php';
        $this->analytics = new Vortex_Thorius_Analytics();
    }

    /**
     * Initialize synthesis reports
     */
    private function init_synthesis_reports() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/admin/class-vortex-thorius-synthesis-reports.php';
        $this->synthesis_reports = new Vortex_Thorius_Synthesis_Reports();
    }

    /**
     * Initialize admin intelligence
     */
    private function init_admin_intelligence() {
        if (is_admin()) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'includes/admin/class-vortex-thorius-admin-intelligence.php';
            $this->admin_intelligence = new Vortex_Thorius_Admin_Intelligence();
        }
    }

    /**
     * Initialize security features
     */
    private function init_security() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-thorius-security.php';
        $this->security = new Vortex_Thorius_Security();
    }

    /**
     * Initialize consent manager
     */
    private function init_consent_manager() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-thorius-consent.php';
        $this->consent = new Vortex_Thorius_Consent();
    }

    /**
     * Initialize data cleanup manager
     */
    private function init_data_cleanup() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-thorius-data-cleanup.php';
        $this->data_cleanup = new Vortex_Thorius_Data_Cleanup();
    }

    /**
     * Initialize agent orchestrator
     */
    private function init_agent_orchestrator() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/agents/class-vortex-thorius-orchestrator.php';
        $this->orchestrator = new Vortex_Thorius_Orchestrator();
    }

    /**
     * Initialize learning system
     */
    private function init_learning_system() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-thorius-learning.php';
        $this->learning_system = new Vortex_Thorius_Learning();
        
        // Hook learning system into query processing
        add_action('thorius_query_processed', array($this->learning_system, 'track_interaction'), 10, 5);
        add_action('thorius_feedback_submitted', array($this->learning_system, 'process_feedback'), 10, 4);
        add_action('thorius_agent_interaction', array($this->learning_system, 'track_agent_interaction'), 10, 3);
        
        // Register machine learning tasks
        add_action('thorius_daily_adaptation_check', array($this->learning_system, 'run_daily_adaptation_checks'));
        add_action('thorius_weekly_model_training', array($this->learning_system, 'run_weekly_model_training'));
        
        // Add analytics hook for tracking learning performance
        add_filter('vortex_thorius_analytics_data', array($this->learning_system, 'add_learning_metrics'), 10, 1);
    }

    /**
     * Initialize multimodal system
     */
    private function init_multimodal_system() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-thorius-multimodal.php';
        $this->multimodal = new Vortex_Thorius_Multimodal();
    }

    /**
     * Initialize cache system
     */
    private function init_cache_system() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-thorius-cache.php';
        $this->cache = new Vortex_Thorius_Cache();
    }

    /**
     * Initialize recovery system
     */
    private function init_recovery_system() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-thorius-recovery.php';
        $this->recovery = new Vortex_Thorius_Recovery();
    }

    /**
     * Initialize user library components
     */
    private function init_user_library() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-thorius-user.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-thorius-session.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-thorius-history.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-thorius-context.php';
        
        $this->user_manager = new Vortex_Thorius_User();
        $this->session_manager = new Vortex_Thorius_Session();
        $this->history_manager = new Vortex_Thorius_History();
        $this->context_manager = new Vortex_Thorius_Context();
    }

    /**
     * Register AJAX handlers
     */
    private function register_ajax_handlers() {
        // Frontend AJAX handlers
        add_action('wp_ajax_vortex_thorius_query', array($this, 'ajax_process_query'));
        add_action('wp_ajax_nopriv_vortex_thorius_query', array($this, 'ajax_process_query'));
        
        // Admin AJAX handlers
        add_action('wp_ajax_vortex_thorius_admin_query', array($this, 'ajax_process_admin_query'));
        add_action('wp_ajax_vortex_thorius_generate_report', array($this, 'ajax_generate_report'));
        
        // Settings AJAX handlers
        add_action('wp_ajax_vortex_thorius_test_api_connection', array($this, 'ajax_test_api_connection'));
        
        // Register AJAX tab handlers
        $this->register_tab_handlers();
        
        // Enhanced AJAX handler for agent-specific queries
        add_action('wp_ajax_vortex_thorius_agent_query', array($this, 'ajax_process_agent_query'));
        
        // AJAX handler for collecting user feedback
        add_action('wp_ajax_vortex_thorius_collect_feedback', array($this, 'ajax_collect_feedback'));
    }

    /**
     * Register AJAX tab handlers
     */
    private function register_tab_handlers() {
        add_action('wp_ajax_thorius_save_tab_state', array($this, 'ajax_save_tab_state'));
        add_action('wp_ajax_thorius_load_tab_content', array($this, 'ajax_load_tab_content'));
    }

    /**
     * Save tab state to user meta
     */
    public function ajax_save_tab_state() {
        check_ajax_referer('thorius_tab_nonce', 'nonce');
        
        $container = isset($_POST['container']) ? sanitize_key($_POST['container']) : '';
        $tab = isset($_POST['tab']) ? sanitize_key($_POST['tab']) : '';
        
        if ($container && $tab) {
            $user_id = get_current_user_id();
            update_user_meta($user_id, "thorius_tab_state_{$container}", $tab);
            wp_send_json_success();
        }
        
        wp_send_json_error();
    }

    /**
     * Get saved tab state
     */
    private function get_saved_tab_state($container_id, $default = '') {
        $user_id = get_current_user_id();
        return get_user_meta($user_id, "thorius_tab_state_{$container_id}", true) ?: $default;
    }

    /**
     * Register all shortcodes
     */
    public function register_shortcodes() {
        // Main concierge shortcode
        add_shortcode('thorius_concierge', array($this, 'concierge_shortcode'));
        
        // Chat interface shortcode
        add_shortcode('thorius_chat', array($this, 'chat_shortcode'));
        
        // Agent-specific shortcode
        add_shortcode('thorius_agent', array($this, 'agent_shortcode'));
        
        // Include payment button shortcode
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/shortcodes/payment-button-shortcode.php';
    }

    /**
     * Thorius Concierge shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string Rendered shortcode
     */
    public function concierge_shortcode($atts) {
        // Extract attributes
        $atts = shortcode_atts(array(
            'theme' => 'light',
            'position' => 'bottom-right',
            'welcome_message' => __('Hello! I\'m Thorius, your AI concierge. How can I help you today?', 'vortex-ai-marketplace'),
            'placeholder' => __('Ask me anything...', 'vortex-ai-marketplace'),
            'voice' => 'true',
            'language' => 'en'
        ), $atts);
        
        // Convert string booleans to actual booleans
        $atts['voice'] = $atts['voice'] === 'true';
        
        // Register analytics event for shortcode usage
        if (class_exists('Vortex_Thorius_Analytics')) {
            $analytics = new Vortex_Thorius_Analytics();
            $analytics->track_event('thorius_init', array(
                'shortcode' => 'thorius_concierge',
                'post_id' => get_the_ID(),
                'post_type' => get_post_type(),
                'theme' => $atts['theme'],
                'position' => $atts['position'],
                'voice_enabled' => $atts['voice']
            ));
        }
        
        // Enqueue necessary scripts and styles
        wp_enqueue_style('thorius-concierge-css');
        wp_enqueue_script('thorius-concierge-js');
        
        // If voice is enabled, enqueue voice recognition script
        if ($atts['voice']) {
            wp_enqueue_script('thorius-voice-js');
        }
        
        // Generate unique ID for this concierge instance
        $concierge_id = 'thorius-concierge-' . uniqid();
        
        // Start output buffer
        ob_start();
        
        // Include template
        include plugin_dir_path(dirname(__FILE__)) . 'templates/thorius-concierge.php';
        
        // Return buffered content
        return ob_get_clean();
    }

    /**
     * Thorius Chat shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string Rendered shortcode
     */
    public function chat_shortcode($atts) {
        // Extract attributes
        $atts = shortcode_atts(array(
            'theme' => 'light',
            'welcome_message' => __('Hello! I\'m Thorius, your AI assistant. How can I help you today?', 'vortex-ai-marketplace'),
            'placeholder' => __('Type your message...', 'vortex-ai-marketplace'),
            'voice' => 'false',
            'language' => 'en',
            'height' => '400px'
        ), $atts);
        
        // Convert string booleans to actual booleans
        $atts['voice'] = $atts['voice'] === 'true';
        
        // Register analytics event for shortcode usage
        if (class_exists('Vortex_Thorius_Analytics')) {
            $analytics = new Vortex_Thorius_Analytics();
            $analytics->track_event('thorius_init', array(
                'shortcode' => 'thorius_chat',
                'post_id' => get_the_ID(),
                'post_type' => get_post_type(),
                'theme' => $atts['theme'],
                'voice_enabled' => $atts['voice']
            ));
        }
        
        // Enqueue necessary scripts and styles
        wp_enqueue_style('thorius-chat-css');
        wp_enqueue_script('thorius-chat-js');
        
        // If voice is enabled, enqueue voice recognition script
        if ($atts['voice']) {
            wp_enqueue_script('thorius-voice-js');
        }
        
        // Generate unique ID for this chat instance
        $chat_id = 'thorius-chat-' . uniqid();
        
        // Build HTML output
        $output = '<div id="' . esc_attr($chat_id) . '" class="thorius-chat-container" ';
        $output .= 'data-theme="' . esc_attr($atts['theme']) . '" ';
        $output .= 'data-voice="' . ($atts['voice'] ? 'true' : 'false') . '" ';
        $output .= 'data-language="' . esc_attr($atts['language']) . '" ';
        $output .= 'style="height: ' . esc_attr($atts['height']) . ';">';
        
        $output .= '<div class="thorius-chat-header">';
        $output .= '<div class="thorius-chat-header-title">Thorius AI</div>';
        $output .= '</div>';
        
        $output .= '<div class="thorius-chat-messages">';
        $output .= '<div class="thorius-message thorius-message-bot">';
        $output .= '<div class="thorius-avatar"></div>';
        $output .= '<div class="thorius-message-content">' . esc_html($atts['welcome_message']) . '</div>';
        $output .= '</div>';
        $output .= '</div>';
        
        $output .= '<div class="thorius-chat-input">';
        $output .= '<textarea placeholder="' . esc_attr($atts['placeholder']) . '" rows="1"></textarea>';
        if ($atts['voice']) {
            $output .= '<div class="thorius-voice-button"><span class="thorius-voice-icon"></span></div>';
        }
        $output .= '<div class="thorius-send-button"><span class="thorius-send-icon"></span></div>';
        $output .= '</div>';
        
        $output .= '</div>'; // Close container
        
        // Add initialization script
        $output .= '<script>
            document.addEventListener("DOMContentLoaded", function() {
                if (typeof ThoriusChat !== "undefined") {
                    new ThoriusChat("' . $chat_id . '");
                }
            });
        </script>';
        
        return $output;
    }

    /**
     * Thorius Agent shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string Rendered shortcode
     */
    public function agent_shortcode($atts) {
        // Extract and sanitize attributes
        $atts = shortcode_atts(array(
            'agent' => 'cloe',
            'theme' => 'light',
            'welcome_message' => '',
            'placeholder' => __('Ask me anything...', 'vortex-ai-marketplace'),
            'voice' => 'false',
            'language' => 'en',
            'height' => '400px'
        ), $atts);
        
        // Validate agent type
        $valid_agents = array('cloe', 'huraii', 'strategist');
        if (!in_array($atts['agent'], $valid_agents)) {
            $atts['agent'] = 'cloe';
        }
        
        // Convert voice attribute to boolean
        $atts['voice'] = filter_var($atts['voice'], FILTER_VALIDATE_BOOLEAN);
        
        // Sanitize height
        $atts['height'] = preg_match('/^\d+(%|px|em|rem)$/', $atts['height']) ? $atts['height'] : '400px';
        
        // Set default welcome message based on agent if not provided
        if (empty($atts['welcome_message'])) {
            switch ($atts['agent']) {
                case 'cloe':
                    $atts['welcome_message'] = __('Hello, I\'m CLOE, your AI assistant for conversation and knowledge. How can I help you?', 'vortex-ai-marketplace');
                    break;
                case 'huraii':
                    $atts['welcome_message'] = __('Hi there! I\'m HURAII, your art and image generation assistant. What shall we create today?', 'vortex-ai-marketplace');
                    break;
                case 'strategist':
                    $atts['welcome_message'] = __('Welcome! I\'m the Business Strategist. I can help with market analysis, business plans, and strategic insights.', 'vortex-ai-marketplace');
                    break;
            }
        }
        
        // Register analytics event for shortcode usage
        if (class_exists('Vortex_Thorius_Analytics')) {
            $analytics = new Vortex_Thorius_Analytics();
            $analytics->track_event('thorius_init', array(
                'shortcode' => 'thorius_agent',
                'agent' => $atts['agent'],
                'post_id' => get_the_ID(),
                'post_type' => get_post_type(),
                'theme' => $atts['theme'],
                'voice_enabled' => $atts['voice']
            ));
        }
        
        // Enqueue necessary scripts and styles
        wp_enqueue_style('thorius-agent-css');
        wp_enqueue_script('thorius-agent-js');
        
        // If voice is enabled, enqueue voice recognition script
        if ($atts['voice']) {
            wp_enqueue_script('thorius-voice-js');
        }
        
        // Generate unique ID for this agent instance
        $agent_id = 'thorius-agent-' . uniqid();
        
        // Build HTML output
        $output = '<div id="' . esc_attr($agent_id) . '" class="thorius-agent-container thorius-agent-' . esc_attr($atts['agent']) . '" ';
        $output .= 'data-agent="' . esc_attr($atts['agent']) . '" ';
        $output .= 'data-theme="' . esc_attr($atts['theme']) . '" ';
        $output .= 'data-voice="' . ($atts['voice'] ? 'true' : 'false') . '" ';
        $output .= 'data-language="' . esc_attr($atts['language']) . '" ';
        $output .= 'style="height: ' . esc_attr($atts['height']) . ';">';
        
        $output .= '<div class="thorius-agent-header">';
        $output .= '<div class="thorius-agent-header-title">' . strtoupper($atts['agent']) . '</div>';
        $output .= '</div>';
        
        $output .= '<div class="thorius-agent-messages">';
        $output .= '<div class="thorius-message thorius-message-bot">';
        $output .= '<div class="thorius-avatar thorius-avatar-' . esc_attr($atts['agent']) . '"></div>';
        $output .= '<div class="thorius-message-content">' . esc_html($atts['welcome_message']) . '</div>';
        $output .= '</div>';
        $output .= '</div>';
        
        $output .= '<div class="thorius-agent-input">';
        $output .= '<textarea placeholder="' . esc_attr($atts['placeholder']) . '" rows="1"></textarea>';
        if ($atts['voice']) {
            $output .= '<div class="thorius-voice-button"><span class="thorius-voice-icon"></span></div>';
        }
        $output .= '<div class="thorius-send-button"><span class="thorius-send-icon"></span></div>';
        $output .= '</div>';
        
        $output .= '</div>'; // Close container
        
        // Add initialization script
        $output .= '<script>
            document.addEventListener("DOMContentLoaded", function() {
                if (typeof ThoriusAgent !== "undefined") {
                    new ThoriusAgent("' . $agent_id . '");
                }
            });
        </script>';
        
        return $output;
    }

    /**
     * Register admin tabs
     */
    private function register_admin_tabs() {
        // Define main admin tabs
        $this->admin_tabs = array(
            'dashboard' => array(
                'title' => __('Dashboard', 'vortex-ai-marketplace'),
                'callback' => array($this, 'render_dashboard_tab')
            ),
            'agents' => array(
                'title' => __('AI Agents', 'vortex-ai-marketplace'),
                'callback' => array($this, 'render_agents_tab')
            ),
            'settings' => array(
                'title' => __('Settings', 'vortex-ai-marketplace'),
                'callback' => array($this, 'render_settings_tab')
            ),
            'analytics' => array(
                'title' => __('Analytics', 'vortex-ai-marketplace'),
                'callback' => array($this, 'render_analytics_tab')
            ),
            'logs' => array(
                'title' => __('Error Logs', 'vortex-ai-marketplace'),
                'callback' => array($this, 'render_logs_tab')
            )
        );
    }

    /**
     * Get last active tab
     */
    private function get_last_active_tab() {
        $last_tab = get_user_meta(get_current_user_id(), 'thorius_last_active_tab', true);
        return !empty($last_tab) ? $last_tab : 'dashboard';
    }

    /**
     * Save last active tab
     */
    private function save_last_active_tab($tab) {
        update_user_meta(get_current_user_id(), 'thorius_last_active_tab', $tab);
    }

    /**
     * Validate tab access
     */
    private function validate_tab_access($tab_id) {
        $required_capabilities = array(
            'dashboard' => 'manage_options',
            'agents' => 'manage_options',
            'settings' => 'manage_options',
            'analytics' => 'view_thorius_analytics',
            'logs' => 'manage_thorius_logs'
        );
        
        if (isset($required_capabilities[$tab_id])) {
            return current_user_can($required_capabilities[$tab_id]);
        }
        
        return false;
    }

    public function register_admin_assets() {
        wp_enqueue_script('thorius-admin', plugin_dir_url(__FILE__) . 'assets/js/thorius-admin.js', array('jquery'), '1.0.0', true);
        
        wp_localize_script('thorius-admin', 'thorius_admin_data', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('thorius_tab_nonce'),
            'is_logged_in' => is_user_logged_in()
        ));
    }

    /**
     * Process user query with context
     */
    public function process_query($query, $context = '', $user_id = 0) {
        // Get user context if logged in
        if ($user_id) {
            $user_context = $this->context_manager->get_context($user_id);
            $user_preferences = $this->user_manager->get_preferences($user_id);
            
            // Merge with provided context
            $context = array_merge(
                $user_context,
                array('preferences' => $user_preferences),
                $context ? array('provided' => $context) : array()
            );
        }
        
        // Process query with enhanced context
        $response = parent::process_query($query, $context, $user_id);
        
        // Track interaction
        if ($user_id) {
            $this->history_manager->track_interaction($user_id, 'query', array(
                'query' => $query,
                'response' => $response
            ));
        }
        
        return $response;
    }

    /**
     * Process query with emotion awareness
     */
    private function process_with_emotion_awareness($query, $user_id) {
        // Detect emotion in user query
        $emotion = $this->detect_emotion($query);
        
        // If user is frustrated, adjust response style
        if ($emotion['primary'] === 'frustrated' && $emotion['confidence'] > 0.7) {
            // Get user's previous interactions
            $previous_interactions = $this->history_manager->get_history($user_id, 5);
            
            // Analyze if there's a pattern of frustration
            $frustration_pattern = $this->analyze_frustration_pattern($previous_interactions);
            
            if ($frustration_pattern) {
                // Create empathetic response
                return $this->create_empathetic_response($query, $frustration_pattern);
            }
        }
        
        // If user is excited, match enthusiasm
        if ($emotion['primary'] === 'excited' && $emotion['confidence'] > 0.7) {
            return $this->create_enthusiastic_response($query);
        }
        
        // Return null if no emotion-specific handling needed
        return null;
    }

    /**
     * Detect emotion in text
     * 
     * @param string $text Text to analyze
     * @return array Emotion data
     */
    private function detect_emotion($text) {
        $emotions = array(
            'happy' => 0,
            'sad' => 0,
            'angry' => 0,
            'frustrated' => 0,
            'excited' => 0,
            'confused' => 0,
            'neutral' => 0
        );
        
        // Happy indicators
        $happy_patterns = array(
            '/thank you|thanks|appreciate|grateful/i',
            '/happy|glad|yay|awesome|excellent|great|love it/i',
            '/😊|😃|😄|😁|👍|❤️|🙏/u'
        );
        
        // Frustrated indicators
        $frustrated_patterns = array(
            '/not working|doesn\'t work|can\'t get it to work/i',
            '/confused|don\'t understand|unclear|makes no sense/i',
            '/tried|attempt|multiple times|again|still|yet/i',
            '/😤|😒|😑|🙄|😠|😡|😞/u'
        );
        
        // Excited indicators
        $excited_patterns = array(
            '/wow|amazing|incredible|unbelievable|cool/i',
            '/excited|can\'t wait|looking forward|eager/i',
            '/!{2,}|\?!|ALL CAPS|AMAZING/i',
            '/😲|🤩|😍|😱|🔥|✨|💯/u'
        );
        
        // Check patterns
        foreach ($happy_patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                $emotions['happy'] += 0.25;
            }
        }
        
        foreach ($frustrated_patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                $emotions['frustrated'] += 0.25;
            }
        }
        
        foreach ($excited_patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                $emotions['excited'] += 0.25;
            }
        }
        
        // Find primary emotion
        arsort($emotions);
        $primary_emotion = key($emotions);
        $confidence = current($emotions);
        
        // Default to neutral if no strong emotion
        if ($confidence < 0.25) {
            $primary_emotion = 'neutral';
            $confidence = 1.0;
        }
        
        return array(
            'primary' => $primary_emotion,
            'confidence' => $confidence,
            'emotions' => $emotions
        );
    }

    /**
     * Initialize TOLA incentive system
     */
    private function init_tola_incentives() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-tola-incentives.php';
        $this->tola_incentives = new Vortex_TOLA_Incentives();
        
        // Register hooks to reward AI agent interactions
        add_action('thorius_agent_interaction', array($this->tola_incentives, 'reward_agent_interaction'), 10, 3);
    }

    /**
     * Register TOLA shortcodes
     */
    private function register_tola_shortcodes() {
        add_shortcode('tola_balance', array($this->tola_incentives, 'balance_shortcode'));
        add_shortcode('tola_actions', array($this->tola_incentives, 'actions_shortcode'));
        add_shortcode('tola_rewards_dashboard', array($this->tola_incentives, 'rewards_dashboard_shortcode'));
        add_shortcode('tola_wallet_register', array($this->tola_incentives, 'register_wallet_shortcode'));
    }

    /**
     * Process agent query with rewards
     * 
     * @param string $query User query
     * @param array $context Conversation context
     * @param string $agent_id Agent ID
     * @return array Response data
     */
    public function process_agent_query($query, $context = array(), $agent_id = '') {
        // Start performance tracking
        $start_time = microtime(true);
        
        // Process query with specified agent
        $response = $this->orchestrator->process_with_specific_agent($agent_id, $query, $context);
        
        // Get current user
        $user_id = get_current_user_id();
        
        // Track interaction metrics
        $interaction_data = array(
            'query_length' => strlen($query),
            'response_length' => strlen($response['content'] ?? ''),
            'processing_time' => microtime(true) - $start_time
        );
        
        // Trigger reward if user is logged in
        if ($user_id) {
            do_action('thorius_agent_interaction', $user_id, $agent_id, $interaction_data);
        }
        
        return $response;
    }

    /**
     * Track user query for continuous learning
     * 
     * @param string $query User query
     * @param string $response AI response
     * @param array $context Context data
     * @param int $user_id User ID
     * @return void
     */
    public function track_query_for_learning($query, $response, $context = array(), $user_id = 0) {
        // Determine agent from context
        $agent = isset($context['agent']) ? $context['agent'] : 'thorius';
        
        // Get performance metrics
        $metrics = array(
            'response_time' => isset($context['response_time']) ? $context['response_time'] : 0,
            'confidence' => isset($context['confidence']) ? $context['confidence'] : 0.75,
            'tokens_used' => isset($context['tokens_used']) ? $context['tokens_used'] : 0
        );
        
        // Track interaction in learning system
        do_action('thorius_query_processed', $query, $response, $agent, $context, $metrics);
        
        // Log for analytics
        if (isset($this->analytics)) {
            $this->analytics->track_event('ai_interaction', array(
                'agent' => $agent,
                'query_length' => strlen($query),
                'response_length' => strlen($response),
                'metrics' => $metrics
            ));
        }
    }

    /**
     * Enhanced AJAX handler for agent-specific queries
     */
    public function ajax_process_agent_query() {
        check_ajax_referer('vortex_thorius_nonce', 'nonce');
        
        $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        $agent = isset($_POST['agent']) ? sanitize_key($_POST['agent']) : 'cloe';
        $context = isset($_POST['context']) ? wp_kses_post_deep($_POST['context']) : array();
        
        if (empty($query)) {
            wp_send_json_error(array('message' => __('Please enter a query.', 'vortex-ai-marketplace')));
            exit;
        }
        
        try {
            // Start timing for performance metrics
            $start_time = microtime(true);
            
            // Process with specific agent
            $response = $this->orchestrator->process_with_specific_agent($agent, $query, $context);
            
            // Calculate processing time
            $process_time = microtime(true) - $start_time;
            
            // Add metrics to context for learning system
            $context['response_time'] = $process_time;
            $context['confidence'] = $response['confidence'] ?? 0.8;
            $context['agent'] = $agent;
            
            // Track interaction for learning
            $this->track_query_for_learning($query, $response['content'], $context, get_current_user_id());
            
            // Trigger agent-specific reward if user is logged in
            if (is_user_logged_in()) {
                do_action('thorius_agent_interaction', get_current_user_id(), $agent, array(
                    'query' => $query,
                    'query_length' => strlen($query),
                    'response_length' => strlen($response['content']),
                    'processing_time' => $process_time
                ));
            }
            
            // Generate response suggestions using learning system
            $response['suggestions'] = $this->learning_system->generate_followup_suggestions($query, $response['content'], $agent);
            
            wp_send_json_success($response);
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
        
        exit;
    }

    /**
     * AJAX handler for collecting user feedback
     */
    public function ajax_collect_feedback() {
        check_ajax_referer('vortex_thorius_feedback_nonce', 'nonce');
        
        $interaction_id = isset($_POST['interaction_id']) ? sanitize_text_field($_POST['interaction_id']) : '';
        $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
        $feedback_text = isset($_POST['feedback']) ? sanitize_textarea_field($_POST['feedback']) : '';
        $agent = isset($_POST['agent']) ? sanitize_text_field($_POST['agent']) : '';
        
        if (empty($interaction_id)) {
            wp_send_json_error(array('message' => __('Invalid interaction ID.', 'vortex-ai-marketplace')));
            exit;
        }
        
        // Get user ID
        $user_id = get_current_user_id();
        
        // Process feedback through learning system
        do_action('thorius_feedback_submitted', $interaction_id, $rating, $feedback_text, $user_id);
        
        // Track in analytics
        if (isset($this->analytics)) {
            $this->analytics->track_event('ai_feedback', array(
                'agent' => $agent,
                'rating' => $rating,
                'has_text' => !empty($feedback_text),
                'user_id' => $user_id
            ));
        }
        
        // Add TOLA reward for providing feedback if user is logged in
        if ($user_id && isset($this->tola_incentives)) {
            $this->tola_incentives->reward_feedback($user_id, array(
                'rating' => $rating,
                'feedback_text' => $feedback_text,
                'agent' => $agent,
                'interaction_id' => $interaction_id
            ));
        }
        
        wp_send_json_success(array('message' => __('Thank you for your feedback!', 'vortex-ai-marketplace')));
        exit;
    }

    /**
     * Initialize artwork swap system
     */
    private function init_artwork_swap_system() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-artist-verification.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-artwork-verification.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-artwork-swap.php';
        
        $this->artwork_swap = new Vortex_Artwork_Swap();
        
        // Register shortcode
        add_shortcode('artist_swap_dashboard', array($this->artwork_swap, 'swap_dashboard_shortcode'));
    }

    /**
     * Register frontend assets
     */
    public function register_frontend_assets() {
        // Existing assets registration...
        
        // Artwork swap assets
        wp_register_style(
            'vortex-swap-dashboard-css',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/swap-dashboard.css',
            array(),
            '1.0.0'
        );
        
        wp_register_script(
            'vortex-swap-dashboard-js',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/swap-dashboard.js',
            array('jquery'),
            '1.0.0',
            true
        );
        
        // Localize swap dashboard script
        wp_localize_script('vortex-swap-dashboard-js', 'vortex_swap_data', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_swap_nonce'),
            'user_id' => get_current_user_id(),
            'artists_only_message' => __('Only artists can participate in artwork swaps.', 'vortex-ai-marketplace'),
            'login_message' => __('Please log in to access the swap dashboard.', 'vortex-ai-marketplace')
        ));
    }

    // Many more methods would be here, including all the action handlers, NLP processing, etc.
    // For brevity, I'm not including all of them, but they would follow similar patterns to what's shown.
} 