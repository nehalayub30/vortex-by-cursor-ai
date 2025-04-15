<?php

class VORTEX_DAO_WooCommerce_Admin {
    private static $instance = null;

    private function __construct() {
        // Initialize hooks
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'handle_export']);
        add_action('wp_ajax_vortex_dao_recalculate_rewards', [$this, 'ajax_recalculate_rewards']);
        add_action('wp_ajax_vortex_dao_generate_order_rewards', [$this, 'ajax_generate_order_rewards']);
        add_action('wp_ajax_vortex_dao_force_distribute_rewards', [$this, 'ajax_force_distribute_rewards']);
    }

    public static function get_instance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function add_admin_menu() {
        add_menu_page(
            'Vortex DAO WooCommerce',
            'Vortex DAO',
            'manage_woocommerce',
            'vortex-dao-woocommerce',
            [$this, 'render_admin_page'],
            'dashicons-admin-plugins',
            6
        );
    }

    public function render_admin_page() {
        // Render the admin page content
    }

    public function ajax_recalculate_rewards() {
        // Implementation of ajax_recalculate_rewards method
    }

    public function ajax_generate_order_rewards() {
        // Implementation of ajax_generate_order_rewards method
    }

    public function ajax_force_distribute_rewards() {
        // Implementation of ajax_force_distribute_rewards method
    }

    public function handle_export() {
        // Implementation of handle_export method
    }

    private function export_rewards($format) {
        // Implementation of export_rewards method
    }

    private function export_claims($format) {
        // Implementation of export_claims method
    }
}

// Initialize the admin class
VORTEX_DAO_WooCommerce_Admin::get_instance(); 