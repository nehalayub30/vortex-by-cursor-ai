<?php
namespace Vortex\AI\Frontend;

use Vortex\AI\Language;

class LanguageSwitcher extends \WP_Widget {
    private $language;

    public function __construct() {
        parent::__construct(
            'vortex_language_switcher',
            __('VORTEX Language Switcher', 'vortex-ai'),
            ['description' => __('Switch between available languages', 'vortex-ai')]
        );
        $this->language = new Language();
    }

    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }

        $current_language = $this->language->get_current_language();
        $available_languages = $this->language->get_available_languages();

        echo '<div class="vortex-language-switcher">';
        echo '<select onchange="window.location.href=this.value;">';
        
        foreach ($available_languages as $code => $name) {
            $selected = $code === $current_language ? 'selected' : '';
            $url = add_query_arg('lang', $code);
            echo sprintf(
                '<option value="%s" %s>%s</option>',
                esc_url($url),
                esc_attr($selected),
                esc_html($name)
            );
        }
        
        echo '</select>';
        echo '</div>';

        echo $args['after_widget'];
    }

    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php esc_html_e('Title:', 'vortex-ai'); ?>
            </label>
            <input class="widefat" 
                   id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" 
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = (!empty($new_instance['title'])) 
            ? strip_tags($new_instance['title'])
            : '';
        return $instance;
    }
} 