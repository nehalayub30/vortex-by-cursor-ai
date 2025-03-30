/**
 * Process quiz submission and generate business plan
 *
 * @param int   $user_id User ID
 * @param array $answers Quiz answers
 * @return array
 */
public function process_quiz_submission($user_id, $answers) {
    try {
        // Validate answers
        if (empty($answers)) {
            throw new Exception(__('No answers provided.', 'vortex'));
        }
        
        // Analyze answers using AI
        $analysis = $this->analyze_quiz_answers($answers);
        
        // Generate business plan
        $business_plan = $this->generate_business_plan($analysis);
        
        // Generate milestones
        $milestones = $this->generate_milestones($analysis);
        
        // Save results
        update_user_meta($user_id, 'vortex_business_analysis', $analysis);
        update_user_meta($user_id, 'vortex_business_plan', $business_plan);
        update_user_meta($user_id, 'vortex_career_milestones', $milestones);
        
        return array(
            'success' => true,
            'data' => array(
                'analysis' => $analysis,
                'business_plan' => $business_plan,
                'milestones' => $milestones
            )
        );
        
    } catch (Exception $e) {
        return array(
            'success' => false,
            'message' => $e->getMessage()
        );
    }
}

/**
 * Analyze quiz answers using AI
 *
 * @param array $answers Quiz answers
 * @return array
 */
private function analyze_quiz_answers($answers) {
    // Initialize AI models
    $this->initialize_learning_models();
    
    // Process answers through AI models
    $analysis = array(
        'business_type' => $this->determine_business_type($answers),
        'growth_potential' => $this->assess_growth_potential($answers),
        'market_focus' => $this->identify_market_focus($answers),
        'resource_needs' => $this->evaluate_resource_needs($answers),
        'risk_factors' => $this->identify_risk_factors($answers),
        'success_metrics' => $this->define_success_metrics($answers)
    );
    
    return apply_filters('vortex_business_analysis', $analysis, $answers);
}

/**
 * Generate business plan based on analysis
 *
 * @param array $analysis Business analysis
 * @return array
 */
private function generate_business_plan($analysis) {
    return array(
        'overview' => $this->generate_business_overview($analysis),
        'marketing_strategy' => $this->generate_marketing_strategy($analysis),
        'action_plan' => $this->generate_action_plan($analysis),
        'resources' => $this->generate_resource_list($analysis),
        'metrics' => $this->generate_success_metrics($analysis)
    );
}

/**
 * Generate career milestones based on analysis
 *
 * @param array $analysis Business analysis
 * @return array
 */
private function generate_milestones($analysis) {
    return array(
        'short_term' => array(
            'timeline' => '0-3 months',
            'goals' => $this->generate_short_term_milestones($analysis)
        ),
        'mid_term' => array(
            'timeline' => '3-12 months',
            'goals' => $this->generate_mid_term_milestones($analysis)
        ),
        'long_term' => array(
            'timeline' => '1-3 years',
            'goals' => $this->generate_long_term_milestones($analysis)
        )
    );
}

/**
 * Generate business overview
 *
 * @param array $analysis Business analysis
 * @return array
 */
private function generate_business_overview($analysis) {
    return array(
        'summary' => $this->generate_summary($analysis),
        'vision' => $this->generate_vision_statement($analysis),
        'objectives' => $this->generate_objectives($analysis),
        'target_market' => $this->define_target_market($analysis)
    );
}

/**
 * Generate marketing strategy
 *
 * @param array $analysis Business analysis
 * @return array
 */
private function generate_marketing_strategy($analysis) {
    return array(
        'channels' => $this->identify_marketing_channels($analysis),
        'tactics' => $this->recommend_marketing_tactics($analysis),
        'budget' => $this->estimate_marketing_budget($analysis),
        'timeline' => $this->create_marketing_timeline($analysis)
    );
}

/**
 * Generate action plan
 *
 * @param array $analysis Business analysis
 * @return array
 */
private function generate_action_plan($analysis) {
    return array(
        'immediate_actions' => $this->identify_immediate_actions($analysis),
        'short_term_goals' => $this->set_short_term_goals($analysis),
        'long_term_goals' => $this->set_long_term_goals($analysis),
        'contingency_plans' => $this->create_contingency_plans($analysis)
    );
}

