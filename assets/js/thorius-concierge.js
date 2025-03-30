/**
 * Thorius AI Concierge Interface
 * 
 * Handles UI interactions, tab switching, and chat functionality
 */
(function($) {
    'use strict';

    // Global Thorius object
    window.vortexThorius = window.vortexThorius || {
        // Default settings
        userLanguage: 'en',
        voiceOutput: false,
        activeTab: 'chat',
        chatHistory: [],
        isInitialized: false,
        
        // Language mapping to standard codes for speech recognition
        mapLanguageCode: function(code) {
            const languageMappings = {
                'en': 'en-US',
                'es': 'es-ES',
                'fr': 'fr-FR',
                'de': 'de-DE',
                'it': 'it-IT',
                'pt': 'pt-PT',
                'ru': 'ru-RU',
                'ja': 'ja-JP',
                'zh': 'zh-CN',
                'ko': 'ko-KR',
                'ar': 'ar-SA'
            };
            
            return languageMappings[code] || 'en-US';
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        const thorius = window.vortexThorius;
        
        if (thorius.isInitialized) {
            return;
        }
        
        thorius.isInitialized = true;
        
        // Cache DOM elements
        const $container = $('.vortex-thorius-container');
        const $tabs = $('.vortex-thorius-tab');
        const $tabContents = $('.vortex-thorius-tab-content');
        const $messageForm = $('#vortex-thorius-message-form');
        const $messageInput = $('#vortex-thorius-message-input');
        const $messagesContainer = $('#vortex-thorius-messages');
        const $minimizeBtn = $('#vortex-thorius-minimize-btn');
        const $themeToggleBtn = $('#vortex-thorius-theme-toggle-btn');
        const $promptButtons = $('.vortex-thorius-prompt-btn');
        
        // Initialize active tab
        activateTab(thorius.activeTab);
        
        // Tab switching
        $tabs.on('click', function() {
            const tabId = $(this).data('tab');
            activateTab(tabId);
        });
        
        // Function to activate tab
        function activateTab(tabId) {
            thorius.activeTab = tabId;
            
            // Update UI
            $tabs.removeClass('active');
            $tabs.filter(`[data-tab="${tabId}"]`).addClass('active');
            
            $tabContents.removeClass('active');
            $(`#vortex-thorius-${tabId}-tab`).addClass('active');
            
            // Save active tab preference
            if (window.localStorage) {
                localStorage.setItem('thorius_active_tab', tabId);
            }
            
            // Track tab change
            trackAction('tab_change', { tab: tabId });
        }
        
        // Load saved tab preference
        if (window.localStorage) {
            const savedTab = localStorage.getItem('thorius_active_tab');
            if (savedTab && $tabs.filter(`[data-tab="${savedTab}"]`).length) {
                activateTab(savedTab);
            }
        }
        
        // Message form submission
        $messageForm.on('submit', function(e) {
            e.preventDefault();
            
            const messageText = $messageInput.val().trim();
            
            if (!messageText) {
                return;
            }
            
            // Add user message to UI
            appendMessage(messageText, 'user');
            
            // Clear input
            $messageInput.val('');
            
            // Show typing indicator
            showTypingIndicator();
            
            // Send message to backend
            sendMessage(messageText);
            
            // Track message sent
            trackAction('message_sent', { 
                text_length: messageText.length,
                tab: thorius.activeTab
            });
        });
        
        // Function to send message to backend
        function sendMessage(message) {
            $.ajax({
                url: vortex_thorius_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_thorius_chat',
                    message: message,
                    tab: thorius.activeTab,
                    history: JSON.stringify(thorius.chatHistory.slice(-10)), // Last 10 messages
                    nonce: vortex_thorius_params.nonce
                },
                success: function(response) {
                    // Hide typing indicator
                    hideTypingIndicator();
                    
                    if (response.success) {
                        // Add thorius response to UI
                        appendMessage(response.data.message, 'thorius');
                        
                        // Trigger response event for other components
                        $(document).trigger('thorius:response', response.data);
                    } else {
                        // Show error message
                        appendMessage('Sorry, I encountered an error. Please try again later.', 'thorius');
                    }
                },
                error: function() {
                    // Hide typing indicator
                    hideTypingIndicator();
                    
                    // Show error message
                    appendMessage('Sorry, I encountered a connection error. Please check your internet connection and try again.', 'thorius');
                }
            });
        }
        
        // Function to append message to chat
        function appendMessage(text, sender) {
            const messageHtml = `
                <div class="vortex-thorius-message vortex-thorius-message-${sender}">
                    ${text}
                </div>
            `;
            
            $messagesContainer.append(messageHtml);
            
            // Scroll to bottom
            $messagesContainer.scrollTop($messagesContainer[0].scrollHeight);
            
            // Add to history
            thorius.chatHistory.push({
                text: text,
                sender: sender,
                timestamp: new Date().toISOString()
            });
            
            // Limit history size
            if (thorius.chatHistory.length > 100) {
                thorius.chatHistory.shift();
            }
        }
        
        // Functions for typing indicator
        function showTypingIndicator() {
            const typingHtml = `
                <div class="vortex-thorius-typing" id="vortex-thorius-typing">
                    <span>Thorius is thinking</span>
                    <div class="vortex-thorius-typing-dots">
                        <div class="vortex-thorius-typing-dot"></div>
                        <div class="vortex-thorius-typing-dot"></div>
                        <div class="vortex-thorius-typing-dot"></div>
                    </div>
                </div>
            `;
            
            $messagesContainer.append(typingHtml);
            $messagesContainer.scrollTop($messagesContainer[0].scrollHeight);
        }
        
        function hideTypingIndicator() {
            $('#vortex-thorius-typing').remove();
        }
        
        // Minimize button
        $minimizeBtn.on('click', function() {
            $container.toggleClass('vortex-thorius-minimized');
            
            // Update button icon
            const isMinimized = $container.hasClass('vortex-thorius-minimized');
            $(this).html(isMinimized ? 
                '<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M0 4v8h16V4H0zm15 7H1V5h14v6z"/></svg>' : 
                '<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M14 1H2a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zM2 0a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/><path d="M2 4h12v1H2z"/></svg>'
            );
            
            // Save preference
            if (window.localStorage) {
                localStorage.setItem('thorius_minimized', isMinimized ? '1' : '0');
            }
        });
        
        // Theme toggle
        $themeToggleBtn.on('click', function() {
            $container.toggleClass('vortex-thorius-light vortex-thorius-dark');
            
            const isDark = $container.hasClass('vortex-thorius-dark');
            
            // Update button icon
            $(this).html(isDark ? 
                '<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM8 0a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 0zm0 13a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 13zm8-5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5zM3 8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2A.5.5 0 0 1 3 8zm10.657-5.657a.5.5 0 0 1 0 .707l-1.414 1.415a.5.5 0 1 1-.707-.708l1.414-1.414a.5.5 0 0 1 .707 0zm-9.193 9.193a.5.5 0 0 1 0 .707L3.05 13.657a.5.5 0 0 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0zm9.193 2.121a.5.5 0 0 1-.707 0l-1.414-1.414a.5.5 0 0 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .707zM4.464 4.465a.5.5 0 0 1-.707 0L2.343 3.05a.5.5 0 1 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .708z"/></svg>' : 
                '<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M6 .278a.768.768 0 0 1 .08.858 7.208 7.208 0 0 0-.878 3.46c0 4.021 3.278 7.277 7.318 7.277.527 0 1.04-.055 1.533-.16a.787.787 0 0 1 .81.316.733.733 0 0 1-.031.893A8.349 8.349 0 0 1 8.344 16C3.734 16 0 12.286 0 7.71 0 4.266 2.114 1.312 5.124.06A.752.752 0 0 1 6 .278zM4.858 1.311A7.269 7.269 0 0 0 1.025 7.71c0 4.02 3.279 7.276 7.319 7.276a7.316 7.316 0 0 0 5.205-2.162c-.337.042-.68.063-1.029.063-4.61 0-8.343-3.714-8.343-8.29 0-1.167.242-2.278.681-3.286z"/></svg>'
            );
            
            // Save preference
            if (window.localStorage) {
                localStorage.setItem('thorius_theme', isDark ? 'dark' : 'light');
            }
        });
        
        // Load saved theme preference
        if (window.localStorage) {
            const savedTheme = localStorage.getItem('thorius_theme');
            if (savedTheme) {
                $container.removeClass('vortex-thorius-light vortex-thorius-dark')
                    .addClass(`vortex-thorius-${savedTheme}`);
                
                // Update button icon
                $themeToggleBtn.html(savedTheme === 'dark' ? 
                    '<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM8 0a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 0zm0 13a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-1 0v-2A.5.5 0 0 1 8 13zm8-5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5zM3 8a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2A.5.5 0 0 1 3 8zm10.657-5.657a.5.5 0 0 1 0 .707l-1.414 1.415a.5.5 0 1 1-.707-.708l1.414-1.414a.5.5 0 0 1 .707 0zm-9.193 9.193a.5.5 0 0 1 0 .707L3.05 13.657a.5.5 0 0 1-.707-.707l1.414-1.414a.5.5 0 0 1 .707 0zm9.193 2.121a.5.5 0 0 1-.707 0l-1.414-1.414a.5.5 0 0 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .707zM4.464 4.465a.5.5 0 0 1-.707 0L2.343 3.05a.5.5 0 1 1 .707-.707l1.414 1.414a.5.5 0 0 1 0 .708z"/></svg>' : 
                    '<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M6 .278a.768.768 0 0 1 .08.858 7.208 7.208 0 0 0-.878 3.46c0 4.021 3.278 7.277 7.318 7.277.527 0 1.04-.055 1.533-.16a.787.787 0 0 1 .81.316.733.733 0 0 1-.031.893A8.349 8.349 0 0 1 8.344 16C3.734 16 0 12.286 0 7.71 0 4.266 2.114 1.312 5.124.06A.752.752 0 0 1 6 .278zM4.858 1.311A7.269 7.269 0 0 0 1.025 7.71c0 4.02 3.279 7.276 7.319 7.276a7.316 7.316 0 0 0 5.205-2.162c-.337.042-.68.063-1.029.063-4.61 0-8.343-3.714-8.343-8.29 0-1.167.242-2.278.681-3.286z"/></svg>'
                );
            }
        }
        
        // Handle prompt buttons
        $promptButtons.on('click', function() {
            const promptText = $(this).text().trim();
            
            // Fill input with prompt text
            $messageInput.val(promptText);
            
            // Focus and position cursor at end
            $messageInput.focus();
            const inputLength = $messageInput.val().length;
            $messageInput[0].setSelectionRange(inputLength, inputLength);
            
            // Track prompt click
            trackAction('prompt_click', { 
                text: promptText,
                tab: thorius.activeTab
            });
        });
        
        // Send initial welcome message
        if (vortex_thorius_params.welcome_message && thorius.chatHistory.length === 0) {
            setTimeout(function() {
                appendMessage(vortex_thorius_params.welcome_text, 'thorius');
            }, 500);
        }
        
        // Location detection
        if (vortex_thorius_params.enable_location && navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                const locationData = {
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    source: 'browser'
                };
                
                // Send to backend
                $.ajax({
                    url: vortex_thorius_params.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'vortex_thorius_update_location',
                        location: JSON.stringify(locationData),
                        nonce: vortex_thorius_params.nonce
                    }
                });
                
                // Update global object
                thorius.location = locationData;
            });
        }
        
        // Track AI agent interactions
        $('.vortex-thorius-ai-card .vortex-thorius-ai-action').on('click', function() {
            const agentName = $(this).closest('.vortex-thorius-ai-card').find('h5').text().trim();
            
            trackAction('ai_agent_click', {
                agent: agentName,
                tab: thorius.activeTab
            });
        });
        
        // Tab-specific functionality
        $(document).on('click', '.switch-to-chat', function() {
            activateTab('chat');
        });
        
        // Analytics tracking
        function trackAction(action, data) {
            $.ajax({
                url: vortex_thorius_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_thorius_track_analytics',
                    thorius_action: action,
                    data: JSON.stringify(data || {}),
                    nonce: vortex_thorius_params.nonce
                }
            });
        }
    });
    
})(jQuery); 