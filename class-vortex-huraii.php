<?php
/**
 * HURAII - AI Art Generation System
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage AI_Processing
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_HURAII Class
 * 
 * Core class for the HURAII AI art generation system, providing text-to-image,
 * style transfer, artwork analysis, and Seed Art technique implementation.
 *
 * @since 1.0.0
 */
class VORTEX_HURAII {
    /**
     * Instance of this class.
     *
     * @since 1.0.0
     * @var object
     */
    protected static $instance = null;
    
    /**
     * Active AI models
     *
     * @since 1.0.0
     * @var array
     */
    private $active_models = array();
    
    /**
     * Model loader instance
     *
     * @since 1.0.0
     * @var VORTEX_Model_Loader
     */
    private $model_loader;
    
    /**
     * Image processor instance
     *
     * @since 1.0.0
     * @var VORTEX_Img2Img
     */
    private $img_processor;
    
    /**
     * Default generation parameters
     *
     * @since 1.0.0
     * @var array
     */
    private $default_params = array(
        'width' => 1024,
        'height' => 1024,
        'steps' => 50,
        'cfg_scale' => 7.5,
        'seed' => -1,
        'sampler' => 'k_euler_ancestral',
        'seed_art_enabled' => true,
        'layer_optimization' => true
    );
    
    /**
     * HURAII welcome messages
     *
     * @since 1.0.0
     * @var array
     */
    private $welcome_messages = array(
        "Welcome back! Ready to unleash your artistic vision today?",
        "Every stroke of creativity matters. Let's create something remarkable!",
        "The canvas awaits your imagination. What shall we create today?",
        "Inspiration is everywhere. Let me help you transform it into art.",
        "Your unique vision deserves to be seen. Let's make it happen."
    );
    
    /**
     * Registered models
     *
     * @since 1.0.0
     * @var array
     */
    private $registered_models = array();
    
    /**
     * Constructor
     *
     * @since 1.0.0
     */
    private function __construct() {
        $this->model_loader = VORTEX_Model_Loader::get_instance();
        $this->img_processor = VORTEX_Img2Img::get_instance();
        
        // Register default models
        $this->register_default_models();
        $this->register_extended_models();
        
        // Setup hooks
        $this->setup_hooks();
    }
    
    /**
     * Get instance of this class.
     *
     * @since 1.0.0
     * @return VORTEX_HURAII
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Setup hooks
     *
     * @since 1.0.0
     * @return void
     */
    private function setup_hooks() {
        // AJAX handlers
        add_action('wp_ajax_vortex_generate_artwork', array($this, 'ajax_generate_artwork'));
        add_action('wp_ajax_vortex_analyze_artwork', array($this, 'ajax_analyze_artwork'));
        add_action('wp_ajax_vortex_get_seed_art_analysis', array($this, 'ajax_get_seed_art_analysis'));
        
        // Filter hooks for AI integrations
        add_filter('vortex_ai_get_huraii_analysis', array($this, 'get_artwork_analysis'), 10, 1);
        add_filter('vortex_ai_get_huraii_samples', array($this, 'get_generated_samples'), 10, 1);
        add_filter('vortex_ai_get_huraii_welcome', array($this, 'get_welcome_message'), 10, 1);
        
        // Actions for tracking and learning
        add_action('vortex_ai_track_interaction', array($this, 'track_user_interaction'), 10, 1);
    }
    
    /**
     * Register default AI models
     *
     * @since 1.0.0
     * @return void
     */
    private function register_default_models() {
        $this->registered_models = array(
            'sd-v1-5' => array(
                'name' => 'Stable Diffusion v1.5',
                'path' => VORTEX_PLUGIN_PATH . 'models/huraii/sd-v1-5',
                'formats' => array('jpg', 'png', 'webp'),
                'capabilities' => array('2d_generation')
            ),
            'sd-v2-1' => array(
                'name' => 'Stable Diffusion v2.1',
                'path' => VORTEX_PLUGIN_PATH . 'models/huraii/sd-v2-1',
                'formats' => array('jpg', 'png', 'webp'),
                'capabilities' => array('2d_generation', 'inpainting')
            ),
            'seed-art-analyzer' => array(
                'name' => 'Seed Art Analyzer',
                'path' => VORTEX_PLUGIN_PATH . 'models/huraii/seed-art-analyzer',
                'formats' => array('jpg', 'png', 'webp'),
                'capabilities' => array('seed_art_analysis')
            ),
            'layer-detector' => array(
                'name' => 'Layer Detector',
                'path' => VORTEX_PLUGIN_PATH . 'models/huraii/layer-detector',
                'formats' => array('jpg', 'png', 'psd'),
                'capabilities' => array('layer_detection', 'transparency_analysis')
            ),
            'style-transfer' => array(
                'name' => 'Style Transfer',
                'path' => VORTEX_PLUGIN_PATH . 'models/huraii/style-transfer',
                'formats' => array('jpg', 'png', 'webp'),
                'capabilities' => array('style_transfer')
            )
        );
        
        foreach ($this->registered_models as $id => $model) {
            $loaded = $this->model_loader->register_model($id, $model);
            if ($loaded && $model['active']) {
                $this->active_models[$id] = $model;
            }
        }
    }
    
