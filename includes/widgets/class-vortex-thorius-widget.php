<?php
/**
 * Thorius Widget
 * 
 * Implements a widget for using Thorius in sidebars
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/widgets
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Thorius Widget
 */
class Vortex_Thorius_Widget extends WP_Widget {
    /**
     * Register widget with WordPress
     */
    public function __construct() {
        parent::__construct(
            'vortex_thorius_widget', // Base ID
            __('Vortex AI Assistant', 'vortex-ai-marketplace'), // Name
            array('description' => __('Add an AI assistant to your sidebar', 'vortex-ai-marketplace')) // Args
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
        
        // Get widget settings
        $agent = !empty($instance['agent']) ? $instance['agent'] : 'cloe';
        $theme = !empty($instance['theme']) ? $instance['theme'] : 'light';
        $height = !empty($instance['height']) ? $instance['height'] : '400px';
        $welcome_message = !empty($instance['welcome_message']) ? $instance['welcome_message'] : '';
        
        // Get instance of main plugin class
        $thorius = new Vortex_Thorius();
        
        // Output the widget content based on agent type
        switch ($agent) {
            case 'concierge':
                echo $thorius->concierge_shortcode(array(
                    'theme' => $theme,
                    'position' => 'bottom-right',
                    'welcome_message' => $welcome_message
                ));
                break;
                
            case 'chat':
                echo $thorius->chat_shortcode(array(
                    'theme' => $theme,
                    'height' => $height,
                    'welcome_message' => $welcome_message
                ));
                break;
                
            default:
                echo $thorius->agent_shortcode(array(
                    'agent' => $agent,
                    'theme' => $theme,
                    'height' => $height,
                    'welcome_message' => $welcome_message
                ));
                break;
        }
        
        echo $args['after_widget'];
    }
    
    /**
     * Back-end widget form
     *
     * @param array $instance Previously saved values from database
     */
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('AI Assistant', 'vortex-ai-marketplace');
        $agent = !empty($instance['agent']) ? $instance['agent'] : 'cloe';
        $theme = !empty($instance['theme']) ? $instance['theme'] : 'light';
        $height = !empty($instance['height']) ? $instance['height'] : '400px';
        $welcome_message = !empty($instance['welcome_message']) ? $instance['welcome_message'] : '';
        
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Title:', 'vortex-ai-marketplace'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('agent')); ?>"><?php esc_html_e('Agent Type:', 'vortex-ai-marketplace'); ?></label>
            <select class="widefat" id="<?php echo esc_attr($this->get_field_id('agent')); ?>" name="<?php echo esc_attr($this->get_field_name('agent')); ?>">
                <option value="concierge" <?php selected($agent, 'concierge'); ?>><?php esc_html_e('Thorius Concierge', 'vortex-ai-marketplace'); ?></option>
                <option value="chat" <?php selected($agent, 'chat'); ?>><?php esc_html_e('Chat Interface', 'vortex-ai-marketplace'); ?></option>
                <option value="cloe" <?php selected($agent, 'cloe'); ?>><?php esc_html_e('CLOE', 'vortex-ai-marketplace'); ?></option>
                <option value="huraii" <?php selected($agent, 'huraii'); ?>><?php esc_html_e('HURAII', 'vortex-ai-marketplace'); ?></option>
                <option value="strategist" <?php selected($agent, 'strategist'); ?>><?php esc_html_e('Business Strategist', 'vortex-ai-marketplace'); ?></option>
            </select>
        </p>
        
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('theme')); ?>"><?php esc_html_e('Theme:', 'vortex-ai-marketplace'); ?></label>
            <select class="widefat" id="<?php echo esc_attr($this->get_field_id('theme')); ?>" name="<?php echo esc_attr($this->get_field_name('theme')); ?>">
                <option value="light" <?php selected($theme, 'light'); ?>><?php esc_html_e('Light', 'vortex-ai-marketplace'); ?></option>
                <option value="dark" <?php selected($theme, 'dark'); ?>><?php esc_html_e('Dark', 'vortex-ai-marketplace'); ?></option>
            </select>
        </p>
        
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('height')); ?>"><?php esc_html_e('Height:', 'vortex-ai-marketplace'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('height')); ?>" name="<?php echo esc_attr($this->get_field_name('height')); ?>" type="text" value="<?php echo esc_attr($height); ?>">
        </p>
        
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('welcome_message')); ?>"><?php esc_html_e('Welcome Message:', 'vortex-ai-marketplace'); ?></label>
            <textarea class="widefat" id="<?php echo esc_attr($this->get_field_id('welcome_message')); ?>" name="<?php echo esc_attr($this->get_field_name('welcome_message')); ?>" rows="4"><?php echo esc_textarea($welcome_message); ?></textarea>
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
        $instance['agent'] = (!empty($new_instance['agent'])) ? sanitize_key($new_instance['agent']) : 'cloe';
        $instance['theme'] = (!empty($new_instance['theme'])) ? sanitize_key($new_instance['theme']) : 'light';
        $instance['height'] = (!empty($new_instance['height'])) ? sanitize_text_field($new_instance['height']) : '400px';
        $instance['welcome_message'] = (!empty($new_instance['welcome_message'])) ? sanitize_textarea_field($new_instance['welcome_message']) : '';
        
        return $instance;
    }
} 