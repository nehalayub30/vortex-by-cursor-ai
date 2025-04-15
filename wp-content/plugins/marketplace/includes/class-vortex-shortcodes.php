<?php
/**
 * Vortex Shortcodes Manager
 *
 * Centralized management of all plugin shortcodes
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class VORTEX_Shortcodes {
    /**
     * Register all shortcodes
     */
    public static function register_shortcodes() {
        // Artwork related shortcodes
        add_shortcode('vortex_artwork_gallery', array(__CLASS__, 'artwork_gallery_shortcode'));
        add_shortcode('vortex_artist_profile', array(__CLASS__, 'artist_profile_shortcode'));
        
        // AI insights shortcodes
        add_shortcode('vortex_ai_insights', array(__CLASS__, 'ai_insights_shortcode'));
        
        // Analytics shortcodes
        add_shortcode('vortex_marketplace_stats', array(__CLASS__, 'marketplace_stats_shortcode'));
        
        // Blockchain shortcodes
        add_shortcode('vortex_blockchain_stats', array(__CLASS__, 'blockchain_stats_shortcode'));
        add_shortcode('vortex_real_time_metrics', array(__CLASS__, 'real_time_metrics_shortcode'));
        add_shortcode('vortex_tola_stats', array(__CLASS__, 'tola_stats_shortcode'));
        
        // Gamification shortcodes
        add_shortcode('vortex_gamification_leaderboard', array(__CLASS__, 'gamification_leaderboard_shortcode'));
        add_shortcode('vortex_user_achievements', array(__CLASS__, 'user_achievements_shortcode'));
        
        // User dashboard shortcodes
        add_shortcode('vortex_user_dashboard', array(__CLASS__, 'user_dashboard_shortcode'));
        
        // AI agents shortcodes
        add_shortcode('vortex_huraii_assistant', array(__CLASS__, 'huraii_assistant_shortcode'));
        add_shortcode('vortex_cloe_insights', array(__CLASS__, 'cloe_insights_shortcode'));
        add_shortcode('vortex_business_strategy', array(__CLASS__, 'business_strategy_shortcode'));
        add_shortcode('vortex_thorius_blockchain', array(__CLASS__, 'thorius_blockchain_shortcode'));
        
        // Image generator shortcode
        add_shortcode('vortex_image_generator', array(VORTEX_HURAII_Image_Generator::get_instance(), 'image_generator_shortcode'));

        // Market Trends Analysis shortcode
        add_shortcode('vortex_market_trends', array(__CLASS__, 'market_trends_shortcode'));
    }
    
    /**
     * Artwork gallery shortcode
     */
    public static function artwork_gallery_shortcode($atts) {
        $atts = shortcode_atts(array(
            'view' => 'grid',       // grid, carousel, masonry
            'category' => '',       // category slug or ID
            'tags' => '',           // comma-separated list of tags
            'artist' => '',         // artist ID or username
            'limit' => 12,          // number of artworks to display
            'orderby' => 'date',    // date, price, popularity
            'order' => 'desc',      // asc, desc
            'featured_only' => false, // show only featured artworks
        ), $atts);
        
        // Use the assets manager to enqueue needed assets
        $assets_manager = vortex_assets_manager();
        $assets_manager->enqueue_style('vortex-dao');
        
        // Get artwork controller
        $artworks = VORTEX_Artworks::get_instance();
        return $artworks->render_artwork_gallery($atts);
    }
    
    /**
     * Artist profile shortcode
     */
    public static function artist_profile_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'username' => '',
            'show_stats' => 'yes',
            'show_artworks' => 'yes',
            'artwork_limit' => 8
        ), $atts, 'vortex_artist_profile');
        
        // Use the assets manager to enqueue needed assets
        $assets_manager = vortex_assets_manager();
        $assets_manager->enqueue_style('vortex-dao');
        
        // Get artist controller
        $artists = VORTEX_Artists::get_instance();
        return $artists->render_artist_profile($atts);
    }
    
    /**
     * AI insights shortcode
     */
    public static function ai_insights_shortcode($atts) {
        $atts = shortcode_atts(array(
            'agent' => 'all', // all, cloe, huraii, business, thorius
            'type' => 'recent', // recent, trending, popular
            'limit' => 5,
            'show_chart' => 'yes'
        ), $atts, 'vortex_ai_insights');
        
        // Use the assets manager to enqueue needed assets
        $assets_manager = vortex_assets_manager();
        $assets_manager->enqueue_ai_insights_assets();
        
        ob_start();
        include(plugin_dir_path(dirname(__FILE__)) . 'templates/shortcodes/ai-insights.php');
        return ob_get_clean();
    }
    
    /**
     * Marketplace stats shortcode
     */
    public static function marketplace_stats_shortcode($atts) {
        $atts = shortcode_atts(array(
            'period' => 'month', // day, week, month, year, all
            'show_chart' => 'yes',
            'metrics' => 'all' // all, sales, views, users, etc.
        ), $atts, 'vortex_marketplace_stats');
        
        // Use the assets manager to enqueue needed assets
        $assets_manager = vortex_assets_manager();
        $assets_manager->enqueue_script('vortex-metrics');
        
        ob_start();
        include(plugin_dir_path(dirname(__FILE__)) . 'templates/shortcodes/marketplace-stats.php');
        return ob_get_clean();
    }
    
    /**
     * Blockchain stats shortcode
     */
    public static function blockchain_stats_shortcode($atts) {
        $atts = shortcode_atts(array(
            'days' => 30,
            'show_chart' => 'yes',
            'type' => 'all' // all, artists, categories, transactions
        ), $atts, 'vortex_blockchain_stats');
        
        // Use the assets manager to enqueue needed assets
        $assets_manager = vortex_assets_manager();
        $assets_manager->enqueue_blockchain_metrics_assets();
        
        // Get blockchain metrics instance
        $blockchain_metrics = VORTEX_Blockchain_Metrics::get_instance();
        $stats = $blockchain_metrics->get_public_blockchain_stats($atts['days'], $atts['type']);
        
        ob_start();
        include(plugin_dir_path(dirname(__FILE__)) . 'templates/shortcodes/blockchain-stats.php');
        return ob_get_clean();
    }
    
    /**
     * Real-time blockchain metrics shortcode
     */
    public static function real_time_metrics_shortcode($atts) {
        $atts = shortcode_atts(array(
            'metric' => 'all', // all, artworks, artists, swaps, transactions
            'show_chart' => 'yes',
            'auto_refresh' => 'yes',
            'refresh_interval' => 30 // seconds
        ), $atts, 'vortex_real_time_metrics');
        
        // Use the assets manager to enqueue needed assets
        $assets_manager = vortex_assets_manager();
        $assets_manager->enqueue_realtime_metrics_assets();
        
        ob_start();
        include(plugin_dir_path(dirname(__FILE__)) . 'templates/shortcodes/real-time-metrics.php');
        return ob_get_clean();
    }
    
    /**
     * TOLA token stats shortcode
     */
    public static function tola_stats_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_chart' => 'yes',
            'show_distribution' => 'yes',
            'show_transactions' => 'yes',
            'transaction_limit' => 10
        ), $atts, 'vortex_tola_stats');
        
        ob_start();
        include(plugin_dir_path(dirname(__FILE__)) . 'templates/shortcodes/tola-stats.php');
        return ob_get_clean();
    }
    
    /**
     * Gamification leaderboard shortcode
     */
    public static function gamification_leaderboard_shortcode($atts) {
        $atts = shortcode_atts(array(
            'period' => 'all', // all, week, month
            'limit' => 10,
            'show_points' => 'yes',
            'show_badges' => 'yes',
            'show_level' => 'yes'
        ), $atts, 'vortex_gamification_leaderboard');
        
        ob_start();
        include(plugin_dir_path(dirname(__FILE__)) . 'templates/shortcodes/gamification-leaderboard.php');
        return ob_get_clean();
    }
    
    /**
     * User achievements shortcode
     */
    public static function user_achievements_shortcode($atts) {
        $atts = shortcode_atts(array(
            'user_id' => 0, // 0 = current user
            'show_progress' => 'yes',
            'show_next_level' => 'yes',
            'show_badges' => 'yes'
        ), $atts, 'vortex_user_achievements');
        
        ob_start();
        include(plugin_dir_path(dirname(__FILE__)) . 'templates/shortcodes/user-achievements.php');
        return ob_get_clean();
    }
    
    /**
     * User dashboard shortcode
     */
    public static function user_dashboard_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_artworks' => 'yes',
            'show_stats' => 'yes',
            'show_blockchain' => 'yes',
            'show_achievements' => 'yes'
        ), $atts, 'vortex_user_dashboard');
        
        ob_start();
        include(plugin_dir_path(dirname(__FILE__)) . 'templates/shortcodes/user-dashboard.php');
        return ob_get_clean();
    }
    
    /**
     * HURAII assistant shortcode
     */
    public static function huraii_assistant_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_image_generator' => 'yes',
            'initial_prompt' => '',
            'theme' => 'light' // light, dark
        ), $atts, 'vortex_huraii_assistant');
        
        // Use the assets manager to enqueue needed assets
        $assets_manager = vortex_assets_manager();
        if ($atts['show_image_generator'] === 'yes') {
            $assets_manager->enqueue_image_generator_assets();
        }
        
        ob_start();
        include(plugin_dir_path(dirname(__FILE__)) . 'templates/shortcodes/huraii-assistant.php');
        return ob_get_clean();
    }
    
    /**
     * CLOE insights shortcode
     */
    public static function cloe_insights_shortcode($atts) {
        $atts = shortcode_atts(array(
            'insight_type' => 'market', // market, user, trend
            'show_chart' => 'yes',
            'limit' => 5
        ), $atts, 'vortex_cloe_insights');
        
        ob_start();
        include(plugin_dir_path(dirname(__FILE__)) . 'templates/shortcodes/cloe-insights.php');
        return ob_get_clean();
    }
    
    /**
     * Business strategy shortcode
     */
    public static function business_strategy_shortcode($atts) {
        $atts = shortcode_atts(array(
            'strategy_type' => 'pricing', // pricing, market, growth
            'show_recommendations' => 'yes',
            'show_chart' => 'yes'
        ), $atts, 'vortex_business_strategy');
        
        ob_start();
        include(plugin_dir_path(dirname(__FILE__)) . 'templates/shortcodes/business-strategy.php');
        return ob_get_clean();
    }
    
    /**
     * Thorius blockchain shortcode
     */
    public static function thorius_blockchain_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_contracts' => 'yes',
            'show_transactions' => 'yes',
            'show_tokenized' => 'yes',
            'limit' => 5
        ), $atts, 'vortex_thorius_blockchain');
        
        ob_start();
        include(plugin_dir_path(dirname(__FILE__)) . 'templates/shortcodes/thorius-blockchain.php');
        return ob_get_clean();
    }

    /**
     * Market Trends Analysis shortcode for all users
     * Displays user-friendly insights without revealing sensitive data
     */
    public static function market_trends_shortcode($atts = array(), $content = null) {
        $atts = shortcode_atts(array(
            'period' => 'month',
            'type' => 'all',
            'limit' => 10
        ), $atts, 'vortex_market_trends');
        
        // Common users should not see ROI or investment data
        $is_admin = current_user_can('manage_options');
        
        // Get market trends data from Business Strategist
        $market_data = array();
        if (class_exists('VORTEX_Business_Strategist')) {
            $strategist = VORTEX_Business_Strategist::get_instance();
            $market_data = $strategist->get_public_market_trends($atts['period'], $atts['type']);
        }
        
        // Get trending styles from CLOE
        $trending_styles = array();
        if (class_exists('VORTEX_CLOE')) {
            $cloe = VORTEX_CLOE::get_instance();
            $trending_styles = $cloe->get_popular_styles($atts['period']);
        }
        
        // Buffer output
        ob_start();
        include(plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/shortcodes/market-trends.php');
        return ob_get_clean();
    }
}

// Register shortcodes
add_action('init', array('VORTEX_Shortcodes', 'register_shortcodes')); 