<?php
/**
 * Blockchain Metrics
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class Vortex_Blockchain_Metrics {
    
    /**
     * Initialize the metrics system
     */
    public function __construct() {
        add_shortcode('vortex_blockchain_metrics', array($this, 'render_blockchain_metrics'));
        add_action('wp_ajax_vortex_get_blockchain_metrics', array($this, 'get_blockchain_metrics'));
        add_action('wp_ajax_nopriv_vortex_get_blockchain_metrics', array($this, 'get_blockchain_metrics'));
    }
    
    /**
     * Render blockchain metrics dashboard
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_blockchain_metrics($atts) {
        // Enqueue required assets
        wp_enqueue_style('vortex-blockchain-metrics', plugin_dir_url(dirname(__FILE__)) . 'public/css/vortex-blockchain-metrics.css', array(), VORTEX_VERSION);
        wp_enqueue_script('vortex-charts', plugin_dir_url(dirname(__FILE__)) . 'public/js/vortex-charts.js', array('jquery'), VORTEX_VERSION, true);
        wp_enqueue_script('vortex-blockchain-metrics', plugin_dir_url(dirname(__FILE__)) . 'public/js/vortex-blockchain-metrics.js', array('jquery', 'vortex-charts'), VORTEX_VERSION, true);
        
        wp_localize_script('vortex-blockchain-metrics', 'vortex_metrics', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_metrics_nonce')
        ));
        
        ob_start();
        include plugin_dir_path(dirname(__FILE__)) . 'public/partials/vortex-blockchain-metrics.php';
        return ob_get_clean();
    }
    
    /**
     * Get blockchain metrics data via AJAX
     */
    public function get_blockchain_metrics() {
        check_ajax_referer('vortex_metrics_nonce', 'nonce');
        
        $blockchain = new Vortex_Blockchain_Connection();
        
        // Get metrics data
        $total_artworks = $this->get_total_verified_artworks();
        $total_artists = $this->get_total_verified_artists();
        $total_swaps = $this->get_total_completed_swaps();
        $top_artists = $this->get_top_swapped_artists(10);
        $top_categories = $this->get_top_artwork_categories();
        $recent_transactions = $this->get_recent_transactions(15);
        $tola_stats = $this->get_tola_statistics();
        $monthly_activity = $this->get_monthly_activity();
        
        wp_send_json_success(array(
            'total_artworks' => $total_artworks,
            'total_artists' => $total_artists,
            'total_swaps' => $total_swaps,
            'top_artists' => $top_artists,
            'top_categories' => $top_categories,
            'recent_transactions' => $recent_transactions,
            'tola_stats' => $tola_stats,
            'monthly_activity' => $monthly_activity
        ));
    }
    
    /**
     * Get total number of verified artworks
     *
     * @return int Total verified artworks
     */
    private function get_total_verified_artworks() {
        global $wpdb;
        
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} 
                WHERE meta_key = %s AND meta_value = %s",
                'vortex_artwork_verified',
                'yes'
            )
        );
        
        return intval($count);
    }
    
    /**
     * Get total number of verified artists
     *
     * @return int Total verified artists
     */
    private function get_total_verified_artists() {
        global $wpdb;
        
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->usermeta} 
                WHERE meta_key = %s AND meta_value = %s",
                'vortex_artist_verified',
                'yes'
            )
        );
        
        return intval($count);
    }
    
    /**
     * Get total number of completed swaps
     *
     * @return int Total completed swaps
     */
    private function get_total_completed_swaps() {
        global $wpdb;
        
        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_artwork_swaps 
                WHERE status = %s",
                'completed'
            )
        );
        
        return intval($count);
    }
    
    /**
     * Get top swapped artists
     *
     * @param int $limit Number of artists to return
     * @return array Top swapped artists
     */
    private function get_top_swapped_artists($limit = 10) {
        global $wpdb;
        
        $artists = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                    p1.post_author as artist_id, 
                    COUNT(*) as swap_count,
                    u.display_name as artist_name
                FROM {$wpdb->prefix}vortex_artwork_swaps as s
                JOIN {$wpdb->posts} as p1 ON s.initiator_artwork_id = p1.ID
                JOIN {$wpdb->posts} as p2 ON s.recipient_artwork_id = p2.ID
                JOIN {$wpdb->users} as u ON p1.post_author = u.ID
                WHERE s.status = 'completed'
                GROUP BY p1.post_author
                ORDER BY swap_count DESC
                LIMIT %d",
                $limit
            ),
            ARRAY_A
        );
        
        return $artists;
    }
    
    /**
     * Get top artwork categories
     *
     * @return array Top artwork categories
     */
    private function get_top_artwork_categories() {
        global $wpdb;
        
        $categories = $wpdb->get_results(
            "SELECT 
                t.name as category_name,
                COUNT(*) as artwork_count
            FROM {$wpdb->posts} as p
            JOIN {$wpdb->term_relationships} as tr ON p.ID = tr.object_id
            JOIN {$wpdb->term_taxonomy} as tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            JOIN {$wpdb->terms} as t ON tt.term_id = t.term_id
            JOIN {$wpdb->postmeta} as pm ON p.ID = pm.post_id
            WHERE 
                p.post_type = 'vortex_artwork' AND
                tt.taxonomy = 'artwork_category' AND
                pm.meta_key = 'vortex_artwork_verified' AND
                pm.meta_value = 'yes'
            GROUP BY t.term_id
            ORDER BY artwork_count DESC
            LIMIT 10",
            ARRAY_A
        );
        
        return $categories;
    }
    
    /**
     * Get recent blockchain transactions
     *
     * @param int $limit Number of transactions to return
     * @return array Recent transactions
     */
    private function get_recent_transactions($limit = 15) {
        global $wpdb;
        
        $transactions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                    swap_id,
                    meta_value as transaction_hash,
                    post_modified as transaction_date
                FROM {$wpdb->postmeta} as pm
                JOIN {$wpdb->posts} as p ON pm.post_id = p.ID
                WHERE meta_key = 'vortex_swap_transaction'
                ORDER BY post_modified DESC
                LIMIT %d",
                $limit
            ),
            ARRAY_A
        );
        
        return $transactions;
    }
    
    /**
     * Get TOLA token statistics
     *
     * @return array TOLA statistics
     */
    private function get_tola_statistics() {
        global $wpdb;
        
        // Total TOLA in circulation
        $total_tola = $wpdb->get_var(
            "SELECT SUM(meta_value) FROM {$wpdb->usermeta} WHERE meta_key = 'vortex_tola_balance'"
        );
        
        // Total transactions using TOLA
        $total_transactions = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_tola_transactions"
        );
        
        // Average TOLA per user
        $average_tola = $wpdb->get_var(
            "SELECT AVG(meta_value) FROM {$wpdb->usermeta} WHERE meta_key = 'vortex_tola_balance' AND meta_value > 0"
        );
        
        return array(
            'total_tola' => floatval($total_tola),
            'total_transactions' => intval($total_transactions),
            'average_tola' => floatval($average_tola)
        );
    }
    
    /**
     * Get monthly activity data
     *
     * @return array Monthly activity data
     */
    private function get_monthly_activity() {
        global $wpdb;
        
        // Get data for the last 12 months
        $months = array();
        for ($i = 0; $i < 12; $i++) {
            $month = date('Y-m', strtotime("-$i months"));
            $months[] = $month;
        }
        
        $data = array();
        foreach ($months as $month) {
            $start_date = $month . '-01 00:00:00';
            $end_date = date('Y-m-t 23:59:59', strtotime($start_date));
            
            // Get new artworks count
            $new_artworks = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->postmeta} as pm
                    JOIN {$wpdb->posts} as p ON pm.post_id = p.ID
                    WHERE 
                        pm.meta_key = 'vortex_artwork_verified' AND
                        pm.meta_value = 'yes' AND
                        p.post_date BETWEEN %s AND %s",
                    $start_date,
                    $end_date
                )
            );
            
            // Get completed swaps count
            $completed_swaps = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_artwork_swaps
                    WHERE 
                        status = 'completed' AND
                        completed_date BETWEEN %s AND %s",
                    $start_date,
                    $end_date
                )
            );
            
            $data[] = array(
                'month' => date('M Y', strtotime($month)),
                'new_artworks' => intval($new_artworks),
                'completed_swaps' => intval($completed_swaps)
            );
        }
        
        // Reverse to get chronological order
        return array_reverse($data);
    }
}

// Initialize the metrics system
new Vortex_Blockchain_Metrics(); 