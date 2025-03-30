<?php
/**
 * HURAII AJAX Handlers
 * 
 * Processes AJAX requests for HURAII-generated images
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/ajax
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * The class that handles HURAII AJAX requests
 */
class Vortex_HURAII_AJAX {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Register AJAX actions
        add_action('wp_ajax_vortex_save_huraii_image', array($this, 'save_huraii_image'));
        add_action('wp_ajax_vortex_create_nft_from_image', array($this, 'create_nft_from_image'));
        add_action('wp_ajax_vortex_get_user_huraii_library', array($this, 'get_user_huraii_library'));
    }
    
    /**
     * Save a HURAII-generated image
     */
    public function save_huraii_image() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_huraii_nonce')) {
            wp_send_json_error(array(
                'message' => __('Security check failed', 'vortex-ai-marketplace'),
                'code' => 'security_error'
            ));
            return;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to save images', 'vortex-ai-marketplace'),
                'code' => 'auth_error'
            ));
            return;
        }
        
        // Get current user ID
        $user_id = get_current_user_id();
        
        // Check if image URL is provided
        if (empty($_POST['imageURL'])) {
            wp_send_json_error(array(
                'message' => __('No image URL provided', 'vortex-ai-marketplace'),
                'code' => 'input_error'
            ));
            return;
        }
        
        $image_url = esc_url_raw($_POST['imageURL']);
        
        // Validate image URL (must be from our domain for security)
        $site_url = parse_url(site_url(), PHP_URL_HOST);
        $image_host = parse_url($image_url, PHP_URL_HOST);
        
        if ($image_host !== $site_url && !wp_http_validate_url($image_url)) {
            wp_send_json_error(array(
                'message' => __('Invalid image URL', 'vortex-ai-marketplace'),
                'code' => 'security_error'
            ));
            return;
        }
        
        // Prepare metadata
        $metadata = array(
            'prompt'    => isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : '',
            'mode'      => isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : '',
            'style'     => isset($_POST['style']) ? sanitize_text_field($_POST['style']) : '',
            'technique' => isset($_POST['technique']) ? sanitize_text_field($_POST['technique']) : '',
            'settings'  => isset($_POST['settings']) ? $this->sanitize_settings($_POST['settings']) : array(),
            'source_url' => isset($_POST['sourceURL']) ? esc_url_raw($_POST['sourceURL']) : '',
        );
        
        try {
            // Load HURAII Library
            require_once dirname(dirname(__FILE__)) . '/class-vortex-huraii-library.php';
            $library = Vortex_HURAII_Library::get_instance();
            
            // Save the image
            $result = $library->save_image($image_url, $metadata, $user_id);
            
            if (is_wp_error($result)) {
                wp_send_json_error(array(
                    'message' => $result->get_error_message(),
                    'code' => $result->get_error_code()
                ));
                return;
            }
            
            // Track AI learning
            do_action('vortex_ai_agent_learn', 'save_image', array(
                'agent' => 'HURAII',
                'user_id' => $user_id,
                'image_id' => $result,
                'metadata' => $metadata,
                'timestamp' => current_time('timestamp')
            ));
            
            // Update user transformation history
            $this->update_user_transformation_history($user_id, array(
                'id' => $result,
                'thumbnail_url' => get_the_post_thumbnail_url($result, 'thumbnail'),
                'url' => get_permalink($result),
                'description' => $metadata['prompt'],
                'mode' => $metadata['mode'],
                'date' => current_time('mysql')
            ));
            
            // Get the image data to return
            $image_data = array(
                'id' => $result,
                'title' => get_the_title($result),
                'url' => get_permalink($result),
                'image_url' => get_the_post_thumbnail_url($result, 'full'),
                'thumbnail' => get_the_post_thumbnail_url($result, 'thumbnail')
            );
            
            wp_send_json_success(array(
                'message' => __('Image saved successfully', 'vortex-ai-marketplace'),
                'image' => $image_data
            ));
            
        } catch (Exception $e) {
            error_log('VORTEX HURAII Save Error: ' . $e->getMessage());
            
            wp_send_json_error(array(
                'message' => __('An error occurred while saving the image', 'vortex-ai-marketplace'),
                'details' => $e->getMessage(),
                'code' => 'server_error'
            ));
        }
    }
    
    /**
     * Create NFT from a saved image
     */
    public function create_nft_from_image() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_huraii_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'vortex-ai-marketplace')));
            return;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to create NFTs', 'vortex-ai-marketplace')));
            return;
        }
        
        // Get current user ID
        $user_id = get_current_user_id();
        
        // Check if image ID is provided
        if (empty($_POST['imageId'])) {
            wp_send_json_error(array('message' => __('No image ID provided', 'vortex-ai-marketplace')));
            return;
        }
        
        $image_id = intval($_POST['imageId']);
        
        // Prepare NFT metadata
        $nft_metadata = array(
            'name'        => isset($_POST['nftName']) ? sanitize_text_field($_POST['nftName']) : get_the_title($image_id),
            'description' => isset($_POST['nftDescription']) ? sanitize_textarea_field($_POST['nftDescription']) : '',
            'royalty'     => isset($_POST['royaltyPercentage']) ? floatval($_POST['royaltyPercentage']) : 10.0,
            'price'       => isset($_POST['price']) ? floatval($_POST['price']) : 0,
            'quantity'    => isset($_POST['quantity']) ? intval($_POST['quantity']) : 1,
        );
        
        // Load HURAII Library
        require_once dirname(dirname(__FILE__)) . '/class-vortex-huraii-library.php';
        $library = Vortex_HURAII_Library::get_instance();
        
        // Create NFT
        $result = $library->create_nft_from_image($image_id, $nft_metadata, $user_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        // Get the NFT data to return
        $nft_data = array(
            'id' => $result,
            'title' => get_the_title($result),
            'url' => get_permalink($result),
            'image_url' => get_the_post_thumbnail_url($result, 'large'),
            'blockchain_status' => 'pending', // In a real implementation, this would be more complex
        );
        
        wp_send_json_success(array(
            'message' => __('NFT creation process started', 'vortex-ai-marketplace'),
            'nft' => $nft_data
        ));
    }
    
    /**
     * Get user's HURAII image library
     */
    public function get_user_huraii_library() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_huraii_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'vortex-ai-marketplace')));
            return;
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('You must be logged in to view your library', 'vortex-ai-marketplace')));
            return;
        }
        
        // Get current user ID
        $user_id = get_current_user_id();
        
        // Get page and limit parameters
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
        
        // Load HURAII Library
        require_once dirname(dirname(__FILE__)) . '/class-vortex-huraii-library.php';
        $library = Vortex_HURAII_Library::get_instance();
        
        // Get user library
        $result = $library->get_user_library($user_id, $limit, $page);
        
        wp_send_json_success($result);
    }
    
    /**
     * Update user transformation history
     *
     * @param int $user_id User ID
     * @param array $new_item New history item
     */
    private function update_user_transformation_history($user_id, $new_item) {
        $history = get_user_meta($user_id, 'vortex_img2img_history', true);
        
        if (!is_array($history)) {
            $history = array();
        }
        
        // Add new item at the beginning
        array_unshift($history, $new_item);
        
        // Limit to 20 items
        if (count($history) > 20) {
            $history = array_slice($history, 0, 20);
        }
        
        update_user_meta($user_id, 'vortex_img2img_history', $history);
    }
    
    /**
     * Sanitize settings array
     *
     * @param array $settings Settings to sanitize
     * @return array Sanitized settings
     */
    private function sanitize_settings($settings) {
        if (!is_array($settings)) {
            return array();
        }
        
        $sanitized = array();
        
        foreach ($settings as $key => $value) {
            $sanitized_key = sanitize_text_field($key);
            
            if (is_array($value)) {
                $sanitized[$sanitized_key] = $this->sanitize_settings($value);
            } elseif (is_numeric($value)) {
                $sanitized[$sanitized_key] = floatval($value);
            } else {
                $sanitized[$sanitized_key] = sanitize_text_field($value);
            }
        }
        
        return $sanitized;
    }
}

// Initialize the AJAX handler
new Vortex_HURAII_AJAX(); 