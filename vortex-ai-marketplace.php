<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://vortexartec.com
 * @since             1.0.0
 * @package           Vortex_AI_Marketplace
 *
 * @wordpress-plugin
 * Plugin Name:       VORTEX AI Marketplace
 * Plugin URI:        https://vortexartec.com/marketplace
 * Description:       A blockchain-powered art marketplace with integrated AI art generation tools and TOLA token functionality.
 * Version:           1.0.0
 * Author:            Marianne Nems
 * Author URI:        https://vortexartec.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       vortex-ai-marketplace
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 */
define( 'VORTEX_VERSION', '1.0.0' );

/**
 * Define plugin directories and URLs.
 */
define( 'VORTEX_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'VORTEX_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'VORTEX_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-vortex-activator.php
 */
function activate_vortex_ai_marketplace() {
    require_once VORTEX_PLUGIN_DIR . 'includes/class-vortex-activator.php';
    Vortex_Activator::activate();
    
    // Initialize database tables
    require_once VORTEX_PLUGIN_DIR . 'includes/class-vortex-db-tables.php';
    $db_tables = VORTEX_DB_Tables::get_instance();
    $db_tables->create_all_tables();
    
    // Explicitly create the cart abandonment feedback table to fix common issues
    $db_tables->create_cart_abandonment_feedback_table();
    
    // Run database migrations
    require_once VORTEX_PLUGIN_DIR . 'includes/class-vortex-db-migrations.php';
    Vortex_DB_Migrations::migrate_to_current_version();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-vortex-deactivator.php
 */
function deactivate_vortex_ai_marketplace() {
    require_once VORTEX_PLUGIN_DIR . 'includes/class-vortex-deactivator.php';
    Vortex_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_vortex_ai_marketplace' );
register_deactivation_hook( __FILE__, 'deactivate_vortex_ai_marketplace' );

// Include the AI components first
require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-user-events.php';
require_once plugin_dir_path(__FILE__) . 'includes/traits/trait-vortex-cloe-analytics.php';
require_once plugin_dir_path(__FILE__) . 'class-vortex-cloe.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-analytics.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-huraii-library.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-db-repair.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-cloe-fixes.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-cloe-search-stats.php';
require_once plugin_dir_path(__FILE__) . 'includes/sql-fixes/fix-search-terms-query.php';
require_once plugin_dir_path(__FILE__) . 'includes/sql-fixes/fix-current-purchases-reference.php';
require_once plugin_dir_path(__FILE__) . 'includes/sql-fixes/fix-share-rate-reference.php';

// Make sure CLOE is initialized during plugins_loaded 
function vortex_initialize_cloe() {
    $cloe = VORTEX_CLOE::get_instance();
    
    // Explicitly re-register the session tracking hooks if needed
    if (has_action('wp_logout') && !has_action('wp_logout', array($cloe, 'end_session_tracking'))) {
        add_action('wp_logout', array($cloe, 'end_session_tracking'), 10);
    }
    
    if (has_action('init') && !has_action('init', array($cloe, 'continue_session_tracking'))) {
        add_action('init', array($cloe, 'continue_session_tracking'), 10);
    }
}
add_action('plugins_loaded', 'vortex_initialize_cloe', 5); // Run this early with priority 5

// Initialize the database repair functionality
function vortex_initialize_db_repair() {
    // Get instance to initialize hooks
    VORTEX_DB_Repair::get_instance();
    
    // Run database migrations
    if (class_exists('Vortex_DB_Migrations')) {
        Vortex_DB_Migrations::migrate_to_current_version();
    }
}
add_action('plugins_loaded', 'vortex_initialize_db_repair', 5);

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require VORTEX_PLUGIN_DIR . 'includes/class-vortex-ai-marketplace.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'class-vortex-support-chat.php';
require_once VORTEX_PLUGIN_DIR . 'includes/class-vortex-subscriptions.php';
require_once VORTEX_PLUGIN_DIR . 'includes/class-vortex-ai-loader.php';
require_once VORTEX_PLUGIN_DIR . 'includes/class-vortex-db-migrations.php';

// Register DB migration to run during activation
add_action('vortex_ai_activate', array('Vortex_DB_Migrations', 'setup_database'));

// Register HURAII Widgets
require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-huraii-widgets.php';
add_action('init', array('Vortex_HURAII_Widgets', 'init'));

// Include the scheduler files
require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-scheduler-db.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-scheduler.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-scheduler-shortcodes.php';

// Include career, project, and collaboration functionality
require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-career-project-collaboration.php';

// Include post types
require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-post-types.php';

// Include AJAX handlers
require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-ajax-handlers.php';

/**
 * Initialize the plugin.
 */
function run_vortex_ai_marketplace() {
    // Initialize post types
    add_action('init', array('Vortex_Post_Types', 'init'));
    
    // Initialize Career, Project, and Collaboration features
    add_action('init', array('Vortex_Career_Project_Collaboration', 'init'));
    
    // Initialize User Events Tracking
    $user_events = VORTEX_User_Events::get_instance();
    
    // Initialize CLOE AI Agent
    $cloe = VORTEX_CLOE::get_instance();
    
    $plugin = new Vortex_AI_Marketplace();
    $plugin->run();
    
    // Initialize Thorius
    $thorius = Vortex_Thorius::get_instance();
    $thorius->initialize();
    $thorius->init_widget(); // Initialize widget
}

// Initialize the scheduler
$scheduler = new Vortex_Scheduler($plugin_name, $version, $this);

run_vortex_ai_marketplace();

// Initialize AJAX handlers
add_action('init', array('Vortex_AJAX_Handlers', 'init'));

// Enqueue HURAII Widgets scripts and styles
function vortex_enqueue_huraii_widgets_assets() {
    wp_enqueue_style(
        'vortex-huraii-widgets',
        plugin_dir_url(__FILE__) . 'css/vortex-huraii-widgets.css',
        array(),
        VORTEX_VERSION
    );

    wp_enqueue_script(
        'vortex-huraii-widgets',
        plugin_dir_url(__FILE__) . 'js/vortex-huraii-widgets.js',
        array('jquery'),
        VORTEX_VERSION,
        true
    );

    wp_localize_script('vortex-huraii-widgets', 'vortexHuraii', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('vortex_huraii_nonce'),
        'i18n' => array(
            'error' => __('An error occurred. Please try again.', 'vortex'),
            'invalidFileType' => __('Please upload a valid image file.', 'vortex'),
            'generating' => __('Generating artwork...', 'vortex'),
            'applying' => __('Applying style transfer...', 'vortex'),
            'analyzing' => __('Analyzing artwork...', 'vortex')
        )
    ));
}
add_action('wp_enqueue_scripts', 'vortex_enqueue_huraii_widgets_assets');

// Enqueue subscription assets
function vortex_enqueue_subscription_assets() {
    wp_enqueue_style(
        'vortex-subscriptions',
        VORTEX_PLUGIN_URL . 'css/vortex-subscriptions.css',
        array(),
        VORTEX_VERSION
    );
    
    wp_enqueue_script(
        'vortex-subscriptions',
        VORTEX_PLUGIN_URL . 'js/vortex-subscriptions.js',
        array('jquery'),
        VORTEX_VERSION,
        true
    );
    
    wp_localize_script('vortex-subscriptions', 'vortexData', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('vortex_subscription_nonce'),
        'isLoggedIn' => is_user_logged_in(),
        'loginUrl' => wp_login_url(get_permalink()),
        'dashboardUrl' => home_url('/dashboard'),
        'i18n' => array(
            'generalError' => __('An error occurred. Please try again.', 'vortex'),
            'insufficientBalance' => __('Insufficient TOLA balance. Please add more tokens to your wallet.', 'vortex'),
            'subscriptionSuccess' => __('Subscription activated successfully!', 'vortex')
        )
    ));
}
add_action('wp_enqueue_scripts', 'vortex_enqueue_subscription_assets');

// Register subscription shortcodes
function vortex_register_subscription_shortcodes() {
    add_shortcode('vortex_artist_plans', 'vortex_artist_plans_shortcode');
    add_shortcode('vortex_collector_plans', 'vortex_collector_plans_shortcode');
}
add_action('init', 'vortex_register_subscription_shortcodes');

function vortex_artist_plans_shortcode($atts) {
    ob_start();
    include VORTEX_PLUGIN_DIR . 'public/partials/vortex-artist-plans.php';
    return ob_get_clean();
}

function vortex_collector_plans_shortcode($atts) {
    ob_start();
    include VORTEX_PLUGIN_DIR . 'public/partials/vortex-collector-plans.php';
    return ob_get_clean();
}

// AJAX handlers for subscription processing
function vortex_check_tola_balance() {
    check_ajax_referer('vortex_subscription_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('Please log in to continue.', 'vortex')));
    }
    
    $plan_type = sanitize_text_field($_POST['plan_type']);
    $plan_tier = sanitize_text_field($_POST['plan_tier']);
    
    $subscriptions = VORTEX_Subscriptions::get_instance();
    $plan_price = $subscriptions->get_plan_price($plan_type, $plan_tier);
    
    // Check user's TOLA balance
    $user_balance = vortex_get_user_tola_balance(get_current_user_id());
    
    if ($user_balance >= $plan_price) {
        wp_send_json_success();
    } else {
        wp_send_json_error(array('message' => __('Insufficient TOLA balance. Please add more tokens to your wallet.', 'vortex')));
    }
}
add_action('wp_ajax_vortex_check_tola_balance', 'vortex_check_tola_balance');

function vortex_process_subscription() {
    check_ajax_referer('vortex_subscription_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('Please log in to continue.', 'vortex')));
    }
    
    $plan_type = sanitize_text_field($_POST['plan_type']);
    $plan_tier = sanitize_text_field($_POST['plan_tier']);
    
    $subscriptions = VORTEX_Subscriptions::get_instance();
    $result = $subscriptions->process_subscription(get_current_user_id(), $plan_type, $plan_tier);
    
    if ($result['success']) {
        wp_send_json_success(array('message' => __('Subscription activated successfully!', 'vortex')));
    } else {
        wp_send_json_error(array('message' => $result['message']));
    }
}
add_action('wp_ajax_vortex_process_subscription', 'vortex_process_subscription');

// Enqueue registration scripts and styles
function vortex_enqueue_registration_assets() {
    wp_enqueue_style(
        'vortex-modal',
        VORTEX_PLUGIN_URL . 'css/vortex-modal.css',
        array(),
        VORTEX_VERSION
    );
    
    wp_enqueue_script(
        'vortex-registration',
        VORTEX_PLUGIN_URL . 'js/vortex-registration.js',
        array('jquery'),
        VORTEX_VERSION,
        true
    );
    
    wp_localize_script('vortex-registration', 'vortexData', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('vortex_registration_nonce'),
        'dashboardUrl' => home_url('/dashboard'),
        'i18n' => array(
            'businessQuizTitle' => __('Tell Us About Your Business Goals', 'vortex'),
            'generalError' => __('An error occurred. Please try again.', 'vortex')
        )
    ));
}
add_action('wp_enqueue_scripts', 'vortex_enqueue_registration_assets');

