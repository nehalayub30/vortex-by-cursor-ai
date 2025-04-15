<?php
/**
 * VORTEX AJAX Handlers
 *
 * @link       https://vortexmarketplace.io
 * @since      1.0.0
 *
 * @package    VORTEX
 * @subpackage VORTEX/includes
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * The AJAX handling class for the plugin.
 */
class VORTEX_Ajax_Handlers {

    /**
     * Initialize the class and set its properties.
     */
    public function __construct() {
        // Register AJAX actions
        add_action('wp_ajax_vortex_get_blockchain_metrics', array($this, 'get_blockchain_metrics'));
        add_action('wp_ajax_nopriv_vortex_get_blockchain_metrics', array($this, 'get_blockchain_metrics'));
        
        add_action('wp_ajax_vortex_export_blockchain_metrics', array($this, 'export_blockchain_metrics'));
        
        add_action('wp_ajax_vortex_get_agent_insights', array($this, 'get_agent_insights'));
        add_action('wp_ajax_nopriv_vortex_get_agent_insights', array($this, 'get_agent_insights'));
        
        add_action('wp_ajax_vortex_submit_insight_feedback', array($this, 'submit_insight_feedback'));
        
        add_action('wp_ajax_vortex_trigger_agent_learning', array($this, 'trigger_agent_learning'));
        
        add_action('wp_ajax_vortex_get_agent_status', array($this, 'get_agent_status'));
        add_action('wp_ajax_nopriv_vortex_get_agent_status', array($this, 'get_agent_status'));

        add_action('wp_ajax_vortex_get_learning_metrics_history', array($this, 'get_learning_metrics_history'));
        add_action('wp_ajax_vortex_export_learning_metrics', array($this, 'export_learning_metrics'));
    }

    /**
     * Get blockchain metrics
     */
    public function get_blockchain_metrics() {
        // Verify nonce
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'vortex_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            exit;
        }
        
        // Get parameters
        $metrics_type = isset($_POST['metrics_type']) ? sanitize_text_field($_POST['metrics_type']) : 'all';
        $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
        $force_refresh = isset($_POST['force_refresh']) && $_POST['force_refresh'] == 1;
        
        // Get blockchain metrics instance
        $blockchain_metrics = new VORTEX_Blockchain_Metrics();
        
        // Get metrics data
        $metrics = $blockchain_metrics->get_metrics($metrics_type, $days, $force_refresh);
        
        if (isset($_POST['chart_data']) && $_POST['chart_data'] == 'true') {
            // Return just the data for updating charts
            wp_send_json_success(array(
                'metrics' => $metrics,
                'last_updated' => date('Y-m-d H:i:s')
            ));
        } else {
            // Generate HTML for response
            ob_start();
            
            // Include the metrics template with data
            include VORTEX_PLUGIN_DIR . 'public/partials/vortex-blockchain-metrics.php';
            
            $html = ob_get_clean();
            
            wp_send_json_success(array(
                'html' => $html,
                'metrics' => $metrics,
                'last_updated' => date('Y-m-d H:i:s')
            ));
        }
        
