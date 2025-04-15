/**
 * Marketplace Frontend JavaScript
 * Handles all frontend interactions for the marketplace components
 * with a focus on accessibility, progressive enhancement and modern interactions
 */

(function($) {
    'use strict';

    // Feature detection for modern browser capabilities
    const supportsLocalStorage = 'localStorage' in window;
    const supportsWebAnimations = 'animate' in document.createElement('div');
    const supportsIntersectionObserver = 'IntersectionObserver' in window;
    
    // Main Marketplace object
    const VortexMarketplace = {
        settings: {
            isAnimationsReduced: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
            lazyLoadThreshold: 0.1,
            notificationDuration: 5000,
            ajaxDebounceTime: 300
        },

        elements: {
            $walletConnection: null,
            $notificationContainer: null,
            $loadingIndicators: null
        },
        
        state: {
            isWalletConnected: false,
            walletAddress: '',
            walletBalance: 0,
            pendingRequests: {},
            debouncedFunctions: {}
        },

        init: function() {
            // Cache commonly used elements
            this.cacheElements();
            
            // Initialize UI components
            this.initAccessibilityFeatures();
            this.initCarousels();
            this.initWalletConnection();
            this.initArtworkFilters();
            this.initMetricsCharts();
            this.initForms();
            this.initModals();
            this.initLazyLoading();
            
            // Set up event handlers
            this.setupEventListeners();
            
            // Log initialization for debugging
            if (vortex_marketplace_vars.debug_mode) {
                console.log('VortexMarketplace initialized', this);
            }
            
            // Trigger ready event
            $(document).trigger('vortex_marketplace_ready');
        },
        
        /**
         * Cache commonly used DOM elements
         */
        cacheElements: function() {
            this.elements.$walletConnection = $('.marketplace-wallet-connection');
            this.elements.$notificationContainer = $('.marketplace-notifications');
            this.elements.$loadingIndicators = $('.vortex-loading');
            
            // Create notification container if it doesn't exist
            if (!this.elements.$notificationContainer.length) {
                $('body').append('<div class="marketplace-notifications" aria-live="polite"></div>');
                this.elements.$notificationContainer = $('.marketplace-notifications');
            }
        },
        
        /**
         * Initialize accessibility features
         */
        initAccessibilityFeatures: function() {
            // Add skip links for keyboard navigation
            if ($('.marketplace-frontend-wrapper').length && !$('.skip-to-content').length) {
                $('.marketplace-frontend-wrapper').prepend(
                    '<a href="#marketplace-main-content" class="skip-to-content">Skip to content</a>'
                );
                
                // Add main content ID if not present
                if (!$('#marketplace-main-content').length) {
                    $('.marketplace-frontend-content').first().attr('id', 'marketplace-main-content');
                }
            }
            
            // Add proper ARIA attributes to interactive elements
            $('.marketplace-tabs').attr('role', 'tablist');
            $('.marketplace-tab').attr('role', 'tab');
            $('.marketplace-tab-content').attr('role', 'tabpanel');
            
            // Make sure buttons have type attribute
            $('button').each(function() {
                if (!$(this).attr('type')) {
                    $(this).attr('type', 'button');
                }
            });
            
            // Add screen reader descriptions for icons
            $('.marketplace-icon').each(function() {
                if (!$(this).attr('aria-hidden')) {
                    $(this).attr('aria-hidden', 'true');
                }
                
                // If no description, add span with sr-only class
                if ($(this).attr('aria-label') === undefined && !$(this).next('.sr-only').length) {
                    const iconType = $(this).data('icon') || 'icon';
                    $(this).after(`<span class="sr-only">${iconType}</span>`);
                }
            });
        },

        /**
         * Initialize carousel components with accessibility
         */
        initCarousels: function() {
            $('.marketplace-carousel-display').each(function() {
                const $carousel = $(this);
                const $items = $carousel.find('.marketplace-carousel-items');
                const $prevBtn = $carousel.find('.marketplace-carousel-prev');
                const $nextBtn = $carousel.find('.marketplace-carousel-next');
                const $slides = $carousel.find('.marketplace-carousel-item');
                
                // Add ARIA attributes
                $carousel.attr('role', 'region');
                $carousel.attr('aria-label', $carousel.data('label') || 'Carousel');
                $slides.attr('role', 'group');
                $slides.attr('aria-roledescription', 'slide');
                
                // Add unique IDs and aria-labels to slides if not present
                $slides.each(function(index) {
                    const $slide = $(this);
                    if (!$slide.attr('id')) {
                        const slideId = 'carousel-slide-' + Math.random().toString(36).substring(2, 9);
                        $slide.attr('id', slideId);
                    }
                    
                    // Set aria-label from slide content or index
                    if (!$slide.attr('aria-label')) {
                        const slideTitle = $slide.find('h3, h4, .title').first().text();
                        $slide.attr('aria-label', slideTitle || `Slide ${index + 1}`);
                    }
                });
                
                // Add ARIA to navigation buttons
                if ($prevBtn.length && $nextBtn.length) {
                    $prevBtn.attr('aria-label', 'Previous slide');
                    $nextBtn.attr('aria-label', 'Next slide');
                    
                    // Enable keyboard navigation
                    $prevBtn.attr('tabindex', '0');
                    $nextBtn.attr('tabindex', '0');
                    
                    // Handle click events
                    $prevBtn.on('click keydown', function(e) {
                        if (e.type === 'click' || (e.type === 'keydown' && (e.key === 'Enter' || e.key === ' '))) {
                            e.preventDefault();
                            navigateCarousel('prev');
                        }
                    });
                    
                    $nextBtn.on('click keydown', function(e) {
                        if (e.type === 'click' || (e.type === 'keydown' && (e.key === 'Enter' || e.key === ' '))) {
                            e.preventDefault();
                            navigateCarousel('next');
                        }
                    });
                }
                
                // Navigation function
                function navigateCarousel(direction) {
                    const scrollAmount = $items.width() * 0.8;
                    const currentScroll = $items.scrollLeft();
                    const isAnimationsReduced = VortexMarketplace.settings.isAnimationsReduced;
                    
                    if (direction === 'prev') {
                        if (isAnimationsReduced) {
                            $items.scrollLeft(currentScroll - scrollAmount);
                        } else {
                            $items.animate({ scrollLeft: currentScroll - scrollAmount }, 300);
                        }
                    } else {
                        if (isAnimationsReduced) {
                            $items.scrollLeft(currentScroll + scrollAmount);
                        } else {
                            $items.animate({ scrollLeft: currentScroll + scrollAmount }, 300);
                        }
                    }
                    
                    // Update focus for keyboard users
                    setTimeout(() => {
                        const visibleSlides = getVisibleSlides();
                        if (visibleSlides.length) {
                            visibleSlides[0].focus();
                        }
                    }, 350);
                }
                
                // Helper to get currently visible slides
                function getVisibleSlides() {
                    const visibleSlides = [];
                    const containerLeft = $items.offset().left;
                    const containerWidth = $items.width();
                    
                    $slides.each(function() {
                        const $slide = $(this);
                        const slideLeft = $slide.offset().left - containerLeft;
                        const slideRight = slideLeft + $slide.width();
                        
                        // Check if slide is more than 50% visible
                        if (slideLeft < containerWidth && slideRight > 0) {
                            visibleSlides.push(this);
                        }
                    });
                    
                    return visibleSlides;
                }
                
                // Add swipe support for touch devices
                let touchStartX = 0;
                let touchEndX = 0;
                
                $items.on('touchstart', function(e) {
                    touchStartX = e.originalEvent.touches[0].clientX;
                });
                
                $items.on('touchend', function(e) {
                    touchEndX = e.originalEvent.changedTouches[0].clientX;
                    handleSwipe();
                });
                
                function handleSwipe() {
                    const swipeThreshold = 50;
                    if (touchStartX - touchEndX > swipeThreshold) {
                        // Swipe left, show next
                        navigateCarousel('next');
                    } else if (touchEndX - touchStartX > swipeThreshold) {
                        // Swipe right, show previous
                        navigateCarousel('prev');
                    }
                }
            });
        },

        /**
         * Setup the Solana wallet connection functionality
         */
        initWalletConnection: function() {
            if (!this.elements.$walletConnection.length) return;
            
            const $connectBtn = this.elements.$walletConnection.find('.marketplace-connect-wallet-button');
            const $disconnectBtn = this.elements.$walletConnection.find('.marketplace-disconnect-wallet-button');
            const $walletStatus = this.elements.$walletConnection.find('.marketplace-wallet-status-indicator');
            const $walletAddress = this.elements.$walletConnection.find('.marketplace-wallet-address');
            const $walletDropdown = this.elements.$walletConnection.find('.marketplace-wallet-dropdown');
            const $copyAddressBtn = this.elements.$walletConnection.find('.marketplace-copy-address-button');
            
            // Add appropriate ARIA attributes
            $connectBtn.attr('aria-label', 'Connect wallet');
            $disconnectBtn.attr('aria-label', 'Disconnect wallet');
            $walletStatus.attr('aria-live', 'polite');
            $copyAddressBtn.attr('aria-label', 'Copy wallet address to clipboard');
            
            // Check if wallet is already connected
            this.checkWalletConnection();
            
            // Connect wallet button
            $connectBtn.on('click keydown', (e) => {
                if (e.type === 'click' || (e.type === 'keydown' && (e.key === 'Enter' || e.key === ' '))) {
                    e.preventDefault();
                    this.connectWallet();
                }
            });
            
            // Disconnect wallet button
            $disconnectBtn.on('click keydown', (e) => {
                if (e.type === 'click' || (e.type === 'keydown' && (e.key === 'Enter' || e.key === ' '))) {
                    e.preventDefault();
                    this.disconnectWallet();
                }
            });
            
            // Copy wallet address button with improved feedback
            $copyAddressBtn.on('click keydown', (e) => {
                if (e.type === 'click' || (e.type === 'keydown' && (e.key === 'Enter' || e.key === ' '))) {
                    e.preventDefault();
                    const address = $walletAddress.text();
                    
                    // Use modern clipboard API if available
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(address)
                            .then(() => {
                                this.provideFeedback($copyAddressBtn, 'Copied!');
                                
                                // Announce to screen readers
                                this.announceToScreenReader('Wallet address copied to clipboard');
                            })
                            .catch(err => {
                                console.error('Failed to copy: ', err);
                                // Fallback to old method
                                this.copyToClipboard(address);
                                this.provideFeedback($copyAddressBtn, 'Copied!');
                            });
                    } else {
                        // Fallback for browsers that don't support clipboard API
                        this.copyToClipboard(address);
                        this.provideFeedback($copyAddressBtn, 'Copied!');
                    }
                }
            });
            
            // Toggle dropdown on wallet address click/keypress
            $walletAddress.on('click keydown', (e) => {
                if (e.type === 'click' || (e.type === 'keydown' && (e.key === 'Enter' || e.key === ' '))) {
                    e.preventDefault();
                    $walletDropdown.toggleClass('active');
                    
                    // Set aria-expanded
                    const isExpanded = $walletDropdown.hasClass('active');
                    $walletAddress.attr('aria-expanded', isExpanded);
                    
                    // Focus first item in dropdown if expanded
                    if (isExpanded) {
                        $walletDropdown.find('a, button').first().focus();
                    }
                }
            });
            
            // Close dropdown when clicking outside
            $(document).on('click', (e) => {
                if ($walletDropdown.hasClass('active') && 
                    !$walletDropdown[0].contains(e.target) && 
                    !$walletAddress[0].contains(e.target)) {
                    $walletDropdown.removeClass('active');
                    $walletAddress.attr('aria-expanded', false);
                }
            });
            
            // Handle dropdown keyboard navigation
            $walletDropdown.on('keydown', 'a, button', function(e) {
                const $items = $walletDropdown.find('a, button');
                const $current = $(this);
                const index = $items.index($current);
                
                switch (e.key) {
                    case 'Escape':
                        $walletDropdown.removeClass('active');
                        $walletAddress.attr('aria-expanded', false);
                        $walletAddress.focus();
                        break;
                    case 'ArrowDown':
                        e.preventDefault();
                        if (index < $items.length - 1) {
                            $items.eq(index + 1).focus();
                        }
                        break;
                    case 'ArrowUp':
                        e.preventDefault();
                        if (index > 0) {
                            $items.eq(index - 1).focus();
                        } else {
                            $walletAddress.focus();
                        }
                        break;
                    case 'Tab':
                        if ((e.shiftKey && index === 0) || 
                            (!e.shiftKey && index === $items.length - 1)) {
                            $walletDropdown.removeClass('active');
                            $walletAddress.attr('aria-expanded', false);
                        }
                        break;
                }
            });
        },

        /**
         * Connect to Solana wallet (Phantom, Solflare, etc)
         * with improved error handling and accessibility
         */
        connectWallet: async function() {
            // Show loading state
            this.showLoadingState('wallet-connect');
            
            // Check if solana object exists in window
            if (!window.solana) {
                this.hideLoadingState('wallet-connect');
                this.showNotification({
                    message: 'Wallet extension not found. Please install a Solana wallet extension like Phantom.',
                    type: 'error',
                    icon: true,
                    autoClose: false
                });
                return;
            }
            
            try {
                // Connect to wallet
                const response = await window.solana.connect();
                const walletAddress = response.publicKey.toString();
                
                // Store wallet info securely if supported
                this.saveWalletState(true, walletAddress);
                
                // Update UI
                this.updateWalletUI(true, walletAddress);
                
                // Fetch wallet balance
                this.fetchWalletBalance(walletAddress);
                
                // Notify server about wallet connection (debounced)
                this.debouncedServerNotify('wallet_connected', {
                    wallet_address: walletAddress
                });
                
                // Display success notification
                this.showNotification({
                    message: 'Wallet connected successfully!',
                    type: 'success',
                });
                
                // Hide loading state
                this.hideLoadingState('wallet-connect');
                
            } catch (error) {
                console.error('Wallet connection error:', error);
                this.hideLoadingState('wallet-connect');
                this.showNotification({
                    message: 'Failed to connect wallet: ' + (error.message || 'Unknown error'),
                    type: 'error',
                });
            }
        },

        /**
         * Disconnect wallet with improved UX
         */
        disconnectWallet: async function() {
            // Show loading state
            this.showLoadingState('wallet-disconnect');
            
            try {
                // Clear stored wallet info
                this.saveWalletState(false, '');
                
                // Update UI
                this.updateWalletUI(false, '');
                
                // Disconnect from wallet if possible
                if (window.solana && window.solana.disconnect) {
                    await window.solana.disconnect();
                }
                
                // Notify server about wallet disconnection
                this.debouncedServerNotify('wallet_disconnected', {});
                
                // Show success message
                this.showNotification({
                    message: 'Wallet disconnected successfully',
                    type: 'success'
                });
            } catch (error) {
                console.error('Wallet disconnection error:', error);
                this.showNotification({
                    message: 'Error disconnecting wallet: ' + (error.message || 'Unknown error'),
                    type: 'warning'
                });
            } finally {
                // Hide loading state
                this.hideLoadingState('wallet-disconnect');
            }
        },

        /**
         * Check if wallet is already connected with better security
         */
        checkWalletConnection: function() {
            // Check locally stored data
            let isConnected = false;
            let walletAddress = '';
            
            if (supportsLocalStorage) {
                isConnected = localStorage.getItem('vortex_wallet_connected') === 'true';
                walletAddress = localStorage.getItem('vortex_wallet_address') || '';
            } else {
                // Fallback to session storage or cookie as needed
                const tempData = this.getData('wallet_connection') || {};
                isConnected = tempData.connected || false;
                walletAddress = tempData.address || '';
            }
            
            // Only use stored address if actually connected
            if (isConnected && walletAddress) {
                // Verify with wallet extension if available
                if (window.solana && window.solana.isConnected) {
                    // Check if the wallet extension reports as connected
                    if (!window.solana.isConnected) {
                        // Extension says we're not connected, clear state
                        this.saveWalletState(false, '');
                        this.updateWalletUI(false, '');
                        return;
                    }
                }
                
                // Update UI and continue
                this.updateWalletUI(true, walletAddress);
                this.fetchWalletBalance(walletAddress);
            } else {
                this.updateWalletUI(false, '');
            }
        },

        /**
         * Save wallet state securely
         */
        saveWalletState: function(isConnected, walletAddress) {
            // Update internal state
            this.state.isWalletConnected = isConnected;
            this.state.walletAddress = walletAddress;
            
            // Save to storage if available
            if (supportsLocalStorage) {
                if (isConnected) {
                    localStorage.setItem('vortex_wallet_connected', 'true');
                    localStorage.setItem('vortex_wallet_address', walletAddress);
                } else {
                    localStorage.removeItem('vortex_wallet_connected');
                    localStorage.removeItem('vortex_wallet_address');
                }
            } else {
                // Fallback storage
                this.setData('wallet_connection', {
                    connected: isConnected,
                    address: isConnected ? walletAddress : ''
                });
            }
        },

        /**
         * Update wallet UI based on connection status with improved accessibility
         */
        updateWalletUI: function(isConnected, walletAddress) {
            const $connectBtn = this.elements.$walletConnection.find('.marketplace-connect-wallet-button');
            const $disconnectBtn = this.elements.$walletConnection.find('.marketplace-disconnect-wallet-button');
            const $walletStatus = this.elements.$walletConnection.find('.marketplace-wallet-status-indicator');
            const $walletStatusText = this.elements.$walletConnection.find('.marketplace-wallet-status-text');
            const $walletDropdown = this.elements.$walletConnection.find('.marketplace-wallet-dropdown');
            const $walletAddress = this.elements.$walletConnection.find('.marketplace-wallet-address');
            
            if (isConnected) {
                // Visual updates
                $connectBtn.hide().attr('aria-hidden', 'true');
                $disconnectBtn.show().removeAttr('aria-hidden');
                $walletStatus.addClass('connected').attr('aria-label', 'Wallet connected');
                $walletStatusText.text('Connected');
                
                // Make address interactive
                $walletAddress
                    .text(this.formatWalletAddress(walletAddress))
                    .attr('role', 'button')
                    .attr('tabindex', '0')
                    .attr('aria-haspopup', 'true')
                    .attr('aria-expanded', 'false')
                    .show();
                
                $walletDropdown.removeClass('active');
                
                // Announce to screen readers
                this.announceToScreenReader('Wallet connected successfully');
                
                // Dispatch custom event
                $(document).trigger('vortex_wallet_connected', [walletAddress]);
                
                // Enable wallet-dependent elements
                $('.requires-wallet').removeAttr('disabled aria-disabled').removeClass('disabled');
                
            } else {
                // Visual updates
                $connectBtn.show().removeAttr('aria-hidden');
                $disconnectBtn.hide().attr('aria-hidden', 'true');
                $walletStatus.removeClass('connected').attr('aria-label', 'Wallet not connected');
                $walletStatusText.text('Not Connected');
                $walletDropdown.removeClass('active').hide();
                $walletAddress.text('').removeAttr('role tabindex aria-haspopup aria-expanded').hide();
                
                // Announce to screen readers if this was a disconnect action (not initial load)
                if (this.state.isWalletConnected) {
                    this.announceToScreenReader('Wallet disconnected');
                }
                
                // Dispatch custom event
                $(document).trigger('vortex_wallet_disconnected');
                
                // Disable wallet-dependent elements
                $('.requires-wallet').attr('disabled', 'disabled').attr('aria-disabled', 'true').addClass('disabled');
            }
            
            // Update state
            this.state.isWalletConnected = isConnected;
            this.state.walletAddress = isConnected ? walletAddress : '';
        },

        /**
         * Format wallet address for display (truncate middle)
         */
        formatWalletAddress: function(address) {
            if (!address || typeof address !== 'string') return '';
            
            if (address.length > 12) {
                return address.substr(0, 6) + '...' + address.substr(-4);
            }
            return address;
        },

        /**
         * Fetch wallet balance from API with improved error handling
         */
        fetchWalletBalance: function(walletAddress) {
            // Show loading indicator
            const $balanceElement = $('.marketplace-wallet-balance-amount');
            $balanceElement.addClass('loading').attr('aria-busy', 'true');
            
            // Cancel any pending request
            if (this.state.pendingRequests.balanceFetch) {
                this.state.pendingRequests.balanceFetch.abort();
            }
            
            // Make AJAX request
            this.state.pendingRequests.balanceFetch = $.ajax({
                url: vortex_marketplace_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_wallet_balance',
                    wallet_address: walletAddress,
                    nonce: vortex_marketplace_vars.nonce
                },
                success: (response) => {
                    if (response.success) {
                        // Update display
                        $balanceElement.text(response.data.balance);
                        
                        // Update state
                        this.state.walletBalance = parseFloat(response.data.balance) || 0;
                        
                        // Cache for session
                        if (supportsLocalStorage) {
                            sessionStorage.setItem('vortex_wallet_balance', this.state.walletBalance);
                        }
                        
                        // Announce to screen readers if significant change
                        const previousBalance = parseFloat($balanceElement.data('previous-balance') || 0);
                        if (Math.abs(this.state.walletBalance - previousBalance) > 0.01) {
                            this.announceToScreenReader(`Wallet balance updated: ${response.data.balance}`);
                            $balanceElement.data('previous-balance', this.state.walletBalance);
                        }
                    } else {
                        console.warn('Balance fetch failed:', response.data?.message || 'Unknown error');
                        $balanceElement.text('Error');
                    }
                },
                error: (xhr, status, error) => {
                    // Only log error if not aborted
                    if (status !== 'abort') {
                        console.error('Failed to fetch wallet balance:', error);
                        $balanceElement.text('Error');
                    }
                },
                complete: () => {
                    // Remove loading state
                    $balanceElement.removeClass('loading').removeAttr('aria-busy');
                    delete this.state.pendingRequests.balanceFetch;
                }
            });
        },

        /**
         * Copy text to clipboard with fallbacks
         */
        copyToClipboard: function(text) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text);
                return true;
            }
            
            // Fallback for older browsers
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'absolute';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();
            let success = false;
            
            try {
                success = document.execCommand('copy');
            } catch (err) {
                console.error('Failed to copy text:', err);
            }
            
            document.body.removeChild(textarea);
            return success;
        },
        
        /**
         * Provide feedback to user for an action
         */
        provideFeedback: function($element, message, duration = 2000) {
            if (!$element || !$element.length) return;
            
            const originalText = $element.text();
            const originalWidth = $element.width();
            
            // Preserve width to prevent layout shift
            $element.css('min-width', originalWidth + 'px');
            
            // Change text
            $element.text(message);
            
            // Reset after duration
            setTimeout(() => {
                $element.text(originalText);
                setTimeout(() => {
                    $element.css('min-width', '');
                }, 300);
            }, duration);
        },
        
        /**
         * Announce message to screen readers
         */
        announceToScreenReader: function(message) {
            // Check if we have a live region for announcements
            let $announcer = $('#vortex-screen-reader-announcer');
            
            if (!$announcer.length) {
                // Create announcer element
                $('body').append('<div id="vortex-screen-reader-announcer" class="sr-only" aria-live="polite" aria-atomic="true"></div>');
                $announcer = $('#vortex-screen-reader-announcer');
            }
            
            // Set text to announce
            $announcer.text(message);
            
            // Clear after a short delay
            setTimeout(() => {
                $announcer.text('');
            }, 3000);
        },
        
        /**
         * Initialize forms with improved validation and accessibility
         */
        initForms: function() {
            const $forms = $('.marketplace-form');
            
            $forms.each((i, form) => {
                const $form = $(form);
                const formId = $form.attr('id') || 'marketplace-form-' + i;
                
                // Make sure the form has an ID
                if (!$form.attr('id')) {
                    $form.attr('id', formId);
                }
                
                // Add form validation
                $form.on('submit', (e) => {
                    if (!this.validateForm($form)) {
                        e.preventDefault();
                        return false;
                    }
                    
                    // If form has data-ajax="true", handle via AJAX
                    if ($form.data('ajax') === true) {
                        e.preventDefault();
                        this.submitFormAjax($form);
                        return false;
                    }
                });
                
                // Live validation on blur
                $form.find('input, select, textarea').on('blur', function() {
                    VortexMarketplace.validateField($(this));
                });
                
                // Handle password reveal buttons
                $form.find('.password-reveal-btn').on('click keydown', function(e) {
                    if (e.type === 'click' || (e.type === 'keydown' && (e.key === 'Enter' || e.key === ' '))) {
                        e.preventDefault();
                        const $btn = $(this);
                        const $input = $btn.siblings('input[type="password"], input[type="text"]');
                        
                        if ($input.attr('type') === 'password') {
                            $input.attr('type', 'text');
                            $btn.attr('aria-label', 'Hide password')
                                .text('Hide');
                        } else {
                            $input.attr('type', 'password');
                            $btn.attr('aria-label', 'Show password')
                                .text('Show');
                        }
                    }
                });
            });
        },
        
        /**
         * Validate an entire form
         */
        validateForm: function($form) {
            if (!$form || !$form.length) return false;
            
            let isValid = true;
            const $fields = $form.find('input, select, textarea').not('[type="submit"], [type="button"], [type="reset"]');
            
            // Validate each field
            $fields.each((i, field) => {
                if (!this.validateField($(field))) {
                    isValid = false;
                }
            });
            
            // If form is invalid, focus the first invalid field
            if (!isValid) {
                const $firstInvalid = $form.find('.has-error').first();
                if ($firstInvalid.length) {
                    $firstInvalid.focus();
                    
                    // Announce error to screen readers
                    const errorCount = $form.find('.has-error').length;
                    this.announceToScreenReader(`Form has ${errorCount} error${errorCount !== 1 ? 's' : ''}. Please correct and try again.`);
                }
            }
            
            return isValid;
        },
        
        /**
         * Validate a single form field
         */
        validateField: function($field) {
            if (!$field || !$field.length) return false;
            
            // Skip validation for non-required fields if empty
            if (!$field.prop('required') && !$field.val()) {
                this.clearFieldError($field);
                return true;
            }
            
            let isValid = true;
            let errorMessage = '';
            
            // HTML5 validation
            if (!$field[0].checkValidity()) {
                isValid = false;
                errorMessage = $field[0].validationMessage;
            }
            
            // Custom validation based on data attributes
            if (isValid) {
                const value = $field.val();
                const minLength = $field.data('min-length');
                const maxLength = $field.data('max-length');
                const pattern = $field.data('pattern');
                const matchField = $field.data('match-field');
                
                // Minimum length
                if (typeof minLength === 'number' && value.length < minLength) {
                    isValid = false;
                    errorMessage = `Must be at least ${minLength} characters`;
                }
                
                // Maximum length
                if (typeof maxLength === 'number' && value.length > maxLength) {
                    isValid = false;
                    errorMessage = `Must be no more than ${maxLength} characters`;
                }
                
                // Pattern
                if (pattern && !new RegExp(pattern).test(value)) {
                    isValid = false;
                    errorMessage = $field.data('pattern-message') || 'Invalid format';
                }
                
                // Match another field (like password confirmation)
                if (matchField) {
                    const $matchTarget = $(`#${matchField}, [name="${matchField}"]`);
                    if ($matchTarget.length && value !== $matchTarget.val()) {
                        isValid = false;
                        errorMessage = $field.data('match-message') || 'Fields do not match';
                    }
                }
            }
            
            // Update field status
            if (isValid) {
                this.clearFieldError($field);
            } else {
                this.showFieldError($field, errorMessage);
            }
            
            return isValid;
        },
        
        /**
         * Show error for a field
         */
        showFieldError: function($field, message) {
            // Add error class
            $field.addClass('has-error');
            
            // Get error container
            let $errorContainer = $field.nextAll('.form-error').first();
            
            // Create error container if it doesn't exist
            if (!$errorContainer.length) {
                $field.after(`<span class="form-error" id="${$field.attr('id')}-error" aria-live="polite"></span>`);
                $errorContainer = $field.nextAll('.form-error').first();
            }
            
            // Set error message
            $errorContainer.text(message);
            
            // Set aria attributes
            $field.attr('aria-invalid', 'true');
            $field.attr('aria-describedby', $errorContainer.attr('id'));
        },
        
        /**
         * Clear error for a field
         */
        clearFieldError: function($field) {
            // Remove error class
            $field.removeClass('has-error');
            
            // Hide error message
            $field.nextAll('.form-error').first().text('');
            
            // Update aria attributes
            $field.removeAttr('aria-invalid');
        },

        /**
         * Initialize artwork filters
         */
        initArtworkFilters: function() {
            const $filters = $('.marketplace-artwork-filters');
            if (!$filters.length) return;
            
            const $filterItems = $filters.find('.marketplace-filter-item');
            const $artworks = $('.marketplace-grid-item, .marketplace-list-item');
            
            $filterItems.on('click', function() {
                const $this = $(this);
                const filter = $this.data('filter');
                
                // Toggle active class
                $filterItems.removeClass('active');
                $this.addClass('active');
                
                // Filter items
                if (filter === 'all') {
                    $artworks.show();
                } else {
                    $artworks.hide();
                    $(`.marketplace-artwork[data-category="${filter}"]`).closest('.marketplace-grid-item, .marketplace-list-item').show();
                }
                
                // Trigger custom event
                $(document).trigger('vortex_artwork_filtered', [filter]);
            });
        },

        /**
         * Initialize metrics charts
         */
        initMetricsCharts: function() {
            const $metricsChart = $('.marketplace-metrics-chart');
            if (!$metricsChart.length || typeof Chart === 'undefined') return;
            
            // Load metrics data from the server
            $.ajax({
                url: vortex_marketplace_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_dao_metrics',
                    nonce: vortex_marketplace_vars.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        VortexMarketplace.renderMetricsChart($metricsChart, response.data);
                    }
                },
                error: function() {
                    console.error('Failed to fetch DAO metrics');
                }
            });
        },

        /**
         * Render metrics chart with Chart.js
         */
        renderMetricsChart: function($container, data) {
            const ctx = $container[0].getContext('2d');
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'TOKEN Price (USD)',
                        data: data.price_data,
                        borderColor: '#007cba',
                        backgroundColor: 'rgba(0, 124, 186, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    }
                }
            });
        },

        /**
         * Set up global event listeners
         */
        setupEventListeners: function() {
            // Artwork purchase button
            $(document).on('click', '.marketplace-purchase-button', function(e) {
                e.preventDefault();
                
                const $this = $(this);
                const artworkId = $this.data('artwork-id');
                const artworkTitle = $this.data('artwork-title');
                const artworkPrice = $this.data('artwork-price');
                
                // Check if wallet is connected
                if (localStorage.getItem('vortex_wallet_connected') !== 'true') {
                    VortexMarketplace.showNotification('Please connect your wallet first to purchase artwork.', 'error');
                    return;
                }
                
                // Confirm purchase
                if (confirm(`Are you sure you want to purchase "${artworkTitle}" for ${artworkPrice}?`)) {
                    VortexMarketplace.purchaseArtwork(artworkId, artworkPrice);
                }
            });
            
            // Load more artworks button
            $(document).on('click', '.marketplace-load-more-button', function(e) {
                e.preventDefault();
                
                const $this = $(this);
                const page = parseInt($this.data('page')) || 1;
                const display = $this.data('display') || 'grid';
                
                VortexMarketplace.loadMoreArtworks(page + 1, display, $this);
            });
        },

        /**
         * Purchase artwork
         */
        purchaseArtwork: function(artworkId, price) {
            const walletAddress = localStorage.getItem('vortex_wallet_address');
            
            $.ajax({
                url: vortex_marketplace_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'purchase_artwork',
                    artwork_id: artworkId,
                    wallet_address: walletAddress,
                    price: price,
                    nonce: vortex_marketplace_vars.nonce
                },
                beforeSend: function() {
                    VortexMarketplace.showNotification('Processing purchase...', 'info');
                },
                success: function(response) {
                    if (response.success) {
                        VortexMarketplace.showNotification('Purchase successful! Transaction ID: ' + response.data.transaction_id, 'success');
                        
                        // Update UI after successful purchase
                        $(`.marketplace-artwork[data-artwork-id="${artworkId}"]`).addClass('purchased');
                        
                        // Trigger custom event
                        $(document).trigger('vortex_artwork_purchased', [artworkId, response.data]);
                    } else {
                        VortexMarketplace.showNotification(response.data.message || 'Purchase failed. Please try again.', 'error');
                    }
                },
                error: function() {
                    VortexMarketplace.showNotification('An error occurred while processing your purchase. Please try again.', 'error');
                }
            });
        },

        /**
         * Load more artworks
         */
        loadMoreArtworks: function(page, display, $button) {
            $.ajax({
                url: vortex_marketplace_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'load_more_artworks',
                    page: page,
                    display: display,
                    nonce: vortex_marketplace_vars.nonce
                },
                beforeSend: function() {
                    $button.text('Loading...').prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        // Append new artworks
                        const $container = $button.closest('.marketplace-frontend-wrapper').find(`.marketplace-${display}-display`);
                        $container.append(response.data.html);
                        
                        // Update button
                        $button.data('page', page);
                        $button.text('Load More');
                        
                        // Hide button if no more results
                        if (!response.data.has_more) {
                            $button.hide();
                        }
                        
                        // Re-enable button
                        $button.prop('disabled', false);
                        
                        // Trigger custom event
                        $(document).trigger('vortex_artworks_loaded', [page, display, response.data]);
                    } else {
                        $button.text('No More Results').prop('disabled', true);
                    }
                },
                error: function() {
                    $button.text('Load More').prop('disabled', false);
                    VortexMarketplace.showNotification('Failed to load more artworks. Please try again.', 'error');
                }
            });
        },

        /**
         * Notify server about events
         */
        notifyServer: function(eventType, data) {
            $.ajax({
                url: vortex_marketplace_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'track_marketplace_event',
                    event_type: eventType,
                    event_data: data,
                    nonce: vortex_marketplace_vars.nonce
                },
                success: function(response) {
                    // Nothing to do on success
                },
                error: function() {
                    console.error('Failed to notify server about event:', eventType);
                }
            });
        },

        /**
         * Show notification to user
         */
        showNotification: function(options) {
            // Process options
            const settings = $.extend({
                message: '',
                type: 'info',  // info, success, error, warning
                duration: this.settings.notificationDuration,
                autoClose: true,
                position: 'right',  // right, left, center
                icon: false
            }, options);
            
            // Check if notification container exists, create if not
            if (!this.elements.$notificationContainer.length) {
                $('body').append('<div class="marketplace-notifications" aria-live="polite"></div>');
                this.elements.$notificationContainer = $('.marketplace-notifications');
            }
            
            // Set position class on container
            this.elements.$notificationContainer.attr('data-position', settings.position);
            
            // Generate unique ID for notification
            const notificationId = 'notification-' + Math.random().toString(36).substring(2, 9);
            
            // Create notification element
            const $notification = $('<div>', {
                class: `marketplace-notification notification-${settings.type}`,
                id: notificationId,
                role: 'alert',
                'aria-atomic': 'true'
            });
            
            // Add icon if requested
            if (settings.icon) {
                let iconClass = 'info-circle';
                
                switch (settings.type) {
                    case 'success': iconClass = 'check-circle'; break;
                    case 'error': iconClass = 'exclamation-circle'; break;
                    case 'warning': iconClass = 'exclamation-triangle'; break;
                }
                
                $notification.append(`
                    <div class="notification-icon">
                        <span class="vortex-icon icon-${iconClass}" aria-hidden="true"></span>
                    </div>
                `);
            }
            
            // Add content
            $notification.append(`
                <div class="notification-content">${settings.message}</div>
            `);
            
            // Add close button
            const $closeBtn = $('<button>', {
                class: 'marketplace-notification-close',
                type: 'button',
                html: '&times;',
                'aria-label': 'Close notification'
            });
            
            $closeBtn.on('click', function() {
                VortexMarketplace.closeNotification($notification);
            });
            
            // Allow ESC key to close notification
            $notification.on('keydown', function(e) {
                if (e.key === 'Escape') {
                    VortexMarketplace.closeNotification($notification);
                }
            });
            
            // Add to container and animate
            this.elements.$notificationContainer.append($notification);
            
            // Use Web Animations API if supported, fallback to CSS
            if (supportsWebAnimations) {
                const animation = $notification[0].animate([
                    { opacity: 0, transform: 'translateX(40px)' },
                    { opacity: 1, transform: 'translateX(0)' }
                ], {
                    duration: 300,
                    easing: 'ease-out',
                    fill: 'forwards'
                });
                
                animation.onfinish = function() {
                    $notification.addClass('show');
                };
            } else {
                // CSS fallback
                setTimeout(() => {
                    $notification.addClass('show');
                }, 10);
            }
            
            // Set focus to the notification for screen readers
            setTimeout(() => {
                $notification.attr('tabindex', -1).focus();
            }, 100);
            
            // Auto close if enabled
            if (settings.autoClose && settings.duration > 0) {
                setTimeout(() => {
                    this.closeNotification($notification);
                }, settings.duration);
            }
            
            return notificationId;
        },

        /**
         * Close a notification
         */
        closeNotification: function($notification) {
            if (typeof $notification === 'string') {
                $notification = $('#' + $notification);
            }
            
            if (!$notification.length) return;
            
            // Use Web Animations API if supported
            if (supportsWebAnimations) {
                const animation = $notification[0].animate([
                    { opacity: 1, transform: 'translateX(0)' },
                    { opacity: 0, transform: 'translateX(40px)' }
                ], {
                    duration: 300,
                    easing: 'ease-in',
                    fill: 'forwards'
                });
                
                animation.onfinish = function() {
                    $notification.remove();
                };
            } else {
                // CSS fallback
                $notification.removeClass('show');
                setTimeout(() => {
                    $notification.remove();
                }, 300);
            }
        },

        /**
         * Initialize modal dialogs with improved accessibility
         */
        initModals: function() {
            // Setup close handlers for existing modals
            this.setupModalHandlers();
            
            // Handle modal trigger buttons
            $(document).on('click keydown', '[data-modal-target]', (e) => {
                if (e.type === 'click' || (e.type === 'keydown' && (e.key === 'Enter' || e.key === ' '))) {
                    e.preventDefault();
                    
                    const modalId = $(e.currentTarget).data('modal-target');
                    this.openModal(modalId);
                }
            });
        },
        
        /**
         * Setup handlers for modals
         */
        setupModalHandlers: function() {
            // Close modal when clicking backdrop or close button
            $(document).on('click', '.marketplace-modal-backdrop, .marketplace-modal-close', (e) => {
                // Only handle direct clicks on these elements (not children)
                if (e.target === e.currentTarget) {
                    this.closeAllModals();
                }
            });
            
            // Handle keyboard events in modal
            $(document).on('keydown', '.marketplace-modal', (e) => {
                const $modal = $(e.currentTarget);
                
                // ESC key closes modal
                if (e.key === 'Escape') {
                    this.closeModal($modal);
                }
                
                // Trap focus inside modal using Tab key
                if (e.key === 'Tab') {
                    this.handleModalTabKey(e, $modal);
                }
            });
        },
        
        /**
         * Open a modal by ID
         */
        openModal: function(modalId) {
            const $modal = $('#' + modalId);
            
            if (!$modal.length) {
                console.error('Modal not found:', modalId);
                return;
            }
            
            // Create backdrop if not exists
            let $backdrop = $('.marketplace-modal-backdrop');
            if (!$backdrop.length) {
                $('body').append('<div class="marketplace-modal-backdrop"></div>');
                $backdrop = $('.marketplace-modal-backdrop');
            }
            
            // Set ARIA attributes
            $modal.attr('aria-hidden', 'false').attr('role', 'dialog').attr('aria-modal', 'true');
            
            // Show modal and backdrop
            $backdrop.addClass('active');
            $modal.addClass('active');
            
            // Prevent body scrolling
            $('body').addClass('modal-open');
            
            // Focus first focusable element
            setTimeout(() => {
                const $focusable = this.getFocusableElements($modal);
                if ($focusable.length) {
                    $focusable.first().focus();
                }
            }, 100);
            
            // Trigger custom event
            $(document).trigger('vortex_modal_opened', [modalId, $modal]);
        },
        
        /**
         * Close a modal
         */
        closeModal: function($modal) {
            if (typeof $modal === 'string') {
                $modal = $('#' + $modal);
            }
            
            if (!$modal.length) return;
            
            // Hide modal
            $modal.removeClass('active').attr('aria-hidden', 'true');
            
            // Check if any other modals are open
            const $openModals = $('.marketplace-modal.active');
            
            if (!$openModals.length) {
                // No other modals open, hide backdrop and enable body scrolling
                $('.marketplace-modal-backdrop').removeClass('active');
                $('body').removeClass('modal-open');
            }
            
            // Return focus to trigger if stored
            const triggerId = $modal.data('trigger-element');
            if (triggerId) {
                const $trigger = $('#' + triggerId);
                if ($trigger.length) {
                    $trigger.focus();
                }
            }
            
            // Trigger custom event
            $(document).trigger('vortex_modal_closed', [$modal.attr('id'), $modal]);
        },
        
        /**
         * Close all open modals
         */
        closeAllModals: function() {
            $('.marketplace-modal.active').each((i, modal) => {
                this.closeModal($(modal));
            });
        },
        
        /**
         * Handle tab key in modal to trap focus
         */
        handleModalTabKey: function(e, $modal) {
            const $focusable = this.getFocusableElements($modal);
            
            if (!$focusable.length) return;
            
            const $firstFocusable = $focusable.first();
            const $lastFocusable = $focusable.last();
            
            // If shift + tab on first element, move to last element
            if (e.shiftKey && document.activeElement === $firstFocusable[0]) {
                e.preventDefault();
                $lastFocusable.focus();
            } 
            // If tab on last element, move to first element
            else if (!e.shiftKey && document.activeElement === $lastFocusable[0]) {
                e.preventDefault();
                $firstFocusable.focus();
            }
        },
        
        /**
         * Get focusable elements in a container
         */
        getFocusableElements: function($container) {
            return $container.find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        },
        
        /**
         * Initialize lazy loading functionality
         */
        initLazyLoading: function() {
            // If browser supports IntersectionObserver, use it
            if (supportsIntersectionObserver) {
                const config = {
                    rootMargin: '50px 0px',
                    threshold: this.settings.lazyLoadThreshold
                };
                
                const observer = new IntersectionObserver((entries, self) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            this.loadLazyItem(entry.target);
                            self.unobserve(entry.target);
                        }
                    });
                }, config);
                
                // Observe all lazy load elements
                $('.vortex-lazy-load').each((i, el) => {
                    observer.observe(el);
                });
            } else {
                // Fallback for browsers that don't support IntersectionObserver
                this.loadAllLazyItems();
                
                // Simple scroll-based loading for legacy browsers
                $(window).on('scroll.vortexLazyLoad', this.debounce(() => {
                    this.checkLazyItemsInViewport();
                }, 200));
            }
        },
        
        /**
         * Load a lazy item
         */
        loadLazyItem: function(item) {
            const $item = $(item);
            
            // Handle different lazy loading element types
            if ($item.is('img')) {
                const src = $item.data('src');
                const srcset = $item.data('srcset');
                
                if (src) {
                    $item.attr('src', src);
                }
                
                if (srcset) {
                    $item.attr('srcset', srcset);
                }
                
                $item.removeClass('vortex-lazy-load').addClass('vortex-lazy-loaded');
                
                // Listen for load event to remove placeholder
                $item.on('load', () => {
                    $item.addClass('loaded');
                });
            } else if ($item.hasClass('vortex-lazy-background')) {
                // Background image
                const src = $item.data('bg');
                if (src) {
                    $item.css('background-image', `url(${src})`);
                }
                
                $item.removeClass('vortex-lazy-load').addClass('vortex-lazy-loaded');
            } else if ($item.hasClass('vortex-lazy-content')) {
                // AJAX loaded content
                const contentSource = $item.data('content-source');
                if (contentSource) {
                    this.loadLazyContent($item, contentSource);
                }
            }
            
            // Trigger custom event
            $(document).trigger('vortex_lazy_item_loaded', [$item]);
        },
        
        /**
         * Load all lazy items at once
         */
        loadAllLazyItems: function() {
            $('.vortex-lazy-load').each((i, item) => {
                this.loadLazyItem(item);
            });
        },
        
        /**
         * Check which lazy items are in viewport
         */
        checkLazyItemsInViewport: function() {
            $('.vortex-lazy-load').each((i, item) => {
                const $item = $(item);
                
                if (this.isElementInViewport($item)) {
                    this.loadLazyItem(item);
                }
            });
        },
        
        /**
         * Load content via AJAX for lazy loading
         */
        loadLazyContent: function($container, source) {
            // Show loading indicator
            $container.html('<div class="vortex-loading"><span class="vortex-loading-text">Loading content...</span></div>');
            
            // Extract parameters
            let url, data;
            
            if (typeof source === 'string') {
                // Simple URL
                url = source;
                data = {};
            } else {
                // Object with URL and data
                url = source.url || vortex_marketplace_vars.ajax_url;
                data = source.data || {};
            }
            
            // Add nonce and action if using admin-ajax.php
            if (url.includes('admin-ajax.php') && !data.action) {
                data.action = 'load_lazy_content';
                data.nonce = vortex_marketplace_vars.nonce;
            }
            
            // Make AJAX request
            $.ajax({
                url: url,
                type: 'POST',
                data: data,
                success: (response) => {
                    // Remove loading indicator
                    $container.html('');
                    
                    // Parse response if needed
                    let content = response;
                    if (typeof response === 'object') {
                        content = response.success ? response.data : 'Error loading content';
                    }
                    
                    // Insert content
                    $container.html(content).removeClass('vortex-lazy-load').addClass('vortex-lazy-loaded');
                    
                    // Initialize any components in the loaded content
                    this.initLoadedContent($container);
                    
                    // Trigger custom event
                    $(document).trigger('vortex_lazy_content_loaded', [$container, content]);
                },
                error: (xhr, status, error) => {
                    console.error('Error loading lazy content:', error);
                    $container.html('<div class="vortex-message vortex-message-error">Failed to load content</div>');
                }
            });
        },

        /**
         * Initialize components in dynamically loaded content
         */
        initLoadedContent: function($container) {
            // Initialize any carousels
            const $carousels = $container.find('.marketplace-carousel-display');
            if ($carousels.length) {
                this.initCarousels();
            }
            
            // Initialize any forms
            const $forms = $container.find('.marketplace-form');
            if ($forms.length) {
                this.initForms();
            }
            
            // Initialize modals
            const $modals = $container.find('.marketplace-modal');
            if ($modals.length) {
                this.setupModalHandlers();
            }
        },
        
        /**
         * Check if element is in viewport
         */
        isElementInViewport: function($el) {
            if (!$el.length) return false;
            
            const rect = $el[0].getBoundingClientRect();
            
            return (
                rect.top >= 0 &&
                rect.left >= 0 &&
                rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                rect.right <= (window.innerWidth || document.documentElement.clientWidth)
            );
        },
        
        /**
         * Show loading state for an operation
         */
        showLoadingState: function(id) {
            const $target = id ? $(`[data-loading-target="${id}"]`) : null;
            
            if ($target && $target.length) {
                $target.addClass('loading').attr('aria-busy', 'true');
                
                // Disable interactive elements
                $target.find('button, input, select, textarea, a').prop('disabled', true);
            } else {
                // Global loading indicator
                if (!$('.vortex-global-loading').length) {
                    $('body').append(`
                        <div class="vortex-global-loading" aria-live="polite">
                            <div class="vortex-loading">
                                <span class="vortex-loading-text">Loading...</span>
                            </div>
                        </div>
                    `);
                }
                
                $('.vortex-global-loading').addClass('active');
                $('body').addClass('loading-active');
            }
        },
        
        /**
         * Hide loading state
         */
        hideLoadingState: function(id) {
            const $target = id ? $(`[data-loading-target="${id}"]`) : null;
            
            if ($target && $target.length) {
                $target.removeClass('loading').removeAttr('aria-busy');
                
                // Re-enable interactive elements
                $target.find('button, input, select, textarea, a').prop('disabled', false);
            } else {
                // Global loading indicator
                $('.vortex-global-loading').removeClass('active');
                $('body').removeClass('loading-active');
            }
        },
        
        /**
         * Debounce function to limit how often a function can be called
         */
        debounce: function(func, wait, immediate) {
            let timeout;
            
            return function() {
                const context = this;
                const args = arguments;
                
                const later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                
                const callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                
                if (callNow) func.apply(context, args);
            };
        },
        
        /**
         * Notify server about events with debouncing
         */
        debouncedServerNotify: function(eventType, data) {
            // Create unique key for this event type
            const key = 'notify_' + eventType;
            
            // Clear any existing timeout
            if (this.state.debouncedFunctions[key]) {
                clearTimeout(this.state.debouncedFunctions[key]);
            }
            
            // Set new timeout
            this.state.debouncedFunctions[key] = setTimeout(() => {
                $.ajax({
                    url: vortex_marketplace_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'track_marketplace_event',
                        event_type: eventType,
                        event_data: data,
                        nonce: vortex_marketplace_vars.nonce
                    },
                    success: (response) => {
                        // Optional success handling
                        if (vortex_marketplace_vars.debug_mode) {
                            console.log('Event tracked:', eventType, response);
                        }
                    },
                    error: (xhr, status, error) => {
                        if (vortex_marketplace_vars.debug_mode) {
                            console.error('Failed to track event:', eventType, error);
                        }
                    },
                    complete: () => {
                        // Clear from debounced functions
                        delete this.state.debouncedFunctions[key];
                    }
                });
            }, this.settings.ajaxDebounceTime);
        },
        
        /**
         * Store data in a cross-browser compatible way
         */
        setData: function(key, value, useSession = false) {
            if (supportsLocalStorage) {
                const storage = useSession ? sessionStorage : localStorage;
                storage.setItem('vortex_' + key, JSON.stringify(value));
                return true;
            } else {
                // Fallback to cookie
                try {
                    const valueStr = JSON.stringify(value);
                    const days = useSession ? null : 30; // null = session cookie
                    this.setCookie('vortex_' + key, valueStr, days);
                    return true;
                } catch (e) {
                    console.error('Failed to store data:', e);
                    return false;
                }
            }
        },
        
        /**
         * Get stored data
         */
        getData: function(key, useSession = false) {
            if (supportsLocalStorage) {
                const storage = useSession ? sessionStorage : localStorage;
                const data = storage.getItem('vortex_' + key);
                
                if (data) {
                    try {
                        return JSON.parse(data);
                    } catch (e) {
                        return data;
                    }
                }
            } else {
                // Fallback to cookie
                const data = this.getCookie('vortex_' + key);
                
                if (data) {
                    try {
                        return JSON.parse(data);
                    } catch (e) {
                        return data;
                    }
                }
            }
            
            return null;
        },
        
        /**
         * Set a cookie
         */
        setCookie: function(name, value, days) {
            let expires = "";
            
            if (days) {
                const date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = "; expires=" + date.toUTCString();
            }
            
            document.cookie = name + "=" + encodeURIComponent(value) + expires + "; path=/; SameSite=Strict";
        },
        
        /**
         * Get a cookie
         */
        getCookie: function(name) {
            const nameEQ = name + "=";
            const ca = document.cookie.split(';');
            
            for (let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) == ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) == 0) return decodeURIComponent(c.substring(nameEQ.length, c.length));
            }
            
            return null;
        },
        
        /**
         * Set up global event listeners
         */
        setupEventListeners: function() {
            // Global click handlers
            $(document).on('click', '.vortex-btn, button, [role="button"]', function() {
                // Add ripple effect to buttons if animations are not reduced
                if (!VortexMarketplace.settings.isAnimationsReduced) {
                    VortexMarketplace.addRippleEffect($(this));
                }
            });
            
            // Load more buttons
            $(document).on('click', '.marketplace-load-more-button', function(e) {
                e.preventDefault();
                
                const $this = $(this);
                const page = parseInt($this.data('page')) || 1;
                const display = $this.data('display') || 'grid';
                
                // Store original text for later
                if (!$this.data('original-text')) {
                    $this.data('original-text', $this.text());
                }
                
                VortexMarketplace.loadMoreArtworks(page + 1, display, $this);
            });
            
            // Global keyboard handlers for modals
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $('.marketplace-modal.active').length) {
                    VortexMarketplace.closeAllModals();
                }
            });
            
            // Track preferred reduced motion setting
            const reducedMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
            const handleReducedMotionChange = () => {
                VortexMarketplace.settings.isAnimationsReduced = reducedMotionQuery.matches;
            };
            
            if (typeof reducedMotionQuery.addEventListener === 'function') {
                reducedMotionQuery.addEventListener('change', handleReducedMotionChange);
            } else {
                // For older browsers
                reducedMotionQuery.addListener(handleReducedMotionChange);
            }
        },
        
        /**
         * Add ripple effect to a button
         */
        addRippleEffect: function($element) {
            const $ripple = $('<span class="vortex-ripple"></span>');
            $element.append($ripple);
            
            const rect = $element[0].getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            
            $ripple.css({
                width: size + 'px',
                height: size + 'px',
                top: '50%',
                left: '50%',
                marginTop: -(size / 2) + 'px',
                marginLeft: -(size / 2) + 'px'
            });
            
            setTimeout(() => {
                $ripple.remove();
            }, 600);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        VortexMarketplace.init();
    });

    // Make VortexMarketplace accessible globally
    window.VortexMarketplace = VortexMarketplace;

})(jQuery); 