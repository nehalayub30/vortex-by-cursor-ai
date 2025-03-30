<?php
/**
 * VORTEX Subscriptions Class
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage Subscriptions
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class VORTEX_Subscriptions {
    /**
     * Instance of this class.
     *
     * @var VORTEX_Subscriptions
     */
    private static $instance = null;
    
    /**
     * Artist subscription plans.
     *
     * @var array
     */
    public $artist_plans = array(
        'basic' => array(
            'name' => 'Basic Artist',
            'price' => 19.99,
            'features' => array(
                'basic_ai_generation',
                'standard_support',
                'basic_marketplace_access',
                '5_artworks_per_month'
            )
        ),
        'pro' => array(
            'name' => 'Pro Artist',
            'price' => 39.99,
            'features' => array(
                'advanced_ai_generation',
                'priority_support',
                'full_marketplace_access',
                'unlimited_artworks',
                'analytics_dashboard'
            )
        ),
        'premium' => array(
            'name' => 'Premium Artist',
            'price' => 59.99,
            'features' => array(
                'premium_ai_generation',
                '24_7_support',
                'full_marketplace_access',
                'unlimited_artworks',
                'advanced_analytics',
                'custom_profile_page'
            )
        ),
        'unlimited' => array(
            'name' => 'Unlimited Artist',
            'price' => 99.99,
            'features' => array(
                'unlimited_ai_generation',
                'dedicated_support',
                'full_marketplace_access',
                'unlimited_artworks',
                'advanced_analytics',
                'custom_profile_page',
                'exclusive_events_access'
            )
        )
    );
    
    /**
     * Collector subscription plans.
     *
     * @var array
     */
    public $collector_plans = array(
        'basic' => array(
            'name' => 'Basic Collector',
            'price' => 19.99,
            'features' => array(
                'basic_marketplace_access',
                'standard_support',
                'basic_artwork_previews'
            )
        ),
        'pro' => array(
            'name' => 'Pro Collector',
            'price' => 59.99,
            'features' => array(
                'full_marketplace_access',
                'priority_support',
                'advanced_artwork_previews',
                'early_access_to_new_artworks'
            )
        ),
        'premium' => array(
            'name' => 'Premium Collector',
            'price' => 99.99,
            'features' => array(
                'full_marketplace_access',
                '24_7_support',
                'advanced_artwork_previews',
                'early_access_to_new_artworks',
                'exclusive_collector_events'
            )
        ),
        'unlimited' => array(
            'name' => 'Unlimited Collector',
            'price' => 199.99,
            'features' => array(
                'full_marketplace_access',
                'dedicated_support',
                'advanced_artwork_previews',
                'early_access_to_new_artworks',
                'exclusive_collector_events',
                'private_artwork_showings',
                'artist_meet_and_greets'
            )
        )
    );
    
    /**
     * Get instance of this class.
     *
     * @return VORTEX_Subscriptions
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor.
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks.
     */
    private function init_hooks() {
        add_action('init', array($this, 'register_subscription_post_type'));
        add_action('admin_menu', array($this, 'add_subscription_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Register subscription post type.
     */
    public function register_subscription_post_type() {
        register_post_type('vortex_subscription', array(
            'labels' => array(
                'name' => __('Subscriptions', 'vortex'),
                'singular_name' => __('Subscription', 'vortex')
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => array('title', 'custom-fields')
        ));
    }
    
    /**
     * Add subscription menu.
     */
    public function add_subscription_menu() {
        add_submenu_page(
            'vortex-settings',
            __('Subscriptions', 'vortex'),
            __('Subscriptions', 'vortex'),
            'manage_options',
            'vortex-subscriptions',
            array($this, 'render_subscription_page')
        );
    }
    
    /**
     * Enqueue admin scripts.
     */
    public function enqueue_admin_scripts($hook) {
        if ('vortex_page_vortex-subscriptions' !== $hook) {
            return;
        }
        
        wp_enqueue_style(
            'vortex-subscriptions-admin',
            VORTEX_PLUGIN_URL . 'css/vortex-subscriptions-admin.css',
            array(),
            VORTEX_VERSION
        );
        
        wp_enqueue_script(
            'vortex-subscriptions-admin',
            VORTEX_PLUGIN_URL . 'js/vortex-subscriptions-admin.js',
            array('jquery'),
            VORTEX_VERSION,
            true
        );
    }
    
    /**
     * Get plan price.
     *
     * @param string $plan_type Plan type (artist or collector).
     * @param string $plan_tier Plan tier (basic, pro, premium, unlimited).
     * @return float
     */
    public function get_plan_price($plan_type, $plan_tier) {
        $plans = $plan_type === 'artist' ? $this->artist_plans : $this->collector_plans;
        return isset($plans[$plan_tier]['price']) ? $plans[$plan_tier]['price'] : 0;
    }
    
    /**
     * Get plan features.
     *
     * @param string $plan_type Plan type (artist or collector).
     * @param string $plan_tier Plan tier (basic, pro, premium, unlimited).
     * @return array
     */
    public function get_plan_features($plan_type, $plan_tier) {
        $plans = $plan_type === 'artist' ? $this->artist_plans : $this->collector_plans;
        return isset($plans[$plan_tier]['features']) ? $plans[$plan_tier]['features'] : array();
    }
    
    /**
     * Process subscription.
     *
     * @param int    $user_id User ID.
     * @param string $plan_type Plan type.
     * @param string $plan_tier Plan tier.
     * @return array
     */
    public function process_subscription($user_id, $plan_type, $plan_tier) {
        try {
            // Get plan price
            $plan_price = $this->get_plan_price($plan_type, $plan_tier);
            
            // Process TOLA payment
            $payment_result = $this->process_tola_payment($user_id, $plan_price);
            
            if (!$payment_result['success']) {
                return array(
                    'success' => false,
                    'message' => $payment_result['message']
                );
            }
            
            // Create subscription record
            $subscription_id = $this->create_subscription($user_id, $plan_type, $plan_tier);
            
            if (!$subscription_id) {
                return array(
                    'success' => false,
                    'message' => __('Failed to create subscription record.', 'vortex')
                );
            }
            
            // Update user meta
            $this->update_user_subscription_meta($user_id, $subscription_id, $plan_type, $plan_tier);
            
            return array(
                'success' => true,
                'message' => __('Subscription activated successfully!', 'vortex')
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * Process TOLA payment.
     *
     * @param int   $user_id User ID.
     * @param float $amount Amount to charge.
     * @return array
     */
    private function process_tola_payment($user_id, $amount) {
        // Get user's TOLA balance
        $user_balance = vortex_get_user_tola_balance($user_id);
        
        if ($user_balance < $amount) {
            return array(
                'success' => false,
                'message' => __('Insufficient TOLA balance.', 'vortex')
            );
        }
        
        // Deduct TOLA from user's balance
        $deduct_result = vortex_deduct_tola_balance($user_id, $amount);
        
        if (!$deduct_result) {
            return array(
                'success' => false,
                'message' => __('Failed to process payment.', 'vortex')
            );
        }
        
        return array('success' => true);
    }
    
    /**
     * Create subscription record.
     *
     * @param int    $user_id User ID.
     * @param string $plan_type Plan type.
     * @param string $plan_tier Plan tier.
     * @return int|false
     */
    private function create_subscription($user_id, $plan_type, $plan_tier) {
        $subscription_data = array(
            'post_title' => sprintf(
                __('Subscription for User %d - %s %s', 'vortex'),
                $user_id,
                ucfirst($plan_type),
                ucfirst($plan_tier)
            ),
            'post_type' => 'vortex_subscription',
            'post_status' => 'publish',
            'meta_input' => array(
                'user_id' => $user_id,
                'plan_type' => $plan_type,
                'plan_tier' => $plan_tier,
                'start_date' => current_time('mysql'),
                'expiry_date' => date('Y-m-d H:i:s', strtotime('+1 month'))
            )
        );
        
        $subscription_id = wp_insert_post($subscription_data);
        
        if (is_wp_error($subscription_id)) {
            return false;
        }
        
        return $subscription_id;
    }
    
    /**
     * Update user subscription meta.
     *
     * @param int    $user_id User ID.
     * @param int    $subscription_id Subscription ID.
     * @param string $plan_type Plan type.
     * @param string $plan_tier Plan tier.
     */
    private function update_user_subscription_meta($user_id, $subscription_id, $plan_type, $plan_tier) {
        update_user_meta($user_id, 'vortex_subscription_id', $subscription_id);
        update_user_meta($user_id, 'vortex_plan_type', $plan_type);
        update_user_meta($user_id, 'vortex_plan_tier', $plan_tier);
        update_user_meta($user_id, 'vortex_subscription_status', 'active');
        update_user_meta($user_id, 'vortex_subscription_expiry', date('Y-m-d H:i:s', strtotime('+1 month')));
    }
    
    /**
     * Check if user has active subscription.
     *
     * @param int $user_id User ID.
     * @return bool
     */
    public function has_active_subscription($user_id) {
        $status = get_user_meta($user_id, 'vortex_subscription_status', true);
        $expiry = get_user_meta($user_id, 'vortex_subscription_expiry', true);
        
        if ($status !== 'active' || !$expiry) {
            return false;
        }
        
        return strtotime($expiry) > current_time('timestamp');
    }
    
    /**
     * Get user's subscription details.
     *
     * @param int $user_id User ID.
     * @return array|false
     */
    public function get_user_subscription($user_id) {
        if (!$this->has_active_subscription($user_id)) {
            return false;
        }
        
        return array(
            'subscription_id' => get_user_meta($user_id, 'vortex_subscription_id', true),
            'plan_type' => get_user_meta($user_id, 'vortex_plan_type', true),
            'plan_tier' => get_user_meta($user_id, 'vortex_plan_tier', true),
            'expiry_date' => get_user_meta($user_id, 'vortex_subscription_expiry', true)
        );
    }
    
    /**
     * Render subscription page.
     */
    public function render_subscription_page() {
        include VORTEX_PLUGIN_DIR . 'admin/partials/vortex-subscription-page.php';
    }
} 