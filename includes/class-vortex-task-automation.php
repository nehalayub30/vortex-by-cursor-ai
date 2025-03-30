<?php
/**
 * Task Automation System
 * 
 * Enables AI agents to automate routine tasks, anticipate user needs,
 * and proactively generate content based on learned patterns.
 */
class Vortex_Task_Automation {
    
    /**
     * Initialize task automation system
     */
    public function __construct() {
        // Register automated task hooks
        add_action('vortex_run_automated_tasks', array($this, 'execute_automated_tasks'));
        
        // Register AJAX handlers for user task management
        add_action('wp_ajax_vortex_get_automation_tasks', array($this, 'get_user_automation_tasks'));
        add_action('wp_ajax_vortex_create_automation_task', array($this, 'create_automation_task'));
        add_action('wp_ajax_vortex_toggle_automation_task', array($this, 'toggle_automation_task'));
        
        // Add task automation settings to user profile
        add_action('show_user_profile', array($this, 'add_automation_preferences'));
        add_action('edit_user_profile', array($this, 'add_automation_preferences'));
        add_action('personal_options_update', array($this, 'save_automation_preferences'));
        add_action('edit_user_profile_update', array($this, 'save_automation_preferences'));
        
        // Schedule automated tasks execution
        if (!wp_next_scheduled('vortex_run_automated_tasks')) {
            wp_schedule_event(time(), 'hourly', 'vortex_run_automated_tasks');
        }
    }
    
    /**
     * Execute automated tasks for all users
     */
    public function execute_automated_tasks() {
        global $wpdb;
        
        // Get all active automation tasks
        $tasks_table = $wpdb->prefix . 'vortex_automation_tasks';
        $active_tasks = $wpdb->get_results(
            "SELECT * FROM $tasks_table 
            WHERE active = 1 
            AND next_run <= NOW()",
            ARRAY_A
        );
        
        foreach ($active_tasks as $task) {
            $this->execute_single_task($task);
        }
    }
    
    /**
     * Execute a single automation task
     * 
     * @param array $task Task data
     * @return bool Success status
     */
    private function execute_single_task($task) {
        global $wpdb;
        $tasks_table = $wpdb->prefix . 'vortex_automation_tasks';
        
        try {
            // Update last run time before execution to prevent duplicate runs
            $wpdb->update(
                $tasks_table,
                array(
                    'last_run' => current_time('mysql'),
                    'next_run' => $this->calculate_next_run_time($task)
                ),
                array('id' => $task['id'])
            );
            
            // Check if user has sufficient TOLA tokens
            $wallet = Vortex_AI_Marketplace::get_instance()->wallet;
            if (!$wallet->check_llm_api_access($task['user_id'])) {
                // Log insufficient tokens
                $this->log_automation_result($task['id'], false, 'Insufficient TOLA tokens');
                return false;
            }
            
            // Execute task based on type
            switch ($task['task_type']) {
                case 'artwork_generation':
                    $result = $this->execute_artwork_task($task);
                    break;
                    
                case 'market_analysis':
                    $result = $this->execute_market_analysis_task($task);
                    break;
                    
                case 'strategy_recommendation':
                    $result = $this->execute_strategy_task($task);
                    break;
                    
                default:
                    // Unsupported task type
                    $this->log_automation_result($task['id'], false, 'Unsupported task type');
                    return false;
            }
            
            // Check result and log
            if (is_wp_error($result)) {
                $this->log_automation_result($task['id'], false, $result->get_error_message());
                return false;
            }
            
            // Log successful execution
            $this->log_automation_result($task['id'], true, json_encode($result));
            
            // Notify user of completed task
            $this->notify_user_of_completed_task($task['user_id'], $task, $result);
            
            return true;
            
        } catch (Exception $e) {
            // Log exception
            $this->log_automation_result($task['id'], false, $e->getMessage());
            return false;
        }
    }
    
