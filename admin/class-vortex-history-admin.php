<?php
/**
 * VORTEX History Admin Class
 *
 * Handles admin interface for history management
 */

class VORTEX_History_Admin {
    private $history_manager;
    
    public function __construct() {
        $this->history_manager = new VORTEX_History_Manager();
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Add AJAX handlers
        add_action('wp_ajax_vortex_get_history_data', array($this, 'ajax_get_history_data'));
        add_action('wp_ajax_vortex_export_history', array($this, 'ajax_export_history'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=vortex_artwork',
            'Marketplace History',
            'History Logs',
            'manage_options',
            'vortex-history',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'vortex_history_options',
            'vortex_history_retention_days',
            array(
                'type' => 'integer',
                'description' => 'Number of days to retain history records',
                'sanitize_callback' => array($this, 'sanitize_retention_days'),
                'default' => 14,
            )
        );
    }
    
    /**
     * Sanitize retention days
     */
    public function sanitize_retention_days($input) {
        $days = intval($input);
        return ($days < 1) ? 1 : $days;
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        // Enqueue admin scripts and styles
        wp_enqueue_script('vortex-history-admin', VORTEX_PLUGIN_URL . 'admin/js/vortex-history-admin.js', array('jquery', 'jquery-ui-datepicker'), VORTEX_VERSION, true);
        wp_enqueue_style('vortex-history-admin', VORTEX_PLUGIN_URL . 'admin/css/vortex-history-admin.css', array(), VORTEX_VERSION);
        wp_enqueue_style('jquery-ui', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css');
        
        // Prepare data for JavaScript
        wp_localize_script('vortex-history-admin', 'vortexHistoryData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_history_nonce'),
            'is_admin' => current_user_can('manage_options') ? 'yes' : 'no'
        ));
        
        // Show admin interface
        ?>
        <div class="wrap vortex-history-admin">
            <h1>Marketplace History Management</h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="#logs" class="nav-tab nav-tab-active">History Logs</a>
                <a href="#settings" class="nav-tab">Settings</a>
            </h2>
            
            <div class="tab-content" id="logs-tab">
                <div class="vortex-history-filters">
                    <h3>Filter History</h3>
                    <div class="filter-controls">
                        <div class="filter-row">
                            <label for="user-filter">User:</label>
                            <select id="user-filter">
                                <option value="">All Users</option>
                                <?php
                                $users = get_users(array('role__in' => array('administrator', 'author', 'subscriber')));
                                foreach ($users as $user) {
                                    echo '<option value="' . esc_attr($user->ID) . '">' . esc_html($user->display_name) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="filter-row">
                            <label for="date-from">From:</label>
                            <input type="text" id="date-from" class="datepicker" placeholder="Start date">
                            <label for="date-to">To:</label>
                            <input type="text" id="date-to" class="datepicker" placeholder="End date">
                        </div>
                        <div class="filter-row">
                            <label for="action-type">Action Type:</label>
                            <select id="action-type">
                                <option value="">All Actions</option>
                                <option value="artwork_created">Artwork Created</option>
                                <option value="purchase">Purchase</option>
                                <option value="sale">Sale</option>
                                <option value="offer_made">Offer Made</option>
                                <option value="offer_received">Offer Received</option>
                                <option value="offer_accepted">Offer Accepted</option>
                                <option value="offer_rejected">Offer Rejected</option>
                                <option value="collection_updated">Collection Updated</option>
                                <option value="blockchain_transaction">Blockchain Transaction</option>
                            </select>
                        </div>
                        <div class="filter-row">
                            <button id="apply-filters" class="button button-primary">Apply Filters</button>
                            <button id="reset-filters" class="button">Reset Filters</button>
                            <button id="export-csv" class="button">Export to CSV</button>
                        </div>
                    </div>
                </div>
                
                <div class="vortex-history-results">
                    <h3>History Records</h3>
                    <div class="history-table-container">
                        <table class="widefat striped" id="history-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Item</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be loaded via AJAX -->
                                <tr class="no-items">
                                    <td colspan="5">Loading history data...</td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="tablenav bottom">
                            <div class="tablenav-pages">
                                <span class="pagination-links">
                                    <span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>
                                    <span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>
                                    <span class="paging-input">
                                        <span class="current-page">1</span>
                                        <span class="total-pages">/ 1</span>
                                    </span>
                                    <a class="next-page button" href="#"><span aria-hidden="true">›</span></a>
                                    <a class="last-page button" href="#"><span aria-hidden="true">»</span></a>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="tab-content" id="settings-tab" style="display:none;">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('vortex_history_options');
                    do_settings_sections('vortex_history_options');
                    ?>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">Retention Period (days)</th>
                            <td>
                                <input type="number" name="vortex_history_retention_days" 
                                    value="<?php echo esc_attr($this->history_manager->get_retention_period()); ?>" min="1" step="1" />
                                <p class="description">Number of days to keep history records before automatic cleanup. Minimum 1 day.</p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button('Save Settings'); ?>
                </form>
                
                <div class="manual-cleanup">
                    <h3>Manual Cleanup</h3>
                    <p>Click the button below to manually delete history records older than the retention period.</p>
                    <button id="manual-cleanup" class="button button-secondary">Run Cleanup Now</button>
                    <div id="cleanup-results"></div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX handler for getting history data
     */
    public function ajax_get_history_data() {
        // Check nonce
        if (!check_ajax_referer('vortex_history_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Invalid security token.'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'You do not have permission to access this data.'));
            return;
        }
        
        // Get filter parameters
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $action_type = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;
        
        // Build filters array
        $filters = array(
            'per_page' => $per_page,
            'page' => $page
        );
        
        if (!empty($action_type)) {
            $filters['action_type'] = $action_type;
        }
        
        if (!empty($date_from)) {
            $filters['date_from'] = date('Y-m-d 00:00:00', strtotime($date_from));
        }
        
        if (!empty($date_to)) {
            $filters['date_to'] = date('Y-m-d 23:59:59', strtotime($date_to));
        }
        
        // Get history data
        if ($user_id > 0) {
            $history = $this->history_manager->get_user_history($user_id, $filters);
        } else {
            $history = $this->history_manager->get_all_history($filters);
        }
        
        // Format for display
        $formatted_data = array();
        if (!empty($history['records'])) {
            foreach ($history['records'] as $record) {
                $user_info = get_userdata($record->user_id);
                $user_name = $user_info ? $user_info->display_name : 'Unknown User';
                
                $details = json_decode($record->action_details, true);
                $formatted_details = '';
                
                if (is_array($details)) {
                    foreach ($details as $key => $value) {
                        if ($key === 'price' && isset($details['currency'])) {
                            $formatted_details .= "$key: $value {$details['currency']}, ";
                        } else if (!in_array($key, array('artwork_id', 'collection_id', 'currency'))) {
                            $formatted_details .= "$key: $value, ";
                        }
                    }
                    $formatted_details = rtrim($formatted_details, ', ');
                }
                
                $formatted_data[] = array(
                    'date' => date('F j, Y, g:i a', strtotime($record->created_at)),
                    'user' => $user_name,
                    'action' => ucwords(str_replace('_', ' ', $record->action_type)),
                    'item' => $record->item_title,
                    'details' => $formatted_details
                );
            }
        }
        
        wp_send_json_success(array(
            'data' => $formatted_data,
            'total' => $history['total'],
            'total_pages' => $history['total_pages'],
            'current_page' => $page
        ));
    }
    
    /**
     * AJAX handler for exporting history to CSV
     */
    public function ajax_export_history() {
        // Check nonce
        if (!check_ajax_referer('vortex_history_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Invalid security token.'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'You do not have permission to export this data.'));
            return;
        }
        
        // Get filter parameters
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $action_type = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        
        // Build filters array
        $filters = array(
            'per_page' => -1, // Get all records
        );
        
        if (!empty($action_type)) {
            $filters['action_type'] = $action_type;
        }
        
        if (!empty($date_from)) {
            $filters['date_from'] = date('Y-m-d 00:00:00', strtotime($date_from));
        }
        
        if (!empty($date_to)) {
            $filters['date_to'] = date('Y-m-d 23:59:59', strtotime($date_to));
        }
        
        // Get history data
        if ($user_id > 0) {
            $history = $this->history_manager->get_user_history($user_id, $filters);
        } else {
            $history = $this->history_manager->get_all_history($filters);
        }
        
        // Generate CSV
        $csv_data = "Date,User ID,User Name,Action Type,Item ID,Item Title,Details\n";
        
        if (!empty($history['records'])) {
            foreach ($history['records'] as $record) {
                $user_info = get_userdata($record->user_id);
                $user_name = $user_info ? $user_info->display_name : 'Unknown User';
                
                // CSV escape fields
                $date = date('Y-m-d H:i:s', strtotime($record->created_at));
                $action_type = $record->action_type;
                $item_id = $record->item_id;
                $item_title = str_replace('"', '""', $record->item_title);
                $details = str_replace('"', '""', $record->action_details);
                
                $csv_data .= "$date,{$record->user_id},\"$user_name\",\"$action_type\",$item_id,\"$item_title\",\"$details\"\n";
            }
        }
        
        // Send CSV data
        $filename = 'vortex-history-export-' . date('Y-m-d') . '.csv';
        wp_send_json_success(array(
            'filename' => $filename,
            'data' => $csv_data
        ));
    }
} 