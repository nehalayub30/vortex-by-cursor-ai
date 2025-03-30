<?php
/**
 * Plugin Name: VORTEX AI Marketplace
 * Plugin URI: https://github.com/MarianneNems/VORTEX
 * Description: A blockchain-powered marketplace for AI-generated art with HURAII AI, metrics, and multilanguage support.
 * Version: 1.0.0
 * Author: Marianne Nems
 * Author URI: https://vortexartec.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: vortex-ai-marketplace
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('VORTEX_AI_MARKETPLACE_VERSION', '1.0.0');
define('VORTEX_AI_MARKETPLACE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VORTEX_AI_MARKETPLACE_PLUGIN_URL', plugin_dir_url(__FILE__));

// Activation and deactivation hooks
function activate_vortex_ai_marketplace() {
    require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/class-vortex-activator.php';
    Vortex_Activator::activate();
}

function deactivate_vortex_ai_marketplace() {
    require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/class-vortex-deactivator.php';
    Vortex_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_vortex_ai_marketplace');
register_deactivation_hook(__FILE__, 'deactivate_vortex_ai_marketplace');

// Include the main plugin class
require VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/class-vortex-ai-marketplace.php';

// Begin execution of the plugin
function run_vortex_ai_marketplace() {
    $plugin = new Vortex_AI_Marketplace();
    $plugin->run();
}
run_vortex_ai_marketplace();