            .adjustment-insights h3 {
                font-size: 1.1em;
                margin-top: 0;
                margin-bottom: 15px;
            }
            
            .adjustment-insights ul {
                margin: 0;
                padding: 0;
                list-style: none;
            }
            
            .adjustment-insights li {
                display: flex;
                align-items: flex-start;
                margin-bottom: 10px;
                padding-left: 5px;
            }
            
            .insight-icon {
                display: inline-block;
                width: 20px;
                height: 20px;
                line-height: 20px;
                text-align: center;
                border-radius: 50%;
                margin-right: 10px;
                flex-shrink: 0;
                font-weight: bold;
            }
            
            .insight-icon.positive {
                background: #e8f5e9;
                color: #28a745;
            }
            
            .insight-icon.negative {
                background: #fbe9e7;
                color: #dc3545;
            }
            
            .insight-icon.neutral {
                background: #e1f5fe;
                color: #0288d1;
            }
            
            .predictions-table {
                width: 100%;
                border-collapse: collapse;
            }
            
            .predictions-table th {
                text-align: left;
            }
            
            .predictions-table .positive {
                color: #28a745;
            }
            
            .predictions-table .negative {
                color: #dc3545;
            }
            
            .confidence-indicator {
                width: 100%;
                height: 8px;
                background: #f0f0f0;
                border-radius: 4px;
                overflow: hidden;
            }
            
            .confidence-bar {
                height: 100%;
                background: #4CAF50;
                border-radius: 4px;
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#run-price-predictions').on('click', function() {
                var $button = $(this);
                $button.prop('disabled', true).text('<?php _e('Processing...', 'vortex-marketplace'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'vortex_run_price_predictions',
                        nonce: '<?php echo wp_create_nonce('vortex_run_price_predictions'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $button.text('<?php _e('Price Predictions Updated!', 'vortex-marketplace'); ?>');
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            $button.prop('disabled', false).text('<?php _e('Run Price Predictions', 'vortex-marketplace'); ?>');
                            alert('<?php _e('Error updating price predictions. Please try again.', 'vortex-marketplace'); ?>');
                        }
                    },
                    error: function() {
                        $button.prop('disabled', false).text('<?php _e('Run Price Predictions', 'vortex-marketplace'); ?>');
                        alert('<?php _e('Error updating price predictions. Please try again.', 'vortex-marketplace'); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Register AJAX handlers
     */
    public function register_ajax_handlers() {
        add_action('wp_ajax_vortex_update_artwork_price', array($this, 'ajax_update_artwork_price'));
        add_action('wp_ajax_vortex_run_price_predictions', array($this, 'ajax_run_price_predictions'));
    }
    
    /**
     * AJAX handler for updating artwork price
     */
    public function ajax_update_artwork_price() {
        check_ajax_referer('vortex_ajax', 'nonce');
        
        $artwork_id = isset($_POST['artwork_id']) ? intval($_POST['artwork_id']) : 0;
        $new_price = isset($_POST['new_price']) ? floatval($_POST['new_price']) : 0;
        
        if (!$artwork_id || $new_price <= 0) {
            wp_send_json_error(array('message' => __('Invalid artwork or price value', 'vortex-marketplace')));
            return;
        }
        
        global $wpdb;
        
        // Check if user owns this artwork
        $artwork = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vortex_artworks WHERE id = %d",
            $artwork_id
        ));
        
        if (!$artwork || $artwork->user_id != get_current_user_id()) {
            wp_send_json_error(array('message' => __('You do not have permission to update this artwork', 'vortex-marketplace')));
            return;
        }
        
        // Update price
        $result = $wpdb->update(
            $wpdb->prefix . 'vortex_artworks',
            array('price' => $new_price),
            array('id' => $artwork_id),
            array('%f'),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error(array('message' => __('Error updating price', 'vortex-marketplace')));
            return;
        }
        
        // Log the price update
        $wpdb->insert(
            $wpdb->prefix . 'vortex_system_logs',
            array(
                'log_type' => 'price_update',
                'message' => sprintf('Artist updated artwork #%d price from $%.2f to $%.2f based on prediction', 
                    $artwork_id, $artwork->price, $new_price),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s')
        );
        
        wp_send_json_success(array(
            'message' => __('Price updated successfully', 'vortex-marketplace')
        ));
    }
    
    /**
     * AJAX handler for running price predictions
     */
    public function ajax_run_price_predictions() {
        check_ajax_referer('vortex_run_price_predictions', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'vortex-marketplace')));
            return;
        }
        
        // Run price predictions
        $this->update_price_predictions();
        
        // Log the action
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'vortex_system_logs',
            array(
                'log_type' => 'admin_action',
                'message' => sprintf('Admin manually triggered price predictions update at %s', 
                    current_time('mysql')),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s')
        );
        
        wp_send_json_success(array(
            'message' => __('Price predictions updated successfully', 'vortex-marketplace')
        ));
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $tables = array();
        
        // Price predictions table
        $tables[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}vortex_price_predictions (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            artwork_id bigint(20) unsigned NOT NULL,
            base_price decimal(10,2) NOT NULL DEFAULT '0.00',
            optimal_price decimal(10,2) NOT NULL DEFAULT '0.00',
            confidence_score decimal(4,3) NOT NULL DEFAULT '0.000',
            prediction_date datetime NOT NULL,
            market_factors longtext,
            PRIMARY KEY (id),
            KEY artwork_id (artwork_id),
            KEY prediction_date (prediction_date)
        ) $charset_collate;";
        
        // NFT valuations table
        $tables[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}vortex_nft_valuations (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nft_id varchar(255) NOT NULL,
            artwork_id bigint(20) unsigned NOT NULL,
            initial_value decimal(10,2) NOT NULL DEFAULT '0.00',
            current_value decimal(10,2) NOT NULL DEFAULT '0.00',
            projected_value decimal(10,2) NOT NULL DEFAULT '0.00',
            last_updated datetime NOT NULL,
            valuation_factors longtext,
            PRIMARY KEY (id),
            UNIQUE KEY nft_id (nft_id),
            KEY artwork_id (artwork_id)
        ) $charset_collate;";
        
        // Price sensitivity segments table
        $tables[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}vortex_price_sensitivity (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            segment varchar(50) NOT NULL DEFAULT 'mid_tier',
            confidence_score decimal(4,3) NOT NULL DEFAULT '0.800',
            avg_purchase_price decimal(10,2) DEFAULT NULL,
            last_calculated datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        foreach ($tables as $table) {
            dbDelta($table);
        }
        
        update_option('vortex_predictive_pricing_db_version', '1.0');
    }
} 