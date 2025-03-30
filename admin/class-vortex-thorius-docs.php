<?php
/**
 * Thorius Documentation Generator
 * 
 * Generates documentation for users and developers
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Thorius Documentation Generator
 */
class Vortex_Thorius_Docs {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_docs_page'));
        add_action('admin_init', array($this, 'generate_docs'));
    }
    
    /**
     * Add documentation page
     */
    public function add_docs_page() {
        add_submenu_page(
            'vortex-thorius',
            __('Documentation', 'vortex-ai-marketplace'),
            __('Documentation', 'vortex-ai-marketplace'),
            'edit_posts',
            'vortex-thorius-docs',
            array($this, 'render_docs_page')
        );
    }
    
    /**
     * Generate documentation
     */
    public function generate_docs() {
        if (isset($_GET['generate-docs']) && current_user_can('manage_options')) {
            $this->generate_user_docs();
            $this->generate_dev_docs();
            
            wp_redirect(admin_url('admin.php?page=vortex-thorius-docs&generated=1'));
            exit;
        }
    }
    
    // Additional documentation methods...
} 