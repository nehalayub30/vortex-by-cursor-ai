<?php
/**
 * HURAII TOLA Watermark
 *
 * Ensures all HURAII-generated artwork includes appropriate TOLA branding and verification.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

class Vortex_HURAII_Watermark {
    /**
     * The single instance of this class
     */
    private static $instance = null;
    
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
     * Apply TOLA watermark to generated artwork
     */
    public function apply_tola_watermark($image_path, $contract_hash) {
        // Verify input
        if (!file_exists($image_path)) {
            return new WP_Error('invalid_file', __('Image file not found', 'vortex-ai-marketplace'));
        }
        
        // Create image resource based on file type
        $ext = strtolower(pathinfo($image_path, PATHINFO_EXTENSION));
        
        // Create the watermark image (TOLA logo + QR code)
        $watermark = $this->create_tola_watermark($contract_hash);
        if (is_wp_error($watermark)) {
            return $watermark;
        }
        
        // Apply watermark to the original image
        if ($ext == 'png') {
            $image = imagecreatefrompng($image_path);
        } elseif ($ext == 'jpg' || $ext == 'jpeg') {
            $image = imagecreatefromjpeg($image_path);
        } else {
            return new WP_Error('unsupported_format', __('Unsupported image format', 'vortex-ai-marketplace'));
        }
        
        // Get dimensions
        $img_width = imagesx($image);
        $img_height = imagesy($image);
        $watermark_width = imagesx($watermark);
        $watermark_height = imagesy($watermark);
        
        // Calculate position (bottom right with 20px padding)
        $watermark_x = $img_width - $watermark_width - 20;
        $watermark_y = $img_height - $watermark_height - 20;
        
        // Apply watermark
        imagecopy(
            $image,
            $watermark,
            $watermark_x,
            $watermark_y,
            0,
            0,
            $watermark_width,
            $watermark_height
        );
        
        // Save the watermarked image
        if ($ext == 'png') {
            imagepng($image, $image_path);
        } else {
            imagejpeg($image, $image_path, 95);
        }
        
        // Clean up
        imagedestroy($image);
        imagedestroy($watermark);
        
        return true;
    }
    
    /**
     * Create TOLA watermark with QR verification code
     */
    private function create_tola_watermark($contract_hash) {
        // Create the watermark canvas (transparent background)
        $watermark = imagecreatetruecolor(200, 80);
        imagesavealpha($watermark, true);
        $transparent = imagecolorallocatealpha($watermark, 0, 0, 0, 127);
        imagefill($watermark, 0, 0, $transparent);
        
        // Add TOLA text
        $white = imagecolorallocate($watermark, 255, 255, 255);
        $purple = imagecolorallocate($watermark, 74, 38, 171);
        
        // Draw background for text
        imagefilledrectangle($watermark, 0, 0, 120, 80, $purple);
        
        // Add text
        $font_path = plugin_dir_path(dirname(__FILE__)) . 'assets/fonts/OpenSans-Bold.ttf';
        imagettftext($watermark, 14, 0, 10, 30, $white, $font_path, 'TOLA');
        imagettftext($watermark, 8, 0, 10, 50, $white, $font_path, 'Verified Original');
        
        // Add QR code (would normally generate actual QR here)
        $qr_path = $this->generate_qr_code_for_contract($contract_hash);
        if (is_wp_error($qr_path)) {
            return $qr_path;
        }
        
        if (file_exists($qr_path)) {
            $qr_code = imagecreatefrompng($qr_path);
            imagecopy($watermark, $qr_code, 120, 0, 0, 0, 80, 80);
            imagedestroy($qr_code);
            @unlink($qr_path); // Remove temp file
        }
        
        return $watermark;
    }
    
    /**
     * Generate QR code for contract verification
     */
    private function generate_qr_code_for_contract($contract_hash) {
        // Check if we have the PHP QR Code library
        if (!function_exists('QRcode::png')) {
            // Fall back to placeholder method
            return $this->create_placeholder_qr();
        }
        
        $verification_url = site_url('/verify/' . $contract_hash);
        $temp_file = get_temp_dir() . 'tola_qr_' . substr($contract_hash, 0, 8) . '.png';
        
        // Generate QR code
        QRcode::png($verification_url, $temp_file, 'M', 4, 2);
        
        if (!file_exists($temp_file)) {
            return new WP_Error('qr_generation_failed', __('Failed to generate QR code', 'vortex-ai-marketplace'));
        }
        
        return $temp_file;
    }
    
    /**
     * Create a placeholder QR code when library is not available
     */
    private function create_placeholder_qr() {
        $temp_file = get_temp_dir() . 'tola_qr_placeholder.png';
        
        // Create a simple placeholder
        $qr = imagecreatetruecolor(80, 80);
        $white = imagecolorallocate($qr, 255, 255, 255);
        $black = imagecolorallocate($qr, 0, 0, 0);
        
        // Fill with white
        imagefill($qr, 0, 0, $white);
        
        // Draw a simple pattern
        for ($i = 0; $i < 8; $i++) {
            for ($j = 0; $j < 8; $j++) {
                if (($i + $j) % 2 == 0) {
                    imagefilledrectangle($qr, $i*10, $j*10, ($i+1)*10, ($j+1)*10, $black);
                }
            }
        }
        
        // Save the image
        imagepng($qr, $temp_file);
        imagedestroy($qr);
        
        return $temp_file;
    }
} 