<?php
/**
 * VORTEX CLOE Search Stats
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class to handle CLOE search statistics in a more database-friendly way
 */
class Vortex_CLOE_Search_Stats {
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Get instance of this class
     *
     * @return Vortex_CLOE_Search_Stats
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
        add_action('plugins_loaded', array($this, 'init'), 30);
    }
    
    /**
     * Initialize the hooks
     */
    public function init() {
        // Hook into the VORTEX_CLOE class methods
        add_filter('vortex_trending_search_terms', array($this, 'get_trending_search_terms'), 10, 2);
        
        // If we're in admin, add hooks for the admin interface
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
        }
    }
    
    /**
     * Get trending search terms using the stats table/view
     * 
     * @param array $original_results Original results (ignored)
     * @param string $period Time period for analysis
     * @return array Trending search terms data
     */
    public function get_trending_search_terms($original_results, $period = 'month') {
        global $wpdb;
        
        try {
            // Check if we're using the stats table or view
            $using_stats = get_option('vortex_use_search_term_stats', false);
            
            if (!$using_stats) {
                // Fall back to original results if not using stats
                return $original_results;
            }
            
            // Determine the table/view name to use
            $view_exists = false;
            $view_name = $wpdb->prefix . 'vortex_search_term_stats_view';
            $table_name = $wpdb->prefix . 'vortex_search_term_stats';
            
            // Check if view exists
            $view_exists = $wpdb->get_var("SHOW TABLES LIKE '$view_name'") === $view_name;
            
            // If view doesn't exist, check if table exists
            if (!$view_exists) {
                $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
                
                if (!$table_exists) {
                    // Neither view nor table exists, fall back to original results
                    error_log('VORTEX CLOE: Neither search stats view nor table exists.');
                    return $original_results;
                }
                
                // Use the table
                $stats_source = $table_name;
            } else {
                // Use the view
                $stats_source = $view_name;
            }
            
            // Get trending search terms (highest growth in searches)
            $query = "
                SELECT *
                FROM $stats_source
                WHERE current_period_searches > 5 AND previous_period_searches > 0
                ORDER BY (current_period_searches - previous_period_searches) DESC
                LIMIT 30
            ";
            
            $trending_terms = $wpdb->get_results($query);
            
            // Calculate growth rates and add to results
            $processed_terms = array();
            foreach ($trending_terms as $term) {
                $growth_rate = 0;
                if ($term->previous_period_searches > 0) {
                    $growth_rate = round((($term->current_period_searches - $term->previous_period_searches) / $term->previous_period_searches) * 100, 2);
                }
                
                $processed_terms[] = array(
                    'term' => $term->search_term,
                    'current_searches' => $term->current_period_searches,
                    'previous_searches' => $term->previous_period_searches,
                    'growth_rate' => $growth_rate
                );
            }
            
            // Find new trending terms (not present in previous period)
            $new_query = "
                SELECT search_term, current_period_searches as search_count
                FROM $stats_source
                WHERE current_period_searches > 3 AND previous_period_searches = 0
                ORDER BY current_period_searches DESC
                LIMIT 20
            ";
            
            $new_trending_terms = $wpdb->get_results($new_query);
            
            // Get trending terms by category - still requires a complex query
            // Get current period constraint
            $current_period = date('Y-m-d H:i:s', strtotime('-30 days'));
            
            $searches_table = $wpdb->prefix . 'vortex_searches';
            $category_query = $wpdb->prepare(
                "SELECT 
                    c.category_id,
                    c.category_name,
                    s.search_term,
                    COUNT(*) as search_count
                FROM $searches_table s
                JOIN {$wpdb->prefix}vortex_search_artwork_clicks sac ON s.search_id = sac.search_id
                JOIN {$wpdb->prefix}vortex_artworks a ON sac.artwork_id = a.artwork_id
                JOIN {$wpdb->prefix}vortex_categories c ON a.category_id = c.category_id
                WHERE s.search_time >= %s
                GROUP BY c.category_id, s.search_term
                ORDER BY c.category_name, search_count DESC",
                $current_period
            );
            
            $category_trends = $wpdb->get_results($category_query);
            
            // Process category-specific trending terms
            $trending_by_category = array();
            $current_category = null;
            $category_terms = array();
            $category_name = '';
            
            foreach ($category_trends as $trend) {
                if ($current_category !== $trend->category_id) {
                    // Save previous category terms if they exist
                    if ($current_category !== null && !empty($category_terms)) {
                        $trending_by_category[] = array(
                            'category_id' => $current_category,
                            'category_name' => $category_name,
                            'terms' => $category_terms
                        );
                    }
                    
                    // Start new category
                    $current_category = $trend->category_id;
                    $category_name = $trend->category_name;
                    $category_terms = array();
                }
                
                // Add term to current category (limit to top 5 per category)
                if (count($category_terms) < 5) {
                    $category_terms[] = array(
                        'term' => $trend->search_term,
                        'search_count' => $trend->search_count
                    );
                }
            }
            
            // Add the last category if it exists
            if ($current_category !== null && !empty($category_terms)) {
                $trending_by_category[] = array(
                    'category_id' => $current_category,
                    'category_name' => $category_name,
                    'terms' => $category_terms
                );
            }
            
            return array(
                'trending_terms' => $processed_terms,
                'new_trending_terms' => $new_trending_terms,
                'trending_by_category' => $trending_by_category
            );
        } catch (Exception $e) {
            error_log('Failed to get trending search terms: ' . $e->getMessage());
            return array(
                'trending_terms' => array(),
                'new_trending_terms' => array(),
                'trending_by_category' => array()
            );
        }
    }
    
    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        add_submenu_page(
            'tools.php',
            'VORTEX Search Stats',
            'VORTEX Search Stats',
            'manage_options',
            'vortex-search-stats',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Check if we should update the stats
        if (isset($_POST['vortex_update_search_stats']) && check_admin_referer('vortex_search_stats')) {
            $this->update_search_stats();
            echo '<div class="notice notice-success"><p>Search statistics updated successfully.</p></div>';
        }
        
        global $wpdb;
        
        // Get stats table/view status
        $view_exists = false;
        $view_name = $wpdb->prefix . 'vortex_search_term_stats_view';
        $table_name = $wpdb->prefix . 'vortex_search_term_stats';
        
        // Check if view exists
        $view_exists = $wpdb->get_var("SHOW TABLES LIKE '$view_name'") === $view_name;
        
        // If view doesn't exist, check if table exists
        $table_exists = false;
        if (!$view_exists) {
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        }
        
        // Determine which source to use for stats display
        $stats_source = '';
        $stats_type = '';
        if ($view_exists) {
            $stats_source = $view_name;
            $stats_type = 'view';
        } elseif ($table_exists) {
            $stats_source = $table_name;
            $stats_type = 'table';
        }
        
        // Get using stats status
        $using_stats = get_option('vortex_use_search_term_stats', false);
        
        // Get trending search terms if we have a stats source
        $trending_terms = array();
        if (!empty($stats_source)) {
            $query = "
                SELECT *
                FROM $stats_source
                WHERE current_period_searches > 0
                ORDER BY (current_period_searches - previous_period_searches) DESC
                LIMIT 50
            ";
            
            $trending_terms = $wpdb->get_results($query);
        }
        
        ?>
        <div class="wrap">
            <h1>VORTEX Search Statistics</h1>
            
            <div class="card">
                <h2>Status</h2>
                <table class="widefat">
                    <tr>
                        <th>Using Search Stats for CLOE</th>
                        <td><?php echo $using_stats ? 'Yes' : 'No'; ?></td>
                    </tr>
                    <tr>
                        <th>Stats View Exists</th>
                        <td><?php echo $view_exists ? 'Yes' : 'No'; ?></td>
                    </tr>
                    <tr>
                        <th>Stats Table Exists</th>
                        <td><?php echo $table_exists ? 'Yes' : 'No'; ?></td>
                    </tr>
                    <tr>
                        <th>Current Stats Source</th>
                        <td><?php echo !empty($stats_source) ? esc_html($stats_source) . ' (' . $stats_type . ')' : 'None'; ?></td>
                    </tr>
                </table>
                
                <?php if ($table_exists): ?>
                <form method="post" action="">
                    <?php wp_nonce_field('vortex_search_stats'); ?>
                    <p>
                        <button type="submit" name="vortex_update_search_stats" class="button button-primary">
                            Update Search Statistics
                        </button>
                    </p>
                </form>
                <?php elseif (!$view_exists): ?>
                <p>
                    <a href="<?php echo admin_url('tools.php?page=vortex-sql-fixes'); ?>" class="button button-primary">
                        Create Search Statistics Table/View
                    </a>
                </p>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($trending_terms)): ?>
            <div class="card">
                <h2>Current Trending Search Terms</h2>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Search Term</th>
                            <th>Current Period Searches</th>
                            <th>Previous Period Searches</th>
                            <th>Growth Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trending_terms as $term): 
                            $growth_rate = 0;
                            if ($term->previous_period_searches > 0) {
                                $growth_rate = round((($term->current_period_searches - $term->previous_period_searches) / $term->previous_period_searches) * 100, 2);
                            }
                        ?>
                        <tr>
                            <td><?php echo esc_html($term->search_term); ?></td>
                            <td><?php echo esc_html($term->current_period_searches); ?></td>
                            <td><?php echo esc_html($term->previous_period_searches); ?></td>
                            <td><?php echo $growth_rate; ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Update the search statistics table
     */
    private function update_search_stats() {
        global $wpdb;
        
        // Get the table name
        $stats_table = $wpdb->prefix . 'vortex_search_term_stats';
        $searches_table = $wpdb->prefix . 'vortex_searches';
        
        // Check if the table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$stats_table'") !== $stats_table) {
            error_log('VORTEX: Search term statistics table does not exist.');
            return false;
        }
        
        // Get current date and calculate periods
        $current_date = current_time('mysql');
        $period_days = 30;
        $previous_start = date('Y-m-d H:i:s', strtotime('-' . ($period_days * 2) . ' days', strtotime($current_date)));
        $current_start = date('Y-m-d H:i:s', strtotime('-' . $period_days . ' days', strtotime($current_date)));
        
        // Empty the table
        $wpdb->query("TRUNCATE TABLE $stats_table");
        
        // Repopulate with fresh data
        $populate_sql = "
            INSERT INTO $stats_table (search_term, current_period_searches, previous_period_searches)
            SELECT 
                search_term,
                COUNT(CASE WHEN search_time >= %s THEN 1 ELSE NULL END) as current_period_searches,
                COUNT(CASE WHEN search_time >= %s AND search_time < %s THEN 1 ELSE NULL END) as previous_period_searches
            FROM $searches_table
            WHERE search_time >= %s
            GROUP BY search_term
        ";
        
        $result = $wpdb->query($wpdb->prepare($populate_sql, $current_start, $previous_start, $current_start, $previous_start));
        
        error_log('VORTEX: Search term statistics table updated successfully. Affected rows: ' . $result);
        
        return $result !== false;
    }
}

// Initialize the class
Vortex_CLOE_Search_Stats::get_instance(); 