/**
 * Thorius WebSocket Client
 * Enables real-time communication with Thorius AI Concierge
 */
(function($) {
    'use strict';
    
    // Check if WebSocket is enabled in browser
    if (!('WebSocket' in window)) {
        console.log('WebSocket is not supported by your browser.');
        return;
    }
    
    // WebSocket connection
    let socket = null;
    let reconnectAttempts = 0;
    let maxReconnectAttempts = 5;
    let reconnectDelay = 2000; // 2 seconds
    
    // Initialize when document is ready
    $(document).ready(function() {
        // Connect if settings are available
        if (typeof vortex_thorius_ws !== 'undefined' && vortex_thorius_ws.enabled) {
            initWebSocket();
        }
        
        // Handle chat form submission
        $('#vortex-thorius-message-form').on('submit', function(e) {
            e.preventDefault();
            
            const $input = $('#vortex-thorius-message-input');
            const message = $input.val().trim();
            
            if (message) {
                sendChatMessage(message);
                $input.val('');
            }
        });
    });
    
    /**
     * Initialize WebSocket connection
     */
    function initWebSocket() {
        try {
            // Create WebSocket connection
            socket = new WebSocket(vortex_thorius_ws.url);
            
            // Connection opened
            socket.addEventListener('open', function(event) {
                console.log('Connected to Thorius WebSocket server');
                reconnectAttempts = 0;
                
                // Send authentication if user is logged in
                if (vortex_thorius_ws.user_id) {
                    socket.send(JSON.stringify({
                        action: 'authenticate',
                        user_id: vortex_thorius_ws.user_id,
                        nonce: vortex_thorius_ws.nonce
                    }));
                }
            });
            
            // Listen for messages
            socket.addEventListener('message', function(event) {
                handleServerMessage(event.data);
            });
            
            // Connection closed
            socket.addEventListener('close', function(event) {
                console.log('Disconnected from Thorius WebSocket server');
                
                // Attempt to reconnect
                if (reconnectAttempts < maxReconnectAttempts) {
                    setTimeout(function() {
                        reconnectAttempts++;
                        initWebSocket();
                    }, reconnectDelay);
                }
            });
            
            // Connection error
            socket.addEventListener('error', function(event) {
                console.error('WebSocket error:', event);
            });
            
        } catch (error) {
            console.error('WebSocket connection error:', error);
        }
    }
    
    /**
     * Send chat message to server
     */
    function sendChatMessage(message) {
        // Add user message to UI
        addChatMessage('user', message);
        
        // If WebSocket is connected, send via WebSocket
        if (socket && socket.readyState === WebSocket.OPEN) {
            socket.send(JSON.stringify({
                action: 'chat',
                message: message
            }));
        } else {
            // Otherwise use AJAX fallback
            $.ajax({
                url: vortex_thorius_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_thorius_chat',
                    nonce: vortex_thorius_params.nonce,
                    message: message
                },
                success: function(response) {
                    if (response.success) {
                        addChatMessage('assistant', response.data.message);
                    }
                }
            });
        }
    }
    
    /**
     * Handle message from server
     */
    function handleServerMessage(data) {
        try {
            const message = JSON.parse(data);
            
            switch (message.action) {
                case 'chat_response':
                    addChatMessage('assistant', message.message);
                    break;
                    
                case 'agent_response':
                    handleAgentResponse(message);
                    break;
                    
                case 'notification':
                    showNotification(message.message, message.type);
                    break;
            }
            
        } catch (error) {
            console.error('Error parsing WebSocket message:', error);
        }
    }
    
    /**
     * Add message to chat UI
     */
    function addChatMessage(sender, message) {
        const $messagesContainer = $('#vortex-thorius-messages');
        const $messageElement = $('<div>', {
            class: `vortex-thorius-message vortex-thorius-${sender}-message`
        });
        
        const $content = $('<div>', {
            class: 'vortex-thorius-message-content',
            html: message
        });
        
        $messageElement.append($content);
        $messagesContainer.append($messageElement);
        
        // Scroll to bottom
        $messagesContainer.scrollTop($messagesContainer[0].scrollHeight);
    }
    
    /**
     * Handle agent response
     */
    function handleAgentResponse(data) {
        switch (data.agent) {
            case 'huraii':
                if (data.data && data.data.artwork) {
                    displayArtwork(data.data.artwork);
                }
                break;
                
            case 'cloe':
                if (data.data && data.data.artworks) {
                    displayArtworks(data.data.artworks);
                }
                break;
                
            case 'strategist':
                if (data.data && data.data.analysis) {
                    displayAnalysis(data.data.analysis);
                }
                break;
        }
    }
    
    /**
     * Display notification
     */
    function showNotification(message, type = 'info') {
        // Implementation for notifications
    }
    
    // Make functions available globally
    window.vortexThorius = {
        sendChatMessage: sendChatMessage
    };
    
})(jQuery); 