/**
 * VORTEX AI Marketplace - Metrics Dashboard
 * Handles visualization and management of platform metrics
 * Integrated with VORTEX AI Agents for real-time analytics and deep learning insights
 */

class VortexMetricsDashboard {
    constructor() {
        this.charts = {};
        this.filters = {
            timeRange: 'last-30-days',
            metrics: ['sales', 'installations', 'reviews'],
            categories: []
        };
        this.aiAnalysisEnabled = true;
        this.aiInsightsCache = {};
        this.aiAgentStatus = 'active';
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadCategories();
        this.refreshDashboard();
        this.connectToAIAgentSystem();
        this.startRealtimeLearningSync();
    }

    /**
     * Establishes connection to the AI Agent System for real-time analytics
     * This allows the dashboard to receive intelligent insights from VORTEX AI
     */
    connectToAIAgentSystem() {
        try {
            // Subscribe to AI agent notifications channel
            if (typeof VortexAIBridge !== 'undefined') {
                VortexAIBridge.connectAgent('metrics-analyst', {
                    onInsight: this.handleAIInsight.bind(this),
                    onRecommendation: this.handleAIRecommendation.bind(this),
                    onError: this.handleAIError.bind(this)
                });
                
                console.log('Successfully connected to VORTEX AI Agent System');
            } else {
                console.warn('VortexAIBridge not found. Some AI features will be unavailable.');
                this.aiAgentStatus = 'disconnected';
            }
        } catch (error) {
            console.error('Error connecting to AI Agent System:', error);
            this.aiAgentStatus = 'error';
        }
    }

    /**
     * Initiates the real-time deep learning sync process
     * This ensures the dashboard reflects latest AI-processed analytics
     */
    startRealtimeLearningSync() {
        // Setup periodic sync with deep learning models
        this.learningInterval = setInterval(() => {
            if (this.aiAgentStatus === 'active') {
                this.syncWithDeepLearningModels();
            }
        }, 60000); // Sync every minute
    }

    /**
     * Syncs dashboard with the latest deep learning model outputs
     */
    async syncWithDeepLearningModels() {
        try {
            const response = await fetch('/wp-json/vortex/v1/ai/metrics/insights', {
                headers: {
                    'X-WP-Nonce': vortexSettings?.nonce || '',
                    'X-VORTEX-AI-Request': 'true'
                }
            });
            
            if (response.ok) {
                const insights = await response.json();
                this.processAIInsights(insights);
            }
        } catch (error) {
            console.warn('Deep learning sync error:', error);
        }
    }

    /**
     * Processes AI-generated insights and applies them to the dashboard
     */
    processAIInsights(insights) {
        if (!insights || !insights.data) return;
        
        this.aiInsightsCache = insights;
        
        // Apply AI-enhanced anomaly detection to charts
        if (insights.anomalies && this.charts) {
            Object.keys(this.charts).forEach(chartId => {
                const metric = chartId.replace('chart-', '');
                const anomalies = insights.anomalies[metric];
                
                if (anomalies && this.charts[chartId]) {
                    this.highlightAnomalies(this.charts[chartId], anomalies);
                }
            });
        }
        
        // Update AI-powered recommendations section
        this.updateAIRecommendations(insights.recommendations || []);
    }

    /**
     * Highlights anomalies in charts based on AI detection
     */
    highlightAnomalies(chart, anomalies) {
        if (!chart || !chart.data || !chart.data.datasets || !chart.data.datasets[0]) return;
        
        // Reset any previous highlighting
        chart.data.datasets[0].pointBackgroundColor = [];
        chart.data.datasets[0].pointBorderColor = [];
        chart.data.datasets[0].pointRadius = [];
        
        // Apply anomaly highlighting
        anomalies.forEach(anomaly => {
            const index = anomaly.dataIndex;
            if (index >= 0 && index < chart.data.datasets[0].data.length) {
                chart.data.datasets[0].pointBackgroundColor[index] = anomaly.severity === 'high' ? 'rgba(255, 0, 0, 0.7)' : 'rgba(255, 165, 0, 0.7)';
                chart.data.datasets[0].pointBorderColor[index] = anomaly.severity === 'high' ? '#ff0000' : '#ffa500';
                chart.data.datasets[0].pointRadius[index] = anomaly.severity === 'high' ? 6 : 5;
            }
        });
        
        chart.update();
    }

