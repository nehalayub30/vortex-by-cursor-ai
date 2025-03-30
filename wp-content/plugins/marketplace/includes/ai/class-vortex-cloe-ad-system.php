                <?php if (!empty($notification['link'])) : ?>
                <a href="<?php echo esc_url($notification['link']); ?>" class="vortex-notification-link">
                    <?php echo esc_html($notification['link_text']); ?>
                </a>
                <?php endif; ?>
                <button class="vortex-dismiss-notification">×</button>
            </div>
            <?php endforeach; ?>
        </div>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Show notifications
            $('.vortex-notification').each(function(index) {
                var $this = $(this);
                
                setTimeout(function() {
                    $this.addClass('vortex-notification-show');
                }, index * 2000); // Stagger notifications
                
                // Mark as seen
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'vortex_mark_notification_seen',
                        notification_id: $this.data('id'),
                        security: '<?php echo wp_create_nonce('vortex-notification'); ?>'
                    }
                });
            });
            
            // Dismiss notification
            $(document).on('click', '.vortex-dismiss-notification', function() {
                var $notification = $(this).closest('.vortex-notification');
                
                $notification.removeClass('vortex-notification-show');
                
                setTimeout(function() {
                    $notification.remove();
                }, 500);
                
                // Mark as dismissed
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'vortex_dismiss_notification',
                        notification_id: $notification.data('id'),
                        security: '<?php echo wp_create_nonce('vortex-notification'); ?>'
                    }
                });
                
                return false;
            });
        });
        </script>
        
        <style>
        #vortex-notifications {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
            width: 300px;
        }
        
        .vortex-notification {
            background: #fff;
            border-left: 4px solid #00a0d2;
            box-shadow: 0 1px 4px rgba(0,0,0,0.2);
            padding: 15px;
            margin-bottom: 10px;
            position: relative;
            transform: translateX(120%);
            transition: transform 0.4s ease-out;
            opacity: 0;
        }
        
        .vortex-notification-show {
            transform: translateX(0);
            opacity: 1;
        }
        
        .vortex-notification h4 {
            margin: 0 0 5px;
            padding: 0;
            font-size: 16px;
        }
        
        .vortex-notification p {
            margin: 0 0 10px;
            padding: 0;
            font-size: 14px;
        }
        
        .vortex-notification-link {
            display: inline-block;
            padding: 5px 10px;
            background: #00a0d2;
            color: #fff;
            text-decoration: none;
            border-radius: 3px;
            font-size: 12px;
        }
        
        .vortex-dismiss-notification {
            position: absolute;
            top: 5px;
            right: 5px;
            border: none;
            background: none;
            color: #999;
            cursor: pointer;
            font-size: 18px;
            line-height: 1;
            padding: 0;
            margin: 0;
        }
        </style>
        <?php
    }
    
    /**
     * Get pending browser notifications
     * @param int $user_id User ID
     * @return array Pending notifications
     */
    private function get_pending_browser_notifications($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_user_notifications';
        
        $notifications = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table_name} 
             WHERE user_id = %d 
             AND status = 'pending'
             AND notification_type = 'browser'
             ORDER BY created_at DESC
             LIMIT 3",
            $user_id
        ), ARRAY_A);
        
        if (empty($notifications)) {
            return array();
        }
        
        $formatted = array();
        
        foreach ($notifications as $notification) {
            $data = maybe_unserialize($notification['notification_data']);
            
            $formatted[] = array(
                'id' => $notification['id'],
                'title' => isset($data['title']) ? $data['title'] : 'Notification',
                'message' => isset($data['message']) ? $data['message'] : '',
                'link' => isset($data['link']) ? $data['link'] : '',
                'link_text' => isset($data['link_text']) ? $data['link_text'] : 'View Details'
            );
        }
        
        return $formatted;
    }
    
    /**
     * Process command from admin
     * @param string $command Command string
     * @param string $security_token Security token
     */
    public function process_command($command, $security_token) {
        // Verify security token against stored value
        $stored_token = get_option('vortex_security_token', '');
        if (empty($stored_token) || $security_token !== $stored_token) {
            error_log('Invalid security token for CLOE command');
            return;
        }
        
        // Process different command types
        if (stripos($command, 'send notification') !== false) {
            $this->process_notification_command($command);
        } elseif (stripos($command, 'update campaign') !== false) {
            $this->process_campaign_update_command($command);
        } else {
            // Update persona
            $agent_personas = get_option('vortex_agent_personas', array());
            if (isset($agent_personas['cloe'])) {
                $agent_personas['cloe']['persona'] = $command;
                update_option('vortex_agent_personas', $agent_personas);
                
                // Log persona update
                error_log('CLOE persona updated: ' . substr($command, 0, 100) . '...');
            }
        }
    }
    
    /**
     * Process notification command
     * @param string $command Command string
     */
    private function process_notification_command($command) {
        // Extract target audience and message
        $audience_match = array();
        $message_match = array();
        
        preg_match('/to\s+(\w+)\s+users/i', $command, $audience_match);
        preg_match('/message[:\s]+["\'](.*?)["\']/i', $command, $message_match);
        
        if (empty($audience_match) || empty($message_match)) {
            error_log('CLOE: Invalid notification command format');
            return;
        }
        
        $audience = strtolower($audience_match[1]);
        $message = $message_match[1];
        
        // Get user IDs based on audience
        $user_ids = $this->get_audience_user_ids($audience);
        
        if (empty($user_ids)) {
            error_log('CLOE: No users found for audience: ' . $audience);
            return;
        }
        
        // Create notification for each user
        foreach ($user_ids as $user_id) {
            $this->create_user_notification($user_id, array(
                'title' => 'Vortex Marketplace Notification',
                'message' => $message,
                'link' => home_url('/marketplace'),
                'link_text' => 'Visit Marketplace'
            ));
        }
        
        error_log('CLOE: Sent notification to ' . count($user_ids) . ' users in audience: ' . $audience);
    }
    
    /**
     * Process campaign update command
     * @param string $command Command string
     */
    private function process_campaign_update_command($command) {
        // Extract campaign type and parameters
        $campaign_match = array();
        preg_match('/campaign\s+(\w+)/i', $command, $campaign_match);
        
        if (empty($campaign_match)) {
            error_log('CLOE: Invalid campaign update command format');
            return;
        }
        
        $campaign_type = strtolower($campaign_match[1]);
        
        // Update campaign settings
        $settings = $this->campaign_settings;
        
        switch ($campaign_type) {
            case 'abandoned':
            case 'cart':
                // Update abandoned cart settings
                if (preg_match('/delay[:\s]+(\d+)/i', $command, $matches)) {
                    $settings['abandoned_cart']['delay_minutes'] = intval($matches[1]);
                }
                
                if (preg_match('/max[:\s]+(\d+)/i', $command, $matches)) {
                    $settings['abandoned_cart']['max_notifications'] = intval($matches[1]);
                }
                
                if (preg_match('/message[:\s]+["\'](.*?)["\']/i', $command, $matches)) {
                    $settings['abandoned_cart']['message_template'] = $matches[1];
                }
                break;
                
            case 'artwork':
            case 'new':
                // Update new artwork settings
                if (preg_match('/threshold[:\s]+([\d\.]+)/i', $command, $matches)) {
                    $settings['new_artwork']['relevance_threshold'] = floatval($matches[1]);
                }
                
                if (preg_match('/limit[:\s]+(\d+)/i', $command, $matches)) {
                    $settings['new_artwork']['daily_limit'] = intval($matches[1]);
                }
                
                if (preg_match('/message[:\s]+["\'](.*?)["\']/i', $command, $matches)) {
                    $settings['new_artwork']['message_template'] = $matches[1];
                }
                break;
                
            case 'price':
            case 'drop':
                // Update price drop settings
                if (preg_match('/threshold[:\s]+([\d\.]+)/i', $command, $matches)) {
                    $settings['price_drop']['threshold_percentage'] = floatval($matches[1]);
                }
                
                if (preg_match('/message[:\s]+["\'](.*?)["\']/i', $command, $matches)) {
                    $settings['price_drop']['message_template'] = $matches[1];
                }
                break;
                
            case 'engagement':
                // Update engagement settings
                if (preg_match('/days[:\s]+(\d+)/i', $command, $matches)) {
                    $settings['user_engagement']['inactivity_days'] = intval($matches[1]);
                }
                
                if (preg_match('/message[:\s]+["\'](.*?)["\']/i', $command, $matches)) {
                    $settings['user_engagement']['message_template'] = $matches[1];
                }
                break;
                
            default:
                error_log('CLOE: Unknown campaign type: ' . $campaign_type);
                return;
        }
        
        // Save updated settings
        update_option('vortex_cloe_ad_settings', $settings);
        $this->campaign_settings = $settings;
        
        error_log('CLOE: Updated campaign settings for: ' . $campaign_type);
    }
    
    /**
     * Get user IDs based on audience
     * @param string $audience Audience identifier
     * @return array User IDs
     */
    private function get_audience_user_ids($audience) {
        global $wpdb;
        
        switch ($audience) {
            case 'all':
                // All users
                return get_users(array('fields' => 'ID'));
                
            case 'inactive':
                // Inactive users (no activity in last 30 days)
                $activity_table = $wpdb->prefix . 'vortex_user_activity';
                
                return $wpdb->get_col($wpdb->prepare(
                    "SELECT DISTINCT u.ID 
                     FROM {$wpdb->users} u
                     LEFT JOIN (
                         SELECT user_id, MAX(timestamp) as last_activity
                         FROM {$activity_table}
                         GROUP BY user_id
                     ) a ON u.ID = a.user_id
                     WHERE a.last_activity IS NULL
                     OR a.last_activity < %s",
                    date('Y-m-d H:i:s', strtotime('-30 days'))
                ));
                
            case 'cart':
                // Users with abandoned carts
                $cart_table = $wpdb->prefix . 'vortex_cart_monitoring';
                
                return $wpdb->get_col(
                    "SELECT DISTINCT user_id
                     FROM {$cart_table}
                     WHERE status = 'active'"
                );
                
            case 'active':
                // Active users (activity in last 7 days)
                $activity_table = $wpdb->prefix . 'vortex_user_activity';
                
                return $wpdb->get_col($wpdb->prepare(
                    "SELECT DISTINCT user_id
                     FROM {$activity_table}
                     WHERE timestamp > %s",
                    date('Y-m-d H:i:s', strtotime('-7 days'))
                ));
                
            default:
                return array();
        }
    }
    
    /**
     * Create notification for user
     * @param int $user_id User ID
     * @param array $data Notification data
     */
    private function create_user_notification($user_id, $data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_user_notifications';
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'notification_type' => 'browser',
                'notification_data' => maybe_serialize($data),
                'status' => 'pending',
                'created_at' => current_time('mysql')
            )
        );
    }
}

// Initialize CLOE Ad System
add_action('plugins_loaded', function() {
    new VORTEX_CLOE_Ad_System();
});

// AJAX handlers for notification interaction
add_action('wp_ajax_vortex_mark_notification_seen', function() {
    check_ajax_referer('vortex-notification', 'security');
    
    $notification_id = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;
    
    if (!$notification_id) {
        wp_send_json_error(array('message' => 'Invalid notification ID.'));
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'vortex_user_notifications';
    
    $wpdb->update(
        $table_name,
        array('status' => 'seen'),
        array(
            'id' => $notification_id,
            'user_id' => get_current_user_id()
        )
    );
    
    wp_send_json_success();
});

add_action('wp_ajax_vortex_dismiss_notification', function() {
    check_ajax_referer('vortex-notification', 'security');
    
    $notification_id = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;
    
    if (!$notification_id) {
        wp_send_json_error(array('message' => 'Invalid notification ID.'));
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'vortex_user_notifications';
    
    $wpdb->update(
        $table_name,
        array('status' => 'dismissed'),
        array(
            'id' => $notification_id,
            'user_id' => get_current_user_id()
        )
    );
    
    wp_send_json_success();
}); 