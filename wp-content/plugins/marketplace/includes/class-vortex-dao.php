/**
 * Initialize WooCommerce integration.
 */
public function init_woocommerce_integration() {
    // Only load if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        return;
    }
    
    // Load dependencies
    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/db/class-vortex-dao-woocommerce-db.php';
    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/integrations/class-vortex-dao-woocommerce.php';
    
    // Register assets
    add_action('wp_enqueue_scripts', [$this, 'register_woocommerce_assets']);
    
    // Check if tables need to be created (on plugin activation)
    if (get_option('vortex_dao_woocommerce_db_version', '') !== VORTEX_VERSION) {
        VORTEX_DAO_WooCommerce_DB::create_tables();
        VORTEX_DAO_WooCommerce_DB::create_sample_achievements();
        update_option('vortex_dao_woocommerce_db_version', VORTEX_VERSION);
    }
}

/**
 * Register WooCommerce integration assets.
 */
public function register_woocommerce_assets() {
    wp_enqueue_style(
        'vortex-dao-woocommerce',
        plugin_dir_url(dirname(__FILE__)) . 'assets/css/vortex-dao-woocommerce.css',
        [],
        VORTEX_VERSION
    );
    
    // Only load on specific WooCommerce pages
    if (is_product() || is_account_page()) {
        wp_enqueue_script(
            'vortex-dao-woocommerce',
            plugin_dir_url(dirname(__FILE__)) . 'assets/js/vortex-dao-woocommerce.js',
            ['jquery'],
            VORTEX_VERSION,
            true
        );
        
        wp_localize_script('vortex-dao-woocommerce', 'vortexDAOWooCommerce', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_dao_woocommerce_nonce')
        ]);
    }
}

/**
 * Load admin dependencies.
 */
private function load_admin_dependencies() {
    // Other admin dependencies...
    
    // WooCommerce admin integration
    if (class_exists('WooCommerce')) {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/admin/class-vortex-dao-woocommerce-admin.php';
    }
}

/**
 * Register admin assets.
 */
public function register_admin_assets() {
    // Other admin assets...
    
    // WooCommerce admin assets
    if (isset($_GET['page']) && $_GET['page'] === 'vortex-dao-woocommerce') {
        wp_enqueue_style(
            'vortex-dao-woocommerce-admin',
            plugin_dir_url(dirname(__FILE__)) . 'assets/css/vortex-dao-woocommerce-admin.css',
            [],
            VORTEX_VERSION
        );
        
        // Add Chosen for enhanced select fields
        wp_enqueue_style('wp-jquery-ui-dialog');
        wp_enqueue_script('jquery-ui-dialog');
        wp_enqueue_script('jquery-ui-progressbar');
        
        wp_enqueue_style(
            'vortex-chosen',
            plugin_dir_url(dirname(__FILE__)) . 'assets/vendors/chosen/chosen.min.css',
            [],
            '1.8.7'
        );
        
        wp_enqueue_script(
            'vortex-chosen',
            plugin_dir_url(dirname(__FILE__)) . 'assets/vendors/chosen/chosen.jquery.min.js',
            ['jquery'],
            '1.8.7',
            true
        );
    }
} 