        exit;
    }
    
    /**
     * Export blockchain metrics
     */
    public function export_blockchain_metrics() {
        // Verify nonce
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'vortex_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            exit;
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
            exit;
        }
        
        // Get parameters
        $metrics_type = isset($_POST['metrics_type']) ? sanitize_text_field($_POST['metrics_type']) : 'all';
        $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
        $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'csv';
        
        // Get blockchain metrics instance
        $blockchain_metrics = new VORTEX_Blockchain_Metrics();
        
        // Get metrics data
        $metrics = $blockchain_metrics->get_metrics($metrics_type, $days, true);
        
        // Convert to CSV
        $csv = $blockchain_metrics->export_metrics_to_csv($metrics);
        
        wp_send_json_success(array(
            'csv' => $csv,
            'filename' => "vortex_{$metrics_type}_metrics_{$days}days.csv"
        ));
        
        exit;
    }
    
    /**
     * Get agent insights
     */
    public function get_agent_insights() {
        // Verify nonce
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'vortex_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            exit;
        }
        
        // Get parameters
        $agent = isset($_POST['agent']) ? sanitize_text_field($_POST['agent']) : 'all';
        $insight_type = isset($_POST['insight_type']) ? sanitize_text_field($_POST['insight_type']) : 'latest';
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 5;
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
        
        // Get orchestrator instance
        $vortex_orchestrator = VORTEX_Orchestrator::get_instance();
        
        // Get insights
        $insights = $vortex_orchestrator->get_agent_insights_for_display(
            $agent,
            $insight_type,
            $limit,
            $offset
        );
        
        wp_send_json_success(array(
            'insights' => $insights,
            'count' => count($insights),
            'agent' => $agent,
            'insight_type' => $insight_type
        ));
        
        exit;
    }
    
    /**
     * Submit insight feedback
     */
    public function submit_insight_feedback() {
        // Verify nonce
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'vortex_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            exit;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'You must be logged in to provide feedback'));
            exit;
        }
        
        // Get parameters
        $insight_id = isset($_POST['insight_id']) ? intval($_POST['insight_id']) : 0;
        $feedback = isset($_POST['feedback']) ? sanitize_text_field($_POST['feedback']) : '';
        
        if (!$insight_id || !in_array($feedback, array('helpful', 'not-helpful'))) {
            wp_send_json_error(array('message' => 'Invalid parameters'));
            exit;
        }
        
        // Get orchestrator instance
        $vortex_orchestrator = VORTEX_Orchestrator::get_instance();
        
        // Record feedback
        $success = $vortex_orchestrator->record_insight_feedback($insight_id, $feedback, get_current_user_id());
        
        if ($success) {
            wp_send_json_success(array('message' => 'Feedback recorded successfully'));
        } else {
            wp_send_json_error(array('message' => 'Error recording feedback'));
        }
        
        exit;
    }
    
    /**
     * Trigger agent learning
     */
    public function trigger_agent_learning() {
        // Verify nonce
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'vortex_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            exit;
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
            exit;
        }
        
        // Get parameters
        $agent = isset($_POST['agent']) ? sanitize_text_field($_POST['agent']) : 'all';
        
        // Get orchestrator instance
        $vortex_orchestrator = VORTEX_Orchestrator::get_instance();
        
        // Trigger learning
        if ($agent === 'all') {
            $result = $vortex_orchestrator->trigger_all_agents_learning();
        } else {
            $result = $vortex_orchestrator->trigger_single_agent_learning($agent);
        }
        
        if ($result['success']) {
            wp_send_json_success(array('message' => $result['message']));
        } else {
            wp_send_json_error(array('message' => $result['message']));
        }
        
        exit;
    }
    
    /**
     * Get agent status
     */
    public function get_agent_status() {
        // Verify nonce
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'vortex_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            exit;
        }
        
        // Get orchestrator instance
        $vortex_orchestrator = VORTEX_Orchestrator::get_instance();
        
        // Get status
        $status = $vortex_orchestrator->get_agent_status();
        
        wp_send_json_success(array(
            'status' => $status,
            'last_updated' => date('Y-m-d H:i:s')
        ));
        
        exit;
    }

    /**
     * Get learning metrics history
     */
    public function get_learning_metrics_history() {
        // Verify nonce
        if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'vortex_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            exit;
        }
        
        global $wpdb;
        
        // Get the last 20 records or less
        $results = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}vortex_learning_metrics_history 
            ORDER BY learning_time DESC 
            LIMIT 20"
        );
        
        // Reverse to get chronological order
        $results = array_reverse($results);
        
        $metrics = array(
            'dates' => array(),
            'huraii_health' => array(),
            'cloe_health' => array(),
            'business_strategist_health' => array(),
            'thorius_health' => array(),
            'total_insights' => array(),
            'artworks_analyzed' => array(),
        );
        
        foreach ($results as $row) {
            // Format date for display
            $metrics['dates'][] = date('M j', strtotime($row->learning_time));
            
            // Parse agent health data
            $agent_health = maybe_unserialize($row->agent_health);
            
            $metrics['huraii_health'][] = isset($agent_health['huraii']['health_score']) ? $agent_health['huraii']['health_score'] : 0;
            $metrics['cloe_health'][] = isset($agent_health['cloe']['health_score']) ? $agent_health['cloe']['health_score'] : 0;
            $metrics['business_strategist_health'][] = isset($agent_health['business_strategist']['health_score']) ? $agent_health['business_strategist']['health_score'] : 0;
            $metrics['thorius_health'][] = isset($agent_health['thorius']['health_score']) ? $agent_health['thorius']['health_score'] : 0;
            
            $metrics['total_insights'][] = $row->insights_generated;
            $metrics['artworks_analyzed'][] = $row->artworks_analyzed;
        }
        
        wp_send_json_success(array(
            'metrics' => $metrics,
            'count' => count($results)
        ));
        
        exit;
    }

    /**
     * Export learning metrics
     */
    public function export_learning_metrics() {
        // Verify nonce
        if (!isset($_GET['security']) || !wp_verify_nonce($_GET['security'], 'vortex_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die('Permission denied');
        }
        
        global $wpdb;
        
        // Get all metrics records
        $results = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}vortex_learning_metrics_history 
            ORDER BY learning_time DESC"
        );
        
        // Prepare CSV data
        $csv_data = array();
        
        // Add CSV header
        $csv_data[] = array(
            'Learning Time',
            'Duration (seconds)',
            'Artworks Analyzed',
            'Users Analyzed',
            'Insights Generated',
            'HURAII Health',
            'CLOE Health',
            'Business Strategist Health',
            'Thorius Health',
            'Learning Type'
        );
        
        foreach ($results as $row) {
            // Parse agent health data
            $agent_health = maybe_unserialize($row->agent_health);
            
            $csv_data[] = array(
                $row->learning_time,
                $row->duration_seconds,
                $row->artworks_analyzed,
                $row->users_analyzed,
                $row->insights_generated,
                isset($agent_health['huraii']['health_score']) ? $agent_health['huraii']['health_score'] : 0,
                isset($agent_health['cloe']['health_score']) ? $agent_health['cloe']['health_score'] : 0,
                isset($agent_health['business_strategist']['health_score']) ? $agent_health['business_strategist']['health_score'] : 0,
                isset($agent_health['thorius']['health_score']) ? $agent_health['thorius']['health_score'] : 0,
                $row->learning_type
            );
        }
        
        // Generate CSV file
        $filename = 'vortex_learning_metrics_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        foreach ($csv_data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }

    /**
     * Enhanced error handling for AJAX requests
     */
    public function handle_ajax_error($callback, $data) {
        try {
            return call_user_func($callback, $data);
        } catch (Exception $e) {
            $this->log_error($e->getMessage(), $data);
            wp_send_json_error(array(
                'message' => 'An error occurred while processing your request.',
                'code' => $e->getCode()
            ));
            exit;
        }
    }

    /**
     * Log errors for debugging
     */
    private function log_error($message, $data) {
        if (WP_DEBUG) {
            error_log('[VORTEX Plugin Error] ' . $message);
            error_log('Data: ' . wp_json_encode($data));
        }
        
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'vortex_error_logs',
            array(
                'error_message' => $message,
                'error_data' => wp_json_encode($data),
                'user_id' => get_current_user_id(),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%d', '%s', '%s')
        );
    }
}

// Initialize AJAX handlers
$vortex_ajax_handlers = new VORTEX_Ajax_Handlers(); 