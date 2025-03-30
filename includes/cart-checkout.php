<?php
/**
 * VORTEX AI Marketplace Cart and Checkout Handler
 *
 * @package VORTEX
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

class VORTEX_Cart_Checkout {
    private $ai_learning_enabled = true;
    private $huraii;
    private $cloe;
    private $business_strategist;

    public function __construct() {
        $this->init_ai_agents();
        $this->init_hooks();
    }

    /**
     * Initialize AI agents
     */
    private function init_ai_agents() {
        $this->huraii = VORTEX_AI_Manager::get_instance()->get_agent('huraii');
        $this->cloe = VORTEX_AI_Manager::get_instance()->get_agent('cloe');
        $this->business_strategist = VORTEX_AI_Manager::get_instance()->get_agent('business_strategist');
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_vortex_add_to_cart', array($this, 'handle_add_to_cart'));
        add_action('wp_ajax_vortex_update_cart', array($this, 'handle_update_cart'));
        add_action('wp_ajax_vortex_process_checkout', array($this, 'handle_checkout'));
        add_action('vortex_before_checkout', array($this, 'analyze_cart_contents'));
    }

    /**
     * Handle adding items to cart with AI analysis
     */
    public function handle_add_to_cart() {
        check_ajax_referer('vortex_cart_nonce', 'nonce');

        try {
            $artwork_id = isset($_POST['artwork_id']) ? absint($_POST['artwork_id']) : 0;
            if (!$artwork_id) {
                throw new Exception(__('Invalid artwork ID', 'vortex'));
            }

            // Verify artwork exists and is available
            $artwork = $this->verify_artwork($artwork_id);

            // AI Analysis before adding to cart
            $ai_insights = $this->get_ai_purchase_insights($artwork);

            // Add to cart with AI insights
            $cart_item = array(
                'artwork_id' => $artwork_id,
                'price' => $artwork->get_price(),
                'ai_insights' => $ai_insights,
                'timestamp' => current_time('timestamp')
            );

            WC()->cart->add_to_cart($artwork_id, 1, 0, array(), $cart_item);

            // Track for AI learning
            $this->track_cart_action('add', $artwork_id, $ai_insights);

            wp_send_json_success(array(
                'message' => __('Artwork added to cart', 'vortex'),
                'insights' => $ai_insights
            ));

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * Process checkout with AI assistance
     */
    public function handle_checkout() {
        check_ajax_referer('vortex_checkout_nonce', 'nonce');

        try {
            // Validate checkout data
            $this->validate_checkout_data($_POST);

            // AI risk assessment
            $risk_assessment = $this->perform_ai_risk_assessment($_POST);
            if ($risk_assessment['risk_level'] === 'high') {
                throw new Exception(__('Transaction flagged for security review', 'vortex'));
            }

            // Process payment
            $order_id = $this->process_payment($_POST);

            // AI learning from successful checkout
            $this->track_successful_checkout($order_id);

            wp_send_json_success(array(
                'order_id' => $order_id,
                'redirect' => $this->get_checkout_redirect($order_id)
            ));

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * Get AI-powered purchase insights
     */
    private function get_ai_purchase_insights($artwork) {
        $insights = array();

        // HURAII artwork analysis
        $insights['artwork_analysis'] = $this->huraii->analyze_artwork($artwork->get_id());

        // CLOE user preference analysis
        $insights['user_preferences'] = $this->cloe->get_user_preferences(get_current_user_id());

        // Business Strategist market analysis
        $insights['market_analysis'] = $this->business_strategist->analyze_market_conditions($artwork->get_id());

        return $insights;
    }

    /**
     * Perform AI risk assessment
     */
    private function perform_ai_risk_assessment($data) {
        $risk_factors = array();

        // HURAII fraud detection
        $risk_factors['artwork_authenticity'] = $this->huraii->verify_artwork_authenticity($data['artwork_id']);

        // CLOE user behavior analysis
        $risk_factors['user_behavior'] = $this->cloe->analyze_user_behavior(get_current_user_id());

        // Business Strategist transaction analysis
        $risk_factors['transaction_pattern'] = $this->business_strategist->analyze_transaction_pattern($data);

        return array(
            'risk_level' => $this->calculate_risk_level($risk_factors),
            'factors' => $risk_factors
        );
    }

    /**
     * Track successful checkout for AI learning
     */
    private function track_successful_checkout($order_id) {
        if (!$this->ai_learning_enabled) {
            return;
        }

        $order = wc_get_order($order_id);
        $learning_data = array(
            'order_id' => $order_id,
            'user_id' => $order->get_user_id(),
            'items' => $order->get_items(),
            'total' => $order->get_total(),
            'payment_method' => $order->get_payment_method(),
            'timestamp' => current_time('timestamp')
        );

        // Update AI agents with checkout data
        $this->huraii->learn_from_checkout($learning_data);
        $this->cloe->learn_from_purchase($learning_data);
        $this->business_strategist->analyze_sales_pattern($learning_data);
    }

    /**
     * Validate checkout data
     */
    private function validate_checkout_data($data) {
        // Required fields validation
        $required_fields = array('billing_email', 'billing_first_name', 'billing_last_name');
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                throw new Exception(sprintf(__('Missing required field: %s', 'vortex'), $field));
            }
        }

        // Email validation
        if (!is_email($data['billing_email'])) {
            throw new Exception(__('Invalid email address', 'vortex'));
        }

        // Cart validation
        if (WC()->cart->is_empty()) {
            throw new Exception(__('Cart is empty', 'vortex'));
        }

        return true;
    }

    /**
     * Process payment
     */
    private function process_payment($data) {
        // Create order
        $order = wc_create_order(array(
            'customer_id' => get_current_user_id()
        ));

        // Add cart items to order
        foreach (WC()->cart->get_cart() as $cart_item) {
            $order->add_product(
                wc_get_product($cart_item['artwork_id']),
                1,
                array(
                    'ai_insights' => $cart_item['ai_insights']
                )
            );
        }

        // Set billing data
        $order->set_address($data, 'billing');
        
        // Process payment
        $payment_gateway = WC()->payment_gateways()->payment_gateways()[$data['payment_method']];
        $payment_result = $payment_gateway->process_payment($order->get_id());

        if ($payment_result['result'] !== 'success') {
            throw new Exception(__('Payment processing failed', 'vortex'));
        }

        return $order->get_id();
    }

    /**
     * Get checkout redirect URL
     */
    private function get_checkout_redirect($order_id) {
        $order = wc_get_order($order_id);
        return $order->get_checkout_order_received_url();
    }

    /**
     * Calculate risk level from factors
     */
    private function calculate_risk_level($factors) {
        $risk_score = 0;
        
        foreach ($factors as $factor) {
            $risk_score += $factor['risk_score'];
        }

        if ($risk_score > 80) return 'high';
        if ($risk_score > 50) return 'medium';
        return 'low';
    }

    /**
     * Track cart actions for AI learning
     */
    private function track_cart_action($action, $artwork_id, $insights) {
        if (!$this->ai_learning_enabled) {
            return;
        }

        $tracking_data = array(
            'action' => $action,
            'artwork_id' => $artwork_id,
            'user_id' => get_current_user_id(),
            'insights' => $insights,
            'timestamp' => current_time('timestamp')
        );

        // Update AI agents with cart interaction data
        $this->huraii->learn_from_cart_action($tracking_data);
        $this->cloe->update_user_preferences($tracking_data);
        $this->business_strategist->analyze_cart_behavior($tracking_data);
    }

    /**
     * Verify artwork exists and is available
     */
    private function verify_artwork($artwork_id) {
        $artwork = wc_get_product($artwork_id);

        if (!$artwork || $artwork->get_status() !== 'publish') {
            throw new Exception(__('Artwork not available', 'vortex'));
        }

        return $artwork;
    }
}

// Initialize the cart checkout handler
new VORTEX_Cart_Checkout(); 