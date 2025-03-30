/**
 * Add Collector Workplace submenu page
 */
public function add_collector_workplace_page() {
    add_submenu_page(
        'vortex-dashboard',
        __('Collector Workplace', 'vortex-ai-marketplace'),
        __('Collector Workplace', 'vortex-ai-marketplace'),
        'manage_options',
        'vortex-collector-workplace',
        array($this, 'render_collector_workplace_page')
    );
}

/**
 * Render the Collector Workplace admin page
 */
public function render_collector_workplace_page() {
    // Add a link to create new swipeable items
    $add_new_url = admin_url('post-new.php?post_type=vortex_item');
    
    // Get all swipeable items
    $items = get_posts(array(
        'post_type' => 'vortex_item',
        'post_status' => 'publish',
        'posts_per_page' => -1
    ));
    
    // Get categories
    $categories = get_terms(array(
        'taxonomy' => 'item_category',
        'hide_empty' => false
    ));
    
    // Include the admin page template
    include plugin_dir_path(dirname(__FILE__)) . 'admin/partials/vortex-collector-workplace-page.php';
} 