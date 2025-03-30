<?php
/**
 * Template for Settings Tabs
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/admin/partials
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
    'advanced' => __('Advanced', 'vortex-ai-marketplace'),
);

?>
<div class="wrap">
    <h1><?php echo esc_html__('Vortex AI Marketplace Settings', 'vortex-ai-marketplace'); ?></h1>
    
    <nav class="nav-tab-wrapper">
        <?php foreach ($tabs as $tab_id => $tab_name): ?>
            <a href="<?php echo esc_url(add_query_arg('tab', $tab_id, admin_url('admin.php?page=vortex-settings'))); ?>" class="nav-tab <?php echo ($current_tab === $tab_id) ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html($tab_name); ?>
            </a>
        <?php endforeach; ?>
    </nav>
    
    <div class="tab-content">
        <?php
        // Include the appropriate tab content
        switch ($current_tab) {
            case 'artwork':
                require_once plugin_dir_path(dirname(__FILE__)) . 'partials/settings/artwork-settings.php';
                break;
            case 'artists':
                require_once plugin_dir_path(dirname(__FILE__)) . 'partials/settings/artists-settings.php';
                break;
            case 'payments':
                require_once plugin_dir_path(dirname(__FILE__)) . 'partials/settings/payments-settings.php';
                break;
            case 'blockchain':
                require_once plugin_dir_path(dirname(__FILE__)) . 'partials/settings/blockchain-settings.php';
                break;
            case 'ai':
                require_once plugin_dir_path(dirname(__FILE__)) . 'partials/settings/ai-settings.php';
                break;
            case 'advanced':
                require_once plugin_dir_path(dirname(__FILE__)) . 'partials/settings/advanced-settings.php';
                break;
            case 'general':
            default:
                require_once plugin_dir_path(dirname(__FILE__)) . 'partials/settings/general-settings.php';
                break;
        }
        ?>
    </div>
</div> 