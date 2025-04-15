class VORTEX_Gamification_Activator {
    public static function activate() {
        self::create_metrics_tables();
        self::create_leaderboard_tables();
        self::create_achievement_tables();
    }

    private static function create_metrics_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}vortex_user_metrics (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            metric_type varchar(50) NOT NULL,
            metric_value decimal(20,8) NOT NULL,
            timestamp datetime NOT NULL,
            additional_data longtext,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY metric_type (metric_type),
            KEY timestamp (timestamp)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
} 