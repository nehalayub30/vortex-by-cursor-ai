<?php
/**
 * Thorius Strategist Agent Class
 * 
 * Adapter for Business Strategist AI agent
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/agents
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Thorius Strategist Agent Class
 */
class Vortex_Thorius_Strategist {
    
    /**
     * API Manager instance
     *
     * @var Vortex_Thorius_API_Manager
     */
    private $api_manager;
    
    /**
     * Constructor
     *
     * @param Vortex_Thorius_API_Manager $api_manager API Manager instance
     */
    public function __construct($api_manager) {
        $this->api_manager = $api_manager;
    }
    
    /**
     * Process query
     *
     * @param string $query User query
     * @param array $context Conversation context
     * @return array Response data
     */
    public function process_query($query, $context = array()) {
        try {
            // Determine type of strategic query
            if ($this->is_pricing_request($query)) {
                return $this->process_pricing_request($query, $context);
            } elseif ($this->is_market_analysis_request($query)) {
                return $this->process_market_analysis_request($query, $context);
            } elseif ($this->is_trend_request($query)) {
                return $this->process_trend_request($query, $context);
            } else {
                return $this->process_general_strategy_request($query, $context);
            }
        } catch (Exception $e) {
            return $this->get_error_response($e->getMessage());
        }
    }
    
