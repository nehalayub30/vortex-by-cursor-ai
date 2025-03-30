<?php
/**
 * The Language Widget functionality.
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/widgets
 */

/**
 * The Language Widget functionality.
 *
 * Displays language selector for multilingual sites.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/widgets
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_Language_Widget extends WP_Widget {

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        parent::__construct(
            'vortex_language_widget', // Base ID
            __( 'VORTEX Language Selector', 'vortex-ai-marketplace' ), // Name
            array(
                'description' => __( 'Display language selector for multilingual sites.', 'vortex-ai-marketplace' ),
                'classname'   => 'vortex-language-widget',
            )
        );

        // Register widget
        add_action( 'widgets_init', array( $this, 'register_widget' ) );
        
        // Load widget specific scripts and styles
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    }

    /**
     * Register the widget with WordPress.
     *
     * @since    1.0.0
     */
    public function register_widget() {
        register_widget( 'Vortex_Language_Widget' );
    }

    /**
     * Enqueue widget specific scripts and styles.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // Only load if widget is active
        if ( is_active_widget( false, false, $this->id_base, true ) ) {
            wp_enqueue_style(
                'vortex-language-widget',
                plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'public/css/vortex-language.css',
                array(),
                VORTEX_VERSION,
                'all'
            );
            
            wp_enqueue_script(
                'vortex-language-widget',
                plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'public/js/vortex-language.js',
                array( 'jquery' ),
                VORTEX_VERSION,
                true
            );
        }
    }

    /**
     * Front-end display of widget.
     *
     * @since    1.0.0
     * @param    array    $args        Widget arguments.
     * @param    array    $instance    Saved values from database.
     */
    public function widget( $args, $instance ) {
        echo $args['before_widget'];

        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
        }

        // Get widget settings
        $display_style = ! empty( $instance['display_style'] ) ? $instance['display_style'] : 'dropdown';
        $show_flags = ! empty( $instance['show_flags'] ) ? (bool) $instance['show_flags'] : true;
        $show_language_name = ! empty( $instance['show_language_name'] ) ? (bool) $instance['show_language_name'] : true;
        $show_language_code = ! empty( $instance['show_language_code'] ) ? (bool) $instance['show_language_code'] : false;
        
        // Check if WPML or Polylang is active
        $languages = $this->get_available_languages();
        
        if ( empty( $languages ) ) {
            // If no multilingual plugin is active, display message only in admin
            if ( is_admin() ) {
                echo '<p>' . esc_html__( 'No multilingual plugin detected. This widget supports WPML and Polylang.', 'vortex-ai-marketplace' ) . '</p>';
            }
            echo $args['after_widget'];
            return;
        }
        
        // Widget container
        $container_class = 'vortex-language-container style-' . esc_attr( $display_style );
        echo '<div class="' . esc_attr( $container_class ) . '">';
        
        // Render language switcher based on style
        switch ( $display_style ) {
            case 'dropdown':
                $this->render_dropdown( $languages, $show_flags, $show_language_name, $show_language_code );
                break;
                
            case 'list':
                $this->render_list( $languages, $show_flags, $show_language_name, $show_language_code );
                break;
                
            case 'flags':
                $this->render_flags( $languages );
                break;
                
            default:
                $this->render_dropdown( $languages, $show_flags, $show_language_name, $show_language_code );
                break;
        }
        
        echo '</div>'; // End container
        
        echo $args['after_widget'];
    }

    /**
     * Render language switcher as dropdown.
     *
     * @since    1.0.0
     * @param    array    $languages           Available languages.
     * @param    bool     $show_flags          Whether to show flags.
     * @param    bool     $show_language_name  Whether to show language names.
     * @param    bool     $show_language_code  Whether to show language codes.
     */
    private function render_dropdown( $languages, $show_flags, $show_language_name, $show_language_code ) {
        $current_language = $this->get_current_language();
        
        echo '<div class="vortex-language-dropdown">';
        
        // Current language display
        echo '<div class="vortex-current-language">';
        
        if ( $show_flags && ! empty( $languages[$current_language]['flag'] ) ) {
            echo '<img src="' . esc_url( $languages[$current_language]['flag'] ) . '" alt="' . esc_attr( $languages[$current_language]['name'] ) . '" class="vortex-language-flag" />';
        }
        
        echo '<span class="vortex-selected-language">';
        
        if ( $show_language_name ) {
            echo esc_html( $languages[$current_language]['name'] );
        }
        
        if ( $show_language_code && $show_language_name ) {
            echo ' (' . esc_html( strtoupper( $current_language ) ) . ')';
        } elseif ( $show_language_code ) {
            echo esc_html( strtoupper( $current_language ) );
        }
        
        echo '</span>';
        
        echo '<span class="vortex-dropdown-arrow"></span>';
        echo '</div>';
        
        // Languages list
        echo '<ul class="vortex-language-list">';
        
        foreach ( $languages as $code => $language ) {
            $active_class = ( $code === $current_language ) ? 'active' : '';
            
            echo '<li class="vortex-language-item ' . esc_attr( $active_class ) . '">';
            echo '<a href="' . esc_url( $language['url'] ) . '" lang="' . esc_attr( $code ) . '">';
            
            if ( $show_flags && ! empty( $language['flag'] ) ) {
                echo '<img src="' . esc_url( $language['flag'] ) . '" alt="' . esc_attr( $language['name'] ) . '" class="vortex-language-flag" />';
            }
            
            if ( $show_language_name ) {
                echo '<span class="vortex-language-name">' . esc_html( $language['name'] ) . '</span>';
            }
            
            if ( $show_language_code && $show_language_name ) {
                echo ' <span class="vortex-language-code">(' . esc_html( strtoupper( $code ) ) . ')</span>';
            } elseif ( $show_language_code ) {
                echo '<span class="vortex-language-code">' . esc_html( strtoupper( $code ) ) . '</span>';
            }
            
            echo '</a>';
            echo '</li>';
        }
        
        echo '</ul>';
        
        echo '</div>';
    }

    /**
     * Render language switcher as list.
     *
     * @since    1.0.0
     * @param    array    $languages           Available languages.
     * @param    bool     $show_flags          Whether to show flags.
     * @param    bool     $show_language_name  Whether to show language names.
     * @param    bool     $show_language_code  Whether to show language codes.
     */
    private function render_list( $languages, $show_flags, $show_language_name, $show_language_code ) {
        $current_language = $this->get_current_language();
        
        echo '<ul class="vortex-language-list-horizontal">';
        
        foreach ( $languages as $code => $language ) {
            $active_class = ( $code === $current_language ) ? 'active' : '';
            
            echo '<li class="vortex-language-item ' . esc_attr( $active_class ) . '">';
            
            if ( $code === $current_language ) {
                echo '<span class="vortex-current-language">';
            } else {
                echo '<a href="' . esc_url( $language['url'] ) . '" lang="' . esc_attr( $code ) . '">';
            }
            
            if ( $show_flags && ! empty( $language['flag'] ) ) {
                echo '<img src="' . esc_url( $language['flag'] ) . '" alt="' . esc_attr( $language['name'] ) . '" class="vortex-language-flag" />';
            }
            
            if ( $show_language_name ) {
                echo '<span class="vortex-language-name">' . esc_html( $language['name'] ) . '</span>';
            }
            
            if ( $show_language_code && $show_language_name ) {
                echo ' <span class="vortex-language-code">(' . esc_html( strtoupper( $code ) ) . ')</span>';
            } elseif ( $show_language_code ) {
                echo '<span class="vortex-language-code">' . esc_html( strtoupper( $code ) ) . '</span>';
            }
            
            if ( $code === $current_language ) {
                echo '</span>';
            } else {
                echo '</a>';
            }
            
            echo '</li>';
        }
        
        echo '</ul>';
    }

    /**
     * Render language switcher as flags.
     *
     * @since    1.0.0
     * @param    array    $languages    Available languages.
     */
    private function render_flags( $languages ) {
        $current_language = $this->get_current_language();
        
        echo '<div class="vortex-language-flags">';
        
        foreach ( $languages as $code => $language ) {
            $active_class = ( $code === $current_language ) ? 'active' : '';
            
            echo '<div class="vortex-language-flag-item ' . esc_attr( $active_class ) . '">';
            
            if ( $code === $current_language ) {
                echo '<span class="vortex-current-flag" title="' . esc_attr( $language['name'] ) . '">';
            } else {
                echo '<a href="' . esc_url( $language['url'] ) . '" lang="' . esc_attr( $code ) . '" title="' . esc_attr( $language['name'] ) . '">';
            }
            
            if ( ! empty( $language['flag'] ) ) {
                echo '<img src="' . esc_url( $language['flag'] ) . '" alt="' . esc_attr( $language['name'] ) . '" />';
            } else {
                echo '<span class="vortex-language-code">' . esc_html( strtoupper( $code ) ) . '</span>';
            }
            
            if ( $code === $current_language ) {
                echo '</span>';
            } else {
                echo '</a>';
            }
            
            echo '</div>';
        }
        
        echo '</div>';
    }

    /**
     * Get available languages from WPML or Polylang.
     *
     * @since    1.0.0
     * @return   array    Available languages.
     */
    private function get_available_languages() {
        $languages = array();
        
        // Check for WPML
        if ( function_exists( 'icl_get_languages' ) ) {
            $wpml_languages = icl_get_languages( 'skip_missing=0' );
            
            if ( ! empty( $wpml_languages ) ) {
                foreach ( $wpml_languages as $code => $language ) {
                    $languages[$code] = array(
                        'name' => $language['native_name'],
                        'url'  => $language['url'],
                        'flag' => $language['country_flag_url'],
                    );
                }
            }
            
            return $languages;
        }
        
        // Check for Polylang
        if ( function_exists( 'pll_the_languages' ) && function_exists( 'pll_languages_list' ) ) {
            $pll_languages = pll_languages_list( array( 'fields' => '' ) );
            
            if ( ! empty( $pll_languages ) ) {
                foreach ( $pll_languages as $language ) {
                    $languages[$language->slug] = array(
                        'name' => $language->name,
                        'url'  => $language->home_url,
                        'flag' => $language->flag_url,
                    );
                }
            }
            
            return $languages;
        }
        
        // For development environments - generate dummy data if no multilingual plugin is active
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            $languages = array(
                'en' => array(
                    'name' => 'English',
                    'url'  => home_url( '/' ),
                    'flag' => plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'public/images/flags/en.png',
                ),
                'es' => array(
                    'name' => 'Español',
                    'url'  => home_url( '/es/' ),
                    'flag' => plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'public/images/flags/es.png',
                ),
                'fr' => array(
                    'name' => 'Français',
                    'url'  => home_url( '/fr/' ),
                    'flag' => plugin_dir_url( dirname( dirname( __FILE__ ) ) ) . 'public/images/flags/fr.png',
                ),
            );
        }
        
        return $languages;
    }

    /**
     * Get current language code from WPML or Polylang.
     *
     * @since    1.0.0
     * @return   string    Current language code.
     */
    private function get_current_language() {
        // Check for WPML
        if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
            return ICL_LANGUAGE_CODE;
        }
        
        // Check for Polylang
        if ( function_exists( 'pll_current_language' ) ) {
            return pll_current_language();
        }
        
        // Default
        return 'en';
    }

    /**
     * Back-end widget form.
     *
     * @since    1.0.0
     * @param    array    $instance    Previously saved values from database.
     * @return   void
     */
    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Language', 'vortex-ai-marketplace' );
        $display_style = ! empty( $instance['display_style'] ) ? $instance['display_style'] : 'dropdown';
        $show_flags = isset( $instance['show_flags'] ) ? (bool) $instance['show_flags'] : true;
        $show_language_name = isset( $instance['show_language_name'] ) ? (bool) $instance['show_language_name'] : true;
        $show_language_code = isset( $instance['show_language_code'] ) ? (bool) $instance['show_language_code'] : false;
        ?>
        
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
                <?php esc_html_e( 'Title:', 'vortex-ai-marketplace' ); ?>
            </label>
            <input 
                class="widefat" 
                id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" 
                name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" 
                type="text" 
                value="<?php echo esc_attr( $title ); ?>"
            >
        </p>
        
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'display_style' ) ); ?>">
                <?php esc_html_e( 'Display Style:', 'vortex-ai-marketplace' ); ?>
            </label>
            <select 
                id="<?php echo esc_attr( $this->get_field_id( 'display_style' ) ); ?>" 
                name="<?php echo esc_attr( $this->get_field_name( 'display_style' ) ); ?>"
            >
                <option value="dropdown" <?php selected( $display_style, 'dropdown' ); ?>>
                    <?php esc_html_e( 'Dropdown', 'vortex-ai-marketplace' ); ?>
                </option>
                <option value="list" <?php selected( $display_style, 'list' ); ?>>
                    <?php esc_html_e( 'List', 'vortex-ai-marketplace' ); ?>
                </option>
                <option value="flags" <?php selected( $display_style, 'flags' ); ?>>
                    <?php esc_html_e( 'Flags', 'vortex-ai-marketplace' ); ?>
                </option>
            </select>
        </p>
        
        <p>
            <input 
                type="checkbox" 
                class="checkbox" 
                id="<?php echo esc_attr( $this->get_field_id( 'show_flags' ) ); ?>" 
                name="<?php echo esc_attr( $this->get_field_name( 'show_flags' ) ); ?>"
                <?php checked( $show_flags ); ?> 
            >
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_flags' ) ); ?>">
                <?php esc_html_e( 'Show flags', 'vortex-ai-marketplace' ); ?>
            </label>
        </p>
        
        <p>
            <input 
                type="checkbox" 
                class="checkbox" 
                id="<?php echo esc_attr( $this->get_field_id( 'show_language_name' ) ); ?>" 
                name="<?php echo esc_attr( $this->get_field_name( 'show_language_name' ) ); ?>"
                <?php checked( $show_language_name ); ?> 
            >
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_language_name' ) ); ?>">
                <?php esc_html_e( 'Show language name', 'vortex-ai-marketplace' ); ?>
            </label>
        </p>
        
        <p>
            <input 
                type="checkbox" 
                class="checkbox" 
                id="<?php echo esc_attr( $this->get_field_id( 'show_language_code' ) ); ?>" 
                name="<?php echo esc_attr( $this->get_field_name( 'show_language_code' ) ); ?>"
                <?php checked( $show_language_code ); ?> 
            >
            <label for="<?php echo esc_attr( $this->get_field_id( 'show_language_code' ) ); ?>">
                <?php esc_html_e( 'Show language code', 'vortex-ai-marketplace' ); ?>
            </label>
        </p>
        <?php
    }

    /**
     * Sanitize widget form values as they are saved.
     *
     * @since    1.0.0
     * @param    array    $new_instance    Values just sent to be saved.
     * @param    array    $old_instance    Previously saved values from database.
     * @return   array                     Updated safe values to be saved.
     */
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        
        $instance['title'] = ! empty( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '';
        $instance['display_style'] = ! empty( $new_instance['display_style'] ) ? sanitize_text_field( $new_instance['display_style'] ) : 'dropdown';
        $instance['show_flags'] = ! empty( $new_instance['show_flags'] ) ? 1 : 0;
        $instance['show_language_name'] = ! empty( $new_instance['show_language_name'] ) ? 1 : 0;
        $instance['show_language_code'] = ! empty( $new_instance['show_language_code'] ) ? 1 : 0;
        
        return $instance;
    }
} 