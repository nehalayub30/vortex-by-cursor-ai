<?php
/**
 * Handles theme compatibility for the plugin
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

/**
 * Theme compatibility class.
 *
 * Ensures the plugin works with any WordPress theme.
 *
 * @since      1.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_Theme_Compatibility {

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
     * Current theme information.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $theme_info    Information about the current theme.
     */
    private $theme_info;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version           The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        // Get current theme information
        $this->theme_info = wp_get_theme();
        
        // Register hooks
        add_action( 'after_setup_theme', array( $this, 'theme_setup' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_compatibility_styles' ), 999 );
        add_filter( 'body_class', array( $this, 'add_theme_body_classes' ) );
        add_filter( 'template_include', array( $this, 'maybe_use_plugin_template' ) );
        add_action( 'vortex_before_content', array( $this, 'add_theme_wrappers_start' ) );
        add_action( 'vortex_after_content', array( $this, 'add_theme_wrappers_end' ) );
    }

    /**
     * Set up theme-specific features after theme is loaded.
     *
     * @since    1.0.0
     */
    public function theme_setup() {
        // Add theme support for post thumbnails if not already supported
        if ( ! current_theme_supports( 'post-thumbnails' ) ) {
            add_theme_support( 'post-thumbnails' );
        }
        
        // Add theme support for custom logo if needed
        if ( ! current_theme_supports( 'custom-logo' ) ) {
            add_theme_support( 'custom-logo' );
        }
        
        // Ensure our custom post types support thumbnails
        add_post_type_support( 'vortex_artwork', 'thumbnail' );
        add_post_type_support( 'vortex_artist', 'thumbnail' );
        
        // Register image sizes that work well across themes
        add_image_size( 'vortex-artwork-thumbnail', 300, 300, true );
        add_image_size( 'vortex-artwork-medium', 600, 600, false );
        add_image_size( 'vortex-artist-thumbnail', 150, 150, true );
    }

    /**
     * Enqueue theme compatibility styles.
     *
     * @since    1.0.0
     */
    public function enqueue_compatibility_styles() {
        // Base compatibility CSS for all themes
        wp_enqueue_style(
            $this->plugin_name . '-theme-compatibility',
            plugin_dir_url( dirname( __FILE__ ) ) . 'public/css/vortex-theme-compatibility.css',
            array(),
            $this->version,
            'all'
        );
        
        // Theme-specific compatibility - check for known themes
        $theme_slug = $this->theme_info->get_template();
        
        // Check if we have specific compatibility CSS for this theme
        $theme_specific_css = plugin_dir_path( dirname( __FILE__ ) ) . 'public/css/theme-specific/' . $theme_slug . '.css';
        
        if ( file_exists( $theme_specific_css ) ) {
            wp_enqueue_style(
                $this->plugin_name . '-' . $theme_slug . '-compatibility',
                plugin_dir_url( dirname( __FILE__ ) ) . 'public/css/theme-specific/' . $theme_slug . '.css',
                array( $this->plugin_name . '-theme-compatibility' ),
                $this->version,
                'all'
            );
        }
        
        // Add inline CSS based on theme features
        $this->add_dynamic_compatibility_css();
    }

    /**
     * Add dynamic CSS for compatibility with current theme.
     *
     * @since    1.0.0
     */
    private function add_dynamic_compatibility_css() {
        $custom_css = '';
        
        // Get theme's content width
        global $content_width;
        if ( isset( $content_width ) && $content_width > 0 ) {
            $custom_css .= "
                .vortex-artwork-container {
                    max-width: {$content_width}px;
                    margin-left: auto;
                    margin-right: auto;
                }
                
                @media (max-width: {$content_width}px) {
                    .vortex-artwork-container {
                        max-width: 100%;
                        padding-left: 15px;
                        padding-right: 15px;
                    }
                }
            ";
        }
        
        // Add theme-specific adjustments based on theme slug
        $theme_slug = $this->theme_info->get_template();
        
        switch ( $theme_slug ) {
            case 'twentytwentyone':
                $custom_css .= "
                    .vortex-artwork-single .entry-content {
                        max-width: none;
                    }
                    .vortex-modal {
                        z-index: 99999;
                    }
                ";
                break;
                
            case 'twentytwentytwo':
                $custom_css .= "
                    .vortex-artwork-container .wp-block-post-content {
                        margin-top: 0;
                    }
                    .vortex-artwork-filter {
                        margin-bottom: var(--wp--style--block-gap);
                    }
                ";
                break;
                
            case 'astra':
                $custom_css .= "
                    .vortex-artwork-container {
                        margin-top: 2em;
                    }
                    body.single-vortex_artwork .site-content .ast-container,
                    body.single-vortex_artist .site-content .ast-container {
                        display: block;
                    }
                ";
                break;
                
            case 'generatepress':
                $custom_css .= "
                    .vortex-container {
                        margin-left: auto;
                        margin-right: auto;
                    }
                ";
                break;
                
            case 'oceanwp':
                $custom_css .= "
                    #main.vortex-main {
                        padding-top: 30px;
                    }
                    .vortex-container .entry-content-wrap {
                        padding: 0;
                    }
                ";
                break;
        }
        
        if ( ! empty( $custom_css ) ) {
            wp_add_inline_style( $this->plugin_name . '-theme-compatibility', $custom_css );
        }
    }

    /**
     * Add theme-specific body classes.
     *
     * @since    1.0.0
     * @param    array    $classes    Body classes.
     * @return   array                Modified body classes.
     */
    public function add_theme_body_classes( $classes ) {
        // Get theme slug
        $theme_slug = $this->theme_info->get_template();
        
        // Add theme-specific class
        $classes[] = 'vortex-theme-' . $theme_slug;
        
        // Add class for plugin pages
        if ( is_singular( 'vortex_artwork' ) ) {
            $classes[] = 'vortex-artwork-single';
        } elseif ( is_singular( 'vortex_artist' ) ) {
            $classes[] = 'vortex-artist-single';
        } elseif ( is_post_type_archive( 'vortex_artwork' ) ) {
            $classes[] = 'vortex-artwork-archive';
        } elseif ( is_post_type_archive( 'vortex_artist' ) ) {
            $classes[] = 'vortex-artist-archive';
        } elseif ( is_tax( 'vortex_artwork_category' ) || is_tax( 'vortex_artwork_tag' ) ) {
            $classes[] = 'vortex-artwork-taxonomy';
        }
        
        return $classes;
    }

    /**
     * Check if we should use plugin template fallbacks.
     *
     * @since    1.0.0
     * @param    string    $template    Template path.
     * @return   string                 Modified template path.
     */
    public function maybe_use_plugin_template( $template ) {
        // Get template file name
        $template_name = basename( $template );
        $template_path = VORTEX_PLUGIN_DIR . 'templates/';
        
        // Check for single artwork template
        if ( is_singular( 'vortex_artwork' ) ) {
            if ( file_exists( $template_path . 'single-vortex-artwork.php' ) ) {
                return $template_path . 'single-vortex-artwork.php';
            }
        }
        
        // Check for single artist template
        if ( is_singular( 'vortex_artist' ) ) {
            if ( file_exists( $template_path . 'single-vortex-artist.php' ) ) {
                return $template_path . 'single-vortex-artist.php';
            }
        }
        
        // Check for artwork archive template
        if ( is_post_type_archive( 'vortex_artwork' ) ) {
            if ( file_exists( $template_path . 'archive-vortex-artwork.php' ) ) {
                return $template_path . 'archive-vortex-artwork.php';
            }
        }
        
        // Check for artist archive template
        if ( is_post_type_archive( 'vortex_artist' ) ) {
            if ( file_exists( $template_path . 'archive-vortex-artist.php' ) ) {
                return $template_path . 'archive-vortex-artist.php';
            }
        }
        
        // Check for taxonomy templates
        if ( is_tax( 'vortex_artwork_category' ) ) {
            if ( file_exists( $template_path . 'taxonomy-vortex-artwork-category.php' ) ) {
                return $template_path . 'taxonomy-vortex-artwork-category.php';
            }
        }
        
        return $template;
    }

    /**
     * Add theme wrapper start for content.
     *
     * @since    1.0.0
     */
    public function add_theme_wrappers_start() {
        // Get theme slug
        $theme_slug = $this->theme_info->get_template();
        
        // Open theme-specific wrappers
        switch ( $theme_slug ) {
            case 'twentytwentyone':
                echo '<div class="entry-content">';
                break;
                
            case 'twentytwentytwo':
                echo '<div class="wp-block-post-content">';
                break;
                
            default:
                echo '<div class="vortex-content-wrapper">';
                break;
        }
    }

    /**
     * Add theme wrapper end for content.
     *
     * @since    1.0.0
     */
    public function add_theme_wrappers_end() {
        // Close theme-specific wrappers
        echo '</div>';
    }

    /**
     * Get template part with theme override support.
     *
     * @since    1.0.0
     * @param    string    $slug       Template slug.
     * @param    string    $name       Template name.
     * @param    array     $args       Template arguments.
     * @return   void
     */
    public static function get_template_part( $slug, $name = null, $args = array() ) {
        $templates = array();
        $name = (string) $name;
        
        if ( '' !== $name ) {
            $templates[] = "{$slug}-{$name}.php";
        }
        
        $templates[] = "{$slug}.php";
        
        // Allow theme override in theme/vortex-ai-marketplace/ directory
        $template = locate_template( array(
            "vortex-ai-marketplace/{$slug}-{$name}.php",
            "vortex-ai-marketplace/{$slug}.php"
        ) );
        
        // If not found in theme, use plugin template
        if ( ! $template ) {
            // Look in templates/ folder of the plugin
            $plugin_template_path = VORTEX_PLUGIN_DIR . "templates/{$slug}" . ( $name ? "-{$name}" : "" ) . ".php";
            
            if ( file_exists( $plugin_template_path ) ) {
                $template = $plugin_template_path;
            }
            
            // If still not found, look in public/partials/ folder
            if ( ! $template ) {
                $public_template_path = VORTEX_PLUGIN_DIR . "public/partials/{$slug}" . ( $name ? "-{$name}" : "" ) . ".php";
                
                if ( file_exists( $public_template_path ) ) {
                    $template = $public_template_path;
                }
            }
        }
        
        // Allow plugins/themes to override the template file
        $template = apply_filters( 'vortex_get_template_part', $template, $slug, $name, $args );
        
        if ( $template ) {
            // Extract args if provided
            if ( ! empty( $args ) && is_array( $args ) ) {
                extract( $args );
            }
            
            include $template;
        }
    }
} 