    /**
     * Register additional AI models for extended format support
     */
    private function register_extended_models() {
        // 3D model generation models
        $this->registered_models['3d-generator'] = array(
            'name' => 'HURAII 3D Generator',
            'path' => VORTEX_PLUGIN_PATH . 'models/huraii/3d-generator',
            'formats' => array('obj', 'glb', 'gltf', 'fbx'),
            'capabilities' => array('3d_generation', '3d_texturing')
        );
        
        $this->registered_models['3d-sculptor'] = array(
            'name' => 'HURAII 3D Sculptor',
            'path' => VORTEX_PLUGIN_PATH . 'models/huraii/3d-sculptor',
            'formats' => array('obj', 'glb', 'gltf'),
            'capabilities' => array('3d_sculpting', '3d_refinement')
        );
        
        // Video generation models
        $this->registered_models['video-generator'] = array(
            'name' => 'HURAII Video Generator',
            'path' => VORTEX_PLUGIN_PATH . 'models/huraii/video-generator',
            'formats' => array('mp4', 'mov', 'gif'),
            'capabilities' => array('video_generation', 'animation')
        );
        
        $this->registered_models['gif-animator'] = array(
            'name' => 'HURAII GIF Animator',
            'path' => VORTEX_PLUGIN_PATH . 'models/huraii/gif-animator',
            'formats' => array('gif'),
            'capabilities' => array('gif_animation', 'looping_media')
        );
        
        // Audio generation models
        $this->registered_models['audio-generator'] = array(
            'name' => 'HURAII Audio Generator',
            'path' => VORTEX_PLUGIN_PATH . 'models/huraii/audio-generator',
            'formats' => array('mp3', 'wav'),
            'capabilities' => array('audio_generation', 'sound_design')
        );
        
        $this->registered_models['music-composer'] = array(
            'name' => 'HURAII Music Composer',
            'path' => VORTEX_PLUGIN_PATH . 'models/huraii/music-composer',
            'formats' => array('mp3', 'wav'),
            'capabilities' => array('music_composition', 'ambient_audio')
        );
        
        // Interactive formats
        $this->registered_models['interactive-generator'] = array(
            'name' => 'HURAII Interactive Generator',
            'path' => VORTEX_PLUGIN_PATH . 'models/huraii/interactive-generator',
            'formats' => array('html', 'svg'),
            'capabilities' => array('interactive_content', 'responsive_design')
        );
        
        // 4D content generation (time-based interactive)
        $this->registered_models['4d-simulator'] = array(
            'name' => 'HURAII 4D Simulator',
            'path' => VORTEX_PLUGIN_PATH . 'models/huraii/4d-simulator',
            'formats' => array('mp4', 'webgl'),
            'capabilities' => array('4d_simulation', 'temporal_generation')
        );
    }
    
    /**
     * Generate artwork from prompt
     *
     * @since 1.0.0
     * @param string $prompt Original prompt
     * @param array $settings Generation parameters
     * @return array|WP_Error Generated image data or error
     */
    public function generate_artwork($prompt, $settings = array()) {
        // Validate prompt
        if (empty($prompt)) {
            return new WP_Error('invalid_prompt', __('A valid prompt is required', 'vortex-marketplace'));
        }
        
        // Default settings
        $defaults = array(
            'width' => 512,
            'height' => 512,
            'steps' => 30,
            'seed' => rand(1, 999999),
            'model' => 'sd-v2-1',
            'guidance_scale' => 7.5,
            'format' => 'png',
            'enable_seed_art' => true
        );
        
        $settings = wp_parse_args($settings, $defaults);
        
        // Validate model
        if (!isset($this->active_models[$settings['model']])) {
            return new WP_Error('invalid_model', __('Invalid generation model', 'vortex-marketplace'));
        }
        
        // Log generation attempt
        $this->log_generation_attempt($prompt, $settings);
        
        try {
            // Enhance prompt with Seed Art techniques if enabled
            if (!empty($settings['enable_seed_art']) && $settings['enable_seed_art']) {
                $prompt = $this->enhance_prompt_with_seed_art($prompt);
            }
            
            // Determine if we should use layer optimization
            $use_layer_optimization = !empty($settings['layer_optimization']) && $settings['layer_optimization'];
            
            // Generate the image
            $generation_result = $this->model_loader->run_inference($settings['model'], array(
                'prompt' => $prompt,
                'negative_prompt' => $settings['negative_prompt'] ?? '',
                'width' => intval($settings['width']),
                'height' => intval($settings['height']),
                'steps' => intval($settings['steps']),
                'cfg_scale' => floatval($settings['guidance_scale']),
                'seed' => intval($settings['seed']),
                'sampler' => $settings['sampler'],
                'layer_optimization' => $use_layer_optimization
            ));
            
            if (is_wp_error($generation_result)) {
                return $generation_result;
            }
            
            // Post-process the image if needed
            if (!empty($settings['post_processing'])) {
                $generation_result = $this->apply_post_processing($generation_result, $settings['post_processing']);
            }
            
            // Calculate metrics
            $end_time = microtime(true);
            $generation_time = round($end_time - $start_time, 2);
            
            // Calculate estimated layers if layer optimization is enabled
            $estimated_layers = 1;
            if ($use_layer_optimization) {
                $estimated_layers = $this->analyze_layer_count($generation_result['image_data']);
            }
            
            // Prepare result
            $result = array(
                'success' => true,
                'image_url' => $generation_result['image_url'],
                'image_id' => $generation_result['image_id'],
                'generation_params' => $settings,
                'seed_used' => $generation_result['seed'],
                'generation_time' => $generation_time,
                'estimated_layers' => $estimated_layers
            );
            
            // Generate Seed Art analysis if enabled
            if (!empty($settings['enable_seed_art']) && $settings['enable_seed_art']) {
                $result['seed_art_analysis'] = $this->analyze_seed_art_components($generation_result['image_data'], $settings);
            }
            
            // Log successful generation
            $this->log_successful_generation($result);
            
            return $result;
        } catch (Exception $e) {
            return new WP_Error('generation_failed', $e->getMessage());
        }
    }
    
