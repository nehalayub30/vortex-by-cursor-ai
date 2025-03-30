<?php
/**
 * The Text to Image AI model functionality.
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/ai-models
 */

/**
 * The Text to Image AI model functionality.
 *
 * Handles generation of images from text prompts using AI models.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/ai-models
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_Text2Img {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * The available AI models for text to image.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $models    The available AI models.
     */
    private $models;

    /**
     * The default AI model.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $default_model    The default AI model.
     */
    private $default_model;

    /**
     * API credentials storage.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $api_credentials    API credentials for different services.
     */
    private $api_credentials;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version           The version of this plugin.
     */
    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        // Initialize models
        $this->initialize_models();
        
        // Load API credentials
        $this->load_api_credentials();
        
        // Define hooks
        $this->define_hooks();
    }

    /**
     * Initialize available models.
     *
     * @since    1.0.0
     */
    private function initialize_models() {
        $this->models = array(
            'stable-diffusion-v1-5' => array(
                'name' => __( 'Stable Diffusion v1.5', 'vortex-ai-marketplace' ),
                'description' => __( 'A latent text-to-image diffusion model capable of generating photo-realistic images.', 'vortex-ai-marketplace' ),
                'provider' => 'stability-ai',
                'tola_cost' => 10,
                'supported_sizes' => array('512x512', '768x768'),
                'max_prompt_length' => 1000,
                'default_settings' => array(
                    'guidance_scale' => 7.5,
                    'num_inference_steps' => 30,
                    'samples' => 1,
                ),
            ),
            'stable-diffusion-xl' => array(
                'name' => __( 'Stable Diffusion XL', 'vortex-ai-marketplace' ),
                'description' => __( 'The latest model from Stability AI with enhanced image quality and prompt following.', 'vortex-ai-marketplace' ),
                'provider' => 'stability-ai',
                'tola_cost' => 20,
                'supported_sizes' => array('512x512', '768x768', '1024x1024'),
                'max_prompt_length' => 2000,
                'default_settings' => array(
                    'guidance_scale' => 7.5,
                    'num_inference_steps' => 40,
                    'samples' => 1,
                ),
            ),
            'dall-e-3' => array(
                'name' => __( 'DALL-E 3', 'vortex-ai-marketplace' ),
                'description' => __( 'OpenAI\'s latest text-to-image model with advanced capabilities.', 'vortex-ai-marketplace' ),
                'provider' => 'openai',
                'tola_cost' => 25,
                'supported_sizes' => array('1024x1024', '1024x1792', '1792x1024'),
                'max_prompt_length' => 4000,
                'default_settings' => array(
                    'quality' => 'standard',
                    'samples' => 1,
                ),
            ),
            'midjourney-v5' => array(
                'name' => __( 'Midjourney v5', 'vortex-ai-marketplace' ),
                'description' => __( 'Midjourney\'s latest model with photo-realistic capabilities.', 'vortex-ai-marketplace' ),
                'provider' => 'midjourney',
                'tola_cost' => 30,
                'supported_sizes' => array('1024x1024', '1024x1792', '1792x1024'),
                'max_prompt_length' => 2000,
                'default_settings' => array(
                    'quality' => 1,
                    'style' => 4,
                    'samples' => 1,
                ),
            ),
        );
        
        // Set default model
        $this->default_model = 'stable-diffusion-xl';
        
        // Allow third-party plugins to modify the models
        $this->models = apply_filters( 'vortex_text2img_models', $this->models );
    }

    /**
     * Load API credentials from options.
     *
     * @since    1.0.0
     */
    private function load_api_credentials() {
        $this->api_credentials = array(
            'stability-ai' => array(
                'api_key' => get_option( 'vortex_stability_ai_api_key', '' ),
                'api_url' => 'https://api.stability.ai/v1/generation',
            ),
            'openai' => array(
                'api_key' => get_option( 'vortex_openai_api_key', '' ),
                'api_url' => 'https://api.openai.com/v1/images/generations',
            ),
            'midjourney' => array(
                'api_key' => get_option( 'vortex_midjourney_api_key', '' ),
                'api_url' => get_option( 'vortex_midjourney_api_url', '' ),
            ),
        );
    }

    /**
     * Define hooks for this class.
     *
     * @since    1.0.0
     */
    private function define_hooks() {
        // Register REST API endpoints
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
        
        // Register AJAX handlers
        add_action( 'wp_ajax_vortex_generate_text2img', array( $this, 'ajax_generate_text2img' ) );
        
        // Admin settings hooks
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        
        // Register shortcodes
        add_shortcode( 'vortex_text2img_generator', array( $this, 'text2img_generator_shortcode' ) );
        
        // Custom user capabilities
        add_filter( 'user_has_cap', array( $this, 'user_capabilities' ), 10, 3 );
    }

    /**
     * Register settings for admin panel.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        // Register settings section
        add_settings_section(
            'vortex_text2img_settings',
            __( 'Text to Image Settings', 'vortex-ai-marketplace' ),
            array( $this, 'text2img_settings_section_callback' ),
            'vortex_ai_settings'
        );
        
        // Register API key settings
        register_setting(
            'vortex_ai_settings',
            'vortex_stability_ai_api_key',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            )
        );
        
        register_setting(
            'vortex_ai_settings',
            'vortex_openai_api_key',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            )
        );
        
        register_setting(
            'vortex_ai_settings',
            'vortex_midjourney_api_key',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            )
        );
        
        register_setting(
            'vortex_ai_settings',
            'vortex_midjourney_api_url',
            array(
                'type' => 'string',
                'sanitize_callback' => 'esc_url_raw',
                'default' => '',
            )
        );
        
        // API key fields
        add_settings_field(
            'vortex_stability_ai_api_key',
            __( 'Stability AI API Key', 'vortex-ai-marketplace' ),
            array( $this, 'stability_ai_api_key_callback' ),
            'vortex_ai_settings',
            'vortex_text2img_settings'
        );
        
        add_settings_field(
            'vortex_openai_api_key',
            __( 'OpenAI API Key', 'vortex-ai-marketplace' ),
            array( $this, 'openai_api_key_callback' ),
            'vortex_ai_settings',
            'vortex_text2img_settings'
        );
        
        add_settings_field(
            'vortex_midjourney_api_key',
            __( 'Midjourney API Key', 'vortex-ai-marketplace' ),
            array( $this, 'midjourney_api_key_callback' ),
            'vortex_ai_settings',
            'vortex_text2img_settings'
        );
        
        add_settings_field(
            'vortex_midjourney_api_url',
            __( 'Midjourney API URL', 'vortex-ai-marketplace' ),
            array( $this, 'midjourney_api_url_callback' ),
            'vortex_ai_settings',
            'vortex_text2img_settings'
        );
        
        // Default model setting
        register_setting(
            'vortex_ai_settings',
            'vortex_default_text2img_model',
            array(
                'type' => 'string',
                'sanitize_callback' => array( $this, 'sanitize_model_id' ),
                'default' => $this->default_model,
            )
        );
        
        add_settings_field(
            'vortex_default_text2img_model',
            __( 'Default Text to Image Model', 'vortex-ai-marketplace' ),
            array( $this, 'default_model_callback' ),
            'vortex_ai_settings',
            'vortex_text2img_settings'
        );
    }

    /**
     * Sanitize model ID.
     *
     * @since    1.0.0
     * @param    string    $model_id    Model ID to sanitize.
     * @return   string                 Sanitized model ID.
     */
    public function sanitize_model_id( $model_id ) {
        if ( ! isset( $this->models[$model_id] ) ) {
            return $this->default_model;
        }
        return $model_id;
    }

    /**
     * Settings section callback.
     *
     * @since    1.0.0
     */
    public function text2img_settings_section_callback() {
        echo '<p>' . esc_html__( 'Configure settings for Text to Image AI models.', 'vortex-ai-marketplace' ) . '</p>';
    }

    /**
     * Stability AI API key field callback.
     *
     * @since    1.0.0
     */
    public function stability_ai_api_key_callback() {
        $api_key = get_option( 'vortex_stability_ai_api_key', '' );
        echo '<input type="password" id="vortex_stability_ai_api_key" name="vortex_stability_ai_api_key" value="' . esc_attr( $api_key ) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__( 'Enter your Stability AI API key for Stable Diffusion models.', 'vortex-ai-marketplace' ) . ' <a href="https://platform.stability.ai/" target="_blank">' . esc_html__( 'Get API key', 'vortex-ai-marketplace' ) . '</a></p>';
    }

    /**
     * OpenAI API key field callback.
     *
     * @since    1.0.0
     */
    public function openai_api_key_callback() {
        $api_key = get_option( 'vortex_openai_api_key', '' );
        echo '<input type="password" id="vortex_openai_api_key" name="vortex_openai_api_key" value="' . esc_attr( $api_key ) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__( 'Enter your OpenAI API key for DALL-E models.', 'vortex-ai-marketplace' ) . ' <a href="https://platform.openai.com/" target="_blank">' . esc_html__( 'Get API key', 'vortex-ai-marketplace' ) . '</a></p>';
    }

    /**
     * Midjourney API key field callback.
     *
     * @since    1.0.0
     */
    public function midjourney_api_key_callback() {
        $api_key = get_option( 'vortex_midjourney_api_key', '' );
        echo '<input type="password" id="vortex_midjourney_api_key" name="vortex_midjourney_api_key" value="' . esc_attr( $api_key ) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__( 'Enter your Midjourney API key.', 'vortex-ai-marketplace' ) . '</p>';
    }

    /**
     * Midjourney API URL field callback.
     *
     * @since    1.0.0
     */
    public function midjourney_api_url_callback() {
        $api_url = get_option( 'vortex_midjourney_api_url', '' );
        echo '<input type="url" id="vortex_midjourney_api_url" name="vortex_midjourney_api_url" value="' . esc_url( $api_url ) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__( 'Enter your Midjourney API URL.', 'vortex-ai-marketplace' ) . '</p>';
    }

    /**
     * Default model field callback.
     *
     * @since    1.0.0
     */
    public function default_model_callback() {
        $default_model = get_option( 'vortex_default_text2img_model', $this->default_model );
        
        echo '<select id="vortex_default_text2img_model" name="vortex_default_text2img_model">';
        
        foreach ( $this->models as $model_id => $model ) {
            $selected = $model_id === $default_model ? 'selected' : '';
            echo '<option value="' . esc_attr( $model_id ) . '" ' . $selected . '>' . esc_html( $model['name'] ) . '</option>';
        }
        
        echo '</select>';
        echo '<p class="description">' . esc_html__( 'Select the default Text to Image model.', 'vortex-ai-marketplace' ) . '</p>';
    }

    /**
     * Register REST API routes.
     *
     * @since    1.0.0
     */
    public function register_rest_routes() {
        register_rest_route(
            'vortex/v1',
            '/text2img/generate',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'rest_generate_image' ),
                'permission_callback' => array( $this, 'rest_check_permissions' ),
                'args'                => array(
                    'prompt' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'model' => array(
                        'required'          => false,
                        'type'              => 'string',
                        'sanitize_callback' => array( $this, 'sanitize_model_id' ),
                        'default'           => $this->default_model,
                    ),
                    'negative_prompt' => array(
                        'required'          => false,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'width' => array(
                        'required'          => false,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                        'default'           => 512,
                    ),
                    'height' => array(
                        'required'          => false,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                        'default'           => 512,
                    ),
                    'guidance_scale' => array(
                        'required'          => false,
                        'type'              => 'number',
                        'sanitize_callback' => 'floatval',
                    ),
                    'num_inference_steps' => array(
                        'required'          => false,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                    'num_samples' => array(
                        'required'          => false,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                        'default'           => 1,
                    ),
                    'seed' => array(
                        'required'          => false,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                ),
            )
        );
        
        register_rest_route(
            'vortex/v1',
            '/text2img/models',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'rest_get_models' ),
                'permission_callback' => '__return_true',
            )
        );
    }

    /**
     * Check permissions for REST API requests.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    The request object.
     * @return   bool|WP_Error                  True if the request has access, WP_Error otherwise.
     */
    public function rest_check_permissions( $request ) {
        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            return new WP_Error(
                'rest_forbidden',
                __( 'You must be logged in to generate images.', 'vortex-ai-marketplace' ),
                array( 'status' => 401 )
            );
        }
        
        // Check if user has permission
        if ( ! current_user_can( 'generate_text2img' ) ) {
            return new WP_Error(
                'rest_forbidden',
                __( 'You do not have permission to generate images.', 'vortex-ai-marketplace' ),
                array( 'status' => 403 )
            );
        }
        
        // Check TOLA balance
        $user_id = get_current_user_id();
        $model_id = $request->get_param( 'model' );
        if ( empty( $model_id ) ) {
            $model_id = $this->default_model;
        }
        
        $tola_cost = $this->get_model_tola_cost( $model_id );
        $tola_balance = $this->get_user_tola_balance( $user_id );
        
        if ( $tola_balance < $tola_cost ) {
            return new WP_Error(
                'insufficient_tola',
                __( 'You do not have enough TOLA to generate images with this model.', 'vortex-ai-marketplace' ),
                array( 'status' => 402 )
            );
        }
        
        return true;
    }

    /**
     * REST API endpoint for getting available models.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    The request object.
     * @return   WP_REST_Response                The response.
     */
    public function rest_get_models( $request ) {
        $models_data = array();
        
        foreach ( $this->models as $model_id => $model ) {
            $models_data[$model_id] = array(
                'id'              => $model_id,
                'name'            => $model['name'],
                'description'     => $model['description'],
                'provider'        => $model['provider'],
                'tola_cost'       => $model['tola_cost'],
                'supported_sizes' => $model['supported_sizes'],
                'max_prompt_length' => $model['max_prompt_length'],
            );
        }
        
        return new WP_REST_Response( array(
            'models'        => $models_data,
            'default_model' => get_option( 'vortex_default_text2img_model', $this->default_model ),
        ), 200 );
    }

    /**
     * REST API endpoint for generating images.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    The request object.
     * @return   WP_REST_Response|WP_Error      The response or error.
     */
    public function rest_generate_image( $request ) {
        $user_id = get_current_user_id();
        $prompt = $request->get_param( 'prompt' );
        $model_id = $request->get_param( 'model' );
        $negative_prompt = $request->get_param( 'negative_prompt' );
        $width = $request->get_param( 'width' );
        $height = $request->get_param( 'height' );
        $guidance_scale = $request->get_param( 'guidance_scale' );
        $num_inference_steps = $request->get_param( 'num_inference_steps' );
        $num_samples = $request->get_param( 'num_samples' );
        $seed = $request->get_param( 'seed' );
        
        // Validate model
        if ( ! isset( $this->models[$model_id] ) ) {
            return new WP_Error(
                'invalid_model',
                __( 'Invalid model specified.', 'vortex-ai-marketplace' ),
                array( 'status' => 400 )
            );
        }
        
        // Check prompt length
        $max_prompt_length = $this->models[$model_id]['max_prompt_length'];
        if ( strlen( $prompt ) > $max_prompt_length ) {
            return new WP_Error(
                'prompt_too_long',
                sprintf( __( 'Prompt is too long. Maximum length is %d characters.', 'vortex-ai-marketplace' ), $max_prompt_length ),
                array( 'status' => 400 )
            );
        }
        
        // Validate dimensions
        $supported_sizes = $this->models[$model_id]['supported_sizes'];
        $size_str = $width . 'x' . $height;
        if ( ! in_array( $size_str, $supported_sizes ) ) {
            return new WP_Error(
                'invalid_dimensions',
                sprintf( __( 'Invalid dimensions. Supported sizes for this model are: %s', 'vortex-ai-marketplace' ), implode( ', ', $supported_sizes ) ),
                array( 'status' => 400 )
            );
        }
        
        // Get default settings if not provided
        $default_settings = $this->models[$model_id]['default_settings'];
        
        if ( empty( $guidance_scale ) && isset( $default_settings['guidance_scale'] ) ) {
            $guidance_scale = $default_settings['guidance_scale'];
        }
        
        if ( empty( $num_inference_steps ) && isset( $default_settings['num_inference_steps'] ) ) {
            $num_inference_steps = $default_settings['num_inference_steps'];
        }
        
        if ( empty( $seed ) ) {
            $seed = rand( 1, 999999 );
        }
        
        // Generate image based on model provider
        $result = $this->generate_image(
            $model_id,
            $prompt,
            $negative_prompt,
            $width,
            $height,
            $guidance_scale,
            $num_inference_steps,
            $num_samples,
            $seed
        );
        
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        
        // Deduct TOLA from user's balance
        $tola_cost = $this->get_model_tola_cost( $model_id );
        $this->deduct_tola_balance( $user_id, $tola_cost );
        
        // Log generation
        $this->log_generation( $user_id, $model_id, $prompt, $result );
        
        return new WP_REST_Response( array(
            'success' => true,
            'images'  => $result['images'],
            'meta'    => array(
                'model'          => $model_id,
                'prompt'         => $prompt,
                'negative_prompt' => $negative_prompt,
                'seed'           => $seed,
                'width'          => $width,
                'height'         => $height,
                'guidance_scale' => $guidance_scale,
                'steps'          => $num_inference_steps,
                'tola_cost'      => $tola_cost,
            ),
        ), 200 );
    }

    /**
     * AJAX handler for generating images.
     *
     * @since    1.0.0
     */
    public function ajax_generate_text2img() {
        // Check nonce
        if ( ! check_ajax_referer( 'vortex_text2img_nonce', 'nonce', false ) ) {
            wp_send_json_error( array(
                'message' => __( 'Security check failed.', 'vortex-ai-marketplace' ),
            ), 401 );
        }
        
        // Check user permissions
        if ( ! current_user_can( 'generate_text2img' ) ) {
            wp_send_json_error( array(
                'message' => __( 'You do not have permission to generate images.', 'vortex-ai-marketplace' ),
            ), 403 );
        }
        
        // Get parameters
        $prompt = isset( $_POST['prompt'] ) ? sanitize_text_field( wp_unslash( $_POST['prompt'] ) ) : '';
        $model_id = isset( $_POST['model'] ) ? sanitize_text_field( wp_unslash( $_POST['model'] ) ) : $this->default_model;
        $negative_prompt = isset( $_POST['negative_prompt'] ) ? sanitize_text_field( wp_unslash( $_POST['negative_prompt'] ) ) : '';
        $width = isset( $_POST['width'] ) ? absint( $_POST['width'] ) : 512;
        $height = isset( $_POST['height'] ) ? absint( $_POST['height'] ) : 512;
        $guidance_scale = isset( $_POST['guidance_scale'] ) ? floatval( $_POST['guidance_scale'] ) : null;
        $num_inference_steps = isset( $_POST['num_inference_steps'] ) ? absint( $_POST['num_inference_steps'] ) : null;
        $num_samples = isset( $_POST['num_samples'] ) ? absint( $_POST['num_samples'] ) : 1;
        $seed = isset( $_POST['seed'] ) ? absint( $_POST['seed'] ) : rand( 1, 999999 );
        
        // Validate model
        if ( ! isset( $this->models[$model_id] ) ) {
            wp_send_json_error( array(
                'message' => __( 'Invalid model specified.', 'vortex-ai-marketplace' ),
            ), 400 );
        }
        
        // Validate prompt
        if ( empty( $prompt ) ) {
            wp_send_json_error( array(
                'message' => __( 'Prompt cannot be empty.', 'vortex-ai-marketplace' ),
            ), 400 );
        }
        
        // Check prompt length
        $max_prompt_length = $this->models[$model_id]['max_prompt_length'];
        if ( strlen( $prompt ) > $max_prompt_length ) {
            wp_send_json_error( array(
                'message' => sprintf( __( 'Prompt is too long. Maximum length is %d characters.', 'vortex-ai-marketplace' ), $max_prompt_length ),
            ), 400 );
        }
        
        // Validate dimensions
        $supported_sizes = $this->models[$model_id]['supported_sizes'];
        $size_str = $width . 'x' . $height;
        if ( ! in_array( $size_str, $supported_sizes ) ) {
            wp_send_json_error( array(
                'message' => sprintf( __( 'Invalid dimensions. Supported sizes for this model are: %s', 'vortex-ai-marketplace' ), implode( ', ', $supported_sizes ) ),
            ), 400 );
        }
        
        // Check TOLA balance
        $user_id = get_current_user_id();
        $tola_cost = $this->get_model_tola_cost( $model_id );
        $tola_balance = $this->get_user_tola_balance( $user_id );
        
        if ( $tola_balance < $tola_cost ) {
            wp_send_json_error( array(
                'message' => __( 'You do not have enough TOLA to generate images with this model.', 'vortex-ai-marketplace' ),
                'balance' => $tola_balance,
                'cost'    => $tola_cost,
            ), 402 );
        }
        
        // Get default settings if not provided
        $default_settings = $this->models[$model_id]['default_settings'];
        
        if ( empty( $guidance_scale ) && isset( $default_settings['guidance_scale'] ) ) {
            $guidance_scale = $default_settings['guidance_scale'];
        }
        
        if ( empty( $num_inference_steps ) && isset( $default_settings['num_inference_steps'] ) ) {
            $num_inference_steps = $default_settings['num_inference_steps'];
        }
        
        // Generate image
        $result = $this->generate_image(
            $model_id,
            $prompt,
            $negative_prompt,
            $width,
            $height,
            $guidance_scale,
            $num_inference_steps,
            $num_samples,
            $seed
        );
        
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array(
                'message' => $result->get_error_message(),
            ), 500 );
        }
        
        // Deduct TOLA from user's balance
        $this->deduct_tola_balance( $user_id, $tola_cost );
        
        // Log generation
        $this->log_generation( $user_id, $model_id, $prompt, $result );
        
        wp_send_json_success( array(
            'images' => $result['images'],
            'meta'   => array(
                'model'          => $model_id,
                'prompt'         => $prompt,
                'negative_prompt' => $negative_prompt,
                'seed'           => $seed,
                'width'          => $width,
                'height'         => $height,
                'guidance_scale' => $guidance_scale,
                'steps'          => $num_inference_steps,
                'tola_cost'      => $tola_cost,
                'balance'        => $this->get_user_tola_balance( $user_id ),
            ),
        ) );
    }

    /**
     * Generate image based on model and parameters.
     *
     * @since    1.0.0
     * @param    string     $model_id             The model ID.
     * @param    string     $prompt               The prompt text.
     * @param    string     $negative_prompt      Negative prompt text.
     * @param    int        $width                Image width.
     * @param    int        $height               Image height.
     * @param    float      $guidance_scale       Guidance scale.
     * @param    int        $num_inference_steps  Number of inference steps.
     * @param    int        $num_samples          Number of images to generate.
     * @param    int        $seed                 Random seed.
     * @return   array|WP_Error                   Generated images data or error.
     */
    private function generate_image( $model_id, $prompt, $negative_prompt, $width, $height, $guidance_scale, $num_inference_steps, $num_samples, $seed ) {
        // Get model data
        $model = $this->models[$model_id];
        $provider = $model['provider'];
        
        // Check if provider credentials are available
        if ( empty( $this->api_credentials[$provider]['api_key'] ) ) {
            return new WP_Error(
                'missing_api_key',
                sprintf( __( 'API key for %s is not configured.', 'vortex-ai-marketplace' ), $provider ),
                array( 'status' => 500 )
            );
        }
        
        // Generate based on provider
        switch ( $provider ) {
            case 'stability-ai':
                return $this->generate_stability_ai(
                    $model_id,
                    $prompt,
                    $negative_prompt,
                    $width,
                    $height,
                    $guidance_scale,
                    $num_inference_steps,
                    $num_samples,
                    $seed
                );
                
            case 'openai':
                return $this->generate_openai(
                    $model_id,
                    $prompt,
                    $width,
                    $height,
                    $num_samples
                );
                
            case 'midjourney':
                return $this->generate_midjourney(
                    $prompt,
                    $negative_prompt,
                    $width,
                    $height,
                    $num_samples
                );
                
            default:
                return new WP_Error(
                    'unsupported_provider',
                    sprintf( __( 'Unsupported provider: %s', 'vortex-ai-marketplace' ), $provider ),
                    array( 'status' => 500 )
                );
        }
    }

    /**
     * Generate image using Stability AI API.
     *
     * @since    1.0.0
     * @param    string     $model_id             The model ID.
     * @param    string     $prompt               The prompt text.
     * @param    string     $negative_prompt      Negative prompt text.
     * @param    int        $width                Image width.
     * @param    int        $height               Image height.
     * @param    float      $guidance_scale       Guidance scale.
     * @param    int        $num_inference_steps  Number of inference steps.
     * @param    int        $num_samples          Number of images to generate.
     * @param    int        $seed                 Random seed.
     * @return   array|WP_Error                   Generated images data or error.
     */
    private function generate_stability_ai( $model_id, $prompt, $negative_prompt, $width, $height, $guidance_scale, $num_inference_steps, $num_samples, $seed ) {
        $api_url = $this->api_credentials['stability-ai']['api_url'] . '/' . $model_id . '/text-to-image';
        $api_key = $this->api_credentials['stability-ai']['api_key'];
        
        $payload = array(
            'text_prompts' => array(
                array(
                    'text'   => $prompt,
                    'negative_prompt' => $negative_prompt,
                ),
            ),
            'cfg_scale' => $guidance_scale,
            'height' => $height,
            'width' => $width,
            'samples' => $num_samples,
            'seed' => $seed,
        );
        
        $response = wp_remote_post( $api_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
            ),
            'body' => json_encode( $payload ),
            'timeout' => 30,
        ) );
        
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        
        if ( ! isset( $data['artifacts'] ) || ! is_array( $data['artifacts'] ) ) {
            return new WP_Error(
                'invalid_response',
                __( 'Invalid response format from Stability AI.', 'vortex-ai-marketplace' ),
                array( 'status' => 500 )
            );
        }
        
        $images = array();
        foreach ( $data['artifacts'] as $artifact ) {
            $images[] = array(
                'url' => $artifact['base64'],
            );
        }
        
        return array(
            'images' => $images,
        );
    }

    /**
     * Generate image using OpenAI API.
     *
     * @since    1.0.0
     * @param    string     $model_id             The model ID.
     * @param    string     $prompt               The prompt text.
     * @param    int        $width                Image width.
     * @param    int        $height               Image height.
     * @param    int        $num_samples          Number of images to generate.
     * @return   array|WP_Error                   Generated images data or error.
     */
    private function generate_openai( $model_id, $prompt, $width, $height, $num_samples ) {
        $api_url = $this->api_credentials['openai']['api_url'];
        $api_key = $this->api_credentials['openai']['api_key'];
        
        $payload = array(
            'model' => $model_id,
            'prompt' => $prompt,
            'n' => $num_samples,
            'size' => $width . 'x' . $height,
        );
        
        $response = wp_remote_post( $api_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
            ),
            'body' => json_encode( $payload ),
            'timeout' => 30,
        ) );
        
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        
        if ( ! isset( $data['data'] ) || ! is_array( $data['data'] ) ) {
            return new WP_Error(
                'invalid_response',
                __( 'Invalid response format from OpenAI.', 'vortex-ai-marketplace' ),
                array( 'status' => 500 )
            );
        }
        
        $images = array();
        foreach ( $data['data'] as $artifact ) {
            $images[] = array(
                'url' => $artifact['url'],
            );
        }
        
        return array(
            'images' => $images,
        );
    }

    /**
     * Generate image using Midjourney API.
     *
     * @since    1.0.0
     * @param    string     $prompt               The prompt text.
     * @param    string     $negative_prompt      Negative prompt text.
     * @param    int        $width                Image width.
     * @param    int        $height               Image height.
     * @param    int        $num_samples          Number of images to generate.
     * @return   array|WP_Error                   Generated images data or error.
     */
    private function generate_midjourney( $prompt, $negative_prompt, $width, $height, $num_samples ) {
        // Implementation of Midjourney API generation logic
        // This is a placeholder and should be replaced with the actual implementation
        return new WP_Error(
            'unimplemented',
            __( 'Midjourney generation logic is not implemented.', 'vortex-ai-marketplace' ),
            array( 'status' => 500 )
        );
    }

    /**
     * Log generation.
     *
     * @since    1.0.0
     * @param    int        $user_id              The user ID.
     * @param    string     $model_id             The model ID.
     * @param    string     $prompt               The prompt text.
     * @param    array|WP_Error $result          Generated images data or error.
     */
    private function log_generation( $user_id, $model_id, $prompt, $result ) {
        // Implementation of logging logic
        // This is a placeholder and should be replaced with the actual implementation
    }

    /**
     * Get model TOLA cost.
     *
     * @since    1.0.0
     * @param    string     $model_id             The model ID.
     * @return   int                            The model TOLA cost.
     */
    private function get_model_tola_cost( $model_id ) {
        if ( isset( $this->models[$model_id]['tola_cost'] ) ) {
            return $this->models[$model_id]['tola_cost'];
        }
        return 0;
    }

    /**
     * Get user TOLA balance.
     *
     * @since    1.0.0
     * @param    int        $user_id              The user ID.
     * @return   float                            The user TOLA balance.
     */
    private function get_user_tola_balance( $user_id ) {
        // Implementation of getting user TOLA balance
        return 0.0; // Placeholder return, actual implementation needed
    }

    /**
     * Deduct TOLA from user's balance.
     *
     * @since    1.0.0
     * @param    int        $user_id              The user ID.
     * @param    float      $tola_cost           The TOLA cost to deduct.
     */
    private function deduct_tola_balance( $user_id, $tola_cost ) {
        // Implementation of deducting TOLA from user's balance
    }

    /**
     * User capabilities filter.
     *
     * @since    1.0.0
     * @param    array      $caps               The user capabilities.
     * @param    string     $cap                The capability being checked.
     * @param    int        $user_id            The user ID.
     * @return   array                            The filtered user capabilities.
     */
    public function user_capabilities( $caps, $cap, $user_id ) {
        if ( 'generate_text2img' === $cap ) {
            $caps[] = 'generate_text2img';
        }
        return $caps;
    }
} 