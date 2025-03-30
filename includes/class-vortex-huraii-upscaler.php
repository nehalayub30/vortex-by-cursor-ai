<?php
/**
 * HURAII Artwork Upscaler
 *
 * Handles high-quality image upscaling for the Vortex AI Marketplace.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

class Vortex_HURAII_Upscaler {
    /**
     * The single instance of this class
     */
    private static $instance = null;
    
    /**
     * Upscaling models available
     */
    private $available_models = array(
        'standard' => array(
            'name' => 'Standard',
            'tola_cost' => array('2x' => 5, '4x' => 10, '8x' => 20),
            'api_model' => 'real-esrgan'
        ),
        'detail_preserve' => array(
            'name' => 'Detail Preservation',
            'tola_cost' => array('2x' => 7, '4x' => 14, '8x' => 28),
            'api_model' => 'ultrasharp'
        ),
        'art_enhance' => array(
            'name' => 'Artistic Enhancement',
            'tola_cost' => array('2x' => 8, '4x' => 16, '8x' => 32),
            'api_model' => 'art-enhance-v2'
        ),
        'noise_reduce' => array(
            'name' => 'Noise Reduction',
            'tola_cost' => array('2x' => 6, '4x' => 12, '8x' => 24),
            'api_model' => 'denoise-sharp'
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
     * Upscale an artwork
     *
     * @param int $artwork_id The ID of the artwork to upscale
     * @param string $factor Upscale factor (2x, 4x, 8x)
     * @param string $method Upscale method/model
     * @param int $user_id User ID
     * @param bool $private Keep upscaled artwork private
     * @return array|WP_Error Result data or error
     */
    public function upscale_artwork($artwork_id, $factor, $method, $user_id, $private = true) {
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
        
        // Validate upscale factor
        if (!in_array($factor, array('2x', '4x', '8x'))) {
            return new WP_Error('invalid_factor', __('Invalid upscale factor', 'vortex-ai-marketplace'));
        }
        
        // Validate upscale method
        if (!isset($this->available_models[$method])) {
            return new WP_Error('invalid_method', __('Invalid upscale method', 'vortex-ai-marketplace'));
        }
        
        // Calculate token cost
        $tola_cost = $this->available_models[$method]['tola_cost'][$factor];
        
        // Check if user has enough tokens
        $wallet = Vortex_AI_Marketplace::get_instance()->wallet;
        if (!$wallet->check_tola_balance($user_id, $tola_cost)) {
            return new WP_Error(
                'insufficient_tokens',
                sprintf(
                    __('You need %d TOLA tokens for this upscale. Current balance: %d', 'vortex-ai-marketplace'),
                    $tola_cost,
                    $wallet->get_tola_balance($user_id)
                )
            );
        }
        
        // Process the upscaling
        $result = $this->process_upscale($artwork, $factor, $method);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Deduct tokens
        $wallet->deduct_tola_tokens($user_id, $tola_cost, 'artwork_upscale');
        
        // Save upscaled artwork to database
        $numeric_factor = intval($factor);
        $upscaled_title = sprintf(__('%s (Upscaled %sx)', 'vortex-ai-marketplace'), $artwork->title, $numeric_factor);
        
        $upscaled_id = $this->save_upscaled_artwork(
            $user_id,
            $upscaled_title,
            sprintf(__('Upscaled version of "%s" using %s method at %sx resolution', 'vortex-ai-marketplace'), 
                $artwork->title, 
                $this->available_models[$method]['name'],
                $numeric_factor
            ),
            $result['file_path'],
            $artwork_id,
            $method,
            $factor,
            $private
        );
        
        // Return result
        return array(
            'id' => $upscaled_id,
            'title' => $upscaled_title,
            'file_path' => $result['file_path'],
            'original_size' => $result['original_size'],
            'upscaled_size' => $result['upscaled_size'],
            'method' => $this->available_models[$method]['name'],
            'factor' => $factor,
            'tola_cost' => $tola_cost
        );
    }
    
    /**
     * Process the actual upscaling
     */
    private function process_upscale($artwork, $factor, $method) {
        // Get the original file
        $original_file = $artwork->file_path;
        
        // If it's a URL, download it temporarily
        if (filter_var($original_file, FILTER_VALIDATE_URL)) {
            $temp_file = download_url($original_file);
            if (is_wp_error($temp_file)) {
                return $temp_file;
            }
            $original_file = $temp_file;
        }
        
        // Get image dimensions
        $original_size = getimagesize($original_file);
        if ($original_size === false) {
            return new WP_Error('invalid_image', __('Invalid image file', 'vortex-ai-marketplace'));
        }
        
        // Calculate new dimensions
        $numeric_factor = intval($factor);
        $new_width = $original_size[0] * $numeric_factor;
        $new_height = $original_size[1] * $numeric_factor;
        
        // Maximum allowed dimensions (to prevent server overload)
        $max_dimension = 8192; // 8K resolution
        if ($new_width > $max_dimension || $new_height > $max_dimension) {
            return new WP_Error(
                'dimensions_too_large',
                sprintf(
                    __('Upscaled dimensions would exceed maximum allowed (%dx%d)', 'vortex-ai-marketplace'),
                    $max_dimension,
                    $max_dimension
                )
            );
        }
        
        // Prepare API request to upscaling service
        $api_model = $this->available_models[$method]['api_model'];
        
        // Prepare upload directory
        $upload_dir = wp_upload_dir();
        $upload_path = $upload_dir['path'] . '/' . uniqid('upscaled_') . '.png';
        
        // Call to external API or local processing
        // This is a placeholder for the actual upscaling logic, which would depend on your implementation
        $success = $this->call_upscale_api($original_file, $upload_path, $api_model, $numeric_factor);
        
        // Clean up temp file if needed
        if (isset($temp_file) && file_exists($temp_file)) {
            @unlink($temp_file);
        }
        
        if (!$success) {
            return new WP_Error('upscale_failed', __('Failed to upscale image', 'vortex-ai-marketplace'));
        }
        
        // Get new image dimensions
        $upscaled_size = getimagesize($upload_path);
        
        // Return result with file path
        return array(
            'file_path' => $upload_dir['url'] . '/' . basename($upload_path),
            'original_size' => array(
                'width' => $original_size[0],
                'height' => $original_size[1]
            ),
            'upscaled_size' => array(
                'width' => $upscaled_size[0],
                'height' => $upscaled_size[1]
            )
        );
    }
    
    /**
     * Call to upscaling API
     */
    private function call_upscale_api($input_file, $output_file, $model, $factor) {
        // This is a placeholder for actual API integration
        // In a real implementation, you would:
        // 1. Call a third-party upscaling API like Replicate, RunwayML, etc.
        // 2. Or use a local implementation with libraries like PyTorch + ESRGAN
        
        // For demonstration purposes, we'll simulate successful upscaling
        // by creating a copy of the original file (in a real implementation this would be an upscaled version)
        return copy($input_file, $output_file);
    }
    
    /**
     * Save upscaled artwork to database
     */
    private function save_upscaled_artwork($user_id, $title, $description, $file_path, $original_artwork_id, $method, $factor, $private) {
        global $wpdb;
        
        $artwork_table = $wpdb->prefix . 'vortex_artwork';
        $upscaled_table = $wpdb->prefix . 'vortex_artwork_upscaled';
        
        // Insert into artwork table
        $wpdb->insert(
            $artwork_table,
            array(
                'user_id' => $user_id,
                'title' => $title,
                'description' => $description,
                'file_path' => $file_path,
                'is_seed' => 0,
                'ai_generated' => 1,
                'upload_date' => current_time('mysql'),
                'private' => $private ? 1 : 0
            ),
            array('%d', '%s', '%s', '%s', '%d', '%d', '%s', '%d')
        );
        
        $artwork_id = $wpdb->insert_id;
        
        // Insert upscaled artwork metadata
        $wpdb->insert(
            $upscaled_table,
            array(
                'artwork_id' => $artwork_id,
                'original_artwork_id' => $original_artwork_id,
                'upscale_method' => $method,
                'upscale_factor' => $factor
            ),
            array('%d', '%d', '%s', '%s')
        );
        
        return $artwork_id;
    }
    
    /**
     * Get all available upscaling methods
     */
    public function get_available_methods() {
        return $this->available_models;
    }
} 