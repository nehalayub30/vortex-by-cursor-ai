<?php
/**
 * VORTEX DAO Treasury Management
 *
 * @package VORTEX
 */

class VORTEX_DAO_Treasury {
    /**
     * Instance of this class.
     *
     * @var object
     */
    protected static $instance = null;
    
    /**
     * DAO configuration settings.
     *
     * @var array
     */
    private $config;
    
    /**
     * Constructor.
     */
    private function __construct() {
        $this->config = VORTEX_DAO_Core::get_instance()->get_config();
        $this->init_hooks();
    }
    
    /**
     * Get instance of this class.
     *
     * @return object Instance of this class.
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize hooks.
     */
    private function init_hooks() {
        // Ajax handlers
        add_action('wp_ajax_vortex_process_revenue', [$this, 'ajax_process_revenue']);
        add_action('wp_ajax_vortex_allocate_funds', [$this, 'ajax_allocate_funds']);
        
        // Shortcodes
        add_shortcode('vortex_treasury_stats', [$this, 'treasury_stats_shortcode']);
    }
    
    /**
     * Render admin page.
     */
    public function render_admin_page() {
        // Include treasury admin template
        include plugin_dir_path(dirname(dirname(__FILE__))) . 'admin/partials/dao-treasury.php';
    }
    
    /**
     * Treasury stats shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string Shortcode HTML.
     */
    public function treasury_stats_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts(
            [
                'show_allocations' => 'true',
                'show_revenue' => 'true',
            ],
            $atts,
            'vortex_treasury_stats'
        );
        
        // Get treasury statistics
        $total_revenue = $this->get_total_revenue();
        
        // Get allocation statistics
        $allocations = [
            'operations' => $this->get_allocation_total('operations'),
            'investor' => $this->get_allocation_total('investor'),
            'creator_grant' => $this->get_allocation_total('creator_grant'),
            'reserve' => $this->get_allocation_total('reserve'),
        ];
        
        // Get recent revenue entries
        $recent_revenue = $this->get_recent_revenue(5);
        