/**
 * Generate resource list
 *
 * @param array $analysis Business analysis
 * @return array
 */
private function generate_resource_list($analysis) {
    return array(
        'tools' => $this->identify_required_tools($analysis),
        'skills' => $this->identify_required_skills($analysis),
        'investments' => $this->calculate_required_investments($analysis),
        'support' => $this->identify_support_needs($analysis)
    );
}

/**
 * Generate success metrics
 *
 * @param array $analysis Business analysis
 * @return array
 */
private function generate_success_metrics($analysis) {
    return array(
        'kpis' => $this->define_kpis($analysis),
        'targets' => $this->set_performance_targets($analysis),
        'milestones' => $this->define_success_milestones($analysis),
        'review_schedule' => $this->create_review_schedule($analysis)
    );
}

/**
 * Process template with analysis data
 *
 * @param array|string $template Template to process
 * @param array       $analysis  Business analysis
 * @return array|string
 */
private function process_template($template, $analysis) {
    if (is_array($template)) {
        return array_map(function($item) use ($analysis) {
            return $this->replace_placeholders($item, $analysis);
        }, $template);
    }
    
    return $this->replace_placeholders($template, $analysis);
}

/**
 * Replace placeholders in template
 *
 * @param string $text     Text with placeholders
 * @param array  $analysis Business analysis
 * @return string
 */
private function replace_placeholders($text, $analysis) {
    $placeholders = array(
        '{business_type}' => $analysis['business_type'],
        '{market_focus}' => $analysis['market_focus'],
        '{growth_potential}' => $analysis['growth_potential'],
        '{resource_needs}' => implode(', ', $analysis['resource_needs']),
        '{risk_factors}' => implode(', ', $analysis['risk_factors'])
    );
    
    return str_replace(
        array_keys($placeholders),
        array_values($placeholders),
        $text
    );
}

// Helper methods for analysis
private function determine_business_type($answers) {
    // Implementation based on primary_goal and art_focus
    $type = array(
        'category' => $answers['primary_goal'],
        'focus' => $answers['art_focus'],
        'scale' => $this->determine_business_scale($answers)
    );
    return apply_filters('vortex_business_type', $type, $answers);
}

private function assess_growth_potential($answers) {
    // Implementation based on experience_level and investment_level
    $potential = array(
        'level' => $this->calculate_growth_level($answers),
        'timeline' => $this->estimate_growth_timeline($answers),
        'constraints' => $this->identify_growth_constraints($answers)
    );
    return apply_filters('vortex_growth_potential', $potential, $answers);
}

private function identify_market_focus($answers) {
    // Implementation based on art_focus and primary_goal
    $focus = array(
        'primary_market' => $this->determine_primary_market($answers),
        'secondary_markets' => $this->identify_secondary_markets($answers),
        'niche_opportunities' => $this->find_niche_opportunities($answers)
    );
    return apply_filters('vortex_market_focus', $focus, $answers);
}

private function evaluate_resource_needs($answers) {
    // Implementation based on time_commitment and investment_level
    $needs = array(
        'time' => $this->calculate_time_requirements($answers),
        'financial' => $this->calculate_financial_requirements($answers),
        'skills' => $this->identify_skill_requirements($answers)
    );
    return apply_filters('vortex_resource_needs', $needs, $answers);
}

private function identify_risk_factors($answers) {
    // Implementation based on experience_level and investment_level
    $risks = array(
        'market_risks' => $this->assess_market_risks($answers),
        'operational_risks' => $this->assess_operational_risks($answers),
        'financial_risks' => $this->assess_financial_risks($answers)
    );
    return apply_filters('vortex_risk_factors', $risks, $answers);
}

private function define_success_metrics($answers) {
    // Implementation based on primary_goal and investment_level
    $metrics = array(
        'financial_metrics' => $this->define_financial_metrics($answers),
        'growth_metrics' => $this->define_growth_metrics($answers),
        'engagement_metrics' => $this->define_engagement_metrics($answers)
    );
    return apply_filters('vortex_success_metrics', $metrics, $answers);
}

