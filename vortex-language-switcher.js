/**
 * VORTEX Language Switcher
 * 
 * Handles language switching functionality while preserving AI agent learning states
 * 
 * @package VORTEX_AI_Marketplace
 * @subpackage Internationalization
 */

(function($) {
    'use strict';
    
    /**
     * VORTEX Language Switcher class
     */
    var VortexLanguageSwitcher = {
        /**
         * Current locale
         */
        currentLocale: 'en_US',
        
        /**
         * Available languages
         */
        languages: [],
        
        /**
         * Translations cache
         */
        translations: {},
        
        /**
         * AI learning context
         */
        aiLearningContext: {
            aiAgents: ['HURAII', 'CLOE', 'BusinessStrategist'],
            activeAgents: {},
            sessionId: '',
            learningActive: true,
            interactionCount: 0
        },
        
        /**
         * Initialize the language switcher
         */
        init: function() {
            // Set initial locale
            this.currentLocale = $('html').attr('lang') || 'en_US';
            
            // Create session ID for AI learning tracking
            this.aiLearningContext.sessionId = 'lang_' + Math.random().toString(36).substring(2, 15);
            
            // Initialize AI learning tracking
            this.initAITracking();
            
            // Get available languages
            this.getAvailableLanguages();
            
            // Set up event handlers
            this.bindEvents();
            
            // Initialize language switcher UI
            this.initSwitcherUI();
        },
        
        /**
         * Initialize AI tracking for language operations
         */
        initAITracking: function() {
            // Set initial state for AI agents
            for (var i = 0; i < this.aiLearningContext.aiAgents.length; i++) {
                var agent = this.aiLearningContext.aiAgents[i];
                this.aiLearningContext.activeAgents[agent] = {
                    active: true,
                    learningMode: 'active',
                    context: 'language_switching',
                    interactionCount: 0
                };
            }
            
            // Track initialization for AI learning
            this.trackAIInteraction('init', {
                locale: this.currentLocale,
                sessionId: this.aiLearningContext.sessionId,
                timestamp: Date.now(),
                userAgent: navigator.userAgent,
                platform: navigator.platform
            });
        },
        
        /**
         * Track AI interaction for learning
         */
        trackAIInteraction: function(type, data) {
            // Increment counters
            this.aiLearningContext.interactionCount++;
            
            for (var agent in this.aiLearningContext.activeAgents) {
                if (this.aiLearningContext.activeAgents.hasOwnProperty(agent)) {
                    this.aiLearningContext.activeAgents[agent].interactionCount++;
                }
            }
            
            // Add common data
            var trackingData = $.extend({}, data, {
                interactionType: type,
                sessionId: this.aiLearningContext.sessionId,
                totalInteractions: this.aiLearningContext.interactionCount
            });
            
            // Send tracking data to server if learning is active
            if (this.aiLearningContext.learningActive) {
                $.ajax({
                    url: vortex_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'vortex_ai_interaction',
                        nonce: vortex_vars.ai_nonce,
                        interaction_type: 'language_operation',
                        data: trackingData
                    },
                    success: function(response) {
                        // Optional success handling
                    }
                });
            }
        },
        
        /**
         * Get available languages from the server
         */
        getAvailableLanguages: function() {
            var self = this;
            
            $.ajax({
                url: vortex_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'vortex_get_languages',
                    nonce: vortex_vars.language_nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        self.languages = response.data;
                        self.updateLanguageSelector();
                        
                        // Track for AI learning
                        self.trackAIInteraction('languages_loaded', {
                            languageCount: self.languages.length,
                            currentLocale: self.currentLocale
                        });
                    }
                }
            });
        },
        
        /**
         * Set up event handlers
         */
        bindEvents: function() {
            var self = this;
            
            // Language selection change
            $(document).on('click', '.vortex-language-option', function(e) {
                e.preventDefault();
                var locale = $(this).data('locale');
                self.switchLanguage(locale);
            });
            
            // Language dropdown toggle
            $(document).on('click', '.vortex-language-dropdown', function(e) {
                e.preventDefault();
                self.toggleLanguageDropdown();
            });
        },
        
        /**
         * Initialize language switcher UI
         */
        initSwitcherUI: function() {
            // Implementation of initSwitcherUI method
        },
        
        /**
         * Switch language
         */
        switchLanguage: function(locale) {
            // Implementation of switchLanguage method
        },
        
        /**
         * Toggle language dropdown
         */
        toggleLanguageDropdown: function() {
            // Implementation of toggleLanguageDropdown method
        },
        
        /**
         * Update language selector
         */
        updateLanguageSelector: function() {
            // Implementation of updateLanguageSelector method
        }
    };
    
    // Initialize language switcher
    VortexLanguageSwitcher.init();
})(jQuery); 