/**
 * Enqueue scripts and styles for the business quiz.
 */
function vortex_enqueue_business_quiz_assets() {
    wp_enqueue_style(
        'vortex-modal',
        plugin_dir_url(__FILE__) . 'css/vortex-modal.css',
        array(),
        VORTEX_VERSION
    );

    wp_enqueue_script(
        'vortex-registration',
        plugin_dir_url(__FILE__) . 'js/vortex-registration.js',
        array('jquery'),
        VORTEX_VERSION,
        true
    );

    wp_localize_script('vortex-registration', 'vortex_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('vortex_ajax_nonce'),
        'i18n' => array(
            'quiz_title' => __('Business Strategy Quiz', 'vortex-ai-marketplace'),
            'error_message' => __('An error occurred. Please try again.', 'vortex-ai-marketplace'),
            'success_message' => __('Your business plan is being generated...', 'vortex-ai-marketplace')
        )
    ));
}
add_action('wp_enqueue_scripts', 'vortex_enqueue_business_quiz_assets');

/**
 * AJAX handler for loading the business quiz.
 */
function vortex_load_business_quiz() {
    check_ajax_referer('vortex_ajax_nonce', 'nonce');

    ob_start();
    include plugin_dir_path(__FILE__) . 'public/partials/vortex-business-quiz-widget.php';
    $html = ob_get_clean();

    wp_send_json_success(array(
        'html' => $html
    ));
}
add_action('wp_ajax_vortex_load_business_quiz', 'vortex_load_business_quiz');
add_action('wp_ajax_nopriv_vortex_load_business_quiz', 'vortex_load_business_quiz');

/**
 * AJAX handler for processing the business quiz submission.
 */
function vortex_process_business_quiz() {
    check_ajax_referer('vortex_ajax_nonce', 'nonce');

    if (!is_user_logged_in()) {
        wp_send_json_error(array(
            'message' => __('You must be logged in to submit the quiz.', 'vortex-ai-marketplace')
        ));
    }

    $required_fields = array(
        'primary_goal',
        'experience_level',
        'art_focus',
        'investment_level',
        'time_commitment'
    );

    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            wp_send_json_error(array(
                'message' => __('Please fill in all required fields.', 'vortex-ai-marketplace')
            ));
        }
    }

    $user_id = get_current_user_id();
    $answers = array(
        'primary_goal' => sanitize_text_field($_POST['primary_goal']),
        'experience_level' => sanitize_text_field($_POST['experience_level']),
        'art_focus' => sanitize_text_field($_POST['art_focus']),
        'investment_level' => sanitize_text_field($_POST['investment_level']),
        'time_commitment' => sanitize_text_field($_POST['time_commitment'])
    );

    // Process quiz answers using the Business Strategist
    $business_strategist = new VORTEX_Business_Strategist();
    $result = $business_strategist->process_quiz_submission($user_id, $answers);

    if (is_wp_error($result)) {
        wp_send_json_error(array(
            'message' => $result->get_error_message()
        ));
    }

    wp_send_json_success(array(
        'message' => __('Your business plan has been generated successfully!', 'vortex-ai-marketplace'),
        'redirect_url' => get_permalink(get_option('vortex_dashboard_page'))
    ));
}
add_action('wp_ajax_vortex_process_business_quiz', 'vortex_process_business_quiz');

