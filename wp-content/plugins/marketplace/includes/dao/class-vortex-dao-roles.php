<?php
/**
 * VORTEX DAO Roles
 *
 * Manages DAO-specific user roles and capabilities
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class VORTEX_DAO_Roles {
    
    private static $instance = null;
    
    /**
     * Get class instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Add init hook for setting up roles
        add_action('init', array($this, 'register_roles'));
    }
    
    /**
     * Register custom DAO roles
     */
    public function register_roles() {
        $this->register_vortex_founder_role();
        $this->register_vortex_investor_role();
        $this->register_vortex_team_role();
    }
    
    /**
     * Register vortex_founder role
     */
    private function register_vortex_founder_role() {
        // Founder capabilities
        $founder_caps = array(
            // WordPress capabilities
            'read' => true,
            'upload_files' => true,
            
            // DAO management capabilities
            'manage_vortex_dao' => true,
            'create_dao_proposals' => true,
            'vote_dao_proposals' => true,
            'execute_dao_proposals' => true,
            'veto_dao_proposals' => true,  // Founders have veto power
            'view_dao_metrics' => true,
            
            // Enhanced capabilities
            'manage_dao_treasury' => true,
            'approve_grants' => true,
            'set_dao_parameters' => true,
            'manage_token_vesting' => true,
            'view_sensitive_metrics' => true,
            'access_founder_dashboard' => true,
            
            // Artist marketplace capabilities
            'approve_artists' => true,
            'curate_featured_content' => true
        );
        
        // Remove the role if it exists, then add it fresh
        remove_role('vortex_founder');
        add_role('vortex_founder', 'VORTEX Founder', $founder_caps);
        
        // Add custom meta to indicate voting weight multiplier
        update_option('vortex_founder_vote_multiplier', 3); // 3x voting power
    }
    
    /**
     * Register vortex_investor role
     */
    private function register_vortex_investor_role() {
        // Investor capabilities
        $investor_caps = array(
            // WordPress capabilities
            'read' => true,
            
            // DAO capabilities
            'create_dao_proposals' => true,
            'vote_dao_proposals' => true,
            'view_dao_metrics' => true,
            
            // Investor-specific capabilities
            'view_investor_reports' => true,
            'access_investor_dashboard' => true
        );
        
        // Remove the role if it exists, then add it fresh
        remove_role('vortex_investor');
        add_role('vortex_investor', 'VORTEX Investor', $investor_caps);
        
        // Add custom meta to indicate voting weight multiplier
        update_option('vortex_investor_vote_multiplier', 1.5); // 1.5x voting power
    }
    
    /**
     * Register vortex_team role
     */
    private function register_vortex_team_role() {
        // Team capabilities
        $team_caps = array(
            // WordPress capabilities
            'read' => true,
            'upload_files' => true,
            
            // DAO capabilities
            'create_dao_proposals' => true,
            'vote_dao_proposals' => true,
            'execute_dao_proposals' => true,
            'view_dao_metrics' => true,
            
            // Team-specific capabilities
            'manage_dao_content' => true,
            'moderate_discussions' => true,
            'edit_dao_proposals' => true,
            'respond_to_support' => true,
            'access_team_dashboard' => true
        );
        
        // Remove the role if it exists, then add it fresh
        remove_role('vortex_team');
        add_role('vortex_team', 'VORTEX Team', $team_caps);
        
        // Add custom meta to indicate voting weight multiplier
        update_option('vortex_team_vote_multiplier', 1.2); // 1.2x voting power
    }
    
    /**
     * Get the vote multiplier for a specific user
     *
     * @param int $user_id The user ID
     * @return float The vote multiplier
     */
    public function get_user_vote_multiplier($user_id) {
        $user = get_userdata($user_id);
        
        if (!$user) {
            return 1.0;
        }
        
        if (in_array('vortex_founder', $user->roles)) {
            return get_option('vortex_founder_vote_multiplier', 3.0);
        } elseif (in_array('vortex_investor', $user->roles)) {
            return get_option('vortex_investor_vote_multiplier', 1.5);
        } elseif (in_array('vortex_team', $user->roles)) {
            return get_option('vortex_team_vote_multiplier', 1.2);
        }
        
        return 1.0; // Default multiplier for standard users
    }
    
    /**
     * Check if user has veto power
     *
     * @param int $user_id The user ID
     * @return bool Whether user has veto power
     */
    public function user_has_veto_power($user_id) {
        $user = get_userdata($user_id);
        
        if (!$user) {
            return false;
        }
        
        return in_array('vortex_founder', $user->roles) || 
               user_can($user_id, 'veto_dao_proposals');
    }
    
    /**
     * Get a list of all users with a specific role
     *
     * @param string $role The role name
     * @return array Array of user objects
     */
    public function get_users_by_role($role) {
        $args = array(
            'role' => $role,
            'fields' => array('ID', 'display_name', 'user_email')
        );
        
        return get_users($args);
    }
}

// Initialize the class
$vortex_dao_roles = VORTEX_DAO_Roles::get_instance(); 