        ob_start();
        include plugin_dir_path(dirname(dirname(__FILE__))) . 'public/partials/dao-treasury-stats.php';
        return ob_get_clean();
    }
    
    /**
     * Process revenue and allocate funds.
     *
     * @param float $amount Total revenue amount.
     * @param string $source Revenue source (e.g., sale_fee, marketplace_commission).
     * @param string $reference_id Optional reference ID.
     * @param array $metadata Optional metadata.
     * @return bool Whether the processing was successful.
     */
    public function process_revenue($amount, $source, $reference_id = '', $metadata = []) {
        global $wpdb;
        
        // Insert revenue record
        $result = $wpdb->insert(
            $wpdb->prefix . 'vortex_dao_revenue',
            [
                'transaction_type' => $source,
                'user_id' => get_current_user_id(),
                'amount' => $amount,
                'currency' => 'USD',
                'reference_id' => $reference_id,
                'transaction_date' => current_time('mysql'),
                'created_at' => current_time('mysql'),
            ],
            ['%s', '%d', '%f', '%s', '%s', '%s', '%s']
        );
        
        if (!$result) {
            return false;
        }
        
        $revenue_id = $wpdb->insert_id;
        
        // Calculate allocations
        $operations_amount = ($amount * $this->config['revenue_operations_allocation']) / 100;
        $investor_amount = ($amount * $this->config['revenue_investor_allocation']) / 100;
        $creator_grant_amount = ($amount * $this->config['revenue_creator_grant_allocation']) / 100;
        $reserve_amount = ($amount * $this->config['revenue_reserve_allocation']) / 100;
        
        // Create allocation records
        $allocations = [
            [
                'allocation_type' => 'operations',
                'amount' => $operations_amount,
                'notes' => __('Operations allocation', 'vortex'),
            ],
            [
                'allocation_type' => 'investor',
                'amount' => $investor_amount,
                'notes' => __('Investor allocation', 'vortex'),
            ],
            [
                'allocation_type' => 'creator_grant',
                'amount' => $creator_grant_amount,
                'notes' => __('Creator grant allocation', 'vortex'),
            ],
            [
                'allocation_type' => 'reserve',
                'amount' => $reserve_amount,
                'notes' => __('Reserve fund allocation', 'vortex'),
            ],
        ];
        
        $success = true;
        
        foreach ($allocations as $allocation) {
            $allocation_success = $this->create_allocation(
                $allocation['allocation_type'],
                $allocation['amount'],
                $revenue_id,
                $allocation['notes']
            );
            
            if (!$allocation_success) {
                $success = false;
            }
        }
        
        // If this is investor allocation and auto-distribution is enabled, create dividend distribution
        if ($success && $investor_amount > 0 && $this->config['auto_distribute_dividends'] === 'yes') {
            $investment_manager = VORTEX_DAO_Investment::get_instance();
            $distribution_id = $investment_manager->distribute_dividends(
                $investor_amount,
                'dividend',
                sprintf(__('Automatic dividend distribution from revenue ID %d', 'vortex'), $revenue_id)
            );
            
            if (!$distribution_id) {
                // Log the failure but don't fail the entire process
                error_log(sprintf('Failed to auto-distribute dividends for revenue ID %d', $revenue_id));
            }
        }
        
        // If this is creator grant allocation, make it available for grant distribution
        if ($success && $creator_grant_amount > 0) {
            update_option('vortex_dao_available_grant_funds', $this->get_available_grant_funds() + $creator_grant_amount);
        }
        
        return $success;
    }
    
    /**
     * Create allocation record.
     *
     * @param string $type Allocation type.
     * @param float $amount Allocation amount.
     * @param int $revenue_id Revenue ID.
     * @param string $notes Optional notes.
     * @return bool Whether the creation was successful.
     */
    private function create_allocation($type, $amount, $revenue_id, $notes = '') {
        global $wpdb;
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'vortex_dao_allocations',
            [
                'allocation_type' => $type,
                'amount' => $amount,
                'revenue_id' => $revenue_id,
                'allocation_date' => current_time('mysql'),
                'notes' => $notes,
                'created_at' => current_time('mysql'),
            ],
            ['%s', '%f', '%d', '%s', '%s', '%s']
        );
        
        return $result !== false;
    }
    
    /**
     * Get total revenue.
     *
     * @param string $period Optional period (all, year, month, week).
     * @return float Total revenue.
     */
    public function get_total_revenue($period = 'all') {
        global $wpdb;
        
        $where = '';
        
        if ($period !== 'all') {
            $date_formats = [
                'year' => 'YEAR(transaction_date) = YEAR(CURRENT_DATE)',
                'month' => 'YEAR(transaction_date) = YEAR(CURRENT_DATE) AND MONTH(transaction_date) = MONTH(CURRENT_DATE)',
                'week' => 'YEARWEEK(transaction_date) = YEARWEEK(CURRENT_DATE)',
            ];
            
            if (isset($date_formats[$period])) {
                $where = "WHERE " . $date_formats[$period];
            }
        }
        
        $total = $wpdb->get_var(
            "SELECT SUM(amount) FROM {$wpdb->prefix}vortex_dao_revenue $where"
        );
        
        return $total ? floatval($total) : 0;
    }
    
    /**
     * Get allocation total.
     *
     * @param string $type Allocation type.
     * @param string $period Optional period (all, year, month, week).
     * @return float Total allocation.
     */
    public function get_allocation_total($type, $period = 'all') {
        global $wpdb;
        
        $where = "WHERE allocation_type = %s";
        $params = [$type];
        
        if ($period !== 'all') {
            $date_formats = [
                'year' => 'YEAR(allocation_date) = YEAR(CURRENT_DATE)',
                'month' => 'YEAR(allocation_date) = YEAR(CURRENT_DATE) AND MONTH(allocation_date) = MONTH(CURRENT_DATE)',
                'week' => 'YEARWEEK(allocation_date) = YEARWEEK(CURRENT_DATE)',
            ];
            
            if (isset($date_formats[$period])) {
                $where .= " AND " . $date_formats[$period];
            }
        }
        
        $total = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(amount) FROM {$wpdb->prefix}vortex_dao_allocations $where",
                $params
            )
        );
        
        return $total ? floatval($total) : 0;
    }
    
    /**
     * Get recent revenue entries.
     *
     * @param int $limit Number of entries to retrieve.
     * @return array Recent revenue entries.
     */
    public function get_recent_revenue($limit = 10) {
        global $wpdb;
        
        $entries = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vortex_dao_revenue ORDER BY transaction_date DESC LIMIT %d",
                $limit
            )
        );
        
        return $entries;
    }
    
    /**
     * Get available grant funds.
     *
     * @return float Available grant funds.
     */
    public function get_available_grant_funds() {
        return floatval(get_option('vortex_dao_available_grant_funds', 0));
    }
    
    /**
     * AJAX: Process revenue.
     */
    public function ajax_process_revenue() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_process_revenue')) {
            wp_send_json_error(['message' => __('Security check failed.', 'vortex')]);
        }
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'vortex')]);
        }
        
        // Get revenue details
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $source = isset($_POST['source']) ? sanitize_text_field($_POST['source']) : '';
        $reference_id = isset($_POST['reference_id']) ? sanitize_text_field($_POST['reference_id']) : '';
        
        if ($amount <= 0 || empty($source)) {
            wp_send_json_error(['message' => __('Invalid revenue details.', 'vortex')]);
        }
        
        // Process revenue
        $success = $this->process_revenue($amount, $source, $reference_id);
        
        if (!$success) {
            wp_send_json_error(['message' => __('Failed to process revenue.', 'vortex')]);
        }
        
        wp_send_json_success([
            'message' => sprintf(__('Successfully processed %s in revenue.', 'vortex'), number_format($amount, 2)),
            'allocations' => [
                'operations' => ($amount * $this->config['revenue_operations_allocation']) / 100,
                'investor' => ($amount * $this->config['revenue_investor_allocation']) / 100,
                'creator_grant' => ($amount * $this->config['revenue_creator_grant_allocation']) / 100,
                'reserve' => ($amount * $this->config['revenue_reserve_allocation']) / 100,
            ],
        ]);
    }
    
    /**
     * AJAX: Allocate funds.
     */
    public function ajax_allocate_funds() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_allocate_funds')) {
            wp_send_json_error(['message' => __('Security check failed.', 'vortex')]);
        }
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'vortex')]);
        }
        
        // Get allocation details
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
        
        if ($amount <= 0 || empty($type)) {
            wp_send_json_error(['message' => __('Invalid allocation details.', 'vortex')]);
        }
        
        // Create manual allocation
        $success = $this->create_allocation($type, $amount, 0, $notes);
        
        if (!$success) {
            wp_send_json_error(['message' => __('Failed to create allocation.', 'vortex')]);
        }
        
        // Special handling for different allocation types
        if ($type === 'creator_grant') {
            update_option('vortex_dao_available_grant_funds', $this->get_available_grant_funds() + $amount);
        } elseif ($type === 'investor') {
            // Create dividend distribution
            $investment_manager = VORTEX_DAO_Investment::get_instance();
            $investment_manager->distribute_dividends($amount, 'dividend', $notes);
        }
        
        wp_send_json_success([
            'message' => sprintf(__('Successfully allocated %s to %s.', 'vortex'), number_format($amount, 2), $type),
        ]);
    }
} 