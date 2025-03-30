<?php
/**
 * VORTEX Business Strategist Widgets
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage AI
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_BusinessStrategist_Widgets Class
 * 
 * Handles the creation and management of Business Strategist widgets
 * with WordPress theme integration and continuous AI agent activity.
 */
class VORTEX_BusinessStrategist_Widgets {
    /**
     * Instance of this class.
     */
    protected static $instance = null;
    
    /**
     * Business Strategist instance
     */
    private $business_strategist;
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->business_strategist = VORTEX_BusinessStrategist::get_instance();
        
        // Set up hooks
        add_action('widgets_init', array($this, 'register_widgets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_vortex_business_quiz_submit', array($this, 'handle_business_quiz_submit'));
        add_action('wp_ajax_vortex_business_plan_generate', array($this, 'handle_business_plan_generate'));
        add_action('wp_ajax_vortex_career_milestone_update', array($this, 'handle_career_milestone_update'));
        
        // Register shortcodes
        add_shortcode('vortex_business_quiz', array($this, 'render_business_quiz'));
        add_shortcode('vortex_business_plan', array($this, 'render_business_plan'));
        add_shortcode('vortex_career_milestones', array($this, 'render_career_milestones'));
        add_shortcode('vortex_learning_resources', array($this, 'render_learning_resources'));
    }
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Register widgets
     */
    public function register_widgets() {
        register_widget('Vortex_Business_Quiz_Widget');
        register_widget('Vortex_Business_Plan_Widget');
        register_widget('Vortex_Career_Milestones_Widget');
        register_widget('Vortex_Learning_Resources_Widget');
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Enqueue styles
        wp_enqueue_style(
            'vortex-business-strategist-widgets',
            VORTEX_PLUGIN_URL . 'css/vortex-business-strategist-widgets.css',
            array(),
            VORTEX_VERSION
        );
        
        // Enqueue scripts
        wp_enqueue_script(
            'vortex-business-strategist-widgets',
            VORTEX_PLUGIN_URL . 'js/vortex-business-strategist-widgets.js',
            array('jquery'),
            VORTEX_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('vortex-business-strategist-widgets', 'vortexBusinessStrategist', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_business_strategist_nonce'),
            'i18n' => array(
                'quiz_submit' => __('Submit Quiz', 'vortex-ai-marketplace'),
                'plan_generate' => __('Generate Plan', 'vortex-ai-marketplace'),
                'milestone_update' => __('Update Milestone', 'vortex-ai-marketplace'),
                'loading' => __('Loading...', 'vortex-ai-marketplace'),
                'error' => __('An error occurred. Please try again.', 'vortex-ai-marketplace')
            )
        ));
        
        // Get theme colors for dynamic styling
        $theme_colors = $this->get_theme_colors();
        wp_localize_script('vortex-business-strategist-widgets', 'vortexThemeColors', $theme_colors);
    }
    
    /**
     * Get theme colors
     */
    private function get_theme_colors() {
        $colors = array(
            'primary' => get_theme_mod('primary_color', '#007bff'),
            'secondary' => get_theme_mod('secondary_color', '#6c757d'),
            'accent' => get_theme_mod('accent_color', '#28a745'),
            'background' => get_theme_mod('background_color', '#ffffff'),
            'text' => get_theme_mod('text_color', '#212529')
        );
        
        return $colors;
    }
    
    /**
     * Handle business quiz submission
     */
    public function handle_business_quiz_submit() {
        check_ajax_referer('vortex_business_strategist_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
        }
        
        $user_id = get_current_user_id();
        $answers = isset($_POST['answers']) ? sanitize_text_field($_POST['answers']) : '';
        
        if (empty($answers)) {
            wp_send_json_error('No answers provided');
        }
        
        // Process quiz answers and generate insights
        $insights = $this->business_strategist->analyze_business_quiz($user_id, $answers);
        
        // Update user meta with quiz results
        update_user_meta($user_id, 'vortex_business_quiz_results', $insights);
        
        wp_send_json_success(array(
            'insights' => $insights,
            'message' => __('Quiz results processed successfully.', 'vortex-ai-marketplace')
        ));
    }
    
