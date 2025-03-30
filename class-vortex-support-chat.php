<?php
/**
 * VORTEX Support Chat
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage Support
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_Support_Chat Class
 * 
 * Handles customer support chat functionality with AI integration.
 *
 * @since 1.0.0
 */
class VORTEX_Support_Chat {
    /**
     * Instance of this class.
     */
    protected static $instance = null;
    
    /**
     * AI agents
     */
    private $ai_agents = array();
    
    /**
     * Chat sessions
     */
    private $chat_sessions = array();
    
    /**
     * Learning data
     */
    private $learning_data = array();
    
    /**
     * Constructor
     */
    private function __construct() {
        // Initialize AI agents
        $this->initialize_ai_agents();
        
        // Set up hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_vortex_support_chat', array($this, 'handle_chat_message'));
        add_action('wp_ajax_nopriv_vortex_support_chat', array($this, 'handle_chat_message'));
        add_action('wp_ajax_vortex_support_feedback', array($this, 'handle_user_feedback'));
        add_action('wp_ajax_nopriv_vortex_support_feedback', array($this, 'handle_user_feedback'));
        
        // Add shortcode
        add_shortcode('vortex_support_chat', array($this, 'render_chat_interface'));
        
        // Schedule learning consolidation
        if (!wp_next_scheduled('vortex_support_learning_consolidation')) {
            wp_schedule_event(time(), 'daily', 'vortex_support_learning_consolidation');
        }
        add_action('vortex_support_learning_consolidation', array($this, 'consolidate_learning'));
    }
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize AI agents
     */
    private function initialize_ai_agents() {
        // Get AI coordinator instance
        $coordinator = VORTEX_AI_Coordinator::get_instance();
        
        // Initialize HURAII for natural language processing with deep learning
        $this->ai_agents['huraii'] = new VORTEX_HURAII();
        $this->ai_agents['huraii']->enable_deep_learning();
        $this->ai_agents['huraii']->set_learning_rate(0.001);
        $this->ai_agents['huraii']->enable_continuous_learning();
        $this->ai_agents['huraii']->set_context_window(1000);
        
        // Initialize CLoe for context understanding with deep learning
        $this->ai_agents['cloe'] = new VORTEX_CLoe();
        $this->ai_agents['cloe']->enable_deep_learning();
        $this->ai_agents['cloe']->set_learning_rate(0.001);
        $this->ai_agents['cloe']->enable_continuous_learning();
        $this->ai_agents['cloe']->set_context_window(1000);
        
        // Initialize Business Strategist for business logic with deep learning
        $this->ai_agents['business_strategist'] = new VORTEX_Business_Strategist();
        $this->ai_agents['business_strategist']->enable_deep_learning();
        $this->ai_agents['business_strategist']->set_learning_rate(0.001);
        $this->ai_agents['business_strategist']->enable_continuous_learning();
        $this->ai_agents['business_strategist']->set_context_window(1000);
        
        // Set up cross-agent learning
        $this->setup_cross_agent_learning();
        
        // Initialize learning data storage
        $this->initialize_learning_storage();
        
        // Set up monitoring
        $this->setup_monitoring();
    }
    
