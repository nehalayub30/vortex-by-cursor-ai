<?php
/**
 * Core Database Operations
 *
 * Base database handling class for the VORTEX AI Marketplace
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/database
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Core Database Operations Class
 *
 * Provides base functionality for database operations in the VORTEX AI Marketplace.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/database
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_DB {

    /**
     * Database version.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $db_version Current database schema version.
     */
    protected $db_version;

    /**
     * Plugin base name.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $plugin_name Base name for table prefixes.
     */
    protected $plugin_name;

    /**
     * Schema directory path.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $schema_dir Path to schema directory.
     */
    protected $schema_dir;

    /**
     * Tables registry.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array $tables Registry of table names without prefix.
     */
    protected $tables = array();

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string $plugin_name The name of this plugin.
     * @param    string $db_version The database version.
     */
    public function __construct($plugin_name, $db_version) {
        global $wpdb;

        $this->plugin_name = $plugin_name;
        $this->db_version = $db_version;
        $this->schema_dir = plugin_dir_path(dirname(__FILE__)) . 'database/schemas/';

        // Register core tables
        $this->tables = array(
            'artworks' => 'vortex_artworks',
            'artists' => 'vortex_artists',
            'artwork_stats' => 'vortex_artwork_stats',
            'artist_stats' => 'vortex_artist_stats',
            'token_transactions' => 'vortex_token_transactions',
            'metrics' => 'vortex_metrics',
            'rankings' => 'vortex_rankings',
            'language_preferences' => 'vortex_language_preferences',
            'sales' => 'vortex_sales',
            'orders' => 'vortex_orders',
            'order_items' => 'vortex_order_items',
        );

        // Initialize database if needed
        $this->maybe_initialize_db();
    }

    /**
     * Initialize database if not already set up.
     *
     * @since    1.0.0
     * @access   private
     */
    private function maybe_initialize_db() {
        $installed_version = get_option($this->plugin_name . '_db_version');

        if ($installed_version !== $this->db_version) {
            $this->create_tables();
            update_option($this->plugin_name . '_db_version', $this->db_version);
        }
    }

    /**
     * Create database tables.
     *
     * @since    1.0.0
     * @access   public
     * @return   boolean True on success, false on failure.
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $success = true;

        // Get dbDelta function for table creation
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Load and execute all schema files
        $schema_files = glob($this->schema_dir . '*.sql');
        
        foreach ($schema_files as $schema_file) {
            if (file_exists($schema_file)) {
                $sql = file_get_contents($schema_file);
                
                // Replace placeholders
                $sql = str_replace('{$charset_collate}', $charset_collate, $sql);
                $sql = str_replace('{$wpdb->prefix}', $wpdb->prefix, $sql);
                
                // Execute schema
                $result = dbDelta($sql);
                
                if (empty($result)) {
                    $success = false;
                    error_log('Failed to create table from schema: ' . basename($schema_file));
                }
            }
        }
        
        return $success;
    }

    /**
     * Get table name with WordPress prefix.
     *
     * @since    1.0.0
     * @access   public
     * @param    string $table_key The table key from the tables registry.
     * @return   string The prefixed table name.
     */
    public function get_table_name($table_key) {
        global $wpdb;
        
        if (!isset($this->tables[$table_key])) {
            return false;
        }
        
        return $wpdb->prefix . $this->tables[$table_key];
    }

    /**
     * Insert data into a table.
     *
     * @since    1.0.0
     * @access   public
     * @param    string $table_key The table key from the tables registry.
     * @param    array  $data The data to insert (column => value).
     * @param    array  $format Optional. An array of formats to be mapped to each value in $data.
     * @return   mixed The number of rows inserted, or false on error.
     */
    public function insert($table_key, $data, $format = null) {
        global $wpdb;
        
        $table_name = $this->get_table_name($table_key);
        
        if (!$table_name) {
            return false;
        }
        
        return $wpdb->insert($table_name, $data, $format);
    }

    /**
     * Update data in a table.
     *
     * @since    1.0.0
     * @access   public
     * @param    string $table_key The table key from the tables registry.
     * @param    array  $data The data to update (column => value).
     * @param    array  $where A named array of WHERE clauses (column => value).
     * @param    array  $format Optional. An array of formats to be mapped to each value in $data.
     * @param    array  $where_format Optional. An array of formats to be mapped to each value in $where.
     * @return   mixed The number of rows updated, or false on error.
     */
    public function update($table_key, $data, $where, $format = null, $where_format = null) {
        global $wpdb;
        
        $table_name = $this->get_table_name($table_key);
        
        if (!$table_name) {
            return false;
        }
        
        return $wpdb->update($table_name, $data, $where, $format, $where_format);
    }

    /**
     * Delete data from a table.
     *
     * @since    1.0.0
     * @access   public
     * @param    string $table_key The table key from the tables registry.
     * @param    array  $where A named array of WHERE clauses (column => value).
     * @param    array  $where_format Optional. An array of formats to be mapped to each value in $where.
     * @return   mixed The number of rows deleted, or false on error.
     */
    public function delete($table_key, $where, $where_format = null) {
        global $wpdb;
        
        $table_name = $this->get_table_name($table_key);
        
        if (!$table_name) {
            return false;
        }
        
        return $wpdb->delete($table_name, $where, $where_format);
    }

    /**
     * Get results from a table.
     *
     * @since    1.0.0
     * @access   public
     * @param    string $table_key The table key from the tables registry.
     * @param    string $query_args Optional. The query arguments.
     * @param    string $output Optional. Any of ARRAY_A | ARRAY_N | OBJECT | OBJECT_K constants.
     * @return   mixed Database query results.
     */
    public function get_results($table_key, $query_args = '', $output = OBJECT) {
        global $wpdb;
        
        $table_name = $this->get_table_name($table_key);
        
        if (!$table_name) {
            return false;
        }
        
        $sql = "SELECT * FROM {$table_name}";
        
        if (!empty($query_args)) {
            $sql .= " {$query_args}";
        }
        
        return $wpdb->get_results($sql, $output);
    }

    /**
     * Get a single row from a table.
     *
     * @since    1.0.0
     * @access   public
     * @param    string $table_key The table key from the tables registry.
     * @param    int|string $row_id The row ID or identifier.
     * @param    string $id_column Optional. The column name for the identifier.
     * @param    string $output Optional. Any of ARRAY_A | ARRAY_N | OBJECT | OBJECT_K constants.
     * @return   mixed Database query result.
     */
    public function get_row($table_key, $row_id, $id_column = 'id', $output = OBJECT) {
        global $wpdb;
        
        $table_name = $this->get_table_name($table_key);
        
        if (!$table_name) {
            return false;
        }
        
        $sql = $wpdb->prepare("SELECT * FROM {$table_name} WHERE {$id_column} = %s", $row_id);
        
        return $wpdb->get_row($sql, $output);
    }

    /**
     * Get a single column from a table.
     *
     * @since    1.0.0
     * @access   public
     * @param    string $table_key The table key from the tables registry.
     * @param    string $column The column name to retrieve.
     * @param    string $query_args Optional. The query arguments.
     * @return   array Database query results.
     */
    public function get_col($table_key, $column, $query_args = '') {
        global $wpdb;
        
        $table_name = $this->get_table_name($table_key);
        
        if (!$table_name) {
            return false;
        }
        
        $sql = "SELECT {$column} FROM {$table_name}";
        
        if (!empty($query_args)) {
            $sql .= " {$query_args}";
        }
        
        return $wpdb->get_col($sql);
    }

    /**
     * Get a single variable from a table.
     *
     * @since    1.0.0
     * @access   public
     * @param    string $table_key The table key from the tables registry.
     * @param    string $column The column name to retrieve.
     * @param    string $query_args Optional. The query arguments.
     * @return   mixed Database query result.
     */
    public function get_var($table_key, $column, $query_args = '') {
        global $wpdb;
        
        $table_name = $this->get_table_name($table_key);
        
        if (!$table_name) {
            return false;
        }
        
        $sql = "SELECT {$column} FROM {$table_name}";
        
        if (!empty($query_args)) {
            $sql .= " {$query_args}";
        }
        
        return $wpdb->get_var($sql);
    }

    /**
     * Execute a custom SQL query.
     *
     * @since    1.0.0
     * @access   public
     * @param    string $sql The SQL query to execute.
     * @return   mixed Query results.
     */
    public function query($sql) {
        global $wpdb;
        
        return $wpdb->query($sql);
    }

    /**
     * Prepare a SQL query for safe execution.
     *
     * @since    1.0.0
     * @access   public
     * @param    string $query The query to prepare.
     * @param    mixed  $args Query arguments.
     * @return   string The prepared query.
     */
    public function prepare($query, ...$args) {
        global $wpdb;
        
        return $wpdb->prepare($query, ...$args);
    }

    /**
     * Get the last error message.
     *
     * @since    1.0.0
     * @access   public
     * @return   string The last error message.
     */
    public function last_error() {
        global $wpdb;
        
        return $wpdb->last_error;
    }

    /**
     * Get the last inserted ID.
     *
     * @since    1.0.0
     * @access   public
     * @return   int The last inserted ID.
     */
    public function last_insert_id() {
        global $wpdb;
        
        return $wpdb->insert_id;
    }

    /**
     * Check if a table exists.
     *
     * @since    1.0.0
     * @access   public
     * @param    string $table_key The table key from the tables registry.
     * @return   boolean True if table exists, false otherwise.
     */
    public function table_exists($table_key) {
        global $wpdb;
        
        $table_name = $this->get_table_name($table_key);
        
        if (!$table_name) {
            return false;
        }
        
        return $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") === $table_name;
    }

    /**
     * Repair a table.
     *
     * @since    1.0.0
     * @access   public
     * @param    string $table_key The table key from the tables registry.
     * @return   boolean True on success, false on failure.
     */
    public function repair_table($table_key) {
        global $wpdb;
        
        $table_name = $this->get_table_name($table_key);
        
        if (!$table_name) {
            return false;
        }
        
        return $wpdb->query("REPAIR TABLE {$table_name}") !== false;
    }

    /**
     * Optimize a table.
     *
     * @since    1.0.0
     * @access   public
     * @param    string $table_key The table key from the tables registry.
     * @return   boolean True on success, false on failure.
     */
    public function optimize_table($table_key) {
        global $wpdb;
        
        $table_name = $this->get_table_name($table_key);
        
        if (!$table_name) {
            return false;
        }
        
        return $wpdb->query("OPTIMIZE TABLE {$table_name}") !== false;
    }

    /**
     * Truncate a table.
     *
     * @since    1.0.0
     * @access   public
     * @param    string $table_key The table key from the tables registry.
     * @return   boolean True on success, false on failure.
     */
    public function truncate_table($table_key) {
        global $wpdb;
        
        $table_name = $this->get_table_name($table_key);
        
        if (!$table_name) {
            return false;
        }
        
        return $wpdb->query("TRUNCATE TABLE {$table_name}") !== false;
    }

    /**
     * Drop a table.
     *
     * @since    1.0.0
     * @access   public
     * @param    string $table_key The table key from the tables registry.
     * @return   boolean True on success, false on failure.
     */
    public function drop_table($table_key) {
        global $wpdb;
        
        $table_name = $this->get_table_name($table_key);
        
        if (!$table_name) {
            return false;
        }
        
        return $wpdb->query("DROP TABLE IF EXISTS {$table_name}") !== false;
    }
} 