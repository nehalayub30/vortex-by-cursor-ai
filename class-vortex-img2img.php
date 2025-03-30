<?php
/**
 * Image-to-Image Transformation System
 *
 * @package VORTEX_AI_Marketplace
 * @subpackage AI_Processing
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * VORTEX_Img2Img Class
 * 
 * Handles image-to-image transformations including style transfer,
 * inpainting, outpainting, enhancement, and upscaling.
 * Integrates with HURAII, CLOE, and BusinessStrategist for deep learning.
 *
 * @since 1.0.0
 */
class VORTEX_Img2Img {
    /**
     * Instance of this class.
     *
     * @since 1.0.0
     * @var object
     */
    protected static $instance = null;
    
    /**
     * Supported image formats
     *
     * @since 1.0.0
     * @var array
     */
    private $supported_formats = array('jpg', 'jpeg', 'png', 'webp');
    
    /**
     * Model loader instance
     *
     * @since 1.0.0
     * @var VORTEX_Model_Loader
     */
    private $model_loader;
    
    /**
     * Active transformation models
     *
     * @since 1.0.0
     * @var array
     */
    private $transformation_models = array();
    
    /**
     * AI agent states for deep learning
     *
     * @since 1.0.0
     * @var array
     */
    private $ai_agent_states = array();
    
    /**
     * Constructor
     *
     * @since 1.0.0
     */
    private function __construct() {
        // Get model loader if available
        if (class_exists('VORTEX_Model_Loader')) {
            $this->model_loader = VORTEX_Model_Loader::get_instance();
        }
        
        // Initialize AI agent states
        $this->initialize_ai_agents();
        
        // Setup hooks
        $this->setup_hooks();
    }
    
    /**
     * Get instance of this class.
     *
     * @since 1.0.0
     * @return VORTEX_Img2Img
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
        add_action('wp_ajax_vortex_style_transfer', array($this, 'ajax_style_transfer'));
        add_action('wp_ajax_vortex_inpaint_image', array($this, 'ajax_inpaint_image'));
        add_action('wp_ajax_vortex_upscale_image', array($this, 'ajax_upscale_image'));
        
        // Filter hooks for AI integrations
        add_filter('vortex_huraii_process_image', array($this, 'process_image_for_huraii'), 10, 2);
        add_filter('vortex_cloe_analyze_style', array($this, 'analyze_style_for_cloe'), 10, 2);
        
        // Actions for learning and feedback
        add_action('vortex_img_transformation_complete', array($this, 'track_transformation_results'), 10, 3);
    }
    
    /**
     * Initialize AI agents for deep learning
     *
     * @since 1.0.0
     * @return void
     */
    private function initialize_ai_agents() {
        $this->ai_agent_states = array(
            'HURAII' => array(
                'active' => true,
                'learning_mode' => 'active',
                'last_updated' => current_time('timestamp'),
                'transformation_count' => 0,
                'success_rate' => 0.95
            ),
            'CLOE' => array(
                'active' => true,
                'learning_mode' => 'active',
                'last_updated' => current_time('timestamp'),
                'style_analysis_count' => 0,
                'recommendation_accuracy' => 0.87
            ),
            'BusinessStrategist' => array(
                'active' => true,
                'learning_mode' => 'active',
                'last_updated' => current_time('timestamp'),
                'market_analysis_count' => 0,
                'trend_prediction_accuracy' => 0.82
            )
        );
        
        // Load saved AI states if available
        $saved_states = get_option('vortex_ai_agent_states', false);
        if ($saved_states) {
            $this->ai_agent_states = array_merge($this->ai_agent_states, $saved_states);
        }
    }
    
