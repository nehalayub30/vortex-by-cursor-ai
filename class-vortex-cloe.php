<?php
/**
 * VORTEX CLOE AI Curation Agent
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage AI
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_CLOE Class
 * 
 * CLOE (Curation & Learning Optimization Engine) handles personalized 
 * user experiences, behavioral analysis, and trend correlation.
 */
class VORTEX_CLOE {
    /**
     * Use the analytics trait for safe database operations
     */
    use VORTEX_CLOE_Analytics;
    
    /**
     * Instance of this class.
     */
    protected static $instance = null;
    
    /**
     * Learning models and capabilities
     */
    private $learning_models = array();
    
    /**
     * User data tracking categories
     */
    private $tracking_categories = array();
    
    /**
     * Greeting templates
     */
    private $greeting_templates = array();
    
    /**
     * Current trends data
     */
    private $current_trends = array();
    
    /**
     * User behavior metrics
     */
    private $behavior_metrics = array();
    
    /**
     * Marketing intelligence data
     */
    private $marketing_data = array();
    
    /**
     * Constructor
     */
    private function __construct() {
        // Initialize learning models
        $this->initialize_learning_models();
        
        // Initialize tracking categories
        $this->initialize_tracking_categories();
        
        // Initialize greeting templates
        $this->initialize_greeting_templates();
        
        // Set up hooks
        $this->setup_hooks();
        
        // Initialize behavioral data collection
        $this->initialize_behavior_metrics();
        
        // Initialize marketing data
        $this->initialize_marketing_data();
        
        // Initialize trends tracking
        $this->initialize_trend_tracking();
    }
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize learning models
     */
    private function initialize_learning_models() {
        $this->learning_models = array(
            'user_preferences' => array(
                'path' => VORTEX_PLUGIN_PATH . 'models/cloe/user_preferences.model',
                'last_trained' => get_option('vortex_cloe_user_preferences_trained', 0),
                'batch_size' => 32,
                'learning_rate' => 0.001
            ),
            'curation' => array(
                'path' => VORTEX_PLUGIN_PATH . 'models/cloe/curation.model',
                'last_trained' => get_option('vortex_cloe_curation_trained', 0),
                'batch_size' => 24,
                'learning_rate' => 0.002
            ),
            'behavior_analysis' => array(
                'path' => VORTEX_PLUGIN_PATH . 'models/cloe/behavior_analysis.model',
                'last_trained' => get_option('vortex_cloe_behavior_analysis_trained', 0),
                'batch_size' => 48,
                'learning_rate' => 0.0015
            ),
            'demographic_insights' => array(
                'path' => VORTEX_PLUGIN_PATH . 'models/cloe/demographic_insights.model',
                'last_trained' => get_option('vortex_cloe_demographic_insights_trained', 0),
                'batch_size' => 32,
                'learning_rate' => 0.001
            ),
            'trend_correlation' => array(
                'path' => VORTEX_PLUGIN_PATH . 'models/cloe/trend_correlation.model',
                'last_trained' => get_option('vortex_cloe_trend_correlation_trained', 0),
                'batch_size' => 64,
                'learning_rate' => 0.0005
            ),
            'seo_optimization' => array(
                'path' => VORTEX_PLUGIN_PATH . 'models/cloe/seo_optimization.model',
                'last_trained' => get_option('vortex_cloe_seo_optimization_trained', 0),
                'batch_size' => 32,
                'learning_rate' => 0.001
            ),
            'content_personalization' => array(
                'path' => VORTEX_PLUGIN_PATH . 'models/cloe/content_personalization.model',
                'last_trained' => get_option('vortex_cloe_content_personalization_trained', 0),
                'batch_size' => 16,
                'learning_rate' => 0.002
            )
        );
        
        // Check for missing model files and create placeholders
        foreach ($this->learning_models as $model_name => $model_data) {
            if (!file_exists($model_data['path'])) {
                $model_dir = dirname($model_data['path']);
                if (!file_exists($model_dir)) {
                    wp_mkdir_p($model_dir);
                }
                file_put_contents($model_data['path'], 'CLOE Model Placeholder: ' . $model_name);
            }
        }
    }
    
    /**
     * Initialize tracking categories
     */
    private function initialize_tracking_categories() {
        $this->tracking_categories = array(
            'temporal' => array(
                'hour_of_day' => array(
                    'type' => 'numeric',
                    'range' => range(0, 23),
                    'tracking' => 'automatic'
                ),
                'day_of_week' => array(
                    'type' => 'numeric',
                    'range' => range(0, 6),
                    'tracking' => 'automatic'
                ),
                'session_duration' => array(
                    'type' => 'numeric',
                    'tracking' => 'automatic'
                ),
                'return_frequency' => array(
                    'type' => 'categorical',
                    'values' => array('daily', 'weekly', 'monthly', 'occasional'),
                    'tracking' => 'derived'
                )
            ),
            'demographic' => array(
                'region' => array(
                    'type' => 'categorical',
                    'tracking' => 'ip_based',
                    'privacy' => 'anonymized'
                ),
                'country' => array(
                    'type' => 'categorical',
                    'tracking' => 'ip_based',
                    'privacy' => 'anonymized'
                ),
                'gender' => array(
                    'type' => 'categorical',
                    'values' => array('male', 'female', 'non_binary', 'other', 'undisclosed'),
                    'tracking' => 'profile_based',
                    'privacy' => 'user_controlled'
                ),
                'age_group' => array(
                    'type' => 'categorical',
                    'values' => array('under_18', '18_24', '25_34', '35_44', '45_54', '55_64', '65_plus', 'undisclosed'),
                    'tracking' => 'profile_based',
                    'privacy' => 'user_controlled'
                ),
                'language' => array(
                    'type' => 'categorical',
                    'tracking' => 'browser_based'
                )
            ),
            'behavioral' => array(
                'view_duration' => array(
                    'type' => 'numeric',
                    'tracking' => 'automatic'
                ),
                'click_patterns' => array(
                    'type' => 'complex',
                    'tracking' => 'automatic'
                ),
                'search_patterns' => array(
                    'type' => 'complex',
                    'tracking' => 'automatic'
                ),
                'style_preferences' => array(
                    'type' => 'categorical',
                    'tracking' => 'derived'
                ),
                'price_sensitivity' => array(
                    'type' => 'numeric',
                    'range' => array(0, 10),
                    'tracking' => 'derived'
                ),
                'social_sharing' => array(
                    'type' => 'boolean',
                    'tracking' => 'action_based'
                )
            ),
            'marketing' => array(
                'referral_source' => array(
                    'type' => 'categorical',
                    'tracking' => 'url_based'
                ),
                'utm_campaign' => array(
                    'type' => 'categorical',
                    'tracking' => 'url_based'
                ),
                'keyword_effectiveness' => array(
                    'type' => 'complex',
                    'tracking' => 'derived'
                ),
                'conversion_path' => array(
                    'type' => 'complex',
                    'tracking' => 'derived'
                )
            ),
            'content' => array(
                'preferred_formats' => array(
                    'type' => 'categorical',
                    'values' => array('2d_image', '3d_model', 'video', 'audio', 'interactive'),
                    'tracking' => 'derived'
                ),
                'style_affinities' => array(
                    'type' => 'complex',
                    'tracking' => 'derived'
                ),
                'artist_followings' => array(
                    'type' => 'complex',
                    'tracking' => 'action_based'
                ),
                'theme_interests' => array(
                    'type' => 'complex',
                    'tracking' => 'derived'
                )
            )
        );
    }
    