    /**
     * Enhance prompt with Seed Art techniques
     *
     * @since 1.0.0
     * @param string $prompt Original prompt
     * @return string Enhanced prompt
     */
    private function enhance_prompt_with_seed_art($prompt) {
        // Add Seed Art enhancements to the prompt to guide the AI
        $seed_art_enhancers = array(
            'sacred geometry' => array('golden ratio', 'sacred proportions', 'geometric harmony'),
            'color weight' => array('balanced color palette', 'harmonious color distribution'),
            'light and shadow' => array('dramatic lighting', 'balanced shadows', 'volumetric light'),
            'texture' => array('rich texture', 'detailed surface', 'tactile quality'),
            'perspective' => array('dimensional depth', 'correct perspective', 'spatial harmony'),
            'movement and layering' => array('dynamic composition', 'layered elements', 'visual flow')
        );
        
        // Only add enhancements if they're not already in the prompt
        foreach ($seed_art_enhancers as $component => $enhancers) {
            $enhancer_added = false;
            
            foreach ($enhancers as $enhancer) {
                if (stripos($prompt, $enhancer) !== false) {
                    $enhancer_added = true;
                    break;
                }
            }
            
            if (!$enhancer_added && !stripos($prompt, $component)) {
                $selected_enhancer = $enhancers[array_rand($enhancers)];
                $prompt .= ", with " . $selected_enhancer;
            }
        }
        
        return $prompt;
    }
    
    /**
     * Analyze artwork for Seed Art components
     *
     * @since 1.0.0
     * @param string $image_data Base64 encoded image data
     * @param array $params Generation parameters
     * @return array Seed Art analysis results
     */
    public function analyze_seed_art_components($image_data, $params = array()) {
        try {
            // Get the seed art analyzer model
            $analyzer_result = $this->model_loader->run_inference('seed-art-analyzer', array(
                'image_data' => $image_data,
                'analyze_components' => array(
                    'sacred_geometry',
                    'color_weight', 
                    'light_shadow',
                    'texture',
                    'perspective',
                    'artwork_size',
                    'movement_layering'
                )
            ));
            
            if (is_wp_error($analyzer_result)) {
                return array(
                    'error' => $analyzer_result->get_error_message(),
                    'greeting' => $this->get_random_welcome_message()
                );
            }
            
            // Analyze layers if enabled
            $layer_analysis = array();
            if (!empty($params['layer_analysis_enabled']) && $params['layer_analysis_enabled']) {
                $layer_count = $this->analyze_layer_count($image_data);
                $layer_analysis = array(
                    'layer_count' => $layer_count,
                    'transparency_analysis' => $this->analyze_transparency($image_data)
                );
            }
            
            // Analyze efficiency if enabled
            $efficiency_analysis = array();
            if (!empty($params['efficiency_enabled']) && $params['efficiency_enabled']) {
                $efficiency_analysis = $this->analyze_efficiency($image_data, $params);
            }
            
            // Combine all analyses
            $analysis = array_merge(
                $analyzer_result['components'],
                $layer_analysis,
                $efficiency_analysis,
                array('greeting' => $this->get_random_welcome_message())
            );
            
            return $analysis;
        } catch (Exception $e) {
            return array(
                'error' => $e->getMessage(),
                'greeting' => $this->get_random_welcome_message()
            );
        }
    }
    
    /**
     * Analyze number of layers in an image
     *
     * @since 1.0.0
     * @param string $image_data Base64 encoded image data
     * @return int Estimated layer count
     */
    public function analyze_layer_count($image_data) {
        try {
            $layer_result = $this->model_loader->run_inference('layer-detector', array(
                'image_data' => $image_data,
                'detection_type' => 'layer_count'
            ));
            
            if (is_wp_error($layer_result)) {
                return 1; // Default to 1 layer on error
            }
            
            return intval($layer_result['layer_count']);
        } catch (Exception $e) {
            return 1;
        }
    }
    
    /**
     * Analyze transparency in an image
     *
     * @since 1.0.0
     * @param string $image_data Base64 encoded image data
     * @return string Transparency analysis description
     */
    public function analyze_transparency($image_data) {
        try {
            $transparency_result = $this->model_loader->run_inference('layer-detector', array(
                'image_data' => $image_data,
                'detection_type' => 'transparency'
            ));
            
            if (is_wp_error($transparency_result)) {
                return __('Unable to analyze transparency', 'vortex');
            }
            
            return $transparency_result['transparency_description'];
        } catch (Exception $e) {
            return __('Unable to analyze transparency', 'vortex');
        }
    }
    
