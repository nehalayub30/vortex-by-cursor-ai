<?php
/**
 * AI-Powered Pricing Helper Class
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Class that handles AI pricing calculations
 */
class Vortex_AI_Pricing {
    
    /**
     * Get HURAII market analysis
     *
     * @param string $style Artwork style
     * @param string $size Artwork size
     * @return array Market analysis data
     */
    public static function get_huraii_market_analysis($style, $size) {
        try {
            // In a real implementation, this would call the HURAII API
            // For now, we'll simulate a response
            
            $market_data = array(
                'average_price' => self::get_simulated_average_price($style, $size),
                'price_trend' => self::get_simulated_trend($style),
                'demand_level' => self::get_simulated_demand($style),
                'market_saturation' => mt_rand(1, 10) / 10,
                'competitor_pricing' => array(
                    'low' => self::get_simulated_average_price($style, $size) * 0.7,
                    'median' => self::get_simulated_average_price($style, $size),
                    'high' => self::get_simulated_average_price($style, $size) * 1.3,
                )
            );
            
            return $market_data;
        } catch (Exception $e) {
            error_log('HURAII Analysis Error: ' . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Get CLOE price optimization
     *
     * @param float $base_price Base artwork price
     * @param string $style Artwork style
     * @return array Optimization data
     */
    public static function get_cloe_price_optimization($base_price, $style) {
        try {
            // In a real implementation, this would call the CLOE API
            // For now, we'll simulate a response
            
            $optimization_data = array(
                'recommended_price' => self::optimize_price($base_price, $style),
                'price_elasticity' => mt_rand(5, 15) / 10,
                'revenue_impact' => array(
                    'at_recommended' => $base_price * mt_rand(110, 130) / 100,
                    'at_current' => $base_price,
                ),
                'confidence_score' => mt_rand(70, 95) / 100,
                'strategy' => self::get_pricing_strategy($style)
            );
            
            return $optimization_data;
        } catch (Exception $e) {
            error_log('CLOE Optimization Error: ' . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Apply market analysis to base price
     *
     * @param float $base_price Base artwork price
     * @param array $market_data Market analysis data
     * @return float Adjusted price
     */
    public static function apply_market_analysis($base_price, $market_data) {
        if (empty($market_data)) {
            return $base_price;
        }
        
        // Adjust price based on market data
        $avg_price = isset($market_data['average_price']) ? $market_data['average_price'] : $base_price;
        $trend_factor = isset($market_data['price_trend']) ? 
            ($market_data['price_trend'] === 'up' ? 1.1 : ($market_data['price_trend'] === 'down' ? 0.9 : 1)) : 1;
        $demand_factor = isset($market_data['demand_level']) ? 
            ($market_data['demand_level'] === 'high' ? 1.1 : ($market_data['demand_level'] === 'low' ? 0.9 : 1)) : 1;
        
        // Calculate weight for market vs base price (0.6 = 60% market influence)
        $market_weight = 0.6;
        
        // Weighted average of base price and market-adjusted price
        $adjusted_price = ($base_price * (1 - $market_weight)) + 
                         ($avg_price * $trend_factor * $demand_factor * $market_weight);
        
        return round($adjusted_price, 2);
    }
    
    /**
     * Apply optimization strategy to base price
     *
     * @param float $base_price Base artwork price
     * @param array $optimization_data Optimization data
     * @return float Optimized price
     */
    public static function apply_optimization_strategy($base_price, $optimization_data) {
        if (empty($optimization_data) || !isset($optimization_data['recommended_price'])) {
            return $base_price;
        }
        
        // Use CLOE's recommended price with a confidence weight
        $confidence = isset($optimization_data['confidence_score']) ? $optimization_data['confidence_score'] : 0.8;
        $optimized_price = ($base_price * (1 - $confidence)) + ($optimization_data['recommended_price'] * $confidence);
        
        return round($optimized_price, 2);
    }
    
    /**
     * Calculate base price from size and complexity
     *
     * @param string $size Artwork size
     * @param string $complexity Artwork complexity
     * @param string $style Artwork style
     * @return float Base price
     */
    public static function calculate_base_price($size, $complexity, $style) {
        // Size multipliers
        $size_multipliers = array(
            'small' => 1,
            'medium' => 1.5,
            'large' => 2.5,
            'custom' => 3,
        );
        
        // Complexity multipliers
        $complexity_multipliers = array(
            'simple' => 1,
            'standard' => 1.5,
            'complex' => 2,
            'premium' => 3,
        );
        
        // Style base prices
        $style_base_prices = array(
            'abstract' => 120,
            'portrait' => 150,
            'landscape' => 130,
            'digital' => 100,
            'photography' => 80,
            'sculpture' => 200,
            'modern' => 140,
            'traditional' => 160,
            'anime' => 90,
            'fantasy' => 110,
        );
        
        // Default base price if style not found
        $base_style_price = isset($style_base_prices[$style]) ? $style_base_prices[$style] : 120;
        
        // Get multipliers (default to medium/standard if not valid)
        $size_multiplier = isset($size_multipliers[$size]) ? $size_multipliers[$size] : $size_multipliers['medium'];
        $complexity_multiplier = isset($complexity_multipliers[$complexity]) ? 
            $complexity_multipliers[$complexity] : $complexity_multipliers['standard'];
            
        // Calculate price
        $price = $base_style_price * $size_multiplier * $complexity_multiplier;
        
        return round($price, 2);
    }
    
    // Helper methods for simulated data
    
    private static function get_simulated_average_price($style, $size) {
        $style_multipliers = array(
            'abstract' => 1.2,
            'portrait' => 1.5,
            'landscape' => 1.3,
            'digital' => 0.9,
            'photography' => 0.8,
            'sculpture' => 2.0,
            'modern' => 1.4,
            'traditional' => 1.6,
            'anime' => 0.85,
            'fantasy' => 1.1,
        );
        
        $size_multipliers = array(
            'small' => 50,
            'medium' => 100,
            'large' => 200,
            'custom' => 300,
        );
        
        $style_mult = isset($style_multipliers[$style]) ? $style_multipliers[$style] : 1;
        $size_base = isset($size_multipliers[$size]) ? $size_multipliers[$size] : $size_multipliers['medium'];
        
        // Add some randomness
        $variation = mt_rand(80, 120) / 100;
        
        return round($size_base * $style_mult * $variation, 2);
    }
    
    private static function get_simulated_trend($style) {
        $trending_styles = array('digital', 'anime', 'abstract', 'fantasy');
        $declining_styles = array('photography');
        
        if (in_array($style, $trending_styles)) {
            return mt_rand(0, 10) > 2 ? 'up' : 'stable';
        } elseif (in_array($style, $declining_styles)) {
            return mt_rand(0, 10) > 2 ? 'down' : 'stable';
        } else {
            $trends = array('up', 'stable', 'down');
            return $trends[mt_rand(0, 2)];
        }
    }
    
    private static function get_simulated_demand($style) {
        $high_demand_styles = array('digital', 'fantasy', 'anime');
        $low_demand_styles = array('traditional');
        
        if (in_array($style, $high_demand_styles)) {
            return mt_rand(0, 10) > 2 ? 'high' : 'medium';
        } elseif (in_array($style, $low_demand_styles)) {
            return mt_rand(0, 10) > 2 ? 'low' : 'medium';
        } else {
            $demands = array('high', 'medium', 'low');
            return $demands[mt_rand(0, 2)];
        }
    }
    
    private static function optimize_price($base_price, $style) {
        // Simulate optimization - add/subtract up to 20%
        $variation = mt_rand(-20, 20) / 100;
        return round($base_price * (1 + $variation), 2);
    }
    
    private static function get_pricing_strategy($style) {
        $strategies = array(
            'premium' => array('sculpture', 'traditional', 'portrait'),
            'competitive' => array('digital', 'photography', 'landscape'),
            'volume' => array('anime', 'abstract'),
            'balanced' => array('fantasy', 'modern')
        );
        
        foreach ($strategies as $strategy => $styles) {
            if (in_array($style, $styles)) {
                return $strategy;
            }
        }
        
        // Default strategy
        return 'balanced';
    }
} 