public function get_insights_stats() {
    return array(
        'plans_generated' => $this->get_total_plans(),
        'success_rate' => $this->calculate_success_rate(),
        'active_users' => $this->get_active_users_count()
    );
}

private function get_total_plans() {
    global $wpdb;
    $table = $wpdb->prefix . 'vortex_business_plans';
    return $wpdb->get_var("SELECT COUNT(*) FROM $table");
}

private function calculate_success_rate() {
    global $wpdb;
    $table = $wpdb->prefix . 'vortex_business_plans';
    $total = $wpdb->get_var("SELECT COUNT(*) FROM $table");
    $successful = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE status = 'completed'");
    return $total > 0 ? round(($successful / $total) * 100) : 0;
}

private function get_active_users_count() {
    global $wpdb;
    $table = $wpdb->prefix . 'vortex_business_plans';
    return $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $table WHERE status = 'active'");
}

/**
 * Generate business strategy
 */
public function generate_strategy($industry, $focus, $details = '') {
    // Add user context
    $user_id = get_current_user_id();
    
    $prompt = "Generate a detailed business strategy for a $focus in the $industry industry.";
    
    if (!empty($details)) {
        $prompt .= " Additional context: $details";
    }
    
    // Enhance with user context
    $prompt = $this->enhance_prompt_with_context($prompt, $user_id);
    
    // Rest of the function...
}

/**
 * Process business idea submission
 * 
 * @since 1.0.0
 */
public function process_business_idea_submission() {
    // Verify nonce
    if (!isset($_POST['business_idea_nonce']) || !wp_verify_nonce($_POST['business_idea_nonce'], 'vortex_business_idea_nonce')) {
        wp_send_json_error(array('message' => __('Security verification failed', 'vortex-ai-marketplace')));
        return;
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('You must be logged in to submit a business idea', 'vortex-ai-marketplace')));
        return;
    }
    
    // Validate required fields
    if (empty($_POST['business_idea'])) {
        wp_send_json_error(array('message' => __('Please provide your business idea', 'vortex-ai-marketplace')));
        return;
    }
    
    // Get all form data
    $user_id = get_current_user_id();
    $business_idea = sanitize_textarea_field($_POST['business_idea']);
    $experience_level = isset($_POST['experience_level']) ? sanitize_text_field($_POST['experience_level']) : 'beginner';
    $time_commitment = isset($_POST['time_commitment']) ? sanitize_text_field($_POST['time_commitment']) : 'part_time';
    $investment_level = isset($_POST['investment_level']) ? sanitize_text_field($_POST['investment_level']) : 'minimal';
    $primary_goal = isset($_POST['primary_goal']) ? sanitize_text_field($_POST['primary_goal']) : 'income';
    $timeline = isset($_POST['timeline']) ? sanitize_text_field($_POST['timeline']) : 'medium';
    
    // Get challenges (if any)
    $challenges = isset($_POST['challenges']) ? array_map('sanitize_text_field', $_POST['challenges']) : array();
    
    // Prepare user context
    $user_role = get_user_meta($user_id, 'vortex_user_role', true);
    $user_categories = get_user_meta($user_id, 'vortex_user_categories', true);
    
    // Process the business idea with AI
    try {
        // Create AI prompt with all context
        $prompt = $this->build_business_plan_prompt(
            $business_idea,
            $experience_level,
            $time_commitment,
            $investment_level,
            $primary_goal,
            $challenges,
            $timeline,
            $user_role,
            $user_categories
        );
        
        // Get detailed analysis and plan from AI
        $business_plan = $this->generate_advanced_business_plan($prompt);
        
        // Store the business plan in the database
        $plan_id = $this->save_business_plan($user_id, $business_plan, $business_idea);
        
        // Generate HTML template with the business plan
        $html = $this->render_business_plan($business_plan, $plan_id);
        
        // Generate PDF version
        $pdf_url = $this->generate_business_plan_pdf($business_plan, $plan_id, $user_id);
        
        // Notify other AI agents about the new business plan
        $this->notify_ai_agents_about_plan($user_id, $plan_id, $business_plan);
        
        // Schedule follow-up notifications
        $this->schedule_business_plan_notifications($user_id, $plan_id);
        
        wp_send_json_success(array(
            'html' => $html,
            'pdf_url' => $pdf_url,
            'plan_id' => $plan_id
        ));
    } catch (Exception $e) {
        wp_send_json_error(array('message' => $e->getMessage()));
    }
}