    /**
     * Apply style transfer to an image
     *
     * @since 1.0.0
     * @param array $source_image Source image data
     * @param string|array $style_reference Style reference image or preset
     * @param float $strength Style transfer strength (0-1)
     * @return array|WP_Error Processed image data or error
     */
    public function apply_style_transfer($source_image, $style_reference, $strength = 0.75) {
        // Validate inputs
        if (empty($source_image) || (empty($style_reference) && !is_string($style_reference))) {
            return new WP_Error('invalid_input', __('Invalid source image or style reference', 'vortex'));
        }
        
        $strength = floatval($strength);
        $strength = max(0.1, min(1.0, $strength)); // Ensure strength is between 0.1 and 1.0
        
        // Extract image data
        $source_data = $this->get_image_data($source_image);
        if (is_wp_error($source_data)) {
            return $source_data;
        }
        
        // Process style reference
        $style_data = '';
        if (is_string($style_reference) && in_array($style_reference, $this->get_style_presets())) {
            // Using preset style
            $style_data = $this->get_preset_style_data($style_reference);
        } else {
            // Using custom style image
            $style_data = $this->get_image_data($style_reference);
            if (is_wp_error($style_data)) {
                return $style_data;
            }
        }
        
        // Check if HURAII is active for this transformation
        if (!$this->ai_agent_states['HURAII']['active']) {
            return new WP_Error('huraii_inactive', __('HURAII AI is currently inactive', 'vortex'));
        }
        
        try {
            // Process style transfer using the model loader
            $model_id = 'style-transfer';
            
            // First check if model is available
            if (!$this->model_loader || !$this->model_loader->is_model_available($model_id)) {
                // Fallback to direct processing if model loader is unavailable
                return $this->process_style_transfer_fallback($source_data, $style_data, $strength);
            }
            
            // Process with model loader
            $result = $this->model_loader->run_inference($model_id, array(
                'source_image' => $source_data,
                'style_image' => $style_data,
                'strength' => $strength,
                'preserve_color' => true
            ));
            
            if (is_wp_error($result)) {
                return $result;
            }
            
            // Track successful transformation for learning
            $this->track_transformation_results('style_transfer', true, array(
                'strength' => $strength,
                'style_type' => is_string($style_reference) ? 'preset' : 'custom'
            ));
            
            // Update AI agent states
            $this->update_agent_learning('HURAII', 'style_transfer', $result);
            
            return $result;
        } catch (Exception $e) {
            // Track failed transformation for learning
            $this->track_transformation_results('style_transfer', false, array(
                'error' => $e->getMessage()
            ));
            
            return new WP_Error('style_transfer_failed', $e->getMessage());
        }
    }
    
    /**
     * Fallback processing for style transfer
     *
     * @since 1.0.0
     * @param string $source_data Base64 encoded source image
     * @param string $style_data Base64 encoded style image
     * @param float $strength Style transfer strength
     * @return array Processed image data
     */
    private function process_style_transfer_fallback($source_data, $style_data, $strength) {
        // This is a simplified fallback when the model loader is unavailable
        // In a real implementation, this would use a more basic algorithm or API call
        
        // Create temporary files
        $source_file = $this->create_temp_image_file($source_data);
        $style_file = $this->create_temp_image_file($style_data);
        
        if (is_wp_error($source_file) || is_wp_error($style_file)) {
            return new WP_Error('temp_file_creation_failed', __('Failed to create temporary files', 'vortex'));
        }
        
        // Process using ImageMagick if available
        if (extension_loaded('imagick')) {
            try {
                $source_img = new Imagick($source_file);
                $style_img = new Imagick($style_file);
                
                // Resize style image to match source dimensions
                $style_img->resizeImage(
                    $source_img->getImageWidth(),
                    $source_img->getImageHeight(),
                    Imagick::FILTER_LANCZOS,
                    1
                );
                
                // Basic color transfer (not actual style transfer, just a visual approximation)
                $source_img->modulateImage(100, 100, 100); // Reset
                $source_img->colorizeImage('#000000', $strength * 100); // Prepare for blending
                $source_img->compositeImage($style_img, Imagick::COMPOSITE_BLEND, 0, 0);
                
                // Save result
                $output_file = $this->get_temp_filename('jpg');
                $source_img->writeImage($output_file);
                
                // Clean up
                $source_img->destroy();
                $style_img->destroy();
                unlink($source_file);
                unlink($style_file);
                
                // Process result
                $image_data = base64_encode(file_get_contents($output_file));
                $attachment_id = $this->save_image_to_media_library($image_data, 'style_transfer_result');
                unlink($output_file);
                
                if (is_wp_error($attachment_id)) {
                    return $attachment_id;
                }
                
                return array(
                    'image_url' => wp_get_attachment_url($attachment_id),
                    'image_id' => $attachment_id,
                    'image_data' => $image_data
                );
            } catch (Exception $e) {
                return new WP_Error('imagick_processing_failed', $e->getMessage());
            }
        }
        
        // Clean up
        if (file_exists($source_file)) unlink($source_file);
        if (file_exists($style_file)) unlink($style_file);
        
        return new WP_Error('fallback_processing_failed', __('Style transfer fallback processing is not available', 'vortex'));
    }
    
