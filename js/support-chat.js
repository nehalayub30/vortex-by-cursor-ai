(function($) {
    'use strict';

    class VortexSupportChat {
        constructor(container) {
            this.container = container;
            this.sessionId = this.generateSessionId();
            this.messageContainer = container.querySelector('.vortex-chat-messages');
            this.input = container.querySelector('.vortex-chat-input input');
            this.button = container.querySelector('.vortex-chat-input button');
            this.typingIndicator = container.querySelector('.vortex-chat-typing-indicator');
            this.feedbackContainer = container.querySelector('.vortex-chat-feedback');
            this.suggestionsContainer = container.querySelector('.vortex-chat-suggestions');
            this.closeButton = container.querySelector('.vortex-chat-close');
            
            this.bindEvents();
            this.showWelcomeMessage();
        }
        
        generateSessionId() {
            return 'vortex-chat-' + Math.random().toString(36).substr(2, 9);
        }
        
        bindEvents() {
            // Send message on button click
            this.button.addEventListener('click', () => this.sendMessage());

            // Send message on Enter key
            this.input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.sendMessage();
                }
            });

            // Close chat
            this.closeButton.addEventListener('click', () => {
                this.container.style.display = 'none';
            });

            // Handle feedback
            if (this.feedbackContainer) {
                this.feedbackContainer.addEventListener('click', (e) => {
                    if (e.target.classList.contains('feedback-yes') || 
                        e.target.classList.contains('feedback-no')) {
                        const rating = parseInt(e.target.dataset.rating);
                        this.handleFeedback(rating);
                    }
                });
            }

            // Handle suggestions
            if (this.suggestionsContainer) {
                this.suggestionsContainer.addEventListener('click', (e) => {
                    if (e.target.classList.contains('suggestion-chip')) {
                        this.input.value = e.target.textContent;
                        this.sendMessage();
                    }
                });
            }
        }
        
        showWelcomeMessage() {
            const welcomeMessage = {
                type: 'bot',
                content: 'Hello! I\'m your VORTEX AI assistant. How can I help you today?'
            };
            this.addMessage(welcomeMessage);
            this.showSuggestions([
                'How do I use the marketplace?',
                'What is TOLA?',
                'How do I buy artwork?'
            ]);
        }
        
        async sendMessage() {
            const message = this.input.value.trim();
            if (!message) return;

            // Add user message
            this.addMessage({
                type: 'user',
                content: message
            });

            // Clear input
            this.input.value = '';

            // Show typing indicator
            if (this.typingIndicator) {
                this.typingIndicator.classList.add('active');
            }

            try {
                // Send message to server
                const response = await this.sendToServer(message);

                // Hide typing indicator
                if (this.typingIndicator) {
                    this.typingIndicator.classList.remove('active');
                }

                // Add bot response
                this.addMessage({
                    type: 'bot',
                    content: response.message
                });

                // Show feedback if enabled
                if (this.feedbackContainer) {
                    this.feedbackContainer.style.display = 'flex';
                }

                // Show suggestions if available
                if (response.suggestions && response.suggestions.length > 0) {
                    this.showSuggestions(response.suggestions);
                }

            } catch (error) {
                console.error('Error sending message:', error);
                this.showError('Sorry, there was an error processing your message. Please try again.');
            }
        }
        
        async sendToServer(message) {
            const response = await fetch(vortexChat.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'vortex_chat_message',
                    message: message,
                    session_id: this.sessionId,
                    nonce: vortexChat.nonce
                })
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            return await response.json();
        }
        
        async handleFeedback(rating) {
            try {
                await fetch(vortexChat.ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'vortex_chat_feedback',
                        rating: rating,
                        session_id: this.sessionId,
                        nonce: vortexChat.nonce
                    })
                });

                // Hide feedback after submission
                this.feedbackContainer.style.display = 'none';

            } catch (error) {
                console.error('Error sending feedback:', error);
            }
        }
        
        addMessage(message) {
            const messageElement = document.createElement('div');
            messageElement.className = `vortex-message ${message.type}`;
            messageElement.textContent = message.content;
            this.messageContainer.appendChild(messageElement);
            this.scrollToBottom();
        }
        
        showSuggestions(suggestions) {
            if (!this.suggestionsContainer) return;

            this.suggestionsContainer.innerHTML = '';
            suggestions.slice(0, parseInt(this.container.dataset.maxSuggestions) || 3)
                .forEach(suggestion => {
                    const chip = document.createElement('div');
                    chip.className = 'suggestion-chip';
                    chip.textContent = suggestion;
                    this.suggestionsContainer.appendChild(chip);
                });
        }
        
        scrollToBottom() {
            this.messageContainer.scrollTop = this.messageContainer.scrollHeight;
        }
        
        showError(message) {
            const errorElement = document.createElement('div');
            errorElement.className = 'vortex-message bot error';
            errorElement.textContent = message;
            this.messageContainer.appendChild(errorElement);
            this.scrollToBottom();
        }
    }
    
    // Initialize chat when document is ready
    jQuery(document).ready(function($) {
        const chatContainers = document.querySelectorAll('.vortex-support-chat');
        chatContainers.forEach(container => {
            new VortexSupportChat(container);
        });
    });
    
})(jQuery); 