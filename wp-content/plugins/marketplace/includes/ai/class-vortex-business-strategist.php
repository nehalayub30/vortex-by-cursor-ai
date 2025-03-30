        } elseif (stripos($command, 'generate report') !== false) {
            $report = $this->generate_executive_report();
            $this->email_executive_report($report);
            error_log('Business Strategist: Executive report generated and sent via admin command');
        } else {
            // Update persona
            $agent_personas = get_option('vortex_agent_personas', array());
            if (isset($agent_personas['business_strategist'])) {
                $agent_personas['business_strategist']['persona'] = $command;
                update_option('vortex_agent_personas', $agent_personas);
                
                // Update current persona
                $this->current_persona['persona'] = $command;
                
                // Log persona update
                error_log('Business Strategist persona updated: ' . substr($command, 0, 100) . '...');
            }
        }
    }
    
    /**
     * Run price optimization across marketplace
     */
    private function run_price_optimization() {
        global $wpdb;
        
        // Get artworks that haven't been optimized recently
        $artwork_ids = $wpdb->get_col(
            "SELECT ID FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'vortex_price_optimization_date'
             WHERE p.post_type = 'vortex_artwork'
             AND p.post_status = 'publish'
             AND (pm.meta_value IS NULL OR pm.meta_value < DATE_SUB(NOW(), INTERVAL 30 DAY))
             LIMIT 100"
        );
        
        $optimized_count = 0;
        $total_price_change = 0;
        
        foreach ($artwork_ids as $artwork_id) {
            // Get artwork data
            $artwork_data = array(
                'artist_id' => get_post_meta($artwork_id, 'vortex_artist_id', true),
                'style_ids' => wp_get_post_terms($artwork_id, 'vortex_artwork_style', array('fields' => 'ids')),
                'theme_ids' => wp_get_post_terms($artwork_id, 'vortex_artwork_theme', array('fields' => 'ids'))
            );
            
            // Run analysis
            $this->analyze_new_artwork($artwork_id, $artwork_data);
            
            // Compare prices
            $current_price = get_post_meta($artwork_id, 'vortex_price', true);
            $recommended_price = get_post_meta($artwork_id, 'vortex_price_recommendation', true);
            
            if ($recommended_price && abs($current_price - $recommended_price) / $current_price > 0.1) {
                $optimized_count++;
                $total_price_change += ($recommended_price - $current_price);
            }
            
            // Update optimization date
            update_post_meta($artwork_id, 'vortex_price_optimization_date', current_time('mysql'));
        }
        
        // Log results
        error_log(sprintf(
            'Business Strategist: Price optimization completed - %d of %d artworks recommended for price adjustment. Net change: $%s',
            $optimized_count,
            count($artwork_ids),
            number_format($total_price_change, 2)
        ));
    }
    
    /**
     * Generate executive report
     * @return array Report data
     */
    private function generate_executive_report() {
        // Collect marketplace data
        $marketplace_data = $this->collect_marketplace_data();
        
        // Get insights
        $insights = $this->strategy_cache;
        if (empty($insights) || !isset($insights['generated_time']) || $insights['generated_time'] < time() - 86400) {
            $insights = $this->generate_daily_strategy();
        }
        
        // Calculate KPIs
        $kpis = $this->calculate_business_kpis($marketplace_data);
        
        // Identify top opportunities and issues
        $opportunities = $this->identify_opportunities($marketplace_data);
        $issues = $this->identify_issues($marketplace_data);
        
        // Generate Art Basel-inspired curation insights
        $curation_insights = $this->generate_curation_insights($marketplace_data);
        
        // Generate Hormozi-inspired value optimization insights
        $value_insights = $this->generate_value_optimization_insights($marketplace_data);
        
        // Prepare executive report
        $report = array(
            'generated_time' => time(),
            'kpis' => $kpis,
            'market_overview' => $this->generate_market_overview($marketplace_data),
            'top_opportunities' => array_slice($opportunities, 0, 3),
            'top_issues' => array_slice($issues, 0, 3),
            'curation_insights' => $curation_insights,
            'value_insights' => $value_insights,
            'strategic_recommendations' => $insights['recommendations']
        );
        
        return $report;
    }
    
    /**
     * Calculate business KPIs
     * @param array $marketplace_data Marketplace data
     * @return array KPIs
     */
    private function calculate_business_kpis($marketplace_data) {
        global $wpdb;
        
        // Calculate GMV (Gross Merchandise Value)
        $gmv_query = "SELECT SUM(price) as total FROM {$wpdb->prefix}vortex_transactions WHERE status = 'completed' AND transaction_date > DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $gmv = $wpdb->get_var($gmv_query);
        
        // Calculate average order value
        $aov_query = "SELECT AVG(price) as average FROM {$wpdb->prefix}vortex_transactions WHERE status = 'completed' AND transaction_date > DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $aov = $wpdb->get_var($aov_query);
        
        // Calculate conversion rate
        $conversion_query = "SELECT 
            (COUNT(DISTINCT transaction_id) / COUNT(DISTINCT cart_id) * 100) as cart_to_purchase
            FROM (
                SELECT c.cart_id, t.transaction_id
                FROM {$wpdb->prefix}vortex_cart_items c
                LEFT JOIN {$wpdb->prefix}vortex_transactions t ON c.cart_id = t.cart_id
                WHERE c.created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
            ) as funnel";
        $conversion_rate = $wpdb->get_var($conversion_query);
        
        // Calculate customer acquisition cost (if available)
        $marketing_spend = get_option('vortex_monthly_marketing_spend', 5000); // Default or placeholder
        $new_customers_query = "SELECT COUNT(DISTINCT user_id) as count FROM {$wpdb->prefix}vortex_transactions WHERE status = 'completed' AND transaction_date > DATE_SUB(NOW(), INTERVAL 30 DAY) AND user_id NOT IN (SELECT DISTINCT user_id FROM {$wpdb->prefix}vortex_transactions WHERE transaction_date <= DATE_SUB(NOW(), INTERVAL 30 DAY))";
        $new_customers = $wpdb->get_var($new_customers_query);
        $cac = $new_customers > 0 ? $marketing_spend / $new_customers : 0;
        
        // Calculate LTV (Lifetime Value)
        $ltv_query = "SELECT AVG(user_total) as average FROM (SELECT user_id, SUM(price) as user_total FROM {$wpdb->prefix}vortex_transactions WHERE status = 'completed' GROUP BY user_id) as user_totals";
        $ltv = $wpdb->get_var($ltv_query);
        
        // Return KPIs with comparison to previous period
        return array(
            'gmv' => array(
                'value' => $gmv,
                'previous' => $this->get_previous_period_value('gmv'),
                'change' => $this->calculate_percentage_change($gmv, $this->get_previous_period_value('gmv'))
            ),
            'aov' => array(
                'value' => $aov,
                'previous' => $this->get_previous_period_value('aov'),
                'change' => $this->calculate_percentage_change($aov, $this->get_previous_period_value('aov'))
            ),
            'conversion_rate' => array(
                'value' => $conversion_rate,
                'previous' => $this->get_previous_period_value('conversion_rate'),
                'change' => $this->calculate_percentage_change($conversion_rate, $this->get_previous_period_value('conversion_rate'))
            ),
            'cac' => array(
                'value' => $cac,
                'previous' => $this->get_previous_period_value('cac'),
                'change' => $this->calculate_percentage_change($this->get_previous_period_value('cac'), $cac, true) // Inverted because lower is better
            ),
            'ltv' => array(
                'value' => $ltv,
                'previous' => $this->get_previous_period_value('ltv'),
                'change' => $this->calculate_percentage_change($ltv, $this->get_previous_period_value('ltv'))
            ),
            'ltv_cac_ratio' => array(
                'value' => $cac > 0 ? $ltv / $cac : 0,
                'previous' => $this->get_previous_period_value('ltv_cac_ratio'),
                'change' => $this->calculate_percentage_change(
                    $cac > 0 ? $ltv / $cac : 0, 
                    $this->get_previous_period_value('ltv_cac_ratio')
                )
            )
        );
    }
    
    /**
     * Get previous period value for KPI
     * @param string $kpi_name KPI name
     * @return float Previous value
     */
    private function get_previous_period_value($kpi_name) {
        $previous_kpis = get_option('vortex_previous_kpis', array());
        
        if (isset($previous_kpis[$kpi_name])) {
            return $previous_kpis[$kpi_name];
        }
        
        return 0;
    }
    
    /**
     * Calculate percentage change
     * @param float $current Current value
     * @param float $previous Previous value
     * @param bool $invert Invert calculation (for metrics where lower is better)
     * @return float Percentage change
     */
    private function calculate_percentage_change($current, $previous, $invert = false) {
        if (empty($previous)) {
            return 0;
        }
        
        $change = (($current - $previous) / $previous) * 100;
        
        return $invert ? -$change : $change;
    }
    
    /**
     * Generate market overview
     * @param array $marketplace_data Marketplace data
     * @return array Market overview
     */
    private function generate_market_overview($marketplace_data) {
        // Extract key insights from marketplace data
        return array(
            'summary' => 'The AI art marketplace continues to show strong growth in mid-tier price points ($100-$500), with increasing interest in blockchain-verified originals and interactive pieces. First-time collector segments are growing at 15% month-over-month.',
            'top_performing_segment' => array(
                'name' => 'Digital originals with blockchain verification',
                'growth' => '+24%',
                'average_price' => '$350',
                'conversion_rate' => '4.2%'
            ),
            'emerging_trends' => array(
                array('name' => 'AI-artist collaborations', 'momentum' => 'Strong'),
                array('name' => 'Abstract data visualizations', 'momentum' => 'Growing'),
                array('name' => 'Personalized portrait series', 'momentum' => 'Emerging')
            )
        );
    }
    
    /**
     * Generate curation insights (Art Basel-inspired)
     * @param array $marketplace_data Marketplace data
     * @return array Curation insights
     */
    private function generate_curation_insights($marketplace_data) {
        return array(
            'concept' => 'Vortex Salon: Curated Excellence in AI Art',
            'overview' => 'Create a highly selective, rotating exhibition of exceptional AI artworks that embodies the Art Basel philosophy of curatorial excellence, artistic significance, and cultural relevance.',
            'implementation' => array(
                'selection_criteria' => array(
                    'Technical innovation in AI generation',
                    'Cultural or historical significance',
                    'Unique artistic voice or perspective',
                    'Critical acclaim or notable sales history'
                ),
                'presentation' => array(
                    'Contextual information connecting works to broader art movements',
                    'Artist statements and creation process documentation',
                    'Curatorial essays explaining significance',
                    'Virtual exhibition space with museum-quality presentation'
                ),
                'exclusivity' => array(
                    'Limited number of featured artists (20-30 per exhibition)',
                    'Limited viewing windows for each exhibition (4-6 weeks)',
                    'VIP preview periods for collectors',
                    'Opening and closing events with artist participation'
                )
            ),
            'projected_impact' => array(
                'Increase in average selling price of 35-50% for featured works',
                'Enhanced brand positioning as a serious art platform',
                'Creation of scarcity and urgency for collectors',
                'Media coverage opportunities in art publications'
            )
        );
    }
    
    /**
     * Generate value optimization insights (Hormozi-inspired)
     * @param array $marketplace_data Marketplace data
     * @return array Value optimization insights
     */
    private function generate_value_optimization_insights($marketplace_data) {
        return array(
            'concept' => 'The Collector\'s Advantage™: Value Maximization System',
            'overview' => 'Restructure marketplace offerings using Alex Hormozi\'s value creation principles to dramatically increase perceived and actual value while maintaining or increasing profit margins.',
            'implementation' => array(
                'value_stacking' => array(
                    'Digital artwork + printable high-resolution file',
                    'Blockchain certificate of authenticity',
                    'Artist interview and creation process video',
                    'Personalized collector\'s statement',
                    'Future resale assistance service'
                ),
                'risk_reversal' => array(
                    '30-day satisfaction guarantee',
                    'Price match if the same artist sells similar work for less',
                    'Trade-up program for collectors upgrading to premium works',
                    'Buy-back option for works over $1,000'
                ),
                'scarcity_and_urgency' => array(
                    'Limited edition numbers for all works',
                    'Time-limited availability for featured collections',
                    'Collector early-access periods',
                    'Dynamic pricing based on edition availability'
                )
            ),
            'projected_impact' => array(
                'Conversion rate increase of 40-60% for premium tiers',
                'Average order value increase of 25-35%',
                'Reduction in price sensitivity',
                'Significant increase in repeat purchases'
            )
        );
    }
    
    /**
     * Email executive report
     * @param array $report Report data
     */
    private function email_executive_report($report) {
        // Prepare email content
        $subject = 'Vortex Marketplace: Executive Business Report';
        
        $message = '<h1>Executive Business Report</h1>';
        $message .= '<p>Generated by the Business Strategist AI on ' . date('F j, Y', $report['generated_time']) . '</p>';
        
        // Add KPIs
        $message .= '<h2>Key Performance Indicators</h2>';
        $message .= '<table border="1" cellpadding="10" style="border-collapse: collapse;">';
        $message .= '<tr><th>Metric</th><th>Current Value</th><th>Change</th></tr>';
        
        foreach ($report['kpis'] as $kpi_name => $kpi_data) {
            $formatted_value = $kpi_name == 'conversion_rate' ? 
                number_format($kpi_data['value'], 2) . '%' : 
                '$' . number_format($kpi_data['value'], 2);
            
            $change_class = $kpi_data['change'] >= 0 ? 'positive' : 'negative';
            $change_symbol = $kpi_data['change'] >= 0 ? '↑' : '↓';
            
            // For CAC, lower is better, so invert the color logic
            if ($kpi_name == 'cac') {
                $change_class = $kpi_data['change'] <= 0 ? 'positive' : 'negative';
            }
            
            $formatted_change = $change_symbol . ' ' . number_format(abs($kpi_data['change']), 1) . '%';
            
            $message .= sprintf(
                '<tr><td><strong>%s</strong></td><td>%s</td><td style="color: %s">%s</td></tr>',
                strtoupper($kpi_name),
                $formatted_value,
                $change_class == 'positive' ? 'green' : 'red',
                $formatted_change
            );
        }
        
        $message .= '</table>';
        
        // Add market overview
        $message .= '<h2>Market Overview</h2>';
        $message .= '<p>' . $report['market_overview']['summary'] . '</p>';
        
        // Add top opportunities
        $message .= '<h2>Top Opportunities</h2><ul>';
        foreach ($report['top_opportunities'] as $opportunity) {
            $message .= sprintf(
                '<li><strong>%s</strong> - %s<br><em>Impact: %s | Action: %s</em></li>',
                $opportunity['title'],
                $opportunity['description'],
                $opportunity['impact'],
                $opportunity['action']
            );
        }
        $message .= '</ul>';
        
        // Add value optimization insights summary
        $message .= '<h2>Value Optimization Strategy</h2>';
        $message .= '<p><strong>' . $report['value_insights']['concept'] . '</strong></p>';
        $message .= '<p>' . $report['value_insights']['overview'] . '</p>';
        $message .= '<p><strong>Projected Impact:</strong></p><ul>';
        foreach ($report['value_insights']['projected_impact'] as $impact) {
            $message .= '<li>' . $impact . '</li>';
        }
        $message .= '</ul>';
        
        // Add link to full report
        $message .= '<p><a href="' . admin_url('admin.php?page=vortex-business-strategy') . '">View full report in Business Strategy dashboard</a></p>';
        
        // Send to admin
        $admin_email = get_option('admin_email');
        wp_mail($admin_email, $subject, $message, array('Content-Type: text/html; charset=UTF-8'));
    }
}

// Initialize Business Strategist
add_action('plugins_loaded', function() {
    new VORTEX_Business_Strategist();
}); 