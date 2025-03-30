<?php
/**
 * Career, Project, and Collaboration features for Vortex AI Marketplace
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes
 */

class Vortex_Career_Project_Collaboration {
    
    /**
     * Initialize the class
     */
    public static function init() {
        // Register shortcodes
        add_action('init', array(__CLASS__, 'register_shortcodes'));
        
        // Register AJAX handlers
        add_action('wp_ajax_vortex_submit_career_path', array(__CLASS__, 'handle_career_path_submission'));
        add_action('wp_ajax_vortex_submit_project_proposal', array(__CLASS__, 'handle_project_proposal_submission'));
        add_action('wp_ajax_vortex_join_collaboration', array(__CLASS__, 'handle_join_collaboration'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'));
    }
    
    /**
     * Register shortcodes
     */
    public static function register_shortcodes() {
        add_shortcode('vortex_career_path', array(__CLASS__, 'render_career_path'));
        add_shortcode('vortex_project_proposals', array(__CLASS__, 'render_project_proposals'));
        add_shortcode('vortex_collaboration_hub', array(__CLASS__, 'render_collaboration_hub'));
    }
    
    /**
     * Enqueue scripts and styles
     */
    public static function enqueue_scripts() {
        wp_enqueue_style(
            'vortex-career-project',
            plugin_dir_url(dirname(__FILE__)) . 'public/css/vortex-career-project.css',
            array(),
            VORTEX_VERSION
        );
        
        wp_enqueue_script(
            'vortex-career-project',
            plugin_dir_url(dirname(__FILE__)) . 'public/js/vortex-career-project.js',
            array('jquery'),
            VORTEX_VERSION,
            true
        );
        
        wp_localize_script('vortex-career-project', 'vortex_career', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_career_project_nonce'),
            'i18n' => array(
                'error' => __('An error occurred. Please try again.', 'vortex-ai-marketplace'),
                'success' => __('Your submission was successful!', 'vortex-ai-marketplace'),
                'generating' => __('Generating AI recommendations...', 'vortex-ai-marketplace'),
                'submitting' => __('Submitting...', 'vortex-ai-marketplace'),
                'update_career' => __('Update Career Path', 'vortex-ai-marketplace'),
                'creating' => __('Creating...', 'vortex-ai-marketplace'),
                'creating_project' => __('Creating your project...', 'vortex-ai-marketplace'),
                'create_project' => __('Create Project', 'vortex-ai-marketplace'),
                'required_skill' => __('Please select at least one required skill.', 'vortex-ai-marketplace'),
                'submit_request' => __('Submit Request', 'vortex-ai-marketplace'),
                'create_collaboration' => __('Create Collaboration', 'vortex-ai-marketplace')
            )
        ));
    }
    
    /**
     * Render career path interface
     */
    public static function render_career_path($atts) {
        // Verify user is logged in
        if (!is_user_logged_in()) {
            return sprintf(
                '<p>%s <a href="%s">%s</a></p>',
                __('You must be logged in to access the career path tools.', 'vortex-ai-marketplace'),
                wp_login_url(get_permalink()),
                __('Log in', 'vortex-ai-marketplace')
            );
        }
        
        // Get user's career stage if it exists
        $user_id = get_current_user_id();
        $career_stage = get_user_meta($user_id, 'vortex_career_stage', true);
        
        // Start output buffering
        ob_start();
        
        // Include career path template
        include plugin_dir_path(dirname(__FILE__)) . 'public/partials/vortex-career-path.php';
        
        return ob_get_clean();
    }
    
    /**
     * Render project proposals interface
     */
    public static function render_project_proposals($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'count' => 10,
            'category' => '',
            'show_filters' => 'yes'
        ), $atts, 'vortex_project_proposals');
        
        // Verify user is logged in
        if (!is_user_logged_in()) {
            return sprintf(
                '<p>%s <a href="%s">%s</a></p>',
                __('You must be logged in to access project proposals.', 'vortex-ai-marketplace'),
                wp_login_url(get_permalink()),
                __('Log in', 'vortex-ai-marketplace')
            );
        }
        
