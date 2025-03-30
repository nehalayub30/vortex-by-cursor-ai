/**
 * Thorius Performance Enhancements
 */
(function($) {
    'use strict';
    
    // Implement lazy loading for tab content
    function initLazyLoading() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const lazyElement = entry.target;
                    if (lazyElement.dataset.src) {
                        lazyElement.src = lazyElement.dataset.src;
                        lazyElement.removeAttribute('data-src');
                    }
                    observer.unobserve(lazyElement);
                }
            });
        });
        
        // Observe all lazy images
        document.querySelectorAll('.vortex-thorius-lazy').forEach(img => {
            observer.observe(img);
        });
    }
    
    // Message debouncing to prevent flooding
    function debounceInput(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Initialize when document is ready
    $(document).ready(function() {
        // Initialize lazy loading
        initLazyLoading();
        
        // Apply debounce to message input typing
        const $messageInput = $('#vortex-thorius-message-input');
        const debouncedInputHandler = debounceInput(function() {
            // Track user typing for analytics
            if (window.vortexThorius && window.vortexThorius.trackAction) {
                window.vortexThorius.trackAction('user_typing', {});
            }
        }, 500);
        
        $messageInput.on('input', debouncedInputHandler);
        
        // Implement response caching
        if (window.localStorage) {
            // Cache common responses
            $(document).on('thorius:response', function(e, data) {
                if (data && data.cacheable && data.query) {
                    const cacheKey = 'thorius_cache_' + btoa(data.query);
                    localStorage.setItem(cacheKey, JSON.stringify({
                        response: data.message,
                        timestamp: Date.now()
                    }));
                }
            });
        }
    });
})(jQuery); 