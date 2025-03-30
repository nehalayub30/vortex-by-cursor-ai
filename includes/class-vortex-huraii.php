<?php
/**
 * Vortex HURAII
 *
 * Handles the integration with the HURAII AI agent for the Vortex AI Marketplace.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

class Vortex_HURAII extends Vortex_AI_Agent {
    /**
     * The single instance of this class
     */
    private static $instance = null;

    /**
     * User upload requirements tracking
     */
    private $upload_requirements = array(
        'weekly_seed_count' => 2, // Number of seed artworks required weekly
    );
    
    /**
     * Get the singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize HURAII
     */
    public function init() {
        // Register AJAX handlers
        add_action('wp_ajax_vortex_check_huraii_access', array($this, 'check_huraii_access'));
        add_action('wp_ajax_vortex_upload_seed_artwork', array($this, 'handle_seed_artwork_upload'));
        add_action('wp_ajax_vortex_analyze_artwork', array($this, 'analyze_artwork'));
        add_action('wp_ajax_vortex_generate_artwork', array($this, 'generate_artwork'));
        add_action('wp_ajax_vortex_handle_upscale_artwork', array($this, 'handle_upscale_artwork'));
        add_action('wp_ajax_vortex_handle_create_animation', array($this, 'handle_create_animation'));
        add_action('wp_ajax_vortex_handle_create_3d_model', array($this, 'handle_create_3d_model'));
        add_action('wp_ajax_vortex_handle_create_vr_environment', array($this, 'handle_create_vr_environment'));
        add_action('wp_ajax_vortex_get_immersive_content', array($this, 'get_immersive_content'));
        
        // Schedule weekly seed artwork check
        if (!wp_next_scheduled('vortex_weekly_seed_check')) {
            wp_schedule_event(time(), 'weekly', 'vortex_weekly_seed_check');
        }
        
        // Hook into the scheduled event
        add_action('vortex_weekly_seed_check', array($this, 'reset_weekly_seed_counts'));
    }
    
    /**
     * Check if user has access to HURAII
     */
    public function check_huraii_access() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_huraii_nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed', 'vortex-ai-marketplace')));
            return;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to use HURAII', 'vortex-ai-marketplace')));
            return;
        }
        
        // Check if user is an artist
        $user_id = get_current_user_id();
        $user_role = get_user_meta($user_id, 'vortex_user_role', true);
        
        if ($user_role !== 'artist') {
            wp_send_json_error(array(
                'message' => __('HURAII is available exclusively for artists. If you\'re an artist, please update your profile settings.', 'vortex-ai-marketplace'),
                'code' => 'not_artist'
            ));
            return;
        }
        
        // Get greeting and user stats
        $greeting = $this->get_personalized_greeting($user_id);
        $stats = $this->get_user_stats($user_id);
        
        wp_send_json_success(array(
            'greeting' => $greeting,
            'stats' => $stats,
            'seed_uploads_required' => $this->get_remaining_seed_uploads($user_id),
            'has_access' => true
        ));
    }
    
    /**
     * Get personalized greeting for user
     */
    private function get_personalized_greeting($user_id) {
        $user = get_userdata($user_id);
        $first_name = $user->first_name ?: $user->display_name;
        
        $greetings = array(
            __("Welcome back, %s! Ready to create something extraordinary?", 'vortex-ai-marketplace'),
            __("Hello, %s! I'm excited to collaborate on new artwork with you today.", 'vortex-ai-marketplace'),
            __("Great to see you, %s! Your artistic journey continues - what shall we create today?", 'vortex-ai-marketplace'),
            __("%s, your creative energy is inspiring! Let's transform your vision into digital art.", 'vortex-ai-marketplace'),
            __("Welcome, %s! Your unique artistic style makes our collaborations special.", 'vortex-ai-marketplace')
        );
        
        // Get time-based context for personalized greeting
        $hour = current_time('G');
        if ($hour < 12) {
            array_push($greetings, __("Good morning, %s! Let's start the day with creative inspiration.", 'vortex-ai-marketplace'));
        } elseif ($hour < 18) {
            array_push($greetings, __("Good afternoon, %s! The perfect time to channel your creativity.", 'vortex-ai-marketplace'));
        } else {
            array_push($greetings, __("Good evening, %s! Nighttime often brings the most creative ideas.", 'vortex-ai-marketplace'));
        }
        
        // Get a random greeting
        $greeting = $greetings[array_rand($greetings)];
        
        // Add seed artwork reminder if needed
        $remaining_uploads = $this->get_remaining_seed_uploads($user_id);
        if ($remaining_uploads > 0) {
            $greeting .= ' ' . sprintf(
                _n(
                    "Remember to upload %d more seed artwork this week to unlock HURAII's full potential.",
                    "Remember to upload %d more seed artworks this week to unlock HURAII's full potential.",
                    $remaining_uploads,
                    'vortex-ai-marketplace'
                ),
                $remaining_uploads
            );
        }
        
        return sprintf($greeting, $first_name);
    }
    
    /**
     * Get user stats related to HURAII
     */
    private function get_user_stats($user_id) {
        global $wpdb;
        
        $artwork_table = $wpdb->prefix . 'vortex_artwork';
        
        // Total artworks created
        $total_created = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $artwork_table WHERE user_id = %d AND ai_generated = 1",
            $user_id
        ));
        
        // Total seed artworks uploaded
        $total_seed = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $artwork_table WHERE user_id = %d AND is_seed = 1",
            $user_id
        ));
        
        // Most used generation style
        $most_used_style = $wpdb->get_var($wpdb->prepare(
            "SELECT generation_style FROM $artwork_table 
            WHERE user_id = %d AND ai_generated = 1 
            GROUP BY generation_style 
            ORDER BY COUNT(*) DESC 
            LIMIT 1",
            $user_id
        ));
        
        return array(
            'total_created' => $total_created ?: 0,
            'total_seed' => $total_seed ?: 0,
            'most_used_style' => $most_used_style ?: __('None yet', 'vortex-ai-marketplace'),
            'seed_this_week' => $this->get_seed_uploads_this_week($user_id)
        );
    }

    /**
     * Get number of seed uploads this week
     */
    private function get_seed_uploads_this_week($user_id) {
        global $wpdb;
        
        $artwork_table = $wpdb->prefix . 'vortex_artwork';
        
        // Get weekly seed artwork count
        $week_start = strtotime('monday this week');
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $artwork_table 
            WHERE user_id = %d 
            AND is_seed = 1 
            AND upload_date >= %s",
            $user_id,
            date('Y-m-d H:i:s', $week_start)
        ));
        
        return $count ?: 0;
    }
    
    /**
     * Get remaining seed uploads needed this week
     */
    private function get_remaining_seed_uploads($user_id) {
        $uploads_this_week = $this->get_seed_uploads_this_week($user_id);
        $required = $this->upload_requirements['weekly_seed_count'];
        
        return max(0, $required - $uploads_this_week);
    }
    
    /**
     * Handle seed artwork upload
     */
    public function handle_seed_artwork_upload() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_seed_upload_nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed', 'vortex-ai-marketplace')));
            return;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to upload artwork', 'vortex-ai-marketplace')));
            return;
        }
        
        // Check if user is an artist
        $user_id = get_current_user_id();
        $user_role = get_user_meta($user_id, 'vortex_user_role', true);
        
        if ($user_role !== 'artist') {
            wp_send_json_error(array('message' => __('Only artists can upload seed artwork', 'vortex-ai-marketplace')));
            return;
        }
        
        // Check for uploaded file
        if (empty($_FILES['seed_artwork']) || !isset($_FILES['seed_artwork']['tmp_name']) || empty($_FILES['seed_artwork']['tmp_name'])) {
            wp_send_json_error(array('message' => __('No file was uploaded', 'vortex-ai-marketplace')));
            return;
        }
        
        // Validate file type
        $file_type = wp_check_filetype(basename($_FILES['seed_artwork']['name']));
        $allowed_types = array('jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png');
        
        if (!in_array($file_type['type'], $allowed_types)) {
            wp_send_json_error(array('message' => __('Only JPG and PNG files are allowed', 'vortex-ai-marketplace')));
            return;
        }
        
        // Create upload directory if it doesn't exist
        $upload_dir = wp_upload_dir();
        $user_dir = $upload_dir['basedir'] . '/vortex-seed-art/' . $user_id;
        
        if (!file_exists($user_dir)) {
            wp_mkdir_p($user_dir);
        }
        
        // Generate unique filename
        $filename = wp_unique_filename($user_dir, $_FILES['seed_artwork']['name']);
        $file_path = $user_dir . '/' . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($_FILES['seed_artwork']['tmp_name'], $file_path)) {
            wp_send_json_error(array('message' => __('Failed to upload file', 'vortex-ai-marketplace')));
            return;
        }
        
        // Get additional metadata
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : pathinfo($filename, PATHINFO_FILENAME);
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        $technique = isset($_POST['technique']) ? sanitize_text_field($_POST['technique']) : '';
        
        // Save to database
        global $wpdb;
        $artwork_table = $wpdb->prefix . 'vortex_artwork';
        
        $result = $wpdb->insert(
            $artwork_table,
            array(
                'user_id' => $user_id,
                'title' => $title,
                'description' => $description,
                'technique' => $technique,
                'file_path' => str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $file_path),
                'is_seed' => 1,
                'ai_generated' => 0,
                'upload_date' => current_time('mysql'),
                'private' => 1 // Seed art is private by default
            ),
            array('%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%d')
        );
        
        if (!$result) {
            wp_send_json_error(array('message' => __('Failed to save artwork information', 'vortex-ai-marketplace')));
            return;
        }
        
        // Get the newly created artwork ID
        $artwork_id = $wpdb->insert_id;
        
        // Analyze the artwork to extract style information
        $analysis = $this->analyze_seed_artwork($file_path, $artwork_id);
        
        // Get updated stats
        $stats = $this->get_user_stats($user_id);
        $remaining = $this->get_remaining_seed_uploads($user_id);
        
        wp_send_json_success(array(
            'message' => __('Seed artwork uploaded successfully', 'vortex-ai-marketplace'),
            'artwork_id' => $artwork_id,
            'artwork_url' => str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $file_path),
            'analysis' => $analysis,
            'stats' => $stats,
            'remaining_uploads' => $remaining
        ));
    }
    
    /**
     * Analyze seed artwork to extract style information
     */
    private function analyze_seed_artwork($file_path, $artwork_id) {
        // This would typically use image analysis AI
        // For now, we'll simulate with a basic analysis
        
        $analysis = array(
            'style' => __('Custom style detected', 'vortex-ai-marketplace'),
            'technique' => __('Mixed media', 'vortex-ai-marketplace'),
            'color_palette' => __('Rich and varied', 'vortex-ai-marketplace'),
            'composition' => __('Balanced with strong focal points', 'vortex-ai-marketplace')
        );
        
        // Save analysis to artwork metadata
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'vortex_artwork',
            array('analysis' => maybe_serialize($analysis)),
            array('id' => $artwork_id),
            array('%s'),
            array('%d')
        );
        
        return $analysis;
    }
    
    /**
     * Analyze existing artwork
     */
    public function analyze_artwork() {
        // Verify nonce and user access
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_huraii_nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed', 'vortex-ai-marketplace')));
            return;
        }
        
        // Verify user is an artist
        if (!$this->is_user_artist()) {
            wp_send_json_error(array('message' => __('Only artists can use this feature', 'vortex-ai-marketplace'), 'code' => 'not_artist'));
            return;
        }
        
        // Get artwork details
        $artwork_id = isset($_POST['artwork_id']) ? intval($_POST['artwork_id']) : 0;
        $focus = isset($_POST['focus']) ? sanitize_text_field($_POST['focus']) : 'general';
        
        if (!$artwork_id) {
            wp_send_json_error(array('message' => __('No artwork selected', 'vortex-ai-marketplace')));
            return;
        }
        
        global $wpdb;
        $artwork_table = $wpdb->prefix . 'vortex_artwork';
        
        $artwork = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $artwork_table WHERE id = %d AND user_id = %d",
            $artwork_id,
            get_current_user_id()
        ));
        
        if (!$artwork) {
            wp_send_json_error(array('message' => __('Artwork not found', 'vortex-ai-marketplace')));
            return;
        }
        
        // Begin analysis based on Seed-Art technique principles
        $analysis = $this->perform_seed_art_analysis($artwork, $focus);
        
        // Log analysis for continued learning
        $this->log_artwork_analysis($artwork_id, $analysis);
        
        // Return results
        wp_send_json_success(array(
            'artwork' => array(
                'id' => $artwork->id,
                'title' => $artwork->title,
                'image_url' => $artwork->file_path,
            ),
            'analysis' => $analysis
        ));
    }

    /**
     * Perform comprehensive Seed-Art analysis of the artwork
     * Based on Marianne Nems' technique focusing on sacred geometry, color weight,
     * light and shadow, texture, perspective, and artwork size
     */
    private function perform_seed_art_analysis($artwork, $focus) {
        // Prepare the base analysis context
        $analysis = array(
            'title' => sprintf(__('Analysis of "%s"', 'vortex-ai-marketplace'), $artwork->title),
            'text' => '',
            'elements' => array(
                'sacred_geometry' => array(),
                'color_weight' => array(),
                'light_shadow' => array(),
                'texture' => array(),
                'perspective' => array(),
                'size_impact' => array()
            )
        );
        
        // Get image metadata from file path
        $image_info = $this->get_image_metadata($artwork->file_path);
        
        // Build specific analysis based on focus type
        switch ($focus) {
            case 'technique':
                $analysis['text'] = $this->generate_technique_analysis($artwork, $image_info);
                break;
            
            case 'composition':
                $analysis['text'] = $this->generate_composition_analysis($artwork, $image_info);
                break;
            
            case 'emotional':
                $analysis['text'] = $this->generate_emotional_analysis($artwork, $image_info);
                break;
            
            case 'market':
                $analysis['text'] = $this->generate_market_analysis($artwork, $image_info);
                break;
            
            case 'general':
            default:
                // For general analysis, combine elements from all areas
                $analysis['text'] = $this->generate_comprehensive_analysis($artwork, $image_info);
                break;
        }
        
        // Add suggestions for enhancement based on Seed-Art principles
        $analysis['suggestions'] = $this->generate_enhancement_suggestions($artwork, $image_info, $focus);
        
        return $analysis;
    }
    
    /**
     * Generate comprehensive analysis based on Seed-Art principles
     */
    private function generate_comprehensive_analysis($artwork, $image_info) {
        // First, get the user's overall style profile
        $style_profile = $this->get_user_style_profile(get_current_user_id());
        
        // Create an AI prompt that incorporates Seed-Art principles
        $prompt = "Analyze this artwork titled \"{$artwork->title}\" with description \"{$artwork->description}\" using the principles of Seed-Art technique:\n\n";
        $prompt .= "1. Sacred Geometry: Identify any geometric patterns, proportions, or harmonious structures\n";
        $prompt .= "2. Color Weight: Analyze the color palette, balance, emotional impact of colors\n";
        $prompt .= "3. Light and Shadow: Assess the light source, contrasts, volumetric effects\n";
        $prompt .= "4. Texture: Evaluate the textural elements and tactile qualities\n";
        $prompt .= "5. Perspective: Analyze spatial relationships and dimensional aspects\n";
        $prompt .= "6. Size and Detail: Consider how the artwork's dimensions affect detail rendering\n\n";
        
        // Add user's style context from previous analyses
        if (!empty($style_profile)) {
            $prompt .= "Artist's Style Context: " . json_encode($style_profile) . "\n\n";
        }
        
        // Add image metadata
        $prompt .= "Image Dimensions: {$image_info['width']}x{$image_info['height']} pixels\n";
        $prompt .= "Image Format: {$image_info['format']}\n\n";
        
        // Send to AI processing
        $llm = Vortex_AI_Marketplace::get_instance()->llm;
        $analysis_text = $llm->generate_text($prompt);
        
        // If LLM fails, provide a basic analysis
        if (empty($analysis_text)) {
            return $this->generate_fallback_analysis($artwork, $image_info);
        }
        
        return $analysis_text;
    }
    
    /**
     * Build user's style profile from previous seed artwork analyses
     */
    private function get_user_style_profile($user_id) {
        global $wpdb;
        
        $analysis_table = $wpdb->prefix . 'vortex_artwork_analysis';
        $artwork_table = $wpdb->prefix . 'vortex_artwork';
        
        // Get the last 5 seed artwork analyses to build style profile
        $analyses = $wpdb->get_results($wpdb->prepare(
            "SELECT a.* FROM $analysis_table a
             JOIN $artwork_table b ON a.artwork_id = b.id
             WHERE b.user_id = %d AND b.is_seed = 1
             ORDER BY a.analysis_date DESC
             LIMIT 5",
            $user_id
        ));
        
        if (empty($analyses)) {
            return array();
        }
        
        // Compile style profile
        $style_profile = array(
            'dominant_colors' => array(),
            'common_themes' => array(),
            'preferred_techniques' => array(),
            'signature_elements' => array()
        );
        
        // Process each analysis to extract style elements
        foreach ($analyses as $analysis) {
            $data = maybe_unserialize($analysis->analysis_data);
            
            // Extract and count style elements
            if (!empty($data['elements'])) {
                // Process colors
                if (!empty($data['elements']['color_weight'])) {
                    foreach ($data['elements']['color_weight'] as $color) {
                        if (!isset($style_profile['dominant_colors'][$color])) {
                            $style_profile['dominant_colors'][$color] = 1;
                        } else {
                            $style_profile['dominant_colors'][$color]++;
                        }
                    }
                }
                
                // Similar processing for other elements...
            }
        }
        
        // Sort and keep top elements
        arsort($style_profile['dominant_colors']);
        $style_profile['dominant_colors'] = array_slice($style_profile['dominant_colors'], 0, 5, true);
        
        // Similar sorting for other elements...
        
        return $style_profile;
    }
    
    /**
     * Generate artwork using the artist's seed art style
     */
    public function generate_artwork() {
        // Verify nonce and user access
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_huraii_nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed', 'vortex-ai-marketplace')));
            return;
        }
        
        // Get current user ID
        $user_id = get_current_user_id();
        
        // Check if user is allowed to generate
        if (!$this->is_user_artist()) {
            wp_send_json_error(array('message' => __('Only artists can generate artwork', 'vortex-ai-marketplace'), 'code' => 'not_artist'));
            return;
        }
        
        // Check if user has met seed upload requirements
        $remaining_uploads = $this->get_remaining_seed_uploads($user_id);
        if ($remaining_uploads > 0) {
            wp_send_json_error(array(
                'message' => sprintf(
                    _n(
                        'You need to upload %d more seed artwork this week before generating.',
                        'You need to upload %d more seed artworks this week before generating.',
                        $remaining_uploads,
                        'vortex-ai-marketplace'
                    ),
                    $remaining_uploads
                ),
                'code' => 'seed_required'
            ));
            return;
        }
        
        // Get generation parameters
        $prompt = isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : '';
        $style_description = isset($_POST['style_description']) ? sanitize_textarea_field($_POST['style_description']) : '';
        $seed_artworks = isset($_POST['seed_artworks']) ? array_map('intval', (array)$_POST['seed_artworks']) : array();
        $width = isset($_POST['width']) ? intval($_POST['width']) : 1024;
        $height = isset($_POST['height']) ? intval($_POST['height']) : 1024;
        $private = isset($_POST['private']) ? (bool)$_POST['private'] : true;
        
        // Get royalty data
        $royalty_data = isset($_POST['royalty_data']) ? json_decode(stripslashes($_POST['royalty_data']), true) : array();
        
        // Validate royalties (if provided)
        require_once plugin_dir_path(__FILE__) . 'class-vortex-huraii-smart-contract.php';
        $contract_manager = Vortex_HURAII_Smart_Contract::get_instance();
        
        // Check token balance
        $wallet = Vortex_AI_Marketplace::get_instance()->wallet;
        $token_cost = 10; // Base cost
        
        if (!$wallet->check_tola_balance($user_id, $token_cost)) {
            wp_send_json_error(array(
                'message' => sprintf(
                    __('You need %d TOLA tokens to generate artwork. Current balance: %d', 'vortex-ai-marketplace'),
                    $token_cost,
                    $wallet->get_tola_balance($user_id)
                ),
                'code' => 'insufficient_tokens'
            ));
            return;
        }
        
        // Load the seed art analyzer and smart contract manager
        require_once plugin_dir_path(__FILE__) . 'class-vortex-huraii-seed-analyzer.php';
        require_once plugin_dir_path(__FILE__) . 'class-vortex-huraii-smart-contract.php';
        
        $seed_analyzer = Vortex_HURAII_Seed_Analyzer::get_instance();
        $contract_manager = Vortex_HURAII_Smart_Contract::get_instance();
        
        // Get the artist's style profile
        $style_profile = get_user_meta($user_id, 'vortex_artist_style_profile', true);
        
        // If no style profile exists, create one from the seed artworks
        if (empty($style_profile)) {
            $style_profile = $this->build_initial_style_profile($user_id, $seed_artworks);
        }
        
        // Merge style profile with specific seed artwork influences
        $enriched_style = $this->enrich_style_with_seeds($style_profile, $seed_artworks);
        
        // Start timing the generation process
        $start_time = microtime(true);
        
        // Generate the artwork
        // (In a real implementation, this would call an AI image generation API)
        $generation_result = $this->process_artwork_generation(
            $prompt,
            $style_description,
            $enriched_style,
            $width,
            $height
        );
        
        if (is_wp_error($generation_result)) {
            wp_send_json_error(array('message' => $generation_result->get_error_message()));
            return;
        }
        
        // Calculate processing time
        $processing_time = round(microtime(true) - $start_time);
        
        // Create a title if none provided
        $title = !empty($_POST['title']) 
            ? sanitize_text_field($_POST['title'])
            : $this->generate_artwork_title($prompt, $style_description);
        
        // Save artwork to database
        $artwork_id = $this->save_generated_artwork(
            $user_id,
            $title,
            $prompt,
            $generation_result['file_path'],
            $seed_artworks,
            $enriched_style['style_fingerprint']['hash'],
            $processing_time,
            $private
        );
        
        if (is_wp_error($artwork_id)) {
            wp_send_json_error(array('message' => $artwork_id->get_error_message()));
            return;
        }
        
        // Create smart contract for the artwork with royalties
        $contract = $contract_manager->create_artwork_contract(
            $artwork_id,
            $user_id,
            $seed_artworks,
            $enriched_style['style_fingerprint'],
            $royalty_data // Pass the royalty data to the contract
        );
        
        if (is_wp_error($contract)) {
            wp_send_json_error(array('message' => $contract->get_error_message()));
            return;
        }
        
        // Generate restricted URL with contract verification
        $artwork_url = $contract_manager->generate_restricted_url($artwork_id);
        
        // Deduct tokens
        $wallet->deduct_tola_tokens($user_id, $token_cost, 'artwork_generation');
        
        // Return the generated artwork info
        wp_send_json_success(array(
            'artwork_id' => $artwork_id,
            'title' => $title,
            'file_path' => $generation_result['file_path'],
            'processing_time' => $processing_time,
            'token_cost' => $token_cost,
            'contract_hash' => $contract['contract_hash'],
            'contract_address' => $contract['blockchain_record']['contract_address'],
            'blockchain' => $contract['blockchain'],
            'creator_signature' => $contract['royalty_recipients'][0]['signature'],
            'total_royalty' => $contract['total_royalty_percentage'] . '%',
            'artwork_url' => $artwork_url
        ));
    }
    
    /**
     * Prepare collection of artworks based on generation type
     */
    private function prepare_artwork_collection($user_id, $generation_type, $seed_artwork_id) {
        global $wpdb;
        $artwork_table = $wpdb->prefix . 'vortex_artwork';
        
        $collection = array();
        
        // Always include the selected seed artwork
        $collection[] = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $artwork_table WHERE id = %d",
            $seed_artwork_id
        ));
        
        // Build collection based on generation type
        switch ($generation_type) {
            case 'seed_only':
                // Only use the selected seed artwork - already added
                break;
            
            case 'all_seed':
                // Use all user's seed artworks
                $seed_artworks = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM $artwork_table 
                     WHERE user_id = %d AND is_seed = 1 AND id != %d
                     ORDER BY upload_date DESC
                     LIMIT 5",
                    $user_id,
                    $seed_artwork_id
                ));
                
                if (!empty($seed_artworks)) {
                    $collection = array_merge($collection, $seed_artworks);
                }
                break;
            
            case 'all_private':
                // Use all user's private artworks (seed + generated)
                $private_artworks = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM $artwork_table 
                     WHERE user_id = %d AND private = 1 AND id != %d
                     ORDER BY upload_date DESC
                     LIMIT 5",
                    $user_id,
                    $seed_artwork_id
                ));
                
                if (!empty($private_artworks)) {
                    $collection = array_merge($collection, $private_artworks);
                }
                break;
            
            case 'all_artwork':
                // Use all user's artworks (private + marketplace)
                $all_artworks = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM $artwork_table 
                     WHERE user_id = %d AND id != %d
                     ORDER BY upload_date DESC
                     LIMIT 7",
                    $user_id,
                    $seed_artwork_id
                ));
                
                if (!empty($all_artworks)) {
                    $collection = array_merge($collection, $all_artworks);
                }
                break;
        }
        
        return $collection;
    }
    
    /**
     * Generate new artwork with AI based on seed artworks
     */
    private function perform_ai_generation($user_id, $artworks_collection, $description, $size) {
        // Set up dimensions based on size
        $dimensions = array(
            'small' => '512x512',
            'medium' => '768x768',
            'large' => '1024x1024'
        );
        
        $dimension = isset($dimensions[$size]) ? $dimensions[$size] : $dimensions['medium'];
        
        // Extract style information from collection
        $style_data = $this->extract_style_from_collection($artworks_collection);
        
        // Build generation title based on seed artwork and description
        $title = !empty($artworks_collection[0]->title) ? 
            sprintf(__('%s Variation', 'vortex-ai-marketplace'), $artworks_collection[0]->title) : 
            __('Inspired Artwork', 'vortex-ai-marketplace');
        
        // Format description if empty
        if (empty($description)) {
            $description = sprintf(__('Generated based on %s using HURAII', 'vortex-ai-marketplace'), 
                $artworks_collection[0]->title);
        }
        
        // Build generation parameters
        $params = array(
            'prompt' => $this->build_generation_prompt($artworks_collection, $description, $style_data),
            'negative_prompt' => 'blurry, distorted, low quality, draft, amateur',
            'seed_images' => $this->prepare_seed_images($artworks_collection),
            'style_transfer_strength' => 0.85, // High to preserve user's style
            'size' => $dimension,
            'user_id' => $user_id
        );
        
        // Call the API service to generate the artwork
        $artwork_service = Vortex_AI_Marketplace::get_instance()->get_artwork_service();
        $result = $artwork_service->generate_from_seeds($params);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Format the result
        return array(
            'url' => $result['url'],
            'title' => $title,
            'description' => $description,
            'prompt' => $params['prompt'],
            'seed_artwork_id' => $artworks_collection[0]->id
        );
    }
    
    /**
     * Extract artistic style information from artwork collection
     */
    private function extract_style_from_collection($artworks) {
        // Implement style analysis based on image recognition and previous analyses
        // This would typically use computer vision or be based on previous analyses
        
        $style_data = array(
            'dominant_colors' => array(),
            'brushwork' => '',
            'composition_type' => '',
            'medium_mimicry' => '',
            'style_characteristics' => array()
        );
        
        // First, check if we have analyses for these artworks
        global $wpdb;
        $analysis_table = $wpdb->prefix . 'vortex_artwork_analysis';
        
        foreach ($artworks as $artwork) {
            $analysis = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $analysis_table WHERE artwork_id = %d ORDER BY analysis_date DESC LIMIT 1",
                $artwork->id
            ));
            
            if ($analysis) {
                $data = maybe_unserialize($analysis->analysis_data);
                
                // Extract style information from the analysis
                if (!empty($data['elements'])) {
                    // Process color elements
                    if (!empty($data['elements']['color_weight'])) {
                        foreach ($data['elements']['color_weight'] as $color => $weight) {
                            if (!isset($style_data['dominant_colors'][$color])) {
                                $style_data['dominant_colors'][$color] = $weight;
                            } else {
                                $style_data['dominant_colors'][$color] += $weight;
                            }
                        }
                    }
                    
                    // Process other elements similarly...
                }
            }
        }
        
        // Sort and normalize
        arsort($style_data['dominant_colors']);
        $style_data['dominant_colors'] = array_slice($style_data['dominant_colors'], 0, 5, true);
        
        return $style_data;
    }
    
    /**
     * Reset weekly seed upload counters for all users
     */
    public function reset_weekly_seed_counts() {
        // In a real implementation, this would reset counters in the database
        // Since we're calculating dynamically based on upload dates, we don't need to do anything here
        
        // Log that the reset occurred
        error_log('HURAII weekly seed upload counters reset at ' . current_time('mysql'));
    }
    
    /**
     * Get user's artwork library
     */
    public function get_user_library($library_type = 'private') {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_huraii_nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed', 'vortex-ai-marketplace')));
            return;
        }
        
        // Check if user is logged in and is an artist
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to access your library', 'vortex-ai-marketplace')));
            return;
        }
        
        $user_id = get_current_user_id();
        $user_role = get_user_meta($user_id, 'vortex_user_role', true);
        
        if ($user_role !== 'artist') {
            wp_send_json_error(array('message' => __('Only artists can access this feature', 'vortex-ai-marketplace')));
            return;
        }
        
        // Get library type from request if not provided
        if (isset($_POST['library_type'])) {
            $library_type = sanitize_text_field($_POST['library_type']);
        }
        
        // Get page number for pagination
        $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
        $per_page = 12; // Number of artworks per page
        $offset = ($page - 1) * $per_page;
        
        global $wpdb;
        $artwork_table = $wpdb->prefix . 'vortex_artwork';
        
        // Build query based on library type
        $where_conditions = array();
        $where_conditions[] = $wpdb->prepare("user_id = %d", $user_id);
        
        switch ($library_type) {
            case 'seed':
                $where_conditions[] = "is_seed = 1";
                break;
            case 'generated':
                $where_conditions[] = "ai_generated = 1";
                break;
            case 'marketplace':
                $where_conditions[] = "private = 0";
                break;
            case 'all':
                // No additional conditions
                break;
            case 'private':
            default:
                $where_conditions[] = "private = 1";
                break;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Get total count for pagination
        $total_count = $wpdb->get_var("SELECT COUNT(*) FROM $artwork_table WHERE $where_clause");
        
        // Get artworks
        $artworks = $wpdb->get_results(
            "SELECT * FROM $artwork_table 
            WHERE $where_clause 
            ORDER BY upload_date DESC 
            LIMIT $offset, $per_page"
        );
        
        $formatted_artworks = array();
        foreach ($artworks as $artwork) {
            $formatted_artworks[] = array(
                'id' => $artwork->id,
                'title' => $artwork->title,
                'description' => $artwork->description,
                'image_url' => $artwork->file_path,
                'is_seed' => (bool)$artwork->is_seed,
                'ai_generated' => (bool)$artwork->ai_generated,
                'upload_date' => $artwork->upload_date,
                'private' => (bool)$artwork->private
            );
        }
        
        wp_send_json_success(array(
            'artworks' => $formatted_artworks,
            'total' => $total_count,
            'pages' => ceil($total_count / $per_page),
            'current_page' => $page
        ));
    }
    
    /**
     * Get insights about HURAII usage
     */
    public function get_insights_stats() {
        global $wpdb;
        
        $artwork_table = $wpdb->prefix . 'vortex_artwork';
        
        // Total artworks generated
        $total_generated = $wpdb->get_var("SELECT COUNT(*) FROM $artwork_table WHERE ai_generated = 1");
        
        // Total seed artworks
        $total_seed = $wpdb->get_var("SELECT COUNT(*) FROM $artwork_table WHERE is_seed = 1");
        
        // Average generation time
        $avg_time = $wpdb->get_var("SELECT AVG(processing_time) FROM $artwork_table WHERE ai_generated = 1");
        
        return array(
            'total_generated' => $total_generated ?: 0,
            'total_seed' => $total_seed ?: 0,
            'avg_generation_time' => $avg_time ?: 0
        );
    }

    /**
     * Handle upscale artwork request
     */
    public function handle_upscale_artwork() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_huraii_nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed', 'vortex-ai-marketplace')));
            return;
        }
        
        // Verify user is an artist
        if (!$this->is_user_artist()) {
            wp_send_json_error(array('message' => __('Only artists can use this feature', 'vortex-ai-marketplace'), 'code' => 'not_artist'));
            return;
        }
        
        // Get request parameters
        $artwork_id = isset($_POST['artwork_id']) ? intval($_POST['artwork_id']) : 0;
        $factor = isset($_POST['factor']) ? sanitize_text_field($_POST['factor']) : '2x';
        $method = isset($_POST['method']) ? sanitize_text_field($_POST['method']) : 'standard';
        $private = isset($_POST['private']) ? (bool)$_POST['private'] : true;
        
        if (!$artwork_id) {
            wp_send_json_error(array('message' => __('No artwork selected', 'vortex-ai-marketplace')));
            return;
        }
        
        // Get upscaler instance
        require_once plugin_dir_path(__FILE__) . 'class-vortex-huraii-upscaler.php';
        $upscaler = Vortex_HURAII_Upscaler::get_instance();
        
        // Process upscaling
        $result = $upscaler->upscale_artwork($artwork_id, $factor, $method, get_current_user_id(), $private);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        // Return success response
        wp_send_json_success($result);
    }

    /**
     * Handle create animation request
     */
    public function handle_create_animation() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_huraii_nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed', 'vortex-ai-marketplace')));
            return;
        }
        
        // Verify user is an artist
        if (!$this->is_user_artist()) {
            wp_send_json_error(array('message' => __('Only artists can use this feature', 'vortex-ai-marketplace'), 'code' => 'not_artist'));
            return;
        }
        
        // Get request parameters
        $artwork_id = isset($_POST['artwork_id']) ? intval($_POST['artwork_id']) : 0;
        $animation_type = isset($_POST['animation_type']) ? sanitize_text_field($_POST['animation_type']) : 'subtle_movement';
        $duration = isset($_POST['duration']) ? intval($_POST['duration']) : 5;
        $include_audio = isset($_POST['include_audio']) ? (bool)$_POST['include_audio'] : false;
        $audio_mood = isset($_POST['audio_mood']) ? sanitize_text_field($_POST['audio_mood']) : 'ambient';
        $private = isset($_POST['private']) ? (bool)$_POST['private'] : true;
        
        if (!$artwork_id) {
            wp_send_json_error(array('message' => __('No artwork selected', 'vortex-ai-marketplace')));
            return;
        }
        
        // Get animator instance
        require_once plugin_dir_path(__FILE__) . 'class-vortex-huraii-animator.php';
        $animator = Vortex_HURAII_Animator::get_instance();
        
        // Process animation
        $result = $animator->create_animation(
            $artwork_id, 
            $animation_type, 
            $duration, 
            $include_audio, 
            $audio_mood, 
            get_current_user_id(), 
            $private
        );
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        // Return success response
        wp_send_json_success($result);
    }

    /**
     * Handle create 3D model request
     */
    public function handle_create_3d_model() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_huraii_nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed', 'vortex-ai-marketplace')));
            return;
        }
        
        // Verify user is an artist
        if (!$this->is_user_artist()) {
            wp_send_json_error(array('message' => __('Only artists can use this feature', 'vortex-ai-marketplace'), 'code' => 'not_artist'));
            return;
        }
        
        // Get request parameters
        $artwork_id = isset($_POST['artwork_id']) ? intval($_POST['artwork_id']) : 0;
        $model_type = isset($_POST['model_type']) ? sanitize_text_field($_POST['model_type']) : 'sculpture';
        $detail_level = isset($_POST['detail_level']) ? sanitize_text_field($_POST['detail_level']) : 'standard';
        $file_format = isset($_POST['file_format']) ? sanitize_text_field($_POST['file_format']) : 'glb';
        $private = isset($_POST['private']) ? (bool)$_POST['private'] : true;
        
        if (!$artwork_id) {
            wp_send_json_error(array('message' => __('No artwork selected', 'vortex-ai-marketplace')));
            return;
        }
        
        // Get immersive instance
        require_once plugin_dir_path(__FILE__) . 'class-vortex-huraii-immersive.php';
        $immersive = Vortex_HURAII_Immersive::get_instance();
        
        // Process 3D model creation
        $result = $immersive->create_3d_model(
            $artwork_id,
            $model_type,
            $detail_level,
            $file_format,
            get_current_user_id(),
            $private
        );
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        // Return success response
        wp_send_json_success($result);
    }

    /**
     * Handle create VR environment request
     */
    public function handle_create_vr_environment() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_huraii_nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed', 'vortex-ai-marketplace')));
            return;
        }
        
        // Verify user is an artist
        if (!$this->is_user_artist()) {
            wp_send_json_error(array('message' => __('Only artists can use this feature', 'vortex-ai-marketplace'), 'code' => 'not_artist'));
            return;
        }
        
        // Get request parameters
        $artwork_id = isset($_POST['artwork_id']) ? intval($_POST['artwork_id']) : 0;
        $environment_type = isset($_POST['environment_type']) ? sanitize_text_field($_POST['environment_type']) : 'gallery';
        $complexity = isset($_POST['complexity']) ? sanitize_text_field($_POST['complexity']) : 'standard';
        $platform = isset($_POST['platform']) ? sanitize_text_field($_POST['platform']) : 'webxr';
        $multi_user = isset($_POST['multi_user']) ? (bool)$_POST['multi_user'] : false;
        $private = isset($_POST['private']) ? (bool)$_POST['private'] : true;
        
        if (!$artwork_id) {
            wp_send_json_error(array('message' => __('No artwork selected', 'vortex-ai-marketplace')));
            return;
        }
        
        // Get immersive instance
        require_once plugin_dir_path(__FILE__) . 'class-vortex-huraii-immersive.php';
        $immersive = Vortex_HURAII_Immersive::get_instance();
        
        // Process VR environment creation
        $result = $immersive->create_vr_environment(
            $artwork_id,
            $environment_type,
            $complexity,
            $platform,
            $multi_user,
            get_current_user_id(),
            $private
        );
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        // Return success response
        wp_send_json_success($result);
    }

    /**
     * Get user's immersive content (3D models, VR environments)
     */
    public function get_immersive_content() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_huraii_nonce')) {
            wp_send_json_error(array('message' => __('Security verification failed', 'vortex-ai-marketplace')));
            return;
        }
        
        // Verify user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in', 'vortex-ai-marketplace')));
            return;
        }
        
        // Get request parameters
        $content_type = isset($_POST['content_type']) ? sanitize_text_field($_POST['content_type']) : '3d';
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 12;
        
        global $wpdb;
        $user_id = get_current_user_id();
        
        if ($content_type === '3d') {
            // Get 3D models
            $models_table = $wpdb->prefix . 'vortex_3d_models';
            
            $total_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $models_table WHERE user_id = %d",
                $user_id
            ));
            
            $models = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $models_table WHERE user_id = %d ORDER BY creation_date DESC LIMIT %d OFFSET %d",
                $user_id,
                $per_page,
                ($page - 1) * $per_page
            ));
            
            wp_send_json_success(array(
                'items' => $models,
                'total' => $total_count,
                'pages' => ceil($total_count / $per_page),
                'current_page' => $page
            ));
        } else if ($content_type === 'vr') {
            // Get VR environments
            $immersive_table = $wpdb->prefix . 'vortex_immersive_experiences';
            
            $total_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $immersive_table WHERE user_id = %d AND experience_type = 'vr'",
                $user_id
            ));
            
            $environments = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $immersive_table WHERE user_id = %d AND experience_type = 'vr' 
                 ORDER BY creation_date DESC LIMIT %d OFFSET %d",
                $user_id,
                $per_page,
                ($page - 1) * $per_page
            ));
            
            wp_send_json_success(array(
                'items' => $environments,
                'total' => $total_count,
                'pages' => ceil($total_count / $per_page),
                'current_page' => $page
            ));
        } else {
            wp_send_json_error(array('message' => __('Invalid content type', 'vortex-ai-marketplace')));
        }
    }

    /**
     * Set the learning rate for the AI model
     * 
     * @param float $rate Learning rate (between 0.0001 and 0.1)
     * @return bool Success status
     */
    public function set_learning_rate($rate = 0.001) {
        // Validate learning rate range
        $rate = max(0.0001, min(0.1, floatval($rate)));
        
        // Store learning rate in options
        update_option('vortex_huraii_learning_rate', $rate);
        
        // Update any active models with new learning rate
        $this->update_model_parameters(array('learning_rate' => $rate));
        
        return true;
    }
    
    /**
     * Enable continuous learning mode for HURAII
     * 
     * @param bool $status Whether to enable continuous learning
     * @return bool Success status
     */
    public function enable_continuous_learning($status = true) {
        // Update option
        update_option('vortex_huraii_continuous_learning', $status ? 'yes' : 'no');
        
        // If enabling, set up feedback collection hooks
        if ($status) {
            // Initialize learning dataset if not already loaded
            if (empty($this->learning_dataset)) {
                $this->load_learning_dataset();
            }
            
            // Set up continuous feedback loops
            $this->setup_continuous_feedback_loops();
        }
        
        return true;
    }
    
    /**
     * Set the context window size for the model
     * 
     * @param int $window_size Context window size (in tokens)
     * @return bool Success status
     */
    public function set_context_window($window_size = 1000) {
        // Validate window size (between 256 and 8192)
        $window_size = max(256, min(8192, intval($window_size)));
        
        // Store context window size in options
        update_option('vortex_huraii_context_window', $window_size);
        
        // Update model configuration
        $this->update_model_parameters(array('context_window' => $window_size));
        
        return true;
    }
    
    /**
     * Enable cross-learning between HURAII and other AI agents
     * 
     * @param bool $status Whether to enable cross-learning
     * @param array $agents Array of agents to enable cross-learning with
     * @return bool Success status
     */
    public function enable_cross_learning($status = true, $agents = array('cloe', 'thorius', 'business_strategist')) {
        // Update option
        update_option('vortex_huraii_cross_learning', $status ? 'yes' : 'no');
        
        // Store which agents to cross-learn with
        if ($status && !empty($agents)) {
            update_option('vortex_huraii_cross_learning_agents', $agents);
            
            // Set up cross-learning data exchange
            $this->initialize_cross_learning_exchange($agents);
        }
        
        return true;
    }
    
    /**
     * Setup continuous feedback loops for learning
     */
    private function setup_continuous_feedback_loops() {
        // Set up hooks to collect feedback from various user interactions
        add_action('vortex_artwork_viewed', array($this, 'process_artwork_view_feedback'), 10, 2);
        add_action('vortex_artwork_rated', array($this, 'process_artwork_rating_feedback'), 10, 3);
        add_action('vortex_artwork_generation_completed', array($this, 'process_generation_feedback'), 10, 2);
        
        // Schedule regular model updates based on collected feedback
        if (!wp_next_scheduled('vortex_huraii_model_update')) {
            wp_schedule_event(time(), 'daily', 'vortex_huraii_model_update');
        }
        
        // Hook into the scheduled event
        add_action('vortex_huraii_model_update', array($this, 'update_model_from_feedback'));
    }
    
    /**
     * Initialize cross-learning exchange with other AI agents
     * 
     * @param array $agents Array of agents to enable cross-learning with
     */
    private function initialize_cross_learning_exchange($agents) {
        // Set up data exchange hooks
        if (in_array('cloe', $agents)) {
            // Hook into CLOE's insights
            add_filter('vortex_cloe_insights_data', array($this, 'process_cloe_insights'), 10, 2);
            // Provide data to CLOE
            add_filter('vortex_huraii_data_for_cloe', array($this, 'prepare_data_for_cloe'), 10, 1);
        }
        
        if (in_array('thorius', $agents)) {
            // Hook into Thorius' consultations
            add_filter('vortex_thorius_consultation_data', array($this, 'process_thorius_data'), 10, 2);
            // Provide data to Thorius
            add_filter('vortex_huraii_data_for_thorius', array($this, 'prepare_data_for_thorius'), 10, 1);
        }
        
        if (in_array('business_strategist', $agents)) {
            // Hook into Business Strategist's analyses
            add_filter('vortex_business_strategist_analysis', array($this, 'process_business_data'), 10, 2);
            // Provide data to Business Strategist
            add_filter('vortex_huraii_data_for_strategist', array($this, 'prepare_data_for_strategist'), 10, 1);
        }
    }
    
    /**
     * Update model parameters
     * 
     * @param array $parameters Parameters to update
     */
    private function update_model_parameters($parameters) {
        // Get current parameters
        $current_params = get_option('vortex_huraii_model_parameters', array());
        
        // Merge with new parameters
        $updated_params = array_merge($current_params, $parameters);
        
        // Save updated parameters
        update_option('vortex_huraii_model_parameters', $updated_params);
        
        // In a real implementation, this would update the AI model configuration
        // For now, we'll just log the update
        error_log('HURAII model parameters updated: ' . json_encode($updated_params));
    }
    
    /**
     * Process feedback from artwork views
     * 
     * @param int $artwork_id Artwork ID
     * @param int $user_id User ID
     */
    public function process_artwork_view_feedback($artwork_id, $user_id) {
        // Get artwork details
        global $wpdb;
        $artwork = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vortex_artwork WHERE id = %d",
            $artwork_id
        ));
        
        if (!$artwork) {
            return;
        }
        
        // Process view feedback for learning
        if ($artwork->ai_generated) {
            // Increment view count for this style/prompt combination
            if (!empty($artwork->generation_style) && !empty($artwork->prompt)) {
                $feedback_key = md5($artwork->generation_style . '|' . $artwork->prompt);
                
                if (!isset($this->learning_dataset['feedback_data'][$feedback_key])) {
                    $this->learning_dataset['feedback_data'][$feedback_key] = array(
                        'style' => $artwork->generation_style,
                        'prompt' => $artwork->prompt,
                        'views' => 0,
                        'likes' => 0,
                        'shares' => 0,
                        'avg_time' => 0,
                        'total_time' => 0,
                        'view_sessions' => 0
                    );
                }
                
                $this->learning_dataset['feedback_data'][$feedback_key]['views']++;
                
                // Save updated learning dataset
                update_option('vortex_huraii_learning_dataset', $this->learning_dataset);
            }
        }
    }
    
    /**
     * Process feedback from artwork ratings
     * 
     * @param int $artwork_id Artwork ID
     * @param int $user_id User ID
     * @param int $rating Rating value
     */
    public function process_artwork_rating_feedback($artwork_id, $user_id, $rating) {
        // Get artwork details
        global $wpdb;
        $artwork = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vortex_artwork WHERE id = %d",
            $artwork_id
        ));
        
        if (!$artwork) {
            return;
        }
        
        // Process rating feedback for learning
        if ($artwork->ai_generated) {
            // Update ratings for this style/prompt combination
            if (!empty($artwork->generation_style) && !empty($artwork->prompt)) {
                $feedback_key = md5($artwork->generation_style . '|' . $artwork->prompt);
                
                if (!isset($this->learning_dataset['feedback_data'][$feedback_key])) {
                    $this->learning_dataset['feedback_data'][$feedback_key] = array(
                        'style' => $artwork->generation_style,
                        'prompt' => $artwork->prompt,
                        'views' => 0,
                        'likes' => 0,
                        'ratings' => array(),
                        'avg_rating' => 0
                    );
                }
                
                // Add rating
                $this->learning_dataset['feedback_data'][$feedback_key]['ratings'][$user_id] = $rating;
                
                // Update average rating
                $ratings = $this->learning_dataset['feedback_data'][$feedback_key]['ratings'];
                $this->learning_dataset['feedback_data'][$feedback_key]['avg_rating'] = array_sum($ratings) / count($ratings);
                
                // If rating is 4 or 5, count as a "like"
                if ($rating >= 4) {
                    $this->learning_dataset['feedback_data'][$feedback_key]['likes']++;
                }
                
                // Save updated learning dataset
                update_option('vortex_huraii_learning_dataset', $this->learning_dataset);
            }
        }
    }
    
    /**
     * Process feedback from artwork generation
     * 
     * @param int $artwork_id Artwork ID
     * @param array $generation_data Generation data
     */
    public function process_generation_feedback($artwork_id, $generation_data) {
        // Record generation parameters and results for future learning
        if (!isset($this->learning_dataset['generation_history'])) {
            $this->learning_dataset['generation_history'] = array();
        }
        
        // Add to generation history
        $this->learning_dataset['generation_history'][] = array(
            'artwork_id' => $artwork_id,
            'timestamp' => time(),
            'parameters' => $generation_data,
            'success' => !empty($generation_data['success']),
            'processing_time' => isset($generation_data['processing_time']) ? $generation_data['processing_time'] : 0
        );
        
        // Keep history to last 1000 generations
        if (count($this->learning_dataset['generation_history']) > 1000) {
            $this->learning_dataset['generation_history'] = array_slice(
                $this->learning_dataset['generation_history'], 
                -1000
            );
        }
        
        // Save updated learning dataset
        update_option('vortex_huraii_learning_dataset', $this->learning_dataset);
    }
    
    /**
     * Update model from collected feedback
     */
    public function update_model_from_feedback() {
        // This would update the AI model based on collected feedback
        // In a real implementation, this might involve training or fine-tuning
        
        // Process style preferences
        $this->update_style_weights();
        
        // Process prompt patterns
        $this->update_prompt_patterns();
        
        // Process user preferences
        $this->update_user_preferences();
        
        // Log the update
        error_log('HURAII model updated from feedback at ' . current_time('mysql'));
    }
    
    /**
     * Update style weights based on feedback
     */
    private function update_style_weights() {
        if (empty($this->learning_dataset['feedback_data'])) {
            return;
        }
        
        // Process feedback data to update style weights
        foreach ($this->learning_dataset['feedback_data'] as $feedback) {
            if (!empty($feedback['style']) && isset($feedback['avg_rating'])) {
                $style = $feedback['style'];
                
                // Initialize style if not exists
                if (!isset($this->learning_dataset['styles'][$style])) {
                    $this->learning_dataset['styles'][$style] = array(
                        'weight' => 1.0,
                        'samples' => 0
                    );
                }
                
                // Adjust weight based on feedback
                // Higher ratings increase weight, lower ratings decrease it
                $weight_adjustment = ($feedback['avg_rating'] - 3) / 10; // -0.2 to +0.2
                
                // Get current weight and samples
                $current_weight = $this->learning_dataset['styles'][$style]['weight'];
                $samples = $this->learning_dataset['styles'][$style]['samples'];
                
                // Calculate new weight with diminishing effect as samples increase
                $learning_rate = get_option('vortex_huraii_learning_rate', 0.001);
                $adjustment_factor = $learning_rate * (1 / (1 + 0.1 * $samples));
                $new_weight = $current_weight + ($weight_adjustment * $adjustment_factor);
                
                // Ensure weight stays between 0.5 and 2.0
                $new_weight = max(0.5, min(2.0, $new_weight));
                
                // Update style weight and increment samples
                $this->learning_dataset['styles'][$style]['weight'] = $new_weight;
                $this->learning_dataset['styles'][$style]['samples']++;
            }
        }
        
        // Save updated learning dataset
        update_option('vortex_huraii_learning_dataset', $this->learning_dataset);
    }
    
    /**
     * Update prompt patterns based on feedback
     */
    private function update_prompt_patterns() {
        if (empty($this->learning_dataset['feedback_data'])) {
            return;
        }
        
        // Extract patterns from successful prompts
        $successful_prompts = array();
        
        foreach ($this->learning_dataset['feedback_data'] as $feedback) {
            if (!empty($feedback['prompt']) && isset($feedback['avg_rating']) && $feedback['avg_rating'] >= 4) {
                $successful_prompts[] = $feedback['prompt'];
            }
        }
        
        if (empty($successful_prompts)) {
            return;
        }
        
        // Analyze prompt patterns (simplified version)
        $words = array();
        $bigrams = array();
        
        foreach ($successful_prompts as $prompt) {
            // Convert to lowercase and split into words
            $prompt_words = preg_split('/\s+/', strtolower($prompt));
            
            // Count words
            foreach ($prompt_words as $word) {
                $word = trim($word);
                if (strlen($word) >= 3) { // Skip short words
                    if (!isset($words[$word])) {
                        $words[$word] = 0;
                    }
                    $words[$word]++;
                }
            }
            
            // Count bigrams (word pairs)
            for ($i = 0; $i < count($prompt_words) - 1; $i++) {
                $word1 = trim($prompt_words[$i]);
                $word2 = trim($prompt_words[$i + 1]);
                
                if (strlen($word1) >= 3 && strlen($word2) >= 3) {
                    $bigram = "$word1 $word2";
                    if (!isset($bigrams[$bigram])) {
                        $bigrams[$bigram] = 0;
                    }
                    $bigrams[$bigram]++;
                }
            }
        }
        
        // Sort by frequency
        arsort($words);
        arsort($bigrams);
        
        // Keep top patterns
        $words = array_slice($words, 0, 50, true);
        $bigrams = array_slice($bigrams, 0, 30, true);
        
        // Update prompt patterns
        $this->learning_dataset['prompt_patterns'] = array(
            'common_words' => $words,
            'common_bigrams' => $bigrams
        );
        
        // Save updated learning dataset
        update_option('vortex_huraii_learning_dataset', $this->learning_dataset);
    }
    
    /**
     * Update user preferences based on feedback
     */
    private function update_user_preferences() {
        global $wpdb;
        
        // Get all users who have generated artwork
        $users = $wpdb->get_col("
            SELECT DISTINCT user_id 
            FROM {$wpdb->prefix}vortex_artwork 
            WHERE ai_generated = 1
        ");
        
        if (empty($users)) {
            return;
        }
        
        foreach ($users as $user_id) {
            // Get user's highest rated generations
            $top_artworks = $wpdb->get_results($wpdb->prepare("
                SELECT a.*, r.rating
                FROM {$wpdb->prefix}vortex_artwork a
                JOIN {$wpdb->prefix}vortex_artwork_ratings r ON a.id = r.artwork_id
                WHERE a.user_id = %d AND a.ai_generated = 1 AND r.user_id = %d
                ORDER BY r.rating DESC
                LIMIT 10
            ", $user_id, $user_id));
            
            if (empty($top_artworks)) {
                continue;
            }
            
            // Extract preferences
            $style_preferences = array();
            $technique_preferences = array();
            $subject_preferences = array();
            
            foreach ($top_artworks as $artwork) {
                // Extract style
                if (!empty($artwork->generation_style)) {
                    if (!isset($style_preferences[$artwork->generation_style])) {
                        $style_preferences[$artwork->generation_style] = 0;
                    }
                    $style_preferences[$artwork->generation_style] += ($artwork->rating - 2.5) * 2; // Convert to -5 to +5
                }
                
                // Extract technique
                if (!empty($artwork->technique)) {
                    if (!isset($technique_preferences[$artwork->technique])) {
                        $technique_preferences[$artwork->technique] = 0;
                    }
                    $technique_preferences[$artwork->technique] += ($artwork->rating - 2.5) * 2;
                }
                
                // Extract subjects from prompt (simplified)
                if (!empty($artwork->prompt)) {
                    // Common art subjects to look for
                    $common_subjects = array('landscape', 'portrait', 'abstract', 'still-life', 'nature', 'urban', 'fantasy');
                    
                    foreach ($common_subjects as $subject) {
                        if (stripos($artwork->prompt, $subject) !== false) {
                            if (!isset($subject_preferences[$subject])) {
                                $subject_preferences[$subject] = 0;
                            }
                            $subject_preferences[$subject] += ($artwork->rating - 2.5) * 2;
                        }
                    }
                }
            }
            
            // Normalize preferences
            $style_preferences = $this->normalize_preferences($style_preferences);
            $technique_preferences = $this->normalize_preferences($technique_preferences);
            $subject_preferences = $this->normalize_preferences($subject_preferences);
            
            // Store user preferences
            $user_preferences = array(
                'styles' => $style_preferences,
                'techniques' => $technique_preferences,
                'subjects' => $subject_preferences,
                'updated' => current_time('mysql')
            );
            
            // Update user's style profile
            update_user_meta($user_id, 'vortex_artist_style_profile', $user_preferences);
            
            // Also store in the learning dataset for overall learning
            if (!isset($this->learning_dataset['user_preferences'][$user_id])) {
                $this->learning_dataset['user_preferences'][$user_id] = array();
            }
            
            $this->learning_dataset['user_preferences'][$user_id] = $user_preferences;
        }
        
        // Save updated learning dataset
        update_option('vortex_huraii_learning_dataset', $this->learning_dataset);
    }
    
    /**
     * Normalize preference scores
     * 
     * @param array $preferences Preferences array
     * @return array Normalized preferences
     */
    private function normalize_preferences($preferences) {
        if (empty($preferences)) {
            return array();
        }
        
        // Get sum of absolute values
        $sum = 0;
        foreach ($preferences as $value) {
            $sum += abs($value);
        }
        
        // If sum is zero, return equal weights
        if ($sum === 0) {
            $equal_weight = 1 / count($preferences);
            return array_fill_keys(array_keys($preferences), $equal_weight);
        }
        
        // Normalize
        $normalized = array();
        foreach ($preferences as $key => $value) {
            $normalized[$key] = $value / $sum;
        }
        
        return $normalized;
    }
    
    /**
     * Check if current user is an artist
     * 
     * @return bool Whether user is an artist
     */
    private function is_user_artist() {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $user_id = get_current_user_id();
        $user_role = get_user_meta($user_id, 'vortex_user_role', true);
        
        return $user_role === 'artist';
    }
}

// Initialize the HURAII system
function vortex_huraii() {
    return Vortex_HURAII::get_instance();
} 