<?php
/**
 * Admin page for Collector Workplace management
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin/partials
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap vortex-collector-workplace-page">
    <h1><?php _e('Collector Workplace Management', 'vortex-ai-marketplace'); ?></h1>
    
    <div class="vortex-admin-notice notice notice-info">
        <p><?php _e('The Collector Workplace provides a Tinder-like swiping interface for users to quickly browse and collect items in the marketplace.', 'vortex-ai-marketplace'); ?></p>
        <p><?php _e('Add new swipeable items using the button below. These items will appear in the collector workplace for users to swipe through.', 'vortex-ai-marketplace'); ?></p>
    </div>
    
    <div class="vortex-admin-actions">
        <a href="<?php echo esc_url($add_new_url); ?>" class="button button-primary">
            <?php _e('Add New Swipeable Item', 'vortex-ai-marketplace'); ?>
        </a>
        
        <a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=item_category&post_type=vortex_item')); ?>" class="button">
            <?php _e('Manage Categories', 'vortex-ai-marketplace'); ?>
        </a>
        
        <a href="<?php echo esc_url(home_url('/?vortex_preview_workplace=1')); ?>" class="button" target="_blank">
            <?php _e('Preview Collector Workplace', 'vortex-ai-marketplace'); ?>
        </a>
    </div>
    
    <div class="vortex-admin-stats">
        <div class="vortex-stat-box">
            <h3><?php _e('Total Items', 'vortex-ai-marketplace'); ?></h3>
            <span class="vortex-stat-value"><?php echo count($items); ?></span>
        </div>
        
        <div class="vortex-stat-box">
            <h3><?php _e('Categories', 'vortex-ai-marketplace'); ?></h3>
            <span class="vortex-stat-value"><?php echo count($categories); ?></span>
        </div>
        
        <div class="vortex-stat-box">
            <h3><?php _e('Swipes', 'vortex-ai-marketplace'); ?></h3>
            <span class="vortex-stat-value"><?php echo get_option('vortex_total_swipes', 0); ?></span>
        </div>
        
        <div class="vortex-stat-box">
            <h3><?php _e('Collections', 'vortex-ai-marketplace'); ?></h3>
            <span class="vortex-stat-value"><?php echo get_option('vortex_total_collections', 0); ?></span>
        </div>
    </div>
    
    <h2><?php _e('Current Swipeable Items', 'vortex-ai-marketplace'); ?></h2>
    
    <?php if (empty($items)) : ?>
        <div class="vortex-empty-state">
            <p><?php _e('No swipeable items found. Click the button above to add your first item.', 'vortex-ai-marketplace'); ?></p>
        </div>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Title', 'vortex-ai-marketplace'); ?></th>
                    <th><?php _e('Category', 'vortex-ai-marketplace'); ?></th>
                    <th><?php _e('Created', 'vortex-ai-marketplace'); ?></th>
                    <th><?php _e('Swipes', 'vortex-ai-marketplace'); ?></th>
                    <th><?php _e('Collections', 'vortex-ai-marketplace'); ?></th>
                    <th><?php _e('Actions', 'vortex-ai-marketplace'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item) : 
                    $edit_url = get_edit_post_link($item->ID);
                    $view_url = get_permalink($item->ID);
                    $item_categories = wp_get_post_terms($item->ID, 'item_category', array('fields' => 'names'));
                    $swipes = get_post_meta($item->ID, 'vortex_swipe_count', true) ?: 0;
                    $collections = get_post_meta($item->ID, 'vortex_collection_count', true) ?: 0;
                ?>
                <tr>
                    <td>
                        <strong><a href="<?php echo esc_url($edit_url); ?>"><?php echo esc_html($item->post_title); ?></a></strong>
                    </td>
                    <td><?php echo esc_html(implode(', ', $item_categories)); ?></td>
                    <td><?php echo get_the_date('', $item->ID); ?></td>
                    <td><?php echo esc_html($swipes); ?></td>
                    <td><?php echo esc_html($collections); ?></td>
                    <td>
                        <a href="<?php echo esc_url($edit_url); ?>" class="button button-small">
                            <?php _e('Edit', 'vortex-ai-marketplace'); ?>
                        </a>
                        <a href="<?php echo esc_url($view_url); ?>" class="button button-small" target="_blank">
                            <?php _e('View', 'vortex-ai-marketplace'); ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<style>
.vortex-collector-workplace-page {
    max-width: 1200px;
    margin: 0 auto;
}

.vortex-admin-notice {
    margin: 20px 0;
    padding: 15px;
}

.vortex-admin-actions {
    display: flex;
    gap: 10px;
    margin: 20px 0;
}

.vortex-admin-stats {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin: 30px 0;
}

.vortex-stat-box {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
    flex: 1;
    min-width: 150px;
}

.vortex-stat-box h3 {
    margin-top: 0;
    color: #23282d;
}

.vortex-stat-value {
    font-size: 24px;
    font-weight: bold;
    color: #0073aa;
}

.vortex-empty-state {
    background: #f8f9fa;
    border: 1px dashed #ddd;
    border-radius: 4px;
    padding: 40px;
    text-align: center;
    margin: 20px 0;
}
</style> 