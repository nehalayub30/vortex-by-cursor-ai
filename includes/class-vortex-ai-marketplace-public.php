/**
 * Register scripts and styles for HURAII advanced features
 */
private function register_huraii_advanced_assets() {
    // Register Three.js for 3D models
    wp_register_script(
        'threejs',
        'https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js',
        array(),
        '128',
        true
    );
    
    // Register GLTFLoader for 3D models
    wp_register_script(
        'gltfloader',
        'https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/GLTFLoader.min.js',
        array('threejs'),
        '0.128.0',
        true
    );
    
    // Register OrbitControls for 3D models
    wp_register_script(
        'orbitcontrols',
        'https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.min.js',
        array('threejs'),
        '0.128.0',
        true
    );
    
    // Register QR Code generator
    wp_register_script(
        'qrcodejs',
        'https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js',
        array(),
        '1.0.0',
        true
    );
    
    // Register HURAII advanced features script
    wp_register_script(
        'vortex-huraii-advanced',
        plugin_dir_url(dirname(__FILE__)) . 'public/js/vortex-huraii-advanced.js',
        array('jquery', 'threejs', 'gltfloader', 'orbitcontrols', 'qrcodejs'),
        '1.0.0',
        true
    );
    
    // Register HURAII advanced features styles
    wp_register_style(
        'vortex-huraii-advanced',
        plugin_dir_url(dirname(__FILE__)) . 'public/css/vortex-huraii-advanced.css',
        array(),
        '1.0.0'
    );
    
    // Register royalty management script
    wp_register_script(
        'vortex-huraii-royalties',
        plugin_dir_url(dirname(__FILE__)) . 'public/js/vortex-huraii-royalties.js',
        array('jquery'),
        '1.0.0',
        true
    );
    
    // Localize royalties script with translations
    wp_localize_script('vortex-huraii-royalties', 'vortex_royalties', array(
        'name_label' => __('Recipient Name:', 'vortex-ai-marketplace'),
        'name_placeholder' => __('Enter recipient name', 'vortex-ai-marketplace'),
        'percentage_label' => __('Percentage:', 'vortex-ai-marketplace'),
        'wallet_label' => __('Solana Wallet Address:', 'vortex-ai-marketplace'),
        'wallet_placeholder' => __('Enter Solana wallet address', 'vortex-ai-marketplace'),
        'role_label' => __('Role:', 'vortex-ai-marketplace'),
        'role_collaborator' => __('Collaborator', 'vortex-ai-marketplace'),
        'role_contributor' => __('Contributor', 'vortex-ai-marketplace'),
        'role_rights_holder' => __('Rights Holder', 'vortex-ai-marketplace')
    ));
}

/**
 * Enqueue scripts and styles for the HURAII interface
 */
public function enqueue_huraii_assets() {
    // Check if we're on a page with the HURAII shortcode
    global $post;
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'vortex_huraii_artwork')) {
        // Basic HURAII assets
        wp_enqueue_style('vortex-huraii-styles');
        wp_enqueue_script('vortex-huraii-script');
        
        // Advanced HURAII assets
        wp_enqueue_style('vortex-huraii-advanced');
        wp_enqueue_script('vortex-huraii-advanced');
        
        // Royalty management
        wp_enqueue_script('vortex-huraii-royalties');
        
        // Localize the script with AJAX URL and security nonce
        wp_localize_script('vortex-huraii-script', 'vortex_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'huraii_nonce' => wp_create_nonce('vortex_huraii_nonce')
        ));
    }
} 