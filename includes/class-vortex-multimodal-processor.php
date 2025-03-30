<?php
/**
 * Multi-Modal Processor
 * 
 * Enables AI agents to process different types of input data (text, images, structured data)
 * and combine insights across modalities for more comprehensive understanding.
 */
class Vortex_Multimodal_Processor {
    
    /**
     * Initialize multimodal processing capabilities
     */
    public function __construct() {
        // Register multimodal processing hooks
        add_filter('vortex_llm_request_params', array($this, 'enrich_request_with_multimodal'), 10, 3);
        add_filter('vortex_ai_output_processing', array($this, 'process_multimodal_output'), 10, 3);
        
        // Handle multimodal file uploads
        add_action('wp_ajax_vortex_upload_multimodal', array($this, 'handle_multimodal_upload'));
        
        // Enhance existing agent capabilities with multimodal processing
        add_filter('vortex_huraii_params', array($this, 'enhance_huraii_with_multimodal'), 10, 2);
        add_filter('vortex_cloe_params', array($this, 'enhance_cloe_with_multimodal'), 10, 2);
        add_filter('vortex_strategist_params', array($this, 'enhance_strategist_with_multimodal'), 10, 2);
    }
    
    /**
     * Enrich AI request with multimodal data
     * 
     * @param array $params Request parameters
     * @param string $agent Agent name
     * @param string $task Task name
     * @return array Enhanced parameters
     */
    public function enrich_request_with_multimodal($params, $agent, $task) {
        // Check if multimodal data is available
        if (!isset($params['multimodal_data']) || empty($params['multimodal_data'])) {
            return $params;
        }
        
        // Process based on agent type
        switch ($agent) {
            case 'HURAII':
                return $this->process_huraii_multimodal($params);
                
            case 'CLOE':
                return $this->process_cloe_multimodal($params);
                
            case 'STRATEGIST':
                return $this->process_strategist_multimodal($params);
                
            default:
                return $params;
        }
    }
    
    /**
     * Process multimodal data for HURAII
     * 
     * @param array $params Request parameters
     * @return array Enhanced parameters
     */
    private function process_huraii_multimodal($params) {
        // Extract image data if present
        if (isset($params['multimodal_data']['image'])) {
            $image_data = $params['multimodal_data']['image'];
            
            // Generate image description
            $description = $this->generate_image_description($image_data);
            
            // Augment prompt with image context
            $params['prompt'] = $this->augment_prompt_with_image_context(
                $params['prompt'],
                $description
            );
            
            // Add image data in format expected by HURAII
            $params['image_data'] = $image_data;
        }
        
        return $params;
    }
    
    /**
     * Process multimodal data for CLOE
     * 
     * @param array $params Request parameters
     * @return array Enhanced parameters
     */
    private function process_cloe_multimodal($params) {
        // Extract chart/graph data if present
        if (isset($params['multimodal_data']['chart'])) {
            $chart_data = $params['multimodal_data']['chart'];
            
            // Analyze chart data
            $chart_analysis = $this->analyze_chart_data($chart_data);
            
            // Enhance market analysis with chart insights
            $params['market_context'] = array_merge(
                $params['market_context'] ?? array(),
                $chart_analysis
            );
        }
        
        // Extract document data if present
        if (isset($params['multimodal_data']['document'])) {
            $document_data = $params['multimodal_data']['document'];
            
            // Extract document insights
            $document_insights = $this->extract_document_insights($document_data);
            
            // Add document insights to context
            $params['document_insights'] = $document_insights;
        }
        
        return $params;
    }
    
    /**
     * Process multimodal data for Business Strategist
     * 
     * @param array $params Request parameters
     * @return array Enhanced parameters
     */
    private function process_strategist_multimodal($params) {
        // Extract business data if present
        if (isset($params['multimodal_data']['business_data'])) {
            $business_data = $params['multimodal_data']['business_data'];
            
            // Analyze business data
            $business_analysis = $this->analyze_business_data($business_data);
            
            // Enhance business context
            $params['business_context'] = array_merge(
                $params['business_context'] ?? array(),
                $business_analysis
            );
        }
        
        return $params;
    }
    
