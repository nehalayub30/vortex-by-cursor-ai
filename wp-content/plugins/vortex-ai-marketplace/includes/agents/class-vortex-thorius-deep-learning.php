<?php
/**
 * Thorius Deep Learning
 * 
 * Powers the AI capabilities of Thorius agents
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/agents
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Thorius Deep Learning Integration
 */
class Vortex_Thorius_Deep_Learning {
    
    /**
     * API Manager instance
     *
     * @var Vortex_Thorius_API_Manager
     */
    private $api_manager;
    
    /**
     * Cache manager
     *
     * @var Vortex_Thorius_Cache
     */
    private $cache;
    
    /**
     * Agents array
     *
     * @var array
     */
    private $agents = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize API manager
        require_once plugin_dir_path(dirname(__FILE__)) . 'api/class-vortex-thorius-api-manager.php';
        $this->api_manager = new Vortex_Thorius_API_Manager();
        
        // Initialize cache manager
        require_once plugin_dir_path(dirname(dirname(__FILE__))) . 'class-vortex-thorius-cache.php';
        $this->cache = new Vortex_Thorius_Cache('deep_learning', 24); // Cache for 24 hours
        
        // Register agent connections
        $this->register_agent_connections();
    }
    
    /**
     * Register AI agent connections
     */
    public function register_agent_connections() {
        // Register CLOE connection
        require_once plugin_dir_path(__FILE__) . 'class-vortex-thorius-cloe.php';
        $this->agents['cloe'] = new Vortex_Thorius_CLOE($this->api_manager);
        
        // Register HURAII connection
        require_once plugin_dir_path(__FILE__) . 'class-vortex-thorius-huraii.php';
        $this->agents['huraii'] = new Vortex_Thorius_HURAII($this->api_manager);
        
        // Register Strategist connection
        require_once plugin_dir_path(__FILE__) . 'class-vortex-thorius-strategist.php';
        $this->agents['strategist'] = new Vortex_Thorius_Strategist($this->api_manager);
        
        // Allow plugins to register custom agent connections
        $this->agents = apply_filters('vortex_thorius_agent_connections', $this->agents);
    }
    
    /**
     * Process query with CLOE
     * 
     * @param string $query User query
     * @param array $context Conversation context
     * @return array Response data
     */
    public function process_with_cloe($query, $context = array()) {
        if (!$this->agents['cloe']) {
            return $this->get_agent_error('cloe');
        }
        
        try {
            // Track start time for performance monitoring
            $start_time = microtime(true);
            
            // Process with CLOE
            $response = $this->agents['cloe']->process_query($query, $context);
            
            // Track performance
            $execution_time = microtime(true) - $start_time;
            $this->log_agent_performance('cloe', $execution_time, strlen($query), strlen($response['response']));
            
            return $response;
        } catch (Exception $e) {
            $this->log_agent_error('cloe', $e->getMessage());
            return $this->get_agent_error('cloe', $e->getMessage());
        }
    }
    
    /**
     * Process query with HURAII
     * 
     * @param string $query User query
     * @param array $context Conversation context
     * @return array Response data
     */
    public function process_with_huraii($query, $context = array()) {
        if (!$this->agents['huraii']) {
            return $this->get_agent_error('huraii');
        }
        
        try {
            // Track start time for performance monitoring
            $start_time = microtime(true);
            
            // Process with HURAII
            $response = $this->agents['huraii']->process_query($query, $context);
            
            // Track performance
            $execution_time = microtime(true) - $start_time;
            $this->log_agent_performance('huraii', $execution_time, strlen($query), strlen($response['response']));
            
            return $response;
        } catch (Exception $e) {
            $this->log_agent_error('huraii', $e->getMessage());
            return $this->get_agent_error('huraii', $e->getMessage());
        }
    }
    
    /**
     * Process query with Strategist
     * 
     * @param string $query User query
     * @param array $context Conversation context
     * @return array Response data
     */
    public function process_with_strategist($query, $context = array()) {
        if (!$this->agents['strategist']) {
            return $this->get_agent_error('strategist');
        }
        
        try {
            // Track start time for performance monitoring
            $start_time = microtime(true);
            
            // Process with Strategist
            $response = $this->agents['strategist']->process_query($query, $context);
            
            // Track performance
            $execution_time = microtime(true) - $start_time;
            $this->log_agent_performance('strategist', $execution_time, strlen($query), strlen($response['response']));
            
            return $response;
        } catch (Exception $e) {
            $this->log_agent_error('strategist', $e->getMessage());
            return $this->get_agent_error('strategist', $e->getMessage());
        }
    }
    
    /**
     * Process admin intelligence query
     * 
     * @param string $query Admin query
     * @param array $data_sources Data sources to include
     * @return array Response data
     */
    public function process_admin_intelligence($query, $data_sources = array()) {
        // Use Strategist as the default agent for admin intelligence
        if (!$this->agents['strategist']) {
            return $this->get_agent_error('strategist');
        }
        
        try {
            // Track start time for performance monitoring
            $start_time = microtime(true);
            
            // Process admin intelligence query with Strategist
            $response = $this->agents['strategist']->process_admin_query($query, $data_sources);
            
            // Track performance
            $execution_time = microtime(true) - $start_time;
            $this->log_agent_performance('admin_intelligence', $execution_time, strlen($query), strlen($response['narrative']));
            
            return $response;
        } catch (Exception $e) {
            $this->log_agent_error('admin_intelligence', $e->getMessage());
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * Generate chat response
     *
     * @param string $prompt User message
     * @param string $context Context for the conversation
     * @return array Response data
     */
    public function generate_chat_response($prompt, $context = '') {
        $cache_key = 'chat_' . md5($prompt . $context);
        $cached = $this->cache->get($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $system_prompt = $this->get_agent_system_prompt('cloe');
        
        if (!empty($context)) {
            $system_prompt .= " Context: $context";
        }
        
        $response = $this->api_manager->generate_chat_completion($prompt, $system_prompt);
        
        if (is_wp_error($response)) {
            return array(
                'response' => __('I apologize, but I encountered an error processing your request. Please try again later.', 'vortex-ai-marketplace'),
                'error' => $response->get_error_message()
            );
        }
        
        $result = array(
            'response' => $response,
            'timestamp' => time()
        );
        
        $this->cache->set($cache_key, $result);
        
        return $result;
    }
    
    /**
     * Generate artwork
     *
     * @param string $prompt Artwork description
     * @param string $style Art style
     * @param string $size Image size
     * @return array Response data
     */
    public function generate_artwork($prompt, $style = 'realistic', $size = '1024x1024') {
        $cache_key = 'artwork_' . md5($prompt . $style . $size);
        $cached = $this->cache->get($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $formatted_prompt = $this->format_artwork_prompt($prompt, $style);
        
        $image_url = $this->api_manager->generate_image($formatted_prompt, $style, $size);
        
        if (is_wp_error($image_url)) {
            return array(
                'response' => __('I apologize, but I encountered an error generating your artwork. Please try again later.', 'vortex-ai-marketplace'),
                'error' => $image_url->get_error_message()
            );
        }
        
        // Save image to media library
        $attachment_id = $this->save_image_to_media_library($image_url, $prompt);
        
        $result = array(
            'image_url' => $image_url,
            'attachment_id' => $attachment_id,
            'prompt' => $prompt,
            'style' => $style,
            'timestamp' => time()
        );
        
        $this->cache->set($cache_key, $result);
        
        return $result;
    }
    
    /**
     * Modify existing artwork
     *
     * @param string $image_url URL of image to modify
     * @param string $prompt Description of modification
     * @param string $modification_type Type of modification
     * @return array Response data
     */
    public function modify_artwork($image_url, $prompt, $modification_type = 'edit') {
        $cache_key = 'modify_' . md5($image_url . $prompt . $modification_type);
        $cached = $this->cache->get($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        // Implementation would depend on specific API capabilities
        // Placeholder implementation
        $modified_url = $this->api_manager->modify_image($image_url, $prompt, $modification_type);
        
        if (is_wp_error($modified_url)) {
            return array(
                'response' => __('I apologize, but I encountered an error modifying your artwork. Please try again later.', 'vortex-ai-marketplace'),
                'error' => $modified_url->get_error_message()
            );
        }
        
        // Save modified image to media library
        $attachment_id = $this->save_image_to_media_library($modified_url, $prompt);
        
        $result = array(
            'image_url' => $modified_url,
            'attachment_id' => $attachment_id,
            'original_url' => $image_url,
            'prompt' => $prompt,
            'modification' => $modification_type,
            'timestamp' => time()
        );
        
        $this->cache->set($cache_key, $result);
        
        return $result;
    }
    
    /**
     * Generate market analysis
     *
     * @param string $market Market to analyze
     * @param string $timeframe Timeframe for analysis
     * @param string $focus Specific focus for analysis
     * @return array Response data
     */
    public function generate_market_analysis($market, $timeframe, $focus = '') {
        $cache_key = 'market_' . md5($market . $timeframe . $focus);
        $cached = $this->cache->get($cache_key);
        
        if ($cached !== false && !empty($cached)) {
            return $cached;
        }
        
        $system_prompt = $this->get_agent_system_prompt('strategist');
        $prompt = "Provide a detailed market analysis for the $market market over the $timeframe timeframe.";
        
        if (!empty($focus)) {
            $prompt .= " Focus on: $focus";
        }
        
        $response = $this->api_manager->generate_chat_completion($prompt, $system_prompt);
        
        if (is_wp_error($response)) {
            return array(
                'response' => __('I apologize, but I encountered an error generating the market analysis. Please try again later.', 'vortex-ai-marketplace'),
                'error' => $response->get_error_message()
            );
        }
        
        $result = array(
            'analysis' => $response,
            'market' => $market,
            'timeframe' => $timeframe,
            'focus' => $focus,
            'timestamp' => time()
        );
        
        $this->cache->set($cache_key, $result);
        
        return $result;
    }
    
    /**
     * Optimize price
     *
     * @param float $current_price Current price
     * @param string $market Target market
     * @param string $context Additional context
     * @return array Response data
     */
    public function optimize_price($current_price, $market, $context = '') {
        $cache_key = 'price_' . md5($current_price . $market . $context);
        $cached = $this->cache->get($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $system_prompt = $this->get_agent_system_prompt('strategist');
        $prompt = "Optimize the price point of $current_price for the $market market.";
        
        if (!empty($context)) {
            $prompt .= " Additional context: $context";
        }
        
        $response = $this->api_manager->generate_chat_completion($prompt, $system_prompt);
        
        if (is_wp_error($response)) {
            return array(
                'response' => __('I apologize, but I encountered an error optimizing the price. Please try again later.', 'vortex-ai-marketplace'),
                'error' => $response->get_error_message()
            );
        }
        
        $result = array(
            'analysis' => $response,
            'current_price' => $current_price,
            'market' => $market,
            'context' => $context,
            'timestamp' => time()
        );
        
        $this->cache->set($cache_key, $result);
        
        return $result;
    }
    
    /**
     * Predict market trends
     *
     * @param string $market Target market
     * @param string $timeframe Prediction timeframe
     * @param string $context Additional context
     * @return array Response data
     */
    public function predict_trends($market, $timeframe, $context = '') {
        $cache_key = 'trends_' . md5($market . $timeframe . $context);
        $cached = $this->cache->get($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        $system_prompt = $this->get_agent_system_prompt('strategist');
        $prompt = "Predict trends in the $market market over the next $timeframe period.";
        
        if (!empty($context)) {
            $prompt .= " Additional context: $context";
        }
        
        $response = $this->api_manager->generate_chat_completion($prompt, $system_prompt);
        
        if (is_wp_error($response)) {
            return array(
                'response' => __('I apologize, but I encountered an error predicting trends. Please try again later.', 'vortex-ai-marketplace'),
                'error' => $response->get_error_message()
            );
        }
        
        $result = array(
            'analysis' => $response,
            'market' => $market,
            'timeframe' => $timeframe,
            'context' => $context,
            'timestamp' => time()
        );
        
        $this->cache->set($cache_key, $result);
        
        return $result;
    }
    
    /**
     * Search for artworks
     *
     * @param string $query Search query
     * @param array $filters Search filters
     * @return array Response data
     */
    public function search_artworks($query, $filters = array()) {
        $cache_key = 'search_' . md5($query . serialize($filters));
        $cached = $this->cache->get($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        // This would typically connect to your artwork database
        // Mock implementation for demonstration
        $system_prompt = "You are an art search assistant. Respond with JSON format listing artworks that match the query.";
        $prompt = "Find artworks matching: $query. ";
        
        if (!empty($filters)) {
            $prompt .= "Filters: " . json_encode($filters);
        }
        
        $response = $this->api_manager->generate_chat_completion($prompt, $system_prompt);
        
        if (is_wp_error($response)) {
            return array(
                'response' => __('I apologize, but I encountered an error searching for artworks. Please try again later.', 'vortex-ai-marketplace'),
                'error' => $response->get_error_message()
            );
        }
        
        $result = array(
            'results' => $response,
            'query' => $query,
            'filters' => $filters,
            'timestamp' => time()
        );
        
        $this->cache->set($cache_key, $result);
        
        return $result;
    }
    
    /**
     * Get system prompt for agent
     *
     * @param string $agent Agent identifier
     * @return string System prompt
     */
    private function get_agent_system_prompt($agent) {
        switch ($agent) {
            case 'cloe':
                return "You are CLOE (Creative Learning Optimized Entity), an AI assistant specializing in art, creativity, and digital content creation. You are friendly, helpful, and knowledgeable about art history, techniques, styles, and digital art tools. Your responses should be informative but concise, and you should always aim to inspire creativity.";
                
            case 'huraii':
                return "You are HURAII (Human-Understanding Rendering Artificial Intelligence Integration), an AI specializing in artwork generation. You excel at creating detailed, high-quality visuals based on textual descriptions. Provide detailed descriptions of the artwork you would create.";
                
            case 'strategist':
                return "You are the Business Strategist, an AI expert in market analysis, pricing strategies, and trend prediction for digital art and NFT markets. Your advice is data-driven, practical, and actionable. You understand market dynamics, buyer psychology, and the unique aspects of digital art valuation.";
                
            default:
                return "You are Thorius AI Concierge, an intelligent assistant focusing on digital art, NFTs, and creative business strategies.";
        }
    }
    
    /**
     * Format artwork prompt
     *
     * @param string $prompt User prompt
     * @param string $style Desired style
     * @return string Formatted prompt
     */
    private function format_artwork_prompt($prompt, $style) {
        $style_descriptions = array(
            'realistic' => 'photorealistic, detailed, high-resolution',
            'abstract' => 'abstract, non-representational, focusing on color, form, and line',
            'digital-art' => 'digital art style, vibrant colors, clean lines',
            'watercolor' => 'watercolor painting, soft edges, translucent colors',
            'oil-painting' => 'oil painting, textured brushstrokes, rich colors',
            'cartoon' => 'cartoon style, bold outlines, simplified forms',
        );
        
        $style_desc = isset($style_descriptions[$style]) ? $style_descriptions[$style] : $style;
        
        return "$prompt, $style_desc";
    }
    
    /**
     * Save image to media library
     *
     * @param string $image_url URL of image to save
     * @param string $title Image title
     * @return int|false Attachment ID or false on failure
     */
    private function save_image_to_media_library($image_url, $title) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        // Download file to temp location
        $tmp = download_url($image_url);
        
        if (is_wp_error($tmp)) {
            return false;
        }
        
        $file_array = array(
            'name' => sanitize_title($title) . '.png',
            'tmp_name' => $tmp
        );
        
        // Do the validation and storage
        $id = media_handle_sideload($file_array, 0, $title);
        
        // Clean up
        if (file_exists($tmp)) {
            @unlink($tmp);
        }
        
        return is_wp_error($id) ? false : $id;
    }
} 