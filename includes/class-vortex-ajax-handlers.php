<?php
/**
 * AJAX handlers for Vortex AI Marketplace
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

class Vortex_AJAX_Handlers {
    
    /**
     * Initialize the AJAX handlers
     */
    public static function init() {
        // Event handlers
        add_action('wp_ajax_vortex_create_event', array(__CLASS__, 'handle_create_event'));
        add_action('wp_ajax_vortex_register_event', array(__CLASS__, 'handle_register_event'));
        add_action('wp_ajax_vortex_cancel_event_registration', array(__CLASS__, 'handle_cancel_event_registration'));
        
        // Offer handlers
        add_action('wp_ajax_vortex_create_offer', array(__CLASS__, 'handle_create_offer'));
        add_action('wp_ajax_vortex_respond_to_offer', array(__CLASS__, 'handle_respond_to_offer'));
        add_action('wp_ajax_vortex_cancel_offer', array(__CLASS__, 'handle_cancel_offer'));
        
        // Collaboration handlers
        add_action('wp_ajax_vortex_create_collaboration', array(__CLASS__, 'handle_create_collaboration'));
        add_action('wp_ajax_vortex_join_collaboration', array(__CLASS__, 'handle_join_collaboration'));
        add_action('wp_ajax_vortex_leave_collaboration', array(__CLASS__, 'handle_leave_collaboration'));
        
        // Swiping handler
        add_action('wp_ajax_vortex_handle_swipe', array(__CLASS__, 'handle_swipe'));
    }
    
    /**
     * Handle event creation
     */
    public static function handle_create_event() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['event_nonce'] ?? '', 'vortex_create_event')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'vortex-ai-marketplace')
            ));
        }
        
        // Verify user is logged in
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to create an event.', 'vortex-ai-marketplace')
            ));
        }
        
        // Validate input
        $required_fields = array(
            'event_title',
            'event_description',
            'event_date',
            'event_location',
            'event_capacity',
            'event_price'
        );
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(array(
                    'message' => sprintf(__('Please fill in all required fields. Missing: %s', 'vortex-ai-marketplace'), $field)
                ));
            }
        }
        
        try {
            // Create event post
            $event_data = array(
                'post_title' => sanitize_text_field($_POST['event_title']),
                'post_content' => wp_kses_post($_POST['event_description']),
                'post_type' => 'vortex_event',
                'post_status' => 'publish',
                'post_author' => $user_id
            );
            
            $event_id = wp_insert_post($event_data);
            if (is_wp_error($event_id)) {
                throw new Exception($event_id->get_error_message());
            }
            
            // Save event meta
            $meta_fields = array(
                'event_date' => sanitize_text_field($_POST['event_date']),
                'event_location' => sanitize_text_field($_POST['event_location']),
                'event_capacity' => intval($_POST['event_capacity']),
                'event_price' => floatval($_POST['event_price'])
            );
            
            foreach ($meta_fields as $key => $value) {
                update_post_meta($event_id, $key, $value);
            }
            
            // Handle event image
            if (!empty($_FILES['event_image'])) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                
                $attachment_id = media_handle_upload('event_image', $event_id);
                if (!is_wp_error($attachment_id)) {
                    set_post_thumbnail($event_id, $attachment_id);
                }
            }
            
            wp_send_json_success(array(
                'message' => __('Event created successfully.', 'vortex-ai-marketplace'),
                'redirect_url' => get_permalink($event_id)
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }
    
    /**
     * Handle event registration
     */
    public static function handle_register_event() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vortex_register_event')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'vortex-ai-marketplace')
            ));
        }
        
        // Verify user is logged in
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to register for an event.', 'vortex-ai-marketplace')
            ));
        }
        
        // Validate input
        $event_id = intval($_POST['event_id'] ?? 0);
        if (!$event_id) {
            wp_send_json_error(array(
                'message' => __('Invalid event ID.', 'vortex-ai-marketplace')
            ));
        }
        
        try {
            // Check if event exists and is published
            $event = get_post($event_id);
            if (!$event || $event->post_type !== 'vortex_event' || $event->post_status !== 'publish') {
                throw new Exception(__('Event not found.', 'vortex-ai-marketplace'));
            }
            
            // Check if event is full
            $current_registrations = self::get_event_registrations_count($event_id);
            $capacity = intval(get_post_meta($event_id, 'event_capacity', true));
            
            if ($current_registrations >= $capacity) {
                throw new Exception(__('This event is full.', 'vortex-ai-marketplace'));
            }
            
            // Check if user is already registered
            if (self::is_user_registered_for_event($event_id, $user_id)) {
                throw new Exception(__('You are already registered for this event.', 'vortex-ai-marketplace'));
            }
            
            // Get event price
            $price = floatval(get_post_meta($event_id, 'event_price', true));
            
            // Check user's TOLA balance
            $user_balance = vortex_get_user_tola_balance($user_id);
            if ($user_balance < $price) {
                throw new Exception(__('Insufficient TOLA balance.', 'vortex-ai-marketplace'));
            }
            
            // Process payment
            $payment_result = vortex_process_tola_payment($user_id, $price);
            if (!$payment_result['success']) {
                throw new Exception($payment_result['message']);
            }
            
            // Register user for event
            global $wpdb;
            $result = $wpdb->insert(
                $wpdb->prefix . 'vortex_event_registrations',
                array(
                    'event_id' => $event_id,
                    'user_id' => $user_id,
                    'registration_date' => current_time('mysql'),
                    'status' => 'confirmed',
                    'payment_status' => 'completed',
                    'payment_amount' => $price,
                    'payment_currency' => 'TOLA',
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ),
                array('%d', '%d', '%s', '%s', '%s', '%f', '%s', '%s', '%s')
            );
            
            if ($result === false) {
                throw new Exception(__('Failed to register for event.', 'vortex-ai-marketplace'));
            }
            
            wp_send_json_success(array(
                'message' => __('Successfully registered for event.', 'vortex-ai-marketplace')
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }
    
    /**
     * Handle offer creation
     */
    public static function handle_create_offer() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['offer_nonce'] ?? '', 'vortex_create_offer')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'vortex-ai-marketplace')
            ));
        }
        
        // Verify user is logged in
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to create an offer.', 'vortex-ai-marketplace')
            ));
        }
        
        // Validate input
        $required_fields = array(
            'offer_title',
            'offer_description',
            'offer_type',
            'offer_amount',
            'offer_deadline',
            'offer_terms'
        );
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(array(
                    'message' => sprintf(__('Please fill in all required fields. Missing: %s', 'vortex-ai-marketplace'), $field)
                ));
            }
        }
        
        try {
            // Create offer post
            $offer_data = array(
                'post_title' => sanitize_text_field($_POST['offer_title']),
                'post_content' => wp_kses_post($_POST['offer_description']),
                'post_type' => 'vortex_offer',
                'post_status' => 'publish',
                'post_author' => $user_id
            );
            
            $offer_id = wp_insert_post($offer_data);
            if (is_wp_error($offer_id)) {
                throw new Exception($offer_id->get_error_message());
            }
            
            // Save offer meta
            $meta_fields = array(
                'offer_type' => sanitize_text_field($_POST['offer_type']),
                'offer_amount' => floatval($_POST['offer_amount']),
                'offer_deadline' => sanitize_text_field($_POST['offer_deadline']),
                'offer_terms' => wp_kses_post($_POST['offer_terms'])
            );
            
            foreach ($meta_fields as $key => $value) {
                update_post_meta($offer_id, $key, $value);
            }
            
            // Handle offer attachments
            if (!empty($_FILES['offer_attachments'])) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                
                $attachment_ids = array();
                foreach ($_FILES['offer_attachments']['name'] as $key => $value) {
                    if ($_FILES['offer_attachments']['error'][$key] === 0) {
                        $file = array(
                            'name' => $_FILES['offer_attachments']['name'][$key],
                            'type' => $_FILES['offer_attachments']['type'][$key],
                            'tmp_name' => $_FILES['offer_attachments']['tmp_name'][$key],
                            'error' => $_FILES['offer_attachments']['error'][$key],
                            'size' => $_FILES['offer_attachments']['size'][$key]
                        );
                        
                        $attachment_id = media_handle_sideload($file, $offer_id);
                        if (!is_wp_error($attachment_id)) {
                            $attachment_ids[] = $attachment_id;
                        }
                    }
                }
                
                if (!empty($attachment_ids)) {
                    update_post_meta($offer_id, 'offer_attachments', $attachment_ids);
                }
            }
            
            wp_send_json_success(array(
                'message' => __('Offer created successfully.', 'vortex-ai-marketplace'),
                'redirect_url' => get_permalink($offer_id)
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }
    
    /**
     * Handle collaboration creation
     */
    public static function handle_create_collaboration() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['collaboration_nonce'] ?? '', 'vortex_create_collaboration')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'vortex-ai-marketplace')
            ));
        }
        
        // Verify user is logged in
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to create a collaboration.', 'vortex-ai-marketplace')
            ));
        }
        
        // Validate input
        $required_fields = array(
            'collaboration_title',
            'collaboration_description',
            'collaboration_type',
            'collaboration_budget',
            'collaboration_deadline',
            'collaboration_requirements'
        );
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error(array(
                    'message' => sprintf(__('Please fill in all required fields. Missing: %s', 'vortex-ai-marketplace'), $field)
                ));
            }
        }
        
        if (empty($_POST['collaboration_roles'])) {
            wp_send_json_error(array(
                'message' => __('Please select at least one required role.', 'vortex-ai-marketplace')
            ));
        }
        
        try {
            // Create collaboration post
            $collaboration_data = array(
                'post_title' => sanitize_text_field($_POST['collaboration_title']),
                'post_content' => wp_kses_post($_POST['collaboration_description']),
                'post_type' => 'vortex_collaboration',
                'post_status' => 'publish',
                'post_author' => $user_id
            );
            
            $collaboration_id = wp_insert_post($collaboration_data);
            if (is_wp_error($collaboration_id)) {
                throw new Exception($collaboration_id->get_error_message());
            }
            
            // Save collaboration meta
            $meta_fields = array(
                'collaboration_type' => sanitize_text_field($_POST['collaboration_type']),
                'collaboration_budget' => floatval($_POST['collaboration_budget']),
                'collaboration_deadline' => sanitize_text_field($_POST['collaboration_deadline']),
                'collaboration_requirements' => wp_kses_post($_POST['collaboration_requirements']),
                'collaboration_roles' => array_map('sanitize_text_field', $_POST['collaboration_roles'])
            );
            
            foreach ($meta_fields as $key => $value) {
                update_post_meta($collaboration_id, $key, $value);
            }
            
            // Handle collaboration attachments
            if (!empty($_FILES['collaboration_attachments'])) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                
                $attachment_ids = array();
                foreach ($_FILES['collaboration_attachments']['name'] as $key => $value) {
                    if ($_FILES['collaboration_attachments']['error'][$key] === 0) {
                        $file = array(
                            'name' => $_FILES['collaboration_attachments']['name'][$key],
                            'type' => $_FILES['collaboration_attachments']['type'][$key],
                            'tmp_name' => $_FILES['collaboration_attachments']['tmp_name'][$key],
                            'error' => $_FILES['collaboration_attachments']['error'][$key],
                            'size' => $_FILES['collaboration_attachments']['size'][$key]
                        );
                        
                        $attachment_id = media_handle_sideload($file, $collaboration_id);
                        if (!is_wp_error($attachment_id)) {
                            $attachment_ids[] = $attachment_id;
                        }
                    }
                }
                
                if (!empty($attachment_ids)) {
                    update_post_meta($collaboration_id, 'collaboration_attachments', $attachment_ids);
                }
            }
            
            // Add creator as first member
            global $wpdb;
            $wpdb->insert(
                $wpdb->prefix . 'vortex_collaboration_members',
                array(
                    'collaboration_id' => $collaboration_id,
                    'user_id' => $user_id,
                    'role' => 'creator',
                    'join_date' => current_time('mysql'),
                    'status' => 'active',
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ),
                array('%d', '%d', '%s', '%s', '%s', '%s', '%s')
            );
            
            wp_send_json_success(array(
                'message' => __('Collaboration created successfully.', 'vortex-ai-marketplace'),
                'redirect_url' => get_permalink($collaboration_id)
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }
    
    /**
     * Get event registrations count
     */
    private static function get_event_registrations_count($event_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_event_registrations 
            WHERE event_id = %d AND status = 'confirmed'",
            $event_id
        ));
    }
    
    /**
     * Check if user is registered for event
     */
    private static function is_user_registered_for_event($event_id, $user_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_event_registrations 
            WHERE event_id = %d AND user_id = %d AND status = 'confirmed'",
            $event_id,
            $user_id
        )) > 0;
    }
    
    /**
     * Handle swipe action
     */
    public static function handle_swipe() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vortex_handle_swipe')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'vortex-ai-marketplace')
            ));
        }
        
        // Verify user is logged in
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to perform this action.', 'vortex-ai-marketplace')
            ));
        }
        
        // Validate input
        $item_id = intval($_POST['item_id'] ?? 0);
        $swipe_action = sanitize_text_field($_POST['swipe_action'] ?? '');
        
        if (!$item_id) {
            wp_send_json_error(array(
                'message' => __('Invalid item ID.', 'vortex-ai-marketplace')
            ));
        }
        
        if (!in_array($swipe_action, array('accept', 'reject'))) {
            wp_send_json_error(array(
                'message' => __('Invalid swipe action.', 'vortex-ai-marketplace')
            ));
        }
        
        try {
            // Check if item exists and is published
            $item = get_post($item_id);
            if (!$item || $item->post_type !== 'vortex_item' || $item->post_status !== 'publish') {
                throw new Exception(__('Item not found.', 'vortex-ai-marketplace'));
            }
            
            // Track swipe statistics
            self::track_swipe_statistics($item_id, $swipe_action);
            
            // Process swipe action
            if ($swipe_action === 'accept') {
                // Add item to user's collection
                $user_collection = get_user_meta($user_id, 'vortex_collection', true);
                if (!is_array($user_collection)) {
                    $user_collection = array();
                }
                
                if (!in_array($item_id, $user_collection)) {
                    $user_collection[] = $item_id;
                    update_user_meta($user_id, 'vortex_collection', $user_collection);
                    
                    // Increment collection count
                    $collection_count = get_post_meta($item_id, 'vortex_collection_count', true);
                    update_post_meta($item_id, 'vortex_collection_count', intval($collection_count) + 1);
                    
                    // Increment total collections
                    $total_collections = get_option('vortex_total_collections', 0);
                    update_option('vortex_total_collections', intval($total_collections) + 1);
                }
            }
            
            wp_send_json_success(array(
                'message' => __('Swipe action processed successfully.', 'vortex-ai-marketplace')
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }
    
    /**
     * Track swipe statistics
     */
    private static function track_swipe_statistics($item_id, $swipe_action) {
        // Increment swipe count for item
        $swipe_count = get_post_meta($item_id, 'vortex_swipe_count', true);
        update_post_meta($item_id, 'vortex_swipe_count', intval($swipe_count) + 1);
        
        // Increment total swipes
        $total_swipes = get_option('vortex_total_swipes', 0);
        update_option('vortex_total_swipes', intval($total_swipes) + 1);
        
        // Track action-specific counts
        if ($swipe_action === 'accept') {
            $accept_count = get_post_meta($item_id, 'vortex_accept_count', true);
            update_post_meta($item_id, 'vortex_accept_count', intval($accept_count) + 1);
        } else {
            $reject_count = get_post_meta($item_id, 'vortex_reject_count', true);
            update_post_meta($item_id, 'vortex_reject_count', intval($reject_count) + 1);
        }
        
        // Calculate and update acceptance rate
        $accept_count = intval(get_post_meta($item_id, 'vortex_accept_count', true));
        $swipe_count = intval(get_post_meta($item_id, 'vortex_swipe_count', true));
        if ($swipe_count > 0) {
            $acceptance_rate = round(($accept_count / $swipe_count) * 100, 2);
            update_post_meta($item_id, 'vortex_acceptance_rate', $acceptance_rate);
        }
    }
    
    /**
     * Handle join collaboration request
     */
    public static function handle_join_collaboration() {
        // Verify nonce
        check_ajax_referer('vortex_career_project_nonce', 'nonce');
        
        // Verify user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to join a collaboration.', 'vortex-ai-marketplace')
            ));
        }
        
        // Get user ID
        $user_id = get_current_user_id();
        
        // Validate required fields
        $collaboration_id = isset($_POST['collaboration_id']) ? intval($_POST['collaboration_id']) : 0;
        $requested_role = isset($_POST['requested_role']) ? sanitize_text_field($_POST['requested_role']) : '';
        $request_message = isset($_POST['request_message']) ? sanitize_textarea_field($_POST['request_message']) : '';
        
        if (empty($collaboration_id) || empty($requested_role) || empty($request_message)) {
            wp_send_json_error(array(
                'message' => __('Please fill in all required fields.', 'vortex-ai-marketplace')
            ));
        }
        
        // Check if collaboration exists
        $collaboration = get_post($collaboration_id);
        if (!$collaboration || $collaboration->post_type !== 'vortex_collaboration' || $collaboration->post_status !== 'publish') {
            wp_send_json_error(array(
                'message' => __('Collaboration not found.', 'vortex-ai-marketplace')
            ));
        }
        
        // Check if user is already a member
        if (self::is_user_collaboration_member($collaboration_id, $user_id)) {
            wp_send_json_error(array(
                'message' => __('You are already a member of this collaboration.', 'vortex-ai-marketplace')
            ));
        }
        
        // Check if user already has a pending request
        if (self::has_user_pending_collaboration_request($collaboration_id, $user_id)) {
            wp_send_json_error(array(
                'message' => __('You already have a pending request for this collaboration.', 'vortex-ai-marketplace')
            ));
        }
        
        try {
            // Submit join request
            global $wpdb;
            $result = $wpdb->insert(
                $wpdb->prefix . 'vortex_collaboration_requests',
                array(
                    'collaboration_id' => $collaboration_id,
                    'user_id' => $user_id,
                    'request_date' => current_time('mysql'),
                    'requested_role' => $requested_role,
                    'request_status' => 'pending',
                    'request_message' => $request_message,
                    'created_at' => current_time('mysql'),
                    'updated_at' => current_time('mysql')
                ),
                array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s')
            );
            
            if ($result === false) {
                throw new Exception(__('Failed to submit join request.', 'vortex-ai-marketplace'));
            }
            
            // Send notification to collaboration creator
            $creator_id = $collaboration->post_author;
            if ($creator_id != $user_id) {
                $notification_message = sprintf(
                    __('User %s has requested to join your collaboration "%s" as a %s.', 'vortex-ai-marketplace'),
                    get_the_author_meta('display_name', $user_id),
                    $collaboration->post_title,
                    $requested_role
                );
                
                // Add notification for the creator
                if (function_exists('vortex_add_notification')) {
                    vortex_add_notification($creator_id, 'collaboration_request', $notification_message, $collaboration_id);
                }
            }
            
            wp_send_json_success(array(
                'message' => __('Your request to join the collaboration has been submitted successfully.', 'vortex-ai-marketplace')
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }
    
    /**
     * Check if user is a member of a collaboration
     */
    public static function is_user_collaboration_member($collaboration_id, $user_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_collaboration_members 
            WHERE collaboration_id = %d AND user_id = %d AND status = 'active'",
            $collaboration_id,
            $user_id
        )) > 0;
    }
    
    /**
     * Check if user has a pending request for a collaboration
     */
    public static function has_user_pending_collaboration_request($collaboration_id, $user_id) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_collaboration_requests 
            WHERE collaboration_id = %d AND user_id = %d AND request_status = 'pending'",
            $collaboration_id,
            $user_id
        )) > 0;
    }
    
    /**
     * Handle leave collaboration
     */
    public static function handle_leave_collaboration() {
        // Verify nonce
        check_ajax_referer('vortex_career_project_nonce', 'nonce');
        
        // Verify user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to leave a collaboration.', 'vortex-ai-marketplace')
            ));
        }
        
        // Get user ID
        $user_id = get_current_user_id();
        
        // Validate input
        $collaboration_id = isset($_POST['collaboration_id']) ? intval($_POST['collaboration_id']) : 0;
        
        if (empty($collaboration_id)) {
            wp_send_json_error(array(
                'message' => __('Invalid collaboration ID.', 'vortex-ai-marketplace')
            ));
        }
        
        // Check if user is a member
        if (!self::is_user_collaboration_member($collaboration_id, $user_id)) {
            wp_send_json_error(array(
                'message' => __('You are not a member of this collaboration.', 'vortex-ai-marketplace')
            ));
        }
        
        try {
            // Update user membership status to 'inactive'
            global $wpdb;
            $result = $wpdb->update(
                $wpdb->prefix . 'vortex_collaboration_members',
                array(
                    'status' => 'inactive',
                    'updated_at' => current_time('mysql')
                ),
                array(
                    'collaboration_id' => $collaboration_id,
                    'user_id' => $user_id
                ),
                array('%s', '%s'),
                array('%d', '%d')
            );
            
            if ($result === false) {
                throw new Exception(__('Failed to leave collaboration.', 'vortex-ai-marketplace'));
            }
            
            // Get collaboration details for notification
            $collaboration = get_post($collaboration_id);
            $creator_id = $collaboration->post_author;
            
            // Send notification to collaboration creator
            if ($creator_id != $user_id) {
                $notification_message = sprintf(
                    __('User %s has left your collaboration "%s".', 'vortex-ai-marketplace'),
                    get_the_author_meta('display_name', $user_id),
                    $collaboration->post_title
                );
                
                // Add notification for the creator
                if (function_exists('vortex_add_notification')) {
                    vortex_add_notification($creator_id, 'collaboration_leave', $notification_message, $collaboration_id);
                }
            }
            
            wp_send_json_success(array(
                'message' => __('You have successfully left the collaboration.', 'vortex-ai-marketplace')
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }
} 