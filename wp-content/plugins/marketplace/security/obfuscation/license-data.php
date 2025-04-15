<?php
/**
 * License Data File for IonCube Encoding
 * 
 * This file contains license configuration data used by the IonCube encoder
 * to generate license files for the VORTEX Marketplace plugin.
 * 
 * @package VORTEX_Marketplace
 * @subpackage Security
 */

return array(
    // Basic license information
    'licensee' => array(
        'name' => 'VORTEX DAO',
        'email' => 'support@vortexdao.com',
    ),
    
    // License validity configuration
    'validity' => array(
        'days' => 365,                         // License valid for 1 year
        'start_date' => date('Y-m-d'),         // Starting from today
        'expiry_date' => null,                 // Set automatically based on days
    ),
    
    // License restrictions
    'restrictions' => array(
        'ip_addresses' => array(),             // Empty for no IP restriction
        'mac_addresses' => array(),            // Empty for no MAC restriction
        'domain_names' => array(               // Restrict to specific domains
            'vortexdao.com',
            'marketplace.vortexdao.com',
        ),
        'server_check' => true,                // Verify server environment
    ),
    
    // License options
    'options' => array(
        'allow_grace_period' => true,          // Allow a grace period after expiry
        'grace_period_days' => 14,             // 2 weeks grace period
        'verify_timestamps' => true,           // Check for clock manipulation
        'allow_vm' => true,                    // Allow virtual machines
        'encrypt_code' => true,                // Use encryption for code protection
    ),
    
    // Custom properties added to the license
    'properties' => array(
        'product' => 'VORTEX Marketplace',
        'edition' => 'Enterprise',
        'version' => '1.0.0',
        'company' => 'VORTEX DAO',
        'website' => 'https://vortexdao.com',
        'contact' => 'support@vortexdao.com',
        'registered_to' => 'VORTEX DAO',
    ),
    
    // License passphrase (used for encryption)
    'passphrase' => 'V0rt3xD@0M@rk3tpl@c3',    // Secure passphrase
    
    // Notification settings
    'notifications' => array(
        'email' => 'support@vortexdao.com',
        'days_before_expiry' => array(30, 14, 7, 3, 1),
        'send_expiry_notice' => true,
        'send_installation_notice' => true,
    ),
); 