<?php
/**
 * VORTEX Commercial Feature Integrations
 * 
 * Handles integration of commercial features with the core VORTEX platform
 *
 * @package   VORTEX_Marketplace
 * @author    VORTEX Development Team
 * @license   GPL-2.0+
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class VORTEX_Integrations {
    /**
     * Instance of this class.
     */
    protected static $instance = null;
    
    /**
     * Commercial features
     */
    private $features = array();
    
    /**
     * Constructor
     */
    private function __construct() {
        // Initialize commercial features
        $this->initialize_features();
        
        // Register hooks
        add_action('init', array($this, 'load_features'), 15);
        add_action('vortex_dashboard_tabs', array($this, 'add_dashboard_tabs'), 20);
        add_filter('vortex_admin_menus', array($this, 'add_admin_menus'), 20);
    }
    
    /**
     * Return an instance of this class.
     */
    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        
        return self::$instance;
    }
    
    /**
     * Initialize commercial features
     */
    private function initialize_features() {
        // Define available features
        $this->features = array(
            'creator_economy' => array(
                'name' => 'Creator Economy System',
                'file' => 'commercial/class-vortex-creator-economy.php',
                'class' => 'VORTEX_Creator_Economy',
                'enabled' => true,
                'dashboard_tab' => true,
                'admin_menu' => true
            ),
            'predictive_pricing' => array(
                'name' => 'Predictive Pricing System',
                'file' => 'commercial/class-vortex-predictive-pricing.php',
                'class' => 'VORTEX_Predictive_Pricing',
                'enabled' => true,
                'dashboard_tab' => true,
                'admin_menu' => true
            )
        );
    }
    
    /**
     * Load enabled features
     */
    public function load_features() {
        foreach ($this->features as $key => $feature) {
            if (!$feature['enabled']) {
                continue;
            }
            
            // Include the feature file
            $file_path = plugin_dir_path(dirname(__FILE__)) . 'includes/' . $feature['file'];
            
            if (file_exists($file_path)) {
                require_once $file_path;
                
                // Initialize the feature if class exists
                if (class_exists($feature['class'])) {
                    call_user_func(array($feature['class'], 'get_instance'));
                }
            }
        }
    }
    
    /**
     * Add dashboard tabs for commercial features
     */
    public function add_dashboard_tabs($tabs) {
        foreach ($this->features as $key => $feature) {
            if ($feature['enabled'] && $feature['dashboard_tab']) {
                $tabs[$key] = array(
                    'title' => $feature['name'],
                    'callback' => array($this, 'render_' . $key . '_tab')
                );
            }
        }
        
        return $tabs;
    }
    
    /**
     * Add admin menus for commercial features
     */
    public function add_admin_menus($menus) {
        foreach ($this->features as $key => $feature) {
            if ($feature['enabled'] && $feature['admin_menu']) {
                // Admin menus are handled by individual feature classes
            }
        }
        
        return $menus;
    }
    
    /**
     * Render Creator Economy tab
     */
    public function render_creator_economy_tab() {
        if (class_exists('VORTEX_Creator_Economy')) {
            $creator_economy = VORTEX_Creator_Economy::get_instance();
            $creator_economy->render_creator_economy_panel();
        }
    }
    
    /**
     * Render Predictive Pricing tab
     */
    public function render_predictive_pricing_tab() {
        if (class_exists('VORTEX_Predictive_Pricing')) {
            $predictive_pricing = VORTEX_Predictive_Pricing::get_instance();
            $predictive_pricing->render_price_prediction_panel();
        }
    }
} 