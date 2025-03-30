function vortex_transaction_history_shortcode($atts) {
    // Load required dependencies
    require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-transaction-manager.php';
    
    // Create transaction manager instance
    $transaction_manager = new Vortex_Transaction_Manager();
    
    // Get user transactions
    $user_id = get_current_user_id();
    if (!$user_id) {
        return '<div class="vortex-notice">' . __('Please log in to view your transaction history.', 'vortex-ai-marketplace') . '</div>';
    }
    
    // Get transactions from the database
    $transactions = $transaction_manager->get_user_transactions($user_id, $atts['limit'], $atts['type']);
    
    // Get user TOLA balance
    $user_balance = vortex_get_user_tola_balance(get_current_user_id());
    
    // Format and display transactions
    // ...
} 