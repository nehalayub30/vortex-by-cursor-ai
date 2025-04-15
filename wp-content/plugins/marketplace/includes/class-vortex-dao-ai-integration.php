                    showInsightDetail(insightId);
                });
                
                // Close modal when clicking on close button
                $('.close-modal').on('click', function() {
                    $('#ai-insight-detail-modal').removeClass('show');
                });
                
                function showInsightDetail(insightId) {
                    // AJAX request to get insight details
                    $.ajax({
                        url: vortexDAOAI.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'vortex_get_ai_insight_detail',
                            insight_id: insightId,
                            nonce: vortexDAOAI.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                renderInsightDetail(response.data);
                                $('#ai-insight-detail-modal').addClass('show');
                            } else {
                                console.error('Error loading insight details:', response.data.message);
                            }
                        },
                        error: function() {
                            console.error('Error loading insight details');
                        }
                    });
                }
                
                function renderInsightDetail(insight) {
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
                                    <div class="confidence-label"><?php esc_html_e('Confidence', 'vortex'); ?></div>
                                    <div class="confidence-score-large">${insight.confidence_score.toFixed(0)}%</div>
                                </div>
                            </div>
                            
                            <div class="insight-detail-content">
                                <div class="insight-summary">
                                    <h3><?php esc_html_e('Summary', 'vortex'); ?></h3>
                                    <p>${insightData.summary || '<?php esc_html_e('No summary available', 'vortex'); ?>'}</p>
                                </div>
                                
                                <div class="insight-details">
                                    <h3><?php esc_html_e('Details', 'vortex'); ?></h3>
                                    <div class="insight-details-content">
                                        ${formatInsightDetails(insightData)}
                                    </div>
                                </div>
                                
                                ${insightData.recommendations ? `
                                <div class="insight-recommendations">
                                    <h3><?php esc_html_e('Recommendations', 'vortex'); ?></h3>
                                    <ul class="recommendations-list">
                                        ${formatRecommendations(insightData.recommendations)}
                                    </ul>
                                </div>
                                ` : ''}
                            </div>
                            
                            <div class="insight-detail-footer">
                                <div class="insight-metadata">
                                    <div class="metadata-item">
                                        <span class="metadata-label"><?php esc_html_e('Generated', 'vortex'); ?></span>
                                        <span class="metadata-value">${formattedDate}</span>
                                    </div>
                                    <div class="metadata-item">
                                        <span class="metadata-label"><?php esc_html_e('Insight ID', 'vortex'); ?></span>
                                        <span class="metadata-value">#${insight.id}</span>
                                    </div>
                                    ${insight.blockchain_ref ? `
                                    <div class="metadata-item">
                                        <span class="metadata-label"><?php esc_html_e('Blockchain Ref', 'vortex'); ?></span>
                                        <span class="metadata-value blockchain-link">
                                            <a href="https://tolascan.org/tx/${insight.blockchain_ref}" target="_blank">
                                                ${insight.blockchain_ref.substring(0, 10)}...
                                            </a>
                                        </span>
                                    </div>
                                    ` : ''}
                                </div>
                                
                                <div class="insight-actions">
                                    <button class="vortex-btn share-insight-btn" data-id="${insight.id}">
                                        <?php esc_html_e('Share Insight', 'vortex'); ?>
                                    </button>
                                    ${insight.on_blockchain ? `
                                        <div class="blockchain-verified">
                                            <span class="dashicons dashicons-shield"></span>
                                            <?php esc_html_e('Verified on blockchain', 'vortex'); ?>
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                    
                    $('.ai-insight-detail-content').html(html);
                    
                    // Add event listener for share button
                    $('.share-insight-btn').on('click', function() {
                        const insightId = $(this).data('id');
                        shareInsight(insightId, insight.agent_name, insight.insight_type, insightData.summary);
                    });
                }
                
                function formatInsightDetails(insightData) {
                    // Format insight details based on the data structure
                    let html = '';
                    
                    // If details is an object with key-value pairs
                    if (insightData.details && typeof insightData.details === 'object') {
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
                        html += `<p><?php esc_html_e('No detailed information available', 'vortex'); ?></p>`;
                    }
                    
                    return html;
                }
                
                function formatRecommendations(recommendations) {
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
                }
                
                function shareInsight(insightId, agentName, insightType, summary) {
                    // Create share data
                    const shareData = {
                        title: `AI Insight from ${agentName}`,
                        text: `Check out this ${insightType} insight from ${agentName} AI agent on VORTEX: "${summary.substring(0, 100)}..."`,
                        url: `${window.location.origin}${window.location.pathname}?insight=${insightId}`
                    };
                    
                    // Use Web Share API if available
                    if (navigator.share) {
                        navigator.share(shareData)
                            .then(() => console.log('Insight shared successfully'))
                            .catch(error => console.log('Error sharing insight:', error));
                    } else {
                        // Fallback for browsers that don't support Web Share API
                        const shareUrl = 'https://twitter.com/intent/tweet?text=' + encodeURIComponent(shareData.text + ' ' + shareData.url);
                        window.open(shareUrl, '_blank');
                    }
                }
                
                // Initialize confidence score colorization
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
            });
        </script>
        <?php
    }
    
    /**
     * AJAX handler for getting AI insight details.
     */
    public function ajax_get_ai_insight_detail() {
        check_ajax_referer('wp_rest', 'nonce');
        
        $insight_id = isset($_POST['insight_id']) ? intval($_POST['insight_id']) : 0;
        
        if (!$insight_id) {
            wp_send_json_error(['message' => __('Invalid insight ID', 'vortex')]);
            wp_die();
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'vortex_ai_dao_insights';
        
        $insight = $wpdb->get_row($wpdb->prepare("
            SELECT id, user_id, agent_type, insight_type, insight_data, confidence_score, created_at, blockchain_ref
            FROM $table_name
            WHERE id = %d
        ", $insight_id));
        
        if (!$insight) {
            wp_send_json_error(['message' => __('Insight not found', 'vortex')]);
            wp_die();
        }
        
        // Check if current user can view this insight
        if (!current_user_can('manage_options') && get_current_user_id() != $insight->user_id) {
            wp_send_json_error(['message' => __('You are not allowed to view this insight', 'vortex')]);
            wp_die();
        }
        
        $response = [
            'id' => $insight->id,
            'user_id' => $insight->user_id,
            'agent_type' => $insight->agent_type,
            'agent_name' => isset($this->ai_agents[$insight->agent_type]) ? $this->ai_agents[$insight->agent_type] : $insight->agent_type,
            'insight_type' => $insight->insight_type,
            'insight_data' => json_decode($insight->insight_data, true),
            'confidence_score' => floatval($insight->confidence_score),
            'created_at' => $insight->created_at,
            'blockchain_ref' => $insight->blockchain_ref,
            'on_blockchain' => !empty($insight->blockchain_ref)
        ];
        
        wp_send_json_success($response);
        wp_die();
    }
    
    /**
     * Get an instance of this class.
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

// Initialize the DAO AI integration
function vortex_dao_ai_integration() {
    return VORTEX_DAO_AI_Integration::get_instance();
}
add_action('plugins_loaded', 'vortex_dao_ai_integration'); 