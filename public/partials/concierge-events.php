<?php
/**
 * Template for displaying concierge experiences
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/partials
 */

// Exit if accessed directly
if (!defined('WPINC')) {
    die;
}
?>

<div class="vortex-concierge-events">
    <div class="vortex-concierge-header">
        <h2><?php _e('Exclusive Art Experiences', 'vortex-ai-marketplace'); ?></h2>
        <p class="vortex-concierge-description"><?php _e('Connect with the art world through our curated physical experiences.', 'vortex-ai-marketplace'); ?></p>
    </div>
    
    <div class="vortex-experience-filters">
        <div class="vortex-filter-group">
            <label for="location-filter"><?php _e('Location', 'vortex-ai-marketplace'); ?></label>
            <select id="location-filter" class="vortex-location-filter">
                <option value=""><?php _e('All Locations', 'vortex-ai-marketplace'); ?></option>
                <?php
                $locations = get_terms(array('taxonomy' => 'vortex_location', 'hide_empty' => true));
                foreach ($locations as $location) {
                    echo '<option value="' . esc_attr($location->slug) . '">' . esc_html($location->name) . '</option>';
                }
                ?>
            </select>
        </div>
        
        <div class="vortex-filter-group">
            <label for="type-filter"><?php _e('Type', 'vortex-ai-marketplace'); ?></label>
            <select id="type-filter" class="vortex-type-filter">
                <option value=""><?php _e('All Types', 'vortex-ai-marketplace'); ?></option>
                <?php
                $types = get_terms(array('taxonomy' => 'vortex_experience_type', 'hide_empty' => true));
                foreach ($types as $type) {
                    echo '<option value="' . esc_attr($type->slug) . '">' . esc_html($type->name) . '</option>';
                }
                ?>
            </select>
        </div>
        
        <div class="vortex-filter-group">
            <label for="date-filter"><?php _e('When', 'vortex-ai-marketplace'); ?></label>
            <select id="date-filter" class="vortex-date-filter">
                <option value="upcoming"><?php _e('Upcoming', 'vortex-ai-marketplace'); ?></option>
                <option value="this-week"><?php _e('This Week', 'vortex-ai-marketplace'); ?></option>
                <option value="this-month"><?php _e('This Month', 'vortex-ai-marketplace'); ?></option>
                <option value="next-month"><?php _e('Next Month', 'vortex-ai-marketplace'); ?></option>
            </select>
        </div>
    </div>
    
    <div class="vortex-experiences-grid">
        <?php while ($experiences->have_posts()) : $experiences->the_post(); ?>
            <?php
            $event_date = get_post_meta(get_the_ID(), 'vortex_experience_date', true);
            $formatted_date = date_i18n(get_option('date_format'), strtotime($event_date));
            $time_string = get_post_meta(get_the_ID(), 'vortex_experience_time', true);
            $location_terms = get_the_terms(get_the_ID(), 'vortex_location');
            $location = $location_terms ? $location_terms[0]->name : '';
            $price = get_post_meta(get_the_ID(), 'vortex_experience_price', true);
            $tola_price = get_post_meta(get_the_ID(), 'vortex_experience_tola', true);
            $remaining_slots = get_post_meta(get_the_ID(), 'vortex_experience_capacity', true) - 
                               get_post_meta(get_the_ID(), 'vortex_experience_booked', true);
            $is_nft_gated = get_post_meta(get_the_ID(), 'vortex_experience_nft_gated', true);
            $required_nft = get_post_meta(get_the_ID(), 'vortex_required_nft', true);
            ?>
            <div class="vortex-experience-card <?php echo $is_nft_gated ? 'vortex-nft-gated' : ''; ?>">
                <?php if (has_post_thumbnail()) : ?>
                <div class="vortex-experience-image">
                    <?php the_post_thumbnail('medium'); ?>
                    <?php if ($is_nft_gated) : ?>
                    <div class="vortex-nft-badge">
                        <span class="dashicons dashicons-lock"></span>
                        <?php _e('NFT Access', 'vortex-ai-marketplace'); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="vortex-experience-details">
                    <h3 class="vortex-experience-title"><?php the_title(); ?></h3>
                    
                    <div class="vortex-experience-meta">
                        <span class="vortex-experience-date">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php echo esc_html($formatted_date); ?>
                        </span>
                        
                        <?php if ($time_string) : ?>
                        <span class="vortex-experience-time">
                            <span class="dashicons dashicons-clock"></span>
                            <?php echo esc_html($time_string); ?>
                        </span>
                        <?php endif; ?>
                        
                        <?php if ($location) : ?>
                        <span class="vortex-experience-location">
                            <span class="dashicons dashicons-location"></span>
                            <?php echo esc_html($location); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="vortex-experience-excerpt">
                        <?php the_excerpt(); ?>
                    </div>
                    
                    <div class="vortex-experience-footer">
                        <div class="vortex-experience-pricing">
                            <?php if ($tola_price) : ?>
                            <span class="vortex-tola-price"><?php echo esc_html($tola_price); ?> TOLA</span>
                            <?php endif; ?>
                            
                            <?php if ($price) : ?>
                            <span class="vortex-fiat-price"><?php echo esc_html($price); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="vortex-experience-slots">
                            <?php if ($remaining_slots > 0) : ?>
                            <span class="vortex-slots-available">
                                <?php printf(_n('%s spot left', '%s spots left', $remaining_slots, 'vortex-ai-marketplace'), number_format_i18n($remaining_slots)); ?>
                            </span>
                            <?php else : ?>
                            <span class="vortex-slots-full"><?php _e('Sold Out', 'vortex-ai-marketplace'); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <a href="<?php the_permalink(); ?>" class="vortex-experience-button">
                            <?php _e('View Details', 'vortex-ai-marketplace'); ?>
                        </a>
                    </div>
                </div>
            </div>
        <?php endwhile; wp_reset_postdata(); ?>
    </div>
</div> 