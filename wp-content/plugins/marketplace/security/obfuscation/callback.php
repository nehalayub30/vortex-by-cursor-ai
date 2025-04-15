<?php
/**
 * IonCube Encoder Callback File for VORTEX Marketplace
 * 
 * This file is used by the IonCube encoder to customize the encoding process.
 * It's called during the encoding process for each file being encoded.
 * 
 * @package VORTEX_Marketplace
 * @subpackage Security
 */

// Define callback functions for IonCube encoder
if (!function_exists('ioncube_event_source')) {
    /**
     * Callback function called by IonCube for each file being encoded
     * 
     * @param string $event        Event name/type
     * @param array  $parameters   Event parameters
     * @return bool|void           Return true to confirm processing or void
     */
    function ioncube_event_source($event, &$parameters) {
        switch ($event) {
            // Called before a file is encoded
            case 'pre_encode_file':
                // Get the source filename
                $source_file = $parameters['source_file'];
                $target_file = $parameters['target_file'];
                
                // Log files being processed
                error_log("Encoding: " . basename($source_file) . " to " . basename($target_file));
                
                // Add custom comments to encoded files
                $parameters['encoding_comments'] = 
                    "Encoded with IonCube for VORTEX Marketplace\n" .
                    "Copyright © " . date('Y') . " VORTEX DAO\n" .
                    "License: Proprietary and Confidential";
                
                return true;
                
            // Called after a file has been encoded
            case 'post_encode_file':
                // You can perform post-processing here if needed
                return true;
                
            // Called when a license is about to be generated
            case 'license_create':
                // Customize license parameters if needed
                $parameters['licensee_name'] = "VORTEX DAO Marketplace";
                
                // Add custom properties to the license
                $parameters['custom_properties'] = array(
                    'company' => 'VORTEX DAO',
                    'website' => 'https://vortexdao.com',
                    'license_type' => 'commercial',
                    'encoded_date' => date('Y-m-d H:i:s')
                );
                
                return true;
                
            // Called for external files referenced by encoded files
            case 'external_file':
                // Determine how to handle external file references
                // You can choose to encode, copy, or ignore external files
                $ext = pathinfo($parameters['source_file'], PATHINFO_EXTENSION);
                
                // Encode PHP files, copy others
                $parameters['action'] = ($ext == 'php') ? 'encode' : 'copy';
                
                return true;
        }
        
        return true;
    }
}

// Additional callback functions can be added below if needed

/**
 * Custom license checker function that can be called from your code
 * to verify license validity with additional custom logic
 * 
 * @return bool True if license is valid, false otherwise
 */
function vortex_verify_license() {
    if (!function_exists('ioncube_license_properties')) {
        return false;
    }
    
    // Get license properties
    $props = ioncube_license_properties();
    
    if (!$props) {
        return false;
    }
    
    // Check custom properties
    if (isset($props['custom_properties'])) {
        $custom = $props['custom_properties'];
        
        // Implement custom verification logic here
        if (isset($custom['license_type']) && $custom['license_type'] !== 'commercial') {
            return false;
        }
        
        // Additional checks as needed
    }
    
    return true;
} 