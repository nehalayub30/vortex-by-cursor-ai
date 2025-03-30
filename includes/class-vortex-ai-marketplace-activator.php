/**
 * Create tables for business plans
 */
private static function create_business_plan_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $business_plans_table = $wpdb->prefix . 'vortex_business_plans';
    $business_plan_milestones_table = $wpdb->prefix . 'vortex_business_plan_milestones';
    $ai_learning_log_table = $wpdb->prefix . 'vortex_ai_learning_log';
    
    $sql = "CREATE TABLE $business_plans_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        title varchar(255) NOT NULL,
        business_idea text NOT NULL,
        business_plan longtext NOT NULL,
        status varchar(50) NOT NULL DEFAULT 'active',
        creation_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        last_updated datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        pdf_url varchar(255) DEFAULT NULL,
        PRIMARY KEY  (id),
        KEY user_id (user_id)
    ) $charset_collate;
    
    CREATE TABLE $business_plan_milestones_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        plan_id bigint(20) NOT NULL,
        day int(11) NOT NULL,
        title varchar(255) NOT NULL,
        description text NOT NULL,
        completed tinyint(1) NOT NULL DEFAULT 0,
        completion_date datetime DEFAULT NULL,
        PRIMARY KEY  (id),
        KEY plan_id (plan_id)
    ) $charset_collate;
    
    CREATE TABLE $ai_learning_log_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        agent_type varchar(50) NOT NULL,
        context_type varchar(50) NOT NULL,
        context_data longtext NOT NULL,
        timestamp int(11) NOT NULL,
        PRIMARY KEY  (id),
        KEY user_id (user_id),
        KEY agent_type (agent_type)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Create table for storing artwork analysis
 */
private static function create_artwork_analysis_table() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $analysis_table = $wpdb->prefix . 'vortex_artwork_analysis';
    
    $sql = "CREATE TABLE $analysis_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        artwork_id bigint(20) NOT NULL,
        analysis_data longtext NOT NULL,
        analysis_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY artwork_id (artwork_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Create tables for TOLA integration
 */
private static function create_tola_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // TOLA NFTs table
    $tola_nfts_table = $wpdb->prefix . 'vortex_tola_nfts';
    $sql_tola_nfts = "CREATE TABLE $tola_nfts_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        artwork_id bigint(20) NOT NULL,
        tx_hash varchar(255) NOT NULL,
        nft_address varchar(255) NOT NULL,
        mint_date datetime NOT NULL,
        metadata_uri varchar(255) NOT NULL,
        tola_program varchar(255) NOT NULL,
        tola_mint varchar(255) NOT NULL,
        royalty_basis_points int(11) NOT NULL DEFAULT 0,
        PRIMARY KEY  (id),
        KEY artwork_id (artwork_id)
    ) $charset_collate;";
    
    // TOLA wallet balances table
    $wallet_table = $wpdb->prefix . 'vortex_wallet_balances';
    $sql_wallet = "CREATE TABLE $wallet_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        tola_balance decimal(18,8) NOT NULL DEFAULT 0.00000000,
        last_updated datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        wallet_address varchar(255) DEFAULT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY user_id (user_id)
    ) $charset_collate;";
    
    // TOLA transactions table
    $transactions_table = $wpdb->prefix . 'vortex_tola_transactions';
    $sql_transactions = "CREATE TABLE $transactions_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        transaction_type varchar(50) NOT NULL,
        amount decimal(18,8) NOT NULL,
        tx_hash varchar(255) DEFAULT NULL,
        artwork_id bigint(20) DEFAULT NULL,
        status varchar(20) NOT NULL DEFAULT 'completed',
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY user_id (user_id),
        KEY artwork_id (artwork_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_tola_nfts);
    dbDelta($sql_wallet);
    dbDelta($sql_transactions);
} 