/**
 * Build comprehensive prompt for business plan generation
 */
private function build_business_plan_prompt($business_idea, $experience_level, $time_commitment, $investment_level, $primary_goal, $challenges, $timeline, $user_role, $user_categories) {
    $prompt = "Generate a comprehensive business plan for this creative business idea: \"$business_idea\"\n\n";
    
    // Add user context
    $prompt .= "CONTEXT:\n";
    $prompt .= "- User Role: " . ($user_role === 'artist' ? 'Creator/Artist' : 'Collector/Buyer') . "\n";
    
    if (!empty($user_categories) && is_array($user_categories)) {
        $prompt .= "- User Categories: " . implode(', ', $user_categories) . "\n";
    }
    
    $prompt .= "- Experience Level: $experience_level\n";
    $prompt .= "- Time Commitment: $time_commitment\n";
    $prompt .= "- Investment Level: $investment_level\n";
    $prompt .= "- Primary Goal: $primary_goal\n";
    
    if (!empty($challenges)) {
        $prompt .= "- Key Challenges: " . implode(', ', $challenges) . "\n";
    }
    
    $prompt .= "- Desired Timeline: $timeline\n\n";
    
    $prompt .= "INSTRUCTIONS:\n";
    $prompt .= "1. Create a structured business plan with the following sections:\n";
    $prompt .= "   - Executive Summary\n";
    $prompt .= "   - Business Concept Overview\n";
    $prompt .= "   - Market Analysis\n";
    $prompt .= "   - Target Audience\n";
    $prompt .= "   - Value Proposition\n";
    $prompt .= "   - Marketing Strategy\n";
    $prompt .= "   - Operational Plan\n";
    $prompt .= "   - Financial Projections\n";
    $prompt .= "   - Implementation Plan\n";
    $prompt .= "   - First 30 Days Milestones (day by day)\n";
    $prompt .= "   - Risk Assessment\n";
    $prompt .= "   - Long-term Vision\n";
    $prompt .= "2. Make the plan practical and actionable\n";
    $prompt .= "3. Focus especially on the first 30 days with detailed daily milestones\n";
    $prompt .= "4. Tailor advice to someone with $experience_level experience level\n";
    $prompt .= "5. Account for the $time_commitment time commitment\n";
    $prompt .= "6. Design the plan to achieve the primary goal: $primary_goal\n";
    $prompt .= "7. Address the specific challenges mentioned\n";
    $prompt .= "8. Format the response with clear headings and bullet points where appropriate\n";
    
    return $prompt;
}

/**
 * Generate advanced business plan using AI
 */
private function generate_advanced_business_plan($prompt) {
    // Initialize AI model (using the appropriate method based on your setup)
    $ai_response = $this->get_ai_response($prompt);
    
    // Parse response into structured format
    $business_plan = $this->parse_ai_response_to_business_plan($ai_response);
    
    return $business_plan;
}

/**
 * Parse AI response into structured business plan
 */
