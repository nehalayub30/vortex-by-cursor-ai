function vortex_payment_button_shortcode($atts) {
    try {
        // Parse attributes
        $atts = shortcode_atts(array(
            'id' => 0,
            'type' => 'standard',
            'text' => __('Purchase Now', 'vortex-ai-marketplace')
        ), $atts, 'vortex_payment_button');
        
        $id = intval($atts['id']);
        
        // Validate required parameters
        if (empty($id) || $id <= 0) {
            return '<div class="vortex-error">' . __('Error: Invalid or missing artwork ID', 'vortex-ai-marketplace') . '</div>';
        }
        
        // Check if artwork exists
        $artwork = get_post($id);
        if (!$artwork || $artwork->post_type !== 'vortex_artwork') {
            return '<div class="vortex-error">' . __('Error: Artwork not found', 'vortex-ai-marketplace') . '</div>';
        }
        
        // Get artwork metadata
        $price = get_post_meta($id, 'vortex_artwork_price', true);
        $currency = get_post_meta($id, 'vortex_artwork_currency', true);
        $blockchain = get_post_meta($id, 'vortex_artwork_blockchain', true);
        
        if (empty($price)) {
            return '<div class="vortex-error">' . __('Error: Artwork price not set', 'vortex-ai-marketplace') . '</div>';
        }
        
        // Prepare button data
        $button_data = array(
            'artwork_id' => $id,
            'price' => $price,
            'currency' => !empty($currency) ? $currency : 'TOLA',
            'blockchain' => !empty($blockchain) ? $blockchain : 'ethereum',
            'button_type' => $atts['type'],
            'button_text' => $atts['text']
        );
        
        // Get current user data
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        
        // Add nonce for security
        $nonce = wp_create_nonce('vortex_purchase_' . $id);
        
        // Generate HTML
        $button_html = '<div class="vortex-payment-button-container">';
        $button_html .= '<button type="button" class="vortex-payment-button vortex-payment-button-' . esc_attr($atts['type']) . '" ';
        $button_html .= 'data-artwork="' . esc_attr($id) . '" ';
        $button_html .= 'data-price="' . esc_attr($price) . '" ';
        $button_html .= 'data-currency="' . esc_attr($button_data['currency']) . '" ';
        $button_html .= 'data-blockchain="' . esc_attr($button_data['blockchain']) . '" ';
        $button_html .= 'data-nonce="' . esc_attr($nonce) . '" ';
        $button_html .= 'data-user="' . esc_attr($user_id) . '">';
        $button_html .= esc_html($atts['text']);
        $button_html .= '</button>';
        
        // Add price display
        $button_html .= '<div class="vortex-payment-price">';
        $button_html .= esc_html($price) . ' ' . esc_html($button_data['currency']);
        $button_html .= '</div>';
        
        $button_html .= '</div>';
        
        // Enqueue necessary scripts
        wp_enqueue_script('vortex-payment-js');
        wp_enqueue_style('vortex-payment-css');
        
        return $button_html;
        
    } catch (Exception $e) {
        // Log the error
        error_log('Vortex Payment Button Error: ' . $e->getMessage());
        
        // Return user-friendly error message
        return '<div class="vortex-error">' . __('An error occurred while processing the payment button.', 'vortex-ai-marketplace') . '</div>';
    }
}

// Register the shortcode
add_shortcode('vortex_payment_button', 'vortex_payment_button_shortcode'); 