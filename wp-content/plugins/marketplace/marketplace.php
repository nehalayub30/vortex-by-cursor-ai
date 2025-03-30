<?php
// Include integrations
require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-integrations.php';

// Initialize classes
function vortex_initialize() {
    // ... existing initialization code ...
    
    // Initialize commercial integrations
    $vortex_integrations = VORTEX_Integrations::get_instance();
}
add_action('plugins_loaded', 'vortex_initialize');

// ... rest of existing code ... 