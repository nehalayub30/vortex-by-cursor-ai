/**
 * Register scripts for the public-facing side of the site.
 */
public function register_scripts() {
    // Register Chart.js library
    wp_register_script(
        'chartjs',
        'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
        array(),
        '3.9.1',
        true
    );
    
    // Register Solana Web3 library
    wp_register_script(
        'solana-web3',
        'https://unpkg.com/@solana/web3.js@latest/lib/index.iife.min.js',
        array(),
        '1.0.0',
        true
    );
    
    // Register wallet connection script
    wp_register_script(
        'vortex-wallet-connection',
        plugin_dir_url(dirname(__FILE__)) . 'assets/js/vortex-wallet-connection.js',
        array('jquery', 'solana-web3'),
        $this->version,
        true
    );
    
    // Register governance script
    wp_register_script(
        'vortex-governance',
        plugin_dir_url(dirname(__FILE__)) . 'assets/js/vortex-governance.js',
        array('jquery', 'vortex-wallet-connection'),
        $this->version,
        true
    );
    
    // Register metrics visualization script
    wp_register_script(
        'vortex-metrics',
        plugin_dir_url(dirname(__FILE__)) . 'assets/js/vortex-metrics.js',
        array('jquery', 'chartjs'),
        $this->version,
        true
    );
    
    // Localize script with necessary data
    wp_localize_script('vortex-wallet-connection', 'vortexParams', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('vortex_dao_nonce'),
        'isLoggedIn' => is_user_logged_in(),
        'network' => get_option('vortex_dao_blockchain_network', 'devnet'),
        'autoConnect' => (bool) get_option('vortex_dao_auto_connect_wallet', false)
    ));
}

/**
 * Enqueue the scripts on the public-facing pages.
 */
public function enqueue_scripts() {
    // Enqueue scripts on DAO pages
    if (is_page('dao') || is_page('governance') || is_page('proposals') || is_page('metrics')) {
        wp_enqueue_script('vortex-wallet-connection');
        wp_enqueue_script('vortex-governance');
        wp_enqueue_script('vortex-metrics');
    }
} 