    /**
     * Initialize greeting templates with humor and motivation
     */
    private function initialize_greeting_templates() {
        $this->greeting_templates = array(
            'time_based' => array(
                'morning' => array(
                    'Welcome, %s! Ready to make art as amazing as your morning coffee?',
                    'Good morning, %s! The creative sun is shining just for you today.',
                    'Morning, %s! Let\'s turn those sleepy dreams into stunning visuals.',
                    'Rise and design, %s! The art world awaits your morning brilliance.'
                ),
                'afternoon' => array(
                    'Afternoon inspiration calling, %s! Ready to answer with your creativity?',
                    'Hi %s! Fighting that afternoon slump with some creative therapy?',
                    'Afternoon, %s! Perfect time to make something that will make tomorrow jealous.',
                    'The afternoon muse has arrived, %s! Let\'s create something extraordinary.'
                ),
                'evening' => array(
                    'Evening, %s! Time to create by the glow of inspiration (and your screen).',
                    'Good evening, %s! Let\'s end the day on a creative high note.',
                    'Evening creativity hits different, doesn\'t it, %s? Let\'s make magic happen.',
                    'Stars are out, %s, and so is your creative potential tonight!'
                ),
                'night' => array(
                    'Night owl or just inspired, %s? Either way, let\'s make this midnight magic count!',
                    'Creating after dark, %s? That\'s when the best ideas come out to play.',
                    'The night is young and so are your ideas, %s! Let\'s bring them to life.',
                    'Burning the creative midnight oil, %s? Your dedication is inspiring!'
                )
            ),
            'returning_user' => array(
                'short_absence' => array(
                    'Welcome back, %s! Your creative projects missed you (almost as much as I did)!',
                    'Look who\'s back! %s has returned to bless us with more creative brilliance!',
                    'Missed you, %s! The creative void in your absence was palpable.',
                    'The creative prodigal returns! Welcome back, %s – ready to pick up where you left off?'
                ),
                'long_absence' => array(
                    'Is that really YOU, %s?! The creative world has been wondering where you\'ve been!',
                    'Well, well, well... look who finally remembered their password! Welcome back, %s!',
                    '%s has returned! Should we alert the art media, or keep your comeback our secret?',
                    'After your extended creative sabbatical, %s, you\'re back! The art world can resume now.'
                )
            ),
            'achievement_based' => array(
                'new_milestone' => array(
                    'Look at you go, %s! %d creations and still breaking boundaries!',
                    'Creative milestone unlocked, %s! %d pieces and counting – you\'re on fire!',
                    'Achievement unlocked: %s has created %d pieces of brilliance! What can\'t you do?',
                    '%d creations, %s? That\'s not just talent – that\'s dedication!'
                ),
                'first_sale' => array(
                    'STOP EVERYTHING! %s just made their first sale! The art world will never be the same!',
                    'Someone just recognized your genius, %s! First sale complete – fame and fortune await!',
                    'Breaking news: %s just made their first sale! Next stop: artistic world domination!',
                    'First sale alert! %s, this calls for a creative victory dance!'
                )
            ),
            'trend_based' => array(
                'following_trends' => array(
                    'I see you\'re riding the %s wave, %s! Your timing is as impeccable as your taste.',
                    'Jumping on the %s trend, %s? Your take is refreshingly original!',
                    'Everyone\'s talking about %s, and now you\'re joining in, %s! Can\'t wait to see your spin on it.',
                    'The %s trend was missing something – turns out it was your contribution, %s!'
                ),
                'trend_setter' => array(
                    'Move over influencers, %s is setting trends with %s before it\'s even cool!',
                    'Always ahead of the curve, aren\'t you, %s? Your work in %s is setting tomorrow\'s trends!',
                    'The %s movement called – they want to thank you, %s, for showing them the future!',
                    'Not following trends but MAKING them – %s, your %s work is what everyone will be copying tomorrow!'
                )
            ),
            'style_based' => array(
                'consistent_style' => array(
                    'That signature %s style of yours, %s – it\'s becoming as recognizable as a Picasso!',
                    'There\'s that %s touch that could only come from you, %s! It\'s becoming your creative fingerprint.',
                    'I\'d recognize your %s style anywhere, %s! It\'s becoming legendary around here.',
                    'The %s master returns! %s, your consistent style is building you quite the reputation!'
                ),
                'style_explorer' => array(
                    'From %s to %s – is there any style you can\'t conquer, %s?',
                    'Creative chameleon alert! %s is switching from %s to %s with impressive versatility!',
                    'Genre-hopping from %s to %s? %s, your creative range is showing (and it\'s impressive)!',
                    'Experimenting from %s to %s? %s, your artistic curiosity is truly inspiring!'
                )
            ),
            'suggestion_based' => array(
                'new_features' => array(
                    'Have you tried the new %s feature yet, %s? I think it has your creative name all over it!',
                    'Psst, %s! The new %s feature just dropped, and it\'s practically begging for your creative touch.',
                    '%s, meet %s – our newest feature that I\'m pretty sure was inspired by creative minds like yours!',
                    'Creative recommendation: %s should check out our new %s feature! It matches your style perfectly.'
                ),
                'trending_content' => array(
                    'Everyone\'s talking about %s right now, %s! Curious to see your take on it.',
                    'The creative world is obsessed with %s this week, %s. Care to join the conversation?',
                    '%s is trending in the art world, %s! Your unique perspective would make an amazing contribution.',
                    'Word on the street is that %s is the next big thing, %s. Seems right up your creative alley!'
                )
            )
        );
    }
    
    /**
     * Set up hooks
     */
    private function setup_hooks() {
        // User interaction tracking
        add_action('wp_login', array($this, 'track_user_login'), 10, 2);
        add_action('vortex_artwork_viewed', array($this, 'track_artwork_view'), 10, 2);
        add_action('vortex_artwork_liked', array($this, 'track_artwork_like'), 10, 2);
        add_action('vortex_artwork_shared', array($this, 'track_artwork_share'), 10, 3);
        add_action('vortex_artwork_purchased', array($this, 'track_artwork_purchase'), 10, 3);
        add_action('vortex_artist_followed', array($this, 'track_artist_follow'), 10, 2);
        add_action('vortex_search_performed', array($this, 'track_search_query'), 10, 2);
        add_action('vortex_swipe_action', array($this, 'track_swipe_action'), 10, 3);
        
        // Session tracking
        add_action('wp_login', array($this, 'start_session_tracking'), 10, 2);
        
        // Re-register the end_session_tracking hook explicitly to ensure it works
        remove_action('wp_logout', array($this, 'end_session_tracking'));
        add_action('wp_logout', array($this, 'end_session_tracking'), 10);
        
        // Add session tracking to init hook
        remove_action('init', array($this, 'continue_session_tracking'));
        add_action('init', array($this, 'continue_session_tracking'), 10);
        
        // Admin reporting
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widgets'));
        
        // AJAX handlers
        add_action('wp_ajax_vortex_cloe_get_greeting', array($this, 'ajax_get_greeting'));
        add_action('wp_ajax_nopriv_vortex_cloe_get_greeting', array($this, 'ajax_get_greeting'));
        add_action('wp_ajax_vortex_cloe_get_recommendations', array($this, 'ajax_get_recommendations'));
        add_action('wp_ajax_vortex_cloe_get_trends', array($this, 'ajax_get_trends'));
        
        // Scheduled tasks
        add_action('vortex_daily_trend_update', array($this, 'update_trend_data'));
        add_action('vortex_weekly_seo_report', array($this, 'generate_seo_report'));
        add_action('vortex_monthly_analytics', array($this, 'generate_monthly_analytics'));
        
        if (!wp_next_scheduled('vortex_daily_trend_update')) {
            wp_schedule_event(time(), 'daily', 'vortex_daily_trend_update');
        }
        
        if (!wp_next_scheduled('vortex_weekly_seo_report')) {
            wp_schedule_event(time(), 'weekly', 'vortex_weekly_seo_report');
        }
        
        if (!wp_next_scheduled('vortex_monthly_analytics')) {
            wp_schedule_event(time(), 'monthly', 'vortex_monthly_analytics');
        }
    }
    
