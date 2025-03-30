<div class="wrap vortex-thorius-intelligence">
    <h1><?php _e('Thorius Intelligence Dashboard', 'vortex-ai-marketplace'); ?></h1>
    
    <div class="intelligence-query-container">
        <div class="intelligence-welcome">
            <h2><?php _e('Ask Thorius Anything About Your Platform', 'vortex-ai-marketplace'); ?></h2>
            <p><?php _e('Get real-time insights about your platform usage, marketplace trends, user behavior, or general AI industry knowledge.', 'vortex-ai-marketplace'); ?></p>
        </div>
        
        <div class="intelligence-query-form">
            <div class="query-input-container">
                <input type="text" id="intelligence-query" class="intelligence-query-input" placeholder="<?php esc_attr_e('Ask about platform status, marketplace trends, or industry information...', 'vortex-ai-marketplace'); ?>">
                <button id="intelligence-query-submit" class="button button-primary"><?php _e('Ask Thorius', 'vortex-ai-marketplace'); ?></button>
            </div>
            
            <div class="suggested-queries">
                <p><?php _e('Try asking:', 'vortex-ai-marketplace'); ?></p>
                <div class="query-suggestions">
                    <?php foreach ($this->get_suggested_queries() as $suggestion): ?>
                    <button class="query-suggestion"><?php echo esc_html($suggestion); ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="intelligence-response-container" style="display: none;">
        <div class="intelligence-loader" style="display: none;">
            <div class="spinner is-active"></div>
            <p><?php _e('Thorius is analyzing platform data...', 'vortex-ai-marketplace'); ?></p>
        </div>
        
        <div class="intelligence-response">
            <div class="response-header">
                <h3><?php _e('Thorius Response', 'vortex-ai-marketplace'); ?></h3>
                <div class="response-meta">
                    <span class="response-timestamp"></span>
                    <span class="refresh-data" title="<?php esc_attr_e('Refresh Data', 'vortex-ai-marketplace'); ?>"><span class="dashicons dashicons-update"></span></span>
                </div>
            </div>
            
            <div class="response-content">
                <div id="response-narrative" class="response-narrative"></div>
                
                <div id="response-data" class="response-data">
                    <div id="platform-stats-section" class="data-section" style="display: none;">
                        <h4><?php _e('Platform Statistics', 'vortex-ai-marketplace'); ?></h4>
                        <div class="data-visualization platform-stats-visualization"></div>
                    </div>
                    
                    <div id="user-activity-section" class="data-section" style="display: none;">
                        <h4><?php _e('User Activity', 'vortex-ai-marketplace'); ?></h4>
                        <div class="data-visualization user-activity-visualization"></div>
                    </div>
                    
                    <div id="marketplace-trends-section" class="data-section" style="display: none;">
                        <h4><?php _e('Marketplace Trends', 'vortex-ai-marketplace'); ?></h4>
                        <div class="data-visualization marketplace-trends-visualization"></div>
                    </div>
                    
                    <div id="agent-performance-section" class="data-section" style="display: none;">
                        <h4><?php _e('Agent Performance', 'vortex-ai-marketplace'); ?></h4>
                        <div class="data-visualization agent-performance-visualization"></div>
                    </div>
                    
                    <div id="content-trends-section" class="data-section" style="display: none;">
                        <h4><?php _e('Content Trends', 'vortex-ai-marketplace'); ?></h4>
                        <div class="data-visualization content-trends-visualization"></div>
                    </div>
                    
                    <div id="market-intelligence-section" class="data-section" style="display: none;">
                        <h4><?php _e('Market Intelligence', 'vortex-ai-marketplace'); ?></h4>
                        <div class="data-visualization market-intelligence-visualization"></div>
                    </div>
                    
                    <div id="world-knowledge-section" class="data-section" style="display: none;">
                        <h4><?php _e('Knowledge Insights', 'vortex-ai-marketplace'); ?></h4>
                        <div class="knowledge-insights"></div>
                    </div>
                </div>
                
                <div id="response-insights" class="response-insights">
                    <h4><?php _e('Key Insights', 'vortex-ai-marketplace'); ?></h4>
                    <ul class="insights-list"></ul>
                </div>
                
                <div id="response-actions" class="response-actions">
                    <h4><?php _e('Recommended Actions', 'vortex-ai-marketplace'); ?></h4>
                    <ul class="actions-list"></ul>
                </div>
                
                <div id="response-sources" class="response-sources">
                    <h4><?php _e('Data Sources', 'vortex-ai-marketplace'); ?></h4>
                    <ul class="sources-list"></ul>
                </div>
            </div>
            
            <div class="response-footer">
                <div class="follow-up-questions">
                    <h4><?php _e('Follow-up Questions', 'vortex-ai-marketplace'); ?></h4>
                    <div class="follow-up-suggestions"></div>
                </div>
                <div class="response-actions">
                    <button id="download-intelligence-report" class="button"><?php _e('Download Report', 'vortex-ai-marketplace'); ?></button>
                    <button id="email-intelligence-report" class="button"><?php _e('Email Report', 'vortex-ai-marketplace'); ?></button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="intelligence-history">
        <h3><?php _e('Recent Queries', 'vortex-ai-marketplace'); ?></h3>
        <ul class="query-history-list"></ul>
    </div>
</div> 