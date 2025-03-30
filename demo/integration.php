<?php
/**
 * VORTEX AI AGENTS - Artist Dashboard Demo Integration
 *
 * This file provides easy integration of the artist dashboard demo
 * into the VORTEX AI AGENTS plugin.
 * 
 * @package VortexAiAgents
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register the Artist Demo menu and page
 */
function vortex_register_artist_demo() {
    add_menu_page(
        'Artist Experience Demo',
        'Artist Demo',
        'manage_options',
        'vortex-artist-demo',
        'vortex_render_artist_demo',
        'dashicons-art',
        30
    );
}
add_action( 'admin_menu', 'vortex_register_artist_demo' );

/**
 * Render the Artist Demo page
 */
function vortex_render_artist_demo() {
    // Include the artist experience demo file
    include_once plugin_dir_path( __FILE__ ) . 'artist-experience.php';
}

/**
 * Add a shortcode to embed the artist dashboard on the frontend
 */
function vortex_artist_dashboard_shortcode( $atts ) {
    // Start output buffering
    ob_start();
    
    // Include the artist experience demo file
    include_once plugin_dir_path( __FILE__ ) . 'artist-experience.php';
    
    // Get the buffered content
    $output = ob_get_clean();
    
    return $output;
}
add_shortcode( 'vortex_artist_dashboard', 'vortex_artist_dashboard_shortcode' );

/**
 * Check for required demo image files and create placeholders if missing
 */
function vortex_check_demo_images() {
    // Directory paths
    $img_dir = plugin_dir_path( __FILE__ ) . 'img/';
    $generated_dir = $img_dir . 'generated/';
    
    // Create directories if they don't exist
    if ( ! is_dir( $img_dir ) ) {
        wp_mkdir_p( $img_dir );
    }
    
    if ( ! is_dir( $generated_dir ) ) {
        wp_mkdir_p( $generated_dir );
    }
    
    // Create placeholder image for artist avatar if missing
    $avatar_path = $img_dir . 'artist-avatar.jpg';
    if ( ! file_exists( $avatar_path ) ) {
        vortex_create_placeholder_image( $avatar_path, 'Artist Avatar', 300, 300 );
    }
    
    // Style types and create placeholder generated images if missing
    $styles = array(
        'abstract' => 'Abstract Art',
        'impressionist' => 'Impressionist Art',
        'cubist' => 'Cubist Art',
        'surreal' => 'Surreal Art',
        'minimal' => 'Minimal Art'
    );
    
    foreach ( $styles as $style_key => $style_name ) {
        for ( $i = 1; $i <= 2; $i++ ) {
            $img_path = $generated_dir . $style_key . '-' . $i . '.jpg';
            if ( ! file_exists( $img_path ) ) {
                vortex_create_placeholder_image( $img_path, $style_name . ' ' . $i, 800, 600 );
            }
        }
    }
}
add_action( 'admin_init', 'vortex_check_demo_images' );

/**
 * Create a placeholder image with text
 *
 * @param string $path     File path where the image should be saved
 * @param string $text     Text to display on the placeholder
 * @param int    $width    Image width
 * @param int    $height   Image height
 */
function vortex_create_placeholder_image( $path, $text, $width = 800, $height = 600 ) {
    // Check if GD library is available
    if ( ! function_exists( 'imagecreatetruecolor' ) ) {
        return false;
    }
    
    // Create image
    $image = imagecreatetruecolor( $width, $height );
    
    // Colors
    $bg_color = imagecolorallocate( $image, 78, 122, 169 ); // Primary color
    $text_color = imagecolorallocate( $image, 255, 255, 255 ); // White
    
    // Fill background
    imagefill( $image, 0, 0, $bg_color );
    
    // Add text
    $font_size = 5;
    $font = 5; // Built-in font
    
    // Calculate text position to center it
    $text_box = imagettfbbox( $font_size, 0, $font, $text );
    if ( $text_box ) {
        $text_width = $text_box[2] - $text_box[0];
        $text_height = $text_box[1] - $text_box[7];
        $text_x = ( $width - $text_width ) / 2;
        $text_y = ( $height - $text_height ) / 2;
    } else {
        // Fallback for when imagettfbbox fails
        $text_x = $width / 5;
        $text_y = $height / 2;
    }
    
    // Draw text
    imagestring( $image, $font, $text_x, $text_y, $text, $text_color );
    
    // Ensure directory exists
    $dir = dirname( $path );
    if ( ! is_dir( $dir ) ) {
        wp_mkdir_p( $dir );
    }
    
    // Save image
    imagejpeg( $image, $path );
    
    // Free memory
    imagedestroy( $image );
    
    return true;
}

/**
 * Add a notice about the demo
 */
function vortex_artist_demo_notice() {
    $screen = get_current_screen();
    
    // Only show on main dashboard and plugin screens
    if ( ! in_array( $screen->id, array( 'dashboard', 'plugins' ) ) ) {
        return;
    }
    
    ?>
    <div class="notice notice-info is-dismissible">
        <p>
            <strong>VORTEX AI AGENTS:</strong> 
            Check out the new <a href="<?php echo admin_url( 'admin.php?page=vortex-artist-demo' ); ?>">Artist Dashboard Demo</a> 
            to see HURAII's generative capabilities and TOLA gamification in action!
        </p>
    </div>
    <?php
}
add_action( 'admin_notices', 'vortex_artist_demo_notice' ); 