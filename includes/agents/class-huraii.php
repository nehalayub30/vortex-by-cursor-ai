<?php
/**
 * HURAII Agent - Visual Artwork Generation
 *
 * @package VortexAiAgents
 * @subpackage Agents
 */

namespace VortexAiAgents\Agents;

use VortexAiAgents\Services\Cache_Service;
use VortexAiAgents\Services\Data\Art_Style_Service;
use VortexAiAgents\Services\Data\Artist_Reference_Service;

/**
 * HURAII Agent class for generating visual artwork
 */
class HURAII {
    /**
     * Cache service instance
     *
     * @var Cache_Service
     */
    private $cache_service;

    /**
     * Art style service instance
     *
     * @var Art_Style_Service
     */
    private $art_style_service;

    /**
     * Artist reference service instance
     *
     * @var Artist_Reference_Service
     */
    private $artist_reference_service;

    /**
     * API endpoint for HURAII generation
     *
     * @var string
     */
    private $api_endpoint;

    /**
     * API key for HURAII service
     *
     * @var string
     */
    private $api_key;

    /**
     * Performance tier (standard, premium, professional)
     *
     * @var string
     */
    private $tier;

    /**
     * Constructor
     */
    public function __construct() {
        $this->cache_service = new Cache_Service( 'huraii_generation', 24 * HOUR_IN_SECONDS );
        $this->art_style_service = new Art_Style_Service();
        $this->artist_reference_service = new Artist_Reference_Service();
        
        $this->api_endpoint = get_option( 'vortex_huraii_api_endpoint', 'https://api.vortex.ai/huraii/v1/generate' );
        $this->api_key = get_option( 'vortex_huraii_api_key', '' );
        $this->tier = get_option( 'vortex_huraii_tier', 'standard' );
        
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
        add_shortcode( 'vortex_huraii_generator', array( $this, 'generator_shortcode' ) );
    }

