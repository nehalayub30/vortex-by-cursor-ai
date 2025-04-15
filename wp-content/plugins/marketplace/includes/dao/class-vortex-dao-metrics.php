<?php
/**
 * VORTEX DAO Metrics
 *
 * Handles DAO metrics, analytics, and reporting
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class VORTEX_DAO_Metrics {
    
    private static $instance = null;
    private $token;
    private $solana_api;
    private $cache_expiration = 300; // Cache for 5 minutes
    
    /**
     * Get class instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->token = VORTEX_DAO_Token::get_instance();
        $this->solana_api = VORTEX_Solana_API::get_instance();
        
        // Register AJAX handlers
        add_action('wp_ajax_vortex_get_dao_metrics', array($this, 'ajax_get_dao_metrics'));
        add_action('wp_ajax_nopriv_vortex_get_dao_metrics', array($this, 'ajax_get_dao_metrics'));
        
        // Register shortcodes
        add_shortcode('vortex_dao_metrics', array($this, 'metrics_shortcode'));
        
        // Schedule regular updates of cached metrics
        if (!wp_next_scheduled('vortex_update_dao_metrics')) {
            wp_schedule_event(time(), 'hourly', 'vortex_update_dao_metrics');
        }
        add_action('vortex_update_dao_metrics', array($this, 'update_cached_metrics'));
        
        // Schedule daily metrics snapshot
        if (!wp_next_scheduled('vortex_daily_metrics_snapshot')) {
            wp_schedule_event(strtotime('tomorrow midnight'), 'daily', 'vortex_daily_metrics_snapshot');
        }
        add_action('vortex_daily_metrics_snapshot', array($this, 'record_daily_metrics'));
    }
    
    /**
     * Get DAO metrics
     */
    public function get_metrics() {
        // Try to get cached metrics first
        $metrics = get_transient('vortex_dao_metrics');
        
        if (false === $metrics) {
            $metrics = $this->update_cached_metrics();
        }
        
        return $metrics;
    }
    
    /**
     * Update cached metrics
     */
    public function update_cached_metrics() {
        global $wpdb;
        
        // Get token metrics
        $token_metrics = $this->get_token_metrics();
        
        // Get governance metrics
        $governance_metrics = $this->get_governance_metrics();
        
        // Get holder metrics
        $holder_metrics = $this->get_holder_metrics();
        
        // Get treasury metrics
        $treasury_metrics = $this->get_treasury_metrics();
        
        // Get marketplace metrics
        $marketplace_metrics = $this->get_marketplace_metrics();
        
        // Combine all metrics
        $metrics = array(
            'token' => $token_metrics,
            'governance' => $governance_metrics,
            'holders' => $holder_metrics,
            'treasury' => $treasury_metrics,
            'marketplace' => $marketplace_metrics,
            'last_updated' => current_time('mysql')
        );
        
        // Cache the metrics
        set_transient('vortex_dao_metrics', $metrics, $this->cache_expiration);
        
        // Save historical metrics
        $this->save_historical_metrics($metrics);
        
        return $metrics;
    }
    
    /**
     * Get token metrics
     */
    private function get_token_metrics() {
        // Get token price from Solana API
        $token_price = $this->solana_api->get_token_price();
        
        // Get token supply
        $total_supply = $this->solana_api->get_token_supply();
        $circulating_supply = $this->solana_api->get_circulating_supply();
        
        // Get 24h volume
        $volume_24h = $this->solana_api->get_token_volume_24h();
        
        // Get price change percentage
        $price_change_24h = $this->solana_api->get_price_change_24h();
        
        // Get market cap
        $market_cap = $token_price * $circulating_supply;
        
        // Get total transactions
        $transaction_count = $this->get_transaction_count();
        
        return array(
            'price' => $token_price,
            'total_supply' => $total_supply,
            'circulating_supply' => $circulating_supply,
            'volume_24h' => $volume_24h,
            'price_change_24h' => $price_change_24h,
            'market_cap' => $market_cap,
            'transaction_count' => $transaction_count
        );
    }
    
    /**
     * Get governance metrics
     */
    private function get_governance_metrics() {
        global $wpdb;
        
        // Get proposal counts by status
        $proposal_counts = $wpdb->get_results(
            "SELECT status, COUNT(*) as count 
            FROM {$wpdb->prefix}vortex_dao_proposals 
            GROUP BY status",
            OBJECT_K
        );
        
        // Format proposal counts
        $proposal_stats = array(
            'active' => 0,
            'approved' => 0,
            'rejected' => 0,
            'failed_quorum' => 0,
            'vetoed' => 0,
            'executed' => 0,
            'total' => 0
        );
        
        foreach ($proposal_counts as $status => $data) {
            $proposal_stats[$status] = intval($data->count);
            $proposal_stats['total'] += intval($data->count);
        }
        
        // Get total votes
        $total_votes = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vortex_dao_votes");
        
        // Get unique voters
        $unique_voters = $wpdb->get_var("SELECT COUNT(DISTINCT wallet_address) FROM {$wpdb->prefix}vortex_dao_votes");
        
        // Get recent proposals
        $recent_proposals = $wpdb->get_results(
            "SELECT id, title, status, created_at 
            FROM {$wpdb->prefix}vortex_dao_proposals 
            ORDER BY created_at DESC 
            LIMIT 5"
        );
        
        // Get voter participation rate
        $total_token_holders = $this->get_total_token_holders();
        $participation_rate = $total_token_holders > 0 ? ($unique_voters / $total_token_holders) * 100 : 0;
        
        return array(
            'proposal_stats' => $proposal_stats,
            'total_votes' => intval($total_votes),
            'unique_voters' => intval($unique_voters),
            'recent_proposals' => $recent_proposals,
            'participation_rate' => $participation_rate
        );
    }
    
    /**
     * Get holder metrics
     */
    private function get_holder_metrics() {
        // Get total holders
        $total_holders = $this->get_total_token_holders();
        
        // Get holder distribution - ideally this would come from the blockchain
        // For now we'll use approximate distribution
        $holder_distribution = array(
            'founders' => 65, // 65% held by founders
            'investors' => 15, // 15% held by investors
            'team' => 10, // 10% held by team
            'community' => 10 // 10% held by community
        );
        
        // Get holder growth over time (last 6 months)
        $holder_growth = $this->get_holder_growth();
        
        return array(
            'total_holders' => $total_holders,
            'holder_distribution' => $holder_distribution,
            'holder_growth' => $holder_growth
        );
    }
    
    /**
     * Get treasury metrics
     */
    private function get_treasury_metrics() {
        // Get treasury balance
        $treasury_address = get_option('vortex_dao_treasury_address');
        $treasury_balance = $this->token->get_balance($treasury_address);
        
        // Get recent treasury transactions
        global $wpdb;
        $recent_transactions = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}vortex_token_transfers 
            WHERE from_address = '$treasury_address' OR to_address = '$treasury_address' 
            ORDER BY created_at DESC 
            LIMIT 10"
        );
        
        // Get grants data
        $total_grants = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vortex_dao_grants");
        $total_grant_amount = $wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}vortex_dao_grants");
        
        return array(
            'treasury_balance' => $treasury_balance,
            'recent_transactions' => $recent_transactions,
            'total_grants' => intval($total_grants),
            'total_grant_amount' => floatval($total_grant_amount)
        );
    }
    
    /**
     * Get marketplace metrics
     */
    private function get_marketplace_metrics() {
        global $wpdb;
        
        // Get total artwork count
        $total_artworks = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vortex_artworks");
        
        // Get total artist count
        $total_artists = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}vortex_artist_profiles WHERE approved = 1");
        
        // Get sales volume in the last 30 days
        $start_date = date('Y-m-d', strtotime('-30 days'));
        $sales_volume_30d = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(price) FROM {$wpdb->prefix}vortex_artwork_sales WHERE created_at >= %s",
            $start_date
        ));
        
        // Get fees collected for the DAO treasury in the last 30 days
        $dao_fee_percentage = get_option('vortex_dao_fee_percentage', 2.5); // Default 2.5%
        $dao_fees_30d = $sales_volume_30d * ($dao_fee_percentage / 100);
        
        // Get number of DAO token holders who are also artists
        $dao_artists = $wpdb->get_var(
            "SELECT COUNT(DISTINCT a.user_id) 
            FROM {$wpdb->prefix}vortex_artist_profiles a
            INNER JOIN {$wpdb->prefix}vortex_wallet_addresses w ON a.user_id = w.user_id
            WHERE a.approved = 1 AND w.verified = 1 AND w.token_balance > 0"
        );
        
        // Get percentage of artists who are also DAO token holders
        $artist_participation = $total_artists > 0 ? ($dao_artists / $total_artists) * 100 : 0;
        
        // Get top artist categories by sales
        $top_categories = $wpdb->get_results(
            "SELECT c.name, COUNT(s.id) as sales_count, SUM(s.price) as sales_volume
            FROM {$wpdb->prefix}vortex_artwork_sales s
            JOIN {$wpdb->prefix}vortex_artworks a ON s.artwork_id = a.id
            JOIN {$wpdb->prefix}vortex_artwork_categories ac ON a.id = ac.artwork_id
            JOIN {$wpdb->prefix}vortex_categories c ON ac.category_id = c.id
            GROUP BY c.id
            ORDER BY sales_volume DESC
            LIMIT 5"
        );
        
        return array(
            'total_artworks' => intval($total_artworks),
            'total_artists' => intval($total_artists),
            'sales_volume_30d' => floatval($sales_volume_30d),
            'dao_fees_30d' => floatval($dao_fees_30d),
            'dao_artists' => intval($dao_artists),
            'artist_participation' => floatval($artist_participation),
            'top_categories' => $top_categories
        );
    }
    
    /**
     * Get total token holders
     */
    private function get_total_token_holders() {
        // Get count from Solana API
        $total_holders = $this->solana_api->get_total_holders();
        
        if ($total_holders === false) {
            // Fallback to database count
            global $wpdb;
            $total_holders = $wpdb->get_var("SELECT COUNT(DISTINCT wallet_address) FROM {$wpdb->prefix}vortex_wallet_addresses WHERE verified = 1");
        }
        
        return $total_holders;
    }
    
    /**
     * Get transaction count
     */
    private function get_transaction_count() {
        // Get count from Solana API
        $transaction_count = $this->solana_api->get_transaction_count();
        
        if ($transaction_count === false) {
            // Fallback to database count
            global $wpdb;
            $transaction_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vortex_token_transfers");
        }
        
        return $transaction_count;
    }
    
    /**
     * Get holder growth over time
     */
    private function get_holder_growth() {
        global $wpdb;
        
        // Get historical data from database
        $growth_data = $wpdb->get_results(
            "SELECT date, holder_count FROM {$wpdb->prefix}vortex_dao_metrics_history 
            WHERE date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) 
            ORDER BY date ASC"
        );
        
        // If we don't have enough historical data, simulate it
        if (count($growth_data) < 6) {
            $growth_data = $this->simulate_holder_growth();
        }
        
        return $growth_data;
    }
    
    /**
     * Simulate holder growth data for demonstration
     */
    private function simulate_holder_growth() {
        $data = array();
        $current_holders = $this->get_total_token_holders();
        
        // Start with approximately 60% of current holders 6 months ago
        $start_holders = max(10, floor($current_holders * 0.6));
        $monthly_growth = ($current_holders - $start_holders) / 6;
        
        for ($i = 6; $i >= 0; $i--) {
            $month_date = date('Y-m-d', strtotime("-$i months"));
            $holder_count = round($start_holders + ($monthly_growth * (6 - $i)));
            
            $data[] = array(
                'date' => $month_date,
                'holder_count' => $holder_count
            );
        }
        
        return $data;
    }
    
    /**
     * Save historical metrics
     */
    private function save_historical_metrics($metrics) {
        global $wpdb;
        
        // Check if we already have metrics for today
        $today = date('Y-m-d');
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}vortex_dao_metrics_history WHERE date = %s",
            $today
        ));
        
        if ($existing) {
            // Update existing record
            $wpdb->update(
                $wpdb->prefix . 'vortex_dao_metrics_history',
                array(
                    'token_price' => $metrics['token']['price'],
                    'market_cap' => $metrics['token']['market_cap'],
                    'volume_24h' => $metrics['token']['volume_24h'],
                    'holder_count' => $metrics['holders']['total_holders'],
                    'participation_rate' => $metrics['governance']['participation_rate'],
                    'treasury_balance' => $metrics['treasury']['treasury_balance'],
                    'updated_at' => current_time('mysql')
                ),
                array('date' => $today)
            );
        } else {
            // Insert new record
            $wpdb->insert(
                $wpdb->prefix . 'vortex_dao_metrics_history',
                array(
                    'date' => $today,
                    'token_price' => $metrics['token']['price'],
                    'market_cap' => $metrics['token']['market_cap'],
                    'volume_24h' => $metrics['token']['volume_24h'],
                    'holder_count' => $metrics['holders']['total_holders'],
                    'participation_rate' => $metrics['governance']['participation_rate'],
                    'treasury_balance' => $metrics['treasury']['treasury_balance'],
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                )
            );
        }
    }
    
    /**
     * AJAX: Get DAO metrics
     */
    public function ajax_get_dao_metrics() {
        // Verify nonce
        check_ajax_referer('vortex_dao_nonce', 'nonce');
        
        // Get specific section if requested
        $section = isset($_POST['section']) ? sanitize_text_field($_POST['section']) : 'all';
        
        // Get metrics
        $metrics = $this->get_metrics();
        
        // Return specific section if requested
        if ($section !== 'all' && isset($metrics[$section])) {
            wp_send_json_success(array('metrics' => $metrics[$section]));
        } else {
            wp_send_json_success(array('metrics' => $metrics));
        }
    }
    
    /**
     * DAO metrics shortcode
     */
    public function metrics_shortcode($atts) {
        $atts = shortcode_atts(array(
            'section' => 'all', // all, token, governance, holders, treasury
            'layout' => 'full', // full, compact, minimal
            'chart' => 'true' // true, false
        ), $atts);
        
        // Enqueue necessary scripts and styles
        wp_enqueue_style('vortex-dao-metrics');
        wp_enqueue_script('vortex-dao-metrics');
        
        // Get metrics
        $metrics = $this->get_metrics();
        
        // Start output buffer
        ob_start();
        
        // Include template based on section
        switch ($atts['section']) {
            case 'token':
                include(VORTEX_PLUGIN_DIR . 'public/partials/metrics/vortex-dao-token-metrics.php');
                break;
                
            case 'governance':
                include(VORTEX_PLUGIN_DIR . 'public/partials/metrics/vortex-dao-governance-metrics.php');
                break;
                
            case 'holders':
                include(VORTEX_PLUGIN_DIR . 'public/partials/metrics/vortex-dao-holder-metrics.php');
                break;
                
            case 'treasury':
                include(VORTEX_PLUGIN_DIR . 'public/partials/metrics/vortex-dao-treasury-metrics.php');
                break;
                
            case 'marketplace':
                include(VORTEX_PLUGIN_DIR . 'public/partials/metrics/vortex-dao-marketplace-metrics.php');
                break;
                
            case 'all':
            default:
                include(VORTEX_PLUGIN_DIR . 'public/partials/metrics/vortex-dao-metrics-dashboard.php');
                break;
        }
        
        return ob_get_clean();
    }
    
    /**
     * Create required database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Metrics history table
        $table_metrics = $wpdb->prefix . 'vortex_dao_metrics_history';
        $sql_metrics = "CREATE TABLE $table_metrics (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            date date NOT NULL,
            token_price decimal(18,8) NOT NULL DEFAULT 0,
            market_cap decimal(18,2) NOT NULL DEFAULT 0,
            volume_24h decimal(18,2) NOT NULL DEFAULT 0,
            holder_count int(11) NOT NULL DEFAULT 0,
            participation_rate decimal(5,2) NOT NULL DEFAULT 0,
            treasury_balance decimal(18,8) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY date (date)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_metrics);
    }
    
    /**
     * Record current DAO metrics
     *
     * @param string $snapshot_type Type of snapshot (daily, proposal, manual)
     * @return int|false The ID of the new metrics record, or false on failure
     */
    public function record_metrics($snapshot_type = 'manual') {
        global $wpdb;
        
        // Gather current metrics
        $metrics = array(
            'total_supply' => $this->get_total_token_supply(),
            'circulating_supply' => $this->get_circulating_supply(),
            'treasury_balance' => $this->get_treasury_balance(),
            'total_holders' => $this->get_total_holders(),
            'active_proposals' => $this->get_active_proposals_count(),
            'total_votes' => $this->get_total_votes_count(),
            'voter_participation' => $this->calculate_voter_participation(),
            'average_vote_weight' => $this->calculate_average_vote_weight(),
            'token_price' => $this->get_current_token_price(),
            'market_cap' => $this->calculate_market_cap(),
            'total_value_locked' => $this->get_total_value_locked(),
            'governance_stats' => wp_json_encode($this->get_governance_statistics()),
            'snapshot_type' => $snapshot_type,
            'recorded_at' => current_time('mysql')
        );
        
        // Insert metrics record
        $result = $wpdb->insert(
            $wpdb->prefix . 'vortex_dao_metrics_history',
            $metrics,
            array(
                '%f', // total_supply
                '%f', // circulating_supply
                '%f', // treasury_balance
                '%d', // total_holders
                '%d', // active_proposals
                '%d', // total_votes
                '%f', // voter_participation
                '%f', // average_vote_weight
                '%f', // token_price
                '%f', // market_cap
                '%f', // total_value_locked
                '%s', // governance_stats
                '%s', // snapshot_type
                '%s'  // recorded_at
            )
        );
        
        if ($result === false) {
            return false;
        }
        
        do_action('vortex_dao_metrics_recorded', $wpdb->insert_id, $metrics);
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get metrics history with filtering options
     *
     * @param array $args Query arguments
     * @return array Array of metrics records
     */
    public function get_metrics_history($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'snapshot_type' => '',
            'from_date' => '',
            'to_date' => '',
            'metrics' => array(),
            'orderby' => 'recorded_at',
            'order' => 'DESC',
            'limit' => 30,
            'offset' => 0
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array('1=1');
        $values = array();
        
        if (!empty($args['snapshot_type'])) {
            $where[] = 'snapshot_type = %s';
            $values[] = $args['snapshot_type'];
        }
        
        if (!empty($args['from_date'])) {
            $where[] = 'recorded_at >= %s';
            $values[] = $args['from_date'];
        }
        
        if (!empty($args['to_date'])) {
            $where[] = 'recorded_at <= %s';
            $values[] = $args['to_date'];
        }
        
        // Build query
        $select = '*';
        if (!empty($args['metrics'])) {
            $select = 'id, recorded_at, ' . implode(', ', array_map('sanitize_key', $args['metrics']));
        }
        
        $query = "SELECT {$select} 
                 FROM {$wpdb->prefix}vortex_dao_metrics_history
                 WHERE " . implode(' AND ', $where) . "
                 ORDER BY {$args['orderby']} {$args['order']}
                 LIMIT %d OFFSET %d";
        
        $values[] = $args['limit'];
        $values[] = $args['offset'];
        
        $metrics = $wpdb->get_results(
            $wpdb->prepare($query, $values),
            ARRAY_A
        );
        
        // Process metrics
        foreach ($metrics as &$metric) {
            if (isset($metric['governance_stats'])) {
                $metric['governance_stats'] = json_decode($metric['governance_stats'], true);
            }
            $metric['recorded_at_formatted'] = human_time_diff(
                strtotime($metric['recorded_at']), 
                current_time('timestamp')
            ) . ' ago';
        }
        
        return $metrics;
    }
    
    /**
     * Calculate metrics changes over time period
     *
     * @param string $period Time period (day, week, month)
     * @return array Changes in metrics
     */
    public function calculate_metrics_changes($period = 'day') {
        global $wpdb;
        
        $current_metrics = $this->get_latest_metrics();
        if (!$current_metrics) {
            return array();
        }
        
        // Get comparison date
        switch ($period) {
            case 'week':
                $compare_date = date('Y-m-d H:i:s', strtotime('-7 days'));
                break;
            case 'month':
                $compare_date = date('Y-m-d H:i:s', strtotime('-30 days'));
                break;
            default: // day
                $compare_date = date('Y-m-d H:i:s', strtotime('-24 hours'));
        }
        
        $previous_metrics = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vortex_dao_metrics_history
                WHERE recorded_at <= %s
                ORDER BY recorded_at DESC
                LIMIT 1",
                $compare_date
            ),
            ARRAY_A
        );
        
        if (!$previous_metrics) {
            return array();
        }
        
        $changes = array();
        $numeric_fields = array(
            'total_supply',
            'circulating_supply',
            'treasury_balance',
            'total_holders',
            'token_price',
            'market_cap',
            'total_value_locked',
            'voter_participation'
        );
        
        foreach ($numeric_fields as $field) {
            if (isset($current_metrics[$field]) && isset($previous_metrics[$field])) {
                $change = $current_metrics[$field] - $previous_metrics[$field];
                $percent = $previous_metrics[$field] != 0 ? 
                    ($change / $previous_metrics[$field]) * 100 : 0;
                
                $changes[$field] = array(
                    'absolute' => $change,
                    'percentage' => round($percent, 2)
                );
            }
        }
        
        return $changes;
    }
    
    /**
     * Get latest metrics record
     *
     * @return array|null Latest metrics or null if none found
     */
    public function get_latest_metrics() {
        global $wpdb;
        
        return $wpdb->get_row(
            "SELECT * FROM {$wpdb->prefix}vortex_dao_metrics_history
            ORDER BY recorded_at DESC
            LIMIT 1",
            ARRAY_A
        );
    }
    
    /**
     * Record daily metrics snapshot
     */
    public function record_daily_metrics() {
        $this->record_metrics('daily');
    }
    
    // Helper methods for gathering metrics
    
    private function get_total_token_supply() {
        // Implementation depends on token contract integration
        return 0;
    }
    
    private function get_circulating_supply() {
        // Implementation depends on token contract integration
        return 0;
    }
    
    private function get_treasury_balance() {
        // Implementation depends on treasury contract integration
        return 0;
    }
    
    private function get_active_proposals_count() {
        global $wpdb;
        return $wpdb->get_var(
            "SELECT COUNT(*) 
            FROM {$wpdb->prefix}vortex_dao_proposals 
            WHERE status = 'active'"
        );
    }
    
    private function get_total_votes_count() {
        global $wpdb;
        return $wpdb->get_var(
            "SELECT COUNT(*) 
            FROM {$wpdb->prefix}vortex_dao_votes"
        );
    }
    
    private function calculate_voter_participation() {
        // Implementation for calculating voter participation rate
        return 0;
    }
    
    private function calculate_average_vote_weight() {
        // Implementation for calculating average vote weight
        return 0;
    }
    
    private function get_current_token_price() {
        // Implementation depends on price feed integration
        return 0;
    }
    
    private function calculate_market_cap() {
        return $this->get_circulating_supply() * $this->get_current_token_price();
    }
    
    private function get_total_value_locked() {
        // Implementation depends on staking contract integration
        return 0;
    }
    
    private function get_governance_statistics() {
        // Gather detailed governance statistics
        return array(
            'proposal_stats' => $this->get_proposal_statistics(),
            'voting_stats' => $this->get_voting_statistics(),
            'participation_stats' => $this->get_participation_statistics()
        );
    }
}

// Initialize Metrics class
$vortex_dao_metrics = VORTEX_DAO_Metrics::get_instance();

// Register activation hook for table creation
register_activation_hook(VORTEX_PLUGIN_FILE, array('VORTEX_DAO_Metrics', 'create_tables')); 