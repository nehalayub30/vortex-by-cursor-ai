<?php
/**
 * VORTEX Internationalization Handler
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage Core
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_I18n Class
 * 
 * Advanced internationalization with AI learning capabilities.
 *
 * @since 1.0.0
 */
class VORTEX_I18n {
    /**
     * Instance of this class.
     */
    protected static $instance = null;
    
    /**
     * Constructor
     */
    private function __construct() {
        // Initialize language support
        add_action('plugins_loaded', array($this, 'load_plugin_textdomain'));
        
        // Handle language switching
        add_action('locale_loaded', array($this, 'handle_language_change'));
        
        // Add AJAX language support
        add_action('wp_ajax_nopriv_vortex_switch_language', array($this, 'ajax_switch_language'));
        add_action('wp_ajax_vortex_switch_language', array($this, 'ajax_switch_language'));
        
        // Handle RTL support
        add_action('wp_head', array($this, 'add_rtl_support'));
        
        // Filter AI messages for localization
        add_filter('vortex_ai_message', array($this, 'localize_ai_message'), 10, 3);
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
     * Load textdomain
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'vortex-marketplace',
            false,
            dirname(plugin_basename(VORTEX_PLUGIN_FILE)) . '/languages'
        );
        
        // Store current language for AI learning
        update_option('vortex_current_locale', get_locale());
    }
    
    /**
     * Handle language changes to preserve AI learning state
     */
    public function handle_language_change($locale) {
        $previous_locale = get_option('vortex_current_locale', 'en_US');
        
        if ($previous_locale !== $locale) {
            update_option('vortex_current_locale', $locale);
            
            // Initialize AI agents with new language context while preserving state
            do_action('vortex_ai_agent_init', 'language_context', 
                array('HURAII', 'CLOE', 'BusinessStrategist'), 
                'active',
                array(
                    'previous_locale' => $previous_locale,
                    'new_locale' => $locale,
                    'context' => 'internationalization',
                    'preserve_learning' => true,
                    'language_session' => uniqid('lang_')
                )
            );
            
            // Log language change for AI learning with enhanced metadata
            do_action('vortex_ai_interaction', 'language_change', array(
                'previous_locale' => $previous_locale,
                'new_locale' => $locale,
                'direction' => is_rtl() ? 'rtl' : 'ltr',
                'timestamp' => current_time('timestamp'),
                'is_admin' => is_admin()
            ), get_current_user_id());
        }
    }
    
    /**
     * AJAX handler for language switching
     */
    public function ajax_switch_language() {
        check_ajax_referer('vortex_switch_language_nonce', 'nonce');
        
        $locale = isset($_POST['locale']) ? sanitize_text_field($_POST['locale']) : 'en_US';
        $user_id = get_current_user_id();
        
        if ($user_id) {
            update_user_meta($user_id, 'locale', $locale);
        } else {
            // For non-logged in users, set a cookie
            setcookie('vortex_locale', $locale, time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
        }
        
        $this->handle_language_change($locale);
        
        // Return localized strings for JavaScript
        wp_send_json_success(array(
            'locale' => $locale,
            'is_rtl' => apply_filters('locale_is_rtl', false, $locale),
            'strings' => $this->get_localized_strings_for_js()
        ));
    }
    
    /**
     * Add RTL support
     */
    public function add_rtl_support() {
        if (is_rtl()) {
            echo '<style type="text/css">.vortex-marketplace-container { direction: rtl; }</style>';
        }
    }
    
    /**
     * Get localized AI welcome messages
     */
    public function get_ai_welcome_messages($agent_name) {
        $messages = array();
        
        switch ($agent_name) {
            case 'HURAII':
                $messages = array(
                    __('Welcome to HURAII!', 'vortex-marketplace'),
                    __('Let\'s create something amazing together.', 'vortex-marketplace'),
                    __('Ready to bring your vision to life with Seed Art.', 'vortex-marketplace'),
                    __('Your artistic journey begins now!', 'vortex-marketplace')
                );
                break;
                
            case 'CLOE':
                $messages = array(
                    __('CLOE is here to curate your experience.', 'vortex-marketplace'),
                    __('Discover amazing artwork tailored to your taste.', 'vortex-marketplace'),
                    __('Let\'s explore the world of digital art together.', 'vortex-marketplace')
                );
                break;
                
            case 'BusinessStrategist':
                $messages = array(
                    __('Ready to analyze market trends for you.', 'vortex-marketplace'),
                    __('Let\'s optimize your art business strategy.', 'vortex-marketplace'),
                    __('Discover new revenue opportunities in the art market.', 'vortex-marketplace')
                );
                break;
        }
        
        return $messages;
    }
    
    /**
     * Localize AI message with enhanced context
     */
    public function localize_ai_message($message, $agent_name, $context) {
        // Store original message for AI learning
        $original_message = $message;
        
        // Translate message
        $translated_message = __($message, 'vortex-marketplace');
        
        // Track translation for AI learning
        do_action('vortex_ai_agent_learn', $agent_name, 'message_translation', array(
            'original' => $original_message,
            'translated' => $translated_message,
            'context' => $context,
            'locale' => get_locale()
        ));
        
        return $translated_message;
    }
    
    /**
     * Get localized strings for JavaScript
     */
    private function get_localized_strings_for_js() {
        return array(
            'viewsLabel' => __('Views', 'vortex-marketplace'),
            'salesLabel' => __('Sales', 'vortex-marketplace'),
            'revenueLabel' => __('Revenue', 'vortex-marketplace'),
            'loadingText' => __('Loading data...', 'vortex-marketplace'),
            'errorText' => __('Error loading data', 'vortex-marketplace'),
            'confirmDelete' => __('Are you sure you want to delete this item?', 'vortex-marketplace'),
            'searchPlaceholder' => __('Search...', 'vortex-marketplace'),
            'filterLabel' => __('Filter', 'vortex-marketplace'),
            'applyLabel' => __('Apply', 'vortex-marketplace'),
            'resetLabel' => __('Reset', 'vortex-marketplace'),
            'noResultsText' => __('No results found', 'vortex-marketplace'),
            'aiGenerating' => __('AI is generating content...', 'vortex-marketplace'),
            'aiAnalyzing' => __('AI is analyzing...', 'vortex-marketplace'),
            'aiRecommending' => __('AI is preparing recommendations...', 'vortex-marketplace')
        );
    }
}

// Initialize Internationalization
VORTEX_I18n::get_instance(); 