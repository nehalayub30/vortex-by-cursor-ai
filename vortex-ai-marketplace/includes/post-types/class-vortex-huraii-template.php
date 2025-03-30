<?php
/**
 * The file that defines the HURAII Template post type
 *
 * @link       https://github.com/MarianneNems/VORTEX
 * @since      1.0.0
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/post-types
 */

/**
 * The HURAII Template post type class.
 *
 * Defines and registers the HURAII template custom post type for AI art generation.
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/post-types
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */
class Vortex_Huraii_Template {

    /**
     * The post type name.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $post_type    The post type name.
     */
    private $post_type = 'vortex_huraii_template';

    /**
     * Register the custom post type.
     *
     * @since    1.0.0
     */
    public function register() {
        $labels = array(
            'name'               => _x( 'HURAII Templates', 'post type general name', 'vortex-ai-marketplace' ),
            'singular_name'      => _x( 'HURAII Template', 'post type singular name', 'vortex-ai-marketplace' ),
            'menu_name'          => _x( 'HURAII Templates', 'admin menu', 'vortex-ai-marketplace' ),
            'name_admin_bar'     => _x( 'HURAII Template', 'add new on admin bar', 'vortex-ai-marketplace' ),
            'add_new'            => _x( 'Add New', 'template', 'vortex-ai-marketplace' ),
            'add_new_item'       => __( 'Add New HURAII Template', 'vortex-ai-marketplace' ),
            'new_item'           => __( 'New HURAII Template', 'vortex-ai-marketplace' ),
            'edit_item'          => __( 'Edit HURAII Template', 'vortex-ai-marketplace' ),
            'view_item'          => __( 'View HURAII Template', 'vortex-ai-marketplace' ),
            'all_items'          => __( 'All HURAII Templates', 'vortex-ai-marketplace' ),
            'search_items'       => __( 'Search HURAII Templates', 'vortex-ai-marketplace' ),
            'parent_item_colon'  => __( 'Parent HURAII Templates:', 'vortex-ai-marketplace' ),
            'not_found'          => __( 'No templates found.', 'vortex-ai-marketplace' ),
            'not_found_in_trash' => __( 'No templates found in Trash.', 'vortex-ai-marketplace' )
        );

        $args = array(
            'labels'             => $labels,
            'description'        => __( 'AI art generation templates for HURAII', 'vortex-ai-marketplace' ),
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => 'edit.php?post_type=vortex_artwork', // Add as submenu to Artworks
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'huraii-template' ),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
            'show_in_rest'       => true,
        );

        register_post_type( $this->post_type, $args );
        
        // Register taxonomies
        $this->register_taxonomies();
        
