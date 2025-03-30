<?php
/**
 * Thorius Agent Orchestrator
 * 
 * Coordinates between CLOE, HURAII, and Business Strategist agents
 * for optimal response generation
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/agents
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Thorius Agent Orchestrator
 */
class Vortex_Thorius_Orchestrator {
    /**
     * Available agents
     */
    private $agents = array();
    
    /**
     * Analytics instance
     */
    private $analytics;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Load agents
        $this->load_agents();
        
        // Initialize analytics
        $this->analytics = new Vortex_Thorius_Analytics();
    }
    
    /**
     * Load all available agents
     */
    private function load_agents() {
        require_once plugin_dir_path(__FILE__) . 'class-vortex-thorius-cloe.php';
        require_once plugin_dir_path(__FILE__) . 'class-vortex-thorius-huraii.php';
        require_once plugin_dir_path(__FILE__) . 'class-vortex-thorius-strategist.php';
        
        $api_manager = new Vortex_Thorius_API_Manager();
        
        $this->agents['cloe'] = new Vortex_Thorius_CLOE($api_manager);
        $this->agents['huraii'] = new Vortex_Thorius_HURAII($api_manager);
        $this->agents['strategist'] = new Vortex_Thorius_Strategist($api_manager);
    }
    
    /**
     * Process query with optimal agent selection
     * 
     * @param string $query User query
     * @param array $context Conversation context
     * @param string $preferred_agent User's preferred agent (optional)
     * @return array Response data
     */
    public function process_query($query, $context = array(), $preferred_agent = '') {
        // Start performance tracking
        $start_time = microtime(true);
        
        // If preferred agent is specified and valid, use it directly
        if (!empty($preferred_agent) && isset($this->agents[$preferred_agent])) {
            $response = $this->process_with_specific_agent($preferred_agent, $query, $context);
            
            // Track performance
            $this->analytics->track_agent_performance($preferred_agent, microtime(true) - $start_time);
            
            return $response;
        }
        
        // Otherwise, use intelligent routing
        $agent = $this->determine_best_agent($query, $context);
        $response = $this->process_with_specific_agent($agent, $query, $context);
        
        // Track performance
        $this->analytics->track_agent_performance($agent, microtime(true) - $start_time);
        
        return $response;
    }
    
    /**
     * Determine the best agent for a given query
     * 
     * @param string $query User query
     * @param array $context Conversation context
     * @return string Best agent ID
     */
    private function determine_best_agent($query, $context) {
        // Check context for explicit agent selection
        if (!empty($context['agent'])) {
            return $context['agent'];
        }
        
        // Cache frequently used keywords for performance
        static $business_keywords = null;
        static $creative_keywords = null;
        static $technical_keywords = null;
        
        if ($business_keywords === null) {
            // Business domain keywords
            $business_keywords = [
                'business', 'strategy', 'market', 'revenue', 'profit', 'growth', 
                'sales', 'roi', 'investment', 'customer', 'competitor', 'finance', 
                'forecast', 'analysis', 'swot', 'monetize', 'pricing', 'startup',
                'stakeholder', 'equity', 'acquisition', 'merger', 'scaling', 'pitch',
                'entrepreneur', 'value', 'proposition', 'portfolio', 'benchmark'
            ];
            
            // Creative domain keywords
            $creative_keywords = [
                'create', 'design', 'imagine', 'story', 'art', 'visual', 
                'draw', 'paint', 'illustrate', 'creative', 'color', 'aesthetic', 
                'composition', 'style', 'beauty', 'artistic', 'inspiration', 'innovative',
                'imaginative', 'concept', 'draft', 'sketch', 'graphic', 'image', 
                'animation', 'visual', 'photography', 'cinematography', 'drawing'
            ];
            
            // Technical domain keywords
            $technical_keywords = [
                'code', 'program', 'technical', 'develop', 'build', 
                'debug', 'algorithm', 'software', 'database', 'function', 
                'application', 'system', 'technology', 'implementation', 'framework',
                'api', 'server', 'cloud', 'deployment', 'encryption', 'architecture',
                'interface', 'compile', 'platform', 'computing', 'network', 'protocol'
            ];
        }
        
        // Clean and tokenize query
        $lower_query = strtolower(trim($query));
        $query_words = preg_split('/\s+/', $lower_query);
        $total_words = count($query_words);
        
        // Initialize weighted scores
        $business_score = 0;
        $creative_score = 0;
        $technical_score = 0;
        
        // Calculate weighted scores with performance optimizations
        for ($i = 0; $i < $total_words; $i++) {
            $word = $query_words[$i];
            
            // Position weight (words at beginning have more weight)
            $position_weight = 1 - (0.5 * $i / max(1, $total_words));
            
            // Optimize keyword checking with single loop
            foreach ($business_keywords as $keyword) {
                if (strpos($word, $keyword) !== false) {
                    $business_score += $position_weight;
                    break; // Stop checking other business keywords for this word
                }
            }
            
            foreach ($creative_keywords as $keyword) {
                if (strpos($word, $keyword) !== false) {
                    $creative_score += $position_weight;
                    break; // Stop checking other creative keywords for this word
                }
            }
            
            foreach ($technical_keywords as $keyword) {
                if (strpos($word, $keyword) !== false) {
                    $technical_score += $position_weight;
                    break; // Stop checking other technical keywords for this word
                }
            }
        }
        
        // Analyze context for additional clues - optimize for performance
        if (!empty($context['conversation_history'])) {
            $history = $context['conversation_history'];
            
            // Get last agent used (if any)
            $last_agent = null;
            for ($i = count($history) - 1; $i >= max(0, count($history) - 3); $i--) {
                if (!empty($history[$i]['agent'])) {
                    $last_agent = $history[$i]['agent'];
                    break;
                }
            }
            
            // Add continuity bonus
            if ($last_agent) {
                switch ($last_agent) {
                    case 'huraii':
                        $creative_score += 0.5;
                        break;
                    case 'cloe':
                        $technical_score += 0.5;
                        break;
                    case 'strategist':
                        $business_score += 0.5;
                        break;
                }
            }
            
            // Check for topic continuity
            $last_query = '';
            for ($i = count($history) - 1; $i >= 0; $i--) {
                if (!empty($history[$i]['query'])) {
                    $last_query = $history[$i]['query'];
                    break;
                }
            }
            
            // If current query is very short, rely more on previous context
            if (strlen($query) < 15 && !empty($last_query)) {
                // Check for pronoun usage indicating context continuation
                $pronouns = ['it', 'this', 'that', 'they', 'these', 'those', 'he', 'she'];
                $has_pronoun = false;
                
                foreach ($pronouns as $pronoun) {
                    if (preg_match('/\b' . $pronoun . '\b/i', $query)) {
                        $has_pronoun = true;
                        break;
                    }
                }
                
                if ($has_pronoun) {
                    // Strongly favor previous agent for continuity
                    switch ($last_agent) {
                        case 'huraii':
                            $creative_score += 1.0;
                            break;
                        case 'cloe':
                            $technical_score += 1.0;
                            break;
                        case 'strategist':
                            $business_score += 1.0;
                            break;
                    }
                }
            }
        }
        
        // Consider using WP Transients for caching common queries
        $cache_key = 'thorius_agent_' . md5($query);
        $cached_agent = get_transient($cache_key);
        
        if ($cached_agent && defined('THORIUS_ENABLE_QUERY_CACHE') && THORIUS_ENABLE_QUERY_CACHE) {
            return $cached_agent;
        }
        
        // Only log scores in debug mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'Thorius agent routing scores - Business: %.2f, Creative: %.2f, Technical: %.2f',
                $business_score,
                $creative_score,
                $technical_score
            ));
        }
        
        // Return optimal agent based on scores
        if ($business_score > $creative_score && $business_score > $technical_score) {
            return 'strategist';
        } else if ($creative_score > $technical_score) {
            return 'huraii'; // CORRECT: HURAII handles creative tasks
        } else {
            return 'cloe'; // CORRECT: CLOE handles technical tasks
        }
    }
    
    /**
     * Process query with specific agent
     * 
     * @param string $agent Agent ID
     * @param string $query User query
     * @param array $context Conversation context
     * @return array Response data
     */
    private function process_with_specific_agent($agent, $query, $context) {
        if (!isset($this->agents[$agent])) {
            return array(
                'success' => false,
                'message' => sprintf(__('Agent "%s" not available', 'vortex-ai-marketplace'), $agent)
            );
        }
        
        try {
            $response = $this->agents[$agent]->process_query($query, $context);
            
            // Add agent info to response
            $response['agent'] = $agent;
            
            return $response;
        } catch (Exception $e) {
            // Log error
            error_log('Thorius Agent Error: ' . $e->getMessage());
            
            // Return error response
            return array(
                'success' => false,
                'message' => $e->getMessage(),
                'agent' => $agent
            );
        }
    }
    
    /**
     * Count keyword matches in text
     * 
     * @param string $text Text to search in
     * @param array $keywords Keywords to match
     * @return int Number of matches
     */
    private function count_keyword_matches($text, $keywords) {
        $count = 0;
        $text = strtolower($text);
        
        foreach ($keywords as $keyword) {
            if (strpos($text, strtolower($keyword)) !== false) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Get agent tabs configuration
     */
    public function get_agent_tabs() {
        return array(
            'cloe' => array(
                'title' => __('CLOE', 'vortex-ai-marketplace'),
                'description' => __('Conversational Learning and Orchestration Engine', 'vortex-ai-marketplace'),
                'settings' => array(
                    'model' => 'cloe-advanced',
                    'temperature' => 0.7,
                    'max_tokens' => 1500
                )
            ),
            'huraii' => array(
                'title' => __('HURAII', 'vortex-ai-marketplace'),
                'description' => __('Human Understanding and Responsive AI Interface', 'vortex-ai-marketplace'),
                'settings' => array(
                    'model' => 'huraii-creative',
                    'temperature' => 0.9,
                    'max_tokens' => 2000
                )
            ),
            'strategist' => array(
                'title' => __('Business Strategist', 'vortex-ai-marketplace'),
                'description' => __('AI-Powered Business Intelligence and Strategy', 'vortex-ai-marketplace'),
                'settings' => array(
                    'model' => 'strategist-pro',
                    'temperature' => 0.5,
                    'max_tokens' => 1800
                )
            )
        );
    }
    
    /**
     * Process collaborative query requiring multiple agents' expertise
     * 
     * @param string $query User query
     * @param array $context Conversation context
     * @return array Response data
     */
    public function process_collaborative_query($query, $context = array()) {
        // Start performance tracking
        $start_time = microtime(true);
        
        // Analyze query complexity
        $complexity_score = $this->analyze_query_complexity($query);
        
        // If query is not complex, process normally
        if ($complexity_score < 7) {
            return $this->process_query($query, $context);
        }
        
        // For complex queries, identify primary and secondary domains
        $domain_scores = $this->analyze_domain_distribution($query);
        arsort($domain_scores); // Sort by highest score
        
        // Get top two domains
        $domains = array_keys($domain_scores);
        $primary_domain = $domains[0];
        $secondary_domain = isset($domains[1]) ? $domains[1] : null;
        
        // Get primary agent
        $primary_agent = $this->get_agent_for_domain($primary_domain);
        
        // Process with primary agent
        $primary_response = $this->process_with_specific_agent($primary_agent, $query, $context);
        
        // If no secondary domain or score is low, return primary response
        if (!$secondary_domain || $domain_scores[$secondary_domain] < 3) {
            // Track interaction for learning
            $this->track_collaborative_interaction($query, $primary_response['content'], $primary_agent, null, $context);
            return $primary_response;
        }
        
        // Get secondary agent
        $secondary_agent = $this->get_agent_for_domain($secondary_domain);
        
        // Process focused variation with secondary agent
        $focused_query = $this->refine_query_for_domain($query, $secondary_domain);
        $secondary_response = $this->process_with_specific_agent($secondary_agent, $focused_query, $context);
        
        // Synthesize collaborative response
        $collaborative_response = $this->synthesize_responses(
            $primary_response, 
            $secondary_response,
            $domain_scores[$primary_domain],
            $domain_scores[$secondary_domain]
        );
        
        // Track collaborative interaction for learning
        $this->track_collaborative_interaction(
            $query, 
            $collaborative_response['content'], 
            $primary_agent,
            $secondary_agent,
            $context
        );
        
        return $collaborative_response;
    }
    
    /**
     * Track collaborative interaction for continuous learning
     * 
     * @param string $query User query
     * @param string $response Final response
     * @param string $primary_agent Primary agent ID
     * @param string|null $secondary_agent Secondary agent ID
     * @param array $context Context data
     */
    private function track_collaborative_interaction($query, $response, $primary_agent, $secondary_agent = null, $context = array()) {
        // Create collaborative context
        $collab_context = array_merge($context, array(
            'primary_agent' => $primary_agent,
            'secondary_agent' => $secondary_agent,
            'is_collaborative' => true
        ));
        
        // Track through main plugin's learning system
        $thorius = Vortex_Thorius::get_instance();
        $thorius->track_query_for_learning($query, $response, $collab_context);
    }
    
    /**
     * Get agent for domain
     */
    private function get_agent_for_domain($domain) {
        switch ($domain) {
            case 'business':
                return 'strategist';
            case 'creative':
                return 'huraii';
            case 'technical':
            default:
                return 'cloe';
        }
    }
    
    /**
     * Synthesize responses from multiple agents
     */
    private function synthesize_responses($primary, $secondary, $primary_weight, $secondary_weight) {
        // Calculate blend ratio based on weights
        $total_weight = $primary_weight + $secondary_weight;
        $primary_ratio = $primary_weight / $total_weight;
        
        // Create synthesized response
        $response = $primary;
        
        // Add insights from secondary response
        if (!empty($secondary['content'])) {
            $response['content'] = $this->blend_content(
                $primary['content'],
                $secondary['content'],
                $primary_ratio
            );
        }
        
        // Mark as collaborative
        $response['collaborative'] = true;
        $response['contributing_agents'] = [
            'primary' => $primary['agent'],
            'secondary' => $secondary['agent'],
            'weights' => [
                $primary['agent'] => $primary_weight,
                $secondary['agent'] => $secondary_weight
            ]
        ];
        
        return $response;
    }
} 