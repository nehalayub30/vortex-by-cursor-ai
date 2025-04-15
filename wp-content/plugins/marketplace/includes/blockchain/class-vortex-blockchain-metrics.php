<?php
/**
 * VORTEX Blockchain Metrics
 *
 * Handles real-time metrics for blockchain data
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class VORTEX_Blockchain_Metrics {
    private static $instance = null;
    private $cache_expiry = 300; // 5 minutes
    
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
        add_action('wp_ajax_vortex_get_blockchain_metrics', array($this, 'ajax_get_blockchain_metrics'));
        add_action('wp_ajax_nopriv_vortex_get_blockchain_metrics', array($this, 'ajax_get_blockchain_metrics'));
        
        // Add real-time update hooks
        add_action('vortex_artwork_tokenized', array($this, 'invalidate_metrics_cache'), 10, 2);
        add_action('vortex_token_transferred', array($this, 'invalidate_metrics_cache'), 10, 2);
        add_action('vortex_marketplace_sale_completed', array($this, 'invalidate_metrics_cache'), 10, 2);
        
        // Schedule hourly cache refresh
        if (!wp_next_scheduled('vortex_refresh_blockchain_metrics')) {
            wp_schedule_event(time(), 'hourly', 'vortex_refresh_blockchain_metrics');
        }
        add_action('vortex_refresh_blockchain_metrics', array($this, 'refresh_all_metrics_cache'));
    }
    
    /**
     * Get metrics with caching
     * 
     * @param string $metric_type Type of metrics to retrieve
     * @param int $days Number of days to look back
     * @param bool $force_refresh Whether to force cache refresh
     * @return array The metrics data
     */
    public function get_metrics($metric_type = 'artwork', $days = 7, $force_refresh = false) {
        $cache_key = 'vortex_blockchain_metrics_' . $metric_type . '_' . $days;
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false && !$force_refresh) {
            return $cached_data;
        }
        
        // Get fresh data
        switch ($metric_type) {
            case 'artwork':
                $data = $this->get_artwork_metrics($days);
                break;
            case 'token':
                $data = $this->get_token_metrics($days);
                break;
            case 'all':
                $data = array(
                    'artwork' => $this->get_artwork_metrics($days),
                    'token' => $this->get_token_metrics($days)
                );
                break;
            default:
                $data = array('error' => 'Invalid metric type');
                break;
        }
        
        // Cache the data for 1 hour by default
        $cache_duration = apply_filters('vortex_blockchain_metrics_cache_duration', HOUR_IN_SECONDS);
        set_transient($cache_key, $data, $cache_duration);
        
        return $data;
    }
    
    /**
     * Get artwork metrics from blockchain
     * 
     * @param int $days Number of days to look back
     * @return array Artwork metrics
     */
    public function get_artwork_metrics($days = 7) {
        global $wpdb;
        
        $from_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        // Get artwork creation count by day
        $creation_data = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(created_at) as date, COUNT(*) as count 
             FROM {$wpdb->prefix}vortex_artwork_tokens
             WHERE created_at >= %s
             GROUP BY DATE(created_at)
             ORDER BY date ASC",
            $from_date
        ), ARRAY_A);
        
        // Get artwork sales by day
        $sales_data = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(t.created_at) as date, COUNT(*) as count, SUM(t.amount) as volume
             FROM {$wpdb->prefix}vortex_token_transfers t
             JOIN {$wpdb->prefix}vortex_artwork_tokens a ON t.metadata LIKE CONCAT('%%', a.token_id, '%%')
             WHERE t.transfer_type = 'sale' AND t.created_at >= %s
             GROUP BY DATE(t.created_at)
             ORDER BY date ASC",
            $from_date
        ), ARRAY_A);
        
        // Get most swapped artists
        $top_artists = $wpdb->get_results($wpdb->prepare(
            "SELECT a.artist_id, u.display_name as artist_name, COUNT(*) as swap_count, SUM(t.amount) as volume
             FROM {$wpdb->prefix}vortex_token_transfers t
             JOIN {$wpdb->prefix}vortex_artwork_tokens tk ON t.metadata LIKE CONCAT('%%', tk.token_id, '%%')
             JOIN {$wpdb->prefix}vortex_artworks a ON tk.artwork_id = a.id
             JOIN {$wpdb->users} u ON a.artist_id = u.ID
             WHERE t.created_at >= %s
             GROUP BY a.artist_id
             ORDER BY swap_count DESC
             LIMIT 10",
            $from_date
        ), ARRAY_A);
        
        // Get most popular categories
        $top_categories = $wpdb->get_results($wpdb->prepare(
            "SELECT c.name as category, COUNT(*) as count, SUM(t.amount) as volume
             FROM {$wpdb->prefix}vortex_token_transfers t
             JOIN {$wpdb->prefix}vortex_artwork_tokens tk ON t.metadata LIKE CONCAT('%%', tk.token_id, '%%')
             JOIN {$wpdb->prefix}vortex_artworks a ON tk.artwork_id = a.id
             JOIN {$wpdb->term_relationships} tr ON a.id = tr.object_id
             JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
             JOIN {$wpdb->terms} c ON tt.term_id = c.term_id
             WHERE tt.taxonomy = 'artwork_category' AND t.created_at >= %s
             GROUP BY c.term_id
             ORDER BY count DESC
             LIMIT 10",
            $from_date
        ), ARRAY_A);
        
        // Get daily swap metrics
        $swap_metrics = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(created_at) as date, COUNT(*) as count, SUM(amount) as volume
             FROM {$wpdb->prefix}vortex_token_transfers
             WHERE transfer_type = 'swap' AND created_at >= %s
             GROUP BY DATE(created_at)
             ORDER BY date ASC",
            $from_date
        ), ARRAY_A);
        
        // Get trending artworks (most transferred in last week)
        $trending_artworks = $wpdb->get_results($wpdb->prepare(
            "SELECT a.id, a.title, a.thumbnail, COUNT(t.id) as transfer_count, 
                    MAX(t.amount) as highest_amount, a.artist_id, u.display_name as artist_name
             FROM {$wpdb->prefix}vortex_token_transfers t
             JOIN {$wpdb->prefix}vortex_artwork_tokens tk ON t.metadata LIKE CONCAT('%%', tk.token_id, '%%')
             JOIN {$wpdb->prefix}vortex_artworks a ON tk.artwork_id = a.id
             JOIN {$wpdb->users} u ON a.artist_id = u.ID
             WHERE t.created_at >= %s
             GROUP BY a.id
             ORDER BY transfer_count DESC, highest_amount DESC
             LIMIT 5",
            $from_date
        ), ARRAY_A);
        
        // Format trending artworks data
        foreach ($trending_artworks as &$artwork) {
            $artwork['thumbnail_url'] = wp_get_attachment_url($artwork['thumbnail']);
            $artwork['permalink'] = get_permalink($artwork['id']);
        }
        
        return array(
            'creation_data' => $creation_data,
            'sales_data' => $sales_data,
            'top_artists' => $top_artists,
            'top_categories' => $top_categories,
            'swap_metrics' => $swap_metrics,
            'trending_artworks' => $trending_artworks,
            'total_artworks' => $this->get_total_tokenized_artworks(),
            'total_volume' => $this->get_total_trading_volume($days),
            'last_updated' => current_time('mysql')
        );
    }
    
    /**
     * Get token metrics from blockchain
     * 
     * @param int $days Number of days to look back
     * @return array Token metrics
     */
    public function get_token_metrics($days = 7) {
        global $wpdb;
        
        $from_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        // Get token transfer count by day
        $transfer_data = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(created_at) as date, COUNT(*) as count, SUM(amount) as volume
             FROM {$wpdb->prefix}vortex_token_transfers
             WHERE created_at >= %s
             GROUP BY DATE(created_at)
             ORDER BY date ASC",
            $from_date
        ), ARRAY_A);
        
        // Get token transfer types distribution
        $transfer_types = $wpdb->get_results($wpdb->prepare(
            "SELECT transfer_type, COUNT(*) as count, SUM(amount) as volume
             FROM {$wpdb->prefix}vortex_token_transfers
             WHERE created_at >= %s
             GROUP BY transfer_type
             ORDER BY count DESC",
            $from_date
        ), ARRAY_A);
        
        // Get top token holders
        $top_holders = $wpdb->get_results(
            "SELECT wallet_address, token_balance, u.display_name
             FROM {$wpdb->prefix}vortex_wallet_addresses w
             LEFT JOIN {$wpdb->users} u ON w.user_id = u.ID
             WHERE token_balance > 0
             ORDER BY token_balance DESC
             LIMIT 10"
        , ARRAY_A);
        
        // Get token price history
        $price_history = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(recorded_at) as date, token_price
             FROM {$wpdb->prefix}vortex_dao_metrics_history
             WHERE recorded_at >= %s
             GROUP BY DATE(recorded_at)
             ORDER BY date ASC",
            $from_date
        ), ARRAY_A);
        
        return array(
            'transfer_data' => $transfer_data,
            'transfer_types' => $transfer_types,
            'top_holders' => $top_holders,
            'price_history' => $price_history,
            'total_transfers' => $this->get_total_transfers($days),
            'total_holders' => $this->get_total_token_holders(),
            'current_price' => $this->get_current_token_price(),
            'last_updated' => current_time('mysql')
        );
    }
    
    /**
     * Get total tokenized artworks
     * 
     * @return int Total artworks on blockchain
     */
    public function get_total_tokenized_artworks() {
        global $wpdb;
        return (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vortex_artwork_tokens");
    }
    
    /**
     * Get total trading volume
     * 
     * @param int $days Number of days to look back
     * @return float Total volume
     */
    public function get_total_trading_volume($days = 7) {
        global $wpdb;
        
        $from_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        return (float)$wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM {$wpdb->prefix}vortex_token_transfers
             WHERE created_at >= %s",
            $from_date
        ));
    }
    
    /**
     * Get total transfers
     * 
     * @param int $days Number of days to look back
     * @return int Total transfers
     */
    public function get_total_transfers($days = 7) {
        global $wpdb;
        
        $from_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        return (int)$wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_token_transfers
             WHERE created_at >= %s",
            $from_date
        ));
    }
    
    /**
     * Get total token holders
     * 
     * @return int Total holders
     */
    public function get_total_token_holders() {
        global $wpdb;
        return (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vortex_wallet_addresses WHERE token_balance > 0");
    }
    
    /**
     * Get current token price
     * 
     * @return float Current price
     */
    public function get_current_token_price() {
        global $wpdb;
        $price = $wpdb->get_var(
            "SELECT token_price FROM {$wpdb->prefix}vortex_dao_metrics_history
             ORDER BY recorded_at DESC LIMIT 1"
        );
        
        return $price ? (float)$price : 0;
    }
    
    /**
     * Invalidate metrics cache when new events occur
     */
    public function invalidate_metrics_cache() {
        // Clear artwork metrics cache for all timeframes
        delete_transient('vortex_blockchain_metrics_artwork_7');
        delete_transient('vortex_blockchain_metrics_artwork_30');
        delete_transient('vortex_blockchain_metrics_artwork_90');
        
        // Clear token metrics cache for all timeframes
        delete_transient('vortex_blockchain_metrics_token_7');
        delete_transient('vortex_blockchain_metrics_token_30');
        delete_transient('vortex_blockchain_metrics_token_90');
        
        // Clear all metrics cache
        delete_transient('vortex_blockchain_metrics_all_7');
        delete_transient('vortex_blockchain_metrics_all_30');
        delete_transient('vortex_blockchain_metrics_all_90');
        
        // Fire action for any listeners
        do_action('vortex_blockchain_metrics_cache_invalidated');
    }
    
    /**
     * Refresh all metrics cache
     */
    public function refresh_all_metrics_cache() {
        // Force refresh of all metrics for common timeframes
        $this->get_metrics('artwork', 7, true);
        $this->get_metrics('artwork', 30, true);
        $this->get_metrics('token', 7, true);
        $this->get_metrics('token', 30, true);
        $this->get_metrics('all', 7, true);
        
        // Log refresh
        error_log('VORTEX blockchain metrics cache refreshed at ' . current_time('mysql'));
    }
    
    /**
     * Export metrics to CSV
     * 
     * @param array $metrics Metrics data
     * @return string CSV content
     */
    public function export_metrics_to_csv($metrics) {
        $csv_data = array();
        
        // Add header row based on metrics type
        if (isset($metrics['creation_data'])) {
            // Artwork metrics headers
            $csv_data[] = array(
                'Date', 'Artworks Created', 'Artworks Sold', 'Sale Volume',
                'Swap Count', 'Swap Volume', 'Total Volume'
            );
            
            // Process artwork metrics
            $dates = array_column($metrics['creation_data'], 'date');
            
            foreach ($dates as $date) {
                $row = array($date);
                
                // Add created artworks
                $created = 0;
                foreach ($metrics['creation_data'] as $item) {
                    if ($item['date'] === $date) {
                        $created = $item['count'];
                        break;
                    }
                }
                $row[] = $created;
                
                // Add sold artworks and volume
                $sold = 0;
                $sale_volume = 0;
                foreach ($metrics['sales_data'] as $item) {
                    if ($item['date'] === $date) {
                        $sold = $item['count'];
                        $sale_volume = $item['volume'];
                        break;
                    }
                }
                $row[] = $sold;
                $row[] = $sale_volume;
                
                // Add swap count and volume
                $swap_count = 0;
                $swap_volume = 0;
                foreach ($metrics['swap_metrics'] as $item) {
                    if ($item['date'] === $date) {
                        $swap_count = $item['count'];
                        $swap_volume = $item['volume'];
                        break;
                    }
                }
                $row[] = $swap_count;
                $row[] = $swap_volume;
                
                // Add total volume
                $row[] = $sale_volume + $swap_volume;
                
                $csv_data[] = $row;
            }
            
            // Add top artists section
            $csv_data[] = array('');
            $csv_data[] = array('Top Artists by Swap Count');
            $csv_data[] = array('Artist', 'Swap Count', 'Volume');
            
            foreach ($metrics['top_artists'] as $artist) {
                $csv_data[] = array(
                    $artist['artist_name'],
                    $artist['swap_count'],
                    $artist['volume']
                );
            }
            
            // Add top categories section
            $csv_data[] = array('');
            $csv_data[] = array('Top Categories by Count');
            $csv_data[] = array('Category', 'Count', 'Volume');
            
            foreach ($metrics['top_categories'] as $category) {
                $csv_data[] = array(
                    $category['category'],
                    $category['count'],
                    $category['volume']
                );
            }
        } elseif (isset($metrics['transfer_data'])) {
            // Token metrics headers
            $csv_data[] = array(
                'Date', 'Transfer Count', 'Transfer Volume'
            );
            
            // Process token metrics
            foreach ($metrics['transfer_data'] as $item) {
                $csv_data[] = array(
                    $item['date'],
                    $item['count'],
                    $item['volume']
                );
            }
            
            // Add transfer types section
            $csv_data[] = array('');
            $csv_data[] = array('Transfer Types');
            $csv_data[] = array('Type', 'Count', 'Volume');
            
            foreach ($metrics['transfer_types'] as $type) {
                $csv_data[] = array(
                    $type['transfer_type'],
                    $type['count'],
                    $type['volume']
                );
            }
            
            // Add top holders section
            $csv_data[] = array('');
            $csv_data[] = array('Top Token Holders');
            $csv_data[] = array('Address/User', 'Balance');
            
            foreach ($metrics['top_holders'] as $holder) {
                $display_name = empty($holder['display_name']) ? 
                    substr($holder['wallet_address'], 0, 6) . '...' . substr($holder['wallet_address'], -4) : 
                    $holder['display_name'];
                
                $csv_data[] = array(
                    $display_name,
                    $holder['token_balance']
                );
            }
        }
        
        // Convert array to CSV
        $output = fopen('php://temp', 'r+');
        foreach ($csv_data as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
    
    /**
     * AJAX handler for getting blockchain metrics
     */
    public function ajax_get_blockchain_metrics() {
        check_ajax_referer('vortex_nonce', 'nonce');
        
        $days = isset($_GET['days']) ? intval($_GET['days']) : 7;
        $metric_type = isset($_GET['metric_type']) ? sanitize_text_field($_GET['metric_type']) : 'artwork';
        $force_refresh = isset($_GET['force_refresh']) && $_GET['force_refresh'] === '1';
        
        // Validate inputs
        $days = max(1, min(365, $days)); // Limit to 1-365 days
        if (!in_array($metric_type, array('artwork', 'token', 'all'))) {
            $metric_type = 'artwork';
        }
        
        $data = $this->get_metrics($metric_type, $days, $force_refresh);
        
        wp_send_json_success($data);
    }
    
    /**
     * Cleanup on plugin deactivation
     */
    public static function cleanup() {
        $timestamp = wp_next_scheduled('vortex_refresh_blockchain_metrics');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'vortex_refresh_blockchain_metrics');
        }
    }
    
    private function get_tola_metrics() {
        try {
            $tola = new VORTEX_TOLA_Integration();
            return $tola->get_current_metrics();
        } catch (Exception $e) {
            error_log("VORTEX: Error fetching TOLA metrics: " . $e->getMessage());
            return null;
        }
    }
}

// Initialize Blockchain Metrics
$vortex_blockchain_metrics = VORTEX_Blockchain_Metrics::get_instance();

// Register deactivation hook
register_deactivation_hook(__FILE__, array('VORTEX_Blockchain_Metrics', 'cleanup'));