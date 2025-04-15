<?php
/**
 * VORTEX Database Repair
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class to handle database repair operations
 */
class VORTEX_DB_Repair {
    
    /**
     * Instance of this class.
     */
    protected static $instance = null;
    
    /**
     * List of required tables
     */
    private $required_tables = array(
        'vortex_user_sessions',
        'vortex_user_geo_data',
        'vortex_user_demographics',
        'vortex_user_languages',
        'vortex_artwork_views',
        'vortex_art_styles',
        'vortex_cart_abandonment_feedback',
        'vortex_searches',
        'vortex_carts',
        'vortex_cart_items',
        'vortex_transactions',
        'vortex_tags',
        'vortex_artwork_tags',
        'vortex_artwork_themes',
        'vortex_artwork_theme_mapping',
        'vortex_social_shares',
        'vortex_search_transactions',
        'vortex_search_artwork_clicks',
        'vortex_search_results'
    );
    
    /**
     * Get instance of this class.
     *
     * @return VORTEX_DB_Repair
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
        // Register admin hooks only if needed
        add_action('admin_init', array($this, 'register_admin_hooks'));
    }
    
    /**
     * Register admin hooks
     */
    public function register_admin_hooks() {
        // Only for administrators
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Show admin notice if tables are missing
        $missing_tables = $this->get_missing_tables();
        if (!empty($missing_tables)) {
            add_action('admin_notices', array($this, 'missing_tables_notice'));
            
            // Register page to fix tables
            add_action('admin_menu', array($this, 'add_repair_menu'));
        }
    }
    
    /**
     * Add repair menu
     */
    public function add_repair_menu() {
        add_submenu_page(
            'tools.php',
            __('VORTEX DB Repair', 'vortex-ai-marketplace'),
            __('VORTEX DB Repair', 'vortex-ai-marketplace'),
            'manage_options',
            'vortex-db-repair',
            array($this, 'render_repair_page')
        );
    }
    
