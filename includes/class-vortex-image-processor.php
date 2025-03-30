<?php
/**
 * The image processing functionality of the plugin.
 *
 * @link       https://vortexartec.com
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

/**
 * The image processing functionality of the plugin.
 *
 * Handles all image manipulation, optimization, watermarking, and variant
 * creation for artworks in the marketplace.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_Image_Processor {

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
     * Image processing options.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $options    Image processing options.
     */
    private $options;

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
        
        // Load image processing options
        $this->options = get_option( 'vortex_image_processor_options', array(
            'enable_watermark' => true,
            'watermark_opacity' => 30,
            'watermark_position' => 'bottom-right',
            'watermark_size' => 20,
            'enable_optimization' => true,
            'jpeg_quality' => 85,
            'max_width' => 2000,
            'max_height' => 2000,
            'create_thumbnails' => true,
            'thumbnail_sizes' => array(
                'small' => array(300, 300),
                'medium' => array(600, 600),
                'large' => array(1200, 1200)
            ),
            'upscaling_enabled' => true,
            'allowed_formats' => array('jpg', 'jpeg', 'png', 'webp'),
            'convert_to_webp' => false,
        ) );
        
        // Initialize hooks
        $this->init_hooks();
    }

    /**
     * Register all image processing related hooks.
     *
     * @since    1.0.0
     * @access   private
     */
    private function init_hooks() {
        // Handle image uploads
        add_filter( 'wp_handle_upload', array( $this, 'process_uploaded_image' ) );
        
        // Process AI-generated images
        add_action( 'vortex_huraii_image_generated', array( $this, 'process_ai_generated_image' ), 10, 2 );
        
        // Add image processing options to admin
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        
        // AJAX handlers for image processing
        add_action( 'wp_ajax_vortex_process_image', array( $this, 'ajax_process_image' ) );
        add_action( 'wp_ajax_vortex_regenerate_thumbnails', array( $this, 'ajax_regenerate_thumbnails' ) );
        add_action( 'wp_ajax_vortex_add_watermark', array( $this, 'ajax_add_watermark' ) );
        
        // Add custom image sizes for artworks
        add_action( 'after_setup_theme', array( $this, 'add_custom_image_sizes' ) );
        
        // Serve WebP images if enabled
        if ( $this->options['convert_to_webp'] ) {
            add_filter( 'wp_get_attachment_image_src', array( $this, 'serve_webp_image' ), 10, 4 );
        }
    }

    /**
     * Register image processor settings.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        register_setting(
            'vortex_image_processor_options',
            'vortex_image_processor_options',
            array( $this, 'validate_options' )
        );
    }

    /**
     * Validate image processor options.
     *
     * @since    1.0.0
     * @param    array    $input    The options array.
     * @return   array              The validated options array.
     */
    public function validate_options( $input ) {
        $output = array();
        
        // Validate boolean options
        $output['enable_watermark'] = isset( $input['enable_watermark'] ) && $input['enable_watermark'];
        $output['enable_optimization'] = isset( $input['enable_optimization'] ) && $input['enable_optimization'];
        $output['create_thumbnails'] = isset( $input['create_thumbnails'] ) && $input['create_thumbnails'];
        $output['upscaling_enabled'] = isset( $input['upscaling_enabled'] ) && $input['upscaling_enabled'];
        $output['convert_to_webp'] = isset( $input['convert_to_webp'] ) && $input['convert_to_webp'];
        
        // Validate numeric options
        $output['watermark_opacity'] = isset( $input['watermark_opacity'] ) ? 
            max( 0, min( 100, intval( $input['watermark_opacity'] ) ) ) : 30;
        
        $output['watermark_size'] = isset( $input['watermark_size'] ) ? 
            max( 5, min( 50, intval( $input['watermark_size'] ) ) ) : 20;
        
        $output['jpeg_quality'] = isset( $input['jpeg_quality'] ) ? 
            max( 1, min( 100, intval( $input['jpeg_quality'] ) ) ) : 85;
        
        $output['max_width'] = isset( $input['max_width'] ) ? 
            max( 800, min( 4000, intval( $input['max_width'] ) ) ) : 2000;
        
        $output['max_height'] = isset( $input['max_height'] ) ? 
            max( 800, min( 4000, intval( $input['max_height'] ) ) ) : 2000;
        
        // Validate select options
        $valid_positions = array( 'top-left', 'top-center', 'top-right', 'middle-left', 'middle-center', 'middle-right', 'bottom-left', 'bottom-center', 'bottom-right' );
        $output['watermark_position'] = isset( $input['watermark_position'] ) && in_array( $input['watermark_position'], $valid_positions ) ? 
            $input['watermark_position'] : 'bottom-right';
        
        // Validate array options
        $valid_formats = array( 'jpg', 'jpeg', 'png', 'webp', 'gif' );
        $output['allowed_formats'] = isset( $input['allowed_formats'] ) && is_array( $input['allowed_formats'] ) ? 
            array_intersect( $input['allowed_formats'], $valid_formats ) : array( 'jpg', 'jpeg', 'png', 'webp' );
        
        // Validate thumbnail sizes
        if ( isset( $input['thumbnail_sizes'] ) && is_array( $input['thumbnail_sizes'] ) ) {
            $output['thumbnail_sizes'] = array();
            foreach ( $input['thumbnail_sizes'] as $name => $dimensions ) {
                if ( is_array( $dimensions ) && count( $dimensions ) === 2 ) {
                    $width = max( 50, min( 2000, intval( $dimensions[0] ) ) );
                    $height = max( 50, min( 2000, intval( $dimensions[1] ) ) );
                    $output['thumbnail_sizes'][$name] = array( $width, $height );
                }
            }
        } else {
            $output['thumbnail_sizes'] = array(
                'small' => array( 300, 300 ),
                'medium' => array( 600, 600 ),
                'large' => array( 1200, 1200 )
            );
        }
        
        return $output;
    }

    /**
     * Add custom image sizes for artworks.
     *
     * @since    1.0.0
     */
    public function add_custom_image_sizes() {
        if ( isset( $this->options['thumbnail_sizes'] ) && is_array( $this->options['thumbnail_sizes'] ) ) {
            foreach ( $this->options['thumbnail_sizes'] as $name => $dimensions ) {
                add_image_size( 'vortex-' . $name, $dimensions[0], $dimensions[1], false );
            }
        }
        
        // Add featured artwork sizes
        add_image_size( 'vortex-featured', 800, 800, false );
        add_image_size( 'vortex-gallery', 400, 400, true );
        add_image_size( 'vortex-social', 1200, 630, true );
    }

    /**
     * Process an uploaded image.
     *
     * @since    1.0.0
     * @param    array    $file    The uploaded file information.
     * @return   array             The processed file information.
     */
    public function process_uploaded_image( $file ) {
        // Only process image files
        if ( ! preg_match( '!\.(jpg|jpeg|png|gif|webp)$!i', $file['file'] ) ) {
            return $file;
        }
        
        // Check if we should process this upload
        if ( ! isset( $_REQUEST['vortex_process_image'] ) || ! $_REQUEST['vortex_process_image'] ) {
            return $file;
        }
        
        // Perform image processing
        try {
            // Optimize if enabled
            if ( $this->options['enable_optimization'] ) {
                $this->optimize_image( $file['file'] );
            }
            
            // Add watermark if enabled
            if ( $this->options['enable_watermark'] ) {
                $this->add_watermark( $file['file'] );
            }
            
            // Resize to max dimensions if needed
            $this->resize_image( $file['file'], $this->options['max_width'], $this->options['max_height'] );
            
        } catch ( Exception $e ) {
            // Log error but don't prevent upload
            error_log( 'VORTEX Image Processing Error: ' . $e->getMessage() );
        }
        
        return $file;
    }

    /**
     * Process an AI-generated image.
     *
     * @since    1.0.0
     * @param    string    $image_path    Path to the generated image.
     * @param    array     $metadata      Metadata for the generated image.
     * @return   string                   Path to the processed image.
     */
    public function process_ai_generated_image( $image_path, $metadata ) {
        // Verify the image exists
        if ( ! file_exists( $image_path ) ) {
            return $image_path;
        }
        
        try {
            // Optimize the image
            $this->optimize_image( $image_path );
            
            // Add watermark if enabled for AI images
            if ( $this->options['enable_watermark'] && isset( $metadata['add_watermark'] ) && $metadata['add_watermark'] ) {
                $this->add_watermark( $image_path, $metadata['artist_name'] ?? null );
            }
            
            // Create thumbnails if needed
            if ( $this->options['create_thumbnails'] ) {
                $this->create_image_variants( $image_path );
            }
            
            // Convert to WebP if enabled
            if ( $this->options['convert_to_webp'] ) {
                $webp_path = $this->convert_to_webp( $image_path );
                if ( $webp_path ) {
                    return $webp_path;
                }
            }
            
        } catch ( Exception $e ) {
            error_log( 'VORTEX AI Image Processing Error: ' . $e->getMessage() );
        }
        
        return $image_path;
    }

    /**
     * Optimize an image for web.
     *
     * @since    1.0.0
     * @param    string    $file_path    Path to the image file.
     * @return   bool                    Whether optimization was successful.
     */
    public function optimize_image( $file_path ) {
        // Verify the file exists
        if ( ! file_exists( $file_path ) ) {
            return false;
        }
        
        // Get image info
        $image_info = getimagesize( $file_path );
        if ( ! $image_info ) {
            return false;
        }
        
        // Create image resource based on type
        $image = null;
        switch ( $image_info[2] ) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg( $file_path );
                break;
            
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng( $file_path );
                break;
            
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif( $file_path );
                break;
            
            case IMAGETYPE_WEBP:
                $image = imagecreatefromwebp( $file_path );
                break;
            
            default:
                return false;
        }
        
        if ( ! $image ) {
            return false;
        }
        
        // Preserve transparency for PNG and GIF
        if ( $image_info[2] === IMAGETYPE_PNG || $image_info[2] === IMAGETYPE_GIF ) {
            imagealphablending( $image, false );
            imagesavealpha( $image, true );
        }
        
        // Save the optimized image
        $result = false;
        switch ( $image_info[2] ) {
            case IMAGETYPE_JPEG:
                $result = imagejpeg( $image, $file_path, $this->options['jpeg_quality'] );
                break;
            
            case IMAGETYPE_PNG:
                $result = imagepng( $image, $file_path, 9 ); // Max compression
                break;
            
            case IMAGETYPE_GIF:
                $result = imagegif( $image, $file_path );
                break;
            
            case IMAGETYPE_WEBP:
                $result = imagewebp( $image, $file_path, $this->options['jpeg_quality'] );
                break;
        }
        
        // Free memory
        imagedestroy( $image );
        
        return $result;
    }

    /**
     * Add a watermark to an image.
     *
     * @since    1.0.0
     * @param    string    $file_path      Path to the image file.
     * @param    string    $artist_name    Optional artist name for text watermark.
     * @return   bool                      Whether watermarking was successful.
     */
    public function add_watermark( $file_path, $artist_name = null ) {
        // Verify the file exists
        if ( ! file_exists( $file_path ) ) {
            return false;
        }
        
        // Get image info
        $image_info = getimagesize( $file_path );
        if ( ! $image_info ) {
            return false;
        }
        
        // Create image resource based on type
        $image = null;
        switch ( $image_info[2] ) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg( $file_path );
                break;
            
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng( $file_path );
                break;
            
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif( $file_path );
                break;
            
            case IMAGETYPE_WEBP:
                $image = imagecreatefromwebp( $file_path );
                break;
            
            default:
                return false;
        }
        
        if ( ! $image ) {
            return false;
        }
        
        // Preserve transparency for PNG and GIF
        if ( $image_info[2] === IMAGETYPE_PNG || $image_info[2] === IMAGETYPE_GIF ) {
            imagealphablending( $image, true );
            imagesavealpha( $image, true );
        }
        
        // Decide on watermark type - image or text
        $watermark_image_path = plugin_dir_path( dirname( __FILE__ ) ) . 'assets/images/vortex-watermark.png';
        
        if ( file_exists( $watermark_image_path ) ) {
            // Image watermark
            $watermark = imagecreatefrompng( $watermark_image_path );
            
            // Get dimensions
            $watermark_width = imagesx( $watermark );
            $watermark_height = imagesy( $watermark );
            
            // Calculate new watermark size based on settings
            $new_watermark_width = (int) ($image_info[0] * ($this->options['watermark_size'] / 100));
            $new_watermark_height = (int) ($watermark_height * ($new_watermark_width / $watermark_width));
            
            // Create resized watermark
            $resized_watermark = imagecreatetruecolor( $new_watermark_width, $new_watermark_height );
            imagealphablending( $resized_watermark, false );
            imagesavealpha( $resized_watermark, true );
            imagecopyresampled( $resized_watermark, $watermark, 0, 0, 0, 0, $new_watermark_width, $new_watermark_height, $watermark_width, $watermark_height );
            
            // Calculate position
            $position = $this->calculate_watermark_position( 
                $image_info[0], 
                $image_info[1], 
                $new_watermark_width, 
                $new_watermark_height, 
                $this->options['watermark_position'] 
            );
            
            // Apply watermark with opacity
            $this->apply_watermark_with_opacity( 
                $image, 
                $resized_watermark, 
                $position['x'], 
                $position['y'], 
                $this->options['watermark_opacity'] 
            );
            
            // Clean up
            imagedestroy( $watermark );
            imagedestroy( $resized_watermark );
            
        } else {
            // Text watermark
            $watermark_text = $artist_name ? "© " . $artist_name : "© VORTEX";
            $font_size = (int) ($image_info[0] * ($this->options['watermark_size'] / 500));
            $font_path = plugin_dir_path( dirname( __FILE__ ) ) . 'assets/fonts/OpenSans-Bold.ttf';
            
            // Use default font if custom font is not available
            if ( ! file_exists( $font_path ) ) {
                $font_path = 5; // Built-in font
            }
            
            // Calculate text dimensions and position
            if ( $font_path !== 5 ) {
                $text_box = imagettfbbox( $font_size, 0, $font_path, $watermark_text );
                $text_width = $text_box[2] - $text_box[0];
                $text_height = $text_box[1] - $text_box[7];
            } else {
                $text_width = imagefontwidth( $font_path ) * strlen( $watermark_text );
                $text_height = imagefontheight( $font_path );
            }
            
            $position = $this->calculate_watermark_position( 
                $image_info[0], 
                $image_info[1], 
                $text_width, 
                $text_height, 
                $this->options['watermark_position'] 
            );
            
            // Create text color with alpha
            $opacity = 127 - (int) (127 * ($this->options['watermark_opacity'] / 100));
            $text_color = imagecolorallocatealpha( $image, 255, 255, 255, $opacity );
            
            // Draw text
            if ( $font_path !== 5 ) {
                imagettftext( $image, $font_size, 0, $position['x'], $position['y'] + $text_height, $text_color, $font_path, $watermark_text );
            } else {
                imagestring( $image, $font_path, $position['x'], $position['y'], $watermark_text, $text_color );
            }
        }
        
        // Save the watermarked image
        $result = false;
        switch ( $image_info[2] ) {
            case IMAGETYPE_JPEG:
                $result = imagejpeg( $image, $file_path, $this->options['jpeg_quality'] );
                break;
            
            case IMAGETYPE_PNG:
                $result = imagepng( $image, $file_path, 9 ); // Max compression
                break;
            
            case IMAGETYPE_GIF:
                $result = imagegif( $image, $file_path );
                break;
            
            case IMAGETYPE_WEBP:
                $result = imagewebp( $image, $file_path, $this->options['jpeg_quality'] );
                break;
        }
        
        // Free memory
        imagedestroy( $image );
        
        return $result;
    }

    /**
     * Calculate watermark position based on chosen position.
     *
     * @since    1.0.0
     * @param    int       $image_width       Image width.
     * @param    int       $image_height      Image height.
     * @param    int       $watermark_width   Watermark width.
     * @param    int       $watermark_height  Watermark height.
     * @param    string    $position          Position identifier.
     * @return   array                        Position coordinates.
     */
    private function calculate_watermark_position( $image_width, $image_height, $watermark_width, $watermark_height, $position ) {
        $padding = 10; // Padding from edges
        
        switch ( $position ) {
            case 'top-left':
                return array(
                    'x' => $padding,
                    'y' => $padding
                );
            
            case 'top-center':
                return array(
                    'x' => (int) (($image_width - $watermark_width) / 2),
                    'y' => $padding
                );
            
            case 'top-right':
                return array(
                    'x' => $image_width - $watermark_width - $padding,
                    'y' => $padding
                );
            
            case 'middle-left':
                return array(
                    'x' => $padding,
                    'y' => (int) (($image_height - $watermark_height) / 2)
                );
            
            case 'middle-center':
                return array(
                    'x' => (int) (($image_width - $watermark_width) / 2),
                    'y' => (int) (($image_height - $watermark_height) / 2)
                );
            
            case 'middle-right':
                return array(
                    'x' => $image_width - $watermark_width - $padding,
                    'y' => (int) (($image_height - $watermark_height) / 2)
                );
            
            case 'bottom-left':
                return array(
                    'x' => $padding,
                    'y' => $image_height - $watermark_height - $padding
                );
            
            case 'bottom-center':
                return array(
                    'x' => (int) (($image_width - $watermark_width) / 2),
                    'y' => $image_height - $watermark_height - $padding
                );
            
            case 'bottom-right':
            default:
                return array(
                    'x' => $image_width - $watermark_width - $padding,
                    'y' => $image_height - $watermark_height - $padding
                );
        }
    }

    /**
     * Apply watermark with opacity.
     *
     * @since    1.0.0
     * @param    resource  $image        Base image resource.
     * @param    resource  $watermark    Watermark image resource.
     * @param    int       $dest_x       Destination X coordinate.
     * @param    int       $dest_y       Destination Y coordinate.
     * @param    int       $opacity      Opacity percentage (0-100).
     */
    private function apply_watermark_with_opacity( $image, $watermark, $dest_x, $dest_y, $opacity ) {
        $watermark_width = imagesx( $watermark );
        $watermark_height = imagesy( $watermark );
        
        // Create temporary alpha layer image for opacity handling
        $alpha = min( 127, 127 * (1 - $opacity / 100) );
        
        // Copy watermark with specified opacity
        imagecopymerge( $image, $watermark, $dest_x, $dest_y, 0, 0, $watermark_width, $watermark_height, $opacity );
    }

    /**
     * Resize an image to specified dimensions.
     *
     * @since    1.0.0
     * @param    string    $file_path    Path to the image file.
     * @param    int       $max_width    Maximum width in pixels.
     * @param    int       $max_height   Maximum height in pixels.
     * @return   bool                    Whether resize was successful.
     */
    public function resize_image( $file_path, $max_width, $max_height ) {
        // Verify the file exists
        if ( ! file_exists( $file_path ) ) {
            return false;
        }
        
        // Get image info
        $image_info = getimagesize( $file_path );
        if ( ! $image_info ) {
            return false;
        }
        
        // Check if resize is needed
        if ( $image_info[0] <= $max_width && $image_info[1] <= $max_height ) {
            return true; // No need to resize
        }
        
        // Create image resource based on type
        $image = null;
        switch ( $image_info[2] ) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg( $file_path );
                break;
            
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng( $file_path );
                break;
            
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif( $file_path );
                break;
            
            case IMAGETYPE_WEBP:
                $image = imagecreatefromwebp( $file_path );
                break;
            
            default:
                return false;
        }
        
        if ( ! $image ) {
            return false;
        }
        
        // Calculate new dimensions
        $width = $image_info[0];
        $height = $image_info[1];
        
        $ratio = min( $max_width / $width, $max_height / $height );
        $new_width = (int) ($width * $ratio);
        $new_height = (int) ($height * $ratio);
        
        // Create new image
        $new_image = imagecreatetruecolor( $new_width, $new_height );
        
        // Preserve transparency for PNG and GIF
        if ( $image_info[2] === IMAGETYPE_PNG || $image_info[2] === IMAGETYPE_GIF ) {
            imagealphablending( $new_image, false );
            imagesavealpha( $new_image, true );
            $transparent = imagecolorallocatealpha( $new_image, 255, 255, 255, 127 );
            imagefilledrectangle( $new_image, 0, 0, $new_width, $new_height, $transparent );
        }
        
        // Resize image
        imagecopyresampled( $new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height );
        
        // Save the resized image
        $result = false;
        switch ( $image_info[2] ) {
            case IMAGETYPE_JPEG:
                $result = imagejpeg( $new_image, $file_path, $this->options['jpeg_quality'] );
                break;
            
            case IMAGETYPE_PNG:
                $result = imagepng( $new_image, $file_path, 9 ); // Max compression
                break;
            
            case IMAGETYPE_GIF:
                $result = imagegif( $new_image, $file_path );
                break;
            
            case IMAGETYPE_WEBP:
                $result = imagewebp( $new_image, $file_path, $this->options['jpeg_quality'] );
                break;
        }
        
        // Free memory
        imagedestroy( $image );
        imagedestroy( $new_image );
        
        return $result;
    }

    /**
     * Create multiple variants of an image.
     *
     * @since    1.0.0
     * @param    string    $file_path    Path to the image file.
     * @return   array                   Paths to variant images.
     */
    public function create_image_variants( $file_path ) {
        // Verify the file exists
        if ( ! file_exists( $file_path ) ) {
            return array();
        }
        
        $variants = array();
        $path_info = pathinfo( $file_path );
        
        // Create variants for each size in options
        if ( isset( $this->options['thumbnail_sizes'] ) && is_array( $this->options['thumbnail_sizes'] ) ) {
            foreach ( $this->options['thumbnail_sizes'] as $name => $dimensions ) {
                $variant_path = $path_info['dirname'] . '/' . $path_info['filename'] . '-' . $name . '.' . $path_info['extension'];
                
                // Copy original file to variant
                copy( $file_path, $variant_path );
                
                // Resize the variant
                $this->resize_image( $variant_path, $dimensions[0], $dimensions[1] );
                
                $variants[$name] = $variant_path;
            }
        }
        
        return $variants;
    }

    /**
     * Convert an image to WebP format.
     *
     * @since    1.0.0
     * @param    string    $file_path    Path to the image file.
     * @return   string|bool             Path to the WebP image or false on failure.
     */
    public function convert_to_webp( $file_path ) {
        // Verify the file exists
        if ( ! file_exists( $file_path ) ) {
            return false;
        }
        
        // Get image info
        $image_info = getimagesize( $file_path );
        if ( ! $image_info ) {
            return false;
        }
        
        // Check if this is already a WebP image
        if ( $image_info[2] === IMAGETYPE_WEBP ) {
            return $file_path;
        }
        
        // Create image resource based on type
        $image = null;
        switch ( $image_info[2] ) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg( $file_path );
                break;
            
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng( $file_path );
                break;
            
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif( $file_path );
                break;
            
            default:
                return false;
        }
        
        if ( ! $image ) {
            return false;
        }
        
        // Preserve transparency for PNG and GIF
        if ( $image_info[2] === IMAGETYPE_PNG || $image_info[2] === IMAGETYPE_GIF ) {
            imagealphablending( $image, false );
            imagesavealpha( $image, true );
        }
        
        // Create WebP output path
        $path_info = pathinfo( $file_path );
        $webp_path = $path_info['dirname'] . '/' . $path_info['filename'] . '.webp';
        
        // Convert to WebP
        $result = imagewebp( $image, $webp_path, $this->options['jpeg_quality'] );
        
        // Free memory
        imagedestroy( $image );
        
        return $result ? $webp_path : false;
    }

    /**
     * Serve WebP image if available.
     *
     * @since    1.0.0
     * @param    array     $image           Image data array.
     * @param    int       $attachment_id   Attachment ID.
     * @param    string    $size            Image size.
     * @param    bool      $icon            Whether it's an icon.
     * @return   array                      Modified image data array.
     */
    public function serve_webp_image( $image, $attachment_id, $size, $icon ) {
        if ( ! $image ) {
            return $image;
        }
        
        // Check browser support via headers
        $supports_webp = strpos( $_SERVER['HTTP_ACCEPT'] ?? '', 'image/webp' ) !== false;
        if ( ! $supports_webp ) {
            return $image;
        }
        
        // Get file path from URL
        $image_url = $image[0];
        $upload_dir = wp_upload_dir();
        $image_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $image_url );
        
        // Check for WebP version
        $path_info = pathinfo( $image_path );
        $webp_path = $path_info['dirname'] . '/' . $path_info['filename'] . '.webp';
        
        if ( file_exists( $webp_path ) ) {
            // Replace URL with WebP version
            $webp_url = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $webp_path );
            $image[0] = $webp_url;
        } else {
            // Create WebP version if it doesn't exist
            if ( file_exists( $image_path ) ) {
                $created_webp = $this->convert_to_webp( $image_path );
                if ( $created_webp ) {
                    $webp_url = str_replace( $upload_dir['basedir'], $upload_dir['baseurl'], $created_webp );
                    $image[0] = $webp_url;
                }
            }
        }
        
        return $image;
    }

    /**
     * AJAX handler for image processing.
     *
     * @since    1.0.0
     */
    public function ajax_process_image() {
        // Check permissions
        if ( ! current_user_can( 'upload_files' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to process images.', 'vortex-ai-marketplace' ) ) );
        }
        
        // Verify nonce
        if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( $_POST['security'], 'vortex_image_processor_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'vortex-ai-marketplace' ) ) );
        }
        
        $attachment_id = isset( $_POST['attachment_id'] ) ? intval( $_POST['attachment_id'] ) : 0;
        
        if ( ! $attachment_id ) {
            wp_send_json_error( array( 'message' => __( 'Invalid attachment ID.', 'vortex-ai-marketplace' ) ) );
        }
        
        $file_path = get_attached_file( $attachment_id );
        
        if ( ! $file_path || ! file_exists( $file_path ) ) {
            wp_send_json_error( array( 'message' => __( 'File not found.', 'vortex-ai-marketplace' ) ) );
        }
        
        // Process operations
        $operations = isset( $_POST['operations'] ) ? (array) $_POST['operations'] : array();
        $results = array();
        
        try {
            // Optimize
            if ( in_array( 'optimize', $operations ) ) {
                $results['optimize'] = $this->optimize_image( $file_path );
            }
            
            // Watermark
            if ( in_array( 'watermark', $operations ) ) {
                $artist_name = isset( $_POST['artist_name'] ) ? sanitize_text_field( $_POST['artist_name'] ) : null;
                $results['watermark'] = $this->add_watermark( $file_path, $artist_name );
            }
            
            // Resize
            if ( in_