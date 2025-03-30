/**
 * Thorius Accessibility Enhancements
 */
(function($) {
    'use strict';
    
    // Initialize when document is ready
    $(document).ready(function() {
        // Add keyboard navigation support
        initKeyboardNavigation();
        
        // Add ARIA attributes
        enhanceARIA();
        
        // Add high contrast mode
        initHighContrastMode();
    });
    
    // Enable keyboard navigation
    function initKeyboardNavigation() {
        // Tab navigation
        $('.vortex-thorius-tab').attr('tabindex', '0');
        
        // Handle keyboard selection of tabs
        $('.vortex-thorius-tab').on('keydown', function(e) {
            // Space or Enter activates tab
            if (e.key === ' ' || e.key === 'Enter') {
                e.preventDefault();
                $(this).click();
            }
        });
        
        // Send message with Ctrl+Enter
        $('#vortex-thorius-message-input').on('keydown', function(e) {
            if (e.key === 'Enter' && e.ctrlKey) {
                e.preventDefault();
                $('#vortex-thorius-message-form').submit();
            }
        });
        
        // Shortcut alt+T to focus Thorius
        $(document).on('keydown', function(e) {
            if (e.altKey && e.key === 't') {
                e.preventDefault();
                $('#vortex-thorius-message-input').focus();
            }
        });
    }
    
    // Enhance ARIA attributes
    function enhanceARIA() {
        // Main container
        $('.vortex-thorius-container').attr({
            'role': 'application',
            'aria-label': 'Thorius AI Concierge'
        });
        
        // Tabs
        $('.vortex-thorius-tabs').attr({
            'role': 'tablist',
            'aria-label': 'Thorius Features'
        });
        
        $('.vortex-thorius-tab').each(function() {
            const tabId = $(this).data('tab');
            $(this).attr({
                'role': 'tab',
                'aria-selected': $(this).hasClass('active') ? 'true' : 'false',
                'aria-controls': `vortex-thorius-${tabId}-tab`
            });
        });
        
        // Tab content
        $('.vortex-thorius-tab-content').each(function() {
            const tabId = $(this).attr('id').replace('vortex-thorius-', '').replace('-tab', '');
            $(this).attr({
                'role': 'tabpanel',
                'aria-labelledby': `vortex-thorius-tab-${tabId}`
            });
        });
        
        // Messages area
        $('#vortex-thorius-messages').attr({
            'role': 'log',
            'aria-live': 'polite',
            'aria-relevant': 'additions'
        });
        
        // Message input
        $('#vortex-thorius-message-input').attr({
            'aria-label': 'Message to Thorius',
            'placeholder': 'Ask Thorius anything...'
        });
    }
    
    // Initialize high contrast mode
    function initHighContrastMode() {
        const $container = $('.vortex-thorius-container');
        const $highContrastToggle = $('<button>', {
            id: 'vortex-thorius-high-contrast-btn',
            class: 'vortex-thorius-btn vortex-thorius-high-contrast-btn',
            'aria-label': 'Toggle high contrast mode',
            html: '<svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 0 8 1v14zm0 1A8 8 0 1 1 8 0a8 8 0 0 1 0 16z"/></svg>'
        });
        
        // Add button to actions
        $('.vortex-thorius-actions').append($highContrastToggle);
        
        // Toggle high contrast mode
        $highContrastToggle.on('click', function() {
            $container.toggleClass('vortex-thorius-high-contrast');
            
            // Save preference
            if (window.localStorage) {
                localStorage.setItem('thorius_high_contrast', 
                    $container.hasClass('vortex-thorius-high-contrast') ? '1' : '0');
            }
        });
        
        // Load saved preference
        if (window.localStorage) {
            const savedSetting = localStorage.getItem('thorius_high_contrast');
            if (savedSetting === '1') {
                $container.addClass('vortex-thorius-high-contrast');
            }
        }
    }
    
})(jQuery); 