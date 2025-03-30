<?php
/**
 * Template for displaying artist collaborations
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// Exit if accessed directly
if (!defined('WPINC')) {
    die;
}
?>

<div class="vortex-collaborations-container">
    <div class="vortex-collaborations-header">
        <h2><?php _e('Artist Collaborations', 'vortex-ai-marketplace'); ?></h2>
        <p class="vortex-collaborations-description">
            <?php _e('Connect with other artists and create something unique together.', 'vortex-ai-marketplace'); ?>
        </p>
    </div>
    
    <div class="vortex-collaborations-filters">
        <div class="vortex-filter-group">
            <label for="collab-status-filter"><?php _e('Status', 'vortex-ai-marketplace'); ?></label>
            <select id="collab-status-filter" class="vortex-status-filter">
                <option value="open"><?php _e('Open', 'vortex-ai-marketplace'); ?></option>
                <option value="active"><?php _e('Active', 'vortex-ai-marketplace'); ?></option>
                <option value="completed"><?php _e('Completed', 'vortex-ai-marketplace'); ?></option>
            </select>
        </div>
        
        <div class="vortex-filter-group">
            <label for="collab-type-filter"><?php _e('Type', 'vortex-ai-marketplace'); ?></label>
            <select id="collab-type-filter" class="vortex-type-filter">
                <option value=""><?php _e('All Types', 'vortex-ai-marketplace'); ?></option>
                <?php
                $types = get_terms(array('taxonomy' => 'vortex_collab_type', 'hide_empty' => true));
                foreach ($types as $type) {
                    echo '<option value="' . esc_attr($type->slug) . '">' . esc_html($type->name) . '</option>';
                }
                ?>
            </select>
        </div>
        
        <?php if (is_user_logged_in()) : ?>
        <div class="vortex-filter-group vortex-collab-participation">
            <label class="vortex-checkbox-filter">
                <input type="checkbox" id="collab-participation-filter" class="vortex-participation-filter">
                <?php _e('Show only my collaborations', 'vortex-ai-marketplace'); ?>
            </label>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="vortex-collaborations-grid">
        <?php while ($collaborations->have_posts()) : $collaborations->the_post(); ?>
            <?php
            $collab_id = get_the_ID();
            $status = get_post_meta($collab_id, 'vortex_collab_status', true);
            $participants = get_post_meta($collab_id, 'vortex_collab_participants', true) ?: array();
            $max_participants = get_post_meta($collab_id, 'vortex_collab_max_participants', true) ?: 0;
            $current_participants = is_array($participants) ? count($participants) : 0;
            $collab_type_terms = get_the_terms($collab_id, 'vortex_collab_type');
            $collab_type = $collab_type_terms ? $collab_type_terms[0]->name : '';
            $creator_id = get_post_field('post_author', $collab_id);
            $creator_name = get_the_author_meta('display_name', $creator_id);
            $deadline = get_post_meta($collab_id, 'vortex_collab_deadline', true);
            $has_deadline = !empty($deadline);
            $days_left = $has_deadline ? ceil((strtotime($deadline) - time()) / DAY_IN_SECONDS) : 0;
            $is_expired = $has_deadline && $days_left <= 0;
            $smart_contract = get_post_meta($collab_id, 'vortex_collab_contract', true);
            $has_contract = !empty($smart_contract);
            ?>
            <div class="vortex-collab-card vortex-collab-<?php echo esc_attr($status); ?>">
                <?php if (has_post_thumbnail()) : ?>
                <div class="vortex-collab-image">
                    <?php the_post_thumbnail('medium'); ?>
                    <div class="vortex-collab-status-badge vortex-status-<?php echo esc_attr($status); ?>">
                        <?php 
                        switch ($status) {
                            case 'open':
                                _e('Open for Collaboration', 'vortex-ai-marketplace');
                                break;
                            case 'active':
                                _e('In Progress', 'vortex-ai-marketplace');
                                break;
                            case 'completed':
                                _e('Completed', 'vortex-ai-marketplace');
                                break;
                            default:
                                echo esc_html($status);
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="vortex-collab-details">
                    <h3 class="vortex-collab-title"><?php the_title(); ?></h3>
                    
                    <div class="vortex-collab-meta">
                        <span class="vortex-collab-type">
                            <span class="dashicons dashicons-art"></span>
                            <?php echo esc_html($collab_type); ?>
                        </span>
                        
                        <span class="vortex-collab-creator">
                            <span class="dashicons dashicons-admin-users"></span>
                            <?php _e('By', 'vortex-ai-marketplace'); ?> <?php echo esc_html($creator_name); ?>
                        </span>
                        
                        <?php if ($has_deadline && !$is_expired) : ?>
                        <span class="vortex-collab-deadline">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php printf(_n('%s day left', '%s days left', $days_left, 'vortex-ai-marketplace'), number_format_i18n($days_left)); ?>
                        </span>
                        <?php elseif ($is_expired) : ?>
                        <span class="vortex-collab-deadline vortex-deadline-expired">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php _e('Deadline passed', 'vortex-ai-marketplace'); ?>
                        </span>
                        <?php endif; ?>
                        
                        <?php if ($has_contract) : ?>
                        <span class="vortex-collab-contract">
                            <span class="dashicons dashicons-shield"></span>
                            <?php _e('Smart Contract', 'vortex-ai-marketplace'); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="vortex-collab-excerpt">
                        <?php the_excerpt(); ?>
                    </div>
                    
                    <div class="vortex-collab-footer">
                        <div class="vortex-collab-participants">
                            <div class="vortex-participants-count">
                                <span class="vortex-current-participants"><?php echo esc_html($current_participants); ?></span>
                                <?php if ($max_participants > 0) : ?>
                                / <span class="vortex-max-participants"><?php echo esc_html($max_participants); ?></span>
                                <?php endif; ?>
                                <?php _e('Participants', 'vortex-ai-marketplace'); ?>
                            </div>
                            
                            <div class="vortex-participants-avatars">
                                <?php 
                                $shown_participants = 0;
                                $max_shown = 3;
                                
                                if (is_array($participants)) {
                                    foreach ($participants as $participant_id) {
                                        if ($shown_participants >= $max_shown) break;
                                        echo get_avatar($participant_id, 32, '', '', array('class' => 'vortex-participant-avatar'));
                                        $shown_participants++;
                                    }
                                    
                                    if (count($participants) > $max_shown) {
                                        echo '<span class="vortex-more-participants">+' . (count($participants) - $max_shown) . '</span>';
                                    }
                                }
                                ?>
                            </div>
                        </div>
                        
                        <a href="<?php the_permalink(); ?>" class="vortex-collab-button">
                            <?php
                            if ($status === 'open') {
                                _e('Join Collaboration', 'vortex-ai-marketplace');
                            } else {
                                _e('View Details', 'vortex-ai-marketplace');
                            }
                            ?>
                        </a>
                    </div>
                </div>
            </div>
        <?php endwhile; wp_reset_postdata(); ?>
    </div>
</div> 