    /**
     * Handle business plan generation
     */
    public function handle_business_plan_generate() {
        check_ajax_referer('vortex_business_strategist_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
        }
        
        $user_id = get_current_user_id();
        $plan_type = isset($_POST['plan_type']) ? sanitize_text_field($_POST['plan_type']) : 'starter';
        
        // Generate personalized business plan
        $plan = $this->business_strategist->generate_business_plan($user_id, $plan_type);
        
        // Update user meta with business plan
        update_user_meta($user_id, 'vortex_business_plan', $plan);
        
        wp_send_json_success(array(
            'plan' => $plan,
            'message' => __('Business plan generated successfully.', 'vortex-ai-marketplace')
        ));
    }
    
    /**
     * Handle career milestone updates
     */
    public function handle_career_milestone_update() {
        check_ajax_referer('vortex_business_strategist_nonce', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in');
        }
        
        $user_id = get_current_user_id();
        $milestone_id = isset($_POST['milestone_id']) ? sanitize_text_field($_POST['milestone_id']) : '';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        
        if (empty($milestone_id)) {
            wp_send_json_error('No milestone ID provided');
        }
        
        // Update milestone status
        $updated = $this->business_strategist->update_career_milestone($user_id, $milestone_id, $status);
        
        if ($updated) {
            wp_send_json_success(array(
                'message' => __('Milestone updated successfully.', 'vortex-ai-marketplace')
            ));
        } else {
            wp_send_json_error('Failed to update milestone');
        }
    }
    
    /**
     * Render business quiz shortcode
     */
    public function render_business_quiz($atts) {
        if (!is_user_logged_in()) {
            return sprintf(
                '<p>%s <a href="%s">%s</a></p>',
                __('Please', 'vortex-ai-marketplace'),
                wp_login_url(get_permalink()),
                __('log in', 'vortex-ai-marketplace')
            );
        }
        
        ob_start();
        include VORTEX_PLUGIN_PATH . 'public/partials/vortex-business-quiz-widget.php';
        return ob_get_clean();
    }
    
    /**
     * Render business plan shortcode
     */
    public function render_business_plan($atts) {
        if (!is_user_logged_in()) {
            return sprintf(
                '<p>%s <a href="%s">%s</a></p>',
                __('Please', 'vortex-ai-marketplace'),
                wp_login_url(get_permalink()),
                __('log in', 'vortex-ai-marketplace')
            );
        }
        
        ob_start();
        include VORTEX_PLUGIN_PATH . 'public/partials/vortex-business-plan-widget.php';
        return ob_get_clean();
    }
    
    /**
     * Render career milestones shortcode
     */
    public function render_career_milestones($atts) {
        if (!is_user_logged_in()) {
            return sprintf(
                '<p>%s <a href="%s">%s</a></p>',
                __('Please', 'vortex-ai-marketplace'),
                wp_login_url(get_permalink()),
                __('log in', 'vortex-ai-marketplace')
            );
        }
        
        ob_start();
        include VORTEX_PLUGIN_PATH . 'public/partials/vortex-career-milestones-widget.php';
        return ob_get_clean();
    }
    
    /**
     * Render learning resources shortcode
     */
    public function render_learning_resources($atts) {
        if (!is_user_logged_in()) {
            return sprintf(
                '<p>%s <a href="%s">%s</a></p>',
                __('Please', 'vortex-ai-marketplace'),
                wp_login_url(get_permalink()),
                __('log in', 'vortex-ai-marketplace')
            );
        }
        
        ob_start();
        include VORTEX_PLUGIN_PATH . 'public/partials/vortex-learning-resources-widget.php';
        return ob_get_clean();
    }
} 