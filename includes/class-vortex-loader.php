<?php
/**
 * Register all actions and filters for the plugin
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

/**
 * Register all actions and filters for the plugin.
 *
 * Maintain a list of all hooks that are registered throughout
 * the plugin, and register them with the WordPress API. Call the
 * run function to execute the list of actions and filters.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_Loader {

    /**
     * The array of actions registered with WordPress.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $actions    The actions registered with WordPress to fire when the plugin loads.
     */
    protected $actions;

    /**
     * The array of filters registered with WordPress.
     *
     * @since    1.0.0
     * @access   protected
     * @var      array    $filters    The filters registered with WordPress to fire when the plugin loads.
     */
    protected $filters;

    /**
     * The array of shortcodes registered with WordPress.
     *
     * @since    
     */
    protected $shortcodes;

    /**
     * The theme compatibility instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Vortex_Theme_Compatibility    $theme_compatibility    Ensures theme compatibility.
     */
    protected $theme_compatibility;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->set_theme_compatibility();
    }

    /**
     * Initialize theme compatibility.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_theme_compatibility() {
        $this->theme_compatibility = new Vortex_Theme_Compatibility( $this->get_plugin_name(), $this->get_version() );
    }

    /**
     * Add a new action to the collection of actions.
     *
     * @since    1.0.0
     * @param    string    $hook          The name of the WordPress action that is being registered.
     * @param    object    $component     A reference to the instance of the object on which the action is defined.
     * @param    string    $callback      The name of the function definition on the $component.
     * @param    int       $priority      Optional. The priority at which the function should be fired. Default is 10.
     * @param    int       $accepted_args Optional. The number of arguments that should be passed to the $callback. Default is 1.
     */
    public function add_action( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
        $this->actions = $this->add_to_collection( $this->actions, $hook, $component, $callback, $priority, $accepted_args );
    }

    /**
     * Add a new filter to the collection of filters.
     *
     * @since    1.0.0
     * @param    string    $hook          The name of the WordPress filter that is being registered.
     * @param    object    $component     A reference to the instance of the object on which the filter is defined.
     * @param    string    $callback      The name of the function definition on the $component.
     * @param    int       $priority      Optional. The priority at which the function should be fired. Default is 10.
     * @param    int       $accepted_args Optional. The number of arguments that should be passed to the $callback. Default is 1.
     */
    public function add_filter( $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
        $this->filters = $this->add_to_collection( $this->filters, $hook, $component, $callback, $priority, $accepted_args );
    }

    /**
     * Add a new shortcode to the collection of shortcodes.
     *
     * @since    1.0.0
     * @param    string    $hook          The name of the WordPress shortcode that is being registered.
     * @param    object    $component     A reference to the instance of the object on which the shortcode is defined.
     * @param    string    $callback      The name of the function definition on the $component.
     */
    public function add_shortcode( $hook, $component, $callback ) {
        $this->shortcodes = $this->add_to_collection( $this->shortcodes, $hook, $component, $callback );
    }

    /**
     * A utility function that is used to register the actions and hooks into a single
     * collection.
     *
     * @since    1.0.0
     * @access   private
     * @param    array     $collection    The collection of actions, filters, or shortcodes.
     * @param    string    $hook          The name of the WordPress action or filter.
     * @param    object    $component     A reference to the instance of the object on which the action or filter is defined.
     * @param    string    $callback      The name of the function definition on the $component.
     * @param    int       $priority      Optional. The priority at which the function should be fired. Default is 10.
     * @param    int       $accepted_args Optional. The number of arguments that should be passed to the $callback. Default is 1.
     * @return   array                      The modified collection of actions, filters, or shortcodes.
     */
    private function add_to_collection( $collection, $hook, $component, $callback, $priority = 10, $accepted_args = 1 ) {
        $collection[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );

        return $collection;
    }

    /**
     * Register the actions and hooks into the WordPress core system.
     *
     * @since    1.0.0
     */
    public function run() {
        foreach ( $this->actions as $hook ) {
            add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
        }

        foreach ( $this->filters as $hook ) {
            add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
        }

        foreach ( $this->shortcodes as $hook ) {
            add_shortcode( $hook['hook'], array( $hook['component'], $hook['callback'] ) );
        }
    }
} 