    /**
     * Updates AI recommendations section with insights from the AI agent
     */
    updateAIRecommendations(recommendations) {
        const container = document.getElementById('vortex-ai-recommendations');
        if (!container) return;
        
        container.innerHTML = '';
        
        if (recommendations.length === 0) {
            container.innerHTML = '<p class="vortex-no-recommendations">No AI recommendations available at this time.</p>';
            return;
        }
        
        const list = document.createElement('ul');
        list.className = 'vortex-recommendations-list';
        
        recommendations.forEach(rec => {
            const item = document.createElement('li');
            item.className = `vortex-recommendation vortex-priority-${rec.priority || 'medium'}`;
            
            const title = document.createElement('h4');
            title.textContent = rec.title;
            
            const desc = document.createElement('p');
            desc.textContent = rec.description;
            
            const meta = document.createElement('div');
            meta.className = 'vortex-recommendation-meta';
            meta.innerHTML = `<span class="vortex-confidence">${Math.round(rec.confidence * 100)}% confidence</span>`;
            
            if (rec.actionable) {
                const actionBtn = document.createElement('button');
                actionBtn.className = 'vortex-action-button';
                actionBtn.textContent = rec.actionText || 'Take Action';
                actionBtn.addEventListener('click', () => this.executeAIRecommendation(rec.id, rec.action));
                meta.appendChild(actionBtn);
            }
            
            item.appendChild(title);
            item.appendChild(desc);
            item.appendChild(meta);
            list.appendChild(item);
        });
        
        container.appendChild(list);
    }