    /**
     * Execute artwork generation task
     * 
     * @param array $task Task data
     * @return array|WP_Error Result data or error
     */
    private function execute_artwork_task($task) {
        try {
            // Get task parameters
            $params = json_decode($task['task_params'], true);
            if (!isset($params['prompt'])) {
                return new WP_Error('invalid_params', 'Prompt is required for artwork generation');
            }
            
            // Set default parameters if not specified
            $style = isset($params['style']) ? $params['style'] : 'realistic';
            $size = isset($params['size']) ? $params['size'] : '512x512';
            
            // Get HURAII service
            $huraii = Vortex_AI_Marketplace::get_instance()->get_artwork_service();
            
            // Generate artwork
            $result = $huraii->generate_artwork(
                $params['prompt'],
                $style,
                $size,
                $task['user_id']
            );
            
            // Deduct TOLA tokens
            $this->deduct_tokens_for_automation($task);
            
            return $result;
            
        } catch (Exception $e) {
            return new WP_Error('artwork_generation_failed', $e->getMessage());
        }
    }
    
    /**
     * Execute market analysis task
     * 
     * @param array $task Task data
     * @return array|WP_Error Result data or error
     */
    private function execute_market_analysis_task($task) {
        try {
            // Get task parameters
            $params = json_decode($task['task_params'], true);
            if (!isset($params['market'])) {
                return new WP_Error('invalid_params', 'Market is required for analysis');
            }
            
            // Set default parameters if not specified
            $timeframe = isset($params['timeframe']) ? $params['timeframe'] : '30';
            $detail_level = isset($params['detail_level']) ? $params['detail_level'] : 'medium';
            
            // Get CLOE service
            $cloe = Vortex_AI_Marketplace::get_instance()->get_cloe_service();
            
            // Generate market analysis
            $result = $cloe->analyze_market(
                $params['market'],
                $timeframe,
                $detail_level,
                $task['user_id']
            );
            
            // Deduct TOLA tokens
            $this->deduct_tokens_for_automation($task);
            
            return $result;
            
        } catch (Exception $e) {
            return new WP_Error('market_analysis_failed', $e->getMessage());
        }
    }
    
    /**
     * Execute strategy recommendation task
     * 
     * @param array $task Task data
     * @return array|WP_Error Result data or error
     */
    private function execute_strategy_task($task) {
        try {
            // Get task parameters
            $params = json_decode($task['task_params'], true);
            if (!isset($params['industry'])) {
                return new WP_Error('invalid_params', 'Industry is required for strategy recommendation');
            }
            
            // Set default parameters if not specified
            $focus = isset($params['focus']) ? $params['focus'] : 'growth';
            $timeframe = isset($params['timeframe']) ? $params['timeframe'] : 'medium';
            
            // Get Business Strategist service
            $strategist = Vortex_AI_Marketplace::get_instance()->get_strategist_service();
            
            // Generate strategy recommendation
            $result = $strategist->generate_strategy(
                $params['industry'],
                $focus,
                $timeframe,
                $task['user_id']
            );
            
            // Deduct TOLA tokens
            $this->deduct_tokens_for_automation($task);
            
            return $result;
            
        } catch (Exception $e) {
            return new WP_Error('strategy_generation_failed', $e->getMessage());
        }
    }
    
    /**
     * Calculate next run time for a task
     * 
     * @param array $task Task data
     * @return string Next run time (MySQL datetime)
     */
    private function calculate_next_run_time($task) {
        $frequency = isset($task['frequency']) ? $task['frequency'] : 'daily';
        $now = current_time('timestamp');
        
        switch ($frequency) {
            case 'hourly':
                $next_run = $now + HOUR_IN_SECONDS;
                break;
            case 'daily':
                $next_run = $now + DAY_IN_SECONDS;
                break;
            case 'weekly':
                $next_run = $now + WEEK_IN_SECONDS;
                break;
            case 'monthly':
                $next_run = $now + 30 * DAY_IN_SECONDS;
                break;
            default:
                $next_run = $now + DAY_IN_SECONDS;
        }
        
        return date('Y-m-d H:i:s', $next_run);
    }
    
    /**
     * Log automation task execution result
     * 
     * @param int $task_id Task ID
     * @param bool $success Success status
     * @param string $message Result message
     */
    private function log_automation_result($task_id, $success, $message) {
        global $wpdb;
        $logs_table = $wpdb->prefix . 'vortex_automation_logs';
        
        $wpdb->insert(
            $logs_table,
            array(
                'task_id' => $task_id,
                'success' => $success ? 1 : 0,
                'message' => $message,
                'execution_time' => current_time('mysql')
            )
        );
    }
    