        // Register meta boxes
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        
        // Save post meta
        add_action( 'save_post_' . $this->post_type, array( $this, 'save_meta_box_data' ) );
    }
    
    /**
     * Register custom taxonomies for the HURAII template post type
     *
     * @since    1.0.0
     */
    private function register_taxonomies() {
        // AI Style Taxonomy
        $style_labels = array(
            'name'              => _x( 'AI Styles', 'taxonomy general name', 'vortex-ai-marketplace' ),
            'singular_name'     => _x( 'AI Style', 'taxonomy singular name', 'vortex-ai-marketplace' ),
            'search_items'      => __( 'Search AI Styles', 'vortex-ai-marketplace' ),
            'all_items'         => __( 'All AI Styles', 'vortex-ai-marketplace' ),
            'parent_item'       => __( 'Parent AI Style', 'vortex-ai-marketplace' ),
            'parent_item_colon' => __( 'Parent AI Style:', 'vortex-ai-marketplace' ),
            'edit_item'         => __( 'Edit AI Style', 'vortex-ai-marketplace' ),
            'update_item'       => __( 'Update AI Style', 'vortex-ai-marketplace' ),
            'add_new_item'      => __( 'Add New AI Style', 'vortex-ai-marketplace' ),
            'new_item_name'     => __( 'New AI Style Name', 'vortex-ai-marketplace' ),
            'menu_name'         => __( 'AI Styles', 'vortex-ai-marketplace' ),
        );

        $style_args = array(
            'hierarchical'      => true,
            'labels'            => $style_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'ai-style' ),
            'show_in_rest'      => true,
        );

        register_taxonomy( 'ai_style', array( $this->post_type ), $style_args );
        
        // Template Category Taxonomy
        $category_labels = array(
            'name'              => _x( 'Template Categories', 'taxonomy general name', 'vortex-ai-marketplace' ),
            'singular_name'     => _x( 'Template Category', 'taxonomy singular name', 'vortex-ai-marketplace' ),
            'search_items'      => __( 'Search Template Categories', 'vortex-ai-marketplace' ),
            'all_items'         => __( 'All Template Categories', 'vortex-ai-marketplace' ),
            'parent_item'       => __( 'Parent Template Category', 'vortex-ai-marketplace' ),
            'parent_item_colon' => __( 'Parent Template Category:', 'vortex-ai-marketplace' ),
            'edit_item'         => __( 'Edit Template Category', 'vortex-ai-marketplace' ),
            'update_item'       => __( 'Update Template Category', 'vortex-ai-marketplace' ),
            'add_new_item'      => __( 'Add New Template Category', 'vortex-ai-marketplace' ),
            'new_item_name'     => __( 'New Template Category Name', 'vortex-ai-marketplace' ),
            'menu_name'         => __( 'Template Categories', 'vortex-ai-marketplace' ),
        );

        $category_args = array(
            'hierarchical'      => true,
            'labels'            => $category_labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'template-category' ),
            'show_in_rest'      => true,
        );

        register_taxonomy( 'template_category', array( $this->post_type ), $category_args );
    }
    
    /**
     * Add meta boxes for the HURAII template post type
     *
     * @since    1.0.0
     */
    public function add_meta_boxes() {
        add_meta_box(
            'vortex_huraii_template_details',
            __( 'HURAII Template Details', 'vortex-ai-marketplace' ),
            array( $this, 'render_template_meta_box' ),
            $this->post_type,
            'normal',
            'high'
        );
    }
    
    /**
     * Render the template meta box
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     */
    public function render_template_meta_box( $post ) {
        // Add nonce for security
        wp_nonce_field( 'vortex_huraii_template_nonce', 'vortex_huraii_template_nonce' );
        
        // Get saved values
        $base_prompt = get_post_meta( $post->ID, '_vortex_huraii_base_prompt', true );
        $negative_prompt = get_post_meta( $post->ID, '_vortex_huraii_negative_prompt', true );
        $ai_model = get_post_meta( $post->ID, '_vortex_huraii_ai_model', true );
        $steps = get_post_meta( $post->ID, '_vortex_huraii_steps', true );
        $guidance_scale = get_post_meta( $post->ID, '_vortex_huraii_guidance_scale', true );
        $token_cost = get_post_meta( $post->ID, '_vortex_huraii_token_cost', true );
        $premium_template = get_post_meta( $post->ID, '_vortex_huraii_premium_template', true );
        
        // Output fields
        ?>
        <p>
            <label for="vortex-huraii-base-prompt"><?php _e( 'Base Prompt:', 'vortex-ai-marketplace' ); ?></label>
            <textarea id="vortex-huraii-base-prompt" name="vortex_huraii_base_prompt" rows="4" class="large-text"><?php echo esc_textarea( $base_prompt ); ?></textarea>
            <span class="description"><?php _e( 'The base prompt text (will be combined with user input)', 'vortex-ai-marketplace' ); ?></span>
        </p>
        <p>
            <label for="vortex-huraii-negative-prompt"><?php _e( 'Negative Prompt:', 'vortex-ai-marketplace' ); ?></label>
            <textarea id="vortex-huraii-negative-prompt" name="vortex_huraii_negative_prompt" rows="3" class="large-text"><?php echo esc_textarea( $negative_prompt ); ?></textarea>
            <span class="description"><?php _e( 'Things to exclude from the generation', 'vortex-ai-marketplace' ); ?></span>
        </p>
        <p>
            <label for="vortex-huraii-ai-model"><?php _e( 'AI Model:', 'vortex-ai-marketplace' ); ?></label>
            <select id="vortex-huraii-ai-model" name="vortex_huraii_ai_model">
                <option value="stable-diffusion-xl" <?php selected( $ai_model, 'stable-diffusion-xl' ); ?>><?php _e( 'Stable Diffusion XL', 'vortex-ai-marketplace' ); ?></option>
                <option value="stable-diffusion-3" <?php selected( $ai_model, 'stable-diffusion-3' ); ?>><?php _e( 'Stable Diffusion 3', 'vortex-ai-marketplace' ); ?></option>
                <option value="midjourney-style" <?php selected( $ai_model, 'midjourney-style' ); ?>><?php _e( 'Midjourney Style', 'vortex-ai-marketplace' ); ?></option>
                <option value="realistic-vision" <?php selected( $ai_model, 'realistic-vision' ); ?>><?php _e( 'Realistic Vision', 'vortex-ai-marketplace' ); ?></option>
                <option value="dreamshaper" <?php selected( $ai_model, 'dreamshaper' ); ?>><?php _e( 'Dreamshaper', 'vortex-ai-marketplace' ); ?></option>
            </select>
        </p>
        <p>
            <label for="vortex-huraii-steps"><?php _e( 'Generation Steps:', 'vortex-ai-marketplace' ); ?></label>
            <input type="number" id="vortex-huraii-steps" name="vortex_huraii_steps" value="<?php echo esc_attr( $steps ); ?>" min="20" max="150" step="1">
            <span class="description"><?php _e( 'Number of inference steps (20-150)', 'vortex-ai-marketplace' ); ?></span>
        </p>
        <p>
            <label for="vortex-huraii-guidance-scale"><?php _e( 'Guidance Scale:', 'vortex-ai-marketplace' ); ?></label>
            <input type="number" id="vortex-huraii-guidance-scale" name="vortex_huraii_guidance_scale" value="<?php echo esc_attr( $guidance_scale ); ?>" min="1" max="20" step="0.5">
            <span class="description"><?php _e( 'How closely to follow the prompt (1-20)', 'vortex-ai-marketplace' ); ?></span>
        </p>
        <p>
            <label for="vortex-huraii-token-cost"><?php _e( 'TOLA Token Cost:', 'vortex-ai-marketplace' ); ?></label>
            <input type="number" id="vortex-huraii-token-cost" name="vortex_huraii_token_cost" value="<?php echo esc_attr( $token_cost ); ?>" min="0" step="0.1">
            <span class="description"><?php _e( 'Cost in TOLA tokens to use this template (0 for free)', 'vortex-ai-marketplace' ); ?></span>
        </p>
        <p>
            <label for="vortex-huraii-premium-template">
                <input type="checkbox" id="vortex-huraii-premium-template" name="vortex_huraii_premium_template" value="1" <?php checked( $premium_template, '1' ); ?>>
                <?php _e( 'Premium Template (requires subscription)', 'vortex-ai-marketplace' ); ?>
            </label>
        </p>
        <?php
    }
    
    /**
     * Save the meta box data
     *
     * @since    1.0.0
     * @param    int    $post_id    The post ID.
     */
    public function save_meta_box_data( $post_id ) {
        // Check if our nonce is set and verify it
        if ( ! isset( $_POST['vortex_huraii_template_nonce'] ) || ! wp_verify_nonce( $_POST['vortex_huraii_template_nonce'], 'vortex_huraii_template_nonce' ) ) {
            return;
        }
        
        // If this is an autosave, we don't want to do anything
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        
        // Check the user's permissions
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        
        // Template details
        if ( isset( $_POST['vortex_huraii_base_prompt'] ) ) {
            update_post_meta( $post_id, '_vortex_huraii_base_prompt', sanitize_textarea_field( $_POST['vortex_huraii_base_prompt'] ) );
        }
        
        if ( isset( $_POST['vortex_huraii_negative_prompt'] ) ) {
            update_post_meta( $post_id, '_vortex_huraii_negative_prompt', sanitize_textarea_field( $_POST['vortex_huraii_negative_prompt'] ) );
        }
        
        if ( isset( $_POST['vortex_huraii_ai_model'] ) ) {
            update_post_meta( $post_id, '_vortex_huraii_ai_model', sanitize_text_field( $_POST['vortex_huraii_ai_model'] ) );
        }
        
        if ( isset( $_POST['vortex_huraii_steps'] ) ) {
            update_post_meta( $post_id, '_vortex_huraii_steps', intval( $_POST['vortex_huraii_steps'] ) );
        }
        
        if ( isset( $_POST['vortex_huraii_guidance_scale'] ) ) {
            update_post_meta( $post_id, '_vortex_huraii_guidance_scale', floatval( $_POST['vortex_huraii_guidance_scale'] ) );
        }
        
        if ( isset( $_POST['vortex_huraii_token_cost'] ) ) {
            update_post_meta( $post_id, '_vortex_huraii_token_cost', floatval( $_POST['vortex_huraii_token_cost'] ) );
        }
        
        // Premium template checkbox
        $premium_template = isset( $_POST['vortex_huraii_premium_template'] ) ? '1' : '0';
        update_post_meta( $post_id, '_vortex_huraii_premium_template', $premium_template );
    }
} 