/**
 * Public-facing JavaScript for VORTEX AI Marketplace
 *
 * Core functionality for the marketplace frontend
 *
 * @package    Vortex_AI_Marketplace
 * @subpackage Vortex_AI_Marketplace/public/js
 * @author     Marianne Nems <Marianne@VortexArtec.com>
 */

(function($) {
    'use strict';

    /**
     * VortexPublic object for managing marketplace public functionality
     */
    window.VortexPublic = {
        /**
         * Initialize public functionality
         */
        init: function() {
            this.setupGridLayout();
            this.setupLazyLoading();
            this.setupFilters();
            this.setupModals();
            this.setupTooltips();
            this.setupImageZoom();
            this.setupAjaxSearch();
            this.setupMiniCart();
            this.setupAddToCart();
            this.setupPagination();
            this.setupNotifications();
            this.setupResponsiveElements();
            this.setupImageGallery();
            this.setupThemeCompatibility();
        },

        /**
         * Initialize grid layout for artwork and artist grids
         */
        setupGridLayout: function() {
            $('.vortex-grid').each(function() {
                const $grid = $(this);
                const columns = $grid.data('columns') || 3;
                
                // Add column class
                $grid.addClass('vortex-grid-columns-' + columns);
                
                // Adjust height for square displays if needed
                if ($grid.hasClass('vortex-grid-square')) {
                    VortexPublic.adjustGridItemHeight($grid);
                    $(window).on('resize', function() {
                        VortexPublic.adjustGridItemHeight($grid);
                    });
                }
            });
        },

        /**
         * Adjust grid item height to maintain square aspect ratio
         */
        adjustGridItemHeight: function($grid) {
            const $items = $grid.find('.vortex-grid-item');
            $items.each(function() {
                const width = $(this).width();
                $(this).height(width);
            });
        },

        /**
         * Initialize lazy loading for images
         */
        setupLazyLoading: function() {
            const lazyImages = document.querySelectorAll('.vortex-lazy-load');
            
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver(function(entries, observer) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            const lazyImage = entry.target;
                            lazyImage.src = lazyImage.dataset.src;
                            if (lazyImage.dataset.srcset) {
                                lazyImage.srcset = lazyImage.dataset.srcset;
                            }
                            lazyImage.classList.remove('vortex-lazy-load');
                            imageObserver.unobserve(lazyImage);
                        }
                    });
                });

                lazyImages.forEach(function(lazyImage) {
                    imageObserver.observe(lazyImage);
                });
            } else {
                // Fallback for browsers without IntersectionObserver
                let lazyLoadThrottleTimeout;
                
                function lazyLoad() {
                    if (lazyLoadThrottleTimeout) {
                        clearTimeout(lazyLoadThrottleTimeout);
                    }

                    lazyLoadThrottleTimeout = setTimeout(function() {
                        const scrollTop = window.pageYOffset;
                        lazyImages.forEach(function(lazyImage) {
                            if (lazyImage.offsetTop < (window.innerHeight + scrollTop)) {
                                lazyImage.src = lazyImage.dataset.src;
                                if (lazyImage.dataset.srcset) {
                                    lazyImage.srcset = lazyImage.dataset.srcset;
                                }
                                lazyImage.classList.remove('vortex-lazy-load');
                            }
                        });
                        if (lazyImages.length === 0) { 
                            document.removeEventListener('scroll', lazyLoad);
                            window.removeEventListener('resize', lazyLoad);
                            window.removeEventListener('orientationChange', lazyLoad);
                        }
                    }, 20);
                }

                document.addEventListener('scroll', lazyLoad);
                window.addEventListener('resize', lazyLoad);
                window.addEventListener('orientationChange', lazyLoad);
                
                // Initial load
                lazyLoad();
            }
        },

        /**
         * Initialize filter functionality for artwork and artist grids
         */
        setupFilters: function() {
            // Filter buttons click handler
            $('.vortex-filter-button').on('click', function(e) {
                e.preventDefault();
                
                const $this = $(this);
                const $filterContainer = $this.closest('.vortex-filters');
                const filterType = $filterContainer.data('filter-type');
                const filterValue = $this.data('filter');
                
                // Update active state
                $filterContainer.find('.vortex-filter-button').removeClass('active');
                $this.addClass('active');
                
                // Apply filter to grid
                const $grid = $filterContainer.siblings('.vortex-grid');
                
                if (filterValue === 'all') {
                    $grid.find('.vortex-grid-item').show();
                } else {
                    $grid.find('.vortex-grid-item').hide();
                    $grid.find('.vortex-grid-item[data-' + filterType + '="' + filterValue + '"]').show();
                }
                
                // Trigger resize to adjust layout
                $(window).trigger('resize');
            });
            
            // Range filters (price, etc.)
            $('.vortex-range-filter').each(function() {
                const $rangeFilter = $(this);
                const $minInput = $rangeFilter.find('.vortex-range-min');
                const $maxInput = $rangeFilter.find('.vortex-range-max');
                const $applyButton = $rangeFilter.find('.vortex-range-apply');
                
                $applyButton.on('click', function(e) {
                    e.preventDefault();
                    
                    const minValue = parseFloat($minInput.val()) || 0;
                    const maxValue = parseFloat($maxInput.val()) || 999999;
                    const filterType = $rangeFilter.data('filter-type');
                    
                    // Apply filter to grid
                    const $grid = $rangeFilter.closest('.vortex-filters').siblings('.vortex-grid');
                    
                    $grid.find('.vortex-grid-item').each(function() {
                        const itemValue = parseFloat($(this).data(filterType)) || 0;
                        if (itemValue >= minValue && itemValue <= maxValue) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });
                    
                    // Trigger resize to adjust layout
                    $(window).trigger('resize');
                });
            });
        },

        /**
         * Initialize modal functionality
         */
        setupModals: function() {
            // Open modal
            $(document).on('click', '.vortex-modal-trigger', function(e) {
                e.preventDefault();
                
                const modalId = $(this).data('modal-id');
                $('#' + modalId).addClass('vortex-modal-open');
                $('body').addClass('vortex-modal-active');
            });
            
            // Close modal
            $(document).on('click', '.vortex-modal-close, .vortex-modal-overlay', function(e) {
                e.preventDefault();
                
                $('.vortex-modal').removeClass('vortex-modal-open');
                $('body').removeClass('vortex-modal-active');
            });
            
            // Close on ESC key
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27 && $('.vortex-modal-open').length) {
                    $('.vortex-modal').removeClass('vortex-modal-open');
                    $('body').removeClass('vortex-modal-active');
                }
            });
        },

        /**
         * Initialize tooltips
         */
        setupTooltips: function() {
            $('.vortex-tooltip').each(function() {
                const $tooltip = $(this);
                const $trigger = $tooltip.find('.vortex-tooltip-trigger');
                const $content = $tooltip.find('.vortex-tooltip-content');
                
                $trigger.on('mouseenter', function() {
                    $content.addClass('vortex-tooltip-active');
                    
                    // Position the tooltip
                    const triggerRect = $trigger[0].getBoundingClientRect();
                    const contentRect = $content[0].getBoundingClientRect();
                    
                    let top = triggerRect.bottom + window.scrollY;
                    let left = triggerRect.left + window.scrollX + (triggerRect.width / 2) - (contentRect.width / 2);
                    
                    // Keep tooltip within viewport
                    const viewportWidth = window.innerWidth;
                    if (left < 10) left = 10;
                    if (left + contentRect.width > viewportWidth - 10) {
                        left = viewportWidth - contentRect.width - 10;
                    }
                    
                    $content.css({
                        top: top + 'px',
                        left: left + 'px'
                    });
                });
                
                $trigger.on('mouseleave', function() {
                    $content.removeClass('vortex-tooltip-active');
                });
            });
        },

        /**
         * Initialize image zoom functionality
         */
        setupImageZoom: function() {
            $('.vortex-image-zoom').each(function() {
                const $container = $(this);
                const $image = $container.find('img');
                
                $container.on('mouseenter', function() {
                    $container.addClass('vortex-zoom-active');
                });
                
                $container.on('mousemove', function(e) {
                    if (!$container.hasClass('vortex-zoom-active')) return;
                    
                    const containerRect = $container[0].getBoundingClientRect();
                    const xPos = (e.clientX - containerRect.left) / containerRect.width;
                    const yPos = (e.clientY - containerRect.top) / containerRect.height;
                    
                    $image.css({
                        'transform-origin': (xPos * 100) + '% ' + (yPos * 100) + '%'
                    });
                });
                
                $container.on('mouseleave', function() {
                    $container.removeClass('vortex-zoom-active');
                });
            });
        },

        /**
         * Initialize AJAX search functionality
         */
        setupAjaxSearch: function() {
            const $searchForm = $('.vortex-search-form');
            const $searchInput = $searchForm.find('.vortex-search-input');
            const $searchResults = $('.vortex-search-results');
            const $loadingIndicator = $searchForm.find('.vortex-search-loading');
            
            let searchTimer;
            let currentSearch = '';
            
            $searchInput.on('keyup', function() {
                const query = $(this).val();
                
                // Don't search if query is too short or same as before
                if (query.length < 3 || query === currentSearch) return;
                
                // Clear previous timer
                clearTimeout(searchTimer);
                
                // Set new timer to avoid too many requests
                searchTimer = setTimeout(function() {
                    currentSearch = query;
                    $loadingIndicator.show();
                    
                    $.ajax({
                        url: vortexVars.ajaxUrl,
                        type: 'post',
                        data: {
                            action: 'vortex_ajax_search',
                            nonce: vortexVars.nonce,
                            query: query,
                            filters: VortexPublic.getSearchFilters()
                        },
                        success: function(response) {
                            $loadingIndicator.hide();
                            
                            if (response.success) {
                                $searchResults.html(response.data.html);
                                $searchResults.show();
                                
                                // Initialize lazy loading for new images
                                VortexPublic.setupLazyLoading();
                            } else {
                                $searchResults.html('<p class="vortex-search-no-results">' + vortexVars.i18n.noResults + '</p>');
                                $searchResults.show();
                            }
                        },
                        error: function() {
                            $loadingIndicator.hide();
                            $searchResults.html('<p class="vortex-search-error">' + vortexVars.i18n.error + '</p>');
                            $searchResults.show();
                        }
                    });
                }, 500);
            });
            
            // Close search results when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.vortex-search-form, .vortex-search-results').length) {
                    $searchResults.hide();
                }
            });
        },

        /**
         * Get current search filters
         */
        getSearchFilters: function() {
            const filters = {};
            
            // Category filter
            const selectedCategory = $('.vortex-category-filter .active').data('filter');
            if (selectedCategory && selectedCategory !== 'all') {
                filters.category = selectedCategory;
            }
            
            // Tag filter
            const selectedTag = $('.vortex-tag-filter .active').data('filter');
            if (selectedTag && selectedTag !== 'all') {
                filters.tag = selectedTag;
            }
            
            // Artist filter
            const selectedArtist = $('.vortex-artist-filter .active').data('filter');
            if (selectedArtist && selectedArtist !== 'all') {
                filters.artist = selectedArtist;
            }
            
            // Price range filter
            const minPrice = $('.vortex-price-filter .vortex-range-min').val();
            const maxPrice = $('.vortex-price-filter .vortex-range-max').val();
            if (minPrice) filters.min_price = minPrice;
            if (maxPrice) filters.max_price = maxPrice;
            
            // AI generated filter
            const aiGenerated = $('.vortex-ai-filter .active').data('filter');
            if (aiGenerated && aiGenerated !== 'all') {
                filters.ai_generated = aiGenerated === 'ai' ? 1 : 0;
            }
            
            return filters;
        },

        /**
         * Initialize mini-cart functionality
         */
        setupMiniCart: function() {
            const $miniCartTrigger = $('.vortex-mini-cart-trigger');
            const $miniCart = $('.vortex-mini-cart');
            
            $miniCartTrigger.on('click', function(e) {
                e.preventDefault();
                $miniCart.toggleClass('vortex-mini-cart-open');
            });
            
            // Close mini-cart when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.vortex-mini-cart, .vortex-mini-cart-trigger').length) {
                    $miniCart.removeClass('vortex-mini-cart-open');
                }
            });
            
            // Update mini-cart on AJAX operations
            $(document.body).on('vortex_cart_updated', function() {
                VortexPublic.refreshMiniCart();
            });
        },

        /**
         * Refresh mini-cart contents via AJAX
         */
        refreshMiniCart: function() {
            $.ajax({
                url: vortexVars.ajaxUrl,
                type: 'post',
                data: {
                    action: 'vortex_get_refreshed_fragments',
                    nonce: vortexVars.nonce
                },
                success: function(response) {
                    if (response && response.fragments) {
                        $.each(response.fragments, function(key, value) {
                            $(key).replaceWith(value);
                        });
                    }
                }
            });
        },

        /**
         * Initialize add to cart functionality
         */
        setupAddToCart: function() {
            $(document).on('click', '.vortex-add-to-cart', function(e) {
                e.preventDefault();
                
                const $button = $(this);
                const artworkId = $button.data('artwork-id');
                const quantity = $button.closest('.vortex-add-to-cart-form').find('.vortex-quantity').val() || 1;
                
                // Prevent double clicks
                if ($button.hasClass('vortex-loading') || $button.hasClass('vortex-added')) {
                    return;
                }
                
                $button.addClass('vortex-loading').text(vortexVars.i18n.pleaseWait);
                
                $.ajax({
                    url: vortexVars.ajaxUrl,
                    type: 'post',
                    data: {
                        action: 'vortex_add_to_cart',
                        nonce: vortexVars.nonce,
                        artwork_id: artworkId,
                        quantity: quantity
                    },
                    success: function(response) {
                        $button.removeClass('vortex-loading');
                        
                        if (response.success) {
                            $button.addClass('vortex-added').text(vortexVars.i18n.added);
                            
                            // Update cart count and fragments
                            $('.vortex-cart-count').text(response.data.cart_count);
                            $('.vortex-cart-subtotal').text(response.data.cart_subtotal);
                            
                            // Trigger event for other components
                            $(document.body).trigger('vortex_cart_updated');
                            
                            // Show notification
                            VortexPublic.showNotification('success', response.data.message);
                            
                            // Reset button after delay
                            setTimeout(function() {
                                $button.removeClass('vortex-added').text(vortexVars.i18n.addToCart);
                            }, 2000);
                        } else {
                            VortexPublic.showNotification('error', response.data.message);
                            $button.text(vortexVars.i18n.addToCart);
                        }
                    },
                    error: function() {
                        $button.removeClass('vortex-loading').text(vortexVars.i18n.addToCart);
                        VortexPublic.showNotification('error', vortexVars.i18n.error);
                    }
                });
            });
        },

        /**
         * Initialize load more pagination
         */
        setupPagination: function() {
            $('.vortex-load-more').on('click', function(e) {
                e.preventDefault();
                
                const $button = $(this);
                const $grid = $button.closest('.vortex-pagination-container').siblings('.vortex-grid');
                const page = $button.data('page') || 1;
                const maxPages = $button.data('max-pages') || 1;
                const gridType = $button.data('grid-type') || 'artwork';
                
                // Prevent double clicks
                if ($button.hasClass('vortex-loading')) {
                    return;
                }
                
                // Hide if last page
                if (page >= maxPages) {
                    $button.hide();
                    return;
                }
                
                $button.addClass('vortex-loading').text(vortexVars.i18n.loading);
                
                $.ajax({
                    url: vortexVars.ajaxUrl,
                    type: 'post',
                    data: {
                        action: 'vortex_load_more_' + gridType,
                        nonce: vortexVars.nonce,
                        page: page + 1,
                        attributes: $button.data('attributes') || {}
                    },
                    success: function(response) {
                        $button.removeClass('vortex-loading').text(vortexVars.i18n.loadMore);
                        
                        if (response.success) {
                            // Append new items
                            $grid.append(response.data.html);
                            
                            // Update page number
                            $button.data('page', page + 1);
                            
                            // Hide button if last page
                            if (page + 1 >= maxPages) {
                                $button.hide();
                            }
                            
                            // Initialize lazy loading for new images
                            VortexPublic.setupLazyLoading();
                            
                            // Trigger resize to adjust layout
                            $(window).trigger('resize');
                        } else {
                            VortexPublic.showNotification('error', response.data.message);
                        }
                    },
                    error: function() {
                        $button.removeClass('vortex-loading').text(vortexVars.i18n.loadMore);
                        VortexPublic.showNotification('error', vortexVars.i18n.error);
                    }
                });
            });
        },

        /**
         * Initialize notification system
         */
        setupNotifications: function() {
            // Create container if it doesn't exist
            if (!$('.vortex-notifications').length) {
                $('body').append('<div class="vortex-notifications"></div>');
            }
        },

        /**
         * Show notification
         */
        showNotification: function(type, message, autoHide = true) {
            const $container = $('.vortex-notifications');
            const notificationId = 'vortex-notification-' + Date.now();
            
            const $notification = $('<div class="vortex-notification vortex-notification-' + type + '" id="' + notificationId + '">' +
                '<div class="vortex-notification-icon"></div>' +
                '<div class="vortex-notification-message">' + message + '</div>' +
                '<div class="vortex-notification-close"></div>' +
                '</div>');
            
            $container.append($notification);
            
            // Fade in
            setTimeout(function() {
                $notification.addClass('vortex-notification-visible');
            }, 10);
            
            // Auto-hide after delay
            if (autoHide) {
                setTimeout(function() {
                    $notification.removeClass('vortex-notification-visible');
                    setTimeout(function() {
                        $notification.remove();
                    }, 300);
                }, 5000);
            }
            
            // Close button
            $notification.find('.vortex-notification-close').on('click', function() {
                $notification.removeClass('vortex-notification-visible');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            });
        },

        /**
         * Setup responsive elements
         */
        setupResponsiveElements: function() {
            // Responsive grid
            function adjustGridColumns() {
                $('.vortex-grid').each(function() {
                    const $grid = $(this);
                    const baseColumns = parseInt($grid.data('columns')) || 3;
                    let columns = baseColumns;
                    
                    // Adjust columns based on viewport width
                    if (window.innerWidth < 480) {
                        columns = 1;
                    } else if (window.innerWidth < 768) {
                        columns = Math.min(baseColumns, 2);
                    }
                    
                    // Remove old column classes
                    $grid.removeClass(function(index, className) {
                        return (className.match(/(^|\s)vortex-grid-columns-\S+/g) || []).join(' ');
                    });
                    
                    // Add new column class
                    $grid.addClass('vortex-grid-columns-' + columns);
                });
            }
            
            // Initial adjustment
            adjustGridColumns();
            
            // Adjust on resize
            $(window).on('resize', function() {
                adjustGridColumns();
                
                // Adjust square grid items if needed
                $('.vortex-grid.vortex-grid-square').each(function() {
                    VortexPublic.adjustGridItemHeight($(this));
                });
            });
        },

        /**
         * Setup image gallery
         */
        setupImageGallery: function() {
            $('.vortex-gallery').each(function() {
                const $gallery = $(this);
                const $mainImage = $gallery.find('.vortex-gallery-main-image img');
                const $thumbnails = $gallery.find('.vortex-gallery-thumbnail');
                
                $thumbnails.on('click', function() {
                    const $thumbnail = $(this);
                    const fullSrc = $thumbnail.data('full-src');
                    const srcset = $thumbnail.data('full-srcset');
                    
                    // Update main image
                    $mainImage.attr('src', fullSrc);
                    if (srcset) {
                        $mainImage.attr('srcset', srcset);
                    }
                    
                    // Update active state
                    $thumbnails.removeClass('active');
                    $thumbnail.addClass('active');
                });
            });
        },

        /**
         * Setup theme compatibility adjustments
         */
        setupThemeCompatibility: function() {
            // Check for specific themes and apply fixes
            const themeBodyClass = $('body').attr('class');
            
            // Twenty Twenty-One fixes
            if (themeBodyClass.includes('twenty-twenty-one')) {
                $('.vortex-button').addClass('wp-element-button');
            }
            
            // Astra fixes
            if (themeBodyClass.includes('astra')) {
                // Fix for Astra's global box-sizing
                $('<style>.vortex-marketplace *, .vortex-marketplace *::before, .vortex-marketplace *::after { box-sizing: border-box; }</style>').appendTo('head');
            }
            
            // Add theme-specific adjustments here
            // ...
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        VortexPublic.init();
    });

})(jQuery); 