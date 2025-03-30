/**
 * Thorius Multi-Agent Coordination
 * 
 * Handles communication between Thorius and other AI agents
 */
(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        const thorius = window.vortexThorius || {};
        
        // Available AI agents
        thorius.agents = {
            'huraii': {
                name: 'HURAII',
                description: 'Advanced AI image generation and transformation',
                capabilities: ['image_generation', 'image_transformation', 'style_transfer', 'nft_creation'],
                endpoint: 'vortex_huraii_process',
                isAvailable: function() {
                    return vortex_thorius_params.agents.includes('huraii');
                }
            },
            'cloe': {
                name: 'CLOE',
                description: 'Art discovery and curation assistant',
                capabilities: ['art_discovery', 'style_analysis', 'art_recommendations', 'artist_discovery'],
                endpoint: 'vortex_cloe_process',
                isAvailable: function() {
                    return vortex_thorius_params.agents.includes('cloe');
                }
            },
            'strategist': {
                name: 'Business Strategist',
                description: 'Market insights and trend analysis',
                capabilities: ['market_analysis', 'trend_prediction', 'price_optimization', 'audience_analysis'],
                endpoint: 'vortex_strategist_process',
                isAvailable: function() {
                    return vortex_thorius_params.agents.includes('strategist');
                }
            }
        };
        
        // Register message handlers for agent-specific requests
        $(document).on('thorius:response', function(e, data) {
            if (data && data.agent_request) {
                handleAgentRequest(data.agent_request);
            }
        });
        
        // Handle agent requests
        function handleAgentRequest(request) {
            if (!request.agent || !request.action) {
                return;
            }
            
            const agent = thorius.agents[request.agent.toLowerCase()];
            
            if (!agent || !agent.isAvailable()) {
                appendAgentError(request.agent, 'Agent not available');
                return;
            }
            
            switch (request.agent.toLowerCase()) {
                case 'huraii':
                    processHuraiiRequest(request);
                    break;
                case 'cloe':
                    processCloeRequest(request);
                    break;
                case 'strategist':
                    processStrategistRequest(request);
                    break;
            }
        }
        
        // Process HURAII requests
        function processHuraiiRequest(request) {
            if (request.action === 'generate_image') {
                // Show loading state
                appendAgentMessage('HURAII', 'Generating image based on your description...');
                
                $.ajax({
                    url: vortex_thorius_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'vortex_huraii_process',
                        request_type: 'generate',
                        prompt: request.parameters.prompt,
                        style: request.parameters.style || 'realistic',
                        nonce: vortex_thorius_params.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data.image_url) {
                            // Display generated image
                            appendAgentImage('HURAII', response.data.image_url, request.parameters.prompt);
                        } else {
                            appendAgentError('HURAII', response.data.message || 'Image generation failed');
                        }
                    },
                    error: function() {
                        appendAgentError('HURAII', 'Connection error while generating image');
                    }
                });
            }
        }
        
        // Process CLOE requests
        function processCloeRequest(request) {
            if (request.action === 'discover_art') {
                // Show loading state
                appendAgentMessage('CLOE', 'Searching for artworks that match your criteria...');
                
                $.ajax({
                    url: vortex_thorius_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'vortex_cloe_process',
                        request_type: 'discover',
                        criteria: JSON.stringify(request.parameters),
                        nonce: vortex_thorius_params.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data.artworks) {
                            // Display discovered artworks
                            appendAgentResults('CLOE', response.data.artworks);
                        } else {
                            appendAgentError('CLOE', response.data.message || 'Art discovery failed');
                        }
                    },
                    error: function() {
                        appendAgentError('CLOE', 'Connection error while discovering art');
                    }
                });
            }
        }
        
        // Process Business Strategist requests
        function processStrategistRequest(request) {
            if (request.action === 'analyze_market') {
                // Show loading state
                appendAgentMessage('Strategist', 'Analyzing market trends for your query...');
                
                $.ajax({
                    url: vortex_thorius_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'vortex_strategist_process',
                        request_type: 'analyze',
                        parameters: JSON.stringify(request.parameters),
                        nonce: vortex_thorius_params.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data.analysis) {
                            // Display market analysis
                            appendAgentMessage('Strategist', response.data.analysis);
                        } else {
                            appendAgentError('Strategist', response.data.message || 'Market analysis failed');
                        }
                    },
                    error: function() {
                        appendAgentError('Strategist', 'Connection error while analyzing market');
                    }
                });
            }
        }
        
        // Helper function to append agent message
        function appendAgentMessage(agent, message) {
            const $messagesContainer = $('#vortex-thorius-messages');
            
            const messageHtml = `
                <div class="vortex-thorius-message vortex-thorius-message-agent">
                    <div class="vortex-thorius-agent-badge">${agent}</div>
                    <div class="vortex-thorius-agent-message">${message}</div>
                </div>
            `;
            
            $messagesContainer.append(messageHtml);
            $messagesContainer.scrollTop($messagesContainer[0].scrollHeight);
        }
        
        // Helper function to append agent image
        function appendAgentImage(agent, imageUrl, caption) {
            const $messagesContainer = $('#vortex-thorius-messages');
            
            const messageHtml = `
                <div class="vortex-thorius-message vortex-thorius-message-agent">
                    <div class="vortex-thorius-agent-badge">${agent}</div>
                    <div class="vortex-thorius-agent-content">
                        <img src="${imageUrl}" alt="${caption}" class="vortex-thorius-agent-image">
                        <p class="vortex-thorius-agent-caption">${caption}</p>
                        <div class="vortex-thorius-agent-actions">
                            <button class="vortex-thorius-agent-btn vortex-save-image" data-url="${imageUrl}">Save</button>
                            <button class="vortex-thorius-agent-btn vortex-create-nft" data-url="${imageUrl}">Create NFT</button>
                        </div>
                    </div>
                </div>
            `;
            
            $messagesContainer.append(messageHtml);
            $messagesContainer.scrollTop($messagesContainer[0].scrollHeight);
        }
        
        // Helper function to append agent results
        function appendAgentResults(agent, results) {
            const $messagesContainer = $('#vortex-thorius-messages');
            
            let resultsHtml = '';
            results.forEach(item => {
                resultsHtml += `
                    <div class="vortex-thorius-result-item">
                        <img src="${item.thumbnail}" alt="${item.title}" class="vortex-thorius-result-image">
                        <div class="vortex-thorius-result-info">
                            <h4>${item.title}</h4>
                            <p>${item.artist}</p>
                            <p class="vortex-thorius-result-price">${item.price}</p>
                        </div>
                        <a href="${item.url}" class="vortex-thorius-result-link" target="_blank">View</a>
                    </div>
                `;
            });
            
            const messageHtml = `
                <div class="vortex-thorius-message vortex-thorius-message-agent">
                    <div class="vortex-thorius-agent-badge">${agent}</div>
                    <div class="vortex-thorius-agent-content">
                        <p class="vortex-thorius-agent-intro">Here are the results that match your criteria:</p>
                        <div class="vortex-thorius-results-grid">
                            ${resultsHtml}
                        </div>
                    </div>
                </div>
            `;
            
            $messagesContainer.append(messageHtml);
            $messagesContainer.scrollTop($messagesContainer[0].scrollHeight);
        }
        
        // Helper function to append agent error
        function appendAgentError(agent, errorMessage) {
            const $messagesContainer = $('#vortex-thorius-messages');
            
            const messageHtml = `
                <div class="vortex-thorius-message vortex-thorius-message-agent vortex-thorius-message-error">
                    <div class="vortex-thorius-agent-badge">${agent}</div>
                    <div class="vortex-thorius-agent-message">
                        <span class="vortex-thorius-error-icon">⚠️</span> ${errorMessage}
                    </div>
                </div>
            `;
            
            $messagesContainer.append(messageHtml);
            $messagesContainer.scrollTop($messagesContainer[0].scrollHeight);
        }
        
        // Add handlers for agent action buttons
        $(document).on('click', '.vortex-save-image', function() {
            const imageUrl = $(this).data('url');
            
            $.ajax({
                url: vortex_thorius_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_huraii_save_image',
                    image_url: imageUrl,
                    nonce: vortex_thorius_params.nonce
                },
                success: function(response) {
                    if (response.success) {
                        appendAgentMessage('HURAII', 'Image saved successfully to your gallery.');
                    } else {
                        appendAgentError('HURAII', response.data.message || 'Failed to save image');
                    }
                },
                error: function() {
                    appendAgentError('HURAII', 'Connection error while saving image');
                }
            });
        });
        
        $(document).on('click', '.vortex-create-nft', function() {
            const imageUrl = $(this).data('url');
            
            // Switch to NFT tab if available
            if ($('.vortex-thorius-tab[data-tab="nft"]').length) {
                $('.vortex-thorius-tab[data-tab="nft"]').click();
            }
            
            // Pass the image URL to the NFT creation form
            if ($('#vortex-nft-image-url').length) {
                $('#vortex-nft-image-url').val(imageUrl);
                $('#vortex-nft-preview').attr('src', imageUrl).show();
            }
            
            appendAgentMessage('HURAII', 'Image prepared for NFT creation. Please complete the details in the NFT tab.');
        });
    });
    
})(jQuery); 