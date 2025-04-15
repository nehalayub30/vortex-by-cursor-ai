<?php
class VORTEX_TOLA_Integration {
    private $health_check_interval = 300; // 5 minutes
    
    public function __construct() {
        add_action('init', array($this, 'schedule_health_checks'));
        add_action('vortex_tola_health_check', array($this, 'perform_health_check'));
    }
    
    public function schedule_health_checks() {
        if (!wp_next_scheduled('vortex_tola_health_check')) {
            wp_schedule_event(time(), 'five_minutes', 'vortex_tola_health_check');
        }
    }
    
    public function perform_health_check() {
        try {
            // Check TOLA connection
            $connection_status = $this->check_connection();
            
            // Check smart contract status
            $contract_status = $this->check_smart_contracts();
            
            // Check sync status
            $sync_status = $this->check_sync_status();
            
            $health_status = [
                'connection' => $connection_status,
                'contracts' => $contract_status,
                'sync' => $sync_status,
                'timestamp' => current_time('mysql')
            ];
            
            update_option('vortex_tola_health_status', $health_status);
            
            if (!$connection_status['healthy'] || !$contract_status['healthy'] || !$sync_status['healthy']) {
                $this->notify_admin_of_health_issues($health_status);
            }
            
        } catch (Exception $e) {
            error_log('VORTEX TOLA Health Check Error: ' . $e->getMessage());
        }
    }
    
    /**
     * Queue blockchain transaction
     * @param string $type Transaction type
     * @param array $data Transaction data
     * @return int Transaction ID
     */
    private function queue_blockchain_transaction($type, $data) {
        $transaction_id = time() . rand(1000, 9999);
        
        $this->transaction_queue[$transaction_id] = array(
            'id' => $transaction_id,
            'type' => $type,
            'data' => $data,
            'status' => 'pending',
            'attempts' => 0,
            'queued_at' => time(),
            'last_attempt' => 0,
            'result' => null
        );
        
        update_option('vortex_tola_transaction_queue', $this->transaction_queue);
        
        return $transaction_id;
    }
    
    /**
     * Process transaction queue
     */
    public function process_transaction_queue() {
        // Skip if no API key
        if (empty($this->api_key)) {
            error_log('TOLA: Cannot process transaction queue - API key not configured');
            return;
        }
        
        $processed = 0;
        $updated_queue = array();
        
        foreach ($this->transaction_queue as $transaction_id => $transaction) {
            // Skip already completed or failed transactions
            if ($transaction['status'] === 'completed' || $transaction['status'] === 'failed') {
                $updated_queue[$transaction_id] = $transaction;
                continue;
            }
            
            // Skip transactions with too many attempts
            if ($transaction['attempts'] >= 5) {
                $transaction['status'] = 'failed';
                $transaction['result'] = 'Max attempts reached';
                $updated_queue[$transaction_id] = $transaction;
                
                error_log("TOLA: Transaction {$transaction_id} failed after max attempts");
                continue;
            }
            
            // Skip transactions attempted recently (wait 5 minutes between attempts)
            if ($transaction['last_attempt'] > 0 && $transaction['last_attempt'] > time() - 300) {
                $updated_queue[$transaction_id] = $transaction;
                continue;
            }
            
            // Update attempt counter and timestamp
            $transaction['attempts']++;
            $transaction['last_attempt'] = time();
            
            // Process transaction based on type
            $result = false;
            
            switch ($transaction['type']) {
                case 'create_contract':
                    $result = $this->execute_create_contract($transaction['data']);
                    break;
                    
                case 'register_sale':
                    $result = $this->execute_register_sale($transaction['data']);
                    break;
                    
                default:
                    $transaction['status'] = 'failed';
                    $transaction['result'] = 'Unknown transaction type';
                    break;
            }
            
            // Update transaction status
            if ($result !== false) {
                $transaction['status'] = 'completed';
                $transaction['result'] = $result;
                
                // Update metadata for the associated artwork
                if ($transaction['type'] === 'create_contract' && isset($transaction['data']['artwork_id'])) {
                    $artwork_id = $transaction['data']['artwork_id'];
                    update_post_meta($artwork_id, 'vortex_tola_contract_id', $result['contract_id']);
                    update_post_meta($artwork_id, 'vortex_tola_contract_hash', $result['contract_hash']);
                    update_post_meta($artwork_id, 'vortex_tola_contract_url', $result['contract_url']);
                }
                
                $processed++;
            }
            
            $updated_queue[$transaction_id] = $transaction;
            
            // Limit processing to 10 transactions per run to avoid timeouts
            if ($processed >= 10) {
                break;
            }
        }
        
        // Update queue in database
        $this->transaction_queue = $updated_queue;
        update_option('vortex_tola_transaction_queue', $updated_queue);
        
        if ($processed > 0) {
            error_log("TOLA: Processed {$processed} blockchain transactions");
        }
    }
    
