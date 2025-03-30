/**
 * Adaptive UX JavaScript
 * Enhances the user experience by adapting UI based on preferences and behavior
 */
(function($) {
    'use strict';
    
    // Store user preferences locally for immediate use
    var userPreferences = vortexUX.preferences || {};
    
    /**
     * Initialize adaptive UX features
     */
    function initAdaptiveUX() {
        // Apply stored preferences to current UI
        applyStoredPreferences();
        
        // Set up preference tracking
        setupPreferenceTracking();
        
        // Set up adaptive help system
        setupAdaptiveHelp();
        
        // Set up interface complexity adjustment
        setupComplexityAdjustment();
    }
    
    /**
     * Apply stored user preferences to current UI
     */
    function applyStoredPreferences() {
        // Apply interface complexity
        if (userPreferences.complexity) {
            adjustComplexity(userPreferences.complexity);
        }
        
        // Apply color theme preference
        if (userPreferences.color_theme) {
            applyColorTheme(userPreferences.color_theme);
        }
        
        // Apply text size preference
        if (userPreferences.text_size) {
            applyTextSize(userPreferences.text_size);
        }
    }
    
    /**
     * Set up tracking of user preference changes
     */
    function setupPreferenceTracking() {
        // Track form submission frequency
        $('.vortex-ai-form').on('submit', function() {
            trackPreference('form_usage', 'active');
        });
        
        // Track AI result interactions
        $(document).on('click', '.vortex-result-action', function() {
            trackPreference('result_interaction', 'engaged');
        });
        
        // Track advanced option usage
        $('.vortex-advanced-option').on('change', function() {
            trackPreference('advanced_usage', 'enabled');
        });
    }
    
    /**
     * Track user preference via AJAX
     */
    function trackPreference(preferenceType, preferenceValue) {
        // Don't send duplicate preference
        if (userPreferences[preferenceType] === preferenceValue) {
            return;
        }
        
        // Update local copy
        userPreferences[preferenceType] = preferenceValue;
        
        // Send to server
        $.ajax({
            url: vortexUX.ajax_url,
            type: 'POST',
            data: {
                action: 'vortex_track_ux_preference',
                preference_type: preferenceType,
                preference_value: preferenceValue,
                nonce: vortexUX.nonce
            }
        });
    }
    
    /**
     * Set up adaptive help system based on user behavior
     */
    function setupAdaptiveHelp() {
        // Check for first-time users
        if (!userPreferences.visited_before) {
            showWelcomeTour();
            trackPreference('visited_before', 'true');
        }
        
        // Check for users who haven't completed actions
        if (userPreferences.form_usage !== 'active') {
            setTimeout(function() {
                showHelpPrompt();
            }, 60000); // Show after 1 minute of inactivity
        }
    }
    
    /**
     * Set up interface complexity adjustment
     */
    function setupComplexityAdjustment() {
        // Add complexity toggle
        $('.vortex-ai-container').each(function() {
            if (!$(this).find('.vortex-complexity-toggle').length) {
                $(this).append(
                    '<div class="vortex-complexity-control">' +
                    '<button class="vortex-complexity-toggle" data-complexity="simple">' + 
                    'Simplify Interface</button>' +
                    '</div>'
                );
            }
        });
        
        // Handle complexity toggle
        $(document).on('click', '.vortex-complexity-toggle', function() {
            var newComplexity = $(this).data('complexity') === 'simple' ? 'advanced' : 'simple';
            $(this).data('complexity', newComplexity);
            $(this).text(newComplexity === 'simple' ? 'Simplify Interface' : 'Show Advanced Options');
            
            adjustComplexity(newComplexity === 'simple' ? 'advanced' : 'simple');
            trackPreference('complexity', newComplexity === 'simple' ? 'advanced' : 'simple');
        });
    }
    
    /**
     * Adjust interface complexity
     */
    function adjustComplexity(complexity) {
        if (complexity === 'simple') {
            $('.vortex-advanced-option').closest('.vortex-form-field').hide();
            $('.vortex-technical-param').closest('.vortex-form-field').hide();
        } else {
            $('.vortex-advanced-option').closest('.vortex-form-field').show();
            
            // Only show technical params if user is technical
            if (userPreferences.advanced_usage === 'enabled') {
                $('.vortex-technical-param').closest('.vortex-form-field').show();
            }
        }
    }
    
    /**
     * Apply color theme preference
     */
    function applyColorTheme(theme) {
        $('body').removeClass('vortex-theme-light vortex-theme-dark vortex-theme-blue');
        $('body').addClass('vortex-theme-' + theme);
    }
    
    /**
     * Apply text size preference
     */
    function applyTextSize(size) {
        $('.vortex-ai-container').removeClass('vortex-text-small vortex-text-medium vortex-text-large');
        $('.vortex-ai-container').addClass('vortex-text-' + size);
    }
    
    /**
     * Show welcome tour for first-time users
     */
    function showWelcomeTour() {
        // Implementation would use a tour library like IntroJS
        if (window.introJs && $('.vortex-ai-container').length) {
            var intro = introJs();
            intro.setOptions({
                steps: [
                    {
                        element: '.vortex-ai-container',
                        intro: 'Welcome to Vortex AI Marketplace! Let\'s take a quick tour.'
                    },
                    {
                        element: '.vortex-ai-form',
                        intro: 'This is where you can interact with our AI agents.'
                    },
                    {
                        element: '.vortex-wallet-status',
                        intro: 'Your wallet status and TOLA tokens are shown here.'
                    },
                    {
                        element: '.vortex-complexity-toggle',
                        intro: 'You can simplify or expand the interface based on your preferences.'
                    }
                ]
            });
            intro.start();
        }
    }
    
    /**
     * Show help prompt for inactive users
     */
    function showHelpPrompt() {
        if (!$('.vortex-ai-form:visible').length) {
            return; // Don't show if no form is visible
        }
        
        if (!$('.vortex-help-prompt').length) {
            $('.vortex-ai-container').append(
                '<div class="vortex-help-prompt">' +
                '<p>Need help getting started with our AI tools?</p>' +
                '<button class="vortex-help-button">Show me how</button>' +
                '<button class="vortex-help-dismiss">No thanks</button>' +
                '</div>'
            );
            
            // Fade in the prompt
            $('.vortex-help-prompt').hide().fadeIn();
            
            // Handle help button
            $('.vortex-help-button').on('click', function() {
                $('.vortex-help-prompt').remove();
                showWelcomeTour();
            });
            
            // Handle dismiss button
            $('.vortex-help-dismiss').on('click', function() {
                $('.vortex-help-prompt').remove();
                trackPreference('help_prompt', 'dismissed');
            });
        }
    }
    
    // Initialize when document is ready
    $(document).ready(initAdaptiveUX);
    
})(jQuery); 