    /**
     * Render repair page
     */
    public function render_repair_page() {
        // Process repair if requested
        $repaired = false;
        $repaired_tables = array();
        
        // Handle full repair
        if (isset($_POST['vortex_repair_tables']) && check_admin_referer('vortex_repair_tables')) {
            $repaired = $this->repair_tables();
        }
        
        // Handle specific table repair
        if (isset($_POST['vortex_repair_specific_table']) && check_admin_referer('vortex_repair_specific_table')) {
            $table_name = isset($_POST['table_name']) ? sanitize_text_field($_POST['table_name']) : '';
            if (!empty($table_name)) {
                $repaired_tables = $this->repair_specific_table($table_name);
                $repaired = !empty($repaired_tables);
            }
        }
        
        // Get current missing tables
        $missing_tables = $this->get_missing_tables();
        
        ?>
        <div class="wrap">
            <h1><?php _e('VORTEX Database Repair', 'vortex-ai-marketplace'); ?></h1>
            
            <?php if ($repaired) : ?>
                <div class="notice notice-success">
                    <p><?php _e('Database tables have been repaired.', 'vortex-ai-marketplace'); ?></p>
                    <?php if (!empty($repaired_tables)) : ?>
                        <p><?php _e('Repaired tables:', 'vortex-ai-marketplace'); ?> <code><?php echo esc_html(implode('</code>, <code>', $repaired_tables)); ?></code></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if (empty($missing_tables)) : ?>
                <div class="notice notice-success">
                    <p><?php _e('All required database tables exist.', 'vortex-ai-marketplace'); ?></p>
                </div>
            <?php else : ?>
                <div class="notice notice-error">
                    <p><?php _e('The following database tables are missing:', 'vortex-ai-marketplace'); ?></p>
                    <ul>
                        <?php foreach ($missing_tables as $table) : ?>
                            <li><code><?php echo esc_html($table); ?></code></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <form method="post" action="">
                    <?php wp_nonce_field('vortex_repair_tables'); ?>
                    <p>
                        <button type="submit" name="vortex_repair_tables" class="button button-primary">
                            <?php _e('Repair Missing Tables', 'vortex-ai-marketplace'); ?>
                        </button>
                    </p>
                </form>
            <?php endif; ?>
            
            <h2><?php _e('Database Table Status', 'vortex-ai-marketplace'); ?></h2>
            
            <table class="widefat" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th><?php _e('Table Name', 'vortex-ai-marketplace'); ?></th>
                        <th><?php _e('Status', 'vortex-ai-marketplace'); ?></th>
                        <th><?php _e('Actions', 'vortex-ai-marketplace'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Add artworks table to the required tables for display
                    $all_tables = array_merge($this->required_tables, array('vortex_artworks'));
                    foreach ($all_tables as $table) : 
                        $exists = !in_array($table, $missing_tables);
                    ?>
                        <tr>
                            <td><code><?php echo esc_html($table); ?></code></td>
                            <td>
                                <?php if ($exists) : ?>
                                    <span style="color: green;"><?php _e('Exists', 'vortex-ai-marketplace'); ?></span>
                                <?php else : ?>
                                    <span style="color: red;"><?php _e('Missing', 'vortex-ai-marketplace'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="post" action="" style="display: inline-block;">
                                    <?php wp_nonce_field('vortex_repair_specific_table'); ?>
                                    <input type="hidden" name="table_name" value="<?php echo esc_attr($table); ?>">
                                    <button type="submit" name="vortex_repair_specific_table" class="button button-small">
                                        <?php _e('Repair This Table', 'vortex-ai-marketplace'); ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    
                    <!-- SQL Reference Issues -->
                    <tr>
                        <td colspan="3"><h3><?php _e('SQL Reference Issues', 'vortex-ai-marketplace'); ?></h3></td>
                    </tr>
                    <tr>
                        <td><code>share_rate_references</code></td>
                        <td>
                            <?php if (get_option('vortex_share_rate_fixed', false)) : ?>
                                <span style="color: green;"><?php _e('Fixed', 'vortex-ai-marketplace'); ?></span>
                            <?php else : ?>
                                <span style="color: orange;"><?php _e('Might need repair', 'vortex-ai-marketplace'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post" action="" style="display: inline-block;">
                                <?php wp_nonce_field('vortex_repair_specific_table'); ?>
                                <input type="hidden" name="table_name" value="share_rate_references">
                                <button type="submit" name="vortex_repair_specific_table" class="button button-small">
                                    <?php _e('Fix SQL References', 'vortex-ai-marketplace'); ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    /**
     * Display missing tables notice
     */
    public function missing_tables_notice() {
        $missing_tables = $this->get_missing_tables();
        if (empty($missing_tables)) {
            return;
        }
        
        ?>
        <div class="notice notice-error">
            <p>
                <strong><?php _e('VORTEX AI Marketplace Database Issue', 'vortex-ai-marketplace'); ?></strong>
            </p>
            <p>
                <?php _e('The following database tables are missing:', 'vortex-ai-marketplace'); ?>
                <code><?php echo esc_html(implode('</code>, <code>', $missing_tables)); ?></code>
            </p>
            <p>
                <a href="<?php echo admin_url('tools.php?page=vortex-db-repair'); ?>" class="button button-primary">
                    <?php _e('Repair Database Tables', 'vortex-ai-marketplace'); ?>
                </a>
            </p>
        </div>
        <?php
    }
    
    /**
     * Get missing tables
     * 
     * @return array List of missing tables
     */
    public function get_missing_tables() {
        global $wpdb;
        $missing_tables = array();
        
        foreach ($this->required_tables as $table) {
            $table_name = $wpdb->prefix . $table;
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
                $missing_tables[] = $table;
            }
        }
        
        return $missing_tables;
    }
    
    /**
     * Repair missing tables
     * 
     * @return bool True if repair was successful
     */
    public function repair_tables() {
        // Require the tables class
        require_once plugin_dir_path(__FILE__) . 'class-vortex-db-tables.php';
        
        // Get DB tables instance
        $db_tables = VORTEX_DB_Tables::get_instance();
        
        // Get missing tables
        $missing_tables = $this->get_missing_tables();
        
        // Create missing tables
        foreach ($missing_tables as $table) {
            $method = 'create_' . $table . '_table';
            if (method_exists($db_tables, $method)) {
                $db_tables->$method();
            }
        }
        
        // Check if tables were created
        $still_missing = $this->get_missing_tables();
        
        // Return true if no tables are missing anymore
        return empty($still_missing);
    }
    
    /**
     * Check if a table exists
     * 
     * @param string $table_name Table name without prefix
     * @return bool True if table exists
     */
    public static function table_exists($table_name) {
        global $wpdb;
        $full_table_name = $wpdb->prefix . $table_name;
        return $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'") === $full_table_name;
    }
    
    /**
     * Repair all tables
     */
    public function repair_all_tables() {
        global $wpdb;
        
        // Get DB tables instance
        require_once plugin_dir_path(__FILE__) . 'class-vortex-db-tables.php';
        $db_tables = VORTEX_DB_Tables::get_instance();
        
        // Create missing tables
        $db_tables->create_all_tables();
        
        // Specifically check and repair the artworks table (for style_id column)
        $db_tables->repair_artworks_table();
        
        // Require the migrations class for specialized repairs
        require_once plugin_dir_path(__FILE__) . 'class-vortex-db-migrations.php';
        
        // Ensure the transactions table has artwork_id column
        Vortex_DB_Migrations::ensure_transactions_table();
        
        // Ensure tags and artwork_tags tables exist
        Vortex_DB_Migrations::ensure_tags_table();
        Vortex_DB_Migrations::ensure_artwork_tags_table();
        
        // Ensure searches table exists
        Vortex_DB_Migrations::ensure_searches_table();
        
        // Ensure search transactions table exists
        Vortex_DB_Migrations::ensure_search_transactions_table();
        
        // Ensure artwork theme tables exist
        Vortex_DB_Migrations::ensure_artwork_themes_table();
        Vortex_DB_Migrations::ensure_artwork_theme_mapping_table();
        
        // Ensure cart abandonment feedback table has required columns
        Vortex_DB_Migrations::ensure_cart_abandonment_reason_column();
        
        // Run the critical tables check
        Vortex_DB_Migrations::ensure_critical_tables();
        
        // Fix SQL reference issues
        if (method_exists($this, 'repair_share_rate_references')) {
            $this->repair_share_rate_references();
        }
        
        // Update the DB version to latest
        update_option('vortex_db_version', '1.6.0');
        
        return true;
    }
    
    /**
     * Repair a specific table
     * 
     * @param string $table_name Table name without prefix
     * @return array Array of repaired tables
     */
    public function repair_specific_table($table_name) {
        $repaired_tables = array();
        
        // Get DB tables instance
        require_once plugin_dir_path(__FILE__) . 'class-vortex-db-tables.php';
        $db_tables = VORTEX_DB_Tables::get_instance();
        
        // Handle special cases
        if ($table_name === 'vortex_artworks') {
            $success = $db_tables->repair_artworks_table();
            if ($success) {
                $repaired_tables[] = $table_name;
            }
            return $repaired_tables;
        }
        
        if ($table_name === 'vortex_transactions') {
            // Handle transactions table specifically
            $success = $this->repair_transactions_table();
            if ($success) {
                $repaired_tables[] = $table_name;
            }
            return $repaired_tables;
        }
        
        if ($table_name === 'vortex_tags') {
            // Require the migrations class
            require_once plugin_dir_path(__FILE__) . 'class-vortex-db-migrations.php';
            $success = Vortex_DB_Migrations::ensure_tags_table();
            if ($success) {
                $repaired_tables[] = $table_name;
            }
            return $repaired_tables;
        }
        
        if ($table_name === 'vortex_artwork_tags') {
            // Require the migrations class
            require_once plugin_dir_path(__FILE__) . 'class-vortex-db-migrations.php';
            $success = Vortex_DB_Migrations::ensure_artwork_tags_table();
            if ($success) {
                $repaired_tables[] = $table_name;
            }
            return $repaired_tables;
        }
        
        if ($table_name === 'vortex_searches') {
            // Handle searches table specifically
            $success = $this->repair_searches_table();
            if ($success) {
                $repaired_tables[] = $table_name;
            }
            return $repaired_tables;
        }
        
        if ($table_name === 'vortex_artwork_themes') {
            // Handle artwork themes table
            $success = $this->repair_artwork_themes_table();
            if ($success) {
                $repaired_tables[] = $table_name;
            }
            return $repaired_tables;
        }
        
        if ($table_name === 'vortex_artwork_theme_mapping') {
            // Handle artwork theme mapping table
            $success = $this->repair_artwork_theme_mapping_table();
            if ($success) {
                $repaired_tables[] = $table_name;
            }
            return $repaired_tables;
        }
        
        if ($table_name === 'vortex_social_shares') {
            // Handle social shares table
            $success = $this->repair_social_shares_table();
            if ($success) {
                $repaired_tables[] = $table_name;
            }
            return $repaired_tables;
        }
        
        if ($table_name === 'vortex_carts') {
            // Handle carts table
            $success = $this->repair_carts_table();
            if ($success) {
                $repaired_tables[] = $table_name;
            }
            return $repaired_tables;
        }
        
        if ($table_name === 'vortex_cart_items') {
            // Handle cart items table
            $success = $this->repair_cart_items_table();
            if ($success) {
                $repaired_tables[] = $table_name;
            }
            return $repaired_tables;
        }
        
        if ($table_name === 'vortex_cart_abandonment_feedback') {
            // Handle cart abandonment feedback table
            $success = $this->repair_cart_abandonment_feedback_table();
            if ($success) {
                $repaired_tables[] = $table_name;
            }
            return $repaired_tables;
        }
        
        if ($table_name === 'vortex_search_transactions') {
            // Handle search transactions table
            $success = $this->repair_search_transactions_table();
            if ($success) {
                $repaired_tables[] = $table_name;
            }
            return $repaired_tables;
        }
        
        if ($table_name === 'vortex_search_artwork_clicks') {
            // Handle search artwork clicks table
            $success = $this->repair_search_artwork_clicks_table();
            if ($success) {
                $repaired_tables[] = $table_name;
            }
            return $repaired_tables;
        }
        
        if ($table_name === 'vortex_search_results') {
            // Handle search results table specifically
            $success = $this->repair_search_results_table();
            if ($success) {
                $repaired_tables[] = $table_name;
            }
            return $repaired_tables;
        }
        
        if ($table_name === 'vortex_social_hashtags') {
            // Handle social hashtags table specifically
            $success = $this->repair_social_hashtags_table();
            if ($success) {
                $repaired_tables[] = $table_name;
            }
            return $repaired_tables;
        }
        
        if ($table_name === 'share_rate_references') {
            // Handle share_rate reference issues specifically
            $success = $this->repair_share_rate_references();
            if ($success) {
                $repaired_tables[] = 'SQL queries with share_rate references';
            }
            return $repaired_tables;
        }
        
        // Handle standard tables
        $method = 'create_' . $table_name . '_table';
        if (method_exists($db_tables, $method)) {
            $db_tables->$method();
            $repaired_tables[] = $table_name;
        }
        
        return $repaired_tables;
    }
    
    /**
     * Repair searches table issues
     * 
     * @return bool Success status
     */
    public function repair_searches_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_searches';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            // Require the migrations class
            require_once plugin_dir_path(__FILE__) . 'class-vortex-db-migrations.php';
            
            // Call the migration method to create the table
            Vortex_DB_Migrations::ensure_searches_table();
            
            // Verify it was created
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            
            // Try another approach if still not created
            if (!$table_exists) {
                require_once plugin_dir_path(__FILE__) . 'class-vortex-db-tables.php';
                $db_tables = VORTEX_DB_Tables::get_instance();
                $db_tables->create_searches_table();
                
                $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            }
            
            if ($table_exists) {
                error_log("Successfully created missing searches table: $table_name");
                return true;
            } else {
                error_log("Failed to create searches table: $table_name");
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Repair artwork themes table
     * 
     * @return bool Success status
     */
    public function repair_artwork_themes_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_artwork_themes';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            // Require the migrations class
            require_once plugin_dir_path(__FILE__) . 'class-vortex-db-migrations.php';
            
            // Call the migration method to create the table
            Vortex_DB_Migrations::ensure_artwork_themes_table();
            
            // Verify it was created
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            
            // Try another approach if still not created
            if (!$table_exists) {
                require_once plugin_dir_path(__FILE__) . 'class-vortex-db-tables.php';
                $db_tables = VORTEX_DB_Tables::get_instance();
                $db_tables->create_artwork_themes_table();
                
                $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            }
            
            if ($table_exists) {
                error_log("Successfully created missing artwork themes table: $table_name");
                return true;
            } else {
                error_log("Failed to create artwork themes table: $table_name");
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Repair artwork theme mapping table
     * 
     * @return bool Success status
     */
    public function repair_artwork_theme_mapping_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_artwork_theme_mapping';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            // First ensure the artwork themes table exists as it's a dependency
            $this->repair_artwork_themes_table();
            
            // Require the migrations class
            require_once plugin_dir_path(__FILE__) . 'class-vortex-db-migrations.php';
            
            // Call the migration method to create the table
            Vortex_DB_Migrations::ensure_artwork_theme_mapping_table();
            
            // Verify it was created
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            
            // Try another approach if still not created
            if (!$table_exists) {
                require_once plugin_dir_path(__FILE__) . 'class-vortex-db-tables.php';
                $db_tables = VORTEX_DB_Tables::get_instance();
                $db_tables->create_artwork_theme_mapping_table();
                
                $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            }
            
            if ($table_exists) {
                error_log("Successfully created missing artwork theme mapping table: $table_name");
                return true;
            } else {
                error_log("Failed to create artwork theme mapping table: $table_name");
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Repair social shares table
     * 
     * @return bool Success status
     */
    public function repair_social_shares_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_social_shares';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            // Require the migrations class
            require_once plugin_dir_path(__FILE__) . 'class-vortex-db-migrations.php';
            
            // Call the migration method to create the table
            if (method_exists('Vortex_DB_Migrations', 'create_social_shares_table')) {
                // Get charset_collate for creating the table
                $charset_collate = $wpdb->get_charset_collate();
                $migration = new Vortex_DB_Migrations();
                $migration->create_social_shares_table($charset_collate);
            } else {
                // Try another approach - use the ensure critical tables method
                Vortex_DB_Migrations::ensure_critical_tables();
            }
            
            // Verify it was created
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            
            if ($table_exists) {
                error_log("Successfully created missing social shares table: $table_name");
                
                // Also ensure click_count column is added
                Vortex_DB_Migrations::add_click_count_to_social_shares();
                
                return true;
            } else {
                error_log("Failed to create social shares table: $table_name");
                return false;
            }
        } else {
            // Table exists, make sure click_count column exists
            Vortex_DB_Migrations::add_click_count_to_social_shares();
        }
        
        return true;
    }
    
    /**
     * Repair transactions table
     * 
     * @return bool Success status
     */
    public function repair_transactions_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_transactions';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            // Require the migrations class
            require_once plugin_dir_path(__FILE__) . 'class-vortex-db-migrations.php';
            
            // Call the migration method to create the table
            $success = Vortex_DB_Migrations::ensure_transactions_table();
            
            if (!$success) {
                error_log("Failed to create transactions table: $table_name");
                return false;
            }
            
            error_log("Successfully created missing transactions table: $table_name");
        }
        
        // Ensure the transaction_time column exists
        Vortex_DB_Migrations::add_transaction_time_to_transactions();
        
        return true;
    }
    
    /**
     * Repair cart items table
     * 
     * @return bool Success status
     */
    public function repair_cart_items_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_cart_items';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            // First ensure the carts table exists as it's a dependency
            $this->repair_carts_table();
            
            // Require the migrations class
            require_once plugin_dir_path(__FILE__) . 'class-vortex-db-migrations.php';
            
            // Call the migration method to create the table
            if (method_exists('Vortex_DB_Migrations', 'ensure_cart_items_table')) {
                Vortex_DB_Migrations::ensure_cart_items_table();
            } else {
                // Use DB tables class as fallback
                require_once plugin_dir_path(__FILE__) . 'class-vortex-db-tables.php';
                $db_tables = VORTEX_DB_Tables::get_instance();
                $db_tables->create_cart_items_table();
            }
            
            // Verify it was created
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            
            if ($table_exists) {
                error_log("Successfully created missing cart items table: $table_name");
                return true;
            } else {
                error_log("Failed to create cart items table: $table_name");
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Repair carts table
     * 
     * @return bool Success status
     */
    public function repair_carts_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_carts';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            // Require the migrations class
            require_once plugin_dir_path(__FILE__) . 'class-vortex-db-migrations.php';
            
            // Call the migration method to create the table
            if (method_exists('Vortex_DB_Migrations', 'ensure_carts_table')) {
                Vortex_DB_Migrations::ensure_carts_table();
            } else {
                // Use DB tables class as fallback
                require_once plugin_dir_path(__FILE__) . 'class-vortex-db-tables.php';
                $db_tables = VORTEX_DB_Tables::get_instance();
                $db_tables->create_carts_table();
            }
            
            // Verify it was created
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            
            if ($table_exists) {
                error_log("Successfully created missing carts table: $table_name");
                return true;
            } else {
                error_log("Failed to create carts table: $table_name");
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Repair cart abandonment feedback table
     * 
     * @return bool Success status
     */
    public function repair_cart_abandonment_feedback_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_cart_abandonment_feedback';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            // Require the DB tables class
            require_once plugin_dir_path(__FILE__) . 'class-vortex-db-tables.php';
            $db_tables = VORTEX_DB_Tables::get_instance();
            $db_tables->create_cart_abandonment_feedback_table();
            
            // Verify it was created
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            
            if ($table_exists) {
                error_log("Successfully created missing cart abandonment feedback table: $table_name");
                return true;
            } else {
                error_log("Failed to create cart abandonment feedback table: $table_name");
                return false;
            }
        }
        
        // If the table exists, make sure it has the abandonment_reason column
        require_once plugin_dir_path(__FILE__) . 'class-vortex-db-migrations.php';
        Vortex_DB_Migrations::ensure_cart_abandonment_reason_column();
        
        return true;
    }
    
    /**
     * Repair search transactions table
     * 
     * @return bool Success status
     */
    public function repair_search_transactions_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_search_transactions';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            // First ensure the searches and transactions tables exist as they're dependencies
            $this->repair_searches_table();
            $this->repair_transactions_table();
            
            // Require the DB tables class
            require_once plugin_dir_path(__FILE__) . 'class-vortex-db-tables.php';
            $db_tables = VORTEX_DB_Tables::get_instance();
            $db_tables->create_search_transactions_table();
            
            // Verify it was created
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            
            if ($table_exists) {
                error_log("Successfully created missing search transactions table: $table_name");
                return true;
            } else {
                error_log("Failed to create search transactions table: $table_name");
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Repair search artwork clicks table
     * 
     * @return bool Success status
     */
    public function repair_search_artwork_clicks_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_search_artwork_clicks';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            // First ensure the searches and artworks tables exist as they're dependencies
            $this->repair_searches_table();
            
            // Check for artworks table method
            if (method_exists($this, 'repair_artworks_table')) {
                $this->repair_artworks_table();
            }
            
            // Require the DB tables class
            require_once plugin_dir_path(__FILE__) . 'class-vortex-db-tables.php';
            $db_tables = VORTEX_DB_Tables::get_instance();
            
            // Create the table
            $db_tables->create_search_artwork_clicks_table();
            
            // Check if table was created
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            
            if ($table_exists) {
                // Set up default data if needed
                // For example, populate from existing searches if applicable
                return true;
            }
            
            return false;
        }
        
        return true;
    }
    
    /**
     * Repair search results table
     * 
     * @return bool Success status
     */
    public function repair_search_results_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_search_results';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            // First ensure the searches table exists as it's a dependency
            $this->repair_searches_table();
            
            // Require the DB tables class
            require_once plugin_dir_path(__FILE__) . 'class-vortex-db-tables.php';
            $db_tables = VORTEX_DB_Tables::get_instance();
            
            // Create the table
            $db_tables->create_search_results_table();
            
            // Check if table was created
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            
            if ($table_exists) {
                return true;
            }
            
            return false;
        }
        
