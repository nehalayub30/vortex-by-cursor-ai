<?php
/**
 * VORTEX AI Marketplace Template Loader
 *
 * Handles loading templates from either the theme or plugin
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/templates
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Get template part.
 *
 * @param string $slug Template slug.
 * @param string $name Template name (optional).
 * @param array  $args Additional arguments passed to the template (optional).
 */
function vortex_get_template_part($slug, $name = '', $args = array()) {
    $template = '';
    $name = (string) $name;
    
    // Look in yourtheme/vortex/slug-name.php and yourtheme/vortex/slug.php
    if ($name) {
        $template = locate_template(array("vortex/{$slug}-{$name}.php", "vortex-ai-marketplace/{$slug}-{$name}.php"));
    }
    
    // If template file doesn't exist, look in yourtheme/vortex/slug.php
    if (!$template && $name && file_exists(VORTEX_TEMPLATE_PATH . "{$slug}-{$name}.php")) {
        $template = VORTEX_TEMPLATE_PATH . "{$slug}-{$name}.php";
    }
    
    // If template file doesn't exist, look in yourtheme/vortex/slug.php
    if (!$template) {
        $template = locate_template(array("vortex/{$slug}.php", "vortex-ai-marketplace/{$slug}.php"));
    }
    
    // Get default slug-name.php
    if (!$template && file_exists(VORTEX_TEMPLATE_PATH . "{$slug}.php")) {
        $template = VORTEX_TEMPLATE_PATH . "{$slug}.php";
    }
    
    // Allow 3rd party plugins to filter template file from their plugin.
    $template = apply_filters('vortex_get_template_part', $template, $slug, $name);
    
    if ($template) {
        load_template($template, false, $args);
    }
}

/**
 * Get other templates passing attributes and including the file.
 *
 * @param string $template_name Template name.
 * @param array  $args          Arguments. (default: array).
 * @param string $template_path Template path. (default: '').
 * @param string $default_path  Default path. (default: '').
 */
function vortex_get_template($template_name, $args = array(), $template_path = '', $default_path = '') {
    if (!empty($args) && is_array($args)) {
        extract($args);
    }
    
    $located = vortex_locate_template($template_name, $template_path, $default_path);
    
    if (!file_exists($located)) {
        return;
    }
    
    // Allow 3rd party plugins to filter template file from their plugin.
    $located = apply_filters('vortex_get_template', $located, $template_name, $args, $template_path, $default_path);
    
    do_action('vortex_before_template_part', $template_name, $template_path, $located, $args);
    
    include $located;
    
    do_action('vortex_after_template_part', $template_name, $template_path, $located, $args);
}

/**
 * Locate a template and return the path for inclusion.
 *
 * This is the load order:
 *
 * yourtheme/vortex/$template_name
 * yourtheme/vortex-ai-marketplace/$template_name
 * $default_path/$template_name
 *
 * @param string $template_name Template name.
 * @param string $template_path Template path. (default: '').
 * @param string $default_path  Default path. (default: '').
 * @return string
 */
function vortex_locate_template($template_name, $template_path = '', $default_path = '') {
    if (!$template_path) {
        $template_path = 'vortex/';
    }
    
    if (!$default_path) {
        $default_path = VORTEX_TEMPLATE_PATH;
    }
    
    // Look within passed path within the theme - this is priority.
    $template = locate_template(
        array(
            trailingslashit($template_path) . $template_name,
            trailingslashit('vortex-ai-marketplace/') . $template_name,
        )
    );
    
    // Get default template.
    if (!$template) {
        $template = trailingslashit($default_path) . $template_name;
    }
    
    // Return what we found.
    return apply_filters('vortex_locate_template', $template, $template_name, $template_path);
} 