    /**
     * Register REST API routes
     */
    public function register_routes() {
        register_rest_route(
            'vortex-ai/v1',
            '/huraii/generate',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'generate_artwork' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'prompt'          => array(
                        'required'    => true,
                        'type'        => 'string',
                        'description' => 'Text prompt describing the artwork to generate',
                    ),
                    'style'           => array(
                        'required'    => false,
                        'type'        => 'string',
                        'description' => 'Art style to apply',
                    ),
                    'artist_influence' => array(
                        'required'    => false,
                        'type'        => 'string',
                        'description' => 'Artist to influence the generation',
                    ),
                    'medium'          => array(
                        'required'    => false,
                        'type'        => 'string',
                        'description' => 'Medium to simulate (oil, watercolor, digital, etc.)',
                    ),
                    'resolution'      => array(
                        'required'    => false,
                        'type'        => 'string',
                        'default'     => '1024x1024',
                        'description' => 'Output resolution',
                    ),
                    'variations'      => array(
                        'required'    => false,
                        'type'        => 'integer',
                        'default'     => 1,
                        'minimum'     => 1,
                        'maximum'     => 4,
                        'description' => 'Number of variations to generate',
                    ),
                ),
            )
        );

        register_rest_route(
            'vortex-ai/v1',
            '/huraii/styles',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_available_styles' ),
                'permission_callback' => array( $this, 'check_permission' ),
            )
        );

        register_rest_route(
            'vortex-ai/v1',
            '/huraii/artists',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_available_artists' ),
                'permission_callback' => array( $this, 'check_permission' ),
            )
        );
    }

    /**
     * Check if user has permission to access the API
     *
     * @return bool
     */
    public function check_permission() {
        // For public endpoints, allow access to logged-in users
        return is_user_logged_in();
    }

    /**
     * Generate artwork based on provided parameters
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function generate_artwork( $request ) {
        $prompt = sanitize_text_field( $request->get_param( 'prompt' ) );
        $style = sanitize_text_field( $request->get_param( 'style' ) );
        $artist_influence = sanitize_text_field( $request->get_param( 'artist_influence' ) );
        $medium = sanitize_text_field( $request->get_param( 'medium' ) );
        $resolution = sanitize_text_field( $request->get_param( 'resolution' ) );
        $variations = intval( $request->get_param( 'variations' ) );

        // Check if API key is configured
        if ( empty( $this->api_key ) ) {
            return new \WP_Error(
                'huraii_api_not_configured',
                __( 'HURAII API is not properly configured.', 'vortex-ai-agents' ),
                array( 'status' => 500 )
            );
        }

        // Generate cache key based on parameters
        $cache_key = md5( $prompt . $style . $artist_influence . $medium . $resolution . $variations );
        $cached_result = $this->cache_service->get( $cache_key );

        if ( $cached_result ) {
            return rest_ensure_response( $cached_result );
        }

        // Prepare request to HURAII API
        $request_body = array(
            'prompt'           => $prompt,
            'tier'             => $this->tier,
            'resolution'       => $resolution,
            'num_outputs'      => $variations,
        );

        // Add optional parameters if provided
        if ( ! empty( $style ) ) {
            $request_body['style'] = $style;
        }

        if ( ! empty( $artist_influence ) ) {
            $request_body['artist_influence'] = $artist_influence;
        }

        if ( ! empty( $medium ) ) {
            $request_body['medium'] = $medium;
        }

        $response = wp_remote_post(
            $this->api_endpoint,
            array(
                'headers'     => array(
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $this->api_key,
                ),
                'body'        => wp_json_encode( $request_body ),
                'timeout'     => 60, // Longer timeout for image generation
                'data_format' => 'body',
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $response_code !== 200 ) {
            return new \WP_Error(
                'huraii_api_error',
                isset( $response_body['message'] ) ? $response_body['message'] : __( 'Error communicating with HURAII API.', 'vortex-ai-agents' ),
                array( 'status' => $response_code )
            );
        }

        // Process and store the generated images
        $processed_results = $this->process_generation_results( $response_body, $prompt );
        
        // Cache the results
        $this->cache_service->set( $cache_key, $processed_results );

        return rest_ensure_response( $processed_results );
    }

    /**
     * Process generation results and store images in media library
     *
     * @param array  $api_response API response data.
     * @param string $prompt Original prompt used for generation.
     * @return array Processed results with WordPress media IDs
     */
    private function process_generation_results( $api_response, $prompt ) {
        $results = array(
            'prompt'     => $prompt,
            'images'     => array(),
            'generation_id' => isset( $api_response['generation_id'] ) ? $api_response['generation_id'] : uniqid( 'huraii_' ),
            'created_at' => current_time( 'mysql' ),
        );

        if ( ! isset( $api_response['images'] ) || empty( $api_response['images'] ) ) {
            return $results;
        }

        foreach ( $api_response['images'] as $index => $image_data ) {
            // For base64 encoded images
            if ( isset( $image_data['base64'] ) ) {
                $image_id = $this->save_base64_image_to_media_library(
                    $image_data['base64'],
                    sanitize_title( $prompt ) . '_' . $index,
                    $prompt
                );
                
                if ( ! is_wp_error( $image_id ) ) {
                    $results['images'][] = array(
                        'id'  => $image_id,
                        'url' => wp_get_attachment_url( $image_id ),
                        'metadata' => isset( $image_data['metadata'] ) ? $image_data['metadata'] : array(),
                    );
                }
            }
            // For image URLs
            elseif ( isset( $image_data['url'] ) ) {
                $image_id = $this->save_remote_image_to_media_library(
                    $image_data['url'],
                    sanitize_title( $prompt ) . '_' . $index,
                    $prompt
                );
                
                if ( ! is_wp_error( $image_id ) ) {
                    $results['images'][] = array(
                        'id'  => $image_id,
                        'url' => wp_get_attachment_url( $image_id ),
                        'metadata' => isset( $image_data['metadata'] ) ? $image_data['metadata'] : array(),
                    );
                }
            }
        }

        // Store generation record in custom table for history
        $this->store_generation_record( $results );

        return $results;
    }

    /**
     * Save base64 encoded image to media library
     *
     * @param string $base64_image Base64 encoded image data.
     * @param string $filename Filename to use.
     * @param string $description Image description.
     * @return int|WP_Error Attachment ID or WP_Error
     */
    private function save_base64_image_to_media_library( $base64_image, $filename, $description ) {
        $upload_dir = wp_upload_dir();
        $upload_path = $upload_dir['path'];
        $upload_url = $upload_dir['url'];
        
        // Get file data from base64 string
        $decoded_image = base64_decode( preg_replace( '#^data:image/\w+;base64,#i', '', $base64_image ) );
        
        if ( empty( $decoded_image ) ) {
            return new \WP_Error( 'invalid_image', __( 'Invalid image data', 'vortex-ai-agents' ) );
        }
        
        $filename = sanitize_file_name( $filename . '.png' );
        $file_path = $upload_path . '/' . $filename;
        
        // Save file
        file_put_contents( $file_path, $decoded_image );
        
        // Check file type
        $file_type = wp_check_filetype( $filename, null );
        
        // Prepare attachment data
        $attachment = array(
            'post_mime_type' => $file_type['type'],
            'post_title'     => sprintf( __( 'HURAII Generated: %s', 'vortex-ai-agents' ), $description ),
            'post_content'   => $description,
            'post_status'    => 'inherit',
            'meta_input'     => array(
                '_huraii_generated' => true,
                '_huraii_prompt'    => $description,
            ),
        );
        
        // Insert attachment
        $attachment_id = wp_insert_attachment( $attachment, $file_path );
        
        if ( ! is_wp_error( $attachment_id ) ) {
            // Include image.php for wp_generate_attachment_metadata
            require_once ABSPATH . 'wp-admin/includes/image.php';
            
            // Generate metadata and update attachment
            $attachment_data = wp_generate_attachment_metadata( $attachment_id, $file_path );
            wp_update_attachment_metadata( $attachment_id, $attachment_data );
        }
        
        return $attachment_id;
    }

    /**
     * Save remote image to media library
     *
     * @param string $image_url Remote image URL.
     * @param string $filename Filename to use.
     * @param string $description Image description.
     * @return int|WP_Error Attachment ID or WP_Error
     */
    private function save_remote_image_to_media_library( $image_url, $filename, $description ) {
        // Include necessary files for media handling
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        
        // Download file to temp location
        $temp_file = download_url( $image_url );
        
        if ( is_wp_error( $temp_file ) ) {
            return $temp_file;
        }
        
        $filename = sanitize_file_name( $filename . '.png' );
        
        $file_array = array(
            'name'     => $filename,
            'tmp_name' => $temp_file,
        );
        
        // Do the validation and storage
        $attachment_id = media_handle_sideload(
            $file_array,
            0,
            sprintf( __( 'HURAII Generated: %s', 'vortex-ai-agents' ), $description ),
            array(
                'post_content' => $description,
                'meta_input'   => array(
                    '_huraii_generated' => true,
                    '_huraii_prompt'    => $description,
                ),
            )
        );
        
        // If error, clean up temp file
        if ( is_wp_error( $attachment_id ) ) {
            @unlink( $temp_file );
        }
        
        return $attachment_id;
    }

    /**
     * Store generation record in custom table
     *
     * @param array $generation_data Generation data to store.
     * @return int|false Record ID or false on failure
     */
    private function store_generation_record( $generation_data ) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_huraii_generations';
        
        // Create table if it doesn't exist
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
            $this->create_generations_table();
        }
        
        $image_ids = array();
        foreach ( $generation_data['images'] as $image ) {
            $image_ids[] = $image['id'];
        }
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'user_id'       => get_current_user_id(),
                'prompt'        => $generation_data['prompt'],
                'generation_id' => $generation_data['generation_id'],
                'image_ids'     => implode( ',', $image_ids ),
                'created_at'    => $generation_data['created_at'],
                'metadata'      => wp_json_encode( $generation_data ),
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s' )
        );
        
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Create generations table if it doesn't exist
     */
    private function create_generations_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'vortex_huraii_generations';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            prompt text NOT NULL,
            generation_id varchar(255) NOT NULL,
            image_ids text NOT NULL,
            created_at datetime NOT NULL,
            metadata longtext NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY generation_id (generation_id)
        ) $charset_collate;";
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Get available art styles
     *
     * @return \WP_REST_Response
     */
    public function get_available_styles() {
        $styles = $this->art_style_service->get_all_styles();
        return rest_ensure_response( $styles );
    }

    /**
     * Get available artist references
     *
     * @return \WP_REST_Response
     */
    public function get_available_artists() {
        $artists = $this->artist_reference_service->get_all_artists();
        return rest_ensure_response( $artists );
    }

    /**
     * Shortcode handler for HURAII generator
     *
     * @param array $atts Shortcode attributes.
     * @return string Shortcode output
     */
    public function generator_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'style'    => '',
                'medium'   => '',
                'width'    => '800',
                'height'   => '600',
                'class'    => '',
                'template' => 'default',
            ),
            $atts,
            'vortex_huraii_generator'
        );
        
        // Enqueue necessary scripts and styles
        wp_enqueue_script( 'vortex-huraii-generator' );
        wp_enqueue_style( 'vortex-huraii-generator' );
        
        // Localize script with shortcode attributes
        wp_localize_script(
            'vortex-huraii-generator',
            'vortexHuraiiParams',
            array(
                'apiUrl'   => rest_url( 'vortex-ai/v1/huraii/' ),
                'nonce'    => wp_create_nonce( 'wp_rest' ),
                'defaults' => array(
                    'style'  => $atts['style'],
                    'medium' => $atts['medium'],
                ),
            )
        );
        
        // Start output buffering
        ob_start();
        
        // Include template
        $template_path = plugin_dir_path( dirname( __FILE__ ) ) . 'templates/huraii-' . $atts['template'] . '.php';
        
        if ( file_exists( $template_path ) ) {
            include $template_path;
        } else {
            include plugin_dir_path( dirname( __FILE__ ) ) . 'templates/huraii-default.php';
        }
        
        // Return buffered content
        return ob_get_clean();
    }

    /**
     * Generate artwork with style transfer
     *
     * @param string $source_image_url URL of source image.
     * @param string $style_reference Style reference (artist name or style).
     * @param array  $options Additional options.
     * @return array|WP_Error Generation results or error
     */
    public function generate_style_transfer( $source_image_url, $style_reference, $options = array() ) {
        // Check if API key is configured
        if ( empty( $this->api_key ) ) {
            return new \WP_Error(
                'huraii_api_not_configured',
                __( 'HURAII API is not properly configured.', 'vortex-ai-agents' )
            );
        }

        $default_options = array(
            'strength' => 0.75, // Style transfer strength (0.0 to 1.0)
            'resolution' => '1024x1024',
            'preserve_color' => false,
        );

        $options = wp_parse_args( $options, $default_options );

        // Generate cache key
        $cache_key = md5( $source_image_url . $style_reference . wp_json_encode( $options ) );
        $cached_result = $this->cache_service->get( $cache_key );

        if ( $cached_result ) {
            return $cached_result;
        }

        // Prepare request to HURAII API
        $request_body = array(
            'source_image' => $source_image_url,
            'style_reference' => $style_reference,
            'strength' => floatval( $options['strength'] ),
            'resolution' => $options['resolution'],
            'preserve_color' => (bool) $options['preserve_color'],
            'tier' => $this->tier,
        );

        $response = wp_remote_post(
            $this->api_endpoint . '/style-transfer',
            array(
                'headers'     => array(
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $this->api_key,
                ),
                'body'        => wp_json_encode( $request_body ),
                'timeout'     => 60,
                'data_format' => 'body',
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $response_code !== 200 ) {
            return new \WP_Error(
                'huraii_api_error',
                isset( $response_body['message'] ) ? $response_body['message'] : __( 'Error communicating with HURAII API.', 'vortex-ai-agents' )
            );
        }

        // Process and store the generated image
        $description = sprintf(
            __( 'Style transfer: %s applied to source image', 'vortex-ai-agents' ),
            $style_reference
        );

        $processed_results = array(
            'source' => $source_image_url,
            'style' => $style_reference,
            'options' => $options,
            'created_at' => current_time( 'mysql' ),
        );

        if ( isset( $response_body['image']['url'] ) ) {
            $image_id = $this->save_remote_image_to_media_library(
                $response_body['image']['url'],
                'style_transfer_' . sanitize_title( $style_reference ),
                $description
            );
            
            if ( ! is_wp_error( $image_id ) ) {
                $processed_results['image'] = array(
                    'id'  => $image_id,
                    'url' => wp_get_attachment_url( $image_id ),
                );
            }
        } elseif ( isset( $response_body['image']['base64'] ) ) {
            $image_id = $this->save_base64_image_to_media_library(
                $response_body['image']['base64'],
                'style_transfer_' . sanitize_title( $style_reference ),
                $description
            );
            
            if ( ! is_wp_error( $image_id ) ) {
                $processed_results['image'] = array(
                    'id'  => $image_id,
                    'url' => wp_get_attachment_url( $image_id ),
                );
            }
        }

        // Cache the results
        $this->cache_service->set( $cache_key, $processed_results );

        return $processed_results;
    }

    /**
     * Generate artwork series based on a theme
     *
     * @param string $theme Theme or concept for the series.
     * @param int    $count Number of images to generate.
     * @param array  $options Additional options.
     * @return array|WP_Error Generation results or error
     */
    public function generate_series( $theme, $count = 3, $options = array() ) {
        // Check if API key is configured
        if ( empty( $this->api_key ) ) {
            return new \WP_Error(
                'huraii_api_not_configured',
                __( 'HURAII API is not properly configured.', 'vortex-ai-agents' )
            );
        }

        $default_options = array(
            'style' => '',
            'artist_influence' => '',
            'medium' => '',
            'resolution' => '1024x1024',
            'coherence_level' => 0.8, // How stylistically coherent the series should be (0.0 to 1.0)
        );

        $options = wp_parse_args( $options, $default_options );

        // Limit count to reasonable number
        $count = min( max( 2, intval( $count ) ), 10 );

        // Generate cache key
        $cache_key = md5( $theme . $count . wp_json_encode( $options ) );
        $cached_result = $this->cache_service->get( $cache_key );

        if ( $cached_result ) {
            return $cached_result;
        }

        // Prepare request to HURAII API
        $request_body = array(
            'theme' => $theme,
            'count' => $count,
            'coherence_level' => floatval( $options['coherence_level'] ),
            'resolution' => $options['resolution'],
            'tier' => $this->tier,
        );

        // Add optional parameters if provided
        if ( ! empty( $options['style'] ) ) {
            $request_body['style'] = $options['style'];
        }

        if ( ! empty( $options['artist_influence'] ) ) {
            $request_body['artist_influence'] = $options['artist_influence'];
        }

        if ( ! empty( $options['medium'] ) ) {
            $request_body['medium'] = $options['medium'];
        }

        $response = wp_remote_post(
            $this->api_endpoint . '/series',
            array(
                'headers'     => array(
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $this->api_key,
                ),
                'body'        => wp_json_encode( $request_body ),
                'timeout'     => 120, // Longer timeout for series generation
                'data_format' => 'body',
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $response_code !== 200 ) {
            return new \WP_Error(
                'huraii_api_error',
                isset( $response_body['message'] ) ? $response_body['message'] : __( 'Error communicating with HURAII API.', 'vortex-ai-agents' )
            );
        }

        // Process and store the generated images
        $processed_results = array(
            'theme' => $theme,
            'options' => $options,
            'series_id' => isset( $response_body['series_id'] ) ? $response_body['series_id'] : uniqid( 'series_' ),
            'images' => array(),
            'created_at' => current_time( 'mysql' ),
        );

        if ( isset( $response_body['images'] ) && is_array( $response_body['images'] ) ) {
            foreach ( $response_body['images'] as $index => $image_data ) {
                $description = sprintf(
                    __( 'Series "%s" - Image %d', 'vortex-ai-agents' ),
                    $theme,
                    $index + 1
                );
                
                if ( isset( $image_data['url'] ) ) {
                    $image_id = $this->save_remote_image_to_media_library(
                        $image_data['url'],
                        'series_' . sanitize_title( $theme ) . '_' . $index,
                        $description
                    );
                    
                    if ( ! is_wp_error( $image_id ) ) {
                        $processed_results['images'][] = array(
                            'id'  => $image_id,
                            'url' => wp_get_attachment_url( $image_id ),
                            'title' => isset( $image_data['title'] ) ? $image_data['title'] : '',
                            'description' => isset( $image_data['description'] ) ? $image_data['description'] : '',
                        );
                    }
                }
            }
        }

        // Cache the results
        $this->cache_service->set( $cache_key, $processed_results );

        return $processed_results;
    }
} 