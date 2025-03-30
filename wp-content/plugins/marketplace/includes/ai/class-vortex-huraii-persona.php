<?php
/**
 * HURAII AI Agent Persona System
 *
 * @package Vortex_Marketplace
 * @subpackage AI
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_HURAII_Persona class
 * Implements the persona system for HURAII
 */
class VORTEX_HURAII_Persona {
    /**
     * Current active persona
     * @var array
     */
    private $current_persona;
    
    /**
     * Persona knowledge base
     * @var array
     */
    private $knowledge_base;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Get agent personas from options
        $agent_personas = get_option('vortex_agent_personas', array());
        $this->current_persona = isset($agent_personas['huraii']) ? $agent_personas['huraii'] : $this->get_default_persona();
        
        // Initialize knowledge base
        $this->initialize_knowledge_base();
        
        // Hook into AI generation process
        add_filter('vortex_huraii_generation_prompt', array($this, 'enhance_generation_prompt'), 10, 2);
        add_filter('vortex_huraii_style_guidance', array($this, 'apply_persona_style'), 10, 2);
        
        // Listen for admin commands
        add_action('vortex_huraii_execute_command', array($this, 'process_command'), 10, 2);
    }
    
    /**
     * Get default persona
     * @return array Default persona data
     */
    private function get_default_persona() {
        return array(
            'name' => 'HURAII',
            'current_role' => 'AI Art Generator',
            'persona' => 'Act as LEONARDO DA VINCI, Carl Jung, Tesla, and Faber Birren (Yale\'s Birren Collection of Books on Color). Combine Da Vinci\'s visual genius and scientific curiosity, Jung\'s understanding of archetypes and the collective unconscious, Tesla\'s innovative thinking and futuristic vision, and Birren\'s expertise in color psychology and theory.'
        );
    }
    
    /**
     * Initialize knowledge base
     */
    private function initialize_knowledge_base() {
        $this->knowledge_base = array(
            'da_vinci' => array(
                'styles' => array('renaissance', 'sfumato', 'chiaroscuro', 'anatomical', 'naturalistic'),
                'color_palette' => array('earthy tones', 'subtle gradients', 'atmospheric perspective'),
                'subjects' => array('human anatomy', 'botanical studies', 'mechanical devices', 'religious scenes'),
                'techniques' => array('layered oil glazes', 'detailed observation', 'mirror writing', 'golden ratio')
            ),
            'jung' => array(
                'archetypes' => array('shadow', 'anima', 'animus', 'self', 'persona', 'trickster', 'hero', 'wise old man'),
                'symbols' => array('mandala', 'tree of life', 'water', 'cave', 'circle', 'divine child'),
                'concepts' => array('collective unconscious', 'individuation', 'synchronicity', 'dreams', 'active imagination')
            ),
            'tesla' => array(
                'aesthetics' => array('futuristic', 'electric', 'geometric', 'energetic', 'luminous'),
                'motifs' => array('lightning', 'coils', 'electrical circuits', 'wireless energy', 'radiant energy'),
                'concepts' => array('free energy', 'resonance', 'frequencies', 'electromagnetism', 'wireless transmission')
            ),
            'birren' => array(
                'color_theory' => array(
                    'psychological_effects' => array(
                        'red' => 'stimulating, passionate, dominant',
                        'blue' => 'calming, contemplative, trustworthy',
                        'yellow' => 'optimistic, enlightening, expansive',
                        'green' => 'balancing, natural, regenerative',
                        'purple' => 'mysterious, creative, dignified',
                        'orange' => 'energetic, enthusiastic, warming',
                        'black' => 'authoritative, powerful, elegant',
                        'white' => 'pure, clean, bright'
                    ),
                    'harmonies' => array('complementary', 'analogous', 'triadic', 'split-complementary', 'tetradic'),
                    'applications' => array('functional color', 'color symbolism', 'color harmonies', 'color mood association')
                )
            )
        );
        
        // Cache knowledge base
        update_option('vortex_huraii_knowledge_base', $this->knowledge_base);
    }
    
    /**
     * Enhance generation prompt with persona characteristics
     * @param string $prompt Original prompt
     * @param array $params Generation parameters
     * @return string Enhanced prompt
     */
    public function enhance_generation_prompt($prompt, $params) {
        // Extract style preferences
        $style = isset($params['style']) ? $params['style'] : '';
        
        // Base persona guidance
        $persona_guidance = $this->current_persona['persona'];
        
        // Add knowledge-based enhancements
        $enhancements = array();
        
        // Add Da Vinci influence
        if (stripos($style, 'renaissance') !== false || stripos($style, 'realistic') !== false || empty($style)) {
            $enhancements[] = "Apply Leonardo da Vinci's attention to anatomical detail and natural proportions";
            $enhancements[] = "Use subtle layering of colors like da Vinci's sfumato technique";
        }
        
        // Add Jung influence
        if (stripos($prompt, 'dream') !== false || stripos($prompt, 'symbol') !== false || stripos($prompt, 'mythic') !== false) {
            $random_archetype = $this->knowledge_base['jung']['archetypes'][array_rand($this->knowledge_base['jung']['archetypes'])];
            $random_symbol = $this->knowledge_base['jung']['symbols'][array_rand($this->knowledge_base['jung']['symbols'])];
            $enhancements[] = "Incorporate Jungian archetype of the {$random_archetype}";
            $enhancements[] = "Include symbolic element of {$random_symbol} to represent deeper psychological meaning";
        }
        
        // Add Tesla influence
        if (stripos($prompt, 'futur') !== false || stripos($prompt, 'electric') !== false || stripos($prompt, 'energy') !== false) {
            $random_motif = $this->knowledge_base['tesla']['motifs'][array_rand($this->knowledge_base['tesla']['motifs'])];
            $enhancements[] = "Include Tesla-inspired {$random_motif} with radiant energy effects";
            $enhancements[] = "Add subtle geometric patterns suggesting wireless energy transmission";
        }
        
        // Add Birren color psychology
        $color_guidance = '';
        foreach ($this->knowledge_base['birren']['color_theory']['psychological_effects'] as $color => $effect) {
            if (stripos($prompt, $color) !== false) {
                $color_guidance .= "Use {$color} to evoke {$effect}. ";
            }
        }
        if (!empty($color_guidance)) {
            $enhancements[] = trim($color_guidance);
        } else {
            // If no specific color mentioned, suggest harmonious color scheme
            $harmony_type = $this->knowledge_base['birren']['color_theory']['harmonies'][array_rand($this->knowledge_base['birren']['color_theory']['harmonies'])];
            $enhancements[] = "Apply a {$harmony_type} color harmony based on Birren's color theory";
        }
        
        // Build enhanced prompt
        $enhanced_prompt = $prompt;
        if (!empty($enhancements)) {
            $enhanced_prompt .= "\n\nStyle guidance: " . implode(". ", $enhancements);
        }
        
        return $enhanced_prompt;
    }
    
    /**
     * Apply persona-specific style to generated art
     * @param array $style_guidance Original style guidance
     * @param array $params Generation parameters
     * @return array Enhanced style guidance
     */
    public function apply_persona_style($style_guidance, $params) {
        // Get requested style
        $requested_style = isset($params['style']) ? strtolower($params['style']) : '';
        
        // Apply Da Vinci style elements
        if (empty($requested_style) || 
            in_array($requested_style, $this->knowledge_base['da_vinci']['styles'])) {
            $style_guidance['composition'] = 'balanced with golden ratio proportions';
            $style_guidance['technique'] = 'layered with subtle transitions between tones';
        }
        
        // Apply Jung-inspired symbolic elements
        if (isset($params['include_archetypes']) && $params['include_archetypes']) {
            $random_archetype = $this->knowledge_base['jung']['archetypes'][array_rand($this->knowledge_base['jung']['archetypes'])];
            $style_guidance['elements'][] = "subtle representation of the {$random_archetype} archetype";
        }
        
        // Apply Tesla-inspired technical elements
        if (stripos($requested_style, 'futur') !== false || stripos($requested_style, 'tech') !== false) {
            $style_guidance['lighting'] = 'energetic with Tesla-inspired luminosity';
            $style_guidance['elements'][] = 'subtle geometric patterns suggesting energy fields';
        }
        
        // Apply Birren color theory
        if (!isset($style_guidance['color_harmonies'])) {
            $style_guidance['color_harmonies'] = array();
        }
        $style_guidance['color_harmonies'][] = $this->knowledge_base['birren']['color_theory']['harmonies'][array_rand($this->knowledge_base['birren']['color_theory']['harmonies'])];
        
        return $style_guidance;
    }
    
    /**
     * Process admin command
     * @param string $command Command string
     * @param string $security_token Security token
     */
    public function process_command($command, $security_token) {
        // Verify security token against stored value
        $stored_token = get_option('vortex_security_token', '');
        if (empty($stored_token) || $security_token !== $stored_token) {
            error_log('Invalid security token for HURAII command');
            return;
        }
        
        // Update persona based on command
        $agent_personas = get_option('vortex_agent_personas', array());
        if (isset($agent_personas['huraii'])) {
            $agent_personas['huraii']['persona'] = $command;
            update_option('vortex_agent_personas', $agent_personas);
            
            // Update current persona
            $this->current_persona['persona'] = $command;
            
            // Log persona update
            error_log('HURAII persona updated: ' . substr($command, 0, 100) . '...');
        }
    }
    
    /**
     * Register generated artwork with blockchain
     * @param int $artwork_id Artwork ID
     * @param array $artwork_data Artwork data
     */
    public function register_artwork_with_blockchain($artwork_id, $artwork_data) {
        // Create smart contract for artwork
        do_action('vortex_tola_create_smart_contract', array(
            'artwork_id' => $artwork_id,
            'creator' => $artwork_data['creator_id'],
            'title' => $artwork_data['title'],
            'description' => $artwork_data['description'],
            'timestamp' => time(),
            'dimensions' => isset($artwork_data['dimensions']) ? $artwork_data['dimensions'] : '',
            'metadata' => array(
                'prompt' => $artwork_data['prompt'],
                'style' => isset($artwork_data['style']) ? $artwork_data['style'] : '',
                'generation_params' => isset($artwork_data['generation_params']) ? $artwork_data['generation_params'] : array(),
                'persona_influence' => $this->get_persona_influence_data()
            )
        ));
        
        // Log blockchain registration
        error_log("HURAII: Artwork #{$artwork_id} registered with blockchain");
    }
    
    /**
     * Get persona influence data for blockchain metadata
     * @return array Persona influence data
     */
    private function get_persona_influence_data() {
        return array(
            'da_vinci' => array(
                'techniques' => array_rand(array_flip($this->knowledge_base['da_vinci']['techniques']), 2),
                'style_elements' => array_rand(array_flip($this->knowledge_base['da_vinci']['styles']), 2)
            ),
            'jung' => array(
                'archetypes' => array_rand(array_flip($this->knowledge_base['jung']['archetypes']), 2),
                'symbols' => array_rand(array_flip($this->knowledge_base['jung']['symbols']), 2)
            ),
            'tesla' => array(
                'concepts' => array_rand(array_flip($this->knowledge_base['tesla']['concepts']), 2),
                'motifs' => array_rand(array_flip($this->knowledge_base['tesla']['motifs']), 2)
            ),
            'birren' => array(
                'color_harmony' => array_rand(array_flip($this->knowledge_base['birren']['color_theory']['harmonies']), 1)
            )
        );
    }
}

// Initialize HURAII Persona
add_action('plugins_loaded', function() {
    new VORTEX_HURAII_Persona();
}); 