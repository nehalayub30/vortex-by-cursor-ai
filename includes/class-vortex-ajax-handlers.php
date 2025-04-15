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
        
        // User actions
        add_action('wp_ajax_vortex_artist_verification', array(__CLASS__, 'artist_verification'));
        add_action('wp_ajax_vortex_submit_artwork', array(__CLASS__, 'submit_artwork'));
        add_action('wp_ajax_vortex_like_artwork', array(__CLASS__, 'like_artwork'));
        add_action('wp_ajax_vortex_share_artwork', array(__CLASS__, 'share_artwork'));
        add_action('wp_ajax_vortex_follow_artist', array(__CLASS__, 'follow_artist'));
        
        // Marketplace actions
        add_action('wp_ajax_vortex_get_artwork', array(__CLASS__, 'get_artwork'));
        add_action('wp_ajax_nopriv_vortex_get_artwork', array(__CLASS__, 'get_artwork'));
        add_action('wp_ajax_vortex_purchase_artwork', array(__CLASS__, 'purchase_artwork'));
        add_action('wp_ajax_vortex_auction_bid', array(__CLASS__, 'auction_bid'));
        
        // Admin actions
        add_action('wp_ajax_vortex_admin_metrics', array(__CLASS__, 'admin_metrics'));
        add_action('wp_ajax_vortex_admin_user_management', array(__CLASS__, 'admin_user_management'));
        add_action('wp_ajax_vortex_admin_artwork_approval', array(__CLASS__, 'admin_artwork_approval'));
        add_action('wp_ajax_vortex_update_database', array(__CLASS__, 'update_database'));
        
        // TOLA token actions
        add_action('wp_ajax_vortex_get_token_balance', array(__CLASS__, 'get_token_balance'));
        add_action('wp_ajax_vortex_transfer_tokens', array(__CLASS__, 'transfer_tokens'));

        // Search handler
        add_action('wp_ajax_vortex_search', array(__CLASS__, 'handle_search'));
        add_action('wp_ajax_nopriv_vortex_search', array(__CLASS__, 'handle_search'));

        // Artwork theme handlers
        add_action('wp_ajax_vortex_artwork_theme_association', array(__CLASS__, 'handle_artwork_theme_association'));
        add_action('wp_ajax_vortex_get_available_themes', array(__CLASS__, 'get_available_themes'));
        add_action('wp_ajax_nopriv_vortex_get_available_themes', array(__CLASS__, 'get_available_themes'));
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

    /**
     * Handle database update AJAX request
     */
    public static function update_database() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_update_database_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed. Please refresh the page and try again.', 'vortex-ai-marketplace')));
        }
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('You do not have permission to perform this action.', 'vortex-ai-marketplace')));
        }
        
        // Run the database update
        require_once VORTEX_PLUGIN_DIR . 'includes/class-vortex-db-migrations.php';
        $db_migration = new \Vortex_DB_Migrations();
        $db_migration->setup_database();
        
        // Update the database version
        update_option('vortex_ai_db_version', VORTEX_VERSION);
        
        wp_send_json_success(array('message' => __('Database tables have been created or updated successfully.', 'vortex-ai-marketplace')));
    }

    /**
     * Handle database errors
     *
     * @param string $error_message The error message
     * @param string $table_name The name of the table causing issues
     * @return boolean True if error was fixed, false otherwise
     */
    public static function handle_db_error($error_message, $table_name) {
        // First, check if this is a missing table error
        if (strpos($error_message, "Table") !== false && strpos($error_message, "doesn't exist") !== false) {
            // Try to fix the specific table
            $table_basename = str_replace($GLOBALS['wpdb']->prefix, '', $table_name);
            
            if ($table_basename === 'vortex_searches') {
                // Fix searches table
                require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-db-migrations.php';
                return Vortex_DB_Migrations::ensure_searches_table();
            } elseif ($table_basename === 'vortex_transactions') {
                // Fix transactions table
                require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-db-migrations.php';
                return Vortex_DB_Migrations::ensure_transactions_table();
            } elseif ($table_basename === 'vortex_tags') {
                // Fix tags table
                require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-db-migrations.php';
                return Vortex_DB_Migrations::ensure_tags_table();
            } elseif ($table_basename === 'vortex_artwork_tags') {
                // Fix artwork tags table
                require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-db-migrations.php';
                return Vortex_DB_Migrations::ensure_artwork_tags_table();
            } elseif ($table_basename === 'vortex_art_styles') {
                // Fix art styles table
                require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-db-migrations.php';
                return Vortex_DB_Migrations::ensure_art_styles_table();
            } elseif ($table_basename === 'vortex_artwork_themes') {
                // Fix artwork themes table
                require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-db-migrations.php';
                return Vortex_DB_Migrations::ensure_artwork_themes_table();
            } elseif ($table_basename === 'vortex_artwork_theme_mapping') {
                // Fix artwork theme mapping table
                require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-db-migrations.php';
                return Vortex_DB_Migrations::ensure_artwork_theme_mapping_table();
            } else {
                // For other tables, try the general repair
                require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-db-repair.php';
                $db_repair = VORTEX_DB_Repair::get_instance();
                $repaired = $db_repair->repair_specific_table($table_basename);
                return !empty($repaired);
            }
        }
        
        // Log the error for debugging
        error_log("VORTEX DB Error: $error_message");
        
        return false;
    }

    /**
     * Handle AJAX search request
     */
    public static function handle_search() {
        check_ajax_referer('vortex_search_nonce', 'nonce');
        
        $search_query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        
        if (empty($search_query)) {
            wp_send_json_error(array('message' => __('Please enter a search query.', 'vortex-ai-marketplace')));
            return;
        }
        
        global $wpdb;
        $searches_table = $wpdb->prefix . 'vortex_searches';
        
        // Record search query
        try {
            $user_id = is_user_logged_in() ? get_current_user_id() : NULL;
            $session_id = isset($_COOKIE['vortex_session']) ? sanitize_text_field($_COOKIE['vortex_session']) : NULL;
            
            // Get search filters if any
            $filters = isset($_POST['filters']) ? $_POST['filters'] : array();
            $filters_json = !empty($filters) ? json_encode($filters) : NULL;
            
            // Get user's IP and user agent
            $ip_address = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';
            $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';
            
            // Run the actual search
            $results = self::perform_search($search_query, $filters);
            
            // Record search in database
            $insert_result = $wpdb->insert(
                $searches_table,
                array(
                    'user_id' => $user_id,
                    'session_id' => $session_id,
                    'search_query' => $search_query,
                    'search_time' => current_time('mysql'),
                    'results_count' => count($results),
                    'search_filters' => $filters_json,
                    'ip_address' => $ip_address,
                    'user_agent' => $user_agent
                )
            );
            
            // If insertion failed, check if it's due to missing table
            if ($insert_result === false) {
                $db_error = $wpdb->last_error;
                if (strpos($db_error, "doesn't exist") !== false) {
                    // Try to fix the table
                    self::handle_db_error($db_error, $searches_table);
                    
                    // Try insertion again
                    $wpdb->insert(
                        $searches_table,
                        array(
                            'user_id' => $user_id,
                            'session_id' => $session_id,
                            'search_query' => $search_query,
                            'search_time' => current_time('mysql'),
                            'results_count' => count($results),
                            'search_filters' => $filters_json,
                            'ip_address' => $ip_address,
                            'user_agent' => $user_agent
                        )
                    );
                }
            }
            
            // Return search results to user
            wp_send_json_success(array(
                'results' => $results,
                'count' => count($results),
                'message' => sprintf(
                    _n('Found %d result for "%s"', 'Found %d results for "%s"', count($results), 'vortex-ai-marketplace'),
                    count($results),
                    $search_query
                )
            ));
            
        } catch (Exception $e) {
            error_log('VORTEX Search Error: ' . $e->getMessage());
            wp_send_json_error(array('message' => __('An error occurred while processing your search. Please try again.', 'vortex-ai-marketplace')));
        }
    }

    /**
     * Perform the actual search
     * 
     * @param string $query The search query
     * @param array $filters Any search filters
     * @return array Search results
     */
    private static function perform_search($query, $filters = array()) {
        // This is a simplified implementation - expand as needed
        $args = array(
            'post_type' => array('vortex_artwork', 'product'),
            's' => $query,
            'posts_per_page' => 20,
        );
        
        // Apply filters if provided
        if (!empty($filters)) {
            // Example: filter by category
            if (isset($filters['category']) && !empty($filters['category'])) {
                $args['tax_query'][] = array(
                    'taxonomy' => 'product_cat',
                    'field' => 'slug',
                    'terms' => $filters['category']
                );
            }
            
            // Example: filter by price range
            if (isset($filters['min_price']) && isset($filters['max_price'])) {
                $args['meta_query'][] = array(
                    'key' => '_price',
                    'value' => array($filters['min_price'], $filters['max_price']),
                    'type' => 'NUMERIC',
                    'compare' => 'BETWEEN'
                );
            }
        }
        
        $query = new WP_Query($args);
        $results = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                $results[] = array(
                    'id' => $post_id,
                    'title' => get_the_title(),
                    'url' => get_permalink(),
                    'thumbnail' => get_the_post_thumbnail_url($post_id, 'thumbnail'),
                    'type' => get_post_type(),
                    'excerpt' => get_the_excerpt(),
                    'price' => get_post_type() === 'product' ? get_post_meta($post_id, '_price', true) : null
                );
            }
            
            wp_reset_postdata();
        }
        
        return $results;
    }

    /**
     * Handle artwork theme associations
     */
    public static function handle_artwork_theme_association() {
        check_ajax_referer('vortex_artwork_theme_nonce', 'nonce');
        
        // Verify user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to perform this action.', 'vortex-ai-marketplace')));
            return;
        }
        
        $user_id = get_current_user_id();
        
        // Get parameters
        $artwork_id = isset($_POST['artwork_id']) ? intval($_POST['artwork_id']) : 0;
        $theme_ids = isset($_POST['theme_ids']) ? array_map('intval', (array)$_POST['theme_ids']) : array();
        $action = isset($_POST['theme_action']) ? sanitize_text_field($_POST['theme_action']) : 'add';
        
        if (!$artwork_id) {
            wp_send_json_error(array('message' => __('Invalid artwork ID.', 'vortex-ai-marketplace')));
            return;
        }
        
        // Initialize the HURAII library
        require_once VORTEX_PLUGIN_DIR . 'includes/class-vortex-huraii-library.php';
        $huraii_library = Vortex_HURAII_Library::get_instance();
        
        // Perform the action
        if ($action === 'add') {
            // Add themes to artwork
            if (empty($theme_ids)) {
                wp_send_json_error(array('message' => __('No themes specified.', 'vortex-ai-marketplace')));
                return;
            }
            
            $result = $huraii_library->associate_artwork_with_themes($artwork_id, $theme_ids, $user_id);
            
            if ($result['success']) {
                wp_send_json_success(array(
                    'message' => sprintf(__('%d themes associated with the artwork.', 'vortex-ai-marketplace'), $result['added']),
                    'added' => $result['added']
                ));
            } else {
                wp_send_json_error(array(
                    'message' => __('Failed to associate themes with the artwork.', 'vortex-ai-marketplace'),
                    'errors' => $result['errors']
                ));
            }
        } elseif ($action === 'remove') {
            // Remove theme from artwork
            if (empty($theme_ids) || count($theme_ids) !== 1) {
                wp_send_json_error(array('message' => __('Please specify exactly one theme to remove.', 'vortex-ai-marketplace')));
                return;
            }
            
            $theme_id = $theme_ids[0];
            $result = $huraii_library->remove_artwork_theme($artwork_id, $theme_id);
            
            if ($result) {
                wp_send_json_success(array('message' => __('Theme removed from artwork.', 'vortex-ai-marketplace')));
            } else {
                wp_send_json_error(array('message' => __('Failed to remove theme from artwork.', 'vortex-ai-marketplace')));
            }
        } elseif ($action === 'get') {
            // Get themes for artwork
            $themes = $huraii_library->get_artwork_themes($artwork_id);
            
            wp_send_json_success(array(
                'themes' => $themes,
                'count' => count($themes)
            ));
        } else {
            wp_send_json_error(array('message' => __('Invalid action.', 'vortex-ai-marketplace')));
        }
    }
    
    /**
     * Get available artwork themes
     */
    public static function get_available_themes() {
        check_ajax_referer('vortex_artwork_theme_nonce', 'nonce');
        
        global $wpdb;
        $themes_table = $wpdb->prefix . 'vortex_artwork_themes';
        
        // Check if the table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$themes_table'") !== $themes_table) {
            try {
                // Try to create the table
                require_once VORTEX_PLUGIN_DIR . 'includes/class-vortex-db-migrations.php';
                Vortex_DB_Migrations::ensure_artwork_themes_table();
                
                // Check again
                if ($wpdb->get_var("SHOW TABLES LIKE '$themes_table'") !== $themes_table) {
                    wp_send_json_error(array('message' => __('Themes table does not exist.', 'vortex-ai-marketplace')));
                    return;
                }
            } catch (Exception $e) {
                wp_send_json_error(array('message' => $e->getMessage()));
                return;
            }
        }
        
        // Query parameters
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $featured_only = isset($_GET['featured']) && $_GET['featured'] === 'true';
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
        
        // Build query
        $sql = "SELECT theme_id, theme_name, theme_slug, theme_description, 
                       popularity_score, trending_score, artwork_count, is_featured 
                FROM $themes_table";
        
        $where_clauses = array();
        
        if (!empty($search)) {
            $where_clauses[] = $wpdb->prepare(
                "(theme_name LIKE %s OR theme_description LIKE %s)",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }
        
        if ($featured_only) {
            $where_clauses[] = "is_featured = 1";
        }
        
        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(' AND ', $where_clauses);
        }
        
        $sql .= " ORDER BY popularity_score DESC, artwork_count DESC";
        
        if ($limit > 0) {
            $sql .= $wpdb->prepare(" LIMIT %d", $limit);
        }
        
        // Execute query
        $themes = $wpdb->get_results($sql);
        
        wp_send_json_success(array(
            'themes' => $themes,
            'count' => count($themes)
        ));
    }
} 