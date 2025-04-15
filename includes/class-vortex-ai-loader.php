<?php
/**
 * AI Agent Loader
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class that handles loading all AI agent classes
 *
 * @since      1.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */
class VORTEX_AI_Loader {

    /**
     * Initialize and load all AI agent classes
     *
     * @since    1.0.0
     */
    public static function load_agents() {
        $plugin_dir = plugin_dir_path(dirname(__FILE__));
        
        // Load base AI classes
        require_once $plugin_dir . 'includes/ai/class-vortex-huraii.php';
        require_once $plugin_dir . 'includes/ai/class-vortex-cloe.php';
        require_once $plugin_dir . 'includes/ai/class-vortex-business-strategist.php';
        require_once $plugin_dir . 'includes/ai/class-vortex-thorius.php';
        
        // Load utility and adapter classes
        require_once $plugin_dir . 'includes/ai/class-vortex-img2img.php';
        require_once $plugin_dir . 'includes/ai/class-vortex-huraii-image-generator.php';
        require_once $plugin_dir . 'includes/ai/class-vortex-huraii-format-processors.php';
        require_once $plugin_dir . 'includes/ai/class-vortex-thorius-huraii.php';
        require_once $plugin_dir . 'includes/ai/class-vortex-thorius-strategist.php';
        require_once $plugin_dir . 'includes/ai/class-vortex-thorius-cloe.php';
        
        // Load orchestrator
        require_once $plugin_dir . 'includes/class-vortex-orchestrator.php';
    }
}

// Load all AI agents
add_action('plugins_loaded', array('VORTEX_AI_Loader', 'load_agents'), 10); 