    /**
     * Analyze efficiency of artwork creation
     *
     * @since 1.0.0
     * @param string $image_data Base64 encoded image data
     * @param array $params Generation parameters
     * @return array Efficiency analysis
     */
    public function analyze_efficiency($image_data, $params) {
        $layer_count = $this->analyze_layer_count($image_data);
        $complexity = $this->estimate_complexity($image_data);
        
        // Estimate time based on complexity and layers
        $estimated_time_per_layer = $complexity * 15; // minutes
        $total_estimated_time = $layer_count * $estimated_time_per_layer;
        
        // Estimate optimal layer count
        $optimal_layer_count = min($layer_count, ceil($complexity * 2));
        $potential_time_saved = ($layer_count - $optimal_layer_count) * $estimated_time_per_layer;
        
        // Only provide optimization advice if we can save significant time
        $optimization_advice = '';
        if ($potential_time_saved > 30) {
            $optimization_advice = sprintf(
                __('This artwork could potentially be created with %d layers instead of %d, which could save approximately %d minutes of creation time. Consider merging similar layers and using opacity adjustments to achieve comparable results with fewer layers.', 'vortex'),
                $optimal_layer_count,
                $layer_count,
                $potential_time_saved
            );
        }
        
        return array(
            'efficiency_analysis' => sprintf(
                __('This artwork uses %d layers with a complexity rating of %d/10. The estimated creation time is approximately %d minutes.', 'vortex'),
                $layer_count,
                $complexity,
                $total_estimated_time
            ),
            'time_estimate' => sprintf(__('%d minutes', 'vortex'), $total_estimated_time),
            'optimization_advice' => $optimization_advice
        );
    }
    
    /**
     * Estimate complexity of an image
     *
     * @since 1.0.0
     * @param string $image_data Base64 encoded image data
     * @return int Complexity rating 1-10
     */
    private function estimate_complexity($image_data) {
        // Decode image data
        $image = imagecreatefromstring(base64_decode($image_data));
        if (!$image) {
            return 5; // Default to medium complexity
        }
        
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Sample the image and analyze edge density
        $samples = 50;
        $edge_count = 0;
        $max_edges = $samples * 4; // Maximum possible edges per sample
        
        for ($i = 0; $i < $samples; $i++) {
            $x = rand(1, $width - 2);
            $y = rand(1, $height - 2);
            
            // Get surrounding pixels
            $center = imagecolorat($image, $x, $y);
            $top = imagecolorat($image, $x, $y - 1);
            $right = imagecolorat($image, $x + 1, $y);
            $bottom = imagecolorat($image, $x, $y + 1);
            $left = imagecolorat($image, $x - 1, $y);
            
            // Detect edges
            if (abs($center - $top) > 10) $edge_count++;
            if (abs($center - $right) > 10) $edge_count++;
            if (abs($center - $bottom) > 10) $edge_count++;
            if (abs($center - $left) > 10) $edge_count++;
        }
        
        // Calculate complexity
        $edge_density = $edge_count / $max_edges;
        $complexity = round($edge_density * 10);
        
        // Ensure complexity is between 1-10
        $complexity = max(1, min(10, $complexity));
        
        imagedestroy($image);
        return $complexity;
    }
    
    /**
     * Apply post-processing to generated image
     *
     * @since 1.0.0
     * @param array $generation_result The original generation result
     * @param array $processing_options Post-processing options
     * @return array Updated generation result
     */
    private function apply_post_processing($generation_result, $processing_options) {
        if (empty($processing_options)) {
            return $generation_result;
        }
        
        // Apply each post-processing option
        foreach ($processing_options as $process => $value) {
            switch ($process) {
                case 'upscale':
                    if (!empty($value) && $value) {
                        $generation_result = $this->img_processor->upscale_image($generation_result);
                    }
                    break;
                    
                case 'enhance':
                    if (!empty($value) && $value) {
                        $generation_result = $this->img_processor->enhance_image($generation_result);
                    }
                    break;
                    
                case 'style_transfer':
                    if (!empty($value)) {
                        $generation_result = $this->img_processor->apply_style_transfer(
                            $generation_result,
                            $value['style_image'],
                            $value['strength'] ?? 0.75
                        );
                    }
                    break;
            }
        }
        
        return $generation_result;
    }
    
    /**
     * Get a random welcome message
     *
     * @since 1.0.0
     * @return string Welcome message
     */
    private function get_random_welcome_message() {
        return $this->welcome_messages[array_rand($this->welcome_messages)];
    }
    
    /**
     * Get artwork analysis for display
     *
     * @since 1.0.0
     * @param array $args Analysis parameters
     * @return array Analysis data
     */
    public function get_artwork_analysis($args) {
        $artwork_id = !empty($args['artwork_id']) ? intval($args['artwork_id']) : 0;
        $analysis_type = !empty($args['analysis_type']) ? sanitize_text_field($args['analysis_type']) : 'seed_art';
        
        if (!$artwork_id) {
            return array(
                'error' => __('No artwork specified for analysis', 'vortex'),
                'greeting' => $this->get_random_welcome_message()
            );
        }
        
        // Get artwork image
        $image_id = get_post_thumbnail_id($artwork_id);
        if (!$image_id) {
            return array(
                'error' => __('Artwork has no image for analysis', 'vortex'),
                'greeting' => $this->get_random_welcome_message()
            );
        }
        
        // Get image data
        $image_path = get_attached_file($image_id);
        if (!$image_path || !file_exists($image_path)) {
            return array(
                'error' => __('Artwork image file not found', 'vortex'),
                'greeting' => $this->get_random_welcome_message()
            );
        }
        
        // Read and encode image data
        $image_data = base64_encode(file_get_contents($image_path));
        
        // Check if we have cached analysis
        $cached_analysis = get_post_meta($artwork_id, '_vortex_huraii_analysis_' . $analysis_type, true);
        if (!empty($cached_analysis)) {
            return array_merge(
                $cached_analysis,
                array('greeting' => $this->get_random_welcome_message())
            );
        }
        
        // Perform new analysis
        $analysis_result = array();
        
        switch ($analysis_type) {
            case 'seed_art':
                $analysis_result = $this->analyze_seed_art_components($image_data, $args);
                break;
                
            case 'style_signature':
                $analysis_result = $this->analyze_style_signature($artwork_id, $image_data);
                break;
                
            default:
                $analysis_result = array(
                    'error' => __('Unsupported analysis type', 'vortex'),
                    'greeting' => $this->get_random_welcome_message()
                );
                break;
        }
        
        // Cache the analysis result
        if (empty($analysis_result['error'])) {
            update_post_meta($artwork_id, '_vortex_huraii_analysis_' . $analysis_type, $analysis_result);
        }
        
        return $analysis_result;
    }
    