    /**
     * Initialize behavior metrics
     */
    private function initialize_behavior_metrics() {
        $this->behavior_metrics = array(
            'temporal_patterns' => array(
                'peak_hours' => $this->get_peak_activity_hours(),
                'weekday_distribution' => $this->get_weekday_distribution(),
                'session_duration_avg' => $this->get_average_session_duration()
            ),
            'demographic_insights' => array(
                'region_distribution' => $this->get_region_distribution(),
                'age_group_distribution' => $this->get_age_group_distribution(),
                'gender_distribution' => $this->get_gender_distribution(),
                'language_preferences' => $this->get_language_preferences()
            ),
            'engagement_metrics' => array(
                'view_to_like_ratio' => $this->calculate_view_to_like_ratio(),
                'average_view_duration' => $this->get_average_view_duration(),
                'style_affinity_clusters' => $this->get_style_affinity_clusters()
            ),
            'conversion_metrics' => array(
                'browse_to_purchase_funnel' => $this->get_purchase_funnel_metrics(),
                'abandoned_carts' => $this->get_abandoned_cart_stats(),
                'price_sensitivity_curve' => $this->get_price_sensitivity_data()
            )
        );
    }
    
    /**
     * Initialize marketing data
     */
    private function initialize_marketing_data() {
        $this->marketing_data = array(
            'seo' => array(
                'top_keywords' => $this->get_top_performing_keywords(),
                'trending_search_terms' => $this->get_trending_search_terms(),
                'optimal_tags' => $this->generate_optimal_tags()
            ),
            'content_strategy' => array(
                'popular_styles' => $this->get_popular_styles(),
                'emerging_themes' => $this->get_emerging_themes(),
                'content_gaps' => $this->identify_content_gaps()
            ),
            'user_acquisition' => array(
                'top_referral_sources' => $this->get_top_referral_sources(),
                'campaign_performance' => $this->get_campaign_performance(),
                'user_retention_rates' => $this->get_retention_rates()
            ),
            'social_impact' => array(
                'sharing_analytics' => $this->get_social_sharing_analytics(),
                'viral_content_patterns' => $this->analyze_viral_content(),
                'hashtag_effectiveness' => $this->analyze_hashtag_effectiveness()
            )
        );
    }
    
    /**
     * Initialize trend tracking
     */
    private function initialize_trend_tracking() {
        $this->update_trend_data();
    }
    
    /**
     * Update trend data
     */
    public function update_trend_data() {
        // Internal platform trends
        $platform_trends = $this->analyze_platform_trends();
        
        // External art market trends
        $external_trends = $this->fetch_external_art_trends();
        
        // Correlate internal and external trends
        $correlated_trends = $this->correlate_trends($platform_trends, $external_trends);
        
        $this->current_trends = array(
            'platform' => $platform_trends,
            'external' => $external_trends,
            'correlated' => $correlated_trends,
            'last_updated' => current_time('timestamp')
        );
        
        update_option('vortex_cloe_current_trends', $this->current_trends);
        
        return $this->current_trends;
    }
    
    /**
     * Get personalized greeting for user
     */
    public function get_personalized_greeting($user_id = 0) {
        // Get current user if not specified
        if ($user_id === 0 && is_user_logged_in()) {
            $user_id = get_current_user_id();
        }
        
        // Default greeting for non-logged in users
        if ($user_id === 0) {
            $time_category = $this->get_time_of_day_category();
            $greeting_templates = $this->greeting_templates['time_based'][$time_category];
            $greeting = $greeting_templates[array_rand($greeting_templates)];
            return sprintf($greeting, 'creative explorer');
        }
        
        // Get user data
        $user = get_userdata($user_id);
        $display_name = $user->display_name;
        
        // Check for user activity data
        $last_login = get_user_meta($user_id, 'vortex_last_login', true);
        $login_count = get_user_meta($user_id, 'vortex_login_count', true);
        $artwork_count = $this->get_user_artwork_count($user_id);
        $has_sales = $this->user_has_sales($user_id);
        $preferred_styles = $this->get_user_preferred_styles($user_id);
        $followed_trends = $this->get_user_followed_trends($user_id);
        
        // Determine greeting type
        $greeting_type = $this->determine_greeting_type($user_id, $last_login, $login_count, $artwork_count, $has_sales);
        
        // Generate appropriate greeting
        switch ($greeting_type) {
            case 'returning_short':
                $templates = $this->greeting_templates['returning_user']['short_absence'];
                $greeting = $templates[array_rand($templates)];
                return sprintf($greeting, $display_name);
                
            case 'returning_long':
                $templates = $this->greeting_templates['returning_user']['long_absence'];
                $greeting = $templates[array_rand($templates)];
                return sprintf($greeting, $display_name);
                
            case 'milestone':
                $templates = $this->greeting_templates['achievement_based']['new_milestone'];
                $greeting = $templates[array_rand($templates)];
                return sprintf($greeting, $display_name, $artwork_count);
                
            case 'first_sale':
                $templates = $this->greeting_templates['achievement_based']['first_sale'];
                $greeting = $templates[array_rand($templates)];
                return sprintf($greeting, $display_name);
                
            case 'trend_following':
                if (!empty($followed_trends)) {
                    $trend = $followed_trends[array_rand($followed_trends)];
                    $templates = $this->greeting_templates['trend_based']['following_trends'];
                    $greeting = $templates[array_rand($templates)];
                    return sprintf($greeting, $trend, $display_name);
                }
                // Fall through to default if no trends
                
            case 'style_consistent':
                if (!empty($preferred_styles)) {
                    $style = $preferred_styles[array_rand($preferred_styles)];
                    $templates = $this->greeting_templates['style_based']['consistent_style'];
                    $greeting = $templates[array_rand($templates)];
                    return sprintf($greeting, $style, $display_name);
                }
                // Fall through to default if no styles
                
            default:
                // Default to time-based greeting
                $time_category = $this->get_time_of_day_category();
                $greeting_templates = $this->greeting_templates['time_based'][$time_category];
                $greeting = $greeting_templates[array_rand($greeting_templates)];
                return sprintf($greeting, $display_name);
        }
    }
    
    /**
     * Determine which type of greeting to use
     */
    private function determine_greeting_type($user_id, $last_login, $login_count, $artwork_count, $has_sales) {
        // Just made first sale
        if ($this->is_recent_first_sale($user_id)) {
            return 'first_sale';
        }
        
        // Recent milestone achievement
        if ($this->is_recent_milestone($user_id, $artwork_count)) {
            return 'milestone';
        }
        
        // Long absence (more than 30 days)
        if ($last_login && (time() - $last_login) > 30 * DAY_IN_SECONDS) {
            return 'returning_long';
        }
        
        // Short absence (2-30 days)
        if ($last_login && (time() - $last_login) > 2 * DAY_IN_SECONDS) {
            return 'returning_short';
        }
        
        // User follows trends
        if ($this->user_follows_trends($user_id)) {
            return 'trend_following';
        }
        
        // User has consistent style
        if ($this->user_has_consistent_style($user_id)) {
            return 'style_consistent';
        }
        
        // Default time-based greeting
        return 'time_based';
    }
    