    /**
     * Perform inpainting on an image (replace masked area with generated content)
     *
     * @since 1.0.0
     * @param array $source_image Source image data
     * @param array $mask_data Mask data (base64 or coordinates)
     * @param string $prompt Text prompt to guide generation
     * @return array|WP_Error Processed image data or error
     */
    public function inpaint_image($source_image, $mask_data, $prompt) {
        // Validate inputs
        if (empty($source_image) || empty($mask_data)) {
            return new WP_Error('invalid_input', __('Invalid source image or mask data', 'vortex'));
        }
        
        // Extract image data
        $source_data = $this->get_image_data($source_image);
        if (is_wp_error($source_data)) {
            return $source_data;
        }
        
        // Process mask data
        $mask_image = '';
        if (isset($mask_data['base64'])) {
            $mask_image = $mask_data['base64'];
        } elseif (isset($mask_data['coordinates'])) {
            $mask_image = $this->generate_mask_from_coordinates($source_data, $mask_data['coordinates']);
            if (is_wp_error($mask_image)) {
                return $mask_image;
            }
        } else {
            return new WP_Error('invalid_mask', __('Invalid mask data format', 'vortex'));
        }
        
        // Check if HURAII is active for this transformation
        if (!$this->ai_agent_states['HURAII']['active']) {
            return new WP_Error('huraii_inactive', __('HURAII AI is currently inactive', 'vortex'));
        }
        
        try {
            // Process inpainting using the model loader
            $model_id = 'inpainting';
            
            // First check if model is available
            if (!$this->model_loader || !$this->model_loader->is_model_available($model_id)) {
                return new WP_Error('model_unavailable', __('Inpainting model is not available', 'vortex'));
            }
            
            // Process with model loader
            $result = $this->model_loader->run_inference($model_id, array(
                'source_image' => $source_data,
                'mask_image' => $mask_image,
                'prompt' => $prompt,
                'negative_prompt' => '',
                'steps' => 50,
                'cfg_scale' => 7.5,
                'seed' => -1
            ));
            
            if (is_wp_error($result)) {
                return $result;
            }
            
            // Track successful transformation for learning
            $this->track_transformation_results('inpainting', true, array(
                'prompt_length' => strlen($prompt),
                'mask_size_percent' => $this->calculate_mask_size_percent($mask_image)
            ));
            
            // Update AI agent states
            $this->update_agent_learning('HURAII', 'inpainting', $result);
            
            return $result;
        } catch (Exception $e) {
            // Track failed transformation for learning
            $this->track_transformation_results('inpainting', false, array(
                'error' => $e->getMessage()
            ));
            
            return new WP_Error('inpainting_failed', $e->getMessage());
        }
    }
    