    /**
     * Generate description of an image
     * 
     * @param string $image_data Base64 encoded image or URL
     * @return array Image description
     */
    private function generate_image_description($image_data) {
        // In a production environment, this would use a vision model API
        // For now, we'll return a placeholder
        return array(
            'objects' => array('person', 'building', 'car'),
            'scene_type' => 'urban',
            'color_palette' => array('gray', 'blue', 'green'),
            'composition' => 'balanced',
            'style' => 'photographic'
        );
    }
    
    /**
     * Augment prompt with image context
     * 
     * @param string $prompt Original prompt
     * @param array $image_context Image context data
     * @return string Enhanced prompt
     */
    private function augment_prompt_with_image_context($prompt, $image_context) {
        // Create context description
        $context = "Image contains: " . implode(', ', $image_context['objects']);
        $context .= ". Scene type: " . $image_context['scene_type'];
        $context .= ". Dominant colors: " . implode(', ', $image_context['color_palette']);
        
        // Append to prompt
        return $prompt . " Context from image: " . $context;
    }
    
    /**
     * Analyze chart data
     * 
     * @param array $chart_data Chart data
     * @return array Chart analysis
     */
    private function analyze_chart_data($chart_data) {
        // In a production environment, this would use chart parsing algorithms
        // For now, return placeholder analysis
        return array(
            'trend' => 'upward',
            'volatility' => 'medium',
            'key_points' => array(
                array('x' => '2023-01', 'y' => 120, 'event' => 'Market peak'),
                array('x' => '2023-06', 'y' => 80, 'event' => 'Market correction')
            ),
            'pattern' => 'cyclical'
        );
    }
    
    /**
     * Handle multimodal file upload
     */
    public function handle_multimodal_upload() {
        // Check nonce for security
        check_ajax_referer('vortex_multimodal_upload', 'nonce');
        
        // Check user permissions
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'User not logged in'));
            return;
        }
        
        // Check file
        if (empty($_FILES['file'])) {
            wp_send_json_error(array('message' => 'No file uploaded'));
            return;
        }
        
        // Get uploaded file
        $file = $_FILES['file'];
        
        // Validate file type
        $allowed_types = array('image/jpeg', 'image/png', 'application/pdf', 'text/csv');
        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error(array('message' => 'File type not allowed'));
            return;
        }
        
        // Handle file based on type
        $file_type = $this->get_multimodal_file_type($file['type']);
        $result = $this->process_multimodal_file($file, $file_type);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
            return;
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Get multimodal file type
     * 
     * @param string $mime_type MIME type
     * @return string Simplified file type
     */
    private function get_multimodal_file_type($mime_type) {
        if (strpos($mime_type, 'image/') === 0) {
            return 'image';
        } elseif ($mime_type === 'application/pdf') {
            return 'document';
        } elseif ($mime_type === 'text/csv') {
            return 'data';
        }
        
        return 'unknown';
    }
    
    /**
     * Process multimodal file
     * 
     * @param array $file Uploaded file data
     * @param string $file_type File type
     * @return array|WP_Error Processed data or error
     */
    private function process_multimodal_file($file, $file_type) {
        // Define upload directory
        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . '/vortex-multimodal/';
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            wp_mkdir_p($target_dir);
        }
        
        // Generate unique filename
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = md5(time() . rand(0, 9999)) . '.' . $file_extension;
        $target_file = $target_dir . $filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $target_file)) {
            return new WP_Error('upload_failed', 'Failed to upload file');
        }
        
        // Get URL for the uploaded file
        $file_url = $upload_dir['baseurl'] . '/vortex-multimodal/' . $filename;
        
        return array(
            'file_type' => $file_type,
            'file_url' => $file_url,
            'file_path' => $target_file
        );
    }
} 