/**
 * VORTEX AI Governance Dashboard
 * 
 * Provides interactive AI-powered governance analytics and predictive insights
 */

(function($) {
    'use strict';

    // Main Dashboard object
    const VortexAIGovernance = {
        /**
         * Initialize the dashboard
         */
        init: function() {
            // Only initialize on governance dashboard page
            if (!$('.vortex-ai-governance-dashboard').length) {
                return;
            }

            // Initialize components
            this.initTabs();
            this.initPredictiveAnalytics();
            this.initEventListeners();
            
            // Load initial data
            this.loadRecommendations();
            
            // Check if user has admin/moderator permissions
            if ($('.vortex-ai-analytics').length) {
                this.loadAnalytics();
            }
        },

        /**
         * Initialize dashboard tabs
         */
        initTabs: function() {
            const tabLinks = $('.vortex-ai-tab-link');
            const tabContents = $('.vortex-ai-tab-content');

            // Set first tab as active
            if (tabLinks.length) {
                $(tabLinks[0]).addClass('active');
                const firstTabId = $(tabLinks[0]).data('tab');
                $('#' + firstTabId).addClass('active');
            }

            // Tab click handler
            tabLinks.on('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all tabs
                tabLinks.removeClass('active');
                tabContents.removeClass('active');
                
                // Add active class to clicked tab
                $(this).addClass('active');
                const tabId = $(this).data('tab');
                $('#' + tabId).addClass('active');
            });
        },

        /**
         * Initialize predictive analytics visualizations
         */
        initPredictiveAnalytics: function() {
            // We'll initialize chart placeholders here
            // Actual charts will be populated when data is loaded
            
            // Check if D3.js is loaded
            if (typeof d3 !== 'undefined') {
                this.setupChartContainers();
            } else {
                // Try to load D3.js dynamically
                $.getScript('https://d3js.org/d3.v7.min.js', () => {
                    this.setupChartContainers();
                });
            }
        },
        
        /**
         * Set up chart containers
         */
        setupChartContainers: function() {
            // Create containers for each chart
            const chartsHTML = `
                <div class="vortex-charts-container">
                    <div class="vortex-chart-card">
                        <h4>${vortexAiGov.i18n.participation_forecast}</h4>
                        <div id="participation-forecast-chart" class="vortex-chart"></div>
                    </div>
                    <div class="vortex-chart-card">
                        <h4>${vortexAiGov.i18n.proposal_volume}</h4>
                        <div id="proposal-volume-chart" class="vortex-chart"></div>
                    </div>
                    <div class="vortex-chart-card">
                        <h4>${vortexAiGov.i18n.token_distribution}</h4>
                        <div id="token-distribution-chart" class="vortex-chart"></div>
                    </div>
                    <div class="vortex-chart-card">
                        <h4>${vortexAiGov.i18n.governance_health}</h4>
                        <div id="governance-health-chart" class="vortex-chart"></div>
                    </div>
                </div>
            `;
            
            // Add charts to the predictive analytics container
            $('#vortex-ai-predictive-analytics').html(chartsHTML);
        },

        /**
         * Initialize event listeners
         */
        initEventListeners: function() {
            // Refresh buttons
            $('.vortex-ai-refresh-btn').on('click', (e) => {
                const target = $(e.currentTarget).data('target');
                
                if (target === 'recommendations') {
                    this.loadRecommendations();
                } else if (target === 'analytics') {
                    this.loadAnalytics();
                }
            });
            
            // Proposal prediction form
            $('#vortex-proposal-prediction-form').on('submit', (e) => {
                e.preventDefault();
                this.predictProposalOutcome();
            });
        },

        /**
         * Load voting recommendations
         */
        loadRecommendations: function() {
            const container = $('#vortex-ai-recommendations-content');
            
            // Show loading state
            container.html('<div class="vortex-ai-loading">' + vortexAiGov.i18n.loading + '</div>');
            
            // Make AJAX request
            $.ajax({
                url: vortexAiGov.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_ai_get_voting_recommendations',
                    nonce: vortexAiGov.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.renderRecommendations(response.data.recommendations);
                    } else {
                        container.html('<div class="vortex-ai-error">' + response.data.message + '</div>');
                    }
                },
                error: () => {
                    container.html('<div class="vortex-ai-error">' + vortexAiGov.i18n.error + '</div>');
                }
            });
        },

        /**
         * Render voting recommendations
         */
        renderRecommendations: function(recommendations) {
            const container = $('#vortex-ai-recommendations-content');
            
            if (!recommendations || recommendations.length === 0) {
                container.html('<div class="vortex-ai-no-data">' + vortexAiGov.i18n.no_recommendations + '</div>');
                return;
            }
            
            let html = '<div class="vortex-recommendations-grid">';
            
            recommendations.forEach(recommendation => {
                // Determine recommendation class based on alignment
                let recommendationClass = 'neutral';
                if (recommendation.recommendation === 'approve') {
                    recommendationClass = 'positive';
                } else if (recommendation.recommendation === 'reject') {
                    recommendationClass = 'negative';
                }
                
                html += `
                    <div class="vortex-recommendation-card ${recommendationClass}">
                        <h4 class="proposal-title">${recommendation.title}</h4>
                        <div class="recommendation-indicator ${recommendation.recommendation}">
                            ${this.getRecommendationText(recommendation.recommendation)}
                        </div>
                        <div class="alignment-score">
                            <span>${vortexAiGov.i18n.alignment_score}:</span>
                            <span class="score-value">${recommendation.alignment_score}</span>
                        </div>
                        <div class="recommendation-reasoning">${recommendation.reasoning}</div>
                        <div class="recommendation-considerations">
                            <h5>${vortexAiGov.i18n.key_considerations}:</h5>
                            <ul>
                                ${recommendation.key_considerations.map(item => `<li>${item}</li>`).join('')}
                            </ul>
                        </div>
                        <a href="${vortexAiGov.proposal_url}?id=${recommendation.proposal_id}" class="vortex-btn view-proposal-btn">
                            ${vortexAiGov.i18n.view_proposal}
                        </a>
                    </div>
                `;
            });
            
            html += '</div>';
            container.html(html);
        },

        /**
         * Get recommendation text based on recommendation type
         */
        getRecommendationText: function(recommendation) {
            switch (recommendation) {
                case 'approve':
                    return vortexAiGov.i18n.recommend_approve;
                case 'reject':
                    return vortexAiGov.i18n.recommend_reject;
                default:
                    return vortexAiGov.i18n.recommend_review;
            }
        },

        /**
         * Load governance analytics
         */
        loadAnalytics: function() {
            const container = $('#vortex-ai-analytics-content');
            
            // Show loading state
            container.html('<div class="vortex-ai-loading">' + vortexAiGov.i18n.loading + '</div>');
            
            // Make AJAX request
            $.ajax({
                url: vortexAiGov.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_ai_get_governance_analytics',
                    nonce: vortexAiGov.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.renderAnalytics(response.data.analytics);
                        this.loadPredictiveAnalytics();
                    } else {
                        container.html('<div class="vortex-ai-error">' + response.data.message + '</div>');
                    }
                },
                error: () => {
                    container.html('<div class="vortex-ai-error">' + vortexAiGov.i18n.error + '</div>');
                }
            });
        },

        /**
         * Render governance analytics
         */
        renderAnalytics: function(analytics) {
            const container = $('#vortex-ai-analytics-content');
            
            // Create metrics summary
            const healthScore = analytics.governance_health_score;
            
            const metricsHTML = `
                <div class="vortex-analytics-overview">
                    <div class="vortex-health-score-container">
                        <div class="vortex-health-score ${this.getHealthScoreClass(healthScore.score)}">
                            <span class="score-value">${healthScore.score}</span>
                            <span class="score-label">${vortexAiGov.i18n.health_score}</span>
                        </div>
                        <div class="vortex-health-assessment">
                            <p>${healthScore.assessment}</p>
                            <ul class="vortex-health-recommendations">
                                ${healthScore.recommendations.map(rec => `<li>${rec}</li>`).join('')}
                            </ul>
                        </div>
                    </div>
                    
                    <div class="vortex-metrics-grid">
                        <div class="vortex-metric-card">
                            <span class="metric-value">${analytics.participation_trends.overall_participation_rate}%</span>
                            <span class="metric-label">${vortexAiGov.i18n.participation_rate}</span>
                            <span class="metric-trend ${analytics.participation_trends.trend > 0 ? 'positive' : 'negative'}">
                                ${analytics.participation_trends.trend > 0 ? '+' : ''}${analytics.participation_trends.trend}%
                            </span>
                        </div>
                        
                        <div class="vortex-metric-card">
                            <span class="metric-value">${analytics.proposal_success_rates.overall_success_rate}%</span>
                            <span class="metric-label">${vortexAiGov.i18n.success_rate}</span>
                        </div>
                        
                        <div class="vortex-metric-card">
                            <span class="metric-value">${analytics.active_participants.total_participants}</span>
                            <span class="metric-label">${vortexAiGov.i18n.active_participants}</span>
                        </div>
                        
                        <div class="vortex-metric-card">
                            <span class="metric-value">${analytics.active_participants.proposers}</span>
                            <span class="metric-label">${vortexAiGov.i18n.active_proposers}</span>
                        </div>
                    </div>
                </div>
                
                <div class="vortex-analytics-details">
                    <!-- Placeholder for detailed analytics - would be visualized with charts in production -->
                </div>
            `;
            
            container.html(metricsHTML);
        },

        /**
         * Get health score CSS class based on score value
         */
        getHealthScoreClass: function(score) {
            if (score >= 80) {
                return 'excellent';
            } else if (score >= 60) {
                return 'good';
            } else if (score >= 40) {
                return 'fair';
            } else {
                return 'poor';
            }
        },

        /**
         * Load predictive analytics data
         */
        loadPredictiveAnalytics: function() {
            // Make REST API request to get predictive analytics
            $.ajax({
                url: vortexAiGov.rest_url + 'vortex-marketplace/v1/ai/governance/predictive-analytics',
                method: 'GET',
                beforeSend: (xhr) => {
                    xhr.setRequestHeader('X-WP-Nonce', vortexAiGov.wp_rest_nonce);
                },
                success: (response) => {
                    if (response && response.success) {
                        this.renderPredictiveAnalytics(response.data);
                    }
                }
            });
        },

        /**
         * Render predictive analytics visualizations
         */
        renderPredictiveAnalytics: function(data) {
            // In a full implementation, this would render D3.js visualizations
            // For this prototype, we'll display a simple overview
            
            const container = $('#vortex-ai-predictive-tab');
            const challengesContainer = $('#governance-challenges');
            const opportunitiesContainer = $('#governance-opportunities');
            
            // Render challenges
            if (challengesContainer.length && data.predicted_governance_challenges) {
                let challengesHTML = '<ul class="vortex-challenges-list">';
                
                data.predicted_governance_challenges.forEach(challenge => {
                    challengesHTML += `
                        <li class="vortex-challenge-item">
                            <div class="challenge-header">
                                <h4>${challenge.challenge}</h4>
                                <span class="probability-badge" data-probability="${challenge.probability}">
                                    ${challenge.probability}% ${vortexAiGov.i18n.probability}
                                </span>
                            </div>
                            <div class="impact-indicator ${challenge.impact}">
                                ${vortexAiGov.i18n.impact}: ${challenge.impact}
                            </div>
                            <p class="mitigation-strategy">${challenge.mitigation_strategy}</p>
                        </li>
                    `;
                });
                
                challengesHTML += '</ul>';
                challengesContainer.html(challengesHTML);
            }
            
            // Render opportunities
            if (opportunitiesContainer.length && data.opportunity_areas) {
                let opportunitiesHTML = '<ul class="vortex-opportunities-list">';
                
                data.opportunity_areas.forEach(opportunity => {
                    opportunitiesHTML += `
                        <li class="vortex-opportunity-item">
                            <div class="opportunity-header">
                                <h4>${opportunity.opportunity}</h4>
                                <div class="opportunity-metrics">
                                    <span class="impact-badge ${opportunity.potential_impact}">
                                        ${vortexAiGov.i18n.impact}: ${opportunity.potential_impact}
                                    </span>
                                    <span class="complexity-badge ${opportunity.implementation_complexity}">
                                        ${vortexAiGov.i18n.complexity}: ${opportunity.implementation_complexity}
                                    </span>
                                </div>
                            </div>
                            <p class="opportunity-outcome">${opportunity.expected_outcome}</p>
                        </li>
                    `;
                });
                
                opportunitiesHTML += '</ul>';
                opportunitiesContainer.html(opportunitiesHTML);
            }
            
            // If using D3.js, we would render charts here based on the data
            // For example, we would visualize participation forecast, proposal volume
            // token distribution, and governance health trends
        },

        /**
         * Predict proposal outcome
         */
        predictProposalOutcome: function() {
            const form = $('#vortex-proposal-prediction-form');
            const proposalId = form.find('[name="proposal_id"]').val();
            const resultContainer = $('#prediction-result');
            
            if (!proposalId) {
                resultContainer.html('<div class="vortex-ai-error">' + vortexAiGov.i18n.select_proposal + '</div>');
                return;
            }
            
            // Show loading state
            resultContainer.html('<div class="vortex-ai-loading">' + vortexAiGov.i18n.analyzing + '</div>');
            
            // Get simulation factors if available
            const participationMod = form.find('[name="participation_modifier"]').val();
            const sentimentMod = form.find('[name="sentiment_modifier"]').val();
            
            let simulationFactors = {};
            if (participationMod) {
                simulationFactors.participation_modifier = parseFloat(participationMod);
            }
            if (sentimentMod) {
                simulationFactors.sentiment_modifier = parseFloat(sentimentMod);
            }
            
            // Make REST API request to predict outcome
            $.ajax({
                url: vortexAiGov.rest_url + 'vortex-marketplace/v1/ai/governance/predict-outcome',
                method: 'POST',
                data: {
                    proposal_id: proposalId,
                    simulation_factors: simulationFactors
                },
                beforeSend: (xhr) => {
                    xhr.setRequestHeader('X-WP-Nonce', vortexAiGov.wp_rest_nonce);
                },
                success: (response) => {
                    if (response && response.success) {
                        this.renderPredictionResult(response.data);
                    } else {
                        resultContainer.html('<div class="vortex-ai-error">' + vortexAiGov.i18n.error + '</div>');
                    }
                },
                error: () => {
                    resultContainer.html('<div class="vortex-ai-error">' + vortexAiGov.i18n.error + '</div>');
                }
            });
        },

        /**
         * Render prediction result
         */
        renderPredictionResult: function(data) {
            const resultContainer = $('#prediction-result');
            
            // Determine outcome class
            let outcomeClass = 'neutral';
            let outcomeText = vortexAiGov.i18n.no_quorum;
            
            if (data.prediction.projected_outcome === 'pass') {
                outcomeClass = 'positive';
                outcomeText = vortexAiGov.i18n.pass;
            } else if (data.prediction.projected_outcome === 'fail') {
                outcomeClass = 'negative';
                outcomeText = vortexAiGov.i18n.fail;
            }
            
            const resultHTML = `
                <div class="vortex-prediction-result">
                    <div class="prediction-summary ${outcomeClass}">
                        <h4>${vortexAiGov.i18n.prediction}:</h4>
                        <div class="outcome-prediction">
                            ${outcomeText}
                            <span class="confidence">(${data.prediction.confidence} ${vortexAiGov.i18n.confidence})</span>
                        </div>
                    </div>
                    
                    <div class="prediction-details">
                        <div class="current-status">
                            <h5>${vortexAiGov.i18n.current_status}:</h5>
                            <div class="vote-counts">
                                <div class="vote-bar">
                                    <div class="yes-bar" style="width: ${this.calculatePercentage(data.current_status.yes_votes, data.current_status.total_votes)}%"></div>
                                    <div class="no-bar" style="width: ${this.calculatePercentage(data.current_status.no_votes, data.current_status.total_votes)}%"></div>
                                    <div class="abstain-bar" style="width: ${this.calculatePercentage(data.current_status.abstain_votes, data.current_status.total_votes)}%"></div>
                                </div>
                                <div class="vote-legend">
                                    <span class="yes-legend">${vortexAiGov.i18n.yes}: ${data.current_status.yes_votes}</span>
                                    <span class="no-legend">${vortexAiGov.i18n.no}: ${data.current_status.no_votes}</span>
                                    <span class="abstain-legend">${vortexAiGov.i18n.abstain}: ${data.current_status.abstain_votes}</span>
                                </div>
                            </div>
                            <div class="quorum-progress">
                                <div class="progress-label">
                                    ${vortexAiGov.i18n.quorum}: ${Math.round(data.current_status.quorum_progress)}%
                                </div>
                                <div class="progress-bar">
                                    <div class="progress" style="width: ${Math.min(100, data.current_status.quorum_progress)}%"></div>
                                </div>
                            </div>
                            <div class="time-remaining">
                                ${vortexAiGov.i18n.days_remaining}: ${data.current_status.days_remaining}
                            </div>
                        </div>
                        
                        <div class="projected-outcome">
                            <h5>${vortexAiGov.i18n.projected_outcome}:</h5>
                            <div class="vote-counts">
                                <div class="vote-bar">
                                    <div class="yes-bar" style="width: ${this.calculatePercentage(data.prediction.projected_yes, data.prediction.projected_total)}%"></div>
                                    <div class="no-bar" style="width: ${this.calculatePercentage(data.prediction.projected_no, data.prediction.projected_total)}%"></div>
                                    <div class="abstain-bar" style="width: ${this.calculatePercentage(data.prediction.projected_abstain, data.prediction.projected_total)}%"></div>
                                </div>
                                <div class="vote-legend">
                                    <span class="yes-legend">${vortexAiGov.i18n.yes}: ${data.prediction.projected_yes}</span>
                                    <span class="no-legend">${vortexAiGov.i18n.no}: ${data.prediction.projected_no}</span>
                                    <span class="abstain-legend">${vortexAiGov.i18n.abstain}: ${data.prediction.projected_abstain}</span>
                                </div>
                            </div>
                            <div class="projected-participation">
                                ${vortexAiGov.i18n.projected_participation}: ${data.prediction.projected_participation}
                            </div>
                            <div class="quorum-status">
                                ${vortexAiGov.i18n.quorum_status}: 
                                <span class="${data.prediction.will_reach_quorum ? 'positive' : 'negative'}">
                                    ${data.prediction.will_reach_quorum ? vortexAiGov.i18n.will_reach_quorum : vortexAiGov.i18n.wont_reach_quorum}
                                </span>
                            </div>
                        </div>
                        
                        <div class="key-factors">
                            <h5>${vortexAiGov.i18n.key_factors}:</h5>
                            <ul>
                                ${data.prediction.key_factors.map(factor => `<li>${factor}</li>`).join('')}
                            </ul>
                        </div>
                    </div>
                </div>
            `;
            
            resultContainer.html(resultHTML);
        },

        /**
         * Calculate percentage for vote visualization
         */
        calculatePercentage: function(value, total) {
            if (!total) return 0;
            return (value / total) * 100;
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        VortexAIGovernance.init();
    });

})(jQuery); 