    /**
     * Generate mask image from coordinates
     *
     * @since 1.0.0
     * @param string $source_data Base64 encoded source image
     * @param array $coordinates Array of x,y coordinates defining a polygon
     * @return string|WP_Error Base64 encoded mask image or error
     */
    private function generate_mask_from_coordinates($source_data, $coordinates) {
        if (empty($coordinates) || count($coordinates) < 3) {
            return new WP_Error('invalid_coordinates', __('At least three coordinates are required for a mask', 'vortex'));
        }
        
        try {
            // Create temporary source file
            $source_file = $this->create_temp_image_file($source_data);
            if (is_wp_error($source_file)) {
                return $source_file;
            }
            
            // Get image dimensions
            list($width, $height) = getimagesize($source_file);
            
            // Create mask image
            $mask = imagecreatetruecolor($width, $height);
            $black = imagecolorallocate($mask, 0, 0, 0);
            $white = imagecolorallocate($mask, 255, 255, 255);
            
            // Fill with black (transparent in mask)
            imagefill($mask, 0, 0, $black);
            
            // Prepare polygon points
            $points = array();
            foreach ($coordinates as $coord) {
                $points[] = $coord['x'];
                $points[] = $coord['y'];
            }
            
            // Draw white polygon (masked area)
            imagefilledpolygon($mask, $points, count($coordinates), $white);
            
            // Save mask to file
            $output_file = $this->get_temp_filename('png');
            imagepng($mask, $output_file);
            
            // Clean up
            imagedestroy($mask);
            unlink($source_file);
            
            // Return base64 encoded mask
            $mask_data = base64_encode(file_get_contents($output_file));
            unlink($output_file);
            
            return $mask_data;
        } catch (Exception $e) {
            return new WP_Error('mask_generation_failed', $e->getMessage());
        }
    }
    