// Rate limiting for AI requests
function vortex_setup_security_hooks() {
    $security = new Vortex_Security();
    
    // HURAII rate limiting
    add_action('wp_ajax_vortex_generate_artwork', array($security, 'check_huraii_rate_limit'), 5);
    add_action('wp_ajax_vortex_analyze_artwork', array($security, 'check_huraii_rate_limit'), 5);
    add_action('wp_ajax_vortex_style_transfer', array($security, 'check_huraii_rate_limit'), 5);
    
    // CLOE rate limiting
    add_action('wp_ajax_vortex_get_recommendations', array($security, 'check_cloe_rate_limit'), 5);
    add_action('wp_ajax_vortex_get_insights', array($security, 'check_cloe_rate_limit'), 5);
    
    // Business Strategist rate limiting
    add_action('wp_ajax_vortex_get_business_plan', array($security, 'check_business_strategist_rate_limit'), 5);
    add_action('wp_ajax_vortex_process_business_quiz', array($security, 'check_business_strategist_rate_limit'), 5);
}
add_action('init', 'vortex_setup_security_hooks');

/**
 * Load AI agents explicitly
 */
function vortex_load_ai_agents() {
    // Load base classes first
    require_once VORTEX_PLUGIN_DIR . 'includes/ai-agents/class-vortex-ai-agent-base.php';
    
    // Load specific AI agent implementations
    require_once VORTEX_PLUGIN_DIR . 'includes/ai-agents/class-vortex-business-strategist.php';
    
    // Initialize Business Strategist if needed
    if (!class_exists('VORTEX_Business_Strategist')) {
        throw new Exception('Failed to load the Business Strategist class');
    }
}

// Load AI agents early
add_action('plugins_loaded', 'vortex_load_ai_agents', 5);

// Enqueue AI Stats styles
function vortex_enqueue_ai_stats_styles() {
    wp_enqueue_style(
        'vortex-ai-stats', 
        plugin_dir_url(__FILE__) . 'css/vortex-ai-stats.css', 
        array(), 
        VORTEX_VERSION
    );
}
add_action('wp_enqueue_scripts', 'vortex_enqueue_ai_stats_styles');

// Add new shortcodes
function vortex_add_new_shortcodes() {
    add_shortcode('vortex_schedule_nft', 'vortex_schedule_nft_shortcode');
    add_shortcode('vortex_schedule_exhibition', 'vortex_schedule_exhibition_shortcode');
    add_shortcode('vortex_schedule_auction', 'vortex_schedule_auction_shortcode');
    add_shortcode('vortex_scheduled_events', 'vortex_scheduled_events_shortcode');
}
add_action('init', 'vortex_add_new_shortcodes');

function vortex_schedule_nft_shortcode($atts) {
    ob_start();
    include VORTEX_PLUGIN_DIR . 'public/partials/vortex-schedule-nft-form.php';
    return ob_get_clean();
}

function vortex_schedule_exhibition_shortcode($atts) {
    ob_start();
    include VORTEX_PLUGIN_DIR . 'public/partials/vortex-schedule-exhibition-form.php';
    return ob_get_clean();
}

function vortex_schedule_auction_shortcode($atts) {
    ob_start();
    include VORTEX_PLUGIN_DIR . 'public/partials/vortex-schedule-auction-form.php';
    return ob_get_clean();
}

function vortex_scheduled_events_shortcode($atts) {
    ob_start();
    include VORTEX_PLUGIN_DIR . 'public/partials/vortex-scheduled-events-list.php';
    return ob_get_clean();
}

// Enqueue collector workplace scripts and styles
function vortex_enqueue_collector_workplace_assets() {
    wp_enqueue_style(
        'vortex-marketplace',
        plugin_dir_url(__FILE__) . 'public/css/vortex-marketplace.css',
        array(),
        VORTEX_VERSION
    );
    
    wp_enqueue_script(
        'vortex-swipe',
        plugin_dir_url(__FILE__) . 'public/js/vortex-swipe.js',
        array('jquery'),
        VORTEX_VERSION,
        true
    );
    
    wp_localize_script('vortex-swipe', 'vortex_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('vortex_handle_swipe')
    ));
}
add_action('wp_enqueue_scripts', 'vortex_enqueue_collector_workplace_assets');

// Add collector workplace shortcodes
function vortex_add_collector_workplace_shortcodes() {
    add_shortcode('vortex_collector_workplace', array('Vortex_Shortcodes', 'render_collector_workplace'));
}
add_action('init', 'vortex_add_collector_workplace_shortcodes');

// Include modal template in the footer
function vortex_include_modal_template() {
    include plugin_dir_path(__FILE__) . 'public/partials/vortex-modal.php';
}
add_action('wp_footer', 'vortex_include_modal_template');

/**
 * AJAX handler for Thorius concierge queries
 */
function vortex_process_thorius_query() {
    check_ajax_referer('vortex_thorius_nonce', 'nonce');
    
    $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
    
    if (empty($query)) {
        wp_send_json_error(array('message' => __('Please enter a question or request.', 'vortex-ai-marketplace')));
        return;
    }
    
    // Process through Thorius
    require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-thorius.php';
    $thorius = Vortex_Thorius::get_instance();
    $result = $thorius->process_query($query);
    
    if (!$result['success']) {
        wp_send_json_error(array('message' => $result['message']));
        return;
    }
    
    wp_send_json_success($result);
}
add_action('wp_ajax_vortex_thorius_query', 'vortex_process_thorius_query');
add_action('wp_ajax_nopriv_vortex_thorius_query', 'vortex_process_thorius_query');

