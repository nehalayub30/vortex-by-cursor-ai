<?php
/**
 * VORTEX Achievement Widget
 *
 * @package VORTEX
 */

class VORTEX_Achievement_Widget extends WP_Widget {
    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct(
            'vortex_achievements',
            __('VORTEX Achievements', 'vortex'),
            ['description' => __('Display user achievements from the DAO system', 'vortex')]
        );
    }

    /**
     * Front-end display of widget.
     */
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        $title = !empty($instance['title']) ? apply_filters('widget_title', $instance['title']) : __('My Achievements', 'vortex');
        $show_count = isset($instance['show_count']) ? (bool)$instance['show_count'] : true;
        $max_achievements = isset($instance['max_achievements']) ? absint($instance['max_achievements']) : 5;
        
        echo $args['before_title'] . esc_html($title) . $args['after_title'];
        
        // Add achievement display
        ?>
        <div class="vortex-widget-achievements">
            <div class="vortex-connect-wallet-container">
                <button class="vortex-connect-wallet"><?php esc_html_e('Connect Wallet', 'vortex'); ?></button>
                <div class="vortex-wallet-status"><?php esc_html_e('Not Connected', 'vortex'); ?></div>
            </div>
            
            <div class="vortex-achievement-mini-gallery">
                <div class="vortex-loading"><?php esc_html_e('Loading achievements...', 'vortex'); ?></div>
            </div>
            
            <?php if ($show_count): ?>
            <div class="vortex-achievement-count">
                <span class="count">0</span> <?php esc_html_e('Achievements earned', 'vortex'); ?>
            </div>
            <?php endif; ?>
            
            <a href="<?php echo esc_url(get_permalink(get_option('vortex_dashboard_page_id'))); ?>" class="vortex-view-all">
                <?php esc_html_e('View All Achievements', 'vortex'); ?>
            </a>
        </div>
        
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // If user is already connected, load achievements
                if (typeof VortexDAO !== 'undefined' && VortexDAO.userAddress) {
                    loadWidgetAchievements(VortexDAO.userAddress, <?php echo esc_js($max_achievements); ?>);
                }
                
                // Listen for wallet connection events
                $(document).on('vortex_wallet_connected', function(e, walletAddress) {
                    loadWidgetAchievements(walletAddress, <?php echo esc_js($max_achievements); ?>);
                });
                
                function loadWidgetAchievements(walletAddress, limit) {
                    $.ajax({
                        url: vortexDAO.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'vortex_get_user_achievements',
                            wallet_address: walletAddress,
                            nonce: vortexDAO.nonce,
                            limit: limit
                        },
                        success: function(response) {
                            if (response.success) {
                                renderWidgetAchievements(response.data, limit);
                                $('.vortex-achievement-count .count').text(response.data.length);
                            } else {
                                $('.vortex-achievement-mini-gallery').html('<div class="vortex-error">' + response.data.message + '</div>');
                            }
                        },
                        error: function() {
                            $('.vortex-achievement-mini-gallery').html('<div class="vortex-error">Error loading achievements</div>');
                        }
                    });
                }
                
                function renderWidgetAchievements(achievements, limit) {
                    if (!achievements || achievements.length === 0) {
                        $('.vortex-achievement-mini-gallery').html('<div class="vortex-no-data">No achievements earned yet</div>');
                        return;
                    }
                    
                    var html = '<div class="achievement-mini-grid">';
                    var displayCount = Math.min(achievements.length, limit);
                    
                    for (var i = 0; i < displayCount; i++) {
                        var achievement = achievements[i];
                        html += '<div class="achievement-mini-card" data-id="' + achievement.id + '">' +
                            '<div class="achievement-mini-image">' +
                            '<img src="' + achievement.image + '" alt="' + achievement.name + '">' +
                            '</div>' +
                            '<div class="achievement-mini-info">' +
                            '<h4>' + achievement.name + '</h4>' +
                            '</div>' +
                            '</div>';
                    }
                    
                    html += '</div>';
                    $('.vortex-achievement-mini-gallery').html(html);
                }
            });
        </script>
        <?php
        
        echo $args['after_widget'];
    }

    /**
     * Back-end widget form.
     */
    public function form($instance) {
        $title = isset($instance['title']) ? $instance['title'] : __('My Achievements', 'vortex');
        $show_count = isset($instance['show_count']) ? (bool)$instance['show_count'] : true;
        $max_achievements = isset($instance['max_achievements']) ? absint($instance['max_achievements']) : 5;
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Title:', 'vortex'); ?></label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
        </p>
        <p>
            <input type="checkbox" class="checkbox" id="<?php echo esc_attr($this->get_field_id('show_count')); ?>" name="<?php echo esc_attr($this->get_field_name('show_count')); ?>" <?php checked($show_count); ?> />
            <label for="<?php echo esc_attr($this->get_field_id('show_count')); ?>"><?php esc_html_e('Display achievement count', 'vortex'); ?></label>
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('max_achievements')); ?>"><?php esc_html_e('Maximum achievements to show:', 'vortex'); ?></label>
            <input class="tiny-text" id="<?php echo esc_attr($this->get_field_id('max_achievements')); ?>" name="<?php echo esc_attr($this->get_field_name('max_achievements')); ?>" type="number" min="1" max="20" value="<?php echo esc_attr($max_achievements); ?>" />
        </p>
        <?php
    }

    /**
     * Sanitize widget form values as they are saved.
     */
    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['show_count'] = isset($new_instance['show_count']) ? (bool)$new_instance['show_count'] : false;
        $instance['max_achievements'] = (!empty($new_instance['max_achievements'])) ? absint($new_instance['max_achievements']) : 5;
        
        return $instance;
    }
} 