<?php
/**
 * VORTEX Admin Status Manager
 * Handles admin-side status monitoring and AI agent health checks
 *
 * @package VORTEX
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

class VORTEX_Admin_Status {
    private $ai_manager;
    private $status_cache;
    private $cache_expiry = 300; // 5 minutes
    private $last_check;

    public function __construct() {
        $this->init();
    }

    /**
     * Initialize status manager
     */
    private function init() {
        try {
            // Security check
            if (!current_user_can('manage_options')) {
                throw new Exception('Unauthorized access');
            }

            $this->ai_manager = VORTEX_AI_Manager::get_instance();
            $this->setup_hooks();
            $this->initialize_status_cache();

        } catch (Exception $e) {
            $this->log_error('Initialization failed', $e);
        }
    }

    /**
     * Setup action and filter hooks
     */
    private function setup_hooks() {
        add_action('admin_menu', array($this, 'add_status_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_status_assets'));
        add_action('wp_ajax_vortex_get_system_status', array($this, 'ajax_get_system_status'));
        add_action('wp_ajax_vortex_run_health_check', array($this, 'ajax_run_health_check'));
    }

    /**
     * Add status page to admin menu
     */
    public function add_status_menu() {
        add_submenu_page(
            'vortex-dashboard',
            __('System Status', 'vortex'),
            __('Status', 'vortex'),
            'manage_options',
            'vortex-status',
            array($this, 'render_status_page')
        );
    }

    /**
     * Render status page
     */
    public function render_status_page() {
        // Security check
        if (!current_user_can('manage_options')) {
            wp_die(__('Access denied', 'vortex'));
        }

        $status = $this->get_system_status();
        ?>
        <div class="wrap vortex-status-page">
            <h1><?php _e('VORTEX System Status', 'vortex'); ?></h1>

            <!-- AI Agents Status -->
            <div class="vortex-status-section">
                <h2><?php _e('AI Agents Status', 'vortex'); ?></h2>
                <div class="vortex-ai-status-grid">
                    <?php $this->render_ai_agent_status('huraii', $status['ai_agents']['huraii']); ?>
                    <?php $this->render_ai_agent_status('cloe', $status['ai_agents']['cloe']); ?>
                    <?php $this->render_ai_agent_status('business_strategist', $status['ai_agents']['business_strategist']); ?>
                </div>
            </div>

            <!-- System Health -->
            <div class="vortex-status-section">
                <h2><?php _e('System Health', 'vortex'); ?></h2>
                <div class="vortex-health-grid">
                    <?php foreach ($status['system_health'] as $key => $health) : ?>
                        <div class="vortex-health-item <?php echo esc_attr($health['status']); ?>">
                            <h3><?php echo esc_html($health['label']); ?></h3>
                            <div class="vortex-health-value"><?php echo esc_html($health['value']); ?></div>
                            <?php if (!empty($health['message'])) : ?>
                                <div class="vortex-health-message"><?php echo esc_html($health['message']); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Learning Status -->
            <div class="vortex-status-section">
                <h2><?php _e('AI Learning Status', 'vortex'); ?></h2>
                <div class="vortex-learning-grid">
                    <?php foreach ($status['learning_status'] as $agent => $learning) : ?>
                        <div class="vortex-learning-item">
                            <h3><?php echo esc_html($learning['label']); ?></h3>
                            <div class="vortex-learning-stats">
                                <div class="vortex-stat">
                                    <span class="label"><?php _e('Iterations:', 'vortex'); ?></span>
                                    <span class="value"><?php echo esc_html($learning['iterations']); ?></span>
                                </div>
                                <div class="vortex-stat">
                                    <span class="label"><?php _e('Success Rate:', 'vortex'); ?></span>
                                    <span class="value"><?php echo esc_html($learning['success_rate']); ?>%</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="vortex-status-actions">
                <button type="button" class="button button-primary" id="vortex-run-health-check">
                    <?php _e('Run Health Check', 'vortex'); ?>
                </button>
                <button type="button" class="button" id="vortex-refresh-status">
                    <?php _e('Refresh Status', 'vortex'); ?>
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Get system status
     */
    private function get_system_status() {
        try {
            if ($this->should_refresh_cache()) {
                $status = array(
                    'ai_agents' => $this->get_ai_agents_status(),
                    'system_health' => $this->get_system_health(),
                    'learning_status' => $this->get_learning_status(),
                    'timestamp' => time()
                );

                $this->update_status_cache($status);
            }

            return $this->status_cache;

        } catch (Exception $e) {
            $this->log_error('Status retrieval failed', $e);
            return $this->get_error_status();
        }
    }

    /**
     * Get AI agents status
     */
    private function get_ai_agents_status() {
        $status = array();

        try {
            foreach (['huraii', 'cloe', 'business_strategist'] as $agent) {
                $status[$agent] = $this->ai_manager->get_agent_status($agent);
            }
        } catch (Exception $e) {
            $this->log_error("AI agent status retrieval failed", $e);
        }

        return $status;
    }

    /**
     * Get system health metrics
     */
    private function get_system_health() {
        try {
            return array(
                'memory' => $this->check_memory_usage(),
                'disk' => $this->check_disk_space(),
                'database' => $this->check_database_health(),
                'api' => $this->check_api_connectivity()
            );
        } catch (Exception $e) {
            $this->log_error('Health check failed', $e);
            return array();
        }
    }

    /**
     * Get AI learning status
     */
    private function get_learning_status() {
        try {
            return $this->ai_manager->get_learning_status();
        } catch (Exception $e) {
            $this->log_error('Learning status retrieval failed', $e);
            return array();
        }
    }

    /**
     * AJAX handler for system status
     */
    public function ajax_get_system_status() {
        try {
            check_ajax_referer('vortex_status_nonce', 'nonce');
            
            if (!current_user_can('manage_options')) {
                throw new Exception('Unauthorized access');
            }

            wp_send_json_success($this->get_system_status());

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * AJAX handler for health check
     */
    public function ajax_run_health_check() {
        try {
            check_ajax_referer('vortex_status_nonce', 'nonce');
            
            if (!current_user_can('manage_options')) {
                throw new Exception('Unauthorized access');
            }

            $results = array(
                'system' => $this->run_system_diagnostics(),
                'ai_agents' => $this->run_ai_diagnostics()
            );

            wp_send_json_success($results);

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * Error logging
     */
    private function log_error($message, $error) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[VORTEX Status] %s: %s',
                $message,
                $error->getMessage()
            ));
        }
    }

    /**
     * Cache management
     */
    private function should_refresh_cache() {
        return empty($this->status_cache) || 
               (time() - $this->last_check) > $this->cache_expiry;
    }

    private function update_status_cache($status) {
        $this->status_cache = $status;
        $this->last_check = time();
    }
}

// Initialize the status manager
new VORTEX_Admin_Status(); 