// Add this to enqueue the Thorius CSS
function vortex_enqueue_thorius_styles() {
    wp_enqueue_style(
        'vortex-thorius-css',
        plugin_dir_url(__FILE__) . 'public/css/vortex-thorius.css',
        array(),
        VORTEX_VERSION,
        'all'
    );
    
    // Add nonce for Thorius AJAX
    wp_localize_script('jquery', 'vortex_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'thorius_nonce' => wp_create_nonce('vortex_thorius_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'vortex_enqueue_thorius_styles');

/**
 * Security checks
 */
function vortex_security_checks() {
    // Check for direct file access
    if (!defined('WPINC')) {
        die;
    }
    
    // Check PHP version
    if (version_compare(PHP_VERSION, '7.2', '<')) {
        add_action('admin_notices', 'vortex_php_version_notice');
        return false;
    }
    
    // Check WordPress version
    if (version_compare($GLOBALS['wp_version'], '5.6', '<')) {
        add_action('admin_notices', 'vortex_wp_version_notice');
        return false;
    }
    
    return true;
}

/**
 * PHP version notice
 */
function vortex_php_version_notice() {
    echo '<div class="error"><p>' . sprintf(__('VORTEX AI Marketplace requires PHP version 7.2 or higher. You are running version %s.', 'vortex-ai-marketplace'), PHP_VERSION) . '</p></div>';
}

/**
 * WordPress version notice
 */
function vortex_wp_version_notice() {
    echo '<div class="error"><p>' . sprintf(__('VORTEX AI Marketplace requires WordPress version 5.6 or higher. You are running version %s.', 'vortex-ai-marketplace'), $GLOBALS['wp_version']) . '</p></div>';
}

// Run security checks
if (!vortex_security_checks()) {
    return;
}

// Set up error handling
function vortex_error_handler($errno, $errstr, $errfile, $errline) {
    // Log errors to file
    error_log(sprintf(
        '[VORTEX ERROR] %s in %s on line %d: %s',
        $errno,
        $errfile,
        $errline,
        $errstr
    ));
    
    // Don't handle errors further
    return false;
}
set_error_handler('vortex_error_handler', E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR);

// Load dependent classes
require plugin_dir_path(__FILE__) . 'includes/class-vortex-artist-verification.php';
require plugin_dir_path(__FILE__) . 'includes/class-vortex-artwork-verification.php';
require plugin_dir_path(__FILE__) . 'includes/class-vortex-blockchain-connection.php';
require plugin_dir_path(__FILE__) . 'includes/class-vortex-artwork-swap.php';
require plugin_dir_path(__FILE__) . 'includes/class-vortex-artwork-swap-shortcodes.php';

// Blockchain and TOLA Integration
require plugin_dir_path(__FILE__) . 'includes/class-vortex-tola.php';
require plugin_dir_path(__FILE__) . 'includes/class-vortex-blockchain-metrics.php'; // New metrics class

// Register DAO and Gamification systems
require_once VORTEX_PLUGIN_DIR . 'includes/class-vortex-dao-manager.php';
require_once VORTEX_PLUGIN_DIR . 'includes/class-vortex-gamification.php';

// Register marketplace shortcodes
require_once VORTEX_PLUGIN_DIR . 'includes/shortcodes/class-vortex-marketplace-shortcodes.php';
new VORTEX_Marketplace_Shortcodes();

// Initialize DAO and Gamification
add_action('plugins_loaded', 'vortex_init_dao_and_gamification');
function vortex_init_dao_and_gamification() {
    global $vortex_dao_manager, $vortex_gamification;
    
    $vortex_dao_manager = new VORTEX_DAO_Manager();
    $vortex_gamification = new VORTEX_Gamification();
}

// Install database tables for DAO and Gamification
register_activation_hook(__FILE__, 'vortex_install_dao_and_gamification_db');
function vortex_install_dao_and_gamification_db() {
    require_once VORTEX_PLUGIN_DIR . 'includes/class-vortex-dao-manager.php';
    require_once VORTEX_PLUGIN_DIR . 'includes/class-vortex-gamification.php';
    
    VORTEX_DAO_Manager::install();
    VORTEX_Gamification::install();
}

// Add this to handle user registration
function vortex_user_registered($user_id) {
    global $wpdb;
    
    $vortex_users_table = $wpdb->prefix . 'vortex_users';
    
    // Check if user already exists in vortex_users
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $vortex_users_table WHERE user_id = %d",
        $user_id
    ));
    
    if ($exists) {
        return; // User already exists in our table
    }
    
    // Get user data
    $user_data = get_userdata($user_id);
    
    // Determine user type based on role
    $user_type = 'collector'; // Default
    
    if (in_array('administrator', $user_data->roles)) {
        $user_type = 'admin';
    } elseif (in_array('vortex_artist', $user_data->roles) || in_array('author', $user_data->roles)) {
        $user_type = 'artist';
    }
    
    // Get avatar URL
    $avatar_url = get_avatar_url($user_id, array('size' => 200));
    
    // Insert user into vortex_users table
    $wpdb->insert(
        $vortex_users_table,
        array(
            'user_id' => $user_id,
            'display_name' => $user_data->display_name,
            'avatar_url' => $avatar_url,
            'user_type' => $user_type,
            'artist_verified' => ($user_type === 'artist' && $user_type !== 'admin') ? 0 : 1,
            'registration_date' => $user_data->user_registered,
            'last_updated' => current_time('mysql'),
            'onboarding_completed' => 0,
            'activity_score' => 0.00,
            'ranking_score' => 0.00,
            'is_featured' => 0,
            'tola_balance' => 50.00000000 // Give new users some initial TOLA tokens
        )
    );
}
add_action('user_register', 'vortex_user_registered');

// Add this to handle user profile updates
function vortex_user_profile_updated($user_id) {
    global $wpdb;
    
    $vortex_users_table = $wpdb->prefix . 'vortex_users';
    
    // Check if user exists in vortex_users
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $vortex_users_table WHERE user_id = %d",
        $user_id
    ));
    
    if (!$exists) {
        // If user doesn't exist in our table, create them
        vortex_user_registered($user_id);
        return;
    }
    
    // Get user data
    $user_data = get_userdata($user_id);
    
    // Determine user type based on role
    $user_type = 'collector'; // Default
    
    if (in_array('administrator', $user_data->roles)) {
        $user_type = 'admin';
    } elseif (in_array('vortex_artist', $user_data->roles) || in_array('author', $user_data->roles)) {
        $user_type = 'artist';
    }
    
    // Get avatar URL
    $avatar_url = get_avatar_url($user_id, array('size' => 200));
    
    // Update user in vortex_users table
    $wpdb->update(
        $vortex_users_table,
        array(
            'display_name' => $user_data->display_name,
            'avatar_url' => $avatar_url,
            'user_type' => $user_type,
            'last_updated' => current_time('mysql')
        ),
        array('user_id' => $user_id)
    );
}
add_action('profile_update', 'vortex_user_profile_updated');

// Handle social sharing functionality
function vortex_social_share() {
    check_ajax_referer('vortex_social_share_nonce', 'nonce');
    
    $artwork_id = isset($_POST['artwork_id']) ? intval($_POST['artwork_id']) : 0;
    $platform = isset($_POST['platform']) ? sanitize_text_field($_POST['platform']) : '';
    $share_message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
    
    if (empty($artwork_id) || empty($platform)) {
        wp_send_json_error(array('message' => __('Missing required parameters.', 'vortex-ai-marketplace')));
        return;
    }
    
    // Verify that the artwork exists
    $artwork = get_post($artwork_id);
    if (!$artwork || $artwork->post_type !== 'vortex_artwork') {
        wp_send_json_error(array('message' => __('Invalid artwork.', 'vortex-ai-marketplace')));
        return;
    }
    
    // Get user ID (can be null for non-logged in users)
    $user_id = is_user_logged_in() ? get_current_user_id() : null;
    
    // Get share URL based on platform
    $share_url = vortex_get_social_share_url($platform, $artwork_id, $share_message);
    
    // Log the share in the database
    global $wpdb;
    $result = $wpdb->insert(
        $wpdb->prefix . 'vortex_social_shares',
        array(
            'user_id' => $user_id,
            'artwork_id' => $artwork_id,
            'platform' => $platform,
            'share_url' => $share_url,
            'share_message' => $share_message,
            'share_date' => current_time('mysql'),
            'ip_address' => sanitize_text_field($_SERVER['REMOTE_ADDR']),
            'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT']),
            'share_status' => 'completed',
            'metadata' => json_encode(array(
                'referrer' => isset($_SERVER['HTTP_REFERER']) ? sanitize_text_field($_SERVER['HTTP_REFERER']) : '',
                'title' => get_the_title($artwork_id)
            ))
        )
    );
    
    if ($result === false) {
        wp_send_json_error(array('message' => __('Failed to log social share.', 'vortex-ai-marketplace')));
        return;
    }
    
    // Update artwork share count
    vortex_update_artwork_share_count($artwork_id);
    
    // Track the share for CLOE analytics
    if (class_exists('VORTEX_CLOE')) {
        $cloe = VORTEX_CLOE::get_instance();
        if (method_exists($cloe, 'track_artwork_share')) {
            $cloe->track_artwork_share($artwork_id, $user_id, $platform);
        }
    }
    
    wp_send_json_success(array(
        'url' => $share_url,
        'message' => __('Artwork shared successfully!', 'vortex-ai-marketplace')
    ));
}
add_action('wp_ajax_vortex_social_share', 'vortex_social_share');
add_action('wp_ajax_nopriv_vortex_social_share', 'vortex_social_share');

