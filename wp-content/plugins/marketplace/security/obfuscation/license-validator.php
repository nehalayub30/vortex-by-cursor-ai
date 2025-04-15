<?php
/**
 * VORTEX Marketplace License Validator
 *
 * Validates the IonCube license for the VORTEX Marketplace plugin.
 * This file is used to check license validity and handle license errors.
 *
 * @package VORTEX_Marketplace
 * @subpackage Security
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * License validator class for the VORTEX Marketplace plugin
 */
class VORTEX_License_Validator {
    /**
     * License data
     *
     * @var array
     */
    private $license_data;

    /**
     * Error messages
     *
     * @var array
     */
    private $errors = array();

    /**
     * License status
     *
     * @var bool
     */
    private $is_valid = false;

    /**
     * Constructor
     */
    public function __construct() {
        // Check if IonCube Loader is installed
        if (!extension_loaded('ionCube Loader')) {
            $this->errors[] = 'IonCube Loader extension is not installed on this server.';
            $this->is_valid = false;
            return;
        }

        // Get license data from encoded file
        $this->license_data = $this->get_license_data();
        
        // Validate license
        $this->validate_license();
    }

    /**
     * Get license data from the encoded file
     *
     * @return array License data
     */
    private function get_license_data() {
        $license_data = array();
        
        // Use ionCube functions if available
        if (function_exists('ioncube_license_properties')) {
            $properties = ioncube_license_properties();
            if (is_array($properties)) {
                $license_data = $properties;
                
                // Add expiry information if available
                if (function_exists('ioncube_license_expiry_time')) {
                    $license_data['expiry_time'] = ioncube_license_expiry_time();
                }
                
                // Add server restrictions if available
                if (function_exists('ioncube_licensed_servers')) {
                    $license_data['licensed_servers'] = ioncube_licensed_servers();
                }
            }
        }
        
        return $license_data;
    }

    /**
     * Validate the license
     */
    private function validate_license() {
        // Check if we have license data
        if (empty($this->license_data)) {
            $this->errors[] = 'No valid license found.';
            $this->is_valid = false;
            return;
        }
        
        // Check license expiry
        if (isset($this->license_data['expiry_time'])) {
            $expiry_time = $this->license_data['expiry_time'];
            $current_time = time();
            
            if ($expiry_time < $current_time) {
                $days_expired = floor(($current_time - $expiry_time) / 86400);
                
                // Check for grace period
                $grace_period = isset($this->license_data['grace_period_days']) ? 
                                intval($this->license_data['grace_period_days']) : 14;
                
                if ($days_expired > $grace_period) {
                    $this->errors[] = 'Your license has expired. Please renew your license to continue using VORTEX Marketplace.';
                    $this->is_valid = false;
                    return;
                } else {
                    // In grace period - still valid but add a warning
                    $this->errors[] = 'Your license has expired but is in the grace period. ' . 
                                     'Please renew your license within ' . ($grace_period - $days_expired) . ' days.';
                    $this->is_valid = true;
                }
            } else {
                // License is valid
                $this->is_valid = true;
            }
        }
        
        // Check domain restrictions
        if (isset($this->license_data['domain_names']) && is_array($this->license_data['domain_names'])) {
            $server_name = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';
            $domain_valid = false;
            
            foreach ($this->license_data['domain_names'] as $domain) {
                if ($domain === $server_name || (strpos($server_name, $domain) !== false)) {
                    $domain_valid = true;
                    break;
                }
            }
            
            if (!$domain_valid) {
                $this->errors[] = 'This license is not valid for the current domain.';
                $this->is_valid = false;
            }
        }
        
        // Additional custom validations can be added here
    }

    /**
     * Check if the license is valid
     *
     * @return bool True if valid, false otherwise
     */
    public function is_valid() {
        return $this->is_valid;
    }

    /**
     * Get error messages
     *
     * @return array Error messages
     */
    public function get_errors() {
        return $this->errors;
    }

    /**
     * Get specific license property
     *
     * @param string $property Property name
     * @param mixed $default Default value
     * @return mixed Property value or default
     */
    public function get_property($property, $default = null) {
        return isset($this->license_data[$property]) ? $this->license_data[$property] : $default;
    }

    /**
     * Add admin notices for license issues
     */
    public function add_admin_notices() {
        if (!$this->is_valid) {
            add_action('admin_notices', array($this, 'display_license_errors'));
        } elseif (!empty($this->errors)) {
            // Display warnings even for valid licenses (e.g., grace period)
            add_action('admin_notices', array($this, 'display_license_warnings'));
        }
    }

    /**
     * Display license errors
     */
    public function display_license_errors() {
        ?>
        <div class="error">
            <p><strong>VORTEX Marketplace License Error:</strong></p>
            <ul>
                <?php foreach ($this->errors as $error) : ?>
                    <li><?php echo esc_html($error); ?></li>
                <?php endforeach; ?>
            </ul>
            <p>Please contact <a href="mailto:support@vortexdao.com">support@vortexdao.com</a> for assistance.</p>
        </div>
        <?php
    }

    /**
     * Display license warnings
     */
    public function display_license_warnings() {
        ?>
        <div class="notice notice-warning">
            <p><strong>VORTEX Marketplace License Warning:</strong></p>
            <ul>
                <?php foreach ($this->errors as $error) : ?>
                    <li><?php echo esc_html($error); ?></li>
                <?php endforeach; ?>
            </ul>
            <p>Please contact <a href="mailto:support@vortexdao.com">support@vortexdao.com</a> for assistance.</p>
        </div>
        <?php
    }
}

// Initialize license validator
$vortex_license_validator = new VORTEX_License_Validator();

// Add admin notices for license issues
if (is_admin()) {
    $vortex_license_validator->add_admin_notices();
}

// Optional: Disable plugin functionality if license is invalid
if (!$vortex_license_validator->is_valid()) {
    // Define a constant to indicate invalid license
    define('VORTEX_LICENSE_INVALID', true);
    
    // You can use this constant elsewhere in the plugin to disable features
    // For example: if (!defined('VORTEX_LICENSE_INVALID')) { ... }
} 