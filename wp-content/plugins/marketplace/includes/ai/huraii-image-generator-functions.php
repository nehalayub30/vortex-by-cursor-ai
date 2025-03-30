<?php
/**
 * HURAII Image Generator Functions
 * 
 * Additional functions and handlers for the HURAII Image Generator
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register settings
 */
function vortex_huraii_image_generator_register_settings() {
    register_setting('vortex_huraii_image_generator', 'vortex_huraii_api_key');
    register_setting('vortex_huraii_image_generator', 'vortex_huraii_default_style', array(
        'type' => 'string',
        'default' => 'realistic'
    ));
    register_setting('vortex_huraii_image_generator', 'vortex_huraii_save_history', array(
        'type' => 'boolean',
        'default' => true
    ));
    register_setting('vortex_huraii_image_generator', 'vortex_huraii_history_days', array(
        'type' => 'integer',
        'default' => 30
    ));
}
add_action('admin_init', 'vortex_huraii_image_generator_register_settings');

/**
 * Create database tables AJAX handler
 */
function vortex_create_image_tables_ajax() {
    check_ajax_referer('vortex_image_generator', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'vortex_generated_images';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            prompt text NOT NULL,
            image_id varchar(255) NOT NULL,
            image_url text NOT NULL,
            width int(11) NOT NULL,
            height int(11) NOT NULL,
            style varchar(50) NOT NULL,
            upscaled tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY image_id (image_id),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
            wp_send_json_success(array('message' => 'Tables created successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to create tables'));
        }
    } else {
        wp_send_json_success(array('message' => 'Tables already exist'));
    }
}
add_action('wp_ajax_vortex_create_image_tables', 'vortex_create_image_tables_ajax');

/**
 * Add dashboard widget for image generation
 */
function vortex_huraii_register_dashboard_widget() {
    if (current_user_can('edit_posts')) {
        wp_add_dashboard_widget(
            'vortex_huraii_image_widget',
            'HURAII Image Generator',
            'vortex_huraii_dashboard_widget'
        );
    }
}
add_action('wp_dashboard_setup', 'vortex_huraii_register_dashboard_widget');

/**
 * Display dashboard widget
 */
function vortex_huraii_dashboard_widget() {
    // Ensure required styles and scripts are loaded
    wp_enqueue_style('vortex-dashboard-image-generator', plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/dashboard-image-generator.css', array(), '1.1.0');
    wp_enqueue_script('vortex-dashboard-image-generator', plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/dashboard-image-generator.js', array('jquery'), '1.1.0', true);
    
    wp_localize_script('vortex-dashboard-image-generator', 'vortexDashboardImageGenerator', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('vortex_image_generator'),
        'i18n' => array(
            'generating' => __('Generating...', 'vortex-marketplace'),
            'error' => __('Error:', 'vortex-marketplace')
        )
    ));
    
    ?>
    <div class="vortex-dashboard-image-generator">
        <div class="quick-generate">
            <textarea placeholder="<?php esc_attr_e('Describe the image you want to generate...', 'vortex-marketplace'); ?>" rows="2"></textarea>
            
            <div class="generate-actions">
                <button class="button button-primary generate-button"><?php _e('Generate', 'vortex-marketplace'); ?></button>
                
                <select class="style-select">
                    <option value="realistic"><?php _e('Realistic', 'vortex-marketplace'); ?></option>
                    <option value="artistic"><?php _e('Artistic', 'vortex-marketplace'); ?></option>
                    <option value="abstract"><?php _e('Abstract', 'vortex-marketplace'); ?></option>
                    <option value="digital"><?php _e('Digital Art', 'vortex-marketplace'); ?></option>
                </select>
            </div>
        </div>
        
        <div class="results-container" style="display: none;">
            <div class="results-grid"></div>
            <div class="results-actions">
                <button class="button back-button"><?php _e('Back', 'vortex-marketplace'); ?></button>
                <a href="<?php echo admin_url('admin.php?page=vortex-image-generator'); ?>" class="button"><?php _e('Open Generator', 'vortex-marketplace'); ?></a>
            </div>
        </div>
        
        <div class="loading-container" style="display: none;">
            <span class="spinner is-active"></span>
            <p><?php _e('Generating your image...', 'vortex-marketplace'); ?></p>
        </div>
    </div>
    <?php
}

/**
 * Clean up old generated images
 */
function vortex_huraii_cleanup_old_images() {
    $retention_days = intval(get_option('vortex_huraii_history_days', 30));
    
    if ($retention_days < 1) {
        return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'vortex_generated_images';
    
    // Delete records older than retention period
    $wpdb->query($wpdb->prepare(
        "DELETE FROM $table_name WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
        $retention_days
    ));
}
add_action('vortex_daily_cleanup', 'vortex_huraii_cleanup_old_images');

// Schedule daily cleanup if not already scheduled
if (!wp_next_scheduled('vortex_daily_cleanup')) {
    wp_schedule_event(time(), 'daily', 'vortex_daily_cleanup');
} 