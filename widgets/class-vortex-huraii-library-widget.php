<?php
/**
 * HURAII Library Widget
 * 
 * Displays the user's saved AI-generated images
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/widgets
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * HURAII Library Widget class
 */
class Vortex_HURAII_Library_Widget extends WP_Widget {

    /**
     * Register widget with WordPress
     */
    public function __construct() {
        parent::__construct(
            'vortex_huraii_library_widget',
            __('HURAII Image Library', 'vortex-ai-marketplace'),
            array(
                'description' => __('Displays your saved AI-generated images', 'vortex-ai-marketplace'),
            )
        );
    }

    /**
     * Front-end display of widget
     *
     * @param array $args Widget arguments
     * @param array $instance Saved values from database
     */
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            echo '<p>' . __('Please log in to view your HURAII image library.', 'vortex-ai-marketplace') . '</p>';
            echo $args['after_widget'];
            return;
        }
        
        $user_id = get_current_user_id();
        $limit = !empty($instance['limit']) ? intval($instance['limit']) : 6;
        $show_prompt = !empty($instance['show_prompt']) ? true : false;
        
        // Load HURAII Library
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-vortex-huraii-library.php';
        $library = Vortex_HURAII_Library::get_instance();
        
        // Get user library
        $library_data = $library->get_user_library($user_id, $limit, 1);
        $images = $library_data['images'];
        
        if (empty($images)) {
            echo '<p>' . __('You haven\'t created any AI images yet.', 'vortex-ai-marketplace') . '</p>';
            
            if (!empty($instance['show_create_button'])) {
                echo '<p><a href="' . esc_url(get_permalink(get_option('vortex_img2img_page'))) . '" class="button">' . __('Create AI Image', 'vortex-ai-marketplace') . '</a></p>';
            }
            
            echo $args['after_widget'];
            return;
        }
        
        echo '<div class="vortex-huraii-library-widget">';
        echo '<div class="vortex-huraii-image-grid">';
        
        foreach ($images as $image) {
            echo '<div class="vortex-huraii-image-item">';
            echo '<a href="' . esc_url($image['url']) . '" title="' . esc_attr($image['title']) . '">';
            echo '<img src="' . esc_url($image['thumbnail']) . '" alt="' . esc_attr($image['title']) . '">';
            echo '</a>';
            
            if ($show_prompt && !empty($image['prompt'])) {
                echo '<div class="vortex-huraii-image-prompt">' . esc_html(wp_trim_words($image['prompt'], 10)) . '</div>';
            }
            
            echo '</div>';
        }
        
        echo '</div>'; // .vortex-huraii-image-grid
        
        if (!empty($instance['show_view_all'])) {
            echo '<p class="vortex-huraii-view-all"><a href="' . esc_url(get_post_type_archive_link('vortex_ai_image')) . '">' . __('View All AI Images', 'vortex-ai-marketplace') . '</a></p>';
        }
        
        echo '</div>'; // .vortex-huraii-library-widget
        
        echo $args['after_widget'];
    }

    /**
     * Back-end widget form
     *
     * @param array $instance Previously saved values from database
     */
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('My AI Images', 'vortex-ai-marketplace');
        $limit = !empty($instance['limit']) ? intval($instance['limit']) : 6;
        $show_prompt = !empty($instance['show_prompt']) ? true : false;
        $show_view_all = !empty($instance['show_view_all']) ? true : false;
        $show_create_button = !empty($instance['show_create_button']) ? true : false;
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Title:', 'vortex-ai-marketplace'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('limit')); ?>"><?php esc_html_e('Number of images to show:', 'vortex-ai-marketplace'); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr($this->get_field_id('limit')); ?>" name="<?php echo esc_attr($this->get_field_name('limit')); ?>" type="number" step="1" min="1" max="20" value="<?php echo esc_attr($limit); ?>">
        </p>
        
        <p>
            <input class="checkbox" id="<?php echo esc_attr($this->get_field_id('show_prompt')); ?>" name="<?php echo esc_attr($this->get_field_name('show_prompt')); ?>" type="checkbox" <?php checked($show_prompt); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_prompt')); ?>"><?php esc_html_e('Show prompts under images', 'vortex-ai-marketplace'); ?></label>
        </p>
        
        <p>
            <input class="checkbox" id="<?php echo esc_attr($this->get_field_id('show_view_all')); ?>" name="<?php echo esc_attr($this->get_field_name('show_view_all')); ?>" type="checkbox" <?php checked($show_view_all); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_view_all')); ?>"><?php esc_html_e('Show "View All" link', 'vortex-ai-marketplace'); ?></label>
        </p>
        
        <p>
            <input class="checkbox" id="<?php echo esc_attr($this->get_field_id('show_create_button')); ?>" name="<?php echo esc_attr($this->get_field_name('show_create_button')); ?>" type="checkbox" <?php checked($show_create_button); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('show_create_button')); ?>"><?php esc_html_e('Show "Create AI Image" button when empty', 'vortex-ai-marketplace'); ?></label>
        </p>
        <?php
    }

    /**
     * Sanitize widget form values as they are saved
     *
     * @param array $new_instance Values just sent to be saved
     * @param array $old_instance Previously saved values from database
     * @return array Updated safe values to be saved
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['limit'] = (!empty($new_instance['limit'])) ? intval($new_instance['limit']) : 6;
        $instance['show_prompt'] = (!empty($new_instance['show_prompt'])) ? true : false;
        $instance['show_view_all'] = (!empty($new_instance['show_view_all'])) ? true : false;
        $instance['show_create_button'] = (!empty($new_instance['show_create_button'])) ? true : false;

        return $instance;
    }
}

// Register the widget
function register_vortex_huraii_library_widget() {
    register_widget('Vortex_HURAII_Library_Widget');
}
add_action('widgets_init', 'register_vortex_huraii_library_widget'); 