    /**
     * Analyze style signature of an artist's work
     *
     * @since 1.0.0
     * @param int $artwork_id Artwork post ID
     * @param string $image_data Base64 encoded image data
     * @return array Style signature analysis
     */
    private function analyze_style_signature($artwork_id, $image_data) {
        // Get the artist ID from the artwork
        $artist_id = get_post_meta($artwork_id, 'vortex_artwork_artist', true);
        if (!$artist_id) {
            return array(
                'error' => __('No artist associated with this artwork', 'vortex')
            );
        }
        
        // Get other artworks by the same artist to analyze style patterns
        $artist_artworks = get_posts(array(
            'post_type' => 'vortex-artwork',
            'posts_per_page' => 5,
            'post__not_in' => array($artwork_id),
            'meta_query' => array(
                array(
                    'key' => 'vortex_artwork_artist',
                    'value' => $artist_id
                )
            )
        ));
        
        // If we don't have enough artworks to analyze, return basic analysis
        if (count($artist_artworks) < 2) {
            return $this->generate_basic_style_analysis($image_data);
        }
        
        // Collect image data from all artworks
        $artwork_images = array();
        foreach ($artist_artworks as $artwork) {
            $img_id = get_post_thumbnail_id($artwork->ID);
            if ($img_id) {
                $img_path = get_attached_file($img_id);
                if ($img_path && file_exists($img_path)) {
                    $artwork_images[] = base64_encode(file_get_contents($img_path));
                }
            }
        }
        
        // Analyze style across multiple works
        return $this->analyze_style_across_works($image_data, $artwork_images);
    }
    
    /**
     * Generate basic style analysis for a single artwork
     *
     * @since 1.0.0
     * @param string $image_data Base64 encoded image data
     * @return array Basic style analysis
     */
    private function generate_basic_style_analysis($image_data) {
        try {
            $style_result = $this->model_loader->run_inference('seed-art-analyzer', array(
                'image_data' => $image_data,
                'analysis_type' => 'style'
            ));
            
            if (is_wp_error($style_result)) {
                return array(
                    'error' => $style_result->get_error_message()
                );
            }
            
            // Generate a URL for creating similar artwork
            $create_in_style_url = add_query_arg(
                array(
                    'style_reference' => base64_encode($image_data),
                    'mode' => 'style_transfer'
                ),
                home_url('/create-with-huraii/')
            );
            
            return array(
                'style_summary' => $style_result['style_summary'],
                'key_elements' => $style_result['key_elements'],
                'signature_techniques' => $style_result['signature_techniques'],
                'create_in_style_url' => $create_in_style_url
            );
        } catch (Exception $e) {
            return array(
                'error' => $e->getMessage()
            );
        }
    }
    
    /**
     * Analyze style across multiple artworks
     *
     * @since 1.0.0
     * @param string $main_image_data Base64 encoded image data for main artwork
     * @param array $comparison_images Array of base64 encoded image data for comparison
     * @return array Style analysis across works
     */
    private function analyze_style_across_works($main_image_data, $comparison_images) {
        try {
            $style_result = $this->model_loader->run_inference('seed-art-analyzer', array(
                'image_data' => $main_image_data,
                'comparison_images' => $comparison_images,
                'analysis_type' => 'style_signature'
            ));
            
            if (is_wp_error($style_result)) {
                return array(
                    'error' => $style_result->get_error_message()
                );
            }
            
            // Generate a URL for creating artwork in this style
            $create_in_style_url = add_query_arg(
                array(
                    'style_reference' => base64_encode($main_image_data),
                    'mode' => 'style_transfer'
                ),
                home_url('/create-with-huraii/')
            );
            
            return array(
                'style_summary' => $style_result['style_summary'],
                'key_elements' => $style_result['key_elements'],
                'influences' => $style_result['influences'],
                'influences_summary' => $style_result['influences_summary'],
                'signature_techniques' => $style_result['signature_techniques'],
                'create_in_style_url' => $create_in_style_url
            );
        } catch (Exception $e) {
            return array(
                'error' => $e->getMessage()
            );
        }
    }
    