    /**
     * Executes an action recommended by the AI agent
     */
    executeAIRecommendation(recId, actionData) {
        if (!recId || !actionData) return;
        
        this.showLoadingIndicator(true);
        
        fetch('/wp-json/vortex/v1/ai/execute-recommendation', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': vortexSettings?.nonce || '',
                'X-VORTEX-AI-Request': 'true'
            },
            body: JSON.stringify({
                recommendation_id: recId,
                action: actionData
            })
        })
        .then(response => {
            if (!response.ok) throw new Error('Failed to execute AI recommendation');
            return response.json();
        })
        .then(result => {
            this.showNotification(result.message || 'AI recommendation executed successfully', 'success');
            this.refreshDashboard();
        })
        .catch(error => {
            console.error('Error executing AI recommendation:', error);
            this.showNotification('Failed to execute AI recommendation', 'error');
        })
        .finally(() => {
            this.showLoadingIndicator(false);
        });
    }

    /**
     * Handles real-time insights received from AI agents
     */
    handleAIInsight(insight) {
        if (!insight) return;
        
        // Display real-time notification for important insights
        if (insight.importance >= 8) {
            this.showAIInsightNotification(insight);
        }
        
        // Update cached insights
        if (!this.aiInsightsCache.realtimeInsights) {
            this.aiInsightsCache.realtimeInsights = [];
        }
        
        this.aiInsightsCache.realtimeInsights.unshift(insight);
        
        // Keep only the 10 most recent insights
        if (this.aiInsightsCache.realtimeInsights.length > 10) {
            this.aiInsightsCache.realtimeInsights.pop();
        }
        
        // Update UI if insights panel is open
        this.updateRealtimeInsightsPanel();
    }

    /**
     * Displays an AI insight notification to the user
     */
    showAIInsightNotification(insight) {
        const notification = document.createElement('div');
        notification.className = 'vortex-ai-insight-notification';
        notification.innerHTML = `
            <div class="vortex-insight-header">
                <i class="dashicons dashicons-lightbulb"></i>
                <h4>AI Insight</h4>
                <span class="vortex-insight-close">&times;</span>
            </div>
            <div class="vortex-insight-content">
                <p>${insight.message}</p>
                <div class="vortex-insight-meta">
                    <span>Confidence: ${Math.round(insight.confidence * 100)}%</span>
                    <span>Source: ${insight.source || 'VORTEX AI'}</span>
                </div>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        notification.querySelector('.vortex-insight-close').addEventListener('click', () => {
            notification.remove();
        });
        
        setTimeout(() => {
            notification.classList.add('vortex-insight-fadeout');
            setTimeout(() => notification.remove(), 500);
        }, 8000);
    }

    /**
     * Updates the real-time insights panel with latest AI insights
     */
    updateRealtimeInsightsPanel() {
        const panel = document.getElementById('vortex-realtime-insights');
        if (!panel || panel.style.display === 'none') return;
        
        const container = panel.querySelector('.vortex-insights-container');
        if (!container) return;
        
        container.innerHTML = '';
        
        if (!this.aiInsightsCache.realtimeInsights || this.aiInsightsCache.realtimeInsights.length === 0) {
            container.innerHTML = '<p class="vortex-no-insights">No real-time insights available.</p>';
            return;
        }
        
        const list = document.createElement('ul');
        
        this.aiInsightsCache.realtimeInsights.forEach(insight => {
            const item = document.createElement('li');
            item.className = `vortex-insight-item vortex-importance-${Math.min(Math.floor(insight.importance), 9)}`;
            
            const time = new Date(insight.timestamp || Date.now()).toLocaleTimeString();
            
            item.innerHTML = `
                <span class="vortex-insight-time">${time}</span>
                <p>${insight.message}</p>
                <div class="vortex-insight-meta">
                    <span>Confidence: ${Math.round(insight.confidence * 100)}%</span>
                </div>
            `;
            
            list.appendChild(item);
        });
        
        container.appendChild(list);
    }

    /**
     * Handles AI recommendation events from the agent system
     */
    handleAIRecommendation(recommendation) {
        // Add to recommendations cache
        if (!this.aiInsightsCache.recommendations) {
            this.aiInsightsCache.recommendations = [];
        }
        
        this.aiInsightsCache.recommendations.unshift(recommendation);
        
        // Update recommendations display
        this.updateAIRecommendations(this.aiInsightsCache.recommendations);
        
        // Show notification for high priority recommendations
        if (recommendation.priority === 'high') {
            this.showNotification(
                `New high-priority AI recommendation: ${recommendation.title}`, 
                'ai-recommendation'
            );
        }
    }

    /**
     * Handles errors from the AI agent system
     */
    handleAIError(error) {
        console.error('AI Agent System Error:', error);
        this.aiAgentStatus = 'error';
        
        // Only show notification for critical errors
        if (error.critical) {
            this.showNotification(
                `AI System Error: ${error.message || 'Unknown error occurred'}`, 
                'error'
            );
        }
    }

    bindEvents() {
        document.getElementById('vortex-time-filter')?.addEventListener('change', (e) => {
            this.filters.timeRange = e.target.value;
            this.refreshDashboard();
        });

        document.getElementById('vortex-metrics-filter')?.addEventListener('change', (e) => {
            const checkboxes = document.querySelectorAll('#vortex-metrics-filter input[type="checkbox"]');
            this.filters.metrics = Array.from(checkboxes)
                .filter(cb => cb.checked)
                .map(cb => cb.value);
            this.refreshDashboard();
        });

        document.getElementById('vortex-export-csv')?.addEventListener('click', () => {
            this.exportMetricsCSV();
        });

        document.getElementById('vortex-export-pdf')?.addEventListener('click', () => {
            this.exportMetricsPDF();
        });

        document.getElementById('vortex-toggle-ai-insights')?.addEventListener('click', () => {
            this.toggleAIInsightsPanel();
        });

        document.getElementById('vortex-toggle-predictions')?.addEventListener('click', () => {
            this.togglePredictiveAnalytics();
        });
    }

    /**
     * Toggles the AI insights panel visibility
     */
    toggleAIInsightsPanel() {
        const panel = document.getElementById('vortex-realtime-insights');
        if (!panel) return;
        
        const isVisible = panel.style.display !== 'none';
        panel.style.display = isVisible ? 'none' : 'block';
        
        const button = document.getElementById('vortex-toggle-ai-insights');
        if (button) {
            button.innerHTML = isVisible ? 
                '<i class="dashicons dashicons-lightbulb"></i> Show AI Insights' : 
                '<i class="dashicons dashicons-lightbulb"></i> Hide AI Insights';
        }
        
        // Update insights when panel becomes visible
        if (!isVisible) {
            this.updateRealtimeInsightsPanel();
        }
    }

    /**
     * Toggles predictive analytics visualization in charts
     */
    togglePredictiveAnalytics() {
        const button = document.getElementById('vortex-toggle-predictions');
        this.predictionsEnabled = !this.predictionsEnabled;
        
        if (button) {
            button.innerHTML = this.predictionsEnabled ? 
                '<i class="dashicons dashicons-chart-line"></i> Hide Predictions' : 
                '<i class="dashicons dashicons-chart-line"></i> Show Predictions';
            button.classList.toggle('vortex-button-active', this.predictionsEnabled);
        }
        
        // Update charts with/without predictions
        this.refreshDashboard();
    }

    async loadCategories() {
        try {
            const response = await fetch('/wp-json/vortex/v1/categories');
            if (!response.ok) throw new Error('Failed to load categories');
            
            const categories = await response.json();
            const container = document.getElementById('vortex-category-filter');
            
            if (container) {
                categories.forEach(category => {
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.value = category.id;
                    checkbox.id = `category-${category.id}`;
                    checkbox.addEventListener('change', () => this.updateCategoryFilters());
                    
                    const label = document.createElement('label');
                    label.htmlFor = `category-${category.id}`;
                    label.textContent = category.name;
                    
                    const div = document.createElement('div');
                    div.className = 'vortex-filter-item';
                    div.appendChild(checkbox);
                    div.appendChild(label);
                    
                    container.appendChild(div);
                });
            }
        } catch (error) {
            console.error('Error loading categories:', error);
            this.showNotification('Failed to load categories', 'error');
        }
    }

    updateCategoryFilters() {
        const checkboxes = document.querySelectorAll('#vortex-category-filter input[type="checkbox"]');
        this.filters.categories = Array.from(checkboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);
        this.refreshDashboard();
    }

    async refreshDashboard() {
        this.showLoadingIndicator(true);
        try {
            const metricsData = await this.fetchMetricsData();
            
            // Apply AI-enhanced data processing if available
            const enhancedData = this.aiAgentStatus === 'active' ? 
                await this.applyAIDataEnhancements(metricsData) : 
                metricsData;
            
            this.renderMetricsOverview(enhancedData.overview);
            this.renderTimeSeriesCharts(enhancedData.timeSeries);
            this.renderTopLists(enhancedData.topItems);
            
            // Render AI-specific sections
            if (this.aiAgentStatus === 'active') {
                this.renderAIInsights(enhancedData.aiInsights || {});
            }
        } catch (error) {
            console.error('Error refreshing dashboard:', error);
            this.showNotification('Failed to refresh metrics data', 'error');
        } finally {
            this.showLoadingIndicator(false);
        }
    }

    /**
     * Applies AI-enhanced data processing to metrics data
     */
    async applyAIDataEnhancements(data) {
        if (!data || this.aiAgentStatus !== 'active') return data;
        
        try {
            // Request AI processing of the data
            const response = await fetch('/wp-json/vortex/v1/ai/enhance-metrics', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': vortexSettings?.nonce || '',
                    'X-VORTEX-AI-Request': 'true'
                },
                body: JSON.stringify({
                    metrics: data,
                    filters: this.filters,
                    includePredictions: this.predictionsEnabled
                })
            });
            
            if (!response.ok) return data;
            
            const enhancedData = await response.json();
            
            // Log AI processing metrics for telemetry
            if (enhancedData.aiMetrics) {
                console.log('AI Enhancement Metrics:', enhancedData.aiMetrics);
                delete enhancedData.aiMetrics; // Remove from data before returning
            }
            
            return enhancedData;
        } catch (error) {
            console.warn('AI data enhancement failed:', error);
            return data; // Return original data if enhancement fails
        }
    }

    /**
     * Renders AI-generated insights on the dashboard
     */
    renderAIInsights(insights) {
        const container = document.getElementById('vortex-ai-insights');
        if (!container) return;
        
        container.innerHTML = '';
        
        if (!insights || Object.keys(insights).length === 0) {
            container.innerHTML = '<p class="vortex-no-insights">No AI insights available for the current data.</p>';
            return;
        }
        
        // Render key findings
        if (insights.keyFindings && insights.keyFindings.length > 0) {
            const findingsSection = document.createElement('div');
            findingsSection.className = 'vortex-insights-section';
            
            const title = document.createElement('h3');
            title.textContent = 'Key Findings';
            findingsSection.appendChild(title);
            
            const list = document.createElement('ul');
            list.className = 'vortex-findings-list';
            
            insights.keyFindings.forEach(finding => {
                const item = document.createElement('li');
                item.innerHTML = `
                    <div class="vortex-finding-icon"><i class="dashicons ${finding.type === 'positive' ? 'dashicons-yes-alt' : finding.type === 'negative' ? 'dashicons-warning' : 'dashicons-info'}"></i></div>
                    <div class="vortex-finding-content">
                        <p>${finding.text}</p>
                        <div class="vortex-finding-meta">
                            <span class="vortex-confidence">Confidence: ${Math.round(finding.confidence * 100)}%</span>
                        </div>
                    </div>
                `;
                list.appendChild(item);
            });
            
            findingsSection.appendChild(list);
            container.appendChild(findingsSection);
        }
        
        // Render correlations
        if (insights.correlations && insights.correlations.length > 0) {
            const correlationsSection = document.createElement('div');
            correlationsSection.className = 'vortex-insights-section';
            
            const title = document.createElement('h3');
            title.textContent = 'Detected Correlations';
            correlationsSection.appendChild(title);
            
            const list = document.createElement('ul');
            list.className = 'vortex-correlations-list';
            
            insights.correlations.forEach(correlation => {
                const item = document.createElement('li');
                item.innerHTML = `
                    <div class="vortex-correlation-strength" style="background: linear-gradient(to right, #e6f7ff ${Math.round(correlation.strength * 100)}%, #f0f0f0 ${Math.round(correlation.strength * 100)}%)">
                        <span>${Math.round(correlation.strength * 100)}%</span>
                    </div>
                    <div class="vortex-correlation-content">
                        <p>${correlation.description}</p>
                    </div>
                `;
                list.appendChild(item);
            });
            
            correlationsSection.appendChild(list);
            container.appendChild(correlationsSection);
        }
    }

    async fetchMetricsData() {
        const queryParams = new URLSearchParams({
            timeRange: this.filters.timeRange,
            metrics: this.filters.metrics.join(','),
            categories: this.filters.categories.join(','),
            aiEnhanced: this.aiAnalysisEnabled ? 'true' : 'false'
        });

        const response = await fetch(`/wp-json/vortex/v1/metrics?${queryParams}`, {
            headers: {
                'X-VORTEX-AI-Request': this.aiAnalysisEnabled ? 'true' : 'false'
            }
        });
        
        if (!response.ok) throw new Error('Failed to fetch metrics data');
        return await response.json();
    }

    renderMetricsOverview(overview) {
        const container = document.getElementById('vortex-metrics-overview');
        if (!container) return;
        
        container.innerHTML = '';
        
        Object.entries(overview).forEach(([metric, value]) => {
            const card = document.createElement('div');
            card.className = 'vortex-metric-card';
            
            // Add AI-enhanced trend indicators if available
            let trendHTML = '';
            if (value.trend) {
                const trendIcon = value.trend > 0 ? 'arrow-up-alt' : (value.trend < 0 ? 'arrow-down-alt' : 'minus');
                const trendClass = value.trend > 0 ? 'positive' : (value.trend < 0 ? 'negative' : 'neutral');
                const trendValue = Math.abs(value.trend).toFixed(1);
                
                trendHTML = `
                    <div class="vortex-metric-trend vortex-trend-${trendClass}">
                        <i class="dashicons dashicons-${trendIcon}"></i>
                        <span>${trendValue}%</span>
                    </div>
                `;
            }
            
            const title = document.createElement('h3');
            title.textContent = this.formatMetricName(metric);
            
            const valueEl = document.createElement('div');
            valueEl.className = 'vortex-metric-value';
            
            // Handle both simple values and AI-enhanced objects
            if (typeof value === 'object' && value.value !== undefined) {
                valueEl.textContent = this.formatMetricValue(metric, value.value);
                
                card.dataset.anomaly = value.isAnomaly ? 'true' : 'false';
                if (value.isAnomaly) {
                    card.classList.add('vortex-anomaly-card');
                }
            } else {
                valueEl.textContent = this.formatMetricValue(metric, value);
            }
            
            card.appendChild(title);
            card.appendChild(valueEl);
            
            if (trendHTML) {
                const trendEl = document.createElement('div');
                trendEl.innerHTML = trendHTML;
                card.appendChild(trendEl.firstElementChild);
            }
            
            container.appendChild(card);
        });
    }

    renderTimeSeriesCharts(timeSeriesData) {
        const container = document.getElementById('vortex-time-series');
        if (!container) return;
        
        // Clear previous charts
        Object.values(this.charts).forEach(chart => chart.destroy());
        this.charts = {};
        container.innerHTML = '';
        
        this.filters.metrics.forEach(metric => {
            if (!timeSeriesData[metric]) return;
            
            const chartContainer = document.createElement('div');
            chartContainer.className = 'vortex-chart-container';
            
            const canvas = document.createElement('canvas');
            canvas.id = `chart-${metric}`;
            chartContainer.appendChild(canvas);
            container.appendChild(chartContainer);
            
            this.createChart(canvas.id, this.formatMetricName(metric), timeSeriesData[metric]);
        });
    }

    createChart(canvasId, title, data) {
        const ctx = document.getElementById(canvasId).getContext('2d');
        
        // Check for predictions in the data (from AI)
        const hasPredictions = this.predictionsEnabled && 
                              data.predictions && 
                              data.predictions.length > 0;
        
        const datasets = [{
            label: title,
            data: data.map ? data.map(point => point.value) : data.actual?.map(point => point.value) || [],
            borderColor: '#3498db',
            backgroundColor: 'rgba(52, 152, 219, 0.1)',
            borderWidth: 2,
            tension: 0.3,
            fill: true
        }];
        
        // Add predictions dataset if available
        if (hasPredictions) {
            datasets.push({
                label: `${title} (AI Prediction)`,
                data: data.predictions.map(point => point.value),
                borderColor: '#9b59b6',
                backgroundColor: 'rgba(155, 89, 182, 0.1)',
                borderWidth: 2,
                borderDash: [5, 5],
                tension: 0.3,
                fill: false
            });
            
            // Add confidence interval if available
            if (data.confidenceInterval) {
                datasets.push({
                    label: 'Confidence Interval (Upper)',
                    data: data.confidenceInterval.upper,
                    borderColor: 'rgba(155, 89, 182, 0.3)',
                    backgroundColor: 'rgba(155, 89, 182, 0.05)',
                    borderWidth: 1,
                    pointRadius: 0,
                    fill: '+1'
                });
                
                datasets.push({
                    label: 'Confidence Interval (Lower)',
                    data: data.confidenceInterval.lower,
                    borderColor: 'rgba(155, 89, 182, 0.3)',
                    backgroundColor: 'rgba(155, 89, 182, 0.05)',
                    borderWidth: 1,
                    pointRadius: 0,
                    fill: false
                });
            }
        }
        
        // Get labels (dates) from appropriate source based on data structure
        const labels = data.map ? 
            data.map(point => point.date) : 
            data.actual?.map(point => point.date) || [];
        
        this.charts[canvasId] = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    title: {
                        display: true,
                        text: title
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            afterBody: (tooltipItems) => {
                                // Add AI insight to tooltip if available
                                const dataIndex = tooltipItems[0].dataIndex;
                                const aiInsight = data.insights && data.insights[dataIndex];
                                
                                if (aiInsight) {
                                    return [`\nAI Insight: ${aiInsight.text}`];
                                }
                                return [];
                            }
                        }
                    },
                    annotation: {
                        annotations: this.generateChartAnnotations(data)
                    }
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: 'Date'
                        }
                    },
                    y: {
                        display: true,
                        title: {
                            display: true,
                            text: title
                        },
                        beginAtZero: true
                    }
                }
            }
        });
    }

    /**
     * Generates chart annotations based on AI-detected patterns and events
     */
    generateChartAnnotations(data) {
        if (!data.annotations) return {};
        
        const annotations = {};
        
        // Process line annotations (trend changes, significant events)
        data.annotations.forEach((annotation, index) => {
            annotations[`annotation-${index}`] = {
                type: annotation.type || 'line',
                xMin: annotation.xValue,
                xMax: annotation.xValue,
                borderColor: annotation.color || 'rgba(255, 99, 132, 0.8)',
                borderWidth: 2,
                label: {
                    content: annotation.label || '',
                    enabled: !!annotation.label,
                    position: 'top'
                }
            };
        });
        
        return annotations;
    }

    renderTopLists(topItemsData) {
        const container = document.getElementById('vortex-top-lists');
        if (!container) return;
        
        container.innerHTML = '';
        
        Object.entries(topItemsData).forEach(([category, items]) => {
            const listContainer = document.createElement('div');
            listContainer.className = 'vortex-top-list';
            
            const title = document.createElement('h3');
            title.textContent = this.formatTopListTitle(category);
            listContainer.appendChild(title);
            
            const list = document.createElement('ul');
            items.forEach(item => {
                const listItem = document.createElement('li');
                
                // Check for AI-enhanced item data
                let aiInsightIcon = '';
                if (item.aiInsight) {
                    aiInsightIcon = `
                        <span class="vortex-ai-insight-icon" title="${item.aiInsight}">
                            <i class="dashicons dashicons-lightbulb"></i>
                        </span>
                    `;
                }
                
                // Check for momentum indicators
                let momentumIndicator = '';
                if (item.momentum !== undefined) {
                    const momentumIcon = item.momentum > 0 ? 'arrow-up-alt' : (item.momentum < 0 ? 'arrow-down-alt' : '');
                    const momentumClass = item.momentum > 0 ? 'positive' : (item.momentum < 0 ? 'negative' : 'neutral');
                    
                    if (momentumIcon) {
                        momentumIndicator = `
                            <span class="vortex-momentum vortex-momentum-${momentumClass}">
                                <i class="dashicons dashicons-${momentumIcon}"></i>
                            </span>
                        `;
                    }
                }
                
                listItem.innerHTML = `
                    <span class="vortex-item-name">${item.name} ${aiInsightIcon}</span>
                    <span class="vortex-item-value">${this.formatMetricValue(category, item.value)} ${momentumIndicator}</span>
                `;
                list.appendChild(listItem);
            });
            
            listContainer.appendChild(list);
            container.appendChild(listContainer);
        });
    }

    formatMetricName(metric) {
        const names = {
            'sales': 'Total Sales',
            'revenue': 'Revenue',
            'installations': 'Installations',
            'reviews': 'Reviews',
            'activeUsers': 'Active Users',
            'averageRating': 'Average Rating'
        };
        
        return names[metric] || metric.replace(/([A-Z])/g, ' $1').replace(/^./, str => str.toUpperCase());
    }

    formatMetricValue(metric, value) {
        if (metric === 'revenue') {
            return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value);
        } else if (metric === 'averageRating') {
            return value.toFixed(1);
        } else if (typeof value === 'number') {
            return value.toLocaleString();
        }
        return value;
    }

    formatTopListTitle(category) {
        const titles = {
            'topSelling': 'Top Selling Plugins',
            'topRated': 'Top Rated Plugins',
            'topInstalled': 'Most Installed Plugins',
            'topCategories': 'Most Popular Categories'
        };
        
        return titles[category] || category.replace(/([A-Z])/g, ' $1').replace(/^./, str => str.toUpperCase());
    }

    exportMetricsCSV() {
        this.showLoadingIndicator(true);
        
        // Include AI analysis flag in export params if enabled
        const exportParams = this.getExportParams();
        
        fetch(`/wp-json/vortex/v1/metrics/export/csv?${exportParams}`, {
            headers: {
                'X-VORTEX-AI-Request': this.aiAnalysisEnabled ? 'true' : 'false'
            }
        })
            .then(response => {
                if (!response.ok) throw new Error('Export failed');
                return response.blob();
            })
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = `vortex-metrics-${new Date().toISOString().slice(0, 10)}.csv`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
            })
            .catch(error => {
                console.error('CSV export error:', error);
                this.showNotification('Failed to export metrics as CSV', 'error');
            })
            .finally(() => {
                this.showLoadingIndicator(false);
            });
    }

    exportMetricsPDF() {
        this.showLoadingIndicator(true);
        
        // Include AI analysis flag in export params if enabled
        const exportParams = this.getExportParams();
        
        fetch(`/wp-json/vortex/v1/metrics/export/pdf?${exportParams}`, {
            headers: {
                'X-VORTEX-AI-Request': this.aiAnalysisEnabled ? 'true' : 'false'
            }
        })
            .then(response => {
                if (!response.ok) throw new Error('Export failed');
                return response.blob();
            })
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = url;
                a.download = `vortex-metrics-${new Date().toISOString().slice(0, 10)}.pdf`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
            })
            .catch(error => {
                console.error('PDF export error:', error);
                this.showNotification('Failed to export metrics as PDF', 'error');
            })
            .finally(() => {
                this.showLoadingIndicator(false);
            });
    }

    getExportParams() {
        return new URLSearchParams({
            timeRange: this.filters.timeRange,
            metrics: this.filters.metrics.join(','),
            categories: this.filters.categories.join(',')
        }).toString();
    }

    showLoadingIndicator(show) {
        const loader = document.getElementById('vortex-loader');
        if (loader) {
            loader.style.display = show ? 'flex' : 'none';
        }
    }

    showNotification(message, type = 'info') {
        const notification = document.getElementById('vortex-notification');
        if (!notification) return;
        
        notification.textContent = message;
        notification.className = `vortex-notification vortex-${type}`;
        notification.style.display = 'block';
        
        setTimeout(() => {
            notification.style.display = 'none';
        }, 5000);
    }
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.vortexMetricsDashboard = new VortexMetricsDashboard();
}); 