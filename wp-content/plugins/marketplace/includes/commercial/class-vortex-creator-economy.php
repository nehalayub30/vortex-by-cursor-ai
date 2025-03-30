            $placeholder = implode(',', array_fill(0, count($all_tags), '%s'));
            $query = $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vortex_creator_trends 
                 WHERE trend_category = 'tag' AND trend_name IN ($placeholder)
                 AND trend_status IN ('emerging', 'growing')
                 ORDER BY confidence_score DESC
                 LIMIT 5",
                $all_tags
            );
            
            $relevant_trends = $wpdb->get_results($query);
        }
        
        // Render panel
        ?>
        <div class="vortex-creator-economy-panel">
            <h3><?php _e('Creator Program Status', 'vortex-marketplace'); ?></h3>
            
            <div class="vortex-creator-tier">
                <?php if ($tier_info): ?>
                    <div class="tier-badge tier-<?php echo esc_attr($tier_info->tier_name); ?>">
                        <?php echo esc_html(ucfirst($tier_info->tier_name)); ?> Creator
                    </div>
                    
                    <div class="tier-details">
                        <div class="tier-benefit">
                            <span class="benefit-label"><?php _e('Your Commission:', 'vortex-marketplace'); ?></span>
                            <span class="benefit-value"><?php echo esc_html($tier_info->commission_rate); ?>%</span>
                        </div>
                        
                        <div class="tier-benefit">
                            <span class="benefit-label"><?php _e('Promotion Boost:', 'vortex-marketplace'); ?></span>
                            <span class="benefit-value"><?php echo esc_html($tier_info->promotion_boost); ?>x</span>
                        </div>
                        
                        <?php if ($tier_info->custom_landing): ?>
                        <div class="tier-benefit">
                            <span class="benefit-label"><?php _e('Custom Landing:', 'vortex-marketplace'); ?></span>
                            <span class="benefit-value"><?php _e('Enabled', 'vortex-marketplace'); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div class="tier-since">
                            <?php printf(__('Member since %s', 'vortex-marketplace'), date_i18n(get_option('date_format'), strtotime($tier_info->assigned_date))); ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="tier-badge tier-none">
                        <?php _e('New Creator', 'vortex-marketplace'); ?>
                    </div>
                    
                    <div class="tier-details">
                        <p><?php _e('You are just starting your journey! Upload more artwork to qualify for our Creator Program tiers and earn increased benefits.', 'vortex-marketplace'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($next_tier): ?>
            <div class="vortex-next-tier">
                <h4><?php _e('Next Tier: ', 'vortex-marketplace'); ?> <?php echo esc_html(ucfirst($next_tier['name'])); ?></h4>
                
                <div class="requirements-progress">
                    <div class="requirement">
                        <span class="req-label"><?php _e('Artworks:', 'vortex-marketplace'); ?></span>
                        <div class="progress-bar">
                            <div class="progress" style="width: <?php echo min(100, ($artwork_count / $next_tier['requirements']['min_artworks']) * 100); ?>%"></div>
                        </div>
                        <span class="req-value"><?php echo $artwork_count; ?> / <?php echo $next_tier['requirements']['min_artworks']; ?></span>
                    </div>
                    
                    <div class="requirement">
                        <span class="req-label"><?php _e('Rating:', 'vortex-marketplace'); ?></span>
                        <div class="progress-bar">
                            <div class="progress" style="width: <?php echo min(100, (($avg_rating ?: 0) / $next_tier['requirements']['min_rating']) * 100); ?>%"></div>
                        </div>
                        <span class="req-value"><?php echo number_format($avg_rating ?: 0, 1); ?> / <?php echo $next_tier['requirements']['min_rating']; ?></span>
                    </div>
                    
                    <?php if (isset($next_tier['requirements']['min_sales'])): ?>
                    <div class="requirement">
                        <span class="req-label"><?php _e('Sales:', 'vortex-marketplace'); ?></span>
                        <div class="progress-bar">
                            <div class="progress" style="width: <?php echo min(100, ($sales_count / $next_tier['requirements']['min_sales']) * 100); ?>%"></div>
                        </div>
                        <span class="req-value"><?php echo $sales_count; ?> / <?php echo $next_tier['requirements']['min_sales']; ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($next_tier['requirements']['min_followers'])): ?>
                    <div class="requirement">
                        <span class="req-label"><?php _e('Followers:', 'vortex-marketplace'); ?></span>
                        <div class="progress-bar">
                            <div class="progress" style="width: <?php echo min(100, ($follower_count / $next_tier['requirements']['min_followers']) * 100); ?>%"></div>
                        </div>
                        <span class="req-value"><?php echo $follower_count; ?> / <?php echo $next_tier['requirements']['min_followers']; ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="next-tier-benefits">
                    <h5><?php _e('Next Tier Benefits:', 'vortex-marketplace'); ?></h5>
                    <ul>
                        <li><?php printf(__('Commission: %s%%', 'vortex-marketplace'), $next_tier['benefits']['commission']); ?></li>
                        <li><?php printf(__('Promotion Boost: %sx', 'vortex-marketplace'), $next_tier['benefits']['promotion_boost']); ?></li>
                        <?php if ($next_tier['benefits']['custom_landing']): ?>
                        <li><?php _e('Custom Landing Page', 'vortex-marketplace'); ?></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($relevant_trends)): ?>
            <div class="vortex-trends-insight">
                <h4><?php _e('Market Insights For You', 'vortex-marketplace'); ?></h4>
                
                <div class="creator-trends">
                    <p><?php _e('Based on your existing artwork, these trends are gaining popularity:', 'vortex-marketplace'); ?></p>
                    
                    <ul class="trends-list">
                        <?php foreach ($relevant_trends as $trend): ?>
                        <li>
                            <span class="trend-name"><?php echo esc_html($trend->trend_name); ?></span>
                            <span class="trend-confidence"><?php echo number_format($trend->confidence_score * 100, 0); ?>% confidence</span>
                            <span class="trend-status trend-<?php echo esc_attr($trend->trend_status); ?>"><?php echo esc_html(ucfirst($trend->trend_status)); ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <p class="trend-tip"><?php _e('Tip: Creating more artwork using these trends may increase your visibility and sales potential.', 'vortex-marketplace'); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <style>
            .vortex-creator-economy-panel {
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                padding: 20px;
                margin-bottom: 30px;
            }
            
            .vortex-creator-tier {
                display: flex;
                align-items: center;
                margin-bottom: 20px;
                padding-bottom: 20px;
                border-bottom: 1px solid #eee;
            }
            
            .tier-badge {
                padding: 8px 16px;
                border-radius: 20px;
                font-weight: bold;
                color: white;
                margin-right: 20px;
            }
            
            .tier-none {
                background-color: #607d8b;
            }
            
            .tier-emerging {
                background-color: #4caf50;
            }
            
            .tier-established {
                background-color: #2196f3;
            }
            
            .tier-signature {
                background-color: #9c27b0;
            }
            
            .tier-elite {
                background: linear-gradient(90deg, #f44336, #ff9800);
            }
            
            .tier-details {
                flex: 1;
            }
            
            .tier-benefit {
                display: flex;
                justify-content: space-between;
                margin-bottom: 5px;
            }
            
            .benefit-value {
                font-weight: bold;
            }
            
            .tier-since {
                font-size: 0.9em;
                color: #666;
                margin-top: 10px;
            }
            
            .requirements-progress {
                margin: 15px 0;
            }
            
            .requirement {
                display: flex;
                align-items: center;
                margin-bottom: 10px;
            }
            
            .req-label {
                width: 80px;
            }
            
            .progress-bar {
                flex: 1;
                height: 8px;
                background: #f1f1f1;
                border-radius: 4px;
                margin: 0 10px;
                overflow: hidden;
            }
            
            .progress {
                height: 100%;
                background: linear-gradient(90deg, #4a6cf7, #46b450);
                border-radius: 4px;
            }
            
            .req-value {
                width: 80px;
                text-align: right;
                font-size: 0.9em;
            }
            
            .next-tier-benefits ul {
                margin: 10px 0;
                padding-left: 20px;
            }
            
            .vortex-trends-insight {
                margin-top: 20px;
                padding-top: 20px;
                border-top: 1px solid #eee;
            }
            
            .trends-list {
                margin: 15px 0;
                padding: 0;
                list-style: none;
            }
            
            .trends-list li {
                display: flex;
                justify-content: space-between;
                padding: 8px 0;
                border-bottom: 1px solid #f1f1f1;
            }
            
            .trend-name {
                font-weight: bold;
            }
            
            .trend-status {
                padding: 2px 8px;
                border-radius: 10px;
                font-size: 0.8em;
                font-weight: bold;
            }
            
            .trend-emerging {
                background-color: #e3f2fd;
                color: #1976d2;
            }
            
            .trend-growing {
                background-color: #e8f5e9;
                color: #388e3c;
            }
            
            .trend-tip {
                font-style: italic;
                font-size: 0.9em;
                color: #666;
                margin-top: 15px;
            }
        </style>
        <?php
    }
    
    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        add_submenu_page(
            'vortex-marketplace',
            __('Creator Economy', 'vortex-marketplace'),
            __('Creator Economy', 'vortex-marketplace'),
            'manage_options',
            'vortex-creator-economy',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        global $wpdb;
        
        // Get creator tiers summary
        $tier_summary = $wpdb->get_results(
            "SELECT tier_name, COUNT(DISTINCT user_id) as creator_count 
             FROM {$wpdb->prefix}vortex_creator_tiers 
             WHERE (user_id, assigned_date) IN (
                SELECT user_id, MAX(assigned_date) 
                FROM {$wpdb->prefix}vortex_creator_tiers 
                GROUP BY user_id
             )
             GROUP BY tier_name"
        );
        
        // Get trend summary
        $trend_summary = $wpdb->get_results(
            "SELECT trend_status, COUNT(*) as trend_count 
             FROM {$wpdb->prefix}vortex_creator_trends 
             GROUP BY trend_status"
        );
        
        // Get top trends
        $top_trends = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}vortex_creator_trends 
             ORDER BY confidence_score DESC, first_detected DESC 
             LIMIT 10"
        );
        
        ?>
        <div class="wrap vortex-creator-economy-admin">
            <h1><?php _e('Creator Economy Management', 'vortex-marketplace'); ?></h1>
            
            <div class="vortex-admin-overview">
                <div class="admin-card">
                    <h2><?php _e('Creator Tier Distribution', 'vortex-marketplace'); ?></h2>
                    
                    <div class="tier-distribution">
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Tier', 'vortex-marketplace'); ?></th>
                                    <th><?php _e('Creators', 'vortex-marketplace'); ?></th>
                                    <th><?php _e('Commission', 'vortex-marketplace'); ?></th>
                                    <th><?php _e('Actions', 'vortex-marketplace'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($this->creator_tiers as $tier_name => $tier_data): 
                                    $count = 0;
                                    foreach ($tier_summary as $summary) {
                                        if ($summary->tier_name === $tier_name) {
                                            $count = $summary->creator_count;
                                            break;
                                        }
                                    }
                                ?>
                                <tr>
                                    <td><?php echo esc_html(ucfirst($tier_name)); ?></td>
                                    <td><?php echo intval($count); ?></td>
                                    <td><?php echo esc_html($tier_data['commission']); ?>%</td>
                                    <td>
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=vortex-creators&tier=' . $tier_name)); ?>" class="button button-small">
                                            <?php _e('View Creators', 'vortex-marketplace'); ?>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="admin-card">
                    <h2><?php _e('Trend Analytics', 'vortex-marketplace'); ?></h2>
                    
                    <div class="trend-analytics">
                        <div class="trend-summary">
                            <?php
                            $emerging_count = 0;
                            $growing_count = 0;
                            $established_count = 0;
                            $declining_count = 0;
                            
                            foreach ($trend_summary as $summary) {
                                switch ($summary->trend_status) {
                                    case 'emerging':
                                        $emerging_count = $summary->trend_count;
                                        break;
                                    case 'growing':
                                        $growing_count = $summary->trend_count;
                                        break;
                                    case 'established':
                                        $established_count = $summary->trend_count;
                                        break;
                                    case 'declining':
                                        $declining_count = $summary->trend_count;
                                        break;
                                }
                            }
                            ?>
                            
                            <div class="trend-stat">
                                <span class="trend-label"><?php _e('Emerging Trends:', 'vortex-marketplace'); ?></span>
                                <span class="trend-value trend-emerging"><?php echo intval($emerging_count); ?></span>
                            </div>
                            
                            <div class="trend-stat">
                                <span class="trend-label"><?php _e('Growing Trends:', 'vortex-marketplace'); ?></span>
                                <span class="trend-value trend-growing"><?php echo intval($growing_count); ?></span>
                            </div>
                            
                            <div class="trend-stat">
                                <span class="trend-label"><?php _e('Established Trends:', 'vortex-marketplace'); ?></span>
                                <span class="trend-value trend-established"><?php echo intval($established_count); ?></span>
                            </div>
                            
                            <div class="trend-stat">
                                <span class="trend-label"><?php _e('Declining Trends:', 'vortex-marketplace'); ?></span>
                                <span class="trend-value trend-declining"><?php echo intval($declining_count); ?></span>
                            </div>
                        </div>
                        
                        <a href="<?php echo esc_url(admin_url('admin.php?page=vortex-trend-analysis')); ?>" class="button button-primary">
                            <?php _e('View All Trends', 'vortex-marketplace'); ?>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="vortex-top-trends">
                <h2><?php _e('Top Emerging Trends', 'vortex-marketplace'); ?></h2>
                
                <?php if (!empty($top_trends)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Trend', 'vortex-marketplace'); ?></th>
                            <th><?php _e('Category', 'vortex-marketplace'); ?></th>
                            <th><?php _e('Confidence', 'vortex-marketplace'); ?></th>
                            <th><?php _e('Status', 'vortex-marketplace'); ?></th>
                            <th><?php _e('Detected', 'vortex-marketplace'); ?></th>
                            <th><?php _e('Market Potential', 'vortex-marketplace'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_trends as $trend): ?>
                        <tr>
                            <td><?php echo esc_html($trend->trend_name); ?></td>
                            <td><?php echo esc_html(ucfirst($trend->trend_category)); ?></td>
                            <td><?php echo number_format($trend->confidence_score * 100, 1); ?>%</td>
                            <td>
                                <span class="trend-badge trend-<?php echo esc_attr($trend->trend_status); ?>">
                                    <?php echo esc_html(ucfirst($trend->trend_status)); ?>
                                </span>
                            </td>
                            <td><?php echo human_time_diff(strtotime($trend->first_detected), current_time('timestamp')); ?> ago</td>
                            <td><?php echo number_format($trend->market_potential * 100, 1); ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p><?php _e('No trends detected yet. The system will automatically detect emerging trends as more artwork is added.', 'vortex-marketplace'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <style>
            .vortex-creator-economy-admin {
                max-width: 1200px;
            }
            
            .vortex-admin-overview {
                display: flex;
                gap: 20px;
                margin-bottom: 30px;
            }
            
            .admin-card {
                flex: 1;
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                padding: 20px;
            }
            
            .trend-summary {
                margin-bottom: 20px;
            }
            
            .trend-stat {
                display: flex;
                justify-content: space-between;
                margin-bottom: 10px;
                padding-bottom: 10px;
                border-bottom: 1px solid #f1f1f1;
            }
            
            .trend-value {
                font-weight: bold;
                padding: 3px 8px;
                border-radius: 12px;
            }
            
            .trend-emerging {
                background-color: #e3f2fd;
                color: #1976d2;
            }
            
            .trend-growing {
                background-color: #e8f5e9;
                color: #388e3c;
            }
            
            .trend-established {
                background-color: #fff3e0;
                color: #e65100;
            }
            
            .trend-declining {
                background-color: #ffebee;
                color: #c62828;
            }
            
            .trend-badge {
                display: inline-block;
                padding: 3px 8px;
                border-radius: 12px;
                font-size: 12px;
            }
            
            .vortex-top-trends {
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                padding: 20px;
            }
        </style>
        <?php
    }
}

// Initialize Creator Economy
add_action('plugins_loaded', function() {
    VORTEX_Creator_Economy::get_instance();
}); 