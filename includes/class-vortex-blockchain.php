<?php
/**
 * Blockchain integration for VORTEX AI Marketplace
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 * @author     Marianne Nems
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Blockchain integration class.
 *
 * This class integrates blockchain functionality with the VORTEX AI Marketplace.
 *
 * @since      1.0.0
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 * @author     Marianne Nems
 */
class Vortex_Blockchain {

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
     * TOLA token instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      Vortex_TOLA    $tola    The TOLA token instance.
     */
    private $tola;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name       The name of this plugin.
     * @param    string    $version           The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        $this->load_dependencies();
    }

    /**
     * Load dependencies for blockchain functionality.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        // Load TOLA token integration
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/blockchain/class-vortex-tola.php';
        
        // Initialize TOLA instance
        $this->tola = new Vortex_TOLA();
    }

    /**
     * Initialize blockchain functionality.
     *
     * @since    1.0.0
     */
    public function initialize_blockchain() {
        // Register AJAX handlers for wallet address
        add_action('wp_ajax_vortex_save_wallet_address', array($this, 'ajax_save_wallet_address'));
        
        // Add meta boxes for blockchain-related fields
        add_action('add_meta_boxes', array($this, 'add_blockchain_meta_boxes'));
        
        // Save blockchain metadata when posts are saved
        add_action('save_post_vortex-artwork', array($this, 'save_blockchain_metadata'));
        
        // Filter content to add blockchain information
        add_filter('the_content', array($this, 'add_blockchain_info_to_content'));
    }

    /**
     * Ajax handler for saving wallet address.
     *
     * @since    1.0.0
     */
    public function ajax_save_wallet_address() {
        // Verify nonce
        check_ajax_referer('vortex_tola_nonce', 'nonce');
        
        // Get parameters
        $wallet_address = isset($_POST['wallet_address']) ? sanitize_text_field($_POST['wallet_address']) : '';
        
        if (empty($wallet_address)) {
            wp_send_json_error(array('message' => 'Wallet address is required'));
            return;
        }
        
        // Check if user is logged in
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(array('message' => 'User must be logged in'));
            return;
        }
        
        // Save wallet address to user meta
        update_user_meta($user_id, 'vortex_wallet_address', $wallet_address);
        
        // Initialize TOLA balance if not exists
        $balance = get_user_meta($user_id, 'vortex_tola_balance', true);
        if ($balance === '') {
            update_user_meta($user_id, 'vortex_tola_balance', 0);
        }
        
        wp_send_json_success(array('message' => 'Wallet address saved successfully'));
    }

    /**
     * Add meta boxes for blockchain-related fields.
     *
     * @since    1.0.0
     */
    public function add_blockchain_meta_boxes() {
        add_meta_box(
            'vortex_blockchain_metadata',
            __('Blockchain Information', 'vortex-ai-marketplace'),
            array($this, 'render_blockchain_meta_box'),
            'vortex-artwork',
            'side',
            'high'
        );
    }

    /**
     * Render the blockchain meta box.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_blockchain_meta_box($post) {
        // Retrieve current values
        $token_id = get_post_meta($post->ID, '_vortex_token_id', true);
        $contract_address = get_post_meta($post->ID, '_vortex_contract_address', true);
        $token_price = get_post_meta($post->ID, '_vortex_token_price', true);
        $royalty_percentage = get_post_meta($post->ID, '_vortex_royalty_percentage', true);
        
        // Add nonce for security
        wp_nonce_field('vortex_blockchain_metadata', 'vortex_blockchain_nonce');
        
        // Output fields
        ?>
        <p>
            <label for="vortex_token_id"><?php _e('Token ID:', 'vortex-ai-marketplace'); ?></label>
            <input type="text" id="vortex_token_id" name="vortex_token_id" value="<?php echo esc_attr($token_id); ?>" class="widefat">
        </p>
        <p>
            <label for="vortex_contract_address"><?php _e('Contract Address:', 'vortex-ai-marketplace'); ?></label>
            <input type="text" id="vortex_contract_address" name="vortex_contract_address" value="<?php echo esc_attr($contract_address); ?>" class="widefat">
        </p>
        <p>
            <label for="vortex_token_price"><?php _e('Price (TOLA):', 'vortex-ai-marketplace'); ?></label>
            <input type="text" id="vortex_token_price" name="vortex_token_price" value="<?php echo esc_attr($token_price); ?>" class="widefat">
        </p>
        <p>
            <label for="vortex_royalty_percentage"><?php _e('Royalty Percentage:', 'vortex-ai-marketplace'); ?></label>
            <input type="number" id="vortex_royalty_percentage" name="vortex_royalty_percentage" value="<?php echo esc_attr($royalty_percentage); ?>" class="widefat" min="0" max="100" step="0.1">
            <span class="description"><?php _e('Percentage of secondary sales that goes to the artist (0-100%)', 'vortex-ai-marketplace'); ?></span>
        </p>
        <?php
    }

    /**
     * Save blockchain metadata when a post is saved.
     *
     * @since    1.0.0
     * @param    int    $post_id    The ID of the post being saved.
     */
    public function save_blockchain_metadata($post_id) {
        // Check if nonce is set
        if (!isset($_POST['vortex_blockchain_nonce'])) {
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['vortex_blockchain_nonce'], 'vortex_blockchain_metadata')) {
            return;
        }
        
        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save the metadata
        if (isset($_POST['vortex_token_id'])) {
            update_post_meta($post_id, '_vortex_token_id', sanitize_text_field($_POST['vortex_token_id']));
        }
        
        if (isset($_POST['vortex_contract_address'])) {
            update_post_meta($post_id, '_vortex_contract_address', sanitize_text_field($_POST['vortex_contract_address']));
        }
        
        if (isset($_POST['vortex_token_price'])) {
            update_post_meta($post_id, '_vortex_token_price', sanitize_text_field($_POST['vortex_token_price']));
        }
        
        if (isset($_POST['vortex_royalty_percentage'])) {
            update_post_meta($post_id, '_vortex_royalty_percentage', sanitize_text_field($_POST['vortex_royalty_percentage']));
        }
    }

    /**
     * Add blockchain information to content.
     *
     * @since    1.0.0
     * @param    string    $content    The content of the post.
     * @return   string                The modified content.
     */
    public function add_blockchain_info_to_content($content) {
        global $post;
        
        // Only add for artwork post type and on single pages
        if (!is_singular('vortex-artwork') || !is_main_query()) {
            return $content;
        }
        
        // Get blockchain information
        $token_id = get_post_meta($post->ID, '_vortex_token_id', true);
        $contract_address = get_post_meta($post->ID, '_vortex_contract_address', true);
        $token_price = get_post_meta($post->ID, '_vortex_token_price', true);
        $royalty_percentage = get_post_meta($post->ID, '_vortex_royalty_percentage', true);
        
        // Only add if we have blockchain information
        if (empty($token_id) && empty($contract_address)) {
            return $content;
        }
        
        // Build blockchain information HTML
        $blockchain_info = '<div class="vortex-blockchain-info">';
        $blockchain_info .= '<h3>' . __('Blockchain Information', 'vortex-ai-marketplace') . '</h3>';
        $blockchain_info .= '<ul>';
        
        if (!empty($token_id)) {
            $blockchain_info .= '<li><strong>' . __('Token ID:', 'vortex-ai-marketplace') . '</strong> ' . esc_html($token_id) . '</li>';
        }
        
        if (!empty($contract_address)) {
            $blockchain_info .= '<li><strong>' . __('Contract Address:', 'vortex-ai-marketplace') . '</strong> ' . esc_html($contract_address) . '</li>';
        }
        
        if (!empty($token_price)) {
            $blockchain_info .= '<li><strong>' . __('Price:', 'vortex-ai-marketplace') . '</strong> ' . esc_html($token_price) . ' TOLA</li>';
        }
        
        if (!empty($royalty_percentage)) {
            $blockchain_info .= '<li><strong>' . __('Royalty:', 'vortex-ai-marketplace') . '</strong> ' . esc_html($royalty_percentage) . '%</li>';
        }
        
        $blockchain_info .= '</ul>';
        
        // Add purchase button if logged in
        if (is_user_logged_in() && !empty($token_price)) {
            $blockchain_info .= '<div class="vortex-purchase-button-container">';
            $blockchain_info .= '<button class="vortex-purchase-button" data-artwork-id="' . esc_attr($post->ID) . '" data-price="' . esc_attr($token_price) . '">' . __('Purchase with TOLA', 'vortex-ai-marketplace') . '</button>';
            $blockchain_info .= '<div class="vortex-purchase-result"></div>';
            $blockchain_info .= '</div>';
        } else if (!is_user_logged_in() && !empty($token_price)) {
            $login_url = wp_login_url(get_permalink());
            $blockchain_info .= '<div class="vortex-purchase-login-notice">';
            $blockchain_info .= sprintf(__('Please <a href="%s">log in</a> to purchase this artwork.', 'vortex-ai-marketplace'), esc_url($login_url));
            $blockchain_info .= '</div>';
        }
        
        $blockchain_info .= '</div>';
        
        // Append to content
        return $content . $blockchain_info;
    }
} 