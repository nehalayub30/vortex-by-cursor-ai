<?php
/**
 * HURAII Immersive Experience Generator
 *
 * Handles 3D models, AR and VR experiences from artwork for the Vortex AI Marketplace.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

class Vortex_HURAII_Immersive {
    /**
     * The single instance of this class
     */
    private static $instance = null;
    
    /**
     * Available 3D model types
     */
    private $model_types = array(
        'sculpture' => array(
            'name' => 'Digital Sculpture',
            'description' => 'Transform your artwork into a 3D sculpture',
            'tola_cost' => array(
                'standard' => 20,
                'high' => 35,
                'ultra' => 50
            ),
            'api_model' => '3d-sculpture'
        ),
        'scene' => array(
            'name' => '3D Scene',
            'description' => 'Convert your artwork into a complete 3D scene',
            'tola_cost' => array(
                'standard' => 30,
                'high' => 45,
                'ultra' => 60
            ),
            'api_model' => '3d-scene'
        ),
        'character' => array(
            'name' => '3D Character',
            'description' => 'Transform characters in your artwork into 3D models',
            'tola_cost' => array(
                'standard' => 25,
                'high' => 40,
                'ultra' => 55
            ),
            'api_model' => '3d-character'
        ),
        'environment' => array(
            'name' => 'Environment',
            'description' => 'Convert landscapes and backgrounds into immersive 3D environments',
            'tola_cost' => array(
                'standard' => 35,
                'high' => 50,
                'ultra' => 65
            ),
            'api_model' => '3d-environment'
        )
    );
    
    /**
     * Available VR environment types
     */
    private $vr_environment_types = array(
        'gallery' => array(
            'name' => 'Virtual Gallery',
            'description' => 'Display your artwork in a customizable virtual gallery',
            'tola_cost' => array(
                'standard' => 35,
                'complex' => 55,
                'expansive' => 75
            ),
            'api_model' => 'vr-gallery'
        ),
        'immersive' => array(
            'name' => 'Immersive World',
            'description' => 'Step inside your artwork as a complete immersive world',
            'tola_cost' => array(
                'standard' => 50,
                'complex' => 70,
                'expansive' => 90
            ),
            'api_model' => 'vr-immersive'
        ),
        'interactive' => array(
            'name' => 'Interactive Environment',
            'description' => 'Create an interactive environment based on your artwork',
            'tola_cost' => array(
                'standard' => 60,
                'complex' => 80,
                'expansive' => 100
            ),
            'api_model' => 'vr-interactive'
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
     * Create 3D model from artwork
     *
     * @param int $artwork_id The ID of the artwork to convert
     * @param string $model_type Type of 3D model (sculpture, scene, character, environment)
     * @param string $detail_level Detail level (standard, high, ultra)
     * @param string $file_format Output file format (glb, obj, usdz, fbx)
     * @param int $user_id User ID
     * @param bool $private Keep model private
     * @return array|WP_Error Result data or error
     */
    public function create_3d_model($artwork_id, $model_type, $detail_level, $file_format, $user_id, $private = true) {
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
        
        // Validate model type
        if (!isset($this->model_types[$model_type])) {
            return new WP_Error('invalid_model_type', __('Invalid 3D model type', 'vortex-ai-marketplace'));
        }
        
        // Validate detail level
        $valid_detail_levels = array('standard', 'high', 'ultra');
        if (!in_array($detail_level, $valid_detail_levels)) {
            return new WP_Error('invalid_detail_level', __('Invalid detail level', 'vortex-ai-marketplace'));
        }
        
        // Validate file format
        $valid_formats = array('glb', 'obj', 'usdz', 'fbx');
        if (!in_array($file_format, $valid_formats)) {
            return new WP_Error('invalid_file_format', __('Invalid file format', 'vortex-ai-marketplace'));
        }
        
        // Calculate token cost
        $model_config = $this->model_types[$model_type];
        $tola_cost = $model_config['tola_cost'][$detail_level];
        
        // Check if user has enough tokens
        $wallet = Vortex_AI_Marketplace::get_instance()->wallet;
        if (!$wallet->check_tola_balance($user_id, $tola_cost)) {
            return new WP_Error(
                'insufficient_tokens',
                sprintf(
                    __('You need %d TOLA tokens for this 3D model. Current balance: %d', 'vortex-ai-marketplace'),
                    $tola_cost,
                    $wallet->get_tola_balance($user_id)
                )
            );
        }
        
        // Process the 3D model generation
        $start_time = microtime(true);
        $result = $this->process_3d_model($artwork, $model_type, $detail_level, $file_format);
        $processing_time = round(microtime(true) - $start_time);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Deduct tokens
        $wallet->deduct_tola_tokens($user_id, $tola_cost, '3d_model_creation');
        
        // Save 3D model to database
        $model_title = sprintf(__('%s (3D %s)', 'vortex-ai-marketplace'), 
            $artwork->title, 
            $model_config['name']
        );
        
        $model_id = $this->save_3d_model(
            $user_id,
            $artwork_id,
            $model_title,
            sprintf(__('3D %s created from "%s" with %s detail level', 'vortex-ai-marketplace'), 
                $model_config['name'],
                $artwork->title,
                ucfirst($detail_level)
            ),
            $model_type,
            $detail_level,
            $file_format,
            $result['model_path'],
            $result['thumbnail_path'],
            $processing_time,
            $tola_cost,
            $private
        );
        
        // Return result
        return array(
            'id' => $model_id,
            'title' => $model_title,
            'model_path' => $result['model_path'],
            'thumbnail_path' => $result['thumbnail_path'],
            'model_type' => $model_config['name'],
            'detail_level' => ucfirst($detail_level),
            'file_format' => strtoupper($file_format),
            'processing_time' => $processing_time,
            'tola_cost' => $tola_cost,
            'ar_link' => $this->generate_ar_link($result['model_path'], $file_format)
        );
    }
    
    /**
     * Create VR environment from artwork
     *
     * @param int $artwork_id The ID of the artwork
     * @param string $environment_type Type of VR environment
     * @param string $complexity Complexity level (standard, complex, expansive)
     * @param string $platform Target platform (oculus, steamvr, webxr)
     * @param bool $multi_user Enable multi-user experience
     * @param int $user_id User ID
     * @param bool $private Keep environment private
     * @return array|WP_Error Result data or error
     */
    public function create_vr_environment($artwork_id, $environment_type, $complexity, $platform, 
                                         $multi_user, $user_id, $private = true) {
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
        
        // Validate environment type
        if (!isset($this->vr_environment_types[$environment_type])) {
            return new WP_Error('invalid_environment_type', __('Invalid VR environment type', 'vortex-ai-marketplace'));
        }
        
        // Validate complexity
        $valid_complexities = array('standard', 'complex', 'expansive');
        if (!in_array($complexity, $valid_complexities)) {
            return new WP_Error('invalid_complexity', __('Invalid complexity level', 'vortex-ai-marketplace'));
        }
        
        // Validate platform
        $valid_platforms = array('oculus', 'steamvr', 'webxr');
        if (!in_array($platform, $valid_platforms)) {
            return new WP_Error('invalid_platform', __('Invalid target platform', 'vortex-ai-marketplace'));
        }
        
        // Calculate token cost
        $environment_config = $this->vr_environment_types[$environment_type];
        $tola_cost = $environment_config['tola_cost'][$complexity];
        
        // Add cost for multi-user if enabled
        if ($multi_user) {
            $tola_cost += 20; // Additional cost for multi-user capability
        }
        
        // Check if user has enough tokens
        $wallet = Vortex_AI_Marketplace::get_instance()->wallet;
        if (!$wallet->check_tola_balance($user_id, $tola_cost)) {
            return new WP_Error(
                'insufficient_tokens',
                sprintf(
                    __('You need %d TOLA tokens for this VR environment. Current balance: %d', 'vortex-ai-marketplace'),
                    $tola_cost,
                    $wallet->get_tola_balance($user_id)
                )
            );
        }
        
        // Process the VR environment generation
        $start_time = microtime(true);
        $result = $this->process_vr_environment($artwork, $environment_type, $complexity, $platform, $multi_user);
        $processing_time = round(microtime(true) - $start_time);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Deduct tokens
        $wallet->deduct_tola_tokens($user_id, $tola_cost, 'vr_environment_creation');
        
        // Save VR environment to database
        $environment_title = sprintf(__('%s (VR %s)', 'vortex-ai-marketplace'), 
            $artwork->title, 
            $environment_config['name']
        );
        
        $environment_id = $this->save_vr_environment(
            $user_id,
            $artwork_id,
            $environment_title,
            sprintf(__('VR %s created from "%s" with %s complexity', 'vortex-ai-marketplace'), 
                $environment_config['name'],
                $artwork->title,
                ucfirst($complexity)
            ),
            $environment_type,
            $complexity,
            $platform,
            $multi_user,
            $result['environment_path'],
            $result['thumbnail_path'],
            $result['preview_path'],
            $processing_time,
            $tola_cost,
            $private
        );
        
        // Return result
        return array(
            'id' => $environment_id,
            'title' => $environment_title,
            'environment_path' => $result['environment_path'],
            'thumbnail_path' => $result['thumbnail_path'],
            'preview_path' => $result['preview_path'],
            'environment_type' => $environment_config['name'],
            'complexity' => ucfirst($complexity),
            'platform' => $this->get_platform_name($platform),
            'multi_user' => $multi_user,
            'processing_time' => $processing_time,
            'tola_cost' => $tola_cost,
            'vr_link' => $this->generate_vr_link($result['environment_path'], $platform)
        );
    }
    
    /**
     * Process 3D model generation
     */
    private function process_3d_model($artwork, $model_type, $detail_level, $file_format) {
        // Get the original artwork file
        $artwork_file = $artwork->file_path;
        
        // If it's a URL, download it temporarily
        if (filter_var($artwork_file, FILTER_VALIDATE_URL)) {
            $temp_file = download_url($artwork_file);
            if (is_wp_error($temp_file)) {
                return $temp_file;
            }
            $artwork_file = $temp_file;
        }
        
        // Prepare upload directory
        $upload_dir = wp_upload_dir();
        $model_base_name = uniqid('3d_model_');
        $model_path = $upload_dir['path'] . '/' . $model_base_name . '.' . $file_format;
        $thumbnail_path = $upload_dir['path'] . '/' . $model_base_name . '_thumbnail.jpg';
        
        // Call to external API or local processing
        // This is a placeholder for the actual 3D model generation
        $api_model = $this->model_types[$model_type]['api_model'];
        $success = $this->call_3d_model_api($artwork_file, $model_path, $thumbnail_path, $api_model, $detail_level, $file_format);
        
        // Clean up temp file if needed
        if (isset($temp_file) && file_exists($temp_file)) {
            @unlink($temp_file);
        }
        
        if (!$success) {
            return new WP_Error('model_generation_failed', __('Failed to generate 3D model', 'vortex-ai-marketplace'));
        }
        
        // Return paths to the generated files
        return array(
            'model_path' => $upload_dir['url'] . '/' . basename($model_path),
            'thumbnail_path' => $upload_dir['url'] . '/' . basename($thumbnail_path)
        );
    }
    
    /**
     * Process VR environment generation
     */
    private function process_vr_environment($artwork, $environment_type, $complexity, $platform, $multi_user) {
        // Get the original artwork file
        $artwork_file = $artwork->file_path;
        
        // If it's a URL, download it temporarily
        if (filter_var($artwork_file, FILTER_VALIDATE_URL)) {
            $temp_file = download_url($artwork_file);
            if (is_wp_error($temp_file)) {
                return $temp_file;
            }
            $artwork_file = $temp_file;
        }
        
        // Prepare upload directory
        $upload_dir = wp_upload_dir();
        $environment_base_name = uniqid('vr_env_');
        $environment_path = $upload_dir['path'] . '/' . $environment_base_name . '.zip';
        $thumbnail_path = $upload_dir['path'] . '/' . $environment_base_name . '_thumbnail.jpg';
        $preview_path = $upload_dir['path'] . '/' . $environment_base_name . '_preview.mp4';
        
        // Call to external API or local processing
        // This is a placeholder for the actual VR environment generation
        $api_model = $this->vr_environment_types[$environment_type]['api_model'];
        $success = $this->call_vr_environment_api(
            $artwork_file, 
            $environment_path, 
            $thumbnail_path, 
            $preview_path,
            $api_model, 
            $complexity, 
            $platform,
            $multi_user
        );
        
        // Clean up temp file if needed
        if (isset($temp_file) && file_exists($temp_file)) {
            @unlink($temp_file);
        }
        
        if (!$success) {
            return new WP_Error('environment_generation_failed', __('Failed to generate VR environment', 'vortex-ai-marketplace'));
        }
        
        // Return paths to the generated files
        return array(
            'environment_path' => $upload_dir['url'] . '/' . basename($environment_path),
            'thumbnail_path' => $upload_dir['url'] . '/' . basename($thumbnail_path),
            'preview_path' => $upload_dir['url'] . '/' . basename($preview_path)
        );
    }
    
    /**
     * Call to 3D model generation API
     */
    private function call_3d_model_api($input_file, $output_file, $thumbnail_file, $model, $detail_level, $file_format) {
        // This is a placeholder for actual API integration
        // In a real implementation, you would:
        // 1. Call a third-party 3D model generation API like Luma AI, Nvidia GET3D, etc.
        // 2. Or use a local implementation with libraries like PyTorch + Point-E
        
        // For demonstration purposes, simulate successful model generation
        // Create a simple thumbnail representation
        $image = imagecreatefromstring(file_get_contents($input_file));
        if ($image !== false) {
            // Save as thumbnail
            imagejpeg($image, $thumbnail_file, 90);
            imagedestroy($image);
            
            // For demo, create placeholder model files
            file_put_contents($output_file, 'PLACEHOLDER 3D MODEL CONTENT');
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Call to VR environment generation API
     */
    private function call_vr_environment_api($input_file, $output_file, $thumbnail_file, $preview_file, 
                                            $model, $complexity, $platform, $multi_user) {
        // This is a placeholder for actual API integration
        // In a real implementation, you would call a third-party VR environment generation API
        
        // For demonstration purposes, simulate successful environment generation
        // Create a simple thumbnail and preview
        $image = imagecreatefromstring(file_get_contents($input_file));
        if ($image !== false) {
            // Save as thumbnail
            imagejpeg($image, $thumbnail_file, 90);
            imagedestroy($image);
            
            // For demo, create placeholder files
            file_put_contents($output_file, 'PLACEHOLDER VR ENVIRONMENT CONTENT');
            file_put_contents($preview_file, 'PLACEHOLDER VR PREVIEW VIDEO CONTENT');
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Save 3D model to database
     */
    private function save_3d_model($user_id, $artwork_id, $title, $description, $model_type, $detail_level, 
                                  $file_format, $file_path, $thumbnail_path, $processing_time, $tola_cost, $private) {
        global $wpdb;
        
        $models_table = $wpdb->prefix . 'vortex_3d_models';
        
        $wpdb->insert(
            $models_table,
            array(
                'user_id' => $user_id,
                'artwork_id' => $artwork_id,
                'title' => $title,
                'description' => $description,
                'model_type' => $model_type,
                'detail_level' => $detail_level,
                'file_format' => $file_format,
                'file_path' => $file_path,
                'thumbnail_path' => $thumbnail_path,
                'creation_date' => current_time('mysql'),
                'processing_time' => $processing_time,
                'tola_cost' => $tola_cost,
                'private' => $private ? 1 : 0
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d')
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Save VR environment to database
     */
    private function save_vr_environment($user_id, $artwork_id, $title, $description, $environment_type, 
                                        $complexity, $platform, $multi_user, $environment_path, $thumbnail_path, 
                                        $preview_path, $processing_time, $tola_cost, $private) {
        global $wpdb;
        
        $immersive_table = $wpdb->prefix . 'vortex_immersive_experiences';
        
        $wpdb->insert(
            $immersive_table,
            array(
                'user_id' => $user_id,
                'artwork_id' => $artwork_id,
                'title' => $title,
                'description' => $description,
                'experience_type' => 'vr',
                'subtype' => $environment_type,
                'complexity' => $complexity,
                'platform' => $platform,
                'multi_user' => $multi_user ? 1 : 0,
                'file_path' => $environment_path,
                'thumbnail_path' => $thumbnail_path,
                'preview_path' => $preview_path,
                'creation_date' => current_time('mysql'),
                'processing_time' => $processing_time,
                'tola_cost' => $tola_cost,
                'private' => $private ? 1 : 0
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%d')
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Generate AR link for 3D model
     */
    private function generate_ar_link($model_path, $file_format) {
        // Generate a link that can be used to view the model in AR
        // For example, on iOS, USDZ files can be viewed directly in AR
        // For web AR, you might use a service like ModelViewer
        
        // This is a simplified example
        $base_url = site_url('/ar-viewer/');
        return add_query_arg(array(
            'model' => urlencode($model_path),
            'format' => $file_format
        ), $base_url);
    }
    
    /**
     * Generate VR link for environment
     */
    private function generate_vr_link($environment_path, $platform) {
        // Generate a link that can be used to access the VR environment
        // This would depend on the platform (Oculus, SteamVR, WebXR)
        
        // This is a simplified example
        $base_url = site_url('/vr-launcher/');
        return add_query_arg(array(
            'env' => urlencode($environment_path),
            'platform' => $platform
        ), $base_url);
    }
    
    /**
     * Get platform display name
     */
    private function get_platform_name($platform) {
        $platform_names = array(
            'oculus' => 'Meta Quest',
            'steamvr' => 'SteamVR',
            'webxr' => 'WebXR (Browser)'
        );
        
        return isset($platform_names[$platform]) ? $platform_names[$platform] : ucfirst($platform);
    }
    
    /**
     * Get all available 3D model types
     */
    public function get_model_types() {
        return $this->model_types;
    }
    
    /**
     * Get all available VR environment types
     */
    public function get_vr_environment_types() {
        return $this->vr_environment_types;
    }
} 