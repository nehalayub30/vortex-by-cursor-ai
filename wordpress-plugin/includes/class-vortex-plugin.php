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
}
