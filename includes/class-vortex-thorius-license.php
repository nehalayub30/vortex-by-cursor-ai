<?php
/**
 * Thorius License Management
 * 
 * Handles license activation, validation, and updates
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Thorius License Management
 */
class Vortex_Thorius_License {
    
    /**
     * License key option name
     */
    const LICENSE_KEY_OPTION = 'vortex_thorius_license_key';
    
    /**
     * License status option name
     */
    const LICENSE_STATUS_OPTION = 'vortex_thorius_license_status';
    
    /**
     * License activation endpoint
     */
    const API_ENDPOINT = 'https://api.vortexai.com/v1/license';
    
    /**
     * Initialize license management
     */
    public function __construct() {
        add_action('admin_init', array($this, 'process_license_actions'));
        add_action('admin_notices', array($this, 'license_notices'));
    }
    
    /**
     * Process license actions (activate, deactivate)
     */
    public function process_license_actions() {
        if (!isset($_POST['vortex_thorius_license_action'])) {
            return;
        }
        
        if (!check_admin_referer('vortex_thorius_license_nonce', 'vortex_thorius_license_nonce')) {
            return;
        }
        
        $action = sanitize_text_field($_POST['vortex_thorius_license_action']);
        
        switch ($action) {
            case 'activate':
                $this->activate_license();
                break;
                
            case 'deactivate':
                $this->deactivate_license();
                break;
        }
    }
    
    /**
     * Activate license
     */
    private function activate_license() {
        $license_key = isset($_POST['vortex_thorius_license_key']) 
            ? sanitize_text_field($_POST['vortex_thorius_license_key']) 
            : '';
            
        if (empty($license_key)) {
            add_settings_error('vortex_thorius_license', 'empty-key', __('Please enter a license key.', 'vortex-ai-marketplace'));
            return;
        }
        
        // API call to validate license
        $response = wp_remote_post(self::API_ENDPOINT, array(
            'timeout' => 15,
            'body' => array(
                'action' => 'activate',
                'license' => $license_key,
                'domain' => home_url(),
                'product' => 'thorius-ai-concierge'
            )
        ));
        
        // Process response and save status
        $this->process_api_response($response, $license_key);
    }
    
    // Additional methods for license management...
} 