        // Get user data
        $user_id = get_current_user_id();
        $user_skills = get_user_meta($user_id, 'vortex_user_skills', true);
        
        // Get project proposals
        $args = array(
            'post_type' => 'vortex_project',
            'posts_per_page' => intval($atts['count']),
            'post_status' => 'publish'
        );
        
        // Add category filter if provided
        if (!empty($atts['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'project_category',
                    'field'    => 'slug',
                    'terms'    => explode(',', $atts['category']),
                ),
            );
        }
        
        $projects = get_posts($args);
        
        // Start output buffering
        ob_start();
        
        // Include project proposals template
        include plugin_dir_path(dirname(__FILE__)) . 'public/partials/vortex-project-proposals.php';
        
        return ob_get_clean();
    }
    
    /**
     * Render collaboration hub interface
     */
    public static function render_collaboration_hub($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'view' => 'grid', // grid, list
            'count' => 12,
            'show_filters' => 'yes'
        ), $atts, 'vortex_collaboration_hub');
        
        // Verify user is logged in
        if (!is_user_logged_in()) {
            return sprintf(
                '<p>%s <a href="%s">%s</a></p>',
                __('You must be logged in to access the collaboration hub.', 'vortex-ai-marketplace'),
                wp_login_url(get_permalink()),
                __('Log in', 'vortex-ai-marketplace')
            );
        }
        
        // Get user ID
        $user_id = get_current_user_id();
        
        // Get active collaborations
        $args = array(
            'post_type' => 'vortex_collaboration',
            'posts_per_page' => intval($atts['count']),
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => 'vortex_collaboration_status',
                    'value' => 'active',
                    'compare' => '='
                )
            )
        );
        
        $collaborations = get_posts($args);
        
        // Get user's active collaborations
        $user_collaborations = self::get_user_collaborations($user_id);
        
        // Start output buffering
        ob_start();
        
        // Include collaboration hub template
        include plugin_dir_path(dirname(__FILE__)) . 'public/partials/vortex-collaboration-hub.php';
        
        return ob_get_clean();
    }
    
    /**
     * Handle career path submission via AJAX
     */
    public static function handle_career_path_submission() {
        // Verify nonce
        check_ajax_referer('vortex_career_project_nonce', 'nonce');
        
        // Verify user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to submit a career path.', 'vortex-ai-marketplace')
            ));
        }
        
        // Get user ID
        $user_id = get_current_user_id();
        
        // Validate and sanitize input
        $career_stage = isset($_POST['career_stage']) ? sanitize_text_field($_POST['career_stage']) : '';
        $career_goals = isset($_POST['career_goals']) ? sanitize_textarea_field($_POST['career_goals']) : '';
        $interests = isset($_POST['interests']) ? array_map('sanitize_text_field', (array)$_POST['interests']) : array();
        $skills = isset($_POST['skills']) ? array_map('sanitize_text_field', (array)$_POST['skills']) : array();
        
        // Validate required fields
        if (empty($career_stage) || empty($career_goals)) {
            wp_send_json_error(array(
                'message' => __('Please fill in all required fields.', 'vortex-ai-marketplace')
            ));
        }
        
        try {
            // Save career path data
            update_user_meta($user_id, 'vortex_career_stage', $career_stage);
            update_user_meta($user_id, 'vortex_career_goals', $career_goals);
            update_user_meta($user_id, 'vortex_interests', $interests);
            update_user_meta($user_id, 'vortex_user_skills', $skills);
            
            // Get AI recommendations using Business Strategist
            $recommendations = self::get_ai_career_recommendations($user_id, $career_stage, $career_goals, $interests, $skills);
            
            // Save recommendations
            update_user_meta($user_id, 'vortex_career_recommendations', $recommendations);
            
            wp_send_json_success(array(
                'message' => __('Your career path has been updated successfully!', 'vortex-ai-marketplace'),
                'recommendations' => $recommendations
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }
    
    /**
     * Handle project proposal submission via AJAX
     */
    public static function handle_project_proposal_submission() {
        // Verify nonce
        check_ajax_referer('vortex_career_project_nonce', 'nonce');
        
        // Verify user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(array(
                'message' => __('You must be logged in to submit a project proposal.', 'vortex-ai-marketplace')
            ));
        }
        
        // Get user ID
        $user_id = get_current_user_id();
        
        // Validate and sanitize input
        $project_title = isset($_POST['project_title']) ? sanitize_text_field($_POST['project_title']) : '';
        $project_description = isset($_POST['project_description']) ? sanitize_textarea_field($_POST['project_description']) : '';
        $project_category = isset($_POST['project_category']) ? sanitize_text_field($_POST['project_category']) : '';
        $project_timeline = isset($_POST['project_timeline']) ? sanitize_text_field($_POST['project_timeline']) : '';
        $project_budget = isset($_POST['project_budget']) ? floatval($_POST['project_budget']) : 0;
        $skills_required = isset($_POST['skills_required']) ? array_map('sanitize_text_field', (array)$_POST['skills_required']) : array();
        
        // Validate required fields
        if (empty($project_title) || empty($project_description) || empty($project_category) || empty($project_timeline)) {
            wp_send_json_error(array(
                'message' => __('Please fill in all required fields.', 'vortex-ai-marketplace')
            ));
        }
        
        try {
            // Create project post
            $project_data = array(
                'post_title' => $project_title,
                'post_content' => $project_description,
                'post_status' => 'publish',
                'post_type' => 'vortex_project',
                'post_author' => $user_id
            );
            
            $project_id = wp_insert_post($project_data);
            
            if (is_wp_error($project_id)) {
                throw new Exception($project_id->get_error_message());
            }
            
            // Save project meta
            update_post_meta($project_id, 'vortex_project_timeline', $project_timeline);
            update_post_meta($project_id, 'vortex_project_budget', $project_budget);
            update_post_meta($project_id, 'vortex_skills_required', $skills_required);
            update_post_meta($project_id, 'vortex_project_status', 'open');
            
            // Set project category
            if (!empty($project_category)) {
                wp_set_object_terms($project_id, $project_category, 'project_category');
            }
            
            // Process file uploads if any
            if (!empty($_FILES['project_files'])) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                
                $attachment_ids = array();
                
                foreach ($_FILES['project_files']['name'] as $key => $value) {
                    if ($_FILES['project_files']['error'][$key] === 0) {
                        $file = array(
                            'name' => $_FILES['project_files']['name'][$key],
                            'type' => $_FILES['project_files']['type'][$key],
                            'tmp_name' => $_FILES['project_files']['tmp_name'][$key],
                            'error' => $_FILES['project_files']['error'][$key],
                            'size' => $_FILES['project_files']['size'][$key]
                        );
                        
                        $attachment_id = media_handle_sideload($file, $project_id);
                        
                        if (!is_wp_error($attachment_id)) {
                            $attachment_ids[] = $attachment_id;
                        }
                    }
                }
                
                if (!empty($attachment_ids)) {
                    update_post_meta($project_id, 'vortex_project_files', $attachment_ids);
                }
            }
            
            // Get AI-powered suggestions for team members using CLOE
            $team_suggestions = self::get_ai_team_suggestions($project_id, $skills_required);
            
            wp_send_json_success(array(
                'message' => __('Your project proposal has been submitted successfully!', 'vortex-ai-marketplace'),
                'project_id' => $project_id,
                'team_suggestions' => $team_suggestions
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }
    
    /**
     * Get user's active collaborations
     */
    private static function get_user_collaborations($user_id) {
        global $wpdb;
        $collaborations = array();
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT collaboration_id, role FROM {$wpdb->prefix}vortex_collaboration_members
            WHERE user_id = %d AND status = 'active'",
            $user_id
        ));
        
        if (!empty($results)) {
            foreach ($results as $result) {
                $collaboration = get_post($result->collaboration_id);
                if ($collaboration && $collaboration->post_status === 'publish') {
                    $collaborations[] = array(
                        'id' => $result->collaboration_id,
                        'title' => $collaboration->post_title,
                        'role' => $result->role,
                        'permalink' => get_permalink($result->collaboration_id)
                    );
                }
            }
        }
        
        return $collaborations;
    }
    
    /**
     * Get AI-powered career recommendations
     */
    private static function get_ai_career_recommendations($user_id, $career_stage, $career_goals, $interests, $skills) {
        // Check if Business Strategist class exists
        if (!class_exists('VORTEX_Business_Strategist')) {
            // Fallback to basic recommendations if AI is not available
            return self::get_fallback_career_recommendations($career_stage);
        }
        
        try {
            // Initialize Business Strategist
            $strategist = new VORTEX_Business_Strategist();
            
            // Prepare data for AI
            $data = array(
                'user_id' => $user_id,
                'career_stage' => $career_stage,
                'career_goals' => $career_goals,
                'interests' => $interests,
                'skills' => $skills
            );
            
            // Get recommendations from Business Strategist
            $result = $strategist->get_career_recommendations($data);
            
            if (is_wp_error($result)) {
                // Log error and fall back to basic recommendations
                error_log('VORTEX AI Error: ' . $result->get_error_message());
                return self::get_fallback_career_recommendations($career_stage);
            }
            
            return $result;
        } catch (Exception $e) {
            // Log error and fall back to basic recommendations
            error_log('VORTEX AI Exception: ' . $e->getMessage());
            return self::get_fallback_career_recommendations($career_stage);
        }
    }
    
    /**
     * Get fallback career recommendations if AI is not available
     */
    private static function get_fallback_career_recommendations($career_stage) {
        $recommendations = array(
            'next_steps' => array(
                __('Update your portfolio with your latest work', 'vortex-ai-marketplace'),
                __('Connect with other artists in your field', 'vortex-ai-marketplace'),
                __('Participate in art challenges to improve your skills', 'vortex-ai-marketplace')
            ),
            'resources' => array(
                __('Online courses in digital art techniques', 'vortex-ai-marketplace'),
                __('Books on art theory and composition', 'vortex-ai-marketplace'),
                __('Tutorials on using AI tools for art creation', 'vortex-ai-marketplace')
            ),
            'milestones' => array(
                __('Create a cohesive portfolio of 10-15 pieces', 'vortex-ai-marketplace'),
                __('Sell your first artwork on the marketplace', 'vortex-ai-marketplace'),
                __('Collaborate with other artists on a project', 'vortex-ai-marketplace')
            )
        );
        
        return $recommendations;
    }
    
    /**
     * Get AI-powered team suggestions for a project
     */
    private static function get_ai_team_suggestions($project_id, $skills_required) {
        // Check if CLOE class exists
        if (!class_exists('VORTEX_CLOE')) {
            // Fallback to basic suggestions if AI is not available
            return array();
        }
        
        try {
            // Initialize CLOE
            $cloe = new VORTEX_CLOE();
            
            // Get project details
            $project = get_post($project_id);
            $project_description = $project->post_content;
            $project_budget = get_post_meta($project_id, 'vortex_project_budget', true);
            $project_timeline = get_post_meta($project_id, 'vortex_project_timeline', true);
            
            // Prepare data for AI
            $data = array(
                'project_id' => $project_id,
                'project_title' => $project->post_title,
                'project_description' => $project_description,
                'skills_required' => $skills_required,
                'project_budget' => $project_budget,
                'project_timeline' => $project_timeline
            );
            
            // Get team suggestions from CLOE
            $result = $cloe->get_team_suggestions($data);
            
            if (is_wp_error($result)) {
                // Log error
                error_log('VORTEX AI Error: ' . $result->get_error_message());
                return array();
            }
            
            return $result;
        } catch (Exception $e) {
            // Log error
            error_log('VORTEX AI Exception: ' . $e->getMessage());
            return array();
        }
    }
} 