/**
 * Get the social share URL for a specific platform
 */
function vortex_get_social_share_url($platform, $artwork_id, $message = '') {
    $artwork_url = get_permalink($artwork_id);
    $artwork_title = get_the_title($artwork_id);
    $encoded_url = urlencode($artwork_url);
    $encoded_title = urlencode($artwork_title);
    $encoded_message = urlencode($message);
    
    switch ($platform) {
        case 'facebook':
            return 'https://www.facebook.com/sharer/sharer.php?u=' . $encoded_url;
        
        case 'twitter':
            return 'https://twitter.com/intent/tweet?url=' . $encoded_url . '&text=' . $encoded_message;
        
        case 'linkedin':
            return 'https://www.linkedin.com/sharing/share-offsite/?url=' . $encoded_url;
        
        case 'pinterest':
            $image_url = '';
            $thumbnail_id = get_post_thumbnail_id($artwork_id);
            if ($thumbnail_id) {
                $image_url = wp_get_attachment_image_url($thumbnail_id, 'full');
            }
            $encoded_image = urlencode($image_url);
            return 'https://pinterest.com/pin/create/button/?url=' . $encoded_url . '&media=' . $encoded_image . '&description=' . $encoded_title;
        
        case 'reddit':
            return 'https://www.reddit.com/submit?url=' . $encoded_url . '&title=' . $encoded_title;
        
        case 'whatsapp':
            return 'https://api.whatsapp.com/send?text=' . $encoded_title . ' ' . $encoded_url;
        
        case 'telegram':
            return 'https://t.me/share/url?url=' . $encoded_url . '&text=' . $encoded_title;
        
        case 'email':
            return 'mailto:?subject=' . $encoded_title . '&body=' . $encoded_message . ' ' . $encoded_url;
        
        default:
            return $artwork_url;
    }
}

/**
 * Update artwork share count
 */
function vortex_update_artwork_share_count($artwork_id) {
    global $wpdb;
    
    // Count total shares for this artwork
    $share_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_social_shares WHERE artwork_id = %d",
        $artwork_id
    ));
    
    // Update artwork meta
    update_post_meta($artwork_id, 'vortex_share_count', $share_count);
    
    // Update statistics table if it exists
    $stats_table = $wpdb->prefix . 'vortex_artwork_statistics';
    $stats_exists = $wpdb->get_var("SHOW TABLES LIKE '$stats_table'") === $stats_table;
    
    if ($stats_exists) {
        // Check if statistics entry exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $stats_table WHERE artwork_id = %d",
            $artwork_id
        ));
        
        if ($exists) {
            // Update existing statistics
            $wpdb->update(
                $stats_table,
                array('shares' => $share_count),
                array('artwork_id' => $artwork_id)
            );
        } else {
            // Insert new statistics
            $wpdb->insert(
                $stats_table,
                array(
                    'artwork_id' => $artwork_id,
                    'shares' => $share_count,
                    'last_updated' => current_time('mysql')
                )
            );
        }
    }
    
    return $share_count;
}

// Enqueue social sharing assets
function vortex_enqueue_social_sharing_assets() {
    wp_enqueue_style(
        'vortex-social-sharing',
        plugin_dir_url(__FILE__) . 'public/css/vortex-social-sharing.css',
        array(),
        VORTEX_VERSION
    );
    
    wp_enqueue_script(
        'vortex-social-sharing',
        plugin_dir_url(__FILE__) . 'public/js/vortex-social-sharing.js',
        array('jquery'),
        VORTEX_VERSION,
        true
    );
    
    wp_localize_script('vortex-social-sharing', 'vortex_social', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('vortex_social_share_nonce'),
        'i18n' => array(
            'share_success' => __('Thanks for sharing!', 'vortex-ai-marketplace'),
            'share_error' => __('An error occurred while sharing.', 'vortex-ai-marketplace'),
            'share_prompt' => __('Share this artwork', 'vortex-ai-marketplace')
        )
    ));
}
add_action('wp_enqueue_scripts', 'vortex_enqueue_social_sharing_assets');

// Register social sharing shortcode
function vortex_register_social_sharing_shortcode() {
    add_shortcode('vortex_social_share', 'vortex_social_share_shortcode');
}
add_action('init', 'vortex_register_social_sharing_shortcode');

/**
 * Social sharing shortcode
 * 
 * Usage: [vortex_social_share artwork_id="123" platforms="facebook,twitter,pinterest" message="Check out this artwork!"]
 * 
 * @param array $atts Shortcode attributes
 * @return string Shortcode output
 */
