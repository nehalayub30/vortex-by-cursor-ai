<?php
/**
 * Thorius Data Cleanup Manager
 * 
 * Handles data retention and cleanup tasks
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Thorius Data Cleanup Manager
 */
class Vortex_Thorius_Data_Cleanup {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Schedule cleanup task if not already scheduled
        if (!wp_next_scheduled('vortex_thorius_data_cleanup')) {
            wp_schedule_event(time(), 'daily', 'vortex_thorius_data_cleanup');
        }
        
        // Register cleanup action
        add_action('vortex_thorius_data_cleanup', array($this, 'cleanup_old_data'));
        
        // Register deactivation hook to clear scheduled events
        register_deactivation_hook(VORTEX_THORIUS_PLUGIN_FILE, array($this, 'deactivation_cleanup'));
    }
    
    /**
     * Cleanup old data based on retention settings
     */
    public function cleanup_old_data() {
        $this->cleanup_analytics_data();
        $this->cleanup_cache_data();
        $this->cleanup_security_logs();
        $this->cleanup_conversation_logs();
        
        // Log cleanup event
        error_log('Thorius data cleanup completed at ' . current_time('mysql'));
    }
    
    /**
     * Cleanup analytics data
     */
    private function cleanup_analytics_data() {
        global $wpdb;
        
        // Get retention period (in days)
        $retention_days = (int) get_option('vortex_thorius_data_retention', 90);
        
        // Skip if retention is set to 0 (keep forever)
        if ($retention_days <= 0) {
            return;
        }
        
        // Calculate cutoff date
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        
        // Delete old analytics events
        $table_name = $wpdb->prefix . 'vortex_thorius_analytics';
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table_name} WHERE timestamp < %s",
                $cutoff_date
            )
        );
        
        // Delete old user sessions
        $table_name = $wpdb->prefix . 'vortex_thorius_sessions';
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table_name} WHERE last_activity < %s",
                $cutoff_date
            )
        );
    }
    
    /**
     * Cleanup cache data
     */
    private function cleanup_cache_data() {
        global $wpdb;
        
        // Get cache table name
        $table_name = $wpdb->prefix . 'vortex_thorius_cache';
        
        // Delete expired cache items
        $wpdb->query(
            "DELETE FROM {$table_name} WHERE expiry < " . time()
        );
    }
    
    /**
     * Cleanup security logs
     */
    private function cleanup_security_logs() {
        global $wpdb;
        
        // Get retention period (in days) - security logs kept longer than regular analytics
        $retention_days = (int) get_option('vortex_thorius_security_log_retention', 365);
        
        // Skip if retention is set to 0 (keep forever)
        if ($retention_days <= 0) {
            return;
        }
        
        // Calculate cutoff date
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        
        // Delete old security logs
        $table_name = $wpdb->prefix . 'vortex_thorius_security_log';
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table_name} WHERE timestamp < %s",
                $cutoff_date
            )
        );
    }
    
    /**
     * Cleanup conversation logs
     */
    private function cleanup_conversation_logs() {
        global $wpdb;
        
        // Get retention period (in days)
        $retention_days = (int) get_option('vortex_thorius_conversation_retention', 30);
        
        // Skip if retention is set to 0 (keep forever)
        if ($retention_days <= 0) {
            return;
        }
        
        // Calculate cutoff date
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        
        // Delete old conversations
        $table_name = $wpdb->prefix . 'vortex_thorius_conversations';
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table_name} WHERE timestamp < %s",
                $cutoff_date
            )
        );
    }
    
    /**
     * Cleanup on plugin deactivation
     */
    public function deactivation_cleanup() {
        // Clear scheduled events
        wp_clear_scheduled_hook('vortex_thorius_data_cleanup');
    }
    
    /**
     * Add user data cleanup with improved security measures
     */
    public function cleanup_user_data($user_id) {
        global $wpdb;
        
        try {
            // Begin transaction for atomic operations
            $wpdb->query('START TRANSACTION');
            
            // Clean up user preferences
            $wpdb->delete($wpdb->prefix . 'vortex_thorius_user_preferences', 
                array('user_id' => $user_id), 
                array('%d')
            );
            
            // Clean up user sessions - important for security
            $wpdb->delete($wpdb->prefix . 'vortex_thorius_sessions', 
                array('user_id' => $user_id), 
                array('%d')
            );
            
            // Clean up interaction history - contains sensitive data
            $wpdb->delete($wpdb->prefix . 'vortex_thorius_interaction_history', 
                array('user_id' => $user_id), 
                array('%d')
            );
            
            // Clean up user context
            $wpdb->delete($wpdb->prefix . 'vortex_thorius_user_context', 
                array('user_id' => $user_id), 
                array('%d')
            );
            
            // Clean up conversation logs
            $wpdb->delete($wpdb->prefix . 'vortex_thorius_conversations', 
                array('user_id' => $user_id), 
                array('%d')
            );
            
            // Commit changes if all deletions succeeded
            $wpdb->query('COMMIT');
            
            // Clear caches
            wp_cache_delete("thorius_user_prefs_{$user_id}", 'thorius');
            wp_cache_delete("thorius_user_context_{$user_id}", 'thorius');
            
            // Log successful cleanup
            error_log("Thorius user data cleanup completed for user ID: {$user_id}");
            
            return true;
        } catch (Exception $e) {
            // Rollback on error
            $wpdb->query('ROLLBACK');
            error_log('Thorius User Cleanup Error: ' . $e->getMessage());
            return false;
        }
    }
} 