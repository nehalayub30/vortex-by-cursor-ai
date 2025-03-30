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

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require VORTEX_PLUGIN_DIR . 'includes/class-vortex-ai-marketplace.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-vortex-shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'class-vortex-support-chat.php';
require_once VORTEX_PLUGIN_DIR . 'includes/class-vortex-subscriptions.php';

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

// Ensure AI agents are loaded efficiently
function vortex_load_ai_agents() {
    // Only load AI agents when needed
    if (
        is_admin() || 
        (defined('DOING_AJAX') && DOING_AJAX) || 
        has_shortcode(get_post()->post_content, 'vortex_huraii_generator') ||
        has_shortcode(get_post()->post_content, 'vortex_cloe_insights') ||
        has_shortcode(get_post()->post_content, 'vortex_business_plan')
    ) {
        // Load HURAII only when needed
        if (
            isset($_REQUEST['action']) && 
            (strpos($_REQUEST['action'], 'vortex_huraii') !== false || has_shortcode(get_post()->post_content, 'vortex_huraii_generator'))
        ) {
            require_once plugin_dir_path(__FILE__) . 'class-vortex-huraii.php';
        }
        
        // Load CLOE only when needed
        if (
            isset($_REQUEST['action']) && 
            (strpos($_REQUEST['action'], 'vortex_cloe') !== false || has_shortcode(get_post()->post_content, 'vortex_cloe_insights'))
        ) {
            require_once plugin_dir_path(__FILE__) . 'class-vortex-cloe.php';
        }
        
        // Load Business Strategist only when needed
        if (
            isset($_REQUEST['action']) && 
            (strpos($_REQUEST['action'], 'vortex_business') !== false || has_shortcode(get_post()->post_content, 'vortex_business_plan'))
        ) {
            require_once plugin_dir_path(__FILE__) . 'class-vortex-business-strategist.php';
        }
    }
}
add_action('template_redirect', 'vortex_load_ai_agents');
add_action('admin_init', 'vortex_load_ai_agents');

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

// Shortcodes
require plugin_dir_path(__FILE__) . 'includes/class-vortex-marketplace-shortcodes.php'; 