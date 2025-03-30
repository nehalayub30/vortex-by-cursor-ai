<?php
namespace VortexAI\Core;

class Plugin {
    private static \ = null;

    public static function getInstance() {
        if (null === self::\) {
            self::\ = new self();
        }
        return self::\;
    }

    private function __construct() {
        \->initHooks();
    }

    private function initHooks() {
        add_action('plugins_loaded', [\, 'loadTextdomain']);
        add_action('admin_enqueue_scripts', [\, 'adminAssets']);
        add_action('wp_enqueue_scripts', [\, 'frontendAssets']);
    }

    public function loadTextdomain() {
        load_plugin_textdomain('vortex-ai-agents', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function adminAssets() {
        wp_enqueue_style('vortex-ai-admin', plugins_url('admin/css/admin.css', dirname(__FILE__)));
        wp_enqueue_script('vortex-ai-admin', plugins_url('admin/js/admin.js', dirname(__FILE__)), ['jquery'], '1.0.0', true);
    }

    public function frontendAssets() {
        wp_enqueue_style('vortex-ai-main', plugins_url('assets/css/style.css', dirname(__FILE__)));
        wp_enqueue_script('vortex-ai-main', plugins_url('assets/js/main.js', dirname(__FILE__)), ['jquery'], '1.0.0', true);
    }
}