function vortex_social_share_shortcode($atts) {
    $atts = shortcode_atts(array(
        'artwork_id' => 0,
        'platforms' => 'facebook,twitter,pinterest,linkedin,reddit,whatsapp,telegram,email',
        'style' => 'buttons', // buttons or dropdown
        'message' => '',
        'show_count' => 'true',
        'label' => __('Share', 'vortex-ai-marketplace')
    ), $atts, 'vortex_social_share');
    
    // If no artwork ID is provided, try to get the current post ID
    if (empty($atts['artwork_id'])) {
        $atts['artwork_id'] = get_the_ID();
    }
    
    // Get artwork info
    $artwork_id = intval($atts['artwork_id']);
    $artwork = get_post($artwork_id);
    
    // If artwork doesn't exist, return empty
    if (!$artwork) {
        return '';
    }
    
    // Parse platforms
    $platforms = explode(',', $atts['platforms']);
    
    // Get share count
    global $wpdb;
    $share_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}vortex_social_shares WHERE artwork_id = %d",
        $artwork_id
    )) ?: 0;
    
    // Start output buffer
    ob_start();
    
    // Display share buttons
    if ($atts['style'] === 'dropdown') {
        // Dropdown style
        ?>
        <div class="vortex-social-share-container">
            <div class="vortex-social-share-dropdown-container">
                <div class="vortex-social-share-dropdown-toggle">
                    <i class="dashicons dashicons-share"></i> <?php echo esc_html($atts['label']); ?>
                </div>
                <div class="vortex-social-share-dropdown">
                    <?php foreach ($platforms as $platform) : 
                        $platform = trim($platform);
                        if (empty($platform)) continue;
                    ?>
                        <a href="#" class="vortex-social-share-button vortex-share-<?php echo esc_attr($platform); ?>" 
                           data-platform="<?php echo esc_attr($platform); ?>" 
                           data-artwork-id="<?php echo esc_attr($artwork_id); ?>"
                           data-message="<?php echo esc_attr($atts['message']); ?>">
                            <i class="dashicons dashicons-share"></i> <?php echo esc_html(ucfirst($platform)); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <?php if ($atts['show_count'] === 'true') : ?>
                <div class="vortex-share-count-container">
                    <span class="vortex-share-count-icon"><i class="dashicons dashicons-share-alt"></i></span>
                    <span class="vortex-share-count" data-artwork-id="<?php echo esc_attr($artwork_id); ?>"><?php echo esc_html($share_count); ?></span>
                </div>
            <?php endif; ?>
        </div>
        <?php
    } else {
        // Buttons style
        ?>
        <div class="vortex-social-share-container">
            <div class="vortex-social-share-buttons">
                <?php foreach ($platforms as $platform) : 
                    $platform = trim($platform);
                    if (empty($platform)) continue;
                ?>
                    <a href="#" class="vortex-social-share-button vortex-share-<?php echo esc_attr($platform); ?>" 
                       data-platform="<?php echo esc_attr($platform); ?>" 
                       data-artwork-id="<?php echo esc_attr($artwork_id); ?>"
                       data-message="<?php echo esc_attr($atts['message']); ?>">
                        <i class="dashicons dashicons-share"></i> <?php echo esc_html(ucfirst($platform)); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <?php if ($atts['show_count'] === 'true') : ?>
                <div class="vortex-share-count-container">
                    <span class="vortex-share-count-icon"><i class="dashicons dashicons-share-alt"></i></span>
                    <span class="vortex-share-count" data-artwork-id="<?php echo esc_attr($artwork_id); ?>"><?php echo esc_html($share_count); ?></span>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    // Return output buffer
    return ob_get_clean();
}

// Run on plugin activation to fix any missing hooks
function vortex_repair_cloe_hooks() {
    if (class_exists('VORTEX_CLOE')) {
        $cloe = VORTEX_CLOE::get_instance();
        if (method_exists($cloe, 'repair_method_hooks')) {
            $cloe->repair_method_hooks();
        }
    }
    
    // Also run database table repairs
    if (class_exists('VORTEX_DB_Tables')) {
        $db_tables = VORTEX_DB_Tables::get_instance();
        $db_tables->create_all_tables();
        
        // Specifically check for artworks table with style_id
        if (method_exists($db_tables, 'repair_artworks_table')) {
            $db_tables->repair_artworks_table();
        }
    }
}
register_activation_hook(__FILE__, 'vortex_repair_cloe_hooks');

// Check for missing database tables and display admin notice
function vortex_check_missing_tables() {
    // Only show to administrators
    if (!current_user_can('administrator')) {
        return;
    }
    
    global $wpdb;
    $missing_tables = array();
    
    // Required tables
    $required_tables = array(
        'vortex_user_sessions',
        'vortex_user_events',
        'vortex_artworks',
        'vortex_art_styles',
        'vortex_searches',
        'vortex_transactions',
        'vortex_tags',
        'vortex_artwork_tags'
    );
    
    // Check for each required table
    foreach ($required_tables as $table) {
        $table_name = $wpdb->prefix . $table;
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
            $missing_tables[] = $table;
        }
    }
    
    // Check if artworks table is missing style_id column
    $artworks_table = $wpdb->prefix . 'vortex_artworks';
    if ($wpdb->get_var("SHOW TABLES LIKE '$artworks_table'") === $artworks_table) {
        $has_style_id = false;
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $artworks_table");
        foreach ($columns as $column) {
            if ($column->Field === 'style_id') {
                $has_style_id = true;
                break;
            }
        }
        
        if (!$has_style_id) {
            // Add a notice about missing style_id column
            $missing_tables[] = 'style_id column in vortex_artworks';
        }
    }
    
    // Check if transactions table is missing artwork_id column
    $transactions_table = $wpdb->prefix . 'vortex_transactions';
    if ($wpdb->get_var("SHOW TABLES LIKE '$transactions_table'") === $transactions_table) {
        $has_artwork_id = false;
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $transactions_table");
        foreach ($columns as $column) {
            if ($column->Field === 'artwork_id') {
                $has_artwork_id = true;
                break;
            }
        }
        
        if (!$has_artwork_id) {
            // Add a notice about missing artwork_id column
            $missing_tables[] = 'artwork_id column in vortex_transactions';
        }
    }
    
    // If there are missing tables, show admin notice
    if (!empty($missing_tables)) {
        add_action('admin_notices', function() use ($missing_tables) {
            ?>
            <div class="notice notice-error">
                <p>
                    <strong><?php _e('VORTEX AI Marketplace Database Issue', 'vortex-ai-marketplace'); ?></strong>
                </p>
                <p>
                    <?php 
                    printf(
                        __('The following database tables or columns are missing: %s', 'vortex-ai-marketplace'), 
                        '<code>' . implode('</code>, <code>', $missing_tables) . '</code>'
                    ); 
                    ?>
                </p>
                <p>
                    <a href="<?php echo admin_url('tools.php?page=vortex-db-repair'); ?>" class="button button-primary">
                        <?php _e('Fix Database Tables', 'vortex-ai-marketplace'); ?>
                    </a>
                </p>
            </div>
            <?php
        });
    }
}
add_action('admin_init', 'vortex_check_missing_tables');

// Fix the searches table on plugin load
function vortex_fix_searches_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'vortex_searches';
    
    // Only run this fix if the table doesn't exist
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
        // Load the migrations class
        require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-db-migrations.php';
        
        // Fix the table
        if (class_exists('Vortex_DB_Migrations')) {
            Vortex_DB_Migrations::ensure_searches_table();
            error_log('Fixed missing searches table on plugin load.');
        }
    }
}

// Fix the artwork theme mapping table on plugin load
function vortex_fix_artwork_theme_mapping_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'vortex_artwork_theme_mapping';
    
    // Only run this fix if the table doesn't exist
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
        // Load the migrations class
        require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-db-migrations.php';
        
        // Fix the table
        if (class_exists('Vortex_DB_Migrations')) {
            // Ensure the artwork themes table exists first since it's a dependency
            Vortex_DB_Migrations::ensure_artwork_themes_table();
            // Then create the theme mapping table
            Vortex_DB_Migrations::ensure_artwork_theme_mapping_table();
            
            error_log('Fixed missing artwork theme mapping tables on plugin load.');
        }
    }
}

// Fix the social shares table click_count column on plugin load
function vortex_fix_social_shares_click_count() {
    // Load the migrations class
    require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-db-migrations.php';
    
    // Fix the click_count column
    if (class_exists('Vortex_DB_Migrations')) {
        Vortex_DB_Migrations::add_click_count_to_social_shares();
    }
}

// Fix the transactions table transaction_time column on plugin load
function vortex_fix_transaction_time() {
    // Load the migrations class
    require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-db-migrations.php';
    
    // Fix the transaction_time column
    if (class_exists('Vortex_DB_Migrations')) {
        Vortex_DB_Migrations::add_transaction_time_to_transactions();
    }
}