private function parse_ai_response_to_business_plan($response) {
    $business_plan = array();
    
    // Extract sections using regex
    $section_pattern = '/##?\s*(.*?)(?=##?\s*|$)(.*?)(?=##?\s*|$)/s';
    preg_match_all($section_pattern, $response, $matches, PREG_SET_ORDER);
    
    if (empty($matches)) {
        // Alternative parsing for responses without ## formatting
        $lines = explode("\n", $response);
        $current_section = null;
        $current_content = '';
        
        foreach ($lines as $line) {
            // Check if this line is a section header
            if (preg_match('/^([A-Z][A-Za-z\s]+):?\s*$/', $line, $header_match)) {
                // Save previous section if exists
                if ($current_section) {
                    $business_plan[$current_section] = trim($current_content);
                }
                
                // Start new section
                $current_section = trim($header_match[1]);
                $current_content = '';
            } else {
                // Add to current section content
                if ($current_section) {
                    $current_content .= $line . "\n";
                }
            }
        }
        
        // Save last section
        if ($current_section) {
            $business_plan[$current_section] = trim($current_content);
        }
    } else {
        // Process structured response with headers
        foreach ($matches as $match) {
            $section_name = trim($match[1]);
            $section_content = trim($match[2]);
            $business_plan[$section_name] = $section_content;
        }
    }
    
    // If no Executive Summary, try to extract it from the beginning
    if (!isset($business_plan['Executive Summary'])) {
        $first_paragraph_pattern = '/^(.*?)(?=##?\s*|$)/s';
        if (preg_match($first_paragraph_pattern, $response, $first_match)) {
            $business_plan['Executive Summary'] = trim($first_match[0]);
        }
    }
    
    return $business_plan;
}

/**
 * Save the business plan to the database
 */
private function save_business_plan($user_id, $business_plan, $business_idea) {
    global $wpdb;
    
    // Create table if it doesn't exist
    $this->create_business_plan_table();
    
    $table_name = $wpdb->prefix . 'vortex_business_plans';
    
    $wpdb->insert(
        $table_name,
        array(
            'user_id' => $user_id,
            'business_idea' => $business_idea,
            'business_plan' => json_encode($business_plan),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'status' => 'active'
        ),
        array('%d', '%s', '%s', '%s', '%s', '%s')
    );
    
    return $wpdb->insert_id;
}

/**
 * Create business plan table if it doesn't exist
 */
private function create_business_plan_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'vortex_business_plans';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            business_idea text NOT NULL,
            business_plan longtext NOT NULL,
            created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            updated_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            status varchar(20) DEFAULT 'active' NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

/**
 * Render business plan as HTML
 */