    /**
     * Get AI-generated artwork samples
     *
     * @since 1.0.0
     * @param array $args Sample generation parameters
     * @return array Generated samples
     */
    public function get_generated_samples($args) {
        $category_id = !empty($args['category_id']) ? intval($args['category_id']) : 0;
        $limit = !empty($args['limit']) ? intval($args['limit']) : 3;
        
        // Check for cached samples
        $cached_samples = get_transient('vortex_huraii_samples_' . $category_id);
        if (!empty($cached_samples)) {
            return $cached_samples;
        }
        
        // Get category info for prompts
        $category = get_term($category_id, 'vortex-artwork-category');
        if (!$category || is_wp_error($category)) {
            $category_name = __('Art', 'vortex');
            $category_description = '';
        } else {
            $category_name = $category->name;
            $category_description = $category->description;
        }
        
        // Generate sample prompts based on category
        $prompt_base = sprintf(__('%s in the style of', 'vortex'), $category_name);
        
        $style_variations = array(
            __('contemporary fine art', 'vortex'),
            __('impressionist masterpiece', 'vortex'),
            __('abstract expression', 'vortex'),
            __('digital art', 'vortex'),
            __('surrealist vision', 'vortex'),
            __('hyper-realistic rendering', 'vortex')
        );
        
        // Generate samples
        $samples = array();
        
        for ($i = 0; $i < $limit; $i++) {
            $style = $style_variations[array_rand($style_variations)];
            $prompt = $prompt_base . ' ' . $style;
            
            if (!empty($category_description)) {
                // Extract keywords from description to enhance prompt
                $keywords = $this->extract_keywords_from_text($category_description, 3);
                if (!empty($keywords)) {
                    $prompt .= ', ' . implode(', ', $keywords);
                }
            }
            
            // Add Seed Art hints
            $prompt .= ', using Seed Art technique';
            
            // Generate artwork
            $sample_result = $this->generate_artwork(array(
                'prompt' => $prompt,
                'width' => 768,
                'height' => 768,
                'steps' => 30,
                'cfg_scale' => 7.0,
                'seed' => rand(-1, 2147483647),
                'seed_art_enabled' => true,
                'layer_optimization' => true,
                'layer_analysis_enabled' => true,
                'efficiency_enabled' => true
            ));
            
            if (!is_wp_error($sample_result)) {
                $samples[] = array(
                    'title' => sprintf(__('%s in %s', 'vortex'), $category_name, $style),
                    'image_url' => $sample_result['image_url'],
                    'prompt' => $prompt,
                    'technique' => $style,
                    'seed_art_analysis' => $sample_result['seed_art_analysis'],
                    'creation_url' => add_query_arg(
                        array('prompt' => urlencode($prompt)),
                        home_url('/create-with-huraii/')
                    )
                );
            }
        }
        
        // Cache the results
        set_transient('vortex_huraii_samples_' . $category_id, $samples, 6 * HOUR_IN_SECONDS);
        
        return $samples;
    }
    
    /**
     * Get welcome message for user
     *
     * @since 1.0.0
     * @param array $args Welcome message parameters
     * @return array Welcome message data
     */
    public function get_welcome_message($args) {
        $user_id = !empty($args['user_id']) ? intval($args['user_id']) : 0;
        $last_visit = !empty($args['last_visit']) ? intval($args['last_visit']) : 0;
        $context = !empty($args['context']) ? sanitize_text_field($args['context']) : 'general';
        
        // Get base welcome message
        $welcome_message = $this->get_random_welcome_message();
        
        // Personalize based on user data if logged in
        if ($user_id) {
            $user_data = get_userdata($user_id);
            $user_first_name = $user_data->first_name;
            
            if (!empty($user_first_name)) {
                $greeting = sprintf(__('Welcome back, %s!', 'vortex'), $user_first_name);
            } else {
                $greeting = __('Welcome back!', 'vortex');
            }
            
            // Get user's activity
            $created_artworks = get_posts(array(
                'post_type' => 'vortex-artwork',
                'author' => $user_id,
                'posts_per_page' => -1,
                'fields' => 'ids'
            ));
            
            $artwork_count = count($created_artworks);
            
            // Personalize suggestion based on activity
            if ($artwork_count > 0) {
                $last_created = get_post(end($created_artworks));
                if ($last_created) {
                    $last_artwork_title = $last_created->post_title;
                    $suggestion = sprintf(
                        __('Your last creation "%s" shows your unique style. Ready to explore new creative directions today?', 'vortex'),
                        $last_artwork_title
                    );
                } else {
                    $suggestion = __('You\'ve created some impressive work. Ready to push your creative boundaries further?', 'vortex');
                }
            } else {
                $suggestion = __('I\'m excited to help you create your first artwork today. Let\'s get started!', 'vortex');
            }
        } else {
            $greeting = __('Welcome to VORTEX!', 'vortex');
            $suggestion = __('Create an account to unlock the full potential of AI-powered artwork creation and analysis.', 'vortex');
        }
        
        return array(
            'greeting' => $greeting,
            'message' => $welcome_message,
            'suggestion' => $suggestion
        );
    }
    
    /**
     * Track user interaction for learning
     *
     * @since 1.0.0
     * @param array $interaction_data Interaction data
     * @return void
     */
    public function track_user_interaction($interaction_data) {
        if (empty($interaction_data['entity_type']) || empty($interaction_data['action'])) {
            return;
        }
        
        // Only process relevant interactions
        if ($interaction_data['entity_type'] === 'artwork' || 
            $interaction_data['entity_type'] === 'generation' ||
            $interaction_data['entity_type'] === 'category') {
            
            // Anonymize user data if needed
            $user_id = !empty($interaction_data['user_id']) ? intval($interaction_data['user_id']) : 0;
            $anonymized = !empty($interaction_data['anonymized']) && $interaction_data['anonymized'];
            
            if ($anonymized) {
                $user_id = 0;
            }
            
            // Log the interaction
            $this->log_user_interaction($interaction_data, $user_id);
            
            // Update user preferences based on interaction
            if ($user_id > 0) {
                $this->update_user_preferences($interaction_data, $user_id);
            }
        }
    }
    
