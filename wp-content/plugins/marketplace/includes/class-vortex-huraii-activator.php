class VORTEX_HURAII_Activator {
    public static function activate() {
        self::create_contract_table();
        self::create_royalty_table();
    }

    private static function create_contract_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}vortex_huraii_contracts (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            contract_address varchar(42) NOT NULL,
            artwork_id bigint(20) NOT NULL,
            creator_id bigint(20) NOT NULL,
            deployment_date datetime NOT NULL,
            contract_data longtext NOT NULL,
            status varchar(20) DEFAULT 'active',
            PRIMARY KEY  (id),
            KEY contract_address (contract_address),
            KEY artwork_id (artwork_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    private static function create_royalty_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}vortex_royalty_distributions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            contract_address varchar(42) NOT NULL,
            inventor_royalty decimal(5,2) NOT NULL,
            transaction_date datetime NOT NULL,
            transaction_hash varchar(66) NOT NULL,
            status varchar(20) DEFAULT 'completed',
            PRIMARY KEY  (id),
            KEY contract_address (contract_address),
            KEY transaction_hash (transaction_hash)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
} 