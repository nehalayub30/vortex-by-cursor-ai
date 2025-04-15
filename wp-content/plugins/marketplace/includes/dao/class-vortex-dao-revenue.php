        // Get monthly revenue for chart (last 12 months)
        $monthly_revenue = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $month_name = date('M Y', strtotime("-$i months"));
            
            $monthly_revenue[] = [
                'month' => $month_name,
                'revenue' => $this->get_revenue_by_month($month),
            ];
        }
        
        ob_start();
        include plugin_dir_path(dirname(dirname(__FILE__))) . 'public/partials/dao-revenue-stats.php';
        return ob_get_clean();
    }
    
    /**
     * Process order fees.
     *
     * @param int $order_id Order ID.
     * @param array $posted_data Posted data.
     * @param WC_Order $order Order object.
     */
    public function process_order_fees($order_id, $posted_data, $order) {
        // Check if order is valid
        if (!$order) {
            return;
        }
        
        // Get fees from order
        $sale_fee = 0;
        $swap_fee = 0;
        
        foreach ($order->get_fees() as $fee) {
            $fee_name = $fee->get_name();
            
            if (strpos($fee_name, 'Sale Fee') !== false) {
                $sale_fee += $fee->get_total();
            } elseif (strpos($fee_name, 'Swap Fee') !== false) {
                $swap_fee += $fee->get_total();
            }
        }
        
        // Process sale fee
        if ($sale_fee > 0) {
            $treasury = VORTEX_DAO_Treasury::get_instance();
            $treasury->process_revenue(
                $sale_fee,
                'sale_fee',
                'order_' . $order_id,
                ['order_id' => $order_id]
            );
        }
        
        // Process swap fee
        if ($swap_fee > 0) {
            $treasury = VORTEX_DAO_Treasury::get_instance();
            $treasury->process_revenue(
                $swap_fee,
                'swap_fee',
                'order_' . $order_id,
                ['order_id' => $order_id]
            );
        }
    }
    
    /**
     * Process marketplace commission.
     *
     * @param int $sale_id Sale ID.
     * @param float $sale_amount Sale amount.
     * @param int $artist_id Artist user ID.
     */
    public function process_marketplace_commission($sale_id, $sale_amount, $artist_id) {
        // Calculate commission amount
        $commission_rate = $this->config['marketplace_commission'] / 100;
        $commission_amount = $sale_amount * $commission_rate;
        
        // Process revenue
        $treasury = VORTEX_DAO_Treasury::get_instance();
        $treasury->process_revenue(
            $commission_amount,
            'marketplace_commission',
            'sale_' . $sale_id,
            [
                'sale_id' => $sale_id,
                'sale_amount' => $sale_amount,
                'artist_id' => $artist_id,
                'commission_rate' => $commission_rate,
            ]
        );
    }
    
    /**
     * Process subscription fee.
     *
     * @param int $subscription_id Subscription ID.
     * @param int $user_id User ID.
     */
    public function process_subscription_fee($subscription_id, $user_id) {
        // Get subscription amount
        $subscription_amount = $this->config['subscription_fee'];
        
        // Process revenue
        $treasury = VORTEX_DAO_Treasury::get_instance();
        $treasury->process_revenue(
            $subscription_amount,
            'subscription',
            'subscription_' . $subscription_id,
            [
                'subscription_id' => $subscription_id,
                'user_id' => $user_id,
            ]
        );
    }
    
    /**
     * Process exhibition ticket.
     *
     * @param int $ticket_id Ticket ID.
     * @param int $user_id User ID.
     */
    public function process_exhibition_ticket($ticket_id, $user_id) {
        // Get ticket amount
        $ticket_amount = $this->config['exhibition_ticket'];
        
        // Process revenue
        $treasury = VORTEX_DAO_Treasury::get_instance();
        $treasury->process_revenue(
            $ticket_amount,
            'exhibition_ticket',
            'ticket_' . $ticket_id,
            [
                'ticket_id' => $ticket_id,
                'user_id' => $user_id,
            ]
        );
    }
    
    /**
     * Get revenue by type.
     *
     * @param string $type Revenue type.
     * @param string $period Optional period (all, year, month, week).
     * @return float Total revenue by type.
     */
    public function get_revenue_by_type($type, $period = 'all') {
        global $wpdb;
        
        $where = "WHERE transaction_type = %s";
        $params = [$type];
        
        if ($period !== 'all') {
            $date_formats = [
                'year' => 'YEAR(transaction_date) = YEAR(CURRENT_DATE)',
                'month' => 'YEAR(transaction_date) = YEAR(CURRENT_DATE) AND MONTH(transaction_date) = MONTH(CURRENT_DATE)',
                'week' => 'YEARWEEK(transaction_date) = YEARWEEK(CURRENT_DATE)',
            ];
            
            if (isset($date_formats[$period])) {
                $where .= " AND " . $date_formats[$period];
            }
        }
        
        $total = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(amount) FROM {$wpdb->prefix}vortex_dao_revenue $where",
                $params
            )
        );
        
        return $total ? floatval($total) : 0;
    }
    
    /**
     * Get revenue by month.
     *
     * @param string $month Month in format YYYY-MM.
     * @return float Total revenue for the month.
     */
    public function get_revenue_by_month($month) {
        global $wpdb;
        
        $total = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(amount) FROM {$wpdb->prefix}vortex_dao_revenue
                WHERE DATE_FORMAT(transaction_date, '%%Y-%%m') = %s",
                $month
            )
        );
        
        return $total ? floatval($total) : 0;
    }
    
    /**
     * AJAX: Process manual revenue.
     */
    public function ajax_process_manual_revenue() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_process_manual_revenue')) {
            wp_send_json_error(['message' => __('Security check failed.', 'vortex')]);
        }
        
        // Check if user has permission
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'vortex')]);
        }
        
        // Get revenue details
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $source = isset($_POST['source']) ? sanitize_text_field($_POST['source']) : '';
        $reference = isset($_POST['reference']) ? sanitize_text_field($_POST['reference']) : '';
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
        
        if ($amount <= 0 || empty($source)) {
            wp_send_json_error(['message' => __('Invalid revenue details.', 'vortex')]);
        }
        
        // Process revenue
        $treasury = VORTEX_DAO_Treasury::get_instance();
        $success = $treasury->process_revenue(
            $amount,
            $source,
            $reference,
            ['notes' => $notes]
        );
        
        if (!$success) {
            wp_send_json_error(['message' => __('Failed to process revenue.', 'vortex')]);
        }
        
        wp_send_json_success([
            'message' => sprintf(__('Successfully processed %s in revenue.', 'vortex'), number_format($amount, 2)),
            'allocations' => [
                'operations' => ($amount * $this->config['revenue_operations_allocation']) / 100,
                'investor' => ($amount * $this->config['revenue_investor_allocation']) / 100,
                'creator_grant' => ($amount * $this->config['revenue_creator_grant_allocation']) / 100,
                'reserve' => ($amount * $this->config['revenue_reserve_allocation']) / 100,
            ],
        ]);
    }
} 