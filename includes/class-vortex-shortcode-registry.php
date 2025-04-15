/**
 * Register blockchain shortcodes
 */
private function register_blockchain_shortcodes() {
    // Register blockchain metrics shortcode
    add_shortcode('vortex_blockchain_metrics', array($this, 'render_blockchain_metrics'));
    
    // Register expanded blockchain metrics shortcode with real-time data
    add_shortcode('vortex_expanded_blockchain_metrics', array($this, 'render_expanded_blockchain_metrics'));
    
    // Register other blockchain shortcodes
    add_shortcode('vortex_blockchain_status', array($this, 'render_blockchain_status'));
    add_shortcode('vortex_token_balance', array($this, 'render_token_balance'));
    add_shortcode('vortex_nft_showcase', array($this, 'render_nft_showcase'));
}

/**
 * Render expanded blockchain metrics
 * 
 * @param array $atts Shortcode attributes
 * @return string Rendered shortcode content
 */
public function render_expanded_blockchain_metrics($atts = array()) {
    // Include blockchain shortcodes class if needed
    if (!class_exists('Vortex_Blockchain_Shortcodes')) {
        require_once plugin_dir_path(__FILE__) . 'shortcodes/class-vortex-blockchain-shortcodes.php';
    }
    
    // Create instance
    $blockchain_shortcodes = new Vortex_Blockchain_Shortcodes();
    
    // Check if the method exists
    if (method_exists($blockchain_shortcodes, 'render_expanded_blockchain_metrics')) {
        return $blockchain_shortcodes->render_expanded_blockchain_metrics($atts);
    }
    
    // Fallback to regular blockchain metrics
    return $this->render_blockchain_metrics($atts);
} 