    /**
     * Get time of day category
     */
    private function get_time_of_day_category() {
        $hour = (int)current_time('G');
        
        if ($hour >= 5 && $hour < 12) {
            return 'morning';
        } elseif ($hour >= 12 && $hour < 17) {
            return 'afternoon';
        } elseif ($hour >= 17 && $hour < 22) {
            return 'evening';
        } else {
            return 'night';
        }
    }
    
    /**
     * Track user login
     */
    public function track_user_login($user_login, $user) {
        $user_id = $user->ID;
        
        // Get previous login time
        $last_login = get_user_meta($user_id, 'vortex_last_login', true);
        
        // Update login count
        $login_count = (int)get_user_meta($user_id, 'vortex_login_count', true);
        $login_count++;
        update_user_meta($user_id, 'vortex_login_count', $login_count);
        
        // Update last login time
        update_user_meta($user_id, 'vortex_last_login', time());
        
        // Record login for trend analysis
        $this->record_user_event($user_id, 'login', array(
            'timestamp' => time(),
            'previous_login' => $last_login
        ));
        
        // Prepare for AI learning
        do_action('vortex_ai_agent_learn', 'CLOE', 'user_login', array(
            'user_id' => $user_id,
            'login_count' => $login_count,
            'last_login' => $last_login,
            'timestamp' => time()
        ));
    }
    
    /**
     * Start session tracking
     * 
     * @param string $user_login User login name
     * @param WP_User $user User object
     */
    public function start_session_tracking($user_login, $user) {
        try {
            $user_id = $user->ID;
            
            // Generate unique session ID
            $session_id = md5(uniqid($user_id . '_', true));
            
            // Store session info
            update_user_meta($user_id, 'vortex_current_session', $session_id);
            
            // Record session start time
            $start_time = time();
            update_user_meta($user_id, 'vortex_session_start', $start_time);
            
            // Get user's IP and user agent
            $ip_address = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';
            $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';
            
            // Store session data in the database
            global $wpdb;
            $session_table = $wpdb->prefix . 'vortex_user_sessions';
            
            // Check if the table exists before attempting to insert
            if ($this->table_exists('vortex_user_sessions')) {
                $current_time = date('Y-m-d H:i:s', $start_time);
                
                // Insert session record
                $wpdb->insert(
                    $session_table,
                    array(
                        'session_id' => $session_id,
                        'user_id' => $user_id,
                        'start_time' => $current_time,
                        'last_activity' => $current_time,
                        'activity_time' => $current_time,
                        'ip_address' => $ip_address,
                        'user_agent' => $user_agent,
                        'active' => 1
                    ),
                    array('%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d')
                );
            }
            
            // Record the session start event
            $this->record_user_event($user_id, 'session_start', array(
                'session_id' => $session_id,
                'timestamp' => $start_time,
                'ip_address' => $ip_address,
                'user_agent' => $user_agent
            ));
        } catch (Exception $e) {
            // Log error but prevent it from breaking the page
            error_log('VORTEX_CLOE: Error in start_session_tracking: ' . $e->getMessage());
        }
    }
    
    /**
     * End session tracking
     */
    public function end_session_tracking() {
        try {
            // Check if user is logged in
            if (!is_user_logged_in()) {
                return;
            }
            
            $user_id = get_current_user_id();
            
            // Get current session
            $session_id = get_user_meta($user_id, 'vortex_current_session', true);
            
            if (empty($session_id)) {
                return;
            }
            
            // Get session start time
            $start_time = (int)get_user_meta($user_id, 'vortex_session_start', true);
            $end_time = time();
            $duration = $end_time - $start_time;
            
            // Update session in database
            global $wpdb;
            $session_table = $wpdb->prefix . 'vortex_user_sessions';
            
            // Check if table exists before attempting update
            if ($this->table_exists('vortex_user_sessions')) {
                $result = $wpdb->update(
                    $session_table,
                    array(
                        'end_time' => date('Y-m-d H:i:s', $end_time),
                        'duration' => $duration,
                        'active' => 0
                    ),
                    array('session_id' => $session_id),
                    array('%s', '%d', '%d'),
                    array('%s')
                );
                
                if ($result === false) {
                    // Log error but don't throw exception
                    error_log('VORTEX_CLOE: Failed to update session in database. Session ID: ' . $session_id);
                }
            }
            
            // Record session end event
            $this->record_user_event($user_id, 'session_end', array(
                'session_id' => $session_id,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'duration' => $duration
            ));
            
            // Clear session data
            delete_user_meta($user_id, 'vortex_current_session');
            delete_user_meta($user_id, 'vortex_session_start');
        } catch (Exception $e) {
            // Log error but prevent it from breaking the page
            error_log('VORTEX_CLOE: Error in end_session_tracking: ' . $e->getMessage());
        }
    }
    
    /**
     * Continue session tracking
     */
    public function continue_session_tracking() {
        try {
            // Check if user is logged in
            if (!is_user_logged_in()) {
                return;
            }
            
            $user_id = get_current_user_id();
            
            // Check if user has an active session
            $session_id = get_user_meta($user_id, 'vortex_current_session', true);
            
            if (empty($session_id)) {
                // Start a new session if none exists
                $this->start_new_session($user_id);
                return;
            }
            
            // Update last activity time
            global $wpdb;
            $session_table = $wpdb->prefix . 'vortex_user_sessions';
            
            // Check if table exists before attempting update
            if ($this->table_exists('vortex_user_sessions')) {
                $result = $wpdb->update(
                    $session_table,
                    array(
                        'last_activity' => current_time('mysql'),
                        'activity_time' => current_time('mysql')
                    ),
                    array('session_id' => $session_id),
                    array('%s', '%s'),
                    array('%s')
                );
                
                if ($result === false) {
                    // Log error but don't throw exception
                    error_log('VORTEX_CLOE: Failed to update session activity. Session ID: ' . $session_id);
                }
            }
        } catch (Exception $e) {
            // Log error but prevent it from breaking the page
            error_log('VORTEX_CLOE: Error in continue_session_tracking: ' . $e->getMessage());
        }
    }
    
    /**
     * Start a new session for a user
     * 
     * @param int $user_id User ID
     */
    private function start_new_session($user_id) {
        try {
            // Generate unique session ID
            $session_id = md5(uniqid($user_id . '_', true));
            
            // Store session info
            update_user_meta($user_id, 'vortex_current_session', $session_id);
            
            // Record session start time
            $start_time = time();
            update_user_meta($user_id, 'vortex_session_start', $start_time);
            
            // Get user's IP and user agent
            $ip_address = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';
            $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';
            
            // Store session data in the database
            global $wpdb;
            $session_table = $wpdb->prefix . 'vortex_user_sessions';
            
            // Check if table exists before attempting to insert
            if ($this->table_exists('vortex_user_sessions')) {
                $current_time = date('Y-m-d H:i:s', $start_time);
                
                // Insert session record
                $wpdb->insert(
                    $session_table,
                    array(
                        'session_id' => $session_id,
                        'user_id' => $user_id,
                        'start_time' => $current_time,
                        'last_activity' => $current_time,
                        'activity_time' => $current_time,
                        'ip_address' => $ip_address,
                        'user_agent' => $user_agent,
                        'active' => 1
                    ),
                    array('%s', '%d', '%s', '%s', '%s', '%s', '%s', '%d')
                );
            }
            
            // Record the session start event
            $this->record_user_event($user_id, 'session_start', array(
                'session_id' => $session_id,
                'timestamp' => $start_time,
                'ip_address' => $ip_address,
                'user_agent' => $user_agent
            ));
        } catch (Exception $e) {
            // Log error but prevent it from breaking the page
            error_log('VORTEX_CLOE: Error in start_new_session: ' . $e->getMessage());
        }
    }
    
