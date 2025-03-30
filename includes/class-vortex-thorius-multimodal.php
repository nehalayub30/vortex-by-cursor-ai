<?php
/**
 * Thorius Multimodal Input Processor
 * 
 * Handles text, voice, and image input for AI processing
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Thorius Multimodal Input Processor
 */
class Vortex_Thorius_Multimodal {
    /**
     * API Manager instance
     */
    private $api_manager;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->api_manager = new Vortex_Thorius_API_Manager();
        
        // Register multimodal shortcode
        add_shortcode('thorius_multimodal', array($this, 'multimodal_shortcode'));
        
        // Register AJAX handlers
        add_action('wp_ajax_vortex_thorius_voice_input', array($this, 'ajax_process_voice_input'));
        add_action('wp_ajax_nopriv_vortex_thorius_voice_input', array($this, 'ajax_process_voice_input'));
        add_action('wp_ajax_vortex_thorius_image_input', array($this, 'ajax_process_image_input'));
        add_action('wp_ajax_nopriv_vortex_thorius_image_input', array($this, 'ajax_process_image_input'));
    }
    
    /**
     * AJAX handler for voice input processing
     */
    public function ajax_process_voice_input() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_thorius_voice_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'vortex-ai-marketplace')));
            exit;
        }
        
        // Check for audio data
        if (!isset($_FILES['audio']) || !file_exists($_FILES['audio']['tmp_name'])) {
            wp_send_json_error(array('message' => __('No audio data received.', 'vortex-ai-marketplace')));
            exit;
        }
        
        try {
            // Process audio file to text
            $audio_file = $_FILES['audio']['tmp_name'];
            $transcript = $this->transcribe_audio($audio_file);
            
            if (empty($transcript)) {
                wp_send_json_error(array('message' => __('Could not transcribe audio.', 'vortex-ai-marketplace')));
                exit;
            }
            
            // Track in analytics
            $analytics = new Vortex_Thorius_Analytics();
            $analytics->track_event('voice_input', array(
                'length' => $_FILES['audio']['size'],
                'transcription_length' => strlen($transcript)
            ));
            
            wp_send_json_success(array(
                'transcript' => $transcript
            ));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
        
        exit;
    }
    
    /**
     * AJAX handler for image input processing
     */
    public function ajax_process_image_input() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_thorius_image_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'vortex-ai-marketplace')));
            exit;
        }
        
        // Check for image data
        if (!isset($_FILES['image']) || !file_exists($_FILES['image']['tmp_name'])) {
            wp_send_json_error(array('message' => __('No image data received.', 'vortex-ai-marketplace')));
            exit;
        }
        
        try {
            // Process image
            $image_file = $_FILES['image']['tmp_name'];
            $description = $this->describe_image($image_file);
            
            if (empty($description)) {
                wp_send_json_error(array('message' => __('Could not analyze image.', 'vortex-ai-marketplace')));
                exit;
            }
            
            // Track in analytics
            $analytics = new Vortex_Thorius_Analytics();
            $analytics->track_event('image_input', array(
                'size' => $_FILES['image']['size'],
                'description_length' => strlen($description)
            ));
            
            wp_send_json_success(array(
                'description' => $description
            ));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
        
        exit;
    }
    
    /**
     * Transcribe audio to text
     * 
     * @param string $audio_file Path to audio file
     * @return string Transcribed text
     */
    private function transcribe_audio($audio_file) {
        // Get audio data
        $audio_data = file_get_contents($audio_file);
        $audio_base64 = base64_encode($audio_data);
        
        // API request to OpenAI Whisper
        $response = $this->api_manager->request('openai', 'audio/transcriptions', [
            'model' => 'whisper-1',
            'file' => $audio_base64,
            'response_format' => 'text'
        ]);
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        return $response['text'] ?? '';
    }
    
    /**
     * Describe image content
     * 
     * @param string $image_file Path to image file
     * @return string Image description
     */
    private function describe_image($image_file) {
        // Process image with Vision API
        $response = $this->api_manager->request('openai', 'chat/completions', [
            'model' => 'gpt-4-vision-preview',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [/* image content */]
                ]
            ]
        ]);
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        return $response['choices'][0]['message']['content'] ?? '';
    }
    
    /**
     * Thorius Multimodal shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string Rendered shortcode
     */
    public function multimodal_shortcode($atts) {
        // Extract attributes
        $atts = shortcode_atts(array(
            'theme' => 'light',
            'welcome_message' => __('Upload images, speak, or type - I can understand them all!', 'vortex-ai-marketplace'),
            'placeholder' => __('Type or upload media...', 'vortex-ai-marketplace'),
            'height' => '500px',
            'agent' => 'auto' // auto, cloe, huraii, strategist
        ), $atts);
        
        // Generate unique ID
        $modal_id = 'thorius-multimodal-' . uniqid();
        
        // Enqueue necessary scripts and styles
        wp_enqueue_style('thorius-multimodal-css');
        wp_enqueue_script('thorius-multimodal-js');
        wp_enqueue_script('thorius-voice-js');
        
        // Start output buffer
        ob_start();
        
        // Get input tabs
        $input_tabs = $this->register_input_tabs();
        $current_tab = isset($_GET['input_tab']) ? sanitize_key($_GET['input_tab']) : 'text';
        
        echo '<div id="' . esc_attr($modal_id) . '" class="thorius-multimodal-container" ';
        echo 'data-theme="' . esc_attr($atts['theme']) . '" ';
        echo 'data-agent="' . esc_attr($atts['agent']) . '" ';
        echo 'style="height: ' . esc_attr($atts['height']) . ';">';
        
        echo '<div class="thorius-multimodal-header">';
        echo '<div class="thorius-multimodal-title">Thorius AI</div>';
        echo '</div>';
        
        echo '<div class="thorius-multimodal-content">';
        
        // Welcome message
        echo '<div class="thorius-multimodal-welcome">';
        echo '<div class="thorius-avatar"></div>';
        echo '<div class="thorius-message-content">' . esc_html($atts['welcome_message']) . '</div>';
        echo '</div>';
        
        // Input tabs
        echo '<div class="thorius-input-tabs" data-persistent="true">';
        echo '<nav class="thorius-tab-nav">';
        
        foreach ($input_tabs as $tab_id => $tab) {
            $active_class = ($current_tab === $tab_id) ? 'active' : '';
            echo '<button class="thorius-tab-button ' . esc_attr($active_class) . '" data-tab="' . esc_attr($tab_id) . '">';
            echo '<span class="' . esc_attr($tab['icon']) . '"></span> ' . esc_html($tab['title']);
            echo '</button>';
        }
        
        echo '</nav>';
        
        // Tab content
        echo '<div class="thorius-tab-content-wrapper">';
        
        foreach ($input_tabs as $tab_id => $tab) {
            $display = ($current_tab === $tab_id) ? 'block' : 'none';
            echo '<div id="' . esc_attr($tab_id) . '-content" class="thorius-tab-content" style="display: ' . esc_attr($display) . ';">';
            
            // Include tab template
            include plugin_dir_path(dirname(__FILE__)) . 'templates/' . $tab['template'];
            
            echo '</div>';
        }
        
        echo '</div>'; // End tab content wrapper
        echo '</div>'; // End input tabs
        
        // Conversation area
        echo '<div class="thorius-multimodal-conversation">';
        echo '<div class="thorius-conversation-messages"></div>';
        echo '</div>';
        
        echo '</div>'; // End multimodal content
        echo '</div>'; // End multimodal container
        
        // Add initialization script
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                if (typeof ThoriusMultimodal !== "undefined") {
                    new ThoriusMultimodal("' . $modal_id . '");
                }
            });
        </script>';
        
        return ob_get_clean();
    }

    /**
     * Register multimodal input tabs
     */
    private function register_input_tabs() {
        return array(
            'text' => array(
                'title' => __('Text', 'vortex-ai-marketplace'),
                'icon' => 'dashicons-text',
                'template' => 'text-input.php'
            ),
            'voice' => array(
                'title' => __('Voice', 'vortex-ai-marketplace'),
                'icon' => 'dashicons-microphone',
                'template' => 'voice-input.php'
            ),
            'image' => array(
                'title' => __('Image', 'vortex-ai-marketplace'),
                'icon' => 'dashicons-format-image',
                'template' => 'image-input.php'
            )
        );
    }

    /**
     * Render input tabs
     */
    private function render_input_tabs() {
        $tabs = $this->register_input_tabs();
        $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'text';
        
        echo '<div class="thorius-input-tabs">';
        echo '<nav class="thorius-tab-nav">';
        
        foreach ($tabs as $tab_id => $tab) {
            $active_class = ($current_tab === $tab_id) ? 'active' : '';
            printf(
                '<button class="thorius-tab-button %s" data-tab="%s"><span class="%s"></span> %s</button>',
                esc_attr($active_class),
                esc_attr($tab_id),
                esc_attr($tab['icon']),
                esc_html($tab['title'])
            );
        }
        
        echo '</nav>';
        
        echo '<div class="thorius-tab-content">';
        if (isset($tabs[$current_tab])) {
            include plugin_dir_path(dirname(__FILE__)) . 'templates/' . $tabs[$current_tab]['template'];
        }
        echo '</div>';
        echo '</div>';
    }

    /**
     * Analyze artwork with deep learning
     * 
     * @param string $image_file Path to image file
     * @return array Analysis data
     */
    public function analyze_artwork($image_file) {
        try {
            // Get image data
            $image_data = file_get_contents($image_file);
            $image_base64 = base64_encode($image_data);
            
            // Request analysis from API
            $analysis = $this->api_manager->request('art_analysis', 'analyze', [
                'image' => $image_base64,
                'analysis_level' => 'deep', // comprehensive analysis
                'include_style' => true,
                'include_composition' => true,
                'include_color_analysis' => true,
                'include_subject_matter' => true,
                'include_emotional_impact' => true,
                'include_historical_context' => true
            ]);
            
            if (is_wp_error($analysis)) {
                throw new Exception($analysis->get_error_message());
            }
            
            // Get similar artworks
            $similar_artworks = $this->find_similar_artworks($analysis);
            
            // Get suggested pricing
            $pricing_suggestion = $this->suggest_pricing($analysis);
            
            // Get market trends for this style
            $market_trends = $this->get_market_trends($analysis['style']);
            
            return array(
                'analysis' => $analysis,
                'similar_artworks' => $similar_artworks,
                'pricing_suggestion' => $pricing_suggestion,
                'market_trends' => $market_trends
            );
        } catch (Exception $e) {
            error_log('Artwork Analysis Error: ' . $e->getMessage());
            return array(
                'error' => $e->getMessage()
            );
        }
    }

    /**
     * AJAX handler for artwork analysis
     */
    public function ajax_process_artwork_analysis() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'vortex_thorius_artwork_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'vortex-ai-marketplace')));
            exit;
        }
        
        // Check for image data
        if (!isset($_FILES['artwork']) || !file_exists($_FILES['artwork']['tmp_name'])) {
            wp_send_json_error(array('message' => __('No artwork image received.', 'vortex-ai-marketplace')));
            exit;
        }
        
        try {
            // Process artwork
            $artwork_file = $_FILES['artwork']['tmp_name'];
            $analysis = $this->analyze_artwork($artwork_file);
            
            if (isset($analysis['error'])) {
                wp_send_json_error(array('message' => $analysis['error']));
                exit;
            }
            
            // Track in analytics
            $analytics = new Vortex_Thorius_Analytics();
            $analytics->track_event('artwork_analysis', array(
                'size' => $_FILES['artwork']['size'],
                'style' => $analysis['analysis']['style'],
                'complexity' => $analysis['analysis']['complexity_score']
            ));
            
            wp_send_json_success($analysis);
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
        
        exit;
    }
} 