    /**
     * Initialize learning storage
     */
    private function initialize_learning_storage() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Create learning data table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}vortex_learning_data (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            agent_type varchar(50) NOT NULL,
            input_data text NOT NULL,
            output_data text NOT NULL,
            context_data text NOT NULL,
            feedback_score float,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Set up cross-agent learning
     */
    private function setup_cross_agent_learning() {
        // Enable cross-agent learning between HURAII and CLoe
        $this->ai_agents['huraii']->enable_cross_learning($this->ai_agents['cloe']);
        
        // Enable cross-agent learning between CLoe and Business Strategist
        $this->ai_agents['cloe']->enable_cross_learning($this->ai_agents['business_strategist']);
        
        // Enable cross-agent learning between HURAII and Business Strategist
        $this->ai_agents['huraii']->enable_cross_learning($this->ai_agents['business_strategist']);
    }
    
    /**
     * Set up monitoring
     */
    private function setup_monitoring() {
        // Add monitoring hooks
        add_action('vortex_support_chat_message', array($this, 'monitor_chat_message'), 10, 2);
        add_action('vortex_support_chat_response', array($this, 'monitor_chat_response'), 10, 2);
        add_action('vortex_support_chat_error', array($this, 'monitor_chat_error'), 10, 2);
        
        // Schedule monitoring tasks
        if (!wp_next_scheduled('vortex_support_monitoring_check')) {
            wp_schedule_event(time(), 'hourly', 'vortex_support_monitoring_check');
        }
        add_action('vortex_support_monitoring_check', array($this, 'check_monitoring_metrics'));
    }
    
    /**
     * Monitor chat message
     */
    public function monitor_chat_message($message, $session_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_chat_monitoring';
        
        $wpdb->insert(
            $table_name,
            array(
                'session_id' => $session_id,
                'event_type' => 'message',
                'event_data' => json_encode(array(
                    'message' => $message,
                    'timestamp' => current_time('mysql'),
                    'user_id' => get_current_user_id()
                ))
            ),
            array('%s', '%s', '%s')
        );
    }
    
    /**
     * Monitor chat response
     */
    public function monitor_chat_response($response, $session_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_chat_monitoring';
        
        $wpdb->insert(
            $table_name,
            array(
                'session_id' => $session_id,
                'event_type' => 'response',
                'event_data' => json_encode(array(
                    'response' => $response,
                    'timestamp' => current_time('mysql'),
                    'processing_time' => microtime(true) - $this->start_time
                ))
            ),
            array('%s', '%s', '%s')
        );
    }
    
    /**
     * Monitor chat error
     */
    public function monitor_chat_error($error, $session_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_chat_monitoring';
        
        $wpdb->insert(
            $table_name,
            array(
                'session_id' => $session_id,
                'event_type' => 'error',
                'event_data' => json_encode(array(
                    'error' => $error,
                    'timestamp' => current_time('mysql'),
                    'user_id' => get_current_user_id()
                ))
            ),
            array('%s', '%s', '%s')
        );
    }
    
    /**
     * Check monitoring metrics
     */
    public function check_monitoring_metrics() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_chat_monitoring';
        
        // Get metrics for the last hour
        $metrics = $wpdb->get_results(
            "SELECT event_type, COUNT(*) as count 
             FROM {$table_name} 
             WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
             GROUP BY event_type"
        );
        
        // Check for anomalies
        foreach ($metrics as $metric) {
            if ($metric->event_type === 'error' && $metric->count > 10) {
                $this->handle_anomaly('high_error_rate', $metric->count);
            }
        }
    }
    
    /**
     * Handle monitoring anomaly
     */
    private function handle_anomaly($type, $value) {
        // Log anomaly
        error_log("VORTEX Support Chat Anomaly: {$type} - Value: {$value}");
        
        // Notify admin if needed
        if ($type === 'high_error_rate') {
            $admin_email = get_option('admin_email');
            wp_mail(
                $admin_email,
                'VORTEX Support Chat Anomaly Detected',
                "High error rate detected in support chat: {$value} errors in the last hour."
            );
        }
    }
    
    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_style(
            'vortex-support-chat',
            plugin_dir_url(__FILE__) . 'css/support-chat.css',
            array(),
            VORTEX_VERSION
        );
        
        wp_enqueue_script(
            'vortex-support-chat',
            plugin_dir_url(__FILE__) . 'js/support-chat.js',
            array('jquery'),
            VORTEX_VERSION,
            true
        );
        
        wp_localize_script('vortex-support-chat', 'vortexSupportChat', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_support_chat_nonce'),
            'feedback_nonce' => wp_create_nonce('vortex_support_feedback_nonce'),
            'typing_indicator' => __('AI is typing...', 'vortex-ai-marketplace'),
            'error_message' => __('Sorry, I encountered an error. Please try again.', 'vortex-ai-marketplace')
        ));
    }
    
    /**
     * Handle chat message
     */
    public function handle_chat_message() {
        try {
            // Verify nonce
            check_ajax_referer('vortex_support_chat_nonce', 'nonce');
            
            // Rate limiting
            if (!$this->check_rate_limit()) {
                wp_send_json_error(array(
                    'message' => __('Too many requests. Please try again later.', 'vortex-ai-marketplace')
                ));
            }
            
            // Sanitize and validate input
            $message = $this->sanitize_message($_POST['message']);
            $session_id = $this->validate_session_id($_POST['session_id']);
            $user_id = get_current_user_id();
            
            // Check for malicious content
            if ($this->contains_malicious_content($message)) {
                $this->log_security_event('malicious_content', array(
                    'message' => $message,
                    'user_id' => $user_id,
                    'session_id' => $session_id
                ));
                wp_send_json_error(array(
                    'message' => __('Invalid content detected.', 'vortex-ai-marketplace')
                ));
            }
            
            // Process message with AI agents
            $response = $this->process_message($message, $session_id, $user_id);
            
            // Log successful interaction
            $this->log_security_event('successful_interaction', array(
                'message' => $message,
                'user_id' => $user_id,
                'session_id' => $session_id
            ));
            
            wp_send_json_success(array(
                'response' => $response,
                'session_id' => $session_id
            ));
            
        } catch (Exception $e) {
            // Log error
            $this->log_security_event('error', array(
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
            
            // Send error response
            wp_send_json_error(array(
                'message' => __('An error occurred. Please try again later.', 'vortex-ai-marketplace')
            ));
        }
    }
    
    /**
     * Check rate limiting
     */
    private function check_rate_limit() {
        $user_id = get_current_user_id();
        $ip = $this->get_client_ip();
        
        $transient_key = 'vortex_chat_rate_' . ($user_id ? $user_id : $ip);
        $rate_limit = get_transient($transient_key);
        
        if ($rate_limit === false) {
            set_transient($transient_key, 1, 60); // 1 minute
            return true;
        }
        
        if ($rate_limit >= 30) { // 30 messages per minute
            return false;
        }
        
        set_transient($transient_key, $rate_limit + 1, 60);
        return true;
    }
    
    /**
     * Get client IP
     */
    private function get_client_ip() {
        $ip = '';
        
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_X_FORWARDED']))
            $ip = $_SERVER['HTTP_X_FORWARDED'];
        else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ip = $_SERVER['HTTP_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_FORWARDED']))
            $ip = $_SERVER['HTTP_FORWARDED'];
        else if(isset($_SERVER['REMOTE_ADDR']))
            $ip = $_SERVER['REMOTE_ADDR'];
        
        return $ip;
    }
    
    /**
     * Sanitize message
     */
    private function sanitize_message($message) {
        // Remove HTML tags
        $message = wp_strip_all_tags($message);
        
        // Sanitize text
        $message = sanitize_text_field($message);
        
        // Remove excessive whitespace
        $message = preg_replace('/\s+/', ' ', $message);
        
        // Trim
        $message = trim($message);
        
        return $message;
    }
    
    /**
     * Validate session ID
     */
    private function validate_session_id($session_id) {
        if (!preg_match('/^vortex-chat-[a-zA-Z0-9]{9}$/', $session_id)) {
            throw new Exception('Invalid session ID format');
        }
        return $session_id;
    }
    
    /**
     * Check for malicious content
     */
    private function contains_malicious_content($message) {
        // Check for SQL injection attempts
        if (preg_match('/(SELECT|INSERT|UPDATE|DELETE|DROP|UNION|ALTER)/i', $message)) {
            return true;
        }
        
        // Check for XSS attempts
        if (preg_match('/(<script|javascript:|on\w+\s*=)/i', $message)) {
            return true;
        }
        
        // Check for command injection attempts
        if (preg_match('/(;|&&|\|\||`|exec|system|shell_exec)/i', $message)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Log security event
     */
    private function log_security_event($event_type, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_security_log';
        
        $wpdb->insert(
            $table_name,
            array(
                'event_type' => $event_type,
                'event_data' => json_encode($data),
                'ip_address' => $this->get_client_ip(),
                'user_id' => get_current_user_id(),
                'timestamp' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%d', '%s')
        );
    }
    
    /**
     * Process message with AI agents
     */
    private function process_message($message, $session_id, $user_id) {
        // Get context from CLoe with deep learning
        $context = $this->ai_agents['cloe']->analyze_context($message, true);
        
        // Get user context if available
        if ($user_id) {
            $user_context = $this->get_user_context($user_id);
            $context = array_merge($context, $user_context);
        }
        
        // Process with HURAII using deep learning
        $nlp_response = $this->ai_agents['huraii']->process_message($message, $context, true);
        
        // Get business logic from Business Strategist using deep learning
        $business_response = $this->ai_agents['business_strategist']->analyze_support_request($message, $context, true);
        
        // Get sentiment analysis with deep learning
        $sentiment = $this->ai_agents['huraii']->analyze_sentiment($message, true);
        
        // Get intent classification with deep learning
        $intent = $this->ai_agents['cloe']->classify_intent($message, true);
        
        // Store learning data
        $this->store_learning_data('huraii', $message, $nlp_response, $context);
        $this->store_learning_data('cloe', $message, $intent, $context);
        $this->store_learning_data('business_strategist', $message, $business_response, $context);
        
        // Combine responses with advanced logic
        $response = $this->combine_responses($nlp_response, $business_response, $sentiment, $intent);
        
        // Store in session with metadata
        $this->store_chat_session($session_id, $message, $response, array(
            'sentiment' => $sentiment,
            'intent' => $intent,
            'context' => $context,
            'user_id' => $user_id,
            'timestamp' => time()
        ));
        
        return $response;
    }
    
    /**
     * Get user context
     */
    private function get_user_context($user_id) {
        $context = array();
        
        // Get user's purchase history
        $purchases = $this->get_user_purchases($user_id);
        if (!empty($purchases)) {
            $context['purchase_history'] = $purchases;
        }
        
        // Get user's previous support interactions
        $previous_interactions = $this->get_user_support_history($user_id);
        if (!empty($previous_interactions)) {
            $context['support_history'] = $previous_interactions;
        }
        
        // Get user's preferences
        $preferences = $this->get_user_preferences($user_id);
        if (!empty($preferences)) {
            $context['preferences'] = $preferences;
        }
        
        return $context;
    }
    
    /**
     * Combine AI responses with advanced logic
     */
    private function combine_responses($nlp_response, $business_response, $sentiment, $intent) {
        // Implement advanced response combination logic
        $response = $nlp_response;
        
        // Add business context if needed
        if ($intent['type'] === 'purchase' || $intent['type'] === 'pricing') {
            $response = $business_response;
        }
        
        // Adjust tone based on sentiment
        if ($sentiment['score'] < 0.3) {
            $response = $this->adjust_tone($response, 'empathetic');
        } elseif ($sentiment['score'] > 0.7) {
            $response = $this->adjust_tone($response, 'enthusiastic');
        }
        
        // Add relevant suggestions based on intent
        $suggestions = $this->generate_suggestions($intent);
        if (!empty($suggestions)) {
            $response .= "\n\n" . implode("\n", $suggestions);
        }
        
        return $response;
    }
    
    /**
     * Adjust response tone
     */
    private function adjust_tone($response, $tone) {
        // Implement tone adjustment logic
        return $response;
    }
    
    /**
     * Generate relevant suggestions
     */
    private function generate_suggestions($intent) {
        $suggestions = array();
        
        switch ($intent['type']) {
            case 'purchase':
                $suggestions[] = __('Would you like to see similar artworks?', 'vortex-ai-marketplace');
                break;
                
            case 'technical':
                $suggestions[] = __('Would you like me to check our technical documentation?', 'vortex-ai-marketplace');
                break;
                
            case 'account':
                $suggestions[] = __('Would you like to visit your account settings?', 'vortex-ai-marketplace');
                break;
        }
        
        return $suggestions;
    }
    
    /**
     * Store chat session with metadata
     */
    private function store_chat_session($session_id, $message, $response, $metadata) {
        if (!isset($this->chat_sessions[$session_id])) {
            $this->chat_sessions[$session_id] = array();
        }
        
        $this->chat_sessions[$session_id][] = array(
            'message' => $message,
            'response' => $response,
            'metadata' => $metadata
        );
        
        // Store in database for persistence
        $this->store_session_in_db($session_id, $message, $response, $metadata);
    }
    
    /**
     * Store session in database
     */
    private function store_session_in_db($session_id, $message, $response, $metadata) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_chat_sessions';
        
        $wpdb->insert(
            $table_name,
            array(
                'session_id' => $session_id,
                'message' => $message,
                'response' => $response,
                'metadata' => json_encode($metadata),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Store learning data
     */
    private function store_learning_data($agent_type, $input_data, $output_data, $context_data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_learning_data';
        
        $wpdb->insert(
            $table_name,
            array(
                'agent_type' => $agent_type,
                'input_data' => json_encode($input_data),
                'output_data' => json_encode($output_data),
                'context_data' => json_encode($context_data)
            ),
            array('%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Handle user feedback
     */
    public function handle_user_feedback() {
        check_ajax_referer('vortex_support_feedback_nonce', 'nonce');
        
        $session_id = sanitize_text_field($_POST['session_id']);
        $message_id = intval($_POST['message_id']);
        $feedback = sanitize_text_field($_POST['feedback']);
        $user_id = get_current_user_id();
        
        // Store feedback
        $this->store_feedback($session_id, $message_id, $feedback, $user_id);
        
        // Update learning from feedback
        $this->update_learning_from_feedback($session_id, $message_id, $feedback);
        
        wp_send_json_success();
    }
    
    /**
     * Store feedback
     */
    private function store_feedback($session_id, $message_id, $feedback, $user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_chat_feedback';
        
        $wpdb->insert(
            $table_name,
            array(
                'session_id' => $session_id,
                'message_id' => $message_id,
                'feedback' => $feedback,
                'user_id' => $user_id,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%d', '%s', '%d', '%s')
        );
    }
    
    /**
     * Update learning from feedback
     */
    private function update_learning_from_feedback($session_id, $message_id, $feedback) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_chat_sessions';
        $session = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE session_id = %s AND id = %d",
            $session_id,
            $message_id
        ));
        
        if ($session) {
            $metadata = json_decode($session->metadata, true);
            
            // Update learning data with feedback
            $this->update_learning_data_with_feedback(
                $session->message,
                $session->response,
                $feedback,
                $metadata['context']
            );
            
            // Update AI agents with feedback using deep learning
            $this->ai_agents['huraii']->learn_from_feedback($session->message, $session->response, $feedback, true);
            $this->ai_agents['cloe']->learn_from_feedback($session->message, $session->response, $feedback, true);
            $this->ai_agents['business_strategist']->learn_from_feedback($session->message, $session->response, $feedback, true);
        }
    }
    
    /**
     * Update learning data with feedback
     */
    private function update_learning_data_with_feedback($message, $response, $feedback, $context) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_learning_data';
        
        // Update feedback score for recent learning data
        $wpdb->update(
            $table_name,
            array('feedback_score' => floatval($feedback)),
            array(
                'input_data' => json_encode($message),
                'output_data' => json_encode($response),
                'context_data' => json_encode($context)
            ),
            array('%f'),
            array('%s', '%s', '%s')
        );
    }
    
    /**
     * Render the chat interface
     */
    public function render_chat_interface($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'title' => 'VORTEX Support',
            'placeholder' => 'Type your message...',
            'button_text' => 'Send',
            'theme' => 'light',
            'position' => 'bottom-right',
            'width' => '350px',
            'height' => '500px',
            'primary_color' => '#007bff',
            'secondary_color' => '#6c757d',
            'background_color' => '#ffffff',
            'text_color' => '#333333',
            'font_family' => 'inherit',
            'border_radius' => '10px',
            'box_shadow' => '0 5px 15px rgba(0,0,0,0.1)',
            'animation_speed' => '0.3s',
            'show_feedback' => 'true',
            'show_typing' => 'true',
            'show_suggestions' => 'true',
            'max_suggestions' => '3',
            'custom_css' => '',
            'custom_js' => ''
        ), $atts);

        // Convert string booleans to actual booleans
        $atts['show_feedback'] = $this->string_to_bool($atts['show_feedback']);
        $atts['show_typing'] = $this->string_to_bool($atts['show_typing']);
        $atts['show_suggestions'] = $this->string_to_bool($atts['show_suggestions']);

        // Generate unique ID for this chat instance
        $chat_id = 'vortex-chat-' . uniqid();

        // Start output buffering
        ob_start();
        ?>
        <div id="<?php echo esc_attr($chat_id); ?>" 
             class="vortex-support-chat" 
             data-theme="<?php echo esc_attr($atts['theme']); ?>"
             data-position="<?php echo esc_attr($atts['position']); ?>"
             data-show-feedback="<?php echo esc_attr($atts['show_feedback']); ?>"
             data-show-typing="<?php echo esc_attr($atts['show_typing']); ?>"
             data-show-suggestions="<?php echo esc_attr($atts['show_suggestions']); ?>"
             data-max-suggestions="<?php echo esc_attr($atts['max_suggestions']); ?>"
             style="
                width: <?php echo esc_attr($atts['width']); ?>;
                height: <?php echo esc_attr($atts['height']); ?>;
                background-color: <?php echo esc_attr($atts['background_color']); ?>;
                color: <?php echo esc_attr($atts['text_color']); ?>;
                font-family: <?php echo esc_attr($atts['font_family']); ?>;
                border-radius: <?php echo esc_attr($atts['border_radius']); ?>;
                box-shadow: <?php echo esc_attr($atts['box_shadow']); ?>;
                transition: all <?php echo esc_attr($atts['animation_speed']); ?> ease;
                <?php echo esc_attr($atts['custom_css']); ?>
             ">
            <div class="vortex-chat-header" style="background-color: <?php echo esc_attr($atts['primary_color']); ?>;">
                <h3><?php echo esc_html($atts['title']); ?></h3>
                <button class="vortex-chat-close">&times;</button>
            </div>
            <div class="vortex-chat-messages"></div>
            <?php if ($atts['show_typing']): ?>
            <div class="vortex-chat-typing-indicator">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <?php endif; ?>
            <div class="vortex-chat-input">
                <input type="text" 
                       placeholder="<?php echo esc_attr($atts['placeholder']); ?>"
                       style="border-color: <?php echo esc_attr($atts['secondary_color']); ?>;">
                <button style="background-color: <?php echo esc_attr($atts['primary_color']); ?>;">
                    <?php echo esc_html($atts['button_text']); ?>
                </button>
            </div>
            <?php if ($atts['show_feedback']): ?>
            <div class="vortex-chat-feedback">
                <span class="feedback-label">Was this helpful?</span>
                <div class="feedback-buttons">
                    <button class="feedback-yes" data-rating="1">👍</button>
                    <button class="feedback-no" data-rating="0">👎</button>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($atts['show_suggestions']): ?>
            <div class="vortex-chat-suggestions"></div>
            <?php endif; ?>
        </div>

        <?php if ($atts['custom_js']): ?>
        <script>
            jQuery(document).ready(function($) {
                <?php echo $atts['custom_js']; ?>
            });
        </script>
        <?php endif; ?>

        <?php
        return ob_get_clean();
    }
    
    /**
     * Convert string to boolean
     */
    private function string_to_bool($string) {
        return filter_var($string, FILTER_VALIDATE_BOOLEAN);
    }
    
    /**
     * Consolidate learning
     */
    public function consolidate_learning() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_learning_data';
        
        // Get learning data from the last 7 days
        $learning_data = $wpdb->get_results(
            "SELECT * FROM {$table_name} WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
        
        // Process learning data by agent type
        $agent_data = array();
        foreach ($learning_data as $data) {
            if (!isset($agent_data[$data->agent_type])) {
                $agent_data[$data->agent_type] = array();
            }
            $agent_data[$data->agent_type][] = array(
                'input' => json_decode($data->input_data, true),
                'output' => json_decode($data->output_data, true),
                'context' => json_decode($data->context_data, true),
                'feedback' => $data->feedback_score
            );
        }
        
        // Update each agent with consolidated data
        foreach ($agent_data as $agent_type => $data) {
            if (isset($this->ai_agents[$agent_type])) {
                $this->ai_agents[$agent_type]->consolidate_learning($data, true);
            }
        }
    }
}

// Initialize Support Chat
VORTEX_Support_Chat::get_instance(); 