// Fix the cart_items table on plugin load
function vortex_fix_cart_items_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'vortex_cart_items';
    
    // Only run this fix if the table doesn't exist
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
        // Load the migrations class
        require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-db-migrations.php';
        
        // Fix the table
        if (class_exists('Vortex_DB_Migrations')) {
            Vortex_DB_Migrations::ensure_cart_items_table();
            error_log('Fixed missing cart_items table on plugin load.');
        }
    }
}

// Fix the cart abandonment feedback table on plugin load
function vortex_fix_cart_abandonment_feedback_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'vortex_cart_abandonment_feedback';
    
    // Check if the table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    // If table doesn't exist, create it
    if (!$table_exists) {
        // Load required classes
        require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-db-tables.php';
        
        // Create the table directly
        $db_tables = VORTEX_DB_Tables::get_instance();
        $db_tables->create_cart_abandonment_feedback_table();
        
        error_log('Created missing vortex_cart_abandonment_feedback table on plugin load.');
    }
    
    // Also ensure the abandonment_reason column exists
    require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-db-migrations.php';
    if (class_exists('Vortex_DB_Migrations')) {
        Vortex_DB_Migrations::ensure_cart_abandonment_reason_column();
    }
}

// Fix the search transactions table on plugin load
function vortex_fix_search_transactions_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'vortex_search_transactions';
    
    // Check if the table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    // If table doesn't exist, create it
    if (!$table_exists) {
        // Load required classes
        require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-db-tables.php';
        
        // Create the table directly
        $db_tables = VORTEX_DB_Tables::get_instance();
        $db_tables->create_search_transactions_table();
        
        error_log('Created missing vortex_search_transactions table on plugin load.');
    }
}

// Fix the search artwork clicks table on plugin load
function vortex_fix_search_artwork_clicks_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'vortex_search_artwork_clicks';
    
    // Check if the table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    // If table doesn't exist, create it
    if (!$table_exists) {
        // Load required classes
        require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-db-tables.php';
        
        // Create the table directly
        $db_tables = VORTEX_DB_Tables::get_instance();
        $db_tables->create_search_artwork_clicks_table();
        
        error_log('Created missing vortex_search_artwork_clicks table on plugin load.');
    }
}

// Function to force repair the cart abandonment table
// Can be called from admin functions or via AJAX if needed
function vortex_force_repair_cart_abandonment_table() {
    // Load required classes
    require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-db-tables.php';
    require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-db-repair.php';
    
    // Get DB repair instance
    $db_repair = VORTEX_DB_Repair::get_instance();
    
    // Force repair the table
    $success = $db_repair->repair_cart_abandonment_feedback_table();
    
    if ($success) {
        error_log('Successfully repaired vortex_cart_abandonment_feedback table.');
        return true;
    } else {
        error_log('Failed to repair vortex_cart_abandonment_feedback table.');
        return false;
    }
}

// Create an admin-only AJAX endpoint to force repair
function vortex_admin_force_repair_cart_abandonment_table() {
    // Security check
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized access.'));
        return;
    }
    
    $result = vortex_force_repair_cart_abandonment_table();
    
    if ($result) {
        wp_send_json_success(array('message' => 'Cart abandonment feedback table repaired successfully.'));
    } else {
        wp_send_json_error(array('message' => 'Failed to repair cart abandonment feedback table.'));
    }
}
add_action('wp_ajax_vortex_repair_cart_abandonment_table', 'vortex_admin_force_repair_cart_abandonment_table');

// Run this after plugins are loaded but before the rest of the plugin
add_action('plugins_loaded', 'vortex_fix_searches_table', 1); 
add_action('plugins_loaded', 'vortex_fix_artwork_theme_mapping_table', 1); 
add_action('plugins_loaded', 'vortex_fix_social_shares_click_count', 1); 
add_action('plugins_loaded', 'vortex_fix_transaction_time', 1); 
add_action('plugins_loaded', 'vortex_fix_cart_items_table', 1); 
add_action('plugins_loaded', 'vortex_fix_cart_abandonment_feedback_table', 1); 
add_action('plugins_loaded', 'vortex_fix_search_transactions_table', 1);
add_action('plugins_loaded', 'vortex_fix_search_artwork_clicks_table', 1); 
add_action('plugins_loaded', 'vortex_fix_search_results_table', 1); 
add_action('plugins_loaded', 'vortex_fix_social_hashtags_table', 1);

// Fix the search results table on plugin load
function vortex_fix_search_results_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'vortex_search_results';
    
    // Check if the table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    // If table doesn't exist, create it
    if (!$table_exists) {
        try {
            // Load required classes
            require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-db-tables.php';
            
            // Create the table directly
            $db_tables = VORTEX_DB_Tables::get_instance();
            $db_tables->create_search_results_table();
            
            // Verify that the table was created successfully
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            
            if ($table_exists) {
                error_log('Created missing vortex_search_results table on plugin load.');
            } else {
                // If the table still doesn't exist, try the repair method
                require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-db-repair.php';
                $db_repair = VORTEX_DB_Repair::get_instance();
                $success = $db_repair->repair_search_results_table();
                
                if ($success) {
                    error_log('Created vortex_search_results table using repair method.');
                } else {
                    // Try one more direct approach using the migrations class
                    require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-db-migrations.php';
                    Vortex_DB_Migrations::ensure_search_results_table();
                    
                    // Check if it worked
                    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
                    if ($table_exists) {
                        error_log('Created vortex_search_results table using migrations method.');
                    } else {
                        error_log('FAILED to create vortex_search_results table after multiple attempts.');
                    }
                }
            }
        } catch (Exception $e) {
            error_log('Exception when creating vortex_search_results table: ' . $e->getMessage());
        }
    }
}

// Function to force repair the search results table
// Can be called from admin functions or via AJAX if needed
function vortex_force_repair_search_results_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'vortex_search_results';
    
    // Track whether we successfully created the table
    $success = false;
    
    try {
        // Load required classes
        require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-db-tables.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-db-repair.php';
        
        // First, try using the repair method
        $db_repair = VORTEX_DB_Repair::get_instance();
        $success = $db_repair->repair_search_results_table();
        
        // Verify the table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            // If repair didn't work, try direct creation
            $db_tables = VORTEX_DB_Tables::get_instance();
            $db_tables->create_search_results_table();
            
            // Check again
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            
            if ($table_exists) {
                $success = true;
                error_log('Successfully created vortex_search_results table using direct creation.');
            } else {
                // Last resort, try the migrations method
                require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-db-migrations.php';
                Vortex_DB_Migrations::ensure_search_results_table();
                
                // Final check
                $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
                if ($table_exists) {
                    $success = true;
                    error_log('Successfully created vortex_search_results table using migrations method.');
                } else {
                    error_log('FAILED to create vortex_search_results table after exhausting all methods.');
                }
            }
        } else if ($success) {
            error_log('Successfully repaired vortex_search_results table.');
        }
        
        // If the table exists but has issues, try to repair the structure
        if ($table_exists) {
            // Get DB structure definition and compare
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-db-tables.php';
            $db_tables = VORTEX_DB_Tables::get_instance();
            
            // This forces a structure check and repair
            $db_tables->create_search_results_table();
            
            $success = true;
            error_log('Checked and repaired vortex_search_results table structure.');
        }
    } catch (Exception $e) {
        error_log('Exception in vortex_force_repair_search_results_table: ' . $e->getMessage());
        $success = false;
    }
    
    return $success;
}

