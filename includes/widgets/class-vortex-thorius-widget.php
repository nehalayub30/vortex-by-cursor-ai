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
        
        // Widget content
        $theme = !empty($instance['theme']) ? $instance['theme'] : 'light';
        $welcome_message = !empty($instance['welcome_message']) ? 
            $instance['welcome_message'] : 
            __('Hello! How can I assist you today?', 'vortex-ai-marketplace');
        $voice_enabled = !empty($instance['voice_enabled']);
        
        // Unique ID for this widget instance
        $widget_id = 'thorius-widget-' . $this->id;
        
        // Output widget HTML
        ?>
        <div id="<?php echo esc_attr($widget_id); ?>" class="thorius-widget" 
             data-theme="<?php echo esc_attr($theme); ?>"
             data-voice="<?php echo $voice_enabled ? 'true' : 'false'; ?>">
            <div class="thorius-widget-messages">
                <div class="thorius-message thorius-message-bot">
                    <div class="thorius-avatar"></div>
                    <div class="thorius-message-content"><?php echo esc_html($welcome_message); ?></div>
                </div>
            </div>
            <div class="thorius-widget-input">
                <input type="text" placeholder="<?php esc_attr_e('Ask me anything...', 'vortex-ai-marketplace'); ?>">
                <?php if ($voice_enabled) : ?>
                <button class="thorius-voice-button" aria-label="<?php esc_attr_e('Voice input', 'vortex-ai-marketplace'); ?>">
                    <span class="thorius-voice-icon"></span>
                </button>
                <?php endif; ?>
                <button class="thorius-send-button" aria-label="<?php esc_attr_e('Send message', 'vortex-ai-marketplace'); ?>">
                    <span class="thorius-send-icon"></span>
                </button>
            </div>
        </div>
        <?php
        
        // Enqueue necessary scripts
        wp_enqueue_style('thorius-widget-style');
        wp_enqueue_script('thorius-widget-script');
        
        if ($voice_enabled) {
            wp_enqueue_script('thorius-voice-script');
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
        $theme = !empty($instance['theme']) ? $instance['theme'] : 'light';
        $welcome_message = !empty($instance['welcome_message']) ? 
            $instance['welcome_message'] : 
            __('Hello! How can I assist you today?', 'vortex-ai-marketplace');
        $voice_enabled = !empty($instance['voice_enabled']) ? (bool) $instance['voice_enabled'] : false;
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php esc_attr_e('Title:', 'vortex-ai-marketplace'); ?>
            </label>
            <input class="widefat" 
                   id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('theme')); ?>">
                <?php esc_attr_e('Theme:', 'vortex-ai-marketplace'); ?>
            </label>
            <select class="widefat" 
                    id="<?php echo esc_attr($this->get_field_id('theme')); ?>" 
                    name="<?php echo esc_attr($this->get_field_name('theme')); ?>">
                <option value="light" <?php selected($theme, 'light'); ?>>
                    <?php esc_html_e('Light', 'vortex-ai-marketplace'); ?>
                </option>
                <option value="dark" <?php selected($theme, 'dark'); ?>>
                    <?php esc_html_e('Dark', 'vortex-ai-marketplace'); ?>
                </option>
                <option value="auto" <?php selected($theme, 'auto'); ?>>
                    <?php esc_html_e('Auto (follows system)', 'vortex-ai-marketplace'); ?>
                </option>
            </select>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('welcome_message')); ?>">
                <?php esc_attr_e('Welcome Message:', 'vortex-ai-marketplace'); ?>
            </label>
            <textarea class="widefat" 
                      id="<?php echo esc_attr($this->get_field_id('welcome_message')); ?>" 
                      name="<?php echo esc_attr($this->get_field_name('welcome_message')); ?>"
                      rows="3"><?php echo esc_textarea($welcome_message); ?></textarea>
        </p>
        <p>
            <input type="checkbox" 
                   id="<?php echo esc_attr($this->get_field_id('voice_enabled')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('voice_enabled')); ?>"
                   <?php checked($voice_enabled); ?>>
            <label for="<?php echo esc_attr($this->get_field_id('voice_enabled')); ?>">
                <?php esc_attr_e('Enable voice input', 'vortex-ai-marketplace'); ?>
            </label>
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
        
        $instance['title'] = (!empty($new_instance['title'])) ? 
            sanitize_text_field($new_instance['title']) : '';
        
        $instance['theme'] = (!empty($new_instance['theme']) && 
            in_array($new_instance['theme'], array('light', 'dark', 'auto'))) ? 
            $new_instance['theme'] : 'light';
        
        $instance['welcome_message'] = (!empty($new_instance['welcome_message'])) ? 
            sanitize_textarea_field($new_instance['welcome_message']) : '';
        
        $instance['voice_enabled'] = (!empty($new_instance['voice_enabled'])) ? 1 : 0;
        
        return $instance;
    }
} 