    /**
     * Execute contract creation on blockchain
     * @param array $data Contract data
     * @return array|bool Result or false on failure
     */
    private function execute_create_contract($data) {
        if (!isset($data['artwork_id']) || !isset($data['contract_data'])) {
            return false;
        }
        
        // Prepare API request
        $endpoint = $this->api_endpoints['base'] . $this->api_endpoints['contract'];
        
        $response = wp_remote_post(
            $endpoint,
            array(
                'timeout' => 45,
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->api_key
                ),
                'body' => json_encode($data['contract_data'])
            )
        );
        
        // Check for API errors
        if (is_wp_error($response)) {
            error_log('TOLA API Error: ' . $response->get_error_message());
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 200 && $status_code !== 201) {
            error_log('TOLA API Error: ' . isset($body['message']) ? $body['message'] : 'Unknown error');
            return false;
        }
        
        // Return contract details
        return array(
            'contract_id' => isset($body['contract_id']) ? $body['contract_id'] : '',
            'contract_hash' => isset($body['contract_hash']) ? $body['contract_hash'] : '',
            'contract_url' => isset($body['contract_url']) ? $body['contract_url'] : '',
            'token_id' => isset($body['token_id']) ? $body['token_id'] : ''
        );
    }
    
    /**
     * Execute sale registration on blockchain
     * @param array $data Sale data
     * @return array|bool Result or false on failure
     */
    private function execute_register_sale($data) {
        if (!isset($data['contract_id']) || !isset($data['buyer_id']) || !isset($data['price'])) {
            return false;
        }
        
        // Prepare API request
        $endpoint = $this->api_endpoints['base'] . $this->api_endpoints['transaction'];
        
        $response = wp_remote_post(
            $endpoint,
            array(
                'timeout' => 45,
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->api_key
                ),
                'body' => json_encode(array(
                    'contract_id' => $data['contract_id'],
                    'from_address' => $this->get_user_wallet_address($data['seller_id']),
                    'to_address' => $this->get_user_wallet_address($data['buyer_id']),
                    'transaction_type' => 'transfer',
                    'amount' => $data['price'],
                    'currency' => $data['currency'],
                    'timestamp' => time()
                ))
            )
        );
        
        // Check for API errors
        if (is_wp_error($response)) {
            error_log('TOLA API Error: ' . $response->get_error_message());
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code !== 200 && $status_code !== 201) {
            error_log('TOLA API Error: ' . isset($body['message']) ? $body['message'] : 'Unknown error');
            return false;
        }
        
        // Return transaction details
        return array(
            'transaction_id' => isset($body['transaction_id']) ? $body['transaction_id'] : '',
            'transaction_hash' => isset($body['transaction_hash']) ? $body['transaction_hash'] : '',
            'transaction_url' => isset($body['transaction_url']) ? $body['transaction_url'] : ''
        );
    }
    
    /**
     * Get user wallet address
     * @param int $user_id User ID
     * @return string Wallet address
     */
    private function get_user_wallet_address($user_id) {
        $wallet_address = get_user_meta($user_id, 'vortex_tola_wallet_address', true);
        
        if (empty($wallet_address)) {
            // Generate a placeholder address if none exists
            $wallet_address = '0x' . md5('vortex_user_' . $user_id . '_' . time());
            update_user_meta($user_id, 'vortex_tola_wallet_address', $wallet_address);
        }
        
        return $wallet_address;
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
            error_log('Invalid security token for TOLA command');
            return;
        }
        
        // Process different command types
        if (stripos($command, 'process queue') !== false) {
            $this->process_transaction_queue();
            error_log('TOLA: Transaction queue processing initiated via admin command');
        } elseif (stripos($command, 'sync contracts') !== false) {
            $this->sync_blockchain_contracts();
            error_log('TOLA: Blockchain contract synchronization initiated via admin command');
        } elseif (stripos($command, 'generate report') !== false) {
            $report = $this->generate_blockchain_report();
            $this->email_blockchain_report($report);
            error_log('TOLA: Blockchain report generated and sent via admin command');
        } else {
            // Update persona
            $agent_personas = get_option('vortex_agent_personas', array());
            if (isset($agent_personas['tola'])) {
                $agent_personas['tola']['persona'] = $command;
                update_option('vortex_agent_personas', $agent_personas);
                
                // Log persona update
                error_log('TOLA persona updated: ' . substr($command, 0, 100) . '...');
            }
        }
    }
    
    /**
     * Sync blockchain contracts
     */
    private function sync_blockchain_contracts() {
        global $wpdb;
        
        // Get artworks with missing or pending contracts
        $artworks = $wpdb->get_results(
            "SELECT p.ID as artwork_id, p.post_title as title, p.post_author as author_id, 
                    pm.meta_value as contract_id
             FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'vortex_tola_contract_id'
             WHERE p.post_type = 'vortex_artwork'
             AND p.post_status = 'publish'
             AND (pm.meta_value IS NULL OR pm.meta_value LIKE 'pending_%')
             LIMIT 50"
        );
        
        $synced_count = 0;
        
        foreach ($artworks as $artwork) {
            // Skip if has pending contract
            if (!empty($artwork->contract_id) && strpos($artwork->contract_id, 'pending_') === 0) {
                continue;
            }
            
            // Prepare artwork data
            $artwork_data = array(
                'artwork_id' => $artwork->artwork_id,
                'creator' => $artwork->author_id,
                'title' => $artwork->title,
                'description' => get_post_field('post_content', $artwork->artwork_id),
                'dimensions' => get_post_meta($artwork->artwork_id, 'vortex_dimensions', true),
                'metadata' => array(
                    'prompt' => get_post_meta($artwork->artwork_id, 'vortex_prompt', true),
                    'style' => get_post_meta($artwork->artwork_id, 'vortex_style', true),
                    'generation_params' => get_post_meta($artwork->artwork_id, 'vortex_generation_params', true)
                )
            );
            
            // Create contract
            $this->create_smart_contract($artwork_data);
            $synced_count++;
        }
        
        if ($synced_count > 0) {
            error_log("TOLA: Synchronized {$synced_count} artworks with blockchain");
        }
    }
    
    /**
     * Generate blockchain report
     * @return array Report data
     */
    private function generate_blockchain_report() {
        global $wpdb;
        
        // Get contract statistics
        $contract_stats_query = "SELECT 
            COUNT(DISTINCT pm.post_id) as total_contracts,
            SUM(CASE WHEN pm.meta_value NOT LIKE 'pending_%' THEN 1 ELSE 0 END) as active_contracts,
            SUM(CASE WHEN pm.meta_value LIKE 'pending_%' THEN 1 ELSE 0 END) as pending_contracts
         FROM {$wpdb->postmeta} pm
         WHERE pm.meta_key = 'vortex_tola_contract_id'";
        
        $contract_stats = $wpdb->get_row($contract_stats_query);
        
        // Get transaction statistics
        $transaction_stats_query = "SELECT 
            COUNT(*) as total_transactions,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_transactions,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_transactions,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_transactions
         FROM {$wpdb->prefix}vortex_blockchain_transactions";
        
        $transaction_stats = $wpdb->get_row($transaction_stats_query);
        
        // Get token statistics
        $token_stats_query = "SELECT 
            AVG(CAST(meta_value AS DECIMAL(10,2))) as avg_token_value,
            MIN(CAST(meta_value AS DECIMAL(10,2))) as min_token_value,
            MAX(CAST(meta_value AS DECIMAL(10,2))) as max_token_value
         FROM {$wpdb->postmeta} 
         WHERE meta_key = 'vortex_tola_token_value'";
        
        $token_stats = $wpdb->get_row($token_stats_query);
        
        // Get recent transactions
        $recent_transactions_query = "SELECT 
            t.transaction_id,
            t.transaction_type,
            t.from_address,
            t.to_address,
            t.amount,
            t.transaction_date,
            t.status,
            p.post_title as artwork_title
         FROM {$wpdb->prefix}vortex_blockchain_transactions t
         LEFT JOIN {$wpdb->posts} p ON t.artwork_id = p.ID
         ORDER BY t.transaction_date DESC
         LIMIT 10";
        
        $recent_transactions = $wpdb->get_results($recent_transactions_query);
        
        // Prepare report
        $report = array(
            'generated_time' => time(),
            'contract_stats' => $contract_stats,
            'transaction_stats' => $transaction_stats,
            'token_stats' => $token_stats,
            'recent_transactions' => $recent_transactions,
            'queue_status' => array(
                'total_queued' => count($this->transaction_queue),
                'pending' => count(array_filter($this->transaction_queue, function($t) { return $t['status'] === 'pending'; })),
                'completed' => count(array_filter($this->transaction_queue, function($t) { return $t['status'] === 'completed'; })),
                'failed' => count(array_filter($this->transaction_queue, function($t) { return $t['status'] === 'failed'; }))
            )
        );
        
        return $report;
    }
    
    /**
     * Email blockchain report
     * @param array $report Report data
     */
    private function email_blockchain_report($report) {
        // Prepare email content
        $subject = 'Vortex Marketplace: TOLA Blockchain Report';
        
        $message = '<h1>TOLA Blockchain Status Report</h1>';
        $message .= '<p>Generated on ' . date('F j, Y', $report['generated_time']) . '</p>';
        
        // Add contract statistics
        $message .= '<h2>Smart Contract Statistics</h2>';
        $message .= '<ul>';
        $message .= '<li><strong>Total Contracts:</strong> ' . $report['contract_stats']->total_contracts . '</li>';
        $message .= '<li><strong>Active Contracts:</strong> ' . $report['contract_stats']->active_contracts . '</li>';
        $message .= '<li><strong>Pending Contracts:</strong> ' . $report['contract_stats']->pending_contracts . '</li>';
        $message .= '</ul>';
        
        // Add transaction statistics
        $message .= '<h2>Transaction Statistics</h2>';
        $message .= '<ul>';
        $message .= '<li><strong>Total Transactions:</strong> ' . $report['transaction_stats']->total_transactions . '</li>';
        $message .= '<li><strong>Completed Transactions:</strong> ' . $report['transaction_stats']->completed_transactions . '</li>';
        $message .= '<li><strong>Pending Transactions:</strong> ' . $report['transaction_stats']->pending_transactions . '</li>';
        $message .= '<li><strong>Failed Transactions:</strong> ' . $report['transaction_stats']->failed_transactions . '</li>';
        $message .= '</ul>';
        
        // Add token statistics
        $message .= '<h2>TOLA Token Statistics</h2>';
        $message .= '<ul>';
        $message .= '<li><strong>Average Token Value:</strong> $' . number_format($report['token_stats']->avg_token_value, 2) . '</li>';
        $message .= '<li><strong>Minimum Token Value:</strong> $' . number_format($report['token_stats']->min_token_value, 2) . '</li>';
        $message .= '<li><strong>Maximum Token Value:</strong> $' . number_format($report['token_stats']->max_token_value, 2) . '</li>';
        $message .= '</ul>';
        
        // Add transaction queue status
        $message .= '<h2>Transaction Queue Status</h2>';
        $message .= '<ul>';
        $message .= '<li><strong>Total Queued:</strong> ' . $report['queue_status']['total_queued'] . '</li>';
        $message .= '<li><strong>Pending:</strong> ' . $report['queue_status']['pending'] . '</li>';
        $message .= '<li><strong>Completed:</strong> ' . $report['queue_status']['completed'] . '</li>';
        $message .= '<li><strong>Failed:</strong> ' . $report['queue_status']['failed'] . '</li>';
        $message .= '</ul>';
        
        // Add recent transactions
        $message .= '<h2>Recent Transactions</h2>';
        if (empty($report['recent_transactions'])) {
            $message .= '<p>No recent transactions found.</p>';
        } else {
            $message .= '<table border="1" cellpadding="5" style="border-collapse: collapse;">';
            $message .= '<tr><th>Date</th><th>Type</th><th>Artwork</th><th>Amount</th><th>Status</th></tr>';
            
            foreach ($report['recent_transactions'] as $transaction) {
                $message .= sprintf(
                    '<tr><td>%s</td><td>%s</td><td>%s</td><td>$%s</td><td>%s</td></tr>',
                    date('Y-m-d H:i', strtotime($transaction->transaction_date)),
                    ucfirst($transaction->transaction_type),
                    $transaction->artwork_title,
                    number_format($transaction->amount, 2),
                    ucfirst($transaction->status)
                );
            }
            
            $message .= '</table>';
        }
        
        // Send to admin
        $admin_email = get_option('admin_email');
        wp_mail($admin_email, $subject, $message, array('Content-Type: text/html; charset=UTF-8'));
    }

    public function enhance_blockchain_features() {
        return [
            'smart_contracts' => [
                'contract_templates' => $this->optimize_contract_templates(),
                'execution_monitoring' => $this->monitor_contract_execution(),
                'gas_optimization' => $this->optimize_gas_usage()
            ],
            'transaction_system' => [
                'batch_processing' => $this->implement_batch_transactions(),
                'fee_optimization' => $this->optimize_transaction_fees(),
                'confirmation_tracking' => $this->track_confirmations()
            ],
            'asset_management' => [
                'tokenization' => $this->enhance_tokenization_process(),
                'ownership_tracking' => $this->track_ownership_changes(),
                'royalty_distribution' => $this->manage_royalties()
            ]
        ];
    }

    /**
     * Log blockchain operation
     *
     * @param string $level Log level
     * @param string $operation Specific operation
     * @param string $message Log message
     * @param array $context Additional context
     * @param array $data Technical data
     * @return int|false Log ID or false on failure
     */
    protected function log_operation($level, $operation, $message, $context = array(), $data = array()) {
        if (class_exists('VORTEX_Blockchain_Error_Logger')) {
            $logger = VORTEX_Blockchain_Error_Logger::get_instance();
            return $logger->log($level, VORTEX_Blockchain_Error_Logger::CAT_TRANSACTION, $operation, $message, $context, $data);
        }
        
        // Fallback to WP error log
        if (in_array($level, array('error', 'critical'))) {
            error_log("VORTEX Blockchain {$level}: [{$operation}] {$message}");
        }
        
        return false;
    }

    /**
     * Log debug message
     *
     * @param string $operation Specific operation
     * @param string $message Log message
     * @param array $context Additional context
     * @param array $data Technical data
     */
    protected function log_debug($operation, $message, $context = array(), $data = array()) {
        $this->log_operation('debug', $operation, $message, $context, $data);
    }

    /**
     * Log info message
     *
     * @param string $operation Specific operation
     * @param string $message Log message
     * @param array $context Additional context
     * @param array $data Technical data
     */
    protected function log_info($operation, $message, $context = array(), $data = array()) {
        $this->log_operation('info', $operation, $message, $context, $data);
    }

    /**
     * Log warning message
     *
     * @param string $operation Specific operation
     * @param string $message Log message
     * @param array $context Additional context
     * @param array $data Technical data
     */
    protected function log_warning($operation, $message, $context = array(), $data = array()) {
        $this->log_operation('warning', $operation, $message, $context, $data);
    }

    /**
     * Log error message
     *
     * @param string $operation Specific operation
     * @param string $message Log message
     * @param array $context Additional context
     * @param array $data Technical data
     */
    protected function log_error($operation, $message, $context = array(), $data = array()) {
        $this->log_operation('error', $operation, $message, $context, $data);
    }

    /**
     * Log critical message
     *
     * @param string $operation Specific operation
     * @param string $message Log message
     * @param array $context Additional context
     * @param array $data Technical data
     */
    protected function log_critical($operation, $message, $context = array(), $data = array()) {
        $this->log_operation('critical', $operation, $message, $context, $data);
    }
}

// Initialize TOLA Integration
add_action('plugins_loaded', function() {
    new VORTEX_TOLA_Integration();
}); 