        return true;
    }
    
    /**
     * Repair social hashtags table
     * 
     * @return bool Success status
     */
    public function repair_social_hashtags_table() {
        global $wpdb;
        $hashtags_table = $wpdb->prefix . 'vortex_social_hashtags';
        $mapping_table = $wpdb->prefix . 'vortex_hashtag_share_mapping';
        
        // Check if tables exist
        $hashtags_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$hashtags_table'") === $hashtags_table;
        $mapping_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$mapping_table'") === $mapping_table;
        
        $success = true;
        
        // Create hashtags table if it doesn't exist
        if (!$hashtags_table_exists) {
            // Require the migrations class
            require_once plugin_dir_path(__FILE__) . 'class-vortex-db-migrations.php';
            
            // Create the hashtags table
            $hashtags_success = Vortex_DB_Migrations::ensure_social_hashtags_table();
            
            // Verify it was created
            $hashtags_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$hashtags_table'") === $hashtags_table;
            
            if ($hashtags_table_exists) {
                error_log("Successfully created missing social hashtags table: $hashtags_table");
            } else {
                error_log("Failed to create social hashtags table: $hashtags_table");
                $success = false;
            }
        }
        
        // Create mapping table if it doesn't exist
        if (!$mapping_table_exists) {
            // Require the migrations class if not already loaded
            if (!class_exists('Vortex_DB_Migrations')) {
                require_once plugin_dir_path(__FILE__) . 'class-vortex-db-migrations.php';
            }
            
            // Create the mapping table
            $mapping_success = Vortex_DB_Migrations::ensure_hashtag_share_mapping_table();
            
            // Verify it was created
            $mapping_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$mapping_table'") === $mapping_table;
            
            if ($mapping_table_exists) {
                error_log("Successfully created missing hashtag share mapping table: $mapping_table");
            } else {
                error_log("Failed to create hashtag share mapping table: $mapping_table");
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Repair SQL query issues related to share_rate references
     * 
     * @return bool Success status
     */
    public function repair_share_rate_references() {
        // Check if the fix function exists
        if (!function_exists('vortex_force_repair_share_rate_issue')) {
            // Try to include the file
            $fix_file = plugin_dir_path(dirname(__FILE__)) . 'includes/sql-fixes/fix-share-rate-reference.php';
            if (file_exists($fix_file)) {
                include_once $fix_file;
            }
        }
        
        // Now try to call the fix function
        if (function_exists('vortex_force_repair_share_rate_issue')) {
            $result = vortex_force_repair_share_rate_issue();
            
            if ($result) {
                // Record that we've fixed the issue
                update_option('vortex_share_rate_fixed', true);
                return true;
            }
        }
        
        // If we don't have the special fix function, add a generic filter
        add_filter('query', function($query) {
            // Check if this is a query with the share_rate issue
            if (stripos($query, 'share_rate') !== false && stripos($query, 'HAVING') !== false) {
                // Replace HAVING share_rate > X with the expanded expression
                $query = preg_replace(
                    '/HAVING\s+share_rate\s+>\s+(\d+)/i',
                    'HAVING (COUNT(s.share_id) / COUNT(DISTINCT v.view_id)) * 100 > $1',
                    $query
                );
                
                // Fix ORDER BY share_rate as well
                $query = preg_replace(
                    '/ORDER BY\s+share_rate\s+(ASC|DESC)/i',
                    'ORDER BY (COUNT(s.share_id) / COUNT(DISTINCT v.view_id)) * 100 $1',
                    $query
                );
            }
            
            return $query;
        });
        
        // Mark as fixed
        update_option('vortex_share_rate_fixed', true);
        
        return true;
    }
} 