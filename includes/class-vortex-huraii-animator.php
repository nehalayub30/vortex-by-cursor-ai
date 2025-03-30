<?php
/**
 * HURAII Artwork Animator
 *
 * Handles animation generation from static artwork for the Vortex AI Marketplace.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

class Vortex_HURAII_Animator {
    /**
     * The single instance of this class
     */
    private static $instance = null;
    
    /**
     * Animation types available
     */
    private $animation_types = array(
        'subtle_movement' => array(
            'name' => 'Subtle Movement',
            'description' => 'Gentle, subtle movement that maintains the integrity of the original artwork',
            'tola_cost' => 15,
            'max_duration' => 10, // seconds
            'api_model' => 'subtle-motion'
        ),
        'particle_flow' => array(
            'name' => 'Particle Flow',
            'description' => 'Transforms elements of the artwork into flowing particles',
            'tola_cost' => 20,
            'max_duration' => 15,
            'api_model' => 'particle-flow'
        ),
        'depth_parallax' => array(
            'name' => 'Depth Parallax',
            'description' => 'Creates a 3D parallax effect from the 2D artwork',
            'tola_cost' => 25,
            'max_duration' => 10,
            'api_model' => 'depth-parallax'
        ),
        'ambient_life' => array(
            'name' => 'Ambient Life',
            'description' => 'Adds ambient life-like movement to the entire composition',
            'tola_cost' => 30,
            'max_duration' => 15,
            'api_model' => 'ambient-life'
        ),
        'cinematic' => array(
            'name' => 'Cinematic',
            'description' => 'Creates a cinematic sequence with camera movement',
            'tola_cost' => 40,
            'max_duration' => 20,
            'api_model' => 'cinematic-motion'
        )
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
     * Create animation from artwork
     *
     * @param int $artwork_id The ID of the artwork to animate
     * @param string $animation_type Type of animation to create
     * @param int $duration Duration in seconds
     * @param bool $include_audio Whether to generate accompanying audio
     * @param string $audio_mood Mood of the audio if included
     * @param int $user_id User ID
     * @param bool $private Keep animation private
     * @return array|WP_Error Result data or error
     */
    public function create_animation($artwork_id, $animation_type, $duration, $include_audio, $audio_mood, $user_id, $private = true) {
        global $wpdb;
        
        // Get artwork details
        $artwork_table = $wpdb->prefix . 'vortex_artwork';
        $artwork = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $artwork_table WHERE id = %d AND user_id = %d",
            $artwork_id,
            $user_id
        ));
        
        if (!$artwork) {
            return new WP_Error('artwork_not_found', __('Artwork not found', 'vortex-ai-marketplace'));
        }
        
        // Validate animation type
        if (!isset($this->animation_types[$animation_type])) {
            return new WP_Error('invalid_animation_type', __('Invalid animation type', 'vortex-ai-marketplace'));
        }
        
        // Validate duration
        $animation_config = $this->animation_types[$animation_type];
        if ($duration <= 0 || $duration > $animation_config['max_duration']) {
            return new WP_Error(
                'invalid_duration',
                sprintf(
                    __('Duration must be between 1 and %d seconds', 'vortex-ai-marketplace'),
                    $animation_config['max_duration']
                )
            );
        }
        
        // Calculate token cost
        $tola_cost = $animation_config['tola_cost'];
        
        // Add cost for audio if included
        if ($include_audio) {
            $tola_cost += 10; // Additional cost for audio generation
        }
        
        // Check if user has enough tokens
        $wallet = Vortex_AI_Marketplace::get_instance()->wallet;
        if (!$wallet->check_tola_balance($user_id, $tola_cost)) {
            return new WP_Error(
                'insufficient_tokens',
                sprintf(
                    __('You need %d TOLA tokens for this animation. Current balance: %d', 'vortex-ai-marketplace'),
                    $tola_cost,
                    $wallet->get_tola_balance($user_id)
                )
            );
        }
        
        // Process the animation
        $start_time = microtime(true);
        $result = $this->process_animation($artwork, $animation_type, $duration, $include_audio, $audio_mood);
        $processing_time = round(microtime(true) - $start_time);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Deduct tokens
        $wallet->deduct_tola_tokens($user_id, $tola_cost, 'artwork_animation');
        
        // Save animation to database
        $animation_title = sprintf(__('%s (Animated)', 'vortex-ai-marketplace'), $artwork->title);
        
        $animation_id = $this->save_animation(
            $user_id,
            $animation_title,
            sprintf(__('Animation of "%s" using %s style', 'vortex-ai-marketplace'), 
                $artwork->title, 
                $animation_config['name']
            ),
            $result['video_path'],
            $result['thumbnail_path'],
            $artwork_id,
            $animation_type,
            $duration,
            $include_audio,
            $result['audio_path'] ?? null,
            $processing_time,
            $tola_cost,
            $private
        );
        
        // Return result
        return array(
            'id' => $animation_id,
            'title' => $animation_title,
            'video_path' => $result['video_path'],
            'thumbnail_path' => $result['thumbnail_path'],
            'audio_path' => $result['audio_path'] ?? null,
            'duration' => $duration,
            'animation_type' => $animation_config['name'],
            'processing_time' => $processing_time,
            'tola_cost' => $tola_cost
        );
    }
    
    /**
     * Process animation generation
     */
    private function process_animation($artwork, $animation_type, $duration, $include_audio, $audio_mood) {
        // Get original artwork file
        $artwork_file = $artwork->file_path;
        
        // If it's a URL, download it temporarily
        if (filter_var($artwork_file, FILTER_VALIDATE_URL)) {
            $temp_file = download_url($artwork_file);
            if (is_wp_error($temp_file)) {
                return $temp_file;
            }
            $artwork_file = $temp_file;
        }
        
        // Prepare API request parameters
        $api_model = $this->animation_types[$animation_type]['api_model'];
        
        // Prepare upload directory
        $upload_dir = wp_upload_dir();
        $animation_base_name = uniqid('animation_');
        $video_path = $upload_dir['path'] . '/' . $animation_base_name . '.mp4';
        $thumbnail_path = $upload_dir['path'] . '/' . $animation_base_name . '_thumb.jpg';
        $audio_path = null;
        
        // Call animation API
        $animation_result = $this->call_animation_api(
            $artwork_file,
            $video_path,
            $thumbnail_path,
            $api_model,
            $duration
        );
        
        // Generate audio if requested
        if ($include_audio && $animation_result) {
            $audio_path = $upload_dir['path'] . '/' . $animation_base_name . '.mp3';
            $audio_result = $this->generate_audio($audio_path, $artwork, $audio_mood, $duration);
            
            if ($audio_result) {
                // Combine video and audio
                $final_video_path = $upload_dir['path'] . '/' . $animation_base_name . '_with_audio.mp4';
                $this->combine_video_audio($video_path, $audio_path, $final_video_path);
                
                // Update video path to the new combined file
                if (file_exists($final_video_path)) {
                    // Delete the original silent video to save space
                    @unlink($video_path);
                    $video_path = $final_video_path;
                }
            }
        }
        
        // Clean up temp file if needed
        if (isset($temp_file) && file_exists($temp_file)) {
            @unlink($temp_file);
        }
        
        if (!$animation_result) {
            return new WP_Error('animation_failed', __('Failed to create animation', 'vortex-ai-marketplace'));
        }
        
        // Return paths to the generated files
        return array(
            'video_path' => $upload_dir['url'] . '/' . basename($video_path),
            'thumbnail_path' => $upload_dir['url'] . '/' . basename($thumbnail_path),
            'audio_path' => $audio_path ? $upload_dir['url'] . '/' . basename($audio_path) : null
        );
    }
    
    /**
     * Call animation generation API
     */
    private function call_animation_api($input_file, $output_file, $model, $duration) {
        // This is a placeholder for actual API integration
        // In a real implementation, you would:
        // 1. Call a third-party animation API like RunwayML, D-ID, etc.
        // 2. Or use a local implementation with libraries like PyTorch + Ebsynth
        
        // For demonstration purposes, simulate successful animation
        // Create a simple thumbnail representation
        $image = imagecreatefromstring(file_get_contents($input_file));
        if ($image !== false) {
            // Save as thumbnail
            $thumbnail_file = str_replace('.mp4', '_thumb.jpg', $output_file);
            imagejpeg($image, $thumbnail_file, 90);
            imagedestroy($image);
            
            // For demo, create a placeholder video file
            file_put_contents($output_file, 'PLACEHOLDER VIDEO CONTENT');
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Generate audio for animation
     */
    private function generate_audio($output_file, $artwork, $mood, $duration) {
        // This is a placeholder for actual audio generation
        // In a real implementation, you would:
        // 1. Call a music generation API like AIVA, Amper Music, etc.
        // 2. Or use a local implementation with libraries for audio synthesis
        
        // For demonstration purposes, simulate successful audio generation
        file_put_contents($output_file, 'PLACEHOLDER AUDIO CONTENT');
        return true;
    }
    
    /**
     * Combine video and audio using FFmpeg
     */
    private function combine_video_audio($video_path, $audio_path, $output_path) {
        // This would use FFmpeg to combine video and audio
        // For demonstration, just create a placeholder file
        file_put_contents($output_path, 'COMBINED VIDEO AND AUDIO');
        return true;
    }
    
    /**
     * Save animation to database
     */
    private function save_animation($user_id, $title, $description, $video_path, $thumbnail_path, $artwork_id, 
                                   $animation_type, $duration, $has_audio, $audio_path, $processing_time, 
                                   $tola_cost, $private) {
        global $wpdb;
        
        $animations_table = $wpdb->prefix . 'vortex_animations';
        
        $wpdb->insert(
            $animations_table,
            array(
                'user_id' => $user_id,
                'artwork_id' => $artwork_id,
                'title' => $title,
                'description' => $description,
                'animation_type' => $animation_type,
                'duration' => $duration,
                'has_audio' => $has_audio ? 1 : 0,
                'audio_path' => $audio_path,
                'file_format' => 'mp4',
                'file_path' => $video_path,
                'thumbnail_path' => $thumbnail_path,
                'creation_date' => current_time('mysql'),
                'processing_time' => $processing_time,
                'tola_cost' => $tola_cost,
                'private' => $private ? 1 : 0
            ),
            array('%d', '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d')
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get all available animation types
     */
    public function get_animation_types() {
        return $this->animation_types;
    }
} 