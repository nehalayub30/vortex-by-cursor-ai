<?php
/**
 * The Translation API functionality of the plugin.
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

/**
 * The Translation API class.
 *
 * This class handles the translation functionality for the marketplace,
 * providing methods for both automatic and manual translation of content.
 *
 * @since      1.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_Translation_API {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Supported languages.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $supported_languages    Array of supported languages.
     */
    private $supported_languages;

    /**
     * Default language.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $default_language    Default language code.
     */
    private $default_language;

    /**
     * Translation cache expiration time in seconds.
     *
     * @since    1.0.0
     * @access   private
     * @var      int    $cache_expiration    Cache expiration time.
     */
    private $cache_expiration;

    /**
     * Translation service to use (Google, DeepL, etc.).
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $translation_service    The translation service.
     */
    private $translation_service;

    /**
     * API key for the translation service.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $api_key    API key for the translation service.
     */
    private $api_key;

    /**
     * Database table for storing translations.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $translations_table    Table name for storing translations.
     */
    private $translations_table;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;

        global $wpdb;
        $this->translations_table = $wpdb->prefix . 'vortex_translations';
        
        // Default cache expiration: 1 week
        $this->cache_expiration = 7 * DAY_IN_SECONDS;
        
        // Initialize supported languages
        $this->init_supported_languages();
        
        // Set default language
        $this->default_language = get_option('vortex_default_language', 'en');
        
        // Set translation service
        $this->translation_service = get_option('vortex_translation_service', 'google');
        
        // Get API key
        $this->api_key = get_option('vortex_translation_api_key', '');
        
        // Register REST API endpoints
        add_action('rest_api_init', array($this, 'register_api_endpoints'));
        
        // Register admin AJAX endpoints
        add_action('wp_ajax_vortex_translate_content', array($this, 'ajax_translate_content'));
        add_action('wp_ajax_nopriv_vortex_translate_content', array($this, 'ajax_translate_content'));
        add_action('wp_ajax_vortex_get_translated_content', array($this, 'ajax_get_translated_content'));
        add_action('wp_ajax_nopriv_vortex_get_translated_content', array($this, 'ajax_get_translated_content'));
        add_action('wp_ajax_vortex_switch_language', array($this, 'ajax_switch_language'));
        add_action('wp_ajax_nopriv_vortex_switch_language', array($this, 'ajax_switch_language'));
        
        // Handle user language preferences
        add_action('init', array($this, 'set_user_language'), 10);
        
        // Filter content for translation
        add_filter('the_title', array($this, 'translate_title'), 10, 2);
        add_filter('the_content', array($this, 'translate_content'), 10, 1);
        add_filter('get_the_excerpt', array($this, 'translate_excerpt'), 10, 2);
        
        // Schedule cleanup of old translations
        add_action('vortex_cleanup_translations', array($this, 'cleanup_old_translations'));
    }

    /**
     * Initialize supported languages.
     *
     * @since    1.0.0
     */
    private function init_supported_languages() {
        // Default supported languages
        $default_languages = array(
            'en' => array(
                'name' => 'English',
                'native_name' => 'English',
                'flag' => 'us',
                'rtl' => false
            ),
            'es' => array(
                'name' => 'Spanish',
                'native_name' => 'Español',
                'flag' => 'es',
                'rtl' => false
            ),
            'fr' => array(
                'name' => 'French',
                'native_name' => 'Français',
                'flag' => 'fr',
                'rtl' => false
            ),
            'de' => array(
                'name' => 'German',
                'native_name' => 'Deutsch',
                'flag' => 'de',
                'rtl' => false
            ),
            'it' => array(
                'name' => 'Italian',
                'native_name' => 'Italiano',
                'flag' => 'it',
                'rtl' => false
            ),
            'ja' => array(
                'name' => 'Japanese',
                'native_name' => '日本語',
                'flag' => 'jp',
                'rtl' => false
            ),
            'zh' => array(
                'name' => 'Chinese (Simplified)',
                'native_name' => '简体中文',
                'flag' => 'cn',
                'rtl' => false
            ),
            'ru' => array(
                'name' => 'Russian',
                'native_name' => 'Русский',
                'flag' => 'ru',
                'rtl' => false
            ),
            'ar' => array(
                'name' => 'Arabic',
                'native_name' => 'العربية',
                'flag' => 'sa',
                'rtl' => true
            ),
            'pt' => array(
                'name' => 'Portuguese',
                'native_name' => 'Português',
                'flag' => 'pt',
                'rtl' => false
            ),
            'ko' => array(
                'name' => 'Korean',
                'native_name' => '한국어',
                'flag' => 'kr',
                'rtl' => false
            ),
        );
        
        // Get languages from options
        $saved_languages = get_option('vortex_supported_languages', array());
        
        // Merge defaults with saved languages
        $this->supported_languages = !empty($saved_languages) ? $saved_languages : $default_languages;
    }

    /**
     * Register REST API endpoints.
     *
     * @since    1.0.0
     */
    public function register_api_endpoints() {
        register_rest_route('vortex/v1', '/translations/languages', array(
            'methods' => 'GET',
            'callback' => array($this, 'api_get_languages'),
            'permission_callback' => '__return_true',
        ));
        
        register_rest_route('vortex/v1', '/translations/translate', array(
            'methods' => 'POST',
            'callback' => array($this, 'api_translate_content'),
            'permission_callback' => '__return_true',
        ));
        
        register_rest_route('vortex/v1', '/translations/content/(?P<type>\w+)/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'api_get_translated_content'),
            'permission_callback' => '__return_true',
            'args' => array(
                'type' => array(
                    'validate_callback' => function($param) {
                        return in_array($param, array('artwork', 'artist', 'post', 'page'));
                    }
                ),
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));
    }

    /**
     * Get available languages via API.
     *
     * @since    1.0.0
     * @param    WP_REST_Request $request Full data about the request.
     * @return   WP_REST_Response
     */
    public function api_get_languages($request) {
        return new WP_REST_Response($this->supported_languages, 200);
    }

    /**
     * Translate content via API.
     *
     * @since    1.0.0
     * @param    WP_REST_Request $request Full data about the request.
     * @return   WP_REST_Response
     */
    public function api_translate_content($request) {
        $text = $request->get_param('text');
        $source_lang = $request->get_param('source_lang') ?: $this->default_language;
        $target_lang = $request->get_param('target_lang');
        
        if (empty($text) || empty($target_lang)) {
            return new WP_REST_Response(array(
                'error' => 'Missing required parameters'
            ), 400);
        }
        
        $translated = $this->translate_text($text, $source_lang, $target_lang);
        
        if (is_wp_error($translated)) {
            return new WP_REST_Response(array(
                'error' => $translated->get_error_message()
            ), 500);
        }
        
        return new WP_REST_Response(array(
            'source_text' => $text,
            'source_lang' => $source_lang,
            'target_lang' => $target_lang,
            'translated_text' => $translated
        ), 200);
    }

    /**
     * Get translated content for an entity via API.
     *
     * @since    1.0.0
     * @param    WP_REST_Request $request Full data about the request.
     * @return   WP_REST_Response
     */
    public function api_get_translated_content($request) {
        $type = $request->get_param('type');
        $id = intval($request->get_param('id'));
        $target_lang = $request->get_param('lang');
        
        if (!$target_lang) {
            $target_lang = $this->get_current_language();
        }
        
        // If target language is the same as default, return original content
        if ($target_lang === $this->default_language) {
            $content = $this->get_entity_content($type, $id);
            
            return new WP_REST_Response($content, 200);
        }
        
        // Get translated content
        $translated = $this->get_translated_entity($type, $id, $target_lang);
        
        if (is_wp_error($translated)) {
            return new WP_REST_Response(array(
                'error' => $translated->get_error_message()
            ), 500);
        }
        
        return new WP_REST_Response($translated, 200);
    }

    /**
     * Handle AJAX request for translating content.
     *
     * @since    1.0.0
     */
    public function ajax_translate_content() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_translation_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'vortex-ai-marketplace')));
        }
        
        $text = isset($_POST['text']) ? sanitize_textarea_field($_POST['text']) : '';
        $source_lang = isset($_POST['source_lang']) ? sanitize_text_field($_POST['source_lang']) : $this->default_language;
        $target_lang = isset($_POST['target_lang']) ? sanitize_text_field($_POST['target_lang']) : '';
        
        if (empty($text) || empty($target_lang)) {
            wp_send_json_error(array('message' => __('Missing required parameters.', 'vortex-ai-marketplace')));
        }
        
        $translated = $this->translate_text($text, $source_lang, $target_lang);
        
        if (is_wp_error($translated)) {
            wp_send_json_error(array('message' => $translated->get_error_message()));
        }
        
        wp_send_json_success(array(
            'source_text' => $text,
            'source_lang' => $source_lang,
            'target_lang' => $target_lang,
            'translated_text' => $translated
        ));
    }

    /**
     * Handle AJAX request for getting translated content.
     *
     * @since    1.0.0
     */
    public function ajax_get_translated_content() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_translation_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'vortex-ai-marketplace')));
        }
        
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $target_lang = isset($_POST['lang']) ? sanitize_text_field($_POST['lang']) : $this->get_current_language();
        
        if (empty($type) || empty($id)) {
            wp_send_json_error(array('message' => __('Missing required parameters.', 'vortex-ai-marketplace')));
        }
        
        // If target language is the same as default, return original content
        if ($target_lang === $this->default_language) {
            $content = $this->get_entity_content($type, $id);
            
            wp_send_json_success($content);
        }
        
        // Get translated content
        $translated = $this->get_translated_entity($type, $id, $target_lang);
        
        if (is_wp_error($translated)) {
            wp_send_json_error(array('message' => $translated->get_error_message()));
        }
        
        wp_send_json_success($translated);
    }

    /**
     * Handle AJAX request for switching languages.
     *
     * @since    1.0.0
     */
    public function ajax_switch_language() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_translation_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'vortex-ai-marketplace')));
        }
        
        $lang = isset($_POST['lang']) ? sanitize_text_field($_POST['lang']) : '';
        
        if (empty($lang) || !isset($this->supported_languages[$lang])) {
            wp_send_json_error(array('message' => __('Invalid language selected.', 'vortex-ai-marketplace')));
        }
        
        // Store language preference in user meta if logged in
        if (is_user_logged_in()) {
            update_user_meta(get_current_user_id(), 'vortex_language', $lang);
        }
        
        // Set cookie for non-logged in users
        setcookie('vortex_language', $lang, time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN);
        
        wp_send_json_success(array(
            'message' => sprintf(__('Language switched to %s.', 'vortex-ai-marketplace'), $this->supported_languages[$lang]['name']),
            'language' => $lang,
            'language_data' => $this->supported_languages[$lang]
        ));
    }

    /**
     * Set user language preference.
     *
     * @since    1.0.0
     */
    public function set_user_language() {
        // Don't override language for admin users in admin area
        if (is_admin() && current_user_can('manage_options')) {
            return;
        }

        $lang = $this->get_current_language();
        
        // Set locale for this request if it's not the default language
        if ($lang !== $this->default_language) {
            // Map language code to WordPress locale
            $locales = array(
                'en' => 'en_US',
                'es' => 'es_ES',
                'fr' => 'fr_FR',
                'de' => 'de_DE',
                'it' => 'it_IT',
                'ja' => 'ja',
                'zh' => 'zh_CN',
                'ru' => 'ru_RU',
                'ar' => 'ar',
                'pt' => 'pt_PT',
                'ko' => 'ko_KR',
            );
            
            if (isset($locales[$lang])) {
                add_filter('locale', function() use ($locales, $lang) {
                    return $locales[$lang];
                });
            }
        }
    }

    /**
     * Translate post title.
     *
     * @since    1.0.0
     * @param    string    $title    The post title.
     * @param    int       $post_id  The post ID.
     * @return   string    The translated title.
     */
    public function translate_title($title, $post_id = null) {
        // Skip translation if in admin or not a singular view
        if (is_admin() || !is_singular() || !$post_id) {
            return $title;
        }
        
        $current_lang = $this->get_current_language();
        
        // Skip translation if current language is the same as default
        if ($current_lang === $this->default_language) {
            return $title;
        }
        
        $post_type = get_post_type($post_id);
        
        // Only translate if it's a VORTEX post type
        if (!in_array($post_type, array('vortex_artwork', 'vortex_post', 'page'))) {
            return $title;
        }
        
        // Determine entity type
        $entity_type = 'post';
        if ($post_type === 'vortex_artwork') {
            $entity_type = 'artwork';
        }
        
        // Get cached translation
        $translated = $this->get_cached_translation($entity_type, $post_id, 'title', $current_lang);
        
        if ($translated) {
            return $translated;
        }
        
        // Translate the title
        $translated = $this->translate_text($title, $this->default_language, $current_lang);
        
        if (!is_wp_error($translated)) {
            // Cache the translation
            $this->cache_translation($entity_type, $post_id, 'title', $current_lang, $translated);
            return $translated;
        }
        
        // Return original title if translation failed
        return $title;
    }

    /**
     * Translate post content.
     *
     * @since    1.0.0
     * @param    string    $content    The post content.
     * @return   string    The translated content.
     */
    public function translate_content($content) {
        // Skip translation if in admin or not a singular view
        if (is_admin() || !is_singular()) {
            return $content;
        }
        
        $current_lang = $this->get_current_language();
        
        // Skip translation if current language is the same as default
        if ($current_lang === $this->default_language) {
            return $content;
        }
        
        $post_id = get_the_ID();
        $post_type = get_post_type($post_id);
        
        // Only translate if it's a VORTEX post type
        if (!in_array($post_type, array('vortex_artwork', 'vortex_post', 'page'))) {
            return $content;
        }
        
        // Determine entity type
        $entity_type = 'post';
        if ($post_type === 'vortex_artwork') {
            $entity_type = 'artwork';
        }
        
        // Get cached translation
        $translated = $this->get_cached_translation($entity_type, $post_id, 'content', $current_lang);
        
        if ($translated) {
            return $translated;
        }
        
        // Translate the content
        $translated = $this->translate_text($content, $this->default_language, $current_lang);
        
        if (!is_wp_error($translated)) {
            // Cache the translation
            $this->cache_translation($entity_type, $post_id, 'content', $current_lang, $translated);
            return $translated;
        }
        
        // Return original content if translation failed
        return $content;
    }

    /**
     * Translate post excerpt.
     *
     * @since    1.0.0
     * @param    string    $excerpt    The post excerpt.
     * @param    WP_Post   $post       The post object.
     * @return   string    The translated excerpt.
     */
    public function translate_excerpt($excerpt, $post = null) {
        // Skip translation if in admin
        if (is_admin()) {
            return $excerpt;
        }
        
        if (!$post) {
            $post = get_post();
        }
        
        if (!$post) {
            return $excerpt;
        }
        
        $current_lang = $this->get_current_language();
        
        // Skip translation if current language is the same as default
        if ($current_lang === $this->default_language) {
            return $excerpt;
        }
        
        $post_type = get_post_type($post);
        
        // Only translate if it's a VORTEX post type
        if (!in_array($post_type, array('vortex_artwork', 'vortex_post', 'page'))) {
            return $excerpt;
        }
        
        // Determine entity type
        $entity_type = 'post';
        if ($post_type === 'vortex_artwork') {
            $entity_type = 'artwork';
        }
        
        // Get cached translation
        $translated = $this->get_cached_translation($entity_type, $post->ID, 'excerpt', $current_lang);
        
        if ($translated) {
            return $translated;
        }
        
        // Translate the excerpt
        $translated = $this->translate_text($excerpt, $this->default_language, $current_lang);
        
        if (!is_wp_error($translated)) {
            // Cache the translation
            $this->cache_translation($entity_type, $post->ID, 'excerpt', $current_lang, $translated);
            return $translated;
        }
        
        // Return original excerpt if translation failed
        return $excerpt;
    }

    /**
     * Get translated entity (artwork, artist, post).
     *
     * @since    1.0.0
     * @param    string    $entity_type    Type of entity (artwork, artist, post, page).
     * @param    int       $entity_id      Entity ID.
     * @param    string    $target_lang    Target language code.
     * @return   array|WP_Error            Translated entity data or error.
     */
    public function get_translated_entity($entity_type, $entity_id, $target_lang) {
        // Skip translation if target language is the same as default
        if ($target_lang === $this->default_language) {
            return $this->get_entity_content($entity_type, $entity_id);
        }
        
        // Get original content
        $content = $this->get_entity_content($entity_type, $entity_id);
        
        if (empty($content)) {
            return new WP_Error('invalid_entity', __('Entity not found or has no content.', 'vortex-ai-marketplace'));
        }
        
        // Check if we have cached translations for all fields
        $translated_content = array();
        $missing_translations = false;
        
        foreach ($content as $field => $value) {
            if (is_string($value) && !empty($value)) {
                $translated = $this->get_cached_translation($entity_type, $entity_id, $field, $target_lang);
                
                if ($translated) {
                    $translated_content[$field] = $translated;
                } else {
                    $missing_translations = true;
                    $translated_content[$field] = $value; // Use original value for now
                }
            } else {
                $translated_content[$field] = $value; // Keep non-string values as is
            }
        }
        
        // If all translations were cached, return them
        if (!$missing_translations) {
            return $translated_content;
        }
        
        // Otherwise, translate missing fields
        foreach ($content as $field => $value) {
            if (is_string($value) && !empty($value) && !isset($translated_content[$field])) {
                $translated = $this->translate_text($value, $this->default_language, $target_lang);
                
                if (!is_wp_error($translated)) {
                    $translated_content[$field] = $translated;
                    // Cache the translation
                    $this->cache_translation($entity_type, $entity_id, $field, $target_lang, $translated);
                } else {
                    $translated_content[$field] = $value; // Use original value if translation fails
                }
            }
        }
        
        return $translated_content;
    }

    /**
     * Get entity content for translation.
     *
     * @since    1.0.0
     * @param    string    $entity_type    Type of entity (artwork, artist, post, page).
     * @param    int       $entity_id      Entity ID.
     * @return   array                     Entity content for translation.
     */
    private function get_entity_content($entity_type, $entity_id) {
        switch ($entity_type) {
            case 'artwork':
                return $this->get_artwork_content($entity_id);
                
            case 'artist':
                return $this->get_artist_content($entity_id);
                
            case 'post':
            case 'page':
                return $this->get_post_content($entity_id);
                
            default:
                return array();
        }
    }

    /**
     * Get artwork content for translation.
     *
     * @since    1.0.0
     * @param    int       $artwork_id     Artwork ID.
     * @return   array                     Artwork content for translation.
     */
    private function get_artwork_content($artwork_id) {
        global $wpdb;
        
        // Get artwork data from database
        $artwork_table = $wpdb->prefix . 'vortex_artworks';
        $artwork = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$artwork_table} WHERE artwork_id = %d",
            $artwork_id
        ), ARRAY_A);
        
        if (!$artwork) {
            return array();
        }
        
        // Get post ID associated with this artwork
        $post_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_vortex_artwork_id' AND meta_value = %d",
            $artwork_id
        ));
        
        $content = array(
            'title' => $artwork['title'],
            'description' => $artwork['description'],
            'short_description' => $artwork['short_description'],
            'artist_name' => $artwork['artist_name'],
        );
        
        // Add tags if available
        if ($post_id) {
            $tags = wp_get_post_terms($post_id, 'artwork_tag', array('fields' => 'names'));
            if (!is_wp_error($tags) && !empty($tags)) {
                $content['tags'] = implode(', ', $tags);
            }
        }
        
        return $content;
    }

    /**
     * Get artist content for translation.
     *
     * @since    1.0.0
     * @param    int       $artist_id      Artist ID.
     * @return   array                     Artist content for translation.
     */
    private function get_artist_content($artist_id) {
        global $wpdb;
        
        // Get artist data from database
        $artists_table = $wpdb->prefix . 'vortex_artists';
        $artist = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$artists_table} WHERE artist_id = %d",
            $artist_id
        ), ARRAY_A);
        
        if (!$artist) {
            return array();
        }
        
        $content = array(
            'display_name' => $artist['display_name'],
            'bio' => $artist['bio'],
            'specialties' => $artist['specialties'],
        );
        
        return $content;
    }

    /**
     * Get post content for translation.
     *
     * @since    1.0.0
     * @param    int       $post_id        Post ID.
     * @return   array                     Post content for translation.
     */
    private function get_post_content($post_id) {
        $post = get_post($post_id);
        
        if (!$post) {
            return array();
        }
        
        $content = array(
            'title' => $post->post_title,
            'content' => $post->post_content,
            'excerpt' => $post->post_excerpt,
        );
        
        return $content;
    }

    /**
     * Translate text using the configured translation service.
     *
     * @since    1.0.0
     * @param    string    $text           Text to translate.
     * @param    string    $source_lang    Source language code.
     * @param    string    $target_lang    Target language code.
     * @return   string|WP_Error           Translated text or error.
     */
    public function translate_text($text, $source_lang, $target_lang) {
        // Skip translation if target language is the same as source
        if ($target_lang === $source_lang) {
            return $text;
        }
        
        // Check cache first
        $cache_key = md5($text . $source_lang . $target_lang);
        $cached = get_transient('vortex_translation_' . $cache_key);
        
        if (false !== $cached) {
            return $cached;
        }
        
        // Choose translation method based on settings
        switch ($this->translation_service) {
            case 'google':
                $translated = $this->translate_with_google($text, $source_lang, $target_lang);
                break;
                
            case 'deepl':
                $translated = $this->translate_with_deepl($text, $source_lang, $target_lang);
                break;
                
            case 'microsoft':
                $translated = $this->translate_with_microsoft($text, $source_lang, $target_lang);
                break;
                
            case 'custom':
                $translated = apply_filters('vortex_custom_translation', $text, $source_lang, $target_lang);
                break;
                
            default:
                return new WP_Error('invalid_service', __('Invalid translation service.', 'vortex-ai-marketplace'));
        }
        
        if (!is_wp_error($translated)) {
            // Cache the translation
            set_transient('vortex_translation_' . $cache_key, $translated, $this->cache_expiration);
            return $translated;
        }
        
        return $translated;
    }

    /**
     * Translate text using Google Cloud Translation.
     *
     * @since    1.0.0
     * @param    string    $text           Text to translate.
     * @param    string    $source_lang    Source language code.
     * @param    string    $target_lang    Target language code.
     * @return   string|WP_Error           Translated text or error.
     */
    private function translate_with_google($text, $source_lang, $target_lang) {
        if (empty($this->api_key)) {
            return new WP_Error('missing_api_key', __('Google Cloud Translation API key is missing.', 'vortex-ai-marketplace'));
        }
        
        $api_url = 'https://translation.googleapis.com/language/translate/v2';
        $params = array(
            'q' => $text,
            'source' => $source_lang,
            'target' => $target_lang,
            'key' => $this->api_key,
            'format' => 'html',
        );
        
        $url = add_query_arg($params, $api_url);
        
        $response = wp_remote_get($url, array(
            'timeout' => 30,
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['data']['translations'][0]['translatedText'])) {
            return html_entity_decode($data['data']['translations'][0]['translatedText']);
        }
        
        return new WP_Error(
            'translation_failed', 
            __('Google translation failed.', 'vortex-ai-marketplace'),
            $data
        );
    }

    /**
     * Translate text using DeepL API.
     *
     * @since    1.0.0
     * @param    string    $text           Text to translate.
     * @param    string    $source_lang    Source language code.
     * @param    string    $target_lang    Target language code.
     * @return   string|WP_Error           Translated text or error.
     */
    private function translate_with_deepl($text, $source_lang, $target_lang) {
        if (empty($this->api_key)) {
            return new WP_Error('missing_api_key', __('DeepL API key is missing.', 'vortex-ai-marketplace'));
        }
        
        // Map language codes to DeepL format
        $deepl_languages = array(
            'en' => 'EN',
            'es' => 'ES',
            'fr' => 'FR',
            'de' => 'DE',
            'it' => 'IT',
            'ja' => 'JA',
            'zh' => 'ZH',
            'ru' => 'RU',
            'pt' => 'PT',
        );
        
        if (!isset($deepl_languages[$target_lang])) {
            return new WP_Error('unsupported_language', sprintf(__('DeepL does not support %s as a target language.', 'vortex-ai-marketplace'), $target_lang));
        }
        
        $api_url = 'https://api.deepl.com/v2/translate';
        
        $params = array(
            'auth_key' => $this->api_key,
            'text' => $text,
            'target_lang' => $deepl_languages[$target_lang],
        );
        
        if (isset($deepl_languages[$source_lang])) {
            $params['source_lang'] = $deepl_languages[$source_lang];
        }
        
        $response = wp_remote_post($api_url, array(
            'timeout' => 30,
            'body' => $params,
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['translations'][0]['text'])) {
            return $data['translations'][0]['text'];
        }
        
        return new WP_Error(
            'translation_failed', 
            __('DeepL translation failed.', 'vortex-ai-marketplace'),
            $data
        );
    }

    /**
     * Translate text using Microsoft Translator API.
     *
     * @since    1.0.0
     * @param    string    $text           
</rewritten_file> 