<?php
/**
 * Database management for VORTEX AI Marketplace
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

/**
 * Database management class.
 *
 * Handles database initialization, updates, and schema management.
 *
 * @since      1.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_DB {

    /**
     * The current schema version.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $schema_version    The current schema version.
     */
    private $schema_version = '1.0.0';

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Load schema files
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/schema/core-schema.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/schema/tola-schema.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/schema/metrics-schema.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/schema/rankings-schema.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/schema/language-schema.php';
    }

    /**
     * Initialize the database when plugin is activated.
     *
     * @since    1.0.0
     * @return   array    Results of database initialization.
     */
    public function initialize_database() {
        $results = array();
        
        // Create core tables
        $core_results = vortex_create_core_schema();
        $results = array_merge($results, $core_results);
        
        // Create TOLA token tables
        $tola_results = vortex_create_tola_schema();
        $results = array_merge($results, $tola_results);
        
        // Create metrics tables
        $metrics_results = vortex_create_metrics_schema();
        $results = array_merge($results, $metrics_results);
        
        // Create rankings tables
        $rankings_results = vortex_create_rankings_schema();
        $results = array_merge($results, $rankings_results);
        
        // Create language tables
        $language_results = vortex_create_language_schema();
        $results = array_merge($results, $language_results);
        
        // Setup initial data
        $this->setup_initial_data();
        
        // Store schema version
        update_option('vortex_db_version', $this->schema_version);
        
        return $results;
    }

    /**
     * Check if database needs to be updated.
     *
     * @since    1.0.0
     * @return   boolean    True if database needs update.
     */
    public function needs_update() {
        $current_version = get_option('vortex_db_version', '0.0.0');
        return version_compare($current_version, $this->schema_version, '<');
    }

    /**
     * Update database to the current schema version.
     *
     * @since    1.0.0
     * @return   array    Results of database update.
     */
    public function update_database() {
        $current_version = get_option('vortex_db_version', '0.0.0');
        $results = array();
        
        // Run update process based on version
        if (version_compare($current_version, '1.0.0', '<')) {
            // Initial setup is the same as initialization
            $results = $this->initialize_database();
        }
        
        // Update the stored schema version
        update_option('vortex_db_version', $this->schema_version);
        
        return $results;
    }

    /**
     * Setup initial data for all tables.
     *
     * @since    1.0.0
     */
    private function setup_initial_data() {
        vortex_setup_core_initial_data();
        vortex_setup_tola_initial_data();
        vortex_setup_metrics_initial_data();
        vortex_setup_rankings_initial_data();
        vortex_setup_language_initial_data();
    }

    /**
     * Check if required tables exist.
     *
     * @since    1.0.0
     * @return   boolean    True if all required tables exist.
     */
    public function check_tables_exist() {
        global $wpdb;
        
        // Core tables that must exist
        $required_tables = array(
            $wpdb->prefix . 'vortex_artists',
            $wpdb->prefix . 'vortex_artworks',
            $wpdb->prefix . 'vortex_sales',
            $wpdb->prefix . 'vortex_settings'
        );
        
        foreach ($required_tables as $table) {
            $table_exists = $wpdb->get_var($wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $table
            ));
            
            if (!$table_exists) {
                return false;
            }
        }
        
        return true;
    }
} 