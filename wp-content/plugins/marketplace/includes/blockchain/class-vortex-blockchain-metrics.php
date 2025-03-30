<?php
/**
 * VORTEX Blockchain Metrics
 * 
 * Tracks and reports real-time metrics for TOLA blockchain integration
 *
 * @package   VORTEX_Marketplace
 * @author    VORTEX Development Team
 * @license   GPL-2.0+
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class VORTEX_Blockchain_Metrics {
    /**
     * Instance of this class.
     */
    protected static $instance = null;
    
    /**
     * Cache expiration times
     */
    private $cache_times = array(
        'real_time' => 60,      // 1 minute
        'hourly' => 3600,       // 1 hour
        'daily' => 86400,       // 1 day
        'weekly' => 604800      // 1 week
    );
    
    /**
     * Constructor
     */
    private function __construct() {
        // Register REST API endpoints for metrics
        add_action('rest_api_init', array($this, 'register_rest_routes'));
        
        // Register admin widget for blockchain metrics
        add_action('wp_dashboard_setup', array($this, 'add_blockchain_dashboard_widget'));
        
        // Schedule metrics caching
        add_action('init', array($this, 'schedule_metrics_caching'));
        
        // Register AJAX handlers
        add_action('wp_ajax_vortex_get_blockchain_metrics', array($this, 'ajax_get_blockchain_metrics'));
        add_action('wp_ajax_nopriv_vortex_get_blockchain_metrics', array($this, 'ajax_get_blockchain_metrics'));
    }
    
    /**
     * Return an instance of this class.
     */
    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        
        return self::$instance;
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes() {
        register_rest_route('vortex/v1', '/blockchain/metrics', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_public_blockchain_metrics'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route('vortex/v1', '/blockchain/artists', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_top_blockchain_artists'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route('vortex/v1', '/blockchain/categories', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_top_artwork_categories'),
            'permission_callback' => '__return_true'
        ));
    }
    
    /**
     * Schedule metrics caching
     */
    public function schedule_metrics_caching() {
        if (!wp_next_scheduled('vortex_cache_blockchain_metrics')) {
            wp_schedule_event(time(), 'hourly', 'vortex_cache_blockchain_metrics');
        }
        
        add_action('vortex_cache_blockchain_metrics', array($this, 'cache_blockchain_metrics'));
    }
    
    /**
     * Cache blockchain metrics
     */
    public function cache_blockchain_metrics() {
        // Generate and cache daily metrics
        $daily_metrics = $this->generate_blockchain_metrics(30);
        set_transient('vortex_blockchain_metrics_daily', $daily_metrics, $this->cache_times['daily']);
        
        // Generate and cache weekly metrics
        $weekly_metrics = $this->generate_blockchain_metrics(90);
        set_transient('vortex_blockchain_metrics_weekly', $weekly_metrics, $this->cache_times['weekly']);
        
        // Generate and cache artist metrics
        $artists_metrics = $this->generate_artist_metrics(30);
        set_transient('vortex_blockchain_artists_metrics', $artists_metrics, $this->cache_times['daily']);
        
        // Generate and cache category metrics
        $categories_metrics = $this->generate_category_metrics(30);
        set_transient('vortex_blockchain_categories_metrics', $categories_metrics, $this->cache_times['daily']);
        
        // Log caching event
        $this->log_metrics_update('Cached blockchain metrics');
    }
    
    /**
     * Get public blockchain metrics
     */
    public function get_public_blockchain_metrics($request) {
        $days = isset($request['days']) ? intval($request['days']) : 30;
        $type = isset($request['type']) ? sanitize_text_field($request['type']) : 'all';
        
        return $this->get_public_blockchain_stats($days, $type);
    }
    
    /**
     * Get public blockchain stats
     */
    public function get_public_blockchain_stats($days = 30, $type = 'all') {
        // Check cache first
        $cache_key = 'vortex_blockchain_metrics_' . $days . '_' . $type;
        $cached = get_transient($cache_key);
        
        if (false !== $cached) {
            return $cached;
        }
        
        // Generate metrics based on type
        $metrics = array();
        
        switch ($type) {
            case 'artists':
                $metrics = $this->generate_artist_metrics($days);
                break;
                
            case 'categories':
                $metrics = $this->generate_category_metrics($days);
                break;
                
            case 'transactions':
                $metrics = $this->generate_transaction_metrics($days);
                break;
                
            case 'all':
            default:
                $metrics = $this->generate_blockchain_metrics($days);
                
                // Add additional data for 'all' type
                $metrics['top_artists'] = $this->get_top_blockchain_artists_data($days, 5);
                $metrics['top_categories'] = $this->get_top_artwork_categories_data($days, 5);
                $metrics['recent_transactions'] = $this->get_recent_transactions(5);
                break;
        }
        
        // Cache for 1 hour
        set_transient($cache_key, $metrics, $this->cache_times['hourly']);
        
        return $metrics;
    }
    
    /**
     * Generate blockchain metrics
     */
    private function generate_blockchain_metrics($days = 30) {
        global $wpdb;
        
        // Total tokenized artworks
        $total_artworks = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_nft_valuations"
        );
        
        // New tokenized artworks in period
        $new_artworks = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_nft_valuations
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
        
        // Total blockchain transactions
        $total_transactions = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_blockchain_transactions"
        );
        
        // Transactions in period
        $period_transactions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_blockchain_transactions
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
        
        // Total TOLA volume
        $total_volume = $wpdb->get_var(
            "SELECT SUM(tola_amount) FROM {$wpdb->prefix}vortex_blockchain_transactions"
        );
        
        // TOLA volume in period
        $period_volume = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(tola_amount) FROM {$wpdb->prefix}vortex_blockchain_transactions
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
        
        // Total artists with NFTs
        $total_artists = $wpdb->get_var(
            "SELECT COUNT(DISTINCT user_id) 
             FROM {$wpdb->prefix}vortex_artworks a
             JOIN {$wpdb->prefix}vortex_nft_valuations v ON a.id = v.artwork_id"
        );
        
        // Daily transaction volume over period
        $daily_volume = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(created_at) as date, SUM(tola_amount) as volume
             FROM {$wpdb->prefix}vortex_blockchain_transactions
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY DATE(created_at)
             ORDER BY date ASC",
            $days
        ));
        
        // Format daily volume for chart
        $volume_dates = array();
        $volume_values = array();
        
        foreach ($daily_volume as $day) {
            $volume_dates[] = $day->date;
            $volume_values[] = floatval($day->volume);
        }
        
        return array(
            'total_artworks' => intval($total_artworks),
            'new_artworks' => intval($new_artworks),
            'total_transactions' => intval($total_transactions),
            'period_transactions' => intval($period_transactions),
            'total_volume' => floatval($total_volume),
            'period_volume' => floatval($period_volume),
            'total_artists' => intval($total_artists),
            'chart_data' => array(
                'dates' => $volume_dates,
                'volumes' => $volume_values
            ),
            'last_updated' => current_time('mysql')
        );
    }
    
    /**
     * Generate artist metrics
     */
    private function generate_artist_metrics($days = 30) {
        global $wpdb;
        
        // Top artists by NFT count
        $top_by_nft_count = $wpdb->get_results($wpdb->prepare(
            "SELECT u.ID as user_id, u.display_name as artist_name, COUNT(*) as nft_count
             FROM {$wpdb->prefix}vortex_nft_valuations v
             JOIN {$wpdb->prefix}vortex_artworks a ON v.artwork_id = a.id
             JOIN {$wpdb->users} u ON a.user_id = u.ID
             WHERE v.created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY u.ID
             ORDER BY nft_count DESC
             LIMIT 10",
            $days
        ));
        
        // Top artists by transaction volume
        $top_by_volume = $wpdb->get_results($wpdb->prepare(
            "SELECT u.ID as user_id, u.display_name as artist_name, SUM(t.tola_amount) as volume
             FROM {$wpdb->prefix}vortex_blockchain_transactions t
             JOIN {$wpdb->prefix}vortex_nft_valuations v ON t.nft_id = v.nft_id
             JOIN {$wpdb->prefix}vortex_artworks a ON v.artwork_id = a.id
             JOIN {$wpdb->users} u ON a.user_id = u.ID
             WHERE t.created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY u.ID
             ORDER BY volume DESC
             LIMIT 10",
            $days
        ));
        
        // Most swapped artists
        $most_swapped = $wpdb->get_results($wpdb->prepare(
            "SELECT u.ID as user_id, u.display_name as artist_name, COUNT(*) as swap_count
             FROM {$wpdb->prefix}vortex_blockchain_transactions t
             JOIN {$wpdb->prefix}vortex_nft_valuations v ON t.nft_id = v.nft_id
             JOIN {$wpdb->prefix}vortex_artworks a ON v.artwork_id = a.id
             JOIN {$wpdb->users} u ON a.user_id = u.ID
             WHERE t.transaction_type = 'swap'
             AND t.created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY u.ID
             ORDER BY swap_count DESC
             LIMIT 10",
            $days
        ));
        
        return array(
            'top_by_nft_count' => $top_by_nft_count,
            'top_by_volume' => $top_by_volume,
            'most_swapped' => $most_swapped,
            'last_updated' => current_time('mysql')
        );
    }
    
    /**
     * Generate category metrics
     */
    private function generate_category_metrics($days = 30) {
        global $wpdb;
        
        // Top categories by NFT count
        $top_by_nft_count = $wpdb->get_results($wpdb->prepare(
            "SELECT a.category, COUNT(*) as nft_count
             FROM {$wpdb->prefix}vortex_nft_valuations v
             JOIN {$wpdb->prefix}vortex_artworks a ON v.artwork_id = a.id
             WHERE v.created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY a.category
             ORDER BY nft_count DESC
             LIMIT 10",
            $days
        ));
        
        // Top categories by transaction volume
        $top_by_volume = $wpdb->get_results($wpdb->prepare(
            "SELECT a.category, SUM(t.tola_amount) as volume
             FROM {$wpdb->prefix}vortex_blockchain_transactions t
             JOIN {$wpdb->prefix}vortex_nft_valuations v ON t.nft_id = v.nft_id
             JOIN {$wpdb->prefix}vortex_artworks a ON v.artwork_id = a.id
             WHERE t.created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY a.category
             ORDER BY volume DESC
             LIMIT 10",
            $days
        ));
        
        // Most swapped categories
        $most_swapped = $wpdb->get_results($wpdb->prepare(
            "SELECT a.category, COUNT(*) as swap_count
             FROM {$wpdb->prefix}vortex_blockchain_transactions t
             JOIN {$wpdb->prefix}vortex_nft_valuations v ON t.nft_id = v.nft_id
             JOIN {$wpdb->prefix}vortex_artworks a ON v.artwork_id = a.id
             WHERE t.transaction_type = 'swap'
             AND t.created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY a.category
             ORDER BY swap_count DESC
             LIMIT 10",
            $days
        ));
        
        // Average price by category
        $avg_price = $wpdb->get_results($wpdb->prepare(
            "SELECT a.category, AVG(v.current_value) as avg_price
             FROM {$wpdb->prefix}vortex_nft_valuations v
             JOIN {$wpdb->prefix}vortex_artworks a ON v.artwork_id = a.id
             WHERE v.last_updated >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY a.category
             ORDER BY avg_price DESC",
            $days
        ));
        
        return array(
            'top_by_nft_count' => $top_by_nft_count,
            'top_by_volume' => $top_by_volume,
            'most_swapped' => $most_swapped,
            'avg_price' => $avg_price,
            'last_updated' => current_time('mysql')
        );
    }
    
    /**
     * Generate transaction metrics
     */
    private function generate_transaction_metrics($days = 30) {
        global $wpdb;
        
        // Transaction count by type
        $by_type = $wpdb->get_results($wpdb->prepare(
            "SELECT transaction_type, COUNT(*) as count
             FROM {$wpdb->prefix}vortex_blockchain_transactions
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY transaction_type
             ORDER BY count DESC",
            $days
        ));
        
        // Daily transaction count
        $daily_count = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(created_at) as date, COUNT(*) as count
             FROM {$wpdb->prefix}vortex_blockchain_transactions
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY DATE(created_at)
             ORDER BY date ASC",
            $days
        ));
        
        // Recent transactions
        $recent = $wpdb->get_results(
            "SELECT t.*, v.artwork_id, a.title as artwork_title,
                    sender.display_name as sender_name,
                    receiver.display_name as receiver_name
             FROM {$wpdb->prefix}vortex_blockchain_transactions t
             JOIN {$wpdb->prefix}vortex_nft_valuations v ON t.nft_id = v.nft_id
             JOIN {$wpdb->prefix}vortex_artworks a ON v.artwork_id = a.id
             LEFT JOIN {$wpdb->users} sender ON t.sender_id = sender.ID
             LEFT JOIN {$wpdb->users} receiver ON t.receiver_id = receiver.ID
             ORDER BY t.created_at DESC
             LIMIT 10"
        );
        
        // Format for chart
        $dates = array();
        $counts = array();
        
        foreach ($daily_count as $day) {
            $dates[] = $day->date;
            $counts[] = intval($day->count);
        }
        
        return array(
            'by_type' => $by_type,
            'recent' => $recent,
            'chart_data' => array(
                'dates' => $dates,
                'counts' => $counts
            ),
            'last_updated' => current_time('mysql')
        );
    }
    
    /**
     * Get top blockchain artists data
     */
    private function get_top_blockchain_artists_data($days = 30, $limit = 5) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT u.ID as user_id, u.display_name as artist_name, 
                    COUNT(DISTINCT v.nft_id) as nft_count,
                    SUM(t.tola_amount) as volume
             FROM {$wpdb->prefix}vortex_blockchain_transactions t
             JOIN {$wpdb->prefix}vortex_nft_valuations v ON t.nft_id = v.nft_id
             JOIN {$wpdb->prefix}vortex_artworks a ON v.artwork_id = a.id
             JOIN {$wpdb->users} u ON a.user_id = u.ID
             WHERE t.created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY u.ID
             ORDER BY volume DESC
             LIMIT %d",
            $days, $limit
        ));
    }
    
    /**
     * Get top artwork categories data
     */
    private function get_top_artwork_categories_data($days = 30, $limit = 5) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT a.category, COUNT(DISTINCT v.nft_id) as nft_count,
                    SUM(t.tola_amount) as volume
             FROM {$wpdb->prefix}vortex_blockchain_transactions t
             JOIN {$wpdb->prefix}vortex_nft_valuations v ON t.nft_id = v.nft_id
             JOIN {$wpdb->prefix}vortex_artworks a ON v.artwork_id = a.id
             WHERE t.created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY a.category
             ORDER BY volume DESC
             LIMIT %d",
            $days, $limit
        ));
    }
    
    /**
     * Get recent transactions
     */
    private function get_recent_transactions($limit = 5) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT t.transaction_id, t.transaction_type, t.tola_amount,
                    t.created_at, v.artwork_id, a.title as artwork_title,
                    sender.display_name as sender_name,
                    receiver.display_name as receiver_name
             FROM {$wpdb->prefix}vortex_blockchain_transactions t
             JOIN {$wpdb->prefix}vortex_nft_valuations v ON t.nft_id = v.nft_id
             JOIN {$wpdb->prefix}vortex_artworks a ON v.artwork_id = a.id
             LEFT JOIN {$wpdb->users} sender ON t.sender_id = sender.ID
             LEFT JOIN {$wpdb->users} receiver ON t.receiver_id = receiver.ID
             ORDER BY t.created_at DESC
             LIMIT %d",
            $limit
        ));
    }
    
    /**
     * Add blockchain dashboard widget
     */
    public function add_blockchain_dashboard_widget() {
        if (current_user_can('edit_posts')) {
            wp_add_dashboard_widget(
                'vortex_blockchain_metrics_widget',
                'TOLA Blockchain Metrics',
                array($this, 'render_blockchain_widget')
            );
        }
    }
    
    /**
     * Render blockchain dashboard widget
     */
    public function render_blockchain_widget() {
        $metrics = $this->get_public_blockchain_stats(7, 'all');
        ?>
        <div class="vortex-blockchain-widget">
            <div class="vortex-metrics-summary">
                <div class="vortex-metric-box">
                    <span class="metric-value"><?php echo number_format($metrics['total_artworks']); ?></span>
                    <span class="metric-label">Total NFTs</span>
                </div>
                
                <div class="vortex-metric-box">
                    <span class="metric-value"><?php echo number_format($metrics['new_artworks']); ?></span>
                    <span class="metric-label">New NFTs (7d)</span>
                </div>
                
                <div class="vortex-metric-box">
                    <span class="metric-value"><?php echo number_format($metrics['period_transactions']); ?></span>
                    <span class="metric-label">Transactions (7d)</span>
                </div>
                
                <div class="vortex-metric-box">
                    <span class="metric-value"><?php echo number_format($metrics['period_volume'], 2); ?> TOLA</span>
                    <span class="metric-label">Volume (7d)</span>
                </div>
            </div>
            
            <?php if (!empty($metrics['top_artists'])): ?>
            <div class="vortex-blockchain-section">
                <h3>Top Artists (7d)</h3>
                <ul class="vortex-top-list">
                    <?php foreach ($metrics['top_artists'] as $artist): ?>
                    <li>
                        <span class="item-name"><?php echo esc_html($artist->artist_name); ?></span>
                        <span class="item-value"><?php echo number_format($artist->volume, 2); ?> TOLA</span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($metrics['top_categories'])): ?>
            <div class="vortex-blockchain-section">
                <h3>Top Categories (7d)</h3>
                <ul class="vortex-top-list">
                    <?php foreach ($metrics['top_categories'] as $category): ?>
                    <li>
                        <span class="item-name"><?php echo esc_html($category->category); ?></span>
                        <span class="item-value"><?php echo number_format($category->volume, 2); ?> TOLA</span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <div class="vortex-blockchain-footer">
                <a href="<?php echo admin_url('admin.php?page=vortex-blockchain-metrics'); ?>" class="button">
                    View Full Metrics
                </a>
                <span class="vortex-updated-time">
                    Updated: <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($metrics['last_updated'])); ?>
                </span>
            </div>
        </div>
        
        <style>
            .vortex-blockchain-widget {
                margin: -12px;
            }
            .vortex-metrics-summary {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
                margin-bottom: 15px;
            }
            .vortex-metric-box {
                background: #f8f9fa;
                border-radius: 4px;
                padding: 10px;
                text-align: center;
            }
            .metric-value {
                display: block;
                font-size: 18px;
                font-weight: 600;
                color: #2271b1;
            }
            .metric-label {
                font-size: 12px;
                color: #50575e;
            }
            .vortex-blockchain-section {
                margin-bottom: 15px;
            }
            .vortex-blockchain-section h3 {
                margin: 0 0 8px 0;
                font-size: 14px;
            }
            .vortex-top-list {
                margin: 0;
                padding: 0;
                list-style: none;
            }
            .vortex-top-list li {
                display: flex;
                justify-content: space-between;
                padding: 4px 0;
                border-bottom: 1px solid #f0f0f1;
                font-size: 13px;
            }
            .vortex-blockchain-footer {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-top: 15px;
                padding-top: 10px;
                border-top: 1px solid #dcdcde;
            }
            .vortex-updated-time {
                font-size: 11px;
                color: #666;
            }
        </style>
        <?php
    }
    
    /**
     * AJAX handler for getting blockchain metrics
     */
    public function ajax_get_blockchain_metrics() {
        $days = isset($_GET['days']) ? intval($_GET['days']) : 30;
        $type = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : 'all';
        
        $metrics = $this->get_public_blockchain_stats($days, $type);
        
        wp_send_json_success($metrics);
    }
    
    /**
     * Get top blockchain artists
     */
    public function get_top_blockchain_artists($request) {
        $days = isset($request['days']) ? intval($request['days']) : 30;
        $limit = isset($request['limit']) ? intval($request['limit']) : 10;
        
        return $this->get_top_blockchain_artists_data($days, $limit);
    }
    
    /**
     * Get top artwork categories
     */
    public function get_top_artwork_categories($request) {
        $days = isset($request['days']) ? intval($request['days']) : 30;
        $limit = isset($request['limit']) ? intval($request['limit']) : 10;
        
        return $this->get_top_artwork_categories_data($days, $limit);
    }
    
    /**
     * Log metrics update
     */
    private function log_metrics_update($message) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'vortex_system_logs',
            array(
                'log_type' => 'blockchain_metrics',
                'message' => $message,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s')
        );
    }
} 