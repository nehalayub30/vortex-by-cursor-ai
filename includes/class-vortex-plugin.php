<?php
namespace Vortex\AI;

class VortexPlugin {
    private $version;
    private $plugin_name;

    public function __construct() {
        $this->version = VORTEX_VERSION;
        $this->plugin_name = 'vortex-ai';
    }

    public function initialize() {
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        require_once VORTEX_PLUGIN_DIR . 'includes/ai/auth/class-vortex-auth.php';
        require_once VORTEX_PLUGIN_DIR . 'includes/ai/blockchain/class-vortex-blockchain.php';
        require_once VORTEX_PLUGIN_DIR . 'includes/ai/market/class-vortex-market.php';
        require_once VORTEX_PLUGIN_DIR . 'includes/ai/composer/class-vortex-composer.php';
    }

    private function set_locale() {
        add_action('plugins_loaded', function() {
            load_plugin_textdomain(
                'vortex-ai',
                false,
                dirname(plugin_basename(__FILE__)) . '/languages/'
            );
        });
    }

    private function define_admin_hooks() {
        // Add admin hooks here
    }

    private function define_public_hooks() {
        // Add public hooks here
    }

    /**
     * Register cron jobs for the plugin
     * 
     * Sets up scheduled tasks including hourly AI synchronization
     *
     * @since 1.0.0
     * @return void 
     */
    public function register_cron_jobs() {
        // Register hourly cron if not already scheduled
        if (!wp_next_scheduled('vortex_hourly_cron')) {
            wp_schedule_event(time(), 'hourly', 'vortex_hourly_cron');
        }
        
        // Register daily cron if not already scheduled
        if (!wp_next_scheduled('vortex_daily_cron')) {
            wp_schedule_event(time(), 'daily', 'vortex_daily_cron');
        }
        
        // Register weekly cron if not already scheduled
        if (!wp_next_scheduled('vortex_weekly_cron')) {
            wp_schedule_event(time(), 'weekly', 'vortex_weekly_cron');
        }
    }

    /**
     * Register activation hook to set up cron jobs
     */
    public function activate() {
        // Register cron jobs
        $this->register_cron_jobs();
        
        // Other activation tasks...
    }

    /**
     * Register deactivation hook to clean up cron jobs
     */
    public function deactivate() {
        // Clear scheduled cron jobs
        wp_clear_scheduled_hook('vortex_hourly_cron');
        wp_clear_scheduled_hook('vortex_daily_cron');
        wp_clear_scheduled_hook('vortex_weekly_cron');
        
        // Other deactivation tasks...
    }
}