    /**
     * Log user interaction for analytics
     *
     * @since 1.0.0
     * @param array $interaction_data Interaction data
     * @param int $user_id User ID
     * @return void
     */
    private function log_user_interaction($interaction_data, $user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_ai_interactions';
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'entity_type' => $interaction_data['entity_type'],
                'entity_id' => $interaction_data['entity_id'],
                'action' => $interaction_data['action'],
                'timestamp' => current_time('mysql')
            )
        );
    }
    
    /**
     * Upscale artwork to specific resolution
     */
    public function upscale_to_resolution($file_path, $target_resolution = '4k', $settings = array()) {
        // Validate file exists
        if (!file_exists($file_path)) {
            return new WP_Error('invalid_file', __('File not found', 'vortex-marketplace'));
        }
        
        // Get file extension
        $file_info = pathinfo($file_path);
        $extension = strtolower($file_info['extension']);
        
        // Only support image formats for resolution upscaling
        $supported_formats = array('jpg', 'jpeg', 'png', 'webp');
        if (!in_array($extension, $supported_formats)) {
            return new WP_Error('unsupported_format', __('Resolution upscaling only supported for images', 'vortex-marketplace'));
        }
        
        // Get original image dimensions
        list($orig_width, $orig_height) = getimagesize($file_path);
        
        // Define target dimensions based on requested resolution
        $target_dimensions = $this->get_resolution_dimensions($target_resolution, $orig_width, $orig_height);
        
        // Calculate scale factor based on target dimensions
        $width_scale = $target_dimensions['width'] / $orig_width;
        $height_scale = $target_dimensions['height'] / $orig_height;
        $scale_factor = max($width_scale, $height_scale);
        
        // Default settings
        $defaults = array(
            'quality' => 'high', // low, medium, high
            'preserve_details' => true,
            'output_format' => $extension,
            'model' => 'upscaler-v2',
            'optimize_for_screen' => true
        );
        
        $settings = wp_parse_args($settings, $defaults);
        
        // Log upscale attempt
        $this->log_action('upscale_resolution', array(
            'file_path' => $file_path,
            'target_resolution' => $target_resolution,
            'target_dimensions' => $target_dimensions,
            'scale_factor' => $scale_factor,
            'settings' => $settings
        ));
        
        // Prepare for AI learning
        do_action('vortex_ai_agent_learn', 'HURAII', 'resolution_upscaling', array(
            'file_path' => $file_path,
            'target_resolution' => $target_resolution,
            'target_dimensions' => $target_dimensions,
            'scale_factor' => $scale_factor,
            'settings' => $settings,
            'timestamp' => current_time('timestamp')
        ));
        
        // Process the resolution upscaling (using the upscale_image method with calculated scale factor)
        $settings['target_width'] = $target_dimensions['width'];
        $settings['target_height'] = $target_dimensions['height'];
        
        return $this->upscale_image($file_path, $scale_factor, $settings);
    }
    
    /**
     * Get dimensions for a standard resolution
     */
    private function get_resolution_dimensions($resolution, $orig_width, $orig_height) {
        // Calculate aspect ratio
        $aspect_ratio = $orig_width / $orig_height;
        
        switch ($resolution) {
            case '4k':
                $width = 3840;
                $height = 2160;
                break;
            case '2k':
                $width = 2560;
                $height = 1440;
                break;
            case 'hd':
                $width = 1920;
                $height = 1080;
                break;
            case '720p':
                $width = 1280;
                $height = 720;
                break;
            default:
                // Default to 4K
                $width = 3840;
                $height = 2160;
        }
        
        // Adjust to maintain original aspect ratio
        if ($aspect_ratio > ($width / $height)) {
            // Image is wider than target aspect ratio
            $target_height = $width / $aspect_ratio;
            return array(
                'width' => $width,
                'height' => round($target_height)
            );
        } else {
            // Image is taller than target aspect ratio
            $target_width = $height * $aspect_ratio;
            return array(
                'width' => round($target_width),
                'height' => $height
            );
        }
    }
    
    /**
     * Upscale image (enhanced to handle specific target dimensions)
     */
    private function upscale_image($file_path, $scale_factor, $settings) {
        // Get original image dimensions
        list($width, $height) = getimagesize($file_path);
        
        // Calculate new dimensions
        if (isset($settings['target_width']) && isset($settings['target_height'])) {
            // Use specific target dimensions if provided
            $new_width = $settings['target_width'];
            $new_height = $settings['target_height'];
        } else {
            // Use scale factor
            $new_width = $width * $scale_factor;
            $new_height = $height * $scale_factor;
        }
        
        // Create output filename with resolution info
        $file_info = pathinfo($file_path);
        $resolution_info = isset($settings['target_width']) ? "{$new_width}x{$new_height}" : "{$scale_factor}x";
        $output_filename = $file_info['filename'] . '-' . $resolution_info . '.' . $settings['output_format'];
        $output_path = $file_info['dirname'] . '/' . $output_filename;
        $output_url = str_replace(wp_upload_dir()['basedir'], wp_upload_dir()['baseurl'], $output_path);
        
        // In a real implementation, this would call the upscaling AI model
        // For now, we'll simulate the process with GD
        
        // Load original image
        switch ($file_info['extension']) {
            case 'jpg':
            case 'jpeg':
                $source = imagecreatefromjpeg($file_path);
                break;
            case 'png':
                $source = imagecreatefrompng($file_path);
                break;
            case 'webp':
                $source = imagecreatefromwebp($file_path);
                break;
            default:
                return new WP_Error('unsupported_format', __('Unsupported image format', 'vortex-marketplace'));
        }
        
        // Create destination image
        $destination = imagecreatetruecolor($new_width, $new_height);
        
        // Preserve transparency if PNG
        if ($file_info['extension'] === 'png') {
            imagealphablending($destination, false);
            imagesavealpha($destination, true);
            $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
            imagefilledrectangle($destination, 0, 0, $new_width, $new_height, $transparent);
        }
        
        // Resize
        imagecopyresampled($destination, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        
        // Save the upscaled image
        switch ($settings['output_format']) {
            case 'jpg':
            case 'jpeg':
                imagejpeg($destination, $output_path, 90);
                break;
            case 'png':
                imagepng($destination, $output_path, 9);
                break;
            case 'webp':
                imagewebp($destination, $output_path, 90);
                break;
        }
        
        // Free memory
        imagedestroy($source);
        imagedestroy($destination);
        
        return array(
            'success' => true,
            'original_file' => $file_path,
            'upscaled_file' => $output_path,
            'upscaled_url' => $output_url,
            'original_dimensions' => $width . 'x' . $height,
            'new_dimensions' => $new_width . 'x' . $new_height,
            'resolution' => isset($settings['target_width']) ? 'custom' : $scale_factor . 'x',
            'scale_factor' => $scale_factor,
            'format' => $settings['output_format']
        );
    }
    
    /**
     * Generate download links for content
     */
    public function generate_download_links($file_path, $include_variants = true) {
        // Validate file exists
        if (!file_exists($file_path)) {
            return new WP_Error('invalid_file', __('File not found', 'vortex-marketplace'));
        }
        
        // Get file information
        $file_info = pathinfo($file_path);
        $extension = strtolower($file_info['extension']);
        $filename = $file_info['filename'];
        
        // Base URL for the file
        $base_url = str_replace(wp_upload_dir()['basedir'], wp_upload_dir()['baseurl'], $file_path);
        
        // Create download links array
        $download_links = array(
            'original' => array(
                'url' => $base_url,
                'format' => $extension,
                'label' => sprintf(__('Download Original (%s)', 'vortex-marketplace'), strtoupper($extension)),
                'filename' => $filename . '.' . $extension
            )
        );
        
        // Add variant links based on format
        if ($include_variants) {
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                case 'png':
                case 'webp':
                    // Add variants for images
                    $download_links['variants'] = $this->get_image_variant_links($file_path, $filename, $extension);
                    break;
                
                case 'mp4':
                case 'mov':
                case 'gif':
                    // Add variants for videos
                    $download_links['variants'] = $this->get_video_variant_links($file_path, $filename, $extension);
                    break;
                
                case 'obj':
                case 'glb':
                case 'gltf':
                    // Add variants for 3D models
                    $download_links['variants'] = $this->get_3d_variant_links($file_path, $filename, $extension);
                    break;
                
                case 'mp3':
                case 'wav':
                    // Add variants for audio
                    $download_links['variants'] = $this->get_audio_variant_links($file_path, $filename, $extension);
                    break;
                
                case 'html':
                case 'svg':
                    // Add variants for interactive content
                    $download_links['variants'] = $this->get_interactive_variant_links($file_path, $filename, $extension);
                    break;
            }
        }
        
        // Generate sharing links
        $download_links['sharing'] = $this->generate_sharing_links($base_url, $filename);
        
        // Track download links generation
        do_action('vortex_ai_agent_learn', 'HURAII', 'download_links_generated', array(
            'file_path' => $file_path,
            'links' => $download_links,
            'timestamp' => current_time('timestamp')
        ));
        
        return $download_links;
    }
    
    /**
     * Generate social media and sharing links
     */
    private function generate_sharing_links($file_url, $title) {
        $encoded_url = urlencode($file_url);
        $encoded_title = urlencode(sprintf(__('Check out this HURAII artwork: %s', 'vortex-marketplace'), $title));
        
        return array(
            'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . $encoded_url,
            'twitter' => 'https://twitter.com/intent/tweet?url=' . $encoded_url . '&text=' . $encoded_title,
            'pinterest' => 'https://pinterest.com/pin/create/button/?url=' . $encoded_url . '&media=' . $encoded_url . '&description=' . $encoded_title,
            'email' => 'mailto:?subject=' . $encoded_title . '&body=' . $encoded_url,
            'copy_link' => $file_url,
            'embed_code' => '<iframe src="' . esc_url($file_url) . '" width="100%" height="500" frameborder="0" allowfullscreen></iframe>'
        );
    }
    
    /**
     * Check if the HURAII agent is active and functioning
     *
     * @since 1.0.0
     * @return bool Whether the agent is active
     */
    public function is_active() {
        // Check if at least one model is loaded and functioning
        if (empty($this->active_models)) {
            return false;
        }
        
        // Check if model loader is functioning
        if (!$this->model_loader || !method_exists($this->model_loader, 'load_model')) {
            return false;
        }
        
        // Perform a basic health check
        try {
            $health_check = $this->model_loader->check_models_health();
            return $health_check['status'] === 'ok';
        } catch (Exception $e) {
            error_log('HURAII health check failed: ' . $e->getMessage());
            return false;
        }
    }
} 