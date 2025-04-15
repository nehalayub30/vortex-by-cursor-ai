/**
 * VORTEX AI Agent Insights JavaScript
 * 
 * Handles the interactive features of the AI agent insights display
 */
(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        initAgentInsights();
    });
    
    /**
     * Initialize Agent Insights functionality
     */
    function initAgentInsights() {
        const containers = $('.vortex-agent-insights');
        if (containers.length === 0) return;
        
        containers.each(function() {
            const container = $(this);
            const agent = container.data('agent');
            const nonce = container.data('nonce');
            
            // Handle agent filter change
            container.on('change', '.agent-filter-select', function() {
                const selectedAgent = $(this).val();
                refreshInsights(container, selectedAgent, container.data('type'), container.data('limit'));
            });
            
            // Handle insight type filter change
            container.on('change', '.insight-type-select', function() {
                const selectedType = $(this).val();
                refreshInsights(container, container.data('agent'), selectedType, container.data('limit'));
            });
            
            // Handle load more button
            container.on('click', '.load-more-insights-btn', function() {
                const btn = $(this);
                const offset = parseInt(btn.data('offset'));
                
                // Show loading indicator
                btn.html('<i class="fas fa-spinner fa-spin"></i> Loading...');
                btn.prop('disabled', true);
                
                // AJAX call to get more insights
                $.ajax({
                    url: vortex_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'vortex_get_agent_insights',
                        security: nonce,
                        agent: container.data('agent'),
                        insight_type: container.data('type'),
                        limit: container.data('limit'),
                        offset: offset
                    },
                    success: function(response) {
                        if (response.success && response.data.insights.length > 0) {
                            // Append new insights to container
                            appendInsightsToContainer(container, response.data.insights);
                            
                            // Update offset for next load
                            btn.data('offset', offset + response.data.insights.length);
                            
                            // Hide button if no more insights
                            if (response.data.insights.length < container.data('limit')) {
                                btn.parent().hide();
                            }
                        } else {
                            // No more insights, hide the button
                            btn.parent().hide();
                        }
                        
                        // Reset button
                        btn.html('<i class="fas fa-sync-alt"></i> Load More Insights');
                        btn.prop('disabled', false);
                        
                        // Update total count
                        container.find('#total-insights-count').text(
                            parseInt(container.find('#total-insights-count').text()) + response.data.insights.length
                        );
                    },
                    error: function() {
                        // Reset button and show error
                        btn.html('<i class="fas fa-sync-alt"></i> Load More Insights');
                        btn.prop('disabled', false);
                        alert('Error loading more insights. Please try again.');
                    }
                });
            });
            
            // Handle insight feedback
            container.on('click', '.insight-feedback-btn', function() {
                const btn = $(this);
                const insightId = btn.data('insight-id');
                const feedback = btn.data('feedback');
                
                // Disable buttons
                btn.closest('.insight-actions').find('button').prop('disabled', true);
                
                // AJAX call to submit feedback
                $.ajax({
                    url: vortex_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'vortex_submit_insight_feedback',
                        security: nonce,
                        insight_id: insightId,
                        feedback: feedback
                    },
                    success: function(response) {
                        if (response.success) {
                            // Show thank you message
                            btn.closest('.insight-actions').html('<div class="feedback-thanks">Thank you for your feedback!</div>');
                        } else {
                            // Re-enable buttons
                            btn.closest('.insight-actions').find('button').prop('disabled', false);
                            alert('Error submitting feedback. Please try again.');
                        }
                    },
                    error: function() {
                        // Re-enable buttons
                        btn.closest('.insight-actions').find('button').prop('disabled', false);
                        alert('Error submitting feedback. Please try again.');
                    }
                });
            });
        });
    }
    
    /**
     * Refresh insights with new filters
     */
    function refreshInsights(container, selectedAgent, selectedType, limit) {
        // Update data attributes
        container.data('agent', selectedAgent);
        container.data('type', selectedType);
        
        // Show loading indicator
        container.find('.insights-container').html('<div class="insights-loading"><i class="fas fa-spinner fa-spin"></i> Loading insights...</div>');
        
        // AJAX call to get insights
        $.ajax({
            url: vortex_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'vortex_get_agent_insights',
                security: container.data('nonce'),
                agent: selectedAgent,
                insight_type: selectedType,
                limit: limit,
                offset: 0
            },
            success: function(response) {
                if (response.success) {
                    // Update container classes
                    container.removeClass(function(index, className) {
                        return (className.match(/(^|\s)insights-agent-\S+/g) || []).join(' ');
                    }).removeClass(function(index, className) {
                        return (className.match(/(^|\s)insights-type-\S+/g) || []).join(' ');
                    });
                    
                    container.addClass('insights-agent-' + selectedAgent);
                    container.addClass('insights-type-' + selectedType);
                    
                    // Clear container and append new insights
                    container.find('.insights-container').empty();
                    
                    if (response.data.insights.length > 0) {
                        appendInsightsToContainer(container, response.data.insights);
                        
                        // Show/hide load more button
                        if (response.data.insights.length >= limit) {
                            if (container.find('.insights-load-more').length === 0) {
                                container.find('.insights-container').after(
                                    '<div class="insights-load-more">' +
                                    '<button class="load-more-insights-btn" data-agent="' + selectedAgent + '" data-type="' + selectedType + '" data-offset="' + limit + '">' +
                                    '<i class="fas fa-sync-alt"></i> Load More Insights' +
                                    '</button>' +
                                    '</div>'
                                );
                            } else {
                                container.find('.insights-load-more').show();
                                container.find('.load-more-insights-btn').data('offset', limit);
                            }
                        } else {
                            container.find('.insights-load-more').hide();
                        }
                    } else {
                        container.find('.insights-container').html(
                            '<div class="no-insights">' +
                            '<p>No insights available for the selected criteria.</p>' +
                            '</div>'
                        );
                        container.find('.insights-load-more').hide();
                    }
                    
                    // Update total count
                    container.find('#total-insights-count').text(response.data.insights.length);
                } else {
                    container.find('.insights-container').html(
                        '<div class="insights-error">' +
                        '<p>Error loading insights. Please try again.</p>' +
                        '</div>'
                    );
                }
            },
            error: function() {
                container.find('.insights-container').html(
                    '<div class="insights-error">' +
                    '<p>Error loading insights. Please try again.</p>' +
                    '</div>'
                );
            }
        });
    }
    
    /**
     * Append insights to container
     */
    function appendInsightsToContainer(container, insights) {
        const insightsContainer = container.find('.insights-container');
        
        insights.forEach(function(insight) {
            const insightCard = $('<div class="insight-card"></div>').css('border-color', insight.color);
            
            const insightHeader = $('<div class="insight-header"></div>');
            insightHeader.append(
                $('<div class="insight-agent"></div>')
                    .text(insight.agent_display_name)
                    .css('background-color', insight.color)
            );
            insightHeader.append(
                $('<div class="insight-type"></div>').text(insight.insight_type.charAt(0).toUpperCase() + insight.insight_type.slice(1))
            );
            insightHeader.append(
                $('<div class="insight-confidence"></div>').append(
                    $('<span class="confidence-label"></span>').text('Confidence:'),
                    $('<span class="confidence-value"></span>').text(insight.confidence + '%')
                )
            );
            
            const insightTitle = $('<div class="insight-title"></div>').append(
                $('<h3></h3>').text(insight.title)
            );
            
            const insightContent = $('<div class="insight-content"></div>').html(insight.content);
            
            const insightFooter = $('<div class="insight-footer"></div>');
            insightFooter.append(
                $('<div class="insight-timestamp"></div>').text(insight.time_ago)
            );
            
            if (insight.related_data) {
                try {
                    const relatedData = JSON.parse(insight.related_data);
                    if (Object.keys(relatedData).length > 0) {
                        const relatedDataContainer = $('<div class="insight-related-data"></div>');
                        relatedDataContainer.append($('<h4></h4>').text('Related Data'));
                        
                        const dataList = $('<ul class="related-data-list"></ul>');
                        Object.keys(relatedData).forEach(function(key) {
                            const value = typeof relatedData[key] === 'object' 
                                ? JSON.stringify(relatedData[key]) 
                                : relatedData[key];
                            
                            dataList.append(
                                $('<li></li>').append(
                                    $('<span class="data-key"></span>').text(key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) + ':'),
                                    $('<span class="data-value"></span>').text(value)
                                )
                            );
                        });
                        
                        relatedDataContainer.append(dataList);
                        insightCard.append(relatedDataContainer);
                    }
                } catch (e) {
                    console.error('Error parsing related data:', e);
                }
            }
            
            if (vortex_ajax.is_user_logged_in) {
                insightFooter.append(
                    $('<div class="insight-actions"></div>').append(
                        $('<button class="insight-feedback-btn" data-insight-id="' + insight.id + '" data-feedback="helpful"></button>')
                            .html('<i class="fas fa-thumbs-up"></i> Helpful'),
                        $('<button class="insight-feedback-btn" data-insight-id="' + insight.id + '" data-feedback="not-helpful"></button>')
                            .html('<i class="fas fa-thumbs-down"></i> Not Helpful')
                    )
                );
            }
            
            insightCard.append(insightHeader, insightTitle, insightContent, insightFooter);
            insightsContainer.append(insightCard);
        });
    }
    
    // Export functions to global scope for external use
    window.vortexAgentInsights = {
        refreshInsights: function(container, agent, type, limit) {
            refreshInsights($(container), agent, type, limit);
        },
        loadMoreInsights: function(container) {
            $(container).find('.load-more-insights-btn').click();
        }
    };
    
})(jQuery); 