private function render_business_plan($business_plan, $plan_id) {
    $html = '<div class="vortex-business-plan">';
    
    // Plan header
    $html .= '<div class="vortex-business-plan-header">';
    $html .= '<h2>' . __('Your Business Strategy', 'vortex-ai-marketplace') . '</h2>';
    $html .= '<p class="vortex-plan-id">' . sprintf(__('Plan ID: %s', 'vortex-ai-marketplace'), $plan_id) . '</p>';
    $html .= '</div>';
    
    // Add download button
    $html .= '<div class="vortex-business-plan-actions">';
    $html .= '<a href="#" id="vortex-download-pdf" class="vortex-button vortex-primary-button">';
    $html .= '<span class="dashicons dashicons-pdf"></span> ' . __('Download as PDF', 'vortex-ai-marketplace');
    $html .= '</a>';
    $html .= '</div>';
    
    // Executive Summary
    if (isset($business_plan['Executive Summary'])) {
        $html .= '<div class="vortex-business-plan-section vortex-plan-summary">';
        $html .= '<h3>' . __('Executive Summary', 'vortex-ai-marketplace') . '</h3>';
        $html .= '<div class="vortex-plan-content">' . wpautop($business_plan['Executive Summary']) . '</div>';
        $html .= '</div>';
    }
    
    // 30-Day Milestones (highlighted)
    if (isset($business_plan['First 30 Days Milestones'])) {
        $html .= '<div class="vortex-business-plan-section vortex-plan-milestones vortex-plan-highlight">';
        $html .= '<h3>' . __('Your First 30 Days', 'vortex-ai-marketplace') . '</h3>';
        $html .= '<div class="vortex-plan-content">' . wpautop($business_plan['First 30 Days Milestones']) . '</div>';
        $html .= '</div>';
    }
    
    // Other sections
    $section_order = array(
        'Business Concept Overview' => __('Business Concept', 'vortex-ai-marketplace'),
        'Market Analysis' => __('Market Analysis', 'vortex-ai-marketplace'),
        'Target Audience' => __('Target Audience', 'vortex-ai-marketplace'),
        'Value Proposition' => __('Value Proposition', 'vortex-ai-marketplace'),
        'Marketing Strategy' => __('Marketing Strategy', 'vortex-ai-marketplace'),
        'Operational Plan' => __('Operations', 'vortex-ai-marketplace'),
        'Financial Projections' => __('Financials', 'vortex-ai-marketplace'),
        'Implementation Plan' => __('Implementation', 'vortex-ai-marketplace'),
        'Risk Assessment' => __('Risks & Mitigation', 'vortex-ai-marketplace'),
        'Long-term Vision' => __('Long-Term Vision', 'vortex-ai-marketplace')
    );
    
    foreach ($section_order as $key => $title) {
        if (isset($business_plan[$key]) && !empty($business_plan[$key])) {
            $html .= '<div class="vortex-business-plan-section vortex-plan-' . sanitize_title($key) . '">';
            $html .= '<h3>' . $title . '</h3>';
            $html .= '<div class="vortex-plan-content">' . wpautop($business_plan[$key]) . '</div>';
            $html .= '</div>';
        }
    }
    
    // Add any additional sections not in our predefined order
    foreach ($business_plan as $key => $content) {
        if (!isset($section_order[$key]) && $key !== 'Executive Summary' && $key !== 'First 30 Days Milestones') {
            $html .= '<div class="vortex-business-plan-section vortex-plan-' . sanitize_title($key) . '">';
            $html .= '<h3>' . $key . '</h3>';
            $html .= '<div class="vortex-plan-content">' . wpautop($content) . '</div>';
            $html .= '</div>';
        }
    }
    
    $html .= '<div class="vortex-business-plan-footer">';
    $html .= '<p>' . __('Generated by Vortex Business Strategist AI', 'vortex-ai-marketplace') . '</p>';
    $html .= '<p>' . __('This plan will be continuously monitored and updated as you progress.', 'vortex-ai-marketplace') . '</p>';
    $html .= '</div>';
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Generate PDF version of the business plan
 */
private function generate_business_plan_pdf($business_plan, $plan_id, $user_id) {
    // Check if we have the PDF generation library
    if (!class_exists('TCPDF')) {
        // If not, link to an endpoint that will generate the PDF on demand
        return add_query_arg(
            array(
                'action' => 'vortex_generate_pdf',
                'plan_id' => $plan_id,
                'user_id' => $user_id,
                'nonce' => wp_create_nonce('vortex_generate_pdf_' . $plan_id)
            ),
            admin_url('admin-ajax.php')
        );
    }
    
    // If we have TCPDF, generate the PDF now
    // (implementation would depend on your PDF library)
    
    // Return the URL to the generated PDF
    $upload_dir = wp_upload_dir();
    $pdf_filename = 'business-plan-' . $plan_id . '.pdf';
    $pdf_path = $upload_dir['basedir'] . '/vortex-plans/' . $pdf_filename;
    $pdf_url = $upload_dir['baseurl'] . '/vortex-plans/' . $pdf_filename;
    
    // Ensure directory exists
    wp_mkdir_p($upload_dir['basedir'] . '/vortex-plans/');
    
    // Here you would generate and save the PDF
    // For this example, we'll just return the URL
    
    return $pdf_url;
}

/**
 * Notify other AI agents about new business plan
 *
 * @param int $user_id User ID
 * @param int $plan_id Plan ID
 * @param array $business_plan Business plan data
 */
private function notify_ai_agents_about_plan($user_id, $plan_id, $business_plan) {
    // Get AI agent instances
    $huraii = Vortex_AI_Marketplace::get_instance()->huraii;
    $cloe = Vortex_AI_Marketplace::get_instance()->cloe;
    
    // Send plan data to HURAII for learning and adaptation
    if ($huraii) {
        $creative_brief = array(
            'business_type' => isset($business_plan['Business Type']) ? $business_plan['Business Type'] : '',
            'target_audience' => isset($business_plan['Target Audience']) ? $business_plan['Target Audience'] : '',
            'key_differentiators' => isset($business_plan['Key Differentiators']) ? $business_plan['Key Differentiators'] : '',
            'visual_identity' => isset($business_plan['Visual Identity Recommendations']) ? $business_plan['Visual Identity Recommendations'] : ''
        );
        
        // Inform HURAII about the new business context for better artwork generation
        $huraii->add_user_context($user_id, 'business_context', $creative_brief);
    }
    
    // Send market data to CLOE for analysis
    if ($cloe) {
        $market_data = array(
            'industry' => isset($business_plan['Industry Analysis']) ? $business_plan['Industry Analysis'] : '',
            'competitors' => isset($business_plan['Competitor Analysis']) ? $business_plan['Competitor Analysis'] : '',
            'market_trends' => isset($business_plan['Market Trends']) ? $business_plan['Market Trends'] : '',
            'target_market' => isset($business_plan['Target Market']) ? $business_plan['Target Market'] : ''
        );
        
        // Inform CLOE about new market context for better market analysis
        $cloe->add_user_business_data($user_id, $market_data);
    }
    
    // Log the integration for analytics
    do_action('vortex_ai_integration_log', array(
        'action' => 'business_plan_created',
        'user_id' => $user_id,
        'plan_id' => $plan_id,
        'huraii_notified' => $huraii ? true : false,
        'cloe_notified' => $cloe ? true : false,
        'timestamp' => current_time('timestamp')
    ));
    
    return true;
}

/**
 * Schedule follow-up notifications for the business plan
 */
private function schedule_business_plan_notifications($user_id, $plan_id) {
    // Set up initial notification for tomorrow
    wp_schedule_single_event(
        strtotime('+1 day'),
        'vortex_business_plan_notification',
        array($user_id, $plan_id, 1) // day 1
    );
    
    // Schedule weekly follow-ups for the first month
    for ($week = 1; $week <= 4; $week++) {
        $day = $week * 7; // day 7, 14, 21, 28
        wp_schedule_single_event(
            strtotime("+{$day} days"),
            'vortex_business_plan_notification',
            array($user_id, $plan_id, $day)
        );
    }
}

/**
 * Send notification to user about their business plan
 */
public function send_business_plan_notification($user_id, $plan_id, $day) {
    // Check if user has enabled notifications
    $notifications_enabled = get_user_meta($user_id, 'vortex_notifications_enabled', true);
    
    if (empty($notifications_enabled) || $notifications_enabled !== 'yes') {
        return false;
    }
    
    // Get business plan
    global $wpdb;
    $table_name = $wpdb->prefix . 'vortex_business_plans';
    $plan = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d AND user_id = %d",
        $plan_id,
        $user_id
    ));
    
    if (!$plan) {
        return false;
    }
    
    $business_plan = json_decode($plan->business_plan, true);
    
    // Get milestone for current day
    $milestone = $this->get_milestone_for_day($business_plan, $day);
    
    // Get user's email
    $user = get_userdata($user_id);
    if (!$user || empty($user->user_email)) {
        return false;
    }
    
    // Generate notification message
    $message = $this->generate_daily_motivation($user_id, $business_plan, $day, $milestone);
    
    // Send email notification
    $subject = sprintf(__('Day %d of Your Business Plan: Keep Going!', 'vortex-ai-marketplace'), $day);
    
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    wp_mail($user->user_email, $subject, $message, $headers);
    
    // Send browser notification if available
    $this->send_browser_notification($user_id, $subject, wp_strip_all_tags($message));
    
    return true;
}

