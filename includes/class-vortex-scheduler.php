<?php
/**
 * The scheduling functionality of the plugin.
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

/**
 * The scheduling functionality of the plugin.
 *
 * Handles scheduling of NFT publications, exhibitions, and auctions
 * with AI-powered optimization and notifications.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_Scheduler {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * The marketplace instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Vortex_Marketplace    $marketplace    The marketplace instance.
     */
    private $marketplace;

    /**
     * The AI coordinator instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Vortex_AI_Coordinator    $ai_coordinator    The AI coordinator instance.
     */
    private $ai_coordinator;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version           The version of this plugin.
     * @param    Vortex_Marketplace    $marketplace    The marketplace instance.
     */
    public function __construct($plugin_name, $version, $marketplace) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->marketplace = $marketplace;
        
        // Initialize AI coordinator
        $this->ai_coordinator = Vortex_AI_Coordinator::get_instance();
        
        // Register hooks
        $this->register_hooks();
        
        // Register shortcodes
        $this->register_shortcodes();
    }

    /**
     * Register all hooks used by the scheduler.
     *
     * @since    1.0.0
     */
    private function register_hooks() {
        // Schedule events
        add_action('init', array($this, 'schedule_events'));
        
        // Handle scheduled events
        add_action('vortex_process_scheduled_events', array($this, 'process_scheduled_events'));
        
        // Handle notifications
        add_action('vortex_send_schedule_notifications', array($this, 'send_schedule_notifications'));
        
        // AJAX handlers
        add_action('wp_ajax_vortex_schedule_nft', array($this, 'handle_schedule_nft'));
        add_action('wp_ajax_vortex_schedule_exhibition', array($this, 'handle_schedule_exhibition'));
        add_action('wp_ajax_vortex_schedule_auction', array($this, 'handle_schedule_auction'));
        add_action('wp_ajax_vortex_get_schedule_suggestions', array($this, 'handle_get_schedule_suggestions'));
        add_action('wp_ajax_vortex_reschedule_event', array($this, 'handle_reschedule_event'));
        add_action('wp_ajax_vortex_get_reschedule_form', array($this, 'handle_get_reschedule_form'));
        add_action('wp_ajax_vortex_cancel_event', array($this, 'handle_cancel_event'));
    }

    /**
     * Register all shortcodes used by the scheduler.
     *
     * @since    1.0.0
     */
    private function register_shortcodes() {
        add_shortcode('vortex_schedule_nft', array($this, 'render_schedule_nft_form'));
        add_shortcode('vortex_schedule_exhibition', array($this, 'render_schedule_exhibition_form'));
        add_shortcode('vortex_schedule_auction', array($this, 'render_schedule_auction_form'));
        add_shortcode('vortex_scheduled_events', array($this, 'render_scheduled_events'));
    }

    /**
     * Schedule recurring events.
     *
     * @since    1.0.0
     */
    public function schedule_events() {
        if (!wp_next_scheduled('vortex_process_scheduled_events')) {
            wp_schedule_event(time(), 'hourly', 'vortex_process_scheduled_events');
        }
        
        if (!wp_next_scheduled('vortex_send_schedule_notifications')) {
            wp_schedule_event(time(), 'hourly', 'vortex_send_schedule_notifications');
        }
    }

    /**
     * Process scheduled events.
     *
     * @since    1.0.0
     */
    public function process_scheduled_events() {
        global $wpdb;
        
        $events_table = $wpdb->prefix . 'vortex_scheduled_events';
        $current_time = current_time('mysql');
        
        // Get events that need to be processed
        $events = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$events_table} 
            WHERE status = 'scheduled' 
            AND scheduled_time <= %s",
            $current_time
        ));
        
        foreach ($events as $event) {
            try {
                switch ($event->event_type) {
                    case 'nft_publication':
                        $this->process_nft_publication($event);
                        break;
                        
                    case 'exhibition_opening':
                        $this->process_exhibition_opening($event);
                        break;
                        
                    case 'auction_start':
                        $this->process_auction_start($event);
                        break;
                }
                
                // Update event status
                $wpdb->update(
                    $events_table,
                    array('status' => 'completed'),
                    array('event_id' => $event->event_id),
                    array('%s'),
                    array('%d')
                );
            } catch (Exception $e) {
                error_log('Vortex Scheduler Error: ' . $e->getMessage());
                
                // Update event status to failed
                $wpdb->update(
                    $events_table,
                    array(
                        'status' => 'failed',
                        'error_message' => $e->getMessage()
                    ),
                    array('event_id' => $event->event_id),
                    array('%s', '%s'),
                    array('%d')
                );
            }
        }
    }

    /**
     * Process NFT publication event.
     *
     * @since    1.0.0
     * @param    object    $event    The event object.
     */
    private function process_nft_publication($event) {
        // Get NFT data
        $nft_data = json_decode($event->event_data, true);
        
        // Verify NFT ownership
        if (!$this->verify_nft_ownership($nft_data['nft_id'], $event->user_id)) {
            throw new Exception('NFT ownership verification failed');
        }
        
        // Publish NFT
        $this->marketplace->publish_nft($nft_data['nft_id']);
        
        // Trigger AI analysis
        $this->ai_coordinator->analyze_nft_publication($nft_data['nft_id']);
    }

    /**
     * Process exhibition opening event.
     *
     * @since    1.0.0
     * @param    object    $event    The event object.
     */
    private function process_exhibition_opening($event) {
        // Get exhibition data
        $exhibition_data = json_decode($event->event_data, true);
        
        // Verify exhibition ownership
        if (!$this->verify_exhibition_ownership($exhibition_data['exhibition_id'], $event->user_id)) {
            throw new Exception('Exhibition ownership verification failed');
        }
        
        // Open exhibition
        $this->marketplace->open_exhibition($exhibition_data['exhibition_id']);
        
        // Trigger AI analysis
        $this->ai_coordinator->analyze_exhibition_opening($exhibition_data['exhibition_id']);
    }

    /**
     * Process auction start event.
     *
     * @since    1.0.0
     * @param    object    $event    The event object.
     */
    private function process_auction_start($event) {
        // Get auction data
        $auction_data = json_decode($event->event_data, true);
        
        // Verify auction ownership
        if (!$this->verify_auction_ownership($auction_data['auction_id'], $event->user_id)) {
            throw new Exception('Auction ownership verification failed');
        }
        
        // Start auction
        $this->marketplace->start_auction($auction_data['auction_id']);
        
        // Trigger AI analysis
        $this->ai_coordinator->analyze_auction_start($auction_data['auction_id']);
    }

    /**
     * Send notifications for scheduled events.
     *
     * @since    1.0.0
     */
    public function send_schedule_notifications() {
        global $wpdb;
        
        $events_table = $wpdb->prefix . 'vortex_scheduled_events';
        $current_time = current_time('mysql');
        
        // Get upcoming events
        $events = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$events_table} 
            WHERE status = 'scheduled' 
            AND scheduled_time <= DATE_ADD(%s, INTERVAL 24 HOUR)
            AND notification_sent = 0",
            $current_time
        ));
        
        foreach ($events as $event) {
            try {
                // Send notification
                $this->send_event_notification($event);
                
                // Mark notification as sent
                $wpdb->update(
                    $events_table,
                    array('notification_sent' => 1),
                    array('event_id' => $event->event_id),
                    array('%d'),
                    array('%d')
                );
            } catch (Exception $e) {
                error_log('Vortex Scheduler Notification Error: ' . $e->getMessage());
            }
        }
    }

    /**
     * Send notification for a specific event.
     *
     * @since    1.0.0
     * @param    object    $event    The event object.
     */
    private function send_event_notification($event) {
        $user = get_user_by('ID', $event->user_id);
        if (!$user) return;
        
        $event_data = json_decode($event->event_data, true);
        
        switch ($event->event_type) {
            case 'nft_publication':
                $subject = sprintf(
                    __('Your NFT "%s" will be published soon', 'vortex-ai-marketplace'),
                    $event_data['title']
                );
                $message = sprintf(
                    __('Your NFT "%s" is scheduled to be published on %s.', 'vortex-ai-marketplace'),
                    $event_data['title'],
                    date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($event->scheduled_time))
                );
                break;
                
            case 'exhibition_opening':
                $subject = sprintf(
                    __('Your exhibition "%s" will open soon', 'vortex-ai-marketplace'),
                    $event_data['title']
                );
                $message = sprintf(
                    __('Your exhibition "%s" is scheduled to open on %s.', 'vortex-ai-marketplace'),
                    $event_data['title'],
                    date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($event->scheduled_time))
                );
                break;
                
            case 'auction_start':
                $subject = sprintf(
                    __('Your auction "%s" will start soon', 'vortex-ai-marketplace'),
                    $event_data['title']
                );
                $message = sprintf(
                    __('Your auction "%s" is scheduled to start on %s.', 'vortex-ai-marketplace'),
                    $event_data['title'],
                    date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($event->scheduled_time))
                );
                break;
        }
        
        // Send email notification
        wp_mail($user->user_email, $subject, $message);
        
        // Send in-app notification
        $this->marketplace->add_notification($event->user_id, $subject, $message);
    }

    /**
     * Handle NFT scheduling AJAX request.
     *
     * @since    1.0.0
     */
    public function handle_schedule_nft() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vortex_schedule_nft')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'vortex-ai-marketplace')
            ));
        }
        
        // Verify user is logged in
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to schedule an NFT.', 'vortex-ai-marketplace')
            ));
        }
        
        // Validate input
        $nft_id = intval($_POST['nft_id'] ?? 0);
        $scheduled_time = sanitize_text_field($_POST['scheduled_time'] ?? '');
        
        if (!$nft_id || !$scheduled_time) {
            wp_send_json_error(array(
                'message' => __('Invalid input data.', 'vortex-ai-marketplace')
            ));
        }
        
        try {
            // Verify NFT ownership
            if (!$this->verify_nft_ownership($nft_id, $user_id)) {
                throw new Exception(__('You do not own this NFT.', 'vortex-ai-marketplace'));
            }
            
            // Get AI suggestions for optimal timing
            $suggestions = $this->ai_coordinator->get_optimal_publishing_time($nft_id);
            
            // Schedule the event
            $event_id = $this->schedule_event(
                'nft_publication',
                $user_id,
                $scheduled_time,
                array(
                    'nft_id' => $nft_id,
                    'title' => get_the_title($nft_id)
                )
            );
            
            wp_send_json_success(array(
                'message' => __('NFT scheduled successfully.', 'vortex-ai-marketplace'),
                'event_id' => $event_id,
                'suggestions' => $suggestions
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * Handle exhibition scheduling AJAX request.
     *
     * @since    1.0.0
     */
    public function handle_schedule_exhibition() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vortex_schedule_exhibition')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'vortex-ai-marketplace')
            ));
        }
        
        // Verify user is logged in
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to schedule an exhibition.', 'vortex-ai-marketplace')
            ));
        }
        
        // Validate input
        $exhibition_id = intval($_POST['exhibition_id'] ?? 0);
        $scheduled_time = sanitize_text_field($_POST['scheduled_time'] ?? '');
        
        if (!$exhibition_id || !$scheduled_time) {
            wp_send_json_error(array(
                'message' => __('Invalid input data.', 'vortex-ai-marketplace')
            ));
        }
        
        try {
            // Verify exhibition ownership
            if (!$this->verify_exhibition_ownership($exhibition_id, $user_id)) {
                throw new Exception(__('You do not own this exhibition.', 'vortex-ai-marketplace'));
            }
            
            // Get AI suggestions for optimal timing
            $suggestions = $this->ai_coordinator->get_optimal_exhibition_time($exhibition_id);
            
            // Schedule the event
            $event_id = $this->schedule_event(
                'exhibition_opening',
                $user_id,
                $scheduled_time,
                array(
                    'exhibition_id' => $exhibition_id,
                    'title' => get_the_title($exhibition_id)
                )
            );
            
            wp_send_json_success(array(
                'message' => __('Exhibition scheduled successfully.', 'vortex-ai-marketplace'),
                'event_id' => $event_id,
                'suggestions' => $suggestions
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * Handle auction scheduling AJAX request.
     *
     * @since    1.0.0
     */
    public function handle_schedule_auction() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vortex_schedule_auction')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'vortex-ai-marketplace')
            ));
        }
        
        // Verify user is logged in
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to schedule an auction.', 'vortex-ai-marketplace')
            ));
        }
        
        // Validate input
        $auction_id = intval($_POST['auction_id'] ?? 0);
        $scheduled_time = sanitize_text_field($_POST['scheduled_time'] ?? '');
        
        if (!$auction_id || !$scheduled_time) {
            wp_send_json_error(array(
                'message' => __('Invalid input data.', 'vortex-ai-marketplace')
            ));
        }
        
        try {
            // Verify auction ownership
            if (!$this->verify_auction_ownership($auction_id, $user_id)) {
                throw new Exception(__('You do not own this auction.', 'vortex-ai-marketplace'));
            }
            
            // Get AI suggestions for optimal timing
            $suggestions = $this->ai_coordinator->get_optimal_auction_time($auction_id);
            
            // Schedule the event
            $event_id = $this->schedule_event(
                'auction_start',
                $user_id,
                $scheduled_time,
                array(
                    'auction_id' => $auction_id,
                    'title' => get_the_title($auction_id)
                )
            );
            
            wp_send_json_success(array(
                'message' => __('Auction scheduled successfully.', 'vortex-ai-marketplace'),
                'event_id' => $event_id,
                'suggestions' => $suggestions
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * Handle schedule suggestions AJAX request.
     *
     * @since    1.0.0
     */
    public function handle_get_schedule_suggestions() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vortex_get_schedule_suggestions')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'vortex-ai-marketplace')
            ));
        }
        
        // Verify user is logged in
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to get schedule suggestions.', 'vortex-ai-marketplace')
            ));
        }
        
        // Validate input
        $item_id = intval($_POST['item_id'] ?? 0);
        $item_type = sanitize_text_field($_POST['item_type'] ?? '');
        
        if (!$item_id || !$item_type) {
            wp_send_json_error(array(
                'message' => __('Invalid input data.', 'vortex-ai-marketplace')
            ));
        }
        
        try {
            // Get AI suggestions based on item type
            $suggestions = array();
            switch ($item_type) {
                case 'nft':
                    $suggestions = $this->ai_coordinator->get_optimal_publishing_time($item_id);
                    break;
                    
                case 'exhibition':
                    $suggestions = $this->ai_coordinator->get_optimal_exhibition_time($item_id);
                    break;
                    
                case 'auction':
                    $suggestions = $this->ai_coordinator->get_optimal_auction_time($item_id);
                    break;
                    
                default:
                    throw new Exception(__('Invalid item type.', 'vortex-ai-marketplace'));
            }
            
            wp_send_json_success(array(
                'suggestions' => $suggestions
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * Schedule an event.
     *
     * @since    1.0.0
     * @param    string    $event_type       The type of event.
     * @param    int       $user_id          The user ID.
     * @param    string    $scheduled_time   The scheduled time.
     * @param    array     $event_data       The event data.
     * @return   int       The event ID.
     */
    private function schedule_event($event_type, $user_id, $scheduled_time, $event_data) {
        global $wpdb;
        
        $events_table = $wpdb->prefix . 'vortex_scheduled_events';
        
        $result = $wpdb->insert(
            $events_table,
            array(
                'event_type' => $event_type,
                'user_id' => $user_id,
                'scheduled_time' => $scheduled_time,
                'event_data' => json_encode($event_data),
                'status' => 'scheduled',
                'notification_sent' => 0,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%d', '%s', '%s', '%s', '%d', '%s')
        );
        
        if ($result === false) {
            throw new Exception(__('Failed to schedule event.', 'vortex-ai-marketplace'));
        }
        
        return $wpdb->insert_id;
    }

    /**
     * Verify NFT ownership.
     *
     * @since    1.0.0
     * @param    int    $nft_id    The NFT ID.
     * @param    int    $user_id   The user ID.
     * @return   boolean   Whether the user owns the NFT.
     */
    private function verify_nft_ownership($nft_id, $user_id) {
        return $this->marketplace->verify_nft_ownership($nft_id, $user_id);
    }

    /**
     * Verify exhibition ownership.
     *
     * @since    1.0.0
     * @param    int    $exhibition_id    The exhibition ID.
     * @param    int    $user_id          The user ID.
     * @return   boolean   Whether the user owns the exhibition.
     */
    private function verify_exhibition_ownership($exhibition_id, $user_id) {
        return $this->marketplace->verify_exhibition_ownership($exhibition_id, $user_id);
    }

    /**
     * Verify auction ownership.
     *
     * @since    1.0.0
     * @param    int    $auction_id    The auction ID.
     * @param    int    $user_id       The user ID.
     * @return   boolean   Whether the user owns the auction.
     */
    private function verify_auction_ownership($auction_id, $user_id) {
        return $this->marketplace->verify_auction_ownership($auction_id, $user_id);
    }

    /**
     * Render NFT scheduling form.
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes.
     * @return   string   HTML content to display the form.
     */
    public function render_schedule_nft_form($atts) {
        // Security check
        if (!wp_verify_nonce($atts['security_nonce'] ?? '', 'vortex_schedule_nft_display')) {
            return '<div class="vortex-error">' . 
                   esc_html__('Security check failed.', 'vortex-ai-marketplace') . 
                   '</div>';
        }
        
        // Check if user is logged in
        $user_id = get_current_user_id();
        if (!$user_id) {
            return '<div class="vortex-notice">' . 
                   esc_html__('Please log in to schedule an NFT.', 'vortex-ai-marketplace') . 
                   '<p><a href="' . esc_url(wp_login_url(get_permalink())) . '" class="vortex-button">' . 
                   esc_html__('Log In', 'vortex-ai-marketplace') . '</a></p></div>';
        }
        
        // Get user's NFTs
        $nfts = $this->marketplace->get_user_nfts($user_id);
        
        // Buffer the output
        ob_start();
        
        // Include the template
        include plugin_dir_path(dirname(__FILE__)) . 'public/partials/vortex-schedule-nft-form.php';
        
        return ob_get_clean();
    }

    /**
     * Render exhibition scheduling form.
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes.
     * @return   string   HTML content to display the form.
     */
    public function render_schedule_exhibition_form($atts) {
        // Security check
        if (!wp_verify_nonce($atts['security_nonce'] ?? '', 'vortex_schedule_exhibition_display')) {
            return '<div class="vortex-error">' . 
                   esc_html__('Security check failed.', 'vortex-ai-marketplace') . 
                   '</div>';
        }
        
        // Check if user is logged in
        $user_id = get_current_user_id();
        if (!$user_id) {
            return '<div class="vortex-notice">' . 
                   esc_html__('Please log in to schedule an exhibition.', 'vortex-ai-marketplace') . 
                   '<p><a href="' . esc_url(wp_login_url(get_permalink())) . '" class="vortex-button">' . 
                   esc_html__('Log In', 'vortex-ai-marketplace') . '</a></p></div>';
        }
        
        // Get user's exhibitions
        $exhibitions = $this->marketplace->get_user_exhibitions($user_id);
        
        // Buffer the output
        ob_start();
        
        // Include the template
        include plugin_dir_path(dirname(__FILE__)) . 'public/partials/vortex-schedule-exhibition-form.php';
        
        return ob_get_clean();
    }

    /**
     * Render auction scheduling form.
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes.
     * @return   string   HTML content to display the form.
     */
    public function render_schedule_auction_form($atts) {
        // Security check
        if (!wp_verify_nonce($atts['security_nonce'] ?? '', 'vortex_schedule_auction_display')) {
            return '<div class="vortex-error">' . 
                   esc_html__('Security check failed.', 'vortex-ai-marketplace') . 
                   '</div>';
        }
        
        // Check if user is logged in
        $user_id = get_current_user_id();
        if (!$user_id) {
            return '<div class="vortex-notice">' . 
                   esc_html__('Please log in to schedule an auction.', 'vortex-ai-marketplace') . 
                   '<p><a href="' . esc_url(wp_login_url(get_permalink())) . '" class="vortex-button">' . 
                   esc_html__('Log In', 'vortex-ai-marketplace') . '</a></p></div>';
        }
        
        // Get user's auctions
        $auctions = $this->marketplace->get_user_auctions($user_id);
        
        // Buffer the output
        ob_start();
        
        // Include the template
        include plugin_dir_path(dirname(__FILE__)) . 'public/partials/vortex-schedule-auction-form.php';
        
        return ob_get_clean();
    }

    /**
     * Render scheduled events list.
     *
     * @since    1.0.0
     * @param    array    $atts    Shortcode attributes.
     * @return   string   HTML content to display the events list.
     */
    public function render_scheduled_events($atts) {
        // Security check
        if (!wp_verify_nonce($atts['security_nonce'] ?? '', 'vortex_scheduled_events_display')) {
            return '<div class="vortex-error">' . 
                   esc_html__('Security check failed.', 'vortex-ai-marketplace') . 
                   '</div>';
        }
        
        // Check if user is logged in
        $user_id = get_current_user_id();
        if (!$user_id) {
            return '<div class="vortex-notice">' . 
                   esc_html__('Please log in to view your scheduled events.', 'vortex-ai-marketplace') . 
                   '<p><a href="' . esc_url(wp_login_url(get_permalink())) . '" class="vortex-button">' . 
                   esc_html__('Log In', 'vortex-ai-marketplace') . '</a></p></div>';
        }
        
        // Get user's scheduled events
        global $wpdb;
        $events_table = $wpdb->prefix . 'vortex_scheduled_events';
        
        $events = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$events_table} 
            WHERE user_id = %d 
            ORDER BY scheduled_time ASC",
            $user_id
        ));
        
        // Buffer the output
        ob_start();
        
        // Include the template
        include plugin_dir_path(dirname(__FILE__)) . 'public/partials/vortex-scheduled-events-list.php';
        
        return ob_get_clean();
    }

    /**
     * Handle rescheduling AJAX request.
     *
     * @since    1.0.0
     */
    public function handle_reschedule_event() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vortex_reschedule_event')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'vortex-ai-marketplace')
            ));
        }
        
        // Verify user is logged in
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to reschedule an event.', 'vortex-ai-marketplace')
            ));
        }
        
        // Validate input
        $event_id = intval($_POST['event_id'] ?? 0);
        $scheduled_time = sanitize_text_field($_POST['scheduled_time'] ?? '');
        
        if (!$event_id || !$scheduled_time) {
            wp_send_json_error(array(
                'message' => __('Invalid input data.', 'vortex-ai-marketplace')
            ));
        }
        
        try {
            // Get event data
            $event = $this->db->get_event($event_id);
            if (!$event) {
                throw new Exception(__('Event not found.', 'vortex-ai-marketplace'));
            }
            
            // Verify event ownership
            if ($event->user_id !== $user_id) {
                throw new Exception(__('You do not own this event.', 'vortex-ai-marketplace'));
            }
            
            // Verify event is not already completed or cancelled
            if (in_array($event->status, array('completed', 'cancelled'))) {
                throw new Exception(__('This event cannot be rescheduled.', 'vortex-ai-marketplace'));
            }
            
            // Update event schedule
            $result = $this->db->update_event($event_id, array(
                'scheduled_time' => $scheduled_time,
                'status' => 'pending'
            ));
            
            if (!$result) {
                throw new Exception(__('Failed to reschedule event.', 'vortex-ai-marketplace'));
            }
            
            wp_send_json_success(array(
                'message' => __('Event rescheduled successfully.', 'vortex-ai-marketplace')
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * Handle get reschedule form AJAX request.
     *
     * @since    1.0.0
     */
    public function handle_get_reschedule_form() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vortex_get_reschedule_form')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'vortex-ai-marketplace')
            ));
        }
        
        // Verify user is logged in
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to reschedule an event.', 'vortex-ai-marketplace')
            ));
        }
        
        // Validate input
        $event_id = intval($_POST['event_id'] ?? 0);
        $event_type = sanitize_text_field($_POST['event_type'] ?? '');
        
        if (!$event_id || !$event_type) {
            wp_send_json_error(array(
                'message' => __('Invalid input data.', 'vortex-ai-marketplace')
            ));
        }
        
        try {
            // Get event data
            $event = $this->db->get_event($event_id);
            if (!$event) {
                throw new Exception(__('Event not found.', 'vortex-ai-marketplace'));
            }
            
            // Verify event ownership
            if ($event->user_id !== $user_id) {
                throw new Exception(__('You do not own this event.', 'vortex-ai-marketplace'));
            }
            
            // Verify event is not already completed or cancelled
            if (in_array($event->status, array('completed', 'cancelled'))) {
                throw new Exception(__('This event cannot be rescheduled.', 'vortex-ai-marketplace'));
            }
            
            // Buffer the output
            ob_start();
            
            // Include the reschedule form template
            include $this->marketplace->get_template_path('vortex-reschedule-form.php');
            
            $form_html = ob_get_clean();
            
            wp_send_json_success(array(
                'form_html' => $form_html
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * Handle cancel event AJAX request.
     *
     * @since    1.0.0
     */
    public function handle_cancel_event() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'vortex_cancel_event')) {
            wp_send_json_error(array(
                'message' => __('Security check failed.', 'vortex-ai-marketplace')
            ));
        }
        
        // Verify user is logged in
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to cancel an event.', 'vortex-ai-marketplace')
            ));
        }
        
        // Validate input
        $event_id = intval($_POST['event_id'] ?? 0);
        
        if (!$event_id) {
            wp_send_json_error(array(
                'message' => __('Invalid input data.', 'vortex-ai-marketplace')
            ));
        }
        
        try {
            // Get event data
            $event = $this->db->get_event($event_id);
            if (!$event) {
                throw new Exception(__('Event not found.', 'vortex-ai-marketplace'));
            }
            
            // Verify event ownership
            if ($event->user_id !== $user_id) {
                throw new Exception(__('You do not own this event.', 'vortex-ai-marketplace'));
            }
            
            // Verify event is not already completed or cancelled
            if (in_array($event->status, array('completed', 'cancelled'))) {
                throw new Exception(__('This event cannot be cancelled.', 'vortex-ai-marketplace'));
            }
            
            // Update event status
            $result = $this->db->update_event($event_id, array(
                'status' => 'cancelled'
            ));
            
            if (!$result) {
                throw new Exception(__('Failed to cancel event.', 'vortex-ai-marketplace'));
            }
            
            wp_send_json_success(array(
                'message' => __('Event cancelled successfully.', 'vortex-ai-marketplace')
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }
} 