// Create an admin-only AJAX endpoint to force repair search results table
function vortex_admin_repair_search_results_table() {
    // Security check
    check_ajax_referer('vortex_repair_nonce', 'nonce');
    
    // Check user permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'You do not have permission to perform this action.'));
        return;
    }
    
    // Perform the repair
    $result = vortex_force_repair_search_results_table();
    
    if ($result) {
        wp_send_json_success(array('message' => 'Search results table successfully repaired!'));
    } else {
        wp_send_json_error(array('message' => 'Failed to repair search results table. Check server logs for details.'));
    }
}
add_action('wp_ajax_vortex_repair_search_results_table', 'vortex_admin_repair_search_results_table'); 

// Add a hook to admin section to allow manually triggering the search results table repair
function vortex_add_admin_repair_hooks() {
    // Only for administrators
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Check if the search results table exists
    global $wpdb;
    $table_name = $wpdb->prefix . 'vortex_search_results';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    // If the table doesn't exist, show an admin notice
    if (!$table_exists) {
        add_action('admin_notices', 'vortex_search_results_table_notice');
    }
}
add_action('admin_init', 'vortex_add_admin_repair_hooks');

// Admin notice for missing search results table
function vortex_search_results_table_notice() {
    ?>
    <div class="notice notice-error">
        <p><strong>VORTEX AI Marketplace:</strong> The search results table is missing from your database.</p>
        <p>
            <button type="button" id="vortex-repair-search-results-table" class="button button-primary">
                Repair Search Results Table
            </button>
            <span id="vortex-repair-status" style="display: none; margin-left: 10px;"></span>
        </p>
    </div>
    <script>
    jQuery(document).ready(function($) {
        $('#vortex-repair-search-results-table').on('click', function() {
            var $button = $(this);
            var $status = $('#vortex-repair-status');
            
            $button.prop('disabled', true);
            $status.text('Repairing...').show();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_repair_search_results_table',
                    nonce: '<?php echo wp_create_nonce('vortex_repair_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $status.text(response.data.message).css('color', 'green');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        $button.prop('disabled', false);
                        $status.text(response.data.message).css('color', 'red');
                    }
                },
                error: function() {
                    $button.prop('disabled', false);
                    $status.text('An error occurred. Please try again.').css('color', 'red');
                }
            });
        });
    });
    </script>
    <?php
}

// Fix the social hashtags table on plugin load
function vortex_fix_social_hashtags_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'vortex_social_hashtags';
    
    // Check if the table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    // If table doesn't exist, create it
    if (!$table_exists) {
        try {
            // Load required classes
            require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-db-migrations.php';
            
            // Create the table directly using the migrations class
            Vortex_DB_Migrations::ensure_social_hashtags_table();
            
            // Also ensure the mapping table exists
            Vortex_DB_Migrations::ensure_hashtag_share_mapping_table();
            
            // Verify the tables were created
            $hashtags_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            $mapping_table_name = $wpdb->prefix . 'vortex_hashtag_share_mapping';
            $mapping_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$mapping_table_name'") === $mapping_table_name;
            
            if ($hashtags_table_exists && $mapping_table_exists) {
                error_log('Successfully created social hashtags tables on plugin load.');
            } else {
                error_log('Failed to create social hashtags tables on plugin load. Falling back to repair method.');
                
                // Try using the DB repair class as a fallback
                require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-db-repair.php';
                $db_repair = VORTEX_DB_Repair::get_instance();
                
                if (method_exists($db_repair, 'repair_social_hashtags_table')) {
                    $db_repair->repair_social_hashtags_table();
                }
            }
        } catch (Exception $e) {
            error_log('Exception when creating social hashtags tables: ' . $e->getMessage());
        }
    }
}

// Function to force repair the social hashtags table
// Can be called from admin functions or via AJAX if needed
function vortex_force_repair_social_hashtags_table() {
    // Load required classes
    require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-db-tables.php';
    require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-db-repair.php';
    
    // Get DB repair instance
    $db_repair = VORTEX_DB_Repair::get_instance();
    
    // Force repair the table
    $success = $db_repair->repair_social_hashtags_table();
    
    if ($success) {
        error_log('Successfully repaired social hashtags tables.');
        return true;
    } else {
        error_log('Failed to repair social hashtags tables.');
        return false;
    }
}

// Create an admin-only AJAX endpoint to force repair
function vortex_admin_force_repair_social_hashtags_table() {
    // Security check
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized access.'));
        return;
    }
    
    $result = vortex_force_repair_social_hashtags_table();
    
    if ($result) {
        wp_send_json_success(array('message' => 'Social hashtags tables repaired successfully.'));
    } else {
        wp_send_json_error(array('message' => 'Failed to repair social hashtags tables.'));
    }
}
add_action('wp_ajax_vortex_repair_social_hashtags_table', 'vortex_admin_force_repair_social_hashtags_table'); 

// Add a hook to admin section to allow manually triggering the social hashtags table repair
function vortex_add_admin_social_hashtags_repair_hooks() {
    // Only for administrators
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Check if the social hashtags table exists
    global $wpdb;
    $table_name = $wpdb->prefix . 'vortex_social_hashtags';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    // If the table doesn't exist, show an admin notice
    if (!$table_exists) {
        add_action('admin_notices', 'vortex_social_hashtags_table_notice');
    }
}
add_action('admin_init', 'vortex_add_admin_social_hashtags_repair_hooks');

// Admin notice for missing social hashtags table
function vortex_social_hashtags_table_notice() {
    ?>
    <div class="notice notice-error">
        <p><strong>VORTEX AI Marketplace:</strong> The social hashtags table is missing from your database.</p>
        <p>
            <button type="button" id="vortex-repair-social-hashtags-table" class="button button-primary">
                Repair Social Hashtags Table
            </button>
            <span id="vortex-repair-hashtags-status" style="display: none; margin-left: 10px;"></span>
        </p>
    </div>
    <script>
    jQuery(document).ready(function($) {
        $('#vortex-repair-social-hashtags-table').on('click', function() {
            var $button = $(this);
            var $status = $('#vortex-repair-hashtags-status');
            
            $button.prop('disabled', true);
            $status.text('Repairing...').show();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_repair_social_hashtags_table',
                    nonce: '<?php echo wp_create_nonce('vortex_repair_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $status.text(response.data.message).css('color', 'green');
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        $button.prop('disabled', false);
                        $status.text(response.data.message).css('color', 'red');
                    }
                },
                error: function() {
                    $button.prop('disabled', false);
                    $status.text('An error occurred. Please try again.').css('color', 'red');
                }
            });
        });
    });
    </script>
    <?php
}

require_once plugin_dir_path(__FILE__) . 'admin/class-vortex-thorius-admin.php';
require_once plugin_dir_path(__FILE__) . 'admin/class-thorius-learning-dashboard.php'; 