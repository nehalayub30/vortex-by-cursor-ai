<?php
/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 * @author     Marianne Nems <Marianne@VortexArtec.com>
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
     * The path to the language files.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_rel_path    Path relative to the plugin.
     */
    private $plugin_rel_path;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->domain = 'vortex-ai-marketplace';
        $this->plugin_rel_path = 'languages';
    }

    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            $this->domain,
            false,
            dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/' . $this->plugin_rel_path
        );
    }

    /**
     * Register language files location.
     *
     * @since    1.0.0
     */
    public function register_languages() {
        // Add filter to localize the plugin
        add_filter( 'plugin_locale', array( $this, 'plugin_locale' ), 10, 2 );
        
        // Register translations directory for admin
        if ( is_admin() ) {
            load_textdomain(
                $this->domain,
                WP_LANG_DIR . '/plugins/' . $this->domain . '-' . get_locale() . '.mo'
            );
        }
    }

    /**
     * Filter to override plugin locale.
     *
     * @since    1.0.0
     * @param    string    $locale    Current locale.
     * @param    string    $domain    Text domain.
     * @return   string               Modified locale if needed.
     */
    public function plugin_locale( $locale, $domain ) {
        // Check if this is our plugin
        if ( $domain === $this->domain ) {
            // Allow custom locale override through user settings
            $user_locale = get_user_meta( get_current_user_id(), 'vortex_preferred_language', true );
            if ( ! empty( $user_locale ) ) {
                return $user_locale;
            }
            
            // Allow site-wide locale override through option
            $site_locale = get_option( 'vortex_site_language', '' );
            if ( ! empty( $site_locale ) ) {
                return $site_locale;
            }
        }
        
        return $locale;
    }

    /**
     * Get the plugin text domain.
     *
     * @since    1.0.0
     * @return   string    The text domain.
     */
    public function get_domain() {
        return $this->domain;
    }

    /**
     * Get supported languages for the plugin.
     *
     * @since    1.0.0
     * @return   array     Array of supported languages.
     */
    public function get_supported_languages() {
        return array(
            'en_US' => __( 'English (US)', 'vortex-ai-marketplace' ),
            'es_ES' => __( 'Spanish (Spain)', 'vortex-ai-marketplace' ),
            'fr_FR' => __( 'French (France)', 'vortex-ai-marketplace' ),
            'de_DE' => __( 'German', 'vortex-ai-marketplace' ),
            'it_IT' => __( 'Italian', 'vortex-ai-marketplace' ),
            'ja'    => __( 'Japanese', 'vortex-ai-marketplace' ),
            'zh_CN' => __( 'Chinese (Simplified)', 'vortex-ai-marketplace' ),
        );
    }
} 