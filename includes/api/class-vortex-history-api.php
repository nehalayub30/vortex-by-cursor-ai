<?php
/**
 * VORTEX History API Class
 *
 * Provides REST API endpoints for accessing history data
 */

class VORTEX_History_API {
    private $history_manager;
    private $namespace = 'vortex/v1';
    
    public function __construct() {
        $this->history_manager = new VORTEX_History_Manager();
        
        // Register REST routes
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Register API routes
     */
    public function register_routes() {
        // Get user history
        register_rest_route($this->namespace, '/history/user/(?P<user_id>\d+)', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_user_history'),
            'permission_callback' => array($this, 'check_user_permission'),
            'args' => $this->get_collection_params()
        ));
        
        // Get all history (admin only)
        register_rest_route($this->namespace, '/history/all', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_all_history'),
            'permission_callback' => array($this, 'check_admin_permission'),
            'args' => $this->get_collection_params()
        ));
    }
    
    /**
     * Check if user has permission to view this user's history
     */
    public function check_user_permission($request) {
        $user_id = (int) $request['user_id'];
        $current_user_id = get_current_user_id();
        
        // Users can view their own history, admins can view any user's history
        if ($current_user_id === $user_id || current_user_can('manage_options')) {
            return true;
        }
        
        return new WP_Error(
            'rest_forbidden',
            __('You cannot view this user\'s history.', 'vortex-marketplace'),
            array('status' => 403)
        );
    }
    
    /**
     * Check if user has admin permission
     */
    public function check_admin_permission() {
        if (current_user_can('manage_options')) {
            return true;
        }
        
        return new WP_Error(
            'rest_forbidden',
            __('You do not have permission to view all history.', 'vortex-marketplace'),
            array('status' => 403)
        );
    }
    
    /**
     * Get user history
     */
    public function get_user_history($request) {
        $user_id = (int) $request['user_id'];
        $filters = $this->prepare_filters($request);
        
        $history = $this->history_manager->get_user_history($user_id, $filters);
        
        return rest_ensure_response($this->format_history_data($history));
    }
    
    /**
     * Get all history
     */
    public function get_all_history($request) {
        $filters = $this->prepare_filters($request);
        
        $history = $this->history_manager->get_all_history($filters);
        
        return rest_ensure_response($this->format_history_data($history));
    }
    
    /**
     * Prepare filters from request parameters
     */
    private function prepare_filters($request) {
        $filters = array();
        
        // Pagination
        $filters['per_page'] = $request->get_param('per_page') ? (int) $request->get_param('per_page') : 20;
        $filters['page'] = $request->get_param('page') ? (int) $request->get_param('page') : 1;
        
        // Other filters
        if ($request->get_param('action_type')) {
            $filters['action_type'] = sanitize_text_field($request->get_param('action_type'));
        }
        
        if ($request->get_param('date_from')) {
            $filters['date_from'] = sanitize_text_field($request->get_param('date_from'));
        }
        
        if ($request->get_param('date_to')) {
            $filters['date_to'] = sanitize_text_field($request->get_param('date_to'));
        }
        
        if ($request->get_param('item_id')) {
            $filters['item_id'] = (int) $request->get_param('item_id');
        }
        
        return $filters;
    }
    
    /**
     * Format history data for API response
     */
    private function format_history_data($history) {
        $data = array(
            'total' => $history['total'],
            'total_pages' => $history['total_pages'],
            'records' => array()
        );
        
        if (!empty($history['records'])) {
            foreach ($history['records'] as $record) {
                $user_info = get_userdata($record->user_id);
                $username = $user_info ? $user_info->user_login : '';
                $display_name = $user_info ? $user_info->display_name : '';
                
                $data['records'][] = array(
                    'id' => (int) $record->id,
                    'user_id' => (int) $record->user_id,
                    'username' => $username,
                    'display_name' => $display_name,
                    'action_type' => $record->action_type,
                    'item_id' => (int) $record->item_id,
                    'item_title' => $record->item_title,
                    'action_details' => json_decode($record->action_details, true),
                    'created_at' => mysql_to_rfc3339($record->created_at)
                );
            }
        }
        
        return $data;
    }
    
    /**
     * Get collection parameters
     */
    public function get_collection_params() {
        return array(
            'page' => array(
                'description' => __('Current page of the collection.', 'vortex-marketplace'),
                'type' => 'integer',
                'default' => 1,
                'minimum' => 1,
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'per_page' => array(
                'description' => __('Maximum number of items to be returned in result set.', 'vortex-marketplace'),
                'type' => 'integer',
                'default' => 20,
                'minimum' => 1,
                'maximum' => 100,
                'sanitize_callback' => 'absint',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'action_type' => array(
                'description' => __('Filter by action type.', 'vortex-marketplace'),
                'type' => 'string',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'date_from' => array(
                'description' => __('Filter by date from (YYYY-MM-DD).', 'vortex-marketplace'),
                'type' => 'string',
                'format' => 'date',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'date_to' => array(
                'description' => __('Filter by date to (YYYY-MM-DD).', 'vortex-marketplace'),
                'type' => 'string',
                'format' => 'date',
                'validate_callback' => 'rest_validate_request_arg',
            ),
            'item_id' => array(
                'description' => __('Filter by item ID.', 'vortex-marketplace'),
                'type' => 'integer',
                'validate_callback' => 'rest_validate_request_arg',
            ),
        );
    }
} 