    /**
     * Ensure the session tracking table exists
     */
    private function ensure_session_table_exists() {
        global $wpdb;
        $session_table = $wpdb->prefix . 'vortex_user_sessions';
        
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$session_table'") === $session_table;
        
        if (!$table_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $session_table (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                session_id varchar(32) NOT NULL,
                user_id bigint(20) unsigned NOT NULL,
                start_time datetime NOT NULL,
                end_time datetime DEFAULT NULL,
                last_activity datetime DEFAULT NULL,
                activity_time datetime DEFAULT CURRENT_TIMESTAMP,
                duration int(11) DEFAULT 0,
                ip_address varchar(45) DEFAULT NULL,
                user_agent text DEFAULT NULL,
                active tinyint(1) DEFAULT 1,
                PRIMARY KEY  (id),
                UNIQUE KEY session_id (session_id),
                KEY user_id (user_id),
                KEY active (active),
                KEY activity_time (activity_time)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        } else {
            // Check if activity_time column exists and add it if missing
            $column_exists = false;
            $columns = $wpdb->get_results("SHOW COLUMNS FROM $session_table");
            foreach ($columns as $column) {
                if ($column->Field === 'activity_time') {
                    $column_exists = true;
                    break;
                }
            }
            
            if (!$column_exists) {
                $wpdb->query("ALTER TABLE $session_table ADD COLUMN activity_time datetime DEFAULT CURRENT_TIMESTAMP AFTER last_activity");
                $wpdb->query("ALTER TABLE $session_table ADD INDEX activity_time (activity_time)");
                
                // Initialize activity_time values
                $wpdb->query("UPDATE $session_table SET activity_time = last_activity WHERE last_activity IS NOT NULL");
                $wpdb->query("UPDATE $session_table SET activity_time = start_time WHERE activity_time IS NULL");
            }
        }
    }
    
    /**
     * Get personalized recommendations for user
     */
    public function get_personalized_recommendations($user_id = 0, $type = 'artwork', $limit = 5) {
        // Get current user if not specified
        if ($user_id === 0 && is_user_logged_in()) {
            $user_id = get_current_user_id();
        }
        
        // For non-logged in users, return trending items
        if ($user_id === 0) {
            return $this->get_trending_items($type, $limit);
        }
        
        // Get user preferences
        $user_preferences = $this->get_user_preferences($user_id);
        
        // Get recommendations based on type
        switch ($type) {
            case 'artwork':
                $recommendations = $this->recommend_artwork($user_id, $user_preferences, $limit);
                break;
                
            case 'artist':
                $recommendations = $this->recommend_artists($user_id, $user_preferences, $limit);
                break;
                
            case 'style':
                $recommendations = $this->recommend_styles($user_id, $user_preferences, $limit);
                break;
                
            case 'feature':
                $recommendations = $this->recommend_features($user_id, $user_preferences, $limit);
                break;
                
            default:
                $recommendations = $this->recommend_artwork($user_id, $user_preferences, $limit);
        }
        
        // Track recommendation generation
        do_action('vortex_ai_agent_learn', 'CLOE', 'recommendations_generated', array(
            'user_id' => $user_id,
            'type' => $type,
            'recommendations' => $recommendations,
            'preferences_used' => $user_preferences,
            'timestamp' => time()
        ));
        
        return $recommendations;
    }
    
    /**
     * Generate SEO report with keywords and hashtags
     */
    public function generate_seo_report() {
        // Analyze platform content
        $content_analysis = $this->analyze_platform_content();
        
        // Get trending external keywords
        $external_keywords = $this->get_external_trending_keywords();
        
        // Generate optimal keywords
        $optimal_keywords = $this->generate_optimal_keywords($content_analysis, $external_keywords);
        
        // Generate hashtag recommendations
        $hashtag_recommendations = $this->generate_hashtag_recommendations($optimal_keywords);
        
        // Create the report
        $report = array(
            'timestamp' => time(),
            'content_analysis' => $content_analysis,
            'optimal_keywords' => $optimal_keywords,
            'hashtag_recommendations' => $hashtag_recommendations,
            'keyword_trends' => array(
                'rising' => $this->get_rising_keywords(),
                'falling' => $this->get_falling_keywords(),
                'stable' => $this->get_stable_keywords()
            ),
            'seo_recommendations' => $this->generate_seo_recommendations(),
            'keyword_difficulty' => $this->analyze_keyword_difficulty($optimal_keywords),
            'content_gaps' => $this->identify_content_gaps()
        );
        
        // Save the report
        update_option('vortex_cloe_latest_seo_report', $report);
        
        // Notify admin if enabled
        if (get_option('vortex_cloe_seo_notifications', false)) {
            $this->notify_admin_of_seo_report();
        }
        
        return $report;
    }
    
    /**
     * Generate monthly analytics report
     */
    public function generate_monthly_analytics() {
        // Collect and analyze data
        $time_period = array(
            'start' => strtotime('first day of last month midnight'),
            'end' => strtotime('last day of last month 23:59:59')
        );
        
        $user_metrics = $this->analyze_user_metrics($time_period);
        $content_metrics = $this->analyze_content_metrics($time_period);
        $financial_metrics = $this->analyze_financial_metrics($time_period);
        $engagement_metrics = $this->analyze_engagement_metrics($time_period);
        
        // Demographic breakdown
        $demographic_analysis = array(
            'age_groups' => $this->analyze_age_group_activity($time_period),
            'genders' => $this->analyze_gender_activity($time_period),
            'regions' => $this->analyze_regional_activity($time_period),
            'languages' => $this->analyze_language_activity($time_period)
        );
        
        // Time-based analysis
        $temporal_analysis = array(
            'hourly_activity' => $this->analyze_hourly_activity($time_period),
            'weekday_activity' => $this->analyze_weekday_activity($time_period),
            'monthly_trends' => $this->analyze_monthly_trends()
        );
        
        // Compile full report
        $report = array(
            'period' => $time_period,
            'generated_at' => time(),
            'user_metrics' => $user_metrics,
            'content_metrics' => $content_metrics,
            'financial_metrics' => $financial_metrics,
            'engagement_metrics' => $engagement_metrics,
            'demographic_analysis' => $demographic_analysis,
            'temporal_analysis' => $temporal_analysis,
            'recommendations' => $this->generate_data_based_recommendations($user_metrics, $content_metrics, $financial_metrics, $engagement_metrics)
        );
        
        // Save the report
        update_option('vortex_cloe_latest_monthly_report', $report);
        
        // Notify admin if enabled
        if (get_option('vortex_cloe_monthly_report_notifications', true)) {
            $this->notify_admin_of_monthly_report();
        }
        
        return $report;
    }
    
    /**
     * AJAX handler for getting personalized greeting
     */
    public function ajax_get_greeting() {
        check_ajax_referer('vortex_cloe_nonce', 'security');
        
        $user_id = get_current_user_id(); // 0 if not logged in
        
        $greeting = $this->get_personalized_greeting($user_id);
        
        wp_send_json_success(array(
            'greeting' => $greeting
        ));
    }
    
    /**
     * AJAX handler for getting recommendations
     */
    public function ajax_get_recommendations() {
        check_ajax_referer('vortex_cloe_nonce', 'security');
        
        $user_id = get_current_user_id(); // 0 if not logged in
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'artwork';
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 5;
        
        $recommendations = $this->get_personalized_recommendations($user_id, $type, $limit);
        
        wp_send_json_success(array(
            'recommendations' => $recommendations
        ));
    }
    
    /**
     * Add CLOE admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('CLOE Intelligence', 'vortex-marketplace'),
            __('CLOE Intelligence', 'vortex-marketplace'),
            'manage_options',
            'vortex-cloe-intelligence',
            array($this, 'display_admin_page')
        );
    }

    public function display_admin_page() {
        // Implementation of the display_admin_page method
    }

    /**
     * Add dashboard widgets for CLOE analytics
     */
    public function add_dashboard_widgets() {
        // Only add widgets for administrators
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Add CLOE recommendations widget
        wp_add_dashboard_widget(
            'vortex_cloe_recommendations',
            __('CLOE AI Recommendations', 'vortex-marketplace'),
            array($this, 'render_recommendations_widget')
        );
        
        // Add CLOE trends widget
        wp_add_dashboard_widget(
            'vortex_cloe_trends',
            __('CLOE Market Trends', 'vortex-marketplace'),
            array($this, 'render_trends_widget')
        );
        
        // Add CLOE user insights widget
        wp_add_dashboard_widget(
            'vortex_cloe_user_insights',
            __('CLOE User Insights', 'vortex-marketplace'),
            array($this, 'render_user_insights_widget')
        );
    }
    
    /**
     * Render the recommendations widget
     */
    public function render_recommendations_widget() {
        // Get marketplace overview data
        $artwork_count = $this->get_total_artwork_count();
        $user_count = $this->get_total_user_count();
        $transaction_count = $this->get_total_transaction_count();
        
        // Get recent recommendations
        $recommendations = $this->get_admin_recommendations(5);
        
        ?>
        <div class="vortex-cloe-widget">
            <div class="vortex-cloe-stats">
                <div class="vortex-cloe-stat-item">
                    <span class="vortex-cloe-stat-value"><?php echo esc_html($artwork_count); ?></span>
                    <span class="vortex-cloe-stat-label"><?php _e('Artworks', 'vortex-marketplace'); ?></span>
                </div>
                <div class="vortex-cloe-stat-item">
                    <span class="vortex-cloe-stat-value"><?php echo esc_html($user_count); ?></span>
                    <span class="vortex-cloe-stat-label"><?php _e('Users', 'vortex-marketplace'); ?></span>
                </div>
                <div class="vortex-cloe-stat-item">
                    <span class="vortex-cloe-stat-value"><?php echo esc_html($transaction_count); ?></span>
                    <span class="vortex-cloe-stat-label"><?php _e('Transactions', 'vortex-marketplace'); ?></span>
                </div>
            </div>
            
            <h3><?php _e('Recommendations', 'vortex-marketplace'); ?></h3>
            
            <?php if (empty($recommendations)) : ?>
                <p><?php _e('No recommendations available at this time.', 'vortex-marketplace'); ?></p>
            <?php else : ?>
                <ul class="vortex-cloe-recommendations">
                    <?php foreach ($recommendations as $recommendation) : ?>
                        <li>
                            <div class="vortex-cloe-recommendation-type"><?php echo esc_html($recommendation['type']); ?></div>
                            <div class="vortex-cloe-recommendation-content"><?php echo esc_html($recommendation['content']); ?></div>
                            <?php if (!empty($recommendation['action_url']) && !empty($recommendation['action_text'])) : ?>
                                <a href="<?php echo esc_url($recommendation['action_url']); ?>" class="button button-small">
                                    <?php echo esc_html($recommendation['action_text']); ?>
                                </a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            
            <div class="vortex-cloe-footer">
                <a href="<?php echo admin_url('admin.php?page=vortex-cloe-intelligence'); ?>" class="button">
                    <?php _e('View All Intelligence', 'vortex-marketplace'); ?>
                </a>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render the trends widget
     */
    public function render_trends_widget() {
        // Get current trends data
        $trends = $this->get_current_trends();
        
        if (empty($trends) || empty($trends['platform'])) {
            echo '<p>' . __('No trend data available. Check back later.', 'vortex-marketplace') . '</p>';
            return;
        }
        
        // Get top platform trends
        $platform_trends = array_slice($trends['platform'], 0, 5);
        
        ?>
        <div class="vortex-cloe-widget">
            <h3><?php _e('Current Platform Trends', 'vortex-marketplace'); ?></h3>
            
            <ul class="vortex-cloe-trends">
                <?php foreach ($platform_trends as $trend) : ?>
                    <li>
                        <div class="vortex-cloe-trend-name"><?php echo esc_html($trend['name']); ?></div>
                        <div class="vortex-cloe-trend-score">
                            <div class="vortex-cloe-trend-score-bar" style="width: <?php echo min(100, $trend['score'] * 10); ?>%;"></div>
                        </div>
                        <div class="vortex-cloe-trend-value"><?php echo esc_html(number_format($trend['score'], 1)); ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
            
            <?php if (!empty($trends['correlated']) && is_array($trends['correlated'])) : ?>
                <h3><?php _e('Emerging Opportunities', 'vortex-marketplace'); ?></h3>
                
                <ul class="vortex-cloe-opportunities">
                    <?php foreach (array_slice($trends['correlated'], 0, 3) as $opportunity) : ?>
                        <li>
                            <div class="vortex-cloe-opportunity-name"><?php echo esc_html($opportunity['name']); ?></div>
                            <div class="vortex-cloe-opportunity-description"><?php echo esc_html($opportunity['description']); ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            
            <div class="vortex-cloe-footer">
                <span class="vortex-cloe-updated">
                    <?php 
                    if (!empty($trends['last_updated'])) {
                        printf(
                            __('Last updated: %s', 'vortex-marketplace'),
                            date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $trends['last_updated'])
                        );
                    }
                    ?>
                </span>
                <a href="<?php echo admin_url('admin.php?page=vortex-cloe-intelligence&tab=trends'); ?>" class="button">
                    <?php _e('View All Trends', 'vortex-marketplace'); ?>
                </a>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render the user insights widget
     */
    public function render_user_insights_widget() {
        // Get user insights
        $insights = $this->get_user_insights();
        
        ?>
        <div class="vortex-cloe-widget">
            <h3><?php _e('User Activity', 'vortex-marketplace'); ?></h3>
            
            <?php if (empty($insights)) : ?>
                <p><?php _e('No user insight data available at this time.', 'vortex-marketplace'); ?></p>
            <?php else : ?>
                <div class="vortex-cloe-insights">
                    <?php if (!empty($insights['active_users'])) : ?>
                        <div class="vortex-cloe-insight-item">
                            <span class="vortex-cloe-insight-value"><?php echo esc_html($insights['active_users']['count']); ?></span>
                            <span class="vortex-cloe-insight-label"><?php _e('Active Users', 'vortex-marketplace'); ?></span>
                            <span class="vortex-cloe-insight-change <?php echo $insights['active_users']['change'] >= 0 ? 'positive' : 'negative'; ?>">
                                <?php echo $insights['active_users']['change'] >= 0 ? '+' : ''; ?><?php echo esc_html($insights['active_users']['change']); ?>%
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($insights['new_users'])) : ?>
                        <div class="vortex-cloe-insight-item">
                            <span class="vortex-cloe-insight-value"><?php echo esc_html($insights['new_users']['count']); ?></span>
                            <span class="vortex-cloe-insight-label"><?php _e('New Users', 'vortex-marketplace'); ?></span>
                            <span class="vortex-cloe-insight-change <?php echo $insights['new_users']['change'] >= 0 ? 'positive' : 'negative'; ?>">
                                <?php echo $insights['new_users']['change'] >= 0 ? '+' : ''; ?><?php echo esc_html($insights['new_users']['change']); ?>%
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($insights['engagement_rate'])) : ?>
                        <div class="vortex-cloe-insight-item">
                            <span class="vortex-cloe-insight-value"><?php echo esc_html($insights['engagement_rate']['value']); ?>%</span>
                            <span class="vortex-cloe-insight-label"><?php _e('Engagement Rate', 'vortex-marketplace'); ?></span>
                            <span class="vortex-cloe-insight-change <?php echo $insights['engagement_rate']['change'] >= 0 ? 'positive' : 'negative'; ?>">
                                <?php echo $insights['engagement_rate']['change'] >= 0 ? '+' : ''; ?><?php echo esc_html($insights['engagement_rate']['change']); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($insights['user_segments']) && is_array($insights['user_segments'])) : ?>
                    <h3><?php _e('User Segments', 'vortex-marketplace'); ?></h3>
                    
