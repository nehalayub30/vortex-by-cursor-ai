<?php
/**
 * Thorius Shortcodes Registration
 * 
 * Registers all shortcodes for Thorius AI Concierge
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/shortcodes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Thorius Shortcodes Registration
 */
class Vortex_Thorius_Shortcodes {
    
    /**
     * Initialize shortcodes
     */
    public function __construct() {
        add_shortcode('thorius_concierge', array($this, 'render_concierge'));
        add_shortcode('thorius_chat', array($this, 'render_chat'));
        add_shortcode('thorius_agent', array($this, 'render_agent'));
    }
    
    /**
     * Render concierge shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function render_concierge($atts) {
        $atts = shortcode_atts(
            array(
                'position' => 'right',
                'theme' => 'light',
                'default_tab' => 'chat'
            ),
            $atts,
            'thorius_concierge'
        );
        
        ob_start();
        include plugin_dir_path(dirname(dirname(__FILE__))) . 'public/shortcodes/thorius-concierge-shortcode.php';
        return ob_get_clean();
    }
    
    /**
     * Render chat shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function render_chat($atts) {
        $atts = shortcode_atts(
            array(
                'agent' => 'cloe',
                'theme' => 'light'
            ),
            $atts,
            'thorius_chat'
        );
        
        ob_start();
        include plugin_dir_path(dirname(dirname(__FILE__))) . 'public/shortcodes/thorius-chat-shortcode.php';
        return ob_get_clean();
    }
    
    /**
     * Render agent shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Shortcode output
     */
    public function render_agent($atts) {
        $atts = shortcode_atts(
            array(
                'agent' => 'huraii',
                'mode' => 'artwork'
            ),
            $atts,
            'thorius_agent'
        );
        
        ob_start();
        include plugin_dir_path(dirname(dirname(__FILE__))) . 'public/shortcodes/thorius-agent-shortcode.php';
        return ob_get_clean();
    }
} 