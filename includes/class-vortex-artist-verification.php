<?php
/**
 * Artist Verification System
 * 
 * Handles verification of artists and their credentials
 * for the artwork swap system.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

class Vortex_Artist_Verification {
    
    /**
     * Initialize the verification system
     */
    public function __construct() {
        // Register verification hooks
        add_action('wp_ajax_vortex_submit_verification', array($this, 'handle_verification_submission'));
        add_action('wp_ajax_vortex_check_verification_status', array($this, 'check_verification_status'));
        
        // Add verification review hooks for admins
        add_action('wp_ajax_vortex_review_verification', array($this, 'handle_verification_review'));
        
        // Add meta box to user profile for artist verification 
        add_action('show_user_profile', array($this, 'add_verification_meta_box'));
        add_action('edit_user_profile', array($this, 'add_verification_meta_box'));
        
        // Save user profile artist verification
        add_action('personal_options_update', array($this, 'save_verification_meta_box'));
        add_action('edit_user_profile_update', array($this, 'save_verification_meta_box'));
        
        // Register AI verification assistance hook
        add_filter('vortex_ai_verify_artist', array($this, 'ai_verification_assistance'), 10, 2);
    }
    
    /**
     * Check if a user is a verified artist
     *
     * @param int $user_id User ID to check
     * @return bool Whether user is verified
     */
    public function is_verified_artist($user_id) {
        // Get verification status
        $verified = get_user_meta($user_id, 'vortex_artist_verified', true);
        
        // Also check for artist role
        $user = get_user_by('id', $user_id);
        $is_artist_role = in_array('vortex_artist', (array) $user->roles);
        
        return $verified === 'yes' && $is_artist_role;
    }
    
    /**
     * Get verification status with details
     *
     * @param int $user_id User ID to check
     * @return array Verification status and details
     */
    public function get_verification_status($user_id) {
        $status = get_user_meta($user_id, 'vortex_artist_verification_status', true) ?: 'not_submitted';
        $submitted_date = get_user_meta($user_id, 'vortex_artist_verification_submitted', true);
        $verified = $this->is_verified_artist($user_id);
        $rejection_reason = get_user_meta($user_id, 'vortex_artist_verification_rejection', true);
        
        return array(
            'status' => $status,
            'submitted_date' => $submitted_date,
            'verified' => $verified,
            'rejection_reason' => $rejection_reason
        );
    }
    
    /**
     * Handle artist verification submission
     */
    public function handle_verification_submission() {
        check_ajax_referer('vortex_verification_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        
        // Check if user has required fields
        $portfolio_url = isset($_POST['portfolio_url']) ? esc_url_raw($_POST['portfolio_url']) : '';
        $artist_statement = isset($_POST['artist_statement']) ? sanitize_textarea_field($_POST['artist_statement']) : '';
        $artist_bio = isset($_POST['artist_bio']) ? sanitize_textarea_field($_POST['artist_bio']) : '';
        
        if (empty($portfolio_url) || empty($artist_statement)) {
            wp_send_json_error(array(
                'message' => __('Portfolio URL and Artist Statement are required.', 'vortex-ai-marketplace')
            ));
        }
        
        // Store verification data
        update_user_meta($user_id, 'vortex_artist_portfolio_url', $portfolio_url);
        update_user_meta($user_id, 'vortex_artist_statement', $artist_statement);
        update_user_meta($user_id, 'vortex_artist_bio', $artist_bio);
        update_user_meta($user_id, 'vortex_artist_verification_status', 'pending');
        update_user_meta($user_id, 'vortex_artist_verification_submitted', current_time('mysql'));
        
        // Handle portfolio files if submitted
        if (!empty($_FILES['portfolio_files'])) {
            $this->handle_portfolio_uploads($user_id);
        }
        
        // Notify admin of new verification request
        $this->notify_admins_of_verification_request($user_id);
        
        // Perform AI-assisted pre-verification
        $this->perform_ai_preverification($user_id, $portfolio_url, $artist_statement, $artist_bio);
        
        wp_send_json_success(array(
            'message' => __('Your artist verification request has been submitted and is pending review.', 'vortex-ai-marketplace')
        ));
    }
    
    /**
     * Handle portfolio file uploads
     *
     * @param int $user_id User ID
     * @return void
     */
    private function handle_portfolio_uploads($user_id) {
        // Create portfolio directory if it doesn't exist
        $upload_dir = wp_upload_dir();
        $portfolio_dir = $upload_dir['basedir'] . '/artist-portfolios/' . $user_id;
        
        if (!file_exists($portfolio_dir)) {
            wp_mkdir_p($portfolio_dir);
        }
        
        // Process each uploaded file
        $files = $_FILES['portfolio_files'];
        $portfolio_files = array();
        
        for ($i = 0; $i < count($files['name']); $i++) {
            // Skip if there was an upload error
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }
            
            // Sanitize file name
            $file_name = sanitize_file_name($files['name'][$i]);
            $file_tmp = $files['tmp_name'][$i];
            
            // Move uploaded file
            $file_path = $portfolio_dir . '/' . $file_name;
            if (move_uploaded_file($file_tmp, $file_path)) {
                $portfolio_files[] = array(
                    'name' => $file_name,
                    'url' => $upload_dir['baseurl'] . '/artist-portfolios/' . $user_id . '/' . $file_name
                );
            }
        }
        
        // Save portfolio files meta
        if (!empty($portfolio_files)) {
            update_user_meta($user_id, 'vortex_artist_portfolio_files', $portfolio_files);
        }
    }
    
    /**
     * Notify admins of new verification request
     *
     * @param int $user_id User ID
     * @return void
     */
    private function notify_admins_of_verification_request($user_id) {
        $user = get_user_by('id', $user_id);
        $admin_email = get_option('admin_email');
        
        $subject = sprintf(__('[%s] New Artist Verification Request', 'vortex-ai-marketplace'), get_bloginfo('name'));
        
        $message = sprintf(
            __('A new artist verification request has been submitted by %s (User ID: %d).', 'vortex-ai-marketplace'),
            $user->display_name,
            $user_id
        );
        
        $message .= "\n\n";
        $message .= __('Please review this request in the WordPress admin panel.', 'vortex-ai-marketplace');
        $message .= "\n";
        $message .= admin_url('users.php?page=vortex-artist-verification');
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Perform AI-assisted pre-verification
     *
     * @param int $user_id User ID
     * @param string $portfolio_url Portfolio URL
     * @param string $artist_statement Artist statement
     * @param string $artist_bio Artist bio
     * @return void
     */
    private function perform_ai_preverification($user_id, $portfolio_url, $artist_statement, $artist_bio) {
        // Get AI agent to assist with verification
        $verification_data = array(
            'portfolio_url' => $portfolio_url,
            'artist_statement' => $artist_statement,
            'artist_bio' => $artist_bio
        );
        
        // Apply AI filter for verification assistance
        $ai_verification = apply_filters('vortex_ai_verify_artist', array(), $verification_data);
        
        // Store AI verification results
        if (!empty($ai_verification)) {
            update_user_meta($user_id, 'vortex_artist_ai_verification', $ai_verification);
        }
    }
    
    /**
     * AI verification assistance
     *
     * @param array $results Default empty results
     * @param array $data Verification data
     * @return array Verification analysis results
     */
    public function ai_verification_assistance($results, $data) {
        // Check if CLOE is available
        if (!class_exists('Vortex_CLOE')) {
            return $results;
        }
        
        // Prepare query for CLOE
        $query = sprintf(
            'Analyze this artist profile for verification. Portfolio: %s. Statement: %s. Bio: %s',
            $data['portfolio_url'],
            $data['artist_statement'],
            $data['artist_bio']
        );
        
        // Get CLOE agent
        $cloe = Vortex_CLOE::get_instance();
        
        // Process query with CLOE
        $analysis = $cloe->process_query($query, 'artist_verification');
        
        if (!empty($analysis['response'])) {
            // Extract verification confidence
            preg_match('/confidence:\s*(\d+)%/i', $analysis['response'], $matches);
            $confidence = isset($matches[1]) ? intval($matches[1]) : 70;
            
            // Extract verification recommendation
            preg_match('/recommendation:\s*(approve|reject|review)/i', $analysis['response'], $rec_matches);
            $recommendation = isset($rec_matches[1]) ? $rec_matches[1] : 'review';
            
            return array(
                'confidence' => $confidence,
                'recommendation' => $recommendation,
                'analysis' => $analysis['response']
            );
        }
        
        return $results;
    }
    
    /**
     * Add verification meta box to user profile
     *
     * @param WP_User $user User object
     * @return void
     */
    public function add_verification_meta_box($user) {
        // Check if current user can edit this user
        if (!current_user_can('edit_user', $user->ID)) {
            return;
        }
        
        // Get verification status
        $verification = $this->get_verification_status($user->ID);
        $portfolio_url = get_user_meta($user->ID, 'vortex_artist_portfolio_url', true);
        $artist_statement = get_user_meta($user->ID, 'vortex_artist_statement', true);
        $artist_bio = get_user_meta($user->ID, 'vortex_artist_bio', true);
        $ai_verification = get_user_meta($user->ID, 'vortex_artist_ai_verification', true);
        
        // Include template
        include plugin_dir_path(dirname(__FILE__)) . 'admin/partials/artist-verification-profile.php';
    }
    
    /**
     * Save verification meta box data
     *
     * @param int $user_id User ID
     * @return void
     */
    public function save_verification_meta_box($user_id) {
        // Check if current user can edit this user
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }
        
        // Verify nonce
        if (!isset($_POST['vortex_verification_nonce']) || !wp_verify_nonce($_POST['vortex_verification_nonce'], 'vortex_verification')) {
            return;
        }
        
        // Update verification status
        if (isset($_POST['vortex_artist_verified'])) {
            update_user_meta($user_id, 'vortex_artist_verified', $_POST['vortex_artist_verified']);
            
            // If approved, update status and add artist role
            if ($_POST['vortex_artist_verified'] === 'yes') {
                update_user_meta($user_id, 'vortex_artist_verification_status', 'approved');
                
                // Add artist role
                $user = get_user_by('id', $user_id);
                $user->add_role('vortex_artist');
                
                // Send approval notification
                $this->send_verification_notification($user_id, 'approved');
            } 
            // If rejected, update status and reason
            elseif ($_POST['vortex_artist_verified'] === 'no' && isset($_POST['vortex_rejection_reason'])) {
                update_user_meta($user_id, 'vortex_artist_verification_status', 'rejected');
                update_user_meta($user_id, 'vortex_artist_verification_rejection', sanitize_textarea_field($_POST['vortex_rejection_reason']));
                
                // Send rejection notification
                $this->send_verification_notification($user_id, 'rejected');
            }
        }
    }
    
    /**
     * Send verification notification to user
     *
     * @param int $user_id User ID
     * @param string $status Status (approved/rejected)
     * @return void
     */
    private function send_verification_notification($user_id, $status) {
        $user = get_user_by('id', $user_id);
        
        if ($status === 'approved') {
            $subject = sprintf(__('[%s] Artist Verification Approved', 'vortex-ai-marketplace'), get_bloginfo('name'));
            
            $message = sprintf(
                __('Congratulations %s! Your artist verification request has been approved.', 'vortex-ai-marketplace'),
                $user->display_name
            );
            
            $message .= "\n\n";
            $message .= __('You can now participate in artist artwork swaps on our platform.', 'vortex-ai-marketplace');
            $message .= "\n";
            $message .= get_permalink(get_option('vortex_artwork_swap_page'));
        } else {
            $subject = sprintf(__('[%s] Artist Verification Update', 'vortex-ai-marketplace'), get_bloginfo('name'));
            
            $message = sprintf(
                __('Hello %s, We have reviewed your artist verification request.', 'vortex-ai-marketplace'),
                $user->display_name
            );
            
            $message .= "\n\n";
            $rejection_reason = get_user_meta($user_id, 'vortex_artist_verification_rejection', true);
            $message .= __('Unfortunately, we cannot approve your request at this time for the following reason:', 'vortex-ai-marketplace');
            $message .= "\n\n";
            $message .= $rejection_reason;
            $message .= "\n\n";
            $message .= __('You can update your information and submit a new verification request.', 'vortex-ai-marketplace');
        }
        
        wp_mail($user->user_email, $subject, $message);
    }
    
    /**
     * Check verification status
     */
    public function check_verification_status() {
        check_ajax_referer('vortex_verification_nonce', 'nonce');
        
        $user_id = get_current_user_id();
        $status = $this->get_verification_status($user_id);
        
        wp_send_json_success($status);
    }
    
    /**
     * Handle verification review (admin)
     */
    public function handle_verification_review() {
        check_ajax_referer('vortex_admin_nonce', 'nonce');
        
        // Check admin permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to perform this action.', 'vortex-ai-marketplace')
            ));
        }
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $action = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
        $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';
        
        if (!$user_id || !in_array($action, array('approve', 'reject'))) {
            wp_send_json_error(array(
                'message' => __('Invalid request.', 'vortex-ai-marketplace')
            ));
        }
        
        if ($action === 'approve') {
            // Approve artist
            update_user_meta($user_id, 'vortex_artist_verified', 'yes');
            update_user_meta($user_id, 'vortex_artist_verification_status', 'approved');
            
            // Add artist role
            $user = get_user_by('id', $user_id);
            $user->add_role('vortex_artist');
            
            // Send approval notification
            $this->send_verification_notification($user_id, 'approved');
            
            wp_send_json_success(array(
                'message' => __('Artist has been approved.', 'vortex-ai-marketplace')
            ));
        } else {
            // Reject artist
            update_user_meta($user_id, 'vortex_artist_verified', 'no');
            update_user_meta($user_id, 'vortex_artist_verification_status', 'rejected');
            update_user_meta($user_id, 'vortex_artist_verification_rejection', $reason);
            
            // Send rejection notification
            $this->send_verification_notification($user_id, 'rejected');
            
            wp_send_json_success(array(
                'message' => __('Artist verification has been rejected.', 'vortex-ai-marketplace')
            ));
        }
    }
} 