                    <div class="vortex-cloe-segments">
                        <?php foreach ($insights['user_segments'] as $segment) : ?>
                            <div class="vortex-cloe-segment-item">
                                <div class="vortex-cloe-segment-name"><?php echo esc_html($segment['name']); ?></div>
                                <div class="vortex-cloe-segment-value"><?php echo esc_html($segment['percentage']); ?>%</div>
                                <div class="vortex-cloe-segment-bar">
                                    <div class="vortex-cloe-segment-fill" style="width: <?php echo esc_attr($segment['percentage']); ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <div class="vortex-cloe-footer">
                <a href="<?php echo admin_url('admin.php?page=vortex-cloe-intelligence&tab=users'); ?>" class="button">
                    <?php _e('View All User Insights', 'vortex-marketplace'); ?>
                </a>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get total artwork count
     * 
     * @return int Artwork count
     */
    private function get_total_artwork_count() {
        // Check if we have a cached value
        $count = get_transient('vortex_cloe_artwork_count');
        
        if ($count === false) {
            global $wpdb;
            
            // Check if the artwork post type exists
            if (post_type_exists('vortex_artwork')) {
                $count = wp_count_posts('vortex_artwork');
                $count = $count->publish;
            } else {
                // Fallback to checking the artwork table if it exists
                $table_name = $wpdb->prefix . 'vortex_artworks';
                if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
                    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'published'");
                } else {
                    $count = 0;
                }
            }
            
            // Cache the result for 1 hour
            set_transient('vortex_cloe_artwork_count', $count, HOUR_IN_SECONDS);
        }
        
