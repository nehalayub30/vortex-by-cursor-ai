<?php
/**
 * Thorius Synthesis Reports
 * 
 * Provides comprehensive behavioral analytics and pattern recognition
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/includes/admin
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Thorius Synthesis Reports
 */
class Vortex_Thorius_Synthesis_Reports {
    
    /**
     * Analytics instance
     */
    private $analytics;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Get analytics instance
        require_once plugin_dir_path(dirname(__FILE__)) . 'class-vortex-thorius-analytics.php';
        $this->analytics = new Vortex_Thorius_Analytics();
        
        // Add admin page
        add_action('admin_menu', array($this, 'add_synthesis_page'));
        
        // Add AJAX handlers
        add_action('wp_ajax_vortex_thorius_get_synthesis_report', array($this, 'get_synthesis_report'));
        
        // Add weekly email report
        add_action('vortex_thorius_weekly_report', array($this, 'send_weekly_report'));
        
        // Schedule weekly report if not already scheduled
        if (!wp_next_scheduled('vortex_thorius_weekly_report')) {
            wp_schedule_event(time(), 'weekly', 'vortex_thorius_weekly_report');
        }
    }
    
    /**
     * Add synthesis report page
     */
    public function add_synthesis_page() {
        add_submenu_page(
            'vortex-thorius',
            __('Behavioral Synthesis', 'vortex-ai-marketplace'),
            __('Behavioral Synthesis', 'vortex-ai-marketplace'),
            'manage_options',
            'vortex-thorius-synthesis',
            array($this, 'render_synthesis_page')
        );
    }
    
    /**
     * Render synthesis report page
     */
    public function render_synthesis_page() {
        // Enqueue necessary scripts and styles
        wp_enqueue_script('vortex-thorius-charts', plugin_dir_url(dirname(dirname(__FILE__))) . 'admin/js/chart.min.js', array('jquery'), '3.7.0', true);
        wp_enqueue_script('vortex-thorius-synthesis', plugin_dir_url(dirname(dirname(__FILE__))) . 'admin/js/thorius-synthesis.js', array('jquery', 'vortex-thorius-charts'), VORTEX_THORIUS_VERSION, true);
        
        // Localize script with required data
        wp_localize_script('vortex-thorius-synthesis', 'vortex_thorius_synthesis', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vortex_thorius_synthesis_nonce'),
            'i18n' => array(
                'loading' => __('Loading synthesis data...', 'vortex-ai-marketplace'),
                'error' => __('Error loading data', 'vortex-ai-marketplace')
            )
        ));
        
        include plugin_dir_path(dirname(dirname(__FILE__))) . 'admin/partials/synthesis-report.php';
    }
    
    /**
     * Get synthesis report via AJAX
     */
    public function get_synthesis_report() {
        check_ajax_referer('vortex_thorius_synthesis_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have sufficient permissions.', 'vortex-ai-marketplace'));
            return;
        }
        
        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : '30days';
        $report_type = isset($_POST['report_type']) ? sanitize_text_field($_POST['report_type']) : 'comprehensive';
        
        $report = $this->generate_synthesis_report($period, $report_type);
        
        wp_send_json_success($report);
    }
    
    /**
     * Generate comprehensive synthesis report
     *
     * @param string $period Time period
     * @param string $report_type Report type
     * @return array Report data
     */
    public function generate_synthesis_report($period = '30days', $report_type = 'comprehensive') {
        // Get raw analytics data
        $analytics_data = $this->analytics->get_analytics_data_for_period($period);
        
        // Skip if no data
        if (empty($analytics_data['results'])) {
            return array(
                'status' => 'no_data',
                'message' => __('No data available for the selected period.', 'vortex-ai-marketplace')
            );
        }
        
        // Start building report
        $report = array(
            'status' => 'success',
            'period' => $period,
            'report_type' => $report_type,
            'generated_at' => current_time('mysql'),
            'summary' => array(),
            'trends' => array(),
            'patterns' => array(),
            'recommendations' => array()
        );
        
        // Process based on report type
        switch ($report_type) {
            case 'usage':
                $this->add_usage_metrics($report, $analytics_data);
                break;
                
            case 'agent_performance':
                $this->add_agent_performance_metrics($report, $analytics_data);
                break;
                
            case 'content_analysis':
                $this->add_content_analysis($report, $analytics_data);
                break;
                
            case 'comprehensive':
            default:
                $this->add_usage_metrics($report, $analytics_data);
                $this->add_agent_performance_metrics($report, $analytics_data);
                $this->add_content_analysis($report, $analytics_data);
                $this->add_comprehensive_insights($report, $analytics_data);
                break;
        }
        
        // Add recommendations based on the data
        $this->generate_recommendations($report, $analytics_data);
        
        return $report;
    }
    
    /**
     * Add usage metrics to report
     */
    private function add_usage_metrics(&$report, $analytics_data) {
        $results = $analytics_data['results'];
        
        // User engagement metrics
        $engagement = array(
            'total_sessions' => 0,
            'avg_session_duration' => 0,
            'returning_users' => 0,
            'new_users' => 0,
            'usage_by_time' => $this->analyze_usage_by_time($results),
            'usage_by_day' => $this->analyze_usage_by_day($results)
        );
        
        // Calculate session metrics from events
        $sessions = $this->extract_sessions($results);
        $engagement['total_sessions'] = count($sessions);
        
        if (!empty($sessions)) {
            $total_duration = 0;
            foreach ($sessions as $session) {
                $total_duration += $session['duration'];
            }
            $engagement['avg_session_duration'] = $total_duration / count($sessions);
        }
        
        // Get unique users
        $unique_users = array();
        $unique_ips = array();
        
        foreach ($results as $event) {
            if (!empty($event['user_id']) && !in_array($event['user_id'], $unique_users)) {
                $unique_users[] = $event['user_id'];
            }
            
            if (!empty($event['ip_address']) && !in_array($event['ip_address'], $unique_ips)) {
                $unique_ips[] = $event['ip_address'];
            }
        }
        
        // Count returning vs new users (simplified approximation)
        $engagement['returning_users'] = count($unique_users);
        $engagement['new_users'] = count($unique_ips) - count($unique_users);
        if ($engagement['new_users'] < 0) $engagement['new_users'] = 0;
        
        $report['summary']['engagement'] = $engagement;
        
        // Feature usage
        $report['summary']['feature_usage'] = $this->analyze_feature_usage($results);
    }
    
    /**
     * Add agent performance metrics to report
     */
    private function add_agent_performance_metrics(&$report, $analytics_data) {
        $results = $analytics_data['results'];
        
        // Filter only agent requests
        $agent_events = array_filter($results, function($event) {
            return $event['event_type'] === 'agent_request';
        });
        
        $agents = array(
            'cloe' => array(
                'total_requests' => 0,
                'avg_response_time' => 0,
                'successful_requests' => 0,
                'failed_requests' => 0,
                'most_common_topics' => array(),
                'request_complexity' => 0
            ),
            'huraii' => array(
                'total_requests' => 0,
                'avg_response_time' => 0,
                'successful_requests' => 0,
                'failed_requests' => 0,
                'popular_styles' => array(),
                'avg_generation_time' => 0
            ),
            'strategist' => array(
                'total_requests' => 0,
                'avg_response_time' => 0,
                'successful_requests' => 0,
                'failed_requests' => 0,
                'analysis_types' => array()
            )
        );
        
        $total_response_times = array(
            'cloe' => 0,
            'huraii' => 0,
            'strategist' => 0
        );
        
        foreach ($agent_events as $event) {
            $agent = isset($event['event_data']['agent']) ? $event['event_data']['agent'] : '';
            
            if (!isset($agents[$agent])) continue;
            
            // Increment total requests
            $agents[$agent]['total_requests']++;
            
            // Check if successful
            $success = isset($event['event_data']['success']) ? $event['event_data']['success'] : true;
            if ($success) {
                $agents[$agent]['successful_requests']++;
            } else {
                $agents[$agent]['failed_requests']++;
            }
            
            // Track response time if available
            if (isset($event['event_data']['response_time'])) {
                $total_response_times[$agent] += $event['event_data']['response_time'];
            }
            
            // Agent-specific tracking
            switch ($agent) {
                case 'huraii':
                    // Track art style
                    if (isset($event['event_data']['style'])) {
                        $style = $event['event_data']['style'];
                        if (!isset($agents[$agent]['popular_styles'][$style])) {
                            $agents[$agent]['popular_styles'][$style] = 0;
                        }
                        $agents[$agent]['popular_styles'][$style]++;
                    }
                    break;
                    
                case 'strategist':
                    // Track analysis type
                    if (isset($event['event_data']['action_type'])) {
                        $action = $event['event_data']['action_type'];
                        if (!isset($agents[$agent]['analysis_types'][$action])) {
                            $agents[$agent]['analysis_types'][$action] = 0;
                        }
                        $agents[$agent]['analysis_types'][$action]++;
                    }
                    break;
                    
                case 'cloe':
                    // Analyze chat complexity (approximation by prompt length)
                    if (isset($event['event_data']['prompt'])) {
                        $agents[$agent]['request_complexity'] += strlen($event['event_data']['prompt']);
                    }
                    break;
            }
        }
        
        // Calculate averages
        foreach ($agents as $agent => $data) {
            if ($data['total_requests'] > 0) {
                $agents[$agent]['avg_response_time'] = $total_response_times[$agent] / $data['total_requests'];
                
                if ($agent === 'cloe') {
                    $agents[$agent]['request_complexity'] = $data['request_complexity'] / $data['total_requests'];
                }
            }
            
            // Sort style/action type arrays
            if ($agent === 'huraii' && !empty($data['popular_styles'])) {
                arsort($agents[$agent]['popular_styles']);
            }
            
            if ($agent === 'strategist' && !empty($data['analysis_types'])) {
                arsort($agents[$agent]['analysis_types']);
            }
        }
        
        $report['summary']['agents'] = $agents;
    }
    
    /**
     * Add content analysis to report
     */
    private function add_content_analysis(&$report, $analytics_data) {
        $results = $analytics_data['results'];
        
        // Filter chat events to analyze common topics
        $chat_events = array_filter($results, function($event) {
            return $event['event_type'] === 'agent_request' && 
                   isset($event['event_data']['agent']) && 
                   $event['event_data']['agent'] === 'cloe' &&
                   isset($event['event_data']['prompt']);
        });
        
        // Extract and analyze topics from prompts
        $topics = $this->extract_topics_from_prompts($chat_events);
        
        // Art generation analysis
        $art_events = array_filter($results, function($event) {
            return $event['event_type'] === 'agent_request' && 
                   isset($event['event_data']['agent']) && 
                   $event['event_data']['agent'] === 'huraii' &&
                   isset($event['event_data']['prompt']);
        });
        
        $art_analysis = $this->analyze_art_prompts($art_events);
        
        // Strategy queries analysis
        $strategy_events = array_filter($results, function($event) {
            return $event['event_type'] === 'agent_request' && 
                   isset($event['event_data']['agent']) && 
                   $event['event_data']['agent'] === 'strategist';
        });
        
        $strategy_analysis = $this->analyze_strategy_queries($strategy_events);
        
        $report['content_analysis'] = array(
            'topics' => $topics,
            'art' => $art_analysis,
            'strategy' => $strategy_analysis
        );
    }
    
    /**
     * Add comprehensive insights to report
     */
    private function add_comprehensive_insights(&$report, $analytics_data) {
        // Calculate growth metrics
        $current_period_count = count($analytics_data['results']);
        
        // Get previous period data for comparison
        $previous_period = $this->get_previous_period($analytics_data['period']);
        $previous_analytics = $this->analytics->get_analytics_data_for_period($previous_period);
        $previous_period_count = count($previous_analytics['results']);
        
        // Calculate growth percentage
        $growth_percentage = 0;
        if ($previous_period_count > 0) {
            $growth_percentage = (($current_period_count - $previous_period_count) / $previous_period_count) * 100;
        }
        
        // User behavior patterns
        $user_patterns = $this->identify_user_patterns($analytics_data['results']);
        
        // Integration with other features
        $integration_stats = $this->analyze_integrations($analytics_data['results']);
        
        $report['insights'] = array(
            'growth' => array(
                'current_period' => $current_period_count,
                'previous_period' => $previous_period_count,
                'growth_percentage' => $growth_percentage,
                'trend' => $growth_percentage >= 0 ? 'positive' : 'negative'
            ),
            'user_patterns' => $user_patterns,
            'integrations' => $integration_stats
        );
    }
    
    /**
     * Generate recommendations based on the data
     */
    private function generate_recommendations(&$report, $analytics_data) {
        $recommendations = array();
        
        // Check if we have agent data
        if (isset($report['summary']['agents'])) {
            $agents = $report['summary']['agents'];
            
            // Identify underutilized features
            foreach ($agents as $agent => $data) {
                if ($data['total_requests'] < 10) { // Arbitrary threshold
                    $recommendations[] = array(
                        'type' => 'feature_promotion',
                        'priority' => 'medium',
                        'message' => sprintf(
                            __('The %s agent is underutilized. Consider promoting its features or improving its visibility.', 'vortex-ai-marketplace'),
                            ucfirst($agent)
                        )
                    );
                }
                
                // Check error rates
                if ($data['total_requests'] > 0) {
                    $error_rate = ($data['failed_requests'] / $data['total_requests']) * 100;
                    if ($error_rate > 10) { // 10% error rate threshold
                        $recommendations[] = array(
                            'type' => 'error_investigation',
                            'priority' => 'high',
                            'message' => sprintf(
                                __('The %s agent has a high error rate of %.1f%%. Consider investigating the causes.', 'vortex-ai-marketplace'),
                                ucfirst($agent),
                                $error_rate
                            )
                        );
                    }
                }
            }
        }
        
        // Growth recommendations
        if (isset($report['insights']['growth'])) {
            $growth = $report['insights']['growth'];
            
            if ($growth['growth_percentage'] < 0) {
                $recommendations[] = array(
                    'type' => 'growth',
                    'priority' => 'high',
                    'message' => __('Usage is declining compared to the previous period. Consider implementing user engagement strategies.', 'vortex-ai-marketplace')
                );
            } elseif ($growth['growth_percentage'] > 50) {
                $recommendations[] = array(
                    'type' => 'performance',
                    'priority' => 'medium',
                    'message' => __('Usage is growing rapidly. Ensure your server resources can handle increased load.', 'vortex-ai-marketplace')
                );
            }
        }
        
        // Feature enhancement recommendations based on content analysis
        if (isset($report['content_analysis'])) {
            if (isset($report['content_analysis']['art']['popular_subjects']) && !empty($report['content_analysis']['art']['popular_subjects'])) {
                $top_subject = key($report['content_analysis']['art']['popular_subjects']);
                $recommendations[] = array(
                    'type' => 'feature_enhancement',
                    'priority' => 'medium',
                    'message' => sprintf(
                        __('Users frequently generate art related to "%s". Consider adding specialized styles or options for this subject.', 'vortex-ai-marketplace'),
                        $top_subject
                    )
                );
            }
        }
        
        // Sort recommendations by priority
        usort($recommendations, function($a, $b) {
            $priority_order = array('high' => 1, 'medium' => 2, 'low' => 3);
            return $priority_order[$a['priority']] - $priority_order[$b['priority']];
        });
        
        $report['recommendations'] = $recommendations;
    }
    
    /**
     * Send weekly report email to admin
     */
    public function send_weekly_report() {
        // Check if email reports are enabled
        if (get_option('vortex_thorius_email_reports', 'yes') !== 'yes') {
            return;
        }
        
        $admin_email = get_option('admin_email');
        if (empty($admin_email)) {
            return;
        }
        
        // Generate report for the last 7 days
        $report = $this->generate_synthesis_report('7days', 'comprehensive');
        
        // Prepare email content
        $subject = sprintf(
            __('[%s] Thorius AI Weekly Activity Synthesis', 'vortex-ai-marketplace'),
            get_bloginfo('name')
        );
        
        ob_start();
        include plugin_dir_path(dirname(dirname(__FILE__))) . 'admin/partials/email-report-template.php';
        $message = ob_get_clean();
        
        // Set content type for HTML email
        add_filter('wp_mail_content_type', function() {
            return 'text/html';
        });
        
        // Send the email
        wp_mail($admin_email, $subject, $message);
        
        // Reset content type
        remove_filter('wp_mail_content_type', function() {
            return 'text/html';
        });
    }
    
    /**
     * Helper methods for data analysis
     */
    
    /**
     * Extract user sessions from events
     */
    private function extract_sessions($events) {
        $sessions = array();
        $current_sessions = array();
        $session_timeout = 30 * 60; // 30 minutes in seconds
        
        // Sort events by time and user
        usort($events, function($a, $b) {
            if ($a['user_id'] === $b['user_id']) {
                return strtotime($a['created_at']) - strtotime($b['created_at']);
            }
            return strcmp($a['user_id'], $b['user_id']);
        });
        
        foreach ($events as $event) {
            $user_key = !empty($event['user_id']) ? $event['user_id'] : $event['ip_address'];
            $event_time = strtotime($event['created_at']);
            
            if (!isset($current_sessions[$user_key])) {
                // Start new session
                $current_sessions[$user_key] = array(
                    'user_key' => $user_key,
                    'start_time' => $event_time,
                    'last_activity' => $event_time,
                    'events' => 1
                );
                continue;
            }
            
            // Check if this event belongs to the current session or starts a new one
            if ($event_time - $current_sessions[$user_key]['last_activity'] > $session_timeout) {
                // Calculate duration and save the completed session
                $current_sessions[$user_key]['duration'] = 
                    $current_sessions[$user_key]['last_activity'] - $current_sessions[$user_key]['start_time'];
                $current_sessions[$user_key]['end_time'] = $current_sessions[$user_key]['last_activity'];
                
                $sessions[] = $current_sessions[$user_key];
                
                // Start new session
                $current_sessions[$user_key] = array(
                    'user_key' => $user_key,
                    'start_time' => $event_time,
                    'last_activity' => $event_time,
                    'events' => 1
                );
            } else {
                // Update existing session
                $current_sessions[$user_key]['last_activity'] = $event_time;
                $current_sessions[$user_key]['events']++;
            }
        }
        
        // Add any remaining active sessions
        foreach ($current_sessions as $session) {
            $session['duration'] = $session['last_activity'] - $session['start_time'];
            $session['end_time'] = $session['last_activity'];
            $sessions[] = $session;
        }
        
        return $sessions;
    }
    
    /**
     * Analyze usage patterns by time of day
     */
    private function analyze_usage_by_time($events) {
        $hours = array_fill(0, 24, 0);
        
        foreach ($events as $event) {
            $hour = (int)date('G', strtotime($event['created_at']));
            $hours[$hour]++;
        }
        
        return $hours;
    }
    
    /**
     * Analyze usage patterns by day of week
     */
    private function analyze_usage_by_day($events) {
        $days = array_fill(0, 7, 0);
        
        foreach ($events as $event) {
            $day = (int)date('w', strtotime($event['created_at']));
            $days[$day]++;
        }
        
        return $days;
    }
    
    /**
     * Analyze feature usage
     */
    private function analyze_feature_usage($events) {
        $features = array(
            'chat' => 0,
            'artwork' => 0,
            'nft' => 0,
            'strategy' => 0
        );
        
        foreach ($events as $event) {
            if ($event['event_type'] !== 'agent_request' || !isset($event['event_data']['agent'])) {
                continue;
            }
            
            $agent = $event['event_data']['agent'];
            $action = isset($event['event_data']['action_type']) ? $event['event_data']['action_type'] : '';
            
            if ($agent === 'cloe') {
                $features['chat']++;
            } elseif ($agent === 'huraii') {
                if ($action === 'nft') {
                    $features['nft']++;
                } else {
                    $features['artwork']++;
                }
            } elseif ($agent === 'strategist') {
                $features['strategy']++;
            }
        }
        
        return $features;
    }
    
    /**
     * Extract common topics from chat prompts
     */
    private function extract_topics_from_prompts($chat_events) {
        $word_count = array();
        $common_words = array('the', 'a', 'an', 'in', 'on', 'at', 'to', 'for', 'with', 'and', 'or', 'but');
        
        foreach ($chat_events as $event) {
            if (!isset($event['event_data']['prompt'])) continue;
            
            $prompt = strtolower($event['event_data']['prompt']);
            $prompt = preg_replace('/[^\w\s]/', '', $prompt); // Remove punctuation
            $words = str_word_count($prompt, 1);
            
            foreach ($words as $word) {
                if (strlen($word) > 3 && !in_array($word, $common_words)) {
                    if (!isset($word_count[$word])) {
                        $word_count[$word] = 0;
                    }
                    $word_count[$word]++;
                }
            }
        }
        
        // Sort and return top words
        arsort($word_count);
        return array_slice($word_count, 0, 20, true);
    }
    
    /**
     * Analyze art prompts
     */
    private function analyze_art_prompts($art_events) {
        $styles = array();
        $subjects = array();
        $word_count = array();
        $common_words = array('the', 'a', 'an', 'in', 'on', 'at', 'to', 'for', 'with', 'and', 'or', 'but');
        
        foreach ($art_events as $event) {
            // Track styles
            if (isset($event['event_data']['style'])) {
                $style = $event['event_data']['style'];
                if (!isset($styles[$style])) {
                    $styles[$style] = 0;
                }
                $styles[$style]++;
            }
            
            // Analyze prompt content
            if (isset($event['event_data']['prompt'])) {
                $prompt = strtolower($event['event_data']['prompt']);
                $prompt = preg_replace('/[^\w\s]/', '', $prompt);
                $words = str_word_count($prompt, 1);
                
                // Extract potential subjects (nouns)
                foreach ($words as $word) {
                    if (strlen($word) > 3 && !in_array($word, $common_words)) {
                        if (!isset($word_count[$word])) {
                            $word_count[$word] = 0;
                        }
                        $word_count[$word]++;
                    }
                }
            }
        }
        
        // Sort result arrays
        arsort($styles);
        arsort($word_count);
        
        // Extract top subjects from word count
        $subjects = array_slice($word_count, 0, 15, true);
        
        return array(
            'popular_styles' => $styles,
            'popular_subjects' => $subjects
        );
    }
    
    /**
     * Analyze strategy queries
     */
    private function analyze_strategy_queries($strategy_events) {
        $analysis_types = array();
        $markets = array();
        
        foreach ($strategy_events as $event) {
            // Track analysis types
            if (isset($event['event_data']['action_type'])) {
                $type = $event['event_data']['action_type'];
                if (!isset($analysis_types[$type])) {
                    $analysis_types[$type] = 0;
                }
                $analysis_types[$type]++;
            }
            
            // Track markets
            if (isset($event['event_data']['market'])) {
                $market = $event['event_data']['market'];
                if (!isset($markets[$market])) {
                    $markets[$market] = 0;
                }
                $markets[$market]++;
            }
        }
        
        // Sort result arrays
        arsort($analysis_types);
        arsort($markets);
        
        return array(
            'analysis_types' => $analysis_types,
            'markets' => $markets
        );
    }
    
    /**
     * Get previous time period for comparison
     */
    private function get_previous_period($period) {
        // Convert current period to previous
        switch ($period) {
            case '7days':
                return '7days_prior';
                
            case '30days':
                return '30days_prior';
                
            case 'quarter':
                return 'quarter_prior';
                
            case 'year':
                return 'year_prior';
                
            default:
                return '30days_prior';
        }
    }
    
    /**
     * Identify user behavior patterns
     */
    private function identify_user_patterns($events) {
        // Extract sessions
        $sessions = $this->extract_sessions($events);
        
        // Default patterns structure
        $patterns = array(
            'single_feature_users' => 0,
            'multi_feature_users' => 0,
            'feature_flow' => array(),
            'common_user_path' => array(),
            'session_duration' => array(
                'short' => 0,  // < 5 minutes
                'medium' => 0, // 5-15 minutes
                'long' => 0    // > 15 minutes
            )
        );
        
        // Analyze sessions
        foreach ($sessions as $session) {
            // Duration categories
            if ($session['duration'] < 300) { // 5 minutes
                $patterns['session_duration']['short']++;
            } elseif ($session['duration'] < 900) { // 15 minutes
                $patterns['session_duration']['medium']++;
            } else {
                $patterns['session_duration']['long']++;
            }
            
            // Extract session events to analyze feature usage
            $session_events = array_filter($events, function($event) use ($session) {
                $event_time = strtotime($event['created_at']);
                return (!empty($event['user_id']) && $event['user_id'] == $session['user_key'] ||
                        !empty($event['ip_address']) && $event['ip_address'] == $session['user_key']) && 
                       $event_time >= $session['start_time'] && 
                       $event_time <= $session['end_time'];
            });
            
            // Check if user used multiple features
            $features_used = array();
            
            foreach ($session_events as $event) {
                if ($event['event_type'] === 'agent_request' && isset($event['event_data']['agent'])) {
                    $feature = $event['event_data']['agent'];
                    
                    if ($feature === 'huraii' && isset($event['event_data']['action_type']) && 
                        $event['event_data']['action_type'] === 'nft') {
                        $feature = 'nft';
                    }
                    
                    if (!in_array($feature, $features_used)) {
                        $features_used[] = $feature;
                    }
                    
                    // Track feature transitions (basic flow)
                    if (!empty($features_used) && count($features_used) > 1) {
                        $from = $features_used[count($features_used) - 2];
                        $to = $features_used[count($features_used) - 1];
                        
                        $transition = $from . '_to_' . $to;
                        if (!isset($patterns['feature_flow'][$transition])) {
                            $patterns['feature_flow'][$transition] = 0;
                        }
                        $patterns['feature_flow'][$transition]++;
                    }
                }
            }
            
            // Single vs multi feature usage
            if (count($features_used) <= 1) {
                $patterns['single_feature_users']++;
            } else {
                $patterns['multi_feature_users']++;
                
                // Track common paths through the app
                $path = implode('->', $features_used);
                if (!isset($patterns['common_user_path'][$path])) {
                    $patterns['common_user_path'][$path] = 0;
                }
                $patterns['common_user_path'][$path]++;
            }
        }
        
        // Sort common paths
        arsort($patterns['common_user_path']);
        arsort($patterns['feature_flow']);
        
        // Limit to top 5 paths
        $patterns['common_user_path'] = array_slice($patterns['common_user_path'], 0, 5, true);
        
        return $patterns;
    }
    
    /**
     * Analyze integration with other WordPress features
     */
    private function analyze_integrations($events) {
        $integration_stats = array(
            'shortcode_usage' => array(
                'thorius_concierge' => 0,
                'thorius_chat' => 0,
                'thorius_agent' => 0
            ),
            'page_types' => array(
                'post' => 0,
                'page' => 0,
                'product' => 0,
                'other' => 0
            )
        );
        
        // Filter initialization events
        $init_events = array_filter($events, function($event) {
            return $event['event_type'] === 'thorius_init';
        });
        
        foreach ($init_events as $event) {
            if (isset($event['event_data']['shortcode'])) {
                $shortcode = $event['event_data']['shortcode'];
                if (isset($integration_stats['shortcode_usage'][$shortcode])) {
                    $integration_stats['shortcode_usage'][$shortcode]++;
                }
            }
            
            if (isset($event['event_data']['post_type'])) {
                $post_type = $event['event_data']['post_type'];
                if (isset($integration_stats['page_types'][$post_type])) {
                    $integration_stats['page_types'][$post_type]++;
                } else {
                    $integration_stats['page_types']['other']++;
                }
            }
        }
        
        return $integration_stats;
    }
} 