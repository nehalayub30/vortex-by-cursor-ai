<?php
/**
 * Thorius Setup Wizard
 * 
 * Guides users through initial setup of Thorius AI
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Thorius Setup Wizard
 */
class Vortex_Thorius_Setup_Wizard {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_wizard_page'));
        add_action('admin_init', array($this, 'start_wizard'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_wizard_scripts'));
    }
    
    /**
     * Add hidden wizard page
     */
    public function add_wizard_page() {
        add_dashboard_page(
            '',
            '',
            'manage_options',
            'vortex-thorius-setup-wizard',
            array($this, 'render_wizard')
        );
    }
    
    /**
     * Start the wizard if requested
     */
    public function start_wizard() {
        if (isset($_GET['page']) && $_GET['page'] === 'vortex-thorius-setup-wizard') {
            // Verify user can manage options
            if (!current_user_can('manage_options')) {
                wp_die(__('You do not have sufficient permissions to access this page.', 'vortex-ai-marketplace'));
            }
            
            // Disable admin notices during wizard
            remove_all_actions('admin_notices');
            remove_all_actions('all_admin_notices');
            
            // Prevent WordPress from redirecting to the welcome screen
            remove_action('welcome_panel', 'wp_welcome_panel');
            
            // Load wizard template
            require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/setup-wizard.php';
            
            // Exit to prevent WordPress from loading the dashboard
            exit;
        }
    }
    
    // Additional wizard methods...
} 