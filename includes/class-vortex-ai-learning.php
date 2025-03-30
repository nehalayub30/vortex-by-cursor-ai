<?php
/**
 * AI Learning System
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class for AI continuous learning capabilities
 */
class Vortex_AI_Learning {
    
    /**
     * Initialize the class
     */
    public function __construct() {
        // Register data collection hooks
        add_action('wp_footer', array($this, 'inject_tracking_script'));
        add_action('wp_ajax_vortex_track_interaction', array($this, 'track_interaction'));
        add_action('wp_ajax_nopriv_vortex_track_interaction', array($this, 'track_interaction'));
        
        // Register hooks for AI feature usage
        add_action('vortex_ai_api_request', array($this, 'track_ai_usage'), 10, 3);
        
        // Disable learning features if user hasn't consented
        add_filter('vortex_ai_request_params', array($this, 'check_learning_consent'), 10, 3);
    }
    
    /**
     * Create database tables for learning data storage
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table for storing user interactions
        $interactions_table = $wpdb->prefix . 'vortex_user_interactions';
        $sql1 = "CREATE TABLE IF NOT EXISTS $interactions_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NULL,
            session_id varchar(64) NOT NULL,
            interaction_type varchar(32) NOT NULL,
            page_url varchar(255) NOT NULL,
            interaction_data longtext NOT NULL,
            timestamp datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY session_id (session_id),
            KEY interaction_type (interaction_type)
        ) $charset_collate;";
        
        // Table for storing learning insights
        $insights_table = $wpdb->prefix . 'vortex_ai_insights';
        $sql2 = "CREATE TABLE IF NOT EXISTS $insights_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            ai_agent varchar(32) NOT NULL,
            insight_type varchar(32) NOT NULL,
            insight_data longtext NOT NULL,
            applied tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY ai_agent (ai_agent),
            KEY insight_type (insight_type),
            KEY applied (applied)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql1);
        dbDelta($sql2);
    }
    
    /**
     * Inject tracking script in footer
     */
    public function inject_tracking_script() {
        // Only inject if user has given consent
        if ($this->has_user_consented()) {
            ?>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Generate or get session ID
                let sessionId = localStorage.getItem('vortex_session_id');
                if (!sessionId) {
                    sessionId = 'session_' + Math.random().toString(36).substring(2, 15);
                    localStorage.setItem('vortex_session_id', sessionId);
                }
                
                // Track page view
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'vortex_track_interaction',
                        interaction_type: 'page_view',
                        page_url: window.location.href,
                        session_id: sessionId,
                        interaction_data: JSON.stringify({
                            title: document.title,
                            referrer: document.referrer
                        })
                    }
                });
                
                // Track AI form interactions
                $(document).on('submit', '.vortex-artwork-generator-form, .vortex-analysis-form, .vortex-strategy-generator-form', function() {
                    const formData = {};
                    $(this).serializeArray().forEach(item => {
                        formData[item.name] = item.value;
                    });
                    
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'vortex_track_interaction',
                            interaction_type: 'ai_form_submit',
                            page_url: window.location.href,
                            session_id: sessionId,
                            interaction_data: JSON.stringify({
                                form_id: $(this).closest('div[id]').attr('id'),
                                form_data: formData
                            })
                        }
                    });
                });
                
                // Track result engagement
                $(document).on('click', '.vortex-generated-artwork, .vortex-analysis-content, .vortex-strategy-content', function() {
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        type: 'POST',
                        data: {
                            action: 'vortex_track_interaction',
                            interaction_type: 'result_engagement',
                            page_url: window.location.href,
                            session_id: sessionId,
                            interaction_data: JSON.stringify({
                                element_class: $(this).attr('class'),
                                element_id: $(this).closest('div[id]').attr('id')
                            })
                        }
                    });
                });
            });
            </script>
            <?php
        }
    }
    
    /**
     * Track user interaction via AJAX
     */
    public function track_interaction() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_user_interactions';
        
        $interaction_type = isset($_POST['interaction_type']) ? sanitize_text_field($_POST['interaction_type']) : '';
        $page_url = isset($_POST['page_url']) ? esc_url_raw($_POST['page_url']) : '';
        $session_id = isset($_POST['session_id']) ? sanitize_text_field($_POST['session_id']) : '';
        $interaction_data = isset($_POST['interaction_data']) ? sanitize_text_field($_POST['interaction_data']) : '{}';
        
        if (empty($interaction_type) || empty($page_url) || empty($session_id)) {
            wp_die();
        }
        
        $user_id = get_current_user_id();
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id > 0 ? $user_id : null,
                'session_id' => $session_id,
                'interaction_type' => $interaction_type,
                'page_url' => $page_url,
                'interaction_data' => $interaction_data,
                'timestamp' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s')
        );
        
        wp_die();
    }
    
    /**
     * Track AI usage for learning
     *
     * @param string $provider The AI provider
     * @param array $request The request parameters
     * @param array $response The response data
     */
    public function track_ai_usage($provider, $request, $response) {
        // Only proceed if this is a learnable interaction
        if (!$this->is_learnable_interaction($provider, $request)) {
            return;
        }
        
        // Map provider to our AI agents
        $ai_agent = $this->map_provider_to_agent($provider);
        if (empty($ai_agent)) {
            return;
        }
        
        // Extract useful data from request and response
        $request_data = array(
            'prompt' => isset($request['prompt']) ? $request['prompt'] : '',
            'task' => isset($request['task']) ? $request['task'] : '',
            'parameters' => array_diff_key($request, array_flip(['prompt', 'task', 'api_key']))
        );
        
        $response_data = array(
            'content' => isset($response['content']) ? $response['content'] : '',
            'model' => isset($response['model']) ? $response['model'] : '',
            'token_count' => isset($response['raw_response']['token_count']) ? $response['raw_response']['token_count'] : 0
        );
        
        // Store the interaction for learning
        $this->store_learning_data($ai_agent, $request_data, $response_data);
        
        // Schedule learning process
        if (!wp_next_scheduled('vortex_process_ai_learning')) {
            wp_schedule_event(time() + 3600, 'daily', 'vortex_process_ai_learning');
        }
    }
    
    /**
     * Check if user has consented to AI learning
     *
     * @param int $user_id User ID (optional)
     * @return bool Whether user has consented
     */
    public function has_user_consented($user_id = 0) {
        if ($user_id === 0) {
            $user_id = get_current_user_id();
        }
        
        if ($user_id === 0) {
            return false; // Not logged in
        }
        
        return (bool) get_user_meta($user_id, 'vortex_ai_learning_consent', true);
    }
    
    /**
     * Store user consent
     *
     * @param int $user_id User ID
     * @param bool $consent Whether user consents
     */
    public function store_user_consent($user_id, $consent = true) {
        update_user_meta($user_id, 'vortex_ai_learning_consent', $consent ? 1 : 0);
        update_user_meta($user_id, 'vortex_ai_consent_date', current_time('mysql'));
    }
    
    /**
     * Check learning consent and modify request parameters
     *
     * @param array $params Request parameters
     * @param string $task Task type
     * @param int $user_id User ID
     * @return array Modified parameters
     */
    public function check_learning_consent($params, $task, $user_id) {
        // Add learnable flag based on user consent
        $params['learn_from_interaction'] = $this->has_user_consented($user_id);
        return $params;
    }
    
    /**
     * Determine if interaction is learnable
     *
     * @param string $provider Provider name
     * @param array $request Request data
     * @return bool Whether interaction is learnable
     */
    private function is_learnable_interaction($provider, $request) {
        // Check if learning flag is present
        if (isset($request['learn_from_interaction']) && !$request['learn_from_interaction']) {
            return false;
        }
        
        // Only our own AI agents can learn
        $internal_providers = array('huraii', 'cloe', 'strategist');
        return in_array(strtolower($provider), $internal_providers);
    }
    
    /**
     * Map provider to agent
     *
     * @param string $provider Provider name
     * @return string Agent name
     */
    private function map_provider_to_agent($provider) {
        $provider = strtolower($provider);
        
        switch ($provider) {
            case 'huraii':
                return 'HURAII';
            case 'cloe':
                return 'CLOE';
            case 'strategist':
                return 'STRATEGIST';
            default:
                return '';
        }
    }
    
    /**
     * Store learning data
     *
     * @param string $ai_agent AI agent name
     * @param array $request_data Request data
     * @param array $response_data Response data
     */
    private function store_learning_data($ai_agent, $request_data, $response_data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_ai_learning_data';
        
        $wpdb->insert(
            $table_name,
            array(
                'ai_agent' => $ai_agent,
                'request_data' => json_encode($request_data),
                'response_data' => json_encode($response_data),
                'user_id' => get_current_user_id(),
                'timestamp' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%d', '%s')
        );
    }
    
    /**
     * Process scheduled learning tasks
     */
    public function process_learning() {
        // This would implement the actual machine learning algorithms
        // For each AI agent, analyze collected data and generate insights
        
        $this->process_agent_learning('HURAII', 'artwork');
        $this->process_agent_learning('CLOE', 'market');
        $this->process_agent_learning('STRATEGIST', 'strategy');
    }
    
    /**
     * Process learning for specific agent
     *
     * @param string $agent Agent name
     * @param string $task_type Task type
     */
    private function process_agent_learning($agent, $task_type) {
        // In a real implementation, this would:
        // 1. Retrieve relevant interaction data
        // 2. Process data using appropriate algorithms
        // 3. Generate insights and model improvements
        // 4. Update agent behavior based on learning
        
        // For demonstration, we'll just log that learning occurred
        error_log(sprintf(
            'Vortex AI Learning: Processed learning data for %s agent on %s tasks',
            $agent,
            $task_type
        ));
    }
} 