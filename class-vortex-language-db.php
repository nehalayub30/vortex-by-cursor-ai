<?php
/**
 * VORTEX Language Database Handler
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage Internationalization
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_Language_DB Class
 * 
 * Manages language-related database operations while ensuring
 * AI agents maintain active learning during language operations.
 *
 * @since 1.0.0
 */
class VORTEX_Language_DB {
    /**
     * Instance of this class.
     *
     * @since 1.0.0
     * @var object
     */
    protected static $instance = null;
    
    /**
     * Active AI agent learning states
     *
     * @since 1.0.0
     * @var array
     */
    private $ai_agents = array();
    
    /**
     * Table name for language data
     *
     * @since 1.0.0
     * @var string
     */
    private $table_name;
    
    /**
     * Table name for language translations
     *
     * @since 1.0.0
     * @var string
     */
    private $translations_table;
    
    /**
     * Constructor
     *
     * @since 1.0.0
     */
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'vortex_language_data';
        $this->translations_table = $wpdb->prefix . 'vortex_language_translations';
        
        // Initialize AI agents for language operations
        $this->initialize_ai_agents();
        
        // Set up hooks
        $this->setup_hooks();
    }
    
    /**
     * Get instance of this class.
     *
     * @since 1.0.0
     * @return VORTEX_Language_DB
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize AI agents for language operations
     *
     * @since 1.0.0
     * @return void
     */
    private function initialize_ai_agents() {
        // Initialize HURAII for visual context in different languages
        $this->ai_agents['HURAII'] = array(
            'active' => true,
            'learning_mode' => 'active',
            'context' => 'language_processing',
            'capabilities' => array(
                'visual_term_translation',
                'seed_art_multilingual_adaptation',
                'style_cross_cultural_analysis'
            )
        );
        
        // Initialize CLOE for content curation across languages
        $this->ai_agents['CLOE'] = array(
            'active' => true,
            'learning_mode' => 'active',
            'context' => 'language_curation',
            'capabilities' => array(
                'cross_cultural_recommendation',
                'language_preference_learning',
                'multilingual_engagement_analysis'
            )
        );
        
        // Initialize BusinessStrategist for international market insights
        $this->ai_agents['BusinessStrategist'] = array(
            'active' => true,
            'learning_mode' => 'active',
            'context' => 'international_market',
            'capabilities' => array(
                'regional_market_analysis',
                'localized_pricing_strategy',
                'international_trends_forecasting'
            )
        );
        
        // Initialize AI agents with language context
        do_action('vortex_ai_agent_init', 'language_operations', array_keys($this->ai_agents), 'active');
    }
    
    /**
     * Set up hooks
     *
     * @since 1.0.0
     * @return void
     */
    private function setup_hooks() {
        // AJAX handlers for language operations
        add_action('wp_ajax_vortex_save_translation', array($this, 'ajax_save_translation'));
        add_action('wp_ajax_vortex_get_translations', array($this, 'ajax_get_translations'));
        add_action('wp_ajax_vortex_set_user_language', array($this, 'ajax_set_user_language'));
        add_action('wp_ajax_nopriv_vortex_set_user_language', array($this, 'ajax_set_user_language'));
        
        // Activation/upgrade hooks
        register_activation_hook(VORTEX_PLUGIN_FILE, array($this, 'create_tables'));
        add_action('plugins_loaded', array($this, 'check_tables'));
    }
    
    /**
     * Create database tables
     *
     * @since 1.0.0
     * @return void
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Language data table
        $sql = "CREATE TABLE $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            locale varchar(20) NOT NULL,
            language_name varchar(100) NOT NULL,
            is_rtl tinyint(1) DEFAULT 0,
            enabled tinyint(1) DEFAULT 1,
            completion_percent float DEFAULT 0,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY locale (locale)
        ) $charset_collate;";
        
        // Translations table
        $sql2 = "CREATE TABLE $this->translations_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            string_key varchar(255) NOT NULL,
            context varchar(100) DEFAULT '',
            source_string text NOT NULL,
            locale varchar(20) NOT NULL,
            translated_string text NOT NULL,
            translator_id bigint(20) DEFAULT 0,
            ai_enhanced tinyint(1) DEFAULT 0,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY string_locale (string_key,locale),
            KEY locale (locale)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($sql2);
        
        // Insert default languages
        $this->insert_default_languages();
    }
    
    /**
     * Check tables on plugin load
     *
     * @since 1.0.0
     * @return void
     */
    public function check_tables() {
        global $wpdb;
        
        // Check if tables exist
        $language_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$this->table_name'") === $this->table_name;
        $translations_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$this->translations_table'") === $this->translations_table;
        
        if (!$language_table_exists || !$translations_table_exists) {
            $this->create_tables();
        }
    }
    
    /**
     * Insert default languages
     *
     * @since 1.0.0
     * @return void
     */
    private function insert_default_languages() {
        global $wpdb;
        
        $default_languages = array(
            array(
                'locale' => 'en_US',
                'language_name' => 'English (US)',
                'is_rtl' => 0,
                'enabled' => 1,
                'completion_percent' => 100
            ),
            array(
                'locale' => 'es_ES',
                'language_name' => 'Español',
                'is_rtl' => 0,
                'enabled' => 1,
                'completion_percent' => 85
            ),
            array(
                'locale' => 'fr_FR',
                'language_name' => 'Français',
                'is_rtl' => 0,
                'enabled' => 1,
                'completion_percent' => 85
            )
        );
        
        foreach ($default_languages as $language) {
            $wpdb->replace(
                $this->table_name,
                $language
            );
        }
    }
    
    /**
     * Get available languages
     *
     * @since 1.0.0
     * @param bool $enabled_only Whether to return only enabled languages
     * @return array Available languages
     */
    public function get_languages($enabled_only = true) {
        global $wpdb;
        
        $where = $enabled_only ? 'WHERE enabled = 1' : '';
        $languages = $wpdb->get_results(
            "SELECT locale, language_name, is_rtl, completion_percent FROM $this->table_name $where ORDER BY language_name ASC",
            ARRAY_A
        );
        
        // Notify AI agents about language query for learning
        foreach ($this->ai_agents as $agent_name => $config) {
            if ($config['active']) {
                do_action('vortex_ai_agent_learn', $agent_name, 'language_query', array(
                    'query_type' => 'available_languages',
                    'enabled_only' => $enabled_only,
                    'result_count' => count($languages),
                    'timestamp' => current_time('timestamp')
                ));
            }
        }
        
        return $languages;
    }
    
    /**
     * Get translations for a specific locale
     *
     * @since 1.0.0
     * @param string $locale The locale to get translations for
     * @param string $context Optional context to filter by
     * @return array Translations
     */
    public function get_translations($locale, $context = '') {
        global $wpdb;
        
        $where = 'WHERE locale = %s';
        $params = array($locale);
        
        if (!empty($context)) {
            $where .= ' AND context = %s';
            $params[] = $context;
        }
        
        $translations = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT string_key, source_string, translated_string, context, ai_enhanced 
                FROM $this->translations_table $where",
                $params
            ),
            ARRAY_A
        );
        
        // Track this query for AI learning
        $user_id = get_current_user_id();
        do_action('vortex_ai_interaction', 'translation_query', array(
            'locale' => $locale,
            'context' => $context,
            'result_count' => count($translations)
        ), $user_id);
        
        return $translations;
    }
    
    /**
     * Save a translation
     *
     * @since 1.0.0
     * @param string $string_key The string key
     * @param string $source_string The source string
     * @param string $locale The locale
     * @param string $translated_string The translated string
     * @param string $context Optional context
     * @param bool $ai_enhanced Whether this was enhanced by AI
     * @return bool|int False on failure, affected rows on success
     */
    public function save_translation($string_key, $source_string, $locale, $translated_string, $context = '', $ai_enhanced = false) {
        global $wpdb;
        
        $user_id = get_current_user_id();
        
        $result = $wpdb->replace(
            $this->translations_table,
            array(
                'string_key' => $string_key,
                'source_string' => $source_string,
                'locale' => $locale,
                'translated_string' => $translated_string,
                'context' => $context,
                'translator_id' => $user_id,
                'ai_enhanced' => $ai_enhanced ? 1 : 0,
                'last_updated' => current_time('mysql')
            )
        );
        
        if ($result) {
            // Update completion percentage
            $this->update_completion_percentage($locale);
            
            // Feed this translation to AI agents for learning
            foreach ($this->ai_agents as $agent_name => $config) {
                if ($config['active']) {
                    do_action('vortex_ai_agent_learn', $agent_name, 'translation_saved', array(
                        'string_key' => $string_key,
                        'source_string' => $source_string,
                        'locale' => $locale,
                        'translated_string' => $translated_string,
                        'context' => $context,
                        'user_id' => $user_id,
                        'ai_enhanced' => $ai_enhanced,
                        'timestamp' => current_time('timestamp')
                    ));
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Update language completion percentage
     *
     * @since 1.0.0
     * @param string $locale The locale to update
     * @return void
     */
    private function update_completion_percentage($locale) {
        global $wpdb;
        
        // Get total count of translatable strings (based on en_US)
        $total_strings = $wpdb->get_var(
            "SELECT COUNT(DISTINCT string_key) FROM $this->translations_table WHERE locale = 'en_US'"
        );
        
        if ($total_strings <= 0) {
            return;
        }
        
        // Get count of translated strings for this locale
        $translated_strings = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(DISTINCT string_key) FROM $this->translations_table WHERE locale = %s",
                $locale
            )
        );
        
        // Calculate percentage
        $percentage = ($translated_strings / $total_strings) * 100;
        
        // Update language record
        $wpdb->update(
            $this->table_name,
            array(
                'completion_percent' => $percentage,
                'last_updated' => current_time('mysql')
            ),
            array('locale' => $locale)
        );
    }
    
    /**
     * Generate AI-enhanced translations
     *
     * @since 1.0.0
     * @param string $locale The target locale
     * @param array $strings Array of string keys to translate
     * @return int Number of translations generated
     */
    public function generate_ai_translations($locale, $strings) {
        $count = 0;
        
        // Get source strings from English
        $source_translations = $this->get_translations('en_US');
        $source_map = array();
        
        foreach ($source_translations as $translation) {
            $source_map[$translation['string_key']] = $translation;
        }
        
        // Use HURAII for visual/artistic term translations
        if (class_exists('VORTEX_HURAII') && method_exists('VORTEX_HURAII', 'get_instance')) {
            $huraii = VORTEX_HURAII::get_instance();
            
            foreach ($strings as $string_key) {
                if (isset($source_map[$string_key]) && strpos($string_key, 'visual_') === 0) {
                    $source = $source_map[$string_key]['source_string'];
                    $context = $source_map[$string_key]['context'];
                    
                    if (method_exists($huraii, 'translate_visual_term')) {
                        $translated = $huraii->translate_visual_term($source, $locale);
                        
                        if ($translated) {
                            $this->save_translation($string_key, $source, $locale, $translated, $context, true);
                            $count++;
                        }
                    }
                }
            }
        }
        
        // Use CLOE for curation/collection terms
        if (class_exists('VORTEX_CLOE') && method_exists('VORTEX_CLOE', 'get_instance')) {
            $cloe = VORTEX_CLOE::get_instance();
            
            foreach ($strings as $string_key) {
                if (isset($source_map[$string_key]) && strpos($string_key, 'curation_') === 0) {
                    $source = $source_map[$string_key]['source_string'];
                    $context = $source_map[$string_key]['context'];
                    
                    if (method_exists($cloe, 'translate_curation_term')) {
                        $translated = $cloe->translate_curation_term($source, $locale);
                        
                        if ($translated) {
                            $this->save_translation($string_key, $source, $locale, $translated, $context, true);
                            $count++;
                        }
                    }
                }
            }
        }
        
        // Use BusinessStrategist for market/business terms
        if (class_exists('VORTEX_BusinessStrategist') && method_exists('VORTEX_BusinessStrategist', 'get_instance')) {
            $bs = VORTEX_BusinessStrategist::get_instance();
            
            foreach ($strings as $string_key) {
                if (isset($source_map[$string_key]) && strpos($string_key, 'market_') === 0) {
                    $source = $source_map[$string_key]['source_string'];
                    $context = $source_map[$string_key]['context'];
                    
                    if (method_exists($bs, 'translate_market_term')) {
                        $translated = $bs->translate_market_term($source, $locale);
                        
                        if ($translated) {
                            $this->save_translation($string_key, $source, $locale, $translated, $context, true);
                            $count++;
                        }
                    }
                }
            }
        }
        
        // Advanced integration with AI engines would go here
        // This is a placeholder for actual AI translation logic
        
        return $count;
    }
    
    /**
     * Get user language preference
     *
     * @since 1.0.0
     * @param int $user_id User ID (0 for current user)
     * @return string User's preferred locale
     */
    public function get_user_language($user_id = 0) {
        if ($user_id === 0) {
            $user_id = get_current_user_id();
        }
        
        if ($user_id) {
            $locale = get_user_meta($user_id, 'vortex_locale', true);
            if (!empty($locale)) {
                return $locale;
            }
        } else {
            // Check for cookie for non-logged in users
            if (isset($_COOKIE['vortex_locale'])) {
                return sanitize_text_field($_COOKIE['vortex_locale']);
            }
        }
        
        // Default to site language
        return get_locale();
    }
    
    /**
     * Set user language preference
     *
     * @since 1.0.0
     * @param string $locale Locale to set
     * @param int $user_id User ID (0 for current user)
     * @return bool Success
     */
    public function set_user_language($locale, $user_id = 0) {
        if ($user_id === 0) {
            $user_id = get_current_user_id();
        }
        
        // Validate locale exists
        $languages = $this->get_languages();
        $valid_locales = array_column($languages, 'locale');
        
        if (!in_array($locale, $valid_locales)) {
            return false;
        }
        
        if ($user_id) {
            update_user_meta($user_id, 'vortex_locale', $locale);
        } else {
            // Set cookie for non-logged in users (30 days expiration)
            setcookie('vortex_locale', $locale, time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN);
        }
        
        // Track this language change for AI learning
        do_action('vortex_ai_interaction', 'language_preference_changed', array(
            'locale' => $locale,
            'previous_locale' => $this->get_user_language($user_id)
        ), $user_id);
        
        return true;
    }
    
    /**
     * AJAX handler for saving translation
     *
     * @since 1.0.0
     * @return void
     */
    public function ajax_save_translation() {
        check_ajax_referer('vortex_translation_nonce', 'nonce');
        
        if (!current_user_can('manage_options') && !current_user_can('edit_vortex_translations')) {
            wp_send_json_error(array('message' => __('Permission denied', 'vortex-marketplace')));
        }
        
        $string_key = isset($_POST['string_key']) ? sanitize_text_field($_POST['string_key']) : '';
        $source_string = isset($_POST['source_string']) ? sanitize_textarea_field($_POST['source_string']) : '';
        $locale = isset($_POST['locale']) ? sanitize_text_field($_POST['locale']) : '';
        $translated_string = isset($_POST['translated_string']) ? sanitize_textarea_field($_POST['translated_string']) : '';
        $context = isset($_POST['context']) ? sanitize_text_field($_POST['context']) : '';
        
        if (empty($string_key) || empty($source_string) || empty($locale) || empty($translated_string)) {
            wp_send_json_error(array('message' => __('Missing required fields', 'vortex-marketplace')));
        }
        
        $result = $this->save_translation($string_key, $source_string, $locale, $translated_string, $context);
        
        if ($result) {
            wp_send_json_success(array('message' => __('Translation saved successfully', 'vortex-marketplace')));
        } else {
            wp_send_json_error(array('message' => __('Failed to save translation', 'vortex-marketplace')));
        }
    }
    
    /**
     * AJAX handler for getting translations
     *
     * @since 1.0.0
     * @return void
     */
    public function ajax_get_translations() {
        check_ajax_referer('vortex_translation_nonce', 'nonce');
        
        $locale = isset($_POST['locale']) ? sanitize_text_field($_POST['locale']) : '';
        $context = isset($_POST['context']) ? sanitize_text_field($_POST['context']) : '';
        
        if (empty($locale)) {
            wp_send_json_error(array('message' => __('Locale is required', 'vortex-marketplace')));
        }
        
        $translations = $this->get_translations($locale, $context);
        
        // Format for JavaScript
        $formatted = array();
        foreach ($translations as $translation) {
            $formatted[$translation['string_key']] = $translation['translated_string'];
        }
        
        wp_send_json_success($formatted);
    }
    
    /**
     * AJAX handler for setting user language
     *
     * @since 1.0.0
     * @return void
     */
    public function ajax_set_user_language() {
        check_ajax_referer('vortex_language_nonce', 'nonce');
        
        $locale = isset($_POST['locale']) ? sanitize_text_field($_POST['locale']) : '';
        
        if (empty($locale)) {
            wp_send_json_error(array('message' => __('Locale is required', 'vortex-marketplace')));
        }
        
        $result = $this->set_user_language($locale);
        
        if ($result) {
            // Get translations for this locale for immediate use
            $translations = $this->get_translations($locale);
            $formatted = array();
            foreach ($translations as $translation) {
                $formatted[$translation['string_key']] = $translation['translated_string'];
            }
            
            wp_send_json_success(array(
                'message' => __('Language preference saved', 'vortex-marketplace'),
                'locale' => $locale,
                'translations' => $formatted
            ));
        } else {
            wp_send_json_error(array('message' => __('Invalid locale', 'vortex-marketplace')));
        }
    }
}

// Initialize Language DB
VORTEX_Language_DB::get_instance(); 