        return intval($count);
    }
    
    /**
     * Get total user count
     * 
     * @return int User count
     */
    private function get_total_user_count() {
        // Check if we have a cached value
        $count = get_transient('vortex_cloe_user_count');
        
        if ($count === false) {
            global $wpdb;
            
            // Check if the vortex_users table exists
            $table_name = $wpdb->prefix . 'vortex_users';
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
                $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            } else {
                // Fallback to counting WordPress users
                $count = count_users();
                $count = $count['total_users'];
            }
            
            // Cache the result for 1 hour
            set_transient('vortex_cloe_user_count', $count, HOUR_IN_SECONDS);
        }
        
        return intval($count);
    }
    
    /**
     * Get total transaction count
     * 
     * @return int Transaction count
     */
    private function get_total_transaction_count() {
        // Check if we have a cached value
        $count = get_transient('vortex_cloe_transaction_count');
        
        if ($count === false) {
            global $wpdb;
            
            // Check if the transactions table exists
            $table_name = $wpdb->prefix . 'vortex_transactions';
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
                $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'completed'");
            } else {
                // Fallback to checking for transaction post type
                if (post_type_exists('vortex_transaction')) {
                    $count = wp_count_posts('vortex_transaction');
                    $count = $count->publish;
                } else {
                    $count = 0;
                }
            }
            
            // Cache the result for 1 hour
            set_transient('vortex_cloe_transaction_count', $count, HOUR_IN_SECONDS);
        }
        
        return intval($count);
    }
    
    /**
     * Get admin recommendations
     * 
     * @param int $limit Number of recommendations to return
     * @return array Recommendations
     */
    private function get_admin_recommendations($limit = 5) {
        // This would be replaced with actual recommendation logic based on CLOE's analysis
        // For now, return some sample recommendations
        $recommendations = array(
            array(
                'type' => __('Content Gap', 'vortex-marketplace'),
                'content' => __('Low inventory of abstract art. Consider adding more items in this category.', 'vortex-marketplace'),
                'action_url' => admin_url('post-new.php?post_type=vortex_artwork'),
                'action_text' => __('Add Artwork', 'vortex-marketplace')
            ),
            array(
                'type' => __('Price Optimization', 'vortex-marketplace'),
                'content' => __('Digital art is underpriced by 15% compared to market average.', 'vortex-marketplace'),
                'action_url' => admin_url('edit.php?post_type=vortex_artwork&category=digital-art'),
                'action_text' => __('View Digital Art', 'vortex-marketplace')
            ),
            array(
                'type' => __('User Engagement', 'vortex-marketplace'),
                'content' => __('20% increase in photography art views. Consider featuring more photography.', 'vortex-marketplace'),
                'action_url' => admin_url('admin.php?page=vortex-marketplace-settings&tab=featured'),
                'action_text' => __('Update Features', 'vortex-marketplace')
            ),
            array(
                'type' => __('Marketing', 'vortex-marketplace'),
                'content' => __('Twitter shares are generating 45% more traffic than other platforms.', 'vortex-marketplace'),
                'action_url' => admin_url('admin.php?page=vortex-marketplace-settings&tab=social'),
                'action_text' => __('Social Settings', 'vortex-marketplace')
            ),
            array(
                'type' => __('New Trend', 'vortex-marketplace'),
                'content' => __('AI-generated landscapes are trending. Consider showcasing this category.', 'vortex-marketplace'),
                'action_url' => admin_url('term.php?taxonomy=vortex-artwork-category'),
                'action_text' => __('Update Categories', 'vortex-marketplace')
            )
        );
        
        // Return the limited number of recommendations
        return array_slice($recommendations, 0, $limit);
    }
    
    /**
     * Get user insights
     * 
     * @return array User insights
     */
    private function get_user_insights() {
        // This would be replaced with actual insight generation based on CLOE's analysis
        // For now, return some sample insights
        $insights = array(
            'active_users' => array(
                'count' => rand(100, 1000),
                'change' => rand(-5, 15)
            ),
            'new_users' => array(
                'count' => rand(10, 100),
                'change' => rand(-5, 20)
            ),
            'engagement_rate' => array(
                'value' => rand(30, 75),
                'change' => rand(-3, 8)
            ),
            'user_segments' => array(
                array(
                    'name' => __('Artists', 'vortex-marketplace'),
                    'percentage' => rand(20, 40)
                ),
                array(
                    'name' => __('Collectors', 'vortex-marketplace'),
                    'percentage' => rand(40, 60)
                ),
                array(
                    'name' => __('Galleries', 'vortex-marketplace'),
                    'percentage' => rand(5, 15)
                ),
                array(
                    'name' => __('Others', 'vortex-marketplace'),
                    'percentage' => rand(5, 15)
                )
            )
        );
        
        return $insights;
    }
    
    /**
     * Check if the CLOE agent is active and functioning
     *
     * @since 1.0.0
     * @return bool Whether the agent is active
     */
    public function is_active() {
        // Check if learning models are initialized
        if (empty($this->learning_models)) {
            return false;
        }
        
        // Check if tracking categories are initialized
        if (empty($this->tracking_categories)) {
            return false;
        }
        
        // Perform a basic health check
        try {
            // Check if at least one model file exists
            $model_exists = false;
            foreach ($this->learning_models as $model) {
                if (file_exists($model['path'])) {
                    $model_exists = true;
                    break;
                }
            }
            
            if (!$model_exists) {
                return false;
            }
            
            // Check if we can write to model files (needed for learning)
            $test_model = reset($this->learning_models);
            $model_dir = dirname($test_model['path']);
            $is_writable = is_writable($model_dir);
            
            return $is_writable;
        } catch (Exception $e) {
            error_log('CLOE health check failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Record user event
     *
     * @param int $user_id User ID
     * @param string $event_type Event type
     * @param array $event_data Event data
     * @return int|false The ID of the inserted record, or false on failure
     */
    public function record_user_event($user_id, $event_type, $event_data = array()) {
        // Check if VORTEX_User_Events class exists
        if (!class_exists('VORTEX_User_Events')) {
            require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-user-events.php';
        }
        
        // Get user events instance
        $user_events = VORTEX_User_Events::get_instance();
        
        // Record event
        $event_id = $user_events->record_event($user_id, $event_type, $event_data);
        
        // Also send to AI learning system if event was recorded successfully
        if ($event_id) {
            do_action('vortex_ai_agent_learn', 'CLOE', $event_type, array(
                'user_id' => $user_id,
                'event_data' => $event_data,
                'timestamp' => time()
            ));
        }
        
        return $event_id;
    }

    /**
     * Check if a database table exists
     * 
     * @param string $table_name Full table name including prefix
     * @return bool True if table exists, false otherwise
     */
    private function table_exists($table_name) {
        global $wpdb;
        return $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    }
    
    /**
     * Repair method hooks if they've been unregistered or corrupted
     * 
     * This can be called from admin actions to fix missing hooks
     * 
     * @return bool True if repairs were made
     */
    public function repair_method_hooks() {
        $repairs_made = false;
        
        // Check and repair wp_logout hook
        if (!has_action('wp_logout', array($this, 'end_session_tracking'))) {
            add_action('wp_logout', array($this, 'end_session_tracking'), 10);
            $repairs_made = true;
        }
        
        // Check and repair init hook
        if (!has_action('init', array($this, 'continue_session_tracking'))) {
            add_action('init', array($this, 'continue_session_tracking'), 10);
            $repairs_made = true;
        }
        
        // Check and repair wp_dashboard_setup hook
        if (!has_action('wp_dashboard_setup', array($this, 'add_dashboard_widgets'))) {
            add_action('wp_dashboard_setup', array($this, 'add_dashboard_widgets'), 10);
            $repairs_made = true;
        }
        
        if ($repairs_made) {
            // Log the repair
            error_log('VORTEX_CLOE: Method hooks repaired at ' . current_time('mysql'));
        }
        
        return $repairs_made;
    }

    private function get_trending_search_terms($period = 'month') {
        try {
            global $wpdb;
            
            $current_period = $this->get_time_constraint($period);
            $previous_period = $this->get_time_constraint($period, true);
            
            // Get trending search terms (highest growth in searches)
            $query = $wpdb->prepare(
                "SELECT 
                    search_term,
                    COUNT(CASE WHEN search_time >= %s THEN 1 ELSE NULL END) as current_period_searches,
                    COUNT(CASE WHEN search_time >= %s AND search_time < %s THEN 1 ELSE NULL END) as previous_period_searches
                FROM {$wpdb->prefix}vortex_searches
                WHERE search_time >= %s
                GROUP BY search_term
                HAVING current_period_searches > 5 AND previous_period_searches > 0
                ORDER BY (current_period_searches - previous_period_searches) DESC
                LIMIT 30",
                $current_period,
                $previous_period,
                $current_period,
                $previous_period
            );
            
            $trending_terms = $wpdb->get_results($query);
            
            // Calculate growth rates and add to results
            $processed_terms = array();
            foreach ($trending_terms as $term) {
                $growth_rate = 0;
                if ($term->previous_period_searches > 0) {
                    $growth_rate = round((($term->current_period_searches - $term->previous_period_searches) / $term->previous_period_searches) * 100, 2);
                }
                
                $processed_terms[] = array(
                    'term' => $term->search_term,
                    'current_searches' => $term->current_period_searches,
                    'previous_searches' => $term->previous_period_searches,
                    'growth_rate' => $growth_rate
                );
            }
            
            // Find new trending terms (not present in previous period)
            $new_query = $wpdb->prepare(
                "SELECT 
                    s1.search_term,
                    COUNT(*) as search_count
                FROM {$wpdb->prefix}vortex_searches s1
                WHERE s1.search_time >= %s
                AND NOT EXISTS (
                    SELECT 1 FROM {$wpdb->prefix}vortex_searches s2
                    WHERE s2.search_term = s1.search_term
                    AND s2.search_time >= %s AND s2.search_time < %s
                )
                GROUP BY s1.search_term
                HAVING search_count > 3
                ORDER BY search_count DESC
                LIMIT 20",
                $current_period,
                $previous_period,
                $current_period
            );
            
            $new_trending_terms = $wpdb->get_results($new_query);
            
            // Get trending terms by category
            $category_query = $wpdb->prepare(
                "SELECT 
                    c.category_id,
                    c.category_name,
                    s.search_term,
                    COUNT(*) as search_count
                FROM {$wpdb->prefix}vortex_searches s
                JOIN {$wpdb->prefix}vortex_search_artwork_clicks sac ON s.search_id = sac.search_id
                JOIN {$wpdb->prefix}vortex_artworks a ON sac.artwork_id = a.artwork_id
                JOIN {$wpdb->prefix}vortex_categories c ON a.category_id = c.category_id
                WHERE s.search_time >= %s
                GROUP BY c.category_id, s.search_term
                ORDER BY c.category_name, search_count DESC",
                $current_period
            );
            
            $category_trends = $wpdb->get_results($category_query);
            
            // Process category-specific trending terms
            $trending_by_category = array();
            $current_category = null;
            $category_terms = array();
            
            foreach ($category_trends as $trend) {
                if ($current_category !== $trend->category_id) {
                    // Save previous category terms if they exist
                    if ($current_category !== null && !empty($category_terms)) {
                        $trending_by_category[] = array(
                            'category_id' => $current_category,
                            'category_name' => $category_name,
                            'terms' => $category_terms
                        );
                    }
                    
                    // Start new category
                    $current_category = $trend->category_id;
                    $category_name = $trend->category_name;
                    $category_terms = array();
                }
                
                // Add term to current category (limit to top 5 per category)
                if (count($category_terms) < 5) {
                    $category_terms[] = array(
                        'term' => $trend->search_term,
                        'search_count' => $trend->search_count
                    );
                }
            }
            
            // Add the last category if it exists
            if ($current_category !== null && !empty($category_terms)) {
                $trending_by_category[] = array(
                    'category_id' => $current_category,
                    'category_name' => $category_name,
                    'terms' => $category_terms
                );
            }
            
            $results = array(
                'trending_terms' => $processed_terms,
                'new_trending_terms' => $new_trending_terms,
                'trending_by_category' => $trending_by_category
            );
            
            // Allow filtering of the results through the vortex_trending_search_terms filter
            return apply_filters('vortex_trending_search_terms', $results, $period);
            
        } catch (Exception $e) {
            $this->log_error('Failed to get trending search terms: ' . $e->getMessage());
            return array(
                'trending_terms' => array(),
                'new_trending_terms' => array(),
                'trending_by_category' => array()
            );
        }
    }
} 