<?php
/**
 * VORTEX DAO Security Bridge
 *
 * Handles security integration between DAO and security system
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class VORTEX_DAO_Security_Bridge {
    
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
        // Initialize security integration hooks
        add_action('init', array($this, 'register_security_capabilities'));
        
        // Hook into security system events
        add_action('vortex_security_threat_detected', array($this, 'handle_security_threat'), 10, 2);
        
        // Add DAO-specific access controls
        add_filter('vortex_user_can_access', array($this, 'check_dao_access'), 10, 3);
        
        // Hook into user login to validate DAO permissions
        add_action('wp_login', array($this, 'validate_governance_access'), 10, 2);
        
        // Register AJAX handlers for security checks
        add_action('wp_ajax_vortex_validate_proposal_access', array($this, 'ajax_validate_proposal_access'));
        add_action('wp_ajax_vortex_validate_treasury_access', array($this, 'ajax_validate_treasury_access'));
        
        // Add hook for logging governance access
        add_action('vortex_dao_access_attempt', array($this, 'log_dao_access_attempt'), 10, 3);
    }
    
    /**
     * Register DAO security capabilities
     */
    public function register_security_capabilities() {
        // Map DAO roles to security capabilities
        $role_capabilities = array(
            'administrator' => array(
                'governance_admin_access',
                'treasury_admin_access',
                'dao_security_admin'
            ),
            'vortex_founder' => array(
                'governance_admin_access',
                'treasury_admin_access',
                'veto_power_access'
            ),
            'vortex_investor' => array(
                'governance_voter_access',
                'treasury_viewer_access'
            ),
            'vortex_team' => array(
                'governance_editor_access',
                'treasury_reporter_access'
            )
        );
        
        // Apply capabilities to roles
        foreach ($role_capabilities as $role_name => $capabilities) {
            $role = get_role($role_name);
            if ($role) {
                foreach ($capabilities as $cap) {
                    $role->add_cap($cap);
                }
            }
        }
    }
    
    /**
     * Check if a user has access to a DAO feature
     *
     * @param bool   $access Current access state
     * @param string $feature Feature being accessed
     * @param int    $user_id User ID (0 for current user)
     * @return bool Whether user has access
     */
    public function check_dao_access($access, $feature, $user_id = 0) {
        if ($access === false) {
            return false; // Already denied by another filter
        }
        
        if ($user_id === 0) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false; // Not logged in
        }
        
        // Get user's security score
        $security_score = $this->get_user_security_score($user_id);
        
        // Check if user is under security review
        if ($this->is_user_under_security_review($user_id)) {
            // Log the access attempt
            $this->log_dao_access_attempt($user_id, $feature, false, 'User under security review');
            return false;
        }
        
        // Check feature-specific access
        switch ($feature) {
            case 'dao_proposal_create':
                $has_access = user_can($user_id, 'create_dao_proposals') && $security_score >= 70;
                break;
                
            case 'dao_proposal_vote':
                $has_access = user_can($user_id, 'vote_dao_proposals') && $security_score >= 50;
                break;
                
            case 'dao_proposal_execute':
                $has_access = user_can($user_id, 'execute_dao_proposals') && $security_score >= 90;
                break;
                
            case 'dao_proposal_veto':
                $has_access = user_can($user_id, 'veto_dao_proposals') && $security_score >= 95;
                break;
                
            case 'dao_treasury_view':
                $has_access = user_can($user_id, 'view_dao_metrics') && $security_score >= 60;
                break;
                
            case 'dao_treasury_manage':
                $has_access = user_can($user_id, 'manage_dao_treasury') && $security_score >= 95;
                break;
                
            default:
                // For unknown features, fallback to default WordPress capability check
                $has_access = current_user_can('manage_vortex_dao');
        }
        
        // Log the access attempt
        $this->log_dao_access_attempt($user_id, $feature, $has_access);
        
        return $has_access;
    }
    
    /**
     * Get user's security score
     *
     * @param int $user_id User ID
     * @return int Security score (0-100)
     */
    private function get_user_security_score($user_id) {
        // Try to get cached score
        $score = get_transient('vortex_security_score_' . $user_id);
        
        if ($score !== false) {
            return $score;
        }
        
        // Calculate security score based on various factors
        $base_score = 70; // Default starting score
        
        // Check if user has 2FA enabled
        if (get_user_meta($user_id, 'vortex_2fa_enabled', true)) {
            $base_score += 15;
        }
        
        // Check if user has verified wallet
        global $wpdb;
        $has_verified_wallet = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_wallet_addresses
            WHERE user_id = %d AND verified = 1",
            $user_id
        ));
        
        if ($has_verified_wallet) {
            $base_score += 10;
        }
        
        // Check recent security incidents
        $recent_incidents = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_security_logs
            WHERE user_id = %d 
            AND severity IN ('high', 'critical')
            AND created_at > %s",
            $user_id,
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ));
        
        $base_score -= ($recent_incidents * 20);
        
        // Ensure score is within 0-100 range
        $final_score = max(0, min(100, $base_score));
        
        // Cache score for 1 hour
        set_transient('vortex_security_score_' . $user_id, $final_score, HOUR_IN_SECONDS);
        
        return $final_score;
    }
    
    /**
     * Check if a user is under security review
     *
     * @param int $user_id User ID
     * @return bool Whether user is under review
     */
    private function is_user_under_security_review($user_id) {
        global $wpdb;
        
        // Check security flags table
        $is_flagged = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_security_flags
            WHERE user_id = %d 
            AND flag_type = 'security_review'
            AND status = 'active'",
            $user_id
        ));
        
        return $is_flagged > 0;
    }
    
    /**
     * Validate governance access on login
     *
     * @param string $user_login Username
     * @param WP_User $user User object
     */
    public function validate_governance_access($user_login, $user) {
        // Only process users with DAO roles
        $dao_roles = array('vortex_founder', 'vortex_investor', 'vortex_team');
        $has_dao_role = false;
        
        foreach ($dao_roles as $role) {
            if (in_array($role, $user->roles)) {
                $has_dao_role = true;
                break;
            }
        }
        
        if (!$has_dao_role) {
            return;
        }
        
        // Check user's security status
        $security_score = $this->get_user_security_score($user->ID);
        
        // If security score is critically low, flag for review
        if ($security_score < 30) {
            $this->flag_user_for_security_review($user->ID, 'Low security score: ' . $security_score);
        }
        
        // Check for unusual login patterns
        if ($this->is_unusual_login($user->ID)) {
            // Log security event
            $this->log_security_event($user->ID, 'unusual_login', 'Unusual login pattern detected', 'medium');
            
            // If user has high privileges, consider flagging for review
            if (user_can($user->ID, 'manage_dao_treasury') || user_can($user->ID, 'veto_dao_proposals')) {
                $this->flag_user_for_security_review($user->ID, 'Unusual login for high-privilege user');
            }
        }
    }
    
    /**
     * Check for unusual login patterns
     *
     * @param int $user_id User ID
     * @return bool Whether login appears unusual
     */
    private function is_unusual_login($user_id) {
        // Implementation would compare current login with user's typical patterns
        // using IP, time of day, device, etc.
        // Simplified implementation for demo purposes
        return false;
    }
    
    /**
     * Flag a user for security review
     *
     * @param int    $user_id User ID
     * @param string $reason Reason for flagging
     */
    private function flag_user_for_security_review($user_id, $reason) {
        global $wpdb;
        
        // Add to security flags table
        $wpdb->insert(
            $wpdb->prefix . 'vortex_security_flags',
            array(
                'user_id' => $user_id,
                'flag_type' => 'security_review',
                'reason' => $reason,
                'status' => 'active',
                'created_at' => current_time('mysql'),
                'created_by' => 0 // System-generated flag
            ),
            array('%d', '%s', '%s', '%s', '%s', '%d')
        );
        
        // Log security event
        $this->log_security_event($user_id, 'security_flag', $reason, 'high');
        
        // Notify security admin
        $this->notify_security_admin('User Flagged for Review', $user_id, $reason);
    }
    
    /**
     * Log a DAO access attempt
     *
     * @param int    $user_id User ID
     * @param string $feature Feature being accessed
     * @param bool   $granted Whether access was granted
     * @param string $reason Optional reason for denial
     */
    public function log_dao_access_attempt($user_id, $feature, $granted, $reason = '') {
        global $wpdb;
        
        // Log to governance logs table
        $wpdb->insert(
            $wpdb->prefix . 'vortex_dao_governance_logs',
            array(
                'action_type' => 'access_attempt',
                'user_id' => $user_id,
                'wallet_address' => $this->get_user_primary_wallet($user_id),
                'action_data' => wp_json_encode(array(
                    'feature' => $feature,
                    'granted' => $granted,
                    'reason' => $reason,
                    'ip_address' => $this->get_user_ip(),
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
                )),
                'status' => $granted ? 'success' : 'denied',
                'timestamp' => current_time('mysql')
            ),
            array('%s', '%d', '%s', '%s', '%s', '%s')
        );
        
        // If access denied for sensitive features, log security event
        $sensitive_features = array('dao_proposal_execute', 'dao_proposal_veto', 'dao_treasury_manage');
        
        if (!$granted && in_array($feature, $sensitive_features)) {
            $this->log_security_event(
                $user_id,
                'dao_access_denied',
                'Access denied to ' . $feature . ': ' . $reason,
                'medium'
            );
        }
    }
    
    /**
     * Get user's primary wallet address
     *
     * @param int $user_id User ID
     * @return string Wallet address or empty string
     */
    private function get_user_primary_wallet($user_id) {
        global $wpdb;
        
        $wallet = $wpdb->get_var($wpdb->prepare(
            "SELECT wallet_address FROM {$wpdb->prefix}vortex_wallet_addresses
            WHERE user_id = %d AND is_primary = 1
            LIMIT 1",
            $user_id
        ));
        
        return $wallet ?: '';
    }
    
    /**
     * Handle security threat detection
     *
     * @param string $threat_type Type of threat
     * @param array  $threat_data Additional data about the threat
     */
    public function handle_security_threat($threat_type, $threat_data) {
        // Only process DAO-related threats
        if (strpos($threat_type, 'dao_') !== 0) {
            return;
        }
        
        // Determine severity
        $severity = $threat_data['severity'] ?? 'medium';
        
        // Take appropriate action based on threat type
        switch ($threat_type) {
            case 'dao_proposal_manipulation':
                $this->handle_proposal_manipulation($threat_data, $severity);
                break;
                
            case 'dao_treasury_exploit':
                $this->handle_treasury_exploit($threat_data, $severity);
                break;
                
            case 'dao_voting_manipulation':
                $this->handle_voting_manipulation($threat_data, $severity);
                break;
                
            case 'dao_governance_spam':
                $this->handle_governance_spam($threat_data, $severity);
                break;
                
            default:
                // Log unknown threat types
                $this->log_security_event(
                    $threat_data['user_id'] ?? 0,
                    $threat_type,
                    'Unknown DAO security threat: ' . wp_json_encode($threat_data),
                    $severity
                );
        }
    }
    
    /**
     * Handle proposal manipulation threat
     *
     * @param array  $threat_data Threat data
     * @param string $severity Threat severity
     */
    private function handle_proposal_manipulation($threat_data, $severity) {
        // Extract data
        $user_id = $threat_data['user_id'] ?? 0;
        $proposal_id = $threat_data['proposal_id'] ?? 0;
        
        // Log security event
        $this->log_security_event(
            $user_id,
            'dao_proposal_manipulation',
            'Suspected manipulation of proposal #' . $proposal_id,
            $severity
        );
        
        // If high or critical severity, take immediate action
        if (in_array($severity, array('high', 'critical'))) {
            // Flag the proposal for review
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'vortex_dao_proposals',
                array('status' => 'flagged'),
                array('id' => $proposal_id),
                array('%s'),
                array('%d')
            );
            
            // Flag user for security review
            $this->flag_user_for_security_review($user_id, 'Suspected proposal manipulation');
            
            // Create alert via AI system
            $ai_integration = VORTEX_DAO_AI_Integration::get_instance();
            $ai_integration->create_governance_alert($proposal_id, array(
                'security_analysis' => array(
                    'risk_level' => 'high',
                    'recommendation' => 'Review proposal for manipulation',
                    'details' => 'Security system detected potential manipulation'
                ),
                'business_analysis' => array(
                    'risk_level' => 'high'
                )
            ));
        }
        
        // Notify security admin
        $this->notify_security_admin('Proposal Manipulation Detected', $user_id, "Proposal #{$proposal_id} - Severity: {$severity}");
    }
    
    /**
     * Handle treasury exploit threat
     *
     * @param array  $threat_data Threat data
     * @param string $severity Threat severity
     */
    private function handle_treasury_exploit($threat_data, $severity) {
        // Extract data
        $user_id = $threat_data['user_id'] ?? 0;
        $transaction_id = $threat_data['transaction_id'] ?? 0;
        
        // Log security event
        $this->log_security_event(
            $user_id,
            'dao_treasury_exploit',
            'Suspected treasury exploit attempt: ' . wp_json_encode($threat_data),
            $severity
        );
        
        // For critical threats, initiate emergency pause
        if ($severity === 'critical') {
            $this->initiate_emergency_treasury_pause($threat_data);
        }
        
        // Flag user
        $this->flag_user_for_security_review($user_id, 'Suspected treasury exploit attempt');
        
        // Notify security admin and founders immediately
        $this->notify_security_admin(
            'URGENT: Treasury Exploit Attempt',
            $user_id,
            "Transaction #{$transaction_id} - Severity: {$severity}",
            true // Send to founders
        );
    }
    
    /**
     * Handle voting manipulation threat
     *
     * @param array  $threat_data Threat data
     * @param string $severity Threat severity
     */
    private function handle_voting_manipulation($threat_data, $severity) {
        // Extract data
        $user_id = $threat_data['user_id'] ?? 0;
        $proposal_id = $threat_data['proposal_id'] ?? 0;
        
        // Log security event
        $this->log_security_event(
            $user_id,
            'dao_voting_manipulation',
            'Suspected voting manipulation on proposal #' . $proposal_id,
            $severity
        );
        
        // Flag affected votes for review
        if (isset($threat_data['affected_votes']) && is_array($threat_data['affected_votes'])) {
            global $wpdb;
            
            foreach ($threat_data['affected_votes'] as $vote_id) {
                $wpdb->update(
                    $wpdb->prefix . 'vortex_dao_votes',
                    array('status' => 'flagged'),
                    array('id' => $vote_id),
                    array('%s'),
                    array('%d')
                );
            }
        }
        
        // Request AI analysis of voting patterns
        $ai_integration = VORTEX_DAO_AI_Integration::get_instance();
        $ai_integration->detect_suspicious_voting_patterns($proposal_id);
    }
    
    /**
     * Handle governance spam threat
     *
     * @param array  $threat_data Threat data
     * @param string $severity Threat severity
     */
    private function handle_governance_spam($threat_data, $severity) {
        // Extract data
        $user_id = $threat_data['user_id'] ?? 0;
        
        // Log security event
        $this->log_security_event(
            $user_id,
            'dao_governance_spam',
            'Suspected governance spam activity',
            $severity
        );
        
        // If medium or higher severity, rate limit the user
        if (in_array($severity, array('medium', 'high', 'critical'))) {
            // Set rate limiting for the user
            set_transient('vortex_dao_rate_limit_' . $user_id, true, 2 * HOUR_IN_SECONDS);
            
            // If high severity, apply a temporary ban
            if (in_array($severity, array('high', 'critical'))) {
                $this->flag_user_for_security_review($user_id, 'Governance spam activity detected');
            }
        }
    }
    
    /**
     * Initiate emergency treasury pause
     *
     * @param array $threat_data Threat data
     */
    private function initiate_emergency_treasury_pause($threat_data) {
        // Set emergency pause flag
        update_option('vortex_dao_treasury_emergency_pause', true);
        update_option('vortex_dao_treasury_pause_reason', 'Security threat detected: ' . wp_json_encode($threat_data));
        update_option('vortex_dao_treasury_pause_time', current_time('mysql'));
        
        // Log governance action
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'vortex_dao_governance_logs',
            array(
                'action_type' => 'emergency_pause',
                'user_id' => 0, // System action
                'wallet_address' => 'SYSTEM',
                'action_data' => wp_json_encode(array(
                    'reason' => 'Treasury security threat detected',
                    'threat_data' => $threat_data
                )),
                'status' => 'completed',
                'timestamp' => current_time('mysql')
            ),
            array('%s', '%d', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Log a security event
     *
     * @param int    $user_id User ID
     * @param string $event_type Event type
     * @param string $description Event description
     * @param string $severity Event severity
     */
    private function log_security_event($user_id, $event_type, $description, $severity = 'medium') {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'vortex_security_logs',
            array(
                'user_id' => $user_id,
                'event_type' => $event_type,
                'description' => $description,
                'ip_address' => $this->get_user_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'severity' => $severity,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Notify security admin
     *
     * @param string $subject Email subject
     * @param int    $user_id User involved
     * @param string $details Additional details
     * @param bool   $notify_founders Whether to also notify founders
     */
    private function notify_security_admin($subject, $user_id, $details, $notify_founders = false) {
        // Get admin emails
        $admin_emails = array();
        $admins = get_users(array('role' => 'administrator', 'meta_key' => 'vortex_security_notifications', 'meta_value' => 'yes'));
        
        foreach ($admins as $admin) {
            $admin_emails[] = $admin->user_email;
        }
        
        // Add security team members
        $security_team = get_users(array('meta_key' => 'vortex_security_team', 'meta_value' => 'yes'));
        foreach ($security_team as $member) {
            $admin_emails[] = $member->user_email;
        }
        
        // Add founders if requested
        if ($notify_founders) {
            $founders = get_users(array('role' => 'vortex_founder'));
            foreach ($founders as $founder) {
                $admin_emails[] = $founder->user_email;
            }
        }
        
        // Prepare email content
        $subject = "[VORTEX Security] $subject";
        
        $user = get_user_by('id', $user_id);
        $username = $user ? $user->user_login : 'Unknown User';
        
        $message = "A security event has been detected in the VORTEX DAO system.\n\n";
        $message .= "User: $username (ID: $user_id)\n";
        $message .= "Details: $details\n\n";
        $message .= "Time: " . current_time('mysql') . "\n";
        $message .= "IP: " . $this->get_user_ip() . "\n\n";
        $message .= "Please review this incident in the security dashboard: " . admin_url('admin.php?page=vortex-security') . "\n\n";
        $message .= "This is an automated security notification.";
        
        // Send email
        wp_mail($admin_emails, $subject, $message);
    }
    
    /**
     * Get user's IP address
     *
     * @return string IP address
     */
    private function get_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
        return $ip;
    }
    
    /**
     * AJAX handler to validate proposal access
     */
    public function ajax_validate_proposal_access() {
        check_ajax_referer('vortex_dao_nonce', 'nonce');
        
        $proposal_id = isset($_POST['proposal_id']) ? intval($_POST['proposal_id']) : 0;
        $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : 'view';
        
        if (!$proposal_id) {
            wp_send_json_error('Invalid proposal ID');
            return;
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('User not logged in');
            return;
        }
        
        // Map action to required capability
        $feature_map = array(
            'view' => 'dao_proposal_vote', // If they can vote, they can view
            'vote' => 'dao_proposal_vote',
            'execute' => 'dao_proposal_execute',
            'veto' => 'dao_proposal_veto'
        );
        
        $feature = isset($feature_map[$action]) ? $feature_map[$action] : 'dao_proposal_vote';
        
        // Check access
        $has_access = apply_filters('vortex_user_can_access', true, $feature, $user_id);
        
        if ($has_access) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Access denied');
        }
    }
    
    /**
     * AJAX handler to validate treasury access
     */
    public function ajax_validate_treasury_access() {
        check_ajax_referer('vortex_dao_nonce', 'nonce');
        
        $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : 'view';
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error('User not logged in');
            return;
        }
        
        // Check if treasury is in emergency pause
        $emergency_pause = get_option('vortex_dao_treasury_emergency_pause', false);
        if ($emergency_pause && $action !== 'view') {
            wp_send_json_error(array(
                'message' => 'Treasury operations are currently paused due to a security concern',
                'pause_reason' => get_option('vortex_dao_treasury_pause_reason', 'Security concern'),
                'pause_time' => get_option('vortex_dao_treasury_pause_time', '')
            ));
            return;
        }
        
        // Map action to required capability
        $feature_map = array(
            'view' => 'dao_treasury_view',
            'manage' => 'dao_treasury_manage'
        );
        
        $feature = isset($feature_map[$action]) ? $feature_map[$action] : 'dao_treasury_view';
        
        // Check access
        $has_access = apply_filters('vortex_user_can_access', true, $feature, $user_id);
        
        if ($has_access) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Access denied');
        }
    }
}

// Initialize Security Bridge
$vortex_dao_security_bridge = VORTEX_DAO_Security_Bridge::get_instance(); 