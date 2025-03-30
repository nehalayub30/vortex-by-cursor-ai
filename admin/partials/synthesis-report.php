<div class="wrap vortex-thorius-synthesis">
    <h1><?php _e('Thorius AI Behavioral Synthesis', 'vortex-ai-marketplace'); ?></h1>
    
    <div class="synthesis-report-controls">
        <div class="period-selector">
            <label for="synthesis-period"><?php _e('Time Period:', 'vortex-ai-marketplace'); ?></label>
            <select id="synthesis-period">
                <option value="7days"><?php _e('Last 7 Days', 'vortex-ai-marketplace'); ?></option>
                <option value="30days" selected><?php _e('Last 30 Days', 'vortex-ai-marketplace'); ?></option>
                <option value="quarter"><?php _e('Last Quarter', 'vortex-ai-marketplace'); ?></option>
                <option value="year"><?php _e('Last Year', 'vortex-ai-marketplace'); ?></option>
            </select>
        </div>
        
        <div class="report-type-selector">
            <label for="synthesis-report-type"><?php _e('Report Type:', 'vortex-ai-marketplace'); ?></label>
            <select id="synthesis-report-type">
                <option value="comprehensive" selected><?php _e('Comprehensive', 'vortex-ai-marketplace'); ?></option>
                <option value="usage"><?php _e('Usage Patterns', 'vortex-ai-marketplace'); ?></option>
                <option value="agent_performance"><?php _e('Agent Performance', 'vortex-ai-marketplace'); ?></option>
                <option value="content_analysis"><?php _e('Content Analysis', 'vortex-ai-marketplace'); ?></option>
            </select>
        </div>
        
        <button id="generate-synthesis-report" class="button button-primary"><?php _e('Generate Report', 'vortex-ai-marketplace'); ?></button>
    </div>
    
    <div class="synthesis-report-loader" style="display: none;">
        <div class="spinner is-active"></div>
        <p><?php _e('Analyzing user behavior patterns...', 'vortex-ai-marketplace'); ?></p>
    </div>
    
    <div id="synthesis-report-content" class="synthesis-report-content">
        <div class="synthesis-overview">
            <h2><?php _e('Executive Summary', 'vortex-ai-marketplace'); ?></h2>
            <div id="synthesis-summary" class="synthesis-summary">
                <p class="no-data-message"><?php _e('Generate a report to see the executive summary.', 'vortex-ai-marketplace'); ?></p>
            </div>
        </div>
        
        <div class="synthesis-sections">
            <div class="synthesis-section">
                <h2><?php _e('Usage Trends', 'vortex-ai-marketplace'); ?></h2>
                <div class="synthesis-charts">
                    <div class="chart-container">
                        <h3><?php _e('Agent Usage Distribution', 'vortex-ai-marketplace'); ?></h3>
                        <canvas id="agent-usage-chart"></canvas>
                    </div>
                    <div class="chart-container">
                        <h3><?php _e('Daily Activity', 'vortex-ai-marketplace'); ?></h3>
                        <canvas id="daily-activity-chart"></canvas>
                    </div>
                </div>
                <div id="usage-trends-details"></div>
            </div>
            
            <div class="synthesis-section">
                <h2><?php _e('Behavioral Patterns', 'vortex-ai-marketplace'); ?></h2>
                <div class="synthesis-charts">
                    <div class="chart-container">
                        <h3><?php _e('User Journeys', 'vortex-ai-marketplace'); ?></h3>
                        <canvas id="user-journey-chart"></canvas>
                    </div>
                    <div class="chart-container">
                        <h3><?php _e('Feature Transitions', 'vortex-ai-marketplace'); ?></h3>
                        <canvas id="feature-transitions-chart"></canvas>
                    </div>
                </div>
                <div id="behavioral-patterns-details"></div>
            </div>
            
            <div class="synthesis-section">
                <h2><?php _e('Content Analysis', 'vortex-ai-marketplace'); ?></h2>
                <div class="synthesis-charts">
                    <div class="chart-container">
                        <h3><?php _e('Popular Topics (CLOE)', 'vortex-ai-marketplace'); ?></h3>
                        <canvas id="topics-chart"></canvas>
                    </div>
                    <div class="chart-container">
                        <h3><?php _e('Art Subjects (HURAII)', 'vortex-ai-marketplace'); ?></h3>
                        <canvas id="art-subjects-chart"></canvas>
                    </div>
                </div>
                <div id="content-analysis-details"></div>
            </div>
            
            <div class="synthesis-section">
                <h2><?php _e('Insights & Recommendations', 'vortex-ai-marketplace'); ?></h2>
                <div id="synthesis-recommendations"></div>
            </div>
        </div>
    </div>
    
    <div class="synthesis-actions">
        <button id="download-synthesis-report" class="button"><?php _e('Download Report (PDF)', 'vortex-ai-marketplace'); ?></button>
        <button id="email-synthesis-report" class="button"><?php _e('Email Report', 'vortex-ai-marketplace'); ?></button>
    </div>
</div> 