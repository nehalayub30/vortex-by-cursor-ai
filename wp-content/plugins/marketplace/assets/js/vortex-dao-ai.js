            // Share insight
            $(document).on('click', '.share-insight-btn', function(e) {
                e.stopPropagation(); // Prevent modal from closing
                const insightId = $(this).data('id');
                const agentName = $(this).data('agent');
                const insightType = $(this).data('type');
                const summary = $(this).data('summary');
                
                VortexDAOAI.shareInsight(insightId, agentName, insightType, summary);
            });
            
            // Search insights
            $(document).on('input', '#insight-search', function() {
                const searchTerm = $(this).val().toLowerCase();
                VortexDAOAI.searchInsights(searchTerm);
            });
            
            // Load more insights
            $(document).on('click', '.load-more-insights', function() {
                VortexDAOAI.loadMoreInsights();
            });
            
            // Integration with notifications
            $(document).on('vortex_notification_received', function(e, notification) {
                if (notification.type === 'ai_insight') {
                    VortexDAOAI.handleNewInsightNotification(notification);
                }
            });
        },
        
        /**
         * Initialize AI insights tab
         */
        initAIInsightsTab: function() {
            // Get current user insights
            this.loadUserInsights();
            
            // Setup filter UI
            this.setupFilterUI();
            
            // Check if there's an insight ID in URL to show
            const urlParams = new URLSearchParams(window.location.search);
            const insightId = urlParams.get('insight');
            if (insightId) {
                setTimeout(() => {
                    this.showInsightDetail(insightId);
                }, 1000); // Delay to ensure insights are loaded
            }
        },
        
        /**
         * Setup filter UI
         */
        setupFilterUI: function() {
            let agentFilters = '<div class="agent-filters">';
            agentFilters += '<button class="agent-filter active" data-agent="all">All Agents</button>';
            
            $.each(vortexDAOAI.ai_agents, function(key, name) {
                agentFilters += `<button class="agent-filter agent-${key}" data-agent="${key}">${name}</button>`;
            });
            
            agentFilters += '</div>';
            
            const typeFilters = `
                <div class="filter-group">
                    <label for="insight-type-filter">Insight Type:</label>
                    <select id="insight-type-filter">
                        <option value="all">All Types</option>
                        <option value="creation">Creation</option>
                        <option value="market">Market</option>
                        <option value="security">Security</option>
                        <option value="trend">Trend</option>
                    </select>
                </div>
            `;
            
            const sortOptions = `
                <div class="filter-group">
                    <label for="insight-sort">Sort By:</label>
                    <select id="insight-sort">
                        <option value="date">Newest First</option>
                        <option value="confidence">Highest Confidence</option>
                        <option value="agent">Agent</option>
                    </select>
                </div>
            `;
            
            const searchBox = `
                <div class="filter-group search-group">
                    <input type="text" id="insight-search" placeholder="Search insights...">
                    <span class="search-icon">🔍</span>
                </div>
            `;
            
            const filtersHtml = `
                <div class="insights-filters">
                    ${agentFilters}
                    <div class="filters-toolbar">
                        ${typeFilters}
                        ${sortOptions}
                        ${searchBox}
                    </div>
                </div>
            `;
            
            // Add filters to the tab
            $('#vortex-ai-insights-tab').prepend(filtersHtml);
            
            // Add insights container
            $('#vortex-ai-insights-tab').append('<div class="insights-container"></div>');
            
            // Add loading indicator
            $('#vortex-ai-insights-tab .insights-container').html(`
                <div class="vortex-loading-animation">
                    <div class="spinner"></div>
                    <p>Loading your AI insights...</p>
                </div>
            `);
            
            // Enhance filter UI with animation
            $('.agent-filter').on('mouseenter', function() {
                $(this).addClass('hover');
            }).on('mouseleave', function() {
                $(this).removeClass('hover');
            });
        },
        
        /**
         * Load user insights
         */
        loadUserInsights: function() {
            const self = this;
            
            $.ajax({
                url: vortexDAOAI.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_get_user_ai_insights',
                    nonce: vortexDAOAI.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.insights = response.data;
                        self.renderInsights(self.insights);
                        
                        // Update insight metrics
                        self.updateInsightMetrics(self.insights);
                    } else {
                        $('#vortex-ai-insights-tab .insights-container').html(`
                            <div class="vortex-error">${response.data.message || 'Error loading insights'}</div>
                        `);
                    }
                },
                error: function() {
                    $('#vortex-ai-insights-tab .insights-container').html(`
                        <div class="vortex-error">Server error while loading insights</div>
                    `);
                }
            });
        },
        
        /**
         * Render insights
         */
        renderInsights: function(insights) {
            if (!insights || insights.length === 0) {
                $('#vortex-ai-insights-tab .insights-container').html(`
                    <div class="vortex-no-data">
                        <p>No AI insights available yet</p>
                        <p class="no-data-hint">Interact with AI agents to receive personalized insights</p>
                        <button class="vortex-btn explore-ai-btn">Explore AI Agents</button>
                    </div>
                `);
                
                // Add event listener to the explore button
                $('.explore-ai-btn').on('click', function() {
                    window.location.href = vortexDAOAI.ai_exploration_url || '#';
                });
                
                return;
            }
            
            let html = '<div class="insights-grid">';
            
            insights.forEach(insight => {
                // Format date
                const date = new Date(insight.created_at);
                const timeAgo = this.getTimeAgo(date);
                
                // Get summary
                const summary = insight.insight_data.summary || 'No summary available';
                
                html += `
                    <div class="insight-card agent-${insight.agent_type}" data-id="${insight.id}" data-agent="${insight.agent_type}" data-type="${insight.insight_type}">
                        <div class="insight-header">
                            <div class="agent-badge">
                                ${insight.agent_name}
                            </div>
                            <div class="confidence-score" data-score="${insight.confidence_score}">
                                ${Math.round(insight.confidence_score)}%
                            </div>
                        </div>
                        <div class="insight-type">
                            ${insight.insight_type.charAt(0).toUpperCase() + insight.insight_type.slice(1)} Insight
                        </div>
                        <div class="insight-content">
                            ${this.truncateText(summary, 100)}
                        </div>
                        <div class="insight-footer">
                            <div class="insight-date">
                                ${timeAgo}
                            </div>
                            ${insight.on_blockchain ? `
                            <div class="blockchain-badge" title="Verified on blockchain">
                                <span class="dashicons dashicons-shield"></span>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            
            // Add load more button if needed
            if (insights.length >= 20) {
                html += `
                    <div class="load-more-container">
                        <button class="vortex-btn load-more-insights">Load More Insights</button>
                    </div>
                `;
            }
            
            $('#vortex-ai-insights-tab .insights-container').html(html);
            
            // Initialize confidence score colors
            this.initConfidenceScores();
        },
        
        /**
         * Initialize confidence score colorization
         */
        initConfidenceScores: function() {
            $('.confidence-score').each(function() {
                const score = parseFloat($(this).data('score'));
                if (score >= 90) {
                    $(this).addClass('confidence-high');
                } else if (score >= 70) {
                    $(this).addClass('confidence-medium');
                } else {
                    $(this).addClass('confidence-low');
                }
            });
        },
        
        /**
         * Update insight metrics
         */
        updateInsightMetrics: function(insights) {
            if (!insights || !insights.length) return;
            
            // Count insights by agent
            const agentCounts = {};
            // Initialize agent counts
            $.each(vortexDAOAI.ai_agents, function(key) {
                agentCounts[key] = 0;
            });
            
            // Count insights by type
            const typeCounts = {
                creation: 0,
                market: 0,
                security: 0,
                trend: 0
            };
            
            // Calculate average confidence
            let totalConfidence = 0;
            let blockchainCount = 0;
            
            insights.forEach(insight => {
                // Count by agent
                if (agentCounts[insight.agent_type] !== undefined) {
                    agentCounts[insight.agent_type]++;
                }
                
                // Count by type
                if (typeCounts[insight.insight_type] !== undefined) {
                    typeCounts[insight.insight_type]++;
                }
                
                // Sum confidence
                totalConfidence += insight.confidence_score;
                
                // Count blockchain verifications
                if (insight.on_blockchain) {
                    blockchainCount++;
                }
            });
            
            // Calculate average confidence
            const avgConfidence = totalConfidence / insights.length;
            
            // Update metrics in the UI
            if ($('.vortex-ai-metrics').length) {
                $('.vortex-ai-metrics .total-insights-value').text(insights.length);
                $('.vortex-ai-metrics .avg-confidence-value').text(avgConfidence.toFixed(1) + '%');
                $('.vortex-ai-metrics .blockchain-verified-value').text(blockchainCount);
                
                // Update agent counts
                $.each(agentCounts, function(agent, count) {
                    $(`.vortex-ai-metrics .${agent}-count-value`).text(count);
                });
            }
        },
        
        /**
         * Apply filters to insights
         */
        applyFilters: function() {
            let filteredInsights = [...this.insights];
            
            // Filter by agent
            if (this.filters.agent !== 'all') {
                filteredInsights = filteredInsights.filter(insight => 
                    insight.agent_type === this.filters.agent
                );
            }
            
            // Filter by type
            if (this.filters.type !== 'all') {
                filteredInsights = filteredInsights.filter(insight => 
                    insight.insight_type === this.filters.type
                );
            }
            
            // Sort insights
            switch (this.filters.sortBy) {
                case 'date':
                    filteredInsights.sort((a, b) => 
                        new Date(b.created_at) - new Date(a.created_at)
                    );
                    break;
                    
                case 'confidence':
                    filteredInsights.sort((a, b) => 
                        b.confidence_score - a.confidence_score
                    );
                    break;
                    
                case 'agent':
                    filteredInsights.sort((a, b) => 
                        a.agent_type.localeCompare(b.agent_type) || 
                        new Date(b.created_at) - new Date(a.created_at)
                    );
                    break;
            }
            
            // Render filtered insights
            this.renderInsights(filteredInsights);
        },
        
        /**
         * Search insights
         */
        searchInsights: function(term) {
            if (!term) {
                this.applyFilters();
                return;
            }
            
            let filteredInsights = this.insights.filter(insight => {
                const summary = insight.insight_data.summary || '';
                const agentName = insight.agent_name || '';
                const insightType = insight.insight_type || '';
                
                return summary.toLowerCase().includes(term) || 
                       agentName.toLowerCase().includes(term) || 
                       insightType.toLowerCase().includes(term);
            });
            
            this.renderInsights(filteredInsights);
        },
        
        /**
         * Show insight detail
         */
        showInsightDetail: function(insightId) {
            // AJAX request to get insight details
            $.ajax({
                url: vortexDAOAI.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_get_ai_insight_detail',
                    insight_id: insightId,
                    nonce: vortexDAOAI.nonce
                },
                success: response => {
                    if (response.success) {
                        this.renderInsightDetail(response.data);
                        $('#ai-insight-detail-modal').addClass('show');
                        
                        // Update URL without refreshing page
                        const url = new URL(window.location);
                        url.searchParams.set('insight', insightId);
                        window.history.replaceState({}, '', url);
                    } else {
                        console.error('Error loading insight details:', response.data.message);
                        this.showNotification('Error loading insight details: ' + response.data.message, 'error');
                    }
                },
                error: () => {
                    console.error('AJAX error loading insight details');
                    this.showNotification('Server error while loading insight details', 'error');
                }
            });
        },
        
        /**
         * Render insight detail
         */
        renderInsightDetail: function(insight) {
            let insightData = insight.insight_data;
            
            // Format the creation date
            const date = new Date(insight.created_at);
            const formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
            
            // Determine agent color class
            const agentClass = 'agent-' + insight.agent_type;
            
            // Build HTML for the modal
            let html = `
                <div class="insight-detail ${agentClass}">
                    <div class="insight-detail-header">
                        <div class="agent-info">
                            <div class="agent-icon-large"></div>
                            <div class="agent-meta">
                                <h2>${insight.agent_name}</h2>
                                <div class="insight-type-badge">${insight.insight_type.charAt(0).toUpperCase() + insight.insight_type.slice(1)} Insight</div>
                            </div>
                        </div>
                        <div class="insight-confidence">
                            <div class="confidence-label">Confidence</div>
                            <div class="confidence-score-large">${insight.confidence_score.toFixed(0)}%</div>
                        </div>
                    </div>
                    
                    <div class="insight-detail-content">
                        <div class="insight-summary">
                            <h3>Summary</h3>
                            <p>${insightData.summary || 'No summary available'}</p>
                        </div>
                        
                        <div class="insight-details">
                            <h3>Details</h3>
                            <div class="insight-details-content">
                                ${this.formatInsightDetails(insightData)}
                            </div>
                        </div>
                        
                        ${insightData.recommendations ? `
                        <div class="insight-recommendations">
                            <h3>Recommendations</h3>
                            <ul class="recommendations-list">
                                ${this.formatRecommendations(insightData.recommendations)}
                            </ul>
                        </div>
                        ` : ''}
                    </div>
                    
                    <div class="insight-detail-footer">
                        <div class="insight-metadata">
                            <div class="metadata-item">
                                <span class="metadata-label">Generated</span>
                                <span class="metadata-value">${formattedDate}</span>
                            </div>
                            <div class="metadata-item">
                                <span class="metadata-label">Insight ID</span>
                                <span class="metadata-value">#${insight.id}</span>
                            </div>
                            ${insight.blockchain_ref ? `
                            <div class="metadata-item">
                                <span class="metadata-label">Blockchain Ref</span>
                                <span class="metadata-value blockchain-link">
                                    <a href="https://tolascan.org/tx/${insight.blockchain_ref}" target="_blank">
                                        ${insight.blockchain_ref.substring(0, 10)}...
                                    </a>
                                </span>
                            </div>
                            ` : ''}
                        </div>
                        
                        <div class="insight-actions">
                            <button class="vortex-btn share-insight-btn" 
                                    data-id="${insight.id}"
                                    data-agent="${insight.agent_name}"
                                    data-type="${insight.insight_type}"
                                    data-summary="${insightData.summary?.replace(/"/g, '&quot;') || ''}">
                                Share Insight
                            </button>
                            ${insight.on_blockchain ? `
                                <div class="blockchain-verified">
                                    <span class="dashicons dashicons-shield"></span>
                                    Verified on blockchain
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
            
            $('.ai-insight-detail-content').html(html);
        },
        
        /**
         * Format insight details
         */
        formatInsightDetails: function(insightData) {
            // Format insight details based on the data structure
            let html = '';
            
            // If details is an object with key-value pairs
            if (insightData.details && typeof insightData.details === 'object' && !Array.isArray(insightData.details)) {
                html += '<dl class="insight-data-list">';
                for (const [key, value] of Object.entries(insightData.details)) {
                    const formattedKey = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                    html += `<dt>${formattedKey}</dt>`;
                    
                    if (typeof value === 'object') {
                        html += `<dd><pre>${JSON.stringify(value, null, 2)}</pre></dd>`;
                    } else if (typeof value === 'boolean') {
                        html += `<dd>${value ? '✅ Yes' : '❌ No'}</dd>`;
                    } else {
                        html += `<dd>${value}</dd>`;
                    }
                }
                html += '</dl>';
            } 
            // If details is an array
            else if (insightData.details && Array.isArray(insightData.details)) {
                html += '<ul class="insight-details-list">';
                insightData.details.forEach(item => {
                    if (typeof item === 'string') {
                        html += `<li>${item}</li>`;
                    } else {
                        html += `<li><pre>${JSON.stringify(item, null, 2)}</pre></li>`;
                    }
                });
                html += '</ul>';
            }
            // If details is a string
            else if (insightData.details && typeof insightData.details === 'string') {
                html += `<p>${insightData.details}</p>`;
            }
            // If there's no structured details but there's a message
            else if (insightData.message) {
                html += `<p>${insightData.message}</p>`;
            }
            // Fallback
            else {
                html += `<p>No detailed information available</p>`;
            }
            
            return html;
        },
        
        /**
         * Format recommendations
         */
        formatRecommendations: function(recommendations) {
            if (!recommendations) return '';
            
            let html = '';
            
            if (Array.isArray(recommendations)) {
                recommendations.forEach(recommendation => {
                    if (typeof recommendation === 'string') {
                        html += `<li>${recommendation}</li>`;
                    } else if (typeof recommendation === 'object' && recommendation.text) {
                        html += `<li>${recommendation.text}`;
                        if (recommendation.confidence) {
                            html += ` <span class="recommendation-confidence">(${recommendation.confidence}% confidence)</span>`;
                        }
                        html += '</li>';
                    }
                });
            } else if (typeof recommendations === 'string') {
                html += `<li>${recommendations}</li>`;
            }
            
            return html;
        },
        
        /**
         * Share insight
         */
        shareInsight: function(insightId, agentName, insightType, summary) {
            // Create share data
            const shareData = {
                title: `AI Insight from ${agentName}`,
                text: `Check out this ${insightType} insight from ${agentName} AI agent on VORTEX: "${this.truncateText(summary, 100)}"`,
                url: `${window.location.origin}${window.location.pathname}?insight=${insightId}`
            };
            
            // Use Web Share API if available
            if (navigator.share) {
                navigator.share(shareData)
                    .then(() => this.showNotification('Insight shared successfully', 'success'))
                    .catch(error => console.log('Error sharing insight:', error));
            } else {
                // Fallback for browsers that don't support Web Share API
                const shareUrl = 'https://twitter.com/intent/tweet?text=' + encodeURIComponent(shareData.text + ' ' + shareData.url);
                window.open(shareUrl, '_blank');
                this.showNotification('Insight opened for sharing on Twitter', 'info');
            }
        },
        
        /**
         * Handle new insight notification
         */
        handleNewInsightNotification: function(notification) {
            // Show notification to user
            this.showNotification(`New ${notification.data.insight_type} insight from ${notification.data.agent_name}`, 'info');
            
            // If on insights tab, refresh the insights
            if ($('#vortex-ai-insights-tab').is(':visible')) {
                this.loadUserInsights();
            }
        },
        
        /**
         * Show notification
         */
        showNotification: function(message, type = 'info') {
            // If VortexDAO.showNotification exists, use it
            if (typeof VortexDAO !== 'undefined' && typeof VortexDAO.showNotification === 'function') {
                VortexDAO.showNotification(message, type);
                return;
            }
            
            // Otherwise, create our own notification system
            let $container = $('.vortex-notifications');
            
            if (!$container.length) {
                $container = $('<div class="vortex-notifications"></div>');
                $('body').append($container);
            }
            
            const $notification = $(`
                <div class="vortex-notification ${type}">
                    <div class="notification-message">${message}</div>
                    <button class="notification-close">&times;</button>
                </div>
            `);
            
            $container.append($notification);
            
            setTimeout(() => {
                $notification.addClass('show');
            }, 10);
            
            setTimeout(() => {
                $notification.removeClass('show');
                setTimeout(() => {
                    $notification.remove();
                }, 300);
            }, 5000);
            
            $notification.find('.notification-close').on('click', function() {
                $notification.removeClass('show');
                setTimeout(() => {
                    $notification.remove();
                }, 300);
            });
        },
        
        /**
         * Initialize AI metrics
         */
        initAIMetrics: function() {
            // Create metrics HTML
            const metricsHtml = `
                <div class="ai-metrics-grid">
                    <div class="metric-card total-insights">
                        <div class="metric-value total-insights-value">--</div>
                        <div class="metric-label">Total Insights</div>
                    </div>
                    <div class="metric-card avg-confidence">
                        <div class="metric-value avg-confidence-value">--</div>
                        <div class="metric-label">Avg. Confidence</div>
                    </div>
                    <div class="metric-card blockchain-verified">
                        <div class="metric-value blockchain-verified-value">--</div>
                        <div class="metric-label">Blockchain Verified</div>
                    </div>
                    ${Object.entries(vortexDAOAI.ai_agents).map(([key, name]) => `
                        <div class="metric-card ${key}-insights">
                            <div class="metric-value ${key}-count-value">--</div>
                            <div class="metric-label">${name} Insights</div>
                        </div>
                    `).join('')}
                </div>
            `;
            
            // Add metrics to container
            $('.vortex-ai-metrics').html(metricsHtml);
            
            // Load insights data for metrics
            $.ajax({
                url: vortexDAOAI.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_get_user_ai_insights',
                    nonce: vortexDAOAI.nonce
                },
                success: response => {
                    if (response.success) {
                        this.updateInsightMetrics(response.data);
                    }
                }
            });
        },
        
        /**
         * Load more insights
         */
        loadMoreInsights: function() {
            // In a real implementation, this would load the next page of insights
            // For now, we'll just show a notification
            this.showNotification('Loading more insights functionality coming soon!', 'info');
        },
        
        /**
         * Helper: Get time ago string
         */
        getTimeAgo: function(date) {
            const seconds = Math.floor((new Date() - date) / 1000);
            
            let interval = Math.floor(seconds / 31536000);
            if (interval > 1) return interval + ' years ago';
            if (interval === 1) return '1 year ago';
            
            interval = Math.floor(seconds / 2592000);
            if (interval > 1) return interval + ' months ago';
            if (interval === 1) return '1 month ago';
            
            interval = Math.floor(seconds / 86400);
            if (interval > 1) return interval + ' days ago';
            if (interval === 1) return '1 day ago';
            
            interval = Math.floor(seconds / 3600);
            if (interval > 1) return interval + ' hours ago';
            if (interval === 1) return '1 hour ago';
            
            interval = Math.floor(seconds / 60);
            if (interval > 1) return interval + ' minutes ago';
            if (interval === 1) return '1 minute ago';
            
            return 'just now';
        },
        
        /**
         * Helper: Truncate text with ellipsis
         */
        truncateText: function(text, maxLength) {
            if (!text || text.length <= maxLength) return text || '';
            return text.substring(0, maxLength) + '...';
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        VortexDAOAI.init();
    });
    
})(jQuery); 