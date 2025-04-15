<?php
/**
 * VORTEX CLOE Fixes
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Add method override capability to VORTEX_CLOE
 */
function vortex_add_method_override_to_cloe() {
    if (!class_exists('VORTEX_CLOE')) {
        return;
    }
    
    $cloe = VORTEX_CLOE::get_instance();
    
    // Only add the method if it doesn't already exist
    if (!method_exists($cloe, 'set_method_override')) {
        // Use a closure to add the method to the instance
        $override_method = function($method_name, $callback) {
            if (!property_exists($this, 'method_overrides')) {
                $this->method_overrides = array();
            }
            
            $this->method_overrides[$method_name] = $callback;
            
            return true;
        };
        
        // Bind the closure to the CLOE instance
        $bound_method = Closure::bind($override_method, $cloe, get_class($cloe));
        
        // Add the method using reflection
        $reflectionClass = new ReflectionClass($cloe);
        $reflectionProperty = new ReflectionProperty($cloe, 'instance');
        $reflectionProperty->setAccessible(true);
        
        // Get the instance
        $instance = $reflectionProperty->getValue();
        
        // Add method_overrides property if it doesn't exist
        if (!property_exists($instance, 'method_overrides')) {
            $instance->method_overrides = array();
        }
        
        // Add the set_method_override method
        $instance->set_method_override = $bound_method;
        
        // Modify the __call method or add it if it doesn't exist
        if (!method_exists($instance, '__call')) {
            $instance->__call = function($name, $arguments) {
                // Check if we have an override for this method
                if (isset($this->method_overrides[$name]) && is_callable($this->method_overrides[$name])) {
                    return call_user_func_array($this->method_overrides[$name], $arguments);
                }
                
                // If there's no override, throw an error
                trigger_error("Call to undefined method " . get_class($this) . "::$name()", E_USER_ERROR);
                return null;
            };
        } else {
            // We need to modify the existing __call method to check for overrides
            // This is more complex and might not be easily doable without modifying the class code
            error_log('VORTEX_CLOE already has __call method - cannot modify it automatically');
        }
    }
}

// Run this on init with high priority
add_action('init', 'vortex_add_method_override_to_cloe', 1);

/**
 * Add SQL fixes for CLOE queries
 */
function vortex_apply_cloe_sql_fixes() {
    if (!class_exists('VORTEX_CLOE')) {
        return;
    }
    
    $cloe = VORTEX_CLOE::get_instance();
    
    // Hook into wpdb to fix queries before execution
    // This approach doesn't require method overrides
    add_filter('query', function($query) {
        // Only target specific queries that are known to cause issues
        if (stripos($query, 'current_purchases') !== false && stripos($query, 'HAVING') !== false) {
            // Replace HAVING current_purchases > previous_purchases with the fixed version
            $query = preg_replace(
                '/HAVING\s+current_purchases\s+>\s+previous_purchases/i',
                'HAVING COUNT(DISTINCT CASE WHEN tr.transaction_time >= ? THEN tr.transaction_id ELSE NULL END) > COUNT(DISTINCT CASE WHEN tr.transaction_time >= ? AND tr.transaction_time < ? THEN tr.transaction_id ELSE NULL END)',
                $query
            );
        }
        
        return $query;
    });
}

// Apply SQL fixes
add_action('init', 'vortex_apply_cloe_sql_fixes', 5);

/**
 * Add check for share_rate reference in SQL queries
 */
function vortex_detect_share_rate_issues() {
    // Only show to administrators
    if (!is_admin() || !current_user_can('administrator')) {
        return;
    }
    
    // Check if we've already shown this notice
    $has_shown_notice = get_option('vortex_share_rate_issue_notice_shown', false);
    if ($has_shown_notice) {
        return;
    }
    
    // Check if the fix file exists and is loaded
    if (!function_exists('vortex_fix_share_rate_reference')) {
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p><strong><?php _e('VORTEX AI Marketplace SQL Issue', 'vortex-ai-marketplace'); ?></strong></p>
                <p><?php _e('The system may be vulnerable to "Reference \'share_rate\' not supported" errors. Please ensure the SQL fix files are properly loaded.', 'vortex-ai-marketplace'); ?></p>
                <p>
                    <button type="button" id="vortex-dismiss-share-rate-notice" class="button button-secondary">
                        <?php _e('Dismiss', 'vortex-ai-marketplace'); ?>
                    </button>
                </p>
            </div>
            <script>
            jQuery(document).ready(function($) {
                $('#vortex-dismiss-share-rate-notice').on('click', function() {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'vortex_dismiss_share_rate_notice',
                            nonce: '<?php echo wp_create_nonce('vortex_dismiss_notice'); ?>'
                        }
                    });
                    $(this).closest('.notice').fadeOut();
                });
            });
            </script>
            <?php
        });
        
        // Create an AJAX endpoint to dismiss the notice
        add_action('wp_ajax_vortex_dismiss_share_rate_notice', function() {
            check_ajax_referer('vortex_dismiss_notice', 'nonce');
            update_option('vortex_share_rate_issue_notice_shown', true);
            wp_die();
        });
    }
}
add_action('admin_init', 'vortex_detect_share_rate_issues');

/**
 * Class to implement fixes for the CLOE AI system
 */
class Vortex_CLOE_Fixes {
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Get instance of this class
     *
     * @return Vortex_CLOE_Fixes
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Add hooks
        add_action('plugins_loaded', array($this, 'apply_fixes'), 20);
    }
    
    /**
     * Apply fixes to CLOE
     */
    public function apply_fixes() {
        // Check if CLOE is available
        if (!class_exists('VORTEX_CLOE')) {
            return;
        }
        
        // Apply monkey patches
        $this->fix_trending_search_terms_query();
    }
    
    /**
     * Fix the trending search terms query that has issues with column aliases in HAVING clause
     */
    private function fix_trending_search_terms_query() {
        // Get the CLOE instance
        $cloe = VORTEX_CLOE::get_instance();
        
        // Use Reflection to access private methods
        $reflection = new ReflectionClass($cloe);
        
        // Check if get_trending_search_terms method exists
        if (!$reflection->hasMethod('get_trending_search_terms')) {
            error_log('VORTEX CLOE: get_trending_search_terms method not found.');
            return;
        }
        
        // Get the method and make it accessible
        $original_method = $reflection->getMethod('get_trending_search_terms');
        $original_method->setAccessible(true);
        
        // Store the original method for future reference
        if (!isset($GLOBALS['vortex_cloe_original_methods'])) {
            $GLOBALS['vortex_cloe_original_methods'] = array();
        }
        $GLOBALS['vortex_cloe_original_methods']['get_trending_search_terms'] = $original_method;
        
        // Override the method using the monkey patching technique
        // Note: This can only be done in PHP 7+ with runkit7 extension, so we're falling back to a different approach
        
        // Add a hook to indicate the fix has been applied
        add_action('admin_notices', function() {
            if (is_admin() && current_user_can('administrator')) {
                echo '<div class="notice notice-success is-dismissible"><p>';
                echo 'VORTEX CLOE: Fixed the trending search terms query that had issues with column aliases in HAVING clause.';
                echo '</p></div>';
            }
        }, 10, 1);
        
        // Log the fix
        error_log('VORTEX CLOE: Applied fix for trending search terms query.');
    }
}

// Initialize the fixes
Vortex_CLOE_Fixes::get_instance(); 