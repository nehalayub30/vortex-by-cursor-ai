class VORTEX_Learning_Activator {
    public static function activate() {
        self::create_learning_tables();
        self::create_growth_tables();
    }

    private static function create_learning_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}vortex_learning_metrics (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            learning_type varchar(50) NOT NULL,
            metrics_data longtext NOT NULL,
            timestamp datetime NOT NULL,
            growth_stage varchar(20) NOT NULL,
            evolution_score decimal(10,2) NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY learning_type (learning_type),
            KEY timestamp (timestamp)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
} 