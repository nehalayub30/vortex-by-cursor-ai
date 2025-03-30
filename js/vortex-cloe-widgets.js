/**
 * VORTEX Cloe AI Agent Widgets
 * Handles all widget functionality and interactions
 */

(function($) {
    'use strict';

    // Widget initialization
    $(document).ready(function() {
        initializeGreetingWidget();
        initializeRecommendationsWidget();
        initializePreferencesWidget();
        initializeInsightsWidget();
        applyThemeColors();
    });

    // Greeting Widget
    function initializeGreetingWidget() {
        const $greetingWidget = $('.vortex-greeting-widget');
        if (!$greetingWidget.length) return;

        // Update greeting based on time of day
        updateGreetingTime();
        setInterval(updateGreetingTime, 60000); // Update every minute

        // Handle refresh button
        $greetingWidget.on('click', '.vortex-refresh-greeting', function(e) {
            e.preventDefault();
            const $button = $(this);
            const $message = $greetingWidget.find('.vortex-greeting-message');
            
            $button.prop('disabled', true).addClass('vortex-loading');
            
            $.ajax({
                url: vortexCloe.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_get_greeting',
                    nonce: vortexCloe.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $message.html(response.data.message);
                        showSuccess('Greeting updated successfully');
                    } else {
                        showError(response.data.message || 'Failed to update greeting');
                    }
                },
                error: function() {
                    showError('Failed to update greeting');
                },
                complete: function() {
                    $button.prop('disabled', false).removeClass('vortex-loading');
                }
            });
        });
    }

    // Recommendations Widget
    function initializeRecommendationsWidget() {
        const $recommendationsWidget = $('.vortex-recommendations-widget');
        if (!$recommendationsWidget.length) return;

        // Handle recommendation card clicks
        $recommendationsWidget.on('click', '.vortex-recommendation-card', function() {
            const $card = $(this);
            const recommendationId = $card.data('id');
            
            $.ajax({
                url: vortexCloe.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_track_recommendation',
                    nonce: vortexCloe.nonce,
                    recommendation_id: recommendationId
                },
                success: function(response) {
                    if (response.success) {
                        $card.addClass('vortex-recommendation-clicked');
                        setTimeout(() => {
                            $card.removeClass('vortex-recommendation-clicked');
                        }, 200);
                    }
                }
            });
        });

        // Handle refresh recommendations
        $recommendationsWidget.on('click', '.vortex-refresh-recommendations', function(e) {
            e.preventDefault();
            const $button = $(this);
            const $grid = $recommendationsWidget.find('.vortex-recommendations-grid');
            
            $button.prop('disabled', true).addClass('vortex-loading');
            $grid.addClass('vortex-loading');
            
            $.ajax({
                url: vortexCloe.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_get_recommendations',
                    nonce: vortexCloe.nonce
                },
                success: function(response) {
                    if (response.success) {
                        updateRecommendationsGrid(response.data.recommendations);
                        showSuccess('Recommendations updated successfully');
                    } else {
                        showError(response.data.message || 'Failed to update recommendations');
                    }
                },
                error: function() {
                    showError('Failed to update recommendations');
                },
                complete: function() {
                    $button.prop('disabled', false).removeClass('vortex-loading');
                    $grid.removeClass('vortex-loading');
                }
            });
        });
    }

    // Preferences Widget
    function initializePreferencesWidget() {
        const $preferencesWidget = $('.vortex-preferences-widget');
        if (!$preferencesWidget.length) return;

        // Handle preference option selection
        $preferencesWidget.on('click', '.vortex-preference-option', function() {
            const $option = $(this);
            const $group = $option.closest('.vortex-preference-group');
            const preferenceType = $group.data('type');
            
            $group.find('.vortex-preference-option').removeClass('selected');
            $option.addClass('selected');
            
            // Update preferences via AJAX
            $.ajax({
                url: vortexCloe.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_update_preferences',
                    nonce: vortexCloe.nonce,
                    preference_type: preferenceType,
                    value: $option.data('value')
                },
                success: function(response) {
                    if (response.success) {
                        showSuccess('Preferences updated successfully');
                    } else {
                        showError(response.data.message || 'Failed to update preferences');
                        $option.removeClass('selected');
                    }
                },
                error: function() {
                    showError('Failed to update preferences');
                    $option.removeClass('selected');
                }
            });
        });
    }

    // Insights Widget
    function initializeInsightsWidget() {
        const $insightsWidget = $('.vortex-insights-widget');
        if (!$insightsWidget.length) return;

        // Handle insight card interactions
        $insightsWidget.on('click', '.vortex-insight-card', function() {
            const $card = $(this);
            const insightId = $card.data('id');
            
            $.ajax({
                url: vortexCloe.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_track_insight',
                    nonce: vortexCloe.nonce,
                    insight_id: insightId
                }
            });
        });

        // Handle refresh insights
        $insightsWidget.on('click', '.vortex-refresh-insights', function(e) {
            e.preventDefault();
            const $button = $(this);
            const $grid = $insightsWidget.find('.vortex-insights-grid');
            
            $button.prop('disabled', true).addClass('vortex-loading');
            $grid.addClass('vortex-loading');
            
            $.ajax({
                url: vortexCloe.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vortex_get_insights',
                    nonce: vortexCloe.nonce
                },
                success: function(response) {
                    if (response.success) {
                        updateInsightsGrid(response.data.insights);
                        showSuccess('Insights updated successfully');
                    } else {
                        showError(response.data.message || 'Failed to update insights');
                    }
                },
                error: function() {
                    showError('Failed to update insights');
                },
                complete: function() {
                    $button.prop('disabled', false).removeClass('vortex-loading');
                    $grid.removeClass('vortex-loading');
                }
            });
        });
    }

    // Helper Functions
    function updateGreetingTime() {
        const hour = new Date().getHours();
        const $greeting = $('.vortex-greeting-time');
        
        if (hour < 12) {
            $greeting.text('Good morning');
        } else if (hour < 18) {
            $greeting.text('Good afternoon');
        } else {
            $greeting.text('Good evening');
        }
    }

    function updateRecommendationsGrid(recommendations) {
        const $grid = $('.vortex-recommendations-grid');
        $grid.empty();
        
        recommendations.forEach(function(rec) {
            const card = `
                <div class="vortex-recommendation-card" data-id="${rec.id}">
                    <img src="${rec.image}" alt="${rec.title}" class="vortex-recommendation-image">
                    <h3 class="vortex-recommendation-title">${rec.title}</h3>
                    <p class="vortex-recommendation-description">${rec.description}</p>
                    <div class="vortex-recommendation-meta">
                        <span>${rec.category}</span>
                        <span>${rec.rating} ★</span>
                    </div>
                </div>
            `;
            $grid.append(card);
        });
    }

    function updateInsightsGrid(insights) {
        const $grid = $('.vortex-insights-grid');
        $grid.empty();
        
        insights.forEach(function(insight) {
            const card = `
                <div class="vortex-insight-card" data-id="${insight.id}">
                    <div class="vortex-insight-header">
                        <i class="vortex-insight-icon ${insight.icon}"></i>
                        <h3 class="vortex-insight-title">${insight.title}</h3>
                    </div>
                    <p class="vortex-insight-content">${insight.content}</p>
                    <div class="vortex-insight-metrics">
                        ${insight.metrics.map(metric => `
                            <div class="vortex-insight-metric">
                                <div class="vortex-insight-metric-value">${metric.value}</div>
                                <div class="vortex-insight-metric-label">${metric.label}</div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
            $grid.append(card);
        });
    }

    function showSuccess(message) {
        const $message = $('<div class="vortex-success">' + message + '</div>');
        $('body').append($message);
        setTimeout(() => $message.remove(), 3000);
    }

    function showError(message) {
        const $message = $('<div class="vortex-error">' + message + '</div>');
        $('body').append($message);
        setTimeout(() => $message.remove(), 3000);
    }

    function applyThemeColors() {
        const root = document.documentElement;
        const themeColors = vortexCloe.themeColors || {};
        
        Object.entries(themeColors).forEach(([key, value]) => {
            root.style.setProperty(`--vortex-${key}`, value);
        });
    }

})(jQuery); 