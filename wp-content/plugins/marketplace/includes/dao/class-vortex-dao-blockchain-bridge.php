/**
 * Execute proposal on blockchain
 */
public function execute_proposal_on_blockchain($proposal_id) {
    global $wpdb;
    
    // Get proposal data
    $proposal = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vortex_dao_proposals WHERE id = %d",
        $proposal_id
    ), ARRAY_A);
    
    if (!$proposal) {
        throw new Exception('Proposal not found');
    }
    
    // Connect to wallet service
    $wallet_service = VORTEX_Wallet_Service::get_instance();
    
    // Prepare transaction based on proposal type
    switch ($proposal['proposal_type']) {
        case 'parameter_change':
            $tx_data = $this->prepare_parameter_change_tx($proposal);
            break;
        case 'treasury_transfer':
            $tx_data = $this->prepare_treasury_transfer_tx($proposal);
            break;
        case 'contract_upgrade':
            $tx_data = $this->prepare_contract_upgrade_tx($proposal);
            break;
        default:
            throw new Exception('Unsupported proposal type for blockchain execution');
    }
    
    // Send transaction
    $result = $wallet_service->send_transaction($tx_data);
    
    // Update proposal with transaction data
    $wpdb->update(
        $wpdb->prefix . 'vortex_dao_proposals',
        array(
            'status' => 'executed',
            'executed_at' => current_time('mysql'),
            'execution_data' => wp_json_encode($result)
        ),
        array('id' => $proposal_id),
        array('%s', '%s', '%s'),
        array('%d')
    );
    
    return $result;
} 