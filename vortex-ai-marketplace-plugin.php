<?php
/**
 * Vortex AI Marketplace
 *
 * @package    Vortex_AI_Marketplace
 * @author     Vortex Development Team
 * @copyright  2023 Vortex Marketplace
 * @license    GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: Vortex AI Marketplace
 * Plugin URI:  https://www.vortexartec.com
 * Description: A lightweight client for the Vortex AI Marketplace SaaS platform.
 * Version:     1.0.0
 * Author:      Vortex Development Team
 * Author URI:  https://www.vortexartec.com
 * Text Domain: vortex-ai-marketplace
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'VORTEX_AI_MARKETPLACE_VERSION', '1.0.0' );
define( 'VORTEX_AI_MARKETPLACE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'VORTEX_AI_MARKETPLACE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'VORTEX_AI_MARKETPLACE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 */
function activate_vortex_ai_marketplace() {
	require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/class-vortex-activator.php';
	Vortex_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_vortex_ai_marketplace() {
	require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/class-vortex-deactivator.php';
	Vortex_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_vortex_ai_marketplace' );
register_deactivation_hook( __FILE__, 'deactivate_vortex_ai_marketplace' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once VORTEX_AI_MARKETPLACE_PLUGIN_DIR . 'includes/class-vortex-ai-marketplace.php';

/**
 * Begins execution of the plugin.
 *
 * @since 1.0.0
 */
function run_vortex_ai_marketplace() {
	$plugin = new Vortex_AI_Marketplace();
	$plugin->run();
}

run_vortex_ai_marketplace(); 