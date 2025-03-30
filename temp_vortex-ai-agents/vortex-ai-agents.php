<?php
/**
 * Plugin Name: VORTEX AI AGENTS
 * Plugin URI: https://github.com/MarianneNems/VORTEX-AI-AGENTS
 * Description: Advanced AI-powered marketplace agents for enhanced e-commerce functionality
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Marianne Nems(aka Mariana Villard all rights reserve, 2025)
 * Author URI: https://github.com/MarianneNems
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: vortex-ai-agents
 * Domain Path: /languages
 * 
 * WordPress Compatibility: Tested up to WordPress 6.4
 * WooCommerce Compatibility: Tested up to WooCommerce 8.5
 */

 if (!defined('ABSPATH')) exit;

 // Plugin Constants
 define('VORTEX_VERSION', '1.0.0');
 define('VORTEX_PLUGIN_DIR', plugin_dir_path(__FILE__));
 define('VORTEX_PLUGIN_URL', plugin_dir_url(__FILE__));
 define('VORTEX_PLUGIN_BASENAME', plugin_basename(__FILE__));
 
 // Autoloader
 require_once VORTEX_PLUGIN_DIR . 'includes/class-vortex-autoloader.php';
 
 // Initialize Plugin
 function vortex_init() {
     \ = new Vortex\AI\VortexPlugin();
     \->initialize();
 }
 add_action('plugins_loaded', 'vortex_init');
 
 // Activation Hook
 register_activation_hook(__FILE__, function() {
     require_once VORTEX_PLUGIN_DIR . 'includes/class-vortex-activator.php';
     Vortex\AI\VortexActivator::activate();
 });
 
 // Deactivation Hook
 register_deactivation_hook(__FILE__, function() {
     require_once VORTEX_PLUGIN_DIR . 'includes/class-vortex-deactivator.php';
     Vortex\AI\VortexDeactivator::deactivate();
 });

