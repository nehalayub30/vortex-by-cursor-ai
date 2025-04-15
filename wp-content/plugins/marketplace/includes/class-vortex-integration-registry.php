            $dao_governance = $this->get_integration_instance('dao_governance');
            if ($dao_governance) {
                $dao_governance->process_artwork_sale($data);
            }
        });
        
        // Blockchain metrics should listen to marketplace and DAO events
        $this->add_event_listener('artwork_tokenized', 'blockchain_metrics', function($data) {
            $blockchain_metrics = $this->get_integration_instance('blockchain_metrics');
            if ($blockchain_metrics) {
                $blockchain_metrics->invalidate_metrics_cache();
            }
        });
        
        $this->add_event_listener('artwork_sold', 'blockchain_metrics', function($data) {
            $blockchain_metrics = $this->get_integration_instance('blockchain_metrics');
            if ($blockchain_metrics) {
                $blockchain_metrics->invalidate_metrics_cache();
            }
        });
        
        $this->add_event_listener('dao_proposal_executed', 'blockchain_metrics', function($data) {
            $blockchain_metrics = $this->get_integration_instance('blockchain_metrics');
            if ($blockchain_metrics) {
                $blockchain_metrics->invalidate_metrics_cache();
            }
        });
        
        // Security system should listen to AI insights
        $this->add_event_listener('ai_insight_generated', 'security_system', function($data) {
            // Only process security-related insights
            if ($data['insight_data']['insight_type'] === 'alert') {
                $security_system = $this->get_integration_instance('security_system');
                if ($security_system) {
                    $security_system->process_security_insight($data['insight_data']);
                }
            }
        });
        
        // Gamification should listen to marketplace events
        $this->add_event_listener('artwork_created', 'gamification', function($data) {
            $gamification = $this->get_integration_instance('gamification');
            if ($gamification) {
                $gamification->award_points(
                    $data['artwork_data']['user_id'],
                    'artwork_created',
                    $data
                );
            }
        });
        
        $this->add_event_listener('artwork_sold', 'gamification', function($data) {
            $gamification = $this->get_integration_instance('gamification');
            if ($gamification) {
                // Award points to the seller
                $gamification->award_points(
                    $data['sale_data']['seller_id'],
                    'artwork_sold',
                    $data
                );
                
                // Award points to the buyer
                $gamification->award_points(
                    $data['sale_data']['buyer_id'],
                    'artwork_purchased',
                    $data
                );
            }
        });
    }
    
    /**
     * Trigger an internal event
     *
     * @param string $event_name Name of the event
     * @param array $data Event data
     */
    public function trigger_event($event_name, $data = array()) {
        // Log the event
        $this->log_integration_event($event_name, $data);
        
        // Execute all registered listeners for this event
        if (isset($this->event_listeners[$event_name])) {
            foreach ($this->event_listeners[$event_name] as $listener) {
                try {
                    $listener['callback']($data);
                } catch (Exception $e) {
                    $this->log_integration_error(
                        $listener['integration_id'],
                        "Error processing {$event_name} event: " . $e->getMessage()
                    );
                }
            }
        }
        
        // Trigger WordPress action for external listeners
        do_action('vortex_integration_event_' . $event_name, $data);
    }
    
    /**
     * Add an event listener
     *
     * @param string $event_name Event to listen for
     * @param string $integration_id ID of the integration that's listening
     * @param callable $callback Callback function to execute
     */
    public function add_event_listener($event_name, $integration_id, $callback) {
        if (!isset($this->event_listeners[$event_name])) {
            $this->event_listeners[$event_name] = array();
        }
        
        $this->event_listeners[$event_name][] = array(
            'integration_id' => $integration_id,
            'callback' => $callback
        );
    }
    
    /**
     * Get an integration instance
     *
     * @param string $id Integration ID
     * @return object|null The integration instance or null if not available
     */
    public function get_integration_instance($id) {
        if (isset($this->integrations[$id]) && 
            $this->integrations[$id]['status'] === 'active' &&
            $this->integrations[$id]['instance'] !== null) {
            return $this->integrations[$id]['instance'];
        }
        
        return null;
    }
    
    /**
     * Log an integration event
     *
     * @param string $event_name Event name
     * @param array $data Event data
     */
    private function log_integration_event($event_name, $data) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'vortex_integration_logs',
            array(
                'event_name' => $event_name,
                'event_data' => wp_json_encode($data),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s')
        );
    }
    
    /**
     * Log an integration error
     *
     * @param string $integration_id Integration ID
     * @param string $error_message Error message
     */
    private function log_integration_error($integration_id, $error_message) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'vortex_integration_logs',
            array(
                'event_name' => 'integration_error',
                'event_data' => wp_json_encode(array(
                    'integration_id' => $integration_id,
                    'error_message' => $error_message,
                    'backtrace' => wp_debug_backtrace_summary()
                )),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s')
        );
        
        error_log("VORTEX Integration Error ({$integration_id}): {$error_message}");
    }
    
    /**
     * Perform regular health check on all integrations
     */
    public function perform_health_check() {
        foreach ($this->integrations as $id => $config) {
            if ($config['enabled'] && $config['status'] === 'active') {
                $this->check_integration_health($id);
            }
        }
        
        // Also check data bridges
        foreach ($this->data_bridges as $bridge_id => $bridge) {
            $this->check_data_bridge($bridge_id);
        }
        
        error_log('VORTEX Integration Registry: Health check completed');
    }
    
    /**
     * Check the health of a specific integration
     *
     * @param string $id Integration ID
     * @return bool True if healthy, false otherwise
     */
    private function check_integration_health($id) {
        if (!isset($this->integrations[$id])) {
            return false;
        }
        
        $config = $this->integrations[$id];
        
        // Skip disabled integrations
        if (!$config['enabled']) {
            return true;
        }
        
        // If instance is null but should be active, try to reload
        if ($config['status'] === 'active' && $config['instance'] === null) {
            $this->load_integration($id);
        }
        
        // Check if instance has a health_check method
        if ($config['instance'] !== null && method_exists($config['instance'], 'health_check')) {
            try {
                $health = $config['instance']->health_check();
                
                if (!$health['healthy']) {
                    $this->log_integration_error($id, "Health check failed: " . $health['message']);
                    
                    // Try to reload if unhealthy
                    if ($health['reload_recommended']) {
                        $this->load_integration($id);
                    }
                    
                    return false;
                }
                
                return true;
            } catch (Exception $e) {
                $this->log_integration_error($id, "Health check threw exception: " . $e->getMessage());
                return false;
            }
        }
        
        // Default to assuming healthy if no health_check method exists
        return true;
    }
    
    /**
     * Check a data bridge
     *
     * @param string $bridge_id Bridge ID
     */
    private function check_data_bridge($bridge_id) {
        if (!isset($this->data_bridges[$bridge_id])) {
            return;
        }
        
        $bridge = $this->data_bridges[$bridge_id];
        
        // Skip disabled bridges
        if (!$bridge['enabled']) {
            return;
        }
        
        // Get source and target instances
        $source = $this->get_integration_instance($bridge['source']);
        $target = $this->get_integration_instance($bridge['target']);
        
        if ($source === null || $target === null) {
            $this->log_integration_error($bridge_id, "Data bridge failure: Source or target not available");
            return;
        }
        
        // Check if sync is needed
        $sync_interval = apply_filters('vortex_data_bridge_sync_interval', 3600, $bridge_id);
        if (time() - $bridge['last_sync'] < $sync_interval) {
            return;
        }
        
        // Perform data sync if both components have appropriate methods
        $source_method = 'get_' . $bridge['data_type'];
        $target_method = 'receive_' . $bridge['data_type'];
        
        if (method_exists($source, $source_method) && method_exists($target, $target_method)) {
            try {
                $data = $source->$source_method();
                $target->$target_method($data);
                
                // Update last sync time
                $this->data_bridges[$bridge_id]['last_sync'] = time();
                
                // Log successful sync
                error_log("VORTEX Data Bridge: Synced {$bridge['data_type']} from {$bridge['source']} to {$bridge['target']}");
            } catch (Exception $e) {
                $this->log_integration_error($bridge_id, "Data sync failed: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Create integration tables
     */
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Integration logs table
        $table_name = $wpdb->prefix . 'vortex_integration_logs';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_name varchar(100) NOT NULL,
            event_data longtext NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY event_name (event_name),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'vortex-dashboard',
            'Integrations',
            'Integrations',
            'manage_options',
            'vortex-integrations',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        // Check for any requested actions
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        $integration_id = isset($_GET['integration']) ? sanitize_text_field($_GET['integration']) : '';
        
        if ($action && $integration_id && isset($this->integrations[$integration_id])) {
            check_admin_referer('vortex_integration_' . $action);
            
            switch ($action) {
                case 'enable':
                    $this->integrations[$integration_id]['enabled'] = true;
                    update_option('vortex_integration_' . $integration_id . '_enabled', true);
                    $this->load_integration($integration_id);
                    break;
                    
                case 'disable':
                    $this->integrations[$integration_id]['enabled'] = false;
                    update_option('vortex_integration_' . $integration_id . '_enabled', false);
                    $this->integrations[$integration_id]['status'] = 'disabled';
                    break;
                    
                case 'reload':
                    $this->load_integration($integration_id);
                    break;
                    
                case 'health_check':
                    $this->check_integration_health($integration_id);
                    break;
            }
        }
        
        // Render the page
        include plugin_dir_path(dirname(__FILE__)) . 'admin/partials/vortex-integrations.php';
    }
}

// Initialize Integration Registry
$vortex_integration_registry = VORTEX_Integration_Registry::get_instance();

// Create tables on activation
register_activation_hook(__FILE__, array('VORTEX_Integration_Registry', 'create_tables')); 