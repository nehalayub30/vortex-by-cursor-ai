<?php
/**
 * Handles artwork provenance and authentication
 */
class Vortex_Provenance {
    /**
     * Initialize the provenance system
     */
    public function __construct() {
        add_action('save_post_vortex_artwork', array($this, 'create_provenance_record'), 10, 3);
        add_action('vortex_artwork_transfer', array($this, 'update_provenance_record'), 10, 4);
        add_filter('vortex_artwork_verification', array($this, 'verify_artwork_authenticity'), 10, 2);
        add_shortcode('vortex_artwork_provenance', array($this, 'render_provenance_shortcode'));
        add_action('init', array($this, 'register_provenance_endpoints'));
    }
    
    /**
     * Create initial provenance record when artwork is created
     */
    public function create_provenance_record($post_id, $post, $update) {
        // Skip if this is just a revision or autosave
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id) || $update) {
            return;
        }
        
        // Get artist/creator information
        $creator_id = get_post_meta($post_id, 'vortex_artwork_creator', true);
        if (!$creator_id) {
            $creator_id = get_current_user_id();
        }
        
        // Create blockchain provenance record
        $blockchain = Vortex_AI_Marketplace::get_instance()->blockchain;
        $metadata = array(
            'title' => get_the_title($post_id),
            'creator' => $creator_id,
            'creation_date' => get_the_date('c', $post_id),
            'description' => get_the_content(null, false, $post_id),
            'media_type' => get_post_meta($post_id, 'vortex_artwork_media', true),
            'dimensions' => get_post_meta($post_id, 'vortex_artwork_dimensions', true),
            'hash' => $this->generate_artwork_hash($post_id)
        );
        
        $result = $blockchain->register_provenance($metadata);
        
        if (!is_wp_error($result)) {
            update_post_meta($post_id, 'vortex_provenance_id', $result['provenance_id']);
            update_post_meta($post_id, 'vortex_blockchain_tx', $result['transaction_id']);
            update_post_meta($post_id, 'vortex_provenance_created', current_time('mysql'));
        }
    }
    
    /**
     * Update provenance record when artwork ownership changes
     */
    public function update_provenance_record($artwork_id, $from_user_id, $to_user_id, $transaction_data) {
        // Implementation for tracking ownership changes on the blockchain
    }
    
    /**
     * Generate a unique hash for artwork verification
     */
    private function generate_artwork_hash($post_id) {
        // Get artwork data
        $content = get_the_content(null, false, $post_id);
        $title = get_the_title($post_id);
        $creator = get_post_meta($post_id, 'vortex_artwork_creator', true);
        $creation_date = get_the_date('c', $post_id);
        
        // For digital artworks, include the file hash
        $file_id = get_post_meta($post_id, 'vortex_artwork_file', true);
        $file_hash = '';
        
        if ($file_id) {
            $file_path = get_attached_file($file_id);
            if ($file_path && file_exists($file_path)) {
                $file_hash = hash_file('sha256', $file_path);
            }
        }
        
        // Combine all elements and hash
        $data_to_hash = $title . $content . $creator . $creation_date . $file_hash;
        return hash('sha256', $data_to_hash);
    }
    
    /**
     * Render artwork provenance information via shortcode
     */
    public function render_provenance_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => get_the_ID(),
            'show_qr' => 'true',
        ), $atts, 'vortex_artwork_provenance');
        
        $artwork_id = intval($atts['id']);
        $show_qr = filter_var($atts['show_qr'], FILTER_VALIDATE_BOOLEAN);
        
        if (!$artwork_id) {
            return '<div class="vortex-notice vortex-notice-error">' . 
                   __('Artwork ID not specified', 'vortex-ai-marketplace') . 
                   '</div>';
        }
        
        // Get provenance data
        $provenance_id = get_post_meta($artwork_id, 'vortex_provenance_id', true);
        $blockchain_tx = get_post_meta($artwork_id, 'vortex_blockchain_tx', true);
        
        if (!$provenance_id || !$blockchain_tx) {
            return '<div class="vortex-notice vortex-notice-warning">' . 
                   __('No provenance record available for this artwork', 'vortex-ai-marketplace') . 
                   '</div>';
        }
        
        // Get ownership history
        $ownership_history = $this->get_ownership_history($artwork_id);
        
        ob_start();
        include plugin_dir_path(dirname(__FILE__)) . 'public/partials/artwork-provenance.php';
        return ob_get_clean();
    }
} 