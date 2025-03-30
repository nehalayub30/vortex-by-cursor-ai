<?php
/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */
class Vortex_i18n {

    /**
     * The domain specified for this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $domain    The domain identifier for this plugin.
     */
    private $domain;

    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            $this->domain,
            false,
            dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
        );
    }

    /**
     * Set the domain equal to that of the specified domain.
     *
     * @since    1.0.0
     * @param    string    $domain    The domain that represents the locale of this plugin.
     */
    public function set_domain( $domain ) {
        $this->domain = $domain;
    }

    /**
     * Get the list of available languages.
     * 
     * @since    1.0.0
     * @return   array     Array of available languages with their native names.
     */
    public function get_available_languages() {
        $languages = array(
            'en_US' => 'English (US)',
            'es_ES' => 'Español',
            'fr_FR' => 'Français',
            'de_DE' => 'Deutsch',
            'it_IT' => 'Italiano',
            'ja'    => '日本語',
            'zh_CN' => '简体中文',
            'ar'    => 'العربية'
        );
        
        return $languages;
    }

    /**
     * Get the current language.
     * 
     * @since    1.0.0
     * @return   string    The current language code.
     */
    public function get_current_language() {
        return get_locale();
    }

    /**
     * Check if a language is RTL.
     * 
     * @since    1.0.0
     * @param    string    $locale    The locale to check.
     * @return   boolean   True if RTL, false otherwise.
     */
    public function is_rtl($locale = '') {
        if (empty($locale)) {
            $locale = $this->get_current_language();
        }
        
        $rtl_locales = array('ar', 'he_IL', 'fa_IR');
        
        return in_array($locale, $rtl_locales);
    }
} 