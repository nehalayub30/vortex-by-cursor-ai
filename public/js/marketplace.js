/**
 * Vortex Marketplace functionality
 *
 * Handles filtering, sorting, and loading more items in the marketplace.
 */

(function ($) {
    'use strict';

    // Store the current state of filters and pagination
    const state = {
        category: '',
        style: '',
        artist: '',
        aiEngine: '',
        sortBy: 'newest',
        page: 1,
        maxPages: 1,
        isLoading: false
    };

    // Initialize the marketplace
    function initMarketplace() {
        // Get filter values from query parameters if any
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('category')) {
            state.category = urlParams.get('category');
            $('#vortex-category-filter').val(state.category);
        }
        if (urlParams.has('style')) {
            state.style = urlParams.get('style');
            $('#vortex-style-filter').val(state.style);
        }
        if (urlParams.has('sort')) {
            state.sortBy = urlParams.get('sort');
            $('#vortex-sort').val(state.sortBy);
        }

        // Set max pages from data attribute
        if ($('.vortex-marketplace').data('max-pages')) {
            state.maxPages = parseInt($('.vortex-marketplace').data('max-pages'));
        }

        // Add event listeners
        attachEventListeners();

        // Update the URL with initial parameters
        updateUrl();
    }

    // Attach event listeners to filter selects and buttons
    function attachEventListeners() {
        // Category filter change
        $('#vortex-category-filter').on('change', function() {
            state.category = $(this).val();
            resetAndFetch();
        });

        // Style filter change
        $('#vortex-style-filter').on('change', function() {
            state.style = $(this).val();
            resetAndFetch();
        });

        // Sorting change
        $('#vortex-sort').on('change', function() {
            state.sortBy = $(this).val();
            resetAndFetch();
        });

        // Load more button
        $('.vortex-load-more').on('click', function() {
            loadMoreArtworks();
        });

        // Any additional custom filters
        $('.vortex-filter-select').on('change', function() {
            const filterType = $(this).data('filter');
            if (filterType) {
                state[filterType] = $(this).val();
                resetAndFetch();
            }
        });
    }

    // Reset page counter and fetch new results
    function resetAndFetch() {
        state.page = 1;
        updateUrl();
        fetchArtworks(true); // true means replace existing content
    }

    // Update URL with current filters
    function updateUrl() {
        if (history.pushState) {
            const params = new URLSearchParams();
            
            if (state.category) {
                params.set('category', state.category);
            }
            
            if (state.style) {
                params.set('style', state.style);
            }
            
            if (state.sortBy && state.sortBy !== 'newest') {
                params.set('sort', state.sortBy);
            }
            
            const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            window.history.pushState({ path: newUrl }, '', newUrl);
        }
    }

    // Load more artworks
    function loadMoreArtworks() {
        if (state.isLoading || state.page >= state.maxPages) {
            return;
        }
        
        state.page++;
        fetchArtworks(false); // false means append to existing content
    }

    // Fetch artworks via AJAX
    function fetchArtworks(replace) {
        if (state.isLoading) {
            return;
        }
        
        state.isLoading = true;
        $('.vortex-marketplace-loading').show();
        
        // If replacing content, scroll to top of grid
        if (replace) {
            $('.vortex-artwork-grid').empty();
            $('html, body').animate({
                scrollTop: $('.vortex-marketplace').offset().top - 50
            }, 500);
        }
        
        // Prepare data for AJAX request
        const data = {
            action: 'vortex_load_artworks',
            nonce: vortex_marketplace.nonce,
            page: state.page,
            category: state.category,
            style: state.style,
            sort: state.sortBy
        };
        
        // Add other filters if set
        if (state.artist) {
            data.artist = state.artist;
        }
        
        if (state.aiEngine) {
            data.ai_engine = state.aiEngine;
        }
        
        // Make AJAX request
        $.ajax({
            url: vortex_marketplace.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    if (replace) {
                        $('.vortex-artwork-grid').html(response.data.html);
                    } else {
                        $('.vortex-artwork-grid').append(response.data.html);
                    }
                    
                    state.maxPages = response.data.max_pages;
                    
                    // Update pagination info
                    if (response.data.showing && response.data.total) {
                        $('.vortex-pagination-info').html(
                            'Showing ' + response.data.showing + ' of ' + response.data.total + ' artworks'
                        );
                    }
                    
                    // Hide load more button if we're at the last page
                    if (state.page >= state.maxPages) {
                        $('.vortex-load-more').hide();
                    } else {
                        $('.vortex-load-more').show();
                    }
                } else {
                    console.error('Error loading artworks:', response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
            },
            complete: function() {
                state.isLoading = false;
                $('.vortex-marketplace-loading').hide();
            }
        });
    }

    // Initialize when document is ready
    $(document).ready(function() {
        if ($('.vortex-marketplace').length) {
            initMarketplace();
        }

        // Initialize any individual artwork sliders
        if ($('.vortex-artwork-slider').length) {
            $('.vortex-artwork-slider').each(function() {
                initArtworkSlider($(this));
            });
        }
    });

    // Initialize an artwork slider
    function initArtworkSlider($slider) {
        const $slides = $slider.find('.vortex-slider-item');
        const $dotsContainer = $slider.find('.vortex-slider-dots');
        const $prevBtn = $slider.find('.vortex-slider-prev');
        const $nextBtn = $slider.find('.vortex-slider-next');
        
        let currentSlide = 0;
        const slideCount = $slides.length;
        
        // Create dots if they don't exist
        if ($dotsContainer.length && $dotsContainer.children().length === 0) {
            for (let i = 0; i < slideCount; i++) {
                $dotsContainer.append('<span class="vortex-slider-dot" data-slide="' + i + '"></span>');
            }
        }
        
        // Initialize the slider
        showSlide(0);
        
        // Add event listeners
        $prevBtn.on('click', function() {
            showSlide(currentSlide - 1);
        });
        
        $nextBtn.on('click', function() {
            showSlide(currentSlide + 1);
        });
        
        $dotsContainer.on('click', '.vortex-slider-dot', function() {
            const slideIndex = $(this).data('slide');
            showSlide(slideIndex);
        });
        
        // Show the specified slide
        function showSlide(index) {
            if (index < 0) {
                index = slideCount - 1;
            } else if (index >= slideCount) {
                index = 0;
            }
            
            $slides.removeClass('active').eq(index).addClass('active');
            $dotsContainer.find('.vortex-slider-dot').removeClass('active').eq(index).addClass('active');
            
            currentSlide = index;
        }
    }

})(jQuery); 