    /**
     * Calculate percentage of image covered by mask
     *
     * @since 1.0.0
     * @param string $mask_data Base64 encoded mask image
     * @return float Percentage (0-100) of image that is masked
     */
    private function calculate_mask_size_percent($mask_data) {
        try {
            // Create temporary mask file
            $mask_file = $this->create_temp_image_file($mask_data);
            if (is_wp_error($mask_file)) {
                return 0;
            }
            
            // Load mask image
            $mask = imagecreatefromstring(file_get_contents($mask_file));
            $width = imagesx($mask);
            $height = imagesy($mask);
            $total_pixels = $width * $height;
            $masked_pixels = 0;
            
            // Count white pixels (masked area)
            for ($x = 0; $x < $width; $x++) {
                for ($y = 0; $y < $height; $y++) {
                    $rgb = imagecolorat($mask, $x, $y);
                    $colors = imagecolorsforindex($mask, $rgb);
                    if ($colors['red'] > 200 && $colors['green'] > 200 && $colors['blue'] > 200) {
                        $masked_pixels++;
                    }
                }
            }
            
            // Clean up
            imagedestroy($mask);
            unlink($mask_file);
            
            return ($masked_pixels / $total_pixels) * 100;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Upscale an image to higher resolution
     *
     * @since 1.0.0
     * @param array $source_image Source image data
     * @param int $scale Upscale factor (2, 4)
     * @return array|WP_Error Processed image data or error
     */
    public function upscale_image($source_image, $scale = 2) {
        // Validate inputs
        if (empty($source_image)) {
            return new WP_Error('invalid_input', __('Invalid source image', 'vortex'));
        }
        
        $scale = intval($scale);
        if ($scale !== 2 && $scale !== 4) {
            $scale = 2; // Default to 2x upscaling
        }
        
        // Extract image data
        $source_data = $this->get_image_data($source_image);
        if (is_wp_error($source_data)) {
            return $source_data;
        }
        
        // Check if HURAII is active for this transformation
        if (!$this->ai_agent_states['HURAII']['active']) {
            return new WP_Error('huraii_inactive', __('HURAII AI is currently inactive', 'vortex'));
        }
        
        try {
            // Process upscaling using the model loader
            $model_id = 'upscaler-' . $scale . 'x';
            
            // First check if model is available
            if (!$this->model_loader || !$this->model_loader->is_model_available($model_id)) {
                // Fallback to basic upscaling
                return $this->upscale_image_fallback($source_data, $scale);
            }
            
            // Process with model loader
            $result = $this->model_loader->run_inference($model_id, array(
                'image' => $source_data,
                'scale' => $scale,
                'face_enhance' => true
            ));
            
            if (is_wp_error($result)) {
                return $result;
            }
            
            // Track successful transformation for learning
            $this->track_transformation_results('upscale', true, array(
                'scale' => $scale
            ));
            
            // Update AI agent states
            $this->update_agent_learning('HURAII', 'upscale', $result);
            
            return $result;
        } catch (Exception $e) {
            // Track failed transformation for learning
            $this->track_transformation_results('upscale', false, array(
                'error' => $e->getMessage()
            ));
            
            return new WP_Error('upscale_failed', $e->getMessage());
        }
    }
    
    /**
     * Fallback upscaling when model is unavailable
     *
     * @since 1.0.0
     * @param string $source_data Base64 encoded source image
     * @param int $scale Upscale factor
     * @return array|WP_Error Processed image data or error
     */
    private function upscale_image_fallback($source_data, $scale) {
        // Create temporary file
        $source_file = $this->create_temp_image_file($source_data);
        if (is_wp_error($source_file)) {
            return $source_file;
        }
        
        try {
            // Process using ImageMagick if available
            if (extension_loaded('imagick')) {
                $source_img = new Imagick($source_file);
                
                // Get original dimensions
                $width = $source_img->getImageWidth();
                $height = $source_img->getImageHeight();
                
                // Calculate new dimensions
                $new_width = $width * $scale;
                $new_height = $height * $scale;
                
                // Resize using Lanczos filter (high quality)
                $source_img->resizeImage($new_width, $new_height, Imagick::FILTER_LANCZOS, 1);
                
                // Enhance sharpness slightly
                $source_img->sharpenImage(0, 1.0);
                
                // Save result
                $output_file = $this->get_temp_filename('jpg');
                $source_img->writeImage($output_file);
                
                // Clean up
                $source_img->destroy();
                unlink($source_file);
                
                // Process result
                $image_data = base64_encode(file_get_contents($output_file));
                $attachment_id = $this->save_image_to_media_library($image_data, 'upscaled_image');
                unlink($output_file);
                
                if (is_wp_error($attachment_id)) {
                    return $attachment_id;
                }
                
                return array(
                    'image_url' => wp_get_attachment_url($attachment_id),
                    'image_id' => $attachment_id,
                    'image_data' => $image_data
                );
            } else {
                // Fallback to GD
                $source_img = imagecreatefromstring(base64_decode($source_data));
                
                // Get original dimensions
                $width = imagesx($source_img);
                $height = imagesy($source_img);
                
                // Calculate new dimensions
                $new_width = $width * $scale;
                $new_height = $height * $scale;
                
                // Create new image
                $new_img = imagecreatetruecolor($new_width, $new_height);
                
                // Resize with bicubic interpolation
                imagecopyresampled($new_img, $source_img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
                
                // Save result
                $output_file = $this->get_temp_filename('jpg');
                imagejpeg($new_img, $output_file, 90);
                
                // Clean up
                imagedestroy($source_img);
                imagedestroy($new_img);
                unlink($source_file);
                
                // Process result
                $image_data = base64_encode(file_get_contents($output_file));
                $attachment_id = $this->save_image_to_media_library($image_data, 'upscaled_image');
                unlink($output_file);
                
                if (is_wp_error($attachment_id)) {
                    return $attachment_id;
                }
                
                return array(
                    'image_url' => wp_get_attachment_url($attachment_id),
                    'image_id' => $attachment_id,
                    'image_data' => $image_data
                );
            }
        } catch (Exception $e) {
            // Clean up
            if (file_exists($source_file)) unlink($source_file);
            
            return new WP_Error('upscale_fallback_failed', $e->getMessage());
        }
    }
    
    /**
     * Enhance image quality (color, contrast, sharpness)
     *
     * @since 1.0.0
     * @param array $source_image Source image data
     * @return array|WP_Error Processed image data or error
     */
    public function enhance_image($source_image) {
        // Validate inputs
        if (empty($source_image)) {
            return new WP_Error('invalid_input', __('Invalid source image', 'vortex'));
        }
        
        // Extract image data
        $source_data = $this->get_image_data($source_image);
        if (is_wp_error($source_data)) {
            return $source_data;
        }
        
        // Check if HURAII is active for this transformation
        if (!$this->ai_agent_states['HURAII']['active']) {
            return new WP_Error('huraii_inactive', __('HURAII AI is currently inactive', 'vortex'));
        }
        
        try {
            // Process enhancement using the model loader
            $model_id = 'image-enhancer';
            
            // First check if model is available
            if (!$this->model_loader || !$this->model_loader->is_model_available($model_id)) {
                // Fallback to basic enhancement
                return $this->enhance_image_fallback($source_data);
            }
            
            // Process with model loader
            $result = $this->model_loader->run_inference($model_id, array(
                'image' => $source_data,
                'enhance_color' => true,
                'enhance_sharpness' => true,
                'enhance_contrast' => true
            ));
            
            if (is_wp_error($result)) {
                return $result;
            }
            
            // Track successful transformation for learning
            $this->track_transformation_results('enhance', true, array());
            
            // Update AI agent states
            $this->update_agent_learning('HURAII', 'enhance', $result);
            
            return $result;
        } catch (Exception $e) {
            // Track failed transformation for learning
            $this->track_transformation_results('enhance', false, array(
                'error' => $e->getMessage()
            ));
            
            return new WP_Error('enhance_failed', $e->getMessage());
        }
    }
    
    /**
     * Fallback enhancement when model is unavailable
     *
     * @since 1.0.0
     * @param string $source_data Base64 encoded source image
     * @return array|WP_Error Processed image data or error
     */
    private function enhance_image_fallback($source_data) {
        // Create temporary file
        $source_file = $this->create_temp_image_file($source_data);
        if (is_wp_error($source_file)) {
            return $source_file;
        }
        
        try {
            // Process using ImageMagick if available
            if (extension_loaded('imagick')) {
                $source_img = new Imagick($source_file);
                
                // Enhance contrast
                $source_img->contrastImage(true);
                
                // Enhance color saturation
                $source_img->modulateImage(100, 115, 100); // Increase saturation by 15%
                
                // Sharpen
                $source_img->sharpenImage(0, 1.0);
                
                // Save result
                $output_file = $this->get_temp_filename('jpg');
                $source_img->writeImage($output_file);
                
                // Clean up
                $source_img->destroy();
                unlink($source_file);
                
                // Process result
                $image_data = base64_encode(file_get_contents($output_file));
                $attachment_id = $this->save_image_to_media_library($image_data, 'enhanced_image');
                unlink($output_file);
                
                if (is_wp_error($attachment_id)) {
                    return $attachment_id;
                }
                
                return array(
                    'image_url' => wp_get_attachment_url($attachment_id),
                    'image_id' => $attachment_id,
                    'image_data' => $image_data
                );
            } else {
                // No GD fallback for enhancement - requires ImageMagick
                unlink($source_file);
                return new WP_Error('enhance_fallback_unavailable', __('Image enhancement requires ImageMagick', 'vortex'));
            }
        } catch (Exception $e) {
            // Clean up
            if (file_exists($source_file)) unlink($source_file);
            
            return new WP_Error('enhance_fallback_failed', $e->getMessage());
        }
    }
    
    /**
     * Process image for HURAII AI system
     * 
     * Implements filter: vortex_huraii_process_image
     *
     * @since 1.0.0
     * @param array $image_data Source image data
     * @param array $processing_params Processing parameters
     * @return array|WP_Error Processed image data or error
     */
    public function process_image_for_huraii($image_data, $processing_params) {
        if (empty($processing_params['process_type'])) {
            return new WP_Error('missing_process_type', __('Processing type is required', 'vortex'));
        }
        
        switch ($processing_params['process_type']) {
            case 'style_transfer':
                return $this->apply_style_transfer(
                    $image_data,
                    $processing_params['style_reference'],
                    $processing_params['strength'] ?? 0.75
                );
                
            case 'inpaint':
                return $this->inpaint_image(
                    $image_data,
                    $processing_params['mask_data'],
                    $processing_params['prompt'] ?? ''
                );
                
            case 'upscale':
                return $this->upscale_image(
                    $image_data,
                    $processing_params['scale'] ?? 2
                );
                
            case 'enhance':
                return $this->enhance_image($image_data);
                
            default:
                return new WP_Error(
                    'unsupported_process_type',
                    sprintf(__('Unsupported process type: %s', 'vortex'), $processing_params['process_type'])
                );
        }
    }
    
    /**
     * Analyze image style for CLOE AI
     * 
     * Implements filter: vortex_cloe_analyze_style
     *
     * @since 1.0.0
     * @param array $image_data Source image data
     * @param array $analysis_params Analysis parameters
     * @return array Style analysis data
     */
    public function analyze_style_for_cloe($image_data, $analysis_params) {
        // Validate input
        if (empty($image_data)) {
            return array(
                'error' => __('No image data provided for style analysis', 'vortex')
            );
        }
        
        // Extract image data
        $source_data = $this->get_image_data($image_data);
        if (is_wp_error($source_data)) {
            return array(
                'error' => $source_data->get_error_message()
            );
        }
        
        // Check if CLOE is active
        if (!$this->ai_agent_states['CLOE']['active']) {
            return array(
                'error' => __('CLOE AI is currently inactive', 'vortex')
            );
        }
        
        try {
            // Process style analysis
            $style_features = $this->extract_style_features($source_data);
            
            // Get art historical context if requested
            $art_history_context = array();
            if (!empty($analysis_params['include_art_history']) && $analysis_params['include_art_history']) {
                $art_history_context = $this->get_art_historical_context($style_features);
            }
            
            // Get market trends if requested
            $market_trends = array();
            if (!empty($analysis_params['include_market_trends']) && $analysis_params['include_market_trends']) {
                $market_trends = $this->get_style_market_trends($style_features);
            }
            
            // Update CLOE's learning state
            $this->update_agent_learning('CLOE', 'style_analysis', array(
                'features' => $style_features,
                'art_history' => $art_history_context,
                'market_trends' => $market_trends
            ));
            
            return array(
                'style_features' => $style_features,
                'art_history_context' => $art_history_context,
                'market_trends' => $market_trends
            );
        } catch (Exception $e) {
            return array(
                'error' => $e->getMessage()
            );
        }
    }
    
    /**
     * Extract style features from an image
     *
     * @since 1.0.0
     * @param string $image_data Base64 encoded image data
     * @return array Style features
     */
    private function extract_style_features($image_data) {
        // Create temporary file
        $image_file = $this->create_temp_image_file($image_data);
        if (is_wp_error($image_file)) {
            throw new Exception($image_file->get_error_message());
        }
        
        try {
            // Basic feature extraction using GD
            $image = imagecreatefromstring(base64_decode($image_data));
            $width = imagesx($image);
            $height = imagesy($image);
            
            // Color histogram analysis
            $colors = array();
            $saturation_total = 0;
            $lightness_total = 0;
            $sample_count = 100;
            
            for ($i = 0; $i < $sample_count; $i++) {
                $x = rand(0, $width - 1);
                $y = rand(0, $height - 1);
                $rgb = imagecolorat($image, $x, $y);
                
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                
                // Convert RGB to HSL
                $hsl = $this->rgb_to_hsl($r, $g, $b);
                
                // Round hue to nearest 10 degrees for histogram
                $hue_bucket = round($hsl['h'] / 10) * 10;
                if (!isset($colors[$hue_bucket])) {
                    $colors[$hue_bucket] = 0;
                }
                $colors[$hue_bucket]++;
                
                $saturation_total += $hsl['s'];
                $lightness_total += $hsl['l'];
            }
            
            // Find dominant colors
            arsort($colors);
            $dominant_colors = array_slice($colors, 0, 3, true);
            
            // Calculate average saturation and lightness
            $avg_saturation = $saturation_total / $sample_count;
            $avg_lightness = $lightness_total / $sample_count;
            
            // Analyze composition
            $composition = $this->analyze_composition($image, $width, $height);
            
            // Analyze texture
            $texture = $this->analyze_texture($image, $width, $height);
            
            // Clean up
            imagedestroy($image);
            unlink($image_file);
            
            // Return features
            return array(
                'dominant_colors' => $dominant_colors,
                'color_palette' => $this->get_color_names(array_keys($dominant_colors)),
                'avg_saturation' => $avg_saturation,
                'avg_lightness' => $avg_lightness,
                'color_mood' => $this->determine_color_mood($avg_saturation, $avg_lightness, $dominant_colors),
                'composition' => $composition,
                'texture' => $texture
            );
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
} 