    /**
     * Process admin query with strategic analysis
     *
     * @param string $query Admin query
     * @param array $data_sources Data sources to include
     * @return array Response data
     */
    public function process_admin_query($query, $data_sources = array()) {
        try {
            // Create enhanced context from data sources
            $context = $this->prepare_admin_context($data_sources);
            
            // Get strategic analysis
            $system_prompt = $this->get_admin_system_prompt();
            $response = $this->api_manager->generate_completion($query, $system_prompt, $context);
            
            // Format the response
            return array(
                'success' => true,
                'narrative' => $response,
                'data_points' => $this->extract_data_points($response),
                'recommendations' => $this->extract_recommendations($response)
            );
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * Check if query is a pricing request
     *
     * @param string $query User query
     * @return bool True if pricing request
     */
    private function is_pricing_request($query) {
        $pricing_keywords = array(
            'price', 'pricing', 'worth', 'value', 'cost', 'charge',
            'how much', 'what price', 'what should i charge', 'priced'
        );
        
        $query_lower = strtolower($query);
        
        foreach ($pricing_keywords as $keyword) {
            if (strpos($query_lower, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if query is a market analysis request
     *
     * @param string $query User query
     * @return bool True if market analysis request
     */
    private function is_market_analysis_request($query) {
        $market_keywords = array(
            'market', 'analysis', 'analyze', 'industry', 'marketplace',
            'market data', 'market research', 'competitor', 'competition'
        );
        
        $query_lower = strtolower($query);
        
        foreach ($market_keywords as $keyword) {
            if (strpos($query_lower, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if query is a trend request
     *
     * @param string $query User query
     * @return bool True if trend request
     */
    private function is_trend_request($query) {
        $trend_keywords = array(
            'trend', 'forecast', 'prediction', 'future', 'upcoming',
            'next year', 'next month', 'predict', 'trending'
        );
        
        $query_lower = strtolower($query);
        
        foreach ($trend_keywords as $keyword) {
            if (strpos($query_lower, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Process pricing request
     *
     * @param string $query User query
     * @param array $context Conversation context
     * @return array Response data
     */
    private function process_pricing_request($query, $context) {
        // Extract artwork details from query or context
        $artwork_details = $this->extract_artwork_details($query, $context);
        
        // Create specialized pricing prompt
        $system_prompt = "You are a pricing strategy expert for digital art and NFTs. Provide detailed, data-backed pricing advice with specific numbers and ranges. Consider the artist's reputation, artwork uniqueness, market conditions, and comparable sales. Always justify your pricing recommendations.";
        
        $pricing_prompt = "I need professional pricing advice for artwork with these details:\n";
        $pricing_prompt .= "Artwork type: " . $artwork_details['type'] . "\n";
        $pricing_prompt .= "Style: " . $artwork_details['style'] . "\n";
        $pricing_prompt .= "Medium: " . $artwork_details['medium'] . "\n";
        $pricing_prompt .= "Artist experience: " . $artwork_details['artist_experience'] . "\n";
        $pricing_prompt .= "Additional context: " . $query;
        
        // Generate response
        $response = $this->api_manager->generate_completion($pricing_prompt, $system_prompt);
        
        return array(
            'success' => true,
            'response' => $response,
            'artwork_details' => $artwork_details,
            'type' => 'pricing_advice'
        );
    }
    
    /**
     * Process market analysis request
     *
     * @param string $query User query
     * @param array $context Conversation context
     * @return array Response data
     */
    private function process_market_analysis_request($query, $context) {
        // Extract market details from query
        $market_details = $this->extract_market_details($query);
        
        // Create specialized market analysis prompt
        $system_prompt = "You are a market analysis expert for digital art, NFTs, and creative industries. Provide comprehensive, data-driven market analysis with specific insights about market size, trends, competition, and opportunities. Include both current state and short-term outlook.";
        
        $market_prompt = "Provide a market analysis for:\n";
        $market_prompt .= "Market segment: " . $market_details['segment'] . "\n";
        $market_prompt .= "Timeframe: " . $market_details['timeframe'] . "\n";
        $market_prompt .= "Specific focus: " . $market_details['focus'] . "\n";
        $market_prompt .= "Additional context: " . $query;
        
        // Generate response
        $response = $this->api_manager->generate_completion($market_prompt, $system_prompt);
        
        return array(
            'success' => true,
            'response' => $response,
            'market_details' => $market_details,
            'type' => 'market_analysis'
        );
    }
    
    /**
     * Process trend request
     *
     * @param string $query User query
     * @param array $context Conversation context
     * @return array Response data
     */
    private function process_trend_request($query, $context) {
        // Extract trend details from query
        $trend_details = $this->extract_trend_details($query);
        
        // Create specialized trend analysis prompt
        $system_prompt = "You are a trend forecasting expert for digital art, NFTs, and creative industries. Provide well-reasoned predictions with specific timeframes and metrics. Include emerging technologies, artistic styles, market movements, and consumer behavior. Balance confidence with appropriate uncertainty.";
        
        $trend_prompt = "Forecast trends for:\n";
        $trend_prompt .= "Focus area: " . $trend_details['area'] . "\n";
        $trend_prompt .= "Timeframe: " . $trend_details['timeframe'] . "\n";
        $trend_prompt .= "Additional context: " . $query;
        
        // Generate response
        $response = $this->api_manager->generate_completion($trend_prompt, $system_prompt);
        
        return array(
            'success' => true,
            'response' => $response,
            'trend_details' => $trend_details,
            'type' => 'trend_forecast'
        );
    }
    
    /**
     * Process general strategy request
     *
     * @param string $query User query
     * @param array $context Conversation context
     * @return array Response data
     */
    private function process_general_strategy_request($query, $context) {
        // Create general strategy prompt
        $system_prompt = "You are the Business Strategist, an AI expert in market analysis, pricing strategies, and trend prediction for digital art and NFT markets. Your advice is data-driven, practical, and actionable. You understand market dynamics, buyer psychology, and the unique aspects of digital art valuation.";
        
        // Generate response
        $response = $this->api_manager->generate_completion($query, $system_prompt, $context);
        
        return array(
            'success' => true,
            'response' => $response,
            'type' => 'strategic_advice'
        );
    }
    
    /**
     * Extract artwork details from query or context
     *
     * @param string $query User query
     * @param array $context Conversation context
     * @return array Artwork details
     */
    private function extract_artwork_details($query, $context) {
        // Default details
        $details = array(
            'type' => 'Digital Artwork',
            'style' => 'Contemporary',
            'medium' => 'Digital',
            'artist_experience' => 'Intermediate'
        );
        
        // Check context for details
        if (isset($context['artwork_type'])) {
            $details['type'] = $context['artwork_type'];
        }
        
        if (isset($context['artwork_style'])) {
            $details['style'] = $context['artwork_style'];
        }
        
        if (isset($context['artwork_medium'])) {
            $details['medium'] = $context['artwork_medium'];
        }
        
        if (isset($context['artist_experience'])) {
            $details['artist_experience'] = $context['artist_experience'];
        }
        
        // Try to extract details from query
        $query_lower = strtolower($query);
        
        // Artwork type detection
        $artwork_types = array(
            'illustration' => 'Illustration',
            'paint' => 'Painting',
            'photo' => 'Photography',
            'portrait' => 'Portrait',
            'landscape' => 'Landscape',
            'abstract' => 'Abstract',
            'sculpture' => 'Sculpture',
            'animation' => 'Animation',
            '3d' => '3D Model',
            'generative' => 'Generative Art',
            'pixel' => 'Pixel Art',
            'nft' => 'NFT'
        );
        
        foreach ($artwork_types as $keyword => $type) {
            if (strpos($query_lower, $keyword) !== false) {
                $details['type'] = $type;
                break;
            }
        }
        
        // Try to detect artist experience level
        $experience_levels = array(
            'beginner' => 'Beginner',
            'new artist' => 'Beginner',
            'starting out' => 'Beginner',
            'intermediate' => 'Intermediate',
            'experienced' => 'Experienced',
            'professional' => 'Professional',
            'established' => 'Established',
            'expert' => 'Expert',
            'master' => 'Master',
            'renowned' => 'Renowned'
        );
        
        foreach ($experience_levels as $keyword => $level) {
            if (strpos($query_lower, $keyword) !== false) {
                $details['artist_experience'] = $level;
                break;
            }
        }
        
        return $details;
    }
    
    /**
     * Extract market details from query
     *
     * @param string $query User query
     * @return array Market details
     */
    private function extract_market_details($query) {
        // Default details
        $details = array(
            'segment' => 'Digital Art',
            'timeframe' => 'Current',
            'focus' => 'General'
        );
        
        $query_lower = strtolower($query);
        
        // Market segment detection
        $segments = array(
            'nft' => 'NFT',
            'digital art' => 'Digital Art',
            'illustration' => 'Illustration',
            'concept art' => 'Concept Art',
            'photography' => 'Photography',
            'animation' => 'Animation',
            'video' => 'Video Art',
            '3d' => '3D Art',
            'virtual reality' => 'VR Art',
            'augmented reality' => 'AR Art',
            'generative' => 'Generative Art',
            'traditional' => 'Traditional Art',
            'physical' => 'Physical Art'
        );
        
        foreach ($segments as $keyword => $segment) {
            if (strpos($query_lower, $keyword) !== false) {
                $details['segment'] = $segment;
                break;
            }
        }
        
        // Timeframe detection
        $timeframes = array(
            'current' => 'Current',
            'now' => 'Current',
            'today' => 'Current',
            'last week' => 'Last Week',
            'last month' => 'Last Month',
            'last year' => 'Last Year',
            'past year' => 'Past Year',
            'past month' => 'Past Month',
            'recent' => 'Recent',
            'upcoming' => 'Upcoming',
            'next month' => 'Next Month',
            'next year' => 'Next Year',
            'future' => 'Future',
            'long term' => 'Long Term'
        );
        
        foreach ($timeframes as $keyword => $timeframe) {
            if (strpos($query_lower, $keyword) !== false) {
                $details['timeframe'] = $timeframe;
                break;
            }
        }
        
        // Focus detection
        $focuses = array(
            'price' => 'Pricing',
            'sales' => 'Sales',
            'revenue' => 'Revenue',
            'growth' => 'Growth',
            'competitor' => 'Competition',
            'consumer' => 'Consumer Behavior',
            'trend' => 'Trends',
            'technology' => 'Technology',
            'platform' => 'Platforms',
            'demographics' => 'Demographics',
            'region' => 'Regional'
        );
        
        foreach ($focuses as $keyword => $focus) {
            if (strpos($query_lower, $keyword) !== false) {
                $details['focus'] = $focus;
                break;
            }
        }
        
        return $details;
    }
    
    /**
     * Extract trend details from query
     *
     * @param string $query User query
     * @return array Trend details
     */
    private function extract_trend_details($query) {
        // Default details
        $details = array(
            'area' => 'Digital Art',
            'timeframe' => 'Next 12 Months'
        );
        
        $query_lower = strtolower($query);
        
        // Area detection
        $areas = array(
            'nft' => 'NFT Market',
            'digital art' => 'Digital Art',
            'traditional art' => 'Traditional Art',
            'physical art' => 'Physical Art',
            'generative' => 'Generative Art',
            'ai art' => 'AI-Generated Art',
            'style' => 'Art Styles',
            'techniques' => 'Art Techniques',
            'platform' => 'Art Platforms',
            'marketplace' => 'Art Marketplaces',
            'technology' => 'Art Technology',
            'collector' => 'Collector Behavior',
            'buyer' => 'Buyer Behavior',
            'investment' => 'Art Investment'
        );
        
        foreach ($areas as $keyword => $area) {
            if (strpos($query_lower, $keyword) !== false) {
                $details['area'] = $area;
                break;
            }
        }
        
        // Timeframe detection
        $timeframes = array(
            'next month' => 'Next Month',
            'next 3 months' => 'Next 3 Months',
            'next quarter' => 'Next Quarter',
            'next 6 months' => 'Next 6 Months',
            'next year' => 'Next 12 Months',
            '2023' => '2023',
            '2024' => '2024',
            '2025' => '2025',
            'next 2 years' => 'Next 2 Years',
            'next 5 years' => 'Next 5 Years',
            'long term' => 'Long Term',
            'near future' => 'Near Future',
            'distant future' => 'Distant Future'
        );
        
        foreach ($timeframes as $keyword => $timeframe) {
            if (strpos($query_lower, $keyword) !== false) {
                $details['timeframe'] = $timeframe;
                break;
            }
        }
        
        return $details;
    }
    
    /**
     * Prepare admin context from data sources
     *
     * @param array $data_sources Data sources to include
     * @return array Enhanced context
     */
    private function prepare_admin_context($data_sources) {
        $context = array();
        
        // Add sales data if available
        if (isset($data_sources['sales']) && !empty($data_sources['sales'])) {
            $context['sales_data'] = $this->summarize_sales_data($data_sources['sales']);
        }
        
        // Add user data if available
        if (isset($data_sources['users']) && !empty($data_sources['users'])) {
            $context['user_data'] = $this->summarize_user_data($data_sources['users']);
        }
        
        // Add market data if available
        if (isset($data_sources['market']) && !empty($data_sources['market'])) {
            $context['market_data'] = $this->summarize_market_data($data_sources['market']);
        }
        
        // Add content data if available
        if (isset($data_sources['content']) && !empty($data_sources['content'])) {
            $context['content_data'] = $this->summarize_content_data($data_sources['content']);
        }
        
        return $context;
    }
    
    /**
     * Get admin system prompt
     *
     * @return string System prompt
     */
    private function get_admin_system_prompt() {
        return "You are the Business Intelligence Advisor for VORTEX, an AI-powered art marketplace. You specialize in analyzing marketplace data, user behavior, sales trends, and content performance to provide strategic insights for business decisions. Your analysis should be comprehensive, data-driven, and include actionable recommendations. Format your response with clear sections for Summary, Key Findings, Data Analysis, and Strategic Recommendations. Use precise numbers and percentages when available, and indicate confidence levels for predictions.";
    }
    
    /**
     * Extract data points from response
     *
     * @param string $response AI response
     * @return array Extracted data points
     */
    private function extract_data_points($response) {
        // This is a simplified implementation
        // In a real-world scenario, this would use more sophisticated parsing
        $data_points = array();
        
        // Look for percentages
        preg_match_all('/(\d+(?:\.\d+)?)%/', $response, $percentage_matches);
        if (!empty($percentage_matches[0])) {
            $data_points['percentages'] = array_slice($percentage_matches[0], 0, 5);
        }
        
        // Look for dollar amounts
        preg_match_all('/\$(\d+(?:,\d+)*(?:\.\d+)?)/', $response, $dollar_matches);
        if (!empty($dollar_matches[0])) {
            $data_points['dollar_amounts'] = array_slice($dollar_matches[0], 0, 5);
        }
        
        // Look for time periods
        preg_match_all('/(January|February|March|April|May|June|July|August|September|October|November|December|Q[1-4]|[1-4]Q|20\d\d)/', $response, $time_matches);
        if (!empty($time_matches[0])) {
            $data_points['time_periods'] = array_slice($time_matches[0], 0, 5);
        }
        
        return $data_points;
    }
    
    /**
     * Extract recommendations from response
     *
     * @param string $response AI response
     * @return array Extracted recommendations
     */
    private function extract_recommendations($response) {
        $recommendations = array();
        
        // Look for recommendation section
        if (preg_match('/(?:Strategic Recommendations|Recommendations):(.*?)(?:\n\n|\n##|\Z)/s', $response, $matches)) {
            $recommendation_text = trim($matches[1]);
            
            // Look for bullet points
            preg_match_all('/(?:\n|\A)\s*(?:-|\*|\d+\.)\s+(.*?)(?=\n\s*(?:-|\*|\d+\.)|$)/s', $recommendation_text, $bullet_matches);
            
            if (!empty($bullet_matches[1])) {
                $recommendations = array_map('trim', $bullet_matches[1]);
            } else {
                // If no bullet points, split by sentences
                $sentences = preg_split('/(?<=[.!?])\s+/', $recommendation_text);
                $recommendations = array_filter(array_map('trim', $sentences));
            }
        }
        
        return $recommendations;
    }
    
    /**
     * Summarize sales data
     *
     * @param array $sales_data Raw sales data
     * @return string Summarized data
     */
    private function summarize_sales_data($sales_data) {
        // Implement summarization logic
        return json_encode($sales_data);
    }
    
    /**
     * Summarize user data
     *
     * @param array $user_data Raw user data
     * @return string Summarized data
     */
    private function summarize_user_data($user_data) {
        // Implement summarization logic
        return json_encode($user_data);
    }
    
    /**
     * Summarize market data
     *
     * @param array $market_data Raw market data
     * @return string Summarized data
     */
    private function summarize_market_data($market_data) {
        // Implement summarization logic
        return json_encode($market_data);
    }
    
    /**
     * Summarize content data
     *
     * @param array $content_data Raw content data
     * @return string Summarized data
     */
    private function summarize_content_data($content_data) {
        // Implement summarization logic
        return json_encode($content_data);
    }
    
    /**
     * Get error response
     *
     * @param string $message Error message
     * @return array Error response data
     */
    private function get_error_response($message) {
        return array(
            'success' => false,
            'response' => 'I\'m sorry, but I couldn\'t process your strategic request at this time.',
            'error' => $message
        );
    }
} 