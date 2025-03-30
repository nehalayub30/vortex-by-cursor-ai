<?php
namespace Vortex;

/**
 * Manages user roles and capabilities.
 *
 * @since      1.0.0
 */
class Vortex_User_Manager {
    
    /**
     * Initialize the class.
     */
    public function __construct() {
        // Constructor code
        add_action('show_user_profile', array($this, 'add_vortex_user_fields'));
        add_action('edit_user_profile', array($this, 'add_vortex_user_fields'));
        add_action('personal_options_update', array($this, 'save_vortex_user_fields'));
        add_action('edit_user_profile_update', array($this, 'save_vortex_user_fields'));
    }
    
    /**
     * Add custom user profile fields.
     */
    public function add_vortex_user_fields($user) {
        // Custom fields for VORTEX users
        include_once(VORTEX_PLUGIN_DIR . 'admin/partials/vortex-user-profile-fields.php');
    }
    
    /**
     * Save custom user profile fields.
     */
    public function save_vortex_user_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }
        
        // Save custom fields
        if (isset($_POST['vortex_wallet_address'])) {
            update_user_meta($user_id, 'vortex_wallet_address', sanitize_text_field($_POST['vortex_wallet_address']));
        }
    }
    
    /**
     * Get user TOLA balance.
     */
    public function get_user_tola_balance($user_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vortex_tola_points';
        
        $total = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(points) FROM $table WHERE user_id = %d",
                $user_id
            )
        );
        
        return $total ? $total : 0;
    }
} 