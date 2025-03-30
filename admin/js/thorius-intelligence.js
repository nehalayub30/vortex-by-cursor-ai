/**
 * Thorius Intelligence Dashboard
 * 
 * Handles the interactivity of the admin intelligence dashboard
 */
(function($) {
    'use strict';
    
    // Chart instances
    var charts = {};
    
    // Current query
    var currentQuery = '';
    
    // Query history
    var queryHistory = [];
    
    // Initialize the intelligence dashboard
    $(document).ready(function() {
        // Handle query submission
        $('#intelligence-query-submit').on('click', function() {
            processQuery();
        });
        
        // Handle enter key in query input
        $('#intelligence-query').on('keypress', function(e) {
            if (e.which === 13) {
                processQuery();
            }
        });
        
        // Handle suggested query clicks
        $('.query-suggestion').on('click', function() {
            $('#intelligence-query').val($(this).text());
            processQuery();
        });
        
        // Handle refresh data
        $('.refresh-data').on('click', function() {
            if (currentQuery) {
                processQuery(true);
            }
        });
        
        // Handle follow-up question clicks
        $(document).on('click', '.follow-up-question', function() {
            $('#intelligence-query').val($(this).text());
            processQuery();
        });
        
        // Handle query history clicks
        $(document).on('click', '.query-history-item', function() {
            $('#intelligence-query').val($(this).text());
            processQuery();
        });
        
        // Handle download report
        $('#download-intelligence-report').on('click', function() {
            downloadIntelligenceReport();
        });
        
        // Handle email report
        $('#email-intelligence-report').on('click', function() {
            emailIntelligenceReport();
        });
        
        // Load query history
        loadQueryHistory();
    });
    
    /**
     * Process query
     */
    function processQuery(forceRefresh = false) {
        currentQuery = $('#intelligence-query').val();
        
        if (!currentQuery) {
            return;
        }
        
        // Show response container and loader
        $('.intelligence-response-container').show();
        $('.intelligence-loader').show();
        
        // Reset response sections
        resetResponseSections();
        
        // Make AJAX request
        $.ajax({
            url: vortex_thorius_intelligence.ajax_url,
            type: 'POST',
            data: {
                action: 'vortex_thorius_admin_query',
                nonce: vortex_thorius_intelligence.nonce,
                query: currentQuery,
                force_refresh: forceRefresh ? 1 : 0
            },
            success: function(response) {
                // Hide loader
                $('.intelligence-loader').hide();
                
                if (response.success && response.data) {
                    renderIntelligenceResponse(response.data);
                    updateQueryHistory(currentQuery);
                } else {
                    showError(response.data || 'Error processing your query.');
                }
            },
            error: function() {
                // Hide loader
                $('.intelligence-loader').hide();
                
                // Show error
                showError('An error occurred while communicating with the server.');
            }
        });
    }
    
    /**
     * Render intelligence response
     */
    function renderIntelligenceResponse(data) {
        // Set response timestamp
        $('.response-timestamp').text('Generated on ' + new Date().toLocaleString());
        
        // Render narrative
        $('#response-narrative').html(data.narrative);
        
        // Display data sections based on data type
        displayDataSections(data);
        
        // Render insights
        renderInsights(data.insights);
        
        // Render recommended actions
        renderActions(data.recommended_actions);
        
        // Render sources
        renderSources(data.sources);
        
        // Render follow-up questions
        renderFollowUpQuestions(data.follow_up_questions);
        
        // Show response container
        $('.intelligence-response-container').show();
    }
    
    /**
     * Display relevant data sections
     */
    function displayDataSections(data) {
        // First hide all sections
        $('.data-section').hide();
        
        // Show and populate relevant sections
        if (data.platform_stats) {
            $('#platform-stats-section').show();
            renderPlatformStats(data.platform_stats);
        }
        
        if (data.user_activity) {
            $('#user-activity-section').show();
            renderUserActivity(data.user_activity);
        }
        
        if (data.marketplace_trends) {
            $('#marketplace-trends-section').show();
            renderMarketplaceTrends(data.marketplace_trends);
        }
        
        if (data.agent_performance) {
            $('#agent-performance-section').show();
            renderAgentPerformance(data.agent_performance);
        }
        
        if (data.content_trends) {
            $('#content-trends-section').show();
            renderContentTrends(data.content_trends);
        }
        
        if (data.market_intelligence) {
            $('#market-intelligence-section').show();
            renderMarketIntelligence(data.market_intelligence);
        }
        
        if (data.world_knowledge) {
            $('#world-knowledge-section').show();
            renderWorldKnowledge(data.world_knowledge);
        }
    }
    
    /**
     * Reset response sections
     */
    function resetResponseSections() {
        // Clear narrative
        $('#response-narrative').html('');
        
        // Clear data visualizations
        $('.data-visualization').html('');
        $('.knowledge-insights').html('');
        
        // Clear lists
        $('.insights-list').html('');
        $('.actions-list').html('');
        $('.sources-list').html('');
        $('.follow-up-suggestions').html('');
        
        // Destroy charts
        destroyCharts();
    }
    
    /**
     * Render insights
     */
    function renderInsights(insights) {
        if (!insights || insights.length === 0) {
            $('#response-insights').hide();
            return;
        }
        
        var $insightsList = $('.insights-list');
        $insightsList.empty();
        
        insights.forEach(function(insight) {
            $insightsList.append('<li>' + insight + '</li>');
        });
        
        $('#response-insights').show();
    }
    
    /**
     * Render recommended actions
     */
    function renderActions(actions) {
        if (!actions || actions.length === 0) {
            $('#response-actions').hide();
            return;
        }
        
        var $actionsList = $('.actions-list');
        $actionsList.empty();
        
        actions.forEach(function(action) {
            $actionsList.append('<li>' + action + '</li>');
        });
        
        $('#response-actions').show();
    }
    
    /**
     * Render sources
     */
    function renderSources(sources) {
        if (!sources || sources.length === 0) {
            $('#response-sources').hide();
            return;
        }
        
        var $sourcesList = $('.sources-list');
        $sourcesList.empty();
        
        sources.forEach(function(source) {
            if (source.url) {
                $sourcesList.append('<li><a href="' + source.url + '" target="_blank">' + source.title + '</a></li>');
            } else {
                $sourcesList.append('<li>' + source.title + '</li>');
            }
        });
        
        $('#response-sources').show();
    }
    
    /**
     * Render follow-up questions
     */
    function renderFollowUpQuestions(questions) {
        if (!questions || questions.length === 0) {
            $('.follow-up-questions').hide();
            return;
        }
        
        var $followUpSuggestions = $('.follow-up-suggestions');
        $followUpSuggestions.empty();
        
        questions.forEach(function(question) {
            $followUpSuggestions.append('<button class="follow-up-question">' + question + '</button>');
        });
        
        $('.follow-up-questions').show();
    }
    
    /**
     * Update query history
     */
    function updateQueryHistory(query) {
        // Add to beginning of array
        queryHistory.unshift(query);
        
        // Keep only latest 10 queries
        queryHistory = queryHistory.slice(0, 10);
        
        // Save to local storage
        localStorage.setItem('vortex_thorius_query_history', JSON.stringify(queryHistory));
        
        // Update UI
        renderQueryHistory();
    }
    
    /**
     * Load query history
     */
    function loadQueryHistory() {
        var savedHistory = localStorage.getItem('vortex_thorius_query_history');
        
        if (savedHistory) {
            queryHistory = JSON.parse(savedHistory);
            renderQueryHistory();
        }
    }
    
    /**
     * Render query history
     */
    function renderQueryHistory() {
        var $historyList = $('.query-history-list');
        $historyList.empty();
        
        queryHistory.forEach(function(query) {
            $historyList.append('<li class="query-history-item">' + query + '</li>');
        });
        
        $('.intelligence-history').toggle(queryHistory.length > 0);
    }
    
    /**
     * Show error message
     */
    function showError(message) {
        $('#response-narrative').html('<p class="error-message">' + message + '</p>');
    }
    
    /**
     * Destroy all charts
     */
    function destroyCharts() {
        Object.keys(charts).forEach(function(key) {
            if (charts[key]) {
                charts[key].destroy();
                charts[key] = null;
            }
        });
    }
    
    /**
     * Download intelligence report as PDF
     */
    function downloadIntelligenceReport() {
        // Implementation for PDF download
        alert('PDF download feature will be implemented.');
    }
    
    /**
     * Email intelligence report
     */
    function emailIntelligenceReport() {
        // Implementation for email feature
        alert('Email report feature will be implemented.');
    }
    
    // Chart rendering functions for each data type would be implemented here
    // For brevity, I'm not including all of them, but they would follow similar patterns
    
})(jQuery); 