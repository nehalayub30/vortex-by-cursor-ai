                            <span class="insight-confidence"><?php echo $insight['confidence']; ?>%</span>
                            <span class="insight-time"><?php echo $insight['time_ago']; ?></span>
                        </div>
                        
                        <h4 class="insight-title"><?php echo $insight['title']; ?></h4>
                        <div class="insight-excerpt">
                            <?php echo wp_trim_words($insight['content'], 20, '...'); ?>
                            <a href="<?php echo admin_url('admin.php?page=vortex-insights&insight=' . $insight['id']); ?>" class="insight-view-link">View Details</a>
                        </div>
                    </div>
                    <?php
                        endforeach;
                    else:
                    ?>
                    <div class="vortex-no-insights">
                        <p>No recent insights found. Trigger a learning cycle to generate new insights.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="vortex-card vortex-learning-metrics">
            <div class="vortex-card-header">
                <h2><i class="fas fa-chart-line"></i> Learning Metrics</h2>
                <div class="vortex-card-actions">
                    <button id="vortexExportMetrics" class="button">
                        <i class="fas fa-download"></i> Export Metrics
                    </button>
                </div>
            </div>
            
            <div class="vortex-card-content">
                <div class="vortex-metrics-chart-container">
                    <canvas id="learningMetricsChart" width="400" height="200"></canvas>
                </div>
                
                <div class="vortex-metrics-legend">
                    <div class="metrics-legend-item" style="color: #4e54c8;">
                        <span class="legend-swatch" style="background-color: #4e54c8;"></span>
                        <span class="legend-label">HURAII</span>
                    </div>
                    
                    <div class="metrics-legend-item" style="color: #36b37e;">
                        <span class="legend-swatch" style="background-color: #36b37e;"></span>
                        <span class="legend-label">CLOE</span>
                    </div>
                    
                    <div class="metrics-legend-item" style="color: #ff9f43;">
                        <span class="legend-swatch" style="background-color: #ff9f43;"></span>
                        <span class="legend-label">Business Strategist</span>
                    </div>
                    
                    <div class="metrics-legend-item" style="color: #ff6b6b;">
                        <span class="legend-swatch" style="background-color: #ff6b6b;"></span>
                        <span class="legend-label">Thorius</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="vortex-dashboard-section">
        <div class="vortex-dashboard-header">
            <h2><i class="fas fa-cog"></i> Agent Configuration</h2>
            <p>Adjust settings for AI agent learning and behavior.</p>
        </div>
        
        <div class="vortex-config-form">
            <form id="vortexAgentConfigForm" method="post" action="">
                <?php wp_nonce_field('vortex_agent_config', 'vortex_agent_config_nonce'); ?>
                
                <div class="vortex-form-row">
                    <div class="vortex-form-field">
                        <label for="learningFrequency">Learning Frequency</label>
                        <select id="learningFrequency" name="learning_frequency">
                            <option value="daily" <?php selected(get_option('vortex_learning_frequency', 'daily'), 'daily'); ?>>Daily</option>
                            <option value="every_3_days" <?php selected(get_option('vortex_learning_frequency', 'daily'), 'every_3_days'); ?>>Every 3 Days</option>
                            <option value="weekly" <?php selected(get_option('vortex_learning_frequency', 'daily'), 'weekly'); ?>>Weekly</option>
                            <option value="manual" <?php selected(get_option('vortex_learning_frequency', 'daily'), 'manual'); ?>>Manual Only</option>
                        </select>
                        <p class="field-description">How often agents should automatically run learning cycles.</p>
                    </div>
                    
                    <div class="vortex-form-field">
                        <label for="learningStartTime">Learning Start Time</label>
                        <input type="time" id="learningStartTime" name="learning_start_time" value="<?php echo esc_attr(get_option('vortex_learning_start_time', '02:00')); ?>">
                        <p class="field-description">The time of day when automatic learning should begin (server time, 24h format).</p>
                    </div>
                </div>
                
                <div class="vortex-form-row">
                    <div class="vortex-form-field">
                        <label for="insightRetentionDays">Insight Retention Period (days)</label>
                        <input type="number" id="insightRetentionDays" name="insight_retention_days" min="7" max="365" value="<?php echo intval(get_option('vortex_insight_retention_days', '90')); ?>">
                        <p class="field-description">How long to keep insights before they're archived.</p>
                    </div>
                    
                    <div class="vortex-form-field">
                        <label for="deepLearningDay">Deep Learning Day</label>
                        <select id="deepLearningDay" name="deep_learning_day">
                            <option value="1" <?php selected(get_option('vortex_deep_learning_day', '1'), '1'); ?>>Monday</option>
                            <option value="2" <?php selected(get_option('vortex_deep_learning_day', '1'), '2'); ?>>Tuesday</option>
                            <option value="3" <?php selected(get_option('vortex_deep_learning_day', '1'), '3'); ?>>Wednesday</option>
                            <option value="4" <?php selected(get_option('vortex_deep_learning_day', '1'), '4'); ?>>Thursday</option>
                            <option value="5" <?php selected(get_option('vortex_deep_learning_day', '1'), '5'); ?>>Friday</option>
                            <option value="6" <?php selected(get_option('vortex_deep_learning_day', '1'), '6'); ?>>Saturday</option>
                            <option value="7" <?php selected(get_option('vortex_deep_learning_day', '1'), '7'); ?>>Sunday</option>
                        </select>
                        <p class="field-description">The day of the week when agents perform deep learning (more comprehensive analysis).</p>
                    </div>
                </div>
                
                <div class="vortex-form-row">
                    <div class="vortex-form-field">
                        <label for="enablePublicInsights">Public Insights</label>
                        <label class="switch-toggle">
                            <input type="checkbox" id="enablePublicInsights" name="enable_public_insights" value="1" <?php checked(get_option('vortex_enable_public_insights', '1'), '1'); ?>>
                            <span class="slider"></span>
                        </label>
                        <p class="field-description">Allow visitors to view AI insights on the frontend.</p>
                    </div>
                    
                    <div class="vortex-form-field">
                        <label for="agentConfiguration">Agent Configuration</label>
                        <div class="agent-config-toggles">
                            <?php
                            $agent_configs = get_option('vortex_agent_config', array(
                                'huraii' => true,
                                'cloe' => true,
                                'business_strategist' => true,
                                'thorius' => true
                            ));
                            
                            $agents = array(
                                'huraii' => 'HURAII',
                                'cloe' => 'CLOE',
                                'business_strategist' => 'Business Strategist',
                                'thorius' => 'Thorius'
                            );
                            
                            foreach ($agents as $agent_key => $agent_name):
                            ?>
                            <div class="agent-toggle">
                                <label class="switch-toggle">
                                    <input type="checkbox" name="agent_config[<?php echo $agent_key; ?>]" value="1" <?php checked(isset($agent_configs[$agent_key]) ? $agent_configs[$agent_key] : true, true); ?>>
                                    <span class="slider"></span>
                                </label>
                                <span><?php echo $agent_name; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="field-description">Enable or disable specific agents in the learning cycle.</p>
                    </div>
                </div>
                
                <div class="vortex-form-actions">
                    <button type="submit" class="button button-primary">Save Configuration</button>
                    <button type="button" id="vortexResetDefaultsBtn" class="button">Reset to Defaults</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="vortex-dashboard-footer">
        <p>VORTEX AI Agent System v<?php echo VORTEX_VERSION; ?> | Documentation: <a href="https://vortexmarketplace.io/docs/ai-agents" target="_blank">Agent Guide</a></p>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize the dashboard
    initAgentDashboard();
    
    function initAgentDashboard() {
        const nonce = '<?php echo $nonce; ?>';
        
        // Initialize metrics chart
        initMetricsChart();
        
        // Handle refresh status button
        $('#vortexRefreshStatus').on('click', function() {
            refreshAgentStatus();
        });
        
        // Handle trigger learning button
        $('#vortexTriggerLearning').on('click', function() {
            triggerLearning();
        });
        
        // Handle single agent training buttons
        $('.vortex-trigger-agent-button').on('click', function() {
            const agent = $(this).data('agent');
            triggerLearning(agent);
        });
        
        // Handle export metrics button
        $('#vortexExportMetrics').on('click', function() {
            exportLearningMetrics();
        });
        
        // Handle reset defaults button
        $('#vortexResetDefaultsBtn').on('click', function() {
            if (confirm('Are you sure you want to reset all agent configuration settings to defaults?')) {
                resetConfigDefaults();
            }
        });
        
        // Function to refresh agent status
        function refreshAgentStatus() {
            const refreshBtn = $('#vortexRefreshStatus');
            
            // Show loading state
            refreshBtn.prop('disabled', true);
            refreshBtn.html('<i class="fas fa-spinner fa-spin"></i> Refreshing...');
            
            // Make AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_get_agent_status',
                    security: nonce
                },
                success: function(response) {
                    if (response.success) {
                        updateStatusDisplay(response.data.status);
                    } else {
                        alert('Error refreshing status: ' + (response.data ? response.data.message : 'Unknown error'));
                    }
                    
                    // Reset button
                    refreshBtn.prop('disabled', false);
                    refreshBtn.html('<i class="fas fa-sync-alt"></i> Refresh Status');
                },
                error: function() {
                    alert('Error connecting to server. Please try again.');
                    
                    // Reset button
                    refreshBtn.prop('disabled', false);
                    refreshBtn.html('<i class="fas fa-sync-alt"></i> Refresh Status');
                }
            });
        }
        
        // Function to trigger learning
        function triggerLearning(agent = 'all') {
            // Show loading state on all buttons
            const allBtn = $('#vortexTriggerLearning');
            const agentBtns = $('.vortex-trigger-agent-button');
            const btnText = agent === 'all' ? 'Running Learning Cycle...' : `Training ${agent}...`;
            
            allBtn.prop('disabled', true);
            agentBtns.prop('disabled', true);
            
            if (agent === 'all') {
                allBtn.html(`<i class="fas fa-spinner fa-spin"></i> ${btnText}`);
            } else {
                $(`.vortex-trigger-agent-button[data-agent="${agent}"]`).html(`<i class="fas fa-spinner fa-spin"></i> Training...`);
            }
            
            // Make AJAX request
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_trigger_agent_learning',
                    security: nonce,
                    agent: agent
                },
                success: function(response) {
                    if (response.success) {
                        // Update status after a short delay
                        setTimeout(function() {
                            refreshAgentStatus();
                            
                            // Show success message
                            alert(response.data.message);
                        }, 1000);
                    } else {
                        alert('Error triggering learning: ' + (response.data ? response.data.message : 'Unknown error'));
                        resetButtons();
                    }
                },
                error: function() {
                    alert('Error connecting to server. Please try again.');
                    resetButtons();
                }
            });
            
            function resetButtons() {
                allBtn.prop('disabled', false);
                allBtn.html('<i class="fas fa-graduation-cap"></i> Trigger Learning Cycle');
                
                agentBtns.prop('disabled', false);
                agentBtns.each(function() {
                    const agentName = $(this).data('agent');
                    $(this).html(`Train ${getAgentDisplayName(agentName)}`);
                });
            }
        }
        
        // Function to update status display
        function updateStatusDisplay(status) {
            // Update learning status
            const statusIndicator = $('.vortex-status-indicator');
            if (status.learning_active) {
                statusIndicator.removeClass('inactive').addClass('active');
                statusIndicator.find('.status-text').text('Learning in Progress');
                
                // Disable buttons
                $('#vortexTriggerLearning').prop('disabled', true);
                $('.vortex-trigger-agent-button').prop('disabled', true);
            } else {
                statusIndicator.removeClass('active').addClass('inactive');
                statusIndicator.find('.status-text').text('Idle');
                
                // Enable buttons
                $('#vortexTriggerLearning').prop('disabled', false);
                $('.vortex-trigger-agent-button').prop('disabled', false);
            }
            
            // Update stats
            $('#lastLearningTime').text(status.last_learning_time ? status.last_learning_time : 'Never');
            $('#totalLearningCycles').text(status.learning_cycles);
            $('#totalInsightsGenerated').text(status.total_insights);
            $('#totalArtworksAnalyzed').text(status.total_artworks_analyzed);
            
            // Update agent health
            $.each(status.agent_health, function(agent, health) {
                const agentElement = $(`.vortex-agent-item:has(.agent-icon:contains('${getAgentDisplayName(agent)}')`);
                if (agentElement.length) {
                    agentElement.find('.health-bar-fill').css({
                        'width': `${health.health_score}%`,
                        'background-color': getHealthColor(health.health_score)
                    });
                    
                    agentElement.find('.health-stat:contains("Health:") .stat-value').text(`${health.health_score}%`);
                    agentElement.find('.health-stat:contains("Insights:") .stat-value').text(health.insights_count);
                }
            });
        }
        
        // Initialize metrics chart
        function initMetricsChart() {
            const ctx = document.getElementById('learningMetricsChart').getContext('2d');
            
            // Get learning metrics history from AJAX
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_get_learning_metrics_history',
                    security: nonce
                },
                success: function(response) {
                    if (response.success) {
                        renderMetricsChart(ctx, response.data.metrics);
                    } else {
                        console.error('Error fetching metrics:', response.data ? response.data.message : 'Unknown error');
                    }
                },
                error: function() {
                    console.error('Error connecting to server while fetching metrics');
                }
            });
        }
        
        // Render metrics chart
        function renderMetricsChart(ctx, metricsData) {
            const labels = metricsData.dates;
            
            const datasets = [
                {
                    label: 'HURAII',
                    data: metricsData.huraii_health,
                    borderColor: '#4e54c8',
                    backgroundColor: 'rgba(78, 84, 200, 0.1)',
                    tension: 0.3,
                    fill: false
                },
                {
                    label: 'CLOE',
                    data: metricsData.cloe_health,
                    borderColor: '#36b37e',
                    backgroundColor: 'rgba(54, 179, 126, 0.1)',
                    tension: 0.3,
                    fill: false
                },
                {
                    label: 'Business Strategist',
                    data: metricsData.business_strategist_health,
                    borderColor: '#ff9f43',
                    backgroundColor: 'rgba(255, 159, 67, 0.1)',
                    tension: 0.3,
                    fill: false
                },
                {
                    label: 'Thorius',
                    data: metricsData.thorius_health,
                    borderColor: '#ff6b6b',
                    backgroundColor: 'rgba(255, 107, 107, 0.1)',
                    tension: 0.3,
                    fill: false
                }
            ];
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    scales: {
                        y: {
                            title: {
                                display: true,
                                text: 'Health Score (%)'
                            },
                            min: 0,
                            max: 100
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Date'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                title: function(tooltipItems) {
                                    return tooltipItems[0].label;
                                },
                                label: function(context) {
                                    return `${context.dataset.label}: ${context.raw}%`;
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Function to export learning metrics
        function exportLearningMetrics() {
            window.location.href = ajaxurl + '?action=vortex_export_learning_metrics&security=' + nonce;
        }
        
        // Function to reset config to defaults
        function resetConfigDefaults() {
            // Set default values
            $('#learningFrequency').val('daily');
            $('#learningStartTime').val('02:00');
            $('#insightRetentionDays').val('90');
            $('#deepLearningDay').val('1');
            $('#enablePublicInsights').prop('checked', true);
            
            // Enable all agents
            $('.agent-toggle input[type="checkbox"]').prop('checked', true);
            
            // Submit form
            $('#vortexAgentConfigForm').submit();
        }
        
        // Helper function to get agent display name
        function getAgentDisplayName(agentKey) {
            const displayNames = {
                'huraii': 'HURAII',
                'cloe': 'CLOE',
                'business_strategist': 'Business Strategist',
                'thorius': 'Thorius'
            };
            
            return displayNames[agentKey] || agentKey;
        }
        
        // Helper function to get health color
        function getHealthColor(healthScore) {
            if (healthScore >= 80) {
                return '#36b37e'; // Green for good health
            } else if (healthScore >= 60) {
                return '#ff9f43'; // Orange for moderate health
            } else {
                return '#ff6b6b'; // Red for poor health
            }
        }
    }
});
</script> 