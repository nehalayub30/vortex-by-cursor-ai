<?php
/**
 * Price Estimator Shortcode
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/shortcodes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Load the AI pricing class
require_once plugin_dir_path(dirname(dirname(__FILE__))) . 'includes/class-vortex-ai-pricing.php';

/**
 * Price estimator shortcode function
 *
 * @param array $atts Shortcode attributes
 * @return string HTML output
 */
function vortex_price_estimator_shortcode($atts) {
    // Define defaults and sanitize attributes
    $atts = shortcode_atts(array(
        'style' => '',
        'size' => 'medium',
        'complexity' => 'standard'
    ), $atts, 'vortex_price_estimator');
    
    // Validate parameters
    $valid_sizes = array('small', 'medium', 'large', 'custom');
    $valid_complexity = array('simple', 'standard', 'complex', 'premium');
    
    // Sanitize and validate inputs
    $size = in_array($atts['size'], $valid_sizes) ? $atts['size'] : 'medium';
    $complexity = in_array($atts['complexity'], $valid_complexity) ? $atts['complexity'] : 'standard';
    $style = sanitize_text_field($atts['style']);
    
    // Check if AI features are enabled
    $payment_settings = get_option('vortex_payment_settings', array());
    $ai_enabled = isset($payment_settings['ai_pricing_enabled']) ? $payment_settings['ai_pricing_enabled'] : false;
    $huraii_enabled = isset($payment_settings['huraii_market_analysis']) ? $payment_settings['huraii_market_analysis'] : false;
    $cloe_enabled = isset($payment_settings['cloe_pricing_optimization']) ? $payment_settings['cloe_pricing_optimization'] : false;
    
    try {
        // Base price calculation
        $base_price = Vortex_AI_Pricing::calculate_base_price($size, $complexity, $style);
        $final_price = $base_price;
        
        $market_data = array();
        $optimization_data = array();
        
        // Apply AI enhancements if enabled
        if ($ai_enabled) {
            if ($huraii_enabled) {
                $market_data = Vortex_AI_Pricing::get_huraii_market_analysis($style, $size);
                $final_price = Vortex_AI_Pricing::apply_market_analysis($final_price, $market_data);
            }
            
            if ($cloe_enabled) {
                $optimization_data = Vortex_AI_Pricing::get_cloe_price_optimization($final_price, $style);
                $final_price = Vortex_AI_Pricing::apply_optimization_strategy($final_price, $optimization_data);
            }
        }
        
        // Get currency from settings
        $currency = isset($payment_settings['currency']) ? $payment_settings['currency'] : 'USD';
        $currencies = array(
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'CAD' => 'C$',
            'AUD' => 'A$',
            'TOLA' => 'TOLA'
        );
        $currency_symbol = isset($currencies[$currency]) ? $currencies[$currency] : '$';
        
        // Start building the output
        $output = '<div class="vortex-price-estimator">';
        $output .= '<h3>' . esc_html__('Artwork Price Estimation', 'vortex-ai-marketplace') . '</h3>';
        
        // Display the basic parameters
        $output .= '<div class="vortex-price-parameters">';
        if (!empty($style)) {
            $output .= '<div class="param"><strong>' . esc_html__('Style:', 'vortex-ai-marketplace') . '</strong> ' . esc_html(ucfirst($style)) . '</div>';
        }
        $output .= '<div class="param"><strong>' . esc_html__('Size:', 'vortex-ai-marketplace') . '</strong> ' . esc_html(ucfirst($size)) . '</div>';
        $output .= '<div class="param"><strong>' . esc_html__('Complexity:', 'vortex-ai-marketplace') . '</strong> ' . esc_html(ucfirst($complexity)) . '</div>';
        $output .= '</div>';
        
        // Display base price
        $output .= '<div class="vortex-price-calculation">';
        $output .= '<div class="base-price"><span>' . esc_html__('Base Price:', 'vortex-ai-marketplace') . '</span> ' . 
                   esc_html($currency_symbol) . esc_html(number_format($base_price, 2)) . '</div>';
        
        // If AI is enabled, show the analysis details
        if ($ai_enabled && ($huraii_enabled || $cloe_enabled)) {
            $output .= '<div class="ai-analysis">';
            $output .= '<h4>' . esc_html__('AI-Enhanced Price Analysis', 'vortex-ai-marketplace') . '</h4>';
            
            if ($huraii_enabled && !empty($market_data)) {
                $output .= '<div class="huraii-analysis">';
                $output .= '<h5><img src="' . esc_url(plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/img/huraii-icon.png') . '" alt="HURAII"> ' . 
                           esc_html__('HURAII Market Analysis', 'vortex-ai-marketplace') . '</h5>';
                
                // Market trends
                if (!empty($market_data['price_trend'])) {
                    $trend_class = $market_data['price_trend'] === 'up' ? 'trend-up' : 
                                  ($market_data['price_trend'] === 'down' ? 'trend-down' : 'trend-stable');
                    $trend_icon = $market_data['price_trend'] === 'up' ? '↑' : 
                                 ($market_data['price_trend'] === 'down' ? '↓' : '→');
                    
                    $output .= '<div class="market-trend ' . esc_attr($trend_class) . '">';
                    $output .= '<span>' . esc_html__('Market Trend:', 'vortex-ai-marketplace') . '</span> ' . 
                               esc_html($trend_icon . ' ' . ucfirst($market_data['price_trend']));
                    $output .= '</div>';
                }
                
                // Demand level
                if (!empty($market_data['demand_level'])) {
                    $demand_class = $market_data['demand_level'] === 'high' ? 'demand-high' : 
                                   ($market_data['demand_level'] === 'low' ? 'demand-low' : 'demand-medium');
                    
                    $output .= '<div class="market-demand ' . esc_attr($demand_class) . '">';
                    $output .= '<span>' . esc_html__('Demand:', 'vortex-ai-marketplace') . '</span> ' . 
                               esc_html(ucfirst($market_data['demand_level']));
                    $output .= '</div>';
                }
                
                // Average price
                if (!empty($market_data['average_price'])) {
                    $output .= '<div class="average-price">';
                    $output .= '<span>' . esc_html__('Market Average:', 'vortex-ai-marketplace') . '</span> ' . 
                               esc_html($currency_symbol) . esc_html(number_format($market_data['average_price'], 2));
                    $output .= '</div>';
                }
                
                $output .= '</div>'; // End huraii-analysis
            }
            
            if ($cloe_enabled && !empty($optimization_data)) {
                $output .= '<div class="cloe-optimization">';
                $output .= '<h5><img src="' . esc_url(plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/img/cloe-icon.png') . '" alt="CLOE"> ' . 
                           esc_html__('CLOE Pricing Optimization', 'vortex-ai-marketplace') . '</h5>';
                
                // Recommended strategy
                if (!empty($optimization_data['strategy'])) {
                    $output .= '<div class="pricing-strategy">';
                    $output .= '<span>' . esc_html__('Recommended Strategy:', 'vortex-ai-marketplace') . '</span> ' . 
                               esc_html(ucfirst($optimization_data['strategy']));
                    $output .= '</div>';
                }
                
                // Recommended price
                if (!empty($optimization_data['recommended_price'])) {
                    $output .= '<div class="recommended-price">';
                    $output .= '<span>' . esc_html__('Optimized Price:', 'vortex-ai-marketplace') . '</span> ' . 
                               esc_html($currency_symbol) . esc_html(number_format($optimization_data['recommended_price'], 2));
                    $output .= '</div>';
                }
                
                // Confidence score
                if (!empty($optimization_data['confidence_score'])) {
                    $confidence = round($optimization_data['confidence_score'] * 100);
                    $output .= '<div class="confidence-score">';
                    $output .= '<span>' . esc_html__('Confidence:', 'vortex-ai-marketplace') . '</span> ' . 
                               esc_html($confidence . '%');
                    $output .= '</div>';
                }
                
                $output .= '</div>'; // End cloe-optimization
            }
            
            $output .= '</div>'; // End ai-analysis
        }
        
        // Final price display
        $output .= '<div class="final-price">';
        $output .= '<h4>' . esc_html__('Estimated Fair Price:', 'vortex-ai-marketplace') . '</h4>';
        $output .= '<div class="price">' . esc_html($currency_symbol) . esc_html(number_format($final_price, 2)) . '</div>';
        $output .= '</div>';
        
        $output .= '</div>'; // End vortex-price-calculation
        
        // Add disclaimer
        $output .= '<div class="price-disclaimer">';
        $output .= '<p>' . esc_html__('This is an estimate only. Final pricing is at the artist\'s discretion.', 'vortex-ai-marketplace') . '</p>';
        $output .= '</div>';
        
        // End wrapper
        $output .= '</div>';
        
        return $output;
        
    } catch (Exception $e) {
        error_log('Price Estimator Error: ' . $e->getMessage());
        return '<div class="vortex-error">' . esc_html__('An error occurred while calculating the price estimation.', 'vortex-ai-marketplace') . '</div>';
    }
} 