    /**
     * Deduct TOLA tokens for automated task execution
     * 
     * @param array $task Task data
     */
    private function deduct_tokens_for_automation($task) {
        $wallet = Vortex_AI_Marketplace::get_instance()->wallet;
        $task_type = $task['task_type'];
        
        // Define token costs for automated tasks
        $token_costs = array(
            'artwork_generation' => 15,
            'market_analysis' => 10,
            'strategy_recommendation' => 20
        );
        
        $cost = isset($token_costs[$task_type]) ? $token_costs[$task_type] : 10;
        
        // Deduct tokens
        $wallet->deduct_tola_tokens(
            $task['user_id'],
            $cost,
            'automated_' . $task_type
        );
    }
    
    /**
     * Notify user of completed automated task
     * 
     * @param int $user_id User ID
     * @param array $task Task data
     * @param array $result Task result
     */
    private function notify_user_of_completed_task($user_id, $task, $result) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return;
        }
        
        $task_name = isset($task['task_name']) ? $task['task_name'] : 'Automated Task';
        $task_type_label = $this->get_task_type_label($task['task_type']);
        
        // Add notification to user meta
        $notifications = get_user_meta($user_id, 'vortex_ai_notifications', true);
        if (!is_array($notifications)) {
            $notifications = array();
        }
        
        // Add new notification
        $notifications[] = array(
            'id' => uniqid('notify_'),
            'title' => $task_name . ' Completed',
            'message' => 'Your automated ' . $task_type_label . ' task has completed successfully.',
            'type' => 'automation',
            'task_id' => $task['id'],
            'result_id' => isset($result['id']) ? $result['id'] : null,
            'time' => current_time('mysql'),
            'read' => false
        );
        
        // Limit to 50 notifications to prevent metadata bloat
        if (count($notifications) > 50) {
            $notifications = array_slice($notifications, -50);
        }
        
        update_user_meta($user_id, 'vortex_ai_notifications', $notifications);
        
        // Send email notification if enabled in user preferences
        $send_email = get_user_meta($user_id, 'vortex_automation_email_notifications', true);
        if ($send_email) {
            $this->send_task_completion_email($user, $task, $result);
        }
    }
    
    /**
     * Send email notification for completed task
     * 
     * @param WP_User $user User object
     * @param array $task Task data
     * @param array $result Task result
     */
    private function send_task_completion_email($user, $task, $result) {
        $task_name = isset($task['task_name']) ? $task['task_name'] : 'Automated Task';
        $task_type_label = $this->get_task_type_label($task['task_type']);
        $admin_email = get_option('admin_email');
        
        $subject = 'Vortex AI: ' . $task_name . ' Completed';
        
        $message = "Hello " . $user->display_name . ",\n\n";
        $message .= "Your automated " . $task_type_label . " task '" . $task_name . "' has completed successfully.\n\n";
        $message .= "You can view the results by logging into your account and checking your AI dashboard.\n\n";
        $message .= "Thank you for using Vortex AI Marketplace!\n";
        
        wp_mail($user->user_email, $subject, $message, array(
            'From: Vortex AI <' . $admin_email . '>'
        ));
    }
    
    /**
     * Get human-readable label for task type
     * 
     * @param string $task_type Task type
     * @return string Human-readable label
     */
    private function get_task_type_label($task_type) {
        $labels = array(
            'artwork_generation' => 'Artwork Generation',
            'market_analysis' => 'Market Analysis',
            'strategy_recommendation' => 'Strategy Recommendation'
        );
        
        return isset($labels[$task_type]) ? $labels[$task_type] : $task_type;
    }
    
    /**
     * Create database tables for task automation
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tasks table
        $tasks_table = $wpdb->prefix . 'vortex_automation_tasks';
        $tasks_sql = "CREATE TABLE IF NOT EXISTS $tasks_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            task_name varchar(100) NOT NULL,
            task_type varchar(50) NOT NULL,
            task_params longtext NOT NULL,
            frequency varchar(20) NOT NULL DEFAULT 'daily',
            active tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime NOT NULL,
            last_run datetime DEFAULT NULL,
            next_run datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY active_next_run (active, next_run)
        ) $charset_collate;";
        
        // Logs table
        $logs_table = $wpdb->prefix . 'vortex_automation_logs';
        $logs_sql = "CREATE TABLE IF NOT EXISTS $logs_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            task_id bigint(20) NOT NULL,
            success tinyint(1) NOT NULL,
            message text NOT NULL,
            execution_time datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY task_id (task_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($tasks_sql);
        dbDelta($logs_sql);
    }
} 