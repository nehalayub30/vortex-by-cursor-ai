<?php
/**
 * Template for displaying VORTEX AI Agent Insights
 *
 * @link       https://vortexmarketplace.io
 * @since      1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Create nonce for AJAX calls
$nonce = wp_create_nonce('vortex_nonce');

// Get parameters from shortcode attributes
$agent = esc_attr($atts['agent']);
$insight_type = esc_attr($atts['insight_type']);
$limit = intval($atts['limit']);

// Default class
$container_class = 'vortex-agent-insights';

// Add agent-specific class if specified
if ($agent !== 'all') {
    $container_class .= ' insights-agent-' . $agent;
}

// Add insight type class
$container_class .= ' insights-type-' . $insight_type;

?>
<div class="<?php echo esc_attr($container_class); ?>" data-agent="<?php echo esc_attr($agent); ?>" data-type="<?php echo esc_attr($insight_type); ?>" data-limit="<?php echo esc_attr($limit); ?>" data-nonce="<?php echo esc_attr($nonce); ?>">
    
    <div class="insights-header">
        <h2>
            <?php 
            if ($agent === 'all') {
                echo 'AI Agent Insights';
            } else {
                echo esc_html($vortex_orchestrator->get_agent_display_name($agent)) . ' Insights';
            }
            ?>
            <?php if ($insight_type !== 'latest'): ?>
                - <?php echo esc_html(ucfirst($insight_type)); ?>
            <?php endif; ?>
        </h2>
        
        <?php if ($agent === 'all'): ?>
        <div class="agent-filter">
            <label for="agent-filter-select">Agent:</label>
            <select id="agent-filter-select" class="agent-filter-select">
                <option value="all" selected>All Agents</option>
                <option value="huraii">HURAII</option>
                <option value="cloe">CLOE</option>
                <option value="business_strategist">Business Strategist</option>
                <option value="thorius">Thorius</option>
            </select>
        </div>
        <?php endif; ?>
        
        <div class="insight-type-filter">
            <label for="insight-type-select">Type:</label>
            <select id="insight-type-select" class="insight-type-select">
                <option value="latest" <?php selected($insight_type, 'latest'); ?>>Latest</option>
                <option value="trending" <?php selected($insight_type, 'trending'); ?>>Trending</option>
                <option value="recommendations" <?php selected($insight_type, 'recommendations'); ?>>Recommendations</option>
                <option value="blockchain" <?php selected($insight_type, 'blockchain'); ?>>Blockchain</option>
                <option value="alerts" <?php selected($insight_type, 'alerts'); ?>>Alerts</option>
            </select>
        </div>
    </div>
    
    <div class="insights-container">
        <?php if (empty($insights)): ?>
            <div class="no-insights">
                <p>No insights available for the selected criteria.</p>
            </div>
        <?php else: ?>
            <?php foreach ($insights as $insight): ?>
                <div class="insight-card" style="border-color: <?php echo esc_attr($insight['color']); ?>">
                    <div class="insight-header">
                        <div class="insight-agent" style="background-color: <?php echo esc_attr($insight['color']); ?>">
                            <?php echo esc_html($insight['agent_display_name']); ?>
                        </div>
                        <div class="insight-type">
                            <?php echo esc_html(ucfirst($insight['insight_type'])); ?>
                        </div>
                        <div class="insight-confidence">
                            <span class="confidence-label">Confidence:</span>
                            <span class="confidence-value"><?php echo esc_html($insight['confidence']); ?>%</span>
                        </div>
                    </div>
                    
                    <div class="insight-title">
                        <h3><?php echo esc_html($insight['title']); ?></h3>
                    </div>
                    
                    <div class="insight-content">
                        <?php echo wp_kses_post(wpautop($insight['content'])); ?>
                    </div>
                    
                    <?php if (!empty($insight['related_data'])): ?>
                    <div class="insight-related-data">
                        <h4>Related Data</h4>
                        <div class="related-data-container">
                            <?php 
                            $related_data = json_decode($insight['related_data'], true);
                            if (is_array($related_data)): 
                            ?>
                                <ul class="related-data-list">
                                <?php foreach ($related_data as $key => $value): ?>
                                    <li>
                                        <span class="data-key"><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?>:</span>
                                        <span class="data-value"><?php echo is_array($value) ? esc_html(json_encode($value)) : esc_html($value); ?></span>
                                    </li>
                                <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="insight-footer">
                        <div class="insight-timestamp">
                            <?php echo esc_html($insight['time_ago']); ?>
                        </div>
                        <?php if (is_user_logged_in()): ?>
                        <div class="insight-actions">
                            <button class="insight-feedback-btn" data-insight-id="<?php echo esc_attr($insight['id']); ?>" data-feedback="helpful">
                                <i class="fas fa-thumbs-up"></i> Helpful
                            </button>
                            <button class="insight-feedback-btn" data-insight-id="<?php echo esc_attr($insight['id']); ?>" data-feedback="not-helpful">
                                <i class="fas fa-thumbs-down"></i> Not Helpful
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($insights) && count($insights) >= $limit): ?>
    <div class="insights-load-more">
        <button class="load-more-insights-btn" data-agent="<?php echo esc_attr($agent); ?>" data-type="<?php echo esc_attr($insight_type); ?>" data-offset="<?php echo esc_attr($limit); ?>">
            <i class="fas fa-sync-alt"></i> Load More Insights
        </button>
    </div>
    <?php endif; ?>
    
    <div class="insights-footer">
        <div class="insights-stats">
            <div class="stats-item">
                <span class="stats-label">Total Insights:</span>
                <span class="stats-value" id="total-insights-count"><?php echo count($insights); ?></span>
            </div>
            
            <div class="stats-item">
                <span class="stats-label">Latest Learning:</span>
                <span class="stats-value" id="latest-learning-time"><?php echo esc_html(get_option('vortex_last_learning_time', 'N/A') !== 'N/A' ? $vortex_orchestrator->time_elapsed_string(get_option('vortex_last_learning_time')) : 'N/A'); ?></span>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize Agent Insights functionality
    initAgentInsights();
    
    function initAgentInsights() {
        const container = $('.vortex-agent-insights');
        const agent = container.data('agent');
        const nonce = container.data('nonce');
        
        // Handle agent filter change
        container.on('change', '.agent-filter-select', function() {
            const selectedAgent = $(this).val();
            refreshInsights(selectedAgent, container.data('type'), container.data('limit'));
        });
        
        // Handle insight type filter change
        container.on('change', '.insight-type-select', function() {
            const selectedType = $(this).val();
            refreshInsights(container.data('agent'), selectedType, container.data('limit'));
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
                        appendInsightsToContainer(response.data.insights);
                        
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
                    $('#total-insights-count').text(
                        parseInt($('#total-insights-count').text()) + response.data.insights.length
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
        
        // Function to refresh insights
        function refreshInsights(selectedAgent, selectedType, limit) {
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
                    security: nonce,
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
                            appendInsightsToContainer(response.data.insights);
                            
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
                        $('#total-insights-count').text(response.data.insights.length);
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
        
        // Function to append insights to container
        function appendInsightsToContainer(insights) {
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
    }
});
</script> 