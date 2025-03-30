<?php
/**
 * Thorius Privacy Handler
 * 
 * Implements GDPR compliance and privacy features
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Thorius Privacy Handler
 */
class Vortex_Thorius_Privacy {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', array($this, 'register_privacy_policy_content'));
        add_filter('wp_privacy_personal_data_exporters', array($this, 'register_data_exporter'));
        add_filter('wp_privacy_personal_data_erasers', array($this, 'register_data_eraser'));
    }
    
    /**
     * Register privacy policy content
     */
    public function register_privacy_policy_content() {
        if (!function_exists('wp_add_privacy_policy_content')) {
            return;
        }
        
        $content = '
            <h3>' . __('Thorius AI Concierge', 'vortex-ai-marketplace') . '</h3>
            <p>' . __('When you use the Thorius AI Concierge, we collect and process the following data:', 'vortex-ai-marketplace') . '</p>
            <ul>
                <li>' . __('Chat conversations with the AI assistant', 'vortex-ai-marketplace') . '</li>
                <li>' . __('Prompts used for generating artwork', 'vortex-ai-marketplace') . '</li>
                <li>' . __('NFT creation data', 'vortex-ai-marketplace') . '</li>
                <li>' . __('Business strategy analysis requests', 'vortex-ai-marketplace') . '</li>
            </ul>
            <p>' . __('This information is used to provide personalized AI services, improve our algorithms, and enhance user experience. The data is stored securely and is not shared with third parties except as necessary to provide the AI services (e.g., sending prompts to OpenAI or Stability.ai).', 'vortex-ai-marketplace') . '</p>
            <p>' . __('You can request export or deletion of this data through the WordPress privacy tools.', 'vortex-ai-marketplace') . '</p>
        ';
        
        wp_add_privacy_policy_content('Thorius AI Concierge', wp_kses_post($content));
    }
    
    // Additional privacy methods...
} 