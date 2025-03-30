<?php
/**
 * Settings Header with Tabs
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin/partials/settings
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';

// Define tabs
$tabs = array(
    'general' => __('General', 'vortex-ai-marketplace'),
    'artwork' => __('Artwork', 'vortex-ai-marketplace'),
    'artists' => __('Artists', 'vortex-ai-marketplace'),
    'payments' => __('Payments', 'vortex-ai-marketplace'),
    'blockchain' => __('Blockchain', 'vortex-ai-marketplace'),
    'ai' => __('AI Systems', 'vortex-ai-marketplace'),
    'advanced' => __('Advanced', 'vortex-ai-marketplace')
);

// Admin page URL
$settings_url = admin_url('admin.php?page=vortex-settings');

?>
<div class="wrap">
    <h1><?php echo esc_html__('Vortex AI Marketplace Settings', 'vortex-ai-marketplace'); ?></h1>
    
    <nav class="nav-tab-wrapper wp-clearfix">
        <?php foreach ($tabs as $tab_id => $tab_name) : ?>
            <a href="<?php echo esc_url(add_query_arg('tab', $tab_id, $settings_url)); ?>" 
               class="nav-tab <?php echo $current_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html($tab_name); ?>
            </a>
        <?php endforeach; ?>
    </nav>
</div> 