/**
 * Get milestone for specific day
 */
private function get_milestone_for_day($business_plan, $day) {
    // Try to find specific day milestone in the 30-day plan
    if (isset($business_plan['First 30 Days Milestones'])) {
        $milestones_text = $business_plan['First 30 Days Milestones'];
        
        // Try to parse day-specific milestone using regex
        if (preg_match('/Day\s+' . $day . '[\s\:\-]+([^\n]+)/i', $milestones_text, $matches)) {
            return trim($matches[1]);
        }
        
        // Try to find in numbered list
        if (preg_match('/\b' . $day . '[\.\)\-\s]+([^\n]+)/i', $milestones_text, $matches)) {
            return trim($matches[1]);
        }
    }
    
    // If no specific milestone found, generate a motivational message
    $implementation = isset($business_plan['Implementation Plan']) ? $business_plan['Implementation Plan'] : '';
    
    return $this->generate_generic_milestone($day, $implementation);
}

/**
 * Generate daily motivation message
 */
private function generate_daily_motivation($user_id, $business_plan, $day, $milestone) {
    $user = get_userdata($user_id);
    $first_name = $user->first_name ?: $user->display_name;
    
    $html = '<div style="max-width: 600px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;">';
    $html .= '<div style="background-color: #4A26AB; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0;">';
    $html .= '<h1 style="margin: 0; font-size: 24px;">Your Business Plan: Day ' . $day . '</h1>';
    $html .= '</div>';
    $html .= '<div style="background-color: #f9f9f9; padding: 20px; border-radius: 0 0 5px 5px;">';
    $html .= '<p>Hello ' . esc_html($first_name) . ',</p>';
    
    if ($day == 1) {
        $html .= '<p>It\'s your first day implementing your business plan! Here\'s what to focus on today:</p>';
    } elseif ($day % 7 == 0) {
        $html .= '<p>You\'ve completed week ' . ($day / 7) . ' of your business plan journey! Here\'s your focus for today:</p>';
    } else {
        $html .= '<p>Day ' . $day . ' of your business plan journey. Here\'s your milestone for today:</p>';
    }
    
    $html .= '<div style="background-color: white; border-left: 4px solid #4A26AB; padding: 15px; margin: 20px 0;">';
    $html .= '<h3 style="margin-top: 0; color: #4A26AB;">Today\'s Focus:</h3>';
    $html .= '<p>' . esc_html($milestone) . '</p>';
    $html .= '</div>';
    
    // Tips section
    $html .= '<h3 style="color: #4A26AB;">Quick Tips:</h3>';
    $html .= '<ul>';
    
    // Generate contextual tips based on the day
    if ($day <= 7) {
        // First week tips
        $tips = array(
            'Break down your task into smaller, manageable steps',
            'Set aside dedicated time for your business activities',
            'Document your progress and learnings',
            'Reach out to potential mentors in your field',
            'Research your competitors to identify gaps in the market'
        );
    } elseif ($day <= 14) {
        // Second week tips
        $tips = array(
            'Review your progress against your plan',
            'Adjust timelines if needed based on what you\'ve learned',
            'Start building your online presence',
            'Connect with potential collaborators',
            'Test your initial product/service with a small audience'
        );
    } else {
        // Later weeks tips
        $tips = array(
            'Analyze feedback you\'ve received so far',
            'Identify what\'s working well and double down',
            'Consider where you might need additional resources',
            'Plan your next 30 days based on your learnings',
            'Celebrate your progress and small wins'
        );
    }
    
    // Add 3 random tips
    $random_tips = array_rand(array_flip($tips), 3);
    foreach ($random_tips as $tip) {
        $html .= '<li>' . esc_html($tip) . '</li>';
    }
    
    $html .= '</ul>';
    
    // Call to action
    $html .= '<div style="text-align: center; margin-top: 30px;">';
    $html .= '<a href="' . esc_url(home_url('/business-plan/' . $plan_id)) . '" style="background-color: #4A26AB; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block;">View Your Complete Plan</a>';
    $html .= '</div>';
    
    $html .= '<p style="margin-top: 30px;">Keep going! Your creative business journey is unfolding one day at a time.</p>';
    $html .= '<p>The Vortex AI Business Strategist</p>';
    $html .= '</div>';
    $html .= '</div>';
    
    return $html;
}

/**
 * Send browser notification to user
 */
private function send_browser_notification($user_id, $title, $message) {
    // This requires additional frontend setup with service workers
    // Here we're just storing the notification for delivery when the user visits the site
    
    $notifications = get_user_meta($user_id, 'vortex_pending_notifications', true);
    if (!is_array($notifications)) {
        $notifications = array();
    }
    
    $notifications[] = array(
        'title' => $title,
        'message' => $message,
        'time' => current_time('timestamp'),
        'read' => false
    );
    
    update_user_meta($user_id, 'vortex_pending_notifications', $notifications);
    
    return true;
} 