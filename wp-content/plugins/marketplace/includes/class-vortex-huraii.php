<?php
class VORTEX_HURAII {
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->load_dependencies();
        $this->initialize_components();
        $this->register_hooks();
    }
}

// Initialize HURAII
$vortex_huraii = VORTEX_HURAII::get_instance(); 